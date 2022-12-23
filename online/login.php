<?php

header('Access-Control-Allow-Origin: *', false);

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json');

// require_once './extra.php';
$login_status = null;
$userID = null;
$dbname = 'doednhdd';
$userName = null;
$userPassword = null;


$privateURL = "http://10.184.4.238:8069";
$publicURL = "http://10.184.49.222:8069";

// mention local or public url
// $url = $privateURL;

$url = $publicURL;

$arr = array();
require_once './ripcord/ripcord.php';

if ($_SERVER['REQUEST_METHOD'] == "POST" || $_SERVER['REQUEST_METHOD'] == 'GET') {
    // file_get_contents will use read only raw data stream php://input
    // to get a json like data object
    $entityBodyJSON = file_get_contents('php://input');
    // decode the afore mentoned json like object
    $entityBody = json_decode($entityBodyJSON);
    // echo json_encode(
    //     array(
    //         "p"=> $entityBody,
    //         'g'=> gettype($entityBody),
    //     )
    // );

    $userName = $entityBody-> user;
    $userPassword = $entityBody->password;
    
    if (isset($entityBody->dbname)) {
        $dbname = $entityBody->dbname;
    } else {
        $dbname = 'doednhdd';
    }

    $common = ripcord::client($url . '/xmlrpc/2/common');

    // echo "13";

    $userID = $common->authenticate($dbname, $userName, $userPassword, array());


    if (empty($userID) || !isset($userID) || $userID == 0 || $userID == false) {

        $arr = array(
            "message" => "Invalid credentials",
            'login_status' => 0,
            'dbname' => $dbname
        );
        echo json_encode($arr);
    } else {
        
        $models = ripcord::client("$url/xmlrpc/2/object");
        // sleep(1);
        
        $record = $models->execute_kw(
            $dbname,
            $userID,
            $userPassword,
            'school.school',
            'search_read',
            array(
                array(
                    array(
                        'email', '=', $userName,
                    )
                ),
            ),
            array(
                'fields' => array('email', 'com_name'),
            ),
        );
        
        if (isset($record) && !isset($record['faultString']) && isset($record[0]['id'])) {
            /// is headmaster
            $arr = array(
                'user' => $entityBody['user'],
                'password' => $entityBody['password'],
                'dbname' => $dbname,
                'login_status' => 1,
                'userID' => $userID,
                'headMaster' => 'yes',
                'schoolId' => $record[0]['id'],
                'isOnline' => 1
            );
            echo json_encode($arr);
        } else {
            /// is not headmaster
            $school = $models->execute_kw(
                $dbname,
                $userID,
                $userPassword,
                'school.school',
                'search_read',
                array(
                    array(
                        array(
                            'name', '!=', FALSE
                        )
                    )
                ),
                array(
                    'fields' => array(
                        'com_name'
                    ),
                ),
            );
            
            $schoolId = $school[0]['id'];
            
            echo json_encode(
                array(
                    'user' => $entityBody->user,
                    'password' => $entityBody->password,
                    'dbname' => $dbname,
                    'login_status' => 1,
                    'userID' => $userID,
                    'schoolId' => $school[0]['id'],
                    'headMaster' => 'no',
                    'isOnline' => 1
                )
            );
        }
    }
} else {
    echo json_encode(array(
        "message" => "failed", "code" => "not post request", 'j' => $_SERVER['REQUEST_METHOD']
    ));
}
