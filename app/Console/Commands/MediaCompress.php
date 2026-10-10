<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use App\Services\Media\CompressionService;
use App\Support\UploadConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * v42 — فشرده‌سازی تدریجی فایل‌های آپلودشده (سرور-پسند).
 *
 * زمان‌بندی: هر دقیقه (schedule:run کرون هاست) — در هر اجرا فقط
 * «batch» تصویر یا «یک» ویدیو پردازش می‌شود؛ یعنی حتی با هزاران فایل
 * منتظر، فشار لحظه‌ای روی CPU هاست ناچیز می‌ماند.
 *
 * کشف فایل‌ها: پوشه‌های آپلود شناخته‌شده روی دو دیسک:
 *   • local (رمزنگاری‌شدهٔ SecureFile): chat / orders / tickets
 *   • public: services
 * هر فایل با «disk:path» یکتا در جدول media_files ثبت می‌شود؛
 * پردازش‌شده‌ها دیگر بررسی نمی‌شوند. فایل‌های جدیدتر از ۷ روز اسکن می‌شوند.
 *
 * اجرای دستی:  php artisan media:compress --limit=50
 */
class MediaCompress extends Command
{
    protected $signature = 'media:compress {--limit= : حداکثر تعداد پردازش در این اجرا}';

    protected $description = 'فشرده‌سازی تدریجی آپلودها طبق تنظیمات (تصویر + ویدیو اختیاری)';

    /** ریشه‌های اسکن به تفکیک دیسک — v43: اطلاعیه‌ها هم اضافه شد */
    protected const SCAN_ROOTS = [
        'local' => ['chat', 'orders', 'tickets'],
        'public' => ['services', 'announcements'],
    ];

    /** پسوندهای تصویر/ویدیو */
    protected const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    protected const VIDEO_EXT = ['mp4', 'webm', 'mov', 'm4v', 'mkv'];

    public function handle(CompressionService $service): int
    {
        $cfg = UploadConfig::server();

        if (! $cfg['enabled']) {
            $this->info('compression disabled');

            return self::SUCCESS;
        }

        $batch = min(50, max(1, (int) ($this->option('limit') ?: $cfg['batch'])));
        $cutoff = now()->subDays(7)->getTimestamp();

        $discovered = $this->discoverNewFiles($cutoff);

        if ($discovered > 0) {
            $this->line("discovered {$discovered} new file(s)");
        }

        // ۱) ویدیو — حداکثر «یک» عدد در هر اجرا (سنگین‌ترین کار)
        if ($cfg['videos']['enabled']) {
            $video = MediaFile::query()
                ->where('type', 'video')
                ->where('status', 'pending')
                ->orderBy('id')
                ->first();

            if ($video) {
                $service->process($video);
                $this->line("video #{$video->id}: {$video->status}");
            }
        }

        // ۲) تصاویر — تا سقف batch
        $images = MediaFile::query()
            ->where('type', 'image')
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($batch)
            ->get();

        foreach ($images as $media) {
            $service->process($media);
            $this->line("image #{$media->id}: {$media->status} {$media->note}");
        }

        if ($images->isEmpty() && ! isset($video)) {
            $this->line('nothing to process');
        }

        return self::SUCCESS;
    }

    /** یافتن فایل‌های تازهٔ آپلودشده و ثبت آن‌ها در دفتر */
    protected function discoverNewFiles(int $cutoffTimestamp): int
    {
        $added = 0;

        foreach (self::SCAN_ROOTS as $diskName => $roots) {
            $disk = Storage::disk($diskName);

            foreach ($roots as $root) {
                if (! $disk->exists($root)) {
                    continue;
                }

                foreach ($disk->allFiles($root) as $path) {
                    // فایل‌های موقت خود سرویس + پسوندهای ناشناخته
                    if (str_contains($path, '.cn-tmp') || str_contains($path, '-tmp')) {
                        continue;
                    }

                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                    $type = in_array($ext, self::IMAGE_EXT, true) ? 'image'
                        : (in_array($ext, self::VIDEO_EXT, true) ? 'video' : null);

                    if ($type === null) {
                        continue;
                    }

                    try {
                        if ($disk->lastModified($path) < $cutoffTimestamp) {
                            continue; // فایل‌های قدیمی — قبل از راه‌اندازی این سیستم بوده‌اند
                        }
                    } catch (\Throwable) {
                        continue;
                    }

                    $hash = sha1($diskName.':'.$path);

                    $exists = MediaFile::query()->where('path_hash', $hash)->exists();

                    if ($exists) {
                        continue;
                    }

                    MediaFile::query()->create([
                        'disk' => $diskName,
                        'path_hash' => $hash,
                        'path' => $path,
                        'type' => $type,
                        'original_size' => (int) $disk->size($path),
                        'status' => 'pending',
                    ]);

                    $added++;
                }
            }
        }

        return $added;
    }
}
