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

require_once('includes/Event.php');
require_once('includes/Filter.php');
require_once('includes/Zone.php');

$eid = validInt($_REQUEST['eid']);
$fid = !empty($_REQUEST['fid']) ? validInt($_REQUEST['fid']) : 1;

$Event = new ZM\Event($eid);
$monitor = $Event->Monitor();

if (!$monitor->canView()) {
  $view = 'error';
  return;
}

zm_session_start();
if (isset($_REQUEST['rate'])) {
  $rate = validInt($_REQUEST['rate']);
} else if (isset($_COOKIE['zmEventRate'])) {
  $rate = validInt($_COOKIE['zmEventRate']);
} else {
  $rate = reScale(RATE_BASE, $monitor->DefaultRate(), ZM_WEB_DEFAULT_RATE);
}
if ($rate > 1600) {
  $rate = 1600;
  zm_setcookie('zmEventRate', $rate);
}

if (isset($_REQUEST['scale'])) {
  $scale = validInt($_REQUEST['scale']);
} else if (isset($_COOKIE['zmEventScale'.$Event->MonitorId()])) {
  $scale = $_COOKIE['zmEventScale'.$Event->MonitorId()];
} else {
  $scale = $monitor->DefaultScale();
}

$showZones = false;
if (isset($_REQUEST['showZones'])) {
  $showZones = $_REQUEST['showZones'] == 1;
  $_SESSION['zmEventShowZones'.$monitor->Id()] = $showZones;
} else if (isset($_COOKIE['zmEventShowZones'.$monitor->Id()])) {
  $showZones = $_COOKIE['zmEventShowZones'.$monitor->Id()] == 1;
} else if (isset($_SESSION['zmEventShowZones'.$monitor->Id()]) ) {
  $showZones = $_SESSION['zmEventShowZones'.$monitor->Id()];
}

$codec = 'auto';
if (isset($_REQUEST['codec'])) {
  $codec = $_REQUEST['codec'];
  $_SESSION['zmEventCodec'.$Event->MonitorId()] = $codec;
} else if ( isset($_SESSION['zmEventCodec'.$Event->MonitorId()]) ) {
  $codec = $_SESSION['zmEventCodec'.$Event->MonitorId()];
} else {
  $codec = $monitor->DefaultCodec();
}
session_write_close();

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

if (isset($_REQUEST['streamMode']))
  $streamMode = validHtmlStr($_REQUEST['streamMode']);
else
  $streamMode = 'video';

$replayMode = '';
if (isset($_REQUEST['replayMode']))
  $replayMode = validHtmlStr($_REQUEST['replayMode']);
if (isset($_COOKIE['replayMode']) && preg_match('#^[a-z]+$#', $_COOKIE['replayMode']))
  $replayMode = validHtmlStr($_COOKIE['replayMode']);

if ((!$replayMode) or !$replayModes[$replayMode]) {
  $replayMode = 'none';
}

