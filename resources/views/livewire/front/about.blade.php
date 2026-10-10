{{-- فاز ۶۰ — صفحهٔ عمومی «درباره/اعتماد» (/about) — App\Livewire\Front\About
     هم‌خانوادهٔ لندینگ: از کلاس‌های landing.css (hero/feat-card/cta-panel) استفاده می‌کند؛
     بخش‌های اختصاصی با namespace «ak-trust-*» در @push('styles') همین ویو.
     محتوای intro/FAQ از تنظیمات (پیش‌فرض کد) می‌آید؛ آکاردئون details/summary خالص — بدون JS. --}}
<main class="site-main" style="display:contents">

{{-- ==================== هیرو اعتمادساز ==================== --}}
<section class="hero" id="top">
    <div class="container">
        <div class="hero-copy ak-trust-hero">
            @if ($workStatus['enabled'])
                <span class="live-pill {{ $workStatus['open'] ? '' : 'is-closed' }}">
                    <span class="pulse"></span>
                    @if ($workStatus['open'])
                        همین حالا آنلاین هستیم — ساعت کاری {{ $workStatus['range'] }}
                    @else
                        خارج از ساعت کاری ({{ $workStatus['range'] }}) — سفارش شما ثبت و در ابتدای ساعت کاری پردازش می‌شود
                    @endif
                </span>
            @else
                <span class="live-pill"><span class="pulse"></span> پشتیبانی ۷ روز هفته — بدون محدودیت ساعت کاری</span>
            @endif

            <h1 class="hero-title">
                {{ __('chrome.about.hero_title_a') }}
                <span class="gold">{{ __('chrome.about.hero_title_b') }}</span>
            </h1>

            <p class="hero-desc">{{ $intro }}</p>

            <div class="hero-ctas">
                <a href="{{ route('front.status') }}" wire:navigate class="btn btn-gold btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    {{ __('chrome.about.cta_status') }}
                </a>
                <a href="{{ route('app.auth') }}" wire:navigate class="btn btn-ghost btn-lg">
                    {{ __('chrome.about.cta_order') }}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                </a>
            </div>

            <ul class="hero-trust">
                <li class="trust-item"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> {{ __('chrome.about.trust_1') }}</li>
                <li class="trust-item"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> {{ __('chrome.about.trust_2') }}</li>
                <li class="trust-item"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> {{ __('chrome.about.trust_3') }}</li>
                <li class="trust-item"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> {{ __('chrome.about.trust_4') }}</li>
            </ul>
        </div>
    </div>
</section>

{{-- ==================== چرا کافی‌نت آنلاین ==================== --}}
<section class="section" id="trust">
    <div class="container">
        <div class="section-head center reveal">
            <span class="eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                {{ __('chrome.about.feat_badge') }}
            </span>
            <h2 class="section-title">{{ __('chrome.about.feat_title_a') }} <span class="gold">{{ __('chrome.about.feat_title_b') }}</span></h2>
            <p class="section-desc">{{ __('chrome.about.feat_desc') }}</p>
        </div>

        <div class="feat-grid">
            <div class="feat-card reveal">
                <span class="feat-ic">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                </span>
                <h3 class="feat-title">{{ __('chrome.about.f1_title') }}</h3>
                <p class="feat-desc">{{ __('chrome.about.f1_desc') }}</p>
            </div>
            <div class="feat-card reveal" style="--rv-delay:.05s">
                <span class="feat-ic">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <h3 class="feat-title">{{ __('chrome.about.f2_title') }}</h3>
                <p class="feat-desc">{{ __('chrome.about.f2_desc') }}</p>
            </div>
            <div class="feat-card reveal" style="--rv-delay:.1s">
                <span class="feat-ic">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>
                </span>
                <h3 class="feat-title">{{ __('chrome.about.f3_title') }}</h3>
                <p class="feat-desc">{{ __('chrome.about.f3_desc') }}</p>
            </div>
            <div class="feat-card reveal" style="--rv-delay:.05s">
                <span class="feat-ic">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </span>
                <h3 class="feat-title">{{ __('chrome.about.f4_title') }}</h3>
                <p class="feat-desc">{{ __('chrome.about.f4_desc') }}</p>
            </div>
            <div class="feat-card reveal" style="--rv-delay:.1s">
                <span class="feat-ic">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </span>
                <h3 class="feat-title">{{ __('chrome.about.f5_title') }}</h3>
                <p class="feat-desc">{{ __('chrome.about.f5_desc') }}</p>
            </div>
            <div class="feat-card reveal" style="--rv-delay:.15s">
                <span class="feat-ic">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/><path d="M8 10h8"/><path d="M8 14h5"/></svg>
                </span>
                <h3 class="feat-title">{{ __('chrome.about.f6_title') }}</h3>
                <p class="feat-desc">{{ __('chrome.about.f6_desc') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ==================== سوالات متداول (details/summary خالص) ==================== --}}
