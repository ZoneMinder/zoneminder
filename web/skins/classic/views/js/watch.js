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
var monitorStream = false; /* Stream is not started */
var currentMonitor;

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
var observer;

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

function changeScale() {
  const scale = $j('#scale').val();
  setCookie('zmWatchScaleNew'+monitorId, scale);
  setCookie('zmCycleScale', scale);
  monitorsSetScale(monitorId);
}

function changeStreamQuality() {
  const streamQuality = $j('#streamQuality').val();
  setCookie('zmStreamQuality', streamQuality);
  monitorsSetScale(monitorId);
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
  if (action && monitorStream) {
    monitorStream.pause();
  }
}

function onPlay() {
  //monitorStream.setup_onplay(onPlay); //IgorA100 Added for testing, but probably not required
  //setButtonState('pauseBtn', 'inactive');
  //setButtonState('playBtn', 'active');
  setButtonStateWatch('pauseBtn', 'inactive');
  setButtonStateWatch('stopBtn', 'inactive');
  setButtonStateWatch('playBtn', 'unavail');
  if (monitorStream.status.delayed == true) {
    //setButtonState('stopBtn', 'inactive');
    if (monitorStreamReplayBuffer) {
      setButtonState('fastFwdBtn', 'inactive');
      setButtonState('slowFwdBtn', 'inactive');
      setButtonState('slowRevBtn', 'inactive');
      setButtonState('fastRevBtn', 'inactive');
    }
  } else {
    //setButtonState('stopBtn', 'unavail');
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
    if (monitorStream.started) {
      //Stream was on pause
      monitorStream.play();
    } else {
      //Stream has been stopped
      monitorStream.start();
    }
  }
}

