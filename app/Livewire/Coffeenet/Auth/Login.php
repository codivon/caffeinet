<?php

namespace App\Livewire\Coffeenet\Auth;

use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * ورود مدیر کافی‌نت — نسخهٔ Livewire 4 (Task 4).
 *
 * منطق عیناً از App\Http\Controllers\Back\Coffeenet\AuthController (showLogin/login)
 * منتقل شده است:
 *   • RateLimiter: ۵ تلاش ناموفق → ۶۰ ثانیه تعلیق (کلید: net-login:{ip})
 *   • AuditLogger: رویدادهای coffeenet.auth.login_failed / coffeenet.auth.login
 *   • نقش مجاز: فقط coffeenet_manager
 *   • حساب باید مدیریت حداقل یک کافی‌نتِ تأییدشده داشته باشد
 *       (EnsureCoffeenetContext::managedQuery)
 *   • یک کافی‌نت → انتخاب خودکار در session + ورود؛ چند کافی‌نت → صفحهٔ انتخاب
 *   • session()->regenerate و last_login_at مثل قبل
 *
 * کنترلر حذف نمی‌شود: POST coffeenet.login.attempt (fallback اسکریپت‌های دیگر)
 * و خروج همچنان همان‌جاست.
 */
#[Layout('livewire.coffeenet.auth.login-layout')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    /** مثل showLogin کنترلر: مدیرِ واردشده مستقیم به پنل می‌رود */
    public function mount(): void
    {
        if (Auth::check() && Auth::user()->hasRole('coffeenet_manager')) {
            // ریشهٔ پنل (coffeenet.home) خودش به داشبورد کافی‌نتِ جلسه یا صفحهٔ انتخاب می‌رود
            $this->redirectRoute('coffeenet.home', navigate: true);
        }
    }

    /** مثل login کنترلر — اما به‌جای JSON، رندر مجدد Livewire با خطاها */
    public function store(): void
    {
        $key = 'net-login:'.(request()->ip() ?? 'cli');

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            $this->addError('form', "تلاش‌های بیش از حد؛ {$seconds} ثانیه دیگر امتحان کنید.");

            return;
        }

        $data = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل معتبر نیست.',
            'password.required' => 'رمز عبور الزامی است.',
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'is_active' => true])) {
            RateLimiter::hit($key, 60);

            AuditLogger::log('coffeenet.auth.login_failed', null, null,
                ['email' => $data['email']], 'تلاش ناموفق ورود مدیر کافی‌نت');

            $this->addError('form', 'ایمیل یا رمز عبور اشتباه است.');

            return;
        }

        $user = Auth::user();

        if (! $user->hasRole('coffeenet_manager')) {
            Auth::logout();

            $this->addError('form', 'این حساب برای ورود به پنل کافی‌نت مجاز نیست.');

            return;
        }

        $managed = \App\Http\Middleware\EnsureCoffeenetContext::managedQuery($user)->get();

        if ($managed->isEmpty()) {
            Auth::logout();

            $this->addError('form', 'حساب شما مدیریت هیچ کافی‌نت تأییدشده‌ای را بر عهده ندارد. با پشتیبانی تماس بگیرید.');

            return;
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        AuditLogger::log('coffeenet.auth.login', $user, null, null, 'ورود مدیر کافی‌نت');

        // یک کافی‌نت → انتخاب خودکار؛ چند کافی‌نت → صفحه انتخاب
        if ($managed->count() === 1) {
            request()->session()->put('coffeenet_id', $managed->first()->id);

            $this->redirectRoute('coffeenet.dashboard', ['coffeenet' => $managed->first()], navigate: true);

            return;
        }

        request()->session()->forget('coffeenet_id');

        $this->redirectRoute('coffeenet.choose', navigate: true);
    }

    public function render()
    {
        return view('livewire.coffeenet.auth.login');
    }
}
