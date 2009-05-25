function setButtonState( element, butClass )
{
    element.className = butClass;
    element.disabled = (butClass != 'inactive');
}

function showEvents()
{
    $('ptzControls').addClass( 'hidden' );
    $('events').removeClass( 'hidden' );
    if ( $('eventsControl') )
        $('eventsControl').addClass('hidden');
    if ( $('controlControl') )
        $('controlControl').removeClass('hidden');
    showMode = "events";
}

function showPtzControls()
{
    $('events').addClass( 'hidden' );
    $('ptzControls').removeClass( 'hidden' );
    if ( $('eventsControl') )
        $('eventsControl').removeClass('hidden');
    if ( $('controlControl') )
        $('controlControl').addClass('hidden');
    showMode = "control";
}

function changeScale()
{
    var scale = $('scale').get('value');
    var newWidth = ( monitorWidth * scale ) / SCALE_BASE;
    var newHeight = ( monitorHeight * scale ) / SCALE_BASE;

    // This causes FF3 to kill the stream now, ok with FF2
    //streamCmdScale( scale );

    /*Stream could be an applet so can't use moo tools*/ 
    var streamImg = document.getElementById('liveStream');
    streamImg.style.width = newWidth + "px";
    streamImg.style.height = newHeight + "px";

}

var alarmState = STATE_IDLE;
var lastAlarmState = STATE_IDLE;

function setAlarmState( currentAlarmState )
{
    alarmState = currentAlarmState;

    var stateString = "Unknown";
    var stateClass = "";
    if ( alarmState == STATE_ALARM )
        stateClass = "alarm";
    else if ( alarmState == STATE_ALERT )
        stateClass = "alert";
    $('stateValue').set( 'text', stateStrings[alarmState] );
    if ( stateClass )
        $('stateValue').setProperty( 'class', stateClass );
    else
        $('stateValue').removeProperty( 'class' );

    var isAlarmed = ( alarmState == STATE_ALARM || alarmState == STATE_ALERT );
    var wasAlarmed = ( lastAlarmState == STATE_ALARM || lastAlarmState == STATE_ALERT );

    var newAlarm = ( isAlarmed && !wasAlarmed );
    var oldAlarm = ( !isAlarmed && wasAlarmed );

    if ( newAlarm )
    {
        if ( SOUND_ON_ALARM )
        {
            // Enable the alarm sound
            if ( !canPlayPauseAudio )
                $('alarmSound').removeClass( 'hidden' );
            else
                $('MediaPlayer').Play();
        }
        if ( POPUP_ON_ALARM )
        {
            window.focus();
        }
    }
    if ( SOUND_ON_ALARM )
    {
        if ( oldAlarm )
        {
            // Disable alarm sound
            if ( !canPlayPauseAudio )
                $('alarmSound').addClass( 'hidden' );
            else
                $('MediaPlayer').Stop();
        }
    }
    if ( oldAlarm) //done with an event do a refresh
        eventCmdQuery();

    lastAlarmState = alarmState;
}

var streamCmdParms = "view=request&request=stream&connkey="+connKey;
var streamCmdReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, onSuccess: getStreamCmdResponse } );
var streamCmdTimer = null;

var streamStatus;

