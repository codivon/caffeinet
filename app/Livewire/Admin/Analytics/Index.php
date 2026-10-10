<?php

namespace App\Livewire\Admin\Analytics;

use App\Services\Analytics\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * گزارش تحلیلی — فاز ۹ (مهاجرت به Livewire — فاز ۵۸)
 *
 * نسخهٔ واقعی صفحه (جایگزین استاب Task 3-e که عامل «در حال به‌روزرسانی این بخش…» بود):
 *   • منطق عیناً از App\Http\Controllers\Back\Admin\AnalyticsController@index منتقل شده
 *     (resolveRange پریست ۷/۳۰/۹۰/ماه جاری/ماه قبل + بازهٔ سفارشی از کوئری‌استرینگ).
 *   • سری‌های نمودار/جدول‌ها همان مسیر قبلی AJAX از روت‌های زندهٔ
 *     admin.analytics.data / admin.analytics.export خوانده می‌شوند (AnalyticsService).
 *   • Chart.js با اسکریپت‌های قبلی صفحه (back/assets/js/pages/admin/analytics/index.js)
 *     رندر می‌شود؛ دادهٔ اولیه از #page-data — اسکریپت‌های بدنه در هر ناوبری
 *     wire:navigate دوباره اجرا می‌شوند و رفتار SPA حفظ است.
 */
#[Layout('back.layouts.panel')]
class Index extends Component
{
    public function render()
    {
        [$from, $to, $preset] = AnalyticsService::resolveRange(
            request()->query('preset'),
            request()->query('from'),
            request()->query('to')
        );

        $service = app(AnalyticsService::class)->forScope([]);

        return view('livewire.admin.analytics.index', [
            'preset' => $preset,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rangeLabel' => jdate($from)->format('Y/m/d').' تا '.jdate($to)->format('Y/m/d'),
            'summary' => $service->summary($from, $to),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'گزارش تحلیلی',
            'breadcrumb' => 'پنل مدیریت کل ← گزارش تحلیلی',
            'htmlTitle' => 'گزارش تحلیلی',
        ]);
    }
}
