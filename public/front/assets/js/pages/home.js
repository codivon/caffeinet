/* اپ مشتری — صفحه خانه (کاتالوگ) */
/* [Task 9] Vanilla JS — بدون جی‌کوئری */
/* global CN */
(function () {
    'use strict';

    if (!CN.requireCompleteProfile()) { return; }

    var state = {
        q: '',
        categoryId: 0,
        page: 1,
        hasMore: false,
        loading: false
    };

    var categories = []; // flat: {id, name, icon, parent_id, children: []}

    /* ---------- دسته‌بندی‌ها ---------- */
    function loadCategories() {
        CN.api('/categories/tree', {
            success: function (resp) {
                categories = flatten(resp.data || []);
                renderChips();
            }
        });
    }

    function flatten(nodes, parent) {
        var out = [];
        (nodes || []).forEach(function (n) {
            out.push({
                id: n.id,
                name: n.name,
                icon: n.icon,
                parent_id: n.parent_id || 0,
                children: (n.children || []).map(function (c) { return c.id; })
            });
            out = out.concat(flatten(n.children || [], n.id));
        });
        return out;
    }

    function renderChips() {
        var row = document.getElementById('categoryChips');
        var html = '<button class="chip' + (state.categoryId === 0 ? ' active' : '') + '" data-cat="0" type="button"><span class="chip-icon">✨</span> همه</button>';

        // اگر دسته فعالِ فرزند است → ریشه + خواهر/برادرها؛ وگرنه ریشه‌ها
        var active = categories.filter(function (c) { return c.id === state.categoryId; })[0];
        var parentId = active ? (active.parent_id || 0) : 0;

        categories
            .filter(function (c) { return (c.parent_id || 0) === parentId; })
            .forEach(function (c) {
                var isActive = state.categoryId === c.id;
                html += '<button class="chip' + (isActive ? ' active' : '') + '" data-cat="' + c.id + '" type="button">' +
                    '<span class="chip-icon">' + CN.esc(c.icon) + '</span> ' + CN.esc(c.name) +
                    '</button>';
            });

        // اگر ریشه انتخاب شده و فرزند دارد → زیر-دسته‌ها
        if (active && active.children.length) {
            html += '<span style="width:14px;flex:none"></span>';
            active.children.forEach(function (childId) {
                var child = categories.filter(function (c) { return c.id === childId; })[0];
                if (!child) { return; }
                html += '<button class="chip' + (state.categoryId === child.id ? ' active' : '') + '" data-cat="' + child.id + '" type="button">' +
                    '<span class="chip-icon">↳</span> ' + CN.esc(child.name) +
                    '</button>';
            });
        }

        if (row) { row.innerHTML = html; }

        /* [Task 9] جایگزین اتصالِ delegate تکراری روی ردیف چیپ‌ها:
           شنونده یک‌بار در بخش رویدادها به #categoryChips بسته می‌شود
           و با بازرندر چیپ‌ها پابرجاست (رفتار عین قبل: هر لحظه فقط یک شنونده). */
    }

    /* ---------- خدمات ---------- */
    function resetAndLoad() {
        state.page = 1;
        state.hasMore = false;
        var servicesList = document.getElementById('servicesList');
        if (servicesList) { servicesList.innerHTML = ''; }
        var servicesEmpty = document.getElementById('servicesEmpty');
        if (servicesEmpty) { servicesEmpty.classList.add('hidden'); }
        loadServices();
    }

    /* [Task 9] حذف همهٔ اسکلت‌ها از کل سند (جایگزین remove سراسری قدیمی) */
    function removeSkeletons() {
        Array.prototype.forEach.call(document.querySelectorAll('.skeleton'), function (sk) {
            sk.remove();
        });
    }

    function loadServices() {
        if (state.loading) { return; }
        state.loading = true;

        if (state.page === 1) {
            var servicesList = document.getElementById('servicesList');
            if (servicesList) {
                servicesList.insertAdjacentHTML('beforeend', '<div class="skeleton svc"></div><div class="skeleton svc"></div>');
            }
        } else {
            var servicesMoreLoader = document.getElementById('servicesMoreLoader');
            if (servicesMoreLoader) { servicesMoreLoader.classList.remove('hidden'); }
        }

        var params = '?page=' + state.page;
        if (state.q) { params += '&q=' + encodeURIComponent(state.q); }
        if (state.categoryId) { params += '&category=' + state.categoryId; }

        CN.api('/services' + params, {
            success: function (resp) {
                state.loading = false;
                removeSkeletons();
                var servicesMoreLoader = document.getElementById('servicesMoreLoader');
                if (servicesMoreLoader) { servicesMoreLoader.classList.add('hidden'); }

                var servicesCount = document.getElementById('servicesCount');
                if (servicesCount) { servicesCount.textContent = CN.toFaDigits(resp.total || 0) + ' خدمت'; }

                var servicesList = document.getElementById('servicesList');
                (resp.data || []).forEach(function (s) {
                    if (servicesList) { servicesList.insertAdjacentHTML('beforeend', serviceCard(s)); }
                });

                state.hasMore = !!resp.next_page_url;
                var loadMoreBtn = document.getElementById('loadMoreBtn');
                if (loadMoreBtn) { loadMoreBtn.classList.toggle('hidden', !state.hasMore); }

                if (!resp.data || !resp.data.length) {
                    var servicesEmpty = document.getElementById('servicesEmpty');
                    if (servicesEmpty) { servicesEmpty.classList.remove('hidden'); }
                }

                loadFeatured(resp.data || []);
            },
            error: function () {
                state.loading = false;
                removeSkeletons();
                var servicesMoreLoader = document.getElementById('servicesMoreLoader');
                if (servicesMoreLoader) { servicesMoreLoader.classList.add('hidden'); }
            }
        });
    }

    function serviceCard(s) {
        var icon = (s.category && s.category.icon) || '📄';
        var catName = (s.category && s.category.name) || '';

        return '<a class="service-card" href="' + CN.withPort('/app/service/' + s.id) + '">' +
            '<span class="svc-icon">' + CN.esc(icon) + '</span>' +
            '<span class="svc-body">' +
            '<span class="svc-name">' + CN.esc(s.name) + '</span>' +
            '<span class="svc-meta">' +
            (catName ? '<span>🏷 ' + CN.esc(catName) + '</span>' : '') +
            (s.estimated_time_label && s.estimated_time_label !== '—' ? '<span>⏱ ' + CN.esc(s.estimated_time_label) + '</span>' : '') +
            '</span>' +
            '</span>' +
            '<span class="svc-price">' +
            '<span class="amount">' + CN.faMoney(s.total_amount) + '</span>' +
            '<span class="unit">تومان</span>' +
            '</span>' +
            '</a>';
    }

    /* ---------- ویژه‌ها (از صفحه اول بدون فیلتر) ---------- */
    var featuredLoaded = false;

    function loadFeatured(firstPage) {
        if (featuredLoaded || state.q || state.categoryId || state.page !== 1) { return; }
        featuredLoaded = true;

        var featured = firstPage.filter(function (s) { return s.is_featured; });
        if (!featured.length) { return; }

        var html = '';
        featured.slice(0, 6).forEach(function (s) {
            /* فاز ۴۷ — طبق درخواست مالک: داخل کارت ویژه «فقط عنوان» —
               آیکون دسته و ستارهٔ گوشهٔ کارت هم حذف شد. */
            html += '<a class="featured-card" href="' + CN.withPort('/app/service/' + s.id) + '">' +
                '<div class="f-name">' + CN.esc(s.name) + '</div>' +
                '</a>';
        });

        var featuredStrip = document.getElementById('featuredStrip');
        if (featuredStrip) { featuredStrip.innerHTML = html; }
        var featuredSection = document.getElementById('featuredSection');
        if (featuredSection) { featuredSection.classList.remove('hidden'); }
    }

    /* ---------- رویدادها ---------- */
    var onSearch = CN.debounce(function () {
        var searchInput = document.getElementById('searchInput');
        state.q = (searchInput ? searchInput.value : '').trim();
        resetAndLoad();
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
            resetAndLoad();
        });
    }

    var loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            if (!state.hasMore || state.loading) { return; }
            state.page++;
            loadServices();
        });
    }

    /* ---------- شروع ---------- */
    loadCategories();
    loadServices();
})();
