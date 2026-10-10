<?php

namespace App\Services\Orders;

use App\Models\Coffeenet;
use Illuminate\Support\Facades\DB;

/**
 * فاز ۵۲ — پخش هوشمند امتیازی: آمار پذیرش + امتیاز هر کافی‌نت.
 *
 * امتیاز = (میانگین رأی مشتری‌ها × ۲) + (نرخ پذیرش × ۱۰)
 *   • نرخ پذیرش = accepted / (accepted + rejected) — بدون داده → ۰٫۵ خنثی
 *   • دادهٔ رأی از RatingDistributionService::coffeenetStats (کش ۳۰۰ ثانیه)
 *
 * ثبت آمار:
 *   bumpAccepted()  — هنگام پذیرش سفارش (پذیرش + میانگین ثانیهٔ پاسخ)
 *   bumpRejected()  — هنگام خروج قطعی سفارش از پخش بدون پذیرش (صف شدن)
 *
 * سوییچ: features.smart_dispatch (تنظیمات ← قابلیت‌ها) — خاموش یعنی
 * ترتیب پخش مثل قبل (id) می‌ماند و فقط فیلتر امتیاز v33 کار می‌کند.
 */
class DispatchStatsService
{
    /** وزن‌های امتیاز */
    public const RATING_WEIGHT = 2.0;

    public const ACCEPTANCE_WEIGHT = 10.0;

    /** ثبت یک پذیرش موفق + به‌روزرسانی میانگین متحرک زمان پاسخ */
    public static function bumpAccepted(int $coffeenetId, ?int $responseSeconds): void
    {
        try {
            DB::table('coffeenets')->where('id', $coffeenetId)->update([
                'dispatch_accepted' => DB::raw('dispatch_accepted + 1'),
            ]);

            if ($responseSeconds !== null && $responseSeconds > 0) {
                $row = DB::table('coffeenets')->where('id', $coffeenetId)
                    ->first(['dispatch_accepted', 'dispatch_avg_seconds']);

                if ($row) {
                    $accepted = max(1, (int) $row->dispatch_accepted);
                    $prevAvg = $row->dispatch_avg_seconds !== null ? (int) $row->dispatch_avg_seconds : $responseSeconds;
                    $newAvg = (int) round((($prevAvg * ($accepted - 1)) + $responseSeconds) / $accepted);

                    DB::table('coffeenets')->where('id', $coffeenetId)
                        ->update(['dispatch_avg_seconds' => min($newAvg, 86400)]);
                }
            }
        } catch (\Throwable $e) {
            report($e); // آمار نباید پذیرش را بشکند
        }
    }

    /** ثبت «پایان مهلت بدون پذیرش» برای همهٔ گیرنده‌های یک سفارش */
    public static function bumpRejectedForOrder(int $orderId): void
    {
        try {
            DB::table('order_broadcasts')
                ->where('order_id', $orderId)
                ->distinct()
                ->pluck('coffeenet_id')
                ->each(fn ($netId) => DB::table('coffeenets')
                    ->where('id', $netId)
                    ->update(['dispatch_rejected' => DB::raw('dispatch_rejected + 1')]));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * امتیاز هر کافی‌نت (بیشتر = اولویت بیشتر در پخش).
     *
     * @param  array<int, array{avg: ?float, votes: int}>  $ratingStats  خروجی coffeenetStats()
     * @param  Coffeenet  $net
     */
    public static function score(?array $ratingStat, Coffeenet $net): float
    {
        $avg = $ratingStat['avg'] ?? null;

        $ratingPart = $avg !== null ? ((float) $avg) * self::RATING_WEIGHT : 2.5 * self::RATING_WEIGHT; // بی‌رأی: میانهٔ خنثی

        $total = ((int) $net->dispatch_accepted) + ((int) $net->dispatch_rejected);
        $rate = $total > 0 ? (int) $net->dispatch_accepted / $total : 0.5;

        return $ratingPart + ($rate * self::ACCEPTANCE_WEIGHT);
    }

    /**
     * مرتب‌سازی کافی‌نت‌های هدف بر اساس امتیاز (نزولی).
     *
     * @param  \Illuminate\Support\Collection<int, Coffeenet>  $targets
     * @return array{0: \Illuminate\Support\Collection, 1: ?string}  (targets, note)
     */
    public static function ordered($targets): array
    {
        $stats = app(RatingDistributionService::class)->coffeenetStats();

        $sorted = $targets->sortByDesc(function (Coffeenet $net) use ($stats) {
            return self::score($stats[$net->id] ?? null, $net);
        })->values();

        return [$sorted, 'مرتب‌سازی امتیازی (رأی + آمار پذیرش)'];
    }
}
