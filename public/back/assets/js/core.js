/**
 * کافی‌نت آنلاین — اسکریپت پایه پنل‌های مدیریت (back)
 * فایل مستقل — بدون Node / بدون بیلد (لینک مستقیم در Blade)
 *
 * App.url      : ساخت URL با حفظ پارامتر گیت‌وی پیش‌نمایش
 * App.ajax     : درخواست fetch با CSRF و هدرهای JSON
 * App.toast    : نمایش اعلان موفقیت/خطا
 * App.money    : قالب‌بندی مبلغ تومانی
 * App.pageData : داده‌های سرور صفحه (از data-payload عنصر #page-data)
 * App.onNavigate : ثبت init برای بار اول + هر ناوبری SPA (wire:navigate) — [Task 2-a]
 */

/* ---------- [F-4] ثابت‌های فشرده‌سازی تصویر سمت کاربر ---------- */
const APP_IMG_MIN_BYTES = 350 * 1024;       // زیر این حجم دست نمی‌زنیم
const APP_IMG_PNG_KEEP_BYTES = 1536 * 1024; // PNG زیر ۱.۵MB همان PNG می‌ماند
const APP_IMG_MAX_SIDE = 2048;              // بزرگ‌ترین ضلع مجاز (پیش‌فرض)
const APP_IMG_QUALITY = 0.85;

/* v42 — پیکربندی فشرده‌سازی از تنظیمات مدیر (meta «upload-config») */
let __uploadCfg = null;
function uploadCfg() {
    if (__uploadCfg !== null) { return __uploadCfg; }
    __uploadCfg = {};
    try {
        const meta = document.querySelector('meta[name="upload-config"]');
        if (meta && meta.content) { __uploadCfg = JSON.parse(meta.content) || {}; }
    } catch (e) { /* پیش‌فرض */ }
    return __uploadCfg;
}

