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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if (!canView('Stream')) {
  $view = 'error';
  return;
}

require_once('includes/MontageLayout.php');
require_once('includes/Zone.php');

$Montage = new Skin\Montage();
$options = $Montage::$options;
$filterbar = $Montage::$resultMonitorFilters['filterBar'];
$fieldsTable = $Montage::buildGlobalFilters ($liveMode = false);

$monitorStatusPosition = array( 
  'insideImgBottom'  => translate('Inside bottom'),
  'outsideImgBottom' => translate('Outside bottom'),
  'hidden' => translate('Hidden'),
  'showOnHover' => translate('Show on hover'),
);

$monitorStatusPositionSelected = 'outsideImgBottom';
if (isset($_REQUEST['monitorStatusPositionSelected'])) {
  $monitorStatusPositionSelected = $_REQUEST['monitorStatusPositionSelected'];
} else if (isset($_COOKIE['zmMonitorStatusPositionSelected'])) {
  $monitorStatusPositionSelected = $_COOKIE['zmMonitorStatusPositionSelected'];
}

$scale = '';   # auto
if (isset($_REQUEST['scale'])) {
  $scale = $_REQUEST['scale'];
} else if (isset($_COOKIE['zmMontageScale'])) {
  $scale = $_COOKIE['zmMontageScale'];
}
if ($scale != 'fixed' and $scale != 'auto') {
  $scale = validNum($scale);
/* So far so, otherwise when opening with scalex2, etc. The image is larger than the screen and everything slows down...
scaleControl is no longer used!
  $options['scale'] = $scale;
*/
}

$Montage::setScale ($scale);

$streamQualitySelected = '0';
if (isset($_REQUEST['streamQuality'])) {
  $streamQualitySelected = $_REQUEST['streamQuality'];
} else if (isset($_COOKIE['zmStreamQuality'])) {
  $streamQualitySelected = $_COOKIE['zmStreamQuality'];
} else if (isset($_SESSION['zmStreamQuality']) ) {
  $streamQualitySelected = $_SESSION['zmStreamQuality'];
}
/*
$archived = '';   # All
if (isset($_REQUEST['Archived'])) {
  $archived = $_REQUEST['Archived'];
} else if (isset($_COOKIE['zmFilterArchived'])) {
  $archived = $_COOKIE['zmFilterArchived'];
}
*/

$speeds = [0, 0.1, 0.25, 0.5, 0.75, 1.0, 1.5, 2, 3, 5, 10, 20, 50];

if (isset($_REQUEST['speed'])) {
  $defaultSpeed = validNum($_REQUEST['speed']);
} else if (isset($_COOKIE['speed'])) {
  $defaultSpeed = validNum($_COOKIE['speed']);
} else {
  $defaultSpeed = 1.0;
}

$speedIndex = 5; // default to 1x
for ( $i = 0; $i < count($speeds); $i++ ) {
  if ( $speeds[$i] == $defaultSpeed ) {
    $speedIndex = $i;
    break;
  }
}

xhtmlHeaders(__FILE__, translate('Montage'));
getBodyTopHTML();
echo getNavBarHTML();
?>
<div id="page">
  <div id="header">
<?php
    $html = '<a class="flip" href="#" 
             data-flip-сontrol-object="#mfbpanel" 
             data-flip-сontrol-run-after-func="applyChosen" 
             data-flip-сontrol-run-after-complet-func="changeScale">
               <i id="mfbflip" class="material-icons md-18" data-icon-visible="filter_alt_off" data-icon-hidden="filter_alt"></i>
             </a>'.PHP_EOL;
    $html .= '<a id="block-timelineflip" class="flip" href="#" 
             data-flip-сontrol-object="#timelinediv" 
             data-flip-сontrol-run-after-complet-func="changeScale">
               <i id="timelineflip" class="material-icons md-18" data-icon-visible="history_toggle_off" data-icon-hidden="schedule"></i>
             </a>'.PHP_EOL;
    $html .= '<div id="mfbpanel" class="hidden-shift container-fluid">'.PHP_EOL;
    echo $html;
