<?php
//
// ZoneMinder web montage feed view file, $Date$, $Revision$
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

$result = mysql_query( "select * from Monitors where Id = '$mid'" );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );

$montage_width = ZM_WEB_MONTAGE_WIDTH?ZM_WEB_MONTAGE_WIDTH:$monitor['Width'];
$montage_height = ZM_WEB_MONTAGE_HEIGHT?ZM_WEB_MONTAGE_HEIGHT:$monitor['Height'];
$width_scale = ($montage_width*SCALE_SCALE)/$monitor['Width'];
$height_scale = ($montage_height*SCALE_SCALE)/$monitor['Height'];
$scale = (int)(($width_scale<$height_scale)?$width_scale:$height_scale);

if ( $mode != "stream" )
{
	if ( !ZM_WEB_DOUBLE_BUFFER )
	{
		// Prompt an image to be generated
		createImage( $monitor, $scale );
	}
	if ( ZM_WEB_REFRESH_METHOD == "http" )
		header("Refresh: ".REFRESH_IMAGE."; URL=$PHP_SELF?view=montagefeed&mid=$mid&mode=still&scale=$scale" );
}
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");			  // HTTP/1.0

?>
<html>
<head>
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangFeed ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
if ( $mode != "stream" && ZM_WEB_REFRESH_METHOD == "javascript" )
{
	if ( ZM_WEB_DOUBLE_BUFFER )
	{
?>
function fetchImage()
{
	window.parent.MontageFetch<?= $monitor['Id'] ?>.location.reload( true );

	var now = new Date();
	var zm_image = new Image();
	zm_image.src = '<?= ZM_DIR_IMAGES.'/'.$monitor['Name'] ?>.jpg?'+now.getTime();

	document['zmImage'].src = zm_image.src;
}

window.parent.MontageFetch<?= $monitor['Id'] ?>.location = '<?= $PHP_SELF ?>?view=imagefetch&mid=<?= $monitor['Id'] ?>&scale=<?= $scale ?>';
window.setInterval( "fetchImage()", <?= REFRESH_IMAGE*1000 ?> );
<?php
	}
	else
	{
?>
window.setTimeout( "window.location.reload(true)", <?= REFRESH_IMAGE*1000 ?> );
<?php
	}
}
?>
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="2">
<tr><td align="center">
<?php
if ( $mode == "stream" )
{
	if ( ZM_WEB_VIDEO_STREAM_METHOD == 'mpeg' )
	{
		$stream_src = ZM_PATH_ZMS."?mode=mpeg&monitor=".$monitor['Id']."&scale=$scale&bitrate=".VIDEO_BITRATE."&buffer=0";
		if ( isWindows() )
		{
			if ( isInternetExplorer() )
			{
?>
<OBJECT ID="MediaPlayer1" width=<?= reScale( $monitor['Width'], $scale ) ?> height=<?= reScale( $monitor['Height'], $scale ) ?> 
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
width=<?= reScale( $monitor['Width'], $scale ) ?>
height=<?= reScale( $monitor['Height'], $scale ) ?>
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
width=<?= reScale( $monitor['Width'], $scale ) ?>
height=<?= reScale( $monitor['Height'], $scale ) ?>
AutoStart=true>
</EMBED>
<?php
		}
	}
	else
	{
		$stream_src = ZM_PATH_ZMS."?mode=jpeg&monitor=".$monitor['Id']."&idle=".STREAM_IDLE_DELAY."&refresh=".STREAM_FRAME_DELAY."&scale=$scale";
		if ( canStreamNative() )
		{
?>
<img src="<?= $stream_src ?>" border="0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>">
<?php
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
?>
<img name="zmImage" src="<?= ZM_DIR_IMAGES.'/'.$monitor['Name'] ?>.jpg" border="0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>">
<?php
}
?>
</td></tr>
</table>
</body>
</html>
