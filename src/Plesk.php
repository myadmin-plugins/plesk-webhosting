<?php

namespace Detain\MyAdminPlesk;

use Detain\MyAdminPlesk\ApiRequestException;

/**
 * A class for interfacing with the Plesk API
 *
 * @link http://docs.plesk.com/en-US/17.0/api-rpc/reference.28784/ *
 */
class Plesk {
	public $curl;
	private $host;
	private $login;
	private $password;
	public $packet;
	public $debug = FALSE;

	/**
	 * Plesk constructor.
	 *
	 * @param string $host the hostname of the plesk server
	 * @param string $login the administrator user name
	 * @param string $password the administrator password
	 */
	public function __construct($host, $login, $password) {
		$this->host = $host;
		$this->login = $login;
		$this->password = $password;
		//$this->updateCurl();
	}

	/**
	 * @param int $length
	 * @return string
	 */
	public static function randomString($length = 8) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		return mb_substr(str_shuffle($chars), 0, $length);
	}

	public function updateCurl() {
		$this->curlInit($this->host, $this->login, $this->password);
	}

	/**
	 * Prepares CURL to perform Plesk API request
	 *
	 * @param string $host the hostname of the plesk server
	 * @param string $login the administrator user name
	 * @param string $password the administrator password
	 * @return resource
	 */
	public function curlInit($host, $login, $password) {
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_URL, "https://{$host}:8443/enterprise/control/agent.php");
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->curl, CURLOPT_POST, TRUE);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
			"HTTP_AUTH_LOGIN: {$login}",
			"HTTP_AUTH_PASSWD: {$password}", 'HTTP_PRETTY_PRINT: TRUE', 'Content-Type: text/xml'
		]
		);
		return $this->curl;
	}

	/**
	 * Performs a Plesk API request, returns raw API response text
	 *
	 * @param string $packet
	 * @return string
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 */
	public function sendRequest($packet) {
		$this->updateCurl();
		$this->packet = $packet;
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $packet);
		$result = curl_exec($this->curl);
		if (curl_errno($this->curl)) {
			$errmsg = curl_error($this->curl);
			$errcode = curl_errno($this->curl);
			curl_close($this->curl);
			throw new ApiRequestException($errmsg, $errcode);
		}
		curl_close($this->curl);
		//var_dump($result);
		if ($this->debug === TRUE) {
			$tempXml = new \SimpleXMLElement($packet);
			$tempXml = json_decode(json_encode($tempXml), TRUE);
			if (isset($tempXml['@attributes']))
				unset($tempXml['@attributes']);
			$function = array_keys($tempXml)[0];
			$type = array_keys($tempXml[$function])[0];
			$tempXml = $tempXml[$function][$type];
			$call = 'Plesk::'.$type.'_'.$function;
			myadmin_log('webhosting', 'debug', 'Calling '.$call.'('.json_encode($tempXml).')', __LINE__, __FILE__);
		}
		return $result;
	}

	/**
	 * Looks if API responded with correct data
	 *
	 * @param string $responseString
	 * @return \SimpleXMLElement
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 */
	public function parseResponse($responseString) {
		$xml = new \SimpleXMLElement($responseString);
		if (!is_a($xml, 'SimpleXMLElement'))
			throw new ApiRequestException("Can not parse server response: {$responseString}");
		if ($this->debug === TRUE) {
			$tempXml = json_decode(json_encode($xml), TRUE);
			if (isset($tempXml['@attributes']))
				unset($tempXml['@attributes']);
			$function = array_keys($tempXml)[0];
			$type = array_keys($tempXml[$function])[0];
			$call = 'Plesk::'.$type.'_'.$function;
			myadmin_log('webhosting', 'debug', $call.' got: '.json_encode($tempXml), __LINE__, __FILE__);
		}
		return $xml;
	}

	/**
	 * Check data in API response
	 *
	 * @param \SimpleXMLElement $response
	 * @return
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 */
	public function checkResponse(\SimpleXMLElement $response) {
		$resultNode = $response->domain->get->result;
		// check if request was successful
		if ('error' == (string) $resultNode->status)
			throw new ApiRequestException('Plesk API returned error: '.(string) $resultNode->result->errtext);
		return $resultNode;
	}

	/**
	 *  the reduced list of error codes which is supported by Plesk 8.0 for UNIX / Plesk 7.6 for Windows and later.
	 *  @link http://docs.plesk.com/en-US/17.0/api-rpc/error-codes/reduced-list-of-error-codes.33765/
	 *
	 * @return string[] list of error codes
	 */
	public function getErrorCodes() {
		return [
			1001 => 'Authentication failed - wrong password.',
			1002 => 'User account  already exists.',
			1003 => 'Agent initialization failed.',
			1004 => 'Plesk initial setup not completed.',
			1005 => 'API RPC version not supported.',
			1006 => 'Permission denied.',
			1007 => 'Inserted data already exists.',
			1008 => 'Multiple access denied.',
			1009 => 'Invalid Virtuozzo key.',
			1010 => 'Access to Plesk Panel denied.',
			1011 => 'Account disabled.',
			1012 => 'Locked login.',
			1013 => 'Object does not exist/Unknown service.',
			1014 => 'Parsing error: wrong format of XML request.',
			1015 => 'Object owner not found.',
			1017 => 'Feature not supported by the current version of API RPC.',
			1018 => 'IP address not found.',
			1019 => 'Invalid value.',
			1023 => 'Operation failed.',
			1024 => 'Limit reached.',
			1025 => 'Wrong status value.',
			1026 => 'Component not installed.',
			1027 => 'IP operation failed.',
			1029 => 'Unknown authentication method.',
			1030 => 'License expired.',
			1031 => 'Component not configured.',
			1032 => 'Wrong network interface.',
			1033 => 'Client account is incomplete (important fields are empty).',
			1050 => 'Webmail not installed.',
			11003 => 'Secret key validation failed.',
			14008 => 'Wrong database server type.',
			14009 => 'Database server not configured.'
		];
	}

	/**
	 * returns the specific error text for your given error code
	 *
	 * @param int $code the error code
	 * @return string the description of the error
	 */
	public function getError($code) {
		$codes = $this->getErrorCodes();
		return $codes[$code];
	}

	/**
	 * Creates Web User
	 *
	 * @param $params
	 * @return \DomDocument
	 */
	public function createWebUser($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;

		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);

		$webuser = $xmldoc->createElement('webuser');
		$packet->appendChild($webuser);

		$add = $xmldoc->createElement('add');
		$webuser->appendChild($add);

		$add->appendChild($xmldoc->createElement('site-id', $params['site-id']));
		$add->appendChild($xmldoc->createElement('login', $params['login']));
		$add->appendChild($xmldoc->createElement('password', $params['password']));
		$add->appendChild($xmldoc->createElement('ftp-quota', 100));

		return $xmldoc;
	}

	/**
	 * @param $username
	 * @param $password
	 * @param $data
	 * @return \DomDocument
	 */
	public function createCustomer($username, $password, $data) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;

		$packet = $xmldoc->createElement('packet');
		$geninfo = $xmldoc->appendChild($packet)->appendChild($xmldoc->createElement('customer'))->appendChild($xmldoc->createElement('add'))->appendChild($xmldoc->createElement('gen_info'));
		$data['login'] = $username;
		$data['passwd'] = $password;
		$data['status'] = '0';
		$dataMappings = [
			'cname' => 'company',
			'pname' => 'name',
			'email' => 'account_lid',
			'pcode' => 'zip',
			'cname' => 'company'
		];
		$fields = [
			'cname',
			'pname',
			'login',
			'passwd',
			'status',
			'phone',
			'fax',
			'email',
			'address',
			'city',
			'state',
			'pcode',
			'country'
		];
		foreach ($fields as $field) {
			$sfield = $field;
			if (isset($dataMappings[$field]))
				$sfield = $dataMappings[$field];
			if (isset($data[$sfield]) && $data[$sfield] != '')
				$geninfo->appendChild($xmldoc->createElement($field, $data[$sfield]));
			//else
				//$geninfo->appendChild($xmldoc->createElement($field));
		}
		//print_r($xmldoc->saveXML());exit;
		return $xmldoc;
	}

	/**
	 * Creates mail account
	 *
	 * @param $params
	 * @return \DomDocument
	 */
	public function createMailAccount($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;

		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);

		$mail = $xmldoc->createElement('mail');
		$packet->appendChild($mail);

		$create = $xmldoc->createElement('create');
		$mail->appendChild($create);

		$filter = $xmldoc->createElement('filter');
		$create->appendChild($filter);

		$siteId = $xmldoc->createElement('site-id', $params['site-id']);
		$filter->appendChild($siteId);

		$mailname = $xmldoc->createElement('mailname');
		$filter->appendChild($mailname);

		$name = $xmldoc->createElement('name', $params['mailname']);
		$mailname->appendChild($name);

		$mailbox = $xmldoc->createElement('mailbox');
		$mailname->appendChild($mailbox);

		$enabled = $xmldoc->createElement('enabled', 'true');
		$mailbox->appendChild($enabled);

		$password = $xmldoc->createElement('password');
		$mailname->appendChild($password);

		$value = $xmldoc->createElement('value', $params['password']);
		$password->appendChild($value);

		$type = $xmldoc->createElement('type', $params['password-type']);
		$password->appendChild($type);

		return $xmldoc;
	}

	/**
	 * @return array
	 */
	public function getServerInfoTypes() {
		return [
			'key' => 'It retrieves Plesk license key.',
			'gen_info' => 'It retrieves general server information which is now presented by the server name.',
			'components' => 'It retrieves software components installed on the server and managed by Plesk.',
			'stat' => 'It retrieves Plesk and OS versions, and statistics on the server resources usage and Plesk logical objects.',
			'admin' => 'It retrieves Plesk Administrator\'s personal information and settings.',
			'interfaces' => 'It retrieves network interfaces supported by the server.',
			'services_state' => 'It retrieves current state of the server services, such as DNS service, FTP service, Mail service, Fail2Ban, and so on.',
			'prefs' => 'It retrieves such server preferences as settings of traffic usage statistics and apache restart interval.',
			'shells' => 'It retrieves shells installed on the server and available for choice when configuring a site\'s physical hosting.',
			'session_setup' => 'It retrieves session idle time, namely, the amount of time a session with Plesk should stay valid when no actions are performed.',
			'site-isolation-config' => 'It retrieves the the server-wide site isolation settings.',
			'updates' => 'It retrieves the information about installed and available Plesk updates, missed security updates, and Plesk update policy.',
			'admin-domain-list' => 'It retrieves the information about all domains, addon domains, subdomains, and domain aliases created on the administrator\'s subscriptions.',
			'certificates' => 'It retrieves the information about the SSL/TLS certificates used for securing Plesk and mail server.'
		];
	}

	/**
	 * converts a var_export output to one using shorthand arrays and compacting empty arrays
	 *
	 * @param array $result an array to get a php shorthand array version of
	 * @return string the exported php code
	 */
	public function varExport($result) {
		$export = var_export($result, TRUE);
		$export = preg_replace("/^array\s*\(\s*$/m", '[', $export);
		$export = preg_replace("/^\)\s*$/m", ']', $export);
		$export = preg_replace("/=>\s*$\n\s*array\s*\(/m", '=> [', $export);
		$export = preg_replace("/^(\s*)\),\s*$/m", '$1],', $export);
		$export = preg_replace("/=>\s*\[\s*$\n\s*\],\s*$/m", '=> [],', $export);
		return $export;
	}

	/**
	 * recursively crawls the results and compacts certain types of arrays into a simpler and easier to read and reference form
	 *
	 * @param array $result
	 * @return array the result but compacted where possible
	 */
	public function fixResult($result) {
		if (is_array($result)) {
			$tempResult = $result;
			foreach ($tempResult as $key => $value) {
				if (is_numeric($key) && is_array($value) && count($value) == 2 && isset($value['name']) && isset($value['value'])) {
					unset($result[$key]);
					$result[$value['name']] = $value['value'];
				} elseif (is_numeric($key) && is_array($value) && count($value) == 2 && isset($value['name']) && isset($value['version'])) {
					unset($result[$key]);
					$result[$value['name']] = $value['version'];
				} elseif (is_array($value)) {
					$result[$key] = $this->fixResult($value);
				}
			}
		}
		return $result;
	}

	/**
	 * Returns DOM object representing request for
	 *
	 * @return \DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function getServerInfo() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->getServerInfoTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		if (!isset($result[$packetName]))
			throw new ApiRequestException('Plesk getServerInfo returned Unknown Data '.json_encode($result));
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk getServerInfo returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Creates a client session in plesk
	 *
	 * @param $user
	 * @return \DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createSession($user) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('create_session');
		$domain->appendChild($get);
		$get->appendChild($xmldoc->createElement('login', $user));
		$data = $xmldoc->createElement('data');
		$get->appendChild($data);
		$data->appendChild($xmldoc->createElement('user_ip', base64_encode(\MyAdmin\Session::get_client_ip())));
		$data->appendChild($xmldoc->createElement('source_server', base64_encode(DOMAIN)));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['create_session']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createSession('.$user.') returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns array representing request for information about all available customers
	 *
	 * @return array an array of customers
	 */
	public function getCustomers() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'customer';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		$dataset = $xmldoc->createElement('dataset');
		$get->appendChild($dataset);
		$dataset->appendChild($xmldoc->createElement('gen_info'));
		$dataset->appendChild($xmldoc->createElement('stat'));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result['customer']['get']['result'];
		return $result;
	}

	/**
	 * Returns array representing request for information about all available domains
	 *
	 * @param bool $hosting include the Hosting info block, defaults to TRUE
	 * @param bool $limits include the Limits info block, defaults to TRUE
	 * @param bool $stat include the Stat info block, defaults to TRUE
	 * @param bool $prefs include the Prefs info block, defaults to TRUE
	 * @return array an array of domains
	 */
	public function getWebspaces($hosting = TRUE, $limits = TRUE, $stat = TRUE, $prefs = TRUE) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'webspace';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		$dataset = $xmldoc->createElement('dataset');
		$get->appendChild($dataset);
		if ($hosting == TRUE)
			$dataset->appendChild($xmldoc->createElement('hosting'));
		if ($limits == TRUE)
			$dataset->appendChild($xmldoc->createElement('limits'));
		if ($prefs == TRUE)
			$dataset->appendChild($xmldoc->createElement('prefs'));
		if ($stat == TRUE)
			$dataset->appendChild($xmldoc->createElement('stat'));
		$dataset->appendChild($xmldoc->createElement('gen_info'));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result['webspace']['get']['result'];
		return $result;
	}

	/**
	 * @return array
	 */
	public function getObjectStatusList() {
		return [
			'0' => 'active',
			'4' => 'under backup',
			'16' => 'disabled by admin',
			'32' => 'disabled by reseller',
			'64' => 'disabled by customer',
			'256' => 'expired'
		];
	}

	/**
	 * @return string[]
	 */
	public function getSiteFilters() {
		return [
			'id',
			'parent-id',
			'parent-site-id',
			'name',
			'parent-name',
			'parent-site-name',
			'guid',
			'parent-guid',
			'parent-site-guid'
		];
	}

	/**
	 * @return string[]
	 */
	public function getSiteDatasets() {
		return [
			'gen_info',
			'hosting',
			'stat',
			'prefs',
			'disk_usage'
		];
	}

	/**
	 * Returns DOM object representing request for information about all available sites
	 *
	 * @param bool|array $params
	 * @return array
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function getSites($params = FALSE) {
		if ($params === FALSE)
			$params = [];
		$mapping = [
			'subscription_id' => 'parent-id'
		];
		$filters = $this->getSiteFilters();
		$datasets = $this->getSiteDatasets();
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'site';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		$dataset = $xmldoc->createElement('dataset');
		$get->appendChild($dataset);
		foreach ($datasets as $field)
			$dataset->appendChild($xmldoc->createElement($field));
		foreach ($params as $field => $value) {
			if (isset($mapping[$field]))
				$realField = $mapping[$field];
			else
				$realField = $field;
			if (in_array($realField, $filters))
				$filter->appendChild($xmldoc->createElement($realField, $value));
		}
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		if (isset($result[$packetName]['get']) && isset($result[$packetName]['get']['result'])) {
			$result = $result[$packetName]['get']['result'];
			if (isset($result['status'])) {
				if ($result['status'] == 'error')
					throw new ApiRequestException('Plesk getSites returned Error #'.$result['errcode'].' '.$result['errtext']);
			} else {
				$resultValues = array_values($result);
				foreach ($resultValues as $resultData)
					if ($resultData['status'] == 'error')
						throw new ApiRequestException('Plesk getSites returned Error #'.$resultData['errcode'].' '.$resultData['errtext']);
			}
		}
		return $result;

	}

	/**
	 * Returns DOM object representing request for get site
	 *
	 * @param bool $params
	 * @return array
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function getSite($params = FALSE) {
		return $this->getSites($params);
	}

	/**
	 * Returns DOM object representing request for list sites
	 *
	 * @param bool|array $params
	 * @return array
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listSites($params = FALSE) {
		return $this->getSites($params);
	}

	/**
	 * @return string[]
	 */
	public function getSiteGenSetups() {
		return [
			'name',
			'htype',
			'status',
			'webspace-name',
			'webspace-id',
			'webspace-guid',
			'parent-site-id',
			'parent-site-name',
			'parent-site-guid'
		];
	}

	/**
	 * Returns DOM object representing request for create site
	 *
	 * @param array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createSite($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'site';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('add');
		$domain->appendChild($get);
		$genSetup = $xmldoc->createElement('gen_setup');
		$get->appendChild($genSetup);
		$hosting = $xmldoc->createElement('hosting');
		$prefs = $xmldoc->createElement('prefs');
		$vrtHst = $xmldoc->createElement('vrt_hst');
		$required = [
			'name'
		];
		$mapping = [
			'domain' => 'name',
			'subscription_id' => 'webspace-id',
			'plan_id' => 'plan-id'
		];
		$revMapping = [];
		foreach ($mapping as $field => $value)
			$revMapping[$value] = $field;
		$prefTypes = [
			'www',
			'stat_ttl',
			'outgoing-messages-domain-limit'
		];
		$vrtHstProperties = [
			'ftp_login',
			'ftp_password'
		];
		$vrtHsts = [
			'ip_address'
		];
		$extra = [
			'plan-id',
			'plan-name',
			'plan-guid',
			'plan-external-id'
		];
		$genSetups = $this->getSiteGenSetups();
		foreach ($required as $require)
			if (!isset($params[$require]) && (isset($revMapping[$require]) && !isset($params[$revMapping[$require]])))
				throw new ApiRequestException('Plesk API '.__FUNCTION__.'('.json_decode(json_encode($params), TRUE).') missing required parameter '.$require);
		if (isset($params['htype'])) {
			$get->appendChild($hosting);
			$hosting->appendChild($vrtHst);
		}
		$found = FALSE;
		foreach ($prefTypes as $pref)
			if (isset($params[$pref]))
				$found = TRUE;
		if ($found == TRUE)
			$get->appendChild($prefs);
		foreach ($params as $field => $value) {
			if (isset($mapping[$field]))
				$realField = $mapping[$field];
			else
				$realField = $field;
			if (in_array($realField, $genSetups))
				$genSetup->appendChild($xmldoc->createElement($realField, $value));
			if (in_array($realField, $prefTypes))
				$prefs->appendChild($xmldoc->createElement($realField, $value));
			if (in_array($realField, $vrtHstProperties)) {
				$property = $xmldoc->createElement('property');
				$vrtHst->appendChild($property);
				$property->appendChild($xmldoc->createElement('name', $realField));
				$property->appendChild($xmldoc->createElement('value', $value));
			}
			if (in_array($realField, $vrtHsts))
				$vrtHst->appendChild($xmldoc->createElement($realField, $value));
			if (in_array($realField, $extra))
				$get->appendChild($xmldoc->createElement($realField, $value));
		}
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['add']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createSite returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for update site
	 *
	 * @param array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function updateSite($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'site';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('set');
		$domain->appendChild($get);
		$genSetups = $this->getSiteGenSetups();
		//$filters = $this->getSiteFilters();
		$genSetupAdded = FALSE;
		$filters = ['id'];
		$filter = $xmldoc->createElement('filter');
		$values = $xmldoc->createElement('values');
		$genSetup = $xmldoc->createElement('gen_setup');
		$get->appendChild($filter);
		$get->appendChild($values);
		foreach ($params as $field => $value) {
			if (isset($mapping[$field]))
				$realField = $mapping[$field];
			else
				$realField = $field;
			if (in_array($realField, $filters))
				$filter->appendChild($xmldoc->createElement($realField, $value));
			if (in_array($realField, $genSetups)) {
				if ($genSetupAdded == FALSE) {
					$values->appendChild($genSetup);
					$genSetupAdded = TRUE;
				}
				$genSetup->appendChild($xmldoc->createElement($realField, $value));
			}
		}
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['set']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk updateSite returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for update site
	 *
	 * @param array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function updateServer($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->updateSiteTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk updateSite returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * @param array $params
	 * @return \DomDocument
	 */
	public function createSite2($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;

		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);

		$site = $xmldoc->createElement('site');
		$packet->appendChild($site);

		$add = $xmldoc->createElement('add');
		$site->appendChild($add);

		$genSetup = $xmldoc->createElement('gen_setup');
		$add->appendChild($genSetup);
		$hosting = $xmldoc->createElement('hosting');
		$add->appendChild($hosting);

		$genSetup->appendChild($xmldoc->createElement('name', $params['name']));
		$genSetup->appendChild($xmldoc->createElement('webspace-id', $params['webspace-id']));

		$vrtHst = $xmldoc->createElement('vrt_hst');
		$hosting->appendChild($vrtHst);

		$property = $xmldoc->createElement('property');
		$vrtHst->appendChild($property);
		$property->appendChild($xmldoc->createElement('name', 'php'));
		$property->appendChild($xmldoc->createElement('value', 'true'));

		return $xmldoc;
	}

	/**
	 * Returns DOM object representing request for create client
	 *
	 * @param array $data account data
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createClient($data) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'customer';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('add');
		$domain->appendChild($get);
		$info = $xmldoc->createElement('gen_info');
		$get->appendChild($info);
		$defaultParams = [
			'name' => NULL,
			'username' => NULL,
			'password' => NULL,
			'status' => 0
		];
		$mapping = [
			'company' => 'cname',
			'name' => 'pname',
			'username' => 'login',
			'password' => 'passwd',
			'status' => 'status',
			'phone' => 'phone',
			'fax' => 'fax',
			'email' => 'email',
			'address' => 'address',
			'city' => 'city',
			'state' => 'state',
			'zip' => 'pcode',
			'country' => 'country'
		];
		foreach ($mapping as $field => $realField)
			if (isset($data[$field]))
				$info->appendChild($xmldoc->createElement($realField, $data[$field]));
			elseif (isset($defaultParams[$field]))
				$info->appendChild($xmldoc->createElement($realField, $defaultParams[$field]));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['add']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createClient returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for create database
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createDatabase() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->createDatabaseTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createDatabase returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for create database user
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createDatabaseUser() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->createDatabaseUserTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createDatabaseUser returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for create email address
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createEmailAddress() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->createEmailAddress_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createEmailAddress returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for create secret key
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createSecretKey() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->createSecretKey_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createSecretKey returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for create site alias
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createSiteAlias() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->createSiteAliasTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createSiteAlias returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for create subdomain
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createSubdomain() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->createSubdomainTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createSubdomain returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * returns an array of possible hosting types
	 *
	 * vrt_hst - virtual hosting
	 * std_fwd none - standard forwarding
	 * frm_fwd - frame forwarding,
	 * none. Data type: string. Allowed values: vrt_hst | std_fwd | frm_fwd | none.
	 *
	 * @return string[] an array of possible htype values
	 */
	public function getHtypes() {
		return [
			'vrt_hst',
			'std_fwd',
			'frm_fwd',
			'none'
		];
	}

	/**
	 * @return string[]
	 */
	public function getSubscriptionFilters() {
		return [
			'id',
			'owner-id',
			'name',
			'owner-login',
			'guid',
			'owner-guid',
			'external-id',
			'owner-external-id'
		];
	}

	/**
	 * @return string[]
	 */
	public function getSubscriptionDatasets() {
		return [
			'gen_info',
			'hosting',
			'limits',
			'stat',
			'prefs',
			'disk_usage',
			'performance',
			'subscriptions',
			'permissions',
			'plan-items',
			'php-settings',
			'resource-usage',
			'mail',
			/*'aps-filter', */
			/*'packages',*/
		];

	}

	/**
	 * Returns DOM object representing request for create subscription
	 *
	 * @param array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function createSubscription($params) {
		$required = [
			'name',
			'ip_address'
		];
		$genSetups = [
			'name',
			'ip_address',
			'owner-id',
			'owner-login',
			'owner-guid',
			'owner-external-id',
			'htype',
			'status',
			'external-id'
		];
		$vrtHstProperties = [
			'ftp_login',
			'ftp_password'
		];
		$vrtHsts = [
			'ip_address'
		];
		$extra = [
			'plan-id',
			'plan-name',
			'plan-guid',
			'plan-external-id'
		];
		$mapping = [
			'domain' => 'name',
			'owner_id' => 'owner-id',
			'owner_login' => 'owner-login',
			'ip' => 'ip_address',
			'subscription_id' => 'webspace-id',
			'plan_id' => 'plan-id'
		];
		$revMapping = [];
		foreach ($mapping as $field => $value)
			$revMapping[$value] = $field;
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'webspace';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('add');
		$domain->appendChild($get);
		$genSetup = $xmldoc->createElement('gen_setup');
		$hosting = $xmldoc->createElement('hosting');
		$vrtHst = $xmldoc->createElement('vrt_hst');
		$get->appendChild($genSetup);
		foreach ($required as $require)
			if ((isset($revMapping[$require]) && !isset($params[$revMapping[$require]])) && !isset($params[$require]))
				throw new ApiRequestException('Plesk API '.__FUNCTION__.'('.json_decode(json_encode($params), TRUE).') missing required parameter '.$require);
		$hostingAdded = FALSE;
		if (isset($params['htype'])) {
			$get->appendChild($hosting);
			$hosting->appendChild($vrtHst);
			$hostingAdded = TRUE;
		}
		foreach ($params as $field => $value) {
			if (isset($mapping[$field]))
				$realField = $mapping[$field];
			else
				$realField = $field;
			if (in_array($realField, $genSetups))
				$genSetup->appendChild($xmldoc->createElement($realField, $value));
			if (in_array($realField, $vrtHstProperties)) {
				if ($hostingAdded == FALSE) {
					$get->appendChild($hosting);
					$hosting->appendChild($vrtHst);
					$hostingAdded = TRUE;
				}
				$property = $xmldoc->createElement('property');
				$vrtHst->appendChild($property);
				$property->appendChild($xmldoc->createElement('name', $realField));
				$property->appendChild($xmldoc->createElement('value', $value));
				/*$property = $xmldoc->createAttribute($realField);
				$property->value = $value;
				$vrtHst->appendChild($property);*/
			}
			if (in_array($realField, $vrtHsts)) {
				if ($hostingAdded == FALSE) {
					$get->appendChild($hosting);
					$hosting->appendChild($vrtHst);
					$hostingAdded = TRUE;
				}
				$vrtHst->appendChild($xmldoc->createElement($realField, $value));
			}
			if (in_array($realField, $extra))
				$get->appendChild($xmldoc->createElement($realField, $value));
		}
		//print_r($xmldoc->saveXML());
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['add']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk createSubscription returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for delete subscription
	 *
	 * @param array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function deleteSubscription($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'webspace';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('del');
		$domain->appendChild($get);
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		$mapping = [
			'domain' => 'name',
			'owner_id' => 'owner-id',
			'owner_login' => 'owner-login',
			'ip' => 'ip_address'
		];
		$filters = $this->getSubscriptionFilters();
		foreach ($params as $field => $value) {
			if (isset($mapping[$field]))
				$realField = $mapping[$field];
			else
				$realField = $field;
			if (in_array($realField, $filters))
				$filter->appendChild($xmldoc->createElement($realField, $value));
		}
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		if (isset($result[$packetName]))
			$result = $result[$packetName]['del']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk deleteSubscription returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list subscriptions
	 *
	 * @param bool|array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listSubscriptions($params = FALSE) {
		if ($params === FALSE)
			$params = [];
		$datasets = $this->getSubscriptionDatasets();
		$filters = $this->getSubscriptionFilters();
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'webspace';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		$dataset = $xmldoc->createElement('dataset');
		$get->appendChild($dataset);
		foreach ($datasets as $field)
			$dataset->appendChild($xmldoc->createElement($field));
		foreach ($filters as $field) {
			if (isset($mapping[$field]))
				$realField = $mapping[$field];
			else
				$realField = $field;
			if (isset($params[$realField]))
				$filter->appendChild($xmldoc->createElement($realField, $params[$realField]));
			elseif (isset($params[$field]))
				$filter->appendChild($xmldoc->createElement($realField, $params[$field]));
		}
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if (isset($result['status'])) {
			if ($result['status'] == 'error')
				throw new ApiRequestException('Plesk listSubscriptions returned Error #'.$result['errcode'].' '.$result['errtext']);
		} else {
			$resultValues = array_values($result);
			foreach ($resultValues as $resultData)
				if ($resultData['status'] == 'error')
					throw new ApiRequestException('Plesk listSubscriptions returned Error #'.$resultData['errcode'].' '.$resultData['errtext']);
		}
		return $result;
	}

	/**
	 * Returns DOM object representing request for delete client
	 *
	 * @param array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function deleteClient($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'customer';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('del');
		$domain->appendChild($get);
		$mapping = [
			'username' => 'login'
		];
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		foreach ($params as $field => $value) {
			if (isset($mapping[$field]))
				$realField = $mapping[$field];
			else
				$realField = $field;
			$filter->appendChild($xmldoc->createElement($realField, $value));
		}
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		/*if (!isset($result[$packetName]['del']['result'])) {
			myadmin_log('webhosting', 'WARNING', json_encode($response), __LINE__, __FILE__);
		}*/
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		if (isset($result[$packetName]))
			$result = $result[$packetName]['del']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk deleteClient returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for delete database
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function deleteDatabase() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->deleteDatabase_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk deleteDatabase returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for delete email address
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function deleteEmailAddress() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->deleteEmailAddress_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk deleteEmailAddress returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for delete secret key
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function deleteSecretKey() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->deleteSecretKey_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk deleteSecretKey returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for delete site alias
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function deleteSiteAlias() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->deleteSiteAlias_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk deleteSiteAlias returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for delete site
	 *
	 * @param array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function deleteSite($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'site';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('del');
		$domain->appendChild($get);
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		$filters = $this->getSiteFilters();
		foreach ($filters as $field)
			if (isset($params[$field]))
				$filter->appendChild($xmldoc->createElement($field, $params[$field]));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['del']['result'];
		if (isset($result['status'])) {
			if ($result['status'] == 'error')
				throw new ApiRequestException('Plesk deleteSite returned Error #'.$result['errcode'].' '.$result['errtext']);
		} else {
			$resultValues = array_values($result);
			foreach ($resultValues as $resultData)
				if ($resultData['status'] == 'error')
					throw new ApiRequestException('Plesk deleteSite returned Error #'.$resultData['errcode'].' '.$resultData['errtext']);
		}
	}

	/**
	 * Returns DOM object representing request for delete subdomain
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function deleteSubdomain() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->deleteSubdomain_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk deleteSubdomain returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for get client
	 *
	 * @param array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function getClient($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'customer';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		$dataset = $xmldoc->createElement('dataset');
		$get->appendChild($dataset);
		$dataset->appendChild($xmldoc->createElement('gen_info'));
		$dataset->appendChild($xmldoc->createElement('stat'));
		$mapping = [
			'username' => 'login'
		];
		foreach ($params as $field => $value)
			$filter->appendChild($xmldoc->createElement((isset($mapping[$field]) ? $mapping[$field] : $field), $value));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk getClient returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for get database user
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function getDatabaseUser() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->getDatabaseUser_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk getDatabaseUser returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for get service plan
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function getServicePlan() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->getServicePlan_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk getServicePlan returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for get subdomain
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function getSubdomain() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->getSubdomain_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk getSubdomain returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for get subscription
	 *
	 * @param array $params
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function getSubscription($params) {
		return $this->listSubscriptions($params);
	}

	/**
	 * Returns DOM object representing request for get traffic
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function getTraffic() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->getTrafficTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk getTraffic returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list clients
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listClients() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'customer';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$dataset = $xmldoc->createElement('dataset');
		$dataset->appendChild($xmldoc->createElement('gen_info'));
		$dataset->appendChild($xmldoc->createElement('stat'));
		$get->appendChild($xmldoc->createElement('filter'));
		$get->appendChild($dataset);
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if (isset($result['status'])) {
			if ($result['status'] == 'error')
				throw new ApiRequestException('Plesk listClients returned Error #'.$result['errcode'].' '.$result['errtext']);
		} else {
			$resultValues = array_values($result);
			foreach ($resultValues as $resultData)
				if ($resultData['status'] == 'error')
					throw new ApiRequestException('Plesk listClients returned Error #'.$resultData['errcode'].' '.$resultData['errtext']);
		}
		return $result;
	}

	/**
	 * Returns DOM object representing request for list users
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listUsers() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'user';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$dataset = $xmldoc->createElement('dataset');
		$dataset->appendChild($xmldoc->createElement('gen-info'));
		$dataset->appendChild($xmldoc->createElement('roles'));
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		$filter->appendChild($xmldoc->createElement('all'));
		$get->appendChild($dataset);
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk listClients returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list database servers
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listDatabaseServers() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'db_server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$getName = 'get-local';
		$get = $xmldoc->createElement($getName);
		$domain->appendChild($get);
		$get->appendChild($xmldoc->createElement('filter'));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response->{$packetName}->{$getName}), TRUE);
		$result = $this->fixResult($result);
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk listDatabaseServers returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list databases
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listDatabases() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->listDatabases_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk listDatabases returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list dns records
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listDnsRecords() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->listDnsRecords_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk listDnsRecords returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list email addresses
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listEmailAddresses() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->listEmailAddresses_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk listEmailAddresses returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list ip addresses
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listIpAddresses() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'ip';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		$ips = $result['addresses']['ip_info'];
		unset($result['addresses']);
		$result['ips'] = $ips;
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk listIpAddresses returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list secret keys
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listSecretKeys() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->listSecretKeys_types();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk listSecretKeys returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list service plans
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listServicePlans() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'service-plan';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$get->appendChild($xmldoc->createElement('filter'));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result[0]['status'] == 'error')
			throw new ApiRequestException('Plesk listServicePlans returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list site aliases
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listSiteAliases() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->listSiteAliasesTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk listSiteAliases returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for list subdomains
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function listSubdomains() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->listSubdomainsTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk listSubdomains returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for rename subdomain
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function renameSubdomain() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->renameSubdomainTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk renameSubdomain returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for update client
	 *
	 * @param array $params array of update parameters
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function updateClient($params) {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'customer';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('set');
		$domain->appendChild($get);
		$filters = [
			'username'
		];
		$mapping = [
			'username' => 'login',
			'password' => 'passwd',
			'zip' => 'pcode'
		];
		$filter = $xmldoc->createElement('filter');
		$get->appendChild($filter);
		$values = $xmldoc->createElement('values');
		$get->appendChild($values);
		$info = $xmldoc->createElement('gen_info');
		$values->appendChild($info);
		foreach ($params as $field => $value) {
			if (isset($mapping[$field]))
				$realField = $mapping[$field];
			else
				$realField = $field;
			if (in_array($field, $filters))
				$filter->appendChild($xmldoc->createElement($realField, $value));
			else
				$info->appendChild($xmldoc->createElement($realField, $value));
		}
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['set']['result'];
		if (isset($result['status'])) {
			if ($result['status'] == 'error')
				throw new ApiRequestException('Plesk updateClient returned Error #'.$result['errcode'].' '.$result['errtext']);
		} else {
			$resultValues = array_values($result);
			foreach ($resultValues as $resultData)
				if ($resultData['status'] == 'error')
					throw new ApiRequestException('Plesk updateClient returned Error #'.$resultData['errcode'].' '.$resultData['errtext'], __LINE__, __FILE__);
		}
		return $result;
	}

	/**
	 * Returns DOM object representing request for update email password
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function updateEmailPassword() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->updateEmailPasswordTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk updateEmailPassword returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

	/**
	 * Returns DOM object representing request for update subdomain
	 *
	 * @return \Detain\MyAdminPlesk\DOMDocument
	 * @throws \Detain\MyAdminPlesk\ApiRequestException
	 * @throws \Detain\MyAdminPlesk\Detain\MyAdminPlesk\ApiRequestException
	 */
	public function updateSubdomain() {
		$xmldoc = new \DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = TRUE;
		$packet = $xmldoc->createElement('packet');
		$xmldoc->appendChild($packet);
		$packetName = 'server';
		$domain = $xmldoc->createElement($packetName);
		$packet->appendChild($domain);
		$get = $xmldoc->createElement('get');
		$domain->appendChild($get);
		$types = $this->updateSubdomainTypes();
		$typesKeys = array_keys($types);
		foreach ($typesKeys as $type)
			if (!in_array($type, ['certificates']))
				$get->appendChild($xmldoc->createElement($type));
		$responseText = $this->sendRequest($xmldoc->saveXML());
		$response = $this->parseResponse($responseText);
		$result = json_decode(json_encode($response), TRUE);
		$result = $this->fixResult($result);
		$result = $result[$packetName]['get']['result'];
		if ($result['status'] == 'error')
			throw new ApiRequestException('Plesk updateSubdomain returned Error #'.$result['errcode'].' '.$result['errtext']);
		return $result;
	}

}
