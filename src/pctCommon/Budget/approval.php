<?php
#File holder Joseph oct 20,2015 task 31982
#File holder Joseph feb 17,2016 task No:31956
// 使用 UTF-8 编码
header("Content-type:TEXT/HTML;charset=UTF-8");

// 导入配置文件和登录验证
// include_once('../../../phpInc/config.inc.php');    // use config variable
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");
// 处理数据加密
// include_once('../../../phpInc/encrypt.inc.php');    // use phpInc variable
include_once($insight_phpInc_path . "/encrypt.inc.php");
// include_once('../../Lib/common.inc.php');    // use lib variable
include_once($insight_lib_path . "/common.inc.php");

include_once($insight_helper_path . "/db_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/pct_common_helpers.php");

// 导入公共函数
include_once 'functions_budget.php';
// 处理当前表单许的用户组
$GpsAuth=NULL;
// 用户权限验证
// include_once('../../../phpInc/auth.inc.php');    // use phpInc variable
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/auth.php");


//getAppUser();
// 导入逻辑处理文件
include_once 'inc_budget.php';

//AdditionalbR
// include_once("reCalculateBR.php");
if($InvHeaderData["ID"]==""){
	echo "No record found";
	exit();
}
/* BR Approval Section */
//echo $InvHeaderData["ID"];
$BUHead_List = getBUHeadList($InvHeaderData["ID"]);
$ApprovalList = getApprovalList($InvHeaderData["ID"]);
//var_dump($ApprovalList);
$rate_to_usd = $currencyExchange[$InvHeaderData["Currency"]];

if (checkEditable($InvHeaderData['ID'],$InvHeaderData['CurrentStep']) && $InvHeaderData[CurrentStep]==9){
	$EditApprovedAmt = true;
} else {
	$EditApprovedAmt = false;
}
/*
if($InvHeaderData['MasterBR']){
 $masterbrtitle="Master BR";
	$masterbrval=$InvHeaderData['MasterBR'];
	}*/
$section[1]["title"] = [
    //"br_id"=>"Budget Request ID",
    "BRStatus"=>"Budget Request Status",
    "SalesProjectID"=>"Sales Project ID",
    "ProjectNum"=>"Project No",
    "ProjectName"=>"Project Name",
    "BudgetDescription"=>"Budget Description",
    "Justification"=>"Justification",
    "Impact"=>"Impact if not approved",
    "ProjectTargetRFS"=>"Project Target RFS Date",
    "ProjectManager"=>"Project Manager",
    "ProjectInitiator"=>"Project Initiator",
    "ProjectSponsor"=>"Project Sponsor",
    "CreateBy"=>"Create By",
    "CreateDate"=>"Create Date",
    "LastUpdateUser"=>"Last Update User",
    "LastUpdateDate"=>"Last Update Date",
];

    
$section[1][] = [
    //"br_id"=>$InvHeaderData["FID"],
    "BRStatus"=>$InvHeaderData[BRStatus],
    "SalesProjectID"=>$InvHeaderData[SalesProjectID],
    "ProjectNum"=>$InvHeaderData[ProjectNum],
    "ProjectName"=>$InvHeaderData[ProjectName],
    "BudgetDescription"=>$InvHeaderData[Description],
    "Justification"=>$InvHeaderData[Justification],
    "Impact"=>$InvHeaderData[ImpactDesc],
    "ProjectTargetRFS"=>$InvHeaderData[ProjectTargetRFS],
    "ProjectManager"=>getUserFullName($InvHeaderData[ProjectManager]),
    "ProjectInitiator"=>getUserFullName($InvHeaderData[ProjectInitiator]),
    "ProjectSponsor"=>getUserFullName($InvHeaderData[ProjectSponsor]),
    "CreateBy"=>$InvHeaderData['CreateUserFullName'],
    "CreateDate"=>date('d-M-Y H:i:s',strtotime($InvHeaderData[CreateDate])),
    "LastUpdateUser"=>$InvHeaderData['LastUpdUserFullName'],
    "LastUpdateDate"=>date('d-M-Y H:i:s',strtotime($InvHeaderData[LastUpdDate])),
];

