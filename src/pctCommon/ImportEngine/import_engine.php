<?php
// File holder Ken @ 17 Dec 2013 Task# ?????

// 2013.12.17:Ken Law:Modify the import engine to accept other module upload



	/*** Import Engine (IE) PHP	-- 24-APRIL-2013 ***/

	flush();
	//echo "Import Engine -- PHP -- " . gmdate("Ymd H:i:s", mktime()+8*3600) . "<br><br>";
	
	// Get current system date
	//$current_date = gmdate("Y-m-d H:i:s", mktime()+8*3600);
	
	//connect to Production DB server
// 	include ('../../phpInc/config.inc.php');     // use config variable
	include_once(getenv("INSIGHT_CONFIG"));
	// include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/config/config.php");



	// include_once($insight_helper_path . "/db_helpers.php");
	// include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/common_helpers.php");
	// include_once($_SERVER["DOCUMENT_ROOT"] . "/pctCommon/helper/getRequest.inc.php");
	include_once($insight_helper_path . "/db_helpers.php");
	include_once($insight_helper_path . "/common_helpers.php");
	include_once($insight_helper_path . "/getRequest.inc.php");
	
	// STRAT
	// Import_mode = 0 -- Application mode
	// Import_mode = 1 -- Background mode
	
	// Getting arguments
	$module = $_POST["module"] ?? '';
	$header_id = $_POST["header_id"] ?? '';
	$import_mode = $_POST["import_mode"] ?? '';
	
	// Handle the uploaded file
	$target_path = "";
	if ($module == 'IS')
		$target_path = "/inv_sttl_file/";
	else if ($module == 'PR')
		$target_path = "/uploadDoc/";
	$target_path = $target_path . basename( $_FILES['uploadedfile']['name'] ?? ""); 
	
	$PR_dataset = [];
        $IS_dataset = [];
	
	if(move_uploaded_file(($_FILES['uploadedfile']['tmp_name'] ?? ""), $target_path)){
	
		$filename = basename( $_FILES['uploadedfile']['name']);

		if($import_mode == 1){
		
			//$db_name = $_POST["DB_Name"];
			//$filename = $_POST["Filename"];
			$offset = 2;							
			$header_id = (INT)substr($filename, $offset, strlen($filename)-4);
		}
	
		// Contains item or not before import data
/*		
		if (chkItemRecord($module, $header_id)) {
			
			if ($module == 'IS') {
				
				echo "<div id=\"DialogWarning\">Error !! IS No. " . $header_id . " contains an item before, please use the input menu to entry item.</div>"; 
				return -1;
				
			} else if ($module == 'PR') {
				
				echo "<div id=\"DialogWarning\">Error !! PR No. " . $header_id . " contains an item before, please use the input menu to entry item.</div>"; 
				return -1;
				
			}
			
		}
*/		

		// Main program entry point
		if ($module == 'IS') {
		
			$sql_result = mysqli_query($insightDBLink, "SELECT ID FROM tblISInvoice WHERE ID = " . $header_id);
			if (mysqli_num_rows($sql_result)){
			
				import_engine($filename, $module, $header_id, $import_mode, $target_path);
			}
			else{ 
				
				UNLINK($target_path);
				echo "<div id=\"DialogWarning\"><font color=red>Error !! IS No. " . $header_id . " does not exist, please input again.</font></div>"; return -1; 
			}
		
		} else if ($module == 'PR') {
			
			$sql_result = mysqli_query($insightDBLink, "SELECT ID FROM tblPCReqHeader WHERE ID = " . $header_id);
			if (mysqli_num_rows($sql_result)){
			
				import_engine($filename, $module, $header_id, $import_mode, $target_path);
			}
			else{ 
				
				UNLINK($target_path);
				echo "<div id=\"DialogWarning\"><font color=red>Error !! PR No. " . $header_id . " does not exist, please input again.</font></div>"; return -1; 
			}
			
		}
		
	} 
	else{
		// if($import_mode == 1){		
			// if($filename == "" && $import_mode == 1){			
				// return -1;
			// }
			// else if($filename != "" && $import_mode == 1){			
				// // Main program entry point
				// import_engine($filename, $module, $header_id, $import_mode);		
			// }
		// }
		$sql_result = mysqli_query($insightDBLink, "SELECT * FROM tblIEDocument ORDER BY ID DESC LIMIT 1");		
		if (mysqli_num_rows($sql_result)){
		
			while($value = mysqli_fetch_object($sql_result)){

				$module = $value->Module;
				$db_name = $value->DBName;
				$header_id = $value->HeaderID;
				$import_mode = $value->ImportMode;
				$filename = $value->Filename;
				$status = $value->Status;
				$uploaded_timestamp = $value->LastUpdDate;
			}
			$back_timestamp = gmdate( "Y-m-d H:i:s", time()+(8*3600)-(3*60) );// 3 minutes counter
			
			//echo "back_timestamp - value: " . $back_timestamp  ."<br>";
			//echo "uploaded_timestamp - value: " . $uploaded_timestamp  ."<br>";
			
			$sql_result = mysqli_query($insightDBLink,  "SELECT '" . $back_timestamp . "' <= '" . $uploaded_timestamp . "' AS result" );
			while($value = mysqli_fetch_object($sql_result)){ $return_code = $value->result; }
			
			//echo "return_code - value: " . $return_code  ."<br>";
			//echo "SQL - value: " . ("SELECT '" . $back_timestamp . "' <= '" . $uploaded_timestamp . "' AS result")  ."<br>";
			
			if( $return_code == 1 ){
			
				if( $status == 0 && $filename != "" && $import_mode > 0 ){
					if (!str_ends_with($target_path, $filename))
						$target_path = $target_path.$filename;
					import_engine($filename, $module, $header_id, $import_mode, $target_path);
				}
			}
			else{
			
				echo "<div id=\"DialogWarning\"> <font color=red>Error !! No uploaded file was found, please upload again. </font><br></div>";
			}
		}
		else{ 
		
			//echo "There was no file to import.";  
			if($import_mode == 0){
			
					echo "<div id=\"DialogWarning\"><font color=red>Error !! There was an error uploading the file, please try again.</font></div>";
			}			
		}
 
	}	
	
	// Main Function
	function import_engine($filename, $module, $header_id, $import_mode, $target_path){
	global $insightDBLink;
	global $updatedatabase;
	
		//$db_name = "WebHynet";
		$db_name = $updatedatabase;

		// File format checking
		if(checkFileType($filename, $target_path) == "csv"){
			$dataset = getFileContent($filename, checkFileType($filename, $target_path), $target_path);
		}
		else{
			echo checkFileType($filename, $target_path) . "<br><br>";	
			UNLINK($target_path);
			return -1;
		}
		
		if (sizeof($dataset) <= 1) {
			
			// check if it is empty or only contain header row
			
			echo "<div id=\"DialogWarning\"><font color=red>Error !! The file is empty, please upload again.</font></div>"; 
			return -1;
				
		}
				
		
		// Get table structure
		$element = getElement($module);
		if(sizeof($element) <= 0){ echo "<div id=\"DialogWarning\"><font color=red>Error !! No data template for this module.</font></div>"; return -1; }
		
		$existedPRitem = [];
		// Log the existed items before import data
		$existedPRitem = chkItemRecord($module, $header_id);
		
		
		// CHECKING OF DATA CONSTRAINTS
		
		//$dc_count = 0; 
		$dc_checking_result = verifyDataConstraint($dataset, $element);
		/*
		foreach ($dc_checking_result[0] as $key => $list){	
				
			if($key > 1){ 
				for($field=0; $field< sizeof($list);$field++){ 
					if( $list[$field] < 0 ){ 
						$dc_count++; 
					} 
				} 
			}
		}
		*/
		
		// CHECKING OF BIZ CONSTRAINTS
		//$bc_count = 0; 
		$bc_checking_result = verifyBizConstraint($dataset, $element, $module, $header_id);
		//$bc_checking_result = array();
		// foreach ($bc_checking_result[0] as $key => $list){		
			// if($key > 1){ for($field=0; $field< sizeof($list); $field++){ if( $list[$field] < 0 ){ $bc_count++; } } }
		// }
		
		//if($dc_count == 0)
		{
			
			// Arguments checking
			if(TRIM($module) == "" || (sizeof($element)) <= 0 || (sizeof($dataset)) <= 0 || $header_id <= 0){
				
				echo "No data.";
				UNLINK($target_path);
				return -1;
			}
			
			// *** Import to Database >>>>
			if($import_mode == 1){ //background mode (import to dababase)
			
				//Building SQL statements
				$sql_array = generateSQL($module, $dataset, $element, $db_name, $header_id);				
			
				$x = 0;
				$sql_error = [];
			
				foreach ($sql_array as $key => $statement_list) {
			
					if($key > 1)
					{
						//echo "Statement ".($key+1).": " . $statement_list[$key];
						//echo "Statement ".($key).": " . $sql_array[$key];
						//echo "<br>";

						// Preparing SQL statements
						if( generateRecord( $sql_array[$key] ) == -1 ) {
						
							// Recording SQL errors
							$sql_error[$key] = $sql_array[$key];
							$x++;
						}						
						
						// Try to show the import errors
						if( sizeof($sql_error) > 0 ){
						
							for($index=0; $index < sizeof($sql_error); $index++){
													
								echo "<br><font color=red>ERROR SQL - row no.: " . $key . "  ==>  " . $sql_array[$key] . "</font><br>";
							}
						}
					}
				}		
                                //exit();
				
				// Checking sql processing result
				if(sizeof($sql_error) > 0){
					
					echo "<font color=red>Error !! Data was invalid to import, please check again.</font><br>";
				
					return $sql_error;
				}
				else{
				
					if (file_exists($filename)){
					
						UNLINK($target_path);
						mysqli_query($insightDBLink, "DELETE FROM tblIEDocument");					
						//mysqli_query($insightDBLink, "DELETE FROM " . $db_name . ".tblIEDocument");					
					}
					
					// Delete existed item finally
					
					foreach ($existedPRitem as $key => $list){
						//echo "existed PR item: ".($key).": " . $existedPRitem[$key] . "<br>";
					 	if($module == "PR")
							$sql_result = mysqli_query($insightDBLink, "delete from tblPCReqDetail where id = " . $existedPRitem[$key]); //PR
					 	else if ($module == 'IS') //task 44572 by Linus
							$sql_result = mysqli_query($insightDBLink, "delete from tblISInvLine where id = " . $existedPRitem[$key]); //IS
						
					 	if ($sql_result < 0){
					 	
					 		echo "<div id=\"DialogWarning\">Error !! There is an error while deleting item id : " . $existedPRitem[$key] . "<br>";
					 	}
					}
					
					echo "Success !! The uploaded document has been imported successfully.";
				}
			}
			
			// *** Generate Grid (HTML) >>>>
			if($import_mode == 0){ //application mode (return HTML)
							
				//echo generateHTML($module, $dataset, $db_name, $header_id, $import_mode, $filename, $dc_checking_result);
				
				echo generateHTML($module, $dataset, $db_name, $header_id, $import_mode, $filename, $dc_checking_result, $bc_checking_result, $target_path);
			}
			
		}
		
	}
	
	// Check File Format
	function checkFileType($filename, $target_path){

		if (file_exists($target_path))
		{			
			if(strpos($filename, ".csv") == (strlen($filename) - strLen(".csv")))
			//if(strpos($filename, ".csv"))
			{
				return "csv";
			}
			//else if(strpos($filename, ".xls") || strpos($filename, ".xlsx")){
			
				//return "xls";
			//}
			else
			{
				return "Invalid file format.";
			}	
		}		
		else
		{
			return "File does not exist.";
		}
		

/*		
$validtypes = array( 
"app" => array("xls", "xlsx"), 
"txt" => array("csv"), 
);
$found = false; 
foreach($validtypes as $type_name => $type_array) { 

	if(in_array($extension,$type_array)){ 

		$found = true; 
		break; 

	} // EndIf found type 

} // EndForEach allowed type 

if($found) 
{ 
	return 0;

}
else 
{ 
	return -1;
}*/
		
	}
	
	// Digest file
	function getFileContent($filename, $file_type, $target_path){

		//Array to store spreadsheet
		$dataset = [];
			
			//Process CSV
			//Less than 20 MB
			if( $file_type == "csv" )
			{
				if( (filesize($target_path) / 1024 / 1024) <= 20 )
				{
					//echo "File size of " . $filename . " : " . (filesize($filename) / 1024 / 1024) . "MB <br><br>";	

					ini_set('auto_detect_line_endings',TRUE);
					
					//Open file handler
					$row = 1;
					$handle = fopen($target_path, "r");
					while (($data = fgetcsv($handle, 10000, ",")) == TRUE) 				
					{
						$num = count($data);
						
						// Check blank row was found
						$invalid_row = 0;
						for($cell=0; $cell < $num; $cell++){
						
							if(TRIM($data[$cell]) != ""){ $invalid_row++;}
						}						
						
						// Building dataset
						if($num > 1 && $invalid_row > 0){
							for ($cell=0; $cell < $num; $cell++) 
							{	

								if ($row==1){
									
									$data[$cell]=str_replace("*", "", $data[$cell]); // remove '*' in header line
								}
								
								
								//Multiple Dimension Array
								$dataset[$row][$cell] = $data[$cell];
							}
						}
						
						$row++;					
					}
					
					//Close file handler
					fclose($handle);
					
						// //For testing purpose
						// ///*
						// echo "<br><br><br><br>";
						// foreach ($dataset as $key => $list) {
						
							// echo "Row No.: " . $key;
							// echo "<br>";
							// for($field=0; $field< sizeof($list); $field++)
							// {
								// //echo "Field ".($field+1).": " . $list[$field] . ", ";
								// echo "Column ".($field+1).": " . $list[$field];
								// echo "<br>";
							// }
							// echo "<br><br>";
						// }
						// //*/
						
					return $dataset;
				}
				
				
				//Larger than 1 MB
				else
				{	
					//echo "File size of " . $filename . " : " . (filesize($filename) / 1024 / 1024) . "MB <br><br>";	
					echo "Error !! File size is too large to upload, please check. (Current Limitation: 1MB)";
					UNLINK($target_path);					
				}			
			} else {
				
				echo "<div id=\"DialogWarning\"><font color=red>Error !! Import function is only support *.csv file, please convert it and try again.</font></div>";
				
			}
		
			
			//Process Excel
			if( $file_type == "xls"){}
			
	}
	
	// Proceed data checking (Part 1) -- Data Constraints
