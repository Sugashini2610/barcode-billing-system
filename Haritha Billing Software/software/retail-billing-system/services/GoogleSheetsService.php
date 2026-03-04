<?php
/**
 * GoogleSheetsService - Primary Database Abstraction Layer
 * PHP 5.6 compatible - no typed properties, no arrow functions
 */
class GoogleSheetsService
{
    private $spreadsheetId;
    private $accessToken = '';
    private $tokenExpiry = 0;

    public function __construct()
    {
        require_once BASE_PATH . '/config/google_config.php';
        $this->spreadsheetId = getSpreadsheetId();
    }

    // =====================================================
    // AUTHENTICATION - JWT / Service Account
    // =====================================================

    private function getAccessToken()
    {
        if ($this->accessToken && time() < $this->tokenExpiry - 60) {
            return $this->accessToken;
        }

        $credentials = getGoogleCredentials();
        $privateKey = $credentials['private_key'];
        $clientEmail = $credentials['client_email'];

        $now = time();
        $payload = array(
            'iss' => $clientEmail,
            'scope' => GOOGLE_SCOPE,
            'aud' => GOOGLE_TOKEN_URL,
            'exp' => $now + 3600,
            'iat' => $now,
        );

        $jwt = $this->createJWT($payload, $privateKey);

        $response = $this->httpPost(GOOGLE_TOKEN_URL, array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ));

        if (isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
            $this->tokenExpiry = $now + (isset($response['expires_in']) ? $response['expires_in'] : 3600);
        } else {
            throw new Exception('Failed to get Google access token: ' . json_encode($response));
        }

