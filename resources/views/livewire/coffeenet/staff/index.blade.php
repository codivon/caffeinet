{{-- «کارمندان» پنل کافی‌نت — نسخهٔ Livewire 4 [Task 4]
     جدول و فیلترها سمت سرور (wire:model.live + WithPagination) با سریال‌سازی عیناً
     از StaffController@data؛ مودال افزودن/ویرایش با Alpine و همان فیلدها که با
     App.ajax به همان endpointهای کنترلر می‌رود (staff.store / staff.update /
     staff.toggle دست‌نخورده) — رفتار و پیام‌ها عیناً از index.js قبلی پورت شده.

     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div x-data="coffeenetStaffPage()">

    {{-- نوار ابزار --}}
    <section class="card p-4 sm:p-5 animate-fade-up">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">

            <div class="relative flex-1 min-w-0">
                <input type="search" class="field !py-2.5 pl-10" placeholder="جستجو بر اساس نام، ایمیل یا موبایل…" aria-label="جستجوی کارمندان" wire:model.live.debounce.350ms="q">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4.5 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <select class="field !py-2.5 !w-auto !text-xs min-w-36" aria-label="فیلتر سمت" wire:model.live="position">
                <option value="">همه سمت‌ها</option>
                <option value="manager">فقط مدیران</option>
                <option value="operator">فقط اپراتورها</option>
            </select>

            <select class="field !py-2.5 !w-auto !text-xs min-w-32" aria-label="فیلتر وضعیت" wire:model.live="status">
                <option value="">همه وضعیت‌ها</option>
                <option value="1">فعال</option>
                <option value="0">غیرفعال</option>
            </select>

            <button type="button" class="btn-primary btn-shine !py-2.5 !text-xs shrink-0" x-on:click="openAdd()">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                افزودن کارمند
            </button>
        </div>
    </section>

    {{-- جدول --}}
    <section class="card mt-4 animate-fade-up delay-1 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <h2 class="no-card-title text-sm font-extrabold text-stone-700">لیست کارمندان</h2>
            <span class="text-[11px] text-stone-400">
                @if ($rows->total())
                    {{ fa_number($rows->total()) }} کارمند — صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}
                @else
                    —
                @endif
            </span>
        </div>

        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                    <thead>
                    <tr>
                        <th>کارمند</th>
                        <th>موبایل</th>
                        <th>سمت</th>
                        <th>مدل حقوق</th>
                        <th>دسترسی‌ها</th>
                        <th>وضعیت</th>
                        <th>آخرین ورود</th>
                        <th class="!text-center">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr class="group" wire:key="staff-{{ $row['id'] }}">
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="grid place-items-center size-9 rounded-xl {{ $row['is_self'] ? 'bg-amber-200 text-amber-800' : 'bg-stone-100 text-stone-500' }} text-xs font-bold shrink-0">{{ mb_substr($row['name'] ?: '؟', 0, 1) }}</span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-stone-700 truncate">{{ $row['full_name'] }}@if ($row['is_self'])<span class="badge bg-amber-50 text-amber-700 border border-amber-200 ms-1">شما</span>@endif</p>
                                        <p class="text-[10px] text-stone-400 truncate" dir="ltr">{{ $row['email'] }}</p>
                                        @if ($row['user_active'] === false)
                                            <span class="text-[10px] text-rose-500">حساب کاربری غیرفعال است</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-[11px] text-stone-500" dir="ltr">{{ $row['mobile'] ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $row['position']['value'] === 'manager' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-stone-50 text-stone-600 border border-stone-200' }}">{{ $row['position']['label'] }}</span>
                            </td>
                            <td>
                                @if ($row['salary'])
                                    <span class="text-[11px] font-semibold text-stone-600">{{ $row['salary']['type_label'] }} — {{ $row['salary']['type'] === 'percent' ? fa_money($row['salary']['rate'], false).'٪' : fa_money($row['salary']['rate']) }}</span>
                                    @if ($row['salary']['overtime_rate'] !== null && $row['salary']['overtime_rate'] > 0)
                                        <span class="block text-[10px] text-stone-400">اضافه‌کار: {{ fa_money($row['salary']['overtime_rate']) }}</span>
                                    @endif
                                @else
                                    <span class="text-[11px] text-stone-400">تعیین نشده</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['position']['value'] === 'manager')
                                    <span class="text-[11px] text-stone-400">کامل</span>
                                @else
                                    <span class="badge bg-stone-50 text-stone-600 border border-stone-200">{{ fa_number($row['permissions_count']) }} مورد</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['approval_status'] === 'pending')
                                    <span class="badge bg-amber-50 text-amber-700 border border-amber-300">⏳ در انتظار تایید مدیر کل</span>
                                @else
                                    <span class="badge {{ $row['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }}">{{ $row['is_active'] ? 'فعال' : 'غیرفعال' }}</span>
                                @endif
                            </td>
                            <td class="text-[11px] text-stone-400 whitespace-nowrap">{{ $row['last_login_at'] }}</td>
                            <td>
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" class="ui-row-btn" title="ویرایش" aria-label="ویرایش کارمند"
                                            data-row='@json($row)' x-on:click="openEdit($el)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                                    </button>
                                    @unless ($row['is_self'])
                                        <button type="button" class="ui-row-btn {{ $row['is_active'] ? '' : 'no-row-activate' }}" {{ $row['is_active'] ? 'data-tone="danger"' : '' }}
                                                data-id="{{ $row['id'] }}" data-active="{{ $row['is_active'] ? 1 : 0 }}" data-name="{{ $row['full_name'] }}"
                                                title="{{ $row['is_active'] ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}" aria-label="{{ $row['is_active'] ? 'غیرفعال‌سازی کارمند' : 'فعال‌سازی کارمند' }}"
                                                x-on:click="toggle($el)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $row['is_active'] ? 'M18.36 6.64A9 9 0 1 1 5.64 6.64' : 'm21 12-9-9-9 9' }}"/><path d="M12 2v10"/></svg>
                                        </button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="!py-10">
                            <div class="ui-empty">
                                <span class="ui-empty-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </span>
                                <p class="text-sm font-semibold text-stone-500">کارمندی یافت نشد</p>
                                <p class="text-xs text-stone-400 mt-1">اولین کارمند خود را با دکمه «افزودن کارمند» ثبت کنید.</p>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- صفحه‌بندی — عیناً فوتر قبلی --}}
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between gap-3 flex-wrap text-xs text-stone-500">
            @if ($rows->lastPage() > 1)
                <span>{{ fa_number($rows->total()) }} کارمند — صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}</span>
                <div class="flex gap-2">
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="previousPage" @disabled($rows->onFirstPage())>قبلی</button>
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="nextPage" @disabled(! $rows->hasMorePages())>بعدی</button>
                </div>
            @else
                <span>{{ fa_number($rows->total()) }} کارمند</span>
            @endif
        </div>
    </section>

    {{-- ================== مودال افزودن/ویرایش (بر پایهٔ ui-modal) ==================
         فیلدها/idها عیناً مثل index.js قبلی؛ ذخیره با App.ajax به همان endpointها --}}
    <div id="staff-modal" class="ui-modal-backdrop no-modal-scroll" role="dialog" aria-modal="true" aria-labelledby="modal-title" x-show="open" x-cloak
         x-effect="document.body.style.overflow = open ? 'hidden' : ''" @keydown.escape.window="open && close()">
        <div class="no-modal-veil" x-on:click="close()" aria-hidden="true"></div>

        <form class="ui-modal no-modal-form" novalidate x-on:submit.prevent="save()">

            {{-- سربرگ --}}
            <div class="no-modal-head">
                <span class="no-modal-head-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <div class="min-w-0">
                    <h3 id="modal-title" class="text-sm font-extrabold text-stone-800" x-text="editingId ? 'ویرایش کارمند' : 'افزودن کارمند جدید'">افزودن کارمند جدید</h3>
                    <p id="modal-subtitle" class="text-[11px] text-stone-400 mt-0.5" x-text="editingId ? editTitle : 'حساب کاربری، دسترسی‌ها و مدل حقوق در یک گام'">حساب کاربری، دسترسی‌ها و مدل حقوق در یک گام</p>
                </div>
                <button type="button" class="no-modal-close" x-on:click="close()" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

                <div class="p-5 sm:p-6 space-y-6 max-h-[70vh] overflow-y-auto">

                    {{-- خطای کلی --}}
                    <p id="modal-error" class="text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-3.5 py-2.5 leading-6" x-show="formError" x-text="formError" x-cloak></p>

                    {{-- ۱. اطلاعات هویتی --}}
                    <fieldset>
                        <legend class="text-xs font-extrabold text-stone-500 mb-3">۱) اطلاعات هویتی</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="lbl" for="f-name">نام <span class="text-rose-500">*</span></label>
                                <input id="f-name" name="name" type="text" class="field" placeholder="مثلاً رضا" autocomplete="off" x-model="form.name">
                                <p class="err text-[11px] text-rose-500 mt-1" data-for="name" x-show="errors.name" x-text="errors.name?.[0]" x-cloak></p>
                            </div>
                            <div>
                                <label class="lbl" for="f-family">نام خانوادگی</label>
                                <input id="f-family" name="family" type="text" class="field" placeholder="مثلاً محمدی" autocomplete="off" x-model="form.family">
                            </div>
                            <div>
                                <label class="lbl" for="f-email">ایمیل <span class="text-rose-500">*</span></label>
                                <input id="f-email" name="email" type="email" dir="ltr" class="field" placeholder="user@example.com" autocomplete="off" x-model="form.email" :disabled="editingId !== null">
                                <p class="text-[10px] text-stone-400 mt-1" id="email-hint" x-show="editingId === null">اگر قبلاً در سیستم ثبت شده باشد، به همین کافی‌نت متصل می‌شود.</p>
                                <p class="err text-[11px] text-rose-500 mt-1" data-for="email" x-show="errors.email" x-text="errors.email?.[0]" x-cloak></p>
                            </div>
                            <div>
                                <label class="lbl" for="f-mobile">موبایل</label>
                                <input id="f-mobile" name="mobile" type="text" dir="ltr" class="field" placeholder="09123456789" autocomplete="off" x-model="form.mobile">
                                <p class="err text-[11px] text-rose-500 mt-1" data-for="mobile" x-show="errors.mobile" x-text="errors.mobile?.[0]" x-cloak></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="lbl" for="f-password">رمز عبور <span id="password-req" class="text-rose-500" x-show="editingId === null">*</span></label>
                                <input id="f-password" name="password" type="password" dir="ltr" class="field" placeholder="حداقل ۸ کاراکتر" autocomplete="new-password" x-model="form.password">
                                <p class="text-[10px] text-stone-400 mt-1" id="password-hint">برای کاربر جدید الزامی است؛ هنگام ویرایش خالی بگذارید تا تغییر نکند.</p>
                                <p class="err text-[11px] text-rose-500 mt-1" data-for="password" x-show="errors.password" x-text="errors.password?.[0]" x-cloak></p>
                            </div>
                        </div>
                    </fieldset>

                    {{-- ۲. سمت و دسترسی‌ها --}}
                    <fieldset>
                        <legend class="text-xs font-extrabold text-stone-500 mb-3">۲) سمت و دسترسی‌ها</legend>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="position-card cursor-pointer rounded-2xl border-2 border-stone-200 px-4 py-3.5 flex items-center gap-3 transition-all has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/60">
                                <input type="radio" name="position" value="operator" class="accent-amber-600 size-4" x-model="form.position">
                                <div class="min-w-0">
                                    <p class="text-xs font-extrabold text-stone-700">اپراتور</p>
                                    <p class="text-[10px] text-stone-400 mt-0.5">دسترسی‌ها قابل شخصی‌سازی</p>
                                </div>
                            </label>
                            <label class="position-card cursor-pointer rounded-2xl border-2 border-stone-200 px-4 py-3.5 flex items-center gap-3 transition-all has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/60">
                                <input type="radio" name="position" value="manager" class="accent-amber-600 size-4" x-model="form.position">
                                <div class="min-w-0">
                                    <p class="text-xs font-extrabold text-stone-700">مدیر کافی‌نت</p>
                                    <p class="text-[10px] text-stone-400 mt-0.5">دسترسی کامل به پنل</p>
                                </div>
                            </label>
                        </div>

                        <div id="permissions-box" class="mt-4 rounded-2xl border border-stone-200 bg-stone-50/50 p-4 transition-opacity"
                             :class="form.position === 'manager' ? 'opacity-40 pointer-events-none' : ''">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-bold text-stone-600">دسترسی‌های این اپراتور</p>
                                <div class="flex items-center gap-2 text-[11px]">
                                    <button type="button" class="text-amber-700 hover:underline" x-on:click="setAllPerms(true)">انتخاب همه</button>
                                    <span class="text-stone-300">|</span>
                                    <button type="button" class="text-amber-700 hover:underline" x-on:click="setAllPerms(false)">حذف همه</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                @foreach ($permissionCatalog as $key => $label)
                                    <label class="perm-item cursor-pointer flex items-center gap-2.5 rounded-xl bg-white border border-stone-200 px-3 py-2.5 text-xs text-stone-600 hover:border-amber-300 transition-colors">
                                        <input type="checkbox" name="permissions" value="{{ $key }}" class="perm-check accent-amber-600 size-4 shrink-0" x-model="form.permissions">
                                        <span class="min-w-0">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-[10px] text-stone-400 mt-3 leading-5">این دسترسی‌ها فقط محدوده فعالیت اپراتور را در همین کافی‌نت تعیین می‌کنند و از فاز ۶ (سفارش‌ها) به بعد اعمال می‌شوند.</p>
                        </div>
                    </fieldset>

                    {{-- ۳. مدل حقوق --}}
                    <fieldset>
                        <legend class="text-xs font-extrabold text-stone-500 mb-3">۳) مدل حقوق</legend>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="lbl" for="f-salary-type">نوع قرارداد <span class="text-rose-500">*</span></label>
                                <select id="f-salary-type" name="salary_type" class="field" x-model="form.salary_type">
                                    <option value="percent">درصدی از هر سفارش</option>
                                    <option value="fixed_per_order">مبلغ ثابت هر سفارش</option>
                                    <option value="monthly">ماهیانه</option>
                                </select>
                            </div>
                            <div>
                                <label class="lbl" for="f-salary-rate"><span id="rate-label" x-text="rateLabel()">درصد</span> <span class="text-rose-500">*</span></label>
                                <input id="f-salary-rate" name="salary_rate" type="number" min="0" :max="rateMax()" step="0.01" dir="ltr" class="field" placeholder="مثلاً 30" x-model="form.salary_rate">
                                <p class="text-[10px] text-stone-400 mt-1" id="rate-hint" x-text="rateHint()">سهم کارمند از مبلغ سفارش (۰ تا ۱۰۰)</p>
                                <p class="err text-[11px] text-rose-500 mt-1" data-for="salary_rate" x-show="errors.salary_rate" x-text="errors.salary_rate?.[0]" x-cloak></p>
                            </div>
                            <div>
                                <label class="lbl" for="f-overtime">نرخ اضافه‌کار <span class="text-stone-400 font-normal">(اختیاری)</span></label>
                                <input id="f-overtime" name="overtime_rate" type="number" min="0" step="0.01" dir="ltr" class="field" placeholder="مثلاً 250000" x-model="form.overtime_rate">
                                <p class="text-[10px] text-stone-400 mt-1">تومان — صرفاً گزارشی برای ماهیانه</p>
                            </div>
                        </div>
                    </fieldset>
                </div>

                {{-- فوتر --}}
                <div class="no-modal-foot">
                    <button type="button" class="btn-ghost !py-2.5 !text-xs" x-on:click="close()">انصراف</button>
                    <button type="submit" id="btn-save" class="btn-primary btn-shine !py-2.5 !text-xs !px-6" :disabled="busy">
                        <span x-show="!busy" class="inline-flex items-center gap-2">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                            <span x-text="editingId ? 'ذخیره تغییرات' : 'ذخیره کارمند'">ذخیره کارمند</span>
                        </span>
                        <span x-show="busy" x-cloak class="inline-flex items-center gap-2">
                            <span class="size-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                            در حال ذخیره…
                        </span>
                    </button>
                </div>
            </form>
    </div>

