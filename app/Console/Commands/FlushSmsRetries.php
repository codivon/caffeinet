<?php

namespace App\Console\Commands;

use App\Models\SmsLog;
use App\Services\Sms\SmsManager;
use App\Services\Settings\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * فاز ۵۴ — ارسال دوبارهٔ پیامک‌های ناموفق (صف ریتری).
 *
 * هر ۵ دقیقه (schedule) پیامک‌های «failed» که:
 *   • features.sms_retry روشن است
 *   • next_retry_at سررسید شده (یا null نیست و گذشته)
 *   • attempts < ۳ (اولین + ۲ تلاش دوباره)
 * را دوباره می‌فرستد — موفق → sent؛ شکست → attempts+1 و +۵ دقیقه
 * فاصلهٔ بعدی؛ بعد از تلاش سوم صف تمام است (رد می‌شود دستی بررسی شود).
 */
class FlushSmsRetries extends Command
{
    protected $signature = 'sms:flush-retries {--limit=20 : سقف پردازش در هر اجرا}';

    protected $description = 'ارسال دوبارهٔ پیامک‌های ناموفق (صف ریتری — حداکثر ۳ تلاش)';

    public function handle(SmsManager $sms, SettingsService $settings): int
    {
        if (! $settings->get('features.sms_retry', true)) {
            $this->line('صف پیامک خاموش است (تنظیمات ← قابلیت‌ها).');

            return self::SUCCESS;
        }

        $rows = SmsLog::query()
            ->where('status', 'failed')
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->where('attempts', '<', SmsManager::MAX_ATTEMPTS)
            ->where('provider', '!=', 'log')
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        if ($rows->isEmpty()) {
            $this->line('پیامکی در صف تلاش دوباره نیست.');

            return self::SUCCESS;
        }

        $sent = 0;
        $rescheduled = 0;
        $abandoned = 0;

        foreach ($rows as $log) {
            // قفل ردیف + جلوگیری از اجرای هم‌زمان
            $claimed = DB::table('sms_logs')
                ->where('id', $log->id)
                ->where('status', 'failed')
                ->update(['next_retry_at' => now()->addSeconds(120), 'attempts' => $log->attempts + 1]);

            if (! $claimed) {
                continue;
            }

            $result = $sms->send($log->mobile, $log->message, $log->template_key);

            if ($result['ok']) {
                $sent++;
                // ردیف جدیدِ موفق ثبت شد — ردیف شکست قبلی به «retried» علامت می‌خورد
                $log->forceFill(['next_retry_at' => null])->save();
            } else {
                $attempts = $log->attempts + 1;

                if ($attempts >= SmsManager::MAX_ATTEMPTS) {
                    $abandoned++;
                    $log->forceFill(['next_retry_at' => null, 'attempts' => SmsManager::MAX_ATTEMPTS])->save();
                } else {
                    $rescheduled++;
                    $log->forceFill(['attempts' => $attempts, 'next_retry_at' => now()->addMinutes(SmsManager::RETRY_DELAY_MINUTES)])->save();
                }
            }
        }

        $this->table(['نتیجه', 'تعداد'], [
            ['ارسال موفق', fa_digits((string) $sent)],
            ['برنامه‌ریزی دوباره', fa_digits((string) $rescheduled)],
            ['منصرف‌شده (۳ تلاش)', fa_digits((string) $abandoned)],
        ]);

        return self::SUCCESS;
    }
}
