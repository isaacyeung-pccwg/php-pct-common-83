<?php

//include("../../../../phpInc/auth.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/pct_common.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");

include_once($insight_helper_path . "/db_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");

include_once("ReceiveNRC.php");
include_once("ReceiveMRC.php");

include_once("ReceiveNRC_epicor.php");
include_once("ReceiveMRC_epicor.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/logger.php");
global $PHP_AUTH_USER;

function receiveATFCostItem($pr_id, $user) {



	if (!$pr_id || isCostItemReceived($pr_id)) return false;

	$ReqInfo		= getPRHeaderInfo($pr_id);
	$ReqDetail		= getPRDetailWithNewCostItem($pr_id);
	
	

	// prepare receiving data
	foreach ($ReqDetail	as $key=>$details) {
		$ready_date = $details["ReadyDate"] ?: $details["StartDate"];
		$end_date	= ($details["ItemCode"]=="MRC") ? calculateEndDate($details["StartDate"], $details["Quantity"]) : $details["EndDate"];
		// $date_from 	= (strcasecmp($details["ItemStatus"], "Ordered")) ? $details["StartDate"] : addDays(findMaxReceiveDate($details["StartDate"]),1);
		$date_from 	= $details["StartDate"];
		// $date_to 	= (strcasecmp($details["UOM"], "MM")) ? findLastDayOfMonth($date_from) : $end_date;
		$date_to 	= $end_date;
		$pccwg_service_id = getPCCWGServiceId($details["ID"]);

		$data[$key]["ci_id"] 				= $details["ID"];
		$data[$key]["item_code"] 			= $details["ItemCode"];
		$data[$key]["make"] 				= ($details["OrderType"]=="NEW_GOODS"||$details["OrderType"]=="CONTINUE")?$details["Make"]:"";
		$data[$key]["model_num"] 			= ($details["OrderType"]=="NEW_GOODS"||$details["OrderType"]=="CONTINUE")?$details["ModelNum"]:"";
		$data[$key]["part_num"] 			= ($details["OrderType"]=="NEW_GOODS"||$details["OrderType"]=="CONTINUE")?$details["PartNum"]:"";
		$data[$key]["serial_num"] 			= ($details["OrderType"]=="NEW_GOODS"||$details["OrderType"]=="CONTINUE")?$details["SerialNum"]:"";
		$data[$key]["ready_date"] 			= $ready_date;
		$data[$key]["start_date"] 			= $details["StartDate"];
        $data[$key]["end_date"] 			= $end_date;
//		$data[$key]["end_date"] 			= ($details["OrderType"]=="NEW_GOODS"||$details["OrderType"]=="CONTINUE")?$end_date:"";
		$data[$key]["vendor_doc_num"] 		= $details["VendorDocNum"];
		$data[$key]["vendor_circuit_id"] 	= $details["VendorCircuitID"];
		$data[$key]["pccwg_service_id"]		= ($details["ItemCode"]!="NRC"||$details["OrderType"]=="CONTINUE")?$pccwg_service_id:"";
		$data[$key]["term_date"] 			= $details["PrevID"]>0?addDays($details["StartDate"],-1):"";
		$data[$key]["receive_date"]			= $ready_date;
		// $data[$key]["receive_qty"]			= $details["ItemCode"]=="NRC"?round((dateDiff($date_from, $date_to)+1)/findLastDayOfMonth($date_to), 4):"";
		$data[$key]["receive_qty"]			= $details["ItemCode"]=="NRC"?$details["Quantity"]:"";
		$data[$key]["date_from"] 			= $details["ItemCode"]=="NRC"?$date_from:"";
		$data[$key]["date_to"]				= $details["ItemCode"]=="NRC"?$date_to:""; 
                //Toni's Task 22069
                //Add Event Log for Auto-processed to insight user
                $data[$key]["user"]				= $user; 
                
	}
	
	if (receiveCostItems($data)) {
		sendEmailCIReceived($pr_id, $user);
		
	}
}


