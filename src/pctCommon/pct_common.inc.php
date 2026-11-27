<?php

// =================================HEADER START========================================
// PHP 7.1 upgrade project
// Modified By Brian 2019-01-22
//======================================================================================

//Header Declaration: Using UTF-8 Encoding
header("Content-type:TEXT/HTML;charset=UTF-8");
header('X-Frame-Options: SAMEORIGIN');

// init lib variable
$insight_lib_path	= $_SERVER["DOCUMENT_ROOT"] . "/Lib";
$insight_img_path	= $_SERVER["DOCUMENT_ROOT"] . "/images";
$insight_phpInc_path	= $_SERVER["DOCUMENT_ROOT"] . "/../phpInc";
$insight_helper_path	= $_SERVER["DOCUMENT_ROOT"] . "/../phpInc/helpers";


//Include Config
include_once(getenv("INSIGHT_CONFIG"));
include_once($insight_phpInc_path . "/auth.inc.php");
include_once($insight_phpInc_path . "/encrypt.inc.php");
include_once($insight_phpInc_path . "/function.inc.php");
include_once($insight_lib_path . "/common.inc.php");

//Include Logger
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/logger.php");
global $logger;

// Common urls
$pct_common_url = $insight_root.'/pctCommon';

// include db helper functions mysql
//include($insight_helper_path . '/db_helpers.php');

//Init mysql environment
//mysqli_set_charset($insightDBConn, "utf8");
global $insightDbConn;

//Init mssql environment
global $EPI_HOSTNAME;
global $EPI_USERNAME;
global $EPI_PASSWORD;

//======================================================
function getEpicorDbNameBySignedCo($signed_co)
{	
	 if (!$signed_co) 
		return false;
	
     $companyCode = findEpicorCompanyCode($signed_co);
	 if ($companyCode) {
		  $db_name = "BTN" . $companyCode[0];
		  return $db_name;
	   } else {
		  return false;
	   }
}
function findEpicorCompanyCode($signed_co){
	// Get Epicor Database Name by Signed_co
    global $insightDBConn;

	$sql = "SELECT lpad(CoCode, 3, '0') 
			FROM tblRegisteredCo 
			LEFT OUTER JOIN tblCo_Entities 
			ON SignedCo = tblRegisteredCo.ID 
			WHERE ID = ".$signed_co;
	  $query = mysqli_query($insightDBConn,$sql);
	if ($query) {
	    $epi_co_code = mysqli_fetch_row($query);
		mysqli_free_result($query);		
		return $epi_co_code;
	} else {
		return false;
	}
}
function getEpicorDbConnectionInfo($db_name)
{
	// Get Connection String for Epicor Database Connection
	global $EPI_USERNAME;
	global $EPI_PASSWORD;
	
	$connectionInfo = ["Database"=>$db_name, "UID"=>$EPI_USERNAME, "PWD"=>$EPI_PASSWORD];
	
	return $connectionInfo;
}


function connectEpicor($signed_co) {
	
	//Since mssql_select_db function is removed on sqlsrv command,
	//So the common function will return the database name
	//To switch different database, developers can use sqlsrv_query by execute 'USE DATABASE xxxxxx'
	//=========================================================
	//e.g. create queries to switch database
	//$tsql-db1 = "USE DATABASE DB_with_Table1"
	//$tsql-db2 = "USE DATABASE DB_with_Table2"

	// switch to database ONE
	//$stmt = sqlsrv_query( $conn, $tsql-db1 );
	// get some data
	//$stmt = sqlsrv_query( $conn, "SELECT * FROM Table1" );
	
	// do something with the data from database ONE
	
	// switch to database TWO
	// $stmt = sqlsrv_query( $conn, $tsql-db2 );
	//=========================================================
	
       global $EPI_HOSTNAME;
	   $epi_db_name = getEpicorDbNameBySignedCo($signed_co); 
            
       if ($epi_db_name) {
		  $connInfo = getEpicorDbConnectionInfo($epi_db_name);
		  $conn = sqlsrv_connect($EPI_HOSTNAME, $connInfo);
		  if ($conn) {
			   return $conn;
		 } else {
			   return false;
		 }
       } else {
              return false;
       }
}

function connectEpicorDefaultDb() {
	
	
	global $EPI_HOSTNAME;
	$EPI_DEFAULTDATABASE = 'BTNControl';
	               
	$connInfo = getEpicorDbConnectionInfo($EPI_DEFAULTDATABASE);
	$conn = sqlsrv_connect($EPI_HOSTNAME, $connInfo);
	if ($conn) {
		   return $conn;
	} else {
		   return false;
	}	
        
}

