<?php

namespace App\Livewire\App\Wallet;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «کیف پول» اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * پوستهٔ موجودی/شارژ/تراکنش‌ها؛ داده‌ها سمت کلاینت با wallet.js
 * (CN.api → API v1) دریافت می‌شود و دست‌نخورده است.
 */
#[Layout('app.layout')]
class Index extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.wallet.index')->layoutData([
            'htmlTitle' => 'کیف پول',
            'activeNav' => 'wallet',
        ]);
    }
}
