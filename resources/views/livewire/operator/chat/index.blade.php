{{-- «گفتگوها» پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
     ساختار/کلاس‌ها عیناً از back.operator.chat.index + رندر operator/chat/index.js حفظ شده؛
     لیست سمت سرور با فیلترهای wire:model.live (در جریان/پایان‌یافته/همه) و صفحه‌بندی ۱۵تایی.
     لینک هر گفتگو wire:navigate به operator.orders.chat.
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه --}}
<div>

    {{-- سربرگ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 animate-fade-up">
        <div>
            <p class="text-sm font-extrabold text-stone-700">گفتگوهای «{{ $coffeenet->name ?? 'همهٔ کافی‌نت‌ها' }}»</p>
            <p class="text-[11px] text-stone-400 mt-1 leading-5">
                گفتگوی زنده با مشتریان — پیام‌های جدید هر چند ثانیه خودکار به‌روز می‌شوند.
            </p>
        </div>
        <span class="hidden sm:inline-flex items-center gap-2 op-status-pill" title="به‌روزرسانی خودکار">
            <span class="relative flex size-2"><span class="absolute inline-flex size-full rounded-full bg-emerald-400 opacity-60 animate-ping"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-400"></span></span>
            به‌روزرسانی زنده
        </span>
    </div>

    {{-- فیلترها --}}
    <div class="card animate-fade-up !p-0 mb-4">
        <div class="px-5 py-4 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" placeholder="جستجوی شماره سفارش یا نام مشتری…"
                       wire:model.live.debounce.400ms="q"
                       class="field op-pill-input !py-2.5 !text-xs w-full pl-10" autocomplete="off">
            </div>
            <div class="flex items-center gap-1.5" role="tablist" aria-label="فیلتر گفتگوها">
                <button type="button" wire:click="filterBy('active')"
                        class="chat-filter-btn btn-ghost !py-2.5 !px-4 !text-xs {{ $filter === 'active' ? 'rounded-lg pg-active' : '' }}"
                        aria-pressed="{{ $filter === 'active' ? 'true' : 'false' }}">در جریان</button>
                <button type="button" wire:click="filterBy('done')"
                        class="chat-filter-btn btn-ghost !py-2.5 !px-4 !text-xs {{ $filter === 'done' ? 'rounded-lg pg-active' : '' }}"
                        aria-pressed="{{ $filter === 'done' ? 'true' : 'false' }}">پایان‌یافته</button>
                <button type="button" wire:click="filterBy('all')"
                        class="chat-filter-btn btn-ghost !py-2.5 !px-4 !text-xs {{ $filter === 'all' ? 'rounded-lg pg-active' : '' }}"
                        aria-pressed="{{ $filter === 'all' ? 'true' : 'false' }}">همه</button>
            </div>
        </div>
    </div>

    {{-- فهرست --}}
    <div class="card animate-fade-up !p-0 overflow-hidden">
        <div class="divide-y divide-stone-100">
            @forelse ($rows as $row)
                <a href="{{ route('operator.orders.chat', $row['id']) }}" wire:key="chat-{{ $row['id'] }}" wire:navigate
                   class="chat-row flex items-center gap-3 px-4 sm:px-5 py-4">
                    <span class="grid place-items-center size-11 rounded-2xl bg-gradient-to-br from-amber-400/80 to-amber-700/80 text-amber-50 font-bold text-sm shrink-0" aria-hidden="true">{{ mb_substr(trim($row['customer_name'] ?: 'م'), 0, 1) }}</span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <strong class="text-xs font-bold text-stone-700 truncate">{{ $row['customer_name'] }}</strong>
                            <span class="text-[10px] font-mono text-stone-400 truncate" dir="ltr">{{ $row['order_number'] }}</span>
                        </div>
                        <p class="text-[11px] text-stone-500 mt-1 truncate {{ $row['last_type'] === 'system' ? 'text-stone-400' : '' }}">
                            @if ($row['unseen'] > 0)<strong class="text-stone-700">@endif
                            {{ $row['last_preview'] ?: '—' }}
                            @if ($row['unseen'] > 0)</strong>@endif
                        </p>
                    </div>

                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                        <span class="text-[10px] text-stone-400 font-mono" dir="ltr">{{ $row['last_time_fa'] ?? '—' }}</span>
                        <div class="flex items-center gap-1.5">
                            @if ($row['unseen'] > 0)
                                <span class="cchat-badge on" aria-label="{{ fa_digits((string) $row['unseen']) }} پیام خوانده‌نشده">{{ fa_digits((string) $row['unseen']) }}</span>
                            @endif
                            <span class="badge {{ ['amber' => 'bg-amber-50 text-amber-700 border border-amber-200', 'sky' => 'bg-sky-50 text-sky-700 border border-sky-200', 'blue' => 'bg-teal-50 text-teal-700 border border-teal-200', 'orange' => 'bg-amber-50 text-amber-700 border border-amber-200', 'teal' => 'bg-teal-50 text-teal-700 border border-teal-200', 'emerald' => 'bg-emerald-50 text-emerald-700 border border-emerald-200', 'rose' => 'bg-rose-50 text-rose-600 border border-rose-200'][$row['status']['color']] ?? 'bg-amber-50 text-amber-700 border border-amber-200' }}">{{ $row['status']['label'] }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-8">
                    <div class="ui-empty mx-auto max-w-md">
                        <span class="ui-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                        </span>
                        <p class="text-sm font-bold text-stone-600">گفتگویی یافت نشد</p>
                        <p class="text-xs text-stone-400 mt-1 leading-6">گفتگوی سفارش‌های پذیرفته‌شدهٔ کافی‌نت اینجا نمایش داده می‌شوند.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="px-5 py-3 border-t border-stone-100 flex items-center justify-between gap-3">
            <p class="text-[11px] text-stone-400">
                @if ($rows->total())
                    نمایش {{ fa_digits((string) $rows->firstItem()) }} تا {{ fa_digits((string) $rows->lastItem()) }} از {{ fa_digits((string) $rows->total()) }} گفتگو
                @else
                    —
                @endif
            </p>
            {{ $rows->links('livewire.operator.partials.pagination') }}
        </div>
    </div>
</div>
