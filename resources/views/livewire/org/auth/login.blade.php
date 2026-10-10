{{-- ورود مدیر سازمان — نسخهٔ Livewire 4 [Task 6]
     ساختار و کلاس‌ها عیناً از back/org/auth/login.blade.php حفظ شده است.
     منطق ورود (rate limit / audit / انتخاب سازمان) در کامپوننت App\Livewire\Org\Auth\Login
     با wire:submit اجرا می‌شود (POST org.login.attempt همچنان fallback کنترلر است).
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div class="relative">

    <div class="text-center mb-8 animate-fade-up">
        <span class="inline-grid place-items-center size-16 rounded-3xl bg-gradient-to-br from-teal-400 to-teal-700 shadow-2xl shadow-black/40 mb-4">
            <img src="{{ asset('icons/icon-96.png') }}" alt="" width="32" height="32" style="width:32px;height:32px;object-fit:contain">
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-teal-50 tracking-tight">کافی‌نت آنلاین</h1>
        <p class="mt-1.5 text-sm text-teal-200/60 font-light">ورود به پنل سازمان</p>
    </div>

    {{-- کارت ورود --}}
    <form wire:submit="store" class="ui-auth-card animate-fade-up delay-1 rounded-3xl glass-warm p-7 sm:p-8 space-y-5" novalidate>

        {{-- خطای کلی (اعتبارسنجی غیرفیلدی / قفل تلاش / نداشتن دسترسی) --}}
        @error('form')
            <p class="text-xs text-rose-300 bg-rose-500/10 border border-rose-400/20 rounded-xl px-3.5 py-2.5 leading-6" role="alert">{{ $message }}</p>
        @enderror

        @unless ($twoFaStep)
        <div>
            <label class="lbl" for="email">ایمیل</label>
            <div class="relative">
                <input id="email" name="email" type="email" dir="ltr" autocomplete="username"
                       wire:model="email"
                       class="field pl-10" placeholder="org@example.com">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </div>
            @error('email')
                <p class="text-[11px] text-rose-300 mt-1.5" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="lbl" for="password">رمز عبور</label>
            <div class="relative" x-data="{ show: false }">
                <input id="password" name="password" dir="ltr" autocomplete="current-password"
                       wire:model="password"
                       :type="show ? 'text' : 'password'"
                       class="field pl-10 pr-10" placeholder="••••••••">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 21v-2a4 4 0 0 1 4-4h2"/><path d="M9.5 3.94a5 5 0 1 0-3.47 8.56 5 5 0 1 0 3.47-8.56z"/></svg>
                <button type="button" x-on:click="show = !show" class="no-pass-toggle absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition-colors" aria-label="نمایش رمز">
                    <svg x-show="!show" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    <svg x-show="show" style="display:none" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password')
                <p class="text-[11px] text-rose-300 mt-1.5" role="alert">{{ $message }}</p>
            @enderror
        </div>
        @endunless

        {{-- فاز ۵۰ — امنیت ورود: گام کد ۲FA یا ربات‌گیر (کپچا) --}}
        @include('livewire.auth.partials.login-security')

        <button type="submit" class="btn-primary btn-shine ui-press no-btn-teal w-full !py-3.5 !text-[15px]"
                wire:loading.attr="disabled" wire:target="store">
            <span wire:loading.remove wire:target="store" class="inline-flex items-center gap-2">
                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                {{ $twoFaStep ? 'تأیید و ورود' : 'ورود به پنل سازمان' }}
            </span>
            <span wire:loading wire:target="store" class="inline-flex items-center gap-2">
                <svg class="size-4 brand-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                در حال ورود…
            </span>
        </button>
    </form>

    <p class="animate-fade-up delay-2 text-center mt-6 text-[11px] text-stone-400">
        <a href="{{ url('/') }}" wire:navigate class="hover:text-amber-200 transition-colors">→ بازگشت به صفحه اصلی</a>
    </p>
</div>
