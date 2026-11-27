<?php
//file holder task 18583 Joan Ng 24 May 2013
//file holder task 19910 Joan Ng 2 Sep 2013
//file holder task  Alvin Leung 6 Aug 2014
//file holder task  Kavis 2015-06-12 29830
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");
include_once($insight_helper_path . "/db_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");
include_once($insight_phpInc_path . "/email.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/logger.php");

function loadRequesterName($region) {
	global $insightDBLink;
	/*
		load the list of pr requester names (PCC in tblStaffGps)
		retrun login name and full name
		input parameter $region (requester group) is optional
	*/
	$sql = "SELECT tblStaffGps.LoginName, CASE WHEN Staffnickname = ''
		THEN concat( StaffNameLast, ', ', StaffNameFirst ) 
		ELSE concat( StaffNameLast, ', ', StaffNameFirst, ', ', Staffnickname ) 
		END AS full_name 
		FROM tblStaffGps
		LEFT OUTER JOIN tblStaffInfo ON tblStaffGps.LoginName = tblStaffInfo.LoginName		
		WHERE GroupID = 'PCC' ";
	if ($region) $sql .= "AND Region = '$region' ";
	$sql .= "ORDER BY full_name ASC";
	$query = mysqli_query($insightDBLink, $sql); 
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$name_list[] = $value;
		}
		return $name_list;
	} else {
		return false;
	}
}

// 202007: migration
// function connectEpicor($signed_co) {
// 	global $EPI_HOSTNAME, $EPI_USERNAME, $EPI_PASSWORD;
// 	$conn = mssql_connect($EPI_HOSTNAME, $EPI_USERNAME, $EPI_PASSWORD);
	
// 	if ($conn) {
// 		if ($signed_co) {
// 			$epi_co_code = findEpicorCompanyCode($signed_co); 
// 			$epi_db_name = "BTN".$epi_co_code;
// 			$db_selected = mssql_select_db($epi_db_name, $conn);
			
// 			if ($db_selected) {
// 				return $conn;
// 			} else {
// 				return false;
// 			}
// 		} else {
// 			return $conn;
// 		}
// 	} else {
// 		return false;
// 	}
// }

function stripPOID($id) {
	$id = str_ireplace("PO", "", $id);
	$id = substr($id, 2);
	$id = ltrim($id, 0);
	return $id;
}

function getCompanyCodeFromPOID($id) {
	$company_code = substr($id, 2, 2);
	return $company_code;
}

function ConsultancyServicesAvailable($login_name) {
	global $insightDBLink;
	// check pcc setting
	$sql = "SELECT CodeValue FROM tblGenericCode WHERE CodeType = 'ConsultSer' AND CodeCat = 
		(SELECT Region FROM tblStaffGps WHERE GroupID = 'PCC' AND LoginName = '$login_name')";
	$query = mysqli_query($insightDBLink, $sql);
	if (!$query) return false;
	
	if (dbSelectColumn($insightDBLink, $sql, 0)) {
		return true;
	} else {
		// check pcv setting
		$sql = "SELECT Region FROM tblStaffGps WHERE GroupID = 'PCV' AND LoginName = '$login_name'";
// 		$query = mysqli_query($insightDBLink, $sql);
		$regions = dbSelectColumn($insightDBLink, $sql, 0);
		if (!$regions) return false;
		
		if (preg_match("/HR|ALL/i", $regions)) {
			return true;
		} else {
			return false;
		}
	}
}

function isCostItemReceived($pr_id) {
	global $insightDBLink;
	$sql = "SELECT COUNT(*) 
		FROM tblPCCostItem LEFT JOIN tblPCReqDetail ON tblPCReqDetail.ID = tblPCCostItem.ReqDetailID 
		WHERE tblPCReqDetail.ReqID = $pr_id AND tblPCCostItem.ItemStatus <> 'Ordered'";
	
	if (dbSelectColumn($insightDBLink, $sql, 0)) {
		return true;
	} else {
		return false;
	}
}

