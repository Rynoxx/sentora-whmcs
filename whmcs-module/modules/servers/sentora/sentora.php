<?php
/*
 * Edited version for Sentora:
 *	Sentora WHMCS module 1.1.2
 *	Originally written by Mathieu L�gar� (levelkro@yahoo.ca)
 *	Edits written by Rynoxx (rynoxx@grid-servers.net)
 *	Uses the PHP XMWS API Client by ballen (ballen@zpanelcp.com)
 *	Tested with WHMCS 5.1.3, Sentora 1.0 and CentOS 7
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
 *  1.1.2
 *  - Testing it in Sentora
 *	- Doing some minor changes
 *		- Editing variable names
 *		- Editing comments
 *	- Updated XMWS
 */

//	Load the PHP XMWS API Client by ballen (ballen@zpanelcp.com) 
require 'xmwsclient.class.php';

/*
  // How to send query to Sentora
  $xmws = new xmwsclient();
  $xmws->InitRequest(url,module_name,function,apikey,adminuser,adminpass);
  $xmws->SetRequestData(xml_data);
  $response_array  = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
*/

function getProtocol($params) {
	return ($params["serversecure"] ? "https://" : "http://");
}

function sentora_ConfigOptions() {
	// Option for the product
	$configarray = array(
		"package_name" => array("FriendlyName" => "Package Name", "Type" => "text", "Size" => "25", "Description" => "The name of the package in Sentora"),
		"reseller" => array("FriendlyName" => "Reseller", "Type" => "yesno", "Description" => "Yes, is a reseller"),
	);
	return $configarray;
}

function sendVersionToSentora($params) {
	$sentora_module_version = '003';
	$serverip = $params["serverip"];									# Server IP address
	$serverusername = $params["serverusername"];						# Server username
	$serverpassword = $params["serverpassword"];						# Server password
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_apikey = $serveraccesshash[1];								# Get the API Key
	$serversecure = $params["serversecure"];							# If set, SSL Mode is enabled in the server config
	
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("whmcs", "checkVersion");
	$data = "<version>" . $sentora_module_version . "</version>\n";
	$xmws->SetRequestData($data);
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$pass = $response_array['xmws']['content']['pass'];
	if($pass == 'true') {
		return true;
	}
	else {
		return false;
	}
}

function sentora_CreateAccount($params) {
	sendVersionToSentora($params);
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
	if ($configoption2 == "yes" || strpos($producttype, "reseller") >= 0){
		$groupid = "2";
	}

	// Server details
	$serverip = $params["serverip"];   # Server IP address
	$serverusername = $params["serverusername"]; # Server username
	$serverpassword = $params["serverpassword"]; # Server password
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_reseller = $serveraccesshash[0];  # Get the Reseller ID
	$server_apikey = $serveraccesshash[1];  # Get the API Key
	$serversecure = $params["serversecure"];   # If set, SSL Mode is enabled in the server config

	//CreateClient Checks if that username exists and creates it, otherwise returns a failure
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("whmcs", "CreateClient");
	$data = "<resellerid>" . $server_reseller . "</resellerid>\n";
	$data .= "<packageid>" . $configoption1 . "</packageid>\n";
	$data .= "<groupid>" . $groupid . "</groupid>\n";
	$data .= "<username>" . $username . "</username>\n";
	$data .= "<fullname>" . $clientsdetails['firstname'] . " " . $clientsdetails['lastname'] . "</fullname>\n";
	$data .= "<email>" . $clientsdetails['email'] . "</email>\n";
	$data .= "<address>" . $clientsdetails['address1'] . "</address>\n";
	$data .= "<postcode>" . $clientsdetails['postcode'] . "</postcode>\n";
	$data .= "<password>" . $password . "</password>\n";
	$data .= "<phone>" . $clientsdetails['phonenumber'] . "</phone>\n";
	$data .= "<sendmail>0</sendmail>\n";
	$data .= "<emailsubject>0</emailsubject>\n";
	$data .= "<emailbody>0</emailbody>";
	$xmws->SetRequestData($data);

	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$xmws_values = $response_array['xmws'];

	// If it returns anything except 'success' then the user already exists
	if ($xmws_values['content'] != 'success') {
		return $xmws_values['content'];
	}
	
	$result = 'success';
	
	// Now add the domain (if setup in WHMCS)
	// This is a guess but i think if no domain is set it will be null?
	if($domain != null) {
		$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
		$xmws->SetRequestType("whmcs", "getUserId");
		$data = "<username>" . $username . "</username>\n";
		$xmws->SetRequestData($data);
		$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
		$uid = $response_array['xmws']['content']['uid'];
		if(empty($uid)) {
			return "Account Created?, error getting uid for domain setup.";
		}
		
		$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
		$xmws->SetRequestType("domains", "CreateDomain");
		$data = "<uid>" . $uid . "</uid>\n";
		$data .= "<domain>" . $domain . "</domain>\n";
		$data .= "<destination> </destination>\n";
		$data .= "<autohome>1</autohome>";
		$xmws->SetRequestData($data);
		$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
		$content = $response_array['xmws']['content'];

		if ($content['created'] == "false") {
			$result = "Account created, but can't add the domain (FQDN Must not already exist).";
		}
	}

	return $result;
}

