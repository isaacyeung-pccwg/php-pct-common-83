<?php
// File Holder #22152 Ken Law @29 Oct 2014
// File Holder # Alvin Leung @06 Aug 2014
// File Holder #16622 Joan Ng @23 Apr 2013

// include_once('../../../phpInc/config.inc.php');    // use config variable
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");

include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/pct_common_helpers.php");

function reCalculateBR($BudgetID){
    global $insightDBLink;
    
    if ($BudgetID != '' && $BudgetID != 0) {
            //echo "start recal...Budget ID...".$BudgetID;

            // if Budget ID is not approved yet, exit function.
            $result = mysqli_query($insightDBLink, "select * from tblBRBudget where ID = $BudgetID and CurrentStep < 11");

            if ($result && mysqli_num_rows($result) > 0) {
                    //echo "Budget ID has not been approved or does not exist. No BR re-calculation.";
                    return false;
            }

            $total_capex_int_amount = 0.0000;
            $total_capex_process_amount = 0.0000;
            $total_opex_int_amount = 0.0000;
            $total_opex_process_amount = 0.0000;

            // get all the PRs which issued from target BRID. Exclude PRs which have status not in Saved, Cancel, Denied and Void
            //$PRHdr_sql = "select ID, CurrentStep, Capex_USD, Opex_USD, ExRate_USD from tblPCReqHeader where BudgetID = $BudgetID and CurrentStep not in (1, 6, 7, 12) order by ID";
            $PRHdr_sql = "select ID, CurrentStep, Capex_USD, Opex_USD, ExRate_USD from tblPCReqHeader where BudgetID = $BudgetID order by ID";
            $result = mysqli_query($insightDBLink, $PRHdr_sql);

            // Loop each PR to recalculate all amounts
            for ($t = 1; $t <= mysqli_num_rows($result); $t++) {
                    $DBTrow = mysqli_fetch_row($result);
                    $PRID = $DBTrow[0];
                    $CurrentStep = $DBTrow[1];
                    $Capex_USD = $DBTrow[2];
                    $Opex_USD = $DBTrow[3];
                    $ExRate_USD = $DBTrow[4];
                    $total_capex_amount = 0.00;
                    $total_opex_amount = 0.00;

                    // Go thru each PR item of an BR to recalcuate the rate
                    $PRDtl_query = mysqli_query($insightDBLink, "select Quantity, UnitCost, BudgetType from tblPCReqDetail where ReqID = $PRID order by LineNum");
                    while ($value = mysqli_fetch_array($PRDtl_query)) {
                            $reCal_item = $value["Quantity"] * $value["UnitCost"] * $ExRate_USD;
                            $budgetType = $value["BudgetType"];
                            if (($CurrentStep != 1) && ($CurrentStep != 6) && ($CurrentStep != 7) && ($CurrentStep != 12)) { // if PR status is not Saved, Cancel, Denied or Void
                                    if ($budgetType == "Capex") {
                                            $total_capex_amount = $total_capex_amount + $reCal_item;
                                            $total_capex_int_amount = $total_capex_int_amount + $reCal_item; 
                                            if ($CurrentStep == 5) { // Processed PR - need to also add amount to total spent Capex amount
                                                    $total_capex_process_amount = $total_capex_process_amount + $reCal_item;
                                            } //else { // Intransit PR
                                                    //$total_capex_int_amount = $total_capex_int_amount + $reCal_item;
                                            //}
                                    }
                                    if ($budgetType == "Opex") {
                                            $total_opex_amount = $total_opex_amount + $reCal_item;
                                            $total_opex_int_amount = $total_opex_int_amount + $reCal_item;
                                            if ($CurrentStep == 5) { // Processed PR - need to also add amount to total spent Opex amount
                                                    $total_opex_process_amount = $total_opex_process_amount + $reCal_item;
                                            } //else { // Intransit PR
                                                    //$total_opex_int_amount = $total_opex_int_amount + $reCal_item;
                                            //}				
                                    }
                            }
                    }

                    // If the overall recal capex amount != Capex_USD in PR header, overwrite it. Also will update it to 0 if it is a NULL value
                    $total_capex_amount_round2 = round($total_capex_amount,2);
                    if ((is_null($Capex_USD) || strlen(trim($Capex_USD)) == '') || ($total_capex_amount_round2 != $Capex_USD)) {
                            $sql_update_capex = "update tblPCReqHeader set Capex_USD = $total_capex_amount_round2 where ID = $PRID";
                            mysqli_query($insightDBLink, $sql_update_capex);
                            /*if ($PRID == $PR_ID) {
                                    $new_PR_Capex_USD = $total_capex_amount_round2;
                            }*/
                    }
                    // If the overall recal opex amount != Opex_USD in PR header, overwrite it. Also will update it to 0 if it is a NULL value
                    $total_opex_amount_round2 = round($total_opex_amount,2);
                    if ((is_null($Opex_USD) || strlen(trim($Opex_USD)) == '') || $total_opex_amount_round2 != $Opex_USD) {
                            $sql_update_opex = "update tblPCReqHeader set Opex_USD = $total_opex_amount_round2 where ID = $PRID";
                            mysqli_query($insightDBLink, $sql_update_opex);
                            /*if ($PRID == $PR_ID) {
                                    $new_PR_Opex_USD = $total_opex_amount_round2;
                            }*/			
                    }		
            }

            // get all the CTs which issued from target BRID. Exclude PRs which have status not in Saved, Cancel, Denied and Void
            //$CT_sql = "select ID, PrevCostItemID, CurrentStep, Capex_USD, Opex_USD, ExRate_USD, Quantity, UnitCost, BudgetType from tblPCTermReq where BudgetID = $BudgetID and CurrentStep not in (1, 6, 7, 12) order by PrevCostItemID";
            $CT_sql = "select ID, PrevCostItemID, CurrentStep, Capex_USD, Opex_USD, ExRate_USD, Quantity, UnitCost, BudgetType from tblPCTermReq where BudgetID = $BudgetID order by PrevCostItemID";
            $result = mysqli_query($insightDBLink, $CT_sql);
            //echo $CT_sql."</br>";
            // Loop each CT to recalculate all amounts
            for ($t = 1; $t <= mysqli_num_rows($result); $t++) {
                    $DBTrow = mysqli_fetch_row($result);
                    $CTID = $DBTrow[0];
                    $PrevCostItemID = $DBTrow[1];
                    $CurrentStep = $DBTrow[2];
                    $Capex_USD = $DBTrow[3];
                    $Opex_USD = $DBTrow[4];
                    $ExRate_USD = $DBTrow[5];
                    $Quantity = $DBTrow[6];
                    $UnitCost = $DBTrow[7];
                    $BudgetType = $DBTrow[8];
                    $total_capex_amount = 0.00;
                    $total_opex_amount = 0.00;

                    // Go thru each CT item to recalcuate the rate
                    $reCal_item = $Quantity * $UnitCost * $ExRate_USD;
                    if (($CurrentStep != 1) && ($CurrentStep != 6) && ($CurrentStep != 7) && ($CurrentStep != 12)) { // if CT status is not Saved, Cancel, Denied or Void
                            if ($BudgetType == 'Capex') {
                                    $total_capex_amount = $total_capex_amount + $reCal_item;
                                    $total_capex_int_amount = $total_capex_int_amount + $reCal_item; 
                                    if ($CurrentStep == 5 || $CurrentStep == 11) { // Awaiting Confirmation/Confirmed CT - need to also add amount to total spent Capex amount
                                            $total_capex_process_amount = $total_capex_process_amount + $reCal_item;
                                    } 
                            }
                            if ($BudgetType == 'Opex') {
                                    $total_opex_amount = $total_opex_amount + $reCal_item;
                                    $total_opex_int_amount = $total_opex_int_amount + $reCal_item;
                                    if ($CurrentStep == 5 || $CurrentStep == 11) { // Awaiting Confirmation/Confirmed CT - need to also add amount to total spent Opex amount
                                            $total_opex_process_amount = $total_opex_process_amount + $reCal_item;
                                    }
                            }
                    }

                    // If the overall recal capex amount != Capex_USD in CT, overwrite it. Also will update it to 0 if it is a NULL value
                    $total_capex_amount_round2 = round($total_capex_amount,2);
                    if ((is_null($Capex_USD) || strlen(trim($Capex_USD)) == '') || ($total_capex_amount_round2 != $Capex_USD)) {
                    //echo "update CT# ".$CTID." Capex_USD = ".$total_capex_amount_round2."</br>";
                            $sql_update_capex = "update tblPCTermReq set Capex_USD = $total_capex_amount_round2 where ID = $CTID";
                            mysqli_query($insightDBLink, $sql_update_capex);
                    }
                    // If the overall recal opex amount != Opex_USD in CT, overwrite it. Also will update it to 0 if it is a NULL value
                    $total_opex_amount_round2 = round($total_opex_amount,2);
                    if ((is_null($Opex_USD) || strlen(trim($Opex_USD)) == '') || $total_opex_amount_round2 != $Opex_USD) {
                    //echo "update CT# ".$CTID." Opex_USD = ".$total_opex_amount_round2."</br>";
                            $sql_update_opex = "update tblPCTermReq set Opex_USD = $total_opex_amount_round2 where ID = $CTID";
                            mysqli_query($insightDBLink, $sql_update_opex);
                    }		
            }
            
            
            //echo "total_opex_int_amount: " .$total_opex_int_amount . "<br><br>";
            //echo "total_opex_process_amount: " .$total_opex_process_amount . "<br><br>";
            
/*********************************************************************/            
/* 
/*
/* For Expense Claim (EC)            
/*
/*            
/*********************************************************************/           

            // modified by Alvin, 2014-08-06
/*            
            $sql = "SELECT TotalAmt_USD, FormStatus
                    FROM tblExpClaim
                    WHERE FormStatus NOT IN ('C','D','S') 
                    AND (OTRefNo = (SELECT OTRefNo FROM tblOvTrvl o, tblBRBudget b WHERE b.OTNo = o.ID AND b.ID = $BudgetID)) OR BRID = $BudgetID ";
*/
            
            // [20141029:Ken:Add]
            // Calc AllocatedOpex amount
            $sql  = "select sum(TotalAmt_USD) as ALLTotalAmt_USD from tblExpClaim ";
            $sql .= "where ";
            $sql .= "FormStatus NOT IN ('C','D','S') ";
            $sql .= "and (OTRefNo = (SELECT OTRefNo FROM tblOvTrvl o, tblBRBudget b WHERE b.OTNo = o.ID AND b.ID = $BudgetID) or BRID = $BudgetID)";
            
            //echo $sql  . "<br><br>";
            
            $ec_res = mysqli_query($insightDBLink, $sql);
            
            if (mysqli_num_rows($ec_res)){
            
                $ec_row=mysqli_fetch_object($ec_res);
                //echo count($ec_row);
                
                $EC_total_USD = $ec_row->ALLTotalAmt_USD;
                
                if (is_null($EC_total_USD) || strlen(trim($EC_total_USD)) == '') {
                    $EC_total_USD = 0;
                }

                $total_opex_int_amount = $total_opex_int_amount + $EC_total_USD;
                
            }
            
            // Calc SpentOp amount
            $sql  = "select sum(TotalAmt_USD) as ALLTotalAmt_USD from tblExpClaim ";
            $sql .= "where ";
            $sql .= "FormStatus = 'P' ";
            $sql .= "and (OTRefNo = (SELECT OTRefNo FROM tblOvTrvl o, tblBRBudget b WHERE b.OTNo = o.ID AND b.ID = $BudgetID) or BRID = $BudgetID)";
            
            $ec_res = mysqli_query($insightDBLink, $sql);
            if (mysqli_num_rows($ec_res)){
            
                $ec_row=mysqli_fetch_object($ec_res);
                //echo count($ec_row);
                
                $EC_total_spent_USD = $ec_row->ALLTotalAmt_USD;

                if (is_null($EC_total_spent_USD) || strlen(trim($EC_total_spent_USD)) == '') {
                    $EC_total_spent_USD = 0;
                }

                $total_opex_process_amount = $total_opex_process_amount + $EC_total_spent_USD;

            }

            /*
            echo "EC_total_USD: " .$EC_total_USD . "<br><br>";
            echo "EC_total_spent_USD :" . $EC_total_spent_USD . "<br><br>";
            
            echo "total_opex_int_amount: " .$total_opex_int_amount . "<br><br>";
            echo "total_opex_process_amount :" . $total_opex_process_amount . "<br><br>";
            
            exit;
             */
            
            
            // Update BR hdr next
            //$sql = "SELECT ApprovedOpEx, AllocatedOpEx, ApprovedCapEx, AllocatedCapEx, SpentOpEx, SpentCapEx FROM tblBRBudget where ID = $BudgetID";
            $sql = "SELECT ApprovedOpEx, AllocatedOpEx, ApprovedCapEx, AllocatedCapEx, SpentOpEx, SpentCapEx, CurrentStep FROM tblBRBudget where ID = $BudgetID";
            $DBT1_last = mysqli_query($insightDBLink, $sql);
            $DBTrow_last = mysqli_fetch_row($DBT1_last);
            $ApprovedOpEx = $DBTrow_last[0];
            $AllocatedOpEx = $DBTrow_last[1];
            $ApprovedCapEx = $DBTrow_last[2];
            $AllocatedCapEx = $DBTrow_last[3];
            $SpentOpEx = $DBTrow_last[4];
            $SpentCapEx = $DBTrow_last[5];
            $BRCurrentStep = $DBTrow_last[6];

            /*
            $EC_total_USD = getECAmount_nouse($BudgetID);
    //echo $EC_total_USD;
            if($EC_total_USD>0) {
                    if (($BRCurrentStep != 1) && ($BRCurrentStep != 6) && ($BRCurrentStep != 7) && ($BRCurrentStep != 12)) {
                            $total_opex_int_amount = $total_opex_int_amount + $EC_total_USD;
                    }else{
                            $total_opex_process_amount = $total_opex_process_amount + $EC_total_USD;
                    }
            }
            */

            // set a 0 to all 6 amount fields of BR if it is null or empty...
            if (is_null($ApprovedCapEx) || strlen(trim($ApprovedCapEx)) == '') {
                    $sql = "update tblBRBudget set ApprovedCapEx = 0 where ID  = $BudgetID";
                    //$new_ApprovedCapEx = 0;
                    mysqli_query($insightDBLink, $sql);
            }

            if (is_null($SpentCapEx) || strlen(trim($SpentCapEx)) == '') {
                    $sql = "update tblBRBudget set SpentCapEx = 0 where ID  = $BudgetID";
                    mysqli_query($insightDBLink, $sql);
            }

            if (is_null($AllocatedCapEx) || strlen(trim($AllocatedCapEx)) == '') {
                    $sql = "update tblBRBudget set AllocatedCapEx = 0 where ID  = $BudgetID";
                    mysqli_query($insightDBLink, $sql);
            }

            if (is_null($ApprovedOpEx) || strlen(trim($ApprovedOpEx)) == '') {
                    $sql = "update tblBRBudget set ApprovedOpEx = 0 where ID  = $BudgetID";
                    //$new_ApprovedOpEx = 0;
                    mysqli_query($insightDBLink, $sql);
            }

            if (is_null($SpentOpEx) || strlen(trim($SpentOpEx)) == '') {
                    $sql = "update tblBRBudget set SpentOpEx = 0 where ID  = $BudgetID";
                    mysqli_query($insightDBLink, $sql);
            }

            if (is_null($AllocatedOpEx) || strlen(trim($AllocatedOpEx)) == '') {
                    $sql = "update tblBRBudget set AllocatedOpEx = 0 where ID  = $BudgetID";
                    mysqli_query($insightDBLink, $sql);
            }

            // update BR Allocated & Spent Amount of Capex/Opex if the are different from the recal amount...
            $total_capex_int_amount_round4 = round($total_capex_int_amount,4);
            if ($AllocatedCapEx != $total_capex_int_amount_round4) {
                    $sql = "update tblBRBudget set AllocatedCapEx = $total_capex_int_amount_round4 where ID = $BudgetID";
                    mysqli_query($insightDBLink, $sql);
                    //$new_total_capex_int_amount_round4 = $total_capex_int_amount_round4;
            }

            $total_opex_int_amount_round4 = round($total_opex_int_amount,4);
            if ($AllocatedOpEx != $total_opex_int_amount_round4) {
            //echo "update BR# ".$BudgetID." AllocatedOpEx = ".$total_opex_int_amount_round4."</br>";
                    $sql = "update tblBRBudget set AllocatedOpEx = $total_opex_int_amount_round4 where ID = $BudgetID";
                    mysqli_query($insightDBLink, $sql);
                    //$new_total_opex_int_amount_round4 = $total_opex_int_amount_round4;
            }

            $total_capex_process_amount_round4 = round($total_capex_process_amount,4);
            if ($SpentCapEx != $total_capex_process_amount_round4) {
                    $sql = "update tblBRBudget set SpentCapEx =$total_capex_process_amount_round4 where ID = $BudgetID";
                    mysqli_query($insightDBLink, $sql);
                    //$new_total_capex_process_amount_round4 = $total_capex_process_amount_round4;
            }

            $total_opex_process_amount_round4 = round($total_opex_process_amount,4);
            if ($SpentOpEx != $total_opex_process_amount_round4) {
                    //echo "update BR# ".$BudgetID." SpentOpEx = ".$total_opex_process_amount_round4."</br>";
                    $sql = "update tblBRBudget set SpentOpEx = $total_opex_process_amount_round4 where ID  = $BudgetID";
                    mysqli_query($insightDBLink, $sql);
                    //$new_total_opex_process_amount_round4 = $total_opex_process_amount_round4;
            }	

            return true;
    } else {
            //echo "invalid BR...";
            return false;
    }
}

