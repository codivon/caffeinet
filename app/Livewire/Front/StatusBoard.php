<?php

namespace App\Livewire\Front;

use App\Models\Coffeenet;
use App\Enums\CoffeenetStatus;
use App\Services\Orders\RatingDistributionService;
use App\Services\Settings\SettingsService;
use App\Services\Settings\WorkingHoursService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * فاز ۵۲ — صفحهٔ عمومی «وضعیت زندهٔ کافی‌نت‌ها» (/status).
 *
 * شفافیت کامل برای مشتری: کدام کافی‌نت باز است، امتیازش چیست،
 * چند سفارش در صف دارد و آخرین بار کِی سفارش تحویل داده.
 * تازه‌سازی خودکار هر ۳۰ ثانیه (wire:poll) — فقط وقتی تب باز است.
 *
 * سوییچ: features.status_page (خاموش = 404).
 * لینک در فوتر/ناوبری لندینگ.
 */
#[Layout('front.layouts.landing')]
class StatusBoard extends Component
{
    public function mount(SettingsService $settings): void
    {
        if (! (bool) $settings->get('features.status_page', true)) {
            abort(404);
        }
    }

    public function render(SettingsService $settings, WorkingHoursService $workHours)
    {
        $ratings = app(RatingDistributionService::class)->coffeenetStats();

        /* صف هر کافی‌نت: سفارش‌های در جریان (پخش/پذیرفته/در حال کار/نیاز به اطلاعات) */
        $activeByNet = DB::table('orders')
            ->whereNull('deleted_at')
            ->whereIn('status', ['broadcasting', 'accepted', 'in_progress', 'needs_info', 'paid'])
            ->whereNotNull('coffeenet_id')
            ->groupBy('coffeenet_id')
            ->selectRaw('coffeenet_id as cid, COUNT(*) as active_count')
            ->pluck('active_count', 'cid');

        /* آخرین تحویل هر کافی‌نت */
        $lastDelivery = DB::table('orders')
            ->whereNull('deleted_at')
            ->whereIn('status', ['delivered', 'completed'])
            ->whereNotNull('coffeenet_id')
            ->groupBy('coffeenet_id')
            ->selectRaw('coffeenet_id as cid, MAX(delivered_at) as last_delivered')
            ->pluck('last_delivered', 'cid');

        /* تحویل‌های ۷ روز اخیر */
        $weekDeliveries = DB::table('orders')
            ->whereNull('deleted_at')
            ->whereIn('status', ['delivered', 'completed'])
            ->whereNotNull('coffeenet_id')
            ->where('delivered_at', '>=', now()->subDays(7))
            ->groupBy('coffeenet_id')
            ->selectRaw('coffeenet_id as cid, COUNT(*) as cnt')
            ->pluck('cnt', 'cid');

        $nets = Coffeenet::query()
            ->where('status', CoffeenetStatus::Approved->value)
            ->with(['city:id,name', 'province:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (Coffeenet $net) use ($ratings, $activeByNet, $lastDelivery, $weekDeliveries) {
                $stat = $ratings[$net->id] ?? null;
                $last = isset($lastDelivery[$net->id]) ? \Illuminate\Support\Carbon::parse($lastDelivery[$net->id]) : null;

                return [
                    'id'        => $net->id,
                    'name'      => $net->name,
                    'city'      => $net->city?->name ?? $net->province?->name ?? '—',
                    'rating'    => $stat['avg'] ?? null,
                    'votes'     => $stat['votes'] ?? 0,
                    'active'    => (int) ($activeByNet[$net->id] ?? 0),
                    'week'      => (int) ($weekDeliveries[$net->id] ?? 0),
                    'last_at'   => $last?->toIso8601String(),
                    'last_fa'   => $last ? fa_date($last) : null,
                    // بار: ≤۱ آزاد، ۲-۴ متعادل، ≥۵ شلوغ
                    'load'      => ($a = (int) ($activeByNet[$net->id] ?? 0)) <= 1 ? 'free' : ($a <= 4 ? 'normal' : 'busy'),
                ];
            });

        /* مجموع سفارش‌های در صف سراسری (بدون کافی‌نت) */
        $queued = DB::table('orders')->whereNull('deleted_at')->whereIn('status', ['queued'])->count();

        $wh = $workHours->status();
        $workStatus = [
            'enabled'  => $wh['enabled'],
            'open'     => $wh['open'],
            'today'    => $wh['day_today_open'],
            'range'    => fa_digits($wh['start']).' تا '.fa_digits($wh['end']),
            'message'  => $wh['message'],
        ];

        $footerCategories = collect(); // فوتر لندینگ — صفحهٔ وضعیت دسته ندارد

        return view('livewire.front.status-board', [
            'nets'          => $nets,
            'queued'        => $queued,
            'openCount'     => $nets->count(),
            'avgRating'     => collect($ratings)->filter(fn ($s) => $s['votes'] > 0)->avg('avg'),
            'totalActive'   => (int) $activeByNet->sum(),
        ])->layoutData([
            'workStatus'        => $workStatus,
            'landingCategories' => collect(),
            'footerCategories'  => $footerCategories,
            'htmlTitle'         => 'وضعیت زندهٔ کافی‌نت‌ها',
            'pageTitle'         => 'وضعیت زنده',
        ]);
    }
}
