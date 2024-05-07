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

var panZoomEnabled = true; //Add it to settings in the future
var panZoom = [];

function stringToNumber(str) {
  return parseInt(str.replace(/\D/g, ''));
}

function isPresetLayout (name) {
  return (( name=='Freeform' || name=='1 Wide' || name=='2 Wide' || name=='3 Wide' || name=='4 Wide' || name=='5 Wide' || name=='6 Wide' || name=='7 Wide' || name=='8 Wide' || name=='9 Wide' || name=='10 Wide' || name=='11 Wide' || name=='12 Wide' || name=='16 Wide' ) ? true : false)
}

function getCurentNameLayout() {
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
  const ddm = $j('#zmMontageLayout');
  if (new_layout_id && (typeof(new_layout_id) != 'object')) {
    ddm.val(new_layout_id);
  }
  const layout_id = parseInt(ddm.val());
  if (!layout_id) {
    console.log("No layout_id?!");
    return;
  }

  const layout = layouts[layout_id];
  if (!layout) {
    console.log("No layout found for " + layout_id);
    return;
  }

  const nameLayout = layout.Name;

  const widthFrame = layoutColumns / stringToNumber(nameLayout);

  if (objGridStack)
    objGridStack.destroy(false);

  if (isPresetLayout(nameLayout)) { //PRESET 
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
        monitor_wrapper.attr('gs-w', 6).removeAttr('gs-x').removeAttr('gs-y').removeAttr('gs-h');
      } else {
        monitor_wrapper.attr('gs-w', widthFrame).removeAttr('gs-x').removeAttr('gs-y').removeAttr('gs-h');
      }  
    }
    initGridStack();  
  } else { //CUSTOM
    for (let i = 0, length = monitors.length; i < length; i++) {
      const monitor = monitors[i];
      // Need to clear the current positioning, and apply the new
      const monitor_frame = $j('#monitor'+monitor.id);
      if (!monitor_frame) {
        console.log('Error finding frame for ' + monitor.id);
        continue;
      }
    }

    if (layout.Positions.gridStack) {
      initGridStack(layout.Positions.gridStack);  
    } else { //Probably the layout was saved in the old (until May 2024) version of ZM
      initGridStack();  
      $j('#messageModal').modal('show');
    }
  }
  
  changeMonitorStatusPositon(); //!!! After loading the saved layer, you must execute.
  monitorsSetScale();
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

function toGrid(value) { //Not used
/*  return Math.round(value / 80) * 80;*/
}

// Makes monitors draggable.
function edit_layout(button) {
  mode = EDITING;
  $j('.grid-stack-item-content').addClass('modeEditingMonitor');
  objGridStack.enable(); //Enable move
//  objGridStack.float(true);
 
  $j('.btn-view-watch').addClass('hidden'); 
 
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
  mode = VIEWING;

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

  var Positions = {};
  Positions['gridStack'] = objGridStack.save(false, false);
  Positions['monitorStatusPositon'] = $j('#monitorStatusPositon').val(); //Not yet used when reading Layout
  form.Positions.value = JSON.stringify(Positions, null, '  ');
  form.submit();
} // end function save_layout

function cancel_layout(button) {
  mode = VIEWING;
  //$j(monitors_ul).removeClass('modeEditingMonitor');
  $j('.grid-stack-item-content').removeClass('modeEditingMonitor');
  objGridStack.disable(); //Disable move
  $j('.btn-view-watch').removeClass('hidden'); 

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
console.log('evt', evt);

  if (evt.target.id) { //Ищем объект с ID, т.к. в кнопке может быть еще элемент.
    var obj = evt.target;
  } else {
    var obj = evt.target.parentElement;
  }
  
//  if (mode == EDITING || evt.target.id == 'btn-zoom-out' || evt.target.id == 'btn-zoom-in') return;
  if (mode == EDITING || obj.className.includes('btn-zoom-out') || obj.className.includes('btn-zoom-in')) return;
//console.log('evt', evt);
//return;
  if (obj.className.includes('btn-view-watch')) {
    const el = evt.currentTarget;
    const id = el.getAttribute("data-monitor-id");
    const url = '?view=watch&mid='+id;
    if (evt.ctrlKey) {
      window.open(url, '_blank');
    } else {
      window.location.assign(url);
    }
  } else if (obj.className.includes('btn-edit-monitor')) {
    const el = evt.currentTarget;
    const id = el.getAttribute("data-monitor-id");
    const url = '?view=monitor&mid='+id;
    if (evt.ctrlKey) {
      window.open(url, '_blank');
    } else {
      window.location.assign(url);
    }
  }
}

