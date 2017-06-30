<?php
/**
* Plesk Test
*
* This file attempts the following operations on a real Plesk server (intended to be run by an admin account)
*
* Get server information
* Find shared ip address
* Find unlimited service plan
* Creates a new client
* Get new client from server
* Update client information
* Create subscription
* List subscriptions
* Create new site
* Find created site
* Update site information
* -- some disabled stuff would normally run here --
* Delete previously created site
* Delete previously created subscription
* Deletes previously created client
*
* -- optional stuff -- *
* Create email address
* List email addresses
* Update email address
* Delete email address *
* Create site alias
* List site aliases
* Delete site alias *
* Create subdomain
* List subdomains
* Update subdomain
* Rename subdomain
* Delete subdomain *
* List database servers
* Create database
* List databases
* Create database user
* Get database user info
* Delete database
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @subpackage Scripts
* @copyright 2017
*/

include_once(__DIR__.'/../../../include/functions.inc.php');
require_once(INCLUDE_ROOT.'/webhosting/class.plesk.php');

$runSiteTests = true;
$runEmailAddressTests = false;
$runSiteAliasTests = false;
$runSubdomainTests = false;
$runDatabaseTests = false;

function exception_error_handler($errno, $errstr, $errfile, $errline) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

function random_string($length = 8) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	return mb_substr(str_shuffle($chars), 0, $length);
}

set_error_handler("exception_error_handler");
$data = [];
$plesk = new Plesk('162.246.20.210', 'admin', 'x0Bak5&0');
$request = $plesk->list_ip_addresses();
if (!isset($request['ips'][0]['ip_address']) || $request['status'] == 'error')
	throw new Exception('Failed getting server information.'.(isset($request['errtext']) ? ' Error message was: '.$request['errtext'].'.' : ''));
foreach ($request['ips'] as $idx => $ip_data)
	if (trim($ip_data['type']) == 'shared' && (!isset($data['shared_ip_address']) || $ip_data['is_default']))
		$data['shared_ip_address'] = $ip_data['ip_address'];
if (!isset($data['shared_ip_address']))
	throw new Exception("Couldn't find any shared IP addresses");
$plesk = new Plesk('162.246.20.210', 'admin', 'x0Bak5&0');
$request = $plesk->list_service_plans();
foreach ($request as $idx => $plan)
	if (strtolower($plan['name']) == 'unlimited') {
		$data['unlimited_plan_id'] = $plan['id'];
		break;
	}
if (!isset($data['unlimited_plan_id']))
	throw new Exception("Couldn't find unlimited service plan");
