<?php
$brid=$_GET['brid'] ?? '';
// include_once('../../../phpInc/config.inc.php');    // use config variable
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");
// include_once('../../../phpInc/encrypt.inc.php');    // use phpInc variable
include_once($insight_phpInc_path . "/encrypt.inc.php");
// include_once('../../Lib/common.inc.php');    // use lib variable
include_once($insight_lib_path . "/common.inc.php");
$GpsAuth=NULL;
// include_once('../../../phpInc/auth.inc.php');    // use phpInc variable
include_once($insight_phpInc_path . "/auth.inc.php");
// include_once('../../../phpInc/email.inc.php');    // use phpInc variable
include_once($insight_phpInc_path . "/email.inc.php");
include_once('../online_cost/PR/ReceiveNRC.php');
include_once('../online_cost/PR/ReceiveMRC.php');
include_once('../online_cost/PR/ReceiveNRC_epicor.php');
include_once('../online_cost/PR/ReceiveMRC_epicor.php');
include_once('../online_cost/PR/CostRequestRecv.php');
include_once('../online_cost/PR/PRFunction.php');
include_once("../online_cost/suppliercost/QuantityChecking.php");
// CSCM-81
include($insight_lib_path . "/duplicateCIchecking.php");

include_once($insight_helper_path . "/db_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/pct_common_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");

session_start();




