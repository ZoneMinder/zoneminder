<?php
//
// ZoneMinder web montage view file, $Date$, $Revision$
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

$sql = "select * from Groups where Name = 'Mobile'";
$result = mysql_query( $sql );
if ( !$result )
    echo mysql_error();
$group = mysql_fetch_assoc( $result );
mysql_free_result( $result );

$result = mysql_query( "select * from Monitors where Function != 'None' order by Sequence" );
$monitors = array();
$max_width = 0;
$max_height = 0;
while( $row = mysql_fetch_assoc( $result ) )
{
	if ( !visibleMonitor( $row['Id'] ) )
	{
		continue;
	}
    if ( $group && $group['MonitorIds'] && !in_array( $row['Id'], split( ',', $group['MonitorIds'] ) ) )
	{
		continue;
	}

	if ( $max_width < $row['Width'] ) $max_width = $row['Width'];
	if ( $max_height < $row['Height'] ) $max_height = $row['Height'];
	$monitors[] = $row;
}
mysql_free_result( $result );

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangMontage ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css"/>
</head>
<body>
<table style="width: 100%">
<?php

$device_width = (isset($device)&&!empty($device['width']))?$device['width']:DEVICE_WIDTH;
$device_height = (isset($device)&&!empty($device['height']))?$device['height']:DEVICE_HEIGHT;

// Allow for margins etc
$device_width -= 16;
$device_height -= 16;

$images_per_line = 2;

$count = 0;
foreach( $monitors as $monitor )
{
	$width_scale = ($device_width*SCALE_BASE)/$monitor['Width'];
	$height_scale = ($device_height*SCALE_BASE)/$monitor['Height'];
	$scale = (int)(($width_scale<$height_scale)?$width_scale:$height_scale);
	$scale /= $images_per_line; // Try and get several pics per line

	if ( $count%$images_per_line == 0 )
	{
?>
<tr>
<?php
	}

	$image_path = getStreamSrc( array( "mode=single", "monitor=".$monitor['Id'], "scale=".$scale ) );
	//$alarm_frame = $alarm_frames[$frame_id];
	//$img_class = $alarm_frame?"alarm":"normal";
?>
<td align="center"><a href="<?= $PHP_SELF ?>?view=watch&amp;mid=<?= $monitor['Id'] ?>"><img src="<?= $image_path ?>" alt="<?= $monitor['Name'] ?>" style="border: 0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>"></a></td>
<?php

	if ( $count%$images_per_line == ($images_per_line-1) )
	{
?>
</tr>
<?php
	}
	$count++;
}
if ( $count%$images_per_line != 0 )
{
	while ( $count%$images_per_line != ($images_per_line-1) )
	{
?>
<td>&nbsp;</td>
<?php
	}
?>
</tr>
<?php
}
?>
<tr>
<td colspan="<?= $images_per_line ?>" align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>"><?= $zmSlangRefresh ?></a></td>
</tr>
</table>
</body>
</html>
