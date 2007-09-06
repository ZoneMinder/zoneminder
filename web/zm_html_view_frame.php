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
	$sql = "select * from Frames where EventId = '$eid' and FrameId = '$fid'";
	if ( !($frame = dbFetchOne( $sql )) )
    {
        $frame = array( 'FrameId'=>$fid, 'Type'=>'Normal', 'Score'=>0 );
    }
}
else
{
	$frame = dbFetchOne( "select * from Frames where EventId = '$eid' and Score = '".$event['MaxScore']."'" );
	$fid = $frame['FrameId'];
}

$max_fid = $event['Frames'];

$first_fid = 1;
$prev_fid = $fid-1;
$next_fid = $fid+1;
$last_fid = $max_fid;

$alarm_frame = $frame['Type']=='Alarm';

if ( !isset( $scale ) )
	$scale = max( reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );

$image_data = getImageSrc( $event, $frame, $scale, (isset($show)&&$show=="capt") );

$image_path = $image_data['thumbPath'];
$event_path = $image_data['eventPath'];
$d_image_path = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-diag-d.jpg", $event_path, $fid );
$r_image_path = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-diag-r.jpg", $event_path, $fid );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangFrame ?> <?= $eid."-".$fid ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
window.focus();
function newWindow(Url,Name,Width,Height)
{
   	var Win = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	window.close();
}
function deleteEvent()
{
	location.href = "<?= $PHP_SELF ?>?view=none&action=delete&mark_eid=<?= $eid ?>";
	//window.close();
}
</script>
</head>
<body>
<table width="96%" cellpadding="3" cellspacing="0" border="0">
<tr>
<td width="60%" class="smallhead"><?= $zmSlangFrame ?> <?= $eid."-".$fid." (".$frame['Score'].")" ?>
<?php if ( ZM_RECORD_EVENT_STATS && $alarm_frame ) { ?>
(<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=stats&eid=<?= $eid ?>&fid=<?= $fid ?>', 'zmStats', <?= $jws['stats']['w'] ?>, <?= $jws['stats']['h'] ?> );"><?= $zmSlangStats ?></a>)
<?php } ?>
</td>
<td width="20%" align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="javascript: deleteEvent();"><?= $zmSlangDelete ?></a><?php } else { ?>&nbsp<?php } ?></td>
<td width="20%" align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<tr>
<td colspan="3" align="center"><?php if ( $image_data['hasAnalImage'] ) { ?><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $fid ?>&scale=<?= $scale ?>&show=<?= $image_data['isAnalImage']?"capt":"anal" ?>"><?php } ?><img src="<?= $image_path ?>" width="<?= reScale( $event['Width'], $event['DefaultScale'], $scale ) ?>" height="<?= reScale( $event['Height'], $event['DefaultScale'], $scale ) ?>" class="<?= $image_data['imageClass'] ?>"><?php if ( $image_data['hasAnalImage'] ) { ?></a><?php } ?></td>
</tr>
<tr>
<td colspan="3" align="center"><table width="96%" cellpadding="0" cellspacing="0" border="0"><tr>
<?php if ( $fid > 1 ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $first_fid ?>&scale=<?= $scale ?>"><?= $zmSlangFirst ?></a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid > 1 ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $prev_fid ?>&scale=<?= $scale ?>"><?= $zmSlangPrev ?></a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $next_fid ?>&scale=<?= $scale ?>"><?= $zmSlangNext ?></a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $last_fid ?>&scale=<?= $scale ?>"><?= $zmSlangLast ?></a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } ?>
</tr>
</table></td></tr>
<?php if (file_exists ($d_image_path)) { ?>
<tr><td colspan="3"><?= $d_image_path ?></tr>
<tr><td colspan="3"><img src="<?= $d_image_path ?>" width="<?= reScale( $event['Width'], $event['DefaultScale'], $scale ) ?>" height="<?= reScale( $event['He
ight'], $event['DefaultScale'], $scale ) ?>" class="<?= $image_data['imageClass'] ?>"></td></tr>
<?php } ?>
<?php if (file_exists ($r_image_path)) { ?>
<tr><td colspan="3"><?= $r_image_path ?></tr>
<tr><td colspan="3"><img src="<?= $r_image_path ?>" width="<?= reScale( $event['Width'], $event['DefaultScale'], $scale ) ?>" height="<?= reScale( $event['He
ight'], $event['DefaultScale'], $scale ) ?>" class="<?= $image_data['imageClass'] ?>"></td></tr>
<?php } ?>
</table>
</body>
</html>
