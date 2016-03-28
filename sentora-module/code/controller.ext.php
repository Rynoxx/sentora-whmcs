<?php

/**
 * @package sentora
 * @subpackage modules
 * @author Rynoxx (rynoxx@grid-servers.net) (Previous: knivey (knivey@botops.net))
 * @copyright knivey (knivey@botops.net)
 * @link http://www.sentora.org/
 * @license GPL (http://www.gnu.org/licenses/gpl.html)
 */

class module_controller
{

	static $complete;
	static $error;
	static $alreadyexists;
	static $badname;
	static $bademail;
	static $badpassword;
	static $userblank;
	static $emailblank;
	static $passwordblank;
	static $packageblank;
	static $groupblank;
	static $ok;
	static $edit;
	static $clientid;
	static $clientpkgid;
	static $resetform;
	
   /***********************************************************
	* Begin functions for WebPage
	***********************************************************/

   /**
	* Get our module name
	* @return string name of the current module
	*/	 
	static function getModuleName()
	{
		$module_name = ui_language::translate(ui_module::GetModuleName());
		return $module_name;
	}

   /**
	* Get our icon path + filename
	* @global type $controller
	* @return string path to the icon file
	*/	
	static function getModuleIcon()
	{
		global $controller;
		$module_icon = "modules/" . $controller->GetControllerRequest('URL', 'module') . "/assets/icon.png";
		return $module_icon;
	}

   /**
	* Get our module path
	* @global type $controller
	* @return string path to the module
	*/
	static function getModulePath()
	{
		global $controller;
		$module_path = "modules/" . $controller->GetControllerRequest('URL', 'module') . "/";
		return $module_path;
	}

   /**
	* Returns our module description
	* @return string the module description from the database (originally improted from the module.xml file). 
	*/
	static function getModuleDesc()
	{
		$message = ui_language::translate(ui_module::GetModuleDescription());
		return $message;
	}

   /**
	* Builds a 'hidden' form type which is populated with the generated token.
	* @author Bobby Allen (ballen@zpanelcp.com)
	* @return string the HTML form tag.
	*/
	static function getCSFR_Tag()
	{
		return runtime_csfr::Token();
	}
	 
   /**
	* Retrieves the current API key from the options
	* @author Bobby Allen (ballen@zpanelcp.com)
	* @return string the API-Key
	*/
	static function getCurrentAPIKey()
	{
		return ctrl_options::GetOption('apikey');
	}

   /**
	* Checks if there is a bad version notice set
	*/
	static function getBadVersionIsSet() 
	{
		$whmcs_version = self::getBadVersion();
		if($whmcs_version == 'false') {
			return false;
		} else {
			return true;
		}
	}

   /**
	* Generate form for sendemail options
	* @return string HTML FORM ITEM
	*/
	static function getSendEmailForm()
	{
		$cval = ctrl_options::GetSystemOption('whmcs_sendemail_bo');
		return ctrl_options::OuputSettingMenuField('SendEmail', 'true|false', $cval);
	}

   /**
	* Generate form for resellerviewapi option
	* @return string HTML FORM ITEM
	*/
	static function getResellerViewAPIForm()
	{
		$cval = ctrl_options::GetSystemOption('whmcs_reseller_view_api');
		return ctrl_options::OuputSettingMenuField('ResellerViewAPI', 'true|false', $cval);
	}

   /**
	* Generate form for whmcs login link
	* @return string HTML FORM ITEM
	*/
	static function getWHMCSLinkForm()
	{
		$cval = ctrl_options::GetSystemOption('whmcs_link');
		return ctrl_options::OutputSettingTextField('Link', $cval);
	}

