<?php

$privateURL = "http://14.139.180.56:8069";
$publicURL = "http://14.139.180.56:8069";

// $url = $privateURL;
$url = $publicURL;

$password = null;
$user = null;
$dbname = null;

require_once './ripcord/ripcord.php';
header('Access-Control-Allow-Origin: *', false);
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json');

header("Access-Control-Allow-Headers: X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // echo "POST request recieved to give attendance data";

    $entityBodyJSON = file_get_contents("php://input");

    $entityBody = get_object_vars(json_decode($entityBodyJSON));

    // echo json_encode($entityBody);

    $user = $entityBody['userName'];
    $password = $entityBody['userPassword'];

    if (isset($entityBody['dbname'])) {
        $dbname = $entityBody['dbname'];
    } else {
        $dbname = 'doednhdd';
    }

    if (isset($entityBody['sync'])) {
        $response = array();

        $leaveTypeName = $entityBody['leaveTypeId'];

        $startDate = $entityBody['start_date'];
        $endDate = $entityBody['end_date'];
        $schoolId = $entityBody['schoolId'];
        $teacherId = $entityBody['teacherId'];

        $days = $entityBody['days'];
        $reason = $entityBody['reason'];

        if (
            isset($endDate)
            && isset($startDate)
            && isset($schoolId)
            && isset($teacherId)
            && isset($days)
            && isset($reason)
            && isset($leaveTypeName)
        ) {
            $common = ripcord::client($url . '/xmlrpc/2/common');

            $uid = $common->authenticate($dbname, $user, $password, array());

            $models = ripcord::client("$url/xmlrpc/2/object");

            $recordCreateId = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'teacher.leave.request',
                'create',
                array(
                    array(
                        'name'=>(int) $leaveTypeName,
                        'school_id'=>(int)$schoolId,
                        // 'user_id'=> (int) 12,
                        'staff_id'=>(int) $teacherId,
                        'start_date'=> $startDate,
                        'end_date'=> $endDate,
                        'reason'=> $reason,
                        'days'=> (int) $days,
                        'state'=> 'toapprove'
                    )
                )
            );
            

            echo json_encode($recordCreateId);
            
        } else {
            echo json_encode(
                array(
                    't' => $entityBody,
                )
            );
        }
    }
}
