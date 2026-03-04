<?php
/**
 * API Entry Point - All AJAX requests go through here
 */

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/Haritha%20Billing%20Software/software/retail-billing-system');

require_once BASE_PATH . '/core/helpers.php';
loadEnv(BASE_PATH . '/.env');

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/config/google_config.php';
require_once BASE_PATH . '/core/session.php';
require_once BASE_PATH . '/core/auth.php';
require_once BASE_PATH . '/core/response.php';
require_once BASE_PATH . '/core/validator.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

Session::start();

// Require authentication for all API requests
if (!Auth::check()) {
    Response::unauthorized('Please login to access this API');
}

$method = $_SERVER['REQUEST_METHOD'];
$module = sanitize($_GET['module'] ?? '');
$action = sanitize($_GET['action'] ?? '');
$id = sanitize($_GET['id'] ?? '');

try {
    switch ($module) {
        // ============ PRODUCTS ============
        case 'products':
            require_once BASE_PATH . '/modules/products/ProductController.php';
            $ctrl = new ProductController();
            if ($action === 'index' && $method === 'GET')
                $ctrl->index();
            elseif ($action === 'show' && $method === 'GET')
                $ctrl->show($id);
            elseif ($action === 'barcode' && $method === 'GET')
                $ctrl->findByBarcode();
            elseif ($action === 'store' && $method === 'POST')
                $ctrl->store();
            elseif ($action === 'store-label' && $method === 'POST')
                $ctrl->storeLabel();
            elseif ($action === 'update' && $method === 'POST')
                $ctrl->update($id);
            elseif ($action === 'delete' && $method === 'POST')
                $ctrl->destroy($id);
            else
                Response::notFound("Unknown action: $action");
            break;

        // ============ BILLING ============
        case 'billing':
            require_once BASE_PATH . '/modules/billing/BillingController.php';
            $ctrl = new BillingController();
            if ($action === 'index' && $method === 'GET')
                $ctrl->index();
            elseif ($action === 'show' && $method === 'GET')
                $ctrl->show($id);
            elseif ($action === 'store' && $method === 'POST')
                $ctrl->store();
            elseif ($action === 'today-sales' && $method === 'GET')
                $ctrl->todaySales();
            else
                Response::notFound("Unknown action: $action");
            break;

        // ============ GST ============
        case 'gst':
            require_once BASE_PATH . '/modules/gst/GstController.php';
            $ctrl = new GstController();
            if ($action === 'index' && $method === 'GET')
                $ctrl->index();
            elseif ($action === 'store' && $method === 'POST')
                $ctrl->store();
            elseif ($action === 'show' && $method === 'GET')
                $ctrl->show($id);
            else
                Response::notFound("Unknown action: $action");
            break;

        // ============ STOCK ============
        case 'stock':
            require_once BASE_PATH . '/modules/stock/StockController.php';
            $ctrl = new StockController();
            if ($action === 'index' && $method === 'GET')
                $ctrl->index();
            elseif ($action === 'summary' && $method === 'GET')
                $ctrl->summary();
            elseif ($action === 'low' && $method === 'GET')
                $ctrl->lowStock();
            elseif ($action === 'out' && $method === 'GET')
                $ctrl->outOfStock();
            elseif ($action === 'log' && $method === 'GET')
                $ctrl->log();
            elseif ($action === 'adjust' && $method === 'POST')
                $ctrl->adjust();
            else
                Response::notFound("Unknown action: $action");
            break;

        // ============ DASHBOARD ============
        case 'dashboard':
            require_once BASE_PATH . '/modules/dashboard/DashboardController.php';
            $ctrl = new DashboardController();
            if ($action === 'index' && $method === 'GET')
                $ctrl->index();
            else
                Response::notFound("Unknown action: $action");
            break;

        // ============ REPORTS ============
        case 'reports':
            require_once BASE_PATH . '/modules/reports/ReportController.php';
            $ctrl = new ReportController();
            if ($action === 'monthly' && $method === 'GET')
                $ctrl->monthly();
            elseif ($action === 'date-range' && $method === 'GET')
                $ctrl->dateRange();
            elseif ($action === 'product-wise' && $method === 'GET')
                $ctrl->productWise();
            else
                Response::notFound("Unknown action: $action");
            break;

        default:
            Response::notFound("Unknown module: $module");
    }
} catch (Exception $e) {
    logMessage('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'ERROR');
    Response::error('Server error: ' . (APP_DEBUG ? $e->getMessage() : 'Internal server error'), 500);
}
