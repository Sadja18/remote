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
$url = getenv('PRIVATE');
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

    if (isset($entityBody['str'])) {
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

                $deptId = $entityBody['deptId'];
                $collegeId = $entityBody['collegeId'];
                $teacherId = $entityBody['teacherId'];

                // $leaveAllocationCount = $models->execute_kw(
                //     $dbname,
                //     $uid,
                //     $userPassword,
                //     'leave.allocation.line',
                //     'search_count',
                //     array(
                //         array(
                //             array("college_id", "=", (int)$collegeId),
                //             array("dept_name", "=", (int)$deptId),
                //             array("faculty_name", "=", (int)$teacherId)

                //         )
                //     ),
                    
                // );

                // $leaveAllocation = $models->execute_kw(
                //     $dbname,
                //     $uid,
                //     $userPassword,
                //     'leave.allocation.line',
                //     'search_read',
                //     array(
                //         array(
                //             array("college_id", "=", (int)$collegeId),
                //             array("dept_name", "=", (int)$deptId),
                //             array("faculty_name", "=", (int)$teacherId)

                //         )
                //     ),
                //     array(
                //         'fields'=> array(
                //             "faculty_name",
                //             "no_leaves",
                //             "pending_leaves",
                //             "approved_leaves",
                //             "available_leaves",
                //             "allocation_id",
                //             "college_id",
                //             "dept_name",
                //             "year",
                //             "leave_type"
                //         )
                //     ),
                // );

                $college = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'college.college',
                    'search_read',
                    array(
                        array(
                            array("com_name", '!=', False),
                        )
                    ),
                    array(
                        'fields' => array('com_name', "display_name", 'code', 'head_name'),
                    ),
                );

                $session = $models->execute_kw(
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
                    array('fields' => array('name', 'code', 'date_start', 'date_stop', 'oddsem_startdate', 'oddsem_enddate', 'evensem_startdate', 'evensem_enddate'))
                );
                
                $yearsCount = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'academic.year.year',
                    'search_count',
                    array(
                        array(
                            array("name", '!=', False),
                        )
                    ),
                );

                $years = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'academic.year.year',
                    'search_read',
                    array(
                        array(
                            array("name", '!=', False),
                        )
                    ),
                    array(
                        'fields' => array('name', 'code'),
                    ),
                );
                $semestersCount = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'academic.year.sem',
                    'search_count',
                    array(
                        array(
                            array('year_id', '!=', False),
                            array('name', '!=', False)
                        )
                    ),
                );

                $semesters = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'academic.year.sem',
                    'search_read',
                    array(
                        array(
                            array('year_id', '!=', False),
                            array('name', '!=', False)
                        )
                    ),
                    array('fields' => array('name', 'code','year_id'))
                );

                $coursesArts = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'course.course',
                    'search_read',
                    array(
                        array(
                            array('no_dept', '=', True),
                            array('college_id', '=', (int)$collegeId),
                        ),
                    ),
                    array('fields' => array('name', 'college_id', 'duration', 'no_dept'))
                );
                $coursesArts_no_records = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'course.course',
                    'search_count',
                    array(
                        array(
                            array('no_dept', '=', True),
                            array('college_id', '=', (int)$collegeId),
                        ),
                    ),

                );

                $coursesAlt = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'course.course',
                    'search_read',
                    array(
                        array(
                            array('no_dept', '=', False),
                            array('college_id', '=', (int)$collegeId),
                        ),
                    ),
                    array('fields' => array('name', 'code', 'college_id', 'graduate', 'duration', 'department_id',  'no_dept'))
                );

                $coursesAlt_no_records = $models->execute_kw(
                    $dbname,
                    $uid,
                    $userPassword,
                    'course.course',
                    'search_count',
                    array(
                        array(
                            array('no_dept', '=', False),
                            array('college_id', '=', (int)$collegeId),
                        ),
                    ),

                );

                $response = array(
                    'no_of_records' => array(
                        'no_of_artsScience' => $coursesArts_no_records,
                        'no_of_others' => $coursesAlt_no_records,
                        'yearCount'=> $yearsCount,
                        'semCount'=> $semestersCount,
                    ),
                    'data' => array(
                        'coursesArts' => $coursesArts,
                        'coursesAlt' => $coursesAlt,
                        'years'=> $years,
                        'semesters'=> $semesters,
                        'session'=> $session,
                        'college'=> $college
                    ),
                    'message' => 'Success'

                );

                echo json_encode($response);
            } else {
                // if the login credentials were incorrect,
                // echo
                echo json_encode($failInvalidCredentials);
            }
        } else {
            echo json_encode($failNoData);
        }
    } else {
        echo json_encode(array('message' => 'Invalid request'));
    }
} else {
    // if request is not POST

    echo json_encode($failNotPost);
}
