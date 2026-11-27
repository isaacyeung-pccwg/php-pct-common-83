<?php
// File Holder Kavis Yiu Task 14165 2014-12-09 Done
// File Holder #Joseph Task 31956 
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");
// 导入电邮处理
// include_once('../../../phpInc/email.inc.php');    // use phpInc variable
include_once($insight_phpInc_path . "/email.inc.php");
include_once("reCalculateBR.php");

include_once($insight_helper_path . "/db_helpers.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/pct_common_helpers.php");

/*if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')){
    ob_start("ob_gzhandler");
}else{*/
  ob_start();
//}

// 附件保存路经
$ATTACH_SAVE_URL = 'file';

/** ------------------------------------- php 公共处理函数 ------------------ ---------------- */

/** 执行mysql查询, 并返回二维数组结果集
 * @param <string> $sql 输入的SQL语句
 * @param <string> $assoc 输出结果是否是关联数组，默认是输出关联数组
 * @@return <array> 返回二维结果集
 */
function getQueryResult($sql, $assoc='Y') {
  global $insightDBLink;
  $resultArr = [];
  $result = mysqli_query($insightDBLink, $sql);

  if($result) {
    if($assoc=='Y') {
      while ($row = mysqli_fetch_assoc($result)) {
        $resultArr[]=$row;
      }
    } elseif($assoc=='N') {
      while ($row = mysqli_fetch_row($result)) {
        $resultArr[]=$row;
      }
    }
  }
  return $resultArr;
}

/** 执行mysql查询, 并返回一行数组结果集
 * @param <string> $sql 输入的SQL语句
 * @param <string> $assoc 输出结果是否是关联数组，默认是输出关联数组
 * @@return <array> 返回一维数组结果集
 */
 function getOneRow($sql, $assoc='Y') {
  global $insightDBLink;
  $resultArr = [];
  $result = mysqli_query($insightDBLink, $sql);
  if($result) {
    if($assoc=='Y') {
      $resultArr = mysqli_fetch_assoc($result);
    } elseif($assoc=='N') {
      $resultArr = mysqli_fetch_row($result);
    }
  }
  return $resultArr;
}

/** 执行mysql查询, 返回第一个单元的值
 * @param <string> $sql 输入的SQL语句
 * @@return <string> 返回字符串结果集
 */
function getOneCell($sql) {
  $result = getOneRow($sql, $assoc='N');
  return $result[0];
}

/** 执行mssql查询, 并返回二维数组结果集
 * @param <string> $sql 输入的SQL语句
 * @param <string> $assoc 输出结果是否是关联数组
 */
function getMsQueryResult($sql, $assoc='') {
  global $EPI_CONN;
  $resultArr = [];
  $result = sqlsrv_query($EPI_CONN, $sql);
  if($result) {
    if($assoc=='Y') {
      while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $resultArr[]=$row;
      }
    } else {
      while ($row = sqlsrv_fetch_array($result)) {
        $resultArr[]=$row;
      }
    }
  }
  return $resultArr;
}

/** 在指定表格中插入一条记录
 * @param <string> $tableName 表格名称
 * @param <array> $data 一维关联数组集
 * @@return <bool> 返回是否成功新增
 */
function insertTable($tableName, $data) {
  global $insightDBLink;
  $fields='';
  foreach ($data as $k=>$val ) {
    $fields .= $k."='".$val."',";
  }
  if($fields!="") {
    $fields=substr($fields, 0, (strlen($fields)-1) );
    $insert_sql = "INSERT INTO ".$tableName." SET ".$fields;
    $result = mysqli_query($insightDBLink, $insert_sql);
    echo $insert_sql."<br>\n";
	//exit;
    return $result;
  }
  return false;
   //echo $update_sql;
}

/** 更新表格中指定行的数据，
 * @param <string> $tableName 表格名称
 * @param <array> $data 一维关联数组集
 * @param <string> $where 筛选记录条件
 * @return <boolean> 返回是否成功更新
 */
function updateTable($tableName, $data, $where) {
  global $insightDBLink;
  $fields='';
  foreach ($data as $k=>$val ) {
    $fields .= $k."='".$val."',";
  }
  if($fields!="") {
    $fields=substr($fields, 0, (strlen($fields)-1) );
    $update_sql = "UPDATE ".$tableName." SET ".$fields.' WHERE '.$where;
    $result = mysqli_query($insightDBLink, $update_sql);
	//echo $update_sql;
	
    return $result;
  }
  //echo $update_sql;
  return false;
  
}

/** 输出货币数据集，可以输出 select 标签中 option 代码
 * @param <string> $outHtml
 * @param <string> $defaultVal
 * @return <mixed> 可以返回二维数组或是 html 代码
 */
function getCurrency($outHtml='Y', $defaultVal='') {
// $sql = "select distinct Currency from tblCountry order by Currency";
  $sql = "select distinct a.CodeValue, b.Description from tblGenericCode a, tblGenericCode b ".
      " where a.CodeType = 'CompCurr' and b.CodeType = 'CurrCode' and b.CodeValue = a.CodeValue ".
      " and a.ActiveFlag=1 and b.ActiveFlag=1 order by  CodeValue";

  $result = getQueryResult($sql,'N');
  // 处理货币的显示
  foreach($result as $k=>$row) {
    $result[$k][1] = $row[0]." - ".$row[1];
  }
  // 输出结果处理
  if($outHtml=='Y') {
    return createOptionHtml($result, $defaultVal);
  }
  return $result;
}

/** 返回支付方式下拉框 */
function getPaymentType($outHtml='Y', $defaultVal='') {
  global $EPI_HOSTNAME,$EPI_USERNAME,$EPI_PASSWORD,$EPI_CONN,$InvHeaderData;
  // 读取器公司ID
  $signed = $_REQUEST['SignedCo'] ?? '';
  if($signed=='') {
    $signed = $InvHeaderData['SignedCo'];
  }
  // 查询 数据库名称
  $sql1 = "SELECT lpad(CoCode, 3, '0') as dbName FROM tblRegisteredCo LEFT OUTER JOIN tblCo_Entities ON SignedCO = tblRegisteredCo.ID WHERE ID ='$signed'";
  $dbName = getOneCell($sql1);
  $EPI_DB_NAME = "BTN".$dbName;

//  $EPI_CONN=mssql_connect($EPI_HOSTNAME, $EPI_USERNAME, $EPI_PASSWORD);
  $EPI_CONN = ["Database"=>$EPI_DB_NAME, "UID"=>$EPI_USERNAME, "PWD"=>$EPI_PASSWORD];
  $resultArr  = [];
  if(sqlsrv_connect($EPI_HOSTNAME, $EPI_CONN)) {
    $sql = "SELECT payment_code,description FROM appymeth ORDER BY payment_code ASC";
    $resultArr = getMsQueryResult($sql);
  }
  // 处理显示结构
  foreach($resultArr as $k=>$row) {
    $resultArr[$k][1] = $row[0]." - ".$row[1];
  }
  // 输出结果处理
  if($outHtml=='Y') {
    return createOptionHtml($resultArr, $defaultVal);
  }
  return $resultArr;
}

/**  读取其它货币与美元的汇率  */
function getCurrencyExchange($currencyFrom='', $currencyTo='USD') {
  if($currencyFrom==''){
	$sql ="select CurrencyFrom,CurrencyTo,Rate from tblExchange where CurrencyTo='".$currencyTo."' order by CurrencyFrom ";
  }else{
	$sql ="select CurrencyFrom,CurrencyTo,Rate from tblExchange where CurrencyFrom='".$currencyFrom."' AND CurrencyTo='".$currencyTo."'";
  }
  $resultArr = getQueryResult($sql);
  $RateArr = [];
  foreach($resultArr as $row) {
    $RateArr[$row['CurrencyFrom']]=$row['Rate'];
  }
  return $RateArr;  
}

/** 输出 Vendor 数据
 * @param <string> $outHtml 是否输出 html
 * @param <string> $defaultVal 输出 html 时，selected 的值
 * @return <mixed> 可以返回二维数组或是 html 代码
 */
function getVendorCode($outHtml='Y', $defaultVal='') {
  global $insightDBLink;
  global $defVendorType,$groupVendor;
  $vendorTypeWhere = '';
  $vendorTypeWhere = " and vendor_type in (select CodeValue from tblGenericCode where CodeType = 'VendorType' and ActiveFlag = 1) ";
  $sql = "select EpicorCode,CoName,vendor_type as VendorType from tblVendor where vendor_status = 'A' ".$vendorTypeWhere.
      " and EpicorCode like 'VN%' order by CoName ";
  $result = mysqli_query($insightDBLink, $sql);

  while($row=mysqli_fetch_row($result)) {
  // 去除带引号和爽引号
    $row[0] = str_replace( ["\"", "\'"], '', $row[0]);
    // 去除换行符和转义html特殊字符
    $search_arr = ["\r\n", "\n", '"', "'"];
    $replace_arr = ['', '', '\\"', "\\'"];
    $row[1] = str_replace($search_arr , $replace_arr, $row[1]);

    $row[1]=$row[1].' ('.$row[0].')';
    $groupVendor['ALL'][] = $row;
    $groupVendor[$row[2]][] = $row;
    // 读取当前的类别
    if( $action=='edit' && $defVendorType!='' && $row[2]==$defVendorType) {
      $row[2]='VendorType="'.$row[2].'"';
      $resultArr[]=$row;
    } elseif($action=='') {
      $row[2]='VendorType="'.$row[2].'"';
      $resultArr[]=$row;
    }
  }
  if($outHtml=='Y' && $resultArr ) {
    return createOptionHtml($resultArr, $defaultVal);
  }
  return $resultArr;
}


/** 构造 Select 中的 option 
 * @param array $optArr; $optArr[0] option的value, $optArr[1] option的描述,
 *        $optArr[2] 更具需要增加的属性值
 * @param string $sel
 * @return string
 */
function createOptionHtml($optArr, $sel='') {
  foreach($optArr as $row) {
    $selected = ($sel==$row[0])?'selected':'';
    if($row[1]=='') { $row[1] = $row[0]; }
    $html.='<option value="'.$row[0].'" '.$row[2].' '.$selected.' title="'.$row[1].'" >'.$row[1].'</option>';
  }
  return $html;
}

function createOptionHtml_1($optArr, $sel='') {
   $html = '<option value="" ></option>';
  foreach($optArr as $row) {
    $selected = ($sel==$row[0])?'selected':'';
    if($row[1]=='') { $row[1] = $row[0]; }
    $html.='<option value="'.$row[0].'" '.$row[2].' '.$selected.' title="'.$row[1].'" >'.$row[1].'</option>';
  }
  return $html;
}

/*
function createOptionHtml_SignedCo($optArr, $sel='') {
   $html = '<option value="" ></option>';
  foreach($optArr as $row) {
    $selected = ($sel==$row[0])?'selected':'';
    if($row[1]=='') { $row[1] = $row[0]; }
    $html.='<option value="'.$row[0].'" '.$row[2].' '.$selected.' title="'.$row[1].'" >'.$row[1].'</option>';
  }
  $html.= '<option value="0" title="Other">Other</option>';
  return $html;
}
*/

/**
 * 通过登录帐号或是用户注册信息返回用户姓名全称
 * @param <mixed> $userData 如果是字符串，则是用户登录帐号，如果是数组，则是用户基本信息
 * @return <string>  返回用户名全称
 */
function getUserFullName($userData) {
// 如果输入的是登录名称，则查询用户基本信息
  if(is_string($userData)) {
    $sql = "SELECT StaffNameFirst, StaffNameLast, StaffNickname FROM tblStaffInfo WHERE LoginName='".$userData."'";
    $userData = getOneRow($sql);
  }
  $fullName = '';
  if($userData['StaffNameLast']!='') {
    $fullName=$userData['StaffNameLast'].", ".$userData['StaffNameFirst'];
  }
  if($userData['StaffNickname']) {
    $fullName .= ", ".$userData['StaffNickname'];
  }
  return $fullName;
}

/** 读取通过日期空件输入的日期  */
function getInputDate($dataName, $setDefault='') {
  $year  = $_POST[$dataName.'_year'] ?? '';
  $month = $_POST[$dataName.'_month'] ?? '';
  $day   = $_POST[$dataName.'_day'] ?? '';
  // 如果需要设置默认值
  if($setDefault=='Y') {
    if($year=='') { $year=date('Y');}
      if($month=='') { $month=date('m');}
      if($day=='') { $day=date('d');}
  }
  if($year=='' || $month == '' || $day=='' ) {
    return 'NULL';
  }
  return "'".$year."-".$month."-".$day."'";
}
/*
function getInputDate_2($dataName, $setDefault='') {
  $year  = $_POST[$dataName.'_year'] ?? '';
  $month = $_POST[$dataName.'_month'] ?? '';
  $day   = $_POST[$dataName.'_day'] ?? '';
  // 如果需要设置默认值
  if($setDefault=='Y') {
    if($year=='') { $year=date('Y');};
    if($month=='') { $month=date('m');};
    if($day=='') { $day=date('d');};
  }
  if($year=='' || $month == '' || $day=='' ) {
    return "''";
  }
  return "'".$year."-".$month."-".$day."'";
}
*/
/** 读取公司列表
 * @param <string> $htmlOption 输入值是 Y 是则输入 Option html编码， 否则返回二维数组集
 * @param <type> $defaultVal 如果输出 Option html编码时， 可以指定一个默认值
 * @return <type>
 */
/*function getSignedCompany($htmlOption='', $defaultVal='') {
  $sql = "select ID, CONCAT_WS(' - ',CoName,Branch) from tblRegisteredCo where Active_Purchase=1 order by CoName asc ";
  $coResult = mysqli_query($insightDBLink, $sql);
  while( $row=mysqli_fetch_row($coResult) ) {
    $dataArr[]=$row;
  }
  //add other option
  //$dataArr[] = array ("0", "Other");
  // 如果需要直接输出 option
  if($htmlOption=='Y') {
    return createOptionHtml_1($dataArr, $defaultVal );
  }
  return $dataArr;
}*/

/** 解析二维数组为 json 对象
 * @param <> $arr_data 二维数组
 */
function dyadicArrayToJson($arr_data) {
  $json = '[';
  foreach( $arr_data as $row ) {
    $jsonrow ='{';
    foreach($row as $k=>$v ) {
      //$k = str_replace(array("\r\n","\n","\""),'',$k);
      //$v = str_replace(array("\r\n","\n","\""),array("","","\""),$v);
	  //$v = str_replace('"','\"',str_replace(array("\r\n","\n","\""),array("","","\""),$v));
	  $k = str_replace(["\\", "/", "\n", "\t", "\r", "\b", "\f", '"'], ['\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'],$k);
	  $v = str_replace(["\\", "/", "\n", "\t", "\r", "\b", "\f", '"'], ['\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'],$v);
      $jsonrow.= '"'.$k.'":"'.$v.'",';
    }
    // 过滤处理
    $jsonrow = (str_ends_with($jsonrow, ","))?(substr($jsonrow, 0, (strlen($jsonrow)-1))):$jsonrow;
    $jsonrow = $jsonrow.'},';
    $json .= $jsonrow;
  }
  $json = (str_ends_with($json, ','))?(substr($json, 0, (strlen($json)-1))):$json;
  $json = $json.']';
  return $json;
}

/** 一维关联数据返回 json 对象  */
function assocArrayToJson($arr) {
  if(is_array($arr) && count($arr)>0) {
    $jsonCostCat = '';
    foreach($arr as $k=>$v) {
      $v = str_replace("\r\n", '', $v);
      $v = str_replace("\n", '', $v);
      $v = str_replace("\"", '\"', $v);
      $jsonCostCat.='"'.$k.'":"'.$v.'",';
    }
    // 返回 json 字符串
    return '{'.substr($jsonCostCat, 0, -1).'}';
  }
  return '';
}

// 读取 EventLog 类型
function getLogDocType($htmlOption='') {
  global $insightDBLink;
  /* edit fung */
  //$sql = "select CodeValue, Description from tblGenericCode where CodeType='DocType' and CodeCat='EventLogCI' and  ActiveFlag=1 order by Description";
  $sql = "select CodeValue, Description from tblGenericCode where CodeType='DocType' and CodeCat='EventLogBR' and  ActiveFlag=1 order by Sequence";
  /* edit fung end*/
  $result = mysqli_query($insightDBLink, $sql);
  while($row=mysqli_fetch_row($result)) {
    $dataArr[]=$row;
  }
  if($htmlOption=='Y') {
    global $defVendorType;
    return createOptionHtml($dataArr, $defVendorType);
  }
  return $dataArr;
}

/* edit fung */
// 读取 EventISLin 类型
function getISLin($htmlOption='') {
  global $insightDBLink;
  $sql = "select CodeValue, Description from tblGenericCode where CodeType='DocType' and CodeCat='EventISLin' and  ActiveFlag=1 order by Description";
  $result = mysqli_query($insightDBLink, $sql);
  while($row=mysqli_fetch_row($result)) {
    $dataArr[]=$row;
  }
  if($htmlOption=='Y') {
    global $defVendorType;
    return createOptionHtml($dataArr, $defVendorType);
  }
  return $dataArr;
}
/* edit fung end*/

/**
 * 格式化数字或是货币
 * @param <type> $num
 * @param <type> $len
 * @param <type> $currency
 */
function formatNumber($num, $len='4', $currency=0){
  if(strlen($num)==0) {
    return '';
  }
    $num_arr = explode('.', $num);
  if( strlen($num_arr[1])<$len ) {
    $num_arr[1] = $num_arr[1].str_repeat('0', $len-(strlen($num_arr[1])) );
  } else {
    $num_arr[1] = substr($num_arr[1], 0, $len);
  }
  // 如果是需要货币格式
  if($currency==1){
    $num_arr[0] = number_format($num_arr[0], 0, '.', ',');
  }
  return $num_arr[0].'.'.$num_arr[1];
  
}

