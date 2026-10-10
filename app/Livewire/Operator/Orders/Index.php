<?php

namespace App\Livewire\Operator\Orders;

use App\Enums\OrderStatus;
use App\Livewire\Operator\Concerns\InteractsWithOperatorContext;
use App\Models\Order;
use App\Models\StaffAssignment;
use App\Services\Orders\OrderAssignmentService;
use App\Support\OperatorPermissions;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «سفارش‌ها» پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
 *
 * منطق عیناً از App\Http\Controllers\Back\Operator\OrdersController (index/data)
 * منتقل شده است:
 *   • محدودهٔ دید: orders.view → همهٔ سفارش‌های کافی‌نت، orders.view.own → فقط خودش
 *     (هیچ‌کدام → 403 — عیناً authorizeScope کنترلر)
 *   • انقضای تنزل مهلت پخش پیش از خواندن لیست (OrderAssignmentService::expireStale)
 *   • فیلتر وضعیت: active | done | mine_open | all | مقدار enum تکی
 *   • جستجوی شماره سفارش / نام و فامیل مشتری
 *   • مرتب‌سازی نزولی accepted_at,id و صفحه‌بندی ۱۵تایی (مثل data())
 *   • سریال‌سازی ردیف‌ها عیناً mapping کنترلر (service/customer/operator، مبلغ، وضعیت، تاریخ‌ها)
 *
 * تفاوت با نسخهٔ قبلی: به‌جای AJAX رندر سمت سرور با wire:model.live + #[Url]؛
 * دکمهٔ گفتگو (statusهای chattable) wire:navigate به operator.orders.chat است.
 *
 * endpointهای GET data و PATCH orders.status کنترلر دست‌نخورده ماندند
 * (PATCH وضعیت از صفحهٔ گفتگو با show.js فعلی کار می‌کند).
 *
 * انحراف کوچک: وضعیت enum نامعتبر در URL که کنترلر با 422 جواب می‌داد،
 * در نسخهٔ Livewire (مثل الگوی ادمین) به فیلتر پیش‌فرض «active» برمی‌گردد
 * تا URL خراب صفحه را نشکند.
 */
#[Layout('back.operator.layouts.panel')]
class Index extends Component
{
    use InteractsWithOperatorContext;
    use WithPagination;

    /** فیلتر وضعیت: active | done | mine_open | all | مقدار enum */
    #[Url(as: 'status')]
    public string $status = 'active';

    /** جستجوی شماره سفارش / نام مشتری */
    #[Url(as: 'q')]
    public string $q = '';

    public function updating(string $name): void
    {
        if (in_array($name, ['status', 'q'], true)) {
            $this->resetPage();
        }
    }

