"use strict";
var monitors = new Array();
var monitorsId = new Array();
var arrRatioMonitors = [];
var monitors_ul = null;
var idle = 0;

const VIEWING = 0;
const EDITING = 1;

var mode = 0; // start up in viewing mode
var observer;
var objGridStack;

var layoutColumns = 48; //Maximum number of columns (items per row) for GridStack
var changedMonitors = []; //Monitor IDs that were changed in the DOM
var onvisibilitychangeTriggered = false;

var scrollBbarExists = null;
var movableMonitorData = []; //Monitor data (id, width, stop (true - stop moving))
var TimerHideShow = null;
var monitorCanvasCtx = [];
var monitorCanvasObj = [];

const presetRatio = new Map([
  ['auto', ''],
  ['real', ''],
  ['1:1', '1.000'],
  ['5:4', '1.250'],
  ['4:3', '1.333'],
  ['43:32', '1.344'],
  ['11:8', '1.375'],
  ['3:2', '1.500'],
  ['25:16', '1.563'],
  ['16:10', '1.600'],
  ['5:3', '1.667'],
  ['16:9', '1.778'],
  ['50:27', '1.852'],
  ['18:9', '2.000'],
  ['11:5', '2.200'],
  ['21:9', '2.333'],
  ['64:27', '2.370'],
  ['12:5', '2.400'],
  ['64:25', '2.560'],
  ['13:5', '2.600'],
  ['11:4', '2.750'],
]);

var defaultPresetRatio = 'auto';
var averageMonitorsRatio;

var montageMode = ''; //Live || inRecording
var prevMontageMode = ''; //The previous mode from which we switch
var eventsPlay = false;

var timeline;
var idTimelineCustomTimeMarker = 'id';
var customTimeSpecified = false;
const timelineBlock = document.getElementById('timelinediv');
const wrapperTimelineBlock = document.getElementById('wrapper-timeline');
var createdTimelineExtraInfo; //Let's create a block in the Timeline table to use the empty space to good use.
var timelineCurrentTimeHTML;
var timelineExtraInfo;
var intervalRefreshCheckNextEvent;
var intervalRefreshUpdateCurrentTime;
var intervalSynchronizeEventsWithTimeline;
var eventsOnTimeline = []; //Events displayed on Timeline
var prevDateTimeTimelineInMilliSec = null; //Required for fast or slow motion playback

var eventsTable = []; //Храним текущее (если идет воспроизвдение) или следующее (если в данный момент нет воспроизведения) событие в т.ч. и для рассчета Scale.
var getEventsInProgress;
var prevRangeWindowTimeline = {}; //Диапазон видимых значений до изменения масштаба Timeline.
const alertLoadEvents = $j("#alert-load-events");

var selectStartDateTime = document.getElementById("StartDateTime");
var selectEndDateTime = document.getElementById("EndDateTime");
var selectArchived = document.getElementById("filterArchived");
var selectTags = document.getElementById("filterTags");
var selectNotes = document.getElementById("filterNotes");

var startDateFirstEvent = dateTimeToISOLocal(new Date(1900, 1, 1)); //Дата начала первого события группы мониторов для TimeLine, далее будет переопределена

var shifted = null;
var ctrled = null; 
var alted = null;

var lastSpeed; //Странно, но этого вообще не было и похоже это не было реализовано
var streamCmdTimer = null;

function isPresetLayout(name) {
  return ((ZM_PRESET_LAYOUT_NAMES.indexOf(name) != -1) ? true : false);
}

function getCurrentNameLayout() {
  return layouts[parseInt($j('#zmMontageLayout').val())].Name;
}

function showSpeed(val) {
  // updates slider only
  $j('#speedslideroutput').text(parseFloat(speeds[val]).toFixed(2).toString() + " x");
}

function setSpeed(newSpeed) {
  if (montageMode == 'Live') {
    // IMPORTANT The code is left from the old montage.js, but it didn't seem to work for Live mode!
    lastSpeed = currentSpeed;
    currentSpeed = newSpeed;
    setCookie('speedForLive', String(currentSpeed), 3600);
    for (let i=0, length = monitors.length; i < length; i++) {
      const monitorStream = monitors[i];
      if (lastSpeed != '0' && currentSpeed != '0') {
        monitorStream.setMaxFPS(currentSpeed);
      } else if (lastSpeed != '0') {
        // pause
        monitorStream.pause();
      } else {
        // play
        monitorStream.play();
      }
      this.started = true;
    }
  } else { //inRecording
    ////console.log("+++newSpeed", newSpeed);
    speedIndex = newSpeed;
    currentSpeed = parseFloat(speeds[speedIndex]);
    setCookie('speed', String(currentSpeed), 3600);
    //playSecsPerInterval = Math.floor( 1000 * currentSpeed * currentDisplayInterval ) / 1000000;
    showSpeed(speedIndex);
    //timerFire();
    setSpeedForMonitors(currentSpeed * 100);
  }
}

function speedChange(ddm) {
  lastSpeed = $j(ddm).val();
  if (lastSpeed == '0') {
    pausedClicked();
  } else {
    clickedPlay();
  }
}

function clickedStop() {
  console.log('clickedStop');
  if (montageMode == 'Live') {

  } else { //inRecording
    stopAllEvents();
  }
}

function clickedPause() {
  console.log('clickedPause');
  if (montageMode == 'Live') {
    setSpeed('0');
    $j('#playBtn').show();
    $j('#pauseBtn').hide();
    $j('#speed').val(speed);
  } else { //inRecording
    pauseAllEvents();
  }
}

function clickedPlay() {
  console.log("lastSpeed==>", lastSpeed);
  if (montageMode == 'Live') {
    if (!lastSpeed) lastSpeed = 'auto';
    setSpeed(lastSpeed);
    $j('#playBtn').hide();
    $j('#pauseBtn').show();
    $j('#speed').val(speed);
  } else { //inRecording
    startAllEvents();
  }
}

function streamPrev() {

}

function streamFastRev() {

}

function streamSlowRev() {

}

function streamSlowFwd() {
  for (var monitorId in eventsTable) {
    eventInfo = getEventInfoFromEventsTable({what: 'current', mid: monitorId});
    if (eventInfo.status == 'started' ) {
      const url = new URL(eventInfo.src);
      const connkey = url.searchParams.get('connkey');
      if (!connkey) continue;
      const monitor = monitors.find((o) => {
        return parseInt(o["id"]) === parseInt(monitorId);
      });
      streamReq({
        command: CMD_SLOWFWD,
        monitorId: monitorId,
        connkey: connkey,
        eventId: eventInfo.eventId,
        monitorUrl: monitor.url
      });
    }
  }
}

function streamFastFwd() {

}

function streamNext() {

}

/**
 * called when the layoutControl select element is changed, or the page
 * is rendered
 * @param {*} new_layout_id - the id of a layout to switch to
 */
function selectLayout(new_layout_id) {
  if (mode == EDITING) {
    changedMonitors.length = 0;
    return;
  }
  const ddm = $j('#zmMontageLayout');
  if (new_layout_id && (typeof(new_layout_id) != 'object')) {
    ddm.val(new_layout_id);
  }
  const layout_id = parseInt(ddm.val());
  if (!layout_id) {
    console.log("No layout_id?!");
    changedMonitors.length = 0;
    return;
  }

  const layout = layouts[layout_id];
  if (!layout) {
    console.log("No layout found for " + layout_id);
    changedMonitors.length = 0;
    return;
  }

  const nameLayout = layout.Name;
  const widthFrame = layoutColumns / stringToNumber(nameLayout);

  if (objGridStack) {
    objGridStack.destroy(false);
  }

  if (isPresetLayout(nameLayout)) { //PRESET
    document.getElementById("btnDeleteLayout").setAttribute('disabled', '');
    setSelected(document.getElementById("ratio"), getCookie('zmMontageRatioForAll'));
    changeRatioForAll();

    for (let i = 0, length = monitors.length; i < length; i++) {
      const monitor = monitors[i];
      // Need to clear the current positioning, and apply the new
      const monitor_frame = $j('#monitor'+monitor.id);
      if (!monitor_frame) {
        console.log('Error finding frame for ' + monitor.id);
        continue;
      }
      const monitor_wrapper = monitor_frame.closest('[gs-id="' + monitor.id + '"]');

      if (nameLayout == 'Auto') {
        monitor_wrapper.attr('gs-w', layoutColumns / stringToNumber(autoLayoutName)).removeAttr('gs-x').removeAttr('gs-y').removeAttr('gs-h');
        //monitor_wrapper.attr('gs-w', 12).removeAttr('gs-x').removeAttr('gs-y').removeAttr('gs-h');
      } else {
        monitor_wrapper.attr('gs-w', widthFrame).removeAttr('gs-x').removeAttr('gs-y').removeAttr('gs-h');
      }
      setRatioForMonitor(getStream(monitors[i].id), monitors[i].id);
    }
    initGridStack();
  } else { //CUSTOM
    document.getElementById("btnDeleteLayout").removeAttribute('disabled');
    for (let i = 0, length = monitors.length; i < length; i++) {
      const monitor = monitors[i];
      // Need to clear the current positioning, and apply the new
      const monitor_frame = $j('#monitor'+monitor.id);
      if (!monitor_frame) {
        console.log('Error finding frame for ' + monitor.id);
        continue;
      }
      const monitor_wrapper = monitor_frame.closest('[gs-id="' + monitor.id + '"]');
      monitor_wrapper.attr('gs-w', 12).removeAttr('gs-x').removeAttr('gs-y').removeAttr('gs-h');
      $j('#liveStream'+monitor.id).css('height', '');
      setRatioForMonitor(getStream(monitors[i].id), monitors[i].id);
    }

    if (layout.Positions.gridStack) {
      if (layout.Positions.monitorRatio) {
        for (const [key, value] of Object.entries(layout.Positions.monitorRatio)) {
          const select = document.getElementById("ratio"+key);
          //Monitor may not be in the saved Layout, because for example, the monitor was removed from the group, etc.
          if (select) {
            setSelected(select, value);
          }
        }
      } else {
        const selected = getSelected(document.getElementById("ratio"));
        setSelectedRatioForAllMonitors(selected ? selected : defaultPresetRatio);
      }

      checkRatioForAllMonitors();
      initGridStack(layout.Positions.gridStack);
    } else { //Probably the layout was saved in the old (until May 2024) version of ZM
      initGridStack();
      $j('#messageModal').modal('show');
    }
  }

  /* Probably unnecessary, because... we have ResizeObserver running
  changeMonitorStatusPosition(); //!!! After loading the saved layer, you must execute.
  monitorsSetScale();
  */
  setCookie('zmMontageLayout', layout_id);
} // end function selectLayout(element)

/*
* objInput: object <input>
*/
function setInputed(objInput, value) {
  let option;

  for (var i=0; i<objSel.options.length; i++) {
    option = objSel.options[i];
    if (option.value == value) {
      option.selected = true;
      $j(objSel).trigger("chosen:updated");
      return;
    }
  }
}

/*
 * called when the scaleControl select element is changed
 */
function changeScale() { //Not used
/*  const scale = $j('#scale').val();
  if (parseInt(scale) == 0) {
    elementResize(true); //Clear
  } else {
    elementResize();
  }

  //selectLayout(freeform_layout_id); // Will also clear width and height IgorA100 ВАЖНО ! Пока мешает нам
  $j('#scale').val(scale);
  setCookie('zmMontageScale', scale);
  setCookie('zmMontageWidth', 'auto');
  setCookie('zmMontageHeight', 'auto');
  $j('#width').val('auto');
  $j('#height').val('auto');

  monitorsSetScale();
*/}

/*
* objSel: object <select>
*/
function getSelected(objSel) {
  return (objSel.selectedIndex != -1) ? objSel.options[objSel.selectedIndex].value : '';
}

/*
* objSel: object <select>
*/
function getSelectedMultiple(objSel) {
  var result = [];
  const options = objSel && objSel.options;
  var opt;

  for (var i=0, iLen=options.length; i<iLen; i++) {
    opt = options[i];
    if (opt.selected) {
      result.push(opt.value || opt.text);
    }
  }
  return result;
}

/*
* Supports Multiple too
* objSel: object <select>
* value: String or array. Object is not allowed.
*/
function setSelected(objSel, value) {
  if (!value) return;
  var newValue =[]; 
  if (typeof value === 'string') {
    newValue.push(value);
  } else {
    newValue = value;
  }

  for (let i=0; i<objSel.options.length; i++) {
    const option = objSel.options[i];
    if (newValue.indexOf(option.value) != -1) {
      option.selected = true;
    }
  }
  $j(objSel).trigger("chosen:updated");
}

/*
* objSel: object <select>
*/
function cancelSelected(objSel) {
  objSel.value = 0;
  $j(objSel).trigger("chosen:updated");
}

function setSelectedRatioForAllMonitors(value) {
  $j('.select-ratio').each(function f() {
    setSelected(this, value);
  });
}

/*Called from a form*/
function changeRatioForAll() {
  const value = getSelected(document.getElementById("ratio"));

  setCookie('zmMontageRatioForAll', value);
  setSelectedRatioForAllMonitors(value);
  setTriggerChangedMonitors();
  waitingMonitorsPlaced('changeRatio'); //IgorA100 ВАЖНО!!! Возможно это не нужно! Но если убрать, то нарушается сортировка !
  //Из за этого происходит ДВА раза вызов initGridStack() при открытии страницы
}

/*Called from a form*/
function changeRatio(el) {
  const objSelect = el.target;

  checkRatioForAllMonitors();
  setTriggerChangedMonitors(stringToNumber(objSelect.id));
}

/*
* Checks ratio for all monitors.
* If the ratio is the same, set it in the main Select.
* Otherwise clears the selected value in the main Select.
*/
function checkRatioForAllMonitors() {
  let prev_value = '';
  let allRatiosSame = getSelected(document.getElementById("ratio"));
  if (!allRatiosSame) {
    //Ratio in Select was not set. Let's install it by default.
    setSelected(document.getElementById("ratio"), defaultPresetRatio);
    allRatiosSame = defaultPresetRatio;
  }

  $j('.select-ratio').each(function() {
    const curr_value = getSelected(this);
    if (prev_value == '') {
      prev_value = curr_value;
    }
    if (curr_value != prev_value) {
      allRatiosSame = false;
      return;
    }
  });

  if (allRatiosSame) {
    setSelected(document.getElementById("ratio"), prev_value);
  } else {
    cancelSelected(document.getElementById("ratio"));
  }
}

/*
* IMPORTANT Изменяет высоту блока <img id="liveStreamX" 
*/
function setRatioForMonitor(objStream, id=null) {
  if (!id) {
    id = stringToNumber(objStream.id);
  }
  const value = getSelected(document.getElementById("ratio"+id));
  const currentMonitor = monitors.find((o) => {
    return parseInt(o["id"]) === id;
  });

  if (!currentMonitor) {
    console.log(`Monitor with ID=${id} not found in 'monitors' object.`);
    return;
  }

  var ratio;
  if (value == 'real') {
    ratio = (currentMonitor.width / currentMonitor.height > 1) ? currentMonitor.width / currentMonitor.height : currentMonitor.height / currentMonitor.width;
  } else {
    const partsRatio = value.split(':');
    ratio = (value == 'auto') ? averageMonitorsRatio : partsRatio[0]/partsRatio[1];
  }

  const height = (currentMonitor.width / currentMonitor.height > 1) ? (objStream.clientWidth / ratio) /* landscape */ : (objStream.clientWidth * ratio);
  if (!height) {
    console.log("0 height from ", currentMonitor.width, currentMonitor.height, (currentMonitor.width / currentMonitor.height > 1), objStream.clientWidth / ratio);
  } else {
    objStream.style['height'] = height + 'px';
    objStream.parentNode.style['height'] = height + 'px';
  }
}

function toGrid(value) { //Not used
/*  return Math.round(value / 80) * 80;*/
}

// Makes monitors draggable.
function edit_layout(button) {
  mode = EDITING;
  $j('.grid-stack-item-content').addClass('modeEditingMonitor');
  objGridStack.enable(); //Enable move
  // objGridStack.float(true);

  $j('.btn-view-watch').addClass('hidden');
  $j('.btn-view-event').addClass('hidden');
  $j('.btn-edit-monitor').addClass('hidden');
  $j('.btn-fullscreen').addClass('hidden');

  zmPanZoomDestroy();

  $j('#SaveLayout').show();
  $j('#EditLayout').hide();

  const layout = layouts[document.getElementById('zmMontageLayout').value];
  if (user.Id && (layout.UserId == 0 || layout.UserId != user.Id)) {
    alert('You may not edit this layout, but you can create a new one from it. Please give it a name.');
  }
} // end function edit_layout