function firstCharNumeric($str_chk) {
	if (strlen($str_chk)) {
		$int_len = strlen($str_chk);
		
		for ($i=0; $i<$int_len; $i++) { 
			$chr_chk = substr($str_chk, 0, 1); 
			if (is_numeric($chr_chk)) {
				if ($chr_chk != 0) {
					return $str_chk;
				} else {
					$str_chk = substr($str_chk, 1); 
				}
			} else {
				$str_chk = substr($str_chk, 1); 
			}
		}
	}
}

function findEmailRecipients($pr_id) {
	global $insightDBLink;
	//$sql = "SELECT DISTINCT a.ActionUser
		//FROM tblPCActFlow a, tblISInvLine i
		//WHERE a.LevelInd = 'IS'
		//AND a.ReferenceID = i.InvoiceID
		//AND a.Sequence = 2
		//AND i.PRLineID IN (SELECT ID FROM tblPCReqDetail WHERE ReqID = $pr_id)";
	$sql = "SELECT DISTINCT a.ActionUser
                              FROM tblPCActFlow a, tblISInvLine i, tblPCReqDetail r
                              WHERE a.LevelInd = 'IS'
                              AND a.ReferenceID = i.InvoiceID
                              AND a.Sequence <= 2
                              AND r.ID = i.PRLineID
                              AND r.ReqID = $pr_id";

	
	
	
	
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$recipients[] = $value["ActionUser"];
		}
		return $recipients;
	} else {
		return false;
	}
}

function findEmailRecipients_new($pr_id) {
	global $insightDBLink;
	//$sql = "select CreateUser  from tblPCReqHeader where ID  = '$pr_id'";
	$sql = "select ActionUser from tblPCActFlow where LevelInd = 'PRReq' and Sequence = '2' and ReferenceID = '$pr_id'";
	
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$recipients[] = $value["ActionUser"];
		}
		return $recipients;
	} else {
		return false;
	}
}

function findEmailCC_new($pr_id) {
	global $insightDBLink;
	$sql = "select ActionUser from tblPCActFlow where LevelInd = 'PRReq' and Sequence = '3' and ReferenceID = '$pr_id'";
		
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$recipients[] = $value["ActionUser"];
		}
		return $recipients;
	} else {
		return false;
	}
}

function isPRAutoGen($pr_id) {
	global $insightDBLink;
	$sql = "select * from tblPCActFlow where LevelInd = 'PRReq' and ActionUser = 'auto' and ReferenceID = '$pr_id'";

	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		return mysqli_num_rows($query)>0?true:false;
	} else {
		return false;
	}
}

function getActFlow($pr_id) {
	global $insightDBLink;
	$sql = "SELECT Sequence, ActionUser FROM tblPCActFlow WHERE LevelInd = 'PRReq' AND ReferenceID = $pr_id";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$act_flow[$value["Sequence"]] = $value["ActionUser"];
		}
		return $act_flow;
	} else {
		return false;
	}
}

function getRecipientEmails($recipient_ids) {
	global $insightDBLink, $logger;
	$logger->info("getRecipientEmails");
	$str_ids = is_array($recipient_ids) ? "'".implode("','", $recipient_ids)."'" : "'$recipient_ids'";
	$sql = "SELECT Email FROM tblStaffInfo WHERE LoginName IN ($str_ids)";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) { 
			$recipient[] = $value["Email"];
		}
		return $recipient;
	} else {
		return false;
	}
}

function findSignedCoName($signed_co) {
	global $insightDBLink;
	$sql = "SELECT CASE WHEN Branch IS NULL THEN CoName ELSE CONCAT(CoName, ' - ', Branch) END AS CoName  
		FROM tblRegisteredCo WHERE ID = $signed_co"; 
	return dbSelectColumn($insightDBLink, $sql, 0);
}

function findVendorName($vendor_code) {
	global $insightDBLink;
	$sql = "SELECT CoName FROM tblVendor WHERE EpicorCode <> '' AND EpicorCode = '$vendor_code'";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$full_name = $value["CoName"];
		} 
		return $full_name;
	} else {
		return false;
	}			
}

function findCurrencyName($currency) {
	global $insightDBLink;
	$sql = "SELECT Description FROM tblGenericCode WHERE CodeType='CurrCode' and CodeValue = '$currency'";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$full_name = $value["Description"];
		} 
		return $full_name;
	} else {
		return false;
	}			
}

