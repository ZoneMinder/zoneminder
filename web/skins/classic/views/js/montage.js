var requestQueue = new Request.Queue( { concurrent: 2 } );

function Monitor( monitorData ) {
  this.id = monitorData.id;
  this.connKey = monitorData.connKey;
  this.server_url = monitorData.server_url;
  this.status = null;
  this.alarmState = STATE_IDLE;
  this.lastAlarmState = STATE_IDLE;
  this.streamCmdParms = 'view=request&request=stream&connkey='+this.connKey;
  this.onclick = monitorData.onclick;
  if ( auth_hash )
    this.streamCmdParms += '&auth='+auth_hash;
  this.streamCmdTimer = null;

  this.start = function( delay ) {
    this.streamCmdTimer = this.streamCmdQuery.delay( delay, this );
  };

  this.setStateClass = function( element, stateClass ) {
    if ( !element.hasClass( stateClass ) ) {
      if ( stateClass != 'alarm' )
        element.removeClass( 'alarm' );
      if ( stateClass != 'alert' )
        element.removeClass( 'alert' );
      if ( stateClass != 'idle' )
        element.removeClass( 'idle' );
      element.addClass( stateClass );
    }
  };

  this.getStreamCmdResponse = function( respObj, respText ) {
    if ( this.streamCmdTimer )
      this.streamCmdTimer = clearTimeout( this.streamCmdTimer );

    var stream = $j('#liveStream'+this.id)[0];

    if ( respObj.result == 'Ok' ) {
      this.status = respObj.status;
      this.alarmState = this.status.state;

      var stateClass = "";
      if ( this.alarmState == STATE_ALARM )
        stateClass = "alarm";
      else if ( this.alarmState == STATE_ALERT )
        stateClass = "alert";
      else
        stateClass = "idle";

      if ( !COMPACT_MONTAGE ) {
        $('fpsValue'+this.id).set( 'text', this.status.fps );
        $('stateValue'+this.id).set( 'text', stateStrings[this.alarmState] );
        this.setStateClass( $('monitorState'+this.id), stateClass );
      }
      this.setStateClass( $('monitor'+this.id), stateClass );

      /*Stream could be an applet so can't use moo tools*/
      stream.className = stateClass;

      var isAlarmed = ( this.alarmState == STATE_ALARM || this.alarmState == STATE_ALERT );
      var wasAlarmed = ( this.lastAlarmState == STATE_ALARM || this.lastAlarmState == STATE_ALERT );

      var newAlarm = ( isAlarmed && !wasAlarmed );
      var oldAlarm = ( !isAlarmed && wasAlarmed );

      if ( newAlarm ) {
        if ( false && SOUND_ON_ALARM ) {
          // Enable the alarm sound
          $('alarmSound').removeClass( 'hidden' );
        }
        if ( POPUP_ON_ALARM ) {
          windowToFront();
        }
      }
      if ( false && SOUND_ON_ALARM ) {
        if ( oldAlarm ) {
          // Disable alarm sound
          $('alarmSound').addClass( 'hidden' );
        }
      }
      if ( this.status.auth ) {
        if ( this.status.auth != auth_hash ) {
          // Try to reload the image stream.
          if ( stream )
            stream.src = stream.src.replace( /auth=\w+/i, 'auth='+this.status.auth );
          console.log("Changed auth from " + auth_hash + " to " + this.status.auth );
          auth_hash = this.status.auth;
        }
      } // end if have a new auth hash
    } else {
      console.error( respObj.message );
      // Try to reload the image stream.
      if ( stream ) {
        if ( stream.src ) {
        console.log('Reloading stream: ' + stream.src );
        stream.src = stream.src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
        } else {
        }
      } else {
        console.log( 'No stream to reload?' );
      }
    } // end if Ok or not
    var streamCmdTimeout = statusRefreshTimeout;
    // The idea here is if we are alarmed, do updates faster.  However, there is a timeout in the php side which isn't getting modified, so this may cause a problem. Also the server may only be able to update so fast. 
    //if ( this.alarmState == STATE_ALARM || this.alarmState == STATE_ALERT ) {
      //streamCmdTimeout = streamCmdTimeout/5;
    //}
    this.streamCmdTimer = this.streamCmdQuery.delay( streamCmdTimeout, this );
    this.lastAlarmState = this.alarmState;
  };

  this.streamCmdQuery = function( resent ) {
    if ( resent )
    console.log( this.connKey+": Resending" );
    //this.streamCmdReq.cancel();
    this.streamCmdReq.send( this.streamCmdParms+"&command="+CMD_QUERY );
  };
  this.onError = function( text, error ) {
    console.log('onerror: ' + text + ' error:'+error);
  };
  this.onFailure = function( xhr ) {
    console.log('onFailure: ' );
    console.log(xhr );
    // Requeue
    var streamCmdTimeout = statusRefreshTimeout;
    this.streamCmdTimer = this.streamCmdQuery.delay( streamCmdTimeout, this );
  };

  this.streamCmdReq = new Request.JSON( {
    url: this.server_url,
    method: 'get',
    timeout: 1000+AJAX_TIMEOUT,
    onSuccess: this.getStreamCmdResponse.bind( this ),
    onTimeout: this.streamCmdQuery.bind( this, true ),
    onError: this.onError.bind(this),
    onFailure: this.onFailure.bind(this),
    link: 'cancel'
  } );

  requestQueue.addRequest( "cmdReq"+this.id, this.streamCmdReq );
}

