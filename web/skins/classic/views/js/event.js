function setButtonState( element, butClass )
{
    element.className = butClass;
    element.disabled = (butClass != 'inactive');
}

function changeScale()
{
    var scale = $('scale').getValue();
    var baseWidth = event.Width;
    var baseHeight = event.Height;
    var newWidth = ( baseWidth * scale ) / SCALE_BASE;
    var newHeight = ( baseHeight * scale ) / SCALE_BASE;

    streamScale( scale );

    var streamImg = $('imageFeed').getElement('img');
    $(streamImg).setStyles( { 'width': newWidth, 'height': newHeight } );
}

function changeReplayMode()
{
    var replayMode = $('replayMode').getValue();

    Cookie.set( 'replayMode', replayMode, { duration: 10*365 })

    refreshWindow();
}

var streamParms = "view=request&request=stream&connkey="+connKey;
var streamCmdTimer = null;

var streamStatus = null;
var lastEventId = 0;

function getCmdResponse( respText )
{
    if ( streamCmdTimer )
        streamCmdTimer = $clear( streamCmdTimer );

    if ( !respText )
        return;
    var response = Json.evaluate( respText );
    streamStatus = response.status;

    var eventId = streamStatus.event;
    if ( eventId != lastEventId )
    {
        eventQuery( eventId );
        lastEventId = eventId;
    }
    if ( streamStatus.paused == true )
    {
        $('modeValue').setHTML( "Paused" );
        $('rate').addClass( 'hidden' );
        streamPause( false );
    }
    else 
    {
        $('modeValue').setHTML( "Replay" );
        $('rateValue').setHTML( streamStatus.rate );
        $('rate').removeClass( 'hidden' );
        streamPlay( false );
    }
    $('progressValue').setHTML( secsToTime( parseInt(streamStatus.progress) ) );
    $('zoomValue').setHTML( streamStatus.zoom );
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
        var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_PAUSE, onComplete: getCmdResponse } );
        streamReq.request();
    }
}

function streamPlay( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), streamStatus.rate==1?'active':'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'unavail' );
    setButtonState( $('slowRevBtn'), 'unavail' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_PLAY, onComplete: getCmdResponse } );
        streamReq.request();
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
        var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_FASTFWD, onComplete: getCmdResponse } );
        streamReq.request();
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
        var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_SLOWFWD, onComplete: getCmdResponse } );
        streamReq.request();
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
        var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_SLOWREV, onComplete: getCmdResponse } );
        streamReq.request();
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
        var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_FASTREV, onComplete: getCmdResponse } );
        streamReq.request();
    }
}

function streamPrev( action )
{
    streamPlay( false );
    if ( action )
    {
        var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_PREV, onComplete: getCmdResponse } );
        streamReq.request();
    }
}

function streamNext( action )
{
    streamPlay( false );
    if ( action )
    {
        var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_NEXT, onComplete: getCmdResponse } );
        streamReq.request();
    }
}

function streamZoomIn( x, y )
{
    var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_ZOOMIN+"&x="+x+"&y="+y, onComplete: getCmdResponse } );
    streamReq.request();
}

function streamZoomOut()
{
    var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_ZOOMOUT, onComplete: getCmdResponse } );
    streamReq.request();
}

function streamScale( scale )
{
    var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_SCALE+"&scale="+scale, onComplete: getCmdResponse } );
    streamReq.request();
}

function streamPan( x, y )
{
    var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_PAN+"&x="+x+"&y="+y, onComplete: getCmdResponse } );
    streamReq.request();
}

function streamSeek( offset )
{
    var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_SEEK+"&offset="+offset, onComplete: getCmdResponse } );
    streamReq.request();
}

function streamQuery()
{       
    var streamReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: streamParms+"&command="+CMD_QUERY, onComplete: getCmdResponse } );
    streamReq.request();
}       

var slider = null;
var scroll = null;

