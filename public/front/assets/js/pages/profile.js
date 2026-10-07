/* اپ مشتری — پروفایل (نمای کاربر — v24: فرم ویرایش به صفحهٔ جدا منتقل شد) */
/* [Task 9] Vanilla JS — بدون جی‌کوئری */
/* global CN */
(function () {
    'use strict';

    if (!CN.requireAuth()) { return; }

    /* ---------- بارگذاری پروفایل + آمار (v40 — مهلت/تلاش‌مجدد مرکزی CN.api) ---------- */
    CN.api('/me', {
        timeout: 15000,
        retries: 2,
        success: function (resp) {
            var u = resp.user || {};
            var stats = resp.orders_stats || {};

            try { window.localStorage.setItem('cn_user', JSON.stringify(u)); } catch (e) { /* noop */ }
            CN.updateAvatar(u);

            /* هرو */
            var profileAvatar = document.getElementById('profileAvatar');
            if (profileAvatar) { profileAvatar.textContent = initials(u); }

            var profileName = document.getElementById('profileName');
            if (profileName) { profileName.textContent = u.full_name || 'کاربر مهمان'; }

            var profileMobile = document.getElementById('profileMobile');
            if (profileMobile) { profileMobile.textContent = u.mobile || '—'; }

            var profileSinceText = document.getElementById('profileSinceText');
            if (profileSinceText) { profileSinceText.textContent = u.member_since_fa ? 'عضو از ' + u.member_since_fa : 'عضو جدید'; }

            var profileCompleteBadge = document.getElementById('profileCompleteBadge');
            if (profileCompleteBadge) { profileCompleteBadge.classList.toggle('hidden', !u.profile_completed); }

            var profileBalance = document.getElementById('profileBalance');
            if (profileBalance) {
                profileBalance.textContent = u.wallet_balance !== undefined && u.wallet_balance !== null
                    ? CN.faMoneyUnit(u.wallet_balance) : '—';
            }

            /* هشدار پروفایل ناقص + پرچم لینک ویرایش */
            var pfIncompleteBanner = document.getElementById('pfIncompleteBanner');
            if (pfIncompleteBanner) { pfIncompleteBanner.classList.toggle('hidden', !!u.profile_completed); }

            var pfEditFlag = document.getElementById('pfEditFlag');
            if (pfEditFlag) { pfEditFlag.classList.toggle('hidden', !!u.profile_completed); }

            var pfEditHint = document.getElementById('pfEditHint');
            if (pfEditHint) { pfEditHint.textContent = u.profile_completed ? 'مشاهده و اصلاح اطلاعات شخصی' : 'برای استفاده از خدمات، تکمیل کنید'; }

            /* آمار سفارش‌ها */
            var statTotal = document.getElementById('statTotal');
            if (statTotal) { statTotal.textContent = CN.toFaDigits(stats.total || 0); }

            var statActive = document.getElementById('statActive');
            if (statActive) { statActive.textContent = CN.toFaDigits(stats.active || 0); }

            var statCompleted = document.getElementById('statCompleted');
            if (statCompleted) { statCompleted.textContent = CN.toFaDigits(stats.completed || 0); }

            var statCancelled = document.getElementById('statCancelled');
            if (statCancelled) { statCancelled.textContent = CN.toFaDigits(stats.cancelled || 0); }
        }
    });

    function initials(u) {
        if (u && u.name && u.family) {
            return (u.name.trim().charAt(0) || '؟') + (u.family.trim().charAt(0) || '');
        }
        return '؟';
    }

    /* ---------- خروج ---------- */
    var logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            CN.confirm({
                icon: '🚪',
                title: 'خروج از حساب',
                desc: 'برای ورود مجدد به کد پیامکی نیاز دارید.',
                okText: 'خروج',
                danger: true
            }, CN.logout);
        });
    }
})();
