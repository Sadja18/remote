<?php

$privateURL = "http://10.184.4.238:8069";
$publicURL = "http://14.139.180.56:8069";

// $url = $publicURL;

$url = $privateURL;

$user = null;
$password = null;
$dbname = null;
$response = null;


require_once './ripcord/ripcord.php';

// check if server request method is get
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // $response = 'Fetch Request received';

    header('Access-Control-Allow-Origin: *', false);
    header('Content-Type: application/json');


    if (isset($_GET['userName'])) {
        $user = $_GET['userName'];
    }
    if (isset($_GET['userPassword'])) {
        $password = $_GET['userPassword'];
    }
    if (isset($_GET['dbname'])) {
        $dbname = $_GET['dbname'];
    } else {
        $dbname = 'school';
    }

    $common = ripcord::client($url . '/xmlrpc/2/common');
    $uid = $common->authenticate($dbname, $user, $password, array());
    $models = ripcord::client("$url/xmlrpc/2/object");

    if (isset($_GET['Persistent'])) {

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

        $teacher_id = $teachers[0]['id'];
        $teacher_name = $teachers[0]['name'];
        $school_name = $teachers[0]['school_id'][1];
        $school_id = $teachers[0]['school_id'][0];

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

        $classes = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.standard',
            'search_read',
            array(
                array(
                    '|', '|',
                    array('user_id.name', '=', $teacher_name),
                    array('sec_user_id.name', '=', $teacher_name),
                    array('ter_user_id.name', '=', $teacher_name),
                ),
            ),
            array('fields' => array('name', 'standard_id', 'medium_id', 'division_id'))
        );

        $student_data = array();

        foreach ($classes as $class_record) {
            $selected_class = $class_record['name'];

            $selected_class_students = array();

            $students = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'student.student',
                'search',
                array(
                    array(
                        array('standard_id.name', '=', $selected_class),
                        array('school_id.name', '=', $school_name),
                        array('state', '=', 'done'),
                    ),
                )
            );
            foreach ($students as $student) {
                $entry = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'student.student',
                    'read',
                    array($student),
                    array('fields' => array('name', 'roll_no'))
                );
                array_push($selected_class_students, $entry[0]);
            }
            $student_data[$selected_class] = $selected_class_students;
        }
        $grading = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'pace.grade',
            'search_read',
            array(
                array(
                    array('from_mark', '!=', False),
                    array('to_mark', '!=',  False),
                    array('result', '!=', False)
                ),
            ),
            array('fields' => array('from_mark', 'to_mark', 'result')),
        );
        $assessment_records = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'pace.examsched',
            'search_read',
            array(
                array(
                    array('year_id.name', '=', $academic_year),
                    array('state', 'in', ['scheduled', 'conducted']),
                ),
            ),
            array('fields' => array('name', 'subject', 'qp_code', 'date', 'standard_id', 'medium'))
        );

        $question_papers = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'pace.qpaper',
            'search_read',
            array(
                array(
                    array('name', '!=', False),
                    array('qp_code', '!=', False),
                    array('totmarks', '!=', False),
                    // array('state', 'in', ['draft', 'validated'])
                )
            ),
            array('fields' => array('name', 'qp_code', 'medium', 'subject', 'standard_id', 'totques', 'totmarks'))
        );

        $languages = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.standard',
            'search_read',
            array(
                array(
                    array('name','!=',False)
                ),
            ),
            array('fields'=> array('standard_id','medium_id'))
        );

        $reading_levels = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.reading',
            'search_read',
            array(
                array(
                    array('name', '!=', False),
                )
            ),
            array('fields' => array('standard', 'name', 'subject'))
        );

        $numeric_levels = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.numeric',
            'search_read',
            array(
                array(
                    array('standard', '!=', False)
                )
            ),
            array('fields' => array('standard', 'name'))
        );

        if (
            !isset($teachers['faultString'])
            && !isset($year['faultString'])
            && !isset($classes['faultString'])
            && !isset($student_data['faultString'])
            && !isset($grading['faultString'])
            && !isset($assessment_records['faultString'])
            && !isset($question_papers['faultString'])
            && !isset($reading_levels['faultString'])
            && !isset($numeric_levels['faultString'])
            && !isset($languages['faultString'])
        ) {
            $response = array(
                "teacher" => array(
                    "teacher_id" => $teacher_id,
                    "teacher_name" => $teacher_name,
                ),
                "school" => array(
                    "school_name" => $school_name,
                    "school_id" => $school_id,
                ),
                "academic_year" => $academic_year,
                "classes" => $classes,
                "students" => $student_data,
                'grading' => $grading,
                'assessments' => $assessment_records,
                'qpapers' => $question_papers,
                'reading_levels'=> $reading_levels,
                'numeric_levels'=> $numeric_levels,
                'langauges'=> $languages
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => $languages
            );
        }
        echo json_encode($response);
    }
}
