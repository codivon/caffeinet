<?php

namespace App\Livewire\Coffeenet\Orders;

use App\Models\Coffeenet;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «سفارش‌ها و پخش زنده» پنل کافی‌نت — Shell Livewire (Task 4).
 *
 * طبق قرارداد Task 4، موتور تعاملی این صفحه دست‌نخورده باقی مانده است:
 *   • صندوق پخش (polling ~۴s روی coffeenet.orders.broadcast.data + شمارش معکوس
 *     + انقضای تنبل سمت data endpoint) و فهرست «سفارش‌های من» (coffeenet.orders.data)
 *     با همان اسکریپت قبلی pages/coffeenet/orders/index.js کار می‌کنند —
 *     endpointهای data/broadcast/data/accept/operators/operator/status
 *     همگی روی کنترلر ماندند.
 *   • تنها تفاوت: shell صفحه (layoutData و هدر) کامپوننت Livewire است و
 *     broadcastTimeout عیناً مثل OrdersController@index محاسبه می‌شود.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Index extends Component
{
    public Coffeenet $coffeenet;

    /** مهلت پذیرش پخش (ثانیه) — عیناً OrdersController@index */
    public int $broadcastTimeout = 60;

    public function mount(Coffeenet $coffeenet): void
    {
        $this->coffeenet = $coffeenet;
    }

    public function render(): \Illuminate\View\View
    {
        $timeout = max(15, (int) app(\App\Services\Settings\SettingsService::class)->get('orders.broadcast_timeout', 60));

        $this->broadcastTimeout = $timeout;

        return view('livewire.coffeenet.orders.index', [
            'coffeenet' => $this->coffeenet,
            'broadcastTimeout' => $timeout,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'سفارش‌ها و پخش زنده',
            'breadcrumb' => 'پنل کافی‌نت ← سفارش‌ها',
            'htmlTitle' => 'سفارش‌ها',
        ]);
    }
}