   /**
	* Accepts admin settings form
	* @return null
	*/
	static function doUpdateSettings()
	{
		global $controller;
		runtime_csfr::Protect();
		$form = $controller->GetAllControllerRequests('FORM');
		if(!isset($form['inAdminSettings'])) {
			return false;
		}
		if(!self::getIsAdmin()) {
			return false;
		}
		
		ctrl_options::SetSystemOption('whmcs_sendemail_bo', $form['SendEmail']);
		ctrl_options::SetSystemOption('whmcs_reseller_view_api', $form['ResellerViewAPI']);
		ctrl_options::SetSystemOption('whmcs_link', $form['Link']);
		self::$Results[] = ui_sysmessage::shout(ui_language::translate("Changes to your settings have been saved successfully!"));
	}

   /**
	* Gets the version reported by WHMCS or if version matches us 'false'
	* @return string version reported by WHMCS
	*/
	static function getBadVersion()
	{
		return ctrl_options::GetSystemOption('whmcs_reported');
	}

   /**
	* Gets the link to the WHMCS installation
	* @return string The link admin defined in settings
	*/
	static function getWHMCSLink()
	{
		return ctrl_options::GetSystemOption('whmcs_link');
	}

   /**
	* Get the zip file for WHMCS module download
	* @return string path to the file
	*/
	static function getWHMCSModule()
	{
		global $controller;
		$module_icon = "modules/" . $controller->GetControllerRequest('URL', 'module') . "/assets/whmcs.zip";
		return $module_icon;
	}

   /**
	* Gets the user ID of the current user
	* @return int - the UserID of the current user
	*/
	static function getCurrentUserID()
	{
		$current_user = ctrl_users::GetUserDetail();
		return $current_user["userid"];
	}

   /**
	* Get the current version of the module
	* @return string version number
	*/
	static function getVersion()
	{
		$tags = ui_module::GetModuleXMLTags('whmcs');
		return $tags['version'];
	}

   /**
	* Check if the user has full admin access
	* @return boolean true if admin false if not
	*/
	static function getIsAdmin() {
		$user = ctrl_users::GetUserDetail();
		if($user['usergroupid'] == 1) {
			return true;
		} else {
			return false;
		}
	}

   /**
	* Check if the user can view the API key
	* @return boolean - true if user is admin or (reseller and resellers can view API key)
	*/
	static function getCanViewAPIKey()
	{
		$user = ctrl_users::GetUserDetail();
		return self::getIsAdmin() || ($user['usergroupid'] == 2 && ctrl_options::GetSystemOption('whmcs_reseller_view_api') == 'true');
	}

	static $Results = Array();

   /**
	* Gets the result of any operations we have ran
	* @return string HTML output seperated by br tags
	*/
	static function getResult()
	{
		return implode('<br />', self::$Results);
	}

   /***********************************************************
	* End functions for WebPage
	***********************************************************/

   /**
	* Sends an email to all admins noticing of mismatched whmcs - zpanelx/sentora versions
	* @global db_driver $zdbh
	* @param type $version WHMCS version detected
	* @return null
	*/
	static function sendBadVersionMail($version)
	{
		global $zdbh;
		$sversion = self::getVersion();
		$sql = 'select ac_email_vc from x_accounts where ac_group_fk = 1';
		$stmt = $zdbh->query($sql);
		$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$zpdomain = ctrl_options::GetSystemOption('zpanel_domain');
		$isSentora = empty($zpdomain);

		$phpmailer = new sys_email();
		$phpmailer->Subject = ($isSentora ? "Sentora" : "ZPanelX") . " - WHMCS version mismatch";
		$phpmailer->Body = "This Email is to warn you that the versions of your WHMCS module (version: $version)" ." and the " . ($isSentora ? "Sentora" : "ZPanelX") . " module (version: $sversion) do not match! Please correct this ASAP!";
		foreach($addresses as $email) {
			$phpmailer->AddAddress($email['ac_email_vc']);
		}
		$phpmailer->SendEmail();
	}

