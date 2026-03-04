<?php
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

// echo "<h2>Google Sheets Test</h2>";

require_once BASE_PATH . '/services/GoogleSheetsService.php';

// $gs = new GoogleSheetsService();

// try {
//     $data = $gs->readSheet('Products');

//     echo "<pre>";
//     print_r($data);
//     echo "</pre>";

// } catch (Exception $e) {
//     echo "Error: " . $e->getMessage();
// }

// exit;
// echo "<h2>Insert Test</h2>";

$gs = new GoogleSheetsService();

$insert = $gs->appendRow('Products', array(
    '1',
    'Test Product',
    '100',
    '10'
));

var_dump($insert);

echo "<br><br>";

$data = $gs->readSheet('Products');

echo "<pre>";
print_r($data);
echo "</pre>";

exit;


?>