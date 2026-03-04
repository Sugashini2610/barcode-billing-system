<?php
/**
 * Main Entry Point - Front Controller
 * Haritha Billing Software - Retail Billing System
 */

define('BASE_PATH', __DIR__);
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/Haritha%20Billing%20Software/software/retail-billing-system');

// Load environment variables
require_once BASE_PATH . '/core/helpers.php';
loadEnv(BASE_PATH . '/.env');

// Load core files
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/core/session.php';
require_once BASE_PATH . '/core/auth.php';
require_once BASE_PATH . '/core/router.php';
require_once BASE_PATH . '/core/validator.php';
require_once BASE_PATH . '/core/response.php';

// Start session
Session::start();

// Initialize router
$router = new Router();

// Define routes
$router->get('', 'dashboard');
$router->get('dashboard', 'dashboard');
$router->get('products', 'products');
$router->get('billing', 'billing');
$router->get('gst', 'gst');
$router->get('reports', 'reports');
$router->get('stock', 'stock');
$router->get('barcode', 'barcode');
$router->get('login', 'login');
$router->post('login', 'login');
$router->get('logout', 'logout');

// Dispatch the route
$router->dispatch();
