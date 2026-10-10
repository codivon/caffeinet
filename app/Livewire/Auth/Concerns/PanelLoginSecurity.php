<?php

namespace App\Livewire\Auth\Concerns;

use App\Models\User;
use App\Services\Sms\SmsManager;
use App\Support\Captcha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * فاز ۵۰ — امنیت مشترک ورود پنل‌ها: ربات‌گیر (کپچا) + ورود دومرحله‌ای پیامکی.
 *
 * استفاده در هر کامپوننت لاگین (Admin/Org/Coffeenet/Operator):
 *   1) `use PanelLoginSecurity;`
 *   2) ابتدای store() بعد از چک قفل RateLimiter:
 *         if (! $this->passCaptchaGate()) return;
 *      (کپچا فقط وقتی لازم است بررسی می‌شود — حالت off/smart)
 *   3) در ابتدای store() اگر $this->twoFaStep است، به‌جای ادامه:
 *         $this->verifyTwoFactor(); return;
 *   4) بعد از موفقیت Auth::attempt + چک نقش‌ها، به‌جای مستقیم لاگین‌کردن:
 *         if ($this->twoFaGate($user)) return;   // اگر فعال بود → گام کد
 *         $this->finishLogin($user);             // tail قبلی store()
 *   5) متد finishLogin(User $user) را در همان کامپوننت پیاده کن
 *      (regenerate + last_login_at + AuditLogger + ریدایرکت مخصوص همان پنل)
 *   6) در ویو: @include('livewire.auth.partials.login-security')
 *      (فیلد کپچا داخل فرم اصلی + گام کد ۲FA جای فیلدها)
 */
trait PanelLoginSecurity
{
    /** پاسخ کپچا (wire:model) */
    public string $captcha = '';

    /** گام دوم ۲FA فعال است؟ */
    public bool $twoFaStep = false;

    /** کد ۶ رقمی (wire:model) */
    public string $twoFaCode = '';

    /** کلید RateLimiter همین فرم (هر پنل پیاده می‌کند) */
    abstract protected function secLimiterKey(): string;

    /** پس از لاگین کامل — هر پنل: regenerate + last_login_at + audit + ریدایرکت */
    abstract protected function finishLogin(User $user): void;

    /* ============================ کپچا ============================ */

    /** آیا این رندر کپچا لازم دارد؟ (برای نمایش در ویو) */
    public function captchaRequired(): bool
    {
        if ($this->twoFaStep) {
            return false;
        }

        return Captcha::required($this->secLimiterKey());
    }

    /** سؤال فعلی (برای ویو) — بدون ساخت سؤال جدید مگر منقضی/ناموجود */
    public function captchaData(): array
    {
        return Captcha::current();
    }

    /** دکمهٔ تازه‌سازی کپچا */
    public function refreshCaptcha(): void
    {
        Captcha::forget();
        $this->captcha = '';
    }

    /**
     * دروازهٔ کپچا — ابتدای store() صدا زده می‌شود.
     *
     * @return bool true = ادامه بده
     */
    protected function passCaptchaGate(): bool
    {
        if (! $this->captchaRequired()) {
            return true;
        }

        if (! Captcha::verify($this->captcha)) {
            $this->addError('captcha', 'پاسخ کد امنیتی درست نیست — سؤال جدید آمد.');

            return false;
        }

        $this->captcha = '';

        return true;
    }

    /* ============================ 2FA ============================ */

    /** آیا ورود دومرحله‌ای برای این کاربر فعال است؟ (بدون موبایل → رد می‌شود) */
    protected function twoFaNeeded(User $user): bool
    {
        if (! (bool) app(\App\Services\Settings\SettingsService::class)->get('features.two_factor')) {
            return false;
        }

        // کاربر بدون شمارهٔ موبایل معتبر — ۲FA پیامکی ممکن نیست؛ مستقیم وارد شود
        return is_string($user->mobile) && preg_match('/^09\d{9}$/', trim($user->mobile)) === 1;
    }

