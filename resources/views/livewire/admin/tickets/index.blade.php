{{-- «تیکت‌های پشتیبانی» — نسخهٔ Livewire 4 [Task 3-e]
     کلاس‌ها/ساختار عیناً از back/admin/tickets/index.blade.php حفظ شده است.
     فیلترها wire:model.live + وضعیت در URL (#[Url]) — حذف نرم همان data-trash + trash.js --}}
<div class="space-y-5">

    @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}?v=14">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/tickets.css') }}?v=13">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/trash.css') }}?v=3">
    @endpush

    {{-- چیپ‌های آماری --}}
    <div class="tk-stats">
        @foreach ($chips as $chip)
            <button type="button" class="tk-stat" data-tone="{{ $chip['tone'] }}" wire:click="applyChip('{{ $chip['key'] }}')" wire:key="chip-{{ $loop->index }}-{{ $chip['key'] }}">
                <strong>{{ fa_number($chip['value']) }}</strong>
                <span>{{ $chip['label'] }}</span>
            </button>
        @endforeach
    </div>

    <section class="card ui-lift animate-fade-up overflow-hidden">

        {{-- هدر جدول --}}
        <div class="adm-card-head">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="text-xs text-stone-500 leading-6">
                    تیکت‌های بدون سفارش «عمومی»اند و فقط همین‌جا پاسخ می‌گیرند؛ تیکت‌های دارای سفارش در پنل کافی‌نتِ مرتبط هم دیده می‌شوند.
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select wire:model.live="status" class="field !py-2.5 !w-auto min-w-36" aria-label="فیلتر وضعیت">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="open">باز</option>
                    <option value="answered">پاسخ داده‌شده</option>
                    <option value="customer_reply">پاسخ مشتری</option>
                    <option value="closed">بسته‌شده</option>
                </select>
                <select wire:model.live="priority" class="field !py-2.5 !w-auto min-w-32" aria-label="فیلتر اولویت">
                    <option value="">همه اولویت‌ها</option>
                    <option value="low">کم</option>
                    <option value="normal">معمولی</option>
                    <option value="high">زیاد</option>
                </select>
                <div class="relative">
                    <input wire:model.live.debounce.400ms="q" type="search" class="field !py-2.5 min-w-44 !ps-9" placeholder="جستجو: شماره / موضوع / مشتری…" aria-label="جستجو">
                    <svg class="absolute start-3 top-1/2 -translate-y-1/2 size-4 text-stone-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
            </div>
        </div>

        {{-- جدول --}}
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                    <thead>
                    <tr>
                        <th>تیکت</th>
                        <th>مشتری</th>
                        <th>سفارش مرتبط</th>
                        <th>وضعیت</th>
                        <th>اولویت</th>
                        <th>کارشناس</th>
                        <th>آخرین فعالیت</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $t)
                        <tr wire:key="ticket-{{ $t['id'] }}">
                            <td>
                                <a href="{{ route('admin.tickets.show', ['ticket' => $t['id']]) }}" wire:navigate class="block group">
                                    <p class="font-bold text-stone-800 group-hover:text-amber-600 transition-colors">{{ $t['subject'] }}</p>
                                    <p class="text-[10px] font-mono text-stone-400 mt-0.5" dir="ltr">{{ $t['ticket_number'] }}</p>
                                </a>
                            </td>
                            <td>
                                <p class="font-semibold text-stone-700 text-xs">{{ $t['customer'] }}</p>
                                @if ($t['customer_mobile'])
                                    <p class="text-[10px] font-mono text-stone-400" dir="ltr">{{ $t['customer_mobile'] }}</p>
                                @endif
                            </td>
                            <td>
                                @if ($t['order_number'])
                                    <span class="badge bg-stone-50 text-stone-500 border border-stone-200 font-mono text-[10px]" dir="ltr">{{ $t['order_number'] }}</span>
                                @else
                                    <span class="text-[11px] text-stone-300">عمومی</span>
                                @endif
                            </td>
                            <td><span class="tk-badge tk-status-{{ $t['status'] }}">{{ $t['status_label'] }}</span></td>
                            <td><span class="tk-badge tk-prio-{{ $t['priority'] }}">{{ $t['priority_label'] }}</span></td>
                            <td>
                                @if ($t['assigned_name'])
                                    <span class="text-xs font-semibold text-stone-600">{{ $t['assigned_name'] }}</span>
                                @else
                                    <span class="text-[11px] text-stone-300">تعیین‌نشده</span>
                                @endif
                            </td>
                            <td class="text-stone-500 text-xs">
                                @if ($t['messages_count'])
                                    <span class="badge bg-amber-50 text-amber-700 border border-amber-200 mb-1">{{ fa_number($t['messages_count']) }} پیام</span>
                                @endif
                                <div>{{ $t['last_message_at'] ?: $t['created_at'] }}</div>
                            </td>
                            <td class="text-center">
                                <a class="btn-ghost !py-2 !px-4 !text-xs ui-press" href="{{ route('admin.tickets.show', ['ticket' => $t['id']]) }}" wire:navigate>
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
                                    گفتگو
                                </a>
                                <button type="button" class="btn-ghost !py-2 !px-4 !text-xs ui-press !text-rose-600 hover:!bg-rose-50" data-trash="{{ $t['id'] }}" data-trash-label="{{ $t['ticket_number'] ?: ('#'.$t['id']) }}" title="حذف تیکت (به حذف‌شده‌ها)">حذف</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-10 text-stone-400 text-xs">تیکتی یافت نشد.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- صفحه‌بندی — عیناً فوتر قبلی --}}
        <div class="adm-table-foot text-xs text-stone-500">
            @if ($rows->lastPage() > 1)
                <span>{{ fa_number($rows->total()) }} تیکت — صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}</span>
                <div class="flex gap-2">
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="previousPage" @disabled($rows->onFirstPage())>قبلی</button>
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="nextPage" @disabled(! $rows->hasMorePages())>بعدی</button>
                </div>
            @else
                <span>{{ fa_number($rows->total()) }} تیکت</span>
            @endif
        </div>
    </section>

    {{-- ماژول حذف نرم/دائم مشترک — delegation سطح document (با گارد دوباره‌سوییچ SPA) --}}
    {{-- [فاز ۵۷ رفع] trash.js باید همین‌جا لود شود؛ قبلاً به بازدید قبلی از
        صفحات مشتریان/سفارش‌ها اتکا داشت و در ورود مستقیم به تیکت‌ها
        window.AdminTrash وجود نداشت (هیچ اروری، ولی قابلیت حذف مرده بود). --}}
    <script src="{{ asset('back/assets/js/pages/trash.js') }}?v=2"></script>
    <script>
        (function () {
            // [Task 3-e] در نسخهٔ Livewire صفحه با wire:navigate سواپ می‌شود؛
            // شنوندهٔ delegation یک‌بار (اولین mount) نصب می‌ماند و برای جلوگیری از
            // دیالوگ دوبله، confirmAndDelete با گارد زمانی کوتاه پوشانده می‌شود.
            // [فاز ۵۷ رفع] window.AdminTrash یک «نمونهٔ ساخته‌شده» است نه کلاس
            // (trash.js: window.AdminTrash = new AdminTrash()) — پس prototype ندارد و
            // دسترسی به AdminTrash.prototype.confirmAndDelete خطای
            // «Cannot read properties of undefined (reading 'confirmAndDelete')» می‌داد؛
            // اکنون متد خودِ نمونه wrap می‌شود (بدون شکستن mount بعدی).
            if (!window.AdminTrash || typeof window.AdminTrash.confirmAndDelete !== 'function') { return; }

            if (!window.__tk3eTrashWrapped) {
                const orig = window.AdminTrash.confirmAndDelete.bind(window.AdminTrash);
                window.AdminTrash.confirmAndDelete = function (base, id, label) {
                    if (window.__tk3eTrashBusy) { return; }
                    window.__tk3eTrashBusy = true;
                    setTimeout(() => { window.__tk3eTrashBusy = false; }, 1500);
                    return orig(base, id, label);
                };
                window.__tk3eTrashWrapped = true;
            }

            // در هر بازدید: گارد بدنه را تازه کن تا «دکمهٔ حذف‌شده‌ها» و بج تزریق/به‌روز شود
            document.body.removeAttribute('data-trash-mounted-tickets');
            window.AdminTrash.mount({ section: 'tickets' });
        })();
    </script>
</div>
