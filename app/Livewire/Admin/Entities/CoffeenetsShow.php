<?php

namespace App\Livewire\Admin\Entities;

use App\Enums\CoffeenetStatus;
use App\Enums\OrderStatus;
use App\Enums\StaffPosition;
use App\Models\BankCard;
use App\Models\Coffeenet;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\ReferralSetting;
use App\Models\StaffAssignment;
use App\Models\Withdrawal;
use App\Services\Finance\WalletService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * جزئیات کامل کافی‌نت — آمار/مالی/کارکنان/سفارش‌ها (Task 3-d | مهاجرت Livewire 4)
 *
 * عیناً از App\Http\Controllers\Back\Admin\CoffeenetsController::show منتقل شده؛
 * چارت «روند کاری» همچنان با AJAX از اندپوینت GET /admin/coffeenets/{id}/trend
 * (coffeenets.trend) خوانده می‌شود — show.js دست‌نخورده ماند.
 */
#[Layout('back.layouts.panel')]
class CoffeenetsShow extends Component
{
    public Coffeenet $coffeenet;

    public function mount(Coffeenet $coffeenet): void
    {
        $this->coffeenet = $coffeenet;
    }

    public function render()
    {
        $coffeenet = $this->coffeenet->load([
            'organization:id,name,status',
            'province:id,name',
            'city:id,name',
            'approvedBy:id,name,family',
            'wallet',
        ]);

        // کارکنان: مدیرها ابتدا، سپس بر اساس نام
        $staff = $coffeenet->staffAssignments()
            ->with('user:id,name,family,mobile,email,created_at,last_seen_at')
            ->get()
            ->sort(function (StaffAssignment $a, StaffAssignment $b) {
                $pa = $a->position === StaffPosition::Manager ? 0 : 1;
                $pb = $b->position === StaffPosition::Manager ? 0 : 1;

                return $pa <=> $pb
                    ?: strcmp((string) $a->user?->full_name, (string) $b->user?->full_name);
            })
            ->values();

        $orders = Order::query()->where('coffeenet_id', $coffeenet->id);

        $totalOrders = (clone $orders)->count();
        $activeOrders = (clone $orders)->whereIn('status', [
            OrderStatus::Accepted->value, OrderStatus::InProgress->value, OrderStatus::NeedsInfo->value,
        ])->count();
        $doneOrders = (clone $orders)->whereIn('status', [
            OrderStatus::Delivered->value, OrderStatus::Completed->value,
        ])->count();
        $totalSales = (float) (clone $orders)
            ->whereNotIn('status', [
                OrderStatus::PendingPayment->value, OrderStatus::Cancelled->value, OrderStatus::Refunded->value,
            ])
            ->sum('price');

        $recentOrders = (clone $orders)
            ->with([
                'service:id,name',
                'customer:id,name,family',
                'operator:id,name,family',
            ])
            ->latest('id')
            ->limit(10)
            ->get();

        $conversationsCount = Conversation::query()
            ->whereIn('order_id', (clone $orders)->select('id'))
            ->count();

        $broadcastsCount = $coffeenet->broadcasts()->count();
        $recentBroadcasts = $coffeenet->broadcasts()
            ->with('order:id,order_number,service_id', 'order.service:id,name')
            ->latest('id')
            ->limit(10)
            ->get();

        $wallet = $coffeenet->wallet;
        $balance = app(WalletService::class)->balance($coffeenet);
        $transactions = $wallet
            ? $wallet->transactions()->latest('id')->limit(10)->get()
            : collect();
        $withdrawals = $wallet
            ? Withdrawal::query()->where('wallet_id', $wallet->id)->latest('id')->limit(5)->get()
            : collect();
        $pendingWithdrawals = (int) ($wallet
            ? Withdrawal::query()->where('wallet_id', $wallet->id)->where('status', 'pending')->count()
            : 0);

        // پیش‌نمایش پاداش معرفی (اگر با تأیید این کافی‌نت پرداخت می‌شود)
        $referral = ReferralSetting::current();
        $rewardPreview = ($coffeenet->organization_id
            && ! $coffeenet->introduction_reward_paid
            && $coffeenet->status !== CoffeenetStatus::Approved
            && $referral->is_active
            && (float) $referral->introduction_reward > 0)
            ? (float) $referral->introduction_reward
            : 0.0;

        // v39 — کارت‌های بانکی کارکنان کافی‌نت (نمایش فقط‌خواندنی)
        $bankCards = BankCard::query()
            ->whereIn('user_id', $staff->pluck('user_id')->filter())
            ->with('user:id,name,family')
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return view('livewire.admin.entities.coffeenets.show', [
            'coffeenet' => $coffeenet,
            'staff' => $staff,
            'bankCards' => $bankCards,
            'activeManagers' => $staff->where('is_active', true)->where('position', StaffPosition::Manager)->count(),
            'activeOperators' => $staff->where('is_active', true)->where('position', StaffPosition::Operator)->count(),
            'stats' => [
                'total_orders' => $totalOrders,
                'active_orders' => $activeOrders,
                'done_orders' => $doneOrders,
                'total_sales' => $totalSales,
                'balance' => $balance,
                'pending_withdrawals' => $pendingWithdrawals,
                'conversations' => $conversationsCount,
                'broadcasts' => $broadcastsCount,
            ],
            'recentOrders' => $recentOrders,
            'recentBroadcasts' => $recentBroadcasts,
            'transactions' => $transactions,
            'withdrawals' => $withdrawals,
            'rewardPreview' => $rewardPreview,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'جزئیات: '.$coffeenet->name,
            'breadcrumb' => 'پنل مدیریت کل ← کافی‌نت‌ها ← جزئیات',
            'htmlTitle' => 'جزئیات کافی‌نت',
        ]);
    }
}
