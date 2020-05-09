var events = {};

function showEvent(e) {
  eid = e.getAttribute('data-event-id');
  fid = e.getAttribute('data-frame-id');
  var url = '?view=event&eid='+eid+'&fid='+fid;
  url += filterQuery;
  window.location.href = url;

  //video element is blocking video elements elsewhere in chrome possible interaction with mouseover event?
  //FIXME unless an exact cause can be determined should store all video controls and do something to the other controls when we want to load a new video seek etc or whatever may block
  /*var vid= $('preview');
    vid.oncanplay=null;
  //    vid.currentTime=vid.currentTime-0.1;
  vid.pause();*/
}

function createEventHtml(zm_event, frame) {
  var eventHtml = new Element('div');

  if ( zm_event.Archived > 0 ) {
    eventHtml.addClass('archived');
  }

  new Element('p').inject(eventHtml).set('text', monitors[zm_event.MonitorId].Name);
  new Element('p').inject(eventHtml).set('text', zm_event.Name+(frame?('('+frame.FrameId+')'):''));
  new Element('p').inject(eventHtml).set('text', zm_event.StartTime+' - '+zm_event.Length+'s');
  new Element('p').inject(eventHtml).set('text', zm_event.Cause);
  if ( zm_event.Notes ) {
    new Element('p').inject(eventHtml).set('text', zm_event.Notes);
  }
  if ( zm_event.Archived > 0 ) {
    new Element('p').inject(eventHtml).set( 'text', archivedString);
  }

  return eventHtml;
}

function showEventDetail( eventHtml ) {
  $('instruction').addClass( 'hidden' );
  $('eventData').empty();
  $('eventData').adopt( eventHtml );
  $('eventData').removeClass( 'hidden' );
}

function eventDataResponse( respObj, respText ) {
  var zm_event = respObj.event;
  if ( !zm_event ) {
    console.log('Null event');
    return;
  }
  events[zm_event.Id] = zm_event;

  if ( respObj.loopback ) {
    requestFrameData(zm_event.Id, respObj.loopback);
  }
}

function frameDataResponse( respObj, respText ) {
  var frame = respObj.frameimage;
  if ( !frame.FrameId ) {
    console.log('Null frame');
    return;
  }

  var zm_event = events[frame.EventId];
  if ( !zm_event ) {
    console.error('No event '+frame.eventId+' found');
    return;
  }

  if ( !zm_event['frames'] ) {
    console.log("No frames data in event response");
    console.log(zm_event);
    console.log(respObj);
    zm_event['frames'] = {};
  }

  zm_event['frames'][frame.FrameId] = frame;
  zm_event['frames'][frame.FrameId]['html'] = createEventHtml( zm_event, frame );

  showEventData(frame.EventId, frame.FrameId);
}

function showEventData(eventId, frameId) {
  if ( events[eventId] ) {
    var zm_event = events[eventId];
    if ( zm_event['frames'] ) {
      if ( zm_event['frames'][frameId] ) {
        showEventDetail( zm_event['frames'][frameId]['html'] );
        var imagePath = 'index.php?view=image&eid='+eventId+'&fid='+frameId;
        var videoName = zm_event.DefaultVideo;
        loadEventImage( imagePath, eventId, frameId, zm_event.Width, zm_event.Height, zm_event.Frames/zm_event.Length, videoName, zm_event.Length, zm_event.StartTime, monitors[zm_event.MonitorId]);
        return;
      } else {
        console.log('No frames for ' + frameId);
      }
    } else {
      console.log('No frames');
    }
  } else {
    console.log('No event for ' + eventId);
  }
}

var eventQuery = new Request.JSON({
  url: thisUrl,
  method: 'get',
  timeout: AJAX_TIMEOUT,
  link: 'cancel',
  onSuccess: eventDataResponse
});

var frameQuery = new Request.JSON({
  url: thisUrl,
  method: 'get',
  timeout: AJAX_TIMEOUT,
  link: 'cancel',
  onSuccess: frameDataResponse
});

function requestFrameData( eventId, frameId ) {
  if ( !events[eventId] ) {
    eventQuery.options.data = "view=request&request=status&entity=event&id="+eventId+"&loopback="+frameId;
    eventQuery.send();
  } else {
    frameQuery.options.data = "view=request&request=status&entity=frameimage&id[0]="+eventId+"&id[1]="+frameId;
    frameQuery.send();
  }
}

function previewEvent(slot) {
  eventId = slot.getAttribute('data-event-id');
  frameId = slot.getAttribute('data-frame-id');
  if ( events[eventId] ) {
    showEventData(eventId, frameId);
  } else {
    requestFrameData(eventId, frameId);
  }
}

function loadEventImage( imagePath, eid, fid, width, height, fps, videoName, duration, startTime, Monitor ) {
  var vid = $('preview');
  var imageSrc = $('imageSrc');
  if ( videoName && vid ) {
    vid.show();
    imageSrc.hide();
    var newsource=imagePath.slice(0, imagePath.lastIndexOf('/'))+'/'+videoName;
    //console.log(newsource);
    //console.log(sources[0].src.slice(-newsource.length));
    if ( newsource != vid.currentSrc.slice(-newsource.length) || vid.readyState == 0 ) {
      //console.log("loading new");
      //it is possible to set a long source list here will that be unworkable?
      var sources = vid.getElementsByTagName('source');
      sources[0].src = newsource;
      var tracks = vid.getElementsByTagName('track');
      if (tracks.length) {
        tracks[0].parentNode.removeChild(tracks[0]);
      }
      vid.load();
      addVideoTimingTrack(vid, Monitor.LabelFormat, Monitor.Name, duration, startTime);
      vid.currentTime = fid/fps;
    } else {
      if ( ! vid.seeking ) {
        vid.currentTime=fid/fps;
      }
    }
  } else {
    if ( vid ) vid.hide();
    imageSrc.show();
    imageSrc.setProperty('src', imagePath);
    imageSrc.setAttribute('data-event-id', eid);
    imageSrc.setAttribute('data-frame-id', fid);
    imageSrc.onclick=window['showEvent'].bind(imageSrc, imageSrc);
  }

  var eventData = $('eventData');
  eventData.removeEvent('click');
  eventData.addEvent('click', showEvent.pass());
}

function tlZoomBounds( minTime, maxTime ) {
  location.replace('?view='+currentView+filterQuery+'&minTime='+minTime+'&maxTime='+maxTime);
}

function tlZoomOut() {
  location.replace('?view='+currentView+filterQuery+'&midTime='+midTime+'&range='+zoomout_range);
}

function tlPanLeft() {
  location.replace('?view='+currentView+filterQuery+'&midTime='+minTime+'&range='+range);
}
function tlPanRight() {
  location.replace('?view='+currentView+filterQuery+'&midTime='+maxTime+'&range='+range);
}

window.addEventListener("DOMContentLoaded", function() {
  document.querySelectorAll("div.event").forEach(function(el) {
    el.onclick = window[el.getAttribute('data-on-click-this')].bind(el, el);
    el.onmouseover = window[el.getAttribute('data-on-mouseover-this')].bind(el, el);
  });
  document.querySelectorAll("div.activity").forEach(function(el) {
    el.onclick = window[el.getAttribute('data-on-click-this')].bind(el, el);
    el.onmouseover = window[el.getAttribute('data-on-mouseover-this')].bind(el, el);
  });
});

