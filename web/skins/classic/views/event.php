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

if ( $user['MonitorIds'] )
    $midSql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', dbEscape($user['MonitorIds']) ) ).")";
else
    $midSql = '';

$sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultRate,M.DefaultScale FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE E.Id = ?'.$midSql;
$event = dbFetchOne( $sql, NULL, array($eid) );

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
    $streamMode = canStream()?'stream':'stills';

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
            <td><span id="dataId" title="<?= $SLANG['Id'] ?>"><?= $event['Id'] ?></span></td>
            <td><span id="dataCause" title="<?= $event['Notes']?validHtmlStr($event['Notes']):$SLANG['AttrCause'] ?>"><?= validHtmlStr($event['Cause']) ?></span></td>
            <td><span id="dataTime" title="<?= $SLANG['Time'] ?>"><?= strftime( STRF_FMT_DATETIME_SHORT, strtotime($event['StartTime'] ) ) ?></span></td>
            <td><span id="dataDuration" title="<?= $SLANG['Duration'] ?>"><?= $event['Length'] ?></span>s</td>
            <td><span id="dataFrames" title="<?= $SLANG['AttrFrames']."/".$SLANG['AttrAlarmFrames'] ?>"><?= $event['Frames'] ?>/<?= $event['AlarmFrames'] ?></span></td>
            <td><span id="dataScore" title="<?= $SLANG['AttrTotalScore']."/".$SLANG['AttrAvgScore']."/".$SLANG['AttrMaxScore'] ?>"><?= $event['TotScore'] ?>/<?= $event['AvgScore'] ?>/<?= $event['MaxScore'] ?></span></td>
          </tr>
        </table>
      </div>
      <div id="menuBar1">
        <div id="scaleControl"><label for="scale"><?= $SLANG['Scale'] ?></label><?= buildSelect( "scale", $scales, "changeScale();" ); ?></div>
        <div id="replayControl"><label for="replayMode"><?= $SLANG['Replay'] ?></label><?= buildSelect( "replayMode", $replayModes, "changeReplayMode();" ); ?></div>
        <div id="nameControl"><input type="text" id="eventName" name="eventName" value="<?= validHtmlStr($event['Name']) ?>" size="16"/><input type="button" value="<?= $SLANG['Rename'] ?>" onclick="renameEvent()"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/></div>
      </div>
      <div id="menuBar2">
        <div id="closeWindow"><a href="#" onclick="closeWindow();"><?= $SLANG['Close'] ?></a></div>
<?php
if ( canEdit( 'Events' ) )
{
?>
        <div id="deleteEvent"><a href="#" onclick="deleteEvent()"><?= $SLANG['Delete'] ?></a></div>
        <div id="editEvent"><a href="#" onclick="editEvent()"><?= $SLANG['Edit'] ?></a></div>
<?php
}
if ( canView( 'Events' ) )
{
?>
        <div id="exportEvent"><a href="#" onclick="exportEvent()"><?= $SLANG['Export'] ?></a></div>
<?php
}
if ( canEdit( 'Events' ) )
{
?>
        <div id="archiveEvent" class="hidden"><a href="#" onclick="archiveEvent()"><?= $SLANG['Archive'] ?></a></div>
        <div id="unarchiveEvent" class="hidden"><a href="#" onclick="unarchiveEvent()"><?= $SLANG['Unarchive'] ?></a></div>
<?php
}
?>
        <div id="framesEvent"><a href="#" onclick="showEventFrames()"><?= $SLANG['Frames'] ?></a></div>
        <div id="streamEvent"<?php if ( $streamMode == 'stream' ) { ?> class="hidden"<?php } ?>><a href="#" onclick="showStream()"><?= $SLANG['Stream'] ?></a></div>
        <div id="stillsEvent"<?php if ( $streamMode == 'still' ) { ?> class="hidden"<?php } ?>><a href="#" onclick="showStills()"><?= $SLANG['Stills'] ?></a></div>
<?php
if ( ZM_OPT_FFMPEG )
{
?>
        <div id="videoEvent"><a href="#" onclick="videoEvent()"><?= $SLANG['Video'] ?></a></div>
<?php
}
?>
      </div>
      <div id="eventStream">
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
          <input type="button" value="&lt;+" id="prevBtn" title="<?= $SLANG['Prev'] ?>" class="inactive" onclick="streamPrev( true )"/>
          <input type="button" value="&lt;&lt;" id="fastRevBtn" title="<?= $SLANG['Rewind'] ?>" class="inactive" disabled="disabled" onclick="streamFastRev( true )"/>
          <input type="button" value="&lt;" id="slowRevBtn" title="<?= $SLANG['StepBack'] ?>" class="unavail" disabled="disabled" onclick="streamSlowRev( true )"/>
          <input type="button" value="||" id="pauseBtn" title="<?= $SLANG['Pause'] ?>" class="inactive" onclick="streamPause( true )"/>
          <input type="button" value="|>" id="playBtn" title="<?= $SLANG['Play'] ?>" class="active" disabled="disabled" onclick="streamPlay( true )"/>
          <input type="button" value="&gt;" id="slowFwdBtn" title="<?= $SLANG['StepForward'] ?>" class="unavail" disabled="disabled" onclick="streamSlowFwd( true )"/>
          <input type="button" value="&gt;&gt;" id="fastFwdBtn" title="<?= $SLANG['FastForward'] ?>" class="inactive" disabled="disabled" onclick="streamFastFwd( true )"/>
          <input type="button" value="&ndash;" id="zoomOutBtn" title="<?= $SLANG['ZoomOut'] ?>" class="avail" onclick="streamZoomOut()"/>
          <input type="button" value="+&gt;" id="nextBtn" title="<?= $SLANG['Next'] ?>" class="inactive" onclick="streamNext( true )"/>
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
           <div class="progressBox" id="progressBox<?= $i ?>" title=""></div>
<?php
        }
?>
        </div>
      </div>
      <div id="eventStills" class="hidden">
        <div id="eventThumbsPanel">
          <div id="eventThumbs">
          </div>
        </div>
        <div id="eventImagePanel" class="hidden">
          <div id="eventImageFrame">
            <img id="eventImage" src="graphics/transparent.gif" alt=""/>
            <div id="eventImageBar">
              <div id="eventImageClose"><input type="button" value="<?= $SLANG['Close'] ?>" onclick="hideEventImage()"/></div>
              <div id="eventImageStats" class="hidden"><input type="button" value="<?= $SLANG['Stats'] ?>" onclick="showFrameStats()"/></div>
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
    </div>
  </div>
</body>
</html>
