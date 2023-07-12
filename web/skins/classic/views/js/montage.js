"use strict";
const monitors = new Array();
var monitors_ul = null;

const VIEWING = 0;
const EDITING = 1;

var mode = 0; // start up in viewing mode

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

  for (let i = 0, length = monitors.length; i < length; i++) {
    const monitor = monitors[i];
    // Need to clear the current positioning, and apply the new
    const monitor_frame = $j('#monitor'+monitor.id);
    if (!monitor_frame) {
      console.log('Error finding frame for ' + monitor.id);
      continue;
    }

    // Apply default layout options, like float left
    if (layout.Positions['default']) {
      const styles = layout.Positions['default'];
      for (const style in styles) {
        monitor_frame.css(style, styles[style]);
      }
    } else {
      console.log("No default styles to apply" + layout.Positions);
    } // end if default styles

    if (layout.Positions['mId'+monitor.id]) {
      const styles = layout.Positions['mId'+monitor.id];
      for (const style in styles) {
        monitor_frame.css(style, styles[style]);
      }
    } // end if specific monitor style
  } // end foreach monitor
  setCookie('zmMontageLayout', layout_id, 3600);
  if (layouts[layout_id].Name != 'Freeform') { // 'montage_freeform.css' ) {
    // For freeform, we don't touch the width/height/scale settings, but we may need to update sizing and scales
    setCookie('zmMontageScale', '0', 3600);
    setCookie('zmMontageWidth', 'auto', 3600);
    //setCookie('zmMontageHeight', 'auto', 3600);
    $j('#scale').val('0');
    $j('#width').val('auto');
    //$j('#height').val('auto');
  }

  for (let i = 0, length = monitors.length; i < length; i++) {
    monitors[i].setScale( $j('#scale').val(), $j('#width').val(), $j('#height').val());
  } // end foreach monitor
} // end function selectLayout(element)

function changeHeight() {
  const height = $j('#height').val();
  setCookie('zmMontageHeight', height, 3600);
  for (let i = 0, length = monitors.length; i < length; i++) {
    const monitor = monitors[i];
    const monitor_frame = $j('#monitor'+monitor.id + " .monitorStream");
    monitor_frame.css('height', height);
  }
}

/**
 * called when the widthControl select element is changed
 */
function changeWidth() {
  const width = $j('#width').val();
  const height = $j('#height').val();

  selectLayout(freeform_layout_id);
  $j('#width').val(width);
  $j('#height').val(height);

  for (let i = 0, length = monitors.length; i < length; i++) {
    monitors[i].setScale('0', width, height);
  }
  $j('#scale').val('0');
  setCookie('zmMontageScale', '0', 3600);
  setCookie('zmMontageWidth', width, 3600);
  setCookie('zmMontageHeight', height, 3600);
} // end function changeSize()

/**
 * called when the scaleControl select element is changed
 */
function changeScale() {
  const scale = $j('#scale').val();
  selectLayout(freeform_layout_id); // Will also clear width and height
  $j('#scale').val(scale);
  setCookie('zmMontageScale', scale, 3600);
  setCookie('zmMontageWidth', 'auto', 3600);
  setCookie('zmMontageHeight', 'auto', 3600);
  $j('#width').val('auto');
  $j('#height').val('auto');

  for ( let i = 0, length = monitors.length; i < length; i++ ) {
    const monitor = monitors[i];
    monitor.setScale(scale);
  } // end foreach Monitor
}

function toGrid(value) {
  return Math.round(value / 80) * 80;
}

