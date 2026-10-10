<?php

namespace App\Http\Controllers\Back\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coffeenet;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * فاز ۵۴ — جستجوی سراسری پنل ادمین (Ctrl+K / ⌘+K).
 *
 * GET /admin/search?q= — سفارش (شماره/موبایل/نام)، مشتری، خدمت، کافی‌نت
 * و «صفحه‌های» پنل — هر بخش حداکثر ۵ نتیجه؛ خروجی JSON برای پالت جستجو.
 * UI در panel.blade.php + layout.js (سوییچ features.global_search).
 */
class GlobalSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => ['q' => $q, 'groups' => []]]);
        }

        $digits = en_digits($q);

        /* ---------- سفارش‌ها ---------- */
        $orders = Order::query()
            ->whereNull('deleted_at')
            ->where(function ($w) use ($q, $digits) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c
                        ->where('mobile', 'like', "%{$digits}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('family', 'like', "%{$q}%"));
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'order_number', 'status', 'created_at'])
            ->map(fn ($o) => [
                'type' => 'orders',
                'id' => $o->id,
                'title' => $o->order_number,
                'sub' => $o->status->label().' · '.fa_date($o->created_at, 'Y/m/d'),
                'url' => route('admin.orders.index'),
            ])->values();

        /* ---------- مشتریان ---------- */
        $customers = User::query()
            ->role('customer')
            ->whereNull('deleted_at')
            ->where(function ($w) use ($q, $digits) {
                $w->where('mobile', 'like', "%{$digits}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('family', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'name', 'family', 'mobile'])
            ->map(fn ($u) => [
                'type' => 'customers',
                'id' => $u->id,
                'title' => trim(($u->name ?? '').' '.($u->family ?? '')) ?: ('کاربر #'.$u->id),
                'sub' => $u->mobile ?: '—',
                'url' => route('admin.customers.index'),
            ])->values();

        /* ---------- خدمات ---------- */
        $services = Service::query()
            ->where('name', 'like', "%{$q}%")
            ->orderBy('sort')
            ->limit(5)
            ->get(['id', 'name', 'is_active'])
            ->map(fn ($s) => [
                'type' => 'services',
                'id' => $s->id,
                'title' => $s->name,
                'sub' => $s->is_active ? 'فعال' : 'غیرفعال',
                'url' => route('admin.services.index'),
            ])->values();

        /* ---------- کافی‌نت‌ها ---------- */
        $nets = Coffeenet::query()
            ->whereNull('deleted_at')
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'name', 'status'])
            ->map(fn ($c) => [
                'type' => 'coffeenets',
                'id' => $c->id,
                'title' => $c->name,
                'sub' => $c->status->label(),
                'url' => route('admin.coffeenets.index'),
            ])->values();

        /* ---------- صفحه‌های پنل (فهرست ثابت با تطبیق متن) ---------- */
        $pages = collect([
            ['title' => 'داشبورد', 'sub' => 'صفحهٔ اصلی پنل', 'url' => route('admin.dashboard')],
            ['title' => 'سفارش‌ها', 'sub' => 'مدیریت سفارش‌ها', 'url' => route('admin.orders.index')],
            ['title' => 'گفتگوها', 'sub' => 'چت سفارش‌ها', 'url' => route('admin.chats.index')],
            ['title' => 'نظرسنجی‌ها', 'sub' => 'امتیاز مشتریان', 'url' => route('admin.ratings.index')],
            ['title' => 'خدمات و فرم‌ساز', 'sub' => 'کاتالوگ', 'url' => route('admin.services.index')],
            ['title' => 'دسته‌بندی خدمات', 'sub' => 'دسته‌ها', 'url' => route('admin.service-categories.index')],
            ['title' => 'مدیران سیستم', 'sub' => 'کاربران ادمین', 'url' => route('admin.admins.index')],
            ['title' => 'سازمان‌ها', 'sub' => 'مدیریت سازمان‌ها', 'url' => route('admin.organizations.index')],
            ['title' => 'کافی‌نت‌ها', 'sub' => 'شعبه‌ها', 'url' => route('admin.coffeenets.index')],
            ['title' => 'مشتریان', 'sub' => 'کاربران', 'url' => route('admin.customers.index')],
            ['title' => 'گزارش مالی', 'sub' => 'درآمد و تسویه', 'url' => route('admin.finance.index')],
            ['title' => 'تیکت‌های پشتیبانی', 'sub' => 'پشتیبانی', 'url' => route('admin.tickets.index')],
            ['title' => 'تنظیمات', 'sub' => 'قابلیت‌ها و امنیت', 'url' => route('admin.settings.edit')],
            ['title' => 'کلیدهای وب‌سرویس', 'sub' => 'API + وب‌هوک', 'url' => route('admin.api-keys.index')],
            ['title' => 'مستندات API', 'sub' => 'راهنمای توسعه‌دهنده', 'url' => route('admin.api-docs.index')],
            ['title' => 'نشست‌های فعال', 'sub' => 'دستگاه‌های واردشده', 'url' => route('admin.sessions.index')],
            ['title' => 'سلامت سیستم', 'sub' => 'وضعیت زیرساخت', 'url' => route('admin.health')],
        ])
            ->filter(fn ($p) => mb_stripos($p['title'].' '.$p['sub'], $q) !== false)
            ->take(5)
            ->map(fn ($p) => [
                'type' => 'pages',
                'id' => null,
                'title' => $p['title'],
                'sub' => $p['sub'],
                'url' => $p['url'],
            ])->values();

        $groups = [
            ['key' => 'orders', 'label' => 'سفارش‌ها', 'items' => $orders],
            ['key' => 'customers', 'label' => 'مشتریان', 'items' => $customers],
            ['key' => 'services', 'label' => 'خدمات', 'items' => $services],
            ['key' => 'coffeenets', 'label' => 'کافی‌نت‌ها', 'items' => $nets],
            ['key' => 'pages', 'label' => 'صفحه‌ها', 'items' => $pages],
        ];

        $groups = collect($groups)->filter(fn ($g) => $g['items']->isNotEmpty())->values();

        return response()->json(['data' => ['q' => $q, 'groups' => $groups]]);
    }
}
