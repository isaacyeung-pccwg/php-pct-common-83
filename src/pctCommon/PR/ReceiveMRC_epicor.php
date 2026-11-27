<?php 
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");
include_once($insight_helper_path . "/db_helpers.php");

function receiveMRC_epicor($ID, 
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
	$files_temp_name) { 

	
	// required variables 
	global $EPI_HOSTNAME, $EPI_USERNAME, $EPI_PASSWORD, $PHP_AUTH_USER;
	$now=date("Y-m-d H:i:s");
	
	
//$sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
	//EndDate =  '$vendor_stop_billing_date',  VendorDocNum = '$vendor_order_ref_no', 
	//VendorCircuitID = '$vendor_circuit_id', FirstReceiveQty = '$qty_receipt', FirstReceiveDate = '$receipt_date',  TotalReceiveQty = '$qty_receipt', ItemStatus = 'Active', PCCWGServiceID = '$PCCWGServiceID', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', PrevID_TermDate = '$actual_term_date_old_costitem'  where ID = '$ID'";

$sql = "update tblPCCostItem set ReadyDate = '$service_handover_date', StartDate = '$vendor_start_billing_date',
	EndDate =  '$vendor_stop_billing_date',  FirstReceiveQty = '$qty_receipt', FirstReceiveDate = '$receipt_date',  TotalReceiveQty = '$qty_receipt', ItemStatus = 'Active', Make = '$Make', PartNum = '$Part_no', ModelNum = '$Model_no', SerialNum = '$Serial_no', PrevID_TermDate = '$actual_term_date_old_costitem'  where ID = '$ID'";

mysqli_query($insightDBLink, $sql);
	
$SQL_vv ="select BillingPeriod, Quantity, Startdate, EndDate from tblPCCostItem where tblPCCostItem.ID = '$ID' ";
       
	    $DBT1 = mysqli_query($insightDBLink, $SQL_vv);
        $DBTrow = mysqli_fetch_row($DBT1);
        $billing_type = $DBTrow[0];
		$quantity = $DBTrow[1];
		//$vendor_start_billing_date = $DBTrow[2];
		//$vendor_stop_billing_date = $DBTrow[3];
		
		//if ($billing_type == '' || $billing_type == null) {
		//$billing_type = 0;
		//};
		
		$billing_type = $billing_type + 1;


$start_date = $vendor_start_billing_date;
$end_date = $vendor_stop_billing_date;

//echo $start_date." ".$end_date."<br>"; 


$start_year = str_replace('�', '', date(�y�,strtotime($start_date))); 
$start_month =  str_replace('�', '', date(�M�,strtotime($start_date))); 
$start_day =  str_replace('�', '', date(�d�,strtotime($start_date))); 
  


$end_start_year = str_replace('�', '', date(�y�,strtotime($end_date))); 
$end_start_month =  str_replace('�', '', date(�M�,strtotime($end_date)));  



if ($start_month == 'Feb') 
{$factor = '28';} 

else if ($start_month == 'Apr' ||  $start_month == 'Jun' || $start_month == 'Sep' || $start_month == 'Nov')
{$factor = '30';} 
else 
{$factor = '31';} 

//echo $factor;

$qty_receipt_first = ((($factor - $start_day) + 1)/$factor);

//secho number_format($qty_receipt_first, 2)."<br>";

//epicor handshake checking start

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
	
	
	//rejected logic 1 MRC


//if ( ($ReqDetailID <> '' and ($PONum == '' || $PONum == null)) || ($ReqDetailID <> '' and $po_no_epicor == '') || (($ReqDetailID == '' || $ReqDetailID == null) and $po_no_epicor == '' and $PONum <> '')) {
	
	
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
	
	//echo "<center>Purchase record has already closed! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre>";
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
	
	//echo "<center>Pur_List record has already closed! Receiving function aborted!<br><br><br><input type='button' value='Exit' onClick='javascript:window.close();' class='W100'></centre>";
	//exit;
	
	$logic_4044 = '10';
	}
	 
	 
	//CHECK 4 Epicor PO quantity match
	   $maxid_sql = "select qty_ordered, qty_received from pur_list where po_no = '$PONum' and line = '$POLineNum'";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
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