function selectLayout( element ) {
  layout = $j(element).val();

  if ( layout_id = parseInt(layout) ) {
    layout = layouts[layout];
    console.log(layout);

    for ( var i = 0; i < monitors.length; i++ ) {
      monitor = monitors[i];
      // Need to clear the current positioning, and apply the new

      monitor_frame = $j('#monitorFrame'+monitor.id);
      if ( ! monitor_frame ) {
        console.log("Error finding frame for " + monitor.id );
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
          console.log("Applying " + style + ' : ' + styles[style] );
        }
      } else {
        console.log("No Monitor styles to apply");
      } // end if specific monitor style
    } // end foreach monitor
  }  // end if a stored layout
  if ( ! layout ) {
    return;
  }
  Cookie.write( 'zmMontageLayout', layout_id, { duration: 10*365 } );
  if ( layouts[layout_id].Name != 'Freeform' ) { // 'montage_freeform.css' ) {
    Cookie.write( 'zmMontageScale', '', { duration: 10*365 } );
    $('scale').set('value', '' );
    $('width').set('value', '');

    for ( var x = 0; x < monitors.length; x++ ) {
      var monitor = monitors[x];
      var streamImg = $( 'liveStream'+monitor.id );
      if ( streamImg ) {
        if ( streamImg.nodeName == 'IMG' ) {
          var src = streamImg.src;
          streamImg.src='';
          src = src.replace(/width=[\.\d]+/i,'width=0' );
          src = src.replace(/rand=\d+/i,'rand='+Math.floor((Math.random() * 1000000) ));
          streamImg.src = src;
        } else if ( streamImg.nodeName == 'APPLET' || streamImg.nodeName == 'OBJECT' ) {
          // APPLET's and OBJECTS need to be re-initialized
        }
        streamImg.style.width = '100%';
      }
      var zonesSVG = $('zones'+monitor.id);
      if ( zonesSVG ) {
        zonesSVG.style.width = '';
      }
    } // end foreach monitor
  }
}

function changeSize() {
  var width = $('width').get('value');
  var height = $('height').get('value');

  for ( var x = 0; x < monitors.length; x++ ) {
    var monitor = monitors[x];
  
    // Scale the frame
      monitor_frame = $j('#monitorFrame'+monitor.id);
      if ( ! monitor_frame ) {
        console.log("Error finding frame for " + monitor.id );
        continue;
      }
      if ( width )
        monitor_frame.css('width',width+'px');
      if ( height )
        monitor_frame.css('height',height+'px');
    /*Stream could be an applet so can't use moo tools*/ 
    var streamImg = $( 'liveStream'+monitor.id );
    if ( streamImg ) {
      if ( streamImg.nodeName == 'IMG' ) {
        var src = streamImg.src;
        streamImg.src='';
        src = src.replace(/width=[\.\d]+/i,'width='+width );
        src = src.replace(/height=[\.\d]+/i,'height='+height );
        src = src.replace(/rand=\d+/i,'rand='+Math.floor((Math.random() * 1000000) ));
        streamImg.src = src;
      }
      streamImg.style.width = width? width + "px" : null;
      streamImg.style.height = height ? height + "px" : null;
      //streamImg.style.height = '';
    }
    var zonesSVG = $('zones'+monitor.id);
    if ( zonesSVG ) {
      zonesSVG.style.width = width ? width + "px" : '100%';
      zonesSVG.style.height = height + "px";
    }
  }
  $('scale').set('value', '' );
  Cookie.write( 'zmMontageScale', '', { duration: 10*365 } );
  Cookie.write( 'zmMontageWidth', width, { duration: 10*365 } );
  Cookie.write( 'zmMontageHeight', height, { duration: 10*365 } );
  selectLayout('#zmMontageLayout');
} // end function changeSize()

