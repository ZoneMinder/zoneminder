<?php
//
// ZoneMinder web watch feed view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

if ( !canView( 'Stream' ) )
{
	$view = "error";
	return;
}

$sql = "select C.*, M.* from Monitors as M left join Controls as C on (M.ControlId = C.Id ) where M.Id = '$mid'";
$monitor = dbFetchOne( $sql );

if ( !isset($control) )
    $control = (canView( 'Control' ) && ($monitor['DefaultView'] == 'Control'));

$showControls = ( ZM_OPT_CONTROL && $monitor['Controllable'] && canView( 'Control' ) );

if ( !isset( $scale ) )
	$scale = reScale( SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE );

$connkey = generateConnKey();

noCacheHeaders();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $monitor['Name'] ?> - <?= $zmSlangFeed ?></title>
<link rel="stylesheet" href="<?= basename(__FILE__,".php") ?>.css" type="text/css" media="screen"/>
<?php require_once( 'zm_html_js.php' ) ?>
<script type="text/javascript">
var STATE_IDLE = <?= STATE_IDLE ?>;
var STATE_PREALARM = <?= STATE_PREALARM ?>;
var STATE_ALARM = <?= STATE_ALARM ?>;
var STATE_ALERT = <?= STATE_ALERT ?>;
var STATE_TAPE = <?= STATE_TAPE ?>;

var thisUrl = "<?= ZM_BASE_URL.$PHP_SELF ?>";

function setButtonState( element, butclass )
{
    element.className = butclass;
    element.disabled = (butclass != 'inactive');
}

var showMode = "<?= ($showControls && $control)?"control":"events" ?>";
function showEvents()
{
    $('ptzControls').addClass( 'hidden' );
    $('events').removeClass( 'hidden' );
    $('controlToggle').setHTML( '<?= $zmSlangControl ?>' );
    $('controlToggle').setProperty( 'onclick', 'showControls()' );
    showMode = "events";
}

function showControls()
{
    $('events').addClass( 'hidden' );
    $('ptzControls').removeClass( 'hidden' );
    $('controlToggle').setHTML( '<?= $zmSlangEvents ?>' );
    $('controlToggle').setProperty( 'onclick', 'showEvents()' );
    showMode = "control";
}

function changeScale()
{
    var scale = $('scale').getValue();
    var baseWidth = <?= $monitor['Width'] ?>;
    var baseHeight = <?= $monitor['Height'] ?>;
    //console.log( "Got new scale: "+scale );
    var newWidth = ( baseWidth * scale ) / <?= SCALE_BASE ?>;
    var newHeight = ( baseHeight * scale ) / <?= SCALE_BASE ?>;

    streamCmdScale( scale );

    var streamImg = $('imageFeed').getElement('img');
    if ( !streamImg )
        streamImg = $('imageFeed').getElement('object');
    $(streamImg).setStyles( { width: newWidth, height: newHeight } );
}

var streamCmdParms = "view=request&request=stream&connkey=<?= $connkey ?>";
var streamCmdReq = new Ajax( thisUrl, { method: 'post', timeout: <?= ZM_WEB_AJAX_TIMEOUT ?>, onComplete: getStreamCmdResponse } );
var streamCmdTimer = null;

var alarmState = STATE_IDLE;
var lastAlarmState = STATE_IDLE;
var status;

