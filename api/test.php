<?php
require_once './envRead.php';
require_once './ripcord/ripcord.php';


use sadja\DotEnv;

header('Access-Control-Allow-Origin: ' . $_SERVER['REMOTE_ADDR'], false);

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

(new DotEnv(__DIR__ . '/.env'))->load();

$url = getenv('PUBLIC');
// $url = getenv('PRIVATE');
// $url = getenv('PUBLICALT');
// $url = getenv('PRIVATEALT');
if ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'get') {
    // $user = 'vigneswaris@cdac.in';
    // $password = 'admin@1234';
    $dbname = 'college';

    $userName='dramitgcd@gmail.com';
    $userPass="faculty@1234";

    $common = ripcord::client($url . '/xmlrpc/2/common');

    // $uid = $common->authenticate($dbname, $user, $password, array());
    $uid = $common->authenticate($dbname, $userName, $userPass, array());


    $models = ripcord::client("$url/xmlrpc/2/object");

    $res= $models->execute_kw(
        $dbname,
        $uid,
        $userPass,
        'college.college',
        'search_read',
        array(
            array(
                array('head_name', '!=', False),
            )
        ),
        array(
            'fields'=> array('head_name', 'college_id'),
        )
    );

    echo json_encode(
        array(
            're' => $res,
        )
    );
}
