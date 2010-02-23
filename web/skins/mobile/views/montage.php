<?php
//
// ZoneMinder web montage view file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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
    $_REQUEST['view'] = "error";
    return;
}

$imagesPerLine = 2;

$sql = "select * from Groups where Name = 'Mobile'";
$group = dbFetchOne( $sql );

$sql = "select * from Monitors where Function != 'None' order by Sequence";
$monitors = array();
$maxWidth = 0;
$maxHeight = 0;
foreach( dbFetchAll( $sql ) as $row )
{
    if ( !visibleMonitor( $row['Id'] ) )
        continue;

    if ( $group && $group['MonitorIds'] && !in_array( $row['Id'], explode( ',', $group['MonitorIds'] ) ) )
        continue;

    if ( $maxWidth < $row['Width'] ) $maxWidth = $row['Width'];
    if ( $maxHeight < $row['Height'] ) $maxHeight = $row['Height'];
    $monitors[] = $row;
}

xhtmlHeaders( __FILE__, $SLANG['Montage'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="?view=<?= $_REQUEST['view'] ?>"><?= $SLANG['Refresh'] ?></a>
      </div>
    </div>
    <div id="content">
      <div id="monitorImages">
<?php
foreach( $monitors as $monitor )
{
    $scale = getDeviceScale( $monitor['Width'], $monitor['Height'], $imagesPerLine*1.1 );
    $imagePath = getStreamSrc( array( "mode=single", "monitor=".$monitor['Id'], "scale=".$scale ), '&amp;' );
?>
        <a href="?view=watch&amp;mid=<?= $monitor['Id'] ?>"><img src="<?= viewImagePath( $imagePath ) ?>" alt="<?= $monitor['Name'] ?>"/></a>
<?php
}
?>
      </div>
      <div id="contentButtons"><a href="?view=console"><?= $SLANG['Console'] ?></a></div>
    </div>
  </div>
</body>
</html>
