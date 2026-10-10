<?php

namespace App\Http\Controllers\Back\Admin;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\Core\ApiKeys;
use App\Livewire\Admin\Core\AuditLogs;
use App\Livewire\Admin\Core\PanelSessions;
use App\Livewire\Admin\Core\SmsLogs;
use App\Models\SmsTemplate;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * فاز ۶۰ — خروجی CSV صفحات عملیاتی پنل مدیریت کل (بستهٔ سرعت و اعتماد).
 *
 * ۴ خروجی با «فیلتر فعال خود صفحه» (همان الگوی AnalyticsController/FinanceController
 * با App\Support\Csv و BOM):
 *
 *   ۱) exports/sms-logs        ← صفحهٔ لاگ پیامک‌ها (SmsLogs) — q/status/provider/from/to
 *   ۲) exports/audit-logs      ← صفحهٔ لاگ فعالیت‌ها (AuditLogs) — q/from/to
 *   ۳) exports/panel-sessions  ← صفحهٔ نشست‌های فعال (PanelSessions) — نشست‌های فعلی
 *   ۴) exports/api-usage       ← اینسایت مصرف کلیدها در صفحهٔ کلیدهای وب‌سرویس — ۳۰ روز اخیر
 *
 * منطق فیلتر در خودِ کامپوننت‌ها است (SmsLogs::filterQuery / AuditLogs::filterQuery /
 * PanelSessions::activeSessions / ApiKeys::apiUsersInsight) — این کنترلر فقط همان را
 * صدا می‌زند تا فیلتر صفحه و فایل CSV هرگز از هم جدا نشوند.
 *
 * سقف ردیف: ۵۰۰۰ ردیف با ردیف هینت پایانی («فقط ۵۰۰۰ ردیف اول») تا روی
 * هاست اشتراکی فایل سنگین نشود.
 *
 * روت‌ها: routes/panels/admin/exports.php — زیر میدل‌ویر admin.access (فقط ادمین).
 */
class CsvExportsController extends Controller
{
    /** سقف ردیف هر خروجی — جلوگیری از سنگین‌شدن هاست اشتراکی */
    private const MAX_ROWS = 5000;

    /** GET admin/exports/sms-logs — با همان فیلترهای صفحهٔ لاگ پیامک */
    public function smsLogs(Request $request): StreamedResponse
    {
        $rows = SmsLogs::filterQuery(
            (string) $request->query('q', ''),
            (string) $request->query('status', ''),
            (string) $request->query('provider', ''),
            (string) $request->query('from', ''),
            (string) $request->query('to', ''),
        )
            ->latest('id')
            ->limit(self::MAX_ROWS + 1)
            ->get();

        $titles = SmsTemplate::query()->pluck('title', 'key');
        $providers = SmsLogs::knownProviders();

        [$rows, $truncated] = self::cap($rows);

        return Csv::download('sms-logs-'.now()->format('Y-m-d').'.csv', [
            __('chrome.csv.sms.mobile'),
            __('chrome.csv.sms.template'),
            __('chrome.csv.sms.message'),
            __('chrome.csv.sms.provider'),
            __('chrome.csv.sms.status'),
            __('chrome.csv.sms.time'),
        ], function () use ($rows, $truncated, $titles, $providers) {
            foreach ($rows as $log) {
                yield [
                    (string) $log->mobile,
                    $log->template_key ? ($titles[$log->template_key] ?? $log->template_key) : '—',
                    (string) $log->message,
                    $providers[$log->provider] ?? $log->provider,
                    $log->status === 'sent' ? __('chrome.csv.sms.sent') : __('chrome.csv.sms.failed'),
                    $log->created_at ? fa_date($log->created_at, 'Y/m/d H:i') : '—',
                ];
            }

            if ($truncated) {
                yield self::truncatedRow();
            }
        });
    }