function getStreamCmdResponse( respObj, respText )
{
    watchdogOk("stream");
    if ( streamCmdTimer )
        streamCmdTimer = $clear( streamCmdTimer );

    if ( respObj.result == 'Ok' )
    {
        streamStatus = respObj.status;
        $('fpsValue').set( 'text', streamStatus.fps );

        setAlarmState( streamStatus.state );

        $('levelValue').set( 'text', streamStatus.level );
        if ( streamStatus.level > 95 )
            $('levelValue').className = "alarm";
        else if ( streamStatus.level > 80 )
            $('levelValue').className = "alert";
        else
            $('levelValue').className = "ok";

        var delayString = secsToTime( streamStatus.delay );

        if ( streamStatus.paused == true )
        {
            $('modeValue').set( 'text', "Paused" );
            $('rate').addClass( 'hidden' );
            $('delayValue').set( 'text', delayString );
            $('delay').removeClass( 'hidden' );
            $('level').removeClass( 'hidden' );
            streamCmdPause( false );
        }
        else if ( streamStatus.delayed == true )
        {
            $('modeValue').set( 'text', "Replay" );
            $('rateValue').set( 'text', streamStatus.rate );
            $('rate').removeClass( 'hidden' );
            $('delayValue').set( 'text', delayString );
            $('delay').removeClass( 'hidden' );
            $('level').removeClass( 'hidden' );
            if ( streamStatus.rate == 1 )
            {
                streamCmdPlay( false );
            }
            else if ( streamStatus.rate > 0 )
            {
                if ( streamStatus.rate < 1 )
                    streamCmdSlowFwd( false );
                else
                    streamCmdFastFwd( false );
            }
            else
            {
                if ( streamStatus.rate > -1 )
                    streamCmdSlowRev( false );
                else
                    streamCmdFastRev( false );
            }
        }
        else 
        {
            $('modeValue').set( 'text', "Live" );
            $('rate').addClass( 'hidden' );
            $('delay').addClass( 'hidden' );
            $('level').addClass( 'hidden' );
            streamCmdPlay( false );
        }
        $('zoomValue').set( 'text', streamStatus.zoom );
        if ( streamStatus.zoom == "1.0" )
            setButtonState( $('zoomOutBtn'), 'unavail' );
        else
            setButtonState( $('zoomOutBtn'), 'inactive' );

        if ( canEditMonitors )
        {
            if ( streamStatus.enabled )
            {
                $('enableAlarmsLink').addClass( 'hidden' );
                $('disableAlarmsLink').removeClass( 'hidden' );
                if ( streamStatus.forced )
                {
                    $('forceAlarmLink').addClass( 'hidden' );
                    $('cancelAlarmLink').removeClass( 'hidden' );
                }
                else
                {
                    $('forceAlarmLink').removeClass( 'hidden' );
                    $('cancelAlarmLink').addClass( 'hidden' );
                }
                $('forceCancelAlarm').removeClass( 'hidden' );
            }
            else
            {
                $('enableAlarmsLink').removeClass( 'hidden' );
                $('disableAlarmsLink').addClass( 'hidden' );
                $('forceCancelAlarm').addClass( 'hidden' );
            }
            $('enableDisableAlarms').removeClass( 'hidden' );
        }
        else
        {
            $('enableDisableAlarms').addClass( 'hidden' );
        }
    }
    else
        checkStreamForErrors("getStreamCmdResponse",respObj);//log them

    var streamCmdTimeout = statusRefreshTimeout;
    if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT )
        streamCmdTimeout = streamCmdTimeout/5;
    streamCmdTimer = streamCmdQuery.delay( streamCmdTimeout );
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
        streamCmdReq.send( streamCmdParms+"&command="+CMD_PAUSE );
}

function streamCmdPlay( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'active' );
    if ( streamStatus.delayed == true )
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
        streamCmdReq.send( streamCmdParms+"&command="+CMD_PLAY );
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
        streamCmdReq.send( streamCmdParms+"&command="+CMD_STOP );
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
        streamCmdReq.send( streamCmdParms+"&command="+CMD_FASTFWD );
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
        streamCmdReq.send( streamCmdParms+"&command="+CMD_SLOWFWD );
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
        streamCmdReq.send( streamCmdParms+"&command="+CMD_SLOWREV );
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
        streamCmdReq.send( streamCmdParms+"&command="+CMD_FASTREV );
}

function streamCmdZoomIn( x, y )
{
    streamCmdReq.send( streamCmdParms+"&command="+CMD_ZOOMIN+"&x="+x+"&y="+y );
}

function streamCmdZoomOut()
{
    streamCmdReq.send( streamCmdParms+"&command="+CMD_ZOOMOUT );
}

function streamCmdScale( scale )
{
    streamCmdReq.send( streamCmdParms+"&command="+CMD_SCALE+"&scale="+scale );
}

