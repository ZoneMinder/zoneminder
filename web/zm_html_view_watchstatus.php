<?php
//
// ZoneMinder web watch status view file, $Date$, $Revision$
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
if ( canEdit( 'Monitors' ) && isset($force) )
{
	$zmu_command .= ($force?" -a":" -c"); 
}

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
$new_alarm = ( $status > 0 && $last_status == 0 );
$old_alarm = ( $status == 0 && $last_status > 0 );

$refresh = (isset($force)||$forced||($status==1)||($status==2))?1:ZM_WEB_REFRESH_STATUS;
$url = "$PHP_SELF?view=watchstatus&mid=$mid&last_status=$status".(($force||$forced)?"&forced=1":"");
if ( ZM_WEB_REFRESH_METHOD == "http" )
	header("Refresh: $refresh; URL=$url" );
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");			  // HTTP/1.0

?>
<html>
<head>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script language="JavaScript">
<?php
if ( ZM_WEB_POPUP_ON_ALARM && $new_alarm )
{
?>
top.window.focus();
<?php
}
if ( $old_alarm )
{
?>
window.parent.MonitorEvents<?= $mid ?>.location.reload(true);
<?php
}
if ( ZM_WEB_REFRESH_METHOD == "javascript" )
{
?>
window.setTimeout( "window.location.replace( '<?= $url ?>' )", <?= $refresh*1000 ?> );
<?php
}
?>
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
<td width="15%" class="text" align="left">&nbsp;</td>
<td width="70%" class="<?= $class ?>" align="center" valign="middle"><?= $zmSlangStatus ?>:&nbsp;<?= $status_string ?>&nbsp;-&nbsp;<?= $fps_string ?>&nbsp;fps</td>
<?php
if ( canEdit( 'Monitors' ) && ($force || $forced) )
{
?>
<td width="15%" align="right" class="text"><a href="<?= $PHP_SELF ?>?view=watchstatus&mid=<?= $mid ?>&last_status=$status&force=0"><?= $zmSlangCancelForcedAlarm ?></a></td>
<?php
}
elseif ( canEdit( 'Monitors' ) && zmaCheck( $mid ) )
{
?>
<td width="15%" align="right" class="text"><a href="<?= $PHP_SELF ?>?view=watchstatus&mid=<?= $mid ?>&last_status=$status&force=1"><?= $zmSlangForceAlarm ?></a></td>
<?php
}
else
{
?>
<td width="15%" align="right" class="text">&nbsp;</td>
<?php
}
?>
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
