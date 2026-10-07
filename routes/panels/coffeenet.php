<?php

/*
|--------------------------------------------------------------------------
| پنل کافی‌نت (/coffeenet)
|--------------------------------------------------------------------------
| [فاز ۴ — مهاجرت Livewire] صفحات GET به کامپوننت‌های Livewire تبدیل شدند
| (کامنت [Task 4])؛ نام‌ها و مسیرهای روت بدون تغییر مانده‌اند. endpointهای
| POST/PATCH/PUT/DELETE و GETهای data/badge/messages کنترلر دست‌نخورده‌اند.
*/

Route::prefix('coffeenet')->name('coffeenet.')->group(function () {

    /* [Task 4] فرم ورود → کامپوننت Livewire (منطق AuthController@showLogin/login منتقل شده)؛
       POST fallback همان‌جا می‌ماند. */
    Route::get('login', \App\Livewire\Coffeenet\Auth\Login::class)
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

        /* [Task 4] داشبورد → کامپوننت Livewire (منطق DashboardController@index منتقل شده) */
        Route::get('{coffeenet}/dashboard', \App\Livewire\Coffeenet\Core\Dashboard::class)
            ->name('dashboard');

        /* کارمندان (AJAX) — [Task 4] فرم/لیست → Livewire؛ ذخیره/ویرایش/فعال‌سازی همان کنترلر */
        Route::get('{coffeenet}/staff', \App\Livewire\Coffeenet\Staff\Index::class)
            ->name('staff.index');
        Route::get('{coffeenet}/staff/data', [App\Http\Controllers\Back\Coffeenet\StaffController::class, 'data'])
            ->name('staff.data');
        Route::post('{coffeenet}/staff', [App\Http\Controllers\Back\Coffeenet\StaffController::class, 'store'])
            ->name('staff.store');
        Route::put('{coffeenet}/staff/{assignment}', [App\Http\Controllers\Back\Coffeenet\StaffController::class, 'update'])
            ->name('staff.update');
        Route::patch('{coffeenet}/staff/{assignment}/toggle', [App\Http\Controllers\Back\Coffeenet\StaffController::class, 'toggle'])
            ->name('staff.toggle');

        /* حقوق و دستمزد (AJAX) — [Task 4] لیست → Livewire؛ ثبت همان کنترلر */
        Route::get('{coffeenet}/salaries', \App\Livewire\Coffeenet\Salaries\Index::class)
            ->name('salaries.index');
        Route::get('{coffeenet}/salaries/data', [App\Http\Controllers\Back\Coffeenet\SalariesController::class, 'data'])
            ->name('salaries.data');
        Route::post('{coffeenet}/salaries', [App\Http\Controllers\Back\Coffeenet\SalariesController::class, 'store'])
            ->name('salaries.store');

        /* تنظیمات کافی‌نت (AJAX) — [Task 4] فرم/تب‌ها → Livewire؛ ذخیره همان کنترلر */
        Route::get('{coffeenet}/settings', \App\Livewire\Coffeenet\Settings\Index::class)
            ->name('settings.index');
        Route::put('{coffeenet}/settings', [App\Http\Controllers\Back\Coffeenet\SettingsController::class, 'update'])
            ->name('settings.update');
        Route::put('{coffeenet}/settings/password', [App\Http\Controllers\Back\Coffeenet\SettingsController::class, 'password'])
            ->name('settings.password');

        /* سفارش‌ها و پخش زنده (فاز ۶) — [Task 4] صفحه → Livewire (فیلتر/جدول server-side)؛
           برد پخش زنده همان JS + endpointهای قبلی */
        Route::get('{coffeenet}/orders', \App\Livewire\Coffeenet\Orders\Index::class)
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
        /* [Task 4] فهرست گفتگوها → Livewire؛ data/send همان کنترلر */
        Route::get('{coffeenet}/chats', \App\Livewire\Coffeenet\Chats\Index::class)
            ->name('chats.index');
        Route::get('{coffeenet}/chats/data', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'coffeenetData'])
            ->name('chats.data');
        /* [Task 4] صفحهٔ گفتگو → Shell Livewire + موتور chat قبلی (منطق coffeenetShow منتقل شده) */
        Route::get('{coffeenet}/orders/{order}/chat', \App\Livewire\Coffeenet\Chats\Conversation::class)
            ->whereNumber('order')->name('orders.chat');
        Route::get('{coffeenet}/orders/{order}/chat/data', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'coffeenetMessages'])
            ->whereNumber('order')->name('orders.chat.data');
        Route::post('{coffeenet}/orders/{order}/chat/send', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'coffeenetSend'])
            ->whereNumber('order')->name('orders.chat.send');

        /* ---------- نظرسنجی‌های کافی‌net (v33 — فقط دیدن نظرات خودش) ---------- */
        /* [Task 4] فهرست نظرسنجی‌ها → Livewire؛ data/stats همان کنترلر */
        Route::get('{coffeenet}/ratings', \App\Livewire\Coffeenet\Ratings\Index::class)
            ->name('ratings.index');
        Route::get('{coffeenet}/ratings/data', [App\Http\Controllers\Back\Admin\RatingsController::class, 'data'])
            ->name('ratings.data');
        Route::get('{coffeenet}/ratings/stats', [App\Http\Controllers\Back\Admin\RatingsController::class, 'stats'])
            ->name('ratings.stats');

        /* جغرافیا (سلکت آبشاری) */
        Route::get('geo/cities', [App\Http\Controllers\Back\GeoController::class, 'cities'])
            ->name('geo.cities');

        /* ---------- فاز ۸ — کیف پول و برداشت کافی‌نت ---------- */
        /* [Task 4] کیف پول → Livewire؛ data همان کنترلر */
        Route::get('{coffeenet}/wallet', \App\Livewire\Coffeenet\Wallet\Index::class)
            ->name('wallet.index');
        Route::get('{coffeenet}/wallet/data', [App\Http\Controllers\Back\Coffeenet\WalletController::class, 'data'])
            ->name('wallet.data');

        /* [Task 4] برداشت‌ها → Livewire؛ data/store همان کنترلر */
        Route::get('{coffeenet}/withdrawals', \App\Livewire\Coffeenet\Withdrawals\Index::class)
            ->name('withdrawals.index');
        Route::get('{coffeenet}/withdrawals/data', [App\Http\Controllers\Back\Coffeenet\WithdrawalsController::class, 'data'])
            ->name('withdrawals.data');
        Route::post('{coffeenet}/withdrawals', [App\Http\Controllers\Back\Coffeenet\WithdrawalsController::class, 'store'])
            ->name('withdrawals.store');

        /* ---------- فاز ۱۰ — تیکت‌های پشتیبانی کافی‌نت ---------- */
        /* [Task 4] فهرست و گفتگو → Livewire؛ data/reply/messages/status همان کنترلر */
        Route::get('{coffeenet}/tickets', \App\Livewire\Coffeenet\Tickets\Index::class)
            ->name('tickets.index');
        Route::get('{coffeenet}/tickets/data', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'data'])
            ->name('tickets.data');
        Route::get('{coffeenet}/tickets/{ticket}', \App\Livewire\Coffeenet\Tickets\Show::class)
            ->whereNumber('ticket')->name('tickets.show');
        Route::post('{coffeenet}/tickets/{ticket}/reply', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'reply'])
            ->whereNumber('ticket')->name('tickets.reply');
        Route::get('{coffeenet}/tickets/{ticket}/messages', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'messages'])
            ->whereNumber('ticket')->name('tickets.messages');
        Route::patch('{coffeenet}/tickets/{ticket}/status', [App\Http\Controllers\Back\Coffeenet\TicketsController::class, 'status'])
            ->whereNumber('ticket')->name('tickets.status');

        /* ---------- v39 — کارت‌های بانکی مدیر کافی‌نت ---------- */
        /* [Task 4] صفحه → Shell Livewire + UI مشترک سه پنل؛ CRUD همان کنترلر */
        Route::get('{coffeenet}/bank-cards', \App\Livewire\Coffeenet\BankCards\Index::class)
            ->name('bank-cards.index');
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
        Route::get('{coffeenet}/guide', [App\Http\Controllers\Back\Shared\GuideController::class, 'index'])
            ->whereNumber('coffeenet')->name('guide.index')->defaults('guide_role', 'coffeenet');
        Route::get('{coffeenet}/guide/{slug}', [App\Http\Controllers\Back\Shared\GuideController::class, 'show'])
            ->whereNumber('coffeenet')->name('guide.show')->defaults('guide_role', 'coffeenet');
    });
});
