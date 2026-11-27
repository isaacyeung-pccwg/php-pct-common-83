<?php 

include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");

include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/suppliercost/QuantityChecking.php");
// include_once($insight_helper_path . "/db_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");

function receiveNRC($ID, 
	$service_handover_date,
	$vendor_start_billing_date,
	$vendor_stop_billing_date,
	$vendor_order_ref_no,
	$vendor_circuit_id,
	$qty_receipt,
	$receipt_date,
	$Receiving_period_from_date,
	$Receiving_period_to_date,
	$Make,
	$Part_no,
	$Model_no,
	$Serial_no,
	$actual_term_date_old_costitem, 
	$Log_EventType, 
	$Log_Description, 
	$files_name, 
	$files_temp_name,
        //Toni's Task 22069
        //Align Auto-received to insight user           
        $user) { 

  global $insightDBLink;
	// split vendor billing start date
	$arr_date = explode("-", $vendor_start_billing_date);
	$vendor_start_billing_date_year = $arr_date[0];
	$vendor_start_billing_date_month = $arr_date[1]; 

	// required variables 
	global $EPI_HOSTNAME, $EPI_USERNAME, $EPI_PASSWORD, $PHP_AUTH_USER;
	$now=date("Y-m-d H:i:s");
	
	
 $query = "select FirstReceiveQty , TotalreceiveQty from tblPCCostItem where  ID = '$ID'";
 $DBT1 = mysqli_query($insightDBLink, $query);
 $DBTrow = mysqli_fetch_row( $DBT1);
 $first_recqty = $DBTrow[0];
 $total_recqty = $DBTrow[1];

 if ($first_recqty == '') {
	$sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
		EndDate =  '$vendor_stop_billing_date',  VendorDocNum = '$vendor_order_ref_no', 
		VendorCircuitID = '$vendor_circuit_id', FirstReceiveQty = '$qty_receipt', FirstReceiveDate = '$receipt_date', TotalReceiveQty = '$qty_receipt',  ItemStatus = 'Active', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', PrevID_TermDate = '$actual_term_date_old_costitem'  where ID = '$ID'";
	mysqli_query($insightDBLink, $sql);
 } else {
	$total_recqty  = $total_recqty  + $qty_receipt;
	$sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
		EndDate =  '$vendor_stop_billing_date',  VendorDocNum = '$vendor_order_ref_no', 
		VendorCircuitID = '$vendor_circuit_id', TotalReceiveQty = '$total_recqty',  ItemStatus = 'Active', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', PrevID_TermDate = '$actual_term_date_old_costitem'   where ID = '$ID'";
	mysqli_query($insightDBLink, $sql);
 }
 
 $SQL = "SELECT BillingPeriod FROM tblPCCostItem WHERE ID = '$ID'";
 $DBT1 = mysqli_query($insightDBLink, $SQL);
 $DBTrow = mysqli_fetch_row( $DBT1);
 $BillingPeriod = $DBTrow[0]; 
 
 $special_month = $vendor_start_billing_date_month + $BillingPeriod;
 
 if ($special_month == 13) 
   {
   $special_month = 1;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
   
 if ($special_month == 14) 
 {
   $special_month = 2;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 15) 
 {
   $special_month = 3;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 16) 
 {
   $special_month = 4;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 17) 
 {
   $special_month = 5;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 18) 
 {
   $special_month = 6;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 19) 
 {
   $special_month = 7;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 20) 
 {
   $special_month = 8;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 21) 
 {
   $special_month = 9;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 22) 
 {
   $special_month = 10;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 23) {
   $special_month = 11;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }
 
 
 if ($special_month == 24)
 {
   $special_month = 12;
   $vendor_start_billing_date_year = $vendor_start_billing_date_year + 1;
   }



if ($special_month == 2) {
$special_day = 28; 
} else if ($special_month == 4 || $special_month == 6 || $special_month == 9 || $special_month == 11) {
$special_day = 30; 
} else {

$special_day = 31;
}





$special_date = $vendor_start_billing_date_year."-".$special_month."-".$special_day;



	 
	 
	 
	 //logic 4044
//Toni's Task 22069
//Align Auto-received cost item to insight user
if (QuantityChecking($ID, $qty_receipt, $vendor_start_billing_date, $special_date)) {
$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) values('$ID', '$receipt_date', '$qty_receipt', '$now', '1', '0', '$now', '$user', '$vendor_start_billing_date', '$special_date')";
 
 
 //get Action Type
	$SQL_co      = "select OrderType from  tblPCReqDetail where ID = '$ReqDetailID'";
	
	$DBT1_signedco = mysqli_query($insightDBLink, $SQL_co);
 $DBTrow_signedco = mysqli_fetch_row( $DBT1_signedco);
 $ActionType = $DBTrow_signedco[0];



if ($ActionType <> 'NEW_GOODS') {
//Toni's Task 22069
//Align Auto-received cost item to insight user
if (QuantityChecking($ID, $qty_receipt, $Receiving_period_from_date, $Receiving_period_to_date))
$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, 
HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) values('$ID', '$receipt_date', 
'$qty_receipt', '$now', '1', '0', '$now', '$user', '$Receiving_period_from_date', '$Receiving_period_to_date')";

}

  
 //echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
}
 
 
 
 
 //new logic
  $SQL_co = "SELECT ID FROM tblPCCostItem where id = (SELECT PrevID FROM tblPCCostItem WHERE ID = '$ID')";
  $DBT1_id = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_id = mysqli_fetch_row( $DBT1_id);
  $previous_id = $DBTrow_id[0];
  
  if ($previous_id <> '') {
  
  
  
  $SQL_co = "SELECT OrderType FROM tblPCReqDetail where id = (SELECT ReqdetailID FROM tblPCCostItem WHERE ID = '$ID')";
  $DBT1_order = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_order = mysqli_fetch_row( $DBT1_order);
  $OrderType = $DBTrow_order[0];
  
  
  $SQL_co = "SELECT StartDate FROM tblPCCostItem WHERE ID = '$ID'";
  $DBT1_StartDate = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_StartDate = mysqli_fetch_row( $DBT1_StartDate);
  $StartDate= $DBTrow_StartDate[0];
   
   
    //task 4426
  $SQL_co = "select Description from tblGenericCode where CodeValue = '$OrderType' and CodeType = 'CIStatus' and CodeCat = 'PROldItem' and   ActiveFlag = '1'";
  $DBT1_order = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_order = mysqli_fetch_row( $DBT1_order);
  $Old_costitem_desc = $DBTrow_order[0];
  
  // $SQL_update = "update tblPCCostItem set ItemStatus ='$OrderType', TermDate = '$actual_term_date_old_costitem' where id =  '$previous_id'";
   $SQL_update = "update tblPCCostItem set ItemStatus ='$Old_costitem_desc', TermDate = '$actual_term_date_old_costitem' where id =  '$previous_id'";
   
   
    mysqli_query($insightDBLink, $SQL_update);
  //echo $SQL_update ;

}

//Insert Log
$Log_file = basename($files_name);
$Log_file = str_replace('#', '_', $Log_file);
$Log_file = str_replace('%', '_', $Log_file);

if (($Log_EventType <> '' && $Log_Description <> '') || $Log_file <> '') {
	//Toni's Task 22069
	//Align Auto-received cost item to insight user
	$SQL = "insert into tblPCEventLog (levelInd, ReferenceID, LogDate, LogUser, DocType, Description, Attachment)
		values ('CostItem', '$ID', '$now', '$user','$Log_EventType', '$Log_Description', '$Log_file')"; 
	//echo $SQL;
	mysqli_query($insightDBLink, $SQL);
	$target = "upload/"; 
	//$target = $target . basename( $_FILES['userfile']['name']) ;
	//$target = $target . basename( $_FILES['userfile']['name']) ;
	$target = $target . $Log_file ; 
	$ok=1; 

	if(move_uploaded_file($files_temp_name, $target)) {
		//echo "The file has been uploaded";
	} else {
		//echo "Sorry, there was a problem uploading your file.";
	}
	//echo $userfile_name;
}  

}


