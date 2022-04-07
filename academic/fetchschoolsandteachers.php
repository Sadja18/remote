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

            $districtId = $entityBody['districtId'];
            $clusterId = $entityBody['clusterId'];

            $dbname = null;
            if (isset($entityBody['dbname'])) {
                $dbname = $entityBody['dbname'];
            } else {
                $dbname = 'school';
            }

            if (isset($userName) && isset($userPassword) && isset($districtId) && isset($clusterId)) {
                $common = ripcord::client($url . '/xmlrpc/2/common');

                $uid = $common->authenticate($dbname, $userName, $userPassword, array());
                if (isset($uid) && $uid != false && $uid != 'false') {
                    $models = ripcord::client("$url/xmlrpc/2/object");

                    $schoolsCount = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        "school.school",
                        "search_count",
                        array(
                            array(
                                // array("name", "!=", false),
                                array('cluster', '=', (int) $clusterId),
                                array('block', '=', (int) $districtId),
                            ),
                        )
                    );
                    $schoolsData = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        "school.school",
                        "search_read",
                        array(
                            array(
                                // array("name", "!=", false),
                                array('cluster', '=', (int) $clusterId),
                                array('block', '=', (int) $districtId),
                            ),
                        ),
                        array(
                            "fields" => array(
                                "com_name",
                                "code",
                                'cluster',
                                "block",
                                'location',
                            ),
                        )
                    );

                    sleep(2);

                    $teachersCount = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        "school.teacher",
                        "search_count",
                        array(
                            array(
                                array("name", "!=", false),

                            ),
                        ),
                    );

                    $teachersData = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        "school.teacher",
                        "search_read",
                        array(
                            array(
                                array("name", "!=", false),
                            ),
                        ),
                        array(
                            "fields" => array(
                                "employee_id",
                                "standard_id",
                                "teacher_code",
                                "name",
                                "school_id",
                                "stand_id",
                                "personal_fileno",
                                "block",
                                "cluster",

                            ),
                        )
                    );

                    if (
                        !isset($schoolsData['faultString']) &&
                        isset($schoolsData) &&
                        $schoolsData != false &&
                        !isset($teachersData['faultString']) &&
                        isset($teachersData) &&
                        $teachersData != false
                    ) {
                        echo json_encode(
                            array(
                                "message" => "success",
                                'count' => array(
                                    "schools" => $schoolsCount,
                                    "teachers" => $teachersCount,

                                ),

                                "data" => array(
                                    "schools" => $schoolsData,
                                    "teachers" => $teachersData,
                                ),

                            )
                        );

                    } else {
                        echo json_encode(
                            array(
                                "error" => array(
                                    "schools" => $schoolsData,

                                    "teachers" => $teachersData,
                                ),
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
