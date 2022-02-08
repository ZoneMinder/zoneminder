var streamCmdTimer = null;
var streamStatus;
var alarmState = STATE_IDLE;
var lastAlarmState = STATE_IDLE;
var backBtn = $j('#backBtn');
var settingsBtn = $j('#settingsBtn');
var enableAlmBtn = $j('#enableAlmBtn');
var forceAlmBtn = $j('#forceAlmBtn');
var table = $j('#eventList');
var filterQuery = '&filter[Query][terms][0][attr]=MonitorId&filter[Query][terms][0][op]=%3d&filter[Query][terms][0][val]='+monitorId;

var server;
var janus = null;
var opaqueId;
var streaming2;
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
  var data = params.data;
  data.order = 'desc';
  data.limit = maxDisplayEvents;
  data.sort = 'Id';
  data.view = 'request';
  data.request = 'watch';
  data.mid = monitorId;
  if (auth_hash) data.auth = auth_hash;

  $j.getJSON(thisUrl, data)
      .done(function(data) {
        var rows = processRows(data.rows);
        params.success(rows);
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

  // Scale the frame
  monitor_frame = $j('#imageFeed');
  if (!monitor_frame) {
    console.log('Error finding frame');
    return;
  }
  if (width) monitor_frame.css('width', width);
  if (height) monitor_frame.css('height', height);

  var streamImg = document.getElementById('liveStream'+monitorData[monIdx].id);
  if (streamImg) {
    if (streamImg.nodeName == 'IMG') {
      let src = streamImg.src;
      streamImg.src = '';
      src = src.replace(/width=[\.\d]+/i, 'width='+parseInt(width));
      src = src.replace(/height=[\.\d]+/i, 'height='+parseInt(height));
      src = src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
      streamImg.src = src;
    }
    streamImg.style.width = width ? width : null;
    streamImg.style.height = height ? height : null;
  } else {
    console.log('Did not find liveStream'+monitorData[monIdx].id);
  }
  $j('#scale').val('');
  setCookie('zmCycleScale', '', 3600);
  setCookie('zmCycleWidth', width, 3600);
  setCookie('zmCycleHeight', height, 3600);
} // end function changeSize()

function changeScale() {
  var scale = $j('#scale').val();
  setCookie('zmWatchScale'+monitorId, scale, 3600);
  $j('#width').val('auto');
  $j('#height').val('auto');
  setCookie('zmCycleScale', scale, 3600);
  setCookie('zmCycleWidth', 'auto', 3600);
  setCookie('zmCycleHeight', 'auto', 3600);

  var newWidth;
  var newHeight;
  var autoScale;

  var streamImg = $j('#liveStream'+monitorId);
  if (!streamImg.length) {
    console.error('No element found for liveStream'+monitorId);
  }

  // Always turn it off, we will re-add it below. I don't know if you can add a callback multiple
  // times and what the consequences would be
  $j(window).off('resize', endOfResize); //remove resize handler when Scale to Fit is not active
  if (scale == '0' || scale == 'auto') {
    const newSize = scaleToFit(monitorWidth, monitorHeight, streamImg, $j('#dvrControls'));
    newWidth = newSize.width;
    newHeight = newSize.height;
    autoScale = newSize.autoScale;
    $j(window).on('resize', endOfResize); //remove resize handler when Scale to Fit is not active
  } else {
    newWidth = monitorWidth * scale / SCALE_BASE;
    newHeight = monitorHeight * scale / SCALE_BASE;
  }

  if (streamImg.prop('nodeName') == 'IMG') {
    const oldSrc = streamImg.attr('src');
    streamImg.attr('src', '');
    // This is so that we don't waste bandwidth and let the browser do all the scaling.
    if (autoScale > 100) autoScale = 100;
    if (scale > 100) scale = 100;
    const newSrc = oldSrc.replace(/scale=\d+/i, 'scale='+((scale == 'auto' || scale == '0') ? autoScale : scale));

    streamImg.css('width', newWidth+'px');
    streamImg.width(newWidth);
    streamImg.css('height', newHeight+'px');
    streamImg.height(newHeight);
    streamImg.attr('src', newSrc);
  } else {
    console.log("Not an IMG, can't set size");
  }
} // end function changeScale

function getStreamCmdResponse(respObj, respText) {
  watchdogOk('stream');
  if (streamCmdTimer) {
    streamCmdTimer = clearTimeout(streamCmdTimer);
  }
  if (respObj.result == 'Ok') {
    // The get status command can get backed up, in which case we won't be able to get the semaphore and will exit.
    if (respObj.status) {
      streamStatus = respObj.status;
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
    fetchImage($j('#imageFeed img'));
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

function streamCmdScale(scale) {
  monitorStream.streamCommand({command: CMD_SCALE, scale: scale});
}

function streamCmdPan(x, y) {
  monitorStream.streamCommand({x: x, y: y, command: CMD_PAN});
}


/* getStatusCmd is used when not streaming, since there is no persistent zms */
function getStatusCmdResponse(respObj, respText) {
  watchdogOk('status');
  if (statusCmdTimer) {
    statusCmdTimer = clearTimeout(statusCmdTimer);
  }

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
    console.log('stop');
    console.log(event);
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
    setTimeout(fetchImage, 1000, $j('#imageFeed img'));
  }
}

function controlCmdImage(x, y) {
  var data = {};
  data.scale = scale;
  data.control = imageControlMode;
  data.x = x;
  data.y = y;
  controlReq(data);

  if (streamMode == 'single') {
    setTimeout(fetchImage, 1000, $j('#imageFeed img'));
  }
}

function fetchImage(streamImage) {
  const oldsrc = streamImage.attr('src');
  streamImage.attr('src', '');
  streamImage.attr('src', oldsrc.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) )));
}

