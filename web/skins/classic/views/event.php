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

if (isset($_REQUEST['rate'])) {
  $rate = validInt($_REQUEST['rate']);
} else if (isset($_COOKIE['zmEventRate'])) {
  $rate = validInt($_COOKIE['zmEventRate']);
} else {
  $rate = reScale(RATE_BASE, $Monitor->DefaultRate(), ZM_WEB_DEFAULT_RATE);
}

if ( isset($_REQUEST['scale']) ) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmEventScale'.$Event->MonitorId()]) ) {
  $scale = $_COOKIE['zmEventScale'.$Event->MonitorId()];
} else {
  $scale = $Monitor->DefaultScale();
}

$codec = 'auto';
if ( isset($_REQUEST['codec']) ) {
  $codec = $_REQUEST['codec'];
  zm_session_start();
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

if ( ( !$replayMode ) or ( !$replayModes[$replayMode] ) ) {
  $replayMode = 'none';
}

$video_tag = false;
if ( $Event->DefaultVideo() and ( $codec == 'MP4' or $codec == 'auto' ) ) {
  $video_tag = true;
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
$filter = ZM\Filter::parse($_REQUEST['filter']);
$filterQuery = $filter->querystring();

$connkey = generateConnKey();

xhtmlHeaders(__FILE__, translate('Event').' '.$Event->Id());
?>
<body>
  <div id="page">
    <?php echo getNavBarHTML() ?>
<?php 
if ( !$Event->Id() ) {
  echo '<div class="error">Event was not found.</div>';
}

if ( $Event->Id() and !file_exists($Event->Path()) )
  echo '<div class="error">Event was not found at '.$Event->Path().'.  It is unlikely that playback will be possible.</div>';
?>

<!-- BEGIN HEADER -->
    <div class="d-flex flex-row justify-content-between px-3 py-1">
      <div id="toolbar" >
        <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
<?php if ( $Event->Id() ) { ?>
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
        <a href="?view=montagereview&live=0&current=<?php echo urlencode($Event->StartDateTime()) ?>" class="btn btn-normal" title="<?php echo translate('Montage Review') ?>"><i class="material-icons md-18">grid_view</i></a>
<?php } // end if Event->Id ?>
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
<?php if ( $Event->Id() ) { ?>
<!-- BEGIN VIDEO CONTENT ROW -->
    <div id="content" class="d-flex flex-row justify-content-center">
      <div class="">
        <!-- VIDEO STATISTICS TABLE -->
        <table id="eventStatsTable" class="table-sm table-borderless">
          <!-- EVENT STATISTICS POPULATED BY JAVASCRIPT -->
        </table>
      </div>
      <div class="">
      <div id="eventVideo">
      <!-- VIDEO CONTENT -->
<?php
if ( $video_tag ) {
?>
        <div id="videoFeed">
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
<?php
} /*end if !DefaultVideo*/
?>
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
<?php xhtmlFooter() ?>
