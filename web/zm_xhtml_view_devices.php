<?php
//
// MobileWatch web devices file, $Date$, $Revision$
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
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangDevices ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css"/>
</head>
<body>
<table style="width: 100%">
<tr><td colspan="3" align="center"><?= $zmSlangDevices ?></td></tr>
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
<tr>
<td align="left" style="width: 60%"><span class="<?= $fclass ?>"><?= substr( $device['Name'], 0, 16 ) ?></span></td>
<td align="center"><?= makeLink( $PHP_SELF."?view=".$view."&amp;action=device&amp;key=".$device['KeyString']."&amp;command=on", $zmSlangOn, canEdit('Devices') ) ?></td>
<td align="center"><?= makeLink( $PHP_SELF."?view=".$view."&amp;action=device&amp;key=".$device['KeyString']."&amp;command=off", $zmSlangOff, canEdit('Devices') ) ?></td>
</tr>
<?php
}
?>
</table>
<p align="center"><a href="<?= $PHP_SELF ?>?view=console"><?= $zmSlangConsole ?></a></p>
</body>
</html>
