<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="section-header">
    <div>
        <h2>Products</h2>
        <p>Manage your product catalog and barcodes</p>
    </div>
    <button class="btn btn-primary" onclick="openAddProduct()">
        <i class="bi bi-plus"></i> Add Product
    </button>
</div>

<div class="card">
    <div class="card-header">
        <div class="search-box" style="width:260px">
            <i class="bi bi-search search-icon"></i>
            <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
        </div>
        <span class="text-muted" style="font-size:.82rem" id="productCount">Loading...</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Barcode</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>GST %</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding:32px">
                        <div class="spinner" style="margin:0 auto 10px"></div>Loading products...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>