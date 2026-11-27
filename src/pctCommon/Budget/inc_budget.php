<?php
// File Holder Kavis Yiu Task 14165 2014-12-09 Done

include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");

include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/pct_common_helpers.php");

// payment 页面处理程序
// 表单当前状态 可分为 new edit 和 save 状态
$action = 'new';
// Status 和 Role 列表
//$RoleAndStatusArr = getInvoiceStatus();
// migration variable start
$BRStatus = _request_if_not_set($BRStatus, 'BRStatus');
// migration variable start

	//Add Cost Type List
	$query = mysqli_query($insightDBLink, "SELECT CodeValue, Description, CodeCat  FROM tblGenericCode WHERE CodeType = 'CostType' AND ActiveFlag = 1 ORDER BY Sequence");
	while($value = mysqli_fetch_array($query)) {
		$purchase_type[] = $value;
	}
	
	//Add Country List
  	$query = mysqli_query($insightDBLink, "SELECT ID, CountryName FROM tblCountry ORDER BY CountryName");
  	while($value = mysqli_fetch_array($query)) {
		$country_list[] = $value;
  	}
	
 //echo $SQL_co;
// echo _post_function('issubmit').":"._post_function('issave');//exit();
#REGION issubmit==true || issave==true
if(_post_function('issubmit')=='true' || _post_function('issave')=='true') {
	$id = $_POST['id'] ?? '';
	$evl_ref_id = $id;
	session_start();
	$_SESSION["id"] = $id ;
	$CurrentStep = $_POST['CurrentStep'] ?? '';
	
	if(_post_function('issubmit')=='true'){
	if ($CurrentStep==0) {
		createStatusChangeEventLog($id, $PHP_AUTH_USER, "Save (Re-Activated) by ".getUserFullName($PHP_AUTH_USER), "Saved");// Kavis # 19941
	}
  // --- --- --- --- --- --- --- --- ---   保存第一层数据    (Saving data of the first layer) --- --- --- --- --- --- --- --- ---
  // 读取当前流程 (Read the current process)
	$InvFieldValue = "";	
	if (_post_function('id')=='' || $CurrentStep==0) {
		$InvFieldValue .= "CurrentStep='1',";
		$InvFieldValue .= "BRStatus='Save',";	
	}
	#JM No:31956
	if(_post_function('ContinueFlag')==true){
		$_POST['ContinueFlag']=1;
		}
 	$InvFieldValue .= "ContinueFlag='"._post_function('ContinueFlag')."',";
	
	
	$InvFieldValue .= "SalesProjectID='".unformatCode(_post_function('SalesProjectID'))."',";
	$InvFieldValue .= "ProjectNum='"._post_function('ProjectNum')."',";
	$InvFieldValue .= "ProjectName='"._post_function('ProjectName')."',";	
	$InvFieldValue .= "Description='"._post_function('Description')."',";
	$InvFieldValue .= "Justification='"._post_function('Justification')."',";
	$InvFieldValue .= "ImpactDesc='"._post_function('ImpactDesc')."',";
	$InvFieldValue .= "ProjectTargetRFS=".getInputDate('ProjectTargetRFS').",";
	$InvFieldValue .= "RequestedCapEx='".unformatAmount(_post_function('RequestedCapEx'))."',";
	//$InvFieldValue .= "ApprovedCapEx='"._post_function('ApprovedCapEx')."',";
	//$InvFieldValue .= "SpentCapEx='"._post_function('SpentCapEx')."',";
	$InvFieldValue .= "RequestedOpEx='".unformatAmount(_post_function('RequestedOpEx'))."',";
	//$InvFieldValue .= "ApprovedOpEx='"._post_function('ApprovedOpEx')."',";
	//$InvFieldValue .= "SpentOpEx='"._post_function('SpentOpEx')."',";
	$InvFieldValue .= "ProjectManager='"._post_function('ProjectManager')."',";
	$InvFieldValue .= "ProjectInitiator='"._post_function('ProjectInitiator')."',";
	$InvFieldValue .= "ProjectSponsor='"._post_function('ProjectSponsor')."',";
	$InvFieldValue .= "LastUpdUser='".$PHP_AUTH_USER."',";
	$InvFieldValue .= "LastUpdDate='".date('Y-m-d H:i:s')."'"; 
 
 
  if($id!='') {
    // 更新 Invoice 数据 (Update Invoice data)
	 $StatusSql = 'SELECT BRStatus,CurrentStep  FROM tblBRBudget WHERE ID ='.$id;
	   
	  
        $DBT1_3 = mysqli_query($insightDBLink, $StatusSql);
        $DBTrow_3 = mysqli_fetch_row($DBT1_3);
        $BRStatus = $DBTrow_3[0];
	    $CurrentStep = $DBTrow_3[1];
	
	
	if ( $CurrentStep <= 1 ) {
		/*if(_post_function('to_sumbit_mode')=='true'){
			$InvFieldValue .= ",CurrentStep='2'";
			$InvFieldValue .= ",BRStatus='Submitted'";
		}*/
    $updateSql = "UPDATE tblBRBudget SET ".$InvFieldValue." WHERE ID=".$id;
    mysqli_query($insightDBLink, $updateSql);
	//echo $updateSql;
	}	
	
	if(($CurrentStep >= 1 && $CurrentStep <= 11) && _post_function('to_cancel_mode')=='true'){
		$CancelValue = "CurrentStep='0'";
		$CancelValue .= ",BRStatus='Cancelled'";
		$updateSql = "UPDATE tblBRBudget SET ".$CancelValue." WHERE ID=".$id;
		mysqli_query($insightDBLink, $updateSql);
		
		$CancelDesc = "Cancelled by ".getUserFullName($PHP_AUTH_USER);
		createStatusChangeEventLog($id, $PHP_AUTH_USER, $CancelDesc, "Cancel");
	}
	
	//echo "Leeds United";
    // 如果需要更新流程 (If you need to update procesS)
  } else {
    // 增加第一层的数据 (Increasing the first data layer)
    $InvFieldValue .= ",CreateUser='".$PHP_AUTH_USER."',";
    $InvFieldValue .= "CreateDate='".date('Y-m-d H:i:s')."'";
    
    $insertSql = "INSERT INTO tblBRBudget SET ".$InvFieldValue;
    if(mysqli_query($insightDBLink, $insertSql)) {
      // 如果新增成功, 则加入流程数据
      $new_id = mysqli_insert_id($insightDBLink);
	  $evl_ref_id = $new_id;
	  InsertApproverPCActFlow($new_id, $PHP_AUTH_USER);
	  /*for($i=1; $i<=5; $i++ ) {
		InsertApproverPCActFlow($new_id, '', 2,'','');
	  }*/
    }
    //echo $insertSql;
	//die;
  }
  // 处理流程中用户
  //$FlowLength = getOneCell("SELECT COUNT(*) as FlowLength FROM tblGenericCode WHERE CodeType='ISStatus' AND CodeCat='Status'");
  //$SequenceSQL = "SELECT DISTINCT Sequence FROM tblGenericCode WHERE CodeType='ISStatus' AND (CodeCat='Status' OR CodeCat = 'CNStatus') ORDER BY Sequence ASC";
  //$res = getQueryResult($SequenceSQL);
  //$FlowLength = count($res);
  
  $DirectCost = false;
  // --- --- --- --- --- --- --- --- ---    保存第二层数据  (The second hierarchy data stored)  --- --- --- --- --- --- --- --- ---
  // 读取第二层数据 (Reads the second layer data)
  if ( $CurrentStep <= 1 ) {
	  $data_level2 = $_POST['data_level_row2'] ?? '';
	  $data_arr2 = [];
	  if($data_level2) {
		foreach($data_level2 as $row ) {
		  $rowdata = parse_data_row($row);
		  $data_arr2[] = $rowdata;
		}
	  }
	  //var_dump($data_arr2);

	  // Alan Ko : fix BR exchange of all BR line item according to tblExchange
	  $rateMap = [];
	  if (_post_function('issubmit')=='true') {
		  $q = mysqli_query($insightDBLink, "SELECT Rate, CurrencyFrom FROM tblExchange WHERE CurrencyTo = 'USD'");
		  while ($r = mysqli_fetch_assoc($q)) {
			$rateMap[$r["CurrencyFrom"]] = $r["Rate"];
		  }
	  }
	  
	  // 处理每一行数据
	  foreach($data_arr2 as $k=>$row) {
		$data_arr = [];
		$fields = '';
		$fields .= "BRID='".$evl_ref_id."',";
		//$fields .= "LineNum='".$row['LineNum']."',";
		$fields .= "LineNum='".($k+1)."',";
		$fields .= "Description='".preg_replace("/[\'\"]/", "", $row['Description'])."',"; // special characters list: ~!@#$%^&*()_+`:"|;'\<>?/,.=-[]{}		
		$fields .= "CCC='".$row['CCC']."',";
		$fields .= "SignedCo='".$row['SignedCo']."',";
		$fields .= "BudgetType='".$row['BudgetType']."',";		
		if($row['VendorCode']==''){ 
			$fields .= "VendorCode=NULL,";
			$fields .= "NewVendorName='".$row['VendorName']."',"; 
		} else {
			$fields .= "VendorCode='".$row['VendorCode']."',";
			$fields .= "NewVendorName=NULL,";
		}
		$fields .= "CostType='".$row['CostType']."',";
		$fields .= "CostCat='".$row['CostCat']."',";
		$fields .= "CostSubcat='".$row['CostSubcat']."',";
		$fields .= "Nomenclate='".$row['Nomenclate']."',";
		$fields .= "SRNum='".unformatCode($row['SRNum'])."',";
		$fields .= "OrderType='".$row['OrderType']."',";
		if($row['PrevCostItemID']==''){ 
			$fields .= "PrevCostItemID=NULL,";
		} else {
			$fields .= "PrevCostItemID='".unformatCode($row['PrevCostItemID'])."',";
		}
		
		$fields .= "FromAddress1='".$row['FromAddress1']."',";
		$fields .= "FromAddress2='".$row['FromAddress2']."',";
		$fields .= "FromAddress3='".$row['FromAddress3']."',";
		$fields .= "FromCountry='".$row['FromCountry']."',";
		$fields .= "FromCity='".$row['FromCity']."',";
		$fields .= "ToAddress1='".$row['ToAddress1']."',";
		$fields .= "ToAddress2='".$row['ToAddress2']."',";
		$fields .= "ToAddress3='".$row['ToAddress3']."',";
		$fields .= "ToCountry='".$row['ToCountry']."',";
		$fields .= "ToCity='".$row['ToCity']."',";
		//$fields .= "ExRate_USD='".$row['ExRate_USD']."',";
		// Alan Ko : fix br exchange rate
		if(_post_function('issubmit')=='true'){
		    $fields .= "ExRate_USD='".$rateMap[$row['Currency']]."',";
		}else{
			$fields .= "ExRate_USD='".$row['ExRate_USD']."',";
		}	 
		$fields .= "Currency='".$row['Currency']."',";
		if(_post_function('to_sumbit_mode')=='true'){ 			
			if(str_starts_with($row['CostType'], "COS")){
				$DirectCost = true;
			}
		}
		//#31956 Cost Saving
		if($row['CostSaving']>0)
			$fields .= "CostSaving='".$row['CostSaving']."',";
		else
			$fields .= "CostSaving=NULL,";
		$fields .= "Quantity='".$row['Quantity']."',";
		$fields .= "UOM='".$row['UOM']."',";
		$fields .= "ContractPeriod='".$row['ContractPeriod']."',";
		if($row['ContractStart']){
			$fields .= "ContractStart='".$row['ContractStart']."',";
		} else {
			$fields .= "ContractStart=NULL,";
		}
		if($row['ContractEnd']){
			$fields .= "ContractEnd='".$row['ContractEnd']."',";		
		} else {
			$fields .= "ContractEnd=NULL,";
		}
		$fields .= "UnitCost='".unformatAmount($row['UnitCost'])."',";
		
		$fields .= "MALineID='".$row['MALineID']."',";
		
		
		$fields .= "LastUpdUser='".$PHP_AUTH_USER."',";
		$fields .= "LastUpdDate='".date('Y-m-d H:i:s')."'";
		
		if($row['dataid']=='') {
			$fields .= ", CreateUser='".$PHP_AUTH_USER."',";
			$fields .= "CreateDate='".date('Y-m-d H:i:s')."'";
			$sql = "INSERT INTO tblBRBudgetLine SET ".$fields;
			$result = mysqli_query($insightDBLink, $sql);

			//$tempsql .= $sql;

		  if($result) {
			$newid = mysqli_insert_id($insightDBLink);
			$data_arr2[$k]['dataid'] = $newid;
		  }
		} else {
		  $newid = intval($row['dataid']);
		  $sql = "UPDATE tblBRBudgetLine SET ".$fields." WHERE ID=".$newid;
		  mysqli_query($insightDBLink, $sql);
		  
		  //$NextStepDebug .= $sql;
		  //$tempsql .= $sql;
		// die;
		 
		}   
		
	  }
	  
	  //ITR 43799 
	  //Alan Ko
	  $opex_sql = "select  SUM( Round( bl.ExRate_USD * bl.UnitCost * bl.Quantity * REPLACE(bl.contractPeriod, 0.0000, 1.0000), 2 ) ) AS lineamt from tblBRBudgetLine bl where budgetType='Opex' and brid='$evl_ref_id'";
      $opex_rs = mysqli_query($insightDBLink, $opex_sql);
      $opex_row = mysqli_fetch_row($opex_rs);
      $recalculated_opex = $opex_row[0];
	  //echo 	 $opex_sql. " " .$recalculated_opex;
	  //die();
	  
	  $capex_sql = "select SUM( Round( bl.ExRate_USD * bl.UnitCost * bl.Quantity * REPLACE(bl.contractPeriod, 0.0000, 1.0000), 2 ) ) AS lineamt from tblBRBudgetLine bl where budgetType='Capex' and brid='$evl_ref_id'";
      $capex_rs = mysqli_query($insightDBLink, $capex_sql);
      $capex_row = mysqli_fetch_row($capex_rs);
      $recalculated_capex = $capex_row[0];

	  //echo 	 $capex_sql. " " .formatNumber($recalculated_capex,2,1);
    	  
	  $updateSql = "UPDATE tblBRBudget SET ApprovedOpEx=0, ApprovedCapEx=0, SpentCapEx=0, SpentOpEx=0, AllocatedCapEx=0, AllocatedOpEx=0, RequestedOpEx=$recalculated_opex, RequestedCapEx=$recalculated_capex  WHERE ID='$evl_ref_id'";
	  mysqli_query($insightDBLink, $updateSql);
	  
	 // echo $updateSql;
	 // die();
  }
  
  //Sumbit Action
  $ref_id = $id!=''?$id:$new_id;
  if(_post_function('to_sumbit_mode')=='true'){ 
		$res = getBRStatus();
		
		$BURolename = $res[2]['RoleName'];
		$BURole = $res[2]['Role'];
		$BUHeadExist = false;
		
	  for($i=1; $i<=10; $i++ ) {

		$VerifiedUser = $_POST['BUHead_'.$i] ?? '';
		$ActFlowID = $_POST['BUHeadActFlowID_'.$i] ?? '';

		if($VerifiedUser!='' || $ActFlowID!=''){			
			$CapexLimit = unformatAmount(_post_function('capex_limit_'.$i));
			$OpexLimit = unformatAmount(_post_function('opex_limit_'.$i));

			if($ActFlowID==''){
				$BUHeadSQL ="INSERT INTO tblPCActFlow SET LevelInd='BR', ReferenceID='$ref_id',Sequence=2,
							Role='$BURolename',StatusName='NO',Amount1='$CapexLimit',Amount2='$OpexLimit',ActionUser='$VerifiedUser',Filter1='$BURole'";
				$BUHeadExist = true;
			} else {
				if($VerifiedUser==""){
					$BUHeadSQL ="DELETE FROM tblPCActFlow WHERE ID ='$ActFlowID' LIMIT 1";
				} else {
					$BUHeadSQL ="UPDATE tblPCActFlow SET ActionUser = '$VerifiedUser' , Amount1 = '$CapexLimit' , Amount2 = '$OpexLimit', StatusName ='NO', Filter1='$BURole'
								WHERE LevelInd='BR' AND ReferenceID='$ref_id' AND Sequence=2 AND ID ='$ActFlowID'";
					$BUHeadExist = true;
				}
			}
			mysqli_query($insightDBLink, $BUHeadSQL);
  
		}
	  }
	
    //Reset Sequence>2 in tblPCActFlow
	if($ref_id!=''){
		$del_sql = "DELETE FROM tblPCActFlow WHERE LevelInd='BR' AND ReferenceID='$ref_id' AND Sequence>2";
		mysqli_query($insightDBLink, $del_sql);
		$delrows = mysqli_affected_rows($insightDBLink);
	}
	
	
	$Capex = floatval(unformatAmount(_post_function('RequestedCapEx')));
	$Opex = floatval(unformatAmount(_post_function('RequestedOpEx')));
	
	if(_post_function('SalesProjectID')!='' || $DirectCost){
		$back_to_back_order = true;
	} else {
		$back_to_back_order = false;
	}
	
	if($BUHeadExist){
		$minSeq = 2;
	} else {
		$minSeq = "NOTFOUND";
	}
	
	$BRRole = getBRRole($back_to_back_order);
	foreach($BRRole as $k=>$row) {
		$RoleType = $BRRole[$k]['CodeCat'];
		$Seq = $BRRole[$k]['Sequence'];
		
		foreach ($res as $key => $val) {
           if($res[$key]['Sequence']==$Seq){
				break;
           }
        }
		
		$Rolename = $res[$key]['RoleName'];
	
		if($RoleType=="CapRole"){
			$Value = $Capex;
		} else {
			$Value = $Opex;
		}
		
		if($Value>=$BRRole[$k]['LowerLimit'] && $Value<=$BRRole[$k]['UpperLimit']){
			
			if($minSeq=="NOTFOUND" && (_post_function('ApproveCapSeq'.$Seq)!="" || _post_function('ApproveOpSeq'.$Seq)!="")){
				$minSeq = $Seq;
			}
			
			if($RoleType=="CapRole"){
				$ApproveUser = $_POST['ApproveCapSeq'.$Seq] ?? '';
				$Amountsql = ",Amount1='$Value'";
								
				if($ApproveUser==_post_function('ApproveOpSeq'.$Seq)){
					continue;
				}
			} else {
				$ApproveUser = $_POST['ApproveOpSeq'.$Seq] ?? '';
				$Amountsql = ",Amount2='$Value'";
				
				if($ApproveUser==_post_function('ApproveCapSeq'.$Seq)){
					$Amountsql .= ",Amount1='$Capex'";
				}
			}
			
			$CodeValue = $BRRole[$k]['CodeValue'];
			
			if($ApproveUser!=''){
				$BRRoleSQL ="INSERT INTO tblPCActFlow SET LevelInd='BR', ReferenceID='$ref_id',Sequence='$Seq',Role='$Rolename',StatusName='NO',ActionUser='$ApproveUser',Filter1='$CodeValue' ".$Amountsql;
				mysqli_query($insightDBLink, $BRRoleSQL);
			}

		}
		
	}
	
	$NextCurrentStep = $minSeq;
	$NextBRStatus = getBRStatusDescription($minSeq);

	//$updateSql = "UPDATE tblBRBudget SET CurrentStep='$NextCurrentStep', BRStatus='$NextBRStatus', ApprovedCapEx='".unformatAmount(_post_function('RequestedCapEx'))."' , ApprovedOpEx='".unformatAmount(_post_function('RequestedOpEx'))."', SpentCapEx=0, SpentOpEx=0, AllocatedCapEx=0, AllocatedOpEx=0 WHERE ID='$ref_id'";
	$updateSql = "UPDATE tblBRBudget SET CurrentStep='$NextCurrentStep', BRStatus='$NextBRStatus', ApprovedCapEx='".$recalculated_capex."' , ApprovedOpEx='".$recalculated_opex."', SpentCapEx=0, SpentOpEx=0, AllocatedCapEx=0, AllocatedOpEx=0 WHERE ID='$ref_id'";
	mysqli_query($insightDBLink, $updateSql);

	$OpenRoleSQL ="INSERT INTO tblPCActFlow (LevelInd, ReferenceID, Sequence) SELECT 'BR', '$ref_id' ,Sequence FROM tblGenericCode WHERE CodeType='BRStatus' AND CodeCat='Status' AND Sequence > 10";
	mysqli_query($insightDBLink, $OpenRoleSQL);

	//Send EventLog
	if($delrows==0){
		$Description = "Submitted by ".getUserFullName($PHP_AUTH_USER);
	} else {
		$Description = "Resubmitted by ".getUserFullName($PHP_AUTH_USER);
	}
	$ActionName = getActionName(1);
	
	createStatusChangeEventLog($ref_id, $PHP_AUTH_USER, $Description, $ActionName);

	// kavis task 
	mysqli_query($insightDBLink, "UPDATE tblPCActFlow SET Actionuser = '".$PHP_AUTH_USER."' WHERE LevelInd = 'BR' AND Role = 'Requester' AND ReferenceId = ".$ref_id);
	
	// Kavis Task 14165
	sendEmailStatusChange($ref_id,$PHP_AUTH_USER,$BRStatus,NULL,NULL,"Budget Approval : ".BRID($ref_id)."_Save_"._post_function('ProjectName'));
  }
  

  // --- --- --- --- --- --- --- --- ---    保存第二层 EventLog   --- --- --- --- --- --- --- --- ---
  // 读取EventLog数据
  /*$inv_event_log = _post_function('inv_event_log');
  $event_log_arr = array();
  if($inv_event_log) {
    foreach($inv_event_log as $row ) {
      $rowdata = parse_data_row($row);
      $event_log_arr[] = $rowdata;
    }
  }*/

// $karen=0;
/*
  foreach($event_log_arr as $row ) {
    if($row['DocType']!='') {
      // 当 DocType 有值时， 这个 EventLog 才算有效
      $eventSql = '';
      // 查询当前 EventLog 的 父对象
      $ReferenceID = '';
      if($row['parentdataid']!=''){
        $ReferenceID = $row['parentdataid'];
      } else {
        foreach($data_arr2 as $row2) {
          if($row['parentid'] == $row2['rowid']){
            $ReferenceID = $row2['dataid'];
            break;
          }
        }
      }
	  
	  	if ($row['DocType']=="DENY"){
			$inv_line = getInvoiceLine($ReferenceID);
			$inv_line["Role"] = getActFlow($evl_ref_id, "is");

			// recipient
			$to_id = $inv_line["Role"][$inv_line["CurrentStep"]];
			$to_emails = implode("';'", getRecipientEmails($to_id));
			
			if ($row['Email']==""){
				$row['Email'] = $to_emails;
			} else {
				$row['Email'] .= ";".$to_emails;
			}
			
		}

// $karen++;
// echo "<BR>karen inv details-".$karen;
      
      if($row['AttachId'] != '' ) {
        $eventSql = "UPDATE tblPCEventLog SET LevelInd='BRLine', ReferenceID='".$ReferenceID."', ".
          "LogDate='".date('Y-m-d H:i:s')."', LogUser='".$PHP_AUTH_USER."', DocType='".$row['DocType']."', ".
          "Description='".$row['Description']."', Action='".$row['Email']."'";	  
		$eventSql .= " WHERE ID='".intval($row['AttachId'])."'";
// echo "<BR>".$eventSql;
      } else {
        $eventSql = "INSERT INTO tblPCEventLog SET LevelInd='BRLine', ReferenceID='".$ReferenceID."', ".
          "LogDate='".date('Y-m-d H:i:s')."', LogUser='".$PHP_AUTH_USER."', DocType='".$row['DocType']."', ".
          "Description='".$row['Description']."', Action='".$row['Email']."'";
	
//echo "<BR>".$eventSql;

      }
      mysqli_query($insightDBLink, $eventSql);
	  if ($row['Email']!=""){
			//sendEmailEventLog($evl_ref_id, $PHP_AUTH_USER, $row['DocType'], $row['Description'], $row['AttachId'], $row['Email']);
	  }
	  //echo $eventSql;
    }
  }
*/  
  // --- --- --- --- --- --- --- --- --- 处理需要删除的数据 (Deleted data processing needs) --- --- --- --- --- --- --- --- ---
  // 读取需要删除的数据
   $data_moves  = $_POST['data_move_ids'] ?? '';

  if($data_moves!='' && is_array($data_moves) ) {
    foreach($data_moves as $mrow ) {
      $moverow_arr = explode(';', $mrow);
      foreach($moverow_arr as $moveval) {
        if($moveval!="") {
          move_one_row_data($moveval);
        }
      }
    }
  }
}
	// --- --- --- --- --- --- --- --- --- 处理 EventLog 数据 --- --- --- --- --- --- --- --- ---
	EditEventLog($evl_ref_id);
	SaveEventLogData($evl_ref_id);
	
	//to_cancel_mode
	if(_post_function('issave')=='true' && ($CurrentStep >= 1 && $CurrentStep <= 11) && _post_function('to_cancel_mode')=='true'){
		$CancelValue = "CurrentStep='0'";
		$CancelValue .= ",BRStatus='Cancelled'";
		$updateSql = "UPDATE tblBRBudget SET ".$CancelValue." WHERE ID=".$id;
		mysqli_query($insightDBLink, $updateSql);
	}
	
	//Change to Open Project
	if((_post_function('issave')=='true' || _post_function('to_approve_mode')=='true') && $CurrentStep==9){
		$approvesql = '';
			
		if(_post_function('ApprovedCapEx')){
			$approvesql = "ApprovedCapEx='".unformatAmount(_post_function('ApprovedCapEx'))."',";
		} else {
			$approvesql = "ApprovedCapEx=0,";
		}
		
		if(_post_function('ApprovedOpEx')){
			$approvesql .= "ApprovedOpEx='".unformatAmount(_post_function('ApprovedOpEx'))."',";
		} else {
			$approvesql = "ApprovedOpEx=0,";
		}
		
		if($approvesql){
			$approvesql .= "LastUpdUser='".$PHP_AUTH_USER."',";
			$approvesql .= "LastUpdDate='".date('Y-m-d H:i:s')."'";
			
			$updateSql = "UPDATE tblBRBudget SET $approvesql WHERE ID='$evl_ref_id'";
			mysqli_query($insightDBLink, $updateSql);
		}
		
		$TotalLine = getOneCell("SELECT COUNT(*) FROM tblBRBudgetLine WHERE BRID ='$evl_ref_id'");
		
		for($i=0; $i<$TotalLine; $i++ ) {
			$fields = "";
			$LineNum = $i+1;
			
			$WONum = $_POST['WONum_'.$i] ?? '';
			$RefNum = $_POST['RefNum_'.$i] ?? '';
			
			if($WONum){
				$fields .= "WONum='$WONum',";
			} else {
				$fields .= "WONum=NULL,";
			}
			
			if($RefNum){
				$fields .= "RefNum='$RefNum',";
			} else {
				$fields .= "RefNum=NULL,";
			}
			
			$fields .= "LastUpdUser='".$PHP_AUTH_USER."',";
			$fields .= "LastUpdDate='".date('Y-m-d H:i:s')."'";
			
			$sql = "UPDATE tblBRBudgetLine SET $fields WHERE BRID='$evl_ref_id' AND LineNum='$LineNum' LIMIT 1";
			mysqli_query($insightDBLink, $sql);
			
		}		
		
	}
	
	// --- --- --- --- --- --- --- --- --- Approval --- --- --- --- --- --- --- --- ---
	if(_post_function('to_approve_mode')=='true'){ 
		// Joan : task 12278 check if any BR item is capex type, if yes, no auto-submit PR
		ApproveBR($evl_ref_id, $CurrentStep, $BRStatus, $has_capex_item);
	}
	
	if(_post_function('to_deny_mode')=='true'){ 
		DenyBR($evl_ref_id, $CurrentStep);
	}
	
	if(_post_function('to_onhold_mode')=='true'){ 
		OnholdBR($evl_ref_id, $CurrentStep);
	}
  
  // 完成数据处理, 关闭窗口
  if($_SESSION["id"] !='' ) {
	if(_post_function('issave')=='true'){
		if(_post_function('to_approve_mode')=='true' || _post_function('to_deny_mode')=='true' || _post_function('to_onhold_mode')=='true' || _post_function('to_cancel_mode')=='true'){
			
			if(_post_function('to_approve_mode')=='true'){
				$action = "approved";
			} else if(_post_function('to_deny_mode')=='true'){
				$action = "denied";
			} else if(_post_function('to_onhold_mode')=='true'){
				$action = "on-hold";
			} else if(_post_function('to_cancel_mode')=='true'){
				$action = "cancelled";
			}
			
			echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
				<html xmlns=\"http://www.w3.org/1999/xhtml\">
				<head>	
				<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
				<link rel=\"stylesheet\" type=\"text/css\" href=\"/mainStyle.css\">
				<title></title>
				</head>
				<body>
				<center><p style=\"margin-top:15px\">";
			echo "<img src=\"/okay-icon.gif\" />The Budget Request <font color=red>".BRID($evl_ref_id)."</font> has been $action.";
			echo "</p><p style=\"margin-top:15px\">";
			echo "<input type=\"button\" value=\"Exit\" onclick=\"javascript:window.close()\" class=\"W100\">";			
			echo "</p></center>
				</body>
				</html>";
			exit;
		} else {
			$location = "Location: approval.php?id=".$_SESSION["id"];
		}
	} else {
		$location = "Location: budget_approval.php?id=".$_SESSION["id"]."&InvoiceLine="._post_function('CurrentInvoiceLine');
		if(_post_function('SaveNextStep')=="true"){
			$location .= "&NextStep=1#MACost";
		}
	}
    header($location);
	//header("Location: budget_approval.php?id=".$_SESSION["id"]."&InvoiceLine="._post_function('CurrentInvoiceLine').$tempsql);
  } else if ($_SESSION["id"] =='' ) {
    header("Location: budget_approval.php?id=".$evl_ref_id);
  }
  
  
  else {
    echo '<html><body>';
    echo '<script type="text/javascript">';
    echo 'window.opener = null;';
    echo 'window.open("","_self");';
    echo 'window.close()';
    echo '</script>';
    echo '</body></html>';
    die();
  }
}
#ENDREGION  issubmit==true || issave==true

