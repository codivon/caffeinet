{{-- لاگ سیستمی لاراول — کامپوننت Livewire [Task 3-b]
     فیلتر سطح/جستجو/صفحه‌بندی سمت سرور؛ کلاس‌ها عیناً مطابق نسخهٔ قبلی --}}
<div class="slg-stack">

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pages/system-logs.css') }}?v=2">
@endpush

    {{-- ================== هدر قهرمان ================== --}}
    <div class="card ui-lift animate-fade-up slg-hero">
        <div class="slg-hero-main">
            <span class="slg-hero-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 17l6-6-6-6"/><path d="M12 19h8"/></svg>
            </span>
            <div class="flex-1 min-w-0">
                <h1 class="slg-hero-title">لاگ سیستمی لاراول</h1>
                <p class="slg-hero-sub">
                    رخدادهای ثبت‌شده در فایل‌های لاگ سامانه (storage/logs) — خطاها، اخطارها و اطلاعات
                    به تفکیک <b>سطح</b> دسته‌بندی شده‌اند؛ با کلیک روی هر ردیف، جزئیات کامل و stack trace دیده می‌شود.
                </p>
            </div>
            <div class="slg-hero-side">
                <a href="{{ route('admin.settings.edit') }}" wire:navigate class="btn-ghost !py-2 !px-4 !text-xs ui-press">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    بازگشت به تنظیمات
                </a>
            </div>
        </div>

        {{-- آمار فایل انتخابی --}}
        @if ($summary)
            <div class="slg-stats">
                <div class="slg-stat">
                    <span class="slg-stat-num">{{ $summary['count_fa'] }}</span>
                    <span class="slg-stat-label">ورودی در این فایل</span>
                </div>
                <div class="slg-stat">
                    <span class="slg-stat-num">{{ $summary['size_fa'] }}</span>
                    <span class="slg-stat-label">حجم فایل (کیلوبایت)</span>
                </div>
                <div class="slg-stat slg-stat--time">
                    <span class="slg-stat-num">{{ $summary['modified_fa'] }}</span>
                    <span class="slg-stat-label">آخرین نوشت در فایل</span>
                </div>
            </div>
        @endif
    </div>

    @if (count($files) === 0)
        {{-- حالت خالی — هیچ فایل لاگی نیست --}}
        <div class="card ui-lift animate-fade-up slg-empty">
            <span class="slg-empty-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
            </span>
            <h2 class="slg-empty-title">هیچ فایل لاگی وجود ندارد</h2>
            <p class="slg-empty-sub">
                فعلاً هیچ خطا یا رخدادی در storage/logs ثبت نشده است — این خودش خبر خوبی است!
                با اولین رخداد (مثلاً خطا یا اخطار) فایل <span dir="ltr" class="font-mono">laravel.log</span> ساخته می‌شود.
            </p>
            <a href="{{ route('admin.settings.edit') }}" wire:navigate class="btn-primary btn-shine ui-press !py-2.5 px-7 !text-xs">بازگشت به تنظیمات</a>
        </div>
    @else

    {{-- ================== کارت فایل‌ها + عملیات ================== --}}
    <div class="card ui-lift animate-fade-up slg-files" style="animation-delay:.04s">
        <div class="slg-files-head">
            <b>فایل‌های لاگ</b>
            <span>{{ fa_number(count($files)) }} فایل — با کلیک انتخاب می‌شود</span>
        </div>
        <div class="slg-files-list">
            @foreach ($files as $f)
                <button type="button" wire:click="selectFile('{{ $f['name'] }}')" wire:key="slgfile-{{ $f['name'] }}"
                   class="slg-file {{ $selected && $selected['name'] === $f['name'] ? 'slg-file--on' : '' }}">
                    <span class="slg-file-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                    </span>
                    <span class="slg-file-body">
                        <b dir="ltr">{{ $f['name'] }}</b>
                        <i>{{ fa_number($f['size'] / 1024) }} KB · آخرین نوشت {{ fa_date($f['modified_carbon'], 'Y/m/d H:i') }}</i>
                    </span>
                    @if ($selected && $selected['name'] === $f['name'])
                        <span class="slg-file-on-badge">در حال نمایش</span>
                    @endif
                </button>
            @endforeach
        </div>

        @if ($selected)
            <div class="slg-file-actions">
                <span class="slg-file-actions-info">
                    فایل فعال: <b dir="ltr">{{ $selected['name'] }}</b>
                </span>
                <button type="button" wire:click="clearFile"
                        x-data @click="window.PanelUI ? PanelUI.confirm({
                            title: 'خالی‌کردن فایل لاگ؟',
                            desc: 'محتوای فایل پاک می‌شود ولی خود فایل می‌ماند؛ این عمل قابل بازگشت نیست.',
                            okText: 'خالی‌کردن', danger: true, icon: 'warning',
                        }, () => $wire.clearFile()) : (confirm('خالی‌کردن فایل لاگ؟') && $wire.clearFile())"
                        class="btn-ghost ui-press !py-2 !px-4 !text-xs">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                    خالی‌کردن فایل
                </button>
                <button type="button" wire:click="deleteFile"
                        x-data @click="window.PanelUI ? PanelUI.confirm({
                            title: 'حذف کامل فایل لاگ؟',
                            desc: 'فایل از دیسک حذف می‌شود؛ در صورت نیاز لاراول دوباره می‌سازدش. این عمل قابل بازگشت نیست.',
                            okText: 'حذف کامل', danger: true, icon: 'warning',
                        }, () => $wire.deleteFile()) : (confirm('حذف کامل فایل لاگ؟') && $wire.deleteFile())"
                        class="btn-ghost ui-press !py-2 !px-4 !text-xs !text-red-600">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    حذف کامل فایل
                </button>
            </div>
        @endif
    </div>

    @if ($summary && $summary['truncated'])
        <div class="card ui-lift animate-fade-up slg-note" role="status">
            فایل بزرگ است — فقط <b>آخرین ۸ مگابایت</b> آن خوانده و نمایش داده می‌شود (برای سرعت). برای دیدن کامل، فایل را خالی یا حذف کنید.
        </div>
    @endif

    {{-- ================== فیلتر سطح + جستجو + جدول ================== --}}
    <section class="card ui-lift animate-fade-up overflow-hidden" style="animation-delay:.08s" id="slg-main">

        {{-- چیپ‌های سطح (دسته‌بندی) --}}
        <div class="slg-levels" role="group" aria-label="فیلتر سطح لاگ">
            <button type="button" wire:click="$set('level', '')" wire:key="lvl-all"
                class="slg-level slg-level--all {{ $level === '' ? 'is-on' : '' }}">
                <span class="slg-level-dot"></span>
                همه
                <span class="slg-level-count">{{ fa_number($summary['count'] ?? 0) }}</span>
            </button>
            @foreach ($levelStats as $lvl => $count)
                <button type="button" wire:click="$set('level', '{{ $lvl }}')" wire:key="lvl-{{ $lvl }}"
                    class="slg-level slg-level--{{ $levelMeta[$lvl][1] ?? 'stone' }} {{ $level === $lvl ? 'is-on' : '' }}">
                    <span class="slg-level-dot"></span>
                    {{ $levelMeta[$lvl][0] ?? $lvl }}
                    <span class="slg-level-count">{{ fa_number($count) }}</span>
                </button>
            @endforeach
        </div>

        {{-- نوار ابزار: جستجو + تعداد در صفحه --}}
        <div class="adm-card-head slg-toolbar">
            <div class="relative flex-1 min-w-44 max-w-xs">
                <input id="slg-q" type="search" class="field !py-2.5 pl-10" placeholder="جستجو در متن و stack لاگ‌ها..." aria-label="جستجو در لاگ‌ها" wire:model.live.debounce.400ms="q">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <label class="slg-perpage">
                <span>نمایش</span>
                <select id="slg-perpage" class="field !py-2 !px-2 !text-xs cursor-pointer" aria-label="تعداد ردیف در صفحه" wire:model.live="perPage">
                    <option value="10" @selected($perPage === 10)>۱۰</option>
                    <option value="25" @selected($perPage === 25)>۲۵</option>
                    <option value="50" @selected($perPage === 50)>۵۰</option>
                    <option value="100" @selected($perPage === 100)>۱۰۰</option>
                </select>
                <span>ورودی</span>
            </label>
        </div>

        {{-- جدول --}}
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                <thead>
                    <tr>
                        <th class="!w-28">سطح</th>
                        <th class="!w-24">محیط</th>
                        <th class="!w-44">زمان</th>
                        <th>متن رخداد</th>
                        <th class="!w-20 text-center">جزئیات</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        /* آیکون‌های سطح — عیناً از اسکریپت قبلی صفحه */
                        $lvlIcons = [
                            'emergency' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>',
                            'alert' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6"/><path d="M12 12v4"/><path d="M12 2h0"/></svg>',
                            'critical' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
                            'error' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
                            'warning' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
                            'notice' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
                            'info' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
                            'debug' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>',
                        ];
                    @endphp
                    @forelse ($rows as $idx => $e)
                        <tr wire:key="slgrow-{{ $e['ts'] }}-{{ $idx }}" class="cursor-pointer slg-row" wire:click="showDetail({{ ($rows->currentPage() - 1) * $rows->perPage() + $idx }})">
                            <td>
                                <span class="slg-lvl-badge slg-tone-{{ $e['tone'] }}">{!! $lvlIcons[$e['level']] ?? $lvlIcons['debug'] !!}{{ $e['level_fa'] }}</span>
                            </td>
                            <td><span class="slg-env">{{ $e['env'] }}</span></td>
                            <td class="slg-time">
                                {{ $e['date_fa'] }} — {{ $e['time_fa'] }}
                                <small title="{{ $e['ts'] }}">{{ $e['ts'] }}</small>
                            </td>
                            <td>
                                <div class="slg-msg">{{ $e['message'] }}</div>
                                @if ($e['trace'])
                                    <span class="slg-has-trace"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>دارای stack trace</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="act-detail ui-row-btn" title="جزئیات" aria-label="مشاهدهٔ جزئیات">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 11h.01"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-10 text-stone-400 text-xs">موردی یافت نشد.@if ($summary && $summary['truncated']) (فقط آخرین ۸ مگابایت فایل جستجو شده)@endif</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        @if ($rows)
            <div class="adm-table-foot text-xs text-stone-500">
                <span>{{ fa_number($rows->total()) }} ورودی @if ($rows->lastPage() > 1)— صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}@endif</span>
                @if ($rows->lastPage() > 1)
                    <div class="flex gap-2">
                        <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" @if ($rows->onFirstPage()) disabled @endif wire:click="previousPage">قبلی</button>
                        <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" @if ($rows->hasMorePages()) @else disabled @endif wire:click="nextPage">بعدی</button>
                    </div>
                @endif
            </div>
        @endif
    </section>
    @endif