?>
      <div id="headerButtons">
<?php
if ($Montage::$showControl) {
  echo makeLink('?view=control', translate('Control'));
}
if (canView('System')) {
  if ($Montage::$showZones) {
  ?>
    <a id="HideZones" href="?view=montage&amp;showZones=0"><?php echo translate('Hide Zones')?></a>
  <?php
  } else {
  ?>
    <a id="ShowZones" href="?view=montage&amp;showZones=1"><?php echo translate('Show Zones')?></a>
  <?php
  }
}
?>
      </div>
      <form id="filters_form" method="get">
        <input type="hidden" name="view" value="montage"/>
        <div id="filterbar"><?php echo $filterbar ?></div>



        <div id="fieldsTable" class="hidden-shift"><?php echo $fieldsTable->simple_widget() ?></div>
        <div id="ButtonsDiv"><!-- Заполняется через Ajax для Montage review (пока нет...)--></div>
        <div id="SpeedDiv0"><!-- Заполняется через Ajax для Montage review пока нет...)--></div>
      </form>
      <div id="sizeControl">
        <form action="?view=montage" method="post">
          <input type="hidden" name="object" value="MontageLayout"/>
          <input id="action" type="hidden" name="action" value=""/> <?php // "value" is generated in montage.js depending on the action "Save" or "Delete"?>

          <span id="monitorStatusPositionControl">
            <label><?php echo translate('Monitor status position') ?></label>
            <?php echo htmlSelect('monitorStatusPosition', $monitorStatusPosition, $monitorStatusPositionSelected, array('id'=>'monitorStatusPosition', 'data-on-change'=>'changeMonitorStatusPosition', 'class'=>'chosen')); ?>
          </span>
          <span id="rateControl">
            <label for="changeRate"><?php echo translate('Rate') ?>:</label>
            <?php
$maxfps_options = array(''=>translate('Unlimited'),
  '0.10' => '1/10' .translate('FPS'),
  '0.50' => '1/2' .translate('FPS'),
  '1' => '1 '.translate('FPS'),
  '2' => '2 '.translate('FPS'),
  '5' => '5 '.translate('FPS'),
  '10' => '10 '.translate('FPS'),
  '20' => '20 '.translate('FPS'),
);
echo htmlSelect('changeRate', $maxfps_options, $options['maxfps'], array('id'=>'changeRate', 'data-on-change'=>'changeMonitorRate', 'class'=>'chosen'));
?>
          </span>
          <span id="ratioControl">
            <label><?php echo translate('Ratio') ?></label>
            <?php echo htmlSelect('ratio', [], '', array('id'=>'ratio', 'data-on-change'=>'changeRatioForAll', 'class'=>'chosen')); ?>
          </span>
          <span id="streamQualityControl">
            <label for="streamQuality"><?php echo translate('Stream quality') ?></label>
            <?php echo htmlSelect('streamQuality', $streamQuality, $streamQualitySelected, array('data-on-change'=>'changeStreamQuality','id'=>'streamQuality')); ?>
          </span>
          <span id="scaleControl" class="hidden"> <!-- OLD version, requires removal -->
            <label><?php echo translate('Scale') ?></label>
            <?php echo htmlSelect('scale', $scales, '0'/*$scale*/, array('id'=>'scale', 'data-on-change-this'=>'changeScale', 'class'=>'chosen')); ?>
          </span> 
          <span id="layoutControl">
            <label for="layout"><?php echo translate('Layout') ?></label>
            <?php echo htmlSelect('zmMontageLayout', $Montage::$layoutsById, $Montage::$layout_id, array('id'=>'zmMontageLayout', 'data-on-change'=>'selectLayout', 'class'=>'chosen')); ?>
          </span>
          <input type="hidden" name="Positions"/>
          <button type="button" id="EditLayout" data-on-click-this="edit_layout"><?php echo translate('EditLayout') ?></button>
          <button type="button" id="btnDeleteLayout" class="btn btn-danger" value="Delete" data-on-click-this="delete_layout" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete layout') ?>" disabled><i class="material-icons md-18">delete</i></button>
          <span id="SaveLayout" style="display:none;">
            <input type="text" name="Name" placeholder="<?php echo translate('Enter new name for layout if desired') ?>" autocomplete="off"/>
            <button type="button" value="Save" data-on-click-this="save_layout"><?php echo translate('Save') ?></button>
            <button type="button" value="Cancel" data-on-click-this="cancel_layout"><?php echo translate('Cancel') ?></button>
          </span>

