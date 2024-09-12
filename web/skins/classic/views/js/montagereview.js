
function evaluateLoadTimes() {
  // Only consider it a completed event if we load ALL monitors, then zero all and start again
  var start=0;
  var end=0;
  if ( liveMode != 1 && currentSpeed == 0 ) return; // don't evaluate when we are not moving as we can do nothing really fast.
  for ( var i = 0; i < monitorIndex.length; i++ ) {
    if ( monitorName[i] > "" ) {
      if ( monitorLoadEndTimems[i] == 0 ) return; // if we have a monitor with no time yet just wait
      if ( start == 0 || start > monitorLoadStartTimems[i] ) start = monitorLoadStartTimems[i];
      if ( end == 0 || end < monitorLoadEndTimems[i] ) end = monitorLoadEndTimems[i];
    }
  }
  if ( start == 0 || end == 0 ) return; // we really should not get here
  for ( var i=0; i < numMonitors; i++ ) {
    var monId = monitorPtr[i];
    monitorLoadStartTimems[monId] = 0;
    monitorLoadEndTimems[monId] = 0;
  }

  freeTimeLastIntervals[imageLoadTimesEvaluated++] = 1 - ((end - start)/currentDisplayInterval);
  if ( imageLoadTimesEvaluated < imageLoadTimesNeeded ) return;
  var avgFrac=0;
  for ( var i=0; i < imageLoadTimesEvaluated; i++ ) {
    avgFrac += freeTimeLastIntervals[i];
  }
  avgFrac = avgFrac / imageLoadTimesEvaluated;
  // The larger this is(positive) the faster we can go
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
  setSpeed(speedIndex);
  $j('#fps').text("Display refresh rate is " + (1000 / currentDisplayInterval).toFixed(1) + " per second, avgFrac=" + avgFrac.toFixed(3) + ".");
} // end evaluateLoadTimes()

function getFrame(monId, time, last_Frame) {
  if ( last_Frame ) {
    if (
      (last_Frame.TimeStampSecs <= time) &&
      (last_Frame.EndTimeStampSecs >= time)
    ) {
      return last_Frame;
    }
  }

  var events_for_monitor = events_by_monitor_id[monId];
  if ( !events_for_monitor ) {
    //console.log("No events for monitor " + monId);
    return;
  }

  var Frame = null;
  for ( var i = 0; i < events_for_monitor.length; i++ ) {
  //for ( var event_id_idx in events_for_monitor ) {
    var event_id = events_for_monitor[i];
    // Search for the event matching this time. Would be more efficient if we had events indexed by monitor
    e = events[event_id];
    if ( !e ) {
      console.log("No event found for " + event_id);
      break;
    }
    if ( e.MonitorId != monId || e.StartTimeSecs > time || e.EndTimeSecs < time ) {
      //console.log("Event not for " + time);
      continue;
    }

    if ( !e.FramesById ) {
      console.log("No FramesById for event " + event_id);
      return;
    }
    var duration = e.EndTimeSecs - e.StartTimeSecs;

    // I think this is an estimate to jump near the desired frame.
    var frame = parseInt((time - e.StartTimeSecs)/(duration)*Object.keys(e.FramesById).length)+1;
    //console.log("frame_id for " + time + " is " + frame);

    // Need to get frame by time, not some fun calc that assumes frames have the same length.
    // Frames are sorted in descreasing order (or not sorted).
    // This is likely not efficient.  Would be better to start at the last frame viewed, see if it is still relevant
    // Then move forward or backwards as appropriate

    for ( var frame_id in e.FramesById ) {
      if ( 0 ) {
        if ( frame == 0 ) {
          console.log("Found frame for time " + time);
          console.log(Frame);
          Frame = e.FramesById[frame_id];
          break;
        }
        frame --;
        continue;
      }
      if (
        e.FramesById[frame_id].TimeStampSecs == time ||
          (
            e.FramesById[frame_id].TimeStampSecs < time &&
            (
              (!e.FramesById[frame_id].NextTimeStampSecs) || // only if event.EndTime is null
             (e.FramesById[frame_id].NextTimeStampSecs > time)
            )
          )
      ) {
        Frame = e.FramesById[frame_id];
        break;
      }
    } // end foreach frame in the event.
    if ( !Frame ) {
      console.log("Didn't find frame for " + time);
      return null;
    }
  } // end foreach event
  return Frame;
}

