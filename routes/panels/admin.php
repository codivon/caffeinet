<?php

/*
|--------------------------------------------------------------------------
| پنل مدیریت کل (/admin)
|--------------------------------------------------------------------------
| [فاز ۳ — مهاجرت Livewire] روت‌ها به ۴ فایل مستقل تفکیک شدند:
|   admin/core.php     → ایجنت 3-b (داشبورد/تنظیمات/اطلاعیه/لاگ/پیامک/راهنما/سیستم)
|   admin/orders.php   → ایجنت 3-c (سفارش‌ها/گفتگوها/نظرسنجی)
|   admin/entities.php → ایجنت 3-d (مدیران/سازمان‌ها/کافی‌نت‌ها/اپراتورها/خدمات/مشتریان)
|   admin/finance.php  → ایجنت 3-e (برداشت/کمیسیون/تسویه/گزارش مالی/تیکت)
| نام‌های روت و مسیرها عیناً حفظ شده‌اند.
*/

Route::prefix('admin')->name('admin.')->group(function () {

    /* [Task 2-a] فرم ورود → کامپوننت Livewire؛ POST fallback و خروج همان‌جا می‌مانند. */
    Route::get('login', App\Livewire\Admin\Auth\Login::class)
        ->name('login');
    Route::post('login', [App\Http\Controllers\Back\Admin\AuthController::class, 'login'])
        ->name('login.attempt');

    Route::middleware(['admin.access'])->group(function () {
        Route::post('logout', [App\Http\Controllers\Back\Admin\AuthController::class, 'logout'])
            ->name('logout');

        require __DIR__.'/admin/core.php';
        require __DIR__.'/admin/orders.php';
        require __DIR__.'/admin/entities.php';
        require __DIR__.'/admin/finance.php';
    });
});
