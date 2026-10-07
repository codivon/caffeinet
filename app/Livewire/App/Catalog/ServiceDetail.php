<?php

namespace App\Livewire\App\Catalog;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «ثبت سفارش» (جزئیات خدمت) اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * پوستهٔ فرم سفارش داینامیک؛ داده‌ها (خدمت/فرم/اعتبارسنجی/پرداخت) سمت کلاینت با
 * service.js (CN.api → API v1) اجرا می‌شود و دست‌نخورده است — شناسهٔ خدمت را
 * mount از پارامتر روت می‌گیرد و به ویو پاس می‌دهد (عین PagesController::service).
 */
#[Layout('app.layout')]
class ServiceDetail extends Component
{
    /** شناسهٔ خدمت (از پارامتر روت service/{service}) */
    public int $serviceId;

    public function mount(int $service): void
    {
        $this->serviceId = $service;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.catalog.service', [
            'serviceId' => $this->serviceId,
        ])->layoutData([
            'htmlTitle' => 'ثبت سفارش',
        ]);
    }
}
