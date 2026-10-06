<?php

/*
|--------------------------------------------------------------------------
| پنل کافی‌نت (/coffeenet)
|--------------------------------------------------------------------------
*/

Route::prefix('coffeenet')->name('coffeenet.')->group(function () {

    Route::get('login', [App\Http\Controllers\Back\Coffeenet\AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('login', [App\Http\Controllers\Back\Coffeenet\AuthController::class, 'login'])
        ->name('login.attempt');

    Route::middleware(['role:coffeenet_manager', 'coffeenet.context'])->group(function () {
        Route::post('logout', [App\Http\Controllers\Back\Coffeenet\AuthController::class, 'logout'])
            ->name('logout');

        /* مدیران چند-کافی‌نتی: صفحه و اندپوینت انتخاب زمینه */
        Route::get('choose', [App\Http\Controllers\Back\Coffeenet\AuthController::class, 'choose'])
            ->name('choose');
        Route::post('select', [App\Http\Controllers\Back\Coffeenet\AuthController::class, 'select'])
            ->name('select');

        /* ریشه پنل → داشبورد کافی‌نت جاری session */
        Route::get('/', function (Illuminate\Http\Request $request) {
            $current = $request->attributes->get('current_coffeenet');

            return redirect()->route('coffeenet.dashboard', ['coffeenet' => $current]);
        })->name('home');

        Route::get('{coffeenet}/dashboard', [App\Http\Controllers\Back\Coffeenet\DashboardController::class, 'index'])
            ->name('dashboard');

        /* کارمندان (AJAX) */
        Route::get('{coffeenet}/staff', [App\Http\Controllers\Back\Coffeenet\StaffController::class, 'index'])
            ->name('staff.index');
        Route::get('{coffeenet}/staff/data', [App\Http\Controllers\Back\Coffeenet\StaffController::class, 'data'])
            ->name('staff.data');
        Route::post('{coffeenet}/staff', [App\Http\Controllers\Back\Coffeenet\StaffController::class, 'store'])
            ->name('staff.store');
        Route::put('{coffeenet}/staff/{assignment}', [App\Http\Controllers\Back\Coffeenet\StaffController::class, 'update'])
            ->name('staff.update');
        Route::patch('{coffeenet}/staff/{assignment}/toggle', [App\Http\Controllers\Back\Coffeenet\StaffController::class, 'toggle'])
            ->name('staff.toggle');

        /* حقوق و دستمزد (AJAX) */
        Route::get('{coffeenet}/salaries', [App\Http\Controllers\Back\Coffeenet\SalariesController::class, 'index'])
            ->name('salaries.index');
        Route::get('{coffeenet}/salaries/data', [App\Http\Controllers\Back\Coffeenet\SalariesController::class, 'data'])
            ->name('salaries.data');
        Route::post('{coffeenet}/salaries', [App\Http\Controllers\Back\Coffeenet\SalariesController::class, 'store'])
            ->name('salaries.store');

        /* تنظیمات کافی‌نت (AJAX) */
        Route::get('{coffeenet}/settings', [App\Http\Controllers\Back\Coffeenet\SettingsController::class, 'index'])
            ->name('settings.index');
        Route::put('{coffeenet}/settings', [App\Http\Controllers\Back\Coffeenet\SettingsController::class, 'update'])
            ->name('settings.update');
        Route::put('{coffeenet}/settings/password', [App\Http\Controllers\Back\Coffeenet\SettingsController::class, 'password'])
            ->name('settings.password');

        /* سفارش‌ها و پخش زنده (فاز ۶) */
        Route::get('{coffeenet}/orders', [App\Http\Controllers\Back\Coffeenet\OrdersController::class, 'index'])
            ->name('orders.index');
        Route::get('{coffeenet}/orders/broadcast/data', [App\Http\Controllers\Back\Coffeenet\OrdersController::class, 'broadcastData'])
            ->name('orders.broadcast.data');
        Route::get('{coffeenet}/orders/data', [App\Http\Controllers\Back\Coffeenet\OrdersController::class, 'data'])
            ->name('orders.data');
        Route::post('{coffeenet}/orders/{order}/accept', [App\Http\Controllers\Back\Coffeenet\OrdersController::class, 'accept'])
            ->whereNumber('order')->name('orders.accept');
        Route::get('{coffeenet}/orders/operators', [App\Http\Controllers\Back\Coffeenet\OrdersController::class, 'operators'])
            ->name('orders.operators');
        Route::patch('{coffeenet}/orders/{order}/operator', [App\Http\Controllers\Back\Coffeenet\OrdersController::class, 'assignOperator'])
            ->whereNumber('order')->name('orders.operator');
        Route::patch('{coffeenet}/orders/{order}/status', [App\Http\Controllers\Back\Coffeenet\OrdersController::class, 'updateStatus'])
            ->whereNumber('order')->name('orders.status');

        /* ---------- گفتگوهای سفارش (مدیر کافی‌نت — درخواست بازخوردی) ---------- */
        Route::get('{coffeenet}/chats', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'coffeenetIndex'])
            ->name('chats.index');
        Route::get('{coffeenet}/chats/data', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'coffeenetData'])
            ->name('chats.data');
        Route::get('{coffeenet}/orders/{order}/chat', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'coffeenetShow'])
            ->whereNumber('order')->name('orders.chat');
        Route::get('{coffeenet}/orders/{order}/chat/data', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'coffeenetMessages'])
            ->whereNumber('order')->name('orders.chat.data');
        Route::post('{coffeenet}/orders/{order}/chat/send', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'coffeenetSend'])
            ->whereNumber('order')->name('orders.chat.send');

        /* ---------- نظرسنجی‌های کافی‌net (v33 — فقط دیدن نظرات خودش) ---------- */
        Route::get('{coffeenet}/ratings', [App\Http\Controllers\Back\Admin\RatingsController::class, 'coffeenetIndex'])
            ->name('ratings.index');
        Route::get('{coffeenet}/ratings/data', [App\Http\Controllers\Back\Admin\RatingsController::class, 'data'])
            ->name('ratings.data');
        Route::get('{coffeenet}/ratings/stats', [App\Http\Controllers\Back\Admin\RatingsController::class, 'stats'])
            ->name('ratings.stats');

        /* جغرافیا (سلکت آبشاری) */
        Route::get('geo/cities', [App\Http\Controllers\Back\GeoController::class, 'cities'])
            ->name('geo.cities');

        /* ---------- فاز ۸ — کیف پول و برداشت کافی‌نت ---------- */
        Route::get('{coffeenet}/wallet', [App\Http\Controllers\Back\Coffeenet\WalletController::class, 'index'])
            ->name('wallet.index');
        Route::get('{coffeenet}/wallet/data', [App\Http\Controllers\Back\Coffeenet\WalletController::class, 'data'])
            ->name('wallet.data');

        Route::get('{coffeenet}/withdrawals', [App\Http\Controllers\Back\Coffeenet\WithdrawalsController::class, 'index'])
            ->name('withdrawals.index');
        Route::get('{coffeenet}/withdrawals/data', [App\Http\Controllers\Back\Coffeenet\WithdrawalsController::class, 'data'])
            ->name('withdrawals.data');
        Route::post('{coffeenet}/withdrawals', [App\Http\Controllers\Back\Coffeenet\WithdrawalsController::class, 'store'])
            ->name('withdrawals.store');

        /* ---------- فاز ۱۰ — تیکت‌های پشتیبانی کافی‌نت ---------- */
        Route::get('{coffeenet}/tickets', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'index'])
            ->name('tickets.index');
        Route::get('{coffeenet}/tickets/data', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'data'])
            ->name('tickets.data');
        Route::get('{coffeenet}/tickets/{ticket}', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'show'])
            ->whereNumber('ticket')->name('tickets.show');
        Route::post('{coffeenet}/tickets/{ticket}/reply', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'reply'])
            ->whereNumber('ticket')->name('tickets.reply');
        Route::get('{coffeenet}/tickets/{ticket}/messages', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'messages'])
            ->whereNumber('ticket')->name('tickets.messages');
        Route::patch('{coffeenet}/tickets/{ticket}/status', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'status'])
            ->whereNumber('ticket')->name('tickets.status');

        /* ---------- v39 — کارت‌های بانکی مدیر کافی‌نت ---------- */
        Route::get('{coffeenet}/bank-cards', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'index'])
            ->defaults('view', 'back.coffeenet.bank-cards.index')->name('bank-cards.index');
        Route::get('{coffeenet}/bank-cards/data', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'data'])
            ->name('bank-cards.data');
        Route::post('{coffeenet}/bank-cards', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'store'])
            ->name('bank-cards.store');
        Route::put('{coffeenet}/bank-cards/{card}', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'update'])
            ->whereNumber('card')->name('bank-cards.update');
        Route::patch('{coffeenet}/bank-cards/{card}/default', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'setDefault'])
            ->whereNumber('card')->name('bank-cards.default');
        Route::delete('{coffeenet}/bank-cards/{card}', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'destroy'])
            ->whereNumber('card')->name('bank-cards.destroy');

        /* ---------- فاز ۱۰ — زنگ اعلان (کافی‌نت) ---------- */
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
        /* ---------- فاز ۱۳ — آموزش پنل (راهنماهای مدیر کافی‌نت) ---------- */
        /* ---------- فاز ۱۳ — آموزش پنل (راهنماهای مدیر کافی‌نت) ---------- */
        Route::get('{coffeenet}/guide', [App\Http\Controllers\Back\Shared\GuideController::class, 'index'])
            ->whereNumber('coffeenet')->name('guide.index')->defaults('guide_role', 'coffeenet');
        Route::get('{coffeenet}/guide/{slug}', [App\Http\Controllers\Back\Shared\GuideController::class, 'show'])
            ->whereNumber('coffeenet')->name('guide.show')->defaults('guide_role', 'coffeenet');
    });
});
