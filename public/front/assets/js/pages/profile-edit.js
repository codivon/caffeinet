/* اپ مشتری — ویرایش اطلاعات پروفایل (v24 — از صفحهٔ پروفایل جدا شد) (Vanilla JS — بدون jQuery) */
/* v39 — تاریخ تولد با سه لیست کشویی سال/ماه/روز شمسی (بدون دیت‌پیکر) */
/* global CN */
(function () {
    'use strict';

    if (!CN.requireAuth()) { return; }

    var provincesLoaded = false;
    var selectedProvinceId = null;
    var isNewUser = false;

    /* اولین ورود (?new=1) → بنر خوش‌آمد */
    try {
        isNewUser = new URLSearchParams(window.location.search).get('new') === '1';
    } catch (e) { isNewUser = false; }
    var welcomeBanner = document.getElementById('pfWelcomeBanner');
    if (welcomeBanner) { welcomeBanner.classList.toggle('hidden', !isNewUser); }

    /* ---------- v39 — انتخابگر تاریخ تولد (سه لیست کشویی) ---------- */
    var BIRTH_MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    /* بازهٔ سنین مجاز — از data-attribute تگ اسکریپت (CSP-safe؛ بدون inline) */
    var birthRange = (function () {
        var el = document.currentScript || document.querySelector('script[src*="profile-edit"]');
        var min = parseInt(el && el.getAttribute('data-birth-min'), 10);
        var max = parseInt(el && el.getAttribute('data-birth-max'), 10);
        return {
            minAge: (min >= 1 && min < 119) ? min : 10,
            maxAge: (max > 1 && max <= 120) ? max : 100
        };
    })();

    /* v40 — کد ملی: الزامی بودن (استعلام فینوتک فعال است؟) از data-attribute */
    var nidRequired = (function () {
        var el = document.currentScript || document.querySelector('script[src*="profile-edit"]');
        return !!(el && el.getAttribute('data-nid-required') === '1');
    })();

    /** چک‌سام کد ملی ۱۰ رقمی ایران */
    function isValidNationalId(raw) {
        var code = CN.toEnDigits(String(raw || '')).trim();
        if (!/^\d{10}$/.test(code)) { return false; }
        if (/^(\d)\1{9}$/.test(code)) { return false; } // تمام ارقام یکسان
        var sum = 0;
        for (var i = 0; i < 9; i++) { sum += parseInt(code[i], 10) * (10 - i); }
        var rem = sum % 11;
        var check = parseInt(code[9], 10);
        return (rem < 2) ? (check === rem) : (check === 11 - rem);
    }

    /** سال جاری شمسی */
    function currentJalaliYear() {
        if (window.CNJdp && CNJdp.toJalaali) {
            var n = new Date();
            var j = CNJdp.toJalaali(n.getFullYear(), n.getMonth() + 1, n.getDate());
            if (j && j.jy) { return j.jy; }
        }
        return Math.floor((new Date().getFullYear() + 621) ); // تقریب
    }

    /** تعداد روزهای یک ماه شمسی (کبیسهٔ اسفند لحاظ می‌شود) */
    function jalaliMonthDays(jy, jm) {
        if (!jy || !jm) { return 31; }
        if (window.CNJdp && CNJdp.monthLength) { return CNJdp.monthLength(jy, jm); }
        if (jm <= 6) { return 31; }
        if (jm <= 11) { return 30; }
        return 29;
    }

    function fillBirthYears(selected) {
        var jyNow = currentJalaliYear();
        var minYear = jyNow - birthRange.maxAge; // قدیمی‌ترین سال مجاز
        var maxYear = jyNow - birthRange.minAge; // جدیدترین سال مجاز

        var opts = '<option value="">انتخاب سال…</option>';
        for (var y = maxYear; y >= minYear; y--) {
            opts += '<option value="' + y + '"' + (selected === y ? ' selected' : '') + '>' + CN.toFaDigits(y) + '</option>';
        }
        var yearSel = document.getElementById('pBirthYear');
        if (yearSel) { yearSel.innerHTML = opts; }
    }

    function fillBirthMonths(selected) {
        var opts = '<option value="">انتخاب ماه…</option>';
        BIRTH_MONTHS.forEach(function (name, i) {
            var m = i + 1;
            opts += '<option value="' + m + '"' + (selected === m ? ' selected' : '') + '>' + name + '</option>';
        });
        var monthSel = document.getElementById('pBirthMonth');
        if (monthSel) { monthSel.innerHTML = opts; }
    }

    function fillBirthDays(selected) {
        var yearSel = document.getElementById('pBirthYear');
        var monthSel = document.getElementById('pBirthMonth');
        var y = parseInt(String((yearSel && yearSel.value) || ''), 10) || 0;
        var m = parseInt(String((monthSel && monthSel.value) || ''), 10) || 0;
        var days = (y && m) ? jalaliMonthDays(y, m) : 31;

        var opts = '<option value="">انتخاب روز…</option>';
        for (var d = 1; d <= days; d++) {
            opts += '<option value="' + d + '"' + (selected === d ? ' selected' : '') + '>' + CN.toFaDigits(d) + '</option>';
        }
        var daySel = document.getElementById('pBirthDay');
        if (daySel) { daySel.innerHTML = opts; }
    }

    /** مقدار نهایی Y/M/D (ارقام انگلیسی) یا '' */
    function birthValue() {
        var yearSel = document.getElementById('pBirthYear');
        var monthSel = document.getElementById('pBirthMonth');
        var daySel = document.getElementById('pBirthDay');
        var y = String((yearSel && yearSel.value) || '');
        var m = String((monthSel && monthSel.value) || '');
        var d = String((daySel && daySel.value) || '');
        if (!y || !m || !d) { return ''; }
        return y + '/' + (m.length < 2 ? '0' + m : m) + '/' + (d.length < 2 ? '0' + d : d);
    }

    /** «۱۳۷۰/۰۵/۱۲» یا «1370/5/12» → انتخاب سه لیست */
    function setBirthFromFa(fa) {
        var raw = CN.toEnDigits(String(fa || '')).trim();
        var m = /^(\d{3,4})[\/.\-](\d{1,2})[\/.\-](\d{1,2})$/.exec(raw);
        if (!m) { return; }

        var y = parseInt(m[1], 10);
        var mo = parseInt(m[2], 10);
        var d = parseInt(m[3], 10);

        fillBirthYears(y);
        fillBirthMonths(mo);
        fillBirthDays(d);

        // اگر مقدار ذخیره‌شده خارج از بازهٔ مجاز است، بازه را گسترش می‌دهیم تا دیده شود
        var yearSel = document.getElementById('pBirthYear');
        if (yearSel && String(yearSel.value || '') !== String(y)) {
            yearSel.insertAdjacentHTML('afterbegin', '<option value="' + y + '" selected>' + CN.toFaDigits(y) + '</option>');
        }
        var monthSel = document.getElementById('pBirthMonth');
        if (monthSel && parseInt(String(monthSel.value || '0'), 10) !== mo) {
            monthSel.value = mo;
        }
        var daySel = document.getElementById('pBirthDay');
        if (daySel && parseInt(String(daySel.value || '0'), 10) !== d) {
            daySel.insertAdjacentHTML('afterbegin', '<option value="' + d + '" selected>' + CN.toFaDigits(d) + '</option>');
        }
    }

    fillBirthYears();
    fillBirthMonths();
    fillBirthDays();

    var birthHidden = document.getElementById('pBirthdate');
    var birthErrorEl = document.getElementById('pBirthdateError');

    Array.prototype.forEach.call(document.querySelectorAll('#pBirthYear, #pBirthMonth'), function (sel) {
        sel.addEventListener('change', function () {
            // با تغییر سال/ماه، روزها بازسازی می‌شود (۳۱/۳۰/۲۹ کبیسه)
            var daySel = document.getElementById('pBirthDay');
            fillBirthDays(parseInt(String((daySel && daySel.value) || ''), 10) || null);
            if (birthHidden) { birthHidden.value = birthValue(); }
            if (birthHidden) { birthHidden.classList.remove('invalid'); }
            if (birthErrorEl) { birthErrorEl.classList.remove('show'); birthErrorEl.textContent = ''; }
        });
    });
    var dayInput = document.getElementById('pBirthDay');
    if (dayInput) {
        dayInput.addEventListener('change', function () {
            if (birthHidden) { birthHidden.value = birthValue(); }
            if (birthHidden) { birthHidden.classList.remove('invalid'); }
            if (birthErrorEl) { birthErrorEl.classList.remove('show'); birthErrorEl.textContent = ''; }
        });
    }

    /* ---------- بارگذاری دادهٔ فرم (v40 — خطا → بنر + تلاش مجدد) ---------- */
    /* باگ گزارش‌شده: «فرم اطلاعات لود نمی‌شود و چندبار رفرش لازم است».
       ریشه: درخواست /me بدون error/timeout بود؛ اگر شبکه قطع یا کند بود
       فرم برای همیشه خالی می‌ماند. اکنون پس از تلاش مجدد خودکارِ CN.api،
       بنر خطا + دکمهٔ «تلاش مجدد» می‌آید. */
    function loadMe() {
        var loadErrorEl = document.getElementById('profileLoadError');
        if (loadErrorEl) { loadErrorEl.classList.add('hidden'); }

        CN.api('/me', {
            timeout: 15000,
            retries: 2,
            success: function (resp) {
                var u = resp.user || {};

                try { window.localStorage.setItem('cn_user', JSON.stringify(u)); } catch (e) { /* noop */ }
                CN.updateAvatar(u);

                var pNameEl = document.getElementById('pName');
                if (pNameEl) { pNameEl.value = u.name || ''; }
                var pFamilyEl = document.getElementById('pFamily');
                if (pFamilyEl) { pFamilyEl.value = u.family || ''; }
                var pNationalIdEl = document.getElementById('pNationalId');
                if (pNationalIdEl) { pNationalIdEl.value = u.national_id || ''; }
                var nidBadge = document.getElementById('pNidVerifiedBadge');
                if (nidBadge) { nidBadge.classList.toggle('hidden', !u.national_id_verified_at); }
                if (u.gender) {
                    var genderInput = document.querySelector('input[name="gender"][value="' + u.gender + '"]');
                    if (genderInput) { genderInput.checked = true; }
                }
                if (u.birthdate_fa) {
                    setBirthFromFa(u.birthdate_fa);
                }

                if (u.province && u.province.id) {
                    selectedProvinceId = u.province.id;
                    loadProvinces(function () {
                        var provinceSel = document.getElementById('pProvince');
                        if (provinceSel) {
                            provinceSel.value = u.province.id;
                            provinceSel.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                } else {
                    loadProvinces();
                }
            },
            error: function (xhr, message) {
                var msgEl = document.getElementById('profileLoadErrorMsg');
                if (msgEl) { msgEl.textContent = message || 'ارتباط با سرور برقرار نشد؛ اینترنت خود را بررسی کنید.'; }
                var errBanner = document.getElementById('profileLoadError');
                if (errBanner) { errBanner.classList.remove('hidden'); }
            }
        });
    }

    document.addEventListener('click', function (e) {
        var retry = e.target.closest ? e.target.closest('#profileLoadRetry') : null;
        if (retry) { loadMe(); }
    });

    loadMe();

    /* ---------- جغرافیا (آبشاری) ---------- */
    function loadProvinces(after) {
        if (provincesLoaded) {
            if (after) { after(); }
            return;
        }

        CN.api('/geo/provinces', {
            timeout: 15000,
            retries: 2,
            success: function (resp) {
                provincesLoaded = true;
                var opts = '<option value="">انتخاب استان…</option>';
                (resp.data || []).forEach(function (p) {
                    opts += '<option value="' + p.id + '">' + CN.esc(p.name) + '</option>';
                });
                var provinceSel = document.getElementById('pProvince');
                if (provinceSel) {
                    provinceSel.innerHTML = opts;
                    provinceSel.disabled = false;
                }
                if (after) { after(); }
            },
            error: function () {
                var provinceSel = document.getElementById('pProvince');
                if (provinceSel) {
                    provinceSel.innerHTML = '<option value="">بارگذاری استان‌ها ناموفق بود</option>';
                    provinceSel.disabled = false;
                }
                var geoErr = document.getElementById('geoLoadError');
                if (geoErr) { geoErr.classList.remove('hidden'); }
            }
        });
    }

    var pProvinceEl = document.getElementById('pProvince');
    if (pProvinceEl) {
        pProvinceEl.addEventListener('change', function () {
            var pid = pProvinceEl.value;
            var citySel = document.getElementById('pCity');
            if (!pid) {
                if (citySel) {
                    citySel.disabled = true;
                    citySel.innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
                }
                return;
            }
            selectedProvinceId = pid;

            if (citySel) {
                citySel.disabled = true;
                citySel.innerHTML = '<option value="">در حال بارگذاری…</option>';
            }

            CN.api('/geo/cities/' + pid, {
                timeout: 15000,
                retries: 2,
                success: function (resp) {
                    var opts = '<option value="">انتخاب شهر…</option>';
                    (resp.data || []).forEach(function (c) {
                        opts += '<option value="' + c.id + '">' + CN.esc(c.name) + '</option>';
                    });
                    var cityEl = document.getElementById('pCity');
                    if (cityEl) {
                        cityEl.innerHTML = opts;
                        cityEl.disabled = false;
                    }

                    // اگر شهر قبلاً ذخیره شده و همین استان است
                    if (selectedProvinceId === pid) {
                        var cached = CN.user();
                        if (cached && cached.city && cached.city.id && cached.province && +cached.province.id === +pid) {
                            if (cityEl) { cityEl.value = cached.city.id; }
                        }
                    }
                }
            });
        });
    }

    /* ---------- اعتبارسنجی زندهٔ فرم ---------- */
    var pNationalIdEl = document.getElementById('pNationalId');
    if (pNationalIdEl) {
        pNationalIdEl.addEventListener('input', function () {
            pNationalIdEl.classList.remove('invalid');
            var errEl = document.getElementById('pNationalIdError');
            if (errEl) { errEl.classList.remove('show'); errEl.textContent = ''; }
            var badgeEl = document.getElementById('pNidVerifiedBadge');
            if (badgeEl) { badgeEl.classList.add('hidden'); } // با ویرایش، وضعیت تأیید باید دوباره بررسی شود
        });
    }
    Array.prototype.forEach.call(document.querySelectorAll('#pName, #pFamily'), function (input) {
        input.addEventListener('input', function () {
            input.classList.remove('invalid');
            var errEl = document.getElementById(input.id + 'Error');
            if (errEl) { errEl.classList.remove('show'); errEl.textContent = ''; }
        });
    });
    Array.prototype.forEach.call(document.querySelectorAll('#pProvince, #pCity'), function (input) {
        input.addEventListener('change', function () {
            input.classList.remove('invalid');
            var errEl = document.getElementById(input.id + 'Error');
            if (errEl) { errEl.classList.remove('show'); errEl.textContent = ''; }
        });
    });
    Array.prototype.forEach.call(document.querySelectorAll('#genderGroup input'), function (radio) {
        radio.addEventListener('change', function () {
            var errEl = document.getElementById('genderError');
            if (errEl) { errEl.classList.remove('show'); errEl.textContent = ''; }
        });
    });

    /* ---------- ذخیره ---------- */
    if (document.getElementById('profileForm')) {
        document.getElementById('profileForm').addEventListener('submit', function (e) {
            e.preventDefault();
            CN.clearFieldErrors(document.getElementById('profileForm'));

            var pNameEl = document.getElementById('pName');
            var pFamilyEl = document.getElementById('pFamily');
            var name = String(pNameEl ? pNameEl.value : '').trim();
            var family = String(pFamilyEl ? pFamilyEl.value : '').trim();
            var genderChecked = document.querySelector('input[name="gender"]:checked');
            var gender = genderChecked ? genderChecked.value : '';
            var provinceId = (pProvinceEl && pProvinceEl.value) || '';
            var pCityEl = document.getElementById('pCity');
            var cityId = (pCityEl && pCityEl.value) || '';
            var birthdate = birthValue();
            var nationalId = CN.toEnDigits((pNationalIdEl && pNationalIdEl.value) || '').trim();

            var valid = true;

            if (name.length < 2) { CN.fieldError('pName', 'نام را وارد کنید (حداقل ۲ حرف).'); valid = false; }
            if (family.length < 2) { CN.fieldError('pFamily', 'نام‌خانوادگی را وارد کنید (حداقل ۲ حرف).'); valid = false; }
            if (!gender) { CN.fieldError('gender', 'جنسیت را انتخاب کنید.'); valid = false; }

            /* v40 — کد ملی */
            if (nationalId) {
                if (!/^\d{10}$/.test(nationalId)) {
                    CN.fieldError('pNationalId', 'کد ملی باید دقیقاً ۱۰ رقم باشد.'); valid = false;
                } else if (!isValidNationalId(nationalId)) {
                    CN.fieldError('pNationalId', 'کد ملی واردشده معتبر نیست؛ رقم آخر (رقم کنترل) نمی‌خورد.'); valid = false;
                }
            } else if (nidRequired) {
                CN.fieldError('pNationalId', 'کد ملی برای احراز هویت الزامی است.'); valid = false;
            }
            if (!provinceId) { CN.fieldError('pProvince', 'استان را انتخاب کنید.'); valid = false; }
            if (!cityId) { CN.fieldError('pCity', 'شهر را انتخاب کنید.'); valid = false; }
            if (!birthdate) {
                CN.fieldError('pBirthdate', 'سال، ماه و روز تولدتان را انتخاب کنید.');
                valid = false;
            } else {
                // روز انتخابی نباید از طول واقعی ماه بیشتر باشد (کبیسه)
                var by = parseInt(birthdate.split('/')[0], 10);
                var bm = parseInt(birthdate.split('/')[1], 10);
                var bd = parseInt(birthdate.split('/')[2], 10);
                if (bd > jalaliMonthDays(by, bm)) {
                    CN.fieldError('pBirthdate', 'روز انتخابی با ماه سازگار نیست؛ دوباره انتخاب کنید.');
                    valid = false;
                }
            }

            if (!valid) {
                CN.toast('لطفاً فیلدهای الزامی را کامل کنید.', 'error');
                return;
            }

            CN.btnLoading(document.getElementById('saveProfileBtn'), true, 'در حال ذخیره…');

            CN.api('/profile/complete', {
                method: 'POST',
                data: {
                    name: name,
                    family: family,
                    gender: gender,
                    province_id: +provinceId,
                    city_id: +cityId,
                    birthdate: birthdate,
                    national_id: nationalId || null
                },
                success: function (resp) {
                    CN.btnLoading(document.getElementById('saveProfileBtn'), false);
                    CN.updateAvatar(resp.user);

                    // به‌روزرسانی کش کاربر → گارد requireCompleteProfile از همین لحظه پاس می‌شود
                    try { window.localStorage.setItem('cn_user', JSON.stringify(resp.user)); } catch (e) { /* noop */ }

                    CN.toast(resp.message || 'اطلاعات با موفقیت ذخیره شد.', 'success');

                    var redirectTo;
                    if (isNewUser) {
                        // اولین ورود → شروع استفاده از اپ
                        redirectTo = '/app/home';
                    } else {
                        // ویرایش عادی → بازگشت به نمای پروفایل (اطلاعات به‌روز)
                        redirectTo = '/app/profile';
                    }
                    window.setTimeout(function () {
                        window.location.replace(CN.withPort(redirectTo));
                    }, isNewUser ? 900 : 700);
                },
                error: function (xhr, message) {
                    CN.btnLoading(document.getElementById('saveProfileBtn'), false);
                    var respData = null;
                    try { respData = JSON.parse(xhr.responseText); } catch (parseErr) { respData = null; }
                    var errors = (respData && respData.errors) || {};

                    var map = {
                        name: 'pName', family: 'pFamily', gender: 'gender',
                        province_id: 'pProvince', city_id: 'pCity', birthdate: 'pBirthdate',
                        national_id: 'pNationalId'
                    };
                    Object.keys(errors).forEach(function (key) {
                        var el = map[key];
                        if (el && errors[key] && errors[key].length) {
                            CN.fieldError(el, errors[key][0]);
                        }
                    });
                    CN.toast(message, 'error');
                }
            });
        });
    }
})();
