{{-- کلیدهای وب‌سرویس (فاز ۴۷) — افزودن/حذف کلید دسترسی API.
     کلید خودکار ساخته می‌شود (cnk_…). همهٔ درخواست‌های /api/v1 باید
     هدر «X-Api-Key» را با یکی از این کلیدها بفرستند.
     کلید داخلی وب‌اپ حذف‌شدنی نیست. --}}
<div>
<div class="sc-stack">

    {{-- ================== هدر صفحه ================== --}}
    <div class="card ui-lift animate-fade-up p-5">
        <div class="flex items-start gap-3 sm:gap-4">
            <span class="grid place-items-center size-14 rounded-2xl bg-stone-900 text-amber-200 shadow-lg shrink-0" aria-hidden="true">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
            </span>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg sm:text-xl font-extrabold text-stone-800 dark:text-stone-100">کلیدهای وب‌سرویس</h1>
                <p class="mt-1 text-xs sm:text-sm leading-6 text-stone-500 dark:text-stone-400">
                    هر برنامه‌ای که بخواهد به <b>API نسخهٔ ۱</b> وصل شود (اپ موبایل، اسکریپت، همکار خارجی) باید یک کلید داشته باشد و آن را
                    در هدر <code class="font-mono bg-stone-100 dark:bg-stone-800 rounded px-1 py-0.5" dir="ltr">X-Api-Key</code> بفرستد.
                    کلیدها خودکار ساخته می‌شوند؛ اگر کلیدی لو رفت فقط حذفش کنید و یک کلید تازه بسازید.
                    مستندات کامل اندپوینت‌ها: <a href="{{ route('admin.api-docs.index') }}" class="text-stone-700 dark:text-stone-200 font-bold underline decoration-dotted">مستندات API</a>
                </p>
            </div>
        </div>
    </div>

    @if (session('ak-ok'))
        <div class="card p-3 sm:p-4 border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm font-bold animate-fade-up dark:!bg-emerald-900/30 dark:!border-emerald-800 dark:!text-emerald-300" role="status">
            {{ session('ak-ok') }}
        </div>
    @endif

    {{-- ================== فرم افزودن ================== --}}
    <div class="card p-5 animate-fade-up">
        <h2 class="text-sm font-extrabold text-stone-800 dark:text-stone-100 mb-3">ساخت کلید جدید</h2>
        <form wire:submit="addKey" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <input
                type="text"
                wire:model="newName"
                class="field flex-1 !py-2"
                placeholder="برچسب دلخواه (اختیاری) — مثلاً: اپ موبایل همکار"
                maxlength="60"
                aria-label="برچسب کلید جدید"
            >
            <button type="submit" class="btn-primary !py-2 !px-5 whitespace-nowrap ui-press shrink-0">
                <svg class="inline-block size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                ساخت خودکار کلید
            </button>
        </form>
        @error('newName')
            <p class="mt-2 text-xs font-bold text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
        @enderror
        <p class="mt-2 leading-5 text-stone-400">
            کلید بلافاصله با فرمت <code dir="ltr" class="font-mono">cnk_…</code> ساخته می‌شود و در فهرست پایین قابل کپی است.
        </p>
    </div>

    {{-- ================== فهرست کلیدها ================== --}}
    <div class="card p-5 animate-fade-up">
        <div class="flex items-center justify-between gap-2 mb-3">
            <h2 class="text-sm font-extrabold text-stone-800 dark:text-stone-100">کلیدهای فعال ({{ fa_number(count($keys)) }})</h2>
        </div>

        <div class="flex flex-col gap-3">
            @forelse ($keys as $k)
                <div class="rounded-2xl border border-stone-200 dark:border-stone-700/70 p-3 sm:p-4 {{ $k->is_internal ? 'bg-stone-50 dark:bg-stone-800/40' : '' }}">
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- برچسب + بج‌ها --}}
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <span class="font-extrabold text-sm text-stone-800 dark:text-stone-100 truncate">{{ $k->name ?: 'بدون برچسب' }}</span>
                            @if ($k->is_internal)
                                <span class="badge bg-stone-900 text-amber-200 shrink-0" title="این کلید خودکار در صفحات تزریق می‌شود و اپ وب/پنل‌ها با آن کار می‌کنند">داخلی وب‌اپ</span>
                            @endif
                        </div>

                        {{-- حذف --}}
                        @unless ($k->is_internal)
                            <button
                                type="button"
                                wire:click="deleteKey({{ $k->id }})"
                                wire:confirm="کلید «{{ $k->name ?: 'بدون برچسب' }}» حذف شود؟ برنامه‌های متصل با این کلید دیگر دسترسی ندارند."
                                class="btn-ghost !py-1 !px-3 !text-xs text-rose-600 hover:!bg-rose-50 dark:text-rose-400 dark:hover:!bg-rose-900/20 shrink-0"
                            >
                                <svg class="inline-block size-4" style="vertical-align:-3px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                حذف
                            </button>
                        @endunless
                    </div>

                    {{-- مقدار کلید + کپی — پیش‌فرض ماسک؛ Alpine بعد از لود کنترل می‌کند --}}
                    <div class="mt-2 flex items-center gap-2" x-data="{ show: false }">
                        <code dir="ltr" class="flex-1 min-w-0 font-mono text-xs bg-stone-100 dark:bg-stone-800 rounded-xl px-3 py-2 overflow-x-auto whitespace-nowrap text-stone-700 dark:text-stone-200">
                            <span x-show="show" style="display:none">{{ $k->key }}</span><span x-show="!show" class="opacity-70">{{ mask_key($k->key) }}</span>
                        </code>
                        <button type="button" class="btn-ghost !py-2 !px-2 !text-xs shrink-0" @click="show = !show" :aria-pressed="show" aria-label="نمایش/پنهان‌کردن کلید">
                            <svg x-show="!show" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" x-cloak class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.744 10.744 0 0 1-1.744 2.8"/><path d="M6.21 6.21a10.744 10.744 0 0 0-3.948 6.036 1 1 0 0 0 0 .696 10.75 10.75 0 0 0 15.246 4.67"/><path d="m2 2 20 20"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                        <button type="button" class="btn-ghost !py-2 !px-2 !text-xs shrink-0" x-data @click="navigator.clipboard && navigator.clipboard.writeText('{{ $k->key }}'); this.setAttribute('data-copied','1'); setTimeout(()=>this.removeAttribute('data-copied'),1500)" aria-label="کپی کلید">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        </button>
                    </div>

                    {{-- آمار استفاده --}}
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-stone-400">
                        <span title="تعداد درخواست‌های ثبت‌شده با این کلید">استفاده: {{ fa_number($k->requests_count) }} درخواست</span>
                        <span title="آخرین باری که این کلید استفاده شد">آخرین استفاده: <bdi dir="ltr">{{ $k->last_used_at ? fa_date($k->last_used_at) : '—' }}</bdi></span>
                        <span title="زمان ساخت">ساخته: <bdi dir="ltr">{{ fa_date($k->created_at) }}</bdi></span>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-stone-400 text-sm">
                    هنوز کلیدی ساخته نشده — از فرم بالا یک کلید بسازید.
                </div>
            @endforelse
        </div>
    </div>

    {{-- ================== راهنما ================== --}}
    <div class="card p-5 animate-fade-up">
        <h2 class="text-sm font-extrabold text-stone-800 dark:text-stone-100 mb-2">نمونهٔ استفاده</h2>
        <pre dir="ltr" class="text-xs leading-6 bg-stone-900 text-stone-100 rounded-2xl p-4 overflow-x-auto font-mono">curl "https://example.ir/api/v1/services" \
  -H "X-Api-Key: cnk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Authorization: Bearer 1|customer-token"</pre>
        <ul class="mt-3 text-xs leading-6 text-stone-500 dark:text-stone-400 list-disc ps-5 space-y-1">
            <li>همهٔ اندپوینت‌های <code dir="ltr" class="font-mono">/api/v1/*</code> بدون کلید پاسخ <b>۴۰۱</b> می‌دهند — به‌جز <code dir="ltr" class="font-mono">/api/v1/health</code> که برای مانیتورینگ باز است.</li>
            <li>کلید داخلی وب‌اپ خودکار در صفحات تزریق می‌شود؛ اپ مشتری و پنل‌ها بی‌دست‌کاری با API کار می‌کنند.</li>
            <li>هر کلید شمارندهٔ استفاده و آخرین زمان استفاده دارد — برای شناسایی مصرف‌کنندهٔ هر کلید.</li>
        </ul>
    </div>

</div>
</div>
