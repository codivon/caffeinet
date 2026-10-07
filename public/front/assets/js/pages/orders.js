/* اپ مشتری — فهرست سفارش‌ها */
/* [Task 9] Vanilla JS — بدون جی‌کوئری */
/* global CN */
(function () {
    'use strict';

    if (!CN.requireCompleteProfile()) { return; }

    var state = { status: '', page: 1, hasMore: false, loading: false };

    function load() {
        if (state.loading) { return; }
        state.loading = true;

        if (state.page === 1) {
            var listEl = document.getElementById('ordersList');
            if (listEl) {
                listEl.innerHTML = '<div class="skeleton svc"></div><div class="skeleton svc"></div>';
            }
        } else {
            var moreEl = document.getElementById('ordersMoreLoader');
            if (moreEl) { moreEl.classList.remove('hidden'); }
        }

        var params = '?page=' + state.page;
        if (state.status) { params += '&status=' + state.status; }

        CN.api('/orders' + params, {
            success: function (resp) {
                state.loading = false;

                var list = document.getElementById('ordersList');
                if (state.page === 1 && list) { list.innerHTML = ''; }

                var moreLoader = document.getElementById('ordersMoreLoader');
                if (moreLoader) { moreLoader.classList.add('hidden'); }

                (resp.data || []).forEach(function (o) {
                    if (list) { list.insertAdjacentHTML('beforeend', orderCard(o)); }
                });

                state.hasMore = !!resp.next_page_url;
                var loadMore = document.getElementById('ordersLoadMore');
                if (loadMore) { loadMore.classList.toggle('hidden', !state.hasMore); }

                var empty = !(resp.data || []).length;
                var emptyState = document.getElementById('ordersEmpty');
                if (emptyState) { emptyState.classList.toggle('hidden', !empty || state.page > 1); }
                if (list) { list.classList.toggle('hidden', empty); }
            },
            error: function () {
                state.loading = false;
                var moreLoader = document.getElementById('ordersMoreLoader');
                if (moreLoader) { moreLoader.classList.add('hidden'); }
            }
        });
    }

    /* رنگ نوار وضعیت بر اساس استاتوس */
    var STRIP = {
        pending_payment: 'st-amber',
        paid: 'st-green',
        broadcasting: 'st-teal',
        accepted: 'st-green',
        in_progress: 'st-teal',
        needs_info: 'st-orange',
        delivered: 'st-teal',
        completed: 'st-green',
        queued: 'st-stone',
        cancelled: 'st-rose',
        refunded: 'st-rose'
    };

    function orderCard(o) {
        var icon = (o.service && o.service.icon) || '📄';
        var name = (o.service && o.service.name) || 'سفارش ' + CN.esc(o.order_number);
        var strip = STRIP[o.status] || 'st-stone';

        return '<a class="order-cardv2 ' + strip + '" href="' + CN.withPort('/app/orders/' + o.id) + '" aria-label="جزئیات سفارش ' + CN.esc(o.order_number) + '">' +
            '<span class="oc-strip" aria-hidden="true"></span>' +
            '<span class="oc-main">' +
            '<span class="oc-top">' +
            '<span class="oc-icon" aria-hidden="true">' + CN.esc(icon) + '</span>' +
            '<span class="oc-titles">' +
            '<strong class="oc-name">' + CN.esc(name) + '</strong>' +
            '<span class="oc-meta"><span class="num" dir="ltr">' + CN.esc(o.order_number) + '</span> · ' + CN.esc(o.created_at_fa || '') + '</span>' +
            '</span>' +
            CN.statusBadge(o.status, o.status_label) +
            '</span>' +
            '<span class="oc-bottom">' +
            '<span class="oc-amount">' + CN.faMoney(o.total_amount) + ' <small>تومان</small></span>' +
            '<span class="oc-cta">مشاهده جزئیات' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>' +
            '</span>' +
            '</span>' +
            '</span>' +
            '</a>';
    }

    /* فیلتر وضعیت — [Task 9] delegate روی #statusChips (جایگزین اتصال کلاسیک روی چیپ‌ها) */
    var statusChips = document.getElementById('statusChips');
    if (statusChips) {
        statusChips.addEventListener('click', function (e) {
            var chip = e.target.closest ? e.target.closest('.chip') : null;
            if (!chip || !statusChips.contains(chip)) { return; }

            Array.prototype.forEach.call(statusChips.querySelectorAll('.chip'), function (c) {
                c.classList.remove('active');
            });
            chip.classList.add('active');
            state.status = chip.dataset.status || '';
            state.page = 1;
            load();
        });
    }

    var ordersLoadMore = document.getElementById('ordersLoadMore');
    if (ordersLoadMore) {
        ordersLoadMore.addEventListener('click', function () {
            if (!state.hasMore || state.loading) { return; }
            state.page++;
            load();
        });
    }

    load();
})();