$section[2]["title"] = ["inv_line_num"=>"No", "column_BudgetType"=>"Budget Item Type", "column_CostTypeDesc"=>"Cost Type", "column_CostCatDesc"=>"Item Category", "column_CurrencyDesc"=>"Currency", "column_TotalCost"=>"Total Cost", "column_TotalCostUSD"=>"Total Cost USD", "column_SRNum"=>"SR Number", "column_Description"=>"Item Description", "column_WONum"=>"WO#", "column_RefNum"=>"Capex/Opex Ref. No", "column_VendorName"=>"Vendor Name", "column_CostSubcatDesc"=>"Item Sub-Category", "column_NomenclatureDesc"=>"Nomenclature", "column_CCC"=>"CCC", "column_SignedCo"=>"Signed Company", "column_UnitCost"=>"Unit Cost", "column_UOM"=>"UOM", "column_CostSaving"=>"Cost Saving", "column_Quantity"=>"Quantity", "column_ContractPeriod"=>"Contract Period", "column_ContractStart"=>"Contract Start", "column_ContractEnd"=>"Contract End", "column_OrderTypeDesc"=>"Action Type", "column_PrevCostItemID"=>"Old Cost Item", "column_FromAddress1"=>"From Address Line 1", "column_FromAddress2"=>"From Address Line 2", "column_FromAddress3"=>"From Address Line 3", "column_FromCountryDesc"=>"From Country", "column_FromCityDesc"=>"From City", "column_ToAddress1"=>"To Address Line 1", "column_ToAddress2"=>"To Address Line 2", "column_ToAddress3"=>"To Address Line 3", "column_ToCountryDesc"=>"To Country", "column_ToCityDesc"=>"To City", "column_ItemCreateUser"=>"Create User", "column_ItemCreateDate"=>"Create Date", "column_ItemLastUpdUser"=>"Last Update User", "column_ItemLastUpdDate"=>"Last Update Date"];
$DirectCost = false;
$DirectCostFlag = true;
foreach ($InvLineData as $k=>$line) {
	// Joan : task 12278
	//if ($line["BudgetType"]=="Capex") $HasCapexItem = 1;
	
	if(str_starts_with($row['CostType'], "COS") && $DirectCostFlag){
		$DirectCost = true;
	} else {
		$DirectCost = false;
		$DirectCostFlag = false;
	}
	$section[2][] = ["inv_line_num"=>$line["LineNum"], "column_BudgetType"=>$line["BudgetType"], "column_CostTypeDesc"=>$line["CostTypeDesc"], "column_CostCatDesc"=>$line["CostCatDesc"], "column_CurrencyDesc"=>$line["CurrencyDesc"], "column_TotalCost"=>"<div align='right'>".$line["TotalCost"]."</div>", "column_TotalCostUSD"=>"<div align='right'>".$line["TotalCostUSD"]."</div>", "column_SRNum"=>$line["SRNum"], "column_Description"=>$line["Description"], "column_WONum"=>($EditApprovedAmt && $line["BudgetType"]=="Capex")?"<input type=\"text\" name=\"WONum_$k\" id=\"WONum_$k\" size=\"8\" maxlength=\"8\"value=\"".$line["WONum"]."\">":$line["WONum"], "column_RefNum"=>($EditApprovedAmt)?"<input type=\"text\" name=\"RefNum_$k\" id=\"RefNum_$k\" value=\"".$line["RefNum"]."\">":$line["RefNum"], "column_VendorName"=>$line["VendorName"], "column_CostSubcatDesc"=>$line["CostSubcatDesc"], "column_NomenclatureDesc"=>$line["NomenclateDesc"], "column_CCC"=>$line["CCCDesc"], "column_SignedCo"=>$line["SignedCoDesc"], "column_UnitCost"=>"<div align='right'>".$line["UnitCost"]."</div>", "column_UOM"=>$line["UOMDesc"], "column_CostSaving"=>$line["CostSaving"], "column_Quantity"=>"<div align='right'>".$line["Quantity"]."</div>", "column_ContractPeriod"=>"<div align='right'>".$line["ContractPeriod"]."</div>", "column_ContractStart"=>$line["ContractStart"], "column_ContractEnd"=>$line["ContractEnd"], "column_OrderTypeDesc"=>$line["OrderTypeDesc"], "column_PrevCostItemID"=>$line["PrevCostItemID"], "column_FromAddress1"=>$line["FromAddress1"], "column_FromAddress2"=>$line["FromAddress2"], "column_FromAddress3"=>$line["FromAddress3"], "column_FromCountryDesc"=>$line["FromCountryDesc"], "column_FromCityDesc"=>$line["FromCityDesc"], "column_ToAddress1"=>$line["ToAddress1"], "column_ToAddress2"=>$line["ToAddress2"], "column_ToAddress3"=>$line["ToAddress3"], "column_ToCountryDesc"=>$line["ToCountryDesc"], "column_ToCityDesc"=>$line["ToCityDesc"], "column_ItemCreateUser"=>$line["ItemCreateUser"], "column_ItemCreateDate"=>$line["ItemCreateDate"], "column_ItemLastUpdUser"=>$line["ItemLastUpdUser"], "column_ItemLastUpdDate"=>$line["ItemLastUpdDate"]];
	
}

$section[3]["title"] = ["BudgetType"=>"Budget Item Type", "Requested"=>"Requested Amount (USD)", "Approved"=>"Approved Amount (USD)", "Remaining"=>"Remaining Amount (USD)"];
/*
//Additional BR For Approval Info
$section[8]["title"] = array(
	"BudgetType"=>"Master / Additional BR",
 	"Brid"=>"BR No.",
	"ApprovedCapex"=>"Capex Approved Amount (USD)",
	"ApprovedOpex"=>"Opex Approved Amount (USD)"
);

$masterbr_amt=getBRAmount($masterbrval);
 
 
 
if($InvHeaderData['MasterBR']){
	 
	$MasterBRRef=$InvHeaderData['MasterBR'];
	$sql="SELECT * FROM tblBRBudget WHERE ID='{$InvHeaderData['MasterBR']}' or MasterBR='{$InvHeaderData['MasterBR']}' AND BRStatus = 'Open'"; 
	}
	else{
			 	$MasterBRRef=$InvHeaderData['ID'];
			$sql="SELECT * FROM tblBRBudget WHERE ID='{$InvHeaderData['ID']}' or MasterBR='{$InvHeaderData['ID']}' AND BRStatus = 'Open'"; 

		}

$sql=mysqli_query($insightDBLink, $sql);
 $countBR=mysqli_num_rows($sql);
while($getAddBr=mysqli_fetch_array($sql)){
	
	if($MasterBRRef==$getAddBr['ID']){
		$BRTypeLabel="Master BR";
		$MasterBRAllocatedOpEx=$getAddBr['AllocatedOpEx'];
		$MasterBRAllocatedCapEx=$getAddBr['AllocatedCapEx'];
		}
		else{
		$BRTypeLabel="Additional BR";
			
			}
	
	$section[8][] = array(
		"BudgetType"=>$BRTypeLabel,
		"Brid"=>"<a href='approval.php?id=".$getAddBr['ID']."' target='_blank' >".BRID($getAddBr['ID'])." </a>",
		"ApprovedCapex"=>"<div align='right'>".number_format($getAddBr['ApprovedCapEx'],2)."</div>",
		"ApprovedOpex"=>"<div align='right'>".number_format($getAddBr['ApprovedOpEx'],2)."</div>"
	 ); 
$BRGrandTotalCapex=$BRGrandTotalCapex+$getAddBr['ApprovedCapEx'];
$BRGrandTotalOpex=$BRGrandTotalOpex+$getAddBr['ApprovedOpEx'];
}

 $section[8][] = array(
	"BudgetType"=>"Total Approved Amount",
 	"Brid"=>'',
	"ApprovedCapex"=>"<div align='right'>".number_format($BRGrandTotalCapex,2)."</div>",
	"ApprovedOpex"=>"<div align='right'>".number_format($BRGrandTotalOpex,2)."</div>"
 ); 
 
 $section[8][] = array(
	"BudgetType"=>"Total Remaining Amount",
 	"Brid"=>'',
	"ApprovedCapex"=>"<div align='right'>".number_format($BRGrandTotalCapex-$MasterBRAllocatedCapEx,2)."</div>",
	"ApprovedOpex"=>"<div align='right'>".number_format($BRGrandTotalOpex-$MasterBRAllocatedOpEx,2)."</div>"
 ); 
 
$section[9][] = array(
	"BudgetType"=>"Capex",
	"Requested"=>"<div id='RequestedCapEx' align='right'>".number_format($InvHeaderData[RequestedCapEx],2)."</div>",
	"Approved"=>($EditApprovedAmt && $InvHeaderData[RequestedCapEx]!=0)?"<input type=\"text\" name=\"ApprovedCapEx\" id=\"ApprovedCapEx\" value=\"".number_format($InvHeaderData[ApprovedCapEx],2)."\">":"<div id='ApprovedCapEx' align='right'>".$ApprovedCapEx."</div>",
	"Remaining"=>"<div align='right'>".$RemainingCapEx."</div>"
	//"ApprovedOpEx"=>($InvHeaderData[CurrentStep]==9 && $InvHeaderData[RequestedOpEx]!=0)?"<input type=\"text\" name=\"ApprovedOpEx\" id=\"ApprovedOpEx\" value=\"".number_format($InvHeaderData[ApprovedOpEx],2)."\">":"<div id='ApprovedOpEx'>".number_format($InvHeaderData[ApprovedOpEx],2)."</div>"
);
*/
if($InvHeaderData[CurrentStep] >= 11){
	$ApprovedCapEx = number_format($InvHeaderData[ApprovedCapEx],2);
	$RemainingCapEx = number_format($InvHeaderData[ApprovedCapEx]-$InvHeaderData[AllocatedCapEx],2);
	$ApprovedOpEx = number_format($InvHeaderData[ApprovedOpEx],2);
	$RemainingOpEx = number_format($InvHeaderData[ApprovedOpEx]-$InvHeaderData[AllocatedOpEx],2);
} else {
	$ApprovedCapEx = "N/A";
	$RemainingCapEx = "N/A";
	$ApprovedOpEx = "N/A";
	$RemainingOpEx = "N/A";
}

