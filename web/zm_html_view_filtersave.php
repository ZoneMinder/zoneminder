<?php
//
// ZoneMinder web filter save view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

$db_debug = true;
if ( !canEdit( 'Events' ) )
{
	$view = "error";
	return;
}

parseFilter( $filter );
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangSaveFilter ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
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
<form name="filter_form" method="post" action="<?= $PHP_SELF ?>" onSubmit="validateForm( document.filter_form );">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="filter">
<?= $filter['fields'] ?>
<input type="hidden" name="sort_field" value="<?= $sort_field ?>">
<input type="hidden" name="sort_asc" value="<?= $sort_asc ?>">
<input type="hidden" name="limit" value="<?= $limit ?>">
<input type="hidden" name="auto_archive" value="<?= $auto_archive ?>">
<input type="hidden" name="auto_video" value="<?= $auto_video ?>">
<input type="hidden" name="auto_upload" value="<?= $auto_upload ?>">
<input type="hidden" name="auto_email" value="<?= $auto_email ?>">
<input type="hidden" name="auto_message" value="<?= $auto_message ?>">
<input type="hidden" name="auto_execute" value="<?= $auto_execute ?>">
<input type="hidden" name="auto_execute_cmd" value="<?= $auto_execute_cmd ?>">
<input type="hidden" name="auto_delete" value="<?= $auto_delete ?>">
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<?php
$select_name = "filter_name";
foreach ( dbFetchAll( "select * from Filters order by Name" ) as $row )
{
	$filter_names[$row['Name']] = $row['Name'];
	if ( $filter_name == $row['Name'] )
	{
		$filter_data = $row;
	}
}
?>
<?php if ( count($filter_names) ) { ?>
<td align="left" colspan="3" class="text"><?= $zmSlangSaveAs ?>:&nbsp;<?= buildSelect( $select_name, $filter_names ); ?>&nbsp;<?= $zmSlangOrEnterNewName ?>:&nbsp;<input type="text" size="32" name="new_<?= $select_name ?>" value="<?= $filter_name ?>" class="form"></td>
<?php } else { ?>
<td align="left" colspan="3" class="text"><?= $zmSlangEnterNewFilterName ?>:&nbsp;<input type="text" size="32" name="new_<?= $select_name ?>" value="" class="form"></td>
<?php } ?>
</tr>
<tr>
<td align="right" colspan="3" class="text">&nbsp;</td>
</tr>
<tr>
<td align="left" class="text"><?= $zmSlangBackgroundFilter ?>:&nbsp;</td>
<td align="left" class="text" colspan="2"><input type="checkbox" name="background" value="1"<?php if ( $filter_data['Background'] ) { echo " checked"; } ?> class="form-noborder"></td>
</tr>
<tr>
<td align="right" colspan="3" class="text">&nbsp;</td>
</tr>
<tr>
<td align="right" colspan="3" class="text"><input type="submit" value="<?= $zmSlangSave ?>" class="form"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>>&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td>
</tr>
</table></center>
</form>
</body>
</html>
