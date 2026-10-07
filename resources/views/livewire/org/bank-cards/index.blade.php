{{-- «کارت‌های بانکی» مدیر سازمان — نسخهٔ Livewire 4 [Task 6]
     ساختار عیناً از back/org/bank-cards/index.blade.php حفظ شده است؛
     پارشیال مشترک سه پنل (back.partials.bank-cards) با همان attributeها
     و اسکریپت مشترک bank-cards.js (delegation) — CRUD همان endpointهای کنترلر.
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div>
    <div class="card p-5 animate-fade-up">
        @include('back.partials.bank-cards', ['baseUrl' => '/organization/bank-cards', 'cards' => $cards])
    </div>
</div>

@push('scripts')
    <script src="{{ asset('back/assets/js/pages/bank-cards.js') }}?v=3" defer></script>
@endpush