    public function render(): \Illuminate\View\View
    {
        if ($this->operatorContextFailed) {
            return view('livewire.operator.partials.context-redirect')
                ->layoutData($this->operatorLayoutData('سفارش‌ها', 'پنل اپراتور ← سفارش‌ها', 'سفارش‌ها'));
        }

        $assignment = $this->operatorAssignment;
        $coffeenet = $this->operatorCoffeenet;
        $user = auth()->user();

        $permissions = OperatorPermissions::filter($assignment->permissions ?? []);

        // عیناً authorizeScope کنترلر: بدون هیچ‌کدام از دو مجوز → 403
        abort_unless(
            in_array('orders.view', $permissions, true) || in_array('orders.view.own', $permissions, true),
            403,
            'دسترسی مشاهده سفارش برای شما فعال نیست.'
        );

        $canAll = in_array('orders.view', $permissions, true);

        // انقضای تنزل مهلت پخش (سفارش‌های در حال پخش تعیین‌تکلیف شوند) — عیناً data()
        app(OrderAssignmentService::class)->expireStale();

        $query = Order::query()
            ->where('coffeenet_id', $coffeenet->id)
            ->when(! $canAll, fn ($q) => $q->where('operator_id', $user->id))
            ->with([
                'service' => fn ($q) => $q->select(['id', 'name']),
                'customer' => fn ($q) => $q->select(['id', 'name', 'family']),
                'operator' => fn ($q) => $q->select(['id', 'name', 'family']),
            ]);

        $status = $this->status;

        if ($status === 'active') {
            // سفارش‌های باز: پذیرفته‌شده، پرداخت‌شده (منتظر شروع کار)، در حال انجام و نیازمند اطلاعات
            $query->whereIn('status', [
                OrderStatus::Accepted->value,
                OrderStatus::Paid->value,
                OrderStatus::InProgress->value,
                OrderStatus::NeedsInfo->value,
            ]);
        } elseif ($status === 'done') {
            $query->whereIn('status', [
                OrderStatus::Delivered->value,
                OrderStatus::Completed->value,
            ]);
        } elseif ($status === 'mine_open') {
            $query->whereIn('status', [
                OrderStatus::Accepted->value,
                OrderStatus::Paid->value,
                OrderStatus::InProgress->value,
                OrderStatus::NeedsInfo->value,
            ])->where('operator_id', $user->id);
        } elseif ($status !== 'all') {
            $enum = OrderStatus::tryFrom($status);

            // کنترلر برای وضعیت نامعتبر 422 می‌داد؛ در نسخهٔ Livewire به پیش‌فرض برمی‌گردیم
            if (! $enum) {
                $status = 'active';
                $query->whereIn('status', [
                    OrderStatus::Accepted->value,
                    OrderStatus::Paid->value,
                    OrderStatus::InProgress->value,
                    OrderStatus::NeedsInfo->value,
                ]);
            } else {
                $query->where('status', $enum->value);
            }
        }

        if ($q = trim($this->q)) {
            $query->where(function (Builder $w) use ($q) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")->orWhere('family', 'like', "%{$q}%"));
            });
        }

        $rows = $query->orderByDesc('accepted_at')->orderByDesc('id')->paginate(15);

        // عیناً mapping سرور در data() کنترلر
        $rows->through(fn (Order $order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'service_name' => $order->service?->name ?? '—',
            'customer_name' => trim(($order->customer?->name ?? '').' '.($order->customer?->family ?? '')) ?: '—',
            'operator_name' => $order->operator ? trim(($order->operator->name ?? '').' '.($order->operator->family ?? '')) : null,
            'total' => (float) $order->price + (float) $order->expenses,
            'is_paid' => (bool) $order->paid_at,
            'status' => [
                'value' => $order->status->value,
                'label' => $order->status->label(),
                'color' => $order->status->color(),
            ],
            'accepted_at_fa' => $order->accepted_at ? fa_date($order->accepted_at, 'Y/m/d H:i') : null,
            'delivered_at_fa' => $order->delivered_at ? fa_date($order->delivered_at, 'Y/m/d H:i') : null,
        ]);

        return view('livewire.operator.orders.index', [
            'rows' => $rows,
            'coffeenet' => $coffeenet,
            'canAll' => $canAll,
            'scopeLabel' => $canAll ? 'سفارش‌های کافی‌نت' : 'سفارش‌های من',
        ])->layoutData($this->operatorLayoutData(
            $canAll ? 'سفارش‌های کافی‌نت' : 'سفارش‌های من',
            'پنل اپراتور ← سفارش‌ها',
            'سفارش‌ها',
        ));
    }

    /* ================== منطق داخلی (عیناً از کنترلر/JS) ================== */

    /** نقشهٔ رنگ بج وضعیت — عیناً STATUS_COLORS در operator/orders/index.js */
    public static function statusColorClass(string $color): string
    {
        return match ($color) {
            'sky' => 'bg-sky-50 text-sky-700 border border-sky-200',
            'blue', 'teal' => 'bg-teal-50 text-teal-700 border border-teal-200',
            'orange', 'amber' => 'bg-amber-50 text-amber-700 border border-amber-200',
            'emerald' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'rose' => 'bg-rose-50 text-rose-600 border border-rose-200',
            default => 'bg-amber-50 text-amber-700 border border-amber-200',
        };
    }

    /** وضعیت‌هایی که گفتگو فعال/قابل‌مشاهده است — عیناً CHATABLE در JS */
    public static function isChattable(string $statusValue): bool
    {
        return in_array($statusValue, ['accepted', 'paid', 'in_progress', 'needs_info', 'delivered', 'completed'], true);
    }
}
