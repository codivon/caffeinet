<?php

namespace App\Services\Realtime;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * سرویس Realtime پوشر (فاز ۱۳) — در کنار سیستم پولینگ.
 *
 * بدون وابستگی SDK: مستقیم روی REST API رسمی Pusher با امضای
 * HMAC-SHA256 (auth_key / auth_timestamp / auth_version / body_md5) کار می‌کند.
 *
 * معماری «بیدارباش» (Wake-up):
 *  - رویداد Pusher فقط «اعلان وقوع» است؛ دادهٔ واقعی همیشه از API خود سیستم
 *    خوانده می‌شود (تک منبع حقیقت = دیتابیس).
 *  - فرانت با دریافت رویداد، همان لحظه پول را اجرا می‌کند و بازهٔ پولینگ
 *    را بزرگ می‌کند → هم آنی بودن، هم کاهش فشار دیتابیس (MySQL).
 *
 * امنیت بدون private-channel auth (ترفند):
 *  - نام کانال‌ها از هش SHA1 دامنه + شناسه + APP_KEY ساخته می‌شود؛
 *    نام کانال عمومی است اما غیرقابل حدس (۲۰ کاراکتر هگز).
 *  - کلاینت فقط همان کانالی را می‌شناسد که سرور برایش فرستاده است.
 */
class PusherService
{
    /** پیشوند کانال اعلان‌های کاربر */
    public const NOTIF_PREFIX = 'u';

    /** پیشوند کانال گفتگوی سفارش */
    public const CHAT_PREFIX = 'c';

    /** پیشوند کانال سراسری پنل‌ها (سفارش جدید/تغییر وضعیت → همهٔ پنل‌های پشتی) */
    public const PANEL_PREFIX = 'p';

    /** روش‌های مجاز ترابورت Realtime (فاز ۱۲ — انتخاب از تب Realtime تنظیمات) */
    public const METHODS = ['polling', 'sse', 'pusher'];

    public function __construct(
        protected \App\Services\Settings\SettingsService $settings,
        protected \App\Services\Realtime\SseService $sse,
    ) {}

    /* ================================================================== */
    /* ۱) وضعیت و پیکربندی                                                 */
    /* ================================================================== */

    /**
     * روش فعلی ترابورت (فاز ۱۲): polling | sse | pusher.
     * برای سازگاری با نصب‌های قبلی، اگر کلید جدید ست نباشد از
     * realtime.pusher.enabled استنتاج می‌شود.
     */
    public function method(): string
    {
        $method = (string) $this->settings->get('realtime.method', '');

        if (in_array($method, self::METHODS, true)) {
            return $method;
        }

        return (bool) $this->settings->get('realtime.pusher.enabled', false) ? 'pusher' : 'polling';
    }

    /** آیا Pusher فعال و پیکربندی‌شده است؟ (فقط وقتی روش انتخابی «پوشر» باشد) */
    public function pusherReady(): bool
    {
        return $this->method() === 'pusher'
            && trim((string) $this->settings->get('realtime.pusher.app_key', '')) !== ''
            && trim((string) $this->settings->get('realtime.pusher.app_secret', '')) !== ''
            && trim((string) $this->settings->get('realtime.pusher.app_id', '')) !== '';
    }

    /**
     * آیا «هر» ترابورت Realtime فعالی داریم؟ (درِ مشترک سرویس‌های دامنه)
     * متدهای دامنه (chatMessage/…) با هر روش فعال، هم پوشر و هم SSE را
     * تغذیه می‌کنند؛ صفحات از نظر RT دقیقاً یکسان کار می‌کنند.
     */
    public function enabled(): bool
    {
        return $this->sse->enabled() || $this->pusherReady();
    }

    /** اعتبارسنجی انتخاب روش (SettingsController) */
    public static function isValidMethod(string $method): bool
    {
        return in_array($method, self::METHODS, true);
    }

