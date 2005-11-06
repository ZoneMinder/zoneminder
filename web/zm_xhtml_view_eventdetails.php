<?php
//
// ZoneMinder web event details view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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

$result = mysql_query( "select * from Frames where EventID = '$eid' and Score = '".$event['MaxScore']."'" );
if ( !$result )
	die( mysql_error() );
$frame = mysql_fetch_assoc( $result );
$fid = $frame['FrameId'];

$device_width = (isset($device)&&!empty($device['width']))?$device['width']:DEVICE_WIDTH;
$device_height = (isset($device)&&!empty($device['height']))?$device['height']:DEVICE_HEIGHT;
// Allow for margins etc
$device_width -= 16;
$device_height -= 16;

$width_scale = ($device_width*SCALE_SCALE)/$event['Width'];
$height_scale = ($device_height*SCALE_SCALE)/$event['Height'];
$scale = (int)(($width_scale<$height_scale)?$width_scale:$height_scale);
$scale /= 2;

$image1 = getThumbnail( $event, 1, $scale );
if ( $frame['Type'] == 'Alarm' )
{
	$image2 = getThumbnail( $event, $fid, $scale );
}
else
{
	$image2 = getThumbnail( $event, (int)($event['Frames']/2), $scale );
}

function getThumbnail( $event, $fid, $scale )
{
	$event_path = ZM_DIR_EVENTS.'/'.$event['MonitorId'].'/'.$event['Id'];
	$image_path = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $event_path, $fid );
	$anal_image = preg_replace( "/capture/", "analyse", $image_path );
	if ( file_exists( $anal_image ) )
	{
		$image_path = $anal_image;
	}
	$alarm_frame = $frame['Type']=='Alarm';

	if ( $scale == 100 || !file_exists( ZM_PATH_NETPBM."/jpegtopnm" ) )
	{
		$thumb_image = $image_path;
	}
	else
	{
		$thumb_image = preg_replace( "/(capture|analyse)/", "$scale", $image_path );

		if ( !file_exists($thumb_image) || !filesize( $thumb_image ) )
		{
			$fraction = sprintf( "%.2f", $scale/100 );
			if ( file_exists( $image_path ) )
				$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $image_path | ".ZM_PATH_NETPBM."/pnmscalefixed $fraction | ".ZM_PATH_NETPBM."/ppmtojpeg --dct=fast > $thumb_image";
			exec( $command );
		}
	}
	return( $thumb_image );
}

?>
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangEvent ?> <?= $eid ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css">
</head>
<body>
<table>
<tr><td><?= $zmSlangName ?>&nbsp;</td><td><?= htmlentities($event['Name']) ?><?= $event['Archived']?("(".$zmSlangArchived.")"):"" ?></td></tr>
<tr><td><?= $zmSlangTime ?>&nbsp;</td><td><?= htmlentities(strftime("%b %d, %H:%M",strtotime($event['StartTime']))) ?></td></tr>
<tr><td><?= $zmSlangDuration ?>&nbsp;</td><td><?= htmlentities($event['Length']) ?>s</td></tr>
<tr><td><?= $zmSlangCause ?>&nbsp;</td><td><?= htmlentities($event['Cause']) ?></td></tr>
<?php if ( !empty($event['Notes']) ) { ?>
<tr><td><?= $zmSlangNotes ?>&nbsp;</td><td><?= htmlentities($event['Notes']) ?></td></tr>
<?php } ?>
<tr><td><?= $zmSlangFrames ?>&nbsp;</td><td><?= $event['Frames'] ?> (<?= $event['AlarmFrames'] ?>)</td></tr>
<tr><td><?= $zmSlangScore ?>&nbsp;</td><td><?= $event['TotScore'] ?>/<?= $event['AvgScore'] ?>/<?= $event['MaxScore'] ?></td></tr>
</table>
<table>
<tr>
<td><a href="<?= $PHP_SELF ?>?view=frame&amp;eid=<?= $eid ?>&amp;fid=1"><img src="<?= $image1 ?>" style="border: 0" width="<?= reScale( $event['Width'], $scale ) ?>" height="<?= reScale( $event['Height'], $scale ) ?>" alt="1"></a></td>
<td><a href="<?= $PHP_SELF ?>?view=frame&amp;eid=<?= $eid ?>&amp;fid=<?= $fid ?>"><img src="<?= $image2 ?>" style="border: 0" width="<?= reScale( $event['Width'], $scale ) ?>" height="<?= reScale( $event['Height'], $scale ) ?>" alt="<?= $fid ?>"></a></td>
</tr>
</table>
<table>
<tr><td><a href="<?= $PHP_SELF ?>?view=event&amp;eid=<?= $eid ?>&amp;page=1"><?= $zmSlangFrames ?></a></td><td><a href="<?= $PHP_SELF ?>?view=video&amp;eid=<?= $eid ?>"><?= $zmSlangVideo ?></a></td></tr>
</table>
</body>
</html>
