<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="section-header">
    <div>
        <h2>Dashboard</h2>
        <p>Welcome back, <?php echo Session::get('username', 'Admin'); ?>. Here's your store overview.</p>
    </div>
    <button class="btn btn-outline btn-sm" onclick="loadDashboard()">
        <i class="bi bi-arrow-clockwise"></i> Refresh
    </button>
</div>

<!-- Stats (3 cards only) -->
<div class="grid grid-3 mb-24">
    <div class="stat-card success">
        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
        <div class="stat-value" id="statProducts">--</div>
        <div class="stat-label">Total Products</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="stat-value" id="statLow">--</div>
        <div class="stat-label">Low Stock</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
        <div class="stat-value" id="statOut">--</div>
        <div class="stat-label">Out of Stock</div>
    </div>
</div>

<!-- Stock Alerts & Top Selling Products Row -->
<div class="grid grid-2 mb-24" style="grid-template-columns:1fr 1fr">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-exclamation-diamond" style="color:#dc2626"></i> Stock Alerts</span>
            <a href="<?php echo BASE_URL; ?>/stock" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="card-body" id="stockAlerts" style="padding:12px 20px">
            <p class="text-muted" style="font-size:.82rem">Loading...</p>
        </div>
    </div>
    <div class="chart-card">
        <div class="chart-title">Top Selling Products</div>
        <div id="topProducts">
            <p class="text-muted" style="font-size:.82rem;padding:8px 0">Loading...</p>
        </div>
    </div>
</div>

<!-- Quick Actions Strip -->
<div class="grid grid-3 mb-24">
    <a href="<?php echo BASE_URL; ?>/barcode" class="stat-card"
        style="text-decoration:none;background:linear-gradient(135deg,#7c3aed 0%,#a855f7 100%);cursor:pointer;">
        <div class="stat-icon"><i class="bi bi-upc-scan" style="color:#fff;"></i></div>
        <div class="stat-value" style="color:#fff;font-size:1rem;">Generate</div>
        <div class="stat-label" style="color:rgba(255,255,255,.8);">Barcode Labels</div>
    </a>
    <a href="<?php echo BASE_URL; ?>/products" class="stat-card"
        style="text-decoration:none;background:linear-gradient(135deg,#0284c7 0%,#38bdf8 100%);cursor:pointer;">
        <div class="stat-icon"><i class="bi bi-plus-circle" style="color:#fff;"></i></div>
        <div class="stat-value" style="color:#fff;font-size:1rem;">Add Product</div>
        <div class="stat-label" style="color:rgba(255,255,255,.8);">Manage Inventory</div>
    </a>
    <a href="<?php echo BASE_URL; ?>/billing" class="stat-card"
        style="text-decoration:none;background:linear-gradient(135deg,#059669 0%,#34d399 100%);cursor:pointer;">
        <div class="stat-icon"><i class="bi bi-receipt" style="color:#fff;"></i></div>
        <div class="stat-value" style="color:#fff;font-size:1rem;">New Bill</div>
        <div class="stat-label" style="color:rgba(255,255,255,.8);">Start Billing</div>
    </a>
</div>

<!-- Recent Bills -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-receipt" style="color:#2563eb"></i> Recent Bills</span>
        <a href="<?php echo BASE_URL; ?>/billing" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Bill</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Bill No</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Mode</th>
                </tr>
            </thead>
            <tbody id="recentBillsBody">
                <tr>
                    <td colspan="5" class="text-center text-muted">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>