//Align Auto-received to insight user
function  GenerateCostItem($ID, $special_6896 = null) {
	global $insightDBLink;
	$query = mysqli_query ( $insightDBLink, "SELECT * FROM tblPCActFlow WHERE LevelInd = 'PRReq' AND Sequence=1 AND ReferenceID = '$ID'" );
	$value = mysqli_fetch_array ( $query );
	$tempRequester = $value ['ActionUser'];
	
 
	if ($special_6896){
            $createUser = 'insight';    
            $lastUpdUser = 'insight';

            // Joan : add to insert 7 new added field (task 16598)
            $sql = "INSERT INTO tblPCCostItem (ReqDetailID, BudgetItemID, SignedCo,PrevID,VendorType,VendorCode,SRNum,ProjectNum,CostType,CostCat,CostSubcat,Nomenclate,ReachCost,POFlag,PONum,POLineNum,LISPONum,PODate,VendorContact,ItemCode,Description,UOM,Currency,UnitCost,Quantity,USDAmount,EvergreenFlag,BillingPeriod,TermNoticeDay,StartDate,EndDate,PopID,AendAddr,BendAddr,GLCode,CCC,WONum,ItemStatus,OccupyFlag,RequestUser,CreateUser,CreateDate,LastUpdUser,LastUpdDate,Make,ModelNum,PartNum,BudgetType,PopID_Bend,Bandwidth,Bandwidth_byMI,HalfCircuit,Protected,MiuPointUnit,MiuPointAmt,CostNature) 
                                    select tblPCReqDetail.ID, BudgetItemID, SignedCo,PrevCostItemID,VendorType, VendorCode,SRNum,ProjectNum,CostType, CostCat,CostSubcat,Nomenclate,ReachCost,POFlag,PONum,LineNum,LISPONum,PODate,VendorName,ItemCode,tblPCReqDetail.Description,UOM,Currency,UnitCost,Quantity,round(round(Quantity*UnitCost,2)*ExRate_USD,2),EvergreenFlag,BillingPeriod,TermNoticeDay,StartDate,EndDate,PopID,AendAddr,BendAddr, GLCode,CCC,WONum,'Ordered','0','" . $tempRequester . "','".$createUser."',CreateDate,'".$lastUpdUser."',LastUpdDate,Make,ModelNum,PartNum,BudgetType,PopID_Bend,Bandwidth,Bandwidth_byMI,HalfCircuit,Protected,MiuPointUnit,MiuPointAmt,CostNature
                                    from tblPCReqDetail left join tblPCReqHeader on ReqID = tblPCReqHeader.ID where tblPCReqHeader.ID = '$ID'";
            //echo "<br>SQL 1= ".$sql."<br>"; ;
        }else{
            $createUser = 'CreateUser';
            $lastUpdUser = 'LastUpdUser';
            
            // Joan : add to insert 7 new added field (task 16598)
          $sql = "INSERT INTO tblPCCostItem (ReqDetailID, BudgetItemID, SignedCo,PrevID,VendorType,VendorCode,SRNum,ProjectNum,CostType,CostCat,CostSubcat,Nomenclate,ReachCost,POFlag,PONum,POLineNum,LISPONum,PODate,VendorContact,ItemCode,Description,UOM,Currency,UnitCost,Quantity,USDAmount,EvergreenFlag,BillingPeriod,TermNoticeDay,StartDate,EndDate,PopID,AendAddr,BendAddr,GLCode,CCC,WONum,ItemStatus,OccupyFlag,RequestUser,CreateUser,CreateDate,LastUpdUser,LastUpdDate,Make,ModelNum,PartNum,BudgetType,PopID_Bend,Bandwidth,Bandwidth_byMI,HalfCircuit,Protected,MiuPointUnit,MiuPointAmt,CostNature) 
                                    select tblPCReqDetail.ID, BudgetItemID, SignedCo,PrevCostItemID,VendorType, VendorCode,SRNum,ProjectNum,CostType, CostCat,CostSubcat,Nomenclate,ReachCost,POFlag,PONum,LineNum,LISPONum,PODate,VendorName,ItemCode,tblPCReqDetail.Description,UOM,Currency,UnitCost,Quantity,round(round(Quantity*UnitCost,2)*ExRate_USD,2),EvergreenFlag,BillingPeriod,TermNoticeDay,StartDate,EndDate,PopID,AendAddr,BendAddr, GLCode,CCC,WONum,'Ordered','0','" . $tempRequester . "',CreateUser,CreateDate,LastUpdUser,LastUpdDate,Make,ModelNum,PartNum,BudgetType,PopID_Bend,Bandwidth,Bandwidth_byMI,HalfCircuit,Protected,MiuPointUnit,MiuPointAmt,CostNature
                                    from tblPCReqDetail left join tblPCReqHeader on ReqID = tblPCReqHeader.ID where tblPCReqHeader.ID = '$ID'";
          # echo "<br>SQL 2= ".$sql."<br>"; 
        }
		 
	mysqli_query ( $insightDBLink, $sql );
	// echo $sql;
	
	// staggered rates - update PrevCostItemID in tblPCReqDetail
	$sql = "UPDATE tblPCReqDetail d LEFT JOIN tblPCReqDetail p ON d.PrevPRLineID = p.LineNum				
				LEFT JOIN tblPCCostItem c ON p.ID = c.ReqDetailID 
				SET d.PrevCostItemID = c.ID WHERE d.ReqID = $ID AND p.ReqID = $ID";
	mysqli_query ( $insightDBLink, $sql );
	// staggered rates - update OccupyFlag in tblPCCostItem
	$sql = "UPDATE tblPCCostItem SET OccupyFlag = 1 
				WHERE ID IN (SELECT PrevCostItemID FROM tblPCReqDetail WHERE ReqID = $ID AND PrevPRLineID > 0)";
	mysqli_query ( $insightDBLink, $sql );
	
	// new task
	
	$sql = "select ID, PrevCostItemID, OrderType from tblPCReqDetail where ReqID = '$ID' order by ID asc";
	
	$result_1 = mysqli_query ( $insightDBLink, $sql );
	for($t = 1; $t <= mysqli_num_rows ( $result_1 ); $t ++) {
		$DBTrow_1 = mysqli_fetch_row ( $result_1 );
		$tblPCReqDetail_ID = $DBTrow_1 [0];
		$PrevCostItemID = $DBTrow_1 [1];
		$OrderType = $DBTrow_1 [2];
		
		if ($t == 1) {
			$first_ordertype = $OrderType;
		}
		
		if ($t > 1 && ($first_ordertype == 'NEW_SERVIC' || $first_ordertype == 'RENEW') && $OrderType == 'RENEW') {
			$query = "update tblPCCostItem set PrevID = '$PrevCostItemID' where ReqDetailID  = '$tblPCReqDetail_ID'  ";
			mysqli_query ( $insightDBLink, $query );
			// echo "<br>".$query;
		}
		
		// #19301 - copy previous cost item's the Vendor Order Ref. Number, Vendor Circuit ID and PCCWG Service ID to the new cost item
		$vendor_circuit_sql = "select VendorCircuitID, VendorDocNum, PCCWGServiceID from tblPCCostItem where ID = '$PrevCostItemID'";
		$vendor_circuit_result = mysqli_query ( $insightDBLink, $vendor_circuit_sql );
		while ( $row = mysqli_fetch_object ( $vendor_circuit_result ) ) {
			$vcID = $row->VendorCircuitID;
			$vcDN = $row->VendorDocNum;
			$vcSI = $row->PCCWGServiceID;
			$vcID = is_null ( $vcID ) ? 'NULL' : "'" . $vcID . "'";
			$vcDN = is_null ( $vcDN ) ? 'NULL' : "'" . $vcDN . "'";
			$vcSI = is_null ( $vcSI ) ? 'NULL' : "'" . $vcSI . "'";
			$update_vc_sql = "update tblPCCostItem SET VendorCircuitID = $vcID, VendorDocNum = $vcDN, PCCWGServiceID = $vcSI where ReqDetailID = '$tblPCReqDetail_ID'";
			// $update_vc_sql = "update tblPCCostItem SET VendorCircuitID = '$vcID', VendorDocNum = '$vcDN', PCCWGServiceID = '$vcSI' where ReqDetailID = '$tblPCReqDetail_ID'";
			mysqli_query ( $insightDBLink, $update_vc_sql );
		}
		mysqli_free_result ( $vendor_circuit_result );
		// #19301
	}
	 GeneratePO($ID);
}
#====================

	function copyFromHidden($hidden_field) {
		for ($i = 0; $i < count($hidden_field); $i++) {
			$to_field[$i] = $hidden_field[$i]; 
		}
		return $to_field;
	}

