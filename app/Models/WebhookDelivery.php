<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * فاز ۵۱ — لاگ ارسال وب‌هوک (کد پاسخ + تکه‌ای از بدنه).
 * فاز ۶۰ — رابطهٔ webhook + خواندن شمارهٔ تلاش: جدول ستون attempts ندارد،
 * پس بازارسال دستی (WebhookDispatcher::retry) شمارهٔ تلاش را به‌صورت نشانگر
 * ««تلاش N» » ابتدای response_snippet نگه می‌دارد؛ این متدها آن را
 * می‌خوانند (attemptNumber) یا متن پاسخ را بدون نشانگر می‌دهند (snippetText).
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

    /** وب‌هوک مالک این ردیف تحویل */
    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }

    /** شمارهٔ تلاش — ارسال عادی ۱ است؛ هر بازارسال دستی یک واحد اضافه می‌کند */
    public function attemptNumber(): int
    {
        if ($this->response_snippet
            && preg_match('/^«تلاش (\d+)» /u', (string) $this->response_snippet, $m)) {
            return max(1, (int) $m[1]);
        }

        return 1;
    }

    /** متن پاسخ بدون نشانگر تلاش (برای نمایش در UI) */
    public function snippetText(): string
    {
        return (string) preg_replace('/^«تلاش \d+» /u', '', (string) $this->response_snippet);
    }
}