$section[3][] = ["BudgetType"=>"Capex", "Requested"=>"<div id='RequestedCapEx' align='right'>".number_format($InvHeaderData[RequestedCapEx],2)."</div>", "Approved"=>($EditApprovedAmt && $InvHeaderData[RequestedCapEx]!=0)?"<input type=\"text\" name=\"ApprovedCapEx\" id=\"ApprovedCapEx\" value=\"".number_format($InvHeaderData[ApprovedCapEx],2)."\">":"<div id='ApprovedCapEx' align='right'>".$ApprovedCapEx."</div>", "Remaining"=>"<div align='right'>".$RemainingCapEx."</div>"];

$section[3][] = ["BudgetType"=>"Opex", "Requested"=>"<div id='RequestedOpEx' align='right'>".number_format($InvHeaderData[RequestedOpEx],2)."</div>", "Approved"=>($EditApprovedAmt && $InvHeaderData[RequestedOpEx]!=0)?"<input type=\"text\" name=\"ApprovedOpEx\" id=\"ApprovedOpEx\" value=\"".number_format($InvHeaderData[ApprovedOpEx],2)."\">":"<div id='ApprovedOpEx' align='right'>".$ApprovedOpEx."</div>", "Remaining"=>"<div align='right'>".$RemainingOpEx."</div>"];
//JM TASK Additional BR

	 
//JM TASK Additional BR ==========================END
$section[4]["title"] = ["approver"=>"Approver", "title"=>"Title", "cap_amount"=>"Capex (USD)", "cap_amount_per"=>"Capex (USD) %", "op_amount"=>"Opex (USD)", "op_amount_per"=>"Opex (USD) %", "status"=>"Status"];

foreach ($BUHead_List as $row) {

	$ttl_cap_amount += $row["Amount1"];
	$ttl_op_amount += $row["Amount2"];
	if($row["StatusName"]=="NO"){
		$row["StatusName"] = "Awaiting for Approval";
	} else if($row["StatusName"]=="YES"){
		$row["StatusName"] = "Approved";
	}
	$section[4][] = ["approver"=>getUserFullName($row["ActionUser"]), "title"=>getUserTitle($row["ActionUser"]), "cap_amount"=>"<div align='right'>".number_format($row["Amount1"], 2)."</div>", "cap_amount_per"=>"<div align='right'>".formatNumber(($row["Amount1"]/$InvHeaderData[RequestedCapEx]*100),2,1)."%</div>", "op_amount"=>"<div align='right'>".number_format($row["Amount2"], 2)."</div>", "op_amount_per"=>"<div align='right'>".formatNumber(($row["Amount2"]/$InvHeaderData[RequestedOpEx]*100),2,1)."%</div>", "status"=>$row["StatusName"]];
}

$section[5]["title"] = ["budget_type"=>"Budget Item Type", "approver"=>"Verifier/Approver", "role"=>"Role", "status"=>"Status"];

$res = getBRStatus();
if($InvHeaderData[SalesProjectID]!='' || $DirectCost){
	$back_to_back_order = true;
	$ORole = 'COSRole';
} else {
	$back_to_back_order = false;
	$ORole = 'OpRole';
}
$BRRole = getBRRole($back_to_back_order);

foreach ($ApprovalList as $row) {
	$Seq = $row["Sequence"];
	$GroupsDescription = '';
	foreach($BRRole as $k=>$row2) {		
		/*foreach ($res as $key => $val) {
		   if($res[$key]['Sequence']==$Seq){
				break;
		   }
		}*/
		if($row["Amount1"]!=""){
				$Role = 'CapRole';
			} else {
				$Role = $ORole;
		}
		if($BRRole[$k]['Sequence']==$Seq && $BRRole[$k]['CodeCat']==$Role){
			$CodeValue = $BRRole[$k]['CodeValue'];
			//$GroupsDescription = $CodeValue;
			if($row["Amount1"]!=""){
					$Value = $row["Amount1"];
				} else {
					$Value = $row["Amount2"];
			}
			if($Value>=$BRRole[$k]['LowerLimit'] && $Value<=$BRRole[$k]['UpperLimit']){
				/*if($row["Amount1"]!="" && $row["Amount2"]!="" && $Seq>4){
					$GroupsDescription = $GroupsDescription.','.getGroupsDescription($CodeValue);
				} else {*/
					//$GroupsDescription = $BRRole[$k]['CodeCat'].' - '.$BRRole[$k]['LowerLimit'].' '.$BRRole[$k]['UpperLimit'].' '.$CodeValue.' '.getGroupsDescription($CodeValue);
					$GroupsDescription = getGroupsDescription($CodeValue);
				//}
			}
		}
	}

	if($row["StatusName"]=="NO"){
		if($row["Role"]=="Verifier"){
			$row["StatusName"] = "Awaiting for Verification";
		} else if($row["Role"]=="Processor"){
			$row["StatusName"] = "Awaiting for Processing";
		} else {
			$row["StatusName"] = "Awaiting for Approval";
		}
	} else if($row["StatusName"]=="YES"){
		if($row["Role"]=="Verifier"){
			$row["StatusName"] = "Verified";
		} else if($row["Role"]=="Processor"){
			$row["StatusName"] = "Processed";
		} else {
			$row["StatusName"] = "Approved";
		}		
	}		
	
	
	if($row["Amount1"]!="" && $row["Amount2"]!=""){
		$row["budget_type"] = "Capex & Opex";
	} else if($row["Amount1"]!=""){
		$row["budget_type"] = "Capex";
	} else if($row["Amount2"]!="") {
		$row["budget_type"] = "Opex";
	}
	
	$section[5][] = [
        "budget_type"=>$row["budget_type"],
        "approver"=>getUserFullName($row["ActionUser"]),
        "role"=>$GroupsDescription,
        /*"cap_amount"=>number_format($row["Amount1"], 2),
        		"cap_amount_per"=>($row["Amount1"]/$InvHeaderData[RequestedCapEx]*100)."%",
        		"op_amount"=>number_format($row["Amount2"], 2),
        		"op_amount_per"=>($row["Amount2"]/$InvHeaderData[RequestedOpEx]*100)."%",*/
        "status"=>$row["StatusName"],
    ];
}

