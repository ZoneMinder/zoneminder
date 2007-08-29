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
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );
mysql_free_result( $result );

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

var url = "<?= ZM_BASE_URL.$PHP_SELF ?>";

function setButtonState( element, class )
{
    element.className = class;
    element.disabled = (class != 'inactive');
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
    console.log( "Got new scale: "+scale );
    var newWidth = ( baseWidth * scale ) / <?= SCALE_BASE ?>;
    var newHeight = ( baseHeight * scale ) / <?= SCALE_BASE ?>;

    cmdScale( scale );

    var streamImg = $('imageFeed').getElement('img');
    $(streamImg).setStyles( { width: newWidth, height: newHeight } );
}

var cmdParms = "view=request&request=command&connkey=<?= $connkey ?>";
var cmdTimeoutId = 0;

var lastState = STATE_IDLE;
var status;

function getCmdResponse( resp_text, resp_xml )
{
    if ( cmdTimeoutId )
    {
        window.clearTimeout( cmdTimeoutId );
        cmdTimeoutId = 0;
    }
    if ( !resp_text )
        return;
    var resp_func = new Function( "return "+resp_text );
    var resp_obj = resp_func();
    status = resp_obj.status;
    $('fpsValue').setHTML( status.fps );
    var state = status.state;
    var stateString = "Unknown";
    var stateClass = "";
    if ( state <= STATE_PREALARM )
    {
        stateString = "<?= $zmSlangIdle ?>";
    }
    else if ( state == STATE_ALARM )
    {
        stateString = "<?= $zmSlangAlarm ?>";
        stateClass = "alarm";
    }
    else if ( state == STATE_ALERT )
    {
        stateString = "<?= $zmSlangAlert ?>";
        stateClass = "alert";
    }
    else if ( state == STATE_TAPE )
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
        cmdPause( false );
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
            cmdPlay( false );
        }
        else if ( status.rate > 0 )
        {
            if ( status.rate < 1 )
                cmdSlowFwd( false );
            else
                cmdFastFwd( false );
        }
        else
        {
            if ( status.rate > -1 )
                cmdSlowRev( false );
            else
                cmdFastRev( false );
        }
    }
    else 
    {
        $('modeValue').setHTML( "Live" );
        $('rate').addClass( 'hidden' );
        $('delay').addClass( 'hidden' );
        $('level').addClass( 'hidden' );
        cmdPlay( false );
    }
    $('zoomValue').setHTML( status.zoom );
    if ( status.zoom == "1.0" )
        setButtonState( $('zoomOutBtn'), 'unavail' );
    else
        setButtonState( $('zoomOutBtn'), 'inactive' );

    var isAlarmed = ( state == STATE_ALARM || state == STATE_ALERT );
    var wasAlarmed = ( lastState == STATE_ALARM || lastState == STATE_ALERT );

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
    var cmdTimeout = <?= ZM_WEB_REFRESH_STATUS ?>;
    if ( state == STATE_ALARM || state == STATE_ALERT )
    {
        cmdTimeout = 1;
    }
    cmdTimeoutId = window.setTimeout( 'cmdQuery()', 1000 * cmdTimeout );
    lastState = state;
}

function cmdPause( action )
{
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_PAUSE ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdPlay( action )
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
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_PLAY ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdStop( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'unavail' );
    setButtonState( $('stopBtn'), 'active' );
    setButtonState( $('fastFwdBtn'), 'unavail' );
    setButtonState( $('slowFwdBtn'), 'unavail' );
    setButtonState( $('slowRevBtn'), 'unavail' );
    setButtonState( $('fastRevBtn'), 'unavail' );

    //window.setTimeout('setButtonState( $('stopBtn'), 'unavail' ); setButtonState( $('playBtn'), 'active' );", 500 );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_STOP ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
    setButtonState( $('stopBtn'), 'unavail' );
    setButtonState( $('playBtn'), 'active' );
}

