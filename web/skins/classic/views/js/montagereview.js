"use strict";


var LOADING = 1;

var ajax = null;
var wait_for_events_interval = null;


function evaluateLoadTimes() {
  if (liveMode != 1 && currentSpeed == 0) return; // don't evaluate when we are not moving as we can do nothing really fast.

  // Only consider it a completed event if we load ALL monitors, then zero all and start again
  let start=0;
  let end=0;
  for (let i = 0; i < monitorIndex.length; i++) {
    if (monitorName[i] > '') {
      if ( monitorLoadEndTimems[i] == 0 ) return; // if we have a monitor with no time yet just wait
      if ( start == 0 || start > monitorLoadStartTimems[i] ) start = monitorLoadStartTimems[i];
      if ( end == 0 || end < monitorLoadEndTimems[i] ) end = monitorLoadEndTimems[i];
    }
  }
  if ( start == 0 || end == 0 ) return; // we really should not get here
  for (let i=0; i < numMonitors; i++) {
    const monId = monitorPtr[i];
    monitorLoadStartTimems[monId] = 0;
    monitorLoadEndTimems[monId] = 0;
  }

  freeTimeLastIntervals[imageLoadTimesEvaluated++] = 1 - ((end - start)/currentDisplayInterval);
  if (imageLoadTimesEvaluated < imageLoadTimesNeeded) return;
  let avgFrac=0;
  for (let i=0; i < imageLoadTimesEvaluated; i++) {
    avgFrac += freeTimeLastIntervals[i];
  }
  avgFrac = avgFrac / imageLoadTimesEvaluated;
  // The larger this is (positive) the faster we can go
  if (avgFrac >= 0.9) currentDisplayInterval = (currentDisplayInterval * 0.50).toFixed(1); // we can go much faster
  else if (avgFrac >= 0.8) currentDisplayInterval = (currentDisplayInterval * 0.55).toFixed(1);
  else if (avgFrac >= 0.7) currentDisplayInterval = (currentDisplayInterval * 0.60).toFixed(1);
  else if (avgFrac >= 0.6) currentDisplayInterval = (currentDisplayInterval * 0.65).toFixed(1);
  else if (avgFrac >= 0.5) currentDisplayInterval = (currentDisplayInterval * 0.70).toFixed(1);
  else if (avgFrac >= 0.4) currentDisplayInterval = (currentDisplayInterval * 0.80).toFixed(1);
  else if (avgFrac >= 0.35) currentDisplayInterval = (currentDisplayInterval * 0.90).toFixed(1);
  else if (avgFrac >= 0.3) currentDisplayInterval = (currentDisplayInterval * 1.00).toFixed(1);
  else if (avgFrac >= 0.25) currentDisplayInterval = (currentDisplayInterval * 1.20).toFixed(1);
  else if (avgFrac >= 0.2) currentDisplayInterval = (currentDisplayInterval * 1.50).toFixed(1);
  else if (avgFrac >= 0.1) currentDisplayInterval = (currentDisplayInterval * 2.00).toFixed(1);
  else currentDisplayInterval = (currentDisplayInterval * 2.50).toFixed(1);
  // limit this from about 40fps to .1 fps
  currentDisplayInterval = Math.min(Math.max(currentDisplayInterval, 40), 10000);
  imageLoadTimesEvaluated=0;
  //setSpeed(speedIndex);
  $j('#fps').text("Display refresh rate is " + (1000 / currentDisplayInterval).toFixed(1) + " per second, avgFrac=" + avgFrac.toFixed(3) + ".");
} // end evaluateLoadTimes()

function findEventByTime(arr, time, debug=false) {
  let start = 0;
  let end = arr.length-1; // -1 because 0 based indexing

  if (debug) {
    if ( arr.length ) {
      console.log("looking for "+time+" Start: " + arr[start].StartTimeSecs + ' End: ' + arr[end].EndTimeSecs);
    } else {
      console.log("looking for "+time+" but nothing in arr");
    }
  }
  // Iterate while start not meets end
  while ((start <= end) && (arr[start].StartTimeSecs <= time) && (!arr[end].EndTimeSecs || (arr[end].EndTimeSecs >= time))) {
    if (debug)
      console.log("looking for "+time+" Start: " + arr[start].StartTimeSecs + ' End: ' + arr[end].EndTimeSecs);
    // Find the middle index
    const middle = Math.floor((start + end)/2);
    const zm_event = arr[middle];

    // If element is present at mid, return True
    if (debug) console.log(middle, zm_event, time);
    if ((zm_event.StartTimeSecs <= time) && (!zm_event.EndTimeSecs || (zm_event.EndTimeSecs >= time))) {
      if (debug) console.log("Found it at ", zm_event);
      return zm_event;
    }

    if (debug) console.log("Didn't find it looking for "+time+" Start: " + zm_event.StartTimeSecs + ' End: ' + zm_event.EndTimeSecs);
    // Else look in left or right half accordingly
    if (zm_event.StartTimeSecs < time) {
      start = middle + 1;
    } else if (zm_event.EndTimeSecs > time) {
      end = middle - 1;
    } else {
      break;
    }
  } // end while
  return false;
} // end function findEventByTime

function findFrameByTime(arr, time, debug=false) {
  if (!arr) {
    console.log("No array in findFrameByTime");
    return false;
  }
  const keys = Object.keys(arr);
  let start=0;
  let end=keys.length-1;

  //console.log(keys);
  //console.log(keys[start]);
  // Iterate while start not meets end
  if (debug) console.log("Looking for "+ time+ "start: " + start + ' end ' + end, arr[keys[start]]);
  while ((start <= end)) {
    if ((arr[keys[start]].TimeStampSecs > time) || (arr[keys[end]].NextTimeStampSecs < time)) {
      console.log(time + " not found in array of frames.", arr[keys[start]], arr[keys[end]]);
      return false;
    }
    // Find the mid index
    const middle = Math.floor((start + end)/2);
    const frame = arr[keys[middle]];
    if (debug) {
      console.log("Looking for ", time, secs2inputstr(time), "frame", frame, 'middle '+middle+ ' start '+ start + ' end ' +end);
      console.log(secs2inputstr(frame.TimeStampSecs));
    }

    // If element is present at mid, return True
    if ((frame.TimeStampSecs == time) ||
        (frame.TimeStampSecs < time) &&
        (
          (frame.NextTimeStampSecs > time) ||
          (!frame.NextTimeStampSecs) // only if event.EndTime is null
        )
    ) {
      if (debug) console.log("Found it at ", frame);
      const e = events[frame.EventId];
      if (!e) {
        console.log("No event for ", frame.EventId);
        return frame;
      }

      if (frame.NextFrameId && e.FramesById) {
        var NextFrame = e.FramesById[frame.NextFrameId];
        if (!NextFrame) {
          console.log("No nextframe for ", frame.NextFrameId);
          return frame;
        }
        //console.log(NextFrame);

        if ((frame.TimeStampSecs != time) && (frame.Type == 'Bulk' || NextFrame.Type == 'Bulk')) {
          // There is time between this frame and a bulk frame
          var duration = frame.NextTimeStampSecs - frame.TimeStampSecs;
          frame.FrameId = parseInt(frame.FrameId) + parseInt( (NextFrame.FrameId-frame.FrameId) * ( time-frame.TimeStampSecs )/duration );
          //console.log("Have NextFrame: duration: " + duration + " frame_id = " + frame.FrameId + " from " + NextFrame.FrameId + ' - ' + frame.FrameId + " time: " + (time-frame.TimeStampSecs)  );
        } else if (debug) {
          console.log("Bulk: " + "frame_id = " + frame.FrameId + " time: " + (time-frame.TimeStampSecs), (NextFrame.Type == 'Bulk'));
        }
      } else if (debug) {
        console.log('No nextframeId');
      }

      return frame;
      // Else look in left or right half accordingly
    } else if (frame.TimeStampSecs < time) {
      start = middle + 1;
    } else if (frame.TimeStampSecs > time) {
      end = middle - 1;
    } else {
      console.log('Error');
      break;
    }
  } // end while
  if (debug) console.log("Didn't find frame it");
  return false;
} // end function findFrameByTime(arr, time, debug=false)

