<?php

namespace App\Support;

use App\Services\Settings\SettingsService;

/**
 * v42 — پیکربندی فشرده‌سازی آپلودها (تنظیمات ← آپلود و فشرده‌سازی).
 *
 * سه حالت آماده + حالت سفارشی:
 *   • lossless  «بدون افت کیفیت» (پیش‌فرض) — فقط بازانکودینگ با کیفیت بالا؛
 *               حداکثر ضلع بزرگ؛ اگر خروجی کوچک‌تر نشد فایل اصلی می‌ماند.
 *   • balanced  «متعادل» — کیفیت ۸۵، حداکثر ضلع ۲۰۴۸.
 *   • max       «حداکثر فشرده‌سازی» — کیفیت ۷۸، حداکثر ضلع ۱۶۰۰.
 *   • custom    مقادیر دستی مدیر (max_side / quality / format).
 *
 * فشرده‌سازی تصاویر دو لایه دارد:
 *   ۱) سمت کاربر (CN.compressImage / App.compressImage) — قبل از ارسال؛
 *      یعنی حجم کمتر آپلود می‌شود و به سرور فشار نمی‌آید.
 *   ۲) سمت سرور (MediaCompressionService + دستور media:compress) —
 *      دستهٔ کوچک در هر اجرای زمان‌بندی‌شده (بدون queue worker) برای
 *      فایل‌هایی که از مسیرهای دیگر (API موبایل و…) آمده‌اند.
 *
 * ویدیو: به‌صورت پیش‌فرض خاموش؛ فقط اگر مدیر فعال کند و باینری ffmpeg
 * روی هاست موجود باشد، در هر اجرای cron حداکثر یک ویدیو تبدیل می‌شود.
 */
class UploadConfig
{
    /** کلیدهای تنظیمات این گروه */
    public const PRESETS = [
        'lossless' => ['max_side' => 4096, 'quality' => 92, 'format' => 'keep'],
        'balanced' => ['max_side' => 2048, 'quality' => 85, 'format' => 'auto'],
        'max'      => ['max_side' => 1600, 'quality' => 78, 'format' => 'auto'],
    ];

    /** پیکربندی مؤثر سمت سرور (از تنظیمات) */
    public static function server(): array
    {
        $s = app(SettingsService::class);

        $enabled = (bool) $s->get('uploads.compression.enabled', '1');
        $preset = (string) $s->get('uploads.compression.preset', 'lossless');
        $derived = self::PRESETS[$preset] ?? null;

        if ($derived !== null) {
            // حالت آماده — مقادیر از خود پریست (فیلدهای دستی فقط در «سفارشی» معنا دارند)
            $maxSide = $derived['max_side'];
            $quality = $derived['quality'];
            $format = $derived['format'];
        } else {
            $maxSide = max(400, min(8000, (int) $s->get('uploads.compression.images.max_side', '4096')));
            $quality = max(50, min(100, (int) $s->get('uploads.compression.images.quality', '92')));
            $format = (string) $s->get('uploads.compression.images.format', 'keep');
            if (! in_array($format, ['auto', 'keep', 'jpeg', 'webp'], true)) {
                $format = 'keep';
            }
        }

        return [
            'enabled' => $enabled,
            'preset' => in_array($preset, ['lossless', 'balanced', 'max', 'custom'], true) ? $preset : 'lossless',
            'max_side' => $maxSide,
            'quality' => $quality,
            'format' => $format,
            'videos' => [
                'enabled' => (bool) $s->get('uploads.compression.videos.enabled', '0'),
                'crf' => (int) $s->get('uploads.compression.videos.crf', '28'),
                'ffmpeg' => self::ffmpegPath(),
            ],
            'batch' => max(1, min(50, (int) $s->get('uploads.compression.server_batch', '8'))),
        ];
    }

    /** پیکربندی سمت کاربر (تزریق‌شده در layoutها — فشرده‌سازی قبل از آپلود) */
    public static function client(): array
    {
        $cfg = self::server();

        return [
            'enabled' => $cfg['enabled'],
            'max_side' => $cfg['max_side'],
            'quality' => $cfg['quality'],
            'format' => $cfg['format'],
        ];
    }

    /** مسیر باینری ffmpeg (اگر موجود باشد) — کش‌شده در حافظهٔ پروسه */
    public static function ffmpegPath(): ?string
    {
        static $cached = false;
        static $path = null;

        if ($cached) {
            return $path;
        }
        $cached = true;

        if (! function_exists('exec')) {
            return $path = null;
        }

        $out = @exec('command -v ffmpeg 2>/dev/null', $lines, $code);

        return $path = ($code === 0 && is_string($out) && $out !== '') ? trim($out) : null;
    }
}
