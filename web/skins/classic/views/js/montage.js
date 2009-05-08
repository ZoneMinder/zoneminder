function Monitor( index, id, connKey )
{
    this.index = index;
    this.id = id;
    this.connKey = connKey;
    this.status = null;
    this.alarmState = STATE_IDLE;
    this.lastAlarmState = STATE_IDLE;
    this.streamCmdParms = "view=request&request=stream&connkey="+this.connKey;
    this.streamCmdTimer = null;

    this.start = function( delay )
    {
        this.streamCmdTimer = this.streamCmdQuery.delay( delay, this );
    }

    this.getStreamCmdResponse = function( respObj, respText )
    {
        if ( this.streamCmdTimer )
            this.streamCmdTimer = $clear( this.streamCmdTimer );

        if ( respObj.result == 'Ok' )
        {
            this.status = respObj.status;
            this.alarmState = this.status.state;
            var stateClass = "";
            if ( this.alarmState == STATE_ALARM )
                stateClass = "alarm";
            else if ( this.alarmState == STATE_ALERT )
                stateClass = "alert";
            else
                stateClass = "idle";

            if ( !COMPACT_MONTAGE )
            {
                $('fpsValue'+this.index).set( 'text', this.status.fps );
                $('stateValue'+this.index).set( 'text', stateStrings[this.alarmState] );
                $('monitorState'+this.index).setProperty( 'class', stateClass );
            }
            $('monitor'+this.index).setProperty( 'class', stateClass );
            /*Stream could be an applet so can't use moo tools*/ 
            var stream = document.getElementById( "liveStream"+this.id );
            stream.className=stateClass;

            var isAlarmed = ( this.alarmState == STATE_ALARM || this.alarmState == STATE_ALERT );
            var wasAlarmed = ( this.lastAlarmState == STATE_ALARM || this.lastAlarmState == STATE_ALERT );

            var newAlarm = ( isAlarmed && !wasAlarmed );
            var oldAlarm = ( !isAlarmed && wasAlarmed );

            if ( newAlarm )
            {
                if ( false && SOUND_ON_ALARM )
                {
                    // Enable the alarm sound
                    $('alarmSound').removeClass( 'hidden' );
                }
                if ( POPUP_ON_ALARM )
                {
                    windowToFront();
                }
            }
            if ( false && SOUND_ON_ALARM )
            {
                if ( oldAlarm )
                {
                    // Disable alarm sound
                    $('alarmSound').addClass( 'hidden' );
                }
            }
        }
        else
        {
            console.error( respObj.message );
        }
        var streamCmdTimeout = statusRefreshTimeout;
        if ( this.alarmState == STATE_ALARM || this.alarmState == STATE_ALERT )
            streamCmdTimeout = streamCmdTimeout/5;
        this.streamCmdTimer = this.streamCmdQuery.delay( streamCmdTimeout, this );
        this.lastAlarmState = this.alarmState;
    }

    this.streamCmdQuery = function()
    {
        //this.streamCmdReq.cancel();
        this.streamCmdReq.send( this.streamCmdParms+"&command="+CMD_QUERY );
    }

    this.streamCmdReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, onComplete: this.getStreamCmdResponse.bind( this ), onTimeout: this.streamCmdQuery.bind( this ), autoCancel: true } );
}

function selectLayout( element )
{
    var cssFile = skinPath+'/views/css/'+$(element).get('value');
    if ( $('dynamicStyles') )
        $('dynamicStyles').destroy();
    new Asset.css( cssFile, { id: 'dynamicStyles' } );
    Cookie.write( 'zmMontageLayout', $(element).get('value'), { duration: 10*365 } );
}

function changeScale()
{
    var scale = $('scale').get('value');

    for ( var x = 0; x < monitors.length; x++ )
    {
        var monitor = monitors[x];
        var newWidth = ( monitorData[x].width * scale ) / SCALE_BASE;
        var newHeight = ( monitorData[x].height * scale ) / SCALE_BASE;
        /*Stream could be an applet so can't use moo tools*/ 
        var streamImg = document.getElementById( 'liveStream'+monitor.id );
        streamImg.style.width = newWidth + "px";
        streamImg.style.height = newHeight + "px";
    }
}

var monitors = new Array();
function initPage()
{
    for ( var i = 0; i < monitorData.length; i++ )
    {
        monitors[i] = new Monitor( i, monitorData[i].id, monitorData[i].connKey );
        var delay = Math.round( (Math.random()+0.5)*statusRefreshTimeout );
        monitors[i].start( delay );
    }
    selectLayout( $('layout') );
}

// Kick everything off
window.addEvent( 'domready', initPage );
