{{-- «حقوق و دستمزد» پنل کافی‌نت — نسخهٔ Livewire 4 [Task 4]
     کارت‌ها/فرم/فیلترها عیناً از back/coffeenet/salaries/index.blade.php حفظ شده؛
     جدول لاگ‌ها سمت سرور (wire:model.live + WithPagination) با سریال‌سازی عیناً
     از SalariesController@data. ثبت پرداخت با همان endpoint کنترلر
     (POST coffeenet.salaries.store) از طریق App.ajax + $wire.$refresh. --}}
<div x-data="coffeenetSalariesPage()">

    {{-- کارت‌های خلاصه --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        <div class="card ui-lift p-5 animate-fade-up bg-gradient-to-l from-emerald-50 to-white overflow-hidden relative">
            <span class="ui-orb" data-tone="emerald" data-pos="tr" aria-hidden="true"></span>
            <div class="flex items-center gap-3 relative">
                <span class="ui-chip" data-tone="ok">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-stone-500">پرداختی {{ $currentPeriodLabel }}</p>
                    <p class="mt-1 text-3xl font-extrabold tabular-nums text-emerald-600">{{ fa_money($summary['current_total']) }}</p>
                </div>
            </div>
        </div>

        <div class="card ui-lift p-5 animate-fade-up delay-1 overflow-hidden relative">
            <span class="ui-orb" data-pos="tr" aria-hidden="true"></span>
            <div class="flex items-center gap-3 relative">
                <span class="ui-chip" data-tone="stone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-stone-500">پرداختی {{ $lastPeriodLabel }}</p>
                    <p class="mt-1 text-3xl font-extrabold tabular-nums text-stone-500">{{ fa_money($summary['last_total']) }}</p>
                </div>
            </div>
        </div>

        <div class="card ui-lift p-5 animate-fade-up delay-2 overflow-hidden relative">
            <span class="ui-orb" data-tone="amber" data-pos="tr" aria-hidden="true"></span>
            <div class="flex items-center gap-3 relative">
                <span class="ui-chip">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-stone-500">کل رکوردهای پرداخت</p>
                    <p class="mt-1 text-3xl font-extrabold tabular-nums text-amber-700">{{ fa_number($summary['logs_count']) }}</p>
                </div>
            </div>
        </div>

        <div class="card ui-lift p-5 animate-fade-up delay-3 overflow-hidden relative">
            <span class="ui-orb" data-tone="teal" data-pos="tr" aria-hidden="true"></span>
            <div class="flex items-center gap-3 relative">
                <span class="ui-chip" data-tone="teal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-stone-500">دریافت‌کنندگان این ماه</p>
                    <p class="mt-1 text-3xl font-extrabold tabular-nums text-teal-600">{{ fa_number($summary['paid_staff']) }} <span class="text-sm font-bold">نفر</span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-5">

        {{-- فرم ثبت پرداخت --}}
        <section class="card ui-lift p-5 xl:col-span-1 animate-fade-up delay-1 h-fit overflow-hidden relative">
            <span class="ui-orb" data-tone="amber" data-pos="bl" aria-hidden="true"></span>
            <h2 class="no-card-title text-sm font-extrabold text-stone-700 relative">ثبت پرداخت جدید</h2>
            <p class="text-[11px] text-stone-400 mt-1 mb-4 leading-5 relative">پرداختی‌های حقوق/اضافه‌کار/پاداش ثبت می‌شوند (دفتر گزارشی کافی‌نت) و مستقیماً از کیف پول کم نمی‌شوند.</p>

            <form class="space-y-4" novalidate x-on:submit.prevent="pay()">
                <p class="text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-3.5 py-2.5 leading-6" x-show="formError" x-text="formError" x-cloak></p>

                <div>
                    <label class="lbl" for="s-user">کارمند <span class="text-rose-500">*</span></label>
                    <select id="s-user" class="field" x-model="form.user_id" required>
                        <option value="">— انتخاب کارمند —</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s['user_id'] }}">{{ $s['full_name'] }}{{ $s['salary'] ? ' — '.$s['salary']['type_label'] : '' }}</option>
                        @endforeach
                    </select>
                    <p class="err text-[11px] text-rose-500 mt-1" data-for="user_id" x-show="errors.user_id" x-text="errors.user_id?.[0]" x-cloak></p>
                </div>

                <div>
                    <label class="lbl" for="s-period">دوره (ماه) <span class="text-rose-500">*</span></label>
                    <select id="s-period" class="field" x-model="form.period" required>
                        @foreach ($monthOptions as $m)
                            <option value="{{ $m['value'] }}">{{ $m['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="err text-[11px] text-rose-500 mt-1" data-for="period" x-show="errors.period" x-text="errors.period?.[0]" x-cloak></p>
                </div>

                <div>
                    <label class="lbl" for="s-type">نوع پرداخت <span class="text-rose-500">*</span></label>
                    <select id="s-type" class="field" x-model="form.type" required>
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="lbl" for="s-amount">مبلغ (تومان) <span class="text-rose-500">*</span></label>
                    <input id="s-amount" type="number" min="1" step="0.01" dir="ltr" class="field" placeholder="مثلاً 5000000" x-model="form.amount" required>
                    <p class="err text-[11px] text-rose-500 mt-1" data-for="amount" x-show="errors.amount" x-text="errors.amount?.[0]" x-cloak></p>
                </div>

                <div>
                    <label class="lbl" for="s-desc">توضیحات <span class="text-stone-400 font-normal">(اختیاری)</span></label>
                    <textarea id="s-desc" rows="2" class="field !py-2.5" maxlength="500" placeholder="مثلاً: حقوق مهر + دو روز اضافه‌کار" x-model="form.description"></textarea>
                    <p class="err text-[11px] text-rose-500 mt-1" data-for="description" x-show="errors.description" x-text="errors.description?.[0]" x-cloak></p>
                </div>

                <button type="submit" id="btn-pay" class="btn-primary btn-shine w-full !py-3 !text-xs" :disabled="busy">
                    <span x-show="!busy" class="inline-flex items-center gap-2">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                        ثبت پرداخت
                    </span>
                    <span x-show="busy" x-cloak class="inline-flex items-center gap-2">
                        <span class="size-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        در حال ثبت…
                    </span>
                </button>
            </form>
        </section>

        {{-- جدول لاگ‌ها --}}
        <section class="card ui-lift xl:col-span-2 animate-fade-up delay-2 overflow-hidden">
            <div class="px-5 py-4 border-b border-stone-100 flex flex-wrap items-center gap-2.5 justify-between">
                <h2 class="no-card-title text-sm font-extrabold text-stone-700">تاریخچه پرداخت‌ها</h2>
                <div class="flex items-center gap-2 flex-wrap">
                    <select class="field !py-2 !w-auto !text-xs min-w-36" aria-label="فیلتر کارمند" wire:model.live="userId">
                        <option value="">همه کارمندان</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s['user_id'] }}">{{ $s['full_name'] }}</option>
                        @endforeach
                    </select>
                    <select class="field !py-2 !w-auto !text-xs min-w-32" aria-label="فیلتر نوع" wire:model.live="type">
                        <option value="">همه انواع</option>
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select class="field !py-2 !w-auto !text-xs min-w-32" aria-label="فیلتر دوره" wire:model.live="period">
                        <option value="">همه دوره‌ها</option>
                        @foreach ($monthOptions as $m)
                            <option value="{{ $m['value'] }}">{{ $m['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-wrap">
                <div class="overflow-x-auto">
                    <table class="table-panel table-modern">
                        <thead>
                        <tr>
                            <th>کارمند</th>
                            <th>دوره</th>
                            <th>نوع</th>
                            <th>مبلغ</th>
                            <th>توضیحات</th>
                            <th>ثبت‌کننده</th>
                            <th>زمان ثبت</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($rows as $row)
                            <tr wire:key="salary-{{ $row['id'] }}">
                                <td class="text-xs font-semibold text-stone-700">{{ $row['user']['full_name'] }}</td>
                                <td class="text-xs text-stone-500">{{ $row['period_label'] }}</td>
                                <td>
                                    <span class="badge {{ ['monthly' => 'bg-stone-50 text-stone-600 border border-stone-200', 'overtime' => 'bg-amber-50 text-amber-700 border border-amber-200', 'bonus' => 'bg-emerald-50 text-emerald-700 border border-emerald-200', 'manual' => 'bg-stone-50 text-stone-500 border border-stone-200'][$row['type']] ?? 'bg-stone-50 text-stone-500 border border-stone-200' }}">{{ $row['type_label'] }}</span>
                                </td>
                                <td class="text-xs font-extrabold tabular-nums text-stone-700">{{ fa_money($row['amount']) }}</td>
                                <td class="max-w-52 truncate text-xs text-stone-400" title="{{ $row['description'] }}">{{ $row['description'] ?? '—' }}</td>
                                <td class="text-xs text-stone-500">{{ $row['logged_by'] }}</td>
                                <td class="text-[11px] text-stone-400">{{ $row['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="!py-10 text-center text-stone-400 text-xs">رکوردی ثبت نشده است.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- صفحه‌بندی — عیناً فوتر قبلی --}}
            <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between gap-3 flex-wrap text-xs text-stone-500">
                @if ($rows->lastPage() > 1)
                    <span>{{ fa_number($rows->total()) }} رکورد — صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}</span>
                    <div class="flex gap-2">
                        <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="previousPage" @disabled($rows->onFirstPage())>قبلی</button>
                        <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="nextPage" @disabled(! $rows->hasMorePages())>بعدی</button>
                    </div>
                @else
                    <span>{{ fa_number($rows->total()) }} رکورد</span>
                @endif
            </div>
        </section>
    </div>

</div>

@script
<script>
/* پورت منطق فرم ثبت پرداخت از back/assets/js/pages/coffeenet/salaries/index.js —
   همان endpoint (POST coffeenet.salaries.store) و همان پیام‌ها/۴۲۲ */
function coffeenetSalariesPage() {
    return {
        busy: false,
        formError: '',
        errors: {},
        form: {
            user_id: '',
            period: '{{ $currentPeriod }}',
            type: 'monthly',
            amount: '',
            description: '',
        },
        base: '/coffeenet/{{ $coffeenet->id }}/salaries',

        async pay() {
            this.formError = '';
            this.errors = {};

            if (!this.form.user_id) {
                this.errors = { user_id: ['انتخاب کارمند الزامی است.'] };
                this.formError = 'انتخاب کارمند الزامی است.';
                return;
            }
            if (!this.form.amount || +this.form.amount < 1) {
                this.errors = { amount: ['مبلغ باید بیش از صفر باشد.'] };
                this.formError = 'مبلغ باید بیش از صفر باشد.';
                return;
            }

            this.busy = true;
            try {
                const res = await App.ajax(this.base, {
                    method: 'POST',
                    body: {
                        user_id: +this.form.user_id,
                        period: this.form.period,
                        type: this.form.type,
                        amount: this.form.amount,
                        description: this.form.description.trim() || null,
                    },
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    App.toast(data.message || 'پرداخت ثبت شد.', 'success');
                    this.form.amount = '';
                    this.form.description = '';
                    $wire.$refresh();
                } else if (res.status === 422 && data.errors) {
                    this.errors = data.errors;
                    this.formError = data.message || Object.values(data.errors)[0][0];
                } else {
                    this.formError = data.message || 'خطا در ثبت پرداخت.';
                }
            } catch {
                this.formError = 'ارتباط با سرور برقرار نشد.';
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endscript
