<?php

namespace App\Livewire\Admin\Settlements;

use App\Models\CommissionPayout;
use App\Models\Order;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «تسویه‌های کمیسیون» پنل مدیریت کل — نسخهٔ Livewire 4 (Task 3-e).
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\SettlementsController منتقل شده:
 *   • index(): جمع‌بندی نقش‌ها (کارت‌های آماری)
 *   • data(): فیلتر نقش/جستجوی شماره سفارش/بازهٔ تاریخ + صفحه‌بندی ۲۰تایی +
 *     سریال‌سازی ردیف (order/service/role_label/holder/amount/date) عیناً
 *   • order(): payload مودال جزئیات تسویهٔ سفارش عیناً (GET endpoint کنترلر هم
 *     سر جایش است؛ مودال در نسخهٔ Livewire همان payload را سمت سرور می‌سازد)
 *
 * retry (POST) endpoint کنترلر دست‌نخورده ماند (در UI ادمین استفاده نمی‌شد).
 */
#[Layout('back.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    /** فیلتر نقش: '' | platform | organization | coffeenet | operator */
    #[Url(as: 'role')]
    public string $role = '';

    /** جستجوی شماره سفارش */
    #[Url(as: 'q')]
    public string $q = '';

    /** بازهٔ تاریخ (Y-m-d میلادی — از دیت‌پیکر شمسی) */
    public string $from = '';
    public string $to = '';

    /** payload مودال جزئیات تسویهٔ سفارش */
    public ?array $orderModal = null;

    public function updating(string $name): void
    {
        if (in_array($name, ['role', 'q', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    /** بازکردن مودال جزئیات تسویهٔ سفارش — عیناً order() کنترلر */
    public function openOrder(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);

        $payouts = CommissionPayout::query()
            ->where('order_id', $order->id)
            ->orderByDesc('amount')
            ->get();

        if ($payouts->isEmpty()) {
            $this->orderModal = [
                'message' => 'این سفارش هنوز تسویه نشده است.',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status_label' => $order->status->label(),
                    'commissionable' => (float) $order->commissionable_amount,
                    'paid_at' => $order->paid_at?->format('Y-m-d H:i'),
                ],
                'payouts' => [],
                'settled' => false,
            ];

            return;
        }

        $snap = $payouts->first()->snapshot ?? [];

        $this->orderModal = [
            'message' => 'ok',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status_label' => $order->status->label(),
                'service' => $order->service?->name,
                'coffeenet' => $order->coffeenet?->name,
                'operator' => trim(($order->operator?->name ?? '').' '.($order->operator?->family ?? '')) ?: null,
                'commissionable' => (float) $order->commissionable_amount,
                'order_total' => (float) $order->price + (float) $order->expenses,
                'paid_at' => $order->paid_at?->format('Y-m-d H:i'),
            ],
            'settled' => true,
            'settled_at' => $snap['settled_at'] ?? null,
            'payouts' => $payouts->map(fn (CommissionPayout $p) => [
                'id' => $p->id,
                'role' => $p->role,
                'role_label' => $p->roleLabel(),
                'holder' => $this->holderName($p),
                'amount' => (float) $p->amount,
            ])->all(),
            'snapshot' => [
                'rule' => $this->ruleSnapshotText($snap['rule'] ?? []),
                'salary' => isset($snap['salary']) && $snap['salary']
                    ? ($snap['salary']['type_label'] ?? '—').' — '.fa_number((float) ($snap['salary']['rate'] ?? 0))
                    : null,
                'shares' => $snap['shares'] ?? [],
            ],
        ];
    }

    public function closeOrder(): void
    {
        $this->orderModal = null;
    }

    public function render(): \Illuminate\View\View
    {
        $roleSums = CommissionPayout::query()
            ->selectRaw('role, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('role')
            ->get()
            ->keyBy('role');

        $query = CommissionPayout::query()
            ->with(['order:id,order_number,coffeenet_id,operator_id,service_id', 'order.service:id,name', 'wallet:id,holder_type,holder_id']);

        if ($role = (string) $this->role) {
            if (in_array($role, ['platform', 'organization', 'coffeenet', 'operator'], true)) {
                $query->where('role', $role);
            }
        }

        if ($q = trim($this->q)) {
            $query->whereHas('order', fn (Builder $o) => $o->where('order_number', 'like', "%{$q}%"));
        }

        if ($from = (string) $this->from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = (string) $this->to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $payouts = $query->orderByDesc('id')->paginate(20);

        $payouts->through(fn (CommissionPayout $p) => [
            'id' => $p->id,
            'order_id' => $p->order_id,
            'order_number' => $p->order?->order_number ?? '—',
            'service' => $p->order?->service?->name ?? '—',
            'role' => $p->role,
            'role_label' => $p->roleLabel(),
            'holder' => $this->holderName($p),
            'amount' => (float) $p->amount,
            'date' => $p->created_at ? jdate($p->created_at)->format('Y/m/d — H:i') : '—',
        ]);

        return view('livewire.admin.settlements.index', [
            'rows' => $payouts,
            'summary' => [
                'total' => (float) CommissionPayout::sum('amount'),
                'count' => (int) CommissionPayout::count(),
                'orders' => (int) CommissionPayout::distinct('order_id')->count('order_id'),
                'platform' => (float) ($roleSums['platform']->total ?? 0),
                'organization' => (float) ($roleSums['organization']->total ?? 0),
                'coffeenet' => (float) ($roleSums['coffeenet']->total ?? 0),
                'operator' => (float) ($roleSums['operator']->total ?? 0),
            ],
            'roleBadges' => [
                'platform' => 'bg-amber-50 text-amber-700 border border-amber-200',
                'organization' => 'bg-teal-50 text-teal-700 border border-teal-200',
                'coffeenet' => 'bg-orange-50 text-orange-700 border border-orange-200',
                'operator' => 'bg-rose-50 text-rose-600 border border-rose-200',
            ],
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'تسویه‌های کمیسیون',
            'breadcrumb' => 'پنل مدیریت کل ← مالی و کمیسیون ← تسویه‌ها',
            'htmlTitle' => 'تسویه‌ها',
        ]);
    }

    /* ================== ابزارها (عیناً از کنترلر) ================== */

    protected function holderName(CommissionPayout $p): string
    {
        $holder = $p->holder;

        return match (true) {
            $holder instanceof \App\Models\Coffeenet => 'کافی‌نت «'.($holder->name ?? '—').'»',
            $holder instanceof \App\Models\Organization => 'سازمان «'.($holder->name ?? '—').'»',
            $holder instanceof \App\Models\User => ($p->role === 'platform' ? 'حساب پلتفرم' : 'اپراتور «'.trim(($holder->name ?? '').' '.($holder->family ?? '')).'»'),
            default => '—',
        };
    }

    protected function ruleSnapshotText(array $rule): ?string
    {
        if (! $rule) {
            return null;
        }

        $share = fn ($pair) => (empty($pair[0]) || ($pair[1] ?? null) === null) ? '—'
            : (($pair[0] === 'percent') ? fa_number((float) $pair[1]).'٪' : fa_money((float) $pair[1]));

        return 'قاعدهٔ '.($rule['scope'] === 'service' ? 'اختصاصی خدمت' : 'سراسری')
            .' — پلتفرم: '.$share($rule['platform'] ?? [])
            .' / سازمان: '.$share($rule['organization'] ?? [])
            .' / کافی‌نت: '.$share($rule['coffeenet'] ?? []);
    }
}