function cmdFastFwd( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_FASTFWD ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdSlowFwd( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'active' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_SLOWFWD ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
}

function cmdSlowRev( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'active' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_SLOWREV ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('slowRevBtn'), 'inactive' );
}

function cmdFastRev( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('stopBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_FASTREV ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdZoomIn( x, y )
{
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_ZOOMIN ?>&x="+x+"&y="+y, onComplete: getCmdResponse } );
    cmdReq.request();
}

function cmdZoomOut()
{
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_ZOOMOUT ?>", onComplete: getCmdResponse } );
    cmdReq.request();
}

function cmdScale( scale )
{
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_SCALE ?>&scale="+scale, onComplete: getCmdResponse } );
    cmdReq.request();
}

function cmdPan( x, y )
{
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_PAN ?>&x="+x+"&y="+y, onComplete: getCmdResponse } );
    cmdReq.request();
}

function cmdQuery()
{       
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_QUERY ?>", onComplete: getCmdResponse } );
    cmdReq.request();
}       

var evtParms = "view=request&request=status&entity=events&id=<?= $mid ?>&count=<?= MAX_EVENTS ?>&sort=Id%20desc";
var evtTimeoutId = 0;
var evtFirst = true;

function getEvtResponse( resp_text, resp_xml )
{
    if ( evtTimeoutId )
    {
        window.clearTimeout( evtTimeoutId );
        evtTimeoutId = 0;
    }
    if ( resp_text == 'Ok' )
        return;
    var resp_func = new Function( "return "+resp_text );
    var resp_obj = resp_func();

    var db_events = resp_obj.events.reverse();
    var eventList = $('eventList');
    var eventListBody = $(eventList).getElement( 'tbody' );
    var eventListRows = $(eventListBody).getElements( 'tr' );
    for ( var i = 0; i < db_events.length; i++ )
    {
        var row = $('event'+db_events[i].Id);
        if ( !$(row) )
        {
            row = new Element( 'tr', { 'id': 'event'+db_events[i].Id } );
            if ( !evtFirst )
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
    var evtTimeout = <?= ZM_WEB_REFRESH_STATUS ?>;
    //if ( state == STATE_ALARM || state == STATE_ALERT )
    //{
        //cmdTimeout = 1;
    //}
    evtTimeoutId = window.setTimeout( 'evtQuery()', 1000 * evtTimeout );
    evtFirst = false;
}

function evtQuery()
{
    var evtReq = new Ajax( url, { method: 'post', postBody: evtParms, onComplete: getEvtResponse } );
    evtReq.request();
}

var controlParms = "view=request&request=control&id=<?= $mid ?>";

function getControlResponse( resp_text, resp_xml )
{
    if ( !resp_text )
        return;
    console.log( resp_text );
    var resp_func = new Function( "return "+resp_text );
    var resp_obj = resp_func();
    result = resp_obj.result;
    if ( result != 'Ok' )
    {
        alert( "Control response was status = "+resp_obj.status+"\nmessage = "+resp_obj.message );
    }
}

function controlCmd( control )
{
    var controlReq = new Ajax( url, { method: 'post', postBody: controlParms+"&control="+control, onComplete: getControlResponse } );
    controlReq.request();
}

function controlCmdImage( x, y )
{
    var imageControlParms = controlParms;
    imageControlParms += "&scale=<?= $scale ?>";
<?php
if ( $monitor['CanMoveMap'] )
{
?>
    imageControlParms += "&control=move_map";
<?php
}
elseif ( $monitor['CanMoveRel'] )
{
?>
    imageControlParms += "&control=move_psuedo_map";
<?php
}
elseif ( $monitor['CanMoveCon'] )
{
?>
    imageControlParms += "&control=move_con_map";
<?php
}
?>
    var controlReq = new Ajax( url, { method: 'post', postBody: imageControlParms+"&x="+x+"&y="+y, onComplete: getControlResponse } );
    controlReq.request();
}       
function startRequests()
{
    cmdTimeoutId = window.setTimeout( 'cmdQuery()', 1000 );
    evtTimeoutId = window.setTimeout( 'evtQuery()', 1500 );
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
$refresh = (isset($force)||$forced||isset($disable)||$disabled||(($status>=STATE_PREALARM)&&($status<=STATE_ALERT)))?1:ZM_WEB_REFRESH_STATUS;
$url = "$PHP_SELF?view=watchstatus&amp;mid=$mid&amp;last_status=$status".(($force||$forced)?"&amp;forced=1":"").(($disable||$disabled)?"&amp;disabled=1":"");

?>
      <span id="enableAlarms"><a href="<?= $PHP_SELF ?>?view=watchstatus&amp;mid=<?= $mid ?>&amp;last_status=<?= $status ?>&amp;disable=0"><?= $zmSlangEnableAlarms ?></a></span>
      <span id="monitorState"><?= $zmSlangState ?>:&nbsp;<span id="stateValue"></span>&nbsp;-&nbsp;<span id="fpsValue"></span>&nbsp;fps</span>
<?php
if ( !($disable || $disabled) )
{
	if ( canEdit( 'Monitors' ) && ($force || $forced) )
	{
?>
      <span id="forceAlarm"><a href="<?= $PHP_SELF ?>?view=watchstatus&amp;mid=<?= $mid ?>&amp;last_status=<?= $status ?>&amp;force=0"><?= $zmSlangCancelForcedAlarm ?></a></span>
<?php
	}
	elseif ( canEdit( 'Monitors' ) && zmaCheck( $mid ) )
	{
?>
      <span id="forceAlarm"><a href="<?= $PHP_SELF ?>?view=watchstatus&amp;mid=<?= $mid ?>&amp;last_status=<?= $status ?>&amp;force=1"><?= $zmSlangForceAlarm ?></a></span>
<?php
	}
}
?>
    </div>
    <p id="dvrControls">
      <input type="button" value="&lt;&lt;" id="fastRevBtn" class="unavail" disabled="disabled" onclick="cmdFastRev( true )"/>
      <input type="button" value="&lt;" id="slowRevBtn" class="unavail" disabled="disabled" onclick="cmdSlowRev( true )"/>
      <input type="button" value="||" id="pauseBtn" class="inactive" onclick="cmdPause( true )"/>
      <input type="button" value="[]" id="stopBtn" class="unavail" disabled="disabled" onclick="cmdStop( true )"/>
      <input type="button" value="|>" id="playBtn" class="active" disabled="disabled" onclick="cmdPlay( true )"/>
      <input type="button" value="&gt;" id="slowFwdBtn" class="unavail" disabled="disabled" onclick="cmdSlowFwd( true )"/>
      <input type="button" value="&gt;&gt;" id="fastFwdBtn" class="unavail" disabled="disabled" onclick="cmdFastFwd( true )"/>
      <input type="button" value="&ndash;" id="zoomOutBtn" class="avail" onclick="cmdZoomOut()"/>
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
    //console.log( "Target = "+event.target );
    //console.log( "Left = "+$(target).getLeft() );
    var x = event.page.x - $(target).getLeft();
    var y = event.page.y - $(target).getTop();
    var canMove = <?= ($monitor['CanMoveMap'] || $monitor['CanMoveRel'] || $monitor['CanMoveCon'])?'true':'false' ?>;
    
    if ( showMode == "events" || !canMove )
    {
        if ( event.shift )
            cmdPan( x, y );
        else
            cmdZoomIn( x, y );
    }
    else
    {
        controlCmdImage( x, y );
    }
    //console.log(x+","+y)
}

var streamImg = $('imageFeed').getElement('img');
$(streamImg).addEvent( 'click', handleClick.bindWithEvent( $(streamImg) ) );
<?php
}
?>
  </script>
</body>
</html>