$section[6]["title"] = ["log_num"=>"No.", "doc_type"=>"Event/Doc Type", "doc_details"=>"Event/Doc Details", "file_attach"=>"File Attachment", "create_date"=>"Create Date", "create_user"=>"Create User"];
foreach ($PaymentEventLogData as $key=>$row) {
	$section[6][] = ["log_num"=>$key+1, "doc_type"=>findBRDocTypeDesc($row["DocType"]), "doc_details"=>$row["Description"], "file_attach"=>$row["Attachment"], "create_date"=>formatDate($row["LogDate"], true), "create_user"=>getUserFullName($row["LogUser"])];
}

$sectionNumStart = 7;
$sectionNum = $sectionNumStart-1;
$MACost = getMACost($InvHeaderData['ID']);
$MACostSize = 0;
$CNNum =[];
//var_dump($MACost);
if($MACost){
	foreach($MACost as $k=>$row) {
		$BudgetMargin = getBudgetMargin($InvHeaderData['ID'],$row["ID"]);
		$MACostSize += 1;
		$sectionNum += 1;
		
		array_push($CNNum, $row["ID"]."<br/>Service Contract Period : ".$row["contract_period"]." month(s)");
		
		$section[$sectionNum]["title"] = ["Type"=>"<div align='left'>Type</div>", "OneTime"=>"<div align='left'>One Time (USD)</div>", "Monthly"=>"<div align='left'>Monthly (USD)</div>", "Total"=>"<div align='left'>Total (USD)</div>"];
	
		$section[$sectionNum][] = ["Type"=>"<div align='left'>Selling Price in MA</div>", "OneTime"=>formatNumber($row["OneTimeRev"],2,1), "Monthly"=>formatNumber($row["MonthlyRev"],2,1), "Total"=>formatNumber($row["TotalContractRevenue"],2,1)];
		
		$section[$sectionNum][] = ["Type"=>"<div align='left'>Cost in MA</div>", "OneTime"=>formatNumber($row["OneTimeTC"],2,1), "Monthly"=>formatNumber($row["MonthlyTC"],2,1), "Total"=>formatNumber($row["TotalContractCost"],2,1)];		
		
		$section[$sectionNum][] = ["Type"=>"<div align='left'>Contribution Margin in MA</div>", "OneTime"=>formatNumber($row["OneTimeGM"],2,1), "Monthly"=>formatNumber($row["MonthlyGM"],2,1), "Total"=>formatNumber($row["TotalContractMargin"],2,1)];
		
		$section[$sectionNum][] = ["Type"=>"<div align='left'>Contribution Margin (%) in MA</div>", "OneTime"=>formatNumber($row["OneTimeGMP"],2,1)."%", "Monthly"=>formatNumber($row["MonthlyGMP"],2,1)."%", "Total"=>formatNumber($row["TotalContributionMarginPercent"],2,1)."%"];
		
		$ActualOneTime = $BudgetMargin[0];
		$ActualMonthly = $BudgetMargin[1];
		$ActualTotal = $ActualOneTime+$ActualMonthly*$row["contract_period"];		
		
		$section[$sectionNum][] = ["Type"=>"<div align='left'><b>Cost in Budget</b></div>", "OneTime"=>"<b>".formatNumber($ActualOneTime,2,1)."</b>", "Monthly"=>"<b>".formatNumber($ActualMonthly,2,1)."</b>", "Total"=>"<b>".formatNumber($ActualTotal,2,1)."</b>"];
		
		$ActualOneTimeCM = $row["OneTimeRev"]-$ActualOneTime;
		$ActualMonthlyCM = $row["MonthlyRev"]-$ActualMonthly;
		$ActualTotalCM =$row["TotalContractRevenue"]-$ActualTotal;
		
		$section[$sectionNum][] = ["Type"=>"<div align='left'><b>Contribution Margin in Budget</b></div>", "OneTime"=>"<b>".formatNumber($ActualOneTimeCM,2,1)."</b>", "Monthly"=>"<b>".formatNumber($ActualMonthlyCM,2,1)."</b>", "Total"=>"<b>".formatNumber($ActualTotalCM,2,1)."</b>"];
		
		$onetime_gmp = CalMargin($row['OneTimeRev'],$ActualOneTimeCM);
		$monthly_gmp = CalMargin($row['MonthlyRev'],$ActualMonthlyCM);
		$total_gmp = CalTotalContributionMargin($row['TotalContractRevenue'],$ActualTotalCM);
		
		$section[$sectionNum][] = ["Type"=>"<div align='left'><b>Contribution Margin (%) in Budget</b></div>", "OneTime"=>"<b>".formatNumber($onetime_gmp,2,1)."%</b>", "Monthly"=>"<b>".formatNumber($monthly_gmp,2,1)."%</b>", "Total"=>"<b>".formatNumber($total_gmp,2,1)."%</b>"];
	}
}
function drawHorizontalTable($rows, $title, $id=null) {
//echo $title;


if ($title == 'Event Log') {
$align = 'align=left';
} else {
$align = 'align=right';
}

	$cols = count($rows["title"]);
	$str_echo = '';
	if ($title) {
		$str_echo .= "<span>$title</span>"; 
	}
	if($id){
		$str_echo .= "<table cellspacing='1' cellpadding='0' border='0' style='background-color:#afafaf;' id='$id'>";
	} else {
		$str_echo .= "<table cellspacing='1' cellpadding='0' border='0' style='background-color:#afafaf;'>";
	}
	 
	/*if ($title) {
		$str_echo .= "<tr><th colspan=\"$cols\" style=\"background-color:#ffffff;color:#000000\">$title</th></tr>"; 
	}*/
	foreach ($rows as $key=>$row) {
		$str_echo .= "<tr id=\"row_$key\">";
		
		foreach ($row as $col_id=>$value) {
		//echo $col_id=>$value;
			if (is_array($value)) {
			
				$str_echo .= "<td id=\"col_$col_id\" colspan=\"".$value["colspan"]."\">"; 
				$str_echo .= ($value["value"]=="")?"&nbsp;":$value["value"];
				$str_echo .= "</td>";			
			} else {
				$str_echo .= "<td ".$align.">"; 
				$str_echo .= ($value=="")?"&nbsp;":$value;
				$str_echo .= "</td>";
			}
		}
		$str_echo .= "</tr>";
	}
	$str_echo .= "</table>";
	echo $str_echo;
}
   
