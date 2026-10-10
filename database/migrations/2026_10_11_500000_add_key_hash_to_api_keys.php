<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* فاز ۵۹ (F6 ممیزی) — ذخیرهٔ امن کلیدهای وب‌سرویس
   key_hash: SHA-256 کلید برای تأیید بدون نگهداری متن ساده (کلیدهای بیرونی جدید)
   key_hint: ۴ نویسهٔ آخر برای نمایش در پنل (مقدار خام فقط یک‌بار در لحظهٔ ساخت دیده می‌شود)
   ستون key قدیمی برای کلید داخلی وب‌اپ و کلیدهای قدیمی حفظ می‌شود (فال‌بک میدل‌ویر). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->string('key', 80)->nullable()->change(); // فاز ۵۹ — کلیدهای بیرونی فقط هش ذخیره می‌کنند
            $table->string('key_hash', 64)->nullable()->index()->after('key');
            $table->string('key_hint', 8)->nullable()->after('key_hash');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex(['key_hash']);
            $table->dropColumn(['key_hash', 'key_hint']);
        });
    }
};