var table = $j('#eventStatsTable');
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
var streamCmdTimer = null;
var streamStatus = null;
var lastEventId = 0;
var zmsBroke = false; //Use alternate navigation if zms has crashed
var wasHidden = false;
var scaleValue = 0;

$j(document).on("keydown", "", function(e) {
  e = e || window.event;
  if ( $j(".modal").is(":visible") ) {
    if (e.key === "Enter") {
      if ( $j("#deleteConfirm").is(":visible") ) {
        $j("#delConfirmBtn").click();
      } else if ( $j("#eventDetailModal").is(":visible") ) {
        $j("#eventDetailSaveBtn").click();
      } else if ( $j("#eventRenamelModal").is(":visible") ) {
        $j("#eventRenameBtn").click();
      }
    } else if (e.key === "Escape") {
      $j(".modal").modal('hide');
    } else {
      console.log('Modal is visible: key not implemented: ', e.key, '  keyCode: ', e.keyCode);
    }
  } else {
    if (e.key === "ArrowLeft") {
      prevEvent();
    } else if (e.key === "ArrowRight") {
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
    } else {
      console.log('Modal is not visible: key not implemented: ', e.key, '  keyCode: ', e.keyCode);
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
    alarmSpans = renderAlarmCues(vid ? $j("#videoobj") : $j("#evtStream"));//use videojs width or zms width
    $j('#alarmCues').html(alarmSpans);
  }
}

function renderAlarmCues(containerEl) {
  let html = '';

  cues_div = document.getElementById('alarmCues');
  const event_length = (eventData.Length > cueFrames[cueFrames.length - 1].Delta) ? eventData.Length : cueFrames[cueFrames.length - 1].Delta;
  const span_count = 10;
  const span_seconds = parseInt(event_length / span_count);
  const span_width = parseInt(containerEl.width() / span_count);
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
  var spanTimeStart = 0;
  var spanTimeEnd = 0;
  var alarmed = 0;
  var alarmHtml = '';
  var pixSkew = 0;
  var skip = 0;
  var num_cueFrames = cueFrames.length;
  let left = 0;

  for (let i=0; i < num_cueFrames; i++) {
    skip = 0;
    frame = cueFrames[i];

    if ((frame.Type == 'Alarm') && (alarmed == 0)) { //From nothing to alarm.  End nothing and start alarm.
      alarmed = 1;
      if (frame.Delta == 0) continue; //If event starts with an alarm or too few for a nonespan
      spanTimeEnd = frame.Delta * 100;
      spanTime = spanTimeEnd - spanTimeStart;
      let pix = cueRatio * spanTime;
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
      futNone = 0;
      indexPlus = i+1;
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
      alarmHtml += '<span class="alarmCue" style="left: '+left+'px; width: ' + pix + 'px;"></span>';
      left = parseInt((frame.Delta / event_length) * containerEl.width());
      spanTimeStart = spanTimeEnd;
    } else if ( (frame.Type == 'Alarm') && (alarmed == 1) && (i + 1 >= cueFrames.length) ) { //event ends on an alarm
      spanTimeEnd = frame.Delta * 100;
      spanTime = spanTimeEnd - spanTimeStart;
      alarmed = 0;
      pix = Math.round(cueRatio * spanTime);
      if (pixSkew >= .5 || pixSkew <= -.5) pix += Math.round(pixSkew);

      alarmHtml += '<span class="alarmCue" style="left: '+left+'px; width: ' + pix + 'px;"></span>';
    }
  }
  return html + alarmHtml;
}

function changeCodec() {
  location.replace(thisUrl + '?view=event&eid=' + eventData.Id + filterQuery + sortQuery+'&codec='+$j('#codec').val());
}

function changeScale() {
  const scale = $j('#scale').val();
  let newWidth;
  let newHeight;
  let autoScale;
  const eventViewer = $j(vid ? '#videoobj' : '#evtStream');

  const alarmCue = $j('#alarmCues');
  const bottomEl = $j('#replayStatus');

  if (scale == '0') {
    const newSize = scaleToFit(eventData.Width, eventData.Height, eventViewer, bottomEl);
    newWidth = newSize.width;
    newHeight = newSize.height;
    autoScale = newSize.autoScale;
  } else {
    $j(window).off('resize', endOfResize); //remove resize handler when Scale to Fit is not active
    newWidth = eventData.Width * scale / SCALE_BASE;
    newHeight = eventData.Height * scale / SCALE_BASE;
  }
  eventViewer.width(newWidth);
  eventViewer.height(newHeight);
  console.log(scale, (scale == '0'));
  scaleValue = scale == '0' ? autoScale : scale;
  if (!vid) { // zms needs extra sizing
    streamScale(scaleValue);
    drawProgressBar();
  }
  if (cueFrames) {
    //just re-render alarmCues.  skip ajax call
    alarmCue.html(renderAlarmCues(eventViewer));
  }
  // 63072000 seconds is 2 years
  setCookie('zmEventScale'+eventData.MonitorId, scale, 63072000);

  // After a resize, check if we still have room to display the event stats table
  onStatsResize(newWidth);
} // end function changeScale

function changeReplayMode() {
  var replayMode = $j('#replayMode').val();

  setCookie('replayMode', replayMode, 3600);

  refreshWindow();
}

function changeRate() {
  var rate = parseInt($j('select[name="rate"]').val());

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
  setCookie('zmEventRate', rate, 3600);
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

  if (streamCmdTimer) streamCmdTimer = clearTimeout(streamCmdTimer);

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

  var eventId = streamStatus.event;
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
    streamPause( );
  } else {
    $j('select[name="rate"]').val(streamStatus.rate*100);
    setCookie('zmEventRate', streamStatus.rate*100, 3600);
    streamPlay( );
  }
  $j('#progressValue').html(secsToTime(parseInt(streamStatus.progress)));
  $j('#zoomValue').html(streamStatus.zoom);
  if (streamStatus.zoom == '1.0') {
    setButtonState('zoomOutBtn', 'unavail');
  } else {
    setButtonState('zoomOutBtn', 'inactive');
  }
  if ((streamStatus.scale !== undefined) && (streamStatus.scale != scaleValue)) {
    console.log("Stream not scaled, re-applying", scaleValue, streamStatus.scale);
    streamScale(scaleValue);
  }

  updateProgressBar();

  if (streamStatus.auth) {
    auth_hash = streamStatus.auth;
  } // end if haev a new auth hash

  streamCmdTimer = setTimeout(streamQuery, streamTimeout); //Timeout is refresh rate for progressBox and time display
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
  var rate_select = $j('select[name="rate"]');

  if (!rate_select.val()) {
    $j('select[name="rate"]').val(100);
  }
  if (vid) {
    if (vid.paused()) {
      vid.play();
    } else {
      vjsPlay(); //handles fast forward and rewind
    }
  } else {
    streamReq({command: CMD_PLAY});
  }
  streamPlay();
}

function vjsPlay() { //catches if we change mode programatically
  if (intervalRewind) {
    stopFastRev();
  }
  $j('select[name="rate"]').val(vid.playbackRate()*100);
  setCookie('zmEventRate', vid.playbackRate()*100, 3600);
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
    setCookie('zmEventRate', vid.playbackRate()*100, 3600);
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
  setCookie('zmEventRate', vid.playbackRate()*100, 3600);
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
    setCookie('zmEventRate', vid.playbackRate()*100, 3600);
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
    var hideContainer = $j('#eventVideo');
    var hideStream = $j(vid ? "#videoobj" : "#evtStream").height() + (vid ? 0 :$j("#progressBar").height());
    hideContainer.prepend('<p class="vjsMessage" style="height: ' + hideStream + 'px; line-height: ' + hideStream + 'px;">No more events</p>');
    if (vid == null) zmsBroke = true;
    return;
  }
  // We used to try to dynamically update all the bits in the page, which is really complex
  // How about we just reload the page?
  //
  if (vid==null) streamReq({command: CMD_QUIT});
  location.replace(thisUrl + '?view=event&eid=' + nextEventId + filterQuery + sortQuery);
  return;
  if (vid && ( NextEventDefVideoPath.indexOf('view_video') > 0 )) {
    // on and staying with videojs
    CurEventDefVideoPath = NextEventDefVideoPath;
    eventQuery(nextEventId);
  } else if (
    zmsBroke ||
    (vid && NextEventDefVideoPath.indexOf("view_video") < 0) ||
    NextEventDefVideoPath.indexOf("view_video") > 0
  ) {//reload zms, leaving vjs, moving to vjs
    location.replace(thisUrl + '?view=event&eid=' + nextEventId + filterQuery + sortQuery);
  } else {
    streamReq({command: CMD_NEXT});
    streamPlay();
  }
} // end function streamNext(action)

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