// --- --- --- --- --- --- --- --- --- --- 结束 Submit 处理 (submit end processing) --- --- --- --- --- --- --- --- ---
// -------------------------------------------------------------------------------------------
// 
// --- --- --- --- --- --- --- --- --- --- 新增或编辑时读取数据  (add or edit when reading data)--- --- --- --- --- --- --- ---
$id = intval(_request_function('id'));

// 读取货币汇率数据
$currencyExchange = getCurrencyExchange();
$ajaxCurExchange = assocArrayToJson($currencyExchange);
// 如果ID存在则是编辑状态，否则就是新增状态
$InvHeaderData = [];
$InvLineJsonData = "[]";
$VchLineJsonData = "[]";
$MchLineJsonData = "[]";
$VCNchLineJsonData = "[]";
$CNMchLineJsonData = "[]";
$InvEventLogJsonData = "[]";

if($id>0) {
  $action = 'edit';
  // 读取第一层数据
 
  $fields .="ID, BRStatus, CreateUser, CreateDate, LastUpdUser, LastUpdDate, 
			SalesProjectID, ProjectNum, ProjectName, Description, Justification, ImpactDesc, ProjectTargetRFS,
			RequestedCapEx, ApprovedCapEx, SpentCapEx, AllocatedCapEx, RequestedOpEx, ApprovedOpEx, SpentOpEx, AllocatedOpEx,
			ProjectManager, ProjectInitiator, ProjectSponsor, CurrentStep, ContinueFlag ";
  $sql1 = 'SELECT '.$fields.' FROM tblBRBudget WHERE ID='.$id;
  $InvHeaderData = getOneRow($sql1);
  
  //echo $sql1;
  //die;
  
  // 处理第一层数据
  $InvHeaderData['FID'] = 'BR'.sprintf('%08d', $InvHeaderData['ID']);
  $InvHeaderData['SalesProjectID'] = SPID($InvHeaderData['SalesProjectID']);
  $InvHeaderData['CreateUserFullName'] = getUserFullName($InvHeaderData[CreateUser]);
  $InvHeaderData['LastUpdUserFullName'] = getUserFullName($InvHeaderData[LastUpdUser]);


  // 读取第二层数据
  $invline_sql = "SELECT ID, BRID,  MALineID, LineNum, OrderType, BudgetType, VendorCode, NewVendorName, 				
				CostType,  CostCat, CostSubcat, Nomenclate, SRNum, Description, CCC, SignedCo,
				FromAddress1, FromAddress2, FromAddress3, FromCountry, FromCity, 
				ToAddress1, ToAddress2, ToAddress3, ToCountry, ToCity, 
				Currency, ExRate_USD, UnitCost, UOM, Quantity, ContractPeriod, 
				ContractStart, ContractEnd, WONum, RefNum, PrevCostItemID, 
				CreateUser, CreateDate, LastUpdUser, LastUpdDate, CostSaving ".
	
 
  $invline_sql.="FROM tblBRBudgetLine WHERE BRID=".$id." ORDER BY LineNum ";
  $InvLineData = getQueryResult($invline_sql);
  
  
  $StatusDataArr = [];
  foreach($RoleAndStatusArr as $row) {
    $StatusDataArr[$row['Sequence']]=$row['Status'];
  }
  
  //$InvCurrency = $InvHeaderData['Currency'];
  
  //$LineStatusDataArr = array();

  foreach ($InvLineData as $k=>$row ) {
	
	$InvLineData[$k]['CostTypeDesc'] = findCostTypeDesc($InvLineData[$k]['CostType']);
	$InvLineData[$k]['CostCatDesc'] = findCostCatDesc($InvLineData[$k]['CostCat']);
	$InvLineData[$k]['CostSubcatDesc'] = findCostSubcatDesc($InvLineData[$k]['CostSubcat']);
	$InvLineData[$k]['NomenclateDesc'] = findNomenclateDesc($InvLineData[$k]['CostSubcat'],$InvLineData[$k]['Nomenclate']);
	
	$InvLineData[$k]['SRNum'] = ($InvLineData[$k]['SRNum']==0 || $InvLineData[$k]['SRNum']=='')?'':SRID($InvLineData[$k]['SRNum']);
	
	$InvLineData[$k]['FromCountryDesc'] = findCountryDesc($InvLineData[$k]['FromCountry']);
	$InvLineData[$k]['FromCityDesc'] = findCityDesc($InvLineData[$k]['FromCity']);
	$InvLineData[$k]['ToCountryDesc'] = findCountryDesc($InvLineData[$k]['FromCountry']);
	$InvLineData[$k]['ToCityDesc'] = findCityDesc($InvLineData[$k]['ToCity']);
	
	if($InvLineData[$k]['Currency']!=''){
		$InvLineData[$k]['CurrencyDesc'] = $InvLineData[$k]['Currency']." - ".findCurrencyDesc($InvLineData[$k]['Currency']);
	}
	
	if($InvLineData[$k]['UOM']!=''){
		$InvLineData[$k]['UOMDesc'] = findUOMDesc($InvLineData[$k]['UOM']);
	}
	
	if($InvLineData[$k]['CCC']!=''){
		$InvLineData[$k]['CCCDesc'] = $InvLineData[$k]['CCC']." - ".findCCCDesc($InvLineData[$k]['CCC']);
	}
	
	if($InvLineData[$k]['SignedCo']!=''){
		$InvLineData[$k]['SignedCoDesc'] = findSignedCoDesc($InvLineData[$k]['SignedCo']);
	}
	
	if($InvLineData[$k]['VendorCode']!=''){		
		$InvLineData[$k]['VendorName'] = findVendorName($InvLineData[$k]['VendorCode']);
	} else {		
		$InvLineData[$k]['VendorName']=$InvLineData[$k]['NewVendorName'];
	}
	
	if($InvLineData[$k]['ContractPeriod']==0){		
		$ContractPeriod = 1;
	} else {
		$ContractPeriod = $InvLineData[$k]['ContractPeriod'];
	}
	
	if($InvLineData[$k]['OrderType']!=''){
		$InvLineData[$k]['OrderTypeDesc'] = findOrderTypeDesc($InvLineData[$k]['OrderType']);
	}
	
	if($InvLineData[$k]['PrevCostItemID']){
		$InvLineData[$k]['PrevCostItemID'] = CIID($InvLineData[$k]['PrevCostItemID']);
	}
	
	$TotalCost = $InvLineData[$k]['Quantity']*$InvLineData[$k]['UnitCost']*$ContractPeriod;
	$InvLineData[$k]['TotalCost'] = formatNumber($TotalCost,2,1);
	// $InvLineData[$k]['ExRate_USD'] = $currencyExchange[$InvLineData[$k]['Currency']];
	//if($InvLineData[$k]['ExRate_USD']==0){
		$InvLineData[$k]['TotalCostUSD'] = formatNumber($InvLineData[$k]['ExRate_USD']*$TotalCost, 2, 1);
	//} else {
	//	$InvLineData[$k]['TotalCostUSD'] = formatNumber($InvLineData[$k]['ExRate_USD']*$TotalCost, 2, 1);
	//}
	// #31956 Cost Saving
	$InvLineData[$k]['CostSaving'] = $InvLineData[$k]['CostSaving'];
	$InvLineData[$k]['ItemCreateUser'] = getUserFullName($InvLineData[$k]['CreateUser']);
	$InvLineData[$k]['ItemLastUpdUser'] = getUserFullName($InvLineData[$k]['LastUpdUser']);
	$InvLineData[$k]['ItemCreateDate'] = date('d-M-Y H:i:s',strtotime($InvLineData[$k]['CreateDate']));
	$InvLineData[$k]['ItemLastUpdDate'] = date('d-M-Y H:i:s',strtotime($InvLineData[$k]['LastUpdDate']));

	
  }
  

  $InvLineJsonData = dyadicArrayToJson($InvLineData);
  
  // 读取 Settlement EventLog
  $PaymentEventLogSql = "SELECT * FROM tblPCEventLog WHERE LevelInd='BR' AND ReferenceID='".$id."' ORDER BY LogDate ASC";
  $PaymentEventLogData = getQueryResult($PaymentEventLogSql); 

} else {
  // 新增状态下 (New state)
  $InvHeaderData['FID'] = '<font color="red">New</font>';
}
/*
// 处理动态用户列表
$VerifyDataList = array();
$VerifyDataList = getVerifyDataList($id);
// 去除重复的角色
$HasRoleArr = array();
foreach($VerifyDataList as $k=>$row) {
  $now_role = $row['Role'];
  if(in_array($now_role, $HasRoleArr)){
    unset($VerifyDataList[$k]);
  } else {
    array_push($HasRoleArr, $now_role);
  }
}

$CurrentStep = "";
$CurrentButton = "";*/
$CurrentStep = $InvHeaderData['CurrentStep'];
/*foreach($VerifyDataList as $row ) {
  if($row['Sequence']==$CurrentStep) {
    $CurrentButton = $row['Button'];
    $CurrentStatus = $row['Status'];
    break;
  }
}*/

