/**
 * called when the layoutControl select element is changed, or the page
 * is rendered
 * @param {*} new_layout_id - the id of a layout to switch to
 */
function selectLayout(new_layout_id) {
  const ddm = $j('#zmMontageLayout');
  if (new_layout_id && (typeof(new_layout_id) != 'object')) {
    console.log("Selecting " + new_layout_id);
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

  for (var i = 0, length = monitors.length; i < length; i++) {
    monitor = monitors[i];
    // Need to clear the current positioning, and apply the new

    monitor_frame = $j('#monitor'+monitor.id);
    if (!monitor_frame) {
      console.log('Error finding frame for ' + monitor.id);
      continue;
    }

    // Apply default layout options, like float left
    if (layout.Positions['default']) {
      styles = layout.Positions['default'];
      for (style in styles) {
        monitor_frame.css(style, styles[style]);
      }
    } else {
      console.log("No default styles to apply" + layout.Positions);
    } // end if default styles

    if (layout.Positions['mId'+monitor.id]) {
      styles = layout.Positions['mId'+monitor.id];
      for (style in styles) {
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

  var width = $j('#width').val();
  var height = $j('#height').val();
  var scale = $j('#scale').val();
  for (var i = 0, length = monitors.length; i < length; i++) {
    var monitor = monitors[i];
    monitor.setScale(scale, width, height);
  } // end foreach monitor
  console.log("Done selectLayout");
} // end function selectLayout(element)

function changeHeight() {
  var height = $j('#height').val();
  setCookie('zmMontageHeight', height, 3600);
  for (var i = 0, length = monitors.length; i < length; i++) {
    var monitor = monitors[i];
    monitor_frame = $j('#monitor'+monitor.id + " .monitorStream");
    monitor_frame.css('height', height);
  }
}

/**
 * called when the widthControl select element is changed
 */
function changeWidth() {
  var width = $j('#width').val();
  var height = $j('#height').val();
  console.log("changeWidth");

  selectLayout(freeform_layout_id);
  $j('#width').val(width);
  $j('#height').val(height);

  for (var i = 0, length = monitors.length; i < length; i++) {
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
  var scale = $j('#scale').val();
  selectLayout(freeform_layout_id); // Will also clear width and height
  $j('#scale').val(scale);
  setCookie('zmMontageScale', scale, 3600);
  setCookie('zmMontageWidth', 'auto', 3600);
  setCookie('zmMontageHeight', 'auto', 3600);
  $j('#width').val('auto');
  $j('#height').val('auto');

  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];
    monitor.setScale(scale);
  } // end foreach Monitor
}

function toGrid(value) {
  return Math.round(value / 80) * 80;
}

// Makes monitorFrames draggable.
function edit_layout(button) {
  // Turn off the onclick on the image.

  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];
    monitor.disable_onclick();
  };

  $j('#monitors .monitorFrame').draggable({
    cursor: 'crosshair',
    //revert: 'invalid'
  });
  $j('#SaveLayout').show();
  $j('#EditLayout').hide();
} // end function edit_layout

function save_layout(button) {
  var form = button.form;
  var name = form.elements['Name'].value;

  if ( !name ) {
    name = form.elements['zmMontageLayout'].options[form.elements['zmMontageLayout'].selectedIndex].text;
  }

  if ( name=='Freeform' || name=='2 Wide' || name=='3 Wide' || name=='4 Wide' || name=='5 Wide' ) {
    alert('You cannot edit the built in layouts.  Please give the layout a new name.');
    return;
  }

  // In fixed positioning, order doesn't matter.  In floating positioning, it does.
  var Positions = {};
  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];
    monitor_frame = $j('#monitorFrame'+monitor.id);

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
  $j('#SaveLayout').hide();
  $j('#EditLayout').show();
  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];
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
  monitor_ids = monitorData.map((monitor)=>{
    return 'monitor_ids[]='+monitor.id;
  });
  window.location = '?view=snapshot&action=create&'+monitor_ids.join('&');
}

function handleClick(evt) {
  evt.preventDefault();

  const el = evt.currentTarget;
  const id = el.getAttribute("data-monitor-id");

  const url = '?view=watch&mid='+id;
  if (evt.ctrlKey) {
    window.open(url, '_blank');
  } else {
    window.location.assign(url);
  }
}

const monitors = new Array();
function initPage() {
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
      for (var i = 0, length = monitors.length; i < length; i++) {
        monitors[i].kill();
      }
    };
  });
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
