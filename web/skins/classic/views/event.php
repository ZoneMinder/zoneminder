<?php
//
// ZoneMinder web event view file, $Date$, $Revision$
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

$eid = validInt($_REQUEST['eid']);
$fid = !empty($_REQUEST['fid']) ? validInt($_REQUEST['fid']) : 1;

$Event = new ZM\Event($eid);
if ( $user['MonitorIds'] ) {
  $monitor_ids = explode(',', $user['MonitorIds']);
  if ( count($monitor_ids) and ! in_array($Event->MonitorId(), $monitor_ids) ) {
    $view = 'error';
    return;
  }
}
$Monitor = $Event->Monitor();

if ( isset($_REQUEST['rate']) ) {
  $rate = validInt($_REQUEST['rate']);
} else if ( isset($_COOKIE['zmEventRate']) ) {
  $rate = $_COOKIE['zmEventRate'];
} else {
  $rate = reScale(RATE_BASE, $Monitor->DefaultRate(), ZM_WEB_DEFAULT_RATE);
}

if ( isset($_REQUEST['scale']) ) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmEventScaleAuto']) ) {
  // If we're using scale to fit use it on all monitors
  $scale = '0';
} else if ( isset($_COOKIE['zmEventScale'.$Event->MonitorId()]) ) {
  $scale = $_COOKIE['zmEventScale'.$Event->MonitorId()];
} else {
  $scale = reScale(SCALE_BASE, $Monitor->DefaultScale(), ZM_WEB_DEFAULT_SCALE);
}

$codec = 'auto';
if ( isset($_REQUEST['codec']) ) {
  $codec = $_REQUEST['codec'];
  session_start();
  $_SESSION['zmEventCodec'.$Event->MonitorId()] = $codec;
  session_write_close();
} else if ( isset($_SESSION['zmEventCodec'.$Event->MonitorId()]) ) {
  $codec = $_SESSION['zmEventCodec'.$Event->MonitorId()];
} else {
  $codec = $Monitor->DefaultCodec();
}
$codecs = array(
  'auto'  => translate('Auto'),
  'MP4'   => translate('MP4'),
  'MJPEG' => translate('MJPEG'),
);

$replayModes = array(
  'none'    => translate('None'),
  'single'  => translate('ReplaySingle'),
  'all'     => translate('ReplayAll'),
  'gapless' => translate('ReplayGapless'),
);

if ( isset($_REQUEST['streamMode']) )
  $streamMode = validHtmlStr($_REQUEST['streamMode']);
else
  $streamMode = 'video';

$replayMode = '';
if ( isset($_REQUEST['replayMode']) )
  $replayMode = validHtmlStr($_REQUEST['replayMode']);
if ( isset($_COOKIE['replayMode']) && preg_match('#^[a-z]+$#', $_COOKIE['replayMode']) )
  $replayMode = validHtmlStr($_COOKIE['replayMode']);

if ( ( ! $replayMode ) or ( ! $replayModes[$replayMode] ) ) {
  $replayMode = 'none';
}

// videojs zoomrotate only when direct recording
$Zoom = 1;
$Rotation = 0;
if ( $Monitor->VideoWriter() == '2' ) {
# Passthrough
  $Rotation = $Event->Orientation();
  if ( in_array($Event->Orientation(),array('90','270')) )
    $Zoom = $Event->Height()/$Event->Width();
}

// These are here to figure out the next/prev event, however if there is no filter, then default to one that specifies the Monitor
if ( !isset($_REQUEST['filter']) ) {
  $_REQUEST['filter'] = array(
    'Query'=>array(
      'terms'=>array(
        array('attr'=>'MonitorId', 'op'=>'=', 'val'=>$Event->MonitorId())
      )
    )
  );
}
parseSort();
parseFilter($_REQUEST['filter']);
$filterQuery = $_REQUEST['filter']['query'];

$connkey = generateConnKey();

$focusWindow = true;

$popup = (isset($_REQUEST['popup']) && ($_REQUEST['popup'] == 1));

xhtmlHeaders(__FILE__, translate('Event'));
?>
<body>
  <div id="page">
    <?php if ( !$popup ) echo getNavBarHTML() ?>
    <div id="header">
