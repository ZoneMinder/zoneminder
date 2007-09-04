<?php
//
// ZoneMinder web video view file, $Date$, $Revision$
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

if ( $user['MonitorIds'] )
{
	$mid_sql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
}
else
{
	$mid_sql = '';
}
$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height,M.DefaultRate,M.DefaultScale from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '$eid'$mid_sql";
$result = mysql_query( $sql );
if ( !$result )
    die( mysql_error() );
$event = mysql_fetch_assoc( $result );
mysql_free_result( $result );

if ( !isset( $rate ) )
	$rate = reScale( RATE_BASE, $event['DefaultRate'], ZM_WEB_DEFAULT_RATE );
if ( !isset( $scale ) )
	$scale = reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE );

$event_path = getEventPath( $event );

$video_formats = array();
$ffmpeg_formats = preg_split( '/\s+/', ZM_FFMPEG_FORMATS );
foreach ( $ffmpeg_formats as $ffmpeg_format )
{
	if ( preg_match( '/^([^*]+)(\*\*?)$/', $ffmpeg_format, $matches ) )
	{
		$video_formats[$matches[1]] = $matches[1];
		if ( !isset($video_format) && $matches[2] == "*" )
		{
			$video_format = $matches[1];
		}
	}
	else
	{
		$video_formats[$ffmpeg_format] = $ffmpeg_format;
	}
}

$video_files = array();
if ( $dir = opendir( $event_path ) )
{
	while ( ($file = readdir( $dir )) !== false )
	{
		$file = $event_path.'/'.$file;
		if ( is_file( $file ) )
		{
			if ( preg_match( '/\.(?:'.join( '|', $video_formats ).')$/', $file ) )
			{
				$video_files[] = $file;
			}
		}
	}
	closedir( $dir );
}

