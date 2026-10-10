<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۴۷ — کلیدهای وب‌سرویس (API Keys).
 *
 * هر درخواست به /api/v1 باید هدر «X-Api-Key» معتبر بفرستد (میدل‌ویر api.key).
 * کلیدها در پنل ادمین (admin/api-keys) به‌صورت خودکار ساخته و حذف می‌شوند.
 * یک کلید «داخلی» هم هنگام مایگرشن seed می‌شود که اپ وب/پنل‌ها خودکار
 * با آن API را صدا بزنند (تزریق‌شده در صفحات؛ قابل حذف از UI نیست).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('برچسب دلخواه؛ مثلاً «اپ موبایل فروشنده»');
            $table->string('key', 80)->unique()->comment('کلید کامل (cnk_…)');
            $table->boolean('is_internal')->default(false)->comment('کلید داخلی وب‌اپ — از UI حذف‌نشدنی');
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('requests_count')->default(0);
            $table->timestamps();

            $table->index('is_internal');
        });

        /* کلید داخلی وب‌اپ — اپ مشتری/پنل‌ها خودکار با این کلید API را صدا می‌زنند */
        \App\Models\ApiKey::create([
            'name'        => 'اپ وب داخلی (خودکار)',
            'key'         => \App\Models\ApiKey::generate(),
            'is_internal' => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