   /**
	* Fetches the uid for username
	* @param string $name username
	* @global db_driver $zdbh
	* @return mixed False if no user exists otherwise UID
	*/
	static function getUserId($name)
	{
		global $zdbh;
		$name = is_array($name) ? implode($name) : $name;
		$stmt = $zdbh->prepare("SELECT COUNT(*) FROM `x_accounts` WHERE `ac_user_vc`=:uname AND ac_deleted_ts is NULL");
		$stmt->bindValue(":uname", $name);
		$stmt->execute();
		$rows = $stmt->fetch(PDO::FETCH_ASSOC);
		$numrows = $rows['COUNT(*)'];
		if ($numrows == 0) {
			return false;
		} else {
			$stmt = $zdbh->prepare("SELECT `ac_id_pk` FROM `x_accounts` WHERE `ac_user_vc`=:uname AND ac_deleted_ts is NULL");
			$stmt->bindValue(":uname", $name);
			$stmt->execute();
			$rowpack = $stmt->fetch(PDO::FETCH_ASSOC);
			return $rowpack['ac_id_pk'];
		}
		return false;
	}

	/**********
	 * Our version of password_assistant
	 * no changes
	 **********/

	static function UpdatePassword($uid, $password)
	{
		global $zdbh;
		$crypto = new runtime_hash;
		$crypto->SetPassword($password);
		$randomsalt = $crypto->RandomSalt();
		$crypto->SetSalt($randomsalt);
		$secure_password = $crypto->CryptParts($crypto->Crypt())->Hash;
		$sql = $zdbh->prepare("UPDATE x_accounts SET ac_pass_vc=:secure_password, ac_passsalt_vc= :randomsalt WHERE ac_id_pk=:userid AND ac_deleted_ts is NULL");
		$sql->bindParam(':randomsalt', $randomsalt);
		$sql->bindParam(':secure_password', $secure_password);
		$sql->bindParam(':userid', $uid);
		$sql->execute();
		return true;
	}

   /**********
	* Our version of manage_clients
	* Changes:
	* Added getUserExists
	* ExecuteCreateClient - initialize $time only once
	* ExecuteCreateClient - add support for packages by textual name
	* ExecuteUpdateClient - add support for packages by textual name
	* ExecuteUpdateClient - dropped self::Enable/Disable client and just added the hooks to top/bottom
	**********/

   /**
	* Checks if a username exists in the database
	* @return bool true if username exists false if available
	*/
	static function getUserExists($username)
	{
		global $zdbh;
		$username = is_array($username) ? implode($username) : $username;
		$stmt = $zdbh->prepare("SELECT COUNT(*) FROM x_accounts WHERE ac_user_vc=:uname AND ac_deleted_ts is NULL");
		$stmt->bindValue(":uname", $username);
		$stmt->execute();
		$res = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($res['COUNT(*)'] > 0) {
			return true;
		} else {
			return false;
		}
	}

