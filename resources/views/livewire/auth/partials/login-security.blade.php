{{-- فاز ۵۰ — امنیت مشترک ورود پنل‌ها: ربات‌گیر (کپچای ریاضی) + گام کد ۲FA
     داخل <form wire:submit="store"> هر صفحهٔ ورود اینکلود می‌شود.
     گام ۲FA جای فیلدهای ایمیل/رمز را می‌گیرد (در ویو با @unless($twoFaStep) بسته شده)
     و همان دکمهٔ ارسال، store() → verifyTwoFactor() را اجرا می‌کند.
     استایل خنثی (شیشه‌ای) تا روی هر ۴ تم ورود بنشیند. --}}
@if ($twoFaStep)
    {{-- ================= گام دوم: کد پیامکی ================= --}}
    <div class="rounded-2xl border border-sky-400/20 bg-sky-400/5 p-4 text-center" role="group" aria-label="ورود دومرحله‌ای">
        <span class="mx-auto grid place-items-center size-11 rounded-full border border-sky-400/30 bg-sky-400/10 text-sky-300" aria-hidden="true">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </span>
        <p class="mt-2.5 text-sm font-extrabold text-stone-100">ورود دومرحله‌ای</p>
        <p class="mt-1 text-[11px] leading-5 text-stone-400">کد ۶ رقمی به <bdi dir="ltr">{{ $this->maskedMobile() }}</bdi> پیامک شد.</p>
        <input type="text" wire:model="twoFaCode" dir="ltr" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
               placeholder="••••••" aria-label="کد تأیید ۶ رقمی"
               class="mt-3 block w-36 mx-auto text-center font-mono text-lg font-extrabold tracking-[.45em] text-stone-100 placeholder:text-stone-600 bg-white/5 border border-white/15 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400/40 @error('twoFaCode') border-rose-400/60 @enderror">
        @error('twoFaCode')
            <p class="mt-1.5 text-[11px] font-bold text-rose-400" role="alert">{{ $message }}</p>
        @enderror
        <button type="button" wire:click="resendTwoFactor" wire:loading.attr="disabled" wire:target="resendTwoFactor"
                class="mt-2.5 inline-flex items-center gap-1.5 text-[11px] font-bold text-sky-300 hover:text-sky-200 underline decoration-dotted underline-offset-4 transition-colors">
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><path d="M21 3v6h-6"/></svg>
            ارسال دوبارهٔ کد
        </button>
    </div>
@elseif ($this->captchaRequired())
    @php($cap = $this->captchaData())
    {{-- ================= ربات‌گیر: کپچای ریاضی ================= --}}
    <div>
        <label class="block text-xs font-bold text-stone-300" for="captcha">کد امنیتی <span class="text-rose-400" aria-hidden="true">*</span></label>
        <div class="mt-1.5 flex items-center gap-2">
            <div class="shrink-0 rounded-xl border border-white/10 bg-white/5 px-1.5 py-1 overflow-hidden" aria-hidden="true">
                @if (! empty($cap['svg']))
                    {!! $cap['svg'] !!}
                @else
                    <span class="inline-block px-2 py-1.5 font-mono text-sm font-bold text-stone-200">{{ $cap['question'] }} = ?</span>
                @endif
            </div>
            <input id="captcha" type="text" wire:model="captcha" dir="ltr" inputmode="numeric" maxlength="3"
                   autocomplete="off" placeholder="؟" aria-describedby="captcha-hint"
                   class="min-w-0 flex-1 bg-white/5 border border-white/15 rounded-xl px-3 py-2.5 text-sm text-stone-100 placeholder:text-stone-600 focus:outline-none focus:ring-2 focus:ring-sky-400/40 @error('captcha') border-rose-400/60 @enderror">
            <button type="button" wire:click="refreshCaptcha" wire:loading.attr="disabled"
                    class="shrink-0 grid place-items-center size-10 rounded-xl border border-white/10 bg-white/5 text-stone-400 hover:text-stone-100 hover:bg-white/10 transition-colors"
                    title="سؤال جدید" aria-label="سؤال جدید کد امنیتی">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><path d="M21 3v6h-6"/></svg>
            </button>
        </div>
        <p class="mt-1 text-[10px] text-stone-500" id="captcha-hint">نتیجهٔ عملیات را وارد کنید (مثلاً ۱۲)</p>
        @error('captcha')
            <p class="mt-1 text-[11px] font-bold text-rose-400" role="alert">{{ $message }}</p>
        @enderror
    </div>
@endif
