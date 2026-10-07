/* اپ مشتری — صفحه ورود با OTP (Vanilla JS — بدون jQuery) */
/* global CN */
(function () {
    'use strict';

    // اگر قبلاً وارد شده → صفحه مناسب (v24: پروفایل ناقص → مستقیم ویرایش اطلاعات)
    if (CN.token() && CN.user()) {
        window.location.replace(CN.withPort(CN.user().profile_completed ? '/app/home' : '/app/profile/edit?new=1'));
        return;
    }

    var currentMobile = '';
    var resendSeconds = 90;

    var stepMobile = document.getElementById('stepMobile');
    var stepCode = document.getElementById('stepCode');
    var mobileInput = document.getElementById('mobileInput');
    var codeInput = document.getElementById('codeInput');

    function showStep(step) {
        if (step === 'code') {
            if (stepMobile) { stepMobile.classList.add('hidden'); }
            if (stepCode) { stepCode.classList.remove('hidden'); }
            var codeTarget = document.getElementById('codeTarget');
            if (codeTarget) { codeTarget.textContent = 'به ' + CN.toFaDigits(currentMobile); }
            window.setTimeout(function () { if (codeInput) { codeInput.focus(); } }, 120);
        } else {
            if (stepCode) { stepCode.classList.add('hidden'); }
            if (stepMobile) { stepMobile.classList.remove('hidden'); }
            window.setTimeout(function () { if (mobileInput) { mobileInput.focus(); } }, 120);
        }
    }

    /* ---------- گام ۱: درخواست کد ---------- */
    function requestOtp() {
        CN.clearFieldErrors(stepMobile);
        currentMobile = CN.normalizeMobile(mobileInput ? mobileInput.value : '');

        if (!/^09\d{9}$/.test(currentMobile)) {
            CN.fieldError('mobileError', 'شماره موبایل معتبر نیست؛ نمونه: ۰۹۱۲۳۴۵۶۷۸۹');
            return;
        }
        var mobileError = document.getElementById('mobileError');
        if (mobileError) { mobileError.classList.add('show'); }

        CN.btnLoading(document.getElementById('sendOtpBtn'), true, 'در حال ارسال…');

        CN.api('/otp/request', {
            method: 'POST',
            data: { mobile: currentMobile },
            success: function (resp) {
                CN.btnLoading(document.getElementById('sendOtpBtn'), false);
                resendSeconds = resp.resend_in || 90;
                showStep('code');

                var devCodeNote = document.getElementById('devCodeNote');
                if (resp.dev_code) {
                    var devCodeValue = document.getElementById('devCodeValue');
                    if (devCodeValue) { devCodeValue.textContent = CN.toFaDigits(resp.dev_code); }
                    if (devCodeNote) { devCodeNote.classList.remove('hidden'); }
                } else if (devCodeNote) {
                    devCodeNote.classList.add('hidden');
                }

                CN.countdown(document.getElementById('resendTimer'), document.getElementById('resendBtn'), resendSeconds);
                CN.toast('کد تأیید به شماره شما پیامک شد.', 'success');
            },
            error: function (xhr, message) {
                CN.btnLoading(document.getElementById('sendOtpBtn'), false);
                CN.fieldError('mobileError', message);
            }
        });
    }

    /* ---------- گام ۲: تأیید ---------- */
    function verifyOtp() {
        CN.clearFieldErrors(stepCode);
        var code = CN.toEnDigits(codeInput ? codeInput.value : '').trim();

        if (!/^\d{4,8}$/.test(code)) {
            CN.fieldError('codeError', 'کد تأیید را کامل و درست وارد کنید.');
            return;
        }

        CN.btnLoading(document.getElementById('verifyBtn'), true, 'در حال بررسی…');

        CN.api('/otp/verify', {
            method: 'POST',
            data: { mobile: currentMobile, code: code },
            success: function (resp) {
                CN.setSession(resp.token, resp.user);
                CN.toast('خوش آمدید ' + (resp.user.name ? resp.user.name : '📖'), 'success');
                // v24: ثبت‌نام اولیه → مستقیم به ویرایش اطلاعات (تکمیل پروفایل الزامی است)
                window.location.replace(CN.withPort(resp.profile_completed ? '/app/home' : '/app/profile/edit?new=1'));
            },
            error: function (xhr, message) {
                CN.btnLoading(document.getElementById('verifyBtn'), false);
                CN.fieldError('codeError', message);
            }
        });
    }

    /* ---------- رویدادها ---------- */
    if (document.getElementById('sendOtpBtn')) {
        document.getElementById('sendOtpBtn').addEventListener('click', requestOtp);
    }
    if (mobileInput) {
        mobileInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { requestOtp(); }
        });
    }

    if (document.getElementById('verifyBtn')) {
        document.getElementById('verifyBtn').addEventListener('click', verifyOtp);
    }
    if (codeInput) {
        codeInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { verifyOtp(); }
        });
    }

    if (document.getElementById('resendBtn')) {
        document.getElementById('resendBtn').addEventListener('click', function () {
            var btn = document.getElementById('resendBtn');
            btn.disabled = true;
            CN.api('/otp/request', {
                method: 'POST',
                data: { mobile: currentMobile },
                success: function (resp) {
                    CN.countdown(document.getElementById('resendTimer'), btn, resp.resend_in || 90);
                    if (resp.dev_code) {
                        var devCodeValue = document.getElementById('devCodeValue');
                        if (devCodeValue) { devCodeValue.textContent = CN.toFaDigits(resp.dev_code); }
                        var devCodeNote = document.getElementById('devCodeNote');
                        if (devCodeNote) { devCodeNote.classList.remove('hidden'); }
                    }
                    CN.toast('کد جدید پیامک شد.', 'success');
                },
                error: function (xhr, message) {
                    btn.disabled = false;
                    CN.toast(message, 'error');
                }
            });
        });
    }

    if (document.getElementById('backBtn')) {
        document.getElementById('backBtn').addEventListener('click', function () {
            if (codeInput) { codeInput.value = ''; }
            var devCodeNote = document.getElementById('devCodeNote');
            if (devCodeNote) { devCodeNote.classList.add('hidden'); }
            showStep('mobile');
        });
    }

    // کلیک روی باکس کد توسعه → پر کردن خودکار (تست)
    var devCodeNoteBox = document.getElementById('devCodeNote');
    if (devCodeNoteBox) {
        devCodeNoteBox.addEventListener('click', function () {
            var devCodeValue = document.getElementById('devCodeValue');
            var v = devCodeValue ? devCodeValue.textContent : '';
            if (codeInput) {
                codeInput.value = CN.toEnDigits(v);
                codeInput.focus();
            }
        });
    }

    window.setTimeout(function () { if (mobileInput) { mobileInput.focus(); } }, 250);
})();