function getFrame(monId, time, last_Frame) {
  if (last_Frame) {
    if (
      (last_Frame.MonitorId == monId) &&
      (last_Frame.TimeStampSecs <= time) &&
      (last_Frame.EndTimeStampSecs >= time)
    ) {
      return last_Frame;
    }
  }

  if (!events_by_monitor_id[monId] || !events_by_monitor_id[monId].length) {
    // Need to load them?
    console.log("No events_by_monitor_id for " + monId);
    return;
  }

  if (!events_for_monitor[monId] || !events_for_monitor[monId].length) {
    events_for_monitor[monId] = events_by_monitor_id[monId].map((x)=>events[x]);
    if (!events_for_monitor[monId].length) {
      //console.log("No events for monitor " + monId);
      return;
    }
  }

  let Event = findEventByTime(events_for_monitor[monId], time, false);
  if (Event === false) {
    for (let i=0, len=events_for_monitor[monId].length; i<len; i++) {
      const event_id = events_for_monitor[monId][i].Id;
      const e = events[event_id];
      if (!e) {
        console.error('No event found for ', event_id);
        break;
      }
      if (e.StartTimeSecs <= time && e.EndTimeSecs >= time) {
        Event = e;
        break;
      }
    }
    if (Event) {
      console.log("Failed to find event for ", time, " but found it using linear search");
      for (let i=0, len=events_for_monitor[monId].length; i<len; i++) {
        const event_id = events_for_monitor[monId][i].Id;
        const e = events[event_id];
        if ((e.StartTimeSecs <= time) && (e.EndTimeSecs >= time)) {
          console.log("Found at " + e.Id + ' start: ' + e.StartTimeSecs + ' end: ' + e.EndTimeSecs);
          break;
        } else {
          console.log("Not Found at " + e.Id + ' start: ' + e.StartTimeSecs + ' end: ' + e.EndTimeSecs);
        }
      }
    }
  }
  if (!Event) {
    console.log('No event found for ' + time + ' ' + secs2inputstr(time) + ' on monitor ' + monId);
    return;
  }

  if (!Event.FramesById) {
    // It is assumed at this time that every event has frames
    console.log('No FramesById for event ', Event.Id);
    var event_list = {};
    event_list[Event.Id] = Event;
    loadFrames(event_list).then(function() {
      if (!Event.FramesById) {
        console.log("No FramesById after loadFrames!", Event);
      }
      return findFrameByTime(Event.FramesById, time);
    }, function(Error) {
      console.log(Error);
    });
    return;
  } else if (!Event.FramesById.length) {
    console.log("frames loading for event " + Event.Id);
    return;
  }

  // Need to get frame by time, not some fun calc that assumes frames have the same length.
  // Frames are sorted in descreasing order (or not sorted).
  // This is likely not efficient.  Would be better to start at the last frame viewed, see if it is still relevant
  // Then move forward or backwards as appropriate
  let Frame = findFrameByTime(Event.FramesById, time);
  if (!Frame) {
    console.log("Didn't find frame by binary search");
    for (const frame_id in Event.FramesById) {
      // Again need binary search
      if (
        Event.FramesById[frame_id].TimeStampSecs == time ||
        (
          Event.FramesById[frame_id].TimeStampSecs < time &&
          (
            (!Event.FramesById[frame_id].NextTimeStampSecs) || // only if event.EndTime is null
            (Event.FramesById[frame_id].NextTimeStampSecs > time)
          )
        )
      ) {
        Frame = Event.FramesById[frame_id];
        break;
      }
    } // end foreach frame in the event.
  }

  if (!Frame) {
    console.log("Didn't find frame for " + time);
  }
  return Frame;
}

// time is seconds since epoch
function getImageSource(monId, time) {
  if (liveMode == 1) {
    let new_url = monitorImageObject[monId].src.replace(
        /rand=\d+/i,
        'rand='+Math.floor(Math.random() * 1000000)
    );
    if (auth_hash) {
      // update auth hash
      new_url = new_url.replace(/auth=[a-z0-9]+/i, 'auth='+auth_hash);
    }
    return new_url;
  }
  let frame_id;

  const Frame = getFrame(monId, time);
  if (Frame) {
    const e = events[Frame.EventId];
    if (!e) {
      console.log('No event found for ' + Frame.EventId, Frame);
      return '';
    }

    // Adjust for bulk frames
    if (Frame.NextFrameId) {
      if (!e.FramesById) {
        console.log("No FramesById in event ", e, e.FramesById);
        return '';
      }
      const NextFrame = e.FramesById[Frame.NextFrameId];
      if (!NextFrame) {
        console.log("No next frame for " + Frame.NextFrameId);
      } else if (NextFrame.Type == 'Bulk') {
        // There is time between this frame and a bulk frame
        const duration = Frame.NextTimeStampSecs - Frame.TimeStampSecs;
        frame_id = parseInt(Frame.FrameId) + parseInt( (NextFrame.FrameId-Frame.FrameId) * ( time-Frame.TimeStampSecs )/duration );
        //console.log("Have NextFrame: duration: " + duration + " frame_id = " + frame_id + " from " + NextFrame.FrameId + ' - ' + Frame.FrameId + " time: " + (time-Frame.TimeStampSecs)  );
      } else {
        frame_id = Frame.FrameId;
      }
    } else {
      frame_id = Frame.FrameId;
    }

    if (!parseInt(frame_id)) {
      console.log("No frame_id from ", Frame);
      return;
    }

    let scale = parseInt(100 * monitorCanvasObj[monId].width / monitorWidth[monId]);
    if (scale > 100) {
      scale = 100;
    } else {
      scale = 10 * parseInt(scale/10); // Round to nearest 10
      // May need to limit how small we can go to maintain fidelity
    }

    // Storage[0] is guaranteed to exist as we make sure it is there in montagereview.js.php
    const storage = Storage[e.StorageId] ? Storage[e.StorageId] : Storage[0];
    // monitorServerId may be 0, which gives us the default Server entry
    const server = storage.ServerId ? Servers[storage.ServerId] : Servers[monitorServerId[monId]];
    return server.PathToZMS + '?mode=jpeg&event=' + Frame.EventId + '&frame='+frame_id +
      //"&width=" + monitorCanvasObj[monId].width +
      //"&height=" + monitorCanvasObj[monId].height +
      "&scale=" + scale +
      "&frames=1" +
      "&rate=" + 100*speeds[speedIndex] +
      '&' + auth_relay;
  } // end found Frame
  return '';
} // end function getImageSource