    /** پیکربندی عمومی برای تزریق در صفحه (بدون Secret) */
    public function clientConfig(?User $user = null): array
    {
        $method = $this->method();
        $active = $this->enabled();

        $cfg = [
            'enabled' => $active,
            // فاز ۱۲ — روش ترابورت؛ کلاینت realtime.js بر اساس آن تصمیم می‌گیرد
            'method' => $method,
            'key' => (string) $this->settings->get('realtime.pusher.app_key', ''),
            'cluster' => (string) $this->settings->get('realtime.pusher.cluster', 'mt1'),
            // فاز ۱۲ — هاست سفارشی (سوکتِ خودمیزبانِ سازگار با پروتکل پوشر مثل Soketi/Reverb)
            'host' => trim((string) $this->settings->get('realtime.pusher.host', '')) ?: null,
            'port' => (int) ($this->settings->get('realtime.pusher.port', '') ?: 0) ?: null,
            'scheme' => in_array((string) $this->settings->get('realtime.pusher.scheme', ''), ['https', 'http'], true)
                ? (string) $this->settings->get('realtime.pusher.scheme', '')
                : null,
            'sse_url' => $method === 'sse' ? url('/realtime/stream') : null,
            'channel' => null,
            // کانال سراسری پنل‌ها — رویدادهای «سفارش جدید/تغییر وضعیت» برای
            // به‌روزرسانی لحظه‌ای لیست‌ها/بج‌ها بدون پولینگ
            'panel_channel' => $active ? $this->panelChannel() : null,
        ];

        if ($user && $active) {
            $cfg['channel'] = $this->userChannel((int) $user->id);
        }

        return $cfg;
    }

    /* ================================================================== */
    /* ۲) نام کانال‌ها (غیرقابل حدس)                                        */
    /* ================================================================== */

    /** کانال اعلان‌های اختصاصی یک کاربر */
    public function userChannel(int $userId): string
    {
        return self::NOTIF_PREFIX.'.'.$this->hash('u', $userId);
    }

    /** کانال گفتگوی یک سفارش (مشتری + اپراتور + کافی‌نت + ادمین) */
    public function chatChannel(int $orderId): string
    {
        return self::CHAT_PREFIX.'.'.$this->hash('c', $orderId);
    }

    /** کانال سراسری پنل‌ها — رویدادهای سفارش برای همهٔ پنل‌های پشتی */
    public function panelChannel(): string
    {
        return self::PANEL_PREFIX.'.'.$this->hash('p', 0);
    }

    /** هش غیرقابل حدس از دامنه + شناسه + APP_KEY */
    protected function hash(string $domain, int $id): string
    {
        return substr(sha1($domain.'|'.$id.'|'.config('app.key')), 0, 20);
    }

    /* ================================================================== */
    /* ۳) ارسال رویداد                                                     */
    /* ================================================================== */

    /**
     * ارسال رویداد به یک یا چند کانال (batch) — به ترابورتِ «فعال».
     * هرگز استثنا بالا نمی‌برد — Realtime اختیاری است و جریان اصلی نباید متوقف شود.
     *
     * @param  string|array  $channels
     */
    public function trigger(string|array $channels, string $event, array $data = []): bool
    {
        $channels = array_values((array) $channels);

        if (! $channels) {
            return false;
        }

        $sent = false;

        // فاز ۱۲ — SSE: درج در جدول رویدادها (جریان شبه‌سوکت همان دامنه)
        if ($this->sse->enabled()) {
            foreach ($channels as $channel) {
                // کانال‌های «سراسری» پنل‌ها (پیشوند p.) برای همهٔ پنل‌های پشتی
                if (str_starts_with((string) $channel, self::PANEL_PREFIX.'.')) {
                    $this->sse->emitForStaff((string) $channel, $event, $data);
                }
                // بقیهٔ کانال‌ها در متدهای دامنه با گیرندهٔ دقیق درج می‌شوند
                // (اینجا گیرندهٔ تک‌کاربر معلوم نیست)
            }

            $sent = true;
        }

        if (! $this->pusherReady()) {
            return $sent;
        }

        // Pusher در هر درخواست حداکثر ۱۰۰ کانال
        foreach (array_chunk($channels, 100) as $batch) {
            $sent = $this->postEvent($batch, $event, $data) || $sent;
        }

        return $sent;
    }

