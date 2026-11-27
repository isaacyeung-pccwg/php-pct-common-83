<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/pct_common.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/logger.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/suppliercost/QuantityChecking.php");
include_once($insight_helper_path . "/db_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");

function lastDay ($month, $year) {
	$last = '31';
	if ($month == '2') {
		$last = '28';
		$div = $year % 4;
		if ($div == 0) {
			$last = '29';
			$div2 = $year % 100;
			if ($div2 == 0)
				$last = '28';
		}
	}
	else if ($month == '4' ||  $month == '6' || $month == '9' || $month == '11')
		$last = '30';
	return $last;
}

function monthDiff ($year1, $month1, $day1, $year2, $month2, $day2) {
	$count = ($year2 - $year1) * 12 + ($month2 - $month1);
	if ($day2 < $day1) {
		$count = $count - 1;
		$new_year = $year2;
		$new_month = $month2 - 1;
		if ($month2 == 1) {
			$new_month = 12;
			$new_year = $new_year - 1;
		}
		$new_date = mktime(0,0,0,$new_month,$day1,$new_year);
		$date2 = mktime(0,0,0,$month2,$day2,$year2);
		$count = $count + ($date2 - $new_date) / (86400 * lastDay ($new_month, $new_year));
	}
	else
		$count = $count + ($day2 - $day1) / lastDay ($month1, $year1);
	return $count;
}

