<?php
//
// ZoneMinder web montage view file, $Date$, $Revision$
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

$sql = "select * from Groups where Name = 'Mobile'";
$result = mysql_query( $sql );
if ( !$result )
    echo mysql_error();
$group = mysql_fetch_assoc( $result );

$result = mysql_query( "select * from Monitors where Function != 'None' order by Id" );
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

?>
<html>
<head>
<title>ZM - <?= $zmSlangMontage ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css">
</head>
<body>
<p align="center">
<?php
$device_width = (isset($device)&&!empty($device['width']))?$device['width']:DEVICE_WIDTH;
$device_height = (isset($device)&&!empty($device['height']))?$device['height']:DEVICE_HEIGHT;
// Allow for margins etc
$device_width -= 16;
$device_height -= 16;

foreach( $monitors as $monitor )
{
	$width_scale = ($device_width*SCALE_SCALE)/$monitor['Width'];
	$height_scale = ($device_height*SCALE_SCALE)/$monitor['Height'];
	$scale = (int)(($width_scale<$height_scale)?$width_scale:$height_scale);
	$scale /= 2; // Try and get two pics per line

	$image_src = getStreamSrc( array( "mode=single", "monitor=".$monitor['Id'], "scale=".$scale ) );
?>
<a href="<?= $PHP_SELF ?>?view=watch&amp;mid=<?= $monitor['Id'] ?>"><img src="<?= $image_src ?>" alt="<?= $monitor['Name'] ?>" style="border: 0" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>"></a>
<?php
}
?>
</p>
</body>
</html>
