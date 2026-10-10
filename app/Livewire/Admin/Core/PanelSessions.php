<?php

namespace App\Livewire\Admin\Core;

use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * فاز ۵۰ — نشست‌های فعال (دستگاه‌های واردشده) — از «تنظیمات» باز می‌شود.
 *
 * درایور session پایه «database» است؛ پس هر ردیف جدول sessions = یک
 * دستگاه/مرورگر واردشده با همین حساب. لیست نشست‌های کاربرِ فعلی +
 * علامت «این دستگاه» + دکمهٔ «خروج از سایر دستگاه‌ها» (حذف ردیف‌های
 * دیگر → کاربر در آن دستگاه‌ها در اولین درخواست لاگ‌اوت می‌شود).
 *
 * GET admin/sessions (این کامپوننت) — نام: admin.sessions.index
 */
#[Layout('back.layouts.panel')]
class PanelSessions extends Component
{
    /** خروج از همهٔ دستگاه‌های دیگر (نشست فعلی حفظ می‌شود) */
    public function logoutOthers(): void
    {
        $currentId = request()->session()->getId();
        $userId = auth()->id();

        $count = DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentId)
            ->delete();

        AuditLogger::log('auth.sessions_logout_others', auth()->user(), null,
            ['count' => $count], 'خروج از سایر دستگاه‌ها (نشست‌های فعال)');

        session()->flash('ps-ok', $count > 0
            ? "از {$count} دستگاه دیگر خارج شدید."
            : 'دستگاه دیگری وارد نشده بود.');
    }

    /** فهرست نشست‌های کاربر فعلی با پارس دستگاه */
    public function render()
    {
        $currentId = request()->session()->getId();
        $userId = auth()->id();

        $rows = DB::table('sessions')
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->limit(50)
            ->get()
            ->map(function ($row) use ($currentId) {
                $ua = (string) ($row->user_agent ?? '');

                return (object) [
                    'id' => $row->id,
                    'is_current' => $row->id === $currentId,
                    'ip' => $row->ip_address ?: '—',
                    'device' => self::deviceLabel($ua),
                    'browser' => self::browserLabel($ua),
                    'last' => fa_date(\Illuminate\Support\Carbon::createFromTimestamp((int) $row->last_activity)),
                    'last_ts' => (int) $row->last_activity,
                ];
            });

        return view('livewire.admin.core.panel-sessions', [
            'sessions' => $rows,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'نشست‌های فعال',
            'breadcrumb' => 'پنل مدیریت کل ← تنظیمات ← نشست‌های فعال',
            'htmlTitle' => 'نشست‌های فعال',
        ]);
    }

    /** «لپ‌تاپ ویندوزی» / «موبایل اندروید» / ... */
    private static function deviceLabel(string $ua): string
    {
        if ($ua === '') {
            return 'ناشناس';
        }

        $mobile = preg_match('/Mobi|Android|iPhone|iPad/i', $ua) === 1;

        if (preg_match('/iPhone|iPad/i', $ua)) {
            return $mobile ? 'آیفون/آیپد' : 'دستگاه اپل';
        }

        if (stripos($ua, 'Android') !== false) {
            return 'موبایل اندروید';
        }

        if (stripos($ua, 'Windows') !== false) {
            return $mobile ? 'مرورگر موبایل ویندوز' : 'کامپیوتر ویندوزی';
        }

        if (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) {
            return 'کامپیوتر مک';
        }

        if (stripos($ua, 'Linux') !== false) {
            return 'کامپیوتر لینوکسی';
        }

        return $mobile ? 'دستگاه موبایل' : 'دستگاه ناشناس';
    }

    /** نام مرورگر از User-Agent */
    private static function browserLabel(string $ua): string
    {
        if ($ua === '') {
            return '—';
        }

        if (stripos($ua, 'Edg/') !== false) {
            return 'Edge';
        }

        if (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) {
            return 'Opera';
        }

        if (stripos($ua, 'Chrome') !== false) {
            return 'Chrome';
        }

        if (stripos($ua, 'Firefox') !== false) {
            return 'Firefox';
        }

        if (stripos($ua, 'Safari') !== false) {
            return 'Safari';
        }

        return 'مرورگر دیگر';
    }
}
