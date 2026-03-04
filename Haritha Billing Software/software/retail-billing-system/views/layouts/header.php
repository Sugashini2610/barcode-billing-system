<?php
$page = isset($_GET['url']) ? trim($_GET['url'], '/') : 'dashboard';
// Fix: trim trailing space from page variable
$page = trim($page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo COMPANY_NAME; ?> - Billing & Inventory Management">
    <title><?php echo APP_NAME; ?> | <?php echo ucfirst($page); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/app.css">
</head>

<body>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="bi bi-shop"></i></div>
            <div>
                <div class="brand-name"><?php echo COMPANY_NAME; ?></div>
                <div class="brand-sub">Billing System</div>
            </div>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section">
                <span class="nav-label">Main</span>
                <a href="<?php echo BASE_URL; ?>/dashboard"
                    class="nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-fill"></i> <span>Dashboard</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/billing"
                    class="nav-item <?php echo $page === 'billing' ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i> <span>New Bill</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/gst" class="nav-item <?php echo $page === 'gst' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-ruled"></i> <span>GST Invoice</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-label">Inventory</span>
                <a href="<?php echo BASE_URL; ?>/products"
                    class="nav-item <?php echo $page === 'products' ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam"></i> <span>Products</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/stock"
                    class="nav-item <?php echo $page === 'stock' ? 'active' : ''; ?>">
                    <i class="bi bi-layers"></i> <span>Stock</span>
                    <span class="nav-badge" id="lowStockBadge" style="display:none">!</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/barcode"
                    class="nav-item <?php echo $page === 'barcode' ? 'active' : ''; ?>"
                    style="<?php echo $page === 'barcode' ? '' : 'color:#7c3aed;'; ?>">
                    <i class="bi bi-upc-scan"></i> <span>Generate Barcode</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-label">Analytics</span>
                <a href="<?php echo BASE_URL; ?>/reports"
                    class="nav-item <?php echo $page === 'reports' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart-line"></i> <span>Reports</span>
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <div class="user-avatar"><i class="bi bi-person-fill"></i></div>
            <div class="user-info">
                <span class="user-name"><?php echo Session::get('username', 'Admin'); ?></span>
                <span class="user-role"><?php echo ucfirst(Session::get('role', 'admin')); ?></span>
            </div>
            <a href="<?php echo BASE_URL; ?>/logout" class="logout-btn" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>

    <!-- Main -->
    <div class="main-wrapper" id="mainWrapper">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
                <span class="page-title"><?php echo ucfirst($page); ?></span>
            </div>
            <div class="topbar-right">
                <span class="topbar-clock" id="topClock"></span>
                <span class="topbar-date" id="topDate"></span>
            </div>
        </header>

        <?php include BASE_PATH . '/views/partials/alerts.php'; ?>

        <main class="main-content">