function startMonitors() {
  for (let i = 0, length = monitorData.length; i < length; i++) {
    // Start the fps and status updates. give a random delay so that we don't assault the server
    const delay = Math.round( (Math.random()+0.5)*statusRefreshTimeout );
console.log("MONITOR PRE START=>", monitors[i]);
    monitors[i].start(delay);

//    monitors[i].setStreamScale();

//      monitors[i].streamCommand({command: CMD_MAXFPS, maxfps: 2});

	
console.log("MONITOR POST START=>", monitors[i]);
//console.log("MONITOR element=>", monitors[i].getElement());
//console.log("MONITOR element onload=>", monitors[i].getElement().onload);
    if ((monitors[i].type == 'WebSite') && (monitors[i].refresh > 0)) {
      setInterval(reloadWebSite, monitors.refresh*1000, i);
    }
    monitors[i].setup_onclick(handleClick);
  }
  
  //        startGrid();

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

function initPage() {
  monitors_ul = $j('#monitors');

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
  if (getCookie('zmMontageLayout')) {
    $j('#zmMontageLayout').val(getCookie('zmMontageLayout'));
  }

  $j(".imageFeed").hover( //Displaying "Scale" and other buttons at the top of the monitor image
    function() {
      const id = stringToNumber(this.closest('.imageFeed').id);
      $j('#button_zoom' + id).stop(true, true).slideDown('fast');
    },
    function() {
      const id = stringToNumber(this.closest('.imageFeed').id);
      $j('#button_zoom' + id).stop(true, true).slideUp('fast');
    }
  );

  for (let i = 0, length = monitorData.length; i < length; i++) {
    monitors[i] = new MonitorStream(monitorData[i]);
  }

  startMonitors();

  $j(window).on('resize', windowResize); //Only used when trying to apply "changeScale". It will be deleted in the future.

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
  
  setInterval(() => { //Updating GridStack resizeToContent
    if (changedMonitors.length > 0) {
      changedMonitors.forEach(function(item, index, object) {
        if (document.getElementById('liveStream'+item).offsetHeight > 20 && objGridStack) {
          objGridStack.resizeToContent(document.getElementById('m'+item));
          changedMonitors.splice(index, 1); 
        }
      });
    }
  }, 200);
  
  setTimeout(() => {
      $j('#monitors').removeClass('hidden-shift');
      selectLayout();
  }, 50); //No matter what flickers. But perhaps this will not be necessary in the future...
  changeMonitorStatusPositon();
  
  if (panZoomEnabled) {
    $j('.zoompan').each( function() {
      panZoomAction('enable', {obj: this});
    });
  }
  
  // Creating a ResizeObserver Instance
  const observer = new ResizeObserver((objResizes) => {
    objResizes.forEach((obj) => {
      const id = stringToNumber(obj.target.id);
      if (mode != EDITING && !changedMonitors.includes(id)) {
        changedMonitors.push(id);
      }
    });
  });

  // Registering an observer on an element
  $j('[id ^= "liveStream"]').each(function(){
    observer.observe(this);
  })
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
  let opts = {
    margin: 0,
    cellHeight: '1px',
    sizeToContent: true, // default to make them all fit
    resizable: { handles: 'all'}, // do all sides
    float: false,
    disableDrag: true,
    disableResize: true,
    column: layoutColumns,
  };

  if (grid) {
    objGridStack = GridStack.init({...opts}).load(grid, false); //When loading, we leave all monitors (according to the filters), and not just those that were saved!  } else {
    objGridStack = GridStack.init({...opts});
  }
  //grid.compact('list', false);

  addEvents(objGridStack);
};

function addEvents(grid, id) {
  let g = (id !== undefined ? 'grid' + id + ' ' : '');
  grid.on('change', function(event, items) { /*Occurs when widgets change their position/size due to constrain or direct changes*/
    items.forEach(function(item) { 
      const curentMonitorId = stringToNumber(item.id); //We received the ID of the monitor whose size was changed
      const curentMonitor = monitors.find((o) => { return parseInt(o["id"]) === curentMonitorId });
      monitorsSetScale(curentMonitorId);
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
  .on('dragstop', function(event, el) { /*After the object has been moved*/
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
    let node = el.gridstackNode; // {x, y, width, height, id, ....}
    //let rec = el.getBoundingClientRect();
    //console.log("INFO==>", `${g} resizestop ${node.content || ''} size: (${node.w}x${node.h}) = (${Math.round(rec.width)}x${Math.round(rec.height)})px`);

    const curentMonitorId = stringToNumber(node.el.id); //We received the ID of the monitor whose size was changed
    const curentMonitor = monitors.find((o) => { return parseInt(o["id"]) === curentMonitorId });
    curentMonitor.setScale( $j('#scale').val(), node.el.offsetWidth + 'px', null, false);
  });
}

/*
param = param['obj'] : DOM object
param = param['id'] : monitor id
*/
function panZoomAction (action, param) {
  if (action == "enable") { //Enable all object
    const i = stringToNumber($j(param['obj']).children('[id ^= "liveStream"]')[0].id);
    $j('.btn-zoom-in').removeClass('hidden'); 
    $j('.btn-zoom-out').removeClass('hidden'); 
    panZoom[i] = Panzoom(param['obj'], {
      maxScale: 20,
      contain: 'outside',
      cursor: 'auto',
    })
    panZoom[i].pan(10, 10)
    panZoom[i].zoom(1, { animate: true })
  } else if (action == "disable") { //Disable a specific object
    $j('.btn-zoom-in').addClass('hidden'); 
    $j('.btn-zoom-out').addClass('hidden'); 
    panZoom[param['id']].reset();
    panZoom[param['id']].resetStyle();
    panZoom[param['id']].setOptions({disablePan: true, disableZoom: true});
    panZoom[param['id']].destroy();
  }
}

function panZoomIn (el) {
  if (el.target.id) {
    var id = stringToNumber(el.target.id);
  } else { //There may be an element without ID inside the button
    var id = stringToNumber(el.target.parentElement.id);
  }
  panZoom[id].zoomIn();
  monitorsSetScale(id);
  var el = document.getElementById('liveStream'+id);
  if (panZoom[id].getScale().toFixed(1) != 1.0) {
    el.closest('.zoompan').style['cursor'] = 'move';
  } else {
    el.closest('.zoompan').style['cursor'] = 'auto';
  }  
}

function panZoomOut (el) {
  if (el.target.id) {
    var id = stringToNumber(el.target.id);
  } else {
    var id = stringToNumber(el.target.parentElement.id);
  }
  panZoom[id].zoomOut();
  monitorsSetScale(id);
  var el = document.getElementById('liveStream'+id);
  if (panZoom[id].getScale().toFixed(1) != 1.0) {
    el.closest('.zoompan').style['cursor'] = 'move';
  } else {
    el.closest('.zoompan').style['cursor'] = 'auto';
  }
}

function monitorsSetScale(id=null) {
  if (id) {
    const curentMonitor = monitors.find((o) => { return parseInt(o["id"]) === id });
    const el = document.getElementById('liveStream'+id);
    if (panZoomEnabled) {
      var panZoomScale = panZoom[id].getScale();
    } else {
      var panZoomScale = 1;
    }
    curentMonitor.setScale($j('#scale').val(), el.clientWidth * panZoomScale + 'px', el.clientHeight * panZoomScale + 'px', false);
  } else {
    for ( let i = 0, length = monitors.length; i < length; i++ ) {
      const id = monitors[i].id;
      const el = document.getElementById('liveStream'+id);
      if (panZoomEnabled) {
        var panZoomScale = panZoom[id].getScale();
      } else {
        var panZoomScale = 1;
      }
      monitors[i].setScale($j('#scale').val(), parseInt(el.clientWidth * panZoomScale) + 'px', parseInt(el.clientHeight * panZoomScale) + 'px', false);
    }
  }
}

function changeMonitorStatusPositon() {
  const monitorStatusPositon = $j('#monitorStatusPositon').val();
  $j('.monitorStatus').each(function() {
    if (monitorStatusPositon == 'insideImgBottom') {
      $j(this).addClass('bottom');
      $j(this).removeClass('hidden');
    } else if (monitorStatusPositon == 'outsideImgBottom') {
      $j(this).removeClass('bottom');
      $j(this).removeClass('hidden');
    } else if (monitorStatusPositon == 'hidden') {
      $j(this).addClass('hidden');
    }
    var id = stringToNumber(this.id);
//    if (mode != EDITING && !changedMonitors.includes(id)) {
    if (!changedMonitors.includes(id)) {
      changedMonitors.push(id);
    }
  })

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
