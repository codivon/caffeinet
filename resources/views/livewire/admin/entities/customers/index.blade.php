{{-- «مدیریت مشتریان» پنل مدیریت کل — نسخهٔ Livewire 4 [Task 3-d]
     جدول و فیلترها سمت سرور (wire:model.live)؛ مودال‌های ویرایش/بن با Alpine
     و همان اندپوینت‌های کنترلر (PUT customers.update / PATCH ban-unban).
     حذف نرم با ماژول مشترک AdminTrash (delegated — سازگار با مورف Livewire). --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pages/org-operators.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/pages/trash.css') }}?v=3">
@endpush
<div x-data="customersPage()">

    <section class="card ui-lift animate-fade-up overflow-hidden">

        {{-- هدر جدول --}}
        <div class="adm-card-head">
            <div class="flex flex-1 min-w-64 flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-44 max-w-sm">
                    <input type="search" class="field !py-2.5 pl-10" placeholder="جستجو: نام، موبایل، ایمیل..." aria-label="جستجوی مشتریان" wire:model.live.debounce.300ms="search">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <select class="field !py-2.5 !w-auto min-w-32" aria-label="فیلتر وضعیت" wire:model.live="status">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="active">فعال</option>
                    <option value="banned">مسدود (بن)</option>
                </select>
                <select class="field !py-2.5 !w-auto min-w-36" aria-label="فیلتر پروفایل" wire:model.live="profile">
                    <option value="">همه پروفایل‌ها</option>
                    <option value="complete">کامل</option>
                    <option value="incomplete">ناقص</option>
                </select>
                <select class="field !py-2.5 !w-auto min-w-36" aria-label="فیلتر استان" wire:model.live="provinceId">
                    <option value="">همه استان‌ها</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                    @endforeach
                </select>
                <select class="field !py-2.5 !w-auto min-w-36" aria-label="مرتب‌سازی" wire:model.live="sort">
                    <option value="newest">جدیدترین عضویت</option>
                    <option value="old">قدیمی‌ترین عضویت</option>
                    <option value="orders"> بیشترین سفارش</option>
                    <option value="spent">بیشترین پرداخت</option>
                    <option value="wallet">بیشترین موجودی کیف</option>
                    <option value="login">آخرین ورود</option>
                </select>
            </div>
        </div>

        {{-- جدول --}}
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                    <thead>
                        <tr>
                            <th>مشتری</th>
                            <th>استان / شهر</th>
                            <th>وضعیت</th>
                            <th>پروفایل</th>
                            <th class="text-center">سفارش‌ها</th>
                            <th class="text-center">مجموع پرداخت</th>
                            <th class="text-center">کیف پول</th>
                            <th>آخرین ورود</th>
                            <th class="text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr wire:key="c{{ $r['id'] }}" class="ops-row" title="پروفایل کامل {{ $r['full_name'] }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="grid place-items-center size-9 rounded-xl bg-gradient-to-br {{ $r['is_active'] ? 'from-blue-400/70 to-blue-600/70' : 'from-stone-300 to-stone-400' }} text-white font-bold text-xs shrink-0">{{ mb_substr($r['name'] ?? '؟', 0, 1) }}</span>
                                        <div>
                                            <a href="{{ route('admin.customers.show', $r['id']) }}" wire:navigate class="font-bold text-stone-800 hover:text-blue-700">{{ $r['full_name'] }} <span class="ops-open-hint" aria-hidden="true">↖</span></a>
                                            <p class="text-[11px] text-stone-400" dir="ltr">{{ $r['mobile'] ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-stone-600 text-xs">{{ $r['province'] ? ($r['province'].' / '.($r['city'] ?? '—')) : '—' }}</td>
                                <td>
                                    @if ($r['is_active'])
                                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">فعال</span>
                                    @else
                                        <span class="badge bg-rose-50 text-rose-600 border border-rose-200">مسدود</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($r['profile_completed'])
                                        <span class="badge bg-teal-50 text-teal-700 border border-teal-200">کامل</span>
                                    @else
                                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">ناقص</span>
                                    @endif
                                </td>
                                <td class="text-center font-bold tabular-nums {{ $r['orders'] > 0 ? 'text-sky-600' : 'text-stone-400' }}">{{ fa_number($r['orders']) }}</td>
                                <td class="text-center font-bold tabular-nums {{ $r['spent'] > 0 ? 'text-emerald-600' : 'text-stone-400' }}">{{ fa_money($r['spent'], false) }}</td>
                                <td class="text-center font-bold tabular-nums {{ $r['wallet'] > 0 ? 'text-blue-600' : 'text-stone-400' }}">{{ fa_money($r['wallet'], false) }}</td>
                                <td class="text-stone-500 text-xs whitespace-nowrap">{{ $r['last_login_fa'] ?? '—' }}</td>
                                <td class="text-center whitespace-nowrap">
                                    <button type="button" class="btn-ghost !py-1.5 !px-3 !text-[11px] ui-press" x-on:click="openEdit(@js($r))" title="ویرایش اطلاعات">ویرایش</button>
                                    @if ($r['is_active'])
                                        <button type="button" class="btn-ghost !py-1.5 !px-3 !text-[11px] ui-press !text-rose-600 hover:!bg-rose-50" x-on:click="openBan(@js($r))" title="مسدودسازی (خروج اجباری)">مسدود</button>
                                    @else
                                        <button type="button" class="btn-ghost !py-1.5 !px-3 !text-[11px] ui-press !text-emerald-600 hover:!bg-emerald-50" x-on:click="unban(@js($r))" title="رفع مسدودی">رفع بن</button>
                                    @endif
                                    <button type="button" class="btn-ghost !py-1.5 !px-3 !text-[11px] ui-press !text-rose-600 hover:!bg-rose-50" data-trash="{{ $r['id'] }}" data-trash-label="{{ $r['name'] ?? '' }} {{ $r['family'] ?? '' }}" title="حذف (به حذف‌شده‌ها)">حذف</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-stone-400 text-xs">مشتری‌ای یافت نشد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="adm-table-foot text-xs text-stone-500">
            @if ($rows->lastPage() > 1)
                <span>{{ fa_number($rows->total()) }} مشتری — صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}</span>
                <div class="flex gap-2">
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" @disabled($rows->onFirstPage()) wire:click="previousPage">قبلی</button>
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" @disabled($rows->onLastPage()) wire:click="nextPage">بعدی</button>
                </div>
            @else
                <span>{{ fa_number($rows->total()) }} مشتری</span>
            @endif
        </div>
    </section>

    {{-- ================== مودال ویرایش کامل مشتری ================== --}}
    <div class="ui-modal-backdrop hidden" id="edit-modal" role="dialog" aria-modal="true">
        <div class="absolute inset-0" x-on:click="closeEdit()" aria-hidden="true"></div>

        <form class="ui-modal adm-modal-lg adm-modal-text-start" data-tone="info" role="document" novalidate x-show="edit" x-cloak
              x-on:submit.prevent="saveEdit()">
            <div class="adm-modal-head">
                <div>
                    <h3 class="text-sm font-extrabold text-stone-800">ویرایش اطلاعات مشتری</h3>
                    <p class="text-[11px] text-stone-400 mt-0.5" x-text="edit ? (edit.full_name + ' — ' + (edit.mobile || '')) : '—'">—</p>
                </div>
                <button type="button" class="adm-modal-x" x-on:click="closeEdit()" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="adm-modal-body space-y-4" x-show="edit">
                <div class="ui-note" data-tone="info">
                    مدیر کل می‌تواند <strong>همهٔ اطلاعات</strong> مشتری را ویرایش کند: مشخصات فردی، جغرافیا، تاریخ تولد (شمسی)، وضعیت پروفایل و حساب.
                    موبایل، شمارهٔ ورود مشتری به اپ است و پس از تغییر، ورود با شمارهٔ جدید انجام می‌شود.
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="lbl" for="f-name">نام <span class="text-rose-500">*</span></label>
                        <input id="f-name" class="field" autocomplete="off" x-model="form.name">
                        <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="name"></p>
                    </div>
                    <div>
                        <label class="lbl" for="f-family">نام خانوادگی</label>
                        <input id="f-family" class="field" autocomplete="off" x-model="form.family">
                    </div>
                    <div>
                        <label class="lbl" for="f-mobile">موبایل (شمارهٔ ورود) <span class="text-rose-500">*</span></label>
                        <input id="f-mobile" type="tel" dir="ltr" class="field" placeholder="09xxxxxxxxx" autocomplete="off" x-model="form.mobile">
                        <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="mobile"></p>
                    </div>
                    <div>
                        <label class="lbl" for="f-email">ایمیل</label>
                        <input id="f-email" type="email" dir="ltr" class="field" autocomplete="off" x-model="form.email">
                        <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="email"></p>
                    </div>
                    <div>
                        <label class="lbl" for="f-gender">جنسیت</label>
                        <select id="f-gender" class="field" x-model="form.gender">
                            <option value="">نامشخص</option>
                            <option value="male">مرد</option>
                            <option value="female">زن</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl" for="f-birthdate">تاریخ تولد (شمسی)</label>
                        <input id="f-birthdate" type="text" dir="ltr" class="field num !text-center" placeholder="۱۳۷۰/۰۵/۱۲"
                               data-jdp data-jdp-min-years-ago="100" data-jdp-max-years-ago="10" title="برای انتخاب تاریخ کلیک کنید" x-model="form.birthdate">
                        <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="birthdate"></p>
                    </div>
                    <div>
                        <label class="lbl" for="f-province">استان</label>
                        <select id="f-province" class="field" x-model="form.province_id" x-on:change="loadCities($event.target.value)">
                            <option value="">انتخاب استان…</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lbl" for="f-city">شهر</label>
                        <select id="f-city" class="field" x-model="form.city_id">
                            <template x-if="!cities.length"><option value="">ابتدا استان را انتخاب کنید…</option></template>
                            <template x-for="c in cities" :key="c.id">
                                <option value="" x-bind:value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                        <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="city_id"></p>
                    </div>
                </div>

                <hr class="border-stone-100">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                    <div class="flex items-end gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="size-4 accent-blue-600" x-model="form.profile_completed">
                            <span class="text-xs font-bold text-stone-700">پروفایل کامل</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="size-4 accent-blue-600" x-model="form.is_active">
                            <span class="text-xs font-bold text-stone-700">حساب فعال</span>
                        </label>
                    </div>
                    <div>
                        <label class="lbl" for="f-password">رمز عبور جدید</label>
                        <input id="f-password" type="password" dir="ltr" class="field" placeholder="(خالی = بدون تغییر)" autocomplete="new-password" x-model="form.password">
                        <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="password"></p>
                    </div>
                </div>

                <p class="field-error hidden" id="edit-error"></p>
            </div>

            <div class="adm-modal-foot">
                <button type="button" class="btn-ghost ui-press !py-2.5 !text-xs" x-on:click="closeEdit()">انصراف</button>
                <button type="submit" class="btn-primary btn-shine !py-2.5 !text-xs" :disabled="busy">ذخیرهٔ تغییرات</button>
            </div>
        </form>
    </div>

    {{-- ================== مودال مسدودسازی (بن) ================== --}}
    <div class="ui-modal-backdrop hidden" id="ban-modal" role="dialog" aria-modal="true">
        <div class="absolute inset-0" x-on:click="closeBan()" aria-hidden="true"></div>

        <form class="ui-modal adm-modal-md adm-modal-text-start" data-tone="danger" role="document" novalidate x-show="ban" x-cloak
              x-on:submit.prevent="saveBan()">
            <div class="adm-modal-head">
                <div>
                    <h3 class="text-sm font-extrabold text-stone-800">مسدودسازی مشتری</h3>
                    <p class="text-[11px] text-stone-400 mt-0.5" x-text="ban ? (ban.full_name + ' — ' + (ban.mobile || '')) : '—'">—</p>
                </div>
                <button type="button" class="adm-modal-x" x-on:click="closeBan()" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="adm-modal-body">
                <div class="ui-note" data-tone="danger">
                    مشتری مسدودشده <strong>نمی‌تواند وارد اپ شود</strong> (ورود با کد تأیید رد می‌شود) و
                    همهٔ جلسه‌های فعال او فوراً بسته می‌شوند. سفارش‌ها و تاریخچه‌اش حفظ می‌شود.
                </div>

                <div class="mt-4">
                    <label class="lbl" for="ban-reason">دلیل مسدودسازی <span class="text-rose-500">*</span></label>
                    <textarea id="ban-reason" rows="3" maxlength="490" class="field !text-xs w-full"
                              placeholder="مثلاً: تخلف در ثبت سفارش / فیش جعلی / رفتار نامناسب با اپراتور…" x-model="banReason"></textarea>
                    <p class="field-error mt-2 hidden" id="ban-error">حداقل ۳ حرف لازم است.</p>
                </div>
            </div>

            <div class="adm-modal-foot">
                <button type="button" class="btn-ghost ui-press !py-2.5 !text-xs" x-on:click="closeBan()">انصراف</button>
                <button type="submit" class="ui-btn-danger !py-2.5 !text-xs" :disabled="busy">مسدودسازی حساب</button>
            </div>
        </form>
    </div>

</div>

@script
<script>
function customersPage() {
    return {
        edit: null,
        ban: null,
        busy: false,
        cities: [],
        citiesCache: {},
        banReason: '',
        form: { name: '', family: '', mobile: '', email: '', gender: '', birthdate: '', province_id: '', city_id: '', profile_completed: false, is_active: false, password: '' },

        toEnDigits(v) {
            return String(v || '').replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
                .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
        },

        openEdit(r) {
            this.edit = r;
            this.form = {
                name: r.name || '',
                family: r.family || '',
                mobile: r.mobile || '',
                email: r.email || '',
                gender: r.gender || '',
                birthdate: r.birthdate_fa || '',
                province_id: r.province_id ? String(r.province_id) : '',
                city_id: r.city_id ? String(r.city_id) : '',
                profile_completed: !!r.profile_completed,
                is_active: !!r.is_active,
                password: '',
            };
            this.loadCities(this.form.province_id, this.form.city_id);
            this.$nextTick(() => this.openModal('edit-modal'));
        },

        closeEdit() {
            this.edit = null;
            this.closeModal('edit-modal');
        },

        async saveEdit() {
            if (!this.edit || this.busy) return;
            document.querySelectorAll('#edit-form .err').forEach(el => el.classList.add('hidden'));
            document.getElementById('edit-error').classList.add('hidden');

            this.busy = true;
            try {
                const res = await App.ajax('/admin/customers/' + this.edit.id, {
                    method: 'PUT',
                    body: {
                        name: this.form.name.trim(),
                        family: this.form.family.trim() || null,
                        mobile: this.form.mobile.trim(),
                        email: this.form.email.trim() || null,
                        gender: this.form.gender || null,
                        birthdate: this.toEnDigits(this.form.birthdate).trim(),
                        province_id: parseInt(this.form.province_id, 10) || null,
                        city_id: parseInt(this.form.city_id, 10) || null,
                        profile_completed: this.form.profile_completed,
                        is_active: this.form.is_active,
                        password: this.form.password || null,
                    },
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    App.toast(data.message || 'ذخیره شد.', 'success');
                    this.closeEdit();
                    this.$wire.$refresh();
                } else if (res.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([k, v]) => {
                        const el = document.querySelector('#edit-form .err[data-for="' + k + '"]');
                        if (el) { el.textContent = v[0]; el.classList.remove('hidden'); }
                    });
                    App.toast(data.message || Object.values(data.errors)[0][0], 'error');
                } else {
                    const el = document.getElementById('edit-error');
                    el.textContent = data.message || 'خطا در ذخیره‌سازی.';
                    el.classList.remove('hidden');
                    App.toast(data.message || 'خطا در ذخیره‌سازی.', 'error');
                }
            } catch {
                const el = document.getElementById('edit-error');
                el.textContent = 'ارتباط با سرور برقرار نشد.';
                el.classList.remove('hidden');
            } finally {
                this.busy = false;
            }
        },

        async loadCities(provinceId, selectedId) {
            if (!provinceId) { this.cities = []; return; }
            if (!this.citiesCache[provinceId]) {
                try {
                    const res = await App.ajax('/admin/geo/cities?province_id=' + provinceId);
                    const data = await res.json();
                    this.citiesCache[provinceId] = data.cities || [];
                } catch { this.citiesCache[provinceId] = []; }
            }
            this.cities = this.citiesCache[provinceId];
            if (selectedId) {
                this.$nextTick(() => { this.form.city_id = String(selectedId); });
            }
        },

        openBan(r) {
            this.ban = r;
            this.banReason = '';
            document.getElementById('ban-error').classList.add('hidden');
            this.$nextTick(() => this.openModal('ban-modal'));
        },

        closeBan() {
            this.ban = null;
            this.closeModal('ban-modal');
        },

        async saveBan() {
            if (!this.ban || this.busy) return;
            const reason = this.banReason.trim();
            if (reason.length < 3) {
                document.getElementById('ban-error').classList.remove('hidden');
                return;
            }
            document.getElementById('ban-error').classList.add('hidden');

            this.busy = true;
            try {
                const res = await App.ajax('/admin/customers/' + this.ban.id + '/ban', {
                    method: 'PATCH',
                    body: { reason },
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    App.toast(data.message || 'مسدود شد.', 'success');
                    this.closeBan();
                    this.$wire.$refresh();
                } else {
                    App.toast(data.message || 'خطا در مسدودسازی.', 'error');
                }
            } catch {
                App.toast('ارتباط با سرور برقرار نشد.', 'error');
            } finally {
                this.busy = false;
            }
        },

        async unban(r) {
            if (!confirm('رفع مسدودی «' + r.full_name + '»؟ دوباره می‌تواند وارد اپ شود.')) return;
            try {
                const res = await App.ajax('/admin/customers/' + r.id + '/unban', { method: 'PATCH' });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    App.toast(data.message || 'فعال شد.', 'success');
                    this.$wire.$refresh();
                } else {
                    App.toast(data.message || 'خطا.', 'error');
                }
            } catch {
                App.toast('ارتباط با سرور برقرار نشد.', 'error');
            }
        },

        openModal(id) {
            const m = document.getElementById(id);
            if (!m) return;
            m.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        },

        closeModal(id) {
            const m = document.getElementById(id);
            if (!m) return;
            m.classList.add('hidden');
            document.body.style.overflow = '';
        },
    };
}
</script>
@endscript

@push('scripts')
<script src="{{ asset('back/assets/js/pages/trash.js') }}?v=2"></script>
<script>
    // mount گارد‌دار (attribute روی body) — اجرای تکراری در ناوبری wire:navigate بی‌ضرر است
    if (window.AdminTrash) AdminTrash.mount({ section: 'customers' });
</script>
@endpush
