var requestQueue = new Request.Queue({
  concurrent: monitorData.length,
  stopOnFailure: false
});

function Monitor(monitorData) {
  this.id = monitorData.id;
  this.connKey = monitorData.connKey;
  this.url = monitorData.url;
  this.status = null;
  this.alarmState = STATE_IDLE;
  this.lastAlarmState = STATE_IDLE;
  this.streamCmdParms = 'view=request&request=stream&connkey='+this.connKey;
  if ( auth_hash ) {
    this.streamCmdParms += '&auth='+auth_hash;
  } else if ( auth_relay ) {
    this.streamCmdParms += '&'+auth_relay;
  }
  this.streamCmdTimer = null;
  this.type = monitorData.type;
  this.refresh = monitorData.refresh;
  this.start = function(delay) {
    if ( this.streamCmdQuery ) {
      this.streamCmdTimer = this.streamCmdQuery.delay(delay, this);
    } else {
      console.log("No streamCmdQuery");
    }
  };

  this.eventHandler = function(event) {
    console.log(event);
  };

  this.onclick = function(evt) {
    var el = evt.currentTarget;
    var tag = 'watch';
    var id = el.getAttribute("data-monitor-id");
    var width = el.getAttribute("data-width");
    var height = el.getAttribute("data-height");
    var url = '?view=watch&mid='+id;
    var name = 'zmWatch'+id;
    evt.preventDefault();
    createPopup(url, name, tag, width, height);
  };

  this.setup_onclick = function() {
    var el = document.getElementById('imageFeed'+this.id);
    if ( el ) el.addEventListener('click', this.onclick, false);
  };
  this.disable_onclick = function() {
    document.getElementById('imageFeed'+this.id).removeEventListener('click', this.onclick );
  };

  this.setStateClass = function(element, stateClass) {
    if ( !element.hasClass( stateClass ) ) {
      if ( stateClass != 'alarm' ) {
        element.removeClass('alarm');
      }
      if ( stateClass != 'alert' ) {
        element.removeClass('alert');
      }
      if ( stateClass != 'idle' ) {
        element.removeClass('idle');
      }
      element.addClass(stateClass);
    }
  };

  this.onError = function(text, error) {
    console.log('onerror: ' + text + ' error:'+error);
    // Requeue, but want to wait a while.
    var streamCmdTimeout = 10*statusRefreshTimeout;
    this.streamCmdTimer = this.streamCmdQuery.delay(streamCmdTimeout, this);
  };
  this.onFailure = function(xhr) {
    console.log('onFailure: ' + this.connKey);
    console.log(xhr);
    if ( ! requestQueue.hasNext("cmdReq"+this.id) ) {
      console.log("Not requeuing because there is one already");
      requestQueue.addRequest("cmdReq"+this.id, this.streamCmdReq);
    }
    if ( 0 ) {
    // Requeue, but want to wait a while.
      if ( this.streamCmdTimer ) {
        this.streamCmdTimer = clearTimeout( this.streamCmdTimer );
      }
      var streamCmdTimeout = 1000*statusRefreshTimeout;
      this.streamCmdTimer = this.streamCmdQuery.delay( streamCmdTimeout, this, true );
      requestQueue.resume();
    }
    console.log("done failure");
  };

  this.getStreamCmdResponse = function(respObj, respText) {
    if ( this.streamCmdTimer ) {
      this.streamCmdTimer = clearTimeout( this.streamCmdTimer );
    }

    var stream = $j('#liveStream'+this.id)[0];

    if ( respObj.result == 'Ok' ) {
      if ( respObj.status ) {
        this.status = respObj.status;
        this.alarmState = this.status.state;

        var stateClass = "";
        if ( this.alarmState == STATE_ALARM ) {
          stateClass = "alarm";
        } else if ( this.alarmState == STATE_ALERT ) {
          stateClass = "alert";
        } else {
          stateClass = "idle";
        }

        if ( (!COMPACT_MONTAGE) && (this.type != 'WebSite') ) {
          $('fpsValue'+this.id).set('text', this.status.fps);
          $('stateValue'+this.id).set('text', stateStrings[this.alarmState]);
          this.setStateClass($('monitorState'+this.id), stateClass);
        }
        this.setStateClass($('monitor'+this.id), stateClass);

        /*Stream could be an applet so can't use moo tools*/
        stream.className = stateClass;

        var isAlarmed = ( this.alarmState == STATE_ALARM || this.alarmState == STATE_ALERT );
        var wasAlarmed = ( this.lastAlarmState == STATE_ALARM || this.lastAlarmState == STATE_ALERT );

        var newAlarm = ( isAlarmed && !wasAlarmed );
        var oldAlarm = ( !isAlarmed && wasAlarmed );

        if ( newAlarm ) {
          if ( false && SOUND_ON_ALARM ) {
            // Enable the alarm sound
            $('alarmSound').removeClass('hidden');
          }
          if ( POPUP_ON_ALARM ) {
            windowToFront();
          }
        }
        if ( false && SOUND_ON_ALARM ) {
          if ( oldAlarm ) {
            // Disable alarm sound
            $('alarmSound').addClass('hidden');
          }
        }
        if ( this.status.auth ) {
          if ( this.status.auth != auth_hash ) {
            // Try to reload the image stream.
            if ( stream ) {
              stream.src = stream.src.replace(/auth=\w+/i, 'auth='+this.status.auth);
            }
            console.log("Changed auth from " + auth_hash + " to " + this.status.auth);
            auth_hash = this.status.auth;
          }
        } // end if have a new auth hash
      } // end if has state
    } else {
      console.error(respObj.message);
      // Try to reload the image stream.
      if ( stream ) {
        if ( stream.src ) {
          console.log('Reloading stream: ' + stream.src);
          stream.src = stream.src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
        } else {
        }
      } else {
        console.log('No stream to reload?');
      }
    } // end if Ok or not

    var streamCmdTimeout = statusRefreshTimeout;
    // The idea here is if we are alarmed, do updates faster.
    // However, there is a timeout in the php side which isn't getting modified,
    // so this may cause a problem. Also the server may only be able to update so fast.
    //if ( this.alarmState == STATE_ALARM || this.alarmState == STATE_ALERT ) {
    //streamCmdTimeout = streamCmdTimeout/5;
    //}
    this.streamCmdTimer = this.streamCmdQuery.delay(streamCmdTimeout, this);
    this.lastAlarmState = this.alarmState;
  };

  this.streamCmdQuery = function(resent) {
    if ( resent ) {
      console.log(this.connKey+": timeout: Resending");
      this.streamCmdReq.cancel();
    }
    //console.log("Starting CmdQuery for " + this.connKey );
    if ( this.type != 'WebSite' ) {
      this.streamCmdReq.send(this.streamCmdParms+"&command="+CMD_QUERY);
    }
  };

  if ( this.type != 'WebSite' ) {
    this.streamCmdReq = new Request.JSON( {
      url: this.url,
      method: 'get',
      timeout: AJAX_TIMEOUT,
      onSuccess: this.getStreamCmdResponse.bind(this),
      onTimeout: this.streamCmdQuery.bind(this, true),
      onError: this.onError.bind(this),
      onFailure: this.onFailure.bind(this),
      link: 'cancel'
    } );
    console.log("queueing for " + this.id + " " + this.connKey + " timeout is: " + AJAX_TIMEOUT);
    requestQueue.addRequest("cmdReq"+this.id, this.streamCmdReq);
  }
} // end function Monitor

