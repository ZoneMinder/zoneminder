<?php
//
// ZoneMinder web devices file, $Date$, $Revision$
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

if ( !canView( 'Devices' ) )
{
    $view = "error";
     return;
}

$sql = "select * from Devices where Type = 'X10' order by Name";
$result = mysql_query( $sql );
if ( !$result )
	echo mysql_error();
$devices = array();
while( $row = mysql_fetch_assoc( $result ) )
{
	$row['Status'] = getDeviceStatusX10( $row['KeyString'] );
	$devices[] = $row;
}
mysql_free_result( $result );
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangDevices ?></title>
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
function editDevice( id )
{
	newWindow( '<?= $PHP_SELF ?>?view=device&did='+id, 'zmDevice', <?= $jws['device']['w'] ?>, <?= $jws['device']['h'] ?> );
}
function switchDeviceOn( key )
{
	devices_form.view.value='<?= $view ?>';
	devices_form.action.value='device';
	devices_form.command.value='on';
	devices_form.key.value=key;
	devices_form.submit();
}
function switchDeviceOff( key )
{
	devices_form.view.value='<?= $view ?>';
	devices_form.action.value='device';
	devices_form.command.value='off';
	devices_form.key.value=key;
	devices_form.submit();
}
function deleteDevice()
{
	devices_form.view.value='<?= $view ?>';
	devices_form.action.value='delete';
	devices_form.submit();
}
function configureButtons(form,name)
{
	var checked = false;
	for (var i = 0; i < form.elements.length; i++)
	{
		if ( form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				checked = true;
				break;
			}
		}
	}
	form.delete_btn.disabled = !checked;
}
window.focus();
</script>
</head>
<body>
<form name="devices_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="device">
<input type="hidden" name="key" value="">
<input type="hidden" name="command" value="">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr><td class="smallhead" align="left"><?= $zmSlangDevices ?></td></tr>
<tr>
<td>
<table width="100%" border="0" cellspacing="1" cellpadding="2" bgcolor="#7F7FB2">
<?php
foreach( $devices as $device )
{
	if ( $device['Status'] == 'ON' )
	{
		$fclass = "gretext";
	}
	elseif ( $device['Status'] == 'OFF' )
	{
		$fclass = "ambtext";
	}
	else
	{
		$fclass = "redtext";
	}
?>
<tr bgcolor="#FFFFFF">
<td align="left"><?= makeLink( "javascript:editDevice( ".$device['Id']." )", '<span class="'.$fclass.'">'.$device['Name'].' ('.$device['KeyString'].')</span>', canEdit( 'Devices' ) ) ?></td>
<td align="center"><input type="button" class="form" value="<?= $zmSlangOn ?>" onClick="switchDeviceOn( '<?= $device['KeyString'] ?>' )"<?= ($device['Status'] != 'ON' && canEdit( 'Devices' ) )?"":' set="set"' ?>></td>
<td align="center"><input type="button" class="form" value="<?= $zmSlangOff ?>" onClick="switchDeviceOff( '<?= $device['KeyString'] ?>' )"<?= ($device['Status'] != 'OFF' && canEdit( 'Devices' ) )?"":' set="set"' ?>></td>
<td align="center" class="text"><input type="checkbox" class="form" name="mark_dids[]" value="<?= $device['Id'] ?>" onClick="configureButtons( document.devices_form, 'mark_dids' );"<?php if ( !canEdit( 'Devices' ) ) {?> disabled<?php } ?>></td>
</tr>
<?php
}
?>
<tr bgcolor="#FFFFFF">
<td align="right" colspan="4" class="text">
<input type="button" value="<?= $zmSlangNew ?>" class="form" onClick="editDevice( 0 )"<?= canEdit('Devices')?"":" disabled" ?>>&nbsp;&nbsp;
<input type="button" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" onClick="deleteDevice()"<?= $selected&&canEdit('Devices')?"":" disabled" ?>>&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td>
</tr>
</table>
</td>
</tr>
</table>
</form>
</body>
</html>
