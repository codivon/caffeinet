<?php

namespace App\Livewire\Org\Coffeenets;

use App\Livewire\Org\Concerns\InteractsWithOrgContext;
use App\Models\Organization;
use App\Models\ReferralSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «کافی‌نت‌های من» سازمان — نسخهٔ Livewire 4 [Task 6]
 *
 * منطق عیناً از App\Http\Controllers\Back\Org\CoffeenetsController (index/data)
 * منتقل شده است:
 *   • بنر پاداش معرفی (ReferralSetting::current) + استان‌ها برای مودال معرفی
 *   • لیست کافی‌نت‌های زیرمجموعه: جستجو (نام/تلفن) + فیلتر وضعیت
 *     (pending/approved/rejected/suspended) + مرتب‌سازی نزولی id + صفحه‌بندی ۲۵تایی
 *   • سریال‌سازی ردیف‌ها عیناً data() (نام/تلفن/استان/شهر/پاداش/وضعیت/تاریخ‌ها)
 *
 * معرفی کافی‌نت (store — POST org.coffeenets.store) و سلکت آبشاری شهرها
 * (GET org.geo.cities) همان endpointهای کنترلر می‌مانند — فرم مودال با
 * App.ajax همان مسیر قبلی را صدا می‌زند (نگاشت خطای ۴۲۲ مثل نسخهٔ JS).
 */
#[Layout('back.layouts.org')]
class Index extends Component
{
    use InteractsWithOrgContext;
    use WithPagination;

    /** جستجو: نام کافی‌نت یا تلفن */
    #[Url(as: 'q')]
    public string $q = '';

    /** فیلتر وضعیت: '' | pending | approved | rejected | suspended */
    #[Url(as: 'status')]
    public string $status = '';

    /** مودال معرفی کافی‌نت — با هر بازشدن فرم تازه رندر می‌شود (مثل closeModal قبلی) */
    public bool $modalOpen = false;

    public function openModal(): void
    {
        $this->modalOpen = true;
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['q', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render(): \Illuminate\View\View
    {
        /** @var Organization|null $org */
        $org = $this->resolveOrgContext();

        // زمینهٔ سازمان نامعتبر است (ریدایرکت choose/login ثبت شده) — رندر خنثی بدون کوئری
        if (! $org) {
            return $this->neutralRender();
        }

        /* ---------- لیست کافی‌نت‌های زیرمجموعه — عیناً data() کنترلر ---------- */
        $query = $org->coffeenets()
            ->with(['province:id,name', 'city:id,name']);

        if ($q = trim($this->q)) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%"));
        }

        if ($status = (string) $this->status) {
            if (in_array($status, ['pending', 'approved', 'rejected', 'suspended'], true)) {
                $query->where('status', $status);
            }
        }

        $paginator = $query->latest('id')->paginate(25);

        $rows = $paginator->through(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'province' => $c->province?->name,
            'city' => $c->city?->name,
            'address' => $c->address,
            'reward_paid' => $c->introduction_reward_paid,
            'status' => [
                'value' => $c->status->value,
                'label' => $c->status->label(),
                'color' => $c->status->color(),
            ],
            'created_at' => $c->created_at ? jdate($c->created_at)->format('Y/m/d') : '—',
            'approved_at' => $c->approved_at ? jdate($c->approved_at)->format('Y/m/d') : null,
        ]);

        return view('livewire.org.coffeenets.index', [
            'organization' => $org,
            'referral' => ReferralSetting::current(),
            'provinces' => \App\Models\Province::orderBy('name')->get(['id', 'name']),
            'rows' => $rows,
        ])->layoutData([
            'organization' => $org,
            'user' => auth()->user(),
            'pageTitle' => 'کافی‌نت‌های من',
            'breadcrumb' => 'پنل سازمان ← کافی‌نت‌های من',
            'htmlTitle' => 'کافی‌نت‌های من',
        ]);
    }

    /** رندر خنثی — فقط وقتی زمینهٔ سازمان نامعتبر شد (بدون هیچ کوئری اضافه) */
    protected function neutralRender(): \Illuminate\View\View
    {
        $org = static::neutralOrganization();

        return view('livewire.org.coffeenets.index', [
            'organization' => $org,
            'referral' => new \App\Models\ReferralSetting(['introduction_reward' => 0, 'is_active' => false]),
            'provinces' => collect(),
            'rows' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25),
        ])->layoutData([
            'organization' => $org,
            'user' => auth()->user(),
            'pageTitle' => 'کافی‌نت‌های من',
            'breadcrumb' => 'پنل سازمان ← کافی‌نت‌های من',
            'htmlTitle' => 'کافی‌نت‌های من',
        ]);
    }
}