/**
 * 配合页面Js生成 tr 的ID， 算法与js算法一致
 */
function getTableRowId(){
  // (new Date()).getTime()+""+Math.ceil(Math.random()*100);
  return 'row_'.time().rand(10000, 99999);
}

// 读取状态列表
function getBRStatus() {
  $sql ="SELECT s.Sequence,s.CodeValue as Button,s.Description as Status, r.CodeValue as Role, r.Description as RoleName 
  FROM tblGenericCode as s left join  tblGenericCode as r on s.Sequence=r.Sequence and r.CodeType='BRStatus' and r.CodeCat='Role' 
  WHERE s.CodeType='BRStatus' AND s.CodeCat='Status' GROUP BY s.Sequence ORDER BY s.Sequence ASC";
  $StatusArr = getQueryResult($sql);
  return $StatusArr;
}

function getNextISSequence($currentISSequence,$CodeCat) {
  if($CodeCat == "Charge"){
	$CodeCat_cond = "CodeCat = 'Status'";
  } elseif ($CodeCat == "CN"){
	$CodeCat_cond = "CodeCat = 'CNStatus'";
  }
  $sql ="SELECT Sequence FROM tblGenericCode  WHERE CodeType = 'ISStatus' AND ".$CodeCat_cond." AND ActiveFlag = 1 ".
		"AND Sequence > ".$currentISSequence." ORDER BY Sequence ASC LIMIT 1;";    
  $NextISSequence = getOneCell($sql);
  if($NextISSequence ==''){
	$NextISSequence = $currentISSequence;
  }
  return $NextISSequence;
}

function getPerviousISSequence($currentISSequence,$CodeCat) {
  if($CodeCat == "Charge"){
	$CodeCat_cond = "CodeCat = 'Status'";
  } elseif ($CodeCat == "CN"){
	$CodeCat_cond = "CodeCat = 'CNStatus'";
  }
  $sql ="SELECT Sequence FROM tblGenericCode  WHERE CodeType = 'ISStatus' AND ".$CodeCat_cond." AND ActiveFlag = 1 ".
		"AND Sequence < ".$currentISSequence." ORDER BY Sequence DESC LIMIT 1;";    
  $PerviousISSequence = getOneCell($sql);
  if($PerviousISSequence ==''){
	$PerviousISSequence = $currentISSequence;
  }
  return $PerviousISSequence;
}

/** 读取用户角色信息
 * @global <string> $PHP_AUTH_USER 当前登录账号
 * @param <string> $repeat 是否允许重复角色出现
 * @return <array> 用户信息
 */
//function getPaymentRole($noRepeat='Y') {
function getBudgetRole() {
  global $PHP_AUTH_USER;
  /*$sql = "SELECT distinct g.Sequence, g2.CodeValue, g2.Description as Role FROM tblGenericCode as g, tblStaffGps as s , tblGenericCode as g2 
	WHERE g.CodeType='BRStatus' AND (g.CodeCat='CapRole' OR g.CodeCat='COSRole' OR g.CodeCat='OpRole') 
	and s.LoginName='$PHP_AUTH_USER' and s.GroupID=g.CodeValue AND g.Sequence = g2.Sequence AND g2.CodeType='BRStatus' AND g2.CodeCat='Role' ORDER BY g.Sequence ASC";*/
  $sql = "SELECT r.Description as Role, min( r.Sequence ) 
		FROM tblGenericCode r, tblGenericCode g, tblStaffGps s
		WHERE s.LoginName = '$PHP_AUTH_USER'
		AND s.GroupID = g.CodeValue
		AND g.CodeCat
		IN ('CapRole', 'COSRole', 'OpRole', 'Role') and g.CodeValue is not null and g.CodeValue > '0'
		AND g.CodeType = 'BRStatus'
		AND g.Sequence = r.Sequence
		AND r.CodeType = 'BRStatus'
		AND r.CodeCat = 'Role'
		GROUP BY r.Description ORDER BY min( r.Sequence )";

  $RoleArr = getQueryResult($sql);
  /*if($noRepeat=='Y') {
    // 去除重复角色
    $roleList = array();
    foreach($RoleArr as $k=>$row ){
      $r = $row['Role'];
	  //$r = $row['Description'];	  
      if(in_array($r, $roleList)){
        unset ($RoleArr[$k]);
      } else {
        array_push($roleList, $r);
      }
    }
  }*/
  return $RoleArr;
}

// 读取用户组别信息
function getAppUserGroup() {
  $id = intval(_request_function('id'));
  $Role = '';
  if($id>0){
    // 读取记录当前的流程, 然后查询用户组信息
    //$sql="SELECT CodeValue FROM tblGenericCode AS g, tblISPayment AS p WHERE g.CodeType='ISStatus' ".
    //"AND g.CodeCat='Role' AND g.ActiveFlag=1 AND p.CurrentStep=g.Sequence AND p.InvoiceID=".$id;	 
	$sql="SELECT CodeValue FROM tblGenericCode AS g, tblBudget_header AS p WHERE g.CodeType='ISStatus' ".
      "AND g.CodeCat='Role' AND g.ActiveFlag=1 AND p.CurrentStep=g.Sequence AND p.ID=".$id;
    $Role = getOneCell($sql);
  } else {
    // 那么此时属于新增过程, 则查询新增用户组
    $sql="SELECT CodeValue FROM tblGenericCode WHERE CodeType='ISStatus' AND CodeCat='Role' AND Sequence=1 And ActiveFlag=1";
    $Role = getOneCell($sql);
  }
  return $Role;
}

function getAppUser() {
  global $insightDBLink;
  global $PHP_AUTH_USER;
  $id = intval(_request_function('id'));
  if($id>0){
    // 读取记录当前的流程, 然后查询用户组信息
	/*
	$sql="SELECT LoginName FROM `tblStaffGps` WHERE ".
		 "((`Region` IN (SELECT DISTINCT Region FROM `tblStaffGps` WHERE GroupID like 'IS%' AND LoginName = (SELECT CreateUser FROM tblGenericCode AS g, tblBudget_header AS p WHERE g.CodeType='ISStatus' AND g.CodeCat='Role' AND g.ActiveFlag=1 AND g.CodeValue='ISC' AND p.ID=".$id.")) ".
		 "AND `GroupID` IN (SELECT DISTINCT CodeValue FROM `tblGenericCode` WHERE (CodeType = 'ISStatus' OR CodeType = 'CNStatus') AND CodeCat ='Role' AND ActiveFlag=1))) AND LoginName = '".$PHP_AUTH_USER."'";
		 //"OR `GroupID` IN (SELECT DISTINCT CodeValue FROM `tblGenericCode` WHERE (CodeType = 'CNStatus') AND CodeCat ='Role' AND ActiveFlag=1)) AND LoginName = '".$PHP_AUTH_USER."'";
	*/
	$sql="SELECT DISTINCT(CodeValue), ActionUser FROM tblPCActFlow as p, tblGenericCode as g WHERE p.Role = g.Description ".
		 "AND g.CodeType = 'ISStatus' AND g.CodeCat = 'Role' AND p.LevelInd = 'IS' AND p.ReferenceID = '$id' AND p.ActionUser != ''";
		 
	$Data = getQueryResult($sql);

	foreach ($Data as $k=>$row) {	
		$sql1="SELECT LoginName FROM `tblStaffGps` WHERE GroupID = '$row[CodeValue]' AND Region IN ( SELECT Region
FROM tblStaffGps WHERE (GroupID = '$row[CodeValue]' OR GroupID = 'ISO') AND LoginName = '$row[ActionUser]')";
		if ( $row[CodeValue] == "ISC"){
			$isosql = "SELECT COUNT(*) FROM tblStaffGps WHERE GroupID = 'ISO' AND LoginName='$PHP_AUTH_USER' AND (Filter1 LIKE concat('%',(SELECT Region
			FROM tblStaffGps WHERE GroupID = 'ISC' AND LoginName = '$row[ActionUser]'),'%') OR Filter1='ALL')";
			$ISOUser = getOneCell($sql1);
			if ($ISOUser) {
				$LoginName[] = $PHP_AUTH_USER;
				//echo $PHP_AUTH_USER;
			}
		}
		$UserList = getQueryResult($sql1);
		foreach ($UserList as $k=>$row) {
			$LoginName[] = $row[LoginName];
			//echo $row[LoginName]." ";
			}
	}
	$resultfound = in_array($PHP_AUTH_USER, $LoginName);
	//if(!$resultfound){
	//	$isosql = "SELECT * FROM `tblStaffGps` WHERE `GroupID` = 'ISO' AND ";

	//}

  } else {
    // 那么此时属于新增过程, 则查询新增用户组
    $sql="SELECT LoginName  FROM `tblStaffGps` WHERE GroupID IN (SELECT CodeValue FROM tblGenericCode WHERE CodeType='ISStatus' AND CodeCat='Role' AND Sequence=1 And ActiveFlag=1) AND LoginName = '".$PHP_AUTH_USER."'";
	$result=mysqli_query($insightDBLink, $sql);
	$resultfound = mysqli_num_rows($result);
  }
	
		
	//if(!$resultfound){		
		//echo "<font face='Arial'>";	
		//echo "<p><font color=red>Error!</font>You are not allowed to access this page.</p>";
		//echo "</font>";
		//exit();
	//}
}

/**
 * 在跟目录下保存日志
 * @param <type> $str 日志内容
 */
function myLog($str) {
  $log_path = realpath('.').'/'.date('Ymd').'.log';
  $f = fopen($log_path, 'a');
  $str = date('H:i:s ').$str."\n";
  fwrite($f, $str);
  fclose($f);
}

/**
 * 插入tblPCActFlow
 *
 */
function InsertApproverPCActFlow($InvID,$uid)
{
  global $insightDBLink;
  $SQL = "SELECT s.Sequence,s.CodeValue as Button,s.Description as Status, r.CodeValue as Role, r.Description as RoleName 
  FROM tblGenericCode as s left join tblGenericCode as r on s.Sequence=r.Sequence and r.CodeType='BRStatus' and r.CodeCat='Role' 
  WHERE s.CodeType='BRStatus' AND s.CodeCat='Status' GROUP BY s.Sequence ORDER BY s.Sequence ASC";
  
  $res = getQueryResult($SQL);
  //$len = count($res);
  
 /*$len = 5;

  for($seq=1;$seq<=$len;$seq++)
  {*/
	//if ($Sequence == 1) { 
	$Rolename = $res[1]['RoleName'];
	$Role = $res[1]['Role'];
	$StatusName = $res[1]['Status'];
	/*} elseif ($Sequence == 2) { 
		$Rolename = $res[$Sequence]['RoleName'];
		$StatusName = 'Approve';
	}*/
	
    $SQL ="INSERT INTO tblPCActFlow SET ";
    $SQL.="LevelInd='BR',";
    $SQL.="ReferenceID=".$InvID.",";
    //$SQL.="Sequence=".$seq.",";
	$SQL.="Sequence='1',";
	$SQL.="Role='$Rolename',";
	$SQL.="StatusName='$StatusName',";
	//$SQL.="Amount1='$CapexLimit',";
	//$SQL.="Amount2='$OpexLimit',";
	$SQL.="Filter1='$Role',";
	$SQL.="ActionUser='$uid'";

    mysqli_query($insightDBLink, $SQL);
	
	//echo $SQL;
	
	
  //}
  
  //die;
 
}
/*
function getInvStatusFromInvLines($inv_id) {
	$sql = "SELECT DISTINCT Description FROM tblGenericCode WHERE (CodeCat = 'Status' OR CodeCat = 'CNStatus') AND CodeType = 'ISStatus' AND Sequence = (
		SELECT MIN(CurrentStep) FROM tblISInvLine WHERE InvoiceID = $inv_id AND (LineStatus IS NULL OR (LineStatus != 'Disputed' AND LineStatus != 'Cancelled')))";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		$inv_status = dbSelectColumnFromQuery($query, 0);
		return $inv_status;
	} else {
		return false;
	}
}

function getInvIdFromInvLineId($inv_line_id) {
	$sql = "SELECT InvoiceID FROM tblISInvLine WHERE ID = $inv_line_id";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		$inv_id = dbSelectColumnFromQuery($query, 0);
		return $inv_id;
	} else {
		return false;
	}
}

function getInvCurrentStepFromInvoice($inv_id) {
	$sql = "SELECT CurrentStep FROM tblBudget_header WHERE ID = $inv_id";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		$inv_current_step = dbSelectColumnFromQuery($query, 0);
		return $inv_current_step;
	} else {
		return false;
	}
}

function checkInvoiceNo($vendcode, $inv_no){
	$pure_inv_n = $inv_no;
	$sql1 = "SELECT * FROM tblVendor where EpicorCode = '".$vendcode."' OR GroupID = (Select GroupID FROM tblVendor where EpicorCode = '".$vendcode."')";
	$sql2 = "SELECT * FROM tblISOldInvoice where VendorCode ='".$vendcode."' AND (InvoiceNum='".$inv_no."' OR NormalizedInvoiceNum='".$pure_inv_no."')";
	$sql3 = "SELECT * FROM tblBudget_header where VendorCode ='".$vendcode."' AND (InvoiceNum='".$inv_no."' OR NormalizedInvoiceNum='".$pure_inv_no."')";
	if(mysqli_num_rows(mysqli_query($insightDBLink, $sql3))){
		// Code inside
		echo "NO result for SQL3";
	} 
	
}
*/
//function getTeamMembers($login_name, $group_id) {
function getTeamMembersBRID($login_name, $role) {
	global $insightDBLink;
	/*$sql = "SELECT LoginName FROM tblStaffGps WHERE GroupID = '$group_id' AND Region = (
		SELECT Region FROM tblStaffGps WHERE GroupID = '$group_id' AND LoginName = '$login_name')";
	$sql = "SELECT DISTINCT LoginName FROM tblStaffGps WHERE GroupID IN ('".implode("','", $group_id)."') AND Region IN (
		SELECT Region FROM tblStaffGps WHERE GroupID IN ('".implode("','", $group_id)."') AND LoginName = '$login_name')";*/
	$sql = "SELECT DISTINCT b.ID FROM tblBRBudget b, tblPCActFlow f, tblStaffGps g1, tblStaffGps g2
			WHERE b.ID = f.ReferenceID AND f.LevelInd = 'BR' AND f.Role = '$role'
			AND g1.LoginName = f.ActionUser AND g1.GroupID = f.Filter1
			AND g2.LoginName = '$login_name' AND g2.GroupID = f.Filter1 AND g2.Region = g1.Region
			UNION
			SELECT DISTINCT ReferenceID as ID FROM tblPCActFlow WHERE LevelInd = 'BR' AND ActionUser = '$login_name' AND Role = '$role' ORDER BY ID";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$members[] = $value["ID"];	
		}
		return $members;
	} else {
		return false;
	}
}

function findRoleByDesc($desc, $field, $status) {
	global $insightDBLink;
	// $field = "CodeValue" for role code, 
	// $field = "Sequence" for current step
	// $status = "ISStatus" or "CNStatus"

	//$sql = "SELECT $field FROM tblGenericCode WHERE CodeType = '$status' AND CodeCat = 'Role' AND Description = '$desc'";
	if($desc=='Requester' || $field == "Sequence"){
		$sql = "SELECT $field FROM tblGenericCode WHERE CodeType = '$status' AND CodeCat = 'Role' AND Description = '$desc'";
	} else {
		/*$sql = "SELECT DISTINCT b.$field FROM tblGenericCode AS a, tblGenericCode AS b WHERE a.CodeType = '$status' AND a.CodeCat = 'Role'
				AND a.Description = '$desc'	AND a.Sequence = b.Sequence AND b.CodeCat IN ('CapRole', 'COSRole', 'OPRole')";*/
		$sql = "SELECT DISTINCT b.$field FROM tblGenericCode AS a, tblGenericCode AS b WHERE a.CodeType = '$status' AND a.CodeCat = 'Role'
				AND a.Description = '$desc'	AND a.Sequence = b.Sequence AND b.CodeCat IN ('CapRole', 'COSRole', 'OPRole')
				UNION
				SELECT DISTINCT $field FROM tblGenericCode WHERE CodeType = '$status' AND CodeCat = 'Role' AND Description = '$desc' AND $field !=''";
	}

	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$vals[] = $value[$field]; 
		}
		return $vals;
	} else {
		return false;
	}
}


