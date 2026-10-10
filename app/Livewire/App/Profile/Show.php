<?php

namespace App\Livewire\App\Profile;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «پروفایل» اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * نمای پروفایل کاربر؛ داده‌ها سمت کلاینت با profile.js
 * (CN.api → API v1) دریافت می‌شود و دست‌نخورده است.
 */
#[Layout('app.layout')]
class Show extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.profile.show')->layoutData([
            'htmlTitle' => 'پروفایل',
            'activeNav' => 'profile',
        ]);
    }
}
