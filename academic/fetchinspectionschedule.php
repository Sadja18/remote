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
            $academicYear = $entityBody['academicYear'];

            $dbname = null;
            if (isset($entityBody['dbname'])) {
                $dbname = $entityBody['dbname'];
            } else {
                $dbname = 'school';
            }

            if (isset($userName) && isset($userPassword) && isset($academicYear)) {
                $common = ripcord::client($url . '/xmlrpc/2/common');

                $uid = $common->authenticate($dbname, $userName, $userPassword, array());
                if (isset($uid) && $uid != false && $uid != 'false') {
                    $models = ripcord::client("$url/xmlrpc/2/object");

                    $queryState = 'confirm';

                    $inspectionScheduleCount = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'inspection.schedule',
                        'search_count',
                        array(
                            array(
                                array('year_id', '=', $academicYear),
                                array('state', '=', $queryState),
                            ),
                        )
                    );
                    $inspectionSchedules = $models->execute_kw(
                        $dbname,
                        $uid,
                        $userPassword,
                        'inspection.schedule',
                        'search_read',
                        array(
                            array(
                                array('year_id', '=', $academicYear),
                                array('state', '=', $queryState),
                            ),
                        ),
                        array(
                            "fields" => array(
                                "name",
                                'date',
                                'year_id',
                                'round_no',
                                'state',
                            ),
                        )
                    );

                    $inspectionScheduleDetails = array();
                    $inspectionScheduleDetailsCount = 0;

                    if (isset($inspectionSchedules) && !isset($inspectionSchedules['faultString'])) {

                        foreach ($inspectionSchedules as $inspectionSchedule) {
                            $recordId = $inspectionSchedule['id'];

                            $inspectionScheduleLinesCount = $models->execute_kw(
                                $dbname,
                                $uid,
                                $userPassword,
                                'inspection.schedule.line',
                                'search_count',
                                array(
                                    array(
                                        array("rec_id", '=', $recordId),
                                    ),
                                )
                            );

                            if (isset($inspectionScheduleLinesCount) && !isset($inspectionScheduleLinesCount['faultString'])) {

                                $inspectionScheduleDetailsCount = $inspectionScheduleDetailsCount + $inspectionScheduleLinesCount;

                                $inspectionScheduleLines = $models->execute_kw(
                                    $dbname,
                                    $uid,
                                    $userPassword,
                                    'inspection.schedule.line',
                                    'search_read',
                                    array(
                                        array(
                                            array("rec_id", '=', $recordId),
                                        ),
                                    ),
                                    array()
                                );
                                array_push($inspectionScheduleDetails, $inspectionScheduleLines);
                            }
                        }

                        echo json_encode(
                            array(
                                "message" => "success",
                                "count" => array(
                                    "inspectionSchedule" => $inspectionScheduleCount,
                                    "inspectionScheduleDetails" => $inspectionScheduleDetailsCount,
                                ),
                                "data" => array(
                                    "inspectionSchedule" => $inspectionSchedule,
                                    "inspectionScheduleDetails" => $inspectionScheduleDetails,
                                ),
                            )
                        );

                    }

                } else {
                    echo json_encode(
                        array(
                            "message" => "failed",
                            "error" => "Invalid credentials",
                        )
                    );
                }

            } else {
                json_encode(array(
                    "message" => "failed",
                    "error" => "Missing required parameters",
                ));
            }
        } else {
            json_encode(array(
                "message" => "failed",
                "error" => "Data is expected in request body",
            ));
        }

    } else {
        json_encode(array(
            "message" => "failed",
            "error" => "Data is expected in request body",
        ));
    }

} else {
    echo json_encode($failNotPost);
}
