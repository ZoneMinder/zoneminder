var streamStatus;
var auth_hash;
var alarmState = STATE_IDLE;
var lastAlarmState = STATE_IDLE;
var backBtn = $j('#backBtn');
var settingsBtn = $j('#settingsBtn');
var enableAlmBtn = $j('#enableAlmBtn');
var forceAlmBtn = $j('#forceAlmBtn');
var table = $j('#eventList');
var filterQuery = '&filter[Query][terms][0][attr]=MonitorId&filter[Query][terms][0][op]=%3d&filter[Query][terms][0][val]='+monitorId;

if ( monitorType != 'WebSite' ) {
  var streamCmdParms = 'view=request&request=stream&connkey='+connKey;
  if ( auth_hash ) {
    streamCmdParms += '&auth='+auth_hash;
  }
  var streamCmdReq = new Request.JSON( {
    url: monitorUrl,
    method: 'get',
    timeout: AJAX_TIMEOUT,
    link: 'chain',
    onError: getStreamCmdError,
    onSuccess: getStreamCmdResponse,
    onFailure: getStreamCmdFailure
  } );
  var streamCmdTimer = null;
}

/*
This is the format of the json object sent by bootstrap-table

var params =
{
"type":"get",
"data":
  {
  "search":"some search text",
  "sort":"StartDateTime",
  "order":"asc",
  "offset":0,
  "limit":25
  "filter":
    {
    "Name":"some advanced search text"
    "StartDateTime":"some more advanced search text"
    }
  },
"cache":true,
"contentType":"application/json",
"dataType":"json"
};
*/

// Called by bootstrap-table to retrieve zm event data
function ajaxRequest(params) {
  // Maintain legacy behavior by statically setting these parameters
  params.data.order = 'desc';
  params.data.limit = maxDisplayEvents;
  params.data.sort = 'Id';

  $j.getJSON(thisUrl + '?view=request&request=events&task=query'+filterQuery, params.data)
      .done(function(data) {
        var rows = processRows(data.rows);
        // rearrange the result into what bootstrap-table expects
        params.success({total: data.total, totalNotFiltered: data.totalNotFiltered, rows: rows});
      })
      .fail(logAjaxFail);
}

function processRows(rows) {
  $j.each(rows, function(ndx, row) {
    var eid = row.Id;

    row.Delete = '<i class="fa fa-trash text-danger"></i>';
    row.Id = '<a href="?view=event&amp;eid=' + eid + filterQuery + '">' + eid + '</a>';
    row.Name = '<a href="?view=event&amp;eid=' + eid + filterQuery + '">' + row.Name + '</a>';
    row.Frames = '<a href="?view=frames&amp;eid=' + eid + '">' + row.Frames + '</a>';
    row.AlarmFrames = '<a href="?view=frames&amp;eid=' + eid + '">' + row.AlarmFrames + '</a>';
    row.MaxScore = '<a href="?view=frame&amp;eid=' + eid + '&amp;fid=0">' + row.MaxScore + '</a>';
    if ( LIST_THUMBS ) row.Thumbnail = '<a href="?view=event&amp;eid=' + eid + filterQuery + '&amp;page=1">' + row.imgHtml + '</a>';
  });

  return rows;
}

function thumbnail_onmouseover(event) {
  var img = event.target;
  img.src = '';
  img.src = img.getAttribute('stream_src');
}

function thumbnail_onmouseout(event) {
  var img = event.target;
  img.src = '';
  img.src = img.getAttribute('still_src');
}

function initThumbAnimation() {
  if ( ANIMATE_THUMBS ) {
    $j('.colThumbnail img').each(function() {
      this.addEventListener('mouseover', thumbnail_onmouseover, false);
      this.addEventListener('mouseout', thumbnail_onmouseout, false);
    });
  }
}

function showEvents() {
  $('ptzControls').addClass('hidden');
  $('events').removeClass('hidden');
  if ( $('eventsControl') ) {
    $('eventsControl').addClass('hidden');
  }
  if ( $('controlControl') ) {
    $('controlControl').removeClass('hidden');
  }
  showMode = 'events';
}

