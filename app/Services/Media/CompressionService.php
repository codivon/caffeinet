<?php

namespace App\Services\Media;

use App\Models\MediaFile;
use App\Support\SecureFile;
use App\Support\UploadConfig;
use Illuminate\Support\Facades\Storage;

/**
 * v42 — فشرده‌سازی سمت سرور (بدون فشار به سرور).
 *
 * اصول طراحی:
 *   • process() فقط «یک فایل» را پردازش می‌کند؛ اجرای منظم (cron هر دقیقه)
 *     در هر بار حداکثر «batch» تصویر یا «یک» ویدیو برمی‌دارد — مصرف CPU
 *     محدود و قابل پیش‌بینی می‌ماند (مناسب هاست اشتراکی).
 *   • «بدون افت کیفیت» پیش‌فرض: بازانکودینگ با کیفیت بالا + حذف EXIF؛
 *     اگر خروجی کوچک‌تر نشد، فایل اصلی دست‌نخورده می‌ماند.
 *   • فایل‌های رمزنگاری‌شدهٔ SecureFile (chat/orders/tickets) پشتیبانی
 *     می‌شوند: خواندن با SecureFile::get → فشرده‌سازی → نوشتن با SecureFile::put.
 *   • GIF (از جمله متحرک) و فایل‌های کوچک فشرده نمی‌شوند.
 */
class CompressionService
{
    /** زیر این حجم تصویر دست نمی‌زنیم (سمت کلاینت هم همین آستانه است) */
    public const IMAGE_MIN_BYTES = 300 * 1024;

    /** آستانهٔ ویدیو — زیر آن ارزش تبدیل ندارد */
    public const VIDEO_MIN_BYTES = 3 * 1024 * 1024;

    /** فشرده‌سازی یک ردیف دفتر؛ خروجی: آیا واقعاً تغییر کرد؟ */
    public function process(MediaFile $media): bool
    {
        $cfg = UploadConfig::server();

        if (! $cfg['enabled']) {
            return $this->finish($media, 'skipped', 0, 'فشرده‌سازی خاموش است');
        }

        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            return $this->finish($media, 'failed', 0, 'فایل یافت نشد');
        }

