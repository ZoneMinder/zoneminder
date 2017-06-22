var vid = null;

function setButtonState( element, butClass ) {
  if ( element ) {
    element.className = butClass;
    element.disabled = (butClass != 'inactive');
  } else {
    console.log("Element was null in setButtonState");
  }
}

function changeScale() {
  var scale = $('scale').get('value');
  var baseWidth = eventData.Width;
  var baseHeight = eventData.Height;
  var newWidth = ( baseWidth * scale ) / SCALE_BASE;
  var newHeight = ( baseHeight * scale ) / SCALE_BASE;

	if ( vid ) {
    // Using video.js
		vid.width = newWidth;
		vid.height = newHeight;
	} else {
    streamScale( scale );
		var streamImg = document.getElementById('evtStream');
		streamImg.style.width = newWidth + "px";
		streamImg.style.height = newHeight + "px";
    Cookie.write( 'zmEventScale'+eventData.MonitorId, scale, { duration: 10*365 } );
  }
}

function changeReplayMode() {
  var replayMode = $('replayMode').get('value');

  Cookie.write( 'replayMode', replayMode, { duration: 10*365 });

  refreshWindow();
}

var streamParms = "view=request&request=stream&connkey="+connKey;
var streamCmdTimer = null;

var streamStatus = null;
var lastEventId = 0;

function getCmdResponse( respObj, respText ) {
  if ( checkStreamForErrors( "getCmdResponse", respObj ) ) {
    console.log('Got an error from getCmdResponse');
    return;
  }

  if ( streamCmdTimer )
    streamCmdTimer = clearTimeout( streamCmdTimer );

  streamStatus = respObj.status;

  var eventId = streamStatus.event;
  if ( eventId != lastEventId ) {
    eventQuery( eventId );
    lastEventId = eventId;
  }
  if ( streamStatus.paused == true ) {
    $('modeValue').set( 'text', 'Paused' );
    $('rate').addClass( 'hidden' );
    streamPause( );
  } else {
    console.log('playing');
    $('modeValue').set( 'text', "Replay" );
    $('rateValue').set( 'text', streamStatus.rate );
    $('rate').removeClass( 'hidden' );
    streamPlay( );
  }
  $('progressValue').set( 'text', secsToTime( parseInt(streamStatus.progress) ) );
  $('zoomValue').set( 'text', streamStatus.zoom );
  if ( streamStatus.zoom == "1.0" )
    setButtonState( $('zoomOutBtn'), 'unavail' );
  else
    setButtonState( $('zoomOutBtn'), 'inactive' );

  updateProgressBar();

  if ( streamStatus.auth ) {
    // Try to reload the image stream.
    var streamImg = document.getElementById('evtStream');
    if ( streamImg )
      streamImg.src = streamImg.src.replace( /auth=\w+/i, 'auth='+streamStatus.auth );
  } // end if haev a new auth hash

  streamCmdTimer = streamQuery.delay( streamTimeout );
}

var streamReq = new Request.JSON( { url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'chain', onSuccess: getCmdResponse } );

function pauseClicked( ) {
  streamReq.send( streamParms+"&command="+CMD_PAUSE );
}

// Called when stream becomes paused, just updates the button status
function streamPause( ) {
  setButtonState( $('pauseBtn'), 'active' );
  setButtonState( $('playBtn'), 'inactive' );
  setButtonState( $('fastFwdBtn'), 'unavail' );
  setButtonState( $('slowFwdBtn'), 'inactive' );
  setButtonState( $('slowRevBtn'), 'inactive' );
  setButtonState( $('fastRevBtn'), 'unavail' );
}

function playClicked( ) {
  streamReq.send( streamParms+"&command="+CMD_PLAY );
}

