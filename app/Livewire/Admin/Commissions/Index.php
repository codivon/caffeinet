<?php

namespace App\Livewire\Admin\Commissions;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
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
        return view('livewire.admin.commissions.index', [
            'globalRule' => null,
            'rules' => collect(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'قواعد کمیسیون',
            'breadcrumb' => 'پنل مدیریت کل ← قواعد کمیسیون',
            'htmlTitle' => 'قواعد کمیسیون',
        ]);
    }
}
