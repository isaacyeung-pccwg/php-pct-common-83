<?php
	/* 
		=======================================================================================================
		php 7 upgrade project
		Modified by Lung 20190125
		=======================================================================================================
		it is a replacement for ISD to calling insight auth.inc.php, 
		it merge global and local config, init all the environment varables then 
		performing authentication check
		
		From 
		include_once($_SERVER["DOCUMENT_ROOT"] . "/../phpInc/auth.inc.php");
		To
		include_once( $_SERVER["DOCUMENT_ROOT"] . "/nis/config/auth.php");
		
	*/
	// setup global / local variable 
	include_once( $_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");
	
	// Authentication check
	include_once( $insight_phpInc_path . "/auth.inc.php");
	
	// require_once ($insight_phpInc_path."/CAS/config.php");
	
	// // Load the CAS lib
	// require_once ($insight_phpInc_path."/CAS/CAS-1.3.5/CAS.php");

	// echo "CAS host from config: " . $cas_host;
	// echo "CAS context: " . $cas_context;
