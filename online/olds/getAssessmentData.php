<?php
$privateURL = "http://10.184.4.238:8069";
$publicURL = "http://14.139.180.56:8069";

$url = $publicURL;
$url = $privateURL;



$password = null;
$user = null;
$dbname =  null;

require_once './ripcord/ripcord.php';


if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // echo "POST request recieved to give attendance data";
header('Access-Control-Allow-Origin: *', false);

    $entityBodyJSON = file_get_contents("php://input");

    $entityBody = get_object_vars(json_decode($entityBodyJSON));

    $user = $entityBody['userName'];
    // echo json_encode($entityBody['userPassword']);;
    $password = $entityBody['userPassword'];

    if (isset($entityBody['dbname'])) {
        $dbname = $entityBody['dbname'];
    } else {
        $dbname = 'school';
    }

    if (isset($entityBody['Persistent'])) {
        $common = ripcord::client($url . '/xmlrpc/2/common');

        $uid = $common->authenticate($dbname, $user, $password, array());

        $models = ripcord::client("$url/xmlrpc/2/object");

        // get teachers data
        $teachers = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.teacher',
            'search_read',
            array(
                array(
                    array('employee_id.user_id.id', '=', $uid),
                ),
            ),
            array('fields' => array('name', 'school_id'))

        );

        $teacher_name = $teachers[0]['name'];
        $school_name = $teachers[0]['school_id'][1];

        // get academic year data
        $year = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'academic.year',
            'search_read',
            array(
                array(
                    array('current', '=', True),
                ),
            ),
            array('fields' => array('name'))
        );

        $academic_year = $year[0]['name'];

        $persistentData = array(
            "teacher_name" => $teacher_name,
            "school_name" => $school_name,
            "academic_year" => $academic_year
        );
        header('Access-Control-Allow-Origin: *');

        header('Access-Control-Allow-Methods: GET, POST');

        header("Access-Control-Allow-Headers: X-Requested-With");
        echo json_encode($persistentData);
    }

    if (isset($entityBody['syncDB'])) {

        $school_id = $entityBody['school_id'];
        $teacher_id = $entityBody['teacher_id'];
        $class_name = $entityBody['class_name'];
        $academic_year = $entityBody['academic_year'];

        $subject = $entityBody['subject'];
        $subject_id = $subject[0];
        $subject_name = $subject[1];

        $assessment_id = $entityBody['assessment_id'];
        $assessment_name = $entityBody['assessment_name'];
        $assessment_records = $entityBody['assessment_record'];

        $assessment_date = $entityBody['assessment_date'];
        $scheduled_date  = $entityBody['scheduled_date'];

        $assessment_state = $entityBody['assessment_state'];

        $exam_type = $entityBody['exam_type'];
        $dynamic_field = $entityBody['dynamic_field'];

        // echo json_encode(array(
        //     '12' => 12
        // ));


        $common = ripcord::client($url . '/xmlrpc/2/common');

        $uid = $common->authenticate($dbname, $user, $password, array());

        $models = ripcord::client("$url/xmlrpc/2/object");

        // echo json_encode(array(
        //     '13' => 13
        // ));

        //     // echo json_encode(array(
        //     //     'user'=>$uid,
        //     //     'teacher'=>$teacher_id
        //     // ));
        // get academic year data
        $year = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'academic.year',
            'search_read',
            array(
                array(
                    array('current', '=', True),
                ),
            ),
            array('fields' => array('name'))
        );
        $academic_year_id = $year[0]['id'];

        $classes = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.standard',
            'search_read',
            array(
                array(
                    '|', '|',
                    array('user_id.id', '=', $teacher_id),
                    array('sec_user_id.id', '=', $teacher_id),
                    array('ter_user_id.id', '=', $teacher_id),
                ),
            ),
            array('fields' => array('name'))
        );

        $class_id = null;
        foreach ($classes as $class_record) {
            if (strcmp($class_name, $class_record['name']) == 0) {
                $class_id = $class_record['id'];
            }
        }

        // echo json_encode(array(
        //     '14' => 14
        // ));

        if ($exam_type == 'basic') {
            // echo json_encode($entityBody);
            $record_create_id = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'pace.examresult',
                'create',
                array(
                    array(
                        'school_id' => $school_id,
                        'year_id' => $academic_year_id,
                        'user_id' => $teacher_id,
                        'academic_class' => $class_id,
                        'subject' => $subject_id,
                        'name' => $assessment_id,
                        'date' => $assessment_date,
                        'exam_type' => $exam_type,
                        'reading_level' => $dynamic_field[0]
                    )
                )
            );
            if (isset($record_create_id['faultString'])) {
                $write_done = false;
                echo json_encode($record_create_id);
            } else {

                foreach ($assessment_records as $assessment_entry) {
                    $entry = get_object_vars($assessment_entry);
                    // echo json_encode(gettype());
                    // echo json_encode(var_dump($assessment_entry));
                    $student_name = $entry['student_name'];
                    $student_marks = $entry['student_marks'];
                    $student_result = $entry['student_result'];

                    $resultKey = resultReturn($student_result);

                    // echo json_encode(array(
                    //     'sid'=> $student_id,
                    //     'sm' => $student_marks,
                    //     'sr' => $student_result
                    // ));

                    $lineid = $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'reading.examresult.line',
                        'search',
                        array(
                            array(
                                array('mark_id', '=', $record_create_id),
                                array('name', '=', $student_name)
                            ),
                        )
                    );
                    $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'reading.examresult.line',
                        'write',
                        array(array($lineid[0]), array(
                            'num_marks' => $student_marks,
                            'marks' => $resultKey
                        ))
                    );

                    $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'pace.examresult',
                        'write',
                        array(array($record_create_id),array(
                            'state'=>'done'
                        ))
                    );
                    $write_done = true;
                }
                echo json_encode(array(
                    'result'=> $record_create_id,
                    'write_done'=> true,
                    'line' => $lineid,
                    'res' => $resultKey
                ));
            }
        }

        // end of basic if

        // start of pace if
        if ($exam_type == 'pace') {
            // echo json_encode($entityBody);
            $record_create_id = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'pace.examresult',
                'create',
                array(
                    array(
                        'school_id' => $school_id,
                        'year_id' => $academic_year_id,
                        'user_id' => $teacher_id,
                        'academic_class' => $class_id,
                        'subject' => $subject_id,
                        'name' => $assessment_id,
                        'date' => $assessment_date,
                        'exam_type' => $exam_type,
                        'qp_code' => $dynamic_field[0]
                    )
                )
            );
            if (isset($record_create_id['faultString'])) {
                $write_done = false;
                echo json_encode($record_create_id);
            } else {

                foreach ($assessment_records as $assessment_entry) {
                    $entry = get_object_vars($assessment_entry);
                    // echo json_encode(gettype());
                    // echo json_encode(var_dump($assessment_entry));
                    $student_name = $entry['student_name'];
                    $student_marks = $entry['student_marks'];
                    $student_result = $entry['student_result'];

                    $resultKey = resultReturn($student_result);

                    // echo json_encode(array(
                    //     'sid'=> $student_id,
                    //     'sm' => $student_marks,
                    //     'sr' => $student_result
                    // ));

                    $lineid = $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'pace.examresult.line',
                        'search',
                        array(
                            array(
                                array('mark_id', '=', $record_create_id),
                                array('name', '=', $student_name)
                            ),
                        )
                    );
                    $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'pace.examresult.line',
                        'write',
                        array(array($lineid[0]), array(
                            'marks' => $student_marks,
                            'result' => $resultKey
                        ))
                    );

                    $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'pace.examresult',
                        'write',
                        array(array($record_create_id),array(
                            'state'=>'done'
                        ))
                    );
                    $write_done = true;
                }
                echo json_encode(array(
                    'result'=> $record_create_id,
                    'write_done'=> true,
                    'line' => $lineid,
                    'res' => $resultKey
                ));
            }
        }
        // end of pace if

        // start of numeric if
        if ($exam_type == 'numeric') {
            // echo json_encode($entityBody);
            $record_create_id = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'pace.examresult',
                'create',
                array(
                    array(
                        'school_id' => $school_id,
                        'year_id' => $academic_year_id,
                        'user_id' => $teacher_id,
                        'academic_class' => $class_id,
                        'subject' => $subject_id,
                        'name' => $assessment_id,
                        'date' => $assessment_date,
                        'exam_type' => $exam_type,
                        'numeric_level' => $dynamic_field[0]
                    )
                )
            );
            if (isset($record_create_id['faultString'])) {
                $write_done = false;
                echo json_encode($record_create_id);
            } else {

                foreach ($assessment_records as $assessment_entry) {
                    $entry = get_object_vars($assessment_entry);
                    // echo json_encode(gettype());
                    // echo json_encode(var_dump($assessment_entry));
                    $student_name = $entry['student_name'];
                    $student_marks = $entry['student_marks'];
                    $student_result = $entry['student_result'];

                    $resultKey = resultReturn($student_result);

                    // echo json_encode(array(
                    //     'sid'=> $student_id,
                    //     'sm' => $student_marks,
                    //     'sr' => $student_result
                    // ));

                    $lineid = $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'numeric.examresult.line',
                        'search',
                        array(
                            array(
                                array('mark_id', '=', $record_create_id),
                                array('name', '=', $student_name)
                            ),
                        )
                    );
                    $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'numeric.examresult.line',
                        'write',
                        array(array($lineid[0]), array(
                            'num_marks' => $student_marks,
                            'marks' => $resultKey
                        ))
                    );

                    $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'pace.examresult',
                        'write',
                        array(array($record_create_id),array(
                            'state'=>'done'
                        ))
                    );
                    $write_done = true;
                }
                echo json_encode(array(
                    'result'=> $record_create_id,
                    'write_done'=> true,
                    'line' => $lineid,
                    'res' => $resultKey
                ));
            }
        }
    }
}


function resultReturn($key){
    if($key == 'Achieved'){
        return 'acc';
    }
    if($key == 'Not Achieved'){
        return 'noacc';
    }
    if($key == 'Not Evaluated'){
        return 'noeval';
    }

}