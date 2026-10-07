<?php

namespace App\Livewire\Coffeenet\Core;

use App\Models\Coffeenet;
use App\Services\Analytics\AnalyticsService;
use App\Services\Finance\WalletService;
use App\Services\Orders\OrderAssignmentService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * داشبورد مدیر کافی‌نت — کامپوننت Livewire (Task 4).
 *
 * منطق عیناً از App\Http\Controllers\Back\Coffeenet\DashboardController@index
 * منتقل شده است (کنترلر حذف نمی‌شود؛ فقط رندر صفحه به Livewire منتقل شد):
 *   • گارد ۴۰۳: کافی‌نتِ مسیر باید همان کافی‌نتِ session باشد (فقط در mount/بار
 *     اولیه — requestهای update لایووایر از مسیر میدل‌ور عبور نمی‌کنند)
 *   • انقضای تنبل پخش سفارش پیش از شمارش (OrderAssignmentService::expireStale)
 *   • کارت‌های آماری + حقوق دوره جاری/گذشته + موجودی کیف پول
 *   • پخش‌شده‌های در انتظار پذیرش + آخرین کارمندان + ترکیب مدل‌های حقوقی
 *   • فاز ۹ — نمای تحلیلی ۱۴ روز (سفارش‌های کافی‌نت + واریزی کیف) با AnalyticsService
 *
 * نمودارهای Chart.js با همان JS قبلی صفحه (pages/coffeenet/dashboard.js) و
 * داده از #page-data رندر می‌شوند — اسکریپت‌های بدنه در هر ناوبری
 * wire:navigate دوباره اجرا می‌شوند و رفتار SPA حفظ است.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Dashboard extends Component
{
    public Coffeenet $coffeenet;

    public function mount(Coffeenet $coffeenet): void
    {
        // عیناً گارد DashboardController@index — کافی‌نت مسیر = کافی‌نت جلسه
        abort_unless(request()->attributes->get('current_coffeenet')?->id === $coffeenet->id, 403);

        $this->coffeenet = $coffeenet;
    }

    public function render(): \Illuminate\View\View
    {
        $coffeenet = $this->coffeenet;

        // فاز ۶ — انقضای تنبل پخش
        app(OrderAssignmentService::class)->expireStale();

        $currentPeriod = now()->format('Y-m');
        $lastPeriod = now()->subMonth()->format('Y-m');

        $staffQuery = $coffeenet->staffAssignments()->where('is_active', true);

        // سفارش‌های پخش‌شدهٔ در انتظار پذیرش این کافی‌نت
        $broadcastingIds = $coffeenet->broadcasts()
            ->whereHas('order', fn ($q) => $q->where('status', 'broadcasting'))
            ->pluck('order_id');

        $stats = [
            'staff_total' => (clone $staffQuery)->count(),
            'operators' => (clone $staffQuery)->where('position', 'operator')->count(),
            'managers' => (clone $staffQuery)->where('position', 'manager')->count(),
            'salary_current' => (float) \App\Models\SalaryLog::where('coffeenet_id', $coffeenet->id)->where('period', $currentPeriod)->sum('amount'),
            'salary_last' => (float) \App\Models\SalaryLog::where('coffeenet_id', $coffeenet->id)->where('period', $lastPeriod)->sum('amount'),
            'wallet_balance' => app(WalletService::class)->balance($coffeenet),
            'orders_broadcasting' => $broadcastingIds->count(),
            'orders_active' => \App\Models\Order::where('coffeenet_id', $coffeenet->id)
                ->whereIn('status', ['accepted', 'in_progress', 'needs_info'])->count(),
            'orders_done' => \App\Models\Order::where('coffeenet_id', $coffeenet->id)
                ->whereIn('status', ['delivered', 'completed'])->count(),
        ];

        $recentStaff = $coffeenet->staffAssignments()
            ->with([
                'user:id,name,family,last_login_at',
                'salarySetting' => fn ($q) => $q->where('salary_settings.coffeenet_id', $coffeenet->id),
            ])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // توزیع مدل‌های حقوقی کارمندان فعال
        $salaryMix = $coffeenet->staffAssignments()
            ->where('is_active', true)
            ->with(['salarySetting' => fn ($q) => $q->where('salary_settings.coffeenet_id', $coffeenet->id)])
            ->get()
            ->filter(fn ($s) => $s->salarySetting?->type !== null)
            ->groupBy(fn ($s) => $s->salarySetting->type->value)
            ->map(fn ($group) => $group->count());

        // فاز ۹ — نمای تحلیلی ۱۴ روز اخیر (سفارش‌های این کافی‌نت + واریزی کیف)
        $analytics = app(AnalyticsService::class)->forScope(['coffeenet_id' => $coffeenet->id]);
        $from = now()->subDays(13)->startOfDay();
        $to = now()->endOfDay();

        $wallet = \App\Models\Wallet::query()
            ->where('holder_type', Coffeenet::class)
            ->where('holder_id', $coffeenet->id)
            ->first();

        $chartData = [
            'daily' => $analytics->daily($from, $to),
            'status' => $analytics->statusBreakdown($from, $to),
            'credits' => $wallet ? $analytics->walletCreditsDaily($wallet->id, $from, $to) : [],
        ];

        return view('livewire.coffeenet.core.dashboard', [
            'coffeenet' => $coffeenet,
            'stats' => $stats,
            'recentStaff' => $recentStaff,
            'salaryMix' => $salaryMix,
            'currentPeriodLabel' => jdate(now())->format('F Y'),
            'chartData' => $chartData,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'داشبورد',
            'breadcrumb' => 'پنل کافی‌نت ← داشبورد',
            'htmlTitle' => 'داشبورد',
        ]);
    }
}
