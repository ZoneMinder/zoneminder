var table = $j('#eventStatsTable');
var backBtn = $j('#backBtn');
var renameBtn = $j('#renameBtn');
var archiveBtn = $j('#archiveBtn');
var unarchiveBtn = $j('#unarchiveBtn');
var editBtn = $j('#editBtn');
var exportBtn = $j('#exportBtn');
var downloadBtn = $j('#downloadBtn');
var deleteBtn = $j('#deleteBtn');
var prevEventId = 0;
var nextEventId = 0;
var prevEventStartTime = 0;
var nextEventStartTime = 0;
var PrevEventDefVideoPath = "";
var NextEventDefVideoPath = "";
var slider = null;
var scroll = null;
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
var frameBatch = 40;
var currFrameId = null;
var auth_hash;

function streamReq(data) {
  if ( auth_hash ) data.auth = auth_hash;
  data.connkey = connKey;
  data.view = 'request';
  data.request = 'stream';

  $j.getJSON(thisUrl, data)
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
        var overLaid = $j("#videoobj");
        overLaid.append('<p class="vjsMessage" style="height: '+overLaid.height()+'px; line-height: '+overLaid.height()+'px;">No more events</p>');
      } else {
        var endTime = (Date.parse(eventData.EndDateTime)).getTime();
        var nextStartTime = nextEventStartTime.getTime(); //nextEventStartTime.getTime() is a mootools workaround, highjacks Date.parse
        if ( nextStartTime <= endTime ) {
          streamNext(true);
          return;
        }
        var overLaid = $j("#videoobj");
        vid.pause();
        overLaid.append('<p class="vjsMessage" style="height: '+overLaid.height()+'px; line-height: '+overLaid.height()+'px;"></p>');
        var gapDuration = (new Date().getTime()) + (nextStartTime - endTime);
        var messageP = $j('.vjsMessage');
        var x = setInterval(function() {
          var now = new Date().getTime();
          var remainder = new Date(Math.round(gapDuration - now)).toISOString().substr(11, 8);
          messageP.html(remainder + ' to next event.');
          if ( remainder < 0 ) {
            clearInterval(x);
            streamNext(true);
          }
        }, 1000);
      }
      break;
    case 'gapless':
      streamNext(true);
      break;
  }
} // end function vjsReplay

function initialAlarmCues(eventId) {
  $j.getJSON(thisUrl + '?view=request&request=status&entity=frames&id=' + eventId, setAlarmCues) //get frames data for alarmCues and inserts into html
      .fail(logAjaxFail);
}

function setAlarmCues(data) {
  cueFrames = data.frames;
  alarmSpans = renderAlarmCues(vid ? $j("#videoobj") : $j("#evtStream"));//use videojs width or zms width
  $j(".alarmCue").html(alarmSpans);
}

function renderAlarmCues(containerEl) {
  if ( !( cueFrames && cueFrames.length ) ) {
    console.log('No cue frames for event');
    return;
  }
  // This uses the Delta of the last frame to get the length of the event.  I can't help but wonder though
  // if we shouldn't just use the event length endtime-starttime
  var cueRatio = containerEl.width() / (cueFrames[cueFrames.length - 1].Delta * 100);
  var minAlarm = Math.ceil(1/cueRatio);
  var spanTimeStart = 0;
  var spanTimeEnd = 0;
  var alarmed = 0;
  var alarmHtml = '';
  var pixSkew = 0;
  var skip = 0;
  var num_cueFrames = cueFrames.length;
  for ( var i = 0; i < num_cueFrames; i++ ) {
    skip = 0;
    frame = cueFrames[i];
    if ( (frame.Type == 'Alarm') && (alarmed == 0) ) { //From nothing to alarm.  End nothing and start alarm.
      alarmed = 1;
      if (frame.Delta == 0) continue; //If event starts with an alarm or too few for a nonespan
      spanTimeEnd = frame.Delta * 100;
      spanTime = spanTimeEnd - spanTimeStart;
      var pix = cueRatio * spanTime;
      pixSkew += pix - Math.round(pix);//average out the rounding errors.
      pix = Math.round(pix);
      if ((pixSkew > 1 || pixSkew < -1) && pix + Math.round(pixSkew) > 0) { //add skew if it's a pixel and won't zero out span.
        pix += Math.round(pixSkew);
        pixSkew = pixSkew - Math.round(pixSkew);
      }
      alarmHtml += '<span class="alarmCue noneCue" style="width: ' + pix + 'px;"></span>';
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
      alarmHtml += '<span class="alarmCue" style="width: ' + pix + 'px;"></span>';
      spanTimeStart = spanTimeEnd;
    } else if ( (frame.Type == 'Alarm') && (alarmed == 1) && (i + 1 >= cueFrames.length) ) { //event ends on an alarm
      spanTimeEnd = frame.Delta * 100;
      spanTime = spanTimeEnd - spanTimeStart;
      alarmed = 0;
      pix = Math.round(cueRatio * spanTime);
      if (pixSkew >= .5 || pixSkew <= -.5) pix += Math.round(pixSkew);
      alarmHtml += '<span class="alarmCue" style="width: ' + pix + 'px;"></span>';
    }
  }
  return alarmHtml;
}