// Makes monitors draggable.
function edit_layout(button) {
  mode = EDITING;

  // Turn off the onclick on the image.
  for ( let i = 0, length = monitors.length; i < length; i++ ) {
    const monitor = monitors[i];
    monitor.disable_onclick();
  };

  $j('#monitors .monitor').draggable({
    cursor: 'crosshair',
    //revert: 'invalid'
  });
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
    if ( name=='Freeform' || name=='2 Wide' || name=='3 Wide' || name=='4 Wide' || name=='5 Wide' ) {
      alert('You cannot edit the built in layouts.  Please give the layout a new name.');
      return;
    } else if (user.Id && (layout.UserId != user.Id) && !canEdit('System') && (name != layout.Name)) {
      alert('You cannot edit someone else\'s layouts.  Please give the layout a new name.');
      return;
    }
  } else if ( name=='Freeform' || name=='2 Wide' || name=='3 Wide' || name=='4 Wide' || name=='5 Wide' ) {
    alert('You cannot use that name. It conflicts with the built in layouts.  Please give the layout a new name.');
    return;
  }


  // In fixed positioning, order doesn't matter.  In floating positioning, it does.
  var Positions = {};
  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];
    const monitor_frame = $j('#monitor'+monitor.id);

    Positions['mId'+monitor.id] = {
      width: monitor_frame.css('width'),
      height: monitor_frame.css('height'),
      top: monitor_frame.css('top'),
      bottom: monitor_frame.css('bottom'),
      left: monitor_frame.css('left'),
      right: monitor_frame.css('right'),
      position: monitor_frame.css('position'),
      float: monitor_frame.css('float'),
    };
  } // end foreach monitor
  form.Positions.value = JSON.stringify(Positions);
  form.submit();
} // end function save_layout

function cancel_layout(button) {
  mode = VIEWING;
  $j('#SaveLayout').hide();
  $j('#EditLayout').show();
  for ( let i = 0, length = monitors.length; i < length; i++ ) {
    const monitor = monitors[i];
    monitor.setup_onclick(handleClick);

    //monitor_feed = $j('#imageFeed'+monitor.id);
    //monitor_feed.click(monitor.onclick);
  };
  selectLayout(freeform_layout_id);
}

function reloadWebSite(ndx) {
  document.getElementById('imageFeed'+ndx).innerHTML = document.getElementById('imageFeed'+ndx).innerHTML;
}

function takeSnapshot() {
  for (let i = 0, length = monitorData.length; i < length; i++) {
    monitors[i].kill();
  }
  monitor_ids = monitorData.map((monitor)=>{
    return monitor.id;
  });
  post('?view=snapshot', {'action': 'create', 'monitor_ids[]': monitor_ids});
}

function handleClick(evt) {
  evt.preventDefault();
  if (mode == EDITING) return;

  const el = evt.currentTarget;
  const id = el.getAttribute("data-monitor-id");

  const url = '?view=watch&mid='+id;
  if (evt.ctrlKey) {
    window.open(url, '_blank');
  } else {
    window.location.assign(url);
  }
}

function initPage() {
  monitors_ul = $j('#monitors');
  $j("#hdrbutton").click(function() {
    $j("#flipMontageHeader").slideToggle("slow");
    $j("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
    setCookie('zmMontageHeaderFlip', $j('#hdrbutton').hasClass('glyphicon-menu-up') ? 'up' : 'down', 3600);
  });
  if (getCookie('zmMontageHeaderFlip') == 'down') {
    // The chosen dropdowns require the selects to be visible, so once chosen has initialized, we can hide the header
    $j("#flipMontageHeader").slideToggle("fast");
    $j("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
  }
  for (let i = 0, length = monitorData.length; i < length; i++) {
    monitors[i] = new MonitorStream(monitorData[i]);
  }

  selectLayout();
  for (let i = 0, length = monitorData.length; i < length; i++) {
    // Start the fps and status updates. give a random delay so that we don't assault the server
    const delay = Math.round( (Math.random()+0.5)*statusRefreshTimeout );
    monitors[i].start(delay);

    if ((monitors[i].type == 'WebSite') && (monitors[i].refresh > 0)) {
      setInterval(reloadWebSite, monitors.refresh*1000, i);
    }
    monitors[i].setup_onclick(handleClick);
  }

  // If you click on the navigation links, shut down streaming so the browser can process it
  document.querySelectorAll('#main-header-nav a').forEach(function(el) {
    el.onclick = function() {
      for (let i = 0, length = monitors.length; i < length; i++) {
        if (monitors[i]) monitors[i].kill();
      }
    };
  });
}

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

// Kick everything off
$j(document).ready(initPage);

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
