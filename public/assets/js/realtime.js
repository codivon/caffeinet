/**
 * کافی‌نت آنلاین — Realtime پوشر (فاز ۱۳)
 * فایل مستقل مشترک بین پنل‌های پشتی و اپ مشتری — بدون Node / بدون بیلد.
 *
 * پیکربندی از ویژگی data-rt-config روی همین تگ <script> خوانده می‌شود
 * (سازگار با CSP سخت‌گیرانهٔ پروژه — بدون اسکریپت درون‌خطی):
 *   <script src="realtime.js" data-rt-config='{"enabled":true,"key":"...","cluster":"mt1","channel":"u.x"}'></script>
 *
 * معماری «بیدارباش» (Wake-up):
 *   پوشر فقط خبر می‌دهد «چیزی اتفاق افتاد»؛ داده همیشه از API خود سیستم
 *   خوانده می‌شود. با دریافت رویداد، پولینگ همان لحظه اجرا می‌شود و بازهٔ
 *   آن بزرگ‌تر می‌شود → هم آنی بودن، هم کاهش فشار دیتابیس (MySQL).
 *
 * رابط عمومی:
 *   RT.on(channel, event, cb)   — اشتراک امن با fallback بی‌صدا
 *   RT.bindUser(event, cb)      — کانال اعلان کاربر جاری (اگر موجود)
 *   RT.active()                 — آیا پوشر فعال است؟
 *   RT.connected()              — آیا اتصال WebSocket برقرار است؟
 *   RT.onConnection(cb)         — رویداد وصل/قطع شدن اتصال (خروجی: تابع لغو اشتراک)
 *   RT.setup(cfg)               — جایگزینی پیکربندی (اپ مشتری: کانال از API)
 *
 * حالت «پوشر کامل» (v38):
 *   وقتی پوشر فعال و متصل است، صفحات «بدون پولینگ» کار می‌کنند — فقط رویدادمحور.
 *   با قطع اتصال، صفحات خودکار به پولینگ اضطراری برمی‌گردند و با وصل شدن
 *   دوباره، پولینگ متوقف می‌شود. (RT.onConnection)
 */
(function () {
    'use strict';

    var cfg = {};

    // خواندن پیکربندی از data-rt-config (بدون اسکریپت درون‌خطی — CSP-safe)
    try {
        var el = document.currentScript
            || document.querySelector('script[data-rt-config]');

        if (el && el.getAttribute) {
            cfg = JSON.parse(el.getAttribute('data-rt-config') || '{}') || {};
        }
    } catch (e) {
        cfg = {};
    }

    var client = null;
    var connecting = false;
    var boundChannels = {};
    var connected = false;
    var stateCbs = [];

    function fireState(up) {
        connected = !!up;
        for (var i = 0; i < stateCbs.length; i++) {
            try { stateCbs[i](connected); } catch (e) { /* noop */ }
        }
    }

    function connect() {
        if (client) { return client; }
        if (connecting) { return null; }
        if (!cfg.enabled || !cfg.key) { return null; }
        if (typeof window.Pusher === 'undefined') { return null; }

        connecting = true;

        try {
            client = new window.Pusher(String(cfg.key), {
                cluster: String(cfg.cluster || 'mt1'),
                forceTLS: true
            });

            // ردیابی وضعیت اتصال برای fallback خودکار صفحات
            client.connection.bind('connected', function () { fireState(true); });
            client.connection.bind('disconnected', function () { fireState(false); });
            client.connection.bind('failed', function () { fireState(false); });
            client.connection.bind('unavailable', function () { fireState(false); });
        } catch (e) {
            client = null;
        }

        connecting = false;
        return client;
    }

    /** اشتراک روی کانال/رویداد — در صورت خطا بی‌صدا false */
    function on(channel, event, cb) {
        if (!channel || !event) { return false; }

        var key = channel + '::' + event;
        if (boundChannels[key]) { return true; } // اشتراک تکراری نمی‌سازیم

        var c = connect();
        if (!c) { return false; }

        try {
            var ch = c.subscribe(String(channel));
            ch.bind(String(event), function (data) {
                try { cb(data || {}); } catch (e) { /* noop */ }
            });
            boundChannels[key] = true;
            return true;
        } catch (e) {
            return false;
        }
    }

    /** کانال اعلان کاربر جاری (اگر پیکربندی شده) */
    function bindUser(event, cb) {
        if (!cfg.channel) { return false; }
        return on(cfg.channel, event, cb);
    }

    function active() {
        return !!(cfg.enabled && cfg.key);
    }

    function connectedNow() {
        return !!(active() && connected && client && client.connection && client.connection.state === 'connected');
    }

    /* ---------- رابط عمومی ---------- */
    window.RT = {
        on: on,
        bindUser: bindUser,
        active: active,
        connected: connectedNow,
        cfg: cfg,
        /**
         * رویداد وصل/قطع اتصال — cb(up) با true/false صدا زده می‌شود.
         * خروجی: تابع لغو اشتراک (برای صفحات SPA که در هر ناوبری دوباره اجرا می‌شوند).
         */
        onConnection: function (cb) {
            if (typeof cb !== 'function') { return function () {}; }
            stateCbs.push(cb);
            return function () {
                var i = stateCbs.indexOf(cb);
                if (i > -1) { stateCbs.splice(i, 1); }
            };
        },
        /** برای اپ مشتری: پر کردن/ترکیب پیکربندی (مثلاً کانال از API) */
        setup: function (newCfg) {
            cfg = newCfg || {};
            return active();
        }
    };
})();
