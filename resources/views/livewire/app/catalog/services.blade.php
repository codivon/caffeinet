{{-- [Task 7] نسخهٔ Livewire 4 «خدمات» — تبدیل از app/services.blade.php
     ساختار/کلاس‌ها/آیدی‌ها عیناً حفظ شده (JS صفحهٔ Vanilla با CN.api → API v1 دست‌نخورده).
     تنظیمات لایه از کامپوننت با layoutData پاس داده می‌شود؛ این ویو فقط یک ریشهٔ <div> دارد. --}}


<div>
{{-- جستجو --}}
<div class="search-bar fade-up">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input class="field" id="searchInput" type="search" placeholder="جستجو در همهٔ خدمات…" autocomplete="off">
</div>

{{-- دسته‌بندی‌ها --}}
<div class="chip-row" id="categoryChips" role="tablist" aria-label="دسته‌بندی خدمات">
    <button class="chip active" data-cat="0" type="button" role="tab" aria-selected="true">
        <span class="chip-icon">✨</span> همه
    </button>
</div>

{{-- شمارنده --}}
{{-- v44 — دکمهٔ ماشهٔ «همهٔ دسته‌ها» کنار عنوان: باز کردن مودال گرید همهٔ دسته‌بندی‌ها --}}
<div class="section fade-up d1">
    <h2 id="servicesTitle">همهٔ دسته‌بندی‌ها</h2>
    <span class="more" id="servicesCount"></span>
    <button type="button" class="chip chip-allcats allcats-trigger" id="allCatsTrigger"
            aria-haspopup="dialog" aria-controls="allCatsSheet" title="مشاهدهٔ همهٔ دسته‌بندی‌ها">
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/></svg>
        همهٔ دسته‌ها
        <svg class="at-caret" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>
</div>

{{-- سکشن‌های دسته/زیردسته (رندر JS) --}}
<div id="groupedList" class="sk-zone" aria-live="polite">
    <div class="skeleton svc"></div>
    <div class="skeleton svc"></div>
    <div class="skeleton svc"></div>
</div>

<div class="empty-state hidden" id="servicesEmpty">
    <div class="e-icon">🔍</div>
    <div class="e-title">خدمتی پیدا نشد</div>
    <div class="e-desc">عبارت دیگری را جستجو کنید یا فیلتر دسته را بردارید.</div>
</div>

{{-- v42 — شیت «همهٔ دسته‌بندی‌ها»: دیدن همهٔ دسته‌ها یکجا به‌صورت گرید --}}
<div class="sheet-backdrop" id="allCatsBackdrop" aria-hidden="true"></div>
<div class="sheet allcats-sheet" id="allCatsSheet" role="dialog" aria-modal="true" aria-labelledby="allCatsTitle">
    <div class="sheet-grip" aria-hidden="true"></div>
    <div class="sheet-head">
        <h2 id="allCatsTitle">همهٔ دسته‌بندی‌ها</h2>
        <button type="button" class="sheet-x" id="allCatsClose" aria-label="بستن">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
    <div class="allcats-grid sk-zone" id="allCatsGrid" role="listbox" aria-label="انتخاب دسته‌بندی">
        <div class="skeleton" style="height:84px"></div>
        <div class="skeleton" style="height:84px"></div>
    </div>
</div>
</div>

@push('page')
    <script src="{{ asset('front/assets/js/pages/services.js') }}?v=5" defer></script>
@endpush
