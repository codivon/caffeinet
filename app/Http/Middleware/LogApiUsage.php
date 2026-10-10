<?php

namespace App\Http\Middleware;

use App\Models\ApiUsageLog;
use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * فاز ۵۱ — ثبت مصرف API (api.usage) — بعد از api.key روی گروه v1.
 *
 * هر درخواست /api/v1 یک ردیف در api_usage_logs می‌سازد: کدام کلید،
 * کدام کاربر (Bearer احرازشده یا مهمان)، چه مسیری، از چه IP/دستگاهی
 * و با چه کد پاسخی. با سوییچ «features.api_usage_log» خاموش می‌شود.
 *
 * هیچ‌وقت نباید درخواست را بشکند — کل بدنه try/catch است.
 * ثبت بعد از پاسخ انجام می‌شود تا status_code هم درست باشد.
 */
class LogApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        /* ثبت مصرف فقط وقتی سوییچ روشن است؛ health و OPTIONS ثبت نمی‌شوند */
        try {
            if (! $request->routeIs('api.health')
                && $request->getMethod() !== 'OPTIONS'
                && app(SettingsService::class)->get('features.api_usage_log', true)) {

                $apiKey = $request->attributes->get('api_key');

                ApiUsageLog::create([
                    'api_key_id'  => $apiKey?->id,
                    'user_id'     => $request->user()?->id,
                    'method'      => substr($request->getMethod(), 0, 10),
                    'path'        => substr($request->getPathInfo(), 0, 190),
                    'ip'          => substr((string) $request->ip(), 0, 45),
                    'user_agent'  => substr((string) $request->userAgent(), 0, 255),
                    'status_code' => $response->getStatusCode(),
                    'created_at'  => now(),
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }
}
