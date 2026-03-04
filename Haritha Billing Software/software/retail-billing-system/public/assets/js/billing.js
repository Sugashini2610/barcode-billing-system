/**
 * billing.js — Billing & GST Invoice page logic
 */

// ========== NORMAL BILLING ==========
var cart = [];
var lastBillData = null;
var selectedPayment = 'Cash';

function generateLocalBillNo() {
    var now = new Date();
    var d = String(now.getFullYear()) +
        String(now.getMonth() + 1).padStart(2, '0') +
        String(now.getDate()).padStart(2, '0');
    var r = String(Math.floor(Math.random() * 9000) + 1000);
    return 'BILL' + d + r;
}

document.addEventListener('DOMContentLoaded', function () {
    var bno = generateLocalBillNo();
    var el = document.getElementById('billNoDisplay');
    if (el) el.textContent = 'Bill No: ' + bno;

    var inp = document.getElementById('barcodeInput');
    if (inp) {
        inp.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') searchBarcode();
        });
    }

    // Reports tabs
    document.addEventListener('DOMContentLoaded', function () { });

    if (APP.page === 'billing') { /* ready */ }
    if (APP.page === 'gst') { /* ready */ }
    if (APP.page === 'stock') loadStock();
    if (APP.page === 'reports') { /* ready */ }
});

// Barcode search
function searchBarcode() {
    var val = document.getElementById('barcodeInput').value.trim();
    if (!val) return;

    // Numeric = barcode scan; text = product name search
    if (/\D/.test(val)) {
        searchByName(val);
        return;
    }

    Loading.show();
    API.get('products', 'barcode', { barcode: val }, function (res) {
        Loading.hide();
        if (res.status === 'success' && res.data) {
            addToCart(res.data);
            document.getElementById('barcodeInput').value = '';
            document.getElementById('productSearchResults').style.display = 'none';
        } else {
            Toast.warning('Product not found for barcode. Try typing the name.');
            searchByName(val);
        }
    });
}

function searchByName(name) {
    Loading.show();
    API.get('products', 'index', null, function (res) {
        Loading.hide();
        if (res.status !== 'success') { Toast.error('Search failed'); return; }
        var q = name.toLowerCase();
        var results = (res.data || []).filter(function (p) {
            return p.Name && p.Name.toLowerCase().indexOf(q) > -1;
        });
        if (!results.length) { Toast.warning('No products match: ' + name); return; }
        if (results.length === 1) { addToCart(results[0]); return; }
        showSearchResults(results);
    });
}

function showSearchResults(products) {
    var tbody = document.getElementById('searchResultsBody');
    var html = '';
    products.slice(0, 10).forEach(function (p) {
        html += '<tr>' +
            '<td>' + esc(p.Name) + '</td>' +
            '<td><span class="font-mono" style="font-size:.75rem">' + esc(p.Barcode || '') + '</span></td>' +
            '<td>' + fmt(p.Price) + '</td>' +
            '<td>' + (p.Quantity || 0) + '</td>' +
            '<td><button class="btn btn-primary btn-sm" onclick="addToCartById(\'' + esc(p.ID) + '\')">Add</button></td>' +
            '</tr>';
    });
    tbody.innerHTML = html;
    window._searchProducts = products;
    document.getElementById('productSearchResults').style.display = '';
}

function addToCartById(id) {
    var products = window._searchProducts || [];
    var p = products.find(function (x) { return x.ID === id; });
    if (p) addToCart(p);
    document.getElementById('productSearchResults').style.display = 'none';
    document.getElementById('barcodeInput').value = '';
}

function addToCart(product) {
    var existing = null;
    cart.forEach(function (item) { if (item.product_id === product.ID) existing = item; });

    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            product_id: product.ID,
            name: product.Name,
            barcode: product.Barcode,
            price: parseFloat(product.Price || 0),
            gst_percent: parseFloat(product.GST_Percent || 0),
            quantity: 1,
            unit: product.Unit || 'Pcs',
            stock: parseInt(product.Quantity || 0)
        });
    }
    renderCart();
    Toast.success('Added: ' + product.Name);
}