function getStreamCmdResponse( respText )
{
    if ( streamCmdTimer )
        streamCmdTimer = $clear( streamCmdTimer );

    if ( !respText )
        return;
    var response = Json.evaluate( respText );
    status = response.status;
    $('fpsValue').setHTML( status.fps );
    alarmState = status.state;
    var stateString = "Unknown";
    var stateClass = "";
    if ( alarmState <= STATE_PREALARM )
    {
        stateString = "<?= $zmSlangIdle ?>";
    }
    else if ( alarmState == STATE_ALARM )
    {
        stateString = "<?= $zmSlangAlarm ?>";
        stateClass = "alarm";
    }
    else if ( alarmState == STATE_ALERT )
    {
        stateString = "<?= $zmSlangAlert ?>";
        stateClass = "alert";
    }
    else if ( alarmState == STATE_TAPE )
    {
        stateString = "<?= $zmSlangRecord ?>";
    }
    $('stateValue').setHTML( stateString );
    if ( stateClass )
        $('stateValue').setProperty( 'class', stateClass );
    else
        $('stateValue').removeProperty( 'class' );
    $('levelValue').setHTML( status.level );
    if ( status.level > 95)
    {
        $('levelValue').className = "alarm";
    }
    else if ( status.level > 80 )
    {
        $('levelValue').className = "alert";
    }
    else
    {
        $('levelValue').className = "ok";
    }

    var delayString;
    if ( status.delay < 60 )
        delayString = status.delay;
    else if ( status.delay < 60*60 )
    {
        var mins = parseInt(status.delay/60);
        var secs = status.delay%60;
        if ( secs < 10 )
            secs = '0'+secs.toString().substr( 0, 4 );
        else
            secs = secs.toString().substr( 0, 5 );
        delayString = mins+":"+secs;
    }
    else
    {
        var hours = parseInt(status.delay/3600);
        var mins = (status.delay%3600)/60;
        var secs = status.delay%60;
        if ( mins < 10 )
            mins = '0'+mins.toString().substr( 0, 4 );
        else
            mins = mins.toString().substr( 0, 5 );
        if ( secs < 10 )
            secs = '0'+secs.toString().substr( 0, 4 );
        else
            secs = secs.toString().substr( 0, 5 );
        delayString = hours+":"+mins+":"+secs;
    }
    if ( status.paused == true )
    {
        $('modeValue').setHTML( "Paused" );
        $('rate').addClass( 'hidden' );
        $('delayValue').setHTML( delayString );
        $('delay').removeClass( 'hidden' );
        $('level').removeClass( 'hidden' );
        streamCmdPause( false );
    }
    else if ( status.delayed == true )
    {
        $('modeValue').setHTML( "Replay" );
        $('rateValue').setHTML( status.rate );
        $('rate').removeClass( 'hidden' );
        $('delayValue').setHTML( delayString );
        $('delay').removeClass( 'hidden' );
        $('level').removeClass( 'hidden' );
        if ( status.rate == 1 )
        {
            streamCmdPlay( false );
        }
        else if ( status.rate > 0 )
        {
            if ( status.rate < 1 )
                streamCmdSlowFwd( false );
            else
                streamCmdFastFwd( false );
        }
        else
        {
            if ( status.rate > -1 )
                streamCmdSlowRev( false );
            else
                streamCmdFastRev( false );
        }
    }
    else 
    {
        $('modeValue').setHTML( "Live" );
        $('rate').addClass( 'hidden' );
        $('delay').addClass( 'hidden' );
        $('level').addClass( 'hidden' );
        streamCmdPlay( false );
    }
    $('zoomValue').setHTML( status.zoom );
    if ( status.zoom == "1.0" )
        setButtonState( $('zoomOutBtn'), 'unavail' );
    else
        setButtonState( $('zoomOutBtn'), 'inactive' );

<?php
if ( canEdit( 'Monitors' ) )
{
?>
    $('enableLink').removeEvents( 'click' );
    if ( status.enabled )
    {
        $('enableLink').setText( '<?= $zmSlangDisableAlarms ?>' );
        $('enableLink').addEvent( 'click', cmdDisableAlarms );
        $('forceLink').removeEvents( 'click' );
        if ( status.forced )
        {
            $('forceLink').setText( '<?= $zmSlangCancelForcedAlarm ?>' );
            $('forceLink').addEvent( 'click', cmdCancelForcedAlarm );
        }
        else
        {
            $('forceLink').setText( '<?= $zmSlangForceAlarm ?>' );
            $('forceLink').addEvent( 'click', cmdForceAlarm );
        }
        $('forceLink').removeClass( 'hidden' );
    }
    else
    {
        $('enableLink').setText( '<?= $zmSlangEnableAlarms ?>' );
        $('enableLink').addEvent( 'click', cmdEnableAlarms );
        $('forceLink').addClass( 'hidden' );
    }
    $('enableLink').removeClass( 'hidden' );
<?php
}
?>

    var isAlarmed = ( alarmState == STATE_ALARM || alarmState == STATE_ALERT );
    var wasAlarmed = ( lastAlarmState == STATE_ALARM || lastAlarmState == STATE_ALERT );

    var newAlarm = ( isAlarmed && !wasAlarmed );
    var oldAlarm = ( !isAlarmed && wasAlarmed );

    if ( newAlarm )
    {
<?php
if ( ZM_WEB_SOUND_ON_ALARM )
{
?>
        // Enable the alarm sound
        $('alarmSound').removeClass( 'hidden' );
<?php
}
if ( ZM_WEB_POPUP_ON_ALARM )
{
?>
        window.focus();
<?php
}
?>
    }
<?php
if ( ZM_WEB_SOUND_ON_ALARM )
{
?>
    if ( oldAlarm )
    {
        // Disable alarm sound
        $('alarmSound').addClass( 'hidden' );
    }
<?php
}
?>
    var streamCmdTimeout = <?= 1000*ZM_WEB_REFRESH_STATUS ?>;
    if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT )
        streamCmdTimeout = streamCmdTimeout/5;
    streamCmdTimer = streamCmdQuery.delay( streamCmdTimeout );
    lastAlarmState = alarmState;
}

