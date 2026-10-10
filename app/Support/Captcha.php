<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * فاز ۵۰ — ربات‌گیر ورود پنل‌ها (کپچای ریاضی SVG — بدون وابستگی بیرونی).
 *
 * چرا ریاضی؟ اپ هدف فقط بات‌های سادهٔ اسکریپتی است؛ بدون سرویس بیرونی
 * (reCAPTCHA در ایران فیلتر است) و بدون جدول اضافه — پاسخ در session نگه
 * داشته می‌شود و هر بار «تازه‌سازی» یا خطا، سؤال جدید می‌سازد.
 *
 * حالت‌ها (features.captcha_mode):
 *   off     → هرگز
 *   always  → همیشه
 *   smart   → فقط بعد از N تلاش ناموفق (features.captcha_after_fails)
 *
 * استفاده:
 *   Captcha::required($limiterKey)      → آیا این درخواست کپچا لازم دارد؟
 *   Captcha::render()                   → ['svg' => ..., 'token' => ...] (سؤال در session)
 *   Captcha::verify($input)             → بررسی پاسخ + تولید سؤال جدید پس از هر بررسی
 */
class Captcha
{
    /** کلید session نگهداری سؤال فعلی */
    public const SESSION_KEY = 'panel_captcha';

    /** سقف عمر سؤال (ثانیه) */
    public const TTL = 600;

    /**
     * آیا برای این تلاشِ ورود کپچا لازم است؟
     *
     * @param  string  $limiterKey  کلید RateLimiter همان پنل (تعداد تلاش‌های ناموفق از همان خوانده می‌شود)
     */
    public static function required(string $limiterKey): bool
    {
        $mode = (string) app(\App\Services\Settings\SettingsService::class)->get('features.captcha_mode', 'smart');

        if ($mode === 'always') {
            return true;
        }

        if ($mode === 'smart') {
            $after = max(1, (int) app(\App\Services\Settings\SettingsService::class)->get('features.captcha_after_fails', 2));

            return \Illuminate\Support\Facades\RateLimiter::attempts($limiterKey) >= $after;
        }

        return false;
    }

    /**
     * ساخت سؤال جدید + رندر SVG — خروجی مستقیم در ویو چاپ می‌شود.
     *
     * @return array{svg: string, token: string}
     */
    public static function render(): array
    {
        $a = random_int(2, 9);
        $b = random_int(1, 9);

        // جمع یا تفریق (تفریق همیشه مثبت)
        if (random_int(0, 1) === 0) {
            [$question, $answer] = ["{$a} + {$b}", $a + $b];
        } else {
            if ($b > $a) {
                [$a, $b] = [$b, $a];
            }
            [$question, $answer] = ["{$a} - {$b}", $a - $b];
        }

        $token = Str::random(16);

        session([self::SESSION_KEY => [
            'answer' => $answer,
            'question' => $question,
            'token' => $token,
            'expires' => now()->addSeconds(self::TTL)->getTimestamp(),
        ]]);

        return ['svg' => self::svg($question), 'question' => $question, 'token' => $token];
    }

    /**
     * سؤال فعلی بدون ساخت سؤال جدید — SVG از همان سؤال دوباره رسم می‌شود
     * (شکل نویز هر بار تازه، پاسخ همان). اگر سؤالی نیست یا منقضی است، جدید می‌سازد.
     *
     * @return array{svg: string, question: string, token: string}
     */
    public static function current(): array
    {
        $state = session(self::SESSION_KEY);

        if (is_array($state)
            && isset($state['question'], $state['expires'])
            && $state['expires'] >= now()->getTimestamp()) {
            return ['svg' => self::svg($state['question']), 'question' => $state['question'], 'token' => $state['token'] ?? ''];
        }

        return self::render();
    }

    /**
     * بررسی پاسخ کاربر — پس از هر بررسی سؤال جدید ساخته می‌شود.
     */
    public static function verify(?string $input): bool
    {
        $state = session(self::SESSION_KEY);

        session()->forget(self::SESSION_KEY);

        $ok = is_array($state)
            && isset($state['answer'], $state['expires'], $state['token'])
            && $state['expires'] >= now()->getTimestamp()
            && trim((string) $input) !== ''
            && (int) trim((string) $input) === (int) $state['answer'];

        return $ok;
    }

    /** حذف سؤال فعلی (تازه‌سازی دستی) */
    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /** SVG سؤال با نویز — برای ضد-OCR ساده */
    private static function svg(string $question): string
    {
        $chars = str_split($question);
        $x = 16;
        $glyphs = '';

        foreach ($chars as $i => $ch) {
            if ($ch === ' ') {
                $x += 7;

                continue;
            }

            $rot = random_int(-16, 16);
            $dy = random_int(-3, 3);
            $color = ['#e2e8f0', '#93c5fd', '#fcd34d', '#86efac'][$i % 4];
            $glyphs .= sprintf(
                '<text x="%d" y="%d" transform="rotate(%d %d %d)" fill="%s" font-size="21" font-family="monospace" font-weight="bold">%s</text>',
                $x,
                27 + $dy,
                $rot,
                $x,
                27 + $dy,
                $color,
                htmlspecialchars($ch, ENT_QUOTES),
            );
            $x += 14;
        }

        $noise = '';
        for ($i = 0; $i < 3; $i++) {
            $noise .= sprintf(
                '<path d="M%d %d Q %d %d %d %d" stroke="rgba(148,163,184,.35)" stroke-width="1" fill="none"/>',
                random_int(0, 40),
                random_int(4, 36),
                random_int(40, 90),
                random_int(4, 36),
                random_int(100, 140),
                random_int(4, 36),
            );
        }
        for ($i = 0; $i < 12; $i++) {
            $noise .= sprintf('<circle cx="%d" cy="%d" r="1" fill="rgba(148,163,184,.4)"/>', random_int(0, 145), random_int(0, 40));
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 44" width="150" height="44" role="img" aria-label="کد امنیتی">%s%s</svg>',
            $noise,
            $glyphs,
        );
    }
}
