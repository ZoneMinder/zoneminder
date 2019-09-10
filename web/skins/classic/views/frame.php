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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

require_once('includes/Frame.php');
require_once('includes/Frame_Data.php');

$eid = validInt($_REQUEST['eid']);
if ( !empty($_REQUEST['fid']) )
  $fid = validInt($_REQUEST['fid']);

$Event = new ZM\Event($eid);
$Monitor = $Event->Monitor();

if ( !empty($fid) ) {
  $sql = 'SELECT * FROM Frames WHERE EventId = ? AND FrameId = ?';
  if ( !($frame = dbFetchOne( $sql, NULL, array($eid, $fid) )) )
    $frame = array( 'EventId'=>$eid, 'FrameId'=>$fid, 'Type'=>'Normal', 'Score'=>0 );
} else {
  $frame = dbFetchOne('SELECT * FROM Frames WHERE EventId = ? AND Score = ?', NULL, array($eid, $Event->MaxScore()));
}
$Frame = new ZM\Frame($frame);

$maxFid = $Event->Frames();

$firstFid = 1;
$prevFid = $fid-1;
$nextFid = $fid+1;
$lastFid = $maxFid;

if ( isset( $_REQUEST['scale'] ) ) {
  $scale = validNum($_REQUEST['scale']);
} else if ( isset( $_COOKIE['zmWatchScale'.$Monitor->Id()] ) ) {
  $scale = validNum($_COOKIE['zmWatchScale'.$Monitor->Id()]);
} else if ( isset( $_COOKIE['zmWatchScale'] ) ) {
  $scale = validNum($_COOKIE['zmWatchScale']);
} else {
  $scale = max( reScale( SCALE_BASE, $Monitor->DefaultScale(), ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
}
$scale = $scale ?: 'auto';

$imageData = $Event->getImageSrc( $frame, $scale, 0 );
if ( ! $imageData ) {
  ZM\Error("No data found for Event $eid frame $fid");
  $imageData = array();
}

$show = 'capt';
if (isset($_REQUEST['show']) && in_array($_REQUEST['show'], array('capt', 'anal'))) {
  $show = $_REQUEST['show'];
} else if ( $imageData['hasAnalImage'] ) {
  $show = 'anal';
}

$imagePath = $imageData['thumbPath'];
$eventPath = $imageData['eventPath'];
$dImagePath = sprintf('%s/%0'.ZM_EVENT_IMAGE_DIGITS.'d-diag-d.jpg', $eventPath, $Frame->FrameId());
$rImagePath = sprintf('%s/%0'.ZM_EVENT_IMAGE_DIGITS.'d-diag-r.jpg', $eventPath, $Frame->FrameId());

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Frame').' - '.$Event->Id().' - '.$Frame->FrameId());
?>
<body>
  <div id="page">
    <div id="header">
    <form>
      <div id="headerButtons">
<?php
  if ( canEdit('Events') ) {
    echo '<button type="button" id="delete_button">'.translate('Delete').'</button>';
  }
?>
        <button type="button" data-on-click="closeWindow"><?php echo translate('Close') ?></button>
      </div>
      <div id="scaleControl"><label for="scale"><?php echo translate('Scale') ?></label><?php echo buildSelect('scale', $scales); ?></div>
      <h2><?php echo translate('Frame') ?> <?php echo $Event->Id().'-'.$Frame->FrameId().' ('.$Frame->Score().')' ?></h2>
      <input type="hidden" name="base_width" id="base_width" value="<?php echo $Event->Width(); ?>"/>
      <input type="hidden" name="base_height" id="base_height" value="<?php echo $Event->Height(); ?>"/>
    </form>
    </div>
    <div id="content">
      <p id="image">

<?php if ( $imageData['hasAnalImage'] ) {
 echo sprintf('<a href="?view=frame&amp;eid=%d&amp;fid=%d&scale=%d&amp;show=%s">', $Event->Id(), $Frame->FrameId(), $scale, ( $show=='anal'?'capt':'anal' ) );
} ?>
<img id="frameImg" src="<?php echo validHtmlStr($Frame->getImageSrc($show=='anal'?'analyse':'capture')) ?>" width="<?php echo reScale($Event->Width(), $Monitor->DefaultScale(), $scale) ?>" height="<?php echo reScale($Event->Height(), $Monitor->DefaultScale(), $scale) ?>" alt="<?php echo $Frame->EventId().'-'.$Frame->FrameId() ?>" class="<?php echo $imageData['imageClass'] ?>"/>
<?php if ( $imageData['hasAnalImage'] ) { ?></a><?php } ?>

      </p>
<?php
  $frame_url_base = '?view=frame&amp;eid='.$Event->Id().'&amp;scale='.$scale.'&amp;show='.$show.'&fid=';
?>
      <p id="controls">
        <a id="firstLink" <?php echo (( $Frame->FrameId() > 1 ) ? 'href="'.$frame_url_base.$firstFid.'" class="btn-primary"' : 'class="btn-primary disabled"') ?>><?php echo translate('First') ?></a>
        <a id="prevLink" <?php echo ( $Frame->FrameId() > 1 ) ? 'href="'.$frame_url_base.$prevFid.'" class="btn-primary"' : 'class="btn-primary disabled"' ?>><?php echo translate('Prev') ?></a>
<?php
if ( ZM_PLATERECOGNIZER_ENABLE ) {
?>
  <a id="PlateRecognizer" href="<?php echo $frame_url_base.$fid.'&action=do_alpr'?>" class="btn-primary">Do Plate Detection</a>
<?php
} # end if ZM_PLATERECOGNIZER_ENABLE
?>
        <a id="nextLink" <?php echo ( $Frame->FrameId() < $maxFid ) ? 'href="'.$frame_url_base.$nextFid.'" class="btn-primary"' : 'class="btn-primary disabled"' ?>><?php echo translate('Next') ?></a>
        <a id="lastLink" <?php echo ( $Frame->FrameId() < $maxFid ) ? 'href="'.$frame_url_base.$lastFid .'" class="btn-primary"' : 'class="btn-primary disabled"' ?>><?php echo translate('Last') ?></a>
      </p>
<?php if (file_exists ($dImagePath)) { ?>
      <p id="diagImagePath"><?php echo $dImagePath ?></p>
      <p id="diagImage"><img src="<?php echo viewImagePath( $dImagePath ) ?>" width="<?php echo reScale( $Event->Width(), $Monitor->DefaultScale(), $scale ) ?>" height="<?php echo reScale( $Event->Height(), $Monitor->DefaultScale(), $scale ) ?>" class="<?php echo $imageData['imageClass'] ?>"/></p>
<?php } if (file_exists ($rImagePath)) { ?>
      <p id="refImagePath"><?php echo $rImagePath ?></p>
      <p id="refImage"><img src="<?php echo viewImagePath( $rImagePath ) ?>" width="<?php echo reScale( $Event->Width(), $Monitor->DefaultScale(), $scale ) ?>" height="<?php echo reScale( $Event->Height(), $Monitor->DefaultScale(), $scale ) ?>" class="<?php echo $imageData['imageClass'] ?>"/></p>
<?php } ?>
<div id="AnalysisData">
  <fieldset><legend><?php echo translate('AnalysisData') ?></legend>
      <?php
$all_data = $Frame->Data();
foreach ( $all_data as $data ) {
  $Data = new ZM\Frame_Data($data);
  echo $Data->to_string().'<br/>';
}
      ?>
  </fieldset>
</div>
<div id="Statistics">
  <fieldset><legend><?php echo translate('Statistics') ?></legend>
<?php
$eid = validInt($_REQUEST['eid']);
$fid = validInt($_REQUEST['fid']);

$sql = 'SELECT S.*,E.*,Z.Name AS ZoneName,Z.Units,Z.Area,M.Name AS MonitorName FROM Stats AS S LEFT JOIN Events AS E ON S.EventId = E.Id LEFT JOIN Zones AS Z ON S.ZoneId = Z.Id LEFT JOIN Monitors AS M ON E.MonitorId = M.Id WHERE S.EventId = ? AND S.FrameId = ? ORDER BY S.ZoneId';
$stats = dbFetchAll( $sql, NULL, array( $eid, $fid ) );
?>
       <table>
          <thead>
            <tr>
              <th class="colZone"><?php echo translate('Zone') ?></th>
              <th class="colPixelDiff"><?php echo translate('PixelDiff') ?></th>
              <th class="colAlarmPx"><?php echo translate('AlarmPx') ?></th>
              <th class="colFilterPx"><?php echo translate('FilterPx') ?></th>
              <th class="colBlobPx"><?php echo translate('BlobPx') ?></th>
              <th class="colBlobs"><?php echo translate('Blobs') ?></th>
              <th class="colBlobSizes"><?php echo translate('BlobSizes') ?></th>
              <th class="colAlarmLimits"><?php echo translate('AlarmLimits') ?></th>
              <th class="colScore"><?php echo translate('Score') ?></th>
            </tr>
          </thead>
          <tbody>
<?php
if ( count($stats) ) {
    foreach ( $stats as $stat ) {
?>
            <tr>
              <td class="colZone"><?php echo validHtmlStr($stat['ZoneName']) ?></td>
              <td class="colPixelDiff"><?php echo validHtmlStr($stat['PixelDiff']) ?></td>
              <td class="colAlarmPx"><?php echo sprintf('%d (%d%%)', $stat['AlarmPixels'], (100*$stat['AlarmPixels']/$stat['Area']) ) ?></td>
              <td class="colFilterPx"><?php echo sprintf('%d (%d%%)', $stat['FilterPixels'], (100*$stat['FilterPixels']/$stat['Area']) ) ?></td>
              <td class="colBlobPx"><?php echo sprintf('%d (%d%%)', $stat['BlobPixels'], (100*$stat['BlobPixels']/$stat['Area']) ) ?></td>
              <td class="colBlobs"><?php echo validHtmlStr($stat['Blobs']) ?></td>
<?php
if ( $stat['Blobs'] > 1 ) {
?>
              <td class="colBlobSizes"><?php echo sprintf('%d-%d (%d%%-%d%%)', $stat['MinBlobSize'], $stat['MaxBlobSize'], (100*$stat['MinBlobSize']/$stat['Area']), (100*$stat['MaxBlobSize']/$stat['Area']) ) ?></td>
<?php
} else {
?>
              <td class="colBlobSizes"><?php echo sprintf('%d (%d%%)', $stat['MinBlobSize'], 100*$stat['MinBlobSize']/$stat['Area'] ) ?></td>
<?php
}
?>
              <td class="colAlarmLimits"><?php echo validHtmlStr($stat['MinX'].','.$stat['MinY'].'-'.$stat['MaxX'].','.$stat['MaxY']) ?></td>
              <td class="colScore"><?php echo $stat['Score'] ?></td>
            </tr>
<?php
    } // end foreach stat
} else {
?>
            <tr>
              <td class="rowNoStats" colspan="9"><?php echo translate('NoStatisticsRecorded') ?></td>
            </tr>
<?php
}
?>
            </tbody>
          </table>
        </fieldset>
      </div><!--Statistics-->
    </div>
  </div>
</body>
</html>
