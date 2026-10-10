<?php

namespace App\Livewire\Admin\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Support\TicketService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «گفتگوی تیکت» پنل مدیریت کل — نسخهٔ Livewire 4 (Task 3-e).
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\TicketsController@show منتقل شده:
 *   • payload صفحه (ticket/messages/last_id/can_manage/staff) عیناً با
 *     TicketService::serializeTicket / serializeMessage(canSeeInternal: true)
 *   • کارشناسان ارجاع: نقش super_admin فعال مرتب‌شده بر اساس نام
 *
 * گفتگو (رشتهٔ پیام + پولینگ افزایشی ۱۰ ثانیه‌ای + ارسال پاسخ با پیوست از
 * POST admin.tickets.reply) با همان show.js فعلی کار می‌کند — polling فعلی
 * دست‌نخورده مانده است. فقط این سه عمل ساده جدول به متد Livewire منتقل شد
 * (منطق/audit عیناً از TicketService — endpointهای PATCH کنترلر هم سر جایشان):
 *   • toggleStatus()  → close/reopen
 *   • savePriority()  → اولویت
 *   • assignStaff()   → ارجاع به کارشناس
 * دکمه‌های این سه، idهای متفاوتی از نسخهٔ JS دارند تا show.js دوباره bind نکند
 * (optional chaining اسکریپت، نبودِ idها را بی‌صدا رد می‌کند).
 */
#[Layout('back.layouts.panel')]
class Show extends Component
{
    public Ticket $ticket;

    /** اولویت انتخاب‌شده در کارت اطلاعات */
    public string $priority = 'normal';

    /** کارشناس انتخاب‌شده برای ارجاع */
    public string $assignTo = '';

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
        $this->priority = $ticket->priority;
        $this->assignTo = (string) ($ticket->assigned_to ?? '');
    }

    /** بستن/بازگشایی تیکت — منطق عیناً از کنترلر status() */
    public function toggleStatus(): void
    {
        $closed = $this->ticket->fresh()->status === TicketStatus::Closed;

        $service = app(TicketService::class);

        if ($closed) {
            $service->reopen($this->ticket, auth()->user());
            $text = 'تیکت «'.$this->ticket->ticket_number.'» بازگشایی شد.';
        } else {
            $service->close($this->ticket, auth()->user());
            $text = 'تیکت «'.$this->ticket->ticket_number.'» بسته شد.';
        }

        $this->ticket->refresh();

        $this->dispatch('tk-toast', text: $text, type: 'success');
    }

    /** ذخیرهٔ اولویت — منطق عیناً از کنترلر priority() */
    public function savePriority(): void
    {
        try {
            app(TicketService::class)->setPriority($this->ticket, $this->priority);
        } catch (ValidationException $e) {
            $this->dispatch('tk-toast', text: collect($e->errors())->flatten()->first() ?? 'خطا', type: 'error');

            return;
        }

        $this->ticket->refresh();

        $this->dispatch('tk-toast', text: 'اولویت تیکت به «'.TicketService::priorityLabel($this->ticket->priority).'» تغییر کرد.', type: 'success');
    }

    /** ارجاع به کارشناس — منطق عیناً از کنترلر assign() */
    public function assignStaff(): void
    {
        if ((int) $this->assignTo === 0) {
            $this->dispatch('tk-toast', text: 'یک کارشناس انتخاب کنید.', type: 'warn');

            return;
        }

        try {
            app(TicketService::class)->assign($this->ticket, (int) $this->assignTo, auth()->user());
        } catch (ValidationException $e) {
            $this->dispatch('tk-toast', text: collect($e->errors())->flatten()->first() ?? 'ارجاع ناموفق بود.', type: 'error');

            return;
        }

        $this->ticket->refresh();

        $this->dispatch('tk-toast', text: 'تیکت ارجاع شد.', type: 'success');
    }

    public function render(): \Illuminate\View\View
    {
        $ticket = $this->ticket->refresh();

        $ticket->load(['order:id,order_number,coffeenet_id,service_id', 'user:id,name,family,mobile', 'assignedTo:id,name,family']);

        $messages = $ticket->messages()->with('sender:id,name,family')->get();

        $viewerId = (int) auth()->id();
        $service = app(TicketService::class);

        $payload = [
            'ticket' => $service->serializeTicket($ticket, canSeeInternal: true),
            'messages' => $messages->map(
                fn ($m) => $service->serializeMessage($m, canSeeInternal: true, viewerId: $viewerId)
            )->values(),
            'last_id' => (int) ($messages->last()?->id ?? 0),
            'can_manage' => true,
            'staff' => User::query()->role('super_admin')->where('is_active', true)
                ->orderBy('name')->get(['id', 'name', 'family']),
        ];

        return view('livewire.admin.tickets.show', [
            'ticket' => $ticket,
            'payload' => $payload,
            'messages' => $messages,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => $ticket->subject,
            'breadcrumb' => 'پنل مدیریت کل ← تیکت‌های پشتیبانی ← '.$ticket->ticket_number,
            'htmlTitle' => 'تیکت '.$ticket->ticket_number,
        ]);
    }
}