function handleClick(event) {
  // target should be the img tag
  var target = $j(event.target);
  console.log("click " + showMode);
  var width = target.width();
  var height = target.height();

  var scaleX = parseInt(monitorWidth / width);
  var scaleY = parseInt(monitorHeight / height);
  var pos = target.offset();
  var x = parseInt((event.pageX - pos.left) * scaleX);
  var y = parseInt((event.pageY - pos.top) * scaleY);

  if (showMode == 'events' || !imageControlMode) {
    if ( event.shift ) {
      streamCmdPan(x, y);
    } else if (event.ctrlKey) {
      streamCmdZoomOut();
    } else {
      streamCmdZoomIn(x, y);
    }
  } else {
    controlCmdImage(x, y);
  }
}

function appletRefresh() {
  if (streamStatus && (!streamStatus.paused && !streamStatus.delayed)) {
    var streamImg = $j('#liveStream'+monitorId);
    if (streamImg) {
      var parent = streamImg.parent();
      streamImg.remove();
      streamImg.append(parent);
    } else {
      console.error("Nothing found for liveStream"+monitorId);
    }
    if (appletRefreshTime) {
      setTimeout(appletRefresh, appletRefreshTime*1000);
    }
  } else {
    setTimeout(appletRefresh, 15*1000); // if we are paused or delayed check every 15 seconds if we are live yet...
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

function getCtrlPresetModal() {
  $j.getJSON(monitorUrl + '?request=modal&modal=controlpreset&mid=' + monitorId)
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

function msieVer() {
  var ua = window.navigator.userAgent;
  var msie = ua.indexOf("MSIE ");

  if (msie >= 0) { // If Internet Explorer, return version number
    return msie;
  } else { // If another browser, return 0
    return 0;
  }
}

function refresh_events_table() {
  table.bootstrapTable('refresh');
}

function initPage() {
  if (canView.Control) {
    // Load the PTZ Preset modal into the DOM
    if (monitorControllable) getCtrlPresetModal();
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

  if (monitorType != 'WebSite') {
    monitorStream = new MonitorStream(monitorData[monIdx]);

    // Start the fps and status updates. give a random delay so that we don't assault the server
    monitorStream.setScale('auto');
    monitorStream.start(Math.round( (Math.random()+0.5)*statusRefreshTimeout ));
    if (streamMode == 'single') {
      monitorStream.setup_onclick(fetchImage);
    } else {
      monitorStream.setup_onclick(handleClick);
    }
    monitorStream.setup_onpause(onPause);
    monitorStream.setup_onplay(onPlay);
    monitorStream.setup_onalarm(refresh_events_table);

    monitorStream.setButton('enableAlarmButton', enableAlmBtn);
    monitorStream.setButton('forceAlarmButton', forceAlmBtn);
    monitorStream.setButton('zoomOutButton', $j('zoomOutBtn'));

    /*
    if (streamMode == 'single') {
      statusCmdTimer = setTimeout(statusCmdQuery, 200);
      setInterval(watchdogCheck, statusRefreshTimeout*2, 'status');
    } else {
      streamCmdTimer = setTimeout(streamCmdQuery, 200);
      setInterval(watchdogCheck, statusRefreshTimeout*2, 'stream');
    }
    if (canStreamNative || (streamMode == 'single')) {
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

    if (refreshApplet && appletRefreshTime) {
      setTimeout(appletRefresh, appletRefreshTime*1000);
    }
    */
    if (window.history.length == 1) {
      $j('#closeControl').html('');
    }
    document.querySelectorAll('select[name="scale"]').forEach(function(el) {
      el.onchange = window['changeScale'];
    });
    changeScale();
    document.querySelectorAll('select[name="changeRate"]').forEach(function(el) {
      el.onchange = window['changeRate'].bind(el, el);
    });

    // Init the bootstrap-table
    table.bootstrapTable({icons: icons});
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
      table.find("tr td:nth-child(" + (thumb_ndx+1) + ")").addClass('colThumbnail');
    });
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

  bindButton('#cyclePlayBtn', 'click', null, cycleStart);
  bindButton('#cyclePauseBtn', 'click', null, cyclePause);
  bindButton('#cycleNextBtn', 'click', null, cycleNext);
  bindButton('#cyclePrevBtn', 'click', null, cyclePrev);
  bindButton('#cycleToggle', 'click', null, cycleToggle);
  bindButton('#cyclePeriod', 'change', null, cyclePeriodChange);
  if (cycle) {
    cycleStart();
  } else {
    cyclePause();
  }
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

var intervalId;
var secondsToCycle = 0;

function nextCycleView() {
  secondsToCycle --;
  if (secondsToCycle<=0) {
    window.location.replace('?view=watch&mid='+nextMid+'&mode='+mode+'&cycle=true');
  }
  $j('#secondsToCycle').text(secondsToCycle);
}

function cyclePause() {
  clearInterval(intervalId);
  $j('#cyclePauseBtn').hide();
  $j('#cyclePlayBtn').show();
}

function cycleStart() {
  secondsToCycle = $j('#cyclePeriod').val();
  intervalId = setInterval(nextCycleView, 1000);
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
  window.location.replace('?view=watch&cycle=true&mid='+monitorData[monIdx].id+'&mode='+mode);
}

function cyclePrev() {
  monIdx --;
  if (monIdx < 0) {
    monIdx = monitorData.length - 1;
  }
  if (!monitorData[monIdx]) {
    console.log('No monitorData for ' + monIdx);
  }
  window.location.replace('?view=watch&cycle=true&mid='+monitorData[monIdx].id+'&mode='+mode);
}

function cyclePeriodChange() {
  const cyclePeriodSelect = $j('#cyclePeriod');
  secondsToCycle = cyclePeriodSelect.val();
  setCookie('zmCyclePeriod', secondsToCycle, 3600);
}
function cycleToggle(e) {
  sidebar = $j('#sidebar');
  button = $j('#cycleToggle');
  if (sidebar.is(":visible")) {
    sidebar.hide();
    setCookie('zmCycleShow', false, 3600);
  } else {
    sidebar.show();
    setCookie('zmCycleShow', true, 3600);
  }
  button.toggleClass('btn-secondary');
  button.toggleClass('btn-primary');
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
  setCookie('zmWatchRate', newvalue, 3600);
}

// Kick everything off
$j(document).ready(initPage);