function showPtzControls() {
  $('events').addClass('hidden');
  $('ptzControls').removeClass('hidden');
  if ( $('eventsControl') ) {
    $('eventsControl').removeClass('hidden');
  }
  if ( $('controlControl') ) {
    $('controlControl').addClass('hidden');
  }
  showMode = 'control';
}

function changeScale() {
  var scale = $('scale').get('value');
  var newWidth;
  var newHeight;
  if ( scale == '0' || scale == 'auto' ) {
    var newSize = scaleToFit(monitorWidth, monitorHeight, $j('#liveStream'+monitorId), $j('#replayStatus'));
    newWidth = newSize.width;
    newHeight = newSize.height;
    autoScale = newSize.autoScale;
  } else {
    $j(window).off('resize', endOfResize); //remove resize handler when Scale to Fit is not active
    newWidth = monitorWidth * scale / SCALE_BASE;
    newHeight = monitorHeight * scale / SCALE_BASE;
  }

  Cookie.write('zmWatchScale'+monitorId, scale, {duration: 10*365, samesite: 'strict'});

  /*Stream could be an applet so can't use moo tools*/
  var streamImg = $('liveStream'+monitorId);
  if ( streamImg ) {
    streamImg.style.width = newWidth + 'px';
    streamImg.style.height = newHeight + 'px';

    streamImg.src = streamImg.src.replace(/scale=\d+/i, 'scale='+(scale== 'auto' ? autoScale : scale));
  } else {
    console.error('No element found for liveStream'+monitorId);
  }
}

function setAlarmState( currentAlarmState ) {
  alarmState = currentAlarmState;

  var stateClass = '';
  if ( alarmState == STATE_ALARM ) {
    stateClass = 'alarm';
  } else if ( alarmState == STATE_ALERT ) {
    stateClass = 'alert';
  }
  $('stateValue').set('text', stateStrings[alarmState]);
  if ( stateClass ) {
    $('stateValue').setProperty('class', stateClass);
  } else {
    $('stateValue').removeProperty('class');
  }

  var isAlarmed = ( alarmState == STATE_ALARM || alarmState == STATE_ALERT );
  var wasAlarmed = ( lastAlarmState == STATE_ALARM || lastAlarmState == STATE_ALERT );

  var newAlarm = ( isAlarmed && !wasAlarmed );
  var oldAlarm = ( !isAlarmed && wasAlarmed );

  if ( newAlarm ) {
    table.bootstrapTable('refresh');
    if ( SOUND_ON_ALARM ) {
      // Enable the alarm sound
      if ( !canPlayPauseAudio ) {
        $('alarmSound').removeClass('hidden');
      } else {
        $('MediaPlayer').Play();
      }
    }
    if ( POPUP_ON_ALARM ) {
      window.focus();
    }
  }
  if ( oldAlarm ) { // done with an event do a refresh
    table.bootstrapTable('refresh');
    if ( SOUND_ON_ALARM ) {
      // Disable alarm sound
      if ( !canPlayPauseAudio ) {
        $('alarmSound').addClass('hidden');
      } else {
        $('MediaPlayer').Stop();
      }
    }
  }

  lastAlarmState = alarmState;
} // end function setAlarmState( currentAlarmState )

function getStreamCmdError(text, error) {
  console.log(error);
  // Error are normally due to failed auth. reload the page.
  window.location.reload();
}

function getStreamCmdFailure(xhr) {
  console.log(xhr);
}

