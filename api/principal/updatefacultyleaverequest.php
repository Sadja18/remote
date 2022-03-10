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

                // find the record with these details
                $recordId = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'teacher.leave.request',
                    'search_read',
                    array(
                        array(
                            array('staff_id', '=', (int) $teacherId),
                            array('start_date', '=', $start),
                            array('end_date', '=', $end),
                            array('user_id', '=', (int) $userId),
                            array('princ_id', '=', (int) $principalId),
                            array('name', '=', (int) $leaveTypeId),
                            array('leave_session', '=', $leaveSession),
                            array('app_date', '=', $applied),
                            array('days', '=', floatval($days)),
                            array('reason', '=', $reason),
                            array('state', '=', 'toapprovep'),
                            array('dept_id', '=', (int) $deptId),
                            array('college_id', '=', (int) $collegeId),
                        ),
                    ),
                    array(
                        'fields' => array(
                            'name', 'state',
                        ),
                    )
                );

                if (
                    isset($recordId) &&
                    !isset($recordId['faultString']) &&
                    $recordId[0]['state'] == 'toapprove' &&
                    $recordId != false

                ) {

                    $lineId = $recordId[0]['id'];

                    if (isset($lineId) && $lineId != false) {

                        // update state

                        $models->execute_kw(
                            $dbname,
                            $uid,
                            $userPassword,
                            "teacher.leave.request",
                            'write',
                            array(
                                array($lineId),
                                array(
                                    'state' => $state,
                                ),
                            )
                        );
                        echo json_encode($recordId);
                    }
                }

                // if record exists, do futher processing

                if (
                    isset($recordId) &&
                    $recordId != false &&
                    !isset($recordId['faultString'])
                ) {
                    if (isset($state)) {
                        if ($state == 'approve') {

                            // if state is toapprovep
                            // update the record
                            // change leave allocation line

                            // decrease pending by number of days
                            // decrease available by number of days

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

                            if (
                                isset($readLineLeaveAllocation) &&
                                $readLineLeaveAllocation != false &&
                                !isset($readLineLeaveAllocation['faultString']) &&
                                isset($readLineLeaveAllocation[0]['id']) &&
                                isset($readLineLeaveAllocation[0]['pending_leaves']) &&
                                isset($readLineLeaveAllocation[0]['available_leaves'])
                            ) {
                                $lineid = $readLineLeaveAllocation[0]['id'];

                                $pendingLeaves = (float) $readLineLeaveAllocation[0]['pending_leaves'];
                                $availableLeaves = (float) $readLineLeaveAllocation[0]['available_leaves'];

                                if ($availableLeaves - (float) $days >= 0) {
                                    // update leave record and then create a leave request;

                                    $newAvailable = $availableLeaves - (float) $days;
                                    $newPending = $pendingLeaves - (float) $days;

                                    // echo json_encode(array("l"=> "b"));

                                    $p = $models->execute_kw(
                                        $dbname,
                                        $uid,
                                        $userPassword,
                                        'leave.allocation.line',
                                        'write',
                                        array(
                                            array(
                                                $lineid,
                                            ),
                                            array(
                                                'pending_leaves' => $newPending,
                                                'available_leaves' => $newAvailable,
                                            ),
                                        )
                                    );

                                    sleep(1);

                                    // now write new record here
                                    // teacher leave request
                                    // update state

                                    $models->execute_kw(
                                        $dbname,
                                        $uid,
                                        $userPassword,
                                        "teacher.leave.request",
                                        'write',
                                        array(
                                            array($lineId),
                                            array(
                                                'state' => $state,
                                            ),
                                        )
                                    );

                                }

                            }

                        } else {
                            if ($state == "reject") {
                                // change leave allocation line

                                // decrease pending by number of days
                                // increase available by number of days

                                // get leave allocation of the teacher for these credentials;
                                // read leave allocation line
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

                                if (
                                    isset($readLineLeaveAllocation) &&
                                    $readLineLeaveAllocation != false &&
                                    !isset($readLineLeaveAllocation['faultString']) &&
                                    isset($readLineLeaveAllocation[0]['id']) &&
                                    isset($readLineLeaveAllocation[0]['pending_leaves']) &&
                                    isset($readLineLeaveAllocation[0]['available_leaves'])
                                ) {
                                    $lineid = $readLineLeaveAllocation[0]['id'];

                                    if (isset($lineid) && $lineid != false) {
                                        $pendingLeaves = (float) $readLineLeaveAllocation[0]['pending_leaves'];
                                        $availableLeaves = (float) $readLineLeaveAllocation[0]['available_leaves'];
                                        $allocatedLeaves = (float) $readLineLeaveAllocation[0]['no_leaves'];

                                        if (
                                            $allocatedLeaves >= $availableLeaves + (float) $days &&
                                            $pendingLeaves >= (float) $days
                                        ) {
                                            // update leave record and then create a leave request;

                                            $newAvailable = $availableLeaves + $days;
                                            $newPending = $pendingLeaves - (float) $days;

                                            // echo json_encode(array("l"=> "b"));

                                            $p = $models->execute_kw(
                                                $dbname,
                                                $uid,
                                                $userPassword,
                                                'leave.allocation.line',
                                                'write',
                                                array(
                                                    array(
                                                        $lineid,
                                                    ),
                                                    array(
                                                        'pending_leaves' => $newPending,
                                                        'available_leaves' => $newAvailable,
                                                    ),
                                                )
                                            );
                                            sleep(1);

                                            // now write new record here
                                            // teacher leave request
                                            // update state

                                            $models->execute_kw(
                                                $dbname,
                                                $uid,
                                                $userPassword,
                                                "teacher.leave.request",
                                                'write',
                                                array(
                                                    array($lineId),
                                                    array(
                                                        'state' => $state,
                                                    ),
                                                )
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

                                }

                            }
                        }
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