function streamPlay( ) {
  setButtonState( $('pauseBtn'), 'inactive' );
  if (streamStatus)
    setButtonState( $('playBtn'), streamStatus.rate==1?'active':'inactive' );
  setButtonState( $('fastFwdBtn'), 'inactive' );
  setButtonState( $('slowFwdBtn'), 'unavail' );
  setButtonState( $('slowRevBtn'), 'unavail' );
  setButtonState( $('fastRevBtn'), 'inactive' );
}

function streamFastFwd( action ) {
  setButtonState( $('pauseBtn'), 'inactive' );
  setButtonState( $('playBtn'), 'inactive' );
  setButtonState( $('fastFwdBtn'), 'inactive' );
  setButtonState( $('slowFwdBtn'), 'unavail' );
  setButtonState( $('slowRevBtn'), 'unavail' );
  setButtonState( $('fastRevBtn'), 'inactive' );
  streamReq.send( streamParms+"&command="+CMD_FASTFWD );
}

function streamSlowFwd( action ) {
  setButtonState( $('pauseBtn'), 'inactive' );
  setButtonState( $('playBtn'), 'inactive' );
  setButtonState( $('fastFwdBtn'), 'unavail' );
  setButtonState( $('slowFwdBtn'), 'active' );
  setButtonState( $('slowRevBtn'), 'inactive' );
  setButtonState( $('fastRevBtn'), 'unavail' );
  streamReq.send( streamParms+"&command="+CMD_SLOWFWD );
  setButtonState( $('pauseBtn'), 'inactive' );
  setButtonState( $('slowFwdBtn'), 'inactive' );
}

function streamSlowRev( action ) {
  setButtonState( $('pauseBtn'), 'inactive' );
  setButtonState( $('playBtn'), 'inactive' );
  setButtonState( $('fastFwdBtn'), 'unavail' );
  setButtonState( $('slowFwdBtn'), 'inactive' );
  setButtonState( $('slowRevBtn'), 'active' );
  setButtonState( $('fastRevBtn'), 'unavail' );
  streamReq.send( streamParms+"&command="+CMD_SLOWREV );
  setButtonState( $('pauseBtn'), 'inactive' );
  setButtonState( $('slowRevBtn'), 'inactive' );
}

function streamFastRev( action ) {
  setButtonState( $('pauseBtn'), 'inactive' );
  setButtonState( $('playBtn'), 'inactive' );
  setButtonState( $('fastFwdBtn'), 'inactive' );
  setButtonState( $('slowFwdBtn'), 'unavail' );
  setButtonState( $('slowRevBtn'), 'unavail' );
  setButtonState( $('fastRevBtn'), 'inactive' );
  streamReq.send( streamParms+"&command="+CMD_FASTREV );
}

function streamPrev( action ) {
  if ( action )
    streamReq.send( streamParms+"&command="+CMD_PREV );
}

function streamNext( action ) {
  if ( action )
    streamReq.send( streamParms+"&command="+CMD_NEXT );
}

function streamZoomIn( x, y ) {
  streamReq.send( streamParms+"&command="+CMD_ZOOMIN+"&x="+x+"&y="+y );
}

function streamZoomOut() {
  streamReq.send( streamParms+"&command="+CMD_ZOOMOUT );
}

function streamScale( scale ) {
  streamReq.send( streamParms+"&command="+CMD_SCALE+"&scale="+scale );
}

function streamPan( x, y ) {
  streamReq.send( streamParms+"&command="+CMD_PAN+"&x="+x+"&y="+y );
}

function streamSeek( offset ) {
  streamReq.send( streamParms+"&command="+CMD_SEEK+"&offset="+offset );
}

function streamQuery() {
  streamReq.send( streamParms+"&command="+CMD_QUERY );
}

var slider = null;
var scroll = null;

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
  drawProgressBar();
  nearEventsQuery( eventData.Id );
}

var eventReq = new Request.JSON( { url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: getEventResponse } );

function eventQuery( eventId ) {
  var eventParms = "view=request&request=status&entity=event&id="+eventId;
  eventReq.send( eventParms );
}