function changeScale() {
  var scale = $('scale').get('value');
  $('width').set('value', '');
  $('height').set('value', '');
  Cookie.write( 'zmMontageScale', scale, { duration: 10*365 } );
  Cookie.write( 'zmMontageWidth', '', { duration: 10*365 } );
  Cookie.write( 'zmMontageHeight', '', { duration: 10*365 } );
  if ( ! scale ) {
    selectLayout('#zmMontageLayout');
    return;
  }
  for ( var x = 0; x < monitors.length; x++ ) {
    var monitor = monitors[x];
    var newWidth = ( monitorData[x].width * scale ) / SCALE_BASE;
    var newHeight = ( monitorData[x].height * scale ) / SCALE_BASE;

    // Scale the frame
      monitor_frame = $j('#monitorFrame'+monitor.id);
      if ( ! monitor_frame ) {
        console.log("Error finding frame for " + monitor.id );
        continue;
      }
      if ( width )
        monitor_frame.css('width',width+'px');
      if ( height )
        monitor_frame.css('height',height+'px');
    /*Stream could be an applet so can't use moo tools*/
    var streamImg = $j('#liveStream'+monitor.id )[0];
    if ( streamImg ) {
      if ( streamImg.nodeName == 'IMG' ) {
        var src = streamImg.src;
        streamImg.src='';

        //src = src.replace(/rand=\d+/i,'rand='+Math.floor((Math.random() * 1000000) ));
        src = src.replace(/scale=[\.\d]+/i,'scale='+ scale );
        src = src.replace(/width=[\.\d]+/i,'width='+newWidth );
        src = src.replace(/height=[\.\d]+/i,'height='+newHeight );
        streamImg.src = src;
      }
      streamImg.style.width = newWidth + "px";
      streamImg.style.height = newHeight + "px";
    }
    var zonesSVG = $('zones'+monitor.id);
    if ( zonesSVG ) {
      zonesSVG.style.width = newWidth + "px";
      zonesSVG.style.height = newHeight + "px";
    }
  }
}

function toGrid(value) {
  return Math.round(value / 80) * 80;
}

// Makes monitorFrames draggable.
function edit_layout(button) {

  // Turn off the onclick on the image.
  
  for ( var i = 0; i < monitors.length; i++ ) {
    var monitor = monitors[i];
    monitor_feed = $j('#imageFeed'+monitor.id)[0];
    monitor_feed.onclick='';
  };

  $j('#monitors .monitorFrame').draggable({
    cursor: 'crosshair',
    //revert: 'invalid'
  });
  $j('#SaveLayout').show();
  $j('#EditLayout').hide();
} // end function edit_layout

function save_layout(button) {
  var form=button.form;
  // In fixed positioning, order doesn't matter.  In floating positioning, it does.
  var Positions = {};
  for ( var i = 0; i < monitors.length; i++ ) {
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
  form.Positions.value = JSON.stringify( Positions );
  form.submit();
}
function cancel_layout(button) {
  $j('#SaveLayout').hide();
  $j('#EditLayout').show();
  for ( var i = 0; i < monitors.length; i++ ) {
    var monitor = monitors[i];
    monitor_feed = $j('#imageFeed'+monitor.id);
    monitor_feed.click( monitor.onclick );
  };
  selectLayout('#zmMontageLayout');
}

var monitors = new Array();
function initPage() {
  for ( var i = 0; i < monitorData.length; i++ ) {
    monitors[i] = new Monitor(monitorData[i]);
    var delay = Math.round( (Math.random()+0.75)*statusRefreshTimeout );
    monitors[i].start(delay);
  }
  selectLayout('#zmMontageLayout');
}
// Kick everything off
window.addEvent( 'domready', initPage );
