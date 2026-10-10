/* کافی‌نت آنلاین — پالت جستجوی سراسری (Ctrl+K / ⌘+K) — فاز ۵۴ */
/* global App, PanelUI */
(function () {
    'use strict';

    if (window.__GK_BOUND) { return; }
    window.__GK_BOUND = true;

    var backdrop = null;
    var input = null;
    var body = null;
    var items = [];       // [{el, url}]
    var active = 0;
    var debounce = null;
    var lastQuery = '';

    /* ---------- ساخت DOM یک‌بار ---------- */
    function ensureDom() {
        if (backdrop) { return; }

        backdrop = document.createElement('div');
        backdrop.className = 'gk-backdrop';
        backdrop.id = 'gk-backdrop';
        backdrop.innerHTML =
            '<div class="gk-panel" role="dialog" aria-modal="true" aria-label="جستجوی سراسری">' +
                '<div class="gk-head">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>' +
                    '<input type="text" class="gk-input" id="gk-input" placeholder="جستجو: سفارش، مشتری، خدمت، صفحه…" autocomplete="off">' +
                    '<span class="gk-esc">Esc</span>' +
                '</div>' +
                '<div class="gk-body" id="gk-body"><div class="gk-empty">دو حرف تایپ کنید…</div></div>' +
                '<div class="gk-hint">' +
                    '<span><span class="gk-kbd">↑↓</span> حرکت</span>' +
                    '<span><span class="gk-kbd">Enter</span> بازکردن</span>' +
                    '<span><span class="gk-kbd">Esc</span> بستن</span>' +
                '</div>' +
            '</div>';

        document.body.appendChild(backdrop);

        input = backdrop.querySelector('#gk-input');
        body = backdrop.querySelector('#gk-body');

        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) { close(); }
        });

        input.addEventListener('input', function () {
            var q = input.value.trim();
            if (debounce) { window.clearTimeout(debounce); }

            if (q.length < 2) {
                lastQuery = '';
                body.innerHTML = '<div class="gk-empty">دو حرف تایپ کنید…</div>';
                items = [];
                return;
            }

            if (q === lastQuery) { return; }
            debounce = window.setTimeout(function () { search(q); }, 260);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                if (items[active]) { window.location.href = items[active].url; }
            }
        });

        bindOpeners();
    }

    function bindOpeners() {
        ['gk-open', 'gk-open-m'].forEach(function (id) {
            var btn = document.getElementById(id);
            if (btn && !btn.dataset.gkBound) {
                btn.dataset.gkBound = '1';
                btn.addEventListener('click', open);
            }
        });
    }

    /* ---------- باز/بسته ---------- */
    function open() {
        ensureDom();
        backdrop.classList.add('is-open');
        input.value = '';
        lastQuery = '';
        items = [];
        body.innerHTML = '<div class="gk-empty">جستجو در سفارش‌ها، مشتریان، خدمات، کافی‌نت‌ها و صفحه‌ها…</div>';
        window.setTimeout(function () { input.focus(); }, 30);
    }

    function close() {
        if (!backdrop) { return; }
        backdrop.classList.remove('is-open');
        if (input) { input.blur(); }
    }

    /* ---------- جستجو ---------- */
    function search(q) {
        lastQuery = q;
        body.innerHTML = '<div class="gk-empty">در حال جستجو…</div>';

        fetch('/admin/search?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                if (input.value.trim() !== q) { return; } // کوئری عوض شده
                render(resp.data && resp.data.groups ? resp.data.groups : []);
            })
            .catch(function () {
                body.innerHTML = '<div class="gk-empty">خطا در جستجو — دوباره تلاش کنید.</div>';
            });
    }

    var ICONS = {
        orders: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        customers: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
        services: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>',
        coffeenets: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
        pages: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="8" x="5" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 18h.01"/><path d="M10 18h.01"/></svg>',
    };

    function render(groups) {
        items = [];
        active = 0;
        body.innerHTML = '';

        if (!groups.length) {
            body.innerHTML = '<div class="gk-empty">نتیجه‌ای یافت نشد.</div>';
            return;
        }

        groups.forEach(function (g) {
            var label = document.createElement('div');
            label.className = 'gk-group-label';
            label.textContent = g.label;
            body.appendChild(label);

            g.items.forEach(function (item) {
                var a = document.createElement('a');
                a.className = 'gk-item';
                a.href = item.url;
                a.innerHTML =
                    '<span class="gk-item-ico" aria-hidden="true">' + (ICONS[item.type] || ICONS.pages) + '</span>' +
                    '<span class="gk-item-body"><span class="gk-item-title">' + esc(item.title) + '</span><span class="gk-item-sub">' + esc(item.sub || '') + '</span></span>' +
                    '<span class="gk-item-enter"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>';

                a.addEventListener('mouseenter', function () { setActive(items.findIndex(function (it) { return it.el === a; })); });

                items.push({ el: a, url: item.url });
                body.appendChild(a);
            });
        });

        setActive(0);
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function setActive(i) {
        if (!items.length) { return; }
        active = (i + items.length) % items.length;
        items.forEach(function (it, idx) { it.el.classList.toggle('is-active', idx === active); });
        var el = items[active].el;
        if (el.scrollIntoView) { el.scrollIntoView({ block: 'nearest' }); }
    }

    function move(dir) { setActive(active + dir); }

    /* ---------- کلید میان‌بر جهانی ---------- */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            if (backdrop && backdrop.classList.contains('is-open')) { close(); } else { open(); }
        } else if (e.key === 'Escape' && backdrop && backdrop.classList.contains('is-open')) {
            close();
        }
    });

    /* re-bind دکمه‌های هدر بعد از هر ناوبری SPA */
    document.addEventListener('DOMContentLoaded', bindOpeners);
    if (window.App && App.onNavigate) { App.onNavigate(bindOpeners); }
})();
