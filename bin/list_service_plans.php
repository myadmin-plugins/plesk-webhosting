<?php
/**
* Plesk List Service Plans
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @copyright 2018
*/
include_once __DIR__.'/../../../../include/functions.inc.php';

use Detain\MyAdminPlesk\ApiRequestException;

function_requirements('get_webhosting_plesk_instance');
$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false));

try {
	$result = $plesk->listServicePlans();
} catch (ApiRequestException $e) {
	echo 'Exception Error: '.$e->getMessage();
	print_r($e);
	die();
}
echo $plesk->varExport($result);
