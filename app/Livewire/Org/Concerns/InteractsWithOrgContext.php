<?php

namespace App\Livewire\Org\Concerns;

use App\Models\Organization;

/**
 * زمینهٔ سازمان برای کامپوننت‌های Livewire پنل سازمان [Task 6]
 *
 * منطق عیناً از App\Http\Middleware\EnsureOrgContext منتقل شده است:
 *   • سازمان فعال از session با کلید org_id خوانده می‌شود
 *   • عضویت/مالکیت اعتبارسنجی می‌شود (owner_id = کاربر جاری)
 *   • تک‌سازمانی → انتخاب خودکار؛ چندسازمانی بدون انتخاب → صفحه انتخاب
 *
 * چرا اینجا؟ view()->share('organization', ...) در آپدیت‌های زندهٔ Livewire
 * (POST /livewire/update — بدون middleware org.context) اجرا نمی‌شود؛ بنابراین
 * هر کامپوننت همین منطق را خودش اجرا و «organization» را صریحاً با layoutData
 * به لایهٔ back.layouts.org پاس می‌دهد. (لایه در load اولیه از share می‌خواند که OK است.)
 *
 * تفاوت تنها در مسیر خطا: به‌جای logout فوری وسط آپدیت زنده (که رندر لایه را
 * با کاربر null می‌شکند)، ریدایرکت ثبت و پاک‌سازی نهایی به بارگذاری صفحهٔ بعدی
 * موکول می‌شود — همان‌جا middleware مسیر choose/login را مثل قبل می‌بندد.
 */
trait InteractsWithOrgContext
{
    /** سازمان فعالِ نشست — در هر چرخهٔ کامپوننت کش می‌شود */
    protected ?Organization $orgContext = null;

    /** سازمان فعال یا null (همراه ثبت ریدایرکت در مسیرهای خطا) */
    protected function resolveOrgContext(): ?Organization
    {
        if ($this->orgContext !== null) {
            return $this->orgContext;
        }

        $user = auth()->user();

        if (! $user) {
            $this->redirectRoute('org.login', navigate: true);

            return null;
        }

        /** @var Organization|null $org */
        $org = session('org_id')
            ? Organization::query()
                ->where('id', session('org_id'))
                ->where('owner_id', $user->id)
                ->first()
            : null;

        if (! $org) {
            $owned = Organization::query()->where('owner_id', $user->id)->orderBy('id')->get();

            if ($owned->count() === 1) {
                // تنها یک سازمان → انتخاب خودکار (عیناً مثل middleware)
                $org = $owned->first();
                session()->put('org_id', $org->id);
            } elseif ($owned->count() > 1) {
                // چند سازمان بدون انتخاب → صفحهٔ انتخاب
                $this->redirectRoute('org.choose', navigate: true);

                return null;
            } else {
                // هیچ سازمانی → middleware در بارگذاری بعدی خروج و پیام خطا را انجام می‌دهد
                $this->redirectRoute('org.login', navigate: true);

                return null;
            }
        }

        return $this->orgContext = $org;
    }

    /**
     * سازمان برای رندر — اگر زمینه نامعتبر شد، ریدایرکت ثبت شده و
     * جایگزین خنثی (بدون ذخیره) برمی‌گردد تا رندر لایه در آپدیت زنده نشکند.
     *
     * ⚠ کامپوننت‌ها در مسیر null نباید هیچ کوئری/نوشتنی با جایگزین انجام دهند؛
     * کافی است رندر خنثی برگردانند (ریدایرکت ثبت شده از قبل).
     */
    protected function orgForRender(): Organization
    {
        return $this->resolveOrgContext()
            ?? static::neutralOrganization();
    }

    /** جایگزین خنثی سازمان — فقط برای رندر امن لایه (هرگز ذخیره نمی‌شود) */
    protected static function neutralOrganization(): Organization
    {
        return Organization::make(['name' => '', 'status' => 'approved']);
    }
}
