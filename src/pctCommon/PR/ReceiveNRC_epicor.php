<?php 

include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");

include_once($insight_helper_path . "/db_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");

function receiveNRC_epicor($ID, 
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
	$files_temp_name) { 

	
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
	//$sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
		//EndDate =  '$vendor_stop_billing_date',  VendorDocNum = '$vendor_order_ref_no', 
		//VendorCircuitID = '$vendor_circuit_id', FirstReceiveQty = '$qty_receipt', FirstReceiveDate = '$receipt_date', TotalReceiveQty = '$qty_receipt',  ItemStatus = 'Active', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', PrevID_TermDate = '$actual_term_date_old_costitem'  where ID = '$ID'";
	$sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
		EndDate =  '$vendor_stop_billing_date',  FirstReceiveQty = '$qty_receipt', FirstReceiveDate = '$receipt_date', TotalReceiveQty = '$qty_receipt',  ItemStatus = 'Active', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', PrevID_TermDate = '$actual_term_date_old_costitem'  where ID = '$ID'";
	
	
	
	mysqli_query($insightDBLink, $sql);
 } else {
	$total_recqty  = $total_recqty  + $qty_receipt;
	//$sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
		//EndDate =  '$vendor_stop_billing_date',  VendorDocNum = '$vendor_order_ref_no', 
		//VendorCircuitID = '$vendor_circuit_id', TotalReceiveQty = '$total_recqty',  ItemStatus = 'Active', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', PrevID_TermDate = '$actual_term_date_old_costitem'   where ID = '$ID'";
	
	$sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
		EndDate =  '$vendor_stop_billing_date',  TotalReceiveQty = '$total_recqty',  ItemStatus = 'Active', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', PrevID_TermDate = '$actual_term_date_old_costitem'   where ID = '$ID'";
	
	
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


//receiving handshake logic
	 //insert data into Epicor receipts table
  $SQL="select ID, SignedCo, PONum, POLineNum, Quantity, ItemStatus, ReqDetailID from tblPCCostItem where tblPCCostItem.ID = '$ID' ";
       
	    $DBT1 = mysqli_query($insightDBLink, $SQL);
        $DBTrow = mysqli_fetch_row($DBT1);
        $Cost_ID = $DBTrow[0];
		$SignedCo = $DBTrow[1];
        $PONum = $DBTrow[2];
        $POLineNum = $DBTrow[3];
		$Quantity = $DBTrow[4];
		$ItemStatus = $DBTrow[5];
		$ReqDetailID = $DBTrow[6];
        
	// search for BTN database
	   $maxid_sql =	"SELECT lpad(CoCode, 3, '0') FROM tblRegisteredCo LEFT OUTER JOIN tblCo_Entities ON SignedCO = tblRegisteredCo.ID WHERE ID ='$SignedCo'";
		$DBT1_max_id = mysqli_query($insightDBLink, $maxid_sql);
    $DBTrow_max_id = mysqli_fetch_row($DBT1_max_id);
    $database = $DBTrow_max_id[0];
	$database = "BTN".$database;
	
		//echo $database; 
		 
	
	$ReceiveQty =  $qty_receipt;
	 
	 
	 
	  $link = dbSqlSrvConnect($EPI_HOSTNAME, $database, $EPI_USERNAME, $EPI_PASSWORD);  
	 
	 $maxid_sql = "select max(receipt_no)  as maxid from  receipts ";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $maxid = $DBTrow_max_id[0];
	
	//echo $maxid;
	$maxid = $maxid + 1;
	
	
	//logic 4044
	$logic_4044 = 0;
	
	
	
	
		  
	  //CHECK 1 valid Epicor PO Number
	   $maxid_sql = "select po_no, part_no, vend_sku, location, type, unit_cost, unit_measure, account_no, conv_factor,
	                  lb_tracking,  po_key, oper_cost, curr_cost
	                  from pur_list where po_no = '$PONum' and line = '$POLineNum'";
					 
					 
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $po_no_epicor = $DBTrow_max_id[0];
	$part_no_epicor = $DBTrow_max_id[1];
	$vend_sku_epicor = $DBTrow_max_id[2];
	$location_epicor = $DBTrow_max_id[3];
	$type_epicor = $DBTrow_max_id[4];
	$unit_cost_epicor = $DBTrow_max_id[5];
	$unit_measure = $DBTrow_max_id[6];
	$account_no = $DBTrow_max_id[7];
	$conv_factor = $DBTrow_max_id[8];
	$lb_tracking = $DBTrow_max_id[9];
	$po_key = $DBTrow_max_id[10];
	$oper_cost = $DBTrow_max_id[11];
	$curr_cost = $DBTrow_max_id[12];
	
	
	//if ( $po_no_epicor <> '') {
	//echo "Epicor PO Number! pur_list table"."<br>";
	
	//} else {
	
	//echo "<center>Epicor PO Number is not valid! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre> ";
	//exit;
	//}
	
		//if ( ($ReqDetailID <> '' and ($PONum == '' || $PONum == null)) || ($ReqDetailID <> '' and $po_no_epicor == '') || (($ReqDetailID == '' || $ReqDetailID == null) and $po_no_epicor == '' and $PONum <> '')) {
	
	//if ( ($ReqDetailID <> '' and $po_no_epicor == '' and $PONum != '' ) || (($ReqDetailID == '' || $ReqDetailID == null) and $po_no_epicor == '' and $PONum <> '')) {
	
	//echo "<center>Epicor PO Number is not valid! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre> ";
	//exit;
	//}
	
	  
	  //CHECK 2 PO status
	   $maxid_sql = "select status, vendor_no, curr_key, oper_factor, curr_factor from purchase where po_no = '$PONum' ";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $status_epicor = $DBTrow_max_id[0];
	$vendor_no_epicor = $DBTrow_max_id[1];
	$curr_key_epicor = $DBTrow_max_id[2];
	$oper_factor_epicor = $DBTrow_max_id[3];
	$curr_factorr_epicor = $DBTrow_max_id[4];
	
	
	
	if ( $status_epicor == 'O') {
	//purchase status open
	$purchase_status = 'Y';
	
	} else {
	
	//echo "<center>Purchase record has already closed! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre> ";
	//exit;
	
	$logic_4044 = '10';
	}
	 
	 
	  //CHECK 3 pur_list status
	   $maxid_sql = "select status from pur_list where po_no = '$PONum' and line = '$POLineNum'";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $status_epicor = $DBTrow_max_id[0];
	
	
	if ( $status_epicor == 'O') {
	//pur list status open
	$pur_list_status = 'Y';
	
	} else {
	
	//echo "<center>Pur_List record has already closed! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre> ";
	//exit;
	
	$logic_4044 = '10';
	}
	 
	 
	//CHECK 4 Epicor PO quantity match
	   $maxid_sql = "select qty_ordered, qty_received from pur_list where po_no = '$PONum' and line = '$POLineNum'";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $qty_ordered_epicor = $DBTrow_max_id[0];
	  $qty_received_pur_list = $DBTrow_max_id[1];
	
	
	//if ( $qty_ordered_epicor == $Quantity) {
	//echo "Epicor PO quantity match"."<br>";
	
	//} else {
	
	//echo "<center>Epicor PO quantity did not match! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre> ";
	//exit;
	//} 
	
	if ( $qty_ordered_epicor == $Quantity) {
	
	$quantity_match = 'Y';
	
	} 
	
	
	
	//echo $logic_4044." ".$ItemStatus." ".$qty_received_pur_list." ".$PrevID;

  
 //  if (($ReqDetailID == '' || $ReqDetailID == '0') && ($ItemStatus == 'ordered' || $ItemStatus == 'Ordered') && $qty_received_pur_list > 0 && $logic_4044 <> '10') {
  // $logic_4044 = 1;
  // $qty_receipt =  $qty_received_pur_list;
   
   // $sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
       //  EndDate =  '$vendor_stop_billing_date',  VendorDocNum = '$vendor_order_ref_no', 
      //   VendorCircuitID = '$vendor_circuit_id', FirstReceiveQty = '$qty_receipt', FirstReceiveDate = '$receipt_date', TotalReceiveQty = '$qty_receipt',  ItemStatus = 'Active', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', LastUpdUser = '$PHP_AUTH_USER', LastUpdDate  = '$now'  where ID = '$ID'";
 
  // }
   
  // echo "<br>1 ".$logic_4044." 2 ".$purchase_status." 3 ".$pur_list_status." 4 ".$quantity_match;
	
	if ($logic_4044 == 0 && $purchase_status == 'Y' && $pur_list_status == 'Y' && $quantity_match == 'Y' ) {
	
	//get release date from table releases
	   $maxid_sql = "select release_date, received, quantity,  { fn SECOND(release_date) } AS Expr1, right(CONVERT(VARCHAR(24),release_date,113), 3) AS Expr2 from releases where po_no = '$PONum' and po_line = '$POLineNum'";
	   
	  
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $rel_date_epicor = $DBTrow_max_id[0];
	$received_releases = $DBTrow_max_id[1];
	$quantity_releases = $DBTrow_max_id[2];
	$expr1 = $DBTrow_max_id[3];
	$expr2 = $DBTrow_max_id[4];




   $rel_date_epicor_day = str_replace('�', '', date(�d�,strtotime($rel_date_epicor)));
  $rel_date_epicor_month = str_replace('�', '', date(�m�,strtotime($rel_date_epicor)));
  $rel_date_epicor_year = str_replace('�', '', date(�y�,strtotime($rel_date_epicor)));   
   $rel_date_epicor_hour = str_replace('�', '', date(�H�,strtotime($rel_date_epicor))); 
   $rel_date_epicor_minute = str_replace('�', '', date(�i�,strtotime($rel_date_epicor)));     
  $rel_date_epicor_second = str_replace('�', '', date(�s�,strtotime($rel_date_epicor)));   
  $rel_date_epicor_after = str_replace('�', '', date(�A�,strtotime($rel_date_epicor)));   
  
  $rel_date_epicor = "20".$rel_date_epicor_year."-".$rel_date_epicor_month."-".$rel_date_epicor_day." ".$rel_date_epicor_hour.":".$rel_date_epicor_minute.":".$expr1." ".$rel_date_epicor_after;
 if ($expr2 != '000') {
	$rel_date_epicor = "20".$rel_date_epicor_year."-".$rel_date_epicor_month."-".$rel_date_epicor_day." ".$rel_date_epicor_hour.":".$rel_date_epicor_minute.":".$expr1.".".$expr2;
	}
	//echo  $rel_date_epicor."<br>";
	  //update next_rec_batch_no.last_number 
	  $update_release_sql = "update next_rec_batch_no set last_number = last_number + 1"; 
	 sqlsrv_query($link, "$update_release_sql"); 
	 
	 //get receipt batch no
	$maxid_sql = "select last_number from next_rec_batch_no";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $receipt_batch_no = $DBTrow_max_id[0];
	 
	 $ext_cost = $unit_cost_epicor * $ReceiveQty;
	 
	 $insert_sql =  "insert into $database.dbo.receipts_all (receipt_no, conv_factor, ext_cost, status, freight_cost, quantity, unit_cost, receipt_batch_no, recv_date, release_date, po_no, part_no, location, vendor, po_line, part_type, sku_no, unit_measure, prod_no, account_no, 
					who_entered, bl_no, lb_tracking, freight_unit, po_key, nat_curr, oper_factor, curr_factor, oper_cost, curr_cost, freight_flag, qc_flag, qc_no, rejected, over_flag, std_cost, std_direct_dolrs, std_ovhd_dolrs, std_util_dolrs, vend_inv_no, pro_number, return_code, note, voucher_no, freight_vendor, freight_inv_no, freight_account, tax_included ) values 
                   ('$maxid', '$conv_factor', '$ext_cost', 'R', '0', '$ReceiveQty', '$unit_cost_epicor', '$receipt_batch_no', '$now', '$rel_date_epicor', '$PONum', '$part_no_epicor', '$location_epicor', '$vendor_no_epicor', '$POLineNum', '$type_epicor', '$vend_sku_epicor', '$unit_measure', '0', '$account_no',  
					'$PHP_AUTH_USER', '0', '$lb_tracking', '0', '$po_key', '$curr_key_epicor', '$oper_factor_epicor', '$curr_factorr_epicor', '$oper_cost', '$curr_cost', 'N', 'N', '0', '0', 'N', '0', '0', '0', '0', NULL,  NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0')";
	   
	
	 
	 if ( sqlsrv_query($link, "DISABLE TRIGGER t700insrec ON $database.dbo.receipts_all")) {
	 
	 } else {
	 echo "<center>vvError in Receiving Function!<br><br>Please try again</center>";
	exit;
	 }
	   //echo $insert_sql."<br>".$update_release_sql."<br>".$update_pur_list_sql;
	
	  if (sqlsrv_query($link, $insert_sql)) {
		//echo "<center>Received function completed and data inserted into Insight and Epicor database</center>";
			 
	} else {
	echo "<center>1Error in Receiving Function!<br><br>Please try again</center>";
	exit;
}
	
	//echo $insert_sql;
	
		  sqlsrv_query($link, "ENABLE TRIGGER t700insrec ON $database.dbo.receipts_all");  
	 
	  
	  //update next_rec_no.last_no 
	  $update_release_sql = "update next_rec_no set last_no = last_no + 1"; 
	 sqlsrv_query($link, "$update_release_sql");
	  
	
	  
	 //update releases
	 
	 if ($received_releases <= $quantity_releases) {
	 $received_releases = $received_releases + $ReceiveQty;
	 $update_release_sql = "update releases set received = '$received_releases' where po_no = '$PONum' and po_line = '$POLineNum'"; 
	 sqlsrv_query($link, "$update_release_sql");
	 }
	  
	  //update pur_list
	  if ($qty_ordered_epicor >=  $qty_received_pur_lis) {
	 $qty_received_pur_list = $qty_received_pur_list + $ReceiveQty;
	 $update_pur_list_sql = "update pur_list set qty_received = ' $qty_received_pur_list' where po_no = '$PONum' and line = '$POLineNum'"; 
	 sqlsrv_query($link, "$update_pur_list_sql");
	 }
	 
	//update pur_list status 
	    if ($qty_ordered_epicor ==  $qty_received_pur_list )
	  {
	   $update_pur_list_sql_1 = "update pur_list set status = 'C' where po_no = '$PONum' and line = '$POLineNum'"; 
	   sqlsrv_query($link,  $update_pur_list_sql_1);
		}
	  
	  
	  //update releases status
	    if ($received_releases == $quantity_releases)
	  {
	   $update_release_sql_1 = "update releases set status = 'C' where po_no = '$PONum' and po_line = '$POLineNum'"; 
	   sqlsrv_query($link,  $update_release_sql_1);
		}
	  
	  //update purchases
	  
	  $SQL="SELECT count(*) from releases where po_no = '$PONum'  ";
$DBT1 =  sqlsrv_query($link, $SQL);	// 2020: without connection before migration
$DBTrow = sqlsrv_fetch_array( $DBT1);
$count_release = $DBTrow[0];



$SQL="SELECT count(*) from releases where po_no = '$PONum' and status = 'C'";
$DBT1 =  sqlsrv_query($link, $SQL);	// 2020: without connection before migration
$DBTrow = sqlsrv_fetch_array( $DBT1);
$status_count = $DBTrow[0];

//echo $count_release." ".$status_count;

   if ($count_release == $status_count)
	  {
	   $update_purchase_sql_1 = "update purchase set status = 'C' where po_no = '$PONum' "; 
	   sqlsrv_query($link,  $update_purchase_sql_1);
		}
	 
	
	 
	 //receiving handshake logic
	 
	 }
	 
	 //epicor handshake 
	 
	 
	 
	 //logic 4044

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) values('$ID', '$receipt_date', '$qty_receipt', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$vendor_start_billing_date', '$special_date')";
 
 
 //get Action Type
	$SQL_co      = "select OrderType from  tblPCReqDetail where ID = '$ReqDetailID'";
	
	$DBT1_signedco = mysqli_query($insightDBLink, $SQL_co);
 $DBTrow_signedco = mysqli_fetch_row( $DBT1_signedco);
 $ActionType = $DBTrow_signedco[0];



if ($ActionType <> 'NEW_GOODS') {

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, 
HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) values('$ID', '$receipt_date', 
'$qty_receipt', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$Receiving_period_from_date', '$Receiving_period_to_date')";

}

  
 //echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
 
 
 
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
	$SQL = "insert into tblPCEventLog (levelInd, ReferenceID, LogDate, LogUser, DocType, Description, Attachment)
		values ('CostItem', '$ID', '$now', '$PHP_AUTH_USER','$Log_EventType', '$Log_Description', '$Log_file')"; 
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


