(function () {
    'use strict';

    var charts = {};
    var rangeSelect = document.querySelector('[data-range]');

    function hasCharts() {
        return typeof window.ApexCharts !== 'undefined';
    }

    function blank() {
        return '<div class="text-muted text-sm">No data for this period.</div>';
    }

    function renderChart(id, options, hasData) {
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        if (!hasData) {
            el.innerHTML = blank();
            return;
        }
        if (charts[id]) {
            charts[id].destroy();
        }
        el.innerHTML = '';
        charts[id] = new window.ApexCharts(el, options);
        charts[id].render();
    }

    function kpiCard(label, value) {
        return '<div class="stat-card"><div class="stat-label">' + UI.escape(label) + '</div><div class="stat-value">' + value + '</div></div>';
    }

    function renderKpis(kpis) {
        var host = document.getElementById('report-kpis');
        host.innerHTML =
            kpiCard('Total revenue', UI.escape(kpis.total_revenue || '\u2014')) +
            kpiCard('Pending payments', UI.escape(kpis.pending_payments || '\u2014')) +
            kpiCard('Active bookings', window.APP.number(kpis.active_bookings)) +
            kpiCard('Upcoming bookings', window.APP.number(kpis.upcoming_bookings));
    }

    function renderTopCustomers(rows) {
        var host = document.getElementById('top-customers');
        var data = (rows || []).filter(function (row) { return Number(row.paid_cents) > 0 || Number(row.booking_count) > 0; });
        if (!data.length) {
            host.innerHTML = blank();
            return;
        }
        host.innerHTML = '<div class="timeline">' + data.map(function (row) {
            return '<div class="timeline-item"><div class="flex items-center justify-between gap-2">' +
                '<div><div class="font-semibold text-sm">' + UI.escape(row.customer_name) + '</div>' +
                '<div class="text-muted text-xs">' + window.APP.number(row.booking_count) + ' booking' + (Number(row.booking_count) === 1 ? '' : 's') + '</div></div>' +
                '<div class="font-semibold text-sm">' + UI.escape(row.paid_formatted) + '</div></div></div>';
        }).join('') + '</div>';
    }

    function renderSummary(data) {
        renderKpis(data.kpis || {});

        var revenue = data.revenue || { labels: [], values: [] };
        var revenueHas = (revenue.values || []).some(function (value) { return Number(value) > 0; });
        if (hasCharts()) {
            renderChart('chart-revenue', {
                chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                series: [{ name: 'Revenue', data: revenue.values || [] }],
                xaxis: { categories: revenue.labels || [] },
                yaxis: { labels: { formatter: function (value) { return window.APP.money(Number(value) || 0); } } },
                colors: ['#8b0a72'],
                stroke: { curve: 'smooth', width: 3 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                dataLabels: { enabled: false }
            }, revenueHas);

            var status = data.booking_status || { labels: [], values: [] };
            var statusHas = (status.values || []).some(function (value) { return Number(value) > 0; });
            renderChart('chart-booking-status', {
                chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
                series: status.values || [],
                labels: status.labels || [],
                colors: ['#b45309', '#8b0a72', '#2563eb', '#0891b2', '#12805c', '#c2263c'],
                legend: { position: 'bottom' },
                dataLabels: { enabled: false }
            }, statusHas);

            var quotation = data.quotations || { labels: [], values: [] };
            renderChart('chart-quotation', {
                chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                series: [{ name: 'Quotations', data: quotation.values || [] }],
                xaxis: { categories: quotation.labels || [] },
                colors: ['#8b0a72'],
                plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
                dataLabels: { enabled: false }
            }, (quotation.values || []).some(function (value) { return Number(value) > 0; }));

            var leads = data.leads || { labels: [], values: [] };
            renderChart('chart-lead', {
                chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                series: [{ name: 'Leads', data: leads.values || [] }],
                xaxis: { categories: leads.labels || [] },
                colors: ['#2563eb'],
                plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
                dataLabels: { enabled: false }
            }, (leads.values || []).some(function (value) { return Number(value) > 0; }));

            var services = data.services || { labels: [], counts: [], revenue: [] };
            renderChart('chart-services', {
                chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                series: [{ name: 'Bookings', data: services.counts || [] }],
                xaxis: { categories: services.labels || [] },
                colors: ['#12805c'],
                plotOptions: { bar: { horizontal: true, borderRadius: 6 } },
                dataLabels: { enabled: false }
            }, (services.counts || []).some(function (value) { return Number(value) > 0; }));
        }

        renderTopCustomers(data.top_customers || []);
    }

    function renderRevenue(data) {
        var rows = data.rows || [];
        var totals = data.totals || {};
        var host = document.getElementById('revenue-table');
        var totalsHtml = '<div class="totals-list mb-4">' +
            '<div class="totals-row"><span>Recorded</span><span>' + UI.escape(totals.recorded || '\u2014') + '</span></div>' +
            '<div class="totals-row"><span>Reversed</span><span>' + UI.escape(totals.reversed || '\u2014') + '</span></div>' +
            '<div class="totals-row is-total"><span>Net</span><span>' + UI.escape(totals.net || '\u2014') + '</span></div>' +
            '</div>';
        if (!rows.length) {
            host.innerHTML = totalsHtml + blank();
            return;
        }
        host.innerHTML = totalsHtml + '<div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Reference</th><th>Booking</th><th>Method</th><th>Amount</th></tr></thead><tbody>' +
            rows.map(function (row) {
                return '<tr><td>' + UI.escape(window.APP.formatDate(row.payment_date)) + '</td>' +
                    '<td>' + UI.escape(row.reference_no) + '</td>' +
                    '<td>' + UI.escape(row.booking_no || '') + '<div class="text-muted text-sm">' + UI.escape(row.customer_name || '') + '</div></td>' +
                    '<td>' + UI.escape(row.method) + '</td>' +
                    '<td class="font-semibold">' + UI.escape(row.amount_formatted) + '</td></tr>';
            }).join('') + '</tbody></table></div>';
    }

    function renderBookings(data) {
        var rows = data.rows || [];
        var totals = data.totals || {};
        var host = document.getElementById('bookings-table');
        var totalsHtml = '<div class="grid-stats mb-4">' +
            '<div class="stat-card"><div class="stat-label">Bookings</div><div class="stat-value">' + window.APP.number(totals.bookings) + '</div></div>' +
            '<div class="stat-card"><div class="stat-label">Value</div><div class="stat-value">' + UI.escape(totals.value || '\u2014') + '</div></div>' +
            '<div class="stat-card"><div class="stat-label">Collected</div><div class="stat-value">' + UI.escape(totals.collected || '\u2014') + '</div></div>' +
            '<div class="stat-card"><div class="stat-label">Outstanding</div><div class="stat-value">' + UI.escape(totals.outstanding || '\u2014') + '</div></div>' +
            '</div>';
        if (!rows.length) {
            host.innerHTML = totalsHtml + blank();
            return;
        }
        host.innerHTML = totalsHtml + '<div class="table-wrap"><table class="table"><thead><tr><th>Reference</th><th>Customer</th><th>Event</th><th>Total</th><th>Paid</th><th>Outstanding</th><th>Status</th></tr></thead><tbody>' +
            rows.map(function (row) {
                return '<tr><td class="font-semibold">' + UI.escape(row.reference_no) + '</td>' +
                    '<td>' + UI.escape(row.customer_name) + '</td>' +
                    '<td>' + UI.escape(window.APP.formatDate(row.event_date)) + '</td>' +
                    '<td>' + UI.escape(row.total_formatted) + '</td>' +
                    '<td>' + UI.escape(row.paid_formatted) + '</td>' +
                    '<td>' + UI.escape(row.remaining_formatted) + '</td>' +
                    '<td>' + UI.statusBadge(row.status) + '</td></tr>';
            }).join('') + '</tbody></table></div>';
    }

    function loadSummary() {
        API.get('/reports/summary', { range: rangeSelect.value }).then(renderSummary).catch(function (error) {
            document.getElementById('report-kpis').innerHTML = '<div class="card"><div class="card-pad text-muted text-sm">' + UI.escape(error.message) + '</div></div>';
        });
    }

    function loadRevenue() {
        var from = document.querySelector('[data-revenue-from]').value;
        var to = document.querySelector('[data-revenue-to]').value;
        API.get('/reports/revenue', { from: from || undefined, to: to || undefined }).then(renderRevenue).catch(function (error) {
            document.getElementById('revenue-table').innerHTML = '<div class="card-pad text-muted text-sm">' + UI.escape(error.message) + '</div>';
        });
    }

    function loadBookings() {
        var from = document.querySelector('[data-bookings-from]').value;
        var to = document.querySelector('[data-bookings-to]').value;
        API.get('/reports/bookings', { from: from || undefined, to: to || undefined }).then(renderBookings).catch(function (error) {
            document.getElementById('bookings-table').innerHTML = '<div class="card-pad text-muted text-sm">' + UI.escape(error.message) + '</div>';
        });
    }

    rangeSelect.addEventListener('change', loadSummary);
    document.querySelector('[data-revenue-apply]').addEventListener('click', loadRevenue);
    document.querySelector('[data-bookings-apply]').addEventListener('click', loadBookings);

    var today = new Date().toISOString().slice(0, 10);
    document.querySelector('[data-revenue-to]').value = today;
    document.querySelector('[data-bookings-to]').value = today;

    loadSummary();
    loadRevenue();
    loadBookings();
})();
