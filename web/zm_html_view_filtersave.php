<?php
//
// ZoneMinder web filter save view file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

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
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangSaveFilter ?></title>
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
<input type="hidden" name="<?= $obracket_name ?>" value="<?= isset($$obracket_name)?$$obracket_name:'' ?>">
<input type="hidden" name="<?= $cbracket_name ?>" value="<?= isset($$cbracket_name)?$$cbracket_name:'' ?>">
<input type="hidden" name="<?= $attr_name ?>" value="<?= isset($$attr_name)?$$attr_name:'' ?>">
<input type="hidden" name="<?= $op_name ?>" value="<?= isset($$op_name)?$$op_name:'' ?>">
<input type="hidden" name="<?= $value_name ?>" value="<?= isset($$value_name)?$$value_name:'' ?>">
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
	$filter_names[$row['Name']] = $row['Name'];
	if ( $filter_name == $row['Name'] )
	{
		$filter_data = $row;
	}
}
?>
<?php if ( count($filter_names) ) { ?>
<td align="left" colspan="2" class="text"><?= $zmSlangSaveAs ?>:&nbsp;<?= buildSelect( $select_name, $filter_names, "submitToFilter( document.filter_form );" ); ?>&nbsp;<?= $zmSlangOrEnterNewName ?>:&nbsp;<input type="text" size="32" name="new_<?= $select_name ?>" value="<?= $filter_name ?>" class="form"></td>
<?php } else { ?>
<td align="left" colspan="2" class="text"><?= $zmSlangEnterNewFilterName ?>:&nbsp;<input type="text" size="32" name="new_<?= $select_name ?>" value="" class="form"></td>
<?php } ?>
</tr>
<tr>
<td align="right" colspan="2" class="text">&nbsp;</td>
</tr>
<tr>
<td align="left" class="text"><?= $zmSlangAutoArchiveEvents ?>:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_archive" value="1"<?php if ( $filter_data['AutoArchive'] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text"><?= $zmSlangAutoDeleteEvents ?>:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_delete" value="1"<?php if ( $filter_data['AutoDelete'] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text"><?= $zmSlangAutoUploadEvents ?>:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_upload" value="1"<?php if ( $filter_data['AutoUpload'] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text"><?= $zmSlangAutoEmailEvents ?>:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_email" value="1"<?php if ( $filter_data['AutoEmail'] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text"><?= $zmSlangAutoMessageEvents ?>:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_message" value="1"<?php if ( $filter_data['AutoMessage'] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="right" colspan="2" class="text">&nbsp;</td>
</tr>
<tr>
<td align="right" colspan="2" class="text"><input type="submit" value="<?= $zmSlangSave ?>" class="form"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>>&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td>
</tr>
</table></center>
</form>
</body>
</html>
