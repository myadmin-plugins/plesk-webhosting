<?php
/**
* Plesk Sites
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
	$response = $plesk->sendRequest($plesk->getSites()->saveXML());
	print_r($response);
	$responseXml = $plesk->parseResponse($response);
	$resultNodes = (array) $plesk->checkResponse($responseXml);
} catch (ApiRequestException $e) {
	echo $e;
	die();
}

// Explore the result
print_r($resultNodes);
