/* اپ مشتری — پروفایل (نمای کاربر — v24: فرم ویرایش به صفحهٔ جدا منتقل شد) */
/* [Task 9] Vanilla JS — بدون جی‌کوئری */
/* global CN */
(function () {
    'use strict';

    if (!CN.requireAuth()) { return; }

    /* ---------- بارگذاری پروفایل + آمار (v40 — مهلت/تلاش‌مجدد مرکزی CN.api) ---------- */
    CN.api('/me', {
        timeout: 15000,
        retries: 2,
        success: function (resp) {
            var u = resp.user || {};
            var stats = resp.orders_stats || {};

            try { window.localStorage.setItem('cn_user', JSON.stringify(u)); } catch (e) { /* noop */ }
            CN.updateAvatar(u);

            /* هرو */
            var profileAvatar = document.getElementById('profileAvatar');
            if (profileAvatar) {
                /* v42 — آواتار تصویری یا حروف اول */
                if (u.avatar_url) {
                    profileAvatar.textContent = '';
                    profileAvatar.style.backgroundImage = 'url("' + u.avatar_url + '")';
                    profileAvatar.style.backgroundSize = 'cover';
                    profileAvatar.style.backgroundPosition = 'center';
                } else {
                    profileAvatar.textContent = initials(u);
                }
            }

            var profileName = document.getElementById('profileName');
            if (profileName) { profileName.textContent = u.full_name || 'کاربر مهمان'; }

            var profileMobile = document.getElementById('profileMobile');
            if (profileMobile) { profileMobile.textContent = u.mobile || '—'; }

            var profileSinceText = document.getElementById('profileSinceText');
            if (profileSinceText) { profileSinceText.textContent = u.member_since_fa ? 'عضو از ' + u.member_since_fa : 'عضو جدید'; }

            var profileCompleteBadge = document.getElementById('profileCompleteBadge');
            if (profileCompleteBadge) { profileCompleteBadge.classList.toggle('hidden', !u.profile_completed); }

            var profileBalance = document.getElementById('profileBalance');
            if (profileBalance) {
                profileBalance.textContent = u.wallet_balance !== undefined && u.wallet_balance !== null
                    ? CN.faMoneyUnit(u.wallet_balance) : '—';
            }

            /* هشدار پروفایل ناقص + پرچم لینک ویرایش */
            var pfIncompleteBanner = document.getElementById('pfIncompleteBanner');
            if (pfIncompleteBanner) { pfIncompleteBanner.classList.toggle('hidden', !!u.profile_completed); }

            var pfEditFlag = document.getElementById('pfEditFlag');
            if (pfEditFlag) { pfEditFlag.classList.toggle('hidden', !!u.profile_completed); }

            var pfEditHint = document.getElementById('pfEditHint');
            if (pfEditHint) { pfEditHint.textContent = u.profile_completed ? 'مشاهده و اصلاح اطلاعات شخصی' : 'برای استفاده از خدمات، تکمیل کنید'; }

            /* آمار سفارش‌ها */
            var statTotal = document.getElementById('statTotal');
            if (statTotal) { statTotal.textContent = CN.toFaDigits(stats.total || 0); }

            var statActive = document.getElementById('statActive');
            if (statActive) { statActive.textContent = CN.toFaDigits(stats.active || 0); }

            var statCompleted = document.getElementById('statCompleted');
            if (statCompleted) { statCompleted.textContent = CN.toFaDigits(stats.completed || 0); }

            var statCancelled = document.getElementById('statCancelled');
            if (statCancelled) { statCancelled.textContent = CN.toFaDigits(stats.cancelled || 0); }
        }
    });

    function initials(u) {
        if (u && u.name && u.family) {
            return (u.name.trim().charAt(0) || '؟') + (u.family.trim().charAt(0) || '');
        }
        return '؟';
    }

    /* ---------- خروج ---------- */
    var logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            CN.confirm({
                icon: '🚪',
                title: 'خروج از حساب',
                desc: 'برای ورود مجدد به کد پیامکی نیاز دارید.',
                okText: 'خروج',
                danger: true
            }, CN.logout);
        });
    }

    /* ================================================================
       v42 — آواتار: کراپر لینکدین‌وار (جابه‌جایی + بزرگ‌نمایی + برش مربع)
       خروجی: کادر ۱۵۰×۱۵۰ (کیفیت رتینا) → WebP اجباری → آپلود
       سرور خودش دوباره به ۷۵×۷۵ WebP بازانکودینگ می‌کند.
       ================================================================ */
    (function avatarCropper() {
        var btn = document.getElementById('profileAvatarBtn');
        var fileInput = document.getElementById('avatarInput');
        var sheet = document.getElementById('avcSheet');
        var backdrop = document.getElementById('avcBackdrop');
        var stage = document.getElementById('avcStage');
        var img = document.getElementById('avcImg');
        var zoomSlider = document.getElementById('avcZoom');
        var saveBtn = document.getElementById('avcSave');
        var errorEl = document.getElementById('avcError');
        if (!btn || !fileInput || !sheet) { return; }

        var state = { natW: 0, natH: 0, scale: 1, minScale: 1, x: 0, y: 0, busy: false };
        var objectUrl = null;

        /* ---------- باز/بستن شیت ---------- */
        function open() {
            sheet.classList.add('open');
            backdrop.classList.add('show');
        }
        function close() {
            sheet.classList.remove('open');
            backdrop.classList.remove('show');
            reset();
        }
        function reset() {
            if (objectUrl) { try { window.URL.revokeObjectURL(objectUrl); } catch (e) { /* noop */ } }
            objectUrl = null;
            img.removeAttribute('src');
            state = { natW: 0, natH: 0, scale: 1, minScale: 1, x: 0, y: 0, busy: state.busy };
            if (zoomSlider) { zoomSlider.value = '1'; }
            if (errorEl) { errorEl.classList.remove('show'); errorEl.textContent = ''; }
        }

        btn.addEventListener('click', function () { fileInput.click(); });
        document.getElementById('avcClose').addEventListener('click', close);
        document.getElementById('avcCancel').addEventListener('click', close);
        backdrop.addEventListener('click', close);

        /* ---------- انتخاب فایل ---------- */
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            fileInput.value = ''; /* اجازهٔ انتخاب دوبارهٔ همان فایل */
            if (!file) { return; }

            if (!/^image\/(jpeg|png|webp)$/.test(String(file.type || '').toLowerCase())) {
                CN.toast('فرمت تصویر مجاز نیست (JPG، PNG یا WebP).', 'error');
                return;
            }
            if (file.size > 8 * 1024 * 1024) {
                CN.toast('حجم تصویر نباید بیش از ۸ مگابایت باشد.', 'error');
                return;
            }

            /* فشرده‌سازی اولیه طبق تنظیمات (اگر تصویر خیلی بزرگ است) */
            var prep = file.size > 1.5 * 1024 * 1024 && CN.compressImage
                ? CN.compressImage(file, { maxSide: 2200 })
                : Promise.resolve(file);

            prep.then(function (f) {
                if (objectUrl) { try { window.URL.revokeObjectURL(objectUrl); } catch (e) { /* noop */ } }
                objectUrl = window.URL.createObjectURL(f);
                img.onload = function () {
                    state.natW = img.naturalWidth || 0;
                    state.natH = img.naturalHeight || 0;
                    fit();
                    open();
                };
                img.onerror = function () { CN.toast('تصویر قابل خواندن نیست.', 'error'); };
                img.src = objectUrl;
            });
        });

        /* ---------- هندسهٔ کراپ ---------- */
        function stageSize() {
            return stage.getBoundingClientRect().width || 300;
        }

        /* بزرگ‌نمایی پایه = پرشدن کامل کادر (cover) */
        function fit() {
            var s = stageSize();
            var cover = Math.max(s / state.natW, s / state.natH);
            state.minScale = cover;
            state.scale = cover;
            state.x = 0;
            state.y = 0;
            if (zoomSlider) { zoomSlider.value = '1'; }
            apply();
        }

        function clamp() {
            /* تصویر همیشه کادر را کامل پر کند (لبهٔ خالی نماند) */
            var halfW = (state.natW * state.scale) / 2;
            var halfH = (state.natH * state.scale) / 2;
            var maxX = Math.max(0, halfW - stageSize() / 2);
            var maxY = Math.max(0, halfH - stageSize() / 2);
            state.x = Math.min(maxX, Math.max(-maxX, state.x));
            state.y = Math.min(maxY, Math.max(-maxY, state.y));
        }

        function apply() {
            clamp();
            img.style.width = state.natW + 'px';
            img.style.height = state.natH + 'px';
            img.style.transform = 'translate(calc(-50% + ' + state.x + 'px), calc(-50% + ' + state.y + 'px)) scale(' + state.scale + ')';
        }

        function setScale(next) {
            var clamped = Math.min(state.minScale * 4, Math.max(state.minScale, next));
            state.scale = clamped;
            if (zoomSlider) { zoomSlider.value = String(clamped / state.minScale); }
            apply();
        }

        if (zoomSlider) {
            zoomSlider.addEventListener('input', function () {
                if (!state.natW) { return; }
                state.scale = state.minScale * parseFloat(zoomSlider.value || '1');
                apply();
            });
        }

        /* ---------- جابه‌جایی با ماوس/لمس + پینچ ---------- */
        var pointers = new Map();
        var pinchStart = null;

        stage.addEventListener('pointerdown', function (e) {
            if (!state.natW) { return; }
            stage.setPointerCapture && stage.setPointerCapture(e.pointerId);
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (pointers.size === 2) {
                var pts = Array.from(pointers.values());
                pinchStart = {
                    dist: Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y),
                    scale: state.scale
                };
            }
            e.preventDefault();
        });

        stage.addEventListener('pointermove', function (e) {
            if (!pointers.has(e.pointerId)) { return; }
            var prev = pointers.get(e.pointerId);
            var dx = e.clientX - prev.x;
            var dy = e.clientY - prev.y;
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

            if (pointers.size === 2 && pinchStart) {
                var pts = Array.from(pointers.values());
                var dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
                if (pinchStart.dist > 0) { setScale(pinchStart.scale * (dist / pinchStart.dist)); }
            } else {
                state.x += dx;
                state.y += dy;
                apply();
            }
            e.preventDefault();
        });

        function dropPointer(e) {
            pointers.delete(e.pointerId);
            if (pointers.size < 2) { pinchStart = null; }
        }
        stage.addEventListener('pointerup', dropPointer);
        stage.addEventListener('pointercancel', dropPointer);
        stage.addEventListener('wheel', function (e) {
            if (!state.natW) { return; }
            e.preventDefault();
            setScale(state.scale * (e.deltaY < 0 ? 1.08 : 0.92));
        }, { passive: false });

        /* ---------- ذخیره: برش ۱۵۰×۱۵۰ → WebP → آپلود ---------- */
        saveBtn.addEventListener('click', function () {
            if (!state.natW || state.busy) { return; }
            state.busy = true;
            saveBtn.disabled = true;

            try {
                var out = 150; /* ۲× کیفیت رتینا؛ سرور به ۷۵×۷۵ نهایی می‌کند */
                var canvas = document.createElement('canvas');
                canvas.width = out;
                canvas.height = out;
                var ctx = canvas.getContext('2d');

                /* ناحیهٔ کادر نسبت به تصویر: scale نسبت به اندازهٔ طبیعی */
                var s = stageSize();
                var sw = s / state.scale;                 /* اندازهٔ کادر در مختصات تصویر */
                var sx = (state.natW - sw) / 2 - state.x / state.scale;
                var sy = (state.natH - sw) / 2 - state.y / state.scale;

                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, sx, sy, sw, sw, 0, 0, out, out);

                /* WebP اجباری — کمترین حجم و سریع‌ترین خواندن (fallback: JPEG) */
                canvas.toBlob(function (blob) {
                    if (!blob) { failSave('خروجی تصویر ساخته نشد.'); return; }
                    var type = blob.type === 'image/webp' ? 'image/webp' : 'image/jpeg';
                    var ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
                    var final = new window.File([blob], 'avatar.' + ext, { type: type, lastModified: Date.now() });
                    upload(final);
                }, 'image/webp', 0.84);
            } catch (err) {
                failSave('برش تصویر ناموفق بود.');
            }

            function failSave(msg) {
                state.busy = false;
                saveBtn.disabled = false;
                if (errorEl) { errorEl.classList.add('show'); errorEl.textContent = msg; }
            }
        });

        function upload(file) {
            var fd = new FormData();
            fd.append('avatar', file, file.name);

            var url = CN.apiUrl('/profile/avatar');

            /* XHR با پیشرفت — مثل بقیهٔ آپلودهای اپ */
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.timeout = 30000;
            xhr.setRequestHeader('Accept', 'application/json');
            var tk = CN.token();
            if (tk) { xhr.setRequestHeader('Authorization', 'Bearer ' + tk); }

            xhr.onload = function () {
                state.busy = false;
                saveBtn.disabled = false;
                var resp = null;
                try { resp = JSON.parse(xhr.responseText); } catch (e) { /* noop */ }

                if (xhr.status >= 200 && xhr.status < 300 && resp && resp.avatar_url) {
                    /* به‌روزرسانی حافظهٔ محلی + همهٔ آواتارهای صفحه */
                    try {
                        var u = JSON.parse(window.localStorage.getItem('cn_user') || 'null') || {};
                        u.avatar_url = resp.avatar_url;
                        window.localStorage.setItem('cn_user', JSON.stringify(u));
                    } catch (e) { /* noop */ }
                    paintAvatar(resp.avatar_url);
                    CN.updateAvatar({ avatar_url: resp.avatar_url });
                    CN.toast(resp.message || 'آواتار شما به‌روزرسانی شد.', 'success');
                    close();
                } else {
                    if (errorEl) { errorEl.classList.add('show'); errorEl.textContent = (resp && resp.message) || 'آپلود ناموفق بود.'; }
                }
            };
            xhr.onerror = function () {
                state.busy = false;
                saveBtn.disabled = false;
                if (errorEl) { errorEl.classList.add('show'); errorEl.textContent = 'خطای شبکه — دوباره تلاش کنید.'; }
            };
            xhr.ontimeout = xhr.onerror;
            xhr.send(fd);
        }

        /* نمایش آواتار ذخیره‌شده روی هرو (جای حروف اول) */
        function paintAvatar(url) {
            var el = document.getElementById('profileAvatar');
            if (!el) { return; }
            el.textContent = '';
            el.style.backgroundImage = 'url("' + url + '")';
            el.style.backgroundSize = 'cover';
            el.style.backgroundPosition = 'center';
        }

        /* اگر کاربر از قبل آواتار دارد → نمایش */
        try {
            var u0 = JSON.parse(window.localStorage.getItem('cn_user') || 'null');
            if (u0 && u0.avatar_url) { paintAvatar(u0.avatar_url); }
        } catch (e) { /* noop */ }
    })();
})();
