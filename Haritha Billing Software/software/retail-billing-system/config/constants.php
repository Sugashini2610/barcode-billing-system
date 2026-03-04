<?php
/**
 * System-wide constants
 */

// Stock Status
define('STOCK_OUT', 'Out of Stock');
define('STOCK_LOW', 'Low Stock');
define('STOCK_IN', 'In Stock');
define('STOCK_LOW_THRESHOLD', 10);

// Payment Modes
define('PAYMENT_CASH', 'Cash');
define('PAYMENT_CARD', 'Card');
define('PAYMENT_UPI', 'UPI');
define('PAYMENT_CREDIT', 'Credit');

// Bill Types
define('BILL_NORMAL', 'Normal');
define('BILL_GST', 'GST');

// GST Types
define('GST_INCLUSIVE', 'Inclusive');
define('GST_EXCLUSIVE', 'Exclusive');

// Date Formats
define('DATE_FORMAT', 'd/m/Y');
define('DATE_TIME_FORMAT', 'd/m/Y H:i:s');
define('DATE_FORMAT_DB', 'Y-m-d');

// Pagination
define('ITEMS_PER_PAGE', 20);

// File Paths
define('STORAGE_PATH', BASE_PATH . '/storage');
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('TEMP_PATH', STORAGE_PATH . '/temp');
define('BACKUPS_PATH', STORAGE_PATH . '/backups');
define('VENDOR_PATH', BASE_PATH . '/public/vendor');

// Bill prefix
define('BILL_PREFIX', 'BILL');
define('GST_BILL_PREFIX', 'GSTB');
