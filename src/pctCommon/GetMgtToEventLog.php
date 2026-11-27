<?php 

$GpsAuth = NULL;
include_once(getenv("INSIGHT_CONFIG"));
// include_once('../../phpInc/config.inc.php');    // use config variable
// include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");
// include("../../phpInc/function.inc.php");    // use phpInc variable
include_once($insight_phpInc_path . "/function.inc.php");

  $sql = "SELECT LoginName,StaffNameFirst, StaffNameLast, StaffNickname FROM tblStaffInfo  
          WHERE DateQuit IS NULL
          ORDER by StaffNameLast ASC";
  $result = mysqli_query($insightDBLink, $sql);
   
$FieldID = $_REQUEST["FieldID"] ?? "";
$FieldName = $_REQUEST["FieldName"] ?? "";
$ID = $_REQUEST["ID"] ?? "";
?>
<html>
<link rel="stylesheet" type="text/css" href="/mainStyle.css">
<body bgcolor=#ffffff topmargin=0 leftmargin=0 marginwidth=0 marginheight=0'>

<script>
  function setword(Mgt,LoginName)
  {
    str1Obj = opener.document.getElementsByName('<?php echo $FieldName ?>');
	str1 = str1Obj[<?php echo $ID ?>].value;
    if(str1) str1 = str1+"; "+Mgt;
    else str1 = Mgt;
    	
    str2Obj = opener.document.getElementsByName('<?php echo $FieldID ?>');
	str2 = str2Obj[<?php echo $ID ?>].value;
    if(str2) str2 = str2+";"+LoginName;
    else str2 = LoginName;
      
    str1Obj[<?php echo $ID ?>].value = str1;
    str2Obj[<?php echo $ID ?>].value = str2;
    window.close();
  }
</script>

<form name="Mgt">
<table border=0 width=260 cellpadding=0 cellspacing=0 align="center">
  <tr><td>&nbsp;</td></tr>
  <tr><td><?php href_index(); ?></td></tr>
  <tr><td>&nbsp;</td></tr>
  <tr><td class="THeader">Select Name List.</td></tr>
  <tr><td class='TOdd' height='20'><a href="javascript:setword('CA All','hca_all')">CA All</A></td></tr>
  <?php 
  	$SQL="SELECT EmailGp, Email FROM tblCAOffice WHERE EmailGp!=''
  		ORDER By EmailGp ASC";
  	$res=mysqli_query($insightDBLink, $SQL);
  	$i=0;
  	while($row=mysqli_fetch_object($res)){
  		if (($i%2) == 0) echo "<tr class='TEven'>";
    		else echo "<tr class='TOdd'>";
    		echo "<td height='20'><a href=\"javascript:setword('$row->EmailGp','$row->Email')\">$row->EmailGp</A></td></tr>";
    		$i++;
  	}
  ?>
<tr><td class='TOdd' height='20'><a href="javascript:setword('IPPhonesGroup','IPPhonesGroup')">IP Phones Group</A></td></tr>  



<?php 
  $PrevChr = "";
  $i = 0;
  while ($row = mysqli_fetch_object($result)) 
  {
    $StaffNameEnglish=$row->StaffNameLast.", ".$row->StaffNameFirst;
    if($row->StaffNickname) { $StaffNameEnglish .= ", ".$row->StaffNickname; }

    $Mgt = trim($StaffNameEnglish);
    $LoginName = trim($row->LoginName);
	//$Email = trim($row->Email);
    $ThisChr = strtoupper(substr($Mgt, 0, 1));
  	
    if ($PrevChr != $ThisChr) {
      if ($PrevChr != "") {
        echo "</table\n>";
      }
      echo "  <a name='" . $ThisChr . "'>\n";
      if ($PrevChr != "") {
        echo "<table border=0 width=260 cellpadding=0 cellspacing=0 align='center'>";
      }
      $PrevChr = $ThisChr;
    }
  	
    if (($i%2) == 0) {
      echo "  <tr class='TEven'>";
    }
    else{
     echo "  <tr class='TOdd'>";
    }

    echo "<td height=20><a href=\"javascript:setword('$Mgt','$LoginName')\">" . $Mgt . "</A></TD>";
    echo "</tr>\n";
    $i++;
  }
  ?>
  </table>
</form>
</body>
</html>
