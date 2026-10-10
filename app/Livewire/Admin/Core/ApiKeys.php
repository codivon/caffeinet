<?php

namespace App\Livewire\Admin\Core;

use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\Webhook;
use App\Services\Settings\SettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * فاز ۴۸ — کلیدهای وب‌سرویس (API Keys) — بازطراحی کامل.
 * فاز ۵۱ — + اینسایت مصرف API (چه کسی/چه دستگاهی/چه IP) + مدیریت وب‌هوک‌ها.
 *
 * مسیر: تنظیمات ← آخرین منو «کلیدهای وب‌سرویس» (از فاز ۴۸ از سایدبار
 * حذف شد). افزودن کلید فقط با مودال انجام می‌شود و «عنوان» الزامی است؛
 * مقدار کلید همیشه خودکار تولید می‌شود (cnk_ + ۴۰ نویسهٔ تصادفی) و
 * پس از ساخت در همان مودال با دکمهٔ کپی نمایش داده می‌شود. حذف هم با
 * مودال تأیید (PanelUI.confirm) انجام می‌شود.
 *
 * کلیدها قابل ویرایش نیستند — اگر لو رفتند فقط حذف و کلید جدید بسازید.
 * هر درخواست API باید هدر «X-Api-Key» را با یکی از این کلیدها بفرستد
 * (میدل‌ویر api.key). کلید «داخلی» (is_internal) کلید خودِ اپ وب/پنل‌هاست
 * و از UI حذف‌شدنی نیست؛ اگر دستی از دیتابیس پاک شود،
 * ApiKey::internalKey() آن را خودکار بازسازی می‌کند.
 *
 * وب‌هوک‌ها (فاز ۵۱): مدیریت اشتراک رویداد — URL + رمز امضا + رویدادها؛
 * ارسال با WebhookDispatcher (امضای HMAC) + لاگ آخرین وضعیت.
 *
 * GET admin/api-keys (این کامپوننت).
 */
#[Layout('back.layouts.panel')]
class ApiKeys extends Component
{
    /** مودال «کلید جدید» باز است؟ */
    public bool $addOpen = false;

    /** عنوان کلید جدید (الزامی — در مودال وارد می‌شود) */
    public string $newName = '';

    /** کلید تازه‌ساخته‌شده — برای صفحهٔ موفقیت مودال (کپی یک‌باره) */
    public ?array $createdKey = null;

    /* ---------- فاز ۵۱ — وب‌هوک ---------- */

    /** مودال «وب‌هوک جدید» باز است؟ */
    public bool $whOpen = false;

    /** آدرس وب‌هوک جدید */
    public string $whUrl = '';

    /** رویدادهای انتخابی وب‌هوک جدید */
    public array $whEvents = ['order.created'];

    /** وب‌هوکی که رمزش تازه ساخته شده (نمایش یک‌باره) */
    public ?array $createdWh = null;

    protected function rules(): array
    {
        return [
            'newName' => ['required', 'string', 'min:2', 'max:60'],
        ];
    }

    protected $messages = [
        'newName.required' => 'عنوان کلید الزامی است — مثلاً «اپ موبایل همکار».',
        'newName.min'      => 'عنوان کلید حداقل ۲ نویسه است.',
        'newName.max'      => 'عنوان کلید حداکثر ۶۰ نویسه است.',
    ];

    /** بازکردن مودال «کلید جدید» — همیشه با فرم تمیز */
    public function openAdd(): void
    {
        $this->reset('newName', 'createdKey');
        $this->resetErrorBag();
        $this->addOpen = true;
    }

    /** بستن مودال — فرم و صفحهٔ موفقیت هم پاک می‌شوند */
    public function closeAdd(): void
    {
        $this->addOpen = false;
        $this->reset('newName', 'createdKey');
        $this->resetErrorBag();
    }

