<?php

/*
|--------------------------------------------------------------------------
| پنل اپراتور (/operator)
|--------------------------------------------------------------------------
*/

Route::prefix('operator')->name('operator.')->group(function () {

    Route::get('login', [App\Http\Controllers\Back\Operator\AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('login', [App\Http\Controllers\Back\Operator\AuthController::class, 'login'])
        ->name('login.attempt');

    Route::middleware(['role:operator', 'operator.context'])->group(function () {
        Route::post('logout', [App\Http\Controllers\Back\Operator\AuthController::class, 'logout'])
            ->name('logout');

        /* ریشه پنل → داشبورد (زمینه از session) */
        Route::get('/', function () {
            return redirect()->route('operator.dashboard');
        })->name('home');

        Route::get('dashboard', [App\Http\Controllers\Back\Operator\DashboardController::class, 'index'])
            ->name('dashboard');

        /* سفارش‌ها (AJAX — محدودهٔ دید طبق دسترسی‌ها) */
        Route::get('orders', [App\Http\Controllers\Back\Operator\OrdersController::class, 'index'])
            ->name('orders.index');
        Route::get('orders/data', [App\Http\Controllers\Back\Operator\OrdersController::class, 'data'])
            ->name('orders.data');

        /* فاز ۱۱ — صندوق درخواست‌های مشتری (پذیرش مستقیم اپراتور) */
        Route::get('requests', [App\Http\Controllers\Back\Operator\RequestsController::class, 'index'])
            ->name('requests.index');
        Route::get('requests/data', [App\Http\Controllers\Back\Operator\RequestsController::class, 'data'])
            ->name('requests.data');
        Route::get('requests/badge', [App\Http\Controllers\Back\Operator\RequestsController::class, 'badge'])
            ->name('requests.badge');
        Route::post('requests/{order}/accept', [App\Http\Controllers\Back\Operator\RequestsController::class, 'accept'])
            ->whereNumber('order')->name('requests.accept');

        /* تغییر وضعیت سریع سفارش (فاز ۷) */
        Route::patch('orders/{order}/status', [App\Http\Controllers\Back\Operator\OrdersController::class, 'updateStatus'])
            ->whereNumber('order')->name('orders.status');

        /* گفتگوها (فاز ۷ — چت تلگرام‌گونه) */
        Route::get('chat', [App\Http\Controllers\Back\Operator\ChatController::class, 'index'])
            ->name('chat.index');
        Route::get('chat/data', [App\Http\Controllers\Back\Operator\ChatController::class, 'data'])
            ->name('chat.data');
        Route::get('chat/badge', [App\Http\Controllers\Back\Operator\ChatController::class, 'badge'])
            ->name('chat.badge');
        Route::get('orders/{order}/chat', [App\Http\Controllers\Back\Operator\ChatController::class, 'show'])
            ->whereNumber('order')->name('orders.chat');
        Route::get('orders/{order}/chat/data', [App\Http\Controllers\Back\Operator\ChatController::class, 'messages'])
            ->whereNumber('order')->name('orders.chat.data');
        Route::post('orders/{order}/chat/send', [App\Http\Controllers\Back\Operator\ChatController::class, 'send'])
            ->whereNumber('order')->name('orders.chat.send');

        /* ---------- فاز ۸ — درآمد و کیف پول اپراتور ---------- */
        Route::get('earnings', [App\Http\Controllers\Back\Operator\EarningsController::class, 'index'])
            ->name('earnings.index');
        Route::get('earnings/data', [App\Http\Controllers\Back\Operator\EarningsController::class, 'data'])
            ->name('earnings.data');
        Route::get('earnings/transactions', [App\Http\Controllers\Back\Operator\EarningsController::class, 'transactions'])
            ->name('earnings.transactions');

        /* ---------- فاز ۱۰ — تیکت‌های پشتیبانی اپراتور ---------- */
        Route::get('tickets', [App\Http\Controllers\Back\Operator\TicketsController::class, 'index'])
            ->name('tickets.index');
        Route::get('tickets/data', [App\Http\Controllers\Back\Operator\TicketsController::class, 'data'])
            ->name('tickets.data');
        Route::get('tickets/{ticket}', [App\Http\Controllers\Back\Operator\TicketsController::class, 'show'])
            ->whereNumber('ticket')->name('tickets.show');
        Route::post('tickets/{ticket}/reply', [App\Http\Controllers\Back\Operator\TicketsController::class, 'reply'])
            ->whereNumber('ticket')->name('tickets.reply');
        Route::get('tickets/{ticket}/messages', [App\Http\Controllers\Back\Operator\TicketsController::class, 'messages'])
            ->whereNumber('ticket')->name('tickets.messages');

        /* ---------- v39 — کارت‌های بانکی اپراتور ---------- */
        Route::get('bank-cards', [App\Http\Controllers\Back\Shared\BankCardsController::class, 'index'])
            ->defaults('view', 'back.operator.bank-cards.index')->name('bank-cards.index');
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

        /* ---------- فاز ۱۰ — زنگ اعلان (اپراتور) ---------- */
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

        /* ---------- فاز ۱۳ — آموزش پنل (راهنماهای اپراتور) ---------- */
        Route::get('guide', [App\Http\Controllers\Back\Shared\GuideController::class, 'index'])
            ->name('guide.index')->defaults('guide_role', 'operator');
        Route::get('guide/{slug}', [App\Http\Controllers\Back\Shared\GuideController::class, 'show'])
            ->name('guide.show')->defaults('guide_role', 'operator');
    });
});