function receiveATFCostItem_renew($pr_id, $user) {
	if (!$pr_id || isCostItemReceived($pr_id)) return false;

	$ReqInfo		= getPRHeaderInfo($pr_id);
	$ReqDetail		= getPRDetailWithNewCostItem_renew($pr_id);

	// prepare receiving data
	foreach ($ReqDetail	as $key=>$details) {
		$ready_date = $details["ReadyDate"] ?: $details["StartDate"];
		$end_date	= ($details["ItemCode"]=="MRC") ? calculateEndDate($details["StartDate"], $details["Quantity"]) : $details["EndDate"];
		// $date_from 	= (strcasecmp($details["ItemStatus"], "Ordered")) ? $details["StartDate"] : addDays(findMaxReceiveDate($details["StartDate"]),1);
		$date_from 	= $details["StartDate"];
		// $date_to 	= (strcasecmp($details["UOM"], "MM")) ? findLastDayOfMonth($date_from) : $end_date;
		$date_to 	= $end_date;
		$pccwg_service_id = getPCCWGServiceId($details["ID"]);

		$data[$key]["ci_id"] 				= $details["ID"];
		$data[$key]["item_code"] 			= $details["ItemCode"];
		$data[$key]["make"] 				= $details["OrderType"]=="NEW_GOODS"?$details["Make"]:"";
		$data[$key]["model_num"] 			= $details["OrderType"]=="NEW_GOODS"?$details["ModelNum"]:"";
		$data[$key]["part_num"] 			= $details["OrderType"]=="NEW_GOODS"?$details["PartNum"]:"";
		$data[$key]["serial_num"] 			= $details["OrderType"]=="NEW_GOODS"?$details["SerialNum"]:"";
		$data[$key]["ready_date"] 			= $ready_date;
		$data[$key]["start_date"] 			= $details["StartDate"];
		$data[$key]["end_date"] 			= $details["OrderType"]!="NEW_GOODS"?$end_date:"";
		$data[$key]["vendor_doc_num"] 		= $details["VendorDocNum"];
		$data[$key]["vendor_circuit_id"] 	= $details["VendorCircuitID"];
		$data[$key]["pccwg_service_id"]		= $details["ItemCode"]!="NRC"?$pccwg_service_id:"";
		$data[$key]["term_date"] 			= $details["PrevID"]>0?addDays($details["StartDate"],-1):"";
		$data[$key]["receive_date"]			= $ready_date;
		// $data[$key]["receive_qty"]			= $details["ItemCode"]=="NRC"?round((dateDiff($date_from, $date_to)+1)/findLastDayOfMonth($date_to), 4):"";
		$data[$key]["receive_qty"]			= $details["ItemCode"]=="NRC"?$details["Quantity"]:"";
		$data[$key]["date_from"] 			= $details["ItemCode"]=="NRC"?$date_from:"";
		$data[$key]["date_to"]				= $details["ItemCode"]=="NRC"?$date_to:"";
	}
	
	
	if (receiveCostItems_renew($data)) {
		sendEmailCIReceived_renew($pr_id, $user);
	}
}

