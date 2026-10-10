<?php

namespace App\Services\Realtime;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * فاز ۱۲ — ترابورت SSE (Server-Sent Events) برای Realtime — «اتصال دائمی داخلی».
 *
 * چرا؟ پوشر از ایران معمولاً بلاک/تحریم است و روی هاست اشتراکی هم نمی‌توان
 * وب‌سوکت مستقل بالا آورد. SSE جریانِ یک‌طرفهٔ همان دامنه است (مثل سوکت،
 * بدون درخواست دوره‌ای) که روی هر هاستی با هر مرورگری کار می‌کند.
 *
 * فاز ۵۹-c — سخت‌سازی برای هاست اشتراکی (LiteSpeed/LSAPI، سقف ~۲۰-۳۰ ورکر):
 *
 *   ۱) کوتاه‌چرخه (Short-Cycle): هر استریم فقط به مدت TTL (پیش‌فرض ۲۵ ثانیه،
 *      ‎REALTIME_SSE_TTL‎) باز می‌ماند؛ بعد با `event: close` تمیز بسته می‌شود
 *      و EventSource خودش دوباره وصل می‌شود. ورکر مرتب آزاد می‌شود و اتصال
 *      مرده/زامبی (بستن تب بدون close، کشتن‌شدن توسط پراکسی) حداکثر بعد از
 *      یک TTL جمع می‌شود — قبلاً ۲۴۰ ثانیه بود.
 *
 *   ۲) ضربان هر ‎REALTIME_HEARTBEAT‎ ثانیه (پیش‌فرض ۱۵) — هم زنده‌نگه‌داشتن
 *      اتصال پشت پراکسی‌ها، هم تشخیص قطعی: اولین نوشتنِ ناموفق، PHP را
 *      (با ignore_user_abort(false)) بلافاصله خاتمه می‌دهد → ورکر آزاد.
 *
 *   ۳) تیک سبک: در حالت عادی هر تیک فقط یک شمارندهٔ کش (‎CACHE_STORE‎ جاری)
 *      می‌خواند؛ کوئری realtime_events فقط وقتی شمارنده تغییر کرده باشد یا
 *      هر ‎REALTIME_SSE_SWEEP‎ ثانیه (شبکهٔ ایمنی) زده می‌شود — یعنی روی
 *      CACHE_STORE=file، تیک‌های بی‌رویداد «صفر» کوئری دیتابیس دارند.
 *
 *   ۴) replay با Last-Event-ID: هر رویداد `id:` دارد؛ کلاینت هنگام اتصال
 *      مجدد آن را برمی‌گرداند (هدر Last-Event-ID یا ‎?last_id=‎) تا رویدادهای
 *      فاصلهٔ reconnect (چند ثانیهٔ بین دو چرخه) از دست نروند.
 *
 *   ۵) هدرها + padding اولیه (~۲KB) برای عبور از بافر LiteSpeed.
 *
 * هزینه: هر اتصال یک پروسهٔ PHP اشغال می‌کند (روی هاست اشتراکی محدودیت
 * Entry Processes را در نظر بگیرید — در تب Realtime تنظیمات هشدار داده
 * شده است). با TTL=۲۵ ثانیه ظرفیت پایدار ≈ تعداد ورکرها؛ بالاتر از آن
 * «پولینگ» یا «پوشر» (سوکت خودمیزبان/VPS) انتخاب شود — جزئیات در DEPLOYMENT.md.
 */
class SseService
{
    /** کانال‌های رویداد بیش از این عمر سرو نمی‌شوند */
    public const RETENTION_SECONDS = 600;

    /** سقف مطلق TTL کانفیگ (محافظ در برابر مقدار اشتباه .env) */
    public const MAX_TTL_SECONDS = 3600;

    /** کلید شمارندهٔ کش برای بیدارکردن سبک استریم‌ها */
    protected const SEQ_KEY = 'rt:seq';

    public function __construct(
        protected \App\Services\Settings\SettingsService $settings,
    ) {}

