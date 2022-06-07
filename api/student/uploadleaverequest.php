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
                    $studentId = $entityBody['studentId'];
                    $classId = $entityBody['classId'];
                    $start = $entityBody['startDate'];
                    $end = $entityBody['endDate'];
                    $reason = $entityBody['reason'];
                    $state = 'toapprove';
    
                    // get dept id;
                    $student = $models->execute_kw(
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
                                'dept_id',
                            ),
                        )
                    );
    
                    if (!isset($student['faultString']) && isset($student)) {
    
                        $deptId = $student[0]['dept_id'][0];
    
                        sleep(1);
                        $hod = $models->execute_kw(
                            $dbname,
                            $uid,
                            $userPassword,
                            'college.department.line',
                            'search_read',
                            array(
                                array(
                                    array('college_id', '=', (int) $collegeId),
                                    array('department_id', '=', $deptId),
    
                                ),
                            ),
                            array(
                                'fields' => array(
                                    'email_id', 'hod',
                                ),
                            )
                        );
    
                        if (!isset($hod['faultString']) && isset($hod)) {
                            $hodEmail = $hod[0]['email_id'];
    
                            $resUsers = $models->execute_kw(
                                $dbname,
                                $uid,
                                $userPassword,
                                'res.users',
                                'search_read',
                                array(
                                    array(
                                        array('login', '=', $hodEmail),
    
                                    ),
                                ),
                                array(
                                    'fields' => array(
                                        'login', 'password',
                                    ),
                                )
                            );
    
                            if (isset($resUsers) && !isset($resUsers['faultString'])) {
                                $hodLoginUserId = $resUsers[0]['id'];
    
                                $teacherId = (int) $hodLoginUserId;
                                $newLeaveRequest = $models->execute_kw(
                                    $dbname,
                                    $uid,
                                    $userPassword,
                                    'studentleave.request',
                                    'create',
                                    array(
                                        array(
                                            'student_id' => (int) $studentId,
                                            'teacher_id' => $teacherId,
                                            'college_id' => (int) $collegeId,
                                            'start_date' => $start,
                                            'end_date' => $end,
                                            'reason' => $reason,
                                            'state' => $state,
                                        ),
                                    )
                                );
                                if ($newLeaveRequest != false &&
                                    isset($newLeaveRequest) &&
                                    !isset($newLeaveRequest['faultString'])
                                ) {
    
                                    echo json_encode(
                                        array(
                                            'message' => 'success',
                                            'data' => $newLeaveRequest,
                                        )
                                    );
    
                                } else {
                                    echo json_encode(
                                        array(
                                            'message' => 'failed',
                                            'error' => $newLeaveRequest,
                                            'data' => $entityBody,
                                        )
                                    );
    
                                }
    
                            } else {
                                echo json_encode(
                                    array(
                                        'message' => 'failed',
                                        'error' => 'Cannot find user id of the HoD of this dept',
                                    )
                                );
                            }
                        } else {
                            echo json_encode(
                                array(
                                    'message' => 'failed',
                                    'error' => 'Cannot find HoD of this department',
                                )
                            );
                        }
                    } else {
                        echo json_encode(
                            array(
                                'message' => 'failed',
                                'error' => 'Cannot find a valid department for this student',
                            )
                        );
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
