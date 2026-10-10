<?php

namespace App\Livewire\Org\Withdrawals;

use App\Livewire\Org\Concerns\InteractsWithOrgContext;
use App\Models\Organization;
use App\Models\Withdrawal;
use App\Services\Finance\WalletService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «برداشت از کیف پول» سازمان — نسخهٔ Livewire 4 [Task 6]
 *
 * منطق عیناً از App\Http\Controllers\Back\Org\WithdrawalsController (index/data)
 * منتقل شده است:
 *   • کارت درخواست برداشت: موجودی قابل برداشت (WalletService::balance) + تعداد در انتظار
 *   • تاریخچه: فیلتر وضعیت + مرتب‌سازی نزولی id + صفحه‌بندی ۲۰تایی + سریال‌سازی عیناً data()
 *
 * ثبت درخواست (store — POST org.withdrawals.store) طبق قرارداد فاز ۳ همان
 * endpoint کنترلر می‌ماند: فرم با App.ajax همان مسیر قبلی را صدا می‌زند
 * (نگاشت خطای ۴۲۲ فیلد amount و رفرش لیست پس از موفقیت مثل نسخهٔ JS).
 */
#[Layout('back.layouts.org')]
class Index extends Component
{
    use InteractsWithOrgContext;
    use WithPagination;

    /** فیلتر وضعیت: '' | pending | paid | rejected (مثل سلکت قبلی) */
    #[Url(as: 'status')]
    public string $status = '';

    public function updating(string $name): void
    {
        if ($name === 'status') {
            $this->resetPage();
        }
    }

    /** برچسب/رنگ وضعیت — عیناً data() کنترلر */
    public static function statusMeta(string $status): array
    {
        $labels = [
            'pending' => ['در انتظار بررسی', 'amber'],
            'approved' => ['تأییدشده', 'sky'],
            'paid' => ['پرداخت‌شده', 'emerald'],
            'rejected' => ['ردشده', 'rose'],
        ];

        return $labels[$status] ?? [$status, 'stone'];
    }

    public function render(): \Illuminate\View\View
    {
        /** @var Organization|null $org */
        $org = $this->resolveOrgContext();

        // زمینهٔ سازمان نامعتبر است (ریدایرکت choose/login ثبت شده) — رندر خنثی بدون کوئری
        if (! $org) {
            return $this->neutralRender();
        }

        /* ---------- تاریخچه برداشت‌ها — عیناً data() کنترلر ---------- */
        $query = Withdrawal::query()
            ->whereHas('wallet', fn ($w) => $w->where('holder_type', Organization::class)->where('holder_id', $org->id))
            ->with('reviewer:id,name,family');

        if ($status = (string) $this->status) {
            if (in_array($status, ['pending', 'approved', 'rejected', 'paid'], true)) {
                $query->where('status', $status);
            }
        }

        $paginator = $query->latest('id')->paginate(20);

        $rows = $paginator->through(fn (Withdrawal $w) => [
            'id' => $w->id,
            'amount' => (float) $w->amount,
            'status' => $w->status,
            'status_label' => static::statusMeta($w->status)[0],
            'status_color' => static::statusMeta($w->status)[1],
            'note' => $w->note,
            'reviewer' => $w->reviewer?->full_name,
            'requested_at' => $w->created_at ? jdate($w->created_at)->format('Y/m/d — H:i') : '—',
            'reviewed_at' => $w->reviewed_at ? jdate($w->reviewed_at)->format('Y/m/d — H:i') : null,
        ]);

        return view('livewire.org.withdrawals.index', [
            'organization' => $org,
            'balance' => app(WalletService::class)->balance($org),
            'pending' => Withdrawal::query()
                ->whereHas('wallet', fn ($w) => $w->where('holder_type', Organization::class)->where('holder_id', $org->id))
                ->where('status', 'pending')
                ->count(),
            'rows' => $rows,
        ])->layoutData([
            'organization' => $org,
            'user' => auth()->user(),
            'pageTitle' => 'برداشت از کیف پول',
            'breadcrumb' => 'پنل سازمان ← برداشت‌ها',
            'htmlTitle' => 'برداشت‌ها',
        ]);
    }

    /** رندر خنثی — فقط وقتی زمینهٔ سازمان نامعتبر شد (بدون هیچ کوئری اضافه) */
    protected function neutralRender(): \Illuminate\View\View
    {
        $org = static::neutralOrganization();

        return view('livewire.org.withdrawals.index', [
            'organization' => $org,
            'balance' => 0.0,
            'pending' => 0,
            'rows' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
        ])->layoutData([
            'organization' => $org,
            'user' => auth()->user(),
            'pageTitle' => 'برداشت از کیف پول',
            'breadcrumb' => 'پنل سازمان ← برداشت‌ها',
            'htmlTitle' => 'برداشت‌ها',
        ]);
    }
}