function getBRAmount($BudgetID) {
	global $insightDBLink;
	if ($BudgetID != '' && $BudgetID != 0) {
		//return approved Capex amount approved Opex amount remaining Capex amount(approved allocated), remaining Opex amount "spent Capex amount" and "spent Opex amount" 
		$budget_amt_arr=["Approved_CapEx"=>NULL, "Approved_OpEx"=>NULL, "Remain_CapEx"=>NULL, "Remain_OpEx"=>NULL, "Spent_CapEx"=>NULL, "Spent_OpEx"=>NULL];
		$result = mysqli_query($insightDBLink, "select ApprovedCapEx, ApprovedOpEx, AllocatedCapEx, AllocatedOpEx, SpentCapEx, SpentOpEx from tblBRBudget where BRStatus = 'Open' and ID = $BudgetID");
		while ($row = mysqli_fetch_assoc($result)) {
			$budget_amt_arr["Approved_CapEx"] = $row["ApprovedCapEx"];
			$budget_amt_arr["Approved_OpEx"] = $row["ApprovedOpEx"];
			$budget_amt_arr["Remain_CapEx"] = $row["ApprovedCapEx"] - $row["AllocatedCapEx"];
			$budget_amt_arr["Remain_OpEx"] = $row["ApprovedOpEx"] - $row["AllocatedOpEx"];
			$budget_amt_arr["Spent_CapEx"] = $row["SpentCapEx"];
			$budget_amt_arr["Spent_OpEx"] = $row["SpentOpEx"];			
		}
		return $budget_amt_arr; 
	} else {
		return false;
	}
}