function verifyDataConstraint($dataset, $element){
		global $insightDBLink;
		
		// Data constraint checking flag
		$verify_data_constraint = 0;
		
		// Array of DC checking result
		$dc_checking_result = [];
		
		// Array for document header row
		$header_row = [];
		
		// Field mapping process
		foreach ($dataset as $key => $list){
		
			if($key == 1){ //<<  is header row
			
				for($field=0; $field< sizeof($list); $field++){ // horizontal scanning				
					
					for($index=0; $index<sizeof($element); $index++){ // vertical scanning						
						
						if($element[$index][0] == $list[$field]){

							// To know where is the index in the data structure							
							$element_map_to[ $list[$field] ] = $index;//zero based <<<===<<<<<<<<<<<<<<<<<<<<<<<<<<<
						}
			
						//$header_row[$field] = TRIM($list[$field]);
					}
					
					$header_row[$field] = TRIM($list[$field]);
					
					//echo "(Dataset) Row No.: " . $key . " - Value is: " . $list[$field] . " <br>";
					//echo "element_map_to is : " . $element_map_to[ $list[$field] ] . " <br><br>";
				}
			}			
			if($key >= 2){ //<< is NOT header row
				for($field=0; $field< sizeof($list); $field++){ // horizontal scanning					
					// Get data constraint settings					
					// ElementType
					$ElementType = $element[ $element_map_to[$header_row[$field]] ][2];
					if( TRIM($ElementType) == "" || $ElementType === NULL ){							
						$verify_data_constraint--;
					}
					//echo $header_row[$field] . " : " . $list[$field] . "  ____ ElementType - Value: " . $ElementType . " <br>";
					
					//echo "########## " . $element[ $element_map_to[$header_row[$field]] ][1] . " <br>";
					
					// DataType
					$DataType = $element[ $element_map_to[$header_row[$field]] ][4];
					if( TRIM($DataType) == "" || $DataType === NULL ){
						$verify_data_constraint--;
					}
					// ConstraintType
					$ConstraintType = $element[ $element_map_to[$header_row[$field]] ][5];
					if( TRIM($ConstraintType) == "" || $ConstraintType === NULL ){
						$ConstraintType = "";
					}
					// DefaultNull
					$DefaultNull = $element[ $element_map_to[$header_row[$field]] ][6];
					if( TRIM($DefaultNull) == "" || $DefaultNull === NULL ){
						$DefaultNull = 0;								
					}	
					// DefaultValue
					$DefaultValue = $element[ $element_map_to[$header_row[$field]] ][7];
					if( $DefaultValue === NULL ){
						$DefaultValue = "";
					} else if (Trim($list[$field]) == "") {
					
						$list[$field] = $element[ $element_map_to[$header_row[$field]] ][7];
					}
					
					// Capacity
					$Capacity = $element[ $element_map_to[$header_row[$field]] ][9];
					if( TRIM($Capacity) == "" || $Capacity === NULL ){
						$Capacity = "";
					}							
					// CapacityMask
					$CapacityMask = $element[ $element_map_to[$header_row[$field]] ][10];
					if( TRIM($CapacityMask) == "" || $CapacityMask === NULL ){
						$CapacityMask = "";
					}							
					// RangeFrom
					$RangeFrom = $element[ $element_map_to[$header_row[$field]] ][11];
					if( TRIM($RangeFrom) == "" || $RangeFrom === NULL ){
						$RangeFrom = "";
					}
					// RangeTo
					$RangeTo = $element[ $element_map_to[$header_row[$field]] ][12];
					if( TRIM($RangeTo) == "" || $RangeTo === NULL ){
						$RangeTo = "";
					}
					// Exception
					$Exception = $element[ $element_map_to[$header_row[$field]] ][13];
					if( TRIM($Exception) == "" || $Exception === NULL ){
						$Exception = "";
					}
					
						// *******************************************
						// *******************************************
						// *** Data constraint verifications *** Start						
						if( $ElementType == "UserData"){

								// Using DefaultNull
								if((trim($list[$field]) == "" || $list[$field] === NULL) && $DefaultNull == 1){
								
									//echo "list[field]:" . $list[$field] . "<br>";
									
									$dc_checking_result[0][$key][$field] = -1;
									$dc_checking_result[1][$key][$field] = "Mandate field";
								}
								// Other data type
								else{
									// Numeric data type
									if( strtoupper($DataType) == "INTEGER" || strtoupper($DataType) == "INT" ||
										strtoupper($DataType) == "BIGINT" || strtoupper($DataType) == "TINYINT" ||						
										strtoupper($DataType) == "DECIMAL" || strtoupper($DataType) == "DOUBLE" || 
										strtoupper($DataType) == "FLOAT")
									{
										
										//echo "list[field]:" . $list[$field] . "<br>";
										
										if( is_numeric( $list[$field] ) ){ 	
											
											$dc_checking_result[0][$key][$field] = 0;
										
											// Capacity
											if( $Capacity != NULL || (strlen($Capacity) >= 3 && strpos($Capacity, ".") >= 1) )
											{
												$int_portion_capacity = getIntCount( floatValue( $Capacity ) );
												$int_portion_capacity = (INT)substr($Capacity, 0, $int_portion_capacity);												
												$int_mask = "";
												for($i=1; $i<=$int_portion_capacity; $i++){ $int_mask = $int_mask . "9"; }
												$int_mask = (INT)$int_mask;
												
												$dec_portion_capacity = getDecCount( floatValue( $Capacity ) );
												$dec_portion_capacity = (INT)substr($Capacity, (strpos($Capacity, ".")+1), $dec_portion_capacity);
												$dec_mask = ""; 
												for($i=1; $i<=$dec_portion_capacity; $i++){ $dec_mask = $dec_mask . "9"; }
												$dec_mask = (INT)$dec_mask;
																								
												//$int_portion = getIntCount( floatValue( $list[$field] ) );
												//$dec_portion = getDecCount( floatValue( $list[$field] ) );
																								
												if( floatValue($int_mask.$dec_mask) >= floatValue($list[$field]) ){
												
													$dc_checking_result[0][$key][$field] = 0;
												}
												else{
												
													$dc_checking_result[0][$key][$field] = -1;
													$dc_checking_result[1][$key][$field] = "Value exceeded capacity limit - Constraint: ". $Capacity;
												}
											}
											// // CapacityMask
											// if( $CapacityMask != NULL || (strlen($CapacityMask) >= 3 && strpos($CapacityMask, ".") >= 1) )
											// {
												// $int_portion_capacity = getIntCount( floatValue( $CapacityMask ) );
												// $dec_portion_capacity = getDecCount( floatValue( $CapacityMask ) );												
												// $int_portion = getIntCount( floatValue( $list[$field] ) );
												// $dec_portion = getDecCount( floatValue( $list[$field] ) );												
												// //      123456.3456
											    // //00009 900099.9900  999
																							
											    // $CapacityMask = (string)(floatValue($CapacityMask) + 100000000000000000000.000000000000000000001);	
												// //$CapacityMask = str_replace($CapacityMask, "", "1");
												// //$CapacityMask[0] = "";
												
												// $doc_value = (string)(floatValue(TRIM($list[$field])) + 100000000000000000000.000000000000000000001);
																		
												// for($i=1; i<(strlen($CapacityMask)-1); $i++){
													
														// if( TRIM(substr($doc_value, i, 1)) != "."  &&  TRIM(substr($CapacityMask, i, 1)) == "9" ){
														
															// //$doc_value
														   // //if ( isset($doc_value[$i]) ) 
														   // { 
														   
																// $doc_value[$i] = "0";
														   // }
														// }														
												// }
												// $doc_value = (string)(floatValue(doc_value) - 100000000000000000000.000000000000000000001);
												
												// $dc_checking_result[0][$key][$field] = 0;
											// }
										}
										else{ 
										
											$dc_checking_result[0][$key][$field] = -1; 
											$dc_checking_result[1][$key][$field] = "Not a numeric value."; 
										}
									}
									// Date or Datetime data type
									else if( strtoupper($DataType) == "DATE" || strtoupper($DataType) == "DATETIME" )
									{

										//echo "b4 list[field]:" . $list[$field] . "<br>";
										
										$list[$field] = str_replace("D","",$list[$field]);
										
										//echo "after list[field]:" . $list[$field] . "<br>";
										
										if( checkDateFormat($list[$field]) == True ){
										
											$dc_checking_result[0][$key][$field] = 0;
										
											if( $RangeFrom != NULL && $RangeFrom !== ""  ){	
											
												$sql_result = mysqli_query($insightDBLink,  ("SELECT '" . $RangeFrom . "' <= '" . $list[$field]) . "' AS result" );
												while($value = mysqli_fetch_object($sql_result)){ $return_code = $value->result; }
																						
												if( $return_code == 1 ){
												
													$dc_checking_result[0][$key][$field] = 0;
													
													
													//if(  checkDateFormat($RangeTo, 'Y-m-d H:i:s') >=  checkDateFormat($list[$field], 'Y-m-d H:i:s') ){
													if( $RangeTo != NULL && $RangeTo !== "" ){
													
														$sql_result = mysqli_query($insightDBLink,  ("SELECT '" . $RangeTo . "' >= '" . $list[$field]) . "' AS result" );
														while($value = mysqli_fetch_object($sql_result)){ $return_code = $value->result; }
																									
														if( $return_code == 1 ){ $dc_checking_result[0][$key][$field] = 0; }
														else{
															$dc_checking_result[0][$key][$field] = -11;
															$dc_checking_result[1][$key][$field] = "Not a valid date (range).";
														}
													}												
												
												}
												else{													
													$dc_checking_result[0][$key][$field] = -1;
													$dc_checking_result[1][$key][$field] = "Not a valid date format.";
												}													
											}

										}
										else{
										
											$dc_checking_result[0][$key][$field] = -1;
											$dc_checking_result[1][$key][$field] = "Not a valid date.";										
										}
									}
									// Non-numeric data type ( Eg.: TEXT, CHAR, VARCHAR etc.)
									else
									{
										$dc_checking_result[0][$key][$field] = 0;
										
										// Capacity
										if( $Capacity != NULL && TRIM($Capacity) != "" ){
										
											if( strlen($list[$field]) <= floatValue($Capacity) || TRIM($list[$field]) == "" ){
											
												$dc_checking_result[0][$key][$field] = 0;
											}
											else{
											
												$dc_checking_result[0][$key][$field] = -1;
												$dc_checking_result[1][$key][$field] = "Total number of input character (" . strlen($list[$field]) . ") over the predefined limit(" . (INT)$Capacity . ").[".floatValue($Capacity)."]";
											}
										}
									}
								}
						}
						else
						{$dc_checking_result[0][$key][$field] = 0;}
						// *** Data constraint verifications *** End						
						// *******************************************
						// *******************************************
						
				}// End for()
			}
		}	
		
		// Testing
		//foreach ($dc_checking_result[0] as $key => $list){
		
			// if($key > 1){ //<< is NOT header row
			
				// for($field=0; $field< sizeof($list); $field++){ // horizontal scanning	

					// echo "<< " . $list[$field] . " >>";
				// }
			// }
			// echo "<br>";
		// }
		// foreach ($dc_checking_result[1] as $key => $list){
		
			// if($key > 1){ //<< is NOT header row
			
				// for($field=0; $field< sizeof($list); $field++){ // horizontal scanning	

					// echo "<< " . $list[$field] . " >>";
				// }
			// }
			// echo "<br>";
		// }			
		
		return $dc_checking_result;
	}	
	
	// Proceed data checking (Part 2) -- Biz Constraints