$player = 'mjpeg';
if ( $Event->DefaultVideo() and ( $codec == 'MP4' or $codec == 'auto' ) ) {
  if (strpos($Event->DefaultVideo(), 'h265') or strpos($Event->DefaultVideo(), 'hevc')) {
    $player = 'h265web.js';
  } else {
    $player = 'video.js';
  }
}
// videojs zoomrotate only when direct recording
$Zoom = 1;
$Rotation = 0;
if ($monitor->VideoWriter() == '2') {
# Passthrough
  $Rotation = $Event->Orientation();
  if (in_array($Event->Orientation(),array('90','270')))
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
$filter = ZM\Filter::parse($_REQUEST['filter']);
$filterQuery = $filter->querystring();

$connkey = generateConnKey();

xhtmlHeaders(__FILE__, translate('Event').' '.$Event->Id());
?>
<body>
  <div id="page">
<?php
echo getNavBarHTML();
if (!$Event->Id())
  echo '<div class="error">Event was not found.</div>';
else if (!file_exists($Event->Path()))
  echo '<div class="error">Event was not found at '.$Event->Path().'.  It is unlikely that playback will be possible.</div>';
?>
<!-- BEGIN HEADER -->
    <div class="d-flex flex-row justify-content-between px-3 py-1">
      <div id="toolbar" >
        <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
<?php if ($Event->Id()) { ?>
        <button id="renameBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Rename') ?>" disabled><i class="fa fa-font"></i></button>
        <button id="archiveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Archive') ?>" disabled><i class="fa fa-archive"></i></button>
        <button id="unarchiveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Unarchive') ?>" disabled><i class="fa fa-file-archive-o"></i></button>
        <button id="editBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Edit') ?>" disabled><i class="fa fa-pencil"></i></button>
        <button id="exportBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Export') ?>"><i class="fa fa-external-link"></i></button>
        <a id="downloadBtn" class="btn btn-normal" href="<?php echo $Event->getStreamSrc(array('mode'=>'mp4'),'&amp;')?>"
          title="<?php echo translate('Download'). ' ' . $Event->DefaultVideo() ?>"
          download
          <?php echo $Event->DefaultVideo() ? '' : 'style="display:none;"' ?>
><i class="fa fa-download"></i></a>
        <button id="videoBtn" class="btn btn-normal" data-toggle="tooltip" data-toggle="tooltip" data-placement="top" title="<?php echo translate('GenerateVideo') ?>"><i class="fa fa-file-video-o"></i></button>
        <button id="statsBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Stats') ?>" ><i class="fa fa-info"></i></button>
        <button id="framesBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Frames') ?>" ><i class="fa fa-picture-o"></i></button>
        <button id="deleteBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>"><i class="fa fa-trash"></i></button>
<?php
  if (canView('System')) { ?>
    <button id="toggleZonesButton" class="btn btn-<?php echo $showZones?'normal':'secondary'?>" title="<?php echo translate(($showZones?'Hide':'Show').' Zones')?>" ><span class="material-icons"><?php echo $showZones?'layers_clear':'layers'?></span</button>
<?php
  }
  } // end if Event->Id
?>
      </div>
      
      <h2><?php echo translate('Event').' '.$Event->Id() ?></h2>
      
      <div class="d-flex flex-row">
        <div id="replayControl">
          <label for="replayMode"><?php echo translate('Replay') ?></label>
          <?php echo htmlSelect('replayMode', $replayModes, $replayMode, array('data-on-change'=>'changeReplayMode','id'=>'replayMode')); ?>
        </div>
        <div id="scaleControl">
          <label for="scale"><?php echo translate('Scale') ?></label>
          <?php echo htmlSelect('scale', $scales, $scale, array('data-on-change'=>'changeScale','id'=>'scale')); ?>
        </div>
        <div id="codecControl">
          <label for="codec"><?php echo translate('Codec') ?></label>
          <?php echo htmlSelect('codec', $codecs, $codec, array('data-on-change'=>'changeCodec','id'=>'codec')); ?>
        </div>
      </div>
    </div>
<?php if ($Event->Id()) { ?>
<!-- BEGIN VIDEO CONTENT ROW -->
    <div id="content" class="d-flex flex-row justify-content-center">
      <div id="eventStats">
        <!-- VIDEO STATISTICS TABLE -->
        <table id="eventStatsTable" class="table-sm table-borderless">
          <!-- EVENT STATISTICS POPULATED BY JAVASCRIPT -->
        </table>
      </div>
      <div id="eventVideo">
      <!-- VIDEO CONTENT -->
        <div id="videoFeed">
<?php
if ($player == 'video.js') {
?>
          <video autoplay id="videoobj" class="video-js vjs-default-skin"
            style="transform: matrix(1, 0, 0, 1, 0, 0);"
           <?php echo $scale ? 'width="'.reScale($Event->Width(), $scale).'"' : '' ?>
           <?php echo $scale ? 'height="'.reScale($Event->Height(), $scale).'"' : '' ?>
            data-setup='{ "controls": true, "autoplay": true, "preload": "auto", "playbackRates": [ <?php echo implode(',',
              array_map(function($r){return $r/100;},
                array_filter(
                  array_keys($rates),
                  function($r){return $r >= 0 ? true : false;}
                ))) ?>], "plugins": { "zoomrotate": { "zoom": "<?php echo $Zoom ?>"}}}'
          >
          <source src="<?php echo $Event->getStreamSrc(array('mode'=>'mpeg','format'=>'h264'),'&amp;'); ?>" type="video/mp4">
          <track id="monitorCaption" kind="captions" label="English" srclang="en" src='data:plain/text;charset=utf-8,"WEBVTT\n\n 00:00:00.000 --> 00:00:01.000 ZoneMinder"' default/>
          Your browser does not support the video tag.
          </video>
