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
$result = mysql_query( "select * from Monitors where Function != 'None' order by Id" );
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
	$frame_height = (ZM_WEB_MONTAGE_HEIGHT?ZM_WEB_MONTAGE_HEIGHT:$monitor['Height'])+16;
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
<html>
<head>
<title>ZM - <?= $zmSlangMontage ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
//window.resizeTo( <?= $jws['montage']['w']*$cols ?>, <?= $jws['montage']['h']*$rows ?> );
window.focus();
</script>
</head>
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
<frameset rows="*,16" cols="100%" border="1" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=montagefeed&mid=<?= $monitor['Id'] ?>" marginwidth="0" marginheight="0" name="MonitorStream" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=montagestatus&mid=<?= $monitor['Id'] ?>" marginwidth="0" marginheight="0" name="MonitorStatus" scrolling="no">
</frameset>
<?php
		}
	}
}
?>
</frameset>
