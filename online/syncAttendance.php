<?php

$privateURL = "http://10.184.4.238:8069";
$publicURL = "http://14.139.180.56:8069";

$url = $privateURL;
// $url = $publicURL;

$password = null;
$user = null;
$dbname = null;

require_once './ripcord/ripcord.php';
header('Access-Control-Allow-Origin: *', false);
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json');

header("Access-Control-Allow-Headers: X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // echo "POST request recieved to give attendance data";

    $entityBodyJSON = file_get_contents("php://input");

    $entityBody = get_object_vars(json_decode($entityBodyJSON));

    // echo json_encode($entityBody);

    $user = $entityBody['userName'];
    $password = $entityBody['userPassword'];

    if (isset($entityBody['dbname'])) {
        $dbname = $entityBody['dbname'];
    } else {
        $dbname = 'school';
    }

    if (isset($entityBody['sync'])) {
        $response = array();

        $absentees = $entityBody['absentees'];
        $className = $entityBody['className'];
        $date = $entityBody['date'];
        $schoolId = $entityBody['schoolId'];
        $schoolName = $entityBody['schoolName'];
        $classId = $entityBody['classId'];
        $teacherId = $entityBody['teacherId'];
        $submissionDate = $entityBody['submissionDate'];

        if (
            isset($absentees)
            && isset($className)
            && isset($classId)
            && isset($date)
            && isset($schoolName)
            && isset($schoolId)
            && isset($classId)
            && isset($teacherId)
            && isset($submissionDate)
        ) {
            $common = ripcord::client($url . '/xmlrpc/2/common');

            $uid = $common->authenticate($dbname, $user, $password, array());

            $models = ripcord::client("$url/xmlrpc/2/object");
            // get total number of students
            $total_students = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'student.student',
                'search_count',
                array(
                    array(
                        array('standard_id.name', '=', $className),
                        array('school_id.name', '=', $schoolName),
                        array('state', '=', 'done'),
                    ),
                )
            );
            if (gettype($absentees) == 'string') {
                $absentees = json_decode($absentees);
            }
            $total_absent = count($absentees);
            $total_present = (int) $total_students - (int) $total_absent;
            if ((int) $total_present + (int) $total_absent != (int) $total_students) {
                echo json_encode(array('problem' => 'mismach counts'));
            } else {
                $state = 'draft';

                $record_create_id = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'daily.attendance',
                    'create',
                    array(array(
                        'date' => $date,
                        'standard_id' => (int) $classId,
                        'user_id' => (int) $teacherId,
                        'state' => $state,
                        'school_id' => (int) $schoolId,
                        'sub_date' => $submissionDate,
                    ))
                );

                if (isset($record_create_id['faultString'])) {
                    echo json_encode(array(
                        "faultString" => $record_create_id['faultString'],
                    ));
                } else {

                    foreach ($absentees as $entry) {
                        $entryA = (int) $entry;

                        $lineid = $models->execute_kw(
                            $dbname,
                            $uid,
                            $password,
                            'daily.attendance.line',
                            'search',
                            array(
                                array(
                                    array('standard_id', '=', $record_create_id),
                                    array('stud_id', '=', $entryA),
                                ),
                            )
                        );
                        if (!isset($lineid['faultString'])) {
                            $models->execute_kw(
                                $dbname,
                                $uid,
                                $password,
                                'daily.attendance.line',
                                'write',
                                array(
                                    array($lineid[0]),
                                    array(
                                        'is_absent' => true,
                                        'is_present' => false,
                                        'att' => 'absent',

                                    ),
                                )
                            );
                            // echo json_encode(array(
                            //     'f'=> gettype($entry),
                            //     'f2'=> $entry,
                            //     'f3'=> $lineid
                            // ));

                            $models->execute_kw(
                                $dbname,
                                $uid,
                                $password,
                                'daily.attendance',
                                'write',
                                array(array($record_create_id), array(
                                    'total_student' => $total_students,
                                    'total_presence' => $total_present,
                                    'total_absent' => $total_absent,
                                    'state' => 'validate',

                                ))
                            );
                            array_push($response, array($entry => 'write done'));
                        } else {
                            array_push($response, array($entry => $lineid['faultString']));
                        }
                    }
                    $response = array(
                        'record_create_id' => $record_create_id,
                    );
                    echo json_encode($response);
                }
            }
        } else {
            echo json_encode(
                array(
                    't' => $entityBody,
                )
            );
        }
    }
}
