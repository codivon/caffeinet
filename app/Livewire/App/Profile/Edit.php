<?php

namespace App\Livewire\App\Profile;

use App\Services\Settings\SettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «ویرایش اطلاعات شخصی» اپ مشتری — نسخهٔ Livewire 4 [Task 7]
 *
 * تنها صفحهٔ اپ مشتری که دادهٔ سروری دارد — منطق عیناً از
 * PagesController::profileEdit (v39/v40) منتقل شده است:
 *   • بازهٔ سنین مجاز (لیست کشویی سال تولد) از تنظیمات عمومی
 *   • الزام کد ملی = استعلام فینوتک روشن + تیک «بررسی کد ملی در پروفایل»
 * ذخیرهٔ فرم سمت کلاینت با profile-edit.js (CN.api → API v1) و دست‌نخورده است.
 */
#[Layout('app.layout')]
class Edit extends Component
{
    public int $birthMinAge;
    public int $birthMaxAge;
    public bool $nidRequired;

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->birthMinAge = max(1, (int) $settings->get('general.birth_min_age', 10));
        $this->birthMaxAge = max($this->birthMinAge + 1, (int) $settings->get('general.birth_max_age', 100));

        $this->nidRequired = (bool) $settings->get('finnotech.enabled', false)
            && (bool) $settings->get('finnotech.verify_profile', false);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.app.profile.edit', [
            'birthMinAge' => $this->birthMinAge,
            'birthMaxAge' => $this->birthMaxAge,
            'nidRequired' => $this->nidRequired,
        ])->layoutData([
            'htmlTitle' => 'ویرایش اطلاعات',
            'activeNav' => 'profile',
        ]);
    }
}
