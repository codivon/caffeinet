<?php

namespace App\Livewire\Admin\Orders\Concerns;

use App\Models\Coffeenet;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderAssignmentService;
use Illuminate\Validation\ValidationException;

/**
 * اکشن‌های مشترک سفارش برای کامپوننت‌های Index و View (Task 3-c).
 *
 * منطق و پیام‌های فارسی عیناً از OrdersController منتقل شده است:
 *   • assign → manualAssign سرویس تخصیص (پیام: «سفارش X به کافی‌نت «Y» تخصیص یافت.»)
 *   • cancel → cancelByAdmin با دلیل اجباری ۳..۴۹۰ حرف
 *   • rebroadcast → ری‌پخش سفارش صف‌شده
 *   • updateStatus → گذار مجاز ماشین وضعیت با یادداشت (لغو/بازگشت وجه اجباری)
 *
 * مودال‌ها با وضعیت کامپوننت (properties) مدیریت می‌شوند و endpointهای
 * POST/PATCH کنترلر هم حفظ شده‌اند (سازگاری با JSهای موجود).
 */
trait InteractsWithOrderActions
{
    /* ---------- مودال تخصیص دستی ---------- */
    public bool $assignOpen = false;
    public ?int $assignOrderId = null;
    public string $assignOrderNumber = '';
    public string $assignSearch = '';
    public ?int $assignCoffeenetId = null;
    public ?int $assignOperatorId = null;
    public string $assignNote = '';

    /* ---------- مودال واگذاری به اپراتور (صفحهٔ جزئیات) ---------- */
    public bool $operatorOpen = false;
    public ?int $operatorId = null;
    public string $operatorNote = '';

    /* ---------- مودال لغو ---------- */
    public bool $cancelOpen = false;
    public ?int $cancelOrderId = null;
    public string $cancelOrderNumber = '';
    public string $cancelReason = '';

    /* ---------- مودال تغییر وضعیت (صفحهٔ جزئیات) ---------- */
    public bool $statusOpen = false;
    public string $newStatus = '';
    public string $statusReason = '';

    /* ---------- پیام نتیجهٔ اکشن‌ها (رندر داخل صفحه) ---------- */
    public ?string $flashMessage = null;
    public string $flashType = 'success';

    /* ================== پیام ================== */

    protected function toast(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;

        // توست هم‌شکل بقیهٔ پنل (App.toast) از طریق رویداد مرورگر
        $this->dispatch('lw-toast', message: $message, type: $type);
    }

    protected function humanError(\Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            return collect($e->errors())->flatten()->first() ?? 'داده‌های ارسالی معتبر نیست.';
        }

        report($e);

