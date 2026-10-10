<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Webhooks\WebhookDispatcher;

/**
 * فاز ۵۱ — پخش رویدادهای سفارش به وب‌هوک‌ها.
 *
 * تنها نقطهٔ اتصال وب‌هوک به جریان سفارش — از Observer استفاده کردیم
 * تا هر مسیرِ تغییر وضعیت (اپراتور/مدیر کافی‌نت/مدیر کل/سرویس‌ها)
 * خودکار پوشش داده شود و لازم نباشد هر Service دستی هوک شود.
 *
 * رویدادها:
 *   order.created   — ساخت سفارش (هر وضعیت اولیه‌ای)
 *   order.delivered — گذار به delivered
 *   order.completed — گذار به completed
 */
class OrderObserver
{
    public function created(Order $order): void
    {
        WebhookDispatcher::dispatch('order.created', [
            'id'           => $order->id,
            'order_number' => $order->order_number,
            'status'       => $order->status?->value ?? (string) $order->status,
            'price'        => (float) $order->price,
            'expenses'     => (float) $order->expenses,
            'customer_id'  => $order->customer_id,
            'service_id'   => $order->service_id,
            'created_at'   => optional($order->created_at)->toIso8601String(),
        ]);
    }

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $status = $order->status?->value ?? (string) $order->status;

        if (in_array($status, ['delivered', 'completed'], true)) {
            WebhookDispatcher::dispatch('order.'.$status, [
                'id'            => $order->id,
                'order_number'  => $order->order_number,
                'status'        => $status,
                'price'         => (float) $order->price,
                'customer_id'   => $order->customer_id,
                'coffeenet_id'  => $order->coffeenet_id,
                'operator_id'   => $order->operator_id,
                'accepted_at'   => optional($order->accepted_at)->toIso8601String(),
                'delivered_at'  => optional($order->delivered_at)->toIso8601String(),
                'completed_at'  => optional($order->completed_at)->toIso8601String(),
            ]);
        }
    }
}