function findInvoiceId($pr_id) {
	global $insightDBLink;
	$sql = "SELECT InvoiceID FROM tblISInvLine
		WHERE PRLineID IN (SELECT ID FROM tblPCReqDetail WHERE ReqID = $pr_id)";
	return dbSelectColumn($insightDBLink, $sql, 0);
}

function sendGenericEmail($recipient, $subject, $message, $cc) { 
	// require phpInc/config.inc.php
	global $webhynet_email;
	global $intranet_naming;
    global $logger;

    $logger->info("sendGenericEmail() start");

	$title_fr = "$intranet_naming System";
	$title_to = $recipient;
	$title_to[count($title_to)-1] = implode(", ", $title_to);

    if (is_array($title_to) && sizeof($title_to) > 0) {
      if (!smtp_mailcc($recipient, $title_to, $subject, $message, $webhynet_email, $title_fr, $cc))
        echo "<br>could not send email";
    } else {
      $logger->info("sendGenericEmail() skipped");
    }
}

function ISID($value) {
	return "IS".sprintf("%08d", $value);
}

function getGenericeCode() {
	global $insightDBLink;
	$sql = "SELECT CodeType, CodeValue, Description FROM tblGenericCode ORDER BY ID";
	$query = mysqli_query($insightDBLink, $sql);
	while ($value = mysqli_fetch_array($query)) {
		$generic_code[$value["CodeType"]][$value["CodeValue"]] = $value["Description"];
	}
	
	return $generic_code;
}

function getPRHeaderInfo($pr_id) {
	global $insightDBLink;
	$sql = "SELECT * FROM tblPCReqHeader WHERE ID = $pr_id";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		$pr_header = mysqli_fetch_array($query);
		return $pr_header;
	} else {
		return false;
	}	
}

function getPRDetailWithNewCostItem($pr_id) {
	global $insightDBLink;
	$sql = "SELECT d.OrderType, d.LineNum, d.UOM, c.ID, c.Description, c.UnitCost, c.Quantity, c.PrevID, 
		c.ItemCode, c.Make, c.ModelNum, c.PartNum, c.SerialNum, c.ReadyDate, c.StartDate, c.EndDate, c.VendorDocNum, 
		c.VendorCircuitID, c.ItemStatus, c.PONum
		FROM tblPCReqHeader h
		INNER JOIN tblPCReqDetail d ON h.ID = d.ReqID
		INNER JOIN tblPCCostItem c ON d.ID = c.ReqDetailID
		WHERE h.ID = $pr_id ORDER BY d.LineNum"; 
	$query = mysqli_query($insightDBLink, $sql);
	$details = [];
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$details[] = $value;
		}
		return $details;
	} else {
		return false;
	}
}

function getPRDetailWithNewCostItem_renew($pr_id) {
	global $insightDBLink;
	$sql = "SELECT d.OrderType, d.LineNum, d.UOM, c.ID, c.Description, c.UnitCost, c.Quantity, c.PrevID, 
		c.ItemCode, c.Make, c.ModelNum, c.PartNum, c.SerialNum, c.ReadyDate, c.StartDate, c.EndDate, c.VendorDocNum, 
		c.VendorCircuitID, c.ItemStatus, c.PONum
		FROM tblPCReqHeader h
		INNER JOIN tblPCReqDetail d ON h.ID = d.ReqID
		INNER JOIN tblPCCostItem c ON d.ID = c.ReqDetailID
		WHERE h.ID = $pr_id and d.OrderType = 'RENEW' and (d.PrevPRLineID = 0 or d.PrevPRLineID = '') ORDER BY d.LineNum"; 
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$details[] = $value;
		}
		return $details;
	} else {
		return false;
	}
}



