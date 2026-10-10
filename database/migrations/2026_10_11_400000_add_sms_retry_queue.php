<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۵۴ — صف ارسال دوبارهٔ پیامک‌های ناموفق.
 *
 * • sms_logs.attempts — چند بار تلاش شده (شامل تلاش اول)
 * • sms_logs.next_retry_at — زمان تلاش بعدی (null = در صف نیست)
 * فرمان sms:flush-retries هر ۵ دقیقه (schedule) ناموفق‌های واجد شرایط
 * را دوباره می‌فرستد (حداکثر ۳ تلاش، هر بار +۵ دقیقه فاصله).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(1)->after('status');
            $table->timestamp('next_retry_at')->nullable()->after('attempts');

            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropIndex(['status', 'next_retry_at']);
            $table->dropColumn(['attempts', 'next_retry_at']);
        });
    }
};
