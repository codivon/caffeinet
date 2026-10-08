<?php

namespace App\Http\Controllers;

use App\Services\Realtime\SseService;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * فاز ۱۲ — جریان Realtime داخلی (SSE) — «اتصال دائمی مثل سوکت، بدون درخواست دوره‌ای».
 *
 * GET /realtime/stream
 *   • اپ مشتری (بدون نشست وب): توکن Sanctum به‌صورت ‎?token=…‎ — EventSource
 *     اجازهٔ هدر نمی‌دهد؛ URL از /api/v1/realtime/config می‌آید.
 *   • پنل‌های پشتی: با کوکی نشست وب (همان دامنه — خودکار ارسال می‌شود).
 *
 * خروجی: text/event-stream — رویدادهای rt ({channel, event, payload}) که
 * realtime.js مثل رویدادهای پوشر به صفحات مسیریابی می‌کند؛ بنابراین
 * «پوشر کامل بدون پولینگ» این بار با ترابورت خودِ سایت کار می‌کند.
 */
class RealtimeStreamController extends Controller
{
    public function stream(Request $request, SseService $sse)
    {
        $user = null;

        $token = trim((string) $request->query('token', ''));

        if ($token !== '') {
            // توکن Sanctum (اپ مشتری) — findToken خودش هش می‌کند
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken
                && (! $accessToken->expires_at || $accessToken->expires_at->isFuture())
                && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            }
        } else {
            // نشست وب (پنل‌های پشتی)
            $user = $request->user();
        }

        if (! $user || (method_exists($user, 'trashed') && $user->trashed())) {
            // SSE نمی‌تواند redirect کند — پاسخ متنی کوتاه
            return response('unauthorized', 401)->header('Content-Type', 'text/plain');
        }

        if (! $sse->enabled()) {
            return response('sse disabled', 404)->header('Content-Type', 'text/plain');
        }

        // فاز ۱۳ — آزادسازی نشست پیش از استریم (نکتهٔ سرعت):
        // استریم تا ~۴ دقیقه باز می‌مانَد؛ اگر نشست باز بماند، نوشتنش تا پایان
        // استریم عقب می‌افتد و در درایورهای دارای قفل (database/file block) بقیهٔ
        // درخواست‌های همان کاربر هم معطل می‌شوند. با ذخیرهٔ فوری، نشست رها می‌شود
        // و بقیهٔ درخواست‌ها با حداکثر سرعت اجرا می‌شوند.
        if ($request->hasSession()) {
            try {
                $request->session()->save();
            } catch (\Throwable) {
                // نشست اختیاری است — استریم نباید به‌خاطر آن بشکند
            }
        }

        return $sse->streamResponse($user);
    }
}
