<?php

$privateURL = "http://10.184.4.238:8069";
$publicURL = "http://10.184.49.222:8069";

$url = $publicURL;

// $url = $privateURL;

$user = null;
$password = null;
$dbname = null;
$response = null;


require_once './ripcord/ripcord.php';

// check if server request method is get
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // $response = 'Fetch Request received';

    header('Access-Control-Allow-Origin: *', false);
    header('Content-Type: application/json');


    if (isset($_GET['userName'])) {
        $user = $_GET['userName'];
    }
    if (isset($_GET['userPassword'])) {
        $password = $_GET['userPassword'];
    }
    if (isset($_GET['dbname'])) {
        $dbname = $_GET['dbname'];
    } else {
        $dbname = 'doednhdd';
    }

    $common = ripcord::client($url . '/xmlrpc/2/common');
    $uid = $common->authenticate($dbname, $user, $password, array());
    $models = ripcord::client("$url/xmlrpc/2/object");

    if (isset($_GET['Persistent'])) {

        $reading_levels = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.reading',
            'search_read',
            array(
                array(
                    array('name', '!=', False),
                )
            ),
            array('fields' => array('standard', 'name', 'subject'))
        ); 

        if ( !isset($reading_levels['faultString'])
        ) {
            $response = array(
                'reading_levels' => $reading_levels,
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => $languages
            );
        }
        echo json_encode($response);
    }
}