        try {
            return $media->type === 'image'
                ? $this->processImage($media, $cfg)
                : $this->processVideo($media, $cfg);
        } catch (\Throwable $e) {
            report($e);

            return $this->finish($media, 'failed', 0, mb_substr($e->getMessage(), 0, 180));
        }
    }

    /* ================================ تصویر ================================ */

    private function processImage(MediaFile $media, array $cfg): bool
    {
        if (! function_exists('imagecreatefromstring')) {
            return $this->finish($media, 'skipped', 0, 'افزونهٔ GD روی سرور نیست');
        }

        $disk = Storage::disk($media->disk);
        $size = (int) $disk->size($media->path);

        if ($size < self::IMAGE_MIN_BYTES) {
            return $this->finish($media, 'skipped', $size, 'حجم کم — نیازی به فشرده‌سازی نیست');
        }

        $encrypted = false;
        $source = $disk->get($media->path);

        if ($source === null) {
            return $this->finish($media, 'failed', $size, 'خواندن فایل ممکن نشد');
        }

        if (SecureFile::isEncrypted($source)) {
            $encrypted = true;
            $source = SecureFile::decrypt($source);
        }

        $result = $this->recodeImage($source, $cfg);

        if ($result === null || strlen($result) >= $size) {
            return $this->finish($media, 'skipped', $size, 'خروجی کوچک‌تر نشد — اصل حفظ شد');
        }

        // اتمیک: نوشتن با پسوند موقت و سپس جایگزینی
        $tmpPath = $media->path.'.cn-tmp';

        if ($encrypted) {
            SecureFile::put($media->disk, $tmpPath, $result);
        } else {
            $disk->put($tmpPath, $result);
        }

        $disk->move($tmpPath, $media->path); // move در Flysystem جایگزین می‌کند
        clearstatcache(true, $disk->path($media->path));

        $final = (int) $disk->size($media->path);

        return $this->finish($media, 'done', $final, $this->savingNote($size, $final), $size);
    }

    /**
     * بازانکودینگ تصویر با GD — حذف EXIF + اختیاری تغییر فرمت/ابعاد.
     * خروجی: باینری تصویر جدید یا null (خطا/فرمت پشتیبانی‌نشده).
     */
    public function recodeImage(string $binary, array $cfg): ?string
    {
        $info = @getimagesizefromstring($binary);

        if (! $info) {
            return null;
        }

        [$w, $h] = $info;
        $mime = (string) ($info['mime'] ?? '');

        // GIF (متحرک یا ثابت) و SVG دست‌نخورده می‌مانند
        if (in_array($mime, ['image/gif', 'image/svg+xml'], true)) {
            return null;
        }

        $src = @imagecreatefromstring($binary);

        if (! $src) {
            return null;
        }

        // حداکثر ضلع — هرگز بزرگ‌نمایی نمی‌کنیم
        $maxSide = max(400, (int) ($cfg['max_side'] ?? 4096));
        $scale = min(1.0, $maxSide / max($w, $h));

        $dstW = max(1, (int) round($w * $scale));
        $dstH = max(1, (int) round($h * $scale));

        $hasAlpha = $mime === 'image/png' && $this->usesAlpha($src, $w, $h);
        $target = (string) ($cfg['format'] ?? 'keep');

        // فرمت مقصد: keep = همان فرمت؛ auto = شفاف → webp (در نبودش png)، بدون شفاف → jpeg
        $outMime = match ($target) {
            'jpeg' => $hasAlpha ? $mime : 'image/jpeg',
            'webp' => function_exists('imagewebp') ? 'image/webp' : $mime,
            'auto' => $hasAlpha
                ? (function_exists('imagewebp') ? 'image/webp' : 'image/png')
                : 'image/jpeg',
            default => $mime,
        };

        $dst = imagecreatetruecolor($dstW, $dstH);

        if (! $dst) {
            imagedestroy($src);

            return null;
        }

        if ($outMime === 'image/png' || $outMime === 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagealphablending($dst, true);
        } else {
            // JPEG — پس‌زمینهٔ سفید (حذف حاشیهٔ سیاه PNG نیمه‌شفاف)
            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, imagecolorallocate($dst, 255, 255, 255));
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $w, $h);
        imagedestroy($src);

        $quality = max(50, min(100, (int) ($cfg['quality'] ?? 92)));

        ob_start();
        $ok = match ($outMime) {
            'image/jpeg' => imagejpeg($dst, null, $quality),
            'image/png' => imagepng($dst, null, $quality >= 90 ? 6 : 8),
            'image/webp' => imagewebp($dst, null, $quality),
            default => false,
        };
        imagedestroy($dst);

        if (! $ok) {
            ob_end_clean();

            return null;
        }

        $out = (string) ob_get_clean();

        return $out !== '' ? $out : null;
    }

    /** آیا PNG واقعاً شفافیت دارد؟ (نمونه‌گیری ۹ نقطه) */
    private function usesAlpha(\GdImage $img, int $w, int $h): bool
    {
        $pts = [
            [0, 0], [$w - 1, 0], [0, $h - 1],
            [$w - 1, $h - 1], [intdiv($w, 2), intdiv($h, 2)],
            [intdiv($w, 4), intdiv($h, 4)], [$w - intdiv($w, 4), intdiv($h, 4)],
            [intdiv($w, 4), $h - intdiv($h, 4)], [$w - intdiv($w, 4), $h - intdiv($h, 4)],
        ];

        foreach ($pts as $p) {
            if ((imagecolorat($img, $p[0], $p[1]) >> 24) & 0x7F) {
                return true;
            }
        }

        return false;
    }

    /* ================================ ویدیو ================================ */

    private function processVideo(MediaFile $media, array $cfg): bool
    {
        if (empty($cfg['videos']['enabled'])) {
            return $this->finish($media, 'skipped', 0, 'فشرده‌سازی ویدیو خاموش است');
        }

        $ffmpeg = $cfg['videos']['ffmpeg'] ?? null;

        if (! $ffmpeg) {
            return $this->finish($media, 'skipped', 0, 'ffmpeg روی سرور در دسترس نیست');
        }

        $disk = Storage::disk($media->disk);
        $size = (int) $disk->size($media->path);

        if ($size < self::VIDEO_MIN_BYTES) {
            return $this->finish($media, 'skipped', $size, 'حجم ویدیو کم است');
        }

        $encrypted = false;
        $tmpIn = $disk->path($media->path.'.in-tmp');
        $tmpOut = $disk->path($media->path.'.out-tmp.mp4');

        try {
            $raw = $disk->get($media->path);

            if ($raw === null) {
                return $this->finish($media, 'failed', $size, 'خواندن فایل ممکن نشد');
            }

            if (SecureFile::isEncrypted($raw)) {
                $encrypted = true;
                file_put_contents($tmpIn, SecureFile::decrypt($raw));
            } else {
                file_put_contents($tmpIn, $raw);
            }

            if (! $this->ffmpegCompress($ffmpeg, $tmpIn, $tmpOut, (int) ($cfg['videos']['crf'] ?? 28))) {
                return $this->finish($media, 'skipped', $size, 'تبدیل ffmpeg ناموفق بود');
            }

            clearstatcache(true, $tmpOut);
            $outSize = (int) filesize($tmpOut);

            if ($outSize === 0 || $outSize >= $size) {
                return $this->finish($media, 'skipped', $size, 'خروجی ویدیو کوچک‌تر نشد — اصل حفظ شد');
            }

            if ($encrypted) {
                SecureFile::put($media->disk, $media->path.'.cn-tmp', (string) file_get_contents($tmpOut));
                $disk->move($media->path.'.cn-tmp', $media->path);
            } else {
                $stream = fopen($tmpOut, 'rb');
                $disk->put($media->path.'.cn-tmp', $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
                $disk->move($media->path.'.cn-tmp', $media->path);
            }

            $final = (int) $disk->size($media->path);

            return $this->finish($media, 'done', $final, $this->savingNote($size, $final), $size);
        } finally {
            @unlink($tmpIn);
            @unlink($tmpOut);
        }
    }

    private function ffmpegCompress(string $ffmpeg, string $in, string $out, int $crf): bool
    {
        if (! is_file($in)) {
            return false;
        }

        $cmd = sprintf(
            '%s -y -i %s -vcodec libx264 -crf %d -preset veryfast -movflags +faststart -an %s 2>/dev/null',
            escapeshellarg($ffmpeg),
            escapeshellarg($in),
            max(18, min(34, $crf)),
            escapeshellarg($out)
        );

        @exec($cmd, $lines, $code);

        return $code === 0 && is_file($out) && filesize($out) > 0;
    }

    /* ================================ ابزار ================================ */

    private function finish(MediaFile $media, string $status, int $finalSize, string $note, ?int $original = null): bool
    {
        $media->update([
            'status' => $status,
            'final_size' => $finalSize,
            'original_size' => $original ?? $media->original_size,
            'note' => mb_substr($note, 0, 190),
            'processed_at' => now(),
        ]);

        return $status === 'done';
    }

    private function savingNote(int $before, int $after): string
    {
        $pct = $before > 0 ? (int) round((1 - $after / $before) * 100) : 0;

        return number_format($before / 1024, 0).'KB ← '.number_format($after / 1024, 0).'KB (−'.$pct.'٪)';
    }
}