window.App = {
    /**
     * پارامتر گیت‌وی پیش‌نمایش (در پروداکشن null است)
     */
    gatewayPort: new URLSearchParams(window.location.search).get('XTransformPort'),

    /**
     * ساخت URL کامل با حفظ پارامتر گیت‌وی
     */
    url(path) {
        let url = String(path);
        if (this.gatewayPort && !url.includes('XTransformPort=')) {
            url += (url.includes('?') ? '&' : '?') + 'XTransformPort=' + encodeURIComponent(this.gatewayPort);
        }
        return url;
    },

    /**
     * درخواست AJAX (fetch) با CSRF و JSON
     *
     * @param {string} path مسیر نسبی مثل '/api/orders'
     * @param {object} options گزینه‌های fetch (method, body, ...)
     */
    async ajax(path, options = {}) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        const defaults = {
            method: options.method || 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
        };

        if (options.body && !(options.body instanceof FormData) && typeof options.body === 'object') {
            defaults.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        const response = await fetch(this.url(path), { ...defaults, ...options });

        if (response.redirected && response.url) {
            window.location = response.url;
        }

        if (!response.ok && response.status === 419) {
            this.toast('نشست شما منقضی شده است، صفحه نوسازی می‌شود…', 'error');
            setTimeout(() => window.location.reload(), 900);
        }

        return response;
    },

    /**
     * قالب‌بندی مبلغ به تومان با ارقام فارسی
     *
     * @param {number|string} value مبلغ (تومان)
     * @param {boolean} withUnit درج واحد «تومان»
     */
    money(value, withUnit = true) {
        const n = Number(value) || 0;
        const formatted = n.toLocaleString('fa-IR', { maximumFractionDigits: 0 });
        return withUnit ? formatted + ' تومان' : formatted;
    },

    /**
     * قالب‌بندی عدد (نه مبلغ) با ارقام فارسی — برای شمارنده‌ها و درصدها
     *
     * @param {number|string} value عدد
     */
    digits(value) {
        return (Number(value) || 0).toLocaleString('fa-IR', { maximumFractionDigits: 2 });
    },

    /**
     * داده‌های سروری صفحه — از ویژگی data-payload عنصر #page-data
     * (در Blade: <div id="page-data" hidden data-payload="{{ json_encode([...]) }}"></div>)
     *
     * @param {string|null} key کلید خاص؛ بدون کلید کل آبجکت
     * @returns {*} مقدار داده یا آبجکت کامل
     */
    pageData(key = null) {
        let data = {};
        try {
            const el = document.getElementById('page-data');
            data = JSON.parse(el ? (el.dataset.payload || '{}') : '{}') || {};
        } catch (e) {
            data = {};
        }
        return key === null ? data : (data[key] !== undefined ? data[key] : null);
    },

    /**
     * نمایش اعلان شناور — اگر PanelUI موجود باشد از توست مدرن آن استفاده می‌کند
     *
     * @param {string} message متن پیام
     * @param {'success'|'error'|'info'|'warn'} type نوع پیام
     */
    toast(message, type = 'success') {
        if (window.PanelUI && typeof window.PanelUI.toast === 'function') {
            window.PanelUI.toast(message, type);
            return;
        }

        const colors = {
            success: ['#2f9e63', '#e7f6ee'],
            error: ['#d24545', '#fdeaea'],
            info: ['#a8652e', '#fbf2e9'],
            warn: ['#d9910b', '#fdf5e3'],
        };
        const [fg, bg] = colors[type] ?? colors.info;

        const el = document.createElement('div');
        el.className = 'app-toast';
        el.dir = 'rtl';
        el.style.cssText = `
            position: fixed; top: 1.25rem; left: 1.25rem; z-index: 9999;
            display: flex; align-items: center; gap: .6rem;
            background: ${bg}; color: ${fg}; border-right: 4px solid ${fg};
            padding: .8rem 1.1rem; border-radius: .8rem; max-width: 22rem;
            font-size: .9rem; font-weight: 500; line-height: 1.6;
            box-shadow: 0 12px 34px -12px rgba(49, 25, 14, .45);
            animation: fade-up .4s cubic-bezier(.21,1.02,.73,1) both;
        `;
        el.innerHTML = `<span>${message}</span>`;
        document.body.appendChild(el);

        setTimeout(() => {
            el.style.transition = 'opacity .35s, transform .35s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-10px)';
            setTimeout(() => el.remove(), 400);
        }, 4200);
    },

    /**
     * [F-4] فشرده‌سازی تصویر در سمت کاربر — Promise «همیشه» resolve می‌شود
     * (هیچ‌وقت reject نمی‌شود تا آپلود هرگز مسدود نشود؛ در هر خطا فایل اصلی برمی‌گردد)
     *
     * قواعد: زیر ۳۵۰KB دست‌نخورده؛ هرگز بزرگ‌نمایی نمی‌کند؛ PNG کوچک/شفاف PNG می‌ماند؛
     * PNG بزرگ بدون شفافیت → JPEG (پس‌زمینه سفید + پسوند .jpg)؛ WebP → WebP؛
     * اگر خروجی کوچک‌تر نشد فایل اصلی برگردانده می‌شود؛ ObjectURL همیشه آزاد می‌شود.
     *
     * @param {File} file فایل تصویر (jpeg/png/webp)
     * @param {object} [opts] { maxSide: 2048, quality: 0.85 }
     * @returns {Promise<File>} فایل فشرده‌شده یا خود فایل اصلی
     */
    async compressImage(file, opts = {}) {
        const keep = () => file;
        let url = null;
        try {
            if (!file || typeof file !== 'object' || typeof file.size !== 'number') { return keep(); }
            const type = String(file.type || '').toLowerCase();
            if (type !== 'image/jpeg' && type !== 'image/png' && type !== 'image/webp') { return keep(); }
            if (file.size <= APP_IMG_MIN_BYTES) { return keep(); }
            if (!window.URL || typeof window.URL.createObjectURL !== 'function' || typeof window.File !== 'function') { return keep(); }

            const uc = uploadCfg();
            if (uc.enabled === false) { return keep(); } // v42 — فشرده‌سازی خاموش
            const maxSide = Number(opts.maxSide) > 0 ? Number(opts.maxSide)
                : (Number(uc.max_side) > 0 ? Number(uc.max_side) : APP_IMG_MAX_SIDE);
            const quality = (typeof opts.quality === 'number' && opts.quality > 0 && opts.quality <= 1) ? opts.quality
                : (Number(uc.quality) > 0 ? Math.min(1, Number(uc.quality) / 100) : APP_IMG_QUALITY);

            /* v43 — فرمت پیش‌فرض مدیر: keep|auto|jpeg|png|webp|gif|avif
               (کانواس GIF انکود نمی‌کند → PNG با حفظ شفافیت) */
            let fmt = String(uc.format || 'auto').toLowerCase();
            if (fmt === 'gif') { fmt = 'png'; }

            url = window.URL.createObjectURL(file);
            const img = new Image();

            const loaded = await new Promise((res) => {
                img.onload = () => res(true);
                img.onerror = () => res(false);
            });
            const w = img.naturalWidth || 0;
            const h = img.naturalHeight || 0;
            if (!loaded || !w || !h) { return keep(); }

            const scale = Math.min(1, maxSide / Math.max(w, h)); // هرگز بزرگ‌نمایی نمی‌کنیم
            const cw = Math.max(1, Math.round(w * scale));
            const ch = Math.max(1, Math.round(h * scale));

            const canvas = document.createElement('canvas');
            canvas.width = cw;
            canvas.height = ch;
            const ctx = canvas.getContext('2d');
            if (!ctx || typeof canvas.toBlob !== 'function') { return keep(); }

            ctx.drawImage(img, 0, 0, cw, ch);

            /* نمونه‌گیری ارزان شفافیت: ۴ گوشه + مرکز (فقط png/webp ممکن است آلفا داشته باشند) */
            const alphaSeen = () => {
                const pts = [[0, 0], [cw - 1, 0], [0, ch - 1], [cw - 1, ch - 1], [cw >> 1, ch >> 1]];
                try {
                    for (const [x, y] of pts) {
                        if (ctx.getImageData(x, y, 1, 1).data[3] < 255) { return true; }
                    }
                } catch (e) { return true; } // خواندن پیکسل ممکن نشد → محافظه‌کار: PNG بماند
                return false;
            };
            const alpha = (type !== 'image/jpeg') && alphaSeen();

            const toBlob = (mime, q) => new Promise((res) => {
                try { canvas.toBlob(res, mime, q); } catch (e) { res(null); }
            });

            const swapExt = (name, ext) => {
                name = String(name || 'image');
                if (/\.(png|jpe?g|webp|avif|gif)$/i.test(name)) { return name.replace(/\.(png|jpe?g|webp|avif|gif)$/i, '.' + ext); }
                return name + '.' + ext;
            };

            /* انکود PNG — شفافیت حفظ می‌شود */
            const outPng = async () => ({ blob: await toBlob('image/png'), type: 'image/png', name: file.name });
            /* انکود JPEG — پس‌زمینهٔ سفید (آلفا از قبل رد شده است) */
            const outJpeg = async () => {
                ctx.clearRect(0, 0, cw, ch);
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, cw, ch);
                ctx.drawImage(img, 0, 0, cw, ch);
                return { blob: await toBlob('image/jpeg', quality), type: 'image/jpeg', name: swapExt(file.name, 'jpg') };
            };
            /* انکود typed (webp/avif) — اگر انکودر نبود fallback */
            const outTyped = async (mime, ext) => ({ blob: await toBlob(mime, quality), type: mime, name: swapExt(file.name, ext) });

            /* تصمیم فرمت مقصد طبق تنظیمات مدیر */
            let target;
            if (fmt === 'keep') { target = type; }
            else if (fmt === 'jpeg') { target = alpha ? 'auto' : 'image/jpeg'; } // آلفا سمت کلاینت JPEG نمی‌شود
            else if (fmt === 'png') { target = 'image/png'; }
            else if (fmt === 'webp') { target = 'image/webp'; }
            else if (fmt === 'avif') { target = 'image/avif'; }
            else { target = alpha ? 'image/webp' : 'image/jpeg'; } // auto
            if (target === 'auto') { target = alpha ? 'image/webp' : 'image/jpeg'; }

            let out = null;
            if (target === 'image/avif') {
                out = await outTyped('image/avif', 'avif');
                if (!out.blob || out.blob.type !== 'image/avif') {
                    out = await outTyped('image/webp', 'webp');
                    if (!out.blob || out.blob.type !== 'image/webp') { out = alpha ? await outPng() : await outJpeg(); }
                }
            } else if (target === 'image/webp') {
                out = await outTyped('image/webp', 'webp');
                if (!out.blob || out.blob.type !== 'image/webp') { out = alpha ? await outPng() : await outJpeg(); }
            } else if (target === 'image/png') {
                out = await outPng();
            } else if (target === 'image/jpeg') {
                out = await outJpeg();
            } else {
                out = await outTyped(type, type === 'image/jpeg' ? 'jpg' : (type === 'image/png' ? 'png' : 'webp'));
                if (!out.blob || (out.blob.type && out.blob.type.toLowerCase() !== type)) { out = null; }
            }

            if (!out || !out.blob || out.blob.size >= file.size) { return keep(); }          // کوچک‌تر نشد
            const outType = String(out.blob.type || out.type).toLowerCase();
            if (outType !== String(out.type).toLowerCase()) { return keep(); }               // انکودر نداشت
            return new window.File([out.blob], out.name, { type: out.type, lastModified: Date.now() });
        } catch (e) {
            return keep();
        } finally {
            if (url) { try { window.URL.revokeObjectURL(url); } catch (e) { /* noop */ } }
        }
    },

    /**
     * [Task 2-a] پل init برای ناوبری SPA (wire:navigate)
     *
     * با wire:navigate رویداد DOMContentLoaded در ناوبری‌های بعدی شلیک نمی‌شود؛
     * لایووایر به‌جای آن «livewire:navigated» می‌فرستد (در بار اول هم شلیک می‌شود).
     *
     * این متد تابع init را هم برای بار اول و هم برای هر ناوبری بعدی اجرا می‌کند،
     * با گارد «حداکثر یک اجرا در هر نسل صفحه» تا اجرای دوباره (DOMContentLoaded +
     * livewire:navigated اولیه) باعث double-init (اینتروال تکراری و…) نشود.
     *
     * نکته: اسکریپت‌های مشترک لایه باید در Blade با data-navigate-once لینک شوند
     * تا خود فایل در هر ناوبری دوباره اجرا نشود؛ فقط این متد کروم تازه را bind می‌کند.
     *
     * @param {Function} fn تابع init صفحه/کروم (با DOM تازه صدا زده می‌شود)
     */
    onNavigate(fn) {
        if (typeof fn !== 'function') { return; }
        _navQueue.add(fn);
        _runOnNavigate(fn);
    },
};

