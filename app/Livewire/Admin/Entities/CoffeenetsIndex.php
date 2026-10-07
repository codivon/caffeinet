<?php

namespace App\Livewire\Admin\Entities;

use App\Models\Coffeenet;
use App\Models\Organization;
use App\Models\Province;
use App\Models\ReferralSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * کافی‌نت‌ها — فهرست (Task 3-d | مهاجرت Livewire 4)
 *
 * عیناً از App\Http\Controllers\Back\Admin\CoffeenetsController (index/data):
 *   • جستجو (نام/تلفن/سازمان) + فیلتر وضعیت + فیلتر سازمان (۰ = فقط مستقل)
 *   • شمارش مدیرهای فعال + اطلاعات کاربر مدیر برای مودال ویرایش
 *   • صفحه‌بندی ۲۵تایی
 *
 * مودال ایجاد/ویرایش، تغییر وضعیت (PATCH) و حذف نرم هنوز با AJAX به همان
 * اندپوینت‌های کنترلر کار می‌کنند (اعتبارسنجی و پیام‌های فارسی عیناً سرور).
 */
#[Layout('back.layouts.panel')]
class CoffeenetsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $status = '';

    /** عدد مثبت = سازمان مشخص، «0» = فقط مستقل‌ها */
    #[Url(as: 'organization_id', history: true)]
    public string $organizationId = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedOrganizationId(): void
    {
        $this->resetPage();
    }

    /** عیناً مثل index() کنترلر — داده‌های هدر + مودال */
    protected function pageData(): array
    {
        return [
            'organizations' => Organization::orderBy('name')->get(['id', 'name', 'status']),
            'provinces' => Province::orderBy('name')->get(['id', 'name']),
            'referralReward' => (float) ReferralSetting::current()->introduction_reward,
            'referralActive' => (bool) ReferralSetting::current()->is_active,
        ];
    }

    /** عیناً مثل data() کنترلر — ردیف‌های جدول */
    public function render()
    {
        $query = Coffeenet::query()
            ->with(['organization:id,name', 'province:id,name', 'city:id,name'])
            ->withCount([
                'staffAssignments as managers_count' => fn ($q) => $q->where('position', 'manager')->where('is_active', true),
            ]);

        if ($q = trim($this->search)) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereHas('organization', fn ($o) => $o->where('name', 'like', "%{$q}%"));
            });
        }

        if (in_array($this->status, ['pending', 'approved', 'rejected', 'suspended'], true)) {
            $query->where('status', $this->status);
        }

        // فیلتر سازمان: عدد مثبت = سازمان مشخص، 0 = فقط مستقل‌ها
        if ($this->organizationId !== '') {
            $orgId = (int) $this->organizationId;
            if ($orgId > 0) {
                $query->where('organization_id', $orgId);
            } else {
                $query->whereNull('organization_id');
            }
        }

        $paginator = $query->latest('id')->paginate(25);

        $rows = $paginator->through(fn (Coffeenet $c) => $this->mapRow($c));

        return view('livewire.admin.entities.coffeenets.index', [
            'rows' => $rows,
            ...$this->pageData(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'مدیریت کافی‌نت‌ها',
            'breadcrumb' => 'پنل مدیریت کل ← کافی‌نت‌ها',
            'htmlTitle' => 'کافی‌نت‌ها',
        ]);
    }

    /** نگاشت ردیف — عیناً همان فیلدهای JSON کنترلر (data) */
    protected function mapRow(Coffeenet $c): array
    {
        $manager = $c->managerUser();

        return [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'organization' => $c->organization?->name,
            'organization_id' => $c->organization_id,
            'is_independent' => $c->organization_id === null,
            'province' => $c->province?->name,
            'city' => $c->city?->name,
            'address' => $c->address,
            'managers_count' => $c->managers_count,
            'manager' => $manager?->full_name,
            'manager_user_id' => $manager?->id,
            'manager_name' => $manager?->name,
            'manager_family' => $manager?->family,
            'manager_email' => $manager?->email,
            'manager_mobile' => $manager?->mobile,
            'manager_is_active' => (bool) $manager?->is_active,
            'introduction_reward_paid' => $c->introduction_reward_paid,
            'status' => [
                'value' => $c->status->value,
                'label' => $c->status->label(),
                'color' => $c->status->color(),
            ],
            'approved_at' => $c->approved_at?->diffForHumans(now(), ['locale' => 'fa']) ?? '—',
            'created_at' => $c->created_at?->diffForHumans(now(), ['locale' => 'fa']) ?? '—',
        ];
    }
}