    /**
     * دروازهٔ ۲FA — بعد از موفقیت attempt + چک نقش‌ها.
     *
     * @return bool true = گام کد شروع شد (store بر گردد)؛ false = ادامهٔ ورود عادی
     */
    protected function twoFaGate(User $user): bool
    {
        if (! $this->twoFaNeeded($user)) {
            return false;
        }

        $this->beginTwoFactor($user);

        return true;
    }

    /** ارسال کد و رفتن به گام دوم */
    protected function beginTwoFactor(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        Cache::put('panel-2fa:'.$user->id, $code, now()->addMinutes(5));
        Cache::put('panel-2fa-cd:'.$user->id, 1, now()->addSeconds(60));
        session(['panel_2fa_user' => $user->id, 'panel_2fa_expires' => now()->addMinutes(5)->getTimestamp()]);

        app(SmsManager::class)->send(
            trim($user->mobile),
            "کد ورود دومرحله‌ای شما: {$code}\nکافی‌نت آنلاین — این کد ۵ دقیقه اعتبار دارد.",
            'panel_2fa',
        );

        $this->twoFaStep = true;
        $this->twoFaCode = '';
        $this->captcha = '';
    }

    /** متد Livewire — تأیید کد در گام دوم */
    public function verifyTwoFactor(): void
    {
        $key = $this->secLimiterKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('twoFaCode', "تلاش‌های بیش از حد؛ {$seconds} ثانیه دیگر امتحان کنید.");

            return;
        }

        $uid = session('panel_2fa_user');
        $expires = (int) session('panel_2fa_expires', 0);

        if (! $uid || $expires < now()->getTimestamp()) {
            $this->twoFaStep = false;
            session()->forget(['panel_2fa_user', 'panel_2fa_expires']);
            $this->addError('form', 'جلسهٔ تأیید منقضی شده است — دوباره وارد شوید.');

            return;
        }

        $user = User::find($uid);
        $expect = $user ? Cache::get('panel-2fa:'.$user->id) : null;

        if (! $user || ! is_string($expect) || ! hash_equals($expect, trim($this->twoFaCode))) { // فاز ۵۹ (F9): مقایسهٔ timing-safe
            RateLimiter::hit($key, 60);
            $this->addError('twoFaCode', 'کد واردشده درست نیست یا منقضی شده است.');

            return;
        }

        Cache::forget('panel-2fa:'.$user->id);
        session()->forget(['panel_2fa_user', 'panel_2fa_expires']);
        $this->twoFaStep = false;
        $this->twoFaCode = '';

        Auth::login($user, true);

        RateLimiter::clear($key);

        $this->finishLogin($user);
    }

    /** متد Livewire — ارسال دوبارهٔ کد (کول‌داون ۶۰ ثانیه) */
    public function resendTwoFactor(): void
    {
        $uid = session('panel_2fa_user');

        if (! $uid || ($user = User::find($uid)) === null) {
            $this->twoFaStep = false;
            $this->addError('form', 'جلسهٔ تأیید منقضی شده است — دوباره وارد شوید.');

            return;
        }

        if (Cache::has('panel-2fa-cd:'.$user->id)) {
            $this->addError('twoFaCode', 'برای ارسال دوباره چند لحظه صبر کنید.');

            return;
        }

        $this->beginTwoFactor($user);
    }

    /** شمارهٔ موبایل ماسک‌شده برای نمایش در گام کد */
    public function maskedMobile(): string
    {
        $uid = session('panel_2fa_user');
        $mobile = $uid ? optional(User::find($uid))->mobile : null;

        if (! is_string($mobile) || preg_match('/^09\d{9}$/', $mobile) !== 1) {
            return 'موبایل شما';
        }

        return substr($mobile, 0, 4).'***'.substr($mobile, -3);
    }
}
