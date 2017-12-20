<?php
/**
* Plesk Create Site
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @copyright 2017
*/
include_once __DIR__.'/../../../../include/functions.inc.php';

use Detain\MyAdminPlesk\ApiRequestException;
use Detain\MyAdminPlesk\Plesk;

function_requirements('get_webhosting_plesk_instance');
$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : FALSE));

$request = [
	'domain' => 'detain-qa-'.Plesk::random_string().'.com',
	'subscription_id' => 1
];

try {
	$result = $plesk->createSite($request);
} catch (ApiRequestException $e) {
	echo 'Exception Error: '.$e->getMessage();
	print_r($e);
	die();
}
echo $plesk->varExport($result);
