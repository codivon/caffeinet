{{-- سوئیچ زبان (فاز ۵۴ — بازطراحی فاز ۵۷) — همهٔ پنل‌ها + اپ مشتری
     شرط نمایش: قابلیت چندزبانه (features.i18n) روشن باشد ($i18nOn از middleware).

     [رفع باگ «باید چندبار بزنی تا عمل کنه»]:
     قبلاً دکمه‌ها <a wire:navigate> بودند — wire:navigate صفحه را از «کش/
     پیش‌بارگذاری» می‌آورد که با زبان قدیمی رندر شده بود، پس تغییر زبان فقط
     بعد از چند کلیک/ناوبری حس می‌شد. اکنون: کوکی ست می‌شود و «رفرش کامل»
     انجام می‌شود (location.reload) تا میدل‌ویر حتماً با زبان تازه رندر کند.
     دکمه‌ها <button> هستند تا Livewire پرچ پیش‌بارگذاری هم نزند. --}}
@php $current = $panelLang ?? 'fa'; @endphp
<div class="loc-switch" role="group" aria-label="{{ __('chrome.common.language') }}">
    <span class="loc-globe" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
    </span>
    <button type="button"
            class="loc-opt {{ $current === 'fa' ? 'is-active' : '' }}"
            aria-pressed="{{ $current === 'fa' ? 'true' : 'false' }}"
            onclick="document.cookie='cn_locale=fa;path=/;max-age=31536000';location.reload()"
            title="فارسی">فا</button>
    <button type="button"
            class="loc-opt {{ $current === 'en' ? 'is-active' : '' }}"
            aria-pressed="{{ $current === 'en' ? 'true' : 'false' }}"
            onclick="document.cookie='cn_locale=en;path=/;max-age=31536000';location.reload()"
            title="English">EN</button>
</div>
