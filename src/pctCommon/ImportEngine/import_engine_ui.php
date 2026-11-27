<HTML>

<HEAD>
<meta http-equiv="Content-Type" content="text/html;charset=big5">
<link rel="STYLESHEET" type="text/css" href="../../mainStyle.css">

<?php 
//header('Content-Type: application/csv');
//header('Content-Disposition: attachment; filename=ImportDocTemplate_PR.csv');
//header('Pragma: no-cache');
$module = $_GET["module"] ?? $_POST["module"] ?? "";
?>

</HEAD>
<body bgcolor="#ffffff" TEXT="#000000" >
<script src="<?php echo $_SERVER["DOCUMENT_ROOT"] ?>/mainFunction.js"></script>
<script>

function save(){
  if(this.document.InputForm.Description.value == ''){
  	alert("Please choose Description!");	
  }
  else if(this.document.InputForm.Hid_WorkD.value == '' || this.document.InputForm.Hid_WorkD.value == '0000-00-00'){
  	alert("Please choose Date!");	
  }
  else if(this.document.InputForm.Owner.value == ''){
  	alert("Please input Done by!");	
  }
  else{

    	this.document.InputForm.Act.value='add';
  
    this.document.InputForm.submit();  
  }
}
function exitbtn(){
 opener.history.go(0);
  window.close();
}
function closewin(){

  if(false == my_window.closed)
  {
	 my_window.close ();
  }
  else
  {
	 alert('Window already closed!');
  }
}
function clr_Name(FieldID, FieldName){
	FieldID.value="";
	FieldName.value="";
}
function del_attach() {
	if(window.confirm('Are you sure to delete the attachment?')) {
		document.InputForm.Act.value='del_attach';
		document.InputForm.submit();
	}
}
function special_char_convert(str){
                // special characters handling
                var s = str;
                s = s.replace(/<br \/>/g,"\n"); // replace <br /> 
                s = s.replace(/Ac/g,"&eacute;"); // replace e    
                s = s.replace(/a��?/g,"&lsquo;"); // replace ��
                s = s.replace(/a��?/g,"&rsquo;"); // replace ��
                s = s.replace(/a��?/g,"&ldquo;"); // replace ��
                s = s.replace(/A��/g,"&rdquo;"); // replace ��
                return s;
}

function checkSize()
{
    var input = document.getElementById("uploadedfile");
    var max_file_size = 1024000; // 1 MB (size is in bytes)

    if(input.files && input.files.length == 1)
    {           
        if (input.files[0].size > max_file_size) 
        {
            alert("Error !! The upload file MUST be less than 1 MB, please reduce the file size and upload again.");
            return false;
        }
    }

    return true;
}


function downloadfile(x){

    var uriContent = "data:text/csv;charset=utf-8," + x;
    var myTempWindow = window.open(uriContent);

    //myTempWindow = window.open('data:text/csv;charset=utf-8;' + escape(x),'','left=10000,screenX=10000');
    myTempWindow.document.execCommand('SaveAs','null',x);
    myTempWindow.close();
    
    
    
    
 }
//-->
</script>

<DIV>
<form Enctype="multipart/form-data" action='import_engine.php' method='post' name='InputForm' onsubmit="return checkSize()">
<input type="hidden" name='import_mode' value="<?php echo 0; ?>" >
<input type="hidden" name='module' value="<?php echo $_GET["module"]; ?>" >
<input type="hidden" name='header_id' value="<?php echo $_GET["header_id"]; ?>" >

    <table width="100%" border="0" cellspacing="0" cellpadding="0">	
		<tr height="20">
			<td size=12pt class="THeader"><font size="3">Import Function</font></td>
		</tr>
		
		<tr>
			<td class="TContent"> 
		  
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<br>
 			<tr>
			  <td colspan="4">
                <p align="left">
				
					<!-- <input type="hidden" name="MAX_FILE_SIZE" value="1024000" /> -->
					
									
					File Name : <input id="uploadedfile" name="uploadedfile" type="file" class='W500' /> 

					<input type="submit" value="Preview Upload" />
					<input type='Button' name='New' class='W100' value='Exit' onClick="javascript:self.close()">
					  
					<br><br>
					
                </p>
              </td>
            </tr>
			</table>

			</td>
		</tr>	
	</table>
<?php if ($module == 'PR'): ?>
 	<font color=red>*** ALL existing PR item(s) will be <u>DELETED</u> after imported</font>
        <br><br><br>

    <?php if(str_contains((string) $_SERVER['HTTP_USER_AGENT'], 'MSIE')): ?>    
            <!--Import Template: <a href="javascript:downloadfile('./ImportDocTemplate_PR.csv');"><u>ImportDocTemplate_PR</u></a>-->
            Import Template: <a href="./file/ImportDocTemplate_PR.csv" download="ImportDocTemplate_PR.csv" border="0" target="_blank"><u>ImportDocTemplate_PR</u></a>
    <?php else: ?>    
            Import Template: <a href="./file/ImportDocTemplate_PR.csv" download="ImportDocTemplate_PR.csv" border="0" target="_blank"><u>ImportDocTemplate_PR</u></a>
    <?php endif; ?>       
	   (Last updated: 2017-10-24)
       <font color=red><br><br>* Date format in template : DYYYY-MM-DD</font>             
       <br><br><br><br>Guideline: <a href="./file/Guideline of Import Function (PR).docx" download="Guideline of Import Function (PR).docx" border="0" target="_blank"><u>Guideline of Import Function (PR)</u></a>      		
<?php endif; ?>


<?php if ($module == 'IS'): ?>
 	<font color=red>*** ALL existing IS item(s) will be <u>DELETED</u> after imported</font>
        <br><br><br>
    <?php if(str_contains((string) $_SERVER['HTTP_USER_AGENT'], 'MSIE')): ?>    
            <!--Import Template: <a href="javascript:downloadfile('./ImportDocTemplate_PR.csv');"><u>ImportDocTemplate_PR</u></a>-->
            Import Template: <a href="./file/ImportDocTemplate_IS.csv" download="ImportDocTemplate_IS.csv" border="0" target="_blank"><u>ImportDocTemplate_IS</u></a>
    <?php else: ?>    
            Import Template: <a href="./file/ImportDocTemplate_IS.csv" download="ImportDocTemplate_IS.csv" border="0" target="_blank"><u>ImportDocTemplate_IS</u></a>        
    <?php endif; ?>  
		(Last updated: 2017-10-24)
		<font color=red><br><br>* Date format in template : DYYYY-MM-DD</font> 

		<br><br><br><br>Guideline: <a href="./file/Guideline of Import Function (IS).docx" download="Guideline of Import Function (IS).docx" border="0" target="_blank"><u>Guideline of Import Function (IS)</u></a>      		
<?php endif; ?>        
        
 	
</FORM>

</DIV>
</BODY>
</HTML>