function encrypt_decrypt($action, $string){
	global $PHP_AUTH_USER;
	/* =================================================
		* ENCRYPTION-DECRYPTION
		* =================================================
		* ENCRYPTION: encrypt_decrypt('encrypt', $string);
		* DECRYPTION: encrypt_decrypt('decrypt', $string) ;
	*/
	
	$output = false;
	$encrypt_method = "AES-256-CBC";
	//$secret_key = 'WS-SERVICE-KEY';
	$secret_key = $PHP_AUTH_USER;
	
	$key = hash('sha256', $secret_key);
	
	//$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	//$iv = openssl_random_pseudo_bytes($iv_size);
	
	
	$secret_iv = 'INSIGHT-UPLOAD-FILE';
	// hash
	
	// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	$iv = substr(hash('sha256', $secret_iv), 0, 16);
	
	if ($action == 'encrypt') {
		$output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
	} else {
		if ($action == 'decrypt') {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}
	}
	return $output;
}    

function csrfToken()
{

	global $logger;
	$logger->debug("Generate Token");
	$token = "";
	
	clean_csrfToken();

	// To prevent CSRF attacks, it�s important to use anti-CSRF tokens and 
	// check them on the server side
	// https://thedigilife.com/securing-your-php-applications-protecting-against-sql-injection-xss-and-csrf-php-beginner-to-advance/

	if (!isset($_SESSION)) {
		//session_start();
	} else {
		// Current Session Timeout Value
		//$currentTimeout = ini_get('session.gc_maxlifetime');

		// form only valid for 5 minutes (60 x 5 = 300)
		// time() returns the current time measured in the number of seconds 
		// since the Unix Epoch (January 1 1970 00:00:00 GMT).
		$time_now = time();
		$expiry = $time_now;
		$expiry4h = $time_now + strtotime('+4 hours', 0);

		// this will be decently random.  Default. 32 character hex number
		// e.g.: 42f4780b3e297d77c7abd7b7b15e5291
		$token = md5(
			openssl_random_pseudo_bytes(16)
		);
		$logger->debug("MD5 : $token");

		//$token = encrypt_decrypt("encrypt", $_SERVER['PHP_SELF']);
		$token = encrypt_decrypt("encrypt", $token);
		//echo $_SERVER['PHP_SELF'] . "<br>";

		$logger->debug("Encrypted : $token");
		
		// save token and expiry to user session.
		if (!isset($_SESSION['csrfToken'])) {
			$_SESSION['csrfToken'] = [];
		}
		//$_SESSION['xssToken'][] = array($token, $expiry);
		$_SESSION['csrfToken'][$token] = $expiry;

		// Setting a secure and HttpOnly cookie
		// setcookie("sensitive_data", "value", time() + 3600, "/", "", true, true);
		$token_p1 = substr($token, 0, 55);
		$token_p2 = substr($token, 55);
		///setcookie($token_p1, $token_p2, $expiry4h, "/", "", true, true);
		$cookie_name = $token_p1;
		$cookie_val = $token_p2;
		$arr_cookie_options = [
			'expires' => time() + strtotime('+4 hours', 0),
			'path' => '/',
			'domain' => '',				// leading dot for compatibility or use subdomain (e.g.: .example.com)
			'secure' => true,			// or false
			'httponly' => true,			// or false
			'samesite' => 'Lax'			// None || Lax  || Strict
		];
		setcookie($cookie_name, $cookie_val, $arr_cookie_options);
	}

	// return token for use in form field.
	return $token;
}

function clean_csrfToken()
{
	if (!isset($_SESSION)) {
		//session_start();
	} else {
		if (isset($_SESSION['csrfToken'])) {
			if (is_array($_SESSION['csrfToken'])) {
				//var_dump($_SESSION['xssToken']);echo time() . "<br>";
				foreach ($_SESSION['csrfToken'] as $x => $y) {
					$diff = (time() - $y) / 60;
					if ($diff > 0) {
						//echo "Unset key $x<br>";
						unset($_SESSION['csrfToken'][$x]);
					} else {
						//echo "$x : $y diff = $diff<br>";
					}
				}
				//echo "<br>";
			}
		}
	}
}

