<?php

use App\Http\Controllers\Back\Admin\CsvExportsController;

/*
|--------------------------------------------------------------------------
| پنل مدیریت کل — خروجی‌های CSV صفحات عملیاتی (فاز ۶۰)
|--------------------------------------------------------------------------
| [فاز ۶۰ — بستهٔ سرعت و اعتماد] ۴ خروجی CSV با «فیلتر فعال خود صفحه»:
|   exports/sms-logs        ← لاگ پیامک‌ها (q/status/provider/from/to از query string)
|   exports/audit-logs      ← لاگ فعالیت‌ها (q/from/to از query string)
|   exports/panel-sessions  ← نشست‌های فعال ادمین جاری
|   exports/api-usage       ← اینسایت مصرف کلیدها (۳۰ روز اخیر)
|
| این فایل عمداً جدا از admin/core.php است تا با ویرایش‌های موازی ایجنت‌های
| دیگر تداخل نکند — فقط از routes/panels/admin.php require می‌شود.
| منطق فیلتر عیناً از کامپوننت‌های Livewire همان صفحات می‌آید
| (SmsLogs::filterQuery / AuditLogs::filterQuery / PanelSessions::activeSessions
| / ApiKeys::apiUsersInsight) تا خروجی همیشه با آنچه کاربر می‌بیند یکی باشد.
*/

Route::middleware(['admin.access'])->prefix('exports')->name('exports.')->group(function () {
    Route::get('sms-logs', [CsvExportsController::class, 'smsLogs'])
        ->name('sms-logs');

    Route::get('audit-logs', [CsvExportsController::class, 'auditLogs'])
        ->name('audit-logs');

    Route::get('panel-sessions', [CsvExportsController::class, 'panelSessions'])
        ->name('panel-sessions');

    Route::get('api-usage', [CsvExportsController::class, 'apiUsage'])
        ->name('api-usage');
});
