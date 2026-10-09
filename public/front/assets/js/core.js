/* ============================================================
   کافی‌نت آنلاین — هسته اپ مشتری (Vanilla JS — بدون jQuery)
   [Task 9] مهاجرت کامل از jQuery به DOM استاندارد:
   • CN.api روی XMLHttpRequest (حفظ xhr.status/responseText در خطاها)
   • همهٔ متدهای DOM اکنون عنصر خام می‌پذیرند (نه آبجکت jQuery)
   • بدون وابستگی به Node/بیلد — فایل مستقل لینک‌شده به صفحات
   ============================================================ */
window.CN = (function () {
    'use strict';

    /* ---------- پیکربندی گیت‌وی پیش‌نمایش ---------- */
    var PORT_PARAM = 'XTransformPort';
    var port = '';
    try {
        port = new URLSearchParams(window.location.search).get(PORT_PARAM) || '';
    } catch (e) { port = ''; }

    /* v41 — قفل ضدتکرار ۴۰۱: در هر بارگذاری صفحه فقط یک پیام/یک ریدایرکت */
    var authRedirectPending = false;

    /** افزودن پارامتر گیت‌وی به مسیر داخلی */
    function withPort(url) {
        if (!port) { return url; }
        if (url.indexOf(PORT_PARAM + '=') !== -1) { return url; }
        return url + (url.indexOf('?') === -1 ? '?' : '&') + PORT_PARAM + '=' + port;
    }

    /** مسیر API (همیشه با پارامتر گیت‌وی) */
    function apiUrl(path) {
        return withPort('/api/v1' + path);
    }

    /* ---------- نشست (توکن Sanctum) ---------- */
    var TOKEN_KEY = 'cn_token';
    var USER_KEY = 'cn_user';

    function token() {
        try { return window.localStorage.getItem(TOKEN_KEY) || ''; } catch (e) { return ''; }
    }

    function user() {
        try { return JSON.parse(window.localStorage.getItem(USER_KEY) || 'null'); } catch (e) { return null; }
    }

    function setSession(newToken, newUser) {
        try {
            window.localStorage.setItem(TOKEN_KEY, newToken);
            window.localStorage.setItem(USER_KEY, JSON.stringify(newUser));
        } catch (e) { /* حافظه محدود */ }
    }

    function clearSession() {
        try {
            window.localStorage.removeItem(TOKEN_KEY);
            window.localStorage.removeItem(USER_KEY);
        } catch (e) { /* noop */ }
    }

    /** گارد صفحات محافظت‌شده */
    function requireAuth() {
        if (!token()) {
            window.location.replace(withPort('/app/auth'));
            return false;
        }
        return true;
    }

    /**
     * گارد تکمیل پروفایل — مشتری تا اطلاعات خود را کامل نکرده، به صفحات اپ دسترسی ندارد
     * (سفارش/شارژ/تیکت در سرور هم مسدود می‌شوند)؛ فقط auth و صفحات پروفایل مجازند.
     */
    function requireCompleteProfile() {
        if (!requireAuth()) { return false; }
        var u = user();
        if (u && !u.profile_completed) {
            toast('برای استفاده از خدمات، ابتدا اطلاعات خود را کامل کنید.', 'info', 4200);
            window.location.replace(withPort('/app/profile/edit?new=1'));
            return false;
        }
        return true;
    }

    /* ---------- لایه API (vanilla XHR — جایگزین $.ajax) ---------- */
    function extractMessage(xhr) {
        var data = null;
        try {
            data = typeof xhr.responseText === 'string' ? JSON.parse(xhr.responseText) : xhr.responseText;
        } catch (e) { data = null; }

        if (data && data.message) {
            if (data.errors) {
                for (var key in data.errors) {
                    if (Object.prototype.hasOwnProperty.call(data.errors, key)) {
                        var list = data.errors[key];
                        if (list && list.length) { return list[0]; }
                    }
                }
            }
            return data.message;
        }
        return 'خطای غیرمنتظره؛ دوباره تلاش کنید.';
    }

    /**
     * فراخوانی API.
     * opts: { method, data(json), formData, success(resp), error(xhr, message), timeout, retries, complete(xhr) }
     *
     * v40 — سخت‌سازی مرکزی (حفظ عین رفتار نسخهٔ jQuery):
     *  • timeout پیش‌فرض ۲۰ ثانیه — درخواست دیگر تا ابد معلق نمی‌ماند.
     *  • تلاش مجدد خودکار روی خطاهای گذرا (شبکه/تایم‌اوت/۵xx/۴۲۹/پاسخ غیر-JSON):
     *    GET به‌طور پیش‌فرض ۱ تلاش مجدد دارد؛ متدهای دیگر (POST/…) هرگز
     *    خودکار تکرار نمی‌شوند (خطر ثبت تکراری سفارش/پرداخت).
     *  • صفحات می‌توانند با retries: N بیشتر یا با retries: 0 کمتر بخواهند.
     *  • ۴۰۱ هرگز تکرار نمی‌شود (نشست منقضی = رفتن به ورود).
     */
    function api(path, opts) {
        opts = opts || {};

        var method = (opts.method || 'GET').toUpperCase();
        var retriesLeft = (typeof opts.__retriesLeft === 'number')
            ? opts.__retriesLeft
            : Math.max(0, (opts.retries !== undefined) ? Number(opts.retries) : (method === 'GET' ? 1 : 0));

        netBusyBegin(); /* معادل ajaxStart — نگه‌داشتن state دکمهٔ busy */

        var hadToken = !!token(); /* v41 — آیا این درخواست اصلاً توکن داشت؟ */

        var xhr = new XMLHttpRequest();
        xhr.open(method, apiUrl(path), true);
        xhr.timeout = opts.timeout || 20000; /* v40 — هیچ درخواستی بی‌مهلت نیست */
        xhr.responseType = 'text';
        xhr.setRequestHeader('Accept', 'application/json');
        if (token()) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + token());
        }
        if (opts.formData) {
            /* FormData: مرورگر خودش Content-Type چند-بخشی با boundary می‌سازد */
        } else if (typeof opts.data !== 'undefined') {
            xhr.setRequestHeader('Content-Type', 'application/json');
        }

        var timedOut = false;
        xhr.ontimeout = function () { timedOut = true; };

        xhr.onload = function () {
            var status = xhr.status;
            var ok = status >= 200 && status < 300;

            if (ok) {
                var resp = null;
                try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = null; }
                netBusyEnd();
                if (opts.success) { opts.success(resp); }
                if (opts.complete) { opts.complete(xhr); }
                return;
            }

            var message = extractMessage(xhr);

            /* v41 — ضد «حلقهٔ نشست منقضی»:
               علت باگ: اسکریپت‌های مشترک لایه (مثل initRealtime اعلان‌ها) روی
               صفحهٔ ورود هم بدون توکن /realtime/config را می‌زدند → ۴۰۱ →
               پیام «منقضی شد» + ریدایرکت به /app/auth → همان صفحه دوباره ۴۰۱…
               حلقهٔ بی‌نهایت رفرش تا سقف throttle سرور (≈۱ دقیقه).
               حالا: ۴۰۱ِ بی‌توکن یا ۴۰۱ روی خود صفحهٔ ورود بی‌صدا است و از
               بین چند ۴۰۱ همزمان فقط اولی پیام/ریدایرکت می‌سازد. */
            if (status === 401) {
                netBusyEnd();
                clearSession();
                var onAuthPage = /^\/app\/auth\b/.test(window.location.pathname);
                if (!authRedirectPending && hadToken && !onAuthPage) {
                    authRedirectPending = true;
                    toast('نشست شما منقضی شده؛ دوباره وارد شوید.', 'error');
                    window.setTimeout(function () {
                        window.location.replace(withPort('/app/auth'));
                    }, 1100);
                }
                if (opts.complete) { opts.complete(xhr); }
                return;
            }

            /* v40 — خطای گذرا و تلاش مجدد باقی است؟ پس از مکث کوتاه دوباره می‌زنیم */
            if (retriesLeft > 0 && isTransientFailure(xhr, timedOut ? 'timeout' : '')) {
                netBusyEnd();
                var retryOpts = Object.assign({}, opts, { __retriesLeft: retriesLeft - 1 });
                window.setTimeout(function () { api(path, retryOpts); }, 900);
                return;
            }

            netBusyEnd();
            if (opts.error) {
                opts.error(xhr, message);
            } else if (message) {
                toast(message, 'error');
            }
            if (opts.complete) {
                if (status === 401 && opts.error === undefined) { return; }
                opts.complete(xhr);
            }
        };

        xhr.onerror = function () {
            var message = extractMessage(xhr);
            netBusyEnd();
            if (opts.error) {
                opts.error(xhr, message);
            } else if (message) {
                toast(message, 'error');
            }
            if (opts.complete) { opts.complete(xhr); }
        };

        if (opts.formData) {
            xhr.send(opts.formData);
        } else if (typeof opts.data !== 'undefined') {
            xhr.send(JSON.stringify(opts.data));
        } else {
            xhr.send();
        }
    }

    /** آیا خطای فعلی گذرا است و ارزش تلاش مجدد دارد؟ (v40) */
    function isTransientFailure(xhr, textStatus) {
        if (!xhr || !xhr.status || xhr.status === 0) { return true; } // شبکه/قطع/آبورت
        if (xhr.status >= 500) { return true; }
        if (xhr.status === 429) { return true; }
        if (textStatus === 'timeout' || textStatus === 'parsererror') { return true; }
        return false;
    }

    /* ---------- قالب‌بندی فارسی ---------- */
    var FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    function toFaDigits(value) {
        return String(value === null || value === undefined ? '' : value).replace(/[0-9]/g, function (d) {
            return FA_DIGITS[+d];
        });
    }

    function toEnDigits(value) {
        var fa = '۰۱۲۳۴۵۶۷۸۹';
        var ar = '٠١٢٣٤٥٦٧٨٩';
        return String(value || '').replace(/[۰-۹٠-٩]/g, function (d) {
            var i = fa.indexOf(d);
            if (i === -1) { i = ar.indexOf(d); }
            return String(i);
        });
    }

    /** مبلغ با جداکننده هزارگان فارسی (بدون واحد) */
    function faMoney(amount) {
        var n = Math.round(Number(amount || 0));
        var sign = n < 0 ? '−' : '';
        n = Math.abs(n);
        var withSep = String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return sign + toFaDigits(withSep);
    }

    /** مبلغ کامل با واحد تومان */
    function faMoneyUnit(amount) {
        return faMoney(amount) + ' تومان';
    }

    /** escape HTML برای ایمنی رندر */
    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /** نرمال‌سازی موبایل (ارقام فارسی/عربی، +98) */
    function normalizeMobile(mobile) {
        var m = toEnDigits(String(mobile || '')).trim();
        m = m.replace(/^\+/, '');
        if (m.indexOf('0098') === 0) { m = '0' + m.slice(4); }
        else if (m.indexOf('98') === 0 && m.length === 12) { m = '0' + m.slice(2); }
        return m;
    }

    /* ---------- توست ---------- */
    var ICONS = { success: '✓', error: '✕', info: 'ℹ' };

    function toast(message, type, duration) {
        type = type || 'info';
        duration = duration || 3600;

        var wrap = document.getElementById('toastWrap');
        if (!wrap) { return; }

        var t = document.createElement('div');
        t.className = 'toast toast-' + type;
        t.setAttribute('role', 'status');
        t.innerHTML = '<span class="t-icon">' + (ICONS[type] || 'ℹ') + '</span><span></span>';
        t.lastElementChild.textContent = message;
        wrap.appendChild(t);

        window.setTimeout(function () {
            t.classList.add('out');
            window.setTimeout(function () { t.remove(); }, 350);
        }, duration);
    }

    /* ---------- دکمه در حال بارگذاری (عنصر خام) ---------- */
    function btnLoading(btn, loading, loadingText) {
        if (!btn) { return; }
        if (loading) {
            if (!btn.__cnHtml) { btn.__cnHtml = btn.innerHTML; }
            btn.disabled = true;
            btn.style.opacity = '0.65';
            btn.innerHTML = '<span class="spinner"></span>' + (loadingText ? ' ' + esc(loadingText) : '');
        } else {
            btn.disabled = false;
            btn.style.opacity = '';
            if (btn.__cnHtml) { btn.innerHTML = btn.__cnHtml; }
        }
    }

    /* ---------- خطای فیلد ---------- */
    /** یافتن عنصر خطای متناظر: #err_name / #ferr_name / #nameError / #name */
    function errorEl(name) {
        var selectors = ['err_' + name, 'ferr_' + name, name + 'Error', name];
        for (var i = 0; i < selectors.length; i++) {
            var el = document.getElementById(selectors[i]);
            if (el && el.classList.contains('field-error')) { return el; }
        }
        return null;
    }

    function fieldError(name, message) {
        var err = errorEl(name);
        if (err) {
            err.textContent = message || '';
            err.classList.add('show');
            var group = err.closest('.form-group');
            if (group) {
                var input = group.querySelector('.field, .check-row, .check-grid');
                if (input) { input.classList.add('invalid'); }
            }
        }
    }

    function clearFieldErrors(scope) {
        var root = (scope instanceof Element) ? scope : document;
        Array.prototype.forEach.call(root.querySelectorAll('.field-error'), function (el) {
            el.classList.remove('show');
            el.textContent = '';
        });
        Array.prototype.forEach.call(root.querySelectorAll('.field'), function (el) {
            el.classList.remove('invalid');
        });
    }

    /** نگاشت خطاهای سرور (کلید = نام فنی فیلد) روی فرم */
    function applyErrors(errors, scope) {
        errors = errors || {};
        Object.keys(errors).forEach(function (key) {
            var list = errors[key];
            var message = list && list.length ? list[0] : String(list);
            if (key === 'form_data' || key === 'files' || key === 'documents' || key === 'service') {
                fieldError('files', message);
                toast(message, 'error');
            } else {
                fieldError(key, message);
            }
        });
    }

    /* ---------- مودال تأیید ---------- */
    var modalHtml =
        '<div class="modal-backdrop" role="dialog" aria-modal="true">' +
        '  <div class="modal">' +
        '    <div class="m-icon">⚠</div>' +
        '    <h3 class="m-title"></h3>' +
        '    <p class="m-desc"></p>' +
        '    <div class="m-actions">' +
        '      <button type="button" class="btn btn-ghost m-cancel">انصراف</button>' +
        '      <button type="button" class="btn btn-primary m-ok">تأیید</button>' +
        '    </div>' +
        '  </div>' +
        '</div>';

    /**
     * CN.confirm({title, desc, okText, danger}, onOk)
     */
    function confirm(opts, onOk) {
        opts = opts || {};
        var tmp = document.createElement('div');
        tmp.innerHTML = modalHtml;
        var modal = tmp.firstElementChild;

        modal.querySelector('.m-icon').textContent = opts.icon || '⚠';
        modal.querySelector('.m-title').textContent = opts.title || 'تأیید عملیات';
        modal.querySelector('.m-desc').textContent = opts.desc || '';
        var okBtn = modal.querySelector('.m-ok');
        okBtn.textContent = opts.okText || 'تأیید';
        if (opts.danger) {
            okBtn.classList.remove('btn-primary');
            okBtn.classList.add('btn-danger');
        }
        document.body.appendChild(modal);

        modal.querySelector('.m-cancel').addEventListener('click', function () { modal.remove(); });
        okBtn.addEventListener('click', function () {
            modal.remove();
            if (onOk) { onOk(); }
        });
        modal.addEventListener('click', function (e) {
            if (e.target === modal) { modal.remove(); }
        });
    }

    /* ---------- هدر مشترک ---------- */
    /* موجودی کیف پول از هدر حذف شد (v24) — دیگه هر صفحه /wallet صدا زده نمی‌شود؛
       کیف پول از ناوبری پایین و صفحهٔ پروفایل در دسترس است. */

    function updateAvatar(u) {
        var targets = document.querySelectorAll('#headerAvatar, #profileAvatar');
        if (!targets.length || !u) { return; }

        /* v42 — آواتار تصویری (کراپ ۷۵×۷۵ WebP) مقدار دارد → نمایش تصویر */
        if (u.avatar_url) {
            Array.prototype.forEach.call(targets, function (a) {
                a.textContent = '';
                a.classList.add('is-avatar');
                a.style.backgroundImage = 'url("' + u.avatar_url + '")';
                a.style.backgroundSize = 'cover';
                a.style.backgroundPosition = 'center';
                if (a.getAttribute('title') !== null) { a.setAttribute('title', u.full_name || ''); }
            });
            return;
        }

        var initials;
        if (u.name && u.family) {
            initials = (u.name.trim().charAt(0) || '؟') + (u.family.trim().charAt(0) || '');
        } else {
            initials = '؟';
        }
        Array.prototype.forEach.call(targets, function (a) {
            a.classList.remove('is-avatar');
            a.style.backgroundImage = '';
            a.textContent = initials;
            if (a.getAttribute('title') !== null) { a.setAttribute('title', u.full_name || ''); }
        });
    }

    /** بروزرسانی هدر: آواتار */
    function refreshChrome() {
        if (!token()) { return; }
        var u = user();
        if (u) { updateAvatar(u); }
    }

    /* ---------- شمارش معکوس (عناصر خام) ---------- */
    function countdown(target, btn, seconds, onEnd) {
        var remain = seconds;
        if (btn) { btn.disabled = true; }

        function tick() {
            if (remain <= 0) {
                if (target) { target.textContent = ''; }
                if (btn) { btn.disabled = false; }
                if (onEnd) { onEnd(); }
                return;
            }
            if (target) { target.textContent = 'ارسال مجدد تا ' + toFaDigits(remain) + ' ثانیه…'; }
            remain--;
            window.setTimeout(tick, 1000);
        }
        tick();
    }

    /* ---------- debounce ---------- */
    function debounce(fn, wait) {
        var t = null;
        return function () {
            var args = arguments;
            var self = this;
            window.clearTimeout(t);
            t = window.setTimeout(function () { fn.apply(self, args); }, wait);
        };
    }

    /* ---------- نقشه وضعیت سفارش → کلاس بج ---------- */
    var STATUS_BADGE = {
        pending_payment: 'badge-amber',
        paid: 'badge-green',
        broadcasting: 'badge-teal',
        accepted: 'badge-green',
        in_progress: 'badge-teal',
        needs_info: 'badge-orange',
        delivered: 'badge-teal',
        completed: 'badge-green',
        queued: 'badge-stone',
        cancelled: 'badge-rose',
        refunded: 'badge-rose'
    };

    function statusBadge(status, label) {
        var cls = STATUS_BADGE[status] || 'badge-stone';
        return '<span class="badge ' + cls + '">' + esc(label || status) + '</span>';
    }

    /* ---------- تم شب/روز (حالت تاریک) ---------- */
    /* کلاس dark روی <html> توسط theme-boot.js (head) ست می‌شود (ضد-فلش).
       کلید localStorage «caffeinet-theme» مشترک با پنل‌های مدیریتی است. */
    var THEME_KEY = 'caffeinet-theme';
    var themeAnimTimer = null;

    function currentTheme() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    }

    function syncThemeButtons() {
        var mode = currentTheme();
        Array.prototype.forEach.call(document.querySelectorAll('[data-theme-toggle]'), function (el) {
            el.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
            el.setAttribute('title', mode === 'dark' ? 'حالت روز' : 'حالت شب');
        });
        /* نمودارها/کامپوننت‌های سراسری از این رویداد باخبر شوند
           [Task 9] vanilla: شنونده با e.detail به حالت دسترسی دارد */
        document.dispatchEvent(new CustomEvent('ui:theme', { detail: mode }));
    }

    function setTheme(mode, options) {
        options = options || {};
        var html = document.documentElement;
        var isDark = mode === 'dark';
        if (isDark === html.classList.contains('dark')) { syncThemeButtons(); return; }

        /* انیمیشن نرم فقط هنگام تعویض (نه لود اولیه) */
        if (options.animate !== false) {
            html.classList.add('theme-anim');
            window.clearTimeout(themeAnimTimer);
            themeAnimTimer = window.setTimeout(function () { html.classList.remove('theme-anim'); }, 480);
        }

        html.classList.toggle('dark', isDark);
        if (options.persist !== false) {
            try { window.localStorage.setItem(THEME_KEY, mode); } catch (e) { /* noop */ }
        }
        /* v42 — کوکی تم برای مانیفست PWA (رنگ اسپلش روشن/تاریک) */
        if (window.CNThemeCookie) { window.CNThemeCookie(isDark ? 'dark' : 'light'); }
        syncThemeButtons();
    }

    /* اتصال دکمه‌های سوییچ (delegate — برای محتوای داینامیک هم کار می‌کند) */
    document.addEventListener('click', function (e) {
        var el = e.target.closest ? e.target.closest('[data-theme-toggle]') : null;
        if (el) {
            setTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        }
    });

    /* ---------- خروج ---------- */
    function logout() {
        api('/logout', {
            method: 'POST',
            complete: function () {
                clearSession();
                window.location.replace(withPort('/app/auth'));
            }
        });
    }

    /* ---------- [Task 2-a] پل init برای ناوبری SPA (wire:navigate) ----------
       مثل App.onNavigate در پنل‌ها: هم بار اول، هم بعد از هر ناوبری؛
       با گارد «حداکثر یک اجرا در هر نسل صفحه». */
    var navQueue = [];
    var navEpoch = 0;
    var firstNavigatedSeen = false;
    var fnEpoch = [];

    function runOnNavigate(fn) {
        var idx = navQueue.indexOf(fn);
        if (idx !== -1 && fnEpoch[idx] === navEpoch) { return; }
        if (idx === -1) { idx = navQueue.push(fn) - 1; }
        fnEpoch[idx] = navEpoch;
        try { fn(); } catch (e) {
            if (window.console && console.error) { console.error('[CN.onNavigate]', e); }
        }
    }

    function onNavigate(fn) {
        if (typeof fn !== 'function') { return; }
        navQueue.push(fn);
        runOnNavigate(fn);
    }

    window.addEventListener('livewire:navigated', function () {
        if (firstNavigatedSeen) { navEpoch++; }
        firstNavigatedSeen = true;
        navQueue.forEach(function (fn) { runOnNavigate(fn); });
    });

    /* ---------- [Task 8] بازخورد ناوبری SPA ----------
       ۱) نوار پیشرفت بالای صفحه از شروع واکشی (livewire:navigate) تا جایگذینی
       ۲) اعمال دوبارهٔ تم — Livewire در هر ناوبری attributeهای <html> را با
          نسخهٔ سرور-رندر (بدون کلاس dark) جایگزین می‌کند و تم می‌پرد؛
          بعد از هر ناوبری تم از localStorage/سیستم دوباره اعمال می‌شود. */
    var spaBar = null, spaHideTimer = null;

    function spaProgressStart() {
        if (!spaBar) {
            spaBar = document.createElement('div');
            spaBar.className = 'spa-progress';
            spaBar.setAttribute('aria-hidden', 'true');
            (document.body || document.documentElement).appendChild(spaBar);
        }
        window.clearTimeout(spaHideTimer);
        spaBar.classList.remove('spa-progress--done');
        spaBar.classList.remove('spa-progress--active');
        void spaBar.offsetWidth; /* ری‌استارت انیمیشن */
        spaBar.classList.add('spa-progress--active');
    }

    function spaProgressDone() {
        if (!spaBar || !spaBar.classList.contains('spa-progress--active')) { return; }
        spaBar.classList.add('spa-progress--done');
        window.clearTimeout(spaHideTimer);
        spaHideTimer = window.setTimeout(function () {
            if (spaBar) { spaBar.classList.remove('spa-progress--active', 'spa-progress--done'); }
        }, 420);
    }

    window.addEventListener('livewire:navigate', spaProgressStart);
    window.addEventListener('alpine:navigate', spaProgressStart);
    window.addEventListener('livewire:navigated', spaProgressDone);

    /* ---------- فاز ۱۴ — پیش‌بارگذاری منوهای پایین (حس اپ بومی) ----------
       لینک‌های ناوبری پایین با «wire:navigate.hover» شنوندهٔ hover Livewire را
       دارند؛ اینجا بعد از هر ناوبری (و هر ۲۵ ثانیه در تبِ باز) به‌صورت مصنوعی
       mouseenter/mouseleave می‌فرستیم تا Livewire HTML صفحه‌های منو را در کشِ
       ۳۰ ثانیه‌ای خودش پیش‌بارگذاری کند → کلیک روی منو همان لحظه از کش سواپ
       می‌شود (بدون انتظار برای پاسخ سرور) — «فقط محتوای لازم از دیتابیس». */
    var navWarmTimer = null;
    var navWarmInterval = null;
    var NAV_WARM_IDLE_MS = 900;
    var NAV_WARM_EVERY_MS = 25000;

    function warmBottomNav() {
        try {
            if (document.hidden || (navigator.onLine === false)) { return; }
            var links = document.querySelectorAll('.bottom-nav a[href]');
            if (!links.length) { return; }
            Array.prototype.forEach.call(links, function (el) {
                if (el.getAttribute('aria-current') === 'page') { return; }
                el.dispatchEvent(new MouseEvent('mouseenter'));
                window.setTimeout(function () {
                    el.dispatchEvent(new MouseEvent('mouseleave'));
                }, 220);
            });
        } catch (e) { /* هرگز گرم‌کردن، صفحه را نشکند */ }
    }

    function scheduleNavWarmup() {
        window.clearTimeout(navWarmTimer);
        navWarmTimer = window.setTimeout(warmBottomNav, NAV_WARM_IDLE_MS);
        if (!navWarmInterval) {
            navWarmInterval = window.setInterval(warmBottomNav, NAV_WARM_EVERY_MS);
        }
    }
    window.addEventListener('livewire:navigated', scheduleNavWarmup);

    function reapplyThemeAfterNavigation() {
        var stored = null;
        try { stored = window.localStorage.getItem(THEME_KEY); } catch (e) { /* noop */ }
        var dark = stored === 'dark' || (!stored && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    }

    window.addEventListener('livewire:navigated', function () {
        reapplyThemeAfterNavigation();
        syncThemeButtons();
    });

    /* ---------- [Task 9] شمارندهٔ درخواست فعال (جایگزین ajaxStart/ajaxStop) ---------- */
    var netBusyCount = 0;

    function netBusyBegin() {
        netBusyCount++;
        window.clearTimeout(busyTimer);
    }

    function netBusyEnd() {
        netBusyCount = Math.max(0, netBusyCount - 1);
        if (netBusyCount === 0 && busyBtn) { busyRelease(); }
    }

    /* ---------- [Task 8] لودینگ روی دکمه‌ها تا پایان پردازش ----------
       هر دکمهٔ کلیک‌شده کلاس is-busy می‌گیرد:
       • اگر درخواست شبکه واقعی (CN.api) شروع شود تا پایان آن می‌ماند
       • اگر اکشن فوری باشد (بازکردن مودال/تب) بعد از ۳۵۰ms آزاد می‌شود تا چشمک زنده نگیرد
       • لینک‌های wire:navigate و سوییچ‌های فوری (تم/زنگ/چیپ) مستثنا هستند */
    var busyBtn = null, busyTimer = null;

    function busyRelease() {
        window.clearTimeout(busyTimer);
        if (busyBtn) {
            busyBtn.classList.remove('is-busy');
            busyBtn.removeAttribute('aria-busy');
            busyBtn = null;
        }
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest ? e.target.closest('button, [role="button"], .btn, a[class*="btn-"]') : null;
        if (!el || el.disabled) { return; }
        if (el.hasAttribute('data-no-loading') || el.hasAttribute('data-theme-toggle') || el.id === 'appBell') { return; }
        if (el.classList.contains('chip') || el.classList.contains('ns-item') || el.classList.contains('ns-markall')) { return; }
        if (el.tagName === 'A' && el.getAttribute('wire:navigate') !== null) { return; }
        if (el.classList.contains('is-busy')) { return; }
        busyRelease();
        busyBtn = el;
        el.classList.add('is-busy');
        el.setAttribute('aria-busy', 'true');
        window.clearTimeout(busyTimer);
        busyTimer = window.setTimeout(function () {
            if (netBusyCount === 0) { busyRelease(); } /* اکشن فوری — بدون درخواست شبکه */
        }, 350);
    });

    /* [Task 2-a] آیتم فعال ناوبری پایین بعد از هر ناوبری تازه شود
       (bottom-nav با @persist حفظ می‌شود و کلاس active سروری کهنه می‌ماند) */
    function refreshBottomNavActive() {
        var nav = document.querySelector('.bottom-nav');
        if (!nav) { return; }
        var path = window.location.pathname.replace(/\/+$/, '') || '/';
        var best = null, bestLen = -1;
        var links = nav.querySelectorAll('a[href]');
        Array.prototype.forEach.call(links, function (a) {
            a.classList.remove('active');
            a.removeAttribute('aria-current');
        });
        Array.prototype.forEach.call(links, function (a) {
            var href;
            try { href = new URL(a.href, window.location.origin).pathname.replace(/\/+$/, '') || '/'; } catch (e) { return; }
            if (href !== path && path.indexOf(href + '/') !== 0) { return; }
            if (href.length > bestLen) { best = a; bestLen = href.length; }
        });
        if (best) {
            best.classList.add('active');
            best.setAttribute('aria-current', 'page');
        }
    }

    /* ---------- عمومی ----------
       [Task 2-a] با wire:navigate هدر اپ در هر ناوبری DOM تازه دارد؛
       refreshChrome/syncThemeButtons باید بعد از هر ناوبری هم اجرا شوند. */
    onNavigate(function () {
        refreshChrome();
        syncThemeButtons();
        refreshBottomNavActive();
    });

    /* v42 — ذخیرهٔ پوستهٔ پالت (ظاهر و رنگ‌بندی) برای صفحهٔ آفلاین:
       offline.js همین CSS را از localStorage تزریق می‌کند تا صفحهٔ
       «اتصال قطع» هم دقیقاً هم‌رنگ پالت انتخابی باشد. */
    try {
        var apEl = document.getElementById('appearance-css');
        if (apEl) { window.localStorage.setItem('cn-appearance-css:app', apEl.textContent); }
    } catch (e) { /* حافظه در دسترس نیست */ }

    /* ---------- [F-4] فشرده‌سازی تصویر در سمت کاربر ----------
       ورودی: File تصویر (jpeg/png/webp) — خروجی: Promise که «همیشه» resolve می‌شود
       (هیچ‌وقت reject نمی‌شود تا آپلود هرگز مسدود نشود؛ در هر خطا فایل اصلی برمی‌گردد).
       opts: { maxSide: 2048, quality: 0.85 }
       قواعد: زیر ۳۵۰KB دست‌نخورده؛ هرگز بزرگ‌نمایی نمی‌کند؛ PNG کوچک/شفاف PNG می‌ماند؛
       PNG بزرگ بدون شفافیت → JPEG (پس‌زمینه سفید + پسوند .jpg)؛ WebP → WebP؛
       اگر خروجی کوچک‌تر نشد فایل اصلی برگردانده می‌شود؛ ObjectURL همیشه آزاد می‌شود. */
    var COMPRESS_MIN_BYTES = 350 * 1024;        /* زیر این حجم دست نمی‌زنیم */
    var COMPRESS_PNG_KEEP_BYTES = 1536 * 1024;  /* PNG زیر ۱.۵MB همان PNG می‌ماند */
    var COMPRESS_DEFAULT_SIDE = 2048;           /* بزرگ‌ترین ضلع مجاز (پیش‌فرض) */
    var COMPRESS_DEFAULT_QUALITY = 0.85;

    /* ---------- فاز ۱۴ — شناسایی نوع فایل (فیکس «پیش‌نمایش تصویر در گوشی») ----------
       خیلی از مرورگرهای موبایل (گالری اندروید/برخی iOS) برای فایل انتخابی
       file.type خالی می‌دهند → کد قبلی فایل تصویری را «file» می‌دانست؛ نه
       پیش‌نمایش تصویر می‌آمد و نه فشرده‌سازی انجام می‌شد. اینجا ابتدا MIME و
       اگر خالی بود پسوند نام فایل ملاک است. خروجی: image|video|audio|file */
    var FTYPE_EXT_MAP = [
        ['image', /\.(jpe?g|png|webp|gif|bmp|heic|heif|avif|tiff?|svg)$/i],
        ['video', /\.(mp4|m4v|mov|webm|avi|mkv|3gp|wmv|flv)$/i],
        ['audio', /\.(mp3|wav|ogg|oga|m4a|aac|opus|flac)$/i]
    ];

    function detectFileType(file) {
        var type = String((file && file.type) || '').toLowerCase();
        if (type.indexOf('image/') === 0) { return 'image'; }
        if (type.indexOf('video/') === 0) { return 'video'; }
        if (type.indexOf('audio/') === 0) { return 'audio'; }
        /* نوع خالی/ناشناخته → قضاوت از پسوند نام فایل (رایج در گالری موبایل) */
        if (!type || type === 'application/octet-stream') {
            var name = String((file && file.name) || '');
            for (var i = 0; i < FTYPE_EXT_MAP.length; i++) {
                if (FTYPE_EXT_MAP[i][1].test(name)) { return FTYPE_EXT_MAP[i][0]; }
            }
            /* iOS: نام فایل گاهی «image.jpg» یا «IMG_0001.HEIC» است — پوشش شد؛
               عدم تطابق → file (کاشی عمومی با بج پسوند) */
            return 'file';
        }
        return 'file';
    }

    function compressImage(file, opts) {
        return new Promise(function (resolve) {
            function keep() { resolve(file); }

            var url = null;
            function safeRevoke() {
                if (url) {
                    try { window.URL.revokeObjectURL(url); } catch (e) { /* noop */ }
                    url = null;
                }
            }

            function finish(blob, outType, outName) {
                safeRevoke();
                try {
                    if (!blob || blob.size >= file.size) { return keep(); }        /* کوچک‌تر نشد */
                    if (blob.type && String(blob.type).toLowerCase() !== outType) { return keep(); } /* انکودر نوع خواسته‌شده را نداشت */
                    resolve(new window.File([blob], outName, { type: outType, lastModified: Date.now() }));
                } catch (err) { keep(); }
            }

            /* نمونه‌گیری ارزان شفافیت: ۴ گوشه + مرکز */
            function alphaSeen(ctx, w, h) {
                var pts = [[0, 0], [w - 1, 0], [0, h - 1], [w - 1, h - 1], [w >> 1, h >> 1]];
                try {
                    for (var i = 0; i < pts.length; i++) {
                        if (ctx.getImageData(pts[i][0], pts[i][1], 1, 1).data[3] < 255) { return true; }
                    }
                } catch (e) { return true; } /* خواندن پیکسل ممکن نشد → محافظه‌کار: PNG بماند */
                return false;
            }

            function swapExtName(name, ext) {
                name = String(name || 'image');
                if (/\.(png|jpe?g|webp)$/i.test(name)) { return name.replace(/\.(png|jpe?g|webp)$/i, '.' + ext); }
                return name + '.' + ext;
            }

            function swapJpgName(name) {
                return swapExtName(name, 'jpg');
            }

            try {
                opts = opts || {};
                if (!file || typeof file !== 'object' || typeof file.size !== 'number' ||
                    !window.URL || typeof window.URL.createObjectURL !== 'function' ||
                    typeof window.File !== 'function') { return keep(); }

                var type = String(file.type || '').toLowerCase();
                if (type !== 'image/jpeg' && type !== 'image/png' && type !== 'image/webp') { return keep(); }
                if (file.size <= COMPRESS_MIN_BYTES) { return keep(); }

                /* v42 — پیکربندی مدیر: خاموش = اصل فایل؛ وگرنه حداکثر ضلع/کیفیت/فرمت */
                var uc = uploadCfg();
                if (uc.enabled === false) { return keep(); }

                var maxSide = Number(opts.maxSide) > 0 ? Number(opts.maxSide)
                    : (Number(uc.max_side) > 0 ? Number(uc.max_side) : COMPRESS_DEFAULT_SIDE);
                var quality = (typeof opts.quality === 'number' && opts.quality > 0 && opts.quality <= 1) ? opts.quality
                    : (Number(uc.quality) > 0 ? Math.min(1, Number(uc.quality) / 100) : COMPRESS_DEFAULT_QUALITY);
                var fmt = String(uc.format || 'auto');

                url = window.URL.createObjectURL(file);
                var img = new Image();

                img.onload = function () {
                    var w = img.naturalWidth || 0;
                    var h = img.naturalHeight || 0;
                    if (!w || !h) { safeRevoke(); return keep(); }
                    try {
                        var scale = Math.min(1, maxSide / Math.max(w, h)); /* هرگز بزرگ‌نمایی نمی‌کنیم */
                        var cw = Math.max(1, Math.round(w * scale));
                        var ch = Math.max(1, Math.round(h * scale));

                        var canvas = document.createElement('canvas');
                        canvas.width = cw;
                        canvas.height = ch;
                        var ctx = canvas.getContext('2d');
                        if (!ctx || typeof canvas.toBlob !== 'function') { safeRevoke(); return keep(); }

                        if (type === 'image/png') {
                            ctx.drawImage(img, 0, 0, cw, ch);
                            var alpha = alphaSeen(ctx, cw, ch);

                            /* v42 — فرمت WebP خواسته شده (حتی با شفافیت) */
                            if (fmt === 'webp' && typeof canvas.toBlob === 'function') {
                                canvas.toBlob(function (blob) {
                                    if (blob && blob.type === 'image/webp') {
                                        finish(blob, 'image/webp', swapExtName(file.name, 'webp'));
                                    } else {
                                        /* انکودر WebP نبود — مثل قبل */
                                        finishPngOrJpeg();
                                    }
                                }, 'image/webp', quality);
                                return;
                            }

                            function finishPngOrJpeg() {
                                ctx.drawImage(img, 0, 0, cw, ch);
                                if (file.size < COMPRESS_PNG_KEEP_BYTES || alpha || fmt === 'keep') {
                                    /* PNG می‌ماند — شفافیت/فرمت حفظ می‌شود */
                                    canvas.toBlob(function (blob) { finish(blob, 'image/png', file.name); }, 'image/png');
                                } else {
                                    /* PNG بزرگ بدون شفافیت → JPEG با پس‌زمینهٔ سفید */
                                    ctx.clearRect(0, 0, cw, ch);
                                    ctx.fillStyle = '#ffffff';
                                    ctx.fillRect(0, 0, cw, ch);
                                    ctx.drawImage(img, 0, 0, cw, ch);
                                    canvas.toBlob(function (blob) { finish(blob, 'image/jpeg', swapExtName(file.name, 'jpg')); }, 'image/jpeg', quality);
                                }
                            }

                            finishPngOrJpeg();
                        } else {
                            /* JPEG → JPEG و WebP → WebP (نوع حفظ می‌شود، شفافیت WebP پاک نمی‌شود) */
                            ctx.drawImage(img, 0, 0, cw, ch);
                            canvas.toBlob(function (blob) { finish(blob, type, file.name); }, type, quality);
                        }
                    } catch (err) {
                        safeRevoke();
                        keep();
                    }
                };
                img.onerror = function () {
                    safeRevoke();
                    keep();
                };
                img.src = url;
            } catch (err) {
                safeRevoke();
                keep();
            }
        });
    }

    /* ---------- v41 — ارتفاع واقعی پوسته (--app-vh) ----------
       در برخی گوشی‌ها در «حالت نصب‌شده» (PWA standalone) مقدار 100dvh
       بزرگ‌تر از پنجرهٔ واقعی گزارش می‌شود → پوسته بلندتر از صفحه می‌شود و
       محتوای پایین (ناوبری/دکمه‌ها) زیر لبهٔ صفحه می‌رود.
       متغیر --app-vh با innerHeight/visualViewport به‌روز می‌شود تا همهٔ
       صفحات (نه فقط چت) ارتفاع دقیق داشته باشند؛ CSS با fallback
       var(--app-vh, 100dvh) از آن استفاده می‌کند. */
    (function fitShellHeight() {
        function apply() {
            var h = window.innerHeight;
            var vv = window.visualViewport;
            if (vv && Math.abs(vv.scale - 1) < 0.02) {
                h = Math.min(h, Math.round(vv.height));
            }
            if (h > 0) {
                document.documentElement.style.setProperty('--app-vh', Math.round(h) + 'px');
            }
        }
        apply();
        window.addEventListener('resize', apply);
        window.addEventListener('orientationchange', function () { setTimeout(apply, 250); });
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', apply);
        }
    })();

    /* ---------- v42 — اسکلتون فوری ناوبری (لودر مدرن) ----------
       با کلیک روی منوی پایین/هدر، پوستهٔ صفحهٔ مقصد (ساختار ثابت) همان لحظه
       با شیمر موجی نمایش داده می‌شود؛Livewire صفحهٔ تازه را که سواپ کرد،
       پوسته پنهان و محتوا با انیمیشن ملایم وارد می‌شود. */
    (function initNavSkeleton() {
        var overlay = null;
        var hideTimer = null;

        function pageKey(href) {
            try {
                var p = new URL(href, window.location.origin).pathname.replace(/\/+$/, '');
                if (p === '/app' || p === '') { return 'home'; }
                var m = p.match(/^\/app\/([^/]+)/);
                return m ? m[1] : null;
            } catch (e) { return null; }
        }

        function block(h, w, extra) {
            return '<div class="skb' + (extra ? ' ' + extra : '') + '" style="height:' + h + 'px;' +
                (w ? 'width:' + w + ';' : '') + '"></div>';
        }

        function row(h, wIcon) {
            return '<div class="sk-row"><div class="skb circle" style="width:' + wIcon + 'px;height:' + wIcon + 'px;flex:none"></div>' +
                '<div class="grow">' + block(h, '100%') + block(h - 6, '62%') + '</div></div>';
        }

        /* پوستهٔ ثابت هر صفحه — فقط ساختار؛ دیتا بعد از باز شدن لود می‌شود */
        var SHAPES = {
            home: function () {
                return block(22, '55%') + block(96) + '<div class="sk-row">' +
                    block(64, 'calc(33% - 7px)') + block(64, 'calc(33% - 7px)') + block(64, 'calc(33% - 7px)') +
                    '</div>' + block(16, '40%', 'sk-gap') + row(72, 44) + row(72, 44);
            },
            services: function () {
                return block(44) + '<div class="sk-row sk-gap">' +
                    block(30, '64px', 'circle') + block(30, '84px', 'circle') + block(30, '76px', 'circle') + block(30, '70px', 'circle') +
                    '</div>' + block(16, '38%', 'sk-gap') + row(82, 48) + row(82, 48) + row(82, 48);
            },
            orders: function () {
                return block(22, '42%') + block(120) + block(120) + block(120);
            },
            support: function () {
                return block(84) + '<div class="sk-row sk-gap">' +
                    block(30, '58px', 'circle') + block(30, '84px', 'circle') + block(30, '70px', 'circle') +
                    '</div>' + block(72, '100%', 'sk-gap') + block(72);
            },
            wallet: function () {
                return block(130) + block(34, '100%', 'circle') + block(56, '100%', 'sk-gap') +
                    block(56) + block(56);
            },
            profile: function () {
                return '<div class="sk-row"><div class="skb circle" style="width:64px;height:64px;flex:none"></div>' +
                    '<div class="grow">' + block(20, '52%') + block(14, '36%') + '</div></div>' +
                    '<div class="sk-row sk-gap">' + block(64, 'calc(25% - 8px)') + block(64, 'calc(25% - 8px)') +
                    block(64, 'calc(25% - 8px)') + block(64, 'calc(25% - 8px)') + '</div>' +
                    row(64, 40) + row(64, 40) + row(64, 40);
            }
        };

        function ensureOverlay() {
            if (overlay) { return overlay; }
            overlay = document.createElement('div');
            overlay.className = 'nav-sk-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            document.body.appendChild(overlay);
            return overlay;
        }

        function show(key) {
            var shape = SHAPES[key];
            if (!shape) { return; }
            var el = ensureOverlay();

            /* ارتفاع = بین هدر و ناوبری پایین تا پوستهٔ صفحه پوشانده شود */
            var head = document.querySelector('.app-header');
            var nav = document.querySelector('.bottom-nav');
            el.style.top = head ? head.offsetHeight + 'px' : '0';
            el.style.bottom = nav ? (nav.offsetHeight + 'px') : '0';
            if (nav && window.CSS && CSS.supports && CSS.supports('padding', 'env(safe-area-inset-bottom)')) {
                el.style.bottom = 'calc(' + nav.offsetHeight + 'px + env(safe-area-inset-bottom, 0px))';
            }

            el.innerHTML = shape();
            el.classList.add('show');
            window.clearTimeout(hideTimer);
            hideTimer = window.setTimeout(hide, 6000); /* ضدهنگ */
        }

        function hide() {
            window.clearTimeout(hideTimer);
            if (overlay) { overlay.classList.remove('show'); }
        }

        /* کلیک روی لینک‌های منوی پایین/هدر (فاز ضبط — قبل از wire:navigate) */
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
            var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
            if (!a || !a.hasAttribute('wire:navigate')) { return; }
            if (!(a.closest('.bottom-nav') || a.closest('.app-header'))) { return; }

            var key = pageKey(a.getAttribute('href'));
            if (!key || key === pageKey(window.location.href)) { return; } /* همان صفحه */
            show(key);
        }, true);

        /* پایان ناوبری → پوسته پنهان + انیمیشن ورود محتوا */
        document.addEventListener('livewire:navigated', function () {
            hide();
            var main = document.getElementById('appMain');
            if (main) {
                main.classList.remove('page-enter');
                void main.offsetWidth;
                main.classList.add('page-enter');
            }
        });
    })();

    return {
        withPort: withPort,
        apiUrl: apiUrl,
        token: token,
        user: user,
        setSession: setSession,
        clearSession: clearSession,
        requireAuth: requireAuth,
        requireCompleteProfile: requireCompleteProfile,
        api: api,
        toast: toast,
        btnLoading: btnLoading,
        fieldError: fieldError,
        clearFieldErrors: clearFieldErrors,
        applyErrors: applyErrors,
        confirm: confirm,
        updateAvatar: updateAvatar,
        refreshChrome: refreshChrome,
        countdown: countdown,
        debounce: debounce,
        statusBadge: statusBadge,
        onNavigate: onNavigate,
        refreshBottomNavActive: refreshBottomNavActive,
        theme: {
            get: currentTheme,
            set: setTheme,
            toggle: function () { setTheme(currentTheme() === 'dark' ? 'light' : 'dark'); }
        },
        logout: logout,
        faMoney: faMoney,
        faMoneyUnit: faMoneyUnit,
        toFaDigits: toFaDigits,
        toEnDigits: toEnDigits,
        esc: esc,
        compressImage: compressImage,
        detectFileType: detectFileType,
        normalizeMobile: normalizeMobile
    };
})();