/* ---------- موتور App.onNavigate (Task 2-a) ---------- */
const _navQueue = new Set();   // همهٔ initهای ثبت‌شده — در هر ناوبری اجرا می‌شوند
let _navEpoch = 0;             // نسل صفحه: ۰ = بار اول، ۱+ = بعد از هر ناوبری SPA
let _firstNavigatedSeen = false; // livewire:navigated بار اول هم شلیک می‌شود (بدون تعویض DOM)
const _fnEpoch = new WeakMap(); // آخرین نسلی که هر fn در آن اجرا شده (گارد اجرای واحد)

function _runOnNavigate(fn) {
    if (_fnEpoch.get(fn) === _navEpoch) { return; } // در همین نسل قبلاً اجرا شده
    _fnEpoch.set(fn, _navEpoch);
    try {
        fn();
    } catch (e) {
        if (window.console && console.error) { console.error('[App.onNavigate]', e); }
    }
}

window.addEventListener('livewire:navigated', () => {
    // اولین شلیک پس از بار اول صفحه = همان نسل ۰؛ از ناوبری بعدی نسل جدید است
    if (_firstNavigatedSeen) { _navEpoch++; }
    _firstNavigatedSeen = true;
    _navQueue.forEach(_runOnNavigate);
});

// افزودن پارامتر گیت‌وی به لینک‌های خام data-gateway — با onNavigate تا در SPA هم اعمال شود
window.App.onNavigate(() => {
    // افزودن پارامتر گیت‌وی به لینک‌های خام data-gateway
    document.querySelectorAll('a[data-gateway]').forEach((a) => {
        a.setAttribute('href', window.App.url(a.getAttribute('href')));
    });
});

