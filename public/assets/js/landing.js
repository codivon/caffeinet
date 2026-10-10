/* ============================================================
   کافی‌نت آنلاین — اسکریپت صفحه فرود
   (Vanilla JS — سبک و مستقل)
   [Task 9] دسترسی مستقیم DOM — بدون هیچ شورت‌کات کمکی
   ------------------------------------------------------------
   [Task 8] سازگاری SPA (wire:navigate):
   • شنونده‌های سراسری (scroll/keydown/click-بیرون) فقط یک‌بار ثبت
     می‌شوند و عناصر را هر بار «تنبل» از DOM تازه می‌خوانند.
   • اتصال‌های وابسته به محتوا (Reveal/شمارنده/آکاردئون/لینک‌های
     داخلی) در init() هستند — هم بار اول و هم بعد از هر ناوبری
     (livewire:navigated) چون DOM محتوا از نو جایگذاری می‌شود.
   [v42] منوی همبرگری حذف شد → نوار چیپ‌های افقی موبایل (بدون JS).
   ============================================================ */
(function () {
    'use strict';

    var doc = document;

    /* ---------- فعال‌بودن JS (برای reveal تدریجی) ---------- */
    doc.documentElement.classList.add('js');

    /* ---------- هدر: سایه هنگام اسکرول — سراسری و تنبل ---------- */
    function onScroll() {
        var header = doc.getElementById('siteHeader');
        var toTop = doc.getElementById('toTop');
        var y = window.scrollY || window.pageYOffset || 0;
        if (header) header.classList.toggle('is-scrolled', y > 12);
        if (toTop) toTop.classList.toggle('is-visible', y > 620);
    }
    window.addEventListener('scroll', onScroll, { passive: true });

    /* ---------- دکمه بازگشت به بالا — سراسری و تنبل ---------- */
    doc.addEventListener('click', function (e) {
        var toTop = e.target && e.target.closest ? e.target.closest('#toTop') : null;
        if (toTop) { window.scrollTo({ top: 0, behavior: 'smooth' }); }
    });

    /* v42 — ذخیرهٔ پوستهٔ پالت لندینگ برای صفحهٔ آفلاین */
    try {
        var apEl = document.getElementById('appearance-css');
        if (apEl) { localStorage.setItem('cn-appearance-css:front', apEl.textContent); }
    } catch (err) { /* noop */ }

    /* ---------- کلید روز/شب — سراسری و تنبل ----------
       پیش‌فرض: روز؛ انتخاب کاربر در localStorage «caffeinet-theme» (مشترک با پنل‌ها) */
    doc.addEventListener('click', function (e) {
        var t = e.target && e.target.closest ? e.target.closest('#themeToggle') : null;
        if (!t) { return; }

        var isDark = doc.documentElement.classList.toggle('dark');

        try { localStorage.setItem('caffeinet-theme', isDark ? 'dark' : 'light'); } catch (err) { /* noop */ }
        if (window.CNThemeCookie) { window.CNThemeCookie(isDark ? 'dark' : 'light'); } /* v42 — کوکی تم */

        t.setAttribute('aria-label', isDark ? 'رفتن به حالت روز' : 'رفتن به حالت شب');

        var meta = doc.querySelector('meta[name="theme-color"]');
        if (meta) { meta.setAttribute('content', isDark ? '#1a1a2e' : '#f3f6fc'); }
    });

    /* ---------- انیمیشن ورود (IntersectionObserver) ---------- */
    function initReveals() {
        var reveals = doc.querySelectorAll('.reveal');

        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -36px 0px' });

            reveals.forEach(function (el) { io.observe(el); });

            // اطمینان: هر چیزی بعد از ۲.۲ ثانیه هنوز مخفی ماند (IO خراب/فول‌پیج) مرئی شود
            window.setTimeout(function () {
                reveals.forEach(function (el) { el.classList.add('is-in'); });
            }, 2200);
        } else {
            reveals.forEach(function (el) { el.classList.add('is-in'); });
        }
    }

    /* ---------- شمارندهٔ عددی آمار ---------- */
    function faDigits(n) {
        return String(n).replace(/\d/g, function (d) {
            return '۰۱۲۳۴۵۶۷۸۹'[+d];
        });
    }

    function animateCount(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (target <= 0) { el.textContent = faDigits(0); return; }

        var duration = 1600;
        var start = null;

        function frame(ts) {
            if (start === null) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            // easeOutCubic
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = faDigits(Math.max(1, Math.round(target * eased)));
            if (p < 1) window.requestAnimationFrame(frame);
            else el.textContent = faDigits(target);
        }

        window.requestAnimationFrame(frame);
    }

    function initCounters() {
        var counters = doc.querySelectorAll('.stat-num[data-count]');

        if (counters.length && 'IntersectionObserver' in window) {
            var cio = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        animateCount(entry.target);
                        cio.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });

            counters.forEach(function (el) { cio.observe(el); });
        } else {
            counters.forEach(function (el) {
                el.textContent = faDigits(el.getAttribute('data-count') || 0);
            });
        }
    }

    /* ---------- init محتوا — بار اول + بعد از هر ناوبری SPA ---------- */
    function init() {
        onScroll();

        initReveals();
        initCounters();

        /* سوالات متداول (آکاردئون) */
        doc.querySelectorAll('.faq-item').forEach(function (item) {
            var btn = item.querySelector('.faq-q');
            var panel = item.querySelector('.faq-a');
            if (!btn || !panel) return;

            btn.addEventListener('click', function () {
                var isOpen = item.classList.contains('is-open');

                // بستن بقیه (آکاردئون تک‌بازشو)
                doc.querySelectorAll('.faq-item.is-open').forEach(function (other) {
                    if (other === item) return;
                    other.classList.remove('is-open');
                    var p = other.querySelector('.faq-a');
                    var q = other.querySelector('.faq-q');
                    if (p) { p.style.maxHeight = ''; }
                    if (q) { q.setAttribute('aria-expanded', 'false'); }
                });

                item.classList.toggle('is-open', !isOpen);
                btn.setAttribute('aria-expanded', String(!isOpen));
                panel.style.maxHeight = !isOpen ? (panel.scrollHeight + 24) + 'px' : '';
            });
        });

        /* اسکرول نرم برای لینک‌های داخلی (fallback مرورگرهای قدیمی) */
        doc.querySelectorAll('a[href^="#"]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                var id = a.getAttribute('href');
                if (!id || id === '#') return;
                var target = doc.querySelector(id);
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    init();

    /* [Task 8] بعد از هر ناوبری SPA محتوا از نو جایگذاری می‌شود */
    window.addEventListener('livewire:navigated', init);
})();
