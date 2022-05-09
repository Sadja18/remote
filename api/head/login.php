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

        // check if the credentials are valid
        $uid = $common->authenticate($dbname, $userName, $userPassword, array());

        if (isset($uid) && $uid != false && $uid != 'false') {

            $models = ripcord::client("$url/xmlrpc/2/object");

            $results = $models->execute_kw(
                $dbname,
                $uid,
                $userPassword,
                'college.teacher',
                'search_read',
                array(
                    array(
                        array('user_id', '!=', False),
                    )
                ),
                array('fields' => array(
                    'name', 'display_name', 'class_id', 'course_id',
                    'college_id', 'is_parent', 'is_hod', 'is_mentor',
                    'teacher_type', 'dept_id'
                ))
            );

            $college = $results[0]['college_id'];

            $collDeptLine = $models->execute_kw(
                $dbname,
                $uid,
                $userPassword,
                'college.department.line',
                'search_read',
                array(
                    array(
                        array('email_id', '=', $userName)
                    )
                ),
                array('fields' => array('email_id', 'hod', 'department_id')),
            );


            $collAdmin = $models->execute_kw(
                $dbname,
                $uid,
                $userPassword,
                'college.college',
                'search_read',
                array(
                    array(
                        array('email', '=', $userName)
                    )
                ),
                array('fields' => array('email'))
            );
            if (isset($results)) {
                if (isset($results['faultString'])) {
                    echo json_encode(array(
                        'message' => 'failed',
                        'data' => []
                    ));
                } else {
                    if (isset($collDeptLine[0]) && !isset($collAdmin[0])) {
                        // means that the teacher is only a dept HoD
                        $hod = $collDeptLine[0]['hod'];

                        $teacherId = $hod[0];
                        $teacherName = $hod[1];

                        echo json_encode(array(
                            'message' => 'Success',
                            'data' => array(
                                'userId' => $uid,
                                'userName' => $userName,
                                'userPassword' => $userPassword,
                                'dbname' => $dbname,
                                'isHoD' => 'yes',
                                'isPrincipal' => 'no',
                                'facultyId'=> $teacherId,
                                'facultyName'=> $teacherName,
                                'collegeId'=> $college[0],
                                'collegeName'=> $college[1],
                                'deptId' => $collDeptLine[0]['department_id'][0],
                                'deptName' => $collDeptLine[0]['department_id'][1],
                                'g'=> $collDeptLine[0],
                            )
                        ));
                    }else{
                        echo json_encode($failedLogin);
                    }
                }
            } else {
                echo json_encode($failedLogin);
            }
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
