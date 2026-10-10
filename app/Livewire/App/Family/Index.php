<?php

namespace App\Livewire\App\Family;

use App\Services\Settings\SettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * فاز ۵۳ — «حساب خانواده» در اپ مشتری — شِل صفحه (داده با JS از API).
 *
 * حساب اصلی: فهرست زیرحساب‌ها + ساخت عضو جدید + سقف خرج/بلاک/حذف.
 * زیرحساب: فقط اطلاعات حساب والد (بدون مدیریت) — از همان API.
 * لینک از صفحهٔ پروفایل. سوییچ: features.family_accounts (خاموش = بازگشت).
 */
#[Layout('app.layout')]
class Index extends Component
{
    public function mount(SettingsService $settings): void
    {
        if (! (bool) $settings->get('features.family_accounts', false)) {
            $this->redirectRoute('app.home', navigate: true);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.family.index')->layoutData([
            'htmlTitle' => 'حساب خانواده',
            'activeNav' => 'profile',
        ]);
    }
}
