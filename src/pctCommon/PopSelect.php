<?php
include($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/pct_common.inc.php");
// File holder by Fung Task #8356
include_once(getenv("INSIGHT_CONFIG"));
//include_once("../config.inc.php");
include_once($insight_phpInc_path . "/auth.inc.php");
include_once($insight_helper_path . "/db_helpers.php");
include_once($insight_helper_path . "/getRequest.inc.php");
include_once($insight_helper_path . "/common_helpers.php");
include_once($insight_lib_path . "common.inc.php");

$s = array_key_exists('s', $_REQUEST) ? $_REQUEST['s'] : 0;
$index = array_key_exists('$index', $_REQUEST) ? $_REQUEST['$index'] : 0;

/* Searching Setting */
$QPerTime = min(1 + $s, 100);
//$QPerTime = 50;
$debugmode = 0;
/* End */

$s = floor($s);
$index = floor($index);
$script_name = basename((string) $_SERVER["SCRIPT_NAME"]);


// $logger->info(" \ns: ".$s);
// $logger->info(" \nQPerTime: ".$QPerTime);

// migration variable start
// global $s;
global $index;
global $i;
global $pm_name;
// global $insightDBLink;

// $num = 0;
// $showlist[] = array();
$p = $_GET['p'] ?? null;
$sr = $_GET['sr'] ?? null;
$f = $_GET['f'] ?? null;
$action = $_GET['action'] ?? null;
$vendor_code = $_GET['vendor_code'] ?? '';
$action_type ??= '';

$CR_filtered = array_key_exists('CR_filtered', $_REQUEST) ? $_REQUEST['CR_filtered'] : "";
$Code = array_key_exists('Code', $_REQUEST) ? $_REQUEST['Code'] : "";
$Name = array_key_exists('Name', $_REQUEST) ? $_REQUEST['Name'] : "";
$cost = array_key_exists('cost', $_REQUEST) ? $_REQUEST['cost'] : 0;
$type = array_key_exists('type', $_REQUEST) ? $_REQUEST['type'] : "";

$addr = array_key_exists('addr', $_REQUEST) ? $_REQUEST['addr'] : "";
$epicor_code = array_key_exists('epicor_code', $_REQUEST) ? $_REQUEST['epicor_code'] : "";
$vendor_group = array_key_exists('vendor_group', $_REQUEST) ? $_REQUEST['vendor_group'] : "";
$vendor_name = array_key_exists('vendor_name', $_REQUEST) ? $_REQUEST['vendor_name'] : "";
$vendor_type = array_key_exists('vendor_type', $_REQUEST) ? $_REQUEST['vendor_type'] : "";
$signed_co = $_REQUEST['co'] ?? 0;
$vendortype = $vendor_type;
//XSS
function customCharEscape($inputstr){
	$inputstr = str_replace(";", "", (string) $inputstr);
	$inputstr = str_replace("<", "&lt;", $inputstr);
	$inputstr = str_replace(">", "&gt;", $inputstr);
	$inputstr = str_replace(":", "", $inputstr);
	return $inputstr;
}

$s = htmlentities($s);
$index = htmlentities((string) $index);
$p = htmlentities((string) $p);
$sr = htmlentities((string) $sr);
$f = htmlentities((string) $f);
$action = htmlentities((string) $action);
$vendor_code = htmlentities((string) $vendor_code);
$CR_filtered = htmlentities((string) $CR_filtered);
$Code = htmlentities((string) $Code);
$Name = customCharEscape($Name);
$cost = htmlentities((string) $cost);
$type = htmlentities((string) $type);
$addr = customCharEscape($addr);
$epicor_code = htmlentities((string) $epicor_code);
$vendor_group = htmlentities((string) $vendor_group);
$vendor_name = customCharEscape($vendor_name);
$vendor_type = htmlentities((string) $vendor_type);
$signed_co = customCharEscape($signed_co);

$EPI_DB_NAME = array_key_exists('EPI_DB_NAME', $_REQUEST) ? $_REQUEST['EPI_DB_NAME'] : "";

global $showlist;
global $glseg1;
global $glseg2;
global $num;

$sql = '';
// migration variable end


// $x = dbSqlSrvConnect($EPI_HOSTNAME, $EPI_DB_NAME, $EPI_USERNAME, $EPI_PASSWORD);
// $logger->info(" \nEPI_HOSTNAME: ".$EPI_HOSTNAME);
// $logger->info(" \nEPI_DB_NAME: ".$EPI_DB_NAME);
// $logger->info(" \nEPI_USERNAME: ".$EPI_USERNAME);
// $logger->info(" \nEPI_PASSWORD: ".$EPI_PASSWORD);

/* Respone page for AJAX searching */
if ($action == 'search') {
	$co_name = array_key_exists('co_name', $_REQUEST) ? $_REQUEST['co_name'] : "";
	$pm_name = array_key_exists('pm_name', $_REQUEST) ? $_REQUEST['pm_name'] : "";
	//XSS
	$co_name = customCharEscape($co_name);
	$pm_name = customCharEscape($pm_name);

	if ($p == 'sr') {
		$sql = '';

		if ($co_name)	$sql .= " AND CustomerName LIKE ('%" . $co_name . "%')";
		if ($pm_name)	$sql .= " AND Engineer = '" . $pm_name . "'";
		if (!$s) {
			// 			$query = mysqli_query("SELECT COUNT(*) FROM tblSale LEFT OUTER JOIN tblCustomer ON CustomerID = tblCustomer.ID LEFT OUTER JOIN tblSaleStatus ON tblSaleStatus.ID = SaleStatusID WHERE SaleStatusID IN ('CL', 'IM', 'OP', 'PD', 'PR') $sql");
			$num = dbSelectColumn($insightDBLink, "SELECT COUNT(*) FROM tblSale LEFT OUTER JOIN tblCustomer ON CustomerID = tblCustomer.ID LEFT OUTER JOIN tblSaleStatus ON tblSaleStatus.ID = SaleStatusID WHERE SaleStatusID IN ('CL', 'IM', 'OP', 'PD', 'PR') $sql", 0);
		}

		$query = mysqli_query($insightDBLink, "SELECT tblSale.ID, CustomerName, StatusName,Engineer FROM tblSale LEFT OUTER JOIN tblCustomer ON CustomerID = tblCustomer.ID LEFT OUTER JOIN tblSaleStatus ON tblSaleStatus.ID = SaleStatusID WHERE SaleStatusID IN ('CL', 'IM', 'OP', 'PD', 'PR') $sql ORDER BY SaleStatusID DESC, CustomerName, ID LIMIT $s, $QPerTime");
		while ($value = mysqli_fetch_array($query)) {
			$value['SaleID'] = SRID($value['ID']);
			$showlist[] = $value;
		}
	} elseif ($p == "pr_sr") {
		$sql = "";
		if ($co_name) $sql .= " AND CustomerName LIKE ('%" . $co_name . "%')";
		if ($pm_name) $sql .= " AND Engineer = '" . $pm_name . "'";

		if (!$s) {
			/*			$query = mysqli_query("SELECT COUNT(*) FROM tblSale 
				LEFT OUTER JOIN tblCustomer ON CustomerID = tblCustomer.ID 
				LEFT OUTER JOIN tblSaleStatus ON tblSaleStatus.ID = SaleStatusID 
				WHERE SaleStatusID NOT IN ('LD', 'PP', 'HP', 'DR') $sql");
			*/
			$num = dbSelectColumn($insightDBLink, "SELECT COUNT(*) FROM tblSale 
				LEFT OUTER JOIN tblCustomer ON CustomerID = tblCustomer.ID 
				LEFT OUTER JOIN tblSaleStatus ON tblSaleStatus.ID = SaleStatusID 
				WHERE SaleStatusID NOT IN ('LD', 'PP', 'HP', 'DR') $sql", 0);
		}

		$query = mysqli_query($insightDBLink, "SELECT tblSale.ID, CustomerName, StatusName,Engineer FROM tblSale 
			LEFT OUTER JOIN tblCustomer ON CustomerID = tblCustomer.ID 
			LEFT OUTER JOIN tblSaleStatus ON tblSaleStatus.ID = SaleStatusID 
			WHERE SaleStatusID NOT IN ('LD', 'PP', 'HP', 'DR') $sql 
			ORDER BY SaleStatusID DESC, CustomerName, ID LIMIT $s, $QPerTime");
		while ($value = mysqli_fetch_array($query)) {
			$value["SaleID"] = SRID($value["ID"]);
			$showlist[] = $value;
		}
	} else if ($p == "vendor") {
		$sql = "";
		if ($epicor_code) $sql .= " AND tblVendor.EpicorCode = '" . $epicor_code . "'";
		if ($vendor_group) $sql .= " AND tblVendorGps.ID = '" . $vendor_group . "'";
		if ($vendor_type) $sql .= " AND vendor_type = '" . $vendor_type . "'";
		if ($vendor_name) $sql .= " AND CoName LIKE '%" . $vendor_name . "%'";
		if ($addr) $sql .= " AND Address LIKE '%" . $addr . "%'";
		if ($CR_filtered == "1") $sql .= " AND CR_filtered >0"; // add by Fung #8356 filter CR_filtered>0 in tblVendor for IS
		if (!$s) {
			/*			$query = mysqli_query("SELECT COUNT(*) 
				FROM tblVendor LEFT JOIN tblVendorGps ON tblVendor.GroupID = tblVendorGps.ID 
				WHERE vendor_status = 'A' AND tblVendor.EpicorCode <> '' AND tblVendor.EpicorCode <> '0' 
					AND tblVendor.EpicorCode is not NULL AND vendor_type <> '' AND vendor_type IS NOT NULL $sql");
			*/
			$num = dbSelectColumn($insightDBLink, "SELECT COUNT(*) 
				FROM tblVendor LEFT JOIN tblVendorGps ON tblVendor.GroupID = tblVendorGps.ID 
				WHERE vendor_status = 'A' AND tblVendor.EpicorCode <> '' AND tblVendor.EpicorCode <> '0' 
					AND tblVendor.EpicorCode is not NULL AND vendor_type <> '' AND vendor_type IS NOT NULL $sql", 0);
		}
		//modified by Fung #6455 add tblVendorGps.ID
		$query = mysqli_query($insightDBLink, "SELECT vendor_type, tblVendor.EpicorCode, CoName, Address, GroupName, tblVendorGps.ID 
			FROM tblVendor LEFT JOIN tblVendorGps ON tblVendor.GroupID = tblVendorGps.ID 
			where vendor_status = 'A' AND tblVendor.EpicorCode <> '' 
				AND tblVendor.EpicorCode <> '0' AND tblVendor.EpicorCode is not NULL AND vendor_type <> '' 
				AND vendor_type IS NOT NULL $sql ORDER BY CoName LIMIT $s, $QPerTime");
		while ($value = mysqli_fetch_array($query)) {
			$showlist[] = $value;
		}
	} else if ($p == 'gl') {
		// $logger->info('gl start');
		// $EPI_HOSTNAME ="192.168.80.96";
		// $EPI_USERNAME ="for_scripts";
		// $EPI_PASSWORD ="5h45tr4";
		// $EPI_DB_NAME = "BTN".dbSelectColumn($insightDBLink, "SELECT lpad(CoCode, 3, '0') FROM tblRegisteredCo LEFT OUTER JOIN tblCo_Entities ON SignedCO = tblRegisteredCo.ID WHERE ID ='$co'", 0);
		// $EPI_DB_NAME = "BTN".dbSelectColumn($insightDBLink, "SELECT lpad(CoCode, 3, '0') FROM tblRegisteredCo LEFT OUTER JOIN tblCo_Entities ON SignedCO = tblRegisteredCo.ID WHERE ID ='$co'", 0);
		$query = mysqli_query($insightDBLink, "SELECT lpad(CoCode, 3, '0') FROM tblRegisteredCo LEFT OUTER JOIN tblCo_Entities ON SignedCO = tblRegisteredCo.ID WHERE ID ='$co'");
		$EPI_DB_NAME = "BTN" . dbSelectColumnFromQuery($query, 0);
		$x = dbSqlSrvConnect($EPI_HOSTNAME, $EPI_DB_NAME, $EPI_USERNAME, $EPI_PASSWORD);

		if ($x) {
			$sql = '';
			if ($glseg1)	$sql .= " AND SUBSTRING(account_code, 1, 5) = '$glseg1'";
			if ($glseg2)	$sql .= " AND SUBSTRING(account_code, 6, 4) = '$glseg2'";

			if (!$s) {
				$query = sqlsrv_query($x, "select COUNT(*) from glchart WHERE inactive_flag = 0 $sql");
				$num = sqlsrv_fetch_array($query);
				$num = $num[0];
			}

			$query = sqlsrv_query($x, "select * FROM (select TOP " . min($num - $QPerTime + 1, $QPerTime) . " * FROM (select TOP " . ($s + $QPerTime) . " account_code, account_description,seg1_code + '-' + seg2_code + '-' + seg3_code +'-' + seg4_code as account_code_display, account_type from glchart WHERE inactive_flag = 0 $sql ORDER BY account_code) t1 ORDER BY account_code DESC) t2 ORDER BY account_code");
			while ($value = sqlsrv_fetch_array($query)) {
				$showlist[] = $value;
			}
			sqlsrv_close($x);
			//debug start
			// $logger->info("\n migration debug 1019:" . "select * FROM (select TOP ".min($num-$QPerTime+1,$QPerTime)." * FROM (select TOP ".($s+$QPerTime)." account_code, account_description,seg1_code + '-' + seg2_code + '-' + seg3_code +'-' + seg4_code as account_code_display, account_type from glchart WHERE inactive_flag = 0 $sql ORDER BY account_code) t1 ORDER BY account_code DESC) t2 ORDER BY account_code");
			// $logger->info("\n migration debug 1019:" . implode(',', $showlist));
			//debug end
		}
	} else if ($p == 'oc' || $p == 'rfq_oc' || $p == 'tm') {
		$query = mysqli_query($insightDBLink, "SELECT CodeValue as value, Description as name FROM tblGenericCode WHERE CodeType = 'CostCat'");
		while ($value = mysqli_fetch_array($query)) {
			$CostCat_name[$value['value']] = $value['name'];
		}

		$query = mysqli_query($insightDBLink, "SELECT CoName as name, EpicorCode as value FROM tblVendor");
		while ($value = mysqli_fetch_array($query)) {
			$VendorCode_name[$value['value']] = $value['name'];
		}

		$query = mysqli_query($insightDBLink, "SELECT CodeValue as value, Description as name FROM tblGenericCode WHERE CodeType = 'CostType'");
		while ($value = mysqli_fetch_array($query)) {
			$CostType_name[$value['value']] = $value['name'];
		}

		$query = mysqli_query($insightDBLink, "SELECT CodeValue as value, Description as name FROM tblGenericCode WHERE CodeType = 'BillPeriod'");
		while ($value = mysqli_fetch_array($query)) {
			$BillingPeriod_name[$value['value']] = $value['name'];
		}

		$query = mysqli_query($insightDBLink, "SELECT ID as value, CoName as name From tblRegisteredCo");
		while ($value = mysqli_fetch_array($query))
			$SignedCo_name[$value['value']] = $value["name"];

		$query = mysqli_query($insightDBLink, "SELECT CodeValue as value, Description as name FROM tblGenericCode WHERE CodeType='UOM'");
		while ($value = mysqli_fetch_array($query)) {
			$UOM_name[$value['value']] = $value['name'];
		}
		/*
		$not_in_id = array();
		$query = mysqli_query("select PrevCostItemID from tblPCReqDetail left outer join tblPCReqHeader on ReqID = tblPCReqHeader.ID where PrevCostItemID > 0 and CurrentStep not in (6, 7)");
		while($value = mysqli_fetch_array($query)) {
			$not_in_id[] = $value['PrevCostItemID'];
		}*/

		if (!$s) $sql = "";

		$desc = array_key_exists('desc', $_REQUEST) ? $_REQUEST['desc'] : "";
		$pro_no = array_key_exists('pro_no', $_REQUEST) ? $_REQUEST['pro_no'] : "";
		$signed_co = array_key_exists('signed_co', $_REQUEST) ? $_REQUEST['signed_co'] : "";
		$requester_name = array_key_exists('requester_name', $_REQUEST) ? $_REQUEST['requester_name'] : "";
		$VendorCode = array_key_exists('VendorCode', $_REQUEST) ? $_REQUEST['VendorCode'] : "";
		$SRno = array_key_exists('SRno', $_REQUEST) ? $_REQUEST['SRno'] : "";
		$usn = array_key_exists('usn', $_REQUEST) ? $_REQUEST['usn'] : "";
		$po_num = array_key_exists('po_num', $_REQUEST) ? $_REQUEST['po_num'] : "";
		$item_status = array_key_exists('item_status', $_REQUEST) ? $_REQUEST['item_status'] : "";

		//XSS
		$desc = htmlentities((string) $desc);
		$pro_no = htmlentities((string) $pro_no);
		$signed_co = htmlentities((string) $signed_co);
		$requester_name = htmlentities($desc);
		$VendorCode = htmlentities((string) $VendorCode);
		$SRno = htmlentities((string) $SRno);
		$usn = htmlentities((string) $usn);
		$po_num = htmlentities((string) $po_num);
		$item_status = htmlentities((string) $item_status);

		if ($desc) $sql .= " AND a.Description LIKE ('%" . $desc . "%')";
		if ($pro_no) $sql .= " AND a.ProjectNum LIKE '" . $pro_no . "%'";
		if ($signed_co) $sql .= " AND a.SignedCo = '" . $signed_co . "'";
		if ($requester_name) $sql .= " AND a.RequestUser = '" . $requester_name . "'";
		if ($vendor_type) $sql .= " AND a.VendorType = '" . $vendor_type . "'";
		if ($VendorCode) $sql .= " AND a.VendorCode = '" . $VendorCode . "'";
		if ($SRno) {
			$SRno = floor(str_replace("sR", "", str_replace("Sr", "", str_replace("SR", "", str_replace("sr", "", $SRno)))));
			$sql .= " AND a.SRNum = '" . $SRno . "'";
		}
		if ($usn) $sql .= " AND a.UniversalServiceNumber = '" . $usn . "'";
		if ($po_num) $sql .= " AND a.PONum LIKE '%" . $po_num . "'";
		if (!$item_status) $item_status = "Active";

		//task 6464 27/7/2010
		if ($item_status == "Terminated") {


			$SQL_co = "SELECT Description FROM tblGenericCode WHERE CodeCat = 'CTSubmit' ";
			$DBT1_coname = mysqli_query($insightDBLink, $SQL_co);
			$DBTrow_max_bpmt = mysqli_fetch_row($DBT1_coname);
			$CI_current_step = $DBTrow_max_bpmt[0];


			$query = mysqli_query($insightDBLink, "SELECT DISTINCT a.ID
					FROM tblPCCostItem a LEFT JOIN tblPCTermReq b ON a.ID = b.PrevCostItemID
					WHERE ((ItemStatus = '$item_status' AND OccupyFlag = 0)
					OR CurrentStep IN ($CI_current_step)) $sql");





			//$query = mysqli_query("SELECT DISTINCT a.ID
			//FROM tblPCCostItem a LEFT JOIN tblPCTermReq b ON a.ID = b.PrevCostItemID
			//WHERE ((ItemStatus = '$item_status' AND OccupyFlag = 0)
			//OR CurrentStep IN ($str_term_step) $sql"); 



			$num = mysqli_num_rows($query);
			while ($value = mysqli_fetch_array($query)) {
				$str_id[] .= $value["ID"];
			}




			$sql_where = "ID in (" . implode(",", $str_id) . ") ";
		} else {
			if (!$s) {
				// 					$query = mysqli_query("SELECT COUNT(*) FROM tblPCCostItem a WHERE ItemStatus = '$item_status' AND OccupyFlag = 0 $sql"); 
				$num = dbSelectColumn($insightDBLink, "SELECT COUNT(*) FROM tblPCCostItem a WHERE ItemStatus = '$item_status' AND OccupyFlag = 0 $sql", 0);
			}

			$sql_where = "ItemStatus = '$item_status' AND OccupyFlag = 0  $sql";
		}


		$sql_full = "SELECT ID, VendorCode, Description, CostType, CostCat, StartDate, EndDate, BillingPeriod, Currency, Quantity, UnitCost, UOM, SignedCo
			FROM tblPCCostItem a WHERE $sql_where ORDER BY ID LIMIT $s, $QPerTime";
		$query = mysqli_query($insightDBLink, $sql_full);
		//echo $sql_full;
		while ($value = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
			$value["CostCat_name"] = (array_key_exists($value["CostCat"], $CostCat_name)) ? $CostCat_name[$value["CostCat"]] : "";
			$value["VendorCode_name"] = $value["VendorCode"] ? ($VendorCode_name[$value["VendorCode"]] ?? '') : "";
			$value["CostType_name"] = $CostType_name[$value["CostType"]];
			$value["BillingPeriod_name"] = $BillingPeriod_name[$value["BillingPeriod"]];
			$value["UOM_name"] = $UOM_name[$value["UOM"]];
			$value["CIID"] = CIID($value["ID"]);
			$value["SignedCo_name"] = $SignedCo_name[$value["SignedCo"]];
			$showlist[] = $value;
		}
	}

	$sql = "";
	echo '{ "num": "' . $num . '", "data": [' . "\n";
	$recordfirst = true;
	foreach ((array) $showlist as $record) {
		if ($recordfirst) {
			$recordfirst = false;
		} else {
			echo ",\n";
		}
		$datafirst = true;
		echo '{';
		foreach ($record as $key => $value) {
			if ($datafirst) {
				$datafirst = false;
			} else {
				echo ",\n";
			}
			echo '"' . $key . '":"' . str_replace("\r\n", "\\r\\n", addslashes((string) $value)) . '"';
		}
		echo '}';
	}
	echo "\n]}";
	exit();
}
/* Respone - END */


/* Preload Data for searching */
if ($p == 'sr' || $p == 'pr_sr') {
	$query = mysqli_query($insightDBLink, "select Engineer as value, CASE WHEN Staffnickname = '' THEN concat( StaffNameLast, ', ', StaffNameFirst )  ELSE concat( StaffNameLast, ', ', StaffNameFirst, ', ', Staffnickname )  END AS name from tblSale left outer join tblStaffInfo on Engineer = tblStaffInfo.LoginName WHERE Engineer <> '' and SaleStatusID IN ('CL', 'IM', 'OP', 'PD', 'PR') group by value, name order by name");
	while ($value = mysqli_fetch_array($query)) {
		$pm_name[] = $value;
	}
} elseif ($p == "vendor") {
	$query = mysqli_query($insightDBLink, "SELECT ID, GroupName FROM tblVendorGps ORDER BY GroupName");
	while ($value = mysqli_fetch_array($query)) {
		$lst_vendor_group[] = $value;
	}

	$vendor_type_list = loadVendorType($sql);
} else if ($p == 'gl') {

	// $logger->info('gl 2 start');
	// $EPI_HOSTNAME ="192.168.80.96";
	// $EPI_USERNAME ="for_scripts";
	// $EPI_PASSWORD ="5h45tr4";
	//	$query = mysqli_query("SELECT lpad(CoCode, 3, '0') FROM tblRegisteredCo LEFT OUTER JOIN tblCo_Entities ON SignedCO = tblRegisteredCo.ID WHERE ID ='$co'");
	$EPI_DB_NAME = "BTN" . dbSelectColumn($insightDBLink, "SELECT lpad(CoCode, 3, '0') FROM tblRegisteredCo LEFT OUTER JOIN tblCo_Entities ON SignedCO = tblRegisteredCo.ID WHERE ID ='$co'", 0);
	$x = dbSqlSrvConnect($EPI_HOSTNAME, $EPI_DB_NAME, $EPI_USERNAME, $EPI_PASSWORD);

	// $logger->info('EPI_DB_NAME'.$EPI_DB_NAME);
	// $logger->info('co'.$co);
	// $logger->info('x'.$x);

	$query = sqlsrv_query($x, "select seg_code as value, seg_code + ' - ' + description as name from glseg1");
	while ($value = sqlsrv_fetch_array($query)) {
		$glseg1[] = $value;
	}

	$query = sqlsrv_query($x, "select seg_code as value, seg_code + ' - ' + description as name from glseg2");
	while ($value = sqlsrv_fetch_array($query)) {
		$glseg2[] = $value;
	}
	sqlsrv_close($x);
} else if ($p == 'oc' || $p == 'rfq_oc' || $p == 'tm') {
	if ($p == 'oc' || $p == 'rfq_oc') {
		$signed_co = floor($signed_co);
		// 		$query = mysqli_query("SELECT CASE WHEN Branch IS NULL THEN CoName ELSE concat(CoName, ' - ', Branch) END as CoName From tblRegisteredCo where ID = ".$signed_co);
		$signed_co_name = dbSelectColumn($insightDBLink, "SELECT CASE WHEN Branch IS NULL THEN CoName ELSE concat(CoName, ' - ', Branch) END as CoName From tblRegisteredCo where ID = " . $signed_co, 0);

		$query = mysqli_query($insightDBLink, "SELECT CoName as name, EpicorCode as value FROM tblVendor");
		while ($value = mysqli_fetch_array($query)) {
			$VendorCode_name[$value['value']] = $value['name'];
		}
	}

	$query = mysqli_query($insightDBLink, "SELECT ID, CASE WHEN Branch IS NULL THEN CoName ELSE concat(CoName, ' - ', Branch) END as CoName From tblRegisteredCo where Active_Purchase=1");
	while ($value = mysqli_fetch_array($query)) {
		$co_list[] = $value;
	}

	// $query = mysqli_query("SELECT CodeValue as value, Description as name FROM tblGenericCode WHERE CodeType = 'VendorType' $sql ORDER BY Sequence");
	// while($value = mysqli_fetch_array($query)) {
	// $vendor_type_list[] = $value;
	// }
	$vendor_type_list = loadVendorType($sql);

	$query = mysqli_query($insightDBLink, "SELECT tblStaffGps.LoginName as value, 
		CASE WHEN Staffnickname = ''
		THEN concat( StaffNameLast, ', ', StaffNameFirst ) 
		ELSE concat( StaffNameLast, ', ', StaffNameFirst, ', ', Staffnickname ) 
		END AS name
		FROM tblStaffGps
		LEFT OUTER JOIN tblStaffInfo ON tblStaffGps.LoginName = tblStaffInfo.LoginName
		WHERE DateQuit is NULL AND GroupID='PCC' ORDER BY name ASC");
	while ($value = mysqli_fetch_array($query)) {
		$requester_list[] = $value;
	}
}

function loadVendorType($sql)
{
	$list = [];
	global $insightDBLink;
	$query = mysqli_query($insightDBLink, "SELECT CodeValue as value, Description as name FROM tblGenericCode WHERE CodeType = 'VendorType' $sql ORDER BY Sequence");
	while ($value = mysqli_fetch_array($query)) {
		$list[] = $value;
	}

	return $list;
}

/* End */


?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
	<title>Selector</title>
	<style>
		body {
			margin: 0;
			padding: 0;
		}

		#nav {
			background: #DDD;
			border-bottom: #777 3px solid;
			line-height: 30px;
			padding: 0 5px;
		}

		// #nav select { width: 200px; }
		#dataNum {
			font-size: 10px;
			color: #222;
		}

		#loading {
			float: right;
			height: 30px;
			margin: 1px;
			vertical-text: middle;
			line-height: 30px;
		}

		#maintable {
			width: 100%;
			margin: 0;
			padding: 0;
			font-size: 14px;
			line-height: 20px;
			border: 1px #000 solid;
		}

		#maintable thead {
			background: #888;
			color: #FFF;
			font-weight: bold;
		}

		#maintable tbody {
			background: #FFF;
			margin: 0;
			padding: 0
		}

		#maintable tbody tr:hover {
			cursor: pointer;
			background: #446;
			text-decoration: underline;
			color: #FFF;
		}

		#maintable tbody td {
			padding: 2px;
		}

		.tr1 {
			background: #DDE
		}

		.tr2 {
			background: #FFF
		}

		.code {
			font-weight: bold;
		}

		.status {
			text-align: right;
		}

		.message {
			margin: 0 auto;
			padding: 240px 0;
			text-align: center;
			border: 30px solid #CCC;
			background: #EEEEEE;
			font-size: 32px;
			font-weight: bold;
		}
	</style>
	<!-- <script type="text/javascript" src="../MochiKit.js"></script> -->
	<script type="text/javascript" src="mochikit/MochiKit.js"></script>
	<script type="text/javascript" src="common.js"></script>
	<script>
		function setText(v) {
			console.log("setText:" + v);
			<?php //modified by Fung 2010-07-29
			if ($p != "tm" && $p != "vendor" && $f != "ISGL") : ?>
				var index = <?php echo ($i == null ? "null" : $i); ?>;
				getObject("<?php echo $f; ?>", index, "opener").value = v;
				if (getObject("<?php echo $f; ?>", index, "opener").onchange) getObject("<?php echo $f; ?>", index, "opener").onchange();
			<?php endif; ?>
			var items = setText.arguments.length;
			var temp = [];

			if (items > 1) {
				for (var i = 1, o; o = setText.arguments[i]; i++) {
					console.log("o:" + o);
					temp[o[0]] = o[1];
				}
				<?php if ($p == 'oc') : ?>
					opener.postPopAction_oc(index, temp);
					var k = 1;
					while (k < 600) k++;
				<?php elseif ($p == 'rfq_oc') : ?>
					stopSearch();
					var win_obj = this;
					opener.postPopAction_oc(index, temp, win_obj);
				<?php elseif ($p == 'tm') : ?>
					console.log("POP - postPopAction_tm:" + temp);
					opener.postPopAction_tm(temp);
				<?php
				elseif ($p == 'gl' && $f != "ISGL") : ?>
					opener.set_wo_name(index, temp['account_type'] == 150);

				<?php elseif ($p == 'gl' && $f == "ISGL") : ?>
					opener.setISGLCode('<?php echo $i; ?>', v);
				<?php
				elseif ($p == "vendor" && $cost == 1) : ?>
					opener.setVendorCost(temp, <?php echo $num; ?>);
					//opener.hideReachCostType(temp['vendor_type'], <?php echo $num; ?>);   
					//opener.hideDiscountUnitCost(<?php echo $num; ?>);                 

				<?php elseif ($p == "vendor") : ?>
					opener.setVendor(temp, '<?php echo $Code; ?>', '<?php echo $Name; ?>');
				<?php endif; ?>
			}
			<?php if ($p != 'rfq_oc') : ?>
				window.close();
			<?php endif; ?>
		}


		var timer;
		var loadingqry = '';
		var totalnum = 0;
		var d;

		function stopSearch() {
			if (d) d.cancel();
			if (timer) clearTimeout(timer);
			$('loading').style.display = "none";
			loadingqry = null;
			return false;
		}


		function clearTbody() {
			/*
			    for(var o;o=$('tablebody').firstChild;){
			        o.onclick="";
			        $('tablebody').removeChild(o);
			    }
			*/
			var newEl = document.createElement($('tablebody').nodeName);
			newEl.id = $('tablebody').id;
			newEl.className = $('tablebody').className;
			newEl.innerHTML = "";
			$('tablebody').parentNode.replaceChild(newEl, $('tablebody'));
		}


		function loadData(s, a) {
			if (!s) {
				stopSearch();
				$('loading').style.display = "";
				clearTbody()
				loadingqry = '';
				<?php if ($p == 'gl') : ?>
					loadingqry += '&co=<?php echo $co; ?>';
					loadingqry += '&glseg1=' + $('glseg1').value;
					loadingqry += '&glseg2=' + $('glseg2').value;
					loadingqry += '&EPI_DB_NAME=<?php echo $EPI_DB_NAME; ?>';
					//alert(loadingqry);
				<?php elseif ($p == "vendor") : ?>
					loadingqry += '&type=' + document.Input.type.value;
					loadingqry += '&epicor_code=' + document.Input.txt_epicor_code.value;
					loadingqry += '&vendor_group=' + document.Input.txt_vendor_group.value;
					loadingqry += '&vendor_type=' + document.Input.opt_vendor_type.value;
					loadingqry += '&vendor_name=' + rawurlencode(document.Input.txt_vendor_name.value);
					loadingqry += '&addr=' + rawurlencode(document.Input.txt_addr.value);
					<?php if ($CR_filtered == "1") : ?> loadingqry += '&CR_filtered=1';
					<?php endif; // add by Fung #8356 filter CR_filtered>0 in tblVendor for IS
					?>
<?php elseif ($p == 'sr' || $p == 'pr_sr') : ?>
					loadingqry += '&co_name=' + $('co_name').value;
					loadingqry += '&pm_name=' + $('pm_name').value;
				<?php elseif ($p == 'oc' || $p == 'rfq_oc' || $p == 'tm') : ?>
					if (isset($('item_status'))) loadingqry += '&item_status=' + $('item_status').value;
					loadingqry += '&signed_co=' + $('signed_co').value;
					loadingqry += '&requester_name=' + $('requester_name').value;
					loadingqry += '&vendor_type=' + $('vendor_type').value;
					loadingqry += '&VendorCode=' + $('VendorCode').value;
					loadingqry += '&pro_no=' + $('pro_no').value;
					loadingqry += '&desc=' + $('desc').value;
					loadingqry += '&SRno=' + $('SRno').value;
					loadingqry += '&usn=' + $('usn').value;
					if (isset($('txt_po_num'))) loadingqry += '&po_num=' + $('txt_po_num').value;
				<?php endif; ?>

				rNum = 0;
				totalnum = 0;
			} else {

			}
			// alert(loadingqry);
			// alert('<?php echo $script_name; ?>?action=search&p=<?php echo $p; ?>&s='+s+loadingqry);
			// alert(s);
			d = loadJSONDoc('<?php echo $script_name; ?>?action=search&p=<?php echo $p; ?>&s=' + s + loadingqry);

			d.addCallback(function(data) {
				if (!s) {
					totalnum = Math.floor(data.num);
					loadingqry += '&num=' + totalnum;
				}
				var needagain = rNum < totalnum ? true : false;

				// 2020 debug start
				console.log("needagain:" + needagain);
				console.log("rNum:" + rNum);
				console.log("totalnum:" + totalnum);
				// 2020 debug end

				if (totalnum > 10000 && !s && !a) {
					var tr1 = TR({
						'onclick': 'loadData(0,1)',
						'style': 'background:#FFF;color:#000'
					});
					var td1 = TD({
						'colspan': 12,
						'style': 'text-align:center'
					});
					appendChildNodes($('tablebody'), tr1);
					appendChildNodes(tr1, td1);
					td1.innerHTML = "<p style=\"font-weight: bold\">There are <span style=\"color:red\">" + totalnum + "</span> records!</p><p>(Please specify the searching conditions)</p>";
					stopSearch();
				} else if (!totalnum && !s) {
					var tr1 = TR({
						'style': 'background:#FFF;color:#000'
					});
					var td1 = TD({
						'colspan': 12,
						'style': 'text-align:center'
					});
					appendChildNodes($('tablebody'), tr1);
					appendChildNodes(tr1, td1);
					td1.innerHTML = "No record avaliable!";
					stopSearch();
				} else {
					/*
					    http://www.lslnet.com/linux/f/docs1/i52/big5353082.htm
					    Speed Enhancement according to the above article
					    (1) Create TR
					    (2) Create TD
					    (3) Create Text 
					    (4) Insert TR into Tbody
					    (5) Insert TD into TR
					    (6) Insert Text into TD (Using innerHTML is better than innerHTML)
					*/
					forEach(data.data, function(data) {
						if (rNum >= totalnum) return;
						var row = TR({
							'class': 'tr' + rNum % 2
						});
						<?php /* Result Show */ ?>
						<?php if ($p == 'sr' || $p == 'pr_sr') :    ?>
							row.onclick = new Function("setText('" + data.SaleID + "')");
							var td1 = TD({
								'class': 'code'
							});
							var td2 = TD(null);
							var td3 = TD(null);
							appendChildNodes($('tablebody'), row);
							appendChildNodes(row, td1, td2, td3);
							td1.innerHTML = data.SaleID;
							td2.innerHTML = data.CustomerName;
							td3.innerHTML = data.StatusName;
						<?php elseif ($p == "vendor") : ?>
							var temp = '';
							for (var key in data) {
								data_key = data[key];
								data_key = data_key.replace(/\\/g, '\\\\');
								data_key = data_key.replace(/'/g, '\\\'');
								data_key = data_key.replace(/\r\n/g, '\\r\\n');
								if (isNaN(key)) temp += ", ['" + key + "', '" + data_key + "']";
							}
							// alert(temp);
							row.onclick = new Function("setText('" + data.EpicorCode + "'" + temp + ")");
							var td1 = TD({
								'class': 'code'
							});
							var td2 = TD(null);
							var td3 = TD(null);
							var td4 = TD(null);
							var td5 = TD(null); //modified by Fung #6455 add GroupID
							appendChildNodes($('tablebody'), row);
							appendChildNodes(row, td1, td2, td3, td4, td5); //modified by Fung #6455 add GroupID
							td1.innerHTML = data.EpicorCode;
							td2.innerHTML = data.CoName.replace(/&/g, '&amp;');
							td3.innerHTML = data.Address;
							td4.innerHTML = data.GroupName;
							td5.innerHTML = data.ID; //modified by Fung #6455 add GroupID
						<?php elseif ($p == 'gl') :  ?>
							var temp = '';
							for (var key in data) {
								if (isNaN(key)) temp += ", ['" + key + "', '" + data[key] + "']";
							}
							row.onclick = new Function("setText('" + data.account_code_display + "'" + temp + ")");
							var td1 = TD({
								'class': 'code'
							});
							var td2 = TD(null);
							appendChildNodes($('tablebody'), row);
							appendChildNodes(row, td1, td2);
							td1.innerHTML = data.account_code_display;
							td2.innerHTML = data.account_description;
						<?php elseif ($p == 'oc' || $p == 'rfq_oc' || $p == 'tm') :  ?>
							var temp = '';
							var data_key;
							for (var key in data) {
								data_key = data[key];
								data_key = data_key.replace(/\\/g, '\\\\');
								data_key = data_key.replace(/'/g, '\\\'');
								data_key = data_key.replace(/\r\n/g, '\\r\\n');
								if (isNaN(key)) temp += ", ['" + key + "', '" + data_key + "']";
							}

							// alert(temp);
							row.onclick = new Function("setText('" + data.CIID + "'" + temp + ")");
							var td1 = TD({
								'class': 'code'
							});
							var td2 = TD(null);
							var td3 = TD(null);
							var td4 = TD(null);
							var td5 = TD(null);
							var td6 = TD(null);
							var td7 = TD(null);
							var td8 = TD(null);
							var td9 = TD(null);
							var td10 = TD(null);
							var td11 = TD(null);
							var td12 = TD(null);
							var td13 = TD(null);
							appendChildNodes($('tablebody'), row);
							appendChildNodes(row, td1, td2, td3, td4, td5, td6, td7, td8, td9, td10, td11, td12, td13);
							td1.innerHTML = data.ID;
							td2.innerHTML = data.VendorCode_name;
							td3.innerHTML = data.Description;
							td4.innerHTML = data.CostType_name;
							td5.innerHTML = data.CostCat_name;
							td6.innerHTML = data.StartDate;
							td7.innerHTML = data.EndDate;
							td8.innerHTML = data.BillingPeriod_name;
							td9.innerHTML = data.Currency;
							td10.innerHTML = data.Quantity;
							td11.innerHTML = data.UnitCost;
							td12.innerHTML = data.UOM_name;
							td13.innerHTML = data.SignedCo_name;

						<?php endif;  ?>
						<?php /* Result Show - END */ ?>
						rNum++;
					});
					$('dataNum').innerHTML = rNum + "/" + totalnum;
					if (needagain) loadData(rNum);
					else stopSearch();
				}


			});

			d.addErrback(function(data) {
				<?php if ($debugmode) : ?>alert(data);
			<?php endif; ?>
			});

			return false;

		}

		function rawurlencode(str) {
			var hexStr = function(dec) {
				return '%' + dec.toString(16).toUpperCase();
			};

			var ret = '',
				unreserved = /[\w.~-]/; // A-Za-z0-9_.~-
			str = (str + '').toString();

			for (var i = 0, dl = str.length; i < dl; i++) {
				var ch = str.charAt(i);
				if (unreserved.test(ch)) {
					ret += ch;
				} else {
					var code = str.charCodeAt(i);
					// Reserved assumed to be in UTF-8, as in PHP
					if (code < 128) { // 1 byte
						ret += hexStr(code);
					} else if (code >= 128 && code < 2048) { // 2 bytes
						ret += hexStr((code >> 6) | 0xC0);
						ret += hexStr((code & 0x3F) | 0x80);
					} else if (code >= 2048 && code < 65536) { // 3 bytes
						ret += hexStr((code >> 12) | 0xE0);
						ret += hexStr(((code >> 6) & 0x3F) | 0x80);
						ret += hexStr((code & 0x3F) | 0x80);
					} else if (code >= 65536) { // 4 bytes
						ret += hexStr((code >> 18) | 0xF0);
						ret += hexStr(((code >> 12) & 0x3F) | 0x80);
						ret += hexStr(((code >> 6) & 0x3F) | 0x80);
						ret += hexStr((code & 0x3F) | 0x80);
					}
				}
			}
			return ret;
		}

		function ShowSel(file, width) {
			width = width ? width : 800;
			var height = 600;
			var status = 0;
			var scroll = 1;
			window.open(file, '', "toolbar=no,location=no,directories=no,status=" + status + ",scrollbars=" + scroll + ",resizable=yes,copyhistory=no,width=" + width + ",height=" + height + ",left=0, top=0");
		}

		function isset() {
			var a = arguments,
				l = a.length,
				i = 0;

			if (l === 0) {
				throw new Error('Empty isset');
			}

			while (i !== l) {
				if (typeof(a[i]) == 'undefined' || a[i] === null) {
					return false;
				} else {
					i++;
				}
			}
			return true;
		}
	</script>
