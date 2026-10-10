<?php

namespace App\Livewire\Admin\Support;

use App\Enums\OrderStatus;

/**
 * کمکی‌های رندر مشترک بخش‌های Livewire پنل مدیریت کل (Task 3-c).
 *
 * نقشهٔ رنگ بج وضعیت‌ها عیناً از STATUS_COLORS اسکریپت‌های قبلی
 * (orders/index.js — operator/chat/index.js) منتقل شده تا ظاهر ردیف‌ها
 * قبل و بعد از مهاجرت یکسان بماند.
 */
class Ui
{
    /** نقشهٔ رنگ بج وضعیت سفارش (عیناً از JS قبلی) */
    public const STATUS_COLORS = [
        'amber' => 'bg-amber-50 text-amber-700 border border-amber-200',
        'sky' => 'bg-sky-50 text-sky-700 border border-sky-200',
        'blue' => 'bg-teal-50 text-teal-700 border border-teal-200',
        'orange' => 'bg-amber-50 text-amber-700 border border-amber-200',
        'teal' => 'bg-teal-50 text-teal-700 border border-teal-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'rose' => 'bg-rose-50 text-rose-600 border border-rose-200',
    ];

    /** کلاس بج یک وضعیت سفارش */
    public static function statusBadge(OrderStatus $status): string
    {
        return self::STATUS_COLORS[$status->color()] ?? self::STATUS_COLORS['amber'];
    }

    /** کلاس شدت رنگ ستاره‌ها (جدول نظرسنجی) — عیناً از ratings/index.js */
    public static function starsClass(int|float|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return $value >= 4 ? 'rt-stars--good' : ($value <= 2 ? 'rt-stars--bad' : 'rt-stars--mid');
    }

    /** کلاس بج وضعیت برداشت — عیناً از withdrawals/index.js */
    public static function withdrawalBadge(string $status): string
    {
        return match ($status) {
            'approved' => 'bg-sky-50 text-sky-700 border border-sky-200',
            'paid' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'rejected' => 'bg-rose-50 text-rose-600 border border-rose-200',
            default => 'bg-amber-50 text-amber-700 border border-amber-200',
        };
    }

    /** برچسب فارسی وضعیت برداشت — عیناً از withdrawals/index.js */
    public static function withdrawalLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'در انتظار بررسی',
            'approved' => 'تأییدشده',
            'paid' => 'پرداخت‌شده',
            'rejected' => 'ردشده',
            default => $status,
        };
    }
}
