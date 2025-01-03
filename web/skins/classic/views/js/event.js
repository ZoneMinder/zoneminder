"use strict";
var eventStats = $j('#eventStats');
var eventVideo = $j('#eventVideo');
var wrapperEventVideo = $j('#wrapperEventVideo');
var videoFeed = $j('#videoFeed');
var eventStatsTable = $j('#eventStatsTable');
var backBtn = $j('#backBtn');
var renameBtn = $j('#renameBtn');
var archiveBtn = $j('#archiveBtn');
var unarchiveBtn = $j('#unarchiveBtn');
var editBtn = $j('#editBtn');
var exportBtn = $j('#exportBtn');
var downloadBtn = $j('#downloadBtn');
var statsBtn = $j('#statsBtn');
var deleteBtn = $j('#deleteBtn');
var prevEventId = 0;
var nextEventId = 0;
var prevEventStartTime = 0;
var nextEventStartTime = 0;
var PrevEventDefVideoPath = "";
var NextEventDefVideoPath = "";
var currEventId = null;
var CurEventDefVideoPath = null;
var vid = null;
var spf = Math.round((eventData.Length / eventData.Frames)*1000000 )/1000000;//Seconds per frame for videojs frame by frame.
var intervalRewind;
var revSpeed = .5;
var cueFrames = null; //make cueFrames available even if we don't send another ajax query
var streamCmdInterval = null;
var streamStatus = null;
var lastEventId = 0;
var zmsBroke = false; //Use alternate navigation if zms has crashed
var wasHidden = false;
var availableTags = [];
var selectedTags = [];

var PrevCoordinatFrame = {x: null, y: null};
var coordinateMouse = {
  start_x: null, start_y: null,
  shiftMouse_x: null, shiftMouse_y: null,
  shiftMouseForTrigger_x: null, shiftMouseForTrigger_y: null
};
var leftBtnStatus = {Down: false, UpAfterDown: false};
var updateScale = false; //Scale needs to be updated
var currentScale = 100; // Temporarily, because need to put things in order with the "scale" variable = "select" block

$j(document).on("keydown", "", function(e) {
  e = e || window.event;
  if (!$j(".tag-input").is(":focus")) {
    if ( $j(".modal").is(":visible") ) {
      if (e.key === "Enter") {
        if ( $j("#deleteConfirm").is(":visible") ) {
          $j("#delConfirmBtn").click();
        } else if ( $j("#eventDetailModal").is(":visible") ) {
          $j("#eventDetailSaveBtn").click();
        }
      } else if (e.key === "Escape") {
        $j(".modal").modal('hide');
      } else {
        console.log('Modal is visible: key not implemented: ', e.key, '  keyCode: ', e.keyCode);
      }
    } else {
      if (e.key === "ArrowLeft" && !e.altKey) {
        prevEvent();
      } else if (e.key === "ArrowRight" && !e.altKey) {
        nextEvent();
      } else if (e.key === "Delete") {
        if ( $j("#deleteBtn").is(":disabled") == false ) {
          $j("#deleteBtn").click();
        }
      } else if (e.keyCode === 32) {
        // space bar for Play/Pause
        if ( $j("#playBtn").is(":visible") ) {
          playClicked();
        } else {
          pauseClicked();
        }
      } else if (e.key === "ArrowDown") {
        if (e.ctrlKey) {
          addTag(availableTags[0]);
        } else {
          $j("#tagInput").focus();
          showDropdown();
        }
      } else if (e.ctrlKey && (e.shift || e.shiftKey)) {
        //Panning (moving the enlarged frame)
      } else if (e.shift || e.shiftKey) {
        //Panning
      } else {
        console.log('Modal is not visible: key not implemented: ', e.key, '  keyCode: ', e.keyCode);
      }
    }
  }
});

function streamReq(data) {
  if (auth_hash) data.auth = auth_hash;
  data.connkey = connKey;
  data.view = 'request';
  data.request = 'stream';

  $j.getJSON(monitorUrl+'?'+auth_relay, data)
      .done(getCmdResponse)
      .fail(logAjaxFail);
}

// Function called when video.js hits the end of the video
function vjsReplay() {
  switch ( replayMode.value ) {
    case 'none':
      break;
    case 'single':
      vid.play();
      break;
    case 'all':
      if ( nextEventId == 0 ) {
        const overLaid = $j('#videoobj');
        overLaid.append('<p class="vjsMessage" style="height: '+overLaid.height()+'px; line-height: '+overLaid.height()+'px;">No more events</p>');
      } else {
        if (!eventData.EndDateTime) {
          // No EndTime but have a next event, just go to it.
          streamNext(true);
          return;
        }
        const date = Date.parse(eventData.EndDateTime);
        if (!date) {
          console.error('Got no date from ', eventData);
          streamNext(true);
          return;
        } else if (typeof date.getTime === 'undefined') {
          console.log("Failed to get valid date object from EndDateTime in ", eventData);
        } else {
          const endTime = date.getTime();
          const nextStartTime = nextEventStartTime.getTime(); //nextEventStartTime.getTime() is a mootools workaround, highjacks Date.parse
          if ( nextStartTime <= endTime ) {
            streamNext(true);
            return;
          }
          vid.pause();
          const overLaid = $j("#videoobj");
          overLaid.append('<p class="vjsMessage" style="height: '+overLaid.height()+'px; line-height: '+overLaid.height()+'px;"></p>');
          const gapDuration = (new Date().getTime()) + (nextStartTime - endTime);
          const messageP = $j('.vjsMessage');
          const x = setInterval(function() {
            const now = new Date().getTime();
            const remainder = new Date(Math.round(gapDuration - now)).toISOString().substr(11, 8);
            messageP.html(remainder + ' to next event.');
            if ( remainder < 0 ) {
              clearInterval(x);
              streamNext(true);
            }
          }, 1000);
        } // end if valid date object
      } // end if have nextEventId
      break;
    case 'gapless':
      streamNext(true);
      break;
  }
} // end function vjsReplay

function initialAlarmCues(eventId) {
  //get frames data for alarmCues and inserts into html
  $j.getJSON(thisUrl + '?view=request&request=status&entity=frames&id=' + eventId)
      .done(setAlarmCues)
      .fail(logAjaxFail);
}

function setAlarmCues(data) {
  if (!data) {
    Error('No data in setAlarmCues for event ' + eventData.Id);
  } else if (!data.frames) {
    Error('No data.frames in setAlarmCues for event ' + eventData.Id);
  } else {
    cueFrames = data.frames;
    const alarmSpans = renderAlarmCues(vid ? $j("#videoobj") : $j("#evtStream"));//use videojs width or zms width
    $j('#alarmCues').html(alarmSpans);
  }
}