function sentora_TerminateAccount($params) {
	sendVersionToSentora($params);
	// Requested user details for the task
	$username = $params["username"];       # Username defined in the product
	// Requested server details for the task
	$serverip = $params["serverip"];       # Server IP address
	$serverusername = $params["serverusername"];    # Server username
	$serverpassword = $params["serverpassword"];    # Server password
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_apikey = $serveraccesshash[1];      # Get the API Key
	
	//Get the UID
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("whmcs", "getUserId");
	$data = "<username>" . $username . "</username>\n";
	$xmws->SetRequestData($data);
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$uid = $response_array['xmws']['content']['uid'];
	if (empty($uid)) {
		return "Error getting the UID";
	}

	// Starting to Suspend the user to zPanel
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("manage_clients", "DeleteClient");
	$data = "<uid>" . $uid . "</uid>";
	$xmws->SetRequestData($data);
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$content = $response_array['xmws']['content'];
	// If disabled return true, is done!
	if ($content['deleted'] == "true") {
		$result = "success";
	} else {
		$result = "User account is not deleted.";
	}
	return $result;
}

function sentora_SuspendAccount($params) {
	sendVersionToSentora($params);
	// Requested user details for the task
	$username = $params["username"];       # Username defined in the product
	// Requested server details for the task
	$serverip = $params["serverip"];       # Server IP address
	$serverusername = $params["serverusername"];    # Server username
	$serverpassword = $params["serverpassword"];    # Server password
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_apikey = $serveraccesshash[1];      # Get the API Key

	//Get the UID
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("whmcs", "getUserId");
	$data = "<username>" . $username . "</username>\n";
	$xmws->SetRequestData($data);
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$uid = $response_array['xmws']['content']['uid'];
	if (empty($uid)) {
		return "Error getting the UID";
	}

	// Starting to Suspend the user to zPanel
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("manage_clients", "DisableClient");
	$data = "<uid>" . $uid . "</uid>";
	$xmws->SetRequestData($data);
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$content = $response_array['xmws']['content'];
	// If disabled return true, is done!
	if ($content['disabled'] == "true") {
		$result = "success";
	} else {
		$result = "User account is not suspended.";
	}
	return $result;
}

function sentora_UnsuspendAccount($params) {
	sendVersionToSentora($params);
	// Requested user details for the task
	$username = $params["username"];       # Username defined in the product
	// Requested server details for the task
	$serverip = $params["serverip"];       # Server IP address
	$serverusername = $params["serverusername"];    # Server username
	$serverpassword = $params["serverpassword"];    # Server password
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_apikey = $serveraccesshash[1];      # Get the API Key
	
	//Get the UID
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("whmcs", "getUserId");
	$data = "<username>" . $username . "</username>\n";
	$xmws->SetRequestData($data);
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$uid = $response_array['xmws']['content']['uid'];
	if (empty($uid)) {
		return "Error getting the UID";
	}
	
	// Starting to Suspend the user to zPanel
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("manage_clients", "EnableClient");
	$data = "<uid>" . $uid . "</uid>";
	$xmws->SetRequestData($data);
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$content = $response_array['xmws']['content'];
	// If enabled return true, is done!
	if ($content['enabled'] == "true") {
		$result = "success";
	} else {
		$result = "User account is not unsuspended.";
	}
	return $result;
}