function BRID($value) {
	return "BR".sprintf("%08d", $value);
}
/*
function calPaidOrPaymentAmount($id, $type, $lvl="h") {
	// total paid amount or total payment amount
	// $type = "Paid" or "Payment", $lvl = "h":header (default) or "d":detail
	if (($type!="Paid" && $type!="Payment") || ($lvl!="h" && $lvl!="d")) return false;
	$str_lvl = ($lvl=="h") ? "InvoiceID" : "ID";
	
	// calculation for invoice
	$sql = "SELECT SUM(m.Amount) 
		FROM tblISInvLine l LEFT JOIN tblISMatchCI m ON l.ID = m.InvLineID LEFT JOIN tblISVoucher v ON m.VoucherID = v.ID	
		WHERE l.$str_lvl = $id AND l.RecordType = 'Charge' AND ";
	$sql .= ($type=="Paid") ? "v.DisbursementID > 0" : "(v.DisbursementID IS NULL OR v.DisbursementID = 0)"; 
	$ch_item_amt = getOneCell($sql); // chargeable item paid or payment amount
	
	// calculation for credit note
	$sql = "SELECT SUM(m.Amount) 
		FROM tblISInvLine l LEFT JOIN tblISMatchCI m ON l.ID = m.InvLineID 
		WHERE l.$str_lvl = $id AND l.RecordType = 'CN' AND m.VoucherID > 0";
	$cn_item_amt = getOneCell($sql); // credit note item paid amount
	if ($type=="Payment") {
		$sql = "SELECT SUM(InvAmount) FROM tblISInvLine	WHERE $str_lvl = $id AND RecordType = 'CN'";
		$charge_amt = getOneCell($sql);
		$cn_item_amt = $charge_amt - $cn_item_amt;
	}
	
	$sum_amt = $ch_item_amt + $cn_item_amt;
	return $sum_amt;
}

function calCNOffsetAmount($inv_id) {
	// total cn offset amount		
	//$sql = "SELECT SUM(Amount) FROM tblISMatchCI 
		//WHERE OriginalInvoiceID = $inv_id AND (CostItemID IS NULL OR CostItemID = 0)";
	$sql = "SELECT sum(Amount) FROM tblISMatchCI, tblISInvLine 
	WHERE tblISInvLine.ID = tblISMatchCI.InvLineID AND tblISInvLine.CurrentStep >10 AND OriginalInvoiceID = $inv_id AND (CostItemID IS NULL OR CostItemID = 0)";
	return getOneCell($sql);	
}

function calTotalInvAmount($inv_id) {
	// total invoice amount
	$sql = "SELECT SUM(InvAmount) FROM tblISInvLine WHERE InvoiceID = $inv_id";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		return dbSelectColumnFromQuery($query, 0);		
	} else {
		return false;
	}
}

function calWithholdAmount($inv_id) {
	// total withhold amount
    $amt_invoice 	= calTotalInvAmount($inv_id);
    $amt_paid 		= calPaidOrPaymentAmount($inv_id, "Paid");
    $amt_payment 	= calPaidOrPaymentAmount($inv_id, "Payment");
	
	return $amt_invoice - $amt_paid - $amt_payment;
}

function calInvLineWithholdAmount($inv_id) {
	// InvLine withhold amount
    $amt_invoice 	= calInvAmount($inv_id);
    $amt_paid 		= calPaidOrPaymentAmount($inv_id, "Paid", "d");
    $amt_payment 	= calPaidOrPaymentAmount($inv_id, "Payment", "d");
	
	return $amt_invoice - $amt_paid - $amt_payment;
}

function calInvAmount($inv_id) {
	// InvLine invoice amount
	$sql = "SELECT InvAmount FROM tblISInvLine WHERE ID = $inv_id";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		return dbSelectColumnFromQuery($query, 0);		
	} else {
		return false;
	}
}

function findSignedCoName($signed_co) {
	$sql = "SELECT CASE WHEN Branch IS NULL THEN CoName ELSE CONCAT(CoName, ' - ', Branch) END AS CoName  
		FROM tblRegisteredCo WHERE ID = $signed_co"; 
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		return dbSelectColumnFromQuery($query, 0);
	} else {
		return false;
	}
}

function findEpicorCompanyCode($signed_co) {
	$sql = "SELECT LPAD(CoCode, 3, 0) FROM tblCo_Entities WHERE SignedCo = ".$signed_co;
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		return dbSelectColumnFromQuery($query, 0);
	} else {
		return false;
	}
}

function findISCurrentStepName($ref_id, $inv_current_step) {
	if (!$ref_id || !$inv_current_step) return false;
	
	$sql = "SELECT StatusName FROM tblPCActFlow
		WHERE LevelInd = 'IS' AND Sequence = $inv_current_step AND ReferenceID = $ref_id"; 
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		return dbSelectColumnFromQuery($query, 0);
	} else {
		return false;
	}
}
*/
// should call this function after the current step is updated 
function sendEmailStatusChange($id,$user,$action=NULL,$GeneratedPR=NULL,$urlPath=NULL,$emailSubject=NULL,$emailFirstSentence=NULL,$isBackEndScript=NULL) { 
	global $insightDBLink;
	global $server_path;

	// prepare data
	$br_id_long = BRID($id);
	$br_header = getBRHeader($id);
	//$br_header["Role"] = getActFlow2($id);
	
	$to_id = [];
	$CurrentStep = $br_header["CurrentStep"];
	$ApprovalList = getActFlow2($id);
	
	/*if($action=="On-hold"){
		foreach ($ApprovalList as $row) {			
			if($row["Sequence"]==1){
				$Requester=$row["ActionUser"];
				$to_id[]=$Requester;
			}	
		}
	} else {*/
		foreach ($ApprovalList as $row) {
			
			if($row["Sequence"]==1){
				$Requester=$row["ActionUser"];				
			}
			
			if($row["Sequence"]==$CurrentStep && $row['StatusName'] == 'NO'){
				if($CurrentStep<11){
					$to_id[]=$row["ActionUser"];
				} else {
					$to_id[]=$Requester;
				}			
			}		
		}
	$cc_emails = null;
	if($action!=$br_header["BRStatus"]){
		$cc = [$Requester, $br_header['ProjectManager']];
		if ($br_header['CreateUser'] != 'insight') {
			$cc[] = $br_header['CreateUser'];
		}
		$cc_emails = getRecipientEmails($cc);
	}
	//}
	$to_emails = getRecipientEmails($to_id);
	
	if($isBackEndScript == TRUE) {
		echo 'BRID:'. $br_id_long .'<br/>';
		for($i=0;$i<count($to_id);$i++) {
			if($i==0) {
				echo 'To:';
			}
			echo '&nbsp;&nbsp;&nbsp;'. $to_id[$i] .'('. $to_emails[$i] .')<br/>';
		}
	}
	//var_dump($to_emails);die; 
	//$cc_emails = getRecipientEmails($user);
	$routedUser	= '';
	foreach($to_id as $key => $value){
		$routedUser .= getUserFullName($to_id[$key]).', ';
	}
	$routedUser=substr($routedUser, 0, (strlen($routedUser)-2) );
	//echo $routedUser;die;
	
	// subject
	if($emailSubject != NULL) {
		$subject = $emailSubject;
	} else {
		$subject = "Budget Approval : $br_id_long";		
	}

	// content message
	if($emailFirstSentence != NULL) {
		$message = $emailFirstSentence . ".\r\n\n";
	} else {
		$message = "Budget Approval ".$br_id_long." has been updated by ".getUserFullName($user).".\r\n\n";	
	}
	
	$message .= "Budget Approval Status : ".$br_header["BRStatus"]."\r\n\n";
	if($action=="DENY" || $action=="HOLD"){
		$query = mysqli_query($insightDBLink, "SELECT Description FROM tblGenericCode WHERE CodeType = 'DocType' AND CodeValue = '$action' AND CodeCat = 'EventLogBR'");
		$reason = dbSelectColumnFromQuery($query, 0);	
		$message .= "$reason : ".getEvengLogReason($id,$action)."\r\n\n";	
	}
	$message .= "Requester : ".getUserFullName($Requester)."\r\n\n";
	$message .= "Request Date :  ".date("d-M-Y", strtotime($br_header['LastUpdDate']))."\r\n\n";
	$message .= "Record Creation Date : ".date("d-M-Y", strtotime($br_header['CreateDate']))."\r\n\n";
	if($br_header['SalesProjectID']!=''){
		$message .= "Sales Project ID : ".$br_header['SalesProjectID']."\r\n\n";
	} else {
		$message .= "Project No : ".$br_header['ProjectNum']."\r\n\n";
	}
	$message .= "Project Name : ".$br_header['ProjectName']."\r\n\n";
	$message .= "Budget Description : ".$br_header['Description']."\r\n\n";
	$message .= "Project Manager : ".getUserFullName($br_header['ProjectManager'])."\r\n\n";
	$message .= "Project Initiator : ".getUserFullName($br_header['ProjectInitiator'])."\r\n\n";
	$message .= "Project Sponsor : ".getUserFullName($br_header['ProjectSponsor'])."\r\n\n";
	$message .= "Requested Capex Amount (USD) : ".formatNumber($br_header["RequestedCapEx"],2,1)."\r\n\n";	
	$message .= "Requested Opex Amount (USD) : ".formatNumber($br_header["RequestedOpEx"],2,1)."\r\n\n";	
	
	/*$message .= "Status changed to ".findISCurrentStepName($id, $inv_header["CurrentStep"]).".\r\n\n";	
	$message .= "Creator :  ".getUserFullName($inv_header["Role"]["1"])."\r\n\n";
	$message .= "Functional Verifier :  ".getUserFullName($inv_header["Role"]["2"])."\r\n\n";
	$message .= "Payment Processor :  ".getUserFullName($inv_header["Role"]["10"])."\r\n\n";
	$message .= "Voucher Processor :  ".getUserFullName($inv_header["Role"]["11"])."\r\n\n";
	$message .= "Record Creation Date :  ".date("d-M-Y", strtotime($inv_header["CreateDate"]))."\r\n\n";
	$message .= "Signed Company :  ".findSignedCoName($inv_header["SignedCo"])."\r\n\n";
	$message .= "Vendor Name :  ".findVendorName($inv_header["VendorCode"])."\r\n\n";
	$message .= "Invoice Amount (Local Curr) :  ".$inv_header["Currency"]." ";
	$message .= ($inv_header["Currency"]=="JPY"||$inv_header["Currency"]=="KRW")?number_format($inv_header["InvoiceAmount"],0):number_format($inv_header["InvoiceAmount"],2);
	$message .= "\r\n\n";*/
	if($routedUser){
	$message .= "Your request has been routed to ".$routedUser.".\r\n\n";
	}
	
	$MACost = getMACost($id);
	
	if($MACost){
		
		foreach ($MACost as $row) {
			
			$BudgetMargin = getBudgetMargin($id,$row["ID"]);
			$ActualOneTime = $BudgetMargin[0];
			$ActualMonthly = $BudgetMargin[1];
			
			$ActualTotal = $ActualOneTime+$ActualMonthly*$row["contract_period"];
			$ActualOneTimeCM = $row["OneTimeRev"]-$ActualOneTime;
			$ActualMonthlyCM = $row["MonthlyRev"]-$ActualMonthly;
			$ActualTotalCM =$row["TotalContractRevenue"]-$ActualTotal;
			$onetime_gmp = CalMargin($row['OneTimeRev'],$ActualOneTimeCM);
			$monthly_gmp = CalMargin($row['MonthlyRev'],$ActualMonthlyCM);
			$total_gmp = CalTotalContributionMargin($row['TotalContractRevenue'],$ActualTotalCM);
			
			$message .= "Revenue and margin information for ".$row["ID"]."\r\n";
			$message .= "Service Contract Period  : ".$row["contract_period"]." month(s)\r\n\n";
			
			$message .= "Cost in MA (USD)\r\n";
			$message .= " - One Time : ".formatNumber($row["OneTimeTC"],2,1)."\r\n";
			$message .= " - Monthly : ".formatNumber($row["MonthlyTC"],2,1)."\r\n";
			$message .= " - Total : ".formatNumber($row["TotalContractCost"],2,1)."\r\n\n";
			
			$message .= "Cost in Budget (USD)\r\n";
			$message .= " - One Time : ".formatNumber($ActualOneTime,2,1)."\r\n";
			$message .= " - Monthly : ".formatNumber($ActualMonthly,2,1)."\r\n";
			$message .= " - Total : ".formatNumber($ActualTotal,2,1)."\r\n\n";
			
			$message .= "Contribution Margin (%) in MA\r\n";
			$message .= " - One Time : ".formatNumber($row["OneTimeGMP"],2,1)."% \r\n";
			$message .= " - Monthly : ".formatNumber($row["MonthlyGMP"],2,1)."% \r\n";
			$message .= " - Total : ".formatNumber($row["TotalContributionMarginPercent"],2,1)."% \r\n\n";
			
			$message .= "Contribution Margin (%) in Budget\r\n";
			$message .= " - One Time : ".formatNumber($onetime_gmp,2,1)."% \r\n";
			$message .= " - Monthly : ".formatNumber($monthly_gmp,2,1)."% \r\n";
			$message .= " - Total : ".formatNumber($total_gmp,2,1)."% \r\n\n";
		}
	}
	
	if(count($GeneratedPR)>0){
		$message .= "The following Purchase Requests have been generated:\r\n";
		$message .= implode(", ", $GeneratedPR)."\r\n\n";
	}
	
	$message .= "Please click below for request $br_id_long details.\r\n\n";
	$message .="************************************************************\r\n\n";
	$message .="Please Note: If clicking on the URL does not work,\r\n\n";
	$message .="please copy and paste the entire URL in your browser window\r\n\n";
	$message .="************************************************************\r\n\n";

	if($urlPath == NULL ) {
		$message .= "$server_path".preg_replace('/.*htdocs/','',getcwd())."/approval.php?id=".$id;
	}else{
		$message .= $urlPath;
	}
	
	sendEmail($to_emails, $subject, $message, $cc_emails);

}

function getBRHeader($BRID) {
	global $insightDBLink;
	if (!$BRID) return false;
	
	$sql = "SELECT * FROM tblBRBudget WHERE ID = '$BRID'"; 
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		return mysqli_fetch_array($query);
	} else {
		return false;
	}		
}
/*
function getInvoiceLine($invln_id) {
	if (!$invln_id) return false;
	
	$sql = "SELECT * FROM tblISInvLine WHERE ID = $invln_id"; 
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		return mysqli_fetch_array($query);
	} else {
		return false;
	}		
}*/

function getActFlow($id) {
	global $insightDBLink;
	
	$sql = "SELECT Sequence, ActionUser FROM tblPCActFlow WHERE LevelInd = 'BR' AND ReferenceID = '$id'";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		/*while ($value = mysqli_fetch_array($query)) {
			$act_flow[$value["Sequence"]] = $value["ActionUser"];
		}
		return $act_flow;*/
		return mysqli_fetch_array($query);
	} else {
		return false;
	}
}

function getActFlow2($id) {
	$sql = "SELECT ID, ActionUser, Role, Amount1, Amount2, StatusName, Sequence from tblPCActFlow where ReferenceID = '$id' and LevelInd = 'BR' ORDER BY Sequence, ID";
	$query = getQueryResult($sql);
	if ($query) {
		return $query;
	} else {
		return false;
	}		
}

function getRecipientEmails($recipient_ids) {
	global $insightDBLink;
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

function findVendorType($vendor_code) {
	global $insightDBLink;
	$sql = "SELECT vendor_type FROM tblVendor WHERE EpicorCode <> '' AND EpicorCode = '$vendor_code'";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$vendor_type = $value["vendor_type"];
		} 
		return $vendor_type;
	} else {
		return false;
	}			
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

function findVendorNameByID($vendorid) {
	global $insightDBLink;
	$sql = "SELECT CoName FROM tblVendor WHERE ID = '$vendorid'";
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

function findVendorCode($vendorid) {
	global $insightDBLink;
	if (!$vendorid) return false;
	
	$sql = "SELECT EpicorCode FROM tblVendor WHERE ID = '$vendorid' AND vendor_status ='A'";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) {
			$VendorCode = $value["EpicorCode"];
		} 
		return $VendorCode;
	} else {
		return false;
	}			
}

//function sendEmail($recipient, $subject, $message, $cc, $attachment=null) { 
function sendEmail($recipient, $subject, $message, $cc=null, $attachment=null) {
	// require phpInc/config.inc.php
	global $webhynet_email;
	global $intranet_naming;
	global $ATTACH_SAVE_URL;

	$title_fr = "$intranet_naming System";
	$title_to = $recipient;
	$title_to[count($title_to)-1] = implode(", ", $title_to); 
	//var_dump($recipient);
	//die;
	
	//if ($cc){

		$sent = smtp_mailcc($recipient, $title_to, $subject, $message, $webhynet_email, $title_fr, $cc);

	//} else {
		//$sent = smtp_mailcc($recipient, $title_to, $subject, $message, $webhynet_email, $title_fr, $cc);
	//}
	/*if ($attachment) {
		// no cc when sending with attachment
		foreach ($recipient as $to) {
			// $sent = sendMIMEMail("wkyeung@hotmail.com", "wkyeung@pccwglobal.com", "subject", "message", "file/100604_1828.txt", "100604_1828.txt");		
			$sent = sendMIMEMail($webhynet_email, $to, $subject, $message, $ATTACH_SAVE_URL."/".$attachment, $attachment);
		}
	} else {*/
		//$sent = smtp_mailcc($recipient, $title_to, $subject, $message, $webhynet_email, $title_fr, $cc);
		//$sent = smtp_mail($recipient, $title_to, $subject, $message, $webhynet_email, $title_fr);
	//}
	if ($sent) return true;
}



function findBREventLogDocTypeDesc($doc_type) {
	$sql = "SELECT Description FROM tblGenericCode 
		WHERE CodeType = 'DocType' AND CodeValue = '$doc_type' AND CodeCat = 'EventLogBR'";
	return getOneCell($sql);
}

function findAttachmentName($log_id) {
	$sql = "SELECT Attachment FROM tblPCEventLog WHERE ID = $log_id";
	return getOneCell($sql);
}

function findISCLoginName($id) {
	global $insightDBLink;
	global $PHP_AUTH_USER;
	if ($id>0){
		$sql = "SELECT F.ActionUser FROM tblPCActFlow AS F LEFT JOIN tblStaffInfo AS U ON U.LoginName=F.ActionUser WHERE LevelInd='BR' AND F.Sequence = '1' AND ReferenceID='".$id."' LIMIT 1";
		if ($query = mysqli_query($insightDBLink, $sql)) {
			while ($value = mysqli_fetch_array($query)) {
				$arr = $value;
			}
			return $arr[0];
		} else {
			return false;
		}
	} else {
		return $PHP_AUTH_USER;
	}
}

