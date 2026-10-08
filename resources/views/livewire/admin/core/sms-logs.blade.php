{{-- لاگ پیامک‌های ارسال‌شده — کامپوننت Livewire [Task 3-b]
     فیلترها/جستجو/صفحه‌بندی سمت سرور؛ کلاس‌ها عیناً مطابق نسخهٔ قبلی --}}
<div class="sl-stack">

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pages/sms-logs.css') }}?v=17">
@endpush

    {{-- ================== هدر قهرمان ================== --}}
    <div class="card ui-lift animate-fade-up sl-hero">
        <div class="sl-hero-main">
            <span class="sl-hero-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M8 8h8"/><path d="M8 12h4"/><path d="M8 16h2"/></svg>
            </span>
            <div class="flex-1 min-w-0">
                <h1 class="sl-hero-title">لاگ پیامک‌های ارسال‌شده</h1>
                <p class="sl-hero-sub">
                    گزارش کامل تمام پیامک‌های سیستم — هر ارسال (موفق یا ناموفق) از هر پرووایدری
                    با <b>متن، قالب، وضعیت و پاسخ پرووایدر</b> اینجا ثبت و قابل بررسی است.
                </p>
            </div>
            <div class="sl-hero-side">
                <span class="badge {{ $provider === 'log' ? 'bg-stone-100 text-stone-500 border border-stone-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}" title="پرووایدر فعال فعلی">
                    پرووایدر فعال: {{ $providerLabel }}
                </span>
                <a href="{{ route('admin.sms-templates.index') }}" wire:navigate class="btn-ghost !py-2 !px-4 !text-xs ui-press">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/></svg>
                    مرکز پیامک
                </a>
            </div>
        </div>

        {{-- آمار کلی --}}
        <div class="sl-stats">
            <div class="sl-stat">
                <span class="sl-stat-num">{{ fa_number($stats['total']) }}</span>
                <span class="sl-stat-label">کل پیامک‌ها</span>
            </div>
            <div class="sl-stat sl-stat--ok">
                <span class="sl-stat-num">{{ fa_number($stats['sent']) }}</span>
                <span class="sl-stat-label">ارسال موفق</span>
            </div>
            <div class="sl-stat sl-stat--err">
                <span class="sl-stat-num">{{ fa_number($stats['failed']) }}</span>
                <span class="sl-stat-label">ناموفق / خطا</span>
            </div>
            <div class="sl-stat sl-stat--today">
                <span class="sl-stat-num">{{ fa_number($stats['today']) }}</span>
                <span class="sl-stat-label">امروز</span>
            </div>
        </div>
    </div>

    {{-- ================== v29 — حذف دوره‌ای لاگ پیامک ================== --}}
    @if ($canSystem)
    <div class="card ui-lift animate-fade-up lg-ret" style="animation-delay:.03s" data-scope="sms_logs">
        <div class="lg-ret-main">
            <span class="lg-ret-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
            </span>
            <div class="flex-1 min-w-0">
                <h2 class="lg-ret-title">حذف دوره‌ای لاگ پیامک</h2>
                <p class="lg-ret-sub">
                    ردیف‌های قدیمی‌تر از <b>{{ fa_number($retentionDays) }}</b> روز، هر شب ساعت ۰۳:۳۰ به‌صورت خودکار حذف می‌شوند (از قدیمی‌ترین به جدید).
                    <span class="badge {{ $oldCount > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-stone-100 text-stone-500 border border-stone-200' }}">
                        {{ fa_number($oldCount) }} ردیف قدیمی‌تر از نگهداشت
                    </span>
                </p>
                @error('retention')
                    <p class="err text-[11px] text-rose-500 mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="lg-ret-actions">
            <label class="sr-only" for="lg-ret-days">نگهداشت لاگ پیامک (روز)</label>
            <div class="lg-ret-input">
                <input id="lg-ret-days" type="number" min="1" max="3650" class="field !py-2.5 font-mono" wire:model.live="retentionDays">
                <span class="lg-ret-unit">روز</span>
            </div>
            <button type="button" wire:click="saveRetention" class="btn-ghost !py-2.5 !px-4 !text-xs ui-press" wire:loading.attr="disabled" wire:target="saveRetention">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                ذخیرهٔ نگهداشت
            </button>
            <button type="button" wire:click="cleanupOld"
                    @if ($oldCount === 0) disabled title="ردیف قدیمی‌تری نیست" @endif
                    x-data @click="window.PanelUI ? PanelUI.confirm({
                        title: 'پاکسازی لاگ پیامک‌های قدیمی؟',
                        desc: 'همهٔ ردیف‌های قدیمی‌تر از نگهداشت فعلی، از قدیمی‌ترین حذف می‌شوند. این عمل قابل بازگشت نیست.',
                        okText: 'حذف قدیمی‌ها', danger: true, icon: 'question',
                    }, () => $wire.cleanupOld()) : (confirm('پاکسازی لاگ پیامک‌های قدیمی؟') && $wire.cleanupOld())"
                    class="btn-ghost !py-2.5 !px-4 !text-xs ui-press !text-red-600">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                پاکسازی قدیمی‌ها الان
            </button>
        </div>
    </div>

    @endif

    {{-- ================== فیلترها + جدول ================== --}}
    <section class="card ui-lift animate-fade-up overflow-hidden" style="animation-delay:.06s">

        <div class="adm-card-head sl-filters">
            <div class="relative flex-1 min-w-40 max-w-xs">
                <input id="f-q" type="search" class="field !py-2.5 !pl-9" placeholder="جستجو: موبایل، متن، کلید قالب..." aria-label="جستجو در لاگ پیامک" wire:model.live.debounce.400ms="q">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <select id="f-status" class="field !py-2.5 !w-auto !text-xs cursor-pointer" aria-label="وضعیت ارسال" wire:model.live="status">
                <option value="">همه وضعیت‌ها</option>
                <option value="sent" @selected($status === 'sent')>ارسال موفق</option>
                <option value="failed" @selected($status === 'failed')>ناموفق</option>
            </select>

            <select id="f-provider" class="field !py-2.5 !w-auto !text-xs cursor-pointer" aria-label="پرووایدر" wire:model.live="provider">
                <option value="">همه پرووایدرها</option>
                @foreach ($providers as $key => $label)
                    <option value="{{ $key }}" @selected($provider === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <input id="f-from" type="text" dir="ltr" placeholder="از تاریخ ۱۴۰۵/۰۶/۰۱" class="field num !py-2.5 !w-auto max-w-40 !text-center"
                   aria-label="از تاریخ" data-jdp title="برای انتخاب تاریخ کلیک کنید" wire:model.live="from">
            <input id="f-to" type="text" dir="ltr" placeholder="تا تاریخ ۱۴۰۵/۰۶/۳۱" class="field num !py-2.5 !w-auto max-w-40 !text-center"
                   aria-label="تا تاریخ" data-jdp title="برای انتخاب تاریخ کلیک کنید" wire:model.live="to">

            <button type="button" wire:click="$refresh" class="btn-ghost !py-2.5 !px-4 !text-xs">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
                اعمال فیلتر
            </button>

            <button type="button" wire:click="resetFilters" class="btn-ghost !py-2.5 !px-3 !text-xs" title="پاک کردن فیلترها" aria-label="پاک کردن فیلترها">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            </button>
        </div>

        {{-- جدول --}}
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                <thead>
                    <tr>
                        <th>موبایل</th>
                        <th>قالب</th>
                        <th>متن پیام</th>
                        <th>پرووایدر</th>
                        <th>وضعیت</th>
                        <th>زمان</th>
                        <th class="text-center">جزئیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr wire:key="sllog-{{ $log->id }}">
                            <td class="font-mono text-xs text-stone-600" dir="ltr">{{ $log->mobile }}</td>
                            <td>
                                @if ($log->template_key)
                                    <span class="badge bg-amber-50 text-amber-700 border border-amber-200">{{ $titles[$log->template_key] ?? $log->template_key }}</span>
                                @else
                                    <span class="text-stone-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="max-w-56 truncate text-xs text-stone-500" title="{{ $log->message }}">{{ $log->message }}</td>
                            <td><span class="badge bg-stone-100 text-stone-500 border border-stone-200">{{ \App\Livewire\Admin\Core\SmsLogs::knownProviders()[$log->provider] ?? $log->provider }}{{ ($log->response['mode'] ?? 'plain') === 'pattern' ? ' · پترنی' : '' }}</span></td>
                            <td>
                                @if ($log->status === 'sent')
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">ارسال موفق</span>
                                @else
                                    <span class="badge bg-rose-50 text-rose-700 border border-rose-200">ناموفق</span>
                                @endif
                            </td>
                            <td class="text-[11px] text-stone-500" dir="ltr">{{ $log->created_at ? fa_date($log->created_at, 'Y/m/d H:i') : '—' }}</td>
                            <td class="text-center">
                                <button type="button" wire:click="showDetail({{ $log->id }})" class="act-detail ui-row-btn" title="جزئیات" aria-label="مشاهده جزئیات پیامک">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 11h.01"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-10 text-stone-400 text-xs">موردی یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="adm-table-foot text-xs text-stone-500">
            <span>{{ fa_number($logs->total()) }} رکورد @if ($logs->lastPage() > 1)— صفحه {{ fa_number($logs->currentPage()) }} از {{ fa_number($logs->lastPage()) }}@endif</span>
            @if ($logs->lastPage() > 1)
                <div class="flex gap-2">
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" @if ($logs->onFirstPage()) disabled @endif wire:click="previousPage">قبلی</button>
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" @if ($logs->hasMorePages()) @else disabled @endif wire:click="nextPage">بعدی</button>
                </div>
            @endif
        </div>
    </section>

{{-- ================== مودال جزئیات (درون ریشهٔ واحد کامپوننت — Livewire فقط یک ریشه می‌پذیرد) ================== --}}
<div class="ui-modal-backdrop {{ $detail === null ? 'hidden' : 'flex' }}">
    <div class="absolute inset-0" wire:click="closeDetail" aria-hidden="true"></div>
    <div class="ui-modal adm-modal-md adm-modal-text-start" data-tone="info" role="dialog" aria-modal="true" aria-labelledby="sl-d-title">
        <div class="adm-modal-head">
            <h3 class="text-sm font-extrabold text-stone-800" id="sl-d-title">جزئیات پیامک</h3>
            <button type="button" class="adm-modal-x" wire:click="closeDetail" aria-label="بستن">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div class="adm-modal-body space-y-4">
            {{-- ردیف اطلاعات --}}
            <div class="sl-d-grid">
                <div>
                    <p class="lbl !text-[10px]">موبایل گیرنده</p>
                    <p class="sl-d-val font-mono" dir="ltr">{{ $detail['mobile'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="lbl !text-[10px]">پرووایدر / روش</p>
                    <p class="sl-d-val">{{ $detail['provider'] ?? '—' }} @if (($detail['mode'] ?? '') === 'pattern')· پترنی @endif</p>
                </div>
                <div>
                    <p class="lbl !text-[10px]">قالب</p>
                    <p class="sl-d-val">{{ $detail['template'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="lbl !text-[10px]">وضعیت و زمان</p>
                    <p class="sl-d-val">{{ ($detail['status'] ?? '') === 'sent' ? 'ارسال موفق' : 'ناموفق' }} — {{ $detail['created_fa'] ?? '—' }}</p>
                </div>
            </div>

            {{-- متن کامل --}}
            <div>
                <p class="lbl !text-[10px]">متن کامل پیامک</p>
                <div class="sl-d-msg"><p>{{ $detail['message'] ?? '—' }}</p></div>
            </div>

            {{-- پاسخ پرووایدر --}}
            <div>
                <p class="lbl !text-[10px]">پاسخ پرووایدر (JSON)</p>
                <pre class="sl-d-json" dir="ltr"><code>{{ $detail['response'] ?? '—' }}</code></pre>
            </div>
        </div>

        <div class="adm-modal-foot">
            <button type="button" class="btn-ghost ui-press !py-3 !px-5" wire:click="closeDetail">بستن</button>
        </div>
    </div>
</div>

</div>
