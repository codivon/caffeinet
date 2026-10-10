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
 *   • پنل‌های پشتی: با کوکی نشست وب (هم‌دامنه — خودکار ارسال می‌شود).
 *   • ‎?last_id=‎ یا هدر Last-Event-ID (اختیاری): آخرین رویدادِ تحویل‌شده —
 *     رویدادهای فاصلهٔ reconnect پخش دوباره می‌شوند (فاز ۵۹-c).
 *
 * فاز ۵۹-c — سخت‌سازی هاست اشتراکی: هر اتصال یک ورکر LSAPI را اشغال می‌کند،
 * پس استریم «کوتاه‌چرخه» است (TTL پیش‌فرض ۲۵ ثانیه، سپس event: close تمیز و
 * اتصال مجدد خودکار کلاینت — SseService::streamResponse). پیش‌نیاز آزادشدن
 * سریع ورکر: رهاکردن قفل نشست در اولین فرصت (پایین) و نگه‌نداشتن هیچ
 * تراکنش/قفل دیگری در طول استریم.
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

        /* فاز ۱۳/۵۹-c — آزادسازی نشست پیش از استریم (حیاتی برای هاست اشتراکی):
           استریم (با هر TTL) تا پایانش باز می‌مانَد؛ اگر قفل نشست (database/file
           driver) باز بماند، بقیهٔ درخواست‌های همان کاربر پشت آن معطل می‌شوند.
           save() دادهٔ نشست را می‌نویسد و lock آن را رها می‌کند؛
           session_write_close() هم برای SAPIهای با نشست نیتیو ضمانت است. */
        if ($request->hasSession()) {
            try {
                $request->session()->save();
            } catch (\Throwable) {
                // نشست اختیاری است — استریم نباید به‌خاطر آن بشکند
            }
        }

        if (\function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        // آخرین رویداد تحویل‌شده (replay فاصلهٔ reconnect — فاز ۵۹-c)
        $lastId = (int) $request->query('last_id', '');

        if ($lastId <= 0) {
            $lastId = (int) $request->headers->get('Last-Event-ID', '');
        }

        return $sse->streamResponse($user, $lastId > 0 ? $lastId : null);
    }
}
