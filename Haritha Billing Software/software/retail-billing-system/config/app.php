<?php
/**
 * Application Configuration
 */

define('APP_NAME', env('APP_NAME', 'Haritha Billing Software'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_URL', env('APP_URL', 'http://localhost/retail-billing-system'));
define('APP_DEBUG', env('APP_DEBUG', false));

// Company Info
define('COMPANY_NAME', env('COMPANY_NAME', 'Haritha Stores'));
define('COMPANY_ADDRESS', env('COMPANY_ADDRESS', '123, Main Street, Chennai - 600001'));
define('COMPANY_PHONE', env('COMPANY_PHONE', '+91 9876543210'));
define('COMPANY_EMAIL', env('COMPANY_EMAIL', 'info@harithastore.com'));
define('COMPANY_GSTIN', env('COMPANY_GSTIN', '33ABCDE1234F1Z5'));
define('CURRENCY_SYMBOL', env('CURRENCY_SYMBOL', '₹'));
define('DEFAULT_GST', env('DEFAULT_GST', 18));

// Google Sheets
define('GOOGLE_SPREADSHEET_ID', env('GOOGLE_SPREADSHEET_ID', ''));
define('GOOGLE_CLIENT_EMAIL', env('GOOGLE_CLIENT_EMAIL', ''));
define('GOOGLE_PRIVATE_KEY', env('GOOGLE_PRIVATE_KEY', ''));
define('GOOGLE_CREDENTIALS_PATH', BASE_PATH . '/config/credentials.json');

// Sheets Names
define('SHEET_PRODUCTS', 'Products');
define('SHEET_BILLS', 'Bills');
define('SHEET_GST_BILLS', 'GST_Bills');
define('SHEET_STOCK_LOG', 'Stock_Log');
define('SHEET_USERS', 'Users');

// Session
define('SESSION_LIFETIME', env('SESSION_LIFETIME', 7200));

// Admin (Demo)
define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));
define('ADMIN_PASSWORD', env('ADMIN_PASSWORD', 'admin@123'));

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
