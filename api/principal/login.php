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
    
            // check if the credentials are valid
            $uid = $common->authenticate($dbname, $userName, $userPassword, array());
    
            if (isset($uid) && $uid != false && $uid != 'false') {
    
                $models = ripcord::client("$url/xmlrpc/2/object");
    
                $collAdmin = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'college.college',
                    'search_read',
                    array(
                        array(
                            array('email', '=', $userName),
                        ),
                    ),
                    array('fields' => array('email', 'head_name', 'display_name', 'com_name', 'code', 's_image'))
                );
    
                if (!isset($collAdmin['faultString'])) {
                    $collAdmin[0]['userId'] = $uid;
                    $collAdmin[0]['userName'] = $userName;
                    $collAdmin[0]['userPassword'] = $userPassword;
                    $collAdmin[0]['dbname'] = $dbname;
    
                    echo json_encode(
                        array(
                            "message" => "success",
                            "data" => $collAdmin,
                        )
                    );
                } else {
                    echo json_encode($collAdmin);
                }
    
            } else {
                // if the login credentials were incorrect,
                // echo for now
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
