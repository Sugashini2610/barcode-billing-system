/**
 * api.js - Central API client & UI utilities
 * Haritha Billing Software
 */

// ============================================================
// API REQUEST HELPERS
// ============================================================
var API = {
    get: function (module, action, params, callback) {
        var url = APP.apiUrl + '?module=' + module + '&action=' + action;
        if (params) {
            for (var k in params) {
                if (params.hasOwnProperty(k)) url += '&' + k + '=' + encodeURIComponent(params[k]);
            }
        }
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(callback)
            .catch(function (e) { console.error('API GET error:', e); Toast.error('Connection error'); });
    },

    post: function (module, action, id, data, callback) {
        var url = APP.apiUrl + '?module=' + module + '&action=' + action;
        if (id) url += '&id=' + encodeURIComponent(id);
        var body;
        if (data instanceof FormData) {
            body = data;
        } else {
            body = JSON.stringify(data);
        }
        fetch(url, {
            method: 'POST',
            headers: body instanceof FormData ? {} : { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        })
            .then(function (r) { return r.json(); })
            .then(callback)
            .catch(function (e) { console.error('API POST error:', e); Toast.error('Connection error'); });
    }
};

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================
var Toast = {
    show: function (msg, type, duration) {
        var icons = { success: 'check-circle-fill', error: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
        var icon = icons[type] || icons.info;
        var d = document.createElement('div');
        d.className = 'toast ' + type;
        d.innerHTML = '<i class="bi bi-' + icon + ' toast-icon"></i>' +
            '<span class="toast-message">' + msg + '</span>' +
            '<button class="toast-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>';
        document.getElementById('toastContainer').appendChild(d);
        setTimeout(function () { if (d.parentElement) d.remove(); }, duration || 4000);
    },
    success: function (m) { this.show(m, 'success'); },
    error: function (m) { this.show(m, 'error', 5000); },
    warning: function (m) { this.show(m, 'warning'); },
    info: function (m) { this.show(m, 'info'); }
};

// ============================================================
// LOADING OVERLAY
// ============================================================
var Loading = {
    show: function () { document.getElementById('loadingOverlay').classList.add('active'); },
    hide: function () { document.getElementById('loadingOverlay').classList.remove('active'); }
};

// ============================================================
// MODAL HELPER
// ============================================================
var Modal = {
    open: function (title, bodyHtml, footerHtml, size) {
        var box = document.getElementById('globalModal');
        var ov = document.getElementById('modalOverlay');
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalBody').innerHTML = bodyHtml || '';
        document.getElementById('modalFooter').innerHTML = footerHtml || '';
        if (size) box.className = 'modal-box active ' + size;
        else box.className = 'modal-box active';
        ov.classList.add('active');
    },
    close: function () {
        document.getElementById('globalModal').classList.remove('active');
        document.getElementById('modalOverlay').classList.remove('active');
    }
};

// Wire close buttons
document.addEventListener('DOMContentLoaded', function () {
    var mc = document.getElementById('modalClose');
    var mo = document.getElementById('modalOverlay');
    if (mc) mc.addEventListener('click', Modal.close);
    if (mo) mo.addEventListener('click', Modal.close);
});

// ============================================================
// CURRENCY FORMAT
// ============================================================
function fmt(amount) {
    return APP.currency + parseFloat(amount || 0).toFixed(2);
}

// ============================================================
// TABLE SEARCH
// ============================================================
function initSearch(inputId, tableBodyId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        var rows = document.getElementById(tableBodyId).querySelectorAll('tr');
        rows.forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
        });
    });
}
