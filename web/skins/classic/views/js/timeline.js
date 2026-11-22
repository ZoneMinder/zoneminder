
// A bar made from several small neighbouring events lists one eventId:frameId
// per slot in data-slot-targets. Pick the one under the pointer.
function slotTarget(el, ev) {
  const target = {eid: el.getAttribute('data-event-id'), fid: el.getAttribute('data-frame-id')};
  const list = el.getAttribute('data-slot-targets');
  if (!list || !ev || typeof ev.clientX !== 'number') return target;
  const parts = list.split(',');
  const rect = el.getBoundingClientRect();
  if (!(rect.width > 0)) return target;
  let idx = Math.floor((ev.clientX - rect.left) / rect.width * parts.length);
  idx = Math.max(0, Math.min(parts.length - 1, idx));
  const pair = parts[idx].split(':');
  target.eid = pair[0];
  target.fid = pair[1];
  return target;
}

function showEvent(e, ev) {
  const t = slotTarget(e, ev);
  var eid = t.eid;
  var fid = t.fid;
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
  if (checkStreamForErrors('eventDataResponse', respObj)) return;
  var zm_event = respObj.event;

  if (!zm_event) {
    console.log('Null event');
    return;
  }
  events[zm_event.Id] = zm_event;

  if (respObj.loopback) {
    requestFrameData(zm_event.Id, respObj.loopback);
  }
}

function frameDataResponse(respObj, respText) {
  if (checkStreamForErrors('frameDataResponse', respObj)) return;
  var frame = respObj.frameimage;
  if (!frame.FrameId) {
    console.log('Null frame');
    return;
  }

  var zm_event = events[frame.EventId];
  if (!zm_event) {
    console.error('No event '+frame.eventId+' found');
    return;
  }

  if (!zm_event['frames']) {
    zm_event['frames'] = {};
  }

  zm_event['frames'][frame.FrameId] = frame;
  zm_event['frames'][frame.FrameId]['html'] = createEventHtml( zm_event, frame );

  showEventData(zm_event, frame.FrameId);
}

function showEventData(zm_event, frameId) {
  if (zm_event) {
    if (zm_event['frames']) {
      if (zm_event['frames'][frameId]) {
        $j('#instruction').hide();
        eventData = $j('#eventData'+zm_event.MonitorId);
        eventData.html(zm_event['frames'][frameId]['html']);
        eventData.show();
        var imagePath = 'index.php?view=image&eid='+zm_event.Id+'&fid='+frameId;
        loadEventImage(imagePath, zm_event, frameId);
        return;
      } else {
        console.log('No frames for ' + frameId);
      }
    } else {
      console.log('No frames');
      requestFrameData(zm_event.Id, frameId);
    }
  } else {
    console.log('No event');
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

function requestFrameData(eventId, frameId) {
  var data = {};

  if (!events[eventId]) {
    data.id = eventId;
    data.loopback = frameId;
    eventQuery(data);
  } else {
    data.eid = eventId;
    data.fid = frameId;
    frameQuery(data);
  }
}

function previewEvent(slot, ev) {
  const t = slotTarget(slot, ev);
  const eventId = t.eid;
  const frameId = t.fid;
  if (events[eventId] && events[eventId]['frames'] && events[eventId]['frames'][frameId]) {
    showEventData(events[eventId], frameId);
  } else {
    requestFrameData(eventId, frameId);
  }
}

function loadEventImage(imagePath, zm_event, fid) {
  if (!zm_event) {
    console.log("No event object passed to loadEventImage");
    return;
  }

  const imageSrc = $j('#imageSrc'+zm_event.MonitorId);

  imageSrc.show();
  imageSrc.attr('src', imagePath);
  imageSrc.attr('data-event-id', zm_event.Id);
  imageSrc.attr('data-frame-id', fid);
  imageSrc.off('click');
  imageSrc.on('click', function() {
    showEvent(this);
  });

  var eventData = $j('#eventData'+zm_event.MonitorId);
  if ( eventData.length ) {
    // showEvent reads these from the clicked element
    eventData.attr('data-event-id', zm_event.Id);
    eventData.attr('data-frame-id', fid);
    eventData.off('click');
    eventData.on('click', function() {
      showEvent(this);
    });
  } else {
    console.log("No eventdata area found for monitor " + zm_event.MonitorId);
  }
}

function tlZoomBounds(event) {
  var target = event.target;
  var minTime = target.getAttribute('data-zoom-min-time');
  var maxTime = target.getAttribute('data-zoom-max-time');
  location.replace('?view='+currentView+navFilterQuery+'&minTime='+minTime+'&maxTime='+maxTime);
}

function tlZoomOut() {
  location.replace('?view='+currentView+navFilterQuery+'&midTime='+midTime+'&range='+zoomout_range);
}

function tlPanLeft() {
  location.replace('?view='+currentView+navFilterQuery+'&midTime='+minTime+'&range='+range);
}
function tlPanRight() {
  location.replace('?view='+currentView+navFilterQuery+'&midTime='+maxTime+'&range='+range);
}

function divDataOnClick() {
  // These look like the code in skin.js, but that code doesn't select for divs.
  document.querySelectorAll('div.event, div.activity').forEach(function(el) {
    el.onclick = window[el.getAttribute('data-on-click-this')].bind(el, el);
    // Absent when the page was loaded with mouseover=0
    const over = el.getAttribute('data-on-mouseover-this');
    if (over) {
      el.onmouseover = window[over].bind(el, el);
      // Moving along a merged bar changes which event is under the pointer
      if (el.getAttribute('data-slot-targets')) {
        el.onmousemove = function(ev) {
          const t = slotTarget(el, ev);
          const key = t.eid + ':' + t.fid;
          if (el.lastPreviewKey === key) return;
          el.lastPreviewKey = key;
          window[over](el, ev);
        };
      }
    }
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

  for (const mid in monitors) {
    const monitor = monitors[mid];
    showEventData(events[monitor.FirstEventId], 1);
  }

  // Bind the data-on-click attributes associated with a div
  divDataOnClick();
}

$j(document).ready(function() {
  initPage();
});
