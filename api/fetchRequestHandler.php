<?php
// this API call is to handle all the fetch request

require_once './ripcord/ripcord.php';
require_once './envRead.php';

use sadja\DotEnv;

// to make sure only the originator of request is able to see the response
header('Access-Control-Allow-Origin: *');

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

(new DotEnv(__DIR__ . '/.env'))->load();

$url = getenv('PUBLIC');
// $url = getenv('PRIVATE');
// $url = getenv('PUBLICALT');
// $url = getenv('PRIVATEALT');

$connected = null;
$responseBody = array();
// $connect = $connected;
$connect = fopen($url, "r");
if ($_SERVER['REQUEST_METHOD'] == 'get' || $_SERVER['REQUEST_METHOD'] == "GET") {
    if (isset($_GET['get']) && (strcmp($_GET['get'], "1")==0 || $_GET['get']==1)) {
        if ($connect == 'true' || $connect == true) {

            $connected  = true;
            $responseBody = array(
                'connected' => $connected,
                'url' => $url
            );
        } else {
            $connected = false;
            $responseBody = array(
                'connected' => $connected,
                'url' => $url
            );
        }
    }
    if ((isset($_GET['held']) && $_GET['held'] == 1)) {
        $responseBody = array(
            'data' => 12
        );
    }
    if (isset($_GET['get']) && $_GET['held']) {
        $responseBody = array(
            'data' => 'something else'
        );
    } 
}

echo json_encode($responseBody);