// اعلان آماده‌سازی (این فایل به‌صورت classic script پیش از اسکریپت‌های صفحه اجرا می‌شود)
window.dispatchEvent(new Event('app:ready'));

/* ============================================================
   [Task 8] بازخورد SPA در پنل‌ها — نوار پیشرفت ناوبری + لودینگ دکمه‌ها
   ============================================================ */

/* ---------- ۱) نوار پیشرفت بالای صفحه هنگام ناوبری ---------- */
(function () {
    let bar = null, hideTimer = null;

    function start() {
        if (!bar) {
            bar = document.createElement('div');
            bar.className = 'spa-progress';
            bar.setAttribute('aria-hidden', 'true');
            (document.body || document.documentElement).appendChild(bar);
        }
        clearTimeout(hideTimer);
        bar.classList.remove('spa-progress--done');
        bar.classList.remove('spa-progress--active');
        void bar.offsetWidth; // ری‌استارت انیمیشن
        bar.classList.add('spa-progress--active');
    }

    function done() {
        if (!bar || !bar.classList.contains('spa-progress--active')) { return; }
        bar.classList.add('spa-progress--done');
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => { if (bar) { bar.classList.remove('spa-progress--active', 'spa-progress--done'); } }, 420);
    }

    window.addEventListener('livewire:navigate', start);
    window.addEventListener('alpine:navigate', start);
    window.addEventListener('livewire:navigated', done);
})();

