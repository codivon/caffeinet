<?php

namespace App\Livewire\Admin\Core;

use App\Services\Push\PushManager;
use App\Services\Settings\SettingsService;
use App\Services\Sms\SmsManager;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * تنظیمات سیستم (صفحهٔ چند-تبی) — کامپوننت Livewire [Task 3-b]
 *
 * shell صفحه با Livewire رندر می‌شود؛ منطقِ view عیناً از
 * App\Http\Controllers\Back\Admin\SettingsController@edit منتقل شده:
 *   • وضعیت پوشر برای تب Realtime
 *   • آمار نوتیفیکیشن (PushManager + فایل صدا + توکن‌ها)
 *   • v33 خلاصهٔ وضعیت پخش هوشمند (RatingDistributionService)
 *   • v36/v37 وضعیت سلامت کرون جاب + ردپای اجراها
 *
 * ذخیرهٔ گروه‌ها (PUT settings)، تست‌ها (test-sms/pusher/finnotech/push)،
 * آپلود صدا/فایربیس و cron-run همه با همان AJAX قبلی به کنترلر می‌روند —
 * اسکریپت صفحه (settings/index.js) بدون تغییر bind می‌شود.
 */
#[Layout('back.layouts.panel')]
class Settings extends Component
{
    public function render()
    {
        $settings = app(SettingsService::class);

        // وضعیت پوشر برای تب Realtime (محاسبه در کامپوننت — blade تمیز می‌ماند)
        $pusherOn = (bool) $settings->get('realtime.pusher.enabled');
        $pusherReady = $pusherOn
            && trim((string) $settings->get('realtime.pusher.app_key')) !== ''
            && trim((string) $settings->get('realtime.pusher.app_secret')) !== ''
            && trim((string) $settings->get('realtime.pusher.app_id')) !== '';

        return view('livewire.admin.core.settings', [
            'settings' => $settings,
            'providers' => SmsManager::providers(),
            'referral' => \App\Models\ReferralSetting::current(),
            'pusherOn' => $pusherOn,
            'pusherReady' => $pusherReady,
            'notificationStats' => $this->notificationStats($settings),
            // v33 — خلاصهٔ وضعیت پخش هوشمند (برای hint زندهٔ تب نظرسنجی)
            'ratingSummary' => app(\App\Services\Orders\RatingDistributionService::class)->summary(),
            // v36 — وضعیت کرون جاب (نشانگر قرمز/سبز تنظیمات عمومی)
            'cronStatus' => $this->cronStatus(),
            // ظاهر و رنگ‌بندی — پالت‌ها + انتخاب فعلی هر پنل + توکن‌های شخصی
            // (همهٔ محاسبات در PHP انجام می‌شود تا Blade ساده بماند)
            'appearance' => \App\Support\Appearance::settingsViewData(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'تنظیمات سیستم',
            'breadcrumb' => 'پنل مدیریت کل ← تنظیمات',
            'htmlTitle' => 'تنظیمات',
        ]);
    }

    /**
     * v36 → v37 — وضعیت سلامت کرون جاب (عیناً از SettingsController).
     */
    private function cronStatus(): array
    {
        $last = \App\Models\Setting::query()
            ->where('key', \App\Support\CronHeartbeat::KEY)
            ->value('value');

        $lastAt = $last ? \Illuminate\Support\Carbon::parse($last) : null;
        $healthy = $lastAt !== null
            && $lastAt->gt(now()->subSeconds(\App\Support\CronHeartbeat::HEALTHY_WINDOW));

        return [
            'healthy' => $healthy,
            'last' => $lastAt,
            'age' => $lastAt ? max(0, (int) $lastAt->diffInSeconds(now())) : null,
            // v37 — آخرین ردپاها (حداکثر ۵ خط) برای عیب‌یابی حالت قرمز
            'trace' => \App\Support\CronHeartbeat::traceTail(5),
        ];
    }

    /** آمار تب اعلان‌ها (عیناً از SettingsController::notificationStats). */
    private function notificationStats(SettingsService $settings): array
    {
        $push = app(PushManager::class);
        $soundFile = trim((string) $settings->get('notification.sound.file', ''));

        return [
            'sound_custom' => $soundFile !== '' ? [
                'name' => basename($soundFile),
                'url' => media_url('sounds/'.$soundFile),
            ] : null,
            'push_provider' => $push->provider(),
            'push_enabled' => $push->enabled(),
            'push_provider_label' => $push->providerLabel(),
            'webpush_public' => $push->webpush()->publicKey(),
            'beams_ready' => $push->beams()->ready(),
            'firebase_ready' => $push->provider() === 'firebase'
                && trim((string) $settings->get('notification.push.firebase.sender_id')) !== ''
                && trim((string) $settings->get('notification.push.firebase.api_key')) !== '',
            'push_tokens' => \App\Models\PushToken::query()->count(),
        ];
    }
}