<?php
} else if ($player == 'mjpeg') {
?>
      <div id="imageFeed">
<?php
if ( (ZM_WEB_STREAM_METHOD == 'mpeg') && ZM_MPEG_LIVE_FORMAT ) {
  $streamSrc = $Event->getStreamSrc(array('mode'=>'mpeg', 'scale'=>$scale, 'rate'=>$rate, 'bitrate'=>ZM_WEB_VIDEO_BITRATE, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'format'=>ZM_MPEG_REPLAY_FORMAT, 'replay'=>$replayMode),'&amp;');
  outputVideoStream('evtStream', $streamSrc, reScale( $Event->Width(), $scale ).'px', reScale( $Event->Height(), $scale ).'px', ZM_MPEG_LIVE_FORMAT );
} else {
  $streamSrc = $Event->getStreamSrc(array('mode'=>'jpeg', 'frame'=>$fid, 'scale'=>$scale, 'rate'=>$rate, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>$replayMode),'&amp;');
  if ( canStreamNative() ) {
    outputImageStream('evtStream', $streamSrc, '100%', '100%', validHtmlStr($Event->Name()));
  } else {
    outputHelperStream('evtStream', $streamSrc, '100%', '100%');
  }
} // end if stream method
?>
        <div id="alarmCue" class="alarmCue"></div>
        <div id="progressBar" style="width: <?php echo reScale($Event->Width(), $scale);?>px;">
          <div class="progressBox" id="progressBox" title="" style="width: 0%;"></div>
        </div><!--progressBar-->
<?php
} else if ($player == 'h265web.js') {
?>
      <div id="player-container">
        <div id="glplayer" class="glplayer"></div>
        <div id="controller" class="controller">
          <div id="progress-contaniner" class="progress-common progress-contaniner">
            <div id="cachePts" class="progress-common cachePts"></div>
            <div id="progressPts" class="progress-common progressPts"></div>
          </div>

          <div id="operate-container" class="operate-container">

              <span id="ptsLabel" class="ptsLabel">00:00:00/00:00:00</span>
              <div class="voice-div">
                  <span>
                      <a id="muteBtn" class="muteBtn">
                          <svg class="icon" style="width: 1em;height: 1em;vertical-align: middle;fill: currentColor;overflow: hidden;" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="488">
                              <path d="M153.6 665.6V358.4h204.8V256H153.6c-56.32 0-102.4 46.08-102.4 102.4v307.2c0 56.32 46.08 102.4 102.4 102.4h204.8v-102.4H153.6zM358.4 256v102.4l204.8-128v563.2L358.4 665.6v102.4l307.2 204.8V51.2zM768 261.12v107.52c61.44 20.48 102.4 76.8 102.4 143.36s-40.96 122.88-102.4 143.36v107.52c117.76-25.6 204.8-128 204.8-250.88s-87.04-225.28-204.8-250.88z" p-id="489">
                              </path>
                          </svg>
                      </a>
                  </span>
                  <progress id="progressVoice" class="progressVoice" value="50" max="100"></progress>
              </div>

              <a id="fullScreenBtn" class="fullScreenBtn">
                  <svg class="icon" style="width: 1em;height: 1em;vertical-align: middle;fill: currentColor;overflow: hidden;" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="403">
                      <path d="M167.8 903.1c-11.5 0-22.9-4.4-31.7-13.1-17.5-17.5-17.5-45.8 0-63.3l221.1-221.1c17.5-17.5 45.9-17.5 63.3 0 17.5 17.5 17.5 45.8 0 63.3L199.4 890c-8.7 8.7-20.2 13.1-31.6 13.1zM638.5 432.4c-11.5 0-22.9-4.4-31.7-13.1-17.5-17.5-17.5-45.8 0-63.3l221.7-221.7c17.5-17.5 45.8-17.5 63.3 0s17.5 45.8 0 63.3L670.1 419.3c-8.7 8.7-20.2 13.1-31.6 13.1zM859.7 903.8c-11.5 0-23-4.4-31.7-13.1L606.7 668.8c-17.5-17.5-17.4-45.9 0.1-63.4s45.9-17.4 63.3 0.1l221.4 221.9c17.5 17.5 17.4 45.9-0.1 63.4-8.8 8.7-20.2 13-31.7 13zM389 432.1c-11.5 0-23-4.4-31.7-13.1L136.1 197.2c-17.5-17.5-17.4-45.9 0.1-63.4s45.9-17.4 63.3 0.1l221.2 221.7c17.5 17.5 17.4 45.9-0.1 63.4-8.7 8.7-20.2 13.1-31.6 13.1z" fill="#ffffff" p-id="404">
                      </path>
                      <path d="M145 370c-24.7 0-44.8-20.1-44.8-44.8V221.8C100.2 153.5 155.7 98 224 98h103.4c24.7 0 44.8 20.1 44.8 44.8s-20.1 44.8-44.8 44.8H224c-18.9 0-34.2 15.3-34.2 34.2v103.4c0 24.7-20.1 44.8-44.8 44.8zM883.3 370c-24.7 0-44.8-20.1-44.8-44.8V221.8c0-18.9-15.3-34.2-34.2-34.2H700.8c-24.7 0-44.8-20.1-44.8-44.8S676.1 98 700.8 98h103.5c68.2 0 123.8 55.5 123.8 123.8v103.5c0 24.7-20.1 44.7-44.8 44.7zM327.5 926.6H224c-68.2 0-123.8-55.5-123.8-123.8V699.4c0-24.7 20.1-44.8 44.8-44.8s44.8 20.1 44.8 44.8v103.5c0 18.9 15.3 34.2 34.2 34.2h103.5c24.7 0 44.8 20.1 44.8 44.8s-20.1 44.7-44.8 44.7zM804.3 926.6H700.8c-24.7 0-44.8-20.1-44.8-44.8s20.1-44.8 44.8-44.8h103.5c18.9 0 34.2-15.4 34.2-34.2V699.4c0-24.7 20.1-44.8 44.8-44.8 24.7 0 44.8 20.1 44.8 44.8v103.5c0 68.2-55.6 123.7-123.8 123.7z" fill="#ffffff" p-id="405">
                      </path>
                  </svg>
              </a>
              <span id="showLabel" class="showLabel"></span>
            </div>
          </div>
       </div> <!-- end player container -->
<br/>
<?php
} /*end if player */
?>
<svg class="zones" id="zones<?php echo $monitor->Id() ?>" style="display:<?php echo $showZones ? 'block' : 'none'; ?>" viewBox="0 0 <?php echo $monitor->ViewWidth().' '.$monitor->ViewHeight() ?>" preserveAspectRatio="none">
<?php
    foreach (ZM\Zone::find(array('MonitorId'=>$monitor->Id()), array('order'=>'Area DESC')) as $zone) {
      echo $zone->svg_polygon();
    } // end foreach zone