<?php if (defined('ZM_FEATURES_SNAPSHOTS') and ZM_FEATURES_SNAPSHOTS) { ?>
          <button type="button" name="snapshotBtn" data-on-click-this="takeSnapshot">
            <i class="material-icons md-18">camera_enhance</i>
            &nbsp;<?php echo translate('Snapshot') ?>
          </button>
<?php } ?>
          <button type="button" id="fullscreenBtn" title="<?php echo translate('Fullscreen') ?>" class="avail" data-on-click="watchFullscreen">
          <i class="material-icons md-18">fullscreen</i>
          </button>
        </form>
      </div>
    </div>

    <div class="btn-group mr-2" role="group" aria-label="Second group">
      <button type="button" class="btn btn-success btn-sm" data-on-click-this="setLiveMode"><?php echo translate('Live') ?></button>
      <button type="button" class="btn btn-info btn-sm" data-on-click-this="setInRecordingMode"><?php echo translate('InRecording') ?></button>
<!--      <button type="button" class="btn btn-info btn-sm" data-on-click-this="stopAllEvents">STOP</button>
      <button type="button" class="btn btn-info btn-sm" data-on-click-this="playAllEvents">START</button>
      <button type="button" class="btn btn-info btn-sm" data-on-click-this="fitTimeline">FIT</button>
      <button type="button" class="btn btn-info btn-sm" data-on-click-this="clickinitGridStack">initGridStack</button>-->
    </div>
  </div>
  <div id="wrapper-timeline">
    <div id="timelinediv" style="background-color: #FFF;"><!-- Filled out via Ajax for viewing in the recording --></div>
    <div id="buttonBlock">
      <button type="button" id="moveLeftTimeline" title="<?php echo translate('Move left Timeline') ?>" data-on-click-true="moveLeftTimeline">
        <i class="material-icons md-18">arrow_back_ios</i>
      </button>
      <button type="button" id="last_24H" data-on-click="click_last_24H"><?php echo translate('24 Hour') ?></button>
      <button type="button" id="last_8H" data-on-click="click_last_8H"><?php echo translate('8 Hour') ?></button>
      <button type="button" id="last_1H" data-on-click="click_last_1H"><?php echo translate('1 Hour') ?></button>
      <button type="button" id="downloadVideo" data-on-click="click_download"><?php echo translate('Download Video') ?></button>
      <button type="button" id="moveRightTimeline" title="<?php echo translate('Move left Timeline') ?>" data-on-click-true="moveRightTimeline">
        <i class="material-icons md-18">arrow_forward_ios</i>
      </button>
      <button type="button" id="timeMarkerInCenterScale" title="<?php echo translate('Time marker in center of the scale') ?>" data-on-click-true="timeMarkerInCenterScale">
        <i class="material-icons md-18">center_focus_weak</i>
      </button>

        <div id="speedDiv" class="hidden-shift">
          <label for="speedslider"><?php echo translate('Speed') ?></label>
          <input id="speedslider" type="range" min="0" max="<?php echo count($speeds)-1?>" value="<?php echo $speedIndex ?>" step="1"/>
          <span id="speedslideroutput"><?php echo $speeds[$speedIndex] ?> fps</span>
        </div>


      <div id="dvrControls">
        <!--<button type="button" id="prevBtn" title="<?php echo translate('Prev') ?>" class="unavail" disabled="disabled" data-on-click-true="streamPrev">
          <i class="material-icons md-18">skip_previous</i>
        </button>
        <button type="button" id="fastRevBtn" title="<?php echo translate('Rewind') ?>" class="unavail" disabled="disabled" data-on-click-true="streamFastRev">
          <i class="material-icons md-18">fast_rewind</i>
        </button>
        <button type="button" id="slowRevBtn" title="<?php echo translate('StepBack') ?>" class="unavail" disabled="disabled" data-on-click-true="streamSlowRev">
          <i class="material-icons md-18">chevron_left</i>
        </button>-->
        <button type="button" id="stopBtn" title="<?php echo translate('Stop') ?>" class="inactive" disabled="disabled" style="display: none;"  data-on-click-true="clickedStop">
          <i class="material-icons md-18">stop</i>
        </button>
        <button type="button" id="pauseBtn" title="<?php echo translate('Pause') ?>" class="inactive" disabled="disabled" style="display: none;" data-on-click="clickedPause">
          <i class="material-icons md-18">pause</i>
        </button>
        <button type="button" id="playBtn" title="<?php echo translate('Play') ?>" class="active" data-on-click="clickedPlay">
          <i class="material-icons md-18">play_arrow</i>
        </button>
        <!--<button type="button" id="slowFwdBtn" title="<?php echo translate('StepForward') ?>" class="inactive" data-on-click-true="streamSlowFwd">
          <i class="material-icons md-18">chevron_right</i>
        </button>
        <button type="button" id="fastFwdBtn" title="<?php echo translate('FastForward') ?>" class="unavail" disabled="disabled" data-on-click-true="streamFastFwd">
          <i class="material-icons md-18">fast_forward</i>
        </button>-->
        <!--<button type="button" id="zoomOutBtn" title="<?php echo translate('ZoomOut') ?>" class="unavail" disabled="disabled" data-on-click="clickZoomOut">
          <i class="material-icons md-18">zoom_out</i>
        </button>
        <button type="button" id="fullscreenBtn" title="<?php echo translate('Fullscreen') ?>" class="avail" data-on-click="fullscreenClicked">
          <i class="material-icons md-18">fullscreen</i>
        </button>
        <button type="button" id="nextBtn" title="<?php echo translate('Next') ?>" class="unavail" disabled="disabled" data-on-click-true="streamNext">
          <i class="material-icons md-18">skip_next</i>-->
        </button>
      </div>
    </div><!--#buttonBlock-->
  </div>
