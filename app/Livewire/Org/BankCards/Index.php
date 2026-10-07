<?php

namespace App\Livewire\Org\BankCards;

use App\Livewire\Org\Concerns\InteractsWithOrgContext;
use App\Models\BankCard;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «کارت‌های بانکی» مدیر سازمان — نسخهٔ Livewire 4 [Task 6]
 *
 * منطق عیناً از App\Http\Controllers\Back\Shared\BankCardsController (index/data)
 * منتقل شده است: کارت‌های متعلق به کاربر جاری با همان ترتیب (پیش‌فرض اول، سپس جدیدترین)
 * و همان سریال‌سازی cardsPayload (ماسک کارت، تأیید فینوتک v40، تاریخ شمسی).
 *
 * CRUD (store/update/default/destroy) و GET data طبق قرارداد فاز ۳ همان endpointهای
 * کنترلر می‌مانند؛ UI مشترک back.partials.bank-cards با همان attributeها
 * (data-base / data-cards / data-finnotech) + اسکریپت مشترک bank-cards.js
 * (delegation سطح ریشه) حفظ شده — فایل JS مشترک دست نخورده است.
 */
#[Layout('back.layouts.org')]
class Index extends Component
{
    use InteractsWithOrgContext;

    public function render(): \Illuminate\View\View
    {
        $org = $this->resolveOrgContext();

        // زمینهٔ سازمان نامعتبر است (ریدایرکت choose/login ثبت شده) — رندر خنثی بدون کوئری
        $cards = $org ? $this->cardsPayload() : [];

        return view('livewire.org.bank-cards.index', [
            'organization' => $org ?? static::neutralOrganization(),
            'cards' => $cards,
        ])->layoutData([
            'organization' => $org ?? static::neutralOrganization(),
            'user' => auth()->user(),
            'pageTitle' => 'کارت‌های بانکی',
            'breadcrumb' => 'پنل سازمان ← کارت‌های بانکی',
            'htmlTitle' => 'کارت‌های بانکی',
        ]);
    }

    /* ================== سریال‌سازی (عیناً cardsPayload کنترلر) ================== */

    /** دادهٔ نمایشی کارت‌های کاربر جاری */
    protected function cardsPayload(): array
    {
        return auth()->user()->bankCards()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map(fn (BankCard $c) => [
                'id' => $c->id,
                'card_number' => $c->card_number,
                'card_masked' => $c->maskedCard(),
                'sheba_number' => $c->sheba_number,
                'account_number' => $c->account_number,
                'holder_name' => $c->holder_name,
                'is_default' => $c->is_default,
                'verified' => $c->isVerified(), // v40 — تأیید فینوتک
                'verified_at_fa' => $c->verified_at ? fa_date($c->verified_at, 'Y/m/d H:i') : null, // v40
            ])
            ->all();
    }
}
