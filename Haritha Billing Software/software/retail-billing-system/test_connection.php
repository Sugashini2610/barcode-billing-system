<?php
define('BASE_PATH', __DIR__);
define('BASE_URL', 'http://localhost/Haritha%20Billing%20Software/software/retail-billing-system');

require_once 'core/helpers.php';
loadEnv(BASE_PATH . '/.env');
require_once 'config/app.php';
require_once 'config/constants.php';
require_once 'config/google_config.php';
require_once 'services/GoogleSheetsService.php';

echo '<pre style="font-family:monospace; font-size:14px; padding:20px;">';
echo "=== HARITHA BILLING - CONNECTION TEST ===\n\n";

// --- Step 1: Check .env values ---
echo "📋 STEP 1: Checking .env values...\n";
$spreadsheetId = GOOGLE_SPREADSHEET_ID;
$clientEmail = GOOGLE_CLIENT_EMAIL;
$privateKey = GOOGLE_PRIVATE_KEY;

echo "  Spreadsheet ID : " . ($spreadsheetId ? "✅ " . $spreadsheetId : "❌ MISSING") . "\n";
echo "  Client Email   : " . ($clientEmail ? "✅ " . $clientEmail : "❌ MISSING") . "\n";
echo "  Private Key    : " . ($privateKey ? "✅ Found (" . strlen($privateKey) . " chars)" : "❌ MISSING") . "\n\n";

// --- Step 2: Check credentials.json ---
echo "📋 STEP 2: Checking credentials.json...\n";
$credPath = GOOGLE_CREDENTIALS_PATH;
if (file_exists($credPath)) {
    $creds = json_decode(file_get_contents($credPath), true);
    if ($creds && isset($creds['private_key'])) {
        echo "  ✅ credentials.json found and valid.\n";
        echo "  Service Account: " . (isset($creds['client_email']) ? $creds['client_email'] : 'N/A') . "\n\n";
    } else {
        echo "  ❌ credentials.json found but INVALID (bad JSON or missing private_key)\n\n";
    }
} else {
    echo "  ⚠️  credentials.json NOT FOUND at: $credPath\n";
    echo "  Will fall back to .env values.\n\n";
}

// --- Step 3: Check OpenSSL private key ---
echo "📋 STEP 3: Checking private key format (OpenSSL)...\n";
$credentials = getGoogleCredentials();
$keyToTest = $credentials['private_key'];

// Fix common issue: literal \n in key string not being treated as newlines
if (strpos($keyToTest, "\\n") !== false && strpos($keyToTest, "\n") === false) {
    $keyToTest = str_replace("\\n", "\n", $keyToTest);
    echo "  ℹ️  Fixed escaped \\n newlines in private key.\n";
}

$pkeyResource = openssl_pkey_get_private($keyToTest);
if ($pkeyResource !== false) {
    echo "  ✅ Private key is valid and OpenSSL can parse it.\n\n";
} else {
    echo "  ❌ PRIVATE KEY ERROR: OpenSSL cannot parse the key!\n";
    echo "  OpenSSL Error: " . openssl_error_string() . "\n";
    echo "  Fix: Check that the private key in credentials.json or .env is complete and not corrupted.\n\n";
}

// --- Step 4: Try actual Google Sheets connection ---
echo "📋 STEP 4: Testing Google Sheets API connection...\n";
try {
    $sheets = new GoogleSheetsService();
    $products = $sheets->readSheet(SHEET_PRODUCTS);

    echo "  ✅ CONNECTION SUCCESSFUL!\n";
    echo "  Products sheet has " . count($products) . " data rows.\n\n";

    if (count($products) > 0) {
        echo "  First product row:\n";
        print_r($products[0]);
    } else {
        echo "  ℹ️  Products sheet is empty (only header row exists).\n";
        echo "  Add a product via the app, then test again.\n";
    }

} catch (Exception $e) {
    echo "  ❌ CONNECTION FAILED!\n";
    echo "  Error: " . $e->getMessage() . "\n\n";

    echo "--- TROUBLESHOOTING CHECKLIST ---\n";
    echo "  1. Is GOOGLE_SPREADSHEET_ID correct in .env?\n";
    echo "     Current: " . GOOGLE_SPREADSHEET_ID . "\n";
    echo "  2. Did you share the Google Sheet with the service account email?\n";
    echo "     Email: " . GOOGLE_CLIENT_EMAIL . "\n";
    echo "  3. Is credentials.json present in config/ folder?\n";
    echo "     Path: " . GOOGLE_CREDENTIALS_PATH . " -> " . (file_exists(GOOGLE_CREDENTIALS_PATH) ? '✅ EXISTS' : '❌ NOT FOUND') . "\n";
    echo "  4. Is Google Sheets API enabled in Cloud Console?\n";
    echo "     Visit: https://console.cloud.google.com/apis/library/sheets.googleapis.com\n";
    echo "  5. Is your internet/cURL working?\n";
    echo "     cURL enabled: " . (function_exists('curl_version') ? '✅ YES' : '❌ NO') . "\n";
}

// --- Step 5: Environment info ---
echo "\n📋 STEP 5: Server Environment Info\n";
echo "  PHP Version    : " . PHP_VERSION . "\n";
echo "  cURL Enabled   : " . (function_exists('curl_version') ? '✅ YES' : '❌ NO') . "\n";
echo "  OpenSSL Loaded : " . (extension_loaded('openssl') ? '✅ YES' : '❌ NO') . "\n";
echo "  BASE_PATH      : " . BASE_PATH . "\n";
echo "  .env path      : " . BASE_PATH . "/.env -> " . (file_exists(BASE_PATH . '/.env') ? '✅ EXISTS' : '❌ NOT FOUND') . "\n";

echo "\n=== TEST COMPLETE ===\n";
echo '</pre>';