function save_layout(button) {
  const form = button.form;
  let name = form.elements['Name'].value;
  const layout = layouts[form.zmMontageLayout.value];

  if (!name) {
    name = form.elements['zmMontageLayout'].options[form.elements['zmMontageLayout'].selectedIndex].text;
    if (isPresetLayout(name)) {
      alert('You cannot edit the built in layouts.  Please give the layout a new name.');
      return;
    } else if (user.Id && (layout.UserId != user.Id) && !canEdit['System'] && (name != layout.Name)) {
      alert('You cannot edit someone else\'s layouts.  Please give the layout a new name.');
      return;
    }
  } else if (isPresetLayout(name)) {
    alert('You cannot use that name. It conflicts with the built in layouts.  Please give the layout a new name.');
    return;
  }

  mode = VIEWING;

  const Positions = {};
  Positions['gridStack'] = objGridStack.save(false, false);
  Positions['monitorStatusPosition'] = $j('#monitorStatusPosition').val(); //Not yet used when reading Layout
  Positions['monitorRatio'] = {};
  $j('.select-ratio').each(function f() {
    Positions['monitorRatio'][stringToNumber(this.id)] = getSelected(this);
  });
  form.Positions.value = JSON.stringify(Positions, null, '  ');
  $j('#action').attr('value', 'Save');
  form.submit();
} // end function save_layout

function cancel_layout(button) {
  mode = VIEWING;
  //$j(monitors_ul).removeClass('modeEditingMonitor');
  $j('.grid-stack-item-content').removeClass('modeEditingMonitor');
  objGridStack.disable(); //Disable move
  $j('.btn-view-watch').removeClass('hidden');
  $j('.btn-view-event').removeClass('hidden');
  $j('.btn-edit-monitor').removeClass('hidden');
  $j('.btn-fullscreen').removeClass('hidden');

  if (panZoomEnabled) {
    $j('.zoompan').each( function() {
      zmPanZoom.action('enable', {obj: this}); //Enable zoom and pan
    });
  }

  $j('#SaveLayout').hide();
  $j('#EditLayout').show();
  for ( let i = 0, length = monitors.length; i < length; i++ ) {
    const monitor = monitors[i];
    monitor.setup_onclick(handleClick);
  };
  selectLayout();
}

