<?php

$privateURL = "http://10.184.49.222:8069";
$publicURL = "http://14.139.180.56:8069";

// $url = $publicURL;

$url = $privateURL;

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

        $classes = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.standard',
            'search_read',
            array(
                array(
                    '|', '|',
                    array('user_id.name', '=', $teacher_name),
                    array('sec_user_id.name', '=', $teacher_name),
                    array('ter_user_id.name', '=', $teacher_name),
                ),
            ),
            array('fields' => array('name', 'standard_id', 'medium_id', 'division_id'))
        );

        if (
            !isset($classes['faultString'])
        ) {
            $response = array(
                // "teacher" => $teachers,
                "classes" => $classes,
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => $classes,

            );
        }
        echo json_encode($response);
    }else{
        echo json_encode(array(
            "message"=> "error",
            "error"=> "persistent not set"
        ));
    }
}else{
    echo json_encode(array(
        "message"=> "error",
        "error"=> "Not get request"
    ));
}
