{{-- [Task 7] نسخهٔ Livewire 4 «داشبورد» — تبدیل از app/home.blade.php
     ساختار/کلاس‌ها/آیدی‌ها عیناً حفظ شده (JS صفحهٔ Vanilla با CN.api → API v1 دست‌نخورده).
     تنظیمات لایه از کامپوننت با layoutData پاس داده می‌شود؛ این ویو فقط یک ریشهٔ <div> دارد. --}}


<div>
{{-- جستجو --}}
<div class="search-bar fade-up">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input class="field" id="searchInput" type="search" placeholder="جستجوی خدمت… (پلاک، گواهی، ثنا)" autocomplete="off">
</div>

{{-- دسته‌بندی‌ها --}}
<div class="chip-row" id="categoryChips" role="tablist" aria-label="دسته‌بندی خدمات">
    <button class="chip active" data-cat="0" type="button" role="tab" aria-selected="true">
        <span class="chip-icon">✨</span> همه
    </button>
</div>

{{-- ویژه‌ها --}}
<div id="featuredSection" class="hidden">
    <div class="section">
        <h2>پیشنهاد ویژه</h2>
    </div>
    <div class="featured-strip" id="featuredStrip"></div>
</div>

{{-- فاز ۶۰ — سفارش مجدد: تا ۴ خدمت پرتکرار خودِ مشتری. اپ مشتری توکن‌محور
     است (CN.api → API v1) و سمت سرور کاربر شناخته نمی‌شود؛ پس این بخش یک
     شِل مخفی است که home.js با GET /api/v1/me/reorder پر می‌کند — بدون
     سابقه یا مهمان بودن، مخفی می‌ماند (بدون فضای خالی). --}}
<div class="ak-ro hidden" id="reorderSection">
    <div class="section">
        <h2>سفارش مجدد</h2>
        <span class="more">سابقهٔ شما</span>
    </div>
    <div class="ak-ro-strip" id="reorderStrip"></div>
</div>

{{-- همه خدمات --}}
<div class="section">
    <h2 id="servicesTitle">همه خدمات</h2>
    <span class="more" id="servicesCount"></span>
</div>

{{-- v44 — sk-zone: حلهٔ نور تا رسیدن خدمات فقط روی همین ناحیه --}}
<div id="servicesList" class="sk-zone" aria-live="polite">
    <div class="skeleton svc"></div>
    <div class="skeleton svc"></div>
    <div class="skeleton svc"></div>
</div>

<div class="empty-state hidden" id="servicesEmpty">
    <div class="e-icon">🔍</div>
    <div class="e-title">خدمتی پیدا نشد</div>
    <div class="e-desc">عبارت دیگری را جستجو کنید یا فیلتر دسته را بردارید.</div>
</div>

<div class="center-loader hidden" id="servicesMoreLoader"><span class="spinner"></span></div>

<button class="btn btn-outline btn-block mt-2 hidden" id="loadMoreBtn" type="button">
    نمایش خدمات بیشتر
</button>
</div>

@push('page')
    <script src="{{ asset('front/assets/js/pages/home.js') }}?v=6" defer></script>
@endpush

@push('styles')
{{-- فاز ۶۰ — کارت «سفارش مجدد» (namespace: ak-ro-*) — از توکن‌های app.css تا در تم روشن/تیره هم‌خانواده بماند --}}
<style>
    /* ردیف افقی اسکرولی (هم‌رفتار featured-strip) — در دسکتاپ دو کارت پهن‌تر دیده می‌شود */
    .ak-ro-strip {
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: minmax(230px, 72%);
        gap: 10px;
        overflow-x: auto;
        padding: 2px 1px 10px;
        margin-bottom: 6px;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
        scroll-snap-type: x mandatory;
    }
    .ak-ro-strip::-webkit-scrollbar { display: none; }
    @media (min-width: 640px) {
        .ak-ro-strip { grid-auto-columns: minmax(300px, 46%); }
    }

    .ak-ro-card {
        scroll-snap-align: start;
        display: flex;
        align-items: center;
        gap: 11px;
        background: var(--surface);
        border: 1px solid var(--line-soft);
        border-radius: var(--radius-lg);
        padding: 12px 14px;
        box-shadow: var(--shadow-1);
        transition: transform .2s, box-shadow .2s, border-color .2s;
        -webkit-tap-highlight-color: transparent;
        position: relative;
        overflow: hidden;
    }
    .ak-ro-card::after {
        content: '';
        position: absolute;
        inset-block: 0;
        inset-inline-start: 0;
        width: 3px;
        background: linear-gradient(180deg, var(--brand-400), var(--brand-600));
        opacity: .8;
    }
    .ak-ro-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-2); border-color: var(--brand-200); }
    .ak-ro-card:active { transform: scale(.985); }

    .ak-ro-card .ak-ro-ic {
        flex: none;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        font-size: 22px;
        background: linear-gradient(135deg, var(--brand-100), var(--brand-200));
    }

    .ak-ro-card .ak-ro-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
    .ak-ro-card .ak-ro-name {
        font-size: 12.5px;
        font-weight: 800;
        color: var(--ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ak-ro-card .ak-ro-meta { font-size: 10.5px; color: var(--ink-faint); font-weight: 600; }
    .ak-ro-card .ak-ro-price { display: flex; align-items: baseline; gap: 3px; margin-top: 2px; }
    .ak-ro-card .ak-ro-price b { font-size: 12.5px; font-weight: 800; color: var(--brand-600); white-space: nowrap; }
    .ak-ro-card .ak-ro-price i { font-style: normal; font-size: 9.5px; color: var(--ink-faint); font-weight: 500; }

    .ak-ro-card .ak-ro-go {
        flex: none;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--brand-50);
        color: var(--brand-600);
    }
</style>
@endpush
