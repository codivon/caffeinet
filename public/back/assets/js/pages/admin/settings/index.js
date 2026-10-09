/**
 * کافی‌نت آنلاین — اسکریپت صفحه «تنظیمات» (بازطراحی درخواست بازخوردی ۶-۵)
 * فایل مستقل (Blade + jQuery) — بدون Node / بدون بیلد
 *
 * [فاز ۱۲-fix] سازگاری SPA (wire:navigate):
 * این اسکریپت در هر ناوبری دوباره اجرا می‌شود (بدون data-navigate-once)؛
 * بنابراین هیچ const/let سراسری نباید داشته باشد — اجرای دوم با
 * «Identifier has already been declared» کل فایل را می‌کشت و هیچ‌کدام
 * از تب‌ها کار نمی‌کرد (بعد از رفرش درست می‌شد). هر سه بلوک اکنون IIFE است.
 */
(function () {
    /* ---------- ناوبری سکشن‌ها ---------- */
    const navItems = document.querySelectorAll('.st-nav-item[data-section]');
    const sections = document.querySelectorAll('.st-section');

    function activate(sectionId) {
        navItems.forEach(b => b.classList.toggle('is-active', b.dataset.section === sectionId));
        sections.forEach(s => s.classList.toggle('hidden', s.id !== 'sec-' + sectionId));
    }

    navItems.forEach(btn => {
        btn.addEventListener('click', () => activate(btn.dataset.section));
    });

    /* hash اولیه: #sms و مانند آن */
    if (location.hash) {
        const want = location.hash.replace('#', '');
        if (document.getElementById('sec-' + want)) activate(want);
    }

    /* ---------- فاز ۱۲ — روش Realtime: نمایش/پنهان‌سازی زون‌ها ----------
     * polling → فقط توضیح؛ sse → کارت راهنمای SSE؛ pusher → کلیدها + هاست سفارشی */
    const rtZoneFor = (method) => ({
        polling: { sse: false, pusher: false },
        sse: { sse: true, pusher: false },
        pusher: { sse: false, pusher: true },
    }[method] || { sse: false, pusher: false });

    function applyRtMethod(method) {
        const z = rtZoneFor(method);
        document.getElementById('rt-sse-zone')?.classList.toggle('hidden', !z.sse);
        document.getElementById('rt-pusher-zone')?.classList.toggle('hidden', !z.pusher);
    }

    document.querySelectorAll('input[name="rt-method"][data-key="realtime.method"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.checked) { applyRtMethod(radio.value); }
        });
    });
    applyRtMethod(
        (document.querySelector('input[name="rt-method"][data-key="realtime.method"]:checked') || {}).value || 'polling'
    );

    /* ---------- v37 — اجرای دستی زمان‌بندی‌ها (کارت سلامت کرون) ---------- */
    document.getElementById('st-cron-run')?.addEventListener('click', async () => {
        const btn = document.getElementById('st-cron-run');
        const box = document.getElementById('st-cron-run-result');
        const card = document.getElementById('st-cron-card');

        btn.disabled = true;
        btn.classList.add('cn-push-busy');

        if (box) {
            box.classList.remove('hidden');
            box.innerHTML = '<p class="st-cron-sub !text-amber-700">در حال اجرای زمان‌بندی‌ها… چند ثانیه صبر کنید.</p>';
        }

        try {
            const res = await App.ajax('/admin/settings/cron-run', { method: 'POST' });
            const data = await res.json().catch(() => ({}));

            if (res.ok && data.ok) {
                if (box) {
                    box.innerHTML = '<p class="st-cron-sub !text-emerald-700">'
                        + (data.message || 'اجرا شد.')
                        + '</p><pre dir="ltr" class="font-mono text-[10px] leading-4 mt-1 p-2 rounded-lg bg-stone-100 dark:bg-stone-800 overflow-x-auto max-h-40 text-stone-600 dark:text-stone-300">'
                        + String(data.output || '').replace(/[<>]/g, c => ({ '<': '&lt;', '>': '&gt;' }[c]))
                        + '</pre>';
                }

                // نشانگر همین‌جا سبز/قرمز شود (بدون رفرش صفحه)
                if (card && data.status && data.status.healthy) {
                    card.classList.remove('st-cron-status--bad');
                    card.classList.add('st-cron-status--ok');
                }
            } else {
                if (box) { box.innerHTML = '<p class="st-cron-sub !text-red-700">اجرای دستی ناموفق بود — لاگ سرور را بررسی کنید.</p>'; }
            }
        } catch {
            if (box) { box.innerHTML = '<p class="st-cron-sub !text-red-700">ارتباط با سرور برقرار نشد.</p>'; }
        } finally {
            btn.disabled = false;
            btn.classList.remove('cn-push-busy');
        }
    });

    /* ---------- پیامک: کارت‌های پرووایدر + کارت تنظیمات فعال (v13) ----------
     * هر پرووایدری که انتخاب شود، فقط کارت تنظیمات همان پرووایدر زیرش باز می‌شود. */
    const SMS_LABELS = {
        log: 'لاگ (محیط توسعه)',
        kavenegar: 'کاوه‌نگار',
        fraasms: 'فراز اس‌ام‌اس',
        ippanel: 'آی‌پی‌پنل',
        melipayamak: 'ملی‌پیامک',
        idehpardazan: 'ایده‌پردازان',
    };

    const smsProviderInput = document.getElementById('s-provider');
    const smsCards = document.querySelectorAll('#sec-sms .st-prov-card[data-prov]');
    const smsPanels = document.querySelectorAll('#sec-sms .st-gw[data-gw]');
    const smsBadge = document.getElementById('sms-provider-badge');
    const smsNavHint = document.querySelector('.st-nav-item[data-section="sms"] .st-nav-hint');

    function syncSmsProvider(value) {
        const v = value || 'log';

        if (smsProviderInput) smsProviderInput.value = v;
        smsCards.forEach(c => c.classList.toggle('is-selected', c.dataset.prov === v));
        smsPanels.forEach(p => p.classList.toggle('is-open', p.dataset.gw === v));

        const label = SMS_LABELS[v] ?? v;
        if (smsBadge) {
            smsBadge.textContent = label;
            smsBadge.className = 'badge ' + (v === 'log'
                ? 'bg-stone-100 text-stone-500 border border-stone-200'
                : 'bg-emerald-50 text-emerald-700 border border-emerald-200');
        }
        if (smsNavHint) smsNavHint.textContent = label;
    }

    smsCards.forEach(card => {
        const radio = card.querySelector('input[type="radio"]');
        radio?.addEventListener('change', () => {
            if (radio.checked) syncSmsProvider(radio.value);
        });
    });
    syncSmsProvider(smsProviderInput?.value);

    /* ---------- درگاه پرداخت: کارت‌های درایور + کارت پذیرندگی فعال (v13) ----------
     * هر درگاهی که فعال شود، فقط تنظیمات همان درگاه زیرش باز می‌شود. */
    const PAY_LABELS = {
        local: 'درگاه تست (local)',
        zarinpal: 'زرین‌پال',
        zibal: 'زیبال',
        behpardakht: 'بانک ملت',
        sep: 'بانک ملی',
        sepehr: 'درگاه سپهر',
    };

    const payDriverInput = document.getElementById('p-driver');
    const payCards = document.querySelectorAll('#sec-payment .st-prov-card[data-driver]');
    const payPanels = document.querySelectorAll('#sec-payment .st-gw[data-gw]');
    const payBadge = document.getElementById('pay-driver-badge');
    const payNavHint = document.querySelector('.st-nav-item[data-section="payment"] .st-nav-hint');

    function syncPayDriver(value) {
        const v = value || 'local';

        if (payDriverInput) payDriverInput.value = v;
        payCards.forEach(c => c.classList.toggle('is-selected', c.dataset.driver === v));
        payPanels.forEach(p => p.classList.toggle('is-open', p.dataset.gw === v));

        const label = PAY_LABELS[v] ?? v;
        if (payBadge) {
            payBadge.textContent = label;
            payBadge.className = 'badge ' + (v === 'local'
                ? 'bg-stone-100 text-stone-500 border border-stone-200'
                : 'bg-emerald-50 text-emerald-700 border border-emerald-200');
        }
        if (payNavHint) payNavHint.textContent = label;
    }

    payCards.forEach(card => {
        const radio = card.querySelector('input[type="radio"]');
        radio?.addEventListener('change', () => {
            if (radio.checked) syncPayDriver(radio.value);
        });
    });
    syncPayDriver(payDriverInput?.value);

    /* ---------- کارکنان: همگام‌سازی کارت سیاست تایید ---------- */
    const hiringInput = document.getElementById('s-hiring-mode');
    document.querySelectorAll('input[name="staff-hiring-mode"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.checked && hiringInput) {
                hiringInput.value = radio.value;
            }
        });
    });
    if (hiringInput) { hiringInput.value = hiringInput.value || 'auto'; }

    /* ---------- ذخیره هر فرم (AJAX) ---------- */
    /* v43 — تب «آپلود و فشرده‌سازی»: کارت‌های حالت + گرید فرمت + خلاصهٔ زندهٔ کارت وضعیت */
  (function () {
    const box = document.getElementById('up-presets');
    const fmtBox = document.getElementById('up-formats');
    const FMT_LABELS = { keep: 'اصل', auto: 'هوشمند', jpeg: 'JPG', png: 'PNG', webp: 'WebP', gif: 'GIF', avif: 'AVIF' };

    function sync() {
      const checked = box ? box.querySelector('input[type=radio]:checked') : null;
      const isCustom = checked && checked.value === 'custom';
      if (box) { box.querySelectorAll('.up2-preset').forEach(l => l.classList.toggle('on', l.contains(checked))); }
      const custom = document.getElementById('up-custom-box');
      if (custom) { custom.classList.toggle('is-locked', !isCustom); }

      // خلاصهٔ زندهٔ کارت وضعیت
      const sumMode = document.getElementById('up2-sum-mode');
      if (sumMode) { sumMode.textContent = (checked && checked.dataset.label) || '—'; }
      const fmtChecked = fmtBox ? fmtBox.querySelector('input[type=radio]:checked') : null;
      if (fmtBox) { fmtBox.querySelectorAll('.up2-fmt').forEach(l => l.classList.toggle('on', l.contains(fmtChecked))); }
      const sumFmt = document.getElementById('up2-sum-fmt');
      if (sumFmt) { sumFmt.textContent = FMT_LABELS[fmtChecked ? fmtChecked.value : ''] || '—'; }
      const ms = document.getElementById('up-max-side');
      const q = document.getElementById('up-quality');
      const sumDim = document.getElementById('up2-sum-dim');
      if (sumDim) { sumDim.textContent = (ms && ms.value ? ms.value + 'px' : '—') + ' / ' + (q && q.value ? '٪' + q.value : '—'); }
      const dimChip = document.getElementById('up2-chip-dim');
      if (dimChip) { dimChip.classList.toggle('is-muted', !isCustom); }
    }

    if (box) { box.addEventListener('change', sync); }
    if (fmtBox) { fmtBox.addEventListener('change', sync); }
    ['up-max-side', 'up-quality'].forEach(id => {
      const el = document.getElementById(id);
      if (el) { el.addEventListener('input', sync); }
    });

    // v43 — نمایش/قفل CRF ویدیو بر اساس کلید ویدیو
    const vsw = document.getElementById('up-video-enabled');
    const crfRow = document.getElementById('up2-crf-row');
    function syncVideo() { if (crfRow) { crfRow.classList.toggle('is-locked', !(vsw && vsw.checked)); } }
    if (vsw) { vsw.addEventListener('change', syncVideo); }

    sync();
    syncVideo();
  })();