function renderCart() {
    var tbody = document.getElementById('cartBody');
    if (!cart.length) {
        tbody.innerHTML = '<tr id="emptyCart"><td colspan="7" class="text-center text-muted" style="padding:28px">' +
            '<i class="bi bi-cart" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.3"></i>Cart is empty</td></tr>';
        recalculate();
        return;
    }
    var html = '';
    cart.forEach(function (item, i) {
        var gstAmt = (item.price * item.quantity * item.gst_percent) / 100;
        var total = item.price * item.quantity + gstAmt;
        html += '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td>' + esc(item.name) + '<br><small class="text-muted font-mono">' + esc(item.barcode || '') + '</small></td>' +
            '<td>' + fmt(item.price) + '</td>' +
            '<td>' +
            '<div class="qty-control">' +
            '<button class="qty-btn" onclick="changeQty(' + i + ',-1)">-</button>' +
            '<input class="qty-input" type="number" value="' + item.quantity + '" min="1" onchange="setQty(' + i + ',this.value)">' +
            '<button class="qty-btn" onclick="changeQty(' + i + ',1)">+</button>' +
            '</div>' +
            '</td>' +
            '<td>' + fmt(gstAmt) + '</td>' +
            '<td class="font-bold">' + fmt(total) + '</td>' +
            '<td><button class="btn btn-ghost btn-sm btn-icon" style="color:#dc2626" onclick="removeCartItem(' + i + ')"><i class="bi bi-x"></i></button></td>' +
            '</tr>';
    });
    tbody.innerHTML = html;
    recalculate();
}

function changeQty(index, delta) {
    cart[index].quantity = Math.max(1, cart[index].quantity + delta);
    renderCart();
}
function setQty(index, val) {
    cart[index].quantity = Math.max(1, parseInt(val) || 1);
    renderCart();
}
function removeCartItem(index) {
    cart.splice(index, 1);
    renderCart();
}
function clearCart() {
    if (cart.length && !confirm('Clear all items from cart?')) return;
    cart = [];
    renderCart();
}

function recalculate() {
    var subtotal = 0, gstTotal = 0;
    cart.forEach(function (item) {
        subtotal += item.price * item.quantity;
        gstTotal += (item.price * item.quantity * item.gst_percent) / 100;
    });
    var discount = parseFloat(document.getElementById('discountInput').value || 0);
    var net = subtotal + gstTotal - discount;
    var final = Math.round(net);

    setText('subTotal', fmt(subtotal));
    setText('gstTotal', fmt(gstTotal));
    setText('netTotal', fmt(net));
    setText('finalTotal', fmt(final));
}

function setText(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val;
}

function selectPayment(btn) {
    document.querySelectorAll('.payment-btn').forEach(function (b) { b.classList.remove('active'); });
    btn.classList.add('active');
    selectedPayment = btn.dataset.mode;
}

function createBill() {
    if (!cart.length) { Toast.error('Cart is empty'); return; }
    var discount = parseFloat(document.getElementById('discountInput').value || 0);
    var data = {
        customer_name: document.getElementById('customerName').value || 'Walk-in Customer',
        customer_phone: document.getElementById('customerPhone').value || '',
        payment_mode: selectedPayment,
        discount: discount,
        items: cart.map(function (item) { return { product_id: item.product_id, quantity: item.quantity }; })
    };

    Loading.show();
    API.post('billing', 'store', null, data, function (res) {
        Loading.hide();
        if (res.status === 'success') {
            lastBillData = res.data;
            Toast.success('Bill created: ' + res.data.bill_no);
            printReceipt(res.data);
            cart = [];
            renderCart();
            var bno = generateLocalBillNo();
            var el = document.getElementById('billNoDisplay');
            if (el) el.textContent = 'Bill No: ' + bno;
        } else {
            Toast.error(res.message || 'Failed to create bill');
        }
    });
}

function printReceipt(bill) {
    var html = buildReceiptHTML(bill);
    var frame = document.getElementById('printFrame');
    frame.contentDocument.open();
    frame.contentDocument.write(html);
    frame.contentDocument.close();
    setTimeout(function () { frame.contentWindow.print(); }, 500);
}

function printLastBill() {
    if (!lastBillData) { Toast.warning('No bill to print'); return; }
    printReceipt(lastBillData);
}