function renderAlarmCues(containerEl) {
  let html = '';

  const event_length = (!cueFrames.length || (eventData.Length > cueFrames[cueFrames.length - 1].Delta)) ? eventData.Length : cueFrames[cueFrames.length - 1].Delta;
  const span_count = 10;
  const span_seconds = parseFloat(event_length / span_count);
  const span_width = parseFloat(containerEl.width() / span_count);
  const date = new Date(eventData.StartDateTime);
  for (let i=0; i < span_count; i += 1) {
    html += '<span style="left:'+(i*span_width)+'px; width: '+span_width+'px;">'+date.toLocaleTimeString()+'</span>';
    date.setTime(date.getTime() + span_seconds*1000);
  }

  if (!(cueFrames && cueFrames.length)) {
    console.log('No cue frames for event');
    return html;
  }
  // This uses the Delta of the last frame to get the length of the event.  I can't help but wonder though
  // if we shouldn't just use the event length endtime-starttime
  var cueRatio = containerEl.width() / (event_length * 100);
  var minAlarm = Math.ceil(1/cueRatio);
  var spanTime = 0;
  var spanTimeStart = 0;
  var spanTimeEnd = 0;
  var alarmed = 0;
  var alarmHtml = '';
  var pix = 0;
  var pixSkew = 0;
  var skip = 0;
  var num_cueFrames = cueFrames.length;
  let left = 0;

  for (let i=0; i < num_cueFrames; i++) {
    skip = 0;
    const frame = cueFrames[i];

    if ((frame.Type == 'Alarm') && (alarmed == 0)) { //From nothing to alarm.  End nothing and start alarm.
      alarmed = 1;
      if (frame.Delta == 0) continue; //If event starts with an alarm or too few for a nonespan
      spanTimeEnd = frame.Delta * 100;
      spanTime = spanTimeEnd - spanTimeStart;
      pix = cueRatio * spanTime;
      pixSkew += pix - Math.round(pix);//average out the rounding errors.
      pix = Math.round(pix);
      if ((pixSkew > 1 || pixSkew < -1) && pix + Math.round(pixSkew) > 0) { //add skew if it's a pixel and won't zero out span.
        pix += Math.round(pixSkew);
        pixSkew = pixSkew - Math.round(pixSkew);
      }

      alarmHtml += '<span class="noneCue" style="left: '+left+'px; width: ' + pix + 'px;"></span>';
      left = parseInt((frame.Delta / event_length) * containerEl.width());
      //console.log(left, frame.Delta, event_length, containerEl.width());
      spanTimeStart = spanTimeEnd;
    } else if ( (frame.Type !== 'Alarm') && (alarmed == 1) ) { //from alarm to nothing.  End alarm and start nothing.
      let futNone = 0;
      let indexPlus = i+1;
      if (((frame.Delta * 100) - spanTimeStart) < minAlarm && indexPlus < num_cueFrames) {
        //alarm is too short and there is more event
        continue;
      }
      while ( futNone < minAlarm ) { //check ahead to see if there's enough for a nonespan
        if ( indexPlus >= cueFrames.length ) break; //check if end of event.
        futNone = (cueFrames[indexPlus].Delta *100) - (frame.Delta *100);
        if ( cueFrames[indexPlus].Type == 'Alarm' ) {
          i = --indexPlus;
          skip = 1;
          break;
        }
        indexPlus++;
      }
      if ( skip == 1 ) continue; //javascript doesn't support continue 2;
      spanTimeEnd = frame.Delta *100;
      spanTime = spanTimeEnd - spanTimeStart;
      alarmed = 0;
      pix = cueRatio * spanTime;
      pixSkew += pix - Math.round(pix);
      pix = Math.round(pix);
      if ((pixSkew > 1 || pixSkew < -1) && pix + Math.round(pixSkew) > 0) {
        pix += Math.round(pixSkew);
        pixSkew = pixSkew - Math.round(pixSkew);
      }
      alarmHtml += '<span class="alarmCue" style="left: '+left+'px; width: ' + pix + 'px; height: '+frame.Score+'px;"></span>';
      left = parseInt((frame.Delta / event_length) * containerEl.width());
      spanTimeStart = spanTimeEnd;
    } else if ( (frame.Type == 'Alarm') && (alarmed == 1) && (i + 1 >= cueFrames.length) ) { //event ends on an alarm
      spanTimeEnd = frame.Delta * 100;
      spanTime = spanTimeEnd - spanTimeStart;
      alarmed = 0;
      pix = Math.round(cueRatio * spanTime);
      if (pixSkew >= .5 || pixSkew <= -.5) pix += Math.round(pixSkew);

      alarmHtml += '<span class="alarmCue" style="left: '+left+'px; width: ' + pix + 'px; height: '+frame.Score+'px;"></span>';
    }
  }
  return html + alarmHtml;
}

function changeCodec() {
  location.replace(thisUrl + '?view=event&eid=' + eventData.Id + filterQuery + sortQuery+'&codec='+$j('#codec').val());
}

function deltaScale() {
  return parseInt(currentScale/100*$j('#streamQuality').val()); // "-" - Decrease quality, "+" - Increase image quality in %
}

function changeScale() {
  const scaleSel = $j('#scale').val();
  let newWidth;
  let newHeight;
  const eventViewer = $j(vid ? '#videoobj' : '#evtStream');

  const alarmCue = $j('#alarmCues');
  const bottomEl = $j('#replayStatus');
  const landscape = eventData.width / eventData.height > 1 ? true : false; //Image orientation.

  setCookie('zmEventScale'+eventData.MonitorId, scaleSel);

  /*!!! eventData.Width & eventData.Height may differ from the actual size of the broadcast frame due to the "Capture Resolution (pixels)" setting on the source page of the monitor settings !!!*/
  /*!!! Because of this, the Scale is not correct. For example, when recording in 4k, Capture Resolution = FHD, the image with a width of 600px looks terrible! */

  let newSize;
  if (scaleSel == '100') {
    //Actual, 100% of original size
    newWidth = eventData.Width;
    newHeight = eventData.Height;
    currentScale = 100;
  } else if (scaleSel == '0') {
    //Auto, Width is calculated based on the occupied height so that the image and control buttons occupy the visible part of the screen.
    newSize = scaleToFit(eventData.Width, eventData.Height, eventViewer, bottomEl, $j('#wrapperEventVideo'));
    newWidth = newSize.width;
    newHeight = newSize.height;
    currentScale = newSize.autoScale ? newSize.autoScale : 100;
  } else if (scaleSel == 'fit_to_width') {
    //Fit to screen width
    newSize = scaleToFit(eventData.Width, eventData.Height, eventViewer, false, $j('#wrapperEventVideo'));
    newWidth = newSize.width;
    newHeight = newSize.height;
    currentScale = newSize.autoScale ? newSize.autoScale : 100;
  } else if (scaleSel.indexOf("px") > -1) {
    newSize = scaleToFit(eventData.Width, eventData.Height, eventViewer, false, $j('#wrapperEventVideo')); // Only for calculating the maximum width!
    let w = 0;
    let h = 0;
    if (landscape) {
      w = Math.min(stringToNumber(scaleSel), newSize.width);
      h = w / (eventData.Width / eventData.Height);
    } else {
      h = Math.min(stringToNumber(scaleSel), newSize.height);
      w = h * (eventData.Width / eventData.Height);
    }
    newWidth = parseInt(w);
    newHeight = parseInt(h);
    currentScale = parseInt(w / eventData.Width * 100);
    currentScale = currentScale;
  }

  console.log(`Real dimensions: ${eventData.Width} X ${eventData.Height}, Scale: ${currentScale}, deltaScale: ${deltaScale()}, New dimensions: ${newWidth} X ${newHeight}`);

  eventViewer.width(newWidth);
  eventViewer.height(newHeight);
  if (!vid) { // zms needs extra sizing
    streamScale(currentScale);
    drawProgressBar();
  }
  if (cueFrames) {
    //just re-render alarmCues.  skip ajax call
    alarmCue.html(renderAlarmCues(videoFeed));
  }

  setButtonSizeOnStream();
  // After a resize, check if we still have room to display the event stats table
  onStatsResize(newWidth);

  //updateScale = true;

  /* OLD version
  scale = parseFloat($j('#scale').val());
  setCookie('zmEventScale'+eventData.MonitorId, scale);

  let newWidth;
  let newHeight;
  const eventViewer = $j(vid ? '#videoobj' : '#evtStream');

  const alarmCue = $j('#alarmCues');
  const bottomEl = $j('#replayStatus');

  if (!scale) {
    const newSize = scaleToFit(eventData.Width, eventData.Height, eventViewer, bottomEl, $j('#wrapperEventVideo'));
    newWidth = newSize.width;
    newHeight = newSize.height;
    scale = newSize.autoScale;
  } else {
    $j(window).off('resize', endOfResize); //remove resize handler when Scale to Fit is not active
    newWidth = eventData.Width * scale / SCALE_BASE;
    newHeight = eventData.Height * scale / SCALE_BASE;
  }
  eventViewer.width(newWidth);
  eventViewer.height(newHeight);
  if (!vid) { // zms needs extra sizing
    streamScale(scale);
    drawProgressBar();
  }
  if (cueFrames) {
    //just re-render alarmCues.  skip ajax call
    alarmCue.html(renderAlarmCues(videoFeed));
  }

  // After a resize, check if we still have room to display the event stats table
  onStatsResize(newWidth);
  */
} // end function changeScale

function changeStreamQuality() {
  const streamQuality = $j('#streamQuality').val();
  setCookie('zmStreamQuality', streamQuality);
  streamScale(currentScale);
}

function changeReplayMode() {
  const replayMode = $j('#replayMode').val();
  setCookie('replayMode', replayMode);
  // FIXME don't need to refresh, can just tell zms
  refreshWindow();
}

function changeRate() {
  const rate = parseInt($j('select[name="rate"]').val());

  if (!rate) {
    pauseClicked();
  } else if (rate < 0) {
    if (vid) { //There is no reverse play with mp4.  Set the speed to 0 and manually set the time back.
      revSpeed = rates[rates.indexOf(-1*rate)-1]/100;
      clearInterval(intervalRewind);
      intervalRewind = setInterval(function() {
        if (vid.currentTime() <= 0) {
          clearInterval(intervalRewind);
          vid.pause();
        } else {
          vid.playbackRate(0);
          vid.currentTime(vid.currentTime() - (revSpeed/2)); //Half of reverse speed because our interval is 500ms.
        }
      }, 500); //500ms is a compromise between smooth reverse and realistic performance
    } else {
      streamReq({command: CMD_VARPLAY, rate: rate});
    } // end if vid
  } else { // Forward rate
    if ( vid ) {
      vid.playbackRate(rate/100);
    } else {
      streamReq({command: CMD_VARPLAY, rate: rate});
    }
  }
  setCookie('zmEventRate', rate);
} // end function changeRate

