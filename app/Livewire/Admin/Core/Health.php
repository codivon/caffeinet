<?php

namespace App\Livewire\Admin\Core;

use App\Models\SmsLog;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * فاز ۵۴ — صفحهٔ «سلامت سیستم» (تنظیمات ← سلامت سیستم).
 *
 * یک نگاه به همهٔ ورودی‌های حیاتی هاست اشتراکی:
 *   PHP/Laravel، دیسک، دیتابیس، ضربان کرون، صف پیامک (در انتظار ریتری/شکست‌های امروز)،
 *   سفارش‌های گیرکردهٔ پخش، نشست‌های فعال، مصرف API امروز، آخرین پاکسازی.
 *
 * سوییچ: features.health_page (خاموش = 404).
 * GET admin/health — نام: admin.health (مسیر بدون منوی مستقل؛ alias سایدبار تنظیمات).
 */
#[Layout('back.layouts.panel')]
class Health extends Component
{
    public function mount(SettingsService $settings): void
    {
        if (! (bool) $settings->get('features.health_page', true)) {
            abort(404);
        }
    }

    public function render()
    {
        $dbPath = config('database.connections.sqlite.database');
        $isSqlite = is_string($dbPath) && str_ends_with($dbPath, '.sqlite');

        $cronLast = \App\Models\Setting::query()->where('key', \App\Support\CronHeartbeat::KEY)->value('value');
        $cronAt = $cronLast ? \Illuminate\Support\Carbon::parse($cronLast) : null;

        $cleanupLast = app(SettingsService::class)->get('system.cleanup.last');

        $checks = [
            [
                'group' => 'بستر اجرا',
                'items' => [
                    ['label' => 'نسخهٔ PHP', 'value' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '8.3', '>=')],
                    ['label' => 'نسخهٔ Laravel', 'value' => app()->version(), 'ok' => true],
                    ['label' => 'دیتابیس', 'value' => $isSqlite ? 'SQLite (توسعه)' : 'MySQL', 'ok' => true],
                    ['label' => 'حجم دیتابیس', 'value' => $isSqlite && is_file($dbPath) ? fa_number(round(filesize($dbPath) / 1048576, 1)).' مگابایت' : '—', 'ok' => true],
                    ['label' => 'فضای آزاد دیسک', 'value' => fa_number(round((float) disk_free_space(storage_path()) / 1073741824, 1)).' گیگابایت', 'ok' => disk_free_space(storage_path()) > 1073741824],
                    ['label' => 'کش نوشتنی', 'value' => is_writable(storage_path('framework/cache')) ? 'سالم' : 'غیرقابل نوشتن!', 'ok' => is_writable(storage_path('framework/cache'))],
                ],
            ],
            [
                'group' => 'زمان‌بندی و صف‌ها',
                'items' => [
                    [
                        'label' => 'ضربان کرون',
                        'value' => $cronAt ? 'آخرین: '.fa_date($cronAt) : 'هرگز اجرا نشده!',
                        'ok' => $cronAt !== null && $cronAt->gt(now()->subSeconds(\App\Support\CronHeartbeat::HEALTHY_WINDOW)),
                    ],
                    [
                        'label' => 'صف ریتری پیامک',
                        'value' => fa_number($this->pendingSmsRetries()).' پیامک در انتظار تلاش دوباره',
                        'ok' => $this->pendingSmsRetries() < 20,
                    ],
                    [
                        'label' => 'پیامک ناموفق امروز',
                        'value' => fa_number(SmsLog::where('status', 'failed')->whereDate('created_at', today())->count()).' پیامک',
                        'ok' => SmsLog::where('status', 'failed')->whereDate('created_at', today())->count() < 30,
                    ],
                    [
                        'label' => 'سفارش‌های گیرکردهٔ پخش',
                        'value' => fa_number($this->stuckBroadcasts()).' سفارش منقضی در انتظار تعیین‌تکلیف',
                        'ok' => $this->stuckBroadcasts() < 10,
                    ],
                ],
            ],
            [
                'group' => 'فعالیت امروز',
                'items' => [
                    ['label' => 'درخواست‌های API امروز', 'value' => fa_number($this->apiToday()).' درخواست', 'ok' => true],
                    ['label' => 'وب‌هوک‌های شکست‌خورده (۳ روز)', 'value' => fa_number($this->failedWebhooks()).' ارسال', 'ok' => $this->failedWebhooks() === 0],
                    ['label' => 'نشست‌های فعال (همهٔ کاربران)', 'value' => fa_number($this->activeSessions()).' نشست', 'ok' => true],
                    [
                        'label' => 'آخرین پاکسازی خودکار',
                        'value' => $cleanupLast && is_string($cleanupLast) ? (function () use ($cleanupLast) {
                            $d = json_decode($cleanupLast, true);
                            return isset($d['ran_at']) ? fa_date(\Illuminate\Support\Carbon::parse($d['ran_at'])) : '—';
                        })() : 'هنوز اجرا نشده',
                        'ok' => true,
                    ],
                ],
            ],
        ];

        return view('livewire.admin.core.health', [
            'checks' => $checks,
            'allOk' => collect($checks)->flatMap->items->every(fn ($i) => (bool) $i['ok']),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'سلامت سیستم',
            'breadcrumb' => 'پنل مدیریت کل ← تنظیمات ← سلامت سیستم',
            'htmlTitle' => 'سلامت سیستم',
        ]);
    }

    private function pendingSmsRetries(): int
    {
        return (int) DB::table('sms_logs')
            ->where('status', 'failed')
            ->whereNotNull('next_retry_at')
            ->where('attempts', '<', 3)
            ->count();
    }

    private function stuckBroadcasts(): int
    {
        return (int) DB::table('orders')
            ->whereNull('deleted_at')
            ->where('status', 'broadcasting')
            ->whereNotNull('broadcast_expires_at')
            ->where('broadcast_expires_at', '<', now())
            ->count();
    }

    private function apiToday(): int
    {
        try {
            return (int) DB::table('api_usage_logs')->whereDate('created_at', today())->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function failedWebhooks(): int
    {
        try {
            return (int) DB::table('webhook_deliveries')
                ->where('ok', false)
                ->where('created_at', '>=', now()->subDays(3))
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function activeSessions(): int
    {
        return (int) DB::table('sessions')->where('last_activity', '>=', now()->subHours(24)->getTimestamp())->count();
    }
}