function buildReceiptHTML(bill) {
    var items = bill.items || [];
    var rows = '';
    items.forEach(function (item) {
        rows += '<tr><td>' + esc(item.name) + ' x' + item.qty + '</td><td style="text-align:right">' + fmt(item.item_total) + '</td></tr>';
    });

    return '<!DOCTYPE html><html><head><title>Receipt</title>' +
        '<style>body{font-family:Courier New,monospace;font-size:12px;margin:0;padding:10px;max-width:300px}' +
        'h2{font-size:14px;text-align:center;margin:0}' +
        '.center{text-align:center}.line{border-top:1px dashed #999;margin:6px 0}' +
        'table{width:100%;border-collapse:collapse}' +
        '.total{font-size:14px;font-weight:bold}' +
        '</style></head><body onload="window.print()">' +
        '<div class="center"><h2>' + APP.companyName + '</h2>' +
        '<div>' + APP.companyAddress + '</div>' +
        '<div>Ph: ' + APP.companyPhone + '</div></div>' +
        '<div class="line"></div>' +
        '<div>Bill No: ' + esc(bill.bill_no) + '</div>' +
        '<div>Date: ' + esc(bill.date) + '</div>' +
        '<div>Customer: ' + esc(bill.customer_name || 'Walk-in') + '</div>' +
        '<div class="line"></div>' +
        '<table><thead><tr><th style="text-align:left">Item</th><th style="text-align:right">Amt</th></tr></thead><tbody>' + rows + '</tbody></table>' +
        '<div class="line"></div>' +
        '<table><tr><td>Subtotal</td><td style="text-align:right">' + fmt(bill.subtotal) + '</td></tr>' +
        '<tr><td>GST</td><td style="text-align:right">' + fmt(bill.gst_amount) + '</td></tr>' +
        (bill.discount ? '<tr><td>Discount</td><td style="text-align:right">-' + fmt(bill.discount) + '</td></tr>' : '') +
        '<tr class="total"><td><b>TOTAL</b></td><td style="text-align:right"><b>' + fmt(bill.final_amount) + '</b></td></tr></table>' +
        '<div class="line"></div>' +
        '<div class="center">Payment: ' + esc(bill.payment_mode) + '</div>' +
        '<div class="center" style="margin-top:8px">Thank you! Visit again.</div>' +
        '</body></html>';
}

// ========== GST BILLING ==========
var gstCart = [];
var gstSelectedPayment = 'Cash';
var isInterState = false;

document.addEventListener('DOMContentLoaded', function () {
    var inp2 = document.getElementById('gstBarcodeInput');
    if (inp2) {
        inp2.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') gstSearchBarcode();
        });
    }
});

function toggleInterState() {
    isInterState = document.getElementById('interStateToggle').checked;
    var cgst = document.getElementById('cgstRow');
    var sgst = document.getElementById('sgstRow');
    var igst = document.getElementById('igstRow');
    if (cgst) cgst.style.display = isInterState ? 'none' : '';
    if (sgst) sgst.style.display = isInterState ? 'none' : '';
    if (igst) igst.style.display = isInterState ? '' : 'none';
    gstRecalculate();
}

function gstSearchBarcode() {
    var val = document.getElementById('gstBarcodeInput').value.trim();
    if (!val) return;

    Loading.show();
    API.get('products', 'barcode', { barcode: val }, function (res) {
        Loading.hide();
        if (res.status === 'success' && res.data) {
            gstAddToCart(res.data);
            document.getElementById('gstBarcodeInput').value = '';
        } else {
            gstSearchByName(val);
        }
    });
}

function gstSearchByName(name) {
    Loading.show();
    API.get('products', 'index', null, function (res) {
        Loading.hide();
        if (res.status !== 'success') return;
        var q = name.toLowerCase();
        var results = (res.data || []).filter(function (p) {
            return p.Name && p.Name.toLowerCase().indexOf(q) > -1;
        });
        if (results.length === 1) { gstAddToCart(results[0]); return; }
        var tbody = document.getElementById('gstSearchBody');
        var html = '';
        results.slice(0, 8).forEach(function (p) {
            html += '<tr><td>' + esc(p.Name) + '</td><td>' + fmt(p.Price) + '</td>' +
                '<td>' + p.GST_Percent + '%</td><td>' + p.Quantity + '</td>' +
                '<td><button class="btn btn-primary btn-sm" onclick="gstAddById(\'' + esc(p.ID) + '\')">Add</button></td></tr>';
        });
        tbody.innerHTML = html;
        window._gstSearchProducts = results;
        document.getElementById('gstSearchResults').style.display = '';
    });
}