function streamScale(scale) {
  streamReq({command: CMD_SCALE, scale: scale});
}

function streamPan(x, y) {
  if (vid) {
    vjsPanZoom('pan', x, y);
  } else {
    streamReq({command: CMD_PAN, x: x, y: y});
  }
}

function streamSeek(offset) {
  streamReq({command: CMD_SEEK, offset: offset});
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
    vjsPanZoom('zoomOut');
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
  data.id = {eventId, frameId};

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

function getActResponse(respObj, respText) {
  if (checkStreamForErrors('getActResponse', respObj)) {
    return;
  }

  if (respObj.refreshEvent) {
    eventQuery(eventData.Id);
  }
  $j('#eventRenameModal').modal('hide');
}

function actQuery(action, parms) {
  var data = {};
  if (parms) data = parms;
  if (auth_hash) data.auth = auth_hash;
  data.id = eventData.Id;
  data.action = action;

  $j.getJSON(thisUrl + '?view=request&request=event', data)
      .done(getActResponse)
      .fail(logAjaxFail);
}

function renameEvent() {
  var newName = $j('input').val();
  actQuery('rename', {eventName: newName});
}

function showEventFrames() {
  window.location.assign('?view=frames&eid='+eventData.Id);
}

function videoEvent() {
  window.location.assign('?view=video&eid='+eventData.Id);
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
  if (!(eventData && streamStatus)) {
    return;
  } // end if ! eventData && streamStatus
  let curWidth = (streamStatus.progress / parseFloat(eventData.Length)) * 100;
  if (curWidth > 100) curWidth = 100;

  const progressDate = new Date(eventData.StartDateTime);
  progressDate.setTime(progressDate.getTime() + (streamStatus.progress*1000));

  const progressBox = $j("#progressBox");
  progressBox.css('width', curWidth + '%');
  progressBox.attr('title', progressDate.toLocaleTimeString());
} // end function updateProgressBar()

// Handles seeking when clicking on the progress bar.
function progressBarNav() {
  $j('#progressBar').click(function(e) {
    let x = e.pageX - $j(this).offset().left;
    if (x<0) x=0;
    const seekTime = (x / $j('#progressBar').width()) * parseFloat(eventData.Length);
    console.log("clicked at ", x, seekTime);
    streamSeek(seekTime);
  });
  $j('#progressBar').mouseover(function(e) {
    let x = e.pageX - $j(this).offset().left;
    if (x<0) x=0;
    console.log(x);
    const seekTime = (x / $j('#progressBar').width()) * parseFloat(eventData.Length);
    const indicator = document.getElementById('indicator');
    indicator.style.display = 'block';
    indicator.style.left = x + 'px';
    indicator.setAttribute('title', seekTime);
  });
  $j('#progressBar').mouseout(function(e) {
    const indicator = document.getElementById('indicator');
    indicator.style.display = 'none';
  });
  $j('#progressBar').mousemove(function(e) {
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
}

function handleClick(event) {
  if (vid && (event.target.id != 'videoobj')) {
    return; // ignore clicks on control bar
  }
  // target should be the img tag
  const target = $j(event.target);

  const width = target.width();
  const height = target.height();

  const scaleX = parseInt(eventData.Width / width);
  const scaleY = parseInt(eventData.Height / height);
  const pos = target.offset();
  const x = parseInt((event.pageX - pos.left) * scaleX);
  const y = parseInt((event.pageY - pos.top) * scaleY);

  if (event.shift || event.shiftKey) { // handle both jquery and mootools
    streamPan(x, y);
  } else if (event.ctrlKey) { // allow zoom out by control click.  useful in fullscreen
    streamZoomOut();
  } else {
    streamZoomIn(x, y);
  }
}

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById("delConfirmBtn").addEventListener("click", function onDelConfirmClick(evt) {
    if (!canEdit.Events) {
      enoperm();
      return;
    }

    pauseClicked();
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
    setCookie(cookie, stats, 10*365);
  }
  return stats;
}

function getStat() {
  table.empty().append('<tbody>');
  $j.each(eventDataStrings, function(key) {
    var th = $j('<th>').addClass('text-right').text(eventDataStrings[key]);
    var tdString;

    //switch ( ( eventData[key] && eventData[key].length ) ? key : 'n/a') {
    switch (key) {
      case 'Frames':
        tdString = '<a href="?view=frames&amp;eid=' + eventData.Id + '">' + eventData[key] + '</a>';
        break;
      case 'AlarmFrames':
        tdString = '<a href="?view=frames&amp;eid=' + eventData.Id + '">' + eventData[key] + '</a>';
        break;
      case 'Location':
        tdString = eventData.Latitude + ', ' + eventData.Longitude;
        break;
      case 'MonitorId':
        if (canView["Monitors"]) {
          tdString = '<a href="?view=monitor&amp;mid='+eventData.MonitorId+'">'+eventData.MonitorId+'</a>';
        } else {
          tdString = eventData[key];
        }
        break;
      case 'MonitorName':
        if (canView["Monitors"]) {
          tdString = '<a href="?view=monitor&amp;mid='+eventData.MonitorId+'">'+eventData.MonitorName+'</a>';
        } else {
          tdString = eventData[key];
        }
        break;
      case 'MaxScore':
        tdString = '<a href="?view=frame&amp;eid=' + eventData.Id + '&amp;fid=0">' + eventData[key] + '</a>';
        break;
      case 'n/a':
        tdString = 'n/a';
        break;
      case 'Resolution':
        tdString = eventData.Width + 'x' + eventData.Height;
        break;
      case 'Path':
        tdString = '<a href="?view=files&amp;path='+eventData.Path+'">'+eventData.Path+'</a>';
        break;
      case 'Archived':
      case 'Emailed':
        tdString = eventData[key] ? yesStr : noStr;
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
  var minWidth = 300; // An arbitrary value in pixels used to hide the stats table
  var scale = $j('#scale').val();

  if (parseInt(scale)) {
    vidWidth = vidWidth * (scale/100);
  }

  var width = $j(window).width() - vidWidth;
  //console.log("Width: " + width + " = window.width " + $j(window).width() + "- vidWidth" + vidWidth);

  // Hide the stats table if we have run out of room to show it properly
  if (width < minWidth) {
    statsBtn.prop('disabled', true);
    if (table.is(':visible')) {
      table.toggle(false);
      wasHidden = true;
    }
  // Show the stats table if we hid it previously and sufficient room becomes available
  } else if (width >= minWidth) {
    statsBtn.prop('disabled', false);
    if ( !table.is(':visible') && wasHidden ) {
      table.toggle(true);
      wasHidden = false;
    }
  }
}

function initPage() {
  // Load the event stats
  getStat();

  if (getEvtStatsCookie() != 'on') {
    table.toggle(false);
  } else {
    onStatsResize(eventData.Width);
  }

  //FIXME prevent blocking...not sure what is happening or best way to unblock
  if (document.getElementById('videoobj')) {
    vid = videojs('videoobj');
    addVideoTimingTrack(vid, LabelFormat, eventData.MonitorName, eventData.Length, eventData.StartDateTime);
    //$j('.vjs-progress-control').append('<div id="alarmCues" class="alarmCues"></div>');//add a place for videojs only on first load
    vid.on('ended', vjsReplay);
    vid.on('play', vjsPlay);
    vid.on('pause', pauseClicked);
    vid.on('click', function(event) {
      handleClick(event);
    });
    vid.on('volumechange', function() {
      setCookie('volume', vid.volume(), 3600);
    });
    const cookie = getCookie('volume');
    if (cookie) vid.volume(cookie);

    vid.on('timeupdate', function() {
      $j('#progressValue').html(secsToTime(Math.floor(vid.currentTime())));
    });
    vid.on('ratechange', function() {
      rate = vid.playbackRate() * 100;
      $j('select[name="rate"]').val(rate);
      setCookie('zmEventRate', rate, 3600);
    });

    // rate is in % so 100 would be 1x
    if (rate > 0) {
      // rate should be 100 = 1x, etc.
      vid.playbackRate(rate/100);
    }
  } else {
    progressBarNav();
    streamCmdTimer = setTimeout(streamQuery, 500);
    if (canStreamNative) {
      if (!$j('#videoFeed')) {
        console.log('No element with id tag videoFeed found.');
      } else {
        let streamImg = $j('#videoFeed img');
        if (!streamImg) {
          streamImg = $j('#videoFeed object');
        }
        $j(streamImg).click(function(event) {
          handleClick(event);
        });
      }
    }
  } // end if videojs or mjpeg stream
  if (scale == '0') changeScale();
  nearEventsQuery(eventData.Id);
  initialAlarmCues(eventData.Id); //call ajax+renderAlarmCues
  document.querySelectorAll('select[name="rate"]').forEach(function(el) {
    el.onchange = window['changeRate'];
  });


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

  // Manage the Event RENAME button
  bindButton('#renameBtn', 'click', null, function onRenameClick(evt) {
    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=modal&modal=eventrename&eid='+eventData.Id)
        .done(function(data) {
          insertModalHtml('eventRenameModal', data.html);
          $j('#eventRenameModal').modal('show');
          // Manage the SAVE button
          $j('#eventRenameBtn').click(renameEvent);
        })
        .fail(logAjaxFail);
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

  // Manage the Event STATISTICS Button
  bindButton('#statsBtn', 'click', null, function onStatsClick(evt) {
    evt.preventDefault();
    var cookie = 'zmEventStats';

    // Toggle the visiblity of the stats table and write an appropriate cookie
    if (table.is(':visible')) {
      setCookie(cookie, 'off', 10*365);
      table.toggle(false);
    } else {
      setCookie(cookie, 'on', 10*365);
      table.toggle(true);
    }
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
  streamPlay();

  if ( parseInt(ZM_OPT_USE_GEOLOCATION) ) {
    if ( window.L ) {
      map = L.map('LocationMap', {
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
      marker = L.marker([eventData.Latitude, eventData.Longitude], {draggable: 'false'});
      marker.addTo(map);
      map.invalidateSize();
    } else {
      console.log('Location turned on but leaflet not installed.');
    }
  } // end if ZM_OPT_USE_GEOLOCATION
} // end initPage

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
      setCookie('zmEventShowZones'+eventData.MonitorId, '0', 3600);
    } else {
      zones.show();
      button.setAttribute('title', hideZonesString);
      $j('#toggleZonesButton .material-icons').text('layers_clear');
      setCookie('zmEventShowZones'+eventData.MonitorId, '1', 3600);
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
    openFullscreen(content);
  }
}

// Kick everything off
$j(document).ready(initPage);
