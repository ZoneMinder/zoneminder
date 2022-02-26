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

ob_start();
include('_monitor_filters.php');
$filterbar = ob_get_contents();
ob_end_clean();


// This is for input sanitation
$mid = isset($_REQUEST['mid']) ? intval($_REQUEST['mid']) : 0;

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

$monitors = array();
$monitor_index = 0;
foreach ($displayMonitors as &$row) {
  if ($row['Function'] == 'None') continue;
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

if (!$mid) {
  $mid = $monitors[0]->Id();
  $monitor_index = 0;
}

if (!visibleMonitor($mid)) {
  $view = 'error';
  return;
}

$monitor = new ZM\Monitor($mid);
$nextMid = ($monitor_index == count($monitors)-1) ? $monitors[0]->Id() : $monitors[$monitor_index+1]->Id();
$cycle = isset($_REQUEST['cycle']) and ($_REQUEST['cycle'] == 'true');
$showCycle = $cycle;
if (isset($_COOKIE['zmCycleShow'])) {
  $showCycle = $_COOKIE['zmCycleShow'] == 'true';
}
#Whether to show the controls button
$showPtzControls = ( ZM_OPT_CONTROL && $monitor->Controllable() && canView('Control') && $monitor->Type() != 'WebSite' );

$options = array();
if (!empty($_REQUEST['mode']) and ($_REQUEST['mode']=='still' or $_REQUEST['mode']=='stream')) {
  $options['mode'] = validHtmlStr($_REQUEST['mode']);
} else if (isset($_COOKIE['zmWatchMode'])) {
  $options['mode'] = $_COOKIE['zmWatchMode'];
} else {
  $options['mode'] = canStream() ? 'stream' : 'still';
}
if (!empty($_REQUEST['maxfps']) and validFloat($_REQUEST['maxfps']) and ($_REQUEST['maxfps']>0)) {
  $options['maxfps'] = validHtmlStr($_REQUEST['maxfps']);
} else if (isset($_COOKIE['zmWatchRate'])) {
  $options['maxfps'] = $_COOKIE['zmWatchRate'];
} else {
  $options['maxfps'] = ''; // unlimited
}

$period = ZM_WEB_REFRESH_CYCLE;
if (isset($_REQUEST['period'])) {
  $period = validInt($_REQUEST['period']);
} else if (isset($_COOKIE['zmCyclePeriod'])) {
  $period = validInt($_COOKIE['zmCyclePeriod']);
}

if (isset($_REQUEST['scale'])) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmWatchScale'.$mid]) ) {
  $scale = $_COOKIE['zmWatchScale'.$mid];
} else {
  $scale = $monitor->DefaultScale();
}
$options['scale'] = $scale;

if (isset($_REQUEST['width'])) {
  $options['width'] = validInt($_REQUEST['width']); 
} else if ( isset($_COOKIE['zmCycleWidth']) and $_COOKIE['zmCycleWidth'] ) {
  $options['width'] = $_COOKIE['zmCycleWidth'];
} else {
  $options['width'] = '';
}
if (isset($_REQUEST['height'])) {
  $options['height'] =validInt($_REQUEST['height']);
} else if (isset($_COOKIE['zmCycleHeight']) and $_COOKIE['zmCycleHeight']) {
  $options['height'] = $_COOKIE['zmCycleHeight'];
} else {
  $options['height'] = '';
}

$connkey = generateConnKey();
if ( $monitor->JanusEnabled() ) {
    $streamMode = 'janus';
} else {
  $streamMode = getStreamMode();
}

noCacheHeaders();
xhtmlHeaders(__FILE__, $monitor->Name().' - '.translate('Feed'));
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="header">
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
          <?php
