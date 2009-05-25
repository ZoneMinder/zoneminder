function setButtonState( element, butClass )
{
    element.className = butClass;
    element.disabled = (butClass != 'inactive');
}

function changeScale()
{
    var scale = $('scale').get('value');
    var baseWidth = event.Width;
    var baseHeight = event.Height;
    var newWidth = ( baseWidth * scale ) / SCALE_BASE;
    var newHeight = ( baseHeight * scale ) / SCALE_BASE;

    streamScale( scale );

    /*Stream could be an applet so can't use moo tools*/ 
    var streamImg = document.getElementById('evtStream');
    streamImg.style.width = newWidth + "px";
    streamImg.style.height = newHeight + "px";
}

function changeReplayMode()
{
    var replayMode = $('replayMode').get('value');

    Cookie.write( 'replayMode', replayMode, { duration: 10*365 })

    refreshWindow();
}

var streamParms = "view=request&request=stream&connkey="+connKey;
var streamCmdTimer = null;

var streamStatus = null;
var lastEventId = 0;

function getCmdResponse( respObj, respText )
{
    if ( checkStreamForErrors( "getCmdResponse" ,respObj ) )
        return;

    if ( streamCmdTimer )
        streamCmdTimer = $clear( streamCmdTimer );

    streamStatus = respObj.status;

    var eventId = streamStatus.event;
    if ( eventId != lastEventId )
    {
        eventQuery( eventId );
        lastEventId = eventId;
    }
    if ( streamStatus.paused == true )
    {
        $('modeValue').set( 'text', "Paused" );
        $('rate').addClass( 'hidden' );
        streamPause( false );
    }
    else 
    {
        $('modeValue').set( 'text', "Replay" );
        $('rateValue').set( 'text', streamStatus.rate );
        $('rate').removeClass( 'hidden' );
        streamPlay( false );
    }
    $('progressValue').set( 'text', secsToTime( parseInt(streamStatus.progress) ) );
    $('zoomValue').set( 'text', streamStatus.zoom );
    if ( streamStatus.zoom == "1.0" )
        setButtonState( $('zoomOutBtn'), 'unavail' );
    else
        setButtonState( $('zoomOutBtn'), 'inactive' );

    updateProgressBar();

    streamCmdTimer = streamQuery.delay( streamTimeout );
}

function streamPause( action )
{
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'unavail' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'unavail' );
    if ( action )
    {
        var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_PAUSE, onSuccess: getCmdResponse } );
        streamReq.send();
    }
}

function streamPlay( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    if (streamStatus)
        setButtonState( $('playBtn'), streamStatus.rate==1?'active':'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'unavail' );
    setButtonState( $('slowRevBtn'), 'unavail' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_PLAY, onSuccess: getCmdResponse } );
        streamReq.send();
    }
}

function streamFastFwd( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'unavail' );
    setButtonState( $('slowRevBtn'), 'unavail' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_FASTFWD, onSuccess: getCmdResponse } );
        streamReq.send();
    }
}

function streamSlowFwd( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'unavail' );
    setButtonState( $('slowFwdBtn'), 'active' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'unavail' );
    if ( action )
    {
        var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_SLOWFWD, onSuccess: getCmdResponse } );
        streamReq.send();
    }
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
}

function streamSlowRev( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'unavail' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'active' );
    setButtonState( $('fastRevBtn'), 'unavail' );
    if ( action )
    {
        var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_SLOWREV, onSuccess: getCmdResponse } );
        streamReq.send();
    }
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('slowRevBtn'), 'inactive' );
}

function streamFastRev( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'unavail' );
    setButtonState( $('slowRevBtn'), 'unavail' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_FASTREV, onSuccess: getCmdResponse } );
        streamReq.send();
    }
}

function streamPrev( action )
{
    streamPlay( false );
    if ( action )
    {
        var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_PREV, onSuccess: getCmdResponse } );
        streamReq.send();
    }
}

function streamNext( action )
{
    streamPlay( false );
    if ( action )
    {
        var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_NEXT, onSuccess: getCmdResponse } );
        streamReq.send();
    }
}

function streamZoomIn( x, y )
{
    var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_ZOOMIN+"&x="+x+"&y="+y, onSuccess: getCmdResponse } );
    streamReq.send();
}

function streamZoomOut()
{
    var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_ZOOMOUT, onSuccess: getCmdResponse } );
    streamReq.send();
}