// 读取每个流程中的用户账号 (Read each process use account)
$FlowSql = "SELECT F.Sequence, F.ActionUser, F.StatusName, U.StaffNameFirst, U.StaffNameLast, U.StaffNickname, S.Region
			FROM tblPCActFlow AS F
			LEFT JOIN tblStaffInfo AS U ON U.LoginName = F.ActionUser
			JOIN tblGenericCode AS G ON F.Sequence = G.Sequence
			JOIN tblStaffGps AS S ON S.GroupID = G.CodeValue
			AND F.ActionUser = S.LoginName
			WHERE CodeType = 'ISStatus'
			AND CodeCat = 'Role'
			AND LevelInd = 'IS'
			AND ReferenceID = '$id'
			ORDER BY F.Sequence ASC";

$FlowArr = getQueryResult($FlowSql);
$FlowActData = [];
foreach($FlowArr as $k=>$row) {
  $row['UserFullName'] = getUserFullName($row);
  $FlowActData[$row['Sequence']]=$row;
}

createCT($BRStatus, null);

/** ---------------------------- 下列只有函数 ----------------------------------------------------- */
/**
 * 解析字符串为数组
 * @param <string> $str 字符串
 * @return <array> 返回数组数据
 */
function parse_data_row($str) {
  $result_arr = [];
  $data_arr = explode(';', $str );
  $reg_quot = "";
  $reg_eq   = "";
  $reg_Sem  = "";
  $search  = ['[val:quot]', '[val:equal]', '[val:sem]'];
  $replace = ['"', '=', ';'];
  
  foreach($data_arr as $cell) {
    $data = explode('=', $cell);
    // 处理转义字符
    $data[1] = str_replace($search, $replace, $data[1]);
    $result_arr[$data[0]]=$data[1];
  }
  return $result_arr;
}

