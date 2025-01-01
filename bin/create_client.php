<?php
/**
* Plesk Create Client
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @copyright 2025
*/
include_once __DIR__.'/../../../../include/functions.inc.php';

use Detain\MyAdminPlesk\ApiRequestException;
use Detain\MyAdminPlesk\Plesk;

function_requirements('get_webhosting_plesk_instance');
$plesk = get_webhosting_plesk_instance(($_SERVER['argv'][1] ?? false));

$request = [
    'name' => Plesk::random_string(8).' '.Plesk::random_string(8),
    'username' => 'detain'.strtolower(Plesk::random_string(5)),
    'password' => Plesk::random_string(10).'1!'
];

try {
    $result = $plesk->createClient($request);
} catch (ApiRequestException $e) {
    echo 'Exception Error: '.$e->getMessage();
    print_r($e);
    die();
}
echo $plesk->varExport($result);
