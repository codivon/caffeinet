<?php

/*
|--------------------------------------------------------------------------
| پنل سازمان (/organization)
|--------------------------------------------------------------------------
*/

Route::prefix('organization')->name('org.')->group(function () {

    Route::get('login', [App\Http\Controllers\Back\Org\AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('login', [App\Http\Controllers\Back\Org\AuthController::class, 'login'])
        ->name('login.attempt');

    Route::middleware(['role:org_manager', 'org.context'])->group(function () {
        Route::post('logout', [App\Http\Controllers\Back\Org\AuthController::class, 'logout'])
            ->name('logout');

        Route::get('choose', [App\Http\Controllers\Back\Org\AuthController::class, 'choose'])
            ->name('choose');
        Route::post('select', [App\Http\Controllers\Back\Org\AuthController::class, 'select'])
            ->name('select');

        Route::get('/', [App\Http\Controllers\Back\Org\DashboardController::class, 'index'])
            ->name('dashboard');

        /* کیف پول */
        Route::get('wallet', [App\Http\Controllers\Back\Org\WalletController::class, 'index'])
            ->name('wallet.index');
        Route::get('wallet/data', [App\Http\Controllers\Back\Org\WalletController::class, 'data'])
            ->name('wallet.data');

        /* برداشت‌ها */
        Route::get('withdrawals', [App\Http\Controllers\Back\Org\WithdrawalsController::class, 'index'])
            ->name('withdrawals.index');
        Route::get('withdrawals/data', [App\Http\Controllers\Back\Org\WithdrawalsController::class, 'data'])
            ->name('withdrawals.data');
        Route::post('withdrawals', [App\Http\Controllers\Back\Org\WithdrawalsController::class, 'store'])
            ->name('withdrawals.store');

        /* کافی‌نت‌های زیرمجموعه + معرفی جدید */
        Route::get('coffeenets', [App\Http\Controllers\Back\Org\CoffeenetsController::class, 'index'])
            ->name('coffeenets.index');
        Route::get('coffeenets/data', [App\Http\Controllers\Back\Org\CoffeenetsController::class, 'data'])
            ->name('coffeenets.data');
        Route::post('coffeenets', [App\Http\Controllers\Back\Org\CoffeenetsController::class, 'store'])
            ->name('coffeenets.store');

        /* جغرافیا (سلکت آبشاری) */
        Route::get('geo/cities', [App\Http\Controllers\Back\GeoController::class, 'cities'])
            ->name('geo.cities');

        /* ---------- v39 — کارت‌های بانکی مدیر سازمان ---------- */
        Route::get('bank-cards', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'index'])
            ->defaults('view', 'back.org.bank-cards.index')->name('bank-cards.index');
        Route::get('bank-cards/data', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'data'])
            ->name('bank-cards.data');
        Route::post('bank-cards', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'store'])
            ->name('bank-cards.store');
        Route::put('bank-cards/{card}', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'update'])
            ->whereNumber('card')->name('bank-cards.update');
        Route::patch('bank-cards/{card}/default', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'setDefault'])
            ->whereNumber('card')->name('bank-cards.default');
        Route::delete('bank-cards/{card}', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'destroy'])
            ->whereNumber('card')->name('bank-cards.destroy');

        /* ---------- فاز ۱۰ — زنگ اعلان (سازمان) ---------- */
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

        /* اطلاعیه‌های پنل (فاز ۱۵ — مشترک) */
        Route::get('announcements/pending', [App\Http\Controllers\Back\Shared\PanelAnnouncementsController::class, 'pending'])
            ->name('announcements.pending');
        Route::post('announcements/{announcement}/read', [App\Http\Controllers\Back\Shared\PanelAnnouncementsController::class, 'read'])
            ->whereNumber('announcement')->name('announcements.read');
        /* ---------- فاز ۱۳ — آموزش پنل (راهنماهای سازمان) ---------- */
        /* ---------- فاز ۱۳ — آموزش پنل (راهنماهای سازمان) ---------- */
        Route::get('guide', [App\Http\Controllers\Back\Shared\GuideController::class, 'index'])
            ->name('guide.index')->defaults('guide_role', 'organization');
        Route::get('guide/{slug}', [App\Http\Controllers\Back\Shared\GuideController::class, 'show'])
            ->name('guide.show')->defaults('guide_role', 'organization');
    });
});
