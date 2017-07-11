<?php
/**
* Plesk Create Database
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @subpackage Scripts
* @copyright 2017
*/
include_once(__DIR__.'/../../../../include/functions.inc.php');

function_requirements('get_webhosting_plesk_instance');
$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : FALSE));

try {
	$result = $plesk->createDatabase();
} catch (ApiRequestException $e) {
	echo "Exception Error: ".$e;
	print_r($e);
	die();
}
echo $plesk->varExport($result);
