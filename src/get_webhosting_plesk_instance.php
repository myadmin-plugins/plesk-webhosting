<?php
/**
 * Gets a Plesk Class instance for the given server.
 *
 * The server row must carry the panel ip and a "user:password" key. A missing or
 * malformed row is refused here with an ApiRequestException -- the same exception
 * the first API call on an unconfigured instance would have thrown anyway, but
 * without the undefined-key warnings and with a message naming what is missing.
 *
 * @param array|bool|false|int|string $server the server to get a Plesk instance for, can be an array like from get_service or a server id, or false for default
 * @return \Detain\MyAdminPlesk\Plesk the plesk instance
 * @throws \Detain\MyAdminPlesk\ApiRequestException when the server row has no ip or no usable credentials
 */
function get_webhosting_plesk_instance($server = false)
{
    $module = 'webhosting';
    $settings = \get_module_settings($module);
    $prefix = is_array($settings) && isset($settings['PREFIX']) ? $settings['PREFIX'] : 'website';
    if (is_array($server)) {
        $serverData = $server;
    } else {
        if ($server === false) {
            $server = defined('NEW_WEBSITE_PLESK_SERVER') ? NEW_WEBSITE_PLESK_SERVER : false;
        }
        $serverData = $server === false ? false : get_service_master($server, $module);
    }
    $label = is_array($serverData) && isset($serverData[$prefix.'_name']) && $serverData[$prefix.'_name'] !== ''
        ? $serverData[$prefix.'_name']
        : (is_scalar($server) && $server !== false ? (string) $server : 'unknown');
    if (!is_array($serverData)) {
        throw new \Detain\MyAdminPlesk\ApiRequestException('Plesk server '.$label.' not found');
    }
    $ip = isset($serverData[$prefix.'_ip']) ? trim((string) $serverData[$prefix.'_ip']) : '';
    if ($ip === '') {
        throw new \Detain\MyAdminPlesk\ApiRequestException('Plesk server '.$label.' has no ip configured');
    }
    $hash = isset($serverData[$prefix.'_key']) ? html_entity_decode((string) $serverData[$prefix.'_key']) : '';
    // limit 2: a password may itself contain ':'
    $credentials = explode(':', $hash, 2);
    if (count($credentials) !== 2 || $credentials[0] === '' || $credentials[1] === '') {
        throw new \Detain\MyAdminPlesk\ApiRequestException('Plesk server '.$label.' has no user:password key configured');
    }
    [$pleskUser, $pleskPass] = $credentials;
    //myadmin_log('webhosting', 'debug', "Plesk($ip, $pleskUser, $pleskPass)", __LINE__, __FILE__, $module);
    return new \Detain\MyAdminPlesk\Plesk($ip, $pleskUser, $pleskPass);
}
