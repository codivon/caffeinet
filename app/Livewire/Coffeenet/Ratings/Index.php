<?php

namespace App\Livewire\Coffeenet\Ratings;

use App\Models\Coffeenet;
use App\Models\Order;
use App\Models\RatingOption;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «نظرسنجی‌های مشتریان» پنل کافی‌نت — نسخهٔ Livewire 4 (Task 4).
 *
 * منطق عیناً از RatingsController (v33/v34) مسیر coffeenetIndex/data/stats
 * منتقل شده — فقط نظرات سفارش‌های همین کافی‌نت (بدون مدیریت گزینه‌ها که
 * مخصوص مدیر کل است):
 *   • data(): فیلترهای امتیاز کلی/اپراتور/دلیل + جستجو (سفارش، مشتری، متن دیدگاه)
 *     + بازهٔ تاریخ + مرتب‌سازی (جدیدترین/بدترین/بهترین)
 *   • stats(): چیپ‌های آماری + توزیع ۱..۵ + برترین دلایل از اسنپ‌شات JSON
 *
 * endpointهای AJAX کنترلر (ratings.data / ratings.stats) دست‌نخورده ماندند.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    public Coffeenet $coffeenet;

    #[Url(as: 'q')]
    public string $q = '';

    /** امتیاز کلی: '' | 1..5 | low | high | with_operator | with_comment */
    #[Url(as: 'rating')]
    public string $rating = '';

    /** امتیاز اپراتور: '' | 1..5 | low | high */
    #[Url(as: 'operator_rating')]
    public string $operatorRating = '';

    #[Url(as: 'operator_id')]
    public string $operatorId = '';

    #[Url(as: 'option_id')]
    public string $optionId = '';

    /** newest | worst | best */
    #[Url(as: 'sort')]
    public string $sort = 'newest';

    #[Url(as: 'from')]
    public string $from = '';

    #[Url(as: 'to')]
    public string $to = '';

    public function mount(Coffeenet $coffeenet): void
    {
        // فاز ۵۹ (F2 ممیزی) — گارد تنانت: کافی‌نتِ مسیر باید همان کافی‌نتِ جلسه باشد
        abort_unless(request()->attributes->get('current_coffeenet')?->id === $coffeenet->id, 403);

        $this->coffeenet = $coffeenet;
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['q', 'rating', 'operatorRating', 'operatorId', 'optionId', 'sort', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    /** ری‌ست همهٔ فیلترها — عیناً rt-clear نسخهٔ JS */
    public function clearFilters(): void
    {
        $this->q = '';
        $this->rating = '';
        $this->operatorRating = '';
        $this->operatorId = '';
        $this->optionId = '';
        $this->sort = 'newest';
        $this->from = '';
        $this->to = '';
        $this->resetPage();
    }

    /** کلیک چیپ/ردیف توزیع = فیلتر سریع امتیاز — عیناً data-rating-filter نسخهٔ JS */
    public function filterByRating(string $value): void
    {
        $this->rating = $value;
        $this->resetPage();
    }

    /** کلیک «پرتکرارترین دلایل» = فیلتر گزینهٔ معادل یا جستجوی متن */
    public function filterByReason(string $title): void
    {
        $option = RatingOption::query()->where('title', $title)->first();

        if ($option) {
            $this->optionId = (string) $option->id;
        } else {
            $this->q = $title;
        }

        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $coffeenetId = (int) $this->coffeenet->id;

        $rows = $this->ordersQuery($coffeenetId)->orderByDesc('orders.id')->distinct()->paginate(20)
            ->through(fn (Order $order) => $this->rowPayload($order));

        return view('livewire.coffeenet.ratings.index', [
            'rows' => $rows,
            'stats' => $this->stats($coffeenetId),
            'options' => RatingOption::query()->ordered()->get(),
            'operators' => $this->operatorFilterList($coffeenetId),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'نظرسنجی‌های مشتریان',
            'breadcrumb' => 'پنل کافی‌نت ← نظرسنجی‌ها',
            'htmlTitle' => 'نظرسنجی‌ها',
        ]);
    }

    /** کوئری نظرات — عیناً data() کنترلر (با محدودهٔ کافی‌نت) */
    protected function ordersQuery(int $coffeenetId): \Illuminate\Database\Eloquent\Builder
    {
        $query = Order::query()
            ->whereHas('rating')
            ->where('coffeenet_id', $coffeenetId)
            ->with([
                'rating',
                'service' => fn ($q) => $q->select(['id', 'name']),
                'customer' => fn ($q) => $q->select(['id', 'name', 'family', 'mobile']),
                'coffeenet' => fn ($q) => $q->select(['id', 'name']),
                'operator' => fn ($q) => $q->select(['id', 'name', 'family']),
            ]);

        // فیلتر اپراتور
        if ($op = (int) $this->operatorId) {
            $query->where('operator_id', $op);
        }

        // فیلتر امتیاز کلی (ستاره‌ها — امتیاز کافی‌نت)
        $rating = $this->rating;
        if ($rating !== '') {
            if ($rating === 'low') {
                $query->whereHas('rating', fn ($q) => $q->where('rating', '<=', 2));
            } elseif ($rating === 'high') {
                $query->whereHas('rating', fn ($q) => $q->where('rating', '>=', 4));
            } elseif ($rating === 'with_operator') {
                $query->whereHas('rating', fn ($q) => $q->whereNotNull('operator_rating'));
            } elseif ($rating === 'with_comment') {
                $query->whereHas('rating', fn ($q) => $q->whereNotNull('comment')->where('comment', '!=', ''));
            } else {
                $value = (int) $rating;
                if ($value >= 1 && $value <= 5) {
                    $query->whereHas('rating', fn ($q) => $q->where('rating', $value));
                }
            }
        }

        // فیلتر امتیاز اپراتور
        $opRating = $this->operatorRating;
        if ($opRating !== '') {
            if ($opRating === 'low') {
                $query->whereHas('rating', fn ($q) => $q->whereNotNull('operator_rating')->where('operator_rating', '<=', 2));
            } elseif ($opRating === 'high') {
                $query->whereHas('rating', fn ($q) => $q->whereNotNull('operator_rating')->where('operator_rating', '>=', 4));
            } else {
                $value = (int) $opRating;
                if ($value >= 1 && $value <= 5) {
                    $query->whereHas('rating', fn ($q) => $q->where('operator_rating', $value));
                }
            }
        }

        // فیلتر دلیل (گزینه) — LIKE دقیق روی اسنپ‌شات JSON (با کامای بعد از id تا ۱۳ با ۱ اشتباه نشود)
        if ($optionId = (int) $this->optionId) {
            $needle = '%"id":'.$optionId.',%';
            $query->whereHas('rating', fn ($q) => $q->where('options', 'like', $needle));
        }

        // جستجو: شماره سفارش / نام و موبایل مشتری / متن دیدگاه
        if ($q = trim($this->q)) {
            $query->where(function ($w) use ($q) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('family', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%"))
                    ->orWhereHas('rating', fn ($r) => $r->where('comment', 'like', "%{$q}%"));
            });
        }

        // بازهٔ تاریخ
        if ($from = trim($this->from)) {
            $query->whereHas('rating', fn ($r) => $r->whereDate('rated_at', '>=', $from));
        }
        if ($to = trim($this->to)) {
            $query->whereHas('rating', fn ($r) => $r->whereDate('rated_at', '<=', $to));
        }

        // مرتب‌سازی
        if ($this->sort === 'worst') {
            $query->leftJoin('order_ratings as r_worst', 'r_worst.order_id', '=', 'orders.id')
                ->orderBy('r_worst.rating')
                ->select('orders.*');
        } elseif ($this->sort === 'best') {
            $query->leftJoin('order_ratings as r_best', 'r_best.order_id', '=', 'orders.id')
                ->orderByDesc('r_best.rating')
                ->select('orders.*');
        }

        return $query;
    }

    /** چیپ‌های آماری + توزیع + برترین دلایل — عیناً stats() کنترلر (با محدودهٔ کافی‌نت) */
    protected function stats(int $coffeenetId): array
    {
        $base = Order::query()->whereHas('rating')->where('coffeenet_id', $coffeenetId);

        $total = (clone $base)->count();
        $avg = (clone $base)->join('order_ratings as rs', 'rs.order_id', '=', 'orders.id')->avg('rs.rating');
        $avgOperator = (clone $base)->join('order_ratings as ro', 'ro.order_id', '=', 'orders.id')
            ->whereNotNull('ro.operator_rating')->avg('ro.operator_rating');
        $low = (clone $base)->join('order_ratings as rl', 'rl.order_id', '=', 'orders.id')
            ->where('rl.rating', '<=', 2)->count();

        $distribution = (clone $base)->join('order_ratings as rd', 'rd.order_id', '=', 'orders.id')
            ->groupBy('rd.rating')
            ->selectRaw('rd.rating as r, count(*) as c')
            ->pluck('c', 'r')
            ->map(fn ($c) => (int) $c)
            ->all();

        // برترین دلایل (از اسنپ‌شات JSON — نتیجه مستقل از حذف گزینه‌ها)
        $reasons = [];
        (clone $base)->join('order_ratings as rr', 'rr.order_id', '=', 'orders.id')
            ->whereNotNull('rr.options')
            ->select('rr.options')
            ->chunk(500, function ($chunk) use (&$reasons) {
                foreach ($chunk as $row) {
                    foreach (json_decode((string) $row->options, true) ?: [] as $opt) {
                        $title = trim((string) ($opt['title'] ?? ''));
                        if ($title !== '') {
                            $reasons[$title] = ($reasons[$title] ?? 0) + 1;
                        }
                    }
                }
            });
        arsort($reasons);

        $dist = [];
        for ($i = 1; $i <= 5; $i++) {
            $dist[$i] = (int) ($distribution[$i] ?? 0);
        }

        return [
            'total' => $total,
            'avg' => $avg !== null ? round((float) $avg, 1) : null,
            'avg_operator' => $avgOperator !== null ? round((float) $avgOperator, 1) : null,
            'low' => $low,
            'distribution' => $dist,
            'top_reasons' => array_slice($reasons, 0, 6, true),
        ];
    }

    /** سطر جدول — عیناً rowPayload() کنترلر (مسیر جزئیات سفارش: فهرست سفارش‌ها با جستجو) */
    protected function rowPayload(Order $order): array
    {
        $rating = $order->rating;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'service_name' => $order->service?->name ?? '—',
            'customer_name' => trim(($order->customer?->name ?? '').' '.($order->customer?->family ?? '')) ?: '—',
            'customer_mobile' => $order->customer?->mobile,
            'operator_name' => $order->operator ? trim(($order->operator->name ?? '').' '.($order->operator->family ?? '')) : null,
            'rating' => (int) $rating->rating,
            'operator_rating' => $rating->operator_rating !== null ? (int) $rating->operator_rating : null,
            'comment' => $rating->comment,
            'options' => collect($rating->options ?? [])->map(fn ($o) => [
                'title' => (string) ($o['title'] ?? ''),
                'type' => (string) ($o['type'] ?? 'pos'),
            ])->all(),
            'rated_at_fa' => $rating->rated_at ? fa_date($rating->rated_at, 'Y/m/d H:i') : null,
            'view_url' => route('coffeenet.orders.index', ['coffeenet' => $this->coffeenet->id, 'q' => $order->order_number]),
        ];
    }

    /** اپراتورهایی که امتیاز اپراتور دارند — عیناً operatorFilterList() کنترلر (با محدودهٔ کافی‌نت) */
    protected function operatorFilterList(int $coffeenetId): \Illuminate\Support\Collection
    {
        return User::query()
            ->whereHas('operatedOrders.rating', fn ($q) => $q->whereNotNull('operator_rating'))
            ->whereHas('operatedOrders', fn ($q) => $q->where('coffeenet_id', $coffeenetId))
            ->orderBy('name')
            ->get(['id', 'name', 'family'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->full_name]);
    }
}