function getApprovalFlow(){
	$sql ="SELECT Sequence, Description as UpperLimit FROM `tblGenericCode` WHERE `CodeType`='BRStatus' AND CodeCat = 'CapRole' AND ActiveFlag = 1";
	$resultArr = getQueryResult($sql);
	
	foreach($resultArr as $k=>$row) {
		//$resultArr['LowerLimit']=$row['Description'];
		//$resultArr[$k]['LowerLimit']=substr($resultArr[$k]['LowerLimit'], 0, strpos($resultArr[$k]['LowerLimit'],','));
		$resultArr[$k]['UpperLimit']=substr(strrchr($resultArr[$k]['UpperLimit'],','),1);
	}
	
	return $resultArr;  
}

function unformatAmount($value) {
	return preg_replace("/[,]/", '', $value);
}


function unformatCode($value) {
	$value = preg_replace("/([^0-9])/", '', $value);
    return preg_replace("/(^0*)/",    '', $value);
	
}

function SPID($code) {
	if ($code) {
		$code = 'SP'.sprintf('%06d', $code);
	}
	return $code;
}	

function findCostTypeDesc($value) {
	if (!$value) return false;
	$sql = "SELECT Description FROM tblGenericCode WHERE CodeType = 'CostType' AND ActiveFlag = 1 AND CodeValue = '$value'";
	return getOneCell($sql);
}

function findCostCatDesc($value) {
	if (!$value) return false;
	$sql = "SELECT Description FROM tblGenericCode WHERE CodeType = 'CostCat' AND ActiveFlag = 1 AND CodeValue = '$value'";
	return getOneCell($sql);
}

function findCostSubcatDesc($value) {
	if (!$value) return false;
	$sql = "SELECT Description FROM tblGenericCode WHERE CodeType = 'CostSubcat' AND ActiveFlag = 1 AND CodeValue = '$value'";
	return getOneCell($sql);
}

function findNomenclateDesc($codecat, $value) {
	if (!$codecat && !$value) return false;
	$sql = "SELECT b.Description
		FROM tblGenericCode a left join tblGenericCode b 
			ON a.CodeValue = b.CodeType
		WHERE a.CodeType = 'NomenGrp'
			AND a.CodeCat = '$codecat'
			AND b.CodeValue = '$value'
			AND a.ActiveFlag = 1 AND b.ActiveFlag = 1";
	return getOneCell($sql);
}

function findCountryDesc($value) {
	if (!$value) return false;
	$sql = "Select CountryName from tblCountry where ID = '$value'";
	return getOneCell($sql);
}

function findCityDesc($value) {
	if (!$value) return false;
	$sql = "Select CityName from tblCity where ID = '$value'";
	return getOneCell($sql);
}

function findCurrencyDesc($value) {
	if (!$value) return false;
	$sql = "select b.Description from tblGenericCode a, tblGenericCode b 
		where a.CodeType = 'CompCurr' and b.CodeType = 'CurrCode' and b.CodeValue = a.CodeValue 
		and a.ActiveFlag=1 and b.ActiveFlag=1 AND a.CodeValue = '$value'";
	return getOneCell($sql);
}

function getProjectManagerList($default='') {
	$sql = "SELECT g.LoginName from tblStaffGps as g, tblStaffInfo as i 
	where g.LoginName = i.LoginName AND g.GroupID = 'BRR' order by i.StaffNameLast, i.StaffNameFirst, i.StaffNickname asc";
	$Data = getQueryResult($sql);
	$dataArr = [];
	foreach($Data as $row ){
		$dataArr[] = [$row['LoginName'], getUserFullName($row['LoginName'])];
	}
	return createOptionHtml($dataArr, $default);
}

function getProjectInitiatorList($default='') {
	$sql = "SELECT LoginName FROM `tblStaffInfo` WHERE `DateQuit` IS NULL order by StaffNameLast, StaffNameFirst, StaffNickname asc";
	$Data = getQueryResult($sql);
	$dataArr = [];
	foreach($Data as $row ){
		$dataArr[] = [$row['LoginName'], getUserFullName($row['LoginName'])];
	}
	return createOptionHtml($dataArr, $default);
}

function getProjectSponsorList($default='') {
	$sql = "SELECT DISTINCT g.LoginName from tblStaffGps as g, tblStaffInfo as i 
	where g.LoginName = i.LoginName AND g.GroupID = 'BRS' order by i.StaffNameLast, i.StaffNameFirst, i.StaffNickname asc";
	$Data = getQueryResult($sql);
	$dataArr = [];
	foreach($Data as $row ){
		$dataArr[] = [$row['LoginName'], getUserFullName($row['LoginName'])];
	}
	return createOptionHtml($dataArr, $default);
}

function findBRADescription() {
	$sql = "SELECT GroupsDescription FROM tblGroups WHERE `ID` = 'BRA'";
	return getOneCell($sql);
}

function getBUHeadList($id) {
	$sql = "SELECT ID, ActionUser, Role, Amount1, Amount2, StatusName from tblPCActFlow where ReferenceID = '$id' and LevelInd = 'BR' AND Sequence = 2 ORDER BY ID";
	$query = getQueryResult($sql);
	if ($query) {
		return $query;
	} else {
		return false;
	}		
}

function getUserTitle($LoginName) {
	$sql = "SELECT tblTitle.TitleName FROM tblTitle, tblStaffInfo WHERE tblStaffInfo.Title = tblTitle.ID AND tblStaffInfo.LoginName = '$LoginName'";
	$query = getOneCell($sql);
	if ($query) {
		return $query;
	} else {
		return false;
	}		
}

function getGroupsDescription($CodeValue) {
	$sql = "SELECT GroupsDescription FROM tblGroups WHERE ID ='$CodeValue'";
	$query = getOneCell($sql);
	if ($query) {
		return $query;
	} else {
		return false;
	}		
}

function getBRRole($back_to_back_order) {
	if($back_to_back_order){
		$OpRole = 'COSRole';
	} else {
		$OpRole = 'OpRole';
	}
	$sql = "SELECT c.CodeCat, c.Sequence ,c.CodeValue, c.Description  FROM tblGenericCode as c WHERE
	CodeType='BRStatus' AND CodeCat = 'CapRole' OR CodeCat = '$OpRole' AND ActiveFlag = 1 ORDER BY Sequence, CodeCat";
	$query = getQueryResult($sql);
	if ($query) {
		foreach($query as $k=>$row) {
			$query[$k]['LowerLimit']=floatval(substr($query[$k]['Description'], 0, strpos($query[$k]['Description'],',')));
			$query[$k]['UpperLimit']=floatval(substr(strrchr($query[$k]['Description'],','),1));
		}
		return $query;
	} else {
		return false;
	}		
}
function ApproveBR($id, $currentstep, $OldBRStatus) {
	global $insightDBLink;
	global $PHP_AUTH_USER;
        
        
	$ActionUser = ActionUserByRegion($id,$currentstep);
	//var_dump($ActionUser);die;
	if($id!="" || $currentstep !='' || $ActionUser){
		/*if($action=="Approve"){
			$StatusName = "YES";
			$StatusName2 = "NO";
			$Description = "Approved by ";
		} else if ($action=="Deny") {
			$StatusName = "NO";
			$StatusName2 = "YES";
			$Description = "Denied by ";
		}*/
		
		
		
		$User = implode("', '", $ActionUser);
		
		$sql = "UPDATE tblPCActFlow SET StatusName ='YES' WHERE LevelInd = 'BR' AND (StatusName = 'NO' OR StatusName = 'On-hold')
		AND Sequence='$currentstep' AND ReferenceID='$id' AND ActionUser IN ('$User')";		
		$query = mysqli_query($insightDBLink, $sql);
		
		if ($query) {
		
			if($currentstep!=11){
				$Action = getActionName($currentstep);
				
				$ActionDesc = $Action;
				if($ActionDesc=="Approve"){
					$ActionDesc = $ActionDesc."d";
				} elseif($ActionDesc=="Verify") {
					$ActionDesc = "Verified";
				} elseif($ActionDesc=="Confirm") {
					$ActionDesc = $ActionDesc."ed";
				}
			} else {
				$Action = "Close";
				$ActionDesc = "Closed";
			}
			$Description = "$ActionDesc by ".getUserFullName($PHP_AUTH_USER);
			createStatusChangeEventLog($id, $PHP_AUTH_USER, $Description, $Action);			
			
			$sql1 = "SELECT COUNT(*) FROM tblPCActFlow WHERE LevelInd = 'BR' AND (StatusName = 'NO' OR StatusName = 'On-hold') AND Sequence='$currentstep' AND ReferenceID='$id'";
			$len = getOneCell($sql1);
			
			if ($len==0) {
				$sql2 = "SELECT MIN(Sequence) FROM tblPCActFlow WHERE LevelInd = 'BR' AND ReferenceID = '$id' AND Sequence>'$currentstep'";
				$nextstep = getOneCell($sql2);
				
				if($nextstep){
					/*$ReqSQL = '';
					//copy Requested budget amounts to Approved budget amounts when budget status changed to Open
					if($nextstep==11){
						$ReqAmountSQL = "SELECT RequestedCapEx, RequestedOpEx FROM tblBRBudget WHERE ID='$id'";
						$ReqAmount = getQueryResult($ReqAmountSQL);
						$ReqSQL = ",ApprovedCapEx = '".$ReqAmount[0]['RequestedCapEx']."', ApprovedOpEx = '".$ReqAmount[0]['RequestedOpEx']."'";
					}*/
					$BRStatus = getBRStatusDescription($nextstep);

					$updatesql = "UPDATE tblBRBudget SET CurrentStep = '$nextstep' , BRStatus='$BRStatus' WHERE ID='$id'";
					mysqli_query($insightDBLink, $updatesql);
					
					/* Disabled on Task 10762, to be launched on next version */
                    
					if($BRStatus == "Open"){
                                               
                                            
                                                //Toni's Task 22069
						//Auto-generate PR			
							
						$GeneratedPR = GeneratePR($id);		
						#echo "Generate PR".$GeneratedPR ;	
						//return false; // 17840 - bug fix not receiving notification email after BR approved.
						// GenerateCT($id);
					 
						
                                                
                                             
					}
					
					// Kavis Task 14165, 29821
					$projectNum = "";
					// find out the project number
					$q = mysqli_query($insightDBLink, "SELECT ProjectNum,ContinueFlag FROM tblBRBudget WHERE ID = '$id'");
					$r = mysqli_fetch_assoc($q);
					$projectNum = $r["ProjectNum"];
					sendEmailStatusChange($id,$PHP_AUTH_USER,$OldBRStatus,$GeneratedPR,NULL,"Budget Approval : ".BRID($id)."_".$BRStatus."_".$projectNum,"Budget Approval : ".BRID($id)."_".$BRStatus."_".$projectNum);

					
				}
			}
			//return $nextstep; 
		} else {
			return false;
		}
	}

}

function DenyBR($id, $currentstep) {
	global $insightDBLink;
	global $PHP_AUTH_USER;
	$ActionUser = ActionUserByRegion($id,$currentstep);
	if($id!="" || $currentstep !='' || $ActionUser){
		$User = implode("', '", $ActionUser);
		
		$sql = "UPDATE tblPCActFlow SET StatusName ='Denied' WHERE LevelInd = 'BR' AND (StatusName = 'NO' OR StatusName = 'On-hold')
		AND Sequence='$currentstep' AND ReferenceID='$id' AND ActionUser IN ('$User')";		
		$query = mysqli_query($insightDBLink, $sql);
		
		$Description = "Denied by ".getUserFullName($PHP_AUTH_USER);
		createStatusChangeEventLog($id, $PHP_AUTH_USER, $Description, "Deny");	
					
		$updatesql = "UPDATE tblBRBudget SET CurrentStep = '1' , BRStatus='Denied' WHERE ID='$id'";
		mysqli_query($insightDBLink, $updatesql);
		
		// Kavis Task 14165
		$projectNum = "";
		// find out the project number
		$q = mysqli_query($insightDBLink, "SELECT ProjectNum FROM tblBRBudget WHERE ID = '$id'");
		$r = mysqli_fetch_assoc($q);
		$r["ProjectNum"] = $projectNum;
		sendEmailStatusChange($id,$PHP_AUTH_USER,"DENY",NULL,NULL,"Budget Approval : ".BRID($id)."_DENY_".$projectNum);
	}
}

function OnholdBR($id,$currentstep) {
	global $insightDBLink;
	global $PHP_AUTH_USER;
	$ActionUser = ActionUserByRegion($id,$currentstep);
	if($id!="" || $currentstep !='' || $ActionUser){
		$User = implode("', '", $ActionUser);
		
		$sql = "UPDATE tblPCActFlow SET StatusName ='On-hold' WHERE LevelInd = 'BR' AND StatusName = 'NO' 
		AND Sequence='$currentstep' AND ReferenceID='$id' AND AND ActionUser IN ('$User')";		
		$query = mysqli_query($insightDBLink, $sql);
	
		$Description = "On-hold by ".getUserFullName($PHP_AUTH_USER);
		createStatusChangeEventLog($id, $PHP_AUTH_USER, $Description, "On-hold");	
				
		$updatesql = "UPDATE tblBRBudget SET BRStatus='On-hold' WHERE ID='$id'";
		mysqli_query($insightDBLink, $updatesql);
		
		// Kavis Task 14165
		$projectNum = "";
		// find out the project number
		$q = mysqli_query($insightDBLink, "SELECT ProjectNum FROM tblBRBudget WHERE ID = '$id'");
		$r = mysqli_fetch_assoc($q);
		$r["ProjectNum"] = $projectNum;
		sendEmailStatusChange($id,$PHP_AUTH_USER,"HOLD",NULL,NULL,"Budget Approval : ".BRID($id)."_HOLD_".$projectNum);
	}
}

function createStatusChangeEventLog($evl_ref_id, $user, $Description, $action){
	global $insightDBLink;
	$eventSql = "INSERT INTO tblPCEventLog SET LevelInd='BR', ReferenceID='".$evl_ref_id."', ".
		  "LogDate='".date('Y-m-d H:i:s')."', LogUser='".$user."', DocType='STATUS', ".
		  "Action='".$action."', Description='".$Description."' ";
	mysqli_query($insightDBLink, $eventSql);

}

function getBRStatusDescription($Sequence) {
	$sql = "SELECT Description from tblGenericCode where CodeType='BRStatus' AND CodeCat='Status' AND ActiveFlag=1 AND Sequence='$Sequence'";
	$query = getOneCell($sql);
	if ($query) {
		return $query;
	} else {
		return false;
	}		
}

function findUOMDesc($value) {
	$sql = "SELECT Description FROM tblGenericCode WHERE CodeValue = '$value'";
	return getOneCell($sql);
}

function getMACost($id) {
	if (!$id) return false;
	
	$sql = "SELECT h.ID, h.OneTimeTC, h.MonthlyTC, (h.OneTimeTC+h.contract_period*h.MonthlyTC) as TotalContractCost, h.contract_period, 
	h.OneTimeRev, h.MonthlyRev, (h.OneTimeRev+h.contract_period*h.MonthlyRev) as TotalContractRevenue , 
	h.OneTimeGM, h.MonthlyGM, h.OneTimeGMP, h.MonthlyGMP
	FROM tblSale AS s, tblCostAnal_Header AS h, tblCostAnal_Details AS d, tblBRBudget AS b, tblBRBudgetLine AS l 
	WHERE b.ID = l.BRID AND h.ID = d.HeaderID AND s.ID = l.SRNum AND s.ID = d.SalesID AND b.ID='$id' AND h.MA_Status != 'C' GROUP BY h.ID";
	$query = getQueryResult($sql);
	if ($query) {
		foreach($query as $k=>$row) {			
			$query[$k]['ID']= "CN".str_pad($query[$k]['ID'], 6, "0", STR_PAD_LEFT);
			
			$query[$k]['TotalContractMargin']=$query[$k]['TotalContractRevenue']-$query[$k]['TotalContractCost'];
			/*
			if ($query[$k]['TotalContractRevenue'] == 0) {
				if ($query[$k]['TotalContractMargin'] < 0) {
					//$total_gmp = formatNumber(-100, 2);
					$total_gmp = -100;
				} else {
					$total_gmp = 0;
				}
			} else {
				//$total_gmp = formatNumber(($query[$k]['TotalContractMargin'] / $query[$k]['TotalContractRevenue']) * 100, 2);
				$total_gmp = ($query[$k]['TotalContractMargin'] / $query[$k]['TotalContractRevenue'])*100;
			}*/
			$query[$k]['TotalContributionMarginPercent'] = CalTotalContributionMargin($query[$k]['TotalContractRevenue'],$query[$k]['TotalContractMargin']);
			
			/*$query[$k]['OneTimeTC'] = formatNumber($query[$k]['OneTimeTC'],2,1);
			$query[$k]['MonthlyTC'] = formatNumber($query[$k]['MonthlyTC'],2,1);
			$query[$k]['OneTimeRev'] = formatNumber($query[$k]['OneTimeRev'],2,1);
			$query[$k]['MonthlyRev'] = formatNumber($query[$k]['MonthlyRev'],2,1);
			$query[$k]['OneTimeGM'] = formatNumber($query[$k]['OneTimeGM'],2,1);
			$query[$k]['MonthlyGM'] = formatNumber($query[$k]['MonthlyGM'],2,1);
			
			$query[$k]['TotalContractCost'] = formatNumber($query[$k]['TotalContractCost'],2,1);
			$query[$k]['TotalContractRevenue'] = formatNumber($query[$k]['TotalContractRevenue'],2,1);
			$query[$k]['TotalContractMargin'] = formatNumber($query[$k]['TotalContractMargin'],2,1);*/
		}
		return $query;
	} else {
		return false;
	}		
}

