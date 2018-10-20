var vid = null;

function vjsReplay() {
  let endTime = (Date.parse(eventData.EndTime)).getTime();
  switch(replayMode.value) {
    case 'none':
      break;
    case 'single':
      vid.play();
      break;
    case 'all':
      if (nextEventId == 0) {
        let overLaid = $j("#videoobj");
        overLaid.append('<p class="vjsMessage" style="height: '+overLaid.height()+'px; line-height: '+overLaid.height()+'px;">No more events</p>');
      } else {
        let nextStartTime = nextEventStartTime.getTime(); //nextEventStartTime.getTime() is a mootools workaround, highjacks Date.parse
        if (nextStartTime <= endTime) {
         streamNext( true );
         return;
        }
        let overLaid = $j("#videoobj");
        vid.pause();
        overLaid.append('<p class="vjsMessage" style="height: '+overLaid.height()+'px; line-height: '+overLaid.height()+'px;"></p>');
        let gapDuration = (new Date().getTime()) + (nextStartTime - endTime);
        let messageP = $j(".vjsMessage");
        let x = setInterval(function() {
          let now = new Date().getTime();
          let remainder = new Date(Math.round(gapDuration - now)).toISOString().substr(11,8);
          messageP.html(remainder + ' to next event.');
          if (remainder < 0) {
            clearInterval(x);
            streamNext( true );
          }
        }, 1000);
      }
        break;
    case 'gapless':
      streamNext( true );
      break;
  }
}

$j.ajaxSetup ({timeout: AJAX_TIMEOUT }); //sets timeout for all getJSON.

var cueFrames = null; //make cueFrames available even if we don't send another ajax query

function initialAlarmCues (eventId) {
  $j.getJSON(thisUrl + '?view=request&request=status&entity=frames&id=' + eventId, setAlarmCues); //get frames data for alarmCues and inserts into html
}

function setAlarmCues (data) {
  cueFrames = data.frames;
  alarmSpans = renderAlarmCues(vid ? $j("#videoobj") : $j("#evtStream"));//use videojs width or zms width
  $j(".alarmCue").html(alarmSpans);
}

