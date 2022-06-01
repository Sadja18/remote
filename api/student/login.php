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
                            "photo"
                        ),
                    )
                );
    
                $course_id = $users[0]['course_id'][0];
                $college_id = $users[0]['college_id'][0];
    
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
                $users[0]['no_dept'] = $isArtScience[0]['no_dept'];
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
                            'course'=> $users[0]['course_id'],
                            'class'=> $users[0]['class_id'],
                            'year'=> $users[0]['colyear'],
                            'semester'=> $users[0]['semester'],
                            'department'=> $users[0]['dept_id'],
                            'noDept'=> $users[0]['no_dept'],
                            'collegeId'=> $users[0]['college_id'][0],
                            'collegeName'=> $users[0]['college_id'][1],
                            'profilePic'=> $users[0]['photo'],
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

    }else{
        echo json_encode(
            serverUnReachable()
        );
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