function gstAddById(id) {
    var products = window._gstSearchProducts || [];
    var p = products.find(function (x) { return x.ID === id; });
    if (p) gstAddToCart(p);
    document.getElementById('gstSearchResults').style.display = 'none';
    document.getElementById('gstBarcodeInput').value = '';
}

function gstAddToCart(product) {
    var existing = null;
    gstCart.forEach(function (item) { if (item.product_id === product.ID) existing = item; });
    if (existing) { existing.quantity++; }
    else {
        gstCart.push({
            product_id: product.ID,
            name: product.Name,
            price: parseFloat(product.Price || 0),
            gst_percent: parseFloat(product.GST_Percent || 18),
            hsn_code: '',
            quantity: 1
        });
    }
    renderGSTCart();
}

function renderGSTCart() {
    var tbody = document.getElementById('gstCartBody');
    if (!gstCart.length) {
        tbody.innerHTML = '<tr id="gstEmptyCart"><td colspan="11" class="text-center text-muted" style="padding:28px"><i class="bi bi-file-earmark" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.3"></i>No items</td></tr>';
        gstRecalculate(); return;
    }
    var html = '';
    gstCart.forEach(function (item, i) {
        var taxable = item.price * item.quantity;
        var halfGST = item.gst_percent / 2;
        var cgstAmt = isInterState ? 0 : (taxable * halfGST / 100);
        var sgstAmt = isInterState ? 0 : (taxable * halfGST / 100);
        var igstAmt = isInterState ? (taxable * item.gst_percent / 100) : 0;
        var rowTotal = taxable + cgstAmt + sgstAmt + igstAmt;

        html += '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td>' + esc(item.name) + '</td>' +
            '<td><input class="form-control" style="width:70px;padding:3px 6px;font-size:.75rem" value="' + esc(item.hsn_code) + '" onchange="gstCart[' + i + '].hsn_code=this.value"></td>' +
            '<td>' + fmt(item.price) + '</td>' +
            '<td><div class="qty-control"><button class="qty-btn" onclick="gstChangeQty(' + i + ',-1)">-</button><input class="qty-input" type="number" value="' + item.quantity + '" onchange="gstSetQty(' + i + ',this.value)"><button class="qty-btn" onclick="gstChangeQty(' + i + ',1)">+</button></div></td>' +
            '<td>' + fmt(taxable) + '</td>' +
            '<td>' + fmt(cgstAmt) + '</td>' +
            '<td>' + fmt(sgstAmt) + '</td>' +
            '<td>' + fmt(igstAmt) + '</td>' +
            '<td class="font-bold">' + fmt(rowTotal) + '</td>' +
            '<td><button class="btn btn-ghost btn-sm btn-icon" style="color:#dc2626" onclick="gstRemoveItem(' + i + ')"><i class="bi bi-x"></i></button></td>' +
            '</tr>';
    });
    tbody.innerHTML = html;
    gstRecalculate();
}

function gstChangeQty(i, d) { gstCart[i].quantity = Math.max(1, gstCart[i].quantity + d); renderGSTCart(); }
function gstSetQty(i, v) { gstCart[i].quantity = Math.max(1, parseInt(v) || 1); renderGSTCart(); }
function gstRemoveItem(i) { gstCart.splice(i, 1); renderGSTCart(); }
function clearGSTCart() { if (gstCart.length && !confirm('Clear cart?')) return; gstCart = []; renderGSTCart(); }

function gstRecalculate() {
    var taxable = 0, cgst = 0, sgst = 0, igst = 0;
    gstCart.forEach(function (item) {
        var t = item.price * item.quantity;
        taxable += t;
        if (isInterState) igst += (t * item.gst_percent / 100);
        else { cgst += (t * (item.gst_percent / 2) / 100); sgst += (t * (item.gst_percent / 2) / 100); }
    });
    var totalGST = cgst + sgst + igst;
    var discount = parseFloat((document.getElementById('gstDiscountInput') || {}).value || 0);
    var final = Math.round(taxable + totalGST - discount);

    setText('gstTaxable', fmt(taxable));
    setText('gstCGST', fmt(cgst));
    setText('gstSGST', fmt(sgst));
    setText('gstIGST', fmt(igst));
    setText('gstTotalGST', fmt(totalGST));
    setText('gstFinal', fmt(final));
}

