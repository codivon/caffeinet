<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۵۱ — اینسایت مصرف API + وب‌هوک‌ها.
 *
 * ۱) api_usage_logs — یک ردیف به‌ازای هر درخواست /api/v1 (با سوییچ
 *    features.api_usage_log روشن/خاموش می‌شود؛ پاکسازی با system:cleanup):
 *    چه کاربری، از چه IP/دستگاهی، کدام مسیر، با کدام کلید و چه پاسخی.
 *
 * ۲) webhooks — اشتراک رویداد: آدرس مقصد + رمز امضا + رویدادهای انتخابی.
 * ۳) webhook_deliveries — لاگ ارسال هر رویداد (کد پاسخ + تکه‌ای از بدنه).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->comment('کاربر احرازشده با Bearer — مهمان null');
            $table->string('method', 10);
            $table->string('path', 190);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['api_key_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('secret', 80);
            $table->json('events')->comment('آرایهٔ رویدادهای فعال — مثل ["order.created","order.delivered"]');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('last_status_code')->nullable();
            $table->timestamp('last_called_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->cascadeOnDelete();
            $table->string('event', 60);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('response_snippet', 300)->nullable();
            $table->boolean('ok')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['webhook_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('api_usage_logs');
    }
};
