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

$failedLogin = array(
    "message" => "Login Failure. This user does not have the required access rights.",
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

            // $deptId = $entityBody['deptId'];
            $collegeId = $entityBody['collegeId'];

            $collDeptLine = $models->execute_kw(
                $dbname,
                $uid,
                $userPassword,
                'college.department.line',
                'search_read',
                array(
                    array(
                        // array('email_id', '=', $userName),
                        // array('department_id', '=', (int) $deptId),
                        array('college_id', '=', (int) $collegeId),
                    ),
                ),
                array('fields' => array('email_id', 'hod', 'department_id', 'college_id')),
            );

            if (isset($collDeptLine) && !empty($collDeptLine)) {
                $hodId = $collDeptLine[0]['hod'][0];
                $hodName = $collDeptLine[0]['hod'][1];
                $recordCount = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'college.teacher',
                    'search_count',
                    array(
                        array(
                            array("user_id", '!=', false),
                            array('active', '=', true),
                            // array('dept_id', '=', (int) $deptId),
                            array('college_id', '=', (int) $collegeId),
                        ),
                    ),
                );

                $records = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'college.teacher',
                    'search_read',
                    array(
                        array(
                            array("user_id", '!=', false),
                            array('active', '=', true),
                            // array('dept_id', '=', (int) $deptId),
                            array('college_id', '=', (int) $collegeId),
                        ),
                    ),
                    array(
                        'fields' => array(
                            'user_id', 'employee_id', 'teacher_code',
                            'phone_numbers', 'ice_phone',
                            'dept_id', 'is_hod', 'college_id',
                        ),
                    ),
                );
                $response = array(
                    "message" => "success",
                    'no_of_records' => $recordCount,
                    "data" => $records,
                );

                echo json_encode($response);
            } else {
                $recordCount = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'college.teacher',
                    'search_count',
                    array(
                        array(
                            array("user_id", '!=', false),
                            array('active', '=', true),
                            array('college_id', '=', (int) $collegeId),
                        ),
                    ),
                );

                $records = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'college.teacher',
                    'search_read',
                    array(
                        array(
                            array("user_id", '!=', false),
                            array('active', '=', true),
                            array('college_id', '=', (int) $collegeId),
                        ),
                    ),
                    array(
                        'fields' => array(
                            'user_id', 'employee_id', 'teacher_code',
                            'phone_numbers', 'ice_phone',
                            'dept_id', 'is_hod', 'college_id',
                        ),
                    ),
                );
                $response = array(
                    "message" => "success",
                    'no_of_records' => $recordCount,
                    "data" => $records,
                );
                echo json_encode($response);
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