function changeCodec() {
  location.replace(thisUrl + '?view=event&eid=' + eventData.Id + filterQuery + sortQuery+'&codec='+$j('#codec').val());
}

function changeScale() {
  var scale = $j('#scale').val();
  var newWidth;
  var newHeight;
  var autoScale;
  var eventViewer;
  var alarmCue = $j('div.alarmCue');
  var bottomEl = streamMode == 'stills' ? $j('#eventImageNav') : $j('#replayStatus');
  if ( streamMode == 'stills' ) {
    eventViewer = $j('#eventThumbs');
  } else {
    eventViewer = $j(vid ? '#videoobj' : '#evtStream');
  }
  if ( scale == '0' || scale == 'auto' ) {
    var newSize = scaleToFit(eventData.Width, eventData.Height, eventViewer, bottomEl);
    newWidth = newSize.width;
    newHeight = newSize.height;
    autoScale = newSize.autoScale;
  } else {
    $j(window).off('resize', endOfResize); //remove resize handler when Scale to Fit is not active
    newWidth = eventData.Width * scale / SCALE_BASE;
    newHeight = eventData.Height * scale / SCALE_BASE;
  }
  if ( streamMode != 'stills' ) {
    eventViewer.width(newWidth);
  } // stills handles its own width
  eventViewer.height(newHeight);
  if ( !vid ) { // zms needs extra sizing
    streamScale(scale == '0' ? autoScale : scale);
    drawProgressBar();
  }
  if ( streamMode == 'stills' ) {
    slider.autosize();
    alarmCue.html(renderAlarmCues($j('#thumbsSliderPanel')));
  } else {
    alarmCue.html(renderAlarmCues(eventViewer));//just re-render alarmCues.  skip ajax call
  }
  Cookie.write('zmEventScale'+eventData.MonitorId, scale, {duration: 10*365, samesite: 'strict'});
} // end function changeScale

function changeReplayMode() {
  var replayMode = $j('#replayMode').val();

  Cookie.write('replayMode', replayMode, {duration: 10*365, samesite: 'strict'});

  refreshWindow();
}