function delete_layout(button) {
  if (!canEdit.System) {
    enoperm();
    return;
  }
  if (!document.getElementById('deleteConfirm')) {
    // Load the delete confirmation modal into the DOM
    $j.getJSON(thisUrl + '?request=modal&modal=delconfirm', {
      key: 'ConfirmDeleteLayout',
    })
        .done(function(data) {
          insertModalHtml('deleteConfirm', data.html);
          manageDelConfirmModalBtns();
          $j('#deleteConfirm').modal('show');
        })
        .fail(function(jqXHR) {
          console.log('error getting delconfirm', jqXHR);
          logAjaxFail(jqXHR);
        });
    return;
  } else {
    $j('#deleteConfirm').modal('show');
  }
} // end function delete_layout

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById('delConfirmBtn').addEventListener('click', function onDelConfirmClick(evt) {
    document.getElementById('delConfirmBtn').disabled = true; // prevent double click
    if (!canEdit.Monitors) {
      enoperm();
      return;
    }
    evt.preventDefault();

    const form = $j('#btnDeleteLayout')[0].form;
    $j('#action').attr('value', 'Delete');
    form.submit();
  });

  // Manage the CANCEL modal button
  document.getElementById('delCancelBtn').addEventListener('click', function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

function reloadWebSite(ndx) {
  document.getElementById('imageFeed'+ndx).innerHTML = document.getElementById('imageFeed'+ndx).innerHTML;
}

function takeSnapshot() {
  for (let i = 0, length = monitors.length; i < length; i++) {
    monitors[i].kill();
  }
  const monitor_ids = monitorData.map((monitor)=>{
    return monitor.id;
  });
  post('?view=snapshot', {'action': 'create', 'monitor_ids[]': monitor_ids});
}

function handleClick(evt) {
  evt.preventDefault();
  var monitorId;

  // We are looking for an object with an ID, because there may be another element in the button.
  const obj = evt.target.id ? evt.target : evt.target.parentElement;

  if (mode == EDITING || obj.className.includes('btn-zoom-out') || obj.className.includes('btn-zoom-in')) return;
  if (obj.className.includes('btn-view-watch')) {
    const el = evt.currentTarget;
    monitorId = el.getAttribute("data-monitor-id");
    const url = '?view=watch&mid='+monitorId;
    if (evt.ctrlKey) {
      window.open(url, '_blank');
    } else {
      window.location.assign(url);
    }
  } else if (obj.className.includes('btn-view-event')) {
    const el = evt.currentTarget;
    monitorId = el.getAttribute("data-monitor-id");
    const eventInfo = getEventInfoFromEventsTable({what: 'current', mid: monitorId});
    const fid = frameCalculationByTime(
      timeline.getCurrentTime(),
      eventInfo.start,
      eventInfo.end,
      eventInfo.frames,
    );
    const url = '?view=event&eid='+eventInfo.eventId+'&fid='+fid;
    if (evt.ctrlKey) {
      window.open(url, '_blank');
    } else {
      window.location.assign(url);
    }
  } else if (obj.className.includes('btn-edit-monitor')) {
    const el = evt.currentTarget;
    monitorId = el.getAttribute("data-monitor-id");
    const url = '?view=monitor&mid='+monitorId;
    if (evt.ctrlKey) {
      window.open(url, '_blank');
    } else {
      window.location.assign(url);
    }
  } else if (obj.className.includes('btn-fullscreen')) {
    if (document.fullscreenElement) {
      closeFullscreen();
    } else {
      openFullscreen(document.getElementById('monitor'+evt.currentTarget.getAttribute("data-monitor-id")));
    }
  }

  if (thisClickOnStreamObject(obj)) {
    monitorId = stringToNumber(obj.getAttribute('id'));
    zmPanZoom.click(monitorId);
  }
}

function startMonitors() {
  for (let i = 0, length = monitors.length; i < length; i++) {
    const monitor = monitors[i];
    if (monitor.capturing == 'None') continue;
    // Why are we scaling here instead of in monitorstream?
    const obj = document.getElementById('liveStream'+monitor.id);
    if (obj) {
      if (obj.src) {
        const url = new URL(obj.src);
        let scale = parseInt(obj.clientWidth / monitor.width * 100);
        if (scale > 100) scale = 100;
        url.searchParams.set('scale', scale);
        obj.src = url;
      }
    } else {
      console.log(`startMonitors NOT FOUND ${'liveStream'+monitor.id}`);
    }

    const isOut = isOutOfViewport(monitor.getElement());
    if (!isOut.all) {
      monitor.start();
    }
    if ((monitor.type == 'WebSite') && (monitor.refresh > 0)) {
      setInterval(reloadWebSite, monitor.refresh*1000, i);
    }
  }
}

function stopAllMonitors() {
  for (let i = 0, length = monitors.length; i < length; i++) {
    if (typeof(monitors[i]) === 'undefined') continue;
    if (!monitorDisplayedOnPage(monitors[i].id)) continue;
    //monitors[i].stop();
    monitors[i].kill();
    //monitors[i].streamCommand(CMD_QUIT);
  }
  monitors.length = 0;
}

function pauseMonitors() {
  for (let i = 0, length = monitors.length; i < length; i++) {
    monitors[i].pause();
  }
}

function playMonitors() {
  for (let i = 0, length = monitors.length; i < length; i++) {
    monitors[i].play();
  }
}

function elementResize(clear = false) { //Only used when trying to apply "changeScale". It will be deleted in the future. We will make the container for the IMG of a fixed height with a Scale different from 0
/*  var heightImageFeed = "";
  const scale = $j('#scale').val();

  $j('[id ^= "liveStream"]').each(function(){
    if (!clear) {
      if (scale != 0) {
        const imageFeed = $j(this).closest('.imageFeed');
        const w = $j(this).css('width');
        const h = $j(this).css('height');
        const ratio = imageFeed.attr('data-width') / imageFeed.attr('data-height');
        heightImageFeed = imageFeed[0].offsetWidth / ratio + "px";
      }
    }
    $j(this).closest('.imageFeed').css('height', heightImageFeed);
  });
*/
}

function windowResize() { //Only used when trying to apply "changeScale". It will be deleted in the future.
  elementResize(true); //Clear
}

function buildRatioSelect(objSelect) {
  if (typeof(objSelect) === 'undefined') return;
  objSelect.options.length = 0;
  presetRatio.forEach(function(value, key) {
    if (key == "auto") {
      objSelect.options[objSelect.options.length] = new Option("Auto", key);
    } else if (key == "real") {
      objSelect.options[objSelect.options.length] = new Option("Real", key);
    } else {
      objSelect.options[objSelect.options.length] = new Option(key+" ("+value+")", key);
    }
  });
  $j(objSelect).trigger("chosen:updated");
}

function fullscreenchanged(event) {
  const objBtn = $j('.btn-fullscreen');
  if (document.fullscreenElement) {
    //console.log(`Element: ${document.fullscreenElement.id} entered fullscreen mode.`);
    if (objBtn) {
      objBtn.attr('title', 'Close full screen');
      objBtn.children('.material-icons').html('fullscreen_exit');
    }
  } else {
    if (objBtn) {
      objBtn.attr('title', 'Open full screen');
      objBtn.children('.material-icons').html('fullscreen');
    }
    //Sometimes the positioning is not correct, so it is better to reset Pan & Zoom
    const monitorId = stringToNumber(event.target.id);
    if (monitorId && zmPanZoom.panZoom[monitorId]) {
      zmPanZoom.panZoom[monitorId].reset();
    } else {
      console.error("No panZoom found for ", monitorId, event);
    }
  }
} // end function fullscreenchanged(event)

function calculateAverageMonitorsRatio(arrRatioMonitors) {
  //Let's calculate the average Ratio value for the displayed monitors
  let total = 0;
  for (var i = 0; i < arrRatioMonitors.length; i++) {
    total += arrRatioMonitors[i];
  }
  const avg = total / arrRatioMonitors.length;

  // We create an array of aspect ratios from the basic set of objects and find the closest avg Ratio value in it
  const arr = [];
  presetRatio.forEach(function(value, key) {
    arr.push(value);
  });
  averageMonitorsRatio = arr.reduce(function(prev, curr) {
    return (Math.abs(curr - avg) < Math.abs(prev - avg) ? curr : prev);
  });
}

function initPage() {
  if (getCookie('zmMontageMode') == 'inRecording') {
    setInRecordingMode();
  } else {
    setLiveMode();
  }

  // +++ For MontageReview
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
  $j('#fieldsTable input, #fieldsTable select').each(function(index) {
    const el = $j(this);
    if (el.hasClass('datetimepicker')) {
      //el.datetimepicker({timeFormat: "HH:mm:ss", dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false, onClose: changeDateTime, todayHighlight: false,});
      el.datetimepicker({timeFormat: "HH:mm:ss", dateFormat: "yy-mm-dd", maxDate: new Date(), constrainInput: false, onClose: changeDateTime, todayHighlight: false,});
    } else if (el.hasClass('datepicker')) {
      //el.datepicker({dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false, onClose: changeDateTime});
      el.datepicker({dateFormat: "yy-mm-dd", maxDate: new Date(), constrainInput: false, onClose: changeDateTime});
    } else {

    }
  });

  $j('#StartDateTime, #EndDateTime').click( handlerClickDateTime );
  //$j('#filterArchived, #filterTags, #filterNotes').click( handlerClickOtherFilters );
  $j('#filterArchived, #filterTags, #filterNotes').change( handlerClickOtherFilters );

  // --- For MontageReview

  // Creating a ResizeObserver Instance
  observer = new ResizeObserver((objResizes) => {
    const blockContent = document.getElementById('content');
    const currentScrollBbarExists = blockContent.scrollHeight > blockContent.clientHeight;
    if (scrollBbarExists === null) {
      scrollBbarExists = currentScrollBbarExists;
    }
    if (currentScrollBbarExists != scrollBbarExists) {
      scrollBbarExists = currentScrollBbarExists;
      return;
    }
    objResizes.forEach((obj) => {
      //const id = stringToNumber(obj.target.id);
      //if (mode != EDITING && !changedMonitors.includes(id)) {
      if (mode != EDITING) {
        setTriggerChangedMonitors(stringToNumber(obj.target.id));
        //changedMonitors.push(id);
      }
    });
  });

  $j(document).on('keyup keydown', function(e) {
    shifted = e.shiftKey ? e.shiftKey : e.shift;
    ctrled = e.ctrlKey;
    alted = e.altKey;
  });

  document.onvisibilitychange = () => {
    if (document.visibilityState === "hidden") {
      TimerHideShow = clearTimeout(TimerHideShow);
      TimerHideShow = setTimeout(function() {

      //if (onvisibilitychangeTriggered) return;
      //onvisibilitychangeTriggered = true;
        //Stop monitors when closing or hiding page
        if (montageMode == 'Live') {
          for (let i = 0, length = monitors.length; i < length; i++) {
            //monitors[i].streamCmdTimer = clearInterval(monitors[i].streamCmdTimer);
            if (!monitorDisplayedOnPage(monitors[i].id)) continue;
//console.log("*********monitors[i].kill() " + monitors[i].id + " in LIVE mode");
            monitors[i].kill();
          }
        } else { //inRecording
          if (eventsPlay) {
            stopAllEvents();
            eventsPlay = true;
          }
        }
      }, 15*1000);
    } else {
      TimerHideShow = clearTimeout(TimerHideShow);
      //if (!onvisibilitychangeTriggered) return;
      //onvisibilitychangeTriggered = false;
      //Start monitors when show page
      if (montageMode == 'Live') {
        if ((!ZM_WEB_VIEWING_TIMEOUT) || (idle < ZM_WEB_VIEWING_TIMEOUT)) {
          for (let i = 0, length = monitors.length; i < length; i++) {
            const monitor = monitors[i];
            const isOut = isOutOfViewport(monitor.getElement());
            if ((!isOut.all) && !monitor.started && monitorDisplayedOnPage(monitor.id)) {
              console.log("*********monitor.start() " + i + " in LIVE mode");
              monitor.start();
            }
          } // end foreach monitor
        } // end if not AYSW
      } else { //inRecording
        if(eventsPlay) {
console.log("*********startAllEvents in RECORDING mode");
          startAllEvents();
        }
      }
    }
  };
  document.getElementById('timelinediv').onclick = function (event) {
    var props = timeline.getEventProperties(event)
    console.log("EEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE", props);
  }

  setInterval(() => { //Updating GridStack resizeToContent, Scale & Ratio
  //return;
    if (changedMonitors.length > 0) {
//console.log("changedMonitors.length", changedMonitors.length);
      changedMonitors.slice().reverse().forEach(function(item, index, object) {
/*В*///console.log("changedMonitors.item", item);
        //const img = document.getElementById('liveStream'+item);
        //const img = (document.getElementById('liveStream'+item)) ? document.getElementById('liveStream'+item) : document.getElementById('evtStream'+item);
        const img = getStream(item);
        if (!img) { //IgorA100 ВРЕМЕННО для просмотре в записи
          changedMonitors.splice(object.length - 1 - index, 1);
          return;
        }
//console.log("changedMonitors.item", item, "<img.offsetHeight=>", img.offsetHeight);
        //if (1 == 1) { //А может надо типа так, т.е. безусловно....
        if (img.offsetHeight > 20 && objGridStack) { //Required for initial page loading
        //if (img.complete) { //Required for initial page loading ВАЖНО! Попробуем так... Не работает для тега <video>
        //https://scottiestech.info/2022/11/08/javascript-how-to-detect-when-an-image-is-loaded/
          setRatioForMonitor(img, item); // ВАЖНО!!! Изменяет высоту блока <img id="liveStream5" 
          if (objGridStack) objGridStack.resizeToContent(document.getElementById('m'+item));
          changedMonitors.splice(object.length - 1 - index, 1);
          if (montageMode == 'Live') {
            monitorsSetScale(item);  //IgorA100 Перенесем сюда, т.к. при переключении режимов бывает что currentMonitor не определен (возможно monitors еще не собран.) !!!
          } else {
            monitorsEventSetScale(item);
          }
          //if (montageMode == 'Live') monitorsSetScale(item); //IgorA100 Перенесем сюда, т.к. при переключении режимов бывает что currentMonitor не определен (возможно monitors еще не собран.) !!!
        }
        //monitorsSetScale(item);
      });
    }
  }, 200);

  document.addEventListener('scrollend', on_scroll); // for non-sticky
  document.getElementById('content').addEventListener('scrollend', on_scroll);
  window.addEventListener('resize', on_scroll);
} // end initPage

function initPageLive() {
  monitors_ul = $j('#monitors');

  //For <select> in header
  buildRatioSelect(document.getElementById("ratio"));

  //For <select> in each monitor
  $j('.grid-monitor').each(function() {
    buildRatioSelect($j(this).find("#ratio"+stringToNumber(this.id))[0]); //For each monitor
  });

  $j("#hdrbutton").click(function() {
    $j("#flipMontageHeader").slideToggle("slow");
    $j("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
    setCookie('zmMontageHeaderFlip', $j('#hdrbutton').hasClass('glyphicon-menu-up') ? 'up' : 'down');
  });
  if (getCookie('zmMontageHeaderFlip') == 'down') {
    // The chosen dropdowns require the selects to be visible, so once chosen has initialized, we can hide the header
    $j("#flipMontageHeader").slideToggle("fast");
    $j("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
  }
  //if (getCookie('zmMontageLayout')) { //This is implemented in montage.php And the cookies may contain the number of a non-existent Layouts!!!
  //  $j('#zmMontageLayout').val(getCookie('zmMontageLayout'));
  //}

  $j(".grid-monitor").hover(
      //Displaying "Scale" and other buttons at the top of the monitor image
      function() {
        const id = stringToNumber(this.id);
        if ($j('#monitorStatusPosition').val() == 'showOnHover') {
          $j(this).find('#monitorStatus'+id).removeClass('hidden');
        }
        $j('#button_zoom' + id).stop(true, true).slideDown('fast');
        $j('#ratioControl' + id).stop(true, true).slideDown('fast');
        $j('#ratioControl' + id).css({ top: document.getElementById('btn-zoom-in' + id).offsetHeight + 10 + 'px' });
      },
      function() {
        const id = stringToNumber(this.id);
        if ($j('#monitorStatusPosition').val() == 'showOnHover') {
          $j(this).find('#monitorStatus'+id).addClass('hidden');
        }
        $j('#button_zoom' + id).stop(true, true).slideUp('fast');
        $j('#ratioControl' + id).stop(true, true).slideUp('fast');
      }
  );

  //const arrRatioMonitors = [];
  buildMonitors(arrRatioMonitors);
  calculateAverageMonitorsRatio(arrRatioMonitors);

  $j(window).on('resize', windowResize); //Only used when trying to apply "changeScale". It will be deleted in the future.
  document.addEventListener("fullscreenchange", fullscreenchanged);

  // If you click on the navigation links, shut down streaming so the browser can process it
  document.querySelectorAll('#main-header-nav a').forEach(function(el) {
    el.onclick = function() {
      for (let i = 0, length = monitors.length; i < length; i++) {
        if (monitors[i]) monitors[i].kill();
      }
    };
  });

  if (ZM_WEB_VIEWING_TIMEOUT > 0) {
    $j('body').on('mousemove', function() {
      idle = 0;
    });
    setInterval(function() {
      idle += 10;
      if (idle > ZM_WEB_VIEWING_TIMEOUT) {
        for (let i=0, length = monitors.length; i < length; i++) {
          const monitor = monitors[i];
          const objStream = getStream(monitor.id);
          if (!objStream) continue;
          if (objStream.src) {
            monitor.pause();
          } else {
            console.log("It is not possible to pause a monitor with ID='"+monitor.id+"'"+" because it does not have the SRC attribute.");
          }
        }
        let ayswModal = $j('#AYSWModal');
        if (!ayswModal.length) {
          $j.getJSON('?request=modal&modal=areyoustillwatching')
              .done(function(data) {
                ayswModal = insertModalHtml('AYSWModal', data.html);
                ayswModal.on('hidden.bs.modal', function() {
                  for (let i=0, length = monitors.length; i < length; i++) monitors[i].play();
                  idle = 0;
                });
                ayswModal.modal('show');
              })
              .fail(logAjaxFail);
        } else {
          ayswModal.modal('show');
        }
        idle = 0;
      }
    }, 10*1000);
  }
  selectLayout();
  $j('#monitors').removeClass('hidden-shift');
  changeMonitorStatusPosition();
  zmPanZoom.init();

  // Registering an observer on an element
  $j('[id ^= "liveStream"]').each(function() {
    observer.observe(this);
  });

  $j('#monitors').removeClass('hidden-shift');
  //You can immediately call startMonitors() here, but in this case the height of the monitor will initially be minimal, and then become normal, but this is not pretty.
  //Check if the monitor arrangement is complete
  waitingMonitorsPlaced('startMonitors'); //???Не уверен что требуется, если это используем в "changeRatioForAll"...
} // end initPageLive

function initPageReview() {
  //$j('#pauseBtn').hide();
  monitors_ul = $j('#monitors');

  /** +++ **"??????????ЭТОТ КОД ВНАЧАЛЕ ВЫПОЛНЯТЬ НЕЛЬЗЯ, Т.К. элементы Input еще не будет созданы фильтром!!! ****/
  var currentStartDateTime = selectStartDateTime.value;
  var currentEndDateTime = selectStartDateTime.value;

  //Нам нужно получить именно такой формат: '2024-06-20 19:01:41' ИЗ '2024-06-20T18:03:09.890Z'
  if (!currentStartDateTime) selectStartDateTime.value = dateTimeToISOLocal(new Date(), {period: 'Date', offset: -1});
  if (!currentEndDateTime) selectEndDateTime.value = dateTimeToISOLocal(new Date());
  /** --- **"ЭТОТ КОД ВНАЧАЛЕ ВЫПОЛНЯТЬ НЕЛЬЗЯ, Т.К. элементы Input еще не будет созданы фильтром!!! ****/

  //For select in header
  buildRatioSelect(document.getElementById("ratio"));

  //For select in each monitor
  $j('.grid-monitor').each(function() {
    buildRatioSelect($j(this).find("#ratio"+stringToNumber(this.id))[0]); //For each monitor
  });

  $j("#hdrbutton").click(function() {
    $j("#flipMontageHeader").slideToggle("slow");
    $j("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
    setCookie('zmMontageHeaderFlip', $j('#hdrbutton').hasClass('glyphicon-menu-up') ? 'up' : 'down');
  });
  if (getCookie('zmMontageHeaderFlip') == 'down') {
    // The chosen dropdowns require the selects to be visible, so once chosen has initialized, we can hide the header
    $j("#flipMontageHeader").slideToggle("fast");
    $j("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
  }
  //if (getCookie('zmMontageLayout')) { //This is implemented in montage.php And the cookies may contain the number of a non-existent Layouts!!!
  //  $j('#zmMontageLayout').val(getCookie('zmMontageLayout'));
  //}

  $j(".grid-monitor").hover(
      //Displaying "Scale" and other buttons at the top of the monitor image
      function() {
        const id = stringToNumber(this.id);
        if ($j('#monitorStatusPosition').val() == 'showOnHover') {
          $j(this).find('#monitorStatus'+id).removeClass('hidden');
        }
        $j('#button_zoom' + id).stop(true, true).slideDown('fast');
        $j('#ratioControl' + id).stop(true, true).slideDown('fast');
      },
      function() {
        const id = stringToNumber(this.id);
        if ($j('#monitorStatusPosition').val() == 'showOnHover') {
          $j(this).find('#monitorStatus'+id).addClass('hidden');
        }
        $j('#button_zoom' + id).stop(true, true).slideUp('fast');
        $j('#ratioControl' + id).stop(true, true).slideUp('fast');
      }
  );

  //const arrRatioMonitors = [];
  buildMonitors(arrRatioMonitors);
  calculateAverageMonitorsRatio(arrRatioMonitors);

  $j(window).on('resize', windowResize); //Only used when trying to apply "changeScale". It will be deleted in the future.
  document.addEventListener("fullscreenchange", fullscreenchanged);

  // If you click on the navigation links, shut down streaming so the browser can process it
  document.querySelectorAll('#main-header-nav a').forEach(function(el) {
    el.onclick = function() {
      for (let i = 0, length = monitors.length; i < length; i++) {
        if (monitors[i]) monitors[i].kill();
      }
    };
  });

  selectLayout();
  $j('#monitors').removeClass('hidden-shift');
  changeMonitorStatusPosition();
  zmPanZoom.init();
  setSpeedForMonitors(parseFloat(speeds[speedIndex]) * 100);

  if (streamCmdTimer) streamCmdTimer = clearTimeout(streamCmdTimer);
  streamCmdTimer = setTimeout(streamQuery, streamTimeout);

  setSpeed(speedIndex);

  // Registering an observer on an element
  $j('[id ^= "evtStream"]').each(function() {
    observer.observe(this);
  });

  //You can immediately call startMonitors() here, but in this case the height of the monitor will initially be minimal, and then become normal, but this is not pretty.
  //Check if the monitor arrangement is complete
  //waitingMonitorsPlaced('startMonitors'); //???Не уверен что требуется, если это используем в "changeRatioForAll"...

  //Получаем дату самого первого события. 
  var getMinData = $j.getJSON(thisUrl, { 
    request: 'montage', 
    montage_mode: 'inRecording',
    MonitorsId: monitorsId,
    montage_action: 'getMinData',
  })
    .done(function(data) {
      startDateFirstEvent = data.message;
      initTimeline();
      ////console.log("startDateFirstEvent===>", startDateFirstEvent);
    })
    .fail(logAjaxFail);
} // end initPageReview

function buildMonitors(arrRatioMonitors) {
  monitors = new Array();
  monitorsId = new Array();
  movableMonitorData = [];
  eventsTable = []; // ??????  А НУЖНО ЛИ ЭТО ДЕЛАТЬ ??????
  var im = 0;
  for (let i = 0, length = monitorData.length; i < length; i++) {
    //Требуется проверить, отображается ли данный монитор, т.к. если нет событий для монитора, то и для просмотра записей монитора не будет на странице!
    //Или (для Live) если монитор отключен, то его не будет на странице
    if (!getStream(monitorData[i].id)) continue;
    const monitor = monitors[im] = new MonitorStream(monitorData[i]);
    monitor.setGridStack(objGridStack); // ВАЖНО! Разобраться для чего нужно.... From https://github.com/ZoneMinder/zoneminder/commit/19ea7339f496d5e1c7ecc40bdc57e44b8546256f 02-10-24
    const monitorId = monitor.id;
    monitorsId.push(monitorId);
    //Create a Ratio array for each monitor
    const r = monitor.width / monitor.height;
    arrRatioMonitors.push(r > 1 ? r : 1/r); //landscape or portret orientation

    monitor.setup_onclick(handleClick);

    if (montageMode == 'inRecording') {
      //Подготовим массив событий.
      clearEventDataInEventsTable({what: 'all', mid: monitorId});
      monitorCanvasObj[monitorId] = document.getElementById('canvas-monitor'+monitorId);
      if ( !monitorCanvasObj[monitorId] ) {
        console.log("Couldn't find DOM element for Monitor" + monitorId);
      } else {
        monitorCanvasCtx[monitorId] = monitorCanvasObj[monitorId].getContext('2d');
      }

      //writeTextCanvas(monitorId, 'No Event');
      writeTextCanvas(monitorId, 'No recording for this time', 0.4);
    }
    //Prepare the array.
    movableMonitorData[monitorId] = {'width': 0, 'stop': false};
    im++;
  }

  // Event listener for double click
  // ???ЭТО ЗДЕСЬ ПОКА КРИВО РАБОТАЕТ !!!!!
  //var elStream = document.querySelectorAll('[id ^= "liveStream"], [id ^= "evtStream"]');
  //var elStream = document.querySelectorAll('[id = "wrapperMonitor"]');
  var elStream = document.querySelectorAll('[id ^= "imageFeed"], [id ^= "monitorStatus"]');
  Array.prototype.forEach.call(elStream, (el) => {
    el.addEventListener('touchstart', doubleTouch);
    el.addEventListener('dblclick', doubleClickOnStream);
  });
  waitingRenderingTimeline();
}

/*
* Otherwise, the assignment of listening to click events on the Timeline may not work.
*/
function waitingRenderingTimeline() {
  const intervalWait = setInterval(() => {
    const elTimeline = document.querySelectorAll('.vis-panel');
    if (elTimeline && elTimeline.length > 0) {
      Array.prototype.forEach.call(elTimeline, (el) => {
        const internalElements = el.querySelectorAll("*");
        Array.prototype.forEach.call(internalElements, (intEl) => {
          ////console.log('intEl=>', intEl);
          intEl.addEventListener('touchstart', doubleTouch);
          intEl.addEventListener('dblclick', doubleClickOnStream);
        });
      });
      clearInterval(intervalWait);
    }
  }, 100);
}

function monitorDisplayedOnPage(id) {
  // Требуется проверить отображается ли монитор на странице.
  // Так-же (наверное, хотя при просмотре записи все мониторы должны отображаться) проверить и при просмотре в записи !
  // const frame = document.getElementById('imageFeed'+id);
  // return frame;
  return (getStream(id)) ? true : false;
}

function zmPanZoomDestroy() {
  // Turn off the onclick & disable panzoom on the image.
  for ( let i = 0, length = monitors.length; i < length; i++ ) {
    const monitor = monitors[i];
    if (!monitorDisplayedOnPage(monitor.id)) continue;
    if (montageMode == 'Live') monitor.disable_onclick();
    if (panZoomEnabled) {
      zmPanZoom.action('disable', {id: monitor.id}); //Disable zoom and pan
    }
  };
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
  //console.log(obj);
  //console.log("PARENT:", parentDim);
  //console.log("САМ:", obj.getBoundingClientRect());
  //console.log("H:", h);
  //if (h>30)
  //  parent.style.height = h+'px';
  //  console.log('panzoomzoom', event.detail) // => { x: 0, y: 0, scale: 1 }
  */
}

function on_scroll() {
  //For now, use only for live viewing.
  if (montageMode == 'inRecording') return;
  for (let i = 0, length = monitors.length; i < length; i++) {
    const monitor = monitors[i];
    const isOut = isOutOfViewport(monitor.getElement());
    if (!isOut.all) {
      if (!monitor.started) monitor.start();
    } else if (monitor.started) {
      //monitor.stop(); //НЕ РАБОТАЕТ...
      monitor.kill();
    }
  } // end foreach monitor
} // end function on_scsroll

function isOutOfViewport(elem) {
  // Get element's bounding
  const bounding = elem.getBoundingClientRect();
  const headerHeight = (parseInt(ZM_WEB_NAVBAR_STICKY) == 1) ? document.getElementById('navbar-container').offsetHeight + document.getElementById('header').offsetHeight : 0;
  //console.log( 'top: ' + bounding.top + ' left: ' + bounding.left + ' bottom: '+bounding.bottom + ' right: '+bounding.right);

  // Check if it's out of the viewport on each side
  const out = {};
  out.topUp = (bounding.top < headerHeight);
  out.topDown = ( bounding.top > (window.innerHeight || document.documentElement.clientHeight) );
  out.top = (out.topUp || out.topDown);
  out.left = (bounding.left < 0) || (bounding.left > (window.innerWidth || document.documentElement.clientWidth));
  out.bottomUp = (bounding.bottom < headerHeight);
  out.bottomDown = (bounding.bottom > (window.innerHeight-headerHeight || document.documentElement.clientHeight-headerHeight) );
  out.bottom = (out.bottomUp || out.bottomDown);
  out.right = (bounding.right > (window.innerWidth || document.documentElement.clientWidth) ) || (bounding.right < 0);
  out.any = out.top || out.left || out.bottom || out.right;
  out.all = (out.topUp && out.bottomUp ) || (out.topDown && out.bottomDown ) || (out.left && out.right);
  //console.log( 'top: ' + out.top + ' left: ' + out.left + ' bottom: '+out.bottom + ' right: '+out.right);

  return out;
};

function formSubmit(form) {
  console.log("Killing streaming");
  for (let i = 0, length = monitors.length; i < length; i++) {
    if (monitors[i]) {
      monitors[i].kill();
    }
  }
  return true;
}

function watchFullscreen() {
  const content = document.getElementById('content');
  openFullscreen(content);
}

/* FOR TEST ! */
function clickinitGridStack() {
  objGridStack.compact('list', true); //When reading a saved custom Layout, the monitors are not always positioned as before saving. The problem is in GridStack. Let's leave the option only for preset layout. Without this option, there may be problems with sorting monitors.
  /*
  if (objGridStack) {
    objGridStack.destroy(false);
  }
  initGridStack();
  */
}

function initGridStack(grid=null) {
  const opts = {
    margin: 0,
    cellHeight: '1px', //Required for correct use of objGridStack.resizeToContent
    //sizeToContent: true, // default to make them all fit //Самое медленное !!!!!!!!!
    resizable: {handles: 'all'}, // do all sides
    float: false,
    disableDrag: true,
    disableResize: true,
    column: layoutColumns,
  };

  if (grid) {
    objGridStack = GridStack.init({...opts}).load(grid, false);
    // When loading, we leave all monitors (according to the filters), and not just those that were saved!
  } else {
    objGridStack = GridStack.init({...opts});
    objGridStack.compact('list', true); //When reading a saved custom Layout, the monitors are not always positioned as before saving. The problem is in GridStack. Let's leave the option only for preset layout. Without this option, there may be problems with sorting monitors.
  }

  addEventsGridStack(objGridStack);
};

function addEventsGridStack(grid, id) {
  //let g = (id !== undefined ? 'grid' + id + ' ' : '');
  grid.on('change', function(event, items) {
    /* Occurs when widgets change their position/size due to constrain or direct changes */
    //items.forEach(function(item) {
    //  const currentMonitorId = stringToNumber(item.id); //We received the ID of the monitor whose size was changed
    //  //setTriggerChangedMonitors(currentMonitorId);
    //  //monitorsSetScale(currentMonitorId);
    //  setTriggerChangedMonitors(currentMonitorId);
    //});

    elementResize();
  })
      .on('added removed', function(event) {
        //let str = '';
        //items.forEach(function(item) { str += ' (' + item.x + ',' + item.y + ' ' + item.w + 'x' + item.h + ')'; });
        //console.log("INFO==>", g + event.type + ' ' + items.length + ' items (x,y w h):' + str );
      })
      .on('enable', function(event) {
        //let grid = event.target;
        //console.log("INFO==>", g + 'enable');
      })
      .on('disable', function(event) {
        //let grid = event.target;
        //console.log("INFO==>", g + 'disable');
      })
      .on('dragstart', function(event, el) {
        //let node = el.gridstackNode;
        //let x = el.getAttribute('gs-x'); // verify node (easiest) and attr are the same
        //let y = el.getAttribute('gs-y');
        //let grid = event.target;
        //objGridStack.float('false');
      })
      .on('drag', function(event, el) {
        //let node = el.gridstackNode;
        //let x = el.getAttribute('gs-x'); // verify node (easiest) and attr are the same
        //let y = el.getAttribute('gs-y');
        //console.log("INFO==>", g + 'drag ' + (node.content || '') + ' pos: (' + node.x + ',' + node.y + ') = (' + x + ',' + y + ')');
      })
      .on('dragstop', function(event, el) {
        /*After the object has been moved*/
        //let node = el.gridstackNode;
        //let x = parseInt(el.getAttribute('gs-x')) || 0; // verify node (easiest) and attr are the same
        //let y = parseInt(el.getAttribute('gs-y')) || 0;
        // or all values...
        //let GridStackNode = el.gridstackNode; // {x, y, width, height, id, ....}
        //console.log("INFO==>", g + 'dragstop ' + (node.content || '') + ' pos: (' + node.x + ',' + node.y + ') = (' + x + ',' + y + ')');
      })
      .on('dropped', function(event, previousNode, newNode) {
        //if (previousNode) {
        //  console.log("INFO==>", g + 'dropped - Removed widget from grid:', previousNode);
        //}
        //if (newNode) {
        //  console.log("INFO==>", g + 'dropped - Added widget in grid:', newNode);
        //}
      })
      .on('resizestart', function(event, el) {
        elementResize(true); //Clear. Only used when trying to apply "changeScale". It will be deleted in the future.
        //let node = el.gridstackNode;
        //let rec = el.getBoundingClientRect();
        //console.log("INFO==>", `${g} resizestart ${node.content || ''} size: (${node.w}x${node.h}) = (${Math.round(rec.width)}x${Math.round(rec.height)})px`);
        //let grid = event.target;
        //objGridStack.float('false');
      })
      .on('resize', function(event, el) {
        //let node = el.gridstackNode;
        //let rec = el.getBoundingClientRect();
        //console.log("INFO==>", `${g} resize ${node.content || ''} size: (${node.w}x${node.h}) = (${Math.round(rec.width)}x${Math.round(rec.height)})px`);
      })
      .on('resizestop', function(event, el) {
        //const width = parseInt(el.getAttribute('gs-w')) || 0;
        // or all values...
        const node = el.gridstackNode; // {x, y, width, height, id, ....}
        //let rec = el.getBoundingClientRect();
        //console.log("INFO==>", `${g} resizestop ${node.content || ''} size: (${node.w}x${node.h}) = (${Math.round(rec.width)}x${Math.round(rec.height)})px`);

        const currentMonitorId = stringToNumber(node.el.id); //We received the ID of the monitor whose size was changed
        const currentMonitor = monitors.find((o) => {
          return parseInt(o["id"]) === currentMonitorId;
        });
        //currentMonitor.setScale(0, node.el.offsetWidth + 'px', null, false);
        setTriggerChangedMonitors(currentMonitorId); //For mode=EDITING
        currentMonitor.setScale(0, node.el.offsetWidth + 'px', null, {resizeImg: false});
      });
}

function panZoomIn(el) {
  zmPanZoom.zoomIn(el);
}

function panZoomOut(el) {
  zmPanZoom.zoomOut(el);
}

function changeStreamQuality() {
  const streamQuality = $j('#streamQuality').val();
  setCookie('zmStreamQuality', streamQuality);
  if (montageMode == 'Live') {
    monitorsSetScale();
  } else {
    monitorsEventSetScale();
  }
}

function getStream(id) {
  if (!id) return null;
  const liveStream = document.getElementById('liveStream'+id);
  return (liveStream) ? liveStream : document.getElementById('evtStream'+id);
}

function monitorsEventSetScale(id=null) {
  const streamQuality = $j('#streamQuality').val();
  if (id) {
    const stream = getStream(id);
    const panZoomScale = (panZoomEnabled && zmPanZoom.panZoom[id] ) ? zmPanZoom.panZoom[id].getScale() : 1;
    if (stream) {
      if (stream.src) {
        const url = new URL(stream.src);
        const connkey = url.searchParams.get('connkey');
        if (!connkey) return;
        const eventId = url.searchParams.get('event');
        const eventInfo = getEventInfoFromEventsTable({what: 'current', eid: eventId});

        if (!eventInfo || eventInfo.status != 'started') return; //При первоначальной загрузке страницы таблица eventsTable еще пустая, т.к. она заполняется по двойному клику на Timeline. А так-же если мониотор уже успели остановить.
        var scale = stream.clientWidth / eventInfo.width * 100 * panZoomScale;
        scale += scale/100*streamQuality;
        scale = parseInt(scale);
        if (scale > 100) scale = 100;

        const monitor = monitors.find((o) => {
          return parseInt(o["id"]) === id;
        });

        streamReq({
          command: CMD_SCALE,
          monitorId: id,
          scale: scale,
          connkey: connkey,
          eventId: eventId,
          monitorUrl: monitor.url
        });

        //url.searchParams.set('scale', scale);
        //stream.src = url;
      }
    }
  } else {
    // For all monitors
    for ( let i = 0, length = monitors.length; i < length; i++ ) {
      const id = monitors[i].id;
      const panZoomScale = (panZoomEnabled && zmPanZoom.panZoom[id] ) ? zmPanZoom.panZoom[id].getScale() : 1;
      if (!monitorDisplayedOnPage(id)) continue;
      const stream = getStream(id);
      if (stream) {
        if (stream.src) {
          const url = new URL(stream.src);
          const connkey = url.searchParams.get('connkey');
          if (!connkey) continue;
          const eventId = url.searchParams.get('event');
          const eventInfo = getEventInfoFromEventsTable({what: 'current', eid: eventId});

          if (!eventInfo || eventInfo.status != 'started') continue; //При первоначальной загрузке страницы таблица eventsTable еще пустая, т.к. она заполняется по двойному клику на Timeline. А так-же если мониотор уже успели остановить.
          var scale = stream.clientWidth / eventInfo.width * 100 * panZoomScale;
          scale += scale/100*streamQuality;
          scale = parseInt(scale);
          if (scale > 100) scale = 100;

          streamReq({
            command: CMD_SCALE,
            monitorId: id,
            scale: scale,
            connkey: connkey,
            eventId: eventId,
            monitorUrl: monitors[i].url
          });

          //url.searchParams.set('scale', scale);
          //stream.src = url;
        }
      }
    }
  }
}

function monitorsSetScale(id=null) {
  // This function will probably need to be moved to the main JS file, because now used on Watch & Montage pages
  if (id || typeof monitorStream !== 'undefined') {
    //monitorStream used on Watch page.
    if (typeof monitorStream !== 'undefined') {
      var currentMonitor = monitorStream;
    } else {
      var currentMonitor = monitors.find((o) => {
        return parseInt(o["id"]) === id;
      });
    }
    const el = getStream(id);
    const panZoomScale = (panZoomEnabled && zmPanZoom.panZoom[id] ) ? zmPanZoom.panZoom[id].getScale() : 1;
    ////console.log(`++monitorsSetScale id=>${id}, el.clientWidth=>${el.clientWidth}, el.clientHeight=>${el.clientHeight}, panZoomScale=>${panZoomScale}`);
    ////console.log("el", el);
    currentMonitor.setScale(0, el.clientWidth * panZoomScale + 'px', el.clientHeight * panZoomScale + 'px', {resizeImg: false, streamQuality: $j('#streamQuality').val()});
  } else {
    for ( let i = 0, length = monitors.length; i < length; i++ ) {
      const id = monitors[i].id;
      if (!monitorDisplayedOnPage(id)) continue;
      const el = getStream(id);
      const panZoomScale = (panZoomEnabled && zmPanZoom.panZoom[id] ) ? zmPanZoom.panZoom[id].getScale() : 1;
      monitors[i].setScale(0, parseInt(el.clientWidth * panZoomScale) + 'px', parseInt(el.clientHeight * panZoomScale) + 'px', {resizeImg: false, streamQuality: $j('#streamQuality').val()});
    }
  }
  setButtonSizeOnStream();
}

function changeMonitorRate() {
  const rate = $j('#changeRate').val();
  ////console.log('maxFPS!!!!!====>', $j('#changeRate').val());
  setRateForMonitors(rate);
  setCookie('zmMontageRate', rate);
}

function setRateForMonitors(fps, id=null) {
  if (montageMode == 'Live') {
    if (id) {
      var currentMonitor = monitors.find((o) => {
        return parseInt(o["id"]) === id;
      });
      currentMonitor.setMaxFPS(fps);
    } else {
      for ( let i = 0, length = monitors.length; i < length; i++ ) {
        monitors[i].setMaxFPS(fps);
      }
    }
  } else { //inRecording
    if (id) {
      const stream = getStream(id);
      if (stream) {
        if (stream.src) {
          /* Пока для событий это не поддерживается */
          /*const currentMonitor = monitors.find((o) => {
            return parseInt(o["id"]) === id;
          });
          const url = new URL(stream.src);
          const connkey = url.searchParams.get('connkey');
          //monitorStream.streamCommand({command: CMD_MAXFPS, maxfps: fps});
          streamReq({
            command: CMD_MAXFPS,
            maxfps: fps,
            connkey: connkey,
            monitorUrl: currentMonitor.url
          });*/

          const url = new URL(stream.src);
          url.searchParams.set('maxfps', fps);
          stream.src = '';
          stream.src = url.href;
        }
      }
      //var currentMonitor = monitors.find((o) => {
      //  return parseInt(o["id"]) === id;
      //});
      //currentMonitor.setMaxFPS(fps);
    } else {
      // For all monitors
      for ( let i = 0, length = monitors.length; i < length; i++ ) {
        const stream = getStream(monitors[i].id);
        if (stream) {
          if (stream.src) {
            /* Пока для событий это не поддерживается */
            /*
            const url = new URL(stream.src);
            const connkey = url.searchParams.get('connkey');
            if (!connkey) continue;
            streamReq({
              command: CMD_MAXFPS,
              maxfps: fps,
              connkey: connkey,
              monitorUrl: monitors[i].url
            });
            */
            const url = new URL(stream.src);
            const eid = url.searchParams.get('event');
            if (eid) { //Только для тех, которые воспроизводятся
              const eventInfo = getEventInfoFromEventsTable({what: 'current', eid: eid});
              if (!eventInfo) continue;
              const startDateTime = new Date(eventInfo.start);
              //const currentDateTime =  new Date(timeline.getCurrentTime());
              url.searchParams.set('maxfps', fps);
              url.searchParams.set('frame', frameCalculationByTime(
                timeline.getCurrentTime(),
                eventInfo.start,
                eventInfo.end,
                eventInfo.frames,
              ));
              stream.src = '';
              stream.src = url.href;
            }
          }
        }
      }
    }
    setTimeout(function() {
      // ВРЕМЕННО ТАК КАК после смены SRC сокета еще нет, НУЖНА ЗАДЕРЖКА !!!
      monitorsEventSetScale();
    }, 3500);
  }
}

function setSpeedForMonitors(speed, id=null) {
  if (montageMode == 'Live') {

  } else { //inRecording
    if (id) {
      if (!monitorDisplayedOnPage(id)) return;
      const stream = getStream(id);
      if (stream) {
        if (stream.src) {
          const url = new URL(stream.src);
          const connkey = url.searchParams.get('connkey');
          if (!connkey) return;
          streamReq({
            command: CMD_VARPLAY,
            monitorId: id,
            rate: speed,
            connkey: connkey,
            eventId: eventsTable[id].current.eventId,
            monitorUrl: monitors[i].url
          });
        }
      }
    } else {
      for ( let i = 0, length = monitors.length; i < length; i++ ) {
        const id = monitors[i].id;
        if (!monitorDisplayedOnPage(id)) continue;
        const stream = getStream(id);
        if (stream) {
          if (stream.src) {
            const url = new URL(stream.src);
            const connkey = url.searchParams.get('connkey');
            if (!connkey) continue;
            streamReq({
              command: CMD_VARPLAY,
              monitorId: id,
              rate: speed,
              connkey: connkey,
              eventId: eventsTable[id].current.eventId,
              monitorUrl: monitors[i].url
            });
          }
        }
      }
    }
  }
}

/*
* Sets the monitor image change flag for positioning recalculation
*/
function setTriggerChangedMonitors(id=null) {
  if (id) {
    if (monitorDisplayedOnPage(id)) {
      if (!changedMonitors.includes(id)) {
        changedMonitors.push(id);
      }
    }
  } else {
    $j('[id ^= "liveStream"], [id ^= "evtStream"]').each(function f() {
      const i = stringToNumber(this.id);
      if (!changedMonitors.includes(i)) {
        changedMonitors.push(i);
      }
    });
  }
}

/*
* Используется при первоначальной инициализации страницы
*/
function checkEndMonitorsPlaced() {
  //return true; //ВАЖНО ВРЕМЕННО !!!
  for (let i = 0, length = monitors.length; i < length; i++) {
    const id = monitors[i].id;
    //if (!monitorDisplayedOnPage(id)) continue;

    if (!movableMonitorData[id].stop) {
      //Monitor is still moving
      const obj = getStream(id);
      if (obj) {
        var objWidth = obj.clientWidth;
        //var objWidth = obj.naturalWidth;
      } else {
        console.log(`checkEndMonitorsPlaced NOT FOUND ${'liveStream'+id}, ${'evtStream'+id}`);
        movableMonitorData[id].stop = true; //Данный монитор не отображается на экране
        continue;
      }
      if (obj.tagName == 'img') {
        if (obj.complete) { //ВАЖНО! Попробуем так... Не работает для тега <video>
          movableMonitorData[id].stop = true; //The size does not change, which means it’s already in its place!
        }
      } else {
        if (objWidth == movableMonitorData[id].width && objWidth !=0 ) {
          movableMonitorData[id].stop = true; //The size does not change, which means it’s already in its place!
        } else {
          movableMonitorData[id].width = objWidth;
        }
      }
    }
  }
  let monitorsEndMoving = true;
  //Check if all monitors are in their places
  for (let i = 0, length = movableMonitorData.length; i < length; i++) {

    if (movableMonitorData[i]) { //There may be empty elements
      if (!movableMonitorData[i].stop) {
        //Monitor is still moving
        monitorsEndMoving = false;
        ////console.log(`monitorsEndMoving++--= FALSE`);
        return false;
      }
    }
  }
  if (monitorsEndMoving) {
    for (let i = 0, length = monitors.length; i < length; i++) {
      //Clean for later use
      movableMonitorData[monitors[i].id] = {'width': 0, 'stop': false};
    }
  }
  ////console.log(`monitorsEndMoving++--= ${monitorsEndMoving}`);
  return monitorsEndMoving;
}

function waitingMonitorsPlaced(action = null) {
  const intervalWait = setInterval(() => {
    if (checkEndMonitorsPlaced()) {
      // This code may not be executed, because when opening the page we still end up in "action == 'changeRatio'"
      //if (isPresetLayout(getCurrentNameLayout())) {
      //  objGridStack.compact('list', true);
      //}
      if (action == 'startMonitors') {
        startMonitors();
      } else if (action == 'changeRatio') {
        if (!isPresetLayout(getCurrentNameLayout())) {
          return;
        }
        if (objGridStack) {
          //Re-initialization is required because the height may have changed, which means the layout may have changed!
          objGridStack.destroy(false);
        }

        for (let i = 0, length = monitors.length; i < length; i++) {
          const monitor = monitors[i];
          // Need to clear the current positioning "X". Otherwise, the order of the monitors will be disrupted
          const monitor_frame = $j('#monitor'+monitor.id);
          if (!monitor_frame) {
            console.log('Error finding frame for ' + monitor.id);
            continue;
          }
          //monitor_wrapper
          monitor_frame.closest('[gs-id="' + monitor.id + '"]').removeAttr('gs-x');
        }
        initGridStack();
        // You could use "objGridStack.compact('list', true)" instead of all this code, but that would mess up the monitor sorting. Because The "compact" algorithm in GridStack is not perfect.
      }
      clearInterval(intervalWait);
    }
  }, 100);
}

function changeMonitorStatusPosition() {
  const monitorStatusPosition = $j('#monitorStatusPosition').val();
  $j('.monitorStatus').each(function updateStatusPosition() {
    if (monitorStatusPosition == 'insideImgBottom' || monitorStatusPosition == 'showOnHover') {
      $j(this).addClass('bottom');
      if (monitorStatusPosition == 'showOnHover') {
        $j(this).addClass('hidden');
      } else {
        $j(this).removeClass('hidden');
      }
    } else if (monitorStatusPosition == 'outsideImgBottom') {
      $j(this).removeClass('bottom');
      $j(this).removeClass('hidden');
    } else if (monitorStatusPosition == 'hidden') {
      $j(this).addClass('hidden');
    }
    setTriggerChangedMonitors(stringToNumber(this.id));
  });
  setCookie('zmMonitorStatusPositionSelected', monitorStatusPosition);
}

function handlerClickDateTime (e) {
  /* ТАК МОЖНО УСТАНОВИТЬ ЛЮБУЮ ДАТУ И ВРЕМЯ*/
  /*var myDate = new Date(1978,2,11)
  //and using setDate from datepicker you can set myDate as current date in datepicker like
  //$(target).datepicker('setDate', myDate);
  $j(e.target).datetimepicker('setDate', myDate);
  */
  $j(e.target).datetimepicker("refresh"); //????? Требуется для корректного определения текущего времения при нажатии на кнопку "NOW"
}

function handlerClickOtherFilters (e) {
  var sel = null;
  //console.log("+++handlerClickOtherFilters", e);
  if (e.target == selectArchived) {
    sel = getSelected(selectArchived);
    //console.log("+++selectArchiv_edselectArchived_selectArchived", selectArchived.value);
    if (getCookie('zmFilterArchived') != sel) updateEventForTimeline();
    setCookie('zmFilterArchived', sel);
  } else if (e.target == selectTags) {
    sel = getSelectedMultiple(selectTags);
    //console.log("+++selectTags_selectTags_selectTags", $j(selectTags).val());
    if (getCookie('zmFilterTags') != sel) updateEventForTimeline();
    setCookie('zmFilterTags', sel);
  } else if (e.target == selectNotes) {
    sel = getSelectedMultiple(selectNotes);
    //console.log("+++selectNotes_selectNotes_selectNotes", $j(selectNotes).val());
    if (getCookie('zmFilterNotes') != sel) updateEventForTimeline();
    setCookie('zmFilterNotes', sel);
  }
}

function secondsBetweenDates (d1, d2) {
  const t1 = new Date(d1);
  const t2 = new Date(d2);
  const dif = t1.getTime() - t2.getTime();

  return Math.abs((t1.getTime() - t2.getTime()) / 1000);
}

/*
* date - объект Date()
* shift.offset - число (может быть отрицательным)
* shift.period - (Date, Month, Day, Hour, Minute, Sec, MilliSec)
*/
function dateTimeToISOLocal(date, shift={}, highPrecision = false) {
  var d = date;
  if (shift.offset && shift.period) {
    if (shift.period == 'Date') {
      d = new Date(date.setDate(date.getDate() + shift.offset)); //День
    } else if (shift.period == 'Month') {
      d = new Date(date.setMonth(date.getMonth() + shift.offset)); //Месяц
    } else if (shift.period == 'Day') {
      d = new Date(date.setHours(date.getHours() + shift.offset*24)); //День
    } else if (shift.period == 'Hour') {
      d =  new Date(date.setHours(date.getHours() + shift.offset)); //Час
    } else if (shift.period == 'Minute') {
      d =  new Date(date.setMinutes(date.getMinutes() + shift.offset)); //Минута
    } else if (shift.period == 'Sec') {
      d =  new Date(date.setSeconds(date.getSeconds() + shift.offset)); //Секунда
    } else if (shift.period == 'MilliSec') {
      d =  new Date(date.setMilliseconds(date.getMilliseconds() + shift.offset)); //Миллисекунда
    }
  }

   const z = n => ('0' + n).slice(-2);
   let off = d.getTimezoneOffset();
   //const sign = off < 0 ? '+' : '-';
   off = Math.abs(off);
   if (highPrecision) {
     return new Date(d.getTime() - (d.getTimezoneOffset() * 60000))
       .toISOString();
   } else {
     return new Date(d.getTime() - (d.getTimezoneOffset() * 60000))
       .toISOString()
       //.slice(0, -1) + sign + z(off / 60 | 0) + ':' + z(off % 60);
       .slice(0, -1)
       .split('.')[0].replace(/[T]/g, ' '); //Преобразование из "2024-06-20T15:12:13.145" в "2024-06-20 15:12:13"
   }
}

function changeDateTime(e) {
  console.log("changeDateTime===>", dateTimeToISOLocal(new Date()));
  var start = selectStartDateTime.value;
  var end = selectEndDateTime.value;
  if (!start) selectStartDateTime.value = dateTimeToISOLocal(new Date(), {period: 'Day', offset: -1}); //Minus 1 day
  if (!end) selectEndDateTime.value = dateTimeToISOLocal(new Date()); //Current date and time
  if (new Date(start) < new Date(startDateFirstEvent)) {
    selectStartDateTime.value = startDateFirstEvent;
    start = startDateFirstEvent;
  }

  if (start != timeline.options.start || end != timeline.options.end) {
    timeline.setOptions({
      start: start,
      end: end,
      min: start,
      max: end,
      //rollingMode: {
      //  follow: true,
      //  offset: 0.5
      //},
    });

    var nd = new Date();
    timeline.setCurrentTime(new Date(nd.setHours(nd.getHours() - 1)));
  }
}

function getGridMonitors() {
  console.log("getGridMonitors_START=>", montageMode);
  const blockMonitors = $j('#monitors');
  blockMonitors.addClass('hidden-shift'); //IgorA100 Особой пользы нет....
  const currentTime = new Date();

  $j.ajaxSetup({
    //Установим максимальное время ожидания выполнениея запроса
    //timeout: 20000 //Time in milliseconds
  });

  var params = {
    request: 'montage', 
    request_montage: request_montage, //$_REQUEST received when opening Montage page
    montage_action: 'grid', 
    dateTime: currentTime, //We will pass the client's current time. It is necessary for correct receipt of events.
    montage_mode: montageMode,
    showZones: showZones,
  };
  $j.getJSON(thisUrl, params)
    .done(function(data) {
      arrRatioMonitors = [];
      movableMonitorData = [];
      //buildMonitors(arrRatioMonitors);
      //calculateAverageMonitorsRatio(arrRatioMonitors);
      loadFontFaceObserver();
      //console.log("++++++getGridMonitors_LastEvents=>", data.lastEvents);
      blockMonitors.html(data.monitors);
      if (montageMode == 'Live') {
        initPageLive();
      } else {
        initPageReview();
      }
      applyChosen(); //ToDo Is it necessary???
      dataOnChangeThis();
      dataOnChange();
      dataOnClick();
    })
    .fail(logAjaxFail);
}

function msToTime(ms) {
  var milliseconds = Math.floor((ms % 1000) / 100),
    seconds = Math.floor((ms / 1000) % 60),
    minutes = Math.floor((ms / (1000 * 60)) % 60),
    hours = Math.floor((ms / (1000 * 60 * 60)) % 24);
  hours = (hours < 10) ? "0" + hours : hours;
  minutes = (minutes < 10) ? "0" + minutes : minutes;
  seconds = (seconds < 10) ? "0" + seconds : seconds;
  return hours + ":" + minutes + ":" + seconds;
}

/*
* _params.StartDateTime - Date()
* _params.EndDateTime - Date()
* _params.MonitorsId - Список ID мониторов или 'all'
*/
function getEventsAndExecAction(params={}) {
  //Процедура может быть длительной, необходимо предупредить.
  if (params.montage_action == 'queryEventsForTimeline') alertLoadEvents.fadeIn( { duration: 'fast' });
  console.log("===getEvents===");
  
  if (getEventsInProgress) { //Let's interrupt the current query and start a new one. (Fast movement of the scale or long query)
    getEventsInProgress.abort();
    console.log("!!! ABORT JSON !!!");
  }
  params = Object.assign({
    //Let's add parameters
    request: 'montage', 
    //montage_action: 'queryEventsForTimeline',
    montage_mode: 'inRecording',
  }, params);

  $j.ajaxSetup({timeout: AJAX_TIMEOUT});

  getEventsInProgress = $j.getJSON(thisUrl, params)
    .done(function(data) {
      if (data.tooManyEvents) {
        alert(translate["TooManyEventsForTimeline"]);
      } else {
        if (params.montage_action == 'queryEventsForTimeline') {
          console.log("getEventsForTimeline===>", data);
          //Let's fill the Timeline with events
          fillTimelineEvents({events: data.events, allEventCount: data.allEventCount});
        } else if (params.montage_action = 'queryEventsForMonitor') {
          console.log("getEventsForMonitor==>", data);
          console.log("getEventsForMonitor_PARAMS==>", params);
          //stopAllEvents();
          processingEventsForMonitor(data, params);
        }
        getEventsInProgress = '';
      }
      if (params.montage_action == 'queryEventsForTimeline') alertLoadEvents.fadeOut();
      return true; //Пробуем использовать синхронную работу
    })
    .fail(logAjaxFail);
} //getEventsAndExecAction

/*
* "dateTime" - Точка времени для которой получаем номер фрейма.
* "startDateTime" - Время начала события
* "endDateTime" - Время окончания события
* "frameCount" - Количество фремов в событии
*/
function frameCalculationByTime(dateTime, startDateTime, endDateTime, frameCount) {
  const current = new Date(dateTime);
  const start = new Date(startDateTime);
  const end = new Date(endDateTime);
  const durationSec = (end.getTime() - start.getTime()) / 1000;
  const FPS = frameCount / durationSec;
  const offsetSec = (current.getTime() - start.getTime()) / 1000;
  return parseInt(offsetSec * FPS);
}

function setLiveMode() {
  document.getElementById("fieldsTable").classList.add("hidden-shift");
  document.getElementById("wrapper-timeline").classList.add("hidden-shift");
  document.getElementById("block-timelineflip").classList.add("hidden-shift");
  document.getElementById("speedDiv").classList.add("hidden-shift");
  montageMode = 'Live';
  setCookie('zmMontageMode', montageMode);
  if (prevMontageMode == 'inRecording') {
    stopAllEvents();
  }

  getGridMonitors();
  prevMontageMode = montageMode;
}

function setInRecordingMode() {
  document.getElementById("fieldsTable").classList.remove("hidden-shift");
  document.getElementById("wrapper-timeline").classList.remove("hidden-shift");
  document.getElementById("block-timelineflip").classList.remove("hidden-shift");
  document.getElementById("speedDiv").classList.remove("hidden-shift");
  montageMode = 'inRecording';
  setCookie('zmMontageMode', montageMode);

  if (prevMontageMode == 'Live') {
    //zmPanZoomDestroy();
    stopAllMonitors();
  }

  getGridMonitors();
  prevMontageMode = montageMode;
}

/*
* Only for inRecording mode
*/
function streamQuery() {
  for (var monitorId in eventsTable) {
    const eventInfo = getEventInfoFromEventsTable({what: 'current', mid: monitorId});
    //if (eventsTable[monitorId].current.status != 'started' ||
    if (eventInfo.status != 'started' || eventInfo.zmsBroke) continue;

    //const url = new URL(eventInfo.src);
    const url = newURL(eventInfo.src);
    const connkey = url.searchParams.get('connkey');
    if (!connkey) continue;
    const monitor = monitors.find((o) => {
      return parseInt(o["id"]) === parseInt(monitorId);
    });
    //console.log(dateTimeToISOLocal(new Date()), " streamQuery для connkey=>", connkey);
    streamReq({
      monitorId: monitorId,
      command: CMD_QUERY,
      connkey: connkey,
      eventId: eventInfo.eventId,
      monitorUrl: monitor.url
    });
  }
}

/*
* Only for inRecording mode
*/
function streamReq(settings) {
  if (auth_hash) settings.auth = auth_hash;
  if (!settings.connkey) {
    console.log("In streamReq() for command: '" + settings.command + "' there is no connkey");
    return;
  }

  if (settings.monitorId) {
    //Еще нет картинки......
    if (!getStream(settings.monitorId).complete) return;

    //Пока для критически важных команд передаем monitorId. Затем нужно это реализовать для всех команд !!!
    const currentDateTime = new Date(timelineGetCurrentTime());
    const eventEndTime = new Date(eventsTable[settings.monitorId].current.end)
    const timeUntilEndEvent = eventEndTime.getTime() - currentDateTime.getTime();
    if ((settings.command == CMD_SCALE || settings.command == CMD_SEEK || settings.command == CMD_QUERY || settings.command == CMD_VARPLAY) && timeUntilEndEvent < (AJAX_TIMEOUT * currentSpeed)) {
      //Если остается мало времени до окончания события, то может возникнуть ситуация, когда команду послали и она попала в очередь, а затем сразу же поступила команда на остановку монитора. В этом случае юудет ошибка в консоле из за того, что сокет уже закрыт!
      //Возможно для ускоренного или замедленного воспроизведения требуется пересчет timeUntilEndEvent !
      //Only for debug !
      console.log("The command: '" + settings.command + "' for monitor ID='" + settings.monitorId + "' was not executed because There is little time left until the end of the event.");
      return;
    }
    /* ТОЛЬКО ВРЕД, например нельзя остановить воспроизведение, если сначала был клик по событию, а затем ПЕРЕД событием !!!
    if (settings.command != CMD_PLAY && eventsTable[settings.monitorId].current.status != 'started') {
      //Не отправлять команд..
      //ВАЖНО А нужно ли это вообще ?????
      //Only for debug !
      console.log("The command: '" + settings.command + "' for monitor ID='" + settings.monitorId + "' was not executed...");
      return;
    }
    */
    if (settings.command == CMD_SEEK) {
      //if (!getStream(settings.monitorId).complete) {
      //А может условие ИЛИ не нужно?? 
      //Попытка побороть ошибку 	"Unable to seek in stream -1	zm_ffmpeg_input.cpp	274" и "Failed getting a frame.	zm_eventstream.cpp	836"
      //if (!getStream(settings.monitorId).complete || eventsTable[settings.monitorId].current.status != 'started') {
      //Вроде поборол ошибку описанную выше. НЕТ, все равно вылезает.....
      //Теперь пробуем побороть ошибку "getCmdResponse stream error: No data to read from socket" при остановке всех событий.
      //Возможно уже изменили SRC при остановке всех событий, потом моментално нажимаем старт всех событии и снова стоп и снова старт и все делаем быстро...???
//      if (!getStream(settings.monitorId).complete || eventsTable[settings.monitorId].current.status != 'started' || eventsTable[settings.monitorId].current.src != getStream(settings.monitorId).src) {
//      if (eventsTable[settings.monitorId].current.status != 'started' || eventsTable[settings.monitorId].current.src != getStream(settings.monitorId).src) {
      if (!getStream(settings.monitorId).complete ||
       eventsTable[settings.monitorId].current.status != 'started' ||
       getStream(settings.monitorId).src.indexOf(eventsTable[settings.monitorId].current.src) == -1
      ) {
        console.log("***SEEK не отправлен для монитора ="+settings.monitorId);
        return; //Событие еще не воспроизводится.
      }
      //???Это необходимо, т.к. небольшая погрешность приводит к генерации ошибки.
      //Нет, к ошибке точно не приводит...
      //Ошибка из за чего-то другого.....
      /*
      if (settings.offset > eventsTable[settings.monitorId].current.length) {
        console.log("***OFFSET_БОЛЬШЕ конца="+settings.offset);
        console.log("Длинна:="+eventsTable[settings.monitorId].current.length);
        settings.offset = eventsTable[settings.monitorId].current.length;
      } else if (settings.offset < 0) {
        console.log("***OFFSET_МЕНЬШЕ 0=", settings.offset);
        settings.offset = 0.01;
      } else if (settings.offset == 0) {
        console.log("***OFFSET_РАВЕН 0");
        settings.offset = 0.01;
      }
      */
    }
  }

  settings.view = 'request';
  settings.request = 'stream';
  $j.ajax({
    timeout: AJAX_TIMEOUT,
    url: settings.monitorUrl+'?'+auth_relay,
    data: settings,
    dataType: "json",
    beforeSend: function(jqXHR) {
      jqXHR.url = settings.monitorUrl+'?'+auth_relay;
      jqXHR.settings = settings;
    },
    success: function(data) {

    },
    complete: getCmdResponse,
    error: logAjaxFail
  });
}

/*
* Only for inRecording mode
*/
function getCmdResponse(respObj, respText, xhr) {
  if ( checkStreamForErrors('getCmdResponse', respObj) ) {
    console.log('Got an error from getCmdResponse');
    console.log(respObj);
    console.log(respText);
    console.log("XHR=>", xhr);
    /*
    console.log("XHR_getResponseHeader=>", xhr.getResponseHeader("Content-Length"));
    console.log("XHR_getResponseHeader_Location=>", xhr.getResponseHeader("Location"));
    console.log("XHR_getAllResponseHeaders=>", xhr.getAllResponseHeaders());
    console.log("XHR_then=>", xhr.then);
    console.log("XHR_responseURL=>", xhr.responseURL);
    */
    eventsTable[respObj.settings.monitorId].current.zmsBroke = true;
    //console.log("------eventsTable=>", eventsTable);
    //return;
  } else {
    eventsTable[respObj.settings.monitorId].current.zmsBroke = false;
  }

  if (streamCmdTimer) streamCmdTimer = clearTimeout(streamCmdTimer);
  streamCmdTimer = setTimeout(streamQuery, streamTimeout);
}

function fitTimeline() {
  timeline.fit();
}

function timelineGetCurrentTime() {
  //if ($j('#pauseBtn').is(":visible")) {
  if ($j('#pauseBtn').css("display") != "none") {
    return timeline.getCurrentTime();
  } else {
    return " ";
  }
}

/*
* params.eid - поиск по Event Id
* params.mid - поиск по Monitor Id
* params.what - prev, current, next or all
*/
function clearEventDataInEventsTable(params) {
  var data = {
    monitorId: null,
    eventId: null,
    start: null,
    end: null,
    src: null,
    width: null,
    length: null,
    frames: null,
    cause: null,
    status: null, //stoped, started, waiting - (ближайший следующий), next - (следующий)
    zmsBroke: false //Use alternate navigation if zms has crashed
  };

  if (params.mid) { 
    var monitorId = params.mid;
    if (params.what == 'current') {
      eventsTable[monitorId].current = data;
    } else if (params.what == 'next') {
      eventsTable[monitorId].next = data;
    } else if (params.what == 'prev') {
      eventsTable[monitorId].prev = data;
    } else if (params.what == 'all') {
      eventsTable[monitorId] = {prev: data, current: data, next: data};
    }
  } else if (params.eid) {
    for (var monitorId in eventsTable) {
      if (params.what == 'current') {
        if (String(eventsTable[monitorId].current.eventId) === String(params.eid)) {
          eventsTable[monitorId] = {current: data};
          break;
        }
      } else if (params.what == 'next') {
        if (String(eventsTable[monitorId].next.eventId) === String(params.eid)) {
          eventsTable[monitorId] = {next: data};
          break;
        }
      } else if (params.what == 'prev') {
        if (String(eventsTable[monitorId].prev.eventId) === String(params.eid)) {
          eventsTable[monitorId] = {prev: data};
          break;
        }
      } else if (params.what == 'all') {
        if (String(eventsTable[monitorId].prev.eventId) === String(params.eid)) {
          eventsTable[monitorId] = {prev: data, current: data, next: data};
          break;
        }
      }
    }
  }
}

function updateEventStatusInEventsTable(params) {
  if (params.mid) { 
    if (params.what == 'current') {
      eventsTable[params.mid].current.status = params.statusValue;
      if (params.statusValue == 'started') {
        document.getElementById('eventId' + params.mid).textContent = eventsTable[params.mid].current.eventId;
        document.getElementById('viewingFPSValue' + params.mid).textContent = (eventsTable[params.mid].current.frames / eventsTable[params.mid].current.length).toFixed(1);
        document.getElementById('causeValue' + params.mid).textContent = eventsTable[params.mid].current.cause;
        document.getElementById('framesValue' + params.mid).textContent = eventsTable[params.mid].current.frames;
        document.getElementById('lengthValue' + params.mid).textContent = msToTime(eventsTable[params.mid].current.length * 1000);
        document.getElementById('widthValue' + params.mid).textContent = eventsTable[params.mid].current.width;
        document.getElementById('startDateTimeValue' + params.mid).textContent = eventsTable[params.mid].current.start;
        document.getElementById('endDateTimeValue' + params.mid).textContent = eventsTable[params.mid].current.end;
      }
    } else if (params.what == 'next') {
      eventsTable[params.mid].next.status = params.statusValue;
    } else if (params.what == 'prev') {
      eventsTable[params.mid].prev.status = params.statusValue;
    }
  } else if (params.eid) {
    for (var monitorId in eventsTable) {
      if (params.what == 'current') {
        if (String(eventsTable[monitorId].current.eventId) === String(params.eid)) {
          eventsTable[monitorId].current.status = params.statusValue;
          if (params.statusValue == 'started') {

          }
          break;
        }
      } else if (params.what == 'next') {
        if (String(eventsTable[monitorId].next.eventId) === String(params.eid)) {
          eventsTable[monitorId].next.status = params.statusValue;
          break;
        }
      } else if (params.what == 'prev') {
        if (String(eventsTable[monitorId].prev.eventId) === String(params.eid)) {
          eventsTable[monitorId].prev.status = params.statusValue;
          break;
        }
      }
    }
  }
}

function updateEventForTimeline() {
  if (!createdTimelineExtraInfo) {
    createdTimelineExtraInfo = document.querySelector("#timelinediv > div > div:nth-child(1)");
    const el = document.createElement('p');
    el.id = 'timeline-current-time';
    createdTimelineExtraInfo.append(el);
    timelineCurrentTimeHTML = $j(el);
  }

  //const StartDateTime = new Date(properties.start);
  //const EndDateTime = new Date(properties.end);
  const timelineGetWindow = timeline.getWindow(); //Видимый временной диапазон.
  const startWindowTimeline = dateTimeToISOLocal(new Date(timelineGetWindow.start));
  const endWindowTimeline = dateTimeToISOLocal(new Date(timelineGetWindow.end));
  //const StartWindowTimelineSec = parseInt(startWindowTimeline.getTime() / 1000);
  //const EndWindowTimelineSec = parseInt(endWindowTimeline.getTime() / 1000);
  const visCenter = $j(timelineBlock).find('.vis-panel.vis-center'); //Часть Timeline на которой отображаются события

  ////console.log('startWindowTimeline=: ', startWindowTimeline);
  //console.log('!!!!!!startWindowTimeline=: ', dateTimeToISOLocal(startWindowTimeline));
  ////console.log('timelineGetWindow=: ', timelineGetWindow);
  //console.log('StartDateTime: ', StartWindowTimelineSec);
  //console.log('EndDateTime: ', EndWindowTimelineSec);
  ////console.log('prevSTART: ', prevRangeWindowTimeline.start);
  ////console.log('prevEND: ', prevRangeWindowTimeline.end);
  //console.log('MonitorsId: ', monitorsId);
  //console.log('Monitors: ', monitors);
  //console.log('++++++++++++getItemRange: ', timeline.getItemRange());
  //console.log('++++++++++++getWindow: ', timeline.getWindow());

  //if (prevRangeWindowTimeline.start != startWindowTimeline || prevRangeWindowTimeline.end != endWindowTimeline) {
  //prevRangeWindowTimeline.start = StartWindowTimelineSec;
  //prevRangeWindowTimeline.end = EndWindowTimelineSec;
  prevRangeWindowTimeline.start = startWindowTimeline;
  prevRangeWindowTimeline.end = endWindowTimeline;
  //const sec = EndWindowTimelineSec - StartWindowTimelineSec;
  const sec = secondsBetweenDates (startWindowTimeline, endWindowTimeline);
  var widthTimelineForItems;
  if (visCenter) {
    if (visCenter[0].offsetWidth < 100) {
      //Если открытие страницы происходит со скрытым Timeline, то ширина будет равняться бордюру. 
      widthTimelineForItems = wrapperTimelineBlock.offsetWidth / 100 * 80;
    } else {
      widthTimelineForItems = visCenter[0].offsetWidth;
    }
  } else {
    widthTimelineForItems = wrapperTimelineBlock.offsetWidth / 100 * 80;
  }
  //const widthTimelineForItems = (visCenter[0].offsetWidth > 100)
  //const resolution = (visCenter) ? parseInt(sec / visCenter[0].offsetWidth*1) : null; //Количество секунд между двумя соседними пикселями
  const resolution = (visCenter) ? parseInt(sec / widthTimelineForItems*1) : null; //Количество секунд между двумя соседними пикселями
  /*В*///console.log('=======================sec: ', sec);
  /*В*///console.log('=====================Width: ', visCenter[0].offsetWidth);
  /*В*///console.log('================resolution: ', resolution);
  /*В*///console.log('=====widthTimelineForItems: ', widthTimelineForItems);
  /*В*///console.log('======widthidTimelineBlock: ', wrapperTimelineBlock.offsetWidth);

  //Соберем СВОЙ фильтр
  var filter = {
    Archived: getSelected(selectArchived), 
    Tags: getSelectedMultiple(selectTags),
    Notes: getSelectedMultiple(selectNotes)
  };

  getEventsAndExecAction({
    //Resolution: resolution * 20, //Увеличим расстояние для анализа, иначе все равно слишком много событий
    Resolution: resolution, //Увеличим расстояние для анализа, иначе все равно слишком много событий
    //StartDateTime: StartWindowTimelineSec,
    //EndDateTime: EndWindowTimelineSec,
    StartDateTime: startWindowTimeline,
    EndDateTime: endWindowTimeline,
    MonitorsId: monitorsId,
    montage_action: 'queryEventsForTimeline',
    filter: filter,
    //MonitorsId: 'all',
  }); //Получим события
  managingTimelineNavigationButtons();
  //}
}

/*
* params.eid - поиск по Event Id
* params.mid - поиск по Monitor Id
* params.what - prev, current or next
*/
function getEventInfoFromEventsTable(params) {
  var event = null;
  if (params.mid) { 
    if (params.what == 'current') {
      event = eventsTable[params.mid].current;
    } else if (params.what == 'next') {
      event = eventsTable[params.mid].next;
    } else if (params.what == 'prev') {
      event = eventsTable[params.mid].prev;
    }
  } else if (params.eid) {
    for (var monitor in eventsTable) {
      if (params.what == 'current') {
        if (String(eventsTable[monitor].current.eventId) === String(params.eid)) {
          event = eventsTable[monitor].current;
          break;
        }
      } else if (params.what == 'next') {
        if (String(eventsTable[monitor].next.eventId) === String(params.eid)) {
          event = eventsTable[monitor].next;
          break;
        }
      } else if (params.what == 'prev') {
        if (String(eventsTable[monitor].prev.eventId) === String(params.eid)) {
          event = eventsTable[monitor].prev;
          break;
        }
      }
    }
  }
  return event;
}

function click_last_24H() {
  selectStartDateTime.value = dateTimeToISOLocal(new Date(), {period: 'Day', offset: -1}); //Минус 1 день
  selectEndDateTime.value = dateTimeToISOLocal(new Date());
  stopAllEvents();
  changeDateTime(null);
}

function click_last_8H() {
  selectStartDateTime.value = dateTimeToISOLocal(new Date(), {period: 'Hour', offset: -8}); //Минус 8 часов
  selectEndDateTime.value = dateTimeToISOLocal(new Date());
  stopAllEvents();
  changeDateTime(null);
}

function click_last_1H() {
  selectStartDateTime.value = dateTimeToISOLocal(new Date(), {period: 'Hour', offset: -1}); //Минус 1 час
  selectEndDateTime.value = dateTimeToISOLocal(new Date());
  stopAllEvents();
  changeDateTime(null);
}

function startAllEvents(properties) {
  prevDateTimeTimelineInMilliSec = null;
  eventsPlay = true;
  var newCurrentTime = null;

  if (!properties || !properties.hasOwnProperty('what')) {
    //This means it is called NOT from a click on the Timeline
    const startTime  = getCustomTimeTimeline(idTimelineCustomTimeMarker);
    if (!startTime) {
      alert('The start time for playback of recordings is not set!'); //ToDo - Add translation
      return;
    }

    //Let's display a red vertical marker of the current time on the scale.
    timeline.setOptions({
      showCurrentTime: true
    });
    //Set the current time on the timeline to the Custom marker time
    newCurrentTime = new Date(startTime);
  } else {
    //Called by clicking on the Timeline
    //Let's display a red vertical marker of the current time on the scale.
    timeline.setOptions({
      showCurrentTime: true
    });
    //Set the current time on the timeline to the time you clicked on
    newCurrentTime = new Date(timeline.getEventProperties(event).time);
  }
  timeline.setCurrentTime(newCurrentTime);
  //Let's remove the Custom marker if there was one.
  if (getCustomTimeTimeline(idTimelineCustomTimeMarker)) delTimelineMarker();

  $j('#pauseBtn').show();
  $j('#stopBtn').show();
  $j('#playBtn').hide();
  setButtonState('pauseBtn', 'inactive');
  setButtonState('stopBtn', 'inactive');
  setButtonState('playBtn', 'active');

  //timeline.setOptions({
  //  rollingMode: {
  //    follow: false,
  //  }
  //});

  //timeline.setOptions({
  //  showCurrentTime: false,
  //});
  //    const newCurrentTime = new Date(props.time);
  //    timeline.setCurrentTime(newCurrentTime);
  //console.log('selected items: ' + properties.items);
  //timeline.setOptions({
  //  showCurrentTime: true,
  //});
  ////console.log('maxFPS====>', $j('#changeRate').val());
  //Соберем СВОЙ фильтр
  var filter = {
    Archived: getSelected(selectArchived), 
    Tags: getSelectedMultiple(selectTags),
    Notes: getSelectedMultiple(selectNotes)
  };
  ////console.log('+++++++filter====>', filter);

  //При использовании Pause могут изменить положение положение Custom marker, значит время продолжения будет другим.
  clearInterval(intervalSynchronizeEventsWithTimeline);
  clearInterval(intervalRefreshCheckNextEvent);
  clearInterval(intervalRefreshUpdateCurrentTime);

  //Получим события для мониторов.
  getEventsAndExecAction({
    Resolution: 0,
    //StartDateTime: parseInt(newCurrentTime.getTime() / 1000),
    //EndDateTime: parseInt(newCurrentTime.getTime() / 1000),
    StartDateTime: dateTimeToISOLocal(newCurrentTime),
    EndDateTime: dateTimeToISOLocal(newCurrentTime),
    MonitorsId: monitorsId,
    montage_action: 'queryEventsForMonitor',
    //montage_action: 'queryNextEventForMonitor',
    maxFPS: $j('#changeRate').val(),
    filter: filter,
    //MonitorsId: 'all',
  }); //Получим события

  intervalSynchronizeEventsWithTimeline = setInterval(() => { //Синхронизировать текущее время воспроизведения события с Timeline. Особенно актуально при скорости отличной от 1X
    for (var monitorId in eventsTable) {
      if (eventsTable[monitorId].current.status != 'started') continue;
      //const url = new URL(eventsTable[monitorId].current.src);
      const url = newURL(eventsTable[monitorId].current.src);
      const connkey = url.searchParams.get('connkey');
      if (!connkey) continue;
      const startDateTime = new Date(eventsTable[monitorId].current.start);
      const endDateTime = new Date(eventsTable[monitorId].current.end);
      const currentDateTime =  new Date(timeline.getCurrentTime());
      const monitor = monitors.find((o) => {
        return parseInt(o["id"]) === parseInt(monitorId);
      });
      //console.log("***MONITOR***\n", "***1MONITOR1***" , monitor);
      //Здесь при старте событий бывает появляется странная ошибка:
      //js_logger-base-1716929130.js:91 getCmdResponse stream error: socket_bind( /run/zm/zms-712037w.sock ) failed: Address already in use
      //Возможно из за этого:
      //Ajax request failed.  No responseText.  jqxhr follows: {readyState: 0, getResponseHeader: ƒ, getAllResponseHeaders: ƒ, setRequestHeader: ƒ, overrideMimeType: ƒ, …}
      //skins_classic_js_skin-base-1721203237.js:942 Request Failed: timeout, timeout
      streamReq({
        command: CMD_SEEK,
        monitorId: monitorId,
        offset: (currentDateTime.getTime()-startDateTime.getTime())/1000,
        connkey: connkey,
        eventId: eventsTable[monitorId].current.eventId,
        monitorUrl: monitor.url
      });
    }
  }, 2 * 1000);
  intervalRefreshCheckNextEvent  = setInterval(function() {
    checkNextEvent();
  }, 0.5*1000);
  intervalRefreshUpdateCurrentTime = setInterval(function() {
        timelineCurrentTimeHTML.html(dateTimeToISOLocal(timeline.getCurrentTime()));
  }, 1*1000);
}

function pauseAllEvents() {
  prevDateTimeTimelineInMilliSec = null;
  eventsPlay = false;
  $j('#pauseBtn').hide();
  $j('#stopBtn').show();
  $j('#playBtn').show();
  setButtonState('pauseBtn', 'active');
  setButtonState('stopBtn', 'inactive');
  setButtonState('playBtn', 'inactive');

  //timelineCurrentTimeHTML.html(" ");

  clearInterval(intervalRefreshCheckNextEvent);
  clearInterval(intervalRefreshUpdateCurrentTime);
  clearInterval(intervalSynchronizeEventsWithTimeline);

  //Let's add our own marker if there wasn't one.
  if (!getCustomTimeTimeline(idTimelineCustomTimeMarker)) {
    setTimelineMarker(timeline.getCurrentTime());
  }
  //Let's hide the red vertical current time marker on the scale.
  timeline.setOptions({
    showCurrentTime: false
  });
  //console.log("monitors======----->", monitors);
  //console.log("eventsTable======----->", eventsTable);

  //Pause playback of monitors
  for (var monitorId in eventsTable) {
    if (eventsTable[monitorId].current.status == 'started' ) {
      const url = newURL(eventsTable[monitorId].current.src);
      const connkey = url.searchParams.get('connkey');
      if (!connkey) continue;
      const monitor = monitors.find((o) => {
        return parseInt(o["id"]) === parseInt(monitorId);
      });
      console.log("eventsTable.connkey======----->", connkey);
      //console.log("eventsTable.status======----->", status);
      streamReq({
        command: CMD_PAUSE,
        monitorId: monitorId,
        connkey: connkey,
        eventId: eventsTable[monitorId].current.eventId,
        monitorUrl: monitor.url
      });
      writeTextCanvas(monitorId, 'Pause');
    }
  }
  //eventsTable = [];
}

function stopAllEvents() {
  prevDateTimeTimelineInMilliSec = null;
  eventsPlay = false;
  $j('#pauseBtn').hide();
  $j('#stopBtn').hide();
  $j('#playBtn').show();
  setButtonState('pauseBtn', 'active');
  setButtonState('stopBtn', 'active');
  setButtonState('playBtn', 'inactive');

  timelineCurrentTimeHTML.html(" ");

  clearInterval(intervalRefreshCheckNextEvent);
  clearInterval(intervalRefreshUpdateCurrentTime);
  clearInterval(intervalSynchronizeEventsWithTimeline);
  streamCmdTimer = clearTimeout(streamCmdTimer);

  //Let's add our marker if there isn't one yet (and there might be one if there was a pause before)
  //view-source:https://visjs.github.io/vis-timeline/examples/timeline/other/customTimeBarsTooltip.html
  //view-source:https://visjs.github.io/vis-timeline/examples/timeline/markers/customTimeMarkers.html
  if (!getCustomTimeTimeline(idTimelineCustomTimeMarker)) {
    setTimelineMarker(timeline.getCurrentTime());
  }
  //Let's hide the red vertical current time marker on the scale.
  timeline.setOptions({
    showCurrentTime: false
  });

  //Stop playing monitors
  for (var monitorId in eventsTable) {
    //if (eventsTable[monitorId].current.status == 'started' ) {
    //stopEvent(parseInt(monitorId), true, "Stop");
    stopEvent(parseInt(monitorId), true, "No recording for this time");
    //}
  }
  //eventsTable = [];
}

function writeTextCanvas( monId, text, scaleSize=1 ) {
  if ( monId ) {
    clearTextCanvas( monId );
    var canvasCtx = monitorCanvasCtx[monId];
    var canvasObj = monitorCanvasObj[monId];
    if (!canvasCtx || !canvasObj) {
      console.log("No canvas for Monitor ID=" + monId + ' in "writeTextCanvas"');
      return;
    }
    canvasObj.classList.remove("hidden-shift");
    //canvasCtx.fillRect(0, 0, canvasObj.width, canvasObj.height);
    var textSize=canvasObj.width * 0.15 * scaleSize;
    canvasCtx.font = "600 " + textSize.toString() + "px Arial";
    //canvasCtx.fillStyle='rgba(100,100,100,1)';
    //canvasCtx.fillStyle="white";
    canvasCtx.fillStyle='#d0d0d0';
    canvasCtx.globalAlpha = 1;
    var textWidth = canvasCtx.measureText(text).width;
    canvasCtx.fillText(text, canvasObj.width/2 - textWidth/2, canvasObj.height/2);
  } else {
    console.log("No monId in writeTextCanvas");
  }
}

function clearTextCanvas( monId ) {
  if ( monId ) {
    var canvasCtx = monitorCanvasCtx[monId];
    var canvasObj = monitorCanvasObj[monId];
    if (!canvasCtx || !canvasObj) {
      console.log("No canvas for Monitor ID=" + monId + ' in "writeTextCanvas"');
      return;
    }
    canvasCtx.clearRect(0, 0, canvasObj.width, canvasObj.height);
    canvasCtx.globalAlpha = 0;
    canvasObj.classList.add("hidden-shift"); //Canvas мешает масштабированию!
  } else {
    console.log("No monId in clearTextCanvas");
  }
}

// Manage the DOWNLOAD VIDEO button
function click_download() {
  const form = $j('#filters_form');
  //console.log(timeline.getItemRange());
  const data = form.serializeArray();

  data[data.length] = {name: 'mergeevents', value: true};
  data[data.length] = {name: 'minTime', value: dateTimeToISOLocal(new Date(timeline.getWindow().start))};
  data[data.length] = {name: 'maxTime', value: dateTimeToISOLocal(new Date(timeline.getWindow().end))};
  console.log(data);
  $j.ajaxSetup({
    //Установим максимальное время ожидания выполнениея запроса
    //Значения AJAX_TIMEOUT может не хватить, если к скачиванию очень много событий !
    timeout: 120000 //Time in milliseconds
  });
  $j.ajax({
    url: thisUrl+'?request=modal&modal=download'+(auth_relay?'&'+auth_relay:''),
    data: data
  })
      .done(function(data) {
        insertModalHtml('downloadModal', data.html);
        $j('#downloadModal').modal('show');
        $j('#downloadModal').on('keyup keypress', function(e) {
          var keyCode = e.keyCode || e.which;
          if (keyCode === 13) {
            e.preventDefault();
            return false;
          }
        });
        // Manage the GENERATE DOWNLOAD button
        $j('#exportButton').click(exportEvent);
      })
      .fail(logAjaxFail);
} // end function click_download

function newURL (src) {
  const baseURL = (src.indexOf('http') == -1) ? ZM_HOME_URL : undefined;
  return new URL(src, baseURL);
}

/* +++++ TimeLine*/
function initTimeline () {
  if (!selectStartDateTime) selectStartDateTime = document.getElementById("StartDateTime");
  if (!selectEndDateTime) selectEndDateTime = document.getElementById("EndDateTime");
  if (!selectArchived) selectArchived = document.getElementById("Archived");
  if (!selectTags) selectTags = document.getElementById("Tags");
  if (!selectNotes) selectNotes = document.getElementById("Notes");

  setSelected(selectArchived, getCookie('zmFilterArchived'));
  setSelected(selectTags, getCookie('zmFilterTags'));
  setSelected(selectNotes, getCookie('zmFilterNotes'));
  //console.log("*****getSelectedMultiple=>", getSelectedMultiple(selectNotes));

  const groups = [];
  console.log("initTimeline_monitors=>", monitors);
  for (let i=0, length = monitors.length; i < length; i++) {
    groups.push({
      content: "(" + monitors[i].id + ") "+ monitors[i].name, 
      id: monitors[i].id, 
      value: i, 
      className:'monitor-group-timeline',
      style: 'height:29px'
    });
  }

  const options = {
    // option groupOrder can be a property name or a sort function
    // the sort function must compare two groups and return a value
    //     > 0 when a > b
    //     < 0 when a < b
    //       0 when a == b
    groupOrder: function (a, b) {
      return a.value - b.value;
    },
    groupOrderSwap: function (a, b, groups) {
      var v = a.value;
      a.value = b.value;
      b.value = v;
    },
    groupTemplate: function(group){
      var container = document.createElement('div');
      var label = document.createElement('span');
      label.innerHTML = group.content + ' ';
      container.insertAdjacentElement('afterBegin',label);
      //var hide = document.createElement('button');
      //hide.innerHTML = 'hide';
      //hide.style.fontSize = 'small';
      //hide.addEventListener('click',function(){
      //  groups.update({id: group.id, visible: false});
      //});
      //container.insertAdjacentElement('beforeEnd',hide);
      return container;
    },
    orientation: 'both',
    editable: false,
    groupEditable: false,
    //start: new Date(2015, 6, 1),
    //end: new Date(2015, 10, 1)
    //rollingMode: {
    //  follow: true,
    //  offset: 0.5
    //},
    //rollingMode: {
    //  follow: true,
    //  offset: 0.5
    //},
    //groupHeightMode: 'fixed',
    //timeAxis: { // С ним тормозит
    //  scale: 'minute',
    //  step: 1,
    //},
    cluster: {
      maxItems: 1,
    },

    start: new Date(selectStartDateTime.value),
    end: new Date(selectEndDateTime.value),
    min: new Date(startDateFirstEvent), //THE BEGINNING of the very FIRST event
    max: new Date(dateTimeToISOLocal(new Date(), {period: 'Hour', offset: +1})),
    zoomMin: 5*1000, //milliseconds
    zoomMax: 3000*(24*3600*1000), //30 дней
    zoomKey: 'shiftKey',
    horizontalScroll: true,
    verticalScroll: true,
    width: '100%',
    maxHeight: '300px',
    stack: false,
    margin: {
      item: {
        horizontal: 10, 
        vertical: 2
      }, // minimal margin between items
      axis: 5   // minimal margin between items and the axis
    },
  };

  // create visualization
  const container = timelineBlock;

  if (!timeline) {
    timeline = new vis.Timeline(container);
  }
  timeline.setOptions(options);
  timeline.setGroups(groups);
  //timeline.setItems(items);
  
  //Let's hide the red vertical current time marker on the scale.
  timeline.setOptions({
    showCurrentTime: false
  });

  timeline.on('rangechanged', function (properties) {
    //Fired once after the timeline window has been changed.
    //console.log('***************************rangechanged: ', properties);
    const timelineGetWindow = timeline.getWindow(); //Visible time range.
    const startWindowTimeline = dateTimeToISOLocal(new Date(timelineGetWindow.start));
    const endWindowTimeline = dateTimeToISOLocal(new Date(timelineGetWindow.end));
    if (prevRangeWindowTimeline.start != startWindowTimeline || prevRangeWindowTimeline.end != endWindowTimeline) {
      updateEventForTimeline();
    }
  });
  timeline.on('rangechange', function (properties) {
    //console.log('rangechange: ', properties);
  });
  timeline.on('select', function (properties) {
    if (ctrled) {
      const url = '?view=event&eid='+properties.items;
      window.open(url, '_blank');
      console.log('CTRLED selected items: ' + properties.items);
    } else {
      console.log('selected items: ' + properties.items);
    }
  });
  timeline.on('click', function (properties) {
    //console.log('getEventProperties: ', timeline.getEventProperties());
  });
  timeline.on('doubleClick', function (properties) {
    console.log("*****properties_doubleClick", properties);
    if (properties.what == 'group-label'){

    } else if (properties.what == 'axis' || properties.what == 'background' || properties.what == 'item' || properties.what == 'custom-time'){
      if (ctrled) {
        //Let's add our own marker
        //view-source:https://visjs.github.io/vis-timeline/examples/timeline/other/customTimeBarsTooltip.html
        //view-source:https://visjs.github.io/vis-timeline/examples/timeline/markers/customTimeMarkers.html
        var eventProps = timeline.getEventProperties(properties.event);
        console.log("*****eventProps", eventProps);
        if (eventProps.what === 'custom-time') {
          delTimelineMarker();
        } else {
          setTimelineMarker(timeline.getEventProperties(properties.event).time);
        }
        //pauseAllEvents();
        console.log("*****properties", properties);
      } else {
        startAllEvents(properties);
      }

    }
  });
  timeline.on('currentTimeTick', function (properties) {
    //Fired when the current time bar redraws. The rate depends on the zoom level.
    //Required to change the marker movement speed depending on the playback speed (other than 1)
    const currentDateTimeLine = new Date(timeline.getCurrentTime());
    const currentTimeMilliseconds = currentDateTimeLine.getTime();
    let delta = 0;

    if (prevDateTimeTimelineInMilliSec) {
      delta = (currentTimeMilliseconds - prevDateTimeTimelineInMilliSec) * (parseFloat(speeds[speedIndex]) - 1);
    }
    if (delta != 0) {
      const nd = new Date(currentTimeMilliseconds + delta);
      const ndISO = nd.toISOString();
      timeline.setCurrentTime(nd);
    }
    prevDateTimeTimelineInMilliSec = currentTimeMilliseconds + delta;
  });

  timelineBlock.onclick = function (event) {
    //console.log(props);
  }
  changeDateTime(null); //Let's set the period
}

function managingTimelineNavigationButtons() {
  const range = timeline.getWindow();
  const moveLeftTimeline = document.getElementById('moveLeftTimeline');
  const moveRightTimeline = document.getElementById('moveRightTimeline');
  if (dateTimeToISOLocal(range.start) > selectStartDateTime.value) {
    moveRightTimeline.removeAttribute('disabled');
  } else {
    moveRightTimeline.setAttribute('disabled', 'disabled');
  }
  if (dateTimeToISOLocal(range.end) < selectEndDateTime.value) {
    moveLeftTimeline.removeAttribute('disabled');
  } else {
    moveLeftTimeline.setAttribute('disabled', 'disabled');
  }
}

function moveTimeline(percentage) {
  const range = timeline.getWindow();
  const interval = range.end - range.start;

  timeline.setWindow({
    start: range.start.valueOf() - interval * percentage,
    end:   range.end.valueOf()   - interval * percentage
  });
  managingTimelineNavigationButtons();
}

/*
* Percentage move  of the visible area of the scale using the button
*/
function moveLeftTimeline() {
  moveTimeline(-0.9);
}

function moveRightTimeline() {
  moveTimeline(0.9);
}

/*
* Set the time marker to the center of the scale
*/
function timeMarkerInCenterScale() {
  const rangeDateTime = timeline.range.end - timeline.range.start;
  const center = rangeDateTime / 2;
  const customDateTime = getCustomTimeTimeline(idTimelineCustomTimeMarker);
  const currentDateTime = timeline.getCurrentTime().getTime();
  var start, end;

  if (customDateTime) {
    start = customDateTime.getTime() - center;
    end = customDateTime.getTime() + center;
  } else {
    start = currentDateTime - center;
    end = currentDateTime + center;
  }

  timeline.setOptions({
    start: new Date(start),
    end: new Date(end),
    //min: start,
    //max: end,
    //rollingMode: {
    //  follow: true,
    //  offset: 0.5
    //},
  });
}

function setTimelineMarker(time) {
  const markerText = translate["Start Time"];
  if (getCustomTimeTimeline(idTimelineCustomTimeMarker)) delTimelineMarker();

  timeline.addCustomTime(time, idTimelineCustomTimeMarker);
  timeline.setCustomTimeMarker(markerText, idTimelineCustomTimeMarker);
  customTimeSpecified = true;
}

function delTimelineMarker() {
  timeline.removeCustomTime(idTimelineCustomTimeMarker);
  customTimeSpecified = false;
}

function getCustomTimeTimeline(id) {
  return (customTimeSpecified) ? timeline.getCustomTime(id) : null;
}

function startEvent(monitorId) {
  clearTextCanvas(monitorId);
  //const monitorId = event.MonitorId;
  //const classArchived = (events[index].Archived) ? " event-archived" : "";
  const stream = getStream(monitorId);
  //console.log('monitorId=>', monitorId, 'StartDateTime=>' , events[index].StartDateTime);
  if (stream) { //ВАЖНО !!!Почему-то появлялась ошибка, не находило stream, разобраться. Понял. В ответе запроса (если в нем не было фильтра по мониторам) могут быть мониторы, которых нет на странице
    /*
      start: events[0].StartDateTime, 
      end: events[0].EndDateTime,
      event: events[0],
      src: data.streamSrc[events[0].Id],
      started: false
    */

    stream.src = '';
    //stream.src = decodeURI(streamSrc[events[index].Id]);
    //stream.src = streamSrc[events[index].Id].replaceAll("&amp;", "&");
    stream.src = getEventInfoFromEventsTable({what: 'current', mid: monitorId}).src;
    clearTextCanvas( monitorId );

    //eventsTable[monitorId].prev = structuredClone(eventsTable[monitorId].current);
    //eventsTable[monitorId].current = structuredClone(eventsTable[monitorId].next);
    updateEventStatusInEventsTable({what: 'current', mid: monitorId, statusValue: 'started'});
    setTriggerChangedMonitors(monitorId);

    ////console.log("eventsTable[monitorId]", eventsTable[monitorId]);
  }
}

/*
* fullStop = false - для того, что бы дать возможность доиграть 1-2 последние секундны при возможной рассинхронизации с Timeline
*/
function stopEvent(monitorId, fullStop = true, message='') {
    //writeTextCanvas(monitorId, 'No Event');
    const eventInfo = getEventInfoFromEventsTable({what: 'current', mid: monitorId});
    const stream = getStream(monitorId);
    //Необходимо немедленно изменить статус, что бы не происходило отправки команд в фоновом режиме.
    //Попытка побороть ошибку 
    //getCmdResponse stream error: socket_sendto( /run/zm/zms-897766s.sock ) failed: Connection refused
    // ВАЖНО !!! НЕ ПОМОГАЕТ !!!!!!!!!!!!!!!!!!!!!
    //updateEventStatusInEventsTable({what: 'current', mid: monitorId, statusValue: 'stoped'});
    //if (eventsTable[monitorId].current.eventId) {
    eventsTable[monitorId].prev = structuredClone(eventsTable[monitorId].current);
    //}
    //if (eventsTable[monitorId].next.eventId) {
    eventsTable[monitorId].current = structuredClone(eventsTable[monitorId].next);
    //}
    /*В*///console.log('STOP');
    /*В*///console.log('stream=>', stream);
    /*В*///console.log('eventInfo.status=>', eventInfo.status);


    if (stream && eventInfo.status == 'started') {
      if (stream.src) {
        updateEventStatusInEventsTable({what: 'current', mid: monitorId, statusValue: 'stoped'});
        //console.log('monitorId=>', monitorId, 'Status=>' , getEventInfoFromEventsTable({what: 'current', mid: monitorId}).status);
        //const url = new URL(stream.src);
        //const url = new URL(eventInfo.src);
        const url = newURL(eventInfo.src);
        const eventId = url.searchParams.get('event');
        const connkey = url.searchParams.get('connkey');
        const monitor = monitors.find((o) => {
          return parseInt(o["id"]) === monitorId;
        });
        if (fullStop || currentSpeed !=1) {
          //console.log('fullStop for=>', monitorId);
          //console.log('fullStop_connkey for=>', connkey);
          //console.log('fullStop_eventId for=>', eventId);
          streamReq({
            //command: CMD_STOP, //ЭТО ПОКА НЕ ПОДДЕРЖИВАЕТСЯ !!!
            command: CMD_PAUSE,
            monitorId: monitorId,
            connkey: connkey,
            eventId: eventId,
            monitorUrl: monitor.url
          });
        }
        //writeTextCanvas(monitorId, 'No Event');
        if (message) {
          writeTextCanvas(monitorId, message, 0.4);
        } else {
          writeTextCanvas(monitorId, 'No recording for this time', 0.4);
        }
      }
    }
}

function processingEventsForMonitor(data, params) {
  //Пропишем SRC для мониторов и запустим проигрывание событий
  const events = data.events;
  const streamSrc = data.streamSrc;
  var index;
  if (events.length < 1) {
    //В текущее время нет событий для воспроизведения.
    //Но если до этого что-то воспроизводилось (а затем переместили временной маркер), то необходимо очистить.
    //Или маркер установили перед закончившимся событием.
    //params.MonitorsId - ЭТО МАССИВ, И ВОТ КАК ПОНЯТЬ, ДЛЯ КАКИХ МОНИТОРОВ ЕСТЬ СОБЫТИЯ, А ДЛЯ КАКИХ НЕТ - ЗАГАДКА ПОКА !!!
  }
  //Копируем массив. Необходимо для отделения мониторов для которых есть события и для которых нет событий.
  var monitorsWOEvents = params.MonitorsId.slice();

  var prevMonitorId = null; //Можеть быть ситуация, когда времена двух событий ПЕРЕСЕКАЮТСЯ. Это редкость и возможно глюк, но тем менее...
  for (index = 0; index < events.length; ++index) {
    const monitorId = events[index].MonitorId;
    //Удалим из массива мониторы для которых событие будет воспроизводиться.
    var im = monitorsWOEvents.indexOf(monitorId);
    if (im !== -1) {
      monitorsWOEvents.splice(im, 1);
    }
    //!!!ЗДЕСЬ ВСЕ ПРАВИЛЬНОЕ ДОЛЖНО БЫТЬ !!!
    const stream = getStream(monitorId);
    /*В*///console.log("+++monitorId=>>>>>", monitorId);
    /*В*///console.log("+++stream=>>>>>>>>", stream);
    if (!stream) continue; //ВАЖНО !!!Почему-то появлялась ошибка, не находило stream, разобраться. Понял. В ответе запроса (если в нем не было фильтра по мониторам) могут быть мониторы, которых нет на странице
    //ПОПЫТКА РАБОТАТЬ С КОМАНДАМИ.
    var url = new URL(stream.src);
    var connkey = url.searchParams.get('connkey');
    //const eventId = url.searchParams.get('event') ? url.searchParams.get('event') : url.searchParams.get('eid');
    const eventId = events[index].Id;
    /*В*///console.log("^stream.src^^^^^^^^^^^^^^^^^^", stream.src);
    /*В*///console.log("^eventId^^^^^^^^^^^^^^^^^^", eventId);
    /*В*///console.log("^events[index].Id^^^^^^^^^^^^^^^^^^", events[index].Id);
    const monitor = monitors.find((o) => {
      return parseInt(o["id"]) === monitorId;
    });
    const startDateTime = new Date(events[index].StartDateTime);
    const currentDateTime =  new Date(timeline.getCurrentTime());

    //Требуется проверить завершено ли воспроизвдение события.
    //Например, событие №1 воспроизводилось и закончилось, следующее событие №2 еще не наступило
    //И в этот момент пытаемся запустить событие №1, будет ошибка, т.к. сокет уже закрыт!
    //НЕТ такой ситуации Вторая ситуация - Событие №1 и №2 закончили воспроизведенение.
    //Мы пытаемся запустить повторно событие №1, да и пофиг, запускай, оно уже не будет текущим
    //его и не будет в таблице eventsTable и оно НЕ будет текущим....
    //А нет, мы еще не знаем текущее оно у нас или нет....
    var eventPlayed = true;
    /*В*///console.log("++++-----eventsTable==>>", eventsTable);
    const eventInfo = getEventInfoFromEventsTable({what: 'current', eid: eventId});
    /*В*///console.log("++++-----eventId==>>", eventId);
    /*В*///console.log("++++-----eventInfo==>>", eventInfo);
    if (eventInfo) {
      if (eventInfo.status != 'started') {
        eventPlayed = false;
      }
    } else {
      eventPlayed = false;
    }

    //if (eventId == events[index].Id && eventPlayed) {
    //if (eventPlayed && !getEventInfoFromEventsTable({what: 'current', mid: monitorId}).zmsBroke) {
    if (eventPlayed && !getEventInfoFromEventsTable({what: 'current', eid: eventId}).zmsBroke) {
      if (String(eventId) == String(eventInfo.eventId)) {
        //Текущее событие, которое воспроизводилось.
        ////console.log("*currentDateTime===>", currentDateTime);
        ////console.log("*startDateTime===>", startDateTime);
        //ВАЖНО!!! А тут требуется проверять ответ на выполнение команды, 
        //т.к. сокет может уже закрыться из за длительной паузы и нужно будет повторно запускать...

        streamReq({command: CMD_PLAY, monitorId: monitorId, connkey: connkey, eventId: eventId, monitorUrl: monitor.url});
        //А еще если запустить событие и недожидаясь пока событие начнет воспроизводится еще раз щелкнуть по нем, то будет ошибка "getCmdResponse stream error:"
        streamReq({
          command: CMD_SEEK,
          monitorId: monitorId,
          offset: (currentDateTime.getTime()-startDateTime.getTime())/1000,
          connkey: connkey,
          eventId: eventId,
          monitorUrl: monitor.url
        });
      }
    } else {
      //const baseURL = (streamSrc[events[index].Id].indexOf('http') == -1) ? ZM_HOME_URL : undefined;
      //url = new URL(streamSrc[events[index].Id], baseURL);
      url = newURL(streamSrc[events[index].Id]);
      url.searchParams.set('frame', frameCalculationByTime(currentDateTime, events[index].StartDateTime, events[index].EndDateTime, events[index].Frames));
      url.searchParams.set('rate', parseFloat(speeds[speedIndex]) * 100);
      /* ПОПЫТКА работать через команды, пока не работает... */
      //connkey = url.searchParams.get('connkey');
      /*setTimeout(function() {
        // ВРЕМЕННО ТАК НЕ РАБОТАЕТ, НУЖНА ЗАДЕРЖКА !!!
        streamReq({
          command: CMD_SEEK,
          offset: (currentDateTime.getTime()-startDateTime.getTime())/1000,
          connkey: connkey,
          monitorUrl: monitor.url
        });
      }, 3500);
      */

      ////console.log("+++++++speedIndex+++++=>", speedIndex);

      //stream.src = '';
      //stream.src = streamSrc[events[index].Id];
      //stream.src = url;
      /*В*///console.log("+++++++stream.src+++++=>", stream.src);
      /*В*///console.log("+++++++NEW URL+++++=>", url.href);
      //fillTableEvents('current', monitorId, events[index], data.streamSrc[events[index].Id]);
      if (monitorId != prevMonitorId) {
        //Необходимо остановить событие, если оно воспроизводиолось.
        //if (getEventInfoFromEventsTable({what: 'current', mid: monitorId}).status == 'started') {
        //console.log("*+*+*+*+*+*stopEvent!!!!!=>", monitorId);
        //  stopEvent(monitorId);
        //}
        fillTableEvents('current', monitorId, events[index], url.href);
        //Необходимо очистить следующее событие, т.к. оно теперь не актуально.
        clearEventDataInEventsTable({what: 'next', mid: monitorId});
        //Запустим сразу, не будем дожидаться (дожидаясь) регулярного опроса.
        //Походу ошибки будут валиться, что сокет уже открыт, т.к. будут две попытки запустить событие.
        //Нужно разобраться, почему срабатывает второй запуск по таймеру.......
        //startEvent(monitorId);
      } else {
        //Случай, когда одно событие пересекается с другим !!!
        //Сразу необходимо внести и следующее (пересекающееся) событие
        //Если будет пересекаться ТРИ события - совсем плохо, но вероятность такого стремится к нулю.
        fillTableEvents('next', monitorId, events[index], url.href);
      }
      eventsTable[monitorId].current.zmsBroke = false;
      setTriggerChangedMonitors(monitorId);
    }

    clearTextCanvas( monitorId );
    prevMonitorId = monitorId;
    //        if (stream.src) {
    //          const url = new URL(stream.src);
    //          url.searchParams.set('scale', parseInt(stream.clientWidth / monitors[i].width * 100));
    //          stream.src = url;
    //        }

    //////////////////////////////          fillTableEvents('current', monitorId, events[index], data.streamSrc[events[index].Id]);
  }
  for (var i = 0; i < monitorsWOEvents.length; ++i) {
    //Необходимо остановить и очистить все старые события
    //stopEvent(monitorsWOEvents[i], false);
    stopEvent(monitorsWOEvents[i]);
    clearEventDataInEventsTable({what: 'current', mid: monitorsWOEvents[i]});
    clearEventDataInEventsTable({what: 'next', mid: monitorsWOEvents[i]});
  }
}

function checkEventEnded(currentDateTime, monitorId) {
  //Ничего возвращать не должно, только перезаполнить eventsTable!
  //Пока так.....
  //return false;
  var result = false;
  var eventInfo = getEventInfoFromEventsTable({what: 'current', mid: monitorId});
  var startDateTime = eventInfo.start;
  var endDateTime = eventInfo.end;
  if (!startDateTime) {
    //Значит нет еще события. Вероятно на момент начала воспроизведения  не было события.
    const eventInfoNext = getEventInfoFromEventsTable({what: 'next', mid: monitorId});
    if (eventInfoNext.start) {
      //Сдвинем все.
      eventsTable[monitorId].prev = structuredClone(eventsTable[monitorId].current);
      eventsTable[monitorId].current = structuredClone(eventsTable[monitorId].next);
      //eventsTable[monitorId].current.status = 'started';
      eventInfo = getEventInfoFromEventsTable({what: 'current', mid: monitorId});
      startDateTime = eventsTable[monitorId].current.start;
      endDateTime = eventsTable[monitorId].current.end;
      /*В*///console.log("******СДВИГ, т.к. CURRENT ПУСТОЙ*****");
      /*В*///console.log("**************eventsTable=>>", eventsTable);
    } else {
      //нет ни времени текущего, ни последующего события.
      /*В*///console.log("******НЕТ ВРЕМЕНИ СОВСЕМ*****", eventsTable);
      return null;
    }
  }

  if (currentDateTime >= startDateTime && currentDateTime < endDateTime ) {
    //Требуется проверить, воспроизводится ли уже новое событие или еще нет.
    //++ для отладки
//    const url = new URL(eventInfo.src);
//    const connkey = url.searchParams.get('connkey');
    //-- для отладки

    /*В*///console.log("+++start_Event_connkey=", connkey);
    /*В*///console.log("+++start_Event_eventId=", eventInfo.eventId);
    /*В*///console.log("+++start_Event_status=", eventInfo.status);
    /*В*///console.log("+++start_Event_start=", eventInfo.start);
    if (eventInfo.status != 'started'){
      /*В*///console.log("**start_Event ", eventInfo);
      /*В*///console.log("**start_Event_eventId=", eventInfo.eventId);
      /*В*///console.log("**start_Event_status=", eventInfo.status);
      /*В*///console.log("**start_Event_start=", eventInfo.start);
      startEvent(monitorId);
    }
    //Событие воспроизодится
    //console.log("**Событие ",startDateTime," для ID=>", monitorId, " воспроизводится" );
  } else if (currentDateTime < startDateTime) {
    //Событие в ожидании времени для воспроизведения
    //console.log("**Событие ",startDateTime," для ID=>", monitorId, " в ожидании времени для воспроизведения" );
   //writeTextCanvas(monitorId, 'No Event');
  } else if (currentDateTime >= endDateTime) {
    //Событие окончило воспроизведение и требуется запустить следующее событие
    //Обновим надпись на Canvas (ВКЛЮЧИМ Режим ожидания)
    //console.log("**Событие ",startDateTime," для ID=>", monitorId, " окончило воспроизведение" );
    if (getEventInfoFromEventsTable({what: 'current', mid: monitorId}).status != 'stoped') {
      stopEvent(monitorId, false);
      //writeTextCanvas(monitorId, 'No Event');
      //console.log("**************eventsTable=>>", eventsTable);
      result = true;
    }
  }
  return result;
}

function fillTableEvents(what, monitorId, event, streamSrc) {
  ////console.log("+++fillTableEvents_event=>", event);
  const eventId = event.Id;
  const startDateTime = event.StartDateTime;
  const endDateTime = event.EndDateTime;

  if (what == 'current') {
    eventsTable[monitorId].current = {
      monitorId: monitorId,
      eventId: eventId,
      start: startDateTime,
      end: endDateTime,
      src: streamSrc,
      width: event.Width,
      length: event.Length,
      frames: event.Frames,
      cause: event.Cause,
      status: 'waiting', //stoped, started, waiting
      zmsBroke: false //Use alternate navigation if zms has crashed
    };
  } if (what == 'next') {
    //eventsTable[monitorId].current = structuredClone(eventsTable[monitorId].next);
    eventsTable[monitorId].next = {
      monitorId: monitorId,
      eventId: eventId,
      start: startDateTime,
      end: endDateTime,
      src: streamSrc,
      width: event.Width,
      length: event.Length,
      frames: event.Frames,
      cause: event.Cause,
      status: 'waiting', //stoped, started, waiting - (ближайший следующий), next - (следующий)
      zmsBroke: false //Use alternate navigation if zms has crashed
    };
  } if (what == 'prev') {
    //We are temporarily not using...
  }
  ////console.log("********eventsTable", eventsTable);
}

function checkNextEvent() {
  const currentDateTime = dateTimeToISOLocal(timeline.getCurrentTime());
  for (let i=0, length = monitors.length; i < length; i++) {
    const monitorId = monitors[i].id;
    //Здесь еще требуется проверка окончания проигрывания checkEventEnded()
    const currentEventInfo = getEventInfoFromEventsTable({what: 'current', mid: monitorId});
    const nextEventInfo = getEventInfoFromEventsTable({what: 'next', mid: monitorId});

    checkEventEnded(currentDateTime, monitorId);
    if (nextEventInfo.status == 'notAvailable') {
      continue;
    }

    const currentEventId = currentEventInfo.eventId;
    const nextEventId = nextEventInfo.eventId;
    //if ((currentEventId == nextEventId && currentEventInfo.status != 'waiting' && nextEventInfo.status != 'notAvailable') || 
      //(!nextEventId && nextEventInfo.status != 'notAvailable'))
    //if ((currentEventId == nextEventId && nextEventInfo.status != 'waiting') || 
    if ((currentEventId == nextEventId && currentEventInfo.status != 'stoped' && currentEventInfo.status != 'waiting') || 
      (!nextEventId))
    {
      $j.getJSON(thisUrl, {
        request: 'montage', 
        montage_mode: 'inRecording',
        StartDateTime: currentDateTime,
        MonitorsId: [monitorId],
        montage_action: 'queryNextEventForMonitor'
      })
        .done(function(data) {
          /*В*///console.log("++++++checkNextEvent", monitorId, data);
          //const events = data.events;
          /*В*///console.log("++++++Текущий events", data.events);
          console.log("+++++checkNextEvent for monitor ID=", monitorId, data.events);
          if (data.events.length != 0) {
            //ВАЖНО ! Понять, почему при двойном клике по Timeline в момент воспроизведения тут нет событий!!!
            // Толи это баг, толи нормально !
            fillTableEvents('next', monitorId, data.events[0], data.streamSrc[data.events[0].Id]);
          } else {
            // Это нормально, значит нет следующего события.
            updateEventStatusInEventsTable({what: 'next', mid: monitorId, statusValue: 'notAvailable'});
          }
      })
        .fail(logAjaxFail);
      //А так же нужно запустить то что ниже..... ?????
    } else {
    
    }
  }
}

function fillTimelineEvents (params={}) {
  const events = params.events;
  const allEventCount = params.allEventCount;
  var index;
  var itemsEvent = [];
  for (index = 0; index < events.length; ++index) {
    const eventId = events[index].Id;
    const start = new Date(events[index].StartDateTime);
    const end = new Date(events[index].EndDateTime);
    const classArchived = (events[index].Archived) ? "event-archived" : "";
    const classEvent = (parseInt(events[index].Length) > 20*60) ? "bad_event_timeline" : "event_timeline";
    eventsOnTimeline.push(eventId);
    itemsEvent.push({
      start: start,
      end: end,
      group: events[index].MonitorId,
      className: classEvent + " " + classArchived,
      content: msToTime(end - start) + "(" + events[index].Cause + ")",
      id: eventId,
      style: 'height:25px',
      title: 'Event ID=' + eventId + '<br>' + 'Ctrl+Click - Open event in new window'
    });
  }
  //console.log("itemsEvent===>", itemsEvent);
  var items = new vis.DataSet(itemsEvent);

  if (!timelineExtraInfo) {
    const el = document.createElement('p');
    el.id = 'timeline-extra-info';
    createdTimelineExtraInfo.append(el);
    timelineExtraInfo = $j(el);
  }
  timelineExtraInfo.html(" " + allEventCount + " " + translate["events"]);
  timeline.setItems(items);
}
/* ----- TimeLine*/

// Kick everything off
$j(window).on('load', () => initPage());

/*
window.onbeforeunload = function(e) {
  console.log('unload');
  //event.preventDefault();
  for (let i = 0, length = monitorData.length; i < length; i++) {
    monitors[i].kill();
  }
  var e = e || window.event;

  // For IE and Firefox
  if (e) {
    e.returnValue = undefined;
  }

  // For Safari
  return undefined;
};
*/
