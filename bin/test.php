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
* @category Scripts
* @copyright 2025
*/

include_once __DIR__.'/../../../../include/functions.inc.php';

use Detain\MyAdminPlesk\ApiRequestException;

require_once INCLUDE_ROOT.'/webhosting/class.plesk.php';

$runSiteTests = true;
$runEmailAddressTests = false;
$runSiteAliasTests = false;
$runSubdomainTests = false;
$runDatabaseTests = false;
/**
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 * @throws \ErrorException
 */
function exception_error_handler($errno, $errstr, $errfile, $errline)
{
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

/**
 * @param int $length
 * @return string
 */
function random_string($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return mb_substr(str_shuffle($chars), 0, $length);
}

set_error_handler('exception_error_handler');
$data = [];
$plesk = new Plesk('162.246.20.210', 'admin', 'x0Bak5&0');
$request = $plesk->listIpAddresses();
if (!isset($request['ips'][0]['ip_address']) || $request['status'] == 'error') {
    throw new xception('Failed getting server information.'.(isset($request['errtext']) ? ' Error message was: '.$request['errtext'].'.' : ''));
}
foreach ($request['ips'] as $idx => $ip_data) {
    if (trim($ip_data['type']) == 'shared' && (!isset($data['shared_ip_address']) || $ip_data['is_default'])) {
        $data['shared_ip_address'] = $ip_data['ip_address'];
    }
}
if (!isset($data['shared_ip_address'])) {
    throw new xception("Couldn't find any shared IP addresses");
}
$plesk = new Plesk('162.246.20.210', 'admin', 'x0Bak5&0');
$request = $plesk->listServicePlans();
foreach ($request as $idx => $plan) {
    if (strtolower($plan['name']) == 'unlimited') {
        $data['unlimited_plan_id'] = $plan['id'];
        break;
    }
}
if (!isset($data['unlimited_plan_id'])) {
    throw new xception("Couldn't find unlimited service plan");
}
$data['client_username'] = strtolower(random_string());
$plesk = new Plesk('162.246.20.210', 'admin', 'x0Bak5&0');
$request = $plesk->createClient(
    [
        'name' => random_string(),
        'username' => $data['client_username'],
        'password' => random_string(16).'1!'
    ]
);
$data['client_id'] = $request->id;
try {
    $request = $plesk->getClient(['username' => $data['client_username']]);
    $request = $plesk->updateClient(
        [
        'username' => $data['client_username'],
        'phone' => random_string(),
        'email' => random_string().'@example.com'
        ]
    );
    $params = [
        'domain_name' => random_string().'.com',
        'username' => $data['client_username'],
        'password' => random_string(16).'1!',
        'ip_address' => $data['shared_ip_address'],
        'owner_id' => $data['client_id'],
        'service_plan_id' => $data['unlimited_plan_id']
    ];
    $request = $plesk->createSubscription($params);
    $data['subscription_id'] = $request->id;
    $request = $plesk->listSubscriptions();
    $subscription_found = false;
    foreach ($request as $subscription) {
        if ($subscription['id'] == $data['subscription_id']) {
            $subscription_found = true;
        }
    }
    if (!$subscription_found) {
        throw new xception("Couldn't find created subscription");
    }
    if ($runSiteTests) {
        $data['domain'] = random_string().'.com';
        $request = $plesk->createSite(['domain' => $data['domain'], 'subscription_id' => $data['subscription_id']]);
        $data['site_id'] = $request->id;
        $request = $plesk->listSites(['subscription_id' => $data['subscription_id']]);
        $site_found = false;
        foreach ($request as $site) {
            if ($site['id'] == $data['site_id']) {
                $site_found = true;
            }
        }
        if (!$site_found) {
            throw new xception("Couldn't find created site");
        }
        $data['domain'] = random_string().'.com';
        $request = $plesk->updateSite(['id' => $data['site_id'], 'domain' => $data['domain']]);
    }
    if ($runSiteTests && $runEmailAddressTests) {
        $data['email_address'] = random_string(4).'@'.$data['domain'];
        $request = $plesk->createEmailAddress(
            [
                'email' => $data['email_address'],
                'password' => random_string().'1!'
            ]
        );
        $data['email_address_id'] = $request->id;
        $request = $plesk->listEmailAddresses(
            [
            'site_id' => $data['site_id']
            ]
        );
        $email_address_found = false;
        foreach ($request as $email_address) {
            if ($email_address['id'] == $data['email_address_id']) {
                $email_address_found = true;
            }
        }
        if (!$email_address_found) {
            throw new xception("Couldn't find created email address (".$data['email_address_id'].')');
        }
        $request = $plesk->updateEmailPassword(
            [
            'email' => $data['email_address'],
            'password' => random_string()
            ]
        );
        $request = $plesk->deleteEmailAddress(
            [
            'email' => $data['email_address']
            ]
        );
    }
    if ($runSiteTests && $runSiteAliasTests) {
        $data['site_alias'] = random_string().'.'.$data['domain'];
        $params = ['site_id' => $data['site_id'], 'alias' => $data['site_alias']];
        $request = $plesk->createSiteAlias($params);
        $data['site_alias_id'] = $request->id;
        $request = $plesk->listSiteAliases(['site_id' => $data['site_id']]);
        $alias_found = false;
        foreach ($request as $alias_id => $alias_name) {
            if ($alias_id == $data['site_alias_id']) {
                $alias_found = true;
            }
        }
        if (!$alias_found) {
            throw new xception("Couldn't find created site alias");
        }
        $request = $plesk->deleteSiteAlias(['id' => $data['site_alias_id']]);
    }
    if ($runSiteTests && $runSubdomainTests) {
        $data['subdomain'] = random_string();
        $request = $plesk->createSubdomain(
            [
            'domain' => $data['domain'],
            'subdomain' => $data['subdomain'],
            'www_root' => '/subdomains/'.strtolower($data['subdomain']),
            'fpt_username' => random_string(),
            'fpt_password' => random_string()
            ]
        );
        $data['subdomain_id'] = $request->id;
        $request = $plesk->listSubdomains(
            [
            'site_id' => $data['site_id']
            ]
        );
        $subdomain_found = false;
        foreach ($request as $subdomain) {
            if ($subdomain['id'] == $data['subdomain_id']) {
                $subdomain_found = true;
            }
        }
        if (!$subdomain_found) {
            throw new xception("Couldn't find created subdomain");
        }
        $request = $plesk->updateSubdomain(
            [
            'id' => $data['subdomain_id'],
            'www_root' => '/subdomains/'.strtolower($data['subdomain']).'2'
            ]
        );
        $data['subdomain'] = random_string();
        $request = $plesk->renameSubdomain(
            [
            'id' => $data['subdomain_id'],
            'name' => $data['subdomain']
            ]
        );
        $info = $request->process();
        $request = $plesk->deleteSubdomain(
            [
            'id' => $data['subdomain_id']
            ]
        );
    }
    if ($runDatabaseTests) {
        $request = $plesk->listDatabaseServers();
        $server_found = false;
        foreach ($request as $server) {
            if ($server['type'] == 'mysql') {
                $data['db_server_id'] = $server['id'];
                $server_found = true;
            }
        }
        if (!$server_found) {
            throw new xception("Couldn't find mysql database server");
        }
        $request = $plesk->createDatabase(
            [
            'name' => random_string(),
            'subscription_id' => $data['subscription_id'],
            'server_id' => $data['db_server_id'],
            'type' => 'mysql'
            ]
        );
        $data['db_id'] = $request->id;
        $request = $plesk->listDatabases(
            [
            'subscription_id' => $data['subscription_id']
            ]
        );
        $databases = $request->process();
        $database_found = false;
        foreach ($databases as $database) {
            if ($database['id'] == $data['db_id']) {
                $database_found = true;
            }
        }
        if (!$database_found) {
            throw new xception("Couldn't find created database");
        }
        $data['db_user_username'] = random_string();
        $request = $plesk->createDatabaseUser(
            [
            'database_id' => $data['db_id'],
            'username' => $data['db_user_username'],
            'password' => random_string()
            ]
        );
        $data['db_user_id'] = $request->id;
        $request = $plesk->getDatabaseUser(
            [
            'database_id' => $data['db_id']
            ]
        );
        if ($data['db_user_id'] != $request->id) {
            throw new xception("Created database user doesn't match retrieved database user");
        }
        /*$request = $plesk->DeleteDatabase(array(
            'id'=>$data['db_id'],
        ));*/
    }
    if ($runSiteTests) {
        $request = $plesk->deleteSite(['id' => $data['site_id']]);
    }
    $request = $plesk->deleteSubscription($data['subscription_id']);
    $request = $plesk->createSecretKey(['ip_address' => file_get_contents('https://api.ipify.org')]);
    $data['secret_key'] = $request->key;
    $request = $plesk->listSecretKeys(['key' => $data['secret_key'], 'host' => $config['host']]);
    $secret_key_found = false;
    foreach ($request as $key) {
        if ($key['key'] == $data['secret_key']) {
            $secret_key_found = true;
        }
    }
    if (!$secret_key_found) {
        throw new xception("Couldn't find created secret_key");
    }
    $request = $plesk->deleteSecretKey(['key' => $data['secret_key']]);
    $request = $plesk->listSecretKeys();
    $secret_key_found = false;
    foreach ($request as $key) {
        if ($key['key'] == $data['secret_key']) {
            $secret_key_found = true;
        }
    }
    if ($secret_key_found) {
        throw new xception('Failed to delete secret_key');
    }
} catch (\Exception $e) {
    throw $e;
}
$plesk = new Plesk('162.246.20.210', 'admin', 'x0Bak5&0');
if (isset($data['client_id'])) {
    $request = $plesk->deleteClient(['id' => $data['client_id']]);
} else {
    echo "Skipping deleteClient as we lack customer id\n";
}
