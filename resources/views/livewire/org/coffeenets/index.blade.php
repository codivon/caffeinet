{{-- «کافی‌نت‌های من» سازمان — نسخهٔ Livewire 4 [Task 6]
     ساختار و کلاس‌ها عیناً از back/org/coffeenets/index.blade.php حفظ شده است.
     لیست سمت سرور با جستجو/فیلتر wire:model.live؛ مودال معرفی با همان endpointهای
     کنترلر (POST org.coffeenets.store + GET org.geo.cities آبشاری) — منطق فرم
     (اعتبارسنجی سمت کلاینت، نگاشت خطای ۴۲۲، سلکت آبشاری) عیناً از index.js قبلی.
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div>

    {{-- بنر پاداش معرفی --}}
    @if ($referral->is_active && (float) $referral->introduction_reward > 0)
        <div class="card ui-lift p-4 mb-5 animate-fade-up bg-gradient-to-l from-emerald-50 to-white border-emerald-200/70 flex flex-wrap items-center gap-3 justify-between relative overflow-hidden">
            <span class="ui-orb" data-tone="emerald" data-pos="tr" aria-hidden="true"></span>
            <div class="flex items-center gap-3.5 relative">
                <span class="ui-chip" data-tone="ok">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/></svg>
                </span>
                <div>
                    <p class="text-sm font-extrabold text-emerald-800 flex items-center gap-2">
                        <span class="ui-dot text-emerald-500"></span>
                        پاداش معرفی فعال است
                    </p>
                    <p class="text-[11px] text-emerald-700/80 mt-0.5">با تأیید هر کافی‌نت جدید، <strong>{{ fa_money($referral->introduction_reward) }}</strong> به‌صورت خودکار به کیف پول شما واریز می‌شود.</p>
                </div>
            </div>
        </div>
    @endif

    <section class="card ui-lift animate-fade-up overflow-hidden">

        {{-- هدر جدول --}}
        <div class="px-5 py-4 border-b border-stone-100 flex flex-wrap items-center gap-3 justify-between">
            <div class="flex flex-1 min-w-52 flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-44 max-w-sm">
                    <input wire:model.live.debounce.350ms="q" type="search" class="field !py-2.5 pl-10" placeholder="جستجو: نام کافی‌نت..." aria-label="جستجوی کافی‌نت‌ها">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <select wire:model.live="status" class="field !py-2.5 !w-auto min-w-36" aria-label="فیلتر وضعیت">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending">در انتظار تأیید</option>
                    <option value="approved">تأییدشده</option>
                    <option value="rejected">ردشده</option>
                    <option value="suspended">معلق</option>
                </select>
            </div>
            <button type="button" id="btn-new" wire:click="openModal" class="btn-primary btn-shine no-btn-teal">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                معرفی کافی‌نت جدید
            </button>
        </div>

        {{-- جدول --}}
        <div class="table-wrap">
            <table class="table-panel table-modern">
                <thead>
                    <tr>
                        <th>کافی‌نت</th>
                        <th>موقعیت</th>
                        <th>پاداش معرفی</th>
                        <th>وضعیت</th>
                        <th>تاریخ ثبت</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        /* نقشهٔ کلاس بج‌ها — عیناً statusColors نسخهٔ JS قبلی */
                        $statusColors = [
                            'amber' => 'bg-amber-50 text-amber-700 border border-amber-200',
                            'emerald' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                            'rose' => 'bg-rose-50 text-rose-600 border border-rose-200',
                            'stone' => 'bg-stone-100 text-stone-500 border border-stone-200',
                        ];
                    @endphp
                    @forelse ($rows as $c)
                        <tr wire:key="cn-{{ $c['id'] }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid place-items-center size-9 rounded-xl bg-gradient-to-br from-teal-400/70 to-teal-600/70 text-white font-bold text-xs shrink-0">{{ mb_substr($c['name'] ?: '؟', 0, 1) }}</span>
                                    <div>
                                        <p class="font-bold text-stone-800">{{ $c['name'] }}</p>
                                        <p class="text-[11px] text-stone-400">{{ $c['phone'] ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-stone-500">{{ $c['city'] ? $c['city'].'، ' : '' }}{{ $c['province'] ?? '—' }}</td>
                            <td>
                                @if ($c['reward_paid'])
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">🏅 واریز شد</span>
                                @else
                                    <span class="text-[11px] text-stone-400">—</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $statusColors[$c['status']['color']] ?? $statusColors['stone'] }}">{{ $c['status']['label'] }}</span></td>
                            <td class="text-stone-500 text-xs">{{ $c['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="!py-10">
                                <div class="ui-empty">
                                    <span class="ui-empty-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 7h20"/><path d="M12 7v5"/><rect x="3" y="7" width="18" height="14" rx="1"/></svg>
                                    </span>
                                    <p class="text-sm font-semibold text-stone-500">کافی‌نتی یافت نشد.</p>
                                    <p class="text-xs text-stone-400 mt-1">اولین کافی‌نت را با دکمه «معرفی کافی‌نت جدید» ثبت کنید.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی — عیناً فوتر قبلی (renderPagination) --}}
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
            @if ($rows->lastPage() > 1)
                <span>{{ fa_number($rows->total()) }} کافی‌نت — صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}</span>
                <div class="flex gap-2">
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="previousPage" @disabled($rows->onFirstPage())>قبلی</button>
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="nextPage" @disabled(! $rows->hasMorePages())>بعدی</button>
                </div>
            @else
                <span>{{ fa_number($rows->total()) }} کافی‌نت</span>
            @endif
        </div>
    </section>

    {{-- ================== مودال معرفی کافی‌نت (بر پایهٔ ui-modal) ==================
         باز/بسته با Livewire ($modalOpen) — با هر بازشدن فرم تازه (مثل closeModal قبلی)؛
         ثبت با همان endpoint کنترلر (POST org.coffeenets.store) و سلکت آبشاری
         شهرها با GET org.geo.cities — عیناً منطق index.js قبلی --}}
    @if ($modalOpen)
        <div class="ui-modal-backdrop no-modal-scroll flex" role="dialog" aria-modal="true" aria-labelledby="modal-title"
             x-data="{
                 busy: false,
                 /* ساخت گزینه‌ها با DOM API — بدون کوتیشن درون attribute (CSP-safe) */
                 fillCitySelect(citySelect, placeholder, options) {
                     citySelect.innerHTML = '';
                     const head = document.createElement('option');
                     head.value = '';
                     head.textContent = placeholder;
                     citySelect.appendChild(head);
                     options.forEach(c => {
                         const opt = document.createElement('option');
                         opt.value = c.id;
                         opt.textContent = c.name;
                         citySelect.appendChild(opt);
                     });
                 },
                 async loadCities(provinceId) {
                     const citySelect = this.$refs.city;
                     citySelect.disabled = true;
                     this.fillCitySelect(citySelect, 'در حال بارگذاری…', []);
                     try {
                         const res = await App.ajax('{{ route('org.geo.cities') }}?province_id=' + provinceId);
                         const data = await res.json();
                         this.fillCitySelect(citySelect, '— انتخاب شهرستان —', data.cities);
                         citySelect.disabled = false;
                     } catch {
                         this.fillCitySelect(citySelect, 'خطا در بارگذاری', []);
                     }
                 },
                 provinceChanged(value) {
                     if (!value) {
                         const citySelect = this.$refs.city;
                         citySelect.disabled = true;
                         this.fillCitySelect(citySelect, 'ابتدا استان…', []);
                         return;
                     }
                     this.loadCities(value);
                 },
                 async submit() {
                     const errEl = this.$refs.nameErr;
                     errEl.classList.add('hidden');
                     this.$refs.name.classList.remove('field-error');

                     const payload = {
                         name: this.$refs.name.value.trim(),
                         phone: this.$refs.phone.value.trim() || null,
                         province_id: this.$refs.province.value || null,
                         city_id: this.$refs.city.value || null,
                         address: this.$refs.address.value.trim() || null,
                     };

                     if (!payload.name) {
                         errEl.textContent = 'نام کافی‌نت الزامی است.';
                         errEl.classList.remove('hidden');
                         this.$refs.name.classList.add('field-error');
                         return;
                     }

                     this.busy = true;
                     try {
                         const res = await App.ajax('{{ route('org.coffeenets.store') }}', { method: 'POST', body: payload });
                         const data = await res.json().catch(() => ({}));

                         if (res.ok) {
                             App.toast(data.message || 'ثبت شد.', 'success');
                             $wire.closeModal();
                         } else if (res.status === 422 && data.errors) {
                             Object.entries(data.errors).forEach(([k, v]) => {
                                 const el = this.$refs.form.querySelector('[data-for=' + k + ']');
                                 if (el) { el.textContent = v[0]; el.classList.remove('hidden'); }
                                 const fld = document.getElementById('f-' + k.replace('_id', ''));
                                 if (fld) fld.classList.add('field-error');
                             });
                             App.toast(Object.values(data.errors)[0][0], 'error');
                         } else {
                             App.toast(data.message || 'خطا در ثبت.', 'error');
                         }
                     } finally {
                         this.busy = false;
                     }
                 }
             }">
            <div class="no-modal-veil" wire:click="closeModal" data-close aria-hidden="true"></div>
            <form class="ui-modal no-modal-form" novalidate x-on:submit.prevent="submit" x-ref="form">
                <div class="no-modal-head">
                    <span class="no-modal-head-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 7h20"/><path d="M12 7v5"/><rect x="3" y="7" width="18" height="14" rx="1"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h3 id="modal-title" class="text-sm font-extrabold text-stone-800">معرفی کافی‌نت جدید</h3>
                        <p class="text-[11px] text-stone-400 mt-0.5">ثبت اولیه و ارسال برای بررسی مدیریت کل</p>
                    </div>
                    <button type="button" class="no-modal-close modal-close" wire:click="closeModal" aria-label="بستن">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <div class="p-5 sm:p-6 space-y-4">
                    <p class="ui-note">
                        کافی‌نت با وضعیت «در انتظار تأیید» ثبت می‌شود و پس از بررسی و تأیید مدیریت کل فعال خواهد شد.
                        مدیر کافی‌نت توسط مدیریت کل تعیین می‌شود.
                    </p>

                <div>
                    <label class="lbl" for="f-name">نام کافی‌نت <span class="text-rose-500">*</span></label>
                    <input id="f-name" class="field" autocomplete="off" placeholder="کافی‌نت نمونه" x-ref="name">
                    <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="name" x-ref="nameErr"></p>
                </div>

                <div>
                    <label class="lbl" for="f-phone">تلفن (اختیاری)</label>
                    <input id="f-phone" class="field" dir="ltr" autocomplete="off" x-ref="phone">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="lbl" for="f-province">استان</label>
                        <select id="f-province" class="field" x-on:change="provinceChanged($event.target.value)" x-ref="province">
                            <option value="">— انتخاب استان —</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lbl" for="f-city">شهرستان</label>
                        <select id="f-city" class="field" disabled x-ref="city">
                            <option value="">ابتدا استان…</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="lbl" for="f-address">نشانی (اختیاری)</label>
                    <textarea id="f-address" class="field min-h-20" rows="2" autocomplete="off" x-ref="address"></textarea>
                </div>

                <div class="no-modal-foot">
                    <button type="button" class="btn-ghost !py-2.5 !text-xs modal-close" wire:click="closeModal">انصراف</button>
                    <button type="submit" id="modal-save" class="btn-primary btn-shine no-btn-teal !py-2.5 !text-xs" x-bind:disabled="busy">
                        <span x-show="!busy" class="inline-flex items-center gap-2">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                            ثبت برای بررسی
                        </span>
                        <span x-show="busy" style="display:none" class="inline-flex items-center gap-2">
                            <span class="size-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> در حال ثبت...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
