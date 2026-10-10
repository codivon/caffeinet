{{-- «تنظیمات» پنل کافی‌نت — نسخهٔ Livewire 4 [Task 4]
     ساختار عیناً از back/coffeenet/settings/index.blade.php حفظ شده؛
     ذخیره اطلاعات (PUT coffeenet.settings.update)، تغییر رمز
     (PUT coffeenet.settings.password) و آبشاری شهرها (coffeenet.geo.cities)
     همان endpointهای قبلی کنترلر هستند — فرم‌ها با Alpine/App.ajax
     (پورت عیناً از pages/coffeenet/settings/index.js). --}}
<div x-data="coffeenetSettingsPage()">

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- اطلاعات پرونده --}}
        <section class="card ui-lift p-5 animate-fade-up xl:col-span-1 h-fit overflow-hidden relative">
            <span class="ui-orb" data-pos="tr" aria-hidden="true"></span>
            <h2 class="no-card-title text-sm font-extrabold text-stone-700 relative">پرونده کافی‌نت</h2>
            <p class="text-[11px] text-stone-400 mt-1 mb-4 relative">اطلاعات ثابت — تغییرات فقط از طریق مدیریت کل</p>

            <dl class="space-y-3 text-xs">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-stone-400">وضعیت</dt>
                    <dd>
                        <span class="badge {{ $coffeenet->status === \App\Enums\CoffeenetStatus::Approved ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">{{ $coffeenet->status->label() }}</span>
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-stone-400">وابستگی</dt>
                    <dd class="font-semibold text-stone-600">{{ $coffeenet->organization?->name ?? 'کافی‌نت مستقل' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-stone-400">تاریخ تأیید</dt>
                    <dd class="font-semibold text-stone-600">{{ $coffeenet->approved_at ? jdate($coffeenet->approved_at)->format('Y/m/d') : '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-stone-400">ایمیل مدیر</dt>
                    <dd class="font-semibold text-stone-600 truncate" dir="ltr">{{ auth()->user()->email }}</dd>
                </div>
            </dl>
        </section>

        {{-- فرم ویرایش اطلاعات --}}
        <section class="card ui-lift p-5 xl:col-span-2 animate-fade-up delay-1 overflow-hidden relative">
            <span class="ui-orb" data-tone="amber" data-pos="bl" aria-hidden="true"></span>
            <h2 class="no-card-title text-sm font-extrabold text-stone-700 relative">اطلاعات تماس و آدرس</h2>
            <p class="text-[11px] text-stone-400 mt-1 mb-5 relative">این اطلاعات برای مشتریان و پشتیبانی نمایش داده می‌شود.</p>

            <form class="space-y-4" novalidate x-on:submit.prevent="saveInfo()">
                <p class="text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-3.5 py-2.5 leading-6" x-show="infoError" x-text="infoError" x-cloak></p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="i-name">نام کافی‌نت <span class="text-rose-500">*</span></label>
                        <input id="i-name" type="text" class="field" value="{{ $coffeenet->name }}" autocomplete="off" x-model="form.name">
                        <p class="err text-[11px] text-rose-500 mt-1" data-for="name" x-show="infoErrors.name" x-text="infoErrors.name?.[0]" x-cloak></p>
                    </div>
                    <div>
                        <label class="lbl" for="i-phone">تلفن</label>
                        <input id="i-phone" type="text" dir="ltr" class="field" value="{{ $coffeenet->phone ?? '' }}" placeholder="02112345678" autocomplete="off" x-model="form.phone">
                    </div>
                    <div>
                        <label class="lbl" for="i-province">استان</label>
                        <select id="i-province" class="field" x-model="form.province_id" x-on:change="onProvinceChange()">
                            <option value="">— انتخاب استان —</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}" {{ $province->id === $coffeenet->province_id ? 'selected' : '' }}>{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lbl" for="i-city">شهرستان</label>
                        <select id="i-city" class="field" x-model="form.city_id" :disabled="cities.length === 0">
                            <option value="">— انتخاب شهرستان —</option>
                            <template x-for="city in cities" :key="city.id">
                                <option :value="city.id" x-text="city.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="lbl" for="i-address">نشانی</label>
                        <textarea id="i-address" rows="3" class="field !py-2.5" maxlength="1000" placeholder="مثلاً: تهران، خیابان ولیعصر، پلاک ۱۲" x-model="form.address">{{ $coffeenet->address ?? '' }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" id="btn-info" class="btn-primary btn-shine !py-2.5 !text-xs !px-6" :disabled="busyInfo">
                        <span x-show="!busyInfo" class="inline-flex items-center gap-2">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                            ذخیره اطلاعات
                        </span>
                        <span x-show="busyInfo" x-cloak class="inline-flex items-center gap-2">
                            <span class="size-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                            در حال ذخیره…
                        </span>
                    </button>
                </div>
            </form>
        </section>

        {{-- تغییر رمز --}}
        <section class="card ui-lift p-5 xl:col-span-3 animate-fade-up delay-2 overflow-hidden relative">
            <span class="ui-orb" data-tone="teal" data-pos="tr" aria-hidden="true"></span>
            <h2 class="no-card-title text-sm font-extrabold text-stone-700 relative">تغییر رمز عبور</h2>
            <p class="text-[11px] text-stone-400 mt-1 mb-5 relative">رمز عبور ورود شما به پنل کافی‌نت.</p>

            <form class="space-y-4 max-w-xl relative" novalidate x-on:submit.prevent="changePassword()">
                <p class="text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-3.5 py-2.5 leading-6" x-show="passError" x-text="passError" x-cloak></p>

                <div>
                    <label class="lbl" for="p-current">رمز عبور فعلی <span class="text-rose-500">*</span></label>
                    <input id="p-current" type="password" dir="ltr" class="field" autocomplete="current-password" x-model="pass.current_password" required>
                    <p class="err text-[11px] text-rose-500 mt-1" data-for="current_password" x-show="passErrors.current_password" x-text="passErrors.current_password?.[0]" x-cloak></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="p-new">رمز عبور جدید <span class="text-rose-500">*</span></label>
                        <input id="p-new" type="password" dir="ltr" class="field" autocomplete="new-password" x-model="pass.password" minlength="8" required>
                        <p class="err text-[11px] text-rose-500 mt-1" data-for="password" x-show="passErrors.password" x-text="passErrors.password?.[0]" x-cloak></p>
                    </div>
                    <div>
                        <label class="lbl" for="p-confirm">تکرار رمز جدید <span class="text-rose-500">*</span></label>
                        <input id="p-confirm" type="password" dir="ltr" class="field" autocomplete="new-password" x-model="pass.password_confirmation" minlength="8" required>
                        <p class="err text-[11px] text-rose-500 mt-1" data-for="password_confirmation" x-show="passErrors.password_confirmation" x-text="passErrors.password_confirmation?.[0]" x-cloak></p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" id="btn-pass" class="btn-primary btn-shine !py-2.5 !text-xs !px-6" :disabled="busyPass">
                        <span x-show="!busyPass" class="inline-flex items-center gap-2">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            تغییر رمز عبور
                        </span>
                        <span x-show="busyPass" x-cloak class="inline-flex items-center gap-2">
                            <span class="size-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                            در حال تغییر…
                        </span>
                    </button>
                </div>
            </form>
        </section>
    </div>

    {{-- داده‌های سرور برای اسکریپت صفحه (بدون JS درون‌خطی) — عیناً همان قرارداد قبلی --}}
    <div id="page-data" hidden data-payload="{{ json_encode(['base' => '/coffeenet/'.$coffeenet->id.'/settings', 'city_id' => (int) $coffeenet->city_id]) }}"></div>

</div>

@script
<script>
/* پورت عیناً منطق back/assets/js/pages/coffeenet/settings/index.js —
   همان endpointهای PUT coffeenet.settings.update / PUT settings.password
   و آبشاری GET coffeenet.geo.cities با همان پیام‌ها و ۴۲۲ */
function coffeenetSettingsPage() {
    return {
        base: App.pageData().base,
        cityId: App.pageData().city_id,
        busyInfo: false,
        busyPass: false,
        infoError: '',
        passError: '',
        infoErrors: {},
        passErrors: {},
        cities: [],
        form: {
            name: document.getElementById('i-name').value.trim(),
            phone: document.getElementById('i-phone').value.trim(),
            province_id: document.getElementById('i-province').value,
            city_id: '',
            address: document.getElementById('i-address').value.trim(),
        },
        pass: { current_password: '', password: '', password_confirmation: '' },

        init() {
            if (this.form.province_id) {
                this.loadCities(this.form.province_id, this.cityId);
            }
        },

        /* سلکت آبشاری استان → شهرستان — عیناً مثل index.js */
        async loadCities(provinceId, selectedId) {
            try {
                const res = await App.ajax('/coffeenet/geo/cities?province_id=' + provinceId);
                const data = await res.json();
                this.cities = data.cities || [];
                this.form.city_id = selectedId ? String(selectedId) : '';
            } catch {
                this.cities = [];
            }
        },

        onProvinceChange() {
            if (this.form.province_id) {
                this.loadCities(this.form.province_id, null);
            } else {
                this.cities = [];
                this.form.city_id = '';
            }
        },

        /* ذخیره اطلاعات — عیناً saveInfo */
        async saveInfo() {
            this.infoError = '';
            this.infoErrors = {};

            if (!this.form.name.trim()) {
                this.infoErrors = { name: ['نام کافی‌نت الزامی است.'] };
                this.infoError = 'نام کافی‌نت الزامی است.';
                return;
            }

            this.busyInfo = true;
            try {
                const res = await App.ajax(this.base, {
                    method: 'PUT',
                    body: {
                        name: this.form.name.trim(),
                        phone: this.form.phone.trim() || null,
                        province_id: this.form.province_id ? +this.form.province_id : null,
                        city_id: this.form.city_id ? +this.form.city_id : null,
                        address: this.form.address.trim() || null,
                    },
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    App.toast(data.message || 'اطلاعات ذخیره شد.', 'success');
                    $wire.$refresh();
                } else if (res.status === 422 && data.errors) {
                    this.infoErrors = data.errors;
                    this.infoError = data.message || Object.values(data.errors)[0][0];
                } else {
                    this.infoError = data.message || 'خطا در ذخیره‌سازی.';
                }
            } catch {
                this.infoError = 'ارتباط با سرور برقرار نشد.';
            } finally {
                this.busyInfo = false;
            }
        },

        /* تغییر رمز — عیناً changePassword */
        async changePassword() {
            this.passError = '';
            this.passErrors = {};

            const errors = {};
            if (!this.pass.current_password) errors.current_password = ['رمز فعلی الزامی است.'];
            if (!this.pass.password || this.pass.password.length < 8) errors.password = ['رمز جدید حداقل ۸ کاراکتر باشد.'];
            if (this.pass.password !== this.pass.password_confirmation) errors.password_confirmation = ['تکرار رمز مطابقت ندارد.'];
            if (Object.keys(errors).length) {
                this.passErrors = errors;
                this.passError = Object.values(errors)[0][0];
                return;
            }

            this.busyPass = true;
            try {
                const res = await App.ajax(this.base + '/password', {
                    method: 'PUT',
                    body: {
                        current_password: this.pass.current_password,
                        password: this.pass.password,
                        password_confirmation: this.pass.password_confirmation,
                    },
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    App.toast(data.message || 'رمز عبور تغییر کرد.', 'success');
                    this.pass = { current_password: '', password: '', password_confirmation: '' };
                } else if (res.status === 422 && data.errors) {
                    this.passErrors = data.errors;
                    this.passError = data.message || Object.values(data.errors)[0][0];
                } else {
                    this.passError = data.message || 'خطا در تغییر رمز.';
                }
            } catch {
                this.passError = 'ارتباط با سرور برقرار نشد.';
            } finally {
                this.busyPass = false;
            }
        },
    };
}
</script>
@endscript