function verifyBizConstraint($dataset, $element, $module, $header_id){ 
		global $insightDBLink;
		
		// Data constraint checking flag
		$verify_biz_constraint = 0;
		
		// Array of DC checking result
		$bc_checking_result = [];
		
		// Array for document header row
		$header_row = [];
		
		
		//For PR
		$orderType = "";
		$costType = "";
		$costCat = "";
		$costSubcat = "";
		$nomenclate = "";
		$billingType = "";
		$actionTypeElm = [];
		$itemCatElm = [];
		$billingTypeElm = [];
		$signedCo = "";
                
                //For IS
                $IS_signedCo = "";
                $defaultGLCode = "";
		
		//echo "Check BizConstraint Start ... <br>";
		
		if ($module == 'PR') {
			
			global $PR_dataset;
			$PR_dataset = $dataset;
			
			// Prepare Action Type array
			$sql_result = mysqli_query($insightDBLink, "select codevalue, description from tblGenericCode where codetype = 'ActionCode' and activeflag = 1");
				
			if (mysqli_num_rows($sql_result)){
					
				$index = 0;
					
				while($value = mysqli_fetch_object($sql_result)){
			
					$actionTypeElm[$index][0] = $value->codevalue;
					$actionTypeElm[$index][1] = $value->description;
			
					$index ++;
			
				}
			}
			
			// Get Cost Type
			$sql_result = mysqli_query($insightDBLink, "select CostType,SignedCo from tblPCReqHeader where id=" . $header_id);
			
			if (mysqli_num_rows($sql_result)) {
			
				while($value = mysqli_fetch_object($sql_result)){
					$costType = $value->CostType;
					$signedCo = $value->SignedCo;
				}
					
			}
				
			//echo "header_id = " . $header_id ."<br>";
			//echo "costType = " . $costType . "<br>";
				
			// Get the list of Item Category
			$sql_result = mysqli_query($insightDBLink, "select codevalue, description from tblGenericCode where CodeType = 'CostCat' and CodeCat in (select CodeCat from tblGenericCode where CodeType = 'CostType' and CodeValue = '" . $costType . "' and ActiveFlag = 1) and ActiveFlag = 1");
				
			if (mysqli_num_rows($sql_result)){
					
				$index = 0;
					
				while($value = mysqli_fetch_object($sql_result)){
						
					$itemCatElm[$index][0] = $value->codevalue;
					$itemCatElm[$index][1] = $value->description;
						
					$index ++;
				}
			}			
			
			
			
			// Get Billing Type
			$sql_result = mysqli_query($insightDBLink, "SELECT codevalue, description FROM tblGenericCode WHERE CodeType = 'BillPeriod'");
			
			if (mysqli_num_rows($sql_result)){
					
				$index = 0;
					
				while($value = mysqli_fetch_object($sql_result)){
			
					$billingTypeElm[$index][0] = $value->codevalue;
					$billingTypeElm[$index][1] = $value->description;
						
					$index ++;
			
				}
			}
			
			// Get UOM Type task 44572 by Linus
			$sql_result = mysqli_query($insightDBLink, "SELECT codevalue, description FROM tblGenericCode WHERE CodeType = 'UOM' AND ActiveFlag = 1");
			
			if (mysqli_num_rows($sql_result)){
					
				$index = 0;
					
				while($value = mysqli_fetch_object($sql_result)){
			
					$UOMTypeElm[$index][0] = $value->codevalue;
					$UOMTypeElm[$index][1] = $value->description;
						
					$index ++;
			
				}
			}
				
		} else if ($module == 'IS'){
                    
			global $IS_dataset;
			$IS_dataset = $dataset;
                        
                                               
			$sql = mysqli_query($insightDBLink, "SELECT SignedCo FROM tblISInvoice WHERE ID = " . $header_id);
			$IS_signedCo = dbSelectColumnFromQuery($sql, 0);
			
			// Get Tax Info task 44572 by Linus
			$sql_result = mysqli_query($insightDBLink, "SELECT codevalue, description FROM tblGenericCode WHERE CodeType = 'ISTrxCode' AND ActiveFlag = 1");
			
			if (mysqli_num_rows($sql_result)){
					
				$index = 0;
					
				while($value = mysqli_fetch_object($sql_result)){
			
					$TaxInfoElm[$index][0] = $value->codevalue;
					$TaxInfoElm[$index][1] = $value->description;
						
					$index ++;
			
				}
			}
        }
		
		$row = 1;
		// Field mapping process
		foreach ($dataset as $key => $list){
			
			if($key == 1){ //<<  is header row
				$PR_dataset[$key][1] = "Budget Item ID";
				for($field=0; $field< sizeof($list); $field++){ // horizontal scanning
						
					for($index=0; $index<sizeof($element); $index++){ // vertical scanning
		
						if($element[$index][0] == $list[$field]){
		
							// To know where is the index in the data structure
							$element_map_to[ $list[$field] ] = $index;//zero based <<<===<<<<<<<<<<<<<<<<<<<<<<<<<<<
						}
					}

					$header_row[$field] = TRIM($list[$field]);
					
					//echo "($Dataset) Row No.: " . $key . " - Value is: " . $list[$field] . " <br>";
					//echo "element_map_to is : " . $element_map_to[ $list[$field] ] . " <br>";
					//echo "header_row[field] is : " . $header_row[$field] . "<br>";
				}
			}
			
			if($key >= 2){ //<< is NOT header row
					
				if ($module == 'IS') {	
					
					for($field=0; $field< sizeof($list); $field++){ // horizontal scanning

						if ($field == 10) {

							if ($list[$field] == null || $list[$field] == '') {

								$defaultGLCode = getLineGLCode($IS_signedCo, $list[9]);
								$IS_dataset[$row][$field] = $defaultGLCode;

								//echo "Tax Flag : " . $list[9] . "<br><br>";
								//echo "defaultGLCode : " . $defaultGLCode . "<br><br>";
								
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $defaultGLCode;                                                

							} else {
								
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $list[$field];                                                 
								
							}

						}
						// Check Tax Info task 44572 by Linus
						if ($field == 9) {
							
							$mFlag = 0;
							if($list[$field] != '0' && $list[$field] != null && $list[$field] != ''){
								for($index=0; $index<sizeof($TaxInfoElm); $index++){
									if(strtoupper($TaxInfoElm[$index][1]) === strtoupper($list[$field])){
										$TaxInfo = $TaxInfoElm[$index][0];
										$mFlag = 1;
										$IS_dataset[$row][$field] = $TaxInfo;
									}
								}
							}else{
								$mFlag = 1;
							}
							
							if($mFlag == 1){
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $list[$field];
							}
							else{
								$bc_checking_result[0][$key][$field] = -1;
								$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
							}							
							
						} // End check Tax Info
						if ($field == 11) { // task 44572 by Linus
							if ($list[$field] != null && $list[$field] != '') {
								$mFlag = 0;

								if ($list[6] >=0) 	//amount+ve 
									$sql_result = mysqli_query($insightDBLink, "SELECT TaxCode FROM tblRefVATCode WHERE SignedCo = '" . $IS_signedCo . "' And  Category IN ('AP','ALL') AND ChargeType IN ('Charge','ALL')");
								else				//amount-ve 
									$sql_result = mysqli_query($insightDBLink, "SELECT TaxCode FROM tblRefVATCode WHERE SignedCo = '" . $IS_signedCo. "' And  Category IN ('AP','ALL') AND ChargeType IN ('CreditNote','ALL')");
								
								
								if (mysqli_num_rows($sql_result)){
									while($value = mysqli_fetch_object($sql_result)){
										if($value->TaxCode == $list[$field]){
											//echo $value->TaxCode." ".$list[$field];
											$mFlag = 1;
											break;
										}
									}
								}
								
								if($mFlag == 1){
										
									$bc_checking_result[0][$key][$field] = 0;
									$bc_checking_result[1][$key][$field] = $list[$field];
								}
								else{
										
									$bc_checking_result[0][$key][$field] = -1;
									$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
								}
							}
						}

					}
					
					
				} else if ($module == 'PR') {
					
					for($field=0; $field<sizeof($list); $field++){ // horizontal scanning
					
					// Start check business logic
					
						$srNum = $list[5];
						
						//echo "Line num = " . $list[0] . "<br>";
						
						
						//check Budget Line Item task 44572 by Linus
						if($field == 1) {
							$mFlag = 0;
							$LineID = 0;
							$sql_result = mysqli_query($insightDBLink, "SELECT BudgetID FROM tblPCReqHeader WHERE ID = " . $header_id);
						
							if (mysqli_num_rows($sql_result)){
								$row_result = mysqli_fetch_array($sql_result);
								if($row_result['BudgetID'] != 0 && $row_result['BudgetID'] != null){ 
									if($list[$field] != "" && $list[$field] != null){
										$sql_result2 = mysqli_query($insightDBLink, "SELECT ID FROM tblBRBudgetLine WHERE BRID = " . $row_result['BudgetID'] ." AND LineNum = ".$list[$field] );
										
										if(mysqli_num_rows($sql_result2)){
											$row_result2 = mysqli_fetch_array($sql_result2);
											$LineID = $row_result2['ID'];
											$mFlag = 1;
										}
									}else
										$mFlag = 1;
								}else{//no budget id linked
									if($list[$field] == "" || $list[$field] == null || $list[$field] == 0)
										$mFlag = 1;
									else
										$mFlag = 2;
								}
							}
							$PR_dataset[$row][$field] = $LineID;
							if($mFlag == 1){
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $LineID;
							}
							else{
									
								$bc_checking_result[0][$key][$field] = -1;
								if($mFlag == 0)
									$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
								else
									$bc_checking_result[1][$key][$field] = "No Budget ID is linked in this PR.";
							}
						}
											
						
						// Check Action Type
						if ($field == 2) {
							
							$mFlag = 0;
							
							//echo "Action Type = " . $list[$field] . "<br>";

							
							for($index=0; $index<sizeof($actionTypeElm); $index++){
								
								//echo "actionTypeElm[". $index . "][0] = " . $actionTypeElm[$index][0] . "<br>";
								//echo "actionTypeElm[". $index . "][1] = " . $actionTypeElm[$index][1] . "<br>";
								
								//$orderType = "";

								if(strtoupper($actionTypeElm[$index][1]) === strtoupper($list[$field])){
									$orderType = $actionTypeElm[$index][0];
									$mFlag = 1;

									//echo "orderType = " .$orderType."<br>";
									//echo "b4: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
									
									$PR_dataset[$row][$field] = $orderType;
									
									//echo "after: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
										
								}
							}
							
							if($mFlag == 1){
							
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $list[$field];
							}
							else{
							
								$bc_checking_result[0][$key][$field] = -1;
								$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
							}							
							
						} // End check Action Type
						
						// Check Old Cost Item
						if ($field == 4) {
							
							$list[$field] = floor(str_replace("cI", "", str_replace("Ci", "", str_replace("CI", "", str_replace("ci", "", $list[$field])))));

							$PR_dataset[$row][$field] = $list[$field];
							
							$bc_checking_result[0][$key][$field] = 0;
							$bc_checking_result[1][$key][$field] = $list[$field];
								
							
						} // End Old Cost Item
						
						
						
						// Check Item Category
						if ($field == 7) {
								
							$mFlag = 0;
							
							//echo "Item Category = " . $list[$field] . "<br>";
								
							for($index=0; $index<sizeof($itemCatElm); $index++){
								
								//echo "itemCatElm[index][1] = " . $itemCatElm[$index][1] . "<br>";
								
								//$costCat = "";
						
								if(strtoupper($itemCatElm[$index][1]) === strtoupper($list[$field])){

									$costCat = $itemCatElm[$index][0];
									$mFlag = 1;
									
									//echo "costCat = " .$costCat."<br>";
									//echo "b4: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
										
									$PR_dataset[$row][$field] = $costCat;
										
									//echo "after: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
										
								}
							}
							
							if($mFlag == 1){
									
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $list[$field];
							}
							else{
									
								$bc_checking_result[0][$key][$field] = -1;
								//$bc_checking_result[1][$key][$field] = "Value is not a member of " . $costCat . ".";
								$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
							}
								
						} // End check Cost Type						

						
						// Check Item Sub-Category
						if ($field == 8) {
						
							$codeElm = [];
								
							$sql_result = mysqli_query($insightDBLink, "select codevalue, description from tblGenericCode where CodeType = 'CostSubCat' and CodeCat = '" . $costCat . "' and ActiveFlag = 1");
							
							//echo "costCat = " . $costCat . "<br>";
							
							if (mysqli_num_rows($sql_result)){
									
								$index = 0;
									
								while($value = mysqli_fetch_object($sql_result)){
									
									$codeElm[$index][0] = $value->codevalue;
									$codeElm[$index][1] = $value->description;
									
									//echo "codeElm[index][1] = " . $codeElm[$index][1] . "<br>";
									
									$index ++;
								}
							}
								
							$mFlag = 0;
								
							for($index=0; $index<sizeof($codeElm); $index++){
								
								if(strtoupper($codeElm[$index][1]) === strtoupper($list[$field])){
										
									$costSubcat = $codeElm[$index][0];
									$mFlag = 1;

									//echo "costSubcat = " .$costSubcat."<br>";
									//echo "b4: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
									
									$PR_dataset[$row][$field] = $costSubcat;
									
									//echo "after: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
														
								} 
							}
							
							if($mFlag == 1){
									
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $list[$field];
							}
							else{
								
								$lastfield = $field - 1;
								
								$bc_checking_result[0][$key][$field] = -1;
								
								if ($bc_checking_result[0][$key][$lastfield] == 0) {
									$bc_checking_result[1][$key][$field] = "Value is not a Sub Category of ". $bc_checking_result[1][$key][$lastfield] .".";
								} else {
									$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
								}
								
							}
								
						} // End check Item Sub-Category						
						
										

						// Check Nomenclate
						if ($field == 9 && ($list[$field] != null || $list[$field] != '')) {
						
							$codeElm = [];
							
							//echo "costSubcat = " . $costSubcat . "<br>";
						
							//$sql_result = mysqli_query($insightDBLink, "select n.codeValue, n.description from tblGenericCode n, tblGenericCode g where n.CodeType = g.CodeValue and g.CodeType = 'NomenGrp' and g.CodeCat = '" . $costSubcat . "' and n.ActiveFlag = 1 and g.ActiveFlag = 1");
							
							$sql_result = mysqli_query($insightDBLink, "select n.codevalue, n.description from tblGenericCode n, tblGenericCode g where n.CodeType = g.CodeValue and g.CodeType = 'NomenGrp' and g.CodeCat = '" . $costSubcat . "' and n.ActiveFlag = 1 and g.ActiveFlag = 1");
								
								
							if (mysqli_num_rows($sql_result)){
									
								$index = 0;
									
								while($value = mysqli_fetch_object($sql_result)){
						
									$codeElm[$index][0] = $value->codevalue;
									$codeElm[$index][1] = $value->description;
									
									$index ++;						
								}
							}
						
							$mFlag = 0;
							
							//echo "Nomenclate in file = " . $list[$field] . "<br>";
						
							for($index=0; $index<sizeof($codeElm); $index++){
								
								//echo "codeElm[index][1] = " . $codeElm[$index][1] . "<br>";
								
								//$nomenclate = "";
						
								if(strtoupper($codeElm[$index][1]) === strtoupper($list[$field])){
						
									$nomenclate = $codeElm[$index][0];
									$mFlag = 1;
									
									//echo "nomenclate = " .$nomenclate."<br>";
									//echo "b4: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
										
									$PR_dataset[$row][$field] = $nomenclate;
										
									//echo "after: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
														
								}
							}
						
							if($mFlag == 1){
									
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $list[$field];
							}
							else{
									
								$lastfield = $field - 1;
								
								$bc_checking_result[0][$key][$field] = -1;
								
								if ($bc_checking_result[0][$key][$lastfield] == 0) {
									$bc_checking_result[1][$key][$field] = "Value is not a member of ". $bc_checking_result[1][$key][$lastfield] .".";
								} else {
									$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
								}
								
							}
						
						} // End check Nomenclate						
						
						//Check UOM task 44572 by Linus
						
						if ($field == 13) {
								
							$mFlag = 0;
							
								
							for($index=0; $index<sizeof($UOMTypeElm); $index++){
								
						
								if(strtoupper($UOMTypeElm[$index][1]) === strtoupper($list[$field])){

									$UOM = $UOMTypeElm[$index][0];
									$mFlag = 1;
									
										
									$PR_dataset[$row][$field] = $UOM;
								}
							}
							
							if($mFlag == 1){
									
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $list[$field];
							}
							else{
									
								$bc_checking_result[0][$key][$field] = -1;
								$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
							}
								
						}//End Check UOM

						// Check Billing Type
						if ($field == 17 && ($list[$field] != null || $list[$field] != '')) {
						
							$mFlag = 0;
							
							//echo "Billing Type = " . $list[$field] . "<br>";
								
							for($index=0; $index<sizeof($billingTypeElm); $index++){
								
								//echo "billingTypeElm[index][1] = " . $billingTypeElm[$index][1] . "<br>";
								
								$billingType = "";
						
								if( strtoupper($billingTypeElm[$index][1]) === strtoupper($list[$field])){
										
									$billingType = $billingTypeElm[$index][0] ;
									$mFlag = 1;
									
									//echo "billingType = " .$billingType."<br>";
									//echo "b4: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
									
									$PR_dataset[$row][$field] = $billingType;
									
									//echo "after: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
														
								}
							}
								
							if($mFlag == 1){
									
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $list[$field];
							}
							else{
									
								$bc_checking_result[0][$key][$field] = -1;
								$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
							}
								
						} // End check Billing Type
						
						
						
						// Auto-population GL Code
						if ($field == 24) {
							
							$mFlag = 0;
							$glCodeElm = [];
							
							
							if ($srNum != null || $srNum != '') { // with srnum
							
								$srnum_only = floor(str_replace("sr", "", strtolower($srNum)));
								
								//echo "<br><br><br>row : " . $row . "<br>";
								//echo "SRNum only : " . $srnum_only . "<br>";
								
								// find productid, segment, signedbdm by srnum
								$query = mysqli_query($insightDBLink, "SELECT ProductID, Segment, SignedBDM FROM tblSale WHERE ID = $srnum_only");
								$prod_id = dbSelectColumnFromQuery($query, 0, "ProductID");
								$segment = dbSelectColumnFromQuery($query, 0, "Segment");
								$signed_bdm = dbSelectColumnFromQuery($query, 0, "SignedBDM");
	
								//echo "prod_id : " . $prod_id . "<br>";
								//echo "segment : " . $segment . "<br>";
								//echo "signed_bdm : " . $signed_bdm . "<br><br>";
								
								// find main region by signedbdm
								$query = mysqli_query($insightDBLink, "SELECT StaffRegionID FROM tblReportingLevel WHERE LoginName = '$signed_bdm'");
								$main_region = dbSelectColumnFromQuery($query, 0);
								
								//echo "main_region : " . $main_region . "<br><br>";
								
								$sql = "SELECT GLAccountCode FROM tblPCReqGLAccountCode WHERE ProductID = '$prod_id' AND (Segment ";
								$sql_segment = "= '$segment') ";
								$sql_end = "AND Region = '$main_region' AND Active = 1";
								
								$query = mysqli_query($insightDBLink, $sql.$sql_segment.$sql_end);
								
								
								if (mysqli_num_rows($query)){
										
									$index = 0;
										
									while($value = mysqli_fetch_object($query)){
								
										$glCodeElm[$index][0] = $value->GLAccountCode;
										
										//echo "1. glCodeElm[".$index."][0] : " . $glCodeElm[$index][0] . "<br><br>";
										
										$index ++;
										
									}
									
								} else {
									
									$sql_segment = "= '' OR Segment IS NULL) ";
									$query = mysqli_query($insightDBLink, $sql.$sql_segment.$sql_end);
										
									$index = 0;
										
									while($value = mysqli_fetch_object($query)){
											
										$glCodeElm[$index][0] = $value->GLAccountCode;
										
										//echo "2. glCodeElm[".$index."][0] : " . $glCodeElm[$index][0] . "<br><br>";
										
										$index ++;
									}
									
								}
							
							
							} else { // no srnum
								
								//  $list[31] -- opex / capex
								
								$opex_capex = $list[31];
								
								/*
								echo "opex_capex: " . $opex_capex . "<br>"; 
								echo "costType: " . $costType . "<br>";
								echo "costCat: " . $costCat . "<br>";
								echo "costSubcat: " . $costSubcat . "<br>";
								echo "signedCo: " . $signedCo . "<br>";
								*/
								
								$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref1 = '$opex_capex' AND Ref2 = '$costType' and Ref3 = '$costCat' and Ref4 = '$costSubcat' and Ref5 = '$signedCo'";
								$DBT1_coname = mysqli_query($insightDBLink, $sql);
								$DBTrow_max_bpmt = mysqli_fetch_row($DBT1_coname);
								$GLCode = $DBTrow_max_bpmt[0];
								
								//echo "1. SQL: " . $sql . "<br><br>";
									
								if ( $GLCode == '') {
									$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref1 = '$opex_capex' AND Ref2 = '$costType' and Ref3 = '$costCat' and Ref4 = '$costSubcat'";
									$DBT1_coname = mysqli_query($insightDBLink, $sql);
									$DBTrow_max_bpmt = mysqli_fetch_row($DBT1_coname);
									$GLCode = $DBTrow_max_bpmt[0];
								}
									
								if ( $GLCode == '') {
									$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref1 = '$opex_capex' AND Ref2 = '$costType' and Ref3 = '$costCat' and Ref4 = ''";
									$DBT1_coname = mysqli_query($insightDBLink, $sql);
									$DBTrow_max_bpmt = mysqli_fetch_row($DBT1_coname);
									$GLCode = $DBTrow_max_bpmt[0];
								}
								
								if ( $GLCode == '') {
									$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref1 = '$opex_capex' AND Ref2 = '$costType' and Ref3 = '' and Ref4 = ''";
									$DBT1_coname = mysqli_query($insightDBLink, $sql);
									$DBTrow_max_bpmt = mysqli_fetch_row($DBT1_coname);
									$GLCode = $DBTrow_max_bpmt[0];
								}
								
								if ( $GLCode == '') {
									$sql = "SELECT GLCode FROM tblRefGLCode WHERE CodeType = 'CostCat' AND Ref1 = '$opex_capex' AND Ref2 = '' and Ref3 = '' and Ref4 = ''";
									$DBT1_coname = mysqli_query($insightDBLink, $sql);
									$DBTrow_max_bpmt = mysqli_fetch_row($DBT1_coname);
									$GLCode = $DBTrow_max_bpmt[0];
								}
								
								if ( $GLCode != '') {
									$glCodeElm[0][0] =  $GLCode;
								} else {
									
									$glCodeElm[0][0] = "";
								}

								
							}
							

							if ($list[$field] != null || $list[$field] != '') {

								for($index=0; $index<sizeof($glCodeElm); $index++){
									
									if($glCodeElm[$index][0] === $list[$field]){
								
										$mFlag = 1;
									
										//echo "glCodeElm = " .$glCodeElm[$index][0]."<br>";
										//echo "b4: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
										
										$PR_dataset[$row][$field] = $glCodeElm[$index][0];
										
										//echo "after: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
									}
										
								}
									
							} else {
									
								$mFlag = 1;
								
								//echo "sizeof(glCodeElm): ". sizeof($glCodeElm). "<br><br>";
									
								if (sizeof($glCodeElm)>1) {
									
									$PR_dataset[$row][$field] = "";
									
									//echo ">1 PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br><br>";
									
								} else {

									$PR_dataset[$row][$field] = $glCodeElm[0][0];
									
									//echo "=1 PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br><br>";
								}
								
							}
	

							if($mFlag == 1){
									
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $PR_dataset[$row][$field];
							}
							else{
									
								$bc_checking_result[0][$key][$field] = -1;
								$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
							}									
								
						} // End of Auto-population GL Code
						
						
						// Auto-population CCC
						if ($field == 25) {
								
							if (str_starts_with($costType, "COS") || str_starts_with($costType, "NET")) {
								
								if (str_starts_with($costType, "COS")) {
									
									$mFlag = 0;
									$CCCElm = [];
									$srnum_only = floor(str_replace("sr", "", strtolower($srNum)));
										
										
									// find segment, signedbdm by srnum
									$query = mysqli_query($insightDBLink, "SELECT SignedBDM, Segment FROM tblSale WHERE ID = $srnum_only");
									$segment = dbSelectColumnFromQuery($query, 0, "Segment");
									$signed_bdm = dbSelectColumnFromQuery($query, 0, "SignedBDM");

									//echo "segment : " . $segment . "<br>";
									//echo "signed_bdm : " . $signed_bdm . "<br><br>";
														
									
									// find main region by signedbdm
									$query = mysqli_query($insightDBLink, "SELECT StaffRegionID FROM tblReportingLevel WHERE LoginName = '$signed_bdm'");
									$main_region = dbSelectColumnFromQuery($query, 0);
										
								} else { // substr($cost_type, 0, 3) == "NET"
									$main_region ="Central";
								}
							
								$sql = "SELECT CCC FROM tblPCReqCCC WHERE SignedCo = $signedCo AND (Segment ";
								$sql_segment = "= '$segment') ";
								$sql_end = "AND Region = '$main_region' AND Active = 1";
							
								//echo "SQL : ". $sql.$sql_segment.$sql_end . "<br>";
								
								$query = mysqli_query($insightDBLink, $sql.$sql_segment.$sql_end);
								
								if (mysqli_num_rows($query)){
										
									$index = 0;
										
									while($value = mysqli_fetch_object($query)){
											
										$CCCElm[$index][0] = $value->CCC;
											
										//echo "1. CCCElm[".$index."][0] : " . $CCCElm[$index][0] . "<br><br>";
											
										$index ++;
											
									}
								
								} else {
								
									$sql_segment = "= '' OR Segment IS NULL) ";
									
									$query = mysqli_query($insightDBLink, $sql.$sql_segment.$sql_end);
										
									$index = 0;
										
									while($value = mysqli_fetch_object($query)){
								
										$CCCElm[$index][0] = $value->CCC;
											
										//echo "2. CCCElm[".$index."][0] : " . $CCCElm[$index][0] . "<br><br>";
											
										$index ++;
									}
								
								}
								
								if ($list[$field] != null || $list[$field] != '') {
								
									for($index=0; $index<sizeof($CCCElm); $index++){
											
										if($CCCElm[$index][0] === $list[$field]){
								
											$mFlag = 1;
												
											//echo "CCCeElm = " .$CCCElm[$index][0]."<br>";
											//echo "b4: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
								
											$PR_dataset[$row][$field] = $CCCElm[$index][0];
								
											//echo "after: PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br>";
										}
								
									}
										
								} else {
										
									$mFlag = 1;
								
									//echo "sizeof($CCCElm): ". sizeof($CCCElm). "<br><br>";
										
									if (sizeof($CCCElm)>1) {
											
										$PR_dataset[$row][$field] = "";
											
										//echo ">1 PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br><br>";
											
									} else {
								
										$PR_dataset[$row][$field] = $CCCElm[0][0];
											
										//echo "=1 PR_dataset[". $row . "][".$field."] = " . $PR_dataset[$row][$field] . "<br><br>";
									}
								
								}

							} else {
								
								$mFlag = 1;
								$PR_dataset[$row][$field] = "";
							}
								
							if($mFlag == 1){
									
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = $PR_dataset[$row][$field];
							}
							else{
									
								$bc_checking_result[0][$key][$field] = -1;
								$bc_checking_result[1][$key][$field] = "Upload data doesn't match value in the system.";
							}								
								
								
						} // End of Auto-population CCC
						
						
						// Check if order type = NEW_GOODS 
						if ($orderType == "NEW_GOODS") {
							
							//echo "orderType = " . $orderType . "<br>";
							//echo "list[".$field."] = " . $list[$field] . "<br>";
							
							if ($field == 28) { //Make
								
								if ($list[$field] != null || $list[$field] != '') {
										
									$bc_checking_result[0][$key][$field] = 0;
									$bc_checking_result[1][$key][$field] = $list[$field];
								}
								else{
									
									$bc_checking_result[0][$key][$field] = -1;
									$bc_checking_result[1][$key][$field] = "Value cannot be empty.";
								}
								
								//echo "bc_checking_result[0][".$key."][".$field."] = " . $bc_checking_result[0][$key][$field]. "<br>";
							}

							if ($field == 29) { //Model No
							
								if ($list[$field] != null || $list[$field] != '') {
							
									$bc_checking_result[0][$key][$field] = 0;
									$bc_checking_result[1][$key][$field] = $list[$field];
								}
								else{
							
									$bc_checking_result[0][$key][$field] = -1;
									$bc_checking_result[1][$key][$field] = "Value cannot be empty.";
								}
							
							}
								
							if ($field == 30) { //Part No
							
								if ($list[$field] != null || $list[$field] != '') {
							
									$bc_checking_result[0][$key][$field] = 0;
									$bc_checking_result[1][$key][$field] = $list[$field];
								}
								else{
							
									$bc_checking_result[0][$key][$field] = -1;
									$bc_checking_result[1][$key][$field] = "Value cannot be empty.";
								}
							
							}
								
						} // End Check if order type = NEW_GOODS 
						
						
						// Check Capex/Opex
						
						if ($field == 31) {
							
							if (strtoupper($list[$field]) == 'CAPEX') {
								$PR_dataset[$row][$field] = 'Capex';
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = 'Capex';
							
							}
							else if(strtoupper($list[$field]) == 'OPEX'){
								$PR_dataset[$row][$field] = 'Opex';
								$bc_checking_result[0][$key][$field] = 0;
								$bc_checking_result[1][$key][$field] = 'Opex';
							}							
							else {
										
								$bc_checking_result[0][$key][$field] = -1;
								$bc_checking_result[1][$key][$field] = "Value should be equals Capex or Opex";
							}
							
						} // End Check Capex/Opex

						
						// add default value in Miu Point Quantity
						if ($field == 38) {
							if ($list[$field] == null || $list[$field] == '') {
								$PR_dataset[$row][$field] = "0.0";
							}
						}
					}
								
				}
			}	
			
			$row++;
		}
		
		return $bc_checking_result;	
	}
	
	// Prepare module's template
