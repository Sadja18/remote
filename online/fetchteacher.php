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
                    array('employee_id.user_id.id', '=', $uid),
                ),
            ),
            array('fields' => array('name', 'school_id'))

        );

        $teacher_id = $teachers[0]['id'];
        $teacher_name = $teachers[0]['name'];
        $school_name = $teachers[0]['school_id'][1];
        $school_id = $teachers[0]['school_id'][0];

        if (
            !isset($teachers['faultString'])
        ) {
            $response = array(
                "teacher" => array(
                    "teacher_id" => $teacher_id,
                    "teacher_name" => $teacher_name,
                    'userID'=> $uid,
                ),
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
