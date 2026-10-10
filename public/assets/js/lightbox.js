/**
 * کافی‌نت آنلاین — لایت‌باکس تمام‌صفحهٔ تصاویر (v45)
 * مشترک همهٔ پنل‌ها — بدون وابستگی / بدون بیلد
 *
 * درخواست مالک: «تصاویر هم روش کلیک میشه تو همون صفحه باز بشه فول اسکرین
 * و دکمهٔ بستن یا X برای بستنش باشه.»
 *
 * با کلیک روی تصاویر پیوست گفتگو (tk-thumb / tkd-thumb / msg-image) یا هر
 * عنصر [data-lightbox]، تصویر در همان صفحه تمام‌صفحه باز می‌شود؛ بستن با
 * دکمهٔ X، کلیک روی پس‌زمینه یا کلید Esc.
 * رویداد delegate است → روی محتوای JS-rندرشده (پیام‌های چت) هم کار می‌کند
 * و بعد از ناوبری wire:navigate زنده می‌ماند (شنونده روی document).
 */
(function () {
    'use strict';

    var SELECTOR = 'img.tk-thumb, img.tkd-thumb, a.msg-image, [data-lightbox]';

    var lb = null;

    function ensure() {
        /* بعد از سواپ body در wire:navigate ممکن است حذف شود — هر بار چک شود */
        if (lb && lb.isConnected) { return lb; }

        lb = document.createElement('div');
        lb.className = 'cn-lightbox';
        lb.setAttribute('role', 'dialog');
        lb.setAttribute('aria-modal', 'true');
        lb.setAttribute('aria-label', 'مشاهدهٔ تصویر');
        lb.innerHTML =
            '<button type="button" class="cn-lb-x" aria-label="بستن" title="بستن">' +
            '  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>' +
            '</button>' +
            '<img class="cn-lb-img" alt="">';
        (document.documentElement || document.body).appendChild(lb);
        return lb;
    }

    function open(src, alt) {
        if (!src) { return; }
        var el = ensure();
        var img = el.querySelector('.cn-lb-img');
        if (!img) { return; }
        img.src = src;
        img.alt = alt || 'تصویر';
        el.classList.add('open');
        document.documentElement.classList.add('cn-lb-lock');
    }

    function close() {
        if (!lb) { return; }
        lb.classList.remove('open');
        document.documentElement.classList.remove('cn-lb-lock');
        /* بعد از پایان انیمیشن، src آزاد شود */
        window.setTimeout(function () {
            if (lb && !lb.classList.contains('open')) {
                var img = lb.querySelector('.cn-lb-img');
                if (img) { img.removeAttribute('src'); }
            }
        }, 220);
    }

    /* باز کردن — delegate روی document (capture تا جلوی <a target=_blank> والد هم گرفته شود) */
    document.addEventListener('click', function (e) {
        var t = e.target && e.target.closest ? e.target.closest(SELECTOR) : null;
        if (!t) { return; }

        e.preventDefault();
        e.stopPropagation();

        var src = '', alt = '';
        if (t.tagName === 'IMG') {
            src = t.currentSrc || t.getAttribute('src') || '';
            alt = t.alt || '';
        } else {
            var img = t.querySelector('img');
            src = img ? (img.currentSrc || img.getAttribute('src') || '') : (t.getAttribute('href') || '');
            alt = img ? img.alt : '';
        }
        open(src, alt);
    }, true);

    /* بستن — کلیک روی هر نقطهٔ لایت‌باکس (X یا پس‌زمینه یا خود تصویر) */
    document.addEventListener('click', function (e) {
        if (!lb || !lb.classList.contains('open')) { return; }
        if (e.target === lb || (e.target.closest && e.target.closest('.cn-lightbox'))) {
            e.preventDefault();
            e.stopPropagation();
            close();
        }
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lb && lb.classList.contains('open')) { close(); }
    });

    /* پایان ناوبری SPA — اگر باز بود ببند (بعد از سواپ می‌ماند چون به <html> می‌چسبد) */
    document.addEventListener('livewire:navigated', function () {
        if (lb && lb.classList.contains('open')) { close(); }
    });
})();
