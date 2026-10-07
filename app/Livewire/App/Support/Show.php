<?php

namespace App\Livewire\App\Support;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «گفتگوی تیکت» اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * صفحهٔ گفتگوی تیکت با support-detail.js (CN.api → API v1) اجرا می‌شود و
 * دست‌نخورده است — شناسهٔ تیکت را mount از پارامتر روت می‌گیرد و در
 * data-ticket-id به JS پاس می‌دهد (عین PagesController::supportShow).
 */
#[Layout('app.layout')]
class Show extends Component
{
    /** شناسهٔ تیکت (از پارامتر روت support/{ticket}) */
    public int $ticketId;

    public function mount(int $ticket): void
    {
        $this->ticketId = $ticket;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.support.show', [
            'ticketId' => $this->ticketId,
        ])->layoutData([
            'htmlTitle' => 'تیکت پشتیبانی',
        ]);
    }
}
