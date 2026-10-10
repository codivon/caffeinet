/**
 * کافی‌نت آنلاین — صفحهٔ گفتگوی تیکت (ادمین / کافی‌نت / اپراتور)
 * فایل مستقل — بدون Node / بدون بیلد
 * رفتارها: رندر پیام‌ها از #page-data + پولینگ افزایشی + ارسال پاسخ + عملیات تیکت
 * اندپوینت‌ها از data-* روی #tk-composer خوانده می‌شوند:
 *   data-reply-url · data-status-url · data-assign-url · data-priority-url · data-poll-url · data-manage
 */
(function () {
    'use strict';

    const PAGE = App.pageData();
    const ticket = PAGE.ticket || {};
    const replyUrl = document.getElementById('tk-composer')?.dataset.replyUrl || '';
    const statusUrl = document.getElementById('tk-composer')?.dataset.statusUrl || '';
    const assignUrl = document.getElementById('tk-composer')?.dataset.assignUrl || '';
    const priorityUrl = document.getElementById('tk-composer')?.dataset.priorityUrl || '';
    const pollUrl = document.getElementById('tk-composer')?.dataset.pollUrl || '';
    const canManage = String(document.getElementById('tk-composer')?.dataset.manage || '0') === '1';

    const threadEl = document.getElementById('tk-thread');
    const countEl = document.getElementById('tk-msgs-count');
    const form = document.getElementById('tk-composer');
    const msgInput = document.getElementById('tk-message');
    const fileInput = document.getElementById('tk-file');
    const fileNameEl = document.getElementById('tk-file-name');
    const fileThumbEl = document.getElementById('tk-file-thumb');
    const internalInput = document.getElementById('tk-internal');
    const sendBtn = document.getElementById('tk-send');

    let lastId = PAGE.last_id || 0;
    let messages = (PAGE.messages || []).filter(m => !m.internal_hidden);
    let sending = false;
    let lastDay = '';
    let fileThumbUrl = ''; // پیش‌نمایش تصویر پیوست — blob URL فعلی

    /* ---------- ابزار ---------- */
    function esc(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function scrollToBottom() {
        threadEl.scrollTop = threadEl.scrollHeight;
    }

    /* ---------- رندر ---------- */
    function msgHtml(m) {
        if (m.internal_hidden) { return ''; }

        const day = m.date_fa || '';
        let daySep = '';
        if (day && day !== lastDay) {
            lastDay = day;
            daySep = `<div class="tk-day-sep">${esc(day)}</div>`;
        }

        const attach = m.file ? (
            (m.file.url && (m.file.is_image || /^image\//.test(m.file.mime || '')))
                ? `<img class="tk-thumb" data-lightbox src="${esc(m.file.url)}" alt="${esc(m.file.name || 'پیوست')}">`
                : `<a class="tk-attach" href="${esc(m.file.url || '#')}" target="_blank" rel="noopener" download>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                        <span class="tk-file-name">${esc(m.file.name || 'پیوست')}</span>
                        <span class="tk-file-size">${esc(m.file.size_fa || '')}</span>
                   </a>`
        ) : '';

        const internalTag = m.internal
            ? `<span class="tk-internal-tag">
                   <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                   یادداشت داخلی
               </span>`
            : '';

        return `${daySep}
            <div class="tk-msg ${m.mine ? 'tk-mine' : ''} ${m.internal ? 'tk-internal' : ''}">
                <div class="tk-msg-bubble">${esc(m.message)}${attach}</div>
                <div class="tk-msg-meta">
                    ${internalTag}
                    ${!m.internal ? `<span class="tk-sender">${esc(m.sender_name || '')}</span>` : ''}
                    <span>·</span>
                    <span>${esc(m.time_fa || '')}</span>
                </div>
            </div>`;
    }

    function renderAll() {
        lastDay = '';
        threadEl.innerHTML = messages.map(msgHtml).join('') ||
            '<div class="nb-empty">پیامی نیست — گفتگو را شروع کنید.</div>';
        countEl.textContent = App.digits(messages.length);
        scrollToBottom();
    }

    /* ---------- پولینگ افزایشی ---------- */
    async function poll() {
        if (!pollUrl || document.hidden || sending) { return; }
        try {
            const res = await App.ajax(pollUrl + (pollUrl.includes('?') ? '&' : '?') + 'after_id=' + lastId);
            if (!res.ok) { return; }
            const data = await res.json();
            const fresh = (data.messages || []).filter(m => !m.internal_hidden);
            if (fresh.length) {
                messages.push(...fresh);
                lastId = data.last_id || lastId;
                renderAll();
            }
        } catch (e) { /* پولینگ بی‌صدا */ }
    }

    /* v38 «پوشر کامل»: پوشر فعال و متصل → بدون پولینگ؛ پاسخ جدید تیکت
       اعلان می‌سازد → رویداد notif.new روی کانال شخصی کاربر → پیام‌ها
       همان لحظه تازه می‌شوند. قطع اتصال → پولینگ اضطراری. */
    let ticketTimer = setInterval(poll, 10000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) poll();
    });

    if (window.RT && RT.active() && RT.cfg.channel) {
        RT.bindUser('notif.new', () => {
            if (!document.hidden) poll();
        });

        if (RT.connected()) { clearInterval(ticketTimer); ticketTimer = null; }

        RT.onConnection((up) => {
            if (up) {
                if (ticketTimer) { clearInterval(ticketTimer); ticketTimer = null; }
                if (!document.hidden) poll();
            } else if (!ticketTimer) {
                ticketTimer = setInterval(poll, 10000);
            }
        });
    }

    /* ---------- ارسال پاسخ + پیش‌نمایش تصویر پیوست (F-3) ---------- */
    function hideFileThumb() {
        if (fileThumbEl) {
            fileThumbEl.hidden = true;
            fileThumbEl.removeAttribute('src');
        }
        if (fileThumbUrl) {
            URL.revokeObjectURL(fileThumbUrl);
            fileThumbUrl = '';
        }
    }

    fileInput?.addEventListener('change', function () {
        const f = this.files && this.files[0];
        hideFileThumb();
        if (fileNameEl) { fileNameEl.textContent = f ? f.name : ''; }
        if (f && fileThumbEl && f.type && f.type.startsWith('image/')) {
            fileThumbUrl = URL.createObjectURL(f);
            fileThumbEl.src = fileThumbUrl;
            fileThumbEl.removeAttribute('hidden');
        }
    });

    msgInput?.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            form?.requestSubmit();
        }
    });

    form?.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (sending) { return; }

        const message = msgInput.value.trim();
        const file = fileInput && fileInput.files[0];
        if (!message && !file) {
            App.toast('متن پیام یا پیوست الزامی است.', 'warn');
            return;
        }

        sending = true;
        sendBtn.disabled = true;
        const original = sendBtn.innerHTML;
        sendBtn.innerHTML = '<span class="size-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> در حال ارسال…';

        try {
            const fd = new FormData();
            if (message) { fd.append('message', message); }
            if (file) { fd.append('file', file); }
            if (internalInput && internalInput.checked) { fd.append('internal', '1'); }

            const res = await App.ajax(replyUrl, { method: 'POST', body: fd });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                msgInput.value = '';
                fileInput.value = '';
                if (fileNameEl) { fileNameEl.textContent = ''; }
                hideFileThumb();
                if (internalInput) { internalInput.checked = false; }
                if (data.data) {
                    messages.push(data.data);
                    lastId = Math.max(lastId, data.data.id || 0);
                    renderAll();
                }
                if (data.status_label && document.getElementById('tk-status-badge')) {
                    const badge = document.getElementById('tk-status-badge');
                    badge.textContent = data.status_label;
                    badge.className = 'tk-badge tk-status-' + data.status;
                    syncToggleBtn();
                }
                App.toast(data.message || 'پاسخ ارسال شد.', 'success');
            } else {
                App.toast(data.message || 'ارسال ناموفق بود.', 'error');
            }
        } finally {
            sending = false;
            sendBtn.disabled = false;
            sendBtn.innerHTML = original;
        }
    });

    /* ---------- عملیات تیکت ---------- */
    function syncToggleBtn() {
        const btn = document.getElementById('tk-toggle-status');
        if (!btn) { return; }
        const closed = (document.getElementById('tk-status-badge')?.textContent || '').includes('بسته');
        btn.textContent = closed ? 'بازگشایی تیکت' : 'بستن تیکت';
        btn.className = (closed ? 'btn-primary btn-shine' : 'btn-danger-soft') + ' !py-2.5 !text-xs w-full ui-press mt-1';
    }

    document.getElementById('tk-toggle-status')?.addEventListener('click', async function () {
        const btn = this;
        const closed = (document.getElementById('tk-status-badge')?.textContent || '').includes('بسته');
        btn.disabled = true;

        try {
            const res = await App.ajax(statusUrl, { method: 'PATCH', body: { action: closed ? 'reopen' : 'close' } });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                const badge = document.getElementById('tk-status-badge');
                badge.textContent = data.status_label;
                badge.className = 'tk-badge tk-status-' + data.status;
                syncToggleBtn();
                App.toast(data.message, 'success');
            } else {
                App.toast(data.message || 'خطا', 'error');
            }
        } finally {
            btn.disabled = false;
        }
    });

    if (canManage) {
        document.getElementById('tk-assign-btn')?.addEventListener('click', async function () {
            const btn = this;
            const select = document.getElementById('tk-assign');
            const userId = select && select.value;
            if (!userId) { App.toast('یک کارشناس انتخاب کنید.', 'warn'); return; }

            btn.disabled = true;
            try {
                const res = await App.ajax(assignUrl, { method: 'PATCH', body: { user_id: +userId } });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    const el = document.getElementById('tk-assignee');
                    if (el && data.assigned_name) { el.textContent = data.assigned_name; }
                    App.toast(data.message || 'ارجاع شد.', 'success');
                } else {
                    App.toast(data.message || 'خطا', 'error');
                }
            } finally {
                btn.disabled = false;
            }
        });

        document.getElementById('tk-priority-btn')?.addEventListener('click', async function () {
            const btn = this;
            const select = document.getElementById('tk-priority');
            const priority = select && select.value;
            if (!priority) { return; }

            btn.disabled = true;
            try {
                const res = await App.ajax(priorityUrl, { method: 'PATCH', body: { priority } });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    const badge = document.getElementById('tk-prio-badge');
                    if (badge) {
                        badge.textContent = data.priority_label;
                        badge.className = 'tk-badge tk-prio-' + data.priority;
                    }
                    App.toast(data.message || 'ذخیره شد.', 'success');
                } else {
                    App.toast(data.message || 'خطا', 'error');
                }
            } finally {
                btn.disabled = false;
            }
        });
    } else {
        // بدون دسترسی مدیریتی — کنترل‌های ارجاع/اولویت حذف شوند
        document.getElementById('tk-assign-btn')?.closest('.flex')?.remove();
        document.getElementById('tk-priority-btn')?.closest('.flex')?.remove();
        document.querySelector('label[for="tk-internal"]')?.remove?.();
        internalInput?.remove?.();
    }

    /* ---------- شروع ---------- */
    renderAll();

    /* ---------- v45 — موبایل: شیت «اطلاعات و عملیات تیکت» ----------
       در موبایل گفتگو تمام‌صفحه است؛ اطلاعات/ارجاع/اولویت/بستن داخل شیت
       با دکمهٔ ⓘ نوار سربرگ چت باز می‌شود. در دسکتاپ بی‌اثر (دکمه مخفی). */
    const infoToggle = document.getElementById('tk-info-toggle');
    const infoClose = document.getElementById('tk-info-close');
    const infoOverlay = document.getElementById('tk-info-overlay');

    function setInfoSheet(open) {
        document.body.classList.toggle('tk-info-open', open);
        if (infoOverlay) {
            infoOverlay.classList.toggle('show', open);
            infoOverlay.setAttribute('aria-hidden', String(!open));
        }
    }

    infoToggle?.addEventListener('click', () => setInfoSheet(true));
    infoClose?.addEventListener('click', () => setInfoSheet(false));
    infoOverlay?.addEventListener('click', () => setInfoSheet(false));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('tk-info-open')) { setInfoSheet(false); }
    });
})();
