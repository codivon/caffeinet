/**
 * کافی‌نت آنلاین — کتابخانهٔ رابط کاربری پنل‌ها (Panel UI Kit)
 * فایل مستقل — بدون Node / بدون بیلد (لینک مستقیم بعد از core.js)
 * [Task 9] بازنویسی کامل به Vanilla JS — بدون هیچ کتابخانهٔ خارجی:
 *   • رویدادهای سفارشی دیالوگ با CustomEvent (ui:ok / ui:cancel)
 *   • رویداد تم با CustomEvent روی document (detail = حالت)
 *   • ساخت عناصر با DOM استاندارد (createElement/innerHTML)
 *
 * PanelUI.alert({title, desc, type, okText}, onClose)
 *   type: success | error | warn | info
 * PanelUI.confirm({title, desc, okText, cancelText, danger, icon}, onOk)
 *   danger → دکمهٔ اصلی قرمز (تأیید عملیات حساس)
 * PanelUI.toast(message, type, {duration})
 *   type: success | error | info | warn
 *
 * امکانات: انیمیشن ورود/خروج، پشتیبانی کیبورد (Esc/Enter)، قفل اسکرول بدنه،
 * شیت پایین در موبایل، توقف عمر توست روی هاور، حداکثر ۴ توست هم‌زمان.
 */

