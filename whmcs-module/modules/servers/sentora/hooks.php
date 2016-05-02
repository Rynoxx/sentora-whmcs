<?php


/**
 * Add hook function call.
 *
 * @param string $hookPoint The hook point to call
 * @param integer $priority The priority for the given hook function
 * @param string|function Function name to call or anonymous function.
 *
 * @return Depends on hook function point.
 */
add_hook('OverrideModuleUsernameGeneration', 1, function ($params) 
{
	if(strtolower($params["moduletype"] != "sentora") && strtolower($params["moduletype"]) != "zpanel" && strtolower($params["moduletype"]) != "zpanelx"){
		return;
	}

	$allowedUsernameGenerationVariables = array(
		"serviceid" => $params["serviceid"],
		"userid" => $params["userid"],
		"serverid" => $params["serverid"],
		"domain" => $params["domain"],
		"domain_short" => substr(str_replace(".", "", $params["domain"]), 0, 8),
		"firstname" => $params["clientsdetails"]["firstname"],
		"lastname" => $params["clientsdetails"]["lastname"],
		"fullname" => $params["clientsdetails"]["fullname"],
		"companyname" => $params["clientsdetails"]["companyname"],
		"email" => $params["clientsdetails"]["email"],
		"country" => $params["clientsdetails"]["country"],
		"groupid" => $params["clientsdetails"]["groupid"],
		"serverip" => $params["serverip"]
	)

	$customUsernameGen = $params["configoption4"];

	if(!empty($customUsernameGen) && is_string($customUsernameGen)){
		$username = $customUsernameGen;

		foreach ($allowedUsernameGenerationVariables as $key => $value) {
			$username = str_replace('{$' . $key . '}', $value, $username);
		}

		return $username;
	}
});
