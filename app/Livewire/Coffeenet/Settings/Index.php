<?php

namespace App\Livewire\Coffeenet\Settings;

use App\Models\Coffeenet;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «تنظیمات» پنل کافی‌نت — نسخهٔ Livewire 4 (Task 4).
 *
 * منطق عیناً از App\Http\Controllers\Back\Coffeenet\SettingsController@index
 * منتقل شده است (کنترلر حذف نمی‌شود؛ فقط رندر صفحه به Livewire منتقل شد):
 *   • گارد ۴۰۳: کافی‌نتِ مسیر = کافی‌نتِ session (فقط در mount/بار اولیه)
 *   • پروندهٔ کافی‌نت با استان/شهرستان/سازمان + فهرست استان‌ها
 *
 * ذخیرهٔ اطلاعات (PUT coffeenet.settings.update)، تغییر رمز (PUT
 * coffeenet.settings.password) و سلکت آبشاری شهرها (coffeenet.geo.cities)
 * طبق قرارداد Task 4 همان endpointهای کنترلر می‌مانند؛ فرم‌ها با همان
 * اعتبارسنجی سمت کلاینت (Alpine) از طریق App.ajax کار می‌کنند —
 * رفتار و پیام‌ها عیناً مثل pages/coffeenet/settings/index.js قبلی.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Index extends Component
{
    public Coffeenet $coffeenet;

    public function mount(Coffeenet $coffeenet): void
    {
        // عیناً گارد SettingsController@index — کافی‌نت مسیر = کافی‌نت جلسه
        abort_unless(request()->attributes->get('current_coffeenet')?->id === $coffeenet->id, 403);

        $this->coffeenet = $coffeenet;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.coffeenet.settings.index', [
            'coffeenet' => $this->coffeenet->load('province:id,name', 'city:id,name', 'organization:id,name'),
            'provinces' => \App\Models\Province::orderBy('name')->get(['id', 'name']),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'تنظیمات',
            'breadcrumb' => 'پنل کافی‌نت ← تنظیمات',
            'htmlTitle' => 'تنظیمات',
        ]);
    }
}
