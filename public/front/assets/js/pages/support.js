/* اپ مشتری — تیکت‌های پشتیبانی (فاز ۱۰) (Vanilla JS — بدون jQuery)
   global CN */
(function () {
    'use strict';

    if (!CN.requireCompleteProfile()) { return; }

    var state = { status: '', loading: false };

    var STATUS_LABEL = {
        open: 'باز',
        answered: 'پاسخ داده‌شده',
        customer_reply: 'در گفتگو',
        closed: 'بسته'
    };
    var PRIO_LABEL = { low: 'کم', normal: 'معمولی', high: 'فوری' };

    /* فاز ۱۳ — لیست زندهٔ تیکت‌ها: اعلان تیکت (پاسخ کارشناس/وضعیت) با
       notif.new می‌رسد → فهرست بدون پولینگ تازه می‌شود. */
    var rtBound = false;

    function bindRealtime() {
        if (rtBound || !window.RT || !RT.active() || !RT.cfg.channel) { return; }
        var ok = RT.bindUser('notif.new', function (payload) {
            var t = String((payload && payload.type) || '');
            if (t && t.indexOf('ticket') === -1) { return; }
            if (document.hidden) { return; }
            load();
        });
        if (ok) { rtBound = true; }
    }
    bindRealtime();
    setTimeout(bindRealtime, 1500);
    setTimeout(bindRealtime, 4000);

    function load() {
        if (state.loading) { return; }
        state.loading = true;

        var qs = state.status ? ('?status=' + state.status) : '';
        var ticketList = document.getElementById('ticketList');
        if (ticketList) {
            ticketList.innerHTML = '<div class="skeleton" style="height:72px"></div><div class="skeleton" style="height:72px"></div>';
        }

        CN.api('/tickets' + qs, {
            success: function (resp) {
                state.loading = false;
                var rows = (resp && resp.data) || [];
                var listEl = document.getElementById('ticketList');
                var emptyEl = document.getElementById('ticketEmpty');

                if (!rows.length) {
                    if (listEl) { listEl.innerHTML = ''; listEl.classList.add('hidden'); }
                    if (emptyEl) { emptyEl.classList.remove('hidden'); }
                    return;
                }

                if (emptyEl) { emptyEl.classList.add('hidden'); }
                if (listEl) { listEl.classList.remove('hidden'); }

                var html = '';
                rows.forEach(function (t) { html += rowHtml(t); });
                if (listEl) { listEl.innerHTML = html; }
            },
            error: function () {
                state.loading = false;
                var listEl = document.getElementById('ticketList');
                if (listEl) { listEl.innerHTML = '<div class="ns-empty">خطا در دریافت تیکت‌ها.</div>'; }
            }
        });
    }

    function rowHtml(t) {
        return '<a class="sup-row" href="' + CN.withPort('/app/support/' + t.id) + '">' +
            '  <span class="sup-icon" data-status="' + CN.esc(t.status) + '">' +
            '    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Z"/><path d="M18 11h3v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2Z"/><path d="M21 11a9 9 0 0 0-18 0"/></svg>' +
            '  </span>' +
            '  <span class="sup-body">' +
            '    <span class="sup-top">' +
            '      <strong class="sup-subject">' + CN.esc(t.subject) + '</strong>' +
            '      <span class="sup-badge" data-status="' + CN.esc(t.status) + '">' + CN.esc(STATUS_LABEL[t.status] || t.status) + '</span>' +
            '    </span>' +
            '    <span class="sup-last">' + CN.esc(t.last_message || '—') + '</span>' +
            '    <span class="sup-meta">' +
            '      <span class="sup-num" dir="ltr">' + CN.esc(t.ticket_number) + '</span>' +
            (t.order_number ? ' · <span dir="ltr">' + CN.esc(t.order_number) + '</span>' : '') +
            (t.priority === 'high' ? ' · <b class="sup-prio">فوری</b>' : '') +
            '      · ' + CN.esc(t.last_message_at || t.created_at || '') +
            '    </span>' +
            '  </span>' +
            '  <svg class="sup-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>' +
            '</a>';
    }

    /* ---------- فیلتر چیپ‌ها ---------- */
    Array.prototype.forEach.call(document.querySelectorAll('.chip'), function (chip) {
        chip.addEventListener('click', function () {
            Array.prototype.forEach.call(document.querySelectorAll('.chip'), function (c) {
                c.classList.remove('is-on');
                c.setAttribute('aria-selected', 'false');
            });
            chip.classList.add('is-on');
            chip.setAttribute('aria-selected', 'true');
            state.status = chip.dataset.status || '';
            load();
        });
    });

    /* ---------- شیت تیکت جدید ---------- */
    var newSheetBackdrop = document.getElementById('newSheetBackdrop');
    var newSheet = document.getElementById('newSheet');

    function openSheet() {
        if (newSheet) { newSheet.classList.add('open'); }
        if (newSheetBackdrop) { newSheetBackdrop.classList.add('show'); }
        loadOrders();
    }

    function closeSheet() {
        if (newSheet) { newSheet.classList.remove('open'); }
        if (newSheetBackdrop) { newSheetBackdrop.classList.remove('show'); }
        Array.prototype.forEach.call(document.querySelectorAll('#err_subject, #err_message'), function (err) {
            err.textContent = '';
            err.classList.remove('show');
        });
        Array.prototype.forEach.call(document.querySelectorAll('#nt-subject, #nt-message'), function (field) {
            field.value = '';
        });
        var prioritySel = document.getElementById('nt-priority');
        if (prioritySel) { prioritySel.value = 'normal'; }
    }

    if (document.getElementById('btnNewTicket')) {
        document.getElementById('btnNewTicket').addEventListener('click', openSheet);
    }
    if (document.getElementById('closeNewSheet')) {
        document.getElementById('closeNewSheet').addEventListener('click', closeSheet);
    }
    if (newSheetBackdrop) { newSheetBackdrop.addEventListener('click', closeSheet); }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeSheet(); }
    });

    /* سفارش‌های کاربر برای انتخاب مرتبط */
    function loadOrders() {
        var select = document.getElementById('nt-order');
        if (!select || select.dataset.loaded) { return; }

        CN.api('/orders?per_page=30', {
            success: function (resp) {
                select.dataset.loaded = '1';
                var rows = (resp && resp.data) || [];
                var html = '<option value="">بدون سفارش</option>';
                rows.forEach(function (o) {
                    html += '<option value="' + o.id + '">' + CN.esc(o.order_number) + ' — ' + CN.esc(o.status_label || '') + '</option>';
                });
                select.innerHTML = html;
            }
        });
    }

    /* ---------- ثبت ---------- */
    if (document.getElementById('newTicketForm')) {
        document.getElementById('newTicketForm').addEventListener('submit', function (e) {
            e.preventDefault();

            var subjectInput = document.getElementById('nt-subject');
            var messageInput = document.getElementById('nt-message');
            var subject = String(subjectInput ? subjectInput.value : '').trim();
            var message = String(messageInput ? messageInput.value : '').trim();
            var prioritySel = document.getElementById('nt-priority');
            var orderSel = document.getElementById('nt-order');
            var priority = prioritySel ? prioritySel.value : undefined;
            var orderId = orderSel ? orderSel.value : undefined;

            Array.prototype.forEach.call(document.querySelectorAll('#err_subject, #err_message'), function (err) {
                err.textContent = '';
                err.classList.remove('show');
            });

            if (!subject) {
                var errSubject = document.getElementById('err_subject');
                if (errSubject) { errSubject.textContent = 'موضوع الزامی است.'; errSubject.classList.add('show'); }
                return;
            }
            if (!message) {
                var errMessage = document.getElementById('err_message');
                if (errMessage) { errMessage.textContent = 'توضیح مشکل الزامی است.'; errMessage.classList.add('show'); }
                return;
            }

            var submitBtn = document.getElementById('nt-submit');
            CN.btnLoading(submitBtn, true, 'در حال ثبت…');

            CN.api('/tickets', {
                method: 'POST',
                data: {
                    subject: subject,
                    message: message,
                    priority: priority,
                    order_id: orderId ? +orderId : null
                },
                success: function (resp) {
                    CN.btnLoading(submitBtn, false);
                    CN.toast(resp.message || 'تیکت ثبت شد.', 'success');
                    closeSheet();
                    window.location.href = CN.withPort('/app/support/' + resp.data.id);
                },
                error: function (xhr, msg) {
                    CN.btnLoading(submitBtn, false);
                    var respData = null;
                    try { respData = JSON.parse(xhr.responseText); } catch (parseErr) { respData = null; }
                    if (respData && respData.errors) {
                        CN.applyErrors(respData.errors);
                    } else {
                        CN.toast(msg || 'ثبت تیکت ناموفق بود.', 'error');
                    }
                }
            });
        });
    }

    load();
})();
