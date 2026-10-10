/* کافی‌نت آنلاین — منطق صفحه آفلاین (PWA فاز ۱۴)
 * فایل خارجی است چون CSP پروژه اسکریپت درون‌خطی را بلاک می‌کند (script-src 'self').
 * توسط Service Worker پیش‌کش می‌شود تا در حالت آفلاین هم کار کند.
 * v3 — پوستهٔ پالت + تم روشن/تاریک:
 *   هنگام آنلاین‌بودن، هستهٔ اپ/پنل‌ها CSS پالت «ظاهر و رنگ‌بندی» را در
 *   localStorage «cn-appearance-css:<panel>» ذخیره می‌کنند؛ اینجا همان
 *   پوسته تزریق می‌شود تا صفحهٔ آفلاین هم دقیقاً هم‌رنگ تنظیمات باشد.
 */
(function () {
    'use strict';

    /* ---------- پوستهٔ پالت + تم (قبل از هر چیز تا فلش نزند) ---------- */
    (function applySkin() {
        var path = location.pathname || '/';
        var panel = path.indexOf('/admin') === 0 ? 'admin'
            : path.indexOf('/organization') === 0 ? 'org'
            : path.indexOf('/coffeenet') === 0 ? 'coffeenet'
            : path.indexOf('/operator') === 0 ? 'operator'
            : 'app';

        var dark = false;
        try {
            var stored = localStorage.getItem('caffeinet-theme');
            dark = stored === 'dark' || (!stored && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches);
        } catch (e) { dark = false; }
        document.documentElement.classList.toggle('dark', dark);

        try {
            var css = localStorage.getItem('cn-appearance-css:' + panel)
                || localStorage.getItem('cn-appearance-css:app') || '';
            if (css) {
                var style = document.createElement('style');
                style.id = 'appearance-css';
                style.textContent = css;
                document.head.appendChild(style);

                /* رنگ‌های مشتق صفحهٔ آفلاین از متغیرهای پالت */
                var brand = getComputedStyle(document.documentElement).getPropertyValue('--brand-500').trim();
                var brand700 = getComputedStyle(document.documentElement).getPropertyValue('--brand-700').trim();
                var paper = getComputedStyle(document.documentElement).getPropertyValue('--paper').trim();
                if (brand) {
                    var skin = document.createElement('style');
                    skin.textContent =
                        'body { --of-glow: ' + hexA(brand, .30) + '; --of-glow2: ' + hexA(brand, .12) + ';' +
                        (dark ? '' : ' --of-bg: ' + (paper || '#eff4fb') + ';') + ' }' +
                        (dark ? ' body { --of-bg-dark: ' + shade(brand700, -78) + '; }' : '') +
                        'button { --of-btn-shadow: 0 14px 34px ' + hexA(brand, .42) + '; }';
                    document.head.appendChild(skin);
                }
            }
        } catch (e) { /* پالت در دسترس نیست — fallback آبی پیش‌فرض */ }

        function hexA(hex, a) {
            var c = parseHex(hex);
            return c ? 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + a + ')' : null;
        }
        function shade(hex, amt) {
            var c = parseHex(hex);
            if (!c) { return null; }
            return 'rgb(' + c.map(function (v) {
                return Math.max(0, Math.min(255, v + amt));
            }).join(',') + ')';
        }
        function parseHex(hex) {
            hex = String(hex || '').trim();
            var m = /^#?([0-9a-f]{6})$/i.exec(hex) || /^#?([0-9a-f]{3})$/i.exec(hex);
            if (!m) { return null; }
            var h = m[1];
            if (h.length === 3) { h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2]; }
            return [parseInt(h.substr(0, 2), 16), parseInt(h.substr(2, 2), 16), parseInt(h.substr(4, 2), 16)];
        }
    })();

    var status = null;
    var statusText = null;
    var retry = null;

    function initChrome() {
        status = document.getElementById('status');
        statusText = document.getElementById('statusText');
        retry = document.getElementById('retry');
        if (!status || !retry) { return; }

        paint();

        retry.addEventListener('click', function () {
            if (navigator.onLine) { go(); return; }
            paint();
        });

        // بازگشت خودکار فقط با رویداد واقعیِ «آنلاین شدن» (نه لود صفحه) — ضد-حلقه
        window.addEventListener('online', function () {
            statusText.textContent = 'آنلاین شد — در حال بازگشت…';
            setTimeout(go, 600);
        });
        window.addEventListener('offline', paint);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChrome);
    } else {
        initChrome();
    }

    function paint() {
        var on = navigator.onLine;
        status.classList.toggle('on', on);
        statusText.textContent = on
            ? 'اتصال برقرار است — «تلاش دوباره» را بزنید'
            : 'آفلاین';
    }

    function go() {
        if (!navigator.onLine) { return; }
        retry.disabled = true;
        retry.classList.add('spin');
        // اگر صفحه از مسیری آفلاین شده بود، به همان مسیر برگرد؛ وگرنه خانه
        var back = location.search.match(/[?&]to=([^&]+)/);
        location.replace(back ? decodeURIComponent(back[1]) : '/');
    }
})();