function getElement($module){
		global $insightDBLink;

		$element = [];
	
		/*$sql = 
		// SELECT 
		// e.DocElementName, e.ElementName, e.ElementType, e.Sequence, 
		// dc.DataType, dc.ConstraintType, dc.DefaultNull, dc.DefaultValue, t.ID, 
		// dc.Capacity, dc.CapacityMask, dc.RangeFrom, dc.RangeTo, dc.Exception
		// FROM WebHynet.tblIETemplate t
		// LEFTJOIN WebHynet.tblIEElement e ON e.TemplateID = t.ID
		// LEFTJOIN WebHynet.tblIEDataConstraint dc ON dc.ElementID = e.ID
		// WHERE t.Module ='IS'
		// ORDERBY Sequence ASC 
		";	*/		
		$sql = 
		"
		SELECT e.DocElementName, e.ElementName, e.ElementType, e.Sequence, 
		dc.DataType, dc.ConstraintType, dc.DefaultNull, dc.DefaultValue, t.ID,
		dc.Capacity, dc.CapacityMask, dc.RangeFrom, dc.RangeTo, dc.Exception		
		FROM tblIETemplate t 
		LEFT JOIN tblIEElement e ON e.TemplateID = t.ID 
		LEFT JOIN tblIEDataConstraint dc ON dc.ElementID = e.ID 
		WHERE t.Module = '" . $module . "' ORDER BY Sequence ASC
		";
		
		/*
		$element[$row][0] is DocElementName
		$element[$row][1] is ElementName
		$element[$row][2] is ElementType
		$element[$row][3] is Sequence
		$element[$row][4] is DataType
		$element[$row][5] is ConstraintType
		$element[$row][6] is DefaultNull
		$element[$row][7] is DefaultVaule
		$element[$row][8] is ID	(tblIETemplateID)		
		$element[$row][9] is Capacity
		$element[$row][10] is CapacityMask
		$element[$row][11] is RangeFrom
		$element[$row][12] is RangeTo
		$element[$row][13] is Exception
		*/
		$sql_result = mysqli_query($insightDBLink, $sql);
		
		if (mysqli_num_rows($sql_result)){
					
			$index = 0;
			
			while($value = mysqli_fetch_object($sql_result)) {
				
				$element[$index][0] = $value->DocElementName;
				$element[$index][1] = $value->ElementName;
				$element[$index][2] = $value->ElementType;
				$element[$index][3] = $value->Sequence;
				$element[$index][4] = $value->DataType;
				$element[$index][5] = $value->ConstraintType;
				$element[$index][6] = $value->DefaultNull;
				$element[$index][7] = $value->DefaultValue;
				$element[$index][8] = $value->ID;
				$element[$index][9] = $value->Capacity;
				$element[$index][10] = $value->CapacityMask;
				$element[$index][11] = $value->RangeFrom;
				$element[$index][12] = $value->RangeTo;
				$element[$index][13] = $value->Exception;			
				
				$index++;							
			}
		}
			
		return $element;
	}	
	
	// Prepare SQL batch
