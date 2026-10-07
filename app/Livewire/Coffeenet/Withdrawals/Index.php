<?php

namespace App\Livewire\Coffeenet\Withdrawals;

use App\Models\Coffeenet;
use App\Models\Withdrawal;
use App\Services\Finance\WalletService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «برداشت از کیف پول کافی‌نت» (فاز ۸) — نسخهٔ Livewire 4 (Task 4).
 *
 * منطق عیناً از App\Http\Controllers\Back\Coffeenet\WithdrawalsController (index/data)
 * منتقل شده است:
 *   • موجودی قابل برداشت + تعداد درخواست‌های در انتظار — عیناً index()
 *   • تاریخچه: فیلتر وضعیت + مرتب‌سازی نزولی id + صفحه‌بندی ۲۰تایی + سریال‌سازی data()
 *
 * ثبت درخواست برداشت (store) طبق قرارداد Task 4 همان endpoint کنترلر می‌ماند:
 * فرم با همان اعتبارسنجی سمت کلاینت (Alpine) از طریق App.ajax به
 * coffeenet.withdrawals.store می‌رود — بلوکهٔ فوری/اعلان/Audit عیناً همان کنترلر.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    public Coffeenet $coffeenet;

    /** فیلتر وضعیت: '' | pending | approved | rejected | paid */
    #[Url(as: 'status')]
    public string $status = '';

    public function mount(Coffeenet $coffeenet): void
    {
        $this->coffeenet = $coffeenet;
    }

    public function updating(string $name): void
    {
        if ($name === 'status') {
            $this->resetPage();
        }
    }

    public function render(): \Illuminate\View\View
    {
        $coffeenet = $this->coffeenet;

        $balance = app(WalletService::class)->balance($coffeenet);

        $pending = Withdrawal::query()
            ->whereHas('wallet', fn ($w) => $w->where('holder_type', Coffeenet::class)->where('holder_id', $coffeenet->id))
            ->where('status', 'pending')
            ->count();

        // تاریخچه — عیناً data() کنترلر
        $query = Withdrawal::query()
            ->whereHas('wallet', fn ($w) => $w->where('holder_type', Coffeenet::class)->where('holder_id', $coffeenet->id))
            ->with('reviewer:id,name,family');

        if ($status = (string) $this->status) {
            if (in_array($status, ['pending', 'approved', 'rejected', 'paid'], true)) {
                $query->where('status', $status);
            }
        }

        $labels = [
            'pending' => ['در انتظار بررسی', 'amber'],
            'approved' => ['تأییدشده', 'sky'],
            'paid' => ['پرداخت‌شده', 'emerald'],
            'rejected' => ['ردشده', 'rose'],
        ];

        $rows = $query->latest('id')->paginate(20)->through(fn (Withdrawal $w) => [
            'id' => $w->id,
            'amount' => (float) $w->amount,
            'status' => $w->status,
            'status_label' => $labels[$w->status][0] ?? $w->status,
            'status_color' => $labels[$w->status][1] ?? 'stone',
            'note' => $w->note,
            'reviewer' => $w->reviewer?->full_name,
            'requested_at' => $w->created_at ? jdate($w->created_at)->format('Y/m/d — H:i') : '—',
            'reviewed_at' => $w->reviewed_at ? jdate($w->reviewed_at)->format('Y/m/d — H:i') : null,
        ]);

        return view('livewire.coffeenet.withdrawals.index', [
            'coffeenet' => $coffeenet,
            'balance' => $balance,
            'pending' => $pending,
            'rows' => $rows,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'برداشت از کیف پول',
            'breadcrumb' => 'پنل کافی‌نت ← برداشت‌ها',
            'htmlTitle' => 'برداشت‌ها',
        ]);
    }
}