var prevEventId = 0;
var nextEventId = 0;
var PrevEventDefVideoPath = "";
var NextEventDefVideoPath = "";

function getNearEventsResponse( respObj, respText ) {
  if ( checkStreamForErrors( "getNearEventsResponse", respObj ) )
    return;
  prevEventId = respObj.nearevents.PrevEventId;
  nextEventId = respObj.nearevents.NextEventId;
  PrevEventDefVideoPath = respObj.nearevents.PrevEventDefVideoPath;
  NextEventDefVideoPath = respObj.nearevents.NextEventDefVideoPath;

  var prevEventBtn = $('prevEventBtn');
  if ( prevEventBtn ) prevEventBtn.disabled = !prevEventId;
  var nextEventBtn = $('nextEventBtn');
  if ( nextEventBtn ) nextEventBtn.disabled = !nextEventId;
}

var nearEventsReq = new Request.JSON( { url: thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: getNearEventsResponse } );

function nearEventsQuery( eventId ) {
  var parms = "view=request&request=status&entity=nearevents&id="+eventId;
  nearEventsReq.send( parms );
}

var frameBatch = 40;

function loadEventThumb( event, frame, loadImage ) {
  var thumbImg = $('eventThumb'+frame.FrameId);
  if ( !thumbImg ) {
    console.error( "No holder found for frame "+frame.FrameId );
    return;
  }
  var img = new Asset.image( imagePrefix+frame.Image.imagePath,
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

function updateStillsSizes( noDelay ) {
  var containerDim = $('eventThumbs').getSize();

  var containerWidth = containerDim.x;
  var containerHeight = containerDim.y;
  var popupWidth = parseInt($('eventImage').getStyle( 'width' ));
  var popupHeight = parseInt($('eventImage').getStyle( 'height' ));

  var left = (containerWidth - popupWidth)/2;
  if ( left < 0 ) left = 0;
  var top = (containerHeight - popupHeight)/2;
  if ( top < 0 ) top = 0;
  if ( popupHeight == 0 && !noDelay ) {
    // image not yet loaded lets give it another second
    updateStillsSizes.pass( true ).delay( 50 );
    return;
  }
  $('eventImagePanel').setStyles( {
      'left': left,
      'top': top
      } );
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

    eventImg.setProperties( {
        'class': frame.Type=='Alarm'?'alarm':'normal',
        'src': thumbImg.getProperty( 'src' ),
        'title': thumbImg.getProperty( 'title' ),
        'alt': thumbImg.getProperty( 'alt' ),
        'width': event.Width,
        'height': event.Height
        } );
    $('eventImageBar').setStyle( 'width', event.Width );
    if ( frame.Type=='Alarm' )
      $('eventImageStats').removeClass( 'hidden' );
    else
      $('eventImageStats').addClass( 'hidden' );
    thumbImg.addClass( 'selected' );
    thumbImg.setOpacity( 0.5 );

    if ( eventImagePanel.getStyle( 'display' ) == 'none' ) {
      eventImagePanel.setOpacity( 0 );
      updateStillsSizes();
      eventImagePanel.setStyle( 'display', 'block' );
      new Fx.Tween( eventImagePanel, { duration: 500, transition: Fx.Transitions.Sine } ).start( 'opacity', 0, 1 );
    }

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
                  checkFrames( eventData.Id, fid );
                  scroll.toElement( 'eventThumb'+fid );
                 }
    } ).set( 0 );
  }
  if ( $('eventThumbs').getStyle( 'height' ).match( /^\d+/ ) < (parseInt(eventData.Height)+80) )
    $('eventThumbs').setStyle( 'height', (parseInt(eventData.Height)+80)+'px' );
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
      var img = new Element( 'img', { 'id': 'eventThumb'+fid, 'src': 'graphics/transparent.gif', 'alt': fid, 'class': 'placeholder' } );
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

  if ( respObj.refreshParent )
    refreshParentWindow();

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