function sendEmailCIReceived($pr_id, $user) { 
	global $insightDBLink, $logger;
	// prepare pr data
	$pr_id_long 		= PRID($pr_id);
	$ReqInfo			= getPRHeaderInfo($pr_id);
	$ReqInfo["Role"] 	= getActFlow($pr_id);
	$ReqDetail 			= getPRDetailWithNewCostItem($pr_id); 
	
	// email 1: notify invoice lines' current processor
	// recipient

    $logger->info("sendEmailCIReceived, PR ID:$pr_id");

	$to_emails	= getRecipientEmails(findEmailRecipients_new($pr_id));
	$cc_emails	= getRecipientEmails(findEmailCC_new($pr_id));


   //Requester
       $sql_main = "select ActionUser from tblPCActFlow where LevelInd = 'PRReq' and Sequence = '1' and ReferenceID = '$pr_id' "; 
	
	   //echo $sql_main."<br>" ;   
   
        $DBT1_3 = mysqli_query($insightDBLink, $sql_main);
        $DBTrow_3 = mysqli_fetch_row($DBT1_3);
        $PR_requester = $DBTrow_3[0];
		
		//Invoice ID
		$sql_main = "select SourceID, PONum, CostType from tblPCReqHeader where ID = '$pr_id' "; 
	
	   //echo $sql_main."<br>" ;   
   
        $DBT1_3 = mysqli_query($insightDBLink, $sql_main);
        $DBTrow_3 = mysqli_fetch_row($DBT1_3);
        $Invoice_ID = $DBTrow_3[0];
		$PONum = $DBTrow_3[1];
		$PR_costtype = $DBTrow_3[2];
	
	// subject
	$subject = "Purchase Request Received: ".$pr_id_long;
	// content message
	//$message = "After-the-fact Purchase Request ".$pr_id_long." has been Received by ".GetStaffName($user).".\r\n\n";	
	//$message = "After-the-fact Purchase Request ".$pr_id_long." has been Received ".GetStaffName($PR_requester).".\r\n\n";	
	
	if ( $PR_costtype == 'HR') {
	$message = "Purchase Request ".$pr_id_long." has been Received \r\n\n";	
	} else {
	$message = "After-the-fact Purchase Request ".$pr_id_long." has been Received \r\n\n";	
	}


	$message .= "Signed Company:  ".findSignedCoName($ReqInfo["SignedCo"])."\r\n\n";
	
	//$message .= "Vendor Name :  ".findVendorName($ReqInfo["VendorCode"])."\r\n\n";
	
	$pr_vendorcode = $ReqInfo['VendorCode'];
			  $sql_count = "Select count(*) from tblVendorAttr where VendorID = 
 (Select ID from tblVendor where EpicorCode = '$pr_vendorcode') and AttributeType = 'VIPVendor' and AttributeValue = 'Y'";
 $DBT1_max_id = mysqli_query($insightDBLink, $sql_count);
 $DBTrow_2 = mysqli_fetch_row($DBT1_max_id);
 $vip_count = $DBTrow_2[0];
 //echo " vv ".$vip_count;
 
 
 if ($vip_count > 0 ) {
		$message .= "Vendor Name :  ".findVendorName($ReqInfo["VendorCode"])." VIP \r\n\n";
		} else {
		$message .= "Vendor Name :  ".findVendorName($ReqInfo["VendorCode"])."\r\n\n";
		}
	
	
	$message .= "Currency :  ".$ReqInfo["Currency"]." - ".findCurrencyName($ReqInfo["Currency"])."\r\n\n";
	$message .= "IS ID :  ".$Invoice_ID."\r\n\n";
	$message .= "PO Num :  ".$PONum."\r\n\n";
	foreach ($ReqDetail as $details) {
	
	    //6896
		 $CI_ID = $details["ID"];
	    $sql_main = "select ID, InvoiceLine from tblISInvLine where PRLineID = (select ReqDetailID from tblPCCostItem where ID = '$CI_ID') "; 
	
	   //echo $sql_main."<br>" ;   
   
        $DBT1_3 = mysqli_query($insightDBLink, $sql_main);
        $DBTrow_3 = mysqli_fetch_row($DBT1_3);
        $tlISInvLine_ID = $DBTrow_3[0];
		$InvoiceLine = $DBTrow_3[1];
	
		
		$message .= "Cost Item Line :  ".$details["LineNum"]."   ";
		$message .= "Cost Item ID :  ".$details["ID"]."   ";
		$message .= "ISLine Item:  ".$InvoiceLine."\r\n\n";
		
	}
	
	//echo $message;
	if ($to_emails && is_array($to_emails) && sizeof($to_emails) > 0) {
      sendGenericEmail($to_emails, $subject, $message, $cc_emails);
    } else {
	  $logger->info("email was not sent as recipient list is empty");
    }
	
	unset($to_emails); 
	foreach ($ReqDetail as $details) {
		if ($details["ItemCode"]=="MRC") {
			$has_mrc = true;
			break;
		}
	}
	
	// email 2: notify pr processor if mrc
	if ($has_mrc) {
		// recipient
		$to_emails	= getRecipientEmails($ReqInfo["Role"][4]); 
		// subject
		$subject = "Purchase Request Received: ".$pr_id_long; 
		// content message
		$message = "After-the-fact Purchase Request ".$pr_id_long." has been Received, please batch receive the following MRC cost items.\r\n\n";	
		$message .= "Signed Company:  ".findSignedCoName($ReqInfo["SignedCo"])."\r\n\n";
		$message .= "Vendor Name :  ".findVendorName($ReqInfo["VendorCode"])."\r\n\n";
		$message .= "Currency :  ".$ReqInfo["Currency"]." - ".findCurrencyName($ReqInfo["Currency"])."\r\n\n";
		$message .= "Invoice ID :  ".ISID(findInvoiceId($pr_id))."\r\n\n";
		foreach ($ReqDetail as $details) {
			if ($details["ItemCode"]=="MRC") {
				$message .= "Cost Item Line :  ".$details["LineNum"]."\r\n\n";
				$message .= "Cost Item ID :  ".$details["ID"]."\r\n\n";
			}
		} 
		//sendGenericEmail($to_emails, $subject, $message);
	}
}