function getECAmount_incorrect($BRID) {
	global $insightDBLink;
	//Get EC details
	$SQL="
	SELECT * 
		FROM tblExpClaim 
		WHERE OTRefNo IN (
			SELECT OTRefNo
                FROM tblOvTrvl
                WHERE ID = (select OTno from tblBRBudget where ID = $BRID) OR MasterOT = (select OTno from tblBRBudget where ID = $BRID))
	UNION
	SELECT *
		FROM tblExpClaim
		WHERE BRID = $BRID";
	//echo "<BR>".$SQL;
	$ec_res=mysqli_query($insightDBLink, $SQL);
	while($ec_row=mysqli_fetch_object($ec_res)){
		$EC_tol=0;
		$ECID=$ec_row->ID;
		$ReqDate=$ec_row->ReqDate;
		$Requester=$ec_row->Requester;
		$RegionMain=$ec_row->RegionMain;
		$SalesRegionID=$Region_ID[$RegionMain];

		$LocalCcy=$ec_row->LocalCcy;
		$Currency1=$ec_row->Currency1;
		$Currency2=$ec_row->Currency2;
		$Currency3=$ec_row->Currency3;
		$FXRate1=$ec_row->FXRate1;
		$FXRate2=$ec_row->FXRate2;
		$FXRate3=$ec_row->FXRate3;
			
		$ExCcy1=$ec_row->ExCcy1;
		$ExAmt1=$ec_row->ExAmt1;
		if($ExCcy1 && $ExAmt1>0){
			if($ExCcy1==$LocalCcy){
				$ExAmt1_local = $ExAmt1;
			}else if($ExCcy1==$Currency1){
				$ExAmt1_local = round($ExAmt1*$FXRate1,2);
			}else if($ExCcy1==$Currency2){
				$ExAmt1_local = round($ExAmt1*$FXRate2,2);
			}else if($ExCcy1==$Currency3){
				$ExAmt1_local = round($ExAmt1*$FXRate3,2);
			}
		}else {
			$ExAmt1_local=0;
		}

		$ExCcy2=$ec_row->ExCcy2;
		$ExAmt2=$ec_row->ExAmt2;
		if($ExCcy2 && $ExAmt2>0){
			if($ExCcy2==$LocalCcy){
				$ExAmt2_local = $ExAmt2;
			}else if($ExCcy2==$Currency1){
				$ExAmt2_local = round($ExAmt2*$FXRate1,2);
			}else if($ExCcy2==$Currency2){
				$ExAmt2_local = round($ExAmt2*$FXRate2,2);
			}else if($ExCcy2==$Currency3){
				$ExAmt2_local = round($ExAmt2*$FXRate3,2);
			}
		}else {
			$ExAmt2_local=0;
		}

		$ExCcy3=$ec_row->ExCcy3;
		$ExAmt3=$ec_row->ExAmt3;
		if($ExCcy3 && $ExAmt3>0){
			if($ExCcy3==$LocalCcy){
				$ExAmt3_local = $ExAmt3;
			}else if($ExCcy3==$Currency1){
				$ExAmt3_local = round($ExAmt3*$FXRate1,2);
			}else if($ExCcy3==$Currency2){
				$ExAmt3_local = round($ExAmt3*$FXRate2,2);
			}else if($ExCcy3==$Currency3){
				$ExAmt3_local = round($ExAmt3*$FXRate3,2);
			}
		}else {
			$ExAmt3_local=0;
		}

		$ExCcy4=$ec_row->ExCcy4;
		$ExAmt4=$ec_row->ExAmt4;
		if($ExCcy4 && $ExAmt4>0){
			if($ExCcy4==$LocalCcy){
				$ExAmt4_local = $ExAmt4;
			}else if($ExCcy4==$Currency1){
				$ExAmt4_local = round($ExAmt4*$FXRate1,2);
			}else if($ExCcy4==$Currency2){
				$ExAmt4_local = round($ExAmt4*$FXRate2,2);
			}else if($ExCcy4==$Currency3){
				$ExAmt4_local = round($ExAmt4*$FXRate3,2);
			}
		}else {
			$ExAmt4_local=0;
		}

		$ExCcy5=$ec_row->ExCcy5;
		$ExAmt5=$ec_row->ExAmt5;
		if($ExCcy5 && $ExAmt5>0){
			if($ExCcy5==$LocalCcy){
				$ExAmt5_local = $ExAmt5;
			}else if($ExCcy5==$Currency1){
				$ExAmt5_local = round($ExAmt5*$FXRate1,2);
			}else if($ExCcy5==$Currency2){
				$ExAmt5_local = round($ExAmt5*$FXRate2,2);
			}else if($ExCcy5==$Currency3){
				$ExAmt5_local = round($ExAmt5*$FXRate3,2);
			}
		}else {
			$ExAmt5_local=0;
		}

		$ExCcy6=$ec_row->ExCcy6;
		$ExAmt6=$ec_row->ExAmt6;
		if($ExCcy6 && $ExAmt6>0){
			if($ExCcy6==$LocalCcy){
				$ExAmt6_local = $ExAmt6;
			}else if($ExCcy6==$Currency1){
				$ExAmt6_local = round($ExAmt6*$FXRate1,2);
			}else if($ExCcy6==$Currency2){
				$ExAmt6_local = round($ExAmt6*$FXRate2,2);
			}else if($ExCcy6==$Currency3){
				$ExAmt6_local = round($ExAmt6*$FXRate3,2);
			}
		}else {
			$ExAmt6_local=0;
		}

		$ExCcy7=$ec_row->ExCcy7;
		$ExAmt7=$ec_row->ExAmt7;
		if($ExCcy7 && $ExAmt7>0){
			if($ExCcy7==$LocalCcy){
				$ExAmt7_local = $ExAmt7;
			}else if($ExCcy7==$Currency1){
				$ExAmt7_local = round($ExAmt7*$FXRate1,2);
			}else if($ExCcy7==$Currency2){
				$ExAmt7_local = round($ExAmt7*$FXRate2,2);
			}else if($ExCcy7==$Currency3){
				$ExAmt7_local = round($ExAmt7*$FXRate3,2);
			}
		}else {
			$ExAmt7_local=0;
		}

		$ExCcy8=$ec_row->ExCcy8;
		$ExAmt8=$ec_row->ExAmt8;
		if($ExCcy8 && $ExAmt8>0){
			if($ExCcy8==$LocalCcy){
				$ExAmt8_local = $ExAmt8;
			}else if($ExCcy8==$Currency1){
				$ExAmt8_local = round($ExAmt8*$FXRate1,2);
			}else if($ExCcy8==$Currency2){
				$ExAmt8_local = round($ExAmt8*$FXRate2,2);
			}else if($ExCcy8==$Currency3){
				$ExAmt8_local = round($ExAmt8*$FXRate3,2);
			}
		}else {
			$ExAmt8_local=0;
		}

		$ExCcy9=$ec_row->ExCcy9;
		$ExAmt9=$ec_row->ExAmt9;
		if($ExCcy9 && $ExAmt9>0){
			if($ExCcy9==$LocalCcy){
				$ExAmt9_local = $ExAmt9;
			}else if($ExCcy9==$Currency1){
				$ExAmt9_local = round($ExAmt9*$FXRate1,2);
			}else if($ExCcy9==$Currency2){
				$ExAmt9_local = round($ExAmt9*$FXRate2,2);
			}else if($ExCcy9==$Currency3){
				$ExAmt9_local = round($ExAmt9*$FXRate3,2);
			}
		}else {
			$ExAmt9_local=0;
		}

		$ExCcy10=$ec_row->ExCcy10;
		$ExAmt10=$ec_row->ExAmt10;
		if($ExCcy10 && $ExAmt10>0){
			if($ExCcy10==$LocalCcy){
				$ExAmt10_local = $ExAmt10;
			}else if($ExCcy10==$Currency1){
				$ExAmt10_local = round($ExAmt10*$FXRate1,2);
			}else if($ExCcy10==$Currency2){
				$ExAmt10_local = round($ExAmt10*$FXRate2,2);
			}else if($ExCcy10==$Currency3){
				$ExAmt10_local = round($ExAmt10*$FXRate3,2);
			}
		}else {
			$ExAmt10_local=0;
		}

		$ExCcy11=$ec_row->ExCcy11;
		$ExAmt11=$ec_row->ExAmt11;
		if($ExCcy11 && $ExAmt11>0){
			if($ExCcy11==$LocalCcy){
				$ExAmt11_local = $ExAmt11;
			}else if($ExCcy11==$Currency1){
				$ExAmt11_local = round($ExAmt11*$FXRate1,2);
			}else if($ExCcy11==$Currency2){
				$ExAmt11_local = round($ExAmt11*$FXRate2,2);
			}else if($ExCcy11==$Currency3){
				$ExAmt11_local = round($ExAmt11*$FXRate3,2);
			}
		}else {
			$ExAmt11_local=0;
		}

		$ExCcy12=$ec_row->ExCcy12;
		$ExAmt12=$ec_row->ExAmt12;
		if($ExCcy12 && $ExAmt12>0){
			if($ExCcy12==$LocalCcy){
				$ExAmt12_local = $ExAmt12;
			}else if($ExCcy12==$Currency1){
				$ExAmt12_local = round($ExAmt12*$FXRate1,2);
			}else if($ExCcy12==$Currency2){
				$ExAmt12_local = round($ExAmt12*$FXRate2,2);
			}else if($ExCcy12==$Currency3){
				$ExAmt12_local = round($ExAmt12*$FXRate3,2);
			}
		}else {
			$ExAmt12_local=0;
		}

		$ExCcy13=$ec_row->ExCcy13;
		$ExAmt13=$ec_row->ExAmt13;
		if($ExCcy13 && $ExAmt13>0){
			if($ExCcy13==$LocalCcy){
				$ExAmt13_local = $ExAmt13;
			}else if($ExCcy13==$Currency1){
				$ExAmt13_local = round($ExAmt13*$FXRate1,2);
			}else if($ExCcy13==$Currency2){
				$ExAmt13_local = round($ExAmt13*$FXRate2,2);
			}else if($ExCcy13==$Currency3){
				$ExAmt13_local = round($ExAmt13*$FXRate3,2);
			}
		}else {
			$ExAmt13_local=0;
		}
	}
	
	$EC_tol = $ExAmt1_local+$ExAmt2_local+$ExAmt3_local+$ExAmt4_local+$ExAmt5_local+$ExAmt6_local+$ExAmt7_local+$ExAmt8_local+$ExAmt9_local+$ExAmt10_local+$ExAmt11_local+$ExAmt12_local+$ExAmt13_local;
	$EC_tol_USD=round($EC_tol*$USD_ExRate[$LocalCcy], 2);

	return $EC_tol_USD;
}