//Jan
if ($start_month == 'Jan') {  

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $jan_confirmflag = 1; } else 
  {  $jan_confirmflag = 0;  }

     //echo "<br>"."20".$start_year."-1-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$jan_confirmflag ;

 $x = $quantity - $total_quantity_month;
 $a = "20".$start_year."-1-1";
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$x', '$now', '$jan_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
  break;
 } else {
 
if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-1-31"." ".$qty_receipt_first." 1";

$a = "20".$start_year."-1-31";
$b = number_format($qty_receipt_first, 4);
$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);


 $jan_quantity = $qty_receipt_first;
 $jan_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }

 
   if ($quantity - $total_quantity_month >= 1) 
  {  $jan_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $jan_quantity = $quantity - $total_quantity_month; } else 
  {  $jan_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1)
  {  $jan_confirmflag = 1; } else 
  {  $jan_confirmflag = 0;  }


    //echo "<br>"."20".$start_year."-1-1"." "."20".$start_year."-1-31"." ".$jan_quantity." ".$jan_confirmflag;
 
 $a = "20".$start_year."-1-1";
 $b = "20".$start_year."-1-31";
 
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$jan_quantity', '$now', '$jan_confirmflag;', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 }
 
 }
  $start_month = 'Feb';
  $total_quantity_month = $total_quantity_month + $jan_quantity;
  $total_billing_type = $total_billing_type + $jan_confirmflag;
 }
 
 
 
 //Feb
if ($start_month == 'Feb') { 
 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $feb_confirmflag = 1; } else 
  {  $feb_confirmflag = 0;  }


     // echo "<br>"."20".$start_year."-2-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$feb_confirmflag;
  
  $a = "20".$start_year."-2-1";
  $b = $quantity - $total_quantity_month;
  
  $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$feb_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 
  break;
 } else {
if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 

//echo "<br>".$start_date." "."20".$start_year."-2-28"." ".$qty_receipt_first." 1";


$b = number_format($qty_receipt_first, 4);


$div = gmp_div_r($start_year, 4);

 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }

 if (gmp_strval($div) == 0) {
  $a = "20".$start_year."-2-29";
  } else {$a = "20".$start_year."-2-28";
  }
  
  if ($start_year == "09" ) {
   $a = "2009-2-28";
  }
  
 

 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";

 mysqli_query($insightDBLink, $insert_sql);


 $feb_quantity = $qty_receipt_first;
  $feb_confirmflag = 1;
 
 } else {


   if ($quantity - $total_quantity_month >= 1) 
  {  $feb_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $feb_quantity = $quantity - $total_quantity_month; } else 
  {  $feb_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1)
  {  $feb_confirmflag = 1; } else 
  {  $feb_confirmflag = 0;  }


    //echo gmp_strval($div)."<br>";
  //echo "<br>"."20".$start_year."-2-1"." "."20".$start_year."-2-28"." ".$feb_quantity." ".$feb_confirmflag;
  $a = "20".$start_year."-2-1";
  
  
  
   $div = gmp_div_r($start_year, 4);
   
    if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
  //echo $start_year."leeds";
  if (gmp_strval($div) == 0) {
  $b = "20".$start_year."-2-29";
  } else { $b = "20".$start_year."-2-28";
  }
  
  if ($start_year == "09" ) {
   $b = "2009-2-28";
  }
  
  $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$feb_quantity', '$now', '$feb_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 
 }

 }
  $start_month = 'Mar';
  $total_quantity_month = $total_quantity_month + $feb_quantity;
  $total_billing_type = $total_billing_type + $feb_confirmflag;
 }



