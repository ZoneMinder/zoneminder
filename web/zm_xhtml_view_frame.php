<?php
//
// ZoneMinder web frame view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}
$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height,M.DefaultScale from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '$eid'";
$event = dbFetchOne( $sql );

if ( $fid )
{
	$frame = dbFetchOne( "select * from Frames where EventID = '$eid' and FrameId = '$fid'" );
}
else
{
	$frame = dbFetchOne( "select * from Frames where EventID = '$eid' and Score = '".$event['MaxScore']."'" );
	$fid = $frame['FrameId'];
}

$max_fid = $event['Frames'];

$first_fid = 1;
$prev_fid = $fid-1;
$next_fid = $fid+1;
$last_fid = $max_fid;

$scale = getDeviceScale( $event['Width'], $event['Height'] );

$image_data = getImageSrc( $event, $frame, $scale, (isset($show)&&$show=="capt") );

$image_path = $image_data['thumbPath'];
$event_path = $image_data['eventPath'];

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangFrame ?> <?= $eid."-".$fid ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css"/>
</head>
<body>
<table>
<tr>
<td class="head"><?= $zmSlangFrame ?> <?= $eid."-".$fid." (".$frame['Score'].")" ?></td>
</tr>
</table>
<table>
<tr><td><?php if ( $has_anal_image ) { ?><a href="<?= $PHP_SELF ?>?view=frame&amp;eid=<?= $eid ?>&amp;fid=<?= $fid ?>&amp;show=<?= $image_path==$anal_image?"capt":"anal" ?>"><?php } ?><img src="<?= $thumb_image_path ?>" width="<?= reScale( $event['Width'], $scale ) ?>" height="<?= reScale( $event['Height'], $scale ) ?>" class="<?= $img_class ?>"/><?php if ( $has_anal_image ) { ?></a><?php } ?></td></tr>
<tr><td><?php if ( $image_data['hasAnalImage'] ) { ?><a href="<?= $PHP_SELF ?>?view=frame&amp;eid=<?= $eid ?>&amp;fid=<?= $fid ?>&amp;show=<?= $image_data['isAnalImage']?"capt":"anal" ?>"><?php } ?><img src="<?= $image_path ?>" width="<?= reScale( $event['Width'], $scale ) ?>" height="<?= reScale( $event['Height'], $scale ) ?>" class="<?= $image_data['imageClass'] ?>"/><?php if ( $image_data['hasAnalImage'] ) { ?></a><?php } ?></td></tr>
</table>
<table>
<tr>
<?php if ( $fid > 1 ) { ?>
<td align="center"><a href="<?= $PHP_SELF ?>?view=frame&amp;eid=<?= $eid ?>&amp;fid=<?= $first_fid ?>">&lt;&lt;</a></td>
<?php } else { ?>
<td align="center">&nbsp;</td>
<?php } if ( $fid > 1 ) { ?>
<td align="center"><a href="<?= $PHP_SELF ?>?view=frame&amp;eid=<?= $eid ?>&amp;fid=<?= $prev_fid ?>">&lt;</a></td>
<?php } else { ?>
<td align="center">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center"><a href="<?= $PHP_SELF ?>?view=frame&amp;eid=<?= $eid ?>&amp;fid=<?= $next_fid ?>">&gt;</a></td>
<?php } else { ?>
<td align="center">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center"><a href="<?= $PHP_SELF ?>?view=frame&amp;eid=<?= $eid ?>&amp;fid=<?= $last_fid ?>">&gt;&gt;</a></td>
<?php } else { ?>
<td align="center">&nbsp;</td>
<?php } ?>
</tr>
</table>
</body>
</html>
