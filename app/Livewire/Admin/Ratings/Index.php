<?php

namespace App\Livewire\Admin\Ratings;

use App\Models\Coffeenet;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\RatingOption;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «نظرسنجی‌های مشتریان» پنل مدیریت کل — نسخهٔ Livewire 4 (Task 3-c).
 *
 * منطق عیناً از RatingsController (v33/v34) منتقل شده:
 *   • data(): همهٔ نظرات + فیلترهای امتیاز کلی/اپراتور/کافی‌نت/اپراتور/دلیل/
 *     جستجو (سفارش، مشتری، متن دیدگاه) + بازهٔ تاریخ + مرتب‌سازی
 *     (جدیدترین/بدترین/بهترین) — سفارش‌های بدون کافی‌نت هم نمایش داده می‌شوند (v34)
 *   • stats(): چیپ‌های آماری + توزیع ۱..۵ + برترین دلایل از اسنپ‌شات JSON
 *   • مدیریت گزینه‌های دلایل (فقط مدیر کل): افزودن/فعال‌سازی/حذف با همان
 *     اعتبارسنجی‌ها، پیام‌های فارسی و AuditLogger
 *
 * endpointهای AJAX کنترلر (data/stats/options) دست‌نخورده ماندند.
 */
#[Layout('back.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $q = '';

    /** امتیاز کلی: '' | 1..5 | low | high | with_operator | with_comment */
    #[Url(as: 'rating')]
    public string $rating = '';

    /** امتیاز اپراتور: '' | 1..5 | low | high */
    #[Url(as: 'operator_rating')]
    public string $operatorRating = '';

    #[Url(as: 'coffeenet_id')]
    public string $coffeenetId = '';

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

    /* ---------- فرم افزودن گزینه ---------- */
    public string $optionTitle = '';
    public string $optionType = 'pos';

    public function updating(string $name): void
    {
        if (in_array($name, ['q', 'rating', 'operatorRating', 'coffeenetId', 'operatorId', 'optionId', 'sort', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    /** ری‌ست همهٔ فیلترها — عیناً rt-clear نسخهٔ JS */
    public function clearFilters(): void
    {
        $this->q = '';
        $this->rating = '';
        $this->operatorRating = '';
        $this->coffeenetId = '';
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

    /* ================== مدیریت گزینه‌های دلایل (فقط مدیر کل) ================== */

    /** عیناً assertOptionsManager کنترلر */
    protected function assertOptionsManager(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->hasRole('super_admin') || $user->can('access', [User::class, 'settings'])),
            403,
            'مدیریت گزینه‌های نظرسنجی فقط برای مدیر کل فعال است.'
        );
    }

    /** عیناً storeOption کنترلر */
    public function addOption(): void
    {
        $this->assertOptionsManager();

        $data = $this->validate([
            'optionTitle' => ['required', 'string', 'min:2', 'max:100'],
            'optionType' => ['required', 'in:pos,neg'],
        ], [
            'optionTitle.required' => 'متن گزینه الزامی است.',
            'optionTitle.min' => 'متن گزینه باید حداقل ۲ حرف باشد.',
            'optionTitle.max' => 'متن گزینه حداکثر ۱۰۰ حرف است.',
            'optionType.in' => 'نوع گزینه باید نقطه قوت یا نقطه ضعف باشد.',
        ]);

        $maxSort = (int) RatingOption::query()->where('type', $data['optionType'])->max('sort_order');

        $option = RatingOption::query()->create([
            'title' => trim($data['optionTitle']),
            'type' => $data['optionType'],
            'is_active' => true,
            'sort_order' => $maxSort + 1,
        ]);

        AuditLogger::log('ratings.option_created', null, null, $option->only(['id', 'title', 'type', 'sort_order']),
            'افزودن گزینهٔ نظرسنجی «'.$option->title.'»');

        $this->optionTitle = '';
        $this->dispatch('lw-toast', message: 'گزینهٔ «'.$option->title.'» اضافه شد؛ از همین لحظه در نظرسنجی اپ مشتری نمایش داده می‌شود.', type: 'success');
    }

    /** فعال/غیرفعال — عیناً updateOption کنترلر (فقط is_active) */
    public function toggleOption(int $optionId): void
    {
        $this->assertOptionsManager();

        $option = RatingOption::query()->findOrFail($optionId);

        $old = $option->only(['title', 'type', 'is_active', 'sort_order']);
        $option->fill(['is_active' => ! $option->is_active])->save();

        AuditLogger::log('ratings.option_updated', null, $old, $option->only(['title', 'type', 'is_active', 'sort_order']),
            'ویرایش گزینهٔ نظرسنجی «'.$option->title.'»');

        $this->dispatch('lw-toast', message: 'گزینهٔ «'.$option->title.'» بروزرسانی شد.', type: 'success');
    }

    /** حذف گزینه — عیناً destroyOption کنترلر (اسنپ‌شات نظرات حفظ می‌ماند) */
    public function deleteOption(int $optionId): void
    {
        $this->assertOptionsManager();

        $option = RatingOption::query()->findOrFail($optionId);

        $title = $option->title;
        $old = $option->only(['id', 'title', 'type', 'is_active', 'sort_order']);

        $usage = (int) OrderRating::query()
            ->where('options', 'like', '%"id":'.$option->id.',%')
            ->count();

        $option->delete();

        AuditLogger::log('ratings.option_deleted', null, $old, null,
            'حذف گزینهٔ نظرسنجی «'.$title.'» (استفاده در '.fa_digits((string) $usage).' نظر)');

        $this->dispatch('lw-toast', message: 'گزینهٔ «'.$title.'» حذف شد.'
            .($usage ? ' تاریخچهٔ '.fa_digits((string) $usage).' نظرِ ثبت‌شده دست‌نخورده می‌ماند (اسنپ‌شات).' : ''), type: 'success');
    }

    /* ================== رندر ================== */

    public function render(): \Illuminate\View\View
    {
        $rows = $this->ordersQuery()->orderByDesc('orders.id')->distinct()->paginate(20)
            ->through(fn (Order $order) => $this->rowPayload($order));

        return view('livewire.admin.ratings.index', [
            'rows' => $rows,
            'stats' => $this->stats(),
            'options' => RatingOption::query()->ordered()->get(),
            'coffeenets' => $this->coffeenetFilterList(),
            'operators' => $this->operatorFilterList(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'نظرسنجی‌های مشتریان',
            'breadcrumb' => 'پنل مدیریت کل ← نظرسنجی‌ها',
            'htmlTitle' => 'نظرسنجی‌ها',
        ]);
    }

    /** کوئری نظرات — عیناً data() کنترلر */
    protected function ordersQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // v34: سفارش‌های بدون کافی‌نت (پردازش مستقیم توسط مدیر) هم نمایش داده شوند
        $query = Order::query()
            ->whereHas('rating')
            ->with([
                'rating',
                'service' => fn ($q) => $q->select(['id', 'name']),
                'customer' => fn ($q) => $q->select(['id', 'name', 'family', 'mobile']),
                'coffeenet' => fn ($q) => $q->select(['id', 'name']),
                'operator' => fn ($q) => $q->select(['id', 'name', 'family']),
            ]);

        // فیلتر کافی‌نت (مدیر کل)
        if ($net = (int) $this->coffeenetId) {
            $query->where('coffeenet_id', $net);
        }

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

    /** چیپ‌های آماری + توزیع + برترین دلایل — عیناً stats() کنترلر */
    protected function stats(): array
    {
        $base = Order::query()->whereHas('rating');

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

    /** سطر جدول — عیناً rowPayload() کنترلر */
    protected function rowPayload(Order $order): array
    {
        $rating = $order->rating;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'service_name' => $order->service?->name ?? '—',
            'customer_name' => trim(($order->customer?->name ?? '').' '.($order->customer?->family ?? '')) ?: '—',
            'customer_mobile' => $order->customer?->mobile,
            'coffeenet_name' => $order->coffeenet?->name ?? 'بدون کافی‌نت',
            'operator_name' => $order->operator ? trim(($order->operator->name ?? '').' '.($order->operator->family ?? '')) : null,
            'rating' => (int) $rating->rating,
            'operator_rating' => $rating->operator_rating !== null ? (int) $rating->operator_rating : null,
            'comment' => $rating->comment,
            'options' => collect($rating->options ?? [])->map(fn ($o) => [
                'title' => (string) ($o['title'] ?? ''),
                'type' => (string) ($o['type'] ?? 'pos'),
            ])->all(),
            'rated_at_fa' => $rating->rated_at ? fa_date($rating->rated_at, 'Y/m/d H:i') : null,
            'view_url' => '/admin/orders/'.$order->id.'/view',
        ];
    }

    /** لیست کافی‌نت‌ها برای فیلتر — عیناً coffeenetFilterList() کنترلر */
    protected function coffeenetFilterList(): \Illuminate\Support\Collection
    {
        return Coffeenet::query()
            ->whereHas('ordersWithRating')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Coffeenet $c) => ['id' => $c->id, 'name' => $c->name]);
    }

    /** اپراتورهایی که امتیاز اپراتور دارند — عیناً operatorFilterList() کنترلر */
    protected function operatorFilterList(): \Illuminate\Support\Collection
    {
        return User::query()
            ->whereHas('operatedOrders.rating', fn ($q) => $q->whereNotNull('operator_rating'))
            ->orderBy('name')
            ->get(['id', 'name', 'family'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->full_name]);
    }
}
