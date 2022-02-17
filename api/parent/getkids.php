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
        $uid = $common->authenticate($dbname, $userName, $userPassword, array());
        if (isset($uid) && $uid != false && $uid != 'false') {
            $models = ripcord::client("$url/xmlrpc/2/object");

            $results = $models->execute_kw(
                $dbname,
                $uid,
                $userPassword,
                'college.parent',
                'search_read',
                array(
                    array(
                        array('id', '!=', False)
                    )
                ),
                array(
                    'fields' => array(
                        'display_name', 'partner_id', 'relation_id', 'student_id'
                    )
                )
            );

            $studentDetails = array();

            $studentIds = $results[0]['student_id'];

            foreach ($studentIds as $studentId) {
                $studentData = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'student.student',
                    'search_read',
                    array(
                        array(
                            array('id', '=', $studentId)
                        )
                    ),
                    array(
                        'fields' => array(
                            'pid',
                            'student_id',
                            'student_code',
                            'student_name',
                            'last',
                        )
                    )
                );
                array_push($studentDetails, $studentData[0]);
            }

            $response = array(
                "message" => "success",
                "data" => $results,
                'studentDetails'=> $studentDetails
            );

            echo json_encode($response);
        } else {
            // if the login credentials were incorrect,
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
