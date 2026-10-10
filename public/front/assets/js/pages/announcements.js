/**
 * کافی‌نت آنلاین — اپ مشتری: مودال اطلاعیه‌های سامانه (فاز ۱۵)
 * اطلاعیه‌های متن/تصویر/ویدیو را از API می‌گیرد و به‌صورت صف نمایش می‌دهد.
 */
/* [Task 9] Vanilla JS — بدون جی‌کوئری */
/* global CN */
(function () {
    'use strict';

    if (!CN.token()) { return; } // فقط کاربران واردشده

    var queue = [];
    var active = null;

    var MEGAPHONE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>';

    function show(item) {
        active = item;

        var mediaHtml = '';
        if (item.media_type === 'image' && item.media_url) {
            mediaHtml = '<div class="ann-media"><img src="' + CN.esc(item.media_url) + '" alt="رسانه اطلاعیه"></div>';
        } else if (item.media_type === 'video' && item.video_url) {
            mediaHtml = '<div class="ann-media"><video src="' + CN.esc(item.video_url) + '" controls playsinline preload="metadata"></video></div>';
        }

        var textHtml = item.body ? '<p class="ann-text">' + CN.esc(item.body) + '</p>' : '';

        var remaining = queue.length;

        var html =
            '<div class="ann-backdrop" role="dialog" aria-modal="true" aria-labelledby="ann-title">' +
            '  <div class="ann-card">' +
            '    <div class="ann-head">' +
            '      <span class="ann-icon">' + MEGAPHONE + '</span>' +
            '      <div class="ann-head-text min-w-0">' +
            '        <span class="ann-kicker">📢 اطلاعیه</span>' +
            '        <h2 class="ann-title" id="ann-title">' + CN.esc(item.title) + '</h2>' +
            '        <p class="ann-date">' + CN.esc(item.created_at_label || '') + '</p>' +
            '      </div>' +
            '    </div>' +
            '    <div class="ann-body">' + mediaHtml + textHtml + '</div>' +
            '    <div class="ann-foot">' +
            (remaining > 1 ? '<span class="ann-count">' + CN.toFaDigits(remaining) + ' اطلاعیه دیگر</span>' : '<span class="ann-count"></span>') +
            '      <button type="button" class="btn btn-primary ann-ok">متوجه شدم</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        /* [Task 9] ساخت عنصر از رشتهٔ HTML و افزودن به body (جایگزین builder قدیمی) */
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        var wrap = tmp.firstElementChild;
        document.body.appendChild(wrap);

        function close() {
            Array.prototype.forEach.call(wrap.querySelectorAll('video'), function (video) {
                try { video.pause(); } catch (e) { /* noop */ }
            });
            wrap.classList.add('closing');
            markRead(item.id);
            window.setTimeout(function () {
                wrap.remove();
                active = null;
                next();
            }, 240);
        }

        var okBtn = wrap.querySelector('.ann-ok');
        if (okBtn) { okBtn.addEventListener('click', close); }
        wrap.addEventListener('click', function (e) {
            if (e.target === wrap) { close(); }
        });
    }

    function next() {
        if (queue.length) { show(queue.shift()); }
    }

    function markRead(id) {
        CN.api('/announcements/' + id + '/read', { method: 'POST' });
    }

    /* بارگذاری اطلاعیه‌ها هنگام ورود به صفحه
       [Task 9] آماده‌سازی قدیمی روی ready بود → اسکریپت با defer در انتهای body است؛
       اجرای فوری عین آماده‌بودن DOM */
    window.setTimeout(function () {
        CN.api('/announcements', {
            success: function (resp) {
                var items = (resp.data || []);
                if (!items.length) { return; }
                items.reverse(); // قدیمی → جدید
                queue = items;
                next();
            }
        });
    }, 800);
})();
