<?php
/**
 * Gets a Plesk Class instance for the given server.
 *
 * @param array|bool|false|int|string $server the server to get a Plesk instance for, can be an array like from get_service or a server id, or false for default
 * @return \Detain\MyAdminPlesk\Plesk the plesk instance
 */
function get_webhosting_plesk_instance($server = FALSE) {
	$module = 'webhosting';
	$settings = \get_module_settings($module);
	if (is_array($server)) {
		$serverData = $server;
	} else {
		if ($server === FALSE)
			$server = NEW_WEBSITE_PLESK_SERVER;
		$serverData = get_service_master($server, $module);
	}
	$hash = $serverData[$settings['PREFIX'].'_key'];
	$ip = $serverData[$settings['PREFIX'].'_ip'];
	list($pleskUser, $pleskPass) = explode(':', html_entity_decode($hash));
	myadmin_log('webhosting', 'info', "Plesk($ip, $pleskUser, $pleskPass)", __LINE__, __FILE__);
	return new \Detain\MyAdminPlesk\Plesk($ip, $pleskUser, $pleskPass);
}
