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

if ( $user['MonitorIds'] )
{
	$mid_sql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
}
else
{
	$mid_sql = '';
}

$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '$eid'$mid_sql";
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
$event = mysql_fetch_assoc( $result );

parseSort();
parseFilter();

$sql = "select * from Events as E where $sort_column ".($sort_order=='asc'?'<=':'>=')." '".$event[$sort_field]."'$filter_sql$mid_sql order by $sort_column ".($sort_order=='asc'?'desc':'asc');
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
while ( $row = mysql_fetch_assoc( $result ) )
{
	if ( $row[Id] == $eid )
	{
		$prev_event = mysql_fetch_assoc( $result );
		break;
	}
}

$sql = "select * from Events as E where $sort_column ".($sort_order=='asc'?'>=':'<=')." '".$event[$sort_field]."'$filter_sql$mid_sql order by $sort_column $sort_order";
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
while ( $row = mysql_fetch_assoc( $result ) )
{
	if ( $row[Id] == $eid )
	{
		$next_event = mysql_fetch_assoc( $result );
		break;
	}
}

if ( !isset( $rate ) )
	$rate = ZM_WEB_DEFAULT_RATE;
if ( !isset( $scale ) )
	$scale = ZM_WEB_DEFAULT_SCALE;

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
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="text" size="16" name="event_name" value="<?= $event['Name'] ?>" class="form">
<input type="submit" value="<?= $zmSlangRename ?>" class="form"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></form></td>
<?php if ( 0 ) { ?>
<td colspan="2" align="right" class="text">
<form name="learn_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="learn">
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
<input type="hidden" name="eid" value="<?= $eid ?>">
<?= $filter_fields ?>
<?= $zmSlangRate ?>: <?= buildSelect( "rate", $rates, "document.view_form.submit();" ); ?>&nbsp;&nbsp;
<?= $zmSlangScale ?>: <?= buildSelect( "scale", $scales, "document.view_form.submit();" ); ?>
</form>
</td>
</tr>
<tr>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="javascript: refreshWindow();"><?= $zmSlangReplay ?></a></td>
<?php } elseif ( $paged && !empty($page) ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>&page=0"><?= $zmSlangAll ?></a></td>
<?php } elseif ( $paged && empty($page) ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>&page=1"><?= $zmSlangPaged ?></a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="<?= $PHP_SELF ?>?view=none&action=delete&mark_eid=<?= $eid ?>"><?= $zmSlangDelete ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<?php if ( $event['Archived'] ) { ?>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=unarchive&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>"><?= $zmSlangUnarchive ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<?php } else { ?>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=archive&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>"><?= $zmSlangArchive ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<?php } ?>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>&page=1"><?= $zmSlangStills ?></a></td>
<?php } elseif ( canStream() ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=stream&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>"><?= $zmSlangStream ?></a></td>
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
<a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>&page=<?= $new_page ?>"><?= $new_page ?></a>&nbsp;
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
&nbsp;<a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>&page=<?= $new_page ?>"><?= $new_page ?></a>
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
	if ( ZM_VIDEO_STREAM_METHOD == 'mpeg' && ZM_VIDEO_REPLAY_FORMAT )
	{
		$stream_src = ZM_PATH_ZMS."?mode=mpeg&event=$eid&scale=$scale&rate=$rate&bitrate=".VIDEO_BITRATE."&maxfps=".VIDEO_MAXFPS."&format=".ZM_VIDEO_REPLAY_FORMAT;
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
<PARAM NAME="FileName" VALUE="<?= $stream_src ?>"
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
SRC="<?= $stream_src ?>"
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
src="<?= $stream_src ?>"
width=<?= reScale( $event['Width'], $scale ) ?>
height=<?= reScale( $event['Height'], $scale ) ?>
AutoStart=true>
</EMBED>
<?php
		}
	}
	else
	{
		$stream_src = ZM_PATH_ZMS."?mode=jpeg&event=$eid&scale=$scale&rate=$rate&maxfps=".VIDEO_MAXFPS;
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
		$lo_frame_id = 1;
		$hi_frame_id = $event['Frames'];
	}
	$sql = "select * from Frames where EventID = '$eid'";
	if ( $paged && !empty($page) )
		$sql .= " and FrameId between $lo_frame_id and $hi_frame_id";
	$sql .= " order by FrameId";
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
	for ( $frame_id = $lo_frame_id; $frame_id <= $hi_frame_id; $frame_id++ )
	{
		$image_path = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $event_path, $frame_id );

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
<td align="center" width="88"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $frame_id ?>', 'zmImage', <?= $event['Width']+$jws['image']['w'] ?>, <?= $event['Height']+$jws['image']['h'] ?> );"><img src="<?= $thumb_image ?>" width="<?= $thumb_width ?>" height="<?= $thumb_height ?>" class="<?= $img_class ?>" alt="<?= $frame_id ?>/<?= $alarm_frame?$alarm_frame['Score']:0 ?>"></a></td>
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
<td width="25%" align="center" class="text"><?php if ( $prev_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $prev_event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>"><?= $zmSlangPrev ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( canEdit( 'Events' ) && $prev_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $prev_event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&action=delete&mark_eid=<?= $eid ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>"><?= $zmSlangDeleteAndPrev ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( canEdit( 'Events' ) && $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $next_event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&action=delete&mark_eid=<?= $eid ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>"><?= $zmSlangDeleteAndNext ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $next_event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>"><?= $zmSlangNext ?></a><?php } else { ?>&nbsp;<?php } ?></td>
</tr></table></td>
</tr>
</table>
</body>
</html>
