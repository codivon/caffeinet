{{-- «تسویه‌های کمیسیون» — نسخهٔ Livewire 4 [Task 3-e]
     کلاس‌ها/ساختار عیناً از back/admin/settlements/index.blade.php حفظ شده است.
     جدول/فیلترها Livewire؛ مودال جزئیات همان payload کنترلر را سمت سرور می‌سازد. --}}
<div>

    {{-- کارت‌های آماری --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card ui-lift p-5 animate-fade-up relative overflow-hidden">
            <span class="ui-orb" data-tone="amber" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">جمع تسویه‌ها</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-stone-800 relative">{{ fa_money($summary['total'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">{{ fa_number($summary['count']) }} پرداخت در {{ fa_number($summary['orders']) }} سفارش</p>
        </div>
        <div class="card ui-lift p-5 animate-fade-up delay-1 relative overflow-hidden">
            <span class="ui-orb" data-tone="amber" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">سهم پلتفرم</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-amber-600 relative">{{ fa_money($summary['platform'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">واریزی به حساب پلتفرم</p>
        </div>
        <div class="card ui-lift p-5 animate-fade-up delay-2 relative overflow-hidden">
            <span class="ui-orb" data-tone="teal" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">سهم کافی‌نت‌ها</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-teal-600 relative">{{ fa_money($summary['coffeenet'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">پس از کسر درآمد اپراتور</p>
        </div>
        <div class="card ui-lift p-5 animate-fade-up delay-2 relative overflow-hidden">
            <span class="ui-orb" data-tone="rose" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">درآمد اپراتورها</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-rose-500 relative">{{ fa_money($summary['operator'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">سازمان‌ها: {{ fa_money($summary['organization'], false) }}</p>
        </div>
    </div>

    {{-- جدول تسویه‌ها --}}
    <section class="card ui-lift animate-fade-up delay-2 overflow-hidden mt-5">
        <div class="adm-card-head">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.400ms="q" class="field !py-2.5 !w-56 !text-xs pl-9" placeholder="جستجوی شماره سفارش…" aria-label="جستجوی شماره سفارش">
                    <svg class="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <input type="text" id="from-filter" dir="ltr" placeholder="۱۴۰۵/۰۶/۰۱" class="field !py-2.5 !text-xs !w-36 !text-center" aria-label="از تاریخ" data-jdp data-jdp-mode="gregorian" title="برای انتخاب تاریخ کلیک کنید">
                <input type="hidden" id="from-filter-g" x-on:change="$wire.set('from', $el.value)">
                <input type="text" id="to-filter" dir="ltr" placeholder="۱۴۰۵/۰۶/۳۱" class="field !py-2.5 !text-xs !w-36 !text-center" aria-label="تا تاریخ" data-jdp data-jdp-mode="gregorian" title="برای انتخاب تاریخ کلیک کنید">
                <input type="hidden" id="to-filter-g" x-on:change="$wire.set('to', $el.value)">
            </div>
            <select wire:model.live="role" class="field !py-2.5 !w-auto min-w-40" aria-label="فیلتر نقش">
                <option value="">همهٔ نقش‌ها</option>
                <option value="platform">پلتفرم</option>
                <option value="organization">سازمان</option>
                <option value="coffeenet">کافی‌نت</option>
                <option value="operator">اپراتور</option>
            </select>
        </div>

        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                    <thead>
                    <tr>
                        <th>سفارش</th>
                        <th>خدمت</th>
                        <th>نقش</th>
                        <th>دارندهٔ سهم</th>
                        <th>مبلغ</th>
                        <th>تاریخ تسویه</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $r)
                        <tr wire:key="payout-{{ $r['id'] }}" class="cursor-pointer" wire:click="openOrder({{ $r['order_id'] }})">
                            <td><p class="font-bold text-stone-800 num">{{ $r['order_number'] }}</p></td>
                            <td class="text-xs text-stone-500">{{ $r['service'] }}</td>
                            <td><span class="badge {{ $roleBadges[$r['role']] ?? '' }}">{{ $r['role_label'] }}</span></td>
                            <td class="text-xs text-stone-600">{{ $r['holder'] }}</td>
                            <td class="font-extrabold tabular-nums text-amber-600">{{ fa_money($r['amount'], false) }} <span class="text-[10px] font-normal text-stone-400">تومان</span></td>
                            <td class="text-xs text-stone-500">{{ $r['date'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="!py-10">
                            <div class="ui-empty">
                                <span class="ui-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/></svg></span>
                                <p class="text-sm font-semibold text-stone-500">تسویه‌ای یافت نشد.</p>
                                <p class="text-xs text-stone-400 mt-1">با تحویل اولین سفارش پرداخت‌شده، سهم‌ها اینجا ثبت می‌شوند.</p>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- صفحه‌بندی — عیناً فوتر قبلی --}}
        <div class="adm-table-foot text-xs text-stone-500">
            @if ($rows->lastPage() > 1)
                <span>{{ fa_number($rows->total()) }} پرداخت — صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}</span>
                <div class="flex gap-2">
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="previousPage" @disabled($rows->onFirstPage())>قبلی</button>
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="nextPage" @disabled(! $rows->hasMorePages())>بعدی</button>
                </div>
            @else
                <span>{{ fa_number($rows->total()) }} پرداخت</span>
            @endif
        </div>
    </section>

    {{-- مودال جزئیات تسویهٔ سفارش — payload عیناً از order() کنترلر --}}
    @php $d = $orderModal; $o = $d['order'] ?? []; @endphp
    <div id="order-modal" class="ui-modal-backdrop {{ $d ? 'flex' : 'hidden' }}">
        <div class="absolute inset-0" wire:click="closeOrder" aria-hidden="true"></div>
        <div class="ui-modal adm-modal-text-start" data-tone="amber" role="dialog" aria-modal="true" aria-labelledby="om-title">
            <div class="adm-modal-head">
                <h3 class="text-sm font-extrabold text-stone-800" id="om-title">جزئیات تسویه</h3>
                <button type="button" class="adm-modal-x" wire:click="closeOrder" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="adm-modal-body space-y-4" id="om-body">
                @if ($d)
                    @if (! $d['settled'])
                        <div class="ui-empty">
                            <span class="ui-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg></span>
                            <p class="text-sm font-semibold text-stone-500">{{ $d['message'] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs rounded-2xl bg-stone-50 border border-stone-100 p-4">
                            <div><span class="text-stone-400">سفارش</span><p class="font-bold text-stone-700 mt-1 num">{{ $o['order_number'] ?? '—' }}</p></div>
                            <div><span class="text-stone-400">مبلغ مشمول</span><p class="font-bold text-stone-700 mt-1">{{ fa_money($o['commissionable'] ?? 0) }}</p></div>
                        </div>
                    @else
                        <div class="rounded-2xl bg-gradient-to-l from-amber-50 to-white border border-amber-100 p-4 space-y-1.5 text-xs">
                            <div class="flex justify-between"><span class="text-stone-400">سفارش</span><strong class="text-stone-800 num">{{ $o['order_number'] ?? '—' }}</strong></div>
                            <div class="flex justify-between"><span class="text-stone-400">خدمت</span><span class="text-stone-600">{{ $o['service'] ?? '—' }}</span></div>
                            @if ($o['coffeenet'] ?? null)
                                <div class="flex justify-between"><span class="text-stone-400">کافی‌نت</span><span class="text-stone-600">{{ $o['coffeenet'] }}</span></div>
                            @endif
                            @if ($o['operator'] ?? null)
                                <div class="flex justify-between"><span class="text-stone-400">اپراتور</span><span class="text-stone-600">{{ $o['operator'] }}</span></div>
                            @endif
                            <div class="flex justify-between"><span class="text-stone-400">مبلغ کل سفارش</span><span class="text-stone-600">{{ fa_money($o['order_total'] ?? 0) }}</span></div>
                            <div class="flex justify-between"><span class="text-stone-400">مبلغ مشمول کمیسیون</span><strong class="text-amber-600">{{ fa_money($o['commissionable'] ?? 0) }}</strong></div>
                            @if ($d['settled_at'] ?? null)
                                <div class="flex justify-between"><span class="text-stone-400">زمان تسویه</span><span class="text-stone-600 num" dir="ltr">{{ $d['settled_at'] }}</span></div>
                            @endif
                        </div>

                        <div class="space-y-2">
                            @foreach ($d['payouts'] as $p)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-stone-100 px-4 py-3" wire:key="om-payout-{{ $p['id'] }}">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="badge {{ $roleBadges[$p['role']] ?? '' }} shrink-0">{{ $p['role_label'] }}</span>
                                        <span class="text-xs text-stone-600 truncate">{{ $p['holder'] }}</span>
                                    </div>
                                    <strong class="tabular-nums text-sm text-stone-800 shrink-0">{{ fa_money($p['amount'], false) }}</strong>
                                </div>
                            @endforeach
                        </div>

                        @if ($d['snapshot']['rule'] ?? null)
                            <p class="text-[11px] text-stone-400 leading-5 rounded-xl bg-stone-50 border border-stone-100 px-3 py-2">{{ $d['snapshot']['rule'] }}</p>
                        @endif
                        @if ($d['snapshot']['salary'] ?? null)
                            <div class="flex justify-between"><span class="text-stone-400">مدل حقوق اپراتور</span><span class="text-stone-600">{{ $d['snapshot']['salary'] }}</span></div>
                        @endif
                    @endif
                @endif
            </div>
            <div class="adm-modal-foot">
                <button type="button" class="btn-ghost !py-2.5 w-full" wire:click="closeOrder">بستن</button>
            </div>
        </div>
    </div>

</div>
