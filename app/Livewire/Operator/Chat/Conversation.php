<?php

namespace App\Livewire\Operator\Chat;

use App\Enums\OrderStatus;
use App\Livewire\Operator\Concerns\InteractsWithOperatorContext;
use App\Models\Order;
use App\Services\Chat\ChatService;
use App\Support\OperatorPermissions;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «گفتگوی سفارش» پنل اپراتور — Shell Livewire + موتور JS قبلی [Task 5]
 *
 * GET operator.orders.chat → ChatController@show قبلاً این ویو را با
 * order/chatMeta/canUpdateStatus رندر می‌کرد. منطق authorizeOrder عیناً
 * منتقل شده (در mount):
 *   • authorizeChat: مجوز orders.view / orders.view.own → 403
 *   • سفارشِ کافی‌نت جاری نیست → 404
 *   • canAll نیست و سفارش مال او نیست → 403
 *   • chat->canView (وضعیت‌های معنادار گفتگو) نیست → 404
 *
 * طبق قرارداد Task 5 موتور گفتگو نشکسته است:
 *   • بدنهٔ گفتگو (ناحیهٔ پیام‌ها، آپلودر، نوار ارسال، مودال دلیل) عیناً همان
 *     markup ویوی قبلی است و موتور operator/chat/show.js (پولینگ after_id +
 *     Pusher + آپلود چندرسانه‌ای + اکشن‌های وضعیت) با همان endpointهای
 *     operator.orders.chat.data / send / operator.orders.status کار می‌کند.
 *   • نقشهٔ دکمه‌های وضعیت اپراتور عیناً از @php ویوی قبلی (map بر اساس
 *     وضعیت + paid_at و مجوز orders.update_status) به کامپوننت منتقل شده.
 */
#[Layout('back.operator.layouts.panel')]
class Conversation extends Component
{
    use InteractsWithOperatorContext;

    public Order $order;

    public function mount(Order $order): void
    {
        $this->authorizeOrder($order);

        $this->order = $order;
    }

    public function render(): \Illuminate\View\View
    {
        if ($this->operatorContextFailed) {
            return view('livewire.operator.partials.context-redirect')
                ->layoutData($this->operatorLayoutData('گفتگوی سفارش', 'پنل اپراتور ← گفتگوها', 'گفتگوی سفارش'));
        }

        $order = $this->order->load([
            'service' => fn ($q) => $q->select(['id', 'name']),
            'customer' => fn ($q) => $q->select(['id', 'name', 'family']),
            'operator' => fn ($q) => $q->select(['id', 'name', 'family', 'last_seen_at']),
        ]);

        $chatMeta = app(ChatService::class)->chatMeta($order);

        $permissions = OperatorPermissions::filter($this->operatorAssignment->permissions ?? []);
        $canUpdateStatus = in_array('orders.update_status', $permissions, true);

        return view('livewire.operator.chat.conversation', [
            'order' => $order,
            'customerName' => trim(($order->customer?->name ?? '').' '.($order->customer?->family ?? '')) ?: 'مشتری',
            'initial' => mb_substr($order->customer?->name ?: 'م', 0, 1),
            'canUpdateStatus' => $canUpdateStatus,
            'chatMeta' => $chatMeta,
            'statusActions' => $this->operatorStatusActions($order, $canUpdateStatus),
            'chatUrls' => [
                'data' => "/operator/orders/{$order->id}/chat/data",
                'send' => "/operator/orders/{$order->id}/chat/send",
                'status' => "/operator/orders/{$order->id}/status",
                'list' => route('operator.chat.index'),
            ],
        ])->layoutData($this->operatorLayoutData(
            'گفتگوی سفارش',
            'پنل اپراتور ← گفتگوها ← '.$order->order_number,
            'گفتگوی سفارش',
        ));
    }

    /* ================== منطق داخلی (عیناً از کنترلر/ویوی قبلی) ================== */

    /** عیناً authorizeOrder کنترلر — در mount */
    protected function authorizeOrder(Order $order): void
    {
        $permissions = OperatorPermissions::filter($this->operatorAssignment->permissions ?? []);

        abort_unless(
            in_array('orders.view', $permissions, true) || in_array('orders.view.own', $permissions, true),
            403,
            'دسترسی گفتگو برای شما فعال نیست.'
        );

        $coffeenet = $this->operatorCoffeenet;

        abort_unless((int) $order->coffeenet_id === (int) $coffeenet->id, 404, 'سفارش یافت نشد.');

        if (! in_array('orders.view', $permissions, true)) {
            abort_unless((int) $order->operator_id === (int) auth()->id(), 403,
                'این سفارش به شما سپرده نشده است؛ گفتگوی آن در دسترس شما نیست.');
        }

        // گفتگو فقط در وضعیت‌های مرتبط معنا دارد
        if (! app(ChatService::class)->canView($order)) {
            abort(404, 'گفتگویی برای این سفارش وجود ندارد.');
        }
    }

    /**
     * نقشهٔ دکمه‌های تغییر وضعیت اپراتور — عیناً @php ویوی قبلی
     * (back.operator.chat.show): map بر اساس وضعیت فعلی + پرداخت‌شدن.
     * قالب آیتم: [status, label, نیاز-به-دلیل]
     *
     * @return array<int, array{0:string,1:string,2?:bool}>
     */
    protected function operatorStatusActions(Order $order, bool $canUpdateStatus): array
    {
        if (! $canUpdateStatus) {
            return [];
        }

        $map = [
            'accepted' => $order->paid_at
                ? [['in_progress', 'شروع کار'], ['needs_info', 'نیازمند اطلاعات'], ['cancelled', 'عدم امکان انجام', true]]
                : [['needs_info', 'نیازمند اطلاعات'], ['cancelled', 'عدم امکان انجام', true]],
            'paid' => [['in_progress', 'شروع کار'], ['cancelled', 'عدم امکان انجام', true]],
            'in_progress' => [['needs_info', 'نیازمند اطلاعات'], ['delivered', 'تحویل شد'], ['cancelled', 'عدم امکان انجام', true]],
            'needs_info' => [['in_progress', 'ادامه کار'], ['delivered', 'تحویل شد'], ['cancelled', 'عدم امکان انجام', true]],
        ];

        return $map[$order->status->value] ?? [];
    }
}
