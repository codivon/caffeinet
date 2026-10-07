{{-- «کارت‌های بانکی» پنل اپراتور — Shell Livewire + UI مشترک سه پنل [Task 5]
     ساختار عیناً از back.operator.bank-cards.index حفظ شده: پارشال مشترک
     back.partials.bank-cards (idهای bcRoot/bcList/… و data-base/data-cards) +
     bank-cards.js؛ CRUD با همان endpointهای کنترلر (store/update/default/destroy)
     و GET data کار می‌کند — همه دست‌نخورده‌اند.
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه --}}
<div>
    <div class="card p-5 animate-fade-up">
        @include('back.partials.bank-cards', ['baseUrl' => '/operator/bank-cards', 'cards' => $cards])
    </div>
</div>

@push('scripts')
    <script src="{{ asset('back/assets/js/pages/bank-cards.js') }}?v=3" defer></script>
@endpush
