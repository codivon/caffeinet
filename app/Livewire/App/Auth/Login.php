<?php

namespace App\Livewire\App\Auth;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «ورود / ثبت‌نام» اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * منطق OTP سمت کلاینت با auth.js (CN.api → API v1) اجرا می‌شود و دست‌نخورده است.
 * کامپوننت فقط تنظیمات لایه را پاس می‌دهد: بدون هدر/ناوبری (chrome=false) + shell بدون ناوبری.
 */
#[Layout('app.layout')]
class Login extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.auth.login')->layoutData([
            'htmlTitle'  => 'ورود / ثبت‌نام',
            'chrome'     => false,
            'shellClass' => 'no-nav',
        ]);
    }
}