/**
 * 删除指定的数据
 * @param <string> $movestr 格式为 ： “层数：ID”
 */
function move_one_row_data($movestr) {
  global $insightDBLink;
  $move_data = explode(':', $movestr);
  $level = $move_data[0];
  $dataid = $move_data[1];

  $move_sql = "DELETE FROM tblBRBudgetLine WHERE ID='".$dataid."'";
  mysqli_query($insightDBLink, $move_sql);
  //echo $move_sql;
 
  
  
}

/** edit EventLog   */
function EditEventLog($evl_ref_id) { 
  global $insightDBLink;
  global $PHP_AUTH_USER;
  // Edit Filter1 field in EventLog
  $EventLogIDArr = $_POST['EventLogID'] ?? '';  

  if(is_array($EventLogIDArr) && count($EventLogIDArr)>0 ) { 
    for($i=0; $i<count($EventLogIDArr); $i++) {
	  $EventLogID = $EventLogIDArr[$i];
      if($EventLogID!='') { 
        $EventLogRelatedItem = _post_function('EventLogRelatedItem')[$i];
        $eventSql = "UPDATE tblPCEventLog SET Filter1 ='$EventLogRelatedItem' WHERE ID='$EventLogID' AND LevelInd='BR' AND ReferenceID='$evl_ref_id' AND LogUser ='$PHP_AUTH_USER' LIMIT 1";
        mysqli_query($insightDBLink, $eventSql);
		
      }
    }
  }
}

