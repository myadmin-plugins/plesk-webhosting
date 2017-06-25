<?php
/**
* Plesk Get Subscription
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @subpackage Scripts
* @copyright 2017
*/
include_once(__DIR__.'/../../../include/functions.inc.php');

$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false));

try {
	$field = $_SERVER['argv'][2];
	$value = $_SERVER['argv'][3];
	$result = $plesk->get_subscription(array($field => $value));
} catch (ApiRequestException $e) {
	echo "Exception Error: " . $e;
	print_r($e);
	die();
}
echo $plesk->var_export($result);