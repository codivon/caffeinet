<?php

/*
|--------------------------------------------------------------------------
| پنل سازمان (/organization)
|--------------------------------------------------------------------------
*/

Route::prefix('organization')->name('org.')->group(function () {

    /* [Task 6] ورود → کامپوننت Livewire (منطق showLogin/login AuthController منتقل شده؛ POST attempt همان کنترلر) */
    Route::get('login', \App\Livewire\Org\Auth\Login::class)
        ->name('login');
    /* فاز ۵۹ (F1 ممیزی) — POST fallback ورود حذف شد: نه کپچا می‌خواست نه 2FA (دور زدن امنیت فاز ۵۰).
       فرم لاگین Livewire تنها درگاه ورود است؛ درخواست POST به این مسیر 405 می‌گیرد. */

    Route::middleware(['role:org_manager', 'org.context'])->group(function () {
        Route::post('logout', [App\Http\Controllers\Back\Org\AuthController::class, 'logout'])
            ->name('logout');

        Route::get('choose', [App\Http\Controllers\Back\Org\AuthController::class, 'choose'])
            ->name('choose');
        Route::post('select', [App\Http\Controllers\Back\Org\AuthController::class, 'select'])
            ->name('select');

        /* [Task 6] داشبورد → کامپوننت Livewire (منطق DashboardController@index منتقل شده) */
        Route::get('/', \App\Livewire\Org\Core\Dashboard::class)
            ->name('dashboard');

        /* کیف پول — [Task 6] صفحه → Livewire (منطق index/data منتقل شده؛ data همان کنترلر) */
        Route::get('wallet', \App\Livewire\Org\Wallet\Index::class)
            ->name('wallet.index');
        Route::get('wallet/data', [App\Http\Controllers\Back\Org\WalletController::class, 'data'])
            ->name('wallet.data');

        /* برداشت‌ها — [Task 6] صفحه → Livewire (منطق index/data منتقل شده؛ store همان کنترلر) */
        Route::get('withdrawals', \App\Livewire\Org\Withdrawals\Index::class)
            ->name('withdrawals.index');
        Route::get('withdrawals/data', [App\Http\Controllers\Back\Org\WithdrawalsController::class, 'data'])
            ->name('withdrawals.data');
        Route::post('withdrawals', [App\Http\Controllers\Back\Org\WithdrawalsController::class, 'store'])
            ->name('withdrawals.store');

        /* کافی‌نت‌های زیرمجموعه + معرفی جدید — [Task 6] صفحه → Livewire (منطق index/data منتقل شده؛ store همان کنترلر) */
        Route::get('coffeenets', \App\Livewire\Org\Coffeenets\Index::class)
            ->name('coffeenets.index');
        Route::get('coffeenets/data', [App\Http\Controllers\Back\Org\CoffeenetsController::class, 'data'])
            ->name('coffeenets.data');
        Route::post('coffeenets', [App\Http\Controllers\Back\Org\CoffeenetsController::class, 'store'])
            ->name('coffeenets.store');

        /* جغرافیا (سلکت آبشاری) */
        Route::get('geo/cities', [App\Http\Controllers\Back\GeoController::class, 'cities'])
            ->name('geo.cities');

        /* ---------- v39 — کارت‌های بانکی مدیر سازمان — [Task 6] صفحه → Livewire
           (پارشیال مشترک + CRUD store/update/default/destroy و data همان کنترلر) ---------- */
        Route::get('bank-cards', \App\Livewire\Org\BankCards\Index::class)
            ->name('bank-cards.index');
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
