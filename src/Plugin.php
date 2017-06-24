<?php

namespace Detain\MyAdminPlesk;

use Detain\Plesk\Plesk;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Plesk Webhosting';
	public static $description = 'Allows selling of Plesk Server and VPS License Types.  More info at https://www.netenberg.com/plesk.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a plesk license. Allow 10 minutes for activation.';
	public static $module = 'webhosting';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
		];
	}

	public static function getActivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Plesk Activation', __LINE__, __FILE__);
			function_requirements('activate_plesk');
			activate_plesk($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$plesk = new Plesk(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $plesk->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Plesk editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_plesk', 'icons/database_warning_48.png', 'ReUsable Plesk Licenses');
			$menu->add_link($module, 'choice=none.plesk_list', 'icons/database_warning_48.png', 'Plesk Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.plesk_licenses_list', 'whm/createacct.gif', 'List all Plesk Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('crud_plesk_list', '/../vendor/detain/crud/src/crud/crud_plesk_list.php');
		$loader->add_requirement('crud_reusable_plesk', '/../vendor/detain/crud/src/crud/crud_reusable_plesk.php');
		$loader->add_requirement('get_plesk_licenses', '/../vendor/detain/myadmin-plesk-webhosting/src/plesk.inc.php');
		$loader->add_requirement('get_plesk_list', '/../vendor/detain/myadmin-plesk-webhosting/src/plesk.inc.php');
		$loader->add_requirement('plesk_licenses_list', '/../vendor/detain/myadmin-plesk-webhosting/src/plesk_licenses_list.php');
		$loader->add_requirement('plesk_list', '/../vendor/detain/myadmin-plesk-webhosting/src/plesk_list.php');
		$loader->add_requirement('get_available_plesk', '/../vendor/detain/myadmin-plesk-webhosting/src/plesk.inc.php');
		$loader->add_requirement('activate_plesk', '/../vendor/detain/myadmin-plesk-webhosting/src/plesk.inc.php');
		$loader->add_requirement('get_reusable_plesk', '/../vendor/detain/myadmin-plesk-webhosting/src/plesk.inc.php');
		$loader->add_requirement('reusable_plesk', '/../vendor/detain/myadmin-plesk-webhosting/src/reusable_plesk.php');
		$loader->add_requirement('class.Plesk', '/../vendor/detain/plesk-webhosting/src/Plesk.php');
		$loader->add_requirement('vps_add_plesk', '/vps/addons/vps_add_plesk.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting('licenses', 'Plesk', 'plesk_username', 'Plesk Username:', 'Plesk Username', $settings->get_setting('FANTASTICO_USERNAME'));
		$settings->add_text_setting('licenses', 'Plesk', 'plesk_password', 'Plesk Password:', 'Plesk Password', $settings->get_setting('FANTASTICO_PASSWORD'));
		$settings->add_dropdown_setting('licenses', 'Plesk', 'outofstock_licenses_plesk', 'Out Of Stock Plesk Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_FANTASTICO'), array('0', '1'), array('No', 'Yes',));
	}

}
