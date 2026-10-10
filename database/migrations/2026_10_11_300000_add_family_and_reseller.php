<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۵۳ — حساب خانواده/تیمی + حالت فروشنده (برند مشترک).
 *
 * ۱) حساب خانواده (سوییچ features.family_accounts):
 *    • users.parent_id — زیرحسابِ حساب اصلی (خرج از کیف پول والد)
 *    • users.wallet_daily_limit / wallet_monthly_limit — سقف خرج اختیاری زیرحساب
 *    • users.family_blocked — مسدودسازی خرج زیرحساب بدون بلاک شدن ورود
 *
 * ۲) فروشنده (سوییچ features.reseller):
 *    • organizations.is_reseller / ref_code (لینک اختصاصی ?ref=CODE) / reseller_percent
 *    • users.referred_org_id — مشتری معرفی‌شده توسط فروشنده (از کوکی ?ref=)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')
                ->comment('فاز ۵۳ — والد زیرحساب خانواده');
            $table->decimal('wallet_daily_limit', 15, 2)->nullable()->after('is_active')
                ->comment('فاز ۵۳ — سقف خرج روزانهٔ زیرحساب');
            $table->decimal('wallet_monthly_limit', 15, 2)->nullable()->after('wallet_daily_limit')
                ->comment('فاز ۵۳ — سقف خرج ماهانهٔ زیرحساب');
            $table->boolean('family_blocked')->default(false)->after('wallet_monthly_limit')
                ->comment('فاز ۵۳ — توقف خرج زیرحساب');
            $table->foreignId('referred_org_id')->nullable()->after('parent_id')
                ->comment('فاز ۵۳ — سازمان فروشندهٔ معرفی‌کننده');

            $table->index('parent_id');
            $table->index('referred_org_id');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('is_reseller')->default(false)->after('status')
                ->comment('فاز ۵۳ — حالت فروشنده');
            $table->string('ref_code', 30)->nullable()->unique()->after('is_reseller')
                ->comment('فاز ۵۳ — کد معرف لینک ?ref=');
            $table->decimal('reseller_percent', 5, 2)->nullable()->after('ref_code')
                ->comment('فاز ۵۳ — درصد کمیسیون فروشنده (null = پیش‌فرض سراسری)');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', fn (Blueprint $t) => $t->dropColumn(['is_reseller', 'ref_code', 'reseller_percent']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['parent_id', 'wallet_daily_limit', 'wallet_monthly_limit', 'family_blocked', 'referred_org_id']));
    }
};
