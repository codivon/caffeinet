<?php

use App\Http\Controllers\Front\LandingController;
use App\Http\Controllers\Back\Admin\TrashController as TrashCtrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای وب (پنل‌ها + صفحه عمومی)
|--------------------------------------------------------------------------
|
| ساختار پوشه‌ای تفکیک‌شده:
|   پنل مدیر کل      → app/Http/Controllers/Back/Admin     → resources/views/back/admin
|   پنل سازمان       → app/Http/Controllers/Back/Org       → resources/views/back/org
|   پنل کافی‌نت      → app/Http/Controllers/Back/Coffeenet  → resources/views/back/coffeenet
|   پنل اپراتور      → app/Http/Controllers/Back/Operator  → resources/views/back/operator
|   API مشتری        → app/Http/Controllers/Api/V1         → routes/api.php
|
*/



/* [Task 8] صفحه فرود → کامپوننت Livewire (منطق عین LandingController::index) */
Route::get('/', \App\Livewire\Front\Landing::class)->name('front.landing');

/* ---------- v37 — بیکن حضور: «برنامه بسته شد» (pagehide) ----------
| push-client.js روی همهٔ لایه‌ها با sendBeacon این مسیر را صدا می‌زند تا
| بستن برنامه همان لحظه «آفلاین» ثبت شود (اپ مشتری با توکن Sanctum در
| بدنه، پنل‌ها با کوکی نشست وب). از CSRF معاف (bootstrap/app.php) —
| احراز هویت داخل کنترلر انجام می‌شود؛ فقط last_seen_at را به گذشته
| برمی‌گرداند و اثر جانبی دیگری ندارد.
*/
Route::post('presence-offline', \App\Http\Controllers\PresenceOfflineController::class)
    ->name('presence.offline');

/* ---------- رسانهٔ عمومی — فاز ۲۲ (رفع 403 تصاویر) ----------
| تصاویر خدمات/اطلاعیه‌ها از storage/app/public مستقیم استریم می‌شوند
| تا سرو تصاویر به symlink باقی‌مانده از storage:link وابسته نباشد
| (روی هاست‌هایی که zip، public/storage را پوشهٔ واقعی باز کرده، 403 می‌داد).
| جزئیات امنیت (لیست سفید پیشوند/پسوند، بدون svg) در MediaController.
*/
Route::get('media/{path}', [\App\Http\Controllers\MediaController::class, 'show'])
    ->where('path', '.*')
    ->middleware('throttle:240,1')
    ->name('media.show');

/* ---------- دانلود/نمایش فایل‌ها (لینک موقت امضاشده) ----------
| ⚠ این سه روت در بازسازی روت‌ها (بازلاین) به‌اشتباه حذف شده بودند؛ بدون آن‌ها
| URL::temporarySignedRoute('files.*') خطا می‌داد → null برمی‌گشت و
| پیش‌نمایش همهٔ فایل‌های آپلودشده (چت سفارش، مدارک سفارش، پیوست تیکت)
| «خراب/خالی» نمایش داده می‌شد. بازگردانی عیناً از اولین کامیت پروژه.
*/
Route::get('files/order/{file}', [App\Http\Controllers\Front\App\FilesController::class, 'download'])
    ->whereNumber('file')->middleware('throttle:120,1')->name('files.order');

Route::get('files/chat/{message}', [App\Http\Controllers\Front\App\ChatFilesController::class, 'download'])
    ->whereNumber('message')->middleware('throttle:240,1')->name('files.chat');

Route::get('files/ticket/{message}', [App\Http\Controllers\Front\App\TicketFilesController::class, 'download'])
    ->whereNumber('message')->middleware('throttle:120,1')->name('files.ticket');

/* ---------- پرداخت آنلاین (مسیر وب) — فاز ۱۲-fix ----------
| کنترلر و سرویس از ابتدا وجود داشتند اما روت‌ها هرگز ثبت نشده بودند →
| هنگام پرداخت آنلاین، URL::temporarySignedRoute('payment.start') استثنای
| «Route [payment.start] not defined» می‌داد و جریان پرداخت می‌شکست.
|   GET  /payment/start/{payment}  → صفحهٔ درگاه (لینک امضاشدهٔ موقت)
|   POST|GET /payment/callback     → بازگشت از درگاه (CSRF-exempt — bootstrap/app.php)
*/
Route::get('payment/start/{payment}', [\App\Http\Controllers\Front\App\PaymentController::class, 'start'])
    ->whereNumber('payment')->middleware('throttle:60,1')->name('payment.start');