function streamCmdPause( action )
{
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
        streamCmdReq.request( streamCmdParms+"&command=<?= CMD_PAUSE ?>" );
}

function streamCmdPlay( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'active' );
    if ( status.delayed == true )
    {
        setButtonState( $('stopBtn'), 'inactive' );
        setButtonState( $('fastFwdBtn'), 'inactive' );
        setButtonState( $('slowFwdBtn'), 'inactive' );
        setButtonState( $('slowRevBtn'), 'inactive' );
        setButtonState( $('fastRevBtn'), 'inactive' );
    }
    else
    {
        setButtonState( $('stopBtn'), 'unavail' );
        setButtonState( $('fastFwdBtn'), 'unavail' );
        setButtonState( $('slowFwdBtn'), 'unavail' );
        setButtonState( $('slowRevBtn'), 'unavail' );
        setButtonState( $('fastRevBtn'), 'unavail' );
    }
    if ( action )
        streamCmdReq.request( streamCmdParms+"&command=<?= CMD_PLAY ?>" );
}

function streamCmdStop( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'unavail' );
    setButtonState( $('stopBtn'), 'active' );
    setButtonState( $('fastFwdBtn'), 'unavail' );
    setButtonState( $('slowFwdBtn'), 'unavail' );
    setButtonState( $('slowRevBtn'), 'unavail' );
    setButtonState( $('fastRevBtn'), 'unavail' );
    if ( action )
        streamCmdReq.request( streamCmdParms+"&command=<?= CMD_STOP ?>" );
    setButtonState( $('stopBtn'), 'unavail' );
    setButtonState( $('playBtn'), 'active' );
}

function streamCmdFastFwd( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
        streamCmdReq.request( streamCmdParms+"&command=<?= CMD_FASTFWD ?>" );
}

function streamCmdSlowFwd( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'active' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
        streamCmdReq.request( streamCmdParms+"&command=<?= CMD_SLOWFWD ?>" );
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
}

function streamCmdSlowRev( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'active' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
        streamCmdReq.request( streamCmdParms+"&command=<?= CMD_SLOWREV ?>" );
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('slowRevBtn'), 'inactive' );
}

function streamCmdFastRev( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
        streamCmdReq.request( streamCmdParms+"&command=<?= CMD_FASTREV ?>" );
}

function streamCmdZoomIn( x, y )
{
    streamCmdReq.request( streamCmdParms+"&command=<?= CMD_ZOOMIN ?>&x="+x+"&y="+y );
}

function streamCmdZoomOut()
{
    streamCmdReq.request( streamCmdParms+"&command=<?= CMD_ZOOMOUT ?>" );
}

function streamCmdScale( scale )
{
    streamCmdReq.request( streamCmdParms+"&command=<?= CMD_SCALE ?>&scale="+scale );
}

