<?php
//
// ZoneMinder web event view file, $Date$, $Revision$
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
if ( !isset($mode) )
{
	if ( canStream() )
		$mode = "stream";
	else
		$mode = "still";
}

$result = mysql_query( "select E.*,M.Name as MonitorName,M.Width,M.Height from Events as E, Monitors as M where E.Id = '$eid' and E.MonitorId = M.Id" );
if ( !$result )
	die( mysql_error() );
$event = mysql_fetch_assoc( $result );

if ( empty($mid) )
{
	$mid = 0;
	$mid_sql = '';
}
else
{
	$mid_sql = " and MonitorId = '$mid'";
}
$result = mysql_query( "select * from Events where Id < '$eid'$mid_sql order by Id desc limit 0,1" );
if ( !$result )
	die( mysql_error() );
$prev_event = mysql_fetch_assoc( $result );

$result = mysql_query( "select * from Events where Id > '$eid'$mid_sql order by Id asc limit 0,1" );
if ( !$result )
	die( mysql_error() );
$next_event = mysql_fetch_assoc( $result );

if ( !isset( $rate ) )
	$rate = RATE_SCALE;
if ( !isset( $scale ) )
	$scale = SCALE_SCALE;

$frames_per_page = EVENT_FRAMES_PER_LINE * EVENT_FRAME_LINES;

$paged = $event['Frames'] > $frames_per_page;

