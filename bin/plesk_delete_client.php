<?php
/**
* Plesk Delete Client
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
	if (trim($field) == '' || trim($value) == '')
		die('this would delete all clients');
	$result = $plesk->deleteClient(array($field => $value));
} catch (ApiRequestException $e) {
	echo "Exception Error: ".$e;
	print_r($e);
	die();
}
print_r($result);
