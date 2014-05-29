<?php
//
// ZoneMinder web frame view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
    $_REQUEST['view'] = "error";
    return;
}

$sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultScale FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE E.Id = ?';
$event = dbFetchOne( $sql, NULL, array( $_REQUEST['eid'] ) );

if ( !empty($_REQUEST['fid']) )
{
    $frame = dbFetchOne( 'SELECT * FROM Frames WHERE EventID = ? AND FrameId = ?', NULL, array( $_REQUEST['eid'], $_REQUEST['fid'] ) );
}
else
{
    $frame = dbFetchOne( 'SELECT * FROM Frames WHERE EventID = ? AND Score = ?', NULL, array( $_REQUEST['eid'], $event['MaxScore'] ) );
}

$maxFid = $event['Frames'];

$firstFid = 1;
$prevFid = $frame['FrameId']-1;
$nextFid = $frame['FrameId']+1;
$lastFid = $maxFid;

$scale = getDeviceScale( $event['Width'], $event['Height'], 1.1 );

$imageData = getImageSrc( $event, $frame, $scale, (isset($_REQUEST['show'])&&$_REQUEST['show']=="capt") );

xhtmlHeaders( __FILE__, $SLANG['Frame'].' - '.$_REQUEST['eid'].'-'.$frame['FrameId'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Frame'] ?> <?= $_REQUEST['eid']."-".$frame['FrameId']." (".$frame['Score'].")" ?></h2>
    </div>
    <div id="content">
      <?php if ( $imageData['hasAnalImage'] ) { ?><a href="?view=frame&amp;eid=<?= $_REQUEST['eid'] ?>&amp;fid=<?= $frame['FrameId'] ?>&amp;show=<?= $imageData['isAnalImage']?"capt":"anal" ?>"><?php } ?><img src="<?= viewImagePath( $imageData['thumbPath'] ) ?>" class="<?= $imageData['imageClass'] ?>"/><?php if ( $imageData['hasAnalImage'] ) { ?></a><?php } ?>
      <div id="contentButtons">
<?php if ( $frame['FrameId'] > 1 ) { ?>
        <a href="?view=frame&amp;eid=<?= $_REQUEST['eid'] ?>&amp;fid=<?= $firstFid ?>">&lt;&lt;</a>
<?php } if ( $frame['FrameId'] > 1 ) { ?>
        <a href="?view=frame&amp;eid=<?= $_REQUEST['eid'] ?>&amp;fid=<?= $prevFid ?>">&lt;</a>
<?php } if ( $frame['FrameId'] < $maxFid ) { ?>
        <a href="?view=frame&amp;eid=<?= $_REQUEST['eid'] ?>&amp;fid=<?= $nextFid ?>">&gt;</a>
<?php } if ( $frame['FrameId'] < $maxFid ) { ?>
        <a href="?view=frame&amp;eid=<?= $_REQUEST['eid'] ?>&amp;fid=<?= $lastFid ?>">&gt;&gt;</a>
<?php } ?>
      </div>
    </div>
  </div>
</body>
</html>