function sendEmailCIReceived_renew($pr_id, $user) { 
	global $insightDBLink;
	// prepare pr data
	$pr_id_long 		= PRID($pr_id);
	$ReqInfo			= getPRHeaderInfo($pr_id);
	$ReqInfo["Role"] 	= getActFlow($pr_id);
	$ReqDetail 			= getPRDetailWithNewCostItem($pr_id); 
	
	// email 1: notify invoice lines' current processor
	// recipient
	$to_emails	= getRecipientEmails(findEmailRecipients_new($pr_id));
	$cc_emails	= getRecipientEmails(findEmailCC_new($pr_id));


   //Requester
       $sql_main = "select ActionUser from tblPCActFlow where LevelInd = 'PRReq' and Sequence = '1' and ReferenceID = '$pr_id' "; 
	
	   //echo $sql_main."<br>" ;   
   
        $DBT1_3 = mysqli_query($insightDBLink, $sql_main);
        $DBTrow_3 = mysqli_fetch_row($DBT1_3);
        $PR_requester = $DBTrow_3[0];
		
		//Invoice ID
		$sql_main = "select SourceID, PONum, CostType, BudgetID, CreateSource  from tblPCReqHeader where ID = '$pr_id' "; 
	
	   //echo $sql_main."<br>" ;   
   
        $DBT1_3 = mysqli_query($insightDBLink, $sql_main);
        $DBTrow_3 = mysqli_fetch_row($DBT1_3);
        $Invoice_ID = $DBTrow_3[0];
		$PONum = $DBTrow_3[1];
		$PR_costtype = $DBTrow_3[2];
		$PR_BudgetID = $DBTrow_3[3];
		$PR_CreateSource = $DBTrow_3[4];
		
	
	// subject
	$subject = "Purchase Request Received: ".$pr_id_long;
	// content message
	//$message = "After-the-fact Purchase Request ".$pr_id_long." has been Received by ".GetStaffName($user).".\r\n\n";	
	//$message = "After-the-fact Purchase Request ".$pr_id_long." has been Received ".GetStaffName($PR_requester).".\r\n\n";	
	
	//if ( $PR_costtype == 'HR') {
	$message = "Purchase Request ".$pr_id_long." with Renew Cost Item has been Received \r\n\n";	
	//} else {
	//$message = "After-the-fact Purchase Request ".$pr_id_long." has been Received \r\n\n";	
	//}


	$message .= "Signed Company:  ".findSignedCoName($ReqInfo["SignedCo"])."\r\n\n";
	$message .= "Vendor Name :  ".findVendorName($ReqInfo["VendorCode"])."\r\n\n";
	$message .= "Currency :  ".$ReqInfo["Currency"]." - ".findCurrencyName($ReqInfo["Currency"])."\r\n\n";
	//$message .= "IS ID :  ".$Invoice_ID."\r\n\n";
	
	
	//$message .= "Source of Creation :  ".$pr_id_long."\r\n\n";
	
	if ( $PR_CreateSource == '') {
$message .= "Source of Creation :  created manually ".$pr_id_long."\r\n\n";

} 
	
	if ( $PR_CreateSource == 'IS') {
	
	if (strlen($Invoice_ID) == 2) { $Prev = "IS000000"; }
	if (strlen($Invoice_ID) == 3) { $Prev = "IS00000"; }
	if (strlen($Invoice_ID) == 4) { $Prev = "IS0000"; }
	if (strlen($Invoice_ID) == 5) { $Prev = "IS000"; }
	if (strlen($Invoice_ID) == 6) { $Prev = "IS00"; }
	if (strlen($Invoice_ID) == 7) { $Prev = "IS0"; }
	if (strlen($Invoice_ID) == 8) { $Prev = "IS"; }
	
	
$message .= "Source of Creation :  generated from  ".$Prev.$Invoice_ID."\r\n\n";

} 

if ( $PR_CreateSource == 'BR'  ) {

	if (strlen($PR_BudgetID) == 2) { $Prev = "BR000000"; }
	if (strlen($PR_BudgetID) == 3) { $Prev = "BR00000"; }
	if (strlen($PR_BudgetID) == 4) { $Prev = "BR0000"; }
	if (strlen($PR_BudgetID) == 5) { $Prev = "BR000"; }
	if (strlen($PR_BudgetID) == 6) { $Prev = "BR00"; }
	if (strlen($PR_BudgetID) == 7) { $Prev = "BR0"; }
	if (strlen($PR_BudgetID) == 8) { $Prev = "BR"; }



$message .= "Source of Creation :  generated from  ".$Prev.$PR_BudgetID."\r\n\n";

}  



	
	
	
	$message .= "PO Num :  ".$PONum."\r\n\n";
	
	$costitem_receive_count = 0;
	
	foreach ($ReqDetail as $details) {
	
	    //6896
		 $CI_ID = $details["ID"];
	    $sql_main = "select ID, InvoiceLine from tblISInvLine where PRLineID = (select ReqDetailID from tblPCCostItem where ID = '$CI_ID' and ItemStatus = 'Active') "; 
	
	   //echo $sql_main."<br>" ;   
   
        $DBT1_3 = mysqli_query($insightDBLink, $sql_main);
        $DBTrow_3 = mysqli_fetch_row($DBT1_3);
        $tlISInvLine_ID = $DBTrow_3[0];
		$InvoiceLine = $DBTrow_3[1];
		
		 $sql_main = "select ItemStatus from tblPCCostItem where ID = '$CI_ID'  "; 
	    $DBT1_3 = mysqli_query($insightDBLink, $sql_main);
        $DBTrow_3 = mysqli_fetch_row($DBT1_3);
        $ItemStatus = $DBTrow_3[0];
		
		if ($ItemStatus == 'Active') {
		$ItemStatus = 'Received';
		$costitem_receive_count = $costitem_receive_count + 1;
		} else {
		$ItemStatus = '        ';
		}
	
		
		$message .= "Cost Item Line :  ".$details["LineNum"]."   ";
		$message .= "Cost Item ID :  ".$details["ID"]." ".$ItemStatus."   ";
		//$message .= "ISLine Item:  ".$InvoiceLine."\r\n\n";
		
	}
	
	if ($costitem_receive_count > 0) {
	sendGenericEmail($to_emails, $subject, $message, $cc_emails);
	
	}
	
	unset($to_emails); 
	foreach ($ReqDetail as $details) {
		if ($details["ItemCode"]=="MRC") {
			$has_mrc = true;
			break;
		}
	}
	
	// email 2: notify pr processor if mrc
	//if ($has_mrc) {
		// recipient
		//$to_emails	= getRecipientEmails($ReqInfo["Role"][4]); 
		// subject
		//$subject = "Purchase Request Received: ".$pr_id_long; 
		//// content message
		//$message = "Purchase Request ".$pr_id_long." with Renew Cost Item has been Received, please batch receive the following MRC cost items.\r\n\n";	
		//$message .= "Signed Company:  ".findSignedCoName($ReqInfo["SignedCo"])."\r\n\n";
		//$message .= "Vendor Name :  ".findVendorName($ReqInfo["VendorCode"])."\r\n\n";
		//$message .= "Currency :  ".$ReqInfo["Currency"]." - ".findCurrencyName($ReqInfo["Currency"])."\r\n\n";
		//$message .= "Invoice ID :  ".ISID(findInvoiceId($pr_id))."\r\n\n";
		//foreach ($ReqDetail as $details) {
			//if ($details["ItemCode"]=="MRC") {
				//$message .= "Cost Item Line :  ".$details["LineNum"]."\r\n\n";
				//$message .= "Cost Item ID :  ".$details["ID"]."\r\n\n";
			//}
		//} 
		//sendGenericEmail($to_emails, $subject, $message);
	//}
}


