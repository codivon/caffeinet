{{-- کلیدهای وب‌سرویس (فاز ۴۹ — بازطراحی مدرن) — از «تنظیمات» (آخرین منو) باز می‌شود.
     • افزودن فقط با مودال و «عنوان» الزامی؛ مقدار کلید خودکار ساخته می‌شود (cnk_…)
     • بعد از ساخت، کلید در همان مودال با دکمهٔ کپی نشان داده می‌شود
     • حذف با مودال تأیید (PanelUI.confirm — danger)
     • همهٔ درخواست‌های /api/v1 باید هدر «X-Api-Key» را بفرستند (میدل‌ویر api.key)
     • کلید داخلی وب‌اپ حذف‌شدنی نیست.
     استایل: assets/css/pages/api-keys.css (خودکفا + دارک‌مود) --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pages/api-keys.css') }}?v=1">
@endpush
<div x-data>
<style>[x-cloak]{display:none!important}</style>
<div class="ak-stack">

    {{-- ================== هیرو: بنر تیره + آمار شیشه‌ای ================== --}}
    <section class="ak-hero ak-in" aria-labelledby="ak-title">
        <div class="ak-hero-row">
            <span class="ak-hero-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
            </span>
            <div class="ak-hero-text">
                <h1 id="ak-title">کلیدهای وب‌سرویس <span class="ak-hero-chip">API v1</span></h1>
                <p>
                    هر برنامه‌ای که به وب‌سرویس وصل می‌شود — اپ موبایل، اسکریپت یا همکار خارجی — به یک کلید نیاز دارد و باید آن را در هدر
                    <code>X-Api-Key</code>
                    بفرستد. مقدار کلیدها خودکار ساخته می‌شود؛ اگر کلیدی لو رفت فقط حذفش کنید و یک کلید تازه بسازید.
                </p>
            </div>
            <div class="ak-hero-actions">
                <a href="{{ route('admin.api-docs.index') }}" wire:navigate class="ak-btn-docs" title="راهنمای کامل اندپوینت‌ها و هدرها">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    مستندات API
                </a>
                <button type="button" wire:click="openAdd" class="ak-btn-new">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    کلید جدید
                </button>
            </div>
        </div>

        {{-- آمار شیشه‌ای --}}
        <div class="ak-stats" role="list" aria-label="آمار کلیدها">
            <div class="ak-stat" role="listitem">
                <span class="ak-stat-icon is-green" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                </span>
                <span class="ak-stat-info">
                    <span class="ak-stat-label">کلیدهای فعال</span>
                    <span class="ak-stat-value">{{ fa_number(count($keys)) }}</span>
                </span>
            </div>
            <div class="ak-stat" role="listitem">
                <span class="ak-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </span>
                <span class="ak-stat-info">
                    <span class="ak-stat-label">مجموع درخواست‌ها</span>
                    <span class="ak-stat-value">{{ fa_number($totalHits) }}</span>
                </span>
            </div>
            <div class="ak-stat" role="listitem">
                <span class="ak-stat-icon is-blue" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </span>
                <span class="ak-stat-info">
                    <span class="ak-stat-label">آخرین استفاده</span>
                    <span class="ak-stat-value"><bdi dir="ltr">{{ $lastUsed ? fa_date($lastUsed) : '—' }}</bdi></span>
                </span>
            </div>
        </div>
    </section>

    {{-- پیام‌های فلش --}}
    @if (session('ak-ok'))
        <div class="ak-flash ak-flash--ok ak-in" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            <span>{{ session('ak-ok') }}</span>
        </div>
    @endif
    @if (session('ak-err'))
        <div class="ak-flash ak-flash--err ak-in" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span>{{ session('ak-err') }}</span>
        </div>
    @endif

    {{-- ================== نوار هدر الزامی ================== --}}
    <div class="ak-notice ak-in" style="--d:.04s" x-data="{ copied: false }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        <span>
            همهٔ درخواست‌های
            <code class="ak-mono-pill" dir="ltr">/api/v1/*</code>
            باید هدر
            <code class="ak-mono-pill" dir="ltr">X-Api-Key</code>
            را داشته باشند؛ درخواست بدون کلید یا با کلید نامعتبر، پاسخ
            <code class="ak-mono-pill" dir="ltr">401</code>
            می‌گیرد.
            <button type="button" class="ak-notice-copy" :class="copied && 'is-copied'"
                    @click="navigator.clipboard && navigator.clipboard.writeText('X-Api-Key').then(() => { copied = true; setTimeout(() => copied = false, 1600) })">
                <svg x-show="!copied" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                <span x-text="copied ? 'کپی شد' : 'کپی نام هدر'"></span>
            </button>
        </span>
    </div>

    {{-- ================== فهرست کلیدها ================== --}}
    <section class="ak-card ak-in" style="--d:.08s" aria-label="کلیدها">
        <div class="ak-section-head">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                کلیدها
            </h2>
            <span class="ak-section-hint">برای دیدن مقدار کامل، آیکون چشم را بزنید</span>
        </div>

        <div class="flex flex-col gap-3">
            @forelse ($keys as $k)
                <article class="ak-key {{ $k->is_internal ? 'ak-key--internal' : '' }}" wire:key="ak-{{ $k->id }}">
                    <div class="ak-key-top">
                        <span class="ak-key-avatar" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                        </span>

                        <div class="ak-key-id">
                            <div class="ak-key-titlerow">
                                <h3>{{ $k->name ?: 'بدون عنوان' }}</h3>
                                @if ($k->is_internal)
                                    <span class="ak-key-badge" title="این کلید خودکار در صفحات تزریق می‌شود و اپ وب/پنل‌ها با آن کار می‌کنند">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                                        داخلی وب‌اپ
                                    </span>
                                @endif
                            </div>
                            <p class="ak-key-meta">ساخته: <bdi dir="ltr">{{ fa_date($k->created_at) }}</bdi></p>
                        </div>

                        @unless ($k->is_internal)
                            <button
                                type="button"
                                x-data
                                data-name="{{ $k->name ?: 'بدون عنوان' }}"
                                @click="window.PanelUI ? PanelUI.confirm({
                                    title: 'حذف کلید؟',
                                    desc: 'کلید «' + $el.dataset.name + '» حذف شود؟ همهٔ برنامه‌های متصل با این کلید، درخواست‌هایشان با خطای ۴۰۱ رد می‌شود. این اقدام قابل بازگشت نیست.',
                                    okText: 'بله، حذف شود', cancelText: 'انصراف', danger: true, icon: 'warn',
                                }, () => $wire.deleteKey({{ $k->id }})) : (confirm('کلید حذف شود؟') && $wire.deleteKey({{ $k->id }}))"
                                wire:loading.attr="disabled" wire:target="deleteKey({{ $k->id }})"
                                class="ak-btn-del"
                                aria-label="حذف کلید {{ $k->name }}"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                حذف
                            </button>
                        @endunless
                    </div>

                    {{-- کی‌باکس ترمینالی: مقدار + نمایش + کپی --}}
                    <div class="ak-keybox" x-data="{ show: false, copied: false }">
                        <code>
                            <span x-show="show" style="display:none">{{ $k->key }}</span><span x-show="!show" class="ak-masked">{{ mask_key($k->key) }}</span>
                        </code>
                        <button type="button" class="ak-keybox-btn" @click="show = !show" :aria-pressed="show" :title="show ? 'پنهان‌کردن' : 'نمایش کلید'" aria-label="نمایش/پنهان‌کردن کلید">
                            <svg x-show="!show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.744 10.744 0 0 1-1.744 2.8"/><path d="M6.21 6.21a10.744 10.744 0 0 0-3.948 6.036 1 1 0 0 0 0 .696 10.75 10.75 0 0 0 15.246 4.67"/><path d="m2 2 20 20"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                        <button type="button" class="ak-keybox-btn" :class="copied && 'is-copied'" :title="copied ? 'کپی شد!' : 'کپی کلید'" aria-label="کپی کلید"
                                @click="navigator.clipboard && navigator.clipboard.writeText('{{ $k->key }}').then(() => { copied = true; setTimeout(() => copied = false, 1600) })">
                            <svg x-show="!copied" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            <svg x-show="copied" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        </button>
                    </div>

                    {{-- چیپ‌های متادیتا --}}
                    <div class="ak-chips">
                        <span class="ak-chip" title="تعداد درخواست‌های ثبت‌شده با این کلید">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                            {{ fa_number($k->requests_count) }} درخواست
                        </span>
                        <span class="ak-chip" title="آخرین باری که این کلید استفاده شد">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            آخرین استفاده: <bdi dir="ltr">{{ $k->last_used_at ? fa_date($k->last_used_at) : '—' }}</bdi>
                        </span>
                        @if ($k->is_internal)
                            <span class="ak-chip-note">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                                خودکار در صفحات تزریق می‌شود — اپ وب و پنل‌ها با آن کار می‌کنند
                            </span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="ak-empty">
                    <span class="ak-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                    </span>
                    <h3>هنوز کلیدی ساخته نشده است</h3>
                    <p>اولین کلید را بسازید و آن را به برنامهٔ مقصد (اپ موبایل، اسکریپت یا همکار) بدهید تا بتواند به وب‌سرویس وصل شود.</p>
                    <button type="button" wire:click="openAdd" class="ak-btn-new">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        ساخت اولین کلید
                    </button>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ================== نمونهٔ استفاده (پنجرهٔ کد تب‌دار) ================== --}}
    <section class="ak-card ak-in" style="--d:.12s" aria-label="نمونهٔ استفاده">
        <div class="ak-section-head">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                نمونهٔ استفاده
            </h2>
            <span class="ak-section-hint">مقدار واقعی کلیدتان را جای <span dir="ltr" class="font-mono">cnk_…</span> بگذارید</span>
        </div>

        <div class="ak-window" x-data="{ tab: 'curl', copied: false }">
            <div class="ak-window-head">
                <span class="ak-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                <div class="ak-tabs" role="tablist" aria-label="زبان نمونه">
                    <button type="button" class="ak-tab" :class="tab === 'curl' && 'is-active'" @click="copied = false; tab = 'curl'" role="tab" :aria-selected="tab === 'curl'">cURL</button>
                    <button type="button" class="ak-tab" :class="tab === 'js' && 'is-active'" @click="copied = false; tab = 'js'" role="tab" :aria-selected="tab === 'js'">JavaScript</button>
                    <button type="button" class="ak-tab" :class="tab === 'php' && 'is-active'" @click="copied = false; tab = 'php'" role="tab" :aria-selected="tab === 'php'">PHP</button>
                </div>
                <button type="button" class="ak-window-copy" :class="copied && 'is-copied'"
                        @click="const t = { curl: $refs.curl, js: $refs.js, php: $refs.php }[tab]; navigator.clipboard && navigator.clipboard.writeText(t.innerText.trim()).then(() => { copied = true; setTimeout(() => copied = false, 1600) })">
                    <svg x-show="!copied" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    <svg x-show="copied" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    <span x-text="copied ? 'کپی شد' : 'کپی کد'"></span>
                </button>
            </div>

            {{-- cURL --}}
            <pre class="ak-window-body" x-show="tab === 'curl'" x-ref="curl" tabindex="0"><code><span class="ak-c-cmd">curl</span> <span class="ak-c-str">"https://your-domain.ir/api/v1/services"</span> \
  <span class="ak-c-flag">-H</span> <span class="ak-c-str">"X-Api-Key: cnk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"</span> \
  <span class="ak-c-flag">-H</span> <span class="ak-c-str">"Authorization: Bearer 1|customer-token"</span></code></pre>

            {{-- JavaScript --}}
            <pre class="ak-window-body" x-show="tab === 'js'" x-ref="js" x-cloak tabindex="0"><code><span class="ak-c-com">// fetch با هدر X-Api-Key</span>
<span class="ak-c-var">const</span> res = <span class="ak-c-var">await</span> <span class="ak-c-cmd">fetch</span>(<span class="ak-c-str">"https://your-domain.ir/api/v1/services"</span>, {
  headers: {
    <span class="ak-c-str">"X-Api-Key"</span>: <span class="ak-c-str">"cnk_xxxxxxxxxxxxxxxxxxxxxxxx"</span>,
    <span class="ak-c-str">"Authorization"</span>: <span class="ak-c-str">"Bearer 1|customer-token"</span>,
  },
});
<span class="ak-c-var">const</span> data = <span class="ak-c-var">await</span> res.<span class="ak-c-cmd">json</span>();</code></pre>

            {{-- PHP --}}
            <pre class="ak-window-body" x-show="tab === 'php'" x-ref="php" x-cloak tabindex="0"><code><span class="ak-c-com">// Laravel HTTP Client</span>
$res = Http::<span class="ak-c-cmd">withToken</span>(<span class="ak-c-str">'1|customer-token'</span>)
    -&gt;<span class="ak-c-cmd">withHeaders</span>([<span class="ak-c-str">'X-Api-Key'</span> =&gt; <span class="ak-c-str">'cnk_xxxxxxxxxxxxxxxxxxxxxxxx'</span>])
    -&gt;<span class="ak-c-cmd">get</span>(<span class="ak-c-str">'https://your-domain.ir/api/v1/services'</span>);

<span class="ak-c-var">$data</span> = $res-&gt;<span class="ak-c-cmd">json</span>();</code></pre>
        </div>

        <ul class="ak-notes">
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <span>همهٔ اندپوینت‌های <code class="ak-mono-pill" dir="ltr">/api/v1/*</code> بدون کلید پاسخ <b>۴۰۱</b> می‌دهند — به‌جز <code class="ak-mono-pill" dir="ltr">/api/v1/health</code> که برای مانیتورینگ باز است.</span>
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                <span>هر کلید شمارندهٔ استفاده و آخرین زمان استفاده دارد — با آن می‌فهمید هر کلید دستِ کدام برنامه است.</span>
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                <span>مستندات کامل اندپوینت‌ها و هدرها: <a href="{{ route('admin.api-docs.index') }}" wire:navigate>مستندات API</a></span>
            </li>
        </ul>
    </section>

    {{-- ================== نکات امنیتی ================== --}}
    <section class="ak-tips ak-in" style="--d:.16s" aria-label="نکات امنیتی">
        <div class="ak-tip">
            <span class="ak-tip-icon t-amber" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
            </span>
            <div>
                <h4>محرمانگی کلید</h4>
                <p>کلید را فقط در اختیار برنامهٔ مقصد بگذارید؛ در کد عمومی، مخازن گیت یا پشتیبانی‌های اشتراکی قرار ندهید.</p>
            </div>
        </div>
        <div class="ak-tip">
            <span class="ak-tip-icon t-rose" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            </span>
            <div>
                <h4>اگر لو رفت</h4>
                <p>همان لحظه کلید را حذف کنید و یک کلید تازه بسازید؛ درخواست‌های کلید حذف‌شده بلافاصله با خطای ۴۰۱ رد می‌شوند.</p>
            </div>
        </div>
        <div class="ak-tip">
            <span class="ak-tip-icon t-green" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </span>
            <div>
                <h4>ردیابی مصرف</h4>
                <p>شمارندهٔ هر کلید نشان می‌دهد کدام برنامه در حال استفاده است؛ قبل از حذف، مالکش را خبر کنید تا سرویسش قطع نشود.</p>
            </div>
        </div>
    </section>

</div>

{{-- ================== مودال «کلید جدید» ================== --}}
<div class="ui-modal-backdrop" x-show="$wire.addOpen" x-cloak x-transition.opacity.duration.200ms
     @keydown.escape.window="$wire.closeAdd()" role="dialog" aria-modal="true" aria-labelledby="ak-modal-title">
    <div class="absolute inset-0" wire:click="closeAdd" aria-hidden="true"></div>

    <form wire:submit="addKey" x-transition.scale.95.duration.200ms
          class="ui-modal adm-modal-sm adm-modal-text-start"
          data-tone="{{ $createdKey ? 'success' : 'info' }}" novalidate>

        <div class="adm-modal-head">
            <h3 class="text-sm font-extrabold text-stone-800" id="ak-modal-title">
                {{ $createdKey ? 'کلید ساخته شد' : 'کلید وب‌سرویس جدید' }}
            </h3>
            <button type="button" class="adm-modal-x" wire:click="closeAdd" aria-label="بستن">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div class="adm-modal-body">
            @if ($createdKey)
                {{-- ---------- صفحهٔ موفقیت: نمایش کلید تازه + کپی ---------- --}}
                <span class="ak-modal-glyph g-success" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <p class="mt-2 text-center text-sm font-extrabold text-stone-800 dark:text-stone-100">کلید «{{ $createdKey['name'] }}» ساخته شد</p>
                <p class="mt-1 text-center text-[11px] leading-5 text-stone-400">از همین‌جا کپی کنید و در برنامهٔ مقصد در هدر <span dir="ltr" class="font-mono">X-Api-Key</span> قرار دهید.</p>

                <div class="ak-key-reveal" x-data="{ copied: false }">
                    <code>{{ $createdKey['key'] }}</code>
                    <button type="button" class="ak-copy-big" @click="navigator.clipboard && navigator.clipboard.writeText('{{ $createdKey['key'] }}').then(() => { copied = true; setTimeout(() => copied = false, 1600) })">
                        <svg x-show="!copied" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <svg x-show="copied" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <span x-text="copied ? 'کپی شد!' : 'کپی کلید'"></span>
                    </button>
                </div>

                <div class="ak-warn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                    <span>این مقدار بعداً هم در فهرست کلیدها قابل مشاهده است؛ اما فقط از طریق همین پنل. اگر لو رفت، کلید را حذف کنید.</span>
                </div>
            @else
                {{-- ---------- فرم ساخت: عنوان الزامی ---------- --}}
                <span class="ak-modal-glyph g-info" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                </span>

                <label class="lbl mt-2 block" for="ak-name">عنوان کلید <span class="text-rose-500" aria-hidden="true">*</span></label>
                <input
                    id="ak-name"
                    type="text"
                    wire:model="newName"
                    class="field"
                    placeholder="مثلاً: اپ موبایل همکار"
                    maxlength="60"
                    aria-required="true"
                    aria-describedby="ak-name-hint"
                >
                @error('newName')
                    <p class="mt-1.5 text-[11px] font-bold text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-[11px] leading-5 text-stone-400" id="ak-name-hint">
                    با یک عنوان مشخص، بعداً می‌فهمید هر کلید دستِ کدام برنامه است.
                    مقدار کلید به‌صورت خودکار با فرمت <span dir="ltr" class="font-mono">cnk_…</span> ساخته می‌شود و قابل ویرایش نیست.
                </p>
            @endif
        </div>

        <div class="adm-modal-foot">
            @if ($createdKey)
                <button type="button" wire:click="openAdd" class="btn-ghost">ساخت کلید دیگر</button>
                <button type="button" wire:click="closeAdd" class="btn-primary btn-shine">تمام شد</button>
            @else
                <button type="button" wire:click="closeAdd" class="btn-ghost">انصراف</button>
                <button type="submit" class="btn-primary btn-shine" wire:loading.attr="disabled" wire:target="addKey">
                    <span wire:loading.remove wire:target="addKey">ساخت خودکار کلید</span>
                    <span wire:loading wire:target="addKey" class="inline-flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z"/></svg>
                        در حال ساخت…
                    </span>
                </button>
            @endif
        </div>
    </form>
</div>

</div>