function renderAlarmCues (containerEl) {
  if ( !( cueFrames && cueFrames.length ) ) {
    console.log("No cue frames for event");
    return;
  }
  // This uses the Delta of the last frame to get the length of the event.  I can't help but wonder though
  // if we shouldn't just use the event length endtime-starttime
  var cueRatio = containerEl.width() / (cueFrames[cueFrames.length - 1].Delta * 100);
  var minAlarm = Math.ceil(1/cueRatio);
  var spanTimeStart = 0;
  var spanTimeEnd = 0;
  var alarmed = 0;
  var alarmHtml = "";
  var pixSkew = 0;
  var skip = 0;
var num_cueFrames = cueFrames.length;
  for ( let i = 0; i < num_cueFrames; i++ ) {
    skip = 0;
    frame = cueFrames[i];
    if (frame.Type == "Alarm" && alarmed == 0) { //From nothing to alarm.  End nothing and start alarm.
      alarmed = 1;
      if (frame.Delta == 0) continue;  //If event starts with an alarm or too few for a nonespan
      spanTimeEnd = frame.Delta * 100;
      spanTime = spanTimeEnd - spanTimeStart;
      let pix = cueRatio * spanTime;
      pixSkew += pix - Math.round(pix);//average out the rounding errors.
      pix = Math.round(pix);
      if ((pixSkew > 1 || pixSkew < -1) && pix + Math.round(pixSkew) > 0) { //add skew if it's a pixel and won't zero out span. 
        pix += Math.round(pixSkew);
        pixSkew = pixSkew - Math.round(pixSkew);
      }
      alarmHtml += '<span class="alarmCue noneCue" style="width: ' + pix + 'px;"></span>';
      spanTimeStart = spanTimeEnd;
    } else if (frame.Type !== "Alarm" && alarmed == 1) { //from alarm to nothing.  End alarm and start nothing.
      futNone = 0;
      indexPlus = i+1;
      if (((frame.Delta * 100) - spanTimeStart) < minAlarm && indexPlus < num_cueFrames) {
        //alarm is too short and there is more event
        continue;
      }
      while (futNone < minAlarm) { //check ahead to see if there's enough for a nonespan
        if (indexPlus >= cueFrames.length) break; //check if end of event.
        futNone = (cueFrames[indexPlus].Delta *100) - (frame.Delta *100);
        if (cueFrames[indexPlus].Type == "Alarm") {
          i = --indexPlus;
          skip = 1;
          break;
        }
        indexPlus++;
      }
      if (skip == 1) continue;  //javascript doesn't support continue 2;
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
    } else if (frame.Type == "Alarm" && alarmed == 1 && i + 1 >= cueFrames.length) { //event ends on an alarm
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

function setButtonState( element, butClass ) {
  if ( element ) {
    element.className = butClass;
    if (butClass == 'unavail' || (butClass == 'active' && (element.id == 'pauseBtn' || element.id == 'playBtn'))) {
      element.disabled = true;
    } else {
      element.disabled = false;
    }
  } else {
    console.log("Element was null in setButtonState");
  }
}

function changeScale() {
  let scale = $j('#scale').val();
  let newWidth;
  let newHeight;
  let autoScale;
  let eventViewer;
  let alarmCue = $j('div.alarmCue');
  let bottomEl = streamMode == 'stills' ? $j('#eventImageNav') : $j('#replayStatus');
  if (streamMode == 'stills') {
    eventViewer = $j('#eventThumbs');
  } else {
    eventViewer = $j(vid ? '#videoobj' : '#evtStream');
  }
  if ( scale == "auto" ) {
    let newSize = scaleToFit(eventData.Width, eventData.Height, eventViewer, bottomEl);
    newWidth = newSize.width;
    newHeight = newSize.height;
    autoScale = newSize.autoScale;
  } else {
    $j(window).off('resize', endOfResize); //remove resize handler when Scale to Fit is not active
    newWidth = eventData.Width * scale / SCALE_BASE;
    newHeight = eventData.Height * scale / SCALE_BASE;
  }
  if ( !(streamMode == 'stills') )
    eventViewer.width(newWidth);  //stills handles its own width
  eventViewer.height(newHeight);
  if ( !vid ) { // zms needs extra sizing
    streamScale(scale == "auto" ? autoScale : scale);
    drawProgressBar();
  }
  if ( streamMode == 'stills' ) {
    slider.autosize();
    alarmCue.html(renderAlarmCues($j('#thumbsSliderPanel')));
  } else {
    alarmCue.html(renderAlarmCues(eventViewer));//just re-render alarmCues.  skip ajax call
  }
  if ( scale == "auto" ) {
    Cookie.write('zmEventScaleAuto', 'auto', {duration: 10*365});
  } else {
    Cookie.write('zmEventScale'+eventData.MonitorId, scale, {duration: 10*365});
    Cookie.dispose('zmEventScaleAuto');
  }
}

function changeReplayMode() {
  var replayMode = $('replayMode').get('value');

  Cookie.write('replayMode', replayMode, { duration: 10*365 });

  refreshWindow();
}

var streamParms = "view=request&request=stream&connkey="+connKey;
var streamCmdTimer = null;

var streamStatus = null;
var lastEventId = 0;
var zmsBroke = false; //Use alternate navigation if zms has crashed

function getCmdResponse( respObj, respText ) {
  if ( checkStreamForErrors("getCmdResponse", respObj) ) {
    console.log('Got an error from getCmdResponse');
    console.log(respObj);
    console.log(respText);
    zmsBroke = true;
    return;
  }

  zmsBroke = false;

  if ( streamCmdTimer )
    streamCmdTimer = clearTimeout(streamCmdTimer);

  streamStatus = respObj.status;
  if ( streamStatus.progress >= Math.round(parseFloat(eventData.Length)) )
    streamStatus.progress = parseFloat(eventData.Length); //Limit progress to reality

  var eventId = streamStatus.event;
  if ( lastEventId ) {
    if ( eventId != lastEventId ) {
      //Doesn't run on first load, prevents a double hit on event and nearEvents ajax
      eventQuery(eventId);
      initialAlarmCues(eventId);  //zms uses this instead of a page reload, must call ajax+render
      lastEventId = eventId;
    }
  } else {
    lastEventId = eventId; //Only fires on first load.
  }

  if ( streamStatus.paused == true ) {
    streamPause( );
  } else {
    $j('#rateValue').html(streamStatus.rate);
    streamPlay( );
  }
  $j('#progressValue').html(secsToTime(parseInt(streamStatus.progress)));
  $j('#zoomValue').html(streamStatus.zoom);
  if ( streamStatus.zoom == "1.0" )
    setButtonState( $('zoomOutBtn'), 'unavail' );
  else
    setButtonState( $('zoomOutBtn'), 'inactive' );

  updateProgressBar();

  if ( streamStatus.auth ) {
    // Try to reload the image stream.
    var streamImg = $j('#evtStream');
    if ( streamImg )
      streamImg.src = streamImg.src.replace( /auth=\w+/i, 'auth='+streamStatus.auth );
  } // end if haev a new auth hash

  streamCmdTimer = streamQuery.delay( streamTimeout ); //Timeout is refresh rate for progressBox and time display
}

var streamReq = new Request.JSON( {
  url: thisUrl,
  method: 'get',
  timeout: AJAX_TIMEOUT,
  link: 'chain',
  onSuccess: getCmdResponse
} );

function pauseClicked() {
  if ( vid ) {
    vid.pause();
  } else {
    streamReq.send(streamParms+"&command="+CMD_PAUSE);
    streamPause();
  }
}

function vjsPause() {
  stopFastRev();
  streamPause();
}

function streamPause( ) {
  $j('#modeValue').html('Paused');
  $j('#rateValue').html('0');
  setButtonState( $('pauseBtn'), 'active' );
  setButtonState( $('playBtn'), 'inactive' );
  setButtonState( $('fastFwdBtn'), 'unavail' );
  setButtonState( $('slowFwdBtn'), 'inactive' );
  setButtonState( $('slowRevBtn'), 'inactive' );
  setButtonState( $('fastRevBtn'), 'unavail' );
}

function playClicked( ) {
  if ( vid ) {
    if ( vid.paused() ) {
      vid.play();
    } else {
      vjsPlay(); //handles fast forward and rewind
    }
  } else {
    streamReq.send( streamParms+"&command="+CMD_PLAY );
    streamPlay();
  }
}

function vjsPlay() { //catches if we change mode programatically
  stopFastRev();
  $j('#rateValue').html(vid.playbackRate());
  streamPlay();
}

function streamPlay( ) {
  $j('#modeValue').html('Replay');
  setButtonState( $('pauseBtn'), 'inactive' );
  setButtonState( $('playBtn'), 'active' );
  setButtonState( $('fastFwdBtn'), 'inactive' );
  setButtonState( $('slowFwdBtn'), 'unavail' );
  setButtonState( $('slowRevBtn'), 'unavail' );
  setButtonState( $('fastRevBtn'), 'inactive' );
}

function streamFastFwd( action ) {
  setButtonState( $('pauseBtn'), 'inactive' );
  setButtonState( $('playBtn'), 'inactive' );
  setButtonState( $('fastFwdBtn'), 'active' );
  setButtonState( $('slowFwdBtn'), 'unavail' );
  setButtonState( $('slowRevBtn'), 'unavail' );
  setButtonState( $('fastRevBtn'), 'inactive' );
  if ( vid ) {
    if ( revSpeed != .5 ) stopFastRev();
    vid.playbackRate(rates[rates.indexOf(vid.playbackRate()*100)-1]/100);
    if ( rates.indexOf(vid.playbackRate()*100)-1 == -1 )
      setButtonState($('fastFwdBtn'), 'unavail');
    $j('#rateValue').html(vid.playbackRate());
  } else {
    streamReq.send(streamParms+"&command="+CMD_FASTFWD);
  }
}

var spf = Math.round((eventData.Length / eventData.Frames)*1000000 )/1000000;//Seconds per frame for videojs frame by frame.
var intervalRewind;
var revSpeed = .5;

function streamSlowFwd( action ) {
  if ( vid ) {
   vid.currentTime(vid.currentTime() + spf);
  } else {
    streamReq.send(streamParms+"&command="+CMD_SLOWFWD);
  }
}

function streamSlowRev( action ) {
  if ( vid ) {
    vid.currentTime(vid.currentTime() - spf);
  } else {
    streamReq.send(streamParms+"&command="+CMD_SLOWREV);
  }
}

function stopFastRev() {
  clearInterval(intervalRewind);
  vid.playbackRate(1);
  revSpeed = .5;
}

function streamFastRev( action ) {
  setButtonState( $('pauseBtn'), 'inactive' );
  setButtonState( $('playBtn'), 'inactive' );
  setButtonState( $('fastFwdBtn'), 'inactive' );
  setButtonState( $('slowFwdBtn'), 'unavail' );
  setButtonState( $('slowRevBtn'), 'unavail' );
  setButtonState( $('fastRevBtn'), 'active' );
  if ( vid ) { //There is no reverse play with mp4.  Set the speed to 0 and manualy set the time back.
    revSpeed = rates[rates.indexOf(revSpeed*100)-1]/100;
    if ( rates.indexOf(revSpeed*100) == 0 ) {
      setButtonState( $('fastRevBtn'), 'unavail' );
    }
    clearInterval(intervalRewind);
    $j('#rateValue').html(-revSpeed);
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
    streamReq.send( streamParms+"&command="+CMD_FASTREV );
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
      streamReq.send(streamParms+"&command="+CMD_PREV);
      streamPlay();
    }
  }
}

function streamNext(action) {
  if ( action ) {
    $j(".vjsMessage").remove();//This shouldn't happen
    if ( nextEventId == 0 ) { //handles deleting last event.
      pauseClicked();
      let hideContainer = $j('#eventVideo');
      let hideStream = $j(vid ? "#videoobj" : "#evtStream").height() + (vid ? 0 :$j("#progressBar").height());
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
      streamReq.send(streamParms+"&command="+CMD_NEXT);
      streamPlay();
    }
  }
}

function vjsPanZoom (action, x, y) { //Pan and zoom with centering where the click occurs
  let outer = $j('#videoobj');
  let video = outer.children().first();
  let zoom =  parseFloat($j('#zoomValue').html());
  let zoomRate = .5;
  let matrix = video.css('transform').split(',');
  let currentPanX = parseFloat(matrix[4]);
  let currentPanY = parseFloat(matrix[5]);
  let xDist = outer.width()/2 - x //Click distance from center of view
  let yDist = outer.height()/2 - y
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
  let limitX = ((zoom*outer.width()) - outer.width())/2; //Calculate outer bounds of video
  let limitY = ((zoom*outer.height()) - outer.height())/2;
  x = Math.min(Math.max((x),-limitX),limitX); //Limit pan to outer bounds of video
  y = Math.min(Math.max((y),-limitY),limitY);
  video.css('transform', 'matrix('+zoom+', 0, 0, '+zoom+', '+x+', '+y+')');
}

function streamZoomIn( x, y ) {
  if (vid) {
    vjsPanZoom('zoom', x, y);
  } else {
    streamReq.send( streamParms+"&command="+CMD_ZOOMIN+"&x="+x+"&y="+y );
  }
}

function streamZoomOut() {
  if (vid) {
    vjsPanZoom('zoomOut');
  } else {
    streamReq.send( streamParms+"&command="+CMD_ZOOMOUT );
  }
}

function streamScale( scale ) {
  streamReq.send( streamParms+"&command="+CMD_SCALE+"&scale="+scale );
}

function streamPan( x, y ) {
  if (vid) {
    vjsPanZoom('pan', x, y);
  } else {
    streamReq.send( streamParms+"&command="+CMD_PAN+"&x="+x+"&y="+y );
  }
}

function streamSeek( offset ) {
  streamReq.send( streamParms+"&command="+CMD_SEEK+"&offset="+offset );
}

function streamQuery() {
  streamReq.send( streamParms+"&command="+CMD_QUERY );
}

var slider = null;
var scroll = null;
var currEventId = null;
var CurEventDefVideoPath = null;

function getEventResponse( respObj, respText ) {
  if ( checkStreamForErrors( "getEventResponse", respObj ) ) {
    console.log("getEventResponse: errors" );
    return;
  }

  eventData = respObj.event;
  var eventStills = $('eventStills');

  if ( eventStills && !$('eventStills').hasClass( 'hidden' ) && currEventId != eventData.Id )
    resetEventStills();
  currEventId = eventData.Id;

  $('dataId').set( 'text', eventData.Id );
  if ( eventData.Notes ) {
    $('dataCause').setProperty( 'title', eventData.Notes );
  } else {
    $('dataCause').setProperty( 'title', causeString );
  }
  $('dataCause').set( 'text', eventData.Cause );
  $('dataTime').set( 'text', eventData.StartTime );
  $('dataDuration').set( 'text', eventData.Length );
  $('dataFrames').set( 'text', eventData.Frames+"/"+eventData.AlarmFrames );
  $('dataScore').set( 'text', eventData.TotScore+"/"+eventData.AvgScore+"/"+eventData.MaxScore );
  $('eventName').setProperty( 'value', eventData.Name );
  history.replaceState(null, null, '?view=event&eid=' + eventData.Id + filterQuery + sortQuery);//if popup removed, check if this allows forward
  if ( canEditEvents ) {
    if ( parseInt(eventData.Archived) ) {
      $('archiveEvent').addClass( 'hidden' );
      $('unarchiveEvent').removeClass( 'hidden' );
    } else {
      $('archiveEvent').removeClass( 'hidden' );
      $('unarchiveEvent').addClass( 'hidden' );
    }
  }
  // Technically, events can be different sizes, so may need to update the size of the image, but it might be better to have it stay scaled...
  //var eventImg = $('eventImage');
  //eventImg.setStyles( { 'width': eventData.width, 'height': eventData.height } );
  if (vid && CurEventDefVideoPath) {
    vid.src({type: 'video/mp4', src: CurEventDefVideoPath}); //Currently mp4 is all we use
    initialAlarmCues(eventData.Id);//ajax and render, new event
    addVideoTimingTrack(vid, LabelFormat, eventData.MonitorName, eventData.Length, eventData.StartTime);
    CurEventDefVideoPath = null;
    $j('#modeValue').html('Replay');
    $j('#zoomValue').html('1');
    $j('#rateValue').html('1');
    vjsPanZoom('zoomOut');
  } else {
    drawProgressBar();
  }
  nearEventsQuery( eventData.Id );
}

var eventReq = new Request.JSON( { url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: getEventResponse } );

function eventQuery( eventId ) {
  var eventParms = "view=request&request=status&entity=event&id="+eventId;
  eventReq.send( eventParms );
}

var prevEventId = 0;
var nextEventId = 0;
var prevEventStartTime = 0;
var nextEventStartTime = 0;
var PrevEventDefVideoPath = "";
var NextEventDefVideoPath = "";

function getNearEventsResponse( respObj, respText ) {
  if ( checkStreamForErrors( "getNearEventsResponse", respObj ) )
    return;
  prevEventId = respObj.nearevents.PrevEventId;
  nextEventId = respObj.nearevents.NextEventId;
  prevEventStartTime = Date.parse(respObj.nearevents.PrevEventStartTime);
  nextEventStartTime = Date.parse(respObj.nearevents.NextEventStartTime);
  PrevEventDefVideoPath = respObj.nearevents.PrevEventDefVideoPath;
  NextEventDefVideoPath = respObj.nearevents.NextEventDefVideoPath;

  var prevEventBtn = $('prevEventBtn');
  if ( prevEventBtn ) prevEventBtn.disabled = !prevEventId;
  var nextEventBtn = $('nextEventBtn');
  if ( nextEventBtn ) nextEventBtn.disabled = !nextEventId;
  $j('#prevBtn').prop('disabled', prevEventId == 0 ? true : false).attr('class', prevEventId == 0 ? 'unavail' : 'inactive');
  $j('#nextBtn').prop('disabled', nextEventId == 0 ? true : false).attr('class', nextEventId == 0 ? 'unavail' : 'inactive');
}

var nearEventsReq = new Request.JSON( { url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: getNearEventsResponse } );

function nearEventsQuery( eventId ) {
  var parms = "view=request&request=status&entity=nearevents&id="+eventId+filterQuery+sortQuery;
  nearEventsReq.send( parms );
}

var frameBatch = 40;

function loadEventThumb( event, frame, loadImage ) {
  var thumbImg = $('eventThumb'+frame.FrameId);
  if ( !thumbImg ) {
    console.error( "No holder found for frame "+frame.FrameId );
    return;
  }
  var img = new Asset.image( imagePrefix+frame.EventId+"&fid="+frame.FrameId,
      {
      'onload': ( function( loadImage ) {
          thumbImg.setProperty( 'src', img.getProperty( 'src' ) );
          thumbImg.removeClass( 'placeholder' );
          thumbImg.setProperty( 'class', frame.Type=='Alarm'?'alarm':'normal' );
          thumbImg.setProperty( 'title', frame.FrameId+' / '+((frame.Type=='Alarm')?frame.Score:0) );
          thumbImg.removeEvents( 'click' );
          thumbImg.addEvent( 'click', function() { locateImage( frame.FrameId, true ); } );
          if ( loadImage )
            loadEventImage( event, frame );
          } ).pass( loadImage )
      }
      );
}

function loadEventImage( event, frame ) {
  console.debug( "Loading "+event.Id+"/"+frame.FrameId );
  var eventImg = $('eventImage');
  var thumbImg = $('eventThumb'+frame.FrameId);
  if ( eventImg.getProperty( 'src' ) != thumbImg.getProperty( 'src' ) ) {
    var eventImagePanel = $('eventImagePanel');

    if ( eventImagePanel.getStyle( 'display' ) != 'none' ) {
      var lastThumbImg = $('eventThumb'+eventImg.getProperty( 'alt' ));
      lastThumbImg.removeClass('selected');
      lastThumbImg.setOpacity( 1.0 );
    }

    $('eventImageBar').setStyle( 'width', event.Width );
    if ( frame.Type=='Alarm' )
      $('eventImageStats').removeClass( 'hidden' );
    else
      $('eventImageStats').addClass( 'hidden' );
    thumbImg.addClass( 'selected' );
    thumbImg.setOpacity( 0.5 );

    if ( eventImagePanel.getStyle( 'display' ) == 'none' ) {
      eventImagePanel.setOpacity( 0 );
      eventImagePanel.setStyle( 'display', 'inline-block' );
      new Fx.Tween( eventImagePanel, { duration: 500, transition: Fx.Transitions.Sine } ).start( 'opacity', 0, 1 );
    }

    eventImg.setProperties( {
        'class': frame.Type=='Alarm'?'alarm':'normal',
        'src': thumbImg.getProperty( 'src' ),
        'title': thumbImg.getProperty( 'title' ),
        'alt': thumbImg.getProperty( 'alt' ),
        'height': $j('#eventThumbs').height() - $j('#eventImageBar').outerHeight(true)-10
        } );

    $('eventImageNo').set( 'text', frame.FrameId );
    $('prevImageBtn').disabled = (frame.FrameId==1);
    $('nextImageBtn').disabled = (frame.FrameId==event.Frames);
  }
}

function hideEventImageComplete() {
  var eventImg = $('eventImage');
  var thumbImg = $('eventThumb'+$('eventImage').getProperty( 'alt' ));
  if ( thumbImg ) {
    thumbImg.removeClass('selected');
    thumbImg.setOpacity( 1.0 );
  } else {
    console.log("Unable to find eventThumb at " + 'eventThumb'+$('eventImage').getProperty( 'alt' ) );
  }
  $('prevImageBtn').disabled = true;
  $('nextImageBtn').disabled = true;
  $('eventImagePanel').setStyle( 'display', 'none' );
  $('eventImageStats').addClass( 'hidden' );
}

function hideEventImage() {
  if ( $('eventImagePanel').getStyle( 'display' ) != 'none' )
    new Fx.Tween( $('eventImagePanel'), { duration: 500, transition: Fx.Transitions.Sine, onComplete: hideEventImageComplete } ).start( 'opacity', 1, 0 );
}

function resetEventStills() {
  hideEventImage();
  $('eventThumbs').empty();
  if ( true || !slider ) {
    slider = new Slider( $('thumbsSlider'), $('thumbsKnob'), {
      /*steps: eventData.Frames,*/
      onChange: function( step ) {
                  if ( !step )
                    step = 0;
                  var fid = parseInt((step * eventData.Frames)/this.options.steps);
                  if ( fid < 1 )
                    fid = 1;
                  else if ( fid > eventData.Frames )
                    fid = eventData.Frames;
                  checkFrames( eventData.Id, fid, ($j('#eventImagePanel').css('display')=='none'?'':'true'));
                  scroll.toElement( 'eventThumb'+fid );
                 }
    } ).set( 0 );
  }
}

function getFrameResponse( respObj, respText ) {
  if ( checkStreamForErrors( "getFrameResponse", respObj ) )
    return;

  var frame = respObj.frameimage;

  if ( !eventData ) {
    console.error( "No event "+frame.EventId+" found" );
    return;
  }

  if ( !eventData['frames'] )
    eventData['frames'] = new Object();

  eventData['frames'][frame.FrameId] = frame;

  loadEventThumb( eventData, frame, respObj.loopback=="true" );
}

var frameReq = new Request.JSON( { url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'chain', onSuccess: getFrameResponse } );

function frameQuery( eventId, frameId, loadImage ) {
  var parms = "view=request&request=status&entity=frameimage&id[0]="+eventId+"&id[1]="+frameId+"&loopback="+loadImage;
  frameReq.send( parms );
}

var currFrameId = null;

function checkFrames( eventId, frameId, loadImage ) {
  if ( !eventData ) {
    console.error( "No event "+eventId+" found" );
    return;
  }

  if ( !eventData['frames'] )
    eventData['frames'] = new Object();

  currFrameId = frameId;

  var loFid = frameId - frameBatch/2;
  if ( loFid < 1 )
    loFid = 1;
  var hiFid = loFid + (frameBatch-1);
  if ( hiFid > eventData.Frames )
    hiFid = eventData.Frames;

  for ( var fid = loFid; fid <= hiFid; fid++ ) {
    if ( !$('eventThumb'+fid) ) {
      var img = new Element( 'img', { 'id': 'eventThumb'+fid, 'src': 'graphics/transparent.png', 'alt': fid, 'class': 'placeholder' } );
      img.addEvent( 'click', function() { eventData['frames'][fid] = null; checkFrames( eventId, fid ); } );
      frameQuery( eventId, fid, loadImage && (fid == frameId) );
      var imgs = $('eventThumbs').getElements( 'img' );
      var injected = false;
      if ( fid < imgs.length ) {
        img.inject( imgs[fid-1], 'before' );
        injected = true;
      } else {
        injected = imgs.some(
            function( thumbImg, index ) {
              if ( parseInt(img.getProperty( 'alt' )) < parseInt(thumbImg.getProperty( 'alt' )) ) {
                img.inject( thumbImg, 'before' );
                return( true );
              }
              return( false );
            }
            );
      }
      if ( !injected ) {
        img.inject( $('eventThumbs') );
      }
      var scale = parseInt(img.getStyle('height'));
      img.setStyles( {
          'width': parseInt((eventData.Width*scale)/100),
          'height': parseInt((eventData.Height*scale)/100)
          } );
    } else if ( eventData['frames'][fid] ) {
      if ( loadImage && (fid == frameId) ) {
        loadEventImage( eventData, eventData['frames'][fid], loadImage );
      }
    }
  }
  $('prevThumbsBtn').disabled = (frameId==1);
  $('nextThumbsBtn').disabled = (frameId==eventData.Frames);
}

function locateImage( frameId, loadImage ) {
  if ( slider )
    slider.fireEvent( 'tick', slider.toPosition( parseInt((frameId-1)*slider.options.steps/eventData.Frames) ));
  checkFrames( eventData.Id, frameId, loadImage );
  scroll.toElement( 'eventThumb'+frameId );
}

function prevImage() {
  if ( currFrameId > 1 )
    locateImage( parseInt(currFrameId)-1, true );
}

function nextImage() {
  if ( currFrameId < eventData.Frames )
    locateImage( parseInt(currFrameId)+1, true );
}

function prevThumbs() {
  if ( currFrameId > 1 )
    locateImage( parseInt(currFrameId)>10?(parseInt(currFrameId)-10):1, $('eventImagePanel').getStyle('display')!="none" );
}

function nextThumbs() {
  if ( currFrameId < eventData.Frames )
    locateImage( parseInt(currFrameId)<(eventData.Frames-10)?(parseInt(currFrameId)+10):eventData.Frames, $('eventImagePanel').getStyle('display')!="none" );
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
  if ( checkStreamForErrors( "getActResponse", respObj ) )
    return;

  if ( respObj.refreshEvent )
    eventQuery( eventData.Id );
}

var actReq = new Request.JSON( { url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: getActResponse } );

function actQuery( action, parms ) {
  var actParms = "view=request&request=event&id="+eventData.Id+"&action="+action;
  if ( parms != null )
    actParms += "&"+Object.toQueryString( parms );
  actReq.send( actParms );
}

function deleteEvent() {
  pauseClicked(); //Provides visual feedback that your click happened.
  actQuery( 'delete' );
  streamNext( true );
}

function renameEvent() {
  var newName = $('eventName').get('value');
  actQuery( 'rename', { eventName: newName } );
}

function editEvent() {
  createPopup( '?view=eventdetail&eid='+eventData.Id, 'zmEventDetail', 'eventdetail' );
}

function exportEvent() {
  createPopup( '?view=export&eid='+eventData.Id, 'zmExport', 'export' );
}

function archiveEvent() {
  actQuery( 'archive' );
}

function unarchiveEvent() {
  actQuery( 'unarchive' );
}

function showEventFrames() {
  createPopup( '?view=frames&eid='+eventData.Id, 'zmFrames', 'frames' );
}

function showStream() {
  $('eventStills').addClass( 'hidden' );
  $('eventVideo').removeClass( 'hidden' );

  $('stillsEvent').removeClass( 'hidden' );
  $('streamEvent').addClass( 'hidden' );

  streamMode = 'video';
  if (scale == 'auto') changeScale();
}

function showStills() {
  $('eventStills').removeClass( 'hidden' );
  $('eventVideo').addClass( 'hidden' );

  if (vid && ( vid.paused != true ) ) {
    // Pause the video
    vid.pause();

    // Update the button text to 'Play'
    //if ( playButton )
    //playButton.innerHTML = "Play";
  }

  $('stillsEvent').addClass( 'hidden' );
  $('streamEvent').removeClass( 'hidden' );

  streamMode = 'stills';

  pauseClicked();
  if ( !scroll ) {
    scroll = new Fx.Scroll( 'eventThumbs', {
      wait: false,
      duration: 500,
      offset: { 'x': 0, 'y': 0 },
      transition: Fx.Transitions.Quad.easeInOut
      }
    );
  }
  resetEventStills();
  if (scale == 'auto') changeScale();
}

function showFrameStats() {
  var fid = $('eventImageNo').get('text');
  createPopup( '?view=stats&eid='+eventData.Id+'&fid='+fid, 'zmStats', 'stats', eventData.Width, eventData.Height );
}

function videoEvent() {
  createPopup( '?view=video&eid='+eventData.Id, 'zmVideo', 'video', eventData.Width, eventData.Height );
}

// Called on each event load because each event can be a different width
function drawProgressBar() {
  let barWidth = $j('#evtStream').width();
  $j('#progressBar').css( 'width', barWidth );
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
function progressBarNav (){
  $j('#progressBar').click(function(e){
    var x = e.pageX - $j(this).offset().left;
    var seekTime = (x / $j('#progressBar').width()) * parseFloat(eventData.Length);
    streamSeek (seekTime);
  });
}

function handleClick( event ) {
  var target = event.target;
  if (vid) {
    if (target.id != 'videoobj') return; //ignore clicks on control bar
    var x = event.offsetX;
    var y = event.offsetY;
  } else {
    var x = event.page.x - $(target).getLeft();
    var y = event.page.y - $(target).getTop();
  }

  if (event.shift || event.shiftKey) {//handle both jquery and mootools
    streamPan(x, y);
  } else if (vid && event.ctrlKey) { //allow zoom out by control click.  useful in fullscreen
    vjsPanZoom('zoomOut', x, y);
  } else {
    streamZoomIn(x, y);
  }
}

function initPage() {
  //FIXME prevent blocking...not sure what is happening or best way to unblock
  if ($j('#videoobj').length) {
    vid = videojs('videoobj');
    addVideoTimingTrack(vid, LabelFormat, eventData.MonitorName, eventData.Length, eventData.StartTime);
    $j('.vjs-progress-control').append('<div class="alarmCue"></div>');//add a place for videojs only on first load
    vid.on('ended', vjsReplay);
    vid.on('play', vjsPlay);
    vid.on('pause', vjsPause);
    vid.on('click', function(event){handleClick(event);});
    vid.on('timeupdate', function (){$j('#progressValue').html(secsToTime(Math.floor(vid.currentTime())))});
  } else {
    progressBarNav ();
    streamCmdTimer = streamQuery.delay( 250 );
    if ( canStreamNative ) {
      var imageFeed = $('imageFeed');
      if ( !imageFeed ) {
        console.log('No element with id tag imageFeed found.');
      } else {
        var streamImg = imageFeed.getElement('img');
        if ( !streamImg )
          streamImg = imageFeed.getElement('object');
        $(streamImg).addEvent( 'click', function( event ) { handleClick( event ); } );
      }
    }
  }
  nearEventsQuery(eventData.Id);
  initialAlarmCues(eventData.Id); //call ajax+renderAlarmCues
  if (scale == "auto") changeScale();
}

// Kick everything off
window.addEvent( 'domready', initPage );
