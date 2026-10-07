{{-- «کیف پول سازمان» — نسخهٔ Livewire 4 [Task 6]
     ساختار و کلاس‌ها عیناً از back/org/wallet/index.blade.php حفظ شده است.
     گردش حساب به‌جای AJAX (wallet/data) سمت سرور رندر می‌شود؛ فیلتر نوع
     wire:model.live + وضعیت در URL (#[Url]) — endpoint data کنترلر دست‌نخورده.
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div>

    {{-- کارت موجودی --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="card ui-lift p-6 animate-fade-up bg-gradient-to-l from-teal-50 to-white overflow-hidden relative">
            <span class="ui-orb" data-tone="emerald" data-pos="tr" aria-hidden="true"></span>
            <span class="ui-orb" data-tone="teal" data-pos="bl" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">موجودی فعلی</p>
            <p class="mt-2 text-4xl font-extrabold tabular-nums text-teal-700 relative">{{ fa_money($wallet->balance) }}</p>
            <div class="mt-4 flex items-center gap-2 flex-wrap relative">
                <a href="{{ route('org.withdrawals.index') }}" wire:navigate class="btn-primary btn-shine no-btn-teal !py-2.5 !text-xs">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                    درخواست برداشت
                </a>
                <a href="{{ route('org.coffeenets.index') }}" wire:navigate class="btn-ghost !py-2.5 !text-xs">افزایش درآمد (معرفی کافی‌نت)</a>
            </div>
        </div>

        <div class="card ui-lift p-5 animate-fade-up delay-1 overflow-hidden relative">
            <span class="ui-orb" data-tone="emerald" data-pos="tr" aria-hidden="true"></span>
            <div class="flex items-center gap-3 relative">
                <span class="ui-chip" data-tone="ok">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-stone-500">مجموع واریزها</p>
                    <p class="mt-1 text-3xl font-extrabold tabular-nums text-emerald-600">{{ fa_money($summary['total_credit'], false) }}</p>
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
                <select wire:model.live="type" class="field !py-2 !w-auto !text-xs min-w-32" aria-label="فیلتر نوع تراکنش">
                    <option value="">همه تراکنش‌ها</option>
                    <option value="credit">فقط واریز</option>
                    <option value="debit">فقط برداشت</option>
                </select>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-panel table-modern">
                <thead>
                    <tr>
                        <th>نوع</th>
                        <th>مبلغ</th>
                        <th>موجودی بعد از تراکنش</th>
                        <th>بابت</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $t)
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
                            <td class="tabular-nums text-stone-600">{{ fa_money($t['balance_after'], false) }}</td>
                            <td class="max-w-64 truncate text-stone-500" title="{{ $t['description'] }}">
                                @if ($t['description'] !== '—') {{ $t['description'] }} @endif
                                <span class="badge bg-stone-100 text-stone-500 border border-stone-200 ms-1">{{ $t['ref'] }}</span>
                            </td>
                            <td class="text-stone-400 text-xs">{{ $t['date'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="!py-10">
                                <div class="ui-empty">
                                    <span class="ui-empty-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/></svg>
                                    </span>
                                    <p class="text-sm font-semibold text-stone-500">تراکنشی ثبت نشده است.</p>
                                    <p class="text-xs text-stone-400 mt-1">گردش کیف پول پس از اولین واریز پاداش اینجا نمایش داده می‌شود.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی — عیناً فوتر قبلی (renderPagination) --}}
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
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
