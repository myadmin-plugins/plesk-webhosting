<?php
/**
* Plesk Test Simple
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @subpackage Scripts
* @copyright 2017
*/

/**
 * This file attempts the following operations on a real Plesk server (intended to be run by an admin account):
 *
 * 	Get server information		Find shared ip address			Find unlimited service plan
 * 	Creates a new client		Get new client from server		Update client information
 * 	Deletes client				Create new site					Find created site
 * 	Update site information * 	Delete site						Delete subscription
 * 	Create subscription			List subscriptions
 *
 */

include_once __DIR__.'/../../../../include/functions.inc.php';

$data = [];
$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : FALSE));
$debugCalls = FALSE;
//$plesk->debug = true;
$result = $plesk->listIpAddresses();
if (!isset($result['ips'][0]['ip_address']) || $result['status'] == 'error')
	throw new Exception('Failed getting server information.'.(isset($result['errtext']) ? ' Error message was: '.$result['errtext'].'.' : ''));
if ($debugCalls == TRUE)
	echo 'plesk->list_ip_adddresses() = ' .var_export($result, TRUE)."\n";
foreach ($result['ips'] as $idx => $ip_data)
	if (trim($ip_data['type']) == 'shared' && (!isset($data['shared_ip_address']) || $ip_data['is_default']))
		$data['shared_ip_address'] = $ip_data['ip_address'];
if (!isset($data['shared_ip_address']))
	throw new Exception("Couldn't find any shared IP addresses");
$result = $plesk->listServicePlans();
if ($debugCalls == TRUE)
	echo 'plesk->listServicePlans() = ' .var_export($result, TRUE)."\n";
foreach ($result as $idx => $plan)
	//if (strtolower($plan['name']) == 'unlimited') {
	if ($plan['name'] == 'Default Simple') {
		$data['unlimited_plan_id'] = $plan['id'];
		break;
	}
if (!isset($data['unlimited_plan_id']))
	throw new Exception("Couldn't find unlimited service plan");
$data['username'] = 'detain'.strtolower(Plesk::random_string(5));
$data['name'] = Plesk::random_string(8).' '.Plesk::random_string(8);
$data['password'] = Plesk::random_string(10). '1!';
$data['email'] = Plesk::random_string().'@example.com';
$data['domain'] = 'detain-qa-'.Plesk::random_string().'.com';
$data['subscription_domain'] = 'detain-qa-'.Plesk::random_string().'.com';
$request = [
	'name' => $data['name'],
	'username' => $data['username'],
	'password' => $data['password']
];
$result = $plesk->createClient($request);
if ($debugCalls == TRUE)
	echo 'plesk->createClient(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
$data['client_id'] = $result['id'];
echo "Got Client ID {$data['client_id']}\n";
try {
	$request = ['username' => $data['username']];
	$result = $plesk->getClient($request);
	if ($debugCalls == TRUE)
		echo 'plesk->getClient(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
	$request = [
		'username' => $data['username'],
		'phone' => Plesk::random_string(),
		'email' => $data['email']
	];
	$result = $plesk->updateClient($request);
	if ($debugCalls == TRUE)
		echo 'plesk->updateClient(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
	$request = [
		'domain' => $data['subscription_domain'],
		'owner_id' => $data['client_id'],
		'htype' => 'vrt_hst',
		'ftp_login' => $data['username'],
		'ftp_password' => $data['password'],
		'ip' => $data['shared_ip_address'],
		'status' => 0,
		'plan_id' => $data['unlimited_plan_id']
	];
	$result = $plesk->createSubscription($request);
	if ($debugCalls == TRUE)
		echo 'plesk->createSubscription(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
	$data['subscription_id'] = $result['id'];
	echo "Got Subscription ID {$data['subscription_id']}\n";
	$result = $plesk->listSubscriptions();
	if ($debugCalls == TRUE)
		echo 'plesk->listSubscriptions() = ' .var_export($result, TRUE)."\n";
	$subscription_found = FALSE;
	foreach ($result as $subscription)
		if ($subscription['id'] == $data['subscription_id'])
			$subscription_found = TRUE;
	if (!$subscription_found)
		throw new Exception("Couldn't find created subscription");
	$request = [
		'domain' => $data['domain'],
		'subscription_id' => $data['subscription_id']
	];
	$result = $plesk->createSite($request);
	if ($debugCalls == TRUE)
		echo 'plesk->createSite(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
	$data['site_id'] = $result['id'];
	echo "Got Site ID {$data['site_id']}\n";
	$request = ['subscription_id' => $data['subscription_id']];
	$result = $plesk->listSites($request);
	if ($debugCalls == TRUE)
		echo 'plesk->listSites(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
	$site_found = FALSE;
	if (isset($result['id']))
		$result = [$result];
	foreach ($result as $site)
		if (isset($site['id']) && $site['id'] == $data['site_id'])
			$site_found = TRUE;
	if (!$site_found)
		throw new Exception("Couldn't find created site");
	$data['new_domain'] = 'detain-qa-'.Plesk::random_string().'.com';
	echo "Changing Domain from {$data['domain']} to {$data['new_domain']}\n";
	$request = ['id' => $data['site_id'], 'domain' => $data['new_domain']];
	$result = $plesk->updateSite($request);
	if ($debugCalls == TRUE)
		echo 'plesk->updateSite(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
	//echo "Got " . print_r($result, TRUE) . "\n";
} catch (Exception $e) {
	myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
//echo "Final Data: ".print_r($data, TRUE).PHP_EOL;
if (isset($data['site_id'])) {
	echo "Deleting Site {$data['site_id']}\n";
	$request = ['id' => $data['site_id']];
	$result = $plesk->deleteSite($request);
	if ($debugCalls == TRUE)
		echo 'plesk->deleteSite(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
} else
	echo "Skipping deleteSite as we lack site id\n";
if (isset($data['subscription_id'])) {
	echo "Deleting Subscription {$data['subscription_id']}\n";
	$request = ['id' => $data['subscription_id']];
	$result = $plesk->deleteSubscription($request);
	if ($debugCalls == TRUE)
		echo 'plesk->deleteSubscription(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
} else
	echo "Skipping deleteClient as we lack subscription id\n";
if (isset($data['client_id'])) {
	echo "Deleting Client {$data['client_id']}\n";
	$request = ['id' => $data['client_id']];
	$result = $plesk->deleteClient($request);
	if ($debugCalls == TRUE)
		echo 'plesk->deleteClient(' .var_export($request, TRUE). ') = ' .var_export($result, TRUE)."\n";
} else
	echo "Skipping deleteClient as we lack customer id\n";
