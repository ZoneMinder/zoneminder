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

if ( !canView('Stream') ) {
  $view = 'error';
  return;
}

if ( !isset($_REQUEST['mid']) ) {
  $view = 'error';
  return;
}

// This is for input sanitation
$mid = intval($_REQUEST['mid']); 
if ( !visibleMonitor($mid) ) {
  $view = 'error';
  return;
}

require_once('includes/Monitor.php');
$monitor = new ZM\Monitor($mid);

#Whether to show the controls button
$showPtzControls = ( ZM_OPT_CONTROL && $monitor->Controllable() && canView('Control') && $monitor->Type() != 'WebSite' );

if ( isset($_REQUEST['scale']) ) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmWatchScale'.$mid]) ) {
  $scale = $_COOKIE['zmWatchScale'.$mid];
  if ($scale == 'auto') $scale = '0';
} else {
  $scale = $monitor->DefaultScale();
}

$connkey = generateConnKey();

$streamMode = getStreamMode();

$popup = ((isset($_REQUEST['popup'])) && ($_REQUEST['popup'] == 1));

noCacheHeaders();
xhtmlHeaders(__FILE__, $monitor->Name().' - '.translate('Feed'));
?>
<body>
  <?php echo getNavBarHTML() ?>
    <div class="d-flex flex-row justify-content-between px-3 py-1">
      <div>
        <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
        <button type="button" id="settingsBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Settings') ?>" disabled><i class="fa fa-sliders"></i></button>
        <button type="button" id="enableAlmBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('DisableAlarms') ?>" disabled><i class="fa fa-bell"></i></button>
        <button type="button" id="forceAlmBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('ForceAlarm') ?>" disabled><i class="fa fa-exclamation-circle"></i></button>
      </div>

      <div>
        <h2><?php echo makeLink('?view=monitor&amp;mid='.$monitor->Id(), validHtmlStr($monitor->Name()), canEdit('Monitors')) ?></h2>
      </div>

      <div>
        <?php echo translate('Scale').': '.htmlSelect('scale', $scales, $scale, array('id'=>'scale')); ?>
      </div>
    </div>
<?php
if ( $monitor->Status() != 'Connected' and $monitor->Type() != 'WebSite' ) {
  echo '<div class="warning">Monitor is not capturing. We will be unable to provide an image</div>';
}
?>
    <div id="content">
      <div class="monitor" id="monitor<?php echo $monitor->Id() ?>">
        <div id="imageFeed<?php echo $monitor->Id() ?>"
<?php
if ( $streamMode == 'jpeg' ) {
  echo 'title="Click to zoom, shift click to pan, ctrl click to zoom out"';
}
?>
>
        <?php echo getStreamHTML($monitor, array('scale'=>$scale, 'mode'=>'single')); ?>
        </div>
      </div>
<?php if ( $monitor->Type() != 'WebSite' ) { ?>
      <div id="monitorStatus">
        <div id="monitorState">
          <?php echo translate('State') ?>:
          <span id="stateValue<?php echo $monitor->Id() ?>"></span> -
          <span title="<?php echo translate('Viewing FPS')?>"><span id="viewingFPSValue<?php echo $monitor->Id() ?>"></span> fps</span>
          <span title="<?php echo translate('Capturing FPS')?>"><span id="captureFPSValue<?php echo $monitor->Id() ?>"></span> fps</span>
          <?php if ( $monitor->Function() == 'Modect' or $monitor->Function() == 'Mocord' ) { ?>
          <span title="<?php echo translate('Analysis FPS')?>"><span id="analysisFPSValue<?php echo $monitor->Id() ?>"></span> fps</span>
          <?php } ?>
        </div>
      </div>
      <div id="dvrControls">
<?php
if ( $streamMode == 'jpeg' ) {
  if ( $monitor->StreamReplayBuffer() != 0 ) {
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
  if ( $monitor->StreamReplayBuffer() != 0 ) {
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
<?php
} // end if streamMode==jpeg
?>
      </div>
      <div id="replayStatus"<?php echo $streamMode=="single" ? ' class="hidden"' : '' ?>>
        <span id="mode<?php echo $monitor->Id() ?>"><?php echo translate('Mode') ?>: <span id="modeValue<?php echo $monitor->Id() ?>"></span></span>
        <span id="rate<?php echo $monitor->Id() ?>"><?php echo translate('Rate') ?>: <span id="rateValue<?php echo $monitor->Id() ?>"></span>x</span>
        <span id="delay<?php echo $monitor->Id() ?>"><?php echo translate('Delay') ?>: <span id="delayValue<?php echo $monitor->Id() ?>"></span>s</span>
        <span id="level<?php echo $monitor->Id() ?>"><?php echo translate('Buffer') ?>: <span id="levelValue<?php echo $monitor->Id() ?>"></span>%</span>
        <span id="zoom<?php echo $monitor->Id() ?>"><?php echo translate('Zoom') ?>: <span id="zoomValue<?php echo $monitor->Id() ?>"></span>x</span>
      </div>
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
              <th data-sortable="false" data-field="Cause"><?php echo translate('Cause') ?></th>
              <th data-sortable="false" data-field="Notes"><?php echo translate('Notes') ?></th>
              <th data-sortable="false" data-field="StartDateTime"><?php echo translate('AttrStartTime') ?></th>
              <th data-sortable="false" data-field="EndDateTime"><?php echo translate('AttrEndTime') ?></th>
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
 <script src="<?php echo cache_bust('js/MonitorStream.js') ?>"></script>
<?php xhtmlFooter() ?>