function addDays($str_date, $days) {
	$str_date = strtotime($str_date);
	return date("Y-m-d", mktime(0,0,0,date("m", $str_date),date("d", $str_date)+$days,date("Y", $str_date)));
}

function calculateEndDate($start_date, $quantity) {
	$start_date = strtotime($start_date); 
	return date("Y-m-d", mktime(0,0,0,date("m", $start_date)+$quantity,date("d", $start_date)-1,date("Y", $start_date)));
}

function findLastDayOfMonth($str_date) {
	$str_date = strtotime($str_date);
	return date("Y-m-d", mktime(0,0,0,date("m", $str_date)+1,0,date("Y", $str_date)));
}

function dateDiff($fr, $to) {
	$parts_fr = explode("-", $fr);
	$parts_to = explode("-", $to);
	$gdate_fr = gregoriantojd($parts_fr[1], $parts_fr[2], $parts_fr[0]);
	$gdate_to = gregoriantojd($parts_to[1], $parts_to[2], $parts_to[0]);
	
	return $gdate_to - $gdate_fr;
}

function findMaxReceiveDate($ci_id) {
	global $insightDBLink;
	$sql = "SELECT MAX(ReceiveDate) AS max_rec_date FROM tblPCReceipts WHERE CostItemID = $ci_id";
	$query = mysqli_query($insightDBLink, $sql);
	while ($value = mysqli_fetch_array($query)) {
		$max_rec_date = $value["max_rec_date"];
	}
	
	return $max_rec_date;
}