// callback when loading an image. Will load itself to the canvas, or draw no data
function imagedone( obj, monId, success ) {
  if (success) {
    const canvasCtx = monitorCanvasCtx[monId];
    const canvasObj = monitorCanvasObj[monId];

    canvasCtx.drawImage( monitorImageObject[monId], 0, 0, canvasObj.width, canvasObj.height );
    const iconSize=(Math.max(canvasObj.width, canvasObj.height) * 0.10);
    canvasCtx.font = "600 " + iconSize.toString() + "px Arial";
    canvasCtx.fillStyle = "white";
    canvasCtx.globalCompositeOperation = "difference";
    canvasCtx.fillText( "+", iconSize*0.2, iconSize*1.2 );
    canvasCtx.fillText( "-", canvasObj.width - iconSize*1.2, iconSize*1.2 );
    canvasCtx.globalCompositeOperation = "source-over";
    monitorLoadEndTimems[monId] = new Date().getTime(); // elapsed time to load
    evaluateLoadTimes();
  }
  monitorLoading[monId] = false;
  if ( ! success ) {
    // if we had a failrue queue up the no-data image
    //loadImage2Monitor(monId,"no data");  // leave the staged URL if there is one, just ignore it here.
    if ( liveMode ) {
      writeText( monId, "Camera Offline" );
    } else {
      writeText( monId, "No event" );
    }
  } else {
    if ( monitorLoadingStageURL[monId] == "" ) {
      //console.log("Not showing image for " + monId );
      // This means that there wasn't a loading image placeholder.
      // So we weren't actually loading an image... which seems weird.
      return;
    }
    //loadImage2Monitor(monId,monitorLoadingStageURL[monId] );
    //monitorLoadingStageURL[monId]="";
  }
  return;
}

function writeText(monId, text) {
  if (monId) {
    const canvasCtx = monitorCanvasCtx[monId];
    const canvasObj = monitorCanvasObj[monId];
    //canvasCtx.fillStyle="white";
    //canvasCtx.fillRect(0, 0, canvasObj.width, canvasObj.height);
    var textSize = canvasObj.width * 0.15;
    canvasCtx.font = '600 ' + textSize.toString() + "px Arial";
    canvasCtx.fillStyle = 'white';
    var textWidth = canvasCtx.measureText(text).width;
    canvasCtx.fillText(text, canvasObj.width/2 - textWidth/2, canvasObj.height/2);
  } else {
    console.log('No monId in writeText');
  }
}

// Either draws the
function loadImage2Monitor(monId, url) {
  if ( monitorLoading[monId] && (monitorImageObject[monId].src != url) ) {
    // never queue the same image twice (if it's loading it has to be defined, right?
    monitorLoadingStageURL[monId] = url; // we don't care if we are overriting, it means it didn't change fast enough
  } else {
    if ( monitorImageObject[monId].src == url ) return; // do nothing if it's the same
    if ( url == 'no data' ) {
      writeText(monId, 'No Event');
    } else {
      //writeText(monId, 'Loading...');
      monitorLoading[monId] = true;
      monitorLoadStartTimems[monId] = new Date().getTime();
      monitorImageObject[monId].src = url; // starts a load but doesn't refresh yet, wait until ready
    }
  }
}

function timerFire() {
  // See if we need to reschedule
  if ( ( currentDisplayInterval != timerInterval ) || ( currentSpeed == 0 ) ) {
    console.log("Turn off interrupts timerInterfave", timerInterval, currentDisplayInterval, currentSpeed);
    // zero just turn off interrupts
    clearInterval(timerObj);
    timerObj = null;
    timerInterval = currentDisplayInterval;
  }

  if (liveMode) {
    outputUpdate(currentTimeSecs); // In live mode we basically do nothing but redisplay
  } else if (currentTimeSecs + playSecsPerInterval >= maxTimeSecs) {
    // beyond the end just stop
    if (speedIndex) setSpeed(0);
    outputUpdate(currentTimeSecs);
  } else if (playSecsPerInterval || (currentTimeSecs==minTimeSecs)) {
    outputUpdate(playSecsPerInterval + currentTimeSecs);
  }

  if ((currentSpeed > 0 || liveMode != 0) && !timerObj) {
    timerObj = setInterval(timerFire, timerInterval); // don't fire out of live mode if speed is zero
  } else {
    console.log("timefire", "CurrentSpeed", currentSpeed, "liveMode", liveMode, timerObj);
  }
} // end function timerFire()

