<?php

namespace App\Livewire\Org\Wallet;

use App\Enums\TransactionType;
use App\Livewire\Org\Concerns\InteractsWithOrgContext;
use App\Models\Organization;
use App\Models\Transaction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «کیف پول سازمان» — نسخهٔ Livewire 4 [Task 6]
 *
 * منطق عیناً از App\Http\Controllers\Back\Org\WalletController (index/data)
 * منتقل شده است:
 *   • کارت موجودی + جمع واریز/برداشت (index) — کیف با firstOrCreate ساخته می‌شود
 *   • گردش حساب: فیلتر نوع (credit/debit) + مرتب‌سازی نزولی id + صفحه‌بندی ۲۰تایی
 *   • سریال‌سازی ردیف‌ها عیناً data() (نوع/مبلغ/موجودی بعد از تراکنش/بابت/تاریخ)
 *
 * endpoint GET org.wallet.data روی کنترلر دست‌نخورده ماند (سازگاری JSهای دیگر).
 */
#[Layout('back.layouts.org')]
class Index extends Component
{
    use InteractsWithOrgContext;
    use WithPagination;

    /** فیلتر نوع تراکنش: '' | credit | debit */
    #[Url(as: 'type')]
    public string $type = '';

    public function updating(string $name): void
    {
        if ($name === 'type') {
            $this->resetPage();
        }
    }

    public function render(): \Illuminate\View\View
    {
        /** @var Organization|null $org */
        $org = $this->resolveOrgContext();

        // زمینهٔ سازمان نامعتبر است (ریدایرکت choose/login ثبت شده) — رندر خنثی بدون کوئری
        if (! $org) {
            return $this->neutralRender();
        }

        $wallet = $org->wallet()->firstOrCreate([], ['balance' => 0]);

        $summary = [
            'total_credit' => (float) Transaction::where('wallet_id', $wallet->id)
                ->where('type', TransactionType::Credit)->sum('amount'),
            'total_debit' => (float) Transaction::where('wallet_id', $wallet->id)
                ->where('type', TransactionType::Debit)->sum('amount'),
            'rewards' => (float) Transaction::where('wallet_id', $wallet->id)
                ->where('ref_type', 'reward')->sum('amount'),
        ];

        /* ---------- گردش حساب — عیناً data() کنترلر ---------- */
        $type = (string) $this->type;
        $query = Transaction::where('wallet_id', $wallet->id)
            ->orderByDesc('id');

        if (in_array($type, ['credit', 'debit'], true)) {
            $query->where('type', $type);
        }

        $paginator = $query->paginate(20);

        $refLabels = [
            'reward' => 'پاداش معرفی',
            'withdrawal' => 'برداشت',
            'order' => 'کمیسیون سفارش',
            'payment' => 'پرداخت',
            'salary' => 'حقوق',
        ];

        $rows = $paginator->through(fn (Transaction $t) => [
            'id' => $t->id,
            'type' => $t->type->value,
            'type_label' => $t->type === TransactionType::Credit ? 'واریز' : 'برداشت',
            'amount' => (float) $t->amount,
            'balance_after' => (float) $t->balance_after,
            'ref' => $t->ref_type ? ($refLabels[$t->ref_type] ?? $t->ref_type) : '—',
            'description' => $t->description ?? '—',
            'date' => $t->created_at ? jdate($t->created_at)->format('Y/m/d — H:i') : '—',
        ]);

        return view('livewire.org.wallet.index', [
            'organization' => $org,
            'wallet' => $wallet,
            'summary' => $summary,
            'rows' => $rows,
        ])->layoutData([
            'organization' => $org,
            'user' => auth()->user(),
            'pageTitle' => 'کیف پول سازمان',
            'breadcrumb' => 'پنل سازمان ← کیف پول',
            'htmlTitle' => 'کیف پول',
        ]);
    }

    /** رندر خنثی — فقط وقتی زمینهٔ سازمان نامعتبر شد (بدون هیچ کوئری اضافه) */
    protected function neutralRender(): \Illuminate\View\View
    {
        $org = static::neutralOrganization();

        return view('livewire.org.wallet.index', [
            'organization' => $org,
            'wallet' => new \App\Models\Wallet(['balance' => 0]),
            'summary' => ['total_credit' => 0.0, 'total_debit' => 0.0, 'rewards' => 0.0],
            'rows' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
        ])->layoutData([
            'organization' => $org,
            'user' => auth()->user(),
            'pageTitle' => 'کیف پول سازمان',
            'breadcrumb' => 'پنل سازمان ← کیف پول',
            'htmlTitle' => 'کیف پول',
        ]);
    }
}
