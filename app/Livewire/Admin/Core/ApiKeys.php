<?php

namespace App\Livewire\Admin\Core;

use App\Models\ApiKey;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * فاز ۴۸ — کلیدهای وب‌سرویس (API Keys) — بازطراحی کامل.
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

    public function render()
    {
        $keys = ApiKey::query()
            ->orderByDesc('is_internal')
            ->orderBy('created_at')
            ->get();

        return view('livewire.admin.core.api-keys', [
            'keys'         => $keys,
            'totalHits'    => (int) $keys->sum('requests_count'),
            'lastUsed'     => $keys->filter(fn ($k) => $k->last_used_at)->max('last_used_at'),
        ])->layoutData([
            'user'       => auth()->user(),
            'pageTitle'  => 'کلیدهای وب‌سرویس',
            'breadcrumb' => 'پنل مدیریت کل ← تنظیمات ← کلیدهای وب‌سرویس',
            'htmlTitle'  => 'کلیدهای وب‌سرویس',
        ]);
    }
}
