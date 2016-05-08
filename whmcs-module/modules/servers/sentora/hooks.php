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
	);

	$matchVariable = "\s?[\"']?(\\$?[-_\w]+[^,\"']?)[\"']?\s?";

	$functions = array(
		"pad\(" . $matchVariable . "," . $matchVariable . "," . $matchVariable . "\)" => function($matches, $allowedVars){
			$input = $matches[1];
			$pad_length = $matches[2];
			$padding = $matches[3];

			foreach ($allowedVars as $key => $value) {
				$input = str_replace('$' . $key, $value, $input);
				$pad_length = str_replace('$' . $key, $value, $pad_length);
				$padding = str_replace('$' . $key, $value, $padding);
			}

			logModuleCall("Sentora", "customUsernameGeneration", $params, "input: $input\npad_length: $pad_length\npadding: $padding", "", array());

			return str_pad($input, $pad_length, $padding, STR_PAD_LEFT);
		}
	);

	$customUsernameGen = $params["configoption4"];

	#var_dump($customUsernameGen);
	#var_dump($params);

	if((!empty($customUsernameGen) && is_string($customUsernameGen)) || empty($params["username"])){
		$username = empty($customUsernameGen) ? "cp_{$serviceid}" : $customUsernameGen;

		foreach ($allowedUsernameGenerationVariables as $key => $value) {
			$username = str_replace('{$' . $key . '}', $value, $username);
		}


		foreach ($functions as $key => $value) {
			#var_dump($key);
			preg_match("|\{" . $key . "\}|iU", $username, $matches);
			#var_dump($matches);

			if(!empty($matches) && count($matches) > 0){
				$username = preg_replace("|\{" . $key . "\}|iU", $value($matches, $allowedUsernameGenerationVariables), $username);
			}

			logModuleCall("Sentora", "customUsernameGeneration", $params, $username . " -> \n" . $key . " -> " . print_r($matches, true) . " -> \n" . $username, "", array());
		}

		$username = str_replace(".", "", str_replace("_", "", $username));

		return $username;
	}
});
