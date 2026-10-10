<?php

namespace App\Livewire\Operator\Concerns;

use App\Http\Middleware\EnsureOperatorContext;
use App\Models\Coffeenet;
use App\Models\StaffAssignment;
use App\Support\OperatorPermissions;

/**
 * زمینهٔ اپراتور برای کامپوننت‌های Livewire [Task 5]
 *
 * middleware ی EnsureOperatorContext فقط روی درخواست‌های کامل صفحه اجرا می‌شود؛
 * درخواست‌های آپدیت لایووایر (/livewire/update) از آن عبور نمی‌کنند و
 * view()->share هم در آن‌ها اجرا نمی‌شود. پس هر کامپوننت، زمینه را خودش
 * عیناً با همان منطق middleware حل می‌کند:
 *   • انتصاب فعال از session (کلید SESSION_KEY middleware)
 *   • در نبودِ انتخاب معتبر → اولین انتصاب فعال به‌صورت خودکار (مثل middleware)
 *   • بدون هیچ انتصابی → خروج و بازگشت به ورود اپراتور
 *
 * هوک boot تِرِیت (bootInteractsWithOperatorContext) در هر درخواست لایووایر —
 * چه mount اولیه چه آپدهای بعدی — قبل از mount اجرا می‌شود.
 * متغیرها سپس صریح به ویو پاس داده می‌شوند (coffeenet / operatorAssignment).
 */
trait InteractsWithOperatorContext
{
    /** انتصاب فعالِ اپراتور در کافی‌نت جاری (مقادیر null یعنی زمینه حل نشد) */
    protected ?StaffAssignment $operatorAssignment = null;

    /** کافی‌نتِ فعال از session */
    protected ?Coffeenet $operatorCoffeenet = null;

    /** آیا حل زمینه شکست خورده (کاربر/انتصابی نیست) و ریدایرکت ثبت شده است؟ */
    protected bool $operatorContextFailed = false;

    /** حل زمینهٔ اپراتور — عیناً منطق EnsureOperatorContext@handle (هوک boot تِرِیت؛ در هر درخواست)
     *  نکته [فیکس]: هوک باید public باشد — Livewire هوک boot تِرِیت را از طریق
     *  proxy بیرون از scope کلاس صدا می‌زند؛ متد protected به __call می‌افتد و
     *  BadMethodCallException «does not exist» می‌دهد. */
    public function bootInteractsWithOperatorContext(): void
    {
        $this->resolveOperatorContext();
    }

    /** حل زمینهٔ اپراتور — عیناً منطق EnsureOperatorContext@handle */
    protected function resolveOperatorContext(): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->failOperatorContext();

            return;
        }

        $assignments = EnsureOperatorContext::assignmentsQuery($user)->with('coffeenet')->get();

        $sessionKey = EnsureOperatorContext::SESSION_KEY;

        /** @var StaffAssignment|null $assignment */
        $assignment = session()->get($sessionKey)
            ? $assignments->first(fn (StaffAssignment $a) => $a->coffeenet_id === (int) session()->get($sessionKey))
            : null;

        if (! $assignment) {
            if ($assignments->count() >= 1) {
                // اپراتور فقط در یک کافی‌نت فعالیت می‌کند؛ بدون صفحهٔ انتخاب —
                // اولین انتصاب فعال به‌صورت خودکار زمینه می‌شود (انتقال فقط توسط مدیر کل)
                $assignment = $assignments->first();
                session()->put($sessionKey, $assignment->coffeenet_id);
            } else {
                $this->failOperatorContext();

                return;
            }
        }

        $this->operatorAssignment = $assignment;
        $this->operatorCoffeenet = $assignment->coffeenet;
        $this->operatorContextFailed = false;
    }

    /** خروج + بازگشت به ورود — همان رفتار middleware برای «هیچ انتصابی» */
    protected function failOperatorContext(): void
    {
        $this->operatorAssignment = null;
        $this->operatorCoffeenet = null;
        $this->operatorContextFailed = true;

        if (auth()->check()) {
            auth()->logout();
            session()->invalidate();
        }

        $this->redirectRoute('operator.login', navigate: true);
    }

    /**
     * دسترسی‌های فیلترشدهٔ اپراتور (عیناً OperatorPermissions::filter روی assignment جاری)
     *
     * @return array<int, string>
     */
    protected function operatorPermissions(): array
    {
        return OperatorPermissions::filter($this->operatorAssignment?->permissions ?? []);
    }

    /** آیا اپراتور «مشاهدهٔ همهٔ سفارش‌های کافی‌نت» را دارد؟ (مثل canAll کنترلرها) */
    protected function operatorCanAll(): bool
    {
        return in_array('orders.view', $this->operatorPermissions(), true);
    }

    /** آرایهٔ layoutData استاندارد پنل اپراتور (الگوی back.operator.layouts.panel) */
    protected function operatorLayoutData(string $pageTitle, string $breadcrumb, string $htmlTitle): array
    {
        return [
            'user' => auth()->user(),
            'pageTitle' => $pageTitle,
            'breadcrumb' => $breadcrumb,
            'htmlTitle' => $htmlTitle,
        ];
    }
}
