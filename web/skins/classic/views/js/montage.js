"use strict";
const monitors = new Array();
var monitors_ul = null;
var idle = 0;

const VIEWING = 0;
const EDITING = 1;

var mode = 0; // start up in viewing mode

var objGridStack;

var layoutColumns = 48; //Maximum number of columns (items per row) for GridStack
var changedMonitors = []; //Monitor IDs that were changed in the DOM
var scrollBbarExists = false;
var movableMonitorData = []; //Monitor data (id, width, stop (true - stop moving))

var panZoomEnabled = true; //Add it to settings in the future
var panZoomMaxScale = 10;
var panZoomStep = 0.3;
var panZoom = [];
var shifted;
var ctrled;

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

function isPresetLayout(name) {
  name = (name == "Auto") ? "Freeform" : name;
  return ((ZM_PRESET_LAYOUT_NAMES.indexOf(name) != -1) ? true : false);
}

function getCurrentNameLayout() {
  return layouts[parseInt($j('#zmMontageLayout').val())].Name;
}

function setSpeed(newSpeed) {
  lastSpeed = currentSpeed;
  currentSpeed = newSpeed;
  setCookie('speed', String(currentSpeed), 3600);
  for (let i=0, length = monitors.length; i < length; i++) {
    const monitorStream = monitors[i];
    if (lastSpeed != '0' && currentSpeed != '0') {
      monitorStream.setMaxFPS(currentSpeed);
    } else if (lastSpeed != '0') {
      monitorStream.pause();
      // pause
    } else {
      // play
      monitorStream.play();
    }
  }
}

function speedChange(ddm) {
  lastSpeed = $j(ddm).val();
  if (lastSpeed == '0') {
    pausedClicked();
  } else {
    playClicked();
  }
}

function pauseClicked() {
  console.log('pauseClicked');
  setSpeed('0');
  $j('#playBtn').show();
  $j('#pauseBtn').hide();
  $j('#speed').val(speed);
}

function playClicked() {
  console.log(lastSpeed);
  if (!lastSpeed) lastSpeed = 'auto';
  setSpeed(lastSpeed);
  $j('#playBtn').hide();
  $j('#pauseBtn').show();
  $j('#speed').val(speed);
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

      if (nameLayout == "Freeform") {
        monitor_wrapper.attr('gs-w', layoutColumns / stringToNumber(freeform_layout_id)).removeAttr('gs-x').removeAttr('gs-y').removeAttr('gs-h');
        //monitor_wrapper.attr('gs-w', 12).removeAttr('gs-x').removeAttr('gs-y').removeAttr('gs-h');
      } else {
        monitor_wrapper.attr('gs-w', widthFrame).removeAttr('gs-x').removeAttr('gs-y').removeAttr('gs-h');
      }
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
  changeMonitorStatusPositon(); //!!! After loading the saved layer, you must execute.
  monitorsSetScale();
  */
  setCookie('zmMontageLayout', layout_id);
} // end function selectLayout(element)


function changeHeight() { //Not used
/*  var height = $j('#height').val();
  setCookie('zmMontageHeight', height);
  for (var i = 0, length = monitors.length; i < length; i++) {
    const monitor = monitors[i];
    const monitor_frame = $j('#monitor'+monitor.id + " .monitorStream");
    monitor_frame.css('height', height);
  }
*/}

/**
 * called when the widthControl select element is changed
 */

function changeWidth() { //Not used
/*  const width = $j('#width').val();
  const height = $j('#height').val();

  selectLayout(freeform_layout_id);
  $j('#width').val(width);
  $j('#height').val(height);

  for (let i = 0, length = monitors.length; i < length; i++) {
    monitors[i].setScale('0', width, height, false);
  }
  $j('#scale').val('0');
  setCookie('zmMontageScale', '0');
  setCookie('zmMontageWidth', width);
  setCookie('zmMontageHeight', height);
*/} // end function changeSize()


