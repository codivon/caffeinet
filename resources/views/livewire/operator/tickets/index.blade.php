{{-- «تیکت‌های پشتیبانی» پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
     کلاس‌ها/ساختار عیناً از back.operator.tickets.index + رندر operator/tickets/index.js
     حفظ شده است. فیلترها wire:model.live + وضعیت در URL (#[Url])؛
     چیپ‌های آماری محدود به کافی‌نت جاری (عیناً counts کنترلر) — بدون حذف نرم
     (اپراتور دسترسی حذف تیکت نداشت و ندارد).
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه --}}
<div>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/tickets.css') }}?v=13">
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
            <div class="text-xs text-stone-500 leading-6">
                تیکت‌های سفارش‌های کافی‌نت «{{ $coffeenet->name }}» — پاسخ‌های شما مستقیم به مشتری می‌رسد.
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select wire:model.live="status" class="field !py-2.5 !w-auto min-w-36" aria-label="فیلتر وضعیت">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="open">باز</option>
                    <option value="answered">پاسخ داده‌شده</option>
                    <option value="customer_reply">پاسخ مشتری</option>
                    <option value="closed">بسته‌شده</option>
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
                        <th>آخرین فعالیت</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($rows as $t)
                    <tr wire:key="ticket-{{ $t['id'] }}">
                        <td>
                            <a href="{{ route('operator.tickets.show', ['ticket' => $t['id']]) }}" wire:navigate class="block group">
                                <p class="font-bold text-stone-800 group-hover:text-amber-600 transition-colors">{{ $t['subject'] }}</p>
                                <p class="text-[10px] font-mono text-stone-400 mt-0.5" dir="ltr">{{ $t['ticket_number'] }}</p>
                            </a>
                        </td>
                        <td><p class="font-semibold text-stone-700 text-xs">{{ $t['customer'] }}</p></td>
                        <td>
                            @if ($t['order_number'])
                                <span class="badge bg-stone-50 text-stone-500 border border-stone-200 font-mono text-[10px]" dir="ltr">{{ $t['order_number'] }}</span>
                            @else
                                <span class="text-[11px] text-stone-300">عمومی</span>
                            @endif
                        </td>
                        <td><span class="tk-badge tk-status-{{ $t['status'] }}">{{ $t['status_label'] }}</span></td>
                        <td><span class="tk-badge tk-prio-{{ $t['priority'] }}">{{ $t['priority_label'] }}</span></td>
                        <td class="text-stone-500 text-xs">
                            @if ($t['messages_count'])
                                <span class="badge bg-amber-50 text-amber-700 border border-amber-200 mb-1">{{ fa_number($t['messages_count']) }} پیام</span>
                            @endif
                            <div>{{ $t['last_message_at'] ?: $t['created_at'] }}</div>
                        </td>
                        <td class="text-center">
                            <a class="btn-ghost !py-2 !px-4 !text-xs ui-press" href="{{ route('operator.tickets.show', ['ticket' => $t['id']]) }}" wire:navigate>
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
                                گفتگو
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-10 text-stone-400 text-xs">تیکتی برای این کافی‌نت ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

        {{-- صفحه‌بندی — عیناً فوتر قبلی --}}
        <div class="adm-table-foot text-xs text-stone-500">
            {{ $rows->links('livewire.operator.partials.pagination') }}
        </div>
    </section>
</div>
