<?php
//
// ZoneMinder web montage feed view file, $Date$, $Revision$
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

if ( !canView( 'Stream' ) )
{
	$view = "error";
	return;
}

if ( empty($mode) )
{
	if ( ZM_WEB_USE_STREAMS && canStream() )
		$mode = "stream";
	else
		$mode = "still";
}

if ( ZM_OPT_CONTROL )
{
	$sql = "select M.*,C.CanMoveMap,C.CanMoveRel from Monitors as M left join Controls as C on (M.ControlId = C.Id ) where M.Id = '$mid'";
}
else
{
	$sql = "select * from Monitors where Id = '$mid'";
}
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );

$montage_width = ZM_WEB_MONTAGE_WIDTH?ZM_WEB_MONTAGE_WIDTH:reScale( $monitor['Width'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
$montage_height = ZM_WEB_MONTAGE_HEIGHT?ZM_WEB_MONTAGE_HEIGHT:reScale( $monitor['Height'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
$width_scale = ($montage_width*SCALE_SCALE)/$monitor['Width'];
$height_scale = ($montage_height*SCALE_SCALE)/$monitor['Height'];
$scale = (int)(($width_scale<$height_scale)?$width_scale:$height_scale);

if ( $mode != "stream" )
{
	// Prompt an image to be generated
	if ( ZM_WEB_REFRESH_METHOD == "http" )
		header("Refresh: ".ZM_WEB_REFRESH_IMAGE."; URL=$PHP_SELF?view=montagefeed&mid=$mid&mode=still&scale=$scale" );
}
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");			  // HTTP/1.0

$image_src = getStreamSrc( array( "mode=single", "monitor=".$monitor['Id'], "scale=".$scale ) );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $monitor['Name'] ?> - <?= $zmSlangFeed ?></title>
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
<table width="100%" align="center" border="0" cellspacing="0" cellpadding="0">
<tr><td align="center">
<?php
if ( $mode == "stream" )
{
	if ( ZM_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT )
	{
		$stream_src = getStreamSrc( array( "mode=mpeg", "monitor=".$monitor['Id'], "scale=".$scale, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_LIVE_FORMAT ) );
		outputVideoStream( $stream_src, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'], ZM_MPEG_LIVE_FORMAT );
	}
	else
	{
		$stream_src = getStreamSrc( array( "mode=jpeg", "monitor=".$mid, "scale=".$scale, "maxfps=".ZM_WEB_VIDEO_MAXFPS ) );
		if ( canStreamNative() )
		{
			if ( $control && ($monitor['CanMoveMap'] || $monitor['CanMoveRel'] || $monitor['CanMoveCon']) )
			{
				outputControlStream( $stream_src, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor, $scale, "MontageSink".$mid );
			}
			else
			{
				outputImageStream( $stream_src, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] );
			}
		}
		else
		{
			outputHelperStream( $stream_src, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ) );
		}
	}
}
else
{
	if ( $control && ($monitor['CanMoveMap'] || $monitor['CanMoveRel'] || $monitor['CanMoveCon']) )
	{
		outputControlStill( $image_src, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor, $scale, "MontageSink".$mid );
	}
	else
	{
		outputImageStill( $image_src, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] );
	}
}
?>
</td></tr>
</table>
</body>
</html>