    /** آیا روش Realtime فعلی «SSE» است؟ */
    public function enabled(): bool
    {
        return $this->settings->get('realtime.method', 'polling') === 'sse';
    }

    /* ================================================================== */
    /* ۰) پیکربندی کوتاه‌چرخه (فاز ۵۹-c)                                    */
    /* ================================================================== */

    /** طول هر چرخهٔ استریم (ثانیه) — پیش‌فرض ۲۵ */
    public function ttl(): int
    {
        return max(8, min(self::MAX_TTL_SECONDS, (int) config('realtime.sse.ttl', 25)));
    }

    /** فاصلهٔ ضربان (ثانیه) — پیش‌فرض ۱۵ */
    public function heartbeat(): int
    {
        return max(5, min(60, (int) config('realtime.sse.heartbeat', 15)));
    }

    /** فاصلهٔ هر تیک بررسی رویداد (ثانیه) */
    public function tick(): int
    {
        return max(1, min(5, (int) config('realtime.sse.tick', 1)));
    }

    /** مقدار retry: برای EventSource (میلی‌ثانیه) */
    public function retryMs(): int
    {
        return max(500, min(30000, (int) config('realtime.sse.retry_ms', 2000)));
    }

    /** padding اولیه برای عبور از بافر LiteSpeed (بایت، ۰ = خاموش) */
    public function padBytes(): int
    {
        return max(0, min(8192, (int) config('realtime.sse.pad_bytes', 2048)));
    }

    /** شبکهٔ ایمنی: هر چند ثانیه یک‌بار خواندن کامل دیتابیس */
    public function sweep(): int
    {
        return max(2, min(60, (int) config('realtime.sse.sweep', 5)));
    }

    /* ================================================================== */
    /* ۱) درج رویداد (سمت منتشرکننده)                                      */
    /* ================================================================== */

    /**
     * رویداد شخصی برای یک کاربر (همان نام کانال پوشر مسیریابی می‌شود).
     * هرگز استثنا بالا نمی‌برد — Realtime اختیاری است.
     */
    public function emitToUser(int $userId, string $channel, string $event, array $data = []): void
    {
        if (! $this->enabled() || $userId <= 0) {
            return;
        }

        $this->insert($userId, false, $channel, $event, $data);
    }

