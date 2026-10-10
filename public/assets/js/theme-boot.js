/**
 * بوت تم (ضد-FOUC) — باید در <head> و قبل از استایل‌ها به‌صورت parser-blocking لود شود.
 * (فایل خارجی = سازگار با CSP سخت‌گیرانه؛ اسکریپت درون‌خطی لازم نیست)
 *
 * ترتیب اولویت:
 *   ۱. تم ذخیره‌شدهٔ کاربر (localStorage «caffeinet-theme»)
 *   ۲. تم سیستم‌عامل (prefers-color-scheme)
 */
(function () {
    'use strict';

    /* v42 — کوکی «cn_theme» برای سرور (مانیفست PWA: رنگ اسپلش/نوار روشن/تاریک).
       همهٔ سیستم‌های تعویض تم (اپ/پنل‌ها/لندینگ) هم با همین تابع همگام می‌مانند. */
    window.CNThemeCookie = function (mode) {
        try {
            document.cookie = 'cn_theme=' + (mode === 'dark' ? 'dark' : 'light') +
                '; path=/; max-age=31536000; SameSite=Lax';
        } catch (e) { /* noop */ }
    };

    try {
        var stored = localStorage.getItem('caffeinet-theme');

        var wantsDark = stored === 'dark' || (!stored && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches);

        if (wantsDark) {
            document.documentElement.classList.add('dark');
        }
        window.CNThemeCookie(wantsDark ? 'dark' : 'light');

        // اگر تم سیستم عوض شود و کاربر انتخاب صریح نداشته باشد، همگام بمان (فقط اولین لود)
        if (!stored && window.matchMedia) {
            try {
                matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                    try { localStorage.removeItem('caffeinet-theme'); } catch (err) { /* noop */ }
                    if (window.PanelUI && window.PanelUI.theme) {
                        window.PanelUI.theme.set(e.matches ? 'dark' : 'light', { persist: false });
                    } else {
                        document.documentElement.classList.toggle('dark', e.matches);
                    }
                });
            } catch (err) { /* Safari قدیمی */ }
        }
    } catch (e) { /* localStorage در دسترس نیست — تم سیستم اعمال نمی‌شود */ }

    /* ═══════ v43 — نگهبان تم (رفع «فلش سفید» بعد از ناوبری SPA) ═══════
       Livewire در هر wire:navigate اتریبیوت‌های <html> را با نسخهٔ
       سرور-رندر (بدون کلاس dark) جایگزین می‌کند → یک فریم با تم اشتباه
       رندر می‌شد (آیکون‌های نوتیف/تم در اپ لحظه‌ای سفید می‌شدند).
       این ناظر هر تغییر کلاس <html> را در همان microtask (قبل از paint)
       با انتخاب واقعی کاربر مقایسه و اصلاح می‌کند:
         • اگر state با localStorage/سیستم هم‌خوان باشد (تعویض دستی کاربر)
           هیچ دخالتی نمی‌کند.
         • اگر Livewire کلاس را قپ کرده باشد، همان لحظه برمی‌گرداند. */
    try {
        var themeWantsDark = function () {
            var s = null;
            try { s = localStorage.getItem('caffeinet-theme'); } catch (err) { /* noop */ }
            return s === 'dark' || (!s && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches);
        };
        var themeSync = function () {
            var wants = false;
            try { wants = themeWantsDark(); } catch (err) { return; }
            var html = document.documentElement;
            if (html.classList.contains('dark') !== wants) {
                html.classList.toggle('dark', wants);
                if (window.CNThemeCookie) { window.CNThemeCookie(wants ? 'dark' : 'light'); }
            }
        };
        if (window.MutationObserver) {
            var themeGuard = new MutationObserver(function () { themeSync(); });
            themeGuard.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            /* بعد از هر ناوبری SPA هم یک بررسی قطعی */
            window.addEventListener('livewire:navigated', themeSync, true);
        }
    } catch (e) { /* ناظر تم حیاتی نیست */ }
})();
