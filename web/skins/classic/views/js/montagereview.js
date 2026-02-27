"use strict";

var LOADING = true; // Default to true as initial state

var ajax = null;
var wait_for_events_interval = null;

var eventStreams = {}; // EventStream instances keyed by monitorId
var eventStreamsActive = false; // True when using EventStream mode
var isScrubbing = false; // True during mouse drag on timeline
var streamDriftThreshold = 2; // Seconds of drift before we seek to correct
var lastDriftCorrection = {}; // Epoch ms of last drift correction per monitorId
var driftCorrectionCooldown = 3000; // ms to wait between drift corrections

var minStartDateTimeElement = null;
var maxStartDateTimeElement = null;

var labelpx;
var labbottom; // This is positioning same as row labels below, but from bottom so 1-position
var labfont;

var sliderX = 0;
const sliderWidth = 10;
const sliderLineWidth = 1;
var sliderHeight = 0;
const scruboutput = document.getElementById('scruboutput');
const scrubleft = document.getElementById('scrubleft');
const scrubright = document.getElementById('scrubright');

// Populated by initPage with jquery
var dateTimeDiv;
var speedDiv;
var timeLineDiv;
var liveButton;
var zoomIn;
var zoomOut;
var panLeft;
var panRight;
var downloadVideo;
var scaleDiv;
var fit;

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
  // Note: multiply then round to 1 decimal place, keeping result as number
  let multiplier;
  if (avgFrac >= 0.9) multiplier = 0.50; // we can go much faster
  else if (avgFrac >= 0.8) multiplier = 0.55;
  else if (avgFrac >= 0.7) multiplier = 0.60;
  else if (avgFrac >= 0.6) multiplier = 0.65;
  else if (avgFrac >= 0.5) multiplier = 0.70;
  else if (avgFrac >= 0.4) multiplier = 0.80;
  else if (avgFrac >= 0.35) multiplier = 0.90;
  else if (avgFrac >= 0.3) multiplier = 1.00;
  else if (avgFrac >= 0.25) multiplier = 1.20;
  else if (avgFrac >= 0.2) multiplier = 1.50;
  else if (avgFrac >= 0.1) multiplier = 2.00;
  else multiplier = 2.50;
  currentDisplayInterval = Math.round(currentDisplayInterval * multiplier * 10) / 10;
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
    if (debug) {
      console.log("looking for "+time+" Start: " + arr[start].StartTimeSecs + ' End: ' + arr[end].EndTimeSecs);
    }
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
    console.warn("No array in findFrameByTime");
    return false;
  }
  const keys = Object.keys(arr);
  let start = 0;
  let end = keys.length-1;

  //console.log(keys);
  //console.log(keys[start]);
  // Iterate while start not meets end
  if (debug) console.log("Looking for "+ time+ "start: " + start + ' end ' + end, arr[keys[start]]);
  while ((start <= end)) {
    if ((arr[keys[start]].TimeStampSecs > time) || (arr[keys[end]].NextTimeStampSecs < time)) {
      // console.log(time + " not found in array of frames.", arr[keys[start]], arr[keys[end]]);
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
        console.warn("No event for ", frame.EventId);
        return frame;
      }

      if (frame.NextFrameId && e.FramesById) {
        var NextFrame = e.FramesById[frame.NextFrameId];
        if (!NextFrame) {
          // console.log("No nextframe for ", frame.NextFrameId);
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
      console.warn('findFrameByTime: unexpected state');
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

  /*
  if (!events_by_monitor_id[monId] || !events_by_monitor_id[monId].length) {
    // Need to load them?
    console.log("No events_by_monitor_id for " + monId);
    return;
  }

  */
  if ((!(monId in events_for_monitor)) || !events_for_monitor[monId].length) {
    //events_for_monitor[monId] = events_by_monitor_id[monId].map((x)=>events[x]);
    //if (!events_for_monitor[monId].length) {
    //console.log("No events for monitor " + monId);
    return;
    //}
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
      console.warn("Failed to find event for ", time, " but found it using linear search");
    }
  }
  if (!Event) {
    //console.log('No event found for ' + time + ' ' + secs2inputstr(time) + ' on monitor ' + monId, events_for_monitor[monId]);
    writeText( monId, "No event" );
    return;
  }

  if (!Event.FramesById) {
    // It is assumed at this time that every event has frames
    // console.log('No FramesById for event ', Event.Id);
    var event_list = {};
    event_list[Event.Id] = Event;
    loadFrames(event_list).then(function() {
      if (!Event.FramesById) {
        console.warn("No FramesById after loadFrames!", Event);
      }
      return findFrameByTime(Event.FramesById, time);
    }, function(Error) {
      console.warn(Error);
    });
    return;
  } else if (!Event.FramesById.length) {
    // console.log("frames loading for event " + Event.Id);
    return;
  }

  // Need to get frame by time, not some fun calc that assumes frames have the same length.
  // Frames are sorted in descreasing order (or not sorted).
  // This is likely not efficient.  Would be better to start at the last frame viewed, see if it is still relevant
  // Then move forward or backwards as appropriate
  let Frame = findFrameByTime(Event.FramesById, time);
  if (!Frame) {
    // console.log("Didn't find frame by binary search");
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
    // console.log("Didn't find frame for " + time);
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
      console.warn('No event found for ' + Frame.EventId);
      return '';
    }

    // Adjust for bulk frames
    if (Frame.NextFrameId) {
      if (!e.FramesById) {
        console.warn("No FramesById in event ", e.Id);
        return '';
      }
      const NextFrame = e.FramesById[Frame.NextFrameId];
      if (!NextFrame) {
        // console.log("No next frame for " + Frame.NextFrameId);
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
      // console.log("No frame_id from ", Frame);
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
    return server.PathToZMS + '?' +
    //mode=jpeg
      "mode=single" +
      "&event=" + Frame.EventId +
      //'&frame='+frame_id +
      '&time='+time +
      //"&width=" + monitorCanvasObj[monId].width +
      //"&height=" + monitorCanvasObj[monId].height +
      "&scale=" + scale +
      "&monitor=" + monId +
      "&frames=1" +
      "&rate=" + 100*speeds[speedIndex] +
      (auth_relay ? '&' + auth_relay : '');
  } // end found Frame
  return '';
} // end function getImageSource

// callback when loading an image. Will load itself to the canvas, or draw no data
function imagedone( obj, monId, success ) {
  // console.log("imagedone", obj, monId, success);
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
    var textSize = canvasObj.width * 0.15;
    canvasCtx.font = '600 ' + textSize.toString() + "px Arial";
    canvasCtx.fillStyle = 'white';
    var textWidth = canvasCtx.measureText(text).width;
    canvasCtx.fillText(text, canvasObj.width/2 - textWidth/2, canvasObj.height/2);
  } else {
    console.warn('No monId in writeText');
  }
}

// Either draws the
function loadImage2Monitor(monId, url) {
  if ( monitorLoading[monId] && (monitorImageObject[monId].src != url) ) {
    // never queue the same image twice (if it's loading it has to be defined, right?
    monitorLoadingStageURL[monId] = url; // we don't care if we are overriting, it means it didn't change fast enough
    // console.log("staging", monitorLoading[monId], monitorImageObject[monId].src, url);
  } else {
    if ( monitorImageObject[monId].src == url ) {
      // console.log("No change in url");
      return; // do nothing if it's the same
    }
    if ( url == 'no data' ) {
      writeText(monId, 'No Event');
    } else {
      //writeText(monId, 'Loading...');
      monitorLoading[monId] = true;
      monitorLoadStartTimems[monId] = new Date().getTime();
      // console.log("Loading", monitorImageObject[monId], url);
      monitorImageObject[monId].src = url; // starts a load but doesn't refresh yet, wait until ready
    }
  }
}

function timerFire() {
  // See if we need to reschedule
  if ( ( currentDisplayInterval != timerInterval ) || ( currentSpeed == 0 ) ) {
    // console.log("Turn off interrupts timerInterval", timerInterval, 'display interval:', currentDisplayInterval, 'speed', currentSpeed);
    // zero just turn off interrupts
    clearInterval(timerObj);
    timerObj = null;
    timerInterval = currentDisplayInterval;
  }

  if (liveMode) {
    //outputUpdate(currentTimeSecs); // In live mode we basically do nothing but redisplay
  } else if (currentTimeSecs + playSecsPerInterval >= maxTimeSecs) {
    // beyond the end just stop
    if (speedIndex) setSpeed(0);
    //outputUpdate(currentTimeSecs);
  } else if (playSecsPerInterval || (currentTimeSecs==minTimeSecs)) {
    currentTimeSecs = playSecsPerInterval + currentTimeSecs;
    //outputUpdate(playSecsPerInterval + currentTimeSecs);
  } else {
    // console.log("Not updating");
  }
  outputUpdate(currentTimeSecs);

  if ((currentSpeed > 0 || liveMode != 0) && !timerObj) {
    timerObj = setInterval(timerFire, timerInterval); // don't fire out of live mode if speed is zero
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

  // If we have data already saved first restore it from LAST time
  if ( typeof underSlider !== 'undefined' ) {
    ctx.putImageData(underSlider, sliderX, 0, 0, 0, sliderWidth, sliderHeight);
    underSlider = undefined;
  }

  // if we have no data to display don't do the slider itself
  sliderX = parseInt((val - minTimeSecs) / rangeTimeSecs * cWidth - sliderWidth/2); // position left side of slider
  if ( sliderX < 0 ) sliderX = 0;
  if ( sliderX + sliderWidth > cWidth ) sliderX = cWidth-sliderWidth-1;

  if ( liveMode == 1 ) {
    scruboutput.innerHTML = 'Live Feed @ ' + (1000 / currentDisplayInterval).toFixed(1) + ' fps';
    scruboutput.style.color = 'red';
  } else { //if (!liveMode) {
    // we get rid of the slider if we switch to live (since it may not be in the "right" place)
    // Now save where we are putting it THIS time
    underSlider = ctx.getImageData(sliderX, 0, sliderWidth, sliderHeight);
    ctx.globalAlpha = 1;
    // And add in the slider'
    ctx.lineWidth = sliderLineWidth;
    ctx.strokeStyle = 'yellow';
    // looks like strokes are on the outside (or could be) so shrink it by the line width so we replace all the pixels
    ctx.strokeRect(sliderX+sliderLineWidth, sliderLineWidth, sliderWidth - 2*sliderLineWidth, sliderHeight - 2*sliderLineWidth);

    scruboutput.innerHTML = secs2dbstr(val);
    scruboutput.style.color = 'yellow'; // make it different from left and right so we know which is which
  }
  // try to get length and then when we get too close to the right switch to the left
  var len = scruboutput.offsetWidth;

  const x = (sliderX > cWidth/2) ? sliderX - len - 10 : sliderX + 10;
  scruboutput.style.left = x.toString() + "px";

  // This displays (or not) the left/right limits depending on how close the slider is.
  // Because these change widths if the slider is too close, use the slider width as an estimate for the left/right label length (i.e. don't recalculate len from above)
  // If this starts to collide increase some of the extra space

  // If the slider will overlay part of this suppress (this is the left side)
  if ( len + 10 > sliderX || cWidth < len * 4 ) {
    // that last check is for very narrow browsers
    scrubleft.style.display = "none";
  } else {
    scrubleft.style.display = "inline";
    scrubleft.style.display = "inline-flex"; // safari won't take this but will just ignore
  }

  if ( sliderX > cWidth - len - 20 || cWidth < len * 4 ) {
    scrubright.style.display = "none";
  } else {
    scrubright.style.display = "inline";
    scrubright.style.display = "inline-flex";
  }
} // end function drawSliderOnGraph(val)

function drawFrameOnGraph(frame) {
  if (!frame.Score) {
    return;
  }
  const MonitorId = parseInt(events[frame.EventId].MonitorId);

  // Now put in scored frames (if any)
  const x1 = parseInt( (frame.TimeStampSecs - minTimeSecs) / rangeTimeSecs * cWidth); // round low end down
  let x2 = parseInt( (frame.TimeStampSecs - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 ); // round up
  if (x2-x1 < 2) x2=x1+2; // So it is visible make them all at least this number of seconds wide
  ctx.fillStyle = monitorColour[MonitorId];
  ctx.globalAlpha = 0.4 + 0.6 * (1 - frame.Score/maxScore); // Background is scaled but even lowest is twice as dark as the background
  ctx.fillRect(x1, monitorIndex[MonitorId]*rowHeight, x2-x1, rowHeight-2);
  //console.log("Drew frame from", x1, MonitorId, monitorIndex[MonitorId]*rowHeight, x2-x1, rowHeight, monitorColour[MonitorId]);
}

function drawEventOnGraph(zm_event) {
  if (!zm_event.StartTimeSecs) {
    console.warn("No time data in event", zm_event.Id);
    return;
  }

  // round low end down
  const x1 = parseInt((zm_event.StartTimeSecs - minTimeSecs) / rangeTimeSecs * cWidth);
  if (!zm_event.EndTimeSecs) zm_event.EndTimeSecs = maxTimeSecs;
  // round high end up to be sure consecutive ones connect
  const x2 = parseInt((zm_event.EndTimeSecs - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 );
  if (!monitorColour[zm_event.MonitorId]) {
    console.warn("No colour for monitor", zm_event.MonitorId);
    ctx.fillStyle = '#43bcf2';
  } else {
    ctx.fillStyle = monitorColour[zm_event.MonitorId];
  }
  ctx.globalAlpha = 0.2; // light color for background
  // Erase any overlap so it doesn't look artificially darker
  ctx.clearRect(x1, monitorIndex[zm_event.MonitorId]*rowHeight, x2-x1, rowHeight);
  ctx.fillRect(x1, monitorIndex[zm_event.MonitorId]*rowHeight, x2-x1, rowHeight-2);
  //outputUpdate(currentTimeSecs);
  //console.log("Drew event from ", x1, monitorIndex[Event.MonitorId]*rowHeight, x2-x1, rowHeight);
}

function drawGraph() {
  if (!canvas) {
    // Likely live mode
    // console.log("Called drawGraph while in live mode");
    return;
  }
  underSlider = undefined; // flag we don't have a slider cached

  // timelinediv starts off 100% of browser, but it's container can be smaller
  const divWidth = Math.min(timeLineDiv.width(), timeLineDiv.parent().width() );

  canvas.width = cWidth = divWidth; // Let it float and determine width (it should be sized a bit smaller percentage of window)
  canvas.height = cHeight = Math.max(parseInt(window.innerHeight * 0.10) /* 10% */, numMonitors * 20);

  /* Clear timeline */
  if (0) {
    ctx.fillStyle = '#000000';
    ctx.globalAlpha = 1;
    ctx.fillRect(0, 0, cWidth, cHeight);
  }

  rowHeight = parseInt(cHeight / (numMonitors + 1) ); // Leave room for a scale of some sort
  sliderHeight = cHeight;

  labelpx = Math.max( 6, Math.min( 20, parseInt(cHeight * timeLabelsFractOfRow / (numMonitors+1)) ) );
  labbottom = parseInt(cHeight * 0.2 / (numMonitors+1)).toString() + "px"; // This is positioning same as row labels below, but from bottom so 1-position
  labfont = labelpx + "px"; // set this like below row labels

  // if we have no data to display don't do the slider itself
  let sliderX = parseInt((currentTimeSecs - minTimeSecs) / rangeTimeSecs * cWidth - sliderWidth/2); // position left side of slider
  if ( sliderX < 0 ) sliderX = 0;
  if ( sliderX + sliderWidth > cWidth ) sliderX = cWidth-sliderWidth-1;

  scruboutput.style.position = 'absolute';
  scruboutput.style.bottom = labbottom;
  scruboutput.style.font = labfont;
  var len = scruboutput.offsetWidth;
  // console.log('sruboutput.offsetWidth', len);

  // This displays (or not) the left/right limits depending on how close the slider is.
  // Because these change widths if the slider is too close, use the slider width as an estimate for the left/right label length (i.e. don't recalculate len from above)
  // If this starts to collide increase some of the extra space

  const scrubleft = document.getElementById('scrubleft');
  scrubleft.innerHTML = secs2dbstr(minTimeSecs);
  scrubleft.style.position = 'absolute';
  scrubleft.style.bottom = labbottom;
  scrubleft.style.font = labfont;
  scrubleft.style.left = '5px';
  if ( len + 10 > sliderX || cWidth < len * 4 ) {
    // that last check is for very narrow browsers
    scrubleft.style.display = "none";
  } else {
    scrubleft.style.display = "inline";
    scrubleft.style.display = "inline-flex"; // safari won't take this but will just ignore
  }

  const scrubright = document.getElementById('scrubright');
  scrubright.innerHTML = secs2dbstr(maxTimeSecs);
  scrubright.style.position = 'absolute';
  scrubright.style.bottom = labbottom;
  scrubright.style.font = labfont;
  scrubright.style.right = "5px";

  if ( sliderX > cWidth - len - 20 || cWidth < len * 4 ) {
    scrubright.style.display = "none";
  } else {
    scrubright.style.display = "inline";
    scrubright.style.display = "inline-flex";
  }

  /* Maybe this should be done in loadEvents and be per monitor
  if (events && ( Object.keys(events).length == 0 ) ) {
    ctx.font = "40px Georgia";
    ctx.globalAlpha = 1;
    ctx.fillStyle = "white";
    const t = LOADING ? "Loading events" : "No events found.";
    var l = ctx.measureText(t).width;
    ctx.fillText(t, (cWidth - l)/2, cHeight-10);
    console.log("No events, returning");
    return;
  }
  */


  // first fill in the bars for the events (not alarms)
  // At first, no events loaded, that's ok, later, we will have some events, should only draw those in the time range.
  for (const event_id in events) {
    const zm_event = events[event_id];
    drawEventOnGraph(zm_event);
    if (zm_event.FramesById) {
      for (const frame_id in zm_event.FramesById ) {
        const frame = zm_event.FramesById[frame_id];
        if (!frame.Score) continue;
        drawFrameOnGraph(frame);
      } // end foreach frame
    } else {
      // console.log("No FramesById", zm_event);
    }
  } // end foreach Event

  for (let i=0; i < numMonitors; i++) {
    // Apparently we have to set these each time before calling fillText
    ctx.font = parseInt(rowHeight * timeLabelsFractOfRow).toString() + "px Georgia";
    ctx.globalAlpha = 1;
    ctx.fillStyle = "white";
    // This should roughly center font in row
    ctx.fillText(monitorName[monitorPtr[i]], 0, (i + 1 - (1 - timeLabelsFractOfRow)/2 ) * rowHeight);
    // console.log("Drawing ", monitorName[monitorPtr[i]], 0, (i + 1 - (1 - timeLabelsFractOfRow)/2 ) * rowHeight);
  }

  drawSliderOnGraph(currentTimeSecs);
} // end function drawGraph

function redrawScreen() {
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
      console.warn("Failed to fit, dropping back to scaled mode");
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
  timerFire(); // force a fire in case it's not timing. timerFirst will call outputUpdate
} // end function redrawScreen

function outputUpdate(time) {
  if (eventStreamsActive && currentSpeed >= 1 && Object.keys(events).length !== 0) {
    // EventStream path — persistent MJPEG streaming for speeds >= 1x.
    // zms handles frame delivery and timing at the correct rate.
    for (let i = 0; i < numMonitors; i++) {
      const monId = monitorPtr[i];
      const es = eventStreams[monId];
      if (!es) continue;

      if (!(monId in events_for_monitor) || !events_for_monitor[monId].length) {
        if (es.started) es.stop();
        writeText(monId, 'No Event');
        continue;
      }

      const ev = findEventByTime(events_for_monitor[monId], time);

      if (ev) {
        if (!es.started) {
          // Stream not running — start it on this event
          es.start(ev.Id, {
            rate: 100 * currentSpeed,
            maxfps: 15,
            time: time
          });
        } else if (es.currentEventId != ev.Id) {
          // Crossed event boundary — switch to new event
          es.switchEvent(ev.Id, {
            rate: 100 * currentSpeed,
            maxfps: 15,
            time: time
          });
        } else if (isScrubbing) {
          // User is dragging the timeline — seek within current event
          const offset = time - ev.StartTimeSecs;
          es.seek(Math.max(0, offset));
        }
      } else {
        // No event at this time for this monitor
        if (es.started) es.stop();
        writeText(monId, 'No Event');
      }
    }
  } else if (Object.keys(events).length !== 0) {
    // Per-frame loading path — used for speeds < 1x, speed 0, live
    // mode, or when EventStreams are not active.  Each frame is an
    // independent mode=single HTTP request to zms.  This works well
    // at slow speeds where there is ample time between frames and
    // avoids fighting zms's lack of slow-motion support.
    for (let i = 0; i < numMonitors; i++) {
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
  isScrubbing = true;
  // Pause EventStreams during scrub for responsive seeking
  if (eventStreamsActive) {
    for (let i = 0; i < numMonitors; i++) {
      const monId = monitorPtr[i];
      if (eventStreams[monId] && eventStreams[monId].started) eventStreams[monId].pause();
    }
  }
  mmove(event);
}
function mup(event) {
  mouseisdown=false;
  isScrubbing = false;
  // Sync and resume EventStreams after scrub (only if streaming mode)
  if (eventStreamsActive && currentSpeed >= 1) {
    syncEventStreamsToTime(currentTimeSecs);
    for (let i = 0; i < numMonitors; i++) {
      const monId = monitorPtr[i];
      if (eventStreams[monId] && eventStreams[monId].started) eventStreams[monId].play();
    }
  }
}
function mout(event) {
  mouseisdown=false;
  if (isScrubbing) {
    isScrubbing = false;
    if (eventStreamsActive && currentSpeed >= 1) {
      syncEventStreamsToTime(currentTimeSecs);
      for (let i = 0; i < numMonitors; i++) {
        const monId = monitorPtr[i];
        if (eventStreams[monId] && eventStreams[monId].started) eventStreams[monId].play();
      }
    }
  }
} // if we go outside treat it as release
function tmove(event) {
  mouseisdown=true;
  mmove(event);
}

function mmove(event) {
  if ( mouseisdown ) {
    // only do anything if the mouse is depressed while on the sheet
    const relx = event.target.relMouseCoords(event).x;
    const sec = minTimeSecs + rangeTimeSecs / event.target.width * relx;
    if (sec) outputUpdate(sec);
  }
}

function secs2inputstr(s) {
  if ( ! parseInt(s) ) {
    console.warn("Invalid value for " + s + " seconds");
    return '';
  }

  var m = moment(s*1000);
  if ( ! m ) {
    console.warn("No valid date for " + s + " seconds");
    return '';
  }
  return m.format("YYYY-MM-DDTHH:mm:ss");
}

function secs2dbstr(s) {
  if (!parseInt(s)) {
    console.warn("Invalid value for " + s + " seconds");
    return '';
  }
  var m = moment(s*1000);
  if ( ! m ) {
    console.warn("No valid date for " + s + " milliseconds");
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
  for ( let i=0; i < numMonitors; i++ ) {
    monitorCanvasObj[monitorPtr[i]].width = monitorWidth[monitorPtr[i]]*monitorNormalizeScale[monitorPtr[i]]*monitorZoomScale[monitorPtr[i]]*newscale;
    monitorCanvasObj[monitorPtr[i]].height = monitorHeight[monitorPtr[i]]*monitorNormalizeScale[monitorPtr[i]]*monitorZoomScale[monitorPtr[i]]*newscale;
  }
  currentScale = newscale;
}

function showSpeed(val) {
  // updates slider only
  $j('#speedslideroutput').text(parseFloat(speeds[val]).toFixed(2).toString() + " x");
}

// Seek all running EventStreams to match the current slider position.
// Called after speed changes and during periodic drift correction.
function syncEventStreamsToTime(time) {
  for (let i = 0; i < numMonitors; i++) {
    const monId = monitorPtr[i];
    if (!eventStreams[monId] || !eventStreams[monId].started) continue;
    if (!(monId in events_for_monitor) || !events_for_monitor[monId].length) continue;
    const ev = findEventByTime(events_for_monitor[monId], time);
    if (ev) {
      const offset = time - ev.StartTimeSecs;
      eventStreams[monId].seek(Math.max(0, offset));
    }
  }
}

function setSpeed(speed_index) {
  if (liveMode == 1) {
    console.warn("setSpeed in liveMode?");
    return; // we shouldn't actually get here but just in case
  }
  currentSpeed = parseFloat(speeds[speed_index]);
  speedIndex = speed_index;
  playSecsPerInterval = currentSpeed * currentDisplayInterval / 1000;
  setCookie('speed', currentSpeed);
  showSpeed(speed_index);

  // Manage EventStream mode transitions.
  // >= 1x: zms streams MJPEG at the correct rate (EventStream path)
  // < 1x:  zms can't slow down, so stop streams and let outputUpdate
  //         fall back to per-frame mode=single loading.
  if (eventStreamsActive) {
    if (currentSpeed >= 1) {
      // Normal/fast — ensure streams are running at the right rate.
      // Streams that were stopped (from slow mode) will be restarted
      // automatically by outputUpdate() on the next call.
      for (let i = 0; i < numMonitors; i++) {
        const monId = monitorPtr[i];
        if (!eventStreams[monId] || !eventStreams[monId].started) continue;
        eventStreams[monId].setRate(100 * currentSpeed);
        if (eventStreams[monId].paused) eventStreams[monId].play();
      }
      // Re-sync position after rate change
      setTimeout(function() {
        syncEventStreamsToTime(currentTimeSecs);
      }, 500);
    } else {
      // Stopped or slow — stop EventStreams so their rAF draw loop
      // doesn't compete with per-frame canvas drawing.
      for (let i = 0; i < numMonitors; i++) {
        const monId = monitorPtr[i];
        if (eventStreams[monId] && eventStreams[monId].started) {
          eventStreams[monId].stop();
        }
      }
    }
  }

  timerFire();
}

function setLive(value) {
  // Stop all EventStreams before switching modes
  if (eventStreamsActive) {
    for (let i = 0; i < numMonitors; i++) {
      const monId = monitorPtr[i];
      if (eventStreams[monId] && eventStreams[monId].started) {
        eventStreams[monId].stop();
      }
    }
    eventStreamsActive = false;
  }
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
    minStr = "&minTime=1950-01-01+12:00:00";
    maxStr = "&maxTime=2035-12-31+12:00:00";
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
  for ( let i = 0; i < numMonitors; i++ ) {
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
  // May Need to update minTimeSecs and maxTimeSecs
  // Also, if StartDateTime <= or >= are changed, limit max duration to 24h

  if (minStartDateTimeElement && maxStartDateTimeElement) {
    let minStartDateTime = DateTime.fromFormat(minStartDateTimeElement.value, 'yyyy-MM-dd HH:mm:ss', {zone: ZM_TIMEZONE});
    let maxStartDateTime = DateTime.fromFormat(maxStartDateTimeElement.value, 'yyyy-MM-dd HH:mm:ss', {zone: ZM_TIMEZONE});

    if (this === minStartDateTimeElement) {
      if (minStartDateTime > maxStartDateTime) {
        maxStartDateTime = minStartDateTime.plus({hours: 1}); // Maybe leave a gap?
        maxStartDateTimeElement.value = minStartDateTimeElement.value;
      } else {
        const diff = maxStartDateTime.diff(minStartDateTime, 'seconds').toObject();
        if (diff.seconds > 86400) { // 1 day
          maxStartDateTime = minStartDateTime.plus({days: 1});
          maxStartDateTimeElement.value = maxStartDateTime.toFormat('yyyy-MM-dd HH:mm:ss');
        }
      }
    } else if (this === maxStartDateTimeElement) {
      if (minStartDateTime > maxStartDateTime) {
        minStartDateTime = maxStartDateTime; // Maybe leave a gap?
        minStartDateTimeElement.value = maxStartDateTimeElement.value;
      } else {
        const diff = minStartDateTime.diff(maxStartDateTime).toObject();
        if (diff.milliseconds > 86400*1000) { // 1 day
          minStartDateTime = maxStartDateTime.plus({days: -1});
          minStartDateTimeElement.value = maxStartDateTime.toFormat('yyyy-MM-dd HH:mm:ss');
        }
      }
    } else {
      console.warn("Not changed min/max");
    } // end if a datetime or something else

    minTime = minStartDateTime.toFormat('yyyy-MM-dd HH:mm:ss');
    minTimeSecs = minStartDateTime.valueOf()/1000;
    maxTime = maxStartDateTime.toFormat('yyyy-MM-dd HH:mm:ss');
    maxTimeSecs = maxStartDateTime.valueOf()/1000;
    rangeTimeSecs = maxTimeSecs - minTimeSecs;
    // On any change, jump to beginning ? No...
    if (currentTimeSecs < minTimeSecs) {
      // console.log("currentTimeSecs < minTimeSecs setting to ", minTimeSecs, minTime);
      currentTimeSecs = minTimeSecs;
    } else if (currentTimeSecs > maxTimeSecs) {
      // console.log("currentTimeSecs > maxTimeSecs setting to ", maxTimeSecs, maxTime);
      currentTimeSecs = minTimeSecs; // Not sure about this one.
    }
  } else {
    console.warn("Don't have min/max date elements");
  }

  for (var key in events) {
    delete events[key];
  }
  for (var key in events_for_monitor) {
    events_for_monitor[key] = [];
  }
  LOADING = true;
  // Reloading can take a while, so stop interrupts to reduce load
  timerObj = clearInterval(timerObj);

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
            if (attr.value==='Monitor') attr.value='MonitorId';
            let urlVal = val;
            // Normalize date/time values to YYYY-MM-DD HH:mm:ss for the API URL.
            // Locale formats using / as separator break the URL path.
            if (/Date|Time/.test(attr.value)) {
              const m = moment(val);
              if (m.isValid()) {
                urlVal = m.format('YYYY-MM-DD HH:mm:ss');
              }
            }
            url += '/'+attr.value+' '+op.value+':'+encodeURIComponent(urlVal);
          } else {
            console.warn('No attr for '+attr_name);
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
      console.warn("No events in response", data.result);
      return;
    }

    if (data.events.length) {
      // event_list is solely for sending to loadFrames
      const event_list = {};
      for (let i=0, len = data.events.length; i<len; i++) {
        const ev = data.events[i].Event;
        ev.Id = parseInt(ev.Id);
        ev.MonitorId = parseInt(ev.MonitorId);
        event_list[ev.Id] = events[ev.Id] = ev;

        if ((!(ev.MonitorId in events_for_monitor)) || !events_for_monitor[ev.MonitorId]) {
          events_for_monitor[ev.MonitorId] = []; // id=>event
        }
        //events_by_monitor_id[ev.MonitorId].push(ev.Id);
        events_for_monitor[ev.MonitorId].push(ev);
        //drawEventOnGraph(ev);
      }
      loadFrames(event_list).then(function() {
        // console.log("have frames, drawing graph");
        drawGraph();
        /*
        // HACK to refresh monitor names over event data
        for (let i=0; i < numMonitors; i++) {
          // Apparently we have to set these each time before calling fillText
          ctx.font = parseInt(rowHeight * timeLabelsFractOfRow).toString() + "px Georgia";
          ctx.globalAlpha = 1;
          ctx.fillStyle = "white";
          // This should roughly center font in row
          ctx.fillText(monitorName[monitorPtr[i]], 0, (i + 1 - (1 - timeLabelsFractOfRow)/2 ) * rowHeight);
        }
        //underSlider = ctx.getImageData(sliderX, 0, sliderWidth, sliderHeight);
        */
      });
    } else {
      // console.log("No events in data?");
    }
  } // end function receive_events

  //FIXME ajax gets overwrritten by subsequent monitor
  if (ajax) ajax.abort();

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
          console.error("loadEventData error", jqXHR.status);
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
      }
    });
  }
  LOADING = false;
  return;
} // end function loadEventData

function initPage() {
  dateTimeDiv = $j('#DateTimeDiv');
  speedDiv = $j('#SpeedDiv');
  timeLineDiv = $j('#timelinediv');
  liveButton = $j('#liveButton');
  zoomIn = $j('#zoomin');
  zoomOut = $j('#zoomout');
  panLeft = $j('#panleft');
  panRight = $j('#panright');
  downloadVideo = $j('#downloadVideo');
  scaleDiv = $j('#ScaleDiv');
  fit = $j('#fit');

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
  } else {
    // console.log("Live mode");
  }

  for (let i = 0, len = monitorPtr.length; i < len; i += 1) {
    getMinMaxStartDateTimeElements();

    const monId = monitorPtr[i];
    if (!monId) continue;
    const canvasObj = monitorCanvasObj[monId] = document.getElementById('Monitor'+monId);
    if ( !monitorCanvasObj[monId] ) {
      alert("Couldn't find DOM element for Monitor" + monId + "monitorPtr.length=" + len);
      continue;
    }
    // console.log("Setting up imagedone for ", monId);
    monitorCanvasCtx[monId] = monitorCanvasObj[monId].getContext('2d');

    const imageObject = monitorImageObject[monId] = new Image();
    imageObject.monId = monId;
    imageObject.onload = function() {
      imagedone(this, this.monId, true);
    };
    imageObject.onerror = function() {
      imagedone(this, this.monId, false);
    };
    if (liveMode) {
      loadImage2Monitor(monId, monitorImageURL[monId]);
    }
    canvasObj.addEventListener('click', clickMonitor, false);
  } // end foreach monitor

  // Create EventStream instances for replay mode
  if (liveMode != 1) {
    for (let i = 0; i < numMonitors; i++) {
      const monId = monitorPtr[i];
      if (!monId || !monitorCanvasObj[monId]) continue;
      const server = Servers[monitorServerId[monId]] || Servers[0];
      let scale = parseInt(100 * monitorCanvasObj[monId].width / monitorWidth[monId]);
      scale = Math.max(10, 10 * parseInt(scale / 10));

      eventStreams[monId] = new EventStream({
        monitorId: monId,
        monitorWidth: monitorWidth[monId],
        monitorHeight: monitorHeight[monId],
        url: thisUrl,
        url_to_zms: server.PathToZMS,
        canvas: monitorCanvasObj[monId],
        scale: scale
      });

      // Draw zoom +/- icons after each frame
      eventStreams[monId].onFrameDrawn = function(canvas) {
        const ctx = canvas.getContext('2d');
        const iconSize = Math.max(canvas.width, canvas.height) * 0.10;
        ctx.font = '600 ' + iconSize.toString() + 'px Arial';
        ctx.fillStyle = 'white';
        ctx.globalCompositeOperation = 'difference';
        ctx.fillText('+', iconSize * 0.2, iconSize * 1.2);
        ctx.fillText('-', canvas.width - iconSize * 1.2, iconSize * 1.2);
        ctx.globalCompositeOperation = 'source-over';
      };

      // Drift correction: for speeds >= 1x, periodically check zms
      // position vs slider and seek if they diverge.  For speeds < 1x,
      // outputUpdate() handles positioning via periodic seeks so we
      // skip drift correction here to avoid conflicting commands.
      eventStreams[monId].onStatus = (function(mid) {
        return function(status) {
          // Only drift-correct during normal/fast streaming
          if (isScrubbing || !eventStreamsActive) return;
          if (currentSpeed < 1) return;
          if (!status.progress && status.progress !== 0) return;
          if (!status.event) return;

          // Compute where zms actually is in wall-clock time
          const ev = events[status.event];
          if (!ev || !ev.StartTimeSecs) return;
          const zmsTimeSecs = ev.StartTimeSecs + status.progress;

          // Compare to where the JS timer thinks we are
          const drift = Math.abs(zmsTimeSecs - currentTimeSecs);
          if (drift > streamDriftThreshold) {
            const now = Date.now();
            if (!lastDriftCorrection[mid] ||
                (now - lastDriftCorrection[mid]) > driftCorrectionCooldown) {
              lastDriftCorrection[mid] = now;
              // Seek zms to match the slider position
              const targetEv = findEventByTime(events_for_monitor[mid], currentTimeSecs);
              if (targetEv) {
                if (targetEv.Id == status.event) {
                  // Same event, just seek within it
                  const offset = currentTimeSecs - targetEv.StartTimeSecs;
                  eventStreams[mid].seek(Math.max(0, offset));
                } else {
                  // zms is on a different event than expected — switch
                  eventStreams[mid].switchEvent(targetEv.Id, {
                    rate: 100 * currentSpeed,
                    maxfps: 15,
                    time: currentTimeSecs
                  });
                }
              }
            }
          }
        };
      })(monId);
    }
    eventStreamsActive = true;
  }

  setSpeed(speedIndex);
  //setFit(fitMode);  // will redraw
  //setLive(liveMode);  // will redraw
  loadEventData();
  wait_for_events();
  redrawScreen(); // calls drawGraph

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

  if (navbar_type != 'left') {
    // If new menu is used, then Datepicker initialization occurs in main "skin.js"
    // Reinitialization is not allowed because the 'Destroy' method is missing.
    initDatepickerMontageReviewPage();
  }
}

function initDatepickerMontageReviewPage() {
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
    if (!wait_for_events_interval) {
      wait_for_events_interval = setInterval(wait_for_events, 1000);
    }
  } else {
    clearInterval(wait_for_events_interval);
    wait_for_events_interval = null;
    // console.log("Have events, starting time");
    timerFire();
  }
}

