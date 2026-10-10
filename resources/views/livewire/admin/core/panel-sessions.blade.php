{{-- نشست‌های فعال (فاز ۵۰) — دستگاه‌های واردشده با همین حساب + خروج ریموت
     از «تنظیمات ← نشست‌های فعال» باز می‌شود.
     فاز ۵۷ — بازطراحی مدرن کامل: هیروی تیرهٔ گرادیانی با چیپ‌های آمار زنده،
     کارت‌های دستگاه با آیکون رنگی نوع دستگاه، بج ضربان‌دار «این دستگاه»،
     IP مونواسپیس، دارک‌مود و ریسپانسیو — CSS خودکفا: pages/panel-sessions.css
     (رفع چسبیدن هدر به کارت‌ها: قبلاً کلاس sc-stack بدون CSSِ لودشده فاصله نداشت). --}}
<div x-data>
<div class="ps-stack">

    {{-- ================== هیرو ================== --}}
    <section class="ps-hero animate-fade-up" aria-labelledby="ps-title">
        <div class="ps-hero-body">
            <span class="ps-hero-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
            </span>
            <div class="ps-hero-text">
                <h1 class="ps-hero-title" id="ps-title">نشست‌های فعال</h1>
                <p class="ps-hero-desc">
                    همهٔ دستگاه‌ها و مرورگرهایی که الان با حساب شما وارد پنل شده‌اند.
                    اگر دستگاهی آشنا نیست، <b>از همهٔ دستگاه‌های دیگر خارج شوید و رمز را عوض کنید</b>.
                </p>
            </div>
            <button type="button" wire:click="logoutOthers"
                    @click="window.PanelUI ? PanelUI.confirm({ title: 'خروج از سایر دستگاه‌ها؟', desc: 'نشست همین دستگاه حفظ می‌شود و بقیه در اولین کلیک لاگ‌اوت می‌شوند.', okText: 'بله، خارج شوند', cancelText: 'انصراف', danger: true, icon: 'warn' }, () => $wire.logoutOthers()) : $wire.logoutOthers()"
                    class="ps-logout-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                خروج از سایر دستگاه‌ها
            </button>
        </div>

        {{-- چیپ‌های آمار زنده --}}
        <div class="ps-hero-chips" role="list" aria-label="خلاصهٔ نشست‌ها">
            <span class="ps-chip" role="listitem">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                دستگاه‌های واردشده: <b>{{ fa_number($sessions->count()) }}</b>
            </span>
            <span class="ps-chip ps-chip--live" role="listitem">
                <span class="ps-pulse" aria-hidden="true"></span>
                همین دستگاه الان فعال است
            </span>
            @php($latest = $sessions->first())
            @if ($latest)
                <span class="ps-chip" role="listitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    آخرین فعالیت: <bdi dir="ltr">{{ $latest->last }}</bdi>
                </span>
            @endif
        </div>
    </section>

    {{-- پیام موفقیت --}}
    @if (session('ps-ok'))
        <div class="ps-ok animate-fade-up" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
            {{ session('ps-ok') }}
        </div>
    @endif

    {{-- ================== فهرست دستگاه‌ها ================== --}}
    <section class="ps-section animate-fade-up" style="animation-delay:.06s" aria-label="دستگاه‌های واردشده">
        <div class="ps-section-head">
            <h2 class="ps-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="8" x="5" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 18h.01"/><path d="M10 18h.01"/></svg>
                دستگاه‌ها
                <span class="ps-count-badge">{{ fa_number($sessions->count()) }}</span>
            </h2>
            <span class="ps-section-hint">نشست‌های بیش از ۲۴ ساعت بی‌فعالیت در این فهرست نمی‌آیند</span>
        </div>

        <div class="ps-devices">
            @forelse ($sessions as $s)
                @php($isMobile = str_contains($s->device, 'موبایل') || str_contains($s->device, 'آیفون'))
                <div class="ps-device {{ $s->is_current ? 'ps-device--current' : '' }} {{ $isMobile ? 'ps-device--mobile' : '' }}" wire:key="ps-{{ $s->id }}">
                    <span class="ps-device-icon" aria-hidden="true">
                        @if ($isMobile)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                        @endif
                    </span>
                    <div class="ps-device-body">
                        <div class="ps-device-name-row">
                            <span class="ps-device-name">{{ $s->device }}</span>
                            <span class="ps-browser-chip">{{ $s->browser }}</span>
                            @if ($s->is_current)
                                <span class="ps-current-badge"><span class="ps-pulse" aria-hidden="true"></span> این دستگاه</span>
                            @endif
                        </div>
                        <div class="ps-device-meta">
                            <span class="ps-meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                آخرین فعالیت: <bdi dir="ltr">{{ $s->last }}</bdi>
                            </span>
                            <span class="ps-meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                                IP: <bdi dir="ltr">{{ $s->ip }}</bdi>
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="ps-empty">
                    <div class="ps-empty-icon" aria-hidden="true">🖥️</div>
                    <div class="ps-empty-title">نشستی یافت نشد</div>
                    <div class="ps-empty-desc">فعالیت اخیری برای حساب شما ثبت نشده است.</div>
                </div>
            @endforelse
        </div>
    </section>

</div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/panel-sessions.css') }}?v=1">
@endpush
