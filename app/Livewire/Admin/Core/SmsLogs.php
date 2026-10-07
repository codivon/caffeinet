<?php

namespace App\Livewire\Admin\Core;

use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * لاگ پیامک‌های ارسال‌شده (v16) — کامپوننت Livewire 4 [Task 3-b]
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\SmsLogsController
 * (index + data) منتقل شده است — به‌جای AJAX، رندر سمت سرور:
 *   • جستجو در موبایل (نرمال‌سازی ارقام)/متن/کلید قالب
 *   • فیلتر وضعیت/پرووایدر/بازهٔ تاریخ شمسی (jalali_or_iso_to_carbon)
 *   • صفحه‌بندی ۲۵تایی + عنوان فارسی قالب‌ها (یک کوئری)
 *   • چیپ‌های آماری (کل/موفق/ناموفق/امروز — بدون فیلتر)
 *   • مودال جزئیات (متن کامل + پاسخ JSON پرووایدر)
 *   • v29 — حذف دوره‌ای لاگ پیامک (نگهداشت + پاکسازی فوری)
 *
 * endpointهای GET sms-logs/data و POST system/* حفظ شده‌اند.
 */
#[Layout('back.layouts.panel')]
class SmsLogs extends Component
{
    use WithPagination;

    /** جستجو: موبایل، متن، کلید قالب */
    #[Url(as: 'q')]
    public string $q = '';

    /** وضعیت: '' | sent | failed */
    #[Url(as: 'status')]
    public string $status = '';

    /** پرووایدر */
    #[Url(as: 'provider')]
    public string $provider = '';

    /** از تاریخ (شمسی یا ISO) */
    #[Url(as: 'from')]
    public string $from = '';

    /** تا تاریخ (شمسی یا ISO) */
    #[Url(as: 'to')]
    public string $to = '';

    /** مقدار نگهداشت (روز) — کارت v29 */
    public int $retentionDays = 90;

    /** لاگ انتخاب‌شده برای مودال جزئیات */
    public ?array $detail = null;

    public function mount(): void
    {
        $this->retentionDays = $this->currentRetention();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedProvider(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    /** پاک‌کردن فیلترها */
    public function resetFilters(): void
    {
        $this->q = '';
        $this->status = '';
        $this->provider = '';
        $this->from = '';
        $this->to = '';
        $this->resetPage();
    }

    /** جزئیات یک ردیف (مودال) */
    public function showDetail(int $id): void
    {
        $log = SmsLog::query()->findOrFail($id);

        $this->detail = [
            'mobile' => $log->mobile,
            'provider' => self::knownProviders()[$log->provider] ?? $log->provider,
            'mode' => (string) ($log->response['mode'] ?? 'plain'),
            'template' => $log->template_key ?: '—',
            'status' => $log->status,
            'created_fa' => $log->created_at ? fa_date($log->created_at, 'Y/m/d H:i') : '—',
            'message' => $log->message ?? '—',
            'response' => $log->response ? json_encode($log->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '—',
        ];
    }

    public function closeDetail(): void
    {
        $this->detail = null;
    }

    /** v29 — ذخیرهٔ نگهداشت لاگ پیامک (همان منطق SystemController@saveRetention) */
    public function saveRetention(): void
    {
        $days = $this->retentionDays;

        if (! is_numeric($days) || (int) $days < 1 || (int) $days > 3650) {
            $this->addError('retention', 'مقدار نگهداشت باید عددی بین ۱ تا ۳۶۵۰ روز باشد.');

            return;
        }

        $days = (int) $days;

        app(SettingsService::class)->updateMany(['system.cleanup.sms_logs' => $days]);

        AuditLogger::log('system.retention.updated', null, null,
            ['sms_logs' => $days], 'بروزرسانی نگهداشت داده‌ها (۱ مورد)');

        $this->retentionDays = $days;
        $this->js("window.App?.toast ? App.toast('نگهداشت لاگ پیامک ذخیره شد.', 'success') : null");
    }

    /** v29 — پاکسازی فوری قدیمی‌ها (scope=sms_logs) */
    public function cleanupOld(): void
    {
        if ($this->oldCount() === 0) {
            $this->js("window.App?.toast ? App.toast('ردیف قدیمی‌تری برای حذف نیست.', 'success') : null");

            return;
        }

        Artisan::call('system:cleanup', ['--scope' => 'sms_logs']);

        AuditLogger::log('system.cleanup.manual', null, null,
            ['--scope' => 'sms_logs'], 'اجرای دستی پاکسازی از پنل (فقط: sms_logs)');

        $this->js("window.App?.toast ? App.toast('پاکسازی اجرا شد؛ ردیف‌های قدیمی حذف شدند.', 'success') : null");
    }

    public function render()
    {
        $query = SmsLog::query();

        if ($q = trim($this->q)) {
            $digits = en_digits($q);
            $query->where(function ($w) use ($q, $digits) {
                $w->where('mobile', 'like', "%{$digits}%")
                    ->orWhere('template_key', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%");
            });
        }

        if (in_array($this->status, ['sent', 'failed'], true)) {
            $query->where('status', $this->status);
        }

        if ($provider = trim($this->provider)) {
            $query->where('provider', $provider);
        }

        if ($from = trim($this->from)) {
            if ($c = jalali_or_iso_to_carbon($from, '00:00')) {
                $query->where('created_at', '>=', $c);
            }
        }

        if ($to = trim($this->to)) {
            if ($c = jalali_or_iso_to_carbon($to, '23:59')) {
                $query->where('created_at', '<=', $c);
            }
        }

        $logs = $query->latest('id')->paginate(25);

        // عنوان فارسی قالب‌ها (یک کوئری)
        $titles = SmsTemplate::query()->pluck('title', 'key');

        return view('livewire.admin.core.sms-logs', [
            'logs' => $logs,
            'titles' => $titles,
            'stats' => [
                'total' => SmsLog::count(),
                'sent' => SmsLog::where('status', 'sent')->count(),
                'failed' => SmsLog::where('status', 'failed')->count(),
                'today' => SmsLog::whereDate('created_at', today())->count(),
            ],
            'provider' => app(SmsManager::class)->driver()->name(),
            'providerLabel' => SmsManager::providers()[app(SmsManager::class)->driver()->name()] ?? app(SmsManager::class)->driver()->name(),
            'providers' => self::knownProviders(),
            'oldCount' => $this->oldCount(),
            'canSystem' => \App\Policies\AdminAccessPolicy::canSection(auth()->user(), 'system'),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'لاگ پیامک‌های ارسال‌شده',
            'breadcrumb' => 'پنل مدیریت کل ← لاگ پیامک‌ها',
            'htmlTitle' => 'لاگ پیامک‌ها',
        ]);
    }

    /* ---------------- داخلی ---------------- */

    private function currentRetention(): int
    {
        return (int) app(SettingsService::class)->get('system.cleanup.sms_logs', 90);
    }

    private function oldCount(): int
    {
        return SmsLog::query()
            ->where('created_at', '<', now()->subDays($this->currentRetention()))
            ->count();
    }

    /** پرووایدرهای شناخته‌شده برای فیلتر (عیناً SmsLogsController) */
    public static function knownProviders(): array
    {
        return [
            'log' => 'لاگ توسعه',
            'kavenegar' => 'کاوه‌نگار',
            'fraasms' => 'فراز اس‌ام‌اس',
            'ippanel' => 'آی‌پی‌پنل',
            'melipayamak' => 'ملی‌پیامک',
            'idehpardazan' => 'ایده‌پردازان',
        ];
    }
}