<?php 
if ( !$Event->Id() ) {
  echo 'Event was not found.';
} else {
?>
      <div id="dataBar">
        <span id="dataId" title="<?php echo translate('Id') ?>"><?php echo $Event->Id() ?></span>
        <span id="dataMonitor" title="<?php echo translate('Monitor') ?>"><?php echo $Monitor->Id().' '.validHtmlStr($Monitor->Name()) ?></span>
        <span id="dataCause" title="<?php echo $Event->Notes()?validHtmlStr($Event->Notes()):translate('AttrCause') ?>"><?php echo validHtmlStr($Event->Cause()) ?></span>
        <span id="dataTime" title="<?php echo translate('Time') ?>"><?php echo strftime(STRF_FMT_DATETIME_SHORT, strtotime($Event->StartTime())) ?></span>
        <span id="dataDuration" title="<?php echo translate('Duration') ?>"><?php echo $Event->Length().'s' ?></span>
        <span id="dataFrames" title="<?php echo translate('AttrFrames').'/'.translate('AttrAlarmFrames') ?>"><?php echo $Event->Frames() ?>/<?php echo $Event->AlarmFrames() ?></span>
        <span id="dataScore" title="<?php echo translate('AttrTotalScore').'/'.translate('AttrAvgScore').'/'.translate('AttrMaxScore') ?>"><?php echo $Event->TotScore() ?>/<?php echo $Event->AvgScore() ?>/<?php echo $Event->MaxScore() ?></span>
        <span id="Storage">
<?php echo 
  human_filesize($Event->DiskSpace(null)) . ' on ' . validHtmlStr($Event->Storage()->Name()).
  ( $Event->SecondaryStorageId() ? ', '.validHtmlStr($Event->SecondaryStorage()->Name()) : '' )
?></span>
        <div id="closeWindow"><a href="#" onclick="<?php echo $popup ? 'window.close()' : 'window.history.back();return false;' ?>"><?php echo $popup ? translate('Close') : translate('Back') ?></a></div>
      </div>
      <div id="menuBar1">
        <div id="nameControl">
          <input type="text" id="eventName" name="eventName" value="<?php echo validHtmlStr($Event->Name()) ?>" />
          <button value="Rename" type="button" data-on-click="renameEvent"<?php if ( !canEdit('Events') ) { ?> disabled="disabled"<?php } ?>>
<?php echo translate('Rename') ?></button>
        </div>
<?php
if ( canEdit('Events') ) {
?>
        <div id="deleteEvent"><button type="button" data-on-click="deleteEvent" <?php echo $Event->can_delete() ? '' : ' disabled="disabled" title="'.$Event->cant_delete_reason().'"' ?>><?php echo translate('Delete') ?></button></div>
        <div id="editEvent"><button type="button" data-on-click="editEvent"><?php echo translate('Edit') ?></button></div>
        <div id="archiveEvent"<?php echo $Event->Archived() == 1 ? ' class="hidden"' : ''  ?>><button type="button" data-on-click="archiveEvent"><?php echo translate('Archive') ?></button></div>
        <div id="unarchiveEvent"<?php echo $Event->Archived() == 0 ? ' class="hidden"' : '' ?>><button type="button" data-on-click="unarchiveEvent"><?php echo translate('Unarchive') ?></button></div>
<?php
} // end if can edit Events
?>
        <div id="framesEvent"><button type="button" data-on-click="showEventFrames"><?php echo translate('Frames') ?></button></div>
        <div id="streamEvent" class="hidden"><button data-on-click="showStream"><?php echo translate('Stream') ?></button></div>
        <div id="stillsEvent"><button type="button" data-on-click="showStills"><?php echo translate('Stills') ?></button></div>
<?php
  if ( $Event->DefaultVideo() ) { 
?>
        <div id="downloadEventFile"><a class="btn-primary" href="<?php echo $Event->getStreamSrc(array('mode'=>'mp4'),'&amp;')?>" download>Download MP4</a></div>
<?php
  } else {
?>
        <div id="videoEvent"><button type="button" data-on-click="videoEvent"><?php echo translate('Video') ?></button></div>
<?php
  } // end if Event->DefaultVideo
?>
        <div id="exportEvent"><button type="button" data-on-click="exportEvent"><?php echo translate('Export') ?></button></div>
        <div id="replayControl">
          <label for="replayMode"><?php echo translate('Replay') ?></label>
          <?php echo htmlSelect('replayMode', $replayModes, $replayMode, array('data-on-change'=>'changeReplayMode')); ?>
        </div>
        <div id="scaleControl">
          <label for="scale"><?php echo translate('Scale') ?></label>
          <?php echo htmlSelect('scale', $scales, $scale, array('data-on-change'=>'changeScale')); ?>
        </div>
        <div id="codecControl">
          <label for="codec"><?php echo translate('Codec') ?></label>
          <?php echo htmlSelect('codec', $codecs, $codec, array('data-on-change'=>'changeCodec')); ?>
        </div>
      </div>
    </div>
    <div id="content">
      <div id="eventVideo">
<?php
if ( ($codec == 'MP4' || $codec == 'auto' ) && $Event->DefaultVideo() ) {
?>
        <div id="videoFeed">
          <video id="videoobj" class="video-js vjs-default-skin"
            style="transform: matrix(1, 0, 0, 1, 0, 0);"
           <?php echo $scale ? 'width="'.reScale($Event->Width(), $scale).'"' : '' ?>
           <?php echo $scale ? 'height="'.reScale($Event->Height(), $scale).'"' : '' ?>
            data-setup='{ "controls": true, "autoplay": true, "preload": "auto", "plugins": { "zoomrotate": { "zoom": "<?php echo $Zoom ?>"}}}'
          >
          <source src="<?php echo $Event->getStreamSrc(array('mode'=>'mpeg','format'=>'h264'),'&amp;'); ?>" type="video/mp4">
          <track id="monitorCaption" kind="captions" label="English" srclang="en" src='data:plain/text;charset=utf-8,"WEBVTT\n\n 00:00:00.000 --> 00:00:01.000 ZoneMinder"' default/>
          Your browser does not support the video tag.
          </video>
        </div><!--videoFeed-->
<?php
} else {
?>
      <div id="imageFeed">
<?php
if ( (ZM_WEB_STREAM_METHOD == 'mpeg') && ZM_MPEG_LIVE_FORMAT ) {
  $streamSrc = $Event->getStreamSrc(array('mode'=>'mpeg', 'scale'=>$scale, 'rate'=>$rate, 'bitrate'=>ZM_WEB_VIDEO_BITRATE, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'format'=>ZM_MPEG_REPLAY_FORMAT, 'replay'=>$replayMode),'&amp;');
  outputVideoStream('evtStream', $streamSrc, reScale( $Event->Width(), $scale ).'px', reScale( $Event->Height(), $scale ).'px', ZM_MPEG_LIVE_FORMAT );
} else {
  $streamSrc = $Event->getStreamSrc(array('mode'=>'jpeg', 'frame'=>$fid, 'scale'=>$scale, 'rate'=>$rate, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>$replayMode),'&amp;');
  if ( canStreamNative() ) {
    outputImageStream('evtStream', $streamSrc, reScale($Event->Width(), $scale).'px', reScale($Event->Height(), $scale).'px', validHtmlStr($Event->Name()));
  } else {
    outputHelperStream('evtStream', $streamSrc, reScale($Event->Width(), $scale).'px', reScale($Event->Height(), $scale).'px' );
  }
} // end if stream method
?>
        <div id="alarmCue" class="alarmCue"></div>
        <div id="progressBar" style="width: <?php echo reScale($Event->Width(), $scale);?>px;">
          <div class="progressBox" id="progressBox" title="" style="width: 0%;"></div>
        </div><!--progressBar-->
      </div><!--imageFeed-->
<?php } /*end if !DefaultVideo*/ ?>
        <p id="dvrControls">
          <button type="button" id="prevBtn" title="<?php echo translate('Prev') ?>" class="inactive" data-on-click-true="streamPrev">
          <i class="material-icons md-18">skip_previous</i>
          </button>
          <button type="button" id="fastRevBtn" title="<?php echo translate('Rewind') ?>" class="inactive" data-on-click-true="streamFastRev">
          <i class="material-icons md-18">fast_rewind</i>
          </button>
          <button type="button" id="slowRevBtn" title="<?php echo translate('StepBack') ?>" class="unavail" disabled="disabled" data-on-click-true="streamSlowRev">
          <i class="material-icons md-18">chevron_left</i>
          </button>
          <button type="button" id="pauseBtn" title="<?php echo translate('Pause') ?>" class="inactive" data-on-click="pauseClicked">
          <i class="material-icons md-18">pause</i>
          </button>
          <button type="button" id="playBtn" title="<?php echo translate('Play') ?>" class="active" disabled="disabled" data-on-click="playClicked">
          <i class="material-icons md-18">play_arrow</i>
          </button>
          <button type="button" id="slowFwdBtn" title="<?php echo translate('StepForward') ?>" class="unavail" disabled="disabled" data-on-click-true="streamSlowFwd">
          <i class="material-icons md-18">chevron_right</i>
          </button>
          <button type="button" id="fastFwdBtn" title="<?php echo translate('FastForward') ?>" class="inactive" data-on-click-true="streamFastFwd">
          <i class="material-icons md-18">fast_forward</i>
          </button>
          <button type="button" id="zoomOutBtn" title="<?php echo translate('ZoomOut') ?>" class="unavail" disabled="disabled" data-on-click="streamZoomOut">
          <i class="material-icons md-18">zoom_out</i>
          </button>
          <button type="button" id="nextBtn" title="<?php echo translate('Next') ?>" class="inactive" data-on-click-true="streamNext">
          <i class="material-icons md-18">skip_next</i>
          </button>
        </p>
        <div id="replayStatus">
          <span id="mode"><?php echo translate('Mode') ?>: <span id="modeValue">Replay</span></span>
          <span id="rate"><?php echo translate('Rate') ?>: 
<?php 
$rates = array( -800=>'-8x', -400=>'-4x', -200=>'-2x', -100=>'-1x', 0=>translate('Stop'), 100 => '1x', 200=>'2x', 400=>'4x', 800=>'8x' );
echo htmlSelect('rate', $rates, intval($rate), array('id'=>'rateValue'));
?>
<!--<span id="rateValue"><?php echo $rate/100 ?></span>x</span>-->
          <span id="progress"><?php echo translate('Progress') ?>: <span id="progressValue">0</span>s</span>
          <span id="zoom"><?php echo translate('Zoom') ?>: <span id="zoomValue">1</span>x</span>
        </div>
      </div><!--eventVideo-->
      <div id="eventStills" class="hidden">
        <div id="eventThumbsPanel">
          <div id="eventThumbs">
          </div>
        </div>
        <div id="eventImagePanel">
          <div id="eventImageFrame">
            <img id="eventImage" src="graphics/transparent.png" alt=""/>
            <div id="eventImageBar">
              <div id="eventImageClose"><button type="button" data-on-click="hideEventImage"><?php echo translate('Close') ?></button></div>
              <div id="eventImageStats" class="hidden"><button type="button" data-on-click="showFrameStats"><?php echo translate('Stats') ?></button></div>
              <div id="eventImageData"><?php echo translate('Frame') ?> <span id="eventImageNo"></span></div>
            </div>
          </div>
        </div>
        <div id="eventImageNav">
          <div id="thumbsSliderPanel">
            <div id="alarmCue" class="alarmCue"></div>
            <div id="thumbsSlider">
              <div id="thumbsKnob">
              </div>
            </div>
          </div>
          <div id="eventImageButtons">
            <div id="prevButtonsPanel">
              <button id="prevEventBtn" type="button" data-on-click="prevEvent" disabled="disabled">&lt;E</button>
              <button id="prevThumbsBtn" type="button" data-on-click="prevThumbs" disabled="disabled">&lt;&lt;</button>
              <button id="prevImageBtn" type="button" data-on-click="prevImage" disabled="disabled">&lt;</button>
              <button id="nextImageBtn" type="button" data-on-click="nextImage" disabled="disabled">&gt;</button>
              <button id="nextThumbsBtn" type="button" data-on-click="nextThumbs" disabled="disabled">&gt;&gt;</button>
              <button id="nextEventBtn" type="button" data-on-click="nextEvent" disabled="disabled">E&gt;</button>
            </div>
          </div>
        </div>
      </div>
<?php
} // end if Event exists
?>
    </div><!--content-->
  </div><!--page-->
</body>
</html>
