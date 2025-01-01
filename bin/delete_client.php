<?php
/**
* Plesk Delete Client
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @copyright 2025
*/
include_once __DIR__.'/../../../../include/functions.inc.php';

use Detain\MyAdminPlesk\ApiRequestException;

function_requirements('get_webhosting_plesk_instance');
$plesk = get_webhosting_plesk_instance(($_SERVER['argv'][1] ?? false));

try {
    $field = $_SERVER['argv'][2];
    $value = $_SERVER['argv'][3];
    if (trim($field) == '' || trim($value) == '') {
        die('this would delete all clients');
    }
    $result = $plesk->deleteClient([$field => $value]);
} catch (ApiRequestException $e) {
    echo 'Exception Error: '.$e->getMessage();
    print_r($e);
    die();
}
print_r($result);
