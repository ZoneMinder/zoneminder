var backBtn = $j('#backBtn');
var settingsBtn = $j('#settingsBtn');
var enableAlmBtn = $j('#enableAlmBtn');
var forceAlmBtn = $j('#forceAlmBtn');
var table = $j('#eventList');
var sidebarView = $j('#sidebar');
var sidebarControls = $j('#ptzControls');
var wrapperMonitor = $j('#wrapperMonitor');
var filterQuery = '&filter[Query][terms][0][attr]=MonitorId&filter[Query][terms][0][op]=%3d&filter[Query][terms][0][val]='+monitorId;
var idle = 0;

var classSidebarL = 'col-sm-3'; /* id="sidebar" */
var classSidebarR = 'col-sm-2'; /* id="ptzControls" */
var classMonitorW_SB_LR = 'col-sm-7'; /* id="wrapperMonitor" MINIMUM width */
var classMonitorW_SB_L = 'col-sm-9'; /* id="wrapperMonitor" ONLY WITH LEFT */
var classMonitorW_SB_R = 'col-sm-10'; /* id="wrapperMonitor" ONLY WITH RIGHT */
var classMonitorWO_SB = 'col-sm-12'; /* id="wrapperMonitor" MAXIMUM width */

var PrevCoordinatFrame = {x: null, y: null};
var coordinateMouse = {
  start_x: null, start_y: null,
  shiftMouse_x: null, shiftMouse_y: null,
  shiftMouseForTrigger_x: null, shiftMouseForTrigger_y: null
};
var leftBtnStatus = {Down: false, UpAfterDown: false};
var updateScale = false; //Scale needs to be updated
var TimerHideShow;

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
  const data = params.data;
  data.order = 'desc';
  data.limit = maxDisplayEvents;
  data.sort = 'Id';
  data.view = 'request';
  data.request = 'watch';
  data.mid = monitorId;
  if (auth_hash) data.auth = auth_hash;

  $j.getJSON(thisUrl, data)
      .done(function(data) {
        const rows = processRows(data.rows);
        params.success(rows);
      })
      .fail(logAjaxFail);
}

function processRows(rows) {
  $j.each(rows, function(ndx, row) {
    const eid = row.Id;

    row.Delete = '<i class="fa fa-trash text-danger"></i>';
    row.Id = '<a href="?view=event&amp;eid=' + eid + filterQuery + '">' + eid + '</a>';
    row.Name = '<a href="?view=event&amp;eid=' + eid + filterQuery + '">' + row.Name + '</a>';
    row.Frames = '<a href="?view=frames&amp;eid=' + eid + '">' + row.Frames + '</a>';
    row.AlarmFrames = '<a href="?view=frames&amp;eid=' + eid + '">' + row.AlarmFrames + '</a>';
    row.MaxScore = '<a href="?view=frame&amp;eid=' + eid + '&amp;fid=0">' + row.MaxScore + '</a>';
    if ( LIST_THUMBS ) row.Thumbnail = '<a href="?view=event&amp;eid=' + eid + filterQuery + '&amp;page=1">' + row.imgHtml + '</a>';
    if ( row.Notes.indexOf('detected:') >= 0 ) {
      row.Notes = '<a href="#" class="objDetectLink" data-eid=' +eid+ '><div class="small text-muted">' + row.Notes + '</div></a>';
    } else if ( row.Notes != 'Forced Web: ' ) {
      row.Notes = '<div class="small text-muted">' + row.Notes + '</div>';
    }
    if (ZM_DATETIME_FORMAT_PATTERN) {
      if (window.DateTime) {
        row.StartDateTime = DateTime.fromSQL(row.StartDateTime)
        //.setZone(ZM_TIMEZONE)
            .toFormat(ZM_DATETIME_FORMAT_PATTERN);
        if (row.EndDateTime) {
          row.EndDateTime = DateTime.fromSQL(row.EndDateTime)
          //.setZone(ZM_TIMEZONE)
              .toFormat(ZM_DATETIME_FORMAT_PATTERN);
        }
      } else {
        console.log("DateTime is not defined");
      }
    } else {
      console.log("No ZM_DATETIME_FORMAT_PATTERN");
    }
  });

  return rows;
}

function showEvents() {
  $j('#ptzControls').addClass('hidden');
  $j('#events').removeClass('hidden');
  if ($j('#eventsControl')) {
    $j('#eventsControl').addClass('hidden');
  }
  if ($j('#controlControl')) {
    $j('#controlControl').removeClass('hidden');
  }
  showMode = 'events';
}

function showPtzControls() {
  $j('#events').addClass('hidden');
  $j('#ptzControls').removeClass('hidden');
  if ($j('#eventsControl')) {
    $j('#eventsControl').removeClass('hidden');
  }
  if ($j('#controlControl')) {
    $j('#controlControl').addClass('hidden');
  }
  showMode = 'control';
}

function changeSize() {
  var width = $j('#width').val();
  var height = $j('#height').val();

  //monitorStream.setScale('0', width, height);
  monitorsSetScale(monitorId);
  //$j('#scale').val('0');
  $j('#sidebar ul').height($j('#wrapperMonitor').height()-$j('#cycleButtons').height());

  //setCookie('zmWatchScale', '0');
  setCookie('zmWatchWidth', width);
  setCookie('zmWatchHeight', height);
} // end function changeSize()

function changeScale() {
  const scale = $j('#scale').val();
  setCookie('zmWatchScaleNew'+monitorId, scale);
  setCookie('zmCycleScale', scale);
  monitorsSetScale(monitorId);
/*
  const scale = $j('#scale').val();
  setCookie('zmWatchScale'+monitorId, scale);
  $j('#width').val('auto');
  $j('#height').val('auto');
  setCookie('zmCycleScale', scale);
  setCookie('zmWatchWidth', 'auto');
  setCookie('zmWatchHeight', 'auto');

  setScale();
*/
}

