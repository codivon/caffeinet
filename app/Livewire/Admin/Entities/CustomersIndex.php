<?php

namespace App\Livewire\Admin\Entities;

use App\Models\Province;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * مشتریان — فهرست سراسری (Task 3-d | مهاجرت Livewire 4)
 *
 * عیناً از App\Http\Controllers\Back\Admin\CustomersController (index/data):
 *   • جستجو (نام/خانوادگی/موبایل/ایمیل) + فیلتر وضعیت/پروفایل/استان + مرتب‌سازی
 *   • آمار گروهی سبک (سفارش/پرداخت/کیف پول) برای ردیف‌های همین صفحه
 *   • مرتب‌سازی‌های مالی (پرداخت/کیف) روی مجموعهٔ صفحهٔ جاری — مثل قبل
 *
 * مودال ویرایش کامل، بن/رفع بن و حذف نرم همچنان با AJAX به همان اندپوینت‌های
 * کنترلر کار می‌کنند (اعتبارسنجی و پیام‌های فارسی عیناً سرور).
 */
#[Layout('back.layouts.panel')]
class CustomersIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    /** active | banned */
    #[Url(history: true)]
    public string $status = '';

    /** complete | incomplete */
    #[Url(history: true)]
    public string $profile = '';

    #[Url(as: 'province_id', history: true)]
    public string $provinceId = '';

    /** newest | old | orders | spent | wallet | login */
    #[Url(history: true)]
    public string $sort = 'newest';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedProfile(): void
    {
        $this->resetPage();
    }

    public function updatedProvinceId(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = User::query()
            ->role('customer')
            ->with(['province:id,name', 'city:id,name']);

        if ($q = trim($this->search)) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('family', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'banned') {
            $query->where('is_active', false);
        }

        if ($this->profile === 'complete') {
            $query->where('profile_completed', true);
        } elseif ($this->profile === 'incomplete') {
            $query->where('profile_completed', false);
        }

        if ((int) $this->provinceId) {
            $query->where('province_id', (int) $this->provinceId);
        }

        match ($this->sort) {
            'orders' => $query->withCount('orders')->orderByDesc('orders_count'),
            'login' => $query->orderByDesc('last_login_at'),
            'old' => $query->orderBy('id'),
            'spent', 'wallet' => null, // بعداً روی صفحهٔ جاری دستی مرتب می‌شوند
            default => $query->latest('id'),
        };

        $paginator = $query->paginate(25);

        $users = $paginator->getCollection();
        $ids = $users->pluck('id')->values()->all();

        /* آمار گروهیِ همین صفحه (سبک — بدون N+1) — عیناً مثل کنترلر */
        $ordersCount = $ids ? \App\Models\Order::query()->whereIn('customer_id', $ids)
            ->selectRaw('customer_id, count(*) as c')->groupBy('customer_id')->pluck('c', 'customer_id') : collect();
        $spentMap = $ids ? \App\Models\Payment::query()->whereIn('user_id', $ids)
            ->where('status', 'success')
            ->selectRaw('user_id, sum(amount) as s')->groupBy('user_id')->pluck('s', 'user_id') : collect();
        $wallets = $ids ? \App\Models\Wallet::query()
            ->where('holder_type', User::class)->whereIn('holder_id', $ids)
            ->pluck('balance', 'holder_id') : collect();

        $rows = $users->map(fn (User $u) => $this->mapRow($u, $ordersCount, $spentMap, $wallets))->values();

        /* مرتب‌سازی‌های مالی روی کل مجموعهٔ صفحه (سبک) — عیناً مثل کنترلر */
        if ($this->sort === 'spent') {
            $rows = $rows->sortByDesc('spent')->values();
        } elseif ($this->sort === 'wallet') {
            $rows = $rows->sortByDesc('wallet')->values();
        }

        $paginator->setCollection($rows);

        return view('livewire.admin.entities.customers.index', [
            'rows' => $paginator,
            'provinces' => Province::query()->orderBy('sort')->orderBy('name')->get(['id', 'name']),
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'مدیریت مشتریان',
            'breadcrumb' => 'پنل مدیریت کل ← مشتریان',
            'htmlTitle' => 'مشتریان',
        ]);
    }

    /** نگاشت ردیف — عیناً همان فیلدهای JSON کنترلر (data) */
    protected function mapRow(User $u, $ordersCount, $spentMap, $wallets): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'family' => $u->family,
            'full_name' => $u->full_name,
            'mobile' => $u->mobile,
            'email' => $u->email,
            'gender' => $u->gender?->value,
            'gender_label' => $u->gender?->label(),
            'province' => $u->province?->name,
            'province_id' => $u->province_id,
            'city' => $u->city?->name,
            'city_id' => $u->city_id,
            'birthdate' => $u->birthdate?->toDateString(),
            'birthdate_fa' => $u->birthdate ? fa_date($u->birthdate, 'Y/m/d') : null,
            'profile_completed' => (bool) $u->profile_completed,
            'is_active' => (bool) $u->is_active,
            'orders' => (int) ($ordersCount[$u->id] ?? 0),
            'spent' => (float) ($spentMap[$u->id] ?? 0),
            'wallet' => (float) ($wallets[$u->id] ?? 0),
            'last_login_fa' => $u->last_login_at ? fa_date($u->last_login_at, 'Y/m/d H:i') : null,
            'joined_fa' => fa_date($u->created_at, 'Y/m/d'),
        ];
    }
}
