<?php

namespace App\Livewire\Coffeenet\Tickets;

use App\Models\Coffeenet;
use App\Models\Ticket;
use App\Services\Support\TicketService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «گفتگوی تیکت» پنل کافی‌نت — Shell Livewire + موتور JS قبلی [Task 4]
 *
 * GET coffeenet.tickets.show → TicketsController@show منطق عیناً منتقل شده:
 *   • authorizeScope: تیکتِ سفارشِ همین کافی‌نت نیست → 404
 *   • پیام‌ها + payload صفحه عیناً با TicketService::serializeTicket /
 *     serializeMessage (canSeeInternal = مجوز tickets.manage مثل canInternal کنترلر)
 *   • can_manage = tickets.manage (مدیر کافی‌نت مجاز به پاسخ + بستن است)
 *
 * گفتگو (رشتهٔ پیام + پولینگ افزایشی + ارسال پاسخ با پیوست + بستن/بازگشایی)
 * با همان admin/tickets/show.js فعلی کار می‌کند — endpointهای
 * coffeenet.tickets.reply / coffeenet.tickets.messages / coffeenet.tickets.status
 * دست‌نخورده‌اند.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Show extends Component
{
    public Coffeenet $coffeenet;

    public Ticket $ticket;

    public function mount(Coffeenet $coffeenet, Ticket $ticket): void
    {
        // فاز ۵۹ (F2 ممیزی) — گارد تنانت: کافی‌نتِ مسیر باید همان کافی‌نتِ جلسه باشد
        abort_unless(request()->attributes->get('current_coffeenet')?->id === $coffeenet->id, 403);

        // عیناً authorizeScope کنترلر: تیکت باید سفارشِ همین کافی‌نت را داشته باشد
        if (! $ticket->order || (int) $ticket->order->coffeenet_id !== (int) $coffeenet->id) {
            abort(404, 'تیکت یافت نشد.');
        }

        $this->coffeenet = $coffeenet;
        $this->ticket = $ticket;
    }

    public function render(): \Illuminate\View\View
    {
        $coffeenet = $this->coffeenet;
        $ticket = $this->ticket;

        $ticket->load(['order:id,order_number', 'user:id,name,family,mobile', 'assignedTo:id,name,family']);

        $messages = $ticket->messages()->with('sender:id,name,family')->get();

        $canInternal = auth()->user()->can('tickets.manage'); // عیناً canInternal کنترلر
        $service = app(TicketService::class);

        $payload = [
            'ticket' => $service->serializeTicket($ticket, canSeeInternal: $canInternal),
            'messages' => $messages->map(
                fn ($m) => $service->serializeMessage($m, canSeeInternal: $canInternal, viewerId: (int) auth()->id())
            )->values(),
            'last_id' => (int) ($messages->last()?->id ?? 0),
            'can_manage' => $canInternal,
        ];

        return view('livewire.coffeenet.tickets.show', [
            'coffeenet' => $coffeenet,
            'ticket' => $ticket,
            'payload' => $payload,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => $ticket->subject,
            'breadcrumb' => 'پنل کافی‌نت ← تیکت‌های پشتیبانی ← '.$ticket->ticket_number,
            'htmlTitle' => 'تیکت '.$ticket->ticket_number,
        ]);
    }
}
