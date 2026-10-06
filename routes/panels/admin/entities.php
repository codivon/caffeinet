<?php

use App\Http\Controllers\Back\Admin\TrashController as TrashCtrl;

/*
|--------------------------------------------------------------------------
| پنل مدیریت کل — موجودیت‌ها (مدیران/سازمان‌ها/کافی‌نت‌ها/اپراتورها/خدمات/مشتریان)
|--------------------------------------------------------------------------
| [فاز ۳ — مهاجرت Livewire] این فایل از admin.php جدا شد تا هر ایجنت مالکیت
| اختصاصی روی بخش خودش داشته باشد. نام‌ها و مسیرهای روت بدون تغییر مانده‌اند.
|
| ⚠ مالکیت فایل: فقط ایجنتِ همین بخش حق ویرایش دارد.
*/

Route::middleware(['admin.access'])->group(function () {
        /* مدیریت مدیران (AJAX) */
        Route::get('admins', [App\Http\Controllers\Back\Admin\AdminsController::class, 'index'])
            ->name('admins.index');
        Route::get('admins/data', [App\Http\Controllers\Back\Admin\AdminsController::class, 'data'])
            ->name('admins.data');

        /* حذف نرم/دائم مشترک (v28) — مسیرها پیش از روت‌های پارامتری بخش‌ها */
        Route::get('admins/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'admins')->name('admins.trashed');
        Route::get('admins/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'admins')->whereNumber('id')->name('admins.delete-info');
        Route::delete('admins/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'admins')->whereNumber('id')->name('admins.destroy');
        Route::post('admins/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'admins')->whereNumber('id')->name('admins.restore');
        Route::delete('admins/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'admins')->whereNumber('id')->name('admins.purge');

        Route::post('admins', [App\Http\Controllers\Back\Admin\AdminsController::class, 'store'])
            ->name('admins.store');
        Route::put('admins/{user}', [App\Http\Controllers\Back\Admin\AdminsController::class, 'update'])
            ->name('admins.update');
        Route::patch('admins/{user}/toggle', [App\Http\Controllers\Back\Admin\AdminsController::class, 'toggle'])
            ->name('admins.toggle');
        /* مدیریت سازمان‌ها (AJAX) */
        Route::get('organizations', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'index'])
            ->name('organizations.index');
        Route::get('organizations/data', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'data'])
            ->name('organizations.data');

        Route::get('organizations/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'organizations')->name('organizations.trashed');
        Route::get('organizations/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'organizations')->whereNumber('id')->name('organizations.delete-info');
        Route::delete('organizations/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'organizations')->whereNumber('id')->name('organizations.destroy');
        Route::post('organizations/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'organizations')->whereNumber('id')->name('organizations.restore');
        Route::delete('organizations/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'organizations')->whereNumber('id')->name('organizations.purge');

        Route::post('organizations', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'store'])
            ->name('organizations.store');
        Route::put('organizations/{organization}', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'update'])
            ->name('organizations.update');
        Route::patch('organizations/{organization}/status', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'status'])
            ->name('organizations.status');
        Route::get('organizations/{organization}', [App\Http\Controllers\Back\Admin\OrganizationsController::class, 'show'])
            ->name('organizations.show');

        /* مدیریت کافی‌نت‌ها (AJAX) */
        Route::get('coffeenets', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'index'])
            ->name('coffeenets.index');
        Route::get('coffeenets/data', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'data'])
            ->name('coffeenets.data');
        /* حذف نرم/دائم مشترک (v28) */
        Route::get('coffeenets/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'coffeenets')->name('coffeenets.trashed');
        Route::get('coffeenets/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'coffeenets')->whereNumber('id')->name('coffeenets.delete-info');
        Route::delete('coffeenets/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'coffeenets')->whereNumber('id')->name('coffeenets.destroy');
        Route::post('coffeenets/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'coffeenets')->whereNumber('id')->name('coffeenets.restore');
        Route::delete('coffeenets/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'coffeenets')->whereNumber('id')->name('coffeenets.purge');

        Route::post('coffeenets', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'store'])
            ->name('coffeenets.store');
        Route::put('coffeenets/{coffeenet}', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'update'])
            ->name('coffeenets.update');
        Route::patch('coffeenets/{coffeenet}/status', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'status'])
            ->name('coffeenets.status');
        Route::get('coffeenets/{coffeenet}', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'show'])
            ->name('coffeenets.show');

        /* روند کاری کافی‌نت (درخواست بازخوردی ۶-۲ — چارت بازه‌ای) */
        Route::get('coffeenets/{coffeenet}/trend', [App\Http\Controllers\Back\Admin\CoffeenetsController::class, 'trend'])
            ->name('coffeenets.trend');

        /* دید کلان مدیر به همه کارکنان/اپراتورهای شبکه (فاز ۱۰) */
        Route::get('operators', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'index'])
            ->name('operators.index');
        Route::get('operators/data', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'data'])
            ->name('operators.data');
        /* حذف نرم/دائم مشترک (v28) */
        Route::get('operators/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'operators')->name('operators.trashed');
        Route::get('operators/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'operators')->whereNumber('id')->name('operators.delete-info');
        Route::delete('operators/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'operators')->whereNumber('id')->name('operators.destroy');
        Route::post('operators/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'operators')->whereNumber('id')->name('operators.restore');
        Route::delete('operators/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'operators')->whereNumber('id')->name('operators.purge');

        Route::post('operators', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'store'])
            ->name('operators.store');

        /* پروفایل کامل اپراتور + روند کاری (درخواست بازخوردی ۶-۱) */
        Route::get('operators/{assignment}', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'show'])
            ->whereNumber('assignment')->name('operators.show');
        Route::get('operators/{assignment}/trend', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'trend'])
            ->whereNumber('assignment')->name('operators.trend');

        /* ویرایش کامل کارمند توسط مدیر کل (اطلاعات ورود + انتقال کافی‌نت) */
        Route::put('operators/{assignment}', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'update'])
            ->whereNumber('assignment')->name('operators.update');
        Route::get('operators/{assignment}/salary', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'salary'])
            ->whereNumber('assignment')->name('operators.salary');

        /* گردش‌کار تایید کارمندان (ساخته‌شده توسط مدیر کافی‌نت) */
        Route::patch('operators/{assignment}/approve', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'approve'])
            ->whereNumber('assignment')->name('operators.approve');
        Route::patch('operators/{assignment}/reject', [App\Http\Controllers\Back\Admin\OperatorsController::class, 'reject'])
            ->whereNumber('assignment')->name('operators.reject');
        /* دسته‌بندی‌ها (AJAX) */
        Route::get('service-categories', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'index'])
            ->name('service-categories.index');
        Route::get('service-categories/data', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'data'])
            ->name('service-categories.data');
        Route::post('service-categories', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'store'])
            ->name('service-categories.store');
        Route::put('service-categories/{category}', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'update'])
            ->name('service-categories.update');
        Route::patch('service-categories/{category}/toggle', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'toggle'])
            ->name('service-categories.toggle');
        Route::delete('service-categories/{category}', [App\Http\Controllers\Back\Admin\ServiceCategoriesController::class, 'destroy'])
            ->name('service-categories.destroy');

        /* خدمات (AJAX + فرم‌ساز) */
        Route::get('services', [App\Http\Controllers\Back\Admin\ServicesController::class, 'index'])
            ->name('services.index');
        Route::get('services/data', [App\Http\Controllers\Back\Admin\ServicesController::class, 'data'])
            ->name('services.data');
        Route::get('services/create', [App\Http\Controllers\Back\Admin\ServicesController::class, 'create'])
            ->name('services.create');
        Route::post('services', [App\Http\Controllers\Back\Admin\ServicesController::class, 'store'])
            ->name('services.store');
        Route::get('services/{service}/edit', [App\Http\Controllers\Back\Admin\ServicesController::class, 'edit'])
            ->name('services.edit');
        Route::put('services/{service}', [App\Http\Controllers\Back\Admin\ServicesController::class, 'update'])
            ->name('services.update');
        Route::patch('services/{service}/toggle', [App\Http\Controllers\Back\Admin\ServicesController::class, 'toggle'])
            ->name('services.toggle');
        Route::delete('services/{service}', [App\Http\Controllers\Back\Admin\ServicesController::class, 'destroy'])
            ->name('services.destroy');
        Route::get('services/{service}/versions', [App\Http\Controllers\Back\Admin\ServicesController::class, 'versions'])
            ->name('services.versions');
        /* ---------- مدیریت مشتریان (درخواست بازخوردی — لیست/پروفایل/ویرایش/بن) ---------- */
        Route::get('customers', [App\Http\Controllers\Back\Admin\CustomersController::class, 'index'])
            ->name('customers.index');
        Route::get('customers/data', [App\Http\Controllers\Back\Admin\CustomersController::class, 'data'])
            ->name('customers.data');
        /* حذف نرم/دائم مشترک (v28) */
        Route::get('customers/trashed', [TrashCtrl::class, 'trashed'])->defaults('section', 'customers')->name('customers.trashed');
        Route::get('customers/{id}/delete-info', [TrashCtrl::class, 'info'])->defaults('section', 'customers')->whereNumber('id')->name('customers.delete-info');
        Route::delete('customers/{id}', [TrashCtrl::class, 'destroy'])->defaults('section', 'customers')->whereNumber('id')->name('customers.destroy');
        Route::post('customers/{id}/restore', [TrashCtrl::class, 'restore'])->defaults('section', 'customers')->whereNumber('id')->name('customers.restore');
        Route::delete('customers/{id}/purge', [TrashCtrl::class, 'purge'])->defaults('section', 'customers')->whereNumber('id')->name('customers.purge');

        Route::get('customers/{customer}', [App\Http\Controllers\Back\Admin\CustomersController::class, 'show'])
            ->whereNumber('customer')->name('customers.show');
        Route::get('customers/{customer}/trend', [App\Http\Controllers\Back\Admin\CustomersController::class, 'trend'])
            ->whereNumber('customer')->name('customers.trend');
        Route::put('customers/{customer}', [App\Http\Controllers\Back\Admin\CustomersController::class, 'update'])
            ->whereNumber('customer')->name('customers.update');
        Route::patch('customers/{customer}/ban', [App\Http\Controllers\Back\Admin\CustomersController::class, 'ban'])
            ->whereNumber('customer')->name('customers.ban');
        Route::patch('customers/{customer}/unban', [App\Http\Controllers\Back\Admin\CustomersController::class, 'unban'])
            ->whereNumber('customer')->name('customers.unban');
        Route::post('customers/{customer}/wallet', [App\Http\Controllers\Back\Admin\CustomersController::class, 'wallet'])
            ->whereNumber('customer')->name('customers.wallet');
    });
