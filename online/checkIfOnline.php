<?php
// header('Content-Type:text/plain');
$privateURL = "http://14.139.180.56:8069";
$publicURL = "http://14.139.180.56:8069";

// $url = $privateURL;
$url = $publicURL;

// $connected = null;
$connectArray = null;
$connect = fopen($url, "r");
if($connect){
    $connectArray = array(
        'connected' => 'true'
    );
}else{
    $connectArray = array(
        'connected' => $connect
    );
}

// http_response_code(201);
// $http_response_header;
header('Access-Control-Allow-Origin: *', false);

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

echo json_encode($connectArray);
