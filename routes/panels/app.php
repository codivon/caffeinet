<?php

/*
|--------------------------------------------------------------------------
| اپ مشتری (/app)
|--------------------------------------------------------------------------
*/

Route::prefix('app')->name('app.')->group(function () {
    Route::get('/', [App\Http\Controllers\Front\App\PagesController::class, 'index'])->name('index');
    Route::get('auth', [App\Http\Controllers\Front\App\PagesController::class, 'auth'])->name('auth');
    Route::get('home', [App\Http\Controllers\Front\App\PagesController::class, 'home'])->name('home');
    Route::get('services', [App\Http\Controllers\Front\App\PagesController::class, 'services'])->name('services');
    Route::get('service/{service}', [App\Http\Controllers\Front\App\PagesController::class, 'service'])
        ->whereNumber('service')->name('service');
    Route::get('orders', [App\Http\Controllers\Front\App\PagesController::class, 'orders'])->name('orders');
    Route::get('orders/{order}', [App\Http\Controllers\Front\App\PagesController::class, 'orderShow'])
        ->whereNumber('order')->name('orders.show');
    Route::get('wallet', [App\Http\Controllers\Front\App\PagesController::class, 'wallet'])->name('wallet');
    Route::get('profile', [App\Http\Controllers\Front\App\PagesController::class, 'profile'])->name('profile');
    Route::get('profile/edit', [App\Http\Controllers\Front\App\PagesController::class, 'profileEdit'])->name('profile.edit');

    /* پشتیبانی و تیکت‌ها (فاز ۱۰) */
    Route::get('support', [App\Http\Controllers\Front\App\PagesController::class, 'support'])->name('support');
    Route::get('support/{ticket}', [App\Http\Controllers\Front\App\PagesController::class, 'supportShow'])
        ->whereNumber('ticket')->name('support.show');
});
