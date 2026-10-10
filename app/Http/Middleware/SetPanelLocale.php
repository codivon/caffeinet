<?php

namespace App\Http\Middleware;

use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * فاز ۵۴ — تعیین زبان پنل‌ها/اپ (i18n).
 *
 * سوییچ «features.i18n» خاموش = همیشه فارسی (رفتار فعلی).
 * روشن = زبان از کوکی «cn_locale» خوانده می‌شود (fa | en) و
 * سوئیچ زبانِ سربرگ پنل ادمین/اپ مشتری آن را ست می‌کند.
 *
 * اثرات: App::locale + direction (rtl/ltr) که لایه‌ها از آن
 * برای <html dir> استفاده می‌کنند — ترجمه‌ها در lang/{fa,en}/chrome.php.
 */
class SetPanelLocale
{
    public const COOKIE = 'cn_locale';

    public const LANGS = ['fa', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'fa';

        try {
            $i18nOn = (bool) app(SettingsService::class)->get('features.i18n', false);

            if ($i18nOn) {
                $cookie = trim((string) $request->cookie(self::COOKIE, ''));

                if (in_array($cookie, self::LANGS, true)) {
                    $locale = $cookie;
                }
            }
        } catch (\Throwable) {
            $locale = 'fa'; // هیچ‌وقت صفحه را نشکن
        }

        App::setLocale($locale);

        // برای لایه‌ها: direction صفحه + وضعیت قابلیت چندزبانه
        view()->share('panelDir', $locale === 'en' ? 'ltr' : 'rtl');
        view()->share('panelLang', $locale);
        view()->share('i18nOn', $i18nOn); // قابلیت روشن است؟ (برای نمایش سوئیچ زبان)

        return $next($request);
    }
}
