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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

if ( !canView( 'Events' ) ) {
    $view = 'error';
    return;
}

$eid = validInt( $_REQUEST['eid'] );
$fid = !empty($_REQUEST['fid'])?validInt($_REQUEST['fid']):1;

$Event = new Event( $eid );
$Monitor = $Event->Monitor();
if ( $user['MonitorIds'] ) {
  $monitor_ids = explode( ',', $user['MonitorIds'] );
  if ( count($monitor_ids) and ! in_array( $Event->MonitorId(), $monitors_ids ) ) {
    $view = 'error';
    return;
  }
}

if ( isset( $_REQUEST['rate'] ) )
    $rate = validInt($_REQUEST['rate']);
else
    $rate = reScale( RATE_BASE, $Event->DefaultRate(), ZM_WEB_DEFAULT_RATE );

if ( isset( $_REQUEST['scale'] ) ) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset( $_COOKIE['zmWatchScale'.$Event->MonitorId()] ) ) {
  $scale = $_COOKIE['zmEventScale'.$Event->MonitorId()];
} else {
  $scale = reScale( SCALE_BASE, $Event->DefaultScale(), ZM_WEB_DEFAULT_SCALE );
}

$replayModes = array(
    'single' => translate('ReplaySingle'),
    'all' => translate('ReplayAll'),
    'gapless' => translate('ReplayGapless'),
);

if ( isset( $_REQUEST['streamMode'] ) )
    $streamMode = validHtmlStr($_REQUEST['streamMode']);
else
    $streamMode = 'video';

if ( isset( $_REQUEST['replayMode'] ) )
    $replayMode = validHtmlStr($_REQUEST['replayMode']);
if ( isset( $_COOKIE['replayMode']) && preg_match('#^[a-z]+$#', $_COOKIE['replayMode']) )
    $replayMode = validHtmlStr($_COOKIE['replayMode']);
