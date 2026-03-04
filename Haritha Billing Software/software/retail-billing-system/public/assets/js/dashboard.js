/**
 * dashboard.js — Dashboard page data loading
 */

// ═══════════════════════════════════════
// DASHBOARD DATA LOADING
// ═══════════════════════════════════════

function loadDashboard() {
    Loading.show();
    API.get('dashboard', 'index', null, function (res) {
        Loading.hide();
        if (res.status !== 'success') { Toast.error('Failed to load dashboard'); return; }
        var d = res.data;

        // Stats
        document.getElementById('statProducts').textContent = d.total_products || 0;
        document.getElementById('statLow').textContent =
            d.stock_summary ? (d.stock_summary.low_stock || 0) : 0;
        document.getElementById('statOut').textContent =
            d.stock_summary ? (d.stock_summary.out_of_stock || 0) : 0;

        // Low stock badge in sidebar
        var lsBadge = document.getElementById('lowStockBadge');
        if (lsBadge && d.stock_summary && d.stock_summary.low_stock > 0) {
            lsBadge.textContent = d.stock_summary.low_stock;
            lsBadge.style.display = 'inline-block';
        }

        // Stock alerts
        renderStockAlerts(d.low_stock || [], d.out_of_stock || []);

        // Top products
        renderTopProducts(d.top_products || []);

        // Recent bills
        renderRecentBills(d.recent_bills || []);
    });
}

function renderStockAlerts(low, out) {
    var el = document.getElementById('stockAlerts');
    if (!el) return;
    var all = [];
    (out || []).forEach(function (p) { all.push({ name: p.Name, qty: p.Quantity, status: 'out' }); });
    (low || []).forEach(function (p) { all.push({ name: p.Name, qty: p.Quantity, status: 'low' }); });
    if (!all.length) {
        el.innerHTML = '<p class="text-muted" style="font-size:.82rem">&#x2714; All products are well stocked</p>';
        return;
    }
    var html = '';
    all.slice(0, 10).forEach(function (p) {
        var badge = p.status === 'out'
            ? '<span class="badge badge-danger">Out of Stock</span>'
            : '<span class="badge badge-warning">Low: ' + p.qty + '</span>';
        html += '<div class="stock-alert-row"><span>' + esc(p.name) + '</span>' + badge + '</div>';
    });
    el.innerHTML = html;
}

function renderTopProducts(products) {
    var el = document.getElementById('topProducts');
    if (!el) return;
    if (!products.length) {
        el.innerHTML = '<p class="text-muted" style="font-size:.82rem;padding:8px 0">No sales data yet</p>';
        return;
    }
    var html = '';
    products.forEach(function (p, i) {
        html += '<div class="rank-item">' +
            '<span class="rank-num">' + (i + 1) + '</span>' +
            '<div class="rank-info"><div class="rank-name">' + esc(p.name) + '</div>' +
            '<div class="rank-qty">' + p.qty + ' units sold</div></div>' +
            '<span class="rank-revenue">' + fmt(p.revenue) + '</span>' +
            '</div>';
    });
    el.innerHTML = html;
}

function renderRecentBills(bills) {
    var tbody = document.getElementById('recentBillsBody');
    if (!tbody) return;
    if (!bills.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:20px">No bills yet</td></tr>';
        return;
    }
    var html = '';
    bills.slice(0, 8).forEach(function (b) {
        html += '<tr>' +
            '<td><span class="font-mono" style="font-size:.8rem">' + esc(b.Bill_No || '') + '</span></td>' +
            '<td>' + esc(b.Customer_Name || 'Walk-in') + '</td>' +
            '<td>' + esc(b.Date || '') + '</td>' +
            '<td class="font-bold">' + fmt(b.Final_Amount) + '</td>' +
            '<td><span class="badge badge-gray">' + esc(b.Payment_Mode || '') + '</span></td>' +
            '</tr>';
    });
    tbody.innerHTML = html;
}

// ═══════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════

function esc(str) {
    var d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

function fmt(val) {
    return (APP.currency || '₹') + parseFloat(val || 0).toFixed(2);
}

// Auto-load on dashboard page
document.addEventListener('DOMContentLoaded', function () {
    if (APP.page === 'dashboard') loadDashboard();
});
