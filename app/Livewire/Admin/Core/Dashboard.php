<?php

namespace App\Livewire\Admin\Core;

use App\Services\Analytics\AnalyticsService;
use App\Services\Orders\OrderAssignmentService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * داشبورد مدیریت کل — کامپوننت Livewire [Task 3-b]
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\DashboardController@index
 * منتقل شده است (کنترلر حذف نمی‌شود؛ فقط رندر صفحه به Livewire منتقل شد):
 *   • انقضای تنبل پخش سفارش پیش از شمارش (OrderAssignmentService::expireStale)
 *   • کارت‌های آماری + آیتم‌های در انتظار تعیین‌تکلیف
 *   • کاربران آنلاین (همان آستانهٔ offline_threshold_seconds تنظیمات)
 *   • آخرین فعالیت‌ها (AuditLog) + نمای تحلیلی ۱۴ روز (AnalyticsService)
 *
 * نمودارهای Chart.js با همان JS قبلی صفحه رندر می‌شوند؛ داده از
 * #page-data (data-payload) خوانده می‌شود — اسکریپت‌های بدنه در هر
 * ناوبری wire:navigate دوباره اجرا می‌شوند و رفتار SPA حفظ است.
 */
#[Layout('back.layouts.panel')]
class Dashboard extends Component
{
    public function render()
    {
        app(OrderAssignmentService::class)->expireStale();

        $user = auth()->user();

        $stats = [
            'users_total' => \App\Models\User::count(),
            'users_new_week' => \App\Models\User::where('created_at', '>=', now()->subDays(7))->count(),
            'organizations_total' => \App\Models\Organization::count(),
            'organizations_pending' => \App\Models\Organization::where('status', 'pending')->count(),
            'coffeenets_total' => \App\Models\Coffeenet::count(),
            'coffeenets_pending' => \App\Models\Coffeenet::where('status', 'pending')->count(),
            'services_active' => \App\Models\Service::where('is_active', true)->count(),
            'transactions_today' => (int) \App\Models\Transaction::where('type', 'credit')
                ->whereDate('created_at', today())->sum('amount'),
            'orders_broadcasting' => \App\Models\Order::where('status', 'broadcasting')->count(),
            'orders_queued' => \App\Models\Order::where('status', 'queued')->count(),
            'orders_active' => \App\Models\Order::whereIn('status', ['accepted', 'in_progress', 'needs_info'])->count(),
        ];

        $pendingItems = [
            [
                'title' => 'سفارش در صف تعیین‌تکلیف',
                'count' => $stats['orders_queued'],
                'url' => route('admin.orders.index', ['status' => 'queued']),
                'icon' => 'orders',
            ],
            [
                'title' => 'سازمان در انتظار بررسی',
                'count' => $stats['organizations_pending'],
                'url' => route('admin.organizations.index', ['status' => 'pending']),
                'icon' => 'building',
            ],
            [
                'title' => 'کافی‌نت در انتظار تأیید',
                'count' => $stats['coffeenets_pending'],
                'url' => route('admin.coffeenets.index', ['status' => 'pending']),
                'icon' => 'store',
            ],
            [
                'title' => 'درخواست برداشت در انتظار پرداخت',
                'count' => \App\Models\Withdrawal::where('status', 'pending')->count(),
                'url' => route('admin.withdrawals.index', ['status' => 'pending']),
                'icon' => 'wallet',
            ],
        ];

        // v36 — کاربران آنلاین (همان آستانهٔ «آفلاین» تنظیمات که پوش سیستمی
        // هم از آن استفاده می‌کند؛ مشتری‌ها و کارمندان جدا)
        $onlineSince = now()->subSeconds(offline_threshold_seconds());

        $roleLabels = [
            'super_admin' => 'مدیر کل',
            'admin' => 'مدیر دستیار',
            'org_manager' => 'مدیر سازمان',
            'coffeenet_manager' => 'مدیر کافی‌نت',
            'operator' => 'اپراتور',
        ];

        $onlineCustomers = \App\Models\User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'customer'))
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $onlineSince)
            ->orderByDesc('last_seen_at')
            ->limit(12)
            ->get(['id', 'name', 'family', 'mobile', 'last_seen_at']);

        $onlineStaff = \App\Models\User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn(
                'name',
                ['super_admin', 'admin', 'org_manager', 'coffeenet_manager', 'operator'],
            ))
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $onlineSince)
            ->orderByDesc('last_seen_at')
            ->limit(12)
            ->get(['id', 'name', 'family', 'mobile', 'last_seen_at'])
            ->map(function ($u) use ($roleLabels) {
                $u->position_label = $roleLabels[$u->getRoleNames()->first()] ?? 'کارمند';

                return $u;
            });

        $onlineUsers = [
            'customers' => $onlineCustomers,
            'staff' => $onlineStaff,
        ];

        $recentAudits = \App\Models\AuditLog::with('user')
            ->latest('id')
            ->limit(6)
            ->get();

        // فاز ۹ — نمای تحلیلی ۱۴ روز اخیر (سراسری)
        $analytics = app(AnalyticsService::class)->forScope([]);
        $from = now()->subDays(13)->startOfDay();
        $to = now()->endOfDay();

        $chartData = [
            'daily' => $analytics->daily($from, $to),
            'status' => $analytics->statusBreakdown($from, $to),
            'paid_total' => (clone $analytics->ordersQuery())
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('paid_at')
                ->whereNotIn('status', [\App\Enums\OrderStatus::Cancelled->value, \App\Enums\OrderStatus::Refunded->value])
                ->count(),
        ];

        return view('livewire.admin.core.dashboard', [
            'stats' => $stats,
            'pendingItems' => $pendingItems,
            'onlineUsers' => $onlineUsers,
            'recentAudits' => $recentAudits,
            'chartData' => $chartData,
        ])->layoutData([
            'user' => $user,
            'pageTitle' => 'داشبورد مدیریت',
            'breadcrumb' => 'داشبورد',
            'htmlTitle' => 'داشبورد',
        ]);
    }
}
