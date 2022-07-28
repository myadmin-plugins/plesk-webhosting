<?php
/**
* Plesk Create Subscription
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @copyright 2019
*/
include_once __DIR__.'/../../../../include/functions.inc.php';

use Detain\MyAdminPlesk\ApiRequestException;

function_requirements('get_webhosting_plesk_instance');
$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false));

$request = [
    'domain' => 'detain-qa-'.Plesk::random_string().'.com',
    'owner_id' => 1,
    'htype' => 'vrt_hst',
    'ftp_login' => 'detain'.strtolower(Plesk::random_string(5)),
    'ftp_password' => Plesk::random_string(10).'1!',
    'ip' => '127.0.0.1',
    'status' => 0,
    'plan_id' => 1
];

try {
    $result = $plesk->createSubscription($request);
} catch (ApiRequestException $e) {
    echo 'Exception Error: '.$e->getMessage();
    print_r($e);
    die();
}
echo $plesk->varExport($result);
