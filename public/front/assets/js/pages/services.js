/* اپ مشتری — صفحهٔ «خدمات» (همهٔ خدمات با دسته‌بندی‌ها، گروهی) */
/* [Task 9] Vanilla JS — بدون جی‌کوئری */
/* global CN */
(function () {
    'use strict';

    if (!CN.requireCompleteProfile()) { return; }

    /*
     * ساختار داده: درخت دسته‌ها + خدماتِ مستقیمِ هر دسته (servicesGrouped API).
     * حالت «همه» → هر دسته و هر زیردسته سکشن جدا با خدمات خودش.
     * انتخاب دسته → فقط سکشن‌های همان شاخه.
     * جستجو → خدمات منطبق از همهٔ سکشن‌ها (سکشن بدون خدمت مخفی می‌شود).
     */
    var state = {
        categoryId: 0,
        q: ''
    };

    var tree = [];

    /* ---------- بارگذاری ---------- */
    function load() {
        var groupedList = document.getElementById('groupedList');
        if (groupedList) {
            groupedList.innerHTML = '<div class="skeleton svc"></div><div class="skeleton svc"></div><div class="skeleton svc"></div>';
        }

        CN.api('/services-grouped', {
            success: function (resp) {
                tree = (resp.data && resp.data.categories) || [];
                var total = (resp.data && resp.data.total_services) || 0;
                var servicesCount = document.getElementById('servicesCount');
                if (servicesCount) { servicesCount.textContent = CN.toFaDigits(total) + ' خدمت'; }
                renderChips();
                render();
                applyLandingHash();
            },
            error: function () {
                var list = document.getElementById('groupedList');
                if (list) { list.innerHTML = ''; }
                var servicesEmpty = document.getElementById('servicesEmpty');
                if (servicesEmpty) { servicesEmpty.classList.remove('hidden'); }
            }
        });
    }

    /* ---------- چیپ‌های دسته ---------- */
    function renderChips() {
        var row = document.getElementById('categoryChips');
        var html = '<button class="chip' + (state.categoryId === 0 ? ' active' : '') + '" data-cat="0" type="button" role="tab" aria-selected="' + (state.categoryId === 0) + '"><span class="chip-icon">✨</span> همه</button>';

        tree.forEach(function (c) {
            var isActive = state.categoryId === c.id;
            html += '<button class="chip' + (isActive ? ' active' : '') + '" data-cat="' + c.id + '" type="button" role="tab" aria-selected="' + isActive + '">' +
                '<span class="chip-icon">' + CN.esc(c.icon) + '</span> ' + CN.esc(c.name) +
                (c.services_count ? ' <span class="chip-count">' + CN.toFaDigits(c.services_count) + '</span>' : '') +
                '</button>';
        });

        if (row) {
            row.innerHTML = html;
            /* [Task 9] حذف aria-selected از ردیف (مقدار null در ستر قدیمی یعنی حذف attribute) */
            row.removeAttribute('aria-selected');
        }

        /* [Task 9] جایگزین اتصالِ delegate تکراری روی ردیف چیپ‌ها:
           شنونده یک‌بار در بخش رویدادها به #categoryChips بسته می‌شود
           و با بازرندر چیپ‌ها پابرجاست (رفتار عین قبل: هر لحظه فقط یک شنونده). */
    }

    /* ---------- رندر سکشن‌ها ---------- */
    function matchesQuery(s) {
        if (!state.q) { return true; }
        var name = (s.name || '').toLowerCase();
        var desc = (s.description || '').toLowerCase();
        var cat = (s.category && s.category.name || '').toLowerCase();
        return name.indexOf(state.q) !== -1 || desc.indexOf(state.q) !== -1 || cat.indexOf(state.q) !== -1;
    }

    /* آیا خود دسته یا نوادگانش خدمتِ منطبق دارند؟ */
    function hasContent(cat) {
        if ((cat.services || []).some(matchesQuery)) { return true; }
        return (cat.children || []).some(hasContent);
    }

    /* سکشن یک دسته: هدر گروه + خدماتِ مستقیم خودش (بدون توارث) */
    function sectionFor(cat, level) {
        var services = (cat.services || []).filter(matchesQuery);
        var childrenContent = (cat.children || []).some(hasContent);
        var isTarget = state.categoryId && state.categoryId === cat.id;

        // بدون خدمت و بدون فرزندِ دارای خدمت → مخفی (مگر خودش انتخاب شده باشد)
        if (!services.length && !childrenContent && !isTarget) { return ''; }

        var head = '<div class="cat-section fade-up" data-cat="' + cat.id + '">' +
            '<div class="cat-head">' +
            '<span class="cat-ico' + (level > 0 ? ' sub' : '') + '">' + CN.esc(cat.icon || '📁') + '</span>' +
            '<h3 class="cat-name">' + CN.esc(cat.name) + '</h3>' +
            (level > 0 ? '<span class="cat-sub-badge">زیردسته</span>' : '') +
            (services.length ? '<span class="cat-total">' + CN.toFaDigits(services.length) + '</span>' : '') +
            '</div>';

        if (services.length) {
            var body = '<div class="cat-services">';
            services.forEach(function (s) {
                body += serviceCard(s);
            });
            return head + body + '</div></div>';
        }

        // دستهٔ انتخاب‌شدهٔ بدون هیچ خدمتی (خود و فرزندان) → پیام؛ وگرنه هدر گروه
        return (isTarget && !childrenContent)
            ? head + '<p class="cat-empty">خدمتی برای این دسته ثبت نشده است.</p></div>'
            : head + '</div>';
    }

    /* پیمایش درخت: هر دسته و هر زیردسته، سکشن جدا */
    function render() {
        var list = document.getElementById('groupedList');
        var html = '';

        (state.categoryId ? [findCategory(tree, state.categoryId)].filter(Boolean) : tree).forEach(function (cat) {
            html += sectionFor(cat, 0);

            (cat.children || []).forEach(function (child) {
                html += sectionFor(child, 1);
            });
        });

        if (list) { list.innerHTML = html; }

        var totalShown = list ? list.querySelectorAll('.service-card').length : 0;
        var servicesEmpty = document.getElementById('servicesEmpty');
        if (servicesEmpty) { servicesEmpty.classList.toggle('hidden', totalShown > 0); }
        if (list) { list.classList.toggle('hidden', totalShown === 0); }

        var active = findCategory(tree, state.categoryId);
        var servicesTitle = document.getElementById('servicesTitle');
        if (servicesTitle) {
            servicesTitle.textContent = state.q ? 'نتایج جستجو' : (active ? active.name : 'همهٔ دسته‌بندی‌ها');
        }
    }

    function findCategory(nodes, id) {
        for (var i = 0; i < (nodes || []).length; i++) {
            if (nodes[i].id === id) { return nodes[i]; }
            var found = findCategory(nodes[i].children || [], id);
            if (found) { return found; }
        }
        return null;
    }

    /* کارت خدمت (هم‌ساخت صفحهٔ خانه) — فاز ۱۵: تصویر + وضعیت قطع/انقضا + آلرت */
    function serviceCard(s) {
        var icon = (s.category && s.category.icon) || '📄';
        var catName = (s.category && s.category.name) || '';

        var blocked = s.availability_state === 'unavailable' || s.availability_state === 'expired';
        var flags = '';
        if (s.availability_state === 'unavailable') {
            flags += '<span class="svc-flag svc-flag--unavailable">قطع موقت</span>';
        } else if (s.availability_state === 'expired') {
            flags += '<span class="svc-flag svc-flag--expired">مهلت تمام شد</span>';
        } else if (s.has_alert) {
            flags += '<span class="svc-flag svc-flag--alert">اطلاعیه</span>';
        }

        var iconHtml = s.image_url
            ? '<span class="svc-thumb"><img src="' + CN.esc(s.image_url) + '" alt="' + CN.esc(s.name) + '"></span>'
            : '<span class="svc-icon">' + CN.esc(icon) + '</span>';

        return '<a class="service-card' + (blocked ? ' is-blocked' : '') + '" href="' + CN.withPort('/app/service/' + s.id) + '">' +
            flags +
            iconHtml +
            '<span class="svc-body">' +
            '<span class="svc-name">' + CN.esc(s.name) + '</span>' +
            '<span class="svc-meta">' +
            (catName ? '<span>🏷 ' + CN.esc(catName) + '</span>' : '') +
            (s.estimated_time_label && s.estimated_time_label !== '—' ? '<span>⏱ ' + CN.esc(s.estimated_time_label) + '</span>' : '') +
            (s.expires_at_label && !blocked ? '<span>⏳ مهلت: ' + CN.esc(s.expires_at_label) + '</span>' : '') +
            '</span>' +
            '</span>' +
            '<span class="svc-price">' +
            '<span class="amount">' + CN.faMoney(s.total_amount) + '</span>' +
            '<span class="unit">تومان</span>' +
            '</span>' +
            '</a>';
    }

    /* ---------- جستجو ---------- */
    var onSearch = CN.debounce(function () {
        var searchInput = document.getElementById('searchInput');
        state.q = (searchInput ? searchInput.value : '').trim().toLowerCase();
        render();
    }, 420);

    var searchInput = document.getElementById('searchInput');
    if (searchInput) { searchInput.addEventListener('input', onSearch); }

    /* کلیک چیپ‌های دسته — [Task 9] delegate یک‌بار روی #categoryChips (جایگزین off/on تکراری) */
    var categoryChipsRow = document.getElementById('categoryChips');
    if (categoryChipsRow) {
        categoryChipsRow.addEventListener('click', function (e) {
            var chip = e.target.closest ? e.target.closest('.chip') : null;
            if (!chip || !categoryChipsRow.contains(chip)) { return; }

            state.categoryId = +chip.dataset.cat;
            renderChips();
            render();
        });
    }

    /* ---------- لینک‌های ورودی از صفحه فرود (#cat-{id}) ---------- */
    function applyLandingHash() {
        var m = /^#cat-(\d+)$/.exec(window.location.hash || '');
        if (!m) { return; }
        var catId = +m[1];

        // انتخاب دستهٔ مربوطه (خودِ دسته یا والدش اگر زیردسته باشد)
        var target = catId;
        tree.forEach(function (c) {
            (c.children || []).forEach(function (s) {
                if (s.id === catId) { target = c.id; }
            });
        });

        state.categoryId = target;
        renderChips();
        render();
        window.setTimeout(function () {
            /* [Task 9] اولین تطبیق در ترتیب سند (همان first قدیمی)
               چیپ‌های #categoryChips قبل از #groupedList اند — عین رفتار قبلی */
            var sec = document.querySelector('[data-cat="' + catId + '"]');
            if (sec) {
                /* [Task 9] $sec.offset().top → فاصله از بالای سند */
                var top = sec.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop) - 70;
                window.scrollTo({ top: top, behavior: 'smooth' }); /* معادل animate({scrollTop}) با ۵۰۰ms */
            }
        }, 350);
    }

    /* ---------- شروع ---------- */
    load();
})();
