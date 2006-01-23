<?php
//
// ZoneMinder web group detail view file, $Date$, $Revision$
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

if ( !canEdit( 'System' ) )
{
	$view = "error";
	return;
}
if ( $gid )
{
	$sql = "select * from Groups where Id = '$gid'";
	$result = mysql_query( $sql );
	if ( !$result )
		die( mysql_error() );
	$new_group = mysql_fetch_assoc( $result );
	mysql_free_result( $result );
}
else
{
	$new_group = array(
		"Id" => "",
		"Name" => "New Group",
		"MonitorIds" => ""
	);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangGroup ?> <?= $eid ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( !empty($refresh_parent) )
{
?>
opener.location.reload(true);
<?php
}
?>
window.focus();
function newWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
function closeWindow()
{
	window.close();
}
function selectMonitors()
{
    newWindow( "<?= $PHP_SELF ?>?view=monitorselect&callForm=<?= urlencode( 'group_form' ) ?>&callField=<?= urlencode( 'new_group[MonitorIds]' ) ?>", "zmMonitors", <?= $jws['monitorselect']['w'] ?>, <?= $jws['monitorselect']['h'] ?> );
}
</script>
</head>
<body>
<form name="group_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="group">
<input type="hidden" name="gid" value="<?= $gid ?>">
<table width="100%" border="0">
<tr><td class="head"><?= $zmSlangGroup ?></td></tr>
<tr><td><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
</table>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
<tr valign="top"><td align="left" class="text"><?= $zmSlangName ?></td><td align="left" class="text"><input type="text" name="new_group[Name]" value="<?= $new_group['Name'] ?>" size="32" class="form"></td></tr>
<tr valign="top"><td align="left" class="text"><?= $zmSlangMonitorIds ?></td><td align="left" class="text"><input type="text" name="new_group[MonitorIds]" value="<?= $new_group['MonitorIds'] ?>" size="32" class="form">&nbsp;<a href="#" onClick="selectMonitors()"><?= $zmSlangSelect ?></a></td></tr>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td colspan="2" align="right"><input type="submit" value="<?= $zmSlangSave ?>" class="form"<?php if ( !canEdit( 'System' ) ) { ?> disabled<?php } ?>>&nbsp;&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</form>
</body>
</html>
