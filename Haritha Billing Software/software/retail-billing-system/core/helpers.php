<?php
/**
 * Common Helper Functions - PHP 5.6+ compatible
 */

// PHP 7+ polyfills
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        $len = strlen($needle);
        return $len === 0 || substr($haystack, -$len) === $needle;
    }
}

/**
 * Load .env file
 */
function loadEnv($path)
{
    if (!file_exists($path))
        return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (substr(trim($line), 0, 1) === '#')
            continue;
        if (strpos($line, '=') === false)
            continue;
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = trim(isset($parts[1]) ? $parts[1] : '', " \t\n\r\0\x0B\"'");
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Get environment variable
 */
function env($key, $default = null)
{
    $val = isset($_ENV[$key]) ? $_ENV[$key] : getenv($key);
    return $val !== false ? $val : $default;
}

/**
 * Generate unique barcode
 */
function generateBarcode()
{
    return str_pad(mt_rand(1000000000, 9999999999), 13, '0', STR_PAD_LEFT);
}

/**
 * Generate bill number
 */
function generateBillNumber($prefix = '')
{
    if (empty($prefix))
        $prefix = defined('BILL_PREFIX') ? BILL_PREFIX : 'BILL';
    return $prefix . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Format currency
 */
function formatCurrency($amount)
{
    $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₹';
    return $symbol . number_format((float) $amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'd/m/Y')
{
    return date($format, strtotime($date));
}

/**
 * Current datetime
 */
function nowDateTime()
{
    return date('d/m/Y H:i:s');
}

/**
 * Calculate GST amount
 */
function calculateGST($amount, $gstPercent, $type = 'Exclusive')
{
    if ($type === 'Inclusive') {
        $base = ($amount * 100) / (100 + $gstPercent);
        $gst = $amount - $base;
        return array('base' => round($base, 2), 'gst' => round($gst, 2), 'total' => $amount);
    } else {
        $gst = ($amount * $gstPercent) / 100;
        $total = $amount + $gst;
        return array('base' => $amount, 'gst' => round($gst, 2), 'total' => round($total, 2));
    }
}

/**
 * Round off amount
 */
function roundOff($amount)
{
    $rounded = round($amount);
    $difference = $rounded - $amount;
    return array('original' => $amount, 'rounded' => $rounded, 'difference' => $difference);
}

/**
 * Sanitize input
 */
function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Logger
 */
function logMessage($message, $level = 'INFO')
{
    if (!defined('LOGS_PATH'))
        return;
    $logFile = LOGS_PATH . '/app-' . date('Y-m-d') . '.log';
    $timestamp = date('d/m/Y H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    if (!is_dir(LOGS_PATH))
        mkdir(LOGS_PATH, 0755, true);
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Get stock status
 */
function getStockStatus($quantity)
{
    $threshold = defined('STOCK_LOW_THRESHOLD') ? STOCK_LOW_THRESHOLD : 10;
    if ($quantity <= 0)
        return defined('STOCK_OUT') ? STOCK_OUT : 'Out of Stock';
    if ($quantity <= $threshold)
        return defined('STOCK_LOW') ? STOCK_LOW : 'Low Stock';
    return defined('STOCK_IN') ? STOCK_IN : 'In Stock';
}

/**
 * Generate CGST/SGST/IGST split
 */
function gstSplit($gstPercent, $interState = false)
{
    if ($interState) {
        return array('IGST' => $gstPercent, 'CGST' => 0, 'SGST' => 0);
    }
    $half = $gstPercent / 2;
    return array('IGST' => 0, 'CGST' => $half, 'SGST' => $half);
}

/**
 * Redirect helper
 */
function redirect($url)
{
    header("Location: $url");
    exit;
}

/**
 * Check if AJAX request
 */
function isAjax()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
