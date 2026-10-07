{{-- ورود مدیر کافی‌نت — کامپوننت Livewire [Task 4]
     ساختار عیناً از الگوی livewire/admin/auth/login.blade.php با آیکن فنجان پنل کافی‌نت.
     تم آبی برند طبق THEME.md: bg-brand-deep، brand-card، brand-btn — هیچ amber تیره.
     فرم با wire:submit="store" (منطق AuthController@login عیناً در کامپوننت) —
     اسکریپت قدیمی pages/coffeenet/auth/login.js دیگر bind نمی‌شود.
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه --}}
<div class="relative">
<div class="pointer-events-none fixed inset-0" aria-hidden="true">
    <div class="brand-glow"></div>
    <div class="brand-stripes"></div>
</div>

<div class="relative w-full max-w-md py-10">

    <div class="text-center mb-8 animate-fade-up">
        <span class="inline-grid place-items-center size-16 rounded-3xl bg-brand-gradient shadow-2xl shadow-black/30 mb-4">
            <svg class="size-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
        </span>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">کافی‌نت آنلاین</h1>
        <p class="mt-1.5 text-sm text-brand-muted font-light">ورود به پنل مدیریت کافی‌نت</p>
    </div>

    {{-- کارت ورود --}}
    <form wire:submit="store" class="brand-card animate-fade-up rounded-3xl p-7 space-y-5 relative overflow-hidden" novalidate>

        {{-- خطای کلی (اعتبارسنجی غیرفیلدی / قفل تلاش / نداشتن دسترسی) --}}
        @error('form')
            <p class="brand-err" role="alert">{{ $message }}</p>
        @enderror

        <div>
            <label class="brand-lbl" for="email">ایمیل</label>
            <div class="relative">
                <input id="email" name="email" type="email" dir="ltr" autocomplete="username"
                       wire:model="email"
                       class="brand-field pl-10 @error('email') brand-field--err @enderror"
                       placeholder="manager@coffeenet.ir">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4.5 text-brand-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </div>
            @error('email')
                <p class="brand-err-field" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="brand-lbl" for="password">رمز عبور</label>
            <div class="relative" x-data="{ show: false }">
                <input id="password" name="password" dir="ltr" autocomplete="current-password"
                       wire:model="password"
                       :type="show ? 'text' : 'password'"
                       class="brand-field pl-10 pr-10 @error('password') brand-field--err @enderror"
                       placeholder="••••••••">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4.5 text-brand-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 21v-2a4 4 0 0 1 4-4h2"/><path d="M9.5 3.94a5 5 0 1 0-3.47 8.56 5 5 0 1 0 3.47-8.56z"/></svg>
                <button type="button" x-on:click="show = !show"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-brand-300 hover:text-brand-200 transition-colors"
                        aria-label="نمایش رمز">
                    <svg x-show="!show" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    <svg x-show="show" style="display:none" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password')
                <p class="brand-err-field" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="brand-btn" wire:loading.attr="disabled" wire:target="store">
            <span wire:loading.remove wire:target="store" class="inline-flex items-center gap-2">
                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                ورود به پنل کافی‌نت
            </span>
            <span wire:loading wire:target="store" class="inline-flex items-center gap-2">
                <svg class="size-4 brand-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                در حال ورود…
            </span>
        </button>
    </form>

    <p class="animate-fade-up text-center mt-6 text-[11px] text-stone-400">
        <a href="{{ url('/') }}" wire:navigate class="brand-link transition-colors">→ بازگشت به صفحه اصلی</a>
    </p>
</div>
</div>
