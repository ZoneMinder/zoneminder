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

      monitor_frame = $j('#monitorFrame'+monitor.id);
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
      $j('#scale').val('');
      $j('#width').val('0');
    }
  } // end if a stored layout
  if (!layout) {
    console.log('No layout?');
    return;
  }
  var width = parseInt($j('#width').val());
  var height = parseInt($j('#height').val());
  var scale = $j('#scale').val();

  for (var i = 0, length = monitors.length; i < length; i++) {
    var monitor = monitors[i];

    var stream_scale = 0;
    if (scale) {
      stream_scale = scale;
    } else if (width) {
      stream_scale = parseInt(100*width/monitor.width);
    } else if (height) {
      stream_scale = parseInt(100*height/monitor.height);
    } else if (layouts[layout_id].Name != 'Freeform') {
      monitor_frame = $j('#monitorFrame'+monitor.id);
      console.log("Monitor frame width : " + monitor_frame.width() + " monitor Width: " + monitor.width);
      if (monitor_frame.width() < monitor.width) {
        stream_scale = parseInt(100 * monitor_frame.width() / monitor.width);
        // Round to a multiple of 5, so 53 become 50% etc
        stream_scale = Math.floor(stream_scale/5)*5;
      }
    }
    setStreamScale('liveStream'+monitor.id, stream_scale, width, height);
  } // end foreach monitor
} // end function selectLayout(element)

function setStreamScale(element_id, scale, width, height) {
  var streamImg = document.getElementById(element_id);
  if (streamImg) {
    if (streamImg.nodeName == 'IMG') {
      var src = streamImg.src;
      src = src.replace(/scale=\d*/i, 'scale='+scale);
      if (height == '0') {
        streamImg.style.height = 'auto';
      }
      if (src != streamImg.src) {
        streamImg.src = '';
        streamImg.src = src;
      }
    } else if (streamImg.nodeName == 'APPLET' || streamImg.nodeName == 'OBJECT') {
      // APPLET's and OBJECTS need to be re-initialized
    }
    //streamImg.style.width = '100%';
  }
}

/**
 * called when the widthControl|heightControl select elements are changed
 */
function changeSize() {
  var width = parseInt($j('#width').val());
  var height = parseInt($j('#height').val());

  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];

    // Scale the frame
    monitor_frame = $j('#monitorFrame'+monitor.id);
    if ( !monitor_frame ) {
      console.log("Error finding frame for " + monitor.id);
      continue;
    }
    monitor_frame.css('width', ( width ? width+'px' : 'auto'));
    monitor_frame.css('height', ( height ? height+'px' : 'auto'));

    /*Stream could be an applet so can't use moo tools*/
    var streamImg = monitor.getElement();
    if ( streamImg ) {
      if ( streamImg.nodeName == 'IMG' ) {
        var src = streamImg.src;
        streamImg.src = '';
        var scale = 100;
        if ( width ) {
          scale = parseInt(100*width/monitor.width);
        } else if ( height ) {
          scale = parseInt(100*height/monitor.height);
        }
        // Note zms ignores width and height
        src = src.replace(/scale=\d*/i, 'scale='+scale);

        src = src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
        streamImg.src = src;
      }
      streamImg.style.width = width ? width+'px' : null;
      streamImg.style.height = height ? height+'px' : null;
      //streamImg.style.height = '';
    }
  }
  $j('#scale').val('');
  setCookie('zmMontageScale', '', 3600);
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
  $j('#width').val('0'); //auto
  $j('#height').val('0'); //auto
  setCookie('zmMontageScale', scale, 3600);
  setCookie('zmMontageWidth', '', 3600);
  setCookie('zmMontageHeight', '', 3600);
  if ( scale == '' ) {
    selectLayout('#zmMontageLayout');
    return;
  }
  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];
    var newWidth = ( monitorData[i].width * scale ) / SCALE_BASE;
    var newHeight = ( monitorData[i].height * scale ) / SCALE_BASE;

    // Scale the frame
    monitor_frame = $j('#monitorFrame'+monitor.id);
    if ( !monitor_frame ) {
      console.log("Error finding frame for " + monitor.id);
      continue;
    }
    if ( scale != '0' ) {
      if ( newWidth ) {
        monitor_frame.css('width', newWidth);
      }
    } else {
      monitor_frame.css('width', '100%');
    }
    // We don't set the frame height because it has the status bar as well
    //if ( height ) {
    ////monitor_frame.css('height', height+'px');
    //}
    /*Stream could be an applet so can't use moo tools*/
    var streamImg = $j('#liveStream'+monitor.id)[0];
    if ( streamImg ) {
      if ( streamImg.nodeName == 'IMG' ) {
        var src = streamImg.src;
        streamImg.src = '';

        //src = src.replace(/rand=\d+/i,'rand='+Math.floor((Math.random() * 1000000) ));
        if ( scale != '0' ) {
          src = src.replace(/scale=[\.\d]+/i, 'scale='+scale);
        } else {
          src = src.replace(/scale=[\.\d]+/i, 'scale=100');
        }
        streamImg.src = src;
      }
      if ( scale != '0' ) {
        streamImg.style.width = newWidth + 'px';
        streamImg.style.height = newHeight + 'px';
      } else {
        streamImg.style.width = '100%';
        streamImg.style.height = 'auto';
      }
    } // end if StreamImg
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

var monitors = new Array();
function initPage() {
  $j("#hdrbutton").click(function() {
    $j("#flipMontageHeader").slideToggle("slow");
    $j("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
    setCookie( 'zmMontageHeaderFlip', $j('#hdrbutton').hasClass('glyphicon-menu-up') ? 'up' : 'down', 3600);
  });
  if ( getCookie('zmMontageHeaderFlip') == 'down' ) {
    // The chosen dropdowns require the selects to be visible, so once chosen has initialized, we can hide the header
    $j("#flipMontageHeader").slideToggle("fast");
    $j("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
  }
  for ( var i = 0, length = monitorData.length; i < length; i++ ) {
    monitors[i] = new MonitorStream(monitorData[i]);

    // Start the fps and status updates. give a random delay so that we don't assault the server
    var delay = Math.round( (Math.random()+0.5)*statusRefreshTimeout );
    console.log("delay: " + delay);
    monitors[i].start(delay);

    var interval = monitors[i].refresh;
    if ( monitors[i].type == 'WebSite' && interval > 0 ) {
      setInterval(reloadWebSite, interval*1000, i);
    }
    monitors[i].setup_onclick(handleClick);
  }
  selectLayout('#zmMontageLayout');
}

function watchFullscreen() {
  const content = document.getElementById('content');
  openFullscreen(content);
}

function handleClick(evt) {
  console.log("handleClick");
  var el = evt.currentTarget;
  console.log(el);
  var id = el.getAttribute("data-monitor-id");
  var url = '?view=watch&mid='+id;
  evt.preventDefault();
  window.location.assign(url);
}

// Kick everything off
$j(document).ready(initPage);
