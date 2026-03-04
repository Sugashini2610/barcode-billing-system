/**
 * barcode.js — Dedicated Barcode Generator & Label Printer page logic
 * Saves generated barcodes as real products in Google Sheets (Products sheet)
 * so they can be scanned on the billing screen like normal products.
 */

var bcCurrentBarcode = '';
var bcCurrentData = {};

// ═══════════════════════════════════════════════
// FORM READ
// ═══════════════════════════════════════════════

function bcGetFormData() {
    return {
        name: (document.getElementById('bcProductName').value || '').trim(),
        price: parseFloat(document.getElementById('bcPrice').value || 0),
        gst: parseInt(document.getElementById('bcGst').value || 0, 10),
        unit: document.getElementById('bcUnit').value,
        category: (document.getElementById('bcCategory').value || '').trim() || 'General',
        copies: Math.max(1, parseInt(document.getElementById('bcCopies').value || 1, 10)),
        custom: (document.getElementById('bcCustomNumber').value || '').trim(),
        size: document.getElementById('bcLabelSize').value,
        showMrp: document.getElementById('bcShowMrp').checked,
        showGst: document.getElementById('bcShowGst').checked
    };
}

// ═══════════════════════════════════════════════
// EAN-13 UTILITIES
// ═══════════════════════════════════════════════

function bcGenerateEAN13() {
    var base = '';
    for (var i = 0; i < 12; i++) base += Math.floor(Math.random() * 10);
    var sum = 0;
    for (var j = 0; j < 12; j++) sum += parseInt(base[j], 10) * (j % 2 === 0 ? 1 : 3);
    return base + ((10 - (sum % 10)) % 10);
}

function bcValidateEAN13(code) {
    if (!/^\d{13}$/.test(code)) return false;
    var sum = 0;
    for (var i = 0; i < 12; i++) sum += parseInt(code[i], 10) * (i % 2 === 0 ? 1 : 3);
    return (10 - (sum % 10)) % 10 === parseInt(code[12], 10);
}

// ═══════════════════════════════════════════════
// LABEL RENDERING (HTML + canvas)
// ═══════════════════════════════════════════════

function bcRenderLabel(barcode, data) {
    var sizes = {
        small: { w: '144px', h: '72px', font: '8px', bcH: 30, fs: '6px' },
        medium: { w: '216px', h: '108px', font: '10px', bcH: 44, fs: '8px' },
        large: { w: '288px', h: '144px', font: '12px', bcH: 60, fs: '10px' }
    };
    var s = sizes[data.size] || sizes.medium;

    var mrpLine = '', gstLine = '';
    var unitLine = data.unit
        ? '<span style="font-size:' + s.fs + ';color:#888;">' + bEsc(data.unit) + '</span>' : '';

    if (data.showMrp) {
        var base = data.price.toFixed(2);
        if (data.gst > 0 && data.showGst) {
            var gstAmt = (data.price * data.gst / 100).toFixed(2);
            var inclMrp = (data.price + parseFloat(gstAmt)).toFixed(2);
            mrpLine = '<div style="font-weight:700;font-size:' + s.font + ';color:#111;">MRP: &#8377;' + inclMrp + '</div>';
            gstLine = '<div style="font-size:' + s.fs + ';color:#555;">Excl: &#8377;' + base + ' + GST ' + data.gst + '%</div>';
        } else {
            mrpLine = '<div style="font-weight:700;font-size:' + s.font + ';color:#111;">MRP: &#8377;' + base + '</div>';
        }
    }

    var canvasId = 'bcC_' + barcode + '_' + Math.random().toString(36).substr(2, 5);
    var label = '<div class="bc-label" style="' +
        'width:' + s.w + ';height:' + s.h + ';border:1px solid #ccc;border-radius:4px;' +
        'background:#fff;padding:4px 6px;box-sizing:border-box;' +
        'display:inline-flex;flex-direction:column;align-items:center;' +
        'justify-content:space-between;font-family:Arial,sans-serif;margin:4px;">' +
        '<div style="font-weight:700;font-size:' + s.font + ';color:#111;text-align:center;' +
        'max-width:100%;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">' + bEsc(data.name) + '</div>' +
        mrpLine + gstLine +
        '<canvas id="' + canvasId + '" style="max-width:100%;"></canvas>' +
        '<div style="font-size:' + s.fs + ';color:#666;letter-spacing:.04em;">' + barcode + '&nbsp;' + unitLine + '</div>' +
        '</div>';

    return { html: label, canvasId: canvasId, bcH: s.bcH };
}