function getCmdResponse(respObj, respText) {
  if ( checkStreamForErrors('getCmdResponse', respObj) ) {
    console.log('Got an error from getCmdResponse');
    console.log(respObj);
    console.log(respText);
    zmsBroke = true;
    return;
  }

  zmsBroke = false;

  streamStatus = respObj.status;
  if (!streamStatus) {
    console.log('No status in respObj');
    console.log(respObj);
    return;
  } else if (streamStatus.duration && ( streamStatus.duration != parseFloat(eventData.Length) )) {
    eventData.Length = streamStatus.duration;
  }
  if (streamStatus.progress > parseFloat(eventData.Length)) {
    console.log("Limiting progress to " + streamStatus.progress + ' >= ' + parseFloat(eventData.Length) );
    //streamStatus.progress = parseFloat(eventData.Length);
  } //Limit progress to reality

  const eventId = streamStatus.event;
  if (lastEventId) {
    if (eventId != lastEventId) {
      //Doesn't run on first load, prevents a double hit on event and nearEvents ajax
      eventQuery(eventId);
      initialAlarmCues(eventId); //zms uses this instead of a page reload, must call ajax+render
      lastEventId = eventId;
    }
  } else {
    lastEventId = eventId; //Only fires on first load.
  }

  if (streamStatus.paused == true) {
    streamPause();
  } else {
    $j('select[name="rate"]').val(streamStatus.rate*100);
    setCookie('zmEventRate', streamStatus.rate*100);
    streamPlay();
  }
  $j('#progressValue').html(secsToTime(parseInt(streamStatus.progress)));
  //$j('#zoomValue').html(streamStatus.zoom);
  const pz = zmPanZoom.panZoom[eventData.MonitorId];
  if (pz) $j('#zoomValue').html(pz.getScale().toFixed(1));
  //if (streamStatus.zoom == '1.0') {
  //  setButtonState('zoomOutBtn', 'unavail');
  //} else {
  //  setButtonState('zoomOutBtn', 'inactive');
  //}

  if (currentScale && (streamStatus.scale !== undefined) && (streamStatus.scale != currentScale + deltaScale())) {
    console.log("Stream not scaled, re-applying, current: ", currentScale + deltaScale(), " stream: ", streamStatus.scale);
    streamScale(currentScale);
  }
  const fps = document.getElementById('fpsValue');
  if (fps) {
    fps.innerHTML = streamStatus.fps;
  }

  updateProgressBar();

  if (streamStatus.auth) {
    auth_hash = streamStatus.auth;
  } // end if have a new auth hash
} // end function getCmdResponse( respObj, respText )

function pauseClicked() {
  if (vid) {
    if (intervalRewind) {
      stopFastRev();
    }
    vid.pause();
  } else {
    streamReq({command: CMD_PAUSE});
  }
  streamPause();
}

function streamPause() {
  $j('#modeValue').html('Paused');

  $j('#pauseBtn').hide();
  $j('#playBtn').show();
  setButtonState('pauseBtn', 'active');
  setButtonState('playBtn', 'inactive');
  setButtonState('fastFwdBtn', 'unavail');
  setButtonState('slowFwdBtn', 'inactive');
  setButtonState('slowRevBtn', 'inactive');
  setButtonState('fastRevBtn', 'unavail');
}

function playClicked( ) {
  const rate_select = $j('select[name="rate"]');

  if (!rate_select.val()) {
    rate_select.val(100);
  }
  if (vid) {
    if (vid.paused()) {
      vid.play();
    } else {
      vjsPlay(); //handles fast forward and rewind
    }
  } else {
    if (zmsBroke) {
      // The assumption is that the command failed because zms exited, so restart the stream.
      const img = document.getElementById('evtStream');
      const src = img.src;
      const url = new URL(src);
      url.searchParams.set('scale', currentScale); // In event.php we don’t yet know what scale to substitute. Let it be for now.
      img.src = '';
      img.src = url;
      zmsBroke = false;
    } else {
      streamReq({command: CMD_PLAY});
    }
  }
  streamPlay();
}

function vjsPlay() { //catches if we change mode programatically
  if (intervalRewind) {
    stopFastRev();
  }
  $j('select[name="rate"]').val(vid.playbackRate()*100);
  setCookie('zmEventRate', vid.playbackRate()*100);
  streamPlay();
}

function streamPlay( ) {
  $j('#pauseBtn').show();
  $j('#playBtn').hide();
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'active');
  setButtonState('fastFwdBtn', 'inactive');
  setButtonState('slowFwdBtn', 'unavail');
  setButtonState('slowRevBtn', 'unavail');
  setButtonState('fastRevBtn', 'inactive');
}

function streamFastFwd(action) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('fastFwdBtn', 'active');
  setButtonState('slowFwdBtn', 'unavail');
  setButtonState('slowRevBtn', 'unavail');
  setButtonState('fastRevBtn', 'inactive');
  if (vid) {
    if (revSpeed != .5) stopFastRev();
    vid.playbackRate(rates[rates.indexOf(vid.playbackRate()*100)+1]/100);
    if (rates.indexOf(vid.playbackRate()*100)+1 == rates.length) {
      setButtonState('fastFwdBtn', 'unavail');
    }
    $j('select[name="rate"]').val(vid.playbackRate()*100);
    setCookie('zmEventRate', vid.playbackRate()*100);
  } else {
    streamReq({command: CMD_FASTFWD});
  }
}

function streamSlowFwd(action) {
  if (vid) {
    vid.currentTime(vid.currentTime() + spf);
  } else {
    streamReq({command: CMD_SLOWFWD});
  }
}

function streamSlowRev(action) {
  if (vid) {
    vid.currentTime(vid.currentTime() - spf);
  } else {
    streamReq({command: CMD_SLOWREV});
  }
}

function stopFastRev() {
  clearInterval(intervalRewind);
  vid.playbackRate(1);
  $j('select[name="rate"]').val(vid.playbackRate()*100);
  setCookie('zmEventRate', vid.playbackRate()*100);
  revSpeed = .5;
}

/* Called when rewind button is clicked
 * should cycle through the reverse rates including pause
 */
function streamFastRev(action) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('fastFwdBtn', 'inactive');
  setButtonState('slowFwdBtn', 'unavail');
  setButtonState('slowRevBtn', 'unavail');
  setButtonState('fastRevBtn', 'active');
  if (vid) { //There is no reverse play with mp4.  Set the speed to 0 and manually set the time back.
    revSpeed = -1*(rates[rates.indexOf(revSpeed*-100)-1]/100);
    if (rates.indexOf(revSpeed*-100) == 0) {
      setButtonState('fastRevBtn', 'unavail');
    }
    clearInterval(intervalRewind);
    $j('select[name="rate"]').val(-revSpeed*100);
    setCookie('zmEventRate', vid.playbackRate()*100);
    intervalRewind = setInterval(function() {
      if (vid.currentTime() <= 0) {
        clearInterval(intervalRewind);
        vid.pause();
      } else {
        vid.playbackRate(0);
        vid.currentTime(vid.currentTime() - (revSpeed/2)); //Half of reverse speed because our interval is 500ms.
      }
    }, 500); //500ms is a compromise between smooth reverse and realistic performance
  } else {
    streamReq({command: CMD_FASTREV});
  }
}

function streamPrev(action) {
  if (action) {
    $j(".vjsMessage").remove();
    if (prevEventId != 0) {
      if (vid==null) streamReq({command: CMD_QUIT});
      location.replace(thisUrl + '?view=event&eid=' + prevEventId + filterQuery + sortQuery);
      return;
    }

    /* Ideally I'd like to get back to this style
    if ( vid && PrevEventDefVideoPath.indexOf("view_video") > 0 ) {
      CurEventDefVideoPath = PrevEventDefVideoPath;
      eventQuery(prevEventId);
    } else if (zmsBroke || (vid && PrevEventDefVideoPath.indexOf("view_video") < 0) || $j("#vjsMessage").length || PrevEventDefVideoPath.indexOf("view_video") > 0) {//zms broke, leaving videojs, last event, moving to videojs
      location.replace(thisUrl + '?view=event&eid=' + prevEventId + filterQuery + sortQuery);
    } else {
      streamReq({command: CMD_PREV});
      streamPlay();
    }
    */
  }
}

