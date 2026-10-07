<?php

namespace App\Livewire\Operator\Requests;

use App\Livewire\Operator\Concerns\InteractsWithOperatorContext;
use App\Support\OperatorPermissions;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «درخواست‌های مشتری» پنل اپراتور — نسخهٔ Livewire 4 [Task 5]
 *
 * منطق عیناً از App\Http\Controllers\Back\Operator\RequestsController@index
 * منتقل شده است:
 *   • authorizeAccept: بدون مجوز orders.accept → 403 (عیناً کنترلر)
 *   • timeout پخش از تنظیمات با کف ۱۵ ثانیه (مثل index کنترلر)
 *
 * لیست زنده (polling ~۴ ثانیه + شمارش معکوس + پذیرش) با همان JS قبلی صفحه
 * (requests/index.js) و همان endpointهای کنترلر کار می‌کند:
 *   • GET operator.requests.data / operator.requests.badge (بج سایدبار)
 *   • POST operator.requests.accept (پذیرش اتمیک)
 * هیچ‌کدام از این سه endpoint دست نخورده‌اند — شمارندهٔ بج هم با JS فعلی است.
 */
#[Layout('back.operator.layouts.panel')]
class Index extends Component
{
    use InteractsWithOperatorContext;

    public function render(): \Illuminate\View\View
    {
        if ($this->operatorContextFailed) {
            return view('livewire.operator.partials.context-redirect')
                ->layoutData($this->operatorLayoutData('درخواست‌های مشتری', 'پنل اپراتور ← درخواست‌های مشتری', 'درخواست‌های مشتری'));
        }

        $permissions = $this->operatorPermissions();

        // عیناً authorizeAccept کنترلر
        abort_unless(in_array('orders.accept', $permissions, true), 403, 'دسترسی قبول درخواست برای شما فعال نیست.');

        $timeout = max(15, (int) app(\App\Services\Settings\SettingsService::class)->get('orders.broadcast_timeout', 60));

        return view('livewire.operator.requests.index', [
            'coffeenet' => $this->operatorCoffeenet,
            'broadcastTimeout' => $timeout,
        ])->layoutData($this->operatorLayoutData('درخواست‌های مشتری', 'پنل اپراتور ← درخواست‌های مشتری', 'درخواست‌های مشتری'));
    }
}
