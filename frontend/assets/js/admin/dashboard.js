(function () {
    'use strict';

    var BRAND = '#8b0a72';
    var INK = '#766b86';
    var PALETTE = ['#8b0a72', '#b72c9b', '#d95ebf', '#ec92da', '#12805c', '#1d4ed8', '#b45309'];

    function baseChartOptions() {
        return {
            chart: {
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            dataLabels: { enabled: false },
            legend: { fontSize: '13px', labels: { colors: INK } },
            grid: { borderColor: '#f2eff6', strokeDashArray: 4 },
            tooltip: { theme: 'light' }
        };
    }

    function areaChart(el, categories, series, colors) {
        if (!el || !window.ApexCharts) {
            return null;
        }
        var chart = new ApexCharts(el, Object.assign(baseChartOptions(), {
            chart: { type: 'area', height: 300, fontFamily: 'Inter, sans-serif', toolbar: { show: false } },
            series: series,
            xaxis: { categories: categories, labels: { style: { colors: INK } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { style: { colors: INK }, formatter: function (value) { return window.APP.money((Number(value) || 0) * 100); } } },
            colors: colors || [BRAND],
            stroke: { curve: 'smooth', width: 3 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 0.35, opacityFrom: 0.35, opacityTo: 0.03, stops: [0, 90, 100] } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#f2eff6', strokeDashArray: 4, padding: { left: 8, right: 8 } }
        }));
        chart.render();
        return chart;
    }

    function donutChart(el, labels, values) {
        if (!el || !window.ApexCharts) {
            return null;
        }
        var chart = new ApexCharts(el, {
            chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
            series: values,
            labels: labels,
            colors: PALETTE,
            legend: { position: 'bottom', fontSize: '13px', labels: { colors: INK } },
            dataLabels: { enabled: true, style: { fontSize: '12px' } },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: function (options) {
                                    return options.globals.seriesTotals.reduce(function (sum, value) { return sum + value; }, 0);
                                }
                            }
                        }
                    }
                }
            },
            stroke: { width: 2, colors: ['#ffffff'] }
        });
        chart.render();
        return chart;
    }

    function barChart(el, categories, values) {
        if (!el || !window.ApexCharts) {
            return null;
        }
        var chart = new ApexCharts(el, {
            chart: { type: 'bar', height: 300, fontFamily: 'Inter, sans-serif', toolbar: { show: false } },
            series: [{ name: 'Bookings', data: values }],
            xaxis: { categories: categories, labels: { style: { colors: INK } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { style: { colors: INK } } },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '48%', distributed: true } },
            colors: PALETTE,
            dataLabels: { enabled: false },
            legend: { show: false },
            grid: { borderColor: '#f2eff6', strokeDashArray: 4 }
        });
        chart.render();
        return chart;
    }

    function renderUpcomingBookings(rows) {
        var host = UI.qs('[data-widget="upcoming-bookings"]');
        if (!host) {
            return;
        }
        if (!rows || !rows.length) {
            host.innerHTML = '<div class="table-empty"><div class="empty-title">No upcoming bookings</div><div class="text-sm mt-1">Scheduled events will appear here.</div></div>';
            return;
        }
        host.innerHTML = '<div class="table-wrap"><table class="table"><thead><tr><th>Reference</th><th>Customer</th><th>Event Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>' +
            rows.map(function (row) {
                return '<tr>' +
                    '<td><a class="link-brand" href="' + window.APP.route('manage.booking', { id: row.id }) + '">' + UI.escape(row.reference_no) + '</a></td>' +
                    '<td>' + UI.escape(row.customer_name) + '</td>' +
                    '<td>' + UI.escape(window.APP.formatDate(row.event_date)) + '</td>' +
                    '<td>' + window.APP.money(Math.round(Number(row.total_amount) * 100)) + '</td>' +
                    '<td>' + UI.statusBadge(row.status) + '</td>' +
                    '</tr>';
            }).join('') + '</tbody></table></div>';
    }

    function renderTopCustomers(rows) {
        var host = UI.qs('[data-widget="top-customers"]');
        if (!host) {
            return;
        }
        if (!rows || !rows.length) {
            host.innerHTML = '<div class="table-empty"><div class="empty-title">No customer data yet</div></div>';
            return;
        }
        host.innerHTML = '<div class="divide-y">' + rows.map(function (row) {
            var paid = row.paid_formatted ? row.paid_formatted : window.APP.money(Number(row.paid_cents));
            return '<div class="flex items-center gap-3" style="padding:14px 20px">' +
                '<span class="avatar">' + UI.escape(window.APP.initials(row.customer_name)) + '</span>' +
                '<div class="min-w-0 flex-1">' +
                '<div class="truncate font-semibold">' + UI.escape(row.customer_name) + '</div>' +
                '<div class="truncate text-muted text-sm">' + UI.escape(row.email || '') + '</div>' +
                '</div>' +
                '<div class="text-right">' +
                '<div class="font-semibold">' + UI.escape(paid) + '</div>' +
                '<div class="text-muted text-xs">' + window.APP.number(row.booking_count) + ' booking' + (Number(row.booking_count) === 1 ? '' : 's') + '</div>' +
                '</div>' +
                '</div>';
        }).join('') + '</div>';
    }

    function renderActivity(rows) {
        var host = UI.qs('[data-widget="recent-activity"]');
        if (!host) {
            return;
        }
        if (!rows || !rows.length) {
            host.innerHTML = '<div class="table-empty"><div class="empty-title">No recent activity</div></div>';
            return;
        }
        host.innerHTML = '<div class="timeline">' + rows.map(function (row) {
            return '<div class="timeline-item">' +
                '<div class="font-semibold text-sm">' + UI.escape(window.APP.titleCase(row.action.replace('.', ' '))) + '</div>' +
                '<div class="text-muted text-sm">' + UI.escape(row.details || '') + '</div>' +
                '<div class="text-muted text-xs mt-1">' + UI.escape(row.user_name || 'System') + ' \u00B7 ' + UI.escape(window.APP.timeAgo(row.created_at)) + '</div>' +
                '</div>';
        }).join('') + '</div>';
    }

    function fillStats(kpis) {
        var currencyStats = { total_revenue: true };
        Object.keys(kpis).forEach(function (key) {
            var node = UI.qs('[data-stat="' + key + '"]');
            if (!node) {
                return;
            }
            var value = kpis[key];
            if (currencyStats[key]) {
                node.textContent = typeof value === 'string' && /[^0-9.,-]/.test(value) ? value : window.APP.money(Number(value));
            } else {
                node.textContent = window.APP.number(value);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        API.get('/dashboard').then(function (data) {
            if (!data) {
                return;
            }
            fillStats(data.kpis || {});
            areaChart(document.getElementById('revenue-chart'), (data.revenue || {}).labels || [], [
                { name: 'Revenue', data: ((data.revenue || {}).values || []).map(function (value) { return (Number(value) || 0) / 100; }) }
            ]);
            donutChart(document.getElementById('booking-status-chart'), (data.booking_status || {}).labels || [], (data.booking_status || {}).values || []);
            barChart(document.getElementById('service-performance-chart'), (data.service_performance || {}).labels || [], (data.service_performance || {}).counts || []);
            renderUpcomingBookings(data.upcoming_bookings);
            renderTopCustomers(data.top_customers);
            renderActivity(data.recent_activities);
            if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
        }).catch(function (error) {
            Alerts.error(error.message);
        });

        document.addEventListener('app:ready', function (event) {
            var user = event.detail;
            var node = UI.qs('[data-user-first-name]');
            if (user && node) {
                node.textContent = String(user.name || '').split(' ')[0] || 'there';
            }
        });
    });
})();
