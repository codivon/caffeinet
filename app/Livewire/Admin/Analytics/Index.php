<?php

namespace App\Livewire\Admin\Analytics;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * [استاب موقت — Task 3-e در حال تکمیل نسخه واقعی است]
 * برای جلوگیری از ۵۰۰ شدن کل پنل ادمین هنگام مهاجرت موازی روت‌ها ساخته شد.
 */
#[Layout('back.layouts.panel')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.analytics.index')->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'گزارش تحلیلی',
            'breadcrumb' => 'پنل مدیریت کل ← گزارش تحلیلی',
            'htmlTitle' => 'گزارش تحلیلی',
        ]);
    }
}
