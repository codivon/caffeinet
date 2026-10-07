<?php

namespace App\Livewire\Org\Auth;

use App\Models\Organization;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * ورود مدیر سازمان — نسخهٔ Livewire 4 [Task 6]
 *
 * منطق عیناً از App\Http\Controllers\Back\Org\AuthController (showLogin/login)
 * منتقل شده است:
 *   • RateLimiter: ۵ تلاش ناموفق → ۶۰ ثانیه تعلیق (کلید: org-login:{ip})
 *   • AuditLogger: لاگ org.auth.login / org.auth.login_failed
 *   • is_active=true در credentials، session()->regenerate، last_login_at
 *   • پس از ورود: یک سازمان → انتخاب خودکار و رفتن به داشبورد؛
 *     چند سازمان → پاک‌کردن org_id و رفتن به صفحهٔ انتخاب
 *
 * کنترلر حذف نمی‌شود: POST fallback (org.login.attempt)، choose/select/logout
 * همچنان همان‌جاست.
 *
 * لایهٔ مستقل صفحهٔ ورود: back.layouts.org نیست (صفحهٔ بیرونی بدون سایدبار).
 */
#[Layout('livewire.org.auth.login-layout')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    /** مثل showLogin کنترلر: مدیر سازمانِ واردشده مستقیم به داشبورد می‌رود */
    public function mount(): void
    {
        if (Auth::check() && Auth::user()->hasRole('org_manager')) {
            $this->redirectRoute('org.dashboard', navigate: true);
        }
    }

    /** مثل login کنترلر — اما به‌جای JSON، رندر مجدد Livewire با خطاها */
    public function store(): void
    {
        $key = 'org-login:'.(request()->ip() ?? 'cli');

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

            AuditLogger::log('org.auth.login_failed', null, null,
                ['email' => $data['email']], 'تلاش ناموفق ورود مدیر سازمان');

            $this->addError('form', 'ایمیل یا رمز عبور اشتباه است.');

            return;
        }

        $user = Auth::user();

        if (! $user->hasRole('org_manager')) {
            Auth::logout();

            $this->addError('form', 'این حساب برای ورود به پنل سازمان مجاز نیست.');

            return;
        }

        $owned = Organization::query()->where('owner_id', $user->id)->orderBy('id')->get();

        if ($owned->isEmpty()) {
            Auth::logout();

            $this->addError('form', 'حساب شما به هیچ سازمانی متصل نیست. با پشتیبانی تماس بگیرید.');

            return;
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        AuditLogger::log('org.auth.login', $user, null, null, 'ورود مدیر سازمان');

        // یک سازمان → انتخاب خودکار؛ چند سازمان → صفحه انتخاب (عیناً کنترلر)
        if ($owned->count() === 1) {
            request()->session()->put('org_id', $owned->first()->id);

            $this->redirectRoute('org.dashboard', navigate: true);

            return;
        }

        request()->session()->forget('org_id');

        $this->redirectRoute('org.choose', navigate: true);
    }

    public function render()
    {
        return view('livewire.org.auth.login');
    }
}