function changeStreamQuality() {
  const streamQuality = $j('#streamQuality').val();
  setCookie('zmStreamQuality', streamQuality);
  monitorsSetScale(monitorId);
}

// Implement current scale, as opposed to changing it
function setScale() {
/*
  const scale = $j('#scale').val();
  //monitorStream.setScale(scale, $j('#width').val(), $j('#height').val());
  monitorsSetScale(monitorId);
  // Always turn it off, we will re-add it below. I don't know if you can add a callback multiple
  // times and what the consequences would be
  $j(window).off('resize', endOfResize); //remove resize handler when Scale to Fit is not active
  if (scale == '0') {
    $j(window).on('resize', endOfResize); //remove resize handler when Scale to Fit is not active
    changeSize();
  }
*/
} // end function changeScale

function getStreamCmdResponse(respObj, respText) {
  watchdogOk('stream');
  streamCmdTimer = clearTimeout(streamCmdTimer);
  if (respObj.result == 'Ok') {
    // The get status command can get backed up, in which case we won't be able to get the semaphore and will exit.
    if (respObj.status) {
      const streamStatus = respObj.status;
      if ($j('#viewingFPSValue').text() != streamStatus.fps) {
        $j('#viewingFPSValue').text(streamStatus.fps);
      }
      if ($j('#captureFPSValue').text() != streamStatus.capturefps) {
        $j('#captureFPSValue').text(streamStatus.capturefps);
      }
      if ($j('#analysisFPSValue').text() != streamStatus.analysisfps) {
        $j('#analysisFPSValue').text(streamStatus.analysisfps);
      }

      setAlarmState(streamStatus.state);

      $j('#levelValue').text(streamStatus.level);
      var newClass = 'ok';
      if (streamStatus.level > 95) {
        newClass = 'alarm';
      } else if (streamStatus.level > 80) {
        newClass = 'alert';
      }
      $j('#levelValue').removeClass();
      $j('#levelValue').addClass(newClass);

      var delayString = secsToTime(streamStatus.delay);

      if (streamStatus.paused == true) {
        $j('#modeValue').text('Paused');
        $j('#rate').addClass('hidden');
        $j('#delayValue').text(delayString);
        $j('#delay').removeClass('hidden');
        $j('#level').removeClass('hidden');
        streamCmdPause(false);
      } else if (streamStatus.delayed == true) {
        $j('#modeValue').text('Replay');
        $j('#rateValue').text(streamStatus.rate);
        $j('#rate').removeClass('hidden');
        $j('#delayValue').text(delayString);
        $j('#delay').removeClass('hidden');
        $j('#level').removeClass('hidden');
        if (streamStatus.rate == 1) {
          streamCmdPlay(false);
        } else if (streamStatus.rate > 0) {
          if (streamStatus.rate < 1) {
            streamCmdSlowFwd(false);
          } else {
            streamCmdFastFwd(false);
          }
        } else {
          if (streamStatus.rate > -1) {
            streamCmdSlowRev(false);
          } else {
            streamCmdFastRev(false);
          }
        } // rate
      } else {
        $j('#modeValue').text('Live');
        $j('#rate').addClass('hidden');
        $j('#delay').addClass('hidden');
        $j('#level').addClass('hidden');
        streamCmdPlay(false);
      } // end if paused or delayed

      $j('#zoomValue').text(streamStatus.zoom);
      if (streamStatus.zoom == '1.0') {
        setButtonState('zoomOutBtn', 'unavail');
      } else {
        setButtonState('zoomOutBtn', 'inactive');
      }

      if (canEdit.Monitors) {
        if (streamStatus.enabled) {
          enableAlmBtn.addClass('disabled');
          enableAlmBtn.prop('title', disableAlarmsStr);
          if (streamStatus.forced) {
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
      } // end if canEdit.Monitors

      if (streamStatus.auth) {
        auth_hash = streamStatus.auth;
        // Try to reload the image stream.
        var streamImg = $j('#liveStream'+monitorId);
        if (streamImg) {
          const oldSrc = streamImg.attr('src');
          if (oldSrc) {
            const newSrc = oldSrc.replace(/auth=\w+/i, 'auth='+streamStatus.auth);
            if (oldSrc != newSrc) {
              streamImg.attr('src', ''); // Required or chrome doesn't stop the stream
              streamImg.attr('src', newSrc);
              table.bootstrapTable('refresh');
            }
          }
        }
      } // end if have a new auth hash
    } // end if respObj.status
  } else {
    console.log("Not ok");
    checkStreamForErrors('getStreamCmdResponse', respObj);//log them
    setTimeout(fetchImage, 1000, $j('#imageFeed img')[0]);
  }

  var streamCmdTimeout = statusRefreshTimeout;
  if (alarmState == STATE_ALARM || alarmState == STATE_ALERT) {
    streamCmdTimeout = streamCmdTimeout/5;
  }
  streamCmdTimer = setTimeout(streamCmdQuery, streamCmdTimeout);
}

function streamCmdQuery() {
  streamCmdReq({command: CMD_QUERY});
}

function onPause() {
  setButtonState('pauseBtn', 'active');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if (monitorStreamReplayBuffer) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'inactive');
    setButtonState('slowRevBtn', 'inactive');
    setButtonState('fastRevBtn', 'inactive');
  }
}

function streamCmdPause(action) {
  onPause();
  if (action) {
    monitorStream.streamCommand(CMD_PAUSE);
  }
}

function onPlay() {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'active');
  if (monitorStream.status.delayed == true) {
    setButtonState('stopBtn', 'inactive');
    if (monitorStreamReplayBuffer) {
      setButtonState('fastFwdBtn', 'inactive');
      setButtonState('slowFwdBtn', 'inactive');
      setButtonState('slowRevBtn', 'inactive');
      setButtonState('fastRevBtn', 'inactive');
    }
  } else {
    setButtonState('stopBtn', 'unavail');
    if (monitorStreamReplayBuffer) {
      setButtonState('fastFwdBtn', 'unavail');
      setButtonState('slowFwdBtn', 'unavail');
      setButtonState('slowRevBtn', 'unavail');
      setButtonState('fastRevBtn', 'unavail');
    }
  }
}

