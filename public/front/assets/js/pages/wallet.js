/* اپ مشتری — کیف پول */
/* [Task 9] Vanilla JS — بدون جی‌کوئری */
/* global CN */
(function () {
    'use strict';

    if (!CN.requireCompleteProfile()) { return; }

    var state = { page: 1, hasMore: false, loading: false };

    function load() {
        if (state.loading) { return; }
        state.loading = true;

        var apiPath = '/wallet' + (state.page > 1 ? '?page=' + state.page : '');

        if (state.page === 1) {
            var txList = document.getElementById('txList');
            if (txList) {
                txList.innerHTML = '<div class="skeleton" style="height:60px"></div><div class="skeleton" style="height:60px"></div>';
            }
        } else {
            var txMoreLoader = document.getElementById('txMoreLoader');
            if (txMoreLoader) { txMoreLoader.classList.remove('hidden'); }
        }

        CN.api(apiPath, {
            success: function (resp) {
                state.loading = false;

                var container = document.getElementById('txList');
                if (state.page === 1 && container) { container.innerHTML = ''; }

                var moreLoader = document.getElementById('txMoreLoader');
                if (moreLoader) { moreLoader.classList.add('hidden'); }

                var walletBalance = document.getElementById('walletBalance');
                if (walletBalance) { walletBalance.textContent = CN.faMoney(resp.balance || 0); }

                var u = CN.user();
                if (u) {
                    u.wallet_balance = resp.balance || 0;
                    try { window.localStorage.setItem('cn_user', JSON.stringify(u)); } catch (e) { /* noop */ }
                }

                var walletMobile = document.getElementById('walletMobile');
                if (walletMobile) { walletMobile.textContent = 'شماره حساب: ' + (CN.toFaDigits((u && u.mobile) || '')); }

                var txCount = document.getElementById('txCount');
                if (txCount) { txCount.textContent = resp.transactions ? CN.toFaDigits(resp.transactions.total || 0) + ' تراکنش' : ''; }

                var txs = (resp.transactions && resp.transactions.data) || [];
                var html = '';
                txs.forEach(function (t) {
                    html += txRow(t);
                });
                if (container) { container.insertAdjacentHTML('beforeend', html); }

                state.hasMore = !!(resp.transactions && resp.transactions.next_page_url);
                var txLoadMore = document.getElementById('txLoadMore');
                if (txLoadMore) { txLoadMore.classList.toggle('hidden', !state.hasMore); }

                var empty = !txs.length && state.page === 1;
                var txEmpty = document.getElementById('txEmpty');
                if (txEmpty) { txEmpty.classList.toggle('hidden', !empty); }
                if (container) { container.classList.toggle('hidden', empty); }
            },
            error: function () {
                state.loading = false;
                var moreLoader = document.getElementById('txMoreLoader');
                if (moreLoader) { moreLoader.classList.add('hidden'); }
            }
        });
    }

    function txRow(t) {
        var isCredit = t.type === 'credit';

        return '<div class="tx-row">' +
            '<span class="tx-icon ' + (isCredit ? 'credit' : 'debit') + '">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            (isCredit
                ? '<path d="M12 19V5"/><path d="m5 12 7-7 7 7"/>'
                : '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>') +
            '</svg></span>' +
            '<span class="tx-body">' +
            '<span class="tx-title">' + CN.esc(t.description || (isCredit ? 'واریز' : 'برداشت')) + '</span>' +
            '<span class="tx-time">' + CN.esc(t.created_at_fa || '') + ' · موجودی پس از تراکنش: ' + CN.faMoney(t.balance_after) + '</span>' +
            '</span>' +
            '<span class="tx-amount ' + (isCredit ? 'credit' : 'debit') + '">' + (isCredit ? '+' : '−') + CN.faMoney(t.amount) + '</span>' +
            '</div>';
    }

    var txLoadMoreBtn = document.getElementById('txLoadMore');
    if (txLoadMoreBtn) {
        txLoadMoreBtn.addEventListener('click', function () {
            if (!state.hasMore || state.loading) { return; }
            state.page++;
            load();
        });
    }

    /* ---------- شارژ کیف پول از درگاه ---------- */
    var selectedAmount = 0;

    /* عناصر ثابت صفحه — [Task 9] عناصر خام (جایگزین انتخاب‌گرهای قدیمی) */
    var chargeOverlay = document.getElementById('chargeOverlay');
    var chargeSheet = document.getElementById('chargeSheet');
    var chargeError = document.getElementById('chargeError');
    var chargeCustom = document.getElementById('chargeCustom');
    var quickAmounts = document.getElementById('quickAmounts');

    /* پیش‌نمایش مبلغ انتخابی با جداکنندهٔ هزارگان (۳رقمی) — v24 */
    function updateAmountPreview() {
        var p = document.getElementById('chargePreview');
        if (!p) { return; }
        if (selectedAmount > 0) {
            p.innerHTML = '<span class="cap-label">مبلغ انتخابی</span><strong class="num">' + CN.faMoney(selectedAmount) + '</strong><span class="cap-unit">تومان</span>';
            p.classList.remove('hidden');
        } else {
            p.classList.add('hidden');
            p.innerHTML = '';
        }
    }

    function resetAmountUI() {
        selectedAmount = 0;
        if (quickAmounts) {
            Array.prototype.forEach.call(quickAmounts.querySelectorAll('.charge-amt'), function (a) {
                a.classList.remove('active');
            });
        }
        if (chargeCustom) { chargeCustom.value = ''; }
        updateAmountPreview();
    }

    function openSheet() {
        if (chargeOverlay) {
            chargeOverlay.classList.add('show');
            chargeOverlay.setAttribute('aria-hidden', 'false');
        }
        if (chargeSheet) { chargeSheet.classList.add('open'); }
        hideChargeError();
        updateAmountPreview();
    }

    function closeSheet() {
        if (chargeOverlay) {
            chargeOverlay.classList.remove('show');
            chargeOverlay.setAttribute('aria-hidden', 'true');
        }
        if (chargeSheet) { chargeSheet.classList.remove('open'); }
    }

    /* [Task 9] #chargeError با style="display:none" مخفی می‌شود؛
       متدهای show/hide قدیمی display را به‌ترتیب '' و 'none' می‌کنند — عین همان رفتار */
    function showChargeError(msg) {
        if (chargeError) {
            chargeError.textContent = msg;
            chargeError.style.display = '';
        }
    }

    function hideChargeError() {
        if (chargeError) { chargeError.style.display = 'none'; }
    }

    var chargeBtn = document.getElementById('chargeBtn');
    if (chargeBtn) { chargeBtn.addEventListener('click', openSheet); }

    var chargeClose = document.getElementById('chargeClose');
    if (chargeClose) { chargeClose.addEventListener('click', closeSheet); }

    if (chargeOverlay) { chargeOverlay.addEventListener('click', closeSheet); }

    /* [Task 9] delegate روی #quickAmounts (جایگزین اتصال کلاسیک روی مبلغ‌های آماده) */
    if (quickAmounts) {
        quickAmounts.addEventListener('click', function (e) {
            var amt = e.target.closest ? e.target.closest('.charge-amt') : null;
            if (!amt || !quickAmounts.contains(amt)) { return; }

            Array.prototype.forEach.call(quickAmounts.querySelectorAll('.charge-amt'), function (a) {
                a.classList.remove('active');
            });
            amt.classList.add('active');
            selectedAmount = +amt.dataset.amount || 0;
            if (chargeCustom) { chargeCustom.value = ''; }
            updateAmountPreview();
            hideChargeError();
        });
    }

    if (chargeCustom) {
        chargeCustom.addEventListener('input', function () {
            var raw = CN.toEnDigits(chargeCustom.value).replace(/[^\d]/g, '');
            if (raw) {
                if (quickAmounts) {
                    Array.prototype.forEach.call(quickAmounts.querySelectorAll('.charge-amt'), function (a) {
                        a.classList.remove('active');
                    });
                }
                selectedAmount = parseInt(raw, 10) || 0;
            } else if (quickAmounts && !quickAmounts.querySelector('.charge-amt.active')) {
                /* ورودی خالی و چیپی هم انتخاب نیست → مقدار صفر */
                selectedAmount = 0;
            }
            updateAmountPreview();
            hideChargeError();
        });
    }

    var chargeSubmit = document.getElementById('chargeSubmit');
    if (chargeSubmit) {
        chargeSubmit.addEventListener('click', function () {
            /* [Task 9] دکمه به‌صورت عنصر خام برای CN.btnLoading */
            var btn = chargeSubmit;

            if (!selectedAmount || selectedAmount < 10000) {
                showChargeError('مبلغ را انتخاب یا وارد کنید (حداقل ۱۰,۰۰۰ تومان).');
                return;
            }
            if (selectedAmount > 50000000) {
                showChargeError('حداکثر مبلغ شارژ ۵۰,۰۰۰,۰۰۰ تومان است.');
                return;
            }

            hideChargeError();
            CN.btnLoading(btn, true, 'در حال اتصال به درگاه…');

            CN.api('/wallet/charge', {
                method: 'POST',
                data: { amount: selectedAmount },
                success: function (resp) {
                    // مسیر نسبی → سازگار با گیت‌وی پیش‌نمایش و دامنهٔ واقعی
                    var url = resp.payment_path
                        ? CN.withPort(resp.payment_path)
                        : (resp.payment_url || '');
                    if (url) {
                        window.location.href = url;
                    } else {
                        CN.btnLoading(btn, false);
                        showChargeError('خطا در ایجاد تراکنش؛ دوباره تلاش کنید.');
                    }
                },
                error: function (xhr, message) {
                    CN.btnLoading(btn, false);
                    showChargeError(message || 'خطا در شروع شارژ؛ دوباره تلاش کنید.');
                }
            });
        });
    }

    /* ---------- پیام نتیجهٔ بازگشت از درگاه (?charged=1 / ?payment=failed) ---------- */
    try {
        var query = Object.fromEntries(new URLSearchParams(window.location.search));
        if (query.charged === '1') {
            CN.toast('کیف پول شما با موفقیت شارژ شد ✓', 'success', 3200);
            history.replaceState(null, '', CN.withPort('/app/wallet'));
        } else if (query.payment === 'failed') {
            CN.toast('پرداخت شارژ ناموفق بود؛ دوباره تلاش کنید.', 'error', 3200);
            history.replaceState(null, '', CN.withPort('/app/wallet'));
        }
    } catch (e) { /* noop */ }

    load();
})();
