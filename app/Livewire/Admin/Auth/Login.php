<?php

namespace App\Livewire\Admin\Auth;

use App\Livewire\Auth\Concerns\PanelLoginSecurity;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * ورود مدیران پنل مدیریت کل — الگوی مرجع تبدیل Blade+jQuery به Livewire SPA (Task 2-a)
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\AuthController (showLogin/login) منتقل شده است:
 *   • RateLimiter: ۵ تلاش ناموفق → ۶۰ ثانیه تعلیق (کلید: admin-login:{ip})
 *   • AuditLogger: لاگ موفقیت/شکست ورود
 *   • پیام‌های فارسی یکسان با پیاده‌سازی قبلی
 *   • تشخیص نقش (super_admin/admin)، دسترسی dashboard.access برای مدیر دستیار
 *   • is_active=true در credentials، session()->regenerate، last_login_at
 *
 * کنترلر AuthController حذف نمی‌شود: POST fallback (admin.login.attempt) و خروج همچنان همان‌جاست.
 *
 * الگو برای ایجنت‌های بعدی:
 *   • کلاس: App\Livewire\{Panel}\{Section}\{Page} — ویو: resources/views/livewire/...
 *   • لایهٔ پنل: #[Layout('back.layouts.panel')] (لایه هم @yield('content') و هم {{ $slot }} دارد)
 *   • لینک‌های بین‌صفحه‌ای: wire:navigate | ریدایرکت: $this->redirectRoute(..., navigate: true)
 *   • تم: کلاس‌های brand-* (آبی) طبق THEME.md — هیچ amber تیره
 */
#[Layout('livewire.admin.auth.login-layout')]
class Login extends Component
{
    use PanelLoginSecurity;

    public string $email = '';

    public string $password = '';

    public bool $remember = true;

    /** مثل showLogin کنترلر: مدیرِ واردشده مستقیم به داشبورد می‌رود */
    public function mount(): void
    {
        if (Auth::check() && Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            $this->redirectRoute('admin.dashboard', navigate: true);
        }
    }

    /** مثل login کنترلر — اما به‌جای JSON، رندر مجدد Livewire با خطاها */
    public function store(): void
    {
        // فاز ۵۰ — اگر در گام دوم ۲FA هستیم، همان دکمهٔ ارسال کد را تأیید می‌کند
        if ($this->twoFaStep) {
            $this->verifyTwoFactor();

            return;
        }

        $key = $this->secLimiterKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            $this->addError('form', "تلاش‌های بیش از حد؛ {$seconds} ثانیه دیگر امتحان کنید.");

            return;
        }

        // فاز ۵۰ — ربات‌گیر (فقط وقتی تنظیمات گفته باشد)
        if (! $this->passCaptchaGate()) {
            return;
        }

        $data = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'email.required' => 'ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل معتبر نیست.',
            'password.required' => 'رمز عبور الزامی است.',
        ]);

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, (bool) ($data['remember'] ?? false))) {
            RateLimiter::hit($key, 60);

            AuditLogger::log('auth.login_failed', null, null,
                ['email' => $data['email']], 'تلاش ناموفق ورود مدیر کل');

            $this->addError('form', 'ایمیل یا رمز عبور اشتباه است.');

            return;
        }

        $user = Auth::user();

        // مدیران پنل: مدیر کل یا مدیر دستیار (درخواست بازخوردی ۶-۴)
        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            Auth::logout();

            $this->addError('form', 'شما به پنل مدیریت دسترسی ندارید.');

            return;
        }

        // مدیر دستیارِ بدون هیچ مجوزی — حداقل داشبورد لازم است
        if (! $user->hasRole('super_admin') && ! $user->can('dashboard.access')) {
            Auth::logout();

            $this->addError('form', 'هیچ بخشی از پنل برای شما فعال نشده است؛ با مدیر کل تماس بگیرید.');

            return;
        }

        // فاز ۵۰ — ورود دومرحله‌ای پیامکی (در تنظیمات ← قابلیت‌ها)
        if ($this->twoFaGate($user)) {
            return;
        }

        RateLimiter::clear($key);

        $this->finishLogin($user);
    }

    /** کلید RateLimiter همین فرم (برای کپچا/۲FA) */
    protected function secLimiterKey(): string
    {
        return 'admin-login:'.(request()->ip() ?? 'cli');
    }

    /** فاز ۵۰ — پس از لاگین کامل (مستقیم یا پس از تأیید کد) */
    protected function finishLogin(User $user): void
    {
        request()->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        AuditLogger::log('auth.login', $user, null, null, 'ورود موفق مدیر کل');

        $this->redirectRoute('admin.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.auth.login');
    }
}
