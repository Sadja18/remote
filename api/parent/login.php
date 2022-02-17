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
$url = getenv('PRIVATE')    ;
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
    "message"=> "Login Failure. This user does not have the required access rights."
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

    if (isset($userName) && isset($userPassword)) {
        $common = ripcord::client($url . '/xmlrpc/2/common');

        // check if the credentials are valid
        $uid = $common->authenticate($dbname, $userName, $userPassword, array());

        if (isset($uid) && $uid != false && $uid != 'false') {

            $models = ripcord::client("$url/xmlrpc/2/object");

            $res = $models->execute_kw(
                $dbname,
                $uid,
                $userPassword,
                'res.users',
                'search_read',
                array(
                    array(
                        array('login', '=', $userName),
                        array('active', '=', True),

                    )
                ),
                array('fields' => array(
                    'login', 'password', 'new_password', 'groups_id', 'display_name'
                ))
            );

            $validity =  $res[0];

            $groups_id = $validity['groups_id'];

            if (in_array(18, $groups_id)) {
                echo json_encode(array(
                    'message' => 'Success',
                    'data' => array(
                        "userId" => $uid,
                        "userName" => $userName,
                        "userPassword" => $userPassword,
                        "dbname" => $dbname,
                        "loginStatus" => '1',
                        'displayName'=> $validity['display_name']
                    )
                ));
            } else {
                echo json_encode($failedLogin);
            }
        } else {
            // if the login credentials were incorrect,
            // echo for now
            echo json_encode($failInvalidCredentials);
        }
    } else {
        echo json_encode($failNoData);
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
