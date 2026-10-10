<?php

namespace App\Livewire\Operator\Core;

use App\Enums\OrderStatus;
use App\Livewire\Operator\Concerns\InteractsWithOperatorContext;
use App\Models\Order;
use App\Services\Analytics\AnalyticsService;
use App\Support\OperatorPermissions;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * داشبورد پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
 *
 * منطق عیناً از App\Http\Controllers\Back\Operator\DashboardController@index
 * منتقل شده است:
 *   • آمار بر اساس دسترسی‌های staff_assignments.permissions (فیلتر OperatorPermissions)
 *   • کارهای در جریان / نیازمند اطلاعات / تحویل‌شدهٔ ماه شمسی / مجموع کارهای من
 *   • «کارهای باز کافی‌نت» فقط با مجوز orders.view
 *   • آخرین سفارش‌ها در محدودهٔ مجاز (۸ مورد)
 *   • فاز ۹ — روند ۱۴ روزه (تحویل + درآمد) با AnalyticsService::operatorDaily
 *
 * نمودار Chart.js با همان JS قبلی صفحه رندر می‌شود؛ داده از #page-data
 * (data-payload) خوانده می‌شود — اسکریپت‌های بدنه در هر ناوبری wire:navigate
 * دوباره اجرا می‌شوند و رفتار قبلی حفظ است.
 */
#[Layout('back.operator.layouts.panel')]
class Dashboard extends Component
{
    use InteractsWithOperatorContext;

    public function render(): \Illuminate\View\View
    {
        if ($this->operatorContextFailed) {
            // زمینه از دست رفته (انتصابی نیست) — ریدایرکت به ورود ثبت شده است
            return view('livewire.operator.partials.context-redirect')
                ->layoutData($this->operatorLayoutData('داشبورد', 'پنل اپراتور ← داشبورد', 'داشبورد'));
        }

        $assignment = $this->operatorAssignment;
        $coffeenet = $this->operatorCoffeenet;
        $user = auth()->user();

        $permissions = OperatorPermissions::filter($assignment->permissions ?? []);
        $canAll = in_array('orders.view', $permissions, true);
        $canOwn = in_array('orders.view.own', $permissions, true);
        $canDashboard = in_array('dashboard.access', $permissions, true);

        // بازهٔ ماه جاری (شمسی) برای «تحویل‌شدهٔ این ماه»
        $monthStart = now()->startOfMonth();

        $myActive = Order::query()
            ->where('operator_id', $user->id)
            ->where('coffeenet_id', $coffeenet->id)
            ->whereIn('status', [OrderStatus::Accepted->value, OrderStatus::InProgress->value])
            ->count();

        $myNeedsInfo = Order::query()
            ->where('operator_id', $user->id)
            ->where('coffeenet_id', $coffeenet->id)
            ->where('status', OrderStatus::NeedsInfo->value)
            ->count();

        $myDeliveredMonth = Order::query()
            ->where('operator_id', $user->id)
            ->where('coffeenet_id', $coffeenet->id)
            ->whereIn('status', [OrderStatus::Delivered->value, OrderStatus::Completed->value])
            ->where('delivered_at', '>=', $monthStart)
            ->count();

        $myTotal = Order::query()
            ->where('operator_id', $user->id)
            ->where('coffeenet_id', $coffeenet->id)
            ->whereNot('status', OrderStatus::Cancelled->value)
            ->count();

        $netOpen = $canAll
            ? Order::query()
                ->where('coffeenet_id', $coffeenet->id)
                ->whereIn('status', [OrderStatus::Accepted->value, OrderStatus::InProgress->value, OrderStatus::NeedsInfo->value])
                ->count()
            : null;

        // آخرین سفارش‌ها در محدودهٔ مجاز
        $recent = collect();

        if ($canAll || $canOwn) {
            $recent = Order::query()
                ->where('coffeenet_id', $coffeenet->id)
                ->when(! $canAll, fn ($q) => $q->where('operator_id', $user->id))
                ->whereIn('status', [
                    OrderStatus::Accepted->value,
                    OrderStatus::InProgress->value,
                    OrderStatus::NeedsInfo->value,
                    OrderStatus::Delivered->value,
                    OrderStatus::Completed->value,
                ])
                ->with([
                    'service' => fn ($q) => $q->select(['id', 'name']),
                    'service.category' => fn ($q) => $q->select(['id', 'name', 'icon']),
                    'customer' => fn ($q) => $q->select(['id', 'name', 'family']),
                ])
                ->orderByDesc('id')
                ->limit(8)
                ->get();
        }

        $stats = [
            'my_active' => $myActive,
            'my_needs_info' => $myNeedsInfo,
            'my_delivered_month' => $myDeliveredMonth,
            'my_total' => $myTotal,
            'net_open' => $netOpen,
        ];

        // فاز ۹ — روند کارهای من (تحویل + درآمد) — ۱۴ روز اخیر
        $chartData = null;
        if ($canDashboard) {
            $analytics = app(AnalyticsService::class);
            $chartData = [
                'daily' => $analytics->operatorDaily($user->id, now()->subDays(13)->startOfDay(), now()->endOfDay()),
            ];
        }

        return view('livewire.operator.core.dashboard', [
            'coffeenet' => $coffeenet,
            'assignment' => $assignment,
            'permissions' => $permissions,
            'permissionLabels' => OperatorPermissions::CATALOG,
            'canAll' => $canAll,
            'canOwn' => $canOwn,
            'canViewOrders' => $canAll || $canOwn,
            'canDashboard' => $canDashboard,
            'stats' => $stats,
            'recent' => $recent,
            'monthLabel' => jdate(now())->format('F Y'),
            'chartData' => $chartData,
        ])->layoutData($this->operatorLayoutData('داشبورد', 'پنل اپراتور ← داشبورد', 'داشبورد'));
    }
}