function getEventResponse( respText )
{
    if ( respText == 'Ok' )
        return;
    var response = Json.evaluate( respText );

    event = response.event;
    if ( !$('eventStills').hasClass( 'hidden' ) && currEventId != event.Id )
        resetEventStills();
    currEventId = event.Id;

    $('dataId').setHTML( event.Id );
    if ( event.Notes )
    {
        $('dataCause').setProperty( 'title', event.Notes );
    }
    else
    {
        $('dataCause').setProperty( 'title', causeString );
    }
    $('dataCause').setHTML( event.Cause );
    $('dataTime').setHTML( event.StartTime );
    $('dataDuration').setHTML( event.Length );
    $('dataFrames').setHTML( event.Frames+"/"+event.AlarmFrames );
    $('dataScore').setHTML( event.TotScore+"/"+event.AvgScore+"/"+event.MaxScore );
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
    var eventReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: eventParms, onComplete: getEventResponse } );
    eventReq.request();
}

var prevEventId = 0;
var nextEventId = 0;

function getNearEventsResponse( respText )
{
    if ( respText == 'Ok' )
        return;
    var response = Json.evaluate( respText );

    prevEventId = response.nearevents.PrevEventId;
    nextEventId = response.nearevents.NextEventId;

    $('prevEventBtn').disabled = !prevEventId;
    $('nextEventBtn').disabled = !nextEventId;
}

function nearEventsQuery( eventId )
{
    var parms = "view=request&request=status&entity=nearevents&id="+eventId;
    var query = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: parms, onComplete: getNearEventsResponse } );
    query.request();
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
                    thumbImg.removeEvent( 'click' );
                    thumbImg.addEvent( 'click', function() { locateImage( frame.FrameId, true ); } );
                    if ( loadImage )
                        loadEventImage( event, frame );
                } ).pass( loadImage )
        }
    );
}

function updateStillsSizes()
{
    var containerDim = $('eventThumbs').getStyles( 'width', 'height' );
    var popupDim = $('eventImageFrame').getStyles( 'width', 'height' );
    console.log( containerDim );
    console.log( popupDim );

    var containerWidth = containerDim.width.match( /^\d+/ );
    var containerHeight = containerDim.height.match( /^\d+/ );
    //var popupWidth = popupDim.width.match( /^\d+/ );
    //var popupHeight = popupDim.height.match( /^\d+/ );
    var popupWidth = $('eventImage').width;
    var popupHeight = $('eventImage').height;

    var left = (containerWidth - popupWidth)/2;
    if ( left < 0 ) left = 0;
    var top = (containerHeight - popupHeight)/2;
    if ( top < 0 ) top = 0;

    console.log( "Top: "+top+", Left: "+left );

    $('eventImagePanel').setStyles( {
        'left': left,
        'top': top
    } );
}

function loadEventImage( event, frame )
{
    console.log( "Loading "+event.Id+"/"+frame.FrameId );
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
        thumbImg.addClass( 'selected' );
        thumbImg.setOpacity( 0.5 );

        if ( eventImagePanel.getStyle( 'display' ) == 'none' )
        {
            eventImagePanel.setOpacity( 0 );
            updateStillsSizes();
            eventImagePanel.setStyle( 'display', 'block' );
            new Fx.Style( eventImagePanel, 'opacity',{ duration: 500, transition: Fx.Transitions.sineInOut } ).start( 0, 1 );
        }

        $('eventImageNo').setText( frame.FrameId );
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
}

function hideEventImage()
{
    if ( $('eventImagePanel').getStyle( 'display' ) != 'none' )
        new Fx.Style( $('eventImagePanel'), 'opacity',{ duration: 500, transition: Fx.Transitions.sineInOut, onComplete: hideEventImageComplete } ).start( 1, 0 );
}

function resetEventStills()
{
    hideEventImage();
    $('eventThumbs').empty();
    if ( true || !slider )
    {
        slider = new Slider( $('thumbsSlider'), $('thumbsKnob'), {
            steps: event.Frames,
            onChange: function( step )
            {
                var fid = step + 1;
                console.log( 'FID:'+step );
                checkFrames( event.Id, fid );
                scroll.toElement( 'eventThumb'+fid );
            }
        } ).set( 0 );
    }
    console.log( "H1: "+$('eventThumbs').getStyle( 'height' ).match( /^\d+/ ) );
    console.log( "H2 :"+(event.Height+80) );
    if ( $('eventThumbs').getStyle( 'height' ).match( /^\d+/ ) < (parseInt(event.Height)+80) )
    {
        console.log( "Resizing" );
        $('eventThumbs').setStyle( 'height', (parseInt(event.Height)+80)+'px' );
    }
}