function getBudgetMargin($id,$MAID){
	if (!$id) return false;
	$MAID = unformatCode($MAID);
	$sql = "SELECT DISTINCT b.ID, b.UnitCost, b.Quantity, b.ContractPeriod, b.Currency, b.ExRate_USD, b.UOM FROM tblBRBudgetLine as b, tblCostAnal_Details as m WHERE b.SRNum = m.SalesID AND b.BRID='$id' AND m.HeaderID = '$MAID'";
	$query = getQueryResult($sql);
	
	if ($query) {
	
		$temp_onetime = 0;
		$temp_monthly = 0;
		
		foreach($query as $k=>$row) {
			
			$UnitCost = $query[$k]['UnitCost'];
			$Quantity = $query[$k]['Quantity'];
			$ContractPeriod = $query[$k]['ContractPeriod'];
			$ExRate_USD = $query[$k]['ExRate_USD'];
			$UOM = $query[$k]['UOM'];
			
			if($ContractPeriod==0){		
				$ContractPeriod = 1;
			}
			
			if($ExRate_USD==0){
				$currencyExchange = getCurrencyExchange();
				$ExRate_USD = $currencyExchange[$query[$k]['Currency']];
			}
			
			$Cost = $Quantity*$UnitCost*$ContractPeriod;
			$USDCost = $Cost*$ExRate_USD;
			
			$temp_unitprice_usd = $UnitCost*$ExRate_USD;
			
			if($UOM){
				$temp_chkMRC=CheckMRC($UOM);
				if($temp_chkMRC>0){
					if($UnitCost) {					
						$temp_unitprice_usd = $temp_unitprice_usd/$temp_chkMRC;
						$temp_monthly += $temp_unitprice_usd; 
						//$temp_contract_monthly += $USDCost; 
					}
				}else {
					$temp_onetime += $USDCost; 
				}
			}
		}
		$result = [formatNumber($temp_onetime,2,0), formatNumber($temp_monthly,2,0)];
		return $result;
	
	} else {
		return false;
	}
}

function CheckMRC($codevalue){
	global $insightDBLink;
	$query = mysqli_query($insightDBLink, "SELECT Sequence FROM `tblGenericCode`  WHERE CodeType='MRCUOM' and CodeValue='$codevalue' and ActiveFlag=1");
	return dbSelectColumnFromQuery($query, 0);
}

function CalMargin($Revenue,$Margin){
	if ($Revenue == 0) {
		if ($Margin < 0) {
			$gmp = -100;
		} else {
			$gmp = 0;
		}
	} else {
		$gmp = ($Margin / $Revenue)*100;
	}
	
	return $gmp;
}

function CalTotalContributionMargin($TotalContractRevenue,$TotalContractMargin){
	if ($TotalContractRevenue == 0) {
		if ($TotalContractMargin < 0) {
			$total_gmp = -100;
		} else {
			$total_gmp = 0;
		}
	} else {
		$total_gmp = ($TotalContractMargin / $TotalContractRevenue)*100;
	}
	
	return $total_gmp;
}

function getEvengLogReason($id,$DocType) {
	$sql = "SELECT Description from tblPCEventLog WHERE LevelInd='BR' AND DocType='$DocType' AND ReferenceID='$id' ORDER BY ID DESC LIMIT 1";
	$query = getOneCell($sql);
	if ($query) {
		return $query;
	} else {
		return false;
	}		
}

function checkEditable($id,$Seq) {
	global $PHP_AUTH_USER;
	$TotalCount = 0;
	$sql = "SELECT COUNT(*) from tblPCActFlow where ReferenceID = '$id' and LevelInd = 'BR' AND Sequence = '$Seq' AND ActionUser = '$PHP_AUTH_USER' ORDER BY Sequence, ID";
	$TotalCount += getOneCell($sql);
	$sql = "SELECT COUNT(*)
			FROM tblPCActFlow a, tblStaffGps g, tblStaffGps s
			WHERE a.LevelInd = 'BR' AND a.ReferenceID = '$id'
			AND a.Sequence = '$Seq' AND a.Filter1 = g.GroupID and a.ActionUser = g.LoginName
			AND s.GroupID = g.GroupID AND (s.Region = g.Region AND s.Region != '' AND s.Region is not null OR s.LoginName = g.LoginName)
			AND s.LoginName = '$PHP_AUTH_USER'";
	$TotalCount += getOneCell($sql);
	if ($TotalCount) {
		return true;
	} else {
		return false;
	}		
}

function ActionUserByRegion($id,$Seq) {
	global $insightDBLink;
	global $PHP_AUTH_USER;
	$sql = "SELECT a.ActionUser
			FROM tblPCActFlow a, tblStaffGps g, tblStaffGps s
			WHERE a.LevelInd = 'BR' AND a.ReferenceID = '$id'
			AND a.Sequence = '$Seq' AND a.Filter1 = g.GroupID and a.ActionUser = g.LoginName
			AND s.GroupID = g.GroupID AND (s.Region = g.Region AND s.Region != '' AND s.Region is not null OR s.LoginName = g.LoginName)
			AND s.LoginName = '$PHP_AUTH_USER'";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) { 
			$user[] = $value["ActionUser"];
		} 
		return $user;
	} else {
		return false;
	}
}

