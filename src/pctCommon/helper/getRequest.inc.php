<?php
// This file is for legacy code only
if (!function_exists("addslashes_recursive")) {
	function addslashes_recursive(&$arr_r)
	{
		foreach ($arr_r as &$val) {
			is_array($val)?
				addslashes_recursive($val)
				: $val=addslashes(rawurldecode((string) $val));
		}
		unset($val);
	}
}
addslashes_recursive($_GET);
addslashes_recursive($_POST);

foreach($_GET as $key => $value)
{
   ${$key}=$value;
}
 
foreach($_POST as $key => $value)
{
   ${$key}=$value;
}