	static function ExecuteCreateClient($uid, $username, $packageid, $groupid, $fullname, $email, $address, $post, $phone, $password, $sendemail, $emailsubject, $emailbody)
	{
		global $zdbh;

		// Check for spaces and remove if found...
		$username = is_array($username) ? implode($username) : $username;
		$username = strtolower(str_replace(' ', '', $username));
		$reseller = ctrl_users::GetUserDetail($uid);

		if(!is_numeric($packageid)) $packageid=self::getPackageIdFix($packageid);

		// Check for errors before we continue...
		if (fs_director::CheckForEmptyValue(self::CheckCreateForErrors($username, $packageid, $groupid, $email, $password))) {
			$errormsg = " ";

		if(self::$alreadyexists) {
			$errormsg .= sprintf(ui_language::translate("That username is already taken (\"%s\"). "), (string)$username);
		}

		if(self::$badname) {
			$errormsg .= sprintf(ui_language::translate("That username is invalid (\"%s\"). "), (string)$username);
		}

		if(self::$badpassword) {
			$errormsg .= sprintf(ui_language::translate("That password doesn't meet the requirements (\"%s\"). "), (string)$password);
		}

		if(self::$userblank) {
			$errormsg .= sprintf(ui_language::translate("The username is empty (\"%s\"). "), (string)$username);
		}

		if(self::$emailblank) {
			$errormsg .= sprintf(ui_language::translate("The email is empty (\"%s\"). "), (string)$email);
		}

		if(self::$passwordblank) {
			$errormsg .= sprintf(ui_language::translate("The password is empty (\"%s\"). "), (string)$password);
		}

		if(self::$packageblank) {
			$errormsg .= sprintf(ui_language::translate("The package is empty (\"%s\"). "), (string)$packageid);
		}

		if(self::$groupblank) {
			$errormsg .= sprintf(ui_language::translate("The group is empty (\"%s\"). "), (string)$groupid);
		}
			return ui_language::translate("Failed the check for valid parameters. ") . $errormsg;
		}
		
		runtime_hook::Execute('OnBeforeCreateClient');

		$crypto = new runtime_hash;
		$crypto->SetPassword($password);
		$randomsalt = $crypto->RandomSalt();
		$crypto->SetSalt($randomsalt);
		$secure_password = $crypto->CryptParts($crypto->Crypt())->Hash;
		$time = time();
		
		// No errors found, so we can add the user to the database...
		$sql = $zdbh->prepare("INSERT INTO x_accounts (ac_user_vc, ac_pass_vc, ac_passsalt_vc, ac_email_vc, ac_package_fk, ac_group_fk, ac_usertheme_vc, ac_usercss_vc, ac_reseller_fk, ac_created_ts) VALUES (:username, :password, :passsalt, :email, :packageid, :groupid, :resellertheme, :resellercss, :uid, :time)");
		$sql->bindParam(':uid', $uid);
		$sql->bindParam(':time', $time);
		$sql->bindParam(':username', $username);
		$sql->bindParam(':password', $secure_password);
		$sql->bindParam(':passsalt', $randomsalt);
		$sql->bindParam(':email', $email);
		$sql->bindParam(':packageid', $packageid);
		$sql->bindParam(':groupid', $groupid);
		$sql->bindParam(':resellertheme', $reseller['usertheme']);
		$sql->bindParam(':resellercss', $reseller['usercss']);
		$sql->execute();

		// Now lets pull back the client ID so that we can add their personal address details etc...
		$numrows = $zdbh->prepare("SELECT * FROM x_accounts WHERE ac_reseller_fk=:uid ORDER BY ac_id_pk DESC");
		$numrows->bindParam(':uid', $uid);
		$numrows->execute();

		$client = $numrows->fetch();

		$address = is_array($address) ? implode($address) : $address;
		$post = is_array($post) ? implode($post) : $post;
		$phone = is_array($phone) ? implode($phone) : $phone;
		$time = time();

		$sql = $zdbh->prepare("INSERT INTO x_profiles (ud_user_fk, ud_fullname_vc, ud_group_fk, ud_package_fk, ud_address_tx, ud_postcode_vc, ud_phone_vc, ud_created_ts) VALUES (:userid, :fullname, :packageid, :groupid, :address, :postcode, :phone, :time)");
		$sql->bindParam(':userid', $client['ac_id_pk']);
		$sql->bindParam(':fullname', $fullname);
		$sql->bindParam(':packageid', $packageid);
		$sql->bindParam(':groupid', $groupid);
		$sql->bindParam(':address', $address);
		$sql->bindParam(':postcode', $post);
		$sql->bindParam(':phone', $phone);
		$sql->bindParam(':time', $time);
		$sql->execute();

		// Now we add an entry into the bandwidth table, for the user for the upcoming month.
		$sql = $zdbh->prepare("INSERT INTO x_bandwidth (bd_acc_fk, bd_month_in, bd_transamount_bi, bd_diskamount_bi) VALUES (:ac_id_pk, :date, 0, 0)");
		$date = date("Ym", time());
		$sql->bindParam(':date', $date);
		$sql->bindParam(':ac_id_pk', $client['ac_id_pk']);
		$sql->execute();

		// Lets create the client diectories
		fs_director::CreateDirectory(ctrl_options::GetSystemOption('hosted_dir') . $username);
		fs_director::SetFileSystemPermissions(ctrl_options::GetSystemOption('hosted_dir') . $username, 0777);
		fs_director::CreateDirectory(ctrl_options::GetSystemOption('hosted_dir') . $username . "/public_html");
		fs_director::SetFileSystemPermissions(ctrl_options::GetSystemOption('hosted_dir') . $username . "/public_html", 0777);
		fs_director::CreateDirectory(ctrl_options::GetSystemOption('hosted_dir') . $username . "/backups");
		fs_director::SetFileSystemPermissions(ctrl_options::GetSystemOption('hosted_dir') . $username . "/backups", 0777);

		// Send the user account details via. email (if requested)...
		if ($sendemail <> 0) {
			if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
				$protocol = 'https://';
			} else {
				$protocol = 'http://';
			}

			$zpdomain = ctrl_options::GetSystemOption('zpanel_domain');
			$domain = empty($zpdomain) ? ctrl_options::GetSystemOption('sentora_domain') : ctrl_options::GetSystemOption('zpanel_domain');

			$emailsubject = str_replace("{{username}}", $username, $emailsubject);
			$emailsubject = str_replace("{{password}}", $password, $emailsubject);
			$emailsubject = str_replace("{{fullname}}", $fullname, $emailsubject);
			$emailbody = str_replace("{{username}}", $username, $emailbody);
			$emailbody = str_replace("{{password}}", $password, $emailbody);
			$emailbody = str_replace("{{fullname}}", $fullname, $emailbody);
			$emailbody = str_replace('{{controlpanelurl}}', $protocol . $domain, $emailbody);

			$phpmailer = new sys_email();
			$phpmailer->Subject = $emailsubject;
			$phpmailer->Body = $emailbody;
			$phpmailer->AddAddress($email);
			$phpmailer->SendEmail();
		}
		runtime_hook::Execute('OnAfterCreateClient');
		self::$resetform = true;
		self::$ok = true;
		return true;
	}

	static function ExecuteUpdateClient($clientid, $package, $enabled, $group, $fullname, $email, $address, $post, $phone, $newpass)
	{
		global $zdbh;
		runtime_hook::Execute('OnBeforeUpdateClient');

		// Convert package to numerical id if needed
		if(!is_numeric($package)) $package=self::getPackageIdFix($package);

		if ($enabled == 0) {
			runtime_hook::Execute('OnBeforeDisableClient');
		}
		if ($enabled == 1) {
			runtime_hook::Execute('OnBeforeEnableClient');
		}
		if ($newpass != "") {

		// Check for password length...
		if (strlen($newpass) < ctrl_options::GetSystemOption('password_minlength')) {
			self::$badpassword = true;
			return false;
		}
		
		$crypto = new runtime_hash;
		$crypto->SetPassword($newpass);
		$randomsalt = $crypto->RandomSalt();
		$crypto->SetSalt($randomsalt);
		$secure_password = $crypto->CryptParts($crypto->Crypt())->Hash;

		$sql = $zdbh->prepare("UPDATE x_accounts SET ac_pass_vc= :newpass, ac_passsalt_vc= :passsalt WHERE ac_id_pk= :clientid");
		$sql->bindParam(':clientid', $clientid);
		$sql->bindParam(':newpass', $secure_password);
		$sql->bindParam(':passsalt', $randomsalt);
		$sql->execute();
		}
		$sql = $zdbh->prepare("UPDATE x_accounts SET ac_email_vc= :email, ac_package_fk= :package, ac_enabled_in= :isenabled, ac_group_fk= :group WHERE ac_id_pk = :clientid");
		$sql->bindParam(':email', $email);
		$sql->bindParam(':package', $package);
		$sql->bindParam(':isenabled', $enabled);
		$sql->bindParam(':group', $group);
		$sql->bindParam(':clientid', $clientid);
		$sql->execute();

		$sql = $zdbh->prepare("UPDATE x_profiles SET ud_fullname_vc= :fullname, ud_group_fk= :group, ud_package_fk= :package, ud_address_tx= :address,ud_postcode_vc= :postcode, ud_phone_vc= :phone WHERE ud_user_fk=:accountid");
		$sql->bindParam(':fullname', $fullname);
		$sql->bindParam(':group', $group);
		$sql->bindParam(':package', $package);
		$sql->bindParam(':address', $address);
		$sql->bindParam(':postcode', $post);
		$sql->bindParam(':phone', $phone);
		$sql->bindParam(':accountid', $clientid);
		$sql->execute();
			
		if ($enabled == 0) {
			runtime_hook::Execute('OnAfterDisableClient');
		}
		if ($enabled == 1) {
			runtime_hook::Execute('OnAfterEnableClient');
		}
		runtime_hook::Execute('OnAfterUpdateClient');
		self::$ok = true;
		return true;
	}

	static function CheckCreateForErrors($username, $packageid, $groupid, $email, $password = "")
	{
		global $zdbh;
		$username = is_array($username) ? implode($username) : $username;
		$username = strtolower(str_replace(' ', '', $username));

		// Check to make sure the username is not blank or exists before we go any further...
		if (!fs_director::CheckForEmptyValue($username)) {
			$sql = "SELECT COUNT(*) FROM x_accounts WHERE UPPER(ac_user_vc)=:user AND ac_deleted_ts IS NULL";
			$numrows = $zdbh->prepare($sql);
			$user = strtoupper($username);
			$numrows->bindParam(':user', $user);
			if ($numrows->execute()) {
				if ($numrows->fetchColumn() <> 0) {
					self::$alreadyexists = true;
					return false;
				}
			}
			if (!self::IsValidUserName($username)) {
				self::$badname = true;
				return false;
			}
		} else {
			self::$userblank = true;
			return false;
		}

		// Check to make sure the packagename is not blank and exists before we go any further...
		if (!fs_director::CheckForEmptyValue($packageid)) {
			$sql = "SELECT COUNT(*) FROM x_packages WHERE pk_id_pk=:packageid AND pk_deleted_ts IS NULL";
			$numrows = $zdbh->prepare($sql);
			$numrows->bindParam(':packageid', $packageid);
			if ($numrows->execute()) {
				if ($numrows->fetchColumn() == 0) {
					self::$packageblank = true;
					return false;
				}
			}
		} else {
			self::$packageblank = true;
			return false;
		}

		// Check to make sure the groupname is not blank and exists before we go any further...
		if (!fs_director::CheckForEmptyValue($groupid)) {
			$sql = "SELECT COUNT(*) FROM x_groups WHERE ug_id_pk=:groupid";
			$numrows = $zdbh->prepare($sql);
			$numrows->bindParam(':groupid', $groupid);
			if ($numrows->execute()) {
				if ($numrows->fetchColumn() == 0) {
					self::$groupblank = true;
					return;
				}
			}
		} else {
			self::$groupblank = true;
			return false;
		}

		// Check for invalid characters in the email and that it exists...
		if (!fs_director::CheckForEmptyValue($email)) {
			if (!self::IsValidEmail($email)) {
				self::$bademail = true;
				return false;
			}
		} else {
			self::$emailblank = true;
			return false;
		}

		// Check for password length...
		if (!fs_director::CheckForEmptyValue($password)) {
			if (strlen($password) < ctrl_options::GetSystemOption('password_minlength')) {
				self::$badpassword = true;
				return false;
			}
		} else {
			self::$passwordblank = true;
			return false;
		}
		return true;
	}

	static function IsValidEmail($email)
	{
		if (!preg_match('/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i', $email)) {
			return false;
		}
		return true;
	}

	static function IsValidUserName($username)
	{
		if (!preg_match('/^[a-z\d][a-z\d-]{0,62}$/i', $username) || preg_match('/-$/', $username)) {
			return false;
		}
		return true;
	}

	static function DefaultEmailBody()
	{
		$line = ui_language::translate("Hi {{fullname}},\r\rWe are pleased to inform you that your hosting account is now activated!\r\rYou can access your web hosting control panel using this link:\r{{controlpanelurl}}\r\rYour username and password is as follows:\rUsername: {{username}}\rPassword: {{password}}\r\rMany thanks,\rThe management");
		return $line;
	}

	static function getPackageIdFix($name)
	{
		global $zdbh;
		$name = is_array($name) ? implode($name) : $name;

		$stmt = $zdbh->prepare("SELECT COUNT(*) FROM `x_packages` WHERE `pk_name_vc`=:name AND pk_deleted_ts IS NULL");
		$stmt->bindValue(":name", $name);
		$stmt->execute();
		$rows = $stmt->fetch(PDO::FETCH_ASSOC);
		$numrows = $rows['COUNT(*)'];
		
		if ($numrows == 0) {
			return false;
		} else {
			$stmt = $zdbh->prepare("SELECT * FROM `x_packages` WHERE `pk_name_vc`=:name AND pk_deleted_ts IS NULL");
			$stmt->bindValue(":name", $name);
			$stmt->execute();
			$rowpack = $stmt->fetch(PDO::FETCH_ASSOC);
			return $rowpack['pk_id_pk'];
		}
		return false;
	}

	/*****************************
	 * Our version of dns_manager
	 *****************************/

	/** 
	 *
	 * NOTE: Unchanged from original.
	 * @param int $id The domain ID
	 * @return bool Returns true if dns_hasupdates is changed.
	 */
	static function TriggerDNSUpdate($id)
	{
		global $zdbh;
		global $controller;
		$records_list = ctrl_options::GetSystemOption('dns_hasupdates');
		$record_array = explode(',', $records_list);
		if (!in_array($id, $record_array)) {
			if (empty($records_list)) {
				$records_list .= $id;
			} else {
				$records_list .= ',' . $id;
			}
			$sql = "UPDATE x_settings SET so_value_tx=:newlist WHERE so_name_vc='dns_hasupdates'";
			$sql = $zdbh->prepare($sql);
			$sql->bindParam(':newlist', $records_list);
			$sql->execute();
			return true;
		}
	}

	/**
	 * Gets the ID of the specified domain name for the specified user
	 * NOTE: Unchanged from original.
	 * @param int $uid The ID of the user
	 * @param string $domainName The domainname to lookup the id for.
	 * @return int DomainID, will return 0 if the domain wasn't found.
	 */
	static function getDomainId($uid, $domainName){
		global $zdbh;

		$sql = $zdbh->prepare("SELECT * FROM x_vhosts WHERE vh_acc_fk=:uid AND vh_type_in !=2 AND vh_deleted_ts IS NULL AND vh_name_vc=:name");
		$sql->bindParam(':uid', $uid);
		$sql->bindParam(':name', $domainName);
		$sql->execute();
		$domainId = $sql->fetch();

		return empty($domainId['vh_id_pk']) ? 0 : $domainId['vh_id_pk'];
	}

	/**
	 * Creates a new DNS record from an array of key value pairs.
	 * @param array $rec Array of record properties (uid, domainName, domainID, type, hostName, ttl, target)
	 * @return void
	 */
	static function createDNSRecord(array $rec)
	{
		global $zdbh;
		$sql = $zdbh->prepare('INSERT INTO x_dns (dn_acc_fk,
						   dn_name_vc,
						   dn_vhost_fk,
						   dn_type_vc,
						   dn_host_vc,
						   dn_ttl_in,
						   dn_target_vc,
						   dn_priority_in,
						   dn_weight_in,
						   dn_port_in,
						   dn_created_ts) VALUES (
						   :userid,
						   :domainName,
						   :domainID,
						   :type_new,
						   :hostName_new,
						   :ttl_new,
						   :target_new,
						   :priority_new,
						   :weight_new,
						   :port_new,
						   :time)'
		);
		$priority_new = array_key_exists('priority', $rec) ? $rec['priority'] : 0;
		$weight_new = array_key_exists('weight', $rec) ? $rec['weight'] : 0;
		$port_new = array_key_exists('port', $rec) ? $rec['port'] : 0;
		$time = array_key_exists('time', $rec) ? $rec['time'] : time();
		$sql->bindParam(':userid', $rec['uid']);
		$sql->bindParam(':domainName', $rec['domainName']);
		$sql->bindParam(':domainID', $rec['domainID']);
		$sql->bindParam(':type_new', $rec['type']);
		$sql->bindParam(':hostName_new', $rec['hostName']);
		$sql->bindParam(':ttl_new', $rec['ttl']);
		$sql->bindParam(':target_new', $rec['target']);
		$sql->bindParam(':priority_new', $priority_new);
		$sql->bindParam(':weight_new', $weight_new);
		$sql->bindParam(':port_new', $port_new);
		$sql->bindParam(':time', $time);
		$sql->execute();
		self::TriggerDNSUpdate($rec['domainID']);
	}

	/**
	 * Creates the default DNS records for the specified UserID/DomainID combo
	 * @param $userID
	 * @param $domainID
	 * @return void
	 */
	static function createDefaultRecords($userID, $domainID){
		global $zdbh;
		global $controller;

		$numrows = $zdbh->prepare('SELECT * FROM x_vhosts WHERE vh_id_pk=:domainID AND vh_type_in !=2 AND vh_deleted_ts IS NULL');
		$numrows->bindParam(':domainID', $domainID);
		$numrows->execute();
		$domainName = $numrows->fetch();
		$domainName = $domainName['vh_name_vc'];

		if (!fs_director::CheckForEmptyValue(ctrl_options::GetSystemOption('server_ip'))) {
			$targetIP = ctrl_options::GetSystemOption('server_ip');
		} else {
			$targetIP = $_SERVER["SERVER_ADDR"]; //This needs checking on windows 7 we may need to use LOCAL_ADDR :- Sam Mottley
		}

		//Get list of DNS rows to create
		$RowCount = $zdbh->prepare('SELECT count(*) FROM x_dns_create WHERE dc_acc_fk=:userId');
		$RowCount->bindparam(':userId', $userID);
		$RowCount->execute();
		if ($RowCount->fetchColumn() > 0) {
			//The current user have specifics entries, use them only
			$CreateList = $zdbh->prepare('SELECT * FROM x_dns_create WHERE dc_acc_fk=:userId');
			$CreateList->bindparam(':userId', $userID);
			$CreateList->execute();
		} else {
			//no entry specific to this user is present, use default entries (user number = 0)
			$CreateList = $zdbh->query('SELECT * FROM x_dns_create WHERE dc_acc_fk=0');
		}

		while ($CreateItem = $CreateList->fetch()) {
			$Target = str_replace(':IP:', $targetIP, $CreateItem['dc_target_vc']);
			$Target = str_replace(':DOMAIN:', $domainName, $Target);
			$Row = array(
				'uid' => $userID,
				'domainName' => $domainName,
				'domainID' => $domainID,
				'type' => $CreateItem['dc_type_vc'],
				'hostName' => $CreateItem['dc_host_vc'],
				'ttl' => $CreateItem['dc_ttl_in'],
				'target' => $Target);
			if (!empty($CreateItem['dc_priority_in']))
				$Row['priority'] = $CreateItem['dc_priority_in'];
			if (!empty($CreateItem['dc_weight_in']))
				$Row['weight'] = $CreateItem['dc_weight_in'];
			if (!empty($CreateItem['dc_port_in']))
				$Row['port'] = $CreateItem['dc_port_in'];
			self::createDNSRecord($Row);
		}

		return;
	}
}

?>