$maxfps_options = array(''=>translate('Unlimited'),
  '0' => translate('Stills'),
  '1' => '1 '.translate('FPS'),
  '2' => '2 '.translate('FPS'),
  '5' => '5 '.translate('FPS'),
  '10' => '10 '.translate('FPS'),
  '20' => '20 '.translate('FPS'),
);
echo htmlSelect('changeRate', $maxfps_options, $options['maxfps']);
?>
      </div>
      <div id="sizeControl">
        <span id="widthControl">
          <label><?php echo translate('Width') ?>:</label>
          <?php echo htmlSelect('width', $widths, $options['width'], array('id'=>'width', 'data-on-change-this'=>'changeSize') ); ?>
        </span>
        <span id="heightControl">
          <label><?php echo translate('Height') ?>:</label>
          <?php echo htmlSelect('height', $heights, $options['height'], array('id'=>'height', 'data-on-change-this'=>'changeSize') ); ?>
        </span>
        <span id="scaleControl">
          <label><?php echo translate('Scale') ?>:</label>
          <?php echo htmlSelect('scale', $scales, $options['scale'], array('id'=>'scale', 'data-on-change-this'=>'changeScale') ); ?>
        </span>
      </div><!--sizeControl-->
    </div><!--control header-->
  </div><!--header-->
<?php
if ( $monitor->Status() != 'Connected' and $monitor->Type() != 'WebSite' ) {
  echo '<div class="warning">Monitor is not capturing. We will be unable to provide an image</div>';
}
?>
    <div class="container-fluid h-100">
      <div class="row flex-nowrap h-100" id="content">
        <nav id="sidebar" class="h-100"<?php echo $showCycle?'':' style="display:none;"'?>>
          <div id="cycleButtons" class="buttons">
