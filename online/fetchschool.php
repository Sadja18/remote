<?php

$privateURL = "http://10.184.4.238:8069";
$publicURL = "http://10.184.49.222:8069";

$url = $publicURL;

// $url = $privateURL;

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
        $dbname = 'doednhdd';
    }

    $common = ripcord::client($url . '/xmlrpc/2/common');
    $uid = $common->authenticate($dbname, $user, $password, array());
    $models = ripcord::client("$url/xmlrpc/2/object");

    if (isset($_GET['Persistent']) && ($_GET['Persistent']=='1' || $_GET['Persistent']==1)) {

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

        
        if (
            !isset($teachers['faultString'])
        ) {
            $response = array(
                "school" => array(
                    "school_name" => $school_name,
                    "school_id" => $school_id,
                ),
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => $languages
            );
        }
        echo json_encode($response);
    }else if(isset($_GET['Persistent']) && ($_GET['Persistent']=='2' || $_GET['Persistent']==2)){
        $school = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.school',
            'search_read',
            array(
                array(
                    array('email','=', $user)
                )
            ),
            array(
                'fields'=> array(
                    'com_name'
                )
            )
        );

        if(isset($school) && !isset($school['faultString'])){
            $response = array(
                "message"=> "success",
                "school" => array(
                    "school_name" => $school[0]['com_name'],
                    "school_id" => $school[0]['id'],
                ),
            );
            echo json_encode($response);
        }else{
            echo json_encode(
                array(
                    'val' => 'error',
                    'error' => $languages
                )
            );
        }

    }else{
        echo json_encode(
            array(
            "message"=> 'failed',
            'error'=> 'invalid request parameters'   
            )
        );
    }
}else{
    echo json_encode(array(
        'h'=>12321423,
        'g'=> "jasgdfj"
    ));
}