function streamCmdPlay(action) {
  onPlay();
  if (action) {
    monitorStream.streamCommand(CMD_PLAY);
  }
}

function streamCmdStop(action) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'unavail');
  setButtonState('stopBtn', 'active');
  if (monitorStreamReplayBuffer) {
    setButtonState('fastFwdBtn', 'unavail');
    setButtonState('slowFwdBtn', 'unavail');
    setButtonState('slowRevBtn', 'unavail');
    setButtonState('fastRevBtn', 'unavail');
  }
  if (action) {
    monitorStream.streamCommand(CMD_STOP);
  }
  setButtonState('stopBtn', 'unavail');
  setButtonState('playBtn', 'active');
}

function streamCmdFastFwd(action) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if (monitorStreamReplayBuffer) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'inactive');
    setButtonState('slowRevBtn', 'inactive');
    setButtonState('fastRevBtn', 'inactive');
  }
  if (action) {
    monitorStream.streamCommand(CMD_FASTFWD);
  }
}

function streamCmdSlowFwd(action) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if (monitorStreamReplayBuffer) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'active');
    setButtonState('slowRevBtn', 'inactive');
    setButtonState('fastRevBtn', 'inactive');
  }
  if (action) {
    monitorStream.command(CMD_SLOWFWD);
  }
  setButtonState('pauseBtn', 'active');
  if (monitorStreamReplayBuffer) {
    setButtonState('slowFwdBtn', 'inactive');
  }
}

function streamCmdSlowRev(action) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if (monitorStreamReplayBuffer) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'inactive');
    setButtonState('slowRevBtn', 'active');
    setButtonState('fastRevBtn', 'inactive');
  }
  if (action) {
    monitorStream.command(CMD_SLOWREV);
  }
  setButtonState('pauseBtn', 'active');
  if (monitorStreamReplayBuffer) {
    setButtonState('slowRevBtn', 'inactive');
  }
}

function streamCmdFastRev(action) {
  setButtonState('pauseBtn', 'inactive');
  setButtonState('playBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  if (monitorStreamReplayBuffer) {
    setButtonState('fastFwdBtn', 'inactive');
    setButtonState('slowFwdBtn', 'inactive');
    setButtonState('slowRevBtn', 'inactive');
    setButtonState('fastRevBtn', 'inactive');
  }
  if (action) {
    monitorStream.command(CMD_FASTREV);
  }
}

function streamCmdZoomIn(x, y) {
  monitorStream.streamCommand({x: x, y: y, command: CMD_ZOOMIN});
}

function streamCmdZoomOut() {
  monitorStream.streamCommand(CMD_ZOOMOUT);
}
function streamCmdZoomStop() {
  monitorStream.streamCommand(CMD_ZOOMSTOP);
}

function streamCmdScale(scale) {
  monitorStream.streamCommand({command: CMD_SCALE, scale: scale});
}

function streamCmdPan(x, y) {
  monitorStream.streamCommand({x: x, y: y, command: CMD_PAN});
}


/* getStatusCmd is used when not streaming, since there is no persistent zms */
function getStatusCmdResponse(respObj, respText) {
  watchdogOk('status');
  statusCmdTimer = clearTimeout(statusCmdTimer);

  if (respObj.result == 'Ok') {
    $j('#captureFPSValue').text(respObj.monitor.FrameRate);
    setAlarmState(respObj.monitor.Status);
  } else {
    checkStreamForErrors('getStatusCmdResponse', respObj);
  }

  var statusCmdTimeout = statusRefreshTimeout;
  if (alarmState == STATE_ALARM || alarmState == STATE_ALERT) {
    statusCmdTimeout = statusCmdTimeout/5;
  }
  statusCmdTimer = setTimeout(statusCmdQuery, statusCmdTimeout);
}

function statusCmdQuery() {
  $j.getJSON(monitorUrl + '?view=request&request=status&entity=monitor&element[]=Status&element[]=FrameRate&id='+monitorId+'&'+auth_relay)
      .done(getStatusCmdResponse)
      .fail(logAjaxFail);

  statusCmdTimer = null;
}

function cmdDisableAlarms() {
  monitorStream.alarmCommand('disableAlarms');
}

function cmdEnableAlarms() {
  monitorStream.alarmCommand('enableAlarms');
}

function cmdAlarm() {
  if (enableAlmBtn.hasClass('disabled')) {
    cmdEnableAlarms();
  } else {
    cmdDisableAlarms();
  }
}

function cmdForceAlarm() {
  monitorStream.alarmCommand('forceAlarm');
  if (window.event) window.event.preventDefault();
  return false;
}

function cmdCancelForcedAlarm() {
  monitorStream.alarmCommand('cancelForcedAlarm');
  if (window.event) window.event.preventDefault();
  return false;
}

function cmdForce() {
  if (forceAlmBtn.hasClass('disabled')) {
    cmdCancelForcedAlarm();
  } else {
    cmdForceAlarm();
  }
}

function controlReq(data) {
  if (auth_hash) data.auth = auth_hash;
  $j.getJSON(monitorUrl + '?view=request&request=control&id='+monitorId, data)
      .done(getControlResponse)
      .fail(logAjaxFail);
}

function getControlResponse(respObj, respText) {
  if (!respObj) {
    return;
  }
  //console.log( respText );
  if (respObj.result != 'Ok') {
    alert("Control response was status = "+respObj.status+"\nmessage = "+respObj.message);
  }
}

