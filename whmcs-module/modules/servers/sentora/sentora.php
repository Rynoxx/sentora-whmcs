<?php
/*
 * Edited version for Sentora:
 *	Sentora WHMCS module 1.1.2
 *	Originally written by Mathieu L�gar� (levelkro@yahoo.ca)
 *	Edits written by Rynoxx (rynoxx@grid-servers.net)
 *	Uses the PHP XMWS API Client by ballen (ballen@zpanelcp.com)
 *	Tested with WHMCS 5.3.13 & 5.3.14, Sentora 1.0 and CentOS 7
 *
 *
 * Original: THIS CAN BE IGNORED, UNLESS YOU WANT TO KNOW ABOUT THE PREVIOUS AUTHOR(S)
 *	zPanel WHMCS module 1.0.1
 *	Writted by Mathieu L�gar� (levelkro@yahoo.ca)
 *	Use the PHP XMWS API Client by ballen (ballen@zpanelcp.com)
 *	Fixed for version zPanel 10.0.1 CentOS Linux
 *	Tested with WHMCS 5.1.2 and Linux CentOS 6.3
 *	Read the readme file for more details
 * 	
 *
 *
 * TODO:
 *
 *	If a future version of zpanel/sentora provides the features we have setup/fixed in our module
 *	we can just query the uid for $username and use the core_modules provided in zpanel
 *
 * 	Last Changes
 * 	1.0
 * 	- First Release
 * 	1.1
 * 	- Fix Control panel link
 * 	- Fix error message
 * 	- Added Change Password
 * 	- Added Change Package
 *  1.2
 *  - Testing it in Sentora
 *	- Doing some minor changes
 *		- Editing variable names
 *		- Editing comments
 *	- Updated XMWS
 *	1.3
 *	- Changed API to "Senitor"
 *	- Added API key to WHMCS module
 *	- Allowing Sentora theme to change the Icon of the WHMCS module section (In Sentora)
 *		- Credits & Source: Ron-e https://github.com/sentora/sentora-core/commit/b88b1295db03cff536b33eebb865f0fa69e783ce
 *	1.3.1
 *	- Fixed UsageUpdate, forgot to make it use Senitor instead of the old XMWS API
 *	- Changed version, now the version will be more correct in relation to semantic versioning [SemVer.org](http://semver.org)
 *
 *	1.3.2
 *	- Fixed the module.zpm to match the proper HTML structure
 *	- Changed default icon size to 35
 *
 *	1.3.3
 *	- Updated the module page
 *		- Using the new Sentora notice manager for the warning message and the "Settings updated" message
 *		- Using the proper button classes for the buttons
 *
 *	1.3.4
 *	- Added support for the WHMCS debugging system
 *
 * 	1.3.5
 *	- Fixed version warning message
 *	- Fixed created users not being put into the right usergroup (Reseller, not reseller)
 *	- Fixed domains not being created in Sentora
 *	1.3.6
 *	- Fixed various bugs occuring on windows
 *	- Added the ability to configure the use of the default Sentora modules instead of the WHMCS in some cases, for API calls.
 *		- Can be configured per package
 *
 *	1.3.7
 *	- Fixed the deploy script(s) to work on Windows as well.
 *	- Fixed errors when using windows, the user is now properly created
 *	- Improved error messages when creating users.
 *
 *	1.3.8
 *	- Fixed a few more errors on windows
 *	- Removed the ability to configure whether or not to use default Sentora modules in some situations
 *	- Increased usage of default Sentora modules when using API calls
 *
 */

// Attempted:	* - Enable auto-login from the WHMCS client area (Will add configuration options for this)
//					Currently the CSRF Token is invalid, even if gotten from the server through API.

//	Load the Senitor by ballen (https://github.com/bobsta63/senitor)
require_once 'lib/senitor/vendor/autoload.php';
use Ballen\Senitor\SenitorFactory;
use Ballen\Senitor\Entities\MessageBag;
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

/*
	// How to send query to Sentora
	$response = sendSenitorRequest($params, $module, $endpoint, $array_data);
*/

$xmws = null;

function getModuleVersion(){
	return '138';
}

function getProtocol($params) {
	return ($params["serversecure"] ? "https://" : "http://");
}

