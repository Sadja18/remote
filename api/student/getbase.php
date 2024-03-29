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
                            'pid',
                            'roll_no',
                            "enrol_no",
                            'course_id',
                            'class_id',
                            'dept_id',
                            'colyear',
                            'semester',
                            "college_id",
                        ),
                    )
                );
                sleep(2);
    
                $user = $users[0];
    
                $course_id = $user['course_id'][0];
                $college_id = $user['college_id'][0];
    
                $isArtScience = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'course.course',
                    'search_read',
                    array(
                        array(
                            array('id', '=', (int) $course_id),
                            array('college_id', '=', $college_id),
                        ),
                    ),
                    array("fields" => array("no_dept"))
                );
    
                $user['no_dept'] = $isArtScience[0]['no_dept'];
                echo json_encode(
                    array(
                        "message" => "success",
                        "data" => $user,
                    )
                );
            }
        }
    }else{
        echo json_encode(serverUnReachable());
    }

}
