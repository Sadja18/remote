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

    if (isset($entityBody['artsScience']) && $entityBody['artsScience'] == 1) {

        if (!isset($uid)) {
            $uid = $common->authenticate($dbname, $user, $password, array());
        }
        if (isset($uid) && !empty($uid)) {
            $models = ripcord::client("$url/xmlrpc/2/object");

            // following query gets courses;
            $coursesArts = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'course.course',
                'search_read',
                array(
                    array(
                        array('no_dept', '=', True)
                    ),
                ),
                array('fields' => array('name', 'college_id', 'duration', 'college_type', 'no_dept'))
            );
            $coursesArts_no_records = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'course.course',
                'search_count',
                array(
                    array(
                        array('no_dept', '=', True)
                    ),
                ),

            );

            $coursesAlt = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'course.course',
                'search_read',
                array(
                    array(
                        array('no_dept', '=', False)
                    ),
                ),
                array('fields' => array('name', 'code', 'college_id', 'graduate', 'duration', 'department_id', 'college_type', 'no_dept'))
            );

            $coursesAlt_no_records = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'course.course',
                'search_count',
                array(
                    array(
                        array('no_dept', '=', False)
                    ),
                ),

            );

            echo json_encode(array(
                'uid' => $uid,
                'no_of_records' => array(
                    'no_of_artsScience'=>$coursesArts_no_records,
                    'no_of_others'=>$coursesAlt_no_records
                ),
                'data' => array(
                    'coursesArts' => $coursesArts,
                    'coursesAlt' => $coursesAlt
                ),
                'message'=>'Success'

            ));
        } else {
            echo json_encode(array(
                'received' => true,
                'login_status' => 'failed'
            ));
        }
    } else {
        echo json_encode(array(
            'received' => false,
            'db' => $dbname
        ));
    }
}