function findPOInEpicor($signed_co, $po_num, $chk_staging=true) {
	// return true if po number found either in staging or real po table
	$conn = connectEpicor($signed_co);
	$sql = "SELECT COUNT(*) AS count_in_purchase FROM $epi_db_name..purchase WHERE po_no = '$po_num'"; 
	$query = sqlsrv_query($conn, $sql);  
	if ($query) {
		$count_in_purchase = dbSqlSrvResult($query, 0, "count_in_purchase"); 
		if ($count_in_purchase) return true;
	}
	
	if ($chk_staging) {
		$sql = "SELECT COUNT(*) AS count_in_impur FROM btnControl..impur 
			WHERE company_code = '$epi_co_code' AND po_key = ".substr($po_num, 4);
		$query = sqlsrv_query($conn, $sql); 
		if ($query) {
			$count_in_impur = dbSqlSrvResult($query, 0, "count_in_impur"); 
			if ($count_in_impur) return true;
		}
	}

	sqlsrv_close($conn);	
}

function compareHdrInEpicor($signed_co, $po_num, $field, $value) {
	// $field="vendor_no" or $field="curr_key" or $field="void"
	$conn = connectEpicor($signed_co);
	$sql = "SELECT $field FROM purchase WHERE po_no = '$po_num'"; 
	$query = sqlsrv_query($conn, $sql);  
	if ($query) {
		if ($value == dbSqlSrvResult($query, 0, 0)) {
			return true;
		}
	} 
	sqlsrv_close($conn);	
}

function compareDtlInEpicor($signed_co, $po_num, $po_line, $field, $value) { 
	// $field="COUNT(*)" or $field="curr_cost" or $field="qty_ordered" or $field!="void"
	$conn = connectEpicor($signed_co);
	$sql = "SELECT $field FROM pur_list WHERE po_no = '$po_num' AND line = $po_line"; 
	$query = sqlsrv_query($conn, $sql);  
	if ($query) {
		if ($value == dbSqlSrvResult($query, 0, 0)) {
			return true;
		} 
	} 
	sqlsrv_close($conn);	
}

# 18583
function handshakeEpicorOrSAP($signed_co){
	global $insightDBLink;
	//$Gateway_SignedCo = array(24, 25, 26, 27, 28, 29); // #19910
	//if (in_array($signed_co, $Gateway_SignedCo)) { // handshake SAP
	// #19910
	$sql = "SELECT count(*) FROM tblGenericCode WHERE CodeType = 'PRCheck' and CodeCat = 'SAPCO' and CodeValue = '$signed_co'";
	$query = mysqli_query($insightDBLink, $sql);

	if ($query) {
		$row = mysqli_fetch_row($query);
		if ($row[0] == 1) return 'SAP'; else return 'Epicor';
	} else {
		return 'Epicor';
	}
}

function checkHandshakeToEpicor($signed_co){
	global $insightDBLink;
	$sql = "SELECT Description FROM tblGenericCode WHERE CodeType = 'PRCheck' and CodeCat = 'POHandShake' and CodeValue = 'NonEpicor'";
	$query = mysqli_query($insightDBLink, $sql);

	while ($value = mysqli_fetch_array($query)) {
		if($value["Description"] != ""){
			$nonEpiSignCo = explode(',', $value["Description"]);
			if (in_array($signed_co, $nonEpiSignCo)) {
				return false;
			}else
				return true;
		}else
			return true;
	}
}