if ( !empty($download) )
{
	header( "Content-disposition: attachment; filename=".$video_files[$download]."; size=".filesize($video_files[$download]) );
	readfile( $video_files[$download] );
	exit;
}

ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangVideo ?> - <?= $event['Name'] ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function newWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
function closeWindow()
{
	window.close();
}
function deleteVideo( index )
{
	location.replace( '<?= $PHP_FILE ?>?view=<?= $view ?>&eid=<?= $eid ?>&delete='+index );
}
</script>
</head>
<body>
<?php
if ( isset( $show ) )
{
	preg_match( '/([^\/]+)\.([^.]+)$/', $video_files[$show], $matches );
	$name = $matches[1];
	$video_format = $matches[2];
?>
<table align="center" border="0" cellspacing="0" cellpadding="2" width="96%">
<tr><td width="50%">&nbsp;</td><td width="50%" class="text" align="right"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="2" width="96%">
<tr><td align="center"><?php outputVideoStream( $video_files[$show], $width, $height, $name, $video_format ) ?></td></tr>
</table>
<?php
}
elseif ( !empty($generate) )
{
?>
<table border="0" cellspacing="0" cellpadding="4" width="100%" height="70">
<tr>
<td align="center" valign="middle" class="head"><?= $zmSlangGeneratingVideo ?></td>
</tr>
</table>
<?php
	$buffer_string = "<!-- This is some long buffer text to ensure that IE flushes correctly -->";
	for ( $i = 0; $i < 8192/strlen($buffer_string); $i++ )
	{
		echo $buffer_string."\n";
	}
?>
</body>
</html>
<?php
	ob_end_flush();

	if ( $video_file = createVideo( $event, $video_format, $rate, $scale, $overwrite ) )
	{
		$video_path = $event_path.'/'.$video_file;
	}
?>
<html>
<head>
<script type="text/javascript">
location.replace('<?= $PHP_FILE ?>?view=<?= $view ?>&eid=<?= $eid ?>&video_format=<?= $video_format ?>&rate=<?= $rate ?>&scale=<?= $scale ?>&generated=<?= $video_file?1:0 ?>');
</script>
</head>
<body>
<?php
}
else
{
?>
<form name="video_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="hidden" name="generate" value="1">
<table align="center" border="0" cellspacing="0" cellpadding="2" width="96%">
<tr><td width="50">&nbsp;</td><td class="head" align="center"><?= $zmSlangVideoGenParms ?></td><td width="50" class="text" align="right"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="2" width="96%">
<tr><td width="50%"><img src="graphics/spacer.gif" width="1" height="5"></td><td width="50%"><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
<tr><td class="text" align="right"><?= $zmSlangVideoFormat ?></td><td><?= buildSelect( "video_format", $video_formats ) ?></td></tr>
<tr><td class="text" align="right"><?= $zmSlangFrameRate ?></td><td><?= buildSelect( "rate", $rates ) ?></td></tr>
<tr><td class="text" align="right"><?= $zmSlangVideoSize ?></td><td><?= buildSelect( "scale", $scales ) ?></td></tr>
<tr><td class="text" align="right"><?= $zmSlangOverwriteExisting ?></td><td><input type="checkbox" class="form-noborder" name="overwrite" value="1"<?php if ( isset($overwrite) ) { ?> checked<?php } ?>></td></tr>
<tr><td colspan="2"><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" class="form" value="<?= $zmSlangGenerateVideo ?>"<?= ( ZM_OPT_MPEG == "no" )?" disabled":"" ?>></td></tr>
</table>
</form>
<table align="center" border="0" cellspacing="0" cellpadding="8" width="96%">
<?php
	if ( isset($generated) )
	{
		if ( $generated )
		{
?>
<tr><td align="center" valign="middle" class="head"><font color="green"><?= $zmSlangVideoGenSucceeded ?></font></td></tr>
<?php
		}
		else
		{
?>
<tr><td align="center" valign="middle" class="head"><font color="red"><?= $zmSlangVideoGenFailed ?></font></td></tr>
<?php
		}
	}
?>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="6" width="96%">
<tr><td class="head" align="center"><?= $zmSlangVideoGenFiles ?></td></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="0" width="96%">
<tr><td>
<table align="center" border="0" cellspacing="0" cellpadding="3">
<?php
	if ( count($video_files) )
	{
?>
<tr>
  <td class="text" align="center"><?= $zmSlangFormat ?></td>
  <td class="text" align="center"><?= $zmSlangSize ?></td>
  <td class="text" align="center"><?= $zmSlangRate ?></td>
  <td class="text" align="center"><?= $zmSlangScale ?></td>
  <td class="text" align="center"><?= $zmSlangAction ?></td>
</tr>
<?php
		if ( isset($delete) )
		{
			unlink( $video_files[$delete] );
			unset( $video_files[$delete] );
		}
		if ( count($video_files) > 0 )
		{
			$index = 0;
			foreach ( $video_files as $file )
			{
				if ( filesize( $file ) > 0 )
				{
					preg_match( '/^(.+)-((?:r[_\d]+)|(?:F[_\d]+))-((?:s[_\d]+)|(?:S[0-9a-z]+))\.([^.]+)$/', $file, $matches );
					if ( preg_match( '/^r(.+)$/', $matches[2], $temp_matches ) )
					{
						$rate = (int)(100 * preg_replace( '/_/', '.', $temp_matches[1] ) );
						$rate_text = isset($rates[$rate])?$rates[$rate]:($rate."x");
					}
					elseif ( preg_match( '/^F(.+)$/', $matches[2], $temp_matches ) )
					{
						$rate_text = $temp_matches[1]."fps";
					}
					if ( preg_match( '/^s(.+)$/', $matches[3], $temp_matches ) )
					{
						$scale = (int)(100 * preg_replace( '/_/', '.', $temp_matches[1] ) );
						$scale_text = isset($scales[$scale])?$scales[$scale]:($scale."x");
					}
					elseif ( preg_match( '/^S(.+)$/', $matches[3], $temp_matches ) )
					{
						$scale_text = $temp_matches[1];
					}
					$width = $scale?reScale( $event['Width'], $scale ):$event['Width'];
					$height = $scale?reScale( $event['Height'], $scale ):$event['Height'];
?>
<tr>
  <td class="text" align="center"><?= $matches[4] ?></td>
  <td class="text" align="center"><?= filesize( $file ) ?></td>
  <td class="text" align="center"><?= $rate_text ?></td>
  <td class="text" align="center"><?= $scale_text ?></td
  <td class="text" align="center"><a href="javascript:newWindow( '<?= $PHP_SELF ?>?view=<?= $view ?>&eid=<?= $eid ?>&width=<?= $width ?>&height=<?= $height ?>&show=<?= $index ?>', 'zmVideo<?= $eid ?>-<?= $scale ?>', <?= 24+$width ?>, <?= 48+$height ?> );"><?= $zmSlangView ?></a>&nbsp;/&nbsp;<a href="<?= $file ?>" onClick="window.location='<?= $PHP_SELF ?>?view=<?= $view ?>&eid=<?= $eid ?>&download=<?= $index ?>'"><?= $zmSlangDownload ?></a>&nbsp;/&nbsp;<a href="javascript: deleteVideo( <?= $index ?> )"><?= $zmSlangDelete ?></a></td>
</tr>
<?php
					$index++;
				}
			}
		}
	}
	else
	{
?>
<tr><td class="text" align="center"><br><?= $zmSlangVideoGenNoFiles ?></td></tr>
<?php
	}
?>
</table>
</td></tr>
</table>
<?php
}
?>
</body>
</html>
<?php
ob_end_flush();
?>
