/**
 * کافی‌نت آنلاین — اسکریپت مشترک لایه پنل‌های مدیریت
 * (سایدبار موبایل + خروج AJAX + بج گفتگوهای ناخوانده) — مشترک بین پنل‌ها
 *
 * مسیرها از data-attribute روی <body> خوانده می‌شوند:
 *   <body data-logout-url="/admin/logout" data-login-url="/admin/login"
 *         data-chat-badge-url="/operator/chat/badge">   ← فقط پنل اپراتور (فاز ۷)
 *
 * [Task 2-a — Livewire SPA]
 *  • کل فایل با data-navigate-once لینک می‌شود (فقط یک بار per page-load اجرا می‌شود).
 *  • بخش‌های وابسته به DOM (تاگل سایدبار، خروج، اسکرول منوی فعال) در App.onNavigate
 *    ثبت می‌شوند تا بعد از هر ناوبری wire:navigate با DOM تازه دوباره bind شوند.
 *  • پولینگ بج‌ها فقط یک بار per page-load شروع می‌شود (المان‌های داخل سایدبار
 *    با @persist('sidebar') بین ناوبری‌ها زنده می‌مانند).
 */
(function () {
    if (typeof window.App === 'undefined') { return; }

    /* ---------- منوی موبایل ----------
       sidebar با @persist بین ناوبری‌ها حفظ می‌شود؛ overlay و toggle با هر ناوبری
       DOM تازه دارند — پس هر بار از نو query می‌شوند (Task 2-a). */
    let sidebar = document.getElementById('panel-sidebar');
    let overlay = document.getElementById('sidebar-overlay');

    function requeryChrome() {
        sidebar = document.getElementById('panel-sidebar');
        overlay = document.getElementById('sidebar-overlay');
    }

    function openSidebar() {
        sidebar?.classList.remove('translate-x-full');
        overlay?.classList.remove('hidden');
    }
    function closeSidebar() {
        sidebar?.classList.add('translate-x-full');
        overlay?.classList.add('hidden');
    }

    /* ---------- اسکرول سایدبار به منوی فعال ----------
       وقتی صفحه‌ای باز می‌شود، منوی فعال (اگر پایین‌تر از دید باشد)
       در مرکز ناحیهٔ ناوبری قرار می‌گیرد (فقط اسکرول داخلی nav،
       بدون جابجایی خود صفحه). */
    function scrollToActiveNav() {
        const nav = sidebar?.querySelector('nav');
        if (!nav) return;
        const active = nav.querySelector('a.is-active, a.no-nav-link--on, a.op-nav-active');
        if (!active) return;
        const target = active.offsetTop - (nav.clientHeight / 2) + (active.offsetHeight / 2);
        if (target > 0) nav.scrollTop = target;
    }

    /* ---------- برچسب‌گذاری آیتم فعال منو بعد از ناوبری SPA ----------
       سایدبار با @persist بین ناوبری‌ها حفظ می‌شود و کلاسِ فعال سروری
       کهنه می‌مانَد؛ اینجا با تطابق مسیر جاری، آیتم فعال را تازه می‌کنیم. */
    const NAV_ACTIVE_CLASSES = ['is-active', 'no-nav-link--on', 'op-nav-active'];

    function refreshActiveNav() {
        const nav = sidebar?.querySelector('nav');
        if (!nav) return;

        const path = window.location.pathname.replace(/\/+$/, '') || '/';
        let best = null;
        let bestLen = -1;

        nav.querySelectorAll('a[href]').forEach((a) => {
            NAV_ACTIVE_CLASSES.forEach((c) => a.classList.remove(c));
        });

        nav.querySelectorAll('a[href]').forEach((a) => {
            let href;
            try {
                href = new URL(a.href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
            } catch (e) { return; }
            const isExact = href === path;
            const isPrefix = path.startsWith(href + '/');
            if (!isExact && !isPrefix) return;
            if (href.length > bestLen) { best = a; bestLen = href.length; }
        });

        if (best) {
            NAV_ACTIVE_CLASSES.forEach((c) => best.classList.add(c));
            best.setAttribute('aria-current', 'page');
        }
    }

    /* ---------- اتصال کروم به DOM تازه (هر ناوبری) ---------- */
    App.onNavigate(function bindChrome() {
        requeryChrome();

        const toggle = document.getElementById('sidebar-toggle');

        toggle?.addEventListener('click', () => {
            sidebar?.classList.contains('translate-x-full') ? openSidebar() : closeSidebar();
        });
        overlay?.addEventListener('click', closeSidebar);

        // بعد از ناوبری، سایدبار موبایل بسته باشد (حالت پیش‌فرض)
        closeSidebar();

        refreshActiveNav();
        scrollToActiveNav();

        /* ---------- خروج (AJAX) با مودال زیبا ---------- */
        document.querySelectorAll('.logout-btn').forEach((btn) => {
            // سایدبار با @persist حفظ می‌شود؛ همان دکمهٔ قبلی است — bind تکراری ممنوع
            if (btn.dataset.logoutBound === '1') { return; }
            btn.dataset.logoutBound = '1';
            btn.addEventListener('click', () => {
                if (window.PanelUI) {
                    window.PanelUI.confirm(
                        {
                            title: 'خروج از حساب',
                            desc: 'آیا مطمئن هستید که می‌خواهید از پنل خارج شوید؟',
                            okText: 'خروج از حساب',
                            danger: true,
                            icon: 'question'
                        },
                        doLogout
                    );
                } else {
                    doLogout();
                }
            });
        });
    });

    async function doLogout() {
        const logoutUrl = document.body.dataset.logoutUrl || '/admin/logout';
        const loginUrl = document.body.dataset.loginUrl || '/admin/login';
        try {
            const res = await App.ajax(logoutUrl, { method: 'POST' });
            const data = await res.json().catch(() => ({}));
            window.location = data.redirect || loginUrl;
        } catch {
            window.location = loginUrl;
        }
    }

    /* ---------- بج گفتگوهای ناخوانده (فاز ۷ — فقط پنل اپراتور) ----------
       یک بار per page-load شروع می‌شود؛ badge داخل سایدبار persist شده است. */
    const badgeUrl = document.body.dataset.chatBadgeUrl;
    const badgeEl = document.getElementById('chatUnreadBadge');

    if (badgeUrl && badgeEl && typeof App !== 'undefined') {
        async function refreshBadge() {
            if (document.hidden) return; // تب مخفی — نیازی نیست
            try {
                const res = await App.ajax(badgeUrl);
                if (!res.ok) return;
                const data = await res.json();
                renderBadge(data.unseen || 0);
            } catch { /* بی‌صدا */ }
        }

        function renderBadge(count) {
            if (count > 0) {
                badgeEl.textContent = count > 99 ? '۹۹+' : fa(count);
                badgeEl.classList.add('on');
            } else {
                badgeEl.textContent = '';
                badgeEl.classList.remove('on');
            }
        }

        function fa(n) {
            return String(n).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
        }

        // رویداد سراسری: صفحهٔ چت بعد از هر پولینگ بج را هم تازه می‌کند
        window.addEventListener('chat:unseen', (e) => renderBadge(e.detail || 0));

        /* v38 «پوشر کامل»: پوشر فعال و متصل → بدون setInterval؛
           رویداد notif.new (پیام جدید هم اعلان می‌سازد) بج را لحظه‌ای تازه می‌کند.
           قطع اتصال → پولینگ اضطراری؛ وصل شدن → توقف پولینگ. */
        refreshBadge();
        let badgeTimer = setInterval(refreshBadge, 20000);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshBadge();
        });

        if (window.RT && RT.active() && RT.cfg.channel) {
            RT.bindUser('notif.new', () => {
                if (!document.hidden) refreshBadge();
            });

            if (RT.connected()) { clearInterval(badgeTimer); badgeTimer = null; }

            RT.onConnection((up) => {
                if (up) {
                    if (badgeTimer) { clearInterval(badgeTimer); badgeTimer = null; }
                    refreshBadge();
                } else if (!badgeTimer) {
                    badgeTimer = setInterval(refreshBadge, 20000);
                }
            });
        }
    }

    /* ---------- بج درخواست‌های در انتظار پذیرش (فاز ۱۱ — پنل اپراتور) ----------
       یک بار per page-load — المان‌ها داخل سایدبار persist شده‌اند. */
    const reqBadgeUrl = document.body.dataset.requestsBadgeUrl;
    const reqBadgeEl = document.getElementById('requestsCountBadge');
    const reqPulseEl = document.getElementById('requestsPulseDot');

    if (reqBadgeUrl && (reqBadgeEl || reqPulseEl) && typeof App !== 'undefined') {
        async function refreshRequestsBadge() {
            if (document.hidden) return;
            try {
                const res = await App.ajax(reqBadgeUrl);
                if (!res.ok) return;
                const data = await res.json();
                renderRequestsBadge(data.count || 0);
            } catch { /* بی‌صدا */ }
        }

        function renderRequestsBadge(count) {
            if (reqBadgeEl) {
                if (count > 0) {
                    reqBadgeEl.textContent = count > 99 ? '۹۹+' : faReq(count);
                    reqBadgeEl.classList.add('on');
                } else {
                    reqBadgeEl.textContent = '';
                    reqBadgeEl.classList.remove('on');
                }
            }
            if (reqPulseEl) {
                reqPulseEl.classList.toggle('hidden', !(count > 0));
            }
            window.dispatchEvent(new CustomEvent('requests:count', { detail: count || 0 }));
        }

        function faReq(n) {
            return String(n).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
        }

        /* v38 «پوشر کامل»: پوشر فعال و متصل → بدون setInterval؛
           رویداد orders.changed روی کانال سراسری پنل‌ها (سفارش جدید/پخش/پذیرش)
           بج درخواست‌ها را لحظه‌ای تازه می‌کند. قطع اتصال → پولینگ اضطراری. */
        refreshRequestsBadge();
        let reqTimer = setInterval(refreshRequestsBadge, 15000);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshRequestsBadge();
        });

        if (window.RT && RT.active() && RT.cfg.panel_channel) {
            RT.on(RT.cfg.panel_channel, 'orders.changed', () => {
                if (!document.hidden) refreshRequestsBadge();
            });

            if (RT.connected()) { clearInterval(reqTimer); reqTimer = null; }

            RT.onConnection((up) => {
                if (up) {
                    if (reqTimer) { clearInterval(reqTimer); reqTimer = null; }
                    refreshRequestsBadge();
                } else if (!reqTimer) {
                    reqTimer = setInterval(refreshRequestsBadge, 15000);
                }
            });
        }
    }
})();
