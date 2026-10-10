{{-- فاز ۵۳ — «حساب خانواده» اپ مشتری — شِل Livewire؛ داده با family.js (CN.api)
     حساب اصلی: فهرست اعضا + ساخت عضو + سقف خرج + بلاک + حذف.
     زیرحساب: نمایش حساب والد. --}}


<div>
<div class="wallet-hero fade-up" style="background:linear-gradient(135deg,var(--brand-600),var(--brand-800))">
    <div class="w-label">کیف پول مشترک خانواده</div>
    <div class="w-balance"><span id="famBalance">—</span> <small>تومان</small></div>
    <div class="w-num" id="famNote">همهٔ اعضا از همین کیف پول خرج می‌کنند</div>
</div>

<div class="section fade-up d1">
    <h2>اعضای خانواده</h2>
    <button class="more" id="famAddBtn" type="button" style="background:none;border:none;cursor:pointer;color:var(--brand-600);font-weight:800;font-size:12px;display:none">+ عضو جدید</button>
</div>

<div class="card fade-up d2">
    <div id="famList" class="sk-zone" aria-live="polite">
        <div class="skeleton" style="height:64px"></div>
        <div class="skeleton" style="height:64px"></div>
    </div>

    <div class="empty-state hidden" id="famEmpty">
        <div class="e-icon">👨‍👩‍👧‍👦</div>
        <div class="e-title">هنوز عضوی ندارید</div>
        <div class="e-desc">برای همسر و فرزندان‌تان زیرحساب بسازید؛ هر عضو با شمارهٔ خودش وارد اپ می‌شود و از کیف پول شما (با سقف اختیاری) خرج می‌کند.</div>
    </div>
</div>

{{-- زیرحساب: اطلاعات حساب والد --}}
<div class="card fade-up d2 hidden" id="famParentCard">
    <div class="card-title">حساب اصلی شما</div>
    <p class="text-faint tiny" id="famParentText" style="margin:6px 0 0;line-height:2"></p>
</div>

{{-- شیت ساخت عضو --}}
<div class="sheet-wrap hidden" id="famSheet">
    <div class="sheet-backdrop" data-fam-close></div>
    <div class="sheet" role="dialog" aria-modal="true" aria-labelledby="famSheetTitle">
        <div class="sheet-handle" aria-hidden="true"></div>
        <h2 class="sheet-title" id="famSheetTitle">عضو جدید خانواده</h2>
        <p class="text-faint tiny" style="margin:4px 0 14px">با شمارهٔ موبایلی که وارد می‌کنید، عضو می‌تواند مثل شما وارد اپ شود.</p>

        <label class="field-label" for="famName">نام عضو *</label>
        <input class="field" id="famName" type="text" maxlength="60" placeholder="مثلاً: سارا">

        <label class="field-label mt-2" for="famFamily">نام‌خانوادگی (اختیاری)</label>
        <input class="field" id="famFamily" type="text" maxlength="60" placeholder="احمدی">

        <label class="field-label mt-2" for="famMobile">شماره موبایل عضو *</label>
        <input class="field" id="famMobile" type="tel" dir="ltr" maxlength="11" inputmode="numeric" placeholder="0912...">
        <p class="field-error hidden" id="famErr"></p>

        <button class="btn btn-primary btn-block mt-3" id="famSaveBtn" type="button">ساخت زیرحساب</button>
    </div>
</div>

{{-- شیت سقف خرج عضو --}}
<div class="sheet-wrap hidden" id="famLimitSheet">
    <div class="sheet-backdrop" data-limit-close></div>
    <div class="sheet" role="dialog" aria-modal="true" aria-labelledby="famLimitTitle">
        <div class="sheet-handle" aria-hidden="true"></div>
        <h2 class="sheet-title" id="famLimitTitle">سقف خرج عضو</h2>
        <p class="text-faint tiny" id="famLimitWho" style="margin:4px 0 14px"></p>

        <label class="field-label" for="famDaily">سقف خرج روزانه (تومان — خالی = بدون سقف)</label>
        <input class="field" id="famDaily" type="number" min="0" dir="ltr" inputmode="numeric" placeholder="بدون سقف">

        <label class="field-label mt-2" for="famMonthly">سقف خرج ماهانه (تومان — خالی = بدون سقف)</label>
        <input class="field" id="famMonthly" type="number" min="0" dir="ltr" inputmode="numeric" placeholder="بدون سقف">

        <label style="display:flex;align-items:center;gap:8px;margin-top:14px;font-size:12px;font-weight:700" for="famBlocked">
            <input type="checkbox" id="famBlocked" style="width:18px;height:18px;accent-color:var(--brand-600)">
            توقف کامل خرج این عضو
        </label>

        <p class="field-error hidden" id="famLimitErr"></p>

        <button class="btn btn-primary btn-block mt-3" id="famLimitSave" type="button">ذخیرهٔ سقف‌ها</button>
    </div>
</div>

@push('page')
<script src="{{ asset('front/assets/js/pages/family.js') }}?v=2" defer></script>
@endpush
</div>
