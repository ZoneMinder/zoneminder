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

if ( empty($scale) )
	$scale = 1;

$width_scale = ($scale<1)?1:$scale;
$height_scale = ($scale<1)?(1/abs($scale)):$scale;
?>
<html>
<head>
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangWatch ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
window.resizeTo( <?= ($width_scale*$monitor['Width'])+$jws['watch']['w'] ?>, <?= ($height_scale*$monitor['Height'])+$jws['watch']['h'] ?> );
//opener.location.reload();
window.focus();
</script>
</head>
<frameset rows="<?= ($height_scale*$monitor['Height'])+32 ?>,16,*" border="1" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=watchfeed&mid=<?= $monitor['Id'] ?>&scale=<?= $scale ?>" marginwidth="0" marginheight="0" name="MonitorStream" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=watchstatus&mid=<?= $monitor['Id'] ?>" marginwidth="0" marginheight="0" name="MonitorStatus" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=watchevents&max_events=<?= MAX_EVENTS ?>&mid=<?= $monitor['Id'] ?>" marginwidth="0" marginheight="0" name="MonitorEvents" scrolling="auto">
</frameset>
