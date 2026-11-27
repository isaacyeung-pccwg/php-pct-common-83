<?php 

	/* 
		=======================================================================================================
		php 7 upgrade project
		Modified Qualicom 20200430
		=======================================================================================================
		
		
	*/
	
	function hash_file_name($action, $string) {
		global $PHP_AUTH_USER;
		/* =================================================
			* ENCODE-DECODE
			* =================================================
			* ENCODE: hash_file_name('encode', $string);
			* DECODE: hash_file_name('decode', $string) ;
		*/
		
		$output = false;
		$encode_method = "AES-256-CBC";
		//$secret_key = 'WS-SERVICE-KEY';
		$secret_key = $PHP_AUTH_USER;
		
		$key = hash('sha256', $secret_key);
		
		//$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		//$iv = openssl_random_pseudo_bytes($iv_size);
		
		$secret_iv = 'INSIGHT-UPLOAD-FILE';
		// hash
		
		// iv - encode method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr(hash('sha256', $secret_iv), 0, 16);
		
		if ($action == 'encode') {
			$output = base64_encode(openssl_encrypt($string, $encode_method, $key, 0, $iv));
		} else if ($action == 'decode') {
			$output = openssl_decrypt(base64_decode($string), $encode_method, $key, 0, $iv);
		}
		return $output;
	}