(function (window) {
    'use strict';

    /* ---------- آیکون‌ها ---------- */
    var ICONS = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="ui-draw" d="M20 6 9 17l-5-5"/></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="7" x2="12" y2="13"/><circle cx="12" cy="16.6" r="0.5" fill="currentColor" stroke="none"/></svg>',
        warn: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.6 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="14"/><circle cx="12" cy="17.2" r="0.5" fill="currentColor" stroke="none"/></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="11" x2="12" y2="16.5"/><circle cx="12" cy="7.6" r="0.5" fill="currentColor" stroke="none"/></svg>',
        question: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.4 9a2.6 2.6 0 0 1 5.1.8c0 1.7-2.5 2.2-2.5 3.7"/><circle cx="12" cy="17.2" r="0.5" fill="currentColor" stroke="none"/></svg>'
    };
    var TONE_FOR_CONFIRM = { danger: 'danger', warn: 'warn', info: 'info' };

    var openDialogs = [];      /* پشتهٔ دیالوگ‌های باز */
    var keyHandlerBound = false;
    var prevFocus = null;

    /* ---------- ابزار داخلی ---------- */
    function lockScroll() {
        if (openDialogs.length === 1) {
            document.body.dataset.uiPrevOverflow = document.body.style.overflow || '';
            document.body.style.overflow = 'hidden';
        }
    }
    function unlockScroll() {
        if (openDialogs.length === 0 && document.body.dataset.uiPrevOverflow !== undefined) {
            document.body.style.overflow = document.body.dataset.uiPrevOverflow;
            delete document.body.dataset.uiPrevOverflow;
        }
    }
    function bindKeys() {
        if (keyHandlerBound) { return; }
        keyHandlerBound = true;
        document.addEventListener('keydown', function (e) {
            if (!openDialogs.length) { return; }
            var top = openDialogs[openDialogs.length - 1];
            if (e.key === 'Escape') {
                e.stopImmediatePropagation();
                top.wrap.dispatchEvent(new CustomEvent('ui:cancel'));
            } else if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                e.stopImmediatePropagation();
                top.wrap.dispatchEvent(new CustomEvent('ui:ok'));
            }
        });
    }

    /**
     * ساخت و نمایش دیالوگ (alert یا confirm)
     */
    function dialog(opts, kind, actions) {
        opts = opts || {};

        var tone = kind === 'confirm'
            ? (TONE_FOR_CONFIRM[opts.danger === true ? 'danger' : 'info'] || 'info')
            : (opts.type || 'info');
        if (kind === 'confirm' && !opts.danger) { tone = 'info'; }

        var iconSvg = ICONS[opts.icon] || ICONS[kind === 'confirm' ? 'question' : tone] || ICONS.info;

        var html =
            '<div class="ui-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ui-dialog-title">' +
            '  <div class="ui-modal" data-tone="' + tone + '">' +
            '    <span class="ui-modal-icon">' + iconSvg + '</span>' +
            '    <h3 class="ui-modal-title" id="ui-dialog-title">' + escapeHtml(opts.title || '') + '</h3>' +
            '    <p class="ui-modal-desc">' + escapeHtml(opts.desc || '') + '</p>' +
            '    <div class="ui-modal-actions"></div>' +
            '  </div>' +
            '</div>';

        /* پارس مارک‌آپ با DOM استاندارد — ریشه، همان .ui-modal-backdrop است */
        var host = document.createElement('div');
        host.innerHTML = html;
        var wrap = host.firstElementChild;
        var actionsEl = wrap.querySelector('.ui-modal-actions');

        /* دکمه‌ها — با row-reverse در RTL، دکمهٔ اصلی چپ و انصراف راست می‌افتد (قرارداد فارسی) */
        var okBtn = document.createElement('button');
        okBtn.type = 'button';
        okBtn.className = (tone === 'danger' ? 'ui-btn-danger' : 'btn btn-primary btn-shine') + ' ui-press';
        okBtn.textContent = opts.okText || (kind === 'confirm' ? 'تأیید' : 'متوجه شدم');
        actionsEl.appendChild(okBtn);

        var cancelBtn = null;
        if (kind === 'confirm') {
            cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-ghost ui-press';
            cancelBtn.textContent = opts.cancelText || 'انصراف';
            actionsEl.appendChild(cancelBtn);
        }

        var closed = false;
        function close() {
            if (closed) { return; }
            closed = true;
            wrap.classList.add('ui-closing');
            var modalEl = wrap.querySelector('.ui-modal');
            if (modalEl) { modalEl.classList.add('ui-closing'); }
            var idx = openDialogs.indexOf(entry);
            if (idx > -1) { openDialogs.splice(idx, 1); }
            unlockScroll();
            setTimeout(function () {
                wrap.remove();
                if (prevFocus && document.contains(prevFocus)) { try { prevFocus.focus(); } catch (e) { /* noop */ } }
            }, 230);
        }

        var entry = { wrap: wrap, close: close };
        openDialogs.push(entry);
        lockScroll();
        bindKeys();

        prevFocus = document.activeElement;
        document.body.appendChild(wrap);
        setTimeout(function () { okBtn.focus(); }, 60);

        okBtn.addEventListener('click', function () { wrap.dispatchEvent(new CustomEvent('ui:ok')); });
        if (cancelBtn) { cancelBtn.addEventListener('click', function () { wrap.dispatchEvent(new CustomEvent('ui:cancel')); }); }

        wrap.addEventListener('ui:ok', function () {
            var proceed = true;
            if (actions && typeof actions.onOk === 'function') { proceed = actions.onOk() !== false; }
            if (proceed !== false) { close(); if (actions && typeof actions.onClosed === 'function') { actions.onClosed(); } }
        });
        wrap.addEventListener('ui:cancel', function () {
            close();
            if (actions && typeof actions.onCancel === 'function') { actions.onCancel(); }
        });

        /* کلیک روی پس‌زمینه = انصراف (فقط confirm؛ alert عمداً می‌ماند) */
        if (kind === 'confirm' && !opts.static) {
            wrap.addEventListener('click', function (e) {
                if (e.target === wrap) { wrap.dispatchEvent(new CustomEvent('ui:cancel')); }
            });
        }

        return entry;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    /* ---------- توست ---------- */
    function ensureToastWrap() {
        var wrap = document.getElementById('ui-toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'ui-toast-wrap';
            wrap.setAttribute('aria-live', 'polite');
            document.body.appendChild(wrap);
        }
        return wrap;
    }

    var TOAST_ICON = {
        success: ICONS.success,
        error: ICONS.error,
        warn: ICONS.warn,
        info: ICONS.info
    };

    function toast(message, type, options) {
        type = TOAST_ICON[type] ? type : 'info';
        options = options || {};
        var duration = options.duration || 4400;

        var wrap = ensureToastWrap();

        /* حداکثر ۴ توست هم‌زمان — قدیمی‌ترین‌ها بسته شوند (همه جز ۳ تای آخر) */
        var kids = wrap.querySelectorAll(':scope > .ui-toast');
        for (var i = 0; i < kids.length - 3; i++) { dismissToast(kids[i]); }

        var host = document.createElement('div');
        host.innerHTML =
            '<div class="ui-toast" data-tone="' + type + '" role="status">' +
            '  <span class="ui-toast-icon">' + TOAST_ICON[type] + '</span>' +
            '  <span class="ui-toast-msg">' + escapeHtml(message) + '</span>' +
            '  <span class="ui-toast-bar" style="animation-duration:' + duration + 'ms"></span>' +
            '</div>';
        var el = host.firstElementChild;
        wrap.appendChild(el);

        var lifeTimer = setTimeout(function () { dismissToast(el); }, duration);

        /* توقف عمر روی هاور + کلیک برای بستن */
        el.addEventListener('mouseenter', function () { el.classList.add('ui-hold'); clearTimeout(lifeTimer); });
        el.addEventListener('mouseleave', function () {
            el.classList.remove('ui-hold');
            /* ری‌استارت نوار + عمر کوتاه‌شده */
            var bar = el.querySelector('.ui-toast-bar');
            if (bar) {
                bar.style.animation = 'none';
                void bar.offsetWidth;
                bar.style.animation = '';
            }
            lifeTimer = setTimeout(function () { dismissToast(el); }, 1500);
        });
        el.addEventListener('click', function () { clearTimeout(lifeTimer); dismissToast(el); });

        return el;
    }

    function dismissToast(el) {
        if (el.__closing) { return; }
        el.__closing = true;
        el.classList.add('ui-closing');
        setTimeout(function () { el.remove(); }, 260);
    }

    /* ---------- مدیریت تم (روشن/تاریک) — فاز ۱۰ ---------- */
    var THEME_KEY = 'caffeinet-theme';
    var themeAnimTimer = null;

    function currentTheme() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    }

    function syncToggleButtons() {
        var mode = currentTheme();
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
            btn.setAttribute('title', mode === 'dark' ? 'حالت روشن' : 'حالت تاریک');
        });
        document.dispatchEvent(new CustomEvent('ui:theme', { detail: mode }));
    }

    function setTheme(mode, options) {
        options = options || {};
        var htmlEl = document.documentElement;
        var isDark = mode === 'dark';
        if (isDark === htmlEl.classList.contains('dark')) { syncToggleButtons(); return; }

        /* انیمیشن نرم فقط هنگام تعویض (نه لود اولیه) */
        if (options.animate !== false) {
            htmlEl.classList.add('theme-anim');
            clearTimeout(themeAnimTimer);
            themeAnimTimer = setTimeout(function () { htmlEl.classList.remove('theme-anim'); }, 480);
        }

        htmlEl.classList.toggle('dark', isDark);
        if (options.persist !== false) {
            try { localStorage.setItem(THEME_KEY, mode); } catch (e) { /* noop */ }
        }
        syncToggleButtons();
    }

    /* init: اتصال دکمه‌های سوییچ (delegate — برای محتوای داینامیک هم کار می‌کند) */
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-theme-toggle]') : null;
        if (btn) { setTheme(currentTheme() === 'dark' ? 'light' : 'dark'); }
    });

    /* [Task 8-fix] اعمال دوبارهٔ تم بعد از هر ناوبری SPA —
       Livewire در هر ناوبری attributeهای <html> را با نسخهٔ سرور-رندر
       (بدون کلاس dark) جایگزین می‌کند و تم می‌پرد. */
    window.addEventListener('livewire:navigated', function () {
        var stored = null;
        try { stored = localStorage.getItem(THEME_KEY); } catch (e) { /* noop */ }
        var dark = stored === 'dark' || (!stored && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        syncToggleButtons();
    });

    window.PanelUI = window.PanelUI || {};
    window.PanelUI.theme = {
        get: currentTheme,
        set: setTheme,
        toggle: function () { setTheme(currentTheme() === 'dark' ? 'light' : 'dark'); },
        init: function () { syncToggleButtons(); }
    };
    /* اسکریپت در انتهای body لود می‌شود — اجرای فوری به‌جای DOM-ready */
    window.PanelUI.theme.init();

    /* ---------- API عمومی ---------- */
    window.PanelUI.alert = function (opts, onClose) { return dialog(opts, 'alert', { onClosed: onClose }); };
    window.PanelUI.confirm = function (opts, onOk) { return dialog(opts, 'confirm', { onOk: onOk }); };
    window.PanelUI.toast = toast;
})(window);
