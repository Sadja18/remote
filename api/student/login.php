<?php

require_once '../ripcord/ripcord.php';
require_once '../envRead.php';

use sadja\DotEnv;

header('Access-Control-Allow-Origin: ' . $_SERVER['REMOTE_ADDR'], false);

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

(new DotEnv(__DIR__ . '/.env'))->load();

$url = getenv('PUBLIC');
// $url = getenv('PRIVATE');
// $url = getenv('PUBLICALT');
// $url = getenv('PRIVATEALT');

$failNotPost = array(
    'message' => 'Invalid Request',
);

$failNoData = array(
    'message' => 'Please pass required parameters',
);

$failInvalidCredentials = array(
    "message" => "Invalid Credentials",
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'post') {
    $entityBodyJSON = file_get_contents('php://input');

    $entityBody = json_decode($entityBodyJSON, true);

    $userName = $entityBody['userName'];
    $userPassword = $entityBody['userPassword'];

    $dbname = null;
    if (isset($entityBody['dbname'])) {
        $dbname = $entityBody['dbname'];
    } else {
        $dbname = 'college';
    }

    if (isset($userName) && isset($userPassword)) {
        $common = ripcord::client($url . '/xmlrpc/2/common');

        // check if the credentials are valid
        $uid = $common->authenticate($dbname, $userName, $userPassword, array());

        if (isset($uid) && $uid != false && $uid != 'false') {
            // if the login credentials were correct,
            $models = ripcord::client("$url/xmlrpc/2/object");

            $users = $models->execute_kw(
                $dbname,
                $uid,
                $userPassword,
                'student.student',
                'search_read',
                array(
                    array(
                        array('user_id', '=', $uid),
                    ),
                ),
                array(
                    'fields' => array(
                        'student_code',
                        'student_name',
                        'middle',
                        'last',
                        'college_id'
                    ),
                )
            );

            echo json_encode(
                array(
                    "message" => "success",
                    "data" => array(
                        'loginStatus' => '1',
                        'userId' => $uid,
                        'userName' => $userName,
                        'userPassword' => $userPassword,
                        'dbname' => $dbname,
                        'studentId' => $users[0]['id'],
                        'studentCode' => $users[0]['student_code'],
                        'fName'=> $users[0]['student_name'],
                        'mName'=> $users[0]['middle'],
                        'lName'=> $users[0]['last'], 
                        'collegeId'=> $users[0]['college_id'][0],
                        'collegeName'=> $users[0]['college_id'][1]
                    ),
                )
            );
        } else {
            // if $uid is not set or it's value is false
            // echo for now
            echo json_encode($failInvalidCredentials);
        }
    } else {
        echo json_encode($failNoData);
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
