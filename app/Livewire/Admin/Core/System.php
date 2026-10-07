<?php

namespace App\Livewire\Admin\Core;

use App\Services\Settings\SettingsService;
use App\Support\SecureFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * وضعیت سیستم و نگهداشت (فاز ۱۱) — کامپوننت Livewire [Task 3-b]
 *
 * منطق view عیناً از App\Http\Controllers\Back\Admin\SystemController@index
 * منتقل شده (scheduler/files/security/retention/lastCleanup/resources) —
 * فقط رندر به Livewire منتقل شده است.
 *
 * عملیات (پاکسازی/نگهداشت/رمزنگاری) با همان JS قبلی به POST
 * system/cleanup|retention|encrypt (کنترلر حفظ شده) می‌رود؛ گزارش
 * پاکسازی از partial مشترک back.admin.system._report رندر می‌شود.
 */
#[Layout('back.layouts.panel')]
class System extends Component
{
    public function render()
    {
        $settings = app(SettingsService::class);

        return view('livewire.admin.core.system', [
            'scheduler' => $this->schedulerInfo(),
            'files' => $this->filesInfo(),
            'security' => $this->securityInfo(),
            'retention' => [
                'notifications_read' => (int) $settings->get('system.cleanup.notifications_read', 30),
                'notifications_unread' => (int) $settings->get('system.cleanup.notifications_unread', 90),
                'sms_logs' => (int) $settings->get('system.cleanup.sms_logs', 90),
                'audit_logs' => (int) $settings->get('system.cleanup.audit_logs', 365),
            ],
            'lastCleanup' => $this->lastCleanup(),
            'resources' => $this->resourcesInfo(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'وضعیت سیستم و نگهداشت',
            'breadcrumb' => 'پنل مدیریت کل ← وضعیت سیستم',
            'htmlTitle' => 'وضعیت سیستم',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  داخلی (عیناً از SystemController)                                   */
    /* ------------------------------------------------------------------ */

    /** برنامهٔ زمان‌بندی ثبت‌شده — از schedule:list (کش ۶۰ ثانیه). */
    private function schedulerInfo(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('admin.system.schedule', now()->addMinute(), function () {
            Artisan::call('schedule:list', ['--json' => true]);

            $rows = json_decode((string) Artisan::output(), true) ?: [];

            return collect($rows)->map(function ($row) {
                $command = trim((string) preg_replace('/^php\s+artisan\s+/', '', $row['command'] ?? ''));

                return [
                    'command' => $command,
                    'expression' => $row['expression'] ?? '',
                    'description' => $row['description'] ?? $command,
                    'next' => $row['next_due_date_human'] ?? null,
                ];
            })->values()->all();
        });
    }

    /** وضعیت رمزنگاری فایل‌های خصوصی. */
    private function filesInfo(): array
    {
        $disk = Storage::disk(SecureFile::DISK);

        $encrypted = 0;
        $plain = 0;
        $bytes = 0;

        foreach (SecureFile::SECURE_PATHS as $folder) {
            foreach ($disk->allFiles($folder) as $path) {
                $bytes += $disk->size($path);

                $stream = $disk->readStream($path);
                $head = fread($stream, 8);
                fclose($stream);

                str_starts_with($head, 'CNENC1:') ? $encrypted++ : $plain++;
            }
        }

        return [
            'encrypted' => $encrypted,
            'plain' => $plain,
            'total' => $encrypted + $plain,
            'bytes' => $bytes,
            'disk' => SecureFile::DISK,
        ];
    }

    /** چک‌لیست امنیت پروداکشن. */
    private function securityInfo(): array
    {
        return [
            ['label' => 'APP_DEBUG خاموش', 'ok' => ! config('app.debug'), 'hint' => 'در پروداکشن باید false باشد.'],
            ['label' => 'APP_ENV پروداکشن', 'ok' => app()->isProduction(), 'hint' => 'در سرور اصلی باید production باشد.'],
            ['label' => 'کلید اپلیکیشن (APP_KEY)', 'ok' => (bool) config('app.key') && ! str_contains((string) config('app.key'), 'base64:SomeRandom'), 'hint' => 'کلید رمزنگاری نشست/کوکی.'],
            ['label' => 'کلید مستقل رمزنگاری فایل', 'ok' => trim((string) env('FILE_ENCRYPTION_KEY')) !== '', 'hint' => 'FILE_ENCRYPTION_KEY اختیاری؛ بدون آن کلیدِ مشتق از APP_KEY استفاده می‌شود.'],
            ['label' => 'اتصال امن HTTPS', 'ok' => request()->isSecure(), 'hint' => 'روی پروداکشن با SSL/TERMINATE پروکسی.'],
            ['label' => 'هدرهای امنیتی + CSP', 'ok' => true, 'hint' => 'SecurityHeaders روی همهٔ پاسخ‌ها فعال است.'],
            ['label' => 'محدودیت نرخ OTP', 'ok' => true, 'hint' => '۲/دقیقه هر شماره + ۱۰/ساعت هر IP.'],
            ['label' => 'محدودیت نرخ API', 'ok' => true, 'hint' => '۱۲۰ درخواست در دقیقه هر کاربر احرازشده.'],
            ['label' => 'قفل تلاش ورود پنل‌ها', 'ok' => true, 'hint' => '۵ تلاش ناموفق → ۶۰ ثانیه تعلیق (۴ پنل).'],
        ];
    }

    /** آخرین گزارش پاکسازی. */
    private function lastCleanup(): ?array
    {
        $raw = app(SettingsService::class)->get('system.cleanup.last');

        if (! $raw) {
            return null;
        }

        $data = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return is_array($data) ? $data : null;
    }

    /** مصرف منابع (db/لاگ/دیسک خصوصی). */
    private function resourcesInfo(): array
    {
        $dbPath = database_path('database.sqlite');
        $log = storage_path('logs/laravel.log');

        $archives = collect(glob(storage_path('logs/laravel-*.log')) ?? [])->count();

        return [
            'database' => is_file($dbPath) ? (int) @filesize($dbPath) : 0,
            'log' => is_file($log) ? (int) @filesize($log) : 0,
            'log_archives' => $archives,
        ];
    }
}
