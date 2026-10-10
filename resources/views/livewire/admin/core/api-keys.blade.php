{{-- کلیدهای وب‌سرویس (فاز ۴۸ — بازطراحی کامل) — از «تنظیمات» (آخرین منو) باز می‌شود.
     • افزودن فقط با مودال و «عنوان» الزامی؛ مقدار کلید خودکار ساخته می‌شود (cnk_…)
     • بعد از ساخت، کلید در همان مودال با دکمهٔ کپی نشان داده می‌شود
     • حذف با مودال تأیید (PanelUI.confirm — danger)
     • همهٔ درخواست‌های /api/v1 باید هدر «X-Api-Key» را بفرستند (میدل‌ویر api.key)
     • کلید داخلی وب‌اپ حذف‌شدنی نیست. --}}
<div x-data>
<style>[x-cloak]{display:none!important}</style>
<div class="sc-stack">

    {{-- ================== هدر صفحه + اکشن‌ها ================== --}}
    <div class="card ui-lift animate-fade-up overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">
            <span class="grid place-items-center size-14 rounded-2xl bg-gradient-to-br from-stone-800 to-stone-950 text-amber-200 shadow-lg shadow-stone-900/20 shrink-0" aria-hidden="true">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
            </span>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg sm:text-xl font-extrabold text-stone-800 dark:text-stone-100">کلیدهای وب‌سرویس</h1>
                <p class="mt-1 text-xs sm:text-sm leading-6 text-stone-500 dark:text-stone-400">
                    هر برنامه‌ای که به <b>API نسخهٔ ۱</b> وصل می‌شود (اپ موبایل، اسکریپت، همکار خارجی) به یک کلید نیاز دارد و باید آن را در هدر
                    <code class="font-mono bg-stone-100 dark:bg-stone-800 rounded px-1 py-0.5" dir="ltr">X-Api-Key</code>
                    بفرستد. مقدار کلیدها <b>خودکار</b> ساخته می‌شود؛ اگر کلیدی لو رفت فقط حذفش کنید و یک کلید تازه بسازید.
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.api-docs.index') }}" wire:navigate class="btn-ghost !py-2.5 !px-4 !text-xs ui-press whitespace-nowrap" title="راهنمای کامل اندپوینت‌ها و هدرها">
                    <svg class="inline-block size-4" style="vertical-align:-3px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    مستندات API
                </a>
                <button type="button" wire:click="openAdd" class="btn-primary btn-shine !py-2.5 !px-4 !text-xs ui-press whitespace-nowrap">
                    <svg class="inline-block size-4" style="vertical-align:-3px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    کلید جدید
                </button>
            </div>
        </div>

        {{-- نوار آمار --}}
        <div class="grid grid-cols-3 border-t border-stone-100 dark:border-stone-700/60 divide-x divide-x-reverse divide-stone-100 dark:divide-stone-700/60 text-center">
            <div class="py-3 px-2">
                <p class="text-[11px] font-bold text-stone-400">کلیدهای فعال</p>
                <p class="mt-0.5 text-base sm:text-lg font-extrabold text-stone-800 dark:text-stone-100">{{ fa_number(count($keys)) }}</p>
            </div>
            <div class="py-3 px-2">
                <p class="text-[11px] font-bold text-stone-400">مجموع درخواست‌ها</p>
                <p class="mt-0.5 text-base sm:text-lg font-extrabold text-stone-800 dark:text-stone-100">{{ fa_number($totalHits) }}</p>
            </div>
            <div class="py-3 px-2">
                <p class="text-[11px] font-bold text-stone-400">آخرین استفاده</p>
                <p class="mt-0.5 text-xs sm:text-sm font-extrabold text-stone-800 dark:text-stone-100 leading-6"><bdi dir="ltr">{{ $lastUsed ? fa_date($lastUsed) : '—' }}</bdi></p>
            </div>
        </div>
    </div>

    {{-- پیام‌ها --}}
    @if (session('ak-ok'))
        <div class="card p-3 sm:p-4 border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm font-bold animate-fade-up dark:!bg-emerald-900/30 dark:!border-emerald-800 dark:!text-emerald-300" role="status">{{ session('ak-ok') }}</div>
    @endif
    @if (session('ak-err'))
        <div class="card p-3 sm:p-4 border border-rose-200 bg-rose-50 text-rose-700 text-sm font-bold animate-fade-up dark:!bg-rose-900/30 dark:!border-rose-800 dark:!text-rose-300" role="alert">{{ session('ak-err') }}</div>
    @endif

    {{-- ================== فهرست کلیدها ================== --}}
    <div class="card p-5 animate-fade-up" style="animation-delay:.05s">
        <div class="flex items-center justify-between gap-2 mb-4">
            <h2 class="text-sm font-extrabold text-stone-800 dark:text-stone-100">کلیدها</h2>
            <span class="text-[11px] text-stone-400 font-bold">برای دیدن مقدار کامل، آیکون چشم را بزنید</span>
        </div>

        <div class="flex flex-col gap-3">
            @forelse ($keys as $k)
                <div class="group rounded-2xl border border-stone-200 dark:border-stone-700/70 bg-white dark:bg-stone-800/40 p-4 transition-all duration-200 hover:border-stone-300 dark:hover:border-stone-600 hover:shadow-sm {{ $k->is_internal ? '!bg-stone-50 dark:!bg-stone-800/70' : '' }}">
                    <div class="flex items-center gap-3">
                        <span class="grid place-items-center size-9 rounded-xl shrink-0 {{ $k->is_internal ? 'bg-stone-900 text-amber-200' : 'bg-stone-100 dark:bg-stone-700/60 text-stone-500 dark:text-stone-300' }}" aria-hidden="true">
                            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                        </span>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-extrabold text-sm text-stone-800 dark:text-stone-100 truncate">{{ $k->name ?: 'بدون عنوان' }}</span>
                                @if ($k->is_internal)
                                    <span class="badge bg-stone-900 text-amber-200 shrink-0" title="این کلید خودکار در صفحات تزریق می‌شود و اپ وب/پنل‌ها با آن کار می‌کنند">داخلی وب‌اپ</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-[11px] text-stone-400 font-medium">
                                ساخته: <bdi dir="ltr">{{ fa_date($k->created_at) }}</bdi>
                            </p>
                        </div>

                        @unless ($k->is_internal)
                            <button
                                type="button"
                                x-data
                                data-name="{{ $k->name ?: 'بدون عنوان' }}"
                                @click="window.PanelUI ? PanelUI.confirm({
                                    title: 'حذف کلید؟',
                                    desc: 'کلید «' + $el.dataset.name + '» حذف شود؟ همهٔ برنامه‌های متصل با این کلید، درخواست‌هایشان با خطای ۴۰۱ رد می‌شود. این اقدام قابل بازگشت نیست.',
                                    okText: 'بله، حذف شود', cancelText: 'انصراف', danger: true, icon: 'warn',
                                }, () => $wire.deleteKey({{ $k->id }})) : (confirm('کلید حذف شود؟') && $wire.deleteKey({{ $k->id }}))"
                                wire:loading.attr="disabled" wire:target="deleteKey({{ $k->id }})"
                                class="btn-ghost !py-1.5 !px-3 !text-xs text-rose-600 hover:!bg-rose-50 dark:text-rose-400 dark:hover:!bg-rose-900/20 shrink-0"
                                aria-label="حذف کلید {{ $k->name }}"
                            >
                                <svg class="inline-block size-4" style="vertical-align:-3px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                حذف
                            </button>
                        @endunless
                    </div>

                    {{-- مقدار کلید + نمایش/کپی --}}
                    <div class="mt-3 flex items-center gap-2" x-data="{ show: false, copied: false }">
                        <code dir="ltr" class="flex-1 min-w-0 font-mono text-[11px] sm:text-xs bg-stone-100 dark:bg-stone-800 rounded-xl px-3 py-2.5 overflow-x-auto whitespace-nowrap text-stone-600 dark:text-stone-300 border border-stone-200/60 dark:border-stone-700/50">
                            <span x-show="show" style="display:none">{{ $k->key }}</span><span x-show="!show" class="opacity-75">{{ mask_key($k->key) }}</span>
                        </code>
                        <button type="button" class="btn-ghost !p-2 shrink-0" @click="show = !show" :aria-pressed="show" :title="show ? 'پنهان‌کردن' : 'نمایش کلید'" aria-label="نمایش/پنهان‌کردن کلید">
                            <svg x-show="!show" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" x-cloak class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.744 10.744 0 0 1-1.744 2.8"/><path d="M6.21 6.21a10.744 10.744 0 0 0-3.948 6.036 1 1 0 0 0 0 .696 10.75 10.75 0 0 0 15.246 4.67"/><path d="m2 2 20 20"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                        <button type="button" class="btn-ghost !p-2 !text-[11px] font-bold shrink-0" @click="navigator.clipboard && navigator.clipboard.writeText('{{ $k->key }}').then(() => { copied = true; setTimeout(() => copied = false, 1600) })" :title="copied ? 'کپی شد!' : 'کپی کلید'" aria-label="کپی کلید">
                            <span x-show="!copied" class="flex items-center gap-1">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                کپی
                            </span>
                            <span x-show="copied" x-cloak class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                کپی شد
                            </span>
                        </button>
                    </div>

                    {{-- آمار استفاده --}}
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-stone-50 dark:bg-stone-800/80 border border-stone-100 dark:border-stone-700/60 px-2.5 py-1 text-[11px] font-bold text-stone-500 dark:text-stone-400" title="تعداد درخواست‌های ثبت‌شده با این کلید">
                            <svg class="size-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                            {{ fa_number($k->requests_count) }} درخواست
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-stone-50 dark:bg-stone-800/80 border border-stone-100 dark:border-stone-700/60 px-2.5 py-1 text-[11px] font-bold text-stone-500 dark:text-stone-400" title="آخرین باری که این کلید استفاده شد">
                            <svg class="size-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            آخرین استفاده: <bdi dir="ltr">{{ $k->last_used_at ? fa_date($k->last_used_at) : '—' }}</bdi>
                        </span>
                        @if ($k->is_internal)
                            <span class="text-[11px] text-stone-400 font-bold">این کلید خودکار در صفحات تزریق می‌شود — اپ وب و پنل‌ها با آن کار می‌کنند.</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-10">
                    <span class="mx-auto grid place-items-center size-14 rounded-2xl bg-stone-100 dark:bg-stone-800 text-stone-400" aria-hidden="true">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-stone-500 dark:text-stone-400">هنوز کلیدی ساخته نشده است.</p>
                    <button type="button" wire:click="openAdd" class="mt-3 btn-primary btn-shine !py-2.5 !px-5 !text-xs ui-press">ساخت اولین کلید</button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ================== نمونهٔ استفاده ================== --}}
    <div class="card p-5 animate-fade-up" style="animation-delay:.1s">
        <h2 class="text-sm font-extrabold text-stone-800 dark:text-stone-100 mb-2">نمونهٔ استفاده</h2>
        <pre dir="ltr" class="text-xs leading-6 bg-stone-900 text-stone-100 rounded-2xl p-4 overflow-x-auto font-mono">curl "https://example.ir/api/v1/services" \
  -H "X-Api-Key: cnk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Authorization: Bearer 1|customer-token"</pre>
        <ul class="mt-3 text-xs leading-6 text-stone-500 dark:text-stone-400 list-disc ps-5 space-y-1">
            <li>همهٔ اندپوینت‌های <code dir="ltr" class="font-mono">/api/v1/*</code> بدون کلید پاسخ <b>۴۰۱</b> می‌دهند — به‌جز <code dir="ltr" class="font-mono">/api/v1/health</code> که برای مانیتورینگ باز است.</li>
            <li>هر کلید شمارندهٔ استفاده و آخرین زمان استفاده دارد — برای شناسایی مصرف‌کنندهٔ هر کلید.</li>
            <li>مستندات کامل اندپوینت‌ها و هدرها: <a href="{{ route('admin.api-docs.index') }}" wire:navigate class="font-bold text-stone-700 dark:text-stone-200 underline decoration-dotted">مستندات API</a></li>
        </ul>
    </div>

</div>

{{-- ================== مودال «کلید جدید» (فاز ۴۸) ================== --}}
<div class="ui-modal-backdrop" x-show="$wire.addOpen" x-cloak x-transition.opacity.duration.200ms
     @keydown.escape.window="$wire.closeAdd()" role="dialog" aria-modal="true" aria-labelledby="ak-modal-title">
    <div class="absolute inset-0" wire:click="closeAdd" aria-hidden="true"></div>

    <form wire:submit="addKey" x-transition.scale.95.duration.200ms
          class="ui-modal adm-modal-sm adm-modal-text-start"
          data-tone="{{ $createdKey ? 'success' : 'info' }}" novalidate>

        <div class="adm-modal-head">
            <h3 class="text-sm font-extrabold text-stone-800" id="ak-modal-title">
                {{ $createdKey ? 'کلید ساخته شد' : 'کلید وب‌سرویس جدید' }}
            </h3>
            <button type="button" class="adm-modal-x" wire:click="closeAdd" aria-label="بستن">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div class="adm-modal-body">
            @if ($createdKey)
                {{-- ---------- صفحهٔ موفقیت: نمایش کلید تازه + کپی ---------- --}}
                <div class="text-center">
                    <span class="mx-auto grid place-items-center size-14 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400" aria-hidden="true">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-extrabold text-stone-800 dark:text-stone-100">کلید «{{ $createdKey['name'] }}» ساخته شد</p>
                    <p class="mt-1 text-[11px] leading-5 text-stone-400">از همین‌جا کپی کنید و در برنامهٔ مقصد در هدر <span dir="ltr" class="font-mono">X-Api-Key</span> قرار دهید.</p>
                </div>

                <div class="mt-3 flex items-center gap-2" x-data="{ copied: false }">
                    <code dir="ltr" class="flex-1 min-w-0 font-mono text-[11px] bg-stone-900 text-emerald-200 rounded-xl px-3 py-2.5 overflow-x-auto whitespace-nowrap">{{ $createdKey['key'] }}</code>
                    <button type="button" class="btn-primary !py-2 !px-3 !text-[11px] font-bold shrink-0" @click="navigator.clipboard && navigator.clipboard.writeText('{{ $createdKey['key'] }}').then(() => { copied = true; setTimeout(() => copied = false, 1600) })">
                        <span x-show="!copied">کپی کلید</span>
                        <span x-show="copied" x-cloak class="flex items-center gap-1">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            کپی شد
                        </span>
                    </button>
                </div>

                <p class="mt-2 text-[11px] leading-5 text-amber-600 dark:text-amber-400 font-bold">
                    این مقدار بعداً هم در فهرست کلیدها قابل مشاهده است؛ اما فقط از طریق همین پنل. اگر لو رفت، کلید را حذف کنید.
                </p>
            @else
                {{-- ---------- فرم ساخت: عنوان الزامی ---------- --}}
                <label class="lbl" for="ak-name">عنوان کلید <span class="text-rose-500" aria-hidden="true">*</span></label>
                <input
                    id="ak-name"
                    type="text"
                    wire:model="newName"
                    class="field"
                    placeholder="مثلاً: اپ موبایل همکار"
                    maxlength="60"
                    aria-required="true"
                    aria-describedby="ak-name-hint"
                >
                @error('newName')
                    <p class="mt-1.5 text-[11px] font-bold text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-[11px] leading-5 text-stone-400" id="ak-name-hint">
                    با یک عنوان مشخص، بعداً می‌فهمید هر کلید دستِ کدام برنامه است.
                    مقدار کلید به‌صورت خودکار با فرمت <span dir="ltr" class="font-mono">cnk_…</span> ساخته می‌شود و قابل ویرایش نیست.
                </p>
            @endif
        </div>

        <div class="adm-modal-foot">
            @if ($createdKey)
                <button type="button" wire:click="openAdd" class="btn-ghost">ساخت کلید دیگر</button>
                <button type="button" wire:click="closeAdd" class="btn-primary btn-shine">تمام شد</button>
            @else
                <button type="button" wire:click="closeAdd" class="btn-ghost">انصراف</button>
                <button type="submit" class="btn-primary btn-shine" wire:loading.attr="disabled" wire:target="addKey">
                    <span wire:loading.remove wire:target="addKey">ساخت خودکار کلید</span>
                    <span wire:loading wire:target="addKey" class="inline-flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z"/></svg>
                        در حال ساخت…
                    </span>
                </button>
            @endif
        </div>
    </form>
</div>

</div>
