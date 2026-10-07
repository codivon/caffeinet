<?php

namespace App\Livewire\Admin\Chats;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Chat\ChatService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «گفتگوی سفارش» پنل مدیریت کل — Shell Livewire + موتور JS قبلی (Task 3-c).
 *
 * GET admin.orders.chat → OrderChatController@adminShow قبلاً ویوی مشترک
 * back.operator.chat.show را با این مقادیر رندر می‌کرد: chatMeta، chatUrls،
 * statusActions، canUpdateStatus و payload صفحه (page-data).
 *
 * طبق قرارداد Task 3-c منطق polling/push چت نشکسته است:
 *   • بدنهٔ گفتگو (ناحیهٔ پیام‌ها، آپلودر، نوار ارسال، مودال دلیل) عیناً همان
 *     markup ویوی قبلی است و موتور show.js (پولینگ after_id + Pusher + آپلود
 *     چندرسانه‌ای + اکشن‌های وضعیت) با همان endpointهای
 *     admin.orders.chat.data / admin.orders.chat.send / admin.orders.status
 *     کار می‌کند — کنترلر و routeهای JSON حفظ شده‌اند.
 *   • تنها تفاوت: shell صفحه (layoutData و هدر) کامپوننت Livewire است.
 */
#[Layout('back.layouts.panel')]
class Conversation extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        // v33 — مدیر کل گفتگوی سفارش لغوشده را هم می‌بیند (فقط-خواندن)
        abort_unless(app(ChatService::class)->canView($order, auth()->user()), 404, 'گفتگویی برای این سفارش وجود ندارد.');

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

        // عملیات‌های سریع وضعیت برای مدیر کل (عیناً staffStatusActions کنترلر)
        $statusActions = $this->staffStatusActions($order);

        return view('livewire.admin.chats.conversation', [
            'order' => $order,
            'customerName' => trim(($order->customer?->name ?? '').' '.($order->customer?->family ?? '')) ?: 'مشتری',
            'initial' => mb_substr($order->customer?->name ?: 'م', 0, 1),
            'chatMeta' => $chatMeta,
            'canUpdateStatus' => $canUpdateStatus,
            'statusActions' => $statusActions,
            'chatUrls' => [
                'data' => "/admin/orders/{$order->id}/chat/data",
                'send' => "/admin/orders/{$order->id}/chat/send",
                'status' => "/admin/orders/{$order->id}/status",
                'list' => '/admin/chats',
            ],
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'گفتگوی سفارش',
            'breadcrumb' => 'پنل مدیریت کل ← گفتگوها ← '.$order->order_number,
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