/**
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
function setSelected(objSel, value) {
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

  //objGridStack.compact('list', true); //???
  //selectLayout(); //???

  setCookie('zmMontageRatioForAll', value);
  setSelectedRatioForAllMonitors(value);
  setTriggerChangedMonitors();
}

/*Called from a form*/
function changeRatio(el) {
  const objSelect = el.target;

  //objGridStack.compact('list', true); //???
  //selectLayout(); //???

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
  $j('.btn-edit-monitor').addClass('hidden');
  $j('.btn-fullscreen').addClass('hidden');

  // Turn off the onclick & disable panzoom on the image.
  for ( let i = 0, length = monitors.length; i < length; i++ ) {
    const monitor = monitors[i];
    monitor.disable_onclick();
    if (panZoomEnabled) {
      panZoomAction('disable', {id: monitors[i].id}); //Disable zoom and pan
    }
  };

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

  var Positions = {};
  Positions['gridStack'] = objGridStack.save(false, false);
  Positions['monitorStatusPositon'] = $j('#monitorStatusPositon').val(); //Not yet used when reading Layout
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
  $j('.btn-edit-monitor').removeClass('hidden');
  $j('.btn-fullscreen').removeClass('hidden');

  if (panZoomEnabled) {
    $j('.zoompan').each( function() {
      panZoomAction('enable', {obj: this}); //Enable zoom and pan
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
    // $j.getJSON(thisUrl + '?request=modal&modal=delconfirm')
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
  for (let i = 0, length = monitorData.length; i < length; i++) {
    monitors[i].kill();
  }
  const monitor_ids = monitorData.map((monitor)=>{
    return monitor.id;
  });
  post('?view=snapshot', {'action': 'create', 'monitor_ids[]': monitor_ids});
}

function handleClick(evt) {
  evt.preventDefault();
  var id;

  if (evt.target.id) { //We are looking for an object with an ID, because there may be another element in the button.
    var obj = evt.target;
  } else {
    var obj = evt.target.parentElement;
  }

  if (mode == EDITING || obj.className.includes('btn-zoom-out') || obj.className.includes('btn-zoom-in')) return;
  if (obj.className.includes('btn-view-watch')) {
    const el = evt.currentTarget;
    id = el.getAttribute("data-monitor-id");
    const url = '?view=watch&mid='+id;
    if (evt.ctrlKey) {
      window.open(url, '_blank');
    } else {
      window.location.assign(url);
    }
  } else if (obj.className.includes('btn-edit-monitor')) {
    const el = evt.currentTarget;
    id = el.getAttribute("data-monitor-id");
    const url = '?view=monitor&mid='+id;
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

  if (obj.getAttribute('id').indexOf("liveStream") >= 0) {
    id = stringToNumber(obj.getAttribute('id'));

    if (ctrled && shifted) {
      return;
    } else if (ctrled) {
      panZoom[id].zoom(1, {animate: true});
    } else if (shifted) {
      const scale = panZoom[id].getScale() * Math.exp(panZoomStep);
      const point = {clientX: event.clientX, clientY: event.clientY};
      panZoom[id].zoomToPoint(scale, point, {focal: {x: event.clientX, y: event.clientY}});
    }
    setTriggerChangedMonitors(id);
    //updateScale = true;
  }
}

function startMonitors() {
  for (let i = 0, length = monitorData.length; i < length; i++) {
    const obj = document.getElementById('liveStream'+monitors[i].id);
    const url = new URL(obj.src);

    url.searchParams.set('scale', parseInt(obj.clientWidth / monitors[i].width * 100));
    obj.src = url;

    // Start the fps and status updates. give a random delay so that we don't assault the server
    const delay = Math.round( (Math.random()+0.5)*statusRefreshTimeout );
    monitors[i].start(delay);
    if ((monitors[i].type == 'WebSite') && (monitors[i].refresh > 0)) {
      setInterval(reloadWebSite, monitors.refresh*1000, i);
    }
    monitors[i].setup_onclick(handleClick);
  }
}

function stopMonitors() { //Not working yet.
  for (let i = 0, length = monitorData.length; i < length; i++) {
    //monitors[i].stop();
    //monitors[i].kill();
    monitors[i].streamCommand(CMD_QUIT);
  }
  monitors.length = 0;
}

function pauseMonitors() {
  for (let i = 0, length = monitorData.length; i < length; i++) {
    monitors[i].pause();
  }
}

function playMonitors() {
  for (let i = 0, length = monitorData.length; i < length; i++) {
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
    panZoom[stringToNumber(event.target.id)].reset();
  }
}

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

/*
* Id - Monitor ID
* The function will probably be moved to the main JS file
*/
function manageCursor(Id) {
  const obj = document.getElementById('liveStream'+Id);
  const currentScale = panZoom[Id].getScale().toFixed(1);

  if (shifted && ctrled) {
    obj.closest('.zoompan').style['cursor'] = 'not-allowed';
  } else if (shifted) {
    obj.closest('.zoompan').style['cursor'] = 'zoom-in';
  } else if (ctrled) {
    if (currentScale == 1.0) {
      obj.closest('.zoompan').style['cursor'] = 'auto';
    } else {
      obj.closest('.zoompan').style['cursor'] = 'zoom-out';
    }
  } else {
    if (currentScale == 1.0) {
      obj.closest('.zoompan').style['cursor'] = 'auto';
    } else {
      obj.closest('.zoompan').style['cursor'] = 'move';
    }
  }
}

function initPage() {
  monitors_ul = $j('#monitors');

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
        if ($j('#monitorStatusPositon').val() == 'showOnHover') {
          $j(this).find('#monitorStatus'+id).removeClass('hidden');
        }
        $j('#button_zoom' + id).stop(true, true).slideDown('fast');
        $j('#ratioControl' + id).stop(true, true).slideDown('fast');
      },
      function() {
        const id = stringToNumber(this.id);
        if ($j('#monitorStatusPositon').val() == 'showOnHover') {
          $j(this).find('#monitorStatus'+id).addClass('hidden');
        }
        $j('#button_zoom' + id).stop(true, true).slideUp('fast');
        $j('#ratioControl' + id).stop(true, true).slideUp('fast');
      }
  );

  const arrRatioMonitors = [];
  for (let i = 0, length = monitorData.length; i < length; i++) {
    monitors[i] = new MonitorStream(monitorData[i]);
    //Create a Ratio array for each monitor
    const r = monitors[i].width / monitors[i].height;
    arrRatioMonitors.push(r > 1 ? r : 1/r); //landscape or portret orientation

    //Prepare the array.
    movableMonitorData[monitors[i].id] = {'width': 0, 'stop': false};
  }

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
    }, 10*1000);
    setInterval(function() {
      if (idle > ZM_WEB_VIEWING_TIMEOUT) {
        for (let i=0, length = monitors.length; i < length; i++) monitors[i].pause();
        let ayswModal = $j('#AYSWModal');
        if (!ayswModal.length) {
          $j.getJSON('?request=modal&modal=areyoustillwatching')
              .done(function(data) {
                ayswModal = insertModalHtml('AYSWModal', data.html);
                $j('#AYSWYesBtn').on('click', function() {
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

  setInterval(() => { //Updating GridStack resizeToContent, Scale & Ratio
    if (changedMonitors.length > 0) {
      changedMonitors.forEach(function(item, index, object) {
        const value = getSelected(document.getElementById("ratio"+item));
        const img = document.getElementById('liveStream'+item);
        const currentMonitor = monitors.find((o) => {
          return parseInt(o["id"]) === item;
        });
        if (value == 'real') {
          img.style['height'] = 'auto';
          img.parentNode.style['height'] = 'auto';
        } else {
          const partsRatio = value.split(':');
          const monitorRatioSel = partsRatio[0]/partsRatio[1];
          const ratio = (value == 'auto') ? averageMonitorsRatio : monitorRatioSel;
          const h = (currentMonitor.width / currentMonitor.height > 1) ? (img.clientWidth / ratio + 'px') /*landscape*/ : (img.clientWidth * ratio + 'px');
          img.style['height'] = h;
          img.parentNode.style['height'] = h;
        }

        if (img.offsetHeight > 20 && objGridStack) { //Required for initial page loading
          objGridStack.resizeToContent(document.getElementById('m'+item));
          changedMonitors.splice(index, 1);
        }
        monitorsSetScale(item);
      });
    }
  }, 200);

  setTimeout(() => {
    selectLayout();
    $j('#monitors').removeClass('hidden-shift');
  }, 50); //No matter what flickers. But perhaps this will not be necessary in the future...
  changeMonitorStatusPositon();

  if (panZoomEnabled) {
    $j('.zoompan').each( function() {
      panZoomAction('enable', {obj: this});
      const id = stringToNumber(this.querySelector("[id^='liveStream']").id);
      $j(document).on('keyup keydown', function(e) {
        shifted = e.shiftKey ? e.shiftKey : e.shift;
        ctrled = e.ctrlKey;
        manageCursor(id);
      });
      this.addEventListener('mousemove', function(e) {
        //Temporarily not use
      });
    });
  }

  // Creating a ResizeObserver Instance
  const observer = new ResizeObserver((objResizes) => {
    const blockContent = document.getElementById('content');
    const currentScrollBbarExists = blockContent.scrollHeight > blockContent.clientHeight;
    if (currentScrollBbarExists != scrollBbarExists) {
      scrollBbarExists = currentScrollBbarExists;
      return;
    }
    objResizes.forEach((obj) => {
      const id = stringToNumber(obj.target.id);
      if (mode != EDITING && !changedMonitors.includes(id)) {
        changedMonitors.push(id);
      }
    });
  });

  // Registering an observer on an element
  $j('[id ^= "liveStream"]').each(function() {
    observer.observe(this);
  });

  //Check if the monitor arrangement is complete
  const intervalIdWidth = setInterval(() => {
    if (checkEndMonitorsChange()) {
      startMonitors();
      clearInterval(intervalIdWidth);
    }
  }, 100);
} // end initPage

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

function initGridStack(grid=null) {
  const opts = {
    margin: 0,
    cellHeight: '1px',
    sizeToContent: true, // default to make them all fit
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

  addEvents(objGridStack);
};

function addEvents(grid, id) {
  //let g = (id !== undefined ? 'grid' + id + ' ' : '');
  grid.on('change', function(event, items) {
    /* Occurs when widgets change their position/size due to constrain or direct changes */
    items.forEach(function(item) {
      const currentMonitorId = stringToNumber(item.id); //We received the ID of the monitor whose size was changed
      //setTriggerChangedMonitors(currentMonitorId);
      //monitorsSetScale(currentMonitorId);
      setTriggerChangedMonitors(currentMonitorId);
    });

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

/*
param = param['obj'] : DOM object
param = param['id'] : monitor id
*/
function panZoomAction(action, param) {
  if (action == "enable") { //Enable all object
    const i = stringToNumber($j(param['obj']).children('[id ^= "liveStream"]')[0].id);
    $j('.btn-zoom-in').removeClass('hidden');
    $j('.btn-zoom-out').removeClass('hidden');
    panZoom[i] = Panzoom(param['obj'], {
      minScale: 1,
      step: panZoomStep,
      maxScale: panZoomMaxScale,
      contain: 'outside',
      cursor: 'auto',
    });
    //panZoom[i].pan(10, 10);
    //panZoom[i].zoom(1, {animate: true});
    // Binds to shift + wheel
    param['obj'].parentElement.addEventListener('wheel', function(event) {
      if (!shifted) {
        return;
      }
      panZoom[i].zoomWithWheel(event);
      setTriggerChangedMonitors(i);
    });
  } else if (action == "disable") { //Disable a specific object
    $j('.btn-zoom-in').addClass('hidden');
    $j('.btn-zoom-out').addClass('hidden');
    panZoom[param['id']].reset();
    panZoom[param['id']].resetStyle();
    panZoom[param['id']].setOptions({disablePan: true, disableZoom: true});
    panZoom[param['id']].destroy();
  }
}

function panZoomIn(el) {
  if (el.target.id) {
    var id = stringToNumber(el.target.id);
  } else { //There may be an element without ID inside the button
    var id = stringToNumber(el.target.parentElement.id);
  }
  if (el.ctrlKey) {
    // Double the zoom step.
    panZoom[id].zoom(panZoom[id].getScale() * Math.exp(panZoomStep*2), {animate: true});
  } else {
    panZoom[id].zoomIn();
  }
  setTriggerChangedMonitors(id);
  manageCursor(id);
}

function panZoomOut(el) {
  if (el.target.id) {
    var id = stringToNumber(el.target.id);
  } else {
    var id = stringToNumber(el.target.parentElement.id);
  }
  if (el.ctrlKey) {
    // Reset zoom
    panZoom[id].zoom(1, {animate: true});
  } else {
    panZoom[id].zoomOut();
  }
  setTriggerChangedMonitors(id);
  manageCursor(id);
}

function monitorsSetScale(id=null) {
  //This function will probably need to be moved to the main JS file, because now used on Watch & Montage pages
  if (id || typeof monitorStream !== 'undefined') {
    //monitorStream used on Watch page.
    if (typeof monitorStream !== 'undefined') {
      var currentMonitor = monitorStream;
    } else {
      var currentMonitor = monitors.find((o) => {
        return parseInt(o["id"]) === id;
      });
    }
    const el = document.getElementById('liveStream'+id);
    if (panZoomEnabled) {
      var panZoomScale = panZoom[id].getScale();
    } else {
      var panZoomScale = 1;
    }
    currentMonitor.setScale(0, el.clientWidth * panZoomScale + 'px', el.clientHeight * panZoomScale + 'px', {resizeImg: false});
  } else {
    for ( let i = 0, length = monitors.length; i < length; i++ ) {
      const id = monitors[i].id;
      const el = document.getElementById('liveStream'+id);
      if (panZoomEnabled) {
        var panZoomScale = panZoom[id].getScale();
      } else {
        var panZoomScale = 1;
      }
      monitors[i].setScale(0, parseInt(el.clientWidth * panZoomScale) + 'px', parseInt(el.clientHeight * panZoomScale) + 'px', {resizeImg: false});
    }
  }
}

/*
* Sets the monitor image change flag for positioning recalculation
*/
function setTriggerChangedMonitors(id=null) {
  if (id) {
    if (!changedMonitors.includes(id)) {
      changedMonitors.push(id);
    }
  } else {
    $j('[id ^= "liveStream"]').each(function f() {
      const i = stringToNumber(this.id);
      if (!changedMonitors.includes(i)) {
        changedMonitors.push(i);
      }
    });
  }
}

function checkEndMonitorsChange() {
  for (let i = 0, length = monitorData.length; i < length; i++) {
    const id = monitors[i].id;

    if (!movableMonitorData[id].stop) {
      //Monitor is still moving
      const objWidth = document.getElementById('liveStream'+monitors[i].id).clientWidth;
      if (objWidth == movableMonitorData[id].width && objWidth !=0 ) {
        movableMonitorData[id].stop = true; //The size does not change, which means it’s already in its place!
      } else {
        movableMonitorData[id].width = objWidth;
      }
    }
  }
  //Check if all monitors are in their places
  for (let i = 0, length = movableMonitorData.length; i < length; i++) {
    var monitorsEndMoving = true;

    if (movableMonitorData[i]) { //There may be empty elements
      if (!movableMonitorData[i].stop) {
        //Monitor is still moving
        monitorsEndMoving = false;
        return;
      }
    }
  }
  return monitorsEndMoving;
}

function changeMonitorStatusPositon() {
  const monitorStatusPositon = $j('#monitorStatusPositon').val();
  $j('.monitorStatus').each(function updateStatusPosition() {
    if (monitorStatusPositon == 'insideImgBottom' || monitorStatusPositon == 'showOnHover') {
      $j(this).addClass('bottom');
      if (monitorStatusPositon == 'showOnHover') {
        $j(this).addClass('hidden');
      } else {
        $j(this).removeClass('hidden');
      }
    } else if (monitorStatusPositon == 'outsideImgBottom') {
      $j(this).removeClass('bottom');
      $j(this).removeClass('hidden');
    } else if (monitorStatusPositon == 'hidden') {
      $j(this).addClass('hidden');
    }
    setTriggerChangedMonitors(stringToNumber(this.id));
  });
  setCookie('zmMonitorStatusPositonSelected', monitorStatusPositon);
}

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