/* ---------- ۲) لودینگ روی دکمه‌ها تا پایان پردازش ----------
   • درخواست jQuery (ajaxStart..ajaxStop) → کلاس is-busy تا پایان
   • اکشن فوری (مودال/تب) → آزادسازی بعد از ۳۵۰ms بدون چشمک
   • مستثناها: data-no-loading، سوییچ تم، زنگ اعلان، چیپ‌های فیلتر */
(function () {
    let busyBtn = null, busyTimer = null, busyAjax = 0;

    function release() {
        clearTimeout(busyTimer);
        if (busyBtn) {
            busyBtn.classList.remove('is-busy');
            busyBtn.removeAttribute('aria-busy');
            busyBtn = null;
        }
    }

    document.addEventListener('click', (e) => {
        const el = e.target.closest('button, [role="button"], .btn, a[class*="btn-"], a.ln');
        if (!el || el.disabled) { return; }
        if (el.hasAttribute('data-no-loading') || el.hasAttribute('data-theme-toggle') || el.hasAttribute('data-nb-toggle')) { return; }
        if (el.classList.contains('chip') || el.classList.contains('ui-tab')) { return; }
        if (el.tagName === 'A' && el.getAttribute('wire:navigate') !== null) { return; }
        if (el.classList.contains('is-busy')) { return; }
        release();
        busyBtn = el;
        el.classList.add('is-busy');
        el.setAttribute('aria-busy', 'true');
        clearTimeout(busyTimer);
        busyTimer = setTimeout(() => { if (busyAjax === 0) { release(); } }, 350);
    }, true);

    if (window.jQuery) {
        window.jQuery(document).ajaxStart(() => { busyAjax++; clearTimeout(busyTimer); });
        window.jQuery(document).ajaxStop(() => { busyAjax = 0; if (busyBtn) { release(); } });
    }
})();
