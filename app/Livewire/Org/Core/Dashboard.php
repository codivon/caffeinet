<?php

namespace App\Livewire\Org\Core;

use App\Livewire\Org\Concerns\InteractsWithOrgContext;
use App\Services\Analytics\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * داشبورد سازمان — کامپوننت Livewire [Task 6]
 *
 * منطق عیناً از App\Http\Controllers\Back\Org\DashboardController@index
 * منتقل شده است (کنترلر حذف نمی‌شود؛ فقط رندر صفحه به Livewire منتقل شد):
 *   • کارت‌های آماری کیف پول/کافی‌نت‌ها/برداشت‌ها
 *   • آخرین کافی‌نت‌های معرفی‌شده
 *   • نمای تحلیلی ۳۰ روز اخیر (AnalyticsService — کافی‌نت‌های زیرمجموعه + واریزی کیف سازمان)
 *
 * نمودارهای Chart.js با همان JS قبلی صفحه (org/dashboard.js) از #page-data
 * رندر می‌شوند — اسکریپت‌های بدنه در هر ناوبری wire:navigate دوباره اجرا می‌شوند.
 *
 * زمینهٔ سازمان خودِ کامپوننت (نه view share) — عیناً منطق EnsureOrgContext.
 */
#[Layout('back.layouts.org')]
class Dashboard extends Component
{
    use InteractsWithOrgContext;

    public function render(): \Illuminate\View\View
    {
        $org = $this->resolveOrgContext();

        // زمینهٔ سازمان نامعتبر است (ریدایرکت choose/login ثبت شده) — رندر خنثی بدون کوئری
        if (! $org) {
            return $this->neutralRender();
        }

        $wallet = $org->wallet;

        $stats = [
            'wallet_balance' => (float) $wallet?->balance ?? 0.0,
            'total_rewards' => (float) \App\Models\Transaction::query()
                ->where('ref_type', 'reward')
                ->whereHas('wallet', fn ($w) => $w->where('holder_type', \App\Models\Organization::class)
                    ->where('holder_id', $org->id))
                ->sum('amount'),
            'coffeenets_total' => $org->coffeenets()->count(),
            'coffeenets_approved' => $org->coffeenets()->where('status', 'approved')->count(),
            'coffeenets_pending' => $org->coffeenets()->where('status', 'pending')->count(),
            'withdrawals_pending' => $wallet ? \App\Models\Withdrawal::where('wallet_id', $wallet->id)->where('status', 'pending')->count() : 0,
            'withdrawals_total' => $wallet ? (float) \App\Models\Withdrawal::where('wallet_id', $wallet->id)->where('status', 'paid')->sum('amount') : 0.0,
        ];

        $recentCoffeenets = $org->coffeenets()->with('province:id,name')->latest('id')->limit(5)->get();

        // فاز ۹ — نمای تحلیلی ۳۰ روز اخیر (کافی‌نت‌های زیرمجموعه + واریزی کیف سازمان)
        $analytics = app(AnalyticsService::class)->forScope(['organization_id' => $org->id]);
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        $chartData = [
            'credits' => $wallet ? $analytics->walletCreditsDaily($wallet->id, $from, $to) : [],
            'coffeenets' => $analytics->coffeenetPerformance($from, $to, 8),
        ];

        return view('livewire.org.core.dashboard', [
            'organization' => $org,
            'stats' => $stats,
            'recentCoffeenets' => $recentCoffeenets,
            'chartData' => $chartData,
        ])->layoutData([
            'organization' => $org,
            'user' => auth()->user(),
            'pageTitle' => 'داشبورد سازمان',
            'breadcrumb' => 'پنل سازمان ← داشبورد',
            'htmlTitle' => 'داشبورد',
        ]);
    }

    /** رندر خنثی — فقط وقتی زمینهٔ سازمان نامعتبر شد (بدون هیچ کوئری اضافه) */
    protected function neutralRender(): \Illuminate\View\View
    {
        $org = static::neutralOrganization();

        return view('livewire.org.core.dashboard', [
            'organization' => $org,
            'stats' => [
                'wallet_balance' => 0.0, 'total_rewards' => 0.0,
                'coffeenets_total' => 0, 'coffeenets_approved' => 0, 'coffeenets_pending' => 0,
                'withdrawals_pending' => 0, 'withdrawals_total' => 0.0,
            ],
            'recentCoffeenets' => collect(),
            'chartData' => ['credits' => [], 'coffeenets' => []],
        ])->layoutData([
            'organization' => $org,
            'user' => auth()->user(),
            'pageTitle' => 'داشبورد سازمان',
            'breadcrumb' => 'پنل سازمان ← داشبورد',
            'htmlTitle' => 'داشبورد',
        ]);
    }
}
