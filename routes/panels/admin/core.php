<?php

use App\Http\Controllers\Back\Admin\TrashController as TrashCtrl;

/*
|--------------------------------------------------------------------------
| پنل مدیریت کل — هسته (داشبورد/تنظیمات/اطلاعیه/لاگ/پیامک/راهنما/سیستم)
|--------------------------------------------------------------------------
| [فاز ۳ — مهاجرت Livewire] این فایل از admin.php جدا شد تا هر ایجنت مالکیت
| اختصاصی روی بخش خودش داشته باشد. نام‌ها و مسیرهای روت بدون تغییر مانده‌اند.
|
| ⚠ مالکیت فایل: فقط ایجنتِ همین بخش حق ویرایش دارد.
*/

Route::middleware(['admin.access'])->group(function () {
        /* [Task 3-b] داشبورد → کامپوننت Livewire (منطق DashboardController@index منتقل شده) */
        Route::get('/', \App\Livewire\Admin\Core\Dashboard::class)
            ->name('dashboard');
        /* تنظیمات (AJAX) — [Task 3-b] فرم/تب‌ها → Livewire؛ ذخیره و تست‌ها همان کنترلر */
        Route::get('settings', \App\Livewire\Admin\Core\Settings::class)
            ->name('settings.edit');
        Route::put('settings', [App\Http\Controllers\Back\Admin\SettingsController::class, 'save'])
            ->name('settings.save');
        Route::put('settings/referral', [App\Http\Controllers\Back\Admin\SettingsController::class, 'saveReferral'])
            ->name('settings.referral');
        Route::post('settings/test-sms', [App\Http\Controllers\Back\Admin\SettingsController::class, 'testSms'])
            ->name('settings.test-sms');
        Route::post('settings/test-pusher', [App\Http\Controllers\Back\Admin\SettingsController::class, 'testPusher'])
            ->name('settings.test-pusher');
        // v40 — تست اتصال سرویس استعلام فینوتک
        Route::post('settings/finnotech-test', [App\Http\Controllers\Back\Admin\SettingsController::class, 'testFinnotech'])
            ->name('settings.finnotech-test');

        /* v37 — اجرای دستی زمان‌بندی‌ها (تست سلامت کرون از تنظیمات عمومی) */
        Route::post('settings/cron-run', [App\Http\Controllers\Back\Admin\SettingsController::class, 'cronRun'])
            ->name('settings.cron-run');

        /* اعلان‌ها (v25) — صدای اعلان + پوش فایربیس */
        Route::post('settings/notification/sound', [App\Http\Controllers\Back\Admin\SettingsController::class, 'uploadSound'])
            ->name('settings.notif-sound.upload');
        Route::delete('settings/notification/sound', [App\Http\Controllers\Back\Admin\SettingsController::class, 'deleteSound'])
            ->name('settings.notif-sound.delete');
        Route::post('settings/notification/push-credentials', [App\Http\Controllers\Back\Admin\SettingsController::class, 'uploadFirebaseCredentials'])
            ->name('settings.notif-firebase.upload');
        Route::delete('settings/notification/push-credentials', [App\Http\Controllers\Back\Admin\SettingsController::class, 'deleteFirebaseCredentials'])
            ->name('settings.notif-firebase.delete');
        Route::post('settings/test-push', [App\Http\Controllers\Back\Admin\SettingsController::class, 'testPush'])
            ->name('settings.test-push');
        /* v26 — بازتولید کلیدهای VAPID وب‌پوش داخلی */
        Route::post('settings/notification/webpush-keys', [App\Http\Controllers\Back\Admin\SettingsController::class, 'regenerateWebpushKeys'])
            ->name('settings.notif-webpush.regenerate');

        /* v38 — نمایشگر لاگ سیستمی لاراول (تنظیمات ← لاگ سیستمی) — [Task 3-b] صفحه → Livewire */
        Route::get('settings/logs', \App\Livewire\Admin\Core\SystemLogs::class)
            ->name('settings.logs');
        Route::get('settings/logs/data', [App\Http\Controllers\Back\Admin\SystemLogsController::class, 'data'])
            ->name('settings.logs.data');
        Route::post('settings/logs/clear', [App\Http\Controllers\Back\Admin\SystemLogsController::class, 'clear'])
            ->name('settings.logs.clear');
        Route::delete('settings/logs/file', [App\Http\Controllers\Back\Admin\SystemLogsController::class, 'destroy'])
            ->name('settings.logs.delete');

        /* اطلاعیه‌های پنل (فاز ۱۵ — مشترک) */
        Route::get('announcements/pending', [App\Http\Controllers\Back\Shared\PanelAnnouncementsController::class, 'pending'])
            ->name('announcements.pending');
        Route::post('announcements/{announcement}/read', [App\Http\Controllers\Back\Shared\PanelAnnouncementsController::class, 'read'])
            ->whereNumber('announcement')->name('announcements.read');

        /* اطلاعیه‌های سامانه (فاز ۱۵ — AJAX) — [Task 3-b] صفحه → Livewire shell */
        Route::get('announcements', \App\Livewire\Admin\Core\Announcements::class)
            ->name('announcements.index');
        Route::get('announcements/data', [App\Http\Controllers\Back\Admin\AnnouncementsController::class, 'data'])
            ->name('announcements.data');
        Route::get('announcements/{announcement}', [App\Http\Controllers\Back\Admin\AnnouncementsController::class, 'show'])
            ->whereNumber('announcement')->name('announcements.show');
        Route::post('announcements', [App\Http\Controllers\Back\Admin\AnnouncementsController::class, 'store'])
            ->name('announcements.store');
        Route::put('announcements/{announcement}', [App\Http\Controllers\Back\Admin\AnnouncementsController::class, 'update'])
            ->whereNumber('announcement')->name('announcements.update');
        Route::patch('announcements/{announcement}/toggle', [App\Http\Controllers\Back\Admin\AnnouncementsController::class, 'toggle'])
            ->whereNumber('announcement')->name('announcements.toggle');
        Route::delete('announcements/{announcement}', [App\Http\Controllers\Back\Admin\AnnouncementsController::class, 'destroy'])
            ->whereNumber('announcement')->name('announcements.destroy');


        /* لاگ فعالیت (AJAX) — [Task 3-b] صفحه → Livewire (رندر سمت سرور) */
        Route::get('audit-logs', \App\Livewire\Admin\Core\AuditLogs::class)
            ->name('audit.index');
        Route::get('audit-logs/data', [App\Http\Controllers\Back\Admin\AuditLogsController::class, 'data'])
            ->name('audit.data');

        /* جغرافیا (مشترک پنل‌ها — سلکت آبشاری) */
        Route::get('geo/cities', [App\Http\Controllers\Back\GeoController::class, 'cities'])
            ->name('geo.cities');
        /* ---------- فاز ۱۰ — قالب‌های پیامک — [Task 3-b] صفحه → Livewire shell ---------- */
        Route::get('sms-templates', \App\Livewire\Admin\Core\SmsTemplates::class)
            ->name('sms-templates.index');
        Route::get('sms-templates/data', [App\Http\Controllers\Back\Admin\SmsTemplatesController::class, 'data'])
            ->name('sms-templates.data');
        Route::put('sms-templates/{template}', [App\Http\Controllers\Back\Admin\SmsTemplatesController::class, 'update'])
            ->whereNumber('template')->name('sms-templates.update');
        Route::patch('sms-templates/{template}/toggle', [App\Http\Controllers\Back\Admin\SmsTemplatesController::class, 'toggle'])
            ->whereNumber('template')->name('sms-templates.toggle');
        Route::post('sms-templates/{template}/test', [App\Http\Controllers\Back\Admin\SmsTemplatesController::class, 'test'])
            ->whereNumber('template')->name('sms-templates.test');

        /* ---------- v16 — لاگ پیامک‌های ارسال‌شده — [Task 3-b] صفحه → Livewire (رندر سمت سرور) ---------- */
        Route::get('sms-logs', \App\Livewire\Admin\Core\SmsLogs::class)
            ->name('sms-logs.index');
        Route::get('sms-logs/data', [App\Http\Controllers\Back\Admin\SmsLogsController::class, 'data'])
            ->name('sms-logs.data');
        /* ---------- فاز ۱۰ — زنگ اعلان ---------- */
        Route::get('notifications/badge', [App\Http\Controllers\Back\NotificationsController::class, 'badge'])
            ->name('notifications.badge');
        Route::get('notifications/data', [App\Http\Controllers\Back\NotificationsController::class, 'data'])
            ->name('notifications.data');
        Route::post('notifications/read', [App\Http\Controllers\Back\NotificationsController::class, 'read'])
            ->name('notifications.read');

        /* نوتیف دستگاه (Web Push / FCM) — v25 */
        Route::post('push/token', [App\Http\Controllers\Back\PushTokenController::class, 'store'])
            ->name('push.token');
        Route::delete('push/token', [App\Http\Controllers\Back\PushTokenController::class, 'destroy'])
            ->name('push.token.destroy');

        /* ---------- فاز ۱۳ — آموزش پنل (راهنماهای مدیر کل) — [Task 3-b] → Livewire ---------- */
        Route::get('guide', \App\Livewire\Admin\Core\GuideIndex::class)
            ->name('guide.index')->defaults('guide_role', 'super_admin');
        Route::get('guide/{slug}', \App\Livewire\Admin\Core\GuideShow::class)
            ->name('guide.show')->defaults('guide_role', 'super_admin');

        /* ---------- فاز ۱۱ — وضعیت سیستم و مستندات API — [Task 3-b] صفحه → Livewire ---------- */
        Route::get('system', \App\Livewire\Admin\Core\System::class)
            ->name('system.index');
        Route::post('system/cleanup', [App\Http\Controllers\Back\Admin\SystemController::class, 'runCleanup'])
            ->name('system.cleanup');
        Route::post('system/retention', [App\Http\Controllers\Back\Admin\SystemController::class, 'saveRetention'])
            ->name('system.retention');
        Route::post('system/encrypt', [App\Http\Controllers\Back\Admin\SystemController::class, 'encryptFiles'])
            ->name('system.encrypt');
        Route::get('api-docs', \App\Livewire\Admin\Core\ApiDocs::class)
            ->name('api-docs.index');
    });