<!--<div id="alert-load-events" class="alert alert-info alert-dismissible fade show" role="alert">-->
<div id="alert-load-events" class="alert alert-info" role="alert">
  <h4 class="alert-heading"><?php echo translate('PleaseWait') ?></h4>
  <?php echo translate('EventsLoading') ?>
<!--  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>-->
</div>
  <div id="content">
    <div id="monitors" class="grid-stack hidden-shift">
      <!-- Filled with monitor blocks via AJAX -->
    </div>
  </div>
</div>
  <script src="<?php echo cache_bust('js/adapter.min.js') ?>"></script>
<?php if ($Montage::$need_janus) { ?>
  <script src="/javascript/janus/janus.js"></script>
<?php } ?>
<?php if ($Montage::$need_hls) { ?>
  <script src="<?php echo cache_bust('js/hls.js') ?>"></script>
<?php } ?>
  <script src="<?php echo cache_bust('js/MonitorStream.js') ?>"></script>

<!-- In May 2024, IgorA100 globally changed grid layout -->
<div id="messageModal" class="modal fade" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Error reading Layout')?></h5>
      </div>
      <div class="modal-body">
        <span id="message-error"></span>
        <span><?php echo translate('This Layout was saved in previous version of ZoneMinder!')?></span>
        <br>
        <span><?php echo translate('It is necessary to place monitors again and resave the Layout.')?></span>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Close') ?></button>
      </div>
    </div>
  </div>
</div>
<script src="<?php echo cache_bust('skins/classic/js/export.js') ?>"></script>
<?php xhtmlFooter() ?>