        return $this->accessToken;
    }

    private function createJWT($payload, $privateKey)
    {
        $header = base64url_encode(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
        $body = base64url_encode(json_encode($payload));
        $data = "$header.$body";

        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return "$data." . base64url_encode($signature);
    }

    // =====================================================
    // CORE CRUD OPERATIONS
    // =====================================================

    public function readSheet($sheetName, $range = 'A:Z')
    {
        $token = $this->getAccessToken();
        $url = GOOGLE_SHEETS_API_BASE . "/{$this->spreadsheetId}/values/{$sheetName}!{$range}";

        $response = $this->httpGet($url, $token);

        if (!isset($response['values'])) {
            return array();
        }

        $rows = $response['values'];
        if (empty($rows))
            return array();

        $headers = array_shift($rows);
        $result = array();
        foreach ($rows as $index => $row) {
            $mapped = array();
            foreach ($headers as $i => $header) {
                $mapped[$header] = isset($row[$i]) ? $row[$i] : '';
            }
            $mapped['_row'] = $index + 2;
            $result[] = $mapped;
        }
        return $result;
    }

    public function appendRow($sheetName, $data)
    {
        $token = $this->getAccessToken();
        // Google Sheets API v4 correct format: /values/{range}:append
        $url = GOOGLE_SHEETS_API_BASE . "/{$this->spreadsheetId}/values/{$sheetName}!A1:append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS";

        $payload = array('values' => array(array_values($data)));
        $response = $this->httpPost($url, $payload, $token, 'application/json');

        if (!isset($response['updates'])) {
            // Log the actual API error response for debugging
            $errMsg = isset($response['error']['message']) ? $response['error']['message'] : json_encode($response);
            logMessage("appendRow FAILED for sheet '$sheetName': $errMsg", 'ERROR');
            return false;
        }
        return true;
    }

    public function updateRow($sheetName, $rowNumber, $data)
    {
        $token = $this->getAccessToken();
        $colLetter = $this->numToColLetter(count($data));
        $range = "{$sheetName}!A{$rowNumber}:{$colLetter}{$rowNumber}";
        $url = GOOGLE_SHEETS_API_BASE . "/{$this->spreadsheetId}/values/{$range}?valueInputOption=USER_ENTERED";

        $payload = array('values' => array(array_values($data)));
        $response = $this->httpPut($url, $payload, $token);

        return isset($response['updatedCells']);
    }

    public function deleteRow($sheetName, $rowNumber)
    {
        $token = $this->getAccessToken();
        $sheetId = $this->getSheetIdByName($sheetName);
        if ($sheetId === null)
            return false;

        $url = GOOGLE_SHEETS_API_BASE . "/{$this->spreadsheetId}:batchUpdate";
        $payload = array(
            'requests' => array(
                array(
                    'deleteDimension' => array(
                        'range' => array(
                            'sheetId' => $sheetId,
                            'dimension' => 'ROWS',
                            'startIndex' => $rowNumber - 1,
                            'endIndex' => $rowNumber,
                        )
                    )
                )
            )
        );

        $response = $this->httpPost($url, $payload, $token, 'application/json');
        return isset($response['replies']);
    }

    public function findWhere($sheetName, $column, $value)
    {
        $rows = $this->readSheet($sheetName);
        $result = array();
        foreach ($rows as $row) {
            if (isset($row[$column]) && $row[$column] == $value) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function findOne($sheetName, $column, $value)
    {
        $rows = $this->readSheet($sheetName);
        foreach ($rows as $row) {
            if (isset($row[$column]) && $row[$column] == $value) {
                return $row;
            }
        }
        return null;
    }

    public function getHeaders($sheetName)
    {
        $token = $this->getAccessToken();
        $url = GOOGLE_SHEETS_API_BASE . "/{$this->spreadsheetId}/values/{$sheetName}!1:1";
        $response = $this->httpGet($url, $token);
        return isset($response['values'][0]) ? $response['values'][0] : array();
    }

    public function initializeSheet($sheetName, $headers)
    {
        $existing = $this->getHeaders($sheetName);
        if (empty($existing)) {
            return $this->appendRow($sheetName, $headers);
        }
        return true;
    }

    public function count($sheetName)
    {
        return count($this->readSheet($sheetName));
    }

    public function sumColumn($sheetName, $column)
    {
        $rows = $this->readSheet($sheetName);
        $sum = 0;
        foreach ($rows as $row) {
            $sum += isset($row[$column]) ? (float) $row[$column] : 0;
        }
        return $sum;
    }

    public function filterByDateRange($sheetName, $dateColumn, $from, $to)
    {
        $rows = $this->readSheet($sheetName);
        $result = array();
        foreach ($rows as $row) {
            if (empty($row[$dateColumn]))
                continue;
            $date = date('Y-m-d', strtotime($row[$dateColumn]));
            if ($date >= $from && $date <= $to) {
                $result[] = $row;
            }
        }
        return $result;
    }

    private function getSheetIdByName($name)
    {
        $token = $this->getAccessToken();
        $url = GOOGLE_SHEETS_API_BASE . "/{$this->spreadsheetId}?fields=sheets.properties";
        $response = $this->httpGet($url, $token);

        if (isset($response['sheets'])) {
            foreach ($response['sheets'] as $sheet) {
                if ($sheet['properties']['title'] === $name) {
                    return $sheet['properties']['sheetId'];
                }
            }
        }
        return null;
    }

    // =====================================================
    // HTTP HELPERS
    // =====================================================

    /**
     * Apply SSL settings to a cURL handle.
     * On WAMP/Windows the system CA bundle is often missing, so we
     * look for the cacert.pem that ships with WAMP. If not found we
     * disable peer verification (acceptable for localhost development).
     */
    private function curlSetSsl($ch)
    {
        // Common WAMP cacert.pem locations
        $possibleCerts = array(
            'C:/wamp64/bin/php/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION . '/cacert.pem',
            'C:/wamp64/bin/php/cacert.pem',
            'C:/wamp/bin/php/cacert.pem',
            'C:/xampp/php/extras/ssl/cacert.pem',
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
        );

        $certFound = false;
        foreach ($possibleCerts as $cert) {
            if ($cert && file_exists($cert)) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_CAINFO, $cert);
                $certFound = true;
                break;
            }
        }

        if (!$certFound) {
            // No CA bundle found — disable peer verification for local dev
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
    }

    private function httpGet($url, $token = '')
    {
        $headers = array();
        if ($token)
            $headers[] = "Authorization: Bearer $token";

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ));
        $this->curlSetSsl($ch);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            logMessage("HTTP GET Error: $error", 'ERROR');
            return array();
        }
        $decoded = json_decode($result, true);
        return $decoded ? $decoded : array();
    }

    private function httpPost($url, $data, $token = '', $contentType = 'application/x-www-form-urlencoded')
    {
        $body = $contentType === 'application/json' ? json_encode($data) : http_build_query($data);
        $headers = array("Content-Type: $contentType");
        if ($token)
            $headers[] = "Authorization: Bearer $token";

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ));
        $this->curlSetSsl($ch);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            logMessage("HTTP POST Error: $error", 'ERROR');
            // Throw so callers (like getAccessToken) see the real reason
            throw new Exception('cURL POST failed: ' . $error);
        }
        $decoded = json_decode($result, true);
        return $decoded ? $decoded : array();
    }

    private function httpPut($url, $data, $token)
    {
        $body = json_encode($data);

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer $token",
            ),
            CURLOPT_TIMEOUT => 30,
        ));
        $this->curlSetSsl($ch);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            logMessage("HTTP PUT Error: $error", 'ERROR');
            return array();
        }
        $decoded = json_decode($result, true);
        return $decoded ? $decoded : array();
    }

    private function numToColLetter($num)
    {
        $letters = '';
        while ($num > 0) {
            $mod = ($num - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $num = (int) (($num - $mod) / 26);
        }
        return $letters;
    }
}

if (!function_exists('base64url_encode')) {
    function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