function getAddress($params){
	$protocol = getProtocol($params);
	$url = empty($params['serverhostname']) ? $params['serverip'] : $params['serverhostname'];

	return $protocol . $url;
}

function getUserID($params){
	$response = sendSenitorRequest($params, "whmcs", "getUserId", ["username" => $params["username"]]);

	$resp_arr = $response->asArray();
	$uid = $resp_arr["uid"];

	return $uid;
}

function sendSenitorRequest($params, $module, $endpoint, $array_data = array()){
	global $xmws;
	global $default_modules;

	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_apikey = $serveraccesshash[1]; # Get the API Key

	$resp = null;

	if($xmws == null){
		$xmws = SenitorFactory::create(getAddress($params), $server_apikey, $params["serverusername"], $params["serverpassword"], ['verify' => false]);
	}

	try{
		// Workaround for an exception caused by having multiple Senitor requests per PHP page.
		MessageBag::getInstance()->reset();
	}
	catch(Exception $e){ }

	$replacevars = array("serveraccesshash", "serverusername", "serverpassword", "password");

	$use_default_modules = $params["configoption3"] === "on" || $params["configoption3"] === "yes";

	if($use_default_modules && !empty($default_modules[$module . "." . $endpoint])){
		$module = $default_modules[$module . "." . $endpoint];
	}

	try{
		$xmws->setModule($module);
		$xmws->setEndpoint($endpoint);
		$xmws->SetRequestData($array_data);

		$resp = $xmws->send();
	}
	catch(Exception $e){
		$str_error = "Caught exception: " . $e->getMessage() . "\n\n" . $e->getTraceAsString() . "\n";

		logModuleCall("Sentora", $module . "." . $endpoint, $array_data, $str_error, "", $replacevars);

		return null;
	}

	logModuleCall("Sentora", $module . "." . $endpoint, $array_data, $resp->asArray(), "", $replacevars);

	return $resp;
}

function sentora_ConfigOptions() {
	// Option for the product
	$configarray = array(
		"package_name" => array("FriendlyName" => "Package Name", "Type" => "text", "Size" => "25", "Description" => "The name of the package in Sentora"),
		"reseller" => array("FriendlyName" => "Reseller", "Type" => "yesno", "Description" => "Yes, is a reseller")
	);

	return $configarray;
}

function sendVersionToSentora($params) {
	$array_data = array("whmcs_version" => getModuleVersion());

	$response = sendSenitorRequest($params, "whmcs", "checkVersion", $array_data);

	if($response != null){
		return $response->asString() == "true";
	}
	else{
		return false;
	}

	//logModuleCall("Sentora", $response->asString(), "", $response->asString(), $response->asString(), "");
}

function sentora_CreateAccount($params) {
	// Create account, used by the automation system and manual button
	// Account details
	$producttype = $params["producttype"];   # Product Type: hostingaccount, reselleraccount, server or other
	$domain = $params["domain"];   # Domain defined in the product
	$username = $params["username"];  # Username defined in the product
	$password = $params["password"];  # Password defined in the product
	$clientsdetails = $params["clientsdetails"];  # Array of clients details - firstname, lastname, email, country, etc...
	// Product option
	$configoption1 = $params["configoption1"];  # Package name
	$configoption2 = $params["configoption2"];  # If is Reseller
	$groupid = "3"; # Default to Client no need to have an else statement for this.
	if ($configoption2 === "on" || stripos($producttype, "reseller") !== false){
		$groupid = "2";
	}

	// Server details
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_reseller = $serveraccesshash[0];  # Get the Reseller ID

	//CreateClient Checks if that username exists and creates it, otherwise returns a failure
	$data = array(
		"resellerid" => $server_reseller,
		"packageid" => $configoption1,
		"groupid" => $groupid,
		"username" => $username,
		"fullname" => $clientsdetails['firstname'] . " " . $clientsdetails['lastname'],
		"email" => $clientsdetails["email"],
		"address" => $clientsdetails["address1"],
		"postcode" => $clientsdetails["postcode"],
		"password" => $password,
		"phone" => $clientsdetails["phonenumber"],
		"sendmail" => 0,
		"emailsubject" => 0,
		"emailbody" => 0
	);

	$response = null;

	$response = sendSenitorRequest($params, "whmcs", "CreateClient", $data);

	// If it returns anything except 'success' then the user already exists
	if($response == null){
		return "Account couldn't be created, the API Request failed.";
	}

	if ($response->asString() != 'success') {
		return $response->asString();
	}

	$response = null;

	$result = 'success';

	// Now add the domain (if setup in WHMCS)
	// This is a guess but i think if no domain is set it will be null?
	if($domain != null) {
		$uid = getUserID($params);

		if(empty($uid)) {
			return "Account Created?, error getting uid for domain setup.";
		}

		$response = sendSenitorRequest($params, "domains", "CreateDomain",
			[
				"uid" => $uid,
				"domain" => $domain,
				"destination" => " ",
				"autohome" => 1
			]);

		$content = $response->asArray();

		if ($content['created'] == "false") {
			$result = "Account created, but can't add the domain (FQDN Must not already exist).";
		}
	}

	return $result;
}

