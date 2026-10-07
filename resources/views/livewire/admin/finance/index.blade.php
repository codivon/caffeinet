{{-- «گزارش مالی جامع» — نسخهٔ Livewire 4 [Task 3-e]
     کلاس‌ها/ساختار عیناً از back/admin/finance/index.blade.php حفظ شده است.
     جدول تراکنش‌ها/فیلترها Livewire؛ نمودارها همان PanelCharts فعلی با دادهٔ
     page-data (months/shares)؛ لینک خروجی CSV سمت سرور با فیلترهای فعلی ساخته می‌شود. --}}
<div>

    {{-- دادهٔ نمودارها برای JS (فاز ۹) — همان قرارداد page-data قبلی --}}
    <div id="page-data" hidden data-payload="{{ json_encode([
        'months' => $months,
        'shares' => [
            'platform' => (float) $summary['platform'],
            'organization' => (float) $summary['organization'],
            'coffeenet' => (float) $summary['coffeenet'],
            'operator' => (float) $summary['operator'],
        ],
    ]) }}"></div>

    {{-- ردیف ۱: کارت‌های کلان --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card ui-lift p-5 animate-fade-up relative overflow-hidden">
            <span class="ui-orb" data-tone="amber" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">حجم سفارش‌های پرداخت‌شده</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-stone-800 relative">{{ fa_money($summary['gross_volume'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">جمع مبالغ همهٔ سفارش‌های پرداخت‌شدهٔ فعال</p>
        </div>
        <div class="card ui-lift p-5 animate-fade-up delay-1 relative overflow-hidden">
            <span class="ui-orb" data-tone="amber" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">مبالغ مشمول کمیسیون</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-amber-600 relative">{{ fa_money($summary['commissionable'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">پایهٔ محاسبهٔ سهم‌ها</p>
        </div>
        <div class="card ui-lift p-5 animate-fade-up delay-2 relative overflow-hidden">
            <span class="ui-orb" data-tone="emerald" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">جمع تسویه‌شده</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-emerald-600 relative">{{ fa_money($summary['settled_total'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">واریزی به کیف پول‌های شبکه</p>
        </div>
        <div class="card ui-lift p-5 animate-fade-up delay-2 relative overflow-hidden">
            <span class="ui-orb" data-tone="teal" data-pos="tr" aria-hidden="true"></span>
            <p class="text-xs font-semibold text-stone-500 relative">موجودی کیف پلتفرم</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-teal-600 relative">{{ fa_money($summary['platform_wallet_balance'], false) }}</p>
            <p class="mt-1 text-[11px] text-stone-400 relative">سهم پلتفرم + باقیمانده</p>
        </div>
    </div>

    {{-- ردیف ۲: تفکیک سهم‌ها + وضعیت برداشت‌ها --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-5">
        <section class="card ui-lift p-5 animate-fade-up delay-2 lg:col-span-2 relative overflow-hidden">
            <h2 class="no-card-title text-sm font-extrabold text-stone-700 mb-4">تفکیک سهم‌های تسویه‌شده</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-4">
                    <p class="text-[11px] text-amber-700 font-semibold">پلتفرم</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums text-amber-600">{{ fa_money($summary['platform'], false) }}</p>
                    <div class="mt-2 h-1.5 rounded-full bg-amber-100 overflow-hidden">
                        <div class="h-full rounded-full bg-amber-400" style="width: {{ $summary['settled_total'] > 0 ? round($summary['platform'] / $summary['settled_total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="rounded-2xl border border-teal-100 bg-teal-50/50 p-4">
                    <p class="text-[11px] text-teal-700 font-semibold">سازمان‌ها</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums text-teal-600">{{ fa_money($summary['organization'], false) }}</p>
                    <div class="mt-2 h-1.5 rounded-full bg-teal-100 overflow-hidden">
                        <div class="h-full rounded-full bg-teal-400" style="width: {{ $summary['settled_total'] > 0 ? round($summary['organization'] / $summary['settled_total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="rounded-2xl border border-orange-100 bg-orange-50/50 p-4">
                    <p class="text-[11px] text-orange-700 font-semibold">کافی‌نت‌ها</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums text-orange-600">{{ fa_money($summary['coffeenet'], false) }}</p>
                    <div class="mt-2 h-1.5 rounded-full bg-orange-100 overflow-hidden">
                        <div class="h-full rounded-full bg-orange-400" style="width: {{ $summary['settled_total'] > 0 ? round($summary['coffeenet'] / $summary['settled_total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-4">
                    <p class="text-[11px] text-rose-700 font-semibold">اپراتورها</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums text-rose-500">{{ fa_money($summary['operator'], false) }}</p>
                    <div class="mt-2 h-1.5 rounded-full bg-rose-100 overflow-hidden">
                        <div class="h-full rounded-full bg-rose-400" style="width: {{ $summary['settled_total'] > 0 ? round($summary['operator'] / $summary['settled_total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card ui-lift p-5 animate-fade-up delay-2 relative overflow-hidden">
            <h2 class="no-card-title text-sm font-extrabold text-stone-700 mb-4">وضعیت برداشت‌ها و کیف‌ها</h2>
            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50/40 px-4 py-3">
                    <span class="text-stone-500">برداشت‌های در انتظار</span>
                    <strong class="text-amber-600 tabular-nums">{{ fa_number($withdrawals['pending_count']) }} مورد — {{ fa_money($withdrawals['pending_amount'], false) }}</strong>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-stone-100 bg-stone-50 px-4 py-3">
                    <span class="text-stone-500">جمع برداشت‌های پرداخت‌شده</span>
                    <strong class="text-stone-700 tabular-nums">{{ fa_money($withdrawals['paid_amount'], false) }}</strong>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-stone-100 bg-stone-50 px-4 py-3">
                    <span class="text-stone-500">موجودی کیف سازمان‌ها</span>
                    <strong class="text-stone-700 tabular-nums">{{ fa_number($wallets['organizations']['count']) }} کیف — {{ fa_money($wallets['organizations']['balance'], false) }}</strong>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-stone-100 bg-stone-50 px-4 py-3">
                    <span class="text-stone-500">موجودی کیف کافی‌نت‌ها</span>
                    <strong class="text-stone-700 tabular-nums">{{ fa_number($wallets['coffeenets']['count']) }} کیف — {{ fa_money($wallets['coffeenets']['balance'], false) }}</strong>
                </div>
            </div>
        </section>
    </div>

    {{-- ردیف ۲.۵: نمودارهای مالی (فاز ۹) — همان JS فعلی؛ از morph مستثنا --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-5">

        <section class="card ui-lift an-card animate-fade-up delay-2 xl:col-span-2">
            <div class="an-card-head">
                <div>
                    <h2 class="an-card-title">
                        <span class="an-title-chip" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                        </span>
                        روند ۶ ماه اخیر
                    </h2>
                    <p class="an-card-sub">سهم‌های تسویه‌شده بر حسب نقش — ماه‌های شمسی</p>
                </div>
            </div>
            <div class="an-chart" wire:ignore>
                <canvas id="fin-months" role="img" aria-label="نمودار روند ۶ ماه اخیر تسویه‌ها"></canvas>
            </div>
        </section>

        <section class="card ui-lift an-card animate-fade-up delay-2">
            <div class="an-card-head">
                <div>
                    <h2 class="an-card-title">
                        <span class="an-title-chip" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12A9 9 0 1 1 12 3a9 9 0 0 1 9 9Z"/><path d="M12 3v9l6 4"/></svg>
                        </span>
                        سهم نقش‌ها
                    </h2>
                    <p class="an-card-sub">از کل تسویه‌شده</p>
                </div>
            </div>
            <div class="an-chart" wire:ignore>
                <canvas id="fin-shares" role="img" aria-label="نمودار سهم نقش‌ها از تسویه"></canvas>
            </div>
        </section>
    </div>

    {{-- ردیف ۳: روند ۶ ماه اخیر (جدول) --}}
    <section class="card ui-lift animate-fade-up delay-2 overflow-hidden mt-5">
        <div class="adm-card-head">
            <h2 class="no-card-title text-sm font-extrabold text-stone-700">روند ۶ ماه اخیر (تسویه بر حسب ماه شمسی)</h2>
        </div>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                    <thead>
                    <tr>
                        <th>ماه</th>
                        <th>سفارش‌های پرداخت‌شده</th>
                        <th>مبالغ مشمول</th>
                        <th>پلتفرم</th>
                        <th>سازمان</th>
                        <th>کافی‌نت</th>
                        <th>اپراتور</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($months as $m)
                        <tr wire:key="month-{{ $loop->index }}">
                            <td class="font-bold text-stone-700">{{ $m['label'] }}</td>
                            <td class="tabular-nums text-stone-600">{{ fa_number($m['paid_orders']) }}</td>
                            <td class="tabular-nums text-stone-600">{{ fa_money($m['commissionable'], false) }}</td>
                            <td class="tabular-nums text-amber-600">{{ fa_money($m['platform'], false) }}</td>
                            <td class="tabular-nums text-teal-600">{{ fa_money($m['organization'], false) }}</td>
                            <td class="tabular-nums text-orange-600">{{ fa_money($m['coffeenet'], false) }}</td>
                            <td class="tabular-nums text-rose-500">{{ fa_money($m['operator'], false) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ردیف ۴: همهٔ تراکنش‌های شبکه --}}
    <section class="card ui-lift animate-fade-up delay-2 overflow-hidden mt-5">
        <div class="adm-card-head">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.400ms="q" class="field !py-2.5 !w-56 !text-xs pl-9" placeholder="جستجو در توضیح تراکنش…" aria-label="جستجوی تراکنش">
                    <svg class="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <input type="text" id="from-filter" dir="ltr" placeholder="۱۴۰۵/۰۶/۰۱" class="field !py-2.5 !text-xs !w-36 !text-center" aria-label="از تاریخ" data-jdp data-jdp-mode="gregorian" title="برای انتخاب تاریخ کلیک کنید">
                <input type="hidden" id="from-filter-g" x-on:change="$wire.set('from', $el.value)">
                <input type="text" id="to-filter" dir="ltr" placeholder="۱۴۰۵/۰۶/۳۱" class="field !py-2.5 !text-xs !w-36 !text-center" aria-label="تا تاریخ" data-jdp data-jdp-mode="gregorian" title="برای انتخاب تاریخ کلیک کنید">
                <input type="hidden" id="to-filter-g" x-on:change="$wire.set('to', $el.value)">
            </div>
            <div class="flex items-center gap-2">
                <select wire:model.live="holder" class="field !py-2.5 !w-auto min-w-32 !text-xs" aria-label="فیلتر دارنده">
                    <option value="">همهٔ دارنده‌ها</option>
                    <option value="App\Models\Organization">سازمان‌ها</option>
                    <option value="App\Models\Coffeenet">کافی‌نت‌ها</option>
                    <option value="App\Models\User">کاربران/اپراتورها</option>
                </select>
                <select wire:model.live="type" class="field !py-2.5 !w-auto min-w-28 !text-xs" aria-label="فیلتر نوع">
                    <option value="">همهٔ انواع</option>
                    <option value="credit">واریز</option>
                    <option value="debit">برداشت</option>
                </select>
                {{-- بدون wire:navigate — لینک دانلود CSV است و نباید توسط SPA رهگیری شود --}}
                <a id="export-transactions" href="{{ $exportUrl }}" class="an-export" title="خروجی CSV با فیلترهای فعلی">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    خروجی CSV
                </a>
            </div>
        </div>

        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                    <thead>
                    <tr>
                        <th>نوع</th>
                        <th>دارنده</th>
                        <th>مبلغ</th>
                        <th>مانده پس از</th>
                        <th>مرجع</th>
                        <th>توضیح</th>
                        <th>تاریخ</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $t)
                        <tr wire:key="trx-{{ $t['id'] }}">
                            <td>
                                @if ($t['type'] === 'credit')
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">واریز</span>
                                @else
                                    <span class="badge bg-rose-50 text-rose-600 border border-rose-200">برداشت</span>
                                @endif
                            </td>
                            <td><span class="text-xs text-stone-600">{{ $t['holder_label'] }}</span></td>
                            <td class="font-extrabold tabular-nums {{ $t['type'] === 'credit' ? 'text-emerald-600' : 'text-rose-500' }}">{{ fa_money($t['amount'], false) }}</td>
                            <td class="tabular-nums text-xs text-stone-500">{{ fa_money($t['balance_after'], false) }}</td>
                            <td class="text-xs text-stone-500">{{ $t['ref'] }}</td>
                            <td class="text-xs text-stone-600 max-w-64 truncate" title="{{ $t['description'] }}">{{ $t['description'] }}</td>
                            <td class="text-xs text-stone-400">{{ $t['date'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="!py-10">
                            <div class="ui-empty">
                                <span class="ui-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg></span>
                                <p class="text-sm font-semibold text-stone-500">تراکنشی یافت نشد.</p>
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

    @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/analytics.css') }}?v=9">
    @endpush

    @push('scripts')
    <script src="{{ asset('assets/js/vendor/chart.umd.min.js') }}?v=9"></script>
    <script src="{{ asset('back/assets/js/charts.js') }}?v=9"></script>
    <script>
        /* [Task 3-e] نمودارهای صفحهٔ مالی — همان PanelCharts فعلی؛
           داده از #page-data (months/shares) که کامپوننت رندر می‌کند */
        (function () {
            function renderFinCharts() {
                if (typeof window.PanelCharts === 'undefined' || !window.PanelCharts.isSupported()) { return; }

                const data = window.App ? (App.pageData() || {}) : {};
                const months = data.months || [];
                const shares = data.shares || {};

                if (document.getElementById('fin-months')) {
                    PanelCharts.bars('fin-months', {
                        labels: months.map(m => m.label_short),
                        datasets: [
                            { label: 'پلتفرم', data: months.map(m => m.platform), key: 'amber' },
                            { label: 'سازمان', data: months.map(m => m.organization), key: 'teal' },
                            { label: 'کافی‌نت', data: months.map(m => m.coffeenet), key: 'orange' },
                            { label: 'اپراتور', data: months.map(m => m.operator), key: 'rose' }
                        ],
                        stacked: true,
                        emptyMessage: 'هنوز تسویه‌ای ثبت نشده است'
                    });
                }

                if (document.getElementById('fin-shares')) {
                    PanelCharts.donut('fin-shares', {
                        labels: ['پلتفرم', 'سازمان', 'کافی‌نت', 'اپراتور'],
                        values: [shares.platform || 0, shares.organization || 0, shares.coffeenet || 0, shares.operator || 0],
                        keys: ['amber', 'teal', 'orange', 'rose'],
                        emptyMessage: 'هنوز تسویه‌ای ثبت نشده است'
                    });
                }
            }

            if (typeof window.App !== 'undefined') { renderFinCharts(); }
            else { window.addEventListener('app:ready', () => setTimeout(renderFinCharts, 60), { once: true }); setTimeout(renderFinCharts, 2500); }
        })();
    </script>
    @endpush
</div>
