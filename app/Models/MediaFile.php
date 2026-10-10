<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * v42 — دفتر فایل‌های رسانه‌ای (فشرده‌سازی سمت سرور).
 *
 * ردیف‌ها توسط دستور «media:compress» از دیسک‌ها کشف و ثبت می‌شوند؛
 * ستون status روند هر فایل را نگه می‌دارد:
 *   pending → در انتظار پردازش | done → فشرده شد | skipped → بدون تغییر
 *   (دلیل در note) | failed → خطا (دلیل در note).
 */
class MediaFile extends Model
{
    protected $fillable = [
        'disk', 'path_hash', 'path', 'type',
        'original_size', 'final_size', 'status', 'note', 'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'original_size' => 'integer',
        'final_size' => 'integer',
    ];
}
