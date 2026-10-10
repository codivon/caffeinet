<?php

namespace App\Livewire\Operator\Tickets;

use App\Livewire\Operator\Concerns\InteractsWithOperatorContext;
use App\Models\Ticket;
use App\Services\Support\TicketService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «گفتگوی تیکت» پنل اپراتور — Shell Livewire + موتور JS قبلی [Task 5]
 *
 * GET operator.tickets.show → TicketsController@show منطق عیناً منتقل شده (mount):
 *   • مجوز tickets.view (نبود → 403)
 *   • authorizeScope: تیکتِ سفارشِ کافی‌نت جاری نیست → 404
 *   • پیام‌ها بدون یادداشت داخلی + payload صفحه عیناً با
 *     TicketService::serializeTicket / serializeMessage (canSeeInternal: false)
 *   • can_manage = false (اپراتور یادداشت داخلی نمی‌بیند و مدیریت نمی‌کند)
 *
 * گفتگو (رشتهٔ پیام + پولینگ افزایشی + ارسال پاسخ با پیوست) با همان
 * admin/tickets/show.js فعلی کار می‌کند — endpointهای
 * operator.tickets.reply / operator.tickets.messages دست‌نخورده‌اند.
 */
#[Layout('back.operator.layouts.panel')]
class Show extends Component
{
    use InteractsWithOperatorContext;

    public Ticket $ticket;

    public function mount(Ticket $ticket): void
    {
        if ($this->operatorContextFailed) {
            return;
        }

        // عیناً show کنترلر
        abort_unless(auth()->user()->can('tickets.view'), 403);

        $coffeenet = $this->operatorCoffeenet;

        // عیناً authorizeScope کنترلر
        if (! $ticket->order || (int) $ticket->order->coffeenet_id !== (int) $coffeenet->id) {
            abort(404, 'تیکت یافت نشد.');
        }

        $this->ticket = $ticket;
    }

    public function render(): \Illuminate\View\View
    {
        if ($this->operatorContextFailed) {
            return view('livewire.operator.partials.context-redirect')
                ->layoutData($this->operatorLayoutData('تیکت', 'پنل اپراتور ← تیکت‌های پشتیبانی', 'تیکت'));
        }

        $coffeenet = $this->operatorCoffeenet;
        $ticket = $this->ticket;

        $ticket->load(['order:id,order_number', 'user:id,name,family', 'assignedTo:id,name,family']);

        // اپراتور یادداشت داخلی نمی‌بیند (فقط tickets.manage)
        $messages = $ticket->messages()->where('is_internal', false)->with('sender:id,name,family')->get();

        $payload = [
            'ticket' => app(TicketService::class)->serializeTicket($ticket, canSeeInternal: false),
            'messages' => $messages->map(
                fn ($m) => app(TicketService::class)->serializeMessage($m, canSeeInternal: false, viewerId: (int) auth()->id())
            )->values(),
            'last_id' => (int) ($messages->last()?->id ?? 0),
            'can_manage' => false,
        ];

        return view('livewire.operator.tickets.show', [
            'coffeenet' => $coffeenet,
            'ticket' => $ticket,
            'payload' => $payload,
        ])->layoutData($this->operatorLayoutData(
            'تیکت '.$ticket->ticket_number,
            'پنل اپراتور ← تیکت‌های پشتیبانی ← '.$ticket->ticket_number,
            'تیکت '.$ticket->ticket_number,
        ));
    }
}