function getECAmount_nouse($BRID) {
	global $insightDBLink;
	/*
	 $sql = "SELECT SUM(TotalAmt_USD) totalAmt_USD
	FROM (
			SELECT TotalAmt_USD
			FROM tblExpClaim
			WHERE OTRefNo IN (
					SELECT OTRefNo
					FROM tblOvTrvl
					WHERE ID = (select OTno from tblBRBudget where ID = $BRID) OR MasterOT = (select OTno from tblBRBudget where ID = $BRID))
			UNION
			SELECT TotalAmt_USD
			FROM tblExpClaim
			WHERE BRID = $BRID) ec";
	$ec_res = mysqli_query($insightDBLink, $sql);
	$ec_row = mysqli_fetch_object($ec_res);
	if($ec_row) {
	return $ec_row->totalAmt_USD;
	} else {
	return 0;
	}
	*/		
	$totalAmtUsd = 0;
	
	$sql = "SELECT OTRefNo
                FROM tblOvTrvl
                WHERE ID = (select OTno from tblBRBudget where ID = $BRID) OR MasterOT = (select OTno from tblBRBudget where ID = $BRID)";
	$ot_res = mysqli_query($insightDBLink, $sql);
	while($ot_row = mysqli_fetch_object($ot_res)) {
		$otRefNo = $ot_row->OTRefNo;

		$sql1 = "SELECT SUM(TotalAmt_USD) totalUSD
					FROM tblExpClaim 
					WHERE OTRefNo = '" . $otRefNo . "'
							and FormStatus NOT IN ('C','D','S') ";
		$ec_res = mysqli_query($insightDBLink, $sql1);
		$ec_row = mysqli_fetch_object($ec_res);
		if($ec_row) {
			$ecAmtUsd = $ec_row->totalUSD;
			$totalAmtUsd = $totalAmtUsd + $ecAmtUsd;
		}
	}
	
	$sql = "SELECT TotalAmt_USD
				FROM tblExpClaim
				WHERE BRID = $BRID";
	$ec_res = mysqli_query($insightDBLink, $sql);
	$ec_row = mysqli_fetch_object($ec_res);
	if($ec_row) {
		$ecAmtUsd = $ec_row->TotalAmt_USD;
		
		$totalAmtUsd = $totalAmtUsd + $ecAmtUsd;
	}
	
	return $totalAmtUsd;
}

