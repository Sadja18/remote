<?php

header('Access-Control-Allow-Origin: *', false);

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

$login_status = null;
$userID = null;
$dbname = null;
$userName = null;
$userPassword = null;


$privateURL = "http://10.184.4.238:8069";
$publicURL = "http://14.139.180.56:8069";

// mention local or public url
// $url = $privateURL;

$url = $publicURL;


// session_start();
require_once './extra.php';
require_once './ripcord/ripcord.php';
header('Access-Control-Allow-Origin: *');

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

$arr = array();

if(isSiteAvailible($url)){
    if ($_SERVER['REQUEST_METHOD'] == "POST") {

        // file_get_contents will use read only raw data stream php://input
        // to get a json like data object
        $entityBodyJSON = file_get_contents('php://input');
    
        // decode the afore mentoned json like object
        $entityBody = json_decode($entityBodyJSON, true);
    
        $userName = $entityBody['user'];
        $userPassword = $entityBody['password'];
    
        if (isset($entityBody['dbname'])) {
            $dbname = $entityBody['dbname'];
        } else {
            $dbname = 'doednhdd';
        }
    
        $common = ripcord::client($url . '/xmlrpc/2/common');
    
    
        $userID = $common->authenticate($dbname, $userName, $userPassword, array());
    
        if (empty($userID) or !isset($userID) or $userID == 0 or $userID == false) {
            $arr = array(
                'login_status' => 0,
                'dbname'=> $dbname
            );
        } else {
            $models = ripcord::client("$url/xmlrpc/2/object");
            sleep(1);
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
                    'fields'=>array('email', 'com_name'),
                ),
            );
            if(isset($record) && !isset($record['faultString']) && isset($record[0]['id'])){
                $arr = array(
                    'user' => $entityBody['user'],
                    'password' => $entityBody['password'],
                    'dbname' => $dbname,
                    'login_status' => 1,
                    'userID' => $userID,
                    'headMaster'=> 'yes',
                    'schoolId'=> $record[0]['id'],
                    'isOnline' => 1
                );
    
            }else{
                sleep(1);
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
                        'fields'=> array(
                            'com_name'
                        ),
                    ),
                );
                $schoolId = $school[0]['id'];
                $arr = array(
                    'user' => $entityBody['user'],
                    'password' => $entityBody['password'],
                    'dbname' => $dbname,
                    'login_status' => 1,
                    'userID' => $userID,
                    'schoolId'=> $school[0]['id'],
                    'headMaster'=> 'no',
                    'isOnline' => 1
                );
            }
        }
    
        // header('Access-Control-Allow-Origin: http://10.184.6.81', false);
    
       
        echo json_encode($arr);
    }
}else{
    echo json_encode(serverUnReachable());
}
