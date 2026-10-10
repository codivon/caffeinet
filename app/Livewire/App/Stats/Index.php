<?php

namespace App\Livewire\App\Stats;

use App\Services\Settings\SettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * فاز ۵۴ — «آمار من» اپ مشتری — شِل صفحه (داده با JS از /me/stats + Chart.js).
 * لینک از پروفایل. سوییچ: features.customer_stats (خاموش = بازگشت به خانه).
 */
#[Layout('app.layout')]
class Index extends Component
{
    public function mount(SettingsService $settings): void
    {
        if (! (bool) $settings->get('features.customer_stats', true)) {
            $this->redirectRoute('app.home', navigate: true);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.stats.index')->layoutData([
            'htmlTitle' => 'آمار من',
            'activeNav' => 'profile',
        ]);
    }
}