//Mar
if ($start_month == 'Mar') { 

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $mar_confirmflag = 1; } else 
  {  $mar_confirmflag = 0;  }


     //echo "<br>"."20".$start_year."-3-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$mar_confirmflag;
 
 $a = "20".$start_year."-3-1";
 $b = $quantity - $total_quantity_month;
 
  $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$mar_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
  break;
 } else {


if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-3-31"." ".$qty_receipt_first." 1";

$b = "20".$start_year."-3-31";
$a  = number_format($qty_receipt_first, 4);

 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$a', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);

 $mar_quantity = $qty_receipt_first;
  $mar_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }

  
  
  if ($quantity - $total_quantity_month >= 1) 
  {  $mar_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $mar_quantity = $quantity - $total_quantity_month; } else 
  {  $mar_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1) 
  {  $mar_confirmflag = 1; } else 
  {  $mar_confirmflag = 0;  }

    //echo "<br>"."20".$start_year."-3-1"." "."20".$start_year."-3-31"." ".$mar_quantity." ".$mar_confirmflag;
 
 
 $a = "20".$start_year."-3-1";
 $b = "20".$start_year."-3-31";
 
 
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$mar_quantity', '$now', '$mar_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 }

 
 }
$start_month = 'Apr';
$total_quantity_month = $total_quantity_month + $mar_quantity;
$total_billing_type = $total_billing_type + $mar_confirmflag;
 }
 
 
 
 
 
//Apr
if ($start_month == 'Apr') { 

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $apr_confirmflag = 1; } else 
  {  $apr_confirmflag = 0;  }
     //echo "<br>"."20".$start_year."-4-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$apr_confirmflag;
 
 $a = "20".$start_year."-4-1";
 $b = $quantity - $total_quantity_month;
 
  $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$apr_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
 
  break;
 } else {


if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-4-30"." ".$qty_receipt_first." 1";

$a = "20".$start_year."-4-30";
$b = number_format($qty_receipt_first, 4);

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);


 $apr_quantity = $qty_receipt_first;
  $apr_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
 
   if ($quantity - $total_quantity_month >= 1) 
  {  $apr_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $apr_quantity = $quantity - $total_quantity_month; } else 
  {  $apr_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1)
  {  $apr_confirmflag = 1; } else 
  {  $apr_confirmflag = 0;  }


//echo "<br>"."20".$start_year."-4-1"." "."20".$start_year."-4-30"." ".$apr_quantity." ".$apr_confirmflag;

$a = "20".$start_year."-4-1";
$b = "20".$start_year."-4-30";

 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$apr_quantity', '$now', '$apr_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 }


 
 }
 $start_month = 'May';
 $total_quantity_month = $total_quantity_month + $apr_quantity;
 $total_billing_type = $total_billing_type + $apr_confirmflag;
 }
 
 

//May
if ($start_month == 'May') {

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $may_confirmflag = 1; } else 
  {  $may_confirmflag = 0;  }

     //echo "<br>"."20".$start_year."-5-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$may_confirmflag;
 
$a =  "20".$start_year."-5-1";
$b = number_format($quantity - $total_quantity_month, 4);
 
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$may_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
  break;
 } else {

if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-5-31"." ".$qty_receipt_first." 1";


$a = "20".$start_year."-5-31";
$b = number_format($qty_receipt_first, 4);

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);

 $may_quantity = $qty_receipt_first;
  $may_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
 
  if ($quantity - $total_quantity_month >= 1) 
  {  $may_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $may_quantity = $quantity - $total_quantity_month; } else 
  {  $may_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1)
  {  $may_confirmflag = 1; } else 
  {  $may_confirmflag = 0;  }

//echo "<br>"."20".$start_year."-5-1"." "."20".$start_year."-5-31"." ".$may_quantity." ".$may_confirmflag;


$a = "20".$start_year."-5-1";
$b = "20".$start_year."-5-31";


$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$may_quantity', '$now', '$may_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
 }



  }
  $start_month = 'Jun';
  $total_quantity_month = $total_quantity_month + $may_quantity;
  $total_billing_type = $total_billing_type + $may_confirmflag;
 }
 
 
 

//Jun
if ($start_month == 'Jun') { 
 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $jun_confirmflag = 1; } else 
  {  $jun_confirmflag = 0;  }

     //echo "<br>"."20".$start_year."-6-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$jun_confirmflag;
 
 $a =  "20".$start_year."-6-1";