function bcDrawCanvas(canvasId, barcode, bcH) {
    var canvas = document.getElementById(canvasId);
    if (canvas && typeof JsBarcode !== 'undefined') {
        try {
            JsBarcode(canvas, barcode, {
                format: 'EAN13', width: 1.5, height: bcH,
                displayValue: false, margin: 0
            });
        } catch (e) { }
    }
}

// ═══════════════════════════════════════════════
// LIVE PREVIEW
// ═══════════════════════════════════════════════

function bcPreviewUpdate() {
    var data = bcGetFormData();
    var area = document.getElementById('bcPreviewArea');
    if (!data.name && !data.price) {
        area.innerHTML = '<p style="color:#94a3b8;font-size:.84rem;text-align:center;">' +
            '<i class="bi bi-upc" style="font-size:1.6rem;display:block;margin-bottom:6px;opacity:.3;"></i>' +
            'Fill in product name &amp; price to see the label preview</p>';
        return;
    }
    var previewBarcode = bcCurrentBarcode || '0000000000000';
    var r = bcRenderLabel(previewBarcode, data);
    area.innerHTML = '<div style="display:flex;gap:4px;flex-wrap:wrap;justify-content:center;">' + r.html + '</div>';
    setTimeout(function () { bcDrawCanvas(r.canvasId, previewBarcode, r.bcH); }, 50);
}

// ═══════════════════════════════════════════════
// GENERATE BARCODE
// ═══════════════════════════════════════════════

function bcGenerate() {
    var data = bcGetFormData();
    if (!data.name) { Toast.error('Please enter a product name'); return; }
    if (!data.price || data.price <= 0) { Toast.error('Please enter a valid price'); return; }

    var barcode = data.custom;
    if (barcode) {
        if (!bcValidateEAN13(barcode)) {
            Toast.error('Custom barcode must be a valid 13-digit EAN-13');
            return;
        }
    } else {
        barcode = bcGenerateEAN13();
        document.getElementById('bcCustomNumber').value = barcode;
    }

    bcCurrentBarcode = barcode;
    bcCurrentData = data;

    var r = bcRenderLabel(barcode, data);
    document.getElementById('bcPreviewArea').innerHTML =
        '<div style="display:flex;gap:4px;flex-wrap:wrap;justify-content:center;">' + r.html + '</div>';

    setTimeout(function () {
        bcDrawCanvas(r.canvasId, barcode, r.bcH);
        document.getElementById('bcSaveBtn').disabled = false;
        document.getElementById('bcPrintBtn').disabled = false;
        Toast.success('Barcode generated! Click "Save to Products" to make it scannable on the billing screen.');
    }, 80);
}

// ═══════════════════════════════════════════════
// SAVE TO GOOGLE SHEETS PRODUCTS
// ═══════════════════════════════════════════════

