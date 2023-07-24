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

if (!canView('Events')) {
  $view = 'error';
  return;
}

require_once('includes/Event.php');
require_once('includes/Event_Data.php');
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
if (!validInt($scale) and $scale != '0') {
  $scale = '0';
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

$video_tag = ((false !== strpos($Event->DefaultVideo(), 'h264')) and ($codec == 'MP4' or $codec == 'auto'));

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
if (count($filter->terms())==1 and $filter->has_term('Id')) {
  # Special case, coming from filter specifying this exact event.
  $filter->terms([]);
  $filter->addTerm(['attr' => 'MonitorId', 'op' => '=', 'val' => $Event->MonitorId(), 'cnj' => 'and']);
}
$filterQuery = $filter->querystring();
$connkey = generateConnKey();

xhtmlHeaders(__FILE__, translate('Event').' '.$Event->Id());
getBodyTopHTML();
?>
  <div id="page">
    <?php echo getNavBarHTML() ?>
    <div id="content">
<?php 
if ( !$Event->Id() ) {
  echo '<div class="error">Event was not found.</div>';
}

if ( $Event->Id() and !file_exists($Event->Path()) )
  echo '<div class="error">Event was not found at '.$Event->Path().'.  It is unlikely that playback will be possible.</div>';
?>

<!-- BEGIN HEADER -->
    <div class="d-flex flex-row flex-wrap justify-content-between px-3 py-1">
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
        <a href="?view=montagereview&live=0&current=<?php echo urlencode($Event->StartDateTime()) ?>" class="btn btn-normal" title="<?php echo translate('Montage Review') ?>"><i class="material-icons md-18">grid_view</i></a>
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
<?php if ( $Event->Id() ) { ?>
<!-- BEGIN VIDEO CONTENT ROW -->
    <div id="inner-content">
      <div class="d-flex flex-row">
        <div class="eventStats">
          <!-- VIDEO STATISTICS TABLE -->
          <table id="eventStatsTable" class="table-sm table-borderless">
            <!-- EVENT STATISTICS POPULATED BY JAVASCRIPT -->
          </table>
<?php
if (defined('ZM_OPT_USE_GEOLOCATION') and ZM_OPT_USE_GEOLOCATION) {
?>
          <div id="LocationMap" style="height: 450px; width: 450px;"></div>
<?php
}
?>

      <div id="frames">
<?php 
if (file_exists($Event->Path().'/alarm.jpg')) {
  echo '
<a href="?view=image&eid='. $Event->Id().'&amp;fid=alarm">
  <img src="?view=image&eid='. $Event->Id().'&amp;fid=alarm&width='.ZM_WEB_LIST_THUMB_WIDTH.'" width="'.ZM_WEB_LIST_THUMB_WIDTH.'" alt="First alarmed frame" title="First alarmed frame"/>
</a>    
';
}
if (file_exists($Event->Path().'/snapshot.jpg')) {
  echo '
<a href="?view=image&eid='. $Event->Id().'&amp;fid=snapshot">
  <img src="?view=image&eid='. $Event->Id().'&amp;fid=snapshot&width='.ZM_WEB_LIST_THUMB_WIDTH.'" width="'.ZM_WEB_LIST_THUMB_WIDTH.'" alt="Frame with the most motion" title="Frame with the most motion"/>
</a>
';
}
if (file_exists($Event->Path().'/objdetect.jpg')) {
  echo '
<a href="?view=image&eid='. $Event->Id().'&amp;fid=objdetect">
  <img src="?view=image&eid='. $Event->Id().'&amp;fid=objdetect" width="'.ZM_WEB_LIST_THUMB_WIDTH.'" alt="Detected Objects" title="Detected Objects"/>
</a>
';
}
?>
      </div>
        </div>
        <div id="eventVideo">
        <!-- VIDEO CONTENT -->
          <div id="videoFeed">
<?php
if ($video_tag) {
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
        <div id="progressBar" style="width: 100%;">
          <div id="alarmCues" style="width: 100%;"></div>
          <div class="progressBox" id="progressBox" title="" style="width: 0%;"></div>
          <div id="indicator" style="display: none;"></div>
        </div><!--progressBar-->
<?php
} else {
if ( (ZM_WEB_STREAM_METHOD == 'mpeg') && ZM_MPEG_LIVE_FORMAT ) {
  $streamSrc = $Event->getStreamSrc(array('mode'=>'mpeg', 'scale'=>$scale, 'rate'=>$rate, 'bitrate'=>ZM_WEB_VIDEO_BITRATE, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'format'=>ZM_MPEG_REPLAY_FORMAT, 'replay'=>$replayMode),'&amp;');
  outputVideoStream('evtStream', $streamSrc, reScale( $Event->Width(), $scale ).'px', reScale( $Event->Height(), $scale ).'px', ZM_MPEG_LIVE_FORMAT );
} else {
  $streamSrc = $Event->getStreamSrc(array('mode'=>'jpeg', 'frame'=>$fid, 'scale'=>$scale, 'rate'=>$rate, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>$replayMode),'&amp;');
  if (!canStreamNative()) {
    echo '<div class="warning">We have detected an inability to stream natively.  Unfortunately we no longer support really ancient browsers.  Trying anyways.</div>';
  }
  outputImageStream('evtStream', $streamSrc,
    ($scale ? reScale($Event->Width(), $scale).'px' : '100%'),
    ($scale ? reScale($Event->Height(), $scale).'px' : 'auto'),
    validHtmlStr($Event->Name()));
} // end if stream method
?>
        <div id="progressBar" style="width: 100%;">
          <div id="alarmCues" style="width: 100%;"></div>
          <div class="progressBox" id="progressBox" title="" style="width: 0%;"></div>
          <div id="indicator" style="display: none;"></div>
        </div><!--progressBar-->
<?php
} /*end if !DefaultVideo*/
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
          <button type="button" id="playBtn" title="<?php echo translate('Play') ?>" class="active" disabled="disabled" data-on-click="playClicked">
          <i class="material-icons md-18">play_arrow</i>
          </button>
          <button type="button" id="slowFwdBtn" title="<?php echo translate('StepForward') ?>" class="unavail" disabled="disabled" data-on-click-true="streamSlowFwd">
          <i class="material-icons md-18">chevron_right</i>
          </button>
          <button type="button" id="fastFwdBtn" title="<?php echo translate('FastForward') ?>" class="inactive" data-on-click-true="streamFastFwd">
          <i class="material-icons md-18">fast_forward</i>
          </button>
          <button type="button" id="zoomOutBtn" title="<?php echo translate('ZoomOut') ?>" class="unavail" disabled="disabled" data-on-click="clickZoomOut">
          <i class="material-icons md-18">zoom_out</i>
          </button>
          <button type="button" id="fullscreenBtn" title="<?php echo translate('Fullscreen') ?>" class="avail" data-on-click="fullscreenClicked">
            <i class="material-icons md-18">fullscreen</i>
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
      <div id="EventData" class="EventData">
      <?php
        $data = ZM\Event_Data::find(['EventId'=>$Event->Id()]);
        if (count($data)) {
          echo '<table class="table table-striped table-hover table-condensed"><thead><tr><th>'.translate('Timestamp').'</th><th>'.translate('Data').'</th></tr></thead><tbody>'.PHP_EOL;
          foreach ($data as $d) {
            echo '<tr><td class="Timestamp">'.$d->Timestamp().'</td><td class="Data">'.strip_tags($d->Data()).'</td></tr>'.PHP_EOL;
          }
          echo '</tbody></table>';
        }
      ?>
      </div><!--EventData-->
</div>
<?php
} // end if Event exists
?>
    </div><!--inner-content-->
    </div><!--content-->
    
  </div><!--page-->
  <link href="skins/<?php echo $skin ?>/js/video-js.css" rel="stylesheet">
  <link href="skins/<?php echo $skin ?>/js/video-js-skin.css" rel="stylesheet">
  <script src="skins/<?php echo $skin ?>/js/video.js"></script>
  <script src="./js/videojs.zoomrotate.js"></script>
<?php
  echo output_link_if_exists(array('css/base/zones.css'));
  echo output_script_if_exists(array('js/leaflet/leaflet.js'), false);
  echo output_link_if_exists(array('js/leaflet/leaflet.css'), false);
  xhtmlFooter();
?>
