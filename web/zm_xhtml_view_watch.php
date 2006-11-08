<?php
//
// ZoneMinder web watch view file, $Date$, $Revision$
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

if ( !canView( 'Stream' ) )
{
	$view = "error";
	return;
}
$result = mysql_query( "select * from Monitors where Id = '$mid'" );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );
mysql_free_result( $result );

if ( !isset($scale) )
	$scale = ZM_WEB_DEFAULT_SCALE;

$width_scale = ($scale<SCALE_BASE)?SCALE_BASE:$scale;
$height_scale = $scale;

$zmu_command = getZmuCommand( " -m $mid -s -f" );
$zmu_output = exec( escapeshellcmd( $zmu_command ) );
list( $status, $fps ) = split( ' ', $zmu_output );
$status_string = $zmSlangUnknown;
$fps_string = "--.--";
$class = "text";
if ( $status <= STATE_PREALARM )
{
	$status_string = $zmSlangIdle;
}
elseif ( $status == STATE_ALARM )
{
	$status_string = $zmSlangAlarm;
	$class = "redtext";
}
elseif ( $status == STATE_ALERT )
{
	$status_string = $zmSlangAlert;
	$class = "ambtext";
}
elseif ( $status == STATE_TAPE )
{
	$status_string = $zmSlangRecord;
}
$fps_string = sprintf( "%.2f", $fps );
$new_alarm = ( $status > STATE_PREALARM && $last_status <= STATE_PREALARM );
$old_alarm = ( $status <= STATE_PREALARM && $last_status > STATE_PREALARM );

$result = mysql_query( "select * from Monitors where Function != 'None' order by Sequence" );
$monitors = array();
$mon_idx = 0;
$max_width = 0;
$max_height = 0;
while( $row = mysql_fetch_assoc( $result ) )
{
	if ( !visibleMonitor( $row['Id'] ) )
	{
		continue;
	}
	if ( isset($mid) && $row['Id'] == $mid )
		$mon_idx = count($monitors);
	if ( $max_width < $row['Width'] ) $max_width = $row['Width'];
	if ( $max_height < $row['Height'] ) $max_height = $row['Height'];
	$monitors[] = $row;
}
mysql_free_result( $result );

//$monitor = $monitors[$mon_idx];
$next_mid = $mon_idx==(count($monitors)-1)?$monitors[0]['Id']:$monitors[$mon_idx+1]['Id'];
$prev_mid = $mon_idx==0?$mon_index[(count($monitors)-1)]['Id']:$monitors[$mon_idx-1]['Id'];

$device_width = (isset($device)&&!empty($device['width']))?$device['width']:DEVICE_WIDTH;
$device_height = (isset($device)&&!empty($device['height']))?$device['height']:DEVICE_HEIGHT;
// Allow for margins etc
$device_width -= 16;
$device_height -= 16;

$width_scale = ($device_width*SCALE_BASE)/$monitor['Width'];
$height_scale = ($device_height*SCALE_BASE)/$monitor['Height'];
$scale = (int)(($width_scale<$height_scale)?$width_scale:$height_scale);

$image_src = getStreamSrc( array( "mode=single", "monitor=".$monitor['Id'], "scale=".$scale ) );
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $monitor['Name'] ?> - <?= $zmSlangWatch ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css"/>
</head>
<body>
<p class="<?= $class ?>" align="center"><?= makeLink( "$PHP_SELF?view=events&amp;page=1&amp;filter=1&amp;trms=1&amp;attr1=MonitorId&amp;op1=%3d&amp;val1=".$monitor['Id']."&amp;sort_field=Id&amp;sort_desc=1", $monitor['Name'], canView( 'Events' ) ) ?>:&nbsp;<?= $status_string ?>&nbsp;-&nbsp;<?= $fps_string ?>&nbsp;fps</p>
<p align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;mid=<?= $monitor['Id'] ?>"><img src="<?= $image_src ?>" alt="<?= $monitor['Name'] ?>" style="border: 0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>"/></a></p>
<?php
if ( $next_mod != $mid || $prev_mid != $mid )
{
?>
<table>
<tr>
<td align="left"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;mid=<?= $prev_mid ?>"><?= $zmSlangPrev ?></a></td>
<td align="center"><a href="<?= $PHP_SELF ?>?view=console"><?= $zmSlangConsole ?></a></td>
<td align="right"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;mid=<?= $next_mid ?>"><?= $zmSlangNext ?></a></td>
</tr>
</table>
<?php
}
?>
</body>
</html>
