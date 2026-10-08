<?php

namespace App\Livewire\Admin\Commissions;

use App\Models\CommissionSetting;
use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * قواعد کمیسیون (فاز ۸) — نسخهٔ Livewire 4 (جایگزین استاب Task 3-e).
 *
 * موتور تسویه اول قاعدهٔ فعال خدمت را برمی‌دارد؛ نبود → قاعدهٔ سراسری.
 * ساختار صفحه عیناً نسخهٔ blade قبلی است (resources/views/back/admin/commissions):
 *   • قاعدهٔ سراسری: فرم سمت کاربر + PUT  /admin/commissions/global
 *   • قواعد اختصاصی خدمات: لیست با AJAX از /admin/commissions/data و
 *     CRUD همان کنترلر (store/update/toggle/destroy)
 * اسکریپت صفحه همان pages/admin/commissions/index.js است — IIFE امن برای
 * اجرای مجدد در هر ناوبری wire:navigate (درس «تب‌های مرده» فاز ۱۲).
 */
#[Layout('back.layouts.panel')]
class Index extends Component
{
    public function render()
    {
        $global = CommissionSetting::query()
            ->where('scope', 'global')
            ->whereNull('service_id')
            ->first();

        $services = Service::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $serviceRulesCount = CommissionSetting::query()
            ->where('scope', 'service')
            ->count();

        return view('livewire.admin.commissions.index', [
            'global' => $global,
            'services' => $services,
            'serviceRulesCount' => $serviceRulesCount,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'قواعد کمیسیون',
            'breadcrumb' => 'پنل مدیریت کل ← مالی و کمیسیون ← قواعد کمیسیون',
            'htmlTitle' => 'قواعد کمیسیون',
        ]);
    }
}