function sentora_TerminateAccount($params) {
	//Get the UID
	$uid = getUserID($params);

	if (empty($uid)) {
		return "Error getting the UID";
	}

	// Server details
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_reseller = $serveraccesshash[0];  # Get the Reseller ID

	// Starting to Terminate the user to Sentora
	$response = sendSenitorRequest($params, "manage_clients", "DeleteClient", ["uid" => $uid, "moveid" => $server_reseller]);

	$content = $response->asArray();
	// If disabled return true, is done!
	if ($content['deleted'] == "true") {
		$result = "success";
	} else {
		$result = "User account is not deleted.";
	}

	return $result;
}

function sentora_SuspendAccount($params) {
	//Get the UID
	$uid = getUserID($params);

	if (empty($uid)) {
		return "Error getting the UID";
	}

	// Starting to Suspend the user to Sentora
	$response = sendSenitorRequest($params, "manage_clients", "DisableClient", ["uid" => $uid]);

	$content = $response->asArray();

	// If disabled return true, is done!
	if ($content['disabled'] == "true") {
		$result = "success";
	} else {
		$result = "User account is not suspended.";
	}

	return $result;
}

function sentora_UnsuspendAccount($params) {
	// sendVersionToSentora($params);

	//Get the UID
	$uid = getUserID($params);

	if (empty($uid)) {
		return "Error getting the UID";
	}

	// Starting to Suspend the user to Sentora
	$response = sendSenitorRequest($params, "manage_clients", "EnableClient", ["uid" => $uid]);

	$content = $response->asArray();

	// If enabled return true, is done!
	if ($content['enabled'] == "true") {
		$result = "success";
	} else {
		$result = "User account is not unsuspended.";
	}

	return $result;
}

function sentora_ChangePassword($params) {
	// sendVersionToSentora($params);

	$uid = getUserID($params);

	if (empty($uid)) {
		return "Error getting the UID";
	}

	// Reset the password
	$response = sendSenitorRequest($params, "password_assistant", "ResetUserPassword", [
			"uid" => $uid,
			"newpassword" => $params["password"]
		]);

	$content = $response->asArray();

	// If reset returns true, is a success
	if ($content['reset'] == "true") {
		$result = "success";
	} else {
		$result = "Can't change the password for the user.";
	}

	return $result;
}

function sentora_ChangePackage($params) {
	// sendVersionToSentora($params);
	// Create account, used by the automation system and manual button
	// Account details
	$producttype = $params["producttype"];       # Product Type: hostingaccount, reselleraccount, server or other
	$domain = $params["domain"];        # Domain defined in the product
	$username = $params["username"];       # Username defined in the product
	$password = $params["password"];       # Password defined in the product
	$clientsdetails = $params["clientsdetails"];     # Array of clients details - firstname, lastname, email, country, etc...
	// Product option
	$configoption1 = $params["configoption1"];     # Package name
	$configoption2 = $params["configoption2"];     # If is Reseller
	$groupid = "3"; # Default to Client no need to have an else statement for this.
	if ($configoption2 == "yes" || strpos($producttype, "reseller") >= 0){
		$groupid = "2";
	}

	// Server details
	$serverusername = $params["serverusername"];    # Server username
	$serverpassword = $params["serverpassword"];    # Server password
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_reseller = $serveraccesshash[0];      # Get the Reseller ID
	$server_apikey = $serveraccesshash[1];      # Get the API Key

	//Get the UID
	$uid = getUserID($params);

	if (empty($uid)) {
		return "Error getting the UID";
	}

	// Starting to update account on Sentora
	$data = [
		"packageid" => $configoption1,
		"groupid" => $groupid,
		"uid" => $uid,
		"fullname" => $clientsdetails['firstname'] . " " . $clientsdetails['lastname'] or "",
		"email" => $clientsdetails['email'] or "",
		"address" => $clientsdetails['address1'] or "",
		"postcode" => $clientsdetails['postcode'] or "",
		"password" => $password or "",
		"phone" => $clientsdetails['phonenumber'] or ""
	];

	$response = sendSenitorRequest($params, "whmcs", "UpdateClient", $data);

	$response_array = $response->asArray();
	return $response_array;
}