function streamNext(action) {
  if (!action) {
    return;
  }

  $j(".vjsMessage").remove();//This shouldn't happen
  if (nextEventId == 0) { //handles deleting last event.
    pauseClicked();
    const hideContainer = $j('#eventVideo');
    const hideStream = $j(vid ? "#videoobj" : "#evtStream").height() + (vid ? 0 :$j("#progressBar").height());
    hideContainer.prepend('<p class="vjsMessage" style="height: ' + hideStream + 'px; line-height: ' + hideStream + 'px;">No more events</p>');
    if (vid == null) zmsBroke = true;
    return;
  }
  // We used to try to dynamically update all the bits in the page, which is really complex
  // How about we just reload the page?
  // Ic0n 2024-09-20: because it is annoying and now that we have fullscreen mode, we need to just update instead of reloading
  if (1) {
    if (vid==null) streamReq({command: CMD_QUIT});
    location.replace(thisUrl + '?view=event&eid=' + nextEventId + filterQuery + sortQuery);
    return;
  }
  if (vid && ( NextEventDefVideoPath.indexOf('view_video') > 0 )) {
    // on and staying with videojs
    CurEventDefVideoPath = NextEventDefVideoPath;
    eventQuery(nextEventId);
  } else if (
    zmsBroke ||
    (vid && NextEventDefVideoPath.indexOf("view_video") < 0) ||
    NextEventDefVideoPath.indexOf("view_video") > 0
  ) {//reload zms, leaving vjs, moving to vjs
    // Need to be able to replace video.js with zms or vice versa as required instead of reloading
    location.replace(thisUrl + '?view=event&eid=' + nextEventId + filterQuery + sortQuery);
  } else {
    streamReq({command: CMD_NEXT});
    streamPlay();
  }
} // end function streamNext(action)

function tagAndNext(action) {
  addTag(availableTags[0]);
  streamNext(action);
}

function tagAndPrev(action) {
  addTag(availableTags[0]);
  streamPrev(action);
}

/* Not used
function vjsPanZoom(action, x, y) { //Pan and zoom with centering where the click occurs
  var outer = $j('#videoobj');
  var video = outer.children().first();
  var zoom = parseFloat($j('#zoomValue').html());
  var zoomRate = .5;
  var matrix = video.css('transform').split(',');
  var currentPanX = parseFloat(matrix[4]);
  var currentPanY = parseFloat(matrix[5]);
  var xDist = outer.width()/2 - x; //Click distance from center of view
  var yDist = outer.height()/2 - y;
  if (action == 'zoomOut') {
    zoom -= zoomRate;
    if (x && y) {
      x = (xDist + currentPanX)*((zoom-zoomRate)/zoom); // if ctrl-click Pan but use ratio of old zoom to new zoom for coords
      y = (yDist + currentPanY)*((zoom-zoomRate)/zoom);
    } else {
      x = currentPanX*((zoom-zoomRate)/zoom); //Leave zoom centered where it was
      y = currentPanY*((zoom-zoomRate)/zoom);
    }
    if (zoom <= 1) {
      zoom = 1;
      $j('#zoomOutBtn').attr('class', 'unavail').attr('disabled', 'disabled');
    }
    $j('#zoomValue').html(zoom);
  } else if (action == 'zoom') {
    zoom += zoomRate;
    x = (xDist + currentPanX)*(zoom/(zoom-zoomRate)); //Pan but use ratio of new zoom to old zoom for coords.  Center on mouse click.
    y = (yDist + currentPanY)*(zoom/(zoom-zoomRate));
    $j('#zoomOutBtn').attr('class', 'inactive').removeAttr('disabled');
    $j('#zoomValue').html(zoom);
  } else if (action == 'pan') {
    x = xDist + currentPanX;
    y = yDist + currentPanY;
  }
  var limitX = ((zoom*outer.width()) - outer.width())/2; //Calculate outer bounds of video
  var limitY = ((zoom*outer.height()) - outer.height())/2;
  x = Math.min(Math.max((x), -limitX), limitX); //Limit pan to outer bounds of video
  y = Math.min(Math.max((y), -limitY), limitY);
  video.css('transform', 'matrix('+zoom+', 0, 0, '+zoom+', '+x+', '+y+')');
}

function streamZoomIn(x, y) {
  if (vid) {
    vjsPanZoom('zoom', x, y);
  } else {
    streamReq({command: CMD_ZOOMIN, x: x, y: y});
  }
}

function clickZoomOut(event) {
  if (event.ctrlKey) { // allow zoom out by control click.  useful in fullscreen
    streamZoomStop();
  } else {
    streamZoomOut();
  }
}

function streamZoomStop() {
  if (vid) {
    vjsPanZoom('zoomOut');
  } else {
    streamReq({command: CMD_ZOOMSTOP});
  }
}

function streamZoomOut() {
  if (vid) {
    vjsPanZoom('zoomOut');
  } else {
    streamReq({command: CMD_ZOOMOUT});
  }
}
*/

function streamScale(scale) {
  scale += deltaScale();
  if (document.getElementById('evtStream')) {
    streamReq({command: CMD_SCALE, scale: (scale>100) ? 100 : scale});
  }
}

/*
function streamPan(x, y) {
  if (vid) {
    vjsPanZoom('pan', x, y);
  } else {
    streamReq({command: CMD_PAN, x: x, y: y});
  }
}
*/

function streamSeek(offset) {
  if (vid) {
    vid.currentTime(offset);
  } else {
    streamReq({command: CMD_SEEK, offset: offset});
  }
}

function streamQuery() {
  streamReq({command: CMD_QUERY});
}

function getEventResponse(respObj, respText) {
  if ( checkStreamForErrors('getEventResponse', respObj) ) {
    console.log('getEventResponse: errors');
    return;
  }

  eventData = respObj.event;
  getStat();
  currEventId = eventData.Id;
  $j('#eventTitle').html('Event '+currEventId); // FIXME should translate Event

  // Refresh the status of the archive buttons
  archiveBtn.prop('disabled', !(!eventData.Archived && canEdit.Events));
  unarchiveBtn.prop('disabled', !(eventData.Archived && canEdit.Events));

  history.replaceState(null, null, '?view=event&eid=' + eventData.Id + filterQuery + sortQuery); //if popup removed, check if this allows forward
  if ( vid && CurEventDefVideoPath ) {
    vid.src({type: 'video/mp4', src: CurEventDefVideoPath}); //Currently mp4 is all we use
    initialAlarmCues(eventData.Id);//ajax and render, new event
    addVideoTimingTrack(vid, LabelFormat, eventData.MonitorName, eventData.Length, eventData.StartDateTime);
    CurEventDefVideoPath = null;
    $j('#modeValue').html('Replay');
    $j('#zoomValue').html('1');
    $j('#rate').val('100');
    //vjsPanZoom('zoomOut');
  } else {
    drawProgressBar();
  }
  nearEventsQuery(eventData.Id);
} // end function getEventResponse

function eventQuery(eventId) {
  var data = {};
  data.id = eventId;
  if (auth_hash) data.auth = auth_hash;

  $j.getJSON(thisUrl + '?view=request&request=status&entity=event', data)
      .done(getEventResponse)
      .fail(logAjaxFail);
}

function getNearEventsResponse(respObj, respText) {
  if (checkStreamForErrors('getNearEventsResponse', respObj)) {
    return;
  }
  prevEventId = parseInt(respObj.nearevents.PrevEventId);
  nextEventId = parseInt(respObj.nearevents.NextEventId);
  prevEventStartTime = Date.parse(respObj.nearevents.PrevEventStartTime);
  nextEventStartTime = Date.parse(respObj.nearevents.NextEventStartTime);
  PrevEventDefVideoPath = respObj.nearevents.PrevEventDefVideoPath;
  NextEventDefVideoPath = respObj.nearevents.NextEventDefVideoPath;

  $j('#prevBtn').prop('disabled', prevEventId == 0 ? true : false).attr('class', prevEventId == 0 ? 'unavail' : 'inactive');
  $j('#nextBtn').prop('disabled', nextEventId == 0 ? true : false).attr('class', nextEventId == 0 ? 'unavail' : 'inactive');
}

function nearEventsQuery(eventId) {
  $j.getJSON(thisUrl + '?view=request&request=status&entity=nearevents&id='+eventId+filterQuery+sortQuery)
      .done(getNearEventsResponse)
      .fail(logAjaxFail);
}

function getFrameResponse(respObj, respText) {
  if (checkStreamForErrors('getFrameResponse', respObj)) {
    return;
  }

  var frame = respObj.frameimage;

  if (!eventData) {
    console.error('No event '+frame.EventId+' found');
    return;
  }

  if (!eventData['frames']) {
    eventData['frames'] = {};
  }

  eventData['frames'][frame.FrameId] = frame;
}

function frameQuery(eventId, frameId, loadImage) {
  var data = {};
  if (auth_hash) data.auth = auth_hash;
  data.loopback = loadImage;
  data.eid = eventId;
  data.fid = frameId;

  $j.getJSON(thisUrl + '?view=request&request=status&entity=frameimage', data)
      .done(getFrameResponse)
      .fail(logAjaxFail);
}

function prevEvent() {
  if (prevEventId) {
    eventQuery(prevEventId);
    streamPrev(true);
  }
}

function nextEvent() {
  if (nextEventId) {
    eventQuery(nextEventId);
    streamNext(true);
  }
}

function showEventFrames() {
  window.location.assign('?view=frames&eid='+eventData.Id);
}

function videoEvent() {
  window.location.assign('?view=video&eid='+eventData.Id);
}

