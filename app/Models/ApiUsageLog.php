<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * فاز ۵۱ — لاگ مصرف API (یک ردیف به‌ازای هر درخواست /api/v1).
 *
 * با سوییچ «features.api_usage_log» روشن/خاموش می‌شود (میدل‌ویر api.usage).
 * پاکسازی دوره‌ای: system:cleanup (scope api_usage — پیش‌فرض ۳۰ روز).
 */
class ApiUsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'api_key_id', 'user_id', 'method', 'path',
        'ip', 'user_agent', 'status_code', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
