<?php

namespace App\Services\Webhooks;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Http;

/**
 * فاز ۵۱ — ارسال رویدادها به وب‌هوک‌های مشترک.
 *
 * رویدادهای فعلی (فاز ۵۱):
 *   order.created    — سفارش جدید ثبت شد
 *   order.delivered  — سفارش تحویل شد
 *   order.completed  — سفارش تکمیل/تأیید شد
 *
 * امضا: هدر «X-Webhook-Signature» = HMAC-SHA256(body, secret) —
 * گیرنده با همین الگوریتم و رمز خودش صحت مبدأ را چک می‌کند.
 *
 * ارسال sync با timeout کوتاه (۴ ثانیه) — روی هاست اشتراکی صف واقعی
 * نداریم؛ حجم رویداد پایین است و شکست فقط در لاگ ارسال ثبت می‌شود
 * (وب‌سایت هیچ‌وقت به‌خاطر وب‌هوک کند نمی‌شود — همه‌چیز try/catch).
 *
 * سوییچ: features.webhooks (تنظیمات ← قابلیت‌ها).
 */
class WebhookDispatcher
{
    /** لیست رویدادهای مجاز (برای UI و اعتبارسنجی) */
    public const EVENTS = [
        'order.created'   => 'ثبت سفارش جدید',
        'order.delivered' => 'تحویل سفارش',
        'order.completed' => 'تکمیل سفارش',
    ];

    /**
     * رویداد را به همهٔ وب‌هوک‌های مشترکِ فعال بفرست.
     *
     * @param  string  $event    یکی از EVENTS
     * @param  array<string, mixed>  $payload  دادهٔ رویداد
     */
    public static function dispatch(string $event, array $payload): void
    {
        try {
            if (! app(SettingsService::class)->get('features.webhooks', false)) {
                return;
            }

            if (! isset(self::EVENTS[$event])) {
                return;
            }

            Webhook::query()
                ->where('is_active', true)
                ->get()
                ->each(fn (Webhook $hook) => self::send($hook, $event, $payload));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** ارسال به یک وب‌هوک + لاگ ارسال */
    public static function send(Webhook $hook, string $event, array $payload): bool
    {
        try {
            if (! $hook->listens($event)) {
                return false;
            }

            $body = json_encode([
                'event'     => $event,
                'sent_at'   => now()->toIso8601String(),
                'data'      => $payload,
            ], JSON_UNESCAPED_UNICODE);

            $signature = hash_hmac('sha256', (string) $body, $hook->secret);

            $response = Http::timeout(4)
                ->withHeaders([
                    'Content-Type'        => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event'     => $event,
                ])
                ->post($hook->url, json_decode((string) $body, true));

            $ok = $response->successful();

            $hook->forceFill([
                'last_status_code' => $response->status(),
                'last_called_at'   => now(),
            ])->save();

            WebhookDelivery::create([
                'webhook_id'       => $hook->id,
                'event'            => $event,
                'status_code'      => $response->status(),
                'response_snippet' => substr($response->body() ?? '', 0, 300),
                'ok'               => $ok,
                'created_at'       => now(),
            ]);

            return $ok;
        } catch (\Throwable $e) {
            try {
                WebhookDelivery::create([
                    'webhook_id'       => $hook->id,
                    'event'            => $event,
                    'status_code'      => null,
                    'response_snippet' => substr($e->getMessage(), 0, 300),
                    'ok'               => false,
                    'created_at'       => now(),
                ]);
            } catch (\Throwable) {
                // ثبت لاگ هم شکست — بی‌خیال
            }

            return false;
        }
    }
}
