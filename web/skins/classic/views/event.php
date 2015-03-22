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

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

$eid = validInt( $_REQUEST['eid'] );
$fid = !empty($_REQUEST['fid'])?validInt($_REQUEST['fid']):1;

$sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultRate,M.DefaultScale,M.VideoWriter,M.SaveJPEGs FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE E.Id = ?';
$sql_values = array( $eid );

if ( $user['MonitorIds'] ) {
    $monitor_ids = explode( ',', $user['MonitorIds'] );
    $sql .= ' AND MonitorId IN (' .implode( ',', array_fill(0,count($monitor_ids),'?') ) . ')';
    $sql_values = array_merge( $sql_values, $monitor_ids );
}
$event = dbFetchOne( $sql, NULL, $sql_values );

if ( isset( $_REQUEST['rate'] ) )
    $rate = validInt($_REQUEST['rate']);
else
    $rate = reScale( RATE_BASE, $event['DefaultRate'], ZM_WEB_DEFAULT_RATE );
if ( isset( $_REQUEST['scale'] ) )
    $scale = validInt($_REQUEST['scale']);
else
    $scale = reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE );

$replayModes = array(
    'single' => $SLANG['ReplaySingle'],
    'all' => $SLANG['ReplayAll'],
    'gapless' => $SLANG['ReplayGapless'],
);

if ( isset( $_REQUEST['streamMode'] ) )
	$streamMode = validHtmlStr($_REQUEST['streamMode']);
else
	$streamMode = video;

if ( isset( $_REQUEST['replayMode'] ) )
    $replayMode = validHtmlStr($_REQUEST['replayMode']);
if ( isset( $_COOKIE['replayMode']) && preg_match('#^[a-z]+$#', $_COOKIE['replayMode']) )
    $replayMode = validHtmlStr($_COOKIE['replayMode']);
else {
	$keys = array_keys( $replayModes );
	$replayMode = array_shift( $keys );
}

parseSort();
parseFilter( $_REQUEST['filter'] );
$filterQuery = $_REQUEST['filter']['query'];

$panelSections = 40;
$panelSectionWidth = (int)ceil(reScale($event['Width'],$scale)/$panelSections);
$panelWidth = ($panelSections*$panelSectionWidth-1);

$connkey = generateConnKey();

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Event'] );
?>
<body>
  <div id="page">
    <div id="content">
      <div id="dataBar">
        <table id="dataTable" class="major" cellspacing="0">
          <tr>
            <td><span id="dataId" title="<?php echo $SLANG['Id'] ?>"><?php echo $event['Id'] ?></span></td>
            <td><span id="dataCause" title="<?php echo $event['Notes']?validHtmlStr($event['Notes']):$SLANG['AttrCause'] ?>"><?php echo validHtmlStr($event['Cause']) ?></span></td>
            <td><span id="dataTime" title="<?php echo $SLANG['Time'] ?>"><?php echo strftime( STRF_FMT_DATETIME_SHORT, strtotime($event['StartTime'] ) ) ?></span></td>
            <td><span id="dataDuration" title="<?php echo $SLANG['Duration'] ?>"><?php echo $event['Length'] ?></span>s</td>
            <td><span id="dataFrames" title="<?php echo $SLANG['AttrFrames']."/".$SLANG['AttrAlarmFrames'] ?>"><?php echo $event['Frames'] ?>/<?php echo $event['AlarmFrames'] ?></span></td>
            <td><span id="dataScore" title="<?php echo $SLANG['AttrTotalScore']."/".$SLANG['AttrAvgScore']."/".$SLANG['AttrMaxScore'] ?>"><?php echo $event['TotScore'] ?>/<?php echo $event['AvgScore'] ?>/<?php echo $event['MaxScore'] ?></span></td>
          </tr>
        </table>
      </div>
      <div id="menuBar1">
        <div id="scaleControl"><label for="scale"><?php echo $SLANG['Scale'] ?></label><?php echo buildSelect( "scale", $scales, "changeScale();" ); ?></div>
        <div id="replayControl"><label for="replayMode"><?php echo $SLANG['Replay'] ?></label><?php echo buildSelect( "replayMode", $replayModes, "changeReplayMode();" ); ?></div>
        <div id="nameControl"><input type="text" id="eventName" name="eventName" value="<?php echo validHtmlStr($event['Name']) ?>" size="16"/><input type="button" value="<?php echo $SLANG['Rename'] ?>" onclick="renameEvent()"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/></div>
      </div>
      <div id="menuBar2">
        <div id="closeWindow"><a href="#" onclick="closeWindow();"><?php echo $SLANG['Close'] ?></a></div>
