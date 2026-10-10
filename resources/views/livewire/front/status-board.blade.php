{{-- وضعیت زندهٔ کافی‌نت‌ها (فاز ۵۲) — صفحهٔ عمومی /status
     شفافیت کامل: باز/بسته بودن، امتیاز واقعی مشتری‌ها، صف هر کافی‌نت، آخرین تحویل.
     تازه‌سازی خودکار هر ۳۰ ثانیه (wire:poll). سوییچ: تنظیمات ← قابلیت‌ها. --}}
<div>
<link rel="stylesheet" href="{{ asset('assets/css/pages/status-board.css') }}?v=1">

<section class="sb-wrap">

    {{-- ---------- سربرگ ---------- --}}
    <header class="sb-hero">
        <div class="sb-hero-inner">
            <span class="sb-live-dot" aria-hidden="true"></span>
            <div>
                <h1 class="sb-title">وضعیت زندهٔ کافی‌نت‌ها</h1>
                <p class="sb-sub">همین لحظه کدام شعبه باز است، امتیازش چیست و چقدر شلوغ است — بدون هیچ تبلیغی، فقط آمار واقعی.</p>
            </div>
        </div>

        <div class="sb-tiles">
            <div class="sb-tile">
                <b>{{ fa_number($openCount) }}</b>
                <span>کافی‌نت فعال</span>
            </div>
            <div class="sb-tile">
                <b>{{ fa_number($totalActive) }}</b>
                <span>سفارش در جریان</span>
            </div>
            <div class="sb-tile">
                <b>{{ fa_number($queued) }}</b>
                <span>در صف بررسی</span>
            </div>
            <div class="sb-tile">
                <b>{{ $avgRating ? fa_number(round($avgRating, 1)) : '—' }}</b>
                <span>میانگین رأی مشتری‌ها</span>
            </div>
        </div>

        <p class="sb-refresh">این صفحه هر ۳۰ ثانیه خودش را تازه می‌کند — آخرین بروزرسانی: <bdi dir="ltr">{{ fa_date(now()) }}</bdi></p>
    </header>

    {{-- ---------- جدول کافی‌نت‌ها ---------- --}}
    <div class="sb-grid" wire:poll.30s>
        @forelse ($nets as $net)
            <article class="sb-card is-{{ $net['load'] }}" wire:key="sb-{{ $net['id'] }}">
                <div class="sb-card-top">
                    <span class="sb-load-badge" aria-hidden="true"></span>
                    <h2 class="sb-net-name">{{ $net['name'] }}</h2>
                    <span class="sb-city">{{ $net['city'] }}</span>
                </div>

                <div class="sb-rating" aria-label="امتیاز {{ $net['rating'] ? number_format($net['rating'], 1) : 'بدون رأی' }} از ۵">
                    <span class="sb-stars" aria-hidden="true">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="sb-star {{ $net['rating'] !== null && $net['rating'] >= $i - 0.5 ? 'is-on' : '' }}" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>
                        @endfor
                    </span>
                    <span class="sb-rating-num">{{ $net['rating'] !== null ? fa_number(number_format($net['rating'], 1)) : 'بدون رأی' }}</span>
                    @if ($net['votes'] > 0)
                        <span class="sb-votes">({{ fa_number($net['votes']) }} رأی)</span>
                    @endif
                </div>

                <div class="sb-meta">
                    <span class="sb-meta-item" title="سفارش‌های در جریان همین شعبه">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        {{ fa_number($net['active']) }} در جریان
                    </span>
                    <span class="sb-meta-item" title="تحویل‌های ۷ روز اخیر">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/></svg>
                        {{ fa_number($net['week']) }} تحویل در هفته
                    </span>
                    <span class="sb-meta-item" title="آخرین باری که این شعبه سفارشی تحویل داد">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        @if ($net['last_fa'])
                            آخرین تحویل: <bdi dir="ltr">{{ $net['last_fa'] }}</bdi>
                        @else
                            هنوز تحویلی ثبت نشده
                        @endif
                    </span>
                </div>

                <p class="sb-load-text">
                    @if ($net['load'] === 'free') ظرفیت آزاد — سریع می‌پذیرد
                    @elseif ($net['load'] === 'normal') در حال کار — پذیرش عادی
                    @else شلوغ — صف طولانی‌تر است
                    @endif
                </p>
            </article>
        @empty
            <div class="sb-empty">
                <p>هنوز کافی‌نت تأییدشده‌ای ثبت نشده است — به‌زودی برمی‌گردیم.</p>
            </div>
        @endforelse
    </div>

    {{-- ---------- CTA ---------- --}}
    <div class="sb-cta">
        <a href="{{ route('app.home') }}" class="sb-cta-btn">ثبت سفارش در کافی‌نت آنلاین</a>
        <span class="sb-cta-hint">اولین اتصال رایگان است — پرداخت پس از پذیرش اپراتور.</span>
    </div>

</section>
</div>