function receiveMRC($ID, 
	$service_handover_date,
	$vendor_start_billing_date,
	$vendor_stop_billing_date,
	$vendor_order_ref_no,
	$vendor_circuit_id,
	$receipt_date,
	$PCCWGServiceID,
	$actual_term_date_old_costitem, 
	$Log_EventType, 
	$Log_Description, 
	$files_name, 
	$files_temp_name,
        //Toni's Task 22069
        //Align Auto-received to insight user        
        $user) {
	
	// required variables 
	global $EPI_HOSTNAME, $EPI_USERNAME, $EPI_PASSWORD, $PHP_AUTH_USER, $insightDBLink;
	$now=date("Y-m-d H:i:s");

	$SQL_actionType = "SELECT OrderType, B.StartDate, B.EndDate from tblPCReqDetail A, tblPCCostItem B WHERE A.ID = B.ReqDetailID AND B.ID = '$ID'";
	$DBT1_actionType = mysqli_query($insightDBLink, $SQL_actionType);
	$DBTrow_actionType = mysqli_fetch_row($DBT1_actionType);
	$ActionType = $DBTrow_actionType[0];
	$ci_start_date = $DBTrow_actionType[1];
	$ci_end_date = $DBTrow_actionType[2];
	$recvStartDate = null;
	$recvEndDate = null;
	$qty_diff = 0;
	if ($ActionType == 'RENEW') {
		$SQL_prevId = "SELECT ID FROM tblPCCostItem where id = (SELECT PrevID FROM tblPCCostItem WHERE ID = '$ID')";
		$DBT1_prevId = mysqli_query($insightDBLink,$SQL_prevId);
		$DBTrow_prevId = mysqli_fetch_row($DBT1_prevId);
		$prev_id = $DBTrow_prevId[0];
		if ($prev_id <> '') {
			$sql_special_2 = "SELECT MAX(A.DateTo) FROM tblPCReceipts A, tblISMatchReceipt B WHERE A.ID = B.ReceiptID AND A.CostItemID = $prev_id AND B.MatchQty > 0";
			$DBT1_last_2 = mysqli_query($insightDBLink,$sql_special_2);
			$DBTrow_last_2 = mysqli_fetch_row($DBT1_last_2);
			$max_date_to = $DBTrow_last_2[0];
			$recvStartDate = $ci_start_date;
			if ($max_date_to != null && $max_date_to != '0000-00-00' && $max_date_to >= $ci_start_date) {
				$recvStartDate = date('Y-m-d', strtotime('+1 day', strtotime($max_date_to)));
				$ci_start_date_year = round(str_replace('”', '', date(”y”,strtotime($ci_start_date))), 0);
				$ci_start_date_month = round(str_replace('”', '', date(”m”,strtotime($ci_start_date))), 0);
				$ci_start_date_day = round(str_replace('”', '', date(”d”,strtotime($ci_start_date))), 0);
				$recvStartDate_year = round(str_replace('”', '', date(”y”,strtotime($recvStartDate))), 0);
				$recvStartDate_month = round(str_replace('”', '', date(”m”,strtotime($recvStartDate))), 0);
				$recvStartDate_day = round(str_replace('”', '', date(”d”,strtotime($recvStartDate))), 0);
				$qty_diff = monthDiff ($ci_start_date_year, $ci_start_date_month, $ci_start_date_day, $recvStartDate_year, $recvStartDate_month, $recvStartDate_day);
			}
			$recvEndDate = $ci_end_date;
			if ($max_date_to != null && $max_date_to != '0000-00-00' && $max_date_to >= $ci_end_date)
				$recvEndDate = date('Y-m-d', strtotime('+1 day', strtotime($max_date_to)));
		}
	}

	$qty_receipt = 0;
	$Make = '';
	$Part_no = '';
	$Model_no = '';
	$Serial_no = '';

$sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
	EndDate =  '$vendor_stop_billing_date',  VendorDocNum = '$vendor_order_ref_no', 
	recvStartDate = '$recvStartDate', recvEndDate = '$recvEndDate', 
	VendorCircuitID = '$vendor_circuit_id', FirstReceiveQty = '$qty_receipt', FirstReceiveDate = '$receipt_date',  TotalReceiveQty = '$qty_receipt', ItemStatus = 'Active', PCCWGServiceID = '$PCCWGServiceID', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', PrevID_TermDate = '$actual_term_date_old_costitem'  where ID = '$ID'";
mysqli_query($insightDBLink,$sql);
	
$SQL_vv ="select BillingPeriod, Quantity, Startdate, EndDate from tblPCCostItem where tblPCCostItem.ID = '$ID' ";
       
	    $DBT1 = mysqli_query($insightDBLink,$SQL_vv);
        $DBTrow = mysqli_fetch_row($DBT1);
        $billing_type = $DBTrow[0];
		$quantity = $DBTrow[1];
		//$vendor_start_billing_date = $DBTrow[2];
		//$vendor_stop_billing_date = $DBTrow[3];
		
		//if ($billing_type == '' || $billing_type == null) {
		//$billing_type = 0;
		//};
		
		$billing_type = $billing_type + 1;

if ($ActionType == 'RENEW') {
	$start_date = $recvStartDate;
	$end_date = $recvEndDate;
	$quantity = $quantity - $qty_diff;
}
else {
$start_date = $vendor_start_billing_date;
$end_date = $vendor_stop_billing_date;
}

//echo $start_date." ".$end_date."<br>"; 


	$start_year = str_replace('”', '', date('”y”',strtotime($start_date)));
	$start_month =  str_replace('”', '', date('”m”',strtotime($start_date)));
	$start_day =  str_replace('”', '', date('”d”',strtotime($start_date)));



	$end_start_year = str_replace('”', '', date('”y”',strtotime($end_date)));
	$end_start_month =  str_replace('”', '', date('”m”',strtotime($end_date)));


	$factor = lastDay($start_month, $start_year);


//echo $factor;

$qty_receipt_first = ((($factor - $start_day) + 1)/$factor);

//secho number_format($qty_receipt_first, 2)."<br>";

//epicor handshake checking start

//receiving handshake logic
	 //insert data into Epicor receipts table
  $SQL="select ID, SignedCo, PONum, POLineNum, Quantity, ItemStatus, ReqDetailID from tblPCCostItem where tblPCCostItem.ID = '$ID' ";
       
	    $DBT1 = mysqli_query($insightDBLink,$SQL);
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
		$DBT1_max_id = mysqli_query($insightDBLink,$maxid_sql);
    $DBTrow_max_id = mysqli_fetch_row($DBT1_max_id);
    $database = $DBTrow_max_id[0];
	$database = "BTN".$database;
	
		//echo $database; 



	$link = dbSqlSrvConnect($EPI_HOSTNAME, $database, $EPI_USERNAME, $EPI_PASSWORD);
	//[2019.02.19,Ken] Epicor v7.4 changed the table as view (from receipts to receipts_all)
	$maxid_sql = "select max(receipt_no)  as maxid from  receipts_all ";
	$DBT1_max_id = sqlsrv_query($link, $maxid_sql);
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
					 
					 
	$DBT1_max_id = sqlsrv_query($link, $maxid_sql);
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
	
	
	//rejected logic 1 MRC


//if ( ($ReqDetailID <> '' and ($PONum == '' || $PONum == null)) || ($ReqDetailID <> '' and $po_no_epicor == '') || (($ReqDetailID == '' || $ReqDetailID == null) and $po_no_epicor == '' and $PONum <> '')) {
	
	
	//echo "<center>Epicor PO Number is not valid! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre> ";
	//exit;
	//}
	  
	  //CHECK 2 PO status
	   $maxid_sql = "select status, vendor_no, curr_key, oper_factor, curr_factor from purchase where po_no = '$PONum' ";
	$DBT1_max_id = sqlsrv_query($link, $maxid_sql);
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
	
	//echo "<center>Purchase record has already closed! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre>";
	//exit;
	
	$logic_4044 = '10';
	}
	 
	 
	  //CHECK 3 pur_list status
	   $maxid_sql = "select status from pur_list where po_no = '$PONum' and line = '$POLineNum'";
	$DBT1_max_id = sqlsrv_query($link, $maxid_sql);
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $status_epicor = $DBTrow_max_id[0];
	
	
	if ( $status_epicor == 'O') {
	//pur list status open
	$pur_list_status = 'Y';
	
	} else {
	
	//echo "<center>Pur_List record has already closed! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre>";
	//exit;
	
	$logic_4044 = '10';
	}
	 
	 
	//CHECK 4 Epicor PO quantity match
	   $maxid_sql = "select qty_ordered, qty_received from pur_list where po_no = '$PONum' and line = '$POLineNum'";
	$DBT1_max_id = sqlsrv_query($link, $maxid_sql);
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $qty_ordered_epicor = $DBTrow_max_id[0];
	  $qty_received_pur_list = $DBTrow_max_id[1];
	
	
	if ( $qty_ordered_epicor == $Quantity) {
	
	$quantity_match = 'Y';
	
	} 



