<?php

use App\Http\Controllers\Back\Admin\TrashController as TrashCtrl;

/*
|--------------------------------------------------------------------------
| پنل مدیریت کل — سفارش‌ها، گفتگوها و نظرسنجی‌ها
|--------------------------------------------------------------------------
| [فاز ۳ — مهاجرت Livewire] این فایل از admin.php جدا شد تا هر ایجنت مالکیت
| اختصاصی روی بخش خودش داشته باشد. نام‌ها و مسیرهای روت بدون تغییر مانده‌اند.
|
| ⚠ مالکیت فایل: فقط ایجنتِ همین بخش حق ویرایش دارد.
*/

Route::middleware(['admin.access'])->group(function () {
        /* ---------- مدیریت سفارش‌ها و موتور تخصیص (فاز ۶) ----------
         * [Task 3-c] GET صفحه‌سازها → کامپوننت‌های Livewire 4 (با همان نام روت)؛
         * endpointهای AJAX/POST/PATCH کنترلر حفظ شده‌اند تا JSهای فعلی نشکنند. */
        Route::get('orders', App\Livewire\Admin\Orders\Index::class)
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
        Route::get('orders/{order}/view', App\Livewire\Admin\Orders\View::class)
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

        /* ---------- گفتگوهای سفارش (مدیر کل — درخواست بازخوردی) ----------
         * [Task 3-c] فهرست گفتگوها Livewire کامل؛ صفحهٔ چت shell Livewire است
         * و موتور polling/push قبلی (operator/chat/show.js) با endpointهای
         * data/send/status حفظ شده است. */
        Route::get('chats', App\Livewire\Admin\Chats\Index::class)
            ->name('chats.index');
        Route::get('chats/data', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'adminData'])
            ->name('chats.data');
        Route::get('orders/{order}/chat', App\Livewire\Admin\Chats\Conversation::class)
            ->whereNumber('order')->name('orders.chat');
        Route::get('orders/{order}/chat/data', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'adminMessages'])
            ->whereNumber('order')->name('orders.chat.data');
        Route::post('orders/{order}/chat/send', [App\Http\Controllers\Back\Shared\OrderChatController::class, 'adminSend'])
            ->whereNumber('order')->name('orders.chat.send');

        /* ---------- نظرسنجی‌ها و امتیازها (v33 — مدیر کل) ----------
         * [Task 3-c] صفحهٔ نظرسنجی → Livewire کامل (آمار + جدول + مدیریت گزینه‌ها)؛
         * endpointهای data/stats/options کنترلر حفظ شده‌اند. */
        Route::get('ratings', App\Livewire\Admin\Ratings\Index::class)
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
    });