?>
  Sorry, your browser does not support inline SVG
</svg>
        </div><!--videoFeed-->
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
          <button type="button" id="playBtn" title="<?php echo translate('Play') ?>" class="active" data-on-click="playClicked">
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
  #rates are defined in skins/classic/includes/config.php
  echo htmlSelect('rate', $rates, intval($rate), array('id'=>'rateValue'));
?>
          <span id="progress"><?php echo translate('Progress') ?>: <span id="progressValue">0</span>s</span>
          <span id="zoom"><?php echo translate('Zoom') ?>: <span id="zoomValue">1</span>x</span>
        </div>
      </div><!--eventVideo-->
<?php
} // end if Event exists
?>
  </div>
    </div><!--content-->
    
  </div><!--page-->
<?php
if ($player == 'video.js') {
?>
  <link href="skins/<?php echo $skin ?>/js/video-js.css" rel="stylesheet">
  <link href="skins/<?php echo $skin ?>/js/video-js-skin.css" rel="stylesheet">
  <script src="skins/<?php echo $skin ?>/js/video.js"></script>
  <script src="./js/videojs.zoomrotate.js"></script>
<?php 
} else if ($player == 'h265web.js') {
?>
  <script src="skins/<?php echo $skin ?>/js/h265web.js/missile.js"></script>
  <script src="skins/<?php echo $skin ?>/js/h265web.js/h265webjs-v20211026.js"></script>
<?php
}
  echo output_link_if_exists(array('css/base/zones.css'));
  xhtmlFooter();
?>
