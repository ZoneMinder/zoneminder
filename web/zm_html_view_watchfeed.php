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
if ( !isset($mode) )
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

if ( $mode != "stream" )
{
	// Prompt an image to be generated
	chdir( ZM_DIR_IMAGES );
	$status = exec( escapeshellcmd( ZMU_COMMAND." -m $mid -i" ) );
	chdir( '..' );
	if ( ZM_WEB_REFRESH_METHOD == "http" )
		header("Refresh: ".REFRESH_IMAGE."; URL=$PHP_SELF?view=watchfeed&mid=$mid&mode=still" );
}
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");			  // HTTP/1.0

if ( !isset( $scale ) )
	$scale = 1;

?>
<html>
<head>
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangFeed ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
<?php
if ( $mode != "stream" && ZM_WEB_REFRESH_METHOD == "javascript" )
{
?>
window.setTimeout( "window.location.reload(true)", <?= REFRESH_IMAGE*1000 ?> );
<?php
}
?>
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
<tr>
<td width="25%" align="left" class="text"><b><?= $monitor['Name'] ?></b></td>
<?php if ( $mode == "stream" ) { ?>
<form name="view_form" method="get" action="<?= $PHP_SELF ?>" target="_parent">
<td align="center" valign="middle" class="text">
<input type="hidden" name="view" value="watch">
<input type="hidden" name="mid" value="<?= $mid ?>">
<?= $zmSlangScale ?>: <?= buildSelect( "scale", $scales, "document.view_form.submit();" ); ?>
</td>
</form>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<?php if ( canView( 'Monitors' ) && $monitor['Type'] == "Local" ) { ?>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=settings&mid=<?= $monitor['Id'] ?>', 'zmSettings<?= $monitor['Id'] ?>', <?= $jws['settings']['w'] ?>, <?= $jws['settings']['h'] ?> );"><?= $zmSlangSettings ?></a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=watchfeed&mode=still&mid=<?= $mid ?>"><?= $zmSlangStills ?></a></td>
<?php } elseif ( canStream() ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=watchfeed&mode=stream&mid=<?= $mid ?>"><?= $zmSlangStream ?></a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<?php
if ( $mode == "stream" )
{
	$stream_src = ZM_PATH_ZMS."?monitor=".$monitor['Id']."&idle=".STREAM_IDLE_DELAY."&refresh=".STREAM_FRAME_DELAY."&scale=$scale";
	if ( canStreamNative() )
	{
?>
<tr><td colspan="5" align="center"><img src="<?= $stream_src ?>" border="0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>"></td></tr>
<?php
	}
	else
	{
?>
<tr><td colspan="5" align="center"><applet code="com.charliemouse.cambozola.Viewer" archive="<?= ZM_PATH_CAMBOZOLA ?>" align="middle" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>"><param name="url" value="<?= $stream_src ?>"></applet></td></tr>
<?php
	}
}
else
{
?>
<tr><td colspan="5" align="center"><img src="<?= ZM_DIR_IMAGES.'/'.$monitor['Name'] ?>.jpg" border="0" width="<?= $monitor['Width'] ?>" height="<?= $monitor['Height'] ?>"></td></tr>
<?php
}
?>
</table>
</body>
</html>
