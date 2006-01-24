<?php
//
// ZoneMinder web monitor groups file, $Date$, $Revision$
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

$result = mysql_query( "select * from Groups order by Name" );
if ( !$result )
	die( mysql_error() );
$groups = array();
$selected = false;
while ( $row = mysql_fetch_assoc( $result ) )
{
	if ( $row['Id'] == $cgroup )
	{
		$row['selected'] = true;
		$selected = true;
	}
	$groups[] = $row;
}
mysql_free_result( $result );
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangGroups ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function newWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
function newGroup()
{
	newWindow( '<?= $PHP_SELF ?>?view=group', 'zmGroup', <?= $jws['group']['w'] ?>, <?= $jws['group']['h'] ?> );
}
function editGroup()
{
	for ( var i = 0; i < groups_form.gid.length; i++ )
	{
		if ( groups_form.gid[i].checked )
		{
			newWindow( '<?= $PHP_SELF ?>?view=group&gid='+groups_form.gid[i].value, 'zmGroup', <?= $jws['group']['w'] ?>, <?= $jws['group']['h'] ?> );
			return;
		}
	}
}
function deleteGroup()
{
	groups_form.view.value='<?= $view ?>';
	groups_form.action.value='delete';
	groups_form.submit();

}
function configureButtons(element)
{
<?php
if ( canEdit('System' ) )
{
?>
	var form = element.form;
	if ( element.checked )
	{
		form.edit_btn.disabled = (element.value == 0);
		form.delete_btn.disabled = (element.value == 0);
	}
<?php
}
?>
}
window.focus();
</script>
</head>
<body>
<form name="groups_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="cgroup">
<table width="100%" border="0" cellspacing="1" cellpadding="2" bgcolor="#7F7FB2">
<tr bgcolor="#FFFFFF"><td class="smallhead"><?= $zmSlangName ?></td><td class="smallhead"><?= $zmSlangMonitorIds ?></td><td class="smallhead"><?= $zmSlangSelect ?></tr>
<tr bgcolor="#CCCCCC">
<td align="left" class="text" style="white-space: nowrap;"><?= $zmSlangNoGroup ?></td>
<td align="left" class="text"><?= $zmSlangAll ?></td>
<td align="center"><input class="form-noborder" type="radio" name="gid" value="0"<?= !$selected?" checked":"" ?> onClick="configureButtons( this );"></td>
</tr>
<?php
foreach ( $groups as $group )
{
?>
<tr bgcolor="#FFFFFF">
<td align="left" class="text" style="white-space: nowrap;"><?= $group['Name'] ?></td>
<td align="left" class="text"><?= monitorIdsToNames( $group['MonitorIds'], 30 ) ?></td>
<td align="center"><input class="form-noborder" type="radio" name="gid" value="<?= $group['Id'] ?>"<?= $group['selected']?" checked":"" ?> onClick="configureButtons( this );"></td>
</tr>
<?php
}
?>
<tr bgcolor="#FFFFFF">
<td align="right" colspan="3" class="text"><img src="graphics/spacer.gif" width="1" height="4"></td>
</tr>
<tr bgcolor="#FFFFFF">
<td align="right" colspan="3" class="text">
<input type="submit" value="<?= $zmSlangApply ?>" class="form" onClick="groups_form.action.value='cgroup'; groups_form.submit();">&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangNew ?>" class="form" onClick="newGroup()"<?= canEdit('System')?"":" disabled" ?>>&nbsp;&nbsp;
<input type="button" name="edit_btn" value="<?= $zmSlangEdit ?>" class="form" onClick="editGroup()"<?= $selected&&canEdit('System')?"":" disabled" ?>>&nbsp;&nbsp;
<input type="button" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" onClick="deleteGroup()"<?= $selected&&canEdit('System')?"":" disabled" ?>>&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td>
</tr>
</table>
</form>
</body>
</html>
