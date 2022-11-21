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

    if (isset($entityBody['str'])) {
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
                sleep(0.25);
                $common = ripcord::client($url . '/xmlrpc/2/common');
    
                // check if the credentials are valid
                $uid = $common->authenticate($dbname, $userName, $userPassword, array());
                sleep(0.25);

    
                if (isset($uid) && $uid != false && $uid != 'false') {
    
                    $models = ripcord::client("$url/xmlrpc/2/object");
    
                    $collegeId = $entityBody['collegeId'];
    
                    $session = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'academic.year',
                        'search_read',
                        array(
                            array(
                                array('current', '=', true),
                            ),
                        ),
                        array('fields' => array('date_start', 'date_stop'),
                        )
                    );
                    sleep(2);
                    // $zero = $session[0];
                    // $dateStart = $zero['date_start'];
                    // $dateEnd = $zero['date_stop'];
    
                    $leaveCount = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'studentleave.request',
                        'search_count',
                        array(
                            array(
                                array("college_id", "=", (int) $collegeId),
                                array('state', '=', 'toapprove'),
                                // array('app_date', '>=', $dateStart),
                                // array('app_date', '<=', $dateEnd),
                            ),
                        ),
    
                    );
    
                    $leaveApps = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'studentleave.request',
                        'search_read',
                        array(
                            array(
                                array("college_id", "=", (int) $collegeId),
                                array('state', '=', 'toapprove'),
                                // array('app_date', '>=', $dateStart),
                                // array('app_date', '<=', $dateEnd),
                            ),
                        ),
                        array(
                            'fields' => array(
                                "name", "student_id", "roll_no", 
                                "class_id", "college_id", "attachments", 
                                "start_date", "end_date",
                                "teacher_id", "days", "reason", "state"
                            ),
    
                        ),
                    );

                    $echoRecords = array();

                    foreach($leaveApps as $leaveRequest){
                        $leaveId = $leaveRequest['id'];
                        $studentId = $leaveRequest['student_id'][0];

                        sleep(0.25);

                        $studentRecord = $models->execute_kw(
                            $dbname,
                            $uid, 
                            $userPassword,
                            "student.student",
                            "search_read",
                            array(
                                array(
                                    array(
                                        'id', '=', $studentId,
                                    )
                                )
                            ),
                            array(
                                'fields'=> array("student_name","middle", "last",'dept_id', 'class_id', 'user_id')
                            )
                        );$leaveStudentProfileId = $studentRecord[0]['id'];
                        $lCollegeId = 0;
                        $middle = "";
                        $last = "";
                        $deptId = 0;
                        $deptName = "";
                        $classId = 0;
                        $className = "";
                        $leaveAttachment="";
                        if(isset($leaveRequest['attachments']) && $leaveRequest['attachments']!=false){
                            $lCollegeId = $leaveRequest['attachments'];
                        }
                        if(isset($leaveRequest['college_id']) && $leaveRequest['college_id']!=false){
                            $lCollegeId = $leaveRequest['college_id'][0];
                        }
                        if(isset($studentRecord[0]['middle']) && $studentRecord[0]['middle']!=false){
                            $middle = $studentRecord[0]['middle'];
                        }
                        if(isset($studentRecord[0]['last']) && $studentRecord[0]['last']!=false){
                            $last = $studentRecord[0]['last'];
                        }
                        if( isset($studentRecord[0]['class_id']) && $studentRecord[0]['class_id']!=false){
                            $classId = $studentRecord[0]['class_id'][0];
                            $className = $studentRecord[0]['class_id'][1];
                        }
                        if(isset($studentRecord[0]['dept_id']) && $studentRecord[0]['dept_id']!=false){
                            $deptId = $studentRecord[0]['dept_id'][0];
                            $deptName = $studentRecord[0]['dept_id'][1];
                        }
                        

                        $newRecord = array(
                            'leaveId'=> $leaveId,
                            "leaveStudentProfileId"=> $studentId,
                            'leaveFromDate'=> $leaveRequest['start_date'],
                            "leaveToDate"=> $leaveRequest['end_date'],
                            "leaveDays"=> $leaveRequest['days'],
                            "leaveReason"=> $leaveRequest['reason'],
                            "leaveAttachment"=> $leaveAttachment,
                            "leaveStatus"=> $leaveRequest['state'],
                            "leaveStudentCollegeId"=>$lCollegeId,
                            "leaveStudentUserId"=> $studentRecord[0]['user_id'][0],
                            "leaveStudentFirstName"=> $studentRecord[0]['student_name'],
                            "leaveStudentMiddleName"=> $middle,
                            "leaveStudentLastName"=> $last,
                            "leaveStudentClassId"=> $classId,
                            "leaveStudentClassName"=> $className,
                            "leaveStudentDeptId"=>  $deptId,
                            "leaveStudentDeptName"=> $deptName,
                        );

                        array_push($echoRecords, $newRecord);
                    }
                    $response = array(
                        'no_of_records' => $leaveCount,
                        'data' => $echoRecords,
                        // 'session academic'=> $session,
                        'message' => 'Success',
    
                    );
    
                    echo json_encode($response);
                } else {
                    // if the login credentials were incorrect,
                    // echo
                    echo json_encode($failInvalidCredentials);
                }
            } else {
                echo json_encode($failNoData);
            }
        }else{
            echo json_encode(serverUnReachable());
        }
    } else {
        echo json_encode(array('message' => 'Invalid request'));
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