function takeSnapshot() {
  const monitor_ids = [];
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

/* Expects an Object, not an array, of EventId=>Event mappings. */
function loadFrames(zm_events) {
  return new Promise(function(resolve, reject) {
    const url = Servers[serverId].urlToApi()+'/frames/index';

    let query = '';
    const ids = Object.keys(zm_events);

    while (ids.length) {
      const event_id = ids.shift();
      {
        const zm_event = zm_events[event_id];
        if (zm_event.FramesById) {
          // console.log('already loaded FramesById', zm_event);
          continue;
        }
        zm_event.FramesById = []; //Signal that we are loading them
      }
      query += '/EventId:'+event_id;

      if ((!ids.length) || (query.length > 1000)) {
        $j.ajax(url+query+'.json?'+auth_relay, {
          timeout: 0,
          success: function(data) {
            if (data && data.frames && data.frames.length) {
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
                if (last_frame) {
                  frame.PrevFrameId = last_frame.Id;
                  last_frame.NextFrameId = frame.Id;
                  if (frame.TimeStampSecs >= last_frame.TimeStampSecs) {
                    last_frame.NextTimeStampSecs = frame.TimeStampSecs;
                  } else {
                    console.warn("Out of order timestamps?", frame.EventId, frame.Id);
                  }
                }
                last_frame = frame;

                //if (!zm_event.FramesById) zm_event.FramesById = [];
                zm_event.FramesById[frame.Id] = frame;
                //drawFrameOnGraph(frame);
              } // end foreach frame
            } else {
              // console.log("No frames in data", data);
            } // end if there are frames
            resolve();
          },
          error: function(jqXHR) {
            logAjaxFail(jqXHR);
            reject(Error("There was an error"));
          }
        }); // end ajax
        query = '';
      } // end if query string is too long
    } // end while zm_events.legtnh
  } // end Promise
  );
} // end function loadFrames(Event)

function getMinMaxStartDateTimeElements() {
  const regexp = /^filter\[Query\]\[terms\]\[(\d+)\]\[attr\]$/;
  $j('#fieldsTable input[value="StartDateTime"]').each(function(index) {
    const matches = this.name.match(regexp);
    if (matches && matches.length) {
      const val = this.form.elements['filter[Query][terms]['+matches[1]+'][val]'];
      if (val) {
        const op = this.form.elements['filter[Query][terms]['+matches[1]+'][op]'];
        if (op.value == '>=') {
          minStartDateTimeElement = val;
        } else if (op.value == '<=') {
          maxStartDateTimeElement = val;
        } else {
          console.warn('unknown op', op.value);
        }
      } else {
        console.warn("no val ", matches);
      }
    }
  });
  if (!(minStartDateTimeElement && maxStartDateTimeElement)) {
    console.warn("Didn't find a min/max StartDateTime");
  }
  if (minStartDateTimeElement == maxStartDateTimeElement) {
    console.warn("Have same a min/max StartDateTime");
  }
}
