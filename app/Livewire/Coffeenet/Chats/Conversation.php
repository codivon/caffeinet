<?php

namespace App\Livewire\Coffeenet\Chats;

use App\Enums\OrderStatus;
use App\Models\Coffeenet;
use App\Models\Order;
use App\Services\Chat\ChatService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «گفتگوی سفارش» پنل کافی‌نت — Shell Livewire + موتور JS قبلی (Task 4).
 *
 * GET coffeenet.orders.chat → OrderChatController@coffeenetShow قبلاً ویوی مشترک
 * back.operator.chat.show را با این مقادیر رندر می‌کرد: chatMeta، chatUrls،
 * statusActions، canUpdateStatus و payload صفحه (page-data).
 *
 * طبق قرارداد Task 4 منطق polling/push چت نشکسته است:
 *   • بدنهٔ گفتگو (ناحیهٔ پیام‌ها، آپلودر، نوار ارسال، مودال دلیل) عیناً همان
 *     markup ویوی قبلی است و موتور show.js (پولینگ after_id + Pusher + آپلود
 *     چندرسانه‌ای + اکشن‌های وضعیت) با همان endpointهای
 *     coffeenet.orders.chat.data / orders.chat.send / orders.status کار می‌کند —
 *     کنترلر و routeهای JSON حفظ شده‌اند.
 *   • تنها تفاوت: shell صفحه (layoutData و هدر) کامپوننت Livewire است.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Conversation extends Component
{
    public Coffeenet $coffeenet;

    public Order $order;

    public function mount(Coffeenet $coffeenet, Order $order): void
    {
        // عیناً assertOrderOfCoffeenet کنترلر
        abort_unless((int) $order->coffeenet_id === (int) $coffeenet->id, 404, 'سفارش یافت نشد.');

        // v33 — مدیر کافی‌نت گفتگوی سفارش لغوشده را هم می‌بیند (فقط-خواندن)
        abort_unless(app(ChatService::class)->canView($order, auth()->user()), 404, 'گفتگویی برای این سفارش وجود ندارد.');

        $this->coffeenet = $coffeenet;
        $this->order = $order;
    }

    public function render(): \Illuminate\View\View
    {
        $order = $this->order->load([
            'service' => fn ($q) => $q->select(['id', 'name']),
            'customer' => fn ($q) => $q->select(['id', 'name', 'family']),
            'operator' => fn ($q) => $q->select(['id', 'name', 'family', 'last_seen_at']),
        ]);

        $chatMeta = app(ChatService::class)->chatMeta($order, auth()->user());

        $canUpdateStatus = $order->status !== OrderStatus::Cancelled; // لغوشده: فقط خواندن

        // عملیات‌های سریع وضعیت برای مدیر کافی‌نت (عیناً staffStatusActions کنترلر)
        $statusActions = $this->staffStatusActions($order);

        $canUpdateStatus = $order->status !== OrderStatus::Cancelled; // لغوشده: فقط خواندن

        $customerName = trim(($order->customer?->name ?? '').' '.($order->customer?->family ?? '')) ?: 'مشتری';

        $chatUrls = [
            'data' => "/coffeenet/{$this->coffeenet->id}/orders/{$order->id}/chat/data",
            'send' => "/coffeenet/{$this->coffeenet->id}/orders/{$order->id}/chat/send",
            'status' => "/coffeenet/{$this->coffeenet->id}/orders/{$order->id}/status",
            'list' => "/coffeenet/{$this->coffeenet->id}/chats",
        ];

        // payload صفحهٔ چت برای JS — ساخت در PHP (سازگار با کامپایلر Livewire — @json چندخطی ممنوع)
        $pagePayload = [
            'orderId' => $order->id,
            'orderNumber' => $order->order_number,
            'customerName' => $customerName,
            'status' => ['value' => $order->status->value, 'label' => $order->status->label()],
            'canSend' => $chatMeta['can_send'],
            'readonly' => $chatMeta['readonly'],
            'isCancelled' => (bool) ($chatMeta['cancelled'] ?? false),
            'cancelReason' => $order->cancel_reason,
            'canUpdateStatus' => $canUpdateStatus,
            'staffActions' => true,
            'isPaid' => (bool) $order->paid_at,
            'urls' => $chatUrls,
        ];

        return view('livewire.coffeenet.chats.conversation', [
            'coffeenet' => $this->coffeenet,
            'order' => $order,
            'customerName' => $customerName,
            'initial' => mb_substr($order->customer?->name ?: 'م', 0, 1),
            'chatMeta' => $chatMeta,
            'canUpdateStatus' => $canUpdateStatus,
            'statusActions' => $statusActions,
            'chatUrls' => $chatUrls,
            'pagePayload' => $pagePayload,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'گفتگوی سفارش',
            'breadcrumb' => 'پنل کافی‌نت ← گفتگوها ← '.$order->order_number,
            'htmlTitle' => 'گفتگوی سفارش',
        ]);
    }

    /**
     * نقشهٔ دکمه‌های تغییر وضعیت — عیناً staffStatusActions() کنترلر گفتگو.
     *
     * @return array<int, array{0:string,1:string,2:bool}> [status, label, نیاز به دلیل]
     */
    protected function staffStatusActions(Order $order): array
    {
        $actions = [];
        $isPaid = (bool) $order->paid_at;

        foreach ($order->status->allowedTransitions() as $to) {
            $label = match ($to) {
                OrderStatus::InProgress => $isPaid ? 'شروع/ادامه کار' : null,
                OrderStatus::NeedsInfo => 'نیازمند اطلاعات',
                OrderStatus::Delivered => $isPaid ? 'تحویل شد' : null,
                OrderStatus::Completed => 'تکمیل نهایی',
                OrderStatus::Cancelled => 'عدم امکان انجام',
                OrderStatus::Paid => null, // پرداخت فقط از سمت مشتری
                OrderStatus::Refunded => 'بازگشت وجه',
                default => null,
            };

            if ($label !== null) {
                $needsReason = $to === OrderStatus::Cancelled || $to === OrderStatus::Refunded;
                $actions[] = [$to->value, $label, $needsReason];
            }
        }

        return $actions;
    }
}
