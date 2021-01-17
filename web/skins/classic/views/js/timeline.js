var events = {};

function showEvent(e) {
  var eid = e.getAttribute('data-event-id');
  var fid = e.getAttribute('data-frame-id');
  var url = '?view=event&eid='+eid+'&fid='+fid+filterQuery;

  window.location.href = url;
}

function createEventHtml(zm_event, frame) {
  var div = $j('<div>');

  if ( zm_event.Archived ) div.addClass('archived');

  var mName = $j('<p>').text(monitors[zm_event.MonitorId].Name);
  var mEvent = $j('<p>').text(zm_event.Name+(frame?('('+frame.FrameId+')'):''));
  var mDateTime = $j('<p>').text(zm_event.StartDateTime+' - '+zm_event.Length+'s');
  var mCause = $j('<p>').text(zm_event.Cause);
  var mNotes = zm_event.Notes ? $j('<p>').text(zm_event.Notes) : '';
  var mArchived = zm_event.Archived ? $j('<p>').text(archivedString) : '';

  var data = div.append(mName, mEvent, mDateTime, mCause, mNotes, mArchived);

  return data;
}

function showEventDetail(eventHtml) {
  $j('#instruction').addClass('hidden');
  $j('#eventData').empty().append(eventHtml).removeClass('hidden');
}

function eventDataResponse(respObj, respText) {
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
    console.log('No frames data in event response');
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
        loadEventImage(imagePath, eventId, frameId);
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

function eventQuery(data) {
  $j.getJSON(thisUrl + '?view=request&request=status&entity=event', data)
      .done(eventDataResponse)
      .fail(logAjaxFail);
}

function frameQuery(data) {
  $j.getJSON(thisUrl + '?view=request&request=status&entity=frameimage', data)
      .done(frameDataResponse)
      .fail(logAjaxFail);
}

function requestFrameData( eventId, frameId ) {
  var data = {};

  if ( !events[eventId] ) {
    data.id = eventId;
    data.loopback = frameId;
    eventQuery(data);
  } else {
    data.id = [eventId, frameId];
    frameQuery(data);
  }
}

function previewEvent(slot) {
  eventId = slot.getAttribute('data-event-id');
  frameId = slot.getAttribute('data-frame-id');
  if ( events[eventId] && events[eventId]['frames'] && events[eventId]['frames'][frameId] ) {
    showEventData(eventId, frameId);
  } else {
    requestFrameData(eventId, frameId);
  }
}

function loadEventImage( imagePath, eid, fid ) {
  var eventData = $j('#eventData');
  var imageSrc = $j('#imageSrc');

  imageSrc.show();
  imageSrc.attr('src', imagePath);
  imageSrc.attr('data-event-id', eid);
  imageSrc.attr('data-frame-id', fid);
  imageSrc.off('click');
  imageSrc.click(function() {
    showEvent(this);
  });

  eventData.attr('data-event-id', eid);
  eventData.attr('data-frame-id', fid);
  eventData.off('click');
  eventData.click(function() {
    showEvent(this);
  });
}

function tlZoomBounds(event) {
  var target = event.target;
  var minTime = target.getAttribute('data-zoom-min-time');
  var maxTime = target.getAttribute('data-zoom-max-time');
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

function divDataOnClick() {
  // These look like the code in skin.js, but that code doesn't select for divs.
  document.querySelectorAll('div.event').forEach(function(el) {
    el.onclick = window[el.getAttribute('data-on-click-this')].bind(el, el);
    el.onmouseover = window[el.getAttribute('data-on-mouseover-this')].bind(el, el);
  });
  document.querySelectorAll('div.activity').forEach(function(el) {
    el.onclick = window[el.getAttribute('data-on-click-this')].bind(el, el);
    el.onmouseover = window[el.getAttribute('data-on-mouseover-this')].bind(el, el);
  });
  document.querySelectorAll('div.tlzoom').forEach(function(el) {
    el.onclick = function(ev) {
      window[el.getAttribute('data-on-click')](ev);
    };
  });
}

function initPage() {
  var backBtn = $j('#backBtn');

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });
  // Manage the LIST Button
  document.getElementById("listBtn").addEventListener("click", function onListClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=events'+filterQuery);
  });

  // Bind the data-on-click attributes associated with a div
  divDataOnClick();
}

$j(document).ready(function() {
  initPage();
});
