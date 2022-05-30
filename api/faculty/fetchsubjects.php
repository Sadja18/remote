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

    if(isSiteAvailable($url)){
        if (isset($userName) && isset($userPassword)) {
            $common = ripcord::client($url . '/xmlrpc/2/common');
    
            sleep(3);
    
            // check if the credentials are valid
            $uid = $common->authenticate($dbname, $userName, $userPassword, array());
    
            if (isset($uid) && $uid != false && $uid != 'false' && !isset($uid['faultCode'])) {
    
                $models = ripcord::client("$url/xmlrpc/2/object");
    
                // $deptId = $entityBody['deptId'];
                $collegeId = $entityBody['collegeId'];
                $courseId = $entityBody['courseId'];
                $semId = $entityBody['semId'];
                $yearId=$entityBody['yearId'];
    
                // echo json_decode(array(''))
    
                $recordCount = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'subject.subject',
                    'search_count',
                    array(
                        array(
                            array('college_id', '=', (int) $collegeId),
                            // array("course_id", "=", (int)$courseId),
                            array('year_id', "=", (int)$yearId),
                            array("sem_id", "=", (int)$semId),
                        )
                    ),
                );
    
                if(!isset($recordCount['faultString'])){
                    $records = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'subject.subject',
                        'search_read',
                        array(
                            array(
                                array('college_id', '=', (int) $collegeId),
                                // array("course_id", "=", (int)$courseId),
                                array('year_id', "=", (int)$yearId),
                                array("sem_id", "=", (int)$semId),
                            )
                        ),
                        array(
                            'fields' => array(
                                'name', 'display_name', "code", 
                                'course_id', 'department_id', 'year_id', 
                                'sem_id','college_id'
                            ),
                        ),
                    );
        
        
                    $response = array(
                        "message" => "success",
                        'no_of_records' => $recordCount,
                        "data" => $records
                    );
        
                    echo json_encode($response);
                }else{
                    echo json_encode(
                        array(
                            "message"=> "failed",
                            "faultCode"=> $recordCount['faultCode'],
                            "data"=> $recordCount['faultString'],
                            
                        )
                    );
                }
    
                
    
                
            } else {
                // if the login credentials were incorrect,
                // echo for now\
                // $failInvalidCredentials['uid'] = $uid['faultString'];
                echo json_encode($failInvalidCredentials);
            }
        } else {
            echo json_encode($failNoData);
        }
    }else{
        echo json_encode(serverUnReachable());
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
