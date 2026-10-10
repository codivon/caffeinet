{{-- تنظیمات سیستم (چند-تبی) — کامپوننت Livewire [Task 3-b]
     shell صفحه: فرم‌ها و مودال‌ها عیناً حفظ شده‌اند و ذخیره/تست‌ها با
     همان AJAX قبلی به PUT/POST settings (کنترلر) می‌رود؛ اسکریپت صفحه
     back/assets/js/pages/admin/settings/index.js بدون تغییر bind می‌شود. --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pages/settings.css') }}?v=24">
@endpush

<div>
@php
    $provider = $settings->get('sms.provider', 'log');
    $kvnConfigured = (bool) $settings->get('sms.kavenegar.api_key');
    $fraaConfigured = (bool) $settings->get('sms.fraasms.api_key');
    $ippConfigured = trim((string) $settings->get('sms.ippanel.username')) !== '' && trim((string) $settings->get('sms.ippanel.password')) !== '';
    $melConfigured = trim((string) $settings->get('sms.melipayamak.username')) !== '' && trim((string) $settings->get('sms.melipayamak.password')) !== '';
    $idehConfigured = trim((string) $settings->get('sms.idehpardazan.api_key')) !== '' && trim((string) $settings->get('sms.idehpardazan.secret_key')) !== '';
    $providerLabel = match ($provider) {
        'kavenegar' => 'کاوه‌نگار',
        'fraasms' => 'فراز اس‌ام‌اس',
        'ippanel' => 'آی‌پی‌پنل',
        'melipayamak' => 'ملی‌پیامک',
        'idehpardazan' => 'ایده‌پردازان',
        default => 'لاگ (محیط توسعه)',
    };

    $payDriver = (string) $settings->get('payment.driver', 'local');
    $payDriverLabel = match ($payDriver) {
        'zarinpal' => 'زرین‌پال',
        'zibal' => 'زیبال',
        'behpardakht' => 'بانک ملت',
        'sep' => 'بانک ملی',
        'sepehr' => 'درگاه سپهر',
        default => 'درگاه تست (local)',
    };
    $zarinpalConfigured = (bool) $settings->get('payment.zarinpal.merchant_id');
    $zibalConfigured = (bool) $settings->get('payment.zibal.merchant_id');
    $zarinpalSandbox = (bool) $settings->get('payment.zarinpal.sandbox', true);
    $behpConfigured = trim((string) $settings->get('payment.behpardakht.terminal_id')) !== ''
        && trim((string) $settings->get('payment.behpardakht.username')) !== ''
        && trim((string) $settings->get('payment.behpardakht.password')) !== '';
    $sepConfigured = trim((string) $settings->get('payment.sep.terminal_id')) !== '';
    $sepehrConfigured = trim((string) $settings->get('payment.sepehr.terminal_id')) !== '';

    // v29 → v35 — آستانهٔ آفلاین (سوییچ + ثانیه؛ کف ۹۰ ثانیه)
    $offlineEnabled = (bool) $settings->get('notification.push.offline_enabled', true);
    $offlineSeconds = (int) $settings->get('notification.push.offline_seconds', 0);
    if ($offlineSeconds <= 0) {
        // مهاجرت از کلید قدیمی (دقیقه) یا پیش‌فرض ۱۸۰ ثانیه
        $offlineSeconds = max(1, (int) $settings->get('notification.push.offline_minutes', 3)) * 60;
    }
    $offlineSeconds = max(45, $offlineSeconds); // v36: کف — با فاصلهٔ ۲۰ ثانیه‌ای نوشتن last_seen هم‌خوان

    // v29 — منطقهٔ زمانی سامانه
    $currentTz = (string) $settings->get('general.timezone', 'UTC');
    if (! in_array($currentTz, timezone_identifiers_list(), true)) {
        $currentTz = 'UTC';
    }

    // v38 — وضعیت آنلاین/آفلاین کاربران (تصمیم مدیر برای نوتیف سیستمی)
    $userStatus = (string) $settings->get('notification.push.user_status', 'offline');
    if (! in_array($userStatus, ['offline', 'online', 'auto'], true)) {
        $userStatus = 'offline';
    }
@endphp

<div class="st-layout">

    {{-- ================== ناوبری سکشن‌ها ================== --}}
    <aside class="st-nav card ui-lift animate-fade-up" aria-label="ناوبری بخش‌های تنظیمات">
        <div class="st-nav-head">
            <span class="st-nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            </span>
            <div class="min-w-0">
                <b class="block text-sm font-extrabold text-stone-800">تنظیمات سیستم</b>
                <span class="block text-[11px] text-stone-400">۱۰ بخش پیکربندی + ابزارها</span>
            </div>
        </div>

        <nav class="st-nav-list" role="tablist">
            <button type="button" role="tab" class="st-nav-item is-active" data-section="general">
                <img src="{{ asset('icons/icon-96.png') }}" alt="" width="18" height="18" style="width:18px;height:18px;object-fit:contain" aria-hidden="true">
                <span class="flex-1 text-start">عمومی</span>
                <span class="st-nav-hint">۲</span>
            </button>

            {{-- فاز ۶۰ — محتوای صفحهٔ عمومی «درباره/اعتماد» (/about) --}}
            <button type="button" role="tab" class="st-nav-item" data-section="about">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <span class="flex-1 text-start">صفحهٔ درباره ما</span>
                <span class="st-nav-hint">/about</span>
            </button>

            <button type="button" role="tab" class="st-nav-item" data-section="sms">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/></svg>
                <span class="flex-1 text-start">پیامک و پرووایدر</span>
                <span class="st-nav-hint">{{ $providerLabel }}</span>
            </button>

            {{-- ظاهر و رنگ‌بندی — پالت اختصاصی هر پنل (پیش‌فرض + ۱۰ پالت + شخصی‌سازی) --}}
            <button type="button" role="tab" class="st-nav-item" data-section="appearance">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                <span class="flex-1 text-start">ظاهر و رنگ‌بندی</span>
                <span class="st-nav-hint">{{ $appearance['selected_name'] }}</span>
            </button>

            <button type="button" role="tab" class="st-nav-item" data-section="orders">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span class="flex-1 text-start">موتور سفارش‌ها</span>
                <span class="st-nav-hint">پخش</span>
            </button>

            <button type="button" role="tab" class="st-nav-item" data-section="ratings">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>
                <span class="flex-1 text-start">نظرسنجی و امتیاز</span>
                <span class="st-nav-hint">{{ $settings->get('ratings.routing_enabled') ? 'پخش هوشمند' : 'پیش‌فرض' }}</span>
            </button>

            <button type="button" role="tab" class="st-nav-item" data-section="workhours">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                <span class="flex-1 text-start">ساعت کاری</span>
                <span class="st-nav-hint" data-wh-hint>{{ $settings->get('workhours.enabled') ? 'فعال' : 'خاموش' }}</span>
            </button>

            <button type="button" role="tab" class="st-nav-item" data-section="payment">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                <span class="flex-1 text-start">درگاه پرداخت</span>
                <span class="st-nav-hint">{{ $payDriverLabel }}</span>
            </button>

            <button type="button" role="tab" class="st-nav-item" data-section="staff">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span class="flex-1 text-start">کارکنان کافی‌نت‌ها</span>
                <span class="st-nav-hint">{{ $settings->get('staff.hiring.mode', 'auto') === 'approval' ? 'تایید مدیر کل' : 'خودکار' }}</span>
            </button>

            {{-- v40 — فینوتک: استعلام کد ملی/موبایل/کارت --}}
            <button type="button" role="tab" class="st-nav-item" data-section="finnotech">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                <span class="flex-1 text-start">فینوتک (استعلام هویت)</span>
                @if ($settings->get('finnotech.enabled'))
                    <span class="st-nav-dot st-nav-dot--on" title="فعال"></span>
                @else
                    <span class="st-nav-dot" title="غیرفعال"></span>
                @endif
            </button>

            <button type="button" role="tab" class="st-nav-item" data-section="realtime">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                <span class="flex-1 text-start">Realtime</span>
                <span class="st-nav-hint">{{ ['polling' => 'پولینگ', 'sse' => 'SSE', 'pusher' => ($pusherReady ? 'پوشر' : 'ناقص')][$rtMethod] }}</span>
            </button>

            <button type="button" role="tab" class="st-nav-item" data-section="notifications">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                <span class="flex-1 text-start">اعلان‌ها و پوش</span>
                <span class="st-nav-hint">{{ $notificationStats['push_provider'] !== 'off' ? $notificationStats['push_provider_label'] : ($settings->get('notification.sound.enabled') ? 'صدا' : 'خاموش') }}</span>
            </button>

            <button type="button" role="tab" class="st-nav-item" data-section="referral">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span class="flex-1 text-start">پاداش معرفی</span>
                @if ($referral->is_active)
                    <span class="st-nav-dot st-nav-dot--on" title="فعال"></span>
                @else
                    <span class="st-nav-dot" title="غیرفعال"></span>
                @endif
            </button>

            {{-- v42 — آپلود و فشرده‌سازی --}}
            <button type="button" role="tab" class="st-nav-item" data-section="uploads">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M20 16v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3"/></svg>
                <span class="flex-1 text-start">آپلود و فشرده‌سازی</span>
                @if ($uploadCfg['enabled'])
                    <span class="st-nav-dot st-nav-dot--on" title="فعال"></span>
                @else
                    <span class="st-nav-dot" title="غیرفعال"></span>
                @endif
            </button>

            {{-- فاز ۵۰ — قابلیت‌ها (سوییچ فعال/غیرفعال زیرسیستم‌ها) --}}
            <button type="button" role="tab" class="st-nav-item" data-section="features">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/><circle cx="12" cy="12" r="3"/></svg>
                <span class="flex-1 text-start">قابلیت‌ها و امنیت</span>
                <span class="st-nav-dot {{ (bool) $settings->get('features.two_factor') || $settings->get('features.captcha_mode') !== 'off' ? 'st-nav-dot--on' : '' }}" title="وضعیت امنیت ورود"></span>
            </button>

            <a href="{{ route('admin.sms-templates.index') }}" class="st-nav-item st-nav-item--link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v18l-6-3-6 3Z"/></svg>
                <span class="flex-1 text-start">مرکز پیامک (پترن‌ها)</span>
                <svg class="size-3.5 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>

            {{-- v38 — نمایشگر لاگ سیستمی لاراول --}}
            <a href="{{ route('admin.settings.logs') }}" class="st-nav-item st-nav-item--link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 17l6-6-6-6"/><path d="M12 19h8"/></svg>
                <span class="flex-1 text-start">نمایش لاگ سیستمی</span>
                <svg class="size-3.5 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>

            {{-- فاز ۴۸ — کلیدهای وب‌سرویس (API Keys) — آخرین منوی تنظیمات --}}
            <a href="{{ route('admin.api-keys.index') }}" class="st-nav-item st-nav-item--link" wire:navigate>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                <span class="flex-1 text-start">کلیدهای وب‌سرویس</span>
                <span class="st-nav-hint">API</span>
                <svg class="size-3.5 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>

            {{-- فاز ۵۰ — نشست‌های فعال (دستگاه‌های واردشده) --}}
            <a href="{{ route('admin.sessions.index') }}" class="st-nav-item st-nav-item--link" wire:navigate>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="8" x="5" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 18h.01"/><path d="M10 18h.01"/></svg>
                <span class="flex-1 text-start">نشست‌های فعال</span>
                <span class="st-nav-hint">دستگاه‌ها</span>
                <svg class="size-3.5 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>

            {{-- فاز ۵۴ — سلامت سیستم --}}
            <a href="{{ route('admin.health') }}" class="st-nav-item st-nav-item--link" wire:navigate>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58Z"/><circle cx="12" cy="12" r="3"/></svg>
                <span class="flex-1 text-start">سلامت سیستم</span>
                <span class="st-nav-hint">وضعیت</span>
                <svg class="size-3.5 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </nav>
    </aside>

    {{-- ================== سکشن‌ها ================== --}}
    <div class="st-sections">

        {{-- ---------- عمومی ---------- --}}
        <form data-group="general" class="st-section card ui-lift animate-fade-up" id="sec-general">
            <div class="st-section-head">
                <span class="st-section-icon" aria-hidden="true">
                    <img src="{{ asset('icons/icon-96.png') }}" alt="" width="18" height="18" style="width:18px;height:18px;object-fit:contain" aria-hidden="true">
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">تنظیمات عمومی</h2>
                    <p class="st-section-desc">نام نمایشی سیستم و منطقهٔ زمانی — در پنل‌ها، اپ مشتری و پیامک‌ها استفاده می‌شود.</p>
                </div>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="g-app-name">نام سیستم</label>
                <input id="g-app-name" data-key="general.app_name" class="field" value="{{ old('general.app_name', $settings->get('general.app_name', 'کافی‌نت آنلاین')) }}">
                <p class="st-hint">در متن پیامک‌ها با متغیر <span class="font-mono text-amber-600" dir="ltr">{app_name}</span> درج می‌شود</p>
            </div>

            {{-- v36 → v37 — نشانگر سلامت کرون جاب (قرمز/سبز) + اجرای دستی --}}
            <div class="st-cron-status {{ $cronStatus['healthy'] ? 'st-cron-status--ok' : 'st-cron-status--bad' }}" role="status" id="st-cron-card">
                <span class="st-cron-dot" aria-hidden="true"></span>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($cronStatus['healthy'])
                            <p class="st-cron-title">کرون جاب فعال است ✓</p>
                        @else
                            <p class="st-cron-title">کرون جاب فعال نیست!</p>
                        @endif
                        {{-- v37 — اجرای دستی: همان schedule:run یک‌بار از داخل پنل --}}
                        <button type="button" id="st-cron-run" class="btn-ghost ui-press !py-1.5 !px-3 !text-[11px]" title="اجرای یک‌بارهٔ زمان‌بندی‌ها — مثل کرون هاست">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m13 2-2 8h8l-9 12 2-9H4z"/></svg>
                            اجرای دستی (تست)
                        </button>
                    </div>
                    @if ($cronStatus['healthy'])
                        <p class="st-cron-sub">آخرین اجرا: {{ fa_date($cronStatus['last'], 'Y/m/d H:i:s') }} — {{ fa_number($cronStatus['age']) }} ثانیه پیش</p>
                    @else
                        <p class="st-cron-sub">
                            @if ($cronStatus['last'])
                                آخرین اجرا: {{ fa_date($cronStatus['last'], 'Y/m/d H:i:s') }} — بیش از {{ fa_number(max(1, (int) floor($cronStatus['age'] / 60))) }} دقیقه پیش.
                            @else
                                هنوز هیچ اجرایی ثبت نشده است.
                            @endif
                            موتور پخش سفارش، تور ایمنی پوش و پاکسازی خودکار کار نمی‌کنند.
                        </p>
                        <p class="st-cron-cmd" dir="ltr">* * * * * cd /path/to/caffeinet && php artisan schedule:run >> /dev/null 2>&1</p>
                        <p class="st-cron-sub">
                            دکمهٔ «اجرای دستی» را بزنید: اگر نشانگر سبز شد ولی بعد از ~۳ دقیقه دوباره قرمز شد، برنامه سالم است و
                            <b>کرونِ هاست به سایت نمی‌رسد</b> (خروجی دستور را بدون <span dir="ltr">>> /dev/null 2>&1</span> در ترمینال اجرا کنید تا خطا دیده شود).
                        </p>
                        @if (! empty($cronStatus['trace']))
                            <p class="st-cron-sub mt-1">
                                آخرین ردپاهای ثبت‌شده:
                                <span dir="ltr" class="font-mono text-[10px] block leading-4 mt-1 opacity-80">{{ implode(' · ', $cronStatus['trace']) }}</span>
                            </p>
                        @endif
                    @endif
                    {{-- نتیجهٔ اجرای دستی (JS اینجا رندر می‌کند) --}}
                    <div id="st-cron-run-result" class="hidden mt-2"></div>
                </div>
            </div>

            {{-- v29 — منطقهٔ زمانی سامانه --}}
            <div class="st-field-row">
                <label class="lbl" for="g-timezone">منطقهٔ زمانی سامانه</label>
                <select id="g-timezone" data-key="general.timezone" class="field cursor-pointer">
                    <optgroup label="پیشنهادی">
                        @foreach (['UTC' => 'UTC — گرینویچ (رفتار پیش‌فرض)', 'Asia/Tehran' => 'ایران — تهران'] as $tz => $label)
                            <option value="{{ $tz }}" {{ $currentTz === $tz ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="همسایگان و غرب آسیا">
                        @foreach (['Asia/Dubai' => 'امارات — دبی', 'Asia/Muscat' => 'عمان — مسقط', 'Asia/Baghdad' => 'عراق — بغداد', 'Asia/Kuwait' => 'کویت', 'Asia/Riyadh' => 'عربستان — ریاض', 'Asia/Qatar' => 'قطر — دوحه', 'Asia/Kabul' => 'افغانستان — کابل', 'Asia/Baku' => 'آذربایجان — باکو', 'Asia/Yerevan' => 'ارمنستان — ایروان', 'Asia/Tashkent' => 'ازبکستان — تاشکند', 'Asia/Istanbul' => 'ترکیه — استانبول'] as $tz => $label)
                            <option value="{{ $tz }}" {{ $currentTz === $tz ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="اروپا و آمریکا">
                        @foreach (['Europe/London' => 'بریتانیا — لندن', 'Europe/Berlin' => 'آلمان — برلین', 'Europe/Paris' => 'فرانسه — پاریس', 'Europe/Moscow' => 'روسیه — مسکو', 'America/New_York' => 'آمریکا — نیویورک', 'America/Chicago' => 'آمریکا — شیکاگو', 'America/Los_Angeles' => 'آمریکا — لوس‌آنجلس', 'America/Toronto' => 'کانادا — تورنتو'] as $tz => $label)
                            <option value="{{ $tz }}" {{ $currentTz === $tz ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="آسیای شرقی و اقیانوسیه">
                        @foreach (['Asia/Karachi' => 'پاکستان — کراچی', 'Asia/Kolkata' => 'هند — دهلی', 'Asia/Shanghai' => 'چین — شانگهای', 'Asia/Tokyo' => 'ژاپن — توکیو', 'Asia/Seoul' => 'کره جنوبی — سئول', 'Asia/Singapore' => 'سنگاپور', 'Australia/Sydney' => 'استرالیا — سیدنی'] as $tz => $label)
                            <option value="{{ $tz }}" {{ $currentTz === $tz ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <p class="st-hint">همهٔ تاریخ‌ها (پنل‌ها، اپ، لاگ‌ها، زمان‌بندی‌های خودکار) با این منطقهٔ زمانی ثبت و نمایش داده می‌شوند. برای ایران <span class="font-mono text-amber-600" dir="ltr">Asia/Tehran</span> را انتخاب کنید. بعد از تغییر، ساعت‌های ثبت‌شدهٔ قبلی نیز با منطقهٔ جدید نمایش داده می‌شوند.</p>
            </div>

            {{-- v39 — بازهٔ سنین مجاز تاریخ تولد (لیست کشویی سال تولد اپ مشتری) --}}
            <div class="st-field-row">
                <label class="lbl" for="g-birth-min-age">محدودهٔ سن مشتریان (تاریخ تولد)</label>
                <div class="flex items-center gap-2">
                    <input id="g-birth-min-age" data-key="general.birth_min_age" type="number" min="1" max="119" class="field num" style="max-width:110px" value="{{ (int) $settings->get('general.birth_min_age', 10) }}">
                    <span class="text-xs text-stone-400">تا</span>
                    <input id="g-birth-max-age" data-key="general.birth_max_age" type="number" min="2" max="120" class="field num" style="max-width:110px" value="{{ (int) $settings->get('general.birth_max_age', 100) }}">
                    <span class="text-xs text-stone-400">سال</span>
                </div>
                <p class="st-hint">حداقل و حداکثر سن مجاز برای تاریخ تولد مشتریان — لیست کشویی «سال تولد» در پروفایل اپ مشتری و اعتبارسنجی سرور بر همین اساس است (پیش‌فرض ۱۰ تا ۱۰۰ سال).</p>
            </div>

            {{-- v38 — میان‌بر نمایش لاگ سیستمی لاراول --}}
            <div class="st-logs-shortcut" id="st-logs-shortcut">
                <span class="st-logs-shortcut-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 11h2"/></svg>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="st-cron-title">لاگ سیستمی لاراول</p>
                    <p class="st-cron-sub">
                        مشاهدهٔ خطاها، اخطارها و رخدادهای ثبت‌شدهٔ سامانه به تفکیک سطح — با امکان جستجو،
                        جزئیات کامل (stack trace)، <b>خالی‌کردن</b> یا <b>حذف کامل فایل</b>.
                    </p>
                </div>
                <a href="{{ route('admin.settings.logs') }}" class="btn-primary btn-shine ui-press !py-2 !px-4 !text-xs whitespace-nowrap" style="text-decoration:none;">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 17l6-6-6-6"/><path d="M12 19h8"/></svg>
                    نمایش لاگ سیستمی
                </a>
            </div>

            <div class="st-section-foot">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات عمومی</button>
            </div>
        </form>

        {{-- ---------- فاز ۶۰ — صفحهٔ درباره/اعتماد (/about) ---------- --}}
        @php
            // FAQ ذخیره‌شده آرایهٔ {q,a} است؛ برای ویرایش ساده به خطوط «سوال | جواب» برمی‌گردیم
            $aboutIntro = (string) $settings->get('about.intro', '');
            $aboutFaqLines = collect($settings->get('about.faq') ?? [])
                ->map(fn ($row) => trim((string) ($row['q'] ?? '')).' | '.trim((string) ($row['a'] ?? '')))
                ->implode("\n");
        @endphp
        <form data-group="about" class="st-section card ui-lift animate-fade-up hidden" id="sec-about">
            <div class="st-section-head">
                <span class="st-section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">صفحهٔ «درباره ما / اعتماد»</h2>
                    <p class="st-section-desc">محتوای صفحهٔ عمومی <span class="font-mono" dir="ltr">/about</span> — لینک آن در فوتر لندینگ است. اگر فیلدی را خالی ذخیره کنید، صفحه به متن پیش‌فرض سیستم برمی‌گردد.</p>
                </div>
                <a href="{{ route('front.about') }}" target="_blank" rel="noopener" class="btn-primary btn-shine ui-press !py-2 !px-4 !text-xs whitespace-nowrap" style="text-decoration:none;">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
                    مشاهدهٔ صفحه
                </a>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="ab-intro">متن معرفی (زیر تیتر هیرو)</label>
                <textarea id="ab-intro" data-key="about.intro" class="field min-h-24" rows="4" maxlength="2000"
                          placeholder="معرفی کوتاه و اعتمادساز پلتفرم — ۲ تا ۴ جمله.">{{ $aboutIntro }}</textarea>
                <p class="st-hint">حداکثر ۲۰۰۰ کاراکتر؛ خالی = متن پیش‌فرض سیستم.</p>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="ab-faq">سوالات متداول</label>
                <textarea id="ab-faq" data-key="about.faq" class="field min-h-48" rows="10" dir="auto"
                          placeholder="پرداخت امن است؟ | بله؛ پرداخت از درگاه بانکی رسمی انجام می‌شود…">{{ $aboutFaqLines }}</textarea>
                <p class="st-hint">هر خط یک سوال با فرمت «سوال | جواب» (کاراکتر ستون جداکننده است). خطوط بدون «|» یا بدون جواب نادیده گرفته می‌شوند؛ خالی کردن کل کادر = بازگشت به ۸ سوال پیش‌فرض.</p>
            </div>

            <div class="st-section-foot">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ محتوای صفحهٔ درباره</button>
            </div>
        </form>

        {{-- ---------- پیامک ---------- --}}
        <form data-group="sms" class="st-section card ui-lift animate-fade-up hidden" id="sec-sms">
            <div class="st-section-head">
                <span class="st-section-icon st-section-icon--sms" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">پیامک و پرووایدر</h2>
                    <p class="st-section-desc">پرووایدر ارسال را انتخاب کنید — تنظیمات همان پرووایدر بلافاصله زیر کارت‌ها باز می‌شود. کد پترن هر رویداد در «مرکز پیامک» ثبت می‌شود.</p>
                </div>
                <span class="badge {{ $provider === 'log' ? 'bg-stone-100 text-stone-500 border border-stone-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}" id="sms-provider-badge">{{ $providerLabel }}</span>
            </div>

            {{-- کارت‌های انتخاب پرووایدر — فعال: تنظیمات همان کارت زیرش باز می‌شود --}}
            <div class="st-pick-head">
                <b>پرووایدر فعال</b>
                <span>با انتخاب کارت، تنظیمات همان سرویس در ادامه نمایان می‌شود</span>
            </div>

            <div class="st-prov-grid" role="radiogroup" aria-label="انتخاب پرووایدر پیامک">
                <label class="st-prov-card" data-prov="kavenegar">
                    <input type="radio" name="sms-provider" value="kavenegar" class="sr-only" {{ $provider === 'kavenegar' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--kavenegar" aria-hidden="true">ک</span>
                    <b class="st-prov-name">کاوه‌نگار</b>
                    <span class="st-prov-sub">Verify Lookup · پترن + متن</span>
                </label>

                <label class="st-prov-card" data-prov="fraasms">
                    <input type="radio" name="sms-provider" value="fraasms" class="sr-only" {{ $provider === 'fraasms' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--fraasms" aria-hidden="true">ف</span>
                    <b class="st-prov-name">فراز اس‌ام‌اس</b>
                    <span class="st-prov-sub">ایران‌پیامک · پترن‌محور</span>
                </label>

                <label class="st-prov-card" data-prov="ippanel">
                    <input type="radio" name="sms-provider" value="ippanel" class="sr-only" {{ $provider === 'ippanel' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--ippanel" aria-hidden="true">آ</span>
                    <b class="st-prov-name">آی‌پی‌پنل</b>
                    <span class="st-prov-sub">ippanel · پترن‌محور</span>
                </label>

                <label class="st-prov-card" data-prov="melipayamak">
                    <input type="radio" name="sms-provider" value="melipayamak" class="sr-only" {{ $provider === 'melipayamak' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--melipayamak" aria-hidden="true">م</span>
                    <b class="st-prov-name">ملی‌پیامک</b>
                    <span class="st-prov-sub">متن ثابت (bodyId)</span>
                </label>

                <label class="st-prov-card" data-prov="idehpardazan">
                    <input type="radio" name="sms-provider" value="idehpardazan" class="sr-only" {{ $provider === 'idehpardazan' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--idehpardazan" aria-hidden="true">ا</span>
                    <b class="st-prov-name">ایده‌پردازان</b>
                    <span class="st-prov-sub">قالب سریع RestfulSms</span>
                </label>

                <label class="st-prov-card" data-prov="log">
                    <input type="radio" name="sms-provider" value="log" class="sr-only" {{ $provider === 'log' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--log" aria-hidden="true">ل</span>
                    <b class="st-prov-name">لاگ (توسعه)</b>
                    <span class="st-prov-sub">فقط ثبت در گزارش</span>
                </label>
            </div>

            <input type="hidden" id="s-provider" data-key="sms.provider" value="{{ $provider }}">

            {{-- ===== کارت تنظیمات کاوه‌نگار ===== --}}
            <div class="st-gw" data-gw="kavenegar">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--kavenegar" aria-hidden="true">ک</span>
                    <div class="st-gw-title flex-1">
                        <b>کاوه‌نگار <span class="font-normal text-stone-400" dir="ltr">(Kavenegar)</span></b>
                        <span>REST رسمی · ارسال پترنی Verify Lookup + متن آزاد (در خطای پترن، افت به متن)</span>
                    </div>
                    @if ($kvnConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">کلید ثبت‌شده</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-field-row">
                    <label class="lbl" for="s-kvn-key">کلید API</label>
                    <input id="s-kvn-key" data-key="sms.kavenegar.api_key" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                           placeholder="{{ $kvnConfigured ? '•••••••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'کلید API را از پنل کاوه‌نگار کپی کنید' }}">
                </div>

                <div class="st-grid-2">
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="s-kvn-sender">شماره خط فرستنده</label>
                        <input id="s-kvn-sender" data-key="sms.kavenegar.sender" dir="ltr" class="field" placeholder="10004345"
                               value="{{ $settings->get('sms.kavenegar.sender') }}">
                    </div>
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="s-kvn-endpoint">آدرس API</label>
                        <input id="s-kvn-endpoint" data-key="sms.kavenegar.endpoint" dir="ltr" class="field font-mono !text-xs"
                               value="{{ $settings->get('sms.kavenegar.endpoint', 'https://api.kavenegar.com') }}">
                    </div>
                </div>
                <p class="st-hint">
                    کد پترن قالب‌ها در «<a href="{{ route('admin.sms-templates.index') }}" class="text-amber-700 font-semibold hover:underline">مرکز پیامک</a>» = <b>نام قالب Verify Lookup</b> در پنل کاوه‌نگار؛ متغیرهای قالب به‌ترتیب روی token10/20/30 نگاشت می‌شوند.
                    مستندات: <a href="https://kavenegar.com/rest.html" target="_blank" rel="noopener" class="text-amber-700 font-semibold hover:underline" dir="ltr">kavenegar.com/rest</a>
                </p>
            </div>

            {{-- ===== کارت تنظیمات فراز اس‌ام‌اس ===== --}}
            <div class="st-gw" data-gw="fraasms">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--fraasms" aria-hidden="true">ف</span>
                    <div class="st-gw-title flex-1">
                        <b>فراز اس‌ام‌اس <span class="font-normal text-stone-400" dir="ltr">(ایران‌پیامک)</span></b>
                        <span>پترن‌محور خالص — متن آزاد پذیرفته نمی‌شود؛ قالب بدون پترن ارسال نمی‌شود</span>
                    </div>
                    @if ($fraaConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">کلید ثبت‌شده</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-field-row">
                    <label class="lbl" for="s-api-key">کلید API <span class="text-stone-400 text-[10px]" dir="ltr">(Api-Key)</span></label>
                    <input id="s-api-key" data-key="sms.fraasms.api_key" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                           placeholder="{{ $fraaConfigured ? '•••••••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'کلید API پنل فراز را وارد کنید' }}">
                    <p class="st-hint">از پنل فراز اس‌ام‌اس: بخش «API» → تولید کلید (به‌صورت هدر <span class="font-mono text-amber-600" dir="ltr">Api-Key</span> ارسال می‌شود)</p>
                </div>

                <div class="st-grid-2">
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="s-sender">شماره خط <span class="text-stone-400 text-[10px]" dir="ltr">(line_number)</span></label>
                        <input id="s-sender" data-key="sms.fraasms.sender" dir="ltr" class="field" placeholder="+983000505"
                               value="{{ $settings->get('sms.fraasms.sender') }}">
                    </div>
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="s-endpoint">آدرس API پترن</label>
                        <input id="s-endpoint" data-key="sms.fraasms.endpoint" dir="ltr" class="field font-mono !text-xs"
                               value="{{ $settings->get('sms.fraasms.endpoint', 'https://api.iranpayamak.com/ws/v1/sms/pattern') }}"
                               placeholder="https://api.iranpayamak.com/ws/v1/sms/pattern">
                    </div>
                </div>
                <p class="st-hint">
                    کد پترن هر رویداد را در «<a href="{{ route('admin.sms-templates.index') }}" class="text-amber-700 font-semibold hover:underline">مرکز پیامک</a>» ثبت کنید؛ نام متغیرها باید با متغیرهای پترن پنل فراز یکی باشد (مثل <span class="font-mono text-amber-600" dir="ltr">{code}</span>).
                </p>
            </div>

            {{-- ===== کارت تنظیمات آی‌پی‌پنل ===== --}}
            <div class="st-gw" data-gw="ippanel">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--ippanel" aria-hidden="true">آ</span>
                    <div class="st-gw-title flex-1">
                        <b>آی‌پی‌پنل <span class="font-normal text-stone-400" dir="ltr">(ippanel.com)</span></b>
                        <span>وب‌سرویس پترن پنل · ارسال پترن‌محور (متن آزاد قابل اتکا نیست)</span>
                    </div>
                    @if ($ippConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">پیکربندی کامل</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-grid-2">
                    <div class="st-field-row">
                        <label class="lbl" for="s-ipp-user">نام کاربری پنل</label>
                        <input id="s-ipp-user" data-key="sms.ippanel.username" dir="ltr" class="field" autocomplete="off"
                               placeholder="نام کاربری پنل آی‌پی‌پنل" value="{{ $settings->get('sms.ippanel.username') }}">
                    </div>
                    <div class="st-field-row">
                        <label class="lbl" for="s-ipp-pass">رمز عبور پنل</label>
                        <input id="s-ipp-pass" data-key="sms.ippanel.password" data-empty-skip dir="ltr" class="field" type="password" autocomplete="off"
                               placeholder="{{ $ippConfigured ? '•••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'رمز عبور پنل آی‌پی‌پنل' }}">
                    </div>
                </div>

                <div class="st-grid-2">
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="s-ipp-from">شماره خط فرستنده</label>
                        <input id="s-ipp-from" data-key="sms.ippanel.from" dir="ltr" class="field" placeholder="+983000505"
                               value="{{ $settings->get('sms.ippanel.from') }}">
                    </div>
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="s-ipp-endpoint">آدرس ارسال پترن</label>
                        <input id="s-ipp-endpoint" data-key="sms.ippanel.endpoint" dir="ltr" class="field font-mono !text-xs"
                               value="{{ $settings->get('sms.ippanel.endpoint', 'https://ippanel.com/patterns/pattern') }}"
                               placeholder="https://ippanel.com/patterns/pattern">
                    </div>
                </div>
                <p class="st-hint">
                    کد پترن قالب‌ها در «مرکز پیامک» = <b>pattern_code</b> پنل آی‌پی‌پنل؛ نام متغیرهای نام‌دار باید با متغیرهای تعریف‌شدهٔ پترن یکی باشد.
                </p>
            </div>

            {{-- ===== کارت تنظیمات ملی‌پیامک ===== --}}
            <div class="st-gw" data-gw="melipayamak">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--melipayamak" aria-hidden="true">م</span>
                    <div class="st-gw-title flex-1">
                        <b>ملی‌پیامک <span class="font-normal text-stone-400" dir="ltr">(Melipayamak)</span></b>
                        <span>سرویس پایه مشترک — ارسال با «شناسه متن ثابت» (bodyId)</span>
                    </div>
                    @if ($melConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">پیکربندی کامل</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-grid-2">
                    <div class="st-field-row">
                        <label class="lbl" for="s-mp-user">نام کاربری پنل</label>
                        <input id="s-mp-user" data-key="sms.melipayamak.username" dir="ltr" class="field" autocomplete="off"
                               placeholder="نام کاربری پنل ملی‌پیامک" value="{{ $settings->get('sms.melipayamak.username') }}">
                    </div>
                    <div class="st-field-row">
                        <label class="lbl" for="s-mp-pass">رمز عبور پنل</label>
                        <input id="s-mp-pass" data-key="sms.melipayamak.password" data-empty-skip dir="ltr" class="field" type="password" autocomplete="off"
                               placeholder="{{ $melConfigured ? '•••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'رمز عبور پنل ملی‌پیامک' }}">
                    </div>
                </div>

                <div class="st-grid-2">
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="s-mp-from">شماره خط فرستنده</label>
                        <input id="s-mp-from" data-key="sms.melipayamak.from" dir="ltr" class="field" placeholder="5000..."
                               value="{{ $settings->get('sms.melipayamak.from') }}">
                    </div>
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="s-mp-endpoint">آدرس REST</label>
                        <input id="s-mp-endpoint" data-key="sms.melipayamak.endpoint" dir="ltr" class="field font-mono !text-xs"
                               value="{{ $settings->get('sms.melipayamak.endpoint', 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber') }}"
                               placeholder="https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber">
                    </div>
                </div>
                <p class="st-hint">
                    کد پترن قالب‌ها در «مرکز پیامک» = <b>شناسه متن ثابت (bodyId)</b> پنل ملی‌پیامک؛ مقادیر متغیرهای قالب به‌ترتیب تعریف، با «;» به هم الحاق و ارسال می‌شوند.
                </p>
            </div>

            {{-- ===== کارت تنظیمات ایده‌پردازان ===== --}}
            <div class="st-gw" data-gw="idehpardazan">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--idehpardazan" aria-hidden="true">ا</span>
                    <div class="st-gw-title flex-1">
                        <b>ایده‌پردازان <span class="font-normal text-stone-400" dir="ltr">(RestfulSms)</span></b>
                        <span>ارسال قالب سریع (Ultra Fast Send) با کلیدهای پنل</span>
                    </div>
                    @if ($idehConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">پیکربندی کامل</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-grid-2">
                    <div class="st-field-row">
                        <label class="lbl" for="s-ide-key">کلید API <span class="text-stone-400 text-[10px]" dir="ltr">(UserApiKey)</span></label>
                        <input id="s-ide-key" data-key="sms.idehpardazan.api_key" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                               placeholder="{{ $idehConfigured ? '•••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'UserApiKey پنل ایده‌پردازان' }}">
                    </div>
                    <div class="st-field-row">
                        <label class="lbl" for="s-ide-secret">کلید امنیتی <span class="text-stone-400 text-[10px]" dir="ltr">(SecretKey)</span></label>
                        <input id="s-ide-secret" data-key="sms.idehpardazan.secret_key" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                               placeholder="{{ $idehConfigured ? '•••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'SecretKey پنل ایده‌پردازان' }}">
                    </div>
                </div>

                <div class="st-field-row">
                    <label class="lbl" for="s-ide-endpoint">آدرس ارسال قالب سریع</label>
                    <input id="s-ide-endpoint" data-key="sms.idehpardazan.endpoint" dir="ltr" class="field font-mono !text-xs"
                           value="{{ $settings->get('sms.idehpardazan.endpoint', 'https://RestfulSms.com/api/UltraFastSend/direct') }}"
                           placeholder="https://RestfulSms.com/api/UltraFastSend/direct">
                </div>
                <p class="st-hint">
                    کد پترن قالب‌ها در «مرکز پیامک» = <b>TemplateId</b> قالب سریع ایده‌پردازان؛ متغیرها به‌صورت «پارامتر نام‌دار» ارسال می‌شوند.
                </p>
            </div>

            {{-- ===== لاگ توسعه ===== --}}
            <div class="st-gw" data-gw="log">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--log" aria-hidden="true">ل</span>
                    <div class="st-gw-title flex-1">
                        <b>لاگ توسعه <span class="font-normal text-stone-400" dir="ltr">(log)</span></b>
                        <span>بدون ارسال واقعی — همهٔ پیامک‌ها فقط در «گزارش پیامک‌ها» ثبت می‌شوند</span>
                    </div>
                    <span class="badge bg-stone-100 text-stone-500 border border-stone-200">محیط توسعه</span>
                </div>
                <p class="st-hint">برای تست کامل جریان‌های سیستم بدون هزینهٔ پیامک مناسب است؛ در پروداکشن یکی از پرووایدرهای واقعی بالا را انتخاب و پیکربندی کنید.</p>
            </div>

            {{-- ===== رویدادهای اطلاع‌رسانی پیامکی (v25) ===== --}}
            <div class="st-sub-card" style="background:linear-gradient(135deg,rgba(37,99,235,.05),transparent)">
                <div class="st-sub-head">
                    <b>رویدادهای اطلاع‌رسانی پیامکی</b>
                    <span class="badge {{ $settings->get('sms.notify.transfer_offline') || $settings->get('sms.notify.salary') || $settings->get('sms.notify.unaccepted') ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200' }}">
                        {{ $settings->get('sms.notify.transfer_offline') || $settings->get('sms.notify.salary') || $settings->get('sms.notify.unaccepted') ? 'برخی فعال' : 'همه خاموش' }}
                    </span>
                </div>
                <p class="st-hint leading-6">
                    این پیامک‌های اطلاع‌رسانی جدا از پیامک‌های اصلی فرایند (OTP/پذیرش/تحویل سفارش) هستند و مکمل نوتیف دستگاه‌اند —
                    متن قالب‌ها از «مرکز پیامک» (کلیدهای notify.*) قابل ویرایش است.
                </p>

                <div class="mt-3 space-y-2.5">
                    <label class="flex items-start gap-3 cursor-pointer select-none p-3 rounded-xl border border-stone-200 hover:border-stone-300 transition-colors">
                        <input type="checkbox" data-key="sms.notify.ticket_reply" class="size-4.5 mt-0.5 accent-emerald-600 shrink-0" {{ $settings->get('sms.notify.ticket_reply', '1') ? 'checked' : '' }}>
                        <span class="text-xs leading-5">
                            <b class="text-stone-700">پاسخ پشتیبانی به مشتری</b>
                            <span class="block text-stone-400 text-[11px] mt-0.5">وقتی کارشناس به تیکت مشتری پاسخ می‌دهد، پیامک خبر می‌رود (رفتار پیش‌فرض سیستم)</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer select-none p-3 rounded-xl border border-stone-200 hover:border-stone-300 transition-colors">
                        <input type="checkbox" data-key="sms.notify.transfer_offline" class="size-4.5 mt-0.5 accent-emerald-600 shrink-0" {{ $settings->get('sms.notify.transfer_offline') ? 'checked' : '' }}>
                        <span class="text-xs leading-5">
                            <b class="text-stone-700">انتقال درخواست به کافی‌نت/اپراتورِ آفلاین</b>
                            <span class="block text-stone-400 text-[11px] mt-0.5">وقتی مدیر کل درخواستی را به کافی‌نتی/اپراتوری که آنلاین نیست منتقل می‌کند، به او پیامک می‌رود</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer select-none p-3 rounded-xl border border-stone-200 hover:border-stone-300 transition-colors">
                        <input type="checkbox" data-key="sms.notify.salary" class="size-4.5 mt-0.5 accent-emerald-600 shrink-0" {{ $settings->get('sms.notify.salary') ? 'checked' : '' }}>
                        <span class="text-xs leading-5">
                            <b class="text-stone-700">واریز حقوق / کمیسیون به کیف پول</b>
                            <span class="block text-stone-400 text-[11px] mt-0.5">با هر تسویهٔ سفارش، واریز درآمد اپراتور و سهم کافی‌نت/سازمان به ذی‌نفعان پیامک می‌شود</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer select-none p-3 rounded-xl border border-stone-200 hover:border-stone-300 transition-colors">
                        <input type="checkbox" data-key="sms.notify.unaccepted" class="size-4.5 mt-0.5 accent-emerald-600 shrink-0" {{ $settings->get('sms.notify.unaccepted') ? 'checked' : '' }}>
                        <span class="text-xs leading-5">
                            <b class="text-stone-700">درخواست بی‌پذیرش (یادآوری مدیر آفلاین)</b>
                            <span class="block text-stone-400 text-[11px] mt-0.5">اگر درخواستی پذیرفته نشد و مدیر آنلاین نبود، پیامک + نوتیف دستگاه به مدیر می‌رسد</span>
                        </span>
                    </label>

                    <div class="st-field-row !mb-0 pt-1">
                        <label class="lbl" for="sn-unaccepted-min">آستانهٔ پیامک بی‌پذیرش (دقیقه)</label>
                        <input id="sn-unaccepted-min" data-key="sms.notify.unaccepted_minutes" type="number" min="5" max="240" class="field" dir="ltr"
                               value="{{ (int) $settings->get('sms.notify.unaccepted_minutes', 15) }}">
                        <p class="st-hint">اگر درخواست بعد از این مدت هنوز پذیرفته نشده باشد، مدیران مطلع می‌شوند (بررسی هر ۵ دقیقه)</p>
                    </div>
                </div>
            </div>

            <div class="st-section-foot">
                <button type="button" id="btn-test-sms" class="btn-ghost ui-press !py-2.5">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4Z"/></svg>
                    ارسال پیامک آزمایشی
                </button>
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات پیامک</button>
            </div>
        </form>

        {{-- ---------- سفارش‌ها ---------- --}}
        <form data-group="orders" class="st-section card ui-lift animate-fade-up hidden" id="sec-orders">
            <div class="st-section-head">
                <span class="st-section-icon st-section-icon--orders" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">موتور پخش سفارش‌ها</h2>
                    <p class="st-section-desc">سفارش‌های جدید بین کافی‌نت‌ها پخش می‌شوند — محدوده، مهلت و سیاست پس از انقضا را تعیین کنید.</p>
                </div>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="o-scope">محدودهٔ پخش سفارش</label>
                <select id="o-scope" data-key="orders.broadcast_scope" class="field">
                    @foreach (['all' => 'همهٔ کافی‌نت‌ها (بدون فیلتر جغرافیایی)', 'province' => 'فقط کافی‌نت‌های هم‌استان مشتری', 'city' => 'فقط کافی‌نت‌های هم‌شهرستان مشتری'] as $value => $label)
                        <option value="{{ $value }}" {{ $settings->get('orders.broadcast_scope', 'all') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="st-hint">پیش‌فرض فعلی: پخش به همهٔ شهرستان‌ها بدون فیلتر</p>
            </div>

            <div class="st-grid-2">
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="o-timeout">مهلت پخش (ثانیه)</label>
                    <input id="o-timeout" data-key="orders.broadcast_timeout" type="number" min="15" max="300" class="field"
                           value="{{ $settings->get('orders.broadcast_timeout', 60) }}">
                    <p class="st-hint">پس از این زمان بدون پذیرش، سفارش به صف تعیین‌تکلیف می‌رود</p>
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="o-assign">سیاست پس از انقضای مهلت</label>
                    <select id="o-assign" data-key="orders.assign_after_timeout" class="field">
                        @foreach (['manual' => 'در انتظار تخصیص دستی ادمین', 'rebroadcast' => 'پخش مجدد خودکار (چرخشی)'] as $value => $label)
                            <option value="{{ $value }}" {{ $settings->get('orders.assign_after_timeout', 'manual') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="st-hint">سیاست فعلی: صف تعیین‌تکلیف دستی</p>
                </div>
            </div>

            <div class="border-t border-dashed border-stone-200 my-2" role="separator" aria-hidden="true"></div>

            {{-- v39 — صفحهٔ انتظار مشتری: ثانیه‌شمار + متن‌ها --}}
            @php
                $broadcastTimerOn = (bool) $settings->get('orders.broadcast_timer_enabled', true);
            @endphp
            <p class="text-xs font-extrabold text-stone-500 mb-1">صفحهٔ انتظار مشتری (کارت‌های «ارسال به اپراتورها» و «صف تعیین‌تکلیف»)</p>

            <div class="st-switch-row">
                <div>
                    <p class="text-xs font-bold text-stone-700">نمایش ثانیه‌شمار مهلت پخش</p>
                    <p class="text-[11px] text-stone-400 mt-0.5 leading-5">
                        وقتی فعال باشد، مشتری در حین پخش سفارش، حلقهٔ ثانیه‌شمار را می‌بیند؛
                        وقتی خاموش باشد، فقط متن کارت پخش (بدون شمارش معکوس) نمایش داده می‌شود.
                    </p>
                </div>
                <label class="st-switch" for="o-timer-enabled">
                    <input type="checkbox" id="o-timer-enabled" data-key="orders.broadcast_timer_enabled" class="peer sr-only" {{ $broadcastTimerOn ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="o-broadcast-text">متن کارت «ارسال به اپراتورها»</label>
                @php
                $broadcastTextDefault = 'درخواستتان بین اپراتورها و کافی‌نت‌های فعال پخش شده است؛ اولین اپراتوری که آن را بپذیرد، به شما وصل می‌شود و گفتگو آغاز می‌گردد.';
                @endphp
                <textarea id="o-broadcast-text" data-key="orders.broadcast_text" rows="3" class="field" maxlength="500" placeholder="{{ $broadcastTextDefault }}">{{ $settings->get('orders.broadcast_text') ?: $broadcastTextDefault }}</textarea>
                <p class="st-hint">در اپ مشتری، زیر عنوان «درخواست شما در حال ارسال به اپراتورهاست» نمایش داده می‌شود — خالی بماند متن پیش‌فرض استفاده می‌شود.</p>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="o-queued-text">متن کارت «صف تعیین‌تکلیف»</label>
                @php
                $queuedTextDefault = 'سفارش شما با موفقیت ثبت شد. همکاران ما در اولین فرصت آن را بررسی و به یکی از کافی‌نت‌ها تخصیص می‌دهند و نتیجه را از طریق پیامک و تماس به شما اطلاع می‌دهند.';
                @endphp
                <textarea id="o-queued-text" data-key="orders.queued_text" rows="3" class="field" maxlength="500" placeholder="{{ $queuedTextDefault }}">{{ $settings->get('orders.queued_text') ?: $queuedTextDefault }}</textarea>
                <p class="st-hint">هنگامی که مهلت پخش تمام شود و هیچ اپراتوری سفارش را نپذیرد، این پیام به مشتری نشان داده می‌شود — خالی بماند متن پیش‌فرض استفاده می‌شود.</p>
            </div>

            <div class="st-section-foot">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات سفارش‌ها</button>
            </div>
        </form>

        {{-- ---------- نظرسنجی و امتیاز (v33) ---------- --}}
        @php
            $surveyEnabled = (bool) $settings->get('ratings.survey_enabled', true);
            $notifyLow = (bool) $settings->get('ratings.notify_low', true);
            $notifyThreshold = (int) $settings->get('ratings.notify_low_threshold', 2);
            $routingEnabled = (bool) $settings->get('ratings.routing_enabled', false);
            $routingMode = (string) $settings->get('ratings.routing_mode', 'hybrid');
            $routingMinRating = (int) $settings->get('ratings.routing_min_rating', 3);
            $routingMinVotes = (int) $settings->get('ratings.routing_min_votes', 3);
            $unratedPolicy = (string) $settings->get('ratings.routing_unrated_policy', 'include');
        @endphp
        <form data-group="ratings" class="st-section card ui-lift animate-fade-up hidden" id="sec-ratings">
            <div class="st-section-head">
                <span class="st-section-icon st-section-icon--orders" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">نظرسنجی، امتیاز و پخش هوشمند</h2>
                    <p class="st-section-desc">پس از تحویل، مشتری با ستاره به تجربه و اپراتور امتیاز می‌دهد و دلایل (برخورد مناسب، انجام سریع کار…) را تیک می‌زند — همه از <a href="{{ route('admin.ratings.index') }}" class="text-amber-700 font-bold underline decoration-dotted">صفحهٔ نظرسنجی‌ها</a> قابل مشاهده و فیلتر است. این‌جا رفتار کلی را تعیین کنید.</p>
                </div>
            </div>

            <div class="st-switch-row">
                <div>
                    <p class="text-xs font-bold text-stone-700">نظرسنجی پس از تحویل</p>
                    <p class="text-[11px] text-stone-400 mt-0.5 leading-5">وقتی فعال باشد، پس از تحویل سفارش، کارت نظرسنجی (ستاره + دلایل + دیدگاه) در اپ مشتری نمایش داده می‌شود.</p>
                </div>
                <label class="st-switch" for="rt-survey-enabled">
                    <input type="checkbox" id="rt-survey-enabled" data-key="ratings.survey_enabled" class="peer sr-only" {{ $surveyEnabled ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div>
                    <p class="text-xs font-bold text-stone-700">اعلان امتیاز پایین به مدیران</p>
                    <p class="text-[11px] text-stone-400 mt-0.5 leading-5">وقتی مشتری امتیازی در حد آستانه یا پایین‌تر بدهد، مدیر کل و مدیر کافی‌netِ مربوطه اعلان (و پوش در صورت آفلاین بودن) می‌گیرند.</p>
                </div>
                <label class="st-switch" for="rt-notify-low">
                    <input type="checkbox" id="rt-notify-low" data-key="ratings.notify_low" class="peer sr-only" {{ $notifyLow ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>
            <div class="st-field-row {{ $notifyLow ? '' : 'hidden' }}" id="rt-notify-threshold-row">
                <label class="lbl" for="rt-notify-threshold">آستانهٔ اعلان امتیاز پایین</label>
                <select id="rt-notify-threshold" data-key="ratings.notify_low_threshold" class="field">
                    @foreach ([1 => '۱ و پایین‌تر (فقط بسیار بد)', 2 => '۲ و پایین‌تر (پیشنهادی)', 3 => '۳ و پایین‌تر (حساس)', 4 => '۴ و پایین‌تر (بسیار حساس)'] as $v => $l)
                        <option value="{{ $v }}" {{ $notifyThreshold === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="st-hint">مثلاً با آستانهٔ ۲: امتیازهای ۱ و ۲ اعلان فوری دارند</p>
            </div>

            <div class="border-t border-dashed border-stone-200 my-2" role="separator" aria-hidden="true"></div>

            {{-- پخش هوشمند --}}
            <div class="st-switch-row">
                <div>
                    <p class="text-xs font-bold text-stone-700">پخش سفارش‌ها بر اساس امتیاز کافی‌netها</p>
                    <p class="text-[11px] text-stone-400 mt-0.5 leading-5" id="rt-routing-desc">
                        @if ($routingEnabled)
                            فعال — سفارش‌های جدید فقط/اول به کافی‌netهای با امتیاز خوب پخش می‌شوند (بر اساس سیاست انتخابی).
                        @else
                            خاموش — سفارش‌ها مثل قبل به همهٔ کافی‌netهای فعالِ محدوده پخش می‌شوند.
                        @endif
                    </p>
                </div>
                <label class="st-switch" for="rt-routing-enabled">
                    <input type="checkbox" id="rt-routing-enabled" data-key="ratings.routing_enabled" class="peer sr-only" {{ $routingEnabled ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div id="rt-routing-box" class="st-sub-card {{ $routingEnabled ? '' : 'hidden' }}">
                <div class="st-sub-head">
                    <b>سیاست پخش هوشمند</b>
                    <span class="badge bg-amber-50 text-amber-700 border border-amber-200">
                        {{ fa_number($ratingSummary['eligible']) }} از {{ fa_number($ratingSummary['approved']) }} کافی‌net واجد شرایط
                    </span>
                </div>

                <div class="st-field-row">
                    <label class="lbl" for="rt-routing-mode">حالت پخش</label>
                    <select id="rt-routing-mode" data-key="ratings.routing_mode" class="field">
                        <option value="hybrid" {{ $routingMode === 'hybrid' ? 'selected' : '' }}>هوشمند ترکیبی (پیشنهادی) — فقط واجد شرایط‌ها؛ اگر نبود، همه</option>
                        <option value="filter" {{ $routingMode === 'filter' ? 'selected' : '' }}>سخت‌گیرانه — فقط واجد شرایط‌ها؛ وگرنه صف تعیین‌تکلیف</option>
                        <option value="priority" {{ $routingMode === 'priority' ? 'selected' : '' }}>اولویت‌بندی — همه می‌گیرند؛ امتیاز بالاتر زودتر خبردار</option>
                    </select>
                    <p class="st-hint" id="rt-mode-hint">
                        {{ $routingMode === 'filter'
                            ? 'سخت‌گیرانه: اگر هیچ کافی‌netی واجد شرایط نباشد، سفارش به صف تعیین‌تکلیف (تخصیص دستی) می‌رود.'
                            : ($routingMode === 'priority'
                                ? 'اولویت‌بندی: هیچ کافی‌netی حذف نمی‌شود؛ فقط ترتیب اطلاع‌رسانی/پخش بر اساس امتیاز است.'
                                : 'ترکیبی: ابتدا فقط کافی‌netهای واجد شرایط؛ اگر هیچ‌کدام نبود، برای نجات سفارش به همه پخش می‌شود.') }}
                    </p>
                </div>

                <div class="st-grid-2">
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="rt-min-rating">حداقل میانگین امتیاز</label>
                        <select id="rt-min-rating" data-key="ratings.routing_min_rating" class="field">
                            @foreach ([
                                2 => '۲ ستاره به بالا (آسان‌گیر)',
                                3 => '۳ ستاره به بالا (پیشنهادی)',
                                4 => '۴ ستاره به بالا (سخت‌گیر)',
                                5 => 'فقط ۵ ستاره (بسیار سخت‌گیر)',
                            ] as $v => $l)
                                <option value="{{ $v }}" {{ $routingMinRating === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="rt-min-votes">حداقل تعداد نظرات</label>
                        <input id="rt-min-votes" data-key="ratings.routing_min_votes" type="number" min="1" max="1000" class="field" dir="ltr"
                               value="{{ $routingMinVotes }}">
                        <p class="st-hint">کافی‌net با نظرات کمتر از این عدد «بدون امتیاز معتبر» در نظر گرفته می‌شود</p>
                    </div>
                </div>

                <div class="st-field-row">
                    <label class="lbl" for="rt-unrated">کافی‌netهای بدون امتیاز معتبر</label>
                    <select id="rt-unrated" data-key="ratings.routing_unrated_policy" class="field">
                        <option value="include" {{ $unratedPolicy === 'include' ? 'selected' : '' }}>شرکت کنند (رفتار مهربان — پیشنهادی)</option>
                        <option value="exclude" {{ $unratedPolicy === 'exclude' ? 'selected' : '' }}>حذف شوند (فقط امتیازدارها)</option>
                    </select>
                    <p class="st-hint">تا وقتی نظرات کافی‌net به حد نصاب نرسیده، این سیاست رفتار آن را تعیین می‌کند</p>
                </div>
            </div>

            <div class="st-section-foot flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات نظرسنجی</button>
                <a href="{{ route('admin.ratings.index') }}" class="btn-ghost ui-press !py-2.5 !px-5 !text-xs">مشاهدهٔ نظرسنجی‌ها ↗</a>
            </div>
        </form>

        {{-- ---------- ساعت کاری (فاز ۱۵) ---------- --}}
        <form data-group="workhours" class="st-section card ui-lift animate-fade-up hidden" id="sec-workhours">
            <div class="st-section-head">
                <span class="st-section-icon st-section-icon--referral" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">ساعت کاری و محدودیت ثبت درخواست</h2>
                    <p class="st-section-desc">خارج از ساعت کاری، مشتری نمی‌تواند درخواست جدید ثبت کند — در اپ مشتری و API با مودال زیبا informing می‌شود (همین قاعده در POST /orders هم اعمال می‌شود).</p>
                </div>
            </div>

            @php
                $whEnabled = (bool) $settings->get('workhours.enabled');
                $whDays = collect(explode(',', (string) $settings->get('workhours.days', '6,0,1,2,3,4')))
                    ->map(fn ($d) => (int) trim($d))->filter(fn ($d) => $d >= 0 && $d <= 6)->values()->all();
                $weekDays = [
                    ['value' => 6, 'label' => 'شنبه'],
                    ['value' => 0, 'label' => 'یکشنبه'],
                    ['value' => 1, 'label' => 'دوشنبه'],
                    ['value' => 2, 'label' => 'سه‌شنبه'],
                    ['value' => 3, 'label' => 'چهارشنبه'],
                    ['value' => 4, 'label' => 'پنج‌شنبه'],
                    ['value' => 5, 'label' => 'جمعه'],
                ];
            @endphp

            {{-- سوییچ فعال/غیرفعال --}}
            <div class="st-switch-row">
                <div class="min-w-0">
                    <b class="block text-sm">فعال‌سازی محدودیت ساعت کاری</b>
                    <small class="st-hint">وقتی روشن باشد، ثبت درخواست فقط در بازهٔ تعیین‌شده ممکن است.</small>
                </div>
                <label class="st-switch">
                    <input type="checkbox" id="wh-enabled" class="opacity-0" data-key="workhours.enabled" {{ $whEnabled ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-grid-2">
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="wh-start">شروع ساعت کاری</label>
                    <input id="wh-start" data-key="workhours.start" type="time" dir="ltr" class="field !text-center"
                           value="{{ $settings->get('workhours.start', '08:00') }}">
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="wh-end">پایان ساعت کاری</label>
                    <input id="wh-end" data-key="workhours.end" type="time" dir="ltr" class="field !text-center"
                           value="{{ $settings->get('workhours.end', '22:00') }}">
                    <p class="st-hint">اگر پایان قبل از شروع باشد، بازهٔ شبانه در نظر گرفته می‌شود (مثلاً ۱۸:۰۰ تا ۰۲:۰۰).</p>
                </div>
            </div>

            <div class="st-field-row">
                <label class="lbl">روزهای کاری</label>
                <input type="hidden" id="wh-days" data-key="workhours.days" value="{{ implode(',', $whDays) }}">
                <div class="flex flex-wrap gap-1.5" role="group" aria-label="روزهای کاری">
                    @foreach ($weekDays as $day)
                        <button type="button" class="wh-day-chip {{ in_array($day['value'], $whDays, true) ? 'is-on' : '' }}"
                                data-day="{{ $day['value'] }}" aria-pressed="{{ in_array($day['value'], $whDays, true) ? 'true' : 'false' }}">
                            {{ $day['label'] }}
                        </button>
                    @endforeach
                </div>
                <p class="st-hint">حداقل یک روز باید انتخاب باشد — روزهای غیر انتخابی، کل روز «بسته» محسوب می‌شوند.</p>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="wh-message">پیام سفارشی مودال خارج از ساعت کاری (اختیاری)</label>
                <textarea id="wh-message" data-key="workhours.message" class="field min-h-16" rows="2" maxlength="500"
                          placeholder="مثلاً: لطفاً در ساعت کاری (۹ صبح تا ۹ شب) درخواست خود را ثبت کنید.">{{ $settings->get('workhours.message', '') }}</textarea>
            </div>

            <div class="st-section-foot">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات ساعت کاری</button>
            </div>
        </form>

        {{-- ---------- درگاه پرداخت (بازطراحی v13 — درگاه بانکی) ---------- --}}
        <form data-group="payment" class="st-section card ui-lift animate-fade-up hidden" id="sec-payment">
            <div class="st-section-head">
                <span class="st-section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">درگاه پرداخت آنلاین</h2>
                    <p class="st-section-desc">درگاه فعال را انتخاب کنید — اطلاعات پذیرندگی همان درگاه بلافاصله زیر کارت‌ها باز می‌شود. پرداخت سفارش و شارژ کیف پول مشتری از همین درگاه انجام می‌شود (پکیج shetabit/payment).</p>
                </div>
                <span class="badge {{ $payDriver === 'local' ? 'bg-stone-100 text-stone-500 border border-stone-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}" id="pay-driver-badge">{{ $payDriverLabel }}</span>
            </div>

            {{-- کارت‌های انتخاب درگاه — فعال: تنظیمات همان درگاه زیرش باز می‌شود --}}
            <div class="st-pick-head">
                <b>درگاه فعال</b>
                <span>با انتخاب کارت، اطلاعات پذیرندگی همان درگاه در ادامه نمایان می‌شود</span>
            </div>

            <div class="st-prov-grid st-prov-grid--pay" role="radiogroup" aria-label="انتخاب درگاه پرداخت">
                <label class="st-prov-card" data-driver="local">
                    <input type="radio" name="pay-driver" value="local" class="sr-only" {{ $payDriver === 'local' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--local" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 2 2 4-4"/><circle cx="10" cy="8" r="4"/><path d="M14 18a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/></svg>
                    </span>
                    <b class="st-prov-name">درگاه تست</b>
                    <span class="st-prov-sub">local · بدون کلید</span>
                </label>

                <label class="st-prov-card" data-driver="zarinpal">
                    <input type="radio" name="pay-driver" value="zarinpal" class="sr-only" {{ $payDriver === 'zarinpal' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--zarinpal" aria-hidden="true">ز</span>
                    <b class="st-prov-name">زرین‌پال</b>
                    <span class="st-prov-sub">merchantId پذیرنده</span>
                </label>

                <label class="st-prov-card" data-driver="zibal">
                    <input type="radio" name="pay-driver" value="zibal" class="sr-only" {{ $payDriver === 'zibal' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--zibal" aria-hidden="true">ض</span>
                    <b class="st-prov-name">زیبال</b>
                    <span class="st-prov-sub">merchantId پذیرنده</span>
                </label>

                <label class="st-prov-card" data-driver="behpardakht">
                    <input type="radio" name="pay-driver" value="behpardakht" class="sr-only" {{ $payDriver === 'behpardakht' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--mellat" aria-hidden="true">م</span>
                    <b class="st-prov-name">بانک ملت</b>
                    <span class="st-prov-sub">به‌پرداخت ملت · BPM</span>
                </label>

                <label class="st-prov-card" data-driver="sep">
                    <input type="radio" name="pay-driver" value="sep" class="sr-only" {{ $payDriver === 'sep' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--melli" aria-hidden="true">م</span>
                    <b class="st-prov-name">بانک ملی</b>
                    <span class="st-prov-sub">درگاه سپ · SEP</span>
                </label>

                <label class="st-prov-card" data-driver="sepehr">
                    <input type="radio" name="pay-driver" value="sepehr" class="sr-only" {{ $payDriver === 'sepehr' ? 'checked' : '' }}>
                    <span class="st-prov-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span class="st-prov-tile st-tile--sepehr" aria-hidden="true">س</span>
                    <b class="st-prov-name">درگاه سپهر</b>
                    <span class="st-prov-sub">بانک صادرات</span>
                </label>
            </div>

            <input type="hidden" id="p-driver" data-key="payment.driver" value="{{ $payDriver }}">

            {{-- ===== درگاه تست (local) ===== --}}
            <div class="st-gw" data-gw="local">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--local" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 2 2 4-4"/><circle cx="10" cy="8" r="4"/><path d="M14 18a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/></svg>
                    </span>
                    <div class="st-gw-title flex-1">
                        <b>درگاه تست <span class="font-normal text-stone-400" dir="ltr">(local)</span></b>
                        <span>درگاه داخلی برای توسعه — بدون کلید؛ دکمه‌های «پرداخت موفق / ناموفق» واقعی</span>
                    </div>
                    <span class="badge bg-stone-100 text-stone-500 border border-stone-200">بدون کلید</span>
                </div>
                <p class="st-hint">برای بررسی صحت روند پرداخت و لغو پرداخت استفاده می‌شود؛ در پروداکشن یکی از درگاه‌های واقعی را انتخاب و پیکربندی کنید.</p>
            </div>

            {{-- ===== زرین‌پال ===== --}}
            <div class="st-gw" data-gw="zarinpal">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--zarinpal" aria-hidden="true">ز</span>
                    <div class="st-gw-title flex-1">
                        <b>زرین‌پال <span class="font-normal text-stone-400" dir="ltr">(ZarinPal)</span></b>
                        <span>درگاه رسمی زرین‌پال — نیازمند مرچنت‌کد پذیرنده</span>
                    </div>
                    @if ($zarinpalConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">مرچنت ثبت‌شده</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-field-row">
                    <label class="lbl" for="p-zarinpal-merchant">مرچنت‌کد پذیرنده</label>
                    <input id="p-zarinpal-merchant" data-key="payment.zarinpal.merchant_id" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                           placeholder="{{ $zarinpalConfigured ? '•••••••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'مرچنت‌کد ۳۶ کاراکتری زرین‌پال' }}">
                </div>

                <div class="st-switch-row !mb-0">
                    <div>
                        <p class="text-xs font-bold text-stone-700">حالت آزمایشی <span class="text-stone-400 text-[10px]" dir="ltr">(Sandbox)</span></p>
                        <p class="text-[11px] text-stone-400 mt-0.5">پرداخت‌ها به سرور تست زرین‌پال ارسال می‌شوند — برای اتصال واقعی خاموش کنید</p>
                    </div>
                    <label class="st-switch" for="p-zarinpal-sandbox">
                        <input type="checkbox" id="p-zarinpal-sandbox" data-key="payment.zarinpal.sandbox" class="peer sr-only" {{ $zarinpalSandbox ? 'checked' : '' }}>
                        <span class="st-switch-track" aria-hidden="true"></span>
                    </label>
                </div>
            </div>

            {{-- ===== زیبال ===== --}}
            <div class="st-gw" data-gw="zibal">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--zibal" aria-hidden="true">ض</span>
                    <div class="st-gw-title flex-1">
                        <b>زیبال <span class="font-normal text-stone-400" dir="ltr">(Zibal)</span></b>
                        <span>درگاه زیبال — نیازمند مرچنت‌کد پذیرنده</span>
                    </div>
                    @if ($zibalConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">مرچنت ثبت‌شده</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-field-row !mb-0">
                    <label class="lbl" for="p-zibal-merchant">مرچنت‌کد پذیرنده</label>
                    <input id="p-zibal-merchant" data-key="payment.zibal.merchant_id" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                           placeholder="{{ $zibalConfigured ? '•••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'مرچنت‌کد زیبال' }}">
                    <p class="st-hint">مرچنت <span class="font-mono text-amber-600" dir="ltr">zibal</span> برای تست درگاه زیبال است</p>
                </div>
            </div>

            {{-- ===== بانک ملت (به‌پرداخت) ===== --}}
            <div class="st-gw" data-gw="behpardakht">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--mellat" aria-hidden="true">م</span>
                    <div class="st-gw-title flex-1">
                        <b>بانک ملت <span class="font-normal text-stone-400" dir="ltr">(به‌پرداخت ملت — BPM)</span></b>
                        <span>درگاه شاپرک به‌پرداخت ملت · نیازمند ترمینال + نام کاربری + رمز</span>
                    </div>
                    @if ($behpConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">پیکربندی کامل</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-grid-2">
                    <div class="st-field-row">
                        <label class="lbl" for="p-mellat-terminal">شماره ترمینال <span class="text-stone-400 text-[10px]" dir="ltr">(terminalId)</span></label>
                        <input id="p-mellat-terminal" data-key="payment.behpardakht.terminal_id" dir="ltr" class="field font-mono !text-xs" inputmode="numeric"
                               placeholder="مثال: 1234567" value="{{ $settings->get('payment.behpardakht.terminal_id') }}">
                    </div>
                    <div class="st-field-row">
                        <label class="lbl" for="p-mellat-user">نام کاربری <span class="text-stone-400 text-[10px]" dir="ltr">(username)</span></label>
                        <input id="p-mellat-user" data-key="payment.behpardakht.username" dir="ltr" class="field font-mono !text-xs" autocomplete="off"
                               placeholder="نام کاربری پذیرنده" value="{{ $settings->get('payment.behpardakht.username') }}">
                    </div>
                </div>

                <div class="st-field-row !mb-0">
                    <label class="lbl" for="p-mellat-pass">رمز عبور <span class="text-stone-400 text-[10px]" dir="ltr">(password)</span></label>
                    <input id="p-mellat-pass" data-key="payment.behpardakht.password" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                           placeholder="{{ $behpConfigured ? '•••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'رمز عبور پذیرنده به‌پرداخت ملت' }}">
                </div>
                <p class="st-hint">
                    سه مقدار <span class="font-mono text-amber-600" dir="ltr">terminalId · username · password</span> همان‌هایی هستند که هنگام صدور درگاه در پنل به‌پرداخت ملت (bpm.shaparak.ir) دریافت کرده‌اید؛ مبلغ به تومان ارسال می‌شود.
                </p>
            </div>

            {{-- ===== بانک ملی (سپ SEP) ===== --}}
            <div class="st-gw" data-gw="sep">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--melli" aria-hidden="true">م</span>
                    <div class="st-gw-title flex-1">
                        <b>بانک ملی <span class="font-normal text-stone-400" dir="ltr">(درگاه سپ — SEP)</span></b>
                        <span>سامانه الکترونیکی پرداخت (sep.shaparak.ir) · نیازمند ترمینال</span>
                    </div>
                    @if ($sepConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">ترمینال ثبت‌شده</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-field-row !mb-0">
                    <label class="lbl" for="p-sep-terminal">شماره ترمینال <span class="text-stone-400 text-[10px]" dir="ltr">(TerminalId)</span></label>
                    <input id="p-sep-terminal" data-key="payment.sep.terminal_id" dir="ltr" class="field font-mono !text-xs" inputmode="numeric"
                           placeholder="مثال: 12345678" value="{{ $settings->get('payment.sep.terminal_id') }}">
                    <p class="st-hint">شماره ترمینال پذیرندگی که در پنل درگاه سپ بانک ملی (sep.shaparak.ir) دریافت کرده‌اید؛ مبلغ به تومان ارسال می‌شود.</p>
                </div>
            </div>

            {{-- ===== درگاه سپهر (بانک صادرات) ===== --}}
            <div class="st-gw" data-gw="sepehr">
                <div class="st-gw-head">
                    <span class="st-prov-tile st-tile--sepehr" aria-hidden="true">س</span>
                    <div class="st-gw-title flex-1">
                        <b>درگاه سپهر <span class="font-normal text-stone-400" dir="ltr">(بانک صادرات — Sepehr)</span></b>
                        <span>درگاه سپهر شاپرک (sepehr.shaparak.ir) · نیازمند ترمینال</span>
                    </div>
                    @if ($sepehrConfigured)
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">ترمینال ثبت‌شده</span>
                    @else
                        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">پیکربندی نشده</span>
                    @endif
                </div>

                <div class="st-field-row !mb-0">
                    <label class="lbl" for="p-sepehr-terminal">شماره ترمینال <span class="text-stone-400 text-[10px]" dir="ltr">(TerminalID)</span></label>
                    <input id="p-sepehr-terminal" data-key="payment.sepehr.terminal_id" dir="ltr" class="field font-mono !text-xs" inputmode="numeric"
                           placeholder="مثال: 1234567890" value="{{ $settings->get('payment.sepehr.terminal_id') }}">
                    <p class="st-hint">شماره ترمینال پذیرندگی که در پنل درگاه سپهر بانک صادرات (sepehr.shaparak.ir) دریافت کرده‌اید؛ مبلغ به تومان ارسال می‌شود.</p>
                </div>
            </div>

            <div class="st-section-foot">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات درگاه</button>
            </div>
        </form>

        {{-- ---------- کارکنان کافی‌نت‌ها (سیاست تایید) ---------- --}}
        <form data-group="staff" class="st-section card ui-lift animate-fade-up hidden" id="sec-staff">
            <div class="st-section-head">
                <span class="st-section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">کارکنان کافی‌نت‌ها</h2>
                    <p class="st-section-desc">سیاست افزودن کارمند (اپراتور/مدیر) توسط مدیران کافی‌نت — هر اپراتور فقط در یک کافی‌نت فعالیت می‌کند و انتقال او فقط توسط شما انجام می‌شود.</p>
                </div>
                @php($hiringMode = $settings->get('staff.hiring.mode', 'auto'))
                <span class="badge {{ $hiringMode === 'approval' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">{{ $hiringMode === 'approval' ? 'نیازمند تایید شما' : 'تایید خودکار' }}</span>
            </div>

            <div class="st-sub-card">
                <div class="st-sub-head">
                    <b>نحوهٔ تایید کارمند جدید</b>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup" aria-label="سیاست تایید کارمند">
                    <label class="cursor-pointer" data-mode="auto">
                        <input type="radio" name="staff-hiring-mode" value="auto" class="sr-only peer" {{ $hiringMode !== 'approval' ? 'checked' : '' }}>
                        <div class="flex items-start gap-3 rounded-2xl border-2 border-stone-200 p-4 transition-all duration-200 peer-checked:border-amber-400 peer-checked:bg-amber-50/60 hover:border-amber-300">
                            <span class="grid place-items-center size-10 rounded-xl bg-emerald-100 text-emerald-600 shrink-0" aria-hidden="true">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <div class="min-w-0">
                                <b class="block text-xs font-extrabold text-stone-800">تایید خودکار</b>
                                <span class="block text-[10px] text-stone-400 leading-5 mt-1">کارمند بلافاصله پس از افزودن توسط مدیر کافی‌نت فعال می‌شود — سریع و بدون دخالت شما</span>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer" data-mode="approval">
                        <input type="radio" name="staff-hiring-mode" value="approval" class="sr-only peer" {{ $hiringMode === 'approval' ? 'checked' : '' }}>
                        <div class="flex items-start gap-3 rounded-2xl border-2 border-stone-200 p-4 transition-all duration-200 peer-checked:border-amber-400 peer-checked:bg-amber-50/60 hover:border-amber-300">
                            <span class="grid place-items-center size-10 rounded-xl bg-amber-100 text-amber-600 shrink-0" aria-hidden="true">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.24 7.76-2.12 2.12"/><path d="M21 12h-4"/><path d="m16.24 16.24 2.12 2.12"/><path d="M12 18v4"/><path d="m7.76 16.24-2.12 2.12"/><path d="M6 12H2"/><path d="m7.76 7.76 5.66-5.66"/><circle cx="12" cy="12" r="3"/></svg>
                            </span>
                            <div class="min-w-0">
                                <b class="block text-xs font-extrabold text-stone-800">تایید توسط مدیر کل</b>
                                <span class="block text-[10px] text-stone-400 leading-5 mt-1">کارمند «در انتظار تایید» ساخته می‌شود و پس از تایید شما در صفحهٔ «کارکنان و اپراتورها» فعال می‌گردد</span>
                            </div>
                        </div>
                    </label>
                </div>

                <input type="hidden" id="s-hiring-mode" data-key="staff.hiring.mode" value="{{ $hiringMode }}">
                <p class="st-hint">کارمندان در انتظار، در صفحهٔ «کارکنان و اپراتورها» (فیلتر «در انتظار تایید») قابل تایید یا رد هستند.</p>
            </div>

            <div class="st-section-foot">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ سیاست کارکنان</button>
            </div>
        </form>

        {{-- ================== فینوتک — استعلام هویت (v40) ================== --}}
        @php($finnotechOn = (bool) $settings->get('finnotech.enabled'))
        @php($finnotechMode = (string) $settings->get('finnotech.mode', 'production'))
        <form data-group="finnotech" class="st-section card ui-lift animate-fade-up hidden" id="sec-finnotech">
            <div class="st-section-head">
                <span class="st-section-icon" aria-hidden="true" style="background:rgba(13,148,136,.12);color:#0f766e">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">فینوتک — استعلام هویت و کارت</h2>
                    <p class="st-section-desc">
                        سرویس <a href="https://finnotech.ir" target="_blank" rel="noopener" class="text-teal-700 font-bold underline decoration-dotted">فینوتک (finnotech.ir)</a>
                        استعلام‌های رسمی انجام می‌دهد: تطبیق کد ملی با شماره موبایل (شاهکار) و تطبیق شماره کارت با کد ملی.
                        اعتبارنامه‌ها را از <a href="https://console.finnotech.ir" target="_blank" rel="noopener" class="text-teal-700 font-bold underline decoration-dotted">کنسول توسعه‌دهندگان فینوتک</a> بگیرید
                        (ساخت برنامه → شناسه برنامه و رمز برنامه) و سرویس‌های «شاهکار» و «تطبیق کارت و کد ملی» را به برنامهٔ خود اضافه کنید.
                    </p>
                </div>
                <span class="badge {{ $finnotechOn ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'bg-stone-100 text-stone-500 border border-stone-200' }}">{{ $finnotechOn ? 'فعال' : 'غیرفعال' }}</span>
            </div>

            {{-- کلید اصلی: فعال/غیرفعال --}}
            <div class="st-switch-row">
                <div>
                    <p class="text-xs font-bold text-stone-700">فعال‌سازی سرویس فینوتک</p>
                    <p class="text-[11px] text-stone-400 mt-0.5 leading-5">
                        کلید اصلی: تا زمانی که خاموش است هیچ استعلامی انجام نمی‌شود و کد ملی در پروفایل مشتری اختیاری است.
                        هر بخش مصرف‌کننده (پروفایل مشتری / کارت‌های بانکی) تیک فعال‌سازی جداگانهٔ خودش را دارد.
                    </p>
                </div>
                <label class="st-switch" for="fin-enabled">
                    <input type="checkbox" id="fin-enabled" data-key="finnotech.enabled" class="peer sr-only" {{ $finnotechOn ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-sub-card">
                <div class="st-sub-head"><b>اعتبارنامه‌های برنامه (از کنسول فینوتک)</b></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="st-field-row">
                        <label class="lbl" for="fin-client-id">شناسه برنامه (clientId)</label>
                        <input id="fin-client-id" data-key="finnotech.client_id" class="field" dir="ltr" placeholder="مثلاً mycoffeinet" value="{{ $settings->get('finnotech.client_id') }}">
                    </div>

                    <div class="st-field-row">
                        <label class="lbl" for="fin-client-secret">رمز برنامه (clientSecret)</label>
                        <input id="fin-client-secret" data-key="finnotech.client_secret" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="new-password" placeholder="{{ $settings->get('finnotech.client_secret') ? '•••••••••• (ذخیره شده — خالی = بدون تغییر)' : 'مثلاً 6932dddb927b76e54997' }}">
                        <p class="st-hint">برای امنیت نمایش داده نمی‌شود؛ خالی بگذارید یعنی «بدون تغییر».</p>
                    </div>

                    <div class="st-field-row">
                        <label class="lbl" for="fin-nid">کد ملی صاحب برنامه</label>
                        <input id="fin-nid" data-key="finnotech.nid" class="field num" dir="ltr" inputmode="numeric" maxlength="10" placeholder="کد ملی ۱۰ رقمی حساب فینوتک" value="{{ $settings->get('finnotech.nid') }}">
                        <p class="st-hint">فینوتک برای صدور توکن، کد ملی صاحب برنامه را می‌خواهد (همان که در کنسول با آن ثبت‌نام کرده‌اید).</p>
                    </div>

                    <div class="st-field-row">
                        <label class="lbl" for="fin-mode">محیط سرویس</label>
                        <select id="fin-mode" data-key="finnotech.mode" class="field">
                            <option value="production" {{ $finnotechMode !== 'sandbox' ? 'selected' : '' }}>production — واقعی (api.finnotech.ir)</option>
                            <option value="sandbox" {{ $finnotechMode === 'sandbox' ? 'selected' : '' }}>sandbox — تستی (sandboxapi.finnotech.ir)</option>
                        </select>
                        <p class="st-hint">در حالت تستی، درخواست‌ها به سرور sandbox فینوتک می‌رود (بدون هزینه و استعلام واقعی).</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="button" id="finTestBtn" class="btn-primary btn-shine ui-press !py-2.5 px-6">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m13 2-2 8h6l-8 12 2-8H5l8-12Z"/></svg>
                        تست اتصال
                    </button>
                    <p class="text-[11px] text-stone-400 leading-5" id="finTestResult">اول تنظیمات را ذخیره کنید، بعد اتصال را تست کنید.</p>
                </div>
            </div>

            <div class="st-sub-card">
                <div class="st-sub-head"><b>اجازهٔ استفاده در هر بخش</b></div>

                <div class="st-switch-row">
                    <div>
                        <p class="text-xs font-bold text-stone-700">بررسی کد ملی در پروفایل مشتری (شاهکار)</p>
                        <p class="text-[11px] text-stone-400 mt-0.5 leading-5">
                            فعال: کد ملی در فرم «اطلاعات» اپ مشتری الزامی می‌شود و باید با شماره موبایلِ تأییدشدهٔ او تطبیق کند؛
                            در غیر این صورت ثبت اطلاعات رد می‌شود. با موفقیتِ تطبیق، نشان «تأییدشده» می‌گیرد.
                        </p>
                    </div>
                    <label class="st-switch" for="fin-verify-profile">
                        <input type="checkbox" id="fin-verify-profile" data-key="finnotech.verify_profile" class="peer sr-only" {{ $settings->get('finnotech.verify_profile') ? 'checked' : '' }}>
                        <span class="st-switch-track" aria-hidden="true"></span>
                    </label>
                </div>

                <div class="st-switch-row">
                    <div>
                        <p class="text-xs font-bold text-stone-700">بررسی کارت‌های بانکی (تطبیق کارت و کد ملی)</p>
                        <p class="text-[11px] text-stone-400 mt-0.5 leading-5">
                            فعال: هنگام ثبت/ویرایش کارت در پنل اپراتور/مدیر کافی‌نت/مدیر سازمان، کد ملی صاحب کارت گرفته می‌شود و
                            مالکیت کارت استعلام می‌گردد؛ کارت تأییدشده نشان ✓ می‌گیرد و در پنل ادمین قابل مشاهده است.
                        </p>
                    </div>
                    <label class="st-switch" for="fin-verify-cards">
                        <input type="checkbox" id="fin-verify-cards" data-key="finnotech.verify_cards" class="peer sr-only" {{ $settings->get('finnotech.verify_cards') ? 'checked' : '' }}>
                        <span class="st-switch-track" aria-hidden="true"></span>
                    </label>
                </div>

                <p class="st-hint">
                    نکته: اگر در لحظهٔ ثبت، سرویس فینوتک در دسترس نباشد (قطعی/تایم‌اوت)، ثبت انجام می‌شود اما بدون نشان «تأییدشده»؛
                    همهٔ استعلام‌ها (موفق و ناموفق) در جدول finnotech_logs ثبت و در لاگ فعالیت قابل پیگیری است.
                </p>
            </div>

            <div class="st-section-foot">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات فینوتک</button>
            </div>
        </form>

        {{-- ---------- پاداش معرفی ---------- --}}
        {{-- ================== Realtime — پوشر (فاز ۱۳) ================== --}}
        {{-- $pusherOn/$pusherReady از کنترلر می‌آیند --}}
        <form data-group="realtime" class="st-section card ui-lift animate-fade-up hidden" id="sec-realtime">
            <div class="st-section-head">
                <span class="st-section-icon" aria-hidden="true" style="background:rgba(245,158,11,.12);color:#b45309">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">Realtime — به‌روزرسانی لحظه‌ای</h2>
                    <p class="st-section-desc">روش ارتباط لحظه‌ای را انتخاب کنید: با هر رویداد (پیام چت، اعلان، سفارش جدید) مرورگرها همان لحظه بیدار می‌شوند و دیگر به پولینگ پرتکرار وابسته نیستند (کاهش فشار MySQL).</p>
                </div>
                <span class="badge {{ $rtMethod === 'polling' ? 'bg-stone-100 text-stone-500 border border-stone-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                    {{ ['polling' => 'فقط پولینگ', 'sse' => 'اتصال دائمی داخلی', 'pusher' => ($pusherReady ? 'پوشر فعال' : 'پوشر ناقص')][$rtMethod] }}
                </span>
            </div>

            <div class="st-sub-card" style="background:linear-gradient(135deg,rgba(245,158,11,.05),transparent)">
                <div class="st-sub-head">
                    <b>چگونه کار می‌کند؟</b>
                </div>
                <p class="st-hint leading-6">
                    هر ترابورت فقط «زنگ خبر» است؛ داده‌ها همیشه از سرور خود شما خوانده می‌شوند. با فعال بودن اتصال،
                    <b>پولینگ کاملاً خاموش می‌شود</b> و همهٔ صفحات رویدادمحور می‌شوند: پیام‌های گفتگو، زنگ اعلان‌ها،
                    صندوق پخش کافی‌نت، درخواست‌های اپراتور، بج‌های سایدبار و آمار سفارش‌ها «همان لحظه» تازه می‌شوند.
                    اگر اتصال قطع باشد، سیستم خودکار به پولینگ قبلی برمی‌گردد — هیچ داده‌ای از دست نمی‌رود.
                </p>
            </div>

            {{-- ===== روش Realtime (فاز ۱۲) ===== --}}
            <div class="st-sub-card">
                <div class="st-sub-head"><b>روش اتصال</b></div>
                <div class="grid gap-2.5 mt-1">
                    <label class="flex items-start gap-3 cursor-pointer select-none px-4 py-3 rounded-xl border border-stone-200 hover:border-stone-300 transition-colors">
                        <input type="radio" name="rt-method" value="polling" data-key="realtime.method" class="mt-0.5 accent-amber-600" {{ $rtMethod === 'polling' ? 'checked' : '' }}>
                        <span class="flex-1">
                            <span class="text-xs font-bold text-stone-700 block">۱) پولینگ دوره‌ای <span class="text-stone-400 font-normal">(پیش‌فرض — بدون تنظیمات)</span></span>
                            <span class="text-[11px] text-stone-400 leading-5 block mt-0.5">سازگار با همهٔ هاست‌ها؛ تأخیر ~۳ ثانیه و درخواست دوره‌ای به سرور. ساده‌ترین گزینه اما «لحظه‌ای» نیست.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer select-none px-4 py-3 rounded-xl border border-stone-200 hover:border-stone-300 transition-colors">
                        <input type="radio" name="rt-method" value="sse" data-key="realtime.method" class="mt-0.5 accent-amber-600" {{ $rtMethod === 'sse' ? 'checked' : '' }}>
                        <span class="flex-1">
                            <span class="text-xs font-bold text-stone-700 block">۲) اتصال دائمی داخلی (SSE) <span class="text-emerald-600 font-normal">— مثل سوکت، بدون سرویس بیرونی ✓ توصیه‌شده</span></span>
                            <span class="text-[11px] text-stone-400 leading-5 block mt-0.5">جریان دائمی از همان دامنهٔ خودتان؛ مثل سوکت هیچ درخواست دوره‌ای زده نمی‌شود و پیام‌ها همان لحظه می‌رسند. بدون تحریم و بدون هزینه. توجه: هر اتصالِ باز یک پروسهٔ PHP اشغال می‌کند — روی هاست اشتراکی محدودیت «Entry Processes» را در نظر بگیرید (برای ده‌ها کاربر هم‌زمان کافی است).</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer select-none px-4 py-3 rounded-xl border border-stone-200 hover:border-stone-300 transition-colors">
                        <input type="radio" name="rt-method" value="pusher" data-key="realtime.method" class="mt-0.5 accent-amber-600" {{ $rtMethod === 'pusher' ? 'checked' : '' }}>
                        <span class="flex-1">
                            <span class="text-xs font-bold text-stone-700 block">۳) سوکت خارجی (پوشر / Soketi / Reverb)</span>
                            <span class="text-[11px] text-stone-400 leading-5 block mt-0.5">سوکت واقعی با سرویس سازگار با پروتکل Pusher. توجه: سرویس pusher.com از ایران معمولاً بلاک/تحریم است و اگر مرورگر کاربر نتواند وصل شود، سیستم خودکار به پولینگ اضطراری برمی‌گردد. اگر VPS دارید، سرور Soketi/Reverb (خودمیزبان) را با «هاست سفارشی» زیر معرفی کنید.</span>
                        </span>
                    </label>
                </div>
            </div>

            {{-- ===== کارت راهنمای SSE ===== --}}
            <div id="rt-sse-zone" class="st-sub-card {{ $rtMethod === 'sse' ? '' : 'hidden' }}" style="background:linear-gradient(135deg,rgba(16,185,129,.06),transparent)">
                <div class="st-sub-head"><b>اتصال دائمی داخلی — چطور کار می‌کند؟</b></div>
                <p class="st-hint leading-6">
                    هیچ کلید و سرویسی لازم نیست — فقط بعد از ذخیره، یک بار «تست اتصال» بزنید تا سلامت جدول رویدادها تأیید شود.
                    مرورگرها با <span dir="ltr" class="font-mono text-[10px]">EventSource</span> به مسیر <span dir="ltr" class="font-mono text-[10px]">/realtime/stream</span> همین دامنه وصل می‌شوند و تا زمان باز بودن صفحه، هر رویداد جدید همان لحظه تحویل گرفته می‌شود.
                    اتصال هر ~۴ دقیقه به‌صورت تمیز تجدید می‌شود (بدون هیچ مداخله‌ای در صفحات).
                </p>
            </div>

            {{-- ===== زون پوشر (فقط وقتی روش = پوشر) ===== --}}
            <div id="rt-pusher-zone" class="{{ $rtMethod === 'pusher' ? '' : 'hidden' }}">
            <div class="st-grid-2">
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="rt-app-id">App ID <span class="text-stone-400 text-[10px]">(رقمی)</span></label>
                    <input id="rt-app-id" data-key="realtime.pusher.app_id" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                           placeholder="{{ trim((string) $settings->get('realtime.pusher.app_id')) !== '' ? '••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'مثال: 1234567' }}">
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="rt-cluster">Cluster</label>
                    <select id="rt-cluster" data-key="realtime.pusher.cluster" class="field">
                        @foreach (['mt1' => 'mt1 — بمبئی (توصیه‌شده برای ایران)', 'eu' => 'eu — اروپا', 'ap2' => 'ap2 — آسیا-جنوب‌شرقی', 'ap1' => 'ap1 — آسیا-شرقی', 'us2' => 'us2 — آمریکا', 'us3' => 'us3 — آمریکا'] as $c => $cl)
                            <option value="{{ $c }}" {{ (string) $settings->get('realtime.pusher.cluster', 'mt1') === $c ? 'selected' : '' }}>{{ $cl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="rt-app-key">App Key <span class="text-stone-400 text-[10px]">(عمومی)</span></label>
                    <input id="rt-app-key" data-key="realtime.pusher.app_key" dir="ltr" class="field font-mono !text-xs" autocomplete="off"
                           value="{{ (string) $settings->get('realtime.pusher.app_key') }}" placeholder="مثال: 7a1b2c3d4e5f6g7h8i9j">
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="rt-app-secret">App Secret <span class="text-stone-400 text-[10px]">(محرمانه)</span></label>
                    <input id="rt-app-secret" data-key="realtime.pusher.app_secret" data-empty-skip dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                           placeholder="{{ trim((string) $settings->get('realtime.pusher.app_secret')) !== '' ? '••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'مثال: 4f8b2c1d9e0a...' }}">
                </div>
            </div>

            {{-- هاست سفارشی — سوکتِ خودمیزبانِ سازگار با پروتکل پوشر (اختیاری) --}}
            <div class="st-sub-card mt-3">
                <div class="st-sub-head"><b>هاست سفارشی <span class="text-stone-400 text-[10px]">(اختیاری — برای Soketi/Reverb روی VPS)</span></b></div>
                <p class="st-hint leading-5 mb-2">
                    خالی = سرویس ابری pusher.com. اگر سرور خودتان پروتکل پوشر را صحبت می‌کند (Soketi یا Laravel Reverb)، آدرسش را وارد کنید تا هم REST سرور و هم وب‌سوکت مرورگرها به همان‌جا وصل شوند.
                </p>
                <div class="st-grid-2">
                    <div class="st-field-row !mb-0">
                        <label class="lbl" for="rt-host">Host <span class="text-stone-400 text-[10px]">(بدون پروتکل)</span></label>
                        <input id="rt-host" data-key="realtime.pusher.host" dir="ltr" class="field font-mono !text-xs" autocomplete="off"
                               value="{{ (string) $settings->get('realtime.pusher.host') }}" placeholder="ws.example.ir">
                    </div>
                    <div class="st-grid-2 !gap-2 !mb-0">
                        <div class="st-field-row !mb-0">
                            <label class="lbl" for="rt-port">Port</label>
                            <input id="rt-port" data-key="realtime.pusher.port" data-empty-skip dir="ltr" class="field font-mono !text-xs" inputmode="numeric" autocomplete="off"
                                   value="{{ (string) $settings->get('realtime.pusher.port') }}" placeholder="6001">
                        </div>
                        <div class="st-field-row !mb-0">
                            <label class="lbl" for="rt-scheme">Scheme</label>
                            <select id="rt-scheme" data-key="realtime.pusher.scheme" class="field">
                                @foreach (['https' => 'https/wss (پیش‌فرض)', 'http' => 'http/ws (فقط تست)'] as $s => $sl)
                                    <option value="{{ $s }}" {{ (string) $settings->get('realtime.pusher.scheme', 'https') === $s ? 'selected' : '' }}>{{ $sl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="st-sub-card mt-3">
                <div class="st-sub-head">
                    <b>راهنمای دریافت کلیدها</b>
                </div>
                <p class="st-hint leading-6">
                    ۱) در <b dir="ltr">pusher.com</b> ثبت‌نام کنید و یک App جدید بسازید (پلن رایگان Sandbox کافی است) یا سرور Soketi/Reverb خود را راه بیندازید.
                    ۲) در تب «App Keys» چهار مقدار <span dir="ltr" class="font-mono">app_id · key · secret · cluster</span> را کپی کنید.
                    ۳) مقادیر را اینجا وارد کنید، ذخیره کنید و با دکمهٔ «تست اتصال» صحت آن‌ها را بررسی کنید.
                </p>
            </div>
            </div>{{-- /rt-pusher-zone --}}

            <div class="st-section-foot flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات Realtime</button>
                <button type="button" id="btn-test-pusher" class="btn-ghost ui-press !py-2.5">
                    <span class="size-4 inline-block align-middle me-1" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                    تست اتصال (پس از ذخیره)
                </button>
            </div>
        </form>

        {{-- ================== اعلان‌ها و پوش دستگاه (v25) ================== --}}
        <form data-group="notifications" class="st-section card ui-lift animate-fade-up hidden" id="sec-notifications">
            <div class="st-section-head">
                <span class="st-section-icon" aria-hidden="true" style="background:rgba(37,99,235,.12);color:#2563eb">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">اعلان‌ها — صدا و نوتیف دستگاه</h2>
                    <p class="st-section-desc">صدای اعلان فقط در پنل‌ها (مدیر کل / کافی‌نت / اپراتور / سازمان) پخش می‌شود؛ نوتیف دستگاه روی گوشی (اندروید، ویندوز و iOS-PWA) نمایش داده می‌شود — v38: مدیر از «وضعیت کاربران» تصمیم می‌گیرد نوتیف سیستمی کی برود (پیش‌فرض: آفلاین = همیشه).</p>
                </div>
                <span class="badge {{ $settings->get('notification.sound.enabled') ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200' }}">
                    {{ $settings->get('notification.sound.enabled') ? 'صدا فعال' : 'صدا خاموش' }}
                </span>
            </div>

            {{-- ===== ۱) صدای اعلان پنل‌ها ===== --}}
            <div class="st-sub-card" style="background:linear-gradient(135deg,rgba(37,99,235,.06),transparent)">
                <div class="st-sub-head"><b>۱) صدای اعلان پنل‌ها</b></div>
                <p class="st-hint leading-6">
                    با رسیدن هر اعلان جدید در پنل‌ها، این صدا یک‌بار پخش می‌شود. صدای پیش‌فرض سامانه همیشه موجود است؛
                    اگر تیک «صدای پیش‌فرض» را بردارید، می‌توانید صدای دلخواه خود (mp3/wav/ogg تا ۲ مگابایت) را بارگذاری کنید.
                </p>
            </div>

            <div class="st-switch-row">
                <div>
                    <p class="text-xs font-bold text-stone-700">پخش صدا هنگام اعلان جدید</p>
                    <p class="text-[11px] text-stone-400 mt-0.5">غیرفعال = اعلان‌ها بی‌صدا (فقط بج و پنل اعلان‌ها)</p>
                </div>
                <label class="st-switch" for="ns-enabled">
                    <input type="checkbox" id="ns-enabled" data-key="notification.sound.enabled" class="peer sr-only" {{ $settings->get('notification.sound.enabled') ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="ns-default">استفاده از صدای پیش‌فرض سامانه</label>
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input id="ns-default" data-key="notification.sound.use_default" type="checkbox" class="size-5 accent-amber-600" {{ $settings->get('notification.sound.use_default', '1') ? 'checked' : '' }}>
                    <span class="text-xs font-bold text-stone-700" id="ns-default-label">{{ $settings->get('notification.sound.use_default', '1') ? 'صدای پیش‌فرض فعال است (ding کوتاه)' : 'صدای سفارشی' }}</span>
                </label>
                <p class="st-hint">با برداشتن این تیک، آپلودر صدای سفارشی ظاهر می‌شود؛ با زدن تیک، صدای سفارشی کنار می‌رود.</p>
            </div>

            {{-- آپلودر صدای سفارشی — فقط وقتی تیک پیش‌فرض برداشته شده باشد --}}
            <div id="ns-custom-zone" class="st-sub-card {{ $settings->get('notification.sound.use_default', '1') ? 'hidden' : '' }}">
                <div class="st-sub-head"><b>صدای سفارشی اعلان</b></div>

                <div class="flex flex-wrap items-center gap-3">
                    <input type="file" id="ns-file" accept=".mp3,.wav,.ogg,.m4a" class="hidden">
                    <button type="button" id="ns-upload-btn" class="btn-ghost ui-press !py-2.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                        انتخاب و بارگذاری فایل صدا
                    </button>
                    <button type="button" id="ns-play-btn" class="btn-ghost ui-press !py-2.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                        پخش صدای فعلی
                    </button>
                    @if ($notificationStats['sound_custom'])
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200" id="ns-current-chip"
                              data-name="{{ $notificationStats['sound_custom']['name'] }}" data-url="{{ $notificationStats['sound_custom']['url'] }}">
                            فایل فعلی: {{ $notificationStats['sound_custom']['name'] }}
                        </span>
                    @else
                        <span class="badge bg-stone-100 text-stone-500 border border-stone-200" id="ns-current-chip" data-name="" data-url="">فایل سفارشی ندارید</span>
                    @endif
                    <button type="button" id="ns-delete-btn" class="btn-ghost ui-press !py-2.5 !text-red-600 {{ $notificationStats['sound_custom'] ? '' : 'hidden' }}" title="حذف صدای سفارشی و بازگشت به پیش‌فرض">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        حذف
                    </button>
                </div>
                <p class="st-hint mt-2">فرمت‌های مجاز: mp3 / wav / ogg / m4a — حداکثر ۲ مگابایت. پس از بارگذاری، ذخیره خودکار انجام می‌شود.</p>
            </div>

            {{-- ===== ۲) نوتیف دستگاه (Web Push) ===== --}}
            <div class="st-sub-card" style="background:linear-gradient(135deg,rgba(245,158,11,.05),transparent)">
                <div class="st-sub-head"><b>۲) نوتیف دستگاه — روی گوشی/ویندوز (نوتیف سیستم‌عامل)</b></div>
                <p class="st-hint leading-6">
                    هر اعلانی (پیام چت، تغییر وضعیت سفارش، تیکت و…) علاوه بر زنگ درون‌برنامه‌ای می‌تواند
                    به‌صورت <b>نوتیف سیستم‌عامل</b> روی گوشی (اندروید/iOS با PWA نصب‌شده) و ویندوز هم برسد.
                    <b>v38:</b> شما با «وضعیت کاربران» (زیر) تعیین می‌کنید نوتیف سیستمی کی برود — پیش‌فرض
                    <b>آفلاین</b> است، یعنی همیشه برای همه ارسال می‌شود تا هیچ خبری از دست نرود.
                    سه سرویس قابل انتخاب است — <b>حالت پیش‌فرض بدون هیچ سرویس بیرونی و بدون ثبت‌نام کار می‌کند</b>؛
                    کاربران از زنگ اعلان پنل خود «فعال‌سازی نوتیف دستگاه» را می‌زنند.
                </p>
            </div>


            {{-- ===== v38 — وضعیت آنلاین/آفلاین کاربران (تصمیم مدیر برای نوتیف سیستمی) ===== --}}
            <div class="st-field-row">
                <label class="lbl" id="ns-ust-lbl">وضعیت آنلاین/آفلاین کاربران <span class="text-stone-400 text-[10px]">(نوتیف سیستمی برای چه کسی ارسال شود؟)</span></label>
                <div class="grid gap-2.5 sm:grid-cols-3" role="radiogroup" aria-labelledby="ns-ust-lbl">
                    <label class="ns-ust-card {{ $userStatus === 'offline' ? 'ns-ust-card--on' : '' }}" data-ust="offline">
                        <input type="radio" name="ns-user-status" value="offline" class="sr-only" {{ $userStatus === 'offline' ? 'checked' : '' }}>
                        <span class="ns-ust-radio" aria-hidden="true"></span>
                        <span class="ns-ust-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 10 10H12V2Z" transform="rotate(-90 12 12)"/><path d="M12 12 5 8.5"/></svg>
                        </span>
                        <span class="ns-ust-body">
                            <b>آفلاین <span class="ns-ust-tag">پیش‌فرض</span></b>
                            <i>کاربران آفلاین فرض می‌شوند؛ نوتیف سیستمی همیشه برای همه می‌رود</i>
                        </span>
                    </label>
                    <label class="ns-ust-card {{ $userStatus === 'online' ? 'ns-ust-card--on' : '' }}" data-ust="online">
                        <input type="radio" name="ns-user-status" value="online" class="sr-only" {{ $userStatus === 'online' ? 'checked' : '' }}>
                        <span class="ns-ust-radio" aria-hidden="true"></span>
                        <span class="ns-ust-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 8.82a15 15 0 0 1 20 0"/><path d="M5 12.86a10 10 0 0 1 14 0"/><path d="M8.5 16.43a5 5 0 0 1 7 0"/><path d="M12 20h.01"/></svg>
                        </span>
                        <span class="ns-ust-body">
                            <b>آنلاین</b>
                            <i>کاربران آنلاین فرض می‌شوند؛ نوتیف سیستمی ارسال نمی‌شود</i>
                        </span>
                    </label>
                    <label class="ns-ust-card {{ $userStatus === 'auto' ? 'ns-ust-card--on' : '' }}" data-ust="auto">
                        <input type="radio" name="ns-user-status" value="auto" class="sr-only" {{ $userStatus === 'auto' ? 'checked' : '' }}>
                        <span class="ns-ust-radio" aria-hidden="true"></span>
                        <span class="ns-ust-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M12 8V4"/><path d="M9 4h6"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                        </span>
                        <span class="ns-ust-body">
                            <b>خودکار</b>
                            <i>تشخیص از حضور واقعی هر کاربر — پوش فقط به آفلاین‌ها</i>
                        </span>
                    </label>
                </div>
                <input type="hidden" id="ns-user-status" data-key="notification.push.user_status" value="{{ $userStatus }}">
                <p class="st-hint leading-6" id="ns-ust-desc">
                    @if ($userStatus === 'offline')
                        <b>آفلاین (پیش‌فرض):</b> کاربران آفلاین فرض می‌شوند — نوتیف سیستمی (پوش دستگاه) <b>همیشه و بلافاصله</b> برای همهٔ گیرندگان ارسال می‌شود؛ حتی وقتی برنامه/پنل باز است. مطمئن‌ترین حالت — هیچ خبری از دست نمی‌رود.
                    @elseif ($userStatus === 'online')
                        <b>آنلاین:</b> کاربران آنلاین فرض می‌شوند — نوتیف سیستمی ارسال نمی‌شود؛ فقط زنگ درون‌برنامه‌ای و Realtime پنل. مناسب وقتی مطمئنید کاربران پای پنل/اپ هستند و پوش اضافه نمی‌خواهید.
                    @else
                        <b>خودکار:</b> برای هر کاربر جداگانه از آخرین حضورش (آستانهٔ پایین همین بخش) تشخیص داده می‌شود — کاربر آنلاین فقط زنگ درون‌برنامه‌ای می‌گیرد؛ اگر تا ۱۵ دقیقه بعد آفلاین شد، پوش همان لحظه برایش ارسال می‌شود.
                    @endif
                </p>
            </div>

            <div class="st-field-row">
                <label class="lbl">سرویس نوتیف دستگاه</label>
                <div class="grid gap-2.5 sm:grid-cols-2">
                    <label class="flex items-center gap-2.5 cursor-pointer select-none px-4 py-3 rounded-xl border {{ $notificationStats['push_provider'] === 'off' ? 'border-stone-400 bg-stone-50' : 'border-stone-200' }}">
                        <input type="radio" name="ns-push-provider" value="off" class="accent-stone-600" {{ $notificationStats['push_provider'] === 'off' ? 'checked' : '' }}>
                        <span class="text-xs font-bold text-stone-700">خاموش</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer select-none px-4 py-3 rounded-xl border {{ $notificationStats['push_provider'] === 'default' ? 'border-amber-500 bg-amber-50' : 'border-stone-200' }}">
                        <input type="radio" name="ns-push-provider" value="default" class="accent-amber-600" {{ $notificationStats['push_provider'] === 'default' ? 'checked' : '' }}>
                        <span class="text-xs font-bold text-stone-700">پیش‌فرض <span class="text-stone-400 font-normal">(وب‌پوش داخلی — بدون سرویس بیرونی)</span></span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer select-none px-4 py-3 rounded-xl border {{ $notificationStats['push_provider'] === 'pusher' ? 'border-amber-500 bg-amber-50' : 'border-stone-200' }}">
                        <input type="radio" name="ns-push-provider" value="pusher" class="accent-amber-600" {{ $notificationStats['push_provider'] === 'pusher' ? 'checked' : '' }}>
                        <span class="text-xs font-bold text-stone-700">Pusher Beams <span class="text-stone-400 font-normal">(پوشر — محصول نوتیف دستگاه)</span></span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer select-none px-4 py-3 rounded-xl border {{ $notificationStats['push_provider'] === 'firebase' ? 'border-amber-500 bg-amber-50' : 'border-stone-200' }}">
                        <input type="radio" name="ns-push-provider" value="firebase" class="accent-amber-600" {{ $notificationStats['push_provider'] === 'firebase' ? 'checked' : '' }}>
                        <span class="text-xs font-bold text-stone-700">Firebase <span class="text-stone-400 font-normal">(FCM گوگل)</span></span>
                    </label>
                </div>
                <input type="hidden" id="ns-provider" data-key="notification.push.provider" value="{{ $notificationStats['push_provider'] }}">
                <p class="st-hint">
                    پوشر (Realtime) فقط وقتی صفحه باز است زنگ می‌زند؛ نوتیف سیستم‌عامل نیاز به یکی از این سه سرویس دارد.
                    <b>پیشنهاد: حالت پیش‌فرض</b> — کلیدها خودکار ساخته می‌شوند و پیام‌ها مستقیم از سرور خودتان به دستگاه می‌رسند.
                </p>
            </div>

            {{-- ===== زون «پیش‌فرض» (وب‌پوش داخلی) ===== --}}
            <div id="ns-webpush-zone" class="st-sub-card {{ $notificationStats['push_provider'] === 'default' ? '' : 'hidden' }}" style="background:linear-gradient(135deg,rgba(16,185,129,.05),transparent)">
                <div class="st-sub-head">
                    <b>وب‌پوش داخلی — کلیدهای VAPID</b>
                    @if ($notificationStats['webpush_public'])
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">کلیدها آمادهٔ ارسال ✓</span>
                    @else
                        <span class="badge bg-stone-100 text-stone-500 border border-stone-200">هنوز ساخته نشده (با ذخیره خودکار ساخته می‌شود)</span>
                    @endif
                </div>
                <div class="st-field-row">
                    <label class="lbl" for="ns-vapid-public">کلید عمومی VAPID <span class="text-stone-400 text-[10px]">(فقط-خواندنی — مرورگرها با این کلید اشتراک می‌سازند)</span></label>
                    <div class="flex flex-wrap items-center gap-2">
                        <input id="ns-vapid-public" dir="ltr" class="field font-mono !text-xs flex-1 min-w-[220px]" type="text" readonly
                               value="{{ (string) $notificationStats['webpush_public'] }}" placeholder="با انتخاب «پیش‌فرض» و ذخیره، این‌جا تولید می‌شود">
                        <button type="button" id="ns-copy-vapid" class="btn-ghost ui-press !py-2.5" title="کپی کلید عمومی">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            کپی
                        </button>
                        <button type="button" id="ns-regen-vapid" class="btn-ghost ui-press !py-2.5 !text-red-600" title="ساخت زوج‌کلید جدید — دستگاه‌های فعال باید دوباره فعال شوند">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
                            بازتولید کلیدها
                        </button>
                    </div>
                    <p class="st-hint leading-6">
                        کلید خصوصی روی دیسک خصوصی نگهداری می‌شود و هرگز نمایش داده نمی‌شود. این سرویس مستقیماً با سرویس پوش
                        مرورگر (FCM/Mozilla/اپل) کار می‌کند — <b>بدون واسطه و بدون محدودیت تعداد پیام</b>.
                    </p>
                </div>
            </div>

            {{-- ===== زون «پوشر Beams» ===== --}}
            <div id="ns-pusher-zone" class="st-grid-2 {{ $notificationStats['push_provider'] === 'pusher' ? '' : 'hidden' }}">
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="beams-instance">Beams Instance ID</label>
                    <input id="beams-instance" data-key="notification.push.beams.instance_id" dir="ltr" class="field font-mono !text-xs" autocomplete="off"
                           value="{{ (string) $settings->get('notification.push.beams.instance_id') }}" placeholder="مثال: 1a2b3c4d-…">
                    <p class="st-hint">در پنل پوشر: تب <span dir="ltr">Beams → Settings → Credentials</span></p>
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="beams-key">Beams Primary Key <span class="text-stone-400 text-[10px]">(محرم)</span></label>
                    <input id="beams-key" data-key="notification.push.beams.primary_key" dir="ltr" class="field font-mono !text-xs" type="password" autocomplete="off"
                           placeholder="{{ trim((string) $settings->get('notification.push.beams.primary_key')) !== '' ? '••••••• (ذخیره‌شده — برای تغییر وارد کنید)' : 'مثال: 9C4F5E6A…' }}">
                </div>
            </div>

            {{-- فیلدهای فایربیس — فقط وقتی firebase انتخاب شده --}}
            <div id="ns-firebase-zone" class="st-grid-2 {{ $notificationStats['push_provider'] === 'firebase' ? '' : 'hidden' }}">
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="fb-project">Project ID</label>
                    <input id="fb-project" data-key="notification.push.firebase.project_id" dir="ltr" class="field font-mono !text-xs" autocomplete="off"
                           value="{{ (string) $settings->get('notification.push.firebase.project_id') }}" placeholder="مثال: caffeinet-ir">
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="fb-sender">Messaging Sender ID <span class="text-stone-400 text-[10px]">(عمومی)</span></label>
                    <input id="fb-sender" data-key="notification.push.firebase.sender_id" dir="ltr" class="field font-mono !text-xs" autocomplete="off"
                           value="{{ (string) $settings->get('notification.push.firebase.sender_id') }}" placeholder="مثال: 123456789012">
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="fb-api">Web API Key <span class="text-stone-400 text-[10px]">(عمومی)</span></label>
                    <input id="fb-api" data-key="notification.push.firebase.api_key" dir="ltr" class="field font-mono !text-xs" autocomplete="off"
                           value="{{ (string) $settings->get('notification.push.firebase.api_key') }}" placeholder="AIza…">
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="fb-app">Web App ID <span class="text-stone-400 text-[10px]">(عمومی)</span></label>
                    <input id="fb-app" data-key="notification.push.firebase.app_id" dir="ltr" class="field font-mono !text-xs" autocomplete="off"
                           value="{{ (string) $settings->get('notification.push.firebase.app_id') }}" placeholder="1:1234:web:abcd…">
                </div>
            </div>

            {{-- آستانهٔ آفلاین — مشترک بین هر سه سرویس (v37: فقط نمایش حضور؛ ارسال پوش همیشه است) --}}
            <div class="st-switch-row {{ in_array($notificationStats['push_provider'], ['default', 'pusher', 'firebase'], true) ? '' : 'hidden' }}" id="ns-offline-row">
                <div>
                    <p class="text-xs font-bold text-stone-700">آستانهٔ «آفلاین» <span class="text-stone-400 font-normal">(حساسیت تشخیص حضور)</span></p>
                    <p class="text-[11px] text-stone-400 mt-0.5 leading-5" id="ns-offline-desc">
                        @if ($offlineEnabled)
                            کاربرِ بدونِ درخواستِ بیشتر از <b>{{ fa_number($offlineSeconds) }}</b> ثانیه «آفلاین» نشان داده می‌شود (جزئیات کاربران، داشبورد، سربرگ چت). در حالت «خودکار» همین آستانه تعیین می‌کند نوتیف سیستمی برای چه کسی برود؛ در حالت «آفلاین/آنلاین» فقط نمایش حضور است. بستن برنامه معمولاً همان لحظه (بیکن pagehide) یا حداکثر تا همین مدت بعد، وضعیت را آفلاین می‌کند.
                        @else
                            <b>کوتاه (۴۵ ثانیه):</b> برنامهٔ بسته حداکثر تا ۴۵ ثانیه بعد «آفلاین» نمایش داده می‌شود — در حالت «خودکار» نوتیف سیستمی پس از همین مدت برایش ارسال می‌شود.
                        @endif
                    </p>
                </div>
                <label class="st-switch" for="ns-offline-enabled">
                    <input type="checkbox" id="ns-offline-enabled" data-key="notification.push.offline_enabled" class="peer sr-only" {{ $offlineEnabled ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>
            <div class="st-field-row {{ ($offlineEnabled && in_array($notificationStats['push_provider'], ['default', 'pusher', 'firebase'], true)) ? '' : 'hidden' }}" id="ns-offline-seconds-row">
                <label class="lbl" for="fb-offline-sec">مدت آستانه (ثانیه)</label>
                <input id="fb-offline-sec" data-key="notification.push.offline_seconds" type="number" min="45" max="86400" class="field" dir="ltr"
                       value="{{ $offlineSeconds }}">
                <p class="st-hint">پیشنهادی ۴۵ تا ۱۲۰ ثانیه؛ حداقل ۴۵ — تعیین می‌کند بعد از چند ثانیه بی‌فعالیتی، کاربر «آفلاین» تلقی شود (نمایش حضور در داشبورد/جزئیات + معیار ارسال پوش در حالت «خودکار»)</p>
            </div>

            {{-- Service Account فایربیس — فقط وقتی firebase انتخاب شده --}}
            <div id="ns-credentials-zone" class="st-sub-card {{ $notificationStats['push_provider'] === 'firebase' ? '' : 'hidden' }}">
                <div class="st-sub-head">
                    <b>Service Account فایربیس (کلید ارسال سرور)</b>
                    @if ($notificationStats['push_enabled'] && $notificationStats['push_provider'] === 'firebase')
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">ذخیره‌شده ✓</span>
                    @else
                        <span class="badge bg-stone-100 text-stone-500 border border-stone-200" id="fb-cred-chip">بارگذاری‌نشده</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="file" id="fb-cred-file" accept=".json,application/json" class="hidden">
                    <button type="button" id="fb-cred-upload-btn" class="btn-ghost ui-press !py-2.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                        بارگذاری فایل Service Account
                    </button>
                    <button type="button" id="fb-cred-delete-btn" class="btn-ghost ui-press !py-2.5 !text-red-600 {{ ($notificationStats['push_enabled'] && $notificationStats['push_provider'] === 'firebase') ? '' : 'hidden' }}">حذف کلید</button>
                    <span class="badge bg-stone-100 text-stone-500 border border-stone-200">
                        {{ fa_number($notificationStats['push_tokens']) }} دستگاه ثبت‌شده
                    </span>
                </div>
                <p class="st-hint mt-2 leading-6">
                    از کنسول فایربیس: <span dir="ltr">Project settings → Service accounts → Generate new private key</span> —
                    فایل JSON دانلود‌شده را این‌جا بارگذاری کنید (روی دیسک خصوصی و دور از دسترس وب ذخیره می‌شود).
                    چهار فیلد بالا هم از <span dir="ltr">Project settings → General</span> قابل کپی است.
                </p>
            </div>

            <div class="st-section-foot flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات اعلان‌ها</button>
                <button type="button" id="btn-test-push" class="btn-ghost ui-press !py-2.5" title="ارسال واقعی از سرویس فعال به دستگاه‌های ثبت‌شدهٔ حساب شما (پس از ذخیره)">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4Z"/></svg>
                    ارسال تست به دستگاه‌های من
                </button>
                <button type="button" id="btn-preview-push" class="btn-ghost ui-press !py-2.5" title="نمایش یک نوتیف آزمایشی روی همین سیستم‌عامل (بدون ارسال از سرور)">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    پیش‌نمایش نوتیف دستگاه
                </button>
            </div>
        </form>

        <form id="sec-referral" class="st-section card ui-lift animate-fade-up hidden" data-referral>
            <div class="st-section-head">
                <span class="st-section-icon st-section-icon--referral" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">پاداش معرفی کافی‌نت</h2>
                    <p class="st-section-desc">پاداش اولیه هنگام تأیید کافی‌نتِ معرفی‌شده و پاداش هر سفارش هنگام تسویهٔ آن، به‌صورت خودکار به کیف پول سازمان واریز می‌شود.</p>
                </div>
                @if ($referral->is_active)
                    <span id="r-badge" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">فعال</span>
                @else
                    <span id="r-badge" class="badge bg-stone-100 text-stone-500 border border-stone-200">غیرفعال</span>
                @endif
            </div>

            <div class="st-switch-row">
                <div>
                    <p class="text-xs font-bold text-stone-700">فعال بودن سیستم پاداش</p>
                    <p class="text-[11px] text-stone-400 mt-0.5">در صورت غیرفعالی، هیچ پاداشی پرداخت نمی‌شود</p>
                </div>
                <label class="st-switch" for="r-active">
                    <input type="checkbox" id="r-active" class="peer sr-only" {{ $referral->is_active ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="r-reward">پاداش اولیهٔ معرفی (تومان)</label>
                <input id="r-reward" type="number" min="0" step="1000" class="field" dir="ltr"
                       value="{{ (float) $referral->introduction_reward }}">
                <p class="st-hint">۰ = بدون پاداش اولیه — هنگام تأیید هر کافی‌نتِ معرفی‌شده واریز می‌شود</p>
            </div>

            <div class="st-grid-2">
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="r-per-type">پاداش از هر سفارش (نوع)</label>
                    <select id="r-per-type" class="field">
                        @foreach (['percent' => 'درصدی از کمیسیون سازمان', 'fixed' => 'مبلغ ثابت (تومان)'] as $value => $label)
                            <option value="{{ $value }}" {{ $referral->per_order_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-field-row !mb-0">
                    <label class="lbl" for="r-per-value">مقدار</label>
                    <input id="r-per-value" type="number" min="0" step="any" class="field" dir="ltr"
                           value="{{ (float) $referral->per_order_value }}">
                    <p class="st-hint">درصد (۰ تا ۱۰۰) از کمیسیون سازمان یا مبلغ ثابت به تومان — هنگام تسویهٔ هر سفارش واریز می‌شود</p>
                </div>
            </div>

            <div class="st-section-foot">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ پاداش معرفی</button>
            </div>
        </form>

        {{-- ================== ظاهر و رنگ‌بندی (Appearance) ==================
             هر پنل پالت مستقل خودش را دارد: «پیش‌فرض» + ۱۰ پالت آماده + «شخصی‌سازی»
        {{-- ================== ظاهر و رنگ‌بندی (Appearance) ==================
             هر پنل پالت مستقل خودش را دارد: «پیش‌فرض» + ۱۰ پالت آماده + «شخصی‌سازی»
             (کنترل کامل: طیف برند، پس‌زمینه، سایدبار، رنگ معنایی، دمای سرد/گرم).
             همهٔ داده‌ها از PHP می‌آید (settingsViewData) — منطق: settings/index.js --}}
        <section class="st-section card ui-lift animate-fade-up hidden" id="sec-appearance" data-appearance>
            <div class="st-section-head">
                <div>
                    <h2 class="st-section-title">ظاهر و رنگ‌بندی</h2>
                    <p class="st-section-desc">هر پنل می‌تواند پالت رنگی مستقل خودش را داشته باشد — با کلیک روی هر پالت، همان لحظه پیش‌نمایش زنده را ببینید و سپس ذخیره کنید.</p>
                </div>
            </div>

            {{-- انتخاب پنل --}}
            <div class="ap-panels" role="tablist" aria-label="انتخاب پنل">
                @foreach ($appearance['panels'] as $apKey => $apLabel)
                    <button type="button" role="tab" class="ap-panel-chip" data-ap-panel="{{ $apKey }}">
                        {{ $apLabel }}
                        <small data-ap-panel-badge></small>
                    </button>
                @endforeach
            </div>

            {{-- شبکهٔ پالت‌ها — سواچ‌ها از پیش ساخته‌شده --}}
            <div class="ap-palette-grid" data-ap-grid role="listbox" aria-label="انتخاب پالت رنگی">
                @foreach ($appearance['cards'] as $palKey => $card)
                    <button type="button" role="option" class="ap-palette" data-ap-palette="{{ $palKey }}" title="{{ $card['hint'] }}">
                        <span class="ap-swatch" style="background: {{ $card['grad'] }}" aria-hidden="true"></span>
                        <span class="ap-pal-name">{{ $card['name'] }}</span>
                        <span class="ap-pal-hint">{{ $card['hint'] }}</span>
                        <span class="ap-pal-check" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                    </button>
                @endforeach
            </div>

            {{-- دمای سرد/گرم — برای «همهٔ» پالت‌های انتخابی (در شخصی‌سازی، دمای اختصاصی ویرایشگر استفاده می‌شود) --}}
            <div class="ap-warmth hidden" data-ap-warmth>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <b class="text-sm font-extrabold text-stone-800">دمای رنگ پالت</b>
                        <p class="st-hint mt-0.5">میزان سردی یا گرمی رنگ‌های همین پالت را جداگانه تنظیم کنید — پیش‌نمایش همان لحظه اعمال می‌شود.</p>
                    </div>
                    <span class="badge bg-stone-100 text-stone-600 border border-stone-200" data-ap-warmth-label>خنثی</span>
                </div>
                <div class="ap-temp-row mt-3">
                    <span class="text-[10px] font-bold text-sky-600">سرد</span>
                    <input type="range" id="ap-palette-warmth" min="-40" max="40" step="1" value="0" class="ap-temp flex-1" aria-label="میزان سردی و گرمی رنگ">
                    <span class="text-[10px] font-bold text-amber-600">گرم</span>
                    <button type="button" class="btn-ghost !py-1.5 !px-2.5 text-[11px]" data-ap-warmth-reset>خنثی‌سازی</button>
                </div>
            </div>

            {{-- ویرایشگر شخصی‌سازی (فقط برای پالت custom) --}}
            <div class="ap-custom hidden" data-ap-custom>
                <div class="ap-custom-head">
                    <div>
                        <b class="text-sm font-extrabold text-stone-800">شخصی‌سازی کامل رنگ‌ها</b>
                        <p class="st-hint mt-0.5">رنگ پایه را انتخاب کنید و دمای سرد/گرم را تنظیم کنید تا کل طیف ساخته شود؛ یا هر سایه را جداگانه تغییر دهید. همهٔ بخش‌ها همین‌جا کنترل‌پذیرند.</p>
                    </div>
                    <button type="button" class="btn-ghost !py-2 !px-3 text-xs" data-ap-from-current>شروع از پالت فعلی</button>
                </div>

                <div class="ap-custom-grid">
                    <div class="ap-cfield">
                        <label class="lbl" for="ap-base">رنگ پایه برند</label>
                        <div class="ap-color-row">
                            <input type="color" id="ap-base" value="#2563eb" class="ap-color">
                            <input type="text" id="ap-base-hex" dir="ltr" class="field !py-1.5 font-mono text-xs" value="#2563eb" maxlength="7">
                        </div>
                    </div>
                    <div class="ap-cfield">
                        <label class="lbl" for="ap-temp">دمای رنگ — <span data-ap-temp-label>خنثی</span></label>
                        <div class="ap-temp-row">
                            <span class="text-[10px] font-bold text-sky-600">سرد</span>
                            <input type="range" id="ap-temp" min="-40" max="40" step="1" value="0" class="ap-temp flex-1">
                            <span class="text-[10px] font-bold text-amber-600">گرم</span>
                        </div>
                    </div>
                </div>

                <div class="ap-cfield mt-4">
                    <label class="lbl">طیف برند (۵۰ تا ۹۵۰) — قابل ویرایش تک‌تک</label>
                    <div class="ap-shades" data-ap-shades>
                        @foreach ($appearance['shades'] as $shade)
                            <label class="ap-shade" title="سایهٔ {{ $shade['key'] }}">
                                <input type="color" value="{{ $shade['hex'] }}" data-ap-shade="{{ $shade['key'] }}">
                                <span dir="ltr">{{ $shade['key'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="ap-custom-grid mt-4">
                    <div class="ap-cfield">
                        <label class="lbl" for="ap-pagebg">پس‌زمینهٔ صفحه (تم روشن)</label>
                        <div class="ap-color-row">
                            <input type="color" id="ap-pagebg" value="#dbeafe" class="ap-color">
                            <input type="text" id="ap-pagebg-hex" dir="ltr" class="field !py-1.5 font-mono text-xs" value="#dbeafe" maxlength="7">
                        </div>
                    </div>
                    <div class="ap-cfield">
                        <label class="lbl">رنگ معنایی (موفقیت / هشدار / خطا / اطلاع)</label>
                        <div class="ap-semantic">
                            <label class="ap-shade" title="موفقیت"><input type="color" value="#10b981" data-ap-sem="ok"><span>موفقیت</span></label>
                            <label class="ap-shade" title="هشدار"><input type="color" value="#f59e0b" data-ap-sem="warn"><span>هشدار</span></label>
                            <label class="ap-shade" title="خطا"><input type="color" value="#ef4444" data-ap-sem="err"><span>خطا</span></label>
                            <label class="ap-shade" title="اطلاع"><input type="color" value="#0ea5e9" data-ap-sem="info"><span>اطلاع</span></label>
                        </div>
                    </div>
                </div>

                <div class="ap-cfield mt-4">
                    <label class="lbl">سایدبار (۴ ایست گرادیان + متن) — سایدبار همیشه تیره است</label>
                    <div class="ap-shades">
                        <label class="ap-shade" title="ایست ۱"><input type="color" value="#172f6d" data-ap-sb="0"><span>ایست ۱</span></label>
                        <label class="ap-shade" title="ایست ۲"><input type="color" value="#1d3e8d" data-ap-sb="1"><span>ایست ۲</span></label>
                        <label class="ap-shade" title="ایست ۳"><input type="color" value="#14295f" data-ap-sb="2"><span>ایست ۳</span></label>
                        <label class="ap-shade" title="ایست ۴"><input type="color" value="#0d1d4a" data-ap-sb="3"><span>ایست ۴</span></label>
                        <label class="ap-shade" title="متن سایدبار"><input type="color" value="#dbeafe" data-ap-sbtext><span>متن</span></label>
                    </div>
                </div>
            </div>

            {{-- دادهٔ پالت‌ها برای اسکریپت (بدون درخواست اضافه) --}}
            <script type="application/json" id="ap-data">{!! $appearance['json'] !!}</script>

            {{-- نوار عملیات --}}
            <div class="ap-actions">
                <p class="st-hint !mt-0" data-ap-status>پالت فعال این پنل: <b>{{ $appearance['selected_name'] }}</b></p>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-ghost !py-2 !px-3 text-xs" data-ap-revert>بازگشت به ذخیره‌شده</button>
                    <button type="button" class="btn-primary btn-shine ui-press !py-2.5 px-7" data-ap-save>
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                        ذخیرهٔ پوسته
                    </button>
                </div>
            </div>
        </section>

        {{-- ================== v43 — آپلود و فشرده‌سازی (بازطراحی مدرن) ==================
             v44 — فیکس چیدمان: این فرم تا حالا «بیرون» از st-sections/st-layout رندر
             می‌شد (دو </div> زودهنگام بالای آن) و به همین دلیل زیر تب‌ها و تمام‌عرض
             می‌افتاد. حالا داخل ستون سکشن‌هاست تا کنار تب‌ها (ستون ۱fr گرید) بنشیند
             + کلاس hidden اولیه مثل بقیهٔ سکشن‌ها. --}}
        <form data-group="uploads" class="st-section card ui-lift animate-fade-up hidden" id="sec-uploads">
            <div class="st-section-head">
                <span class="st-section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M20 16v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">آپلود و فشرده‌سازی</h2>
                    <p class="st-section-desc">این تنظیمات روی «همهٔ» بخش‌های آپلود اعمال می‌شود — چت سفارش‌ها، تیکت‌ها، فرم سفارش، خدمات، اطلاعیه‌ها و فایل‌های API. لایهٔ اول همان لحظه در مرورگر کاربر فشرده می‌کند (آپلود سریع‌تر) و لایهٔ دوم به‌تدریج و دسته‌کوچک روی سرور — بدون فشار به CPU هاست.</p>
                </div>
            </div>

            {{-- ① کارت وضعیت (کلید اصلی + خلاصهٔ زنده) --}}
            <div class="up2-hero {{ $settings->get('uploads.compression.enabled', '1') ? '' : 'is-off' }}">
                <span class="up2-hero-tile" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/><path d="M18 3h3v3"/></svg>
                </span>
                <div class="up2-hero-body">
                    <p class="up2-hero-title">فشرده‌سازی هوشمند آپلودها</p>
                    <p class="up2-hero-sub">اگر خاموش کنید هیچ فایلی دست نمی‌خورد و همه‌چیز اصل آپلود می‌شود.</p>
                    <div class="up2-chips" aria-label="خلاصهٔ تنظیمات فعلی">
                        <span class="up2-chip">حالت: <b id="up2-sum-mode">—</b></span>
                        <span class="up2-chip">فرمت: <b id="up2-sum-fmt">—</b></span>
                        <span class="up2-chip up2-chip-dim" id="up2-chip-dim">ابعاد/کیفیت: <b id="up2-sum-dim">—</b></span>
                    </div>
                </div>
                <label class="st-switch" for="up-enabled">
                    <input type="checkbox" id="up-enabled" data-key="uploads.compression.enabled" value="1" class="sr-only" {{ $settings->get('uploads.compression.enabled', '1') ? 'checked' : '' }}>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            {{-- ② حالت فشرده‌سازی --}}
            <div class="up2-block">
                <p class="up2-label">حالت فشرده‌سازی تصاویر</p>
                <div class="up2-presets" id="up-presets" role="radiogroup" aria-label="حالت فشرده‌سازی">
                    @foreach ([
                        'lossless' => ['بدون افت کیفیت', 'کیفیت ۹۲٪ — فقط فشرده‌سازی امن؛ عکس دست‌نخورده می‌ماند (پیش‌فرض)', '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>'],
                        'balanced' => ['متعادل', 'کیفیت ۸۵٪ + حداکثر ضلع ۲۰۴۸ — توصیه‌شده برای حجم کمتر', '<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
                        'max' => ['حداکثر فشرده‌سازی', 'کیفیت ۷۸٪ + حداکثر ضلع ۱۶۰۰ — کمترین حجم', '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>'],
                        'custom' => ['سفارشی', 'تنظیم دستی کیفیت و ابعاد', '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>'],
                    ] as $key => $preset)
                        <label class="up2-preset {{ $settings->get('uploads.compression.preset', 'lossless') === $key ? 'on' : '' }}" data-preset="{{ $key }}">
                            <input type="radio" name="up-preset" data-key="uploads.compression.preset" data-label="{{ $preset[0] }}" value="{{ $key }}" class="sr-only" {{ $settings->get('uploads.compression.preset', 'lossless') === $key ? 'checked' : '' }}>
                            <span class="up2-preset-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $preset[2] !!}</svg></span>
                            <span class="up2-preset-body">
                                <span class="up2-preset-name">{{ $preset[0] }}</span>
                                <span class="up2-preset-desc">{{ $preset[1] }}</span>
                            </span>
                            <span class="up2-preset-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        </label>
                    @endforeach
                </div>
                <p class="st-hint">هر تصویر فقط وقتی جایگزین می‌شود که خروجی «واقعاً کوچک‌تر» شود؛ در غیر این صورت فایل اصلی حفظ می‌شود.</p>
            </div>

            {{-- ③ فرمت پیش‌فرض (سراسری — همهٔ بخش‌های آپلود) --}}
            <div class="up2-block">
                <p class="up2-label">فرمت پیش‌فرض فایل‌های آپلودی</p>
                <p class="up2-label-sub">هر تصویری که از هر بخشی آپلود می‌شود به این فرمت تبدیل می‌شود — شفافیت حفظ می‌شود و اگر انکودری روی مرورگر/سرور نبود، خودکار بهترین جایگزین انتخاب می‌گردد.</p>
                <div class="up2-formats" id="up-formats" role="radiogroup" aria-label="فرمت پیش‌فرض">
                    @foreach ([
                        'keep' => ['اصل', 'SAME', 'هیچ تبدیلی انجام نمی‌شود'],
                        'auto' => ['هوشمند', 'AUTO', 'خودش کوچک‌ترین را انتخاب می‌کند (پیش‌فرض)'],
                        'jpeg' => ['JPEG', 'JPG', 'سازگاری کامل — برای عکس‌های معمولی'],
                        'png' => ['PNG', 'PNG', 'شفافیت کامل — حجم بیشتر'],
                        'webp' => ['WebP', 'WEBP', 'بهترین تعادل حجم/کیفیت — پیشنهادی'],
                        'gif' => ['GIF', 'GIF', 'سازگاری قدیمی — بدون شفافیت نرم'],
                        'avif' => ['AVIF', 'AVIF', 'نوترین نسل — در نبود پشتیبانی → WebP'],
                    ] as $key => $fmt)
                        <label class="up2-fmt {{ $settings->get('uploads.compression.images.format', 'auto') === $key ? 'on' : '' }}">
                            <input type="radio" name="up-format" data-key="uploads.compression.images.format" value="{{ $key }}" class="sr-only" {{ $settings->get('uploads.compression.images.format', 'auto') === $key ? 'checked' : '' }}>
                            <span class="up2-fmt-badge" data-fmt="{{ $key }}">{{ $fmt[1] }}</span>
                            <span class="up2-fmt-body">
                                <span class="up2-fmt-name">{{ $fmt[0] }}</span>
                                <span class="up2-fmt-desc">{{ $fmt[2] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- ④ تنظیمات دستی (فقط حالت سفارشی) --}}
            <div class="up2-block up2-soft {{ $settings->get('uploads.compression.preset', 'lossless') !== 'custom' ? 'is-locked' : '' }}" id="up-custom-box">
                <p class="up2-label">تنظیمات دستی تصویر <span class="up2-only-custom">(فقط در حالت «سفارشی»)</span></p>
                <div class="up2-grid2">
                    <div>
                        <label class="text-[11px] text-stone-500 mb-1 block" for="up-max-side">حداکثر ضلع (px)</label>
                        <input id="up-max-side" type="number" min="400" max="8000" data-key="uploads.compression.images.max_side" class="field" value="{{ $settings->get('uploads.compression.images.max_side', '4096') }}">
                    </div>
                    <div>
                        <label class="text-[11px] text-stone-500 mb-1 block" for="up-quality">کیفیت (۵۰–۱۰۰)</label>
                        <input id="up-quality" type="number" min="50" max="100" data-key="uploads.compression.images.quality" class="field" value="{{ $settings->get('uploads.compression.images.quality', '92') }}">
                    </div>
                </div>
            </div>

            {{-- ⑤ ویدیوها --}}
            <div class="up2-block">
                <div class="up2-rowline">
                    <div>
                        <p class="up2-label !mb-0">فشرده‌سازی ویدیوها</p>
                        <p class="up2-label-sub">
                            @if ($uploadCfg['videos']['ffmpeg'])
                                <span class="up2-pill up2-pill--ok">ffmpeg فعال</span>
                                تبدیل به H.264 سبک — سازگار با همهٔ گوشی‌ها؛ در هر اجرای کرون فقط «یک» ویدیو.
                            @else
                                <span class="up2-pill">ffmpeg یافت نشد</span>
                                تا نصب نشود ویدیوها دست‌نخورده می‌مانند (هیچ فشاری به سرور نمی‌آید).
                            @endif
                        </p>
                    </div>
                    <label class="st-switch" for="up-video-enabled">
                        <input type="checkbox" id="up-video-enabled" data-key="uploads.compression.videos.enabled" value="1" class="sr-only" {{ $settings->get('uploads.compression.videos.enabled', '0') ? 'checked' : '' }}>
                        <span class="st-switch-track" aria-hidden="true"></span>
                    </label>
                </div>
                <div class="up2-soft up2-mt3 {{ $settings->get('uploads.compression.videos.enabled', '0') ? '' : 'is-locked' }}" id="up2-crf-row">
                    <label class="text-[11px] text-stone-500 mb-1 block" for="up-crf">سطح فشرده‌سازی ویدیو (CRF) — عدد کمتر = کیفیت بهتر (۱۸ بهترین، ۳۴ سبک‌ترین)</label>
                    <input id="up-crf" type="number" min="18" max="34" data-key="uploads.compression.videos.crf" class="field" value="{{ $settings->get('uploads.compression.videos.crf', '28') }}">
                </div>
            </div>

            {{-- ⑥ خط‌لولهٔ سرور (بدون فشار به هاست) --}}
            <div class="up2-block">
                <p class="up2-label">خط‌لولهٔ پردازش روی سرور</p>
                <div class="up2-flow" aria-hidden="true">
                    <span class="up2-step"><i>۱</i>آپلود کاربر</span>
                    <span class="up2-arrow"></span>
                    <span class="up2-step up2-step--hi"><i>۲</i>فشرده‌سازی در مرورگر</span>
                    <span class="up2-arrow"></span>
                    <span class="up2-step"><i>۳</i>ذخیرهٔ امن</span>
                    <span class="up2-arrow"></span>
                    <span class="up2-step"><i>۴</i>صف سرور — دسته‌کوچک</span>
                </div>
                <div class="up2-mt3">
                    <label class="text-[11px] text-stone-500 mb-1 block" for="up-batch">تعداد تصویر در هر اجرای خودکار (هر دقیقه)</label>
                    <input id="up-batch" type="number" min="1" max="50" data-key="uploads.compression.server_batch" class="field" value="{{ $settings->get('uploads.compression.server_batch', '8') }}">
                    <p class="st-hint">در هر اجرای کرون فقط همین تعداد فایل فشرده می‌شود تا CPU هاست سرِ بازی نماند — پیش‌فرض ۸. فایل‌های رمزنگاری‌شدهٔ چت/تیکت/سفارش هم پشتیبانی می‌شوند.</p>
                </div>
            </div>

            <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ تنظیمات فشرده‌سازی</button>
        </form>

        {{-- ---------- قابلیت‌ها و امنیت (فاز ۵۰) ---------- --}}
        <form data-group="features" class="st-section card ui-lift animate-fade-up hidden" id="sec-features">
            <div class="st-section-head">
                <span class="st-section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="st-section-title">قابلیت‌ها و امنیت</h2>
                    <p class="text-[11px] text-stone-400 mt-0.5">هر زیرسیستم را از اینجا روشن/خاموش کنید — خاموش بودن یعنی آن بخش کلا از سایت و پنل‌ها حذف می‌شود.</p>
                </div>
            </div>

            {{-- ===== زیرسیستم‌های اصلی ===== --}}
            <div class="st-field-row">
                <p class="text-xs font-extrabold text-stone-700 border-b border-stone-100 pb-2 w-full">زیرسیستم‌های اصلی</p>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">وضعیت زندهٔ کافی‌نت‌ها (/status)</p><p class="text-[11px] text-stone-400 mt-0.5">صفحهٔ عمومی با وضعیت باز/بسته بودن هر کافی‌نت، امتیاز و صف — لینک آن در لندینگ هم می‌آید.</p></div>
                <label class="st-switch" for="f-status-page">
                    <input type="checkbox" id="f-status-page" data-key="features.status_page" class="peer sr-only" @checked((bool) $settings->get('features.status_page', true))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">پخش هوشمند سفارش</p><p class="text-[11px] text-stone-400 mt-0.5">اولویت پخش بر اساس امتیاز رأی مشتری‌ها + آمار پذیرش هر کافی‌نت (پذیرش سریع‌تر = امتیاز بیشتر).</p></div>
                <label class="st-switch" for="f-smart-dispatch">
                    <input type="checkbox" id="f-smart-dispatch" data-key="features.smart_dispatch" class="peer sr-only" @checked((bool) $settings->get('features.smart_dispatch', true))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">حالت فروشنده (برند مشترک)</p><p class="text-[11px] text-stone-400 mt-0.5">سازمان‌های فروشنده با لینک اختصاصی برندشان روی لندینگ را می‌بینند و از خرید مشتریان معرفی‌شده کمیسیون می‌گیرند.</p></div>
                <label class="st-switch" for="f-reseller">
                    <input type="checkbox" id="f-reseller" data-key="features.reseller" class="peer sr-only" @checked((bool) $settings->get('features.reseller', false))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">حساب خانواده/تیمی</p><p class="text-[11px] text-stone-400 mt-0.5">حساب اصلی می‌تواند زیرحساب بسازد؛ همه از کیف پول اصلی خرج می‌کنند و می‌شود برای هر زیرحساب سقف خرج گذاشت.</p></div>
                <label class="st-switch" for="f-family">
                    <input type="checkbox" id="f-family" data-key="features.family_accounts" class="peer sr-only" @checked((bool) $settings->get('features.family_accounts', false))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">وب‌هوک‌ها</p><p class="text-[11px] text-stone-400 mt-0.5">رویدادهای سیستم (سفارش جدید، تحویل، …) به آدرس وب‌هوک شما POST می‌شود — مدیریت از صفحهٔ «کلیدهای وب‌سرویس».</p></div>
                <label class="st-switch" for="f-webhooks">
                    <input type="checkbox" id="f-webhooks" data-key="features.webhooks" class="peer sr-only" @checked((bool) $settings->get('features.webhooks', false))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">ثبت مصرف API</p><p class="text-[11px] text-stone-400 mt-0.5">هر درخواست API ثبت می‌شود تا در صفحهٔ کلیدها ببینید چه کاربری با چه دستگاهی وصل شده — خاموش یعنی فقط شمارندهٔ کلی.</p></div>
                <label class="st-switch" for="f-api-log">
                    <input type="checkbox" id="f-api-log" data-key="features.api_usage_log" class="peer sr-only" @checked((bool) $settings->get('features.api_usage_log', true))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">داشبورد مصرف مشتری</p><p class="text-[11px] text-stone-400 mt-0.5">صفحهٔ «آمار من» در اپ مشتری: نمودار هزینهٔ ۶ ماه، تعداد سفارش و خدمات پرتکرار.</p></div>
                <label class="st-switch" for="f-cust-stats">
                    <input type="checkbox" id="f-cust-stats" data-key="features.customer_stats" class="peer sr-only" @checked((bool) $settings->get('features.customer_stats', true))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">جستجوی سراسری پنل ادمین</p><p class="text-[11px] text-stone-400 mt-0.5">با Ctrl+K (یا ⌘+K) سفارش، مشتری، خدمت و صفحه‌ها را همان‌جا جستجو کنید.</p></div>
                <label class="st-switch" for="f-ctrlk">
                    <input type="checkbox" id="f-ctrlk" data-key="features.global_search" class="peer sr-only" @checked((bool) $settings->get('features.global_search', true))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">چندزبانه</p><p class="text-[11px] text-stone-400 mt-0.5">خاموش = سایت فقط فارسی. روشن = سوئیچ زبان (فارسی/انگلیسی) در پنل ادمین و اپ مشتری فعال می‌شود.</p></div>
                <label class="st-switch" for="f-i18n">
                    <input type="checkbox" id="f-i18n" data-key="features.i18n" class="peer sr-only" @checked((bool) $settings->get('features.i18n', false))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">صفحهٔ سلامت سیستم</p><p class="text-[11px] text-stone-400 mt-0.5">صفحهٔ «سلامت سیستم» در تنظیمات: دیسک، کرون، صف پیامک، سفارش‌های گیرکرده و…</p></div>
                <label class="st-switch" for="f-health">
                    <input type="checkbox" id="f-health" data-key="features.health_page" class="peer sr-only" @checked((bool) $settings->get('features.health_page', true))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">ارسال دوبارهٔ خودکار پیامک</p><p class="text-[11px] text-stone-400 mt-0.5">پیامک‌های ناموفق تا ۳ بار (هر بار با ۵ دقیقه فاصله) دوباره ارسال می‌شوند.</p></div>
                <label class="st-switch" for="f-sms-retry">
                    <input type="checkbox" id="f-sms-retry" data-key="features.sms_retry" class="peer sr-only" @checked((bool) $settings->get('features.sms_retry', true))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            {{-- ===== تعهد زمان تحویل (SLA) ===== --}}
            <div class="st-field-row">
                <p class="text-xs font-extrabold text-stone-700 border-b border-stone-100 pb-2 w-full">تعهد زمان تحویل (SLA)</p>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">تعهد زمان تحویل با تایمر زنده</p><p class="text-[11px] text-stone-400 mt-0.5">در سفارش مشتری شمارش معکوس «تحویل تا …» نمایش داده می‌شود و دیرکرد در پنل ادمین علامت می‌خورد.</p></div>
                <label class="st-switch" for="f-sla">
                    <input type="checkbox" id="f-sla" data-key="features.sla_enabled" class="peer sr-only" @checked((bool) $settings->get('features.sla_enabled', false))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="f-sla-min">زمان تعهدی تحویل پس از پرداخت (دقیقه)</label>
                <input id="f-sla-min" type="number" min="5" max="1440" data-key="features.sla_minutes" class="field" value="{{ $settings->get('features.sla_minutes', 60) }}">
                <p class="st-hint">اگر سفارش دیرتر از این زمان تحویل شود، در پنل ادمین بج «دیرکرد» می‌خورد (۵ تا ۱۴۴۰ دقیقه).</p>
            </div>

            {{-- ===== امنیت ورود پنل‌ها ===== --}}
            <div class="st-field-row">
                <p class="text-xs font-extrabold text-stone-700 border-b border-stone-100 pb-2 w-full">امنیت ورود پنل‌ها (ادمین/سازمان/کافی‌نت/اپراتور)</p>
            </div>

            <div class="st-field-row">
                <label class="lbl" for="f-captcha-mode">ربات‌گیر (کپچا) — حالت</label>
                <select id="f-captcha-mode" data-key="features.captcha_mode" class="field" aria-label="حالت ربات‌گیر">
                    @foreach (['off' => 'خاموش — هیچ‌وقت کپچا نشان داده نمی‌شود', 'smart' => 'هوشمند — فقط بعد از چند تلاش ناموفق', 'always' => 'همیشه — همهٔ ورودها کپچا دارند'] as $m => $lbl)
                        <option value="{{ $m }}" @selected($settings->get('features.captcha_mode', 'smart') === $m)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <p class="st-hint">کپچای ریاضی داخلی (بدون سرویس بیرونی) — روی هر ۴ پنل اعمال می‌شود.</p>
            </div>
            <div class="st-field-row">
                <label class="lbl" for="f-captcha-fails">حالت هوشمند: کپچا بعد از چند تلاش ناموفق؟</label>
                <input id="f-captcha-fails" type="number" min="1" max="10" data-key="features.captcha_after_fails" class="field" value="{{ $settings->get('features.captcha_after_fails', 2) }}">
                <p class="st-hint">۱ تا ۱۰ — پیشنهاد: ۲. کاربر عادی اصلاً کپچا نمی‌بیند؛ بات‌ها می‌بینند.</p>
            </div>

            <div class="st-switch-row">
                <div><p class="text-xs font-bold text-stone-700">ورود دومرحله‌ای پیامکی (2FA)</p><p class="text-[11px] text-stone-400 mt-0.5">پس از رمز، کد ۶ رقمی پیامک می‌شود (۵ دقیقه اعتبار). حساب‌های بدون موبایل مستثنا هستند.</p></div>
                <label class="st-switch" for="f-2fa">
                    <input type="checkbox" id="f-2fa" data-key="features.two_factor" class="peer sr-only" @checked((bool) $settings->get('features.two_factor', false))>
                    <span class="st-switch-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="st-section-foot">
                <button type="submit" class="btn-primary btn-shine ui-press !py-2.5 px-7">ذخیرهٔ قابلیت‌ها و امنیت</button>
            </div>
        </form>

    </div><!-- /st-sections — v44: سکشن آپلود حالا داخل ستون سکشن‌هاست -->

{{-- مودال پیامک آزمایشی --}}
<div id="sms-modal" class="ui-modal-backdrop hidden">
    <div data-close class="absolute inset-0" aria-hidden="true"></div>
    <form id="sms-form" class="ui-modal adm-modal-sm adm-modal-text-start" data-tone="info" role="dialog" aria-modal="true" aria-labelledby="sms-title" novalidate>
        <div class="adm-modal-head">
            <h3 class="text-sm font-extrabold text-stone-800" id="sms-title">پیامک آزمایشی</h3>
            <button type="button" class="adm-modal-x" data-close aria-label="بستن">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="adm-modal-body">
            <div>
                <label class="lbl" for="t-mobile">شماره موبایل</label>
                <input id="t-mobile" type="tel" dir="ltr" class="field" placeholder="09123456789" required>
                <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="mobile"></p>
            </div>
        </div>
        <div class="adm-modal-foot">
            <button type="submit" id="sms-send" class="btn-primary btn-shine w-full !py-3">ارسال</button>
        </div>
    </form>
</div>
</div>
</div><!-- /root — v44: بسته‌شدن ریشهٔ کامپوننت (st-layout + ریشه) -->
@push('scripts')
<script src="{{ asset('back/assets/js/pages/admin/settings/index.js') }}?v=32"></script>
@endpush

