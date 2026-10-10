<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * فاز ۴۷ — الزام کلید وب‌سرویس روی همهٔ APIها (api.key).
 *
 * هر درخواست /api/v1 باید هدر «X-Api-Key» (یا «Api-Key») با یکی از کلیدهای
 * ثبت‌شده در جدول api_keys بفرستد؛ وگرنه 401 JSON. کلیدها فقط از
 * پنل ادمین → «کلیدهای وب‌سرویس» ساخته/حذف می‌شوند.
 *
 * استثنا: /api/v1/health برای مانیتورینگ/بالانسر بدون کلید باز است.
 * کلید داخلی وب‌اپ (is_internal) در صفحات تزریق می‌شود و اپ/پنل‌ها
 * خودکار با آن صدا می‌زنند (partials/vpn-modal + core.js).
 */
class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        /* health — بدون کلید (مانیتورینگ) */
        if ($request->routeIs('api.health')) {
            return $next($request);
        }

        $provided = trim((string) ($request->header('X-Api-Key') ?: $request->header('Api-Key') ?: ''));

        if ($provided === '') {
            return $this->deny('کلید وب‌سرویس ارسال نشده است؛ هدر «X-Api-Key» الزامی است.');
        }

        $apiKey = ApiKey::query()->where('key', $provided)->first();

        if (! $apiKey) {
            return $this->deny('کلید وب‌سرویس نامعتبر است.');
        }

        /* ثبت استفاده — هیچ‌وقت نباید درخواست را بشکند */
        try {
            $apiKey->markUsed();
            // فاز ۵۱ — برای میدل‌ویر api.usage (لاگ مصرف با کاربر/دستگاه)
            $request->attributes->set('api_key', $apiKey);
        } catch (\Throwable $e) {
            report($e);
        }

        return $next($request);
    }

    private function deny(string $message): Response
    {
        return response()->json([
            'message' => $message,
            'error'   => 'invalid_api_key',
        ], 401);
    }
}
