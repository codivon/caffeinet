<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * فاز ۵۴ — داشبورد مصرف مشتری: GET /api/v1/me/stats
 *
 * ورودی صفحهٔ «آمار من» در اپ مشتری (سوییچ features.customer_stats):
 *   • هزینهٔ هر ماه (۶ ماه اخیر — شمسی) برای نمودار
 *   • تعداد سفارش + میانگین هر سفارش + مجموع کل
 *   • ۵ خدمت پرتکرار کاربر
 */
class StatsController extends Controller
{
    public function stats(Request $request, SettingsService $settings): JsonResponse
    {
        if (! (bool) $settings->get('features.customer_stats', true)) {
            abort(404);
        }

        $user = $request->user();

        /* سفارش‌های پرداخت‌شدهٔ کاربر */
        $orders = Order::query()
            ->whereNull('deleted_at')
            ->where('customer_id', $user->id)
            ->whereIn('status', ['paid', 'broadcasting', 'accepted', 'in_progress', 'needs_info', 'delivered', 'completed'])
            ->whereNotNull('paid_at')
            ->with('status')
            ->get(['id', 'service_id', 'price', 'expenses', 'paid_at', 'delivered_at', 'status']);

        $total = (float) $orders->sum(fn ($o) => (float) $o->price + (float) $o->expenses);

        /* هزینهٔ ۶ ماه شمسی اخیر */
        $months = [];
        $today = now();

        for ($i = 5; $i >= 0; $i--) {
            $start = $today->copy()->subMonths($i)->startOfMonth();
            $end = $today->copy()->subMonths($i)->endOfMonth();

            // برچسب شمسی (Jalali) از هلپر fa_date — «ماه/سال»
            $label = fa_date($start, 'n') // ماه شمسی به عدد
                ? fa_date($start, 'Y/n')
                : $start->format('Y/m');

            $sum = (float) $orders
                ->filter(fn ($o) => $o->paid_at->between($start, $end))
                ->sum(fn ($o) => (float) $o->price + (float) $o->expenses);

            $months[] = [
                'label' => $label,
                'sum'   => round($sum, 2),
                'count' => $orders->filter(fn ($o) => $o->paid_at->between($start, $end))->count(),
            ];
        }

        /* ۵ خدمت پرتکرار */
        $topServices = Order::query()
            ->whereNull('deleted_at')
            ->where('customer_id', $user->id)
            ->whereIn('status', ['paid', 'broadcasting', 'accepted', 'in_progress', 'needs_info', 'delivered', 'completed'])
            ->join('services', 'services.id', '=', 'orders.service_id')
            ->groupBy('orders.service_id', 'services.name')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->selectRaw('orders.service_id as id, services.name as name, COUNT(*) as cnt, SUM(orders.price + orders.expenses) as sum')
            ->get()
            ->map(fn ($r) => [
                'id'   => (int) $r->id,
                'name' => $r->name,
                'count' => (int) $r->cnt,
                'sum'  => (float) $r->sum,
            ]);

        return response()->json([
            'data' => [
                'orders_count'   => $orders->count(),
                'total_spend'    => round($total, 2),
                'avg_order'      => $orders->isNotEmpty() ? round($total / $orders->count(), 2) : 0.0,
                'avg_delivery_minutes' => $this->avgDeliveryMinutes($orders),
                'months'         => $months,
                'top_services'   => $topServices,
            ],
        ]);
    }

    /** میانگین زمان پرداخت تا تحویل (دقیقه — ۳۰ سفارش آخرِ تحویل‌شده) */
    private function avgDeliveryMinutes($orders): ?float
    {
        $delivered = $orders
            ->filter(fn ($o) => in_array($o->status->value ?? '', ['delivered', 'completed'], true) && $o->delivered_at)
            ->take(-30);

        if ($delivered->isEmpty()) {
            return null;
        }

        $minutes = $delivered->map(fn ($o) => $o->paid_at->diffInMinutes($o->delivered_at))->avg();

        return round((float) $minutes, 1);
    }
}
