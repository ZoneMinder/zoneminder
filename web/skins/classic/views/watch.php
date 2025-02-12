<?php
//
// ZoneMinder web watch feed view file
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
require_once('includes/Monitor.php');
include('_monitor_filters.php');
$resultMonitorFilters = buildMonitorsFilters();
$filterbar = $resultMonitorFilters['filterBar'];
$displayMonitors = $resultMonitorFilters['displayMonitors'];

$widths = array(
  'auto'  => translate('auto'),
  '100%'  => '100%',
  '160px' => '160px',
  '320px' => '320px',
  '352px' => '352px',
  '640px' => '640px',
  '1280px' => '1280px',
  '1920px'  =>  '1920px'
);

$heights = array(
  'auto'  => translate('auto'),
  '240px' => '240px',
  '480px' => '480px',
  '720px' => '720px',
  '1080px'  =>  '1080px',
);

// This is for input sanitation
$mid = isset($_REQUEST['mid']) ? intval($_REQUEST['mid']) : 0;
$monitors = array();
$monitor_index = -1;
foreach ($displayMonitors as &$row) {
  if ($row['Capturing'] == 'None') continue;
  if ($mid and ($row['Id'] == $mid)) $monitor_index = count($monitors);
  $monitors[] = new ZM\Monitor($row);
  if (!isset($widths[$row['Width'].'px'])) {
    $widths[$row['Width'].'px'] = $row['Width'].'px';
  }
  if (!isset($heights[$row['Height'].'px'])) {
    $heights[$row['Height'].'px'] = $row['Height'].'px';
  }
  unset($row);
} # end foreach Monitor

if ($mid and ($monitor_index == -1)) {
  $monitor = ZM\Monitor::find_one(array('Id'=>$mid));
  if (!$monitor) {
    ZM\Error("No monitor found for $mid");
  } else {
    $monitor_index = count($monitors);
    $monitors[] = $monitor;
  }
}

if (!$mid and count($monitors)) {
  $mid = $monitors[0]->Id();
  $monitor_index = 0;
  $nextMid = ($monitor_index == count($monitors)-1) ? $monitors[0]->Id() : $monitors[$monitor_index+1]->Id();
}

if (!visibleMonitor($mid)) {
  $view = 'error';
  return;
}

$monitor = new ZM\Monitor($mid);

# cycle is wether to do the countdown/move to next monitor bit.
# showCycle is whether to show the cycle controls.
# If cycle is true, then showcycle should also be true.
# If showcycle is false, then cycle should be false
# But showcycle can be true, and cycle false.
$cycle = false;
$showCycle = false;
if (isset($_REQUEST['cycle']) and ($_REQUEST['cycle'] == 'true')) {
  $cycle = true;
}
$showCycle = $cycle;
if (!$cycle and isset($_COOKIE['zmCycleShow'])) {
  $showCycle = $_COOKIE['zmCycleShow'] == 'true';
}
#Whether to show the controls button
$hasPtzControls = false;
$hasHLS = false;
foreach ($monitors as $m) {
  if (( ZM_OPT_CONTROL && $m->Controllable() && canView('Control') && $m->Type() != 'WebSite' )) {
    //If there is control for at least one camera, then we display the block.
    $hasPtzControls = true;
  }
  if ( $m->RTSP2WebEnabled() and $m->RTSP2WebType == "HLS") {
    $hasHLS = true;
  }
  if ($hasPtzControls && $hasHLS) {
    break;
  }
}

$showPtzControls = false;
if ($hasPtzControls) {
  $showPtzControls = true;
  if (isset($_REQUEST['ptzShow']) and ($_REQUEST['ptzShow'] == 'false')) {
    $showPtzControls = false;
  } else if (isset($_COOKIE['ptzShow'])) {
    $showPtzControls = $_COOKIE['ptzShow'] == 'true';
  }
}

$options = array();
if (0) {
if (!empty($_REQUEST['mode']) and ($_REQUEST['mode']=='still' or $_REQUEST['mode']=='stream')) {
  $options['mode'] = validHtmlStr($_REQUEST['mode']);
} else if (isset($_COOKIE['zmWatchMode'])) {
  $options['mode'] = validHtmlStr($_COOKIE['zmWatchMode']);
} else {
  $options['mode'] = canStream() ? 'stream' : 'still';
}
}
$options['mode'] = 'single';