function getStreamCmdResponse(respObj, respText) {
  watchdogOk('stream');
  if ( streamCmdTimer ) {
    streamCmdTimer = clearTimeout(streamCmdTimer);
  }
  if ( respObj.result == 'Ok' ) {
    // The get status command can get backed up, in which case we won't be able to get the semaphore and will exit.
    if ( respObj.status ) {
      streamStatus = respObj.status;
      $('fpsValue').set('text', streamStatus.fps);

      setAlarmState(streamStatus.state);

      $('levelValue').set('text', streamStatus.level);
      if ( streamStatus.level > 95 ) {
        $('levelValue').className = 'alarm';
      } else if ( streamStatus.level > 80 ) {
        $('levelValue').className = 'alert';
      } else {
        $('levelValue').className = 'ok';
      }

      var delayString = secsToTime(streamStatus.delay);

      if ( streamStatus.paused == true ) {
        $('modeValue').set('text', 'Paused');
        $('rate').addClass('hidden');
        $('delayValue').set('text', delayString);
        $('delay').removeClass('hidden');
        $('level').removeClass('hidden');
        streamCmdPause(false);
      } else if ( streamStatus.delayed == true ) {
        $('modeValue').set('text', 'Replay');
        $('rateValue').set('text', streamStatus.rate);
        $('rate').removeClass('hidden');
        $('delayValue').set('text', delayString);
        $('delay').removeClass('hidden');
        $('level').removeClass('hidden');
        if ( streamStatus.rate == 1 ) {
          streamCmdPlay(false);
        } else if ( streamStatus.rate > 0 ) {
          if ( streamStatus.rate < 1 ) {
            streamCmdSlowFwd(false);
          } else {
            streamCmdFastFwd(false);
          }
        } else {
          if ( streamStatus.rate > -1 ) {
            streamCmdSlowRev(false);
          } else {
            streamCmdFastRev(false);
          }
        } // rate
      } else {
        $('modeValue').set( 'text', 'Live' );
        $('rate').addClass( 'hidden' );
        $('delay').addClass( 'hidden' );
        $('level').addClass( 'hidden' );
        streamCmdPlay(false);
      } // end if paused or delayed

      $('zoomValue').set('text', streamStatus.zoom);
      if ( streamStatus.zoom == '1.0' ) {
        setButtonState('zoomOutBtn', 'unavail');
      } else {
        setButtonState('zoomOutBtn', 'inactive');
      }

      if ( canEditMonitors ) {
        if ( streamStatus.enabled ) {
          enableAlmBtn.addClass('disabled');
          enableAlmBtn.prop('title', disableAlarmsStr);
          if ( streamStatus.forced ) {
            forceAlmBtn.addClass('disabled');
            forceAlmBtn.prop('title', cancelForcedAlarmStr);
          } else {
            forceAlmBtn.removeClass('disabled');
            forceAlmBtn.prop('title', forceAlarmStr);
          }
          forceAlmBtn.prop('disabled', false);
        } else {
          enableAlmBtn.removeClass('disabled');
          enableAlmBtn.prop('title', enableAlarmsStr);
          forceAlmBtn.prop('disabled', true);
        }
        enableAlmBtn.prop('disabled', false);
      } // end if canEditMonitors

      if ( streamStatus.auth ) {
        auth_hash = streamStatus.auth;
        // Try to reload the image stream.
        var streamImg = $('liveStream');
        if ( streamImg ) {
          streamImg.src = streamImg.src.replace(/auth=\w+/i, 'auth='+streamStatus.auth);
        }
        streamCmdParms = streamCmdParms.replace(/auth=\w+/i, 'auth='+streamStatus.auth);
        statusCmdParms = statusCmdParms.replace(/auth=\w+/i, 'auth='+streamStatus.auth);
        table.bootstrapTable('refresh');
        controlParms = controlParms.replace(/auth=\w+/i, 'auth='+streamStatus.auth);
      } // end if have a new auth hash
    } // end if respObj.status
  } else {
    checkStreamForErrors('getStreamCmdResponse', respObj);//log them
    // Try to reload the image stream.
    // If it's an auth error, we should reload the whole page.
    window.location.reload();
    if ( 0 ) {
      var streamImg = $('liveStream'+monitorId);
      if ( streamImg ) {
        streamImg.src = streamImg.src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
        console.log('Changing livestream src to ' + streamImg.src);
      } else {
        console.log('Unable to find streamImg liveStream');
      }
    }
  }

  var streamCmdTimeout = statusRefreshTimeout;
  if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT ) {
    streamCmdTimeout = streamCmdTimeout/5;
  }
  streamCmdTimer = streamCmdQuery.delay(streamCmdTimeout);
}

function streamCmdPause( action ) {
  setButtonState('pauseBtn', 'active');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if ( monitorStreamReplayBuffer ) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'inactive');
    setButtonState('slowRevBtn', 'inactive');
    setButtonState('fastRevBtn', 'inactive');
  }
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_PAUSE);
  }
}

