<?php

require_once './envRead.php';

use sadja\DotEnv;

header('Access-Control-Allow-Origin: ' . $_SERVER['REMOTE_ADDR'], false);

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

(new DotEnv(__DIR__ . '/.env'))->load();

// $url = getenv('PUBLIC');

$url = getenv('PRIVATE');
// $url = getenv('PUBLICALT');
// $url = getenv('PRIVATEALT');

$connected = null;
// $connect = $connected;
$connect = fopen($url, "r");
if ($_SERVER['REQUEST_METHOD'] == 'get' || $_SERVER['REQUEST_METHOD'] == "GET") {
    if (isset($_GET['get']) && $_GET['get'] == 1) {
        if ($connect == 'true' || $connect == true) {

            $connected  = true;
        } else {
            $connected = false;
        }
    } else {
        $connected = false;
    }
} else {
    $connected = false;
}

echo json_encode(array(
    'connected' => $connected,
    'url' => $url,
    'client' => $_SERVER['REMOTE_ADDR']
));
