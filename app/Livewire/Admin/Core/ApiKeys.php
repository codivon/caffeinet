<?php

namespace App\Livewire\Admin\Core;

use App\Models\ApiKey;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * فاز ۴۷ — کلیدهای وب‌سرویس (API Keys) — پنل مدیریت.
 *
 * افزودن/حذف کلید برای دسترسی به /api/v1. مقدار کلیدها خودکار
 * تولید می‌شود (cnk_ + ۴۰ نویسهٔ تصادفی) و قابل ویرایش نیست —
 * اگر لو رفت فقط حذف و کلید جدید بسازید. هر درخواست API باید
 * هدر «X-Api-Key» را با یکی از این کلیدها بفرستد (VerifyApiKey).
 *
 * کلید «داخلی» (is_internal) کلید خودِ اپ وب/پنل‌هاست و از UI حذف‌شدنی
 * نیست؛ اگر دستی از دیتابیس پاک شود، ApiKey::internalKey() آن را
 * خودکار بازسازی می‌کند.
 *
 * GET admin/api-keys (این کامپوننت).
 */
#[Layout('back.layouts.panel')]
class ApiKeys extends Component
{
    /** برچسب دلخواه کلید جدید (اختیاری) */
    public string $newName = '';

    protected function rules(): array
    {
        return [
            'newName' => ['nullable', 'string', 'max:60'],
        ];
    }

    protected $messages = [
        'newName.max' => 'برچسب حداکثر ۶۰ نویسه است.',
    ];

    /** ساخت کلید جدید — مقدار خودکار تولید می‌شود */
    public function addKey(): void
    {
        $this->validate();

        $key = ApiKey::create([
            'name' => trim($this->newName) !== '' ? trim($this->newName) : null,
            'key'  => ApiKey::generate(),
        ]);

        $this->reset('newName');

        session()->flash('ak-ok', 'کلید «' . ($key->name ?: 'بدون برچسب') . '» ساخته شد — از دکمهٔ کپی بردارید.');
    }

    /** حذف کلید — کلید داخلی وب‌اپ حذف‌شدنی نیست */
    public function deleteKey(int $id): void
    {
        $key = ApiKey::findOrFail($id);

        if ($key->is_internal) {
            $this->addError('newName', 'کلید داخلی وب‌اپ قابل حذف نیست.');
            return;
        }

        $key->delete();

        session()->flash('ak-ok', 'کلید حذف شد؛ درخواست‌های دارای آن کلید دیگر پذیرفته نمی‌شوند.');
    }

    public function render()
    {
        return view('livewire.admin.core.api-keys', [
            'keys' => ApiKey::query()
                ->orderByDesc('is_internal')
                ->orderBy('created_at')
                ->get(),
        ])->layoutData([
            'user'       => auth()->user(),
            'pageTitle'  => 'کلیدهای وب‌سرویس',
            'breadcrumb' => 'پنل مدیریت کل ← کلیدهای وب‌سرویس',
            'htmlTitle'  => 'کلیدهای وب‌سرویس',
        ]);
    }
}
