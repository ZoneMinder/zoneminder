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
    var scale = $('scale').getValue();
    var newWidth = ( monitorWidth * scale ) / SCALE_BASE;
    var newHeight = ( monitorHeight * scale ) / SCALE_BASE;

    streamCmdScale( scale );

    var streamImg = $('imageFeed').getElement('img');
    if ( !streamImg )
        streamImg = $('imageFeed').getElement('object');
    streamImg.setStyles( { width: newWidth, height: newHeight } );
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
    $('stateValue').setText( stateStrings[alarmState] );
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
            $('alarmSound').removeClass( 'hidden' );
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
            $('alarmSound').addClass( 'hidden' );
        }
    }
    lastAlarmState = alarmState;
}

var streamCmdParms = "view=request&request=stream&connkey="+connKey;
var streamCmdReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, onComplete: getStreamCmdResponse } );
var streamCmdTimer = null;

var streamStatus;

function getStreamCmdResponse( respText )
{
    if ( streamCmdTimer )
        streamCmdTimer = $clear( streamCmdTimer );

    if ( !respText )
        return;
    var response = Json.evaluate( respText );

    if ( response.result == 'Ok' )
    {
        streamStatus = response.status;
        $('fpsValue').setText( streamStatus.fps );

        setAlarmState( streamStatus.state );

        $('levelValue').setText( streamStatus.level );
        if ( streamStatus.level > 95 )
            $('levelValue').className = "alarm";
        else if ( streamStatus.level > 80 )
            $('levelValue').className = "alert";
        else
            $('levelValue').className = "ok";

        var delayString = secsToTime( streamStatus.delay );

        if ( streamStatus.paused == true )
        {
            $('modeValue').setText( "Paused" );
            $('rate').addClass( 'hidden' );
            $('delayValue').setText( delayString );
            $('delay').removeClass( 'hidden' );
            $('level').removeClass( 'hidden' );
            streamCmdPause( false );
        }
        else if ( streamStatus.delayed == true )
        {
            $('modeValue').setText( "Replay" );
            $('rateValue').setText( streamStatus.rate );
            $('rate').removeClass( 'hidden' );
            $('delayValue').setText( delayString );
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
            $('modeValue').setText( "Live" );
            $('rate').addClass( 'hidden' );
            $('delay').addClass( 'hidden' );
            $('level').addClass( 'hidden' );
            streamCmdPlay( false );
        }
        $('zoomValue').setText( streamStatus.zoom );
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
        streamCmdReq.request( streamCmdParms+"&command="+CMD_PAUSE );
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
        streamCmdReq.request( streamCmdParms+"&command="+CMD_PLAY );
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
        streamCmdReq.request( streamCmdParms+"&command="+CMD_STOP );
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
        streamCmdReq.request( streamCmdParms+"&command="+CMD_FASTFWD );
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
        streamCmdReq.request( streamCmdParms+"&command="+CMD_SLOWFWD );
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
        streamCmdReq.request( streamCmdParms+"&command="+CMD_SLOWREV );
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
        streamCmdReq.request( streamCmdParms+"&command="+CMD_FASTREV );
}

function streamCmdZoomIn( x, y )
{
    streamCmdReq.request( streamCmdParms+"&command="+CMD_ZOOMIN+"&x="+x+"&y="+y );
}

function streamCmdZoomOut()
{
    streamCmdReq.request( streamCmdParms+"&command="+CMD_ZOOMOUT );
}

function streamCmdScale( scale )
{
    streamCmdReq.request( streamCmdParms+"&command="+CMD_SCALE+"&scale="+scale );
}

function streamCmdPan( x, y )
{
    streamCmdReq.request( streamCmdParms+"&command="+CMD_PAN+"&x="+x+"&y="+y );
}

function streamCmdQuery()
{
    streamCmdReq.request( streamCmdParms+"&command="+CMD_QUERY );
}       

var statusCmdParms = "view=request&request=status&entity=monitor&id="+monitorId+"&element[]=Status&element[]=FrameRate";
var statusCmdReq = new Ajax( thisUrl, { method: 'post', data: statusCmdParms, timeout: AJAX_TIMEOUT, onComplete: getStatusCmdResponse } );
var statusCmdTimer = null;

function getStatusCmdResponse( respText )
{
    if ( statusCmdTimer )
        statusCmdTimer = $clear( statusCmdTimer );

    if ( !respText )
        return;
    var response = Json.evaluate( respText );

    if ( response.result == 'Ok' )
    {
        $('fpsValue').setText( response.monitor.FrameRate );
        setAlarmState( response.monitor.Status );
    }

    var statusCmdTimeout = statusRefreshTimeout;
    if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT )
        statusCmdTimeout = statusCmdTimeout/5;
    statusCmdTimer = statusCmdQuery.delay( statusCmdTimeout );
} 

function statusCmdQuery()
{
    statusCmdReq.request();
}       

var alarmCmdParms = "view=request&request=alarm&id="+monitorId;
var alarmCmdReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, onComplete: getAlarmCmdResponse, onTimeout: streamCmdQuery } );
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

function getActResponse( respText )
{
    if ( respText == 'Ok' )
        return;
    var response = Json.evaluate( respText );

    if ( response.result == 'Ok' )
    {
        if ( response.refreshParent )
        {
            window.opener.location.reload();
        }
    }
    eventCmdQuery();
}

