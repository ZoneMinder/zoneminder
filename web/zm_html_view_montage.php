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

if ( !canView( 'Stream' ) )
{
	$view = "error";
	return;
}

if ( $group )
{
	$sql = "select * from Groups where Id = '$group'";
	$result = mysql_query( $sql );
	if ( !$result )
		die( mysql_error() );
	$row = mysql_fetch_assoc( $result );
	$group_sql = "and find_in_set( Id, '".$row['MonitorIds']."' )";
}

$sql = "select * from Monitors where Function != 'None' $group_sql order by Id";
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
$monitors = array();
while( $row = mysql_fetch_assoc( $result ) )
{
	if ( !visibleMonitor( $row['Id'] ) )
	{
		continue;
	}
	$monitors[] = $row;
}
$rows = intval(ceil(count($monitors)/ZM_WEB_MONTAGE_MAX_COLS));
$cols = count($monitors)>=ZM_WEB_MONTAGE_MAX_COLS?ZM_WEB_MONTAGE_MAX_COLS:count($monitors);
$widths = array();
$heights = array();
for ( $i = 0; $i < count($monitors); $i++ )
{
	$monitor = $monitors[$i];
	$frame_height = (ZM_WEB_MONTAGE_HEIGHT?ZM_WEB_MONTAGE_HEIGHT:$monitor['Height'])+(ZM_WEB_COMPACT_MONTAGE?0:16);
	$frame_width = (ZM_WEB_MONTAGE_WIDTH?ZM_WEB_MONTAGE_WIDTH:$monitor['Width']);
	$row = $i/ZM_WEB_MONTAGE_MAX_COLS;
	$col = $i%ZM_WEB_MONTAGE_MAX_COLS;
	if ( empty( $heights[$row] ) || $frame_height > $heights[$row] )
		$heights[$row] = $frame_height;
	if ( empty( $widths[$col] ) || $frame_width > $widths[$col] )
		$widths[$col] = $frame_width;
}
$row_spec = join( ',', $heights );
$col_spec = join( ',', $widths );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>ZM - <?= $zmSlangMontage ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
window.focus();
</script>
</head>
<?php
if ( ZM_WEB_COMPACT_MONTAGE )
{
?>
<frameset rows="16,*" cols="*" border="1" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=montagemenu&mode=<?= $mode ?>" marginwidth="0" marginheight="0" name="MontageMenu" scrolling="no">
<?php
}
?>
<frameset rows="<?= $row_spec ?>" cols="<?= $col_spec ?>" border="1" frameborder="no" framespacing="0">
<?php
for ( $row = 0; $row < $rows; $row++ )
{
	for ( $col = 0; $col < $cols; $col++ )
	{
		$i = ($row*$cols)+$col;
		if ( $i < count($monitors) )
		{
			$monitor = $monitors[$i];
?>
<frame src="<?= $PHP_SELF ?>?view=montageframe&mid=<?= $monitor['Id'] ?>&mode=<?= $mode ?>" marginwidth="0" marginheight="0" name="MontageFrame<?= $monitor['Id'] ?>" scrolling="no">
<?php
		}
	}
}
?>
<?php
if ( ZM_WEB_COMPACT_MONTAGE )
{
?>
</frameset>
<?php
}
?>
</frameset>
</html>