function sentora_ClientArea($params) {
	sendVersionToSentora($params);
	
	/*$response = sendSenitorRequest($params, "whmcs", "getCSRFToken", ["auto-login-enabled" => $params["configoption3"] == "on"]);

	$arr = $response->asArray();*/

	$code = '<form action="' . getAddress($params) . '/" method="post" target="_blank" name="sentoraform">' . /*
		'<input type="hidden" name="inUsername" value="' . htmlentities($params["username"]) . '" />' .
		'<input type="hidden" name="inPassword" value="' . htmlentities($params["password"]) . '" />' . 
		$arr["csrf_token"] . //*/
		'<input type="button" onClick="window.open(\'' . getAddress($params) . '/\')" value="Login to Control Panel" />
		<input type="button" value="Login to Webmail" onClick="window.open(\'' . getAddress($params) . '/etc/apps/webmail/\')" />
	</form>';

	return $code;
}

function sentora_AdminLink($params) {
	$code = '<form action="' . getAddress($params) . '/" method="get" target="_blank">
		<input type="submit" value="Login to Control Panel" onClick="window.open(\'' . getAddress($params) . '/\')" />
	</form>';

	return $code;
}

function sentora_LoginLink($params) {
	echo '<a href="' . getAddress($params) . '/" target="_blank" style="color:#cc0000">Login to Control Panel</a>';
}

function sentora_reboot($params) {
	// Is not a VPS or dedicated control panel
	return "Not available with Sentora";
}

function sentora_shutdown($params) {
	// Is not a VPS or dedicated control panel
	return "Not available with Sentora";
}

function sentora_ClientAreaCustomButtonArray() {
	return "Not available with Sentora";
}

function sentora_AdminCustomButtonArray() {
	return "Not available with Sentora";
}

function sentora_extrapage($params) {
	return "Not available with Sentora";
}

function sentora_UsageUpdate($params) {
	sendVersionToSentora($params);
	// Server details

	$response = sendSenitorRequest($params, "manage_clients", "GetAllClients", []); // $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$xmws_values = $response->asArray();
	$xmws_clients = $xmws_values['client'];

	/*
	 * NOTICE In the whmcs api doc disklimit is shown as dislimit in mysql it is really disklimit
	 * also diskused is really diskusage
	 * also bwused is really bwusage
	 * 
	 * not sure if these are changes from another whmcs version
	 *  but i'm using the latest whmcs and thats what they are now
	 * 
	 * All values should be in MB
	 */
	foreach ($xmws_clients as $xmws_client) {
		update_query("tblhosting", array(
			"diskusage" => (int)convertToMBytes($xmws_client['diskspacereadable']),
			"disklimit" => (int)convertToMBytes($xmws_client['diskspacequotareadable']),
					"bwusage" => (int)convertToMBytes($xmws_client['bandwidthreadable']),
					"bwlimit" => (int)convertToMBytes($xmws_client['bandwidthquotareadable']),
					"lastupdate" => "now()",
				), array("server" => $serverid, "username" => $xmws_client['username']));
	}
}

function convertToMBytes($from) {
	$number = substr($from, 0, -2);
	switch (strtoupper(substr($from, -2))) {
		case "KB":
			return (float)($number / 1024);
		case "MB":
			return $number;
		case "GB":
			return $number * 1024;
		case "TB":
			return $number * pow(1024, 2);
		case "PB":
			return $number * pow(1024, 3);
		default:
			return $from;
	}
}

function sentora_AdminServicesTabFields($params) {
	return "Not available with Sentora";
}

function sentora_AdminServicesTabFieldsSave($params) {
	return "Not available with Sentora";
}
