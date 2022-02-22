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

                $courseId = $entityBody['courseId'];
                $collegeId =$entityBody['collegeId'];
                $yearId = $entityBody['yearId'];
                $semId = $entityBody['semId'];

            

                if(isset($entityBody['classId'])){
                    $classId = $entityBody['classId'];
                    $records = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'student.student',
                        'search_read',
                        array(
                            array(
                                array('college_id', '=',(int)$collegeId),
                                array('course_id', '=', (int)$courseId),
                                array('colyear', '=', (int)$yearId)
                            )
                        ),
                        array(
                            "fields"=> array("user_id", "student_name", "student_code", 'course_id', "class_id", "year", "colyear", "semester")
                        )
                        
                    );
    
                    $response = array(
                        "message" => "success",
                        'no_of_records' => count($records),
                        "data" => $records
                    );
    
                    echo json_encode($response);

                }else{
                    $records = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'student.student',
                        'search_read',
                        array(
                            array(
                                array('college_id', '=',(int)$collegeId),
                                array('course_id', '=', (int)$courseId),
                                array('colyear', '=', (int)$yearId)
                            )
                        ),
                        array(
                            "fields"=> array("user_id", "student_name", "student_code", 'course_id', "class_id", "year", "colyear", "semester")
                        )
                        
                    );
    
                    $response = array(
                        "message" => "success",
                        'no_of_records' => count($records),
                        "data" => $records
                    );
    
                    echo json_encode($response);
                }

                
            } else {
                // if the login credentials were incorrect,
                // echo
                echo json_encode($failInvalidCredentials);
            }
        } else {
            echo json_encode($failNoData);
        }
    }else{
        echo json_encode(array('message'=> 'Invalid request'));
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
