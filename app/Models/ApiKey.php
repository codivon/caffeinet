<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * فاز ۴۷ — کلید وب‌سرویس (API Key).
 *
 * دسترسی به /api/v1 بدون هدر «X-Api-Key» معتبر ممکن نیست (میدل‌ویر api.key).
 * کلیدها فقط از پنل ادمین (admin/api-keys) ساخته/حذف می‌شوند و مقدارشان
 * خودکار تولید می‌شود (cnk_ + ۴۰ نویسهٔ تصادفی).
 *
 * رکورد «is_internal=true» کلید وب‌اپ داخلی است و در صفحات تزریق می‌شود؛
 * از UI حذف‌شدنی نیست تا اپ مشتری/پنل‌ها نشکنند.
 *
 * @property int         $id
 * @property string|null $name
 * @property string      $key
 * @property bool        $is_internal
 * @property \Carbon\Carbon|null $last_used_at
 * @property int         $requests_count
 */
class ApiKey extends Model
{
    protected $fillable = ['name', 'key', 'is_internal'];

    protected $casts = [
        'is_internal'   => 'boolean',
        'last_used_at'  => 'datetime',
        'requests_count' => 'integer',
    ];

    /** تولید کلید خودکار — cnk_ + ۴۰ نویسهٔ هگز تصادفی (کریپتو-امن) */
    public static function generate(): string
    {
        return 'cnk_' . bin2hex(random_bytes(20));
    }

    /** کلید داخلی وب‌اپ (با خودترمیمی — اگر حذف شده باشد فوراً یکی می‌سازد) */
    public static function internalKey(): string
    {
        $internal = static::query()->where('is_internal', true)->first();

        if (! $internal) {
            $internal = static::create([
                'name'        => 'اپ وب داخلی (خودکار)',
                'key'         => static::generate(),
                'is_internal' => true,
            ]);
        }

        return $internal->key;
    }

    /** ثبت استفاده — یک UPDATE اتمی: شمارنده + آخرین استفاده (در میدل‌ویر) */
    public function markUsed(): void
    {
        static::query()
            ->whereKey($this->getKey())
            ->update([
                'requests_count' => \DB::raw('requests_count + 1'),
                'last_used_at'   => $this->freshTimestamp(),
            ]);
    }
}
