<?php

namespace App\Http\Controllers\Front\App;

use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * v42 — سرو آواتار پروفایل مشتری.
 *
 * فایل آواتار ۷۵×۷۵ WebP روی دیسک «public» (storage/app/public/avatars)
 * نگهداری می‌شود و از این مسیر بدون نیاز به storage:link سرو می‌شود —
 * با هدر کش طولانی + ETag مبتنی بر mtime تا مرورگر/CDN دوباره نگیرد.
 */
class AvatarController extends Controller
{
    public function show(User $user)
    {
        $path = $user->avatar_path;

        if (! $path) {
            abort(404);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $etag = '"'.md5($path.$disk->lastModified($path)).'"';

        $response = response($disk->get($path), 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=86400',
            'ETag' => $etag,
        ]);

        if (request()->header('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return $response;
    }
}