function streamCmdPan( x, y )
{
    streamCmdReq.send( streamCmdParms+"&command="+CMD_PAN+"&x="+x+"&y="+y );
}

function streamCmdQuery()
{
    streamCmdReq.send( streamCmdParms+"&command="+CMD_QUERY );
}       

var statusCmdParms = "view=request&request=status&entity=monitor&id="+monitorId+"&element[]=Status&element[]=FrameRate";
var statusCmdReq = new Request.JSON( { url: thisUrl, method: 'post', data: statusCmdParms, timeout: AJAX_TIMEOUT, onSuccess: getStatusCmdResponse } );
var statusCmdTimer = null;

function getStatusCmdResponse( respObj, respText )
{
    watchdogOk("status");
    if ( statusCmdTimer )
        statusCmdTimer = $clear( statusCmdTimer );

    if ( respObj.result == 'Ok' )
    {
        $('fpsValue').set( 'text', respObj.monitor.FrameRate );
        setAlarmState( respObj.monitor.Status );
    }
    else
        checkStreamForErrors("getStatusCmdResponse",respObj);

    var statusCmdTimeout = statusRefreshTimeout;
    if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT )
        statusCmdTimeout = statusCmdTimeout/5;
    statusCmdTimer = statusCmdQuery.delay( statusCmdTimeout );
} 

function statusCmdQuery()
{
    statusCmdReq.send();
}       

var alarmCmdParms = "view=request&request=alarm&id="+monitorId;
var alarmCmdReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, onSuccess: getAlarmCmdResponse, onTimeout: streamCmdQuery } );
var alarmCmdFirst = true;

function getAlarmCmdResponse( respObj, respText )
{
    checkStreamForErrors("getAlarmCmdResponse",respObj);
}

function cmdDisableAlarms()
{
    alarmCmdReq.send( alarmCmdParms+"&command=disableAlarms" );
}

function cmdEnableAlarms()
{
    alarmCmdReq.send( alarmCmdParms+"&command=enableAlarms" );
}

function cmdForceAlarm()
{
    alarmCmdReq.send( alarmCmdParms+"&command=forceAlarm" );
}

function cmdCancelForcedAlarm()
{
    alarmCmdReq.send( alarmCmdParms+"&command=cancelForcedAlarm" );
}

function getActResponse( respObj, respText )
{
    if ( respObj.result == 'Ok' )
    {
        if ( respObj.refreshParent )
        {
            window.opener.location.reload();
        }
    }
    eventCmdQuery();
}

function deleteEvent( event, eventId )
{
    var actParms = "view=request&request=event&action=delete&id="+eventId;
    var actReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: 3000, data: actParms, onSuccess: getActResponse } );
    actReq.send();
    event.stop();
}

var eventCmdParms = "view=request&request=status&entity=events&id="+monitorId+"&count="+maxDisplayEvents+"&sort=Id%20desc";
var eventCmdReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: eventCmdParms, onSuccess: getEventCmdResponse, onTimeout: eventCmdQuery } );
var eventCmdTimer = null;
var eventCmdFirst = true;

function highlightRow( row )
{
    $(row).toggleClass( 'highlight' );
}