function streamCmdStop(action) {
  monitorStream.onplay = false; //Without this line, "onPlay" is triggered immediately due to "if (this.onplay) this.onplay();" in MonitorStream.js
  //setButtonState('pauseBtn', 'inactive');
  //setButtonState('playBtn', 'unavail');
  //setButtonState('stopBtn', 'active');
  if (monitorStreamReplayBuffer) {
    setButtonState('fastFwdBtn', 'unavail');
    setButtonState('slowFwdBtn', 'unavail');
    setButtonState('slowRevBtn', 'unavail');
    setButtonState('fastRevBtn', 'unavail');
  }
  if (action) {
    //monitorStream.streamCommand(CMD_STOP);
    monitorStream.kill();
  }
  //setButtonState('stopBtn', 'unavail');
  //setButtonState('playBtn', 'active');
  setButtonStateWatch('playBtn', 'inactive');
  setButtonStateWatch('stopBtn', 'unavail');
  setButtonStateWatch('pauseBtn', 'unavail');
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
    monitorStream.streamCommand(CMD_SLOWFWD);
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
    monitorStream.streamCommand(CMD_SLOWREV);
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
    monitorStream.streamCommand(CMD_FASTREV);
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
    cmdForceAlarm();
  } else {
    cmdCancelForcedAlarm();
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
  const targetId = event.target.id;
  if (targetId.indexOf("nav-link") >= 0) { //Navigation through monitors
    cycleStop(event.target);
    const oldId = stringToNumber(document.querySelector('[id ^= "liveStream"]').id);
    const newId = stringToNumber(targetId);
    streamReStart(oldId, newId);
  } else if (event.target.closest('#dvrControls')) { //Controls DVR
    cyclePause();
  } else if (!event.target.closest('#wrapperMonitor')) {
    return;
  }

  if (panZoomEnabled) {
    //event.preventDefault();
    //We are looking for an object with an ID, because there may be another element in the button.
    const obj = targetId ? event.target : event.target.parentElement;
    if (!obj) {
      console.log("No obj found", targetId, event.target, event.target.parentElement);
      return;
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

    const obj_id = obj.getAttribute('id');
    if (obj_id) {
      if (obj_id.indexOf("liveStream") >= 0) zmPanZoom.click(monitorId);
    } else {
      console.log("obj does not have an id", obj);
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

function reloadWebSite() {
  document.getElementById('imageFeed').innerHTML = document.getElementById('imageFeed').innerHTML;
}

function updatePresetLabels() {
  var lblNdx = $j('#ctrlPresetForm option:selected').val();
  $j('#newLabel').val(labels[lblNdx]);
}


function changeControl(e) {
  const input = e.target;
  $j.getJSON(monitorUrl+'?request=v4l2_settings&mid='+monitorId+'&'+input.name+'='+input.value+'&'+auth_relay)
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
  $j.getJSON(monitorUrl + '?request=modal&modal=settings&mid=' + monitorId+'&'+auth_relay)
      .done(function(data) {
        let modal = $j('#settingsModal');
        if (modal.length) modal.remove();
        insertModalHtml('settingsModal', data.html);
        modal = $j('#settingsModal');
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

function streamPrepareStart(monitor=null) {
  // Only enable the settings button for local cameras
  if (!canView.Control) {
    settingsBtn.prop('disabled', true);
    settingsBtn.prop('title', 'Disabled due to lack of Control View permission.');
  } else if (monitorType != 'Local') {
    settingsBtn.prop('disabled', true);
    settingsBtn.prop('title', 'Settings only available for Local monitors.');
  } else {
    // Load the settings modal into the DOM
    settingsBtn.prop('disabled', false);
    getSettingsModal();
    // Manage the SETTINGS button
    bindButton('#settingsBtn', 'click', null, function onSettingsClick(evt) {
      evt.preventDefault();
      $j('#settingsModal').modal('show');
    });
  }

  if ((monitorType != 'WebSite') && monitorData.length) {
    streamStart(monitor);
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
        const thumb_ndx = $j('#eventList tr th').filter(function() {
          return $j(this).text().trim() == 'Thumbnail';
        }).index();
        table.find("tr td:nth-child(" + (thumb_ndx+1) + ")").addClass('colThumbnail');
      });
    } // end if canView.Events
  } else if (monitorRefresh > 0) {
    setInterval(reloadWebSite, monitorRefresh*1000);
  }

  // Manage the generate Edit button
  bindButton('#editBtn', 'click', null, function onEditClick(evt) {
    evt.preventDefault();
    window.location.assign("?view=monitor&mid="+monitorId);
  });

  const el = document.querySelector('.imageFeed');
  if (el) {
    el.addEventListener('mouseenter', handleMouseEnter);
    el.addEventListener('mouseleave', handleMouseLeave);
  }

  const i = setInterval(function() {
    const livestream = document.querySelector('[id ^= "liveStream"]');

    if (!livestream) {
      console.log("No id matching liveStream");
    } else if (livestream.offsetHeight > 20) {
      //You need to wait until the image appears.
      clearInterval(i);
      document.getElementById('monitor').classList.remove('hidden-shift');
      monitorsSetScale(monitorId);
    }
  }, 100);
  setButtonStateWatch('stopBtn', 'active');
  setTimeout(dataOnClick, 100);
}

function handleMouseEnter(event) {
  //Displaying "Scale" and other buttons at the top of the monitor image
  const id = stringToNumber(this.id);
  //$j('#button_zoom' + id).stop(true, true).slideDown('fast');
  $j('#button_zoom' + id).removeClass('hidden');
}

function handleMouseLeave(event) {
  const id = stringToNumber(this.id);
  //$j('#button_zoom' + id).stop(true, true).slideUp('fast');
  $j('#button_zoom' + id).addClass('hidden');
}

function streamStart(monitor = null) {
  monitorStream = new MonitorStream(monitor ? monitor : monitorData[monIdx]);
  monitorStream.setBottomElement(document.getElementById('dvrControls'));
  // Start the fps and status updates. give a random delay so that we don't assault the server
  //monitorStream.setScale($j('#scale').val(), $j('#width').val(), $j('#height').val());
  //monitorsSetScale(monitorId);
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
}

function streamReStart(oldId, newId) {
  const monitor_div = document.getElementById('monitor');
  monitor_div.classList.add('hidden-shift');

  currentMonitor = monitorData.find((o) => {
    return parseInt(o["id"]) === newId;
  });
  monitorId = newId;
  filterQuery = '&filter[Query][terms][0][attr]=MonitorId&filter[Query][terms][0][op]=%3d&filter[Query][terms][0][val]='+monitorId;

  const newMonitorName = document.getElementById('nav-item-cycle'+newId).querySelector('a').textContent;
  document.querySelector('title').textContent = newMonitorName;
  const url = new URL(document.location.href);
  url.searchParams.set('mid', monitorId);
  history.pushState(null, "", url);

  zmPanZoom.action('disable', {id: oldId});
  if (monitorStream) {
    monitorStream.kill();
  }

  const el = document.querySelector('.imageFeed');
  el.removeEventListener('mouseenter', handleMouseEnter);
  el.removeEventListener('mouseleave', handleMouseLeave);

  //Change main monitor block
  monitor_div.innerHTML = currentMonitor.streamHTML;

  //Change active element of the navigation menu
  document.getElementById('nav-item-cycle'+oldId).querySelector('a').classList.remove("active");
  document.getElementById('nav-item-cycle'+newId).querySelector('a').classList.add("active");

  //Set global variables from the current monitor
  monitorWidth = currentMonitor.monitorWidth;
  monitorHeight = currentMonitor.monitorHeight;
  monitorType = currentMonitor.monitorType;
  monitorRefresh = currentMonitor.monitorRefresh;
  monitorStreamReplayBuffer = currentMonitor.monitorStreamReplayBuffer;
  monitorControllable = currentMonitor.monitorControllable;
  streamMode = currentMonitor.streamMode;

  table.bootstrapTable('destroy');
  applyMonitorControllable();
  streamPrepareStart(currentMonitor);
  zmPanZoom.init();
  zmPanZoom.init({objString: '.imageFeed', disablePan: true, contain: 'inside', additional: true});
  loadFontFaceObserver();
  //document.getElementById('monitor').classList.remove('hidden-shift');
}

function applyMonitorControllable() {
  const ptzToggle = document.getElementById('ptzToggle');
  if (!ptzToggle) {
    console.log('ptz toggle is not present. Likely OPT_CONTROL is off');
    return;
  }
  if (currentMonitor.monitorControllable) {
    const ptzShow = getCookie('ptzShow');

    ptzToggle.classList.remove("disabled");
    ptzToggle.disabled=false;
    sidebarControls.html(currentMonitor.ptzControls);
    if (ptzShow) {
      sidebarControls.show();
      ptzToggle.classList.remove("btn-secondary");
      ptzToggle.classList.add("btn-primary");
    } else {
      sidebarControls.hide();
      ptzToggle.classList.remove("btn-primary");
      ptzToggle.classList.add("btn-secondary");
    }
  } else {
    ptzToggle.classList.add("disabled");
    ptzToggle.disabled=true;
    sidebarControls.html('');
    sidebarControls.hide();
  }
  changeObjectClass();
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
  zmPanZoom.init({objString: '.imageFeed', disablePan: true, contain: 'inside', additional: true});

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


  bindButton('#cyclePlayBtn', 'click', null, cycleStart);
  bindButton('#cyclePauseBtn', 'click', null, cyclePause);
  bindButton('#cycleNextBtn', 'click', null, cycleNext);
  bindButton('#cyclePrevBtn', 'click', null, cyclePrev);
  bindButton('#cycleToggle', 'click', null, cycleToggle);
  bindButton('#cyclePeriod', 'change', null, cyclePeriodChange);
  if (monitorData.length > 1 && cycle) {
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
      if (idle >= ZM_WEB_VIEWING_TIMEOUT) {
        streamCmdPause(true);
        const cycle_was = cycle;
        cyclePause();
        let ayswModal = $j('#AYSWModal');
        if (!ayswModal.length) {
          $j.getJSON('?request=modal&modal=areyoustillwatching')
              .done(function(data) {
                ayswModal = insertModalHtml('AYSWModal', data.html);
                ayswModal.on('hidden.bs.modal', function() {
                  streamCmdPlay(true);
                  if (cycle_was) cycleStart();
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

  setInterval(() => {
    //Updating Scale. When quickly scrolling the mouse wheel or quickly pressing Zoom In/Out, you should not set Scale very often.
    if (updateScale) {
      console.log('set scale for ', monitorId);
      monitorsSetScale(monitorId);
      updateScale = false;
    }
  }, 300);

  document.addEventListener('click', function(event) {
    handleClick(event);
  });

  //document.getElementById('monitor').classList.remove('hidden-shift');
  changeObjectClass();

  currentMonitor = monitorData.find((o) => {
    return parseInt(o["id"]) === monitorId;
  });
  if (currentMonitor) {
    applyMonitorControllable();
    streamPrepareStart(currentMonitor);

    // Creating a ResizeObserver Instance
    observer = new ResizeObserver((objResizes) => {
      updateScale = true;
    });

    // Registering an observer on an element
    $j('[id ^= "liveStream"]').each(function() {
      observer.observe(this);
    });

    // Event listener for double click
    //var elStream = document.querySelectorAll('[id ^= "liveStream"], [id ^= "evtStream"]');
    var elStream = document.querySelectorAll('[id = "wrapperMonitor"]');
    Array.prototype.forEach.call(elStream, (el) => {
      el.addEventListener('touchstart', doubleTouch);
      el.addEventListener('dblclick', doubleClickOnStream);
    });
  } else {
    alert("No monitor found for id "+monitorId);
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

function watchAllEvents() {
  window.location.replace(currentMonitor.urlForAllEvents);
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
  if (secondsToCycle == 0) secondsToCycle = $j('#cyclePeriod').val();
  intervalId = setInterval(nextCycleView, 1000);
  cycle = true;
  $j('#cyclePauseBtn').show();
  $j('#cyclePlayBtn').hide();
}

function cycleStop(target) {
  secondsToCycle = 0;
  monIdx = target.getAttribute('data-monIdx');
  $j('#secondsToCycle').text('');
  cyclePause();
}

// FIXME this runs within the interval handler and can take >50ms which will cause chrome to complain.
function cycleNext() {
  monIdx ++;
  if (monIdx >= monitorData.length) {
    monIdx = 0;
  }
  if (!monitorData[monIdx]) {
    console.log('No monitorData for ' + monIdx);
  }
  clearInterval(intervalId);

  // +++ Start next monitor
  const oldId = monitorData[(monIdx == 0) ? monitorData.length-1 : monIdx-1].id;
  const newId = monitorData[monIdx].id;
  streamReStart(oldId, newId);
  if (cycle) cycleStart();
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

  // +++ Start previous monitor
  const oldId = monitorData[(monIdx == monitorData.length - 1)? 0 : monIdx+1].id;
  const newId = monitorData[monIdx].id;
  streamReStart(oldId, newId);
  if (cycle) cycleStart();
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
  monitorsSetScale(monitorId);
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
  monitorsSetScale(monitorId);
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

function panZoomEventPanzoomzoom(event) {
  //Temporarily not in use
  /*
  const obj = event.target;
  const parent = obj.parentNode;
  const objDim = obj.getBoundingClientRect();
  const parentDim = parent.getBoundingClientRect();
  const top = objDim.top - parentDim.top;
  const h = objDim.height + top;
  console.log("object", obj);
  console.log("parentDim:", parentDim);
  console.log("objDim:", objDim);
  console.log("H:", h);
  if (h>30) {
    parent.style.height = h+'px';
    console.log('panzoomzoom', event.detail) // => { x: 0, y: 0, scale: 1 }
  }
  */
}

function panZoomEventPanzoomchange(event) {

}

function monitorsSetScale(id=null) {
  //This function will probably need to be moved to the main JS file, because now used on Watch & Montage pages
  if (id || typeof monitorStream !== 'undefined') {
    if (monitorStream !== false) {
      //monitorStream used on Watch page.
      var curentMonitor = monitorStream;
    } else if (typeof monitors !== 'undefined') {
      //used on Montage, Watch & Event page.
      var curentMonitor = monitors.find((o) => {
        return parseInt(o["id"]) === id;
      });
    } else {
      //Stream is missing
      return;
    }
    //const el = document.getElementById('liveStream'+id);
    if (panZoomEnabled && zmPanZoom.panZoom[id]) {
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

    const monitor_div = document.getElementById('monitor'+id);
    if (!monitor_div) console.log("No monitor div for ", id);
    if (resize) {
      if (scale == '0') {
        monitor_div.style.width = 'max-content'; //Required when switching from resize=false to resize=true
      }
      monitor_div.style.maxWidth = maxWidth;
      if (!landscape) { //PORTRAIT
        monitor_div.style.width = 'max-content';
        document.getElementById('liveStream'+id).style.height = height;
      }
    } else {
      document.getElementById('liveStream'+id).style.height = '';
      monitor_div.style.width = width;
      monitor_div.style.maxWidth = '';
      if (scale == 'fit_to_width') {
        monitor_div.style.width = '';
      } else if (scale == '100') {
        document.getElementById('liveStream'+id).style.width = width;
      }
    }
    //curentMonitor.setScale(0, maxWidth ? maxWidth : width, height, {resizeImg: resize, scaleImg: panZoomScale});
    curentMonitor.setScale(defScale, width, height, {resizeImg: resize, scaleImg: panZoomScale, streamQuality: $j('#streamQuality').val()});
    if (overrideHW) {
      if (!landscape) { //PORTRAIT
        monitor_div.style.width = 'max-content';
      } else {
        document.getElementById('liveStream'+id).style.height = 'auto';
        monitor_div.style.width = 'auto';
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
        monitor_div.style.width = 'max-content'; //Required when switching from resize=false to resize=true
      }
      //monitors[i].setScale(0, parseInt(el.clientWidth * panZoomScale) + 'px', parseInt(el.clientHeight * panZoomScale) + 'px', {resizeImg:true, scaleImg:panZoomScale});
      monitors[i].setScale(0, width, height, {resizeImg: resize, scaleImg: panZoomScale});
      if (!resize) {
        document.getElementById('liveStream'+id).style.height = '';
        if (scale == 'fit_to_width') {
          monitor_div.style.width = '';
        } else if (scale == '100') {
          monitor_div.style.width = 'max-content';
          document.getElementById('liveStream'+id).style.width = width;
        }
      }
    } // end foreach monitor
  }
  setButtonSizeOnStream();
}

// Kick everything off
$j( window ).on("load", initPage);

document.onvisibilitychange = () => {
  // Always clear it because the return to visibility might happen before timeout
  TimerHideShow = clearTimeout(TimerHideShow);
  if (document.visibilityState === "hidden") {
    TimerHideShow = setTimeout(function() {
      //Stop monitor when closing or hiding page
      if (monitorStream) {
        monitorStream.kill();
      }
    }, 15*1000);
  } else {
    //Start monitor when show page
    if (monitorStream && !monitorStream.started && (idle<ZM_WEB_VIEWING_TIMEOUT)) {
      monitorStream.start();
    }
  }
};

function setButtonStateWatch(element_id, btnClass) {
  //Temporary function so as not to break anything else, because analysis of the setButtonState function in skin.js is required,
  //and also review the logic of the buttons and more (if (this.onplay) this.onplay() in MonitorStream.js)  var element = document.getElementById(element_id);
  var element = document.getElementById(element_id);
  if ( element ) {
    element.className = btnClass;
    if (btnClass == 'unavail') {
      element.disabled = true;
    } else {
      element.disabled = false;
    }
  } else {
    console.log('Element was null or not found in setButtonState. id:'+element_id);
  }
}
