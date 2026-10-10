<?php

namespace App\Livewire\App\Orders;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «سفارش‌های من» اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * پوستهٔ لیست سفارش‌ها با فیلتر چیپی؛ داده‌ها سمت کلاینت با orders.js
 * (CN.api → API v1) دریافت می‌شود و دست‌نخورده است.
 */
#[Layout('app.layout')]
class Index extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.orders.index')->layoutData([
            'htmlTitle' => 'سفارش‌های من',
            'activeNav' => 'orders',
        ]);
    }
}
