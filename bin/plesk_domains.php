<?php
/**
* Plesk Domains
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @copyright 2017
*/
include_once __DIR__.'/../../../../include/functions.inc.php';

use Detain\MyAdminPlesk\ApiRequestException;


function_requirements('get_webhosting_plesk_instance');
$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : FALSE));

try {
	$response = $plesk->sendRequest($plesk->getDomains()->saveXML());
} catch (ApiRequestException $e) {
	echo 'Exception Error: '.$e;
	die();
}
print_r($response);
try {
	$responseXml = $plesk->parseResponse($response);
} catch (Exception $e) {
	myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
try {
	$resultNodes = (array) $plesk->checkResponse($responseXml);
} catch (Exception $e) {
	myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}

// Explore the result
print_r($resultNodes);
