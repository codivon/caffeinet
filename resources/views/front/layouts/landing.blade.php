{{-- [Task 8] لایهٔ صفحه فرود — نسخهٔ Livewire 4
     جدا شده از front/landing.blade.php (محتوای اصلی در livewire/front/landing.blade.php).
     هدر/فوتر/دکمهٔ بازگشت ثابت‌اند و لینک‌هایشان wire:navigate دارند؛
     اسکریپت‌های صفحه در ویوی کامپوننت با @push لود می‌شوند. --}}
{{-- ============================================================
 |  کافی‌نت آنلاین — صفحه فرود تک‌صفحه‌ای معرفی برای مشتریان
 |  طراحی مستقل (بدون وابستگی به app.css کامپایل‌شده)
 ============================================================ --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title>{{ config('app.name', 'کافی‌نت آنلاین') }} — سفارش آنلاین خدمات کافی‌نت</title>
    <meta name="description" content="سفارش آنلاین خدمات کافی‌نت؛ فرم‌ساز پویا، پرداخت آنلاین، پیگیری لحظه‌ای سفارش و چت مستقیم با اپراتور. خدمات خودرویی، اداری و پلاک خودرو در چند کلیک.">

    {{-- آیکون و رنگ تم سایت — بدون مانیفست PWA و بدون Service Worker
     | (صفحه فرود عمداً «غیرقابل نصب» است؛ نصب اپ فقط از داخل پنل‌ها انجام می‌شود) --}}
    <meta name="theme-color" content="#f3f6fc">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('icons/icon-48.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('icons/icon-96.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">

    {{-- بوت تم روز/شب — قبل از استایل‌ها (ضد-FLUC)؛ پیش‌فرض: روز --}}
    <script src="{{ asset('assets/js/landing-theme-boot.js') }}?v=4"></script>

    {{-- فونت وزیرمتن --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap">

    {{-- استایل مستقل صفحه فرود --}}
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}?v=8">
    {{-- پوستهٔ پویا: پالت اختصاصی لندینگ --}}
    @include('partials.appearance', ['panel' => 'front'])

    {{-- [Task 8] Livewire SPA --}}
    @livewireStyles

    {{-- اسکیمای SEO (JSON-LD) --}}
    <script type="application/ld+json">{!! json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'WebSite',
        'name'       => config('app.name', 'کافی‌نت آنلاین'),
        'url'        => url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction', 'target' => url('/app/services'), 'query' => 'search',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
</head>
<body>

<div class="bg-texture" aria-hidden="true"></div>
<div class="bg-fx" aria-hidden="true">
    <span class="blob blob-1"></span>
    <span class="blob blob-2"></span>
</div>

{{-- [Task 8] $svgAttrs/$checkSvg به ویوی کامپوننت منتقل شد (فقط همان‌جا مصرف می‌شوند) --}}

{{-- ==================== هدر ==================== --}}
<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <a class="brand" href="{{ url('/') }}" aria-label="کافی‌نت آنلاین — صفحه اصلی">
            <span class="brand-mark">
                <img src="{{ asset('icons/icon-96.png') }}" alt="" width="22" height="22" style="width:22px;height:22px;object-fit:contain">
                <span class="dot"></span>
            </span>
            <span class="brand-text">
                <strong>{{ config('app.name', 'کافی‌نت آنلاین') }}</strong>
                <span>سفارش آنلاین خدمات</span>
            </span>
        </a>

        <nav class="main-nav" aria-label="ناوبری اصلی">
            <a href="#services">خدمات</a>
            <a href="#how">مراحل سفارش</a>
            <a href="#features">چرا ما؟</a>
            <a href="#pwa">نصب اپلیکیشن</a>
            <a href="#faq">سوالات متداول</a>
        </nav>

        <div class="header-actions">
            {{-- کلید روز/شب — پیش‌فرض: روز --}}
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="تغییر حالت روز و شب" title="حالت روز / شب">
                <svg class="ic-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                <svg class="ic-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
            </button>
            <a href="{{ route('app.auth') }}" wire:navigate class="btn btn-gold btn-sm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
                ورود / ثبت‌نام
            </a>
        </div>
    </div>

    {{-- منوی موبایل — v42: نوار چیپ‌های افقی اسکرول‌شونده زیر هدر (جایگزین همبرگری
         که در موبایل جای هدر را می‌گرفت). لینک‌های لنگری همین صفحه‌اند؛ دسکتاپ main-nav. --}}
    <nav class="mobile-chipnav" id="mobileNav" aria-label="ناوبری موبایل">
        <div class="chipnav-scroll container">
            <a href="#services">خدمات</a>
            <a href="#how">مراحل سفارش</a>
            <a href="#features">چرا ما؟</a>
            <a href="#pwa">نصب اپلیکیشن</a>
            <a href="#faq">سوالات متداول</a>
        </div>
    </nav>
</header>

{{-- [Task 8] محتوای صفحه — کامپوننت Livewire اینجا جایگذاری می‌شود (SPA با wire:navigate) --}}
{{ $slot }}

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="brand" href="{{ url('/') }}" aria-label="کافی‌نت آنلاین">
                    <span class="brand-mark">
                        <img src="{{ asset('icons/icon-96.png') }}" alt="" width="22" height="22" style="width:22px;height:22px;object-fit:contain">
                        <span class="dot"></span>
                    </span>
                    <span class="brand-text">
                        <strong>{{ config('app.name', 'کافی‌نت آنلاین') }}</strong>
                        <span>سفارش آنلاین خدمات</span>
                    </span>
                </a>
                <p class="footer-brand-desc">
                    پلتفرم اتصال مشتریان به کافی‌نت‌ها؛ سفارش آنلاین خدمات با فرم‌ساز پویا، پرداخت امن، تخصیص هوشمند و گفتگوی لحظه‌ای.
                </p>
            </div>

            <div>
                <h4 class="footer-title">دسترسی سریع</h4>
                <nav class="footer-links" aria-label="لینک‌های سریع">
                    <a href="#services"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>خدمات</a>
                    <a href="#how"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>مراحل سفارش</a>
                    <a href="#features"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>مزایا</a>
                    <a href="#faq"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>سوالات متداول</a>
                    <a href="{{ route('app.auth') }}" wire:navigate><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>ورود / ثبت‌نام</a>
                </nav>
            </div>

            <div>
                <h4 class="footer-title">دسته‌بندی خدمات</h4>
                <nav class="footer-links" aria-label="لینک دسته‌بندی خدمات">
                    {{-- v26.1 — فوتر فقط ۵ دستهٔ پرخدمت‌ترین؛ فهرست بلند فوتر را بدشکل کرده بود --}}
                    @forelse (($footerCategories ?? []) as $item)
                        <a href="{{ route('app.services') }}#cat-{{ $item['category']->id }}" wire:navigate>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            {{ $item['category']->name }}
                        </a>
                    @empty
                        <a href="{{ route('app.services') }}" wire:navigate><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>کاتالوگ خدمات</a>
                    @endforelse
                    @if (count($footerCategories ?? []) < count($landingCategories ?? []))
                        <a href="{{ route('app.services') }}" wire:navigate><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>همهٔ خدمات</a>
                    @endif
                </nav>
            </div>

            <div>
                <h4 class="footer-title">ارتباط با ما</h4>
                <div class="footer-contact">
                    <div>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span>
                            @if ($workStatus['enabled'])
                                ساعت کاری: {{ $workStatus['range'] }}<br>{{ $workStatus['open'] ? 'در حال حاضر باز هستیم' : 'در حال حاضر بسته هستیم' }}
                            @else
                                پاسخگویی: ۷ روز هفته، ۲۴ ساعته
                            @endif
                        </span>
                    </div>
                    <div>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        <span>پشتیبانی از طریق تیکت — بعد از ورود به حساب</span>
                    </div>
                    <div>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                        <span>پلتفرم تحت وب — روی همهٔ دستگاه‌ها</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-bottom-inner">
                <span>© {{ fa_digits(now()->year) }} {{ config('app.name', 'کافی‌نت آنلاین') }} — تمامی حقوق محفوظ است.</span>
                <nav class="footer-panels" aria-label="ورود پنل‌ها">
                    <a class="fp-chip" href="{{ route('app.auth') }}" wire:navigate>پنل مشتری</a>
                    <a class="fp-chip" href="{{ route('operator.login') }}">پنل اپراتور</a>
                    <a class="fp-chip" href="{{ route('admin.login') }}">پنل مدیریت</a>
                </nav>
            </div>
        </div>
    </div>
</footer>

{{-- دکمه بازگشت به بالا --}}
<button class="to-top" id="toTop" type="button" aria-label="بازگشت به بالای صفحه">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg>
</button>

<script src="{{ asset('assets/js/landing.js') }}?v=7" defer></script>
@stack('scripts')

{{-- [Task 8] Livewire SPA --}}
@livewireScripts
</body>
</html>
