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

            $result = self::perform($hook, $event, $payload);

            $hook->forceFill([
                'last_status_code' => $result['status'],
                'last_called_at'   => now(),
            ])->save();

            WebhookDelivery::create([
                'webhook_id'       => $hook->id,
                'event'            => $event,
                'status_code'      => $result['status'],
                'response_snippet' => $result['snippet'],
                'ok'               => $result['ok'],
                'created_at'       => now(),
            ]);

            return $result['ok'];
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

    /**
     * فاز ۶۰ — بازارسال دستی یک تحویل (از UI «تحویل‌های وب‌هوک» در کلیدهای وب‌سرویس).
     *
     * همان event همان ردیف delivery دوباره POST می‌شود و «دقیقاً همان ستون‌هایی
     * که send می‌نویسد» روی همان ردیف آپدیت می‌شود: status_code / response_snippet /
     * ok / created_at (+ last_status_code و last_called_at روی خود وب‌هوک).
     *
     * نکتهٔ شِما (فاز ۶۰): جدول webhook_deliveries ستون attempts/duration/payload
     * ندارد (طبق قید تسک، مایگریشن جدید اضافه نشد)؛ شمارهٔ تلاش به‌صورت نشانگر
     * ««تلاش N» » ابتدای response_snippet نگه‌داری می‌شود و مدل
     * (WebhookDelivery::attemptNumber / snippetText) آن را می‌خواند/می‌زداید.
     * payload اصلی هم ذخیره نشده است؛ بنابراین بازارسال با پاکت «replay» فرستاده
     * می‌شود تا گیرنده بداند این ارسال، تکرار دستی است.
     *
     * برخلاف dispatch()، سوییچ features.webhooks و متد listens() اینجا بررسی
     * نمی‌شوند — بازارسال، اقدام صریح ادمین روی یک ردیف مشخص است.
     *
     * @return array{ok: bool, status: ?int, attempt: int, message: string}
     */
    public static function retry(WebhookDelivery $delivery): array
    {
        $hook = $delivery->webhook;

        if (! $hook) {
            return [
                'ok'      => false,
                'status'  => null,
                'attempt' => $delivery->attemptNumber(),
                'message' => 'وب‌هوک این ردیف حذف شده و بازارسال ممکن نیست.',
            ];
        }

        $attempt = $delivery->attemptNumber() + 1;

        // پاکت بازپخش — payload اصلی در جدول ذخیره نمی‌شود (فاز ۵۱ فقط event را نگه می‌دارد)
        $result = self::perform($hook, $delivery->event, [
            'replayed'     => true,
            'delivery_id'  => $delivery->id,
            'original_event' => $delivery->event,
        ]);

        $marker = '«تلاش '.$attempt.'» ';
        $snippet = $marker.mb_substr((string) $result['snippet'], 0, max(0, 300 - mb_strlen($marker)));

        $delivery->forceFill([
            'status_code'      => $result['status'],
            'response_snippet' => $snippet,
            'ok'               => $result['ok'],
            'created_at'       => now(),
        ])->save();

        $hook->forceFill([
            'last_status_code' => $result['status'],
            'last_called_at'   => now(),
        ])->save();

        return [
            'ok'      => $result['ok'],
            'status'  => $result['status'],
            'attempt' => $attempt,
            'message' => $result['ok']
                ? 'ارسال مجدد موفق بود — کد پاسخ: '.$result['status']
                : 'ارسال مجدد ناموفق بود — '.($result['status'] !== null ? 'کد پاسخ: '.$result['status'] : 'بدون پاسخ (timeout/خطا)'),
        ];
    }

    /**
     * انجام خودِ درخواست HTTP (مشترک بین send و retry).
     *
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, status: ?int, snippet: string}
     */
    private static function perform(Webhook $hook, string $event, array $payload): array
    {
        try {
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

            return [
                'ok'      => $response->successful(),
                'status'  => $response->status(),
                'snippet' => (string) ($response->body() ?? ''),
            ];
        } catch (\Throwable $e) {
            return [
                'ok'      => false,
                'status'  => null,
                'snippet' => $e->getMessage(),
            ];
        }
    }
}
