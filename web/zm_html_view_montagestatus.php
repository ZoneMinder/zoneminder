<?php
//
// ZoneMinder web montage status view file, $Date$, $Revision$
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

if ( !canView( 'Stream' ) )
{
	$view = "error";
	return;
}
$zmu_command = ZMU_COMMAND." -m $mid -s -f";
$zmu_output = exec( escapeshellcmd( $zmu_command ) );
list( $status, $fps ) = split( ' ', $zmu_output );
$status_string = $zmSlangUnknown;
$fps_string = "--.--";
$class = "text";
if ( $status <= 1 )
{
	$status_string = $zmSlangIdle;
}
elseif ( $status == 2 )
{
	$status_string = $zmSlangAlarm;
	$class = "redtext";
}
elseif ( $status == 3 )
{
	$status_string = $zmSlangAlert;
	$class = "ambtext";
}
elseif ( $status == 4 )
{
	$status_string = $zmSlangRecord;
}
$fps_string = sprintf( "%.2f", $fps );
$new_alarm = ( $status > 0 && empty($last_status) );
$old_alarm = ( $status == 0 && isset($last_status) && $last_status > 0 );

$refresh = $status?1:ZM_WEB_REFRESH_STATUS;
$url = "$PHP_SELF?view=montagestatus&mid=$mid&last_status=$status";
if ( ZM_WEB_REFRESH_METHOD == "http" )
	header("Refresh: $refresh; URL=$url" );
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");			  // HTTP/1.0

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( ZM_WEB_POPUP_ON_ALARM && $new_alarm )
{
?>
top.window.focus();
<?php
}
if ( ZM_WEB_REFRESH_METHOD == "javascript" )
{
?>
window.setTimeout( "window.location.reload(true)", <?= $refresh*1000 ?> );
<?php
}
?>
</script>
</head>
<body>
<table width="90%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
<td width="15%" class="text" align="left">&nbsp;</td>
<td width="70%" class="<?= $class ?>" align="center" valign="middle"><?= $zmSlangStatus ?>:&nbsp;<?= $status_string ?>&nbsp;-&nbsp;<?= $fps_string ?>&nbsp;<?= $zmSlangFPS ?></td>
<td width="15%" align="right" class="text">&nbsp;</td>
</tr>
</table>
<?php
if ( ZM_WEB_SOUND_ON_ALARM && $status == 1 )
{
?>
<embed src="<?= ZM_DIR_SOUNDS.'/'.ZM_WEB_ALARM_SOUND ?>" autostart="yes" hidden="true"></embed>
<?php
}
?>
</body>
</html>
