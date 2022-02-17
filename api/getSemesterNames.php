<?php
require_once './ripcord/ripcord.php';
require_once './envRead.php';

use sadja\DotEnv;

header('Access-Control-Allow-Origin: ' . $_SERVER['REMOTE_ADDR'], false);

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

(new DotEnv(__DIR__ . '/.env'))->load();

$url = getenv('PUBLIC');

$user = null;
$password = null;
$dbname = null;
$response = null;
$uid = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'get') {
    $entityBody = $_GET;
    if (isset($_GET['userID'])) {
        $uid = $_GET['userID'];
        $password = $_GET['userPassword'];
    } else {
        if (isset($entityBody['userName'])) {
            $user = $entityBody['userName'];
        }
        if (isset($entityBody['userPassword'])) {
            $password = $entityBody['userPassword'];
        }
    }
    if (isset($entityBody['dbname'])) {
        $dbname = $entityBody['dbname'];
    } else {
        $dbname = 'college';
    }

    $common = ripcord::client($url . '/xmlrpc/2/common');

    $uid = $common->authenticate($dbname, $user, $password, array());

    echo json_encode(
        array(
            'h'=> $entityBody,
            'g'=> $uid
        )
    );
    // if (isset($uid) && !empty($uid)) {
    //     $models = ripcord::client("$url/xmlrpc/2/object");

    //     $semester_names_count = $models->execute_kw(
    //         $dbname,
    //         $uid,
    //         $password,
    //         'academic.year.sem',
    //         'search_count',
    //         array(
    //             array(
    //                 array('id', '!=', False)
    //             )
    //         )
    //     );
    //     $semester_names = $models->execute_kw(
    //         $dbname,
    //         $uid,
    //         $password,
    //         'academic.year.sem',
    //         'search_read',
    //         array(
    //             array(
    //                 array('id', '!=', False)
    //             )
    //         ),
    //         array('fields' => array('id', 'name', 'year_id'))
    //     );
    //     echo json_encode(
    //         array(
    //             'no_of_records' => $semester_names_count,
    //             'data' => $semester_names,
    //             'message' => 'success'
    //         )
    //     );
    // }
}