function getEventCmdResponse( respObj, respText )
{
    watchdogOk("event");
    if ( eventCmdTimer )
        eventCmdTimer = $clear( eventCmdTimer );

    if ( respObj.result == 'Ok' )
    {
        var dbEvents = respObj.events.reverse();
        var eventList = $('eventList');
        var eventListBody = $(eventList).getElement( 'tbody' );
        var eventListRows = $(eventListBody).getElements( 'tr' );

        eventListRows.each( function( row ) { row.removeClass( 'updated' ); } );

        for ( var i = 0; i < dbEvents.length; i++ )
        {
            var event = dbEvents[i];
            var row = $('event'+event.Id);
            var newEvent = (row == null ? true : false);
            if ( newEvent )
            {
                row = new Element( 'tr', { 'id': 'event'+event.Id } );
                new Element( 'td', { 'class': 'colId' } ).injectInside( row );
                new Element( 'td', { 'class': 'colName' } ).injectInside( row );
                new Element( 'td', { 'class': 'colTime' } ).injectInside( row );
                new Element( 'td', { 'class': 'colSecs' } ).injectInside( row );
                new Element( 'td', { 'class': 'colFrames' } ).injectInside( row );
                new Element( 'td', { 'class': 'colScore' } ).injectInside( row );
                new Element( 'td', { 'class': 'colDelete' } ).injectInside( row );

                var cells = row.getElements( 'td' );

                var link = new Element( 'a', { 'href': '#', 'events': { 'click': createEventPopup.pass( [ event.Id, '&trms=1&attr1=MonitorId&op1=%3d&val1='+monitorId+'&page=1', event.Width, event.Height ] ) } });
                link.set( 'text', event.Id );
                link.injectInside( row.getElement( 'td.colId' ) );

                link = new Element( 'a', { 'href': '#', 'events': { 'click': createEventPopup.pass( [ event.Id, '&trms=1&attr1=MonitorId&op1=%3d&val1='+monitorId+'&page=1', event.Width, event.Height ] ) } });
                link.set( 'text', event.Name );
                link.injectInside( row.getElement( 'td.colName' ) );

                row.getElement( 'td.colTime' ).set( 'text', event.StartTime );
                row.getElement( 'td.colSecs' ).set( 'text', event.Length );

                link = new Element( 'a', { 'href': '#', 'events': { 'click': createFramesPopup.pass( [ event.Id, event.Width, event.Height ] ) } });
                link.set( 'text', event.Frames+'/'+event.AlarmFrames );
                link.injectInside( row.getElement( 'td.colFrames' ) );

                link = new Element( 'a', { 'href': '#', 'events': { 'click': createFramePopup.pass( [ event.Id, '0', event.Width, event.Height ] ) } });
                link.set( 'text', event.AvgScore+'/'+event.MaxScore );
                link.injectInside( row.getElement( 'td.colScore' ) );

                link = new Element( 'a', { 'href': '#', 'title': deleteString, 'events': { 'click': deleteEvent.bindWithEvent( link, event.Id ), 'mouseover': highlightRow.pass( row ), 'mouseout': highlightRow.pass( row ) } });
                link.set( 'text', 'X' );
                link.injectInside( row.getElement( 'td.colDelete' ) );

                if ( i == 0 )
                    row.injectInside( $(eventListBody) );
                else
                {
                    row.injectTop( $(eventListBody) );
                    if ( !eventCmdFirst )
                        row.addClass( 'recent' );
                }
            }
            else
            {
                row.getElement( 'td.colName a' ).set( 'text', event.Name );
                row.getElement( 'td.colSecs' ).set( 'text', event.Length );
                row.getElement( 'td.colFrames a' ).set( 'text', event.Frames+'/'+event.AlarmFrames );
                row.getElement( 'td.colScore a' ).set( 'text', event.AvgScore+'/'+event.MaxScore );
                row.removeClass( 'recent' );
            }
            row.addClass( 'updated' );
        }

        var rows = $(eventListBody).getElements( 'tr' );
        for ( var i = 0; i < rows.length; i++ )
        {
            if ( !rows[i].hasClass( 'updated' ) )
            {
                rows[i].destroy();
                rows.splice( i, 1 );
                i--;
            }
        }
        while ( rows.length > maxDisplayEvents )
        {
            rows[rows.length-1].destroy();
            rows.length--;
        }
    }
    else
        checkStreamForErrors("getEventCmdResponse",respObj);

    var eventCmdTimeout = eventsRefreshTimeout;
    if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT )
        eventCmdTimeout = eventCmdTimeout/5;
    eventCmdTimer = eventCmdQuery.delay( eventCmdTimeout );
    eventCmdFirst = false;
}

function eventCmdQuery()
{
    if ( eventCmdTimer ) //avoid firing another if we are firing one
        eventCmdTimer = $clear( eventCmdTimer );
    eventCmdReq.send();
}