$data['client_username'] = strtolower(random_string());
$plesk = new Plesk('162.246.20.210', 'admin', 'x0Bak5&0');
$request = $plesk->create_client(array(
	'name' => random_string(),
	'username' => $data['client_username'],
	'password' => random_string(16) . "1!",
));
$data['client_id'] = $request->id;
try {
	$request = $plesk->get_client(array('username' => $data['client_username']));
	$request = $plesk->update_client(array(
		'username' => $data['client_username'],
		'phone' => random_string(),
		'email' => random_string().'@example.com',
	));
	$params = array(
		'domain_name' => random_string().'.com',
		'username' => $data['client_username'],
		'password' => random_string(16).'1!',
		'ip_address' => $data['shared_ip_address'],
		'owner_id' => $data['client_id'],
		'service_plan_id' => $data['unlimited_plan_id'],
	);
	$request = $plesk->createSubscription($params);
	$data['subscription_id'] = $request->id;
	$request = $plesk->list_subscriptions();
	$subscription_found = false;
	foreach ($request as $subscription)
		if ($subscription['id'] == $data['subscription_id'])
			$subscription_found = true;
	if (!$subscription_found)
		throw new Exception("Couldn't find created subscription");
	if ($runSiteTests) {
		$data['domain'] = random_string().'.com';
		$request = $plesk->create_site(array('domain' => $data['domain'], 'subscription_id' => $data['subscription_id']));
		$data['site_id'] = $request->id;
		$request = $plesk->list_sites(array('subscription_id' => $data['subscription_id']));
		$site_found = false;
		foreach ($request as $site)
			if ($site['id'] == $data['site_id'])
				$site_found = true;
		if (!$site_found)
			throw new Exception("Couldn't find created site");
		$data['domain'] = random_string().'.com';
		$request = $plesk->update_site(array('id' => $data['site_id'], 'domain' => $data['domain']));
	}
	if ($runSiteTests && $runEmailAddressTests) {
		$data['email_address'] = random_string(4).'@'.$data['domain'];
		$request = $plesk->create_email_address(array(
			'email' => $data['email_address'],
			'password' => random_string() . "1!",
		));
		$data['email_address_id'] = $request->id;
		$request = $plesk->list_email_addresses(array(
			'site_id' => $data['site_id'],
		));
		$email_address_found = false;
		foreach ($request as $email_address)
			if ($email_address['id'] == $data['email_address_id'])
				$email_address_found = true;
		if (!$email_address_found)
			throw new Exception("Couldn't find created email address (" . $data['email_address_id'] . ")");
		$request = $plesk->update_email_password(array(
			'email' => $data['email_address'],
			'password' => random_string(),
		));
		$request = $plesk->delete_email_address(array(
			'email' => $data['email_address'],
		));
	}
	if ($runSiteTests && $runSiteAliasTests) {
		$data['site_alias'] = random_string().'.'.$data['domain'];
		$params = array('site_id' => $data['site_id'], 'alias' => $data['site_alias']);
		$request = $plesk->create_site_alias($params);
		$data['site_alias_id'] = $request->id;
		$request = $plesk->list_site_aliases(array('site_id' => $data['site_id']));
		$alias_found = false;
		foreach ($request as $alias_id => $alias_name)
			if ($alias_id == $data['site_alias_id'])
				$alias_found = true;
		if (!$alias_found)
			throw new Exception("Couldn't find created site alias");
		$request = $plesk->delete_site_alias(array('id' => $data['site_alias_id']));
	}
	if ($runSiteTests && $runSubdomainTests) {
		$data['subdomain'] = random_string();
		$request = $plesk->create_subdomain(array(
			'domain' => $data['domain'],
			'subdomain' => $data['subdomain'],
			'www_root' => '/subdomains/'.strtolower($data['subdomain']),
			'fpt_username' => random_string(),
			'fpt_password' => random_string(),
		));
		$data['subdomain_id'] = $request->id;
		$request = $plesk->list_subdomains(array(
			'site_id' => $data['site_id'],
		));
		$subdomain_found = false;
		foreach ($request as $subdomain)
			if ($subdomain['id'] == $data['subdomain_id'])
				$subdomain_found = true;
		if (!$subdomain_found)
			throw new Exception("Couldn't find created subdomain");
		$request = $plesk->update_subdomain(array(
			'id' => $data['subdomain_id'],
			'www_root' => '/subdomains/'.strtolower($data['subdomain']).'2',
		));
		$data['subdomain'] = random_string();
		$request = $plesk->rename_subdomain(array(
			'id' => $data['subdomain_id'],
			'name' => $data['subdomain'],
		));
		$info = $request->process();
		$request = $plesk->delete_subdomain(array(
			'id' => $data['subdomain_id'],
		));
	}
	if ($runDatabaseTests) {
		$request = $plesk->list_database_servers();
		$server_found = false;
		foreach ($request as $server)
			if ($server['type'] == 'mysql') {
				$data['db_server_id'] = $server['id'];
				$server_found = true;
			}
		if (!$server_found)
			throw new Exception("Couldn't find mysql database server");
		$request = $plesk->create_database(array(
			'name' => random_string(),
			'subscription_id' => $data['subscription_id'],
			'server_id' => $data['db_server_id'],
			'type' => 'mysql',
		));
		$data['db_id'] = $request->id;
		$request = $plesk->list_databases(array(
			'subscription_id' => $data['subscription_id'],
		));
		$databases = $request->process();
		$database_found = false;
		foreach ($databases as $database)
			if ($database['id'] == $data['db_id'])
				$database_found = true;
		if (!$database_found)
			throw new Exception("Couldn't find created database");
		$data['db_user_username'] = random_string();
		$request = $plesk->create_database_user(array(
			'database_id' => $data['db_id'],
			'username' => $data['db_user_username'],
			'password' => random_string(),
		));
		$data['db_user_id'] = $request->id;
		$request = $plesk->get_database_user(array(
			'database_id' => $data['db_id'],
		));
		if ($data['db_user_id'] != $request->id)
			throw new Exception("Created database user doesn't match retrieved database user");
		/*$request = $plesk->DeleteDatabase(array(
			'id'=>$data['db_id'],
		));*/
	}
	if ($runSiteTests) {
		$request = $plesk->delete_site(array('id' => $data['site_id']));
	}
	$request = $plesk->deleteSubscription($data['subscription_id']);
	$request = $plesk->create_secret_key(['ip_address' => file_get_contents('https://api.ipify.org')]);
	$data['secret_key'] = $request->key;
	$request = $plesk->list_secret_keys(['key' => $data['secret_key'], 'host' => $config['host']]);
	$secret_key_found = false;
	foreach ($request as $key)
		if ($key['key'] == $data['secret_key'])
			$secret_key_found = true;
	if (!$secret_key_found)
		throw new Exception("Couldn't find created secret_key");
	$request = $plesk->delete_secret_key(['key' => $data['secret_key']]);
	$request = $plesk->list_secret_keys();
	$secret_key_found = false;
	foreach ($request as $key)
		if ($key['key'] == $data['secret_key'])
			$secret_key_found = true;
	if ($secret_key_found)
		throw new Exception("Failed to delete secret_key");
} catch (Exception $e) {
	throw $e;
}
$plesk = new Plesk('162.246.20.210', 'admin', 'x0Bak5&0');
if (isset($data['client_id']))
	$request = $plesk->delete_client(array('id' => $data['client_id']));
else
	echo "Skipping delete_client as we lack customer id\n";