function streamScale( scale )
{
    var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_SCALE+"&scale="+scale, onSuccess: getCmdResponse } );
    streamReq.send();
}

function streamPan( x, y )
{
    var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_PAN+"&x="+x+"&y="+y, onSuccess: getCmdResponse } );
    streamReq.send();
}

function streamSeek( offset )
{
    var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_SEEK+"&offset="+offset, onSuccess: getCmdResponse } );
    streamReq.send();
}

function streamQuery()
{       
    var streamReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_QUERY, onSuccess: getCmdResponse } );
    streamReq.send();
}       

var slider = null;
var scroll = null;

function getEventResponse( respObj, respText )
{
    if ( checkStreamForErrors( "getEventResponse", respObj ) )
        return;

    event = respObj.event;
    if ( !$('eventStills').hasClass( 'hidden' ) && currEventId != event.Id )
        resetEventStills();
    currEventId = event.Id;

    $('dataId').set( 'text', event.Id );
    if ( event.Notes )
    {
        $('dataCause').setProperty( 'title', event.Notes );
    }
    else
    {
        $('dataCause').setProperty( 'title', causeString );
    }
    $('dataCause').set( 'text', event.Cause );
    $('dataTime').set( 'text', event.StartTime );
    $('dataDuration').set( 'text', event.Length );
    $('dataFrames').set( 'text', event.Frames+"/"+event.AlarmFrames );
    $('dataScore').set( 'text', event.TotScore+"/"+event.AvgScore+"/"+event.MaxScore );
    $('eventName').setProperty( 'value', event.Name );

    if ( parseInt(event.Archived) )
    {
        $('archiveEvent').addClass( 'hidden' );
        $('unarchiveEvent').removeClass( 'hidden' );
    }
    else
    {
        $('archiveEvent').removeClass( 'hidden' );
        $('unarchiveEvent').addClass( 'hidden' );
    }
    //var eventImg = $('eventImage');
    //eventImg.setStyles( { 'width': event.width, 'height': event.height } );
    drawProgressBar();
    nearEventsQuery( event.Id );
}

function eventQuery( eventId )
{
    var eventParms = "view=request&request=status&entity=event&id="+eventId;
    var eventReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: eventParms, onSuccess: getEventResponse } );
    eventReq.send();
}

var prevEventId = 0;
var nextEventId = 0;

function getNearEventsResponse( respObj, respText )
{
    if ( checkStreamForErrors( "getNearEventsResponse", respObj ) )
        return;
    prevEventId = respObj.nearevents.PrevEventId;
    nextEventId = respObj.nearevents.NextEventId;

    $('prevEventBtn').disabled = !prevEventId;
    $('nextEventBtn').disabled = !nextEventId;
}

function nearEventsQuery( eventId )
{
    var parms = "view=request&request=status&entity=nearevents&id="+eventId;
    var query = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: parms, onSuccess: getNearEventsResponse } );
    query.send();
}

var frameBatch = 40;

function loadEventThumb( event, frame, loadImage )
{
    var thumbImg = $('eventThumb'+frame.FrameId);
    if ( !thumbImg )
    {
        console.error( "No holder found for frame "+frame.FrameId );
        return;
    }
    var img = new Asset.image( frame.Image.imagePath,
        {
            'onload': ( function( loadImage )
                {
                    thumbImg.setProperty( 'src', img.getProperty( 'src' ) );
                    thumbImg.removeClass( 'placeholder' );
                    thumbImg.setProperty( 'class', frame.Type=='Alarm'?'alarm':'normal' );
                    thumbImg.setProperty( 'title', frame.FrameId+' / '+((frame.Type=='Alarm')?frame.Score:0) );
                    thumbImg.removeEvents( 'click' );
                    thumbImg.addEvent( 'click', function() { locateImage( frame.FrameId, true ); } );
                    if ( loadImage )
                        loadEventImage( event, frame );
                } ).pass( loadImage )
        }
    );
}

function updateStillsSizes( noDelay )
{
    var containerDim = $('eventThumbs').getSize();

    var containerWidth = containerDim.x;
    var containerHeight = containerDim.y;
    var popupWidth = parseInt($('eventImage').getStyle( 'width' ));
    var popupHeight = parseInt($('eventImage').getStyle( 'height' ));

    var left = (containerWidth - popupWidth)/2;
    if ( left < 0 ) left = 0;
    var top = (containerHeight - popupHeight)/2;
    if ( top < 0 ) top = 0;
    if ( popupHeight == 0 && !noDelay ) // image not yet loaded lets give it another second
    {
        updateStillsSizes.pass( true ).delay( 50 );
        return;
    }
    $('eventImagePanel').setStyles( {
        'left': left,
        'top': top
    } );
}

