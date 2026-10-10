<?php

namespace App\Livewire\Coffeenet\Tickets;

use App\Enums\TicketStatus;
use App\Models\Coffeenet;
use App\Models\Ticket;
use App\Services\Support\TicketService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «تیکت‌های پشتیبانی» پنل کافی‌نت — نسخهٔ Livewire 4 (Task 4).
 *
 * منطق عیناً از App\Http\Controllers\Back\Coffeenet\TicketsController (index/data/counts)
 * منتقل شده:
 *   • محدوده: فقط تیکت‌های سفارش‌های همین کافی‌نت (scopedQuery)
 *   • فیلتر وضعیت + جستجوی شماره تیکت / موضوع / نام مشتری / شماره سفارش
 *   • مرتب‌سازی نزولی updated_at,id و صفحه‌بندی ۲۵تایی
 *   • چیپ‌های آماری بالای لیست (counts) با فیلتر سریع «اولویت زیاد (باز)»
 *   • سریال‌سازی ردیف‌ها عیناً با TicketService::serializeTicket
 *     (یادداشت داخلی فقط با مجوز tickets.manage مثل canInternal کنترلر)
 *
 * endpointهای reply/messages/status کنترلر دست‌نخورده ماندند تا JSهای دیگر نشکنند.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    public Coffeenet $coffeenet;

    /** فیلتر وضعیت: '' | open | answered | customer_reply | closed */
    #[Url(as: 'status')]
    public string $status = '';

    /** جستجو: شماره تیکت / موضوع / نام مشتری / شماره سفارش */
    #[Url(as: 'q')]
    public string $q = '';

    public function mount(Coffeenet $coffeenet): void
    {
        // فاز ۵۹ (F2 ممیزی) — گارد تنانت: کافی‌نتِ مسیر باید همان کافی‌نتِ جلسه باشد
        abort_unless(request()->attributes->get('current_coffeenet')?->id === $coffeenet->id, 403);

        $this->coffeenet = $coffeenet;
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['status', 'q'], true)) {
            $this->resetPage();
        }
    }

    /** کلیک چیپ آماری = فیلتر سریع (مثل data-stat-chip نسخهٔ JS) */
    public function applyChip(string $key): void
    {
        // در نسخهٔ JS کافی‌نت هیچ فیلتر اولویتی نبود؛ «اولویت زیاد» فقط فیلتر را پاک می‌کرد
        $this->status = $key === 'high-open' ? '' : $key;

        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $coffeenet = $this->coffeenet;
        $canInternal = auth()->user()->can('tickets.manage'); // عیناً canInternal کنترلر

        $query = $this->scopedQuery($coffeenet);

        // فیلتر وضعیت
        if ($status = (string) $this->status) {
            $query->where('tickets.status', $status);
        }

        // جستجو: شماره تیکت / موضوع / نام مشتری / شماره سفارش
        if ($search = trim($this->q)) {
            $query->where(function ($q) use ($search) {
                $q->where('tickets.ticket_number', 'like', "%{$search}%")
                    ->orWhere('tickets.subject', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('family', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"));
            });
        }

        $tickets = $query->orderByDesc('tickets.updated_at')->orderByDesc('tickets.id')->paginate(25);

        $tickets->through(
            fn (Ticket $t) => app(TicketService::class)->serializeTicket($t, canSeeInternal: $canInternal)
        );

        return view('livewire.coffeenet.tickets.index', [
            'rows' => $tickets,
            'stats' => $this->counts($coffeenet),
            'chips' => $this->chips($coffeenet),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'تیکت‌های پشتیبانی',
            'breadcrumb' => 'پنل کافی‌نت ← تیکت‌های پشتیبانی',
            'htmlTitle' => 'تیکت‌های پشتیبانی',
        ]);
    }

    /* ================== منطق داخلی (عیناً از کنترلر) ================== */

    /** تیکت‌های سفارش‌های همین کافی‌نت — عیناً scopedQuery() کنترلر */
    protected function scopedQuery(Coffeenet $coffeenet): Builder
    {
        return Ticket::query()
            ->with(['order:id,order_number,coffeenet_id', 'user:id,name,family,mobile', 'assignedTo:id,name,family'])
            ->whereHas('order', fn ($o) => $o->where('coffeenet_id', $coffeenet->id));
    }

    /** چیپ‌های آماری بالای لیست — عیناً counts() کنترلر */
    protected function counts(Coffeenet $coffeenet): array
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

    /** برچسب چیپ‌ها — عیناً ویوی قبلی (back/coffeenet/tickets/index) */
    protected function chips(Coffeenet $coffeenet): array
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
