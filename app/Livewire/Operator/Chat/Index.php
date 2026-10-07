<?php

namespace App\Livewire\Operator\Chat;

use App\Enums\OrderStatus;
use App\Livewire\Operator\Concerns\InteractsWithOperatorContext;
use App\Models\Message;
use App\Models\Order;
use App\Support\OperatorPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «گفتگوها» پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
 *
 * منطق عیناً از App\Http\Controllers\Back\Operator\ChatController (index/data)
 * منتقل شده است:
 *   • authorizeChat: orders.view یا orders.view.own (هیچ‌کدام → 403)
 *   • محدودهٔ کافی‌نت جاری + سفارش‌های دارای گفتگو
 *   • فیلتر در جریان (chattable) / پایان‌یافته / همه — مثل data() کنترلر
 *   • جستجوی شماره سفارش / نام مشتری
 *   • آخرین پیام هر گفتگو در یک کوئری + پیش‌نمایش نوع‌محور (تصویر/صدا/ویدیو/فایل/سیستم)
 *   • شمارش پیام‌های خوانده‌نشدهٔ مشتری به تفکیک سفارش (unseenByOrder با محدودهٔ
 *     کافی‌نت و operator_id وقتی canAll نیست — عیناً کنترلر)
 *   • مرتب‌سازی بر اساس آخرین پیام و صفحه‌بندی ۱۵تایی (slice دستی مثل کنترلر)
 *
 * بج ناخواندهٔ سایدبار همان GET operator.chat.badge کنترلر است (دست‌نخورده).
 */
#[Layout('back.operator.layouts.panel')]
class Index extends Component
{
    use InteractsWithOperatorContext;
    use WithPagination;

    /** فیلتر گفتگوها: active | done | all */
    #[Url(as: 'filter')]
    public string $filter = 'active';

    #[Url(as: 'q')]
    public string $q = '';

    public function updating(string $name): void
    {
        if (in_array($name, ['filter', 'q'], true)) {
            $this->resetPage();
        }
    }

    public function filterBy(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        if ($this->operatorContextFailed) {
            return view('livewire.operator.partials.context-redirect')
                ->layoutData($this->operatorLayoutData('گفتگوها', 'پنل اپراتور ← گفتگوها', 'گفتگوها'));
        }

        $coffeenet = $this->operatorCoffeenet;
        $user = auth()->user();

        $permissions = OperatorPermissions::filter($this->operatorAssignment->permissions ?? []);

        // عیناً authorizeChat کنترلر
        abort_unless(
            in_array('orders.view', $permissions, true) || in_array('orders.view.own', $permissions, true),
            403,
            'دسترسی گفتگو برای شما فعال نیست.'
        );

        $canAll = in_array('orders.view', $permissions, true);

        $query = Order::query()
            ->where('coffeenet_id', $coffeenet->id)
            ->whereHas('conversation')
            ->when(! $canAll, fn ($q) => $q->where('operator_id', $user->id))
            ->with([
                'service' => fn ($q) => $q->select(['id', 'name']),
                'customer' => fn ($q) => $q->select(['id', 'name', 'family']),
                'operator' => fn ($q) => $q->select(['id', 'name', 'family', 'last_seen_at']),
                'conversation:id,order_id',
            ]);

        $filter = $this->filter;

        if ($filter === 'active') {
            $query->whereIn('status', OrderStatus::chattableValues());
        } elseif ($filter === 'done') {
            $query->whereIn('status', [OrderStatus::Delivered->value, OrderStatus::Completed->value]);
        } else {
            $filter = 'all';
        }

        if ($q = trim($this->q)) {
            $query->where(function ($w) use ($q) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")->orWhere('family', 'like', "%{$q}%"));
            });
        }

        // آخرین پیامِ هر گفتگو در یک کوئری — عیناً data() کنترلر
        $rows = $query->get();