/** 保存EventLog   */
function SaveEventLogData($evl_ref_id) { 
  global $insightDBLink;
  global $PHP_AUTH_USER;

  // 保存 BR 的 EventLog
  $PayLogDocType = $_POST['LogDocType'] ?? '';
  $PayLogDescription = $_POST['LogDescription'] ?? '';
  $PayLogAttachment = $_POST['LogAttachment'] ?? '';
  if(is_array($PayLogDocType) && count($PayLogDocType)>0 ) { 
    for($i=0; $i<count($PayLogDocType); $i++) { 
      if($PayLogDocType[$i]!='') { 
        // 当 DocType 有值时， 这个 EventLog 才算有效
        $DocType = _post_function('LogDocType')[$i];
        $Description = _post_function('LogDescription')[$i];
        $Attach = _post_function('LogAttachment')[$i];
		$Email = _post_function('LogEmailAddress')[$i];
		$RelatedItem = _post_function('LogEmailRelatedItem')[$i];
		
		if($Email!=""){
			$Email = explode(";", $Email);
			$Email = getRecipientEmails($Email);
			$Email = implode(";", $Email);
		}
		
		if ($DocType=="DENY"){
			$inv_header = getBRHeader($evl_ref_id);
			$inv_header["Role"] = getActFlow($evl_ref_id, "is");

			// recipient
			$to_id = $inv_header["Role"][$inv_header["CurrentStep"]];
			$to_emails = implode("';'", getRecipientEmails($to_id));
			
			if ($Email==""){
				$Email = $to_emails;
			} else {
				$Email .= ";".$to_emails;
			}
			
		}
        $eventSql = '';

// echo "<BR>karen inv header - ".$i;        
        if($Attach != '' ) {
          $eventSql = "UPDATE tblPCEventLog SET LevelInd='BR', ReferenceID='".$evl_ref_id."', ".
                  "LogDate='".date('Y-m-d H:i:s')."', LogUser='".$PHP_AUTH_USER."', DocType='".$DocType."', ".
                  "Description='".$Description."', Action='".$Email."', Filter1 ='".$RelatedItem."'";
		  $eventSql .= " WHERE ID='".intval($Attach)."'";
// echo "<BR>".$eventSql;
        } else {
          $eventSql = "INSERT INTO tblPCEventLog SET LevelInd='BR', ReferenceID='".$evl_ref_id."', ".
                  "LogDate='".date('Y-m-d H:i:s')."', LogUser='".$PHP_AUTH_USER."', DocType='".$DocType."', ".
                  "Description='".$Description."', Action='".$Email."', Filter1 ='".$RelatedItem."'";
 //echo "<BR>".$eventSql;
 
        }
        mysqli_query($insightDBLink, $eventSql);
		if ($Email!=""){
			$inv_header = getBRHeader($evl_ref_id);// kavis 33861
			sendEmailEventLog($evl_ref_id, $PHP_AUTH_USER, $DocType, $Description, $Attach, $Email, "[".($inv_header["ProjectNum"] ?: $inv_header["ProjectName"])."]");
		}
		
      }
    }
  }
}

