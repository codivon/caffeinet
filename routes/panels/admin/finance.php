<?php

use App\Http\Controllers\Back\Admin\TrashController as TrashCtrl;

/*
|--------------------------------------------------------------------------
| پنل مدیریت کل — مالی (برداشت/کمیسیون/تسویه/گزارش/تیکت)
|--------------------------------------------------------------------------
| [فاز ۳ — مهاجرت Livewire] این فایل از admin.php جدا شد تا هر ایجنت مالکیت
| اختصاصی روی بخش خودش داشته باشد. نام‌ها و مسیرهای روت بدون تغییر مانده‌اند.
|
| ⚠ مالکیت فایل: فقط ایجنتِ همین بخش حق ویرایش دارد.
*/

Route::middleware(['admin.access'])->group(function () {
        /* مدیریت برداشت‌ها
           [Task 3-e] صفحهٔ لیست = کامپوننت Livewire (admin.withdrawals.index)؛
           data (AJAX قدیمی) و review (PATCH) همان کنترلر باقی می‌مانند */
        Route::get('withdrawals', App\Livewire\Admin\Withdrawals\Index::class)
            ->name('withdrawals.index');
        Route::get('withdrawals/data', [App\Http\Controllers\Back\Admin\WithdrawalsController::class, 'data'])
            ->name('withdrawals.data');
        Route::patch('withdrawals/{withdrawal}/review', [App\Http\Controllers\Back\Admin\WithdrawalsController::class, 'review'])
            ->name('withdrawals.review');
        /* ---------- فاز ۸ — مالی و کمیسیون ---------- */

        /* قواعد کمیسیون (سراسری + اختصاصی خدمت)
           [Task 3-e] صفحهٔ لیست = کامپوننت Livewire؛ CRUD همان کنترلر */
        Route::get('commissions', App\Livewire\Admin\Commissions\Index::class)
            ->name('commissions.index');
        Route::put('commissions/global', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'saveGlobal'])
            ->name('commissions.global');
        Route::get('commissions/data', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'data'])
            ->name('commissions.data');
        Route::post('commissions', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'store'])
            ->name('commissions.store');
        Route::put('commissions/{commission}', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'update'])
            ->whereNumber('commission')->name('commissions.update');
        Route::patch('commissions/{commission}/toggle', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'toggle'])
            ->whereNumber('commission')->name('commissions.toggle');
        Route::delete('commissions/{commission}', [App\Http\Controllers\Back\Admin\CommissionRulesController::class, 'destroy'])
            ->whereNumber('commission')->name('commissions.destroy');

        /* تسویه‌های کمیسیون
           [Task 3-e] صفحهٔ لیست = کامپوننت Livewire؛ order/retry همان کنترلر */
        Route::get('settlements', App\Livewire\Admin\Settlements\Index::class)
            ->name('settlements.index');
        Route::get('settlements/data', [App\Http\Controllers\Back\Admin\SettlementsController::class, 'data'])
            ->name('settlements.data');
        Route::get('settlements/order/{order}', [App\Http\Controllers\Back\Admin\SettlementsController::class, 'order'])
            ->whereNumber('order')->name('settlements.order');
        Route::post('settlements/order/{order}/retry', [App\Http\Controllers\Back\Admin\SettlementsController::class, 'retry'])
            ->whereNumber('order')->name('settlements.retry');

        /* گزارش مالی جامع
           [Task 3-e] صفحه = کامپوننت Livewire؛ data/export همان کنترلر */
        Route::get('finance', App\Livewire\Admin\Finance\Index::class)
            ->name('finance.index');
        Route::get('finance/data', [App\Http\Controllers\Back\Admin\FinanceController::class, 'data'])
            ->name('finance.data');
        Route::get('finance/export', [App\Http\Controllers\Back\Admin\FinanceController::class, 'export'])
            ->name('finance.export');
        /* ---------- فاز ۹ — گزارش تحلیلی و نمودارها ---------- */
        /* [Task 3-e] صفحه = کامپوننت Livewire؛ data/export همان کنترلر */
        Route::get('analytics', App\Livewire\Admin\Analytics\Index::class)
            ->name('analytics.index');
        Route::get('analytics/data', [App\Http\Controllers\Back\Admin\AnalyticsController::class, 'data'])
            ->name('analytics.data');
        Route::get('analytics/export', [App\Http\Controllers\Back\Admin\AnalyticsController::class, 'export'])
            ->name('analytics.export');

        /* ---------- فاز ۱۰ — تیکت‌های پشتیبانی ---------- */
        /* [Task 3-e] صفحهٔ لیست = کامپوننت Livewire؛ data و trash همان کنترلر/TrashController */
        Route::get('tickets', App\Livewire\Admin\Tickets\Index::class)
            ->name('tickets.index');
        Route::get('tickets/data', [App\Http\Controllers\Back\Admin\TicketsController::class, 'data'])
            ->name('tickets.data');
        /* حذف نرم/دائم مشترک (v28) */
        Route::get('tickets/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'tickets')->name('tickets.trashed');
        Route::get('tickets/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'tickets')->whereNumber('id')->name('tickets.delete-info');
        Route::delete('tickets/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'tickets')->whereNumber('id')->name('tickets.destroy');
        Route::post('tickets/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'tickets')->whereNumber('id')->name('tickets.restore');
        Route::delete('tickets/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'tickets')->whereNumber('id')->name('tickets.purge');

        /* [Task 3-e] صفحهٔ گفتگو = کامپوننت Livewire (shell) — reply/messages/status/
           priority/assign همان کنترلر؛ سه عمل سادهٔ وضعیت/اولویت/ارجاع با متد Livewire */
        Route::get('tickets/{ticket}', App\Livewire\Admin\Tickets\Show::class)
            ->whereNumber('ticket')->name('tickets.show');
        Route::post('tickets/{ticket}/reply', [App\Http\Controllers\Back\Admin\TicketsController::class, 'reply'])
            ->whereNumber('ticket')->name('tickets.reply');
        Route::get('tickets/{ticket}/messages', [App\Http\Controllers\Back\Admin\TicketsController::class, 'messages'])
            ->whereNumber('ticket')->name('tickets.messages');
        Route::patch('tickets/{ticket}/status', [App\Http\Controllers\Back\Admin\TicketsController::class, 'status'])
            ->whereNumber('ticket')->name('tickets.status');
        Route::patch('tickets/{ticket}/priority', [App\Http\Controllers\Back\Admin\TicketsController::class, 'priority'])
            ->whereNumber('ticket')->name('tickets.priority');
        Route::patch('tickets/{ticket}/assign', [App\Http\Controllers\Back\Admin\TicketsController::class, 'assign'])
            ->whereNumber('ticket')->name('tickets.assign');
    });
