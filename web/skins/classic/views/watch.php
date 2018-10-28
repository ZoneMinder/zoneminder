<?php
//
// ZoneMinder web watch feed view file, $Date$, $Revision$
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

require_once('includes/Monitor.php');

if ( !canView('Stream') ) {
  $view = 'error';
  return;
}

if ( ! isset($_REQUEST['mid']) ) {
  $view = 'error';
  return;
}

// This is for input sanitation
$mid = intval($_REQUEST['mid']); 
if ( ! visibleMonitor($mid) ) {
  $view = 'error';
  return;
}

$monitor = new Monitor($mid);

#Whether to show the controls button
$showPtzControls = ( ZM_OPT_CONTROL && $monitor->Controllable() && canView('Control') && $monitor->Type() != 'WebSite' );

if ( isset($_REQUEST['scale']) ) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmWatchScale'.$mid]) ) {
  $scale = $_COOKIE['zmWatchScale'.$mid];
} else {
  $scale = reScale(SCALE_BASE, $monitor->DefaultScale(), ZM_WEB_DEFAULT_SCALE);
}

$connkey = generateConnKey();

$streamMode = getStreamMode();

noCacheHeaders();

$popup = ((isset($_REQUEST['popup'])) && ($_REQUEST['popup'] == 1));

xhtmlHeaders( __FILE__, $monitor->Name().' - '.translate('Feed') );
?>
<body>
  <div id="page">
  <?php if ( !$popup ) echo getNavBarHTML() ?>
    <div id="header">
        <div id="monitorName"><?php echo $monitor->Name() ?></div>
        <div id="menuControls">
<?php
if ( canView('Control') && $monitor->Type() == 'Local' ) {
?>
          <div id="settingsControl"><?php echo makePopupLink( '?view=settings&amp;mid='.$monitor->Id(), 'zmSettings'.$monitor->Id(), 'settings', translate('Settings'), true, 'id="settingsLink"' ) ?></div>
<?php
}
?>
          <div id="scaleControl"><?php echo translate('Scale') ?>: <?php echo buildSelect( "scale", $scales, "changeScale( this );" ); ?></div>
        </div>
        <div id="closeControl"><a href="#" onclick="<?php echo $popup ? 'window.close()' : 'window.history.back()' ?>"><?php echo $popup ? translate('Close') : translate('Back') ?></a></div>
    </div>
    <div id="content">
      <div id="imageFeed"><?php echo getStreamHTML( $monitor, array('scale'=>$scale) ); ?></div>
<?php if ( $monitor->Type() != 'WebSite' ) { ?>
      <div id="monitorStatus">
<?php if ( canEdit('Monitors') ) { ?>
        <div id="enableDisableAlarms">
          <a id="enableAlarmsLink" href="#" onclick="cmdEnableAlarms();return false;" class="hidden">
          <?php echo translate('EnableAlarms') ?></a>
          <a id="disableAlarmsLink" href="#" onclick="cmdDisableAlarms();return false;" class="hidden">
          <?php echo translate('DisableAlarms') ?></a>
        </div>
<?php
}
if ( canEdit('Monitors') ) {
?>
        <div id="forceCancelAlarm">
            <a id="forceAlarmLink" href="#" onclick="cmdForceAlarm();"><?php echo translate('ForceAlarm') ?></a>
            <a id="cancelAlarmLink" href="#" onclick="cmdCancelForcedAlarm();" class="hidden"><?php echo translate('CancelForcedAlarm') ?></a>
        </div>
<?php
}
?>
        <div id="monitorState"><?php echo translate('State') ?>:&nbsp;<span id="stateValue"></span>&nbsp;-&nbsp;<span id="fpsValue"></span>&nbsp;fps</div>
      </div>
      <div id="dvrControls">
<?php
if ( $streamMode == 'jpeg' ) {
  if ( $monitor->StreamReplayBuffer() != 0 ) {
?>
        <input type="button" value="&lt;&lt;" id="fastRevBtn" title="<?php echo translate('Rewind') ?>" class="unavail" disabled="disabled" onclick="streamCmdFastRev(true)"/>
        <input type="button" value="&lt;" id="slowRevBtn" title="<?php echo translate('StepBack') ?>" class="unavail" disabled="disabled" onclick="streamCmdSlowRev(true)"/>
<?php 
  }
?>
        <input type="button" value="||" id="pauseBtn" title="<?php echo translate('Pause') ?>" class="inactive" onclick="streamCmdPause(true)"/>
        <input type="button" value="[]" id="stopBtn" title="<?php echo translate('Stop') ?>" class="unavail" disabled="disabled" onclick="streamCmdStop(true)"/>
        <input type="button" value="|&gt;" id="playBtn" title="<?php echo translate('Play') ?>" class="active" disabled="disabled" onclick="streamCmdPlay(true)"/>
<?php
  if ( $monitor->StreamReplayBuffer() != 0 ) {
?>
        <input type="button" value="&gt;" id="slowFwdBtn" title="<?php echo translate('StepForward') ?>" class="unavail" disabled="disabled" onclick="streamCmdSlowFwd(true)"/>
        <input type="button" value="&gt;&gt;" id="fastFwdBtn" title="<?php echo translate('FastForward') ?>" class="unavail" disabled="disabled" onclick="streamCmdFastFwd(true)"/>
<?php
  }
?>
        <input type="button" value="&ndash;" id="zoomOutBtn" title="<?php echo translate('ZoomOut') ?>" class="avail" onclick="streamCmdZoomOut()"/>
<?php
} // end if streamMode==jpeg
?>
      </div>
      <div id="replayStatus"<?php echo $streamMode=="single"?' class="hidden"':'' ?>>
        <span id="mode"><?php echo translate('Mode') ?>: <span id="modeValue"></span></span>
        <span id="rate"><?php echo translate('Rate') ?>: <span id="rateValue"></span>x</span>
        <span id="delay"><?php echo translate('Delay') ?>: <span id="delayValue"></span>s</span>
        <span id="level"><?php echo translate('Buffer') ?>: <span id="levelValue"></span>%</span>
        <span id="zoom"><?php echo translate('Zoom') ?>: <span id="zoomValue"></span>x</span>
      </div>
<?php } // end if $monitor->Type() != 'WebSite' ?>
<?php
if ( $showPtzControls ) {
    foreach ( getSkinIncludes( 'includes/control_functions.php' ) as $includeFile )
        require_once $includeFile;
?>
      <div id="ptzControls" class="ptzControls">
<?php echo ptzControls( $monitor ) ?>
      </div>
<?php
}
if ( canView( 'Events' ) && $monitor->Type() != 'WebSite' ) {
?>
      <div id="events">
        <table id="eventList" cellspacing="0">
          <thead>
            <tr>
              <th class="colId"><?php echo translate('Id') ?></th>
              <th class="colName"><?php echo translate('Name') ?></th>
              <th class="colTime"><?php echo translate('Time') ?></th>
              <th class="colSecs"><?php echo translate('Secs') ?></th>
              <th class="colFrames"><?php echo translate('Frames') ?></th>
              <th class="colScore"><?php echo translate('Score') ?></th>
              <th class="colDelete">&nbsp;</th>
            </tr>
          </thead>
          <tbody>
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
</body>
</html>
