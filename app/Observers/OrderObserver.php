<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Realtime\PusherService;
use BackedEnum;

/**
 * ناظر مدل سفارش — Realtime پوشر
 *
 * با هر «سفارش جدید» یا «تغییر وضعیت/تخصیص»، رویداد پوشر ارسال می‌شود تا:
 *  • پنل‌های پشتی (ادمین/کافی‌نت/اپراتور) با کانال سراسری p.* همان لحظه
 *    لیست‌ها/صندوق پخش/بج درخواست‌ها را تازه کنند — بدون پولینگ.
 *  • مشتری صاحب سفارش با کانال شخصی u.* در صفحهٔ پیگیری سفارش، وضعیت جدید
 *    را لحظه‌ای ببیند — بدون پولینگ.
 *
 * معماری «بیدارباش»: رویداد فقط خبر است؛ دادهٔ واقعی از API خود سیستم
 * خوانده می‌شود (تک منبع حقیقت = دیتابیس).
 */
class OrderObserver
{
    /** فیلدهایی که تغییرشان یعنی «اتفاقی افتاده» و ارزش بیدارکردن دارد */
    private const WATCHED = ['status', 'coffeenet_id', 'operator_id', 'payment_status'];

    public function created(Order $order): void
    {
        $this->ping($order, 'created');
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged(self::WATCHED)) {
            $this->ping($order, 'updated');
        }
    }

    /** ارسال رویداد به پنل‌ها + مشتری صاحب سفارش (هرگز استثنا نمی‌دهد) */
    private function ping(Order $order, string $reason): void
    {
        try {
            $pusher = app(PusherService::class);

            if (! $pusher->enabled()) {
                return;
            }

            $payload = [
                'order' => (int) $order->id,
                'reason' => $reason,
                'status' => $order->status instanceof BackedEnum
                    ? $order->status->value
                    : (string) $order->status,
            ];

            // ۱) همهٔ پنل‌های پشتی — لیست سفارش‌ها/صندوق پخش/درخواست‌ها/بج‌ها
            $pusher->ordersChanged($payload);

            // ۲) مشتری صاحب سفارش — صفحهٔ پیگیری سفارش
            if (! empty($order->customer_id)) {
                $pusher->orderChangedForUser((int) $order->customer_id, $payload);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
