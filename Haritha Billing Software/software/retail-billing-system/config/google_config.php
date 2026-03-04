<?php
/**
 * Google Sheets API Configuration - PHP 5.6 compatible
 */

function getGoogleCredentials()
{
    $credentialsPath = GOOGLE_CREDENTIALS_PATH;
    if (file_exists($credentialsPath)) {
        $creds = json_decode(file_get_contents($credentialsPath), true);
        if ($creds && isset($creds['private_key'])) {
            return $creds;
        }
    }
    // Fallback to .env values
    return array(
        'type' => 'service_account',
        'private_key' => GOOGLE_PRIVATE_KEY,
        'client_email' => GOOGLE_CLIENT_EMAIL,
    );
}

function getSpreadsheetId()
{
    return GOOGLE_SPREADSHEET_ID;
}

define('GOOGLE_SHEETS_API_BASE', 'https://sheets.googleapis.com/v4/spreadsheets');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_SCOPE', 'https://www.googleapis.com/auth/spreadsheets');