/** 处理动态显示的字段 */
function getVerifyDataList($id) {
  $DataList = [];
//  $sql .= "SELECT S.Sequence,S.CodeType,S.CodeCat,S.CodeValue AS Button,S.Description, ";
//  $sql .= "R.CodeType AS RCodeType,R.CodeCat AS RCodeCat,R.CodeValue AS Role,R.Description AS LabelName ";
//  $sql .= "FROM tblGenericCode AS S, tblGenericCode AS R WHERE S.CodeType='ISStatus' AND S.CodeCat='Status' AND ";
//  $sql .= "R.CodeType='ISStatus' AND R.CodeCat='Role' AND S.Sequence=R.Sequence ORDER BY S.Sequence";
  $sql .= "SELECT s.Sequence,s.CodeValue as Button,s.Description as Status, r.CodeValue as Role, r.Description as RoleName ";
  $sql .= "FROM tblGenericCode as s left join  tblGenericCode as r on s.Sequence=r.Sequence and r.CodeType='ISStatus' and r.CodeCat='Role' ";
  $sql .= "WHERE s.CodeType='ISStatus' AND (s.CodeCat='Status' or s.CodeCat='CNStatus') ORDER BY s.Sequence ASC ";
  //$sql .= "WHERE s.CodeType='ISStatus' AND s.CodeCat='CNStatus' ORDER BY s.Sequence ASC ";

//echo $sql;

  $DataList = getQueryResult($sql);
  $ISCLoginName = findISCLoginName($id);
  foreach($DataList as $k=>$row ){
    $usql = '';
    $usql .= "SELECT U.StaffNameFirst, U.StaffNameLast, U.StaffNickname, U.LoginName FROM  tblStaffInfo AS U  , tblStaffGps AS G ";
    $usql .= "WHERE U.LoginName=G.LoginName AND U.LoginName=G.LoginName AND G.GroupID='".$row['Role']."' ";
	//if ($row['Role']=="ISV" || $row['Role']=="ISVC"){
	if ($row['Role']=="ISV" || $row['Role']=="ISVC"){
		$usql .= "AND (G.Filter1 LIKE concat('%',(SELECT Region FROM tblStaffGps WHERE GroupID='ISC' AND LoginName = '$ISCLoginName'),'%') OR G.Filter1='ALL') ";	
	}
    $usql .= "ORDER BY CONCAT(U.StaffNameLast, U.StaffNameFirst, U.StaffNickname) ";
//echo "<BR>".$usql;    
    $UserData = getQueryResult($usql);
    $UserList = [];
    foreach($UserData as $urow ){
      $UserList[] = [$urow['LoginName'], getUserFullName($urow)];
    }
    $DataList[$k]['UserList'] = $UserList;
  }
  
  return $DataList;
}