function sentora_ChangePassword($params) {
	sendVersionToSentora($params);
	// Account details
	$username = $params["username"];       # Username defined in the product
	$password = $params["password"];       # Password defined in the product
	// Server details
	$serverip = $params["serverip"];       # Server IP address
	$serverusername = $params["serverusername"];    # Server username
	$serverpassword = $params["serverpassword"];    # Server password
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_reseller = $serveraccesshash[0];      # Get the Reseller ID
	$server_apikey = $serveraccesshash[1];      # Get the API Key
	$serversecure = $params["serversecure"];      # If set, SSL Mode is enabled in the server config
	// Reset the password
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("whmcs", "ResetUserPassword");
	$data = "<username>" . $username . "</username>\n";
	$data .= "<newpassword>" . $password . "</newpassword>\n";
	$xmws->SetRequestData($data);
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$content = $response_array['xmws']['content'];
	// If reset returns true, is a success
	if ($content['reset'] == "true") {
		$result = "success";
	} else {
		$result = "Can't change the password for the user.";
	}
	return $result;
}

function sentora_ChangePackage($params) {
	sendVersionToSentora($params);
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
	$serverip = $params["serverip"];       # Server IP address
	$serverusername = $params["serverusername"];    # Server username
	$serverpassword = $params["serverpassword"];    # Server password
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_reseller = $serveraccesshash[0];      # Get the Reseller ID
	$server_apikey = $serveraccesshash[1];      # Get the API Key
	$serversecure = $params["serversecure"];      # If set, SSL Mode is enabled in the server config
	
	//Get the UID
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("whmcs", "getUserId");
	$data = "<username>" . $username . "</username>\n";
	$xmws->SetRequestData($data);
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$uid = $response_array['xmws']['content']['uid'];
	if (empty($uid)) {
		return "Error getting the UID";
	}
	
	// Starting to update account on zPanel
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("whmcs", "UpdateClient");
	$data = "<packageid>" . $configoption1 . "</packageid>\n";
	$data .= "<groupid>" . $groupid . "</groupid>\n";
	$data .= "<uid>" . $uid . "</uid>\n";
	$data .= "<fullname>" . $clientsdetails['firstname'] . " " . $clientsdetails['lastname'] . "</fullname>\n";
	$data .= "<email>" . $clientsdetails['email'] . "</email>\n";
	$data .= "<address>" . $clientsdetails['address1'] . "</address>\n";
	$data .= "<postcode>" . $clientsdetails['postcode'] . "</postcode>\n";
	$data .= "<password>" . $password . "</password>\n";
	$data .= "<phone>" . $clientsdetails['phonenumber'] . "</phone>\n";
	$xmws->SetRequestData($data);

	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$xmws_values = $response_array['xmws'];
	return $xmws_values['content'];
}

function sentora_ClientArea($params) {
	$code = '<form action="http://' . $params["serverip"] . '/" method="get" target="_blank">'
		/*<input type="hidden" name="inUsername" value="' . htmlentities($params["username"]) . '" />
        <input type="hidden" name="inPassword" value="' . htmlentities($params["password"]) . '" /> */ . '
		<input type="button" value="Login to Control Panel" onClick="window.open(\'http://' . $params['serverip'] . '/\')" />
		<input type="button" value="Login to Webmail" onClick="window.open(\'http://' . $params['serverip'] . '/etc/apps/webmail/\')" />
	</form>';
	return $code;
}

function sentora_AdminLink($params) {
	$code = '<form action="http://' . $params["serverip"] . '/" method="get" target="_blank">
		<input type="submit" value="Login to Control Panel" onClick="window.open(\'http://' . $params['serverip'] . '/\')" />
	</form>';
	return $code;
}

function sentora_LoginLink($params) {
	echo '<a href="http://' . $params["serverip"] . '/" target="_blank" style="color:#cc0000">Login to control panel</a>';
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
	$serverip = $params["serverip"];       # Server IP address
	$serverid = $params["serverid"];       # Server IP address
	$serverusername = $params["serverusername"];    # Server username
	$serverpassword = $params["serverpassword"];    # Server password
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_reseller = $serveraccesshash[0];      # Get the Reseller ID
	$server_apikey = $serveraccesshash[1];      # Get the API Key
	$serversecure = $params["serversecure"];      # If set, SSL Mode is enabled in the server config
	// Starting to update account on Sentora
	$xmws = new xmwsclient(getProtocol($params) . $serverip, $server_apikey, $serverusername, $serverpassword);
	$xmws->SetRequestType("manage_clients", "GetAllClients");
	$xmws->SetRequestData('');
	$response_array = $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	$xmws_values = $response_array['xmws'];
	$xmws_clients = $xmws_values['content']['client'];
	
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
