<?php

namespace App\Livewire\Operator\Earnings;

use App\Enums\TransactionType;
use App\Livewire\Operator\Concerns\InteractsWithOperatorContext;
use App\Models\CommissionPayout;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «درآمد و کیف پول» پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
 *
 * منطق عیناً از App\Http\Controllers\Back\Operator\EarningsController
 * (index/data/transactions) منتقل شده است:
 *   • کیف پول سراسری کاربر (firstOrCreate) + خلاصهٔ درآمد (جمع کل / تعداد سفارش /
 *     ماه جاری / آخرین تسویه) — عیناً index()
 *   • سابقهٔ تسویهٔ سهم‌ها: CommissionPayout نقش operator + صفحه‌بندی ۲۰تایی
 *     با همان mapping (order/service/coffeenet از snapshot/amount/jdate)
 *   • لاگ تراکنش‌های کیف پول: فیلتر نوع credit/debit + صفحه‌بندی ۲۰تایی با
 *     همان برچسب‌های مرجع (reward/withdrawal/order/…)
 */
#[Layout('back.operator.layouts.panel')]
class Index extends Component
{
    use InteractsWithOperatorContext;
    use WithPagination;

    /** فیلتر نوع تراکنش کیف پول: '' | credit | debit */
    #[Url(as: 'type')]
    public string $type = '';

    public function updating(string $name): void
    {
        if (in_array($name, ['type'], true)) {
            $this->resetPage('txPage');
        }
    }

    /** فیلتر نوع تراکنش با دکمه‌ها (مثل data-type نسخهٔ JS) */
    public function filterByType(string $type): void
    {
        $this->type = in_array($type, ['credit', 'debit'], true) ? $type : '';
        $this->resetPage('txPage');
    }

    public function render(): \Illuminate\View\View
    {
        if ($this->operatorContextFailed) {
            return view('livewire.operator.partials.context-redirect')
                ->layoutData($this->operatorLayoutData('درآمد و کیف پول', 'پنل اپراتور ← درآمد و کیف پول', 'درآمد و کیف پول'));
        }

        $user = auth()->user();
        $wallet = $user->wallet()->firstOrCreate([], ['balance' => 0]);

        /* ---------- خلاصهٔ درآمد — عیناً index() کنترلر ---------- */
        $payouts = CommissionPayout::query()
            ->where('role', 'operator')
            ->where('holder_type', User::class)
            ->where('holder_id', $user->id);

        $startOfMonth = now()->startOfMonth();

        $summary = [
            'total' => (float) (clone $payouts)->sum('amount'),
            'orders' => (int) (clone $payouts)->distinct('order_id')->count('order_id'),
            'month' => (float) (clone $payouts)->where('created_at', '>=', $startOfMonth)->sum('amount'),
            'last_payout' => (clone $payouts)->latest('id')->first(),
        ];

        /* ---------- سابقهٔ تسویه‌ها — عیناً data() کنترلر ---------- */
        $payoutRows = CommissionPayout::query()
            ->where('role', 'operator')
            ->where('holder_type', User::class)
            ->where('holder_id', $user->id)
            ->with(['order:id,order_number,coffeenet_id,service_id', 'order.service:id,name'])
            ->orderByDesc('id')
            ->paginate(20);

        $payoutRows->through(fn (CommissionPayout $p) => [
            'id' => $p->id,
            'order_number' => $p->order?->order_number ?? '—',
            'service' => $p->order?->service?->name ?? '—',
            'coffeenet' => $p->snapshot['coffeenet']['name'] ?? '—',
            'amount' => (float) $p->amount,
            'date' => $p->created_at ? jdate($p->created_at)->format('Y/m/d — H:i') : '—',
        ]);

        /* ---------- لاگ تراکنش‌ها — عیناً transactions() کنترلر ---------- */
        $txQuery = Transaction::where('wallet_id', $wallet->id)->orderByDesc('id');

        if (in_array($this->type, ['credit', 'debit'], true)) {
            $txQuery->where('type', $this->type);
        }

        $txRows = $txQuery->paginate(20, ['*'], 'txPage');

        $refLabels = [
            'reward' => 'پاداش معرفی',
            'withdrawal' => 'برداشت',
            'order' => 'سهم سفارش',
            'payment' => 'پرداخت',
            'salary' => 'حقوق',
            'wallet_charge' => 'شارژ کیف پول',
        ];

        $txRows->through(fn (Transaction $t) => [
            'id' => $t->id,
            'type' => $t->type->value,
            'type_label' => $t->type === TransactionType::Credit ? 'واریز' : 'برداشت',
            'amount' => (float) $t->amount,
            'balance_after' => (float) $t->balance_after,
            'ref' => $t->ref_type ? ($refLabels[$t->ref_type] ?? $t->ref_type) : '—',
            'description' => $t->description ?? '—',
            'date' => $t->created_at ? jdate($t->created_at)->format('Y/m/d — H:i') : '—',
        ]);

        return view('livewire.operator.earnings.index', [
            'wallet' => $wallet,
            'summary' => $summary,
            'payoutRows' => $payoutRows,
            'txRows' => $txRows,
        ])->layoutData($this->operatorLayoutData('درآمد و کیف پول', 'پنل اپراتور ← درآمد و کیف پول', 'درآمد و کیف پول'));
    }
}
