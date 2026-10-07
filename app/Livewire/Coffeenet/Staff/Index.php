<?php

namespace App\Livewire\Coffeenet\Staff;

use App\Models\Coffeenet;
use App\Models\StaffAssignment;
use App\Support\OperatorPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «کارمندان» پنل کافی‌نت — نسخهٔ Livewire 4 (Task 4).
 *
 * منطق عیناً از App\Http\Controllers\Back\Coffeenet\StaffController (index/data)
 * منتقل شده است:
 *   • فیلتر جستجو (نام/نام خانوادگی/ایمیل/موبایل) + سمت + وضعیت — همان queryهای data()
 *   • مرتب‌سازی نزولی id و صفحه‌بندی ۲۵تایی
 *   • سریال‌سازی ردیف‌ها عیناً مثل data() (موقعیت/حقوق/دسترسی‌ها/وضعیت تایید)
 *
 * store/update/toggle طبق قرارداد Task 4 همان endpointهای کنترلر می‌مانند:
 * مودال افزودن/ویرایش با همان فیلدها و همان اعتبارسنجی سمت کلاینت (Alpine) از
 * طریق App.ajax به coffeenet.staff.store / staff.update می‌رود و فعال/غیرفعال‌سازی
 * با PanelUI.confirm به coffeenet.staff.toggle — رفتار و پیام‌ها عیناً مثل index.js قبلی.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    public Coffeenet $coffeenet;

    /** جستجو: نام / خانوادگی / ایمیل / موبایل */
    #[Url(as: 'q')]
    public string $q = '';

    /** فیلتر سمت: '' | manager | operator */
    #[Url(as: 'position')]
    public string $position = '';

    /** فیلتر وضعیت: '' | 1 | 0 (مثل select قبلی) */
    #[Url(as: 'status')]
    public string $status = '';

    public function mount(Coffeenet $coffeenet): void
    {
        $this->coffeenet = $coffeenet;
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['q', 'position', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render(): \Illuminate\View\View
    {
        $coffeenet = $this->coffeenet;

        $query = StaffAssignment::query()
            ->where('coffeenet_id', $coffeenet->id)
            ->with([
                'user:id,name,family,email,mobile,last_login_at,is_active',
                'salarySetting' => fn ($q) => $q->where('salary_settings.coffeenet_id', $coffeenet->id),
            ]);

        if ($q = trim($this->q)) {
            $query->whereHas('user', function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('family', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%");
            });
        }

        if ($position = (string) $this->position) {
            if (in_array($position, ['manager', 'operator'], true)) {
                $query->where('position', $position);
            }
        }

        if ($this->status !== '' && $this->status !== null) {
            // در select قدیمی «در انتظار تایید» نبود؛ پشتیبانی از ۱/۰ مثل data()
            $query->where('is_active', $this->status === '1');
        }

        $rows = $query->orderByDesc('id')->paginate(25);

        $rows->through(function (StaffAssignment $s) {
            $salary = $s->salarySetting;

            return [
                'id' => $s->id,
                'name' => $s->user->name,
                'family' => $s->user->family,
                'full_name' => $s->user->full_name,
                'email' => $s->user->email,
                'mobile' => $s->user->mobile,
                'position' => [
                    'value' => $s->position->value,
                    'label' => $s->position->label(),
                ],
                'is_active' => $s->is_active,
                'user_active' => (bool) $s->user->is_active,
                'is_self' => $s->user_id === auth()->id(),
                'approval_status' => $s->approval_status ?? StaffAssignment::APPROVAL_APPROVED,
                'permissions' => $s->permissions ?? [],
                'permissions_count' => is_array($s->permissions) ? count($s->permissions) : 0,
                'salary' => $salary ? [
                    'type' => $salary->type->value,
                    'type_label' => $salary->type->label(),
                    'rate' => (float) $salary->rate,
                    'overtime_rate' => $salary->overtime_rate !== null ? (float) $salary->overtime_rate : null,
                    'is_active' => (bool) $salary->is_active,
                ] : null,
                'last_login_at' => $s->user->last_login_at?->diffForHumans(now(), ['locale' => 'fa']) ?? '—',
                'created_at' => $s->created_at?->diffForHumans(now(), ['locale' => 'fa']) ?? '—',
            ];
        });

        return view('livewire.coffeenet.staff.index', [
            'rows' => $rows,
            'permissionCatalog' => OperatorPermissions::CATALOG,
            'permissionDefaults' => OperatorPermissions::defaults(),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'کارمندان',
            'breadcrumb' => 'پنل کافی‌نت ← کارمندان',
            'htmlTitle' => 'کارمندان',
        ]);
    }
}