function drawVerticalTable($rows) {
	$str_echo = "<table style=\"width:100%;border-collapse:collapse;border:1px solid black\">";
	foreach ($rows["title"] as $key=>$title) {
		$str_echo .= "<tr>";
		$str_echo .= "<td style=\"width:25%;border:1px solid black\" >";
		$str_echo .= $title ?: "&nbsp;";
		$str_echo .= "</td>";			
		foreach ($rows as $row_num=>$value) {
			if (is_numeric($row_num)) {
				$str_echo .= "<td style=\"font-weight:bold;border:1px solid black\" >";
				$str_echo .= ($value[$key]=="")?"&nbsp;":$value[$key];
				$str_echo .= "</td>";
			}
		}
		$str_echo .= "</tr>";
	}
	$str_echo .= "</table>";
	echo $str_echo;
}

function findBRDocTypeDesc($doc_type) {
	global $insightDBLink;
	if ($doc_type=="STATUS") return "Status Change";
	
	$sql = "SELECT Description FROM tblGenericCode 
		WHERE CodeType = 'DocType' AND CodeValue = '$doc_type' AND CodeCat = 'EventLogBR'"; 
	return dbSelectColumn($insightDBLink, $sql, 0);	
}

function formatDate($date, $bln_time) {
	if (!$date || $date=="0000-00-00") {
		return false;
	} else {
		if ($bln_time) $time = "H:i:s";
		return date("d-M-Y $time", strtotime($date));
	}
}

function getApprovalList($id) {
	$sql = "SELECT ID, ActionUser, Role, Amount1, Amount2, StatusName, Sequence from tblPCActFlow where ReferenceID = '$id' and LevelInd = 'BR' AND Sequence > 2 AND Sequence < 11 ORDER BY Sequence, ID";
	$query = getQueryResult($sql);
	if ($query) {
		return $query;
	} else {
		return false;
	}		
}

function countLinkedPR($id) {
	$sql = "SELECT count(*) from tblPCReqHeader where BudgetID = '$id'";
	$count = getOneCell($sql);
	if ($count>0) {
		return true;
	} else {
		return false;
	}		
}