function loadEventImage( event, frame )
{
    console.debug( "Loading "+event.Id+"/"+frame.FrameId );
    var eventImg = $('eventImage');
    var thumbImg = $('eventThumb'+frame.FrameId);
    if ( eventImg.getProperty( 'src' ) != thumbImg.getProperty( 'src' ) )
    {
        var eventImagePanel = $('eventImagePanel');

        if ( eventImagePanel.getStyle( 'display' ) != 'none' )
        {
            var lastThumbImg = $('eventThumb'+eventImg.getProperty( 'alt' ));
            lastThumbImg.removeClass('selected');
            lastThumbImg.setOpacity( 1.0 );
        }

        eventImg.setProperties( {
            'class': frame.Type=='Alarm'?'alarm':'normal',
            'src': thumbImg.getProperty( 'src' ),
            'title': thumbImg.getProperty( 'title' ),
            'alt': thumbImg.getProperty( 'alt' ),
            'width': event.Width,
            'height': event.Height
        } );
        $('eventImageBar').setStyle( 'width', event.Width );
        if ( frame.Type=='Alarm' )
            $('eventImageStats').removeClass( 'hidden' );
        else
            $('eventImageStats').addClass( 'hidden' );
        thumbImg.addClass( 'selected' );
        thumbImg.setOpacity( 0.5 );

        if ( eventImagePanel.getStyle( 'display' ) == 'none' )
        {
            eventImagePanel.setOpacity( 0 );
            updateStillsSizes();
            eventImagePanel.setStyle( 'display', 'block' );
            new Fx.Tween( eventImagePanel, { duration: 500, transition: Fx.Transitions.Sine } ).start( 'opacity', 0, 1 );
        }

        $('eventImageNo').set( 'text', frame.FrameId );
        $('prevImageBtn').disabled = (frame.FrameId==1);
        $('nextImageBtn').disabled = (frame.FrameId==event.Frames);
    }
}

function hideEventImageComplete()
{
    var eventImg = $('eventImage');
    var thumbImg = $('eventThumb'+$('eventImage').getProperty( 'alt' ));
    thumbImg.removeClass('selected');
    thumbImg.setOpacity( 1.0 );
    $('prevImageBtn').disabled = true;
    $('nextImageBtn').disabled = true;
    $('eventImagePanel').setStyle( 'display', 'none' );
    $('eventImageStats').addClass( 'hidden' );
}

function hideEventImage()
{
    if ( $('eventImagePanel').getStyle( 'display' ) != 'none' )
        new Fx.Tween( $('eventImagePanel'), { duration: 500, transition: Fx.Transitions.Sine, onComplete: hideEventImageComplete } ).start( 'opacity', 1, 0 );
}

function resetEventStills()
{
    hideEventImage();
    $('eventThumbs').empty();
    if ( true || !slider )
    {
        slider = new Slider( $('thumbsSlider'), $('thumbsKnob'), {
            /*steps: event.Frames,*/
            onChange: function( step )
            {
                if ( !step )
                    step = 0;
                var fid = parseInt((step * event.Frames)/this.options.steps);
                if ( fid < 1 )
                    fid = 1;
                else if ( fid > event.Frames )
                    fid = event.Frames;
                checkFrames( event.Id, fid );
                scroll.toElement( 'eventThumb'+fid );
            }
        } ).set( 0 );
    }
    if ( $('eventThumbs').getStyle( 'height' ).match( /^\d+/ ) < (parseInt(event.Height)+80) )
        $('eventThumbs').setStyle( 'height', (parseInt(event.Height)+80)+'px' );
}

function getFrameResponse( respObj, respText )
{
    if ( checkStreamForErrors( "getFrameResponse", respObj ) )
        return;

    var frame = respObj.frameimage;

    if ( !event )
    {
        console.error( "No event "+frame.EventId+" found" );
        return;
    }

    if ( !event['frames'] )
        event['frames'] = new Hash();

    event['frames'][frame.FrameId] = frame;
    
    loadEventThumb( event, frame, respObj.loopback=="true" );
}

