<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * فاز ۵۳ — حساب خانواده/تیمی (API اپ مشتری).
 *
 * حساب اصلی می‌تواند زیرحساب بسازد (موبایل هر عضو؛ ورود همان OTP)،
 * برای هر عضو سقف خرج روزانه/ماهانه بگذارد، خرجش را موقتاً ببندد
 * یا عضو را حذف کند. همه از کیف پول حساب اصلی خرج می‌کنند.
 *
 * سوییچ: features.family_accounts — خاموش = 404.
 * دسترسی: درخواست‌کننده باید «حساب اصلی» باشد (بدون parent_id).
 */
class FamilyController extends Controller
{
    /** آیا قابلیت برای این کاربر باز است؟ */
    private function guard(Request $request): void
    {
        if (! app(SettingsService::class)->get('features.family_accounts', false)) {
            abort(404);
        }

        // زیرحساب‌ها مدیریت ندارند؛ فقط حساب اصلی
        if ($request->user()->parent_id) {
            abort(403, 'فقط حساب اصلی می‌تواند اعضای خانواده را مدیریت کند.');
        }
    }

    /** GET /api/v1/family — فهرست اعضا + خرج امروز/ماه هر عضو */
    public function index(Request $request): JsonResponse
    {
        $this->guard($request);

        $members = $request->user()
            ->familyMembers()
            ->orderBy('id')
            ->get()
            ->map(fn (User $m) => $this->memberPayload($m));

        return response()->json([
            'data' => [
                'members' => $members,
                'balance' => (float) ($request->user()->wallet?->balance ?? 0),
            ],
        ]);
    }

    /** POST /api/v1/family {name, family?, mobile} — ساخت زیرحساب */
    public function store(Request $request): JsonResponse
    {
        $this->guard($request);

        $data = $request->validate([
            'name'   => ['required', 'string', 'min:2', 'max:60'],
            'family' => ['nullable', 'string', 'max:60'],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ], [
            'name.required' => 'نام عضو الزامی است.',
            'mobile.required' => 'شماره موبایل عضو الزامی است.',
            'mobile.regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
        ]);

        $mobile = trim($data['mobile']);

        // موبایل تکراری؟
        if (User::withTrashed()->where('mobile', $mobile)->exists()) {
            return response()->json([
                'message' => 'این شماره قبلاً در سیستم ثبت شده است.',
                'errors' => ['mobile' => ['این شماره قبلاً در سیستم ثبت شده است.']],
            ], 422);
        }

        // سقف تعداد اعضا (۸ نفر)
        if ($request->user()->familyMembers()->count() >= 8) {
            return response()->json([
                'message' => 'حداکثر ۸ عضو خانواده می‌توانید بسازید.',
            ], 422);
        }

        $member = User::create([
            'name' => trim($data['name']),
            'family' => trim((string) ($data['family'] ?? '')) ?: null,
            'mobile' => $mobile,
            'parent_id' => $request->user()->id,
            'is_active' => true,
            'profile_completed' => true,
            'mobile_verified_at' => now(),
        ]);

        $member->assignRole('customer');

        return response()->json([
            'message' => 'عضو خانواده ساخته شد — با شماره خودش می‌تواند مثل شما وارد اپ شود و از کیف پول شما خرج کند.',
            'data' => $this->memberPayload($member),
        ], 201);
    }

    /** PATCH /api/v1/family/{member} {wallet_daily_limit?, wallet_monthly_limit?, family_blocked?} */
    public function update(Request $request, User $member): JsonResponse
    {
        $this->guard($request);

        if ((int) $member->parent_id !== (int) $request->user()->id) {
            abort(404);
        }

        $data = $request->validate([
            'wallet_daily_limit'   => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'wallet_monthly_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'family_blocked'       => ['nullable', 'boolean'],
        ]);

        $member->fill(collect($data)->filter(fn ($v) => $v !== null)->all());
        $member->save();

        return response()->json([
            'message' => 'تغییرات ذخیره شد.',
            'data' => $this->memberPayload($member->refresh()),
        ]);
    }

    /** DELETE /api/v1/family/{member} — حذف عضو (نرم) */
    public function destroy(Request $request, User $member): JsonResponse
    {
        $this->guard($request);

        if ((int) $member->parent_id !== (int) $request->user()->id) {
            abort(404);
        }

        $name = $member->name;
        $member->tokens()->delete();
        $member->delete();

        return response()->json([
            'message' => 'عضو «'.$name.'» حذف شد.',
        ]);
    }

    /** شکل استاندارد عضو در پاسخ‌ها */
    private function memberPayload(User $m): array
    {
        return [
            'id'             => $m->id,
            'name'           => trim(($m->name ?? '').' '.($m->family ?? '')) ?: 'عضو',
            'mobile'         => $m->mobile,
            'initial'        => mb_substr($m->name ?: 'ع', 0, 1),
            'daily_limit'    => $m->wallet_daily_limit !== null ? (float) $m->wallet_daily_limit : null,
            'monthly_limit'  => $m->wallet_monthly_limit !== null ? (float) $m->wallet_monthly_limit : null,
            'blocked'        => (bool) $m->family_blocked,
            'spend_today'    => $m->familySpendToday(),
            'spend_month'    => $m->familySpendThisMonth(),
        ];
    }
}
