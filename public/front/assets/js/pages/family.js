/* اپ مشتری — حساب خانواده (فاز ۵۳) */
/* Vanilla JS — CN.api → /api/v1/family */
/* global CN */
(function () {
    'use strict';

    if (!CN.requireAuth()) { return; }

    var members = [];
    var editing = null; // عضو در حال ویرایش سقف
    var state = { loading: false };

    /* ---------- ابزار ---------- */
    function $(id) { return document.getElementById(id); }

    function money(v) {
        return CN.faMoney ? CN.faMoney(v) : CN.toFaDigits(String(Math.round(v)).replace(/\B(?=(\d{3})+(?!\d))/g, '٬'));
    }

    function err(el, msg) {
        if (!el) { return; }
        el.textContent = msg || '';
        el.classList.toggle('hidden', !msg);
    }

    /* ---------- بارگذاری ---------- */
    function load() {
        var list = $('famList');
        if (list && state.loading === false) {
            list.innerHTML = '<div class="skeleton" style="height:64px"></div><div class="skeleton" style="height:64px"></div>';
        }

        CN.api('/family', {
            success: function (resp) {
                var d = resp.data || {};

                var balEl = $('famBalance');
                if (balEl) { balEl.textContent = money(d.balance || 0); }

                members = d.members || [];
                render();
            },
            error: function (xhr, msg) {
                if (list) { list.innerHTML = '<p class="text-faint tiny" style="padding:14px;text-align:center">'+CN.esc(msg||'خطا در دریافت اطلاعات')+'</p>'; }
            }
        });
    }

    /* ---------- رندر ---------- */
    function render() {
        var list = $('famList');
        var empty = $('famEmpty');
        var addBtn = $('famAddBtn');
        var parentCard = $('famParentCard');

        if (!list) { return; }

        /* زیرحساب؟ — فقط نمایش حساب والد */
        if (members && members.__is_child) { /* placeholder — API زیرحساب 403 می‌دهد */ }

        list.innerHTML = '';

        if (!members.length) {
            list.appendChild(empty || document.createElement('div'));
            if (empty) { empty.classList.remove('hidden'); }
            if (addBtn) { addBtn.style.display = 'none'; }
            return;
        }

        if (empty) { empty.classList.add('hidden'); }
        if (addBtn) { addBtn.style.display = ''; }

        members.forEach(function (m) {
            list.appendChild(memberCard(m));
        });
    }

    function memberCard(m) {
        var el = document.createElement('div');
        el.className = 'tx-row';
        el.innerHTML =
            '<span class="tx-icon" style="background:var(--brand-100);color:var(--brand-700);font-weight:800">'+CN.esc(m.initial || 'ع')+'</span>' +
            '<span class="tx-body">' +
                '<b>' + CN.esc(m.name) + '</b>' +
                '<i dir="ltr">' + CN.esc(m.mobile || '') + '</i>' +
                '<small>' + (m.blocked ? '<span style="color:var(--err);font-weight:800">خرج متوقف</span> · ' : '') +
                    'امروز: ' + money(m.spend_today || 0) + ' · این ماه: ' + money(m.spend_month || 0) + ' تومان' +
                    (m.daily_limit != null ? ' · سقف روز: ' + money(m.daily_limit) : '') +
                    (m.monthly_limit != null ? ' · سقف ماه: ' + money(m.monthly_limit) : '') +
                '</small>' +
            '</span>' +
            '<span class="tx-amount" style="display:flex;gap:6px;align-items:center">' +
                '<button type="button" data-act="limit" class="btn btn-outline" style="padding:6px 10px;font-size:11px">سقف‌ها</button>' +
                '<button type="button" data-act="del" class="btn btn-ghost" style="padding:6px 10px;font-size:11px;color:var(--err)">حذف</button>' +
            '</span>';

        el.querySelector('[data-act=limit]').addEventListener('click', function () { openLimit(m); });
        el.querySelector('[data-act=del]').addEventListener('click', function () { removeMember(m); });

        return el;
    }

    /* ---------- ساخت عضو ---------- */
    function openSheet(sheetId) {
        var s = $(sheetId);
        if (!s) { return; }
        s.classList.remove('hidden');
        var bd = s.querySelector('.sheet-backdrop');
        if (bd) { bd.classList.add('show'); }
        var sh = s.querySelector('.sheet');
        if (sh) { sh.classList.add('show'); }
    }

    function closeSheet(sheetId) {
        var s = $(sheetId);
        if (!s) { return; }
        var bd = s.querySelector('.sheet-backdrop');
        if (bd) { bd.classList.remove('show'); }
        var sh = s.querySelector('.sheet');
        if (sh) { sh.classList.remove('show'); }
        window.setTimeout(function () { s.classList.add('hidden'); }, 220);
    }

    function saveMember() {
        var name = ($('famName') || {}).value || '';
        var family = ($('famFamily') || {}).value || '';
        var mobile = ($('famMobile') || {}).value || '';

        err($('famErr'), '');

        if (name.trim().length < 2) { return err($('famErr'), 'نام عضو را وارد کنید.'); }
        if (!/^09\d{9}$/.test(mobile.trim())) { return err($('famErr'), 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.'); }

        var btn = $('famSaveBtn');
        CN.btnLoading && CN.btnLoading(btn, true);

        CN.api('/family', {
            method: 'POST',
            data: { name: name.trim(), family: family.trim(), mobile: mobile.trim() },
            success: function (resp) {
                CN.btnLoading && CN.btnLoading(btn, false);
                CN.toast(resp.message || 'عضو ساخته شد.', 'success');
                closeSheet('famSheet');
                ['famName', 'famFamily', 'famMobile'].forEach(function (i) { var e = $(i); if (e) { e.value = ''; } });
                load();
            },
            error: function (xhr, msg) {
                CN.btnLoading && CN.btnLoading(btn, false);
                err($('famErr'), msg || 'خطا در ساخت عضو.');
            }
        });
    }

    /* ---------- سقف‌ها ---------- */
    function openLimit(m) {
        editing = m;
        var who = $('famLimitWho');
        if (who) { who.textContent = m.name + ' (' + m.mobile + ')'; }
        var d = $('famDaily'), mo = $('famMonthly'), b = $('famBlocked');
        if (d) { d.value = m.daily_limit != null ? m.daily_limit : ''; }
        if (mo) { mo.value = m.monthly_limit != null ? m.monthly_limit : ''; }
        if (b) { b.checked = !!m.blocked; }
        err($('famLimitErr'), '');
        openSheet('famLimitSheet');
    }

    function saveLimit() {
        if (!editing) { return; }
        err($('famLimitErr'), '');

        var d = ($('famDaily') || {}).value || '';
        var mo = ($('famMonthly') || {}).value || '';
        var b = ($('famBlocked') || {}).checked;

        if (d !== '' && Number(d) < 0) { return err($('famLimitErr'), 'سقف روزانه نمی‌تواند منفی باشد.'); }
        if (mo !== '' && Number(mo) < 0) { return err($('famLimitErr'), 'سقف ماهانه نمی‌تواند منفی باشد.'); }

        var btn = $('famLimitSave');
        CN.btnLoading && CN.btnLoading(btn, true);

        CN.api('/family/' + editing.id, {
            method: 'PATCH',
            data: {
                wallet_daily_limit: d === '' ? null : Number(d),
                wallet_monthly_limit: mo === '' ? null : Number(mo),
                family_blocked: b,
            },
            success: function (resp) {
                CN.btnLoading && CN.btnLoading(btn, false);
                CN.toast(resp.message || 'ذخیره شد.', 'success');
                closeSheet('famLimitSheet');
                load();
            },
            error: function (xhr, msg) {
                CN.btnLoading && CN.btnLoading(btn, false);
                err($('famLimitErr'), msg || 'خطا در ذخیره.');
            }
        });
    }

    /* ---------- حذف ---------- */
    function removeMember(m) {
        CN.confirm({
            title: 'حذف عضو؟',
            desc: '«' + m.name + '» از خانواده حذف می‌شود و دیگر نمی‌تواند وارد اپ شود.',
            okText: 'بله، حذف شود',
            danger: true,
        }, function () {
            CN.api('/family/' + m.id, {
                method: 'DELETE',
                success: function (resp) {
                    CN.toast(resp.message || 'حذف شد.', 'success');
                    load();
                },
                error: function (xhr, msg) { CN.toast(msg || 'خطا در حذف.', 'error'); }
            });
        });
    }

    /* ---------- اتصال ---------- */
    document.addEventListener('DOMContentLoaded', function () {
        var add = $('famAddBtn');
        if (add) { add.addEventListener('click', function () { openSheet('famSheet'); }); }

        var save = $('famSaveBtn');
        if (save) { save.addEventListener('click', saveMember); }

        var limitSave = $('famLimitSave');
        if (limitSave) { limitSave.addEventListener('click', saveLimit); }

        document.querySelectorAll('[data-fam-close]').forEach(function (el) {
            el.addEventListener('click', function () { closeSheet('famSheet'); });
        });
        document.querySelectorAll('[data-limit-close]').forEach(function (el) {
            el.addEventListener('click', function () { closeSheet('famLimitSheet'); });
        });

        load();
    });
})();
