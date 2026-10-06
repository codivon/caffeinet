<?php

use App\Http\Controllers\Back\Admin\TrashController as TrashCtrl;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| پنل مدیریت کل (/admin)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    /* [Task 2-a] فرم ورود → کامپوننت Livewire (الگوی مرجع SPA)؛ نام روت admin.login حفظ شده.
       POST fallback (admin.login.attempt) و خروج همچنان در AuthController می‌مانند. */
    Route::get('login', App\Livewire\Admin\Auth\Login::class)
        ->name('login');
    Route::post('login', [App\Http\Controllers\Back\Admin\AuthController::class, 'login'])
        ->name('login.attempt');

    Route::middleware(['admin.access'])->group(function () {
        Route::post('logout', [App\Http\Controllers\Back\Admin\AuthController::class, 'logout'])
            ->name('logout');

        Route::get('/', [App\Http\Controllers\Back\Admin\DashboardController::class, 'index'])
            ->name('dashboard');

        /* مدیریت مدیران (AJAX) */
        Route::get('admins', [App\Http\Controllers\Back\Admin\AdminsController::class, 'index'])
            ->name('admins.index');
        Route::get('admins/data', [App\Http\Controllers\Back\Admin\AdminsController::class, 'data'])
            ->name('admins.data');

        /* حذف نرم/دائم مشترک (v28) — مسیرها پیش از روت‌های پارامتری بخش‌ها */
        Route::get('admins/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'admins')->name('admins.trashed');
        Route::get('admins/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'admins')->whereNumber('id')->name('admins.delete-info');
        Route::delete('admins/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'admins')->whereNumber('id')->name('admins.destroy');
        Route::post('admins/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'admins')->whereNumber('id')->name('admins.restore');
        Route::delete('admins/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'admins')->whereNumber('id')->name('admins.purge');

        Route::post('admins', [App\Http\Controllers\Back\Admin\AdminsController::class, 'store'])
            ->name('admins.store');
        Route::put('admins/{user}', [App\Http\Controllers\Back\Admin\AdminsController::class, 'update'])
            ->name('admins.update');
        Route::patch('admins/{user}/toggle', [App\Http\Controllers\Back\Admin\AdminsController::class, 'toggle'])
            ->name('admins.toggle');

        /* تنظیمات (AJAX) */
        Route::get('settings', [App\Http\Controllers\Back\Admin\SettingsController::class, 'edit'])
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

        /* v38 — نمایشگر لاگ سیستمی لاراول (تنظیمات ← لاگ سیستمی) */
        Route::get('settings/logs', [App\Http\Controllers\Back\Admin\SystemLogsController::class, 'index'])
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

        /* اطلاعیه‌های سامانه (فاز ۱۵ — AJAX) */
        Route::get('announcements', [App\Http\Controllers\Back\Admin\AnnouncementsController::class, 'index'])
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


        /* لاگ فعالیت (AJAX) */
        Route::get('audit-logs', [App\Http\Controllers\Back\Admin\AuditLogsController::class, 'index'])
            ->name('audit.index');
        Route::get('audit-logs/data', [App\Http\Controllers\Back\Admin\AuditLogsController::class, 'data'])
            ->name('audit.data');

        /* جغرافیا (مشترک پنل‌ها — سلکت آبشاری) */
        Route::get('geo/cities', [App\Http\Controllers\Back\GeoController::class, 'cities'])
            ->name('geo.cities');

        /* مدیریت سازمان‌ها (AJAX) */
        Route::get('organizations', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'index'])
            ->name('organizations.index');
        Route::get('organizations/data', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'data'])
            ->name('organizations.data');

        Route::get('organizations/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'organizations')->name('organizations.trashed');
        Route::get('organizations/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'organizations')->whereNumber('id')->name('organizations.delete-info');
        Route::delete('organizations/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'organizations')->whereNumber('id')->name('organizations.destroy');
        Route::post('organizations/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'organizations')->whereNumber('id')->name('organizations.restore');
        Route::delete('organizations/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'organizations')->whereNumber('id')->name('organizations.purge');

        Route::post('organizations', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'store'])
            ->name('organizations.store');
        Route::put('organizations/{organization}', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'update'])
            ->name('organizations.update');
        Route::patch('organizations/{organization}/status', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'status'])
            ->name('organizations.status');
        Route::get('organizations/{organization}', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'show'])
            ->name('organizations.show');

        /* مدیریت کافی‌نت‌ها (AJAX) */
        Route::get('coffeenets', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'index'])
            ->name('coffeenets.index');
        Route::get('coffeenets/data', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'data'])
            ->name('coffeenets.data');
        /* حذف نرم/دائم مشترک (v28) */
        Route::get('coffeenets/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'coffeenets')->name('coffeenets.trashed');
        Route::get('coffeenets/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'coffeenets')->whereNumber('id')->name('coffeenets.delete-info');
        Route::delete('coffeenets/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'coffeenets')->whereNumber('id')->name('coffeenets.destroy');
        Route::post('coffeenets/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'coffeenets')->whereNumber('id')->name('coffeenets.restore');
        Route::delete('coffeenets/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'coffeenets')->whereNumber('id')->name('coffeenets.purge');

        Route::post('coffeenets', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'store'])
            ->name('coffeenets.store');
        Route::put('coffeenets/{coffeenet}', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'update'])
            ->name('coffeenets.update');
        Route::patch('coffeenets/{coffeenet}/status', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'status'])
            ->name('coffeenets.status');
        Route::get('coffeenets/{coffeenet}', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'show'])
            ->name('coffeenets.show');

        /* روند کاری کافی‌نت (درخواست بازخوردی ۶-۲ — چارت بازه‌ای) */
        Route::get('coffeenets/{coffeenet}/trend', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'trend'])
            ->name('coffeenets.trend');

        /* دید کلان مدیر به همه کارکنان/اپراتورهای شبکه (فاز ۱۰) */
        Route::get('operators', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'index'])
            ->name('operators.index');
        Route::get('operators/data', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'data'])
            ->name('operators.data');
        /* حذف نرم/دائم مشترک (v28) */
        Route::get('operators/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'operators')->name('operators.trashed');
        Route::get('operators/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'operators')->whereNumber('id')->name('operators.delete-info');
        Route::delete('operators/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'operators')->whereNumber('id')->name('operators.destroy');
        Route::post('operators/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'operators')->whereNumber('id')->name('operators.restore');
        Route::delete('operators/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'operators')->whereNumber('id')->name('operators.purge');

        Route::post('operators', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'store'])
            ->name('operators.store');

        /* پروفایل کامل اپراتور + روند کاری (درخواست بازخوردی ۶-۱) */
        Route::get('operators/{assignment}', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'show'])
            ->whereNumber('assignment')->name('operators.show');
        Route::get('operators/{assignment}/trend', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'trend'])
            ->whereNumber('assignment')->name('operators.trend');

        /* ویرایش کامل کارمند توسط مدیر کل (اطلاعات ورود + انتقال کافی‌نت) */
        Route::put('operators/{assignment}', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'update'])
            ->whereNumber('assignment')->name('operators.update');
        Route::get('operators/{assignment}/salary', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'salary'])
            ->whereNumber('assignment')->name('operators.salary');

        /* گردش‌کار تایید کارمندان (ساخته‌شده توسط مدیر کافی‌نت) */
        Route::patch('operators/{assignment}/approve', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'approve'])
            ->whereNumber('assignment')->name('operators.approve');
        Route::patch('operators/{assignment}/reject', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'reject'])
            ->whereNumber('assignment')->name('operators.reject');

        /* مدیریت برداشت‌ها (AJAX) */
        Route::get('withdrawals', [App\Http\Controllers\Back\Admin\WithdrawalsController::class, 'index'])
            ->name('withdrawals.index');
        Route::get('withdrawals/data', [App\Http\Controllers\Back\Admin\WithdrawalsController::class, 'data'])
            ->name('withdrawals.data');
        Route::patch('withdrawals/{withdrawal}/review', [App\Http\Controllers\Back\Admin\WithdrawalsController::class, 'review'])
            ->name('withdrawals.review');

        /* ---------- فاز ۸ — مالی و کمیسیون ---------- */

        /* قواعد کمیسیون (سراسری + اختصاصی خدمت) */
        Route::get('commissions', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'index'])
            ->name('commissions.index');
        Route::put('commissions/global', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'saveGlobal'])
            ->name('commissions.global');
        Route::get('commissions/data', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'data'])
            ->name('commissions.data');
        Route::post('commissions', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'store'])
            ->name('commissions.store');
        Route::put('commissions/{commission}', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'update'])
            ->whereNumber('commission')->name('commissions.update');
        Route::patch('commissions/{commission}/toggle', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'toggle'])
            ->whereNumber('commission')->name('commissions.toggle');
        Route::delete('commissions/{commission}', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'destroy'])
            ->whereNumber('commission')->name('commissions.destroy');

        /* تسویه‌های کمیسیون */
        Route::get('settlements', [App\Http\Controllers\Back\Admin\SettlementsController::class, 'index'])
            ->name('settlements.index');
        Route::get('settlements/data', [App\Http\Controllers\Back\Admin\SettlementsController::class, 'data'])
            ->name('settlements.data');
        Route::get('settlements/order/{order}', [App\Http\Controllers\Back\Admin\SettlementsController::class, 'order'])
            ->whereNumber('order')->name('settlements.order');
        Route::post('settlements/order/{order}/retry', [App\Http\Controllers\Back\Admin\SettlementsController::class, 'retry'])
            ->whereNumber('order')->name('settlements.retry');

        /* گزارش مالی جامع */
        Route::get('finance', [App\Http\Controllers\Back\Admin\FinanceController::class, 'index'])
            ->name('finance.index');
        Route::get('finance/data', [App\Http\Controllers\Back\Admin\FinanceController::class, 'data'])
            ->name('finance.data');
        Route::get('finance/export', [App\Http\Controllers\Back\Admin\FinanceController::class, 'export'])
            ->name('finance.export');

        /* ---------- فاز ۹ — گزارش تحلیلی و نمودارها ---------- */
        Route::get('analytics', [App\Http\Controllers\Back\Admin\AnalyticsController::class, 'index'])
            ->name('analytics.index');
        Route::get('analytics/data', [App\Http\Controllers\Back\Admin\AnalyticsController::class, 'data'])
            ->name('analytics.data');
        Route::get('analytics/export', [App\Http\Controllers\Back\Admin\AnalyticsController::class, 'export'])
            ->name('analytics.export');

        /* ---------- فاز ۱۰ — تیکت‌های پشتیبانی ---------- */
        Route::get('tickets', [App\Http\Controllers\Back\Admin\TicketsController::class, 'index'])
            ->name('tickets.index');
        Route::get('tickets/data', [App\Http\Controllers\Back\Admin\TicketsController::class, 'data'])
            ->name('tickets.data');
        /* حذف نرم/دائم مشترک (v28) */
        Route::get('tickets/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'tickets')->name('tickets.trashed');
        Route::get('tickets/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'tickets')->whereNumber('id')->name('tickets.delete-info');
        Route::delete('tickets/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'tickets')->whereNumber('id')->name('tickets.destroy');
        Route::post('tickets/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'tickets')->whereNumber('id')->name('tickets.restore');
        Route::delete('tickets/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'tickets')->whereNumber('id')->name('tickets.purge');

        Route::get('tickets/{ticket}', [App\Http\Controllers\Back\Admin\TicketsController::class, 'show'])
            ->whereNumber('ticket')->name('tickets.show');
        Route::post('tickets/{ticket}/reply', [App\Http\Controllers\Back\Admin\TicketsController::class, 'reply'])
            ->whereNumber('ticket')->name('tickets.reply');
        Route::get('tickets/{ticket}/messages', [App\Http\Controllers\Back\Admin\TicketsController::class, 'messages'])
            ->whereNumber('ticket')->name('tickets.messages');
        Route::patch('tickets/{ticket}/status', [App\Http\Controllers\Back\Admin\TicketsController::class, 'status'])
            ->whereNumber('ticket')->name('tickets.status');
        Route::patch('tickets/{ticket}/priority', [App\Http\Controllers\Back\Admin\TicketsController::class, 'priority'])
            ->whereNumber('ticket')->name('tickets.priority');
        Route::patch('tickets/{ticket}/assign', [App\Http\Controllers\Back\Admin\TicketsController::class, 'assign'])
            ->whereNumber('ticket')->name('tickets.assign');

        /* ---------- فاز ۱۰ — قالب‌های پیامک ---------- */
        Route::get('sms-templates', [App\Http\Controllers\Back\Admin\SmsTemplatesController::class, 'index'])
            ->name('sms-templates.index');
        Route::get('sms-templates/data', [App\Http\Controllers\Back\Admin\SmsTemplatesController::class, 'data'])
            ->name('sms-templates.data');
        Route::put('sms-templates/{template}', [App\Http\Controllers\Back\Admin\SmsTemplatesController::class, 'update'])
            ->whereNumber('template')->name('sms-templates.update');
        Route::patch('sms-templates/{template}/toggle', [App\Http\Controllers\Back\Admin\SmsTemplatesController::class, 'toggle'])
            ->whereNumber('template')->name('sms-templates.toggle');
        Route::post('sms-templates/{template}/test', [App\Http\Controllers\Back\Admin\SmsTemplatesController::class, 'test'])
            ->whereNumber('template')->name('sms-templates.test');

        /* ---------- v16 — لاگ پیامک‌های ارسال‌شده ---------- */
        Route::get('sms-logs', [App\Http\Controllers\Back\Admin\SmsLogsController::class, 'index'])
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

        /* ---------- فاز ۱۳ — آموزش پنل (راهنماهای مدیر کل) ---------- */
        Route::get('guide', [App\Http\Controllers\Back\Shared\GuideController::class, 'index'])
            ->name('guide.index')->defaults('guide_role', 'super_admin');
        Route::get('guide/{slug}', [App\Http\Controllers\Back\Shared\GuideController::class, 'show'])
            ->name('guide.show')->defaults('guide_role', 'super_admin');

        /* ---------- فاز ۱۱ — وضعیت سیستم و مستندات API ---------- */
        Route::get('system', [App\Http\Controllers\Back\Admin\SystemController::class, 'index'])
            ->name('system.index');
        Route::post('system/cleanup', [App\Http\Controllers\Back\Admin\SystemController::class, 'runCleanup'])
            ->name('system.cleanup');
        Route::post('system/retention', [App\Http\Controllers\Back\Admin\SystemController::class, 'saveRetention'])
            ->name('system.retention');
        Route::post('system/encrypt', [App\Http\Controllers\Back\Admin\SystemController::class, 'encryptFiles'])
            ->name('system.encrypt');
        Route::get('api-docs', [App\Http\Controllers\Back\Admin\ApiDocsController::class, 'index'])
            ->name('api-docs.index');

        /* دسته‌بندی‌ها (AJAX) */
        Route::get('service-categories', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'index'])
            ->name('service-categories.index');
        Route::get('service-categories/data', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'data'])
            ->name('service-categories.data');
        Route::post('service-categories', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'store'])
            ->name('service-categories.store');
        Route::put('service-categories/{category}', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'update'])
            ->name('service-categories.update');
        Route::patch('service-categories/{category}/toggle', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'toggle'])
            ->name('service-categories.toggle');
        Route::delete('service-categories/{category}', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'destroy'])
            ->name('service-categories.destroy');

        /* خدمات (AJAX + فرم‌ساز) */
        Route::get('services', [App\Http\Controllers\Back\Admin\ServicesController::class, 'index'])
            ->name('services.index');
        Route::get('services/data', [App\Http\Controllers\Back\Admin\ServicesController::class, 'data'])
            ->name('services.data');
        Route::get('services/create', [App\Http\Controllers\Back\Admin\ServicesController::class, 'create'])
            ->name('services.create');
        Route::post('services', [App\Http\Controllers\Back\Admin\ServicesController::class, 'store'])
            ->name('services.store');
        Route::get('services/{service}/edit', [App\Http\Controllers\Back\Admin\ServicesController::class, 'edit'])
            ->name('services.edit');
        Route::put('services/{service}', [App\Http\Controllers\Back\Admin\ServicesController::class, 'update'])
            ->name('services.update');
        Route::patch('services/{service}/toggle', [App\Http\Controllers\Back\Admin\ServicesController::class, 'toggle'])
            ->name('services.toggle');
        Route::delete('services/{service}', [App\Http\Controllers\Back\Admin\ServicesController::class, 'destroy'])
            ->name('services.destroy');
        Route::get('services/{service}/versions', [App\Http\Controllers\Back\Admin\ServicesController::class, 'versions'])
            ->name('services.versions');

        /* ---------- مدیریت سفارش‌ها و موتور تخصیص (فاز ۶) ---------- */
        Route::get('orders', [App\Http\Controllers\Back\Admin\OrdersController::class, 'index'])
            ->name('orders.index');
        Route::get('orders/data', [App\Http\Controllers\Back\Admin\OrdersController::class, 'data'])
            ->name('orders.data');
        /* حذف نرم/دائم مشترک (v28) */
        Route::get('orders/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'orders')->name('orders.trashed');
        Route::get('orders/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'orders')->whereNumber('id')->name('orders.delete-info');
        Route::delete('orders/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'orders')->whereNumber('id')->name('orders.destroy');
        Route::post('orders/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'orders')->whereNumber('id')->name('orders.restore');
        Route::delete('orders/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'orders')->whereNumber('id')->name('orders.purge');

        Route::get('orders/counts', [App\Http\Controllers\Back\Admin\OrdersController::class, 'counts'])
            ->name('orders.counts');
        Route::get('orders/coffeenets', [App\Http\Controllers\Back\Admin\OrdersController::class, 'coffeenets'])
            ->name('orders.coffeenets');
        Route::get('orders/{order}', [App\Http\Controllers\Back\Admin\OrdersController::class, 'show'])
            ->whereNumber('order')->name('orders.show');
        Route::get('orders/{order}/view', [App\Http\Controllers\Back\Admin\OrdersController::class, 'view'])
            ->whereNumber('order')->name('orders.view');
        Route::get('orders/{order}/operators', [App\Http\Controllers\Back\Admin\OrdersController::class, 'operators'])
            ->whereNumber('order')->name('orders.operators');
        Route::patch('orders/{order}/assign', [App\Http\Controllers\Back\Admin\OrdersController::class, 'assign'])
            ->whereNumber('order')->name('orders.assign');
        Route::patch('orders/{order}/operator', [App\Http\Controllers\Back\Admin\OrdersController::class, 'assignOperator'])
            ->whereNumber('order')->name('orders.operator');
        Route::post('orders/{order}/rebroadcast', [App\Http\Controllers\Back\Admin\OrdersController::class, 'rebroadcast'])
            ->whereNumber('order')->name('orders.rebroadcast');
        Route::patch('orders/{order}/status', [App\Http\Controllers\Back\Admin\OrdersController::class, 'updateStatus'])
            ->whereNumber('order')->name('orders.status');
        Route::patch('orders/{order}/cancel', [App\Http\Controllers\Back\Admin\OrdersController::class, 'cancel'])
            ->whereNumber('order')->name('orders.cancel');

        /* ---------- گفتگوهای سفارش (مدیر کل — درخواست بازخوردی) ---------- */
        Route::get('chats', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'adminIndex'])
            ->name('chats.index');
        Route::get('chats/data', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'adminData'])
            ->name('chats.data');
        Route::get('orders/{order}/chat', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'adminShow'])
            ->whereNumber('order')->name('orders.chat');
        Route::get('orders/{order}/chat/data', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'adminMessages'])
            ->whereNumber('order')->name('orders.chat.data');
        Route::post('orders/{order}/chat/send', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'adminSend'])
            ->whereNumber('order')->name('orders.chat.send');

        /* ---------- نظرسنجی‌ها و امتیازها (v33 — مدیر کل) ---------- */
        Route::get('ratings', [App\Http\Controllers\Back\Admin\RatingsController::class, 'index'])
            ->name('ratings.index');
        Route::get('ratings/data', [App\Http\Controllers\Back\Admin\RatingsController::class, 'data'])
            ->name('ratings.data');
        Route::get('ratings/stats', [App\Http\Controllers\Back\Admin\RatingsController::class, 'stats'])
            ->name('ratings.stats');
        Route::get('ratings/options', [App\Http\Controllers\Back\Admin\RatingsController::class, 'options'])
            ->name('ratings.options');
        Route::post('ratings/options', [App\Http\Controllers\Back\Admin\RatingsController::class, 'storeOption'])
            ->name('ratings.options.store');
        Route::patch('ratings/options/{option}', [App\Http\Controllers\Back\Admin\RatingsController::class, 'updateOption'])
            ->whereNumber('option')->name('ratings.options.update');
        Route::delete('ratings/options/{option}', [App\Http\Controllers\Back\Admin\RatingsController::class, 'destroyOption'])
            ->whereNumber('option')->name('ratings.options.destroy');

        /* ---------- مدیریت مشتریان (درخواست بازخوردی — لیست/پروفایل/ویرایش/بن) ---------- */
        Route::get('customers', [App\Http\Controllers\Back\Admin\CustomersController::class, 'index'])
            ->name('customers.index');
        Route::get('customers/data', [App\Http\Controllers\Back\Admin\CustomersController::class, 'data'])
            ->name('customers.data');
        /* حذف نرم/دائم مشترک (v28) */
        Route::get('customers/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'customers')->name('customers.trashed');
        Route::get('customers/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'customers')->whereNumber('id')->name('customers.delete-info');
        Route::delete('customers/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'customers')->whereNumber('id')->name('customers.destroy');
        Route::post('customers/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'customers')->whereNumber('id')->name('customers.restore');
        Route::delete('customers/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'customers')->whereNumber('id')->name('customers.purge');

        Route::get('customers/{customer}', [App\Http\Controllers\Back\Admin\CustomersController::class, 'show'])
            ->whereNumber('customer')->name('customers.show');
        Route::get('customers/{customer}/trend', [App\Http\Controllers\Back\Admin\CustomersController::class, 'trend'])
            ->whereNumber('customer')->name('customers.trend');
        Route::put('customers/{customer}', [App\Http\Controllers\Back\Admin\CustomersController::class, 'update'])
            ->whereNumber('customer')->name('customers.update');
        Route::patch('customers/{customer}/ban', [App\Http\Controllers\Back\Admin\CustomersController::class, 'ban'])
            ->whereNumber('customer')->name('customers.ban');
        Route::patch('customers/{customer}/unban', [App\Http\Controllers\Back\Admin\CustomersController::class, 'unban'])
            ->whereNumber('customer')->name('customers.unban');
        Route::post('customers/{customer}/wallet', [App\Http\Controllers\Back\Admin\CustomersController::class, 'wallet'])
            ->whereNumber('customer')->name('customers.wallet');
    });
});
