<?php
//
// ZoneMinder web frame view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}
$result = mysql_query( "select E.*,M.Name as MonitorName,M.Width,M.Height from Events as E, Monitors as M where E.Id = '$eid' and E.MonitorId = M.Id" );
if ( !$result )
	die( mysql_error() );
$event = mysql_fetch_assoc( $result );

if ( $fid )
{
	$result = mysql_query( "select * from Frames where EventID = '$eid' and FrameId = '$fid'" );
	if ( !$result )
		die( mysql_error() );
	$frame = mysql_fetch_assoc( $result );
}
else
{
	$result = mysql_query( "select * from Frames where EventID = '$eid' and Score = '".$event['MaxScore']."'" );
	if ( !$result )
		die( mysql_error() );
	$frame = mysql_fetch_assoc( $result );
	$fid = $frame['FrameId'];
}

$max_fid = $event['Frames'];

$first_fid = 1;
$prev_fid = $fid-1;
$next_fid = $fid+1;
$last_fid = $max_fid;

$event_path = ZM_DIR_EVENTS.'/'.$event['MonitorName'].'/'.$event['Id'];
$image_path = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $event_path, $fid );
$anal_image = preg_replace( "/capture/", "analyse", $image_path );
if ( file_exists( $anal_image ) )
{
	$image_path = $anal_image;
}
$alarm_frame = $frame['Type']=='Alarm';
$img_class = $alarm_frame?"alarm":"normal";

$device_width = (isset($device)&&!empty($device['width']))?$device['width']:DEVICE_WIDTH;
$device_height = (isset($device)&&!empty($device['height']))?$device['height']:DEVICE_HEIGHT;
// Allow for margins etc
$device_width -= 16;
$device_height -= 16;

$width_scale = ($device_width*SCALE_SCALE)/$event['Width'];
$height_scale = ($device_height*SCALE_SCALE)/$event['Height'];
$scale = (int)(($width_scale<$height_scale)?$width_scale:$height_scale);

$event_path = ZM_DIR_EVENTS.'/'.$event['MonitorName'].'/'.$event['Id'];
$image_path = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $event_path, $fid );

$capt_image = $image_path;
if ( $scale == 100 || !file_exists( ZM_PATH_NETPBM."/jpegtopnm" ) )
{
	$anal_image = preg_replace( "/capture/", "analyse", $image_path );

	if ( file_exists($anal_image) && filesize( $anal_image ) )
	{
		$thumb_image = $anal_image;
	}
	else
	{
		$thumb_image = $capt_image;
	}
}
else
{
	$thumb_image = preg_replace( "/capture/", "$scale", $capt_image );

	if ( !file_exists($thumb_image) || !filesize( $thumb_image ) )
	{
		$fraction = sprintf( "%.2f", $scale/100 );
		$anal_image = preg_replace( "/capture/", "analyse", $capt_image );
		if ( file_exists( $anal_image ) )
			$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $anal_image | ".ZM_PATH_NETPBM."/pnmscalefixed $fraction | ".ZM_PATH_NETPBM."/ppmtojpeg --dct=fast > $thumb_image";
		else
			$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $capt_image | ".ZM_PATH_NETPBM."/pnmscalefixed $fraction | ".ZM_PATH_NETPBM."/ppmtojpeg --dct=fast > $thumb_image";
		exec( $command );
	}
}

?>
<html>
<head>
<title>ZM - <?= $zmSlangFrame ?> <?= $eid."-".$fid ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
</head>
<body>
<table>
<tr>
<td class="smallhead"><?= $zmSlangFrame ?> <?= $eid."-".$fid." (".$frame['Score'].")" ?></td>
<!--<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="javascript: deleteEvent();"><?= $zmSlangDelete ?></a><?php } else { ?>&nbsp<?php } ?></td>-->
</tr>
</table>
<table>
<tr><td><img src="<?= $thumb_image ?>" width="<?= reScale( $event['Width'], $scale ) ?>" height="<?= reScale( $event['Height'], $scale ) ?>" class="<?= $img_class ?>"></td></tr>
</table>
<table>
<tr>
<?php if ( $fid > 1 ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $first_fid ?>">&lt;&lt;</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } if ( $fid > 1 ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $prev_fid ?>">&lt;</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $next_fid ?>">&gt;</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $last_fid ?>">&gt;&gt;</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
</tr>
</table>
</body>
</html>
