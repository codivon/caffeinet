/**
 * بوت تم لندینگ (ضد-FLUC) — باید در <head> و قبل از استایل‌ها لود شود.
 * (فایل خارجی = سازگار با CSP سخت‌گیرانه؛ اسکریپت درون‌خطی لازم نیست)
 *
 * پیش‌فرض: «روز» (روشن) — برخلاف پنل‌ها تم سیستم را دنبال نمی‌کند.
 * فقط اگر کاربر قبلاً «شب» را انتخاب کرده باشد (localStorage)، شب می‌شود.
 */
(function () {
    'use strict';

    try {
        var stored = null;
        try { stored = localStorage.getItem('caffeinet-theme'); } catch (e) { /* noop */ }

        if (stored === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    } catch (e) { /* localStorage در دسترس نیست — روز (پیش‌فرض) می‌ماند */
        document.documentElement.classList.remove('dark');
    }
})();