function GeneratePR1($BRID){
	$br_line = findBRLineDetail($BRID);
	// task 12278 - count number of capex item group by vendor
	$Vendor_Capex_CountArr = [];
	foreach ($br_line as $k=>$row) {
		$VendorCodeTemp = $br_line[$k]["VendorCode"];
		$BudgetTypeTemp = $br_line[$k]["BudgetType"];
		if (!array_key_exists($VendorCodeTemp, $Vendor_Capex_CountArr)) {
			if ($BudgetTypeTemp == 'Capex') {
				echo "1if...".$VendorCodeTemp;
				$Vendor_Capex_CountArr[$VendorCodeTemp] = 1;
			} else {
				echo "1else...".$VendorCodeTemp;
				$Vendor_Capex_CountArr[$VendorCodeTemp] = 0;
			}
		} else {
			if ($BudgetTypeTemp == 'Capex') {
				echo "2if...".$VendorCodeTemp;
				$Vendor_Capex_CountArr[$VendorCodeTemp] = $Vendor_Capex_CountArr[$VendorCodeTemp] + 1;
			}
		}
	}
	print_r($Vendor_Capex_CountArr);
	return true;
}
function GenerateCT($BRID) {
	global $insightDBLink;
	$sql = "SELECT l.PrevCostItemID, l.BRID AS BudgetID
				, l.ContractEnd AS TargetTermDate, l.Currency
				, l.ExRate_USD, l.Description
				, l.UOM, l.UnitCost
				, l.Quantity, l.UnitCost * l.Quantity AS TotalAmt
				, l.UnitCost * l.Quantity * l.ExRate_USD AS TotalAmt_USD, l.BudgetType
				, l.ContractEnd < c.EndDate AS EarlyTermFlag, l.CostType
				, l.SignedCo, h.PONum
				, h.LISPONum, h.PODate
				, h.VendorName, h.POLoc
				, h.ShipToLoc, h.ShipToLoc_Add1
				, h.ShipToLoc_Add2, h.ShipToLoc_Add3
				, h.ShipToLoc_Add4, h.ShipToLoc_Add5
				, h.ShipToLoc_Name, c.ItemCode
				, c.GLCode
				, (CASE WHEN (l.BudgetType = 'Opex') THEN  l.UnitCost * l.Quantity * l.ExRate_USD  ELSE 0 END) AS Opex_USD
				, (CASE WHEN (l.BudgetType = 'Capex') THEN  l.UnitCost * l.Quantity * l.ExRate_USD  ELSE 0 END) AS Capex_USD
				FROM tblBRBudgetLine l 
				INNER JOIN tblPCCostItem c ON l.PrevCostItemID = c.ID 
				LEFT JOIN tblPCReqDetail d on d.ID = c.ReqDetailID
				LEFT JOIN tblPCReqHeader h on h.ID = d.ReqID
				WHERE l.BRID =$BRID and l.OrderType = 'TERMINATE' ";			
	$result = getQueryResult($sql);
	foreach ($result as $k => $row) {
		$sql = "INSERT INTO tblPCTermReq SET PrevCostItemID = '$row[PrevCostItemID]'
				, TargetTermDate = '$row[TargetTermDate]', Currency = '$row[Currency]'
				, ExRate_USD = '$row[ExRate_USD]', ItemCode = '$row[ItemCode]'
				, Description = '$row[Description]', UOM = '$row[UOM]'
				, UnitCost = '$row[UnitCost]', Quantity = '$row[Quantity]'
				, TotalAmt = '$row[TotalAmt]', TotalAmt_USD = '$row[TotalAmt_USD]'
				, PONum = '$row[PONum]', LISPONum = '$row[LISPONum]'
				, PODate = '$row[PODate]', VendorName = '$row[VendorName]'
				, POLoc = '$row[POLoc]', ShipToLoc = '$row[ShipToLoc]'
				, ShipToLoc_Add1 = '$row[ShipToLoc_Add1]', ShipToLoc_Add2 = '$row[ShipToLoc_Add2]'
				, ShipToLoc_Add3 = '$row[ShipToLoc_Add3]', ShipToLoc_Add4 = '$row[ShipToLoc_Add4]'
				, ShipToLoc_Add5 = '$row[ShipToLoc_Add5]', ShipToLoc_Name = '$row[ShipToLoc_Name]'
				, GLCode = '$row[GLCode]', BudgetID = '$row[BudgetID]'
				, Capex_USD = '$row[Capex_USD]', Opex_USD = '$row[Opex_USD]'
				, BudgetType = '$row[BudgetType]', EarlyTermFlag = '$row[EarlyTermFlag]' 
				, BillingPeriod = '0' 
				, InternalPOFlag = '1' 
				, POFlag = '1' 
				, CreateUser = 'insight' 
				, CreateDate = NOW() 
				, LastUpdUser = 'insight' 
				, LastUpdDate = NOW() 
				, CurrentStep = '1' 
				, BudgetAppvFlag = '1'	 ";

		$query = mysqli_query($insightDBLink, $sql);
		$ct_id = mysqli_insert_id($insightDBLink);
		
		$requestersql = "SELECT ActionUser FROM tblPCActFlow WHERE LevelInd = 'BR' AND ReferenceID = '$BRID' AND Sequence=1";
		$Requester = getOneCell($requestersql);
		
		$Approver = getPRRenewApprover('PCA',$row["CostType"]);
		$Buyer = getPRRenewApprover('PCB',$row["SignedCo"]);
		
		$Actsql = "INSERT INTO tblPCActFlow (LevelInd, ReferenceID, Sequence, Role, ActionUser, StatusName) VALUES
		('TRReq', $ct_id, '1', 'Requester', '$Requester', 'Saved'),
		('TRReq', $ct_id, '2', 'Buyer', '$Buyer', 'Awaiting Verification'),
		('TRReq', $ct_id, '3', 'Approver', '$Approver', 'Awaiting Approval'),
		('TRReq', $ct_id, '4', 'Buyer', '$Buyer', 'Awaiting Processing'),
		('TRReq', $ct_id, '5', '', '', 'Processed') ";
		$query = mysqli_query($insightDBLink, $Actsql);
	}
}
function GeneratePR($BRID){
	global $insightDBLink;
	$current_dt = date("Y-m-d H:i:s");
	$br_head = getBRHeader($BRID); 
	$br_line = findBRLineDetail($BRID);
	
	$requestersql = "SELECT ActionUser FROM tblPCActFlow WHERE LevelInd = 'BR' AND ReferenceID = '$BRID' AND Sequence=1";
	$user = getOneCell($requestersql);
	
	$RelatedItemSQL = "SELECT * FROM tblPCEventLog WHERE LevelInd = 'BR' AND DocType != 'STATUS' 
						AND Filter1 != '' AND Filter1 IS NOT NULL AND ReferenceID = '$BRID' ORDER BY LogDate ASC";
	$RelatedItemEventLog = getQueryResult($RelatedItemSQL);
	
	$PRDocType = getPRDocType();
	
	$CopiedEventLog = [];
	$GeneratedPR = [];

	// task 12278 - count number of capex item group by vendor
	$Vendor_Capex_CountArr = [];
	foreach ($br_line as $k=>$row) {
		$VendorCodeTemp = $br_line[$k]["VendorCode"];
		$BudgetTypeTemp = $br_line[$k]["BudgetType"];
		if (!array_key_exists($VendorCodeTemp, $Vendor_Capex_CountArr)) {
			if ($BudgetTypeTemp == 'Capex') {
				$Vendor_Capex_CountArr[$VendorCodeTemp] = 1;
			} else {
				$Vendor_Capex_CountArr[$VendorCodeTemp] = 0;
			}
		} else {
			if ($BudgetTypeTemp == 'Capex') {
				$Vendor_Capex_CountArr[$VendorCodeTemp] = $Vendor_Capex_CountArr[$VendorCodeTemp] + 1;
			}
		}
	}
	//print_r($Vendor_Capex_CountArr);
	reset($br_line);
	
	$VendorCode = '';
	$NewVendorName = '';
	$CostType = '';
	$SignedCo = '';
	$Currency = '';
	$TotalAmt = 0;
	$Capex_USD = 0;
	$Opex_USD = 0;
	
	$PrevAutoSubmitFlag = false;

	foreach ($br_line as $k=>$row) { 
		$VendorCodeTemp = $br_line[$k]["VendorCode"];
		$NewVendorNameTemp = $br_line[$k]["NewVendorName"];
		$CostTypeTemp = $br_line[$k]["CostType"];
		$SignedCoTemp = $br_line[$k]["SignedCo"];
		$CurrencyTemp = $br_line[$k]["Currency"];
                
            

		$GenValue = checkAutoGen($VendorCodeTemp,$CostTypeTemp);
                
              
		//$curCapexCnt = $Vendor_Capex_CountArr[$VendorCodeTemp];
		//echo "GenValue...".$GenValue."VendorCode...".$VendorCode."VendorCodeTemp...".$VendorCodeTemp."curCapexCnt...".$curCapexCnt;
		if($GenValue=='AutoGen' || $GenValue=='AutoSubmit'){
					//echo "GenValue...".$GenValue."</br>";
                        
				 
			if($VendorCode!=$VendorCodeTemp || $NewVendorName!=$NewVendorNameTemp || $CostType!=$CostTypeTemp || $SignedCo!=$SignedCoTemp || $Currency!=$CurrencyTemp){
			//echo "In 1st if..."."</br>";
				//submit threshold = USD 100K
				//if ($PrevAutoSubmitFlag) { $a = "true"; } else { $a = "false"; }
				//echo "1st...PrevGenValue...".$PrevGenValue."PrevAutoSubmitFlag...".$PrevAutoSubmitFlag."TotalAmt...".$TotalAmt."PrevPRID...".$PrevPRID;
				//echo "1st...GenValue...".$GenValue."VendorCode...".$VendorCode."VendorCodeTemp...".$VendorCodeTemp."curCapexCnt...".$curCapexCnt;
				if($PrevGenValue=='AutoSubmit' && $PrevAutoSubmitFlag && $TotalAmtUSD < 100000 && $PrevPRID>0){
					// Joan : task 12278 If there is capex type BR item, do not auto-submit PR
					//echo "1st***";
					$curCapexCnt = $Vendor_Capex_CountArr[$VendorCode];
					//echo "capexcnt...".$curCapexCnt;
					if ($curCapexCnt == 0) {
					//echo "going to submit 1st time...".$PrevPRID;
					
						
						 
					if($br_line[$k]["OrderType"]=='CONTINUE'){
							//echo "***1";
							continuePR($BRID,$PrevPRID,$CostType,$SignedCo,$current_dt,$Capex_USD,$Opex_USD);
					}
					else{
						submitPR($BRID,$PrevPRID,$CostType,$SignedCo,$current_dt,$Capex_USD,$Opex_USD);
					}
					//echo "end submit 1st time...".$PrevPRID;	
					}
				}
				
				$PrevAutoSubmitFlag = true;
				
				$Capex_USD = 0;
				$Opex_USD = 0;
				
				$CopiedEventLog = [];
				
				if($VendorCodeTemp){
					$VendorName = findVendorName($VendorCodeTemp);
					$VendorType = "'".findVendorType($VendorCodeTemp)."'";
				} else {
					$VendorName = $NewVendorNameTemp;
					$VendorType = "NULL";
				}
				
				$TotalAmt = 0;
				
				$sql = "INSERT INTO tblPCReqHeader SET ";
				$sql .= "VendorCode = '$VendorCodeTemp'";
				$sql .= ", CostType = '$CostTypeTemp'";
				$sql .= ", SignedCo = '$SignedCoTemp'";			
				$sql .= ", Currency = '$CurrencyTemp'";
				//$sql .= ", ExRate_USD = '".$br_line[$k]["ExRate_USD"]."'";
				//$sql .= ", VendorType = '".findVendorType($inv_head["VendorCode"])."'";			
				$sql .= ", Description = '".mysqli_real_escape_string($insightDBLink, $br_head["Description"])."'";
				$sql .= ", VendorName = '".mysqli_real_escape_string($insightDBLink, $VendorName)."'"; 	
				$sql .= ", VendorType = $VendorType";				
				//$sql .= ", UrgentFlag = 1"; 
				$sql .= ", POFlag = 0";			
				$sql .= ", CurrentStep = 1";				
				$sql .= ", CreateUser = 'insight'"; 
				$sql .= ", CreateDate = '$current_dt'";
				$sql .= ", LastUpdUser = 'insight'";
				$sql .= ", LastUpdDate = '$current_dt'";
				$sql .= ", Justification = '".mysqli_real_escape_string($insightDBLink, $br_head["Justification"])."'";
				$sql .= ", BudgetID = '$BRID'";
				$sql .= ", CreateSource = 'BR'";			
				$query = mysqli_query($insightDBLink, $sql);
				$pr_id = mysqli_insert_id($insightDBLink);
				//$sqltmp .= $sql."<br><br>";
                                
                               
				
				$GeneratedPR[] = PRID($pr_id);
				
				$Actsql = "INSERT INTO tblPCActFlow (LevelInd, ReferenceID, Sequence, Role, ActionUser, StatusName) 
				VALUES ('PRReq', '$pr_id', 1, 'Requester', '$user', 'Saved'), 
				('PRReq', '$pr_id', 2, 'Buyer', '', 'Awaiting Verification'), 
				('PRReq', '$pr_id', 3, 'Approver', '', 'Awaiting Approval'), 
				('PRReq', '$pr_id', 4, 'Buyer', '', 'Awaiting Processing'), 
				('PRReq', '$pr_id', 5, '', '', 'Processed'), 
				('PRReq', '$pr_id', 6, 'Buyer2', '', 'Awaiting 2nd Processor Verification'),
				('PRReq', '$pr_id', 14, 'Approver1', '', 'Awaiting 1st Approver Approval')"; 
				$query = mysqli_query($insightDBLink, $Actsql);
				if($br_line[$k]["OrderType"]=='CONTINUE'){
					$EventLogsql = "INSERT INTO tblPCEventLog (LevelInd, ReferenceID, LogDate, LogUser, DocType, Action, Description, Attachment) 
					VALUES ('ReqHeader', '$pr_id', '$current_dt', 'auto', 'STATUS', 'Save', 'Newly Created by Continue BR:".$BRID."', NULL)";
				}else{
					$EventLogsql = "INSERT INTO tblPCEventLog (LevelInd, ReferenceID, LogDate, LogUser, DocType, Action, Description, Attachment) 
					VALUES ('ReqHeader', '$pr_id', '$current_dt', 'insight', 'STATUS', 'Save', 'Newly Created', NULL)";
				}
				$query = mysqli_query($insightDBLink, $EventLogsql);				
				
				$line_num = 0;
			} else {
				//$line_num++;
			}		
			
			//Copy Related Item EventLog
			$LineNum = $br_line[$k]["LineNum"];				
			
			foreach ($RelatedItemEventLog as $j=>$Erow) {
				$RelatedItem = $RelatedItemEventLog[$j]["Filter1"];
				$RelatedArr = range_string($RelatedItem);
				
				$ELID = $RelatedItemEventLog[$j]["ID"];
				$DocType = $RelatedItemEventLog[$j]["DocType"];
				
				
				if (in_array($LineNum, $RelatedArr) && !in_array($ELID, $CopiedEventLog)) {
					$AttachmentPath = $RelatedItemEventLog[$j]["Attachment"];
					// Joan : task 17840 bug fix - need to save PR attachment in absolute path (ie. uploadDoc/yyyy/mm/filename).
					$AttachmentName = substr($AttachmentPath, (strrpos($AttachmentPath, '/')+1));
					$OriginalName = $AttachmentName;
					if($AttachmentName){
						$AttachmentName = "uploadDoc/".$AttachmentPath;
					} else {
						$AttachmentName = "NULL";
					}
					
					if(!in_array($DocType, $PRDocType)){
						$DocType = 'OTHER';
					}

					$EventLogsql = "INSERT INTO tblPCEventLog (LevelInd, ReferenceID, LogDate, LogUser, DocType, Action, Description, Attachment, Original_file) 
					SELECT 'ReqHeader', '$pr_id', '$current_dt', 'insight', '$DocType', Action, Description, '$AttachmentName', '$OriginalName' FROM tblPCEventLog WHERE ID = '$ELID'";
					$query = mysqli_query($insightDBLink, $EventLogsql);
					if($AttachmentPath){
						//Copy EventLog File
						// Joan : task 17840 bug fix - need to save PR attachment in absolute path (ie. uploadDoc/yyyy/mm/filename). 
						//copyBRFileToPR($AttachmentPath,$pr_id);
						copyBRFileToPR1($AttachmentName);
					}
					$CopiedEventLog[] = $ELID;
				}
			}
			
			$WONum = $br_line[$k]["WONum"];
			$UOM = $br_line[$k]["UOM"];
			$BillingPeriod = BillingPeriodByUOM($UOM);
			
			//if UOM = MM and CostType like ‘COS%’ or CostType like ‘NET%’, then ItemCode = ‘MRC’ otherwise ItemCode = ‘NRC’
			if($UOM=="MM" && (str_starts_with($CostTypeTemp, "COS") || str_starts_with($CostTypeTemp, "NET")) ){
				$ItemCode = "MRC";
			} else {
				$ItemCode = "NRC";
			}
									
			$PrevCostItemID = $br_line[$k]["PrevCostItemID"];
			/*if($PrevCostItemID>0){
				$OrderType = "RENEW";				
			} else {
				$OrderType = "NEW_SERVIC";
			}*/
			$OrderType = $br_line[$k]["OrderType"];
			
			//if SRNum != '', get GLCode from tblPCReqGLAccountCode, otherwise get GLCode from tblRefGLCode
			$SRNum = $br_line[$k]["SRNum"];
			/*if($SRNum != ''){
				// Joan - new condition to check capex flag also (task 12278)
				if ($br_line[$k]["BudgetType"] == 'Opex')
					$GLCode = getPCReqGLCode($SRNum);
				else
					$GLCode = getRefGLCode($br_line[$k]["SignedCo"],$CostTypeTemp,$br_line[$k]["CostCat"],$br_line[$k]["CostSubcat"],$br_line[$k]["BudgetType"]);
			} else {
				$GLCode = getRefGLCode($br_line[$k]["SignedCo"],$CostTypeTemp,$br_line[$k]["CostCat"],$br_line[$k]["CostSubcat"],$br_line[$k]["BudgetType"]);
			}*/			

			if ($br_line[$k]["BudgetType"] == 'Capex'){ // task : 12278
				$GLCode = getRefGLCode($br_line[$k]["SignedCo"],$CostTypeTemp,$br_line[$k]["CostCat"],$br_line[$k]["CostSubcat"],$br_line[$k]["BudgetType"]);
			} else {
				$pos = strpos($CostTypeTemp,"COS");
				if ($pos !== false) { // Direct Cost
					if ($SRNum != "") {
						$GLCode = getPCReqGLCode($SRNum);
					} else {
						$GLCode = getRefGLCode($br_line[$k]["SignedCo"],$CostTypeTemp,$br_line[$k]["CostCat"],$br_line[$k]["CostSubcat"],$br_line[$k]["BudgetType"]);
					}
				} else {
					$GLCode = getRefGLCode($br_line[$k]["SignedCo"],$CostTypeTemp,$br_line[$k]["CostCat"],$br_line[$k]["CostSubcat"],$br_line[$k]["BudgetType"]);
				}
			}
	
			//if contract period != 0 or contract period != 1, user contract period as quantity, split PR lines by BR quantity (round down to integer)
			$ContractPeriod = $br_line[$k]['ContractPeriod'];
			
			if($OrderType == "CONTINUE"){
				if($BillingPeriod){
					$len = floor($br_line[$k]["Quantity"]);
					$Quantity = $ContractPeriod;
				} else {
					$len = 1;
					$Quantity = $br_line[$k]["Quantity"];
				}
			}else{
				if($ContractPeriod !=0 && $ContractPeriod !=1){
				//if($BillingPeriod){
					$len = floor($br_line[$k]["Quantity"]);
					$Quantity = $ContractPeriod;
				} else {
					$len = 1;
					$Quantity = $br_line[$k]["Quantity"];
				}
			}
			if($br_head["ProjectNum"]){
				$ProjectNum = $br_head["ProjectNum"];
			} else {
				$ProjectNum = BRID($BRID);
			}
			
			for($qty=0;$qty<$len;$qty++){			
				
				$line_num++;
				
				// insert details
				$sql = "INSERT INTO tblPCReqDetail SET ";
				$sql .= "ReqID = $pr_id";
				$sql .= ", LineNum = $line_num";
				$sql .= ", BudgetItemID = '".$br_line[$k]["ID"]."'";
				$sql .= ", SRNum = '$SRNum'";
				if($GLCode){
					$sql .= ", GLCode = '$GLCode'"; 
				}				
				$sql .= ", ProjectNum = '$ProjectNum'";
				$sql .= ", CostCat = '".$br_line[$k]["CostCat"]."'";
				$sql .= ", CostSubcat = '".$br_line[$k]["CostSubcat"]."'";
				$sql .= ", Nomenclate = '".$br_line[$k]["Nomenclate"]."'";
				$sql .= ", Description = '".mysqli_real_escape_string($insightDBLink, $br_line[$k]["Description"])."'";
				$sql .= ", UOM = '$UOM'";
				$sql .= ", BillingPeriod = '$BillingPeriod'";
				$sql .= ", ItemCode = '$ItemCode'";
				$sql .= ", UnitCost = '".$br_line[$k]["UnitCost"]."'";
				$sql .= ", Quantity = '$Quantity'";		
				$sql .= ", StartDate = '".$br_line[$k]["ContractStart"]."'";
				$sql .= ", EndDate = '".$br_line[$k]["ContractEnd"]."'";
				$sql .= ", AendAddr = '".mysqli_real_escape_string($insightDBLink, $br_line[$k]["FromAddress1"]." ".$br_line[$k]["FromAddress2"]." ".$br_line[$k]["FromAddress3"]." ".findCityDesc($br_line[$k]['FromCity'])." ".findCountryDesc($br_line[$k]['FromCountry']))."'";
				$sql .= ", BendAddr = '".mysqli_real_escape_string($insightDBLink, $br_line[$k]["ToAddress1"]." ".$br_line[$k]["ToAddress2"]." ".$br_line[$k]["ToAddress3"]." ".findCityDesc($br_line[$k]['ToCity'])." ".findCountryDesc($br_line[$k]['ToCountry']))."'";
				$sql .= ", CCC = '".$br_line[$k]["CCC"]."'";
				$sql .= ", WONum = '".mysqli_real_escape_string($insightDBLink, $WONum)."'";			
				$sql .= ", OrderType = '$OrderType'";	
				$sql .= ", PrevCostItemID = '$PrevCostItemID'";
				// Joan - update tblPCReqDetail.BudgetType (task 12278)
				$sql .= ", BudgetType = '".$br_line[$k]["BudgetType"]."'";
				$sql .= ", CostSaving = '".$br_line[$k]["CostSaving"]."'";
				
				//if PrevCostItemID > 0 then copy TermNoticeDay, Make, ModelNum, PartNum, PopID from old cost item
				//copy previous PR PO location, ship-to location, ship-to address, and term code
				if($PrevCostItemID>0){
					$PrevCostItemInfo = getCostItemInfo($PrevCostItemID);
					$sql .= ", TermNoticeDay = '".$PrevCostItemInfo[0]["TermNoticeDay"]."'";
					$sql .= ", Make = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["Make"])."'";
					$sql .= ", ModelNum = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ModelNum"])."'";
					$sql .= ", PartNum = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["PartNum"])."'";
					$sql .= ", PopID = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["PopID"])."'";
					
					if($GLCode==""){
						$sql .= ", GLCode = '".unformatAccCode($PrevCostItemInfo[0]["GLCode"])."'"; 
					}
					
					$Updatesql2 = ",POLoc = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["POLoc"])."', ShipToLoc = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc"])."', ShipToLoc_Add1 = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc_Add1"])."', ShipToLoc_Add2 = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc_Add2"])."', ShipToLoc_Add3 = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc_Add3"])."', ShipToLoc_Add4 = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc_Add4"])."', ShipToLoc_Add5 = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc_Add5"])."', ShipToLoc_Name = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc_Name"])."', ShipToLoc_City = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc_City"])."', ShipToLoc_State = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc_State"])."', ShipToLoc_Zip = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["ShipToLoc_Zip"])."', TermsCode = '".mysqli_real_escape_string($insightDBLink, $PrevCostItemInfo[0]["TermsCode"])."'";
					
					if($GenValue=='AutoSubmit'){
						$CISigedCo = $PrevCostItemInfo[0]["SignedCo"];
						$CIVendorCode = $PrevCostItemInfo[0]["VendorCode"];
					}
				} else {
					$Updatesql2 = "";
				}
				
				$query = mysqli_query($insightDBLink, $sql);
				$pr_line_id = mysqli_insert_id($insightDBLink);
				
				$TotalCost = ($Quantity*$br_line[$k]['UnitCost']);
				$TotalAmt += $TotalCost;
				$ExRate_USD =  $br_line[$k]['ExRate_USD'];
				$TotalAmtUSD = $ExRate_USD*$TotalAmt;
				
				$TotalCostUSD = $ExRate_USD*$TotalCost;
				
				$Updatesql = "UPDATE tblPCReqHeader SET TotalAmt = $TotalAmt , TotalAmt_USD = $TotalAmtUSD , ExRate_USD = $ExRate_USD $Updatesql2 WHERE ID = $pr_id LIMIT 1";
				$query = mysqli_query($insightDBLink, $Updatesql);
				
				if($BudgetType=="Capex"){
					$Capex_USD += $TotalCostUSD;
				} else {
					$Opex_USD += $TotalCostUSD;
				}
			}
			//echo "Gen:" .$GenValue;
			//additional check for Auto Submit
			if($GenValue=='AutoSubmit'){
				
				$ChkSignedCo = validateValue("SELECT COUNT(*) FROM tblRegisteredCo WHERE Active_Purchase=1 AND ID = '".$br_line[$k]["SignedCo"]."'");
				$ChkCostType = validateValue("SELECT COUNT(*) FROM tblGenericCode WHERE ActiveFlag = 1 AND CodeType = 'CostType' AND CodeValue = '$CostTypeTemp'");
				$ChkCurrency = validateValue("SELECT COUNT(*) FROM tblGenericCode WHERE ActiveFlag = 1 AND CodeType = 'CurrCode' AND CodeValue = '$CurrencyTemp'");
				$ChkUOM = validateValue("SELECT COUNT(*) FROM tblGenericCode WHERE ActiveFlag = 1 AND CodeType = 'UOM' AND CodeValue = '$UOM'");
				$ChkCCC = validateValue("SELECT COUNT(*) FROM tblCCC WHERE Active_Cost =1 AND CCC = '".$br_line[$k]["CCC"]."'");
				$ChkItemCat = validateValue("SELECT COUNT(*) FROM tblGenericCode WHERE ActiveFlag = 1 AND CodeType = 'CostCat' AND CodeValue = '".$br_line[$k]["CostCat"]."'");
				$ChkItemSubCat =  validateValue("SELECT COUNT(*) FROM tblGenericCode WHERE ActiveFlag = 1 AND CodeType = 'CostSubcat' AND CodeCat = '".$br_line[$k]["CostCat"]."' AND CodeValue = '".$br_line[$k]["CostSubcat"]."'");
				$ChkShipToLoc = validateValue("SELECT COUNT(*) FROM tblPCReqHeader WHERE ShipToLoc != '' AND ShipToLoc IS NOT NULL AND ID = $pr_id");
				// Joan : task 12278 bug fix - exclude Old Cost Item checking if Action Type is New Goods or New Services
				if (($OrderType == 'NEW_GOODS') || ($OrderType == 'NEW_SERVIC')) {
					$ChkPrevCostItemID = true;
				} else {
					$ChkPrevCostItemID = validateValue("SELECT COUNT(*) FROM tblPCCostItem WHERE ItemStatus = 'Active' AND OccupyFlag = 0 AND ID = '$PrevCostItemID'");
				}
				
				//Nomenclate validation
				if($br_line[$k]["Nomenclate"]==""){
					$countNomeList = validateValue("SELECT COUNT(*) FROM tblGenericCode a left join tblGenericCode b ON a.CodeValue = b.CodeType WHERE a.CodeType = 'NomenGrp' AND a.CodeCat = '".$br_line[$k]["CostSubcat"]."' AND a.ActiveFlag = 1 AND b.ActiveFlag = 1");
					if($countNomeList && $OrderType!="CONTINUE"){
						$ChkNome = false;
					} else {
						$ChkNome = true;
					}
				} else {
					$ChkNome = validateValue("SELECT COUNT(*) FROM tblGenericCode a left join tblGenericCode b ON a.CodeValue = b.CodeType WHERE a.CodeType = 'NomenGrp' AND a.CodeCat = '".$br_line[$k]["CostSubcat"]."' AND a.ActiveFlag = 1 AND b.ActiveFlag = 1 AND b.CodeValue='".$br_line[$k]["Nomenclate"]."'");
				}
								
				//Quantity validation
				if($Quantity>0 || $ContractPeriod >0){
					$ChkQty = true;
				} else {
					$ChkQty = false;
				}
				
				//Order Type validation
				if($OrderType=="RENEW" || $OrderType=="UPGRADE" || $OrderType=="DOWNGRADE" ||$OrderType=="RELOCATE"){
					if($SignedCoTemp==$CISigedCo && $VendorCodeTemp == $CIVendorCode){
						$ChkOrderType = true;
					} else {
						$ChkOrderType = false;
					}
				} elseif ($OrderType=="ASSIGNMENT"){					
					$VendorGp = getVendorGroup($VendorCodeTemp);
					$CIVendorGp = getVendorGroup($CIVendorCode);
					
					if(($SignedCoTemp!=$CISigedCo || $VendorCodeTemp != $CIVendorCode) && $VendorGp==$CIVendorGp){
						$ChkOrderType = true;
					} else {
						$ChkOrderType = false;
					}
				} elseif ($OrderType=="REPLACE"){
					$VendorGp = getVendorGroup($VendorCodeTemp);
					$CIVendorGp = getVendorGroup($CIVendorCode);
					
					if($VendorCodeTemp != $CIVendorCode && $VendorGp != $CIVendorGp){
						$ChkOrderType = true;
					} else {
						$ChkOrderType = false;
					}
				} elseif ($OrderType!="") {
					$ChkOrderType = true;
				} else {
					$ChkOrderType = false;
				}
				
				//WO# validation
				$BudgetType = $br_line[$k]['BudgetType'];
				$ChkWONum = checkWONum($BudgetType,$GLCode,$WONum,$br_line[$k]["SignedCo"]);				
				
				// Joan : task 12278 new add checking on PR Buyer and Approver validation
				if (getPRRenewApprover('PCB',$br_line[$k]["SignedCo"]) != "") {
					$ChkBuyer = true;
				} else {
					$ChkBuyer = false;
				}
				if (getPRRenewApprover('PCA',$CostTypeTemp) != "") {
					$ChkApprover = true;
				} else {
					$ChkApprover = false;
				}
				
				$ChkArr = [];
				
				$ChkArr[] = [$ChkSignedCo, "Signed Company"];
				$ChkArr[] = [$ChkCostType, "Cost Type"];
				$ChkArr[] = [$ChkCurrency, "Currency"];
				$ChkArr[] = [$ChkUOM, "UOM"];
				$ChkArr[] = [$ChkCCC, "CCC"];
				$ChkArr[] = [$ChkItemCat, "Item Category"];
				$ChkArr[] = [$ChkItemSubCat, "Item Sub-Category"];
				$ChkArr[] = [$ChkNome, "Nomenclature"];
				$ChkArr[] = [$ChkQty, "Quantity/Contract Period"];
				$ChkArr[] = [$ChkOrderType, "Action Type"];
				$ChkArr[] = [$ChkWONum, "WO#"];
				$ChkArr[] = [$ChkPrevCostItemID, "Old Cost Item ID"];		
				// Joan : task 12278 new add checking on PR Buyer and Approver validation
				$ChkArr[] = [$ChkBuyer, "Buyer"];	
				$ChkArr[] = [$ChkApprover, "Approver"];
				$ChkArr[] = [$ChkShipToLoc, "Ship To Location"];
				
				$InvalidFieldArr = [];
				$AutoSubmitFlag = true;
				// var_dump($ChkArr);
				foreach($ChkArr as $row ){
					if(!$row[0]){
						$AutoSubmitFlag = false;
						$InvalidFieldArr[] = $row[1];
					}
				}
				//var_dump($InvalidFieldArr);
				//add validation log for any invalid result
				//echo "AutoF:" .$AutoSubmitFlag;
				if($AutoSubmitFlag  && $OrderType !='CONTINUE'){
					if($BudgetType=="Capex"){
						$Capex_USD += $TotalCostUSD;
					} else {
						$Opex_USD += $TotalCostUSD;
					}
				} else {
					$EventLogsql = "INSERT INTO tblPCEventLog (LevelInd, ReferenceID, LogDate, LogUser, DocType, Action, Description, Attachment) 
						VALUES ('ReqHeader', '$pr_id', '$current_dt', 'insight', 'VALIDATE', 'Validation', 'Auto-submit did not performed as the following fields in line $line_num are invalid: ".implode(", ",$InvalidFieldArr).".', NULL)";
						$query = mysqli_query($insightDBLink, $EventLogsql);
				}
				
			}
			
		}
		else {
			$errorAutoSubmit="Auto Generate PR did not performed because vendor status is not Active";
			}
		
		$PrevGenValue = $GenValue;
		$PrevPRID = $pr_id;
		
		if($PrevAutoSubmitFlag && $AutoSubmitFlag){
			$PrevAutoSubmitFlag = true;
		} else {
			$PrevAutoSubmitFlag = false;
		}	
		
		
		$VendorCode = $VendorCodeTemp;
		$NewVendorName = $NewVendorNameTemp;
		$CostType = $CostTypeTemp;
		$SignedCo = $SignedCoTemp;
		$Currency = $CurrencyTemp;
		
		#JM Task 31956 CONTINUE PR IF ACTION TYPE IS CONTINUE#				 	
		if($OrderType=="CONTINUE") {
			$UnitCost= $br_line[$k]['UnitCost'];
			$getCISql = "SELECT ID,UnitCost,VendorCode,SignedCo,Quantity FROM tblPCCostItem WHERE  ID={$PrevCostItemID}";
			$getCIrow = mysqli_fetch_assoc(mysqli_query($insightDBLink, $getCISql));	
			#&&$Quantity==$getCIrow['Quantity']
			if($UnitCost==$getCIrow['UnitCost']&&$VendorCode==$getCIrow['VendorCode']&&$SignedCo==$getCIrow['SignedCo']){
				echo "<b>Continue Ok</b>";
				$continue=1;
			}
			else{
				
				if($UnitCost!=$getCIrow['UnitCost']){
					$errorContinueArr[0]="UnitCost/Price";
				}
			
				if($VendorCode!=$getCIrow['VendorCode']){
							 
						$errorContinueArr[1]="Vendor Code";
				}
				if($SignedCo!=$getCIrow['SignedCo']){
						$errorContinueArr[2]="Signed Company";
				}
				if($ContractPeriod!=$getCIrow['Quantity']){
					#	$errorContinueArr[3]="Term/Quantity";
				}
				  
				$EventLogsql = "INSERT INTO tblPCEventLog (LevelInd, ReferenceID, LogDate, LogUser, DocType, Action, Description, Attachment) 
							VALUES ('ReqHeader', '$pr_id', '$current_dt', 'insight', 'VALIDATE', 'Validation', 'Action Type Continue did not performed as the following fields in line $line_num doesnt match old cost item: ".implode(", ",$errorContinueArr).".', NULL)";
				$query = mysqli_query($insightDBLink, $EventLogsql);
				$errorContinue=1;
			}	 
			
		}
		 
		#JM End ===============================
	
	
	//submit threshold = USD 100K
	if ($PrevAutoSubmitFlag) { $a = "true"; } else { $a = "false"; }
	//echo "%$%$%$%%PrevGenValue...".$PrevGenValue."PrevAutoSubmitFlag...".$a."TotalAmt...".$TotalAmt."PrevPRID...".$PrevPRID;
	//echo "2nd...GenValue...".$GenValue."VendorCode...".$VendorCode."VendorCodeTemp...".$VendorCodeTemp."curCapexCnt...".$curCapexCnt;
	//echo "Before 2nd submit..."."</br>";
	//echo ($continue==1) ."B". ($TotalAmt) ."C". ($PrevPRID>0);
	#JM Task 31956 CONTINUE PR IF ACTION TYPE IS CONTINUE#	
	if($continue==1&& $TotalAmtUSD < 100000 && $PrevPRID>0){
		if(!$errorContinue){
			
				echo "<br><b>Continue Process PR: $PrevPRID</b><br>Total PR Amount is: ".$TotalAmt . " capex: " .$Capex_USD. " opex: " .$Opex_USD; 
			 continuePR($BRID,$PrevPRID,$CostType,$SignedCo,$current_dt,$Capex_USD,$Opex_USD);
			 echo "after<br><b>Continue Process PR: $PrevPRID</b><br>Total PR Amount is: ".$TotalAmt . " capex: " .$Capex_USD. " opex: " .$Opex_USD; 
		} 

	
			
		$getCIsql=" SELECT * FROM tblBRBudgetLine WHERE BRID ={$BRID}";
			$getCIsql=mysqli_query($insightDBLink, $getCIsql);
			while($cirow=mysqli_fetch_array($getCIsql)){
				
				if($cirow['OrderType']!="CONTINUE"){
					$notContinue=1;
				}			
			}
	}
	if($errorContinue){
		 echo "<center> Action Type Continue did not performed because BR CostItem did not match old cost item: ".implode(", ",$errorContinueArr)."</center>";
	}
	elseif($errorAutoSubmit&&!$notContinue&&$continue){
		echo "<center>(
		".$errorAutoSubmit.") </center>";
		$EventLogsql = "INSERT INTO tblPCEventLog (LevelInd, ReferenceID, LogDate, LogUser, DocType, Action, Description, Attachment) 
							VALUES ('BR', '$BRID', '$current_dt', 'insight', 'VALIDATE', 'Validation','{$errorAutoSubmit}', NULL)";
		$query = mysqli_query($insightDBLink, $EventLogsql);
	}
	
	}
	#=====================================End of Continue Action Type# 	
	if($continue!=1&&$PrevGenValue=='AutoSubmit' && $PrevAutoSubmitFlag && $TotalAmtUSD < 100000 && $PrevPRID>0){
	//echo "In 2nd if..."."</br>";	
	// Joan : task 12278 If there is capex type BR item, do not auto-submit PR
		//echo "2nd...curCapexCnt...".$curCapexCnt;
		$curCapexCnt = $Vendor_Capex_CountArr[$VendorCode];
		//echo "capexcnt...".$curCapexCnt;
		if ($curCapexCnt == 0) {
		//echo "going to submit 2nd time...".$PrevPRID;
			submitPR($BRID,$PrevPRID,$CostType,$SignedCo,$current_dt,$Capex_USD,$Opex_USD);
		//echo "end submit 2nd time...".$PrevPRID;
		}
	}
	
					$q="SELECT ProjectNum,ContinueFlag FROM tblBRBudget WHERE ID = '$BRID'";
					$q = mysqli_query($insightDBLink, $q);
					$r = mysqli_fetch_assoc($q);
					if($r["ContinueFlag"]==1){
						reCalculateBR($BRID);
						header("Location: CI_Generator.php?brid=$BRID");	
					}
					else{
						 return $GeneratedPR;
					}
	
}

