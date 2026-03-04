/**
 * product.js — Product Management page
 */
var allProducts = [];

function loadProducts() {
    Loading.show();
    API.get('products', 'index', null, function (res) {
        Loading.hide();
        if (res.status !== 'success') { Toast.error('Failed to load products'); return; }
        allProducts = res.data || [];
        document.getElementById('productCount').textContent = allProducts.length + ' products';
        renderProductTable(allProducts);
        initSearch('productSearch', 'productTableBody');
    });
}

function renderProductTable(products) {
    var tbody = document.getElementById('productTableBody');
    if (!products.length) {
        tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="bi bi-box-seam"></i><p>No products found. Add your first product.</p></div></td></tr>';
        return;
    }
    var html = '';
    products.forEach(function (p) {
        var statusClass = p.Stock_Status === 'Out of Stock' ? 'badge-danger' :
            p.Stock_Status === 'Low Stock' ? 'badge-warning' : 'badge-success';
        html += '<tr>' +
            '<td><span class="font-mono" style="font-size:.75rem">' + esc(p.Barcode || '') + '</span></td>' +
            '<td class="font-bold">' + esc(p.Name || '') + '</td>' +
            '<td><span class="badge badge-gray">' + esc(p.Category || '') + '</span></td>' +
            '<td>' + fmt(p.Price) + '</td>' +
            '<td>' + (p.GST_Percent || 0) + '%</td>' +
            '<td>' + (p.Quantity || 0) + ' ' + esc(p.Unit || '') + '</td>' +
            '<td><span class="badge ' + statusClass + '">' + esc(p.Stock_Status || '') + '</span></td>' +
            '<td>' +
            '<button class="btn btn-ghost btn-sm btn-icon" onclick="viewBarcode(\'' + esc(p.ID) + '\')" title="View Barcode"><i class="bi bi-upc"></i></button> ' +
            '<button class="btn btn-ghost btn-sm btn-icon" onclick="openEditProduct(\'' + esc(p.ID) + '\')" title="Edit"><i class="bi bi-pencil"></i></button> ' +
            '<button class="btn btn-ghost btn-sm btn-icon" style="color:#dc2626" onclick="deleteProduct(\'' + esc(p.ID) + '\')" title="Delete"><i class="bi bi-trash"></i></button>' +
            '</td>' +
            '</tr>';
    });
    tbody.innerHTML = html;
}

// Add Product
function openAddProduct() {
    Modal.open('Add New Product',
        '<div class="form-row">' +
        '<div class="form-group"><label class="form-label">Product Name *</label><input id="mp_name" class="form-control" placeholder="Product name"></div>' +
        '<div class="form-group"><label class="form-label">Category</label><input id="mp_category" class="form-control" value="General"></div>' +
        '</div>' +
        '<div class="form-row">' +
        '<div class="form-group"><label class="form-label">Price (₹) *</label><input id="mp_price" type="number" class="form-control" min="0" step="0.01"></div>' +
        '<div class="form-group"><label class="form-label">GST %</label><input id="mp_gst" type="number" class="form-control" value="18" min="0"></div>' +
        '</div>' +
        '<div class="form-row">' +
        '<div class="form-group"><label class="form-label">Quantity *</label><input id="mp_qty" type="number" class="form-control" value="0" min="0"></div>' +
        '<div class="form-group"><label class="form-label">Unit</label><input id="mp_unit" class="form-control" value="Piece"></div>' +
        '</div>' +
        '<div class="form-group"><label class="form-label">Description</label><input id="mp_desc" class="form-control" placeholder="Optional"></div>',
        '<button class="btn btn-outline" onclick="Modal.close()">Cancel</button>' +
        '<button class="btn btn-primary" onclick="submitAddProduct()"><i class="bi bi-check"></i> Add Product</button>'
    );
}

