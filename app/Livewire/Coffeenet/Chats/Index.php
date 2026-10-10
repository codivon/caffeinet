<?php

namespace App\Livewire\Coffeenet\Chats;

use App\Enums\OrderStatus;
use App\Models\Coffeenet;
use App\Models\Message;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «گفتگوها» پنل کافی‌نت — نسخهٔ Livewire 4 (Task 4).
 *
 * منطق عیناً از OrderChatController@dataResponse (مسیر coffeenetIndex/coffeenetData)
 * منتقل شده:
 *   • سفارش‌های دارای گفتگوِ همین کافی‌نت + فیلتر در جریان/پایان‌یافته/لغوشده (v33)/همه
 *   • جستجوی شماره سفارش / نام مشتری
 *   • آخرین پیام هر گفتگو در یک کوئری + پیش‌نمایش نوع‌محور (تصویر/صدا/ویدیو/فایل/سیستم)
 *   • شمارش پیام‌های خوانده‌نشدهٔ مشتری به تفکیک سفارش (unseenByOrder با محدودهٔ کافی‌نت)
 *   • مرتب‌سازی بر اساس آخرین پیام و صفحه‌بندی ۱۵تایی
 *
 * صفحهٔ خودِ چت (coffeenet.orders.chat) shell Livewire است و موتور polling/push
 * قبلی (operator/chat/show.js) دست‌نخورده باقی مانده — endpointها حفظ شده‌اند.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    public Coffeenet $coffeenet;

    /** فیلتر گفتگوها: active | done | cancelled | all */
    #[Url(as: 'filter')]
    public string $filter = 'active';

    #[Url(as: 'q')]
    public string $q = '';

    public function mount(Coffeenet $coffeenet): void
    {
        // فاز ۵۹ (F2 ممیزی) — گارد تنانت: کافی‌نتِ مسیر باید همان کافی‌نتِ جلسه باشد
        abort_unless(request()->attributes->get('current_coffeenet')?->id === $coffeenet->id, 403);

        $this->coffeenet = $coffeenet;
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['q', 'filter'], true)) {
            $this->resetFilterPage();
        }
    }

    public function filterBy(string $filter): void
    {
        $this->filter = $filter;
        $this->resetFilterPage();
    }

    protected function resetFilterPage(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $coffeenet = $this->coffeenet;

        $query = Order::query()
            ->whereHas('conversation')
            ->where('coffeenet_id', $coffeenet->id)
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
        } elseif ($filter === 'cancelled') {
            // v33 — سوابق گفتگوی سفارش‌های لغوشده
            $query->where('status', OrderStatus::Cancelled->value);
        } else {
            $filter = 'all';
        }

        if ($q = trim($this->q)) {
            $query->where(function ($w) use ($q) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")->orWhere('family', 'like', "%{$q}%"));
            });
        }

        $rows = $query->get();

        // آخرین پیامِ هر گفتگو در یک کوئری — عیناً مثل کنترلر
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

        $rows = $rows->sortByDesc(fn (Order $order) => (int) ($lastMessageMap[$order->conversation?->id] ?? 0))->values();

        $unseen = $this->unseenByOrder($coffeenet->id);

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
            ['pageName' => 'page']
        );

        return view('livewire.coffeenet.chats.index', [
            'rows' => $paginator,
            'unseenTotal' => array_sum($unseen),
            'statusBadge' => fn ($status) => \App\Livewire\Coffeenet\Support\Ui::statusBadge($status),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'گفتگوها',
            'breadcrumb' => 'پنل کافی‌نت ← گفتگوها',
            'htmlTitle' => 'گفتگوها',
        ]);
    }

    /** سطر هر گفتگو — عیناً rowPayload() کنترلر */
    protected function rowPayload(Order $order, array $lastMessageMap, array $unseen): array
    {
        $last = null;
        $lastMsgId = $order->conversation ? ($lastMessageMap[$order->conversation->id] ?? null) : null;

        if ($lastMsgId) {
            $last = Message::query()->find($lastMsgId);
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
            'operator_name' => $order->operator ? trim(($order->operator->name ?? '').' '.($order->operator->family ?? '')) : null,
            'status' => $order->status,
            'last_preview' => mb_substr($preview, 0, 90),
            'last_type' => $previewType,
            'last_time_fa' => $last?->created_at ? jdate($last->created_at)->format('H:i') : '—',
            'unseen' => (int) ($unseen[$order->id] ?? 0),
        ];
    }

    /** شمارش پیام‌های خوانده‌نشدهٔ مشتری — عیناً unseenByOrder() کنترلر (با محدودهٔ کافی‌نت) */
    protected function unseenByOrder(?int $coffeenetId): array
    {
        $query = Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->join('orders', 'orders.id', '=', 'conversations.order_id')
            ->whereNull('messages.seen_at')
            ->whereColumn('messages.sender_id', 'orders.customer_id')
            ->when($coffeenetId, fn ($q) => $q->where('orders.coffeenet_id', $coffeenetId));

        return $query->groupBy('orders.id')
            ->selectRaw('orders.id as oid, count(*) as c')
            ->pluck('c', 'oid')
            ->map(fn ($c) => (int) $c)
            ->all();
    }
}
