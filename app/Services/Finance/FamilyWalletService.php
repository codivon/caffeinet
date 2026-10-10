<?php

namespace App\Services\Finance;

use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Validation\ValidationException;

/**
 * فاز ۵۳ — حساب خانواده/تیمی: خرجِ زیرحساب از کیف پولِ حساب اصلی.
 *
 * جریان: زیرحساب (users.parent_id) سفارش می‌دهد و می‌خواهد از کیف پول
 * بپردازد → به‌جای کیف خودش، کیفِ والد بدهکار می‌شود و در متای تراکنش
 * «family_user_id» ثبت می‌گردد (برای گردش حساب و سقف‌ها).
 *
 * سقف‌ها (اختیاری، روی خودِ زیرحساب ست می‌شوند از اپ):
 *   • wallet_daily_limit  — سقف خرج در روز
 *   • wallet_monthly_limit — سقف خرج در ماه
 *   • family_blocked — توقف کامل خرج (بدون بلاک ورود)
 *
 * سوییچ: features.family_accounts — خاموش یعنی همه‌چیز مثل قبل.
 */
class FamilyWalletService
{
    public function __construct(
        protected WalletService $wallets,
        protected SettingsService $settings,
    ) {}

    /**
     * پرداخت برای کاربر (زیرحساب → کیف والد؛ حساب اصلی → کیف خودش).
     *
     * @param  string  $desc  توضیح تراکنش
     * @param  array<string, mixed>  $meta  متای اضافه
     * @return \App\Models\Transaction
     */
    public function debitFor(User $user, float $amount, string $refType, ?int $refId, string $desc, array $meta = [])
    {
        $parent = $this->enabledParentOf($user);

        if ($parent === null) {
            return $this->wallets->debit($user, $amount, $refType, $refId, $desc, $meta);
        }

        $this->assertWithinLimits($user, $amount);

        return $this->wallets->debit(
            $parent,
            $amount,
            $refType,
            $refId,
            $desc.' — '.($user->name ?: 'زیرحساب خانواده'),
            array_merge($meta, [
                'family_user_id' => $user->id,
                'family_user_name' => trim(($user->name ?? '').' '.($user->family ?? '')) ?: null,
            ]),
        );
    }

    /** کیف پولی که این کاربر با آن خرج می‌کند (والد یا خودش) */
    public function payerOf(User $user): User
    {
        return $this->enabledParentOf($user) ?? $user;
    }

    /** اگر حساب خانواده فعال و کاربر زیرحسابِ قابل‌خرج است، والد را بده */
    public function enabledParentOf(User $user): ?User
    {
        if (! $this->settings->get('features.family_accounts', false)) {
            return null;
        }

        if (! $user->parent_id) {
            return null;
        }

        $parent = $user->parent()->first();

        if (! $parent || ! $parent->is_active) {
            return null;
        }

        return $parent;
    }

    /** چک سقف‌های خرج زیرحساب (بلاک/روزانه/ماهانه) */
    public function assertWithinLimits(User $child, float $amount): void
    {
        if ($child->family_blocked) {
            throw ValidationException::withMessages([
                'method' => ['خرج این زیرحساب توسط حساب اصلی متوقف شده است.'],
            ]);
        }

        if ($child->wallet_daily_limit !== null) {
            $today = $child->familySpendToday();

            if ($today + $amount > (float) $child->wallet_daily_limit + 0.001) {
                throw ValidationException::withMessages([
                    'method' => ['سقف خرج روزانهٔ این زیرحساب ('.fa_money((float) $child->wallet_daily_limit).') پر شده است.'],
                ]);
            }
        }

        if ($child->wallet_monthly_limit !== null) {
            $month = $child->familySpendThisMonth();

            if ($month + $amount > (float) $child->wallet_monthly_limit + 0.001) {
                throw ValidationException::withMessages([
                    'method' => ['سقف خرج ماهانهٔ این زیرحساب ('.fa_money((float) $child->wallet_monthly_limit).') پر شده است.'],
                ]);
            }
        }
    }
}