$b = number_format($quantity - $total_quantity_month, 4);
 
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$jun_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
  break;
 } else {

if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-6-30"." ".$qty_receipt_first." 1";

$a = "20".$start_year."-6-30";
$b = number_format($qty_receipt_first, 4);

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 $jun_quantity = $qty_receipt_first;
  $jun_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
 
 if ($quantity - $total_quantity_month >= 1) 
  {  $jun_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $jun_quantity = $quantity - $total_quantity_month; } else 
  {  $jun_quantity = 0;  }


    if ($billing_type - $total_billing_type >= 1)
  {  $jun_confirmflag = 1; } else 
  {  $jun_confirmflag = 0;  }

//echo "<br>"."20".$start_year."-6-1"." "."20".$start_year."-6-30"." ".$jun_quantity." ".$jun_confirmflag;

$a = "20".$start_year."-6-1";
$b = "20".$start_year."-6-30";


$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$jun_quantity', '$now', '$jun_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);

  
 }




 }
$start_month = 'Jul'; 
$total_quantity_month = $total_quantity_month + $jun_quantity;
$total_billing_type = $total_billing_type + $jun_confirmflag;
  
 }


//Jul
if ($start_month == 'Jul') { 

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $jul_confirmflag = 1; } else 
  {  $jul_confirmflag = 0;  }
     //echo "<br>"."20".$start_year."-7-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$jul_confirmflag;
 
 $a = "20".$start_year."-7-1";
 $b = $quantity - $total_quantity_month;
 
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$jul_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
  break;
 } else {


if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-7-31"." ".$qty_receipt_first." 1";

$a = "20".$start_year."-7-31";
$b = number_format($qty_receipt_first, 4);

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);

 $jul_quantity = $qty_receipt_first;
  $jul_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
 
  if ($quantity - $total_quantity_month >= 1) 
  {  $jul_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $jul_quantity = $quantity - $total_quantity_month; } else 
  {  $jul_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1)
  {  $jul_confirmflag = 1; } else 
  {  $jul_confirmflag = 0;  }

//echo "<br>"."20".$start_year."-7-1"." "."20".$start_year."-7-31"." ".$jul_quantity." ".$jul_confirmflag;

$a = "20".$start_year."-7-1";
$b = "20".$start_year."-7-31";

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$jul_quantity', '$now', '$jul_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 }
 
 
 }
 $start_month = 'Aug';
 $total_quantity_month = $total_quantity_month + $jul_quantity;
 $total_billing_type = $total_billing_type + $jul_confirmflag;
  
 }
 
 
//Aug
if ($start_month == 'Aug') { 

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $aug_confirmflag = 1; } else 
  {  $aug_confirmflag = 0;  }


     //echo "<br>"."20".$start_year."-8-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$aug_confirmflag;

$a =  "20".$start_year."-8-1";
$b = $quantity - $total_quantity_month;
 
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$aug_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
  break;
 } else {


if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-8-31"." ".$qty_receipt_first." 1";

$a = "20".$start_year."-8-31";
$b = number_format($qty_receipt_first, 4);


 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);

 $aug_quantity = $qty_receipt_first;
  $aug_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
 
  if ($quantity - $total_quantity_month >= 1) 
  {  $aug_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $aug_quantity = $quantity - $total_quantity_month; } else 
  {  $aug_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1) 
  {  $aug_confirmflag = 1; } else 
  {  $aug_confirmflag = 0;  }

//echo "<br>"."20".$start_year."-8-1"." "."20".$start_year."-8-31"." ".$aug_quantity." ".$aug_confirmflag ;

$a = "20".$start_year."-8-1";
$b = "20".$start_year."-8-31";

 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$aug_quantity', '$now', '$aug_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 }
 }
 $start_month = 'Sep';
 $total_quantity_month = $total_quantity_month + $aug_quantity;
 
 $total_billing_type = $total_billing_type + $aug_confirmflag;
 
 }