function streamCmdPlay( action ) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'active');
  if ( streamStatus.delayed == true ) {
    setButtonState('stopBtn', 'inactive');
    if ( monitorStreamReplayBuffer ) {
      setButtonState('fastFwdBtn', 'inactive');
      setButtonState('slowFwdBtn', 'inactive');
      setButtonState('slowRevBtn', 'inactive');
      setButtonState('fastRevBtn', 'inactive');
    }
  } else {
    setButtonState('stopBtn', 'unavail');
    if ( monitorStreamReplayBuffer ) {
      setButtonState('fastFwdBtn', 'unavail');
      setButtonState('slowFwdBtn', 'unavail');
      setButtonState('slowRevBtn', 'unavail');
      setButtonState('fastRevBtn', 'unavail');
    }
  }
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_PLAY);
  }
}

function streamCmdStop( action ) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'unavail');
  setButtonState('stopBtn', 'active');
  if ( monitorStreamReplayBuffer ) {
    setButtonState('fastFwdBtn', 'unavail');
    setButtonState('slowFwdBtn', 'unavail');
    setButtonState('slowRevBtn', 'unavail');
    setButtonState('fastRevBtn', 'unavail');
  }
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_STOP);
  }
  setButtonState('stopBtn', 'unavail');
  setButtonState('playBtn', 'active');
}

function streamCmdFastFwd( action ) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if ( monitorStreamReplayBuffer ) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'inactive');
    setButtonState('slowRevBtn', 'inactive');
    setButtonState('fastRevBtn', 'inactive');
  }
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_FASTFWD);
  }
}

function streamCmdSlowFwd( action ) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if ( monitorStreamReplayBuffer ) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'active');
    setButtonState('slowRevBtn', 'inactive');
    setButtonState('fastRevBtn', 'inactive');
  }
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_SLOWFWD);
  }
  setButtonState('pauseBtn', 'active');
  if ( monitorStreamReplayBuffer ) {
    setButtonState('slowFwdBtn', 'inactive');
  }
}

function streamCmdSlowRev( action ) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if ( monitorStreamReplayBuffer ) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'inactive');
    setButtonState('slowRevBtn', 'active');
    setButtonState('fastRevBtn', 'inactive');
  }
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_SLOWREV);
  }
  setButtonState('pauseBtn', 'active');
  if ( monitorStreamReplayBuffer ) {
    setButtonState('slowRevBtn', 'inactive');
  }
}

function streamCmdFastRev( action ) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if ( monitorStreamReplayBuffer ) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'inactive');
    setButtonState('slowRevBtn', 'inactive');
    setButtonState('fastRevBtn', 'inactive');
  }
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_FASTREV);
  }
}

function streamCmdZoomIn( x, y ) {
  streamCmdReq.send(streamCmdParms+"&command="+CMD_ZOOMIN+"&x="+x+"&y="+y);
}

function streamCmdZoomOut() {
  streamCmdReq.send(streamCmdParms+"&command="+CMD_ZOOMOUT);
}

function streamCmdScale( scale ) {
  streamCmdReq.send(streamCmdParms+"&command="+CMD_SCALE+"&scale="+scale);
}

function streamCmdPan( x, y ) {
  streamCmdReq.send(streamCmdParms+"&command="+CMD_PAN+"&x="+x+"&y="+y);
}

function streamCmdQuery() {
  streamCmdReq.send(streamCmdParms+"&command="+CMD_QUERY);
}

if ( monitorType != 'WebSite' ) {
  var statusCmdParms = "view=request&request=status&entity=monitor&id="+monitorId+"&element[]=Status&element[]=FrameRate";
  if ( auth_hash ) {
    statusCmdParms += '&auth='+auth_hash;
  }
  var statusCmdReq = new Request.JSON( {
    url: monitorUrl,
    method: 'get',
    timeout: AJAX_TIMEOUT,
    link: 'cancel',
    onSuccess: getStatusCmdResponse
  } );
  var statusCmdTimer = null;
}

