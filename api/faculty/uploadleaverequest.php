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

                // get leave allocation of the teacher for these credentials;

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

                if (isset($readLineLeaveAllocation) &&
                    $readLineLeaveAllocation != false &&
                    !isset($readLineLeaveAllocation['faultString']) &&
                    isset($readLineLeaveAllocation[0]['id']) &&
                    isset($readLineLeaveAllocation[0]['pending_leaves']) &&
                    isset($readLineLeaveAllocation[0]['available_leaves'])
                ) {
                    $lineId = $readLineLeaveAllocation[0]['id'];

                    $pendingLeaves = (float) $readLineLeaveAllocation[0]['pending_leaves'];
                    $availableLeaves = (float) $readLineLeaveAllocation[0]['available_leaves'];

                    if ($availableLeaves - (float) $days >= 0) {
                        // update leave record and then create a leave request;

                        $newAvailable = $availableLeaves - (float) $days;
                        $newPending = (float) $days;


                       $p = $models->execute_kw(
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
                                    'pending_leaves' => $newPending,
                                    'available_leaves' => $newAvailable,
                                ),
                            )
                        );

                        $create = $models->execute_kw(
                            $dbname,
                            $uid,
                            $userPassword,
                            "teacher.leave.request",
                            "create",
                            array(
                                array(
                                    array(
                                        'staff_id' => (int) $teacherId,
                                        'start_date' => $start,
                                        'end_date' => $end,
                                        'user_id' => (int) $userId,
                                        'princ_id' => (int) $principalId,
                                        'name' => (int) $leaveTypeId,
                                        'leave_session' => $leaveSession,
                                        'app_date' => $applied,
                                        'days' => floatval($days),
                                        'reason' => $reason,
                                        'state' => $state,
                                        'dept_id' => (int) $deptId,
                                        'college_id' => (int) $collegeId,
                                    ),
                                ),
                            ),
                        );

                        $response = array(
                            'no_of_records' => [],
                            'data' => array(
                                'create' => $create,
                                'newPending' => $newPending,
                                'newAvailable' => $newAvailable,
                            ),
                            'message' => 'Success',
                        );

                        echo json_encode($response);

                    }
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