function receiveCostItems($details, $files = null) { 
	global $insightDBLink;
	foreach ($details as $key=>$value) {
		$item_code = $value["item_code"];
		if ($item_code=="NRC") { 
			receiveNRC($value["ci_id"], 
				$value["ready_date"],
				$value["start_date"],
				$value["end_date"],
				$value["vendor_doc_num"],
				$value["vendor_circuit_id"],
				$value["receive_qty"],
				$value["receive_date"],
				$value["date_from"],
				$value["date_to"],
				$value["make"],
				$value["part_num"],
				$value["model_num"],
				$value["serial_num"],
				$value["term_date"],
				$value["EventType"],
				$value["EventDetails"],
				$files["userfile"]["name"], 
				$files["userfile"]["tmp_name"],
                                //Toni's Task 22069
                                //Align Auto-received cost item to insight user
                                $value["user"]
                                );
				
		} elseif ($item_code=="MRC") {
			receiveMRC($value["ci_id"], 
				$value["ready_date"],
				$value["start_date"],
				$value["end_date"],
				$value["vendor_doc_num"],
				$value["vendor_circuit_id"],
				$value["receive_date"],
				$value["pccwg_service_id"],
				$value["term_date"],
                $value["EventType"] ?? '',
                $value["EventDetails"] ?? '',
				$files["userfile"]["name"], 
				$files["userfile"]["tmp_name"],
                                //Toni's Task 22069
                                //Align Auto-received cost item to insight user
                                $value["user"]                                
                                );
		}
                
            //Toni's Task 22069
            //Add a log for Auto Received
            $COSTITEM_id = $value["ci_id"];
                                  

            $now=date("Y-m-d H:i:s");
            $SQL_log = "insert into tblPCEventLog (levelInd, ReferenceID, LogDate, LogUser, DocType, Description)
            values ('CostItem', '$COSTITEM_id', '$now', 'insight','DNOTE', 'Auto-received (AFT/HR)')"; 
            mysqli_query($insightDBLink, $SQL_log);
            
	}
	return true;
}