// val is seconds?
function drawSliderOnGraph(val) {
  if (numMonitors <= 0) {
    return;
  }

  if ( liveMode == 1 ) {
    val = Math.floor( Date.now() / 1000);
  }

  var sliderWidth=10;
  var sliderLineWidth=1;
  var sliderHeight=cHeight;
  // Set some sizes
  var labelpx = Math.max( 6, Math.min( 20, parseInt(cHeight * timeLabelsFractOfRow / (numMonitors+1)) ) );
  var labbottom = parseInt(cHeight * 0.2 / (numMonitors+1)).toString() + "px"; // This is positioning same as row labels below, but from bottom so 1-position
  var labfont = labelpx + "px"; // set this like below row labels

  // if we have no data to display don't do the slider itself
  let sliderX = parseInt((val - minTimeSecs) / rangeTimeSecs * cWidth - sliderWidth/2); // position left side of slider
  if ( sliderX < 0 ) sliderX = 0;
  if ( sliderX + sliderWidth > cWidth ) sliderX = cWidth-sliderWidth-1;

  // If we have data already saved first restore it from LAST time

  if ( typeof underSlider !== 'undefined' ) {
    ctx.putImageData(underSlider, underSliderX, 0, 0, 0, sliderWidth, sliderHeight);
    underSlider = undefined;
  }
  if ( liveMode == 0 ) {
    // we get rid of the slider if we switch to live (since it may not be in the "right" place)
    // Now save where we are putting it THIS time
    underSlider = ctx.getImageData(sliderX, 0, sliderWidth, sliderHeight);
    // And add in the slider'
    ctx.lineWidth = sliderLineWidth;
    ctx.strokeStyle = 'yellow';
    // looks like strokes are on the outside (or could be) so shrink it by the line width so we replace all the pixels
    ctx.strokeRect(sliderX+sliderLineWidth, sliderLineWidth, sliderWidth - 2*sliderLineWidth, sliderHeight - 2*sliderLineWidth);
    underSliderX = sliderX;
  }
  var o = document.getElementById('scruboutput');
  if ( liveMode == 1 ) {
    o.innerHTML = 'Live Feed @ ' + (1000 / currentDisplayInterval).toFixed(1) + ' fps';
    o.style.color = 'red';
  } else {
    o.innerHTML = secs2dbstr(val);
    o.style.color = 'white';
  }
  o.style.position = 'absolute';
  o.style.bottom = labbottom;
  o.style.font = labfont;
  // try to get length and then when we get too close to the right switch to the left
  var len = o.offsetWidth;
  const x = (sliderX > cWidth/2) ? sliderX - len - 10 : sliderX + 10;
  o.style.left = x.toString() + "px";

  // This displays (or not) the left/right limits depending on how close the slider is.
  // Because these change widths if the slider is too close, use the slider width as an estimate for the left/right label length (i.e. don't recalculate len from above)
  // If this starts to collide increase some of the extra space

  o = document.getElementById('scrubleft');
  if ( numMonitors == 0 ) { // we need a len calculation if we skipped the slider
    len = o.offsetWidth;
  }
  // If the slider will overlay part of this suppress (this is the left side)
  if ( len + 10 > sliderX || cWidth < len * 4 ) {
    // that last check is for very narrow browsers
    o.style.display = "none";
  } else {
    o.style.display = "inline";
    o.style.display = "inline-flex"; // safari won't take this but will just ignore
  }

  o = document.getElementById('scrubright');
  if ( sliderX > cWidth - len - 20 || cWidth < len * 4 ) {
    o.style.display = "none";
  } else {
    o.style.display = "inline";
    o.style.display = "inline-flex";
  }
}