#JM Task Continue======================================
function continuePR($BRID,$PrevPRID,$CostType,$SignedCo,$current_dt,$Capex_USD,$Opex_USD){
	global $insightDBLink;
	//update GSP
	$sql = "SELECT max(PrevCostItemID) AS CIID FROM tblPCReqDetail WHERE ReqID = '$PrevPRID' AND PrevCostItemID>0";
	$q = mysqli_query($insightDBLink, $sql);
	$r = mysqli_fetch_assoc($q);
	$oldCIID = $r["CIID"];
	$sql = "SELECT GSPFlag, GSPStaff FROM tblPCReqHeader h, tblPCReqDetail d, tblPCCostItem ci where ci.id = $oldCIID and ci.ReqDetailID = d.id and d.ReqID = h.id";
	$q = mysqli_query($insightDBLink, $sql);
	$r = mysqli_fetch_assoc($q);
	$GSPFlag = $r["GSPFlag"];
	$GSPStaff = $r["GSPStaff"];
	
	//update tblPCReqHeader's CurrentStep
	//echo "<B>ContinuePR</b><hr>";
	$sql = "SELECT Capex_USD, Opex_USD FROM tblPCReqHeader h where ID = '$PrevPRID'";
	$q = mysqli_query($insightDBLink, $sql);
	$r = mysqli_fetch_assoc($q);
	$Capex_USD += $r["Capex_USD"];
	$Opex_USD += $r["Opex_USD"];
	
	$Actsql = "UPDATE tblPCReqHeader SET GSPFlag= '$GSPFlag', GSPStaff = '$GSPStaff', CurrentStep =5, Capex_USD='$Capex_USD', Opex_USD = '$Opex_USD', RequestDate = '$current_dt' WHERE ID = '$PrevPRID' LIMIT 1";
	$query = mysqli_query($insightDBLink, $Actsql);	
	//echo "<br>";
	//update tblPCActFlow
	$Approver = getPRRenewApprover('PCA',$CostType);
	//echo $Actsql = "UPDATE tblPCActFlow SET ActionUser = '$Approver' WHERE LevelInd = 'PRReq' AND ReferenceID = '$PrevPRID' AND Sequence = 3";
  //Change the PR approver from ‘Insight’ to ‘Auto’ 
	$Actsql = "UPDATE tblPCActFlow SET ActionUser = 'auto' WHERE LevelInd = 'PRReq' AND ReferenceID = '$PrevPRID' AND Sequence = 3";
	$query = mysqli_query($insightDBLink, $Actsql);
	//echo "<br>";
	$Buyer = getPRRenewApprover('PCB',$SignedCo);
	//echo $Actsql = "UPDATE tblPCActFlow SET ActionUser = '$Buyer' WHERE LevelInd = 'PRReq' AND ReferenceID = '$PrevPRID' AND Sequence IN (2,4)";
  //Change the PR approver from ‘Insight’ to ‘Auto’ 
	$Actsql = "UPDATE tblPCActFlow SET ActionUser = 'auto' WHERE LevelInd = 'PRReq' AND ReferenceID = '$PrevPRID' AND Sequence IN (2,4)";
	$query = mysqli_query($insightDBLink, $Actsql);
	//echo "<br>";
	$sql = "SELECT COUNT(*) AS Processed FROM tblPCEventLog where ReferenceID = $PrevPRID AND LevelInd = 'ReqHeader' AND Action = 'Process'";
	$q = mysqli_query($insightDBLink, $sql);
	$r = mysqli_fetch_assoc($q);
	
	if($r["Processed"] == 0){
		$EventLogsql = "INSERT INTO tblPCEventLog ( LevelInd , ReferenceID , LogDate , LogUser , DocType, Action , Description , Attachment ) 
				VALUES ('ReqHeader', '$PrevPRID',  '$current_dt', 'auto', 'STATUS', 'Submit', 'Submitted', NULL),
				('ReqHeader', '$PrevPRID',  '$current_dt', 'auto', 'STATUS', 'Verify', 'Verified', NULL),
				('ReqHeader', '$PrevPRID',  '$current_dt', 'auto', 'STATUS', 'Approve', 'Approved', NULL),
				('ReqHeader', '$PrevPRID',  '$current_dt', 'auto', 'STATUS', 'Process', 'Processed', NULL)";			
		$EventLogQuery = mysqli_query($insightDBLink, $EventLogsql);	
	}
	//echo "<br>";
	//update tblBRBudget's Allocated Amount
	$Amtsql = "UPDATE tblBRBudget SET AllocatedCapEx = AllocatedCapEx + $Capex_USD, AllocatedOpEx = AllocatedOpEx + $Opex_USD WHERE ID = '$BRID' LIMIT 1";
 	$query = mysqli_query($insightDBLink, $Amtsql);
	//	echo "<br>";
	//update OccupyFlag = 1 in tblPCCostItem
	$Actsql = "UPDATE tblPCCostItem SET OccupyFlag = 1 WHERE ID IN (SELECT PrevCostItemID FROM tblPCReqDetail WHERE ReqID = '$PrevPRID' AND PrevCostItemID>0)";
	$query = mysqli_query($insightDBLink, $Actsql);
	//echo "<br>";
	//echo $PrevPRID;
	#SendPRSubmitEmail($PrevPRID);

}

#JM Task Action Type = Continue End======================

function findBRLineDetail($BRID) {
	$sql = "SELECT * FROM tblBRBudgetLine WHERE BRID = '$BRID' AND OrderType != 'TERMINATE' ORDER BY CostType , SignedCo , Currency, VendorCode, NewVendorName, LineNum"; 
	$Data = getQueryResult($sql);
	return $Data;
}

function checkAutoGen($VendorCodeTemp,$CostTypeTemp){
	$CheckVendorStatus = false;
	$CheckAutoGen = false;
	
	//check vendor status must be "A" or new vendor
	if($VendorCodeTemp){
		$sql = "SELECT COUNT(*) FROM tblVendor WHERE EpicorCode = '$VendorCodeTemp' AND vendor_status ='A'";
		$count = getOneCell($sql);
		if ($count) {
			//check vendor PR attribute
			$sql = "SELECT COUNT(*) FROM tblVendor v, tblVendorAttr a WHERE v.ID = a.VendorID AND a.AttributeType = 'VAttrPur' AND AttributeValue != '00' AND v.EpicorCode = '$VendorCodeTemp'";
			$count2 = getOneCell($sql);
			if ($count2>0) {
				return false;
			} else {				
				$CheckVendorStatus = true;
			}
		} else {
			return false;
		}
	} else {
		$CheckVendorStatus = true;
	}	
	
	if($CostTypeTemp){
		//check auto-gen or auto-submit flag by cost type
		//$sql = "SELECT COUNT(*) FROM tblGenericCode WHERE CodeType='CIRenew' AND CodeCat='GenPR' 
		//		AND (Description='AutoGen' OR Description='AutoSubmit')	AND CodeValue='$CostTypeTemp' AND ActiveFlag='1'";
		$sql = "SELECT Description FROM tblGenericCode WHERE CodeType='CIRenew' AND CodeCat='GenPR' 
				AND (Description='AutoGen' OR Description='AutoSubmit')	AND CodeValue='$CostTypeTemp' AND ActiveFlag='1' ORDER BY Description DESC LIMIT 1";
		$GenValue = getOneCell($sql);
		if ($GenValue=='AutoGen' || $GenValue=='AutoSubmit') {
			$CheckAutoGen = true;
		} else {
			return false;
		}
	} else {
		return false;
	}
	
	if($CheckVendorStatus && $CheckAutoGen){
		return $GenValue;
	} else {
		return false;
	}
	
}

//if UOm in MRCUOM, BillingPeriod = MRCUOM’s Sequence, otherwise BillingPeriod = 0
function BillingPeriodByUOM($UOM){
	$sql = "SELECT Sequence FROM `tblGenericCode` WHERE CodeType='MRCUOM' and CodeValue='$UOM' and ActiveFlag=1";
	$BillingPeriod = getOneCell($sql);
	if ($BillingPeriod) {
		return $BillingPeriod;
	} else {
		return 0;
	}	
}