//Sep
if ($start_month == 'Sep')  { 

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $sept_confirmflag = 1; } else 
  {  $sept_confirmflag = 0;  }

     //echo "<br>"."20".$start_year."-9-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$sept_confirmflag;
 
 $a = "20".$start_year."-9-1";
 $b = $quantity - $total_quantity_month;
 
  $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$sept_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
  break;
 } else {


if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-9-30"." ".$qty_receipt_first." 1";

$a = "20".$start_year."-9-30";
$b = number_format($qty_receipt_first, 4);


 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);

 $sept_quantity = $qty_receipt_first;
  $sept_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
 
  if ($quantity - $total_quantity_month >= 1) 
  {  $sept_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $sept_quantity = $quantity - $total_quantity_month; } else 
  {  $sept_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1)
  {  $sept_confirmflag = 1; } else 
  {  $sept_confirmflag = 0;  }

//echo "<br>"."20".$start_year."-9-1"." "."20".$start_year."-9-30"." ".$sept_quantity." ".$sept_confirmflag ;

$a = "20".$start_year."-9-1";
$b = "20".$start_year."-9-30";

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$sept_quantity', '$now', '$sept_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 }
 
 }
 $start_month = 'Oct';
 $total_quantity_month = $total_quantity_month + $sept_quantity;
$total_billing_type = $total_billing_type + $sept_confirmflag;
 }



//Oct
if ($start_month == 'Oct') {  

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $oct_confirmflag = 1; } else 
  {  $oct_confirmflag = 0;  }

// echo "<br>"."20".$start_year."-10-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$oct_confirmflag;
 
 $a = "20".$start_year."-10-1";
 $b = $quantity - $total_quantity_month;
 
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$oct_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
  break;
 } else {



if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-10-31"." ".$qty_receipt_first." 1";

$a = "20".$start_year."-10-31";
$b = number_format($qty_receipt_first, 4);


 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);

 $oct_quantity = $qty_receipt_first;
  $oct_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
 
  if ($quantity - $total_quantity_month >= 1) 
  {  $oct_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $oct_quantity = $quantity - $total_quantity_month; } else 
  {  $oct_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1)
  {  $oct_confirmflag = 1; } else 
  {  $oct_confirmflag = 0;  }

//echo "<br>"."20".$start_year."-10-1"." "."20".$start_year."-10-31"." ".$oct_quantity." ".$oct_confirmflag;

$a = "20".$start_year."-10-1";
$b = "20".$start_year."-10-31";


$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$oct_quantity', '$now', '$oct_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 }
 
 
 }
 $start_month = 'Nov';
 $total_quantity_month = $total_quantity_month + $oct_quantity;
 $total_billing_type = $total_billing_type + $oct_confirmflag;
 
 }
 
 
 
//Nov
if ($start_month == 'Nov') { 

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $nov_confirmflag = 1; } else 
  {  $nov_confirmflag = 0;  }
     //echo "<br>"."20".$start_year."-11-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$nov_confirmflag;
 
 $a = "20".$start_year."-11-1";
 $b = $quantity - $total_quantity_month;
 
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$nov_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
  break;
 } else {

if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-11-30"." ".$qty_receipt_first." 1";

$a = "20".$start_year."-11-30";
$b = number_format($qty_receipt_first, 4);

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
 
 $nov_quantity = $qty_receipt_first;
  $nov_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
 
  if ($quantity - $total_quantity_month >= 1) 
  {  $nov_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $nov_quantity = $quantity - $total_quantity_month; } else 
  {  $nov_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1)
  {  $nov_confirmflag = 1; } else 
  {  $nov_confirmflag = 0;  }


//echo "<br>"."20".$start_year."-11-1"." "."20".$start_year."-11-30"." ".$nov_quantity." ".$nov_confirmflag;


$a = "20".$start_year."-11-1";
$b =  "20".$start_year."-11-30";

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$nov_quantity', '$now', '$nov_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 } 
 
 }
 
 $start_month = 'Dec';
 $total_quantity_month = $total_quantity_month + $nov_quantity;
 $total_billing_type = $total_billing_type + $nov_confirmflag;
 
 }
 