function generateSQL($module, $dataset, $element, $db_name, $header_id){
		global $insightDBLink;
	
		$sql_array = [];
		$sql = "";
		$doc_element = "";
		
		// Get table name 
		if($module <> ""){
		
			//$sql_result = mysqli_query($insightDBLink, "SELECT HeaderTableName, TableName FROM ".$db_name.".tblIETemplate WHERE module = '".$module."'");
			$sql_result = mysqli_query($insightDBLink, "SELECT HeaderTableName, TableName FROM tblIETemplate WHERE module = '".$module."'");
			if (mysqli_num_rows($sql_result)){
										
				while($value = mysqli_fetch_object($sql_result)) { $table_name = $value->HeaderTableName; $detail_table_name = $value->TableName; }
				
				// Get header ID
				if($header_id == 0){
				
					//$sql_result = mysqli_query($insightDBLink,  "SELECT (MAX(ID) + 1) AS ID FROM ".$db_name.".".$table_name );
					$sql_result = mysqli_query($insightDBLink,  "SELECT (MAX(ID) + 1) AS ID FROM " . $table_name );
					
					if (mysqli_num_rows($sql_result)){  while($value = mysqli_fetch_object($sql_result)) { $header_id = $value->ID; } }
				
					//echo "Header ID is: " . $header_id . "<br><br>";
				}
				else{ 
					
					if( is_numeric( $header_id ) == FALSE ){ return "No header ID."; }
                }
			}
			else{ return "No table name."; }
		}
		else{ return "No module ID."; }
		
		
		if ($module == 'PR') {
                    global $PR_dataset;
                    $dataset = $PR_dataset;		
		} else if ($module == 'IS') {
                    global $IS_dataset;
                    $dataset = $IS_dataset;		
                }
		
		
		// Field mapping process
		foreach ($dataset as $key => $list) {
		
			if($key == 1){ //<<  is header row
			
				for($field=0; $field< sizeof($list); $field++){ // horizontal scanning

					for($index=0; $index<sizeof($element); $index++){ // vertical scanning
						
						//Test
						//echo "element[index][0] : " . $element[$index][0]  . "<br>";
						//echo "element[index][1] : " . $element[$index][1]  . "<br>";
						//echo " list[field] : " .  $list[$field]  . "<br>";
						
						if($element[$index][0] == $list[$field]){
						
							//build mapping;
							// To tell the program that which column should be referred to
							// such as "Billing Start" should be mapped to column 4 of document.
							$element_map_to[ $list[$field] ] = $field;//zero based
							
							//Test
							//echo "Seq.   " . $list[$field] . "  location ==>  " . $element_map_to[ $list[$field] ] . "<br>";
						}
						
					}
				}
				//break;
			}//end if			
			
			//echo " list[0] : " .  $list[0]  . "<br>";
		}//end foreach
		
		// Generate SQL statements
		$sql_array = [];		
		if(sizeof($element_map_to) > 0 && sizeof($dataset) > 0){
		
			$line_id = 0;
		
			// Scan document content - $dataset
			foreach ($dataset as $key => $list) {
							
				if($key > 1){// Skip the header row						

					//$sql_array[$key] = "INSERT INTO " . $db_name . "." . $detail_table_name . " ( ";
					$sql_array[$key] = "INSERT INTO " . $detail_table_name . " ( ";
					
					for($column=0; $column < sizeof($element); $column++){
					
						$sql_array[$key] = $sql_array[$key] . $element[$column][1];					
						
						// Add delimiter to sql
						if(sizeof($element) > ($column+1) ) {
						
							$sql_array[$key] = $sql_array[$key] . " , ";
						}
					}
					$sql_array[$key] = $sql_array[$key] . " ) VALUES( ";			
								
					// Scan table Structure -- $element;
					foreach ($element as $element_key => $element_list) {
			
						// CATEGORY 1 - System
						if($element[$element_key][2] == "SystemData"){ // ElementType
					
							//PKEY, LINE, SEQUENCE, FKEY, NULL, "" etc  // ConstraintType
							if(strtoupper( $element[$element_key][5] ) == "PKEY"){$sql_array[$key] = $sql_array[$key] . $header_id;}
							else if(strtoupper( $element[$element_key][5] ) == "LINE"){$sql_array[$key] = $sql_array[$key] . $line_id;}
							else{
								
								if( // DataType
								strtoupper($element[$element_key][4]) == "INTEGER" || strtoupper($element[$element_key][4]) == "INT" ||
								strtoupper($element[$element_key][4]) == "BIGINT" || strtoupper($element[$element_key][4]) == "TINYINT" ||						
								strtoupper($element[$element_key][4]) == "DECIMAL" || strtoupper($element[$element_key][4]) == "DOUBLE" || 
								strtoupper($element[$element_key][4]) == "FLOAT" ){
								
									//Default Null
									if( $element[$element_key][6] == 1){
									
										$sql_array[$key] = $sql_array[$key] . "NULL";
									}
									else{
									
										//Default Value
										if( $element[$element_key][7] === NULL ){
										
											//$sql_array[$key] = $sql_array[$key] . "'DV null'";
											$sql_array[$key] = $sql_array[$key] . "NULL";
										}
										else{
										
											$sql_array[$key] = $sql_array[$key] . mysqli_real_escape_string($insightDBLink,  (string)$element[$element_key][7] );
										}									
									}
								}
								// Dafault Datetime
								else if( strtoupper($element[$element_key][4]) == "DATE" || strtoupper($element[$element_key][4]) == "DATETIME" ){
								
									$sql_array[$key] = $sql_array[$key] . "NOW()";
								}								
								else{
									// Default Null
									if( $element[$element_key][7] === NULL ){
									
										$sql_array[$key] = $sql_array[$key] . "NULL";									
									}
									// Default Value
									else{
									
										$sql_array[$key] = $sql_array[$key] . "'" . mysqli_real_escape_string($insightDBLink,  $element[$element_key][7] ) . "'";
									}
								}							
							}		
						}

						// CATEGORY 2 - User
						if($element[$element_key][2] == "UserData"){ // ElementType
					
							// Numeric
							if( // DataType
							strtoupper($element[$element_key][4]) == "INTEGER" || strtoupper($element[$element_key][4]) == "INT" ||
							strtoupper($element[$element_key][4]) == "BIGINT" || strtoupper($element[$element_key][4]) == "TINYINT" ||						
							strtoupper($element[$element_key][4]) == "DECIMAL" || strtoupper($element[$element_key][4]) == "DOUBLE" || 
							strtoupper($element[$element_key][4]) == "FLOAT" ){					
							
								//echo  "list[ element_map_to[element[element_key][0]] ] :" .$list[ $element_map_to[$element[$element_key][0]] ] . "<br>";
								//echo  "list[ 0 ] : ".$list[ 0 ] . "<br><br>";
								
								if( is_numeric( $list[ $element_map_to[$element[$element_key][0]] ] )  ){
							
									// // CapacityMask 
									// // if( $CapacityMask != NULL || (strlen($CapacityMask) >= 3 && strpos($CapacityMask, ".") >= 1) )
									// if( $element[$element_key][10] != NULL && strlen($element[$element_key][10]) >= 3 && strpos($element[$element_key][10], ".") >= 1 ){
									// {
										// $CapacityMask = $element[$element_key][10];
										// $value = $list[ $element_map_to[$element[$element_key][0]] ];
										
										// $int_portion_capacity = getIntCount( floatValue( $CapacityMask ) );
										// $dec_portion_capacity = getDecCount( floatValue( $CapacityMask ) );												
										// $int_portion = getIntCount( floatValue( $value ) );
										// $dec_portion = getDecCount( floatValue( $value ) );												
																					
										// $CapacityMask = (string)(floatValue($CapacityMask) + 100000000000000000000.000000000000000000001);	
										
										// $doc_value = (string)(floatValue(TRIM($value)) + 100000000000000000000.000000000000000000001);
																
										// for($i=1; i<(strlen($CapacityMask)-1); $i++){
											
												// if( TRIM(substr($doc_value, i, 1)) != "."  &&  TRIM(substr($CapacityMask, i, 1)) == "9" ){
												
													// //$doc_value
													// //if ( isset($doc_value[$i]) ) 
													// { 												   
														// $doc_value[$i] = "0";
													// }
												// }														
										// }
										// $doc_value = (string)(floatValue(doc_value) - 100000000000000000000.000000000000000000001);
									
										// $sql_array[$key] = $sql_array[$key] . $doc_value;
									// }
									// else{
									
										$sql_array[$key] = $sql_array[$key] . $list[ $element_map_to[$element[$element_key][0]] ];
										
									// }
								}
								else{
								
									$sql_array[$key] = $sql_array[$key] . "0";
									
								}
                                                                
							} else if( strtoupper($element[$element_key][4]) == "DATE" || strtoupper($element[$element_key][4]) == "DATETIME" ){
                                                        
                                                            
                                                            if ($module == 'PR') {
                                                                
                                                                $mDate = substr($list[ $element_map_to[$element[$element_key][0]] ],1);
                                                                $sql_array[$key] = $sql_array[$key] . "'" . $mDate . "'";
                                                                
                                                            } else {
                                                                
                                                                $sql_array[$key] = $sql_array[$key] . "'" . $list[ $element_map_to[$element[$element_key][0]] ]. "'";
                                                            }
                                                                
                                                        
                                                        
							// Exception//13
							//if(($element[$element_key][13]) != NULL ){
							
								// if( TRIM($list[ $element_map_to[$element[$element_key][0]] ]) == TRIM($element[$element_key][13]) ){
								
									// $sql_array[$key] = $sql_array[$key] . "'" . mysqli_real_escape_string($insightDBLink,  $list[ $element_map_to[$element[$element_key][0]] ] ) . "'";					
								// }
								// else{
								
									// $sql_array[$key] = $sql_array[$key] . "'" . TRIM($element[$element_key][13]) . "'";					
								// }
							// }
                                                         
							// Others
                                                        } else {
							
								$sql_array[$key] = $sql_array[$key] . "'" . mysqli_real_escape_string($insightDBLink,  $list[ $element_map_to[$element[$element_key][0]] ] ) . "'";					
							}							
						}
						
						// Add delimiter to sql
						if(sizeof($element) > ($element_key+1)){
						
							$sql_array[$key] = $sql_array[$key] . " , ";
							
							//echo "sql_array : " .$sql_array[$key]."<br>";
						}
					}

					$sql_array[$key] = $sql_array[$key] . " )";
					
				} // end if "Skip the header row"
						
				// Process next row
				$line_id++;

			}// end foreach		
		
		}// end if "size of($element_map_to)"
		//var_dump($sql_array);
		return $sql_array;		
	}
	
	// Create records