function submitAddProduct() {
    var name = document.getElementById('mp_name').value.trim();
    var price = document.getElementById('mp_price').value;
    var qty = document.getElementById('mp_qty').value;
    if (!name || !price) { Toast.error('Name and Price are required'); return; }

    Loading.show();
    API.post('products', 'store', null, {
        name: name,
        category: document.getElementById('mp_category').value,
        price: price,
        gst_percent: document.getElementById('mp_gst').value,
        quantity: qty,
        unit: document.getElementById('mp_unit').value,
        description: document.getElementById('mp_desc').value
    }, function (res) {
        Loading.hide();
        Modal.close();
        if (res.status === 'success') { Toast.success('Product added successfully!'); loadProducts(); }
        else { Toast.error(res.message || 'Failed to add product'); }
    });
}

// Edit Product
function openEditProduct(id) {
    var p = allProducts.find(function (x) { return x.ID === id; });
    if (!p) { Toast.error('Product not found'); return; }

    Modal.open('Edit Product',
        '<div class="form-row">' +
        '<div class="form-group"><label class="form-label">Product Name</label><input id="ep_name" class="form-control" value="' + esc(p.Name) + '"></div>' +
        '<div class="form-group"><label class="form-label">Category</label><input id="ep_category" class="form-control" value="' + esc(p.Category) + '"></div>' +
        '</div>' +
        '<div class="form-row">' +
        '<div class="form-group"><label class="form-label">Price (₹)</label><input id="ep_price" type="number" class="form-control" value="' + p.Price + '" step="0.01"></div>' +
        '<div class="form-group"><label class="form-label">GST %</label><input id="ep_gst" type="number" class="form-control" value="' + p.GST_Percent + '"></div>' +
        '</div>' +
        '<div class="form-row">' +
        '<div class="form-group"><label class="form-label">Quantity</label><input id="ep_qty" type="number" class="form-control" value="' + p.Quantity + '"></div>' +
        '<div class="form-group"><label class="form-label">Unit</label><input id="ep_unit" class="form-control" value="' + esc(p.Unit) + '"></div>' +
        '</div>' +
        '<div class="form-group"><label class="form-label">Description</label><input id="ep_desc" class="form-control" value="' + esc(p.Description || '') + '"></div>',
        '<button class="btn btn-outline" onclick="Modal.close()">Cancel</button>' +
        '<button class="btn btn-primary" onclick="submitEditProduct(\'' + id + '\')"><i class="bi bi-check"></i> Save</button>'
    );
}

function submitEditProduct(id) {
    Loading.show();
    API.post('products', 'update', id, {
        name: document.getElementById('ep_name').value,
        category: document.getElementById('ep_category').value,
        price: document.getElementById('ep_price').value,
        gst_percent: document.getElementById('ep_gst').value,
        quantity: document.getElementById('ep_qty').value,
        unit: document.getElementById('ep_unit').value,
        description: document.getElementById('ep_desc').value
    }, function (res) {
        Loading.hide(); Modal.close();
        if (res.status === 'success') { Toast.success('Product updated!'); loadProducts(); }
        else { Toast.error(res.message || 'Update failed'); }
    });
}

// Delete
function deleteProduct(id) {
    if (!confirm('Delete this product? This cannot be undone.')) return;
    Loading.show();
    API.post('products', 'delete', id, {}, function (res) {
        Loading.hide();
        if (res.status === 'success') { Toast.success('Product deleted'); loadProducts(); }
        else { Toast.error(res.message || 'Delete failed'); }
    });
}

// View Barcode
function viewBarcode(id) {
    var p = allProducts.find(function (x) { return x.ID === id; });
    if (!p) return;
    Modal.open('Barcode — ' + p.Name,
        '<div class="barcode-wrap">' +
        '<img src="' + (p.Barcode_Image || '') + '" alt="barcode" style="max-width:240px">' +
        '<div class="barcode-num">' + p.Barcode + '</div>' +
        '</div>',
        '<button class="btn btn-outline" onclick="Modal.close()">Close</button>' +
        '<button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>'
    );
}

function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// Init
document.addEventListener('DOMContentLoaded', function () {
    if (APP.page === 'products') loadProducts();
});