</head>

<body>
	<div id="nav">
		<div id="loading" style="display: none;"><span id="dataNum"></span><img src='loading.gif' height="24px" /></div>
		<form id='Input' name='Input' onsubmit="return loadData(0);">
			<?php if ($p == 'gl') : ?>
				<select style="width:300px" name="glseg1" id="glseg1">
					<option value=''> --- All Segment 1 --- </option>
					<?php foreach ($glseg1 as $value) : ?>
						<option value="<?php echo $value['value']; ?>"><?php echo $value['name']; ?></option>
					<?php endforeach; ?>
				</select>
				<select style="width:300px" name="glseg2" id="glseg2">
					<option value=''> --- All Segment 2 --- </option>
					<?php foreach ($glseg2 as $value) : ?>
						<option value="<?php echo $value['value']; ?>"><?php echo $value['name']; ?></option>
					<?php endforeach; ?>
				</select>

			<?php elseif ($p == "vendor") : ?>
				<script>
					function setVendor(data) {
						// $('hid_vendor_code').value = data['EpicorCode'];
						document.getElementById('opt_vendor_type').value = data['vendor_type'];
						document.getElementById('txt_vendor_name').value = data['CoName'];
					}
				</script>
				<input type="hidden" name="type" value="<?php echo $type; ?>">
				<table>
					<tr>
						<td>Epicor Vendor Code: </td>
						<td><input type="text" name="txt_epicor_code" id="txt_epicor_code" /></td>
						<td>Vendor Group Name: </td>
						<td>
							<select name="txt_vendor_group" id="txt_vendor_group" onchange="">
								<option value=""> --- All --- </option>
								<?php foreach ($lst_vendor_group as $value) : ?>
									<option value="<?php echo $value["ID"]; ?>"><?php echo $value["GroupName"]; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					<tr>
						<td>Vendor Type: </td>
						<td>
							<select name="opt_vendor_type" id="opt_vendor_type">
								<option value=""> --- Vendor Type --- </option>
								<?php foreach ($vendor_type_list as $value) : ?>
									<option value="<?php echo $value["value"]; ?>" <?php if ($value["value"] == $type) echo "selected=\"selected\"" ?>><?php echo $value["name"]; ?></option>
								<?php endforeach; ?>

								<?php
								//modified by karen 2010-02-18
								if ($p == "vendor" && $cost == 1) : ?>
									<option value="INTERNAL" <?php if ($type == 'INTERNAL') echo "selected"; ?>>Internal</option>
								<?php endif; ?>
							</select>
						</td>
						<td>Vendor Name: </td>
						<td>
							<!-- <input type="hidden" name="hid_vendor_code" /> -->
							<input type="text" name="txt_vendor_name" id="txt_vendor_name" style="width:375px" />
							<!-- <a href="javascript:ShowSel('../../../Lib/<?php echo $script_name; ?>?p=vendor&type='+document.Input.opt_vendor_type.value, 1000)"><img src="/Lib/search.gif" border="0" align="absmiddle" alt="Search"></a> -->
						</td>
					</tr>
					<tr>
						<td>Address: </td>
						<td colspan=4><input type="text" name="txt_addr" id="txt_addr" style="width:688px" /></td>
					</tr>
				</table>

			<?php elseif ($p == 'sr' || $p == 'pr_sr') : ?>
				Customer Name: <input type="text" name="co_name" id="co_name" />
				<select name="pm_name" id="pm_name">
					<option value=''> --- All Project Manager --- </option>
					<?php foreach ($pm_name as $value) : ?>
						<option value="<?php echo $value['value']; ?>"><?php echo $value['name']; ?></option>
					<?php endforeach; ?>
				</select>

			<?php elseif ($p == 'oc' || $p == 'rfq_oc' || $p == 'tm') : ?>
				<script>
					function showVendorSearch(val) {
						document.getElementById('VendorCode').value = '';
						document.getElementById('VendorName').value = '';
						if (val) {
							document.getElementById('VendorSearch').style.display = '';
						} else {
							document.getElementById('VendorSearch').style.display = 'none';
						}
					}

					function setVendor(data) {
						document.getElementById('vendor_type').value = data['vendor_type'];
						document.getElementById('VendorCode').value = data['EpicorCode'];
						document.getElementById('VendorName').value = data['CoName'];
					}

					function checkSRNum(v) {
						if (!v) return false;
						var d = loadJSONDoc('respone.php?p=pr_sr&id=' + v);
						var readdata = function(data) {
							sr_corr = data.value;

							if (sr_corr == '0') alert('SR Number Not found!');
							if (sr_corr == '-1') alert('SR Number status is in Lead, Prospect, Hot Prospect, or Dropped.');

						};
						d.addCallback(readdata);
						var q = getObject('SRno');

						q.value = q.value.replace(/SR|Sr|sR|sr/, '');
						if (isInteger(q.value)) {
							if (q.value.length <= 6) {
								if (q.value != 0)
									q.value = 'SR' + Array(6 + 1 - q.value.length).join('0') + q.value;
								else q.value = '';
							} else q.value = ''
						} else q.value = '';
					}

					function isInteger(s) {
						return (s.toString().search(/^-?[0-9]+$/) == 0);
					}
				</script>
				<table border="0">
					<tr>
						<td>Signed Company: </td>
						<td colspan="3">
							<?php if ($p == "oc") : ?>
								<input type="hidden" name="item_status" id="item_status" value="<?php echo ($item_status ?? ''); ?>">
							<?php endif; ?>
							<?php if ($p == "tm" || ($p == "oc" && ($action_type == "ASSIGNMENT" || $action_type == "REPLACE"))) : ?>
								<select style="width:370px" name="signed_co" id="signed_co">
									<option value=''> --- All Signed Company --- </option>
									<?php foreach ($co_list as $value) : ?>
										<option value="<?php echo $value["ID"]; ?>"><?php echo $value["CoName"]; ?></option>
									<?php endforeach; ?>
								</select>

							<?php else : ?>
								<input type="hidden" name="signed_co" id="signed_co" value="<?php echo $signed_co; ?>"><?php echo ($signed_co_name ?: ""); ?><br />
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td>Vendor Type: </td>
						<td>
							<select name="vendor_type" id="vendor_type" onchange="showVendorSearch(this.value);" <?php echo $vendor_code ? "disabled=\"disabled\"" : ""; ?>>

								<option value=''> --- Vendor Type --- </option>
								<?php foreach ($vendor_type_list as $value) : ?>
									<option value="<?php echo $value['value']; ?>" <?php if ($value['value'] == $vendortype) echo "selected=\"selected\"" ?>><?php echo $value['name']; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>Vendor Name: </td>
						<td>
							<div id="VendorList" name="VendorList">
								<!--
					<select name="VendorCode">
						<option value=""> --- Vendor Name --- </option>
					</select>				
					-->

								<input type="hidden" name="VendorCode" id="VendorCode" value="<?php echo ($vendor_code ?? ""); ?>" />
								<input type="text" name="VendorName" id="VendorName" style="width:375px" disabled="disabled" value="<?php echo $vendor_code ? $VendorCode_name[$vendor_code] : ""; ?>" />
								<a href="javascript:ShowSel('<?php echo $insight_lib_url . "/" . $script_name; ?>?p=vendor&type='+document.Input.vendor_type.value, 1000)" name="VendorSearch" id="VendorSearch" style="<?php echo ($vendortype && !$vendor_code) ? "" : "display: none"; ?>"><img src="<?php echo $insight_lib_url ?>/search.gif" border="0" align="absmiddle" alt="Search"></a>

							</div>
						</td>
					</tr>
					<tr>
						<td>Project Number: </td>
						<td><input type="text" name="pro_no" id="pro_no" /></td>
						<td>Requester: </td>
						<td><select name="requester_name" id="requester_name">
								<option value=''> --- All Requesters --- </option>
								<?php foreach ($requester_list as $value) : ?>
									<option value="<?php echo $value['value']; ?>"><?php echo $value['name']; ?></option>
								<?php endforeach; ?>
							</select></td>
					</tr>
					<tr>
						<td>SR Number: </td>
						<td>
							<input type="text" name="SRno" id="SRno" onchange="checkSRNum(this.value)" />
							<a href="javascript:ShowSel('<?php echo $script_name; ?>?p=pr_sr&f=SRno')"><img src="<?php echo $insight_lib_url ?>/search.gif" border=0 align="absmiddle" alt="Search"></a>
						</td>
						<td>Description: </td>
						<td><input type="text" name="desc" id="desc" /></td>
					</tr>
					<tr>
						<td>Service ID: </td>
						<td><input type="text" name="usn" id="usn" /></td>
					</tr>
					<?php if ($p == "oc") : ?>
						<tr>
							<td>PO Number: </td>
							<td><input type="text" name="txt_po_num" id="txt_po_num" /></td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
					<?php endif; ?>
				</table>

			<?php endif; ?>
			<input type="submit" value="Go">
			<input type="button" value="Stop" onclick="stopSearch();">
			<input type="button" value="Clear" onclick="document.Input.reset();clearTbody();">
		</form>
	</div>
	<table id="maintable" cellpadding="0" cellspacing="0">
		<thead>
			<?php
			if ($p == 'sr' || $p == 'pr_sr') {
				echo '<tr><th class="code">Sale ID</th><th>Customer Name</th><th>Status</th></tr>' . "\n";
			} else if ($p == "vendor") {
				echo '<tr><th class="code">Epicor Vendor Code</th><th>Vendor Name</th><th>Address</th><th>Vendor Group Name</th><th>Vendor Group ID</th></tr>' . "\n";
			} else if ($p == 'gl') {
				echo '<tr><th class="code">Account Code</th><th>Account Description</th></tr>' . "\n";
			} else if ($p == 'oc' || $p == 'rfq_oc' || $p == 'tm') {
				echo '<tr><th class="code">ID</th><th>Vendor Name</th><th>Description</th><th>Cost Type</th><th>Cost Category</th><th>Target Delivery Date</th><th>Target Termination Date</th><th>Billing Type</th><th>Currency</th><th>Qty</th><th>Unit Price</th><th>UOM</th><th>Signed Company</th></tr>' . "\n";
			}
			?>
		</thead>
		<tbody id="tablebody">

		</tbody>
	</table>
	<script>
		loadData(1, 0);
	</script>
</body>

</html>