document.querySelectorAll('form[data-group]').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const values = {};
            form.querySelectorAll('[data-key]').forEach(input => {
                if (input.type === 'checkbox') {
                    values[input.dataset.key] = input.checked ? '1' : '0';
                    return;
                }
                // فاز ۱۲ — رادیو: فقط گزینهٔ انتخاب‌شده ارسال شود (مثل گروه rt-method)
                if (input.type === 'radio') {
                    if (input.checked) { values[input.dataset.key] = input.value; }
                    return;
                }
                const v = input.value.trim();
                // فیلد رمز-like خالی → کلید ارسال نشود (موجود حفظ شود)
                if (input.hasAttribute('data-empty-skip') && v === '') return;
                values[input.dataset.key] = v;
            });

            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            const original = btn.innerHTML;
            btn.innerHTML = '<span class="size-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> ذخیره...';

            try {
                const res = await App.ajax('/admin/settings', {
                    method: 'PUT',
                    body: { group: form.dataset.group, values },
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    App.toast(data.message || 'ذخیره شد.', 'success');

                    // v26 — کلید VAPID وب‌پوش ممکن است تازه ساخته شده باشد
                    if (data.webpush_public) {
                        const vapidInput = document.getElementById('ns-vapid-public');
                        if (vapidInput) { vapidInput.value = data.webpush_public; }
                    }
                } else {
                    App.toast(data.message || 'خطا در ذخیره‌سازی.', 'error');
                }
            } catch {
                App.toast('ارتباط با سرور برقرار نشد.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        });
    });

    /* ذخیره پاداش معرفی (اندپوینت اختصاصی) */
    const referralForm = document.getElementById('sec-referral');
    referralForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = referralForm.querySelector('button[type="submit"]');
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<span class="size-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> ذخیره...';

        try {
            const isActive = document.getElementById('r-active').checked;
            const res = await App.ajax('/admin/settings/referral', {
                method: 'PUT',
                body: {
                    introduction_reward: parseFloat(document.getElementById('r-reward').value) || 0,
                    per_order_type: document.getElementById('r-per-type').value,
                    per_order_value: parseFloat(document.getElementById('r-per-value').value) || 0,
                    is_active: isActive,
                },
            });
            const data = await res.json().catch(() => ({}));
            App.toast(data.message || (res.ok ? 'ذخیره شد.' : 'خطا در ذخیره‌سازی.'), res.ok ? 'success' : 'error');

            /* به‌روزرسانی زندهٔ نشانگرها (بدون رفرش) */
            if (res.ok) {
                const dot = document.querySelector('.st-nav-item[data-section="referral"] .st-nav-dot');
                if (dot) {
                    dot.classList.toggle('st-nav-dot--on', isActive);
                    dot.title = isActive ? 'فعال' : 'غیرفعال';
                }
                const badge = document.getElementById('r-badge');
                if (badge) {
                    badge.textContent = isActive ? 'فعال' : 'غیرفعال';
                    badge.className = isActive
                        ? 'badge bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : 'badge bg-stone-100 text-stone-500 border border-stone-200';
                }
            }
        } catch {
            App.toast('ارتباط با سرور برقرار نشد.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    /* ---------- v25: تب اعلان‌ها — صدا + پوش دستگاه ---------- */

    /* ۱) تیک «صدای پیش‌فرض» ↔ آپلودر صدای سفارشی */
    const nsDefault = document.getElementById('ns-default');
    const nsCustomZone = document.getElementById('ns-custom-zone');
    const nsDefaultLabel = document.getElementById('ns-default-label');

    function syncSoundDefault() {
        if (!nsDefault || !nsCustomZone) { return; }

        const useDefault = nsDefault.checked;

        nsCustomZone.classList.toggle('hidden', useDefault);

        if (nsDefaultLabel) {
            nsDefaultLabel.textContent = useDefault
                ? 'صدای پیش‌فرض فعال است (ding کوتاه)'
                : 'صدای سفارشی';
        }
    }

    nsDefault?.addEventListener('change', syncSoundDefault);
    syncSoundDefault();

    /* آدرس صدای «فعلی» برای پخش تست */
    function currentSoundUrl() {
        const chip = document.getElementById('ns-current-chip');
        const useDefault = !nsDefault || nsDefault.checked;
        const customUrl = chip && chip.dataset.url ? chip.dataset.url : null;

        if (!useDefault && customUrl) { return customUrl; }

        return App.url('/assets/sounds/notify.mp3');
    }

    /* ۲) پخش تست صدا */
    let nsTestAudio = null;

    document.getElementById('ns-play-btn')?.addEventListener('click', () => {
        try {
            if (!nsTestAudio || nsTestAudio.src !== currentSoundUrl()) {
                nsTestAudio = new Audio(currentSoundUrl());
            }
            nsTestAudio.currentTime = 0;
            const p = nsTestAudio.play();
            if (p && typeof p.catch === 'function') { p.catch(() => {}); }
            App.toast('در حال پخش صدا… برای قطع، صفحه‌ای با صدا باز نکنید 🙂', 'info');
        } catch {
            App.toast('پخش صدا در این مرورگر ممکن نشد.', 'error');
        }
    });

    /* ۳) آپلود صدای سفارشی */
    const nsFile = document.getElementById('ns-file');
    const nsUploadBtn = document.getElementById('ns-upload-btn');

    nsUploadBtn?.addEventListener('click', () => nsFile?.click());

    nsFile?.addEventListener('change', async () => {
        if (!nsFile.files || !nsFile.files.length) { return; }

        const fd = new FormData();
        fd.append('sound', nsFile.files[0]);

        nsUploadBtn.disabled = true;

        try {
            const res = await App.ajax('/admin/settings/notification/sound', { method: 'POST', body: fd });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                App.toast(data.message || 'صدا ذخیره شد.', 'success');

                const chip = document.getElementById('ns-current-chip');
                if (chip) {
                    chip.textContent = 'فایل فعلی: ' + (data.name || 'صدا');
                    chip.dataset.url = data.url || '';
                    chip.className = 'badge bg-emerald-50 text-emerald-700 border border-emerald-200';
                }

                const delBtn = document.getElementById('ns-delete-btn');
                if (delBtn) { delBtn.classList.remove('hidden'); }

                // آپلودر باز می‌ماند تا در صورت نیاز جایگزین شود
            } else {
                App.toast(data.message || 'آپلود صدا ناموفق بود.', 'error');
            }
        } catch {
            App.toast('ارتباط با سرور برقرار نشد.', 'error');
        } finally {
            nsUploadBtn.disabled = false;
            nsFile.value = '';
        }
    });

    /* ۴) حذف صدای سفارشی */
    document.getElementById('ns-delete-btn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;

        try {
            const res = await App.ajax('/admin/settings/notification/sound', { method: 'DELETE' });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                App.toast(data.message || 'حذف شد.', 'success');

                const chip = document.getElementById('ns-current-chip');
                if (chip) {
                    chip.textContent = 'فایل سفارشی ندارید';
                    chip.dataset.url = '';
                    chip.className = 'badge bg-stone-100 text-stone-500 border border-stone-200';
                }
                btn.classList.add('hidden');
                if (nsDefault) { nsDefault.checked = true; }
                syncSoundDefault();
            } else {
                App.toast(data.message || 'حذف ناموفق بود.', 'error');
            }
        } catch {
            App.toast('ارتباط با سرور برقرار نشد.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ۵) پرووایدر پوش (خاموش / پیش‌فرض / پوشر / فایربیس) — v26 */
    const nsProviderInput = document.getElementById('ns-provider');
    const fbZone = document.getElementById('ns-firebase-zone');
    const webpushZone = document.getElementById('ns-webpush-zone');
    const pusherZone = document.getElementById('ns-pusher-zone');
    const credZone = document.getElementById('ns-credentials-zone');
    const offlineRow = document.getElementById('ns-offline-row');
    const offlineSecondsRow = document.getElementById('ns-offline-seconds-row');
    const offlineSwitch = document.getElementById('ns-offline-enabled');

    /* v29 — آستانهٔ آفلاین: سوییچ روشن → ورودی ثانیه نمایش داده شود */
    function syncOfflineRows(providerVal) {
        const providerOn = ['default', 'pusher', 'firebase'].includes(providerVal);
        const thresholdOn = !!(offlineSwitch && offlineSwitch.checked);

        if (offlineRow) { offlineRow.classList.toggle('hidden', !providerOn); }
        if (offlineSecondsRow) { offlineSecondsRow.classList.toggle('hidden', !providerOn || !thresholdOn); }
    }

    function syncPushProvider(value) {
        const v = value || 'off';
        const providers = ['off', 'default', 'pusher', 'firebase'];

        if (nsProviderInput) { nsProviderInput.value = v; }

        document.querySelectorAll('input[name="ns-push-provider"]').forEach(radio => {
            const card = radio.closest('label');
            if (!card) { return; }

            if (radio.value === v) {
                card.className = 'flex items-center gap-2.5 cursor-pointer select-none px-4 py-3 rounded-xl border '
                    + (v === 'off' ? 'border-stone-400 bg-stone-50' : 'border-amber-500 bg-amber-50');
            } else {
                card.className = 'flex items-center gap-2.5 cursor-pointer select-none px-4 py-3 rounded-xl border border-stone-200';
            }
        });

        if (webpushZone) { webpushZone.classList.toggle('hidden', v !== 'default'); }
        if (pusherZone) { pusherZone.classList.toggle('hidden', v !== 'pusher'); }
        if (fbZone) { fbZone.classList.toggle('hidden', v !== 'firebase'); }
        if (credZone) { credZone.classList.toggle('hidden', v !== 'firebase'); }
        syncOfflineRows(v);
    }

    document.querySelectorAll('input[name="ns-push-provider"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.checked) { syncPushProvider(radio.value); }
        });
    });
    syncPushProvider(nsProviderInput?.value);

    /* ۵-الف) v38 — وضعیت آنلاین/آفلاین کاربران (تصمیم مدیر برای نوتیف سیستمی) */
    const nsUserStatusInput = document.getElementById('ns-user-status');
    const nsUserStatusDesc = document.getElementById('ns-ust-desc');
    const UST_LABELS = { offline: 'آفلاین', online: 'آنلاین', auto: 'خودکار' };

    function ustDescHtml(mode) {
        if (mode === 'online') {
            return '<b>آنلاین:</b> کاربران آنلاین فرض می‌شوند — نوتیف سیستمی ارسال نمی‌شود؛ فقط زنگ درون‌برنامه‌ای و Realtime پنل. مناسب وقتی مطمئنید کاربران پای پنل/اپ هستند و پوش اضافه نمی‌خواهید.';
        }
        if (mode === 'auto') {
            return '<b>خودکار:</b> برای هر کاربر جداگانه از آخرین حضورش (آستانهٔ پایین همین بخش) تشخیص داده می‌شود — کاربر آنلاین فقط زنگ درون‌برنامه‌ای می‌گیرد؛ اگر تا ۱۵ دقیقه بعد آفلاین شد، پوش همان لحظه برایش ارسال می‌شود.';
        }
        return '<b>آفلاین (پیش‌فرض):</b> کاربران آفلاین فرض می‌شوند — نوتیف سیستمی (پوش دستگاه) <b>همیشه و بلافاصله</b> برای همهٔ گیرندگان ارسال می‌شود؛ حتی وقتی برنامه/پنل باز است. مطمئن‌ترین حالت — هیچ خبری از دست نمی‌رود.';
    }

    function syncUserStatus(value) {
        const v = ['offline', 'online', 'auto'].includes(value) ? value : 'offline';

        if (nsUserStatusInput) { nsUserStatusInput.value = v; }
        if (nsUserStatusDesc) { nsUserStatusDesc.innerHTML = ustDescHtml(v); }

        document.querySelectorAll('input[name="ns-user-status"]').forEach(radio => {
            const card = radio.closest('.ns-ust-card');
            if (card) { card.classList.toggle('ns-ust-card--on', radio.value === v && radio.checked); }
        });

        syncOfflineDesc(); // توضیح آستانه به وضعیت وابسته است
    }

    document.querySelectorAll('input[name="ns-user-status"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.checked) { syncUserStatus(radio.value); }
        });
    });

    /* ۵-الف) v29 → v38 — توضیح زندهٔ آستانهٔ آفلاین (سوییچ + ثانیه)
     * v38: در حالت «خودکار» همین آستانه معیار ارسال پوش است؛ در دو حالت
     * دیگر فقط «نمایش حضور» را تعیین می‌کند */
    const nsOfflineDesc = document.getElementById('ns-offline-desc');
    const nsOfflineSec = document.getElementById('fb-offline-sec');

    function syncOfflineDesc() {
        if (!nsOfflineDesc) { return; }

        const mode = nsUserStatusInput ? nsUserStatusInput.value : 'offline';
        const on = !!(offlineSwitch && offlineSwitch.checked);
        const sec = Math.max(45, parseInt(nsOfflineSec && nsOfflineSec.value, 10) || 45);
        const secFa = sec.toLocaleString('fa-IR');

        if (mode === 'auto') {
            nsOfflineDesc.innerHTML = on
                ? 'حالت «خودکار»: کاربرِ بدونِ درخواستِ بیشتر از <b>' + secFa + '</b> ثانیه آفلاین تلقی می‌شود و <b>نوتیف سیستمی فقط برای او</b> ارسال می‌شود (کاربرِ آنلاین فقط زنگ درون‌برنامه‌ای می‌گیرد؛ اگر تا ۱۵ دقیقه بعد آفلاین شد پوش می‌رسد).'
                : 'حالت «خودکار» + آستانهٔ کوتاه: برنامهٔ بسته حداکثر تا ۴۵ ثانیه بعد آفلاین تلقی می‌شود و نوتیف سیستمی برایش ارسال می‌شود.';
            return;
        }

        nsOfflineDesc.innerHTML = on
            ? 'کاربرِ بدونِ درخواستِ بیشتر از <b>' + secFa + '</b> ثانیه «آفلاین» نشان داده می‌شود (جزئیات کاربران، داشبورد و سربرگ چت). در حالت «' + (UST_LABELS[mode] || mode) + '» این آستانه فقط نمایش حضور است. بستن برنامه معمولاً همان لحظه (بیکن pagehide) یا حداکثر تا همین مدت بعد، وضعیت را آفلاین می‌کند.'
            : '<b>کوتاه (۴۵ ثانیه):</b> برنامهٔ بسته حداکثر تا ۴۵ ثانیه بعد «آفلاین» نمایش داده می‌شود — نمایش حضور؛ ارسال پوش طبق «وضعیت کاربران» بالای همین بخش است.';
    }

    offlineSwitch?.addEventListener('change', () => {
        syncOfflineRows(nsProviderInput ? nsProviderInput.value : 'off');
        syncOfflineDesc();
    });
    nsOfflineSec?.addEventListener('input', syncOfflineDesc);
    syncUserStatus(nsUserStatusInput ? nsUserStatusInput.value : 'offline');

    /* ۵-الف) کپی کلید عمومی VAPID */
    document.getElementById('ns-copy-vapid')?.addEventListener('click', async (e) => {
        const input = document.getElementById('ns-vapid-public');
        if (!input || !input.value.trim()) {
            App.toast('کلیدی برای کپی وجود ندارد؛ ابتدا «پیش‌فرض» را ذخیره کنید.', 'info');
            return;
        }

        try {
            await navigator.clipboard.writeText(input.value.trim());
            App.toast('کلید عمومی VAPID کپی شد.', 'success');
        } catch {
            input.select();
            document.execCommand('copy');
            App.toast('کلید عمومی VAPID کپی شد.', 'success');
        }
    });

    /* ۵-ب) بازتولید کلیدهای VAPID وب‌پوش داخلی */
    document.getElementById('ns-regen-vapid')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;

        if (!confirm('کلیدهای وب‌پوش از نو ساخته شوند؟ دستگاه‌هایی که قبلاً نوتیف دستگاه را فعال کرده بودند باید دوباره فعالش کنند.')) {
            return;
        }

        btn.disabled = true;

        try {
            const res = await App.ajax('/admin/settings/notification/webpush-keys', { method: 'POST' });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                App.toast(data.message || 'کلیدهای جدید ساخته شد.', 'success');
                const input = document.getElementById('ns-vapid-public');
                if (input && data.public_key) { input.value = data.public_key; }
            } else {
                App.toast(data.message || 'بازتولید ناموفق بود.', 'error');
            }
        } catch {
            App.toast('ارتباط با سرور برقرار نشد.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ۶) آپلود Service Account فایربیس */
    const fbCredFile = document.getElementById('fb-cred-file');
    const fbCredUploadBtn = document.getElementById('fb-cred-upload-btn');

    fbCredUploadBtn?.addEventListener('click', () => fbCredFile?.click());

    fbCredFile?.addEventListener('change', async () => {
        if (!fbCredFile.files || !fbCredFile.files.length) { return; }

        const fd = new FormData();
        fd.append('credentials', fbCredFile.files[0]);

        fbCredUploadBtn.disabled = true;

        try {
            const res = await App.ajax('/admin/settings/notification/push-credentials', { method: 'POST', body: fd });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                App.toast(data.message || 'اعتبارنامه ذخیره شد.', 'success');

                const chip = document.getElementById('fb-cred-chip');
                if (chip) {
                    chip.textContent = 'ذخیره‌شده ✓';
                    chip.className = 'badge bg-emerald-50 text-emerald-700 border border-emerald-200';
                }
                document.getElementById('fb-cred-delete-btn')?.classList.remove('hidden');

                const projectInput = document.getElementById('fb-project');
                if (projectInput && !projectInput.value.trim() && data.project_id) {
                    projectInput.value = data.project_id;
                }
            } else {
                App.toast(data.message || 'آپلود ناموفق بود.', 'error');
            }
        } catch {
            App.toast('ارتباط با سرور برقرار نشد.', 'error');
        } finally {
            fbCredUploadBtn.disabled = false;
            fbCredFile.value = '';
        }
    });

    /* ۷) حذف اعتبارنامه */
    document.getElementById('fb-cred-delete-btn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;

        try {
            const res = await App.ajax('/admin/settings/notification/push-credentials', { method: 'DELETE' });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                App.toast(data.message || 'حذف شد.', 'success');
                const chip = document.getElementById('fb-cred-chip');
                if (chip) {
                    chip.textContent = 'بارگذاری‌نشده';
                    chip.className = 'badge bg-stone-100 text-stone-500 border border-stone-200';
                }
                btn.classList.add('hidden');
            } else {
                App.toast(data.message || 'حذف ناموفق بود.', 'error');
            }
        } catch {
            App.toast('ارتباط با سرور برقرار نشد.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ۸) تست پوش فایربیس (ارسال واقعی از گوگل) */
    document.getElementById('btn-test-push')?.addEventListener('click', async () => {
        const btn = document.getElementById('btn-test-push');
        btn.disabled = true;

        try {
            const res = await App.ajax('/admin/settings/test-push', { method: 'POST' });
            const data = await res.json().catch(() => ({}));
            App.toast(data.message || (res.ok ? 'ارسال شد.' : 'ارسال ناموفق بود.'), res.ok ? 'success' : 'error');
        } catch {
            App.toast('ارتباط با سرور برقرار نشد.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ۹) پیش‌نمایش محلی نوتیف دستگاه (بدون گوگل — از طریق SW) */
    document.getElementById('btn-preview-push')?.addEventListener('click', async () => {
        if (typeof Notification === 'undefined') {
            App.toast('این مرورگر نوتیف سیستم‌عامل را پشتیبانی نمی‌کند.', 'error');
            return;
        }

        if (Notification.permission === 'default') {
            try { await Notification.requestPermission(); } catch { /* noop */ }
        }

        if (!window.CNPush) {
            App.toast('پیش‌نمایش در دسترس نیست.', 'error');
            return;
        }

        try {
            const shown = await CNPush.simulate({
                notification: { title: 'پیش‌نمایش نوتیف دستگاه — کافی‌نت آنلاین', body: 'نوتیف‌های سیستم‌عامل به همین شکل روی گوشی/ویندوز شما نمایش داده می‌شوند.' },
                data: { url: '/admin/settings#notifications', tag: 'cn-preview' },
            });

            App.toast(shown
                ? 'نوتیف پیش‌نمایش ارسال شد — نوار اعلان سیستم‌عامل خود را ببینید. 🔔'
                : 'Service Worker هنوز آماده نیست؛ صفحه را یک‌بار تازه کنید و دوباره بزنید.', 'info');
        } catch {
            App.toast('اجرای پیش‌نمایش ممکن نشد.', 'error');
        }
    });

    /* ---------- تست اتصال Realtime (فاز ۱۳/۱۲ — ترابورت فعال) ---------- */
    document.getElementById('btn-test-pusher')?.addEventListener('click', async () => {
        const btn = document.getElementById('btn-test-pusher');
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<span class="size-4 border-2 border-stone-300 border-t-stone-600 rounded-full animate-spin inline-block align-middle me-1"></span> در حال اتصال...';

        try {
            const res = await App.ajax('/admin/settings/test-pusher', { method: 'POST' });
            const data = await res.json().catch(() => ({}));
            App.toast(data.message || (res.ok ? 'اتصال برقرار است.' : 'اتصال ناموفق بود.'), res.ok ? 'success' : 'error');
        } catch {
            App.toast('ارتباط با سرور برقرار نشد.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    /* پیامک آزمایشی */
    const smsModal = document.getElementById('sms-modal');
    document.getElementById('btn-test-sms')?.addEventListener('click', () => {
        smsModal.classList.remove('hidden');
        smsModal.classList.add('flex');
    });
    smsModal.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', () => {
        smsModal.classList.add('hidden');
        smsModal.classList.remove('flex');
    }));

    document.getElementById('sms-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = smsModal.querySelector('.err[data-for="mobile"]');
        errEl.classList.add('hidden');

        const mobile = document.getElementById('t-mobile').value.trim();
        if (!/^09\d{9}$/.test(mobile)) {
            errEl.textContent = 'فرمت موبایل صحیح نیست.';
            errEl.classList.remove('hidden');
            return;
        }

        const btn = document.getElementById('sms-send');
        btn.disabled = true;
        btn.innerHTML = '<span class="size-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> ارسال...';

        try {
            const res = await App.ajax('/admin/settings/test-sms', { method: 'POST', body: { mobile } });
            const data = await res.json().catch(() => ({}));
            App.toast(data.message || (res.ok ? 'ارسال شد.' : 'خطا در ارسال.'), res.ok ? 'success' : 'error');
            if (res.ok) {
                smsModal.classList.add('hidden');
                smsModal.classList.remove('flex');
            }
        } finally {
            btn.disabled = false;
            btn.textContent = 'ارسال';
        }
    });
})();

/* ---------- فینوتک: تست اتصال (v40) + ساعت کاری + نظرسنجی ----------
 * [فاز ۱۲-fix] این بلوک قبلاً بیرون IIFE بود (const سراسری) → اجرای دومِ
 * اسکریپت در ناوبری SPA با SyntaxError می‌مرد. حالا IIFE اختصاصی دارد. */
(function () {
    /* فینوتک — اول باید تنظیمات ذخیره شده باشد (سرویس از دیتابیس می‌خواند). */
    const finTestBtn = document.getElementById('finTestBtn');
    finTestBtn?.addEventListener('click', async () => {
        const resultEl = document.getElementById('finTestResult');
        if (!resultEl) return;

        finTestBtn.disabled = true;
        const original = finTestBtn.innerHTML;
        finTestBtn.innerHTML = '<span class="size-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> در حال تست...';
        resultEl.textContent = 'در حال ارتباط با فینوتک…';
        resultEl.className = 'text-[11px] text-stone-400 leading-5';

        try {
            const res = await App.ajax('/admin/settings/finnotech-test', { method: 'POST', body: {} });
            const data = await res.json().catch(() => ({}));

            if (res.ok && data.succeeded) {
                resultEl.textContent = '✓ ' + (data.message || 'اتصال موفق بود.');
                resultEl.className = 'text-[11px] text-teal-600 font-bold leading-5';
            } else {
                resultEl.textContent = '✗ ' + (data.message || 'اتصال ناموفق بود.');
                resultEl.className = 'text-[11px] text-red-600 font-bold leading-5';
            }
            App.toast(data.message || (res.ok ? 'اتصال موفق.' : 'اتصال ناموفق.'), res.ok ? 'success' : 'error');
        } catch {
            resultEl.textContent = '✗ ارتباط با سرور برقرار نشد.';
            resultEl.className = 'text-[11px] text-red-600 font-bold leading-5';
        } finally {
            finTestBtn.disabled = false;
            finTestBtn.innerHTML = original;
        }
    });

    /* ---------- ساعت کاری (فاز ۱۵) — چیپ‌های روز هفته ----------
     * انتخاب چیپ‌ها به hidden input با data-key="workhours.days" sync می‌شود
     * تا همان سازوکار عمومی ذخیرهٔ فرم، مقدار را ارسال کند. */
    const whDaysInput = document.getElementById('wh-days');
    const whChips = document.querySelectorAll('.wh-day-chip');

    function syncWhDays() {
        if (!whDaysInput) return;
        const days = [...document.querySelectorAll('.wh-day-chip.is-on')]
            .map(c => c.dataset.day);
        whDaysInput.value = days.join(',');
    }

    whChips.forEach(chip => {
        chip.addEventListener('click', () => {
            // جلوگیری از خالی شدن کامل لیست روزها
            if (chip.classList.contains('is-on') && document.querySelectorAll('.wh-day-chip.is-on').length === 1) {
                App.toast('حداقل یک روز کاری باید انتخاب باشد.', 'warn');
                return;
            }
            chip.classList.toggle('is-on');
            chip.setAttribute('aria-pressed', chip.classList.contains('is-on') ? 'true' : 'false');
            syncWhDays();
        });
    });
    syncWhDays();

    /* همگام‌سازی نشانگر ناوبری پس از ذخیره */
    const whForm = document.getElementById('sec-workhours');
    whForm?.addEventListener('submit', () => {
        setTimeout(() => {
            const hint = document.querySelector('[data-wh-hint]');
            if (hint) hint.textContent = document.getElementById('wh-enabled')?.checked ? 'فعال' : 'خاموش';
        }, 900);
    }, { once: false });

    /* ---------- نظرسنجی و پخش هوشمند (v33) ---------- */
    const rtSurvey = document.getElementById('rt-survey-enabled');
    const rtNotify = document.getElementById('rt-notify-low');
    const rtThresholdRow = document.getElementById('rt-notify-threshold-row');
    const rtRouting = document.getElementById('rt-routing-enabled');
    const rtRoutingBox = document.getElementById('rt-routing-box');
    const rtRoutingDesc = document.getElementById('rt-routing-desc');
    const rtModeHint = document.getElementById('rt-mode-hint');
    const rtModeSelect = document.getElementById('rt-routing-mode');

    rtNotify?.addEventListener('change', () => {
        rtThresholdRow?.classList.toggle('hidden', !rtNotify.checked);
    });

    rtRouting?.addEventListener('change', () => {
        rtRoutingBox?.classList.toggle('hidden', !rtRouting.checked);
        if (rtRoutingDesc) {
            rtRoutingDesc.innerHTML = rtRouting.checked
                ? 'فعال — سفارش‌های جدید فقط/اول به کافی‌netهای با امتیاز خوب پخش می‌شوند (بر اساس سیاست انتخابی).'
                : 'خاموش — سفارش‌ها مثل قبل به همهٔ کافی‌netهای فعالِ محدوده پخش می‌شوند.';
        }
    });

    const MODE_HINTS = {
        hybrid: 'ترکیبی: ابتدا فقط کافی‌netهای واجد شرایط؛ اگر هیچ‌کدام نبود، برای نجات سفارش به همه پخش می‌شود.',
        filter: 'سخت‌گیرانه: اگر هیچ کافی‌netی واجد شرایط نباشد، سفارش به صف تعیین‌تکلیف (تخصیص دستی) می‌رود.',
        priority: 'اولویت‌بندی: هیچ کافی‌netی حذف نمی‌شود؛ فقط ترتیب اطلاع‌رسانی/پخش بر اساس امتیاز است.',
    };

    rtModeSelect?.addEventListener('change', () => {
        if (rtModeHint) rtModeHint.textContent = MODE_HINTS[rtModeSelect.value] || '';
    });
})();


/* ═══════════════════════════════════════════════════════════════
   ظاهر و رنگ‌بندی (Appearance) — پالت اختصاصی هر پنل
   پیش‌نمایش زنده با GET settings/appearance-css و ذخیره با PUT settings/appearance
   ═══════════════════════════════════════════════════════════════ */
(function () {
    const dataEl = document.getElementById('ap-data');
    const section = document.getElementById('sec-appearance');
    if (!dataEl || !section) return;

    let data;
    try { data = JSON.parse(dataEl.textContent); } catch { return; }

    const SHADES = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];

    /* ---------- وضعیت ---------- */
    const initial = JSON.parse(JSON.stringify(data.selected));
    let panel = 'admin';
    let selections = Object.assign({}, data.selected);   // کلید پالت هر پنل (ممکن است ذخیره‌نشده باشد)
    let customs = {};                                     // توکن‌های شخصی هر پنل (در حال ویرایش)
    let warmths = {};                                     // دمای سرد/گرم هر پنل (پالت‌های آماده)
    const initialWarmths = {};
    Object.keys(data.panels).forEach(k => {
        customs[k] = data.customs[k] ? JSON.parse(JSON.stringify(data.customs[k])) : null;
        warmths[k] = Number((data.warmths && data.warmths[k]) || 0);
        initialWarmths[k] = warmths[k];
    });

    /* ---------- ابزارهای DOM ---------- */
    const q = (sel) => section.querySelector(sel);
    const qa = (sel) => Array.from(section.querySelectorAll(sel));

    /* ---------- استایل پیش‌نمایش زنده ---------- */
    let liveStyle = document.getElementById('ap-live-style');
    if (!liveStyle) {
        liveStyle = document.createElement('style');
        liveStyle.id = 'ap-live-style';
        document.head.appendChild(liveStyle);
    }
    let previewTimer = null;

    function previewCss(params) {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(async () => {
            try {
                const qs = new URLSearchParams(params).toString();
                const res = await fetch('/admin/settings/appearance-css?' + qs, { headers: { 'Accept': 'text/css' } });
                if (res.ok) liveStyle.textContent = await res.text();
            } catch { /* noop — پیش‌نمایش اختیاری است */ }
        }, 120);
    }

    function previewCurrent() {
        const pal = selections[panel] || 'default';
        if (pal === 'custom' && customs[panel]) {
            previewCss({ panel, custom: JSON.stringify(customs[panel]) });
        } else {
            const params = { panel, palette: pal };
            if ((warmths[panel] || 0) !== 0) { params.warmth = String(warmths[panel] || 0); }
            previewCss(params);
        }
    }

    /* ---------- رندر UI ---------- */
    function panelChips() { return qa('.ap-panel-chip'); }

    function syncPanelChips() {
        panelChips().forEach(chip => {
            const on = chip.dataset.apPanel === panel;
            chip.classList.toggle('is-active', on);
            chip.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }

    function syncPaletteGrid() {
        qa('.ap-palette').forEach(card => {
            const on = card.dataset.apPalette === (selections[panel] || 'default');
            card.classList.toggle('is-selected', on);
            card.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }

    function palName(key) {
        return (data.palettes[key] && data.palettes[key].name) || key;
    }

    function syncStatus() {
        const st = q('[data-ap-status]');
        if (st) {
            const isDirty = (selections[panel] !== (initial[panel] || 'default'))
                || (Number(warmths[panel] || 0) !== Number(initialWarmths[panel] || 0));
            st.innerHTML = 'پالت فعال این پنل: <b>' + palName(selections[panel] || 'default') + '</b>'
                + (isDirty ? ' — <span class="text-amber-600 font-bold">ذخیره نشده (پیش‌نمایش فعال است)</span>' : '');
        }
        qa('[data-ap-panel-badge]').forEach(b => {
            const chip = b.closest('.ap-panel-chip');
            if (chip) b.textContent = palName(selections[chip.dataset.apPanel] || 'default');
        });
        const navHint = document.querySelector('.st-nav-item[data-section="appearance"] .st-nav-hint');
        if (navHint) navHint.textContent = palName(selections[panel] || 'default');
    }

    /* ---------- ویرایشگر شخصی‌سازی ---------- */
    const customBox = () => q('[data-ap-custom]');

    function fillEditor(tokens) {
        if (!tokens) return;
        const base = q('#ap-base'), baseHex = q('#ap-base-hex');
        if (base) base.value = tokens.base || tokens.ramp['600'] || '#2563eb';
        if (baseHex) baseHex.value = base ? base.value : '#2563eb';
        const temp = q('#ap-temp');
        if (temp) { temp.value = String(tokens.temperature ?? 0); syncTempLabel(); }
        SHADES.forEach(s => {
            const input = q('[data-ap-shade="' + s + '"]');
            if (input && tokens.ramp && tokens.ramp[s]) input.value = tokens.ramp[s];
        });
        const pb = q('#ap-pagebg'), pbHex = q('#ap-pagebg-hex');
        if (pb && tokens.page_bg) pb.value = tokens.page_bg;
        if (pbHex) pbHex.value = pb ? pb.value : (tokens.page_bg || '#dbeafe');
        if (tokens.semantic) {
            Object.entries(tokens.semantic).forEach(([k, v]) => {
                const input = q('[data-ap-sem="' + k + '"]');
                if (input) input.value = v;
            });
        }
        if (tokens.sidebar) {
            tokens.sidebar.forEach((c, i) => {
                const input = q('[data-ap-sb="' + i + '"]');
                if (input) input.value = c;
            });
        }
        const sbt = q('[data-ap-sbtext]');
        if (sbt && tokens.sidebar_text) sbt.value = tokens.sidebar_text;
    }

    function readEditor() {
        const ramp = {};
        let ok = true;
        SHADES.forEach(s => {
            const input = q('[data-ap-shade="' + s + '"]');
            if (input && /^#[0-9a-fA-F]{6}$/.test(input.value)) ramp[s] = input.value.toLowerCase();
            else ok = false;
        });
        const tokens = {};
        if (ok) tokens.ramp = ramp;
        const pb = q('#ap-pagebg');
        if (pb && /^#[0-9a-fA-F]{6}$/.test(pb.value)) tokens.page_bg = pb.value.toLowerCase();
        const sem = {};
        qa('[data-ap-sem]').forEach(input => {
            if (/^#[0-9a-fA-F]{6}$/.test(input.value)) sem[input.dataset.apSem] = input.value.toLowerCase();
        });
        if (Object.keys(sem).length === 4) tokens.semantic = sem;
        const sb = [];
        qa('[data-ap-sb]').forEach(input => {
            if (/^#[0-9a-fA-F]{6}$/.test(input.value)) sb.push(input.value.toLowerCase());
        });
        if (sb.length === 4) tokens.sidebar = sb;
        const sbt = q('[data-ap-sbtext]');
        if (sbt && /^#[0-9a-fA-F]{6}$/.test(sbt.value)) tokens.sidebar_text = sbt.value.toLowerCase();
        tokens.base = (q('#ap-base') || {}).value || ramp['600'];
        tokens.temperature = Number((q('#ap-temp') || {}).value || 0);
        return tokens;
    }

    /* ---------- تولید طیف از رنگ پایه + دما (سرد/گرم) ---------- */
    function hexToHsl(hex) {
        const m = /^#?([0-9a-f]{6})$/i.exec(hex);
        if (!m) return null;
        const n = parseInt(m[1], 16);
        const r = ((n >> 16) & 255) / 255, g = ((n >> 8) & 255) / 255, b = (n & 255) / 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h = 0, s = 0;
        const l = (max + min) / 2;
        if (max !== min) {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            if (max === r) h = ((g - b) / d + (g < b ? 6 : 0));
            else if (max === g) h = (b - r) / d + 2;
            else h = (r - g) / d + 4;
            h *= 60;
        }
        return { h, s: s * 100, l: l * 100 };
    }

    function hslToHex(h, s, l) {
        h = ((h % 360) + 360) % 360;
        s = Math.max(0, Math.min(100, s)) / 100;
        l = Math.max(0, Math.min(100, l)) / 100;
        const c = (1 - Math.abs(2 * l - 1)) * s;
        const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
        const m = l - c / 2;
        let rgb;
        if (h < 60) rgb = [c, x, 0];
        else if (h < 120) rgb = [x, c, 0];
        else if (h < 180) rgb = [0, c, x];
        else if (h < 240) rgb = [0, x, c];
        else if (h < 300) rgb = [x, 0, c];
        else rgb = [c, 0, x];
        const hex = rgb.map(v => Math.round((v + m) * 255).toString(16).padStart(2, '0'));
        return '#' + hex.join('');
    }

    /* منحنی روشنایی/اشباع شبیه ramp های Tailwind — سایه‌های تیره‌تر کمی خنک‌تر می‌شوند */
    const L_CURVE = { '50': 97, '100': 94, '200': 86, '300': 77, '400': 66, '500': 54, '600': 47, '700': 40, '800': 33, '900': 26, '950': 17 };
    const S_CURVE = { '50': 84, '100': 90, '200': 94, '300': 92, '400': 92, '500': 90, '600': 88, '700': 84, '800': 78, '900': 72, '950': 74 };
    const H_SHIFT = { '50': 4, '100': 3, '200': 2, '300': 1, '400': 0, '500': 0, '600': -2, '700': -4, '800': -6, '900': -8, '950': -10 };

    function generateRamp(baseHex, temperature) {
        const hsl = hexToHsl(baseHex);
        if (!hsl) return null;
        const out = {};
        SHADES.forEach(s => {
            const hue = hsl.h + (H_SHIFT[s] || 0) + (temperature || 0);
            out[s] = hslToHex(hue, S_CURVE[s], L_CURVE[s]);
        });
        return out;
    }

    function syncTempLabel() {
        const temp = q('#ap-temp'), label = q('[data-ap-temp-label]');
        if (!temp || !label) return;
        const v = Number(temp.value);
        label.textContent = v === 0 ? 'خنثی' : (v > 0 ? 'گرم +' + v : 'سرد ' + v);
    }

    function regenerateFromBase() {
        const base = q('#ap-base');
        const temp = q('#ap-temp');
        if (!base || !/^#[0-9a-fA-F]{6}$/.test(base.value)) return;
        const ramp = generateRamp(base.value, Number((temp || {}).value || 0));
        if (!ramp) return;
        SHADES.forEach(s => {
            const input = q('[data-ap-shade="' + s + '"]');
            if (input) input.value = ramp[s];
        });
        // پس‌زمینهٔ صفحه هم از طیف مشتق شود (تا هماهنگ بمانند)
        const pb = q('#ap-pagebg');
        if (pb) pb.value = ramp['100'];
        const pbHex = q('#ap-pagebg-hex');
        if (pbHex) pbHex.value = ramp['100'];
        // سایدبار (همیشه تیره) از سایهٔ ۹۰۰ مشتق شود — ۴ ایست + متن
        const hsl900 = hexToHsl(ramp['900']);
        if (hsl900) {
            const stops = [
                hslToHex(hsl900.h + 2, hsl900.s, Math.min(92, hsl900.l + 5)),
                ramp['900'],
                hslToHex(hsl900.h - 2, hsl900.s, Math.max(4, hsl900.l - 7)),
                hslToHex(hsl900.h - 4, hsl900.s, Math.max(3, hsl900.l - 14)),
            ];
            stops.forEach((c, i) => {
                const input = q('[data-ap-sb="' + i + '"]');
                if (input) input.value = c;
            });
            const sbt = q('[data-ap-sbtext]');
            if (sbt) sbt.value = ramp['100'];
        }
    }

    /* ---------- دمای سرد/گرم مشترک (برای همهٔ پالت‌های آماده) ---------- */
    const warmthBox = () => q('[data-ap-warmth]');

    function syncTempLabelFor(value, label) {
        if (!label) return;
        const v = Number(value) || 0;
        label.textContent = v === 0 ? 'خنثی' : (v > 0 ? 'گرم +' + v : 'سرد ' + v);
    }

    function syncWarmthBox() {
        const box = warmthBox();
        if (!box) return;
        const isCustom = (selections[panel] || 'default') === 'custom';
        box.classList.toggle('hidden', isCustom); // شخصی‌سازی دمای اختصاصی خودش را دارد
        const slider = q('#ap-palette-warmth');
        if (slider) slider.value = String(warmths[panel] || 0);
        syncTempLabelFor(warmths[panel] || 0, q('[data-ap-warmth-label]'));
    }

    /* ---------- رویدادها ---------- */
    panelChips().forEach(chip => {
        chip.addEventListener('click', () => {
            panel = chip.dataset.apPanel;
            syncPanelChips();
            syncPaletteGrid();
            syncStatus();
            const isCustom = (selections[panel] || 'default') === 'custom';
            customBox().classList.toggle('hidden', !isCustom);
            syncWarmthBox();
            if (isCustom) {
                fillEditor(customs[panel] || tokensOfPalette(selections[panel]));
            }
            previewCurrent();
        });
    });

    function tokensOfPalette(key) {
        const p = data.palettes[key];
        if (!p) return null;
        return {
            ramp: p.ramp,
            page_bg: p.page_bg || p.ramp['100'],
            sidebar: p.sidebar || null,
            sidebar_text: p.sidebar_text || p.ramp['100'],
        };
    }

    qa('.ap-palette').forEach(card => {
        card.addEventListener('click', () => {
            selections[panel] = card.dataset.apPalette;
            // دما مال «پنل» است نه پالت — هنگام تعویض پالت حفظ می‌شود
            syncPaletteGrid();
            syncStatus();
            const isCustom = selections[panel] === 'custom';
            customBox().classList.toggle('hidden', !isCustom);
            syncWarmthBox();
            if (isCustom && !customs[panel]) {
                // اولین ورود به شخصی‌سازی: از پالت قبلی پنل شروع کن
                const seed = tokensOfPalette(initial[panel] || 'default');
                customs[panel] = seed;
                fillEditor(seed);
            }
            previewCurrent();
            App.toast('پیش‌نمایش «' + palName(selections[panel]) + '» اعمال شد — برای ماندگاری ذخیره کنید.', 'info');
        });
    });

    /* اسلایدر دما → پیش‌نمایش زندهٔ پالت + دما */
    q('#ap-palette-warmth')?.addEventListener('input', () => {
        const slider = q('#ap-palette-warmth');
        if (!slider) return;
        warmths[panel] = Number(slider.value) || 0;
        syncTempLabelFor(warmths[panel], q('[data-ap-warmth-label]'));
        syncStatus();
        if ((selections[panel] || 'default') !== 'custom') {
            previewCss({ panel, palette: selections[panel] || 'default', warmth: String(warmths[panel]) });
        }
    });

    q('[data-ap-warmth-reset]')?.addEventListener('click', () => {
        warmths[panel] = 0;
        const slider = q('#ap-palette-warmth');
        if (slider) slider.value = '0';
        syncTempLabelFor(0, q('[data-ap-warmth-label]'));
        syncStatus();
        if ((selections[panel] || 'default') !== 'custom') {
            previewCss({ panel, palette: selections[panel] || 'default' });
        }
    });

    /* رنگ پایه + دما → بازتولید طیف */
    ['input', 'change'].forEach(evt => {
        q('#ap-base')?.addEventListener(evt, () => {
            const baseHex = q('#ap-base-hex');
            if (baseHex) baseHex.value = q('#ap-base').value;
            regenerateFromBase();
            scheduleCustomPreview();
        });
        q('#ap-base-hex')?.addEventListener('change', () => {
            const inp = q('#ap-base-hex'), base = q('#ap-base');
            if (!inp || !base) return;
            if (/^#[0-9a-fA-F]{6}$/.test(inp.value)) { base.value = inp.value; regenerateFromBase(); scheduleCustomPreview(); }
        });
        q('#ap-temp')?.addEventListener('input', () => {
            syncTempLabel();
            regenerateFromBase();
            scheduleCustomPreview();
        });
    });

    /* هر تغییر دیگر در ویرایشگر → پیش‌نمایش */
    ['data-ap-shade', 'data-ap-sem', 'data-ap-sb'].forEach(attr => {
        qa('[' + attr + ']').forEach(input => {
            input.addEventListener('input', scheduleCustomPreview);
        });
    });
    q('#ap-pagebg')?.addEventListener('input', () => {
        const pbHex = q('#ap-pagebg-hex');
        if (pbHex) pbHex.value = q('#ap-pagebg').value;
        scheduleCustomPreview();
    });
    q('#ap-pagebg-hex')?.addEventListener('change', () => {
        const inp = q('#ap-pagebg-hex'), pb = q('#ap-pagebg');
        if (inp && pb && /^#[0-9a-fA-F]{6}$/.test(inp.value)) { pb.value = inp.value; scheduleCustomPreview(); }
    });
    q('[data-ap-sbtext]')?.addEventListener('input', scheduleCustomPreview);

    function scheduleCustomPreview() {
        if ((selections[panel] || 'default') !== 'custom') return;
        customs[panel] = readEditor();
        previewCss({ panel, custom: JSON.stringify(customs[panel]) });
    }

    /* شروع از پالت فعلی */
    q('[data-ap-from-current]')?.addEventListener('click', () => {
        const seed = tokensOfPalette(initial[panel] || 'default');
        if (!seed) return;
        customs[panel] = seed;
        selections[panel] = 'custom';
        syncPaletteGrid();
        customBox().classList.remove('hidden');
        fillEditor(seed);
        previewCss({ panel, custom: JSON.stringify(customs[panel]) });
        syncStatus();
        App.toast('توکن‌های «' + palName(initial[panel]) + '» بارگذاری شد — حالا رنگ‌ها را تغییر دهید.', 'info');
    });

    /* ذخیره */
    q('[data-ap-save]')?.addEventListener('click', async () => {
        const btn = q('[data-ap-save]');
        const body = { panel, palette: selections[panel] || 'default' };
        if (body.palette === 'custom') {
            body.custom = customs[panel] || readEditor();
        } else {
            body.warmth = Number(warmths[panel] || 0);
        }

        btn.disabled = true;
        try {
            const res = await App.ajax('/admin/settings/appearance', { method: 'PUT', body });
            if (res.ok) {
                const out = await res.json().catch(() => ({}));
                App.toast(out.message || 'پوسته ذخیره شد.', 'success');
                initial[panel] = body.palette;
                initialWarmths[panel] = Number(warmths[panel] || 0);
                syncStatus();
            } else {
                const out = await res.json().catch(() => ({}));
                App.toast(out.message || 'ذخیرهٔ پوسته ناموفق بود.', 'error');
            }
        } catch {
            App.toast('ارتباط با سرور برقرار نشد.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* بازگشت به ذخیره‌شده */
    q('[data-ap-revert]')?.addEventListener('click', () => {
        selections[panel] = initial[panel] || 'default';
        customs[panel] = data.customs[panel] ? JSON.parse(JSON.stringify(data.customs[panel])) : null;
        warmths[panel] = Number(initialWarmths[panel] || 0);
        syncPaletteGrid();
        syncStatus();
        customBox().classList.toggle('hidden', selections[panel] !== 'custom');
        syncWarmthBox();
        if (selections[panel] === 'custom') fillEditor(customs[panel]);
        previewCss({ panel }); // CSS ذخیره‌شدهٔ پنل
        App.toast('به پالت ذخیره‌شده بازگشت.', 'info');
    });

    /* وضعیت اولیه */
    syncPanelChips();
    syncPaletteGrid();
    syncStatus();
    syncWarmthBox();
})();
