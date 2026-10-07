{{-- لاگ فعالیت‌ها — کامپوننت Livewire [Task 3-b]
     رندر سمت سرور (جایگزین AJAX) — کلاس‌ها عیناً مطابق نسخهٔ قبلی --}}
<div>

{{-- ================== v29 — حذف دوره‌ای لاگ فعالیت ================== --}}
@if ($canSystem)
<div class="card ui-lift animate-fade-up lg-ret" data-scope="audit_logs">
    <div class="lg-ret-main">
        <span class="lg-ret-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
        </span>
        <div class="flex-1 min-w-0">
            <h2 class="lg-ret-title">حذف دوره‌ای لاگ فعالیت</h2>
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
        <label class="sr-only" for="lg-ret-days">نگهداشت لاگ فعالیت (روز)</label>
        <div class="lg-ret-input">
            <input id="lg-ret-days" type="number" min="7" max="3650" class="field !py-2.5 font-mono" wire:model.live="retentionDays">
            <span class="lg-ret-unit">روز</span>
        </div>
        <button type="button" wire:click="saveRetention" class="btn-ghost !py-2.5 !px-4 !text-xs ui-press" wire:loading.attr="disabled" wire:target="saveRetention">
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
            ذخیرهٔ نگهداشت
        </button>
        <button type="button" wire:click="cleanupOld"
                @if ($oldCount === 0) disabled title="ردیف قدیمی‌تری نیست" @endif
                x-data @click="window.PanelUI ? PanelUI.confirm({
                    title: 'پاکسازی لاگ فعالیت‌های قدیمی؟',
                    desc: 'همهٔ ردیف‌های قدیمی‌تر از نگهداشت فعلی، از قدیمی‌ترین حذف می‌شوند. این عمل قابل بازگشت نیست.',
                    okText: 'حذف قدیمی‌ها', danger: true, icon: 'question',
                }, () => $wire.cleanupOld()) : (confirm('پاکسازی لاگ فعالیت‌های قدیمی؟') && $wire.cleanupOld())"
                class="btn-ghost !py-2.5 !px-4 !text-xs ui-press !text-red-600">
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
            پاکسازی قدیمی‌ها الان
        </button>
    </div>
</div>

@endif

<section class="card ui-lift animate-fade-up overflow-hidden" style="animation-delay:.04s">

    {{-- فیلترها --}}
    <div class="adm-card-head">
        <div class="relative flex-1 min-w-44 max-w-xs">
            <input id="f-q" type="search" class="field !py-2.5 pl-10" placeholder="جستجو: عملیات، توضیح، کاربر..." aria-label="جستجو در لاگ‌ها" wire:model.live.debounce.400ms="q">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </div>
        <input id="f-from" type="text" dir="ltr" placeholder="۱۴۰۵/۰۶/۰۱" class="field !py-2.5 !w-auto max-w-40 !text-center num"
               aria-label="از تاریخ" data-jdp title="برای انتخاب تاریخ کلیک کنید" wire:model.live="from">
        <input id="f-to" type="text" dir="ltr" placeholder="۱۴۰۵/۰۶/۳۱" class="field !py-2.5 !w-auto max-w-40 !text-center num"
               aria-label="تا تاریخ" data-jdp title="برای انتخاب تاریخ کلیک کنید" wire:model.live="to">
        <button type="button" wire:click="$refresh" class="btn-ghost !py-2.5 !px-4 !text-xs">
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
            اعمال فیلتر
        </button>
        <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" class="btn-ghost !py-2.5 !px-3 !text-xs" title="پاک کردن فیلترها" aria-label="پاک کردن فیلترها">
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        </button>
    </div>

    {{-- جدول --}}
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="table-panel table-modern">
            <thead>
                <tr>
                    <th>کاربر</th>
                    <th>عملیات</th>
                    <th>توضیحات</th>
                    <th>موجودیت</th>
                    <th>IP</th>
                    <th>زمان</th>
                    <th class="text-center">جزئیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $action = $log->action;
                        if (str_starts_with($action, 'auth.login')) { $toneCls = 'bg-emerald-50 text-emerald-700 border-emerald-200'; $label = 'ورود'; }
                        elseif (str_starts_with($action, 'auth.logout')) { $toneCls = 'bg-stone-100 text-stone-500 border-stone-200'; $label = 'خروج'; }
                        elseif (str_starts_with($action, 'admin')) { $toneCls = 'bg-amber-50 text-amber-700 border-amber-200'; $label = 'مدیر'; }
                        elseif (str_starts_with($action, 'settings')) { $toneCls = 'bg-sky-50 text-sky-700 border-sky-200'; $label = 'تنظیمات'; }
                        elseif (str_starts_with($action, 'sms')) { $toneCls = 'bg-violet-50 text-violet-600 border-violet-200'; $label = 'پیامک'; }
                        else { $toneCls = 'bg-stone-100 text-stone-500 border-stone-200'; $label = 'سیستم'; }
                    @endphp
                    <tr wire:key="audit-{{ $log->id }}">
                        <td class="font-semibold text-stone-700">{{ $log->user?->full_name ?? 'سیستم' }}</td>
                        <td>
                            <span class="badge {{ $toneCls }}">{{ $label }}</span>
                            <span class="font-mono text-[10px] text-stone-400 ms-1" dir="ltr">{{ $action }}</span>
                        </td>
                        <td class="max-w-64 truncate text-stone-500">{{ $log->description ?? '—' }}</td>
                        <td class="text-stone-400">{{ $log->auditable_type ? class_basename($log->auditable_type).'#'.$log->auditable_id : '—' }}</td>
                        <td class="font-mono text-[11px] text-stone-400" dir="ltr">{{ $log->ip ?? '—' }}</td>
                        <td class="text-[11px] text-stone-500" dir="ltr">{{ $log->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td class="text-center">
                            @if ($log->old_values || $log->new_values)
                                <button type="button" wire:click="showDetail({{ $log->id }})" class="act-detail ui-row-btn" title="جزئیات" aria-label="مشاهده جزئیات">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 11h.01"/></svg>
                                </button>
                            @else
                                <span class="text-stone-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-10 text-stone-400 text-xs">موردی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
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

{{-- مودال جزئیات --}}
<div class="ui-modal-backdrop {{ $detail === null ? 'hidden' : 'flex' }}">
    <div class="absolute inset-0" wire:click="closeDetail" aria-hidden="true"></div>
    <div class="ui-modal adm-modal-md adm-modal-text-start" data-tone="info" role="dialog" aria-modal="true" aria-labelledby="d-title">
        <div class="adm-modal-head">
            <h3 class="text-sm font-extrabold text-stone-800" id="d-title">جزئیات {{ $detail ? ': '.$detail['action'] : 'فعالیت' }}</h3>
            <button type="button" class="adm-modal-x" wire:click="closeDetail" aria-label="بستن">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="adm-modal-body">
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <p class="lbl">مقادیر قبلی</p>
                    <pre class="rounded-xl bg-stone-50 border border-stone-200 p-3 text-[11px] leading-6 overflow-x-auto max-h-60 overflow-y-auto text-stone-600" dir="ltr"><code>{{ $detail['old'] ?? '—' }}</code></pre>
                </div>
                <div>
                    <p class="lbl">مقادیر جدید</p>
                    <pre class="rounded-xl bg-stone-50 border border-stone-200 p-3 text-[11px] leading-6 overflow-x-auto max-h-60 overflow-y-auto text-stone-600" dir="ltr"><code>{{ $detail['new'] ?? '—' }}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