Route::match(['get', 'post'], 'payment/callback', [\App\Http\Controllers\Front\App\PaymentController::class, 'callback'])
    ->middleware('throttle:60,1')->name('payment.callback');

/* ---------- Realtime داخلی — جریان SSE (فاز ۱۲) ----------
| «اتصال دائمی مثل سوکت» بدون سرویس بیرونی و بدون تحریم — هنگام روش
| «SSE» در تب Realtime تنظیمات، realtime.js با EventSource به همین
| مسیر وصل می‌شود و رویدادها (چت/اعلان/سفارش) همان لحظه می‌رسند.
| احراز: نشست وبِ پنل‌ها یا توکن Sanctum در ‎?token=‎ (اپ مشتری).
| بدون CSRF-مشکل (GET) و بدون کش (هدرهای no-cache در پاسخ).
*/
Route::get('realtime/stream', [\App\Http\Controllers\RealtimeStreamController::class, 'stream'])
    ->name('realtime.stream');

/* ---------- PWA (فاز ۱۴ — بازطراحی تفکیک‌شده) — مانیفست مستقل هر پنل ----------
| هر بخش «اپ نصب‌شدنی» اختصاصی خودش را دارد؛ نصب از داخل همان پنل انجام
| می‌شود و آیکون نصب‌شده مستقیماً همان پنل را باز می‌کند (نه صفحه فرود):
|   /app/manifest.webmanifest          → اپ مشتریان  (start_url=/app؛ مهمان → ورود، عضو → داشبورد)
|   /admin/manifest.webmanifest        → پنل مدیریت کل
|   /organization/manifest.webmanifest → پنل سازمان
|   /coffeenet/manifest.webmanifest    → پنل کافی‌نت
|   /operator/manifest.webmanifest     → پنل اپراتور
| صفحه فرود (landing) عمداً مانیفست ندارد → قابل نصب نیست.
| مانیفست از روت سرو می‌شود تا در هر وب‌سروری (Apache/Nginx/Caddy/artisan)
| با هدر صحیح application/manifest+json تحویل داده شود.
| آیکون‌ها و sw.js فایل استاتیک public هستند.
*/
Route::get('{panel}/manifest.webmanifest', function (string $panel) {
    $name = (string) config('app.name', 'کافی‌نت آنلاین');

    /* ریشه هر پنل به‌صورت هوشمند عمل می‌کند: مهمان → لاگین همان پنل،
     * کاربر واردشده → داشبورد (یا صفحه انتخاب زمینه) همان پنل. */
    $panels = [
        'app' => [
            // v42 — عنوان PWA طبق درخواست مالک: «کافی‌نت آنلاین» (بدون پسوند)
            'title'       => $name,
            'short'       => 'کافی‌نت آنلاین',
            'description' => 'سفارش خدمات کافی‌نت آنلاین؛ فرم‌ساز پویا، پرداخت آنلاین، پیگیری لحظه‌ای سفارش و چت مستقیم با اپراتور.',
            'start'       => '/app',
            'scope'       => '/',
            'id'          => '/app/',
            'panel_icons' => null,   // اپ مشتری → آیکون برند اصلی
            'orientation' => 'portrait-primary',
            'extras'      => true,   // shortcuts + اسکرین‌شات (فقط اپ مشتری)
        ],
        'admin' => [
            'title'       => $name.' — پنل مدیریت کل',
            'short'       => 'مدیریت کل',
            'description' => 'پنل مدیریت کل کافی‌نت آنلاین؛ داشبورد، مالی، کمیسیون‌ها، کافی‌نت‌ها، اپراتورها و تنظیمات سامانه.',
            'start'       => '/admin',
            'scope'       => '/admin/',
            'id'          => '/admin/',
            'panel_icons' => 'admin',
        ],
        'organization' => [
            'title'       => $name.' — پنل سازمان',
            'short'       => 'سازمان',
            'description' => 'پنل سازمان کافی‌نت آنلاین؛ مدیریت اپراتورها، سفارش‌ها و مالی سازمان.',
            'start'       => '/organization',
            'scope'       => '/organization/',
            'id'          => '/organization/',
            'panel_icons' => 'organization',
        ],
        'coffeenet' => [
            'title'       => $name.' — پنل کافی‌نت',
            'short'       => 'پنل کافی‌نت',
            'description' => 'پنل کافی‌نت آنلاین؛ مدیریت خدمات، سفارش‌ها، اپراتورها و درآمد شعبه.',
            'start'       => '/coffeenet',
            'scope'       => '/coffeenet/',
            'id'          => '/coffeenet/',
            'panel_icons' => 'coffeenet',
        ],
        'operator' => [
            'title'       => $name.' — پنل اپراتور',
            'short'       => 'اپراتور',
            'description' => 'پنل اپراتور کافی‌نت آنلاین؛ صف سفارش‌ها، اجرا و گفتگو با مشتریان.',
            'start'       => '/operator',
            'scope'       => '/operator/',
            'id'          => '/operator/',
            'panel_icons' => 'operator',
        ],
    ];

    abort_unless(isset($panels[$panel]), 404);
    $cfg = $panels[$panel];

    /* ظاهر پویا — theme_color/background_color از پالت رنگی همان پنل
       (تنظیمات ← ظاهر و رنگ‌بندی) — سازمان → کلید org در سیستم پوسته.
       v42 — تم روشن/تاریک: کوکی «cn_theme» (theme-boot.js روی همهٔ صفحات
       می‌نویسد) رنگ اسپلش/نوار را عوض می‌کند؛ بدون کوکی = تیره (رفتار قبلی). */
    $appearancePanel = $panel === 'organization' ? 'org' : $panel;
    $tokens = \App\Support\Appearance::tokensFor($appearancePanel);
    $isDark = ($_COOKIE['cn_theme'] ?? '') !== 'light';
    $themeColor = $isDark
        ? ($tokens['ramp']['800'] ?? '#1e3a8a')
        : ($tokens['ramp']['600'] ?? '#2563eb');
    $bgColor = $isDark
        ? ($tokens['ramp']['950'] ?? '#172554')
        : ($tokens['page_bg'] ?? '#ffffff');

    /* آیکون اختصاصی پنل (icons/panels/…) — در نبود فایل‌ها → آیکون برند */
    $usePanelIcons = $cfg['panel_icons'] !== null
        && is_file(public_path('icons/panels/'.$cfg['panel_icons'].'-512.png'));

    $iconUrl = function (int $size) use ($cfg, $usePanelIcons) {
        return $usePanelIcons
            ? url('/icons/panels/'.$cfg['panel_icons'].'-'.$size.'.png')
            : url('/icons/icon-'.$size.'.png');
    };

    $manifest = [
        'id'                     => url($cfg['id']),
        'name'                   => $cfg['title'],
        'short_name'             => $cfg['short'],
        'description'            => $cfg['description'],
        'lang'                   => 'fa',
        'dir'                    => 'rtl',
        // start_url/scope هر دو باید با «/» تمام شوند تا قاعدهٔ «در محدوده بودن»
        // start_url و عدم گسترش ناخواستهٔ scope به کل دامنه رعایت شود.
        'start_url'              => rtrim(url($cfg['start']), '/').'/?source=pwa',
        'scope'                  => rtrim(url($cfg['scope']), '/').'/',
        'display'                => 'standalone',
        'display_override'       => ['standalone', 'minimal-ui'],
        'background_color'       => $bgColor,
        'theme_color'            => $themeColor,
        'categories'             => ['business', 'productivity', 'shopping'],
        'prefer_related_applications' => false,

        // آیکون‌ها — فقط purpose «any» (v42): آیکون پس‌زمینهٔ شفاف دارد؛
        // در لانچر اندروید لانچر خودش قاب مربع‌گوشه‌گرد می‌گذارد (نه دایرهٔ maskable)
        // و در iOS گوشه‌ها توسط خود سیستم گرد می‌شود؛ اسپلش هم بدون قاب جدا رندر می‌شود.
        'icons' => [
            ['src' => $iconUrl(48),   'sizes' => '48x48',   'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $iconUrl(96),   'sizes' => '96x96',   'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $iconUrl(192),  'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $iconUrl(512),  'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ],
    ];

    if (! empty($cfg['orientation'])) {
        $manifest['orientation'] = $cfg['orientation'];
    }

    // میان‌برهای صفحه اصلی (اندروید لانچر — لمس طولانی آیکون) + اسکرین‌شات‌ها — فقط اپ مشتری
    if (! empty($cfg['extras'])) {
        $manifest['shortcuts'] = [
            [
                'name'       => 'خدمات کافی‌نت',
                'short_name' => 'خدمات',
                'description' => 'کاتالوگ خدمات و فرم سفارش',
                'url'        => url('/app/services?source=pwa-shortcut'),
                'icons'      => [['src' => $iconUrl(96), 'sizes' => '96x96']],
            ],
            [
                'name'       => 'سفارش‌های من',
                'short_name' => 'سفارش‌ها',
                'description' => 'پیگیری سفارش‌ها و گفتگو با اپراتور',
                'url'        => url('/app/orders?source=pwa-shortcut'),
                'icons'      => [['src' => $iconUrl(96), 'sizes' => '96x96']],
            ],
            [
                'name'       => 'پشتیبانی و تیکت',
                'short_name' => 'پشتیبانی',
                'description' => 'تیکت پشتیبانی و پیگیری پاسخ',
                'url'        => url('/app/support?source=pwa-shortcut'),
                'icons'      => [['src' => $iconUrl(96), 'sizes' => '96x96']],
            ],
            [
                'name'       => 'کیف پول',
                'short_name' => 'کیف پول',
                'description' => 'موجودی، واریز و تراکنش‌ها',
                'url'        => url('/app/wallet?source=pwa-shortcut'),
                'icons'      => [['src' => $iconUrl(96), 'sizes' => '96x96']],
            ],
        ];

        $manifest['screenshots'] = [
            ['src' => url('/icons/screenshots/home.png'),  'sizes' => '540x720', 'type' => 'image/png', 'form_factor' => 'narrow', 'label' => 'داشبورد مشتری'],
            ['src' => url('/icons/screenshots/services.png'), 'sizes' => '540x720', 'type' => 'image/png', 'form_factor' => 'narrow', 'label' => 'کاتالوگ خدمات'],
            ['src' => url('/icons/screenshots/order.png'), 'sizes' => '540x720', 'type' => 'image/png', 'form_factor' => 'narrow', 'label' => 'جزئیات سفارش و چت'],
        ];
    }

    return response()->json($manifest)
        ->header('Content-Type', 'application/manifest+json')
        // v42 — با کوکی تم (روشن/تاریک) عوض می‌شود؛ کش کوتاه خصوصی
        ->header('Cache-Control', 'private, max-age=300')
        ->header('Vary', 'Cookie');
})->where('panel', 'app|admin|organization|coffeenet|operator')->name('pwa.manifest');

/* مانیفست قدیمی ریشه → مانیفست اپ مشتری (مهاجرت نصب‌های قبلی، ۳۰۱ دائمی) */
Route::permanentRedirect('manifest.webmanifest', 'app/manifest.webmanifest');

// صفحه آفلاین — بدون نیاز به سشن/لاگین (توسط SW کش می‌شود)
Route::view('offline', 'pwa.offline')->name('pwa.offline');

/* ---------- روت سراسری login (فاز ۱۱ — هاردنینگ) ----------
| ریدایرکتِ احراز هویت ناموفقِ وب به مسیر login پنلِ مربوطه هدایت می‌شود
| (قبلاً route('login') تعریف‌نشده → 500). API مهمان 401 JSON می‌گیرد.
*/
Route::get('login', function (Request $request) {
    $intended = (string) $request->session()->get('url.intended', $request->input('to', ''));

    return match (true) {
        str_starts_with($intended, '/admin') => redirect()->route('admin.login'),
        str_starts_with($intended, '/organization') => redirect()->route('org.login'),
        str_starts_with($intended, '/org') => redirect()->route('org.login'),
        str_starts_with($intended, '/coffeenet') => redirect()->route('coffeenet.login'),
        str_starts_with($intended, '/operator') => redirect()->route('operator.login'),
        default => redirect()->route('admin.login'),
    };
})->name('login');

/*
|--------------------------------------------------------------------------
| بارگذاری مسیرهای تفکیک‌شدهٔ پنل‌ها (Livewire SPA)
|--------------------------------------------------------------------------
*/

require __DIR__.'/panels/app.php';
require __DIR__.'/panels/admin.php';
require __DIR__.'/panels/org.php';
require __DIR__.'/panels/coffeenet.php';
require __DIR__.'/panels/operator.php';
