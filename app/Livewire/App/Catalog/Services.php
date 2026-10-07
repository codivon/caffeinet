<?php

namespace App\Livewire\App\Catalog;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «خدمات» اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * پوستهٔ لیست گروهی/سکشنی خدمات؛ داده‌ها سمت کلاینت با CN.api (jQuery) از API v1
 * دریافت می‌شود (API دست‌نخورده). کامپوننت فقط تنظیمات لایه را پاس می‌دهد.
 */
#[Layout('app.layout')]
class Services extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.catalog.services')->layoutData([
            'htmlTitle' => 'خدمات',
            'activeNav' => 'services',
        ]);
    }
}