function frameQuery( eventId, frameId, loadImage )
{
    var parms = "view=request&request=status&entity=frameimage&id[0]="+eventId+"&id[1]="+frameId+"&loopback="+loadImage;
    var req = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: parms, onSuccess: getFrameResponse } );
    req.send();
}

var currFrameId = null;

function checkFrames( eventId, frameId, loadImage )
{
    if ( !event )
    {
        console.error( "No event "+eventId+" found" );
        return;
    }

    if ( !event['frames'] )
        event['frames'] = new Hash();

    currFrameId = frameId;

    var loFid = frameId - frameBatch/2;
    if ( loFid < 1 )
        loFid = 1;
    var hiFid = loFid + (frameBatch-1);
    if ( hiFid > event.Frames )
        hiFid = event.Frames;

    for ( var fid = loFid; fid <= hiFid; fid++ )
    {
        if ( !$('eventThumb'+fid) )
        {
            var img = new Element( 'img', { 'id': 'eventThumb'+fid, 'src': 'graphics/transparent.gif', 'alt': fid, 'class': 'placeholder' } );
            img.addEvent( 'click', function () { event['frames'][fid] = null; checkFrames( eventId, fid ) } );
            frameQuery( eventId, fid, loadImage && (fid == frameId) );
            var imgs = $('eventThumbs').getElements( 'img' );
            var injected = false;
            if ( fid < imgs.length )
            {
                img.injectBefore( imgs[fid-1] );
                injected = true;
            }
            else
            {
                injected = imgs.some(
                    function( thumbImg, index )
                    {
                        if ( parseInt(img.getProperty( 'alt' )) < parseInt(thumbImg.getProperty( 'alt' )) )
                        {
                            img.injectBefore( thumbImg );
                            return( true );
                        }
                        return( false );
                    }
                );
            }
            if ( !injected )
            {
                img.injectInside( $('eventThumbs') );
            }
            var scale = parseInt(img.getStyle('height'));
            img.setStyles( {
                'width': parseInt((event.Width*scale)/100),
                'height': parseInt((event.Height*scale)/100)
            } );
        }
        else if ( event['frames'][fid] )
        {
            if ( loadImage && (fid == frameId) )
            {
                loadEventImage( event, event['frames'][fid], loadImage );
            }
        }
    }
    $('prevThumbsBtn').disabled = (frameId==1);
    $('nextThumbsBtn').disabled = (frameId==event.Frames);
}

function locateImage( frameId, loadImage )
{
    if ( slider )
        slider.fireEvent( 'tick', slider.toPosition( parseInt((frameId-1)*slider.options.steps/event.Frames) ));
    checkFrames( event.Id, frameId, loadImage );
    scroll.toElement( 'eventThumb'+frameId );
}

function prevImage()
{
    if ( currFrameId > 1 )
        locateImage( parseInt(currFrameId)-1, true );
}

function nextImage()
{
    if ( currFrameId < event.Frames )
        locateImage( parseInt(currFrameId)+1, true );
}

function prevThumbs()
{
    if ( currFrameId > 1 )
        locateImage( parseInt(currFrameId)>10?(parseInt(currFrameId)-10):1, $('eventImagePanel').getStyle('display')!="none" );
}

function nextThumbs()
{
    if ( currFrameId < event.Frames )
        locateImage( parseInt(currFrameId)<(event.Frames-10)?(parseInt(currFrameId)+10):event.Frames, $('eventImagePanel').getStyle('display')!="none" );
}

function prevEvent()
{
    if ( prevEventId )
    {
        eventQuery( prevEventId );
        streamPrev( true );
    }
}

function nextEvent()
{
    if ( nextEventId )
    {
        eventQuery( nextEventId );
        streamNext( true );
    }
}

function getActResponse( respObj, respText )
{
    if ( checkStreamForErrors( "getActResponse", respObj ) )
        return;

    if ( respObj.refreshParent )
        refreshParentWindow();

    if ( respObj.refreshEvent )
        eventQuery( event.Id );
}

function actQuery( action, parms )
{
    var actParms = "view=request&request=event&id="+event.Id+"&action="+action;
    if ( parms != null )
    {
        actParms += "&"+Hash.toQueryString( parms );
    }
    var actReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, data: actParms, onSuccess: getActResponse } );
    actReq.send();
}