function getBudgetHeaderInfo($pr_id) {
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

function sendEmailEventLog($inv_id, $user, $doc_type, $doc_details, $log_id, $emails, $sub) { 
	global $server_path;

	// prepare data
	$inv_id_long = BRID($inv_id);
	$attachment = findAttachmentName($log_id);

	// recipient
	$to_emails = explode(";", $emails);
	$cc_emails = getRecipientEmails($user);	
	
	// subject
	$subject = "BR Event Log : $inv_id_long $sub";

	// content message
	$message .= "Event Log has been added for $inv_id_long. Please note the change to the BR and proceed on actions when appropriate.\r\n\n";
	$message .= "BR ID :  $inv_id_long\r\n\n";
	$message .= "Event Log Type :  ".findBREventLogDocTypeDesc($doc_type)."\r\n\n";
	if ($doc_details) $message .= "Event Log Description :  $doc_details\r\n\n";
	if ($attachment) $message .= "File Attachment :  $attachment\r\n\n";
	$message .= "\r\n\n";
	$message .= "Please click below for Budget Approval $inv_id_long details.\r\n\n";
	$message .="************************************************************\r\n\n";
	$message .="Please Note: If clicking on the URL does not work,\r\n\n";
	$message .="please copy and paste the entire URL in your browser window\r\n\n";
	$message .="************************************************************\r\n\n";
	$message .= "$server_path".preg_replace('/.*htdocs/','',getcwd())."/approval.php?id=".$inv_id;
	
	// sendEmail($to_emails, $subject, $message, $cc_emails, $attachment);
	sendEmail($to_emails, $subject, $message, $cc_emails);
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

function formatAccCode($code) {
	if ($code) {
		$code = unformatAccCode($code);
		
		$seg1 = substr($code, 0, 5);
		$seg2 = substr($code, 5, 4);
		$seg3 = substr($code, 9, 4);
		$seg4 = substr($code, 13);
		
		$code = $seg1."-".$seg2."-".$seg3."-".$seg4;
	}
	return $code;
}

function unformatAccCode($code) {
	if ($code) {
		$code = preg_replace("/[^A-Za-z0-9]/", "", $code); // special characters list: ~!@#$%^&*()_+`:"|;'\<>?/,.=-[]{}
	}
	return $code;
}

function getElementObjectId(){
	$RandNum = mt_rand(100000, 999999);
	$id = "obj_".$RandNum;
	return $id;
}

function getParentId($ID,$InvLineArr){
	foreach($InvLineArr as $row) {
		if($row['ID']==$ID){
			return $row['RowID'];
		}
	}
}

function getRandomRow(){
	return time().mt_rand(10000, 99999);
}
/*
function showCloseButton($ID, $CurrentStep){
	if($CurrentStep==11){
		$outstandingPR = getOneCell("SELECT COUNT(*) from tblPCReqHeader where BudgetID = '$ID' and CurrentStep NOT IN (5,6,12)");
		//echo $outstandingPR; echo "SELECT COUNT(*) from tblPCReqHeader where BudgetID = '$ID' and CurrentStep in (5,6,12) = 0";die;
		if($outstandingPR==0){
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}*/

function getUOMList() {
	$sql = "SELECT CodeValue, Description FROM tblGenericCode WHERE CodeType = 'UOM' AND ActiveFlag =1 ORDER BY Sequence";
	$Data = getQueryResult($sql);
	$dataArr = [];
	foreach($Data as $row ){
		$dataArr[] = [$row['CodeValue'], $row['Description']];
	}
	return createOptionHtml($dataArr, $default);
}

function getCCCList() {
	$sql = "SELECT CCC, Description FROM tblCCC WHERE Active_Cost =1 ORDER BY CCC ASC";
	$Data = getQueryResult($sql);
	$dataArr = [];
	foreach($Data as $row ){
		$dataArr[] = [$row['CCC'], $row['CCC']." - ".$row['Description']];
	}
	return createOptionHtml($dataArr, $default);
}

function getSignedCompany() {
	// Joan : task 17306 check ActiveBR instead of Active_Purchase
	//$sql = "SELECT ID, CONCAT_WS(' - ',CoName,Branch) as Description from tblRegisteredCo where Active_Purchase=1 ORDER BY CoName ASC";
	$sql = "SELECT ID, CONCAT_WS(' - ',CoName,Branch) as Description from tblRegisteredCo where ActiveBR = 1 ORDER BY CoName ASC";
	$Data = getQueryResult($sql);
	$dataArr = [];
	foreach($Data as $row ){
		$dataArr[] = [$row['ID'], $row['Description']];
	}
	return createOptionHtml($dataArr, $default);
}

function findCCCDesc($value) {
	$sql = "SELECT Description FROM tblCCC WHERE CCC = '$value'";
	return getOneCell($sql);
}

function findSignedCoDesc($value) {
	$sql = "SELECT CONCAT_WS(' - ',CoName,Branch) FROM tblRegisteredCo WHERE ID = '$value'";
	return getOneCell($sql);
}

function getActionType() {
	$sql = "SELECT CodeValue, Description FROM tblGenericCode WHERE CodeType = 'ActionCode' AND CodeValue!='TERM' AND ActiveFlag = 1 ORDER BY Sequence";
	$Data = getQueryResult($sql);
	$dataArr = [];
	foreach($Data as $row ){
		$dataArr[] = [$row['CodeValue'], $row['Description']];
	}
	return createOptionHtml($dataArr, $default);
}


createCT($id);

/**
 * Creates a CT when action type = Terminate and once the BR has been submitted and status is 'OPEN'.
 * 
 * @param unknown $brStatus
 */
function createCT($brStatus, $data=null) {
	
	// var_dump($_POST);
	//echo ob_get_contents();
	//header("Location: budget_newct.php?id=".$brStatus);

	//if ($brStatus && $brStatus == 'OPEN') {
		$data_level2 = $_POST['data_level_row2'] ?? '';
		$data_arr2 = [];
		
		if($data_level2) {
			foreach($data_level2 as $row ) {
				$rowdata = parse_data_row($row);
				$data_arr2[] = $rowdata;
			}
		}
		
		$prIds = [];
		$i = 0;
		foreach($data_arr2 as $k=>$row) {
			$orderType = $row['OrderType'];
			$prIds[$i] = $i++;
			if ($orderType == 'TERMINATE') {
				//TODO create a CT
			}
		}
		
		$var = strstr($prIds, ",");
		
// 		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
// 				<html xmlns=\"http://www.w3.org/1999/xhtml\">
// 				<head>
// 				<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
// 				<link rel=\"stylesheet\" type=\"text/css\" href=\"/mainStyle.css\">
// 				<title></title>
// 				</head>
// 				<body>
// 				<center><p style=\"margin-top:15px\">";
// 		echo "<img src=\"/okay-icon.gif\" />The Budget Request <font color=red>".$var."</font> has been $action.";
// 		echo "</p><p style=\"margin-top:15px\">";
// 		echo $brStatus;
// 		echo "<input type=\"button\" value=\"Exit\" onclick=\"javascript:window.close()\" class=\"W100\">";
// 		echo "</p></center>
// 				</body>
// 				</html>";
// 		exit;

	//}
}