function releaseOldCostItem($pr_id) {
		global $insightDBLink;
		$sql = "SELECT tblPCCostItem.ID 
			FROM tblPCCostItem LEFT OUTER JOIN tblPCReqDetail ON tblPCCostItem.ID = PrevCostItemID
			WHERE ReqID = '$pr_id'";
		$query = mysqli_query($insightDBLink, $sql);
		if ($query) {
			$str_id = implode(",", mysqli_fetch_array($query));
			$sql = "UPDATE tblPCCostItem SET OccupyFlag = 0 WHERE ID IN ($str_id)";
			$query = mysqli_query($insightDBLink, $sql);
			if ($query) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	 
function GeneratePO($ID){
	global $insightDBLink;
		
		

	$query = mysqli_query($insightDBLink, "SELECT * FROM tblPCReqHeader WHERE ID = '$ID'") or die(mysqli_error($insightDBLink));
	$ReqInfo = mysqli_fetch_array($query);


	 

	 
	//===================================
		$q="SELECT lpad(CoCode, 3, '0') FROM tblRegisteredCo LEFT OUTER JOIN tblCo_Entities ON SignedCO = tblRegisteredCo.ID WHERE ID ='$ReqInfo[SignedCo]'";
		$query = mysqli_query($insightDBLink, $q);
		$company_code = dbSelectColumnFromQuery($query, 0);
		$EPI_DB_NAME = "BTN".$company_code;
		Global $EPI_HOSTNAME,$EPI_USERNAME,$EPI_PASSWORD,$PHP_AUTH_USER,$brid;
		$x = dbSqlSrvConnect($EPI_HOSTNAME, $EPI_DB_NAME, $EPI_USERNAME, $EPI_PASSWORD);
		
		// if (!$epicor_Conn) {
		// 	die('Unable to connect or select database!');
		// }
		if (!$x) {
			die('Unable to connect database!');
		}
		// get loaction list
		$query = sqlsrv_query($x, "select location, name from locations where void != 'V' order by location"); 
		while ($result = sqlsrv_fetch_array($query)) {
			$loc_list[$result["location"]] = $result["name"];
		}

		// get terms code list
		$query = sqlsrv_query($x, "SELECT terms_code, terms_desc FROM apterms");
		while ($result = sqlsrv_fetch_array($query)) {
			$terms_list[$result["terms_code"]] = $result["terms_desc"];
		}	
		
		sqlsrv_close($x);	

	// #18583 - If signed company is Gateway, do not handshake to Epicor but handshake PO to SAP when PR has been approved. (CurrentStep 3 -> 4)
	$genPOtoEpicor = true;
	if ($action=="Approve" && handshakeEpicorOrSAP($ReqInfo["SignedCo"]) == 'SAP') {
		$genPOtoEpicor = false;
		// if it is Gateway company, insert queue to wait for process to handshake SAP(to generate xml files)
		$insert_queueSQL = "Insert into tblQueue (SystemID, RequestStatus, DateCreated, ActionType, ActionID, ActionStatus) values ('SAP', 1, NOW(), 2, '$ID', 'create')";
		mysqli_query($insightDBLink, $insert_queueSQL);
	}
	//====================================
	$query ="SELECT * FROM tblPCReqDetail WHERE ReqID = '$ID'";
	$query = mysqli_query($insightDBLink, $query);

	while ($value = mysqli_fetch_array($query)) {
		$value["int_srnum"] = $value["SRNum"];
		$value["SRNum"] = $value["SRNum"] ? SRID($value["SRNum"]) : "";
		$value["OldCostName"] = $value["PrevCostItemID"] ? CIID($value["PrevCostItemID"]) : "";		

		$query2 = sqlsrv_query($x, "SELECT account_type from glchart WHERE account_code='".str_replace("-", "", $value[GLCode])."'");
		$value["wo_check"] = sqlsrv_fetch_array($query2);
		$value["wo_check"] = $value["wo_check"][0] == 150 ? true : false;
		
		 $ReqDetail[$value["LineNum"]-1] = $value;
	}
	$dateFormat = date("Y-m-d H:i:s", time());
	$Detailnum = count($ReqDetail);
	$q="SELECT ContinueFlag FROM tblBRBudget WHERE ID = '$brid'";
	$q = mysqli_query($insightDBLink, $q);
	$r = mysqli_fetch_assoc($q);
	
	if ( $r["ContinueFlag"]==1){
		include("../online_cost/PR/insertPO.php");
		$CIIDSql = "select id from tblPCCostItem where ReqDetailID IN(select ID from tblPCReqDetail where ReqID = '$ID' and OrderType = 'CONTINUE')";
		$CIIDResult = mysqli_query($insightDBLink, $CIIDSql);
		while($CIRow = mysqli_fetch_assoc($CIIDResult) ) {
			$q="SELECT oldCI.SerialNum FROM tblPCCostItem oldCI,tblPCCostItem newCI,tblPCReqDetail d  WHERE newCI.ReqDetailID = d.id and d.PrevCostItemID = oldCI.id and newCI.id = ".$CIRow['id'];
			$q = mysqli_query($insightDBLink, $q);
			$r = mysqli_fetch_assoc($q);
			$serial_num = $r["SerialNum"];
			
			$sql = "UPDATE tblPCCostItem SET PONum = '$new_po_no',PODate = '$dateFormat',SerialNum = '$serial_num'  WHERE id = ".$CIRow['id'];
			mysqli_query($insightDBLink, $sql);
			$eventSql = "INSERT INTO tblPCEventLog SET LevelInd='CostItem', ReferenceID='".$CIRow['id']."', ".
			  "LogDate='".date('Y-m-d H:i:s')."', LogUser='insight', DocType='AddInfo', Description='Created by Continue BR:".$brid."'";
			mysqli_query($insightDBLink, $eventSql);
		}
	}
	else if ($ReqInfo["CurrentStep"] == 3 ) {
		include("../online_cost/PR/insertPO.php");
		//echo "<B>Generated PO: ".$new_po_no."</B>";
	}
}

$q1="SELECT distinct(h.id) AS PRID FROM tblPCReqHeader h, tblPCReqDetail d WHERE h.BudgetID='{$brid}' and h.id = d.ReqID and d.orderType = 'CONTINUE' ";
$q1 = mysqli_query($insightDBLink, $q1);
while($pr_row = mysqli_fetch_array($q1)){	
$prid=$pr_row["PRID"];				
    // CSCM-81
    if (!dupCIcheckByReqID ($prid))
	GenerateCostItem($prid, $special_6896);
	#receiveATFCostItem_renew ($prid,$PHP_AUTH_USER);
	
	receiveATFCostItem($prid, 'insight');
	//$Actsql = "UPDATE tblPCCostItem SET ItemStatus = 'Renewed' WHERE ID IN (SELECT PrevCostItemID FROM tblPCReqDetail WHERE ReqID = '$prid' AND PrevCostItemID>0)";
	//$query = mysqli_query($insightDBLink, $Actsql); 
	$Actsql = "UPDATE tblPCReceipts SET ConfirmFlag = '1' WHERE CostItemID IN ( select ci.id from tblPCCostItem ci,  tblPCReqDetail d WHERE d.ReqID = '$prid' and ci.reqdetailid = d.id)";
	$query = mysqli_query($insightDBLink, $Actsql);
	
}
?><body> 
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="/mainStyle.css">
<title></title>


<center>
<p style="margin-top:15px">
<img src="/okay-icon.gif">The Budget Request <font color="red"><?php echo $brid; ?> </font>has been approved  </p> <br>
<?php 
$q1="SELECT distinct(h.id) AS PRID FROM tblPCReqHeader h, tblPCReqDetail d WHERE h.BudgetID='{$brid}' and h.id = d.ReqID and d.orderType = 'CONTINUE' ";
$q1 = mysqli_query($insightDBLink, $q1);
while($pr_row = mysqli_fetch_array($q1)){	
$prid=$pr_row["PRID"];				
 ?>
 <br />Purchase Request  <font color="red"><?php echo $prid; ?></font> has been approved and Received   
 <?php
 			$getCIsql="SELECT * FROM tblPCCostItem WHERE ReqDetailID IN (SELECT ID FROM tblPCReqDetail WHERE ReqID='{$prid}')";
			$getCIsql=mysqli_query($insightDBLink, $getCIsql);
			while($cirow=mysqli_fetch_array($getCIsql)){
				echo "<br />Cost Item ID : <font color=red>".$cirow['ID']."</font>";
			 
				} 
	 
}
?>
<br />
 

 




<p style="margin-top:15px"><input type="button" value="Exit" onclick="javascript:window.close()" class="W100"></p>
</center>

</body>
