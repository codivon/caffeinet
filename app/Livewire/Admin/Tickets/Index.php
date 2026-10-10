<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Ticket;
use App\Services\Support\TicketService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «تیکت‌های پشتیبانی» پنل مدیریت کل — نسخهٔ Livewire 4 (Task 3-e).
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\TicketsController (index/data/counts)
 * منتقل شده:
 *   • فیلتر وضعیت / اولویت + جستجوی شماره تیکت / موضوع / نام مشتری / شماره سفارش
 *   • مرتب‌سازی نزولی updated_at,id و صفحه‌بندی ۲۵تایی
 *   • چیپ‌های آماری بالای لیست (counts) با فیلتر سریع «اولویت زیاد (باز)»
 *   • سریال‌سازی ردیف‌ها عیناً با TicketService::serializeTicket(canSeeInternal: true)
 *
 * حذف نرم تیکت‌ها همان مسیر قبلی است: دکمهٔ data-trash در ردیف‌ها +
 * ماژول مشترک trash.js (delegation سطح document — DELETE کنترلر دست‌نخورده).
 *
 * endpointهای POST/PATCH (reply/status/priority/assign) و GET data/messages
 * کنترلر دست‌نخورده ماندند تا JSهای دیگر نشکنند.
 */
#[Layout('back.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    /** فیلتر وضعیت: '' | open | answered | customer_reply | closed */
    #[Url(as: 'status')]
    public string $status = '';

    /** فیلتر اولویت: '' | low | normal | high */
    #[Url(as: 'priority')]
    public string $priority = '';

    /** جستجو: شماره تیکت / موضوع / نام مشتری / شماره سفارش */
    #[Url(as: 'q')]
    public string $q = '';

    public function updating(string $name): void
    {
        if (in_array($name, ['status', 'priority', 'q'], true)) {
            $this->resetPage();
        }
    }

    /** کلیک چیپ آماری = فیلتر سریع (مثل data-stat-chip نسخهٔ JS) */
    public function applyChip(string $key): void
    {
        if ($key === 'high-open') {
            // اولویت زیاد + بدون بسته — مثل نسخهٔ JS
            $this->priority = 'high';
            $this->status = '';
        } else {
            $this->status = $key;
        }

        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $query = Ticket::query()
            ->with(['order:id,order_number,coffeenet_id', 'user:id,name,family,mobile', 'assignedTo:id,name,family']);

        // فیلتر وضعیت
        if ($status = (string) $this->status) {
            $query->where('status', $status);
        }

        // فیلتر اولویت
        if ($priority = (string) $this->priority) {
            $query->where('priority', $priority);
        }

        // جستجو: شماره تیکت / موضوع / نام مشتری / شماره سفارش
        if ($search = trim($this->q)) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('family', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"));
            });
        }

        $tickets = $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(25);

        $tickets->through(fn (Ticket $t) => app(TicketService::class)->serializeTicket($t, canSeeInternal: true));

        return view('livewire.admin.tickets.index', [
            'rows' => $tickets,
            'stats' => $this->counts(),
            'chips' => $this->chips(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'تیکت‌های پشتیبانی',
            'breadcrumb' => 'پنل مدیریت کل ← تیکت‌های پشتیبانی',
            'htmlTitle' => 'تیکت‌های پشتیبانی',
        ]);
    }

    /* ================== منطق داخلی (عیناً از کنترلر) ================== */

    /** چیپ‌های آماری بالای لیست — عیناً counts() کنترلر */
    protected function counts(): array
    {
        return [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'open')->count(),
            'answered' => Ticket::where('status', 'answered')->count(),
            'customer_reply' => Ticket::where('status', 'customer_reply')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
            'high' => Ticket::where('priority', 'high')->where('status', '!=', 'closed')->count(),
        ];
    }

    /** برچسب چیپ‌ها — عیناً ویوی قبلی (back/admin/tickets/index) */
    protected function chips(): array
    {
        $stats = $this->counts();

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