function selectGSTPayment(btn) {
    document.querySelectorAll('#gstPaymentBtns .payment-btn, .bill-summary .payment-btn').forEach(function (b) { b.classList.remove('active'); });
    btn.classList.add('active');
    gstSelectedPayment = btn.dataset.mode;
}

function createGSTBill() {
    if (!gstCart.length) { Toast.error('No items added'); return; }
    var name = (document.getElementById('gstCustomerName') || {}).value || '';
    if (!name.trim()) { Toast.error('Customer name is required'); return; }

    var data = {
        customer_name: name,
        customer_gstin: (document.getElementById('gstCustomerGSTIN') || {}).value || '',
        customer_address: (document.getElementById('gstCustomerAddress') || {}).value || '',
        customer_state: (document.getElementById('gstCustomerState') || {}).value || '',
        payment_mode: gstSelectedPayment,
        inter_state: isInterState ? 1 : 0,
        discount: parseFloat((document.getElementById('gstDiscountInput') || {}).value || 0),
        items: gstCart.map(function (item) {
            return { product_id: item.product_id, quantity: item.quantity, hsn_code: item.hsn_code || '' };
        })
    };

    Loading.show();
    API.post('gst', 'store', null, data, function (res) {
        Loading.hide();
        if (res.status === 'success') {
            Toast.success('GST Invoice created: ' + res.data.bill_no);
            window._lastGSTBill = res.data;
            gstCart = [];
            renderGSTCart();
        } else {
            Toast.error(res.message || 'Failed to create GST invoice');
        }
    });
}

function printGSTInvoice() {
    if (!window._lastGSTBill) { Toast.warning('No GST invoice to print'); return; }
    var bill = window._lastGSTBill;
    var rows = '';
    (bill.items || []).forEach(function (item) {
        rows += '<tr><td>' + esc(item.name) + '</td><td>' + esc(item.hsn_code || '') + '</td>' +
            '<td>' + fmt(item.price) + '</td><td>' + item.qty + '</td>' +
            '<td>' + fmt(item.taxable_amount) + '</td>' +
            '<td>' + fmt(item.cgst_amount) + '</td><td>' + fmt(item.sgst_amount) + '</td>' +
            '<td>' + fmt(item.igst_amount) + '</td>' +
            '<td>' + fmt(item.taxable_amount + item.cgst_amount + item.sgst_amount + item.igst_amount) + '</td></tr>';
    });

    var html = '<!DOCTYPE html><html><head><title>GST Invoice</title>' +
        '<style>body{font-family:Arial,sans-serif;font-size:12px;margin:20px}' +
        'table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:6px}' +
        'th{background:#f5f5f5;font-size:11px}.header{margin-bottom:16px}.right{text-align:right}.bold{font-weight:700}' +
        '</style></head><body onload="window.print()">' +
        '<div class="header" style="display:grid;grid-template-columns:1fr auto;gap:20px">' +
        '<div><h2 style="margin:0">' + APP.companyName + '</h2>' +
        '<div>' + APP.companyAddress + '</div>' +
        '<div>GSTIN: ' + APP.companyGSTIN + '</div></div>' +
        '<div style="text-align:right"><div style="border:2px solid #333;padding:8px 12px;font-weight:700;font-size:14px">TAX INVOICE</div>' +
        '<div>No: ' + esc(bill.bill_no) + '</div><div>Date: ' + esc(bill.date) + '</div></div></div>' +
        '<hr><div style="margin-bottom:12px"><b>Bill To:</b><br>' +
        esc(bill.customer_name) + '<br>' +
        (bill.customer_gstin ? 'GSTIN: ' + esc(bill.customer_gstin) + '<br>' : '') +
        '</div>' +
        '<table><thead><tr><th>Product</th><th>HSN</th><th>Price</th><th>Qty</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total</th></tr></thead><tbody>' + rows + '</tbody></table>' +
        '<div class="right" style="margin-top:12px">' +
        '<div>Taxable Amount: ' + fmt(bill.taxable_amount) + '</div>' +
        '<div>CGST: ' + fmt(bill.cgst) + '</div>' +
        '<div>SGST: ' + fmt(bill.sgst) + '</div>' +
        '<div>IGST: ' + fmt(bill.igst) + '</div>' +
        '<div class="bold" style="font-size:14px;margin-top:6px">Total: ' + fmt(bill.final_amount) + '</div>' +
        '</div><div style="margin-top:16px;font-size:11px;color:#666;text-align:center">This is a computer generated invoice</div>' +
        '</body></html>';

    var frame = document.getElementById('gstPrintFrame');
    frame.contentDocument.open();
    frame.contentDocument.write(html);
    frame.contentDocument.close();
    setTimeout(function () { frame.contentWindow.print(); }, 500);
}

