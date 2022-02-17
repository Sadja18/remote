<?php
$privateURL = "http://10.184.4.238:8069";
$publicURL = "http://14.139.180.56:8069";

// $url = $privateURL;
$url = $publicURL;


$user = null;
$password = null;
$dbname = null;
$response = null;


require_once './ripcord/ripcord.php';

if($_SERVER['REQUEST_METHOD']=='GET'){
header('Access-Control-Allow-Origin: *', false);

    $response = "getting sync data";
    echo json_encode($response);
}
?>