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
    
                    // get leave allocation line for this teacher Id
                    $leaveLineId = $models->execute_kw(
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
                                'available_leaves', 'approved_leaves', 'no_leaves',
                            ),
                        )
                    );
                    if (
                        isset($leaveLineId) &&
                        !isset($leaveLineId['faultString']) &&
                        $leaveLineId != false
                    ) {
                        // get line id of this leave allocation
    
                        $pendinLeaves = $leaveLineId[0]['pending_leaves'];
                        $approvedLeaves = $leaveLineId[0]['approved_leaves'];
                        $availableLeaves = $leaveLineId[0]['available_leaves'];
                        $allocatedLeaves = $leaveLineId[0]['no_leaves'];
                        $tmp = $leaveLineId[0]['id'];
    
                        // check allocatedLeaves == available + approved + pending + $days
                        // and available >= $days
    
                        if (
                            $approvedLeaves + $availableLeaves + $pendinLeaves == $allocatedLeaves &&
                            $availableLeaves >= (float) $days
                        ) {
    
                            sleep(2);
    
                            // fetch leave request record
                            $leaveRequestRecord = $models->execute_kw(
                                $dbname,
                                $uid,
                                $userPassword,
                                'teacher.leave.request',
                                'search_read',
                                array(
                                    array(
                                        array('staff_id', '=', (int) $teacherId),
                                        array('college_id', '=', (int) $collegeId),
                                        array('name', '=', (int) $leaveTypeId),
                                        array('leave_session', '=', $leaveSession),
                                        array('start_date', '=', $start),
                                        array('end_date', '=', $end),
                                        array('days', '=', $days),
                                        array('state', '=', 'toapprovep'),
                                        array('app_date', '=', $applied),
                                        array('reason', '=', $reason),
                                    ),
                                ),
                                array(
                                    'fields' => array(
                                        'name',
                                    ),
                                )
                            );
                            if (
                                isset($state) &&
                                isset($leaveRequestRecord) &&
                                !isset($leaveRequestRecord['faultString']) &&
                                isset($leaveRequestRecord[0]['id'])
                            ) {
                                $leaveRequestLineId = $leaveRequestRecord[0]['id'];
    
                                if ($state == 'reject') {
                                    // rejection of leave request
                                    $updated = $models->execute_kw(
                                        $dbname,
                                        $uid,
                                        $userPassword,
                                        'teacher.leave.request',
                                        'write',
                                        array(
                                            array($leaveRequestLineId),
                                            array(
                                                'state' => 'reject',
                                            ),
                                        ),
                                    );
    
                                    // decrease pending leave by float $days
                                    $newPending = $pendinLeaves - (float) $days;
                                    $newAvailable = $availableLeaves + (float) $days;
                                    // update leave allocation line
                                    $lalUpdate = $models->execute_kw(
                                        $dbname,
                                        $uid,
                                        $userPassword,
                                        'leave.allocation.line',
                                        'write',
                                        array(
                                            array($tmp),
                                            array(
                                                'available_leaves' => $newAvailable,
                                                'pending_leaves' => $newPending,
                                                'approved_leaves' => $approvedLeaves,
                                            ),
                                        )
                                    );
    
                                    echo json_encode(
                                        array(
                                            "message"=> "success",
                                            "requestUpdated" => $updated,
                                            "lineUpdated" => $lalUpdate,
                                        )
                                    );
                                } else {
                                    if ($state = 'approve') {
                                        $updated = $models->execute_kw(
                                            $dbname,
                                            $uid,
                                            $userPassword,
                                            'teacher.leave.request',
                                            'write',
                                            array(
                                                array($leaveRequestLineId),
                                                array(
                                                    'state' => 'approve',
                                                ),
                                            ),
                                        );
    
                                        // decrease pending leave by float $days
                                        $newPending = $pendinLeaves - (float) $days;
                                        $newApproved = $approvedLeaves + (float) $days;
                                        // update leave allocation line
                                        $lalUpdate = $models->execute_kw(
                                            $dbname,
                                            $uid,
                                            $userPassword,
                                            'leave.allocation.line',
                                            'write',
                                            array(
                                                array($tmp),
                                                array(
                                                    'available_leaves' => $availableLeaves,
                                                    'pending_leaves' => $newPending,
                                                    'approved_leaves' => $newApproved,
                                                ),
                                            )
                                        );
    
                                        echo json_encode(
                                            array(
                                                "message"=> "success",
                                                "requestUpdated" => $updated,
                                                "lineUpdated" => $lalUpdate,
                                            )
                                        );
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
    }else{
        echo json_encode(serverUnReachable());
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