function bcSaveProduct() {
    if (!bcCurrentBarcode) { Toast.error('Generate a barcode first'); return; }

    // Always read fresh form values at save time (user may have updated fields after generating)
    var freshData = bcGetFormData();
    // Merge: use fresh form name/price/etc., but fall back to bcCurrentData if field is empty
    var data = {
        name: (freshData.name || bcCurrentData.name || '').trim(),
        price: freshData.price || bcCurrentData.price || 0,
        gst: (freshData.gst !== undefined ? freshData.gst : (bcCurrentData.gst || 0)),
        unit: freshData.unit || bcCurrentData.unit || 'Piece',
        category: freshData.category || bcCurrentData.category || 'General',
        copies: freshData.copies || bcCurrentData.copies || 1,
        size: freshData.size || bcCurrentData.size || 'medium',
        showMrp: freshData.showMrp,
        showGst: freshData.showGst
    };

    if (!data.name) { Toast.error('Product name is required'); return; }
    if (!data.price || data.price <= 0) { Toast.error('Valid price is required'); return; }

    // Send as plain JSON — reliable across all PHP/server configurations
    var payload = {
        name: data.name,
        category: data.category || 'General',
        price: data.price,
        gst_percent: data.gst || 0,
        unit: data.unit || 'Piece',
        quantity: 0,
        barcode: bcCurrentBarcode,
        description: 'Generated label product'
    };

    Loading.show();
    API.post('products', 'store-label', null, payload, function (res) {
        Loading.hide();
        if (res.status === 'success') {
            Toast.success('✔ Saved! Barcode ' + bcCurrentBarcode + ' is now scannable on the billing screen.');
            document.getElementById('bcSaveBtn').disabled = true;
            bcLoadSaved();
        } else {
            Toast.error(res.message || 'Failed to save product');
        }
    });
}

// ═══════════════════════════════════════════════
// PRINT LABELS
// ═══════════════════════════════════════════════

function bcPrint() {
    if (!bcCurrentBarcode) { Toast.error('Generate a barcode first'); return; }
    bcDoPrint(bcCurrentBarcode, bcCurrentData);
}

function bcDoPrint(barcode, data) {
    var copies = Math.max(1, parseInt(data.copies) || 1);
    var labels = '';
    for (var c = 0; c < copies; c++) {
        labels += bcRenderLabel(barcode, data).html;
    }
    var bcH = data.size === 'small' ? 30 : (data.size === 'large' ? 60 : 44);

    var pw = window.open('', '_blank', 'width=900,height=700');
    pw.document.write('<!DOCTYPE html><html><head>' +
        '<title>Labels — ' + bEsc(data.name) + '</title>' +
        '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>' +
        '<style>' +
        'body{margin:0;padding:6px;font-family:Arial,sans-serif;background:#fff;}' +
        '.bc-wrap{display:flex;flex-wrap:wrap;gap:2px;padding:2px;}' +
        '.bc-label{page-break-inside:avoid;break-inside:avoid;}' +
        '.noprint{display:inline-block;margin:6px 4px;padding:7px 16px;border:none;border-radius:6px;cursor:pointer;font-size:13px;}' +
        '@media print{.noprint{display:none!important;}body{margin:0;padding:0;}@page{margin:3mm;size:auto;}}' +
        '</style></head><body>' +
        '<div class="noprint" style="background:#f1f5f9;padding:10px;margin-bottom:8px;border-radius:6px;display:flex;gap:8px;align-items:center;">' +
        '<b style="font-size:13px;">🖨 ' + copies + ' label(s) — ' + bEsc(data.name) + '</b>' +
        '<button class="noprint" style="background:#7c3aed;color:#fff;" onclick="window.print()">Print Now</button>' +
        '<button class="noprint" style="background:#64748b;color:#fff;" onclick="window.close()">Close</button>' +
        '</div>' +
        '<div class="bc-wrap">' + labels + '</div>' +
        '<script>window.onload=function(){' +
        'document.querySelectorAll("canvas").forEach(function(cv){' +
        'try{JsBarcode(cv,"' + barcode + '",{format:"EAN13",width:1.6,height:' + bcH + ',displayValue:false,margin:0});}catch(e){}' +
        '});};<\/script></body></html>');
    pw.document.close();
}

// ═══════════════════════════════════════════════
// LOAD SAVED LABEL PRODUCTS
// ═══════════════════════════════════════════════