<section class="section" id="faq">
    <div class="container">
        <div class="section-head center reveal">
            <span class="eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                {{ __('chrome.about.faq_title_a') }} {{ __('chrome.about.faq_title_b') }}
            </span>
            <h2 class="section-title">{{ __('chrome.about.faq_title_a') }} <span class="gold">{{ __('chrome.about.faq_title_b') }}</span></h2>
            <p class="section-desc">{{ __('chrome.about.faq_sub') }}</p>
        </div>

        <div class="faq-wrap">
            @foreach ($faqItems as $item)
                <details class="ak-trust-faq reveal">
                    <summary>
                        <span class="ak-trust-q">{{ $item['q'] }}</span>
                        <span class="ak-trust-chev" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </summary>
                    <div class="ak-trust-answer">{{ $item['a'] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ==================== CTA نهایی ==================== --}}
<section class="section final-cta">
    <div class="container">
        <div class="cta-panel reveal">
            <div class="cta-cup" aria-hidden="true">
                <img src="{{ asset('icons/icon-96.png') }}" alt="" width="34" height="34" style="width:34px;height:34px;object-fit:contain" aria-hidden="true">
            </div>
            <h2 class="cta-title">{{ __('chrome.about.cta_title_a') }} <span class="gold">{{ __('chrome.about.cta_title_b') }}</span></h2>
            <div class="cta-actions">
                <a href="{{ route('app.auth') }}" wire:navigate class="btn btn-gold btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
                    {{ __('chrome.about.cta_order') }}
                </a>
                <a href="{{ route('front.status') }}" wire:navigate class="btn btn-ghost btn-lg">{{ __('chrome.about.cta_status') }}</a>
            </div>
            <div class="cta-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                {{ __('chrome.about.cta_note') }}
            </div>
        </div>
    </div>
</section>
</main>

@push('styles')
{{-- فاز ۶۰ — استایل اختصاصی صفحهٔ درباره (namespace: ak-trust-*) — از توکن‌های landing.css استفاده می‌کند تا در تم روشن/تیره هم‌خانواده بماند --}}
<style>
    /* هیرو وسط‌چین و جمع‌وجورتر از لندینگ */
    .ak-trust-hero { max-width: 820px; margin-inline: auto; text-align: center; }
    .ak-trust-hero .hero-title { font-size: clamp(1.8rem, 4.4vw, 2.9rem); }
    .ak-trust-hero .hero-ctas { justify-content: center; }
    .ak-trust-hero .hero-trust { justify-content: center; }

    /* آکاردئون سوالات متداول — details/summary خالص (بدون JS)، هم‌رنگ کارت‌های لندینگ */
    .ak-trust-faq {
        border: 1px solid var(--line);
        background: var(--glass);
        border-radius: var(--radius);
        overflow: hidden;
        transition: border-color .3s var(--ease), background .3s var(--ease), box-shadow .3s var(--ease);
    }
    .ak-trust-faq[open] {
        border-color: rgba(59, 130, 246, .4);
        background: var(--glass-2);
        box-shadow: var(--shadow-glow);
    }
    .ak-trust-faq summary {
        list-style: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 20px;
        font-weight: 700;
        font-size: 13.5px;
        color: var(--ink);
        -webkit-tap-highlight-color: transparent;
    }
    .ak-trust-faq summary::-webkit-details-marker { display: none; }
    .ak-trust-faq .ak-trust-q { flex: 1; min-width: 0; line-height: 1.9; }
    .ak-trust-faq .ak-trust-chev {
        flex: none;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: rgba(59, 130, 246, .22);
        color: var(--brand-300);
        transition: transform .35s var(--ease), background .3s;
    }
    .ak-trust-faq[open] .ak-trust-chev { transform: rotate(180deg); }
    .ak-trust-faq .ak-trust-answer {
        margin: 0 20px 18px;
        padding-top: 14px;
        border-top: 1px dashed var(--line);
        font-size: 13px;
        color: var(--ink-3);
        font-weight: 400;
        line-height: 2.2;
    }
</style>
@endpush
