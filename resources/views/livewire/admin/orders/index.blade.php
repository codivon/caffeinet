{{-- «مدیریت سفارش‌ها» پنل مدیریت کل — نسخهٔ Livewire 4 [Task 3-c]
     کلاس‌ها و ساختار عیناً از back/admin/orders/index.blade.php حفظ شده است؛
     رندر لیست و چیپ‌ها سمت سرور (به‌جای AJAX) با فیلترهای wire:model.live + #[Url].
     حذف نرم از همان مسیر trash (data-trash + trash.js) — با اسکریپت اتصال سبک.

     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div wire:poll.20s.visible>

    {{-- پیام نتیجهٔ اکشن‌ها (تخصیص/لغو/ری‌پخش) --}}
    @if ($flashMessage)
        <div class="ui-note mb-4" data-tone="{{ $flashType === 'error' ? 'err' : 'ok' }}" role="status">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- چیپ‌های آماری (فیلتر سریع) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 mb-5">
        <button type="button" wire:click="filterByStatus('broadcasting')"
                class="stat-chip card p-4 flex items-center gap-3 text-right animate-fade-up {{ $status === 'broadcasting' ? 'ring-2 ring-sky-300' : '' }}">
            <span class="stat-chip-icon grid place-items-center size-10 rounded-2xl bg-sky-50 text-sky-600 shrink-0">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5Z"/><circle cx="12" cy="12" r="1"/></svg>
            </span>
            <div class="min-w-0">
                <p class="stat-chip-label text-[11px] font-semibold text-stone-500">در حال پخش</p>
                <p class="stat-chip-count text-xl font-extrabold tabular-nums text-sky-700">{{ fa_digits((string) $chips['broadcasting']) }} <span class="stat-chip-unit text-[10px] font-medium">سفارش</span></p>
            </div>
        </button>

        <button type="button" wire:click="filterByStatus('queued')"
                class="stat-chip card p-4 flex items-center gap-3 text-right animate-fade-up delay-1 {{ $status === 'queued' ? 'ring-2 ring-sky-300' : '' }}">
            <span class="stat-chip-icon grid place-items-center size-10 rounded-2xl bg-amber-100 text-amber-600 shrink-0">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12h4l2-3 4 6 2-3h6"/><path d="M12 2v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="m19.07 4.93-2.83 2.83"/></svg>
            </span>
            <div class="min-w-0">
                <p class="stat-chip-label text-[11px] font-semibold text-stone-500">صف تعیین‌تکلیف</p>
                <p class="stat-chip-count text-xl font-extrabold tabular-nums text-amber-700">{{ fa_digits((string) $chips['queued']) }} <span class="stat-chip-unit text-[10px] font-medium">سفارش</span></p>
            </div>
        </button>

        <button type="button" wire:click="filterByStatus('active')"
                class="stat-chip card p-4 flex items-center gap-3 text-right animate-fade-up delay-2 {{ $status === 'active' ? 'ring-2 ring-sky-300' : '' }}">
            <span class="stat-chip-icon grid place-items-center size-10 rounded-2xl bg-teal-100 text-teal-600 shrink-0">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6V4m0 2a2 2 0 1 0 0 4m0-4a2 2 0 1 1 0 4m-6 8a2 2 0 1 0 0-4m0 4a2 2 0 1 1 0-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 1 0 0-4m0 4a2 2 0 1 1 0-4m0 4v2m0-6V4"/></svg>
            </span>
            <div class="min-w-0">
                <p class="stat-chip-label text-[11px] font-semibold text-stone-500">در جریان کار</p>
                <p class="stat-chip-count text-xl font-extrabold tabular-nums text-teal-700">{{ fa_digits((string) $chips['active']) }} <span class="stat-chip-unit text-[10px] font-medium">سفارش</span></p>
            </div>
        </button>

        <button type="button" wire:click="filterByStatus('done')"
                class="stat-chip card p-4 flex items-center gap-3 text-right animate-fade-up delay-3 {{ $status === 'done' ? 'ring-2 ring-sky-300' : '' }}">
            <span class="stat-chip-icon grid place-items-center size-10 rounded-2xl bg-emerald-100 text-emerald-600 shrink-0">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <div class="min-w-0">
                <p class="stat-chip-label text-[11px] font-semibold text-stone-500">تحویل/تکمیل</p>
                <p class="stat-chip-count text-xl font-extrabold tabular-nums text-emerald-700">{{ fa_digits((string) $chips['done']) }} <span class="stat-chip-unit text-[10px] font-medium">سفارش</span></p>
            </div>
        </button>

        <button type="button" wire:click="filterByStatus('')"
                class="stat-chip card p-4 flex items-center gap-3 text-right animate-fade-up delay-4 col-span-2 {{ $status === '' ? 'ring-2 ring-sky-300' : '' }}">
            <span class="stat-chip-icon grid place-items-center size-10 rounded-2xl bg-stone-100 text-stone-500 shrink-0">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M3 12h18"/><path d="M3 18h18"/></svg>
            </span>
            <div class="min-w-0">
                <p class="stat-chip-label text-[11px] font-semibold text-stone-500">کل سفارش‌ها</p>
                <p class="stat-chip-count text-xl font-extrabold tabular-nums text-stone-700">{{ fa_digits((string) $chips['total']) }} <span class="stat-chip-unit text-[10px] font-medium">سفارش</span></p>
            </div>
        </button>
    </div>

    {{-- جدول سفارش‌ها --}}
    <div class="card animate-fade-up delay-2 overflow-hidden">
        <div class="adm-card-head adm-card-head-stacked">
            <div class="relative flex-1 min-w-0">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" placeholder="جستجوی شماره سفارش، نام یا موبایل مشتری…"
                       wire:model.live.debounce.400ms="q"
                       class="field !py-2.5 !text-xs w-full pl-10" autocomplete="off">
            </div>
            <select wire:model.live="status" class="field !py-2.5 !text-xs !w-auto min-w-44" aria-label="فیلتر وضعیت">
                <option value="">همه وضعیت‌ها</option>
                <option value="active">در جریان کار (پخش تا انجام)</option>
                <option value="broadcasting">در حال پخش</option>
                <option value="queued">صف تعیین‌تکلیف</option>
                <option value="accepted">پذیرفته‌شده</option>
                <option value="in_progress">در حال انجام</option>
                <option value="needs_info">نیازمند اطلاعات</option>
                <option value="delivered">تحویل‌شده</option>
                <option value="completed">تکمیل‌شده</option>
                <option value="cancelled">لغوشده</option>
                <option value="refunded">بازگشت وجه</option>
                <option value="pending_payment">در انتظار پرداخت</option>
                <option value="paid">پرداخت‌شده</option>
            </select>

            <select wire:model.live="rating" class="field !py-2.5 !text-xs !w-auto min-w-40" aria-label="فیلتر امتیاز نظرسنجی">
                <option value="">همهٔ امتیازها</option>
                <option value="5">★ ۵ (عالی)</option>
                <option value="4">★ ۴</option>
                <option value="3">★ ۳</option>
                <option value="2">★ ۲</option>
                <option value="1">★ ۱ (بسیار بد)</option>
                <option value="high">عالی (۴+)</option>
                <option value="low">ضعیف (۲ و کمتر)</option>
                <option value="none">بدون امتیاز</option>
            </select>
        </div>

        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                    <thead>
                    <tr>
                        <th>سفارش</th>
                        <th>خدمت</th>
                        <th>مشتری</th>
                        <th>کافی‌نت</th>
                        <th>مبلغ</th>
                        <th>وضعیت</th>
                        <th>امتیاز</th>
                        <th>ثبت</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="order-{{ $row->id }}" class="group">
                            <td><span class="text-[11px] font-mono text-stone-500" dir="ltr">{{ $row->order_number }}</span></td>
                            <td class="text-xs font-bold text-stone-700 max-w-40 truncate">{{ $row->service?->name ?? '—' }}</td>
                            <td>
                                <p class="text-xs font-semibold text-stone-700">{{ trim(($row->customer?->name ?? '').' '.($row->customer?->family ?? '')) ?: '—' }}</p>
                                @if ($row->customer?->mobile)
                                    <p class="text-[10px] text-stone-400 font-mono" dir="ltr">{{ $row->customer->mobile }}</p>
                                @endif
                            </td>
                            <td class="text-xs text-stone-500">{{ $row->coffeenet?->name ?? '—' }}</td>
                            <td class="text-xs font-extrabold text-amber-700 tabular-nums whitespace-nowrap">{{ fa_money((float) $row->price + (float) $row->expenses) }}</td>
                            <td>
                                <span class="badge {{ $statusBadge($row->status) }} whitespace-nowrap">{{ $row->status->label() }}</span>
                                @if ($row->status->value === 'broadcasting' && $row->broadcastSecondsLeft() > 0)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 mt-1 tabular-nums" dir="ltr">
                                        <span class="ui-dot text-amber-400"></span>
                                        <span class="row-countdown" data-seconds="{{ $row->broadcastSecondsLeft() }}">{{ fa_digits((string) $row->broadcastSecondsLeft()) }}s</span>
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($row->rating?->rating !== null)
                                    @php $rval = (int) $row->rating->rating; $rcls = $rval <= 2 ? 'rt-row-rating--bad' : ($rval >= 4 ? 'rt-row-rating--good' : 'rt-row-rating--mid'); @endphp
                                    <span class="rt-row-rating {{ $rcls }}" title="امتیاز نظرسنجی مشتری: {{ fa_digits((string) $rval) }} از ۵">
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>
                                        {{ fa_digits((string) $rval) }}
                                        @if ($row->rating->operator_rating !== null)
                                            <span class="rt-row-rating-op">اپ {{ fa_digits((string) $row->rating->operator_rating) }}</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-[11px] text-stone-300" title="بدون امتیاز">—</span>
                                @endif
                            </td>
                            <td class="text-[11px] text-stone-400 whitespace-nowrap">{{ fa_date($row->created_at, 'Y/m/d H:i') ?? '—' }}</td>
                            <td>
                                <div class="flex items-center justify-center gap-1.5">
                                    @if (in_array($row->status->value, ['queued', 'broadcasting'], true))
                                        <button type="button" wire:click="openAssign({{ $row->id }})"
                                                class="btn-primary !py-1.5 !px-3 !text-[11px]" title="تخصیص دستی به کافی‌نت">تخصیص</button>
                                    @endif
                                    <a href="{{ route('admin.orders.view', $row->id) }}" wire:navigate
                                       class="btn-ghost !py-1.5 !px-3 !text-[11px]" title="صفحهٔ کامل جزئیات">جزئیات</a>
                                    <button type="button" data-trash="{{ $row->id }}" data-trash-label="{{ $row->order_number }}"
                                            class="btn-ghost !py-1.5 !px-3 !text-[11px] ui-press !text-rose-600 hover:!bg-rose-50"
                                            title="حذف سفارش و گفتگو (به حذف‌شده‌ها)">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="!py-4">
                            <div class="ui-empty">
                                <span class="ui-empty-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 13v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M2 9h20"/><path d="M12 2 2 9l10 7 10-7-10-7Z"/></svg>
                                </span>
                                <p class="text-xs font-bold text-stone-500">سفارشی یافت نشد</p>
                                <p class="text-[11px] text-stone-400 mt-1">با تغییر فیلترها دوباره تلاش کنید.</p>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="adm-table-foot">
            <p class="text-[11px] text-stone-400">
                @if ($rows->total())
                    نمایش {{ fa_digits((string) $rows->firstItem()) }} تا {{ fa_digits((string) $rows->lastItem()) }} از {{ fa_digits((string) $rows->total()) }} سفارش
                @else
                    —
                @endif
            </p>
            {{ $rows->links('livewire.admin.orders.partials.pagination') }}
        </div>
    </div>

    {{-- ================== مودال تخصیص دستی ================== --}}
    <div class="ui-modal-backdrop {{ $assignOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="assign-title" wire:key="assign-modal">
        <div class="absolute inset-0" aria-hidden="true" wire:click="closeAssign()"></div>

        <form wire:submit="assignOrder" wire:key="assign-form" class="ui-modal adm-modal-md adm-modal-text-start" data-tone="info" role="document" novalidate>
            <div class="adm-modal-head">
                <div>
                    <h3 id="assign-title" class="text-sm font-extrabold text-stone-800">تخصیص دستی سفارش</h3>
                    <p class="text-[11px] text-stone-400 mt-0.5 font-mono" dir="ltr">{{ $assignOrderNumber ?: '—' }}</p>
                </div>
                <button type="button" class="adm-modal-x" wire:click="closeAssign()" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="adm-modal-body space-y-4">
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" placeholder="جستجوی نام کافی‌نت…" wire:model.live.debounce.300ms="assignSearch"
                           class="field !py-2.5 !text-xs w-full pl-10" autocomplete="off">
                </div>

                <div class="ui-stagger space-y-2 max-h-96 overflow-y-auto" role="radiogroup" aria-label="انتخاب کافی‌نت">
                    @forelse ($assignCoffeenets as $c)
                        <button type="button" wire:click="setAssignCoffeenet({{ $c['id'] }})" wire:key="assign-net-{{ $c['id'] }}"
                                class="w-full text-right rounded-2xl border px-4 py-3 flex items-center gap-3 transition-colors
                                       {{ $assignCoffeenetId === $c['id'] ? 'border-blue-300 bg-blue-50/60 ring-1 ring-blue-200' : 'border-stone-200 bg-white hover:bg-stone-50' }}">
                            <span class="grid place-items-center size-9 rounded-xl bg-sky-50 text-sky-600 shrink-0">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 7h20"/><path d="M12 7v5"/><rect width="18" height="14" x="3" y="7" rx="1"/></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-stone-700 truncate">{{ $c['name'] }}</span>
                                <span class="block text-[10px] text-stone-400 mt-0.5">{{ $c['city'] ?? '—' }} @if($c['phone']) · <span dir="ltr" class="font-mono">{{ $c['phone'] }}</span>@endif</span>
                            </span>
                            @if ($assignCoffeenetId === $c['id'])
                                <svg class="size-4 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </button>
                    @empty
                        <p class="text-xs text-stone-400 text-center py-6">
                            {{ $assignSearch !== '' ? 'کافی‌نتی با این نام یافت نشد.' : 'در حال دریافت کافی‌نت‌های فعال…' }}
                        </p>
                    @endforelse
                </div>

                @if ($assignCoffeenetId && count($assignOperators) > 0)
                    <div>
                        <label for="assign-operator" class="lbl">واگذاری به اپراتور (اختیاری)</label>
                        <select id="assign-operator" wire:model="assignOperatorId" class="field !text-xs">
                            <option value="">بدون اپراتور — تعیین تکلیف توسط مدیر کافی‌نت</option>
                            @foreach ($assignOperators as $op)
                                <option value="{{ $op['id'] }}">{{ $op['name'] }} {{ $op['mobile'] ? ' — '.$op['mobile'] : '' }} ({{ $op['position_label'] }})</option>
                            @endforeach
                        </select>
                        <p class="st-hint text-[11px] text-stone-400 mt-1">اپراتورهای فعال کافی‌نت انتخابی — اختیاری</p>
                    </div>
                @endif

                <div>
                    <label for="assign-note" class="lbl">یادداشت (اختیاری)</label>
                    <textarea id="assign-note" rows="2" wire:model="assignNote" class="field !text-xs w-full" placeholder="مثلاً دلیل تخصیص یا توضیح برای تاریخچه…"></textarea>
                </div>

                @error('assignCoffeenetId')<p class="field-error">{{ $message }}</p>@enderror
                @error('assignOperatorId')<p class="field-error">{{ $message }}</p>@enderror
                @error('assignNote')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="adm-modal-foot">
                <button type="button" class="btn-ghost ui-press !py-2.5 !text-xs" wire:click="closeAssign()">انصراف</button>
                <button type="submit" class="btn-primary btn-shine !py-2.5 !text-xs" @disabled(!$assignCoffeenetId)>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    تخصیص به کافی‌نت
                </button>
            </div>
        </form>
    </div>

    {{-- ================== مودال لغو ================== --}}
    <div class="ui-modal-backdrop {{ $cancelOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="cancel-title" wire:key="cancel-modal">
        <div class="absolute inset-0" aria-hidden="true" wire:click="closeCancel()"></div>

        <form wire:submit="cancelOrder" wire:key="cancel-form" class="ui-modal adm-modal-sm adm-modal-text-start" data-tone="danger" role="document" novalidate>
            <div class="adm-modal-head">
                <div>
                    <h3 id="cancel-title" class="text-sm font-extrabold text-rose-600">لغو سفارش</h3>
                    <p class="text-[11px] text-stone-400 mt-0.5 font-mono" dir="ltr">{{ $cancelOrderNumber ?: '—' }}</p>
                </div>
                <button type="button" class="adm-modal-x" wire:click="closeCancel()" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="adm-modal-body space-y-4">
                <div class="ui-note" data-tone="err">
                    لغو سفارش پرداخت‌شده به مشتری پیامک می‌شود و در تاریخچه ثبت می‌گردد.
                    <strong>وجه پرداختی طبق فرایند تسویه (فاز ۸) برگشت داده می‌شود.</strong>
                </div>
                <div>
                    <label for="cancel-reason" class="lbl">دلیل لغو <span class="text-rose-500">*</span></label>
                    <textarea id="cancel-reason" rows="3" wire:model="cancelReason" class="field !text-xs w-full" placeholder="دلیل لغو برای مشتری و تاریخچه…"></textarea>
                </div>
                @error('cancelReason')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="adm-modal-foot">
                <button type="button" class="btn-ghost ui-press !py-2.5 !text-xs" wire:click="closeCancel()">انصراف</button>
                <button type="submit" class="ui-btn-danger !py-2.5 !text-xs" wire:loading.attr="disabled">
                    لغو سفارش
                </button>
            </div>
        </form>
    </div>

    @include('livewire.admin.orders.partials.toaster')
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pages/trash.css') }}?v=3">
<link rel="stylesheet" href="{{ asset('assets/css/pages/ratings.css') }}?v=1">
@endpush

@push('scripts')
<script src="{{ asset('back/assets/js/pages/trash.js') }}?v=2"></script>
<script>
    /* اتصال سبک trash به ردیف‌های Livewire — همان دیالوگ/مسیر قبل.
       رفرش کامل صفحه نسخهٔ JS با رندر مجدد Livewire جایگزین شده است. */
    (function () {
        if (!window.AdminTrash) { return; }
        if (document.body.hasAttribute('data-trash-mounted-orders-3c')) { return; }
        document.body.setAttribute('data-trash-mounted-orders-3c', '1');

        window.__trashReload = false; // به‌جای reload کامل، لیست Livewire تازه می‌شود

        const origRefreshBadge = AdminTrash.refreshBadge.bind(AdminTrash);
        AdminTrash.refreshBadge = function (base) {
            origRefreshBadge(base);
            window.dispatchEvent(new CustomEvent('trash-changed-3c'));
        };

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-trash]');
            if (!btn) { return; }
            e.preventDefault();
            const id = parseInt(btn.getAttribute('data-trash'), 10);
            if (!id) { return; }
            AdminTrash.confirmAndDelete('/admin/orders', id, btn.getAttribute('data-trash-label') || '');
        });

        window.addEventListener('trash-changed-3c', () => {
            if (window.Livewire) { Livewire.dispatch('trash-changed-3c'); }
        });
    })();

    /* شمارش معکوس ردیف‌های broadcasting — همان رفتار نسخهٔ JS (فقط نمایشی؛ داده هر ۲۰ ثانیه از سرور تازه می‌شود) */
    (function () {
        function fa(n) { return String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[+d]); }
        setInterval(() => {
            document.querySelectorAll('.row-countdown').forEach((el) => {
                let s = parseInt(el.dataset.seconds || '0', 10);
                if (s > 0) {
                    s -= 1;
                    el.dataset.seconds = String(s);
                    el.textContent = fa(s) + 's';
                    el.classList.toggle('text-rose-600', s <= 10);
                }
            });
        }, 1000);
    })();
</script>
@endpush
