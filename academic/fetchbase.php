<?php
require_once './ripcord/ripcord.php';
require_once './envRead.php';

use sadja\DotEnv;

header('Access-Control-Allow-Origin: ' . $_SERVER['REMOTE_ADDR'], false);

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

(new DotEnv(__DIR__ . '/.env'))->load();

// $url = getenv('PUBLICALT');
// $url = getenv('PRIVATEALT');
// $url = getenv('PUBLIC');
$url = getenv('PRIVATE');

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

    if (isset($entityBodyJSON)) {

        $entityBody = json_decode($entityBodyJSON, true);


        if (isset($entityBody) && $entityBody != false) {
            

            $userName = $entityBody['userName'];
            $userPassword = $entityBody['userPassword'];

            $dbname = null;
            if (isset($entityBody['dbname'])) {
                $dbname = $entityBody['dbname'];
            } else {
                $dbname = 'school';
            }

            if (isset($userName) && isset($userPassword)) {
                $common = ripcord::client($url . '/xmlrpc/2/common');

                $uid = $common->authenticate($dbname, $userName, $userPassword, array());
                if (isset($uid) && $uid != false && $uid != 'false') {
                    $models = ripcord::client("$url/xmlrpc/2/object");

                    $academicYear = $models->execute_kw(
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
                        array(
                            "fields" => array(
                                "sequence",
                                "name",
                                "code",
                                "date_start",
                                "date_stop",
                                "grade_id",
                            ),
                        )
                    );

                    if (!isset($academicYear['faultString']) && isset($academicYear) && $academicYear != false) {
                        echo json_encode(
                            array(
                                "message" => "success",
                                "data" => $academicYear[0],
                            )
                        );

                    } else {
                        echo json_encode(
                            array(
                                "message" => "failed",
                                "data" => "no academic year selected",
                            )
                        );

                    }
                } else {
                    echo json_encode($failedLogin);

                }
            } else {
                echo json_encode($failInvalidCredentials);

            }
        } else {
            echo json_encode($failNoData);

        }
    } else {
        echo json_encode($failNoData);
    }
} else {
    echo json_encode($failNotPost);
}