function deleteEvent( event, eventId )
{
    var actParms = "view=request&request=event&action=delete&id="+eventId;
    var actReq = new Ajax( thisUrl, { method: 'post', timeout: 3000, data: actParms, onComplete: getActResponse } );
    actReq.request();
    event.stop();
}

var eventCmdParms = "view=request&request=status&entity=events&id="+monitorId+"&count="+maxDisplayEvents+"&sort=Id%20desc";
var eventCmdReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: eventCmdParms, onComplete: getEventCmdResponse, onTimeout: eventCmdQuery } );
var eventCmdTimer = null;
var eventCmdFirst = true;

function highlightRow( row )
{
    $(row).toggleClass( 'highlight' );
}

function getEventCmdResponse( respText )
{
    if ( eventCmdTimer )
        eventCmdTimer = $clear( eventCmdTimer );

    if ( respText == 'Ok' )
        return;
    var response = Json.evaluate( respText );

    if ( response.result == 'Ok' )
    {
        var dbEvents = response.events.reverse();
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
                link.setText( event.Id );
                link.injectInside( row.getElement( 'td.colId' ) );

                link = new Element( 'a', { 'href': '#', 'events': { 'click': createEventPopup.pass( [ event.Id, '&trms=1&attr1=MonitorId&op1=%3d&val1='+monitorId+'&page=1', event.Width, event.Height ] ) } });
                link.setText( event.Name );
                link.injectInside( row.getElement( 'td.colName' ) );

                row.getElement( 'td.colTime' ).setText( event.StartTime );
                row.getElement( 'td.colSecs' ).setText( event.Length );

                link = new Element( 'a', { 'href': '#', 'events': { 'click': createFramesPopup.pass( [ event.Id, event.Width, event.Height ] ) } });
                link.setText( event.Frames+'/'+event.AlarmFrames );
                link.injectInside( row.getElement( 'td.colFrames' ) );

                link = new Element( 'a', { 'href': '#', 'events': { 'click': createFramePopup.pass( [ event.Id, '0', event.Width, event.Height ] ) } });
                link.setText( event.AvgScore+'/'+event.MaxScore );
                link.injectInside( row.getElement( 'td.colScore' ) );

                link = new Element( 'a', { 'href': '#', 'title': deleteString, 'events': { 'click': deleteEvent.bindWithEvent( link, event.Id ), 'mouseover': highlightRow.pass( row ), 'mouseout': highlightRow.pass( row ) } });
                link.setText( 'X' );
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
                row.getElement( 'td.colName a' ).setText( event.Name );
                row.getElement( 'td.colSecs' ).setText( event.Length );
                row.getElement( 'td.colFrames a' ).setText( event.Frames+'/'+event.AlarmFrames );
                row.getElement( 'td.colScore a' ).setText( event.AvgScore+'/'+event.MaxScore );
                row.removeClass( 'recent' );
            }
            row.addClass( 'updated' );
        }

        var rows = $(eventListBody).getElements( 'tr' );
        for ( var i = 0; i < rows.length; i++ )
        {
            if ( !rows[i].hasClass( 'updated' ) )
            {
                rows[i].remove();
                rows.splice( i, 1 );
                i--;
            }
        }
        while ( rows.length > maxDisplayEvents )
        {
            rows[rows.length-1].remove();
            rows.length--;
        }
    }

    var eventCmdTimeout = eventsRefreshTimeout;
    if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT )
        eventCmdTimeout = eventCmdTimeout/5;
    eventCmdTimer = eventCmdQuery.delay( eventCmdTimeout );
    eventCmdFirst = false;
}

function eventCmdQuery()
{
    eventCmdReq.request();
}

var controlParms = "view=request&request=control&id="+monitorId;
var controlReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, onComplete: getControlResponse } );

function getControlResponse( respText )
{
    if ( !respText )
        return;
    //console.log( respText );
    var response = Json.evaluate( respText );
    if ( response.result != 'Ok' )
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
    if ( streamMode == "single" )
        fetchImage.pass( $('imageFeed').getElement('img') ).delay( 1000 );
}

function controlCmdImage( x, y )
{
    var imageControlParms = controlParms;
    imageControlParms += "&scale="+scale;
    imageControlParms += "&control="+imageControlMode;

    controlReq.request( imageControlParms+"&x="+x+"&y="+y );
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

function initPage()
{
    if ( streamMode == "single" )
        statusCmdTimer = statusCmdQuery.delay( (Math.random()+0.5)*statusRefreshTimeout );
    else
        streamCmdTimer = streamCmdQuery.delay( (Math.random()+0.5)*statusRefreshTimeout );
 
    eventCmdTimer = eventCmdQuery.delay( (Math.random()+0.5)*eventsRefreshTimeout );

    if ( canStreamNative || streamMode == "single" )
    {
        var streamImg = $('imageFeed').getElement('img');
        if ( !streamImg )
            streamImg = $('imageFeed').getElement('object');
        $(streamImg).addEvent( 'click', handleClick.bindWithEvent( $(streamImg) ) );
        if ( streamMode == "single" )
            fetchImage.pass( streamImg ).periodical( imageRefreshTimeout );
    }

}

// Kick everything off
window.addEvent( 'domready', initPage );
