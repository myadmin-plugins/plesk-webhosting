<?php
/**
* Plesk Domains
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @subpackage Scripts
* @copyright 2017
*/
include_once(__DIR__.'/../../../include/functions.inc.php');

$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false));

try {
	$response = $plesk->sendRequest($plesk->getDomains()->saveXML());
	print_r($response);
	$responseXml = $plesk->parseResponse($response);
	$resultNodes = (array) $plesk->checkResponse($responseXml);
} catch (ApiRequestException $e) {
	echo "Exception Error: ".$e;
	die();
}


// Explore the result
print_r($resultNodes);
