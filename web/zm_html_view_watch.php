<?php
//
// ZoneMinder web watch view file, $Date$, $Revision$
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
$result = mysql_query( "select * from Monitors where Id = '$mid'" );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );

if ( !isset($scale) )
	$scale = ZM_WEB_DEFAULT_SCALE;

$width_scale = ($scale<SCALE_SCALE)?SCALE_SCALE:$scale;
$height_scale = $scale;
?>
<html>
<head>
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangWatch ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
window.resizeTo( <?= reScale($monitor['Width'],$width_scale)+$jws['watch']['w'] ?>, <?= reScale($monitor['Height'],$height_scale)+$jws['watch']['h'] ?> );
//opener.location.reload();
window.focus();
</script>
</head>
<frameset rows="24,<?= (ZM_WEB_REFRESH_METHOD=='javascript'&&ZM_WEB_DOUBLE_BUFFER)?'0,':'' ?><?= reScale($monitor['Height'],$height_scale)+8 ?>,16,*" border="1" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=watchmenu&mode=<?= $mode ?>&mid=<?= $monitor['Id'] ?>&scale=<?= $scale ?>" marginwidth="0" marginheight="0" name="MonitorMenu<?= $monitor['Id' ] ?>" scrolling="no">
<?php
if ( ZM_WEB_REFRESH_METHOD == 'javascript' && ZM_WEB_DOUBLE_BUFFER )
{
?>
<frame src="about:blank" name="MonitorFetch<?= $monitor['Id'] ?>" scrolling="no">
<?php
}
?>
<frame src="<?= $PHP_SELF ?>?view=watchfeed&mode=<?= $mode ?>&mid=<?= $monitor['Id'] ?>&scale=<?= $scale ?>" marginwidth="0" marginheight="0" name="MonitorStream<?= $monitor['Id' ] ?>" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=watchstatus&mid=<?= $monitor['Id'] ?>" marginwidth="0" marginheight="0" name="MonitorStatus<?= $monitor['Id' ] ?>" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=watchevents&max_events=<?= MAX_EVENTS ?>&mid=<?= $monitor['Id'] ?>" marginwidth="0" marginheight="0" name="MonitorEvents<?= $monitor['Id' ] ?>" scrolling="auto">
</frameset>
