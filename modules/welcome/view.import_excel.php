
<div>
		<form method="post" action="<?php echo base_url();?>index.php/welcome/proses_import" enctype="multipart/form-data" name="fupload" id="fupload">
		<fieldset id="import"><legend><b>Import</b></legend>
		<table width="100%" border="0">
		
		  <tr>
			<td align="right">Excel File</td>
			<td>&nbsp;
			<input type="File" name= "userfile" id="userfile"/>
			
			</td>
		  </tr>
		  <tr>
			<td width="25%" align="right">&nbsp;</td>
			<td width="75%"></td>
		  </tr>	  
		  <tr>
			<td width="25%" align="right">&nbsp;</td>
			<td width="75%"> &nbsp;<button type="submit">Process</button></td>
		  </tr>	  
		 <table>
		</fieldset>
		</form>
</div>