function bcLoadSaved() {
    var wrap = document.getElementById('bcSavedWrap');
    if (!wrap) return;
    wrap.innerHTML = '<p style="color:#94a3b8;font-size:.84rem;">Loading…</p>';

    API.get('products', 'index', null, function (res) {
        if (res.status !== 'success') {
            wrap.innerHTML = '<p style="color:#dc2626;font-size:.84rem;">Failed to load products.</p>';
            return;
        }
        // Filter to products that have "Generated label product" description OR show all
        var products = (res.data || []).filter(function (p) {
            return p.Description && p.Description.indexOf('Generated label product') > -1;
        });

        if (!products.length) {
            wrap.innerHTML = '<p style="color:#94a3b8;font-size:.84rem;padding:10px 0;">' +
                'No barcode label products saved yet. Generate one and click <strong>Save to Products</strong>.</p>';
            return;
        }

        var html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;">';
        products.forEach(function (p) {
            var mrp = parseFloat(p.GST_Percent) > 0
                ? '₹' + (parseFloat(p.Price) * (1 + parseFloat(p.GST_Percent) / 100)).toFixed(2) + ' (incl. GST ' + p.GST_Percent + '%)'
                : '₹' + parseFloat(p.Price).toFixed(2);
            html += '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;">' +
                '<div style="font-weight:700;font-size:.88rem;color:#1e293b;margin-bottom:3px;">' + bEsc(p.Name) + '</div>' +
                '<div style="font-size:.78rem;color:#64748b;">' + mrp + ' &nbsp;|&nbsp; ' + bEsc(p.Unit || '') + '</div>' +
                '<div style="font-family:monospace;font-size:.74rem;color:#94a3b8;margin-top:4px;">' + bEsc(p.Barcode || '') + '</div>' +
                '<div style="margin-top:10px;display:flex;gap:6px;">' +
                '<button class="btn btn-outline btn-sm" style="font-size:.74rem;padding:4px 10px;" ' +
                'onclick="bcReprintSaved(' + JSON.stringify({
                    name: p.Name, price: parseFloat(p.Price),
                    gst: parseFloat(p.GST_Percent), unit: p.Unit, barcode: p.Barcode
                }) + ')"><i class="bi bi-printer"></i> Reprint</button>' +
                '</div>' +
                '</div>';
        });
        html += '</div>';
        wrap.innerHTML = html;
    });
}

function bcReprintSaved(item) {
    var data = {
        name: item.name, price: item.price,
        gst: item.gst, unit: item.unit,
        copies: 1, size: 'medium',
        showMrp: true, showGst: true
    };
    bcDoPrint(item.barcode, data);
}

// ═══════════════════════════════════════════════
// RESET
// ═══════════════════════════════════════════════

function bcReset() {
    ['bcProductName', 'bcCopies', 'bcCustomNumber'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { if (id === 'bcCopies') el.value = '1'; else el.value = ''; }
    });
    document.getElementById('bcCategory').value = '';
    document.getElementById('bcGst').value = '18';
    document.getElementById('bcUnit').value = 'Piece';
    document.getElementById('bcLabelSize').value = 'medium';
    document.getElementById('bcShowMrp').checked = true;
    document.getElementById('bcShowGst').checked = true;
    document.getElementById('bcSaveBtn').disabled = true;
    document.getElementById('bcPrintBtn').disabled = true;
    document.getElementById('bcPrice').value = '';
    bcCurrentBarcode = '';
    bcCurrentData = {};
    document.getElementById('bcPreviewArea').innerHTML =
        '<p style="color:#94a3b8;font-size:.84rem;text-align:center;">' +
        '<i class="bi bi-upc" style="font-size:1.6rem;display:block;margin-bottom:6px;opacity:.3;"></i>' +
        'Fill in product name &amp; price to see the label preview</p>';
}

// ═══════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════

function bEsc(str) {
    var d = document.createElement('div');
    d.textContent = String(str || '');
    return d.innerHTML;
}

// Init
document.addEventListener('DOMContentLoaded', function () {
    if (APP.page === 'barcode') bcLoadSaved();
});
