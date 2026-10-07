{{-- [Task 3-d] کافی‌نت‌ها — فهرست (Livewire 4)
     عیناً از back/admin/coffeenets/index.blade.php + pages/admin/coffeenets/index.js
     جدول/فیلترها = Livewire (wire:model.live + WithPagination) — مودال CRUD و
     تغییر وضعیت = همان AJAX کنترلر (adm-modal + App.ajax) با پیام‌های فارسی سرور --}}
<div id="lw-coffeenets">
<section class="card ui-lift animate-fade-up overflow-hidden">

    {{-- هدر جدول --}}
    <div class="adm-card-head">
        <div class="flex flex-1 min-w-64 flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-44 max-w-sm">
                <input type="search" class="field !py-2.5 pl-10" placeholder="جستجو: نام کافی‌نت، سازمان..." aria-label="جستجوی کافی‌نت‌ها" wire:model.live.debounce.350ms="search">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <select class="field !py-2.5 !w-auto min-w-36" aria-label="فیلتر وضعیت" wire:model.live="status">
                <option value="">همه وضعیت‌ها</option>
                <option value="pending">در انتظار تأیید</option>
                <option value="approved">تأییدشده</option>
                <option value="rejected">ردشده</option>
                <option value="suspended">معلق</option>
            </select>
            <select class="field !py-2.5 !w-auto min-w-40" aria-label="فیلتر سازمان" wire:model.live="organizationId">
                <option value="">همه (مستقل + زیرمجموعه)</option>
                <option value="0">فقط مستقل</option>
                @foreach ($organizations as $org)
                    <option value="{{ $org->id }}">سازمان {{ $org->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" data-new class="btn-primary btn-shine ui-press">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            ثبت کافی‌نت جدید
        </button>
        {{-- [Task 3-d] دکمهٔ حذفشده‌ها — استاتیک (ضد حذف با morph) — bind در trash-glue --}}
        <button type="button" class="trash-toolbar-btn" data-trash-open aria-label="فهرست رکوردهای حذف‌شده">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
            <span>حذف‌شده‌ها</span>
            <b class="trash-toolbar-count" id="trash-count-coffeenets" hidden>۰</b>
        </button>
    </div>

    {{-- جدول --}}
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="table-panel table-modern">
            <thead>
                <tr>
                    <th>کافی‌نت</th>
                    <th>وابستگی</th>
                    <th>مدیر</th>
                    <th>موقعیت</th>
                    <th>وضعیت</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody wire:loading.delay.class="opacity-50">
                @forelse ($rows as $row)
                    @php
                        $statusColors = [
                            'amber' => 'bg-amber-50 text-amber-700 border border-amber-200',
                            'emerald' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                            'rose' => 'bg-rose-50 text-rose-600 border border-rose-200',
                            'stone' => 'bg-stone-100 text-stone-500 border border-stone-200',
                        ];
                        $editPayload = json_encode([
                            'id' => $row['id'],
                            'name' => $row['name'],
                            'phone' => $row['phone'],
                            'address' => $row['address'],
                            'organization_id' => $row['organization_id'],
                            'province' => $row['province'],
                            'city' => $row['city'],
                            'manager_name' => $row['manager_name'],
                            'manager_family' => $row['manager_family'],
                            'manager_email' => $row['manager_email'],
                            'manager_mobile' => $row['manager_mobile'],
                            'manager_user_id' => $row['manager_user_id'],
                        ]);
                    @endphp
                    <tr wire:key="coffeenet-{{ $row['id'] }}">
                        <td>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.coffeenets.show', $row['id']) }}" wire:navigate class="flex items-center gap-3 shrink-0" title="جزئیات کامل کافی‌نت">
                                    <span class="grid place-items-center size-9 rounded-xl bg-gradient-to-br from-rose-400/70 to-rose-600/70 text-white font-bold text-xs shrink-0">{{ mb_substr((string) $row['name'] ?: '؟', 0, 1) }}</span>
                                </a>
                                <div>
                                    <p class="font-bold"><a href="{{ route('admin.coffeenets.show', $row['id']) }}" wire:navigate class="text-amber-700 underline transition-colors">{{ $row['name'] }}</a></p>
                                    <p class="text-xs text-stone-400">{{ $row['phone'] ?: '—' }} · ثبت: {{ $row['created_at'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($row['is_independent'])
                                <span class="badge bg-stone-100 text-stone-500 border border-stone-200">مستقل</span>
                            @else
                                <span class="badge bg-teal-50 text-teal-700 border border-teal-200">{{ $row['organization'] }}</span>@if ($row['introduction_reward_paid']) <span class="text-[10px] text-emerald-600" title="پاداش معرفی پرداخت شده">🏅</span>@endif
                            @endif
                        </td>
                        <td class="text-stone-600 font-semibold">
                            {{ $row['manager'] ?: '—' }}
                            @if (! $row['manager']) <span class="badge bg-rose-50 text-rose-600 border border-rose-200" title="کاربر مدیر (اطلاعات ورود) تعریف نشده — اعلان‌ها گیرنده ندارند">بدون حساب مدیر</span>@endif
                        </td>
                        <td class="text-stone-500">{{ $row['city'] ? $row['city'].'، ' : '' }}{{ $row['province'] ?: '—' }}</td>
                        <td><span class="badge {{ $statusColors[$row['status']['color']] ?? '' }}">{{ $row['status']['label'] }}</span></td>
                        <td class="text-center">
                            <div class="adm-row-actions">
                                @if ($row['status']['value'] !== 'approved')
                                    <button type="button" class="ui-row-btn" data-status="approved" data-id="{{ $row['id'] }}" title="تأیید" aria-label="تأیید کافی‌نت">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    </button>
                                @endif
                                <button type="button" class="ui-row-btn" data-edit data-row="{{ $editPayload }}" title="ویرایش" aria-label="ویرایش">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v16"/><path d="m21.12 8.88-8.24 8.24a2 2 0 0 1-1.42.58H8.5v-3a2 2 0 0 1 .58-1.42l8.24-8.24a2 2 0 0 1 2.82 0l1.98 1.98a2 2 0 0 1 0 2.82Z"/></svg>
                                </button>
                                <button type="button" class="ui-row-btn" data-trash="{{ $row['id'] }}" data-trash-label="{{ $row['name'] ?: '' }}" data-tone="danger" title="حذف (به حذف‌شده‌ها)" aria-label="حذف کافی‌نت">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                                @if ($row['status']['value'] !== 'suspended')
                                    <button type="button" class="ui-row-btn" data-status="suspended" data-id="{{ $row['id'] }}" title="تعلیق" aria-label="تعلیق کافی‌نت">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
                                    </button>
                                @endif
                                @if ($row['status']['value'] !== 'rejected')
                                    <button type="button" class="ui-row-btn" data-tone="danger" data-status="rejected" data-id="{{ $row['id'] }}" title="رد" aria-label="رد کافی‌نت">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-10 text-stone-400 text-xs">کافی‌نتی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- صفحه‌بندی --}}
    {{ $rows->links('livewire.admin.entities.partials.pagination', ['paginatorUnit' => 'کافی‌نت']) }}
</section>

{{-- ================== مودال ایجاد/ویرایش (عیناً adm-modal قبلی + AJAX کنترلر) ================== --}}
<div id="modal" class="ui-modal-backdrop hidden">
    <div data-close class="absolute inset-0" aria-hidden="true"></div>
    <form id="modal-form" class="ui-modal adm-modal-lg adm-modal-text-start" data-tone="info" role="dialog" aria-modal="true" aria-labelledby="modal-title" novalidate>
        <input type="hidden" id="f-id" value="">
        <div class="adm-modal-head">
            <h3 id="modal-title" class="text-sm font-extrabold text-stone-800">ثبت کافی‌نت جدید</h3>
            <button type="button" class="adm-modal-x modal-close" aria-label="بستن">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div class="adm-modal-body space-y-4">
            <p class="ui-note">
                کاربر «مدیر کافی‌نت» همزمان ساخته می‌شود (پنل کاملش در فاز ۳). سازمان خالی = کافی‌نت مستقل.
            </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="sm:col-span-2">
                <label class="lbl" for="f-name">نام کافی‌نت <span class="text-rose-500">*</span></label>
                <input id="f-name" class="field" autocomplete="off" required>
                <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="name"></p>
            </div>

            <div class="sm:col-span-2">
                <label class="lbl" for="f-org">سازمان (وابستگی)</label>
                <select id="f-org" class="field">
                    <option value="">مستقل — بدون سازمان</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-stone-400 mt-1.5">در صورت انتخاب سازمان، پاداش معرفی پس از تأیید پرداخت می‌شود</p>
            </div>

            <div>
                <label class="lbl" for="f-phone">تلفن</label>
                <input id="f-phone" class="field" dir="ltr" autocomplete="off">
            </div>

            <div>
                <label class="lbl" for="f-province">استان</label>
                <select id="f-province" class="field">
                    <option value="">— انتخاب استان —</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="lbl" for="f-city">شهرستان</label>
                <select id="f-city" class="field" disabled>
                    <option value="">ابتدا استان را انتخاب کنید</option>
                </select>
            </div>

            <div id="approve-wrap">
                <label class="lbl" for="f-approve">ثبت و تأیید فوری</label>
                <label class="flex items-center gap-2.5 rounded-xl border border-stone-200 px-3.5 py-2.5 cursor-pointer hover:bg-stone-50 transition-colors">
                    <input type="checkbox" id="f-approve" class="size-4 accent-amber-600">
                    <span class="text-xs font-semibold text-stone-600">بدون نیاز به تأیید مجدد فعال شود</span>
                </label>
            </div>

            <div class="sm:col-span-2">
                <label class="lbl" for="f-address">نشانی</label>
                <textarea id="f-address" class="field min-h-20" rows="2" autocomplete="off"></textarea>
            </div>
        </div>

        {{-- مدیر کافی‌نت --}}
        <div class="border-t border-stone-100 pt-4 space-y-3">
            <p class="text-xs font-bold text-stone-600 flex items-center gap-2">
                <span class="grid place-items-center size-6 rounded-lg bg-amber-50 text-amber-600">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </span>
                حساب مدیر کافی‌نت
            </p>

            <p id="no-manager-hint" class="hidden text-[11px] leading-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-3 py-2">
                این کافی‌نت هنوز <b>کاربر مدیر (اطلاعات ورود) ندارد</b> — مثلاً کافی‌نت معرفی‌شده توسط سازمان.
                نام، ایمیل و رمز را پر کنید تا حساب مدیر ساخته شود؛ بدون آن، اعلان‌های کافی‌نت گیرنده‌ای ندارد.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="manager-basic-fields">
                <div>
                    <label class="lbl" for="f-manager-name">نام <span class="text-rose-500">*</span></label>
                    <input id="f-manager-name" class="field" autocomplete="off">
                    <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="manager_name"></p>
                </div>
                <div>
                    <label class="lbl" for="f-manager-family">نام خانوادگی</label>
                    <input id="f-manager-family" class="field" autocomplete="off">
                </div>
                <div>
                    <label class="lbl" for="f-manager-email">ایمیل (نام کاربری) <span class="text-rose-500">*</span></label>
                    <input id="f-manager-email" type="email" dir="ltr" class="field" autocomplete="off" placeholder="net@example.com">
                    <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="manager_email"></p>
                </div>
                <div>
                    <label class="lbl" for="f-manager-mobile">موبایل (اختیاری)</label>
                    <input id="f-manager-mobile" type="tel" dir="ltr" class="field" placeholder="09123456789" autocomplete="off">
                    <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="manager_mobile"></p>
                </div>
                <div class="sm:col-span-2">
                    <label class="lbl" for="f-manager-password">رمز عبور <span id="pass-hint" class="font-normal text-stone-400">(حداقل ۸ کاراکتر)</span></label>
                    <input id="f-manager-password" type="password" dir="ltr" class="field" autocomplete="new-password">
                    <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="manager_password"></p>
                </div>
            </div>
        </div>

        <div class="adm-modal-foot">
            <button type="submit" id="modal-save" class="btn-primary btn-shine !py-3">ذخیره کافی‌نت</button>
            <button type="button" class="btn-ghost modal-close !py-3 px-5">انصراف</button>
        </div>
    </form>
</div>

{{-- داده‌های سرور برای اسکریپت صفحه (بدون JS درون‌خطی) --}}
<div id="page-data" hidden data-payload="{{ json_encode(['referral_reward' => $referralActive ? $referralReward : 0]) }}"></div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pages/trash.css') }}?v=3">
@endpush

{{-- [Task 3-d] چسب CRUD — عیناً همان منطق pages/admin/coffeenets/index.js اما
     روی ریشهٔ Livewire bind شده (بدون رندر جدول که حالا کار Livewire است) --}}
@include('livewire.admin.entities.partials.trash-glue', ['trashSection' => 'coffeenets', 'rootId' => 'lw-coffeenets'])

<script>
(function () {
    const root = document.getElementById('lw-coffeenets');
    if (!root) return;

    /* اسکریپت درون‌خطی کامپوننت پیش از @livewireScripts لایه اجرا می‌شود؛
       تا آماده‌شدن App/Livewire و ثبت کامپوننت صبر می‌کنیم (بار اول). */
    const ready = () => window.App && window.Livewire
        && window.Livewire.find(root.getAttribute('wire:id'));

    function init() {
    const $wire = window.Livewire.find(root.getAttribute('wire:id'));
    const PAGE = App.pageData();
    window.__referralReward = Number(PAGE.referral_reward) || 0;

    const modal = document.getElementById('modal');
    const form = document.getElementById('modal-form');

    /* ---------- مودال ---------- */
    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        form.reset();
        document.getElementById('f-id').value = '';
        document.getElementById('approve-wrap').classList.remove('hidden');
        document.getElementById('manager-basic-fields').classList.remove('hidden');
        document.getElementById('f-city').disabled = true;
        document.getElementById('f-city').innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
        document.getElementById('modal-title').textContent = 'ثبت کافی‌نت جدید';
        document.getElementById('pass-hint').textContent = '(حداقل ۸ کاراکتر)';
        document.getElementById('no-manager-hint').classList.add('hidden');
        document.getElementById('f-manager-password').required = true;
        form.querySelectorAll('.err').forEach(e => e.classList.add('hidden'));
        form.querySelectorAll('.field').forEach(e => e.classList.remove('field-error'));
    }

    /* ---------- سلکت آبشاری استان/شهر (عیناً قبلی) ---------- */
    async function loadCities(provinceId, citySelect, selectedName) {
        citySelect.disabled = true;
        citySelect.innerHTML = '<option value="">در حال بارگذاری…</option>';
        try {
            const res = await App.ajax('/admin/geo/cities?province_id=' + provinceId);
            const data = await res.json();
            citySelect.innerHTML = '<option value="">— انتخاب شهرستان —</option>' + data.cities.map(c =>
                `<option value="${c.id}">${c.name}</option>`).join('');
            if (selectedName) {
                for (const opt of citySelect.options) {
                    if (opt.text === selectedName) { citySelect.value = opt.value; break; }
                }
            }
            citySelect.disabled = false;
        } catch {
            citySelect.innerHTML = '<option value="">خطا در بارگذاری شهرستان‌ها</option>';
        }
    }

    document.getElementById('f-province').addEventListener('change', function () {
        const citySelect = document.getElementById('f-city');
        if (!this.value) {
            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
            return;
        }
        loadCities(this.value, citySelect);
    });

    /* ---------- ویرایش (دادهٔ ردیف از data-row) ---------- */
    async function openEdit(row) {
        if (!row) return;
        document.getElementById('f-id').value = row.id;
        document.getElementById('f-name').value = row.name || '';
        document.getElementById('f-phone').value = row.phone || '';
        document.getElementById('f-address').value = row.address || '';
        document.getElementById('f-org').value = row.organization_id || '';
        document.getElementById('approve-wrap').classList.add('hidden');
        document.getElementById('manager-basic-fields').classList.remove('hidden');
        document.getElementById('f-manager-name').value = row.manager_name || '';
        document.getElementById('f-manager-family').value = row.manager_family || '';
        document.getElementById('f-manager-email').value = row.manager_email || '';
        document.getElementById('f-manager-mobile').value = row.manager_mobile || '';
        document.getElementById('f-manager-email').required = true;
        document.getElementById('f-manager-password').value = '';
        document.getElementById('modal-title').textContent = 'ویرایش کافی‌نت «' + (row.name || '') + '»';

        // v28 — کافی‌نت بدون کاربر مدیر (معرفی‌شده توسط سازمان): راهنما + الزامی‌شدن رمز
        const hasManager = !!(row.manager_email || row.manager_user_id);
        document.getElementById('no-manager-hint').classList.toggle('hidden', hasManager);
        document.getElementById('f-manager-name').required = true;
        document.getElementById('f-manager-password').required = !hasManager;
        document.getElementById('pass-hint').textContent = hasManager
            ? '(خالی = بدون تغییر رمز مدیر)'
            : '(الزامی — حساب مدیر ساخته می‌شود)';

        // بازسازی استان/شهر
        const provSelect = document.getElementById('f-province');
        const citySelect = document.getElementById('f-city');
        let matched = false;
        for (const opt of provSelect.options) {
            if (opt.text === row.province) {
                provSelect.value = opt.value;
                if (opt.value) await loadCities(opt.value, citySelect, row.city);
                matched = true;
                break;
            }
        }
        if (!matched) {
            provSelect.value = '';
            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
        }

        openModal();
    }

    /* ---------- ارسال فرم (عیناً همان اندپوینت‌ها) ---------- */
    function fieldId(name) {
        const map = {
            manager_name: 'f-manager-name', manager_family: 'f-manager-family',
            manager_email: 'f-manager-email', manager_mobile: 'f-manager-mobile',
            manager_password: 'f-manager-password', name: 'f-name', phone: 'f-phone', city_id: 'f-city',
        };
        return document.getElementById(map[name] || ('f-' + name));
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        form.querySelectorAll('.err').forEach(el => el.classList.add('hidden'));
        form.querySelectorAll('.field').forEach(el => el.classList.remove('field-error'));

        const id = document.getElementById('f-id').value;

        const payload = {
            name: document.getElementById('f-name').value.trim(),
            phone: document.getElementById('f-phone').value.trim() || null,
            province_id: document.getElementById('f-province').value || null,
            city_id: document.getElementById('f-city').value || null,
            address: document.getElementById('f-address').value.trim() || null,
            organization_id: document.getElementById('f-org').value || null,
        };

        if (id) {
            payload.manager_password = document.getElementById('f-manager-password').value || null;
            payload.manager_name = document.getElementById('f-manager-name').value.trim() || null;
            payload.manager_family = document.getElementById('f-manager-family').value.trim() || null;
            payload.manager_email = document.getElementById('f-manager-email').value.trim() || null;
            payload.manager_mobile = document.getElementById('f-manager-mobile').value.trim() || null;
        } else {
            payload.approve = document.getElementById('f-approve').checked;
            payload.manager_name = document.getElementById('f-manager-name').value.trim();
            payload.manager_family = document.getElementById('f-manager-family').value.trim() || null;
            payload.manager_email = document.getElementById('f-manager-email').value.trim();
            payload.manager_mobile = document.getElementById('f-manager-mobile').value.trim() || null;
            payload.manager_password = document.getElementById('f-manager-password').value || null;
        }

        const btn = document.getElementById('modal-save');
        btn.disabled = true;
        btn.innerHTML = '<span class="size-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> ذخیره...';

        try {
            const res = await App.ajax(id ? `/admin/coffeenets/${id}` : '/admin/coffeenets', {
                method: id ? 'PUT' : 'POST',
                body: payload,
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                closeModal();
                App.toast(data.message || 'ذخیره شد.', 'success');
                $wire.$refresh();
            } else if (res.status === 422 && data.errors) {
                Object.entries(data.errors).forEach(([k, v]) => {
                    const el = form.querySelector(`.err[data-for="${k}"]`);
                    if (el) { el.textContent = v[0]; el.classList.remove('hidden'); }
                    const fld = fieldId(k);
                    if (fld) fld.classList.add('field-error');
                });
                App.toast(Object.values(data.errors)[0][0], 'error');
            } else {
                App.toast(data.message || 'خطا در ذخیره‌سازی.', 'error');
            }
        } finally {
            btn.disabled = false;
            btn.textContent = 'ذخیره کافی‌نت';
        }
    });

    /* ---------- تغییر وضعیت (PATCH همان کنترلر) ---------- */
    const statusTexts = {
        approved: 'این کافی‌نت تأیید و فعال شود؟',
        suspended: 'این کافی‌نت به‌طور موقت تعلیق شود؟',
        rejected: 'این کافی‌نت رد شود؟',
    };

    function askStatus(id, status) {
        const doStatus = async () => {
            try {
                const res = await App.ajax(`/admin/coffeenets/${id}/status`, {
                    method: 'PATCH',
                    body: { status },
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) { App.toast(data.message, 'success'); $wire.$refresh(); }
                else App.toast(data.message || 'خطا', 'error');
            } catch {
                App.toast('ارتباط با سرور برقرار نشد.', 'error');
            }
        };

        let desc = statusTexts[status] || 'آیا مطمئن هستید؟';

        if (status === 'approved') {
            const reward = window.__referralReward || 0;
            if (reward > 0) {
                desc += ' — 🏅 با تأیید، پاداش معرفی ' + App.money(reward) + ' به کیف پول سازمان معرف واریز می‌شود.';
            }
        }

        if (window.PanelUI) {
            window.PanelUI.confirm(
                {
                    title: 'تغییر وضعیت کافی‌نت',
                    desc,
                    okText: 'بله، انجام بده',
                    danger: status === 'rejected',
                    icon: 'question',
                },
                doStatus
            );
        } else {
            doStatus();
        }
    }

    /* ---------- bind روی ریشهٔ کامپوننت (ضد تکرار در ناوبری SPA) ---------- */
    root.addEventListener('click', (e) => {
        const statusBtn = e.target.closest('[data-status]');
        if (statusBtn) {
            askStatus(statusBtn.dataset.id, statusBtn.dataset.status);
            return;
        }
        if (e.target.closest('[data-new]')) { openModal(); return; }
        const editBtn = e.target.closest('[data-edit]');
        if (editBtn) {
            openEdit(JSON.parse(editBtn.getAttribute('data-row') || '{}'));
        }
    });

    form.querySelectorAll('.modal-close').forEach(b => b.addEventListener('click', closeModal));
    modal.querySelector('[data-close]').addEventListener('click', closeModal);
    }

    if (ready()) { init(); }
    else { const t = setInterval(() => { if (ready()) { clearInterval(t); init(); } }, 25); }
})();
</script>
