var events = new Object();

function showEvent( eid, fid, width, height )
{    
    var url = '?view=event&eid='+eid+'&fid='+fid;
    url += filterQuery;
    var pop=createPopup( url, 'zmEvent', 'event', width, height );
    pop.vid=$('preview');
    
    //video element is blocking video elements elsewhere in chrome possible interaction with mouseover event?
    //FIXME unless an exact cause can be determined should store all video controls and do something to the other controls when we want to load a new video seek etc or whatever may block
    /*var vid= $('preview');
    vid.oncanplay=null;
//    vid.currentTime=vid.currentTime-0.1;
    vid.pause();*/
}

function createEventHtml( event, frame )
{
    var eventHtml = new Element( 'div' );

    if ( event.Archived > 0 )
        eventHtml.addClass( 'archived' );

    new Element( 'p' ).inject( eventHtml ).set( 'text', monitorNames[event.MonitorId] );
    new Element( 'p' ).inject( eventHtml ).set( 'text', event.Name+(frame?("("+frame.FrameId+")"):"") );
    new Element( 'p' ).inject( eventHtml ).set( 'text', event.StartTime+" - "+event.Length+"s" );
    new Element( 'p' ).inject( eventHtml ).set( 'text', event.Cause );
    if ( event.Notes )
        new Element( 'p' ).inject( eventHtml ).set( 'text', event.Notes );
    if ( event.Archived > 0 )
        new Element( 'p' ).inject( eventHtml ).set( 'text', archivedString );

    return( eventHtml );
}

function showEventDetail( eventHtml )
{
    $('instruction').addClass( 'hidden' );
    $('eventData').empty();
    $('eventData').adopt( eventHtml );
    $('eventData').removeClass( 'hidden' );
}

function eventDataResponse( respObj, respText )
{
    var event = respObj.event;
    if ( !event )
    {
        console.log( "Null event" );
        return;
    }
    events[event.Id] = event;

    if ( respObj.loopback )
    {
        requestFrameData( event.Id, respObj.loopback );
    }
}

function frameDataResponse( respObj, respText )
{
    var frame = respObj.frameimage;
    if ( !frame.FrameId )
    {
        console.log( "Null frame" );
        return;
    }

    var event = events[frame.EventId];
    if ( !event )
    {
        console.error( "No event "+frame.eventId+" found" );
        return;
    }

    if ( !event['frames'] )
        event['frames'] = new Object();

    event['frames'][frame.FrameId] = frame;
    event['frames'][frame.FrameId]['html'] = createEventHtml( event, frame );
    showEventDetail( event['frames'][frame.FrameId]['html'] );
    loadEventImage( frame.Image.imagePath, event.Id, frame.FrameId, event.Width, event.Height, event.Frames/event.Length );
}

var eventQuery = new Request.JSON( { url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: eventDataResponse } );
var frameQuery = new Request.JSON( { url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: frameDataResponse } );

function requestFrameData( eventId, frameId )
{
    if ( !events[eventId] )
    {
        eventQuery.options.data = "view=request&request=status&entity=event&id="+eventId+"&loopback="+frameId;
        eventQuery.send();
    }
    else
    {
        frameQuery.options.data = "view=request&request=status&entity=frameimage&id[0]="+eventId+"&id[1]="+frameId;
        frameQuery.send();
    }
}

function previewEvent( eventId, frameId )
{
    if ( events[eventId] )
    {
        if ( events[eventId]['frames'] )
        {
            if ( events[eventId]['frames'][frameId] )
            {
                showEventDetail( events[eventId]['frames'][frameId]['html'] );
                loadEventImage( events[eventId].frames[frameId].Image.imagePath, eventId, frameId, events[eventId].Width, events[eventId].Height, events[eventId].Frames/events[eventId].Length );
                return;
            }
        }
    }
    requestFrameData( eventId, frameId );
}

function loadEventImage( imagePath, eid, fid, width, height, fps )
{
    console.log(fps);
//    console.log(imagePrefix);
//    console.log(imagePath);
    
    //console.log(fid/25.0);
    var vid= $('preview');
    var newsource=imagePrefix+imagePath.slice(0,imagePath.lastIndexOf('/'))+"/event.mp4";
    //console.log(newsource);
    //console.log(sources[0].src.slice(-newsource.length));
    if(newsource!=vid.currentSrc.slice(-newsource.length) || vid.readyState==0)
    {
        //console.log("loading new");
        //it is possible to set a long source list here will that be unworkable?
        var sources = vid.getElementsByTagName('source');
        sources[0].src=newsource;
        vid.load();
        vid.oncanplay=function(){    vid.currentTime=fid/fps;} //25.0
    }
    else
    {
        vid.oncanplay=null;//console.log("canplay");}
        if(!vid.seeking)
            vid.currentTime=fid/fps;//25.0;
    }
    
   /* var imageSrc = $('imageSrc');
    imageSrc.setProperty( 'src', imagePrefix+imagePath );
    imageSrc.removeEvent( 'click' );
    imageSrc.addEvent( 'click', showEvent.pass( [ eid, fid, width, height ] ) );
    var eventData = $('eventData');
    eventData.removeEvent( 'click' );
    eventData.addEvent( 'click', showEvent.pass( [ eid, fid, width, height ] ) );*/
}

function tlZoomBounds( minTime, maxTime )
{
    console.log( "Zooming" );
    window.location = '?view='+currentView+filterQuery+'&minTime='+minTime+'&maxTime='+maxTime;
}

function tlZoomRange( midTime, range )
{
    window.location = '?view='+currentView+filterQuery+'&midTime='+midTime+'&range='+range;
}

function tlPan( midTime, range )
{
    window.location = '?view='+currentView+filterQuery+'&midTime='+midTime+'&range='+range;
}