function streamCmdPan( x, y )
{
    streamCmdReq.request( streamCmdParms+"&command=<?= CMD_PAN ?>&x="+x+"&y="+y );
}

function streamCmdQuery()
{       
    streamCmdReq.request( streamCmdParms+"&command=<?= CMD_QUERY ?>" );
}       

var alarmCmdParms = "view=request&request=alarm&id=<?= $mid ?>";
var alarmCmdReq = new Ajax( thisUrl, { method: 'post', timeout: <?= ZM_WEB_AJAX_TIMEOUT ?>, onComplete: getAlarmCmdResponse } );
var alarmCmdFirst = true;

function getAlarmCmdResponse( respText )
{
    if ( respText == 'Ok' )
        return;
    var response = Json.evaluate( respText );
}

function cmdDisableAlarms()
{
    alarmCmdReq.request( alarmCmdParms+"&command=disableAlarms" );
}

function cmdEnableAlarms()
{
    alarmCmdReq.request( alarmCmdParms+"&command=enableAlarms" );
}

function cmdForceAlarm()
{
    alarmCmdReq.request( alarmCmdParms+"&command=forceAlarm" );
}

function cmdCancelForcedAlarm()
{
    alarmCmdReq.request( alarmCmdParms+"&command=cancelForcedAlarm" );
}

var eventCmdParms = "view=request&request=status&entity=events&id=<?= $mid ?>&count=<?= MAX_EVENTS ?>&sort=Id%20desc";
var eventCmdReq = new Ajax( thisUrl, { method: 'post', timeout: <?= ZM_WEB_AJAX_TIMEOUT ?>, data: eventCmdParms, onComplete: getEventCmdResponse } );
var eventCmdTimer = null;
var eventCmdFirst = true;

function getEventCmdResponse( respText )
{
    if ( eventCmdTimer )
        eventCmdTimer = $clear( eventCmdTimer );

    if ( respText == 'Ok' )
        return;
    var response = Json.evaluate( respText );

    var db_events = response.events.reverse();
    var eventList = $('eventList');
    var eventListBody = $(eventList).getElement( 'tbody' );
    var eventListRows = $(eventListBody).getElements( 'tr' );
    for ( var i = 0; i < db_events.length; i++ )
    {
        var row = $('event'+db_events[i].Id);
        if ( !$(row) )
        {
            row = new Element( 'tr', { 'id': 'event'+db_events[i].Id } );
            if ( !eventCmdFirst )
                $(row).addClass( 'highlight' );
            var cell = new Element( 'td' );
            $(cell).injectInside( $(row) );
            $(cell).clone().injectInside( $(row) );
            $(cell).clone().injectInside( $(row) );
            $(cell).clone().injectInside( $(row) );
            $(cell).clone().injectInside( $(row) );
            $(cell).clone().injectInside( $(row) );
            $(row).injectTop( $(eventListBody) );
        }
        else
        {
            $(row).removeClass( 'highlight' );
        }
        var cells = $(row).getElements( 'td' );
        var id = cells[0];
        var idLink = $(id).getElement( 'a' );
        if ( !$(idLink) )
        {
            idLink = new Element( 'a', { 'href': '#', 'events': { 'click': eventWindow.pass( db_events[i].Id, '&trms=1&attr1=MonitorId&op1=%3d&val1=<?= $mid ?>&page=1' ) } });
            $(idLink).injectInside( $(id) );
        }
        $(idLink).setHTML( db_events[i].Id );
        var name = cells[1];
        var nameLink = $(name).getElement( 'a' );
        if ( !$(nameLink) )
        {
            nameLink = new Element( 'a', { 'href': '#', 'events': { 'click': eventWindow.pass( db_events[i].Id, '&trms=1&attr1=MonitorId&op1=%3d&val1=<?= $mid ?>&page=1' ) } });
            $(nameLink).injectInside( $(name) );
        }
        $(nameLink).setHTML( db_events[i].Name );
        var time = cells[2];
        $(time).setHTML( db_events[i].StartTime );
        var secs = cells[3];
        $(secs).setHTML( db_events[i].Length );
        var frames = cells[4];
        var framesLink = $(frames).getElement( 'a' );
        if ( !$(framesLink) )
        {
            framesLink = new Element( 'a', { 'href': '#', 'events': { 'click': framesWindow.pass( db_events[i].Id ) } });
            $(framesLink).injectInside( $(frames) );
        }
        $(framesLink).setHTML( db_events[i].Frames+'/'+db_events[i].AlarmFrames );
        var score = cells[5];
        var scoreLink = $(score).getElement( 'a' );
        if ( !$(scoreLink) )
        {
            scoreLink = new Element( 'a', { 'href': '#', 'events': { 'click': frameWindow.pass( [ db_events[i].Id, '0' ] ) } });
            $(scoreLink).injectInside( $(score) );
        }
        $(scoreLink).setHTML( db_events[i].AvgScore+'/'+db_events[i].MaxScore );
    }
    var rows = $(eventListBody).getElements( 'tr' );
    while ( $$(rows).length > <?= MAX_EVENTS ?> )
    {
        $$(rows)[$$(rows).length-1].remove();
        rows = $(eventListBody).getElements( 'tr' );
    }
    var eventCmdTimeout = <?= 1000*ZM_WEB_REFRESH_STATUS ?>;
    if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT )
        eventCmdTimeout = eventCmdTimeout/5;
    eventCmdTimer = eventCmdQuery.delay( eventCmdTimeout );
    eventCmdFirst = false;
}

