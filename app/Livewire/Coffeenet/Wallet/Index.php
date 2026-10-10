<?php

namespace App\Livewire\Coffeenet\Wallet;

use App\Enums\TransactionType;
use App\Models\Coffeenet;
use App\Models\Transaction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «کیف پول کافی‌نت» (فاز ۸) — نسخهٔ Livewire 4 (Task 4).
 *
 * منطق عیناً از App\Http\Controllers\Back\Coffeenet\WalletController (index/data)
 * منتقل شده است:
 *   • موجودی + خلاصه (کل واریز/کل برداشت/کمیسیون سفارش‌ها) — عیناً index()
 *   • گردش حساب: فیلتر نوع (credit/debit) + مرتب‌سازی نزولی id + صفحه‌بندی ۲۰تایی
 *     + سریال‌سازی عیناً data() (برچسب مرجع/مانده پس از تراکنش/تاریخ شمسی)
 */
#[Layout('back.coffeenet.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    public Coffeenet $coffeenet;

    /** فیلتر نوع تراکنش: '' | credit | debit */
    #[Url(as: 'type')]
    public string $type = '';

    public function mount(Coffeenet $coffeenet): void
    {
        // فاز ۵۹ (F2 ممیزی) — گارد تنانت: کافی‌نتِ مسیر باید همان کافی‌نتِ جلسه باشد
        abort_unless(request()->attributes->get('current_coffeenet')?->id === $coffeenet->id, 403);

        $this->coffeenet = $coffeenet;
    }

    public function updating(string $name): void
    {
        if ($name === 'type') {
            $this->resetPage();
        }
    }

    public function render(): \Illuminate\View\View
    {
        $coffeenet = $this->coffeenet;

        // موجودی/خلاصه — عیناً index() کنترلر
        $wallet = $coffeenet->wallet()->firstOrCreate([], ['balance' => 0]);

        $summary = [
            'total_credit' => (float) Transaction::where('wallet_id', $wallet->id)
                ->where('type', TransactionType::Credit)->sum('amount'),
            'total_debit' => (float) Transaction::where('wallet_id', $wallet->id)
                ->where('type', TransactionType::Debit)->sum('amount'),
            'commission' => (float) Transaction::where('wallet_id', $wallet->id)
                ->where('type', TransactionType::Credit)
                ->where('ref_type', 'order')->sum('amount'),
        ];

        // گردش حساب — عیناً data() کنترلر
        $type = (string) $this->type;
        $query = Transaction::where('wallet_id', $wallet->id)->orderByDesc('id');

        if (in_array($type, ['credit', 'debit'], true)) {
            $query->where('type', $type);
        }

        // برچسب مرجع تراکنش — عیناً data() کنترلر
        $refLabels = [
            'reward' => 'پاداش معرفی',
            'withdrawal' => 'برداشت',
            'order' => 'کمیسیون سفارش',
            'payment' => 'پرداخت',
            'salary' => 'حقوق',
        ];

        $rows = $query->paginate(20)->through(fn (Transaction $t) => [
            'id' => $t->id,
            'type' => $t->type->value,
            'type_label' => $t->type === TransactionType::Credit ? 'واریز' : 'برداشت',
            'amount' => (float) $t->amount,
            'balance_after' => (float) $t->balance_after,
            'ref' => $t->ref_type ? ($refLabels[$t->ref_type] ?? $t->ref_type) : '—',
            'description' => $t->description ?? '—',
            'date' => $t->created_at ? jdate($t->created_at)->format('Y/m/d — H:i') : '—',
        ]);

        return view('livewire.coffeenet.wallet.index', [
            'coffeenet' => $coffeenet,
            'wallet' => $wallet,
            'summary' => $summary,
            'rows' => $rows,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'کیف پول کافی‌نت',
            'breadcrumb' => 'پنل کافی‌نت ← کیف پول',
            'htmlTitle' => 'کیف پول',
        ]);
    }
}