function generateRecord($sql){
		global $insightDBLink;

		//INSERT
		//echo $sql . "<br>";
		
		$sql_result = mysqli_query($insightDBLink, $sql);
		
		if ($sql_result < 0){
		
			return -1;
		}
		
		return 0;
	}

	// Prepare HTML page
function generateHTML($module, $dataset, $db_name, $header_id, $import_mode, $filename, $dc_checking_result, $bc_checking_result, $target_path){
		global $insightDBLink;
		$page_header = "";
		$page_footer = "";
		$data_grid = "";
		$import_button_row = "";
		$exit_button_row = "";
		$numOfElement = 0;
		
		// Check DC result
		$dc_count = 0;
		foreach ($dc_checking_result[0] as $key => $list){
			if($key > 1){ for($field=0; $field< sizeof($list); $field++){
				//echo "list[" . $field . "] = " .  $list[$field] . "<br>";
				if( $list[$field] < 0 ){ $dc_count++; } } }}
				
		$numOfElement = sizeof($list);
		
		// Check BC result
		$bc_count = 0;
		foreach ($bc_checking_result[0] as $pr_key => $pr_list){
			if($pr_key > 1){ for($field=0; $field<$numOfElement; $field++){
				//echo "pr_list[" . $field . "] = " .  $pr_list[$field] . "<br>";
				if( $pr_list[$field] < 0 ){ $bc_count++; } } }}
		
		
		// Upload Header
	
		// Header
		//$page_header = "<table style=\"border: 5px double rgb(109, 2, 107); height: 600px; background-color: rgb(255, 255, 255); width: 980px;\" align=\"left\" cellpadding=\"5\" cellspacing=\"5\" frame=\"border\" rules=\"all\">";
		$page_header = "<html><head><link type=\"text/css\" href=\"import_engine.css\" rel=\"stylesheet\" /> </head> <body>";
		$page_header = $page_header . "<div id=\"header\"><h1 id=\"banner\">Import Function - " . $module . "</h1></div>";
		$page_header = $page_header . "<form Enctype=\"multipart/form-data\" action='import_engine.php' method='post' name='InputForm'>";
		$page_header = $page_header . "<input type=\"hidden\" name='import_mode' value=\"1\" >";
		$page_header = $page_header . "<input type=\"hidden\" name='module' value=\"" . $module . "\" >";
		$page_header = $page_header . "<input type=\"hidden\" name='header_id' value=\"" . $header_id . "\" >";		
		$page_header = $page_header . "<input type=\"hidden\" name='db_name' value=\"" . $db_name . "\" >";
		$page_header = $page_header . "<input type=\"hidden\" name='filename' value=\"" . $filename . "\" >";
		//$page_header = $page_header . "<table border=\"5\" bordercolor=\"#FFCC00\" style=\"background-color:#FFFFCC\" width=\"100%\" cellpadding=\"3\" cellspacing=\"3\">";
		$page_header = $page_header . "<table cellspacing=\"1\" cellpadding=\"0\" border=\"0\"id=\"table1\">";
		//$page_header = $page_header . "<tbody>";
		//echo $module . " " . $db_name . " " . $header_id . " " . $import_mode . " " . $filename;

		//$page_header = $page_header . "<tr style=\"background-color:#FFFDCC\" ><td colspan=\"100\" BGCOLOR=\"skyblue\"> <font size=\"5\" > Import Function </font> </td></tr>";
		//$page_header = $page_header . "<tr><td> <font size=\"5\" > Import Function </font> </td></tr>";
		//$page_header = $page_header . "<tr><td size=12pt></td></tr>";
		//$page_header = $page_header . "<tr><td size=12pt>". $module . $db_name . $header_id . $import_mode . $filename . "</td></tr>";
		
		// Import Button
		//$import_button_row = "<tr><td WIDTH=\"50\"> <input type=\"submit\" value=\"Submit To Import\" size=\"50\" class='W150'/></td>    <td WIDTH=\"50\"> <input type=\"submit\" value=\"Exit\" size=\"50\" class='W150'/></td></tr>";
		$import_button_row = "<table><tr><td WIDTH=\"300\"> <input type=\"submit\" name=\"submit_to_import\" value=\"Confirm Upload\" /> <input type='Button' name='New' class='W150' value='Cancel' onClick=\"javascript:self.close()\"></td></tr></table>";
		
		// Exit Button
		$exit_button_row = "<input type='Button' name='New' class='W150' value='Exit' onClick=\"exitbtn();\">";
		
		// Footer		
		if($dc_count == 0 && $bc_count == 0){
		
			// Create IE documnet log
			//mysqli_query($insightDBLink, "INSERT INTO " . $db_name . ".tblIEDocument (Module, DBName, HeaderID, ImportMode, Filename, Status, CreateUser, CreateDate, LastUpdUser, LastUpdDate) VALUES ('" . $module . "', '" . $db_name . "', " . $header_id . ", 1, '" . $filename . "', 0, 'Insight', Now(), 'Insight', Now() )");
			mysqli_query($insightDBLink, "INSERT INTO tblIEDocument (Module, DBName, HeaderID, ImportMode, Filename, Status, CreateUser, CreateDate, LastUpdUser, LastUpdDate) VALUES ('" . $module . "', '" . $db_name . "', " . $header_id . ", 1, '" . $filename . "', 0, 'Insight', Now(), 'Insight', Now() )");

			//$page_footer = "</tbody></table>" . $import_button_row . "</form>";
			$page_footer = "</table>" . $import_button_row . "</form></body></html>";
		}
		else{
			if (file_exists($filename)){UNLINK($target_path);}
			$page_footer = "</table></form><div id=\"DialogWarning\">Error !! Please check the upload data with highlighted in red.</div><br><input type='Button' name='Back' style='width:100px' value='Back' onClick=\"window.history.back()\">";//Back button #44572 by Linus
		}
		
		// Data Grid
		if( sizeof($dataset) > 0 ){
		
			$row = 1;
			
			foreach ($dataset as $key => $list) {

				// Header Row
				if($key == 1){

					$data_grid = $data_grid . "<tr id=\"row_title\">";	
								
					for($field=0; $field < sizeof($list); $field++){
					
						$data_grid = $data_grid . "<td>" . $list[$field] . "</td>";			
					}
					
					$data_grid = $data_grid . "</tr>";
				}
				
				// Data Rows
				if($key > 1){
				
					$data_grid = $data_grid . "<tr>";
								
					for($field=0; $field < sizeof($list); $field++){
					
						if((string)$list[$field] == "" || (string)$list[$field] === NULL){ $cell = "&nbsp;";}
						else{ $cell = (string)$list[$field]; }
						
						if($dc_checking_result[0][$key][$field] < 0){
						
							$data_grid = $data_grid . "<td id=\"invalid_data\"><b>" . $cell . "</b><br><br>[". $dc_checking_result[1][$key][$field] ."]";
							
							$data_grid = $data_grid . "</td>";
							
						} else if($bc_checking_result[0][$key][$field] < 0){

							if($dc_checking_result[0][$key][$field] < 0){
							
								$data_grid = $data_grid . "<td id=\"invalid_data\"><br><br>[". $bc_checking_result[1][$key][$field] ."]";
								
							} else {
								
								$data_grid = $data_grid . "<td id=\"invalid_data\"><b>" . $cell . "</b><br><br>[". $bc_checking_result[1][$key][$field] ."]";
							}
								
							$data_grid = $data_grid . "</td>";
							
						} else {
						

							if ($module == 'PR') {
								
								
								if ($field == 24 || $field == 25) { //show GLCode, CCC
									
									$data_grid = $data_grid . "<td>" . $bc_checking_result[1][$key][$field] . "</td>";
									
								} else {
									
									$data_grid = $data_grid . "<td>" . $cell . "</td>";
								}
								
								
								
							} else if ($module == 'IS') {
                                                            
                                                            
                                                                if ($field == 10) { // show default GLCode
								
                                                                    $data_grid = $data_grid . "<td>" . $bc_checking_result[1][$key][$field] . "</td>";
                                                                 
                                                                } else {
                                                                    
                                                                    $data_grid = $data_grid . "<td>" . $cell . "</td>";
                                                                }
								
							}
							
							
							
						}							
					}
					
					$data_grid = $data_grid . "</tr>";
					
					if ($key > sizeof($dataset)) { 
						echo "</table>";
					}
						
					
				}
				 //echo "</tr>123</table>";
/*
				global $PR_dataset;
			
				for($y=0; $y<39; $y++){ // vertical scanning

					echo "PR_dataset[".$row."][".$y."] = " . $PR_dataset[$row][$y] . "<br>";
							
				}
*/
				$row++;
			}
				
		
		}
		else{ 
		
			$data_grid = "<tr><td></td></tr>";
			$data_grid = $data_grid . "<tr><td> No data. </td></tr></table></form></body></html>";
			
			// HTML Result
			return ($page_header . $data_grid . $page_footer);
		}
		
		// HTML Result
		return ($page_header . $data_grid . $page_footer);
	}

	// Convert text to decimal number
	function floatValue($value) {
	
		 return floatval(preg_replace_callback('#^([-]*[0-9\.,\' ]+?)((\.|,){1}([0-9-]{1,2}))*$#', fn($m) => str_replace(['.', ',', '\'', ' '], '', $m[1]) .'.'.$m[4], $value));
    }

	// Get number of integer place
	function getIntCount($f) {
	
		if ($f === 0) {
		
			return 1;
		} 
		elseif ($f < 0) {
		
			return getIntCount(-$f);
		} 
		else {
		
			return floor(log10(floor($f))) + 1;
		}
	}

	// Get number of decimal place
	function getDecCount($f) {
	
		$num = 0;
		
		while (true) {
		
			if ((string)$f === (string)round($f)) {
			
				break;
			}
			if (is_infinite($f)) {
			
				break;
			}

			$f *= 10;
			$num++;
		}
		return $num;
	}
	
	// Validate the datetime format
	function checkDateFormat($dateTime){
		
		if ( preg_match("/^[0-9]{4}-[01][0-9]-[0-3][0-9]$/",$dateTime) ) {
		
			  [$year, $month, $day] = explode('-',$dateTime);
			  return( checkdate( $month , $day , $year ) );
	   } 
	   else {
		   
		  return( false );
	   }		

		return False;
	}  	
	
	

	
	function packDataField($field, $data_type){
	
		//check data type before using single quote ie : '
		//return $packedField
	}
	// Date format conversion for Excel 
	function excelTime($days, $time=false)	{
	
		if(is_numeric($days))
		{
			//based on 1900-1-1
			$jd = GregorianToJD(1, 1, 1970);
			$gregorian = JDToGregorian($jd+intval($days)-25569);
			$myDate = explode('/',$gregorian);
			$myDateStr = str_pad($myDate[2],4,'0', STR_PAD_LEFT)."-".str_pad($myDate[0],2,'0', STR_PAD_LEFT)."-".str_pad($myDate[1],2,'0', STR_PAD_LEFT).($time?" 00:00:00":'');
			
			return $myDateStr;
		}
		
		return $days;
	}	
	
	
