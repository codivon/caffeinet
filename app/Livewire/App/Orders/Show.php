<?php

namespace App\Livewire\App\Orders;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «گفتگوی سفارش» (جزئیات سفارش) اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * صفحهٔ گفتگو/وضعیت سفارش با order-chat.js + order-detail.js (CN.api + Pusher →
 * API v1) اجرا می‌شود و دست‌نخورده است — JS شناسهٔ سفارش را از مسیر URL می‌خواند.
 * بدون هدر/ناوبری (chrome=false) + shell چت — عین @section('no-chrome') قبلی.
 */
#[Layout('app.layout')]
class Show extends Component
{
    /** شناسهٔ سفارش (از پارامتر روت orders/{order}) */
    public int $orderId;

    public function mount(int $order): void
    {
        $this->orderId = $order;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.orders.show', [
            'orderId' => $this->orderId,
        ])->layoutData([
            'htmlTitle'  => 'گفتگوی سفارش',
            'chrome'     => false,
            'shellClass' => 'chat-shell',
        ]);
    }
}