?>
<html>
<head>
<title>ZM - <?= $zmSlangEvent ?> - <?= $event['Name'] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
if ( !$event )
{
?>
opener.location.reload(true);
window.close();
<?php
}
?>
window.focus();
<?php
if ( !empty($refresh_parent) )
{
?>
opener.location.reload(true);
<?php
}
?>
function refreshWindow()
{
	window.location.reload(true);
}
function closeWindow()
{
	window.close();
}
function newWindow(Url,Name,Width,Height)
{
	var Name = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
</script>
</head>
<body scroll="auto">
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="3" align="left" class="text">
<form name="rename_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="text" size="16" name="event_name" value="<?= $event['Name'] ?>" class="form">
<input type="submit" value="<?= $zmSlangRename ?>" class="form"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></form></td>
<?php if ( 0 ) { ?>
<td colspan="2" align="right" class="text">
<form name="learn_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="learn">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="hidden" name="mark_eid" value="<?= $eid ?>">
<?php if ( LEARN_MODE ) { ?>
Learn Pref:&nbsp;<select name="learn_state" class="form" onChange="learn_form.submit();"><option value=""<?php if ( !$event['LearnState'] ) echo " selected" ?>><?= $zmSlangIgnore ?></option><option value="-"<?php if ( $event['LearnState']=='-' ) echo " selected" ?>><?= $zmSlangExclude ?></option><option value="+"<?php if ( $event['LearnState']=='+' ) echo " selected" ?>><?= $zmSlangInclude ?></option></select>
<?php } ?>
</form></td>
<?php } ?>
<td colspan="3" align="right" class="text">
<form name="view_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<?= $zmSlangRate ?>: <?= buildSelect( "rate", $rates, "document.view_form.submit();" ); ?>&nbsp;&nbsp;
<?= $zmSlangScale ?>: <?= buildSelect( "scale", $scales, "document.view_form.submit();" ); ?>
</form>
</td>
</tr>
<tr>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="javascript: refreshWindow();"><?= $zmSlangReplay ?></a></td>
<?php } elseif ( $paged && !empty($page) ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&mid=<?= $mid ?>&eid=<?= $eid ?>&page=0"><?= $zmSlangAll ?></a></td>
<?php } elseif ( $paged && empty($page) ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&mid=<?= $mid ?>&eid=<?= $eid ?>&page=1"><?= $zmSlangPaged ?></a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="<?= $PHP_SELF ?>?view=none&action=delete&mid=<?= $mid ?>&mark_eid=<?= $eid ?>"><?= $zmSlangDelete ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<?php if ( $event['Archived'] ) { ?>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=unarchive&mid=<?= $mid ?>&eid=<?= $eid ?>"><?= $zmSlangUnarchive ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<?php } else { ?>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=archive&mid=<?= $mid ?>&eid=<?= $eid ?>"><?= $zmSlangArchive ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<?php } ?>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&mid=<?= $mid ?>&eid=<?= $eid ?>&page=1"><?= $zmSlangStills ?></a></td>
<?php } elseif ( canStream() ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=stream&mid=<?= $mid ?>&eid=<?= $eid ?>"><?= $zmSlangStream ?></a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<?php if ( ZM_OPT_MPEG != "no" ) { ?>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=video&eid=<?= $eid ?>', 'zmVideo', <?= $jws['video']['w']+$event['Width'] ?>, <?= $jws['video']['h']+$event['Height'] ?> );"><?= $zmSlangVideo ?></a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<?php
if ( $mode == "still" && $paged && !empty($page) )
{
?>
<?php
	$pages = (int)ceil($event['Frames']/$frames_per_page);
	$max_shortcuts = 5;
?>
<tr><td colspan="6" align="center" class="text">
<?php
	if ( $page < 0 )
		$page = 1;
	if ( $page > $pages )
		$page = $pages;

	if ( $page > 1 )
	{
		$new_pages = array();
		$pages_used = array();
		$lo_exp = max(2,log($page-1)/log($max_shortcuts));
		for ( $i = 0; $i < $max_shortcuts; $i++ )
		{
			$new_page = round($page-pow($lo_exp,$i));
			if ( isset($pages_used[$new_page]) )
				continue;
			if ( $new_page <= 1 )
				break;
			$pages_used[$new_page] = true;
			array_unshift( $new_pages, $new_page );
		}
		if ( !isset($pages_used[1]) )
			array_unshift( $new_pages, 1 );

		foreach ( $new_pages as $new_page )
		{
?>
<a href="<?= $PHP_SELF ?>?view=event&mode=still&mid=<?= $mid ?>&eid=<?= $eid ?>&page=<?= $new_page ?>"><?= $new_page ?></a>&nbsp;
<?php
		}
	}
?>
-&nbsp;<?= $page ?>&nbsp;-
<?php
	if ( $page < $pages )
	{
		$new_pages = array();
		$pages_used = array();
		$hi_exp = max(2,log($pages-$page)/log($max_shortcuts));
		for ( $i = 0; $i < $max_shortcuts; $i++ )
		{
			$new_page = round($page+pow($hi_exp,$i));
			if ( isset($pages_used[$new_page]) )
				continue;
			if ( $new_page > $pages )
				break;
			$pages_used[$new_page] = true;
			array_push( $new_pages, $new_page );
		}
		if ( !isset($pages_used[$pages]) )
			array_push( $new_pages, $pages );

		foreach ( $new_pages as $new_page )
		{
?>
&nbsp;<a href="<?= $PHP_SELF ?>?view=event&mode=still&mid=<?= $mid ?>&eid=<?= $eid ?>&page=<?= $new_page ?>"><?= $new_page ?></a>
<?php
		}
	}
?>
</td></tr>
<?php
}
?>
<?php
if ( $mode == "stream" )
{
?>
<tr><td colspan="6" align="center" valign="middle">
<?php
	if ( ZM_WEB_VIDEO_STREAM_METHOD == 'mpeg' )
	{
		$stream_src = ZM_PATH_ZMS."?mode=mpeg&event=$eid&rate=$rate&scale=$scale&bit_rate=".VIDEO_BITRATE;
		if ( isWindows() )
		{
			if ( isInternetExplorer() )
			{
?>
<OBJECT ID="MediaPlayer1" width=<?= reScale( $event['Width'], $scale ) ?> height=<?= reScale( $event['Height'], $scale ) ?> 
classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,902"
standby="Loading Microsoft Windows Media Player components..."
type="application/x-oleobject">
<PARAM NAME="FileName" VALUE="<?= $stream_src."&format=asf" ?>"
<PARAM NAME="animationatStart" VALUE="true">
<PARAM NAME="transparentatStart" VALUE="true">
<PARAM NAME="autoStart" VALUE="true">
<PARAM NAME="showControls" VALUE="false">
</OBJECT>
<?php
			}
			else
			{
?>
<EMBED type="application/x-mplayer2"
pluginspage = "http://www.microsoft.com/Windows/MediaPlayer/"
SRC="<?= $stream_src."&format=asf" ?>"
name="MediaPlayer1"
width=<?= reScale( $event['Width'], $scale ) ?>
height=<?= reScale( $event['Height'], $scale ) ?>
AutoStart=true>
</EMBED>
<?php
			}
		}
		else
		{
?>
<EMBED type="video/mpeg"
src="<?= $stream_src."&format=mpeg" ?>"
width=<?= reScale( $event['Width'], $scale ) ?>
height=<?= reScale( $event['Height'], $scale ) ?>
AutoStart=true>
</EMBED>
<?php
		}
	}
	else
	{
		$stream_src = ZM_PATH_ZMS."?mode=jpeg&event=$eid&rate=$rate&scale=$scale";
		if ( canStreamNative() )
		{
?>
<img src="<?= $stream_src ?>" border="0" width="<?= reScale( $event['Width'], $scale ) ?>" height="<?= reScale( $event['Height'], $scale ) ?>">
<?php
		}
		else
		{
?>
<applet code="com.charliemouse.cambozola.Viewer" archive="<?= ZM_PATH_CAMBOZOLA ?>" align="middle" width="<?= reScale( $event['Width'], $scale ) ?>" height="<?= reScale( $event['Height'], $scale ) ?>"><param name="url" value="<?= $stream_src ?>"></applet>
<?php
		}
	}
?>
</td></tr>
<?php
}
else
{
	if ( $paged && !empty($page) )
	{
		$lo_frame_id = (($page-1)*$frames_per_page)+1;
		$hi_frame_id = min( $page*$frames_per_page, $event['Frames'] );
	}
	else
	{
		$lo_frame = 1;
		$hi_frame = $event['Frames'];
	}
	$sql = "select * from Frames where EventID = '$eid'";
	if ( $paged && !empty($page) )
		$sql .= " and FrameId between $lo_frame_id and $hi_frame_id";
	$sql .= "order by FrameId";
	$result = mysql_query( $sql );
	if ( !$result )
		die( mysql_error() );
	$alarm_frames = array();
	while( $row = mysql_fetch_assoc( $result ) )
	{
		if ( $row['Type'] == 'Alarm' )
		{
			$alarm_frames[$row['FrameId']] = $row;
		}
	}
?>
<tr><td colspan="6"><table border="0" cellpadding="0" cellspacing="2" align="center">
<tr>
<?php
	$count = 0;
	$scale = IMAGE_SCALING;
	$fraction = sprintf( "%.2f", 1/$scale );
	$thumb_width = $event['Width']/4;
	$thumb_height = $event['Height']/4;
	$event_path = ZM_DIR_EVENTS.'/'.$event['MonitorName'].'/'.$event['Id'];
	for ( $frame_id = $lo_frame; $frame_id <= $hi_frame_id; $frame_id++ )
	{
		$image_path = sprintf( "%s/%03d-capture.jpg", $event_path, $frame_id );

		$capt_image = $image_path;
		if ( $scale == 1 || !file_exists( ZM_PATH_NETPBM."/jpegtopnm" ) )
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
			$thumb_image = preg_replace( "/capture/", "thumb", $capt_image );

			if ( !file_exists($thumb_image) || !filesize( $thumb_image ) )
			{
				$anal_image = preg_replace( "/capture/", "analyse", $capt_image );
				if ( file_exists( $anal_image ) )
					$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $anal_image | ".ZM_PATH_NETPBM."/pnmscalefixed $fraction | ".ZM_PATH_NETPBM."/ppmtojpeg --dct=fast > $thumb_image";
				else
					$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $capt_image | ".ZM_PATH_NETPBM."/pnmscalefixed $fraction | ".ZM_PATH_NETPBM."/ppmtojpeg --dct=fast > $thumb_image";
				#exec( escapeshellcmd( $command ) );
				exec( $command );
			}
		}
		$alarm_frame = $alarm_frames[$frame_id];
		$img_class = $alarm_frame?"alarm":"normal";
?>
<td align="center" width="88"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $frame_id ?>', 'zmImage', <?= $event['Width']+$jws['image']['w'] ?>, <?= $event['Height']+$jws['image']['h'] ?> );"><img src="<?= $thumb_image ?>" width="<?= $thumb_width ?>" height="<? echo $thumb_height ?>" class="<?= $img_class ?>" alt="<?= $frame_id ?>/<?= $alarm_frame['Score'] ?>"></a></td>
<?php
		flush();
		if ( !(++$count % 4) )
		{
?>
</tr>
<tr>
<?php
		}
	}
?>
</tr>
</table></td></tr>
<?php
}
?>
<tr>
<td colspan="6"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td width="25%" align="center" class="text"><?php if ( $prev_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $prev_event['Id'] ?>&page=<?= $page ?>"><?= $zmSlangPrev ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( canEdit( 'Events' ) && $prev_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $prev_event['Id'] ?>&action=delete&mid=<?= $mid ?>&mark_eid=<?= $eid ?>&page=<?= $page ?>"><?= $zmSlangDeleteAndPrev ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( canEdit( 'Events' ) && $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $next_event['Id'] ?>&action=delete&mid=<?= $mid ?>&mark_eid=<?= $eid ?>&page=<?= $page ?>"><?= $zmSlangDeleteAndNext ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $next_event['Id'] ?>&page=<?= $page ?>"><?= $zmSlangNext ?></a><?php } else { ?>&nbsp;<?php } ?></td>
</tr></table></td>
</tr>
</table>
</body>
</html>
