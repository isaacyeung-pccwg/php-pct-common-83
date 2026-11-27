<?php 

function QuantityChecking($ID, $InsertQty, $DateFrom, $DateTo) {
	global $insightDBLink;
//	echo "ID: ".$ID.", Qty: ".$InsertQty.", From: ".$DateFrom.", To: ".$DateTo."<br>";
	$ci_sql = "select Quantity from tblPCCostItem where ID = '$ID'";
	$ci_result = mysqli_query($insightDBLink, $ci_sql);
	$ci_show = mysqli_fetch_row($ci_result);
	$Quantity = $ci_show[0];
	
	$r_sql = "select sum(ReceiveQty) from tblPCReceipts where CostItemID = '$ID' group by CostItemID";
	$r_result = mysqli_query($insightDBLink, $r_sql);
	$r_show = mysqli_fetch_row($r_result);
	$ReceiveQty = $r_show[0];
	
	if ($Quantity >= ($ReceiveQty + $InsertQty) && DateChecking($ID, $DateFrom, $DateTo))
		return true;
	else
		return false;
	
}


function DateChecking($ID, $DateFrom, $DateTo) {
	global $insightDBLink;
	
	$r2_sql = "select DateFrom, DateTo from tblPCReceipts where CostItemID = '$ID'";
	$r2_result = mysqli_query($insightDBLink, $r2_sql);
	if (mysqli_num_rows($r2_result) == 0) {
		return true;
	} else {
		while($r2_show = mysqli_fetch_array($r2_result)) {
			$saved_DateFrom = $r2_show[0];
			$saved_DateTo = $r2_show[1];

			// Both New DateFrom and New DateTo are before or after the existing date periods
			if ((strtotime((string) $DateFrom) < strtotime((string) $saved_DateFrom) && strtotime((string) $DateTo) < strtotime((string) $saved_DateFrom)) || (strtotime((string) $DateFrom) > strtotime((string) $saved_DateTo) && strtotime((string) $DateTo) > strtotime((string) $saved_DateTo))) {
				
			} else {
				return false;
			}
		}

		return true;
	}
	
}