        return 'خطا در انجام عملیات؛ لطفاً دوباره تلاش کنید.';
    }

    protected function assignment(): OrderAssignmentService
    {
        return app(OrderAssignmentService::class);
    }

    /** کافی‌نت‌های فعال برای مودال تخصیص — عیناً coffeenets() کنترلر */
    protected function coffeenetOptions(): array
    {
        $query = \App\Models\Coffeenet::query()
            ->where('status', \App\Enums\CoffeenetStatus::Approved->value)
            ->with('city:id,name')
            ->select(['id', 'name', 'phone', 'city_id']);

        if ($q = trim($this->assignSearch)) {
            $query->where('name', 'like', "%{$q}%");
        }

        return $query->orderBy('name')->limit(20)->get()
            ->map(fn (\App\Models\Coffeenet $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'city' => $c->city?->name,
            ])->all();
    }

    /** اپراتورهای فعال یک کافی‌نت — عیناً operators() کنترلر */
    protected function operatorOptions(?int $coffeenetId): array
    {
        if (! $coffeenetId) {
            return [];
        }

        return \App\Models\StaffAssignment::query()
            ->where('coffeenet_id', $coffeenetId)
            ->where('is_active', true)
            ->with('user:id,name,family,mobile')
            ->get()
            ->map(fn (\App\Models\StaffAssignment $s) => [
                'id' => $s->user_id,
                'name' => $s->user?->full_name ?? '—',
                'mobile' => $s->user?->mobile,
                'position_label' => $s->position->label(),
            ])->all();
    }

    /* ================== تخصیص دستی ================== */

    public function openAssign(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);

        // مثل JS قبلی: فقط حالت‌های صف/پخش/پرداخت‌شده قابل تخصیص
        abort_unless(in_array($order->status->value, ['queued', 'broadcasting', 'paid'], true), 422, 'این سفارش در فاز تخصیص نیست.');

        $this->assignOrderId = $order->id;
        $this->assignOrderNumber = $order->order_number;
        $this->assignOpen = true;
        $this->assignSearch = '';
        $this->assignCoffeenetId = null;
        $this->assignOperatorId = null;
        $this->assignNote = '';
    }

    public function closeAssign(): void
    {
        $this->assignOpen = false;
    }

    public function updatedAssignSearch(): void
    {
        // جستجوی جدید → انتخاب قبلی کافی‌نت/اپراتور معتبر نیست
        $this->assignCoffeenetId = null;
        $this->assignOperatorId = null;
    }

    public function setAssignCoffeenet(int $coffeenetId): void
    {
        $this->assignCoffeenetId = $coffeenetId;
        $this->assignOperatorId = null;
    }

    /** عیناً assign() کنترلر — فقط خروجی JSON به پیام کامپوننت تبدیل شده */
    public function assignOrder(): void
    {
        abort_unless($this->assignOrderId, 422, 'سفارشی انتخاب نشده است.');

        $data = $this->validate([
            'assignCoffeenetId' => ['required', 'integer', 'exists:coffeenets,id'],
            'assignOperatorId' => ['nullable', 'integer', 'exists:users,id'],
            'assignNote' => ['nullable', 'string', 'max:400'],
        ], [
            'assignCoffeenetId.required' => 'انتخاب کافی‌نت الزامی است.',
            'assignCoffeenetId.exists' => 'کافی‌نت انتخابی یافت نشد.',
            'assignOperatorId.exists' => 'اپراتور انتخابی یافت نشد.',
            'assignNote.max' => 'یادداشت حداکثر ۴۰۰ حرف است.',
        ]);

        $order = Order::query()->findOrFail($this->assignOrderId);
        $coffeenet = Coffeenet::query()->findOrFail((int) $data['assignCoffeenetId']);
        $operator = ! empty($data['assignOperatorId']) ? User::findOrFail((int) $data['assignOperatorId']) : null;

        try {
            $order = $this->assignment()->manualAssign(
                $order,
                $coffeenet,
                auth()->user(),
                $data['assignNote'] ?? null,
                $operator
            );
        } catch (\Throwable $e) {
            $this->toast($this->humanError($e), 'error');

            return;
        }

        $operatorName = $operator ? trim(($operator->name ?? '').' '.($operator->family ?? '')) : null;

        $this->assignOpen = false;

        $this->toast('سفارش '.$order->order_number.' به کافی‌نت «'.$coffeenet->name.'» تخصیص یافت.'
            .($operatorName ? ' (اپراتور مسئول: «'.$operatorName.'»)' : ''));
    }

    /* ================== واگذاری به اپراتور ================== */

    public function openOperatorModal(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);

        abort_unless((bool) $order->coffeenet_id, 422, 'این سفارش هنوز به کافی‌نت تخصیص نیافته است.');

        $this->assignOrderId = $order->id;
        $this->assignOrderNumber = $order->order_number;
        $this->operatorId = null;
        $this->operatorNote = '';
        $this->operatorOpen = true;
    }

    public function closeOperatorModal(): void
    {
        $this->operatorOpen = false;
    }

    /** عیناً assignOperator() کنترلر */
    public function assignOperator(): void
    {
        abort_unless($this->assignOrderId, 422, 'سفارشی انتخاب نشده است.');

        $data = $this->validate([
            'operatorId' => ['required', 'integer', 'exists:users,id'],
            'operatorNote' => ['nullable', 'string', 'max:400'],
        ], [
            'operatorId.required' => 'انتخاب اپراتور الزامی است.',
            'operatorId.exists' => 'اپراتور انتخابی یافت نشد.',
        ]);

        $order = Order::query()->findOrFail($this->assignOrderId);
        $coffeenet = $order->coffeenet()->firstOrFail();
        $operator = User::query()->findOrFail((int) $data['operatorId']);

        try {
            $order = $this->assignment()->assignOperator(
                $order,
                $coffeenet,
                $operator,
                auth()->user(),
                $data['operatorNote'] ?? null
            );
        } catch (\Throwable $e) {
            $this->toast($this->humanError($e), 'error');

            return;
        }

        $operatorName = trim(($operator->name ?? '').' '.($operator->family ?? ''));

        $this->operatorOpen = false;

        $this->toast('سفارش '.$order->order_number.' به اپراتور «'.$operatorName.'» واگذار شد.');
    }

    /* ================== تغییر وضعیت ================== */

    public function openStatusModal(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);

        abort_unless((bool) $this->statusActions($order), 422, 'گذار مجازی از وضعیت فعلی این سفارش وجود ندارد.');

        $this->assignOrderId = $order->id;
        $this->assignOrderNumber = $order->order_number;
        $this->newStatus = '';
        $this->statusReason = '';
        $this->statusOpen = true;
    }

    public function closeStatusModal(): void
    {
        $this->statusOpen = false;
    }

    /** عیناً updateStatus() کنترلر — پیام تسویه کمیسیون هم حفظ شده */
    public function updateStatus(): void
    {
        abort_unless($this->assignOrderId, 422, 'سفارشی انتخاب نشده است.');

        $data = $this->validate([
            'newStatus' => ['required', 'string'],
            'statusReason' => ['nullable', 'string', 'max:490'],
        ], [
            'newStatus.required' => 'وضعیت جدید الزامی است.',
        ]);

        $to = \App\Enums\OrderStatus::tryFrom((string) $data['newStatus']);
        abort_unless((bool) $to, 422, 'وضعیت سفارش نامعتبر است.');

        $order = Order::query()->findOrFail($this->assignOrderId);

        try {
            $result = $this->assignment()->changeStatusByStaff(
                $order,
                auth()->user(),
                $to,
                (string) ($data['statusReason'] ?? '')
            );
        } catch (\Throwable $e) {
            $this->toast($this->humanError($e), 'error');

            return;
        }

        $updated = $result['order'];

        $this->statusOpen = false;

        $message = 'وضعیت سفارش به «'.$updated->status->label().'» تغییر کرد.';
        if ($result['settled']) {
            $message .= ' سهم‌های کمیسیون تسویه شد.';
        } elseif (($result['settled_reason'] ?? '') === 'no_rule') {
            $message .= ' (قاعدهٔ کمیسیون فعال یافت نشد — تسویه انجام نشد)';
        }

        $this->toast($message);
    }

    /* ================== لغو ================== */

    public function openCancel(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);

        // مثل JS قبلی: لغو فقط در paid/broadcasting/queued/accepted
        abort_unless(in_array($order->status->value, ['paid', 'broadcasting', 'queued', 'accepted'], true), 422, 'لغو این سفارش در وضعیت فعلی ممکن نیست.');

        $this->cancelOrderId = $order->id;
        $this->cancelOrderNumber = $order->order_number;
        $this->cancelReason = '';
        $this->cancelOpen = true;
    }

    public function closeCancel(): void
    {
        $this->cancelOpen = false;
    }

    /** عیناً cancel() کنترلر */
    public function cancelOrder(): void
    {
        abort_unless($this->cancelOrderId, 422, 'سفارشی انتخاب نشده است.');

        $data = $this->validate([
            'cancelReason' => ['required', 'string', 'min:3', 'max:490'],
        ], [
            'cancelReason.required' => 'ذکر دلیل لغو الزامی است.',
            'cancelReason.min' => 'دلیل لغو باید حداقل ۳ حرف باشد.',
            'cancelReason.max' => 'دلیل لغو حداکثر ۴۹۰ حرف است.',
        ]);

        $order = Order::query()->findOrFail($this->cancelOrderId);

        try {
            $order = $this->assignment()->cancelByAdmin($order, auth()->user(), (string) $data['cancelReason']);
        } catch (\Throwable $e) {
            $this->toast($this->humanError($e), 'error');

            return;
        }

        $this->cancelOpen = false;

        $this->toast('سفارش '.$order->order_number.' لغو شد.');
    }

    /* ================== ری‌پخش ================== */

    /** عیناً rebroadcast() کنترلر */
    public function rebroadcastOrder(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);

        try {
            $order = $this->assignment()->rebroadcast($order, auth()->user());
        } catch (\Throwable $e) {
            $this->toast($this->humanError($e), 'error');

            return;
        }

        $this->toast('سفارش '.$order->order_number.' مجدداً بین کافی‌نت‌ها پخش شد (کوشش '.fa_digits((string) $order->broadcast_attempts).').');
    }

    /* ================== نقشهٔ گذارهای وضعیت (مثل OrderChatController) ================== */

    /**
     * نقشهٔ دکمه‌های تغییر وضعیت — عیناً staffStatusActions() کنترلر گفتگو.
     *
     * @return array<int, array{0:string,1:string,2:bool}> [status, label, نیاز به دلیل]
     */
    protected function statusActions(Order $order): array
    {
        $actions = [];
        $isPaid = (bool) $order->paid_at;

        foreach ($order->status->allowedTransitions() as $to) {
            $label = match ($to) {
                \App\Enums\OrderStatus::InProgress => $isPaid ? 'شروع/ادامه کار' : null,
                \App\Enums\OrderStatus::NeedsInfo => 'نیازمند اطلاعات',
                \App\Enums\OrderStatus::Delivered => $isPaid ? 'تحویل شد' : null,
                \App\Enums\OrderStatus::Completed => 'تکمیل نهایی',
                \App\Enums\OrderStatus::Cancelled => 'عدم امکان انجام',
                \App\Enums\OrderStatus::Paid => null, // پرداخت فقط از سمت مشتری
                \App\Enums\OrderStatus::Refunded => 'بازگشت وجه',
                default => null,
            };

            if ($label !== null) {
                $needsReason = $to === \App\Enums\OrderStatus::Cancelled || $to === \App\Enums\OrderStatus::Refunded;
                $actions[] = [$to->value, $label, $needsReason];
            }
        }

        return $actions;
    }
}
