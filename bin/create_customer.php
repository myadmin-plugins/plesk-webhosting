<?php
/**
* Plesk Create Customer
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
    $data = $GLOBALS['tf']->accounts->read(2773);
    $password = _randomstring(10);
    $username = 'joeapitest';
    echo "Calling createCustomer($username, $password)\n";
    $xml = $plesk->createCustomer($username, $password, $data)->saveXML();
    echo "Sending XML:\n{$xml}\n";
    $response = $plesk->sendRequest($xml);
    print_r($response);
    $responseXml = $plesk->parseResponse($response);
    //$plesk->checkResponse($responseXml);
} catch (ApiRequestException $e) {
    echo $e;
    die();
}

// Explore the result
foreach ($responseXml->xpath('/packet/customer/add/result') as $resultNode) {
    echo 'Customer Added, Id: '.(string) $resultNode->id." Status:{$resultNode->status} GUID:{$resultNode->guid}\n";
}
