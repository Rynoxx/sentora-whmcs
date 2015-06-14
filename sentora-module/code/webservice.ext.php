<?php

/**
 * @package sentora
 * @subpackage modules
 * @author Rynoxx (rynoxx@grid-servers.net) (Previous: knivey (knivey@botops.net))
 * @copyright knivey (knivey@botops.net)
 * @link http://www.sentora.org/
 * @license GPL (http://www.gnu.org/licenses/gpl.html)
 */
class webservice extends ws_xmws {

	/*
	 * todo
	 * 
	 * release:
	 * 
	 * add a version check for the whmcs' module
	 *   could add a new api call to all whmcs function to tell Sentora its version
	 *   perhaps even have an option to send email to admin
	 * 
	 * add a user page that will link them to their whmcs clientarea
	 * 
	 * add a serveradmin page to provide download & instructions for setting up whmcs side
	 * 
	 * possibly add a table to track which users are setup through whmcs
	 * 
	 * Future:
	 * 
	 * add ssl support (SSL support done (but not tested)) and provide a guide on how to setup sentora for ssl
	 * 
	 * add option on whmcs for if remote logins (csfr disabled) on sentora and explain
	 *   why this is a bad idea (Warning running sentora with remote logins enabled is a security vunerability)
	 *   ^ actually might be best not to support this to discourage
	 * 
	 * test reseller support
	 */

	/**
	 * Looks for a UID matching the username provided
	 * @param string $username Username to lookup
	 * @return mixed string UID or empty Array() on failure
	 */
	function getUserId() {
		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if(!empty($ctags["whmcs_version"])){
			$this->checkVersion($ctags["whmcs_version"]);
		}

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$uid = module_controller::getUserId($ctags['username']);
		$dataobject->addItemValue('content', ws_xmws::NewXMLTag('uid', $uid));
		return $dataobject->getDataObject();
	}

	/**
	 * Checks if Sentora module version matches the module in WHMCS
	 * @param string $version WHMCS module version
	 * @return string "true" if versions match "false" otherwise
	 */
	function checkVersion($whmcs_version = null) {
		$version = module_controller::getVersion();

		if(empty($whmcs_version)){
			$request_data = $this->XMLDataToArray($this->wsdata);
			$ctags = $request_data['xmws']['content'];
			$whmcs_version = $ctags['whmcs_version'];
		}

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		if((int)$version != (int)$whmcs_version) {
			$dataobject->addItemValue('content', ws_xmws::NewXMLTag('pass', 'false'));
			//check database if this is first time
			$alreadyReported = ctrl_options::GetSystemOption('whmcs_reported');
			if ($alreadyReported == 'false') {
				//if so theWn update database
				ctrl_options::SetSystemOption('whmcs_reported', $ctags['whmcs_version']);
				//then send email to admins if possible
				$sendemail = ctrl_options::GetSystemOption('whmcs_sendemail_bo');
				if ($sendemail == 'true') {
					module_controller::sendBadVersionMail($ctags['whmcs_version']);
				}
			}
		} else {
			$alreadyReported = ctrl_options::GetSystemOption('whmcs_reported');
			if($alreadyReported != 'false') {
				ctrl_options::SetSystemOption('whmcs_reported', 'false');
			}
			$dataobject->addItemValue('content', ws_xmws::NewXMLTag('pass', 'true'));
		}
		return $dataobject->getDataObject();
	}

	/**
	 * Gets a CSRF token so that we can automaticly login from the WHMCS Client Area
	 * @return string A HTML form version of the CSRF token
	 */
	function getCSRFToken() {
		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');

		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if($ctags["auto-login-enabled"]){
			$dataobject->addItemValue('content', ws_xmws::NewXMLTag('csrf_token', htmlentities(module_controller::getCSFR_Tag())));
		}
		else{
			$dataobject->addItemValue('content', ws_xmws::NewXMLTag('csrf_token', "false"));
		}

		return $dataobject->getDataObject();
	}

	/****************
	 * Our version of manage_clients
	 ****************/
	/**
	 * Checks if <username> exists in the database
	 * Returns "true" or "false"
	 * @return type
	 */
	public function UsernameExits() {
		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if(!empty($ctags["whmcs_version"])){
			$this->checkVersion($ctags["whmcs_version"]);
		}

		$response = null;
		if (module_controller::getUserExists($ctags['username'])) {
			$response = "true";
		} else {
			$response = "false";
		}
		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$dataobject->addItemValue('content', $response);
		return $dataobject->getDataObject();
	}

	/**
	 * Checks if username is taken if not Creates a new client with data provided
	 * Accepts <resellerid> <username> <packageid> <groupid> <fullname> <email>
	 * Accepts <address> <postcode> <phone> <password> <sendemail> <emailsubject> <emailbody>
	 * @return type
	 */
	public function CreateClient() {
		$response_xml = 'unknown error';
		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if(!empty($ctags["whmcs_version"])){
			$this->checkVersion($ctags["whmcs_version"]);
		}

		//$fp = fopen("/etc/sentora/knivey.debug", 'w');
		//fputs($fp, var_export($request_data, true) . "\n\n\n" . var_export($ctags, true));
		//fclose($fp);
		$userExists = module_controller::getUserExists($ctags['username']);
		if ($userExists == false) {
			$result = module_controller::ExecuteCreateClient(
					$ctags['resellerid'],
					$ctags['username'],
					$ctags['packageid'],
					$ctags['groupid'],
					$ctags['fullname'],
					$ctags['email'],
					$ctags['address'],
					$ctags['postcode'],
					$ctags['phone'],
					$ctags['password'],
					$ctags['sendmail'],
					$ctags['emailsubject'],
					$ctags['emailbody']
				);

			if(module_controller::getUserExists($ctags['username'])){
				$response_xml = 'success';
			}
			else{
				$response_xml = "Failed to create user. Error: $result";
			}
		} else {
			$response_xml = "A user already exists with that username.";
		}
		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$dataobject->addItemValue('content', $response_xml);
		return $dataobject->getDataObject();
	}

	public function UpdateClient() {
		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if(!empty($ctags["whmcs_version"])){
			$this->checkVersion($ctags["whmcs_version"]);
		}

		$response_xml = module_controller::ExecuteUpdateClient(
				$ctags['uid'],
				$ctags['packageid'],
				'1',
				$ctags['groupid'],
				$ctags['fullname'],
				$ctags['email'],
				$ctags['address'],
				$ctags['postcode'],
				$ctags['phone']);

		if ($response_xml == true){
			$response_xml = "success";
		}
		else{
			$response_xml = empty($response_xml) ? "Can't update user." : $response_xml;
		}

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$dataobject->addItemValue('content', $response_xml);
		return $dataobject->getDataObject();
	}
}