function showVideo() {
  $('eventStills').addClass( 'hidden' );
  $('imageFeed').addClass('hidden');
  $('eventVideo').removeClass( 'hidden' );

  $('stillsEvent').removeClass( 'hidden' );
  $('videoEvent').addClass( 'hidden' );

  streamMode = 'video';
}

function showStills() {
  $('eventStills').removeClass( 'hidden' );
  $('imageFeed').removeClass('hidden');
  $('eventVideo').addClass( 'hidden' );		

  if (vid && ( vid.paused != true ) ) {
    // Pause the video
    vid.pause();

    // Update the button text to 'Play'
    //if ( playButton )
    //playButton.innerHTML = "Play";
  }

  $('stillsEvent').addClass( 'hidden' );
  $('videoEvent').removeClass( 'hidden' );

  streamMode = 'stills';

  streamPause( true );
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
  $(window).addEvent( 'resize', updateStillsSizes );
}

function showFrameStats() {
  var fid = $('eventImageNo').get('text');
  createPopup( '?view=stats&eid='+eventData.Id+'&fid='+fid, 'zmStats', 'stats', eventData.Width, eventData.Height );
}

function videoEvent() {
  createPopup( '?view=video&eid='+eventData.Id, 'zmVideo', 'video', eventData.Width, eventData.Height );
}

// Called on each event load, because each event can be a different length.
function drawProgressBar() {
  // Make it invisible so we don't see the update happen
  $('progressBar').addClass( 'invisible' );
  var barWidth = 0;
  var cells = $('progressBar').getElements( 'div' );
  var cells_length = cells.length;

  var cellWidth = parseInt( eventData.Width / cells_length );
  for ( var index = 0; index < cells_length; index += 1 ) {
    var cell = $( cells[index] );
    if ( index == 0 ) 
      cell.setStyles( { 'left': barWidth, 'width': cellWidth, 'borderLeft': 0 } );
    else
      cell.setStyles( { 'left': barWidth, 'width': cellWidth } );

    var offset = parseInt( (index*eventData.Length)/cells_length );
    cell.setProperty( 'title', '+'+secsToTime(offset)+'s' );
    cell.removeEvent( 'click' );
    cell.addEvent( 'click', function() { streamSeek( offset ); } );
    barWidth += cell.getCoordinates().width;
  }
  $('progressBar').setStyle( 'width', barWidth );
  $('progressBar').removeClass( 'invisible' );
}

// Changes the colour of the cells making up the completed progress bar
function updateProgressBar() {
  if ( ! ( eventData && streamStatus ) ) {
    return;
  } // end if ! eventData && streamStatus
  var cells = $('progressBar').getElements( 'div' );
  var cells_length = cells.length;
  var completeIndex = parseInt(((cells_length+1)*streamStatus.progress)/eventData.Length);
  for ( var index = 0; index < cells_length; index += 1 ) {
    var cell = $( cells[index] );
    if ( index < completeIndex ) {
      if ( !cell.hasClass( 'complete' ) ) {
        cell.addClass( 'complete' );
      }
    } else {
      if ( cell.hasClass( 'complete' ) ) {
        cell.removeClass( 'complete' );
      }
    } // end if
  } // end function
} // end function updateProgressBar()

function handleClick( event ) {
  var target = event.target;
  var x = event.page.x - $(target).getLeft();
  var y = event.page.y - $(target).getTop();

  if ( event.shift )
    streamPan( x, y );
  else
    streamZoomIn( x, y );
}

