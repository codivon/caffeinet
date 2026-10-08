/**
 * کافی‌نت آنلاین — Realtime (فاز ۱۳ + ۱۲-fix)
 * فایل مستقل مشترک بین پنل‌های پشتی و اپ مشتری — بدون Node / بدون بیلد.
 *
 * پیکربندی از ویژگی data-rt-config روی همین تگ <script> خوانده می‌شود
 * (سازگار با CSP سخت‌گیرانهٔ پروژه — بدون اسکریپت درون‌خطی):
 *   <script src="realtime.js" data-rt-config='{"enabled":true,"method":"sse","sse_url":"/realtime/stream"}'></script>
 *
 * معماری «بیدارباش» (Wake-up):
 *   رویداد فقط خبر می‌دهد «چیزی اتفاق افتاد»؛ داده همیشه از API خود سیستم
 *   خوانده می‌شود. با دریافت رویداد، پولینگ همان لحظه اجرا می‌شود و بازهٔ
 *   آن بزرگ‌تر می‌شود → هم آنی بودن، هم کاهش فشار دیتابیس.
 *
 * ترابورت‌ها (فاز ۱۲ — انتخاب از تب Realtime تنظیمات):
 *   • method "pusher"  → سوکت واقعی با سرویس سازگار با پروتکل پوشر
 *     (pusher.com یا سرور خودمیزبانِ Soketi/Reverb با host/port سفارشی).
 *   • method "sse"     → «اتصال دائمی داخلی» — EventSource همان دامنه؛
 *     مثل سوکت، بدون درخواست دوره‌ای، بدون سرویس بیرونی و بدون مشکل
 *     تحریم. سرور حداکثر ~۴ دقیقه استریم می‌کند و EventSource خودش
 *     دوباره وصل می‌شود (برای صفحات شفاف است).
 *   • روش polling      → RT غیرفعال؛ صفحات با پولینگ معمولی کار می‌کنند.
 *
 * «پوشر کامل» (حالت رویدادمحور):
 *   وقتی RT.active() و RT.connected() است، صفحات «بدون پولینگ» کار
 *   می‌کنند — فقط رویدادمحور. با قطع اتصال، صفحات خودکار به پولینگ
 *   اضطراری برمی‌گردند و با وصل شدن دوباره، پولینگ متوقف می‌شود.
 *
 * رابط عمومی:
 *   RT.on(channel, event, cb)   — اشتراک امن با fallback بی‌صدا
 *   RT.bindUser(event, cb)      — کانال اعلان کاربر جاری (اگر موجود)
 *   RT.leave(channel)           — ترک کانال (ضد زامبی در SPA)
 *   RT.active()                 — آیا Realtime رویدادمحور فعال است؟
 *   RT.connected()              — آیا اتصال (WS یا SSE) برقرار است؟
 *   RT.onConnection(cb)         — رویداد وصل/قطع شدن (خروجی: تابع لغو)
 *   RT.setup(cfg)               — جایگزینی/ترکیب پیکربندی (اپ مشتری)
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

    var client = null;      // Pusher
    var es = null;          // EventSource (SSE)
    var connecting = false;
    var boundChannels = {}; // "channel::event" → [cb, …]
    var connected = false;
    var stateCbs = [];

    function fireState(up) {
        connected = !!up;
        for (var i = 0; i < stateCbs.length; i++) {
            try { stateCbs[i](connected); } catch (e) { /* noop */ }
        }
    }

    /* ================================================================ */
    /* ترابورت ۱ — پوشر (سوکت واقعی؛ pusher.com یا خودمیزبان)            */
    /* ================================================================ */

    function connectPusher() {
        if (client) { return client; }
        if (connecting) { return null; }
        if (!cfg.enabled || !cfg.key) { return null; }
        if (typeof window.Pusher === 'undefined') { return null; }

        connecting = true;

        try {
            var opts = { forceTLS: true };

            // فاز ۱۲ — سرور خودمیزبانِ سازگار با پروتکل پوشر (Soketi/Reverb)
            if (cfg.host) {
                opts.cluster = String(cfg.cluster || 'mt1');
                opts.wsHost = String(cfg.host);
                opts.httpHost = String(cfg.host);
                if (cfg.port) { opts.wsPort = Number(cfg.port); opts.wssPort = Number(cfg.port); }
                opts.forceTLS = String(cfg.scheme || 'https') !== 'http';
                opts.enabledTransports = ['ws', 'wss'];
                if (opts.forceTLS) {
                    opts.wsPort = null;
                    opts.wssPort = cfg.port ? Number(cfg.port) : 443;
                }
            } else {
                opts.cluster = String(cfg.cluster || 'mt1');
            }

            client = new window.Pusher(String(cfg.key), opts);

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

    /* ================================================================ */
    /* ترابورت ۲ — SSE (اتصال دائمی داخلی — فاز ۱۲)                      */
    /* ================================================================ */

    function sseActive() {
        return !!(cfg.enabled && String(cfg.method || '') === 'sse' && cfg.sse_url
            && typeof window.EventSource !== 'undefined');
    }

    var esTokenUsed = false; // آیا es فعلی با توکن باز شده؟

    function connectSse() {
        if (es) { return es; }
        if (!sseActive()) { return null; }
        if (document.hidden) { pausedHidden = true; return null; } // تب پنهان → اتصال ممنوع

        try {
            var url = String(cfg.sse_url);

            /* هم‌دامنه‌سازی: اگر sse_url همان مبدأ صفحه است، نسبی کن تا
               تفاوت APP_URL/پروکسی‌ها هرگز CORS نسازد */
            try {
                var u = new URL(url, location.href);
                if (u.origin === location.origin) { url = u.pathname + u.search; }
            } catch (e) { /* نسبی بماند */ }

            // اپ مشتری: توکن در URL (EventSource اجازهٔ هدر نمی‌دهد)
            if (cfg.sse_token) {
                url += (url.indexOf('?') > -1 ? '&' : '?') + 'token=' + encodeURIComponent(String(cfg.sse_token));
            }
            esTokenUsed = !!cfg.sse_token;

            es = new EventSource(url);

            // رویداد اصلی: {channel, event, payload} → مسیریابی به صفحات
            es.addEventListener('rt', function (ev) {
                var msg = null;
                try { msg = JSON.parse(ev.data || '{}'); } catch (e) { msg = null; }
                if (!msg || !msg.event) { return; }

                var key = String(msg.channel || '') + '::' + String(msg.event);
                var handlers = boundChannels[key];
                if (handlers && handlers.length) {
                    for (var i = 0; i < handlers.length; i++) {
                        try { handlers[i](msg.payload || {}); } catch (e) { /* noop */ }
                    }
                }
            });

            es.addEventListener('ping', function () { /* ضربان — اتصال زنده است */ });

            es.onopen = function () { fireState(true); };
            es.onerror = function () {
                // EventSource خودش دوباره وصل می‌شود؛ اینجا فقط وضعیت را
                // گزارش می‌کنیم تا صفحات به پولینگ اضطراری برگردند.
                fireState(false);
            };
        } catch (e) {
            es = null;
        }

        return es;
    }

    /* ================================================================ */
    /* اشتراک‌ها (مشترک بین دو ترابورت)                                  */
    /* ================================================================ */

    /* فاز ۱۳ — بهینه‌سازی سرعت هاست اشتراکی:
       هر استریم SSE یک پروسهٔ PHP را تا ~۴ دقیقه اشغال می‌کند. تب‌های
       پس‌زمینه (غیرفعال) به رویدادِ لحظه‌ای نیاز ندارند — اتصال آن‌ها را
       می‌بندیم تا پروسه‌ها آزاد بمانند (سقف Entry Processes هاست). با
       بازگشت به تب، همان لحظه دوباره وصل می‌شود. در حالت پنهان، وضعیت
       «وصل» برای صفحات دست‌نخورده می‌ماند تا پولینگ اضطراری روشن نشود. */
    var pausedHidden = false;

    document.addEventListener('visibilitychange', function () {
        if (!sseActive()) { return; }

        if (document.hidden) {
            if (es) {
                try { es.close(); } catch (e) { /* noop */ }
                es = null;
                pausedHidden = true;
            }
        } else if (pausedHidden) {
            pausedHidden = false;
            if (!es) { connectSse(); }
        }
    });

    /** اشتراک روی کانال/رویداد — در صورت خطا بی‌صدا false */
    function on(channel, event, cb) {
        if (!channel || !event || typeof cb !== 'function') { return false; }

        var key = String(channel) + '::' + String(event);

        if (sseActive()) {
            if (!boundChannels[key]) { boundChannels[key] = []; }
            boundChannels[key].push(cb);
            connectSse();
            return true;
        }

        if (boundChannels[key]) {
            // اشتراک تکراری پوشر نمی‌سازیم اما کال‌بک اضافه ثبت می‌شود
            if (boundChannels[key].indexOf(cb) === -1) { boundChannels[key].push(cb); }
            return true;
        }

        var c = connectPusher();
        if (!c) { return false; }

        try {
            var ch = c.subscribe(String(channel));
            ch.bind(String(event), function (data) {
                var hs = boundChannels[key];
                if (!hs) { return; }
                for (var i = 0; i < hs.length; i++) {
                    try { hs[i](data || {}); } catch (e) { /* noop */ }
                }
            });
            boundChannels[key] = [cb];
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * ترک کانال (ضد زامبی در SPA):
     * صفحات Livewire در هر ناوبری دوباره اجرا می‌شوند؛ اگر اشتراک چتِ سفارشِ
     * قبلی نماند، رویدادش باز هم صفحهٔ قبلی را «بیدار» می‌کرد. با ترک کانال،
     * کال‌بک‌ها پاک و در پوشر unsubscribe می‌شود (در SSE فقط کال‌بک‌ها).
     */
    function leave(channel) {
        if (!channel) { return; }
        var prefix = String(channel) + '::';
        for (var key in boundChannels) {
            if (Object.prototype.hasOwnProperty.call(boundChannels, key) && key.indexOf(prefix) === 0) {
                delete boundChannels[key];
            }
        }
        try {
            if (client && client.channel) {
                client.channel(String(channel)).unbind_all();
                client.unsubscribe(String(channel));
            }
        } catch (e) { /* noop */ }
    }

    /** کانال اعلان کاربر جاری (اگر پیکربندی شده) */
    function bindUser(event, cb) {
        if (!cfg.channel) { return false; }
        return on(cfg.channel, event, cb);
    }

    function active() {
        if (sseActive()) { return true; }
        return !!(cfg.enabled && cfg.key && String(cfg.method || 'pusher') === 'pusher');
    }

    function connectedNow() {
        if (sseActive()) {
            return !!(es && es.readyState === 1 && connected);
        }
        return !!(active() && connected && client && client.connection && client.connection.state === 'connected');
    }

    /* ---------- رابط عمومی ---------- */
    window.RT = {
        on: on,
        leave: leave,
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
            // اگر همین حالا وصل است، فوراً خبر بده (SSE/پوشرِ از قبل متصل)
            if (connectedNow()) {
                try { cb(true); } catch (e) { /* noop */ }
            }
            return function () {
                var i = stateCbs.indexOf(cb);
                if (i > -1) { stateCbs.splice(i, 1); }
            };
        },
        /** برای اپ مشتری: پر کردن/ترکیب پیکربندی (مثلاً کانال و توکن از API) — ادغام امن */
        setup: function (newCfg) {
            if (!newCfg) { return active(); }
            var merged = {};
            for (var k in cfg) { if (Object.prototype.hasOwnProperty.call(cfg, k)) { merged[k] = cfg[k]; } }
            for (var k2 in newCfg) { if (Object.prototype.hasOwnProperty.call(newCfg, k2)) { merged[k2] = newCfg[k2]; } }
            cfg = merged;
            // SSE بدون اتصال → همان لحظه وصل شو؛ اگر es بدون توکنِ درحال-خطا
            // باز شده و حالا توکن رسید → ببند و با توکن از نو باز کن
            if (sseActive()) {
                if (cfg.sse_token && !esTokenUsed) {
                    try { if (es) { es.close(); } } catch (e) { /* noop */ }
                    es = null;
                }
                if (!es) { connectSse(); }
            }
            return active();
        },
        /** فاز ۱۲ — راه‌اندازی ترابورت در بار اول (لایه‌ها بعد از تزریق config صدا می‌زنند) */
        boot: function () {
            if (sseActive()) { connectSse(); return true; }
            if (cfg.enabled && cfg.key) { connectPusher(); }
            return active();
        }
    };

    // اتصال در بار اول (پنل‌ها: config سرور-رندر؛ اپ مشتری: بعد از RT.setup تکمیل می‌شود)
    if (sseActive()) { connectSse(); }
})();