function check_csrf_Token($myToken, $ec_mode)
{
	/// https://css-tricks.com/forums/topic/should-i-worry-about-xss-attacks/

	$return_val = 0;
	if ($myToken) {
		// valid the token
		if ($ec_mode == "bin") {
			$check_result = ctype_xdigit($myToken);
			$token_len = 32;
		} else {
			$check_result = isBase64Encoded($myToken);
			$token_len = strlen($myToken);
		}
		if ($check_result) {
			if (strlen($myToken) == $token_len) {
				if (!isset($_SESSION)) {
					//session_start();
					$return_val = 0;
				} else {
					if (!isset($_SESSION['csrfToken'])) {
						$return_val = -5;
					}else{
						if (is_array($_SESSION['csrfToken'])) {
							if (array_key_exists($myToken, $_SESSION['csrfToken'])) {

								/* before processing the form, MAKE SURE:
									1) the user session has an xss token.		e.g.: $_SESSION['xssToken']
									2) the form submission has an xss token.	e.g.: $myToken
									3) the tokens match.						e.g.: array_key_exists($myToken, $_SESSION['xssToken'])
									4) the tokens have not expired. 			e.g.: time() < $_SESSION['xssToken'][$myToken]
								*/

								// token can only be used once
								// you might log each token so it can't be reused, ever.
								// unset($_SESSION['xssToken']);
								$time_now = time();
								$expiry = $time_now;
								$_SESSION['csrfToken'][$myToken] = $expiry;
								$return_val = 1;
							} else {
								$return_val = -4;
							}
							/* if not, ignore entire POST. Seriously.or maybe write to security log, etc..  */
							//exit(1);
						} else {
							$return_val = -3;
							/*
							setcookie($myToken,$timeout,$timeout + 3600);
							$_COOKIE][$myToken]
							*/
						}
					}
				}
			} else {
				$return_val = -2;
			}
		} else {
			$return_val = -1;
		}
	}
	return $return_val;
}

function check_csrf_Cookie($myToken, $ec_mode)
{
	$return_val = 0;
	$check_result = 0;
	if ($myToken) {
		// valid the token
		if ($ec_mode == "bin") {
			$myToken2a = $myToken;
			$myToken2b = $_COOKIE[$myToken2a];
		} else {
			$myToken2a = substr($myToken, 0, 55);
			$myToken2b = substr($myToken, 55);
		}

		if (isset($_COOKIE[$myToken2a])) {
			if ($ec_mode == "bin") {
				$myToken2 = $myToken2a;
				if ($myToken2 == $myToken) {
					$check_result = ctype_xdigit($myToken2);
					$token_len = strlen($myToken2);
				} else {
					$return_val = -1;
				}
			} else {
				$myToken2b = $_COOKIE[$myToken2a];
				$myToken2 = $myToken2a . urldecode($myToken2b);
				if ($myToken2 == $myToken) {
					$check_result = isBase64Encoded($myToken2);
					$token_len = strlen($myToken2);
				} else {
					$return_val = -1;
				}
			}
			if ($check_result) {
				//$time_now = time();
				//$expiry4h = $time_now + strtotime('+4 hours', 0);
				//setcookie($myToken2a, $myToken2b, $expiry4h);

				$cookie_name = $myToken2a;
				$cookie_val = $myToken2b;
				$arr_cookie_options = [
					'expires' => time() + strtotime('+4 hours', 0),
					'path' => '/',
					'domain' => '',				// leading dot for compatibility or use subdomain (e.g.: .example.com)
					'secure' => true,			// or false
					'httponly' => true,			// or false
					'samesite' => 'Lax'			// None || Lax  || Strict
				];
				setcookie($cookie_name, $cookie_val, $arr_cookie_options);
			}
			$return_val = $check_result;
		} else {
			$return_val = -2;
		}
	}
	return $return_val;
}

function check_session_data($csrf_token)
{

	global $logger;
	
	$logger->debug("Check CSRF Token...");
	
	// "bin" / "b64"
	$resu_1 = check_csrf_Token($csrf_token, "b64");
	$resu_2 = check_csrf_Cookie($csrf_token, "b64");

	$str_resu_1 = $resu_1 > 0 ? "1" : "0";
	$str_resu_2 = $resu_2 > 0 ? "1" : "0";

	$check_resu = $str_resu_1 . $str_resu_2;
	$report_error = 0;
	switch ($check_resu) {
		case "11":
			/*
			Session token found, also exist in cookie variable
		*/

			break;
		case "10":
			/*
			Session token found, but it does not exist in cookie variable
			May be cookie expiry
		*/
			break;
		case "01":
			/*
			Session token can not be found, but it exist in cookie variable
			May be session timeout
		*/
			break;
		case "00":
			/*
			Session token can not be found, also it not exist in cookie variable
			May be session timeout
		*/
			$report_error = 1;
			break;
	}
	return $report_error;
}


function isBase64Encoded($strValue)
{
	return base64_encode(base64_decode($strValue, true)) === $strValue;
}

?>
