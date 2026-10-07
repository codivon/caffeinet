{{-- «کیف پول کافی‌نت» — نسخهٔ Livewire 4 [Task 4]
     کارت‌ها/جدول عیناً از back/coffeenet/wallet/index.blade.php حفظ شده؛
     جدول سمت سرور با فیلتر نوع wire:model.live + WithPagination (۲۰تایی) و
     سریال‌سازی عیناً از WalletController@data. --}}
<div>

    {{-- کارت‌های موجودی --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="card ui-lift p-6 animate-fade-up bg-gradient-to-l from-orange-50 to-white overflow-hidden relative">
            <span class="ui-orb" data-tone="amber" data-pos="tr" aria-hidden="true"></span>
            <span class="ui-orb" data-tone="orange" data-pos="bl" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">موجودی فعلی</p>
            <p class="mt-2 text-4xl font-extrabold tabular-nums text-orange-700 relative">{{ fa_money($wallet->balance) }}</p>
            <div class="mt-4 flex items-center gap-2 flex-wrap relative">
                <a href="{{ route('coffeenet.withdrawals.index', ['coffeenet' => $coffeenet]) }}" wire:navigate class="btn-primary btn-shine !py-2.5 !text-xs">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                    درخواست برداشت
                </a>
            </div>
        </div>

        <div class="card ui-lift p-5 animate-fade-up delay-1 overflow-hidden relative">
            <span class="ui-orb" data-tone="emerald" data-pos="tr" aria-hidden="true"></span>
            <div class="flex items-center gap-3 relative">
                <span class="ui-chip" data-tone="ok">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-stone-500">درآمد کمیسیون سفارش‌ها</p>
                    <p class="mt-1 text-3xl font-extrabold tabular-nums text-emerald-600">{{ fa_money($summary['commission'], false) }}</p>
                </div>
            </div>
        </div>

        <div class="card ui-lift p-5 animate-fade-up delay-2 overflow-hidden relative">
            <span class="ui-orb" data-tone="rose" data-pos="tr" aria-hidden="true"></span>
            <div class="flex items-center gap-3 relative">
                <span class="ui-chip" data-tone="rose">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-stone-500">مجموع برداشت‌ها و کسرها</p>
                    <p class="mt-1 text-3xl font-extrabold tabular-nums text-rose-500">{{ fa_money($summary['total_debit'], false) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول تراکنش‌ها --}}
    <section class="card ui-lift mt-5 animate-fade-up delay-2 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex flex-wrap items-center gap-3 justify-between">
            <h2 class="no-card-title text-sm font-extrabold text-stone-700">گردش حساب</h2>
            <div class="flex items-center gap-2">
                <select class="field !py-2 !w-auto !text-xs min-w-32" aria-label="فیلتر نوع تراکنش" wire:model.live="type">
                    <option value="">همه تراکنش‌ها</option>
                    <option value="credit">فقط واریز</option>
                    <option value="debit">فقط برداشت</option>
                </select>
            </div>
        </div>

        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                <thead>
                    <tr>
                        <th>نوع</th>
                        <th>مبلغ</th>
                        <th>مانده پس از</th>
                        <th>مرجع</th>
                        <th>توضیح</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="tx-{{ $row['id'] }}">
                            <td>
                                <span class="badge {{ $row['type'] === 'credit' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }}">{{ $row['type_label'] }}</span>
                            </td>
                            <td class="font-extrabold tabular-nums {{ $row['type'] === 'credit' ? 'text-emerald-600' : 'text-rose-500' }}">{{ fa_money($row['amount']) }}</td>
                            <td class="tabular-nums text-stone-600">{{ fa_money($row['balance_after']) }}</td>
                            <td class="text-xs text-stone-500">{{ $row['ref'] }}</td>
                            <td class="max-w-56 truncate text-xs text-stone-400" title="{{ $row['description'] }}">{{ $row['description'] }}</td>
                            <td class="text-xs text-stone-400 whitespace-nowrap">{{ $row['date'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-10 text-stone-400 text-xs">تراکنشی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی — عیناً فوتر قبلی --}}
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between gap-3 flex-wrap text-xs text-stone-500">
            @if ($rows->lastPage() > 1)
                <span>{{ fa_number($rows->total()) }} تراکنش — صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}</span>
                <div class="flex gap-2">
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="previousPage" @disabled($rows->onFirstPage())>قبلی</button>
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="nextPage" @disabled(! $rows->hasMorePages())>بعدی</button>
                </div>
            @else
                <span>{{ fa_number($rows->total()) }} تراکنش</span>
            @endif
        </div>
    </section>

</div>
