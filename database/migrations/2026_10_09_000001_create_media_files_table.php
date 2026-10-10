<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v42 — دفتر فایل‌های رسانه‌ای برای فشرده‌سازی سمت سرور.
 *
 * دستور «media:compress» فایل‌های آپلودشدهٔ جدید را در این جدول ثبت و
 * به‌تدریج (در دسته‌های کوچک، بدون فشار به سرور) فشرده می‌کند.
 * تصاویر چت/تیکت/سفارش «رمزنگاری‌شده» نگهداری می‌شوند (SecureFile) و
 * سرویس فشرده‌سازی رمزگشایی → فشرده‌سازی → رمزنگاری دوباره را انجام می‌دهد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 20);                 // local | public
            $table->string('path_hash', 64)->unique();  // sha1(disk:path) — جلوگیری از تکرار
            $table->string('path');                     // مسیر نسبی روی دیسک
            $table->string('type', 12);                 // image | video | other
            $table->unsignedBigInteger('original_size')->default(0);
            $table->unsignedBigInteger('final_size')->default(0);
            $table->string('status', 12)->default('pending'); // pending | done | skipped | failed
            $table->string('note', 190)->nullable();    // دلیل skip/خطا
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