function changeRate() {
  var rate = parseInt($j('select[name="rate"]').val());

  if ( ! rate ) {
    pauseClicked();
  } else if ( rate < 0 ) {
    if ( vid ) { //There is no reverse play with mp4.  Set the speed to 0 and manually set the time back.
      revSpeed = rates[rates.indexOf(-1*rate)-1]/100;
      clearInterval(intervalRewind);
      intervalRewind = setInterval(function() {
        if ( vid.currentTime() <= 0 ) {
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
  Cookie.write('zmEventRate', rate, {duration: 10*365, samesite: 'strict'});
} // end function changeRate

function getCmdResponse( respObj, respText ) {
  if ( checkStreamForErrors('getCmdResponse', respObj) ) {
    console.log('Got an error from getCmdResponse');
    console.log(respObj);
    console.log(respText);
    zmsBroke = true;
    return;
  }

  zmsBroke = false;

  if ( streamCmdTimer ) {
    streamCmdTimer = clearTimeout(streamCmdTimer);
  }

  streamStatus = respObj.status;
  if ( streamStatus.duration && ( streamStatus.duration != parseFloat(eventData.Length) ) ) {
    eventData.Length = streamStatus.duration;
  }
  if ( streamStatus.progress > parseFloat(eventData.Length) ) {
    console.log("Limiting progress to " + streamStatus.progress + ' >= ' + parseFloat(eventData.Length) );
    streamStatus.progress = parseFloat(eventData.Length);
  } //Limit progress to reality

  var eventId = streamStatus.event;
  if ( lastEventId ) {
    if ( eventId != lastEventId ) {
      //Doesn't run on first load, prevents a double hit on event and nearEvents ajax
      eventQuery(eventId);
      initialAlarmCues(eventId); //zms uses this instead of a page reload, must call ajax+render
      lastEventId = eventId;
    }
  } else {
    lastEventId = eventId; //Only fires on first load.
  }

  if ( streamStatus.paused == true ) {
    streamPause( );
  } else {
    $j('select[name="rate"]').val(streamStatus.rate*100);
    Cookie.write('zmEventRate', streamStatus.rate*100, {duration: 10*365, samesite: 'strict'});
    streamPlay( );
  }
  $j('#progressValue').html(secsToTime(parseInt(streamStatus.progress)));
  $j('#zoomValue').html(streamStatus.zoom);
  if ( streamStatus.zoom == "1.0" ) {
    setButtonState( 'zoomOutBtn', 'unavail' );
  } else {
    setButtonState( 'zoomOutBtn', 'inactive' );
  }

  updateProgressBar();

  if ( streamStatus.auth ) {
    // Try to reload the image stream.
    var streamImg = $j('#evtStream');
    if ( streamImg ) {
      streamImg.src = streamImg.src.replace( /auth=\w+/i, 'auth='+streamStatus.auth );
    }
  } // end if haev a new auth hash

  streamCmdTimer = streamQuery.delay(streamTimeout); //Timeout is refresh rate for progressBox and time display
} // end function getCmdResponse( respObj, respText )

function pauseClicked() {
  if ( vid ) {
    if ( intervalRewind ) {
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
  setButtonState( 'pauseBtn', 'active' );
  setButtonState( 'playBtn', 'inactive' );
  setButtonState( 'fastFwdBtn', 'unavail' );
  setButtonState( 'slowFwdBtn', 'inactive' );
  setButtonState( 'slowRevBtn', 'inactive' );
  setButtonState( 'fastRevBtn', 'unavail' );
}

function playClicked( ) {
  var rate_select = $j('select[name="rate"]');

  if ( ! rate_select.val() ) {
    $j('select[name="rate"]').val(100);
  }
  if ( vid ) {
    if ( vid.paused() ) {
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
  if ( intervalRewind ) {
    stopFastRev();
  }
  $j('select[name="rate"]').val(vid.playbackRate()*100);
  Cookie.write('zmEventRate', vid.playbackRate()*100, {duration: 10*365, samesite: 'strict'});
  streamPlay();
}

function streamPlay( ) {
  setButtonState( 'pauseBtn', 'inactive' );
  setButtonState( 'playBtn', 'active' );
  setButtonState( 'fastFwdBtn', 'inactive' );
  setButtonState( 'slowFwdBtn', 'unavail' );
  setButtonState( 'slowRevBtn', 'unavail' );
  setButtonState( 'fastRevBtn', 'inactive' );
}

function streamFastFwd( action ) {
  setButtonState( 'pauseBtn', 'inactive' );
  setButtonState( 'playBtn', 'inactive' );
  setButtonState( 'fastFwdBtn', 'active' );
  setButtonState( 'slowFwdBtn', 'unavail' );
  setButtonState( 'slowRevBtn', 'unavail' );
  setButtonState( 'fastRevBtn', 'inactive' );
  if ( vid ) {
    if ( revSpeed != .5 ) stopFastRev();
    vid.playbackRate(rates[rates.indexOf(vid.playbackRate()*100)-1]/100);
    if ( rates.indexOf(vid.playbackRate()*100)-1 == -1 ) {
      setButtonState('fastFwdBtn', 'unavail');
    }
    $j('select[name="rate"]').val(vid.playbackRate()*100);
    Cookie.write('zmEventRate', vid.playbackRate()*100, {duration: 10*365, samesite: 'strict'});
  } else {
    streamReq({command: CMD_FASTFWD});
  }
}


function streamSlowFwd( action ) {
  if ( vid ) {
    vid.currentTime(vid.currentTime() + spf);
  } else {
    streamReq({command: CMD_SLOWFWD});
  }
}

function streamSlowRev( action ) {
  if ( vid ) {
    vid.currentTime(vid.currentTime() - spf);
  } else {
    streamReq({command: CMD_SLOWREV});
  }
}

function stopFastRev() {
  clearInterval(intervalRewind);
  vid.playbackRate(1);
  $j('select[name="rate"]').val(vid.playbackRate()*100);
  Cookie.write('zmEventRate', vid.playbackRate()*100, {duration: 10*365, samesite: 'strict'});
  revSpeed = .5;
}

function streamFastRev( action ) {
  setButtonState( 'pauseBtn', 'inactive' );
  setButtonState( 'playBtn', 'inactive' );
  setButtonState( 'fastFwdBtn', 'inactive' );
  setButtonState( 'slowFwdBtn', 'unavail' );
  setButtonState( 'slowRevBtn', 'unavail' );
  setButtonState( 'fastRevBtn', 'active' );
  if ( vid ) { //There is no reverse play with mp4.  Set the speed to 0 and manually set the time back.
    revSpeed = rates[rates.indexOf(revSpeed*100)-1]/100;
    if ( rates.indexOf(revSpeed*100) == 0 ) {
      setButtonState( 'fastRevBtn', 'unavail' );
    }
    clearInterval(intervalRewind);
    $j('select[name="rate"]').val(-revSpeed*100);
    Cookie.write('zmEventRate', vid.playbackRate()*100, {duration: 10*365, samesite: 'strict'});
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
  if ( action ) {
    $j(".vjsMessage").remove();
    location.replace(thisUrl + '?view=event&eid=' + prevEventId + filterQuery + sortQuery);
    return;

    if ( vid && PrevEventDefVideoPath.indexOf("view_video") > 0 ) {
      CurEventDefVideoPath = PrevEventDefVideoPath;
      eventQuery(prevEventId);
    } else if (zmsBroke || (vid && PrevEventDefVideoPath.indexOf("view_video") < 0) || $j("#vjsMessage").length || PrevEventDefVideoPath.indexOf("view_video") > 0) {//zms broke, leaving videojs, last event, moving to videojs
      location.replace(thisUrl + '?view=event&eid=' + prevEventId + filterQuery + sortQuery);
    } else {
      streamReq({command: CMD_PREV});
      streamPlay();
    }
  }
}

function streamNext(action) {
  if ( action ) {
    $j(".vjsMessage").remove();//This shouldn't happen
    if ( nextEventId == 0 ) { //handles deleting last event.
      pauseClicked();
      var hideContainer = $j('#eventVideo');
      var hideStream = $j(vid ? "#videoobj" : "#evtStream").height() + (vid ? 0 :$j("#progressBar").height());
      hideContainer.prepend('<p class="vjsMessage" style="height: ' + hideStream + 'px; line-height: ' + hideStream + 'px;">No more events</p>');
      if ( vid == null ) zmsBroke = true;
      return;
    }
    // We used to try to dynamically update all the bits in the page, which is really complex
    // How about we just reload the page?
    //
    location.replace(thisUrl + '?view=event&eid=' + nextEventId + filterQuery + sortQuery);
    return;
    if ( vid && ( NextEventDefVideoPath.indexOf("view_video") > 0 ) ) { //on and staying with videojs
      CurEventDefVideoPath = NextEventDefVideoPath;
      eventQuery(nextEventId);
    } else if ( zmsBroke || (vid && NextEventDefVideoPath.indexOf("view_video") < 0) || NextEventDefVideoPath.indexOf("view_video") > 0) {//reload zms, leaving vjs, moving to vjs
      location.replace(thisUrl + '?view=event&eid=' + nextEventId + filterQuery + sortQuery);
    } else {
      streamReq({command: CMD_NEXT});
      streamPlay();
    }
  }
}

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

function streamZoomIn( x, y ) {
  if (vid) {
    vjsPanZoom('zoom', x, y);
  } else {
    streamReq({command: CMD_ZOOMIN, x: x, y: y});
  }
}

function streamZoomOut() {
  if (vid) {
    vjsPanZoom('zoomOut');
  } else {
    streamReq({command: CMD_ZOOMOUT});
  }
}

function streamScale( scale ) {
  streamReq({command: CMD_SCALE, scale: scale});
}

function streamPan( x, y ) {
  if (vid) {
    vjsPanZoom('pan', x, y);
  } else {
    streamReq({command: CMD_PAN, x: x, y: y});
  }
}

function streamSeek( offset ) {
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
  var eventStills = $j('#eventStills');

  if ( eventStills && !eventStills.hasClass('hidden') && currEventId != eventData.Id ) {
    resetEventStills();
  }
  currEventId = eventData.Id;

  $j('#dataEventId').text( eventData.Id );
  $j('#dataEventName').text( eventData.Name );
  $j('#dataMonitorId').text( eventData.MonitorId );
  $j('#dataMonitorName').text( eventData.MonitorName );
  $j('#dataCause').text( eventData.Cause );
  if ( eventData.Notes ) {
    $j('#dataCause').prop( 'title', eventData.Notes );
  } else {
    $j('#dataCause').prop( 'title', causeString );
  }
  $j('#dataStartTime').text( eventData.StartDateTime );
  $j('#dataDuration').text( eventData.Length );
  $j('#dataFrames').text( eventData.Frames );
  $j('#dataAlarmFrames').text( eventData.AlarmFrames );
  $j('dataTotalScore').text( eventData.TotScore );
  $j('dataAvgScore').text( eventData.AvgScore );
  $j('dataMaxScore').text( eventData.MaxScore );
  $j('dataDiskSpace').text( eventData.DiskSpace );
  $j('dataStorage').text( eventData.Storage );

  // Refresh the status of the archive buttons
  archiveBtn.prop('disabled', !(!eventData.Archived && canEdit.Events));
  unarchiveBtn.prop('disabled', !(eventData.Archived && canEdit.Events));

  history.replaceState(null, null, '?view=event&eid=' + eventData.Id + filterQuery + sortQuery); //if popup removed, check if this allows forward
  if ( vid && CurEventDefVideoPath ) {
    vid.src({type: 'video/mp4', src: CurEventDefVideoPath}); //Currently mp4 is all we use
    console.log('getEventResponse');
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
  nearEventsQuery( eventData.Id );
} // end function getEventResponse

function eventQuery(eventId) {
  var data = {};
  data.id = eventId;
  if ( auth_hash ) data.auth = auth_hash;

  $j.getJSON(thisUrl + '?view=request&request=status&entity=event', data)
      .done(getEventResponse)
      .fail(logAjaxFail);
}

function getNearEventsResponse( respObj, respText ) {
  if ( checkStreamForErrors('getNearEventsResponse', respObj) ) {
    return;
  }
  prevEventId = respObj.nearevents.PrevEventId;
  nextEventId = respObj.nearevents.NextEventId;
  prevEventStartTime = Date.parse(respObj.nearevents.PrevEventStartTime);
  nextEventStartTime = Date.parse(respObj.nearevents.NextEventStartTime);
  PrevEventDefVideoPath = respObj.nearevents.PrevEventDefVideoPath;
  NextEventDefVideoPath = respObj.nearevents.NextEventDefVideoPath;

  $j('#prevEventBtn').prop('disabled', !prevEventId);
  $j('#nextEventBtn').prop('disabled', !nextEventId);
  $j('#prevBtn').prop('disabled', prevEventId == 0 ? true : false).attr('class', prevEventId == 0 ? 'unavail' : 'inactive');
  $j('#nextBtn').prop('disabled', nextEventId == 0 ? true : false).attr('class', nextEventId == 0 ? 'unavail' : 'inactive');
}

var nearEventsReq = new Request.JSON( {url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: getNearEventsResponse} );

function nearEventsQuery( eventId ) {
  var parms = "view=request&request=status&entity=nearevents&id="+eventId+filterQuery+sortQuery;
  nearEventsReq.send( parms );
}

function loadEventThumb( event, frame, loadImage ) {
  var thumbImg = $j('#eventThumb'+frame.FrameId);
  if ( !thumbImg ) {
    console.error('No holder found for frame '+frame.FrameId);
    return;
  }
  var img = new Asset.image( imagePrefix+frame.EventId+"&fid="+frame.FrameId,
      {
        'onload': ( function( loadImage ) {
          thumbImg.prop('src', img.prop('src'));
          thumbImg.prop('class', frame.Type=='Alarm'?'alarm':'normal');
          thumbImg.prop('title', frame.FrameId+' / '+((frame.Type=='Alarm')?frame.Score:0));
          thumbImg.removeClass('placeholder');
          thumbImg.off('click');
          thumbImg.click(function() {
            locateImage( frame.FrameId, true );
          } );
          if ( loadImage ) {
            loadEventImage( event, frame );
          }
        } ).pass( loadImage )
      }
  );
}

function loadEventImage(event, frame) {
  console.debug('Loading '+event.Id+'/'+frame.FrameId);
  var eventImg = $j('#eventImage');
  var thumbImg = $j('#eventThumb'+frame.FrameId);
  if ( eventImg.prop('src') != thumbImg.prop('src') ) {
    var eventImagePanel = $j('#eventImagePanel');

    if ( eventImagePanel.css('display') != 'none' ) {
      var lastThumbImg = $j('#eventThumb' + eventImg.prop('alt'));
      lastThumbImg.removeClass('selected');
      lastThumbImg.css('opacity', '1.0');
    }

    $j('#eventImageBar').css('width', event.Width);
    if ( frame.Type == 'Alarm' ) {
      $j('#eventImageStats').removeClass('hidden');
    } else {
      $j('#eventImageStats').addClass('hidden');
    }
    thumbImg.addClass('selected');
    thumbImg.css('opacity', '0.5');

    if ( eventImagePanel.css('display') == 'none' ) {
      eventImagePanel.css('opacity', '0');
      eventImagePanel.css('display', 'inline-block');
      new Fx.Tween( eventImagePanel, {duration: 500, transition: Fx.Transitions.Sine} ).start( 'opacity', 0, 1 );
    }

    eventImg.prop( {
      'class': frame.Type=='Alarm'?'alarm':'normal',
      'src': thumbImg.prop('src'),
      'title': thumbImg.prop('title'),
      'alt': thumbImg.prop('alt'),
      'height': $j('#eventThumbs').height() - $j('#eventImageBar').outerHeight(true)-10
    } );

    $j('#eventImageNo').text(frame.FrameId);
    $j('#prevImageBtn').prop('disabled', !frame.FrameId == 1);
    $j('#nextImageBtn').prop('disabled', !frame.FrameId == event.Frames);
  }
}

function hideEventImageComplete() {
  var thumbImg = $j('#eventThumb'+$j('#eventImage').prop('alt'));
  if ( thumbImg ) {
    thumbImg.removeClass('selected');
    thumbImg.css('opacity', '1.0');
  } else {
    console.log('Unable to find eventThumb at eventThumb'+$j('#eventImage').prop('alt'));
  }
  $j('#prevImageBtn').prop('disabled', true);
  $j('#nextImageBtn').prop('disabled', true);
  $j('#eventImagePanel').css('display', 'none');
  $j('#eventImageStats').addClass('hidden');
}

function hideEventImage() {
  if ( $j('#eventImagePanel').css('display') != 'none' ) {
    new Fx.Tween( $j('#eventImagePanel'), {duration: 500, transition: Fx.Transitions.Sine, onComplete: hideEventImageComplete} ).start('opacity', 1, 0);
  }
}

function resetEventStills() {
  hideEventImage();
  $j('#eventThumbs').empty();
  if ( true || !slider ) {
    slider = new Slider( '#thumbsSlider', '#thumbsKnob', {
      /*steps: eventData.Frames,*/
      value: 0,
      onChange: function( step ) {
        if ( !step ) {
          step = 0;
        }
        var fid = parseInt((step * eventData.Frames)/this.options.steps);
        if ( fid < 1 ) {
          fid = 1;
        } else if ( fid > eventData.Frames ) {
          fid = eventData.Frames;
        }
        checkFrames( eventData.Id, fid, ($j('#eventImagePanel').css('display')=='none'?'':'true'));
        scroll.toElement( 'eventThumb'+fid );
      }
    } );
  }
}

function getFrameResponse(respObj, respText) {
  if ( checkStreamForErrors('getFrameResponse', respObj) ) {
    return;
  }

  var frame = respObj.frameimage;

  if ( !eventData ) {
    console.error('No event '+frame.EventId+' found');
    return;
  }

  if ( !eventData['frames'] ) {
    eventData['frames'] = {};
  }

  eventData['frames'][frame.FrameId] = frame;

  loadEventThumb(eventData, frame, respObj.loopback=="true");
}

function frameQuery( eventId, frameId, loadImage ) {
  var data = {};
  data.loopback = loadImage;
  data.id = {eventId, frameId};

  $j.getJSON(thisUrl + '?view=request&request=status&entity=frameimage', data)
      .done(getFrameResponse)
      .fail(logAjaxFail);
}

function checkFrames( eventId, frameId, loadImage ) {
  if ( !eventData ) {
    console.error("No event "+eventId+" found");
    return;
  }

  if ( !eventData['frames'] ) {
    eventData['frames'] = {};
  }

  currFrameId = frameId;

  var loFid = frameId - frameBatch/2;
  if ( loFid < 1 ) {
    loFid = 1;
  }
  var hiFid = loFid + (frameBatch-1);
  if ( hiFid > eventData.Frames ) {
    hiFid = eventData.Frames;
  }

  for ( var fid = loFid; fid <= hiFid; fid++ ) {
    if ( !$j('#eventThumb'+fid) ) {
      var img = $j('<img>');
      img.attr({
        'id': 'eventThumb'+fid,
        'src': 'graphics/transparent.png',
        'alt': fid,
        'class': 'placeholder'
      });

      img.click(function() {
        eventData['frames'][fid] = null;
        checkFrames(eventId, fid);
      });
      frameQuery(eventId, fid, loadImage && (fid == frameId));
      var imgs = $j('#eventThumbs img');
      var injected = false;
      if ( fid < imgs.length ) {
        imgs.before(img);
        injected = true;
      } else {
        injected = imgs.toArray().some(
            function( thumbImg, index ) {
              if ( parseInt(img.prop('alt')) < parseInt(thumbImg.prop('alt')) ) {
                thumbImg.before(img);
                return true;
              }
              return false;
            }
        );
      }
      if ( !injected ) {
        $j('#eventThumbs').append(img);
      }
      var scale = parseInt(img.css('height'));
      img.css( {
        'width': parseInt((eventData.Width*scale)/100),
        'height': parseInt((eventData.Height*scale)/100)
      } );
    } else if ( eventData['frames'][fid] ) {
      if ( loadImage && (fid == frameId) ) {
        loadEventImage( eventData, eventData['frames'][fid], loadImage );
      }
    }
  }
  $j('#prevThumbsBtn').prop('disabled', frameId == 1);
  $j('#nextThumbsBtn').prop('disabled', frameId == eventData.Frames);
}

function locateImage( frameId, loadImage ) {
  if ( slider ) {
    slider.fireEvent( 'tick', slider.toPosition( parseInt((frameId-1)*slider.options.steps/eventData.Frames) ));
  }
  checkFrames( eventData.Id, frameId, loadImage );
  scroll.toElement( 'eventThumb'+frameId );
}

function prevImage() {
  if ( currFrameId > 1 ) {
    locateImage( parseInt(currFrameId)-1, true );
  }
}

function nextImage() {
  if ( currFrameId < eventData.Frames ) {
    locateImage( parseInt(currFrameId)+1, true );
  }
}

function prevThumbs() {
  if ( currFrameId > 1 ) {
    locateImage( parseInt(currFrameId)>10?(parseInt(currFrameId)-10):1, $j('#eventImagePanel').css('display')!="none" );
  }
}

function nextThumbs() {
  if ( currFrameId < eventData.Frames ) {
    locateImage( parseInt(currFrameId)<(eventData.Frames-10)?(parseInt(currFrameId)+10):eventData.Frames, $j('#eventImagePanel').css('display')!="none" );
  }
}

function prevEvent() {
  if ( prevEventId ) {
    eventQuery( prevEventId );
    streamPrev( true );
  }
}

function nextEvent() {
  if ( nextEventId ) {
    eventQuery( nextEventId );
    streamNext( true );
  }
}

function getActResponse( respObj, respText ) {
  if ( checkStreamForErrors( "getActResponse", respObj ) ) {
    return;
  }

  if ( respObj.refreshEvent ) {
    eventQuery( eventData.Id );
  }
}

function actQuery(action, parms) {
  var data = {};
  if ( parms ) data = parms;
  if ( auth_hash ) data.auth = auth_hash;
  data.id = eventData.Id;
  data.action = action;

  $j.getJSON(thisUrl + '?view=request&request=event', data)
      .done(getActResponse)
      .fail(logAjaxFail);
}

function renameEvent() {
  var newName = $j('input').val();
  actQuery('rename', {eventName: newName});
  //FIXME: update the value of the event name rather than reload the whole page
  window.location.reload(true);
}

function exportEvent() {
  window.location.assign('?view=export&eid='+eventData.Id);
}

function showEventFrames() {
  window.location.assign('?view=frames&eid='+eventData.Id);
}

function showStream() {
  $j('#eventStills').addClass('hidden');
  $j('#eventVideo').removeClass('hidden');

  $j('#stillsEvent').removeClass('hidden');
  $j('#streamEvent').addClass('hidden');

  streamMode = 'video';
  if (scale == 'auto') changeScale();
}

function showStills() {
  $j('#eventStills').removeClass('hidden');
  $j('#eventVideo').addClass('hidden');

  if (vid && ( vid.paused != true ) ) {
    // Pause the video
    vid.pause();

    // Update the button text to 'Play'
    //if ( playButton )
    //playButton.innerHTML = "Play";
  }

  $j('#stillsEvent').addClass('hidden');
  $j('#streamEvent').removeClass('hidden');

  streamMode = 'stills';

  pauseClicked();
  if ( !scroll ) {
    scroll = new Fx.Scroll('eventThumbs', {
      wait: false,
      duration: 500,
      offset: {'x': 0, 'y': 0},
      transition: Fx.Transitions.Quad.easeInOut
    }
    );
  }
  resetEventStills();
  if (scale == 'auto') changeScale();
}

function showFrameStats() {
  var fid = $j('#eventImageNo').text();
  window.location.assign('?view=stats&eid='+eventData.Id+'&fid='+fid);
}

function videoEvent() {
  window.location.assign('?view=video&eid='+eventData.Id);
}

// Called on each event load because each event can be a different width
function drawProgressBar() {
  var barWidth = $j('#evtStream').width();
  $j('#progressBar').css('width', barWidth);
}

// Shows current stream progress.
function updateProgressBar() {
  if ( ! ( eventData && streamStatus ) ) {
    return;
  } // end if ! eventData && streamStatus
  var curWidth = (streamStatus.progress / parseFloat(eventData.Length)) * 100;
  $j("#progressBox").css('width', curWidth + '%');
} // end function updateProgressBar()

// Handles seeking when clicking on the progress bar.
function progressBarNav() {
  $j('#progressBar').click(function(e) {
    var x = e.pageX - $j(this).offset().left;
    var seekTime = (x / $j('#progressBar').width()) * parseFloat(eventData.Length);
    streamSeek(seekTime);
  });
}

function handleClick( event ) {
  var target = event.target;
  var rect = target.getBoundingClientRect();
  if ( vid ) {
    if (target.id != 'videoobj') return; // ignore clicks on control bar
    var x = event.offsetX;
    var y = event.offsetY;
  } else {
    var x = event.page.x - rect.left;
    var y = event.page.y - rect.top;
  }

  if ( event.shift || event.shiftKey ) { // handle both jquery and mootools
    streamPan(x, y);
  } else if ( vid && event.ctrlKey ) { // allow zoom out by control click.  useful in fullscreen
    vjsPanZoom('zoomOut', x, y);
  } else {
    streamZoomIn(x, y);
  }
}

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById("delConfirmBtn").addEventListener("click", function onDelConfirmClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=delete&eids[]='+eventData.Id)
        .done( function(data) {
          streamNext( true );
        })
        .fail(logAjaxFail);
  });

  // Manage the CANCEL modal button
  document.getElementById("delCancelBtn").addEventListener("click", function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

function getEvtStatsCookie() {
  var cookie = 'zmEventStats';
  var stats = getCookie(cookie);

  if ( !stats ) {
    stats = 'on';
    setCookie(cookie, stats, 10*365);
  }
  return stats;
}

function getStat() {
  table.empty().append('<tbody>');
  $j.each( eventDataStrings, function( key ) {
    var th = $j('<th>').addClass('text-right').text(eventDataStrings[key]);
    var tdString;

    switch (eventData[key].length ? key : 'n/a') {
      case 'Frames':
        tdString = '<a href="?view=frames&amp;eid=' + eventData.Id + '">' + eventData[key] + '</a>';
        break;
      case 'AlarmFrames':
        tdString = '<a href="?view=frames&amp;eid=' + eventData.Id + '">' + eventData[key] + '</a>';
        break;
      case 'MaxScore':
        tdString = '<a href="?view=frame&amp;eid=' + eventData.Id + '&amp;fid=0">' + eventData[key] + '</a>';
        break;
      case 'n/a':
        tdString = 'n/a';
        break;
      default:
        tdString = eventData[key];
    }

    var td = $j('<td>').html(tdString);
    var row = $j('<tr>').append(th, td);

    $j('#eventStatsTable tbody').append(row);
  });
}

function initPage() {
  // Load the event stats
  getStat();

  var stats = getEvtStatsCookie();
  if ( stats != 'on' ) table.toggle(false);

  //FIXME prevent blocking...not sure what is happening or best way to unblock
  if ( $j('#videoobj').length ) {
    vid = videojs('videoobj');
    addVideoTimingTrack(vid, LabelFormat, eventData.MonitorName, eventData.Length, eventData.StartDateTime);
    $j('.vjs-progress-control').append('<div class="alarmCue"></div>');//add a place for videojs only on first load
    vid.on('ended', vjsReplay);
    vid.on('play', vjsPlay);
    vid.on('pause', pauseClicked);
    vid.on('click', function(event) {
      handleClick(event);
    });
    vid.on('volumechange', function() {
      Cookie.write('volume', vid.volume(), {duration: 10*365, samesite: 'strict'});
    });
    if ( Cookie.read('volume') != null ) {
      vid.volume(Cookie.read('volume'));
    }
    vid.on('timeupdate', function() {
      $j('#progressValue').html(secsToTime(Math.floor(vid.currentTime())));
    });

    // rate is in % so 100 would be 1x
    if ( rate > 0 ) {
      // rate should be 100 = 1x, etc.
      vid.playbackRate(rate/100);
    }
  } else {
    progressBarNav();
    streamCmdTimer = streamQuery.delay(250);
    if ( canStreamNative ) {
      if ( !$j('#imageFeed') ) {
        console.log('No element with id tag imageFeed found.');
      } else {
        var streamImg = $j('#imageFeed img');
        if ( !streamImg ) {
          streamImg = $j('#imageFeed object');
        }
        $j(streamImg).click(function(event) {
          handleClick(event);
        });
      }
    }
  }
  nearEventsQuery(eventData.Id);
  initialAlarmCues(eventData.Id); //call ajax+renderAlarmCues
  if ( scale == '0' || scale == 'auto' ) changeScale();
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
  deleteBtn.prop('disabled', !canEdit.Events);

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

  // Manage the Event RENAME button
  document.getElementById("renameBtn").addEventListener("click", function onRenameClick(evt) {
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
  document.getElementById("archiveBtn").addEventListener("click", function onArchiveClick(evt) {
    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=archive&eids[]='+eventData.Id)
        .done( function(data) {
          //FIXME: update the status of the archive button reather than reload the whole page
          window.location.reload(true);
        })
        .fail(logAjaxFail);
  });

  // Manage the UNARCHIVE button
  document.getElementById("unarchiveBtn").addEventListener("click", function onUnarchiveClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }
    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=unarchive&eids[]='+eventData.Id)
        .done( function(data) {
          //FIXME: update the status of the unarchive button reather than reload the whole page
          window.location.reload(true);
        })
        .fail(logAjaxFail);
  });

  // Manage the EDIT button
  document.getElementById("editBtn").addEventListener("click", function onEditClick(evt) {
    if ( ! canEdit.Events ) {
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
  document.getElementById("exportBtn").addEventListener("click", function onExportClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=export&eids[]='+eventData.Id);
  });

  // Manage the DOWNLOAD VIDEO button
  document.getElementById("downloadBtn").addEventListener("click", function onDownloadClick(evt) {
    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=modal&modal=download&eids[]='+eventData.Id)
        .done(function(data) {
          insertModalHtml('downloadModal', data.html);
          $j('#downloadModal').modal('show');
          // Manage the GENERATE DOWNLOAD button
          $j('#exportButton').click(exportEvent);
        })
        .fail(logAjaxFail);
  });

  // Manage the Event STATISTICS Button
  document.getElementById("statsBtn").addEventListener("click", function onStatsClick(evt) {
    evt.preventDefault();
    var cookie = 'zmEventStats';

    // Toggle the visiblity of the stats table and write an appropriate cookie
    if ( table.is(':visible') ) {
      setCookie(cookie, 'off', 10*365);
      table.toggle(false);
    } else {
      setCookie(cookie, 'on', 10*365);
      table.toggle(true);
    }
  });

  // Manage the FRAMES Button
  document.getElementById("framesBtn").addEventListener("click", function onFramesClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=frames&eid='+eventData.Id);
  });

  // Manage the DELETE button
  document.getElementById("deleteBtn").addEventListener("click", function onDeleteClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }

    evt.preventDefault();
    if ( ! $j('#deleteConfirm').length ) {
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
  });
}

// Kick everything off
$j(document).ready(initPage);
