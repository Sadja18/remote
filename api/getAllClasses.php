<?php
require_once './ripcord/ripcord.php';
require_once './envRead.php';

use sadja\DotEnv;

header('Access-Control-Allow-Origin: ' . $_SERVER['REMOTE_ADDR'], false);

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

(new DotEnv(__DIR__ . '/.env'))->load();

// $url = getenv('PUBLIC');
$url = getenv('PRIVATE');

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

    if (isset($_GET['classFetch']) && $_GET['classFetch'] == 1) {
        $course_id = $_GET['course_id'];
        $college_id = $_GET['college_id'];
        $semester = $_GET['semester'];

        if (!empty($course_id) && !empty($college_id) && !empty($semester)) {

            $uid = $common->authenticate($dbname, $user, $password, array());
            if (isset($uid) && !empty($uid)) {
                $models = ripcord::client("$url/xmlrpc/2/object");

                $semester_id = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'academic.year.sem',
                    'search_read',
                    array(
                        array(
                            array('name', '=', $semester),
                        ),
                    ),
                    array('fields' => array('id'))
                );

                $semester_id = $semester_id[0]['id'];

                $no_of_classes = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'class.class',
                    'search_count',
                    array(
                        array(
                            array('college_id', '=', (int)$college_id),
                            array('course_id', '=', (int)$course_id),
                            array('sem_id', '=', $semester_id),
                            // array('name', '!=', False)
                        ),
                    ),
                );

                $classes = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'class.class',
                    'search_read',
                    array(
                        array(
                            array('college_id', '=', (int)$college_id),
                            array('course_id', '=', (int)$course_id),
                            array('sem_id', '=', $semester_id),
                            // array('name', '!=', False)
                        ),
                    ),
                    array('fields' => array('id', 'name', 'course_id', 'college_id', 'sem_id')),
                );

                $response = array(
                    'no_of_records' => $no_of_classes,
                    'data' => $classes,
                    'message' => 'Success',
                );

                echo json_encode($response);
            } else {
                $response = array(
                    'data' => '',
                    'message' => 'Login failed'
                );

                echo json_encode($response);
            }
        }else{
            echo json_encode(array(
                'hfadfkhadfa'=> $_GET
            ));
        }
    } else if (isset($_GET['subjectFetch']) && $_GET['subjectFetch'] == 1) {
        $uid = $common->authenticate($dbname, $user, $password, array());
        if (isset($uid) && !empty($uid)) {
            $models = ripcord::client("$url/xmlrpc/2/object");

            $response = array(
                'no_of_records' => "Deve",
                'data' => 'subject fetch data goes here',
                'message' => 'Success'
            );

            echo json_encode($response);
        } else {
            $response = array(
                'data' => '',
                'message' => 'Login failed',
                'b'=> var_dump($uid)
            );

            echo json_encode($response);
        }
    } else {
        $response = array(
            'user' => $user,
            'message' => 'Here'
        );
        echo json_encode($response);
    }
}
