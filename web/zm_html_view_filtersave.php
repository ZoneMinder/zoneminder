<?php
	if ( !canEdit( 'Events' ) )
	{
		$view = "error";
		return;
	}
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Save Filter</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	top.window.close();
}
function validateForm( form )
{
	return( true );
}
window.focus();
</script>
</head>
<body>
<form name="filter_form" method="get" action="<?= $PHP_SELF ?>" onSubmit="validateForm( document.filter_form );">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="filter">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="trms" value="<?= $trms ?>">
<?php
	for ( $i = 1; $i <= $trms; $i++ )
	{
		$conjunction_name = "cnj$i";
		$obracket_name = "obr$i";
		$cbracket_name = "cbr$i";
		$attr_name = "attr$i";
		$op_name = "op$i";
		$value_name = "val$i";
		if ( $i > 1 )
		{
?>
<input type="hidden" name="<?= $conjunction_name ?>" value="<?= $$conjunction_name ?>">
<?php
		}
?>
<input type="hidden" name="<?= $obracket_name ?>" value="<?= $$obracket_name ?>">
<input type="hidden" name="<?= $cbracket_name ?>" value="<?= $$cbracket_name ?>">
<input type="hidden" name="<?= $attr_name ?>" value="<?= $$attr_name ?>">
<input type="hidden" name="<?= $op_name ?>" value="<?= $$op_name ?>">
<input type="hidden" name="<?= $value_name ?>" value="<?= $$value_name ?>">
<?php
	}
?>
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<?php
	$select_name = "filter_name";
	$result = mysql_query( "select * from Filters where MonitorId = '$mid' order by Name" );
	if ( !$result )
		die( mysql_error() );
	while ( $row = mysql_fetch_assoc( $result ) )
	{
		$filter_names[$row[Name]] = $row[Name];
		if ( $filter_name == $row[Name] )
		{
			$filter_data = $row;
		}
	}
?>
<?php if ( count($filter_names) ) { ?>
<td align="left" colspan="2" class="text">Save as:&nbsp;<?php buildSelect( $select_name, $filter_names, "submitToFilter( document.filter_form );" ); ?>&nbsp;or enter new name:&nbsp;<input type="text" size="32" name="new_<?= $select_name ?>" value="<?= $filter ?>" class="form"></td>
<?php } else { ?>
<td align="left" colspan="2" class="text">Enter new filter name:&nbsp;<input type="text" size="32" name="new_<?= $select_name ?>" value="" class="form"></td>
<?php } ?>
</tr>
<tr>
<td align="right" colspan="2" class="text">&nbsp;</td>
</tr>
<tr>
<td align="left" class="text">Automatically archive all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_archive" value="1"<?php if ( $filter_data[AutoArchive] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text">Automatically delete all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_delete" value="1"<?php if ( $filter_data[AutoDelete] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text">Automatically upload all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_upload" value="1"<?php if ( $filter_data[AutoUpload] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text">Automatically email details of all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_email" value="1"<?php if ( $filter_data[AutoEmail] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text">Automatically message details of all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_message" value="1"<?php if ( $filter_data[AutoMessage] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="right" colspan="2" class="text">&nbsp;</td>
</tr>
<tr>
<td align="right" colspan="2" class="text"><input type="submit" value="Save" class="form"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>>&nbsp;<input type="button" value="Cancel" class="form" onClick="closeWindow();"></td>
</tr>
</table></center>
</form>
</body>
</html>