    /** ساخت کلید — عنوان الزامی؛ مقدار خودکار تولید می‌شود */
    public function addKey(): void
    {
        $this->validate();

        $key = ApiKey::create([
            'name' => trim($this->newName),
            'key'  => ApiKey::generate(),
        ]);

        $this->createdKey = ['name' => $key->name, 'key' => $key->key];
        $this->reset('newName');
    }

    /** حذف کلید — پس از مودال تأیید (PanelUI.confirm) صدا زده می‌شود */
    public function deleteKey(int $id): void
    {
        $key = ApiKey::findOrFail($id);

        if ($key->is_internal) {
            session()->flash('ak-err', 'کلید داخلی وب‌اپ قابل حذف نیست.');
            return;
        }

        $key->delete();

        session()->flash('ak-ok', 'کلید «' . ($key->name ?: 'بدون عنوان') . '» حذف شد؛ درخواست‌های دارای آن کلید دیگر پذیرفته نمی‌شوند.');
    }

    /* ==================== فاز ۵۱ — وب‌هوک‌ها ==================== */

    /** باز کردن مودال «وب‌هوک جدید» */
    public function openWh(): void
    {
        $this->reset('whUrl', 'whEvents', 'createdWh');
        $this->resetErrorBag();
        $this->whOpen = true;
    }

    public function closeWh(): void
    {
        $this->whOpen = false;
        $this->reset('whUrl', 'whEvents', 'createdWh');
        $this->resetErrorBag();
    }

    /** ذخیره وب‌هوک جدید — URL + حداقل یک رویداد؛ رمز خودکار ساخته می‌شود */
    public function addWebhook(): void
    {
        $this->validate([
            'whUrl'    => ['required', 'url', 'max:500'],
            'whEvents' => ['required', 'array', 'min:1'],
        ], [
            'whUrl.required' => 'آدرس مقصد وب‌هوک الزامی است.',
            'whUrl.url'      => 'آدرس باید یک URL کامل معتبر باشد (https://…).',
            'whEvents.min'   => 'حداقل یک رویداد را انتخاب کنید.',
        ]);

        // فقط رویدادهای شناخته‌شده
        $events = array_values(array_intersect(
            $this->whEvents,
            array_keys(\App\Services\Webhooks\WebhookDispatcher::EVENTS),
        ));

        $secret = 'whk_' . bin2hex(random_bytes(20));

        $hook = Webhook::create([
            'url'       => trim($this->whUrl),
            'secret'    => $secret,
            'events'    => $events,
            'is_active' => true,
        ]);

        $this->createdWh = ['url' => $hook->url, 'secret' => $secret];
        $this->reset('whUrl', 'whEvents');
    }

    /** فعال/غیرفعال کردن وب‌هوک */
    public function toggleWebhook(int $id): void
    {
        $hook = Webhook::findOrFail($id);
        $hook->update(['is_active' => ! $hook->is_active]);
    }

    /** حذف وب‌هوک — پس از تأیید */
    public function deleteWebhook(int $id): void
    {
        Webhook::findOrFail($id)->delete();

        session()->flash('ak-ok', 'وب‌هوک حذف شد؛ دیگر رویدادی به آن ارسال نمی‌شود.');
    }

    /** تست وب‌هوک — رویداد آزمایشی ping می‌فرستد و نتیجه را فلش می‌کند */
    public function testWebhook(int $id): void
    {
        $hook = Webhook::findOrFail($id);

        $ok = \App\Services\Webhooks\WebhookDispatcher::send($hook, 'order.created', [
            'id'           => 0,
            'order_number' => 'TEST-PING',
            'test'         => true,
            'message'      => 'این یک رویداد آزمایشی از پنل کافی‌نت آنلاین است.',
        ]);

        session()->flash(
            $ok ? 'ak-ok' : 'ak-err',
            $ok
                ? 'رویداد آزمایشی با موفقیت ارسال شد (کد ' . $hook->fresh()->last_status_code . ').'
                : 'ارسال آزمایشی ناموفق بود — لاگ آخرین ارسال را در همان کارت ببینید.',
        );
    }

