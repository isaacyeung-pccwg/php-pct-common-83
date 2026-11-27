<?php
    /*
		=======================================================================================================
		php 7 upgrade project
		Modified by Asker 20200728
		=======================================================================================================
        */
        include_once(getenv("INSIGHT_CONFIG"));
	
	// Try and connect to the database, if a connection has not been established yet
    if ($insightDBLink) {
		
		
		if ($insightDBLink === false) {
			// Handle error - notify administrator, log to a file, show an error screen, etc.
			die("Unable to connect to database");
		}else{
			// use DEFINE for Configs because no one can mess with it after the fact, and we can check it anywhere without global-ling, making for easier checks
			// Global
			// For access by URL
			define('insight_root', $insight_root);
			define('insight_lib_url', $insight_lib_url);
			define('insight_img_url', $insight_img_url);
			define('dhx_lib_url', $dhx_lib_url);
			
			
			// For include in PHP source code
			define('insight_lib_path', $insight_lib_path);
			define('insight_img_path', $insight_img_path);
			define('insight_phpInc_path', $insight_phpInc_path);
            define('insight_helper_path', $insight_helper_path);
        }
    }else{
        die("Unable to connect to database");
	}

		
# set mysqli session time_zone to php's default timezone
$php_default_timezone = date_default_timezone_get();
if($php_default_timezone == 'UTC') {
	$php_default_timezone_offset = "+08:00";
} else {
	$timezone = new DateTimeZone($php_default_timezone); // Get default system timezone to create a new DateTimeZone object
	$offset = $timezone->getOffset(new DateTime()); // Offset in seconds to UTC
	$offsetHours = round(abs($offset)/3600);
	$offsetMinutes = round((abs($offset) - $offsetHours * 3600) / 60);
	$php_default_timezone_offset = ($offset < 0 ? '-' : '+')
		. ($offsetHours < 10 ? '0' : '') . $offsetHours
		. ':'
		. ($offsetMinutes < 10 ? '0' : '') . $offsetMinutes;
}
mysqli_query($insightDBLink, "set session time_zone = '$php_default_timezone_offset'");
date_default_timezone_set("Asia/Hong_Kong");

/** @var string $server_path */
$pct7_budget_request_root = "{$server_path}/pct7/cir/budget-request";
$pct7_purchase_request_root = "{$server_path}/pct7/pur/purchase-request";
$pct7_cost_term_root = "{$server_path}/pct7/cot/cost-term";
$pct81_budget_request_root = "{$server_path}/pct81/cir/budget-request";
$pct81_purchase_request_root = "{$server_path}/pct81/pur/purchase-request";
$pct82_purchase_request_root = "{$server_path}/pct82/pur/purchase-request";
$pct81_cost_term_root = "{$server_path}/pct81/cot/cost-term";
$pct82_budget_request_root = "{$server_path}/pct82/cir/budget-request";
$pct83_cost_term_root = "{$server_path}/pct83/cot/cost-term";
$budget_request_root = $pct82_budget_request_root;
$purchase_request_root = $pct82_purchase_request_root;
$cost_term_root = $pct83_cost_term_root;

