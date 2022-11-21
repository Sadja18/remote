<?php

require_once '../ripcord/ripcord.php';
require_once '../envRead.php';
require_once '../helper.php';

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

$failedLogin = array(
    "message" => "Login Failure. This user does not have the required access rights.",
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'post') {
    $entityBodyJSON = file_get_contents('php://input');

    $entityBody = json_decode($entityBodyJSON, true);

    if(isSiteAvailable($url)){
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
    
                    $session = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'academic.year',
                        'search_read',
                        array(
                            array(
                                array('current', '=', true),
                            ),
                        ),
                        array('fields' => array('date_start', 'date_stop'),
                        )
                    );
                    sleep(2);
                    $zero = $session[0];
                    $dateStart = $zero['date_start'];
                    $dateEnd = $zero['date_stop'];
    
                    $leaveCount = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'daily.attendance',
                        'search_count',
                        array(
                            array(
                                array("college_id", "=", (int) $collegeId),
                                array('state', '=', 'validate'),
                                array('date', '>=', $dateStart),
                                array('date', '<=', $dateEnd),
                            ),
                        ),
                    );
    
                    $leaveApps = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'daily.attendance',
                        'search_read',
                        array(
                            array(
                                array("college_id", "=", (int) $collegeId),
                                array('state', '=', 'validate'),
                                array('date', '>=', $dateStart),
                                array('date', '<=', $dateEnd),
                            ),
                        ),
                        array(
                            'fields' => array(
                                "display_name", "state", "date", "student_ids",
                                'course_id', 'no_dept', 'course_year',
                                'course_sem', 'subject_id', 'class_id',
                                'user_id', 'year',
                                "total_student", "total_presence", "total_absent",
                                "dept_id", "college_id",
                            ),
                        ),
                    );
                    $response = array(
                        'no_of_records' => $leaveCount,
                        'data' => $leaveApps,
                        'message' => 'Success',
    
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
    }else{
        echo json_encode(serverUnReachable());
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