function getFrameResponse( respText )
{
    if ( respText == 'Ok' )
        return;
    var response = Json.evaluate( respText );

    var frame = response.frameimage;

    //console.log( 'Got response for frame '+frame.FrameId );

    if ( !event )
    {
        console.error( "No event "+frame.EventId+" found" );
        return;
    }

    if ( !event['frames'] )
        event['frames'] = new Object();

    event['frames'][frame.FrameId] = frame;
    
    loadEventThumb( event, frame, response.loopback=="true" );
}

function frameQuery( eventId, frameId, loadImage )
{
    var parms = "view=request&request=status&entity=frameimage&id[0]="+eventId+"&id[1]="+frameId+"&loopback="+loadImage;
    var req = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: parms, onComplete: getFrameResponse } );
    req.request();
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
        event['frames'] = new Object();

    currFrameId = frameId;

    var loFid = frameId - frameBatch/2;
    if ( loFid < 1 )
        loFid = 1;
    var hiFid = loFid + (frameBatch-1);
    if ( hiFid > event.Frames )
        hiFid = event.Frames;

    for ( var fid = loFid; fid <= hiFid; fid++ )
    {
        console.log( 'Checking frame '+fid );
        if ( !$('eventThumb'+fid) )
        {
            console.log( 'Creating frame placeholder '+fid );
            var img = new Element( 'img', { 'id': 'eventThumb'+fid, 'src': 'graphics/transparent.gif', 'alt': fid, 'class': 'placeholder' } );
            img.addEvent( 'click', function () { event['frames'][fid] = null; checkFrames( eventId, frameId ) } );
            frameQuery( eventId, fid, loadImage && (fid == frameId) );
            var imgs = $('eventThumbs').getElements( 'img' );
            var injected = false;
            if ( fid < imgs.length )
            {
                //console.log( "Injecting "+fid+" at "+(fid-1)+", length "+imgs.length );
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
                            //console.log( "Injecting "+fid+" at index "+index+", length "+imgs.length );
                            img.injectBefore( thumbImg );
                            return( true );
                        }
                        return( false );
                    }
                );
            }
            if ( !injected )
            {
                //console.log( "Injecting "+fid+", length "+imgs.length );
                img.injectInside( $('eventThumbs') );
            }
        }
        else if ( event['frames'][fid] )
        {
            if ( loadImage && (fid == frameId) )
            {
                loadEventImage( event, event['frames'][fid] );
            }
        }
    }
    $('prevThumbsBtn').disabled = (frameId==1);
    $('nextThumbsBtn').disabled = (frameId==event.Frames);
}

function locateImage( frameId, loadImage )
{
    slider.fireEvent( 'onTick', slider.toPosition( frameId-1 ));
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
        locateImage( currFrameId>10?(currFrameId-10):1, !$('eventImagePanel').hasClass( 'hidden' ) );
}

function nextThumbs()
{
    if ( currFrameId < event.Frames )
        locateImage( currFrameId<(event.Frames-10)?(currFrameId+10):event.Frames, !$('eventImagePanel').hasClass( 'hidden' ) );
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

function getActResponse( respText )
{
    if ( respText == 'Ok' )
        return;
    var response = Json.evaluate( respText );

    if ( response.refreshParent )
        refreshParentWindow();

    if ( response.refreshEvent )
        eventQuery( event.Id );
}

function actQuery( action, parms )
{
    var actParms = "view=request&request=event&id="+event.Id+"&action="+action;
    if ( parms != null )
    {
        actParms += "&"+Object.toQueryString( parms );
    }
    var actReq = new Ajax( thisUrl, { method: 'post', timeout: AJAX_TIMEOUT, data: actParms, onComplete: getActResponse } );
    actReq.request();
}

function deleteEvent()
{
    actQuery( 'delete' );
    streamNext( true );
}

function renameEvent()
{
    var newName = $('eventName').getValue();
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
