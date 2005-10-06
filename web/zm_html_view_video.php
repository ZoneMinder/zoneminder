<?php
//
// ZoneMinder web video view file, $Date$, $Revision$
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
$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '$eid'$mid_sql";
$result = mysql_query( $sql );
if ( !$result )
    die( mysql_error() );
$event = mysql_fetch_assoc( $result );

if ( !isset( $rate ) )
	$rate = RATE_SCALE;
if ( !isset( $scale ) )
	$scale = SCALE_SCALE;

$event_dir = ZM_DIR_EVENTS."/".$event['MonitorId']."/".sprintf( "%d", $eid );

ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangVideo ?> - <?= $event['Name'] ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function closeWindow()
{
	window.close();
}
function viewVideo(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
function deleteVideo( index )
{
	location.replace( '<?= $PHP_FILE ?>?view=<?= $view ?>&eid=<?= $eid ?>&delete='+index );
}
</script>
</head>
<body>
<?php
if ( !empty($generate) )
{
?>
<table border="0" cellspacing="0" cellpadding="4" width="100%" height="70">
<tr>
<td align="center" valign="middle" class="head"><?= $zmSlangGeneratingVideo ?></td>
</tr>
</table>
<?php
	$buffer_string = "<!-- This is some long buffer text to ensure that IE flushes correctly -->";
	for ( $i = 0; $i < 4096/strlen($buffer_string); $i++ )
	{
		echo $buffer_string."\n";
	}
?>
</body>
</html>
<?php
	ob_flush();
	ob_start();

	if ( $video_file = createVideo( $event, $rate, $scale, $overwrite ) )
	{
		$video_path = $event_dir.'/'.$video_file;
	}
?>
<html>
<head>
<script type="text/javascript">
location.replace('<?= $PHP_FILE ?>?view=<?= $view ?>&eid=<?= $eid ?>&generated=<?= $video_file?1:0 ?>');
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
<tr><td width="50%">&nbsp;</td><td width="50%">&nbsp;</td></tr>
<tr><td class="text" align="right"><?= $zmSlangFrameRate ?></td><td><?= buildSelect( "rate", $rates ) ?></td></tr>
<tr><td class="text" align="right"><?= $zmSlangVideoSize ?></td><td><?= buildSelect( "scale", $scales ) ?></td></tr>
<tr><td class="text" align="right"><?= $zmSlangOverwriteExisting ?></td><td><input type="checkbox" class="form-noborder" name="overwrite" value="1"<?php if ( isset($overwrite) ) { ?> checked<?php } ?>></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" align="center"><input type="submit" class="form" value="<?= $zmSlangGenerateVideo ?>"></td></tr>
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
	$video_files = array();
	$video_types = array( "mpg", "mpeg", "asf", "mov", "3gp" );
	if ( $dir = opendir( $event_dir ) )
	{
		while ( ($file = readdir( $dir )) !== false )
		{
			$file = $event_dir.'/'.$file;
			if ( is_file( $file ) )
			{
				if ( preg_match( '/\.(?:'.join( '|', $video_types ).')$/', $file ) )
				{
					$video_files[] = $file;
				}
			}
		}
		closedir( $dir );
	}

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
		$index = 0;
		foreach ( $video_files as $file )
		{
			preg_match( '/^(.+)-([_\d]+)-([_\d]+)\.([^.]+)$/', $file, $matches );
			$rate = (int)(100 * preg_replace( '/_/', '.', $matches[2] ) );
			$scale = (int)(100 * preg_replace( '/_/', '.', $matches[3] ) );
			$rate_text = isset($rates[$rate])?$rates[$rate]:($rate."x");
			$scale_text = isset($scales[$scale])?$scales[$scale]:($scale."x");
?>
<tr>
  <td class="text" align="center"><?= $matches[4] ?></td>
  <td class="text" align="center"><?= filesize( $file ) ?></td>
  <td class="text" align="center"><?= $rate_text ?></td>
  <td class="text" align="center"><?= $scale_text ?></td>
  <td class="text" align="center"><a href="javascript:viewVideo( '<?= $file ?>', 'zmVideo<?= $eid ?>-<?= $scale ?>', 12+<?= reScale( $event['Width'], $scale ) ?>, 20+<?= reScale( $event['Height'], $scale ) ?> );"><?= $zmSlangView ?></a>&nbsp;/&nbsp;<a href="<?= $file ?>" target="_blank"><?= $zmSlangDownload ?></a>&nbsp;/&nbsp;<a href="javascript: deleteVideo( <?= $index ?> )"><?= $zmSlangDelete ?></a></td>
</tr>
<?php
			$index++;
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
