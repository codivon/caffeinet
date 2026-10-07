<?php

/*
|--------------------------------------------------------------------------
| اپ مشتری (/app) — نسخهٔ Livewire 4 SPA [Task 7]
|--------------------------------------------------------------------------
| روت‌های GET صفحه‌ساز به کامپوننت‌های Livewire تبدیل شدند (نام/URI عیناً حفظ شده).
| صفحات، پوسته‌های Blade هستند و داده‌ها سمت کلاینت با CN.api (jQuery) از
| API v1 دریافت می‌شود — API و JS صفحات دست‌نخورده.
| endpointهای POST/PATCH + فایل‌ها/پرداخت در کنترلرهای Front\App باقی‌اند.
*/

use App\Http\Controllers\Front\App\PagesController;
use App\Livewire\App\Auth\Login;
use App\Livewire\App\Catalog\ServiceDetail;
use App\Livewire\App\Catalog\Services;
use App\Livewire\App\Core\Home;
use App\Livewire\App\Orders\Index as OrdersIndex;
use App\Livewire\App\Orders\Show as OrdersShow;
use App\Livewire\App\Profile\Edit;
use App\Livewire\App\Profile\Show as ProfileShow;
use App\Livewire\App\Support\Index as SupportIndex;
use App\Livewire\App\Support\Show as SupportShow;
use App\Livewire\App\Wallet\Index as WalletIndex;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->name('app.')->group(function () {
    /* ورودی — ریدایرکت به home (کنترلر می‌ماند) */
    Route::get('/', [PagesController::class, 'index'])->name('index');

    /* [Task 7] صفحات به کامپوننت‌های Livewire */
    Route::get('auth', Login::class)->name('auth');
    Route::get('home', Home::class)->name('home');
    Route::get('services', Services::class)->name('services');
    Route::get('service/{service}', ServiceDetail::class)
        ->whereNumber('service')->name('service');
    Route::get('orders', OrdersIndex::class)->name('orders');
    Route::get('orders/{order}', OrdersShow::class)
        ->whereNumber('order')->name('orders.show');
    Route::get('wallet', WalletIndex::class)->name('wallet');
    Route::get('profile', ProfileShow::class)->name('profile');
    Route::get('profile/edit', Edit::class)->name('profile.edit');

    /* پشتیبانی و تیکت‌ها (فاز ۱۰) */
    Route::get('support', SupportIndex::class)->name('support');
    Route::get('support/{ticket}', SupportShow::class)
        ->whereNumber('ticket')->name('support.show');
});
