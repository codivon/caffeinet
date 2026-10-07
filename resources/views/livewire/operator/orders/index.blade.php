{{-- «سفارش‌ها» پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
     ساختار عیناً از back.operator.orders.index + رندر operator/orders/index.js حفظ شده؛
     لیست سمت سرور با فیلترهای wire:model.live + #[Url] و صفحه‌بندی ۱۵تایی (مثل data کنترلر).
     دکمهٔ گفتگو فقط برای statusهای chattable (عیناً CHATABLE در JS) با wire:navigate.
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه --}}
<div>

    {{-- سربرگ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 animate-fade-up">
        <div>
            <p class="text-sm font-extrabold text-stone-700">{{ $canAll ? 'همهٔ سفارش‌های کافی‌نت' : 'سفارش‌های سپرده‌شده به شما' }}</p>
            <p class="text-[11px] text-stone-400 mt-1 leading-5">
                {{ $canAll
                    ? 'شما دسترسی مشاهده همهٔ سفارش‌های کافی‌نت «'.$coffeenet->name.'» را دارید.'
                    : 'فقط سفارش‌هایی که اپراتور آن‌ها هستند نمایش داده می‌شوند.' }}
            </p>
        </div>
    </div>

    {{-- جدول --}}
    <div class="card overflow-hidden animate-fade-up">
        {{-- فیلترها --}}
        <div class="px-5 py-4 border-b border-stone-100 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" placeholder="جستجوی شماره سفارش یا نام مشتری…"
                       wire:model.live.debounce.400ms="q"
                       class="field op-pill-input !py-2.5 !text-xs w-full pl-10" autocomplete="off">
            </div>
            <select wire:model.live="status" class="field op-select-pill !py-2.5 !text-xs !w-auto min-w-44" aria-label="فیلتر وضعیت">
                <option value="active">در جریان کار</option>
                <option value="accepted">پذیرفته‌شده (در انتظار پرداخت)</option>
                <option value="paid">پرداخت‌شده (آماده شروع)</option>
                <option value="in_progress">در حال انجام</option>
                <option value="needs_info">نیازمند اطلاعات</option>
                <option value="done">تحویل/تکمیل‌شده</option>
                <option value="completed">تکمیل‌شده</option>
                <option value="cancelled">لغوشده</option>
                <option value="all">همه</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table-panel table-modern">
                <thead>
                <tr>
                    <th>سفارش</th>
                    <th>خدمت</th>
                    <th>مشتری</th>
                    @if ($canAll)
                        <th>اپراتور</th>
                    @endif
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>پرداخت</th>
                    <th>پذیرش</th>
                    <th>گفتگو</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr class="group" wire:key="order-{{ $row['id'] }}">
                        <td><span class="text-[11px] font-mono text-stone-500" dir="ltr">{{ $row['order_number'] }}</span></td>
                        <td class="text-xs font-bold text-stone-700">{{ $row['service_name'] }}</td>
                        <td class="text-xs text-stone-600">{{ $row['customer_name'] }}</td>
                        @if ($canAll)
                            <td class="text-xs text-stone-500">{{ $row['operator_name'] ?? 'تعیین نشده' }}</td>
                        @endif
                        <td class="text-xs font-extrabold text-amber-700 tabular-nums">{{ fa_money($row['total'], false) }}</td>
                        <td><span class="badge {{ \App\Livewire\Operator\Orders\Index::statusColorClass($row['status']['color']) }}">{{ $row['status']['label'] }}</span></td>
                        <td>
                            @if ($row['is_paid'])
                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200" title="پرداخت انجام شده">✓ پرداخت</span>
                            @else
                                <span class="badge bg-amber-50 text-amber-700 border border-amber-200 animate-pulse" title="در انتظار پرداخت مشتری">⏳ در انتظار پرداخت</span>
                            @endif
                        </td>
                        <td class="text-[11px] text-stone-400">{{ $row['accepted_at_fa'] ?? '—' }}</td>
                        <td>
                            @if (\App\Livewire\Operator\Orders\Index::isChattable($row['status']['value']))
                                <a href="{{ route('operator.orders.chat', $row['id']) }}" wire:navigate
                                   class="ui-row-btn" title="گفتگو با مشتری" aria-label="گفتگوی سفارش {{ $row['order_number'] }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                                </a>
                            @else
                                <span class="text-[10px] text-stone-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canAll ? 9 : 8 }}" class="!py-10">
                            <div class="ui-empty mx-auto max-w-md">
                                <span class="ui-empty-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 13v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M2 9h20"/><path d="M12 2 2 9l10 7 10-7-10-7Z"/></svg>
                                </span>
                                <p class="text-sm font-bold text-stone-600">سفارشی یافت نشد</p>
                                <p class="text-xs text-stone-400 mt-1 leading-6">{{ $canAll ? 'سفارش‌های این کافی‌نت' : 'سفارش‌های سپرده‌شده به شما' }} با فیلتر جاری اینجا نمایش داده می‌شوند.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3 border-t border-stone-100 flex items-center justify-between gap-3">
            <p class="text-[11px] text-stone-400">
                @if ($rows->total())
                    نمایش {{ fa_number($rows->firstItem()) }} تا {{ fa_number($rows->lastItem()) }} از {{ fa_number($rows->total()) }} سفارش
                @else
                    —
                @endif
            </p>
            {{ $rows->links('livewire.operator.partials.pagination') }}
        </div>
    </div>
</div>