if (!empty($_REQUEST['maxfps']) and validNum($_REQUEST['maxfps']) and ($_REQUEST['maxfps']>0)) {
  $options['maxfps'] = validNum($_REQUEST['maxfps']);
} else if (isset($_COOKIE['zmWatchRate'])) {
  $options['maxfps'] = validNum($_COOKIE['zmWatchRate']);
} else {
  $options['maxfps'] = ''; // unlimited
}

$period = ZM_WEB_REFRESH_CYCLE;
if (isset($_REQUEST['period'])) {
  $period = validInt($_REQUEST['period']);
} else if (isset($_COOKIE['zmCyclePeriod'])) {
  $period = validInt($_COOKIE['zmCyclePeriod']);
}
/*
if (isset($_REQUEST['scale'])) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmWatchScale'.$mid]) ) {
  $scale = validInt($_COOKIE['zmWatchScale'.$mid]);
} else {
  $scale = validInt($monitor->DefaultScale());
}
*/

if (isset($_REQUEST['scale'])) {
  $scale = $_REQUEST['scale'];
} else if ( isset($_COOKIE['zmWatchScaleNew'.$mid]) ) {
  $scale = $_COOKIE['zmWatchScaleNew'.$mid];
} else {
  $scale = $monitor->DefaultScale();
}

if ( !isset($scales[$scale])) {
  ZM\Info("Invalid scale found in cookie: $scale, defaulting to auto");
  zm_setcookie('zmWatchScaleNew'.$mid, 0);
  $scale = 0;
}
$options['scale'] = 0; //Somewhere something is spoiled because of this...

$streamQualitySelected = '0';
# TODO input validation on streamquality
if (isset($_REQUEST['streamQuality'])) {
  $streamQualitySelected = $_REQUEST['streamQuality'];
} else if (isset($_COOKIE['zmStreamQuality'])) {
  $streamQualitySelected = $_COOKIE['zmStreamQuality'];
} else if (isset($_SESSION['zmStreamQuality']) ) {
  $streamQualitySelected = $_SESSION['zmStreamQuality'];
}

if (isset($_REQUEST['width'])) {
  $options['width'] = validInt($_REQUEST['width']); 
} else if ( isset($_COOKIE['zmWatchWidth']) and $_COOKIE['zmWatchWidth'] ) {
  $options['width'] = $_COOKIE['zmWatchWidth'];
} else {
  $options['width'] = 'auto';
}
$options['width'] = preg_replace('/[^0-9A-Za-z%]/', '', $options['width']);

if (isset($_REQUEST['height'])) {
  $options['height'] =validInt($_REQUEST['height']);
} else if (isset($_COOKIE['zmWatchHeight']) and $_COOKIE['zmWatchHeight']) {
  $options['height'] = $_COOKIE['zmWatchHeight'];
} else {
  $options['height'] = 'auto';
}
$options['height'] = preg_replace('/[^0-9A-Za-z%]/', '', $options['height']);
if (
  ($options['width'] and ($options['width'] != 'auto'))
  or 
  ($options['height'] and ($options['height'] != 'auto'))
) {
  $options['scale'] = 0;
}

function getStreamModeMonitor($monitor) {
  if ($monitor->JanusEnabled()) {
    $streamMode = 'janus';
  } else if ($monitor->RTSP2WebEnabled()) {
    $streamMode = $monitor->RTSP2WebType();
  } else {
    $streamMode = getStreamMode();
  }
  return $streamMode;
}

$streamMode = getStreamModeMonitor($monitor);
noCacheHeaders();
xhtmlHeaders(__FILE__, $monitor->Name().' - '.translate('Feed'));
getBodyTopHTML();
echo getNavBarHTML() ?>
<div id="page">
  <div id="header">
<?php
    $html = '<a class="flip" href="#" 
             data-flip-сontrol-object="#mfbpanel" 
             data-flip-сontrol-run-after-func="applyChosen" 
             data-flip-сontrol-run-after-complet-func="changeScale">
               <i id="mfbflip" class="material-icons md-18" data-icon-visible="filter_alt_off" data-icon-hidden="filter_alt"></i>
             </a>'.PHP_EOL;
    $html .= '<div id="mfbpanel" class="hidden-shift container-fluid">'.PHP_EOL;
    echo $html;