function eventCmdQuery()
{
    eventCmdReq.request();
}

var controlParms = "view=request&request=control&id=<?= $mid ?>";
var controlReq = new Ajax( thisUrl, { method: 'post', timeout: <?= ZM_WEB_AJAX_TIMEOUT ?>, onComplete: getControlResponse } );

function getControlResponse( respText )
{
    if ( !respText )
        return;
    //console.log( respText );
    var response = Json.evaluate( respText );
    result = response.result;
    if ( result != 'Ok' )
    {
        alert( "Control response was status = "+response.status+"\nmessage = "+response.message );
    }
}

function controlCmd( control, event, xtell, ytell )
{
    var locParms = "";
    if ( event && (xtell || ytell) )
    {
        var xEvent = new Event( event );
        var target = xEvent.target;
        var coords = $(target).getCoordinates();

        var l = coords.left;
        var t = coords.top;
        var x = xEvent.page.x - l;
        var y = xEvent.page.y - t;

        if  ( xtell )
        {
            var xge = parseInt( (x*100)/coords.width );
            if ( xtell == -1 )
                xge = 100 - xge;
            else if ( xtell == 2 )
                xge = 2*(50 - xge);
            locParms += "&xge="+xge;
        }
        if  ( ytell )
        {
            var yge = parseInt( (y*100)/coords.height );
            if ( ytell == -1 )
                yge = 100 - yge;
            else if ( ytell == 2 )
                yge = 2*(50 - yge);
            locParms += "&yge="+yge;
        }
    }
    controlReq.request( controlParms+"&control="+control+locParms );
}

function controlCmdImage( x, y )
{
    var imageControlParms = controlParms;
    imageControlParms += "&scale=<?= $scale ?>";
<?php
if ( $monitor['CanMoveMap'] )
{
?>
    imageControlParms += "&control=moveMap";
<?php
}
elseif ( $monitor['CanMoveRel'] )
{
?>
    imageControlParms += "&control=movePseudoMap";
<?php
}
elseif ( $monitor['CanMoveCon'] )
{
?>
    imageControlParms += "&control=moveConMap";
<?php
}
?>
    controlReq.request( imageControlParms+"&x="+x+"&y="+y );
}       
function startRequests()
{
    streamCmdTimer = streamCmdQuery.delay( 1000 );
    eventCmdTimer = eventCmdQuery.delay( 1500 );
}
</script>
</head>
<body>
  <div id="container">
    <div id="menuBar">
      <span id="monitorName"><strong><?= $monitor['Name'] ?></strong></span>
      <span id="menuControls">
