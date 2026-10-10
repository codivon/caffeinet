<?php

namespace App\Livewire\Admin\Core;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * راهنمای پنل — فهرست (فاز ۱۳) — کامپوننت Livewire [Task 3-b]
 *
 * منطق عیناً از App\Http\Controllers\Back\Shared\GuideController@index
 * منتقل شده — این روت فقط برای پنل ادمین است (route defaults
 * guide_role=super_admin)؛ لایه همیشه back.layouts.panel است.
 *
 * پیشرفت مطالعه (localStorage) با همان JS قبلی صفحه کار می‌کند.
 */
#[Layout('back.layouts.panel')]
class GuideIndex extends Component
{
    public function mount(): void
    {
        $role = (string) request()->route()->parameter('guide_role', 'super_admin');

        abort_unless($role === 'super_admin', 404);
    }

    public function render()
    {
        $role = 'super_admin';

        $guides = \App\Models\Guide::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // مسیر پایهٔ راهنما در همین پنل (/admin/guide)
        $base = rtrim(request()->getPathInfo(), '/');

        return view('livewire.admin.core.guide.index', [
            'guides' => $guides,
            'guideRole' => $role,
            'guideRoleLabel' => \App\Models\Guide::ROLES[$role] ?? $role,
            'guideBase' => $base,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'آموزش استفاده از پنل',
            'breadcrumb' => 'پنل مدیریت کل ← راهنمای پنل',
            'htmlTitle' => 'راهنمای پنل',
        ]);
    }
}
