<?php

namespace App\Livewire\Admin\Core;

use App\Models\Announcement;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * اطلاعیه‌های سامانه (فاز ۱۵) — کامپوننت Livewire [Task 3-b]
 *
 * shell صفحه (هدر + فیلترها + لیست + مودال ایجاد/ویرایش) با Livewire
 * رندر می‌شود؛ لیست/آپلود رسانه (multipart تا ۱۵MB)/toggle/حذف با همان
 * JS قبلی به endpointهای AJAX کنترلر (announcements.data و CRUD) می‌رود
 * — آپلود multipart و مودال پیچیده امن‌ترین حالت را با JS قبلی دارند.
 *
 * GET admin/announcements (این کامپوننت) | endpointهای data/show/store/
 * update/toggle/destroy حفظ شده‌اند (روت‌ها دست‌نخورده).
 */
#[Layout('back.layouts.panel')]
class Announcements extends Component
{
    public function render()
    {
        return view('livewire.admin.core.announcements', [
            'audiences' => Announcement::AUDIENCES,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'اطلاعیه‌های سامانه',
            'breadcrumb' => 'پنل مدیریت کل ← اطلاعیه‌ها',
            'htmlTitle' => 'اطلاعیه‌های سامانه',
        ]);
    }
}