        $lastMessageMap = [];
        $convIds = $rows->pluck('conversation.id')->filter()->values()->all();

        if ($convIds) {
            $lastMessageMap = Message::query()
                ->selectRaw('conversation_id, MAX(id) as mid')
                ->whereIn('conversation_id', $convIds)
                ->groupBy('conversation_id')
                ->pluck('mid', 'conversation_id')
                ->all();
        }

        // مرتب‌سازی: آخرین پیام (سفارش بدون پیام آخر) — عیناً کنترلر
        $rows = $rows
            ->sortByDesc(fn (Order $order) => (int) ($lastMessageMap[$order->conversation?->id] ?? 0))
            ->values();

        // شمارش ناخوانده برای هر گفتگو (پیام مشتری دیده‌نشده) — عیناً کنترلر
        $unseen = $this->unseenByOrder($coffeenet->id, $canAll ? null : $user->id);

        $perPage = 15;
        $page = max(1, (int) $this->getPage('page'));
        $total = $rows->count();

        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values()
            ->map(fn (Order $order) => $this->rowPayload($order, $lastMessageMap, $unseen))->all();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['pageName' => 'page'],
        );

        return view('livewire.operator.chat.index', [
            'rows' => $paginator,
            'coffeenet' => $coffeenet,
            'unseenTotal' => array_sum($unseen),
        ])->layoutData($this->operatorLayoutData('گفتگوها', 'پنل اپراتور ← گفتگوها', 'گفتگوها'));
    }

    /* ================== منطق داخلی (عیناً از کنترلر) ================== */

    /** سطر هر گفتگو — عیناً rowPayload() کنترلر */
    protected function rowPayload(Order $order, array $lastMessageMap, array $unseen): array
    {
        $last = null;
        $lastMsgId = $order->conversation ? ($lastMessageMap[$order->conversation->id] ?? null) : null;

        if ($lastMsgId) {
            $last = Message::with('sender:id,name,family')->find($lastMsgId);
        }

        $preview = 'گفتگو آغاز شد';
        $previewType = 'none';

        if ($last) {
            $previewType = $last->message_type;
            $preview = match ($last->message_type) {
                'image' => '📷 تصویر',
                'audio' => '🎤 پیام صوتی',
                'video' => '🎬 ویدیو',
                'file' => '📎 '.($last->file_meta['name'] ?? 'فایل'),
                'system' => (string) $last->content,
                default => (string) $last->content,
            };
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'service_name' => $order->service?->name ?? '—',
            'customer_name' => trim(($order->customer?->name ?? '').' '.($order->customer?->family ?? '')) ?: '—',
            'operator_name' => $order->operator ? trim(($order->operator->name ?? '').' '.($order->operator?->family ?? '')) : null,
            'status' => [
                'value' => $order->status->value,
                'label' => $order->status->label(),
                'color' => $order->status->color(),
            ],
            'last_preview' => mb_substr($preview, 0, 90),
            'last_type' => $previewType,
            'last_time_fa' => $last?->created_at ? fa_date($last->created_at, 'm/d H:i') : null,
            'unseen' => (int) ($unseen[$order->id] ?? 0),
        ];
    }

    /** نگاشت order_id → تعداد پیام دیده‌نشدهٔ مشتری — عیناً unseenByOrder() کنترلر */
    protected function unseenByOrder(int $coffeenetId, ?int $operatorId = null): array
    {
        $query = Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->join('orders', 'orders.id', '=', 'conversations.order_id')
            ->where('orders.coffeenet_id', $coffeenetId)
            ->whereNull('messages.seen_at')
            ->whereColumn('messages.sender_id', 'orders.customer_id')
            ->when($operatorId, fn ($q) => $q->where('orders.operator_id', $operatorId));

        return $query->groupBy('orders.id')
            ->selectRaw('orders.id as oid, count(*) as c')
            ->pluck('c', 'oid')
            ->map(fn ($c) => (int) $c)
            ->all();
    }
}