function chkItemRecord($module, $header_id){
		global $insightDBLink;
		
		$existPRL = [];
		
		//echo "module: ". $module;
		//echo "<br>header_id:" . $header_id . "<br>";
		
		
		if ($module == 'IS') {
			$search_sql = "select id from tblISInvLine where invoiceid = " . $header_id;  //IS 
		} else if ($module == 'PR') {
			$search_sql = "select id from tblPCReqDetail where reqid = " . $header_id;  //PR
		}

		$result = mysqli_query($insightDBLink, $search_sql);
		
		//echo "result:" .$result;
			
		if (mysqli_num_rows($result)){
				
			$index = 0;
				
			while($value = mysqli_fetch_object($result)) {
		
				$existPRL[$index] = $value->id;
		
				//echo "<br>$existPRL[index]:" . $existPRL[$index];
				
				$index++;
			}
		}		
		
		return $existPRL;
	}

        
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
        
function getLineGLCode($SignedCo, $TaxFlag) {
    
    // Get GLCode in IS Line Item 
    
    $invLine_GLCode = '';
    $invLine_TaxFlag = $TaxFlag;
    $invLine_TaxCode = '';
    $Result = '';
    
    
    //$sql = "select GLCode, TaxFlag, TaxCode from tblISInvLine where ID = $inv_id";
        
    //$lineResult = getOneRow($sql);        
        
    if($SignedCo != ''){
        
        //$invLine_GLCode = $lineResult["GLCode"];
        $invLine_TaxFlag = $TaxFlag;
        //$invLine_TaxCode = $TaxCode;
        
        
        //if ($invLine_TaxFlag >= 10) {
            
            $sql = "select Description as ISTrxCode_GLCode from tblGenericCode where CodeType='SysDefault' and CodeCat='ISTrxCode' and CodeValue = '$invLine_TaxFlag' and Sequence = '$SignedCo' ";
            
            $Result = getOneRow($sql);
            
            if($Result){
                $invLine_GLCode = $Result["ISTrxCode_GLCode"];
            }
            
            
            
            if ($Result=='' || $Result==null) {
            
                // Get default
                $sql = "select Description as ISTrxCode_GLCode from tblGenericCode where CodeType='SysDefault' and CodeCat='ISTrxCode' and CodeValue = '$invLine_TaxFlag' and Sequence = '0' ";

                $dResult = getOneRow($sql);

                if($dResult){
                    $invLine_GLCode = $dResult["ISTrxCode_GLCode"];
                }
            
            }
        
            /*
            
            if ($invLine_TaxFlag > 0 && $invLine_TaxFlag < 10 && $invLine_TaxCode !='') {
            
                $sql = "select TaxAccount from tblRefVATCode where Category in ('AP','ALL') and TaxCode='$invLine_TaxCode' and SignedCo = '$SignedCo' ";
            
                $Result = getOneRow($sql);
            
                if($Result){
                    $invLine_GLCode = $Result["TaxAccount"];
                }
            
            }

            */
        
    }
    //echo $sql . "<br><br>";
    //echo $invLine_GLCode . "<br><br>";
    
    //exit();
    
    return $invLine_GLCode;
}        
        
        
        
	
	/*loadFileIntoDataset($inputFileName){
		
		// File type checking
		try
		{
			$FileType = '';			
			if( strpos($inputFileName, ".xls") ){ $FileType = 'Excel5'; }			
			else if (strpos($inputFileName, ".xlsx") ){ $FileType = 'Excel2007'; }			
			else if (strpos($inputFileName, ".csv") ){ $FileType = 'CSV'; }			
			else if( strpos($inputFileName, ".XML") ){ $FileType = 'Excel2003XML'; }			
			else if( strpos($inputFileName, ".xml") ){ $FileType = 'Excel2003XML'; }			
			else{ return -1; }	
		}
		Catch(Exception $ex){ throw $ex }
	
		// File size checking
		try
		{
			if( (filesize($inputFileName) / 1024 / 1024) <= 10 ){
		
			
			}
			//Larger than 10 MB
			{
			}
		}
		catch(Exception $ex){ throw $ex }
	
	
	require_once ��../PHPExcel/IOFactory.php��;
	$reader = PHPExcel_IOFactory::createReader($FileType);
	$PHPExcel = $reader->load("inputFileName.xls");
	$worksheet = $PHPExcel->getSheet(0);
	$highestRow = $worksheet->getHighestRow();
	echo "==".$highestRow."<br />"; 

		// Read 1 row each time
		try
		{
			for ($row = 1; $row <= $highestRow; $row++) {
	
				for ($column = 0; $column <= 6; $column++) { 
		
					$val = $worksheet->getCellByColumnAndRow($column, $row)->getValue(); 
					echo $val . �� ��; 
			
				} 
				echo "<br />";
			} 
		}
		catch(exception $ex){ throw $ex }
	
	}*/
	
	