<?php
if ( $showControls )
{
	if ( !$control )
	{
		if ( canView( 'Control' ) )
		{
?>
      <span><a id="controlToggle" href="javascript: void(0)" onclick="showControls()"><?= $zmSlangControl ?></a></span>
<?php
		}
	}
	else
	{
		if ( canView( 'Events' ) )
		{
?>
      <span><a id="controlToggle" href="javascript: void(0)" onclick="showEvents()"><?= $zmSlangEvents ?></a></span>
<?php
		}
	}
}
?>
      <span><?= $zmSlangScale ?>: <?= buildSelect( "scale", $scales, "changeScale( this );" ); ?></span>
<?php
if ( canView( 'Control' ) && $monitor['Type'] == "Local" )
{
?>
      <span><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=settings&amp;mid=<?= $monitor['Id'] ?>', 'zmSettings<?= $monitor['Id'] ?>', <?= $jws['settings']['w'] ?>, <?= $jws['settings']['h'] ?> );"><?= $zmSlangSettings ?></a></span>
<?php
}
?>
      </span>
      <span id="closeWindow"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></span>
    </div>
    <div id="imageFeed">
<?php
if ( ZM_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT )
{
    $stream_src = getStreamSrc( array( "mode=mpeg", "monitor=".$mid, "scale=".$scale, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_LIVE_FORMAT ) );
    outputVideoStream( $stream_src, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'], ZM_MPEG_LIVE_FORMAT );
}
else
{
    $stream_src = getStreamSrc( array( "mode=jpeg", "monitor=".$mid, "scale=".$scale, "maxfps=".ZM_WEB_VIDEO_MAXFPS ) );
    if ( canStreamNative() )
    {
        //if ( $control && ($monitor['CanMoveMap'] || $monitor['CanMoveRel'] || $monitor['CanMoveCon']) )
        {
            outputImageStream( $stream_src, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] );
        }
    }
    else
    {
        outputHelperStream( $stream_src, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ) );
    }
}
?>
    </div>
    <div id="monitorStatus">
<?php
if ( canEdit( 'Monitors' ) )
{
?>
      <span id="enableAlarms"><a id="enableLink" href="javascript: void(0)" class="hidden">&nbsp;</a></span>
<?php
}
?>
      <span id="monitorState"><?= $zmSlangState ?>:&nbsp;<span id="stateValue"></span>&nbsp;-&nbsp;<span id="fpsValue"></span>&nbsp;fps</span>
<?php
if ( canEdit( 'Monitors' ) )
{
?>
      <span id="forceAlarm"><a id="forceLink" href="javascript: void(0)" class="hidden">&nbsp;</a></span>
<?php
}
?>
    </div>
    <p id="dvrControls">
      <input type="button" value="&lt;&lt;" id="fastRevBtn" title="<?= $zmSlangRewind ?>" class="unavail" disabled="disabled" onclick="streamCmdFastRev( true )"/>
      <input type="button" value="&lt;" id="slowRevBtn" title="<?= $zmSlangStepBack ?>" class="unavail" disabled="disabled" onclick="streamCmdSlowRev( true )"/>
      <input type="button" value="||" id="pauseBtn" title="<?= $zmSlangPause ?>" class="inactive" onclick="streamCmdPause( true )"/>
      <input type="button" value="[]" id="stopBtn" title="<?= $zmSlangStop ?>" class="unavail" disabled="disabled" onclick="streamCmdStop( true )"/>
      <input type="button" value="|>" id="playBtn" title="<?= $zmSlangPlay ?>" class="active" disabled="disabled" onclick="streamCmdPlay( true )"/>
      <input type="button" value="&gt;" id="slowFwdBtn" title="<?= $zmSlangStepForward ?>" class="unavail" disabled="disabled" onclick="streamCmdSlowFwd( true )"/>
      <input type="button" value="&gt;&gt;" id="fastFwdBtn" title="<?= $zmSlangFastForward ?>" class="unavail" disabled="disabled" onclick="streamCmdFastFwd( true )"/>
      <input type="button" value="&ndash;" id="zoomOutBtn" title="<?= $zmSlangZoomOut ?>" class="avail" onclick="streamCmdZoomOut()"/>
    </p>
    <div id="replayStatus"><span id="mode">Mode: <span id="modeValue">&nbsp;</span></span><span id="rate">&nbsp;&ndash;&nbsp;Rate: <span id="rateValue"></span>x</span><span id="delay">&nbsp;&ndash;&nbsp;Delay: <span id="delayValue"></span>s</span><span id="level">&nbsp;&ndash;&nbsp;Buffer: <span id="levelValue"></span>%</span><span id="zoom">&nbsp;&ndash;&nbsp;Zoom: <span id="zoomValue"></span>x</span>
    </div>
