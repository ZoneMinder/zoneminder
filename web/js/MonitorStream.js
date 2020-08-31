
function MonitorStream(monitorData) {
  this.id = monitorData.id;
  this.connKey = monitorData.connKey;
  this.url = monitorData.url;
  this.width = monitorData.width;
  this.height = monitorData.height;
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
} // end function MonitorStream