?>
    <div class="controlHeader">
      <form method="get">
        <input type="hidden" name="view" value="watch"/>
        <?php echo $filterbar ?>
      </form>
    </div>

    <div class="d-flex flex-row justify-content-between px-3 py-1">
      <div id="navButtons">
        <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
        <button type="button" id="editBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Edit') ?>"><i class="fa fa-edit"></i></button>
        <button type="button" id="settingsBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Settings') ?>" disabled><i class="fa fa-sliders"></i></button>
        <button type="button" id="enableAlmBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('DisableAlarms') ?>" disabled><i class="fa fa-bell"></i></button>
        <button type="button" id="forceAlmBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('ForceAlarm') ?>" disabled><i class="fa fa-exclamation-circle"></i></button>
      </div>
      <div id="headerButtons">
<!--
        <button type="button" id="streamToggle" class="btn <?php echo $options['mode'] == 'stream' ? 'btn-primary':'btn-secondary'?>" title="<?php echo translate('Toggle Streaming/Stills')?>">
            <span class="material-icons md-18">switch_video</span>
        </button>
-->
        <button type="button" id="cycleToggle" class="btn <?php echo $showCycle ? 'btn-primary':'btn-secondary'?>" title="<?php echo translate('Toggle cycle sidebar')?>">
            <span class="material-icons md-18">view_carousel</span>
        </button>
        <button type="button" id="ptzToggle" class="btn <?php echo $showPtzControls ? 'btn-primary':'btn-secondary'?>"
<?php
    if ($monitor->Controllable() && canView('Control') && ($monitor->Type() != 'WebSite')) {
      echo 'title="'.translate('Toggle PTZ Controls').'"';
    } else {
      echo 'disabled="disabled" title="'.translate('PTZ Not available').'"';
    }
?>
>
            <span class="material-icons md-18">open_with</span>
        </button>
        <span id="rateControl">
          <label><?php echo translate('Rate') ?>:</label>
          <?php
$maxfps_options = array(''=>translate('Unlimited'),
  '0.10' => '1/10 '.translate('FPS'),
  '0.50' => '1/2 '.translate('FPS'),
  '1' => '1 '.translate('FPS'),
  '2' => '2 '.translate('FPS'),
  '5' => '5 '.translate('FPS'),
  '8' => '8 '.translate('FPS'),
  '10' => '10 '.translate('FPS'),
  '15' => '15 '.translate('FPS'),
  '20' => '20 '.translate('FPS'),
);
echo htmlSelect('changeRate', $maxfps_options, $options['maxfps']);
?>
        </span>
      </div>
      <div class="form-check control-use-old-zoom-pan">
        <input id="use-old-zoom-pan" class="form-check-input" type="checkbox" value="">
        <label class="form-check-label" for="use-old-zoom-pan">
          <?php echo translate('Use old ZoomPan') ?>
        </label>
      </div>
      <div id="sizeControl">
        <span id="scaleControl">
          <label><?php echo translate('Scale') ?>:</label>
          <?php echo htmlSelect('scale', $scales, $scale, array('id'=>'scale', 'data-on-change-this'=>'changeScale') ); ?>
        </span>
        <span id="streamQualityControl">
          <label for="streamQuality"><?php echo translate('Stream quality') ?></label>
          <?php echo htmlSelect('streamQuality', $streamQuality, $streamQualitySelected, array('data-on-change'=>'changeStreamQuality','id'=>'streamQuality')); ?>
        </span>
      </div><!--sizeControl-->
    </div><!--control header-->
    </div><!--flip-->
  </div><!--header-->
  <div id="content">
    <div class="container-fluid">
      <div class="row flex-nowrap" >
