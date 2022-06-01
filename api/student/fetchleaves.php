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
    'message' => 'Invalid Request',
);

$failNoData = array(
    'message' => 'Please pass required parameters',
);

$failInvalidCredentials = array(
    "message" => "Invalid Credentials",
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
    
            if (isset($uid) && $uid != false && $uid != 'false' && !isset($uid['faultString'])) {
                // if the login credentials were correct,
                $models = ripcord::client("$url/xmlrpc/2/object");
    
                $sessionData = $models->execute_kw(
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
                    array('fields' => array('date_start', 'date_stop'))
                );
                sleep(1);
                if (!isset($sessionData['faultString'])) {
                    $start = $sessionData[0]['date_start'];
                    $end = $sessionData[0]['date_stop'];
    
                    $count = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'studentleave.request',
                        'search_count',
                        array(
                            array(
                                array('start_date', '>=', $start),
                                array('start_date', '<=', $end),
                                array('end_date', '>=', $start),
                                array('end_date', '<=', $end),
                            ),
                        )
                    );
                    if (!isset($count['faultString'])) {
                        $data = $models->execute_kw(
                            $dbname,
                            $uid,
                            $userPassword,
                            'studentleave.request',
                            'search_read',
                            array(
                                array(
                                    array('start_date', '>=', $start),
                                    array('start_date', '<=', $end),
                                    array('end_date', '>=', $start),
                                    array('end_date', '<=', $end),
                                ),
                            ),
                            array(
                                'fields' => array('name', 'student_id', 'roll_no',
                                    'start_date', 'end_date', 'days', 'reason', 'state',
                                    'class_id', 'college_id'),
                            )
                        );
                        echo json_encode(
                            array(
                                "message" => "success",
                                "count" => $count,
                                "data" => $data,
                            )
                        );
                    } else {
                        echo json_encode($count);
                    }
                } else {
                    echo json_encode($sessionData);
    
                }
            }
        }
    }else{
        echo json_encode(serverUnReachable());
    }

}
