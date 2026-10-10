<?php

namespace App\Livewire\App\Support;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «پشتیبانی» (تیکت‌های من) اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * پوستهٔ لیست تیکت‌ها با فیلتر چیپی و شیت ثبت؛ داده‌ها سمت کلاینت با support.js
 * (CN.api → API v1) دریافت می‌شود و دست‌نخورده است.
 */
#[Layout('app.layout')]
class Index extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.support.index')->layoutData([
            'htmlTitle' => 'پشتیبانی',
            'activeNav' => 'support',
        ]);
    }
}