    /** رویداد سراسری برای همهٔ پنل‌های پشتی (ادمین/سازمان/کافی‌نت/اپراتور) */
    public function emitForStaff(string $channel, string $event, array $data = []): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->insert(null, true, $channel, $event, $data);
    }

    protected function insert(?int $userId, bool $forStaff, string $channel, string $event, array $data): void
    {
        try {
            DB::table('realtime_events')->insert([
                'user_id' => $userId,
                'for_staff' => $forStaff,
                'channel' => mb_substr($channel, 0, 64),
                'event' => mb_substr($event, 0, 64),
                'payload' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // فاز ۵۹-c — بیدارباش سبک استریم‌ها: شمارندهٔ کش یک واحد جلو می‌رود
            // تا تیک‌های استریم بدون کوئری دیتابیس بفهمند «رویداد تازه هست».
            $this->bumpSequence();

            // پاکسازی کهنه‌ها — ۵٪ درصدها تا فشار دیتابیس کم بماند
            if (random_int(1, 20) === 1) {
                DB::table('realtime_events')
                    ->where('created_at', '<', now()->subSeconds(self::RETENTION_SECONDS))
                    ->delete();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** افزایش شمارندهٔ کش (هرگز نمی‌شکند — اگر کش مرد، شبکهٔ ایمنی sweep جبران می‌کند) */
    protected function bumpSequence(): void
    {
        try {
            Cache::add(self::SEQ_KEY, 0, 86400);
            Cache::increment(self::SEQ_KEY);
        } catch (Throwable) {
            // کش اختیاری است — sweep دوره‌ای خودش رویدادها را پیدا می‌کند
        }
    }

    /** خواندن شمارندهٔ کش (null = کش در دسترس نیست → تیک، همیشه «کثیف» فرض شود) */
    protected function currentSequence(): ?int
    {
        try {
            $v = Cache::get(self::SEQ_KEY);

            return $v === null ? null : (int) $v;
        } catch (Throwable) {
            return null;
        }
    }

    /* ================================================================== */
    /* ۲) استریم (سمت گیرنده)                                              */
    /* ================================================================== */

    /** نقش‌های پنل‌های پشتی — گیرندهٔ رویدادهای for_staff */
    public const STAFF_ROLES = ['super_admin', 'admin', 'org_manager', 'coffeenet_manager', 'operator'];

    public function isStaff(User $user): bool
    {
        try {
            return $user->hasAnyRole(self::STAFF_ROLES);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * پاسخ استریم SSE برای کاربر احرازشده.
     * هر رویداد: id: <rowid> + event: rt + data: {channel, event, payload}
     * کلاینت (realtime.js) بر اساس channel/event به صفحات مسیریابی می‌کند.
     *
     * فاز ۵۹-c: ‎$lastEventId‎ (از هدر Last-Event-ID یا ‎?last_id=‎) برای
     * پخش دوبارهٔ رویدادهای فاصلهٔ reconnect — عدم از دست رفتن رویداد.
     */
    public function streamResponse(User $user, ?int $lastEventId = null): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $userId = (int) $user->id;
        $isStaff = $this->isStaff($user);
        $startedAt = time();

        $ttl = $this->ttl();
        $heartbeat = $this->heartbeat();
        $tick = $this->tick();
        $retryMs = $this->retryMs();
        $pad = $this->padBytes();
        $sweepEvery = $this->sweep();

        return response()->stream(function () use ($userId, $isStaff, $lastEventId, $startedAt, $ttl, $heartbeat, $tick, $retryMs, $pad, $sweepEvery) {
            // اگر سوکت کلاینت مرده باشد، اولین نوشتنِ ناموفق PHP را همان لحظه
            // خاتمه می‌دهد → ورکر LSAPI فوراً آزاد می‌شود (هیچ قفلی نگه نداریم —
            // نشست در کنترلر قبل از استریم ذخیره/رها شده است).
            if (\function_exists('ignore_user_abort')) {
                @ignore_user_abort(false);
            }

            // بافرها ببندند تا استریم همان لحظه بیرون برود
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            // عبور از بافر LiteSpeed/پراکسی‌ها: یک کامنت SSE ~۲KB (سایز قابل کانفیگ)
            if ($pad > 0) {
                echo ': '.str_repeat('.', $pad)."\n\n";
            }

            // فاصلهٔ اتصال مجدد مرورگر بعد از هر پایان (کوتاه‌چرخه = چند ثانیه)
            echo 'retry: '.$retryMs."\n\n";

            echo ": sse-connected\n\n";

            // ضربان فوری — اتصال «گرم» تحویل کلاینت شود
            $this->write('ping', ['at' => now()->toIso8601String()]);

            if (\function_exists('flush')) {
                flush();
            }

            /* نقطهٔ شروع: رویدادهای بعد از آخرین رویدادِ تحویل‌شدهٔ کلاینت.
               بدون lastId → فقط رویدادهای جدید (رفتار فاز ۱۲).
               با lastId معتبر → replay رویدادهای فاصلهٔ reconnect (سقف ۵۰۰۰). */
            $maxId = (int) DB::table('realtime_events')->max('id');
            $lastId = $maxId;

            if ($lastEventId !== null && $lastEventId > 0) {
                $lastId = min($lastEventId, $maxId);
                $lastId = max($lastId, max(0, $maxId - 5000));
            }

            $lastSeq = $this->currentSequence();
            $lastSweep = time();
            $lastBeat = time();
            $lastErrReport = 0;

            while (true) {
                if (connection_aborted()) {
                    return;
                }

                try {
                    // تیک سبک: خواندن دیتابیس فقط وقتی شمارندهٔ کش تغییر کرده
                    // یا شبکهٔ ایمنی (هر ‎sweepEvery‎ ثانیه) وقتش رسیده باشد.
                    $sweep = (time() - $lastSweep) >= $sweepEvery;
                    $seq = $this->currentSequence();
                    $dirty = ($seq === null) || ($lastSeq === null) || ($seq !== $lastSeq) || $sweep;

                    if ($sweep) {
                        $lastSweep = time();
                    }

                    if ($dirty) {
                        $rows = DB::table('realtime_events')
                            ->where('id', '>', $lastId)
                            ->where(function ($q) use ($userId, $isStaff) {
                                $q->where('user_id', $userId);
                                if ($isStaff) {
                                    $q->orWhere('for_staff', true);
                                }
                            })
                            ->orderBy('id')
                            ->limit(200)
                            ->get(['id', 'channel', 'event', 'payload']);

                        foreach ($rows as $row) {
                            $lastId = max($lastId, (int) $row->id);

                            $payload = json_decode((string) $row->payload, true);

                            $this->write('rt', [
                                'channel' => (string) $row->channel,
                                'event' => (string) $row->event,
                                'payload' => is_array($payload) ? $payload : [],
                            ], (int) $row->id);
                        }

                        if ($seq !== null) {
                            $lastSeq = $seq;
                        }
                    }

                    // ضربان دوره‌ای — پراکسی‌ها اتصال را نبندند + تشخیص قطعی
                    if (time() - $lastBeat >= $heartbeat) {
                        $lastBeat = time();
                        $this->write('ping', ['at' => now()->toIso8601String()]);
                    }
                } catch (Throwable $e) {
                    // هرگز استریم را با استثنا نکش — گزارش حداکثر هر ۳۰ ثانیه
                    if (time() - $lastErrReport >= 30) {
                        $lastErrReport = time();
                        report($e);
                    }
                }

                if (\function_exists('flush')) {
                    flush();
                }

                /* کوتاه‌چرخه (فاز ۵۹-c): پایان تمیز بعد از TTL — ورکر آزاد می‌شود،
                   EventSource خودش (طبق retry: بالا) دوباره وصل می‌شود. */
                if (time() - $startedAt >= $ttl) {
                    $this->write('close', ['reason' => 'ttl', 'retry' => $retryMs]);
                    if (\function_exists('flush')) {
                        flush();
                    }

                    return;
                }

                sleep($tick);
            }
        }, 200, $this->headers());
    }

    /** هدرهای استاندارد استریم — ضدکش + ضدبافر (Nginx + LiteSpeed) */
    protected function headers(): array
    {
        return [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'X-LiteSpeed-Cache-Control' => 'no-cache',
            'Content-Encoding' => 'none',
            'Connection' => 'close',
        ];
    }

    protected function write(string $eventName, array $data, ?int $id = null): void
    {
        if ($id !== null) {
            echo 'id: '.$id."\n";
        }

        echo 'event: '.$eventName."\n";
        echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
    }

    /* ================================================================== */
    /* ۳) تست اتصال (پنل تنظیمات)                                          */
    /* ================================================================== */

    /** تست سلامت: درج رویداد آزمایشی + خواندنش (تأیید جدول/دیتابیس) */
    public function test(): array
    {
        if (! $this->enabled()) {
            return ['ok' => false, 'message' => 'روش Realtime روی «اتصال دائمی داخلی (SSE)» نیست.'];
        }

        try {
            $probe = 'probe.'.bin2hex(random_bytes(4));

            $this->emitForStaff('test', 'sse.probe', ['probe' => $probe]);

            $found = DB::table('realtime_events')
                ->where('channel', 'test')
                ->where('event', 'sse.probe')
                ->where('created_at', '>=', now()->subSeconds(10))
                ->exists();

            if (! $found) {
                return ['ok' => false, 'message' => 'درج/خواندن رویداد در جدول realtime_events تأیید نشد — مهاجرت‌ها را اجرا کنید.'];
            }

            return ['ok' => true, 'message' => 'اتصال دائمی داخلی (SSE) سالم است ✓ — جدول رویدادها در دسترس است.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'خطای تست SSE: '.$e->getMessage()];
        }
    }
}
