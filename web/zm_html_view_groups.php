<?php
//
// ZoneMinder web monitor groups file, $Date$, $Revision$
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

if ( !canEdit( 'System' ) )
{
	$view = "error";
	return;
}

$result = mysql_query( "select * from Groups order by Id" );
if ( !$result )
	die( mysql_error() );
$groups = array();
while ( $row = mysql_fetch_assoc( $result ) )
{
	$groups[] = $row;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>ZM - <?= $zmSlangGroups ?></title>
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
function configureButtons(form,name)
{
	var count = 0;
	for (var i = 0; i < form.elements.length; i++)
	{
		if ( form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				count++;
			}
		}
	}
	form.select_btn.disabled = (count != 1);
	form.new_btn.disabled = (count > 0);
	form.delete_btn.disabled = (count < 1);
}
function monitorIds()
{
	with ( opener.document.monitor_form )
	{
		var monitor_ids = new Array();
		for (var i = 0; i < elements.length; i++)
		{
			if ( elements[i].name.indexOf('mark_mids') == 0)
			{
				if ( elements[i].checked )
				{
					monitor_ids[monitor_ids.length] = elements[i].value;
				}
			}
		}
		return( monitor_ids.join( ',' ) );
	}
}
window.focus();
</script>
</head>
<body>
<form name="group_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="groups">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td class="smallhead"><?= $zmSlangName ?></td><td class="smallhead"><?= $zmSlangMonitorIds ?></td><td class="smallhead"><?= $zmSlangMark ?></tr>
<?php
if ( count($groups) || $new )
{
	foreach ( $groups as $group )
	{
?>
<tr>
<td align="left" class="text"><input class="form" name="names[<?= $group['Id'] ?>]" value="<?= $group['Name'] ?>" size="16"></td>
<td align="left" class="text"><input class="form" name="monitor_ids[<?= $group['Id'] ?>]" value="<?= $group['MonitorIds'] ?>" size="24"></td>
<td align="center"><input class="form-noborder" type="checkbox" name="mark_gids[]" value="<?= $group['Id'] ?>" onClick="configureButtons( document.group_form, 'mark_gids' );"></td>
</tr>
<?php
	}
	if ( $new )
	{
?>
<tr>
<td align="left" class="text"><input class="form" name="new_name" value="<?= $zmSlangNewGroup ?>" size="16"></td>
<td align="left" class="text"><input class="form" name="new_monitor_ids" value="<?= $monitor_ids ?>" size="24"></td>
<td align="center" class="text"><input type="checkbox" disabled></td>
</tr>
<?php
	}
}
else
{
?>
<tr><td colspan="3" class="text" align="center">&nbsp;</td></tr>
<tr><td colspan="3" class="text" align="center"><?= $zmSlangNoGroups ?></td></tr>
<tr><td colspan="3" class="text" align="center">&nbsp;</td></tr>
<?php
}
?>
<tr>
<td align="right" colspan="3" class="text">&nbsp;</td>
</tr>
<tr>
<td align="right" colspan="3" class="text">
<input type="button" name="select_btn" value="<?= $zmSlangSelect ?>" class="form" onClick="group_form.action.value='group'; group_form.submit();" disabled>&nbsp;&nbsp;
<input type="button" name="none_btn" value="<?= $zmSlangNone ?>" class="form" onClick="group_form.action.value='group'; group_form.submit();">&nbsp;&nbsp;
<input type="button" name="new_btn" value="<?= $zmSlangNew ?>" class="form" onClick="window.location='<?= $PHP_SELF ?>?view=<?= $view ?>&monitor_ids='+monitorIds()+'&new=1'">&nbsp;&nbsp;
<input type="button" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" onClick="group_form.action.value='<?= $view ?>'; group_form.action.value='delete'; group_form.submit();" disabled>&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangSave ?>" class="form" onClick="group_form.submit()">&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td>
</tr>
</table>
</form>
</body>
</html>
