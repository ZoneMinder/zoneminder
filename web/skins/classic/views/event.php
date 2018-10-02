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

$eid = validInt( $_REQUEST['eid'] );
$fid = !empty($_REQUEST['fid'])?validInt($_REQUEST['fid']):1;

$Event = new Event( $eid );
if ( $user['MonitorIds'] ) {
  $monitor_ids = explode( ',', $user['MonitorIds'] );
  if ( count($monitor_ids) and ! in_array( $Event->MonitorId(), $monitor_ids ) ) {
    $view = 'error';
    return;
  }
}
$Monitor = $Event->Monitor();

if (isset($_REQUEST['rate'])) {
  $rate = validInt($_REQUEST['rate']);
} else {
  $rate = reScale(RATE_BASE, $Monitor->DefaultRate(), ZM_WEB_DEFAULT_RATE);
}

if (isset($_REQUEST['scale'])) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset( $_COOKIE['zmEventScaleAuto'] ) ) { //If we're using scale to fit use it on all monitors
  $scale = 'auto';
} else if ( isset( $_COOKIE['zmEventScale'.$Event->MonitorId()] ) ) {
  $scale = $_COOKIE['zmEventScale'.$Event->MonitorId()];
} else {
  $scale = reScale( SCALE_BASE, $Monitor->DefaultScale(), ZM_WEB_DEFAULT_SCALE );
}

$replayModes = array(
    'none'   => translate('None'),
    'single' => translate('ReplaySingle'),
    'all' => translate('ReplayAll'),
    'gapless' => translate('ReplayGapless'),
);

if ( isset( $_REQUEST['streamMode'] ) )
  $streamMode = validHtmlStr($_REQUEST['streamMode']);
else
  $streamMode = 'video';

$replayMode = '';
if ( isset( $_REQUEST['replayMode'] ) )
  $replayMode = validHtmlStr($_REQUEST['replayMode']);
if ( isset( $_COOKIE['replayMode']) && preg_match('#^[a-z]+$#', $_COOKIE['replayMode']) )
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

parseSort();
parseFilter( $_REQUEST['filter'] );
$filterQuery = $_REQUEST['filter']['query'];

$connkey = generateConnKey();

$focusWindow = true;

$popup = ((isset($_REQUEST['popup'])) && ($_REQUEST['popup'] = 1));

xhtmlHeaders(__FILE__, translate('Event') );
?>
<body>
  <div id="page">
    <?php if ( !$popup ) echo getNavBarHTML() ?>
    <div id="header">
