<?php

namespace App\Livewire\Admin\Core;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * راهنمای پنل — مقاله (فاز ۱۳) — کامپوننت Livewire [Task 3-b]
 *
 * منطق عیناً از App\Http\Controllers\Back\Shared\GuideController@show
 * منتقل شده — این روت فقط برای پنل ادمین است (route defaults
 * guide_role=super_admin)؛ لایه همیشه back.layouts.panel است.
 */
#[Layout('back.layouts.panel')]
class GuideShow extends Component
{
    public function mount(): void
    {
        $role = (string) request()->route()->parameter('guide_role', 'super_admin');

        abort_unless($role === 'super_admin', 404);
    }

    public function render()
    {
        $role = 'super_admin';
        $slug = (string) request()->route('slug', '');

        $guide = \App\Models\Guide::query()
            ->where('role', $role)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // راهنمای بعدی (برای ناوبری پایان مطلب)
        $next = \App\Models\Guide::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->where('sort_order', '>=', $guide->sort_order)
            ->whereKeyNot($guide->getKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        // مسیر پایهٔ راهنما (بدون slug آخر مسیر)
        $base = preg_replace('#/[^/]+$#', '', rtrim(request()->getPathInfo(), '/'));

        return view('livewire.admin.core.guide.show', [
            'guide' => $guide,
            'nextGuide' => $next,
            'guideRole' => $role,
            'guideRoleLabel' => \App\Models\Guide::ROLES[$role] ?? $role,
            'guideBase' => $base,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => $guide->title,
            'breadcrumb' => 'پنل مدیریت کل ← راهنمای پنل ← '.$guide->title,
            'htmlTitle' => $guide->title,
        ]);
    }
}