function getStatusCmdResponse(respObj, respText) {
  watchdogOk('status');
  if ( statusCmdTimer ) {
    statusCmdTimer = clearTimeout(statusCmdTimer);
  }

  if ( respObj.result == 'Ok' ) {
    $('fpsValue').set('text', respObj.monitor.FrameRate);
    setAlarmState(respObj.monitor.Status);
  } else {
    checkStreamForErrors('getStatusCmdResponse', respObj);
  }

  var statusCmdTimeout = statusRefreshTimeout;
  if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT ) {
    statusCmdTimeout = statusCmdTimeout/5;
  }
  statusCmdTimer = statusCmdQuery.delay(statusCmdTimeout);
}

function statusCmdQuery() {
  statusCmdReq.send(statusCmdParms);
}

if ( monitorType != 'WebSite' ) {
  var alarmCmdParms = 'view=request&request=alarm&id='+monitorId;
  if ( auth_hash ) {
    alarmCmdParms += '&auth='+auth_hash;
  }
  var alarmCmdReq = new Request.JSON( {
    url: monitorUrl,
    method: 'get',
    timeout: AJAX_TIMEOUT,
    link: 'cancel',
    onSuccess: getAlarmCmdResponse,
    onTimeout: streamCmdQuery
  } );
  var alarmCmdFirst = true;
}

function getAlarmCmdResponse(respObj, respText) {
  checkStreamForErrors('getAlarmCmdResponse', respObj);
}

function cmdDisableAlarms() {
  alarmCmdReq.send(alarmCmdParms+"&command=disableAlarms");
}

function cmdEnableAlarms() {
  alarmCmdReq.send(alarmCmdParms+"&command=enableAlarms");
}

function cmdAlarm() {
  if ( enableAlmBtn.hasClass('disabled') ) {
    cmdEnableAlarms();
  } else {
    cmdDisableAlarms();
  }
}

function cmdForceAlarm() {
  alarmCmdReq.send(alarmCmdParms+"&command=forceAlarm");
  if ( window.event ) {
    window.event.preventDefault();
  }
}

function cmdCancelForcedAlarm() {
  alarmCmdReq.send(alarmCmdParms+"&command=cancelForcedAlarm");
  if ( window.event ) {
    window.event.preventDefault();
  }
  return false;
}

function cmdForce() {
  if ( forceAlmBtn.hasClass('disabled') ) {
    cmdCancelForcedAlarm();
  } else {
    cmdForceAlarm();
  }
}

if ( monitorType != 'WebSite' ) {
  var controlParms = 'view=request&request=control&id='+monitorId;
  if ( auth_hash ) {
    controlParms += '&auth='+auth_hash;
  }
  var controlReq = new Request.JSON( {
    url: monitorUrl,
    method: 'post',
    timeout: AJAX_TIMEOUT,
    link: 'cancel',
    onSuccess: getControlResponse
  } );
}

function getControlResponse(respObj, respText) {
  if ( !respObj ) {
    return;
  }
  //console.log( respText );
  if ( respObj.result != 'Ok' ) {
    alert("Control response was status = "+respObj.status+"\nmessage = "+respObj.message);
  }
}

function controlCmd(event) {
  button = event.target;
  control = button.getAttribute('value');
  xtell = button.getAttribute('data-xtell');
  ytell = button.getAttribute('data-ytell');

  var locParms = '';
  if ( event && (xtell || ytell) ) {
    var target = event.target;
    var coords = $(target).getCoordinates();

    var x = event.pageX - coords.left;
    var y = event.pageY - coords.top;

    if ( xtell ) {
      var xge = parseInt((x*100)/coords.width);
      if ( xtell == -1 ) {
        xge = 100 - xge;
      } else if ( xtell == 2 ) {
        xge = 2*(50 - xge);
      }
      locParms += '&xge='+xge;
    }
    if ( ytell ) {
      var yge = parseInt((y*100)/coords.height);
      if ( ytell == -1 ) {
        yge = 100 - yge;
      } else if ( ytell == 2 ) {
        yge = 2*(50 - yge);
      }
      locParms += '&yge='+yge;
    }
  }
  controlReq.send(controlParms+"&control="+control+locParms);
  if ( streamMode == 'single' ) {
    fetchImage.pass($('imageFeed').getElement('img')).delay(1000);
  }
}

