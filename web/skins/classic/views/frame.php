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

require_once('includes/Frame.php');

$eid = validInt($_REQUEST['eid']);
$fid = empty($_REQUEST['fid']) ? 0 : validInt($_REQUEST['fid']);

$Event = new ZM\Event($eid);
if (!$Event->canView()) {
  $view = 'error';
  return;
}
$Monitor = $Event->Monitor();

// The object detection images are not frames; show them through the image view.
if (isset($_REQUEST['fid']) && in_array($_REQUEST['fid'], array('objdetect', 'objdetect_gif'), true)) {
  header('Location: ?view=image&eid='.$Event->Id().'&fid='.$_REQUEST['fid']);
  return;
}

# This is kinda weird.. so if we pass fid=0 or some other non-integer, then it loads max score
# perhaps we should consider being explicit, like fid = maxscore
if (!empty($fid)) {
  $sql = 'SELECT * FROM Frames WHERE EventId=? AND FrameId=?';
  if (!($frame = dbFetchOne($sql, NULL, array($eid, $fid)))) {
    $frame = array('EventId'=>$eid, 'FrameId'=>$fid, 'Type'=>'Normal', 'Score'=>0);
    $virtualFrame = $Event->find_virtual_frame($fid);
    if ($virtualFrame) $frame['Delta'] = $virtualFrame->Delta();
  }
} else {
  $frame = dbFetchOne('SELECT * FROM Frames WHERE EventId=? AND Score=?', NULL, array($eid, $Event->MaxScore()));
  if (!$frame) {
    $frame = array('EventId'=>$eid, 'FrameId'=>1, 'Type'=>'Normal', 'Score'=>0, 'Delta'=>0);
  }
}
$Frame = new ZM\Frame($frame);
if (!$fid) $fid = (int)$Frame->FrameId(); // fid=0 loaded the max score frame, navigate and fetch stats from its real id

// Links only to frames that exist, so a sparse or short Frames table leaves a link disabled rather than empty.
function frameNavigation($eid, $current) {
  $nav = array(
    'prev' => dbFetchOne('SELECT MAX(FrameId) AS FrameId FROM Frames WHERE EventId=? AND FrameId < ?', 'FrameId', array($eid, $current)),
    'next' => dbFetchOne('SELECT MIN(FrameId) AS FrameId FROM Frames WHERE EventId=? AND FrameId > ?', 'FrameId', array($eid, $current)),
  );
  $nav['first'] = $nav['prev'] ? dbFetchOne('SELECT MIN(FrameId) AS FrameId FROM Frames WHERE EventId=?', 'FrameId', array($eid)) : null;
  $nav['last'] = $nav['next'] ? dbFetchOne('SELECT MAX(FrameId) AS FrameId FROM Frames WHERE EventId=?', 'FrameId', array($eid)) : null;
  return $nav;
}
$nav = frameNavigation($eid, (int)$Frame->FrameId());

$alarmFrame = ( $Frame->Type() == 'Alarm' ) ? 1 : 0;

if (isset($_REQUEST['scale'])) {
  $scaleSelected = $_REQUEST['scale'];
} else if (isset($_COOKIE['zmWatchScale'.$Monitor->Id()])) {
  $scaleSelected = $_COOKIE['zmWatchScale'.$Monitor->Id()];
} else if (isset($_COOKIE['zmWatchScale'])) {
  $scaleSelected = $_COOKIE['zmWatchScale'];
} else {
  $scaleSelected = $Monitor->DefaultScale();
}
// $scales also offers fit_to_width and NNNpx, so keep those keys for the select and the links.
if (!is_scalar($scaleSelected) || !array_key_exists((string)$scaleSelected, $scales)) {
  $scaleSelected = validNum($scaleSelected);
}
$scaleSelected = (string)$scaleSelected;
$scale = $scaleSelected;
if ($scaleSelected === 'fit_to_width') {
  $scale = 0; // frame.js fits the image to the page width
} else if (preg_match('/^(\d+)px$/', $scaleSelected, $m)) {
  // Max width: the percentage that gives that width, never above actual size.
  $scale = $Event->Width() ? min(SCALE_BASE, (int)floor($m[1]*SCALE_BASE/$Event->Width())) : 0;
}
$scale = $scale ? $scale : 0;

$imageData = $Event->getImageSrc($frame, $scale, 0);
if (!$imageData) {
  ZM\Error("No data found for Event $eid frame $fid");
  $imageData = array('hasAnalysisImage'=>0, 'thumbPath' => '', 'eventPath'=>'', 'imageClass'=>'normal');
}

$show = 'capture';
if (isset($_REQUEST['show']) && in_array($_REQUEST['show'], array('capture', 'analysis'))) {
  $show = $_REQUEST['show'];
  if ($show == 'analysis' and ! $imageData['hasAnalysisImage']) {
    $show = 'capture';
  }
} else if ($imageData['hasAnalysisImage']) {
  $show = 'analysis';
}