//epicor handshake checking

//echo $start_date." ".$start_date."<br>";
$total_quantity_month = 0;
$total_billing_type = 0;


while ($start_year <= $end_start_year) {

	if ($start_month == $end_start_month && $start_year == $end_start_year ) {
		if ($billing_type - $total_billing_type >= 1) 
			$confirmflag = 1;
		else 
			$confirmflag = 0;
		$a = "20".$start_year."-".$start_month."-1";
		$b = number_format($quantity - $total_quantity_month, 4);
		if (QuantityChecking($ID, $b, $a, $end_date)) {
			$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) values('$ID', '$a', '$b', '$now', '$confirmflag', '0', '$now', '$user', '$a', '$end_date')";
			mysqli_query($insightDBLink,$insert_sql);
		}
		break;
	}
	else {
		if (strlen($start_year) == 1)
			$start_year = "0".$start_year;
		if ($start_month == str_replace('”', '', date('”m”',strtotime($start_date))) && $start_year == str_replace('”', '', date('”y”',strtotime($start_date)))) {
			$a = "20".$start_year."-".$start_month."-".lastDay($start_month, $start_year);
			$b = number_format($qty_receipt_first, 4);
			if (QuantityChecking($ID, $b, $start_date, $a)) {
				$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$user', '$start_date', '$a')";
				mysqli_query($insightDBLink,$insert_sql);
			}
			$month_quantity = $qty_receipt_first;
			$confirmflag = 1;
		}
		else {
			if ($quantity - $total_quantity_month >= 1) 
				$month_quantity = 1;
			else if ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month >= 0)
				$month_quantity = $quantity - $total_quantity_month;
			else 
				$month_quantity = 0;
			if ($billing_type - $total_billing_type >= 1) 
				$confirmflag = 1;
			else 
				$confirmflag = 0;
			$a = "20".$start_year."-".$start_month."-1";
			$b = "20".$start_year."-".$start_month."-".lastDay($start_month, $start_year);
			if (QuantityChecking($ID, $month_quantity, $a, $b)) {
				$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) values('$ID', '$a', '$month_quantity', '$now', '$confirmflag;', '0', '$now', '$user', '$a', '$b')";
				mysqli_query($insightDBLink,$insert_sql);
			}
 		}
	}
	if ($start_month == '12') {
		$start_month = '1';
		$start_year = $start_year + 1;
	}
	else
		$start_month = $start_month + 1;
	$total_quantity_month = $total_quantity_month + $month_quantity;
	$total_billing_type = $total_billing_type + $confirmflag;
}
 
 // new report format end 
 
 //new logic end
 
 
 
 
 
 //eipcor handshake add
 
 
 
 
 // new logic
 //new logic
  $SQL_co = "SELECT ID FROM tblPCCostItem where id = (SELECT PrevID FROM tblPCCostItem WHERE ID = '$ID')";
  $DBT1_id = mysqli_query($insightDBLink,$SQL_co);
  $DBTrow_id = mysqli_fetch_row( $DBT1_id);
  $previous_id = $DBTrow_id[0];
  
  
  //1
  if ($previous_id <> '') {
  
  
  
  $SQL_co = "SELECT OrderType FROM tblPCReqDetail where id = (SELECT ReqdetailID FROM tblPCCostItem WHERE ID = '$ID')";
  $DBT1_order = mysqli_query($insightDBLink,$SQL_co);
  $DBTrow_order = mysqli_fetch_row( $DBT1_order);
  $OrderType = $DBTrow_order[0];
  
  
  // $StartDate = $vendor_start_billing_date;
    $StartDate =  $actual_term_date_old_costitem;
   
  
   //task 4426
  $SQL_co = "select Description from tblGenericCode where CodeValue = '$OrderType' and CodeType = 'CIStatus' and CodeCat = 'PROldItem' and   ActiveFlag = '1'";
  $DBT1_order = mysqli_query($insightDBLink,$SQL_co);
  $DBTrow_order = mysqli_fetch_row( $DBT1_order);
  $Old_costitem_desc = $DBTrow_order[0];
  
  
   //$SQL_update = "update tblPCCostItem set ItemStatus ='$OrderType', TermDate = '$actual_term_date_old_costitem' where id =  '$previous_id'";
   
  $SQL_update = "update tblPCCostItem set ItemStatus ='$Old_costitem_desc', TermDate = '$actual_term_date_old_costitem' where id =  '$previous_id'";
    
    mysqli_query($insightDBLink,$SQL_update);
  //echo $SQL_update ;
  
  
   $new_term_date = date('Y-m-d', strtotime('-1 day', strtotime($StartDate)));
   // $SQL_co = "select ID from tblPCReceipts where CostItemID = '$previous_id' and  DateFrom <= '$StartDate' and DateTo >= '$StartDate' ";
  $SQL_co = "select ID from tblPCReceipts where CostItemID = '$previous_id' and DateFrom <= '$new_term_date' and DateTo >= '$new_term_date' and ConfirmFlag <> '1'";
  
  
  $DBT1_VV = mysqli_query($insightDBLink,$SQL_co);
  $DBTrow_VV = mysqli_fetch_row( $DBT1_VV);
  $exceed_billing_ID_within = $DBTrow_VV[0];
  
   $SQL_co = "select ID from tblPCReceipts where CostItemID = '$previous_id' and DateFrom <= '$new_term_date' and DateTo >= '$new_term_date' ";
  
  
  $DBT1_GG = mysqli_query($insightDBLink,$SQL_co);
  $DBTrow_GG = mysqli_fetch_row( $DBT1_GG);
  $exceed_billing_ID_general = $DBTrow_GG[0];
  
  
  
 
   // NEW RECEIVING LOGIC 2
 if (  $exceed_billing_ID_general <> '') {
 $SQL_co = "delete from tblPCReceipts where CostItemID = '$previous_id' and  ID > '$exceed_billing_ID_within' and ConfirmFlag <> '1'";
   mysqli_query($insightDBLink,$SQL_co);
 //echo $SQL_co." "."<br>";
 }

  // NEW RECEIVING LOGIC 3
  //2
  if (  $exceed_billing_ID_within <> '') {
  
   $SQL_co = "SELECT StartDate FROM tblPCCostItem WHERE ID = '$previous_id'";
  $DBT1_StartDate_prev = mysqli_query($insightDBLink,$SQL_co);
  $DBTrow_StartDate_prev = mysqli_fetch_row( $DBT1_StartDate_prev);
  $Previous_ID_StartDate = $DBTrow_StartDate_prev[0];
  
  //echo $SQL_co;
	  $Previous_ID_StartDate_day = str_replace('”', '', date('”j”',strtotime($Previous_ID_StartDate)));
	  $Previous_ID_StartDate_month = str_replace('”', '', date('”m”',strtotime($Previous_ID_StartDate)));
	  $Previous_ID_StartDate_year = str_replace('”', '', date('”Y”',strtotime($Previous_ID_StartDate)));



	  $StartDate_day = str_replace('”', '', date('”j”',strtotime($StartDate)));
	  $StartDate_month = str_replace('”', '', date('”m”',strtotime($StartDate)));
	  $StartDate_year = str_replace('”', '', date('”Y”',strtotime($StartDate)));



	  $date1 = mktime(0,0,0,$Previous_ID_StartDate_month,$Previous_ID_StartDate_day,$Previous_ID_StartDate_year);
     $date2 = mktime(0,0,0,$StartDate_month,$StartDate_day,$StartDate_year);	 

     $total_month =  floor(($date2 - $date1)/2628000);

$calculate_month = $Previous_ID_StartDate_month + $total_month;

if ($calculate_month > 12) {
$calculate_month = $calculate_month - 12;
$Previous_ID_StartDate_year = $Previous_ID_StartDate_year + 1;
}




$date1 = mktime(0,0,0,$calculate_month,$Previous_ID_StartDate_day,$Previous_ID_StartDate_year);
$date2 = mktime(0,0,0,$StartDate_month,$StartDate_day ,$StartDate_year);	 
$dateDiff = $date2 - $date1;
$fullDays = floor($dateDiff/(60*60*24)); 



  if ($calculate_month == '2') 
{$factor = '28';} 

else if ($calculate_month == '4' ||  $calculate_month == '6' || $calculate_month == '9' || $calculate_month == '11')
{$factor = '30';} 
else 
{$factor = '31';} 

$SQL_co = "select sum(ReceiveQty) as actual_receive_qty from tblPCReceipts where CostItemID = '$previous_id' and  ID < '$exceed_billing_ID_within' "; 
$DBT1_last = mysqli_query($insightDBLink,$SQL_co);
  $DBTrow_last = mysqli_fetch_row($DBT1_last);
  $actual_receive_qty = $DBTrow_last[0];

$Last_term_qty =  ($total_month + (($fullDays) / $factor))  - $actual_receive_qty ;
  
  
  //echo $total_month." ".$fullDays." ".$factor." ".$actual_receive_qty;
  
 $StartDate = date('Y-m-d', strtotime('-1 day', strtotime($StartDate)));
  
// $SQL_old_cost_item = "update tblPCReceipts set ReceiveQty = '$Last_term_qty', DateTo = '$StartDate' where ID = '$exceed_billing_ID_within'";
  // mysqli_query($insightDBLink,$SQL_co);
   

   
}//2


}//1

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
	mysqli_query($insightDBLink,$SQL);
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

}//end function


