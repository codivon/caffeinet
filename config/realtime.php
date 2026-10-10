<?php

/* ============================================================================
| فاز ۵۹-c — پیکربندی Realtime/SSE برای هاست اشتراکی (LiteSpeed/LSAPI)
|--------------------------------------------------------------------------
| هر اتصال SSE یک ورکر LSAPI را تا پایان اتصال اشغال می‌کند؛ سقف معمول
| هاست اشتراکی ~۲۰-۳۰ ورکر است. راه‌حل «کوتاه‌چرخه» (Short-Cycle):
| اتصال فقط به مدت TTL کوتاه باز می‌ماند، بعد با `event: close` تمیز
| بسته می‌شود و کلاینت (EventSource) خودش دوباره وصل می‌شود — ورکر مرتب
| آزاد می‌شود و اتصال‌های مرده/زامبی حداکثر بعد از یک TTL جمع می‌شوند.
|
| همهٔ مقادیر در SseService به بازهٔ ایمیل clamp می‌شوند؛ این فایل فقط
| پیش‌فرض .env را می‌خواند (بعد از تغییر .env: config:clear + config:cache).
|
|   REALTIME_SSE_TTL      طول هر چرخهٔ استریم (ثانیه) — پیش‌فرض ۲۵
|   REALTIME_HEARTBEAT    فاصلهٔ ضربان (ثانیه) — پیش‌فرض ۱۵
|   REALTIME_SSE_TICK     فاصلهٔ هر تیک بررسی رویداد (ثانیه) — پیش‌فرض ۱
|   REALTIME_SSE_RETRY    مقدار retry: برای EventSource (میلی‌ثانیه)
|   REALTIME_SSE_PAD      padding اولیه برای عبور از بافر LiteSpeed (بایت)
|   REALTIME_SSE_SWEEP    شبکهٔ ایمنی خواندن کامل دیتابیس (ثانیه)
*/

return [

    'sse' => [
        'ttl' => (int) env('REALTIME_SSE_TTL', 25),
        'heartbeat' => (int) env('REALTIME_HEARTBEAT', 15),
        'tick' => (int) env('REALTIME_SSE_TICK', 1),
        'retry_ms' => (int) env('REALTIME_SSE_RETRY', 2000),
        'pad_bytes' => (int) env('REALTIME_SSE_PAD', 2048),
        'sweep' => (int) env('REALTIME_SSE_SWEEP', 5),
    ],

];