function controlCmd(event) {
  button = event.target;

  console.log(event);
  if (event.type !='mouseup') {
    control = button.getAttribute('value');
  } else {
    control = 'moveStop';
  }
  xtell = button.getAttribute('data-xtell');
  ytell = button.getAttribute('data-ytell');

  var data = {};

  if (event && (xtell || ytell)) {
    const target = event.target;
    const offset = $j(target).offset();
    const width = $j(target).width();
    const height = $j(target).height();

    const x = event.pageX - offset.left;
    const y = event.pageY - offset.top;

    if (xtell) {
      let xge = parseInt((x*100)/width);
      if (xtell == -1) {
        xge = 100 - xge;
      } else if (xtell == 2) {
        xge = 2*(50 - xge);
      }
      data.xge = xge;
    }
    if (ytell) {
      let yge = parseInt((y*100)/height);
      if (ytell == -1) {
        yge = 100 - yge;
      } else if (ytell == 2) {
        yge = 2*(50 - yge);
      }
      data.yge = yge;
    }
  }

  data.control = control;
  controlReq(data);

  if (streamMode == 'single') {
    setTimeout(fetchImage, 1000, $j('#imageFeed img')[0]);
  }
}

function controlCmdImage(x, y) {
  const data = {};
  data.scale = scale;
  data.control = imageControlMode;
  data.x = x;
  data.y = y;
  controlReq(data);

  if (streamMode == 'single') {
    setTimeout(fetchImage, 1000, $j('#imageFeed img')[0]);
  }
}

function fetchImage(streamImage) {
  const oldsrc = streamImage.src;
  const newsrc = oldsrc.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
  streamImage.src = '';
  streamImage.src = newsrc;
}

function handleClick(event) {
  if (panZoomEnabled) {
    //event.preventDefault();
    if (event.target.id) {
    //We are looking for an object with an ID, because there may be another element in the button.
      var obj = event.target;
    } else {
      var obj = event.target.parentElement;
    }
    if (obj.className.includes('btn-zoom-out') || obj.className.includes('btn-zoom-in')) return;

    if (obj.className.includes('btn-edit-monitor')) {
      const url = '?view=monitor&mid='+monitorId;
      if (event.ctrlKey) {
        window.open(url, '_blank');
      } else {
        window.location.assign(url);
      }
    }

    if (obj.getAttribute('id').indexOf("liveStream") >= 0) {
      zmPanZoom.click(monitorId);
    }
  } else {
    // +++ Old ZoomPan algorithm.
    if (!(event.ctrlKey && (event.shift || event.shiftKey))) {
    // target should be the img tag
      const target = $j(event.target);
      const width = target.width();
      const height = target.height();

      const scaleX = parseFloat(monitorWidth / width);
      const scaleY = parseFloat(monitorHeight / height);
      const pos = target.offset();
      const x = parseInt((event.pageX - pos.left) * scaleX);
      const y = parseInt((event.pageY - pos.top) * scaleY);

      if (showMode == 'events' || !imageControlMode) {
        if (event.shift || event.shiftKey) {
          streamCmdPan(x, y);
          updatePrevCoordinatFrame(x, y); //Fixing current coordinates after scaling or shifting
        } else if (event.ctrlKey) {
          streamCmdZoomOut();
        } else {
          streamCmdZoomIn(x, y);
          updatePrevCoordinatFrame(x, y); //Fixing current coordinates after scaling or shifting
        }
      } else {
        controlCmdImage(x, y);
      }
    }
    // --- Old ZoomPan algorithm.
  }
}

function shiftImgFrame() { //We calculate the coordinates of the image displacement and shift the image
  let newPosX = parseInt(PrevCoordinatFrame.x - coordinateMouse.shiftMouse_x);
  let newPosY = parseInt(PrevCoordinatFrame.y - coordinateMouse.shiftMouse_y);

  if (newPosX < 0) newPosX = 0;
  if (newPosX > monitorWidth) newPosX = monitorWidth;
  if (newPosY < 0) newPosY = 0;
  if (newPosY > monitorHeight) newPosY = monitorHeight;

  streamCmdPan(newPosX, newPosY);
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

  const scaleX = parseFloat(monitorWidth / target.width());
  const scaleY = parseFloat(monitorHeight / target.height());
  const pos = target.offset();

  return {x: parseInt((event.pageX - pos.left) * scaleX), y: parseInt((event.pageY - pos.top) * scaleY)}; //The point of the mouse click relative to the dimensions of the real frame.
}