function controlCmdImage( x, y ) {
  var imageControlParms = controlParms;
  imageControlParms += '&scale='+scale;
  imageControlParms += '&control='+imageControlMode;

  controlReq.send( imageControlParms+"&x="+x+"&y="+y );
  if ( streamMode == 'single' ) {
    fetchImage.pass( $('imageFeed').getElement('img') ).delay( 1000 );
  }
}

function fetchImage( streamImage ) {
  streamImage.src = streamImage.src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
}

function handleClick( event ) {
  var $target = $(event.target);
  var scaleX = parseInt(monitorWidth / $target.getWidth());
  var scaleY = parseInt(monitorHeight / $target.getHeight());
  var x = (event.page.x - $target.getLeft()) * scaleX;
  var y = (event.page.y - $target.getTop()) * scaleY;

  if ( showMode == 'events' || !imageControlMode ) {
    if ( event.shift ) {
      streamCmdPan( x, y );
    } else if ( event.event.ctrlKey ) {
      streamCmdZoomOut();
    } else {
      streamCmdZoomIn(x, y);
    }
  } else {
    controlCmdImage(x, y);
  }
}

function appletRefresh() {
  if ( streamStatus && (!streamStatus.paused && !streamStatus.delayed) ) {
    var streamImg = $('liveStream'+monitorId);
    if ( streamImg ) {
      var parent = streamImg.getParent();
      streamImg.dispose();
      streamImg.inject( parent );
    } else {
      console.error("Nothing found for liveStream"+monitorId);
    }
    if ( appletRefreshTime ) {
      appletRefresh.delay( appletRefreshTime*1000 );
    }
  } else {
    appletRefresh.delay( 15*1000 ); //if we are paused or delayed check every 15 seconds if we are live yet...
  }
}

var watchdogInactive = {
  'stream': false,
  'status': false
};

var watchdogFunctions = {
  'stream': streamCmdQuery,
  'status': statusCmdQuery,
};

//Make sure the various refreshes are still taking effect
function watchdogCheck( type ) {
  if ( watchdogInactive[type] ) {
    console.log("Detected streamWatch of type: " + type + " stopped, restarting");
    watchdogFunctions[type]();
    watchdogInactive[type] = false;
  } else {
    watchdogInactive[type] = true;
  }
}

function watchdogOk( type ) {
  watchdogInactive[type] = false;
}

function reloadWebSite() {
  document.getElementById('imageFeed').innerHTML = document.getElementById('imageFeed').innerHTML;
}

function updatePresetLabels() {
  var form = $('ctrlPresetForm');
  var preset_ddm = form.elements['preset'];

  var presetIndex = preset_ddm[preset_ddm.selectedIndex].value;
  if ( labels[presetIndex] ) {
    form.newLabel.value = labels[presetIndex];
  } else {
    form.newLabel.value = '';
  }
}

function getCtrlPresetModal() {
  $j.getJSON(thisUrl + '?request=modal&modal=controlpreset&mid=' + monitorId)
      .done(function(data) {
        insertModalHtml('ctrlPresetModal', data.html);
        updatePresetLabels();
        // Manage the Preset Select box
        $j('#preset').change(updatePresetLabels);
        // Manage the Save button
        $j('#cPresetSubmitModal').click(function(evt) {
          evt.preventDefault();
          $j('#ctrlPresetForm').submit();
        });
      })
      .fail(logAjaxFail);
}

function getSettingsModal() {
  $j.getJSON(thisUrl + '?request=modal&modal=settings&mid=' + monitorId)
      .done(function(data) {
        insertModalHtml('settingsModal', data.html);
        // Manage the Save button
        $j('#settingsSubmitModal').click(function(evt) {
          evt.preventDefault();
          $j('#settingsForm').submit();
        });
      })
      .fail(logAjaxFail);
}

