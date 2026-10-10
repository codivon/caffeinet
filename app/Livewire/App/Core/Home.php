<?php

namespace App\Livewire\App\Core;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «داشبورد» اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * پوستهٔ کاتالوگ خدمات است؛ داده‌ها سمت کلاینت با CN.api (jQuery) از API v1
 * دریافت می‌شود (API دست‌نخورده). کامپوننت فقط تنظیمات لایه را پاس می‌دهد.
 */
#[Layout('app.layout')]
class Home extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.core.home')->layoutData([
            'htmlTitle' => 'داشبورد',
            'activeNav' => 'home',
        ]);
    }
}
