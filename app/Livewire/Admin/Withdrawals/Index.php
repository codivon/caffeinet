<?php

namespace App\Livewire\Admin\Withdrawals;

use App\Models\Organization;
use App\Models\Withdrawal;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «مدیریت برداشت‌ها» پنل مدیریت کل — نسخهٔ Livewire 4 (Task 3-e).
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\WithdrawalsController (index/data)
 * منتقل شده: فیلتر وضعیت + مرتب‌سازی نزولی + صفحه‌بندی ۲۵تایی + سریال‌سازی عیناً.
 *
 * تعیین‌تکلیف (review — PATCH) طبق قرارداد فاز ۳ همان endpoint کنترلر می‌ماند:
 * مودال Livewire باز می‌شود و ثبت نهایی با همان POST/PATCH قبلی (App.ajax)
 * انجام می‌شود؛ پس از موفقیت، رویداد withdrawal.reviewed لیست را تازه می‌کند.
 */
#[Layout('back.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    /** فیلتر وضعیت: '' | pending | approved | paid | rejected */
    #[Url(as: 'status')]
    public string $status = '';

    /** مودال تعیین‌تکلیف: [id, holder, amount] */
    public array $review = [];

    /** یادداشت مودال (مثلاً شماره پیگیری واریز) */
    public string $reviewNote = '';

    public function updating(string $name): void
    {
        if ($name === 'status') {
            $this->resetPage();
        }
    }

    /** بازکردن مودال تعیین‌تکلیف — همان اطلاعات مودال قبلی */
    public function openReview(int $id): void
    {
        $w = Withdrawal::query()
            ->with(['wallet.holder', 'requester:id,name,family,email'])
            ->findOrFail($id);

        $holder = $w->wallet?->holder;

        $this->review = [
            'id' => $w->id,
            'holder' => $holder?->name ?? '—',
            'amount' => (float) $w->amount,
        ];
        $this->reviewNote = '';
    }

    /** پس از ثبت موفق review در کنترلر — بستن مودال + رندر مجدد لیست */
    #[On('withdrawal.reviewed')]
    public function onReviewed(): void
    {
        $this->review = [];
        $this->reviewNote = '';
    }

    public function render(): \Illuminate\View\View
    {
        $query = Withdrawal::query()
            ->with(['wallet.holder', 'requester:id,name,family,email', 'reviewer:id,name,family']);

        if ($status = (string) $this->status) {
            if (in_array($status, ['pending', 'approved', 'rejected', 'paid'], true)) {
                $query->where('status', $status);
            }
        }

        $withdrawals = $query->latest('id')->paginate(25);

        $withdrawals->through(function (Withdrawal $w) {
            $holder = $w->wallet?->holder;

            return [
                'id' => $w->id,
                'holder' => $holder?->name ?? '—',
                'holder_type' => $holder instanceof Organization ? 'سازمان' : 'کافی‌نت',
                'requester' => $w->requester?->full_name ?? '—',
                'amount' => (float) $w->amount,
                'wallet_balance' => (float) $w->wallet?->balance ?? 0.0,
                'status' => $w->status,
                'note' => $w->note,
                'reviewer' => $w->reviewer?->full_name,
                'requested_at' => $w->created_at ? jdate($w->created_at)->format('Y/m/d H:i') : '—',
                'reviewed_at' => $w->reviewed_at ? jdate($w->reviewed_at)->format('Y/m/d H:i') : null,
            ];
        });

        return view('livewire.admin.withdrawals.index', [
            'rows' => $withdrawals,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'مدیریت برداشت‌ها',
            'breadcrumb' => 'پنل مدیریت کل ← برداشت‌ها',
            'htmlTitle' => 'برداشت‌ها',
        ]);
    }
}
