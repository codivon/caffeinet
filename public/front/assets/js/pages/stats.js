/* اپ مشتری — آمار من (فاز ۵۴) */
/* Vanilla JS — CN.api → /api/v1/me/stats + Chart.js */
/* global CN, Chart */
(function () {
    'use strict';

    if (!CN.requireAuth()) { return; }

    var chart = null;

    function $(id) { return document.getElementById(id); }

    function money(v) { return CN.faMoney ? CN.faMoney(v) : CN.toFaDigits(String(Math.round(v))); }

    function load() {
        CN.api('/me/stats', {
            success: function (resp) {
                var d = resp.data || {};

                var totalEl = $('stTotal');
                if (totalEl) { totalEl.textContent = money(d.total_spend || 0); }

                var ordersEl = $('stOrders');
                if (ordersEl) { ordersEl.textContent = CN.toFaDigits(String(d.orders_count || 0)) + ' سفارش پرداخت‌شده'; }

                var avgEl = $('stAvg');
                if (avgEl) { avgEl.textContent = d.orders_count ? money(d.avg_order) : '—'; }

                var delEl = $('stDelivery');
                if (delEl) { delEl.textContent = d.avg_delivery_minutes != null ? CN.toFaDigits(String(Math.round(d.avg_delivery_minutes))) : '—'; }

                renderChart(d.months || []);
                renderTop(d.top_services || []);
            },
            error: function (xhr, msg) {
                CN.toast(msg || 'خطا در دریافت آمار', 'error');
            }
        });
    }

    function renderChart(months) {
        var canvas = $('stChart');
        var empty = $('stChartEmpty');
        if (!canvas) { return; }

        var hasData = months.some(function (m) { return (m.sum || 0) > 0 || (m.count || 0) > 0; });

        if (!hasData) {
            canvas.parentNode.style.display = 'none';
            if (empty) { empty.classList.remove('hidden'); }
            return;
        }

        if (empty) { empty.classList.add('hidden'); }
        if (typeof Chart === 'undefined') { return; } // vendor هنوز نرسیده — بعد از رفرش می‌آید

        var styles = getComputedStyle(document.documentElement);
        var brand = styles.getPropertyValue('--brand-600').trim() || '#2563eb';
        var ink = styles.getPropertyValue('--ink').trim() || '#334155';
        var line = styles.getPropertyValue('--line').trim() || '#e2e8f0';

        var isDark = document.documentElement.classList.contains('dark');
        var gridColor = isDark ? 'rgba(255,255,255,.08)' : line;

        if (chart) { chart.destroy(); }

        chart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: months.map(function (m) { return m.label; }),
                datasets: [{
                    label: 'هزینه (تومان)',
                    data: months.map(function (m) { return m.sum || 0; }),
                    backgroundColor: brand,
                    borderRadius: 8,
                    maxBarThickness: 42,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return money(ctx.parsed.y) + ' تومان · ' + CN.toFaDigits(String(months[ctx.dataIndex].count || 0)) + ' سفارش';
                            }
                        }
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: ink, font: { size: 10 } } },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            color: ink, font: { size: 10 },
                            callback: function (v) {
                                return v >= 1000000 ? CN.toFaDigits(String(Math.round(v / 100000) / 10)) + 'م' : (v >= 1000 ? CN.toFaDigits(String(Math.round(v / 1000))) + 'هـ' : CN.toFaDigits(String(v)));
                            }
                        }
                    },
                },
            },
        });
    }

    function renderTop(services) {
        var box = $('stTop');
        var empty = $('stTopEmpty');
        if (!box) { return; }

        box.innerHTML = '';

        if (!services.length) {
            if (empty) { empty.classList.remove('hidden'); }
            return;
        }

        if (empty) { empty.classList.add('hidden'); }

        var max = Math.max.apply(null, services.map(function (s) { return s.count || 1; }));

        services.forEach(function (s, i) {
            var el = document.createElement('div');
            el.style.cssText = 'padding:10px 12px;border-bottom:1px dashed var(--line)' + (i === services.length - 1 ? ';border-bottom:none' : '');
            el.innerHTML =
                '<div style="display:flex;align-items:center;gap:8px">' +
                    '<span style="display:grid;place-items:center;width:26px;height:26px;border-radius:9px;background:var(--brand-100);color:var(--brand-700);font-weight:800;font-size:12px;flex-shrink:0">' + CN.toFaDigits(String(i + 1)) + '</span>' +
                    '<div style="flex:1;min-width:0">' +
                        '<b style="font-size:12.5px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + CN.esc(s.name) + '</b>' +
                        '<div style="height:5px;border-radius:99px;background:var(--line);margin-top:6px;overflow:hidden">' +
                            '<div style="height:100%;width:' + Math.round(((s.count || 0) / max) * 100) + '%;background:var(--brand-600);border-radius:99px"></div>' +
                        '</div>' +
                    '</div>' +
                    '<span style="font-size:11px;font-weight:800;color:var(--ink);white-space:nowrap">' + CN.toFaDigits(String(s.count)) + ' بار · ' + money(s.sum || 0) + '</span>' +
                '</div>';
            box.appendChild(el);
        });
    }

    document.addEventListener('DOMContentLoaded', load);
})();
