<?php

namespace App\Livewire\Front;

use App\Models\Coffeenet;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Settings\WorkingHoursService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * صفحه فرود عمومی — نسخهٔ Livewire 4 [Task 8]
 *
 * منطق عیناً از App\Http\Controllers\Front\LandingController::index منتقل شده است:
 *   • دسته‌بندی‌ها/خدمات فعال (گروه‌بندی بر اساس ریشهٔ دسته + ۸ دستهٔ برتر v21/v25)
 *   • آمار زندهٔ پلتفرم (کافی‌نت‌ها/خدمات/سفارش‌ها/مشتریان/دسته‌ها)
 *   • وضعیت لحظه‌ای ساعت کاری (WorkingHoursService)
 * لایه: front.layouts.landing — هدر/فوتر ثابت + wire:navigate برای پیوستن SPA به اپ.
 */
#[Layout('front.layouts.landing')]
class Landing extends Component
{
    public function render(WorkingHoursService $workHours): \Illuminate\View\View
    {
        /* ---------- خدمات و دسته‌بندی‌ها (داده واقعی) — عین کنترلر ---------- */
        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->whereNull('parent_id')
            ->orderBy('sort')
            ->get();

        // خدمات قابل نمایش: فعال + غیر منقضی‌وضعیت
        $services = Service::query()
            ->where('is_active', true)
            ->with('category')
            ->orderByDesc('is_featured')
            ->orderBy('sort')
            ->get()
            ->filter(fn (Service $s) => $s->availabilityState() !== 'inactive')
            ->values();

        // گروه‌بندی خدمات بر اساس دستهٔ (خودِ دسته یا والدش)
        $grouped = $services
            ->groupBy(fn (Service $s) => $s->category?->parent_id ?: $s->category_id)
            ->map(fn ($group, $rootId) => [
                'category' => $categories->firstWhere('id', $rootId),
                'services' => $group->take(6)->values(),
                'total'    => $group->count(),
            ])
            ->filter(fn ($g) => $g['category'] !== null)
            ->sortBy(fn ($g) => $g['category']->sort ?? 999) // v21: ترتیب اهمیت دسته‌ها
            ->values();

        // v21 — لندینگ فقط ۸ دستهٔ برتر را نمایش می‌دهد (بقیه در اپ)
        $landingGroups = $grouped->take(8)->values();

        // v25 — بخش خدمات لندینگ فقط «دسته‌بندی‌ها» را نشان می‌دهد
        $landingCategories = $grouped
            ->map(fn ($g) => [
                'category' => $g['category'],
                'total'    => $g['total'],
            ])
            ->values();

        // v26.1 — فوتر: ۵ دستهٔ پرخدمت‌ترین + لینک «همهٔ خدمات»
        $footerCategories = $landingCategories
            ->sortByDesc(fn ($g) => $g['total'])
            ->take(5)
            ->values();

        /* ---------- آمار زندهٔ پلتفرم ---------- */
        $stats = [
            'coffeenets' => Coffeenet::count(),
            'services'   => $services->count(),
            'orders'     => Order::count(),
            'customers'  => User::role('customer')->count(),
            'categories' => $categories->count() + $categories->flatMap->children->count(),
        ];

        /* ---------- وضعیت لحظه‌ای ساعت کاری ---------- */
        $wh = $workHours->status();
        $workStatus = [
            'enabled'  => $wh['enabled'],
            'open'     => $wh['open'],
            'today'    => $wh['day_today_open'],
            'range'    => fa_digits($wh['start']).' تا '.fa_digits($wh['end']),
            'message'  => $wh['message'],
        ];

        return view('livewire.front.landing', [
            'grouped'           => $grouped,
            'landingGroups'     => $landingGroups,
            'landingCategories' => $landingCategories,
            'footerCategories'  => $footerCategories,
            'stats'             => $stats,
            'workStatus'        => $workStatus,
        ])->layoutData([
            // هدر/فوتر لایه هم به این داده‌ها نیاز دارند (وضعیت ساعت کاری + دسته‌های فوتر)
            'workStatus'        => $workStatus,
            'landingCategories' => $landingCategories,
            'footerCategories'  => $footerCategories,
        ]);
    }
}
