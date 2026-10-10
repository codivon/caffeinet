<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * فاز ۵۱ — لاگ ارسال وب‌هوک (کد پاسخ + تکه‌ای از بدنه).
 */
class WebhookDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'webhook_id', 'event', 'status_code', 'response_snippet', 'ok', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
