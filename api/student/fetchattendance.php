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

    if(isSiteAvailable($url)){
        if (isset($userName) && isset($userPassword)) {
            $common = ripcord::client($url . '/xmlrpc/2/common');
    
            // check if the credentials are valid
            $uid = $common->authenticate($dbname, $userName, $userPassword, array());
    
            if (isset($uid) && $uid != false && $uid != 'false') {
                // if the login credentials were correct,
                $models = ripcord::client("$url/xmlrpc/2/object");
    
                $studentId  = $entityBody['studentId'];
                $yearId = $entityBody['academicYearId'];
    
                $data = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'daily.attendance.line',
                    'search_read',
                    array(
                        array(
                            array('stud_id', '=', (int)$studentId),
                            array('year', '=', (int)$yearId),
                        )
                    ),
                    array(
                        'fields'=> array(
                            'stud_id', 'roll_no' ,'class_id', 
                            'is_present', 'is_absent', 'year', 
                            'date', 'college_id'
                            )
                    )
                );
                echo json_encode(
                    array(
                        "message" => "success",
                        "data" => $data,
                    )
                );
            }
        }
    }else{
        echo json_encode(serverUnReachable());
    }
}