    /** ارسال HTTP امضاشده — خطا فقط لاگ می‌شود */
    protected function postEvent(array $channels, string $event, array $data): bool
    {
        try {
            $appId = trim((string) $this->settings->get('realtime.pusher.app_id', ''));
            $key = trim((string) $this->settings->get('realtime.pusher.app_key', ''));
            $secret = trim((string) $this->settings->get('realtime.pusher.app_secret', ''));
            $cluster = trim((string) $this->settings->get('realtime.pusher.cluster', 'mt1')) ?: 'mt1';

            $path = "/apps/{$appId}/events";
            $body = json_encode([
                'name' => $event,
                'channels' => $channels,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ], JSON_UNESCAPED_UNICODE);

            $params = [
                'auth_key' => $key,
                'auth_timestamp' => (string) time(),
                'auth_version' => '1.0',
                'body_md5' => md5((string) $body),
            ];
            ksort($params);
            $query = http_build_query($params);

            $signature = hash_hmac('sha256', "POST\n{$path}\n{$query}", $secret);

            $url = $this->restBaseUrl($cluster).$path.'?'.$query.'&auth_signature='.$signature;

            $response = Http::timeout(5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withBody((string) $body, 'application/json')
                ->post($url);

            if (! $response->successful()) {
                Log::warning('Pusher trigger failed', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 300),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Pusher trigger error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * فاز ۱۲ — آدرس پایهٔ REST پوشر: به‌طور پیش‌فرض کلودِ pusher.com؛
     * با هاست سفارشی، سرورِ سازگار با پروتکل پوشر (Soketi/Reverb روی VPS).
     */
    protected function restBaseUrl(string $cluster): string
    {
        $host = trim((string) $this->settings->get('realtime.pusher.host', ''));

        if ($host === '') {
            $host = str_starts_with($cluster, 'api-') ? $cluster : "api-{$cluster}";

            return "https://{$host}.pusher.com";
        }

        $port = (int) ($this->settings->get('realtime.pusher.port', '') ?: 0);
        $scheme = in_array((string) $this->settings->get('realtime.pusher.scheme', ''), ['https', 'http'], true)
            ? (string) $this->settings->get('realtime.pusher.scheme', '')
            : 'https';

        $suffix = ($port > 0 && ! in_array([$scheme, $port], [['https', 443], ['http', 80]], true)) ? ":{$port}" : '';

        return "{$scheme}://{$host}{$suffix}";
    }

    /* ================================================================== */
    /* ۴) رویدادهای دامنه                                                  */
    /* ================================================================== */

    /** رویداد پیام جدید چت → بیدارباش لحظه‌ای طرفِ مقابل */
    public function chatMessage(Order $order, ?int $messageId = null, ?int $senderId = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        $payload = [
            'order' => (int) $order->id,
            'id' => $messageId,
            'sender_id' => $senderId,
        ];

        $channel = $this->chatChannel((int) $order->id);

        // فاز ۱۲ — SSE: گیرنده‌های دقیق گفتگو (مشتری + اپراتور) + پنل‌های پشتی
        if ($this->sse->enabled()) {
            if (! empty($order->customer_id)) {
                $this->sse->emitToUser((int) $order->customer_id, $channel, 'message.new', $payload);
            }

            if (! empty($order->operator_id)) {
                $this->sse->emitToUser((int) $order->operator_id, $channel, 'message.new', $payload);
            }

            $this->sse->emitForStaff($channel, 'message.new', $payload);
        }

        if ($this->pusherReady()) {
            $this->trigger($channel, 'message.new', $payload);
        }
    }

    /** رویداد اعلان جدید برای کاربران */
    public function notifyUsers(array $userIds, string $type = 'system'): void
    {
        if (! $this->enabled() || ! $userIds) {
            return;
        }

        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        // فاز ۱۲ — SSE: یک ردیف شخصی برای هر گیرنده
        if ($this->sse->enabled()) {
            foreach ($userIds as $uid) {
                $this->sse->emitToUser($uid, $this->userChannel($uid), 'notif.new', ['type' => $type]);
            }
        }

        if ($this->pusherReady()) {
            $channels = array_map(fn ($id) => $this->userChannel((int) $id), $userIds);

            $this->trigger($channels, 'notif.new', ['type' => $type]);
        }
    }

    /**
     * رویداد «سفارش جدید/تغییر وضعیت» روی کانال سراسری پنل‌ها —
     * لیست‌ها/بج‌های پنل‌های پشتی (ادمین/کافی‌نت/اپراتور) بدون پولینگ لحظه‌ای تازه می‌شوند.
     */
    public function ordersChanged(array $payload = []): void
    {
        if (! $this->enabled()) {
            return;
        }

        if ($this->sse->enabled()) {
            $this->sse->emitForStaff($this->panelChannel(), 'orders.changed', $payload);
        }

        if ($this->pusherReady()) {
            $this->trigger($this->panelChannel(), 'orders.changed', $payload);
        }
    }

    /** رویداد «سفارش من تغییر کرد» روی کانال شخصی مشتری — صفحهٔ پیگیری سفارش بدون پولینگ */
    public function orderChangedForUser(int $userId, array $payload = []): void
    {
        if (! $this->enabled()) {
            return;
        }

        if ($this->sse->enabled()) {
            $this->sse->emitToUser($userId, $this->userChannel($userId), 'order.changed', $payload);
        }

        if ($this->pusherReady()) {
            $this->trigger($this->userChannel($userId), 'order.changed', $payload);
        }
    }

    /* ================================================================== */
    /* ۵) تست اتصال (پنل تنظیمات)                                          */
    /* ================================================================== */

    /**
     * تست اعتبارسنجی اعتبارنامه‌های Pusher با فراخوانی GET /channels.
     *
     * @return array{ok: bool, message: string}
     */
    public function test(): array
    {
        if (! $this->pusherReady()) {
            return ['ok' => false, 'message' => 'پوشر در تنظیمات فعال نیست یا کلیدها ناقص‌اند (روش انتخابی باید «سوکت خارجی پوشر» باشد).'];
        }

        try {
            $appId = trim((string) $this->settings->get('realtime.pusher.app_id', ''));
            $key = trim((string) $this->settings->get('realtime.pusher.app_key', ''));
            $secret = trim((string) $this->settings->get('realtime.pusher.app_secret', ''));
            $cluster = trim((string) $this->settings->get('realtime.pusher.cluster', 'mt1')) ?: 'mt1';

            $path = "/apps/{$appId}/channels";
            $params = [
                'auth_key' => $key,
                'auth_timestamp' => (string) time(),
                'auth_version' => '1.0',
            ];
            ksort($params);
            $query = http_build_query($params);

            $signature = hash_hmac('sha256', "GET\n{$path}\n{$query}", $secret);

            $url = $this->restBaseUrl($cluster).$path.'?'.$query.'&auth_signature='.$signature;

            $response = Http::timeout(8)->get($url);

            if ($response->status() === 401 || $response->status() === 403) {
                return ['ok' => false, 'message' => 'اعتبارنامه‌های پوشر رد شد (۴۰۱/۴۰۳) — App Key/Secret/ID را بررسی کنید.'];
            }

            if ($response->status() === 404) {
                return ['ok' => false, 'message' => 'App ID یا Cluster اشتباه است (۴۰۴).'];
            }

            if (! $response->successful()) {
                return ['ok' => false, 'message' => 'پاسخ غیرمنتظره از پوشر (HTTP '.$response->status().').'];
            }

            $channels = (array) (json_decode((string) $response->body(), true)['channels'] ?? []);

            return [
                'ok' => true,
                'message' => 'اتصال به پوشر برقرار است ✓ — '.count($channels).' کانال فعال',
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'خطای اتصال: '.$e->getMessage()];
        }
    }

    /** ارسال رویداد آزمایشی روی کانال تست */
    public function sendTestEvent(): array
    {
        $ok = $this->trigger('test', 'test.ping', ['at' => now()->toIso8601String()]);

        return $ok
            ? ['ok' => true, 'message' => 'رویداد آزمایشی با موفقیت ارسال شد ✓']
            : ['ok' => false, 'message' => 'ارسال رویداد ناموفق بود — لاگ را بررسی کنید.'];
    }

    /** فاز ۱۲ — تست روش فعلی: SSE → تست جدول رویدادها؛ غیر SSE → تست REST پوشر */
    public function testActiveTransport(): array
    {
        return $this->sse->enabled()
            ? $this->sse->test()
            : $this->test();
    }
}