// ========== STOCK PAGE ==========
function loadStock() {
    Loading.show();
    API.get('stock', 'summary', null, function (res) {
        Loading.hide();
        if (res.status === 'success') {
            var s = res.data || {};
            setText('inStockCount', s.in_stock || 0);
            setText('lowStockCount', s.low_stock || 0);
            setText('outStockCount', s.out_of_stock || 0);
        }
    });
    API.get('stock', 'index', null, function (res) {
        if (res.status !== 'success') return;
        var tbody = document.getElementById('stockTableBody');
        if (!tbody) return;
        var products = res.data || [];
        if (!products.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding:28px">No products found</td></tr>'; return; }
        var html = '';
        products.forEach(function (p) {
            var bclass = p.Stock_Status === 'Out of Stock' ? 'badge-danger' : p.Stock_Status === 'Low Stock' ? 'badge-warning' : 'badge-success';
            html += '<tr>' +
                '<td class="font-bold">' + esc(p.Name || '') + '</td>' +
                '<td><span class="badge badge-gray">' + esc(p.Category || '') + '</span></td>' +
                '<td><span class="font-mono" style="font-size:.78rem">' + esc(p.Barcode || '') + '</span></td>' +
                '<td>' + (p.Quantity || 0) + ' ' + esc(p.Unit || '') + '</td>' +
                '<td><span class="badge ' + bclass + '">' + esc(p.Stock_Status || '') + '</span></td>' +
                '<td><button class="btn btn-outline btn-sm" onclick="openAdjustModal(\'' + esc(p.ID) + '\',\'' + esc(p.Name) + '\')"><i class="bi bi-plus-minus"></i> Adjust</button></td>' +
                '</tr>';
        });
        tbody.innerHTML = html;
        initSearch('stockSearch', 'stockTableBody');
    });
    API.get('stock', 'log', null, function (res) {
        if (res.status !== 'success') return;
        var tbody = document.getElementById('stockLogBody');
        if (!tbody) return;
        var logs = res.data || [];
        if (!logs.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:16px">No log entries yet</td></tr>'; return; }
        var html = '';
        logs.slice(0, 30).forEach(function (l) {
            var chg = parseInt(l.Change || 0);
            var color = chg >= 0 ? 'color:var(--success)' : 'color:var(--danger)';
            html += '<tr>' +
                '<td style="font-size:.78rem">' + esc(l.Created_At || '') + '</td>' +
                '<td>' + esc(l.Product_Name || '') + '</td>' +
                '<td style="' + color + ';font-weight:600">' + (chg > 0 ? '+' : '') + chg + '</td>' +
                '<td><span class="badge badge-gray">' + esc(l.Type || '') + '</span></td>' +
                '<td>' + esc(l.Note || '') + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;
    });
}

function openAdjustModal(id, name) {
    document.getElementById('adjustProductId').value = id;
    document.getElementById('adjustProductName').textContent = 'Product: ' + name;
    document.getElementById('adjustQty').value = 1;
    document.getElementById('adjustNote').value = '';
    document.getElementById('adjustModal').classList.add('active');
    document.getElementById('adjustOverlay').classList.add('active');
}
function closeAdjustModal() {
    document.getElementById('adjustModal').classList.remove('active');
    document.getElementById('adjustOverlay').classList.remove('active');
}
function submitAdjustment() {
    var id = document.getElementById('adjustProductId').value;
    var qty = parseInt(document.getElementById('adjustQty').value || 0);
    var note = document.getElementById('adjustNote').value;
    if (!id) return;
    Loading.show();
    API.post('stock', 'adjust', null, { product_id: id, quantity: qty, note: note }, function (res) {
        Loading.hide(); closeAdjustModal();
        if (res.status === 'success') { Toast.success('Stock adjusted'); loadStock(); }
        else { Toast.error(res.message || 'Adjustment failed'); }
    });
}

// Click outside adjust modal
document.addEventListener('DOMContentLoaded', function () {
    var bo = document.getElementById('adjustOverlay');
    if (bo) bo.addEventListener('click', closeAdjustModal);
});

// ========== REPORTS PAGE ==========
function switchTab(tab, btn) {
    document.querySelectorAll('.report-tab').forEach(function (b) {
        b.style.borderBottom = '';
        b.style.color = '';
    });
    btn.style.borderBottom = '2px solid var(--primary)';
    btn.style.color = 'var(--primary)';

    document.getElementById('tab-monthly').style.display = 'none';
    document.getElementById('tab-daterange').style.display = 'none';
    document.getElementById('tab-product').style.display = 'none';
    document.getElementById('tab-' + tab).style.display = '';
}

function loadMonthlyReport() {
    var month = document.getElementById('reportMonth').value;
    var year = document.getElementById('reportYear').value;
    Loading.show();
    API.get('reports', 'monthly', { month: month, year: year }, function (res) {
        Loading.hide();
        if (res.status !== 'success') { Toast.error('Failed to load report'); return; }
        var d = res.data;
        document.getElementById('monthlySummary').style.display = 'grid';
        document.getElementById('monthlyBillsCard').style.display = '';
        document.getElementById('monthlyTitle').textContent = 'Bills for ' + d.month;
        setText('mBillCount', d.bill_count || 0);
        setText('mNormalSales', fmt(d.total_normal_sales));
        setText('mGSTSales', fmt(d.total_gst_sales));

        var all = (d.normal_bills || []).concat(d.gst_bills || []);
        var tbody = document.getElementById('monthlyBillsBody');
        if (!all.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:20px">No bills for this month</td></tr>';
            return;
        }
        tbody.innerHTML = all.map(function (b) {
            return '<tr><td class="font-mono" style="font-size:.8rem">' + esc(b.Bill_No || b.GST_Bill_No || '') + '</td>' +
                '<td>' + esc(b.Date || '') + '</td>' +
                '<td>' + esc(b.Customer_Name || '') + '</td>' +
                '<td class="font-bold">' + fmt(b.Final_Amount) + '</td>' +
                '<td><span class="badge badge-gray">' + esc(b.Payment_Mode || '') + '</span></td></tr>';
        }).join('');
    });
}

function loadDateRangeReport() {
    var from = document.getElementById('dateFrom').value;
    var to = document.getElementById('dateTo').value;
    Loading.show();
    API.get('reports', 'date-range', { from: from, to: to }, function (res) {
        Loading.hide();
        if (res.status !== 'success') { Toast.error('Failed'); return; }
        var d = res.data;
        document.getElementById('dateRangeCard').style.display = '';
        document.getElementById('dateRangeTitle').textContent = 'Bills: ' + from + ' to ' + to;
        document.getElementById('dateRangeTotal').textContent = 'Total: ' + fmt(d.total_sales);

        var all = (d.normal_bills || []).concat(d.gst_bills || []);
        var tbody = document.getElementById('dateRangeBody');
        if (!all.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:20px">No bills found</td></tr>'; return; }
        tbody.innerHTML = all.map(function (b) {
            return '<tr><td class="font-mono" style="font-size:.8rem">' + esc(b.Bill_No || b.GST_Bill_No || '') + '</td>' +
                '<td>' + esc(b.Date || '') + '</td>' +
                '<td>' + esc(b.Customer_Name || '') + '</td>' +
                '<td class="font-bold">' + fmt(b.Final_Amount) + '</td>' +
                '<td><span class="badge badge-gray">' + (b.GST_Bill_No ? 'GST' : 'Normal') + '</span></td></tr>';
        }).join('');
    });
}

function loadProductReport() {
    Loading.show();
    API.get('reports', 'product-wise', null, function (res) {
        Loading.hide();
        if (res.status !== 'success') { Toast.error('Failed'); return; }
        var products = res.data || [];
        var tbody = document.getElementById('productReportBody');
        if (!products.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:20px">No sales data</td></tr>'; return; }
        tbody.innerHTML = products.map(function (p, i) {
            return '<tr><td class="font-bold">' + (i + 1) + '</td>' +
                '<td>' + esc(p.product_name || '') + '</td>' +
                '<td>' + (p.total_qty || 0) + '</td>' +
                '<td class="font-bold text-success">' + fmt(p.total_revenue) + '</td>' +
                '<td>' + (p.bill_count || 0) + '</td></tr>';
        }).join('');
    });
}

function esc(s) { var d = document.createElement('div'); d.textContent = String(s || ''); return d.innerHTML; }
