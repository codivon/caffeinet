/* ==========================================================================
   فاز ۶۰ — رندر QR سفارش/پرداخت (پنل ستافی) + کپی لینک پرداخت
   وابستگی: assets/js/vendor/qrcode.min.js (qrcodejs 1.0.0 — بدون Node)
   سازوکار:
     • هر المان با [data-ak-qr] → new QRCode(el, {text, width, height, correctLevel})
     • بازسازی بعد از هر رندر مجدد لایووایر (پول/ناوبری) با رویدادهای livewire
     • دکمهٔ [data-ak-copy] → navigator.clipboard + توست پنل (App.toast)
   ========================================================================== */
(function () {
    'use strict';

    /* رندر (بازسازی) یک المان QR — محتوای قبلی پاک می‌شود تا دوباره رسم شود */
    function renderQr(el) {
        var text = el.getAttribute('data-ak-qr');
        if (!text) { return; }

        /* متن/URL تغییری نکرده؟ دوباره رسم نکن (ضد چشمک در پول ۳۰ ثانیه).
           فاز ۶۰ — گارد فقط وقتی معتبر است که بچهٔ واقعی (img/canvas) هنوز در DOM باشد؛
           مورفِ لایووایر (پول ۳۰ثانیه) innerHTML را می‌تراشد ولی پراپرتی JS می‌ماند →
           بر اساس فلگِ زنده‌مانده skip نکن (باگ QR خالی). */
        if (el.__akQrText === text && el.querySelector('img, canvas')) { return; }

        var size = parseInt(el.getAttribute('data-ak-qr-size'), 10) || 120;

        el.innerHTML = '';
        el.__akQrText = text;
        el.__akQrOk = false;

        try {
            if (!window.QRCode) { throw new Error('QRCode lib missing'); }

            /* پس‌زمینهٔ سفید همیشه — خوانایی در هر دو تم روشن/تیره */
            new QRCode(el, {
                text: text,
                width: size,
                height: size,
                colorDark: '#1c1917',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
            el.__akQrOk = true;
            el.classList.add('ok');
        } catch (e) {
            el.classList.remove('ok');
            el.innerHTML = '<span class="ak-qr-fallback">QR</span>';
        }
    }

    function refresh() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-ak-qr]'), renderQr);
    }

    /* ---------- کپی لینک پرداخت (delegation — از رندر مجدد زنده می‌ماند) ---------- */
    function copyLegacy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        document.body.removeChild(ta);
        return ok;
    }

    function copyText(text) {
        /* فال‌بک: بدون clipboard API یا رد شدن (مثلاً «Document is not focused») → execCommand */
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).catch(function () {
                if (!copyLegacy(text)) { throw new Error('copy failed'); }
            });
        }

        if (!copyLegacy(text)) {
            return Promise.reject(new Error('copy failed'));
        }

        return Promise.resolve();
    }

    function panelToast(message, type) {
        if (window.App && typeof App.toast === 'function') { App.toast(message, type || 'success'); return; }
        if (window.PanelUI && typeof PanelUI.toast === 'function') { PanelUI.toast(message, type || 'success'); }
    }

    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-ak-copy]') : null;
        if (!btn) { return; }

        e.preventDefault();
        copyText(btn.getAttribute('data-ak-copy') || '').then(function () {
            panelToast(btn.getAttribute('data-toast') || 'کپی شد.', 'success');
            btn.classList.add('ak-copied');
            window.setTimeout(function () { btn.classList.remove('ak-copied'); }, 1600);
        }).catch(function () {
            panelToast('کپی انجام نشد؛ لینک را دستی انتخاب کنید.', 'error');
        });
    });

    /* ---------- بازسازی بعد از رندر مجدد لایووایر ---------- */
    var refreshQueued = false;

    /* refresh در فریم بعدی — یک‌بار در هر موج رویداد (ضد اسپم) */
    function scheduleRefresh() {
        if (refreshQueued) { return; }
        refreshQueued = true;
        window.requestAnimationFrame(function () {
            refreshQueued = false;
            refresh();
        });
    }

    function bindLivewire() {
        if (!window.Livewire || window.__akQrBound) { return; }
        window.__akQrBound = true;

        refresh(); // اولین رندر پس از init (اسکریپت defer است)

        /* پول ۳۰ثانیه/اکشن‌ها → هر به‌روزرسانی کامپوننت QR را تازه می‌کند.
           فاز ۶۰ — رویدادهای livewire:update(d) روی document منتشر نمی‌شوند (تأیید با
           تست زنده)؛ هوک رسمی commit — دست‌العمل لایووایر: ctx.succeed(cb) را «صدا بزن»
           (برگشاندن آبجکت اثر ندارد — با تست زنده تأیید شد). رفرش idempotent است. */
        try {
            Livewire.hook('commit', function (ctx) {
                if (ctx && typeof ctx.succeed === 'function') {
                    ctx.succeed(function () { scheduleRefresh(); });
                }
            });
        } catch (e) { /* بدون هوک — شنونده‌های document */ }
        document.addEventListener('livewire:update', scheduleRefresh);
        document.addEventListener('livewire:updated', scheduleRefresh);
        document.addEventListener('livewire:navigated', refresh);

        /* فاز ۶۰ — رویداد اکشن «ساخت لینک پرداخت»: URL تازهٔ ۲۰ دقیقه‌ای را
           روی دکمهٔ کپی/باکس QR همان صفحه می‌نشاند (بدون خروج از صفحه) */
        Livewire.on('payment-link-ready', function (payload) {
            var data = Array.isArray(payload) ? payload[0] : (payload || {});
            var url = data && data.url;

            if (!url) { return; }

            Array.prototype.forEach.call(document.querySelectorAll('[data-ak-paylink-block], [data-ak-paylink-empty]'), function (box) {
                var urlEl = box.querySelector('.ak-qr-url');
                if (urlEl) { urlEl.textContent = url.length > 64 ? url.slice(0, 64) + '…' : url; }
            });

            document.querySelectorAll('[data-ak-paylink-block]').forEach(function (box) {
                box.classList.remove('hidden');
            });
            document.querySelectorAll('[data-ak-paylink-empty]').forEach(function (box) {
                box.classList.add('hidden');
            });

            var qr = document.querySelector('[data-ak-paylink-block] [data-ak-qr]');
            if (qr) {
                qr.setAttribute('data-ak-qr', url);
                renderQr(qr);
            }
            document.querySelectorAll('[data-ak-paylink-block] [data-ak-copy]').forEach(function (btn) {
                btn.setAttribute('data-ak-copy', url);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindLivewire, { once: true });
    } else {
        bindLivewire();
    }
    document.addEventListener('livewire:init', bindLivewire, { once: true });
})();