function deleteEvent()
{
    actQuery( 'delete' );
    streamNext( true );
}

function renameEvent()
{
    var newName = $('eventName').get('value');
    actQuery( 'rename', { eventName: newName } );
}

function editEvent()
{
    createPopup( '?view=eventdetail&eid='+event.Id, 'zmEventDetail', 'eventdetail' );
}

function exportEvent()
{
    createPopup( '?view=export&eid='+event.Id, 'zmExport', 'export' );
}

function archiveEvent()
{
    actQuery( 'archive' );
}

function unarchiveEvent()
{
    actQuery( 'unarchive' );
}

function showEventFrames()
{
    createPopup( '?view=frames&eid='+event.Id, 'zmFrames', 'frames' );
}

function showStream()
{
    $('eventStills').addClass( 'hidden' );
    $('eventStream').removeClass( 'hidden' );
    $('streamEvent').addClass( 'hidden' );
    $('stillsEvent').removeClass( 'hidden' );

    //$(window).removeEvent( 'resize', updateStillsSizes );
}

function showStills()
{
    $('eventStream').addClass( 'hidden' );
    $('eventStills').removeClass( 'hidden' );
    $('stillsEvent').addClass( 'hidden' );
    $('streamEvent').removeClass( 'hidden' );
    streamPause( true );
    if ( !scroll )
    {
        scroll = new Fx.Scroll( 'eventThumbs', {
            wait: false,
            duration: 500,
            offset: { 'x': 0, 'y': 0 },
            transition: Fx.Transitions.Quad.easeInOut
            }
        );
    }
    resetEventStills();
    $(window).addEvent( 'resize', updateStillsSizes );
}

function showFrameStats()
{
    var fid = $('eventImageNo').get('text');
    createPopup( '?view=stats&eid='+event.Id+'&fid='+fid, 'zmStats', 'stats', event.Width, event.Height );
}

function videoEvent()
{
    createPopup( '?view=video&eid='+event.Id, 'zmVideo', 'video', event.Width, event.Height );
}

function drawProgressBar()
{
    var barWidth = 0;
    $('progressBar').addClass( 'invisible' );
    var cells = $('progressBar').getElements( 'div' );
    var cellWidth = parseInt( event.Width/$$(cells).length );
    $$(cells).forEach(
        function( cell, index )
        {
            if ( index == 0 )
                $(cell).setStyles( { 'left': barWidth, 'width': cellWidth, 'borderLeft': 0 } );
            else
                $(cell).setStyles( { 'left': barWidth, 'width': cellWidth } );
            var offset = parseInt((index*event.Length)/$$(cells).length);
            $(cell).setProperty( 'title', '+'+secsToTime(offset)+'s' );
            $(cell).removeEvent( 'click' );
            $(cell).addEvent( 'click', function(){ streamSeek( offset ); } );
            barWidth += $(cell).getCoordinates().width;
        }
    );
    $('progressBar').setStyle( 'width', barWidth );
    $('progressBar').removeClass( 'invisible' );
}

function updateProgressBar()
{
    if ( event && streamStatus )
    {
        var cells = $('progressBar').getElements( 'div' );
        var completeIndex = parseInt((($$(cells).length+1)*streamStatus.progress)/event.Length);
        $$(cells).forEach(
            function( cell, index )
            {
                if ( index < completeIndex )
                {
                    if ( !$(cell).hasClass( 'complete' ) )
                    {
                        $(cell).addClass( 'complete' );
                    }
                }
                else
                {
                    if ( $(cell).hasClass( 'complete' ) )
                    {
                        $(cell).removeClass( 'complete' );
                    }
                }
            }
        );
    }
}

function handleClick( event )
{
    var target = event.target;
    var x = event.page.x - $(target).getLeft();
    var y = event.page.y - $(target).getTop();
    
    if ( event.shift )
        streamPan( x, y );
    else
        streamZoomIn( x, y );
}

function initPage()
{
    streamCmdTimer = streamQuery.delay( 250 );
    eventQuery.pass( event.Id ).delay( 500 );

    if ( canStreamNative )
    {
        var streamImg = $('imageFeed').getElement('img');
        if ( !streamImg )
            streamImg = $('imageFeed').getElement('object');
        $(streamImg).addEvent( 'click', handleClick.bindWithEvent( $(streamImg) ) );
    }
}

// Kick everything off
window.addEvent( 'domready', initPage );