else {
	$keys = array_keys( $replayModes );
	$replayMode = array_shift( $keys );
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

$panelSections = 40;
$panelSectionWidth = (int)ceil(reScale($Event->Width(),$scale)/$panelSections);
$panelWidth = ($panelSections*$panelSectionWidth-1);

$connkey = generateConnKey();

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Event') );
?>
<body>
  <div id="page">
    <div id="content">
      <div id="dataBar">
        <table id="dataTable" class="major" cellspacing="0">
          <tr>
            <td><span id="dataId" title="<?php echo translate('Id') ?>"><?php echo $Event->Id() ?></span></td>
            <td><span id="dataCause" title="<?php echo $Event->Notes()?validHtmlStr($Event->Notes()):translate('AttrCause') ?>"><?php echo validHtmlStr($Event->Cause()) ?></span></td>
            <td><span id="dataTime" title="<?php echo translate('Time') ?>"><?php echo strftime( STRF_FMT_DATETIME_SHORT, strtotime($Event->StartTime() ) ) ?></span></td>
            <td><span id="dataDuration" title="<?php echo translate('Duration') ?>"><?php echo $Event->Length() ?></span>s</td>
            <td><span id="dataFrames" title="<?php echo translate('AttrFrames')."/".translate('AttrAlarmFrames') ?>"><?php echo $Event->Frames() ?>/<?php echo $Event->AlarmFrames() ?></span></td>
            <td><span id="dataScore" title="<?php echo translate('AttrTotalScore')."/".translate('AttrAvgScore')."/".translate('AttrMaxScore') ?>"><?php echo $Event->TotScore() ?>/<?php echo $Event->AvgScore() ?>/<?php echo $Event->MaxScore() ?></span></td>
          </tr>
        </table>
      </div>
      <div id="menuBar1">
        <div id="scaleControl"><label for="scale"><?php echo translate('Scale') ?></label><?php echo buildSelect( "scale", $scales, "changeScale();" ); ?></div>
        <div id="replayControl"><label for="replayMode"><?php echo translate('Replay') ?></label><?php echo buildSelect( "replayMode", $replayModes, "changeReplayMode();" ); ?></div>
        <div id="nameControl"><input type="text" id="eventName" name="eventName" value="<?php echo validHtmlStr($Event->Name()) ?>" size="16"/><input type="button" value="<?php echo translate('Rename') ?>" onclick="renameEvent()"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/></div>
      </div>
      <div id="menuBar2">
        <div id="closeWindow"><a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a></div>
<?php
if ( canEdit( 'Events' ) ) {
?>
        <div id="deleteEvent"><a href="#" onclick="deleteEvent()"><?php echo translate('Delete') ?></a></div>
        <div id="editEvent"><a href="#" onclick="editEvent()"><?php echo translate('Edit') ?></a></div>
        <div id="archiveEvent" class="hidden"><a href="#" onclick="archiveEvent()"><?php echo translate('Archive') ?></a></div>
        <div id="unarchiveEvent" class="hidden"><a href="#" onclick="unarchiveEvent()"><?php echo translate('Unarchive') ?></a></div>
<?php
} // end if can edit Events
if ( canView( 'Events' ) ) {
?>
        <div id="framesEvent"><a href="#" onclick="showEventFrames()"><?php echo translate('Frames') ?></a></div>
<?php
if ( $Event->SaveJPEGs() & 3 ) { // Analysis or Jpegs
?>
        <div id="stillsEvent"<?php if ( $streamMode == 'still' ) { ?> class="hidden"<?php } ?>><a href="#" onclick="showStills()"><?php echo translate('Stills') ?></a></div>
<?php
}
?>
        <div id="videoEvent"<?php if ( $streamMode == 'video' ) { ?> class="hidden"<?php } ?>><a href="#" onclick="showVideo()"><?php echo translate('Video') ?></a></div>
        <div id="exportEvent"><a href="#" onclick="exportEvent()"><?php echo translate('Export')  ?></a></div>
      </div>
      <div id="eventVideo" class="">
<?php
if ( $Event->DefaultVideo() ) {
?>
        <div id="videoFeed">
          <video id="videoobj" class="video-js vjs-default-skin" width="<?php echo reScale( $Event->Width(), $scale ) ?>" height="<?php echo reScale( $Event->Height(), $scale ) ?>" data-setup='{ "controls": true, "playbackRates": [0.5, 1, 1.5, 2, 4, 8, 16, 32, 64, 128, 256], "autoplay": true, "preload": "auto", "plugins": { "zoomrotate": { "rotate": "<?php echo $Rotation ?>", "zoom": "<?php echo $Zoom ?>"}}}'>
          <source src="<?php echo getEventDefaultVideoPath($Event) ?>" type="video/mp4">
          Your browser does not support the video tag.
          </video>
        </div>
<!--script>includeVideoJs();</script-->
<link href="//vjs.zencdn.net/4.11/video-js.css" rel="stylesheet">
<script src="//vjs.zencdn.net/4.11/video.js"></script>
<script src="./js/videojs.zoomrotate.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.min.js"></script>
<script>
var LabelFormat = "<?php echo validJsStr($Event->LabelFormat())?>";
var monitorName = "<?php echo validJsStr($Event->MonitorName())?>";
var duration = <?php echo $Event->Length() ?>, startTime = '<?php echo $Event->StartTime() ?>';

addVideoTimingTrack(document.getElementById('videoobj'), LabelFormat, monitorName, duration, startTime);
</script>

<?php
} else {
?>
        <div id="imageFeed">
<?php
if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT ) {
    $streamSrc = getStreamSrc( array( "source=event", "mode=mpeg", "event=".$eid, "frame=".$fid, "scale=".$scale, "rate=".$rate, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_REPLAY_FORMAT, "replay=".$replayMode ) );
    outputVideoStream( "evtStream", $streamSrc, reScale( $Event->Width(), $scale ), reScale( $Event->Height(), $scale ), ZM_MPEG_LIVE_FORMAT );
} else {
    $streamSrc = getStreamSrc( array( "source=event", "mode=jpeg", "event=".$eid, "frame=".$fid, "scale=".$scale, "rate=".$rate, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "replay=".$replayMode) );
    if ( canStreamNative() ) {
        outputImageStream( "evtStream", $streamSrc, reScale( $Event->Width(), $scale ), reScale( $Event->Height(), $scale ), validHtmlStr($Event->Name()) );
    } else {
        outputHelperStream( "evtStream", $streamSrc, reScale( $Event->Width(), $scale ), reScale( $Event->Height(), $scale ) );
    }
} // end if stream method
?>
        </div>
        <p id="dvrControls">
          <input type="button" value="&lt;+" id="prevBtn" title="<?php echo translate('Prev') ?>" class="inactive" onclick="streamPrev( true );"/>
          <input type="button" value="&lt;&lt;" id="fastRevBtn" title="<?php echo translate('Rewind') ?>" class="inactive" disabled="disabled" onclick="streamFastRev( true );"/>
          <input type="button" value="&lt;" id="slowRevBtn" title="<?php echo translate('StepBack') ?>" class="unavail" disabled="disabled" onclick="streamSlowRev( true );"/>
          <input type="button" value="||" id="pauseBtn" title="<?php echo translate('Pause') ?>" class="inactive" onclick="streamPause( true );"/>
          <input type="button" value="|>" id="playBtn" title="<?php echo translate('Play') ?>" class="active" disabled="disabled" onclick="streamPlay( true );"/>
          <input type="button" value="&gt;" id="slowFwdBtn" title="<?php echo translate('StepForward') ?>" class="unavail" disabled="disabled" onclick="streamSlowFwd( true );"/>
          <input type="button" value="&gt;&gt;" id="fastFwdBtn" title="<?php echo translate('FastForward') ?>" class="inactive" disabled="disabled" onclick="streamFastFwd( true );"/>
          <input type="button" value="&ndash;" id="zoomOutBtn" title="<?php echo translate('ZoomOut') ?>" class="avail" onclick="streamZoomOut();"/>
          <input type="button" value="+&gt;" id="nextBtn" title="<?php echo translate('Next') ?>" class="inactive" onclick="streamNext( true );"/>
        </p>
        <div id="replayStatus">
          <span id="mode"><?php echo translate('Mode') ?>: <span id="modeValue">&nbsp;</span></span>
          <span id="rate"><?php echo translate('Rate') ?>: <span id="rateValue"></span>x</span>
          <span id="progress"><?php echo translate('Progress') ?>: <span id="progressValue"></span>s</span>
          <span id="zoom"><?php echo translate('Zoom') ?>: <span id="zoomValue"></span>x</span>
        </div>
        <div id="progressBar" class="invisible">
<?php for ( $i = 0; $i < $panelSections; $i++ ) { ?>
           <div class="progressBox" id="progressBox<?php echo $i ?>" title=""></div>
<?php } ?>
    </div>
<?php } ?>
        </div>
      </div>
<?php if ($Event->SaveJPEGs() & 3) { ?>
      <div id="eventStills" class="hidden">
        <div id="eventThumbsPanel">
          <div id="eventThumbs">
          </div>
        </div>
        <div id="eventImagePanel">
          <div id="eventImageFrame">
            <img id="eventImage" src="graphics/transparent.gif" alt=""/>
            <div id="eventImageBar">
              <div id="eventImageClose"><input type="button" value="<?php echo translate('Close') ?>" onclick="hideEventImage()"/></div>
              <div id="eventImageStats" class="hidden"><input type="button" value="<?php echo translate('Stats') ?>" onclick="showFrameStats()"/></div>
              <div id="eventImageData"><?php echo translate('Frame') ?> <span id="eventImageNo"></span></div>
            </div>
          </div>
        </div>
        <div id="eventImageNav">
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
          <div id="thumbsSliderPanel">
            <div id="thumbsSlider">
              <div id="thumbsKnob">
              </div>
            </div>
          </div>
        </div>
      </div>
<?php
} // end if SaveJPEGs() & 3 Analysis or Jpegs
}
?>
  </div>
</body>
</html>
