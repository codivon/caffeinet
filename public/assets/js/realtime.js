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
 *     تحریم. سرور «کوتاه‌چرخه» است (فاز ۵۹-c): بعد از TTL (~۲۵ ثانیه) با
 *     event: close تمیز می‌بندد و ما بلافاصله با jitter دوباره وصل می‌شویم
 *     (رویدادهای فاصلهٔ reconnect با last_id پخش دوباره می‌شوند — صفر ازدست‌رفته).
 *   • روش polling      → RT غیرفعال؛ صفحات با پولینگ معمولی کار می‌کنند.
 *
 * فاز ۵۹-c — ضدتندزنی SSE روی هاست اشتراکی (ورکرها محدودند):
 *   خطای واقعی (بدون هیچ داده در ~۱۲ ثانیهٔ اول / close فوری / ۴۰۱) در
 *   پنجرهٔ ۶۰ ثانیه شمرده می‌شود؛ ۳ خطا → تعلیق SSE (cooldown نمایی
 *   ۶۰s→۸m) و برگشت خودکار صفحات به پولینگ اضطراری (RT.connected=false).
 *   پایان تعلیق → تلاش مجدد؛ اتصال سالم → صفر شدن شمارنده‌ها (برگشت نرم
 *   به SSE). چرخهٔ سالمِ کوتاه‌چرخه هرگز خطا حساب نمی‌شود.
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
    /* ترابورت ۲ — SSE (اتصال دائمی داخلی — فاز ۱۲ + سخت‌سازی ۵۹-c)      */
    /* ================================================================ */

    function sseActive() {
        return !!(cfg.enabled && String(cfg.method || '') === 'sse' && cfg.sse_url
            && typeof window.EventSource !== 'undefined');
    }

    var esTokenUsed = false; // آیا es فعلی با توکن باز شده؟

    /* ---- حالت‌های سخت‌سازی ۵۹-c ---- */
    var esOpenedAt = 0;       // زمان آخرین open موفق (ms)
    var esGotData = false;    // در استریم جاری دادهٔ واقعی (rt/ping/close) رسید؟
    var lastRtId = '';        // Last-Event-ID → پرش gap ها با ?last_id=
    var failTimes = [];       // timestamp خطاهای واقعی (پنجرهٔ ۶۰ ثانیه)
    var failStreak = 0;       // تعداد ساسپندهای پشت‌سرهم (بخاطر backoff نمایی)
    var suspendedUntil = 0;   // تا این زمان EventSource باز نشود (ms epoch)
    var lastAttemptAt = 0;    // ضدتندزنی: حداقل فاصلهٔ دو تلاش
    var reconnectTimer = null;

    function sseRetryMs() {
        var r = Number(cfg.sse_retry) || 2000;
        return (r < 500) ? 500 : (r > 30000 ? 30000 : r);
    }

    function jitter(ms) { return Math.round(ms * (0.75 + Math.random() * 0.75)); }

    /** اتصال سالم شد → شمارنده‌ها صفر (برگشت نرم به SSE) */
    function markHealthy() {
        esGotData = true;
        failTimes = [];
        failStreak = 0;
        suspendedUntil = 0;
    }

    function closeEs() {
        if (es) {
            try { es.close(); } catch (e) { /* noop */ }
            es = null;
        }
    }

    function scheduleReconnect(delay) {
        if (reconnectTimer) { return; }
        reconnectTimer = setTimeout(function () {
            reconnectTimer = null;
            if (!es) { connectSse(); }
        }, jitter(delay || sseRetryMs()));
    }

    /** خطای «واقعی»؟ = استریمِ جاری هیچ داده‌ای نداد و زود مرد
     *  (چرخهٔ سالم کوتاه‌چرخه/ضربان‌دار هرگز خطا حساب نمی‌شود) */
    function isHardFailure() {
        var age = esOpenedAt ? (Date.now() - esOpenedAt) : 0;
        return !esGotData && age < 12000;
    }

    /** ثبت خطای واقعی — ۳ بار در ۶۰ ثانیه → تعلیق + پولینگ اضطراری صفحات */
    function recordFailure() {
        var now = Date.now();
        failTimes.push(now);
        while (failTimes.length && (now - failTimes[0]) > 60000) { failTimes.shift(); }

        if (failTimes.length >= 3) {
            failStreak = Math.min(failStreak + 1, 8);
            failTimes = [];
            // cooldown نمایی: 60s → 120s → … → سقف ۸ دقیقه (با jitter ضدگله)
            suspendedUntil = now + jitter(Math.min(60000 * Math.pow(2, failStreak - 1), 480000));
            fireState(false); // صفحات → پولینگ اضطراری
            scheduleReconnect(suspendedUntil - now); // برگشت نرم به SSE
        }
    }

    function suspendDelay() {
        return Math.min(60000 * Math.pow(2, Math.max(failStreak - 1, 0)), 480000);
    }

    function connectSse(force) {
        if (es) { return es; }
        if (!sseActive()) { return null; }

        var now = Date.now();

        // تعلیق‌شده (خطاهای پشت‌سرهم) → جز در تلاش‌های اجباری باز نشو
        if (!force && now < suspendedUntil) { return null; }
        if (now - lastAttemptAt < 1000) { return null; } // ضدتندزنی
        if (document.hidden) { pausedHidden = true; return null; } // تب پنهان → اتصال ممنوع

        lastAttemptAt = now;

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

            // فاز ۵۹-c — replay: رویدادهای فاصلهٔ reconnect از دست نروند
            if (lastRtId) {
                url += (url.indexOf('?') > -1 ? '&' : '?') + 'last_id=' + encodeURIComponent(lastRtId);
            }

            es = new EventSource(url);
            esOpenedAt = Date.now();
            esGotData = false;

            // رویداد اصلی: {channel, event, payload} → مسیریابی به صفحات
            es.addEventListener('rt', function (ev) {
                markHealthy();
                try { if (ev && ev.lastEventId) { lastRtId = String(ev.lastEventId); } } catch (e) { /* noop */ }

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

            es.addEventListener('ping', function () { markHealthy(); /* ضربان — اتصال زنده است */ });

            // فاز ۵۹-c — پایان تمیز کوتاه‌چرخهٔ سرور (TTL) → reconnect کنترل‌شدهٔ خودمان
            es.addEventListener('close', function (ev) {
                markHealthy();

                var retry = sseRetryMs();
                try {
                    var d = JSON.parse(ev.data || '{}');
                    if (d && Number(d.retry) >= 500) { retry = Number(d.retry); }
                } catch (e) { /* پیش‌فرض */ }

                closeEs();
                fireState(true); // gap چندثانیه‌ای کوتاه‌چرخه «قطعی» نیست — صفحات بیدار نشوند
                scheduleReconnect(retry);
            });

            es.onopen = function () { esOpenedAt = Date.now(); fireState(true); };
            es.onerror = function () {
                fireState(false);

                var hard = isHardFailure();
                var closed = es && es.readyState === 2; // CLOSED — مرورگر دیگر reconnect نمی‌شود (مثلاً 401)

                if (closed) { closeEs(); }

                if (hard) {
                    recordFailure(); // ۳ بار در ۶۰ ثانیه → تعلیق + پولینگ اضطراری
                }

                if (!es) {
                    // یا CLOSED شده، یا تعلیق فعال شده — خودمان با فاصلهٔ امن وصل شویم
                    scheduleReconnect(hard ? suspendDelay() : sseRetryMs());
                }
                // در حالت CONNECTING مرورگر خودش طبق retry: سرور ادامه می‌دهد
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
       هر استریم SSE یک پروسهٔ PHP را تا ~TTL (فاز ۵۹-c: ~۲۵ ثانیه) اشغال
       می‌کند. تب‌های پس‌زمینه (غیرفعال) به رویدادِ لحظه‌ای نیاز ندارند —
       اتصال آن‌ها را می‌بندیم تا پروسه‌ها آزاد بمانند (سقف Entry Processes
       هاست). با بازگشت به تب، همان لحظه دوباره وصل می‌شود. در حالت پنهان،
       وضعیت «وصل» برای صفحات دست‌نخورده می‌ماند تا پولینگ اضطراری روشن نشود. */
    var pausedHidden = false;

    document.addEventListener('visibilitychange', function () {
        if (!sseActive()) { return; }

        if (document.hidden) {
            if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }
            if (es) {
                try { es.close(); } catch (e) { /* noop */ }
                es = null;
                pausedHidden = true;
            }
        } else if (!es) {
            pausedHidden = false;
            if (Date.now() < suspendedUntil) {
                // تعلیق فعال است — اما کاربر همین حالا صفحه را می‌بیند:
                // یک تلاش «گریس» اگر از آخرین تلاش ≥۱۰ ثانیه گذشته باشد
                if (Date.now() - lastAttemptAt > 10000) { connectSse(true); }
            } else {
                connectSse();
            }
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
            /* فاز ۱۴ — فیکس ریشه‌ای: cfg «درجا» پر می‌شود و دیگر بازتخصیص نمی‌شود.
               قبلاً `cfg = merged` بود و window.RT.cfg به شیء قدیمی اشاره می‌کرد؛
               نتیجه: `RT.cfg.channel = …` در اپ مشتری (notifications.js / order-detail.js /
               support-detail.js) روی شیء مرده نوشته می‌شد و bindUser چون کانالِ داخلی
               null می‌ماند بی‌صدا false برمی‌گرداند → هیچ شنونده‌ای روی استریم SSE/پوشر
               وصل نمی‌شد و «realtime» فقط در order-chat (که کانال را صریح پاس می‌داد)
               کار می‌کرد. حالا هویت RT.cfg === cfg داخلی همیشه حفظ می‌شود. */
            for (var k2 in newCfg) {
                if (Object.prototype.hasOwnProperty.call(newCfg, k2)) { cfg[k2] = newCfg[k2]; }
            }
            // SSE بدون اتصال → همان لحظه وصل شو؛ اگر es بدون توکنِ درحال-خطا
            // باز شده و حالا توکن رسید → ببند و با توکن از نو باز کن
            if (sseActive()) {
                if (cfg.sse_token && !esTokenUsed) {
                    closeEs();
                    suspendedUntil = 0; // توکن تازه رسید → تعلیق قبلی بی‌معناست
                }
                if (!es) { connectSse(true); } // force — احراز تازه‌شده باید تلاش کند
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
