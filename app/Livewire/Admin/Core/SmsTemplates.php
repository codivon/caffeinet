<?php

namespace App\Livewire\Admin\Core;

use App\Services\Sms\SmsManager;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * مرکز پیامک (قالب‌های پیامک) — کامپوننت Livewire [Task 3-b]
 *
 * shell صفحه (هدر + دسته‌ها + جستجو + گرید قالب‌ها + مودال ویرایش/آزمایش)
 * با Livewire رندر می‌شود؛ لیست/ذخیره/toggle/آزمایش با همان JS قبلی به
 * endpointهای AJAX کنترلر (sms-templates/data و PUT/toggle/test) می‌رود.
 *
 * GET admin/sms-templates (این کامپوننت) | endpointهای حفظ شده‌اند.
 */
#[Layout('back.layouts.panel')]
class SmsTemplates extends Component
{
    public function render()
    {
        return view('livewire.admin.core.sms-templates', [
            'provider' => app(SmsManager::class)->driver()->name(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'مرکز پیامک',
            'breadcrumb' => 'پنل مدیریت کل ← مرکز پیامک',
            'htmlTitle' => 'مرکز پیامک',
        ]);
    }
}