    /** GET admin/exports/audit-logs — با همان فیلترهای صفحهٔ لاگ فعالیت */
    public function auditLogs(Request $request): StreamedResponse
    {
        $rows = AuditLogs::filterQuery(
            (string) $request->query('q', ''),
            (string) $request->query('from', ''),
            (string) $request->query('to', ''),
        )
            ->latest('id')
            ->limit(self::MAX_ROWS + 1)
            ->get();

        [$rows, $truncated] = self::cap($rows);

        return Csv::download('audit-logs-'.now()->format('Y-m-d').'.csv', [
            __('chrome.csv.audit.user'),
            __('chrome.csv.audit.action'),
            __('chrome.csv.audit.description'),
            __('chrome.csv.audit.entity'),
            __('chrome.csv.audit.ip'),
            __('chrome.csv.audit.time'),
        ], function () use ($rows, $truncated) {
            foreach ($rows as $log) {
                yield [
                    $log->user?->full_name ?: 'سیستم',
                    (string) $log->action,
                    (string) ($log->description ?? ''),
                    $log->auditable_type ? class_basename($log->auditable_type).'#'.$log->auditable_id : '—',
                    (string) ($log->ip ?? '—'),
                    $log->created_at ? fa_date($log->created_at, 'Y/m/d H:i') : '—',
                ];
            }

            if ($truncated) {
                yield self::truncatedRow();
            }
        });
    }

    /** GET admin/exports/panel-sessions — نشست‌های فعلی همان ادمین (فیلتر ندارد) */
    public function panelSessions(Request $request): StreamedResponse
    {
        $rows = PanelSessions::activeSessions();

        return Csv::download('panel-sessions-'.now()->format('Y-m-d').'.csv', [
            __('chrome.csv.sessions.device'),
            __('chrome.csv.sessions.browser'),
            __('chrome.csv.sessions.ip'),
            __('chrome.csv.sessions.last_activity'),
            __('chrome.csv.sessions.current'),
        ], function () use ($rows) {
            foreach ($rows as $s) {
                yield [
                    (string) $s->device,
                    (string) $s->browser,
                    (string) $s->ip,
                    (string) $s->last,
                    $s->is_current ? __('chrome.common.yes') : __('chrome.common.no'),
                ];
            }
        });
    }

    /** GET admin/exports/api-usage — مصرف ۳۰ روز اخیر به‌ازای کاربر (ستون‌های جدول «کاربران مصرف‌کننده») */
    public function apiUsage(): StreamedResponse
    {
        $rows = ApiKeys::apiUsersInsight();

        return Csv::download('api-usage-'.now()->format('Y-m-d').'.csv', [
            __('chrome.csv.usage.user'),
            __('chrome.csv.usage.mobile'),
            __('chrome.csv.usage.hits'),
            __('chrome.csv.usage.ips'),
            __('chrome.csv.usage.last_used'),
        ], function () use ($rows) {
            foreach ($rows as $u) {
                yield [
                    (string) $u->name,
                    (string) ($u->mobile ?? '—'),
                    (int) $u->hits,
                    (int) $u->ips,
                    $u->last_at ? fa_date($u->last_at, 'Y/m/d H:i') : '—',
                ];
            }
        });
    }

    /* ---------------- داخلی ---------------- */

    /**
     * بریدن به سقف ۵۰۰۰ ردیف + هینت پایانی.
     *
     * @param  Collection  $rows  با limit(MAX_ROWS + 1) خوانده شده
     * @return array{0: Collection, 1: bool}  [ردیف‌های مجاز، بریده‌شده؟]
     */
    private static function cap(Collection $rows): array
    {
        if ($rows->count() <= self::MAX_ROWS) {
            return [$rows, false];
        }

        return [$rows->take(self::MAX_ROWS), true];
    }

    /** ردیف هینت «فقط ۵۰۰۰ ردیف اول» */
    private static function truncatedRow(): array
    {
        return [__('chrome.csv.rows_note')];
    }
}
