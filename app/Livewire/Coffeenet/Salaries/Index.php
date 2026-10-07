<?php

namespace App\Livewire\Coffeenet\Salaries;

use App\Models\Coffeenet;
use App\Models\SalaryLog;
use App\Models\StaffAssignment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * «حقوق و دستمزد» پنل کافی‌نت — نسخهٔ Livewire 4 (Task 4).
 *
 * منطق عیناً از App\Http\Controllers\Back\Coffeenet\SalariesController (index/data)
 * منتقل شده است:
 *   • کارت‌های خلاصه (پرداختی دورهٔ جاری/گذشته/تعداد رکوردها/دریافت‌کنندگان) — عیناً index()
 *   • فهرست کارمندان فعال برای فرم پرداخت — عیناً index()
 *   • لاگ‌ها: فیلتر کارمند/نوع/دوره + مرتب‌سازی نزولی id + صفحه‌بندی ۲۵تایی + سریال‌سازی data()
 *
 * ثبت پرداخت (store) طبق قرارداد Task 4 همان endpoint کنترلر می‌ماند:
 * فرم با همان اعتبارسنجی سمت کلاینت (Alpine) از طریق App.ajax به
 * coffeenet.salaries.store می‌رود — رفتار و پیام‌ها عیناً مثل index.js قبلی.
 */
#[Layout('back.coffeenet.layouts.panel')]
class Index extends Component
{
    use WithPagination;

    /** نوع‌های لاگ حقوق — عیناً SalariesController::TYPES */
    public const TYPES = [
        'monthly' => 'حقوق ماهیانه',
        'overtime' => 'اضافه‌کار',
        'bonus' => 'پاداش',
        'manual' => 'دستی / سایر',
    ];

    public Coffeenet $coffeenet;

    /** فیلتر کارمند */
    #[Url(as: 'user_id')]
    public string $userId = '';

    /** فیلتر نوع پرداخت */
    #[Url(as: 'type')]
    public string $type = '';

    /** فیلتر دوره (Y-m) */
    #[Url(as: 'period')]
    public string $period = '';

    public function mount(Coffeenet $coffeenet): void
    {
        $this->coffeenet = $coffeenet;
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['userId', 'type', 'period'], true)) {
            $this->resetPage();
        }
    }

    public function render(): \Illuminate\View\View
    {
        $coffeenet = $this->coffeenet;

        $currentPeriod = now()->format('Y-m');
        $lastPeriod = now()->subMonth()->format('Y-m');

        // کارمندان فعال — عیناً index() کنترلر
        $staff = $coffeenet->staffAssignments()
            ->where('is_active', true)
            ->with([
                'user:id,name,family',
                'salarySetting' => fn ($q) => $q->where('salary_settings.coffeenet_id', $coffeenet->id),
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (StaffAssignment $s) => [
                'assignment_id' => $s->id,
                'user_id' => $s->user_id,
                'full_name' => $s->user->full_name,
                'salary' => $s->salarySetting ? [
                    'type' => $s->salarySetting->type->value,
                    'type_label' => $s->salarySetting->type->label(),
                    'rate' => (float) $s->salarySetting->rate,
                    'overtime_rate' => $s->salarySetting->overtime_rate !== null ? (float) $s->salarySetting->overtime_rate : null,
                ] : null,
            ]);

        // لاگ‌ها — عیناً data() کنترلر
        $query = SalaryLog::query()
            ->where('coffeenet_id', $coffeenet->id)
            ->with('user:id,name,family,email', 'loggedBy:id,name,family');

        if ($filterUser = (int) $this->userId) {
            $query->where('user_id', $filterUser);
        }

        if ($type = (string) $this->type) {
            if (array_key_exists($type, self::TYPES)) {
                $query->where('type', $type);
            }
        }

        if ($period = trim($this->period)) {
            if (preg_match('/^\d{4}-\d{2}$/', $period)) {
                $query->where('period', $period);
            }
        }

        $rows = $query->orderByDesc('id')->paginate(25)->through(fn (SalaryLog $log) => [
            'id' => $log->id,
            'user' => [
                'id' => $log->user_id,
                'full_name' => $log->user?->full_name ?? '—',
            ],
            'period' => $log->period,
            'period_label' => jdate($log->period.'-01')->format('F Y'),
            'type' => $log->type,
            'type_label' => self::TYPES[$log->type] ?? $log->type,
            'amount' => (float) $log->amount,
            'description' => $log->description,
            'logged_by' => $log->loggedBy?->full_name ?? '—',
            'created_at' => $log->created_at?->diffForHumans(now(), ['locale' => 'fa']) ?? '—',
        ]);

        // ۱۲ ماه اخیر برای انتخاب دوره — برچسب شمسی، مقدار میلادی Y-m (عیناً ویوی قبلی)
        $monthOptions = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $monthOptions[] = [
                'value' => $cursor->format('Y-m'),
                'label' => jdate($cursor)->format('F Y'),
            ];
            $cursor = $cursor->copy()->subMonth();
        }

        return view('livewire.coffeenet.salaries.index', [
            'rows' => $rows,
            'staff' => $staff,
            'types' => self::TYPES,
            'monthOptions' => $monthOptions,
            'currentPeriod' => $currentPeriod,
            'currentPeriodLabel' => jdate(now())->format('F Y'),
            'lastPeriodLabel' => jdate(now()->subMonth())->format('F Y'),
            'summary' => [
                'current_total' => (float) SalaryLog::where('coffeenet_id', $coffeenet->id)->where('period', $currentPeriod)->sum('amount'),
                'last_total' => (float) SalaryLog::where('coffeenet_id', $coffeenet->id)->where('period', $lastPeriod)->sum('amount'),
                'logs_count' => SalaryLog::where('coffeenet_id', $coffeenet->id)->count(),
                'paid_staff' => SalaryLog::where('coffeenet_id', $coffeenet->id)->where('period', $currentPeriod)->distinct('user_id')->count('user_id'),
            ],
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'حقوق و دستمزد',
            'breadcrumb' => 'پنل کافی‌نت ← حقوق و دستمزد',
            'htmlTitle' => 'حقوق و دستمزد',
        ]);
    }
}