<?php
if ( $showControls )
{
    require_once( 'zm_control_funcsX.php' );
    $cmds = getControlCommands( $monitor );
?>
    <div id="ptzControls"<?= $control?'':' class="hidden"' ?>>
      <div id="controlsPanel">
<?php
        if ( $monitor['CanFocus'] )
        {
            echo controlFocus( $monitor );
        }
        if ( $monitor['CanZoom'] )
        {
            echo controlZoom( $monitor );
        }
        if ( $monitor['CanMove'] || ( $monitor['CanWake'] || $monitor['CanSleep'] || $monitor['CanReset'] ) )
        {
?>
        <div id="pantiltPanel">
<?php
	        if ( $monitor['CanMove'] )
	        {
                echo controlPanTilt( $monitor );
	        }
	        if ( $monitor['CanWake'] || $monitor['CanSleep'] || $monitor['CanReset'] )
	        {
                echo controlPower( $monitor );
	        }
?>
        </div>
<?php
        }
        if ( $monitor['CanIris'] )
        {
            echo controlIris( $monitor );
        }
        if ( $monitor['CanWhite'] )
        {
            echo controlWhite( $monitor );
        }
?>
      </div>
<?php
        if ( $monitor['HasPresets'] )
        {
            echo controlPresets( $monitor );
        }
?>
    </div>
<?php
}
if ( canView( 'Events' ) )
{
?>
    <div id="events"<?= $control?' class="hidden"':'' ?>>
      <table id="eventList" cellspacing="0">
        <thead>
          <tr>
            <td><?= $zmSlangId ?></td>
            <td><?= $zmSlangName ?></td>
            <td><?= $zmSlangTime ?></td>
            <td><?= $zmSlangSecs ?></td>
            <td><?= $zmSlangFrames ?></td>
            <td><?= $zmSlangScore ?></td>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
<?php
}
if ( ZM_WEB_SOUND_ON_ALARM )
{
    $sound_src = ZM_DIR_SOUNDS.'/'.ZM_WEB_ALARM_SOUND;
?>
    <div id="alarmSound" class="hidden">
<?php
    if ( ZM_WEB_USE_OBJECT_TAGS && isWindows() )
    {
?>
      <object id="MediaPlayer" width="0" height="0"
        classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
        codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,902">
        <param name="FileName" value="<?= $sound_src ?>"/>
        <param name="autoStart" value="1"/>
        <param name="loop" value="1"/>
        <param name=hidden value="1"/>
        <param name="showControls" value="0"/>
        <embed src="<?= $sound_src ?>"
          autostart="true"
          loop="true"
          hidden="true">
        </embed>
      </object>
<?php
    }
    else
    {
?>
      <embed src="<?= $sound_src ?>"
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
  <script type="text/javascript">
startRequests();
<?php
if ( canStreamNative() )
{
?>
function handleClick( event )
{
    var target = event.target;
    var x = event.page.x - $(target).getLeft();
    var y = event.page.y - $(target).getTop();
    var canMove = <?= ($monitor['CanMoveMap'] || $monitor['CanMoveRel'] || $monitor['CanMoveCon'])?'true':'false' ?>;
    
    if ( showMode == "events" || !canMove )
    {
        if ( event.shift )
            streamCmdPan( x, y );
        else
            streamCmdZoomIn( x, y );
    }
    else
    {
        controlCmdImage( x, y );
    }
}

var streamImg = $('imageFeed').getElement('img');
if ( !streamImg )
    streamImg = $('imageFeed').getElement('object');
$(streamImg).addEvent( 'click', handleClick.bindWithEvent( $(streamImg) ) );
<?php
}
?>
  </script>
</body>
</html>
