<?php
/**
* Plesk Customers
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @copyright 2019
*/
include_once __DIR__.'/../../../../include/functions.inc.php';

use Detain\MyAdminPlesk\ApiRequestException;

function_requirements('get_webhosting_plesk_instance');
$plesk = get_webhosting_plesk_instance(($_SERVER['argv'][1] ?? false));

try {
    $response = $plesk->getCustomers();
} catch (ApiRequestException $e) {
    echo 'Exception Error: '.$e->getMessage();
    die();
}
print_r($response);
