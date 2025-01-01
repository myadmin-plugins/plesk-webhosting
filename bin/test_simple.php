<?php
/**
* Plesk Test Simple
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @copyright 2025
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
$plesk = get_webhosting_plesk_instance(($_SERVER['argv'][1] ?? false));
$debugCalls = false;
//$plesk->debug = true;
try {
    $result = $plesk->listIpAddresses();
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if (!isset($result['ips'][0]['ip_address']) || $result['status'] == 'error') {
    throw new xception('Failed getting server information.'.(isset($result['errtext']) ? ' Error message was: '.$result['errtext'].'.' : ''));
}
if ($debugCalls == true) {
    echo 'plesk->list_ip_adddresses() = '.var_export($result, true).PHP_EOL;
}
foreach ($result['ips'] as $idx => $ip_data) {
    if (trim($ip_data['type']) == 'shared' && (!isset($data['shared_ip_address']) || $ip_data['is_default'])) {
        $data['shared_ip_address'] = $ip_data['ip_address'];
    }
}
try {
    if (!isset($data['shared_ip_address'])) {
        throw new xception("Couldn't find any shared IP addresses");
    }
    $result = $plesk->listServicePlans();
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if ($debugCalls == true) {
    echo 'plesk->listServicePlans() = '.var_export($result, true).PHP_EOL;
}
foreach ($result as $idx => $plan) {
    //if (strtolower($plan['name']) == 'unlimited') {
    if ($plan['name'] == 'Default Simple') {
        $data['unlimited_plan_id'] = $plan['id'];
        break;
    }
}
if (!isset($data['unlimited_plan_id'])) {
    throw new xception("Couldn't find unlimited service plan");
}
$data['username'] = 'detain'.strtolower(Plesk::random_string(5));
$data['name'] = Plesk::random_string(8).' '.Plesk::random_string(8);
$data['password'] = Plesk::random_string(10).'1!';
$data['email'] = Plesk::random_string().'@example.com';
$data['domain'] = 'detain-qa-'.Plesk::random_string().'.com';
$data['subscription_domain'] = 'detain-qa-'.Plesk::random_string().'.com';
$request = [
    'name' => $data['name'],
    'username' => $data['username'],
    'password' => $data['password']
];
try {
    $result = $plesk->createClient($request);
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if ($debugCalls === true) {
    echo 'plesk->createClient('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
}
$data['client_id'] = $result['id'];
echo "Got Client ID {$data['client_id']}\n";
$request = ['username' => $data['username']];
try {
    $result = $plesk->getClient($request);
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if ($debugCalls === true) {
    echo 'plesk->getClient('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
}
$request = ['username' => $data['username'], 'phone' => Plesk::random_string(), 'email' => $data['email']];
try {
    $result = $plesk->updateClient($request);
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if ($debugCalls === true) {
    echo 'plesk->updateClient('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
}
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
try {
    $result = $plesk->createSubscription($request);
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if ($debugCalls === true) {
    echo 'plesk->createSubscription('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
}
$data['subscription_id'] = $result['id'];
echo "Got Subscription ID {$data['subscription_id']}\n";
try {
    $result = $plesk->listSubscriptions();
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if ($debugCalls === true) {
    echo 'plesk->listSubscriptions() = '.var_export($result, true).PHP_EOL;
}
$subscription_found = false;
foreach ($result as $subscription) {
    if ($subscription['id'] == $data['subscription_id']) {
        $subscription_found = true;
    }
}
if (!$subscription_found) {
    throw new xception("Couldn't find created subscription");
}
$request = [
    'domain' => $data['domain'],
    'subscription_id' => $data['subscription_id']
];
try {
    $result = $plesk->createSite($request);
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if ($debugCalls == true) {
    echo 'plesk->createSite('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
}
$data['site_id'] = $result['id'];
echo "Got Site ID {$data['site_id']}\n";
$request = ['subscription_id' => $data['subscription_id']];
try {
    $result = $plesk->listSites($request);
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if ($debugCalls == true) {
    echo 'plesk->listSites('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
}
$site_found = false;
if (isset($result['id'])) {
    $result = [$result];
}
foreach ($result as $site) {
    if (isset($site['id']) && $site['id'] == $data['site_id']) {
        $site_found = true;
    }
}
if (!$site_found) {
    throw new xception("Couldn't find created site");
}
$data['new_domain'] = 'detain-qa-'.Plesk::random_string().'.com';
echo "Changing Domain from {$data['domain']} to {$data['new_domain']}\n";
$request = ['id' => $data['site_id'], 'domain' => $data['new_domain']];
try {
    $result = $plesk->updateSite($request);
} catch (\Exception $e) {
    myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
}
if ($debugCalls == true) {
    echo 'plesk->updateSite('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
}
//echo "Got " . print_r($result, TRUE).PHP_EOL;
//echo "Final Data: ".print_r($data, TRUE).PHP_EOL;
if (isset($data['site_id'])) {
    echo "Deleting Site {$data['site_id']}\n";
    $request = ['id' => $data['site_id']];
    try {
        $result = $plesk->deleteSite($request);
    } catch (\Exception $e) {
        myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
    }
    if ($debugCalls == true) {
        echo 'plesk->deleteSite('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
    }
} else {
    echo "Skipping deleteSite as we lack site id\n";
}
if (isset($data['subscription_id'])) {
    echo "Deleting Subscription {$data['subscription_id']}\n";
    $request = ['id' => $data['subscription_id']];
    try {
        $result = $plesk->deleteSubscription($request);
    } catch (\Exception $e) {
        myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
    }
    if ($debugCalls == true) {
        echo 'plesk->deleteSubscription('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
    }
} else {
    echo "Skipping deleteClient as we lack subscription id\n";
}
if (isset($data['client_id'])) {
    echo "Deleting Client {$data['client_id']}\n";
    $request = ['id' => $data['client_id']];
    try {
        $result = $plesk->deleteClient($request);
    } catch (\Exception $e) {
        myadmin_log('webhosting', 'critical', 'Caught exception: '.$e->getMessage(), __LINE__, __FILE__);
    }
    if ($debugCalls == true) {
        echo 'plesk->deleteClient('.var_export($request, true).') = '.var_export($result, true).PHP_EOL;
    }
} else {
    echo "Skipping deleteClient as we lack customer id\n";
}