function setupListener() {

  // Buttons
  var playButton = document.getElementById("play-pause");
  var muteButton = document.getElementById("mute");
  var fullScreenButton = document.getElementById("full-screen");

  // Sliders
  var seekBar = document.getElementById("seekbar");
  var volumeBar = document.getElementById("volume-bar");

  // Event listener for the play/pause button
  playButton.addEventListener( "click", function() {
      if (vid.paused == true) {
      // Play the video
      vid.play();

      // Update the button text to 'Pause'
      playButton.innerHTML = "Pause";
      } else {
      // Pause the video
      vid.pause();

      // Update the button text to 'Play'
      playButton.innerHTML = "Play";
      }
      });


  // Event listener for the mute button
  muteButton.addEventListener("click", function() {
      if (vid.muted == false) {
      // Mute the video
      vid.muted = true;

      // Update the button text
      muteButton.innerHTML = "Unmute";
      } else {
      // Unmute the video
      vid.muted = false;

      // Update the button text
      muteButton.innerHTML = "Mute";
      }
      });


  // Event listener for the full-screen button
  fullScreenButton.addEventListener("click", function() {
      if (vid.requestFullscreen) {
      vid.requestFullscreen();
      } else if (vid.mozRequestFullScreen) {
      vid.mozRequestFullScreen(); // Firefox
      } else if (vid.webkitRequestFullscreen) {
      vid.webkitRequestFullscreen(); // Chrome and Safari
      }
      });


  // Event listener for the seek bar
  seekBar.addEventListener("change", function() {
      // Calculate the new time
      var time = vid.duration * (seekBar.value / 100);

      // Update the video time
      vid.currentTime = time;
      });


  // Update the seek bar as the video plays
  vid.addEventListener("timeupdate", function() {
      // Calculate the slider value
      var value = (100 / vid.duration) * vid.currentTime;

      // Update the slider value
      seekBar.value = value;
      });

  // Pause the video when the seek handle is being dragged
  seekBar.addEventListener("mousedown", function() {
      vid.pause();
      });

  // Play the video when the seek handle is dropped
  seekBar.addEventListener("mouseup", function() {
      vid.play();
      });

  // Event listener for the volume bar
  volumeBar.addEventListener("change", function() {
      // Update the video volume
      vid.volume = volumeBar.value;
      });
}

function initPage() {
  //FIXME prevent blocking...not sure what is happening or best way to unblock
  if ( $('videoobj') ) {
    vid = videojs("videoobj");
  }
  if ( vid ) {
/*
    setupListener();
      vid.removeAttribute("controls");
    /* window.videoobj.oncanplay=null;
    window.videoobj.currentTime=window.videoobj.currentTime-1;
    window.videoobj.currentTime=window.videoobj.currentTime+1;//may not be symetrical of course

    vid.onstalled=function(){window.vid.currentTime=window.vid.currentTime-1;window.vid.currentTime=window.vid.currentTime+1;} 
    vid.onwaiting=function(){window.vid.currentTime=window.vid.currentTime-1;window.vid.currentTime=window.vid.currentTime+1;}
    vid.onloadstart=function(){window.vid.currentTime=window.vid.currentTime-1;window.vid.currentTime=window.vid.currentTime+1;}
    vid.onplay=function(){window.vid.currentTime=window.vid.currentTime-1;window.vid.currentTime=window.vid.currentTime+1;}
    vid.onplaying=function(){window.vid.currentTime=window.vid.currentTime-1;window.vid.currentTime=window.vid.currentTime+1;}
    //window.vid.hide();//does not help
    var sources = window.videoobj.getElementsByTagName('source');
    sources[0].src=null;
    window.videoobj.load();
    streamPlay();    */
  } else {
    streamCmdTimer = streamQuery.delay( 250 );
    eventQuery.pass( eventData.Id ).delay( 500 );

    if ( canStreamNative ) {
      var streamImg = $('imageFeed').getElement('img');
      if ( !streamImg )
        streamImg = $('imageFeed').getElement('object');
      $(streamImg).addEvent( 'click', function( event ) { handleClick( event ); } );
    }
  }
}

// Kick everything off
window.addEvent( 'domready', initPage );