function eventLive() {
  window.location.assign("?view=watch&mid="+eventData.MonitorId);
}

function eventEdit() {
  window.location.assign("?view=monitor&mid="+eventData.MonitorId);
}

function viewAllEvents() {
  window.location.assign("?view=events&page=1&filter%5BQuery%5D%5Bterms%5D%5B0%5D%5Battr%5D=Monitor&filter%5BQuery%5D%5Bterms%5D%5B0%5D%5Bop%5D=%3D&filter%5BQuery%5D%5Bterms%5D%5B0%5D%5Bval%5D="+eventData.MonitorId+"&filter%5BQuery%5D%5Bsort_asc%5D=1&filter%5BQuery%5D%5Bsort_field%5D=StartDateTime&filter%5BQuery%5D%5Bskip_locked%5D=&filter%5BQuery%5D%5Blimit%5D=0");
}

// Called on each event load because each event can be a different width
function drawProgressBar() {
  var barWidth = $j('#evtStream').width();
  if (barWidth) {
    $j('#progressBar').css('width', barWidth);
  } else {
    console.log("No bar width: " + barWidth);
  }
}

// Shows current stream progress.
function updateProgressBar() {
  if (!eventData) return;
  if (vid) {
    var currentTime = vid.currentTime();
    var progressDate = new Date(currentTime);
  } else {
    if (!streamStatus) return;
    var currentTime = streamStatus.progress;
    var progressDate = new Date(eventData.StartDateTime);
    progressDate.setTime(progressDate.getTime() + (streamStatus.progress*1000));
  }
  const progressBox = $j("#progressBox");
  let curWidth = (currentTime / parseFloat(eventData.Length)) * 100;
  if (curWidth > 100) curWidth = 100;

  progressBox.css('width', curWidth + '%');
  progressBox.attr('title', progressDate.toLocaleTimeString());
} // end function updateProgressBar()

// Handles seeking when clicking on the progress bar.
function progressBarNav() {
  console.log('progress');
  const progressBar = $j('#progressBar');
  progressBar.click(function(e) {
    let x = e.pageX - $j(this).offset().left;
    if (x<0) x=0;
    const seekTime = (x / $j('#progressBar').width()) * parseFloat(eventData.Length);

    const date = new Date(eventData.StartDateTime);
    date.setTime(date.getTime() + (seekTime*1000));
    console.log("clicked at ", x, seekTime, date.toLocaleTimeString(), "from pageX", e.pageX, "offsetleft", $j(this).offset().left );
    streamSeek(seekTime);
  });
  progressBar.mouseover(function(e) {
    let x = e.pageX - $j(this).offset().left;
    if (x<0) x=0;
    const seekTime = (x / $j('#progressBar').width()) * parseFloat(eventData.Length);

    const date = new Date(eventData.StartDateTime);
    date.setTime(date.getTime() + (seekTime*1000));
    console.log("mouseovered at ", x, seekTime, date.toLocaleTimeString(), "from pageX", e.pageX, "offsetleft", $j(this).offset().left );

    const indicator = document.getElementById('indicator');
    indicator.style.display = 'block';
    indicator.style.left = x + 'px';
    indicator.setAttribute('title', seekTime);
  });
  progressBar.mouseout(function(e) {
    const indicator = document.getElementById('indicator');
    indicator.style.display = 'none';
  });
  progressBar.mousemove(function(e) {
    const bar = $j(this);

    let x = e.pageX - bar.offset().left;
    if (x<0) x=0;
    if (x > bar.width()) x = bar.width();

    const seekTime = (x / bar.width()) * parseFloat(eventData.Length);

    const indicator = document.getElementById('indicator');

    const date = new Date(eventData.StartDateTime);
    date.setTime(date.getTime() + (seekTime*1000));

    indicator.innerHTML = date.toLocaleTimeString();
    indicator.style.left = x+'px';
    indicator.setAttribute('title', seekTime);
  });
} // end function progressBarNav

function handleClick(event) {
  if (panZoomEnabled) {
    if (!event.target.closest('#wrapperEventVideo')) {
      return;
    }

    //event.preventDefault();
    const monitorId = eventData.MonitorId; // Event page
    //We are looking for an object with an ID, because there may be another element in the button.
    const obj = event.target.id ? event.target : event.target.parentElement;

    if (obj.className.includes('btn-zoom-out') || obj.className.includes('btn-zoom-in')) return;
    if (obj.className.includes('btn-edit-monitor')) {
      const url = '?view=monitor&mid='+monitorId;
      if (event.ctrlKey) {
        window.open(url, '_blank');
      } else {
        window.location.assign(url);
      }
    }

    const obj_id = obj.getAttribute('id');
    //if (obj.getAttribute('id').indexOf("liveStream") >= 0 || obj.getAttribute('id').indexOf("button_zoom") >= 0) { //Montage & Watch page
    if (obj_id && (
      obj_id.indexOf("evtStream") >= 0 ||
      obj_id.indexOf("button_zoom") >= 0 ||
      obj.querySelector('video'))
    ) { //Event page
      //panZoom[monitorId].setOptions({disablePan: false});
      zmPanZoom.click(monitorId);
    }
  } else {
    // +++ Old ZoomPan algorithm.
    /*
    if (vid && (event.target.id != 'videoobj')) {
      return; // ignore clicks on control bar
    }
    // target should be the img tag
    if (!(event.ctrlKey && (event.shift || event.shiftKey))) {
      const target = $j(event.target);

      const width = target.width();
      const height = target.height();

      const scaleX = parseFloat(eventData.Width / width);
      const scaleY = parseFloat(eventData.Height / height);
      const pos = target.offset();
      const x = parseInt((event.pageX - pos.left) * scaleX);
      const y = parseInt((event.pageY - pos.top) * scaleY);

      if (event.shift || event.shiftKey) { // handle both jquery and mootools
        streamPan(x, y);
        updatePrevCoordinatFrame(x, y); //Fixing current coordinates after scaling or shifting
      } else if (event.ctrlKey) { // allow zoom out by control click.  useful in fullscreen
        streamZoomOut();
      } else {
        streamZoomIn(x, y);
        updatePrevCoordinatFrame(x, y); //Fixing current coordinates after scaling or shifting
      }
    }
    */// --- Old ZoomPan algorithm.
  }
}

/*
function shiftImgFrame() { //We calculate the coordinates of the image displacement and shift the image
  let newPosX = parseInt(PrevCoordinatFrame.x - coordinateMouse.shiftMouse_x);
  let newPosY = parseInt(PrevCoordinatFrame.y - coordinateMouse.shiftMouse_y);

  if (newPosX < 0) newPosX = 0;
  if (newPosX > eventData.Width) newPosX = eventData.Width;
  if (newPosY < 0) newPosY = 0;
  if (newPosY > eventData.Height) newPosY = eventData.Height;

  streamPan(newPosX, newPosY);
  updatePrevCoordinatFrame(newPosX, newPosY);
  coordinateMouse.shiftMouseForTrigger_x = coordinateMouse.shiftMouseForTrigger_y = 0;
}

function updateCoordinateMouse(x, y) { //We fix the coordinates when pressing the left mouse button
  coordinateMouse.start_x = x;
  coordinateMouse.start_y = y;
}

function updatePrevCoordinatFrame(x, y) { //Update the Frame's current coordinates
  PrevCoordinatFrame.x = x;
  PrevCoordinatFrame.y = y;
}

function getCoordinateMouse(event) { //We get the current cursor coordinates taking into account the scale relative to the frame size.
  const target = $j(event.target);

  const scaleX = parseFloat(eventData.Width / target.width());
  const scaleY = parseFloat(eventData.Height / target.height());
  const pos = target.offset();

  return {x: parseInt((event.pageX - pos.left) * scaleX), y: parseInt((event.pageY - pos.top) * scaleY)}; //The point of the mouse click relative to the dimensions of the real frame.
}
*/

