
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
    // Step 1 make sure we are streaming instead of a static image
    var stream = $j('#liveStream'+this.id)[0];
    if ( ! stream ) {
      console.log('No live stream');
      return;
    }
    src = stream.src.replace(/mode=single/i, 'mode=jpeg');
    if ( -1 == src.search('connkey') ) {
      src += '&connkey='+this.connKey;
    }
    if ( stream.src != src ) {
      console.log("Setting to streaming");
      stream.src = '';
      stream.src = src;
    }

    if ( this.streamCmdQuery ) {
      this.streamCmdTimer = this.streamCmdQuery.delay(delay, this);
    } else {
      console.log("No streamCmdQuery");
    }

    console.log("queueing for " + this.id + " " + this.connKey + " timeout is: " + AJAX_TIMEOUT);
    requestQueue.addRequest("cmdReq"+this.id, this.streamCmdReq);
  };
  this.stop = function() {
    if ( 0 ) {
      var stream = $j('#liveStream'+this.id)[0];
      if ( ! stream ) {
        console.log('No live stream');
        return;
      }
      src = stream.src.replace(/mode=jpeg/i, 'mode=single');
      if ( stream.src != src ) {
        console.log("Setting to stopped");
        stream.src = '';
        stream.src = src;
      }
    }
    this.streamCmdReq.send(this.streamCmdParms+"&command="+CMD_STOP);
  };
  this.pause = function() {
    this.streamCmdReq.send(this.streamCmdParms+"&command="+CMD_PAUSE);
  };
  this.play = function() {
    this.streamCmdReq.send(this.streamCmdParms+"&command="+CMD_PLAY);
  };

  this.eventHandler = function(event) {
    console.log(event);
  };

  this.onclick = function(evt) {
    var el = evt.currentTarget;
    var id = el.getAttribute("data-monitor-id");
    var url = '?view=watch&mid='+id;
    evt.preventDefault();
    window.location.assign(url);
  };

  this.setup_onclick = function() {
    var el = document.getElementById('imageFeed'+this.id);
    if ( el ) el.addEventListener('click', this.onclick, false);
  };
  this.disable_onclick = function() {
    document.getElementById('imageFeed'+this.id).removeEventListener('click', this.onclick );
  };

  this.setStateClass = function(element, stateClass) {
    if ( !element ) {
      return;
    }
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
    if ( ! requestQueue.hasNext('cmdReq'+this.id) ) {
      console.log('Not requeuing because there is one already');
      requestQueue.addRequest('cmdReq'+this.id, this.streamCmdReq);
    }
    if ( 0 ) {
    // Requeue, but want to wait a while.
      if ( this.streamCmdTimer ) {
        this.streamCmdTimer = clearTimeout( this.streamCmdTimer );
      }
      var streamCmdTimeout = 1000*statusRefreshTimeout;
      this.streamCmdTimer = this.streamCmdQuery.delay(streamCmdTimeout, this, true);
      requestQueue.resume();
    }
    console.log('done failure');
  };

  this.getStreamCmdResponse = function(respObj, respText) {
    if ( this.streamCmdTimer ) {
      this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
    }

    var stream = $j('#liveStream'+this.id)[0];
    if ( ! stream ) {
      console.log('No live stream');
      return;
    }

    if ( respObj.result == 'Ok' ) {
      if ( respObj.status ) {
        this.status = respObj.status;
        this.alarmState = this.status.state;

        var stateClass = '';
        if ( this.alarmState == STATE_ALARM ) {
          stateClass = 'alarm';
        } else if ( this.alarmState == STATE_ALERT ) {
          stateClass = 'alert';
        } else {
          stateClass = 'idle';
        }

        if ( (
          (typeof COMPACT_MONTAGE === 'undefined') ||
          !COMPACT_MONTAGE) &&
          (this.type != 'WebSite')
        ) {
          fpsValue = $('fpsValue'+this.id);
          if ( fpsValue ) {
            fpsValue.set('text', this.status.fps);
          }
          stateValue = $('stateValue'+this.id);
          if ( stateValue ) {
            stateValue.set('text', stateStrings[this.alarmState]);
          }

          monitorState = $('monitorState'+this.id);
          if ( monitorState ) {
            this.setStateClass(monitorState, stateClass);
          }
        }

        this.setStateClass($('monitor'+this.id), stateClass);

        /*Stream could be an applet so can't use moo tools*/
        //stream.parentNode().className = stateClass;

        var isAlarmed = ( this.alarmState == STATE_ALARM || this.alarmState == STATE_ALERT );
        var wasAlarmed = ( this.lastAlarmState == STATE_ALARM || this.lastAlarmState == STATE_ALERT );

        var newAlarm = ( isAlarmed && !wasAlarmed );
        var oldAlarm = ( !isAlarmed && wasAlarmed );

        if ( newAlarm ) {
          if ( false && SOUND_ON_ALARM ) {
            // Enable the alarm sound
            $('alarmSound').removeClass('hidden');
          }
          if ( (typeof POPUP_ON_ALARM !== 'undefined') && POPUP_ON_ALARM ) {
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
          src = stream.src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
          if ( src != stream.src ) {
            stream.src = src;
          } else {
            console.log("Failed to update rand on stream src");
          }
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
      console.log(this.connKey+': timeout: Resending');
      this.streamCmdReq.cancel();
    }
    //console.log("Starting CmdQuery for " + this.connKey );
    if ( this.type != 'WebSite' ) {
      this.streamCmdReq.send(this.streamCmdParms+'&command='+CMD_QUERY);
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
  }
} // end function MonitorStream
