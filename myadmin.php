<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_plesk define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Plesk Webhosting',
	'description' => 'Allows selling of Plesk Server and VPS License Types.  More info at https://www.netenberg.com/plesk.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a plesk license. Allow 10 minutes for activation.',
	'module' => 'webhosting',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-plesk-webhosting',
	'repo' => 'https://github.com/detain/myadmin-plesk-webhosting',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		/*'function.requirements' => ['Detain\MyAdminPlesk\Plugin', 'Requirements'],
		'webhosting.settings' => ['Detain\MyAdminPlesk\Plugin', 'Settings'],
		'webhosting.activate' => ['Detain\MyAdminPlesk\Plugin', 'Activate'],
		'webhosting.change_ip' => ['Detain\MyAdminPlesk\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminPlesk\Plugin', 'Menu'] */
	],
];
