<?php

namespace App\Livewire\Admin\Finance;

use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Models\Coffeenet;
use App\Models\CommissionPayout;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\Finance\SettlementService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «گزارش مالی جامع» پنل مدیریت کل — نسخهٔ Livewire 4 (Task 3-e).
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\FinanceController منتقل شده:
 *   • index(): حجم پرداخت‌شده/مشمول کمیسیون/سهم‌های نقش/کیف‌های شبکه/برداشت‌ها
 *     + روند ۶ ماه اخیر (عیناً — فقط برچسب ماه با Jalalian می‌سازد؛ %B روی
 *     Carbon 3 خروجی خراب «%041 %2026» می‌داد و قصدهای نویسنده برچسب شمسی بود)
 *   • data(): فیلتر دارنده/نوع/جستجو/بازهٔ تاریخ + صفحه‌بندی ۲۰تایی + سریال‌سازی عیناً
 *
 * export (خروجی CSV) همان endpoint کنترلر است — لینک آن با فیلترهای فعلی
 * سمت سرور ساخته می‌شود؛ نمودارهای Chart.js با همان charts.js فعلی و
 * دادهٔ page-data (months/shares) رندر می‌شوند.
 */
#[Layout('back.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    /** فیلتر دارندهٔ کیف پول: '' | FQN سازمان/کافی‌نت/کاربر */
    #[Url(as: 'holder')]
    public string $holder = '';

    /** فیلتر نوع تراکنش: '' | credit | debit */
    #[Url(as: 'type')]
    public string $type = '';

    /** جستجو در توضیح تراکنش */
    #[Url(as: 'q')]
    public string $q = '';

    /** بازهٔ تاریخ (Y-m-d میلادی — از دیت‌پیکر شمسی) */
    public string $from = '';
    public string $to = '';

    public function updating(string $name): void
    {
        if (in_array($name, ['holder', 'type', 'q', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render(): \Illuminate\View\View
    {
        [$summary, $wallets, $withdrawals, $months] = $this->summary();

        $rows = $this->filteredTransactions()
            ->orderByDesc('id')
            ->paginate(20);

        $rows->through(fn (Transaction $t) => $this->serializeTransaction($t));

        return view('livewire.admin.finance.index', [
            'summary' => $summary,
            'wallets' => $wallets,
            'withdrawals' => $withdrawals,
            'months' => $months,
            'rows' => $rows,
            'exportUrl' => $this->exportUrl(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'گزارش مالی',
            'breadcrumb' => 'پنل مدیریت کل ← مالی و کمیسیون ← گزارش مالی',
            'htmlTitle' => 'گزارش مالی',
        ]);
    }

    /* ================== منطق داخلی (عیناً از کنترلر) ================== */

    /** جمع‌بندی‌های بالای صفحه + روند ۶ ماه — عیناً index() کنترلر */
    protected function summary(): array
    {
        // حجم موفق: مجموع مبلغ سفارش‌های پرداخت‌شدهٔ حذف‌نشده
        $paidBase = \App\Models\Order::query()
            ->whereNotNull('paid_at')
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value]);

        $commissionable = (clone $paidBase)->sum('commissionable_amount');
        $grossVolume = (clone $paidBase)->selectRaw('SUM(price + expenses)')->value('SUM(price + expenses)');

        $roleSums = CommissionPayout::query()
            ->selectRaw('role, SUM(amount) as total')
            ->groupBy('role')
            ->get()
            ->keyBy('role');

        $walletSums = Wallet::query()
            ->selectRaw('holder_type, COUNT(*) as cnt, SUM(balance) as total')
            ->groupBy('holder_type')
            ->get()
            ->keyBy('holder_type');

        $pendingWithdrawals = Withdrawal::query()->where('status', 'pending');

        // روند ۶ ماه اخیر (شمسی)
        $months = [];
        $now = now();
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->startOfMonth()->subMonths($i);
            $from = $m->toDateTimeString();
            $to = $m->copy()->endOfMonth()->toDateTimeString();

            $paidCount = \App\Models\Order::query()
                ->whereBetween('paid_at', [$from, $to])
                ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value])
                ->count();

            $settled = CommissionPayout::query()
                ->whereBetween('created_at', [$from, $to]);

            $months[] = [
                'label' => jdate($m)->format('%B %Y'),
                'label_short' => jdate($m)->format('%B'),
                'paid_orders' => $paidCount,
                'commissionable' => (float) \App\Models\Order::query()
                    ->whereBetween('paid_at', [$from, $to])
                    ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value])
                    ->sum('commissionable_amount'),
                'platform' => (float) (clone $settled)->where('role', 'platform')->sum('amount'),
                'organization' => (float) (clone $settled)->where('role', 'organization')->sum('amount'),
                'coffeenet' => (float) (clone $settled)->where('role', 'coffeenet')->sum('amount'),
                'operator' => (float) (clone $settled)->where('role', 'operator')->sum('amount'),
            ];
        }

        $summary = [
            'gross_volume' => (float) ($grossVolume ?? 0),
            'commissionable' => (float) $commissionable,
            'settled_total' => (float) CommissionPayout::sum('amount'),
            'platform' => (float) ($roleSums['platform']->total ?? 0),
            'organization' => (float) ($roleSums['organization']->total ?? 0),
            'coffeenet' => (float) ($roleSums['coffeenet']->total ?? 0),
            'operator' => (float) ($roleSums['operator']->total ?? 0),
            'platform_wallet_balance' => app(SettlementService::class)->platformUser()->wallet()->firstOrCreate([], ['balance' => 0])->balance,
        ];

        $wallets = [
            'organizations' => ['count' => (int) ($walletSums[Organization::class]->cnt ?? 0), 'balance' => (float) ($walletSums[Organization::class]->total ?? 0)],
            'coffeenets' => ['count' => (int) ($walletSums[Coffeenet::class]->cnt ?? 0), 'balance' => (float) ($walletSums[Coffeenet::class]->total ?? 0)],
        ];

        $withdrawals = [
            'pending_count' => (int) (clone $pendingWithdrawals)->count(),
            'pending_amount' => (float) (clone $pendingWithdrawals)->sum('amount'),
            'paid_amount' => (float) Withdrawal::where('status', 'paid')->sum('amount'),
        ];

        return [$summary, $wallets, $withdrawals, $months];
    }

    /** کوئری مشترک فیلترهای تراکنش — عیناً filteredTransactions() کنترلر */
    protected function filteredTransactions(): Builder
    {
        $query = Transaction::query()
            ->with('wallet:id,holder_type,holder_id');

        if ($holderType = (string) $this->holder) {
            if (in_array($holderType, [Organization::class, Coffeenet::class, \App\Models\User::class], true)) {
                $query->whereHas('wallet', fn ($w) => $w->where('holder_type', $holderType));
            }
        }

        $type = (string) $this->type;
        if (in_array($type, ['credit', 'debit'], true)) {
            $query->where('type', $type);
        }

        if ($q = trim($this->q)) {
            $query->where('description', 'like', "%{$q}%");
        }

        if ($from = (string) $this->from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = (string) $this->to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    /** سریال‌سازی ردیف جدول — عیناً data() کنترلر */
    protected function serializeTransaction(Transaction $t): array
    {
        $refLabels = [
            'reward' => 'پاداش معرفی',
            'withdrawal' => 'برداشت',
            'order' => 'کمیسیون سفارش',
            'payment' => 'پرداخت',
            'salary' => 'حقوق',
        ];

        $holderType = $t->wallet?->holder_type;
        $holderLabel = match ($holderType) {
            Organization::class => 'سازمان',
            Coffeenet::class => 'کافی‌نت',
            \App\Models\User::class => 'کاربر',
            default => '—',
        };

        return [
            'id' => $t->id,
            'type' => $t->type->value,
            'type_label' => $t->type === TransactionType::Credit ? 'واریز' : 'برداشت',
            'amount' => (float) $t->amount,
            'balance_after' => (float) $t->balance_after,
            'holder_label' => $holderLabel,
            'ref' => $t->ref_type ? ($refLabels[$t->ref_type] ?? $t->ref_type) : '—',
            'description' => $t->description ?? '—',
            'date' => $t->created_at ? jdate($t->created_at)->format('Y/m/d — H:i') : '—',
        ];
    }

    /** لینک خروجی CSV با فیلترهای فعلی — عیناً syncExportLink نسخهٔ JS */
    protected function exportUrl(): string
    {
        $params = array_filter([
            'holder' => $this->holder,
            'type' => $this->type,
            'q' => trim($this->q),
            'from' => $this->from,
            'to' => $this->to,
        ], fn ($v) => $v !== null && $v !== '');

        return route('admin.finance.export', $params);
    }
}
