<?php
//
// ZoneMinder web watch feed view file, $Date$, $Revision$
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

if ( empty($mode) )
{
	if ( canStream() )
		$mode = "stream";
	else
		$mode = "still";
}

if ( !isset( $scale ) )
	$scale = ZM_WEB_DEFAULT_SCALE;

$sql = "select M.*,C.CanMoveMap,C.CanMoveRel from Monitors as M left join Controls as C on (M.ControlId = C.Id ) where M.Id = '$mid'";
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );

if ( $mode != "stream" )
{
	// Prompt an image to be generated
	if ( ZM_WEB_REFRESH_METHOD == "http" )
		header("Refresh: ".ZM_WEB_REFRESH_IMAGE."; URL=$PHP_SELF?view=watchfeed&mid=$mid&mode=still&scale=$scale" );
}
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");			  // HTTP/1.0

$image_src = getStreamSrc( array( "mode=single", "monitor=".$mid, "scale=".$scale ) );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangFeed ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( $mode != "stream" && ZM_WEB_REFRESH_METHOD == "javascript" )
{
	if ( ZM_WEB_DOUBLE_BUFFER )
	{
?>
function fetchImage()
{
	var now = new Date();
	var zm_image = new Image();
	zm_image.src = '<?= $image_src ?>'+'&'+now.getTime();

	document['zmImage'].src = zm_image.src;
}

window.setInterval( "fetchImage()", <?= ZM_WEB_REFRESH_IMAGE*1000 ?> );
<?php
	}
	else
	{
?>
window.setTimeout( "window.location.reload(true)", <?= ZM_WEB_REFRESH_IMAGE*1000 ?> );
<?php
	}
}
?>
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="2">
<tr><td colspan="5" align="center">
<?php
if ( $mode == "stream" )
{
	if ( ZM_VIDEO_STREAM_METHOD == 'mpeg' && ZM_VIDEO_LIVE_FORMAT )
	{
		$stream_src = getStreamSrc( array( "mode=mpeg", "monitor=".$mid, "scale=".$scale, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_VIDEO_LIVE_FORMAT ) );
		if ( isWindows() )
		{
			if ( isInternetExplorer() )
			{
?>
<object id="MediaPlayer1" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>"
classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,902"
standby="Loading Microsoft Windows Media Player components..."
type="application/x-oleobject">
<param name="FileName" value="<?= $stream_src ?>">
<param name="animationatStart" value="true">
<param name="transparentatStart" value="true">
<param name="autoStart" value="true">
<param name="showControls" value="false">
</OBJECT>
<?php
			}
			else
			{
?>
<embed type="application/x-mplayer2"
pluginspage = "http://www.microsoft.com/Windows/MediaPlayer/"
src="<?= $stream_src ?>"
name="MediaPlayer1"
width="<?= reScale( $monitor['Width'], $scale ) ?>"
height="<?= reScale( $monitor['Height'], $scale ) ?>"
autostart="true">
</embed>
<?php
			}
		}
		else
		{
?>
<embed type="video/mpeg"
src="<?= $stream_src ?>"
width="<?= reScale( $monitor['Width'], $scale ) ?>"
height="<?= reScale( $monitor['Height'], $scale ) ?>"
autostart="true">
</embed>
<?php
		}
	}
	else
	{
		$stream_src = getStreamSrc( array( "mode=jpeg", "monitor=".$mid, "scale=".$scale, "maxfps=".ZM_WEB_VIDEO_MAXFPS ) );
		if ( canStreamNative() )
		{
			if ( $control && ($monitor['CanMoveMap'] || $monitor['CanMoveRel']) )
			{
?>
<form name="ctrl_form" method="get" action="<?= $PHP_SELF ?>" target="ControlSink<?= $mid ?>">
<input type="hidden" name="view" value="blank">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="action" value="control">
<?php
				if ( $monitor['CanMoveMap'] ) 
				{
?>
<input type="hidden" name="control" value="move_map">
<?php
				}
				elseif ( $monitor['CanMoveRel'] )
				{
?>
<input type="hidden" name="control" value="move_pseudo_map">
<?php
				}
?>
<input type="hidden" name="scale" value="<?= $scale ?>">
<input type="image" src="<?= $stream_src ?>" border="0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>">
</form>
<?php
			}
			else
			{
?>
<img src="<?= $stream_src ?>" alt="<?= $monitor['Name'] ?>" border="0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>">
<?php
			}
		}
		else
		{
?>
<applet code="com.charliemouse.cambozola.Viewer" archive="<?= ZM_PATH_CAMBOZOLA ?>" align="middle" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>"><param name="url" value="<?= $stream_src ?>"></applet>
<?php
		}
	}
}
else
{
	if ( $control && ($monitor['CanMoveMap'] || $monitor['CanMoveRel']) )
	{
?>
<form name="ctrl_form" method="get" action="<?= $PHP_SELF ?>" target="ControlSink<?= $mid ?>">
<input type="hidden" name="view" value="blank">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="action" value="control">
<?php
				if ( $monitor['CanMoveMap'] ) 
				{
?>
<input type="hidden" name="control" value="move_map">
<?php
				}
				elseif ( $monitor['CanMoveRel'] )
				{
?>
<input type="hidden" name="control" value="move_pseudo_map">
<?php
				}
?>
<input type="image" src="<?= $image_src ?>" border="0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>">
</form>
<?php
	}
	else
	{
?>
<img name="zmImage" src="<?= $image_src ?>" alt="<?= $monitor['Name'] ?>" border="0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>">
<?php
	}
}
?>
</td></tr>
</table>
</body>
</html>
