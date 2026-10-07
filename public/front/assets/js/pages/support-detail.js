/* اپ مشتری — گفتگوی تیکت پشتیبانی (فاز ۱۰) (Vanilla JS — بدون jQuery)
   global CN */
(function () {
    'use strict';

    if (!CN.requireCompleteProfile()) { return; }

    var ticketId = 0;
    try {
        var pageDataEl = document.getElementById('page-data');
        ticketId = parseInt((pageDataEl ? pageDataEl.dataset.ticketId : ''), 10) || 0;
    } catch (e) { ticketId = 0; }

    if (!ticketId) {
        window.location.replace(CN.withPort('/app/support'));
        return;
    }

    var state = {
        lastId: 0,
        messages: [],
        sending: false,
        closed: false
    };

    var STATUS_LABEL = {
        open: 'باز',
        answered: 'پاسخ داده‌شده',
        customer_reply: 'در گفتگو',
        closed: 'بسته'
    };
    var PRIO_LABEL = { low: 'کم', normal: 'معمولی', high: 'فوری' };

    /* ---------- هدر تیکت ---------- */
    function renderHead(t) {
        state.closed = t.status === 'closed';

        var tkdHead = document.getElementById('tkdHead');
        if (tkdHead) {
            tkdHead.innerHTML =
                '<div class="tkd-main">' +
                '  <div class="tkd-title-row">' +
                '    <h1>' + CN.esc(t.subject) + '</h1>' +
                '    <span class="sup-badge" data-status="' + CN.esc(t.status) + '" id="tkdStatusBadge">' + CN.esc(STATUS_LABEL[t.status] || t.status) + '</span>' +
                '  </div>' +
                '  <div class="tkd-meta">' +
                '    <span class="sup-num" dir="ltr">' + CN.esc(t.ticket_number) + '</span>' +
                (t.order_number ? ' · سفارش <span dir="ltr">' + CN.esc(t.order_number) + '</span>' : '') +
                (t.priority === 'high' ? ' · <b class="sup-prio">' + CN.esc(PRIO_LABEL.high) + '</b>' : '') +
                '    · ثبت: ' + CN.esc(t.created_at || '') +
                '  </div>' +
                '</div>' +
                '<div class="tkd-actions">' +
                (state.closed
                    ? '<span class="tkd-closed-note">این تیکت بسته شده — با ارسال پیام جدید بازگشایی می‌شود.</span>'
                    : '<button type="button" class="btn btn-outline btn-sm" id="tkdCloseBtn">' +
                      '  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>' +
                      '  بستن تیکت' +
                      '</button>') +
                '</div>';
        }

        var closeBtn = document.getElementById('tkdCloseBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                CN.confirm({
                    title: 'بستن تیکت',
                    desc: 'آیا مطمئنید مشکل شما حل شده است؟',
                    okText: 'بله، ببند'
                }, function () {
                    CN.api('/tickets/' + ticketId + '/close', {
                        method: 'POST',
                        success: function (resp) {
                            CN.toast(resp.message || 'تیکت بسته شد.', 'success');
                            load();
                        }
                    });
                });
            });
        }
    }

    /* ---------- پیام‌ها ---------- */
    function msgHtml(m) {
        var attach = '';
        if (m.file && m.file.url) {
            if (m.file.is_image || /^image\//.test(m.file.mime || '')) {
                attach = '<a href="' + CN.esc(m.file.url) + '" target="_blank" rel="noopener"><img class="tkd-thumb" src="' + CN.esc(m.file.url) + '" alt="' + CN.esc(m.file.name || 'پیوست') + '"></a>';
            } else {
                attach = '<a class="tkd-attach" href="' + CN.esc(m.file.url) + '" target="_blank" rel="noopener" download>' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>' +
                    '<span class="tkd-file-name">' + CN.esc(m.file.name || 'پیوست') + '</span>' +
                    '<span class="tkd-file-size">' + CN.esc(m.file.size_fa || '') + '</span>' +
                    '</a>';
            }
        }

        return '<div class="tkd-msg ' + (m.mine ? 'mine' : 'staff') + '">' +
            '  <div class="tkd-bubble">' + CN.esc(m.message || '') + attach + '</div>' +
            '  <div class="tkd-meta-msg">' +
            '    <span>' + CN.esc(m.sender_name || '') + '</span>' +
            '    <span>·</span>' +
            '    <span>' + CN.esc(m.time_fa || '') + '</span>' +
            '  </div>' +
            '</div>';
    }

    function renderThread() {
        var thread = document.getElementById('tkdThread');
        if (!thread) { return; }
        thread.innerHTML = state.messages.map(msgHtml).join('') || '<div class="ns-empty">پیامی نیست.</div>';
        thread.scrollTop = thread.scrollHeight;
    }

    function load() {
        CN.api('/tickets/' + ticketId, {
            success: function (resp) {
                renderHead(resp.data || {});
                state.messages = resp.messages || [];
                state.lastId = resp.last_id || 0;
                renderThread();
            },
            error: function (xhr, msg) {
                if (xhr && xhr.status === 404) {
                    window.location.replace(CN.withPort('/app/support'));
                    return;
                }
                var thread = document.getElementById('tkdThread');
                if (thread) { thread.innerHTML = '<div class="ns-empty">' + CN.esc(msg || 'خطا در دریافت تیکت.') + '</div>'; }
            }
        });
    }

    /* ---------- پیوست (v29 — چیپ زیر تکست‌باکس + دکمهٔ حذف) ---------- */
    var chip = document.getElementById('tkdFileChip');
    var chipName = document.getElementById('tkdChipName');
    var chipSize = document.getElementById('tkdChipSize');
    var chipHint = document.getElementById('tkdChipHint');
    var chipThumb = document.getElementById('tkdChipThumb');
    var attachBtn = document.getElementById('tkdAttachBtn');
    var chipThumbUrl = '';

    function sizeFa(bytes) {
        if (!bytes || bytes <= 0) { return ''; }
        if (bytes < 1024) { return bytes.toLocaleString('fa-IR') + ' بایت'; }
        if (bytes < 1048576) { return (bytes / 1024).toLocaleString('fa-IR', { maximumFractionDigits: 0 }) + ' کیلوبایت'; }
        return (bytes / 1048576).toLocaleString('fa-IR', { maximumFractionDigits: 1 }) + ' مگابایت';
    }

    function clearChipThumb() {
        if (chipThumb) { chipThumb.setAttribute('hidden', ''); chipThumb.removeAttribute('src'); }
        if (chipThumbUrl) { URL.revokeObjectURL(chipThumbUrl); chipThumbUrl = ''; }
    }

    function renderChip(file) {
        clearChipThumb();
        var isImage = !!(file && file.type && file.type.indexOf('image/') === 0);
        var showThumb = isImage && !!chipThumb;
        if (file) {
            if (chipName) { chipName.textContent = file.name || 'پیوست'; }
            if (chipSize) { chipSize.textContent = sizeFa(file.size); }
            if (chip) {
                chip.removeAttribute('hidden');
                chip.classList.toggle('has-thumb', showThumb);
            }
            if (showThumb) {
                chipThumbUrl = URL.createObjectURL(file);
                chipThumb.src = chipThumbUrl;
                chipThumb.removeAttribute('hidden');
            }
            if (chipHint) { chipHint.removeAttribute('hidden'); }
            if (attachBtn) { attachBtn.classList.add('has-file'); }
        } else {
            if (chip) { chip.setAttribute('hidden', ''); chip.classList.remove('has-thumb'); }
            if (chipHint) { chipHint.setAttribute('hidden', ''); }
            if (attachBtn) { attachBtn.classList.remove('has-file'); }
        }
    }

    var fileInputEl = document.getElementById('tkdFile');
    if (fileInputEl) {
        fileInputEl.addEventListener('change', function () {
            renderChip(this.files && this.files[0]);
        });
    }

    if (document.getElementById('tkdChipRemove')) {
        document.getElementById('tkdChipRemove').addEventListener('click', function () {
            var input = document.getElementById('tkdFile');
            if (input) { input.value = ''; }
            renderChip(null);
        });
    }

    var tkdMessage = document.getElementById('tkdMessage');
    if (tkdMessage) {
        tkdMessage.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                var composer = document.getElementById('tkdComposer');
                if (composer) {
                    composer.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                }
            }
        });
    }

    if (document.getElementById('tkdComposer')) {
        document.getElementById('tkdComposer').addEventListener('submit', function (e) {
            e.preventDefault();
            if (state.sending) { return; }

            var message = String(tkdMessage ? tkdMessage.value : '').trim();
            var fileInput = document.getElementById('tkdFile');
            var file = fileInput && fileInput.files[0];

            if (!message && !file) {
                CN.toast('متن پیام یا پیوست الزامی است.', 'error');
                return;
            }

            state.sending = true;
            var sendBtn = document.getElementById('tkdSend');
            CN.btnLoading(sendBtn, true);

            var fd = new FormData();
            if (message) { fd.append('message', message); }
            if (file) { fd.append('file', file); }

            CN.api('/tickets/' + ticketId + '/messages', {
                method: 'POST',
                formData: fd,
                success: function (resp) {
                    state.sending = false;
                    CN.btnLoading(sendBtn, false);

                    if (tkdMessage) { tkdMessage.value = ''; }
                    if (fileInput) { fileInput.value = ''; }
                    renderChip(null);

                    if (resp.data) {
                        state.messages.push(resp.data);
                        state.lastId = Math.max(state.lastId, resp.data.id || 0);
                    }
                    CN.toast(resp.message || 'پاسخ ثبت شد.', 'success');
                    renderThread();
                    // وضعیت ممکن است تغییر کند (بازگشایی)
                    load();
                },
                error: function (xhr, msg) {
                    state.sending = false;
                    CN.btnLoading(sendBtn, false);
                    CN.toast(msg || 'ارسال ناموفق بود.', 'error');
                }
            });
        });
    }

    load();
})();
