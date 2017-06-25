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

include_once(__DIR__.'/../../../include/functions.inc.php');

$data = [];
$plesk = get_webhosting_plesk_instance((isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false));
$debugCalls = false;
//$plesk->debug = true;
$result = $plesk->list_ip_addresses();
if (!isset($result['ips'][0]['ip_address']) || $result['status'] == 'error')
	throw new Exception('Failed getting server information.'.(isset($result['errtext']) ? ' Error message was: '.$result['errtext'].'.' : ''));
if ($debugCalls == true)
	echo "plesk->list_ip_adddresses() = ".var_export($result, true). "\n";
foreach ($result['ips'] as $idx => $ip_data)
	if (trim($ip_data['type']) == 'shared' && (!isset($data['shared_ip_address']) || $ip_data['is_default']))
		$data['shared_ip_address'] = $ip_data['ip_address'];
if (!isset($data['shared_ip_address']))
	throw new Exception("Couldn't find any shared IP addresses");
$result = $plesk->list_service_plans();
if ($debugCalls == true)
	echo "plesk->list_service_plans() = ".var_export($result, true). "\n";
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
$data['password'] = Plesk::random_string(10) . "1!";
$data['email'] = Plesk::random_string().'@example.com';
$data['domain'] = 'detain-qa-'.Plesk::random_string().'.com';
$data['subscription_domain'] = 'detain-qa-'.Plesk::random_string().'.com';
$request = array(
	'name' => $data['name'],
	'username' => $data['username'],
	'password' => $data['password'],
);
$result = $plesk->create_client($request);
if ($debugCalls == true)
	echo "plesk->create_client(".var_export($request, true).") = ".var_export($result, true). "\n";
$data['client_id'] = $result['id'];
echo "Got Client ID {$data['client_id']}\n";
try {
	$request = array('username' => $data['username']);
	$result = $plesk->get_client($request);
	if ($debugCalls == true)
		echo "plesk->get_client(".var_export($request, true).") = ".var_export($result, true). "\n";
	$request = array(
		'username' => $data['username'],
		'phone' => Plesk::random_string(),
		'email' => $data['email'],
	);
	$result = $plesk->update_client($request);
	if ($debugCalls == true)
		echo "plesk->update_client(".var_export($request, true).") = ".var_export($result, true). "\n";
	$request = array(
		'domain' => $data['subscription_domain'],
		'owner_id' => $data['client_id'],
		'htype' => 'vrt_hst',
		'ftp_login' => $data['username'],
		'ftp_password' => $data['password'],
		'ip' => $data['shared_ip_address'],
		'status' => 0,
		'plan_id' => $data['unlimited_plan_id'],
	);
	$result = $plesk->create_subscription($request);
	if ($debugCalls == true)
		echo "plesk->create_subscription(".var_export($request, true).") = ".var_export($result, true). "\n";
	$data['subscription_id'] = $result['id'];
	echo "Got Subscription ID {$data['subscription_id']}\n";
	$result = $plesk->list_subscriptions();
	if ($debugCalls == true)
		echo "plesk->list_subscriptions() = ".var_export($result, true). "\n";
	$subscription_found = false;
	foreach ($result as $subscription)
		if ($subscription['id'] == $data['subscription_id'])
			$subscription_found = true;
	if (!$subscription_found)
		throw new Exception("Couldn't find created subscription");
	$request = array(
		'domain' => $data['domain'],
		'subscription_id' => $data['subscription_id'],
	);
	$result = $plesk->create_site($request);
	if ($debugCalls == true)
		echo "plesk->create_site(".var_export($request, true).") = ".var_export($result, true). "\n";
	$data['site_id'] = $result['id'];
	echo "Got Site ID {$data['site_id']}\n";
	$request = array('subscription_id' => $data['subscription_id']);
	$result = $plesk->list_sites($request);
	if ($debugCalls == true)
		echo "plesk->list_sites(".var_export($request, true).") = ".var_export($result, true). "\n";
	$site_found = false;
	if (isset($result['id']))
		$result = array($result);
	foreach ($result as $site)
		if (isset($site['id']) && $site['id'] == $data['site_id'])
			$site_found = true;
	if (!$site_found)
		throw new Exception("Couldn't find created site");
	$data['new_domain'] = 'detain-qa-'.Plesk::random_string().'.com';
	echo "Changing Domain from {$data['domain']} to {$data['new_domain']}\n";
	$request = array('id' => $data['site_id'], 'domain' => $data['new_domain']);
	$result = $plesk->update_site($request);
	if ($debugCalls == true)
		echo "plesk->update_site(".var_export($request, true).") = ".var_export($result, true). "\n";
	//echo "Got " . print_r($result, true) . "\n";
} catch (Exception $e) {
	myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
//echo "Final Data: ".print_r($data, true)."\n";
if (isset($data['site_id'])) {
	echo "Deleting Site {$data['site_id']}\n";
	$request = array('id' => $data['site_id']);
	$result = $plesk->delete_site($request);
	if ($debugCalls == true)
		echo "plesk->delete_site(".var_export($request, true).") = ".var_export($result, true). "\n";
} else
	echo "Skipping delete_site as we lack site id\n";
if (isset($data['subscription_id'])) {
	echo "Deleting Subscription {$data['subscription_id']}\n";
	$request = array('id' => $data['subscription_id']);
	$result = $plesk->delete_subscription($request);
	if ($debugCalls == true)
		echo "plesk->delete_subscription(".var_export($request, true).") = ".var_export($result, true). "\n";
} else
	echo "Skipping delete_client as we lack subscription id\n";
if (isset($data['client_id'])) {
	echo "Deleting Client {$data['client_id']}\n";
	$request = array('id' => $data['client_id']);
	$result = $plesk->delete_client($request);
	if ($debugCalls == true)
		echo "plesk->delete_client(".var_export($request, true).") = ".var_export($result, true). "\n";
} else
	echo "Skipping delete_client as we lack customer id\n";