<?php 
if ( ! $Event->Id() ) {
  echo 'Event was not found.';
} else {
?>
      <div id="dataBar">
        <span id="dataId" title="<?php echo translate('Id') ?>"><?php echo $Event->Id() ?></span>
        <span id="dataMonitor" title="<?php echo translate('Monitor') ?>"><?php echo $Monitor->Id() . ' ' . $Monitor->Name() ?></span>
        <span id="dataCause" title="<?php echo $Event->Notes()?validHtmlStr($Event->Notes()):translate('AttrCause') ?>"><?php echo validHtmlStr($Event->Cause()) ?></span>
        <span id="dataTime" title="<?php echo translate('Time') ?>"><?php echo strftime( STRF_FMT_DATETIME_SHORT, strtotime($Event->StartTime() ) ) ?></span>
        <span id="dataDuration" title="<?php echo translate('Duration') ?>"><?php echo $Event->Length().'s' ?></span>
        <span id="dataFrames" title="<?php echo translate('AttrFrames')."/".translate('AttrAlarmFrames') ?>"><?php echo $Event->Frames() ?>/<?php echo $Event->AlarmFrames() ?></span>
        <span id="dataScore" title="<?php echo translate('AttrTotalScore')."/".translate('AttrAvgScore')."/".translate('AttrMaxScore') ?>"><?php echo $Event->TotScore() ?>/<?php echo $Event->AvgScore() ?>/<?php echo $Event->MaxScore() ?></span>
        <span id="Storage"> <?php echo human_filesize($Event->DiskSpace(null)) . ' on ' . $Event->Storage()->Name() ?></span>
        <div id="closeWindow"><a href="#" onclick="<?php echo $popup ? 'window.close()' : 'window.history.back();return false;' ?>"><?php echo $popup ? translate('Close') : translate('Back') ?></a></div>
      </div>
      <div id="menuBar1">
        <div id="nameControl">
          <input type="text" id="eventName" name="eventName" value="<?php echo validHtmlStr($Event->Name()) ?>" />
          <button value="Rename" onclick="renameEvent()"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>>
<?php echo translate('Rename') ?></button>
        </div>
<?php
if ( canEdit('Events') ) {
?>
        <div id="deleteEvent"><button onclick="deleteEvent()"><?php echo translate('Delete') ?></button></div>
        <div id="editEvent"><button onclick="editEvent()"><?php echo translate('Edit') ?></button></div>
        <div id="archiveEvent"<?php echo $Event->Archived == 1 ? ' class="hidden"' : ''  ?>><button onclick="archiveEvent()"><?php echo translate('Archive') ?></button></div>
        <div id="unarchiveEvent"<?php echo $Event->Archived == 0 ? ' class="hidden"' : '' ?>><button onclick="unarchiveEvent()"><?php echo translate('Unarchive') ?></button></div>
<?php
} // end if can edit Events
?>
        <div id="framesEvent"><button onclick="showEventFrames()"><?php echo translate('Frames') ?></button></div>
        <div id="streamEvent" class="hidden"><button onclick="showStream()"><?php echo translate('Stream') ?></button></div>
        <div id="stillsEvent"><button onclick="showStills()"><?php echo translate('Stills') ?></button></div>
<?php
  if ( $Event->DefaultVideo() ) { 
?>
        <div id="downloadEventFile"><a class="btn-primary" href="<?php echo $Event->getStreamSrc(array('mode'=>'mp4'))?>" download>Download MP4</a></div>
<?php
  } else {
?>
        <div id="videoEvent"><button onclick="videoEvent();"><?php echo translate('Video') ?></button></div>
<?php
  } // end if Event->DefaultVideo
?>
        <div id="exportEvent"><button onclick="exportEvent();"><?php echo translate('Export') ?></button></div>
        <div id="replayControl"><label for="replayMode"><?php echo translate('Replay') ?></label><?php echo buildSelect( "replayMode", $replayModes, "changeReplayMode();" ); ?></div>
        <div id="scaleControl"><label for="scale"><?php echo translate('Scale') ?></label><?php echo buildSelect( "scale", $scales, "changeScale();" ); ?></div>
      </div>
     </div>
    <div id="content">
      <div id="eventVideo" class="">
<?php
if ( $Event->DefaultVideo() ) {
?>
        <div id="videoFeed">
          <video id="videoobj" class="video-js vjs-default-skin" style="transform: matrix(1, 0, 0, 1, 0, 0)" width="<?php echo reScale( $Event->Width(), $scale ) ?>" height="<?php echo reScale( $Event->Height(), $scale ) ?>" data-setup='{ "controls": true, "autoplay": true, "preload": "auto", "plugins": { "zoomrotate": { "zoom": "<?php echo $Zoom ?>"}}}'>
          <source src="<?php echo $Event->getStreamSrc(array('mode'=>'mpeg','format'=>'h264')); ?>" type="video/mp4">
          <track id="monitorCaption" kind="captions" label="English" srclang="en" src='data:plain/text;charset=utf-8,"WEBVTT\n\n 00:00:00.000 --> 00:00:01.000 ZoneMinder"' default>
          Your browser does not support the video tag.
          </video>
        </div><!--videoFeed-->
<?php
}  // end if DefaultVideo
?>
<?php if (!$Event->DefaultVideo()) { ?>
      <div id="imageFeed">
<?php
if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT ) {
  $streamSrc = $Event->getStreamSrc(array('mode'=>'mpeg', 'scale'=>$scale, 'rate'=>$rate, 'bitrate'=>ZM_WEB_VIDEO_BITRATE, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'format'=>ZM_MPEG_REPLAY_FORMAT, 'replay'=>$replayMode));
  outputVideoStream( "evtStream", $streamSrc, reScale( $Event->Width(), $scale ), reScale( $Event->Height(), $scale ), ZM_MPEG_LIVE_FORMAT );
} else {
  $streamSrc = $Event->getStreamSrc( array( 'mode'=>'jpeg', 'frame'=>$fid, 'scale'=>$scale, 'rate'=>$rate, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>$replayMode) );
  if ( canStreamNative() ) {
    outputImageStream( 'evtStream', $streamSrc, reScale( $Event->Width(), $scale ), reScale( $Event->Height(), $scale ), validHtmlStr($Event->Name()) );
  } else {
    outputHelperStream( 'evtStream', $streamSrc, reScale( $Event->Width(), $scale ), reScale( $Event->Height(), $scale ) );
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
          <input type="button" value="&lt;+" id="prevBtn" title="<?php echo translate('Prev') ?>" class="inactive" onclick="streamPrev( true );"/>
          <input type="button" value="&lt;&lt;" id="fastRevBtn" title="<?php echo translate('Rewind') ?>" class="inactive" onclick="streamFastRev( true );"/>
          <input type="button" value="&lt;" id="slowRevBtn" title="<?php echo translate('StepBack') ?>" class="unavail" disabled="disabled" onclick="streamSlowRev( true );"/>
          <input type="button" value="||" id="pauseBtn" title="<?php echo translate('Pause') ?>" class="inactive" onclick="pauseClicked();"/>
          <input type="button" value="|>" id="playBtn" title="<?php echo translate('Play') ?>" class="active" disabled="disabled" onclick="playClicked();"/>
          <input type="button" value="&gt;" id="slowFwdBtn" title="<?php echo translate('StepForward') ?>" class="unavail" disabled="disabled" onclick="streamSlowFwd( true );"/>
          <input type="button" value="&gt;&gt;" id="fastFwdBtn" title="<?php echo translate('FastForward') ?>" class="inactive" onclick="streamFastFwd( true );"/>
          <input type="button" value="&ndash;" id="zoomOutBtn" title="<?php echo translate('ZoomOut') ?>" class="unavail" disabled="disabled" onclick="streamZoomOut();"/>
          <input type="button" value="+&gt;" id="nextBtn" title="<?php echo translate('Next') ?>" class="inactive" onclick="streamNext( true );"/>
        </p>
        <div id="replayStatus">
          <span id="mode"><?php echo translate('Mode') ?>: <span id="modeValue">Replay</span></span>
          <span id="rate"><?php echo translate('Rate') ?>: <span id="rateValue"><?php echo $rate/100 ?></span>x</span>
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
              <div id="eventImageClose"><input type="button" value="<?php echo translate('Close') ?>" onclick="hideEventImage()"/></div>
              <div id="eventImageStats" class="hidden"><input type="button" value="<?php echo translate('Stats') ?>" onclick="showFrameStats()"/></div>
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
              <input id="prevEventBtn" type="button" value="&lt;E" onclick="prevEvent()" disabled="disabled"/>
              <input id="prevThumbsBtn" type="button" value="&lt;&lt;" onclick="prevThumbs()" disabled="disabled"/>
              <input id="prevImageBtn" type="button" value="&lt;" onclick="prevImage()" disabled="disabled"/>
              <input id="nextImageBtn" type="button" value="&gt;" onclick="nextImage()" disabled="disabled"/>
              <input id="nextThumbsBtn" type="button" value="&gt;&gt;" onclick="nextThumbs()" disabled="disabled"/>
              <input id="nextEventBtn" type="button" value="E&gt;" onclick="nextEvent()" disabled="disabled"/>
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
