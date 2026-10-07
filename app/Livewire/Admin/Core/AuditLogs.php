<?php

namespace App\Livewire\Admin\Core;

use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * لاگ فعالیت‌ها — کامپوننت Livewire 4 [Task 3-b]
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\AuditLogsController
 * (index + data) منتقل شده است — به‌جای AJAX، رندر سمت سرور:
 *   • جستجو در action/description/نام و ایمیل کاربر
 *   • فیلتر بازهٔ تاریخ (شمسی یا ISO — با helper jalali_or_iso_to_carbon)
 *   • صفحه‌بندی ۲۵تایی (WithPagination + #[Url])
 *   • مودال جزئیات (old_values/new_values)
 *   • v29 — حذف دوره‌ای: ذخیرهٔ نگهداشت (system.cleanup.audit_logs) +
 *     پاکسازی فوری قدیمی‌ها (system:cleanup --scope=audit_logs) با همان
 *     AuditLogger و پیام‌های فارسی SystemController
 *
 * endpointهای GET admin/audit-logs/data و POST admin/system/* حفظ شده‌اند.
 */
#[Layout('back.layouts.panel')]
class AuditLogs extends Component
{
    use WithPagination;

    /** جستجو: عملیات، توضیح، کاربر */
    #[Url(as: 'q')]
    public string $q = '';

    /** از تاریخ (شمسی Y/m/d یا ISO) */
    #[Url(as: 'from')]
    public string $from = '';

    /** تا تاریخ (شمسی Y/m/d یا ISO) */
    #[Url(as: 'to')]
    public string $to = '';

    /** مقدار نگهداشت (روز) — کارت v29 */
    public int $retentionDays = 365;

    /** لاگ انتخاب‌شده برای مودال جزئیات */
    public ?array $detail = null;

    public function mount(): void
    {
        $this->retentionDays = $this->currentRetention();
    }

    /** تغییر هر فیلتر → صفحه ۱ */
    public function updatedQ(): void
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
        $this->from = '';
        $this->to = '';
        $this->resetPage();
    }

    /** جزئیات یک ردیف (مودال) */
    public function showDetail(int $id): void
    {
        $log = \App\Models\AuditLog::query()->findOrFail($id);

        $this->detail = [
            'action' => $log->action,
            'old' => $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null,
            'new' => $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null,
        ];
    }

    public function closeDetail(): void
    {
        $this->detail = null;
    }

    /** v29 — ذخیرهٔ نگهداشت لاگ فعالیت (همان منطق SystemController@saveRetention) */
    public function saveRetention(): void
    {
        $days = $this->retentionDays;

        if (! is_numeric($days) || (int) $days < 7 || (int) $days > 3650) {
            $this->addError('retention', 'مقدار نگهداشت باید عددی بین ۷ تا ۳۶۵۰ روز باشد.');

            return;
        }

        $days = (int) $days;

        app(SettingsService::class)->updateMany(['system.cleanup.audit_logs' => $days]);

        AuditLogger::log('system.retention.updated', null, null,
            ['audit_logs' => $days], 'بروزرسانی نگهداشت داده‌ها (۱ مورد)');

        $this->retentionDays = $days;
        $this->js("window.App?.toast ? App.toast('نگهداشت لاگ فعالیت ذخیره شد.', 'success') : null");
    }

    /** v29 — پاکسازی فوری قدیمی‌ها (همان منطق SystemController@runCleanup با scope=audit_logs) */
    public function cleanupOld(): void
    {
        $oldCount = $this->oldCount();

        if ($oldCount === 0) {
            $this->js("window.App?.toast ? App.toast('ردیف قدیمی‌تری برای حذف نیست.', 'success') : null");

            return;
        }

        Artisan::call('system:cleanup', ['--scope' => 'audit_logs']);

        AuditLogger::log('system.cleanup.manual', null, null,
            ['--scope' => 'audit_logs'], 'اجرای دستی پاکسازی از پنل (فقط: audit_logs)');

        $this->js("window.App?.toast ? App.toast('پاکسازی اجرا شد؛ ردیف‌های قدیمی حذف شدند.', 'success') : null");
    }

    public function render()
    {
        $query = \App\Models\AuditLog::with('user');

        if ($q = trim($this->q)) {
            $query->where(function ($w) use ($q) {
                $w->where('action', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%"));
            });
        }

        if ($from = trim($this->from)) {
            if ($c = jalali_or_iso_to_carbon($from, '00:00')) {
                $query->whereDate('created_at', '>=', $c->toDateString());
            }
        }

        if ($to = trim($this->to)) {
            if ($c = jalali_or_iso_to_carbon($to, '23:59')) {
                $query->whereDate('created_at', '<=', $c->toDateString());
            }
        }

        $logs = $query->latest('id')->paginate(25);

        return view('livewire.admin.core.audit', [
            'logs' => $logs,
            'oldCount' => $this->oldCount(),
            'canSystem' => \App\Policies\AdminAccessPolicy::canSection(auth()->user(), 'system'),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'لاگ فعالیت‌ها',
            'breadcrumb' => 'پنل مدیریت کل ← لاگ فعالیت‌ها',
            'htmlTitle' => 'لاگ فعالیت',
        ]);
    }

    /* ---------------- داخلی ---------------- */

    private function currentRetention(): int
    {
        return (int) app(SettingsService::class)->get('system.cleanup.audit_logs', 365);
    }

    private function oldCount(): int
    {
        return \App\Models\AuditLog::query()
            ->where('created_at', '<', now()->subDays($this->currentRetention()))
            ->count();
    }
}
