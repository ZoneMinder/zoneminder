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
$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height,M.DefaultRate from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '$eid'$mid_sql";
$event = dbFetchOne( $sql );

$device_width = (isset($device)&&!empty($device['width']))?$device['width']:DEVICE_WIDTH;
$device_height = (isset($device)&&!empty($device['height']))?$device['height']:DEVICE_HEIGHT;

if ( $device_width >= 352 && $device_height >= 288 )
{
	$video_size = "352x288";
}
elseif ( $device_width >= 176 && $device_height >= 144 )
{
	$video_size = "176x144";
}
else
{
	$video_size = "128x96";
}

$event_width = $event['Width'];
$event_height = $event['Height'];

if ( !isset( $rate ) )
	$rate = reScale( RATE_BASE, $event['DefaultRate'], ZM_WEB_DEFAULT_RATE );

$event_path = getEventPath( $event );

$video_formats = array();
$ffmpeg_formats = preg_split( '/\s+/', ZM_FFMPEG_FORMATS );
foreach ( $ffmpeg_formats as $ffmpeg_format )
{
	preg_match( '/^([^*]+)(\**)$/', $ffmpeg_format, $matches );
	$video_formats[$matches[1]] = $matches[1];
	if ( $matches[2] == '*' )
	{
		$default_video_format = $matches[1];
	}
	elseif ( $matches[2] == '**' )
	{
		$default_phone_format = $matches[1];
	}
}
if ( !isset($video_format) )
{
    if ( isset($default_phone_format) )
    {
        $video_format = $default_phone_format;
    }
    elseif ( isset($default_video_format) )
    {
        $video_format = $default_video_format;
    }
    else
    {
        $video_format = $ffmpeg_formats[0];
    }
}

if ( !empty($generate) )
{
	if ( version_compare( phpversion(), "4.3.10", ">=") )
		$command = ZM_PATH_BIN."/zmvideo.pl -e ".$event['Id']." -f ".$video_format." -r ".sprintf( "%.2F", ($rate/RATE_BASE) )." -S ".$video_size;
	else
		$command = ZM_PATH_BIN."/zmvideo.pl -e ".$event['Id']." -f ".$video_format." -r ".sprintf( "%.2f", ($rate/RATE_BASE) )." -S ".$video_size;
	if ( $overwrite )
		$command .= " -o";
	$generated = exec( $command, $output, $status );
}

$video_files = array();
if ( $dir = opendir( $event_path ) )
{
	while ( ($file = readdir( $dir )) !== false )
	{
		$file = $event_path.'/'.$file;
		if ( is_file( $file ) )
		{
			if ( preg_match( '/-S([\da-z]+)\.(?:'.join( '|', $video_formats ).')$/', $file, $matches ) )
			{
				if ( $matches[1] == $video_size )
				{
					$video_files[] = $file;
				}
			}
		}
	}
	closedir( $dir );
}

if ( isset($download) )
{
	header( "Content-disposition: attachment; filename=".preg_replace( "/^.*\//", "", $video_files[$download] )."; size=".filesize($video_files[$download]) );
	readfile( $video_files[$download] );
	exit;
}

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangVideo ?> - <?= $event['Name'] ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css"/>
</head>
<body>
<form method="post" action="<?= $PHP_SELF ?>">
<div style="visibility: hidden">
<fieldset>
<input type="hidden" name="view" value="<?= $view ?>"/>
<input type="hidden" name="eid" value="<?= $eid ?>"/>
<input type="hidden" name="generate" value="1"/>
</fieldset>
</div>
<table>
<tr><td style="width: 12em"><?= $zmSlangVideoFormat ?></td><td><?= buildSelect( "video_format", $video_formats ) ?></td></tr>
<tr><td><?= $zmSlangFrameRate ?></td><td><?= buildSelect( "rate", $rates ) ?></td></tr>
<tr><td><?= $zmSlangOverwriteExisting ?></td><td><input type="checkbox" class="noborder" name="overwrite" value="1"<?php if ( isset($overwrite) ) { ?> checked<?php } ?>/></td></tr>
</table>
<table>
<tr><td align="center"><input type="submit" value="<?= $zmSlangGenerateVideo ?>"/></td></tr>
</table>
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
<table>
<tr><td class="head" align="center"><br/><?= $zmSlangVideoGenFiles ?></td></tr>
</table>
<table align="center">
<?php
	if ( count($video_files) )
	{
?>
<tr>
  <td align="center" style="width: 5em"><?= $zmSlangFormat ?></td>
  <td align="center" style="width: 6em"><?= $zmSlangSize ?></td>
  <td align="center" style="width: 4em"><?= $zmSlangRate ?></td>
  <td align="center" style="width: 6em"><?= $zmSlangScale ?></td>
  <td align="center" style="width: 8em"><?= $zmSlangAction ?></td>
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
?>
<tr>
  <td align="center"><?= $matches[4] ?></td>
  <td align="center"><?= filesize( $file ) ?></td>
  <td align="center"><?= $rate_text ?></td>
  <td align="center"><?= $scale_text ?></td>
  <td align="center"><table><tr><td><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;eid=<?= $eid ?>&amp;download=<?= $index ?>"><?= $zmSlangView ?></a></td><td>/</td><td><a href="<?= $PHP_FILE ?>?view=<?= $view ?>&amp;eid=<?= $eid ?>&amp;delete=<?= $index ?>"><?= $zmSlangDelete ?></a></td></tr></table></td>
</tr>
<?php
				$index++;
			}
		}
	}
	else
	{
?>
<tr><td align="center"><?= $zmSlangVideoGenNoFiles ?></td></tr>
<?php
	}
?>
</table>
</form>
</body>
</html>
