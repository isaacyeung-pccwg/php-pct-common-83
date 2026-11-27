<?php

	/* 
		=======================================================================================================
		php 7 upgrade project
		Modified by Qualicom 20200430
		=======================================================================================================
		
	*/
	
	header("Content-type:TEXT/HTML;charset=UTF-8");
	header('X-Frame-Options: SAMEORIGIN');
	
	$GpsAuth = NULL;
	$hash = '';
	$hash2string = '';
	$file = '';

	// Get request variables
	// include_once($_SERVER["DOCUMENT_ROOT"] . '/salesCommon/inc/getRequest.inc.php');
	//include_once($_SERVER["DOCUMENT_ROOT"] . '/salesCommon/config/local_config.php');

	// Customize for pct folder
	include($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/pct_common.inc.php");
	include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/hash_file_name.php");

	$hash = $_GET['hash'];
	$preview = $_GET['preview'];
	$originalFile = $_GET['originalFile'];
	//mysqli_set_charset($insightDBConn, "utf8");

	if (!empty($hash)) {
		$hash2string = hash_file_name("decode", $hash);
		$file = "$hash2string";
	}

	// Set default to preview mode
	if (!isset($preview)) {
		$preview = 1;
	}
	
	/* List of File Types */
	function fileTypes($extn){
		$fileTypes = [];
  $fileTypes['swf'] = 'application/x-shockwave-flash';
		$fileTypes['pdf'] = 'application/pdf';
		$fileTypes['exe'] = 'application/octet-stream';
		$fileTypes['zip'] = 'application/zip';
		$fileTypes['doc'] = 'application/msword';
		$fileTypes['xls'] = 'application/vnd.ms-excel';
		$fileTypes['ppt'] = 'application/vnd.ms-powerpoint';
		$fileTypes['gif'] = 'image/gif';
		$fileTypes['png'] = 'image/png';
		$fileTypes['tiff'] = 'image/tiff';
		$fileTypes['jpeg'] = 'image/jpg';
		$fileTypes['jpg'] = 'image/jpg';
		$fileTypes['rar'] = 'application/rar';

		$fileTypes['ra'] = 'audio/x-pn-realaudio';
		$fileTypes['ram'] = 'audio/x-pn-realaudio';
		$fileTypes['ogg'] = 'audio/x-pn-realaudio';

		$fileTypes['wav'] = 'video/x-msvideo';
		$fileTypes['wmv'] = 'video/x-msvideo';
		$fileTypes['avi'] = 'video/x-msvideo';
		$fileTypes['asf'] = 'video/x-msvideo';
		$fileTypes['divx'] = 'video/x-msvideo';

		$fileTypes['mp3'] = 'audio/mpeg';
		$fileTypes['mp4'] = 'audio/mpeg';
		$fileTypes['mpeg'] = 'video/mpeg';
		$fileTypes['mpg'] = 'video/mpeg';
		$fileTypes['mpe'] = 'video/mpeg';
		$fileTypes['mov'] = 'video/quicktime';
		$fileTypes['swf'] = 'video/quicktime';
		$fileTypes['3gp'] = 'video/quicktime';
		$fileTypes['m4a'] = 'video/quicktime';
		$fileTypes['aac'] = 'video/quicktime';
		$fileTypes['m3u'] = 'video/quicktime';
		$fileTypes['txt'] = 'text/*';	// header("Content-Type: text/*");
		
		if (array_key_exists($extn, $fileTypes)){
			$return_mime_str = $fileTypes[$extn];
		}else{
			$return_mime_str = "";
		}
		return $return_mime_str;
	}

//header('Location: '.$file);

    if (empty($hash)) {
		echo "hash not provided.";
    } else if (is_file($file)) {
		
		preg_match('/\.([a-zA-Z0-9]*)$/m', $file, $extension);
		
		$filename = basename($file);
		
		if(isset($extension)){
			
			$contentType = fileTypes($extension[1]);
			ob_clean();
			if ($contentType){
				header("Content-Type: $contentType");
			}else{
				header("Content-Type: application/octet-stream");
			}
			header("Content-Description: File Transfer");
			header('Content-Type: ' . mime_content_type($file));
			if (!isset($preview) || $preview != 1) {
			    
			    if ( isset($originalFile) ) {
			        $fallbackName = str_replace(["'", '"'], '', $originalFile); // fallback for ancient browsers
			        $encodedName = rawurlencode(rawurlencode($fallbackName));
			        //header("Content-Disposition: attachment; filename=\"$filename\"");
			        header("Content-Disposition: attachment; filename=\"$fallbackName\"; filename*=UTF-8''$encodedName");
			    } else {
			        header("Content-Disposition: attachment; filename=\"$filename\"");
			    }
				
			} else {
				if ( isset($originalFile) ) {
			        $fallbackName = str_replace(["'", '"'], '', $originalFile); // fallback for ancient browsers
			        $encodedName = rawurlencode(rawurlencode($fallbackName));
			        header("Content-Disposition: inline; filename=\"$fallbackName\"; filename*=UTF-8''$encodedName");
				}
				else
					header("Content-Disposition: inline; filename=\"$filename\"");
			}
			header("Content-Transfer-Encoding: binary");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Pragma: public");
			header("Content-Length: " . filesize($file));

			flush();
			readfile($file);
			exit;
		}
		
	} else {
		echo "File not found.";
	}
	
?>

<html>

	<head>
		<title>
			Open file
		</title>
		
		
	</head> 

	<body>

		
	</body>

</html> 
