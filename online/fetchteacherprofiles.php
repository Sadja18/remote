<?php

$privateURL = "http://10.184.4.238:8069";
$publicURL = "http://14.139.180.56:8069";

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

    // echo json_decode("echo");

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
        $teachers = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.teacher',
            'search_read',
            array(
                array(
                    array('active', '=', TRUE),
                ),
            ),
            array(
                'fields' => array(
                    'name', 'school_id', 'employee_id',
                    'teacher_code', 'user_id', "photo"
                ),
            )
        );

        

        if (!isset($teachers['faultString'])) {
            $response = array(
                'message'=> 'success',
                "teachers" => $teachers,
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => array(
                    "students" => $students,
                ),
            );
        }
        echo json_encode($response);
    }
}