{{-- مودال جزئیات ورودی لاگ (درون ریشهٔ واحد کامپوننت — Livewire فقط یک ریشه می‌پذیرد) --}}
<div class="ui-modal-backdrop {{ $detail === null ? 'hidden' : 'flex' }}">
    <div class="absolute inset-0" wire:click="closeDetail" aria-hidden="true"></div>
    <div class="ui-modal adm-modal-lg adm-modal-text-start" data-tone="info" role="dialog" aria-modal="true" aria-labelledby="slg-d-title">
        <div class="adm-modal-head">
            <h3 class="text-sm font-extrabold text-stone-800" id="slg-d-title">جزئیات رخداد</h3>
            <button type="button" class="adm-modal-x" wire:click="closeDetail" aria-label="بستن">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="adm-modal-body">
            <div class="slg-d-meta">
                <span class="slg-lvl-badge slg-tone-{{ $detail['tone'] ?? 'stone' }}">{!! $detail['icon'] ?? '' !!}{{ $detail['level_fa'] ?? '—' }}</span>
                <span class="badge bg-stone-100 text-stone-500 border border-stone-200" dir="ltr">{{ $detail['env'] ?? '—' }}</span>
                <span class="badge bg-stone-100 text-stone-500 border border-stone-200">{{ $detail['date_fa'] ?? '—' }} — {{ $detail['time_fa'] ?? '' }}</span>
                <span class="badge bg-stone-100 text-stone-500 border border-stone-200" dir="ltr" title="{{ $detail['ts'] ?? '' }}">{{ $detail['ts'] ?? '—' }}</span>
            </div>
            <p class="lbl !mt-4 !mb-1.5">متن کامل رخداد</p>
            <pre class="slg-d-pre" dir="ltr"><code>{{ $detail['message'] ?? '—' }}</code></pre>
            @if (($detail['trace'] ?? null) !== null && ($detail['trace'] ?? '') !== '')
                <div id="slg-d-trace-zone">
                    <p class="lbl !mt-4 !mb-1.5">Stack trace / ادامهٔ متن</p>
                    <pre class="slg-d-pre slg-d-pre--trace" dir="ltr"><code>{{ $detail['trace'] }}</code></pre>
                </div>
            @endif
        </div>
    </div>
</div>

</div>