function drawFrameOnGraph(frame) {
  if (!frame.Score) return;
  // Now put in scored frames (if any)
  let x1 = parseInt( (frame.TimeStampSecs - minTimeSecs) / rangeTimeSecs * cWidth); // round low end down
  let x2 = parseInt( (frame.TimeStampSecs - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 ); // round up
  if (x2-x1 < 2) x2=x1+2; // So it is visible make them all at least this number of seconds wide
  ctx.fillStyle=monitorColour[Event.MonitorId];
  //ctx.fillStyle = '#ff0000';
  ctx.globalAlpha = 0.4 + 0.6 * (1 - frame.Score/maxScore); // Background is scaled but even lowest is twice as dark as the background
  const MonitorId = events[frame.EventId].MonitorId;
  ctx.fillRect(x1, monitorIndex[MonitorId]*rowHeight, x2-x1, rowHeight-2);
  //console.log("Drew frame from ", x1, MonitorId, monitorIndex[MonitorId]*rowHeight, x2-x1, rowHeight);
}

function drawEventOnGraph(Event) {
  // round low end down
  const x1 = parseInt((Event.StartTimeSecs - minTimeSecs) / rangeTimeSecs * cWidth);
  if (!Event.EndTimeSecs) Event.EndTimeSecs = maxTimeSecs;
  // round high end up to be sure consecutive ones connect
  const x2 = parseInt((Event.EndTimeSecs - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 );
  if (!monitorColour[Event.MonitorId]) {
    console.log("No colour for ", Event.MonitorId, monitorColour);
    ctx.fillStyle = '#43bcf2';
  } else {
    ctx.fillStyle = monitorColour[Event.MonitorId];
  }
  ctx.globalAlpha = 0.2; // light color for background
  // Erase any overlap so it doesn't look artificially darker
  ctx.clearRect(x1, monitorIndex[Event.MonitorId]*rowHeight, x2-x1, rowHeight);
  ctx.fillRect(x1, monitorIndex[Event.MonitorId]*rowHeight, x2-x1, rowHeight-2);
  //outputUpdate(currentTimeSecs);
  //console.log("Drew event from ", x1, monitorIndex[Event.MonitorId]*rowHeight, x2-x1, rowHeight);
}

function drawGraph() {
  underSlider = undefined; // flag we don't have a slider cached
  const divWidth = document.getElementById('timelinediv').clientWidth;
  canvas.width = cWidth = divWidth; // Let it float and determine width (it should be sized a bit smaller percentage of window)
  cHeight = parseInt(window.innerHeight * 0.10); // 10%
  if (cHeight < numMonitors * 20) { //Minimum 20px per monitor maybe it should be 10px per monitor?
    cHeight = numMonitors * 20;
  }
  canvas.height = cHeight;

  if (events && ( Object.keys(events).length == 0 ) ) {
    ctx.font = "40px Georgia";
    ctx.globalAlpha = 1;
    ctx.fillStyle = "white";
    const t = LOADING ? "Loading events" : "No events found.";
    var l = ctx.measureText(t).width;
    ctx.fillText(t, (cWidth - l)/2, cHeight-10);
    return;
  }

  rowHeight = parseInt(cHeight / (numMonitors + 1) ); // Leave room for a scale of some sort
  // Note that this may be a sparse array

  // Should we clear the canvas?

  // first fill in the bars for the events (not alarms)
  // At first, no events loaded, that's ok, later, we will have some events, should only draw those in the time range.
  for (const event_id in events) {
    const Event = events[event_id];
    drawEventOnGraph(Event);
    if (Event.FramesById) {
      for (const frame_id in Event.FramesById ) {
        const Frame = Event.FramesById[frame_id];
        if (!Frame.Score) continue;
        drawFrameOnGraph(Frame);
      } // end foreach frame
    }
  } // end foreach Event

  for (let i=0; i < numMonitors; i++) {
    // Apparently we have to set these each time before calling fillText
    ctx.font = parseInt(rowHeight * timeLabelsFractOfRow).toString() + "px Georgia";
    ctx.globalAlpha = 1;
    ctx.fillStyle = "white";
    // This should roughly center font in row
    ctx.fillText(monitorName[monitorPtr[i]], 0, (i + 1 - (1 - timeLabelsFractOfRow)/2 ) * rowHeight);
  }

  drawSliderOnGraph(currentTimeSecs);
} // end function drawGraph

function redrawScreen() {
  var dateTimeDiv = $j('#DateTimeDiv');
  var speedDiv = $j('#SpeedDiv');
  var timeLineDiv = $j('#timelinediv');
  var liveButton = $j('#liveButton');
  var zoomIn = $j('#zoomin');
  var zoomOut = $j('#zoomout');
  var panLeft = $j('#panleft');
  var panRight = $j('#panright');
  var downloadVideo = $j('#downloadVideo');
  var scaleDiv = $j('#ScaleDiv');
  var fit = $j('#fit');

  if (liveMode == 1) {
    // if we are not in live view switch to history -- this has to come before fit in case we re-establish the timeline
    dateTimeDiv.hide();
    speedDiv.hide();
    timeLineDiv.hide();
    liveButton.text('History');
    zoomIn.hide();
    zoomOut.hide();
    panLeft.hide();
    panRight.hide();
    downloadVideo.hide();
  } else {
    // switch out of liveview mode
    dateTimeDiv.show();
    speedDiv.show();
    timeLineDiv.show();
    liveButton.text('Live');
    zoomIn.show();
    zoomOut.show();
    panLeft.show();
    panRight.show();
    downloadVideo.show();
    drawGraph();
  }

  var monitors = $j('#monitors');
  if (fitMode == 1) {
    var fps = $j('#fps');
    var vh = window.innerHeight;
    var mh = (vh - monitors.position().top - fps.outerHeight());

    scaleDiv.hide();
    fit.text('Scale');
    monitors.height(mh.toString() + 'px'); // leave a small gap at bottom

    if (maxfit2(monitors.outerWidth(), monitors.outerHeight()) == 0) { /// if we fail to fix we back out of fit mode -- ??? This may need some better handling
      console.log("Failed to fit, dropping back to scaled mode");
      fitMode=1-fitMode;
    }
  } else {
    // switch out of fit mode
    // if we fit, then monitors were absolutely positioned already (or will be) otherwise release them to float
    for (let i=0; i<numMonitors; i++) {
      monitorCanvasObj[monitorPtr[i]].style.position = '';
    }
    monitors.height('');
    scaleDiv.show();
    fit.text('fit');
    setScale(currentScale);
  }
  outputUpdate(currentTimeSecs);
  timerFire(); // force a fire in case it's not timing
} // end function redrawScreen

function outputUpdate(time) {
  if (Object.keys(events).length !== 0) {
    for ( let i=0; i < numMonitors; i++ ) {
      const src = getImageSource(monitorPtr[i], time);
      loadImage2Monitor(monitorPtr[i], src);
    }
  }
  currentTimeSecs = time;
  drawSliderOnGraph(time);
}

// Found this here: http://stackoverflow.com/questions/55677/how-do-i-get-the-coordinates-of-a-mouse-click-on-a-canvas-element
function relMouseCoords(event) {
  let totalOffsetX = 0;
  let totalOffsetY = 0;
  let currentElement = event.target;

  do {
    totalOffsetX += currentElement.offsetLeft - currentElement.scrollLeft;
    totalOffsetY += currentElement.offsetTop - currentElement.scrollTop;
  } while (currentElement = currentElement.offsetParent);

  const canvasX = event.pageX - totalOffsetX;
  const canvasY = event.pageY - totalOffsetY;

  return {x: canvasX, y: canvasY};
}
HTMLCanvasElement.prototype.relMouseCoords = relMouseCoords;

// These are the functions for mouse movement in the timeline.  Note that touch is treated as a mouse move with mouse down

var mouseisdown=false;
function mdown(event) {
  mouseisdown=true;
  mmove(event);
}
function mup(event) {
  mouseisdown=false;
}
function mout(event) {
  mouseisdown=false;
} // if we go outside treat it as release
function tmove(event) {
  mouseisdown=true;
  mmove(event);
}

function mmove(event) {
  if ( mouseisdown ) {
    // only do anything if the mouse is depressed while on the sheet
    const relx = event.target.relMouseCoords(event).x;
    const sec = Math.floor(minTimeSecs + rangeTimeSecs / event.target.width * relx);
    if (parseInt(sec)) outputUpdate(sec);
  }
}

function secs2inputstr(s) {
  if ( ! parseInt(s) ) {
    console.log("Invalid value for " + s + " seconds");
    return '';
  }

  var m = moment(s*1000);
  if ( ! m ) {
    console.log("No valid date for " + s + " seconds");
    return '';
  }
  return m.format("YYYY-MM-DDTHH:mm:ss");
}

function secs2dbstr(s) {
  if (!parseInt(s)) {
    console.log("Invalid value for " + s + " seconds");
    return '';
  }
  var m = moment(s*1000);
  if ( ! m ) {
    console.log("No valid date for " + s + " milliseconds");
    return '';
  }
  return m.format("YYYY-MM-DD HH:mm:ss");
}

function setFit(value) {
  fitMode = value;
  redrawScreen();
}

function showScale(newscale) {
  // updates slider only
  $j('#scaleslideroutput').text(parseFloat(newscale).toFixed(2).toString() + " x");
  return;
}

function setScale(newscale) {
  // makes actual change
  showScale(newscale);
  for ( var i=0; i < numMonitors; i++ ) {
    monitorCanvasObj[monitorPtr[i]].width = monitorWidth[monitorPtr[i]]*monitorNormalizeScale[monitorPtr[i]]*monitorZoomScale[monitorPtr[i]]*newscale;
    monitorCanvasObj[monitorPtr[i]].height = monitorHeight[monitorPtr[i]]*monitorNormalizeScale[monitorPtr[i]]*monitorZoomScale[monitorPtr[i]]*newscale;
  }
  currentScale = newscale;
}

function showSpeed(val) {
  // updates slider only
  $j('#speedslideroutput').text(parseFloat(speeds[val]).toFixed(2).toString() + " x");
}

function setSpeed(speed_index) {
  if (liveMode == 1) {
    console.log("setSpeed in liveMode?");
    return; // we shouldn't actually get here but just in case
  }
  currentSpeed = parseFloat(speeds[speed_index]);
  speedIndex = speed_index;
  playSecsPerInterval = Math.floor( 1000 * currentSpeed * currentDisplayInterval ) / 1000000;
  setCookie('speed', speedIndex);
  showSpeed(speed_index);
  timerFire();
}

function setLive(value) {
  // When we submit the context etc goes away but we may still be trying to update
  // So kill the timer.
  clearInterval(timerObj);
  liveMode = value;
  var form = document.getElementById('montagereview_form');
  form.elements['live'].value = value;
  form.submit();
  return false;
}


//vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
// The section below are to reload this program with new parameters

function clicknav(minSecs, maxSecs, live) {// we use the current time if we can
  var date = new Date();
  var now = Math.floor(date.getTime() / 1000);
  var tz_difference = (-1 * date.getTimezoneOffset() * 60) - server_utc_offset;
  now -= tz_difference;

  var minStr = "";
  var maxStr = "";
  var currentStr = "";
  if ( minSecs > 0 ) {
    if ( maxSecs > now ) {
      maxSecs = parseInt(now);
    }
    maxStr = "&maxTime=" + secs2inputstr(maxSecs);
    $j('#maxTime').val(secs2inputstr(maxSecs));
  }
  if ( minSecs > 0 ) {
    $j('#minTime').val(secs2inputstr(minSecs));
    minStr = "&minTime=" + secs2inputstr(minSecs);
  }
  if ( maxSecs == 0 && minSecs == 0 ) {
    minStr = "&minTime=01/01/1950T12:00:00";
    maxStr = "&maxTime=12/31/2035T12:00:00";
  }
  var intervalStr="&displayinterval=" + currentDisplayInterval.toString();
  if ( minSecs && maxSecs ) {
    if ( currentTimeSecs > minSecs && currentTimeSecs < maxSecs ) { // make sure time is in the new range
      currentStr = "&current=" + secs2dbstr(currentTimeSecs);
    }
  }

  var liveStr = "&live=0";
  if ( live == 1 ) {
    liveStr = "&live=1";
  }

  var zoomStr = "";
  for ( var i = 0; i < numMonitors; i++ ) {
    if ( monitorZoomScale[monitorPtr[i]] < 0.99 || monitorZoomScale[monitorPtr[i]] > 1.01 ) { // allow for some up/down changes and just treat as 1 of almost 1
      zoomStr += "&z" + monitorPtr[i].toString() + "=" + monitorZoomScale[monitorPtr[i]].toFixed(2);
    }
  }

  var uri = "?view=" + currentView + '&fit='+(fitMode==1?'1':'0') + minStr + maxStr + currentStr + intervalStr + liveStr + zoomStr + "&scale=" + $j("#scaleslider")[0].value + "&speed=" + speeds[$j("#speedslider")[0].value];
  window.location = uri;
} // end function clicknav

function click_lastHour() {
  var date = new Date();
  var now = Math.floor( date.getTime() / 1000 );
  now -= -1 * date.getTimezoneOffset() * 60;
  now += server_utc_offset;
  clicknav(now - 3599, now, 0);
}
function click_lastEight() {
  var date = new Date();
  var now = Math.floor( date.getTime() / 1000 );
  now -= -1 * date.getTimezoneOffset() * 60 - server_utc_offset;
  clicknav(now - 3600*8 + 1, now, 0);
}
function click_last24() {
  var date = new Date();
  var now = Math.floor( date.getTime() / 1000 );
  now -= -1 * date.getTimezoneOffset() * 60 - server_utc_offset;
  clicknav(now - 3600*24 + 1, now, 0);
}
function click_zoomin() {
  rangeTimeSecs = parseInt(rangeTimeSecs / 2);
  minTimeSecs = parseInt(currentTimeSecs - rangeTimeSecs/2); // this is the slider current time, we center on that
  maxTimeSecs = parseInt(currentTimeSecs + rangeTimeSecs/2);
  clicknav(minTimeSecs, maxTimeSecs, 0);
}

function click_zoomout() {
  rangeTimeSecs = parseInt(rangeTimeSecs * 2);
  minTimeSecs = parseInt(currentTimeSecs - rangeTimeSecs/2); // this is the slider current time, we center on that
  maxTimeSecs = parseInt(currentTimeSecs + rangeTimeSecs/2);
  clicknav(minTimeSecs, maxTimeSecs, 0);
}
function click_panleft() {
  minTimeSecs = parseInt(minTimeSecs - rangeTimeSecs/2);
  maxTimeSecs = minTimeSecs + rangeTimeSecs - 1;
  currentTimeSecs -= rangeTimeSecs/2;
  clicknav(minTimeSecs, maxTimeSecs, 0);
}
function click_panright() {
  minTimeSecs = parseInt(minTimeSecs + rangeTimeSecs/2);
  maxTimeSecs = minTimeSecs + rangeTimeSecs - 1;
  clicknav(minTimeSecs, maxTimeSecs, 0);
}
// Manage the DOWNLOAD VIDEO button
function click_download() {
  const form = $j('#montagereview_form');

  const data = form.serializeArray();
  data[data.length] = {name: 'mergeevents', value: true};
  data[data.length] = {name: 'minTime', value: minTime};
  data[data.length] = {name: 'maxTime', value: maxTime};
  data[data.length] = {name: 'minTimeSecs', value: minTimeSecs};
  data[data.length] = {name: 'maxTimeSecs', value: maxTimeSecs};
  console.log(data);
  $j.ajax({
    url: thisUrl+'?request=modal&modal=download'+(auth_relay?'&'+auth_relay:''),
    data: data
  })
      .done(function(data) {
        insertModalHtml('downloadModal', data.html);
        $j('#downloadModal').modal('show');
        $j('#downloadModal').on('keyup keypress', function(e) {
          var keyCode = e.keyCode || e.which;
          if (keyCode === 13) {
            e.preventDefault();
            return false;
          }
        });
        // Manage the GENERATE DOWNLOAD button
        $j('#exportButton').click(exportEvent);
      })
      .fail(logAjaxFail);
} // end function click_download

function click_all_events() {
  clicknav(0, 0, 0);
}
function allnon() {
  clicknav(0, 0, 0);
}

// Handles individual monitor clicks and navigation to the standard event/watch display

function showOneMonitor(monId, event) {
  // link out to the normal view of one event's data
  // We know the monitor, need to determine the event based on current time
  let url = '';
  if ( liveMode != 0 ) {
    url = '?view=watch&mid=' + monId.toString();
  } else {
    const Frame = getFrame(monId, currentTimeSecs);
    if ( Frame ) {
      url = '?view=event&eid=' + Frame.EventId + '&fid=' + Frame.FrameId;
    } else {
      url = '?view=watch&mid=' + monId.toString();
    }
  } // end if live/events

  if (event.ctrlKey) {
    window.open(url, '_blank');
  } else {
    window.location.assign(url);
  }
}

function zoom(monId, scale) {
  var lastZoomMonPriorScale = monitorZoomScale[monId];
  monitorZoomScale[monId] *= scale;
  if ( redrawScreen() == 0 ) {// failure here is probably because we zoomed too far
    monitorZoomScale[monId] = lastZoomMonPriorScale;
    alert("You can't zoom that far -- rolling back");
    redrawScreen(); // put things back and hope it works
  }
}

function clickMonitor(event) {
  var element = event.target;
  //var monitor_element = document.getElementById('Monitor'+monId.toString());
  var monId = element.getAttribute('monitor_id');
  var pos_x = event.offsetX ? (event.offsetX) : event.pageX - element.offsetLeft;
  var pos_y = event.offsetY ? (event.offsetY) : event.pageY - element.offsetTop;
  if ( pos_x < element.width/4 && pos_y < element.height/4 ) {
    zoom(monId, 1.15);
  } else if ( pos_x > element.width * 3/4 && pos_y < element.height/4 ) {
    zoom(monId, 1/1.15);
  } else {
    showOneMonitor(monId, event);
  }
  return;
}

function changeFilters(e) {
  console.log(e, this);
  // Need to update minTimeSecs and maxTimeSecs

  let minMoment, maxMoment;

  const matches = this.name.match(/^filter\[Query\]\[terms\]\[(\d+)\]\[val\]$/);
  console.log(matches);
  if (matches && matches.length) {
    const name = 'filter[Query][terms]['+matches[1]+'][attr]';

    const attr = this.form.elements[name];
    if (attr && (attr.value == 'StartDateTime')) {
      const val = this;
      const op = this.form.elements['filter[Query][terms]['+matches[1]+'][op]'];
      if (op.value == '>=') {
        minMoment = moment(val.value, 'YYYY-MM-DD HH:mm:ss');
        if (!minMoment.isValid()) {
          alert("Date start is not valid." + val.value);
          return;
        }
      } else if (op == '<=') {
        maxMoment = moment(val.value, 'YYYY-MM-DD HH:mm:ss');
        if (!maxMoment.isValid()) maxMoment = moment();
      }
    } else {
      console.log("No attr", attr);
    }
  } else {
    const regexp = /^filter\[Query\]\[terms\]\[(\d+)\]\[attr\]$/;
    $j('#fieldsTable input[value="StartDateTime"]').each(function(index) {
      const matches = this.name.match(regexp);
      console.log('looking at', this, matches);
      if (matches && matches.length) {
        const val = this.form.elements['filter[Query][terms]['+matches[1]+'][val]'];
        if (val) {
          const op = this.form.elements['filter[Query][terms]['+matches[1]+'][op]'];
          if (op.value == '>=') {
            minMoment = moment(val.value, 'YYYY-MM-DD HH:mm:ss');
            if (!minMoment.isValid()) {
              alert("Date start is not valid." + val.value);
              return;
            }
          } else if (op == '<=') {
            maxMoment = moment(val.value, 'YYYY-MM-DD HH:mm:ss');
            if (!maxMoment.isValid()) maxMoment = moment();
          }
        } else {
          console.log("no val ", matches);
        }
      } else { 
        console.log("No matches for ", this.name);
      }
    });
  } // end if a datetime or something else
  if (minMoment) {
    minTimeSecs = minMoment.unix();
    console.log("Set minMoment to ", minTimeSecs);
    if (currentTimeSecs < minTimeSecs) {
      console.log("Adjusting currentTimeSecs", currentTimeSecs, minTimeSecs);
      currentTimeSecs = minTimeSecs;
    }
  } else { 
    console.log("No minMoment");
  }

  if (maxMoment) {
    maxTimeSecs = maxMoment.unix();
    console.log(currentTimeSecs,  minTimeSecs, maxTimeSecs);
    if (currentTimeSecs > maxTimeSecs) currentTimeSecs = maxTimeSecs;
  } else { 
    console.log("No maxMoment");
  }

  // Reloading can take a while, so stop interrupts to reduce load
  clearInterval(timerObj);
  timerObj = null;

  drawGraph(); // Will use new values
  loadEventData();
  wait_for_events();
}

function loadEventData(e) {
  LOADING = true;

  var monitors = monitorData;
  var data = {};
  var mon_ids = [];
  for (let monitor_i=0, monitors_len=monitors.length; monitor_i < monitors_len; monitor_i++) {
    const monitor = monitors[monitor_i];
    monitorLoading[monitor.Id] = false;
    mon_ids[mon_ids.length] = monitor.Id;
  }

  var url = Servers[serverId].urlToApi()+'/events/index';
  $j('#fieldsTable input,#fieldsTable select').each(function(index) {
    const el = $j(this);
    const val = el.val();
    if (val && (!Array.isArray(val) || val.length)) {
      const name = el.attr('name');

      if (name) {
        const found = name.match(/filter\[Query\]\[terms\]\[(\d)+\]\[val\]/);
        if (found) {
          const attr_name = 'filter[Query][terms]['+found[1]+'][attr]';
          const attr = this.form.elements[attr_name];
          const op_name = 'filter[Query][terms]['+found[1]+'][op]';
          const op = this.form.elements[op_name];
          if (attr) {
            url += '/'+attr.value+' '+op.value+':'+encodeURIComponent(val);
          } else {
            console.log('No attr for '+attr_name);
          }
        //} else {
          //console.log("No match for " + name);
        }
        data[name] = val;
        const cookie = el.attr('data-cookie');
        if (cookie) setCookie(cookie, val, 3600);
      } // end if name
    } // end if val
  });

  function receive_events(data) {
    if (data.result == 'Error') {
      alert(data.message);
      return;
    }
    if (!data.events) {
      console.log(data);
      return;
    }
    console.log("Event data ", data);

    if (data.events.length) {
      const event_list = {};
      for (let i=0, len = data.events.length; i<len; i++) {
        const ev = data.events[i].Event;
        events[parseInt(ev.Id)] = ev;
        if (!events_by_monitor_id[ev.MonitorId]) {
          events_by_monitor_id[ev.MonitorId] = []; // just event ids
          events_for_monitor[ev.MonitorId] = []; // id=>event
        }
        events_by_monitor_id[ev.MonitorId].push(ev.Id);
        events_for_monitor[ev.MonitorId].push(ev);
        drawEventOnGraph(ev);
        event_list[ev.Id] = ev;
        events[ev.id] = ev;
      }
      loadFrames(event_list);
    }
  } // end function receive_events

  if (ajax) ajax.abort();
  LOADING = false;

  if (mon_ids.length) {
    for (let i=0; i < mon_ids.length; i++) {
      ajax = $j.ajax({
        url: url+ '/MonitorId:'+mon_ids[i]+ '.json'+'?'+auth_relay,
        method: 'GET',
        //url: thisUrl + '?view=request&request=events&task=query&sort=Id&order=ASC',
        //data: data,
        timeout: 0,
        success: receive_events,
        error: function(jqXHR) {
          ajax = null;
          console.log("error", jqXHR);
          //logAjaxFail(jqXHR);
          //$j('#eventTable').bootstrapTable('refresh');
        }
      });
    } // end foreach monitor
  } else {
    ajax = $j.ajax({
      url: url+'.json'+'?'+auth_relay,
      method: 'GET',
      //url: thisUrl + '?view=request&request=events&task=query&sort=Id&order=ASC',
      //data: data,
      timeout: 0,
      success: receive_events,
      error: function(jqXHR) {
        ajax = null;
        console.log("error", jqXHR);
        //logAjaxFail(jqXHR);
        //$j('#eventTable').bootstrapTable('refresh');
      }
    });
  }
  return;
} // end function loadEventData

function initPage() {
  if (!liveMode) {
    canvas = document.getElementById('timeline');

    canvas.addEventListener('mousemove', mmove, false);
    canvas.addEventListener('touchmove', tmove, false);
    canvas.addEventListener('mousedown', mdown, false);
    canvas.addEventListener('mouseup', mup, false);
    canvas.addEventListener('mouseout', mout, false);

    ctx = canvas.getContext('2d', {willReadFrequently: true});

    // draw an empty timeline
    drawGraph();
  }

  for (let i = 0, len = monitorPtr.length; i < len; i += 1) {
    const monId = monitorPtr[i];
    if (!monId) continue;
    monitorCanvasObj[monId] = document.getElementById('Monitor'+monId);
    if ( !monitorCanvasObj[monId] ) {
      alert("Couldn't find DOM element for Monitor" + monId + "monitorPtr.length=" + len);
      continue;
    }
    monitorCanvasCtx[monId] = monitorCanvasObj[monId].getContext('2d');
    const imageObject = monitorImageObject[monId] = new Image();
    imageObject.monId = monId;
    imageObject.onload = function() {
      imagedone(this, this.monId, true);
    };
    imageObject.onerror = function() {
      imagedone(this, this.monId, false);
    };
    if (liveMode) loadImage2Monitor(monId, monitorImageURL[monId]);
    monitorCanvasObj[monId].addEventListener('click', clickMonitor, false);
  } // end foreach monitor

  setSpeed(speedIndex);
  //setFit(fitMode);  // will redraw
  //setLive(liveMode);  // will redraw
  loadEventData();
  wait_for_events();
  redrawScreen();

  $j('#scaleslider').bind('change', function() {
    setScale(this.value);
  });
  $j('#scaleslider').bind('input', function() {
    showScale(this.value);
  });
  $j('#speedslider').bind('change', function() {
    setSpeed(this.value);
  });
  $j('#speedslider').bind('input', function() {
    showSpeed(this.value);
  });

  $j('#liveButton').bind('click', function() {
    setLive(1-liveMode);
  });
  $j('#fit').bind('click', function() {
    setFit(1-fitMode);
  });
  $j('#archive_status').bind('change', function() {
    this.form.submit();
  });
  $j('#fieldsTable input, #fieldsTable select').each(function(index) {
    const el = $j(this);
    if (el.hasClass('datetimepicker')) {
      el.datetimepicker({timeFormat: "HH:mm:ss", dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false, onClose: changeFilters});
    } else if (el.hasClass('datepicker')) {
      el.datepicker({dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false, onClose: changeFilters});
    } else {
      el.on('change', changeFilters);
    }
  });
}

function wait_for_events() {
  if (Object.keys(events).length === 0) {
    if (!wait_for_events_interval)
    wait_for_events_interval = setInterval(wait_for_events, 1000);
  } else {
    clearInterval(wait_for_events_interval);
    wait_for_events_interval = null;
    timerFire();
  }
}

function takeSnapshot() {
  monitor_ids = [];
  for (const key in monitorIndex) {
    monitor_ids[monitor_ids.length] = key;
  }
  post('?view=snapshot', {'action': 'create', 'monitor_ids[]': monitor_ids});

  /*
   * Alternate implementation using the API
  server = new Server(Servers[serverId]);
  $j.ajax({
    method: 'POST',
    url: server.urlToApi()+'/snapshots.json' + (auth_relay ? '?' + auth_relay : ''),
    data: { 'monitor_ids[]': monitorIndex.keys()},
    success: function(response) {
      console.log(response);
    }
  });
  //console.log(monitor_ids);
  //window.location = '?view=snapshot&action=create&'+monitor_ids.join('&');
*/
}

window.addEventListener("resize", redrawScreen, {passive: true});
// Kick everything off
window.addEventListener('DOMContentLoaded', initPage);

/* Expects and Object, not an array, of EventId=>Event mappings. */
function loadFrames(zm_events) {
  return new Promise(function(resolve, reject) {
    const url = Servers[serverId].urlToApi()+'/frames/index';

    let query = '';
    const ids = Object.keys(zm_events);

    while (ids.length) {
      const event_id = ids.shift();
      const zm_event = zm_events[event_id];
      if (zm_events.FramesById) continue;

      query += '/EventId:'+zm_event.Id;
      if ((!ids.length) || (query.length > 1000)) {
        $j.ajax(url+query+'.json?'+auth_relay, {
          timeout: 0,
          success: function(data) {
            if (data && data.frames && data.frames.length) {
              zm_event.FramesById = [];
              let last_frame = null;

              for (let i=0, len=data.frames.length; i<len; i++) {
                const frame = data.frames[i].Frame;
                const zm_event = events[frame.EventId];
                if (!zm_event) {
                  console.error("No event object found for " + data.frames[0].Frame.EventId);
                  continue;
                }
                if (last_frame && (frame.EventId != last_frame.EventId)) {
                  last_frame = null;
                }
                //console.log(date, frame.TimeStamp, frame.Delta, frame.TimeStampSecs);
                if (last_frame) {
                  frame.PrevFrameId = last_frame.Id;
                  last_frame.NextFrameId = frame.Id;
                  if (frame.TimeStampSecs >= last_frame.TimeStampSecs) {
                    last_frame.NextTimeStampSecs = frame.TimeStampSecs;
                  } else {
                    console.log("Out of order timestamps?", last_frame, frame);
                  }
                }
                last_frame = frame;

                if (!zm_event.FramesById) zm_event.FramesById = [];
                zm_event.FramesById[frame.Id] = frame;
              } // end foreach frame
            } else {
              console.log("No frames in data", data);
            } // end if there are frames
            drawGraph();
            resolve();
          },
          error: function() {
            logAjaxFail;
            reject(Error("There was an error"));
          }
        }); // end ajax
        query = '';
      } // end if query string is too long
    } // end while zm_events.legtnh
  } // end Promise
  );
} // end function loadFrames(Event)