//Dec
if ($start_month == 'Dec') {  

 if ($start_month == $end_start_month && $start_year == $end_start_year ) {
 
 if ($billing_type - $total_billing_type >= 1) 
  {  $dec_confirmflag = 1; } else 
  {  $dec_confirmflag = 0;  }

     //echo "<br>"."20".$start_year."-12-1"." ".$end_date." ".($quantity - $total_quantity_month)." ".$dec_confirmflag;
 
 $a = "20".$start_year."-12-1";
 $b = $quantity - $total_quantity_month;
 
 $insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$b', '$now', '$dec_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$end_date')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 
  break;
 } else {




if ( $start_month ==  str_replace('�', '', date(�M�,strtotime($start_date))) && $start_year == str_replace('�', '', date(�y�,strtotime($start_date)))) {
 
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
//echo "<br>".$start_date." "."20".$start_year."-12-31"." ".$qty_receipt_first." 1";

$a = "20".$start_year."-12-31";
$b = number_format($qty_receipt_first, 4);


$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$receipt_date', '$b', '$now', '1', '0', '$now', '$PHP_AUTH_USER', '$start_date', '$a')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
 

 $dec_quantity = $qty_receipt_first;
  $dec_confirmflag = 1;
 
 } else {
 if (strlen($start_year) == 1) { $start_year = "0".$start_year; }
 
  if ($quantity - $total_quantity_month >= 1) 
  {  $dec_quantity = 1; } else if 
  ($quantity - $total_quantity_month < 1 && $quantity - $total_quantity_month  >= 0) {  $dec_quantity = $quantity - $total_quantity_month; } else 
  {  $dec_quantity = 0;  }

    if ($billing_type - $total_billing_type >= 1) 
  {  $dec_confirmflag = 1; } else 
  {  $dec_confirmflag = 0;  }
//echo "<br>"."20".$start_year."-12-1"." "."20".$start_year."-12-31"." ".$dec_quantity." ".$dec_confirmflag;

$a = "20".$start_year."-12-1";
$b = "20".$start_year."-12-31";

$insert_sql = "insert into tblPCReceipts (CostItemID, ReceiveDate, ReceiveQty, ConfirmDate, ConfirmFlag, HandshakeFlag, CreateDate, CreateUser, Datefrom, DateTo) 
values('$ID', '$a', '$dec_quantity', '$now', '$dec_confirmflag', '0', '$now', '$PHP_AUTH_USER', '$a', '$b')";
//echo "<br>".$insert_sql;
 mysqli_query($insightDBLink, $insert_sql);
  
 }

 
 
 }
 $start_month = 'Jan';
 $total_quantity_month = $total_quantity_month + $dec_quantity;
 $total_billing_type = $total_billing_type + $dec_confirmflag;
 $start_year = $start_year + 1;

}

 
 }
 
 // new report format end 
 
 //new logic end
 
 
 //epicor handshake add start
 
 $maxid_sql = "select sum(ReceiveQty) from tblPCReceipts where CostItemID = '$ID' and ConfirmFlag = '1'";
	$DBT1_max_id = mysqli_query($insightDBLink, $maxid_sql);
    $DBTrow_max_id = mysqli_fetch_row($DBT1_max_id);
  	$ReceiveQty =  $DBTrow_max_id[0];
	
	
	//echo "C".$ReceiveQty." ".$maxid_sql."<br>";
	
	
	
 if (($ReqDetailID == '' || $ReqDetailID == '0' )&& ($ItemStatus == 'ordered' || $ItemStatus == 'Ordered') && $qty_received_pur_list > 0 && $ReceiveQty  > $qty_received_pur_list && $logic_4044 <> '10') {
   $logic_4044 = 1;
   $ReceiveQty = $ReceiveQty - $qty_received_pur_list;
   }
   
    if (($ReqDetailID == '' || $ReqDetailID == '0' ) && ($ItemStatus == 'ordered' || $ItemStatus == 'Ordered') && $qty_received_pur_list > 0 && $ReceiveQty  < $qty_received_pur_list && $logic_4044 <> '10') {
   $logic_4044 = 2;
   $remain_amount = $qty_received_pur_list - $ReceiveQty;
     //echo "A".$remain_amount."<br>";
   do {
   
   $remain_amount =  $remain_amount - 1;
   //min unconfirm id 
    $min_sql = "select min(ID) from tblPCReceipts where CostItemID = '$ID' and ConfirmFlag = '0'";
	$DBT1_min_id = mysqli_query($insightDBLink, $min_sql);
    $DBTrow_min_id = mysqli_fetch_row($DBT1_min_id);
  	$min_id =  $DBTrow_min_id[0];
	
	
	//insert into Insight
	$query1 = "update tblPCReceipts set ConfirmFlag = 1, ConfirmDate = '$now' where ID = '".$min_id."'";
	
	mysqli_query($insightDBLink, $query1);
   //echo "B".$remain_amount."<br>";
     
    
   } while ( $remain_amount > 0);
   
   
    $maxid_sql = "select sum(ReceiveQty) from tblPCReceipts where CostItemID = '$ID' and ConfirmFlag = '1'";
	$DBT1_max_id = mysqli_query($insightDBLink, $maxid_sql);
    $DBTrow_max_id = mysqli_fetch_row($DBT1_max_id);
  	$ReceiveQty =  $DBTrow_max_id[0];
    $ReceiveQty = $ReceiveQty - $qty_received_pur_list;
	//echo $maxid_sql." ".$ReceiveQty."<br>";
   }
   
    if (($ReqDetailID == '' || $ReqDetailID == '0' ) && ($ItemStatus == 'ordered' || $ItemStatus == 'Ordered') && $qty_received_pur_list > 0 && $ReceiveQty  == $qty_received_pur_list) {
   $logic_4044 = 3;
   //$redirect_url = "Location: Receiving_edit.php?id=".$ID."";
   //header($redirect_url);
   }
	
	//echo $logic_4044." ".$ItemStatus." ".$qty_received_pur_list." ".$PrevID." ".$ReceiveQty;
	
	if (($logic_4044 == 0 || $logic_4044 == 1 || $logic_4044 == 2) && $purchase_status == 'Y' && $pur_list_status == 'Y' && $quantity_match == 'Y') {
	
	
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
	 
	$maxid_sql = "select sum(ReceiveQty) from tblPCReceipts where CostItemID = '$ID' and ConfirmFlag = '1'";
	$DBT1_max_id = mysqli_query($insightDBLink, $maxid_sql);
    $DBTrow_max_id = mysqli_fetch_row($DBT1_max_id);
  	$ReceiveQty =  $DBTrow_max_id[0];
    $ReceiveQty = $ReceiveQty - $qty_received_pur_list;
	 
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
	   
	
	 
	  //sqlsrv_query($link, "DISABLE TRIGGER t700insrec ON $database.dbo.receipts");
	   //echo $insert_sql."<br>".$update_release_sql."<br>".$update_pur_list_sql." 11".$ReceiveQty;
	   
	    if ( sqlsrv_query($link, "DISABLE TRIGGER t700insrec ON $database.dbo.receipts_all")) {
	 
	 } else {
	 echo "<center>vvError in Receiving Function!<br><br>Please try again</center>";
	exit;
	 }
	
	  if (sqlsrv_query($link, $insert_sql)) {
		//echo "<center>Received function completed and data inserted into Insight and Epicor database</center>";
			 
	} else {
	echo "<center>Error in Receiving Function!<br><br>Please try again</center>";
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
	 
	
 }
 
 //logic 4044
 
 
 //eipcor handshake add
 
 
 //eipcor handshake add
 
 
 
 
 // new logic
 //new logic
  $SQL_co = "SELECT ID FROM tblPCCostItem where id = (SELECT PrevID FROM tblPCCostItem WHERE ID = '$ID')";
  $DBT1_id = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_id = mysqli_fetch_row( $DBT1_id);
  $previous_id = $DBTrow_id[0];
  
  
  //1
  if ($previous_id <> '') {
  
  
  
  $SQL_co = "SELECT OrderType FROM tblPCReqDetail where id = (SELECT ReqdetailID FROM tblPCCostItem WHERE ID = '$ID')";
  $DBT1_order = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_order = mysqli_fetch_row( $DBT1_order);
  $OrderType = $DBTrow_order[0];
  
  
  // $StartDate = $vendor_start_billing_date;
    $StartDate =  $actual_term_date_old_costitem;
   
  
   //task 4426
  $SQL_co = "select Description from tblGenericCode where CodeValue = '$OrderType' and CodeType = 'CIStatus' and CodeCat = 'PROldItem' and   ActiveFlag = '1'";
  $DBT1_order = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_order = mysqli_fetch_row( $DBT1_order);
  $Old_costitem_desc = $DBTrow_order[0];
  
  
   //$SQL_update = "update tblPCCostItem set ItemStatus ='$OrderType', TermDate = '$actual_term_date_old_costitem' where id =  '$previous_id'";
   
  $SQL_update = "update tblPCCostItem set ItemStatus ='$Old_costitem_desc', TermDate = '$actual_term_date_old_costitem' where id =  '$previous_id'";
    
    mysqli_query($insightDBLink, $SQL_update);
  //echo $SQL_update ;
  
  
   $new_term_date = date('Y-m-d', strtotime('-1 day', strtotime($StartDate)));
   // $SQL_co = "select ID from tblPCReceipts where CostItemID = '$previous_id' and  DateFrom <= '$StartDate' and DateTo >= '$StartDate' ";
  $SQL_co = "select ID from tblPCReceipts where CostItemID = '$previous_id' and DateFrom <= '$new_term_date' and DateTo >= '$new_term_date' and ConfirmFlag <> '1'";
  
  
  $DBT1_VV = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_VV = mysqli_fetch_row( $DBT1_VV);
  $exceed_billing_ID_within = $DBTrow_VV[0];
  
   $SQL_co = "select ID from tblPCReceipts where CostItemID = '$previous_id' and DateFrom <= '$new_term_date' and DateTo >= '$new_term_date' ";
  
  
  $DBT1_GG = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_GG = mysqli_fetch_row( $DBT1_GG);
  $exceed_billing_ID_general = $DBTrow_GG[0];
  
  
  
 
   // NEW RECEIVING LOGIC 2
 if (  $exceed_billing_ID_general <> '') {
 $SQL_co = "delete from tblPCReceipts where CostItemID = '$previous_id' and  ID > '$exceed_billing_ID_within' and ConfirmFlag <> '1'";
   mysqli_query($insightDBLink, $SQL_co);
 //echo $SQL_co." "."<br>";
 }

  // NEW RECEIVING LOGIC 3
  //2
  if (  $exceed_billing_ID_within <> '') {
  
   $SQL_co = "SELECT StartDate FROM tblPCCostItem WHERE ID = '$previous_id'";
  $DBT1_StartDate_prev = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_StartDate_prev = mysqli_fetch_row( $DBT1_StartDate_prev);
  $Previous_ID_StartDate = $DBTrow_StartDate_prev[0];
  
  //echo $SQL_co;
      $Previous_ID_StartDate_day = str_replace('�', '', date(�j�,strtotime($Previous_ID_StartDate)));
	  $Previous_ID_StartDate_month = str_replace('�', '', date(�m�,strtotime($Previous_ID_StartDate)));
	  $Previous_ID_StartDate_year = str_replace('�', '', date(�Y�,strtotime($Previous_ID_StartDate)));
	  
 
	  
	  $StartDate_day = str_replace('�', '', date(�j�,strtotime($StartDate)));
	  $StartDate_month = str_replace('�', '', date(�m�,strtotime($StartDate)));
	  $StartDate_year = str_replace('�', '', date(�Y�,strtotime($StartDate)));  
	  
	
	
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
$DBT1_last = mysqli_query($insightDBLink, $SQL_co);
  $DBTrow_last = mysqli_fetch_row($DBT1_last);
  $actual_receive_qty = $DBTrow_last[0];

$Last_term_qty =  ($total_month + (($fullDays) / $factor))  - $actual_receive_qty ;
  
  
  //echo $total_month." ".$fullDays." ".$factor." ".$actual_receive_qty;
  
 $StartDate = date('Y-m-d', strtotime('-1 day', strtotime($StartDate)));
  
 $SQL_co = "update tblPCReceipts set ReceiveQty = '$Last_term_qty', DateTo = '$StartDate' where ID = '$exceed_billing_ID_within'";
   //mysqli_query($insightDBLink, $SQL_co);
   

   
}//2


}//1

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

}//end function


