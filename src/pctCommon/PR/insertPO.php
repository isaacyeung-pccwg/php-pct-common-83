<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/logger.php");
global $logger;
global $insightDBLink;
$logger->info("EPI_DB_NAME=".$EPI_DB_NAME);
$epicor_Conn = dbSqlSrvConnect($EPI_HOSTNAME, $EPI_DB_NAME, $EPI_USERNAME, $EPI_PASSWORD);
$logger->info($epicor_Conn);
$sql = "SELECT home_currency FROM glco";
$query = sqlsrv_query($epicor_Conn, $sql);
$home_curr = sqlsrv_fetch_array($query)[0];
$logger->info("home_curr=$home_curr");
$sql = "SELECT Rate FROM tblExchange WHERE CurrencyFrom = '{$ReqInfo["Currency"]}' AND CurrencyTo = '$home_curr'";
// $query = mysqli_query($insightDBLink, $sql);
$po_home_curr = dbSelectColumn($insightDBLink, $sql, 0);
$logger->info("po_home_curr=$po_home_curr");
$sql = "SELECT Rate FROM tblExchange WHERE CurrencyFrom = '{$ReqInfo["Currency"]}' AND CurrencyTo = 'USD'";
// $query = mysqli_query($insightDBLink, $sql);
$po_usd_curr = dbSelectColumn($insightDBLink, $sql, 0);
$logger->info("po_usd_curr=$po_usd_curr");
$sql = "SELECT Description FROM tblGenericCode WHERE CodeType = 'ParaDef' and CodeCat = 'POPosting' and CodeValue = '{$ReqInfo["SignedCo"]}'";
// $query = mysqli_query($insightDBLink, $sql);
$post_code = dbSelectColumn($insightDBLink, $sql, 0);
$logger->info("post_code=$post_code");
$sql = "SELECT last_no+1 AS next_po_no FROM next_po_no";
$query = sqlsrv_query($epicor_Conn, $sql);
$next_po_no = sqlsrv_fetch_array($query)[0];
$logger->info("next_po_no=$next_po_no");
$sql = "UPDATE next_po_no SET last_no = last_no + 1";
$query = sqlsrv_query($epicor_Conn, $sql);

/* from tblPCReqHeader to BTN000..purchase*/
$sql = "INSERT INTO purchase (
	po_no, 
	amt_blnk_limit, approved_by, attn, blanket, buyer, curr_factor, curr_key, 
	date_blnk_from, date_blnk_to, date_of_order, date_order_due, 
	discount, fob, location, 
	oper_factor, phone, po_ext, po_key, 
	po_type, posting_code, printed, prod_no, rate_type_home, rate_type_oper, reference_code, requested_by, 
	ship_address1, ship_address2, 
	ship_address3, ship_address4, 
	ship_address5, ship_name, 
	ship_city, ship_state, ship_zip, 
	ship_to_no, ship_via, status, tax_code, terms, 
	total_tax, vend_order_no, vendor_no, 
	blanket_flag, expedite_flag, user_category, user_code, who_entered, 
	freight, prepaid_amt, void
) VALUES (
	'PO' + SUBSTRING('$company_code', 2, 3) + REPLICATE('0', 7 - LEN('$next_po_no')) + CAST('$next_po_no' AS varchar), 
	NULL, NULL, NULL, 'N', '', $po_home_curr, '{$ReqInfo["Currency"]}', 
	NULL, NULL, '$dateFormat', '$dateFormat', 
	0, 'na', '{$ReqInfo["POLoc"]}', 
	$po_usd_curr, '', 0, $next_po_no, 
	'XX', '$post_code', 'N', 0, 'BUY', 'BUY', '', NULL, 
	'".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add1"], 0, 40))."', '".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add2"], 0, 40))."', 
	'".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add3"], 0, 40))."', '".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add4"], 0, 40))."', 
	'".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add5"], 0, 40))."', '".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Name"], 0, 40))."', 
	NULL, NULL, NULL, 
	'{$ReqInfo["ShipToLoc"]}', 'na', 'O', 'na', '{$ReqInfo["TermsCode"]}', 
	0, NULL, '{$ReqInfo["VendorCode"]}', 
	0, 0, '', 'OPEN', 'INSIGHT\\$PHP_AUTH_USER', 
	0, 0, 'N'
)";
$query = sqlsrv_query($epicor_Conn, $sql);

