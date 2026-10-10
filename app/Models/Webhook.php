<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * فاز ۵۱ — وب‌هوک: آدرس مقصد + رمز امضا + رویدادهای فعال.
 *
 * مدیریت از پنل ادمین ← کلیدهای وب‌سرویس (بخش وب‌هوک‌ها).
 * ارسال: WebhookDispatcher — امضای X-Webhook-Signature = HMAC-SHA256(body, secret).
 */
class Webhook extends Model
{
    protected $fillable = [
        'url', 'secret', 'events', 'is_active',
        'last_status_code', 'last_called_at',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_called_at' => 'datetime',
        ];
    }

    /** آیا این وب‌هوک به این رویداد گوش می‌دهد؟ */
    public function listens(string $event): bool
    {
        return is_array($this->events) && in_array($event, $this->events, true);
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class)->latest('id')->limit(10);
    }
}
