<?php
require_once './envRead.php';
require_once './ripcord/ripcord.php';


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

$login_status = null;
$responseBody = array();

// $connect = fopen($url, "r");
if($_SERVER['REQUEST_METHOD']=='POST' || $_SERVER['REQUEST_METHOD']=='post'){
    $entityBodyJSON = file_get_contents('php://input');

    $entityBody = json_decode($entityBodyJSON, true);

    $userName = $entityBody['userName'];
    $userPassword = $entityBody['userPassword'];

    $dbname = null;
    if(isset($entityBody['dbname'])){
        $dbname = $entityBody['dbname'];
    }else{
        $dbname = 'college';
    }

    $common = ripcord::client($url . '/xmlrpc/2/common');

    // check if the credentials are valid
    $uid = $common->authenticate($dbname, $userName, $userPassword, array());

    // dummy code, remove later

    if($uid != false){
        // if the credentials are valid,
        // get the user type
        // ['student', 'parent', 'faculty', 'staff']

        // response body expected
        // userName,
        // userPassword
        // dbname
        // 

        $responseBody = array(
            'message'=> 'success',
            'data'=>
                array(
                    'received'=> $entityBody,
                    'uID'=>$uid != false
                )
        );

        echo json_encode($responseBody);

    }else{

        echo json_encode(
            array(
                'message'=> 'wrong request',
                'en'=> $entityBody,
                'f'=> $uid==false
            )
        );
    }
}
else{

    // if any other request type
    echo json_encode(['message'=> 'wrong request method',
        'data'=> $_SERVER]
    );

}