if ($query) {
	for ($i = 0; $i < $Detailnum; ++$i) {
		if (str_starts_with($ReqInfo["CostType"], "COS")) {
			$vend_sku = firstCharNumeric($ReqDetail[$i]["SRNum"]);
		} else if (str_starts_with($ReqInfo["CostType"], "NET")) {
			$vend_sku = firstCharNumeric($ReqDetail[$i]["ProjectNum"]);
		} else {
			$vend_sku = "";
		}
		
		$part_no = $ReqDetail[$i]["ItemCode"].($ReqDetail[$i]["PartNum"]? " - ".str_replace("'", "''", $ReqDetail[$i]["PartNum"]): "");
		if (strlen($part_no) > 30) {
			if (strlen($part_no) < 33) {
				$part_no = $ReqDetail[$i]["ItemCode"].($ReqDetail[$i]["PartNum"]? "-".str_replace("'", "''", $ReqDetail[$i]["PartNum"]): "");
			} else {
				$part_no = substr($part_no, 0, 30);
			}
		}
		
		/* from tblPCReqDetail to BTN000..pur_list*/
		$sql = "INSERT INTO pur_list (
			po_no, 
			part_no, location, type, vend_sku, 
			account_no, description, 
			unit_cost, unit_measure, rel_date, 
			qty_ordered, qty_received, who_entered, 
			status, conv_factor, lb_tracking, line, 
			taxable, prev_qty, po_key, 
			weight_ea, tax_code, total_tax, curr_cost, 
			oper_cost, reference_code, project1, project2, project3, tolerance_code, 
			shipto_code, receiving_loc, 
			shipto_name, addr1, 
			addr2, addr3, 
			addr4, addr5, 
			ext_cost, void
		) VALUES (
			'PO' + SUBSTRING('$company_code', 2, 3) + REPLICATE('0', 7 - LEN('$next_po_no')) + CAST('$next_po_no' AS varchar), 
			'$part_no', '{$ReqInfo["ShipToLoc"]}', 'M', '".str_replace("'", "''", substr($vend_sku, 0, 30))."', 
			'{$ReqDetail[$i]["GLCode"]}', '".str_replace("'", "''", substr($ReqDetail[$i]["Description"], 0, 255))."', 
			".($ReqDetail[$i]["UnitCost"]*$po_home_curr).", 'EA', '$dateFormat', 
			{$ReqDetail[$i]["Quantity"]}, 0, '$PHP_AUTH_USER', 
			'O', 1, 'N', {$ReqDetail[$i]["LineNum"]}, 
			1, NULL, $next_po_no, 
			0, 'na', 0, {$ReqDetail[$i]["UnitCost"]}, 
			".($ReqDetail[$i]["UnitCost"]*$po_usd_curr).", '', '', '', '', NULL, 
			'{$ReqInfo["ShipToLoc"]}', '{$ReqInfo["ShipToLoc"]}', 
			'".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Name"], 0, 40))."', 
			'".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add1"], 0, 40))."', 
			'".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add2"], 0, 40))."', 
			'".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add3"], 0, 40))."', 
			'".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add4"], 0, 40))."', 
			'".str_replace("'", "''", substr($ReqInfo["ShipToLoc_Add5"], 0, 40))."', 
			0, 'N'
		)";
		$query = sqlsrv_query($epicor_Conn, $sql);
		
		/* from tblPCReqDetail to BTN000..releases*/
		$sql = "INSERT INTO releases (
			po_no, 
			part_no, location, 
			status, lb_tracking, conv_factor, po_key, 
			part_type, release_date, due_date, 
			po_line, quantity, 
			received, prev_qty, 
			confirm_date, confirmed
		) VALUES (
			'PO' + SUBSTRING('$company_code', 2, 3) + REPLICATE('0', 7 - LEN('$next_po_no')) + CAST('$next_po_no' AS varchar), 
			'$part_no', '{$ReqInfo["ShipToLoc"]}',
			'O', 'N', 1, $next_po_no, 
			'M', '$dateFormat', '$dateFormat',
			{$ReqDetail[$i]["LineNum"]}, {$ReqDetail[$i]["Quantity"]},
			0, {$ReqDetail[$i]["Quantity"]},
			getdate(), 'N'
		)";
		$query = sqlsrv_query($epicor_Conn, $sql);
	}

	$sql = "UPDATE purchase SET total_amt_order = (
		SELECT SUM(curr_cost * qty_ordered) FROM pur_list WHERE po_key = '$next_po_no'
	) WHERE po_key= '$next_po_no'";
	$query = sqlsrv_query($epicor_Conn, $sql);
}

sqlsrv_close($epicor_Conn);

$new_po_no = "PO".substr($company_code, -2).str_pad($next_po_no, 7, "0", STR_PAD_LEFT);

$sql = "UPDATE tblPCReqHeader SET PONum = '$new_po_no' WHERE ID = $ID";
$success = mysqli_query($insightDBLink, $sql);
if ($success) {
	$ReqInfo["PONum"] = $new_po_no;
	mysqli_query($insightDBLink, "INSERT INTO tblPCEventLog ( LevelInd , ReferenceID , LogDate , LogUser , DocType, Action , Description , Attachment ) 
				VALUES ('ReqHeader', '$ID',  '$dateFormat', '$PHP_AUTH_USER', 'STATUS', 'POCREATE', '$new_po_no has been successfully created in Epicor', NULL)");
}
