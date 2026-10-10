{{-- [Task 3-d] چسب حذف نرم/دائم برای صفحات Livewire موجودیت‌ها
     هم‌ساخت trash.js (ماژول مشترک v28) اما:
       • فقط روی ریشهٔ کامپوننت bind می‌شود (ضد تکرار لیسنر در ناوبری SPA)
       • دکمهٔ «حذفشده‌ها» استاتیک در Blade رندر می‌شود (ضد حذف‌شدن با morph)
     اندپوینت‌ها عیناً همان‌های TrashController هستند (GET trashed / delete-info، DELETE، POST restore، DELETE purge)
     پارامترها: $trashSection (نام بخش)، $rootId (آیدی ریشهٔ کامپوننت) --}}
@php $trashBase = '/admin/' . $trashSection; @endphp
<script>
(function () {
    const root = document.getElementById('{{ $rootId }}');
    if (!root) return;

    /* اسکریپت درون‌خطی کامپوننت پیش از تزریق اسکریپت‌های فریم‌ورک در لایه اجرا می‌شود؛
       تا آماده‌شدن App/Livewire و ثبت کامپوننت صبر می‌کنیم (بار اول).
       ⚠️ هرگز «دایرکتیو اسکریپت‌های فریم‌ورک» را داخل کامنت ننویسید — بلیید آن را
       به تگ <script> واقعی بسط می‌دهد و بلاک جاوااسکریپت را نیمه‌کاره می‌بندد. */
    const ready = () => window.App && window.Livewire
        && window.Livewire.find(root.getAttribute('wire:id'));

    function init() {
    const base = @js($trashBase);
    const $wire = window.Livewire.find(root.getAttribute('wire:id'));
    const badge = document.getElementById('trash-count-{{ $trashSection }}');

    function esc(s) {
        /* [فاز ۵۷ رفع] سه‌تایی ناقص بود: `? ''` بدون `: s` — خطای سینتکس
           «Unexpected token ')' Expected ':' in ternary operator» می‌داد که
           کل اسکریپت چسب حذف را می‌کُشت (همان ارور کنسول صفحهٔ کافی‌نت‌ها). */
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    const TRASH_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>';
    const RESTORE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';

    /* ---------- بج شمار حذفشده‌ها ---------- */
    async function refreshBadge() {
        try {
            const res = await App.ajax(base + '/trashed');
            if (!res.ok) return;
            const data = await res.json().catch(() => ({}));
            const count = (data.data || []).length;
            if (!badge) return;
            badge.hidden = count === 0;
            badge.textContent = String(count).replace(/[0-9]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[+d]);
        } catch { /* بی‌صدا */ }
    }

    /* ---------- ساخت مودال عمومی ---------- */
    function buildModal(innerHtml, tone) {
        const wrap = document.createElement('div');
        wrap.innerHTML = '<div class="ui-modal-backdrop" role="dialog" aria-modal="true"><div class="ui-modal trash-modal' +
            (tone === 'info' ? ' trash-list-modal' : '') + '" data-tone="' + tone + '">' + innerHtml + '</div></div>';
        const modal = wrap.firstElementChild;
        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';

        const close = () => {
            modal.classList.add('ui-closing');
            setTimeout(() => {
                modal.remove();
                if (!document.querySelector('.ui-modal-backdrop:not(.ui-closing):not(.hidden)')) {
                    document.body.style.overflow = '';
                }
            }, 200);
        };

        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
        const onKey = (e) => { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', onKey);
        const obs = new MutationObserver(() => {
            if (!modal.isConnected) { obs.disconnect(); document.removeEventListener('keydown', onKey); }
        });
        obs.observe(document.body, { childList: true, subtree: true });

        return { modal, close };
    }

    /* ---------- تأیید حذف + DELETE ---------- */
    async function confirmAndDelete(id, fallbackLabel) {
        App.toast('در حال بررسی وابستگی‌ها…', 'info');

        let info = null;
        try {
            const res = await App.ajax(base + '/' + id + '/delete-info');
            const data = await res.json().catch(() => ({}));
            if (res.ok) info = data.data;
        } catch { /* ادامه با حالت ساده */ }

        const label = info?.label || fallbackLabel || 'این رکورد';
        const warnings = Array.isArray(info?.warnings) ? info.warnings : [];

        if (info && info.deletable === false) {
            window.PanelUI?.alert({ title: 'حذف مجاز نیست', desc: info.reason || 'این رکورد قابل حذف نیست.', type: 'warning' });
            return;
        }

        const html =
            '<span class="ui-modal-icon trash-icon">' + TRASH_ICON + '</span>' +
            '  <h3 class="ui-modal-title">حذف «' + esc(label) + '»</h3>' +
            '  <p class="ui-modal-desc">این رکورد به <b>لیست حذف‌شده‌ها</b> منتقل می‌شود و تا پیش از «حذف دائم» قابل بازگردانی است.' +
            '      هشدارهای وابستگی را بخوانید:</p>' +
            '  <ul class="trash-warnings" role="list">' + warnings.map((w) => '<li>' + esc(w) + '</li>').join('') + '</ul>' +
            '  <div class="ui-modal-actions">' +
            '    <button type="button" class="ui-btn-danger ui-press" data-trash-ok>بله، حذف کن</button>' +
            '    <button type="button" class="btn btn-ghost ui-press" data-trash-cancel>انصراف</button>' +
            '  </div>';

        const { modal, close } = buildModal(html, 'warning');

        modal.querySelector('[data-trash-cancel]').addEventListener('click', close);
        modal.querySelector('[data-trash-ok]').addEventListener('click', async function () {
            const okBtn = this;
            okBtn.disabled = true;
            okBtn.innerHTML = '<span class="size-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> در حال حذف…';
            try {
                const res = await App.ajax(base + '/' + id, { method: 'DELETE' });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    App.toast(data.message || 'حذف شد و به حذفشده‌ها منتقل شد.', 'success');
                    close();
                    refreshBadge();
                    $wire.$refresh();
                } else {
                    App.toast(data.message || 'حذف ناموفق بود.', 'error');
                    okBtn.disabled = false;
                    okBtn.textContent = 'بله، حذف کن';
                }
            } catch {
                App.toast('ارتباط با سرور برقرار نشد.', 'error');
                okBtn.disabled = false;
                okBtn.textContent = 'بله، حذف کن';
            }
        });
    }

    /* ---------- مودال حذفشده‌ها ---------- */
    function openTrashed() {
        const html =
            '  <div class="trash-list-head">' +
            '    <span class="ui-modal-icon trash-icon">' + TRASH_ICON + '</span>' +
            '    <h3 class="ui-modal-title">حذفشده‌ها</h3>' +
            '    <input type="search" class="field trash-list-search" placeholder="جستجو در حذفشده‌ها…" aria-label="جستجو">' +
            '  </div>' +
            '  <p class="ui-modal-desc">رکوردهای حذفشده (نرم). «بازگردانی» آن‌ها را برمی‌گرداند؛ «حذف دائم» رکورد و وابسته‌هایش را برای همیشه پاک می‌کند.</p>' +
            '  <div class="trash-list" role="list" aria-live="polite"><p class="trash-empty"><span class="nb-spin"></span> در حال بارگذاری…</p></div>' +
            '  <div class="ui-modal-actions"><button type="button" class="btn btn-ghost ui-press" data-trash-close>بستن</button></div>';

        const { modal, close } = buildModal(html, 'info');
        modal.querySelector('[data-trash-close]').addEventListener('click', close);

        const listEl = modal.querySelector('.trash-list');

        const load = async () => {
            const q = modal.querySelector('.trash-list-search')?.value.trim() || '';
            listEl.innerHTML = '<p class="trash-empty"><span class="nb-spin"></span> در حال بارگذاری…</p>';
            try {
                const res = await App.ajax(base + '/trashed' + (q ? '?q=' + encodeURIComponent(q) : ''));
                const data = await res.json().catch(() => ({}));
                const rows = data.data || [];
                if (!res.ok) { listEl.innerHTML = '<p class="trash-empty">خطا در دریافت حذفشده‌ها.</p>'; return; }
                if (!rows.length) { listEl.innerHTML = '<p class="trash-empty">مورد حذفشده‌ای' + (q ? ' با این جستجو ' : ' ') + 'وجود ندارد.</p>'; return; }
                listEl.innerHTML = rows.map((r) => `
                    <div class="trash-row" role="listitem">
                        <div class="trash-row-body">
                            <strong>${esc(r.label)}</strong>
                            <small>${esc(r.sub || '')} · حذف: ${esc(r.deleted_at_fa)}</small>
                        </div>
                        <div class="trash-row-actions">
                            <button type="button" class="btn btn-ghost ui-press trash-restore" data-id="${r.id}" title="بازگردانی به حالت فعال">${RESTORE_ICON} بازگردانی</button>
                            <button type="button" class="ui-btn-danger ui-press trash-purge" data-id="${r.id}" data-label="${esc(r.label)}">${TRASH_ICON} حذف دائم</button>
                        </div>
                    </div>`).join('');
            } catch {
                listEl.innerHTML = '<p class="trash-empty">ارتباط با سرور برقرار نشد.</p>';
            }
        };

        modal.querySelector('.trash-list-search')?.addEventListener('input', (() => {
            let t = null;
            return () => { clearTimeout(t); t = setTimeout(load, 350); };
        })());

        listEl.addEventListener('click', async (e) => {
            const restoreBtn = e.target.closest('.trash-restore');
            const purgeBtn = e.target.closest('.trash-purge');
            if (restoreBtn) {
                await doAction(parseInt(restoreBtn.getAttribute('data-id'), 10), 'restore', 'POST', restoreBtn, load);
            } else if (purgeBtn) {
                const pid = parseInt(purgeBtn.getAttribute('data-id'), 10);
                const plabel = purgeBtn.getAttribute('data-label') || 'این رکورد';
                window.PanelUI?.confirm({
                    title: 'حذف دائم «' + plabel + '»',
                    desc: 'این عمل غیرقابل بازگشت است؛ رکورد، فایل‌ها/تصاویر و گفتگوهای وابسته برای همیشه پاک می‌شوند. مطمئن هستید؟',
                    okText: 'بله، حذف دائم کن',
                    cancelText: 'انصراف',
                    danger: true,
                }, () => doAction(pid, 'purge', 'DELETE', purgeBtn, load));
            }
        });

        async function doAction(id, action, method, btn, onDone) {
            if (!id) return;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="size-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>';
            try {
                const res = await App.ajax(base + '/' + id + '/' + action, { method });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    App.toast(data.message || 'انجام شد.', 'success');
                    refreshBadge();
                    if (onDone) onDone();
                } else {
                    App.toast(data.message || 'عملیات ناموفق بود.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            } catch {
                App.toast('ارتباط با سرور برقرار نشد.', 'error');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }

        load();
    }

    /* ---------- bind فقط روی ریشهٔ کامپوننت (ضد تکرار) ---------- */
    root.addEventListener('click', (e) => {
        if (e.target.closest('[data-trash-open]')) { openTrashed(); return; }
        const btn = e.target.closest('[data-trash]');
        if (!btn) return;
        e.preventDefault();
        const id = parseInt(btn.getAttribute('data-trash'), 10);
        if (!id) return;
        confirmAndDelete(id, btn.getAttribute('data-trash-label') || '');
    });

    refreshBadge();
    }

    if (ready()) { init(); }
    else { const t = setInterval(() => { if (ready()) { clearInterval(t); init(); } }, 25); }
})();
</script>
