<?php

namespace App\Livewire\Operator\BankCards;

use App\Livewire\Operator\Concerns\InteractsWithOperatorContext;
use App\Models\BankCard;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * «کارت‌های بانکی» پنل اپراتور — Shell Livewire + UI مشترک سه پنل [Task 5]
 *
 * GET operator.bank-cards.index → Shared\BankCardsController@index منطق عیناً
 * منتقل شده: کارت‌ها متعلق به «کاربرِ» لاگین‌شده‌اند و data-payload اولیه
 * عیناً با cardsPayload() کنترلر ساخته می‌شود (maskedCard / تأیید فینوتک v40).
 *
 * CRUD کامل (ثبت/ویرایش/پیش‌فرض/حذف) با همان UI مشترک
 * (back.partials.bank-cards + bank-cards.js) و همان endpointهای کنترلر کار
 * می‌کند — store/update/default/destroy و GET data همه دست‌نخورده‌اند.
 */
#[Layout('back.operator.layouts.panel')]
class Index extends Component
{
    use InteractsWithOperatorContext;

    public function render(): \Illuminate\View\View
    {
        if ($this->operatorContextFailed) {
            return view('livewire.operator.partials.context-redirect')
                ->layoutData($this->operatorLayoutData('کارت‌های بانکی', 'پنل اپراتور ← کارت‌های بانکی', 'کارت‌های بانکی'));
        }

        return view('livewire.operator.bank-cards.index', [
            'cards' => $this->cardsPayload(),
        ])->layoutData($this->operatorLayoutData('کارت‌های بانکی', 'پنل اپراتور ← کارت‌های بانکی', 'کارت‌های بانکی'));
    }

    /* ================== منطق داخلی (عیناً از کنترلر) ================== */

    /** دادهٔ نمایشی کارت‌های کاربر جاری — عیناً cardsPayload() کنترلر */
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
