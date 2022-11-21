<?php

$privateURL = "http://10.184.49.222:8069";
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
        $dbname = 'doednhdd';
    }

    if(isset($entityBody['Persistent'])){
        $presentCount = $entityBody['present'];
        $absentCount = $entityBody['absent'];
        $attendanceSheet = $entityBody['attendanceSheet'];
        $date = $entityBody['date'];
        $submissionDate = $entityBody['submissionDate'];
        $userId = $entityBody['headMasterUserId'];

        if(isset($date)){
            $weekDayCapital = date('l', strtotime($date));
            $weekDay = strtolower($weekDayCapital);

            $common = ripcord::client($url . '/xmlrpc/2/common');

            $uid = $common->authenticate($dbname, $user, $password, array());

            $models = ripcord::client("$url/xmlrpc/2/object");

            $totalTeachers = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'school.teacher',
                'search_count',
                array(
                    array(
                        array('active', '=', TRUE),
                    ),
                )
            );

            sleep(0.5);
            $school = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'school.school',
                'search_read',
                array(
                    array(
                        array('email','=', $user)
                    ),
                ),
                array('fields'=> array('com_name'))
            );
            sleep(0.5);
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
                array('fields'=> array('name'))
            );

            $schoolId = $school[0]['id'];
            $yearId = $year[0]['id'];
            sleep(0.5); 
            
            // foreach ($attendanceSheet[0]->proxy as $key=>$value) {
            //     echo json_encode(array($key => $attendanceSheet[0]->proxy->$key));
            // }

            $pushEntry = $models-> execute_kw(
                $dbname,
                $uid,
                $password,
                'teacher.daily.attendance',
                'create',
                array(
                    array(
                        'date'=> $date,
                            'school_id'=> $schoolId,
                            'year'=> $yearId,
                            'create_uid'=> $userId,
                            'state'=> 'draft'
                    )
                ),
            );           

            if(isset($pushEntry['faultString'])){
                echo json_encode(
                    array(
                        'message'=> 'failed',
                        // 'f'=> array(
                        //     'date'=> $date,
                        //     // 'school_id'=> $schoolId,
                        //     // 'year'=> $yearId,
                        //     'create_uid'=> $userId,
                        //     'state'=> 'draft'
                        // ),
                        'create failed'=> $pushEntry,
                    )
                );
            }else{
                $iser = 0;
                $message = null;
                foreach($attendanceSheet as $attendance){
                    $teacherId =(int) $attendance->teacherId;
                    $reasonId =(int) $attendance->reasonId;
                    $isAbsent =(bool) $attendance->absent;

                    $lineId = $models->execute_kw(
                        $dbname,
                            $uid,
                            $password,
                            'teacher.daily.attendance.line',
                            'search',
                            array(
                                array(
                                    array('teachers_ids', '=', $pushEntry),
                                    array('teacher_id', '=', $teacherId),
                                ),
                            )
                    );

                    // echo json_encode(array('l'=>$lineId));

                    if(isset($lineId['faultString'])){
                        $iser = 1;
                        $message = $lineId;
                        // echo json_encode(
                        //     array(
                        //         'message'=>'success'
                        //     )
                        // );
                    }else{
                        $models->execute_kw(
                            $dbname,
                            $uid,
                            $password,
                            'teacher.daily.attendance.line',
                            'write',
                            array(
                                array($lineId[0]),
                                array(
                                    'is_absent' => true,
                                    'is_present' => false,
                                    'reason_id'=> $reasonId,
                                ),
                            )
                        );
                        
                    }
                }
                if($iser!=0){
                    echo json_encode(
                        array(
                            "message"=> "failed",
                            'f'=> $message
                        )
                    );
                }else{
                    $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'teacher.daily.attendance',
                        'write',
                        array(array($pushEntry), array(
                            'total_teacher' => $totalTeachers,
                            'total_presence' => $presentCount,
                            'total_absent' => $absentCount,
                            'state' => 'draft',

                            ),
                        )
                    );
                    sleep(0.25);
                    
                    // loop again in the attendance sheet
                    foreach($attendanceSheet as $attendance){
                        // find teacher id
                        $teacherId =(int) $attendance->teacherId;
                        $proxies = $attendance->proxy;
                    
                        foreach($proxies as $key=>$value){
                            $periodName = $key;
                            $assignedTeacherId = $proxies->$periodName;

                            // echo json_encode(array(
                            //     "f"=> $periodName,
                            //     "T"=> $assignedTeacherId,
                            // ));

                            $proxyLine = $models->execute_kw(
                                $dbname,
                                $uid,
                                $password,
                                'teacher.daily.attendance.proxy',
                                'search_read',
                                array(
                                    array(
                                        array('teachers_ids','=', $pushEntry),
                                        array('teacher_id','=',$teacherId),
                                        array('period', '=', $periodName),
                                    )
                                ),
                                array(
                                    'fields'=> array('teachers_ids', 'teacher_id', 'period', 'assigned_teacher_id')
                                )
                            );

                            $proxyLineId = $proxyLine[0]['id'];

                            $p = $models->execute_kw(
                                $dbname,
                                $uid,
                                $password,
                                'teacher.daily.attendance.proxy',
                                'write',
                                array(
                                    array($proxyLineId),
                                    array(
                                        'assigned_teacher_id'=> (int) $assignedTeacherId
                                    )
                                )
                            );

                            // if($p!=true){
                            //     $iser == -24;
                            // }
                            sleep(0.5);

                            // echo json_encode(array(
                            //     '1'=> $pushEntry,
                            //     'g'=> $proxyLineId,
                            //     'h'=> $p,
                            // ));
                        }
                    }
                    
                    echo json_encode(
                        array(
                            'message'=> 'success',
                        )
                    );
                }
            }
        }else{
            echo json_encode(
                array(
                    "message"=> "failed",
                    "no date"=> true
                )
            );
        }
    }else{
        echo json_encode(
            array(
                "message"=> "failed",
                "no valid"=> true
            )
        );
    }
}else{
    echo json_encode(
        array(
            "message"=> "failed",
            "no parma"=> true
        )
    );
}
