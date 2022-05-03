<?php
require_once './envRead.php';
require_once './ripcord/ripcord.php';


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

    if (isset($userName) && isset($userPassword)) {
        $common = ripcord::client($url . '/xmlrpc/2/common');
    // echo 12;


        // check if the credentials are valid
        $uid = $common->authenticate($dbname, $userName, $userPassword, array());

        if (isset($uid) && $uid != false && $uid != 'false') {

            $models = ripcord::client("$url/xmlrpc/2/object");

            $data = $models->execute_kw(
                $dbname,
                $uid, 
                $userPassword,
                'academic.year',
                'search_read',
                array(
                    array(
                        array('current', '=', True),
                    )
                ),
                array('fields'=>array('name','code','date_start', 'date_stop', 'oddsem_startdate', 'oddsem_enddate', 'evensem_startdate', 'evensem_enddate'))
            );

            echo json_encode(array(
                'message' => 'success',
                'data' => $data
            ));
        }
    }
}