    public function render()
    {
        $keys = ApiKey::query()
            ->orderByDesc('is_internal')
            ->orderBy('created_at')
            ->get();

        /* فاز ۵۱ — اینسایت مصرف (۳۰ روز اخیر) */
        $usageLogOn = (bool) app(SettingsService::class)->get('features.api_usage_log', true);

        $usage = null;
        $apiUsers = collect();

        if ($usageLogOn) {
            $since = now()->subDays(30);

            $usage = [
                'requests_24h' => ApiUsageLog::query()->where('created_at', '>=', now()->subDay())->count(),
                'unique_users' => ApiUsageLog::query()->where('created_at', '>=', $since)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'unique_ips'   => ApiUsageLog::query()->where('created_at', '>=', $since)->distinct('ip')->count('ip'),
                'devices'      => ApiUsageLog::query()
                    ->where('created_at', '>=', $since)
                    ->get(['user_agent'])
                    ->map(fn ($l) => self::deviceLabel((string) $l->user_agent))
                    ->countBy()
                    ->sortDesc()
                    ->take(4),
                'errors_24h' => ApiUsageLog::query()->where('created_at', '>=', now()->subDay())->where('status_code', '>=', 400)->count(),
            ];

            /* چه کسانی از API استفاده می‌کنند؟ — برترین کاربران ۳۰ روز */
            $apiUsers = ApiUsageLog::query()
                ->where('created_at', '>=', $since)
                ->whereNotNull('user_id')
                ->selectRaw('user_id, COUNT(*) as hits, MAX(created_at) as last_at, COUNT(DISTINCT ip) as ips')
                ->groupBy('user_id')
                ->orderByDesc('hits')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    $u = \App\Models\User::find($row->user_id);

                    return (object) [
                        'id'      => $row->user_id,
                        'name'    => $u ? trim(($u->name ?? '').' '.($u->family ?? '')) ?: 'کاربر #'.$u->id : 'کاربر حذف‌شده',
                        'mobile'  => $u?->mobile,
                        'hits'    => (int) $row->hits,
                        'ips'     => (int) $row->ips,
                        'last_at' => $row->last_at,
                    ];
                });
        }

        $webhooks = Webhook::query()->orderBy('id')->get();

        return view('livewire.admin.core.api-keys', [
            'keys'         => $keys,
            'totalHits'    => (int) $keys->sum('requests_count'),
            'lastUsed'     => $keys->filter(fn ($k) => $k->last_used_at)->max('last_used_at'),
            // فاز ۵۱
            'usageLogOn'   => $usageLogOn,
            'usage'        => $usage,
            'apiUsers'     => $apiUsers,
            'webhooks'     => $webhooks,
            'whEventsList' => \App\Services\Webhooks\WebhookDispatcher::EVENTS,
        ])->layoutData([
            'user'       => auth()->user(),
            'pageTitle'  => 'کلیدهای وب‌سرویس',
            'breadcrumb' => 'پنل مدیریت کل ← تنظیمات ← کلیدهای وب‌سرویس',
            'htmlTitle'  => 'کلیدهای وب‌سرویس',
        ]);
    }

    /** برچسب دستگاه از User-Agent (فشرده) */
    private static function deviceLabel(string $ua): string
    {
        if ($ua === '') {
            return 'ناشناس';
        }

        if (preg_match('/iPhone|iPad/i', $ua)) {
            return 'آیفون/آیپد';
        }

        if (stripos($ua, 'Android') !== false) {
            return 'اندروید';
        }

        if (stripos($ua, 'Windows') !== false) {
            return 'ویندوز';
        }

        if (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) {
            return 'مک';
        }

        if (stripos($ua, 'Linux') !== false) {
            return 'لینوکس';
        }

        return preg_match('/Mobi/i', $ua) === 1 ? 'موبایل' : 'سایر';
    }
}
