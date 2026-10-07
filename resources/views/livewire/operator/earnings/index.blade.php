{{-- «درآمد و کیف پول» پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
     ساختار عیناً از back.operator.earnings.index + رندر operator/earnings/index.js
     حفظ شده؛ هر دو جدول سمت سرور رندر می‌شوند (صفحه‌بندی ۲۰تایی مثل کنترلر):
       • سابقهٔ تسویهٔ سهم‌ها → صفحه‌بندی «page»
       • لاگ تراکنش‌های کیف پول → فیلتر type (#[Url]) + صفحه‌بندی «txPage»
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه --}}
<div>

    {{-- کارت‌های درآمد --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="card ui-lift p-5 animate-fade-up relative overflow-hidden bg-gradient-to-l from-emerald-50 to-white">
            <span class="ui-orb" data-tone="emerald" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">موجودی کیف پول من</p>
            <p class="mt-2 text-4xl font-extrabold tabular-nums text-emerald-700 relative">{{ fa_money($wallet->balance) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">درآمد همهٔ کافی‌نت‌های محل کار شما در یک کیف جمع می‌شود</p>
        </div>

        <div class="card ui-lift p-5 animate-fade-up delay-1 relative overflow-hidden">
            <span class="ui-orb" data-tone="amber" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">جمع درآمد تسویه‌شده</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-amber-600 relative">{{ fa_money($summary['total'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">{{ fa_number($summary['orders']) }} سفارش تحویل‌شده</p>
        </div>

        <div class="card ui-lift p-5 animate-fade-up delay-2 relative overflow-hidden">
            <span class="ui-orb" data-tone="rose" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">درآمد این ماه</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-rose-500 relative">{{ fa_money($summary['month'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">
                @if ($summary['last_payout'])
                    آخرین تسویه: {{ jdate($summary['last_payout']->created_at)->format('Y/m/d') }}
                @else
                    هنوز تسویه‌ای ثبت نشده
                @endif
            </p>
        </div>
    </div>

    {{-- راهنمای مدل حقوق --}}
    <div class="card ui-lift p-4 mt-4 animate-fade-up delay-2 relative overflow-hidden">
        <div class="flex items-start gap-3 relative">
            <span class="ui-chip" data-tone="amber shrink-0">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </span>
            <p class="text-xs text-stone-500 leading-6">
                سهم شما از هر سفارش، بلافاصله پس از <strong class="text-stone-700">تحویل به مشتری</strong> و طبق مدل حقوق‌تان در همان کافی‌نت محاسبه و به کیف پول شما واریز می‌شود (درصدی یا مبلغ ثابت هر سفارش؛ مدل «ماهیانه» فقط در لاگ حقوق مدیر کافی‌نت ثبت می‌شود).
            </p>
        </div>
    </div>

    {{-- سابقهٔ تسویه‌ها --}}
    <section class="card ui-lift mt-4 animate-fade-up delay-2 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex flex-wrap items-center gap-3 justify-between">
            <h2 class="no-card-title text-sm font-extrabold text-stone-700">سابقهٔ تسویهٔ سهم‌ها</h2>
        </div>

        <div class="table-wrap">
            <table class="table-panel table-modern">
                <thead>
                    <tr>
                        <th>سفارش</th>
                        <th>خدمت</th>
                        <th>کافی‌نت</th>
                        <th>سهم من</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($payoutRows as $p)
                    <tr wire:key="payout-{{ $p['id'] }}">
                        <td class="font-bold text-stone-800 num">{{ $p['order_number'] }}</td>
                        <td class="text-xs text-stone-500">{{ $p['service'] }}</td>
                        <td class="text-xs text-stone-500">{{ $p['coffeenet'] }}</td>
                        <td class="font-extrabold tabular-nums text-emerald-600">{{ fa_money($p['amount'], false) }} <span class="text-[10px] font-normal text-stone-400">تومان</span></td>
                        <td class="text-xs text-stone-400">{{ $p['date'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="!py-10">
                            <div class="ui-empty">
                                <span class="ui-empty-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v10"/><path d="m8 6.5 4-4 4 4"/><path d="M20 16v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3"/></svg>
                                </span>
                                <p class="text-sm font-semibold text-stone-500">هنوز تسویه‌ای ثبت نشده است.</p>
                                <p class="text-xs text-stone-400 mt-1">با تحویل اولین سفارشِ پرداخت‌شده، سهم شما به کیف پول واریز و اینجا نمایش داده می‌شود.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
            {{ $payoutRows->links('livewire.operator.partials.pagination') }}
        </div>
    </section>

    {{-- لاگ تراکنش‌های کیف پول (درخواست بازخوردی) --}}
    <section class="card ui-lift mt-4 animate-fade-up delay-3 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex flex-wrap items-center gap-3 justify-between">
            <h2 class="no-card-title text-sm font-extrabold text-stone-700">لاگ تراکنش‌های کیف پول</h2>
            <div class="flex items-center gap-1.5" role="tablist" aria-label="فیلتر نوع تراکنش">
                <button type="button" wire:click="filterByType('')"
                        class="btn-ghost !py-2 !px-4 !text-xs {{ $type === '' ? 'pg-active' : '' }}"
                        aria-pressed="{{ $type === '' ? 'true' : 'false' }}">همه</button>
                <button type="button" wire:click="filterByType('credit')"
                        class="btn-ghost !py-2 !px-4 !text-xs {{ $type === 'credit' ? 'pg-active' : '' }}"
                        aria-pressed="{{ $type === 'credit' ? 'true' : 'false' }}">واریز</button>
                <button type="button" wire:click="filterByType('debit')"
                        class="btn-ghost !py-2 !px-4 !text-xs {{ $type === 'debit' ? 'pg-active' : '' }}"
                        aria-pressed="{{ $type === 'debit' ? 'true' : 'false' }}">برداشت</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-panel table-modern">
                <thead>
                    <tr>
                        <th>نوع</th>
                        <th>مبلغ</th>
                        <th>موجودی پس از</th>
                        <th>مرجع</th>
                        <th>شرح</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($txRows as $t)
                    <tr wire:key="tx-{{ $t['id'] }}">
                        <td>
                            @if ($t['type'] === 'credit')
                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">واریز</span>
                            @else
                                <span class="badge bg-rose-50 text-rose-600 border border-rose-200">برداشت</span>
                            @endif
                        </td>
                        <td class="font-extrabold tabular-nums {{ $t['type'] === 'credit' ? 'text-emerald-600' : 'text-rose-500' }}">
                            {{ $t['type'] === 'credit' ? '+' : '−' }} {{ fa_money($t['amount'], false) }} <span class="text-[10px] font-normal text-stone-400">تومان</span>
                        </td>
                        <td class="text-xs text-stone-600 tabular-nums">{{ fa_money($t['balance_after'], false) }}</td>
                        <td class="text-xs text-stone-500">{{ $t['ref'] }}</td>
                        <td class="text-xs text-stone-400 max-w-64 truncate" title="{{ $t['description'] }}">{{ $t['description'] }}</td>
                        <td class="text-xs text-stone-400 whitespace-nowrap">{{ $t['date'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="!py-10">
                            <div class="ui-empty">
                                <span class="ui-empty-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/></svg>
                                </span>
                                <p class="text-sm font-semibold text-stone-500">تراکنشی ثبت نشده است.</p>
                                <p class="text-xs text-stone-400 mt-1">واریز سهم سفارش‌ها و برداشت‌های شما با جزئیات در این جدول نمایش داده می‌شود.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
            {{ $txRows->links('livewire.operator.partials.pagination', ['pageName' => 'txPage']) }}
        </div>
    </section>
</div>
