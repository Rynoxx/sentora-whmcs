<?php
/*
 * Edited version for Sentora:
 *	Sentora WHMCS module
 *	Originally written by Mathieu Légaré (levelkro@yahoo.ca)
 *	Edits written by Rynoxx (rynoxx@grid-servers.net)
 *	Uses the PHP XMWS API Client by ballen (ballen@zpanelcp.com)
 *	Tested with WHMCS 5.3.13, 5.3.14, 6.1 & 6.2, Sentora 1.0 - 1.0.3 and CentOS 7/Debian 8
 *
 *
 * Original: THIS CAN BE IGNORED, UNLESS YOU WANT TO KNOW ABOUT THE PREVIOUS AUTHOR(S)
 *	zPanel WHMCS module 1.0.1
 *	Writted by Mathieu Légaré (levelkro@yahoo.ca)
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
 * 1.3.9
 * - Fixed some compatability issues with PHP 5.3
 * - Removed some unneeded files from the dependencies of the Senitor API (documentation files, files for testing the dependencies)
 *
 * 1.3.10
 * - Added the ability to choose whether or not resellers can view the API key
 * - Some style edits to the module.zpm file (module page)
 * - Changed the module numbering to allow 2 digit numbers in versions
 *
 * 2.3.1
 * - Bumped version to 2.3.1 to match the version (plus one, due to updates) of AWServer ZPanelX version of the plugin.
 * - Included some ZPanelX compatability updates from MarkDark [Source](http://forums.sentora.org/showthread.php?tid=1563&pid=12786#pid12786)
 * - Changes to the ZPanelX compatability updates to ensure a more "neutral" use of ZPanelX/Sentora in comments while showing the one which is relevant for the currently installed panel.
 * - Translation updates.
 * 
 * 2.3.2
 * - Fixed #2
 *
 * 2.3.3
 * - Allows the option to automatically create default DNS records upon account creation
 * - Note added to the 'Reseller' option.
 * - Misc small changes.
 *
 * 2.3.4
 * - Extending the error messages further for certain cases (no server configured for the product/order and no IP/Hostname assigned to a server)
 * - Put some debug code behind "If Debug" statements
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
	return '234';
}

function sentora_ConfigOptions() {
$custom_username_help = <<<'HTML'
<div id='custom_username_help' class='modal fade' role='dialog'>
	<div class="modal-dialog">
		<!-- help content -->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Custom Username Generation Help</h4>
			</div>
			<div class="modal-body">
				<p>
					This field can be used to generate custom usernames replacing the default ones. You can use variables in a smarty-like way (<code>{$variablename}</code>) to insert variable values into the username.<br />
					Example: <code>cp{$serviceid}</code> would result in the username <code>cp5</code> on a service with the ID 5, <code>cp{pad($serviceid, 4, '0')}</code> would result in <code>cp0005</code> if ran on a service with the ID 5.
				</p>

				<h5>Available functions:</h5>
				<ul>
					<li><code>pad(String $Input, int $Pad_Length, String $Padding)</code> - (NOT IMPLEMENTED YET, BUT IS PLANNED) Adds a padding to the left of the string, e.g. <code>pad($serviceid, 4, 0)</code> will result in <code>0004</code>, this uses <a href="http://php.net/manual/en/function.str-pad.php">str_pad</a> (although omitting the last argument to only use left padding) click the link for more info.</li>
				</ul>

				<br />

				<h5>Available variables:</h5>
				<ul>
					<li><code>serviceid</code> - The unique ID of the service/order</li>
					<li><code>userid</code> - The unique ID of the client that owns the service</li>
					<li><code>serverid</code> - The server ID that the service is assigned to (zero if no server assigned)</li>
					<li><code>serverip</code> - The IP Address of the sentora server</li>
					<li><code>domain</code> - The domain assigned to the product/service (can be empty)</li>
					<li><code>domain_short</code> - The domain assigned to the product/service, without dots and only the first eight (8) characters (can be empty)</li>
					<li><code>firstname</code> - The firstname of the client</li>
					<li><code>lastname</code> - The lastname of the client</li>
					<li><code>fullname</code> - First Name + Last Name</li>
					<li><code>companyname</code> - The company name associated with the client (can be empty)</li>
					<li><code>email</code> - The email address associated with the client</li>
					<li><code>country</code> - 2 Letter ISO Country Code</li>
					<li><code>groupid</code> - Client Group ID</li>
				</ul>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
HTML;

	// Option for the product
	$configarray = array(
		"package_name" => array(
			"FriendlyName" => "Package Name",
			"Type" => "text",
			"Size" => "25",
			"Description" => "The name of the package in Sentora"
		),
		"reseller" => array(
			"FriendlyName" => "Reseller",
			"Type" => "yesno",
			"Description" => "Yes, is a reseller (can be ignored if you've set the product type to reseller). <br>This will give the people who buy this package reseller access to sentora.<br>Leave this unticked if you're unsure.",
			"Default" => "off"
		),
		"autocreate_dns" => array(
			"FriendlyName" => "Auto-Create DNS Records",
			"Type" => "yesno",
			"Description" => "Yes, automatically create default domain records.<br>(This is required if you want your clients to be able to use your nameservers without them manually creating the default records)",
			"Default" => "on"
		),
		"custom_username" => array(
			"FriendlyName" => "Custom Username Generation",
			"Type" => "text",
			"Description" => "Leave empty to disable, click <a data-toggle='modal' data-target='#custom_username_help' href='#'>HERE</a> for more info." . $custom_username_help,
		)
	);

	return $configarray;
}

function getProtocol($params) {
	return ($params["serversecure"] ? "https://" : "http://");
}

function getServerHostname($params){
	return empty($params['serverhostname']) ? $params['serverip'] : $params['serverhostname'];
}

function getAddress($params){
	return getProtocol($params) . getServerHostname($params);
}

function getUserID($params){
	$response = sendSenitorRequest($params, "whmcs", "GetUserId", array("username" => $params["username"]));

	if(!empty($response)){
		$resp_arr = $response->asArray();

		return $resp_arr["uid"];
	}
	else{
		return 0;
	}
}

function getDomainID($params, $userid, $domain){
	$response = sendSenitorRequest($params, "whmcs", "GetDomainId", array("uid" => $userid, "domain" => $domain));

	if(!empty($response)){
		$resp_arr = $response->asArray();

		return $resp_arr["domainid"];
	}
	else{
		return 0;
	}
}

function sendSenitorRequest($params, $module, $endpoint, $array_data = array()){
	global $xmws;
	global $default_modules;

	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_apikey = $serveraccesshash[1]; # Get the API Key
	$debug = false;

	if($debug){
		$replacevars = array(); # The array should ONLY be empty when debugging very thoroughly.
	}
	else{
		$replacevars = array($server_apikey, $params["serveraccesshash"], $params["serverusername"], $params["serverpassword"], $params["password"], $params["clientsdetails"]["phonenumber"]);
	}

	if(empty($params["server"]) || empty(getServerHostname($params))){
		$logOutput = $params["server"] ? (
				"The server has no IP or hostname configured." . 
				"\nServer ID:" . $params["serverid"] .
				"\nServer Secure: " . $params["serversecure"] .
				"\nServer Hostname: " . $params["serverhostname"] .
				"\nServer IP: " . $paramas["serverip"] .
				"\nProtocol: " . getProtocol($params) .
				"\nAddress: " . getAddress($params)
			) : "There's no server associated with this order." . (
				"\nServer ID:" . $params["serverid"] .
				"\nServer Secure: " . $params["serversecure"] .
				"\nServer Hostname: " . $params["serverhostname"] .
				"\nServer IP: " . $paramas["serverip"] .
				"\nProtocol: " . getProtocol($params) .
				"\nAddress: " . getAddress($params)
			);

		logModuleCall("Sentora", $module . "." . $endpoint, $array_data, $logOutput, "", $replacevars);
		return null;
	}

	if($xmws == null){
		$xmws = SenitorFactory::create(getAddress($params), $server_apikey, $params["serverusername"], $params["serverpassword"], array('verify' => false));
	}

	try{
		// Workaround for an exception caused by having multiple Senitor requests per PHP page.
		MessageBag::getInstance()->reset();
	}
	catch(Exception $e){
		$str_error = "Caught exception: " . $e->getMessage() . "\n\n" . $e->getTraceAsString() . "\n";

		logModuleCall("Sentora", $module . "." . $endpoint, $array_data, $str_error, "", $replacevars);

		return null;
	}

	$use_default_modules = $params["configoption3"] === "on" || $params["configoption3"] === "yes";

	if($use_default_modules && !empty($default_modules[$module . "." . $endpoint])){
		$module = $default_modules[$module . "." . $endpoint];
	}

	if($debug){ # Even more debugging, enables verbose output of the Senitor library
		$xmws->debugMode();
		ob_start();
	}

	$resp = null;

	try{
		$xmws->setModule($module);
		$xmws->setEndpoint($endpoint);
		$xmws->SetRequestData($array_data);

		$resp = $xmws->send();
	}
	catch(Exception $e){
		$str_error = "Caught exception: " . $e->getMessage() . "\n\n" . $e->getTraceAsString();

		if($debug){
			$str_error .= PHP_EOL . "Debug string from Senitor: " . ob_get_clean();
		}

		$str_error .= PHP_EOL . " " . print_r(expression);

		logModuleCall("Sentora", $module . "." . $endpoint, $array_data, $str_error, "", $replacevars);

		return null;
	}

	logModuleCall("Sentora", $module . "." . $endpoint, $array_data, print_r($resp->asArray(), true), "", $replacevars);

	return $resp;
}

function sendVersionToSentora($params) {
	$array_data = array("whmcs_version" => getModuleVersion());

	$response = sendSenitorRequest($params, "whmcs", "CheckVersion", $array_data);

	if($response != null){
		return $response->asString() == "true";
	}
	else{
		return false;
	}

	//logModuleCall("Sentora", $response->asString(), "", $response->asString(), $response->asString(), "");
}

function sentora_CreateAccount($params) {
	if(empty($params["server"])){
		return "There's no server assigned to the order...";
	}

	if(empty(getServerHostname($params))){
		return "There's no hostname or IP assigned to the server (ID: " . $params["serverid"] . ")";
	}

	// Create account, used by the automation system and manual button
	// Account details
	$producttype = $params["producttype"];   # Product Type: hostingaccount, reselleraccount, server or other
	$domain = $params["domain"];   # Domain defined in the product
	$username = empty($params["username"]) ? substr(str_replace(".", "", $domain), 0, 49) : $params["username"];  # Username defined in the product, default to domainname if no username is defined.
	$password = $params["password"];  # Password defined in the product
	$clientsdetails = $params["clientsdetails"];  # Array of clients details - firstname, lastname, email, country, etc...
	// Product option
	$groupid = "3"; # Default to Client no need to have an else statement for this.
	if ($params["configoption2"] === "on" || stripos($producttype, "reseller") !== false){
		$groupid = "2";
	}

	if(empty($username)){
		return "No username is defined for '" . $clientsdetails['firstname'] . " " . $clientsdetails['lastname'] . "'.";
	}

	// Server details
	$serveraccesshash = explode(",", $params["serveraccesshash"]);
	$server_reseller = $serveraccesshash[0];  # Get the Reseller ID

	//CreateClient Checks if that username exists and creates it, otherwise returns a failure
	$data = array(
		"resellerid" => $server_reseller,
		"packageid" => $params["configoption1"],
		"groupid" => $groupid,
		"username" => $username,
		"fullname" => $clientsdetails['firstname'] . " " . $clientsdetails['lastname'],
		"email" => $clientsdetails["email"],
		"address" => $clientsdetails["address1"],
		"postcode" => $clientsdetails["postcode"],
		"password" => $password,
		"phone" => $clientsdetails["phonenumber"],
		"sendmail" => "0",
		"emailsubject" => "0",
		"emailbody" => "0"
	);

	$response = null;

	$response = sendSenitorRequest($params, "whmcs", "CreateClient", $data);
	#$response = sendSenitorRequest($params, "manage_clients", "CreateClient", $data);

	// If it returns anything except 'success' then the user already exists
	if ($response == null) {
		return "Account couldn't be created, the API Request failed.";
	}

	$stringResponse = $response->asString();

	if (!empty($stringResponse) && ($stringResponse != 'success' || $stringResponse != 'true')) {
		if ($response->asString() == "false") {
			$usernameExists = sendSenitorRequest($params, "manage_clients", "UsernameExists", array("username" => $username));

			if (!empty($usernameExists) && $usernameExists->asString() == "true") {
				return "Account couldn't be created, an account with that username already exists.";
			}
			else{
				return "Account couldn't be created.";
			}
		}
		else{
			return $response->asString();
		}
	}

	$response = null;

	// Now add the domain (if setup in WHMCS)
	if(!empty($domain)) {
		$uid = getUserID($params);

		if(empty($uid)) {
			return "Account Created? Error getting user id for domain setup.";
		}

		$domainResponse = sendSenitorRequest($params, "domains", "CreateDomain",
			array(
				"uid" => $uid,
				"domain" => $domain,
				"destination" => " ",
				"autohome" => 1
			)
		);

		$content = $domainResponse->asArray();

		if ($content['created'] == "false") {
			return "Account created, but couldn't add the domain (FQDN Must not already exist on the Sentora server).";
		}

		if($params["configoption3"] === "on"){
			$domainid = getDomainID($params, $uid, $params["domain"]);

			if(empty($domainid)){
				return "Account and domain created? Error getting domain id for DNS setup.";
			}

			$dnsRecordsResponse = sendSenitorRequest($params, "whmcs", "CreateDefaultRecords",
				array(
					"uid" => $uid,
					"domainid" => $domainid
				)
			);
		}
	}

	return "success";
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
	$response = sendSenitorRequest($params, "manage_clients", "DeleteClient", array("uid" => $uid, "moveid" => $server_reseller));

	if(empty($response)){
		$result = "Failed to delete the client.";
	}
	else{
		$content = $response->asArray();
		// If deleted return true, is done!
		if ($content['deleted'] == "true") {
			$result = "success";
		} else {
			$result = "Failed to delete the client.";
		}
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
	$response = sendSenitorRequest($params, "manage_clients", "DisableClient", array("uid" => $uid));

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
	$response = sendSenitorRequest($params, "manage_clients", "EnableClient", array("uid" => $uid));

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
	$response = sendSenitorRequest($params, "password_assistant", "ResetUserPassword", array(
			"uid" => $uid,
			"newpassword" => $params["password"]
		));

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
	$groupid = "3"; # Default to Client no need to have an else statement for this.
	if ($params["configoption2"] == "yes" || strpos($producttype, "reseller") >= 0){
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
	$data = array(
		"packageid" => $params["configoption1"],
		"groupid" => $groupid,
		"uid" => $uid,
		"fullname" => $clientsdetails['firstname'] . " " . $clientsdetails['lastname'] or "",
		"email" => $clientsdetails['email'] or "",
		"address" => $clientsdetails['address1'] or "",
		"postcode" => $clientsdetails['postcode'] or "",
		"password" => $password or "",
		"phone" => $clientsdetails['phonenumber'] or ""
	);

	$response = sendSenitorRequest($params, "whmcs", "UpdateClient", $data);

	$response_array = $response->asArray();
	return $response_array;
}

function sentora_ClientArea($params) {
	sendVersionToSentora($params);

	$code = '<form action="' . getAddress($params) . '/" method="post" target="_blank" name="sentoraform">
		<input type="button" onClick="window.open(\'' . getAddress($params) . '/\')" value="Login to Control Panel" />
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

	$response = sendSenitorRequest($params, "manage_clients", "GetAllClients", array()); // $xmws->XMLDataToArray($xmws->Request($xmws->BuildRequest()), 0);
	if(!empty($response)){
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