/**
 * called when the layoutControl select element is changed, or the page
 * is rendered
 * @param {*} element - the event data passed by onchange callback
 */
function selectLayout(element) {
  layout = $j(element).val();

  if ( layout_id = parseInt(layout) ) {
    layout = layouts[layout];

    for ( var i = 0, length = monitors.length; i < length; i++ ) {
      monitor = monitors[i];
      // Need to clear the current positioning, and apply the new

      monitor_frame = $j('#monitorFrame'+monitor.id);
      if ( !monitor_frame ) {
        console.log('Error finding frame for ' + monitor.id);
        continue;
      }

      // Apply default layout options, like float left
      if ( layout.Positions['default'] ) {
        styles = layout.Positions['default'];
        for ( style in styles ) {
          monitor_frame.css(style, styles[style]);
        }
      } else {
        console.log("No default styles to apply" + layout.Positions);
      } // end if default styles

      if ( layout.Positions['mId'+monitor.id] ) {
        styles = layout.Positions['mId'+monitor.id];
        for ( style in styles ) {
          monitor_frame.css(style, styles[style]);
          console.log("Applying " + style + ' : ' + styles[style]);
        }
      } else {
        console.log("No Monitor styles to apply");
      } // end if specific monitor style
    } // end foreach monitor
  } // end if a stored layout
  if ( ! layout ) {
    return;
  }
  Cookie.write('zmMontageLayout', layout_id, {duration: 10*365});
  if ( layouts[layout_id].Name != 'Freeform' ) { // 'montage_freeform.css' ) {
    Cookie.write( 'zmMontageScale', '', {duration: 10*365} );
    $('scale').set('value', '');
    $('width').set('value', 'auto');
    for ( var i = 0, length = monitors.length; i < length; i++ ) {
      var monitor = monitors[i];
      var streamImg = $('liveStream'+monitor.id);
      if ( streamImg ) {
        if ( streamImg.nodeName == 'IMG' ) {
          var src = streamImg.src;
          src = src.replace(/width=[\.\d]+/i, 'width=0' );
          if ( $j('#height').val() == 'auto' ) {
            src = src.replace(/height=[\.\d]+/i, 'height=0' );
            streamImg.style.height = 'auto';
          }
          if ( src != streamImg.src ) {
            streamImg.src = '';
            streamImg.src = src;
          }
        } else if ( streamImg.nodeName == 'APPLET' || streamImg.nodeName == 'OBJECT' ) {
          // APPLET's and OBJECTS need to be re-initialized
        }
        streamImg.style.width = '100%';
      }
    } // end foreach monitor
  }
} // end function selectLayout(element)

