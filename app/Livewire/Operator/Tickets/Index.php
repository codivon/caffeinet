<?php

namespace App\Livewire\Operator\Tickets;

use App\Enums\TicketStatus;
use App\Livewire\Operator\Concerns\InteractsWithOperatorContext;
use App\Models\Ticket;
use App\Services\Support\TicketService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «تیکت‌های پشتیبانی» پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
 *
 * منطق عیناً از App\Http\Controllers\Back\Operator\TicketsController
 * (index/data/counts) منتقل شده است:
 *   • مجوز tickets.view (نبود → 403 — عیناً کنترلر)
 *   • دید: تیکت‌های سفارش‌های کافی‌نت جاری (whereHas order)
 *   • فیلتر وضعیت + جستجوی شماره تیکت / موضوع / نام مشتری / شماره سفارش
 *   • مرتب‌سازی نزولی updated_at,id و صفحه‌بندی ۲۵تایی
 *   • چیپ‌های آماری (counts) محدود به کافی‌نت جاری — عیناً counts() کنترلر
 *   • سریال‌سازی ردیف‌ها عیناً با TicketService::serializeTicket(canSeeInternal: false)
 *
 * endpointهای POST reply و GET messages کنترلر دست‌نخورده ماندند
 * (صفحهٔ گفتگو با show.js فعلی کار می‌کند).
 */
#[Layout('back.operator.layouts.panel')]
class Index extends Component
{
    use InteractsWithOperatorContext;
    use WithPagination;

    /** فیلتر وضعیت: '' | open | answered | customer_reply | closed */
    #[Url(as: 'status')]
    public string $status = '';

    /** جستجو: شماره تیکت / موضوع / نام مشتری / شماره سفارش */
    #[Url(as: 'q')]
    public string $q = '';

    public function updating(string $name): void
    {
        if (in_array($name, ['status', 'q'], true)) {
            $this->resetPage();
        }
    }

    /** کلیک چیپ آماری = فیلتر سریع (مثل data-stat-chip نسخهٔ JS) */
    public function applyChip(string $key): void
    {
        if ($key === 'high-open') {
            // اولویت زیاد + بدون بسته — مثل نسخهٔ JS (وضعیت خالی می‌شود)
            $this->status = '';
        } else {
            $this->status = $key;
        }

        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        if ($this->operatorContextFailed) {
            return view('livewire.operator.partials.context-redirect')
                ->layoutData($this->operatorLayoutData('تیکت‌های پشتیبانی', 'پنل اپراتور ← تیکت‌های پشتیبانی', 'تیکت‌های پشتیبانی'));
        }

        $coffeenet = $this->operatorCoffeenet;

        // عیناً index/data کنترلر
        abort_unless(auth()->user()->can('tickets.view'), 403);

        $query = $this->scopedQuery($coffeenet);

        if ($status = (string) $this->status) {
            $query->where('tickets.status', $status);
        }

        if ($search = trim($this->q)) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('tickets.ticket_number', 'like', "%{$search}%")
                    ->orWhere('tickets.subject', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"));
            });
        }

        $rows = $query->orderByDesc('tickets.updated_at')->orderByDesc('tickets.id')->paginate(25);

        $rows->through(
            fn (Ticket $t) => app(TicketService::class)->serializeTicket($t, canSeeInternal: false)
        );

        return view('livewire.operator.tickets.index', [
            'rows' => $rows,
            'coffeenet' => $coffeenet,
            'stats' => $this->counts($coffeenet),
            'chips' => $this->chips($coffeenet),
        ])->layoutData($this->operatorLayoutData('تیکت‌های پشتیبانی', 'پنل اپراتور ← تیکت‌های پشتیبانی', 'تیکت‌های پشتیبانی'));
    }

    /* ================== منطق داخلی (عیناً از کنترلر) ================== */

    /** کوئری محدودهٔ کافی‌نت — عیناً scopedQuery() کنترلر */
    protected function scopedQuery($coffeenet): Builder
    {
        return Ticket::query()
            ->with(['order:id,order_number,coffeenet_id', 'user:id,name,family', 'assignedTo:id,name,family'])
            ->whereHas('order', fn ($o) => $o->where('coffeenet_id', $coffeenet->id));
    }

    /** چیپ‌های آماری — عیناً counts() کنترلر (محدود به کافی‌نت جاری) */
    protected function counts($coffeenet): array
    {
        $base = $this->scopedQuery($coffeenet);

        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->where('status', TicketStatus::Open->value)->count(),
            'answered' => (clone $base)->where('status', TicketStatus::Answered->value)->count(),
            'customer_reply' => (clone $base)->where('status', TicketStatus::CustomerReply->value)->count(),
            'closed' => (clone $base)->where('status', TicketStatus::Closed->value)->count(),
            'high' => (clone $base)->where('priority', 'high')->where('status', '!=', 'closed')->count(),
        ];
    }

    /** برچسب چیپ‌ها — عیناً ویوی قبلی (back.operator.tickets.index) */
    protected function chips($coffeenet): array
    {
        $stats = $this->counts($coffeenet);

        return [
            ['label' => 'همه تیکت‌ها', 'value' => $stats['total'], 'tone' => 'total', 'key' => ''],
            ['label' => 'باز', 'value' => $stats['open'], 'tone' => 'open', 'key' => 'open'],
            ['label' => 'پاسخ داده‌شده', 'value' => $stats['answered'], 'tone' => 'answered', 'key' => 'answered'],
            ['label' => 'پاسخ مشتری', 'value' => $stats['customer_reply'], 'tone' => 'customer', 'key' => 'customer_reply'],
            ['label' => 'بسته', 'value' => $stats['closed'], 'tone' => 'closed', 'key' => 'closed'],
            ['label' => 'اولویت زیاد (باز)', 'value' => $stats['high'], 'tone' => 'high', 'key' => 'high-open'],
        ];
    }
}
