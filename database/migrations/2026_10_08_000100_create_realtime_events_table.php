<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۱۲ — جدول رویدادهای Realtime برای ترابورت SSE (اتصال دائمی داخلی).
 *
 * معماری «بیدارباش» مثل پوشر: رویداد فقط خبر است؛ دادهٔ واقعی همیشه از
 * API خود سیستم خوانده می‌شود. هر ردیف یک رویداد است که یک جریان SSE
 * (شبه‌سوکتِ همان دامنه) بلافاصله به گیرنده‌هایش می‌رساند:
 *   • user_id  → رویداد شخصیِ یک کاربر (اعلان/سفارش من/چت)
 *   • for_staff → رویداد سراسری پنل‌های پشتی (ادمین/سازمان/کافی‌نت/اپراتور)
 * ردیف‌های کهنه (> ۱۰ دقیقه) هنگام درج، گاه‌به‌گاه پاک می‌شوند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realtime_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->boolean('for_staff')->default(false)->index();
            $table->string('channel', 64)->index();
            $table->string('event', 64);
            $table->text('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realtime_events');
    }
};
