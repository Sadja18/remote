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

$user = 'deptadmin@gmail.com';
$password = 'deptadmin@1234';
$dbname = 'college';
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

    if (isset($_GET['students']) && $_GET['students'] == 1) {

        if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
            $class_id = $_GET['class_id'];

            $uid = $common->authenticate($dbname, $user, $password, array());
            if (isset($uid) && !empty($uid)) {

                $models = ripcord::client("$url/xmlrpc/2/object");

                $no_of_students = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'student.student',
                    'search_count',
                    array(
                        array(
                            array('class_id', '=', (int)$class_id),
                            array('state', '=', 'done'),
                        )
                    )
                );
                $students = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'student.student',
                    'search_read',
                    array(
                        array(
                            array('class_id', '=', (int)$class_id),
                            array('state', '=', 'done'),
                            // array('name', '!=', False),
                        )
                        ),
                        array('fields'=>array('name', 'college_id', 'class_id', 'course_id'))
                );

                echo json_encode(
                    array(
                        'no_of_records'=>$no_of_students,
                        'data'=>$students,
                        'message'=>'Success',
                    )
                );
            }
        }
    }
}
