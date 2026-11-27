<?php 

include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");
include_once( $_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/auth.php");

include_once($insight_helper_path . "/db_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/pct_common_helpers.php");

// migration log start
include_once($insight_lib_path . '/log4php/apache-log4php-2.3.0/src/main/php/Logger.php');
$logger = Logger::getLogger('pr-log'); // declare default logger as $logger
// migration log end

// handshake_received(797190);

function handshake_received ($ID) {
	$qty_received_pur_lis = null;
 global $logger;	// migration debug
	global $insightDBLink;

   
	global $PHP_AUTH_USER;
	global $EPI_HOSTNAME;
	global $EPI_USERNAME;
	global $EPI_PASSWORD;
	
	//  $link=mssql_connect($EPI_HOSTNAME, $EPI_USERNAME, $EPI_PASSWORD);

$now=date("Y-m-d H:i:s");

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
		 
	
	$ReceiveQty =  $Quantity;
	 	 
	 
	$link = dbSqlSrvConnect($EPI_HOSTNAME, $database, $EPI_USERNAME, $EPI_PASSWORD);
	//  mssql_select_db ($database, $link);  
	 //[2019.02.19,Ken] Epicor v7.4 changed the table as view (from receipts to receipts_all)
	 $maxid_sql = "select max(receipt_no)  as maxid from  receipts_all ";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $maxid = $DBTrow_max_id[0];
	
	//echo $maxid;
	$maxid = $maxid + 1;
	
	
		
	
		  
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
	
	
	
	  
	  //CHECK 2 PO status
	   $maxid_sql = "select status, vendor_no, curr_key, oper_factor, curr_factor from purchase where po_no = '$PONum' ";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $status_epicor = $DBTrow_max_id[0];
	$vendor_no_epicor = $DBTrow_max_id[1];
	$curr_key_epicor = $DBTrow_max_id[2];
	$oper_factor_epicor = $DBTrow_max_id[3];
	$curr_factorr_epicor = $DBTrow_max_id[4];
	
	
	
	
	 
	 
	  //CHECK 3 pur_list status
	   $maxid_sql = "select status from pur_list where po_no = '$PONum' and line = '$POLineNum'";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $status_epicor = $DBTrow_max_id[0];
	
	
	
	 
	 
	//CHECK 4 Epicor PO quantity match
	   $maxid_sql = "select qty_ordered, qty_received from pur_list where po_no = '$PONum' and line = '$POLineNum'";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $qty_ordered_epicor = $DBTrow_max_id[0];
	  $qty_received_pur_list = $DBTrow_max_id[1];
	
   
  
	
	//get release date from table releases
	   $maxid_sql = "select release_date, received, quantity,  { fn SECOND(release_date) } AS Expr1 from releases where po_no = '$PONum' and po_line = '$POLineNum'";
	   
	  
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $rel_date_epicor = $DBTrow_max_id[0];
	$received_releases = $DBTrow_max_id[1];
	$quantity_releases = $DBTrow_max_id[2];
	$expr1 = $DBTrow_max_id[3];
	
	
// 	  $rel_date_epicor_day = str_replace('�', '', date(�d�,strtotime($rel_date_epicor)));
//   $rel_date_epicor_month = str_replace('�', '', date(�m�,strtotime($rel_date_epicor)));
//   $rel_date_epicor_year = str_replace('�', '', date(�y�,strtotime($rel_date_epicor)));   
//    $rel_date_epicor_hour = str_replace('�', '', date(�h�,strtotime($rel_date_epicor))); 
//    $rel_date_epicor_minute = str_replace('�', '', date(�i�,strtotime($rel_date_epicor)));     
//   $rel_date_epicor_second = str_replace('�', '', date(�s�,strtotime($rel_date_epicor)));   
//   $rel_date_epicor_after = str_replace('�', '', date(�A�,strtotime($rel_date_epicor)));  
	

	$rel_date_epicor_day = str_replace('"', '', (string) $rel_date_epicor->format("d"));
	$rel_date_epicor_month = str_replace('"', '', (string) $rel_date_epicor->format("m"));
	$rel_date_epicor_year = str_replace('"', '', (string) $rel_date_epicor->format("y"));   
	 $rel_date_epicor_hour = str_replace('"', '', (string) $rel_date_epicor->format("h")); 
	 $rel_date_epicor_minute = str_replace('"', '', (string) $rel_date_epicor->format("i"));     
	$rel_date_epicor_second = str_replace('"', '', (string) $rel_date_epicor->format("s"));   
	$rel_date_epicor_after = str_replace('"', '', (string) $rel_date_epicor->format("A"));   
  
  $rel_date_epicor = "20".$rel_date_epicor_year."-".$rel_date_epicor_month."-".$rel_date_epicor_day." ".$rel_date_epicor_hour.":".$rel_date_epicor_minute.":".$expr1." ".$rel_date_epicor_after;
	


	  //update next_rec_batch_no.last_number 
	  $update_release_sql = "update next_rec_batch_no set last_number = last_number + 1"; 
	 sqlsrv_query($link, "$update_release_sql"); 
	 
	 //get receipt batch no
	$maxid_sql = "select last_number from next_rec_batch_no";
	$DBT1_max_id =  sqlsrv_query($link, $maxid_sql);	// 2020: without connection before migration
    $DBTrow_max_id = sqlsrv_fetch_array($DBT1_max_id);
    $receipt_batch_no = $DBTrow_max_id[0];
	 
	 $ext_cost = $unit_cost_epicor * $ReceiveQty;


	 //[2019.02.19,Ken] Epicor v7.4 changed the table as view (from receipts to receipts_all)
	 $insert_sql =  "insert into $database.dbo.receipts_all (receipt_no, conv_factor, ext_cost, status, freight_cost, quantity, unit_cost, receipt_batch_no, recv_date, release_date, po_no, part_no, location, vendor, po_line, part_type, sku_no, unit_measure, prod_no, account_no, 
					who_entered, bl_no, lb_tracking, freight_unit, po_key, nat_curr, oper_factor, curr_factor, oper_cost, curr_cost, freight_flag, qc_flag, qc_no, rejected, over_flag, std_cost, std_direct_dolrs, std_ovhd_dolrs, std_util_dolrs, vend_inv_no, pro_number, return_code, note, voucher_no, freight_vendor, freight_inv_no, freight_account, tax_included ) values 
                   ('$maxid', '$conv_factor', '$ext_cost', 'R', '0', '$ReceiveQty', '$unit_cost_epicor', '$receipt_batch_no', '$now', '$rel_date_epicor', '$PONum', '$part_no_epicor', '$location_epicor', '$vendor_no_epicor', '$POLineNum', '$type_epicor', '$vend_sku_epicor', '$unit_measure', '0', '$account_no',  
					'$PHP_AUTH_USER', '0', '$lb_tracking', '0', '$po_key', '$curr_key_epicor', '$oper_factor_epicor', '$curr_factorr_epicor', '$oper_cost', '$curr_cost', 'N', 'N', '0', '0', 'N', '0', '0', '0', '0', NULL,  NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0')";
	
	 //[2019.02.19,Ken] Epicor v7.4 changed the table as view (from receipts to receipts_all)
	  sqlsrv_query($link, "DISABLE TRIGGER t700insrec ON $database.dbo.receipts_all");
	   //echo $insert_sql."<br>".$update_release_sql."<br>".$update_pur_list_sql;
	  if (sqlsrv_query($link, $insert_sql)) {
		//echo "<center>Received function completed and data inserted into Insight and Epicor database</center>";
			 
	} else {
	echo "<center>Error in Receiving Function!<br><br>Please try again</center>";
	exit;
}
	
	//echo $insert_sql;
	//[2019.02.19,Ken] Epicor v7.4 changed the table as view (from receipts to receipts_all)
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