function receiveCostItems_renew($details, $files=null) { 
	global $insightDBLink;
	foreach ($details as $key=>$value) {
		//logic 11176
		$logic_11176 = '';

$COSTITEM_id = $value["ci_id"];

$SQL = "select startdate  from tblPCCostItem where ID = '$COSTITEM_id'";
			
  $DBT1_signedco = mysqli_query($insightDBLink, $SQL);
  $DBTrow_signedco = mysqli_fetch_row($DBT1_signedco);
  $startdate  = $DBTrow_signedco[0];

//echo "<BR>".$SQL;

$SQL_vvv = "select enddate, VendorDocNum, VendorCircuitID, PCCWGServiceID   from tblPCCostItem where ID = (select PrevID  from tblPCCostItem where ID = '$COSTITEM_id')";
			
  $DBT1_signedco = mysqli_query($insightDBLink, $SQL_vvv);
  $DBTrow_signedco = mysqli_fetch_row($DBT1_signedco);
  $enddate  = $DBTrow_signedco[0];
  $VendorDocNum  = $DBTrow_signedco[1];
  $VendorCircuitID  = $DBTrow_signedco[2];
  $PCCWGServiceID   = $DBTrow_signedco[3];
  
  //11076
  
//$sql_aa = "update tblPCCostItem set enddate = '$enddate', VendorDocNum = '$VendorDocNum', VendorCircuitID = '$VendorCircuitID', PCCWGServiceID = '$PCCWGServiceID' where ID = '$COSTITEM_id'";
//mysqli_query($insightDBLink, "UPDATE tblPCCostItem SET enddate = '$enddate', VendorDocNum = '$VendorDocNum', VendorCircuitID = '$VendorCircuitID', PCCWGServiceID = '$PCCWGServiceID' WHERE ID = '$COSTITEM_id'");
//echo $sql_aa."<br>";


//$now=date("Y-m-d H:i:s");
//$SQL_log = "insert into tblPCEventLog (levelInd, ReferenceID, LogDate, LogUser, DocType, Description)
//values ('CostItem', '$COSTITEM_id', '$now', 'insight','DNOTE', 'Auto-received')"; 

//echo $SQL_log;

//mysqli_query($insightDBLink, $SQL_log);

  
  
  //11076
  
//echo "<BR>".$SQL;  
  
  $enddate_new = date('Y-m-d', strtotime('+1 day', strtotime($enddate)));
  
  if ($startdate == $enddate_new )    
  {
  
  } else  {
  
   $logic_11176 = 'false';
   
   }
		
		//logic 11176
		//11
		if ($logic_11176 == '') {
		
		$now=date("Y-m-d H:i:s");
$SQL_log = "insert into tblPCEventLog (levelInd, ReferenceID, LogDate, LogUser, DocType, Description)
values ('CostItem', '$COSTITEM_id', '$now', 'insight','DNOTE', 'Auto-received (Enddate + 1)')"; 

//echo $SQL_log;

mysqli_query($insightDBLink, $SQL_log);
		
		
		$item_code = $value["item_code"];
		if ($item_code=="NRC") { 
			receiveNRC_epicor($value["ci_id"], 
				$value["ready_date"],
				$value["start_date"],
				$value["end_date"],
				$value["vendor_doc_num"],
				$value["vendor_circuit_id"],
				$value["receive_qty"],
				$value["receive_date"],
				$value["date_from"],
				$value["date_to"],
				$value["make"],
				$value["part_num"],
				$value["model_num"],
				$value["serial_num"],
				$value["term_date"],
				$value["EventType"],
				$value["EventDetails"],
				$files["userfile"]["name"], 
				$files["userfile"]["tmp_name"]);
				
		} elseif ($item_code=="MRC") {
			receiveMRC_epicor($value["ci_id"], 
				$value["ready_date"],
				$value["start_date"],
				$value["end_date"],
				$value["vendor_doc_num"],
				$value["vendor_circuit_id"],
				$value["receive_date"],
				$value["pccwg_service_id"],
				$value["term_date"],
				$value["EventType"],
				$value["EventDetails"],
				$files["userfile"]["name"], 
				$files["userfile"]["tmp_name"]);
		}
	
	}//11
	
	}
	return true;
}

function getPCCWGServiceId($ci_id) {
	global $insightDBLink;
	$sql = "SELECT s.CustomerID, s.SiteSerial, s.ServiceID, s.ServiceSerial
		FROM tblPCCostItem c INNER JOIN tblSale s ON c.SRNum = s.ID
		WHERE c.ID = $ci_id";
	$query = mysqli_query($insightDBLink, $sql);
	if (!$query) return false;
	
	$site_serial = dbSelectColumn($insightDBLink, $sql, 0, 1);
	$service_serial = dbSelectColumn($insightDBLink, $sql, 0, 3);
	if ($site_serial && $service_serial) {
		$pccwg_service_id = dbSelectColumn($insightDBLink,$query,0,0).".".str_pad($site_serial, 2, 0, STR_PAD_LEFT).".".dbSelectColumn($insightDBLink,$query,0,2).".".str_pad($service_serial, 4, 0, STR_PAD_LEFT);
	}
	return $pccwg_service_id;
}
