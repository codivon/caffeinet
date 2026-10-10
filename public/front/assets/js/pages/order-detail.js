/* اپ مشتری — جزئیات سفارش + پرداخت */
/* فاز ۳۲ — برازش ارتفاع پوستهٔ گفتگو با viewport واقعی (حالت نصب PWA) */
/* [Task 9] Vanilla JS — بدون jQuery */
/* global CN */
(function () {
    'use strict';

    if (!CN.requireCompleteProfile()) { return; }

    /* ---------- v32 — برازش ارتفاع چت تمام‌صفحه ----------
       در برخی گوشی‌ها در «حالت نصب‌شده» (PWA standalone) مقدار 100dvh بزرگ‌تر
       از پنجرهٔ واقعی گزارش می‌شود → نوار ارسال زیر صفحه می‌رود و body اسکرول
       می‌گیرد. ارتفاع را با innerHeight/visualViewport (سازگار با کیبورد مجازی)
       دقیق تنظیم می‌کنیم؛ 100dvh فقط fallback بدون-JS می‌ماند. */
    (function fitChatShell() {
        var shell = document.querySelector('.app-shell.chat-shell');
        if (!shell) { return; }

        var lastH = 0;

        function fit() {
            var h = window.innerHeight;
            var vv = window.visualViewport;

            // کیبورد مجازی: visualViewport کوچک‌تر می‌شود → نوار ارسال بالای کیبورد
            // (زمان زومِ scale≠1 مداخله نمی‌کنیم تا رفتار پینچ‌زوم طبیعی بماند)
            if (vv && Math.abs(vv.scale - 1) < 0.02) {
                h = Math.min(h, Math.round(vv.height));
            }

            if (h > 0 && h !== lastH) {
                lastH = h;
                shell.style.height = h + 'px';
                shell.style.minHeight = h + 'px';
            }
        }

        fit();
        window.addEventListener('resize', fit);
        window.addEventListener('orientationchange', function () { setTimeout(fit, 250); });
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', fit);
        }
    })();

    var orderId = Number(window.location.pathname.split('/').pop()) || 0;
    var order = null;

    var BROADCAST_TOTAL = 60;      // مهلت پخش (از سرور تنظیم می‌شود)
    var RING_C = 2 * Math.PI * 40; // محیط حلقه (r=40, viewBox 96)
    var pollTimer = null;
    var tickTimer = null;
    var lastStatus = null;
    var chatCardVisible = false;   // فاز ۱۲ — کارت گفتگو در دسترس است؟ (رویداد chat:visibility)

    /* پیام‌های بازگشت از درگاه */
    var query = {};
    try { query = Object.fromEntries(new URLSearchParams(window.location.search)); } catch (e) { /* noop */ }
    if (query.paid === '1') {
        CN.toast('پرداخت با موفقیت انجام شد؛ اپراتور کار شما را آغاز می‌کند.', 'success', 5200);
    } else if (query.payment === 'failed') {
        CN.toast('پرداخت ناموفق بود یا لغو شد؛ می‌توانید دوباره تلاش کنید.', 'error', 5200);
    }

    function load() {
        var loaderEl = document.getElementById('orderLoader');
        if (loaderEl) { loaderEl.classList.remove('hidden'); }

        CN.api('/orders/' + orderId, {
            success: function (resp) {
                if (loaderEl) { loaderEl.classList.add('hidden'); }
                order = resp.data;
                render();
            },
            error: function (xhr, message) {
                if (loaderEl) { loaderEl.classList.add('hidden'); }
                var orderNumberEl = document.getElementById('orderNumber');
                if (orderNumberEl) { orderNumberEl.textContent = '—'; }
                var orderServiceEl = document.getElementById('orderService');
                if (orderServiceEl) { orderServiceEl.textContent = message; }
            }
        });
    }

    /* بارگذاری بی‌صدا (polling وضعیت پخش/صف) */
    function loadSilent() {
        if (document.hidden) { return; } // تب مخفی — بدون درخلود بی‌مورد

        CN.api('/orders/' + orderId, {
            success: function (resp) {
                var next = resp.data;
                /* v42 — مقایسهٔ کامل: status/پرداخت (is_paid/paid_at/payment_status)
                   /اپراتور/مبلغ — فاکتور پرداخت بعد از پرداختِ موفق با رویداد
                   realtime همان لحظه تازه می‌شود و تا رفرش سرِ جایش نمی‌ماند */
                var changed = !order
                    || next.status !== order.status
                    || (!!next.is_paid) !== (!!order.is_paid)
                    || String(next.paid_at || '') !== String(order.paid_at || '')
                    || String(next.payment_status || '') !== String(order.payment_status || '')
                    || next.operator_id !== order.operator_id
                    || Number(next.total_amount) !== Number(order.total_amount);
                if (changed) {
                    order = next;
                    render();
                    return;
                }
                // هم‌گام‌سازی ثانیهٔ شمارش معکوس
                if (next.status === 'broadcasting') {
                    var s = parseInt(next.broadcast_seconds_left, 10) || 0;
                    var bcCardSync = document.getElementById('broadcastCard');
                    if (bcCardSync) { bcCardSync.dataset.seconds = String(s); }
                }
            }
        });
    }

    /* v38 «پوشر کامل»: وقتی پوشر متصل است interval وضعیت خاموش می‌شود —
       رویداد order.changed (کانال شخصی کاربر) و notif.new صفحه را لحظه‌ای
       تازه می‌کنند. قطع اتصال → interval خودکار برمی‌گردد.
       tick شمارش معکوس محلی است و در هر حالت کار می‌کند. */
    function startPolling() {
        stopPolling();
        if (!rtLive()) {
            pollTimer = window.setInterval(loadSilent, 4000);
        }
        if (!tickTimer) {
            tickTimer = window.setInterval(tickBroadcast, 1000);
        }
    }

    function rtLive() {
        return !!(window.RT && RT.active() && RT.connected() && RT.cfg.channel);
    }

    function stopPolling() {
        if (pollTimer) { window.clearInterval(pollTimer); pollTimer = null; }
    }

    function stopTicking() {
        if (tickTimer) { window.clearInterval(tickTimer); tickTimer = null; }
    }

    /* شمارش معکوس محلی هر ثانیه */
    function tickBroadcast() {
        tickSla();
        var card = document.getElementById('broadcastCard');
        if (card && card.classList.contains('hidden')) { return; }

        /* v39 — ثانیه‌شمار به تصمیم مدیر خاموش است → فقط poll وضعیت کافی است */
        var bcTimerHidden = document.getElementById('broadcastTimer');
        if (bcTimerHidden && bcTimerHidden.classList.contains('hidden')) { return; }

        var s = parseInt(String((card && card.dataset.seconds) || '0'), 10);
        if (s > 0) {
            s -= 1;
            if (card) { card.dataset.seconds = String(s); }
        }

        var numEl = document.getElementById('broadcastSeconds');
        var ring = document.getElementById('broadcastRing');
        var timer = document.getElementById('broadcastTimer');

        if (numEl) { numEl.textContent = CN.toFaDigits(s); }
        if (ring) {
            var total = Math.max(15, BROADCAST_TOTAL);
            var ratio = Math.min(1, s / total);
            ring.setAttribute('stroke-dasharray', String(RING_C));
            ring.setAttribute('stroke-dashoffset', String(RING_C * (1 - ratio)));
        }
        if (timer) { timer.classList.toggle('danger', s <= 10); }

        if (s <= 0) {
            window.setTimeout(loadSilent, 1200); // تعیین‌تکلیف تنبل سرور
        }
    }

    function render() {
        var o = order;

        /* سربرگ */
        var orderNumberEl = document.getElementById('orderNumber');
        if (orderNumberEl) { orderNumberEl.textContent = o.order_number; }

        var badgeEl = document.getElementById('orderStatusBadge');
        if (badgeEl) {
            var tmpBadge = document.createElement('div');
            tmpBadge.innerHTML = CN.statusBadge(o.status, o.status_label).replace('<span class="badge', '<span id="orderStatusBadge" class="badge');
            var newBadge = tmpBadge.firstElementChild;
            if (newBadge && badgeEl.parentNode) { badgeEl.parentNode.replaceChild(newBadge, badgeEl); }
        }

        var orderIconEl = document.getElementById('orderIcon');
        if (orderIconEl) { orderIconEl.textContent = o.service ? o.service.icon || '📄' : '📄'; }
        var orderServiceEl = document.getElementById('orderService');
        if (orderServiceEl) { orderServiceEl.textContent = o.service ? o.service.name : '—'; }
        var orderDateEl = document.getElementById('orderDate');
        if (orderDateEl) { orderDateEl.textContent = o.created_at_fa || ''; }

        /* کارت پرداخت — فاز ۱۲: accepted = فاکتور داخل چت؛ legacy pending_payment = کارت جدا */
        renderPayment();

        /* کارت لغو — تا قبل از پرداخت (v31: بین «وضعیت‌های پیش از اتصال» و شیت اطلاعات جابه‌جا می‌شود) */
        placeCancel(o);

        /* ---------- فاز ۶ — کارت‌های تخصیص ---------- */
        renderAssignment(o);

        /* ---------- فاز ۵۲ — تایمر تعهد زمان تحویل (SLA) ---------- */
        renderSla(o);

        /* ---------- نظرسنجی پس از اتمام ---------- */
        renderSurvey(o);

        /* اعلان تغییر وضعیت (مثلاً پذیرش در حین تماشا) */
        if (lastStatus && lastStatus !== o.status) {
            if (o.status === 'accepted' && o.coffeenet) {
                CN.toast('هورا! ' + (o.operator ? 'اپراتور «' + o.operator.name + '» از ' : '') + 'کافی‌نت «' + o.coffeenet.name + '» به درخواست شما متصل شد؛ حالا پرداخت را انجام دهید.', 'success', 7000);
            } else if (o.status === 'queued') {
                CN.toast('مهلت پخش پایان یافت؛ درخواست به صف بررسی کارشناسان منتقل شد.', 'info', 6000);
            } else if (o.status === 'paid') {
                CN.toast('پرداخت ثبت شد؛ اپراتور کار شما را آغاز می‌کند.', 'success');
            } else if (o.status === 'in_progress') {
                CN.toast('کار روی درخواست شما آغاز شد.', 'success');
            }
        }
        lastStatus = o.status;

        /* زمان‌بندی */
        var timeline = '';
        var history = o.status_history || [];
        var currentShown = false;
        history.forEach(function (h, i) {
            var cls = i === 0 ? (currentShown ? '' : 'current') : 'done';
            if (i === 0) { currentShown = true; cls = 'current'; }
            timeline += '<div class="tl-item ' + cls + '">' +
                '<div class="tl-title">' + CN.esc(h.to_status_label || h.to_status) + '</div>' +
                (h.created_at_fa ? '<div class="tl-time">' + CN.esc(h.created_at_fa) + '</div>' : '') +
                (h.note ? '<div class="tl-note">' + CN.esc(h.note) + '</div>' : '') +
                '</div>';
        });
        if (!timeline.length) {
            timeline = '<div class="tl-item current"><div class="tl-title">' + CN.esc(o.status_label) + '</div></div>';
        }
        var timelineEl = document.getElementById('timeline');
        if (timelineEl) { timelineEl.innerHTML = timeline; }

        /* خلاصه هزینه */
        var rows = '<div class="price-row"><span class="pr-title">💰 کارمزد خدمت</span><span class="pr-amount">' + CN.faMoney(o.price) + ' تومان</span></div>';
        if (o.expenses > 0) {
            rows += '<div class="price-row"><span class="pr-title">📦 هزینه‌های جانبی</span><span class="pr-amount">' + CN.faMoney(o.expenses) + ' تومان</span></div>';
        }
        rows += '<div class="price-row total"><span class="pr-title">مبلغ کل</span><span class="pr-amount">' + CN.faMoney(o.total_amount) + ' تومان</span></div>';
        var costRowsEl = document.getElementById('orderCostRows');
        if (costRowsEl) { costRowsEl.innerHTML = rows; }

        /* داده‌های فرم */
        var fdHtml = '';
        (o.form_data_display || []).forEach(function (row) {
            fdHtml += '<div class="data-row"><span class="data-key">' + CN.esc(row.label) + '</span><span class="data-val">' + CN.esc(row.value) + '</span></div>';
        });
        var formDataEl = document.getElementById('orderFormData');
        if (formDataEl) { formDataEl.innerHTML = fdHtml || '<p class="text-faint tiny">فرمی ثبت نشده است.</p>'; }

        /* دلیل لغو */
        var cancelReasonBoxEl = document.getElementById('cancelReasonBox');
        if (cancelReasonBoxEl) { cancelReasonBoxEl.classList.toggle('hidden', !o.cancel_reason); }
        var cancelReasonTextEl = document.getElementById('cancelReasonText');
        if (cancelReasonTextEl) { cancelReasonTextEl.textContent = o.cancel_reason || ''; }

        /* مدارک */
        var files = o.files || [];
        var filesCardEl = document.getElementById('filesCard');
        if (filesCardEl) { filesCardEl.classList.toggle('hidden', !files.length); }
        var fHtml = '';
        files.forEach(function (f) {
            fHtml += '<a class="btn btn-outline btn-sm btn-block" href="' + CN.esc(f.url) + '" target="_blank" rel="noopener">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>' +
                CN.esc(f.original_name) + ' <span class="tiny text-faint">(' + CN.toFaDigits(f.size_kb) + 'KB)</span></a>';
        });
        var filesListEl = document.getElementById('filesList');
        if (filesListEl) { filesListEl.innerHTML = fHtml; }

        /* پرداخت‌ها */
        var payments = o.payments || [];
        var paymentsCardEl = document.getElementById('paymentsCard');
        if (paymentsCardEl) { paymentsCardEl.classList.toggle('hidden', !payments.length); }
        var pHtml = '';
        payments.forEach(function (p) {
            pHtml += '<div class="data-row"><span class="data-key">' + CN.esc(p.driver_label) + (p.ref_id ? ' — ' + CN.esc(p.ref_id) : '') + '</span>' +
                '<span class="data-val">' + CN.faMoney(p.amount) + ' تومان · ' + CN.esc(p.status_label) + (p.paid_at_fa ? ' · ' + CN.esc(p.paid_at_fa) : '') + '</span></div>';
        });
        var paymentsListEl = document.getElementById('paymentsList');
        if (paymentsListEl) { paymentsListEl.innerHTML = pHtml; }
    }

    /* ---------- نظرسنجی سفارش (پس از تحویل/تکمیل) — v33 کامل ---------- */

    var surveyValue = 0;
    var surveyOpValue = 0;
    var surveySubmitting = false;
    var surveyOptionsCache = null; // گزینه‌های دلایل (از API)
    var surveySelected = {}; // id → true
    var RATING_HINTS = {
        1: 'خیلی ضعیف بود 😞',
        2: 'ضعیف بود 🙁',
        3: 'متوسط بود 🙂',
        4: 'خوب بود 😊',
        5: 'عالی بود! 🤩'
    };

    /* [Task 9] $.Deferred → Promise بومی — همان نیم‌راخت surveyOptionsCache */
    function ratingOptions() {
        if (surveyOptionsCache !== null) {
            return Promise.resolve(surveyOptionsCache);
        }
        return new Promise(function (resolve) {
            CN.api('/rating-options', {
                success: function (resp) {
                    surveyOptionsCache = (resp && resp.data) || [];
                    resolve(surveyOptionsCache);
                },
                error: function () {
                    surveyOptionsCache = [];
                    resolve([]);
                }
            });
        });
    }

    function renderSurvey(o) {
        var done = ['delivered', 'completed'].indexOf(o.status) !== -1;
        var rated = !!(o.rating && o.rating.rating);

        var surveyCardEl = document.getElementById('surveyCard');
        if (surveyCardEl) { surveyCardEl.classList.toggle('hidden', !done); }
        if (!done) { return; }

        var hasOperator = !!(o.operator && o.operator.id);
        var surveyOpBoxEl = document.getElementById('surveyOpBox');
        if (surveyOpBoxEl) { surveyOpBoxEl.classList.toggle('hidden', !hasOperator); }
        if (!hasOperator) { surveyOpValue = 0; }

        var surveyFormBoxEl = document.getElementById('surveyFormBox');
        var surveyDoneBoxEl = document.getElementById('surveyDoneBox');

        if (rated) {
            if (surveyFormBoxEl) { surveyFormBoxEl.classList.add('hidden'); }
            if (surveyDoneBoxEl) { surveyDoneBoxEl.classList.remove('hidden'); }

            var stars = '';
            for (var i = 1; i <= 5; i++) {
                stars += '<svg class="s-done' + (i <= o.rating.rating ? '' : ' s-off') + '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>';
            }
            var doneStarsEl = document.getElementById('surveyDoneStars');
            if (doneStarsEl) { doneStarsEl.innerHTML = stars; }

            // امتیاز اپراتور
            var doneOpStarsEl = document.getElementById('surveyDoneOpStars');
            if (o.rating.operator_rating) {
                var opStars = '';
                for (var j = 1; j <= 5; j++) {
                    opStars += '<svg class="s-done' + (j <= o.rating.operator_rating ? '' : ' s-off') + '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>';
                }
                if (doneOpStarsEl) {
                    doneOpStarsEl.innerHTML = '<span class="s-done-label">اپراتور</span>' + opStars;
                    doneOpStarsEl.classList.remove('hidden');
                }
            } else {
                if (doneOpStarsEl) {
                    doneOpStarsEl.classList.add('hidden');
                    doneOpStarsEl.innerHTML = '';
                }
            }

            // دلایل انتخابی (اسنپ‌شات)
            var opts = o.rating.options || [];
            var doneOptionsEl = document.getElementById('surveyDoneOptions');
            if (doneOptionsEl) {
                doneOptionsEl.innerHTML = opts.length
                    ? opts.map(function (op) {
                        return '<span class="s-done-chip' + (op.type === 'neg' ? ' s-done-chip--neg' : '') + '">' + escapeHtmlFa(op.title) + '</span>';
                    }).join('')
                    : '';
            }

            var doneCommentEl = document.getElementById('surveyDoneComment');
            if (doneCommentEl) { doneCommentEl.textContent = o.rating.comment ? '«' + o.rating.comment + '»' : (o.rating.rated_at_fa ? 'ثبت‌شده در ' + o.rating.rated_at_fa : ''); }
        } else {
            if (surveyFormBoxEl) { surveyFormBoxEl.classList.remove('hidden'); }
            if (surveyDoneBoxEl) { surveyDoneBoxEl.classList.add('hidden'); }
            var surveyIntroEl = document.getElementById('surveyIntro');
            if (surveyIntroEl) {
                surveyIntroEl.textContent = o.status === 'delivered'
                    ? 'سفارش شما تحویل شد! از تجربه‌تان چه امتیازی می‌دهید؟'
                    : 'سفارش شما تکمیل شد! از تجربه‌تان چه امتیازی می‌دهید؟';
            }

            // گزینه‌های دلایل را از قبل بارگذاری کن تا با اولین تیک آماده باشد
            ratingOptions();
        }
    }

    function setSurveyStars(value) {
        surveyValue = value;
        Array.prototype.forEach.call(document.querySelectorAll('#surveyStars .s-star'), function (star) {
            var v = parseInt(star.dataset.value, 10) || 0;
            var on = v <= value;
            star.classList.toggle('on', on);
            star.setAttribute('aria-checked', on && v === value ? 'true' : 'false');
        });
        var hint = document.getElementById('surveyRatingHint');
        if (value > 0) {
            if (hint) { hint.textContent = RATING_HINTS[value] || ''; hint.classList.add('hint-on'); }
        } else {
            if (hint) { hint.textContent = 'امتیاز خود را انتخاب کنید'; hint.classList.remove('hint-on'); }
        }
        updateSurveyOptions();
        updateSurveySubmit();
    }

    function setSurveyOpStars(value) {
        surveyOpValue = value;
        Array.prototype.forEach.call(document.querySelectorAll('#surveyOpStars .s-star'), function (star) {
            var v = parseInt(star.dataset.value, 10) || 0;
            var on = v <= value;
            star.classList.toggle('on', on);
            star.setAttribute('aria-checked', on && v === value ? 'true' : 'false');
        });
        updateSurveySubmit();
    }

    /* دلایل متناسب با امتیاز: ۴/۵ → نقاط قوت، ۱/۲ → نقاط ضعف، ۳ → هر دو */
    function surveyWantedTypes() {
        if (surveyValue >= 4) { return ['pos']; }
        if (surveyValue > 0 && surveyValue <= 2) { return ['neg']; }
        if (surveyValue === 3) { return ['pos', 'neg']; }
        return [];
    }

    function updateSurveyOptions() {
        var box = document.getElementById('surveyOptionsBox');
        var types = surveyWantedTypes();

        if (!types.length) {
            if (box) { box.classList.add('hidden'); }
            surveySelected = {};
            renderSurveyOptionsList([]);
            return;
        }

        ratingOptions().then(function (options) {
            var filtered = (options || []).filter(function (o) {
                return types.indexOf(o.type) !== -1;
            });

            // انتخاب‌های خارج از نوع (مثلاً بعد از تغییر ستاره) پاک شود
            var keep = {};
            filtered.forEach(function (o) { if (surveySelected[o.id]) { keep[o.id] = true; } });
            surveySelected = keep;

            if (box) { box.classList.remove('hidden'); }
            var titleEl = document.getElementById('surveyOptionsTitle');
            if (titleEl) {
                titleEl.textContent =
                    surveyValue >= 4 ? 'چه چیزهایی خوب بود؟ (اختیاری)'
                        : (surveyValue <= 2 ? 'چه چیزهایی ضعیف بود؟ (اختیاری)'
                            : 'چه چیزهایی را بیشتر دوست داشتید یا نبود؟ (اختیاری)');
            }
            renderSurveyOptionsList(filtered);
        });
    }

    function renderSurveyOptionsList(options) {
        var box = document.getElementById('surveyOptions');
        if (!box) { return; }
        if (!options.length) {
            box.innerHTML = '<p class="tiny text-faint text-center" style="padding:6px 0">گزینه‌ای برای این امتیاز ثبت نشده است.</p>';
            return;
        }
        box.innerHTML = options.map(function (o) {
            var checked = !!surveySelected[o.id];
            return '<button type="button" class="s-opt' + (checked ? ' on' : '') + (o.type === 'neg' ? ' s-opt--neg' : '') + '" data-id="' + o.id + '" role="checkbox" aria-checked="' + (checked ? 'true' : 'false') + '">' +
                '<span class="s-opt-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>' +
                '<span class="s-opt-title">' + escapeHtmlFa(o.title) + '</span>' +
                '</button>';
        }).join('');
    }

    var surveyOptionsEl = document.getElementById('surveyOptions');
    if (surveyOptionsEl) {
        surveyOptionsEl.addEventListener('click', function (e) {
            var opt = e.target.closest('.s-opt');
            if (!opt) { return; }
            var id = parseInt(opt.dataset.id, 10) || 0;
            if (!id) { return; }
            if (surveySelected[id]) {
                delete surveySelected[id];
            } else {
                surveySelected[id] = true;
            }
            opt.classList.toggle('on', !!surveySelected[id]);
            opt.setAttribute('aria-checked', surveySelected[id] ? 'true' : 'false');
        });
    }

    function updateSurveySubmit() {
        var submitBtn = document.getElementById('surveySubmitBtn');
        if (submitBtn) { submitBtn.disabled = surveyValue === 0; }
    }

    var surveyStarsEl = document.getElementById('surveyStars');
    if (surveyStarsEl) {
        surveyStarsEl.addEventListener('click', function (e) {
            var star = e.target.closest('.s-star');
            if (!star) { return; }
            setSurveyStars(parseInt(star.dataset.value, 10) || 0);
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('#surveyStars .s-star'), function (star) {
        star.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                setSurveyStars(parseInt(star.dataset.value, 10) || 0);
            }
        });
    });

    var surveyOpStarsEl = document.getElementById('surveyOpStars');
    if (surveyOpStarsEl) {
        surveyOpStarsEl.addEventListener('click', function (e) {
            var star = e.target.closest('.s-star');
            if (!star) { return; }
            // کلیک دوباره روی همان ستاره = حذف امتیاز اپراتور (اختیاری)
            var v = parseInt(star.dataset.value, 10) || 0;
            setSurveyOpStars(surveyOpValue === v ? 0 : v);
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('#surveyOpStars .s-star'), function (star) {
        star.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                var v = parseInt(star.dataset.value, 10) || 0;
                setSurveyOpStars(surveyOpValue === v ? 0 : v);
            }
        });
    });

    var surveySubmitBtnEl = document.getElementById('surveySubmitBtn');
    if (surveySubmitBtnEl) {
        surveySubmitBtnEl.addEventListener('click', function () {
            if (!surveyValue || surveySubmitting) { return; }

            surveySubmitting = true;
            var btn = surveySubmitBtnEl;
            btn.disabled = true;
            btn.textContent = 'در حال ثبت…';
            var surveyErrorEl = document.getElementById('surveyError');
            if (surveyErrorEl) { surveyErrorEl.textContent = ''; }

            var selectedIds = Object.keys(surveySelected).map(function (k) { return parseInt(k, 10); });
            var commentEl = document.getElementById('surveyComment');
            var payload = {
                rating: surveyValue,
                comment: ((commentEl && commentEl.value) || '').trim() || null
            };
            if (surveyOpValue > 0) { payload.operator_rating = surveyOpValue; }
            if (selectedIds.length) { payload.options = selectedIds; }

            CN.api('/orders/' + orderId + '/rating', {
                method: 'POST',
                data: payload,
                success: function (resp) {
                    surveySubmitting = false;
                    CN.toast(resp.message || 'از بازخورد شما سپاسگزاریم.', 'success');
                    order = resp.data || order;
                    render();
                },
                error: function (xhr, message) {
                    surveySubmitting = false;
                    btn.disabled = false;
                    btn.textContent = 'ثبت نظرسنجی';
                    if (surveyErrorEl) { surveyErrorEl.textContent = message || 'ثبت نظرسنجی ناموفق بود.'; }
                }
            });
        });
    }

    function escapeHtmlFa(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /* ---------- فاز ۶/۱۱/۱۲+: کارت‌های ارسال/صف — اتصال داخل چت نمایش داده می‌شود ---------- */

    /* v41 — راه‌های ارتباطی مشتری (نمایش پس از پایان مهلت پخش بدون پذیرش)
       مدل نهایی (درخواست مالک): «همه با هم ترکیب بشن و هر کدوم که خواست تیک بزنه» —
       یک لیست واحد چندانتخابی؛ «تماس تلفنی» گزینهٔ اول لیست است و «فرقی ندارد» حذف شده.
       ثبت نهایی با دکمهٔ «ثبت انتخاب من» → POST { preferences: [...] }. */
    var CONTACT_PREFS = [
        { value: 'call', label: 'تماس تلفنی', icon: '📞', desc: 'کارشناس ما مستقیماً با شما تماس می‌گیرد' },
        { value: 'app_chat', label: 'چت داخل برنامه', icon: '💬', desc: 'گفتگو در همین برنامه' },
        { value: 'telegram', label: 'تلگرام', icon: '✈️', desc: 'پیام از طریق تلگرام' },
        { value: 'whatsapp', label: 'واتس‌اپ', icon: '🟢', desc: 'پیام از طریق واتس‌اپ' },
        { value: 'bale', label: 'بله', icon: '🔵', desc: 'پیام از طریق بله' },
        { value: 'eitaa', label: 'ایتا', icon: '📨', desc: 'پیام از طریق ایتا' }
    ];

    var savedPreference = null; // مقدار ثبت‌شدهٔ کاربر (رشتهٔ ترکیبی مثل «call,telegram»)

    /** تجزیهٔ «call,telegram» → آرایهٔ توکن‌های انتخابی (به ترتیب ذخیره) */
    function parsePreference(raw) {
        var out = [];
        String(raw || '').split(',').forEach(function (token) {
            token = token.trim();
            if (!token) { return; }
            for (var i = 0; i < CONTACT_PREFS.length; i++) {
                if (CONTACT_PREFS[i].value === token) { out.push(token); return; }
            }
            /* دادهٔ قدیمی «any» به‌عنوان انتخاب ذخیره‌شده معتبر است (نمایش می‌ماند) */
            if (token === 'any') { out.push(token); }
        });
        return out;
    }

    function prefMeta(value) {
        for (var i = 0; i < CONTACT_PREFS.length; i++) {
            if (CONTACT_PREFS[i].value === value) { return CONTACT_PREFS[i]; }
        }
        /* «any» دادهٔ قدیمی — بدون متا در لیست جدید */
        if (value === 'any') { return { value: 'any', label: 'فرقی ندارد', icon: '🤝', desc: 'هر راهی که راحت‌تر است' }; }
        return null;
    }

    /** انتخاب فعلی کاربر از گرید (توکن‌های تیک‌خورده) */
    function cprefSelection() {
        var items = document.querySelectorAll('#contactPrefGrid .cpref-item.active');
        var out = [];
        Array.prototype.forEach.call(items, function (it) {
            if (it.dataset.pref) { out.push(it.dataset.pref); }
        });
        return out;
    }

    function sameSelection(a, b) {
        if (a.length !== b.length) { return false; }
        var sortedA = a.slice().sort();
        var sortedB = b.slice().sort();
        for (var i = 0; i < sortedA.length; i++) {
            if (sortedA[i] !== sortedB[i]) { return false; }
        }
        return true;
    }

    function updateCprefSaveBtn() {
        var sel = cprefSelection();
        var changed = !savedPreference || !sameSelection(parsePreference(savedPreference), sel);
        var saveBtnEl = document.getElementById('contactPrefSave');
        if (saveBtnEl) { saveBtnEl.disabled = !sel.length || !changed; }
        var cprefErrorEl = document.getElementById('contactPrefError');
        if (cprefErrorEl) {
            cprefErrorEl.classList.remove('show');
            cprefErrorEl.textContent = '';
        }
    }

    function renderContactPrefs(selected) {
        var box = document.getElementById('contactPrefBox');
        var grid = document.getElementById('contactPrefGrid');
        var savedEl = document.getElementById('contactPrefSaved');

        if (selected) { savedPreference = selected; }

        var selectedTokens = parsePreference(savedPreference);
        if (!savedPreference) {
            /* بدون انتخاب ثبت‌شده → تماس تلفنی به‌صورت پیش‌فرض تیک‌خورده است
               (کاربر هر ترکیبی بخواهد می‌تواند عوض کند) */
            selectedTokens = ['call'];
        }

        var html = '';
        CONTACT_PREFS.forEach(function (p) {
            var active = selectedTokens.indexOf(p.value) !== -1;
            html += '<button type="button" class="cpref-item' + (active ? ' active' : '') + '" data-pref="' + p.value + '"' +
                ' role="checkbox" aria-checked="' + (active ? 'true' : 'false') + '" title="' + CN.esc(p.desc) + '">' +
                '<span class="cpref-item-ico" aria-hidden="true">' + p.icon + '</span>' +
                '<span class="cpref-item-label">' + p.label + '</span>' +
                (active ? '<span class="cpref-check" aria-hidden="true">✓</span>' : '') +
                '</button>';
        });
        if (grid) { grid.innerHTML = html; }

        if (savedPreference) {
            var parts = [];
            selectedTokens.forEach(function (token) {
                var meta = prefMeta(token);
                parts.push(meta ? (meta.icon + ' ' + meta.label) : token);
            });
            if (savedEl) {
                savedEl.classList.remove('hidden');
                savedEl.removeAttribute('hidden'); /* فاز ۱۴ — اتریبیوت hidden بلیید هم باید برداشته شود */
                savedEl.textContent = '✓ راه‌های ارتباطی شما: ' + parts.join(' + ') + ' — کارشناسان ما از همین راه‌ها با شما در تماس می‌شوند.';
            }
        } else {
            if (savedEl) {
                savedEl.classList.add('hidden');
                savedEl.setAttribute('hidden', '');
                savedEl.textContent = '';
            }
        }

        if (box) { box.removeAttribute('hidden'); }
        updateCprefSaveBtn();
    }

    /* تیک/برداشتن هر گزینه — لیست واحد چندانتخابی (v41) — دله‌گیشن روی گرید ثابت صفحه */
    var cprefGridEl = document.getElementById('contactPrefGrid');
    if (cprefGridEl) {
        cprefGridEl.addEventListener('click', function (e) {
            var item = e.target.closest('.cpref-item');
            if (!item) { return; }
            var nowActive = !item.classList.contains('active');
            item.classList.toggle('active', nowActive);
            item.setAttribute('aria-checked', nowActive ? 'true' : 'false');
            var check = item.querySelector('.cpref-check');
            if (check) { check.remove(); }
            if (nowActive) {
                item.insertAdjacentHTML('beforeend', '<span class="cpref-check" aria-hidden="true">✓</span>');
            }
            updateCprefSaveBtn();
        });
    }

    /* ثبت انتخاب */
    var cprefSaveEl = document.getElementById('contactPrefSave');
    if (cprefSaveEl) {
        cprefSaveEl.addEventListener('click', function () {
            var sel = cprefSelection();

            if (!sel.length) {
                var cprefErrorEl = document.getElementById('contactPrefError');
                if (cprefErrorEl) {
                    cprefErrorEl.classList.add('show');
                    cprefErrorEl.textContent = 'حداقل یک راه ارتباطی را انتخاب کنید.';
                }
                return;
            }

            var btn = cprefSaveEl;
            btn.disabled = true;
            btn.textContent = 'در حال ثبت…';

            CN.api('/orders/' + orderId + '/contact-preference', {
                method: 'POST',
                data: { preferences: sel },
                success: function (resp) {
                    btn.disabled = false;
                    btn.textContent = 'ثبت انتخاب من';
                    savedPreference = resp.preference || null;
                    renderContactPrefs(savedPreference);
                    CN.toast(resp.message || 'انتخاب شما ثبت شد.', 'success');
                },
                error: function (xhr, message) {
                    btn.disabled = false;
                    btn.textContent = 'ثبت انتخاب من';
                    /* [Task 9] xhr دیگر responseJSON ندارد → پارس دستی */
                    var body = null;
                    try { body = JSON.parse(xhr.responseText); } catch (parseErr) { body = null; }
                    var errors = (body && body.errors) || {};
                    if (errors.preferences && errors.preferences.length) {
                        var errEl = document.getElementById('contactPrefError');
                        if (errEl) {
                            errEl.classList.add('show');
                            errEl.textContent = errors.preferences[0];
                        }
                    } else if (message) {
                        CN.toast(message, 'error');
                    }
                }
            });
        });
    }

    /* ---------- فاز ۵۲ — تایمر تعهد زمان تحویل (SLA) ----------
       کارت «تعهد ما: تحویل تا …» فقط وقتی نشان داده می‌شود که:
       • مدیر سوییچ SLA را روشن کرده (sla_deadline ارسال می‌شود)
       • سفارش پرداخت شده و هنوز تحویل نشده است                      */
    function renderSla(o) {
        var card = document.getElementById('slaCard');
        if (!card) { return; }

        if (!o.sla_deadline || !o.is_paid ||
            ['delivered', 'completed', 'cancelled', 'refunded'].indexOf(o.status) !== -1) {
            card.classList.add('hidden');
            return;
        }

        card.classList.remove('hidden');

        var dFaEl = document.getElementById('slaDeadlineFa');
        if (dFaEl && !dFaEl.dataset.set) {
            dFaEl.textContent = CN.toFaDigits(new Date(o.sla_deadline).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' })) || '';
            dFaEl.dataset.set = '1';
        }

        tickSla();
    }

    /* تیک هر ثانیه — باقی‌مانده را از deadline محلی حساب می‌کند */
    function tickSla() {
        var card = document.getElementById('slaCard');
        if (!card || card.classList.contains('hidden') || !order || !order.sla_deadline) { return; }

        var left = Math.floor((new Date(order.sla_deadline).getTime() - Date.now()) / 1000);
        var remainEl = document.getElementById('slaRemaining');
        var statusEl = document.getElementById('slaStatusText');
        if (!remainEl) { return; }

        if (left > 0) {
            var h = Math.floor(left / 3600), m = Math.floor((left % 3600) / 60), s2 = left % 60;
            remainEl.textContent = CN.toFaDigits((h > 0 ? h + ':' : '') + String(m).padStart(2, '0') + ':' + String(s2).padStart(2, '0'));
            if (statusEl) { statusEl.textContent = 'اگر دیرتر از این زمان تحویل شود، مسئولیت با ماست.'; }
        } else {
            remainEl.textContent = CN.toFaDigits(Math.floor(-left / 60)) + ' دقیقه دیرکرد';
            remainEl.style.background = 'color-mix(in srgb, var(--err,#e11d48) 15%, transparent)';
            remainEl.style.color = 'var(--err,#e11d48)';
            if (statusEl) { statusEl.textContent = 'از زمان تعهد ما گذشته است — با پشتیبانی تماس بگیرید.'; }
        }
    }

    function renderAssignment(o) {
        var broadcasting = o.status === 'broadcasting';
        var queued = o.status === 'queued';

        var bcCardEl = document.getElementById('broadcastCard');
        if (bcCardEl) { bcCardEl.classList.toggle('hidden', !broadcasting); }
        var qCardEl = document.getElementById('queuedCard');
        if (qCardEl) { qCardEl.classList.toggle('hidden', !queued); }

        /* v39 — متن‌ها و ثانیه‌شمار از تصمیم مدیر (تنظیمات سفارش‌ها) */
        var timerEnabled = o.broadcast_timer_enabled !== false; // پیش‌فرض: روشن
        var bcTimerEl = document.getElementById('broadcastTimer');
        if (bcTimerEl) { bcTimerEl.classList.toggle('hidden', !timerEnabled); }
        if (o.broadcast_text) {
            var bcDescEl = document.getElementById('broadcastDesc');
            if (bcDescEl) { bcDescEl.innerHTML = CN.esc(o.broadcast_text).replace(/\n/g, '<br>'); }
        }
        if (o.queued_text) {
            var qDescEl = document.getElementById('queuedDesc');
            if (qDescEl) { qDescEl.innerHTML = CN.esc(o.queued_text).replace(/\n/g, '<br>'); }
        }

        if (broadcasting) {
            var s = parseInt(o.broadcast_seconds_left, 10) || 0;
            BROADCAST_TOTAL = Math.max(15, s > 0 ? s : 60);
            if (bcCardEl) { bcCardEl.dataset.seconds = String(s); }

            var notes = [];
            if (o.broadcast_attempts > 1) {
                notes.push('دور ارسال ' + CN.toFaDigits(o.broadcast_attempts));
            }
            if (s <= 0) {
                notes.push('در حال تعیین‌تکلیف…');
            }
            var bcNoteEl = document.getElementById('broadcastAttemptsNote');
            if (bcNoteEl) { bcNoteEl.textContent = notes.join(' · '); }
            var bcSecondsEl = document.getElementById('broadcastSeconds');
            if (bcSecondsEl) { bcSecondsEl.textContent = CN.toFaDigits(s); }

            var ring = document.getElementById('broadcastRing');
            if (ring) {
                ring.setAttribute('stroke-dasharray', String(RING_C));
                ring.setAttribute('stroke-dashoffset', String(RING_C * (1 - Math.min(1, s / BROADCAST_TOTAL))));
            }
            var timer = document.getElementById('broadcastTimer');
            if (timer) { timer.classList.toggle('danger', s <= 10); }

            startPolling();
        } else if (queued) {
            /* فاز ۴۶ — «در صف از تاریخ» (queuedAtNote) طبق درخواست مالک حذف شد */

            /* v39 — انتخاب راه ارتباطی (فقط وقتی مهلت تمام شده و اپراتوری قبول نکرده) */
            renderContactPrefs(o.contact_preference || null);

            startPolling();
        } else if (['accepted', 'paid', 'in_progress', 'needs_info'].indexOf(o.status) !== -1) {
            // فاز ۱۱ — تا پایان چرخهٔ کار، صفحه زنده می‌ماند
            startPolling();
        } else {
            stopPolling();
            stopTicking();
        }
    }

    /* ---------- فاز ۱۲ — جایگذاری پرداخت: فاکتور داخل چت یا کارت جدا ---------- */
    function renderPayment() {
        if (!order) { return; }
        var o = order;

        var payAccepted = o.status === 'accepted' && !o.is_paid;           // جریان جدید: اتصال → پرداخت
        var payLegacy = o.status === 'pending_payment' && !o.is_paid;      // سفارش‌های قدیمی
        var inChat = payAccepted && chatCardVisible;                        // چت در دسترس → فاکتور داخل چت

        /* کارت جدا: فقط legacy یا حالت نادرِ accepted بدون چت */
        var showStandalone = payLegacy || (payAccepted && !chatCardVisible);
        var paymentCardEl = document.getElementById('paymentCard');
        if (paymentCardEl) { paymentCardEl.classList.toggle('hidden', !showStandalone); }
        var payConnectNoteEl = document.getElementById('payConnectNote');
        if (payConnectNoteEl) { payConnectNoteEl.classList.toggle('hidden', !(showStandalone && payAccepted)); }
        if (showStandalone) {
            var payTotalEl = document.getElementById('payTotal');
            if (payTotalEl) { payTotalEl.textContent = CN.faMoneyUnit(o.total_amount); }
            var u = CN.user();
            var walletHintEl = document.getElementById('walletBalanceHint');
            if (walletHintEl) { walletHintEl.textContent = '(موجودی: ' + CN.faMoney((u && u.wallet_balance) || 0) + ')'; }
        }

        /* فاکتور داخل چت — v42: بعد از پرداختِ موفق کامل پنهان می‌شود
           (تاریخچهٔ پرداخت در شیت «اطلاعات سفارش» هست)؛ تا همین نسخه
           حالت سبز «پرداخت شد» می‌ماند و تا رفرش برنمی‌گشت */
        var inv = document.getElementById('chatInvoice');
        var alreadyPaid = o.is_paid || o.status === 'paid';
        if (inv) { inv.classList.toggle('hidden', !inChat || alreadyPaid); }

        if (inChat && !alreadyPaid) {
            var invServiceEl = document.getElementById('invService');
            if (invServiceEl) { invServiceEl.textContent = o.service ? o.service.name : 'سفارش ' + o.order_number; }
            var invAmountEl = document.getElementById('invAmount');
            if (invAmountEl) { invAmountEl.textContent = CN.faMoneyUnit(o.total_amount); }
            var invPaidAmountEl = document.getElementById('invPaidAmount');
            if (invPaidAmountEl) { invPaidAmountEl.textContent = CN.faMoneyUnit(o.total_amount); }
            var uw = CN.user();
            var invWalletHintEl = document.getElementById('invWalletHint');
            if (invWalletHintEl) { invWalletHintEl.textContent = '(موجودی: ' + CN.faMoney((uw && uw.wallet_balance) || 0) + ')'; }

            var paid = alreadyPaid;
            if (inv) { inv.classList.toggle('paid', !!paid); }
            var invPaidAtEl = document.getElementById('invPaidAt');
            if (invPaidAtEl) { invPaidAtEl.textContent = o.paid_at_fa || ''; }
            if (!paid) {
                var invStateEl = document.getElementById('invState');
                if (invStateEl) { invStateEl.textContent = 'در انتظار پرداخت'; }
                var invNoteEl = document.getElementById('invNote');
                if (invNoteEl) { invNoteEl.textContent = 'برای شروع کار اپراتور، پرداخت را تکمیل کنید.'; }
            }
        }
    }

    /* ---------- v31 — جایگذاری کارت لغو ----------
       وضعیت‌های پیش از اتصال (پخش/صف/پرداخت legacy): زیر کارت وضعیت داخل ناحیهٔ پیام‌ها؛
       گفتگوی فعال (accepted): پایین شیت «اطلاعات سفارش». DOM با appendTo جابه‌جا می‌شود
       تا شنونده‌های مستقیم دکمه حفظ شوند. */
    function placeCancel(o) {
        var cancelable = ['pending_payment', 'broadcasting', 'queued', 'accepted'].indexOf(o.status) !== -1 && !o.is_paid;
        var card = document.getElementById('cancelCard');
        if (!card) { return; }

        card.classList.toggle('hidden', !cancelable);
        if (!cancelable) { return; }

        var preChat = ['pending_payment', 'broadcasting', 'queued'].indexOf(o.status) !== -1;
        var target = document.getElementById(preChat ? 'stateActions' : 'chatinfoActions');
        if (target && card.parentElement !== target) {
            target.appendChild(card);
        }
    }

    /* ---------- v31 — شیت «اطلاعات سفارش» (روند/خلاصه/مدارک/تاریخچه پرداخت) ---------- */
    function openInfoSheet() {
        var backdrop = document.getElementById('chatinfoBackdrop');
        if (backdrop) {
            backdrop.classList.add('show');
            backdrop.setAttribute('aria-hidden', 'false');
        }
        var sheet = document.getElementById('chatinfoSheet');
        if (sheet) { sheet.classList.add('open'); }
        var infoBtn = document.getElementById('orderInfoBtn');
        if (infoBtn) { infoBtn.setAttribute('aria-expanded', 'true'); }
    }

    function closeInfoSheet() {
        var backdrop = document.getElementById('chatinfoBackdrop');
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.setAttribute('aria-hidden', 'true');
        }
        var sheet = document.getElementById('chatinfoSheet');
        if (sheet) { sheet.classList.remove('open'); }
        var infoBtn = document.getElementById('orderInfoBtn');
        if (infoBtn) { infoBtn.setAttribute('aria-expanded', 'false'); }
    }

    var orderInfoBtnEl = document.getElementById('orderInfoBtn');
    if (orderInfoBtnEl) { orderInfoBtnEl.addEventListener('click', openInfoSheet); }
    var chatinfoCloseEl = document.getElementById('chatinfoClose');
    if (chatinfoCloseEl) { chatinfoCloseEl.addEventListener('click', closeInfoSheet); }
    var chatinfoBackdropEl = document.getElementById('chatinfoBackdrop');
    if (chatinfoBackdropEl) { chatinfoBackdropEl.addEventListener('click', closeInfoSheet); }

    /* فاز ۱۲ — اطلاع از دسترس‌پذیری کارت گفتگو (order-chat.js) */
    document.addEventListener('chat:visibility', function (e) {
        var v = !!(e && e.detail && e.detail.visible);
        if (v !== chatCardVisible) {
            chatCardVisible = v;
            renderPayment();
        }
    });

    /* ---------- پرداخت آنلاین (مشترک بین کارت جدا و فاکتور چت) ---------- */
    function payOnline(btn) {
        CN.btnLoading(btn, true, 'در حال اتصال به درگاه…');
        var payErrorEl = document.getElementById('payError');
        if (payErrorEl) { payErrorEl.classList.remove('show'); }
        var invErrorEl = document.getElementById('invError');
        if (invErrorEl) { invErrorEl.classList.remove('show'); }

        CN.api('/orders/' + orderId + '/pay', {
            method: 'POST',
            data: { method: 'online' },
            success: function (resp) {
                // مسیر نسبی → سازگار با گیت‌وی پیش‌نمایش و دامنهٔ واقعی
                var url = resp.payment_path
                    ? CN.withPort(resp.payment_path)
                    : (resp.payment_url || '');
                if (url) {
                    CN.toast(resp.message || 'انتقال به درگاه…', 'info', 1800);
                    window.setTimeout(function () {
                        window.location.href = url;
                    }, 500);
                } else {
                    CN.btnLoading(btn, false);
                }
            },
            error: function (xhr, message) {
                CN.btnLoading(btn, false);
                if (payErrorEl) { payErrorEl.textContent = message; payErrorEl.classList.add('show'); }
                if (invErrorEl) { invErrorEl.textContent = message; invErrorEl.classList.add('show'); }
            }
        });
    }

    /* ---------- پرداخت کیف پول (مشترک) ---------- */
    function payWallet(btn) {
        var payErrorEl = document.getElementById('payError');
        if (payErrorEl) { payErrorEl.classList.remove('show'); }
        var invErrorEl = document.getElementById('invError');
        if (invErrorEl) { invErrorEl.classList.remove('show'); }

        CN.confirm({
            title: 'پرداخت از کیف پول',
            desc: 'مبلغ ' + CN.faMoneyUnit(order ? order.total_amount : 0) + ' از موجودی کیف پول شما کسر می‌شود.',
            okText: 'پرداخت'
        }, function () {
            CN.btnLoading(btn, true, 'در حال پرداخت…');

            CN.api('/orders/' + orderId + '/pay', {
                method: 'POST',
                data: { method: 'wallet' },
                success: function (resp) {
                    CN.btnLoading(btn, false);
                    CN.toast(resp.message || 'پرداخت انجام شد.', 'success');
                    CN.refreshChrome();
                    load();
                },
                error: function (xhr, message) {
                    CN.btnLoading(btn, false);
                    if (payErrorEl) { payErrorEl.textContent = message; payErrorEl.classList.add('show'); }
                    if (invErrorEl) { invErrorEl.textContent = message; invErrorEl.classList.add('show'); }
                }
            });
        });
    }

    var payOnlineBtnEl = document.getElementById('payOnlineBtn');
    if (payOnlineBtnEl) { payOnlineBtnEl.addEventListener('click', function () { payOnline(payOnlineBtnEl); }); }
    var payWalletBtnEl = document.getElementById('payWalletBtn');
    if (payWalletBtnEl) { payWalletBtnEl.addEventListener('click', function () { payWallet(payWalletBtnEl); }); }

    /* فاکتور داخل چت */
    var invPayOnlineEl = document.getElementById('invPayOnline');
    if (invPayOnlineEl) { invPayOnlineEl.addEventListener('click', function () { payOnline(invPayOnlineEl); }); }
    var invPayWalletEl = document.getElementById('invPayWallet');
    if (invPayWalletEl) { invPayWalletEl.addEventListener('click', function () { payWallet(invPayWalletEl); }); }

    /* ---------- لغو (با دلیل اجباری — فاز ۲۳) ---------- */
    var cancelSubmitting = false;
    var MIN_REASON = 5;

    function openCancelSheet() {
        closeInfoSheet(); /* v31 — شیت اطلاعات بسته شود تا دو شیت روی هم نیفتند */

        /* ریست وضعیت شیت */
        var reasonInputReset = document.getElementById('cancelReasonInput');
        if (reasonInputReset) {
            reasonInputReset.value = '';
            reasonInputReset.classList.remove('invalid');
        }
        var reasonErrorReset = document.getElementById('cancelReasonInputError');
        if (reasonErrorReset) {
            reasonErrorReset.classList.remove('show');
            reasonErrorReset.textContent = '';
        }
        Array.prototype.forEach.call(document.querySelectorAll('#cancelReasonChips .chip'), function (chipEl) {
            chipEl.classList.remove('active');
        });
        var confirmBtnReset = document.getElementById('cancelConfirmBtn');
        if (confirmBtnReset) { confirmBtnReset.disabled = true; }
        cancelSubmitting = false;

        var cancelBackdropEl = document.getElementById('cancelBackdrop');
        if (cancelBackdropEl) {
            cancelBackdropEl.classList.add('show');
            cancelBackdropEl.setAttribute('aria-hidden', 'false');
        }
        var cancelSheetEl = document.getElementById('cancelSheet');
        if (cancelSheetEl) { cancelSheetEl.classList.add('open'); }
    }

    function closeCancelSheet() {
        var cancelBackdropEl = document.getElementById('cancelBackdrop');
        if (cancelBackdropEl) {
            cancelBackdropEl.classList.remove('show');
            cancelBackdropEl.setAttribute('aria-hidden', 'true');
        }
        var cancelSheetEl = document.getElementById('cancelSheet');
        if (cancelSheetEl) { cancelSheetEl.classList.remove('open'); }
    }

    function reasonValid() {
        var inputEl = document.getElementById('cancelReasonInput');
        var v = ((inputEl && inputEl.value) || '').trim();
        return v.length >= MIN_REASON;
    }

    function refreshCancelState(showError) {
        var ok = reasonValid();
        var confirmBtnEl = document.getElementById('cancelConfirmBtn');
        if (confirmBtnEl) { confirmBtnEl.disabled = !ok || cancelSubmitting; }

        if (!ok && showError) {
            var inputEl2 = document.getElementById('cancelReasonInput');
            var v = ((inputEl2 && inputEl2.value) || '').trim();
            CN.fieldError('cancelReasonInput', v
                ? ('دلیل لغو باید حداقل ' + CN.toFaDigits(MIN_REASON) + ' نویسه باشد.')
                : 'انتخاب یا نوشتن دلیل لغو الزامی است.');
        }
        return ok;
    }

    var cancelOrderBtnEl = document.getElementById('cancelOrderBtn');
    if (cancelOrderBtnEl) { cancelOrderBtnEl.addEventListener('click', openCancelSheet); }
    var cancelSheetCloseEl = document.getElementById('cancelSheetClose');
    if (cancelSheetCloseEl) { cancelSheetCloseEl.addEventListener('click', closeCancelSheet); }
    var cancelGiveupBtnEl = document.getElementById('cancelGiveupBtn');
    if (cancelGiveupBtnEl) { cancelGiveupBtnEl.addEventListener('click', closeCancelSheet); }
    var cancelBackdropClickEl = document.getElementById('cancelBackdrop');
    if (cancelBackdropClickEl) { cancelBackdropClickEl.addEventListener('click', closeCancelSheet); }

    /* چیپ دلیل: انتخاب → متن داخل textarea (قابل ویرایش) */
    var cancelReasonChipsEl = document.getElementById('cancelReasonChips');
    if (cancelReasonChipsEl) {
        cancelReasonChipsEl.addEventListener('click', function (e) {
            var chip = e.target.closest('.chip');
            if (!chip) { return; }
            var wasActive = chip.classList.contains('active');

            Array.prototype.forEach.call(cancelReasonChipsEl.querySelectorAll('.chip'), function (chipEl) {
                chipEl.classList.remove('active');
            });
            var reasonInputEl = document.getElementById('cancelReasonInput');
            if (wasActive) {
                /* کلیک دوباره = برداشتن انتخاب */
                if (reasonInputEl) { reasonInputEl.value = ''; }
            } else {
                chip.classList.add('active');
                if (reasonInputEl) { reasonInputEl.value = chip.dataset.reason || ''; }
            }
            if (reasonInputEl) { reasonInputEl.classList.remove('invalid'); }
            var reasonErrorEl = document.getElementById('cancelReasonInputError');
            if (reasonErrorEl) {
                reasonErrorEl.classList.remove('show');
                reasonErrorEl.textContent = '';
            }
            refreshCancelState(false);
        });
    }

    var cancelReasonInputEl = document.getElementById('cancelReasonInput');
    if (cancelReasonInputEl) {
        cancelReasonInputEl.addEventListener('input', function () {
            /* ویرایش دستی → انتخاب چیپ برداشته می‌شود */
            var activeChip = document.querySelector('#cancelReasonChips .chip.active');
            var chipText = (activeChip && activeChip.dataset.reason) || '';
            if (chipText && cancelReasonInputEl.value !== chipText) {
                Array.prototype.forEach.call(document.querySelectorAll('#cancelReasonChips .chip'), function (chipEl) {
                    chipEl.classList.remove('active');
                });
            }
            refreshCancelState(false);
        });
    }

    var cancelSheetFormEl = document.getElementById('cancelSheet');
    if (cancelSheetFormEl) {
        cancelSheetFormEl.addEventListener('submit', function (e) { e.preventDefault(); });
    }

    var cancelConfirmBtnEl = document.getElementById('cancelConfirmBtn');
    if (cancelConfirmBtnEl) {
        cancelConfirmBtnEl.addEventListener('click', function () {
            if (cancelSubmitting || !refreshCancelState(true)) { return; }

            var reasonInputEl = document.getElementById('cancelReasonInput');
            var reason = ((reasonInputEl && reasonInputEl.value) || '').trim();
            cancelSubmitting = true;
            CN.btnLoading(cancelConfirmBtnEl, true, 'در حال لغو…');

            CN.api('/orders/' + orderId + '/cancel', {
                method: 'POST',
                data: { reason: reason },
                success: function (resp) {
                    cancelSubmitting = false;
                    CN.btnLoading(cancelConfirmBtnEl, false);
                    closeCancelSheet();
                    CN.toast(resp.message || 'درخواست لغو شد.', 'success');
                    load();
                },
                error: function (xhr, message) {
                    cancelSubmitting = false;
                    CN.btnLoading(cancelConfirmBtnEl, false);
                    /* خطای فیلد reason روی textarea؛ بقیه روی توست
                       [Task 9] xhr دیگر responseJSON ندارد → پارس دستی */
                    var body = null;
                    try { body = JSON.parse(xhr.responseText); } catch (parseErr) { body = null; }
                    var errors = (body && body.errors) || {};
                    if (errors.reason && errors.reason.length) {
                        CN.fieldError('cancelReasonInput', errors.reason[0]);
                    } else {
                        CN.toast(message || 'لغو سفارش ناموفق بود.', 'error');
                    }
                }
            });
        });
    }

    /* بستن شیت‌ها با Escape */
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        var cancelSheetEsc = document.getElementById('cancelSheet');
        var chatinfoSheetEsc = document.getElementById('chatinfoSheet');
        if (cancelSheetEsc && cancelSheetEsc.classList.contains('open')) { closeCancelSheet(); }
        else if (chatinfoSheetEsc && chatinfoSheetEsc.classList.contains('open')) { closeInfoSheet(); }
    });

    /* ---------- v38 Realtime پوشر — پیگیری سفارش بدون پولینگ ----------
       تغییر وضعیت سفارش (سمت سرور) → رویداد order.changed روی کانال شخصی
       کاربر → یک loadSilent همان لحظه (اگر مال همین سفارش بود). قطع اتصال →
       interval خودکار برمی‌گردد؛ وصل شدن → دوباره خاموش می‌شود. */
    var unConn = null;

    function bindRealtime() {
        if (!window.RT || !RT.active()) { return; }

        /* کانال شخصی کاربر — در اپ مشتری از API خوانده می‌شود (یک درخواست سبک) */
        if (RT.cfg.channel) {
            subscribeOrderEvents();
            return;
        }

        CN.api('/realtime/config', {
            success: function (resp) {
                if (!resp || !resp.enabled || !resp.channel) { return; }
                RT.cfg.channel = resp.channel;
                subscribeOrderEvents();
            },
            error: function () { /* interval معمولی کافی است */ }
        });
    }

    function subscribeOrderEvents() {
        var wake = function (data) {
            var oid = data && data.order ? Number(data.order) : 0;
            if (oid && orderId && oid !== orderId) { return; }
            loadSilent();
        };

        RT.bindUser('order.changed', wake);
        RT.bindUser('notif.new', function () {
            if (!document.hidden) { loadSilent(); }
        });

        /* پاک‌سازی هنگام خروج از صفحه (ناوبری SPA) — ضد زامبی */
        document.addEventListener('livewire:navigate', function () {
            stopPolling();
            if (unConn) { unConn(); unConn = null; }
        }, { once: true });

        unConn = RT.onConnection(function (up) {
            if (up) { stopPolling(); loadSilent(); }
            else if (!pollTimer) { pollTimer = window.setInterval(loadSilent, 4000); }
        });
    }

    load();
    bindRealtime();
})();
