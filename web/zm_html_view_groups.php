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

if ( !canEdit( 'System' ) )
{
	$view = "error";
	return;
}

$result = mysql_query( "select * from Groups order by Id" );
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
function closeWindow()
{
	top.window.close();
}
function validateForm( form )
{
	return( true );
}
function configureButtons(element)
{
	var form = element.form;
	if ( element.checked )
	{
		form.delete_btn.disabled = (element.value == 0);
	}
}
function monitorIds()
{
	var form = opener.document.monitor_form;
	var monitor_ids = new Array();
	for ( var i = 0; i < form.elements.length; i++ )
	{
		if ( form.elements[i].name.indexOf('mark_mids') == 0)
		{
			if ( form.elements[i].checked )
			{
				monitor_ids[monitor_ids.length] = form.elements[i].value;
			}
		}
	}
	return( monitor_ids.join( ',' ) );
}
window.focus();
</script>
</head>
<body>
<form name="group_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="groups">
<table width="100%" border="0" cellpadding="0" cellspacing="4">
<tr><td class="smallhead"><?= $zmSlangName ?></td><td class="smallhead"><?= $zmSlangMonitorIds ?></td><td class="smallhead"><?= $zmSlangSelect ?></tr>
<tr>
<td align="left" class="text"><input class="form" value="<?= $zmSlangNoGroup ?>" size="20" disabled></td>
<td align="left" class="text"><input class="form" value="<?= $zmSlangAll ?>" size="40" disabled></td>
<td align="center"><input class="form-noborder" type="radio" name="gid" value="0"<?= !$selected?" checked":"" ?> onClick="configureButtons( this );"></td>
</tr>
<?php
if ( count($groups) || $new )
{
	foreach ( $groups as $group )
	{
?>
<tr>
<td align="left" class="text"><input class="form" name="names[<?= $group['Id'] ?>]" value="<?= $group['Name'] ?>" size="20"></td>
<td align="left" class="text"><input class="form" name="monitor_ids[<?= $group['Id'] ?>]" value="<?= $group['MonitorIds'] ?>" size="40"></td>
<td align="center"><input class="form-noborder" type="radio" name="gid" value="<?= $group['Id'] ?>"<?= $group['selected']?" checked":"" ?> onClick="configureButtons( this );"></td>
</tr>
<?php
	}
	if ( $new )
	{
?>
<tr>
<td align="left" class="text"><input class="form" name="new_name" value="<?= $zmSlangNewGroup ?>" size="20"></td>
<td align="left" class="text"><input class="form" name="new_monitor_ids" value="<?= $monitor_ids ?>" size="40"></td>
<td align="center"><input class="form-noborder" type="radio" name="gid" value="<?= $group['Id'] ?>" onClick="configureButtons( this );"></td>
</tr>
<?php
	}
}
?>
<tr>
<td align="right" colspan="3" class="text">&nbsp;</td>
</tr>
<tr>
<td align="right" colspan="3" class="text">
<input type="button" name="select_btn" value="<?= $zmSlangApply ?>" class="form" onClick="group_form.action.value='group'; group_form.submit();">&nbsp;&nbsp;
<input type="button" name="new_btn" value="<?= $zmSlangNew ?>" class="form" onClick="window.location='<?= $PHP_SELF ?>?view=<?= $view ?>&monitor_ids='+monitorIds()+'&new=1'">&nbsp;&nbsp;
<input type="button" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" onClick="group_form.action.value='<?= $view ?>'; group_form.action.value='delete'; group_form.submit();"<?= $selected?"":" disabled" ?>>&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangSave ?>" class="form" onClick="group_form.submit()">&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td>
</tr>
</table>
</form>
</body>
</html>
