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
if ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'get') {
    $user = 'vigneswaris@cdac.in';
    $password = 'admin@1234';
    $dbname = 'college';

    $userName='dramitgcd@gmail.com';
    $userPass="faculty@1234";

    $common = ripcord::client($url . '/xmlrpc/2/common');

    // $uid = $common->authenticate($dbname, $user, $password, array());
    $uid = $common->authenticate($dbname, $userName, $userPass, array());


    $models = ripcord::client("$url/xmlrpc/2/object");
    // for res.groups
    // $res = $models->execute_kw(
    //     $dbname,
    //     $uid,
    //     $password,
    //     'res.groups',
    //     'search_read',
    //     array(
    //         array(
    //             array('id', '=', 17)
    //         )
    //     ),
    //     array(
    //         'fields' => array(
    //             'name', 'users', 'rule_groups',
    //             'menu_access', 'view_access', 'category_id',
    //             'full_name', ''
    //         )
    //     )
    // );

    $res= $models->execute_kw(
        $dbname,
        $uid,
        $userPass,
        'college.teacher',
        'search_read',
        array(
            array(
                array('active', '!=', False),
            )
        ),
        array(
        )
    );

    echo json_encode(
        array(
            're' => $res,
        )
    );
}