/**
 * called when the widthControl|heightControl select elements are changed
 */
function changeSize() {
  var width = $('width').get('value');
  var height = $('height').get('value');

  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    var monitor = monitors[i];

    // Scale the frame
    monitor_frame = $j('#monitorFrame'+monitor.id);
    if ( !monitor_frame ) {
      console.log("Error finding frame for " + monitor.id);
      continue;
    }
    if ( width ) {
      monitor_frame.css('width', width);
    }
    if ( height ) {
      monitor_frame.css('height', height);
    }

    /*Stream could be an applet so can't use moo tools*/
    var streamImg = $('liveStream'+monitor.id);
    if ( streamImg ) {
      if ( streamImg.nodeName == 'IMG' ) {
        var src = streamImg.src;
        streamImg.src = '';
        src = src.replace(/width=[\.\d]+/i, 'width='+width);
        src = src.replace(/height=[\.\d]+/i, 'height='+height);
        src = src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
        streamImg.src = src;
      }
      streamImg.style.width = width ? width : null;
      streamImg.style.height = height ? height : null;
      //streamImg.style.height = '';
    }
  }
  $('scale').set('value', '');
  Cookie.write('zmMontageScale', '', {duration: 10*365});
  Cookie.write('zmMontageWidth', width, {duration: 10*365});
  Cookie.write('zmMontageHeight', height, {duration: 10*365});
  //selectLayout('#zmMontageLayout');
} // end function changeSize()

/**
 * called when the scaleControl select element is changed
 */
function changeScale() {
  var scale = $('scale').get('value');
  $('width').set('value', 'auto');
  $('height').set('value', 'auto');
  Cookie.write('zmMontageScale', scale, {duration: 10*365});
  Cookie.write('zmMontageWidth', '', {duration: 10*365});
  Cookie.write('zmMontageHeight', '', {duration: 10*365});
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
          src = src.replace(/width=[\.\d]+/i, 'width='+newWidth);
          src = src.replace(/height=[\.\d]+/i, 'height='+newHeight);
        } else {
          src = src.replace(/scale=[\.\d]+/i, 'scale=100');
          src = src.replace(/width=[\.\d]+/i, 'width='+monitorData[i].width);
          src = src.replace(/height=[\.\d]+/i, 'height='+monitorData[i].height);
        }
        streamImg.src = src;
      }
      if ( scale != '0' ) {
        streamImg.style.width = newWidth + "px";
        streamImg.style.height = newHeight + "px";
      } else {
        streamImg.style.width = '100%';
        streamImg.style.height = 'auto';
      }
    }
  }
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
    monitor.setup_onclick();

    //monitor_feed = $j('#imageFeed'+monitor.id);
    //monitor_feed.click(monitor.onclick);
  };
  selectLayout('#zmMontageLayout');
}

function reloadWebSite(ndx) {
  document.getElementById('imageFeed'+ndx).innerHTML = document.getElementById('imageFeed'+ndx).innerHTML;
}

var monitors = new Array();
function initPage() {
  jQuery(document).ready(function() {
    jQuery("#hdrbutton").click(function() {
      jQuery("#flipMontageHeader").slideToggle("slow");
      jQuery("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
      Cookie.write( 'zmMontageHeaderFlip', jQuery('#hdrbutton').hasClass('glyphicon-menu-up') ? 'up' : 'down', {duration: 10*365} );
    });
  });
  if ( Cookie.read('zmMontageHeaderFlip') == 'down' ) {
    // The chosen dropdowns require the selects to be visible, so once chosen has initialized, we can hide the header
    jQuery("#flipMontageHeader").slideToggle("fast");
    jQuery("#hdrbutton").toggleClass('glyphicon-menu-down').toggleClass('glyphicon-menu-up');
  }

  for ( var i = 0, length = monitorData.length; i < length; i++ ) {
    monitors[i] = new Monitor(monitorData[i]);

    // Start the fps and status updates. give a random delay so that we don't assault the server
    var delay = Math.round( (Math.random()+0.5)*statusRefreshTimeout );
    console.log("delay: " + delay);
    monitors[i].start(delay);

    var interval = monitors[i].refresh;
    if ( monitors[i].type == 'WebSite' && interval > 0 ) {
      setInterval(reloadWebSite, interval*1000, i);
    }
    monitors[i].setup_onclick();
  }
  selectLayout('#zmMontageLayout');
}
// Kick everything off
window.addEventListener('DOMContentLoaded', initPage);