<?php
$seconds = translate('seconds');
$minute = translate('minute');
$minutes = translate('minutes');
$cyclePeriodOptions = array(
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
          <ul class="nav nav-pills flex-column h-100">
<?php
  foreach ($monitors as $m) {
    echo '<li class="nav-item"><a class="nav-link'.( $m->Id() == $monitor->Id() ? ' active' : '' ).'" href="?view=watch&amp;mid='.$m->Id().'">'.$m->Name().'</a></li>';
  }
 ?>
          </ul>
        </nav>
      <div class="container-fluid col-sm-offset-2 h-100 pr-0">
        <div id="imageFeed<?php echo $monitor->Id() ?>"
<?php
if ($streamMode == 'jpeg') {
  echo 'title="Click to zoom, shift click to pan, ctrl click to zoom out"';
}
?>
><?php echo getStreamHTML($monitor, $options); ?>
        </div>
<?php if ($monitor->Type() != 'WebSite') {
    echo $monitor->getMonitorStateHTML();
 ?>
        <div class="buttons" id="dvrControls">
<?php
if ($streamMode == 'jpeg') {
  if ($monitor->StreamReplayBuffer() != 0) {
?>
            <button type="button" id="fastRevBtn" title="<?php echo translate('Rewind') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdFastRev">
            <i class="material-icons md-18">fast_rewind</i>
            </button>
            <button type="button" id="slowRevBtn" title="<?php echo translate('StepBack') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdSlowRev">
            <i class="material-icons md-18">chevron_right</i>
            </button>
<?php 
  }
?>
            <button type="button" id="pauseBtn" title="<?php echo translate('Pause') ?>" class="inactive" data-on-click-true="streamCmdPause">
            <i class="material-icons md-18">pause</i>
            </button>
            <button type="button" id="stopBtn" title="<?php echo translate('Stop') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdStop" style="display:none;">
            <i class="material-icons md-18">stop</i>
            </button>
            <button type="button" id="playBtn" title="<?php echo translate('Play') ?>" class="active" disabled="disabled" data-on-click-true="streamCmdPlay">
            <i class="material-icons md-18">play_arrow</i>
            </button>
<?php
  if ($monitor->StreamReplayBuffer() != 0) {
?>
            <button type="button" id="slowFwdBtn" title="<?php echo translate('StepForward') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdSlowFwd">
            <i class="material-icons md-18">chevron_right</i>
            </button>
            <button type="button" id="fastFwdBtn" title="<?php echo translate('FastForward') ?>" class="unavail" disabled="disabled" data-on-click-true="streamCmdFastFwd">
            <i class="material-icons md-18">fast_forward</i>
            </button>
<?php
  }
?>
            <button type="button" id="zoomOutBtn" title="<?php echo translate('ZoomOut') ?>" class="avail" data-on-click="streamCmdZoomOut">
            <i class="material-icons md-18">zoom_out</i>
            </button>
            <button type="button" id="fullscreenBtn" title="<?php echo translate('Fullscreen') ?>" class="avail" data-on-click="watchFullscreen">
            <i class="material-icons md-18">fullscreen</i>
            </button>
<?php
} // end if streamMode==jpeg
?>
      </div><!--dvrButtons-->
<?php } // end if $monitor->Type() != 'WebSite' ?>
<?php
if ( $showPtzControls ) {
    foreach ( getSkinIncludes('includes/control_functions.php') as $includeFile )
        require_once $includeFile;
?>
      <div id="ptzControls" class="ptzControls">
      <?php echo ptzControls($monitor) ?>
      </div>
<?php
}
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
          <thead>
            <!-- Row styling is handled by bootstrap-tables -->
            <tr>
              <th data-sortable="false" data-field="Delete"><?php echo translate('Delete') ?></th>
              <th data-sortable="false" data-field="Id"><?php echo translate('Id') ?></th>
              <th data-sortable="false" data-field="Name"><?php echo translate('Name') ?></th>
              <th data-sortable="false" data-field="StartDateTime"><?php echo translate('AttrStartTime') ?></th>
              <th data-sortable="false" data-field="Length"><?php echo translate('Duration') ?></th>
              <th data-sortable="false" data-field="Frames"><?php echo translate('Frames') ?></th>
              <th data-sortable="false" data-field="AlarmFrames"><?php echo translate('AlarmBrFrames') ?></th>
              <th data-sortable="false" data-field="AvgScore"><?php echo translate('AvgBrScore') ?></th>
              <th data-sortable="false" data-field="MaxScore"><?php echo translate('MaxBrScore') ?></th>
              <th data-sortable="false" data-field="Thumbnail"><?php echo translate('Thumbnail') ?></th>
            </tr>
          </thead>

          <tbody>
          <!-- Row data populated via Ajax -->
          </tbody>

        </table>
      </div>
    </div>
<?php
}
if ( ZM_WEB_SOUND_ON_ALARM ) {
    $soundSrc = ZM_DIR_SOUNDS.'/'.ZM_WEB_ALARM_SOUND;
?>
      <div id="alarmSound" class="hidden">
<?php
    if ( ZM_WEB_USE_OBJECT_TAGS && isWindows() ) {
?>
        <object id="MediaPlayer" width="0" height="0"
          classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
          codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,902">
          <param name="FileName" value="<?php echo $soundSrc ?>"/>
          <param name="autoStart" value="0"/>
          <param name="loop" value="1"/>
          <param name="hidden" value="1"/>
          <param name="showControls" value="0"/>
          <embed src="<?php echo $soundSrc ?>"
            autostart="true"
            loop="true"
            hidden="true">
          </embed>
        </object>
<?php
    } else {
?>
        <embed src="<?php echo $soundSrc ?>"
          autostart="true"
          loop="true"
          hidden="true">
        </embed>
<?php
    }
?>
      </div>
<?php
}
?>
    </div>
  </div>
  <script src="<?php echo cache_bust('js/adapter.min.js') ?>"></script>
<?php
if ( $monitor->JanusEnabled() ) {
?>
  <script src="/javascript/janus/janus.js"></script>
<?php
}
?>
  <script src="<?php echo cache_bust('js/MonitorStream.js') ?>"></script>
<?php xhtmlFooter() ?>