</div>

@script
<script>
/* پورت عیناً منطق مودال/ذخیره/تغییر وضعیت از back/assets/js/pages/coffeenet/staff/index.js
   با این تفاوت که لیست سمت سرور رندر می‌شود و پس از هر عملیات $wire.$refresh() */
function coffeenetStaffPage() {
    return {
        open: false,
        busy: false,
        editingId: null,
        editingSelf: false,
        editTitle: '',
        formError: '',
        errors: {},
        form: { name: '', family: '', email: '', mobile: '', password: '', position: 'operator', permissions: [], salary_type: 'percent', salary_rate: '', overtime_rate: '' },
        catalog: @js($permissionCatalog),
        defaults: @js($permissionDefaults),
        base: '/coffeenet/{{ $coffeenet->id }}/staff',

        rateLabel() {
            return this.form.salary_type === 'percent' ? 'درصد'
                : (this.form.salary_type === 'fixed_per_order' ? 'مبلغ هر سفارش' : 'حقوق ماهیانه');
        },
        rateHint() {
            return this.form.salary_type === 'percent' ? 'سهم کارمند از مبلغ سفارش (۰ تا ۱۰۰)'
                : (this.form.salary_type === 'fixed_per_order' ? 'تومان — مبلغ ثابت به‌ازای هر سفارش موفق' : 'تومان — مبلغ ثابت ماهانه (گزارشی)');
        },
        rateMax() { return this.form.salary_type === 'percent' ? '100' : '1000000000'; },

        setAllPerms(on) {
            this.form.permissions = on ? Object.keys(this.catalog) : [];
        },

        openAdd() {
            this.editingId = null;
            this.editingSelf = false;
            this.editTitle = '';
            this.formError = '';
            this.errors = {};
            this.form = { name: '', family: '', email: '', mobile: '', password: '', position: 'operator', permissions: [...this.defaults], salary_type: 'percent', salary_rate: '', overtime_rate: '' };
            this.open = true;
            setTimeout(() => document.getElementById('f-name')?.focus(), 100);
        },

        openEdit(btn) {
            const row = JSON.parse(btn.dataset.row);
            this.editingId = row.id;
            this.editingSelf = !!row.is_self;
            this.editTitle = row.full_name;
            this.formError = '';
            this.errors = {};
            this.form = {
                name: row.name || '',
                family: row.family || '',
                email: row.email || '',
                mobile: row.mobile || '',
                password: '',
                position: this.editingSelf ? 'manager' : row.position.value,
                permissions: [...(row.permissions || [])],
                salary_type: row.salary ? row.salary.type : 'percent',
                salary_rate: row.salary ? row.salary.rate : '',
                overtime_rate: row.salary && row.salary.overtime_rate !== null ? row.salary.overtime_rate : '',
            };
            this.open = true;
            setTimeout(() => document.getElementById('f-name')?.focus(), 100);
        },

        close() {
            this.open = false;
            document.body.style.overflow = '';
            this.editingId = null;
            this.editingSelf = false;
        },

        /* اعتبارسنجی سمت کلاینت — عیناً مثل index.js */
        clientErrors() {
            const errors = {};
            if (!this.form.name.trim()) errors.name = ['نام کارمند الزامی است.'];
            if (!this.editingId && !this.form.email.trim()) errors.email = ['ایمیل کارمند الزامی است.'];
            if (!this.editingId && !this.form.password) errors.password = ['برای کارمند جدید تعیین رمز عبور الزامی است.'];
            if (this.form.salary_rate === '' || isNaN(+this.form.salary_rate)) errors.salary_rate = ['مقدار حقوق الزامی است.'];
            return errors;
        },

        async save() {
            this.formError = '';
            this.errors = {};

            const errors = this.clientErrors();
            if (Object.keys(errors).length) {
                this.errors = errors;
                this.formError = Object.values(errors)[0][0];
                return;
            }

            const payload = {
                name: this.form.name.trim(),
                family: this.form.family.trim() || null,
                email: this.form.email.trim(),
                mobile: this.form.mobile.trim() || null,
                password: this.form.password || null,
                position: this.form.position,
                salary_type: this.form.salary_type,
                salary_rate: this.form.salary_rate,
                overtime_rate: this.form.overtime_rate || null,
            };
            if (payload.position === 'operator') {
                payload.permissions = [...this.form.permissions];
            }

            this.busy = true;
            try {
                const res = await App.ajax(this.editingId ? this.base + '/' + this.editingId : this.base, {
                    method: this.editingId ? 'PUT' : 'POST',
                    body: payload,
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    App.toast(data.message || 'ذخیره شد.', 'success');
                    this.close();
                    $wire.$refresh();
                } else if (res.status === 422 && data.errors) {
                    this.errors = data.errors;
                    this.formError = data.message || Object.values(data.errors)[0][0];
                } else {
                    this.formError = data.message || 'خطا در ذخیره‌سازی.';
                }
            } catch {
                this.formError = 'ارتباط با سرور برقرار نشد.';
            } finally {
                this.busy = false;
            }
        },

        /* فعال/غیرفعال‌سازی — همان PanelUI.confirm و PATCH کنترلر */
        async toggle(btn) {
            const name = btn.dataset.name;
            const deactivating = btn.dataset.active === '1';

            const run = async () => {
                btn.disabled = true;
                try {
                    const res = await App.ajax(this.base + '/' + btn.dataset.id + '/toggle', { method: 'PATCH' });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok) {
                        App.toast(data.message || 'انجام شد.', 'success');
                        $wire.$refresh();
                    } else {
                        btn.disabled = false;
                        App.toast(data.message || 'خطا در تغییر وضعیت.', 'error');
                    }
                } catch {
                    btn.disabled = false;
                    App.toast('ارتباط با سرور برقرار نشد.', 'error');
                }
            };

            if (window.PanelUI) {
                window.PanelUI.confirm({
                    title: (deactivating ? 'غیرفعال‌سازی' : 'فعال‌سازی') + ' کارمند',
                    desc: 'آیا مطمئن هستید که «' + name + '» ' + (deactivating ? 'غیرفعال' : 'فعال') + ' شود؟',
                    okText: deactivating ? 'غیرفعال شود' : 'فعال شود',
                    danger: deactivating,
                    icon: 'question',
                }, run);
            } else {
                run();
            }
        },
    };
}
</script>
@endscript