<?php
if ( canEdit( 'Events' ) )
{
?>
				<div id="deleteEvent"><a href="#" onclick="deleteEvent()"><?php echo $SLANG['Delete'] ?></a></div>
				<div id="editEvent"><a href="#" onclick="editEvent()"><?php echo $SLANG['Edit'] ?></a></div>
				<div id="archiveEvent" class="hidden"><a href="#" onclick="archiveEvent()"><?php echo $SLANG['Archive'] ?></a></div>
				<div id="unarchiveEvent" class="hidden"><a href="#" onclick="unarchiveEvent()"><?php echo $SLANG['Unarchive'] ?></a></div>
<?php
}
if ( canView( 'Events' ) )
{
?>
				<div id="framesEvent"><a href="#" onclick="showEventFrames()"><?php echo $SLANG['Frames'] ?></a></div>
<?php
if ( $event['SaveJPEGs'] & 3 )
{
?>
				<div id="stillsEvent"<?php if ( $streamMode == 'still' ) { ?> class="hidden"<?php } ?>><a href="#" onclick="showStills()"><?php echo $SLANG['Stills'] ?></a></div>
<?php
}
?>
				<div id="videoEvent"<?php if ( $streamMode == 'video' ) { ?> class="hidden"<?php } ?>><a href="#" onclick="showVideo()"><?php echo $SLANG['Video'] ?></a></div>
				<div id="exportEvent"><a href="#" onclick="exportEvent()"><?php echo $SLANG['Export'] ?></a></div>
			</div>
			<div id="eventVideo" class="">
<?php 
if ( $event['VideoWriter'] )
{ 
?>
<link href="//vjs.zencdn.net/4.11/video-js.css" rel="stylesheet">
<script src="//vjs.zencdn.net/4.11/video.js"></script>
				<div id="videoFeed">
					<video id="videoobj" class="video-js vjs-default-skin" width="<?php echo reScale( $event['Width'], $scale ) ?>" height="<?php echo reScale( $event['Height'], $scale ) ?>" data-setup='{ "controls": true, "autoplay": false, "preload": "auto" }' >
					<source src="<?php echo getEventDefaultVideoPath($event) ?>" type="video/mp4">
					Your browser does not support the video tag.
					</video>
				</div>

<?php
}
else
{
?>
				<div id="imageFeed">
<?php
if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT )
{
		$streamSrc = getStreamSrc( array( "source=event", "mode=mpeg", "event=".$eid, "frame=".$fid, "scale=".$scale, "rate=".$rate, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_REPLAY_FORMAT, "replay=".$replayMode ) );
		outputVideoStream( "evtStream", $streamSrc, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ), ZM_MPEG_LIVE_FORMAT );
}
else
{
		$streamSrc = getStreamSrc( array( "source=event", "mode=jpeg", "event=".$eid, "frame=".$fid, "scale=".$scale, "rate=".$rate, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "replay=".$replayMode) );
		if ( canStreamNative() )
		{
				outputImageStream( "evtStream", $streamSrc, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ), validHtmlStr($event['Name']) );
		}
		else
		{
				outputHelperStream( "evtStream", $streamSrc, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) );
		}
}
?>
        </div>
        <p id="dvrControls">
          <input type="button" value="&lt;+" id="prevBtn" title="<?php echo $SLANG['Prev'] ?>" class="inactive" onclick="streamPrev( true )"/>
          <input type="button" value="&lt;&lt;" id="fastRevBtn" title="<?php echo $SLANG['Rewind'] ?>" class="inactive" disabled="disabled" onclick="streamFastRev( true )"/>
          <input type="button" value="&lt;" id="slowRevBtn" title="<?php echo $SLANG['StepBack'] ?>" class="unavail" disabled="disabled" onclick="streamSlowRev( true )"/>
          <input type="button" value="||" id="pauseBtn" title="<?php echo $SLANG['Pause'] ?>" class="inactive" onclick="streamPause( true )"/>
          <input type="button" value="|>" id="playBtn" title="<?php echo $SLANG['Play'] ?>" class="active" disabled="disabled" onclick="streamPlay( true )"/>
          <input type="button" value="&gt;" id="slowFwdBtn" title="<?php echo $SLANG['StepForward'] ?>" class="unavail" disabled="disabled" onclick="streamSlowFwd( true )"/>
          <input type="button" value="&gt;&gt;" id="fastFwdBtn" title="<?php echo $SLANG['FastForward'] ?>" class="inactive" disabled="disabled" onclick="streamFastFwd( true )"/>
          <input type="button" value="&ndash;" id="zoomOutBtn" title="<?php echo $SLANG['ZoomOut'] ?>" class="avail" onclick="streamZoomOut()"/>
          <input type="button" value="+&gt;" id="nextBtn" title="<?php echo $SLANG['Next'] ?>" class="inactive" onclick="streamNext( true )"/>
        </p>
        <div id="replayStatus">
          <span id="mode">Mode: <span id="modeValue">&nbsp;</span></span>
          <span id="rate">Rate: <span id="rateValue"></span>x</span>
          <span id="progress">Progress: <span id="progressValue"></span>s</span>
          <span id="zoom">Zoom: <span id="zoomValue"></span>x</span>
        </div>
        <div id="progressBar" class="invisible">
<?php
        for ( $i = 0; $i < $panelSections; $i++ )
        {
?>
           <div class="progressBox" id="progressBox<?php echo $i ?>" title=""></div>
<?php
        }
?>
		</div>    
<?php				    
}
?>
				</div>
			</div>
<?php
if ($event['SaveJPEGs'] & 3)
{
?>
			<div id="eventStills" class="hidden">
				<div id="eventThumbsPanel">
					<div id="eventThumbs">
					</div>
				</div>
				<div id="eventImagePanel">
					<div id="eventImageFrame">
						<img id="eventImage" src="graphics/transparent.gif" alt=""/>
						<div id="eventImageBar">
							<div id="eventImageClose"><input type="button" value="<?php echo $SLANG['Close'] ?>" onclick="hideEventImage()"/></div>
							<div id="eventImageStats" class="hidden"><input type="button" value="<?php echo $SLANG['Stats'] ?>" onclick="showFrameStats()"/></div>
							<div id="eventImageData">Frame <span id="eventImageNo"></span></div>
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
}
}
?>
	</div>
</body>
</html>