function handleMove(event) {
  if (panZoomEnabled) {
    return;
  }
  // +++ Old ZoomPan algorithm.
  if (event.ctrlKey && event.shiftKey) {
    document.ondragstart = function() {
      return false;
    }; //Allow drag and drop
  } else {
    document.ondragstart = function() {}; //Prevent drag and drop
    return false;
  }

  if (leftBtnStatus.Down) { //The left button was previously pressed and is now being held. Processing movement with a pressed button.
    var {x, y} = getCoordinateMouse(event);
    const k = Math.log(2.72) / Math.log(parseFloat($j('#zoomValue'+monitorId).html())) - 0.3; //Necessary for correctly shifting the image in accordance with the scaling proportions

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
}

function zoomOutClick(event) {
  if (event.ctrlKey) {
    streamCmdZoomStop();
  } else {
    streamCmdZoomOut();
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
function watchdogCheck(type) {
  if (watchdogInactive[type]) {
    console.log("Detected streamWatch of type: " + type + " stopped, restarting");
    watchdogFunctions[type]();
    watchdogInactive[type] = false;
  } else {
    watchdogInactive[type] = true;
  }
}

function watchdogOk(type) {
  watchdogInactive[type] = false;
}

function reloadWebSite() {
  document.getElementById('imageFeed').innerHTML = document.getElementById('imageFeed').innerHTML;
}

function updatePresetLabels() {
  var lblNdx = $j('#ctrlPresetForm option:selected').val();
  $j('#newLabel').val(labels[lblNdx]);
}


function changeControl(e) {
  const input = e.target;
  $j.getJSON(monitorUrl+'?request=v4l2_settings&mid='+monitorId+'&'+input.name+'='+input.value)
      .done(function(evt) {
        if (evt.result == 'Ok') {
          evt.controls.forEach(function(control) {
            const element = $j('#new'+control.control.charAt(0).toUpperCase() + control.control.slice(1));
            if (element.length) {
              element.val(control.value);
              element.attr('title', control.value);
            } else {
              console.err('Element not found for #new'+control.control.charAt(0).toUpperCase() + control.control.slice(1));
            }
          });
        }
      })
      .fail(logAjaxFail);
}

function getSettingsModal() {
  $j.getJSON(monitorUrl + '?request=modal&modal=settings&mid=' + monitorId)
      .done(function(data) {
        insertModalHtml('settingsModal', data.html);
        // Manage the Save button
        $j('#settingsSubmitModal').click(function(evt) {
          evt.preventDefault();
          $j('#settingsForm').submit();
        });
        $j('#newBrightness').change(changeControl);
        $j('#newContrast').change(changeControl);
        $j('#newHue').change(changeControl);
        $j('#newColour').change(changeControl);
      })
      .fail(logAjaxFail);
}

function processClicks(event, field, value, row, $element) {
  if (field == 'Delete') {
    if (window.event.shiftKey) {
      var eid = row.Id.replace(/(<([^>]+)>)/gi, '');
      $j.getJSON(thisUrl + '?request=events&task=delete&eids[]='+eid)
          .done(function(data) {
            table.bootstrapTable('refresh');
          })
          .fail(logAjaxFail);
    } else {
      $j.getJSON(monitorUrl + '?request=modal&modal=delconfirm')
          .done(function(data) {
            insertModalHtml('deleteConfirm', data.html);
            manageDelConfirmModalBtns();
            $j('#deleteConfirm').data('eid', row.Id.replace(/(<([^>]+)>)/gi, ''));
            $j('#deleteConfirm').modal('show');
          })
          .fail(logAjaxFail);
    }
  }
}

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById("delConfirmBtn").addEventListener("click", function onDelConfirmClick(evt) {
    if (!canEdit.Events) {
      enoperm();
      return;
    }

    var eid = $j('#deleteConfirm').data('eid');

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=delete&eids[]='+eid)
        .done(function(data) {
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

function refresh_events_table() {
  table.bootstrapTable('refresh');
}

function controlSetClicked() {
  console.log("Clicked");
  const modal = $j('#ctrlPresetModal');
  if (!modal.lenth) {
    console.log('loading');
    // Load the PTZ Preset modal into the DOM
    $j.getJSON(monitorUrl + '?request=modal&modal=controlpreset&mid=' + monitorId+'&'+auth_relay)
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
          $j('#ctrlPresetModal').modal('show');
        })
        .fail(logAjaxFail);
  } else {
    console.log('not loading');
    modal.modal('show');
  }
}

function streamStart() {
  monitorStream = new MonitorStream(monitorData[monIdx]);
  monitorStream.setBottomElement(document.getElementById('dvrControls'));

  // Start the fps and status updates. give a random delay so that we don't assault the server
  //monitorStream.setScale($j('#scale').val(), $j('#width').val(), $j('#height').val());
  monitorsSetScale(monitorId);
  monitorStream.start();
  if (streamMode == 'single') {
    monitorStream.setup_onclick(fetchImage);
  } else {
    monitorStream.setup_onclick(handleClick);
    monitorStream.setup_onmove(handleMove);
  }
  monitorStream.setup_onpause(onPause);
  monitorStream.setup_onplay(onPlay);
  monitorStream.setup_onalarm(refresh_events_table);

  monitorStream.setButton('enableAlarmButton', enableAlmBtn);
  monitorStream.setButton('forceAlarmButton', forceAlmBtn);
  monitorStream.setButton('zoomOutButton', $j('zoomOutBtn'));
  if (canEdit.Monitors) {
    // Will be enabled by streamStatus ajax
    enableAlmBtn.on('click', cmdAlarm);
    forceAlmBtn.on('click', cmdForce);
  } else {
    forceAlmBtn.prop('title', forceAlmBtn.prop('title') + ': disabled because cannot edit Monitors');
    enableAlmBtn.prop('title', enableAlmBtn.prop('title') + ': disabled because cannot edit Monitors');
  }

  /*
  if (streamMode == 'single') {
    statusCmdTimer = setTimeout(statusCmdQuery, 200);
    setInterval(watchdogCheck, statusRefreshTimeout*2, 'status');
  } else {
    streamCmdTimer = setTimeout(streamCmdQuery, 200);
    setInterval(watchdogCheck, statusRefreshTimeout*2, 'stream');
  }
  if (canStream || (streamMode == 'single')) {
    var streamImg = $j('#imageFeed img');
    if (!streamImg) streamImg = $j('#imageFeed object');
    if (!streamImg) {
      console.error('No streamImg found for imageFeed');
    } else {
      if (streamMode == 'single') {
        streamImg.click(streamImg, fetchImage);
        setInterval(fetchImage, imageRefreshTimeout, $j('#imageFeed img'));
      } else {
        streamImg.click(function(event) {
          handleClick(event);
        });
        streamImg.on("error", function(thing) {
          console.log("Error loading image");
          console.log(thing);
          setInterval(fetchImage, 100, $j('#imageFeed img'));
        });
      }
    } // end if have streamImg
  } // streamMode native or single
  */
}

function initPage() {
// +++ Support of old ZoomPan algorithm
  var useOldZoomPan = getCookie('zmUseOldZoomPan');
  const btnZoomOutBtn = document.getElementById('zoomOutBtn'); //Zoom out button below Frame. She may not
  if (useOldZoomPan) {
    panZoomEnabled = false;
    if (btnZoomOutBtn) {
      btnZoomOutBtn.classList.remove("hidden");
    }
  } else {
    if (btnZoomOutBtn) {
      btnZoomOutBtn.classList.add("hidden");
    }
  }
  $j("#use-old-zoom-pan").click(function() {
    useOldZoomPan = this.checked;
    setCookie('zmUseOldZoomPan', this.checked);
    location.reload();
  });
  document.getElementById('use-old-zoom-pan').checked = useOldZoomPan;
  // --- Support of old ZoomPan algorithm

  zmPanZoom.init();

  if (canView.Control) {
    // Load the settings modal into the DOM
    if (monitorType == 'Local') getSettingsModal();
  }
  // Only enable the settings button for local cameras
  if (!canView.Control) {
    settingsBtn.prop('disabled', true);
    settingsBtn.prop('title', 'Disbled due to lack of Control View permission.');
  } else if (monitorType != 'Local') {
    settingsBtn.prop('disabled', true);
    settingsBtn.prop('title', 'Settings only available for Local monitors.');
  } else {
    settingsBtn.prop('disabled', false);
  }

  if ((monitorType != 'WebSite') && monitorData.length) {
    streamStart();
    if (window.history.length == 1) {
      $j('#closeControl').html('');
    }
    document.querySelectorAll('select[name="scale"]').forEach(function(el) {
      el.onchange = window['changeScale'];
    });
    document.querySelectorAll('select[name="changeRate"]').forEach(function(el) {
      el.onchange = window['changeRate'].bind(el, el);
    });

    if (canView.Events) {
      // Init the bootstrap-table
      table.bootstrapTable({icons: icons});
      // Update table rows each time after new data is loaded
      table.on('post-body.bs.table', function(data) {
        $j('#eventList tr:contains("New Event")').addClass('recent');
        $j('.objDetectLink').click(function(evt) {
          evt.preventDefault();
          getObjdetectModal($j(this).data('eid'));
        });
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
        table.find("tr td:nth-child(" + (thumb_ndx+1) + ")").addClass('colThumbnail');
      });
    } // end if canView.Events
  } else if (monitorRefresh > 0) {
    setInterval(reloadWebSite, monitorRefresh*1000);
  }

  // Manage the BACK button
  bindButton('#backBtn', 'click', null, function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Manage the REFRESH Button
  bindButton('#refreshBtn', 'click', null, function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Manage the SETTINGS button
  bindButton('#settingsBtn', 'click', null, function onSettingsClick(evt) {
    evt.preventDefault();
    $j('#settingsModal').modal('show');
  });

  // Manage the generate Edit button
  bindButton('#editBtn', 'click', null, function onEditClick(evt) {
    evt.preventDefault();
    window.location.assign("?view=monitor&mid="+monitorId);
  });

  bindButton('#cyclePlayBtn', 'click', null, cycleStart);
  bindButton('#cyclePauseBtn', 'click', null, cyclePause);
  bindButton('#cycleNextBtn', 'click', null, cycleNext);
  bindButton('#cyclePrevBtn', 'click', null, cyclePrev);
  bindButton('#cycleToggle', 'click', null, cycleToggle);
  bindButton('#cyclePeriod', 'change', null, cyclePeriodChange);
  if (monitorData.length && cycle) {
    cycleStart();
  } else {
    cyclePause();
  }
  bindButton('#ptzToggle', 'click', null, ptzToggle);
  if (ZM_WEB_VIEWING_TIMEOUT > 0) {
    $j('body').on('mousemove', function() {
      idle = 0;
    });
    setInterval(function() {
      idle += 10;
    }, 10*1000);
    setInterval(function() {
      if (idle >= ZM_WEB_VIEWING_TIMEOUT) {
        streamCmdPause(true);
        let ayswModal = $j('#AYSWModal');
        if (!ayswModal.length) {
          $j.getJSON('?request=modal&modal=areyoustillwatching')
              .done(function(data) {
                ayswModal = insertModalHtml('AYSWModal', data.html);
                $j('#AYSWYesBtn').on('click', function() {
                  streamCmdPlay(true);
                  idle = 0;
                });
                ayswModal.modal('show');
              })
              .fail(logAjaxFail);
        } else {
          ayswModal.modal('show');
        }
      }
    }, 10*1000);
  }
  $j(".imageFeed").hover(
      //Displaying "Scale" and other buttons at the top of the monitor image
      function() {
        const id = stringToNumber(this.id);
        $j('#button_zoom' + id).stop(true, true).slideDown('fast');
      },
      function() {
        const id = stringToNumber(this.id);
        $j('#button_zoom' + id).stop(true, true).slideUp('fast');
      }
  );

  setInterval(() => {
    //Updating Scale. When quickly scrolling the mouse wheel or quickly pressing Zoom In/Out, you should not set Scale very often.
    if (updateScale) {
      monitorsSetScale(monitorId);
      updateScale = false;
    }
  }, 500);

  document.getElementById('monitor').classList.remove('hidden-shift');
  changeObjectClass();
  changeSize();
} // initPage

function watchFullscreen() {
  const btn = document.getElementById('fullscreenBtn');
  if (btn.firstElementChild.innerHTML=='fullscreen') {
    const content = document.getElementById('content');
    openFullscreen(content);
    btn.firstElementChild.innerHTML='fullscreen_exit';
    btn.setAttribute('title', translate["Exit Fullscreen"]);
  } else {
    closeFullscreen();
    btn.firstElementChild.innerHTML='fullscreen';
    btn.setAttribute('title', translate["Fullscreen"]);
  }
}

function watchAllEvents() {
  window.location.replace(document.getElementById('allEventsBtn').getAttribute('data-url'));
}

var intervalId;
var secondsToCycle = 0;

function nextCycleView() {
  secondsToCycle --;
  if (secondsToCycle<=0) {
    cycleNext();
  }
  $j('#secondsToCycle').text(secondsToCycle);
}

function cyclePause() {
  clearInterval(intervalId);
  cycle = false;
  $j('#cyclePauseBtn').hide();
  $j('#cyclePlayBtn').show();
}

function cycleStart() {
  secondsToCycle = $j('#cyclePeriod').val();
  intervalId = setInterval(nextCycleView, 1000);
  cycle = true;
  $j('#cyclePauseBtn').show();
  $j('#cyclePlayBtn').hide();
}

function cycleNext() {
  monIdx ++;
  if (monIdx >= monitorData.length) {
    monIdx = 0;
  }
  if (!monitorData[monIdx]) {
    console.log('No monitorData for ' + monIdx);
  }
  clearInterval(intervalId);
  monitorStream.kill();

  // +++ Старт следующего монитора
  monitorStream = new MonitorStream(monitorData[monIdx]);
  const img = document.getElementById('liveStream'+monitorData[monIdx-1].id);
  const src = img.src;
  if (src) {
    const url = new URL(src);
    url.searchParams.set('monitor', monitorData[monIdx].id);
    url.searchParams.delete('connkey');
    url.searchParams.set('mode', 'single');
    img.src = '';
    img.src = url;
    img.id = 'liveStream'+monitorData[monIdx].id;
  } else {
    // Пока х.з. что делать.... 
  }

  if (!monitorStream.started) {
    monitorStream.start();
  }

  cycleStart();
  //Изменим активный элемент
  document.getElementById('nav-item-cycle'+monitorData[monIdx-1].id).querySelector('a').classList.remove("active");
  document.getElementById('nav-item-cycle'+monitorData[monIdx].id).querySelector('a').classList.add("active");
  // --- Старт следующего монитора
  //window.location.replace('?view=watch&cycle='+cycle+'&mid='+monitorData[monIdx].id+'&mode='+mode);
}

function cyclePrev() {
  monIdx --;
  if (monIdx < 0) {
    monIdx = monitorData.length - 1;
  }
  if (!monitorData[monIdx]) {
    console.log('No monitorData for ' + monIdx);
  }
  clearInterval(intervalId);
  monitorStream.stop();
  window.location.replace('?view=watch&cycle='+cycle+'&mid='+monitorData[monIdx].id+'&mode='+mode);
}

function cyclePeriodChange() {
  const cyclePeriodSelect = $j('#cyclePeriod');
  setCookie('zmCyclePeriod', cyclePeriodSelect.val());
}
function cycleToggle(e) {
  const button = $j('#cycleToggle');
  if (sidebarView.is(":visible")) {
    sidebarView.hide();
    setCookie('zmCycleShow', false);
  } else {
    sidebarView.show();
    setCookie('zmCycleShow', true);
  }
  button.toggleClass('btn-secondary');
  button.toggleClass('btn-primary');
  changeObjectClass();
  changeSize();
}

function ptzToggle(e) {
  const button = $j('#ptzToggle');
  if (sidebarControls.is(":visible")) {
    sidebarControls.hide();
    setCookie('ptzShow', false);
  } else {
    sidebarControls.show();
    setCookie('ptzShow', true);
  }
  button.toggleClass('btn-secondary');
  button.toggleClass('btn-primary');
  changeObjectClass();
  changeSize();
}

function changeRate(e) {
  const newvalue = $j(e).val();
  if (1) {
    monitorStream.streamCommand({command: CMD_MAXFPS, maxfps: newvalue});
  } else {
    streamImage = $j('#liveStream'+monitorData[monIdx].id);
    const oldsrc = streamImage.attr('src');
    streamImage.attr('src', ''); // stop streaming
    console.log(newvalue);
    if (newvalue == '0') {
      // Unlimited
      streamImage.attr('src', oldsrc.replace(/maxfps=\d+/i, 'maxfps=0.00100'));
    } else {
      streamImage.attr('src', oldsrc.replace(/maxfps=\d+/i, 'maxfps='+newvalue));
    }
  }
  setCookie('zmWatchRate', newvalue);
}

function getObjdetectModal(eid) {
  $j.getJSON(thisUrl + '?request=modal&modal=objdetect&eid=' + eid)
      .done(function(data) {
        insertModalHtml('objdetectModal', data.html);
        $j('#objdetectModal').modal('show');
      })
      .fail(function(jqxhr) {
        console.log("Fail get objdetect details");
        logAjaxFail(jqxhr);
      });
}

function changeObjectClass() {
  if (sidebarView.is(":visible") && sidebarControls.is(":visible")) { //LEFT + RIGHT
    sidebarView.removeClass(classSidebarL).addClass(classSidebarL);
    sidebarControls.removeClass(classSidebarR).addClass(classSidebarR);
    wrapperMonitor.removeClass(classMonitorW_SB_LR).removeClass(classMonitorW_SB_L).removeClass(classMonitorW_SB_R).removeClass(classMonitorWO_SB).addClass(classMonitorW_SB_LR);
  } else if (sidebarView.is(":visible") && !sidebarControls.is(":visible")) { //LEFT
    sidebarView.removeClass(classSidebarL).addClass(classSidebarL);
    sidebarControls.removeClass(classSidebarR);
    wrapperMonitor.removeClass(classMonitorW_SB_LR).removeClass(classMonitorW_SB_L).removeClass(classMonitorW_SB_R).removeClass(classMonitorWO_SB).addClass(classMonitorW_SB_L);
  } else if (!sidebarView.is(":visible") && sidebarControls.is(":visible")) { //RIGHT
    sidebarView.removeClass(classSidebarL);
    sidebarControls.removeClass(classSidebarR).addClass(classSidebarR);
    wrapperMonitor.removeClass(classMonitorW_SB_LR).removeClass(classMonitorW_SB_L).removeClass(classMonitorW_SB_R).removeClass(classMonitorWO_SB).addClass(classMonitorW_SB_R);
  } else if (!sidebarView.is(":visible") && !sidebarControls.is(":visible")) { //NOT
    sidebarView.removeClass(classSidebarL);
    sidebarControls.removeClass(classSidebarR);
    wrapperMonitor.removeClass(classMonitorW_SB_LR).removeClass(classMonitorW_SB_L).removeClass(classMonitorW_SB_R).removeClass(classMonitorWO_SB).addClass(classMonitorWO_SB);
  }
}

function panZoomIn(el) {
  zmPanZoom.zoomIn(el);
}

function panZoomOut(el) {
  zmPanZoom.zoomOut(el);
}

function monitorsSetScale(id=null) {
  //This function will probably need to be moved to the main JS file, because now used on Watch & Montage pages
  if (id || typeof monitorStream !== 'undefined') {
    //monitorStream used on Watch page.
    if (monitorStream) {
      var curentMonitor = monitorStream;
    } else {
      var curentMonitor = monitors.find((o) => {
        return parseInt(o["id"]) === id;
      });
    }
    //const el = document.getElementById('liveStream'+id);
    if (panZoomEnabled) {
      var panZoomScale = zmPanZoom.panZoom[id].getScale();
    } else {
      var panZoomScale = 1;
    }

    const scale = $j('#scale').val();
    let resize;
    let width;
    let maxWidth = '';
    let height;
    let overrideHW = false;
    let defScale = 0;
    const landscape = curentMonitor.width / curentMonitor.height > 1 ? true : false; //Image orientation.

    if (scale == '0') {
      //Auto, Width is calculated based on the occupied height so that the image and control buttons occupy the visible part of the screen.
      resize = true;
      width = 'auto';
      height = 'auto';
    } else if (scale == '100') {
      //Actual, 100% of original size
      resize = false;
      width = curentMonitor.width + 'px';
      height = curentMonitor.height + 'px';
    } else if (scale == 'fit_to_width') {
      //Fit to screen width
      resize = false;
      width = parseInt(window.innerWidth * panZoomScale) + 'px';
      height = 'auto';
    } else if (scale.indexOf("px") > -1) {
      if (landscape) {
        maxWidth = scale;
        defScale = parseInt(Math.min(stringToNumber(scale), window.innerWidth) / curentMonitor.width * panZoomScale * 100);
        height = 'auto';
      } else {
        defScale = parseInt(Math.min(stringToNumber(scale), window.innerHeight) / curentMonitor.height * panZoomScale * 100);
        height = scale;
      }
      resize = true;
      width = 'auto';
      overrideHW = true;
    }

    if (resize) {
      if (scale == '0') {
        document.getElementById('monitor'+id).style.width = 'max-content'; //Required when switching from resize=false to resize=true
      }
      document.getElementById('monitor'+id).style.maxWidth = maxWidth;
      if (!landscape) { //PORTRAIT
        document.getElementById('monitor'+id).style.width = 'max-content';
        document.getElementById('liveStream'+id).style.height = height;
      }
    } else {
      document.getElementById('liveStream'+id).style.height = '';
      document.getElementById('monitor'+id).style.width = width;
      document.getElementById('monitor'+id).style.maxWidth = '';
      if (scale == 'fit_to_width') {
        document.getElementById('monitor'+id).style.width = '';
      } else if (scale == '100') {
        document.getElementById('liveStream'+id).style.width = width;
      }
    }
    //curentMonitor.setScale(0, maxWidth ? maxWidth : width, height, {resizeImg: resize, scaleImg: panZoomScale});
    curentMonitor.setScale(defScale, width, height, {resizeImg: resize, scaleImg: panZoomScale, streamQuality: $j('#streamQuality').val()});
    if (overrideHW) {
      if (!landscape) { //PORTRAIT
        document.getElementById('monitor'+id).style.width = 'max-content';
      } else {
        document.getElementById('liveStream'+id).style.height = 'auto';
        document.getElementById('monitor'+id).style.width = 'auto';
      }
    }
  } else {
    for ( let i = 0, length = monitors.length; i < length; i++ ) {
      const id = monitors[i].id;
      //const el = document.getElementById('liveStream'+id);
      if (panZoomEnabled) {
        var panZoomScale = panZoom[id].getScale();
      } else {
        var panZoomScale = 1;
      }

      const scale = $j('#scale').val();
      let resize;
      let width;
      let height;

      if (scale == '0') {
        //Auto, Width is calculated based on the occupied height so that the image and control buttons occupy the visible part of the screen.
        resize = true;
        width = 'auto';
        height = 'auto';
      } else if (scale == '100') {
        //Actual, 100% of original size
        resize = false;
        width = monitors[i].width + 'px';
        height = monitors[i].height + 'px';
      } else if (scale == 'fit_to_width') {
        //Fit to screen width
        resize = false;
        width = parseInt(window.innerWidth * panZoomScale) + 'px';
        height = 'auto';
      }

      if (resize) {
        document.getElementById('monitor'+id).style.width = 'max-content'; //Required when switching from resize=false to resize=true
      }
      //monitors[i].setScale(0, parseInt(el.clientWidth * panZoomScale) + 'px', parseInt(el.clientHeight * panZoomScale) + 'px', {resizeImg:true, scaleImg:panZoomScale});
      monitors[i].setScale(0, width, height, {resizeImg: resize, scaleImg: panZoomScale});
      if (!resize) {
        document.getElementById('liveStream'+id).style.height = '';
        if (scale == 'fit_to_width') {
          document.getElementById('monitor'+id).style.width = '';
        } else if (scale == '100') {
          document.getElementById('monitor'+id).style.width = 'max-content';
          document.getElementById('liveStream'+id).style.width = width;
        }
      }
    }
  }
}

// Kick everything off
$j( window ).on("load", initPage);

document.onvisibilitychange = () => {
  if (document.visibilityState === "hidden") {
    TimerHideShow = clearTimeout(TimerHideShow);
    TimerHideShow = setTimeout(function() {
      //Stop monitor when closing or hiding page
      monitorStream.kill();
    }, 15*1000);
  } else {
    //Start monitor when show page
    if (!monitorStream.started) {
      monitorStream.start();
    }
  }
};
