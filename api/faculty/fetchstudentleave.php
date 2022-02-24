<?php

require_once '../ripcord/ripcord.php';
require_once '../envRead.php';

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

$failNotPost = array(
    'message' => 'Invalid Request'
);

$failNoData = array(
    'message' => 'Please pass required parameters'
);

$failInvalidCredentials = array(
    "message" => "Invalid Credentials"
);

$failedLogin = array(
    "message" => "Login Failure. This user does not have the required access rights."
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'post') {
    $entityBodyJSON = file_get_contents('php://input');

    $entityBody = json_decode($entityBodyJSON, true);

    if (isset($entityBody['str'])) {
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

                $models = ripcord::client("$url/xmlrpc/2/object");

                $collegeId = $entityBody['collegeId'];
                $teacherId = $entityBody['teacherId'];

                $leaveCount = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'studentleave.request',
                    'search_count',
                    array(
                        array(
                            array("college_id", "=", (int)$collegeId),
                            array("teacher_id", '=', (int)$teacherId),
                            array('state', '=', 'toapprove'),
                        )
                    ),
                    
                );

                $leaveApps = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'studentleave.request',
                    'search_read',
                    array(
                        array(
                            array("college_id", "=", (int)$collegeId),
                            array("teacher_id", '=', (int)$teacherId),
                            array('state', '=', 'toapprove'),
                        )
                    ),
                    array(
                        'fields'=> array(
                            "name", "student_id", "reason",
                            "state",  "college_id", "class_id",
                            "start_date", "end_date", "days", 'teacher_id'
                            
                        )
                    ),
                );
                $response = array(
                    'no_of_records' => $leaveCount,
                    'data' => $leaveApps,
                    'message' => 'Success'

                );

                echo json_encode($response);
            } else {
                // if the login credentials were incorrect,
                // echo
                echo json_encode($failInvalidCredentials);
            }
        } else {
            echo json_encode($failNoData);
        }
    } else {
        echo json_encode(array('message' => 'Invalid request'));
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