// time is seconds since epoch
function getImageSource(monId, time) {
  if ( liveMode == 1 ) {
    var new_url = monitorImageObject[monId].src.replace(
        /rand=\d+/i,
        'rand='+Math.floor(Math.random() * 1000000)
    );
    if ( auth_hash ) {
      // update auth hash
      new_url = new_url.replace(/auth=[a-z0-9]+/i, 'auth='+auth_hash);
    }
    return new_url;
  }
  var frame_id;

  var Frame = getFrame(monId, time);
  if ( Frame ) {
    // Adjust for bulk frames
    if ( Frame.NextFrameId ) {
      var e = events[Frame.EventId];
      var NextFrame = e.FramesById[Frame.NextFrameId];
      if ( !NextFrame ) {
        console.log("No next frame for " + Frame.NextFrameId);
      } else if ( NextFrame.Type == 'Bulk' ) {
        // There is time between this frame and a bulk frame
        var duration = Frame.NextTimeStampSecs - Frame.TimeStampSecs;
        frame_id = Frame.FrameId + parseInt( (NextFrame.FrameId-Frame.FrameId) * ( time-Frame.TimeStampSecs )/duration );
        //console.log("Have NextFrame: duration: " + duration + " frame_id = " + frame_id + " from " + NextFrame.FrameId + ' - ' + Frame.FrameId + " time: " + (time-Frame.TimeStampSecs)  );
      } else {
        frame_id = Frame.FrameId;
      }
    } else {
      frame_id = Frame.FrameId;
      console.log("No NextFrame");
    }
    Event = events[Frame.EventId];

    var storage = Storage[Event.StorageId];
    if ( !storage ) {
      // Storage[0] is guaranteed to exist as we make sure it is there in montagereview.js.php
      console.log("No storage area for id " + Event.StorageId);
      storage = Storage[0];
    }
    // monitorServerId may be 0, which gives us the default Server entry
    var server = storage.ServerId ? Servers[storage.ServerId] : Servers[monitorServerId[monId]];
    return server.PathToIndex +
      '?view=image&eid=' + Frame.EventId + '&fid='+frame_id +
      "&width=" + monitorCanvasObj[monId].width +
      "&height=" + monitorCanvasObj[monId].height;
  } // end found Frame
  return '';
  //return "no data";
}

