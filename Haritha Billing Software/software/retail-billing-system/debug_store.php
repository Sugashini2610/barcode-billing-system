<?php
/**
 * debug_store.php — Check actual tab names + write test
 */

define('BASE_PATH', __DIR__);
define('BASE_URL', 'http://localhost/Haritha%20Billing%20Software/software/retail-billing-system');

require_once 'core/helpers.php';
loadEnv(BASE_PATH . '/.env');
require_once 'config/app.php';
require_once 'config/constants.php';
require_once 'config/google_config.php';

class DebugSheetsService
{
    public $spreadsheetId;
    private $accessToken = '';
    private $tokenExpiry = 0;
    public $lastRawResponse = '';

    public function __construct()
    {
        $this->spreadsheetId = getSpreadsheetId();
    }

    public function getToken()
    {
        if ($this->accessToken && time() < $this->tokenExpiry - 60)
            return $this->accessToken;
        $credentials = getGoogleCredentials();
        $privateKey = $credentials['private_key'];
        $clientEmail = $credentials['client_email'];
        if (strpos($privateKey, "\\n") !== false && strpos($privateKey, "\n") === false) {
            $privateKey = str_replace("\\n", "\n", $privateKey);
        }
        $now = time();
        $pay = array('iss' => $clientEmail, 'scope' => GOOGLE_SCOPE, 'aud' => GOOGLE_TOKEN_URL, 'exp' => $now + 3600, 'iat' => $now);
        $h = $this->b64u(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
        $b = $this->b64u(json_encode($pay));
        openssl_sign("$h.$b", $sig, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = "$h.$b." . $this->b64u($sig);
        $resp = $this->curlPost(GOOGLE_TOKEN_URL, http_build_query(array('grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt)), '', 'application/x-www-form-urlencoded');
        if (!isset($resp['access_token']))
            throw new Exception('Token error: ' . json_encode($resp));
        $this->accessToken = $resp['access_token'];
        $this->tokenExpiry = $now + (isset($resp['expires_in']) ? $resp['expires_in'] : 3600);
        return $this->accessToken;
    }

    public function getSheetTabs()
    {
        $token = $this->getToken();
        $url = GOOGLE_SHEETS_API_BASE . "/{$this->spreadsheetId}?fields=sheets.properties";
        $resp = $this->curlGet($url, $token);
        return $resp;
    }

    public function tryReadRaw($tab)
    {
        $token = $this->getToken();
        $url = GOOGLE_SHEETS_API_BASE . "/{$this->spreadsheetId}/values/{$tab}!A:Z";
        $ch = curl_init($url);
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => array("Authorization: Bearer $token"), CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false));
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array('http' => $code, 'body' => $raw);
    }

    public function tryWriteRaw($tab, $data)
    {
        $token = $this->getToken();
        $url = GOOGLE_SHEETS_API_BASE . "/{$this->spreadsheetId}/values/{$tab}!A1:append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS";
        $body = json_encode(array('values' => array(array_values($data))));
        $headers = array("Content-Type: application/json", "Authorization: Bearer $token");
        $ch = curl_init($url);
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false));
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->lastRawResponse = "HTTP $code\n$raw";
        return json_decode($raw, true) ?: array();
    }

    private function curlGet($url, $token)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => array("Authorization: Bearer $token"), CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false));
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw, true) ?: array();
    }

    private function curlPost($url, $body, $token, $ct)
    {
        $h = array("Content-Type: $ct");
        if ($token)
            $h[] = "Authorization: Bearer $token";
        $ch = curl_init($url);
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $h, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false));
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw, true) ?: array();
    }

    private function b64u($d)
    {
        return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
    }
}

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>Tab Checker</title>';
echo '<style>body{font-family:monospace;padding:20px;font-size:13px;background:#f8f9fa;}
.ok{color:#16a34a;font-weight:bold;}.err{color:#dc2626;font-weight:bold;}.warn{color:#d97706;}
.box{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:10px 0;}
h2{color:#1e293b;border-bottom:2px solid #7c3aed;padding-bottom:6px;}
pre{background:#1e293b;color:#a5f3fc;padding:12px;border-radius:6px;overflow:auto;white-space:pre-wrap;word-break:break-all;font-size:12px;}
table{border-collapse:collapse;width:100%;}td,th{border:1px solid #e2e8f0;padding:6px 10px;text-align:left;}th{background:#f1f5f9;}
</style></head><body>';

echo '<h1>🔍 Google Sheets Tab Checker & Write Test</h1>';

try {
    $db = new DebugSheetsService();
    $token = $db->getToken();
    echo '<div class="box"><span class="ok">✅ Auth token obtained</span></div>';
} catch (Exception $e) {
    echo '<div class="box"><span class="err">❌ AUTH FAILED: ' . htmlspecialchars($e->getMessage()) . '</span></div>';
    die('</body></html>');
}

// ── STEP 1: List actual sheet tabs ──────────────────────────
echo '<div class="box"><h2>STEP 1: Actual Tab Names in Your Spreadsheet</h2>';
echo '<b>Spreadsheet ID:</b> ' . GOOGLE_SPREADSHEET_ID . '<br>';
echo '<b><a href="https://docs.google.com/spreadsheets/d/' . GOOGLE_SPREADSHEET_ID . '/edit" target="_blank">Open Google Sheet ↗</a></b><br><br>';

$meta = $db->getSheetTabs();
if (isset($meta['sheets'])) {
    echo '<table><tr><th>#</th><th>Tab Title</th><th>Sheet ID</th><th>Match "Products"?</th></tr>';
    $productsFound = false;
    foreach ($meta['sheets'] as $i => $s) {
        $title = $s['properties']['title'];
        $sid = $s['properties']['sheetId'];
        $match = ($title === 'Products');
        if ($match)
            $productsFound = true;
        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo '<td><b>' . htmlspecialchars($title) . '</b></td>';
        echo '<td>' . $sid . '</td>';
        echo '<td>' . ($match ? "<span class='ok'>✅ YES</span>" : "<span class='err'>❌ No</span>") . '</td>';
        echo '</tr>';
    }
    echo '</table><br>';
    if ($productsFound) {
        echo "<span class='ok'>✅ 'Products' tab EXISTS — tab name is correct!</span><br>";
    } else {
        echo "<span class='err'>❌ No tab named 'Products' found! The write is failing because the tab name does not match.</span><br>";
        echo "<span class='warn'>⚠️  Your tab might be named 'Product' (singular) or something else — rename it to <b>Products</b> in Google Sheets.</span><br>";
    }
} elseif (isset($meta['error'])) {
    echo "<span class='err'>❌ Could not read spreadsheet metadata: " . htmlspecialchars($meta['error']['message']) . "</span><br>";
    echo "<span class='warn'>The Spreadsheet ID might be wrong, or the service account has no access at all.</span>";
} else {
    echo "<span class='err'>❌ No sheets metadata returned. Raw: " . htmlspecialchars(json_encode($meta)) . "</span>";
}
echo '</div>';

// ── STEP 2: Try read on both "Products" and "Product" ───────
echo '<div class="box"><h2>STEP 2: Read Test — Products vs Product Tab</h2>';
foreach (array('Products', 'Product', 'Sheet1') as $tabName) {
    $result = $db->tryReadRaw($tabName);
    $body = json_decode($result['body'], true);
    $statusText = $result['http'] == 200 ? "<span class='ok'>✅ HTTP 200 — tab exists & accessible</span>" : "<span class='err'>❌ HTTP " . $result['http'] . " — " . (isset($body['error']['message']) ? htmlspecialchars($body['error']['message']) : 'unknown') . "</span>";
    echo "<b>Tab name <code>'$tabName'</code>:</b> $statusText<br>";
}
echo '</div>';

// ── STEP 3: Write test to whichever tab exists ──────────────
echo '<div class="box"><h2>STEP 3: Write Test to Correct Tab</h2>';
$correctTab = null;
foreach (array('Products', 'Product') as $t) {
    $r = $db->tryReadRaw($t);
    if ($r['http'] == 200) {
        $correctTab = $t;
        break;
    }
}

if (!$correctTab) {
    echo "<span class='err'>❌ Neither 'Products' nor 'Product' tab is accessible. Cannot write. Check spreadsheet ID and permissions.</span>";
} else {
    echo "<span class='ok'>✅ Will write to tab: <b>$correctTab</b></span><br><br>";
    $now = date('d/m/Y H:i:s');
    $testId = 'PRD' . date('YmdHis') . '77';
    $testRow = array($testId, 'A4 Bundle - Test', 'Stationery', 450, 0, '4901234567890', 10, 'Pack', 'Debug test', $now, $now);

    $resp = $db->tryWriteRaw($correctTab, $testRow);
    echo '<b>Raw API Response:</b><br>';
    echo '<pre>' . htmlspecialchars($db->lastRawResponse) . '</pre>';

    if (isset($resp['updates'])) {
        $updatedRows = isset($resp['updates']['updatedRows']) ? $resp['updates']['updatedRows'] : '?';
        echo "<span class='ok'>✅ WRITE SUCCESSFUL! Updated rows: $updatedRows</span><br>";
        echo "<span class='ok'>✅ Check your Google Sheet — 'A4 Bundle - Test' should now appear in the <b>$correctTab</b> tab!</span><br>";

        // Update the app config if tab name differs
        if ($correctTab !== 'Products') {
            echo "<br><span class='warn'>⚠️  Your tab is named '<b>$correctTab</b>' but the app is configured for 'Products'.</span><br>";
            echo "<span class='warn'>To fix the app, either:</span><br>";
            echo "<span class='warn'>  Option A: Rename the tab in Google Sheets from '$correctTab' to 'Products'</span><br>";
            echo "<span class='warn'>  Option B: Change <code>define('SHEET_PRODUCTS', 'Products');</code> in <code>config/app.php</code> to '$correctTab'</span><br>";
        }
    } elseif (isset($resp['error'])) {
        echo "<span class='err'>❌ STILL FAILED: " . htmlspecialchars($resp['error']['message']) . "</span><br>";
        echo "<span class='err'>Status: " . htmlspecialchars($resp['error']['status']) . "</span><br>";
        if ($resp['error']['status'] === 'PERMISSION_DENIED') {
            echo "<span class='warn'>🔴 FIX: Go to your Google Sheet → Share → Add <b>haritha-billing-sa@haritha-billing-system.iam.gserviceaccount.com</b> as <b>Editor</b></span><br>";
        }
    }
}
echo '</div>';

echo '</body></html>';