var controlParms = "view=request&request=control&id="+monitorId;
var controlReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, onSuccess: getControlResponse } );

function getControlResponse( respObj, respText )
{
    if ( !respObj )
        return;
    //console.log( respText );
    if ( respObj.result != 'Ok' )
    {
        alert( "Control response was status = "+respObj.status+"\nmessage = "+respObj.message );
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
    controlReq.send( controlParms+"&control="+control+locParms );
    if ( streamMode == "single" )
        fetchImage.pass( $('imageFeed').getElement('img') ).delay( 1000 );
}

function controlCmdImage( x, y )
{
    var imageControlParms = controlParms;
    imageControlParms += "&scale="+scale;
    imageControlParms += "&control="+imageControlMode;

    controlReq.send( imageControlParms+"&x="+x+"&y="+y );
    if ( streamMode == "single" )
        fetchImage.pass( $('imageFeed').getElement('img') ).delay( 1000 );
}       

var tempImage = null;
function fetchImage( streamImage )
{
    var now = new Date();
    if ( !tempImage )
        tempImage = new Element( 'img' );
    tempImage.setProperty( 'src', streamSrc+'&'+now.getTime() );
    $(streamImage).setProperty( 'src', tempImage.getProperty( 'src' ) );
}

function handleClick( event )
{
    var target = event.target;
    var x = event.page.x - $(target).getLeft();
    var y = event.page.y - $(target).getTop();
    
    if ( showMode == "events" || !imageControlMode )
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

function appletRefresh()
{
    if ( streamStatus && (!streamStatus.paused && !streamStatus.delayed) )
    {
        var streamImg = $('liveStream');
        var parent = streamImg.getParent();
        streamImg.dispose();
        streamImg.inject( parent );
        if ( appletRefreshTime )
            appletRefresh.delay( appletRefreshTime*1000 );
    }
    else
    {
        appletRefresh.delay( 15*1000 ); //if we are paused or delayed check every 15 seconds if we are live yet...
    }
}

var watchdogInactive = {
    'stream': false,
    'status': false,
    'event': false
};

var watchdogFunctions = {
    'stream': streamCmdQuery,
    'status': statusCmdQuery,
    'event': eventCmdQuery
};

//Make sure the various refreshes are still taking effect
function watchdogCheck( type )
{
    if ( watchdogInactive[type] )
{
        console.log( "Detected streamWatch of type: " + type + " stopped, restarting" );
        watchdogFunctions[type]();
        watchdogInactive[type] = false;
    }
    else
    {
        watchdogInactive[type] = true;
    }
}

function watchdogOk( type )
{
    watchdogInactive[type] = false;
}

function initPage()
{
    if ( streamMode == "single" )
    {
        statusCmdTimer = statusCmdQuery.delay( (Math.random()+0.1)*statusRefreshTimeout );
        watchdogCheck.pass('status').periodical(statusRefreshTimeout*2);
    }
    else
    {
        streamCmdTimer = streamCmdQuery.delay( (Math.random()+0.1)*statusRefreshTimeout );
        watchdogCheck.pass('stream').periodical(statusRefreshTimeout*2);
    }
 
    eventCmdTimer = eventCmdQuery.delay( (Math.random()+0.1)*statusRefreshTimeout );
    watchdogCheck.pass('event').periodical(eventsRefreshTimeout*2);

    if ( canStreamNative || streamMode == "single" )
    {
        var streamImg = $('imageFeed').getElement('img');
        if ( !streamImg )
            streamImg = $('imageFeed').getElement('object');
        $(streamImg).addEvent( 'click', handleClick.bindWithEvent( $(streamImg) ) );
        if ( streamMode == "single" )
            fetchImage.pass( streamImg ).periodical( imageRefreshTimeout );
    }

    if ( refreshApplet && appletRefreshTime )
        appletRefresh.delay( appletRefreshTime*1000 );
}

// Kick everything off
window.addEvent( 'domready', initPage );
