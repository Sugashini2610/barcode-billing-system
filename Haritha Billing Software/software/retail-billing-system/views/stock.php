<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="section-header">
    <div>
        <h2>Stock Management</h2>
        <p>Monitor inventory levels and manage stock adjustments</p>
    </div>
    <button class="btn btn-outline btn-sm" onclick="loadStock()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
</div>

<!-- Stock Summary Cards -->
<div class="grid grid-3 mb-24" style="margin-bottom:20px">
    <div class="stat-card success">
        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
        <div class="stat-value" id="inStockCount">--</div>
        <div class="stat-label">In Stock</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="stat-value" id="lowStockCount">--</div>
        <div class="stat-label">Low Stock</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
        <div class="stat-value" id="outStockCount">--</div>
        <div class="stat-label">Out of Stock</div>
    </div>
</div>

<div class="grid grid-2">
    <!-- Stock Table -->
    <div class="card" style="grid-column: 1 / -1">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-layers"></i> All Products</span>
            <div class="search-box" style="width:220px">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" id="stockSearch" placeholder="Search...">
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Barcode</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Adjust</th>
                    </tr>
                </thead>
                <tbody id="stockTableBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding:32px">
                            <div class="spinner" style="margin:0 auto 10px"></div>Loading...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stock Log -->
    <div class="card" style="grid-column: 1 / -1">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-clock-history"></i> Recent Stock Activity</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Change</th>
                        <th>Type</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody id="stockLogBody">
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding:20px">Loading log...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal-overlay" id="adjustOverlay"></div>
<div class="modal-box" id="adjustModal">
    <div class="modal-header">
        <span class="modal-title">Adjust Stock</span>
        <button class="modal-close" onclick="closeAdjustModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
        <input type="hidden" id="adjustProductId">
        <div class="form-group">
            <label class="form-label" id="adjustProductName">Product</label>
        </div>
        <div class="form-group">
            <label class="form-label">Quantity Change (+ to add, - to reduce)</label>
            <input type="number" id="adjustQty" class="form-control" value="1" step="1">
        </div>
        <div class="form-group">
            <label class="form-label">Note (optional)</label>
            <input type="text" id="adjustNote" class="form-control" placeholder="Reason for adjustment">
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeAdjustModal()">Cancel</button>
        <button class="btn btn-primary" onclick="submitAdjustment()">Apply Adjustment</button>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>