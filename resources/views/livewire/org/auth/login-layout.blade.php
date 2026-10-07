{{-- لایهٔ مستقل صفحهٔ ورود مدیر سازمان — نسخهٔ Livewire 4 [Task 6]
     ساختار عیناً مطابق back/org/auth/login.blade.php قبلی (صفحهٔ مستقل، بدون سایدبار پنل)
     و الگوی livewire/admin/auth/login-layout (Task 2-a)؛ CSSها دست‌نخورده (net-org.css هویت سازمان).
     {{ $slot }} = محتوای کامپوننت Livewire --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- تم ذخیره‌شده قبل از رندر اعمال می‌شود (ضد-FOUC) --}}
    <script src="{{ asset('assets/js/theme-boot.js') }}?v=1"></script>

    <title>ورود سازمان — {{ config('app.name') }}</title>
    {{-- PWA (فاز ۱۴) --}}
    @include('partials.pwa', ['panel' => 'organization'])
    @include('partials.vpn-modal')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap">

    {{-- استایل و اسکریپت — کاملاً بدون Node / بدون بیلد (عیناً صفحهٔ قبلی) --}}
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v=14">
    <link rel="stylesheet" href="{{ asset('assets/css/panel-ui.css') }}?v=16">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/net-org.css') }}?v=11">
    {{-- سیستم تم روشن/تاریک (فاز ۱۰) — باید آخرین CSS باشد تا برنده بماند --}}
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v=11">

    {{-- Livewire [Task 6] --}}
    @livewireStyles
</head>
<body class="font-sans antialiased selection:bg-amber-200 selection:text-amber-950">

{{-- سوییچ تم روشن/تاریک — گوشهٔ بالا-چپ (در RTL دور از محتوا) --}}
<button type="button" class="theme-toggle" data-theme-toggle aria-label="تغییر تم روشن/تاریک" title="حالت روشن/تاریک" style="position:fixed;top:1rem;left:1rem;z-index:50">
    <svg class="tt-icon tt-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l1.41 1.41"/></svg>
    <svg class="tt-icon tt-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
</button>

<main class="no-theme-org relative min-h-screen grid place-items-center overflow-hidden no-auth-bg-org px-4">

    {{-- پس‌زمینهٔ هویتی — حباب‌های شناور + خطوط مورب (عیناً صفحهٔ قبلی) --}}
    <div class="pointer-events-none fixed inset-0" aria-hidden="true">
        <span class="ui-blob" data-tone="teal" data-pos="1"></span>
        <span class="ui-blob" data-tone="forest" data-pos="2"></span>
        <span class="ui-blob" data-tone="teal" data-pos="3"></span>
        <span class="no-auth-stripes"></span>
    </div>

    <div class="relative w-full max-w-md py-10">
        {{ $slot }}
    </div>
</main>

{{-- [Task 6] data-navigate-once: اسکریپت‌های مشترک بین ناوبری‌های SPA دوباره اجرا نشوند --}}
<script src="{{ asset('back/assets/js/core.js') }}?v=14" data-navigate-once></script>
<script src="{{ asset('back/assets/js/ui.js') }}?v=12" data-navigate-once></script>

{{-- Livewire [Task 6] --}}
@livewireScripts
</body>
</html>
