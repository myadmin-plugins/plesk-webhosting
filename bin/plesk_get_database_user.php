<?php
/**
* Plesk Get Database User
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @subpackage Scripts
* @copyright 2017
*/
include_once(__DIR__.'/../../../include/functions.inc.php');

$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false));

try {
	$result = $plesk->getDatabaseUser();
} catch (ApiRequestException $e) {
	echo "Exception Error: ".$e;
	print_r($e);
	die();
}
echo $plesk->var_export($result);
