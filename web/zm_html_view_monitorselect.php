<?php
//
// ZoneMinder web monitor selection file, $Date$, $Revision$
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

if ( !canView( 'System' ) )
{
	$view = "error";
	return;
}

$result = mysql_query( "select Id,Name from Monitors order by Sequence asc" );
if ( !$result )
	die( mysql_error() );
$monitors = array();
while ( $monitor = mysql_fetch_assoc( $result ) )
{
	$monitors[] = $monitor;
}
mysql_free_result( $result );
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangSelectMonitors ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function closeWindow()
{
	top.window.close();
}
function getSelectedIds()
{
	var form = document.selectForm;
	var call_form = opener.document.<?= $callForm ?>;
	var selected_ids = call_form.elements['<?= $callField ?>'].value.split( /\s*,\s*/g );
	var selected_id_hash = new Array();
	for ( var i = 0; i < selected_ids.length; i++ )
	{
		selected_id_hash[selected_ids[i]] = true;
	}
	for ( var j = 0; j < form.selectedIds.options.length; j++ )
	{
		if ( selected_id_hash[form.selectedIds.options[j].value] )
		{
			form.selectedIds.options[j].selected = true;
		}
	}
	return( true );
}
function setSelectedIds()
{
	var form = document.selectForm;
	var call_form = opener.document.<?= $callForm ?>;
	var selected_ids = new Array();
	for ( var i = 0; i < form.selectedIds.options.length; i++ )
	{
		if ( form.selectedIds.options[i].selected )
		{
			selected_ids[selected_ids.length] = form.selectedIds.options[i].value;
		}
	}
	call_form.elements['<?= $callField ?>'].value = selected_ids.join( ',' );
	return( true );
}
window.focus();
</script>
</head>
<body>
<form name="selectForm" method="get" action="<?= $PHP_SELF ?>" onSubmit="setSelectedIds()">
<input type="hidden" name="view" value="none">
<table width="100%" border="0" cellpadding="0" cellspacing="4">
<tr><td class="head"><?= $zmSlangSelectMonitors ?></td></tr>
<tr><td><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
<tr>
<td align="center"><select name="selectedIds" class="form" size="8" multiple>
<?php
foreach ( $monitors as $monitor )
{
	if ( visibleMonitor( $monitor['Id'] ) )
	{
?>
<option value="<?= $monitor['Id'] ?>"><?= htmlentities($monitor['Name']) ?></option>
<?php
	}
}
?>
</select>
</tr>
<tr><td><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
<tr>
<td align="right">
<input type="submit" value="<?= $zmSlangSave ?>" class="form">&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();">
</td>
</tr>
</table>
</form>
<script type="text/javascript">
getSelectedIds();
</script>
</body>
</html>
