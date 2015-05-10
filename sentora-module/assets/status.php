<?php
header("Content-Type: text/plain; charset=utf-8");

error_reporting(0);

if(!empty($_REQUEST["action"]) && $_REQUEST["action"] == "phpinfo"){
	// If you want to enable PHP info, uncomment the line below.
	// exit(phpinfo());

	exit("No PHP Info is available due to security reasons.");
}

/*

	Credits to rick@rctonline.nl for creating the original get_server_load
	http://php.net/manual/en/function.sys-getloadavg.php#107243

	Slight modifications to reduce the execution time on windows servers by caching.
*/
function get_server_load() {
	if (stristr(PHP_OS, 'win') != false) {
		$fileModTime = filemtime("./load.cache");
		$cache = @file_get_contents("./load.cache");

		if(file_exists("./load.cache") && !empty($cache) && $fileModTime && (time() - $fileModTime) < 60){
			$load = $cache;
		}
		else{
			$wmi = new COM("Winmgmts://");
			$server = $wmi->execquery("SELECT LoadPercentage FROM Win32_Processor");

			$cpu_num = 0;
			$load_total = 0;

			foreach($server as $cpu){
				$cpu_num++;
				$load_total += $cpu->loadpercentage;
			}

			$load = round($load_total/$cpu_num, 2);

			file_put_contents("./load.cache", $load);
		}
	} else {
		$sys_load = sys_getloadavg();
		$load = round($sys_load[0], 2);
	}

	return (int) $load;
}

$avg_load = get_server_load();
$load = str_pad(strstr($avg_load, ".") === false ? $avg_load . "." : $avg_load, 4, "0", STR_PAD_RIGHT);

echo "<load>$load</load>\n";

$uptime_array = array("days" => "N/A", "hours" => "N/A", "mins" => "N/A", "secs" => "N/A");

function format_uptime($uptime){
	$days = floor($uptime/60/60/24);
	$hours = str_pad(floor($uptime/60/60)%24, 2, "0", STR_PAD_LEFT);
	$mins = str_pad(floor($uptime/60)%60, 2, "0", STR_PAD_LEFT);
	$secs = str_pad(floor($uptime%60), 2, "0", STR_PAD_LEFT);

	return array("days" => $days, "hours" => $hours, "mins" => $mins, "secs" => $secs);
}

if(stristr(PHP_OS, 'win') != false){
	$fileModTime = filemtime("./uptime.cache");

	$uptime = 0;
	
	$cache = @file_get_contents("./uptime.cache");

	if(file_exists("./uptime.cache") && !empty($cache) && $fileModTime && (time() - $fileModTime) < 60){
		$uptime = (int) $cache;
	}
	else{
		if(function_exists("shell_exec")){
			$started = shell_exec('systeminfo | find "Time:"');

			$matches = array();

			# Two available formats:
			# YYYY-MM-DD, HH:MM:SS
			# DD/MM/YYYY, HH:MM:SS
			# 
			# preg_match("/(([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})|([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})).?[^0-9]([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})[\s]?(AM|PM|[\s]?)/", $started, $matches);
			preg_match("/(([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})|([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})).?[^0-9]([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})[\s]?(AM|PM|[\s]?)/", $started, $matches);

			$uptime = time() - strtotime($matches[1]);

			file_put_contents("./uptime.cache", $uptime);
		}
	}

	$uptime_array = format_uptime($uptime);
}
else{
	$uptime_txt = @file_get_contents('/proc/uptime');
	$uptime = trim(substr($uptime_txt, 0, strpos($uptime_txt," ")));

	if (!$uptime && function_exists('shell_exec')) $uptime = shell_exec("cut -d. -f1 /proc/uptime");

	$uptime_array = format_uptime($uptime);
}

echo "<uptime>{$uptime_array["days"]} Days {$uptime_array["hours"]}:{$uptime_array["mins"]}:{$uptime_array["secs"]}</uptime>\n";
