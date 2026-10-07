<?php

namespace App\Livewire\Operator\Auth;

use App\Http\Middleware\EnsureOperatorContext;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * ورود اپراتور — نسخهٔ Livewire 4 [Task 5]
 *
 * منطق عیناً از App\Http\Controllers\Back\Operator\AuthController (showLogin/login)
 * منتقل شده است:
 *   • RateLimiter: کلید «op-login:{ip}» — ۵ تلاش ناموفق → ۶۰ ثانیه تعلیق
 *   • AuditLogger: رویدادهای operator.auth.login_failed / operator.auth.login
 *   • اعتبارنامه با is_active=true، بدون «مرا به خاطر بسپار» (مثل کنترلر)
 *   • نقش operator الزامی؛ انتصاب فعال در کافی‌نت تأییدشده الزامی (assignmentsQuery)
 *   • session()->regenerate، last_login_at، انتخاب خودکار کافی‌نت (SESSION_KEY)
 *
 * کنترلر حذف نمی‌شود: POST fallback (operator.login.attempt) و خروج همان‌جاست.
 */
#[Layout('livewire.operator.auth.login-layout')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    /** مثل showLogin کنترلر: اپراتورِ واردشده مستقیم به داشبورد می‌رود */
    public function mount(): void
    {
        if (Auth::check() && Auth::user()->hasRole('operator')) {
            $this->redirectRoute('operator.dashboard', navigate: true);
        }
    }

    /** مثل login کنترلر — اما به‌جای JSON، رندر مجدد Livewire با خطاها */
    public function store(): void
    {
        $key = 'op-login:'.(request()->ip() ?? 'cli');

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

            AuditLogger::log('operator.auth.login_failed', null, null,
                ['email' => $data['email']], 'تلاش ناموفق ورود اپراتور');

            $this->addError('form', 'ایمیل یا رمز عبور اشتباه است.');

            return;
        }

        $user = Auth::user();

        if (! $user->hasRole('operator')) {
            Auth::logout();

            $this->addError('form', 'این حساب برای ورود به پنل اپراتور مجاز نیست.');

            return;
        }

        $assignments = EnsureOperatorContext::assignmentsQuery($user)
            ->with('coffeenet:id,name,status')
            ->get();

        if ($assignments->isEmpty()) {
            Auth::logout();

            $this->addError('form', 'حساب شما اپراتورِ فعال هیچ کافی‌نت تأییدشده‌ای نیست. با مدیر کافی‌نت خود تماس بگیرید.');

            return;
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        AuditLogger::log('operator.auth.login', $user, null, null, 'ورود اپراتور');

        // اپراتور فقط در یک کافی‌نت فعالیت می‌کند — انتخاب خودکار، بدون صفحهٔ انتخاب
        // (انتقال بین کافی‌نت‌ها فقط توسط مدیر کل انجام می‌شود)
        request()->session()->put(EnsureOperatorContext::SESSION_KEY, $assignments->first()->coffeenet_id);

        $this->redirectRoute('operator.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.operator.auth.login');
    }
}
