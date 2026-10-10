{{-- فاز ۵۴ — «آمار من» اپ مشتری — شِل Livewire؛ داده با stats.js + Chart.js --}}


<div>
<div class="wallet-hero fade-up" style="background:linear-gradient(135deg,var(--brand-600),var(--brand-800))">
    <div class="w-label">مجموع هزینه از ابتدا</div>
    <div class="w-balance"><span id="stTotal">—</span> <small>تومان</small></div>
    <div class="w-num" id="stOrders">— سفارش پرداخت‌شده</div>
</div>

<div class="section fade-up d1">
    <h2>هزینهٔ ۶ ماه اخیر</h2>
</div>

<div class="card fade-up d1" style="padding:14px">
    <div style="position:relative;height:190px">
        <canvas id="stChart" aria-label="نمودار هزینهٔ ماهانه"></canvas>
    </div>
    <div class="empty-state hidden" id="stChartEmpty">
        <div class="e-icon">📊</div>
        <div class="e-title">هنوز سفارشی ثبت نشده</div>
        <div class="e-desc">اولین سفارش‌تان را ثبت کنید تا آمار اینجا ساخته شود.</div>
    </div>
</div>

<div class="section fade-up d2">
    <h2>خلاصهٔ شما</h2>
</div>

<div class="card fade-up d2" style="display:grid;grid-template-columns:1fr 1fr;gap:10px" id="stTiles">
    <div style="text-align:center;padding:12px 6px;border-radius:14px;background:var(--surface)">
        <strong class="num" id="stAvg" style="font-size:15px">—</strong>
        <div class="text-faint tiny">میانگین هر سفارش (تومان)</div>
    </div>
    <div style="text-align:center;padding:12px 6px;border-radius:14px;background:var(--surface)">
        <strong class="num" id="stDelivery" style="font-size:15px">—</strong>
        <div class="text-faint tiny">میانگین زمان تحویل (دقیقه)</div>
    </div>
</div>

<div class="section fade-up d2">
    <h2>خدمات پرتکرار شما</h2>
</div>

<div class="card fade-up d3">
    <div id="stTop" class="sk-zone" aria-live="polite">
        <div class="skeleton" style="height:52px"></div>
        <div class="skeleton" style="height:52px"></div>
    </div>
    <div class="empty-state hidden" id="stTopEmpty">
        <div class="e-icon">🗂️</div>
        <div class="e-title">داده‌ای نیست</div>
        <div class="e-desc">پس از چند سفارش، پرتکرارترین خدماتتان اینجا می‌آید.</div>
    </div>
</div>

@push('page')
<script src="{{ asset('assets/js/vendor/chart.umd.min.js') }}" defer data-navigate-once></script>
<script src="{{ asset('front/assets/js/pages/stats.js') }}?v=2" defer></script>
@endpush
</div>