<?php if (count($monitors)) { ?>
        <nav id="sidebar" <?php echo $showCycle?'':' style="display:none;"'?>>
          <div id="cycleButtons" class="buttons">
<?php
$seconds = translate('seconds');
$minute = translate('minute');
$minutes = translate('minutes');
$cyclePeriodOptions = array(
  5 => '5 '.$seconds,
  10 => '10 '.$seconds,
  30 => '30 '.$seconds,
  60 => '1 '.$minute,
  120 => '2 '.$minutes,
  300 => '5 '.$minutes,
);
if (!isset($cyclePeriodOptions[ZM_WEB_REFRESH_CYCLE])) {
  $cyclePeriodOptions[ZM_WEB_REFRESH_CYCLE] = ZM_WEB_REFRESH_CYCLE.' '.$seconds;
}
echo htmlSelect('cyclePeriod', $cyclePeriodOptions, $period, array('id'=>'cyclePeriod'));
?>
            <span id="secondsToCycle"></span><br/>
            <button type="button" id="cyclePrevBtn" title="<?php echo translate('PreviousMonitor') ?>">
            <i class="material-icons md-18">skip_previous</i>
            </button>
            <button type="button" id="cyclePauseBtn" title="<?php echo translate('PauseCycle') ?>">
            <i class="material-icons md-18">pause</i>
            </button>
            <button type="button" id="cyclePlayBtn" title="<?php echo translate('PlayCycle') ?>">
            <i class="material-icons md-18">play_arrow</i>
            </button>
            <button type="button" id="cycleNextBtn" title="<?php echo translate('NextMonitor') ?>">
            <i class="material-icons md-18">skip_next</i>
            </button>
          </div>
          <ul class="nav nav-pills flex-column">
<?php
  if ($monitor->Type() != 'WebSite') {
    $options['state'] = true;
  }
  $monitorsExtraData = [];
  $monitorJanusUsed = false;
  $dataMonIdx=0;
  if ($hasPtzControls) {
    foreach ( getSkinIncludes('includes/control_functions.php') as $includeFile )
      require_once $includeFile;
  }
  foreach ($monitors as $m) {
    $monitorsExtraData[$m->Id()]['StreamHTML'] = $m->getStreamHTML($options);
    $monitorsExtraData[$m->Id()]['urlForAllEvents'] = "?view=events&page=1&filter%5BQuery%5D%5Bterms%5D%5B0%5D%5Battr%5D=Monitor&filter%5BQuery%5D%5Bterms%5D%5B0%5D%5Bop%5D=%3D&filter%5BQuery%5D%5Bterms%5D%5B0%5D%5Bval%5D=".$m->Id()."&filter%5BQuery%5D%5Bsort_asc%5D=1&filter%5BQuery%5D%5Bsort_field%5D=StartDateTime&filter%5BQuery%5D%5Bskip_locked%5D=&filter%5BQuery%5D%5Blimit%5D=0";
    if ($m->JanusEnabled()) {
      $monitorJanusUsed = true;
    }
    $monitorsExtraData[$m->Id()]['ptzControls'] = '';
    if ($hasPtzControls) {
      $ptzControls = ptzControls($m);
      $monitorsExtraData[$m->Id()]['ptzControls'] = $ptzControls;
    }
    echo '<li id="nav-item-cycle'.$m->Id().'" class="nav-item"><a id="nav-link'.$m->Id().'" class="nav-link'.( $m->Id() == $monitor->Id() ? ' active' : '' ).'" data-monIdx='.$dataMonIdx++.' href="#">'.$m->Name().'</a></li>';
  }
  if ($monitorJanusUsed) {
?>
    <script src="<?php echo cache_bust('js/adapter.min.js') ?>"></script>
    <script src="/javascript/janus/janus.js"></script>
<?php
  }
 ?>
          </ul>
        </nav>
        <div id="wrapperMonitor" class="container-fluid col">
          <div id="monitor" class="monitor hidden-shift"
>
<?php 
echo $monitor->getStreamHTML($options);
?>
          </div><!-- id="Monitor" -->
          <div class="buttons" id="dvrControls">
<!--
          <button type="button" id="getImageBtn" title="<?php echo translate('Download Image') ?>"/>
-->
              <button type="button" id="fastRevBtn" title="<?php echo translate('Rewind') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdFastRev">
              <i class="material-icons md-18">fast_rewind</i>
              </button>
              <button type="button" id="slowRevBtn" title="<?php echo translate('StepBack') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdSlowRev">
              <i class="material-icons md-18">chevron_left</i>
              </button>
              <button type="button" id="pauseBtn" title="<?php echo translate('Pause') ?>" class="inactive" data-on-click-true="streamCmdPause">
              <i class="material-icons md-18">pause</i>
              </button>
              <button type="button" id="stopBtn" title="<?php echo translate('Stop') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdStop">
              <i class="material-icons md-18">stop</i>
              </button>
              <button type="button" id="playBtn" title="<?php echo translate('Play') ?>" class="active" disabled="disabled" data-on-click-true="streamCmdPlay">
              <i class="material-icons md-18">play_arrow</i>
              </button>
              <button type="button" id="slowFwdBtn" title="<?php echo translate('StepForward') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdSlowFwd">
              <i class="material-icons md-18">chevron_right</i>
              </button>
              <button type="button" id="fastFwdBtn" title="<?php echo translate('FastForward') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdFastFwd">
              <i class="material-icons md-18">fast_forward</i>
              </button>
              <button type="button" id="zoomOutBtn" title="<?php echo translate('ZoomOut') ?>" class="avail" data-on-click="zoomOutClick">
              <i class="material-icons md-18">zoom_out</i>
              </button>
          </div><!--dvrControls-->
          <div class="buttons" id="extButton"> 
            <button type="button" id="fullscreenBtn" title="<?php echo translate('Fullscreen') ?>" class="avail" data-on-click="watchFullscreen">
            <i class="material-icons md-18">fullscreen</i>
            </button>
            <button type="button" id="allEventsBtn" title="<?php echo translate('All Events') ?>" class="avail" data-on-click="watchAllEvents"><?php echo translate('All Events') ?> 
            </button>
          </div>
        </div><!-- id="wrapperMonitor" -->

<!-- START Control -->
<?php
if ( $hasPtzControls ) {
?>
        <div id="ptzControls" class="col-sm-2 ptzControls"<?php echo $showPtzControls ? '' : ' style="display:none;"'?>>
        </div>
<?php
}
?>
<!-- END Control -->
      </div><!-- class="row" -->

<!-- START table Events -->
<?php
if ( canView('Events') && ($monitor->Type() != 'WebSite') ) {
?>
      <!-- Table styling handled by bootstrap-tables -->
      <div id="events" class="row justify-content-center table-responsive-sm">
        <table 
          id="eventList"
          data-locale="<?php echo i18n() ?>"
          data-side-pagination="server"
          data-ajax="ajaxRequest"
          data-cookie="true"
          data-cookie-id-table="zmEventListTable"
          data-cookie-expire="2y"
          data-show-columns="true"
          data-show-export="true"
          data-uncheckAll="true"
          data-buttons-class="btn btn-normal"
          data-show-refresh="true"
          class="table-sm table-borderless"
        >
          <thead class="thead-highlight">
            <!-- Row styling is handled by bootstrap-tables -->
            <tr>
              <th data-sortable="false" data-field="Delete"><?php echo translate('Delete') ?></th>
              <th data-sortable="false" data-field="Id"><?php echo translate('Id') ?></th>
              <th data-sortable="false" data-field="Name"><?php echo translate('Name') ?></th>
              <th data-sortable="false" data-field="Cause"><?php echo translate('Cause') ?></th>
              <th data-sortable="false" data-field="Tags"><?php echo translate('Tags') ?></th>
              <th data-sortable="false" data-field="Notes"><?php echo translate('Notes') ?></th>
              <th data-sortable="false" data-field="StartDateTime"><?php echo translate('AttrStartTime') ?></th>
              <th data-sortable="false" data-field="EndDateTime"><?php echo translate('AttrEndTime') ?></th>
              <th data-sortable="false" data-field="Length"><?php echo translate('Duration') ?></th>
              <th data-sortable="false" data-field="Frames"><?php echo translate('Frames') ?></th>
              <th data-sortable="false" data-field="AlarmFrames"><?php echo translate('AlarmBrFrames') ?></th>
              <th data-sortable="false" data-field="AvgScore"><?php echo translate('AvgBrScore') ?></th>
              <th data-sortable="false" data-field="MaxScore"><?php echo translate('MaxBrScore') ?></th>
<?php if (ZM_WEB_LIST_THUMBS) { ?>
              <th data-sortable="false" data-field="Thumbnail"><?php echo translate('Thumbnail') ?></th>
<?php } ?>
            </tr>
          </thead>

          <tbody>
          <!-- Row data populated via Ajax -->
          </tbody>

        </table>
      </div>
<?php
} //end if ( canView('Events') && ($monitor->Type() != 'WebSite') )
?>
<!-- END table Events -->

    </div><!-- id="content" -->
  </div>
</div>
<?php
if ($hasHLS) {
?>
  <script src="<?php echo cache_bust('js/hls.js') ?>"></script>
<?php
}
?>
<?php
  } else {
    echo "There are no monitors to display\n";
  }
  echo '<script src="'.cache_bust('js/MonitorStream.js') .'"></script>'.PHP_EOL;
  xhtmlFooter();
?>
