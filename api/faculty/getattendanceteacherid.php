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

        // check if the credentials are valid
        $uid = $common->authenticate($dbname, $userName, $userPassword, array());

        if (isset($uid) && $uid != false && $uid != 'false') {

            $models = ripcord::client("$url/xmlrpc/2/object");

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

            $deptId = $collDeptLine[0]['department_id'][0];

            $records = $models->execute_kw(
                $dbname,
                $uid,
                $userPassword,
                'teacher.daily.attendance',
                'search_read',
                array(
                    array(
                        array("dept_id", "=",(int) $deptId)
                    )
                ),
                array(
                    'fields' => array(
                        "date", 'teacher_ids', "state",
                        "total_teacher", "total_presence", "total_absent",
                        "dept_id", "college_id", "name"
                    ),
                ),
            );

            echo json_encode(array(
                "message" => "success",
                'data' => $records
            ));

            
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
