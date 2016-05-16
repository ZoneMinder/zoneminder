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
    $view = "error";
    return;
}

require_once('includes/Frame.php');

$eid = validInt($_REQUEST['eid']);
if ( !empty($_REQUEST['fid']) )
    $fid = validInt($_REQUEST['fid']);

$sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultScale FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE E.Id = ?';
$event = dbFetchOne( $sql, NULL, array($eid) );

if ( !empty($fid) ) {
    $sql = 'SELECT * FROM Frames WHERE EventId = ? AND FrameId = ?';
    if ( !($frame = dbFetchOne( $sql, NULL, array($eid, $fid) )) )
        $frame = array( 'FrameId'=>$fid, 'Type'=>'Normal', 'Score'=>0 );
} else {
    $frame = dbFetchOne( 'SELECT * FROM Frames WHERE EventId = ? AND Score = ?', NULL, array( $eid, $event['MaxScore'] ) );
}
$frame = new Frame( $frame );

$maxFid = $event['Frames'];

$firstFid = 1;
$prevFid = $frame->FrameId()-1;
$nextFid = $frame->FrameId()+1;
$lastFid = $maxFid;

$alarmFrame = $frame->Type()=='Alarm';

if ( isset( $_REQUEST['scale'] ) )
    $scale = validInt($_REQUEST['scale']);
else
    $scale = max( reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );

$imageData = getImageSrc( $event, $frame->FrameId(), $scale, (isset($_REQUEST['show']) && $_REQUEST['show']=="capt") );

$imagePath = $imageData['thumbPath'];
$eventPath = $imageData['eventPath'];
$dImagePath = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-diag-d.jpg", $eventPath, $frame->FrameId() );
$rImagePath = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-diag-r.jpg", $eventPath, $frame->FrameId() );

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Frame')." - ".$event['Id']." - ".$frame->FrameId() );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <?php if ( ZM_RECORD_EVENT_STATS && $alarmFrame ) { echo makePopupLink( '?view=stats&amp;eid='.$event['Id'].'&amp;fid='.$frame->FrameId(), 'zmStats', 'stats', translate('Stats') ); } ?>
        <?php if ( canEdit( 'Events' ) ) { ?><a href="?view=none&amp;action=delete&amp;markEid=<?php echo $event['Id'] ?>"><?php echo translate('Delete') ?></a><?php } ?>
        <a href="#" onclick="closeWindow(); return( false );"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Frame') ?> <?php echo $event['Id']."-".$frame->FrameId()." (".$frame->Score().")" ?></h2>
    </div>
    <div id="content">
      <p id="image">
<?php if ( $imageData['hasAnalImage'] ) { ?><a href="?view=frame&amp;eid=<?php echo $event['Id'] ?>&amp;fid=<?php echo $frame->FrameId() ?>&amp;scale=<?php echo $scale ?>&amp;show=<?php echo $imageData['isAnalImage']?"capt":"anal" ?>"><?php } ?>
<img src="<?php echo $frame->getImageSrc() ?>" width="<?php echo reScale( $event['Width'], $event['DefaultScale'], $scale ) ?>" height="<?php echo reScale( $event['Height'], $event['DefaultScale'], $scale ) ?>" alt="<?php echo $frame->EventId()."-".$frame->FrameId() ?>" class="<?php echo $imageData['imageClass'] ?>"/>
<?php if ( $imageData['hasAnalImage'] ) { ?></a><?php } ?>

      <p id="controls">
<?php if ( $frame->FrameId() > 1 ) { ?>
        <a id="firstLink" href="?view=frame&amp;eid=<?php echo $event['Id'] ?>&amp;fid=<?php echo $firstFid ?>&amp;scale=<?php echo $scale ?>"><?php echo translate('First') ?></a>
<?php } if ( $frame->FrameId() > 1 ) { ?>
        <a id="prevLink" href="?view=frame&amp;eid=<?php echo $event['Id'] ?>&amp;fid=<?php echo $prevFid ?>&amp;scale=<?php echo $scale ?>"><?php echo translate('Prev') ?></a>
<?php } if ( $frame->FrameId() < $maxFid ) { ?>
        <a id="nextLink" href="?view=frame&amp;eid=<?php echo $event['Id'] ?>&amp;fid=<?php echo $nextFid ?>&amp;scale=<?php echo $scale ?>"><?php echo translate('Next') ?></a>
<?php } if ( $frame->FrameId() < $maxFid ) { ?>
        <a id="lastLink" href="?view=frame&amp;eid=<?php echo $event['Id'] ?>&amp;fid=<?php echo $lastFid ?>&amp;scale=<?php echo $scale ?>"><?php echo translate('Last') ?></a>
<?php } ?>
      </p>
<?php if (file_exists ($dImagePath)) { ?>
      <p id="diagImagePath"><?php echo $dImagePath ?></p>
      <p id="diagImage"><img src=?"<?php echo viewImagePath( $dImagePath ) ?>" width="<?php echo reScale( $event['Width'], $event['DefaultScale'], $scale ) ?>" height="<?php echo reScale( $event['Height'], $event['DefaultScale'], $scale ) ?>" class="<?php echo $imageData['imageClass'] ?>"/></p>
<?php } if (file_exists ($rImagePath)) { ?>
      <p id="refImagePath"><?php echo $rImagePath ?></p>
      <p id="refImage"><img src="<?php echo viewImagePath( $rImagePath ) ?>" width="<?php echo reScale( $event['Width'], $event['DefaultScale'], $scale ) ?>" height="<?php echo reScale( $event['Height'], $event['DefaultScale'], $scale ) ?>" class="<?php echo $imageData['imageClass'] ?>"/></p>
<?php } ?>
    </div>
  </div>
</body>
</html>