function isBRContinue($id) {
	global $insightDBLink;
	$sql = "SELECT ContinueFlag FROM tblBRBudget WHERE ID = '$id'";
	return dbSelectColumn($insightDBLink, $sql, 0);
}
?>
<html>
  <head>
    <title>Budget Approval - <?php echo BRID($id); ?></title>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <link type="text/css" href="../../mainStyle.css" rel="stylesheet" />
    <!--<style type="text/css">
		td { font-family:Arial,Geneva,sans-serif; padding:5px; }
		th { font-family:Arial,Geneva,sans-serif; font-size:medium; }
		div { font-family:Arial,Geneva,sans-serif; }
		body { font-family:Arial,Geneva,sans-serif; }
		table { width:100%; border-collapse:collapse; margin-top:25px; }
		
		p#sec_0 #row_0 { font-weight:bold; }
		p#sec_0 #row_0 td { border-top:solid 1px black; }
		
		p#sec_2 table td { border:solid 1px black; }
		p#sec_2 #row_0 { font-weight:bold; }
		
		p#sec_3 table th, p#sec_3 table td { border:solid 1px black; }
		p#sec_3 #row_title { font-weight:bold; }
		p#sec_3 #row_subtotal_loc { font-weight:bold; }
		p#sec_3 #row_subtotal_usd { font-weight:bold; }
		p#sec_3 #col_pre_cols { border:none; }
		p#sec_3 #col_post_cols { border:none; }
		
		p#sec_8 table th, p#sec_3 table td { border:solid 1px black; }
		p#sec_8 #row_title { font-weight:bold; }
		p#sec_8 #row_subtotal_loc { font-weight:bold; }
		p#sec_8 #row_subtotal_usd { font-weight:bold; }
		p#sec_8 #col_pre_cols { border:none; }
		p#sec_8 #col_post_cols { border:none; }		
        
        p#sec_9 table th, p#sec_3 table td { border:solid 1px black; }
		p#sec_9 #row_title { font-weight:bold; }
		p#sec_9 #row_subtotal_loc { font-weight:bold; }
		p#sec_9 #row_subtotal_usd { font-weight:bold; }
		p#sec_9 #col_pre_cols { border:none; }
		p#sec_9 #col_post_cols { border:none; }		
		
		p#sec_5 table th, p#sec_5 table td { border:solid 1px black; }
		p#sec_5 #row_pre_title { font-weight:bold; text-align:center; }		
		p#sec_5 #row_title { font-weight:bold; }
		p#sec_5 #row_subtotal { font-weight:bold; }
		p#sec_5 #col_pre_cols { border:none; }
		
		p#sec_6 table th, p#sec_6 table td { border:solid 1px black; }
		p#sec_6 #row_title { font-weight:bold; text-align:center; }
		p#sec_6 { margin-bottom:25px; }
		.style1 {color: #FF0000}
		
    </style>-->	
	<link type="text/css" href="../../mainStyle.css" rel="stylesheet" />
	<link type="text/css" href="css_budget.css" rel="stylesheet" />
	<link type="text/css" href="./swfupload/default.css" rel="stylesheet" />
	<link rel="stylesheet" href="jquery.treeview.css" />
	<link rel="stylesheet" href="screen.css" />
	<link rel="stylesheet" href="approval.css" />
    <script type="text/javascript" src="jquery.js" ></script>
	<script type="text/javascript" src="jquery.cookie.js" ></script>
	<script type="text/javascript" src="jquery.treeview.js"></script>
    <script type="text/javascript" src="script_common.js" ></script>
    <script type="text/javascript" src="script_budget.js" ></script>
	<script type="text/javascript" src="jqueryui/jquery-ui.js" ></script>
    <link  type="text/css" href="jqueryui/jquery-ui.css" rel="stylesheet" />
    <?php 
 
 $notContinue=1;
 
			$getCIsql="SELECT ContinueFlag FROM tblBRBudget WHERE ID ={$id}";
			$getCIsql=mysqli_query($insightDBLink, $getCIsql);
			$cirow=mysqli_fetch_row($getCIsql);
				 
		 	if ($ApprovalList[0]['ActionUser']==$PHP_AUTH_USER&&$cirow[0]==1) {
				$notContinue=0;
			}
			for ($i = 0; $i < sizeof($BUHead_List); $i++) {
				// echo $BUHead_List[$i]['ActionUser'];
				if ($BUHead_List[$i]['ActionUser']==$PHP_AUTH_USER&&$cirow[0]==1) {
					$notContinue=0;
				}
			}
			// if($cirow[0]==1){
			// 	$notContinue=0;
			// }				
				
 
?>
	<script>
	 
		function ChangeStatus(action){
			global $insightDBLink;
			var	content  = '<div id="DialogWarning"></div>';
				content += '<textarea id="DialogReason" cols="55" rows="6" ></textarea>';
			var options = {
					title: action+' reason', 
					//height: 210,
					width: 400,
					modal:true,
					buttons: {
						'Cancel': function() {
							$(this).dialog("destroy");
						},
						'OK': function() {							
							if($('#DialogReason').val()==""){
								$("#DialogWarning").html("Please input "+action+" reason");
							} else {
								ApprovalCheckForm(action);
								$(this).dialog('close');
							}
			
						}
					}		
				}
			
			openModelDialog(content, options);
		}
		/*
		function checkDenyReason(obj){
			var notfind = true;
			$("#EventLogEditTable tr").each(function(i){
				var TypeObj = $(this).find("td select[name='LogDocType[]']");
				var TypeValue = TypeObj.val();		
				if (TypeValue=="DENY"){
					var TextArea = TypeObj.parent().parent().next().find("td textarea[name='LogDescription[]']").val();
					var UploadFile = TypeObj.parent().parent().next().next().find("ul li[class='swf_list_left']").html();
					if (TextArea!='' || UploadFile!=null){
						notfind = false;
						return false;
					}
				}
			});
			
			if(!notfind){
				ApprovalCheckForm('Deny');
				return true;
			} else {
				showModeDialog("Please input Deny reason.");
				return false;
			}
		}
		*/
		<?php if($InvHeaderData['CurrentStep']==9): ?>
		function checkApprovedAmount(){
			var RequestedCapEx = formatNumber($('#RequestedCapEx').html());
			var RequestedOpEx = formatNumber($('#RequestedOpEx').html());
			var ApprovedCapEx = formatNumber($('#ApprovedCapEx').val());
			var ApprovedOpEx = formatNumber($('#ApprovedOpEx').val());
			
			if(ApprovedCapEx>RequestedCapEx || ApprovedOpEx>RequestedOpEx || ApprovedCapEx<0 || ApprovedOpEx<0){
				showModeDialog("Approved amount must be less than or equal to requested amount.");
			} else if ( (RequestedCapEx!=0 && ApprovedCapEx=="") || (RequestedOpEx!=0 && ApprovedOpEx=="")){
				showModeDialog("Please input approved amount.");
			} else {
				noValue = true;
				$("INPUT[id^='WONum_']").each(function(i){		
					var Value = $(this).val();
					/*if (Value==""){				
						line = $(this).parent().prev().prev().html();
						noValue = false;
						showModeDialog("Please input WO# in line "+line+".");
						return false;
					}*/
					
					var reg=/^\d{8}$/;
					if (!(reg.test(Value))) {
						line = $(this).parent().prev().prev().html();
						noValue = false;
						showModeDialog("Please input a valid 8 digits WO# in line "+line+".");
						return false;
					}

				});
				if(noValue){
					ApprovalCheckForm('Approve');
				}
			}
		}
		<?php endif;
		
		 ?>	
		 
		 
		function ApprovalCheckForm(command){
			if (command=="Save" || command=="Deny" || command=="On-hold" || command=="Cancel"){
				var agree = true;
				showModeMessage("Please wait...");
			} else {
				<?php 				
 			if ($notContinue){ 
				echo $msg='var msg = "Are you sure to proceed?"';
		  	} else {
				echo $msg='var msg = "ATTENTION: This is a ‘Continue’ BR. Please indicate your approval for both the BR and the PR. Upon approval by all approver(s) in this BR, the system will auto-generate, auto-submit, auto-verify, auto-approve, auto-process and auto-receive PR, PO, Cost Item and receiving records. \r\n Are you sure to approve this BR?"'; 
			}
				?>
				//var msg = "xxx";
				var agree = confirm(msg);
				if (agree){
					showModeMessage("Please wait...");
				} else {
					return false;
				}
			}
			
			if (agree){	
				/*if(checkAllEventLogEmail() == false){
					window.setTimeout("hiddenModeDialog()", 1000);
					return false;
				} else {*/
				
					switch(command) {
						case 'Save':
						  var goforward = true;
						  break;
						case 'Approve':
							var to_approve_mode = document.getElementById('to_approve_mode');
							to_approve_mode.value = "true";
							var goforward = true;							
							break;
						case 'Deny':
							appendDialogReason("DENY");
							
							var to_deny_mode = document.getElementById('to_deny_mode');
							to_deny_mode.value = "true";
							var goforward = true;							
							break;
						case 'On-hold':
							appendDialogReason("HOLD");
							
							var to_onhold_mode = document.getElementById('to_onhold_mode');
							to_onhold_mode.value = "true";
							var goforward = true;							
							break;
						case 'Cancel':	
							appendDialogReason("CANCEL");
					
							var to_cancel_mode = document.getElementById('to_cancel_mode');
							to_cancel_mode.value = "true";
							var goforward = true;
							break;
						default:
						  alert("ERROR");
						}
						
						if (goforward == true){
							var formObj = $("#Input");
							formObj.submit();
						}
				//}						
			}
			if(<?php echo $notContinue; ?>){
				window.setTimeout("hiddenModeDialog()", 1000);
			}
		}
		
		$(document).ready(function(){
			$("#tree_selected").treeview()
		});
    </script>

  </head>
  <body>

	<div id="header">
		<h1 id="banner">Budget Approval - <?php echo $InvHeaderData["FID"]; ?></h1>
	</div>
	<form id="Input" name="Input" action="" method="post" enctype="multipart/form-data" >
	<ul class="treeview" id="tree_selected">	
		<li class="collapsable" id="sec_1"><?php echo drawHorizontalTable($section[1],"Budget Approval - ".$InvHeaderData["FID"],'table1'); ?></li>
		<ul>
	
		<li id="sec_2"><?php echo drawHorizontalTable($section[2], "Budget Item(s)"); ?></li>
		<?php if($MACostSize) : 
			for($i=0; $i<$MACostSize; $i++ ) : 
		?><li id="sec_7"><?php echo drawHorizontalTable($section[$sectionNumStart+$i], "Revenue and Margin Information for ".$CNNum[$i]); ?></li><?php endfor; endif;?>
	
    	<li id="sec_3"><?php echo drawHorizontalTable($section[3], 'Budget Amount Summary'); ?><?php if(countLinkedPR($InvHeaderData["ID"])){?><input type="button" onClick="openWindow('/Fin/online_cost/PR/budget_detail_usage.php?ID=<?php echo $InvHeaderData["ID"]; ?>', 1030, 400, 1, 1)" value="Show Budget Usage Detail" /><?php } ?>
   </li> 
   <?php //if($countBR>1||$masterbrval){     //JM Task Additional BR
	//$masterbrid=BRID($masterbrval)?>
   <!--<li id="sec_8"> <?php //=drawHorizontalTable($section[8], 'Total Budget Amount Summary')?></li> -->
    <?php //} ?>
   
   
   
	   	<?php if($BUHead_List): ?><li id="sec_4"><?php echo drawHorizontalTable($section[4], findBRADescription()); ?></li><?php endif; ?>
		<?php if($ApprovalList): ?><li id="sec_5"><?php echo drawHorizontalTable($section[5], "Verification/Approver List"); ?></li>
		
		<?php endif; ?>

		<?php 
 
		if($_REQUEST['ViewOnly']!='1'): ?>
		<li>
			<input type="button" onClick="return ApprovalCheckForm('Save');" value="Save" class="button-standard"/>
			<?php if($InvHeaderData['CurrentStep']>1 && checkEditable($InvHeaderData['ID'],$InvHeaderData['CurrentStep'])): ?>
			<input type='button' onClick="<?=($InvHeaderData['CurrentStep']==9)?"return checkApprovedAmount()":"return ApprovalCheckForm('Approve')" ?>" value='<?php echo getActionName($InvHeaderData['CurrentStep']); ?>' class="button-standard"/>
			<input type='button' onClick="ChangeStatus('Deny')" value='Deny' class="button-standard"/>
			<?php if($InvHeaderData[BRStatus]!='On-hold'): ?>
			<input type='button' onClick="ChangeStatus('On-hold')" value='On-hold' class="button-standard"/>
			<?php endif; endif; ?>						
			
			<?php 	if (checkEditable($InvHeaderData['ID'],1)):
				if ($InvHeaderData[CurrentStep] >= 1 && $InvHeaderData[CurrentStep] < 9): ?>
				<input id='Cancelbtn' type='button' onClick="ChangeStatus('Cancel')" value='Cancel' class="button-standard"/>
			<?php elseif($InvHeaderData[BRStatus] == "Open" ):
				$TotalBudget = floatval($InvHeaderData[AllocatedCapEx])+floatval($InvHeaderData[AllocatedOpEx]);
				if($TotalBudget==0): ?>
				<input id='Cancelbtn' type='button' onClick="ChangeStatus('Cancel')" value='Cancel' class="button-standard"/>	
			<?php elseif($TotalBudget = floatval($InvHeaderData[SpentCapEx])+floatval($InvHeaderData[SpentOpEx])): ?>
				<input id='CloseProjectbtn' type='button' onClick="return ApprovalCheckForm('Approve')" value='Close Project' class="button-standard"/>
			<?php endif; endif; endif;?>
			
			<input type="button" onClick="confirmExit();" value="Exit" class="button-standard"/>
            <?php 
			
			#JM task 31982
				
			//if ($InvHeaderData[CurrentStep] == 11&&!$InvHeaderData['MasterBR']){ ?>
            <!--<input type="button" onClick="javascript:window.open('budget_approval.php?currentStep=1&additionalbr=yes&originalbr=<?php echo $InvHeaderData["ID"]; ?>')" value="Additional BR" class="button-standard"/>-->
            <?php // } 
		 ?>
		</li>
		<?php endif; ?>

		<!-- Show the message after the Evergreen BR approved (BR status became ‘Open’) where at the top of Budget Approval Event Log -->
		<?php if($InvHeaderData[BRStatus]=='Open' && isBRContinue($id) == 1) { ?><div style='color:red; padding: 15px;'><table cellpadding='10' style='border: 2px solid red;'><tr><td style='color:red;'>ATTENTION: <br>- This is a 'Continue' BR, all approver(s) approved the BR and PR. <br>- The system generated PR, PO, Cost Item and receiving records automatically.<br></td></tr></table></div><?php } ?>
		
	<li class="collapsable lastCollapsable">
		<ul>
		<input type="hidden" name="issave" value="true" />
		<input type="hidden" name="id" id="InvDataID" value="<?php echo $InvHeaderData['ID']; ?>" />
		<input type="hidden" name="to_approve_mode" id="to_approve_mode" value="false" />
		<input type="hidden" name="to_deny_mode" id="to_deny_mode" value="false" />
		<input type="hidden" name="to_onhold_mode" id="to_onhold_mode" value="false" />
		<input type="hidden" name="to_cancel_mode" id="to_cancel_mode" value="false" />
		<input type="hidden" name="CurrentStep" id="CurrentStep" value="<?php echo $InvHeaderData['CurrentStep']; ?>" />
	
	                    <table id="EventLogListTable" border=0 cellpadding="0" cellspacing="1" class="table">
                      <tr>
                        <td class='THeader' height="23" colspan="8" align=left  valing="top"><strong>&nbsp;Budget Approval Event Log</strong></td>
                      </tr>
                      <tr bgcolor="#999999" >
                        <td height="23" bgcolor="#999999"><div align="center"><strong><font size=1 color='#FFFFFF'>ID</font></strong></div></td>
                        <td bgcolor="#999999"><div align="center"><strong><font size=1 color='#FFFFFF'>Event/Doc Type</font></strong></div></td>
                        <td bgcolor="#999999"><div align="center"><strong><font size=1 color='#FFFFFF'>Event/Doc Details</font></strong></div></td>
                        <td bgcolor="#999999"><div align="center"><strong><font color="#FFFFFF" size="1">File Attachment</font></strong></div></td>
                        <td bgcolor="#999999"><div align="center"><strong><font color="#FFFFFF" size="1">Create Date</font></strong></div></td>
                        <td bgcolor="#999999"><div align="center"><strong><font color="#FFFFFF" size="1">Create User</font></strong></div></td>
						<td bgcolor="#999999"><div align="center"><strong><font color="#FFFFFF" size="1">Email</font></strong></div></td>
						<td bgcolor="#999999"><div align="center"><strong><font color="#FFFFFF" size="1">Related Line Item</font></strong></div></td>
                      </tr>
                      <?php
                      if($PaymentEventLogData):
                        $logCount = 1;
                        foreach($PaymentEventLogData as $row):
						  if($row["DocType"] == "STATUS") {
							$row["DocType"] = "Status Change";
							} else {
// 								$query2 = mysqli_query($insightDBLink, "SELECT Description FROM tblGenericCode WHERE CodeType = 'DocType' AND CodeValue = '".$row["DocType"]."' AND CodeCat = 'EventLogBR' ORDER BY Sequence");
								$row["DocType"] = dbSelectColumn($insightDBLink, "SELECT Description FROM tblGenericCode WHERE CodeType = 'DocType' AND CodeValue = '".$row["DocType"]."' AND CodeCat = 'EventLogBR' ORDER BY Sequence", 0);			
							}
                          $className = ($logCount%2==1)?"TOdd":"TEven";
                          ?>
                      <tr  class="<?php echo $className; ?>" >
                        <td height="23" align=left  valing="top"><div align="center"><?php echo $logCount++; ?></div></td>
                        <td align=left  valing="top" tdclass="DocType"><div align="center"><?php echo ($row['DocTypeDesc']=='')?$row['DocType']:$row['DocTypeDesc']; ?></div></td>
                        <td align=left  valing="top"><div align="center"><?php echo $row['Description'] ?> </div></td>
                        <td align=left  valing="top"><div align="center"><?php if($row['Attachment']!='') { echo '<a href="'.$ATTACH_SAVE_URL.'/'.$row['Attachment'].'" target="_blank" >'.substr($row['Attachment'], (strrpos($row['Attachment'], '/')+1) ).'</a>'; } ?></div></td>
                        <td align=left  valing="top"><div align="center"><?php if($row['LogDate']!='') { echo date('d-M-Y H:i:s', strtotime($row['LogDate'])); } ?> </div></td>
                        <td align=left  valing="top"><div align="center"><?php echo getUserFullName($row['LogUser']); ?> </div></td>
						<td align=left  valing="top"><div align="center"><?php if(filter_var($row['Action'], FILTER_VALIDATE_EMAIL)) { echo $row['Action'];} ?> </div></td>
						<td align=left  valing="top"><div align="center"><?php if($row["DocType"] != "Status Change" && $row['LogUser']==$PHP_AUTH_USER && $_REQUEST['ViewOnly']!='1'): ?><input type='hidden' name='EventLogID[]' value="<?php echo $row['ID']; ?>"/><input type='text' name='EventLogRelatedItem[]' size="10" value="<?php echo $row['Filter1']; ?>"/><?php else: echo $row['Filter1']; endif; ?> </div></td>
                      </tr>
                        <?php endforeach; else:
                          echo '<tr><td align="center" colspan="8" style="height:30px;" class="TOdd">No log is avaliable </td></tr>';

						endif; ?>
                    </table>
					<?php if($_REQUEST['ViewOnly']!='1'): ?>
                    <br/>
                    <table id="EventLogEditTable" cellpadding="0" cellspacing="0" border="1" totalrow="1" width="600">
                      <tr>
                        <td class='THeader' colspan="2" >Budget Approval Event Log</td>
                      </tr>                  
					  <tr class="EventLogOddRow" rownum="1" >
                        <td>Event/Doc Type :</td>
                        <td >
                          <select name="LogDocType[]">
                            <option value="" ></option>
                            <?php echo getLogDocType('Y') ?>
                          </select>
						  Related Line Item: <input type='text' name='LogEmailRelatedItem[]' />
                        </td>
                      </tr>
                      <tr class="EventLogOddRow" rownum="1" >
                        <td>Event/Doc Details :</td>
                        <td><textarea name="LogDescription[]" cols="55" rows="3" ></textarea> </td>
                      </tr>
                      <tr class="EventLogOddRow" rownum="1" >
                        <td>Attachment :</td>
                        <td align="left" >
                          <input type='hidden' name='LogAttachment[]' value=''/><span id="swfUploadFileName_1" ></span>
                          <span id="swfUploadObject_1"><span id="swfUploadButton_1"></span></span>
                        </td>
                      </tr>
					  <tr class="EventLogOddRow" rownum="1" >
                        <td>Email Address : <br/></td>
                        <td><input type="hidden" name="LogEmailAddress[]"><input type='text' name='LogEmailUserName[]' size='55' disabled /> <a href="javascript:openWindow('/Lib/GetMgtToEventLog.php?FormName=Input&FieldID=LogEmailAddress[]&FieldName=LogEmailUserName[]&ID=0',300,200,1,1)"><img src="/Lib/search.gif" style="border-width:0px"></a> <a href="javascript:clearEventLogEmail(0)"><img src="/Lib/del.gif" style="border-width:0px"></a></td>
                      </tr>
                      
                      <tr id="EventLogButtonRow" >
                        <td colspan="6" style="background-color:#cccccc; padding-left:20px;">
                          <input type="button" value="Add more" onClick="EventLogMoreRow(this)" />
                          <input type="text" value="1" style="width:30px" />
                          Items
                        </td>
                      </tr>
                      
                    </table>					
			</ul>		
			</li>
		</ul>
	</ul>
	<div id='ModelModeMessageDiv' style='display:none' title='Message' ></div>
    <div id='ModelConfirmMessageDiv' style='display:none' title='Confirm Message' ></div>	
	</form>
	
	<script type="text/javascript" src="./swfupload/swfupload.js"></script>
    <script type="text/javascript" src="./swfupload/swfupload.queue.js"></script>
    <script type="text/javascript" src="./swfupload/fileprogress.js"></script>
    <script type="text/javascript" src="./swfupload/handlers.js"></script>
	
	<script type="text/javascript" >EventLogAddRow();</script>
	<?php endif; 
	
	?>
 
  </body>
</html>