// added by alvin, 2014-08-06
// 29830: add ignoreBRstatus = true parameter to ignore BR status
function calculateBudgetSummary($budgetId, $PurchaseRequestId = NULL, $PRReqStatement = NULL, $GroupIDStatment = NULL, $ignoreBRstatus = false) {
	global $insightDBLink;
	//JM Include AdditionalBR  task 31982
//include_once("../../Budget/reCalculateBR.php");
 
	//$AdditionalBROpex=getAdditionalBRAmount($budgetId);	if (!$AdditionalBROpex) {$AdditionalBROpex=0;}
	/*
	$sql="SELECT ProjectName, ApprovedCapEx,(ApprovedOpEx+".$AdditionalBROpex.") as 'ApprovedOpEx', AllocatedCapEx, AllocatedOpEx, OTNo
			FROM tblBRBudget 
			WHERE ID = $budgetId ".($ignoreBRstatus? "": "AND BRStatus = 'Open' ");
	*/
	$sql="SELECT ProjectName, ApprovedCapEx, ApprovedOpEx, AllocatedCapEx, AllocatedOpEx, OTNo
			FROM tblBRBudget 


			WHERE ID = $budgetId ".($ignoreBRstatus? "": "AND BRStatus = 'Open' ");
	
	if($PRReqStatement != null) {
		$sql = $sql .'AND ID IN '. $PRReqStatement;
	}
	
	if($GroupIDStatment != null) {
		$sql = $sql .'OR ID IN '. $GroupIDStatment;
	}
		
	$DBT1_last = mysqli_query($insightDBLink, $sql);
	$DBTrow_last = null;	// ELK fix
	if($DBT1_last){
		$DBTrow_last = mysqli_fetch_array($DBT1_last);
	}
	// $DBTrow_last = mysqli_fetch_array($DBT1_last);
	$ProjectName = $DBTrow_last["ProjectName"];
	$ApprovedCapEx = $DBTrow_last["ApprovedCapEx"];
	$ApprovedOpEx = $DBTrow_last["ApprovedOpEx"];
	$AllocatedCapEx = $DBTrow_last["AllocatedCapEx"];
	$AllocatedOpEx = $DBTrow_last["AllocatedOpEx"];
	$otNo = $DBTrow_last["OTNo"];

	if($ProjectName) {
		if($otNo) {
			$sql = "SELECT ID
			FROM tblOvTrvl
			WHERE masterOT = $otNo
			ORDER BY ID";
			$result = mysqli_query($insightDBLink, $sql);
			while ($row = mysqli_fetch_array($result)) {
				$childOTNo = $row['ID'];
		
				//$sql1 = "SELECT ApprovedCapEx, (ApprovedOpEx+".$AdditionalBROpex.") as 'ApprovedOpEx'
				$sql1 = "SELECT ApprovedCapEx, ApprovedOpEx
						FROM tblBRBudget
						WHERE OTNo = $childOTNo";
				/*
				$sql1 = "SELECT ApprovedCapEx, ApprovedOpEx
						FROM tblBRBudget br
						WHERE EXISTS (
							SELECT 1
							FROM tblOvTrvl ot
							WHERE ot.ID = br.OTNo
							AND ot.masterOT = $childOTNo)";
				*/
				$result1 = mysqli_query($insightDBLink, $sql1);
				while ($row1 = mysqli_fetch_array($result1)) {
					$ApprovedCapEx = round($ApprovedCapEx + $row1["ApprovedCapEx"], 2);
					$ApprovedOpEx = round($ApprovedOpEx + $row1["ApprovedOpEx"], 2);
				}
			}
		}
		$RemainingCapEx = round($ApprovedCapEx - $AllocatedCapEx, 2);
		$RemainingOpEx = round($ApprovedOpEx - $AllocatedOpEx, 2);
		
		$CurrentCapexBal = $RemainingCapEx;
		$CurrentOpexBal = $RemainingOpEx;
		
		if($PurchaseRequestId != NULL) {
			$sql="SELECT TotalAmt_USD, Capex_USD, Opex_USD, CurrentStep
			FROM tblPCReqHeader
			WHERE ID = $PurchaseRequestId";
			$DBT1_last = mysqli_query($insightDBLink, $sql);
			$DBTrow_last = mysqli_fetch_array($DBT1_last);
			if($DBTrow_last) {
				$PR_TotalAmt_USD = $DBTrow_last ["TotalAmt_USD"];
				$Capex_USD = $DBTrow_last ["Capex_USD"];
				$Opex_USD = $DBTrow_last ["Opex_USD"];
				$currentStep = $DBTrow_last ["CurrentStep"];
			
				//echo $sql."<br>";
				/*
				 CodeValue	Description
				1	Saved
				2	Awaiting Verification
				3	Awaiting Approval
				4	Awaiting Processing
				5	Processed
				6	Cancel
				7	Denied
				8	Hold by Verifier
				9	Hold by Approver
				10	Hold by Processer
				12	Voided
				13	Awaiting 2nd Processor Verification
				14	Awaiting 1st Approver Approval
				19	Hold by 2nd Verifier
				20	Hold by 1st Approver
				*/
				if (($currentStep != 1) && ($currentStep != 6) && ($currentStep != 7) && ($currentStep != 12)) {
					//echo "submitted<br>";
					
					#JM AddtionalBR correction. BR Task 31982
					//$CurrentCapexBal = $RemainingCapEx;
					//$CurrentOpexBal = $RemainingOpEx;
					 
					#Original
					$CurrentCapexBal = $RemainingCapEx + $Capex_USD;
					$CurrentOpexBal = $RemainingOpEx + $Opex_USD;
				} else {
					//echo "not yet submitted" . $Capex_USD . $Opex_USD . "<br>";
					#JM AdditionalBR task 31982
					#$CurrentCapexBal = $RemainingCapEx + $Capex_USD;
					#$CurrentOpexBal = $RemainingOpEx + $Opex_USD;
					
					#original
					$CurrentCapexBal = $RemainingCapEx;
					$CurrentOpexBal = $RemainingOpEx;
					
					$RemainingCapEx = $RemainingCapEx - $Capex_USD;
					$RemainingOpEx = $RemainingOpEx - $Opex_USD;
				}
			}
		}
		
		/*
		 Capex (USD) Opex (USD)
		Approved 		1,000.00 	1,280.00
		Current Balance 1,000.00 	0.00
		New Balance		1,000.00 	0.00
		*/
		 #JM AddtionalBR correction. BR Task 31982
		/*if($currentStep==1){
			
			$sqlOpex="SELECT SUM(UnitCost*Quantity) AS 'OpexUnitCost',(SELECT ExRate_USD FROM tblPCReqHeader WHERE ID=tblPCReqDetail.ReqID) AS  'ExRate_USD' FROM tblPCReqDetail WHERE BudgetType='Opex'  AND ReqID={$PurchaseRequestId}";
			$sqlOpex = mysqli_query($insightDBLink, $sqlOpex);
			$OpexRow = mysqli_fetch_array($sqlOpex);
			
			
			$TotalOpexUSD=$OpexRow['OpexUnitCost']*$OpexRow['ExRate_USD'];
		
			$sqlCapex="SELECT SUM(UnitCost*Quantity) AS 'CapexUnitCost',(SELECT ExRate_USD FROM tblPCReqHeader WHERE ID=tblPCReqDetail.ReqID) AS  'ExRate_USD' FROM tblPCReqDetail WHERE BudgetType='Capex'  AND ReqID={$PurchaseRequestId}";
			$sqlCapex = mysqli_query($insightDBLink, $sqlCapex);
			$CapexRow = mysqli_fetch_array($sqlCapex);
			
			
			$TotalCapexUSD=$CapexRow['OpexUnitCost']*$CapexRow['ExRate_USD'];
		} #JM AddtionalBR correction. BR Task 31982 end
		*/
		$resultArr = [];
		$resultArr[0][0] = $ApprovedCapEx;
		$resultArr[0][1] = $ApprovedOpEx;
		$resultArr[1][0] = $CurrentCapexBal;
		$resultArr[1][1] = $CurrentOpexBal;
		//$resultArr[1][0] = $CurrentCapexBal-$TotalCapexUSD; #JM AddtionalBR correction. BR Task 31982
		//$resultArr[1][1] = $CurrentOpexBal-$TotalOpexUSD;#JM AddtionalBR correction. BR Task 31982
		$resultArr[2][0] = $RemainingCapEx;
		$resultArr[2][1] = $RemainingOpEx;
		return $resultArr;
	}else{
		return null;
	}
}
// end added by alvin, 2014-08-06

function encodeString($value) {
	return str_replace(['\\', '/', '"', "\r", "\n", "\b", "\f", "\t"], 
	                             ['\\\\', '\/', '\"', '\r', '\n', '\b', '\f', '\t'], $value);
}

function csrfToken()
{

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
		$expiry = $time_now + CurrentSessionTimeout;
		$expiry4h = $time_now + strtotime('+4 hours', 0);

		// this will be decently random.  Default. 32 character hex number
		// e.g.: 42f4780b3e297d77c7abd7b7b15e5291
		$token = md5(
			openssl_random_pseudo_bytes(16)
		);

		$token = encrypt_decrypt("encrypt", $_SERVER['PHP_SELF']);
		//echo $_SERVER['PHP_SELF'] . "<br>";

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
								$expiry = $time_now + CurrentSessionTimeout;
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

?>