function handleMove(event) {
/*
  if (panZoomEnabled) {
    return;
  }
  // +++ Old ZoomPan algorithm.
  if (event.ctrlKey && (event.shift || event.shiftKey)) {
    document.ondragstart = function() {
      return false;
    }; //Allow drag and drop
  } else {
    document.ondragstart = function() {}; //Prevent drag and drop
    return false;
  }

  if (leftBtnStatus.Down) { //The left button was previously pressed and is now being held. Processing movement with a pressed button.
    var {x, y} = getCoordinateMouse(event);
    const k = Math.log(2.72) / Math.log(parseFloat($j('#zoomValue').html())) - 0.3; //Necessary for correctly shifting the image in accordance with the scaling proportions

    coordinateMouse.shiftMouse_x = parseInt((x - coordinateMouse.start_x) * k);
    coordinateMouse.shiftMouse_y = parseInt((y - coordinateMouse.start_y) * k);

    coordinateMouse.shiftMouseForTrigger_x = Math.abs(parseInt(x - coordinateMouse.start_x));
    coordinateMouse.shiftMouseForTrigger_y = Math.abs(parseInt(y - coordinateMouse.start_y));
  }
  if (event.buttons == 1 && leftBtnStatus.Down != true) { //Start of pressing left button
    const {x, y} = getCoordinateMouse(event);

    updateCoordinateMouse(x, y);
    leftBtnStatus.Down = true;
  } else if (event.buttons == 0 && leftBtnStatus.Down == true) { //Up left button after pressed
    leftBtnStatus.Down = false;
    leftBtnStatus.UpAfterDown = true;
  }

  if ((leftBtnStatus.UpAfterDown) || //The left button was raised or the cursor was moved more than 30 pixels relative to the actual size of the image
    ((coordinateMouse.shiftMouseForTrigger_x > 30) && leftBtnStatus.Down) ||
    ((coordinateMouse.shiftMouseForTrigger_y > 30) && leftBtnStatus.Down)) {
    //We perform frame shift
    shiftImgFrame();
    updateCoordinateMouse(x, y);
    leftBtnStatus.UpAfterDown = false;
  }
  // --- Old ZoomPan algorithm.
*/
}

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById("delConfirmBtn").addEventListener("click", function onDelConfirmClick(evt) {
    if (!canEdit.Events) {
      enoperm();
      return;
    }

    // Stop playing, this is mostly for video.js
    pauseClicked();
    if (!vid) {
      // zms is supposed to get SIGPIPE but might not if running under FPM
      streamReq({command: CMD_QUIT});
    }

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=event&action=delete&id='+eventData.Id)
        .done(function(data) {
          $j('#deleteConfirm').modal('hide');
          streamNext(true);
        })
        .fail(logAjaxFail);
  });

  // Manage the CANCEL modal button
  document.getElementById("delCancelBtn").addEventListener("click", function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

function getEvtStatsCookie() {
  const cookie = 'zmEventStats';
  let stats = getCookie(cookie);

  if (!stats) {
    stats = 'on';
    setCookie(cookie, stats);
  }
  return stats;
}

function getStat() {
  eventStatsTable.empty().append('<tbody>');
  $j.each(eventDataStrings, function(key) {
    if (key == 'MonitorId') return true; // Not show ID string
    var th = $j('<th class="label">').addClass('text-right').text(eventDataStrings[key]);
    var tdString;

    //switch ( ( eventData[key] && eventData[key].length ) ? key : 'n/a') {
    switch (key) {
      case 'Name':
        tdString = eventData[key] + ' (Id:' + eventData['Id'] + ')';
        break;
      case 'Frames':
        tdString = '<a href="?view=frames&amp;eid=' + eventData.Id + '">' + eventData[key] + '</a>';
        tdString += ' Alarm:' + '<a href="?view=frames&amp;eid=' + eventData.Id + '">' + eventData['AlarmFrames'] + '</a>';
        break;
      //case 'AlarmFrames':
      //  tdString = '<a href="?view=frames&amp;eid=' + eventData.Id + '">' + eventData[key] + '</a>';
      //  break;
      case 'Location':
        tdString = eventData.Latitude + ', ' + eventData.Longitude;
        break;
      //case 'MonitorId':
      //  if (canView["Monitors"]) {
      //    tdString = '<a href="?view=monitor&amp;mid='+eventData.MonitorId+'">'+eventData.MonitorId+'</a>';
      //  } else {
      //    tdString = eventData[key];
      //  }
      //  break;
      case 'MonitorName':
        if (canView["Monitors"]) {
          tdString = '('+ eventData.MonitorId +') '+ eventData.MonitorName+ '&nbsp';
          tdString += '<div style="display:inline-block">';
          tdString += '<button id="eventLiveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="'+translate["Live"]+'" ><i class="fa fa-television"></i></button>';
          tdString += '<button id="eventEditBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="'+translate["Edit"]+'" ><i class="fa fa-edit"></i></button>';
          tdString += '<button id="eventAllEvents" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="'+translate["All Events"]+'" ><i class="fa fa-film"></i></button>';
          tdString += '</div>';
        } else {
          tdString = eventData[key];
        }
        break;
      //case 'MaxScore':
      //  tdString = '<a href="?view=frame&amp;eid=' + eventData.Id + '&amp;fid=0">' + eventData[key] + '</a>';
      //  break;
      case 'Score':
        tdString = 'Total:' + eventData['TotScore'] + ' '+ '<a href="?view=frame&amp;eid=' + eventData.Id + '&amp;fid=0">' + 'Max:' + eventData['MaxScore'] + '</a>' + ' Avg:' + eventData['AvgScore'];
        break;
      case 'n/a':
        tdString = 'n/a';
        break;
      case 'Resolution':
        tdString = eventData.Width + 'x' + eventData.Height;
        break;
      case 'DiskSpace':
        tdString = eventData[key] + ' on ' + eventData['Storage'];
        break;
      case 'Path':
        tdString = '<a href="?view=files&amp;path='+eventData.Path+'">'+eventData.Path+'</a>';
        break;
      //case 'Archived':
      //case 'Emailed':
      //  tdString = eventData[key] ? yesStr : noStr;
      //  break;
      case 'Info':
        tdString = translate["Archived"] + ':' + (eventData['Archived'] ? yesStr : noStr);
        tdString += ', ' + translate["Emailed"] + ':' + (eventData['Emailed'] ? yesStr : noStr);
        break;
      case 'Length':
        const date = new Date(0); // Have to init it fresh.  setSeconds seems to add time, not set it.
        date.setSeconds(eventData[key]);
        tdString = date.toISOString().substr(11, 8);
        break;
      default:
        tdString = eventData[key];
    }

    var td = $j('<td>').html(tdString);
    var row = $j('<tr>').append(th, td);

    $j('#eventStatsTable tbody').append(row);
  });
}


function onStatsResize(vidWidth) {
  if (!vidWidth) return;
  var minWidth = 200; // An arbitrary value in pixels used to hide the stats table
  //var scale = $j('#scale').val();

  //if (parseInt(scale)) {
  //  vidWidth = vidWidth * (scale/100);
  //}

  var width = $j(window).width() - vidWidth;
  //console.log("Width: " + width + " = window.width " + $j(window).width() + "- vidWidth" + vidWidth);

  // Hide the stats table if we have run out of room to show it properly
  if (width < minWidth) {
    statsBtn.prop('disabled', true);
    if (eventStats.is(':visible')) {
      eventStats.toggle(false);
      wasHidden = true;
      wrapperEventVideo.removeClass('col-sm-8').addClass('col-sm-12');
    }
  // Show the stats table if we hid it previously and sufficient room becomes available
  } else if (width >= minWidth) {
    statsBtn.prop('disabled', false);
    if ( !eventStats.is(':visible') && wasHidden ) {
      eventStats.toggle(true);
      wasHidden = false;
      wrapperEventVideo.removeClass('col-sm-12').addClass('col-sm-8');
    }
  }
}

function initPage() {
  getAvailableTags();
  getSelectedTags();

  // Load the event stats
  getStat();
  zmPanZoom.init();

  if (getEvtStatsCookie() != 'on') {
    eventStats.toggle(false);
    wrapperEventVideo.removeClass('col-sm-8').addClass('col-sm-12');
  } else {
    onStatsResize(eventData.Width);
    wrapperEventVideo.removeClass('col-sm-12').addClass('col-sm-8');
  }

  //FIXME prevent blocking...not sure what is happening or best way to unblock
  if (document.getElementById('videoobj')) {
    vid = videojs('videoobj');
    addVideoTimingTrack(vid, LabelFormat, eventData.MonitorName, eventData.Length, eventData.StartDateTime);
    //$j('.vjs-progress-control').append('<div id="alarmCues" class="alarmCues"></div>');//add a place for videojs only on first load
    vid.on('ended', vjsReplay);
    vid.on('play', playClicked);
    vid.on('pause', pauseClicked);
    vid.on('click', function(event) {
      handleClick(event);
    });
    vid.on('mousemove', function(event) { // It is not clear whether it is necessary...
      handleMove(event);
    });
    vid.on('volumechange', function() {
      setCookie('volume', vid.volume());
    });
    const cookie = getCookie('volume');
    if (cookie) vid.volume(cookie);

    vid.on('timeupdate', function() {
      $j('#progressValue').html(secsToTime(Math.floor(vid.currentTime())));
    });
    vid.on('ratechange', function() {
      rate = vid.playbackRate() * 100;
      $j('select[name="rate"]').val(rate);
      setCookie('zmEventRate', rate);
    });

    // rate is in % so 100 would be 1x
    if (rate > 0) {
      // rate should be 100 = 1x, etc.
      vid.playbackRate(rate/100);
    }
  } else {
    streamCmdInterval = setInterval(streamQuery, streamTimeout); //Timeout is refresh rate for progressBox and time display
    if (canStreamNative) {
      if (!$j('#videoFeed')) {
        console.log('No element with id tag videoFeed found.');
      } else {
        let streamImg = $j('#videoFeed img');
        if (!streamImg) {
          streamImg = $j('#videoFeed object');
        }
        const observedObject = panZoomEnabled ? 'body' : streamImg;
        $j(observedObject).click(function(event) {
          handleClick(event);
        });
        $j(streamImg).mousemove(function(event) {
          handleMove(event);
        });
      }
    }
  } // end if videojs or mjpeg stream
  nearEventsQuery(eventData.Id);
  initialAlarmCues(eventData.Id); //call ajax+renderAlarmCues
  document.querySelectorAll('select[name="rate"]').forEach(function(el) {
    el.onchange = window['changeRate'];
  });
  console.log('progress');
  progressBarNav();
  console.log('changescale');
  changeScale();
  console.log('changeStreamQality');
  changeStreamQuality();

  // enable or disable buttons based on current selection and user rights
  renameBtn.prop('disabled', !canEdit.Events);
  archiveBtn.prop('disabled', !(!eventData.Archived && canEdit.Events));
  unarchiveBtn.prop('disabled', !(eventData.Archived && canEdit.Events));
  editBtn.prop('disabled', !canEdit.Events);
  exportBtn.prop('disabled', !canView.Events);
  downloadBtn.prop('disabled', !canView.Events);
  deleteBtn.prop('disabled', !(!eventData.Archived && canEdit.Events));
  deleteBtn.prop('title', eventData.Archived ? "You cannot delete an archived event." : "");

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Manage the BACK button
  bindButton('#backBtn', 'click', null, function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Manage the REFRESH Button
  bindButton('#refreshBtn', 'click', null, function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Manage the ARCHIVE button
  bindButton('#archiveBtn', 'click', null, function onArchiveClick(evt) {
    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=archive&eids[]='+eventData.Id)
        .done( function(data) {
          //FIXME: update the status of the archive button reather than reload the whole page
          window.location.reload(true);
        })
        .fail(logAjaxFail);
  });

  // Manage the UNARCHIVE button
  bindButton('#unarchiveBtn', 'click', null, function onUnarchiveClick(evt) {
    if (!canEdit.Events) {
      enoperm();
      return;
    }
    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=unarchive&eids[]='+eventData.Id)
        .done( function(data) {
          //FIXME: update the status of the unarchive button rather than reload the whole page
          window.location.reload(true);
        })
        .fail(logAjaxFail);
  });

  // Manage the EDIT button
  bindButton('#editBtn', 'click', null, function onEditClick(evt) {
    if (!canEdit.Events) {
      enoperm();
      return;
    }

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=modal&modal=eventdetail&eids[]='+eventData.Id)
        .done(function(data) {
          insertModalHtml('eventDetailModal', data.html);
          $j('#eventDetailModal').modal('show');
          // Manage the Save button
          $j('#eventDetailSaveBtn').click(function(evt) {
            evt.preventDefault();
            $j('#eventDetailForm').submit();
          });
        })
        .fail(logAjaxFail);
  });

  // Manage the EXPORT button
  bindButton('#exportBtn', 'click', null, function onExportClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=export&eids[]='+eventData.Id);
  });

  // Manage the generateVideo button
  bindButton('#videoBtn', 'click', null, function onVideoClick(evt) {
    evt.preventDefault();
    videoEvent();
  });

  // Manage the generate Live button
  bindButton('#eventLiveBtn', 'click', null, function onLiveClick(evt) {
    evt.preventDefault();
    eventLive();
  });

  // Manage the generate Edit button
  bindButton('#eventEditBtn', 'click', null, function onEditClick(evt) {
    evt.preventDefault();
    eventEdit();
  });

  // Manage the generate All Events button
  bindButton('#eventAllEvents', 'click', null, function onAllEventsClick(evt) {
    evt.preventDefault();
    viewAllEvents();
  });
  // Manage the Event STATISTICS Button
  bindButton('#statsBtn', 'click', null, function onStatsClick(evt) {
    evt.preventDefault();
    var cookie = 'zmEventStats';

    // Toggle the visiblity of the stats table and write an appropriate cookie
    if (eventStats.is(':visible')) {
      setCookie(cookie, 'off');
      eventStats.toggle(false);
      wrapperEventVideo.removeClass('col-sm-8').addClass('col-sm-12');
    } else {
      setCookie(cookie, 'on');
      eventStats.toggle(true);
      wrapperEventVideo.removeClass('col-sm-12').addClass('col-sm-8');
    }
    changeScale();
  });

  // Manage the FRAMES Button
  bindButton('#framesBtn', 'click', null, function onFramesClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=frames&eid='+eventData.Id);
  });

  // Manage the DELETE button
  bindButton('#deleteBtn', 'click', null, function onDeleteClick(evt) {
    if (!canEdit.Events) {
      enoperm();
      return;
    }

    evt.preventDefault();
    if (window.event.shiftKey) {
      $j.getJSON(thisUrl + '?request=event&action=delete&id='+eventData.Id)
          .done(function(data) {
            streamNext(true);
          })
          .fail(logAjaxFail);
    } else {
      if (!$j('#deleteConfirm').length) {
        // Load the delete confirmation modal into the DOM
        $j.getJSON(thisUrl + '?request=modal&modal=delconfirm')
            .done(function(data) {
              insertModalHtml('deleteConfirm', data.html);
              manageDelConfirmModalBtns();
              $j('#deleteConfirm').modal('show');
            })
            .fail(logAjaxFail);
        return;
      }
      $j('#deleteConfirm').modal('show');
    }
  });
  document.addEventListener('fullscreenchange', fullscreenChangeEvent);

  if (isMobile()) { // Mobile
    // Event listener for adding tags when Space or Comma key is pressed on mobile devices
    // Mobile Firefox is consistent with Desktop Firefox and Desktop Chrome supporting event.key for space and comma.
    // Mobile Chrome always returns Unidentified for event.key for space and comma.
    $j('#tagInput').on('input', function(event) {
      var key = this.value.substr(-1).charCodeAt(0);
      if (key === 32 || key === 44) { // Space or Comma
        const tagInput = $j(this);
        const tagValue = tagInput.val().slice(0, -1).trim();
        addOrCreateTag(tagValue);
        event.preventDefault(); // Prevent the key from being entered in the input field
      }
    });
    // Event listener for adding tags when Enter key is pressed on mobile devices
    // All mobile and desktop browsers don't pick up on Enter as 'input'.
    // Mobile Chrome 'input' doesn't pick up "Next" button as Enter.
    $j('#tagInput').on('keydown', function(event) {
      var key = event.key;
      if (key === "Enter") { // Enter
        const tagInput = $j(this);
        const tagValue = tagInput.val().trim();
        addOrCreateTag(tagValue);
        event.preventDefault(); // Prevent the key from being entered in the input field
      }
    });
  } else { // Desktop
    // Event listener for adding tags when Enter key is pressed or highlighting available tag when up/down arrows are pressed
    $j('#tagInput').on('keydown', function(event) {
      event = event || window.event;
      var $hlight = $j('div.tag-dropdown-item.hlight');
      var $div = $j('div.tag-dropdown-item');
      if (event.key === "ArrowDown") {
        if (event.ctrlKey) {
          addTag(availableTags[0]);
        } else if ($div.is(":visible")) {
          $hlight.removeClass('hlight').next().addClass('hlight');
          if ($hlight.next().length == 0) {
            $div.eq(0).addClass('hlight');
          }
        } else {
          showDropdown();
        }
      } else if (event.key === "ArrowUp") {
        $hlight.removeClass('hlight').prev().addClass('hlight');
        if ($hlight.prev().length == 0) {
          $div.eq(-1).addClass('hlight');
        }
      } else if (event.key === "Enter") {
        var tagValue = $hlight.text();
        if (!tagValue) {
          const tagInput = $j(this);
          tagValue = tagInput.val().trim();
        }
        addOrCreateTag(tagValue);
      } else if (event.key === " " || event.key === ",") {
        const tagInput = $j(this);
        const tagValue = tagInput.val().trim();
        addOrCreateTag(tagValue);
        event.preventDefault(); // Prevent the key from being entered in the input field
      } else if (event.key === "Escape") {
        $j("#tagInput").blur();
      }
    });
  }

  // Event listener for typing in the tag input
  $j('#tagInput').on('input', showDropdown);

  // Event listener for clicking in the tag input
  $j('#tagInput').on('focus', showDropdown);

  // Event listener for removing tags
  $j('.tags-container').on('click', '.tag-remove', function() {
    const tagElement = $j(this).closest('.tag');
    const tag = tagElement.data('tag');
    removeTag(tag);
  });

  // Event listener for double click
  //var elStream = document.querySelectorAll('[id ^= "liveStream"], [id ^= "evtStream"]');
  //// When using video.js, the document will have both #videoobj and #wrapperEventVideo, but we only need #videoobj
  //const elStreamVideoJS = document.querySelectorAll("[id = 'videoobj']");
  //const elStream = (elStreamVideoJS.length > 0) ? elStreamVideoJS : document.querySelectorAll("[id = 'wrapperEventVideo']");
  const elStream = document.querySelectorAll("[id = 'wrapperEventVideo']");
  Array.prototype.forEach.call(elStream, (el) => {
    el.addEventListener('touchstart', doubleTouch);
    el.addEventListener('dblclick', doubleClickOnStream);
  });

  streamPlay();

  if ( parseInt(ZM_OPT_USE_GEOLOCATION) && parseFloat(eventData.Latitude) && parseFloat(eventData.Longitude)) {
    const mapDiv = document.getElementById('LocationMap');
    if (mapDiv) {
      mapDiv.style.width='450px';
      mapDiv.style.height='450px';
    }
    if ( window.L ) {
      const map = L.map('LocationMap', {
        center: L.latLng(eventData.Latitude, eventData.Longitude),
        zoom: 8,
        onclick: function() {
          alert('click');
        }
      });
      L.tileLayer(ZM_OPT_GEOLOCATION_TILE_PROVIDER, {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
        maxZoom: 18,
        id: 'mapbox/streets-v11',
        tileSize: 512,
        zoomOffset: -1,
        accessToken: ZM_OPT_GEOLOCATION_ACCESS_TOKEN,
      }).addTo(map);
      const marker = L.marker([eventData.Latitude, eventData.Longitude], {draggable: 'false'});
      marker.addTo(map);
      map.invalidateSize();
    } else {
      console.log('Location turned on but leaflet not installed.');
    }
  } // end if ZM_OPT_USE_GEOLOCATION

  $j("#videoFeed").hover(
      //Displaying "Scale" and other buttons at the top of the monitor image
      function() {
        //const id = stringToNumber(this.id); //Montage & Watch page
        const id = eventData.MonitorId; // Event page
        //$j('#button_zoom' + id).stop(true, true).slideDown('fast');
        $j('#button_zoom' + id).removeClass('hidden');
      },
      function() {
        //const id = stringToNumber(this.id); //Montage & Watch page
        const id = eventData.MonitorId; // Event page
        //$j('#button_zoom' + id).stop(true, true).slideUp('fast');
        $j('#button_zoom' + id).addClass('hidden');
      }
  );

  setInterval(() => {
    //Updating Scale. When quickly scrolling the mouse wheel or quickly pressing Zoom In/Out, you should not set Scale very often.
    if (updateScale) {
      const eventViewer = $j(vid ? '#videoobj' : '#evtStream');
      const panZoomScale = panZoomEnabled ? zmPanZoom.panZoom[eventData.MonitorId].getScale() : 1;
      const newSize = scaleToFit(eventData.Width, eventData.Height, eventViewer, false, $j('#videoFeed'), panZoomScale);
      scale = newSize.autoScale > 100 ? 100 : newSize.autoScale;
      currentScale = scale;
      streamScale(currentScale);
      updateScale = false;
    }
  }, 500);

  if (vid) {
    setInterval(() => {
      updateProgressBar();
    }, streamTimeout);
  }
} // end initPage

function addOrCreateTag(tagValue) {
  const tagNames = availableTags.map((t) => t.Name.toLowerCase());
  const index = tagNames.indexOf(tagValue.toLowerCase());
  if (index > -1) {
    addTag(availableTags[index]);
    $j('.tag-dropdown-content').hide();
  } else if (tagValue.trim().length > 0) {
    createTag(tagValue);
  }
}

function clickTag() {
  const tagName = $j(this).text();
  const selectedTag = availableTags.find((tag) => tag.Name === tagName);
  addTag(selectedTag);
}

function showDropdown() {
  const dropdownContent = $j('.tag-dropdown-content');
  dropdownContent.empty();
  const input = $j('#tagInput').val().trim();

  var matchingTags = [];
  if (availableTags) {
    matchingTags = availableTags.filter(function(tag) {
      var isMatch = tag.Name.toLowerCase().includes(input.toLowerCase());
      return isMatch && !isDup(tag.Name);
    });
  }

  matchingTags.forEach(function(tag) {
    const dropdownItem = $j('<div>', {class: 'tag-dropdown-item', text: tag.Name});
    dropdownItem.appendTo(dropdownContent); // Append the element to the dropdown content
  });

  if (matchingTags.length > 0) {
    $j('.tag-dropdown-content').off('click');
    $j('.tag-dropdown-content').on('click', '.tag-dropdown-item', clickTag);
    $j('.tag-dropdown-content').show();
  } else {
    $j('.tag-dropdown-content').hide();
  }
}

function isDup(tagName) {
  return $j('.tag-text').filter(function() {
    var elemText = $j(this).text();
    return elemText === tagName;
  }).length != 0;
}

function formatTag(tag) {
  const tagName = tag.Name;
  const tagElement = $j('<div>', {class: 'tag'});
  tagElement.data('tag', tag);
  tagElement.append($j('<span>', {class: 'tag-text', text: tagName}));
  tagElement.append($j('<span>', {class: 'tag-remove', text: '\u00D7'}));
  $j('.tag-dropdown').before(tagElement);
}

function addTag(tag) {
  if (tag.Name.trim() !== '' && !isDup(tag.Name)) {
    $j.getJSON(thisUrl + '?request=event&action=addtag&tid=' + tag.Id + '&id=' + eventData.Id)
        .done(function(data) {
          formatTag(tag);
          selectedTags.push(tag);

          // Move the added tag to the front(top) of the availableTags array
          const index = availableTags.map((t) => t.Id).indexOf(tag.Id);
          availableTags.splice(0, 0, availableTags.splice(index, 1)[0]);
        })
        .fail(logAjaxFail);
  } else {
    $j('.tag-dropdown-content').hide();
  }
  $j('#tagInput').val('');
  $j('#tagInput').blur();
}

function removeTag(tag) {
  $j.getJSON(thisUrl + '?request=event&action=removetag&tid=' + tag.Id + '&id=' + eventData.Id)
      .done(function(data) {
        $j('.tag-text').filter(function() {
          return $j(this).text() === tag.Name;
        }).parent().remove();
        if (data.response > 0) {
          getAvailableTags();
        }
      })
      .fail(logAjaxFail);
}

function createTag(tagName) {
  $j.getJSON(thisUrl + '?request=tags&action=createtag&tname=' + tagName)
      .done(function(data) {
        if (data.response.length > 0) {
          var tag = data.response[0];
          if (availableTags) {
            availableTags.splice(0, 0, tag);
          }
          addTag(tag);
        }
      })
      .fail(logAjaxFail);
}

function getAvailableTags() {
  $j.getJSON(thisUrl + '?request=tags&action=getavailabletags')
      .done(function(data) {
        availableTags = data.response;
      })
      .fail(logAjaxFail);
}

function getSelectedTags() {
  $j.getJSON(thisUrl + '?request=event&action=getselectedtags&id=' + eventData.Id)
      .done(function(data) {
        selectedTags = data.response;
        if (!selectedTags) {
          console.log(data);
        } else {
          selectedTags.forEach((tag) => formatTag(tag));
        }
      })
      .fail(logAjaxFail);
}

var toggleZonesButton = document.getElementById('toggleZonesButton');
if (toggleZonesButton) toggleZonesButton.addEventListener('click', toggleZones);

function toggleZones(e) {
  const zones = $j('#zones'+eventData.MonitorId);
  const button = document.getElementById('toggleZonesButton');
  if (zones.length) {
    if (zones.is(":visible")) {
      zones.hide();
      button.setAttribute('title', showZonesString);
      $j('#toggleZonesButton .material-icons').text('layers');
      setCookie('zmEventShowZones'+eventData.MonitorId, '0');
    } else {
      zones.show();
      button.setAttribute('title', hideZonesString);
      $j('#toggleZonesButton .material-icons').text('layers_clear');
      setCookie('zmEventShowZones'+eventData.MonitorId, '1');
    }
  } else {
    console.error("Zones svg not found");
  }
}

function fullscreenChangeEvent() {
  const btn = document.getElementById('fullscreenBtn');
  if (document.fullscreenElement) {
    btn.firstElementChild.innerHTML = 'fullscreen_exit';
    btn.setAttribute('title', translate["Exit Fullscreen"]);
  } else {
    btn.firstElementChild.innerHTML = 'fullscreen';
    btn.setAttribute('title', translate["Fullscreen"]);
  }
}

function fullscreenClicked() {
  if (document.fullscreenElement) {
    closeFullscreen();
  } else {
    console.log(content);
    openFullscreen(content);
  }
}

function panZoomIn(el) {
  zmPanZoom.zoomIn(el);
}

function panZoomOut(el) {
  zmPanZoom.zoomOut(el);
}

// Kick everything off
$j( window ).on("load", initPage);
