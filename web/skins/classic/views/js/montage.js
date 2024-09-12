
const VIEWING = 0;
const EDITING = 1;

var mode = 0; // start up in viewing mode

/**
 * called when the layoutControl select element is changed, or the page
 * is rendered
 * @param {*} element - the event data passed by onchange callback
 */
function selectLayout(element) {
  var ddm = $j('#zmMontageLayout');
  layout = ddm.val();

  if (layout_id = parseInt(layout)) {
    layout = layouts[layout];

    for (var i = 0, length = monitors.length; i < length; i++) {
      monitor = monitors[i];
      // Need to clear the current positioning, and apply the new

      monitor_frame = $j('#monitor'+monitor.id);
      if (!monitor_frame) {
        console.log('Error finding frame for ' + monitor.id);
        continue;
      }

      // Apply default layout options, like float left
      if (layout.Positions['default'] ) {
        styles = layout.Positions['default'];
        for (style in styles) {
          console.log("Applying " + style + ' ' + styles[style]);
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
      setCookie('zmMontageScale', '', 3600);
      $j('#scale').val('0');
      $j('#width').val('auto');
    }
  } // end if a stored layout
  if (!layout) {
    console.log('No layout?');
    return;
  }
  var width = $j('#width').val();
  var height = $j('#height').val();
  var scale = $j('#scale').val();

  for (var i = 0, length = monitors.length; i < length; i++) {
    var monitor = monitors[i];
    monitor.setScale(scale, width, height);
  } // end foreach monitor
} // end function selectLayout(element)

function changeHeight() {
  var height = $j('#height').val();
  setCookie('zmMontageHeight', height, 3600);
  for (var i = 0, length = monitors.length; i < length; i++) {
    var monitor = monitors[i];
    monitor_frame = $j('#monitor'+monitor.id + " .monitorStream");
    monitor_frame.css('height', height);
    monitor_img = $j('#liveStream'+monitor.id);
    monitor_img.css('height', height);
  }
}

/**
 * called when the widthControl select elements are changed
 */
function changeWidth() {
  var width = $j('#width').val();
  var height = $j('#height').val();

  // Reset frame css
  $j('#zmMontageLayout').val(freeform_layout_id);
  selectLayout();
  $j('#width').val(width);
  $j('#height').val(height);

  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];
    monitor.setScale('0', width, height);
  } // end foreach monitor
  $j('#scale').val('0');
  setCookie('zmMontageScale', '0', 3600);
  setCookie('zmMontageWidth', width, 3600);
  setCookie('zmMontageHeight', height, 3600);
  $j("#zmMontageLayout option:selected").removeAttr("selected");
  //selectLayout('#zmMontageLayout');
} // end function changeSize()

/**
 * called when the scaleControl select element is changed
 */
function changeScale() {
  var scale = $j('#scale').val();
  $j('#width').val('auto');
  $j('#height').val('auto');
  setCookie('zmMontageScale', scale, 3600);
  setCookie('zmMontageWidth', 'auto', 3600);
  setCookie('zmMontageHeight', 'auto', 3600);
  $j('#zmMontageLayout').val(freeform_layout_id);
  selectLayout('#zmMontageLayout');
  for ( let i = 0, length = monitors.length; i < length; i++ ) {
    const monitor = monitors[i];

    // Scale the frame
    monitor_frame = $j('#monitor'+monitor.id);
    if ( !monitor_frame ) {
      console.log("Error finding frame for " + monitor.id);
      continue;
    }
    if ( scale != '0' ) {
      const newWidth = ( monitorData[i].width * scale ) / SCALE_BASE;
      if ( newWidth ) {
        console.log("Setting to " + newWidth);
        monitor_frame.css('width', newWidth);
      }
    }
    monitor_img = $j('#liveStream'+monitor.id);
    monitor_img.css('width', '100%');
    monitor_img.css('height', 'auto');
    monitor.setStreamScale(scale);
  } // end foreach Monitor
}

function toGrid(value) {
  return Math.round(value / 80) * 80;
}

// Makes monitors draggable.
function edit_layout(button) {
  mode = EDITING;

  // Turn off the onclick on the image.
  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];
    monitor.disable_onclick();
  };

  $j('#monitors .monitor').draggable({
    cursor: 'crosshair',
    //revert: 'invalid'
  });
  $j('#SaveLayout').show();
  $j('#EditLayout').hide();
} // end function edit_layout

function save_layout(button) {
  mode = VIEWING;

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
    monitor_frame = $j('#monitor'+monitor.id);

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
  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];
    monitor.setup_onclick();

    //monitor_feed = $j('#imageFeed'+monitor.id);
    //monitor_feed.click(monitor.onclick);
  };
  selectLayout('#zmMontageLayout');
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

var monitors = new Array();
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
  selectLayout('#zmMontageLayout');
  for (let i = 0, length = monitorData.length; i < length; i++) {
    // Start the fps and status updates. give a random delay so that we don't assault the server
    var delay = Math.round((Math.random()+0.5)*statusRefreshTimeout);
    monitors[i].start(delay);

    var interval = monitors[i].refresh;
    if (monitors[i].type == 'WebSite' && interval > 0) {
      setInterval(reloadWebSite, interval*1000, i);
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
// Kick everything off
$j(document).ready(initPage);
