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

        return $sse->streamResponse($user);
    }
}
