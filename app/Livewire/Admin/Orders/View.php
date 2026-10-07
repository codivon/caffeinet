<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\ContactPreference;
use App\Enums\OrderStatus;
use App\Livewire\Admin\Orders\Concerns\InteractsWithOrderActions;
use App\Livewire\Admin\Support\Ui;
use App\Models\Order;
use App\Models\OrderFile;
use App\Services\Chat\ChatService;
use App\Services\Orders\OrderAssignmentService;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «صفحهٔ کامل جزئیات سفارش» پنل مدیریت کل — نسخهٔ Livewire 4 (Task 3-c).
 *
 * GET admin.orders.view → OrdersController@view قبلاً ویوی back/admin/orders/show.blade.php
 * را رندر می‌کرد که تمام بدنه و مودال‌هایش با show.js + endpointهای JSON
 * (admin.orders.show / orders.operators / ...) پر می‌شد.
 *
 * در این نسخه:
 *   • کل جزئیات (مشتری/مبالغ/فرم داینامیک/مدارک/گیرنده‌های پخش/پرداخت‌ها/
 *     تاریخچهٔ وضعیت/نظرسنجی) سمت سرور رندر می‌شود — عیناً همان renderDetail()
 *     نسخهٔ JS با همان کلاس‌ها.
 *   • اکشن‌ها (تغییر وضعیت/تخصیص/واگذاری به اپراتور/ری‌پخش/لغو) متد Livewire
 *     با همان منطق و پیام‌های کنترلر هستند (trait مشترک با Index).
 *   • endpointهای JSON کنترلر دست‌نخورده ماندند؛ لینک گفتگو wire:navigate است
 *     و موتور چت در صفحهٔ chat همان JS قبلی است.
 */
#[Layout('back.layouts.panel')]
class View extends Component
{
    use InteractsWithOrderActions;

    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    public function render(): \Illuminate\View\View
    {
        app(OrderAssignmentService::class)->expireStale();

        $order = $this->order->load([
            'service:id,name,description,estimated_time',
            'service.category:id,name,icon',
            'serviceVersion:id,service_id,version,snapshot',
            'customer:id,name,family,mobile',
            'customer.city:id,name',
            'coffeenet:id,name,phone',
            'operator:id,name,family',
            'files',
            'statusHistory',
            'payments',
            'rating',
            'broadcasts.coffeenet:id,name',
        ]);

        $total = (float) $order->price + (float) $order->expenses;

        $formDataDisplay = collect($order->form_data ?? [])->map(function ($value, $key) use ($order) {
            $label = $key;

            foreach ($order->serviceVersion?->snapshot['form_fields'] ?? [] as $field) {
                if (($field['name'] ?? null) === $key) {
                    $label = $field['label'] ?? $key;
                    break;
                }
            }

            return [
                'label' => $label,
                'value' => is_array($value) ? implode('، ', $value) : (string) $value,
            ];
        })->values()->all();

        $files = $order->files
            ->filter(fn (OrderFile $f) => $f->file_type === 'document')
            ->map(fn (OrderFile $f) => [
                'id' => $f->id,
                'original_name' => $f->original_name,
                'size_kb' => (int) round((int) $f->size / 1024),
                'url' => URL::temporarySignedRoute('files.order', now()->addHours(6), ['file' => $f->id]),
            ])->values()->all();

        $chat = [
            'exists' => $order->conversation()->exists(),
            'meta' => app(ChatService::class)->chatMeta($order, auth()->user()),
        ];

        $canAssign = in_array($order->status->value, ['queued', 'broadcasting', 'paid'], true);
        $canRebroadcast = $order->status->value === 'queued';
        $canCancel = in_array($order->status->value, ['paid', 'broadcasting', 'queued', 'accepted'], true);

        $operators = $order->coffeenet_id ? $this->operatorOptions((int) $order->coffeenet_id) : [];

        $assignCoffeenets = $this->assignOpen ? $this->coffeenetOptions() : [];
        $assignOperators = $this->assignOpen && $this->assignCoffeenetId ? $this->operatorOptions($this->assignCoffeenetId) : [];

        return view('livewire.admin.orders.view', [
            'statusBadge' => fn (OrderStatus $s) => Ui::statusBadge($s),
            'detail' => [
                'total' => $total,
                'seconds_left' => $order->broadcastSecondsLeft(),
                'attempts' => (int) $order->broadcast_attempts,
                'customer_name' => trim(($order->customer?->name ?? '').' '.($order->customer?->family ?? '')) ?: '—',
                'customer_city' => $order->customer?->city?->name,
                'operator_name' => $order->operator ? trim(($order->operator->name ?? '').' '.($order->operator->family ?? '')) : null,
                'contact_preference_label' => ContactPreference::describe($order->contact_preference),
                'estimated_time' => $order->service?->estimated_time,
                'service_description' => $order->service?->description,
                'form_data_display' => $formDataDisplay,
                'files' => $files,
                'broadcasts' => $order->broadcasts->map(fn ($b) => [
                    'coffeenet_name' => $b->coffeenet?->name ?? '—',
                    'sent_fa' => $b->sent_at ? fa_date($b->sent_at, 'H:i') : '—',
                    'seen_fa' => $b->seen_at ? fa_date($b->seen_at, 'H:i') : null,
                ])->all(),
                'status_history' => $order->statusHistory->map(fn ($h) => [
                    'to_status_label' => OrderStatus::tryFrom((string) $h->to_status)?->label(),
                    'note' => $h->note,
                    'created_at_fa' => $h->created_at ? fa_date($h->created_at, 'Y/m/d H:i') : null,
                ])->all(),
                'payments' => $order->payments->map(fn ($p) => [
                    'driver' => $p->driver,
                    'amount' => (float) $p->amount,
                    'status_label' => $p->status->label(),
                    'ref_id' => $p->ref_id,
                    'paid_at_fa' => $p->paid_at ? fa_date($p->paid_at, 'Y/m/d H:i') : null,
                ])->all(),
                'rating' => $order->rating ? [
                    'rating' => (int) $order->rating->rating,
                    'operator_rating' => $order->rating->operator_rating !== null ? (int) $order->rating->operator_rating : null,
                    'comment' => $order->rating->comment,
                    'options' => collect($order->rating->options ?? [])->map(fn ($o) => [
                        'title' => (string) ($o['title'] ?? ''),
                        'type' => (string) ($o['type'] ?? 'pos'),
                    ])->values()->all(),
                    'rated_at_fa' => $order->rating->rated_at ? fa_date($order->rating->rated_at, 'Y/m/d H:i') : null,
                ] : null,
            ],
            'chat' => $chat,
            'canAssign' => $canAssign,
            'canRebroadcast' => $canRebroadcast,
            'canCancel' => $canCancel,
            'statusActions' => $this->statusActions($order),
            'operators' => $operators,
            'assignCoffeenets' => $assignCoffeenets,
            'assignOperators' => $assignOperators,
            'trashedCount' => Order::onlyTrashed()->count(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'جزئیات سفارش: '.$order->order_number,
            'breadcrumb' => 'پنل مدیریت کل ← سفارش‌ها ← '.$order->order_number,
            'htmlTitle' => 'جزئیات سفارش',
        ]);
    }
}
