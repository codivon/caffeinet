<?php

namespace App\Services\Realtime;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * فاز ۱۲ — ترابورت SSE (Server-Sent Events) برای Realtime — «اتصال دائمی داخلی».
 *
 * چرا؟ پوشر از ایران معمولاً بلاک/تحریم است و روی هاست اشتراکی هم نمی‌توان
 * وب‌سوکت مستقل بالا آورد. SSE جریانِ یک‌طرفهٔ همان دامنه است (مثل سوکت،
 * بدون درخواست دوره‌ای) که روی هر هاستی با هر مرورگری کار می‌کند:
 *
 *   • سمت سرور: یک endpoint همان دامنه (‎/realtime/stream) که با هر ثانیه
 *     یک پرس‌وجوی سبک به جدول realtime_events می‌زند و رویدادهای جدید را
 *     همان لحظه استریم می‌کند. حداکثر ~۴ دقیقه، بعد «bye» تمیز و
 *     EventSource خودش دوباره وصل می‌شود (بدون هیچ مداخلهٔ صفحات).
 *
 *   • سمت کلاینت: realtime.js با EventSource گوش می‌دهد؛ از نظر صفحات
 *     دقیقاً مثل پوشر است (RT.on / RT.connected / RT.onConnection) —
 *     یعنی «پوشر کامل» بدون پولینگ، این بار بدون هیچ سرویس بیرونی.
 *
 * هزینه: هر اتصال یک پروسهٔ PHP اشغال می‌کند (روی هاست اشتراکی محدودیت
 * Entry Processes را در نظر بگیرید — در تب Realtime تنظیمات هشدار داده
 * شده است). پرس‌وجوی هر ثانیه روی جدول کوچکِ ایندکس‌شده سبک است.
 */
class SseService
{
    /** کانال‌های رویداد بیش از این عمر سرو نمی‌شوند */
    public const RETENTION_SECONDS = 600;

    /** حداکثر عمر یک استریم (بعد از آن پایان تمیز + اتصال مجدد بی‌صدا) */
    public const MAX_STREAM_SECONDS = 240;

    public function __construct(
        protected \App\Services\Settings\SettingsService $settings,
    ) {}

    /** آیا روش Realtime فعلی «SSE» است؟ */
    public function enabled(): bool
    {
        return $this->settings->get('realtime.method', 'polling') === 'sse';
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
     * هر رویداد: event: rt — data: {channel, event, payload}
     * کلاینت (realtime.js) بر اساس channel/event به صفحات مسیریابی می‌کند.
     */
    public function streamResponse(User $user): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $userId = (int) $user->id;
        $isStaff = $this->isStaff($user);
        $startedAt = time();
        $lastBeat = 0;

        return response()->stream(function () use ($userId, $isStaff, $startedAt, &$lastBeat) {
            // بافرها ببندند تا استریم همان لحظه بیرون برود
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            echo ": sse-connected\n\n";
            if (\function_exists('flush')) {
                flush();
            }

            $lastId = (int) DB::table('realtime_events')->max('id'); // فقط رویدادهای جدید

            while (true) {
                if (connection_aborted()) {
                    return;
                }

                try {
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
                        ]);
                    }

                    // ضربان هر ۱۵ ثانیه — پراکسی‌ها اتصال را نبندند
                    if (time() - $lastBeat >= 15) {
                        $lastBeat = time();
                        $this->write('ping', ['at' => now()->toIso8601String()]);
                    }
                } catch (Throwable $e) {
                    // هرگز استریم را با استثنا نکش
                    report($e);
                }

                if (\function_exists('flush')) {
                    flush();
                }

                if (time() - $startedAt >= self::MAX_STREAM_SECONDS) {
                    $this->write('bye', []);
                    if (\function_exists('flush')) {
                        flush();
                    }

                    return; // EventSource خودش دوباره وصل می‌شود
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Content-Encoding' => 'none',
        ]);
    }

    protected function write(string $eventName, array $data): void
    {
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
