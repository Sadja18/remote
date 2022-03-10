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

                $teacherId = $entityBody['teacherId'];
                $leaveTypeId = $entityBody['leaveTypeId'];
                $leaveSession = $entityBody['leaveSession'];
                $days = $entityBody['days'];
                $applied = $entityBody['appDate'];
                $start = $entityBody['fromDate'];
                $end = $entityBody['toDate'];
                $state = $entityBody['status'];
                $deptId = $entityBody['deptId'];
                $collegeId = $entityBody['collegeId'];
                $reason = $entityBody['reason'];
                $year = $entityBody['year'];

                // user Id of HoD login
                $userId = $entityBody['userId'];
                $principalId = $entityBody['principalId'];

                sleep(1);

                // first find out the record of leave allocation line of this teacherID

                $readLineLeaveAllocation = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'leave.allocation.line',
                    'search_read',
                    array(
                        array(
                            array('faculty_name', '=', (int) $teacherId),
                            array('college_id', '=', (int) $collegeId),
                            array('year', '=', (int) $year),
                            array('leave_type', '=', (int) $leaveTypeId),
                        ),
                    ),
                    array(
                        'fields' => array(
                            'faculty_name', 'no_leaves', 'pending_leaves',
                            'available_leaves', 'dept_name', 'leave_type',
                            'state', 'display_name',
                        ),
                    ),
                );

                $lineId = $readLineLeaveAllocation[0]['id'];
                if (isset($lineId) && !isset($lineId['faultString'])) {
                    // echo json_encode(array("lne" => $lineId));

                    // $updateInitialisedMode =
                    $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'leave.allocation.line',
                        'write',
                        array(
                            array(
                                $lineId,
                            ),
                            array(
                                'pending_leaves' => 0.00,
                                'available_leaves' => 8.00,
                                'approved_leaves'=> 0.00,
                            ),
                        )
                    );
                    // echo json_encode(array("line" => $lineId));
                    sleep(1);
                    $readAgain = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'leave.allocation.line',
                        'search_read',
                        array(
                            array(
                                array('faculty_name', '=', (int) $teacherId),
                                array('college_id', '=', (int) $collegeId),
                                array('year', '=', (int) $year),
                                array('leave_type', '=', (int) $leaveTypeId),
                            ),
                        ),
                        array(
                            'fields' => array(
                                'faculty_name', 'no_leaves', 'pending_leaves',
                                'available_leaves', 'dept_name', 'leave_type',
                                'state', 'display_name',
                            ),
                        ),
                    );
                    $response = array(
                        'no_of_records' => $readLineLeaveAllocation,
                        'j' => $updateInitialisedMode,
                        'c' => $readAgain,
                        'message' => 'Success',

                    );

                    echo json_encode($response);

                } else {
                    echo json_encode(array(
                        "red" => $readLineLeaveAllocation,
                        'j' => $lineId,
                    ));
                }

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