function processClicks(event, field, value, row, $element) {
  if ( field == 'Delete' ) {
    $j.getJSON(thisUrl + '?request=modal&modal=delconfirm')
        .done(function(data) {
          insertModalHtml('deleteConfirm', data.html);
          manageDelConfirmModalBtns();
          $j('#deleteConfirm').data('eid', row.Id.replace(/(<([^>]+)>)/gi, ''));
          $j('#deleteConfirm').modal('show');
        })
        .fail(logAjaxFail);
  }
}

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById("delConfirmBtn").addEventListener("click", function onDelConfirmClick(evt) {
    if ( ! canEditEvents ) {
      enoperm();
      return;
    }

    var eid = $j('#deleteConfirm').data('eid');

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=delete&eids[]='+eid)
        .done( function(data) {
          table.bootstrapTable('refresh');
          $j('#deleteConfirm').modal('hide');
        })
        .fail(logAjaxFail);
  });

  // Manage the CANCEL modal button
  document.getElementById("delCancelBtn").addEventListener("click", function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

function initPage() {
  if ( canViewControl ) {
    // Load the PTZ Preset modal into the DOM
    if ( monitorControllable ) getCtrlPresetModal();
    // Load the settings modal into the DOM
    if ( monitorType == "Local" ) getSettingsModal();
  }

  if ( monitorType != 'WebSite' ) {
    if ( streamMode == 'single' ) {
      statusCmdTimer = statusCmdQuery.delay( (Math.random()+0.1)*statusRefreshTimeout );
      watchdogCheck.pass('status').periodical(statusRefreshTimeout*2);
    } else {
      streamCmdTimer = streamCmdQuery.delay( (Math.random()+0.1)*statusRefreshTimeout );
      watchdogCheck.pass('stream').periodical(statusRefreshTimeout*2);
    }

    if ( canStreamNative || (streamMode == 'single') ) {
      var streamImg = $('imageFeed').getElement('img');
      if ( !streamImg ) {
        streamImg = $('imageFeed').getElement('object');
      }
      if ( !streamImg ) {
        console.error('No streamImg found for imageFeed');
      } else {
        if ( streamMode == 'single' ) {
          streamImg.addEvent('click', fetchImage.pass(streamImg));
          fetchImage.pass(streamImg).periodical(imageRefreshTimeout);
        } else {
          streamImg.addEvent('click', function(event) {
            handleClick(event);
          });
        }
      } // end if have streamImg
    } // streamMode native or single

    if ( refreshApplet && appletRefreshTime ) {
      appletRefresh.delay(appletRefreshTime*1000);
    }
    if ( scale == '0' || scale == 'auto' ) changeScale();
    if ( window.history.length == 1 ) {
      $j('#closeControl').html('');
    }
    document.querySelectorAll('select[name="scale"]').forEach(function(el) {
      el.onchange = window['changeScale'];
    });
  } else if ( monitorRefresh > 0 ) {
    setInterval(reloadWebSite, monitorRefresh*1000);
  }

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Manage the SETTINGS button
  document.getElementById("settingsBtn").addEventListener("click", function onSettingsClick(evt) {
    evt.preventDefault();
    $j('#settingsModal').modal('show');
  });

  // Only enable the settings button for local cameras
  settingsBtn.prop('disabled', !canViewControl);
  if ( monitorType != 'Local' ) settingsBtn.hide();

  // Init the bootstrap-table
  if ( monitorType != 'WebSite' ) table.bootstrapTable({icons: icons});

  // Update table rows each time after new data is loaded
  table.on('post-body.bs.table', function(data) {
    $j('#eventList tr:contains("New Event")').addClass('recent');
  });

  // Take appropriate action when the user clicks on a cell
  table.on('click-cell.bs.table', processClicks);

  // Some toolbar events break the thumbnail animation, so re-init eventlistener
  table.on('all.bs.table', initThumbAnimation);

  // Update table links each time after new data is loaded
  table.on('post-body.bs.table', function(data) {
    var thumb_ndx = $j('#eventList tr th').filter(function() {
      return $j(this).text().trim() == 'Thumbnail';
    }).index();
    var thmbClass = ANIMATE_THUMBS ? 'colThumbnail zoom' : 'colThumbnail';
    table.find("tr td:nth-child(" + (thumb_ndx+1) + ")").addClass(thmbClass);
  });
} // initPage

// Kick everything off
$j(document).ready(initPage);