$imagePath = $imageData['thumbPath'];
$eventPath = $imageData['eventPath'];
$dImagePath = sprintf('%s/%0'.ZM_EVENT_IMAGE_DIGITS.'d-diag-d.jpg', $eventPath, $Frame->FrameId());
$rImagePath = sprintf('%s/%0'.ZM_EVENT_IMAGE_DIGITS.'d-diag-r.jpg', $eventPath, $Frame->FrameId());

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Frame').' - '.$Event->Id().' - '.$Frame->FrameId());
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page p-0">
    <div class="d-flex flex-row justify-content-between px-3 pt-1">
      <div id="toolbar" >
        <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button type="button" id="framesBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Frames') ?>" ><i class="fa fa-picture-o"></i></button>
        <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
        <button type="button" id="statsBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Stats') ?>" ><i class="fa fa-info"></i></button>
        <button type="button" id="statsViewBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Stats').' '.translate('View') ?>" ><i class="fa fa-table"></i></button>
      </div>
      <h2><?php echo translate('Frame').' <span title="'.translate('Event Id').'">'.$Event->Id().'</span>-<span title="'.translate('Frame Id').'">'.$Frame->FrameId().'</span> (<span title="'.translate('Score').'">'.$Frame->Score().'</span>)' ?></h2>
      <form>
        <div id="scaleControl">
          <label for="scale"><?php echo translate('Scale') ?></label>
          <?php echo htmlSelect('scale', $scales, $scaleSelected, array('data-on-change'=>'changeScale','id'=>'scale')); ?>
        </div>
        <input type="hidden" name="base_width" id="base_width" value="<?php echo $Event->Width(); ?>"/>
        <input type="hidden" name="base_height" id="base_height" value="<?php echo $Event->Height(); ?>"/>
      </form>
    </div>
    
    <div id="content" class="d-flex flex-row justify-content-center">
      <table id="frameStatsTable" class="table-sm table-borderless pr-3">
      <!-- FRAME STATISTICS POPULATED BY AJAX -->
      </table>
      <div>
        <p id="image">
<?php
if ($imageData['hasAnalysisImage']) {
  echo sprintf('<a href="?view=frame&amp;eid=%d&amp;fid=%d&scale=%s&amp;show=%s" title="Click to display frame %s analysis">',
    $Event->Id(), $Frame->FrameId(), urlencode($scaleSelected),
    ($show=='analysis'?'capture':'analysis'),
    ($show=='analysis'?'without':'with')
  );
}
?>
<img id="frameImg"
  src="<?php echo validHtmlStr($Frame->getImageSrc($show=='analysis'?'analyse':'capture')) ?>"
  width="<?php echo reScale($Event->Width(), $scale) ?>"
  height="<?php echo reScale($Event->Height(), $scale) ?>"
  alt="<?php echo $Frame->EventId().'-'.$Frame->FrameId() ?>"
  class="<?php echo $imageData['imageClass'] ?>"
  loading="lazy"
/>
<?php
if ($imageData['hasAnalysisImage']) { ?></a><?php } ?>
        </p>
<?php
  $frame_url_base = '?view=frame&amp;eid='.$Event->Id().'&amp;scale='.urlencode($scaleSelected).'&amp;show='.$show.'&amp;fid=';
?>
        <p id="controls">
          <a id="firstLink" <?php echo $nav['first'] ? 'href="'.$frame_url_base.$nav['first'].'" class="btn-primary"' : 'class="btn-primary disabled"' ?>><?php echo translate('First') ?></a>
          <a id="prevLink" <?php echo $nav['prev'] ? 'href="'.$frame_url_base.$nav['prev'].'" class="btn-primary"' : 'class="btn-primary disabled"' ?>><?php echo translate('Prev') ?></a>
          <a id="nextLink" <?php echo $nav['next'] ? 'href="'.$frame_url_base.$nav['next'].'" class="btn-primary"' : 'class="btn-primary disabled"' ?>><?php echo translate('Next') ?></a>
          <a id="lastLink" <?php echo $nav['last'] ? 'href="'.$frame_url_base.$nav['last'].'" class="btn-primary"' : 'class="btn-primary disabled"' ?>><?php echo translate('Last') ?></a>
        </p>
<?php
if (file_exists($dImagePath)) {
?>
        <p id="diagImagePath"><?php echo $dImagePath ?></p>
        <p id="diagImage">
          <img
            src="<?php echo viewImagePath($dImagePath) ?>"
            width="<?php echo reScale($Event->Width(), $scale) ?>"
            height="<?php echo reScale($Event->Height(), $scale) ?>"
            class="<?php echo $imageData['imageClass'] ?>"
          />
        </p>
<?php
}
if (file_exists($rImagePath)) {
?>
        <p id="refImagePath"><?php echo $rImagePath ?></p>
        <p id="refImage">
          <img
            src="<?php echo viewImagePath($rImagePath) ?>"
            width="<?php echo reScale($Event->Width(), $scale) ?>"
            height="<?php echo reScale($Event->Height(), $scale) ?>"
            class="<?php echo $imageData['imageClass'] ?>"
          />
        </p>
      </div>
<?php } ?>
    </div>
  </div>
<?php xhtmlFooter() ?>
