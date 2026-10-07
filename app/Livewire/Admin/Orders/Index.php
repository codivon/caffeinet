<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Livewire\Admin\Orders\Concerns\InteractsWithOrderActions;
use App\Livewire\Admin\Support\Ui;
use App\Models\Order;
use App\Services\Orders\OrderAssignmentService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «مدیریت سفارش‌ها» پنل مدیریت کل — نسخهٔ Livewire 4 (Task 3-c).
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\OrdersController منتقل شده:
 *   • data(): فیلتر وضعیت (چیپ‌های active/done + وضعیت تکی)، فیلتر امتیاز نظرسنجی (v33)،
 *     جستجوی شماره سفارش/نام/موبایل مشتری، مرتب‌سازی نزولی و صفحه‌بندی ۲۰تایی
 *   • counts(): چیپ‌های آماری (broadcasting/queued/active/done/total)
 *   • cancel(): لغو با دلیل اجباری (حداقل ۳ حرف) → cancelByAdmin سرویس تخصیص
 *   • rebroadcast(): ری‌پخش سفارش صف‌شده
 *   • assign(): تخصیص دستی به کافی‌نت (+ اپراتور اختیاری) با همان پیام فارسی
 *     (endpointهای POST/PATCH کنترلر دست‌نخورده ماندند تا JSهای دیگر نشکنند)
 *
 * تفاوت با نسخهٔ قبلی: به‌جای AJAX رندر سمت سرور با wire:model.live + #[Url]؛
 * «جزئیات» لینک wire:navigate به صفحهٔ کامل (admin.orders.view) است و حذف نرم
 * از مسیر قبلی (data-trash + trash.js) با همان ظاهر حفظ شده است.
 */
#[Layout('back.layouts.panel')]
class Index extends Component
{
    use InteractsWithOrderActions;
    use WithPagination;

    /** فیلتر وضعیت: '' | active | done | مقدار enum */
    #[Url(as: 'status')]
    public string $status = '';

    /** v33 — فیلتر امتیاز نظرسنجی: '' | 1..5 | low | high | none */
    #[Url(as: 'rating')]
    public string $rating = '';

    /** جستجوی شماره سفارش / نام / موبایل مشتری */
    #[Url(as: 'q')]
    public string $q = '';

    public function mount(): void
    {
        $this->abortIfInvalidStatus();
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['q', 'status', 'rating'], true)) {
            $this->resetPage();
        }
    }

    /** بعد از حذف نرم از ردیف‌ها (trash.js) — رندر مجدد لیست و چیپ‌ها */
    #[On('trash-changed-3c')]
    public function onTrashChanged(): void
    {
        // بدون عملیات؛ فراخوانی شنونده باعث رندر مجدد و تازه‌شدن لیست می‌شود
    }

    /** کلیک چیپ آماری = فیلتر سریع (مثل data-status نسخهٔ JS) */
    public function filterByStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $assignment = $this->assignment();
        $assignment->expireStale();

        $query = Order::query()
            ->with([
                'service' => fn ($q) => $q->select(['id', 'name']),
                'customer' => fn ($q) => $q->select(['id', 'name', 'family', 'mobile']),
                'coffeenet' => fn ($q) => $q->select(['id', 'name']),
                'rating',
            ]);

        if ($this->status !== '') {
            if ($this->status === 'active') {
                $query->whereIn('status', [
                    OrderStatus::Broadcasting->value,
                    OrderStatus::Queued->value,
                    OrderStatus::Accepted->value,
                    OrderStatus::Paid->value,
                    OrderStatus::InProgress->value,
                    OrderStatus::NeedsInfo->value,
                ]);
            } elseif ($this->status === 'done') {
                $query->whereIn('status', [
                    OrderStatus::Delivered->value,
                    OrderStatus::Completed->value,
                ]);
            }
        }

        // v33 — فیلتر امتیاز نظرسنجی
        if ($this->rating !== '') {
            if ($this->rating === 'none') {
                $query->whereDoesntHave('rating');
            } elseif ($this->rating === 'low') {
                $query->whereHas('rating', fn ($r) => $r->where('rating', '<=', 2));
            } elseif ($this->rating === 'high') {
                $query->whereHas('rating', fn ($r) => $r->where('rating', '>=', 4));
            } else {
                $value = (int) $this->rating;

                if ($value >= 1 && $value <= 5) {
                    $query->whereHas('rating', fn ($r) => $r->where('rating', $value));
                }
            }
        }

        if ($q = trim($this->q)) {
            $query->where(function (Builder $w) use ($q) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('family', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%"));
            });
        }

        $rows = $query->orderByDesc('id')->paginate(20);

        return view('livewire.admin.orders.index', [
            'statusBadge' => fn (OrderStatus $s) => Ui::statusBadge($s),
            'rows' => $rows,
            'chips' => $this->counts(),
            'statuses' => collect(OrderStatus::cases())
                ->map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()])
                ->all(),
            'trashedCount' => Order::onlyTrashed()->count(),
            'assignCoffeenets' => $this->assignOpen ? $this->coffeenetOptions() : [],
            'assignOperators' => $this->assignOpen && $this->assignCoffeenetId ? $this->operatorOptions($this->assignCoffeenetId) : [],
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'مدیریت سفارش‌ها',
            'breadcrumb' => 'پنل مدیریت کل ← سفارش‌ها',
            'htmlTitle' => 'سفارش‌ها',
        ]);
    }

    /* ================== منطق داخلی (عیناً از کنترلر) ================== */

    /** چیپ‌های آماری — عیناً counts() کنترلر */
    protected function counts(): array
    {
        $counts = Order::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $map = fn (array $statuses) => (int) collect($statuses)->sum(fn ($s) => $counts->get($s, 0));

        return [
            'broadcasting' => (int) $counts->get(OrderStatus::Broadcasting->value, 0),
            'queued' => (int) $counts->get(OrderStatus::Queued->value, 0),
            'active' => $map([OrderStatus::Accepted->value, OrderStatus::InProgress->value, OrderStatus::NeedsInfo->value]),
            'done' => $map([OrderStatus::Delivered->value, OrderStatus::Completed->value]),
            'total' => (int) $counts->sum(),
        ];
    }

    protected function abortIfInvalidStatus(): void
    {
        if ($this->status === '' || in_array($this->status, ['active', 'done'], true)) {
            return;
        }

        // وضعیت نامعتبر: مثل data() کنترلر ۴۲۲ می‌داد؛ در نسخهٔ Livewire به حالت خالی برمی‌گردیم
        if (OrderStatus::tryFrom($this->status) === null) {
            $this->status = '';
        }
    }
}
