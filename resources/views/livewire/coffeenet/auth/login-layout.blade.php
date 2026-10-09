{{-- لایهٔ مستقل صفحهٔ ورود مدیر کافی‌نت — نسخهٔ Livewire [Task 4]
     ساختار عیناً مطابق livewire/admin/auth/login-layout.blade.php (الگوی مرجع Task 2-a)
     با منابع اختصاصی پنل کافی‌نت (net-org.css + PWA coffeenet).
     {{ $slot }} = محتوای کامپوننت Livewire --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- تم ذخیره‌شده قبل از رندر اعمال می‌شود (ضد-FOUC) --}}
    <script src="{{ asset('assets/js/theme-boot.js') }}?v=2"></script>

    <title>ورود مدیر کافی‌نت — {{ config('app.name') }}</title>
    {{-- PWA (فاز ۱۴) --}}
    @include('partials.pwa', ['panel' => 'coffeenet'])
    @include('partials.vpn-modal')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap">

    {{-- استایل و اسکریپت — کاملاً بدون Node / بدون بیلد --}}
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v=14">
    <link rel="stylesheet" href="{{ asset('assets/css/panel-ui.css') }}?v=18">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/net-org.css') }}?v=11">
    {{-- سیستم تم روشن/تاریک (فاز ۱۰) — باید آخرین CSS باشد تا برنده بماند --}}
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v=11">

    {{-- پوستهٔ پویا: پالت اختصاصی این پنل (بعد از theme.css) --}}
    @include('partials.appearance', ['panel' => 'coffeenet'])
    {{-- Livewire [Task 4] --}}
    @livewireStyles
</head>
<body class="font-sans antialiased">

{{-- سوییچ تم روشن/تاریک — گوشهٔ بالا-چپ (در RTL دور از محتوا) --}}
<button type="button" class="theme-toggle" data-theme-toggle aria-label="تغییر تم روشن/تاریک" title="حالت روشن/تاریک" style="position:fixed;top:1rem;left:1rem;z-index:50">
    <svg class="tt-icon tt-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
    <svg class="tt-icon tt-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
</button>

<main class="relative min-h-screen grid place-items-center overflow-hidden bg-brand-deep px-4">
    {{ $slot }}
</main>

{{-- [Task 2-a] data-navigate-once: اسکریپت‌های مشترک بین ناوبری‌های SPA دوباره اجرا نشوند --}}
<script src="{{ asset('back/assets/js/core.js') }}?v=15" data-navigate-once></script>
<script src="{{ asset('back/assets/js/ui.js') }}?v=13" data-navigate-once></script>

{{-- Livewire [Task 4] --}}
@livewireScripts
</body>
</html>
