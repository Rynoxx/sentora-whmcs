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
	* could add a new api call to all whmcs function to tell ZPanelX it's version
	* perhaps even have an option to send email to admin
	*
	* add a user page that will link them to their whmcs clientarea
	*
	* add a serveradmin page to provide download & instructions for setting up whmcs side
	*
	* possibly add a table to track which users are setup through whmcs
	*
	* Future:
	*
	* add ssl support (SSL support done (but not tested))
	* and provide a guide on how to setup ZPanelX for ssl
	*
	* add option on whmcs for if remote logins (csfr disabled) on ZPanelX and explain
	* why this is a bad idea (Warning running ZPanelX with remote logins enabled is a security
	* vulnerability) ^ actually might be best not to support this to discourage
	*
	* test reseller support
	*/

   /**
	* Looks for a UID matching the username provided
	* @param string $username Username to lookup
	* @return mixed string UID or empty Array() on failure
	*/
	function GetUserId()
	{
		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if(!empty($ctags["whmcs_version"]))	{
			$this->checkVersion($ctags["whmcs_version"]);
		}

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$uid = module_controller::getUserId($ctags['username']);
		$dataobject->addItemValue('content', ws_xmws::NewXMLTag('uid', $uid));

		return $dataobject->getDataObject();
	}

	function GetPackageId(){
		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if(!empty($ctags["whmcs_version"]))	{
			$this->checkVersion($ctags["whmcs_version"]);
		}

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$pid = module_controller::getPackageIdFix($ctags['packagename']);
		$dataobject->addItemValue('content', ws_xmws::NewXMLTag('packageid', $pid));

		return $dataobject->getDataObject();
	}

   /**
	* Checks if ZPanelX module version matches the module in WHMCS
	* @param string $version WHMCS module version
	* @return string "true" if versions match "false" otherwise
	*/
	function CheckVersion($whmcs_version = null)
	{
		$version = module_controller::getVersion();

		if(empty($whmcs_version)) {
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
				//if so then update database
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

	/****************
	* Our version of manage_clients
	****************/
	/* REMOVE ME AS SOON AS FIX IS ADDED TO SENTORA ( https://github.com/sentora/sentora-core/pull/265 ) */
	function DeleteClient()
	{
		$request_data = $this->RawXMWSToArray($this->wsdata);
		$contenttags = $this->XMLDataToArray($request_data['content']);
		module_controller::ExecuteDeleteClient($contenttags['uid'], empty($contenttags['moveid']) ? 1 : $contenttags['moveid']);
		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$dataobject->addItemValue('content', print_r($request_data, true) .  print_r($contenttags, true) . ws_xmws::NewXMLTag('uid', $contenttags['uid']) . ws_xmws::NewXMLTag('deleted', 'true'));
		return $dataobject->getDataObject();
	}

	/**
	* Checks if username is taken if not Creates a new client with data provided
	* Accepts <resellerid> <username> <packageid> <groupid> <fullname> <email>
	* Accepts <address> <postcode> <phone> <password> <sendemail> <emailsubject> <emailbody>
	* @return type
	*/
	public function CreateClient()
	{
		$response_xml = 'unknown error';
		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if(!empty($ctags["whmcs_version"])) {
			$this->checkVersion($ctags["whmcs_version"]);
		}

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
				$ctags['sendemail'],
				$ctags['emailsubject'],
				$ctags['emailbody']
			);

			if(module_controller::getUserExists($ctags['username'])) {
				$response_xml = 'success';
			} else {
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

	public function UpdateClient()
	{
		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if(!empty($ctags["whmcs_version"]))	{
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
			$ctags['phone'],
			$ctags['password']
		);

		if ($response_xml === true)
		{
			$response_xml = "success";
		} else {
			$response_xml = empty($response_xml) ? "Can't update user." : $response_xml;
		}

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$dataobject->addItemValue('content', $response_xml);

		return $dataobject->getDataObject();
	}

	public function CheckUserEmailIsUnique(){
		$request_data = $this->XMLDataToArray($this->wsdata);
		$ctags = $request_data['xmws']['content'];

		if(!empty($ctags["whmcs_version"]))	{
			$this->checkVersion($ctags["whmcs_version"]);
		}

		$response_xml = ctrl_users::CheckUserEmailIsUnique($ctags['email']) ? "true" : "false";

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$dataobject->addItemValue('content', $response_xml);

		return $dataobject->getDataObject();
	}

	/****************
	 * Our version of dns_manager
	 ****************/
	public function CreateDefaultRecords(){
		$request_data = $this->RawXMWSToArray($this->wsdata);

		module_controller::createDefaultRecords(ws_generic::GetTagValue('uid', $request_data['content']), ws_generic::GetTagValue('domainid', $request_data['content']));

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$dataobject->addItemValue('content', ws_xmws::NewXMLTag('created', "true"));

		return $dataobject->getDataObject();
	}

	public function GetDomainId()
	{
		$request_data = $this->RawXMWSToArray($this->wsdata);

		$domainId = module_controller::getDomainId(ws_generic::GetTagValue('uid', $request_data['content']), ws_generic::GetTagValue('domain', $request_data['content']));

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$dataobject->addItemValue('content', ws_xmws::NewXMLTag('domainid', $domainId));

		return $dataobject->getDataObject();
	}

	public function UpdateDomainStatus()
	{
		$request_data = $this->RawXMWSToArray($this->wsdata);

		$enable = ws_generic::GetTagValue('enable', $request_data['content']);

		$disabled = module_controller::ExecuteUpdateDomainStatus(ws_generic::GetTagValue('uid', $request_data['content']), ws_generic::GetTagValue('domainid', $request_data['content']), $enable);

		$dataobject = new runtime_dataobject();
		$dataobject->addItemValue('response', '');
		$dataobject->addItemValue('content', ws_xmws::NewXMLTag('disabled', (bool)$enable < 1 ? "true" : "false"));

		return $dataobject->getDataObject();
	}
}

?>
