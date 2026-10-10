<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۵۲ — پخش هوشمند امتیازی + تعهد زمان تحویل (SLA).
 *
 * ۱) coffeenets: آمار پذیرش برای امتیازدهی پخش —
 *    dispatch_accepted (پذیرش‌های موفق)، dispatch_rejected (پایان مهلت بدون پذیرش)،
 *    dispatch_avg_seconds (میانگین ثانیهٔ پاسخ تا پذیرش — میانگین متحرک).
 * ۲) orders: sla_deadline_at — مهلت تعهدی تحویل (paid_at + تنظیمات SLA)
 *    برای تایمر زندهٔ مشتری و بج «دیرکرد» در پنل ادمین.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coffeenets', function (Blueprint $table) {
            $table->unsignedInteger('dispatch_accepted')->default(0)->comment('تعداد پذیرش موفق (پخش هوشمند فاز ۵۲)');
            $table->unsignedInteger('dispatch_rejected')->default(0)->comment('پایان مهلت پخش بدون پذیرش');
            $table->unsignedInteger('dispatch_avg_seconds')->nullable()->comment('میانگین ثانیه تا پذیرش');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('sla_deadline_at')->nullable()->comment('مهلت تعهدی تحویل پس از پرداخت (فاز ۵۲)');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('sla_deadline_at'));
        Schema::table('coffeenets', fn (Blueprint $t) => $t->dropColumn(['dispatch_accepted', 'dispatch_rejected', 'dispatch_avg_seconds']));
    }
};