// callback when loading an image. Will load itself to the canvas, or draw no data
function imagedone( obj, monId, success ) {
  if ( success ) {
    var canvasCtx = monitorCanvasCtx[monId];
    var canvasObj = monitorCanvasObj[monId];

    canvasCtx.drawImage( monitorImageObject[monId], 0, 0, canvasObj.width, canvasObj.height );
    var iconSize=(Math.max(canvasObj.width, canvasObj.height) * 0.10);
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
      writeText( monId, "No Data" );
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

function loadNoData( monId ) {
  if ( monId ) {
    var canvasCtx = monitorCanvasCtx[monId];
    var canvasObj = monitorCanvasObj[monId];
    canvasCtx.fillStyle="white";
    canvasCtx.fillRect(0, 0, canvasObj.width, canvasObj.height);
    var textSize=canvasObj.width * 0.15;
    var text="No Data";
    canvasCtx.font = "600 " + textSize.toString() + "px Arial";
    canvasCtx.fillStyle="black";
    var textWidth = canvasCtx.measureText(text).width;
    canvasCtx.fillText(text, canvasObj.width/2 - textWidth/2, canvasObj.height/2);
  } else {
    console.log("No monId in loadNoData");
  }
}

function writeText( monId, text ) {
  if ( monId ) {
    var canvasCtx = monitorCanvasCtx[monId];
    var canvasObj = monitorCanvasObj[monId];
    //canvasCtx.fillStyle="white";
    //canvasCtx.fillRect(0, 0, canvasObj.width, canvasObj.height);
    var textSize=canvasObj.width * 0.15;
    canvasCtx.font = "600 " + textSize.toString() + "px Arial";
    canvasCtx.fillStyle="white";
    var textWidth = canvasCtx.measureText(text).width;
    canvasCtx.fillText(text, canvasObj.width/2 - textWidth/2, canvasObj.height/2);
  } else {
    console.log("No monId in loadNoData");
  }
}

// Either draws the
function loadImage2Monitor( monId, url ) {
  if ( monitorLoading[monId] && monitorImageObject[monId].src != url ) {
    // never queue the same image twice (if it's loading it has to be defined, right?
    monitorLoadingStageURL[monId] = url; // we don't care if we are overriting, it means it didn't change fast enough
  } else {
    if ( monitorImageObject[monId].src == url ) return; // do nothing if it's the same
    if ( url == 'no data' ) {
      writeText(monId, 'No Data');
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
    // zero just turn off interrupts
    clearInterval(timerObj);
    timerObj = null;
    timerInterval = currentDisplayInterval;
    console.log("Turn off interrupts timerInterfave" + timerInterval);
  }

  if ( (currentSpeed > 0 || liveMode != 0) && ! timerObj ) {
    timerObj = setInterval(timerFire, timerInterval); // don't fire out of live mode if speed is zero
  }

  if (liveMode) {
    outputUpdate(currentTimeSecs); // In live mode we basically do nothing but redisplay
  } else if (currentTimeSecs + playSecsPerInterval >= maxTimeSecs) {
    // beyond the end just stop
    console.log("Current time " + currentTimeSecs + " + " + playSecsPerInterval + " >= " + maxTimeSecs + " so stopping");
    if (speedIndex) setSpeed(0);
    outputUpdate(currentTimeSecs);
  } else {
    //console.log("Current time " + currentTimeSecs + " + " + playSecsPerInterval);
    outputUpdate(playSecsPerInterval + currentTimeSecs);
  }
  return;
}

// val is seconds?
function drawSliderOnGraph(val) {
  var sliderWidth=10;
  var sliderLineWidth=1;
  var sliderHeight=cHeight;

  if ( liveMode == 1 ) {
    val = Math.floor( Date.now() / 1000);
  }
  // Set some sizes

  var labelpx = Math.max( 6, Math.min( 20, parseInt(cHeight * timeLabelsFractOfRow / (numMonitors+1)) ) );
  var labbottom = parseInt(cHeight * 0.2 / (numMonitors+1)).toString() + "px"; // This is positioning same as row labels below, but from bottom so 1-position
  var labfont = labelpx + "px"; // set this like below row labels

  if ( numMonitors > 0 ) {
    // if we have no data to display don't do the slider itself
    var sliderX = parseInt((val - minTimeSecs) / rangeTimeSecs * cWidth - sliderWidth/2); // position left side of slider
    if ( sliderX < 0 ) sliderX = 0;
    if ( sliderX + sliderWidth > cWidth ) {
      sliderX = cWidth-sliderWidth-1;
    }

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
      ctx.strokeStyle = 'black';
      // looks like strokes are on the outside (or could be) so shrink it by the line width so we replace all the pixels
      ctx.strokeRect(sliderX+sliderLineWidth, sliderLineWidth, sliderWidth - 2*sliderLineWidth, sliderHeight - 2*sliderLineWidth);
      underSliderX = sliderX;
    }
    var o = document.getElementById('scruboutput');
    if ( liveMode == 1 ) {
      o.innerHTML = "Live Feed @ " + (1000 / currentDisplayInterval).toFixed(1) + " fps";
      o.style.color = "red";
    } else {
      o.innerHTML = secs2dbstr(val);
      o.style.color = "blue";
    }
    o.style.position = "absolute";
    o.style.bottom = labbottom;
    o.style.font = labfont;
    // try to get length and then when we get too close to the right switch to the left
    var len = o.offsetWidth;
    var x;
    if ( sliderX > cWidth/2 ) {
      x = sliderX - len - 10;
    } else {
      x = sliderX + 10;
    }
    o.style.left = x.toString() + "px";
  }

  // This displays (or not) the left/right limits depending on how close the slider is.
  // Because these change widths if the slider is too close, use the slider width as an estimate for the left/right label length (i.e. don't recalculate len from above)
  // If this starts to collide increase some of the extra space

  var o = document.getElementById('scrubleft');
  o.innerHTML = secs2dbstr(minTimeSecs);
  o.style.position = "absolute";
  o.style.bottom = labbottom;
  o.style.font = labfont;
  o.style.left = "5px";
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

  var o = document.getElementById('scrubright');
  o.innerHTML = secs2dbstr(maxTimeSecs);
  o.style.position = "absolute";
  o.style.bottom = labbottom;
  o.style.font = labfont;
  // If the slider will overlay part of this suppress (this is the right side)
  o.style.left=(cWidth - len - 15).toString() + "px";
  if ( sliderX > cWidth - len - 20 || cWidth < len * 4 ) {
    o.style.display = "none";
  } else {
    o.style.display = "inline";
    o.style.display = "inline-flex";
  }
}

function drawGraph() {
  var divWidth = document.getElementById('timelinediv').clientWidth;
  canvas.width = cWidth = divWidth; // Let it float and determine width (it should be sized a bit smaller percentage of window)
  cHeight = parseInt(window.innerHeight * 0.10);
  if ( cHeight < numMonitors * 20 ) {
    cHeight = numMonitors * 20;
  }

  canvas.height = cHeight;

  if ( events && ( Object.keys(events).length == 0 ) ) {
    ctx.globalAlpha = 1;
    ctx.font = "40px Georgia";
    ctx.fillStyle = "white";
    var t = "No data found in range - choose differently";
    var l = ctx.measureText(t).width;
    ctx.fillText(t, (cWidth - l)/2, cHeight-10);
    underSlider = undefined;
    return;
  }
  var rowHeight = parseInt(cHeight / (numMonitors + 1) ); // Leave room for a scale of some sort

  // first fill in the bars for the events (not alarms)

  for ( var event_id in events ) {
    var Event = events[event_id];

    // round low end down
    var x1 = parseInt((Event.StartTimeSecs - minTimeSecs) / rangeTimeSecs * cWidth);
    var x2 = parseInt((Event.EndTimeSecs - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 ); // round high end up to be sure consecutive ones connect
    ctx.fillStyle = monitorColour[Event.MonitorId];
    ctx.globalAlpha = 0.2; // light color for background
    ctx.clearRect(x1, monitorIndex[Event.MonitorId]*rowHeight, x2-x1, rowHeight); // Erase any overlap so it doesn't look artificially darker
    ctx.fillRect(x1, monitorIndex[Event.MonitorId]*rowHeight, x2-x1, rowHeight);

    for ( var frame_id in Event.FramesById ) {
      var Frame = Event.FramesById[frame_id];
      if ( ! Frame.Score ) {
        continue;
      }

      // Now put in scored frames (if any)
      var x1=parseInt( (Frame.TimeStampSecs - minTimeSecs) / rangeTimeSecs * cWidth); // round low end down
      var x2=parseInt( (Frame.TimeStampSecs - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 ); // round up
      if (x2-x1 < 2) x2=x1+2; // So it is visible make them all at least this number of seconds wide
      ctx.fillStyle=monitorColour[Event.MonitorId];
      ctx.globalAlpha = 0.4 + 0.6 * (1 - Frame.Score/maxScore); // Background is scaled but even lowest is twice as dark as the background
      ctx.fillRect(x1, monitorIndex[Event.MonitorId]*rowHeight, x2-x1, rowHeight);
    } // end foreach frame
  } // end foreach Event

  for ( var i=0; i < numMonitors; i++ ) {
    // Note that this may be a sparse array
    ctx.font = parseInt(rowHeight * timeLabelsFractOfRow).toString() + "px Georgia";
    ctx.fillStyle = "white";
    ctx.globalAlpha = 1;
    // This should roughly center font in row
    ctx.fillText(monitorName[monitorPtr[i]], 0, (i + 1 - (1 - timeLabelsFractOfRow)/2 ) * rowHeight);
  }
  underSlider = undefined; // flag we don't have a slider cached
  drawSliderOnGraph(currentTimeSecs);
  return;
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

  if ( liveMode == 1 ) {
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
  if ( fitMode == 1 ) {
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
    for ( var i=0; i<numMonitors; i++ ) {
      monitorCanvasObj[monitorPtr[i]].style.position="";
    }
    monitors.height('auto');
    scaleDiv.show();
    fit.text('fit');
    setScale(currentScale);
  }
  outputUpdate(currentTimeSecs);
  timerFire(); // force a fire in case it's not timing
}

function outputUpdate(time) {
  drawSliderOnGraph(time);
  for ( var i=0; i < numMonitors; i++ ) {
    var src = getImageSource(monitorPtr[i], time);
    //console.log("New image src: " + src);
    loadImage2Monitor(monitorPtr[i], src);
  }
  currentTimeSecs = time;
}

/// Found this here: http://stackoverflow.com/questions/55677/how-do-i-get-the-coordinates-of-a-mouse-click-on-a-canvas-element
function relMouseCoords(event) {
  var totalOffsetX = 0;
  var totalOffsetY = 0;
  var canvasX = 0;
  var canvasY = 0;
  var currentElement = this;

  do {
    totalOffsetX += currentElement.offsetLeft - currentElement.scrollLeft;
    totalOffsetY += currentElement.offsetTop - currentElement.scrollTop;
  } while (currentElement = currentElement.offsetParent);

  canvasX = event.pageX - totalOffsetX;
  canvasY = event.pageY - totalOffsetY;

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
    var sec = Math.floor(minTimeSecs + rangeTimeSecs / event.target.width * event.target.relMouseCoords(event).x);
    outputUpdate(sec);
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
  if ( ! parseInt(s) ) {
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
  fitMode=value;
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
  clicknav(minTimeSecs, maxTimeSecs, 0);
}
function click_panright() {
  minTimeSecs = parseInt(minTimeSecs + rangeTimeSecs/2);
  maxTimeSecs = minTimeSecs + rangeTimeSecs - 1;
  clicknav(minTimeSecs, maxTimeSecs, 0);
}
// Manage the DOWNLOAD VIDEO button
function click_download() {
  $j.getJSON(thisUrl + '?request=modal&modal=download')
      .done(function(data) {
        insertModalHtml('downloadModal', data.html);
        $j('#downloadModal').modal('show');
        // Manage the GENERATE DOWNLOAD button
        $j('#exportButton').click(exportEvent);
      })
      .fail(logAjaxFail);
}
function click_all_events() {
  clicknav(0, 0, 0);
}
function allnon() {
  clicknav(0, 0, 0);
}
/// >>>>>>>>>>>>>>>>> handles packing different size/aspect monitors on screen    <<<<<<<<<<<<<<<<<<<<<<<<

function compSize(a, b) { // sort array by some size parameter  - height seems to work best.  A semi-greedy algorithm
  var a_value = monitorHeight[a] * monitorWidth[a] * monitorNormalizeScale[a] * monitorZoomScale[a] * monitorNormalizeScale[a] * monitorZoomScale[a];
  var b_value = monitorHeight[b] * monitorWidth[b] * monitorNormalizeScale[b] * monitorZoomScale[b] * monitorNormalizeScale[b] * monitorZoomScale[b];

  if ( a_value > b_value ) return -1;
  else if ( a_value == b_value ) return 0;
  else return 1;
}

function maxfit2(divW, divH) {
  var bestFitX = []; // how we arranged the so-far best match
  var bestFitX2 = [];
  var bestFitY = [];
  var bestFitY2 = [];

  var minScale = 0.05;
  var maxScale = 5.00;
  var bestFitArea = 0;
  var borders_width=-1;
  var borders_height=-1;

  //monitorPtr.sort(compSize); //Sorts monitors by size in viewport.  If enabled makes captions not line up with graphs.

  while (1) {
    if ( maxScale - minScale < 0.01 ) break;
    var thisScale = (maxScale + minScale) / 2;
    var allFit=1;
    var thisArea=0;
    var thisX=[]; // top left
    var thisY=[];
    var thisX2=[]; // bottom right
    var thisY2=[];

    for ( var m = 0; m < numMonitors; m++ ) {
      // this loop places each monitor (if it can)
      var monId = monitorPtr[m];

      function doesItFit(x, y, w, h, d) { // does block (w,h) fit at position (x,y) relative to edge and other nodes already done (0..d)
        if (x+w>=divW) return 0;
        if (y+h>=divH) return 0;
        for ( var i=0; i <= d; i++ ) {
          if ( !( thisX[i]>x+w-1 || thisX2[i] < x || thisY[i] > y+h-1 || thisY2[i] < y ) ) return 0;
        }
        return 1; // it's OK
      }

      var monitor_div = $j('#Monitor'+monId);
      if ( borders_width <= 0 ) {
        borders_width = parseInt(monitor_div.css('border-left-width')) + parseInt(monitor_div.css('border-right-width'));
      }
      if ( borders_height <= 0) {
        borders_height = parseInt(monitor_div.css('border-top-width')) + parseInt(monitor_div.css('border-bottom-width'));
      } // assume fixed size border, and added to both sides and top/bottom
      // try fitting over first, then down.  Each new one must land at either upper right or lower left corner of last (try in that order)
      // Pick the one with the smallest Y, then smallest X if Y equal
      var fitX = 999999999;
      var fitY = 999999999;
      for ( adjacent = 0; adjacent < m; adjacent ++ ) {
        // try top right of adjacent
        if (doesItFit(
            thisX2[adjacent]+1,
            thisY[adjacent],
            monitorWidth[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_width,
            monitorHeight[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_height,
            m-1) == 1) {
          if ( thisY[adjacent]<fitY || ( thisY[adjacent] == fitY && thisX2[adjacent]+1 < fitX ) ) {
            fitX = thisX2[adjacent] + 1;
            fitY = thisY[adjacent];
          }
        }
        // try bottom left
        if (doesItFit(
            thisX[adjacent],
            thisY2[adjacent]+1,
            monitorWidth[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_width,
            monitorHeight[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_height,
            m-1) == 1) {
          if ( thisY2[adjacent]+1 < fitY || ( thisY2[adjacent]+1 == fitY && thisX[adjacent] < fitX ) ) {
            fitX = thisX[adjacent];
            fitY = thisY2[adjacent] + 1;
          }
        }
      } // end for adjacent < m
      if ( m == 0 ) { // note for the very first one there were no adjacents so the above loop didn't run
        if ( doesItFit(
            0, 0,
            monitorWidth[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_width,
            monitorHeight[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_height,
            -1) == 1 ) {
          fitX = 0;
          fitY = 0;
        }
      }
      if ( fitX == 999999999 ) {
        allFit = 0;
        break; // break out of monitor loop flagging we didn't fit
      }
      thisX[m] =fitX;
      thisX2[m]=fitX + monitorWidth[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders_width;
      thisY[m] =fitY;
      thisY2[m]=fitY + monitorHeight[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders_height;
      thisArea += (thisX2[m] - thisX[m])*(thisY2[m] - thisY[m]);
    } // end foreach monitor
    if ( allFit == 1 ) {
      minScale=thisScale;
      if (bestFitArea<thisArea) {
        bestFitArea=thisArea;
        bestFitX=thisX;
        bestFitY=thisY;
        bestFitX2=thisX2;
        bestFitY2=thisY2;
        bestFitScale=thisScale;
      }
    } else {
      // didn't fit
      maxScale=thisScale;
    }
  }
  if ( bestFitArea > 0 ) { // only rearrange if we could fit -- otherwise just do nothing, let them start coming out, whatever
    for ( m = 0; m < numMonitors; m++ ) {
      c = document.getElementById('Monitor' + monitorPtr[m]);
      c.style.position = 'absolute';
      c.style.left = bestFitX[m].toString() + "px";
      c.style.top = bestFitY[m].toString() + "px";
      c.width = bestFitX2[m] - bestFitX[m] + 1 - borders_width;
      c.height = bestFitY2[m] - bestFitY[m] + 1 - borders_height;
    }
    return 1;
  } else {
    return 0;
  }
}

// >>>>>>>>>>>>>>>> Handles individual monitor clicks and navigation to the standard event/watch display

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

function changeDateTime(e) {
  var minTime_element = $j('#minTime');
  var maxTime_element = $j('#maxTime');

  var minTime = moment(minTime_element.val());
  var maxTime = moment(maxTime_element.val());
  if ( minTime.isAfter(maxTime) ) {
    maxTime_element.parent().addClass('has-error');
    return; // Don't reload because we have invalid datetime filter.
  } else {
    maxTime_element.parent().removeClass('has-error');
  }

  var minStr = "&minTime="+($j('#minTime')[0].value);
  var maxStr = "&maxTime="+($j('#maxTime')[0].value);

  var liveStr="&live="+(liveMode?"1":"0");
  var fitStr ="&fit="+(fitMode?"1":"0");

  var zoomStr="";
  for ( var i=0; i < numMonitors; i++ ) {
    if ( monitorZoomScale[monitorPtr[i]] < 0.99 || monitorZoomScale[monitorPtr[i]] > 1.01 ) { // allow for some up/down changes and just treat as 1 of almost 1
      zoomStr += "&z" + monitorPtr[i].toString() + "=" + monitorZoomScale[monitorPtr[i]].toFixed(2);
    }
  }

  // Reloading can take a while, so stop interrupts to reduce load
  clearInterval(timerObj);
  timerObj = null;

  var uri = "?view=" + currentView + fitStr + minStr + maxStr + liveStr + zoomStr + "&scale=" + $j("#scaleslider")[0].value + "&speed=" + speeds[$j("#speedslider")[0].value];
  window.location = uri;
}

// >>>>>>>>> Initialization that runs on window load by being at the bottom

function initPage() {
  jQuery(document).ready(function() {
    jQuery("#hdrbutton").click(function() {
      jQuery("#flipMontageHeader").slideToggle("slow");
      jQuery("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
    });
  });

  if ( !liveMode ) {
    canvas = document.getElementById('timeline');

    canvas.addEventListener('mousemove', mmove, false);
    canvas.addEventListener('touchmove', tmove, false);
    canvas.addEventListener('mousedown', mdown, false);
    canvas.addEventListener('mouseup', mup, false);
    canvas.addEventListener('mouseout', mout, false);

    ctx = canvas.getContext('2d');
    drawGraph();
  }

  for ( var i = 0, len = monitorPtr.length; i < len; i += 1 ) {
    var monId = monitorPtr[i];
    if ( !monId ) continue;
    monitorCanvasObj[monId] = document.getElementById('Monitor'+monId);
    if ( !monitorCanvasObj[monId] ) {
      alert("Couldn't find DOM element for Monitor" + monId + "monitorPtr.length=" + len);
    } else {
      monitorCanvasCtx[monId] = monitorCanvasObj[monId].getContext('2d');
      var imageObject = monitorImageObject[monId] = new Image();
      imageObject.monId = monId;
      imageObject.onload = function() {
        imagedone(this, this.monId, true);
      };
      imageObject.onerror = function() {
        imagedone(this, this.monId, false);
      };
      loadImage2Monitor(monId, monitorImageURL[monId]);
      monitorCanvasObj[monId].addEventListener('click', clickMonitor, false);
    }
  } // end foreach monitor

  setSpeed(speedIndex);
  //setFit(fitMode);  // will redraw
  //setLive(liveMode);  // will redraw
  redrawScreen();
  $j('#minTime').datetimepicker({
    timeFormat: "HH:mm:ss",
    dateFormat: "yy-mm-dd",
    maxDate: +0,
    constrainInput: false,
    onClose: function(newDate, oldData) {
      if (newDate !== oldData.lastVal) {
        changeDateTime();
      }
    }
  });
  $j('#maxTime').datetimepicker({
    timeFormat: "HH:mm:ss",
    dateFormat: "yy-mm-dd",
    minDate: minTime,
    maxDate: +0,
    constrainInput: false,
    onClose: function(newDate, oldData) {
      if ( newDate !== oldData.lastVal ) {
        changeDateTime();
      }
    }
  });
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
}
window.addEventListener("resize", redrawScreen, {passive: true});
// Kick everything off
window.addEventListener('DOMContentLoaded', initPage);