function getCostItemInfo($CostItemID) {
	$sql = "SELECT c.SignedCo, c.VendorCode, c.GLCode, c.TermNoticeDay, c.Make, c.ModelNum, c.PartNum, c.PopID, r.POLoc, r.ShipToLoc, r.ShipToLoc_Add1, r.ShipToLoc_Add2, r.ShipToLoc_Add3, r.ShipToLoc_Add4, r.ShipToLoc_Add5, r.ShipToLoc_Name, r.ShipToLoc_City, r.ShipToLoc_State, r.ShipToLoc_Zip, r.TermsCode  
			FROM tblPCCostItem AS c
			LEFT JOIN tblPCReqDetail d ON d.ID = c.ReqDetailID
			LEFT JOIN tblPCReqHeader AS r ON d.ReqID = r.ID
			WHERE c.ID = '$CostItemID' LIMIT 1";
	$Data = getQueryResult($sql);
	return $Data;
}

function getRefGLCode($SignedCo,$CostType,$CostCat,$CostSubcat,$BudgetType){
// Joan : Ref1 is now be the BudgetType (Capex/Opex), Ref5 is now the Signed Company Code. Retreive the GLCode by Ref1-Ref5 matching. (task 12278)
	//$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType='CostCat' AND Ref1='$SignedCo' AND Ref2='$CostType' AND Ref3='$CostCat' AND Ref4='$CostSubcat'";
	$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref5 = '$SignedCo' AND Ref4 = '$CostSubcat' and Ref3 = '$CostCat' and Ref2 = '$CostType' and Ref1 = '$BudgetType'";
	$GLCode = getOneCell($sql);
	
	if(!$GLCode){
		//$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType='CostCat' AND Ref1='$SignedCo' AND Ref2='$CostType' AND Ref3='$CostCat' AND Ref4=''";
		$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref4 = '$CostSubcat' and Ref3 = '$CostCat' and Ref2 = '$CostType' and Ref1 = '$BudgetType'";
		$GLCode = getOneCell($sql);
	}
	
	if(!$GLCode){
		//$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType='CostCat' AND Ref1='$SignedCo' AND Ref2='$CostType' AND Ref3='' AND Ref4=''";
		$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref4 = '' and Ref3 = '$CostCat' and Ref2 = '$CostType' and Ref1 = '$BudgetType'";
		$GLCode = getOneCell($sql);
	}
	
	if(!$GLCode){
		//$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType='CostCat' AND Ref1='$SignedCo' AND Ref2='' AND Ref3='' AND Ref4=''";
		$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref4 = '' and Ref3 = '' and Ref2 = '$CostType' and Ref1 = '$BudgetType'";
		$GLCode = getOneCell($sql);
	}

	if(!$GLCode){
		$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref4 = '' and Ref3 = '' and Ref2 = '' and Ref1 = '$BudgetType'";
		$GLCode = getOneCell($sql);
	}
	
	return unformatAccCode($GLCode);
}

function getPCReqGLCode($SRNum){
	global $insightDBLink;
	$sql = "SELECT ProductID, Segment, SignedBDM FROM tblSale WHERE ID='$SRNum'";
	$SRData = getQueryResult($sql);
	
	$ProductID = $SRData[0]["ProductID"];
	$Segment = $SRData[0]["Segment"];
	$SignedBDM = $SRData[0]["SignedBDM"];
	
	$strSQL = "SELECT StaffRegionID FROM tblReportingLevel WHERE LoginName='$SignedBDM'";
	$MainRegion = getOneCell($strSQL);
	
	/*
	$ures = mysqli_query($insightDBLink, $strSQL);
	if($urow=mysqli_fetch_object($ures)){

		$tmpStaffRegionID=$urow->StaffRegionID;
// 		include_once('../../../phpInc/function.inc.php');    // use phpInc variable
include_once($insight_phpInc_path . "/function.inc.php");
		$MainRegion=Find_StaffMainRegion($tmpStaffRegionID); //file in /phpInc/function.inc.php

	}*/
	
	$sql = "SELECT GLAccountCode FROM tblPCReqGLAccountCode WHERE ProductID = '$ProductID' AND Segment = '$Segment' AND Region = '$MainRegion' AND Active=1";
	//$GLCode = getOneCell($sql);
	
	$query = mysqli_query($insightDBLink, $sql);
	$num_rows = mysqli_num_rows($query); 
	
	if ($num_rows == 1) {
		$GLCode = dbSelectColumnFromQuery($query, 0);		
		
	} elseif ($num_rows < 1) {
		$sql = "SELECT GLAccountCode FROM tblPCReqGLAccountCode WHERE ProductID = '$ProductID' AND (Segment = '' OR Segment IS NULL) AND Region = '$MainRegion' AND Active=1";
		$GLCode = getOneCell($sql);	
	} else { // $num_rows > 1
		$GLCode = "";
	}
	/*
	if($GLCode==""){
		$sql = "SELECT GLAccountCode FROM tblPCReqGLAccountCode WHERE ProductID = '$ProductID' AND (Segment = '' OR Segment IS NULL) AND Region = '$MainRegion' AND Active=1";
		$GLCode = getOneCell($sql);
	}*/
  
	return unformatAccCode($GLCode);
}

const RANGE_ARRAY_SORT = 1;
const RANGE_ARRAY = 2;
const RANGE_STRING_SORT = 3;
const RANGE_STRING = 4;

function range_string($range_str, $output_type = RANGE_ARRAY_SORT){
    // Remove spaces and nother non-essential characters
    $find[]    = "/[^\d,\-]/";
    $replace[] = "";
   
    // Remove duplicate hyphens
    $find[]    = "/\-+/";
    $replace[] = "-";
   
    // Remove duplicate commas
    $find[]    = "/\,+/";
    $replace[] = ",";
   
    $range_str = preg_replace($find, $replace, $range_str);

    // Remove any commas or hypens from the end of the string
    $range_str = trim($range_str,",-");
   
    $range_out = [];
    $ranges    = explode(",", $range_str);
   
    foreach($ranges as $range)
    {
       
        if(is_numeric($range) || strlen($range) == 1)
        {
            // Just a number; add it to the list.
            $range_out[] = (int) $range;
        }
        else if(is_string($range))
        {
           
            // Is probably a range of values.
           $range_exp = preg_split("/(\D)/",$range,-1,PREG_SPLIT_DELIM_CAPTURE);
       
            $start = $range_exp[0];
            $end   = $range_exp[2];
       
            if($start > $end)
            {
                for($i = $start; $i >= $end; $i -= 1)
                {
                    $range_out[] = (int) $i;
                }
            }
            else
            {
                for($i = $start; $i <= $end; $i += 1)
                {
                    $range_out[] = (int) $i;
                }
            }
           
        }
    }
   
    switch ($output_type) {
        case RANGE_ARRAY_SORT:
            $range_out = array_unique($range_out);
            sort($range_out);
           
        case RANGE_ARRAY:
            return $range_out;
            break;
           
        case RANGE_STRING_SORT:
            $range_out = array_unique($range_out);
            sort($range_out);
           
        case RANGE_STRING:
			$range_out = array_unique($range_out);
            sort($range_out);
			return implode(",", $range_out);
			break;
			
        default:
            return implode(", ", $range_out);
            break;
    }
}

function getPRDocType(){
global $insightDBLink;

	$sql = "SELECT DISTINCT `CodeValue` FROM `tblGenericCode` WHERE `CodeType` = 'DocType' AND `CodeCat` = 'EventLogPR' AND ActiveFlag = 1 ORDER BY `CodeValue`";
	$query = mysqli_query($insightDBLink, $sql);
	if ($query) {
		while ($value = mysqli_fetch_array($query)) { 
			$DocType[] = $value["CodeValue"];
		} 
		return $DocType;
	} else {
		return false;
	}
}

function copyBRFileToPR($Attachment,$pr_id) {
	$dir_br = "file/";
	$dir_pr = "../../Fin/online_cost/PR/uploadDoc/".PRID($pr_id)."/";
	if (MakeDirectory($dir_pr, 0777)) {
		//$sql = "SELECT Attachment FROM tblPCEventLog WHERE LevelInd = 'BR' AND ID='$EventLogID'";
		//$query = mysqli_query($insightDBLink, $sql);
		
		//while ($row = mysqli_fetch_array($query)) {
			//copy($dir_is.$row["Attachment"], $dir_pr.$row["Attachment"]);
			$target = substr($Attachment, 8);
			
			copy($dir_br.$Attachment, $dir_pr.$target);
		//}
		return true;
	} else {
		return false;
	}
}

// Joan : task 17840 bug fix - need to save PR attachment to uploadDoc/yyyy/mm/filename.
function copyBRFileToPR1($Attachment) {
	$AttachmentLen = strlen($Attachment);
	$dir_br = "file/".substr($Attachment, 10, $AttachmentLen-10);
	$dir_pr = "../online_cost/PR/";
	if (MakeDirectory($dir_pr, 0777)) {
		copy($dir_br, $dir_pr.$Attachment);
		return true;
	} else {
		return false;
	}
}

function findOrderTypeDesc($value) {
	if (!$value) return false;
	$sql = "SELECT Description FROM tblGenericCode WHERE CodeType = 'ActionCode' AND CodeValue = '$value'";
	return getOneCell($sql);
}

function validateValue($SQL){
	if($SQL){			
		
		$RecordFound = getOneCell($SQL);
		
		if ($RecordFound) {
			return true;
		} else {
			return false;
		}

	} else {
		return false;
	}
}

function getVendorGroup($VendorCode){
	if (!$VendorCode) return false;
	$sql = "SELECT GroupID FROM tblVendor WHERE EpicorCode= '$VendorCode'";
	return getOneCell($sql);	
}

function submitPR($BRID,$PrevPRID,$CostType,$SignedCo,$current_dt,$Capex_USD,$Opex_USD){
	global $insightDBLink;
	//echo "submitBR";
	//update tblPCReqHeader's CurrentStep
	$Actsql = "UPDATE tblPCReqHeader SET CurrentStep =2, Capex_USD='$Capex_USD', Opex_USD ='$Opex_USD', RequestDate = '$current_dt' WHERE ID = '$PrevPRID' LIMIT 1";
	$query = mysqli_query($insightDBLink, $Actsql);	
	
	//update tblPCActFlow
	$Approver = getPRRenewApprover('PCA',$CostType);
	$Actsql = "UPDATE tblPCActFlow SET ActionUser = '$Approver' WHERE LevelInd = 'PRReq' AND ReferenceID = '$PrevPRID' AND Sequence = 3";
	$query = mysqli_query($insightDBLink, $Actsql);
	
	$Buyer = getPRRenewApprover('PCB',$SignedCo);
	$Actsql = "UPDATE tblPCActFlow SET ActionUser = '$Buyer' WHERE LevelInd = 'PRReq' AND ReferenceID = '$PrevPRID' AND Sequence IN (2,4)";
	$query = mysqli_query($insightDBLink, $Actsql);
	
	$EventLogsql = "INSERT INTO tblPCEventLog ( LevelInd , ReferenceID , LogDate , LogUser , DocType, Action , Description , Attachment ) 
			VALUES ('ReqHeader', '$PrevPRID',  '$current_dt', 'insight', 'STATUS', 'Submit', 'Submitted', NULL)";
	$EventLogQuery = mysqli_query($insightDBLink, $EventLogsql);	
	
	//update tblBRBudget's Allocated Amount
	$Amtsql = "UPDATE tblBRBudget SET AllocatedCapEx = AllocatedCapEx + $Capex_USD, AllocatedOpEx = AllocatedOpEx + $Opex_USD WHERE ID = '$BRID' LIMIT 1";
	$query = mysqli_query($insightDBLink, $Amtsql);
	
	//update OccupyFlag = 1 in tblPCCostItem
	$Actsql = "UPDATE tblPCCostItem SET OccupyFlag = 1 WHERE ID IN (SELECT PrevCostItemID FROM tblPCReqDetail WHERE ReqID = '$PrevPRID' AND PrevCostItemID>0)";
	$query = mysqli_query($insightDBLink, $Actsql);
	
	SendPRSubmitEmail($PrevPRID);

}

function getPRRenewApprover($Role,$CodeValue){
	if (!$CodeValue) return false;
	$sql = "SELECT Description FROM tblGenericCode WHERE CodeType='CIRenew' AND CodeCat = '$Role' AND CodeValue='$CodeValue' AND ActiveFlag=1 LIMIT 1";
	return getOneCell($sql);	
}

function checkWONum($BudgetType,$GLCode,$WONum,$SignedCo){
	global $insightDBLink;
	if (!$GLCode || !$SignedCo) return true;
	global $EPI_HOSTNAME, $EPI_USERNAME, $EPI_PASSWORD;
	$query = mysqli_query($insightDBLink, "SELECT lpad(CoCode, 3, '0') FROM tblRegisteredCo LEFT OUTER JOIN tblCo_Entities ON SignedCO = tblRegisteredCo.ID WHERE ID ='$SignedCo'");
	$EPI_DB_NAME = "BTN".dbSelectColumnFromQuery($query, 0);
	$x = dbSqlSrvConnect($EPI_HOSTNAME, $EPI_DB_NAME, $EPI_USERNAME, $EPI_PASSWORD);
	
	$query2 = sqlsrv_query($x, "SELECT account_type from glchart WHERE account_code='$GLCode'");
	$EPIresult = sqlsrv_fetch_array($query2);
	$wo_check = $EPIresult[0];
	
	//$sql = "SELECT account_type from glchart WHERE account_code='$GLCode'";
	//$wo_check = getOneCell($sql);
	
	if($wo_check==150){
		if($BudgetType=="Capex" && !preg_match("/[^0-9]/", $WONum) && $WONum!=""){
			return true;
		} else {
			return false;
		}		
	} else {
		if($BudgetType=="Opex" && $wo_check){
			return true;
		} else {
			return false;
		}
	}	
}

//function SendPRSubmitEmail($DocType, $ID, $newStep, $action, $EventType, $EventDetails, $fname, $special_6896) {
function SendPRSubmitEmail($ID) { 
	global $insightDBLink;
	global $webhynet_email;
	global $intranet_naming;
	$FromTitle = "$intranet_naming System";
	$headers = $webhynet_email;
    $recipient = NULL;
	$ToTitle = NULL;
	global $PHP_AUTH_USER;
	global $BDM_FullName;
	global $server_path;
	
	$prid = PRID($ID);
	$subject = "Purchase Request : $prid";
	
	$query = mysqli_query($insightDBLink, "SELECT * FROM tblPCReqHeader WHERE ID = '$ID'");
	$ReqInfo = mysqli_fetch_array($query);
	
	$query = mysqli_query($insightDBLink, "SELECT * FROM tblPCActFlow WHERE LevelInd = 'PRReq' AND ReferenceID = '$ID'");
	while ($value = mysqli_fetch_array($query)) {
		if($value["Role"]) $ReqInfo[$value["Role"]] = $value["ActionUser"];
		$ReqInfo["Role"][$value["Sequence"]] = $value["ActionUser"];
	}
	
	$message = "Purchase Request ".$prid." has been Submitted by $FromTitle.\r\n\n";	
	$message .= "Requester : ".$BDM_FullName[$ReqInfo["Role"]["1"]]."\r\n\n";
	$message .= "Request Date :  ".date("d-M-Y", strtotime($ReqInfo['RequestDate']))."\r\n\n";
	$message .= "Record Creation Date : ".date("d-M-Y", strtotime($ReqInfo['CreateDate']))."\r\n\n";
	$message .= "Signed Company:  ".findSignedCoDesc($ReqInfo['SignedCo'])."\r\n\n";
	$message .= "Cost Type :  ".findCostTypeDesc($ReqInfo['CostType'])."\r\n\n";
	$message .= "Vendor Name :  ".findVendorName($ReqInfo['VendorCode'])."\r\n\n";
	$message .= "PR Description :  ".$ReqInfo['Description']."\r\n\n";
	$message .= "PR Amount (Local Curr) : ".$ReqInfo['Currency']." ";
	$message .= ($ReqInfo["Currency"]=="JPY"||$ReqInfo["Currency"]=="KRW")?number_format($ReqInfo["TotalAmt"],0):number_format($ReqInfo["TotalAmt"],2);
	$message .= "\r\n\n";
	$message .= "PR Amount (USD) : ".number_format($ReqInfo['TotalAmt_USD'],2)."\r\n\n";
	$message .= "PO Number : ".$ReqInfo['PONum']."\r\n\n";
	$message .= "1st Processor : ".$BDM_FullName[$ReqInfo["Role"]["2"]]."\r\n\n";
	//$message .= "2nd Processor : ".$BDM_FullName[$ReqInfo["Role"]["6"]]."\r\n\n";
	$message .= "Approver : ".$BDM_FullName[$ReqInfo["Role"]["3"]]."\r\n\n";
	
	
	$message .= "Your request has been routed to ".$BDM_FullName[$ReqInfo["Role"]["2"]]." for Verification.\r\n\n";

	$message .= "Please click below for request $prid details.\r\n\n";
	$message .="************************************************************\r\n\n";
	$message .="Please Note: If clicking on the URL does not work,\r\n\n";
	$message .="please copy and paste the entire URL in your browser window\r\n\n";
	$message .="************************************************************\r\n\n";
	$message .= "For PC: \r\n\n";	
	$message .= "$server_path/Fin/online_cost/PR/CostRequest.php?ID=".$ID."";
	$message .= "\r\n\n";
	$message .= "For BlackBerry: \r\n\n";
	$message .= "$server_path/Fin/online_cost/PR/CostRequestLite.php?ID=".$ID."";				
	
	$to_emails = getRecipientEmails($ReqInfo["Role"]["2"]);
	$cc_emails = getRecipientEmails($ReqInfo["Role"]["1"]);
	
	sendEmail($to_emails, $subject, $message, $cc_emails);
	
}

function getActionName($currentstep){
	if(!$currentstep) return false;
	
	$ActionSQL ="SELECT CodeValue FROM tblGenericCode WHERE CodeType = 'BRStatus' AND CodeCat = 'Status' AND Sequence ='$currentstep'";
	$Action = getOneCell($ActionSQL);
	
	return $Action;
}
