function MonitorStream(monitorData) {
  this.id = monitorData.id;
  this.connKey = monitorData.connKey;
  this.auth_relay = auth_relay;
  this.auth_hash = auth_hash;
  this.url = monitorData.url;
  this.url_to_zms = monitorData.url_to_zms;
  this.width = monitorData.width;
  this.height = monitorData.height;
  this.scale = 100;
  this.status = null;
  this.lastAlarmState = STATE_IDLE;
  this.statusCmdTimer = null;
  this.streamCmdTimer = null;
  this.streamCmdParms = {
    view: 'request',
    request: 'stream',
    connkey: this.connKey
  };
  this.ajaxQueue = null;
  this.type = monitorData.type;
  this.refresh = monitorData.refresh;

  this.buttons = {}; // index by name
  this.setButton = function(name, element) {
    this.buttons[name] = element;
  };

  this.bottomElement = null;
  this.setBottomElement = function(e) {
    if (!e) {
      console.error("Empty bottomElement");
    }
    this.bottomElement = e;
  };

  this.img_onerror = function() {
    console.log('Image stream has been stoppd! stopping streamCmd');
    this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
  };
  this.img_onload = function() {
    if (!this.streamCmdTimer) {
      console.log('Image stream has loaded! starting streamCmd for '+this.connKey);
      this.streamCmdTimer = setTimeout(this.streamCmdQuery.bind(this), statusRefreshTimeout);
    }
  };

  this.element = null;
  this.getElement = function() {
    if (this.element) return this.element;
    this.element = document.getElementById('liveStream'+this.id);
    if (!this.element) {
      console.error("No img for #liveStream"+this.id);
    }
    return this.element;
  };
  this.getFrame = function() {
    if (this.frame) return this.frame;
    this.frame = document.getElementById('imageFeed'+this.id);
    if (!this.frame) {
      console.error("No frame div for #imageFeed"+this.id);
    }
    return this.frame;
  };

  /* if the img element didn't have a src, this would fill it in, causing it to show. */
  this.show = function() {
    const stream = this.getElement();
    if (!stream.src) {
      stream.src = this.url_to_zms+"&mode=single&scale=100&connkey="+this.connKey+this.auth_relay;
    }
  };

  /* scale should be '0' for auto, or an integer value
   * width should be auto, 100%, integer +px
   * height should be auto, 100%, integer +px
   * */
  this.setScale = function(newscale, width, height) {
    const img = this.getElement();
    if (!img) {
      console.log("No img in setScale");
      return;
    }

    this.scale = newscale;

    // Scale the frame
    monitor_frame = $j('#monitor'+this.id);
    if (!monitor_frame) {
      console.log('Error finding frame');
      return;
    }

    if (((newscale == '0') || (newscale==0) || (newscale=='auto')) && (width=='auto' || !width)) {
      if (!this.bottomElement) {
        newscale = parseInt(100*monitor_frame.width() / this.width);
        // We don't want to change the existing css, cuz it might be 59% or 123px or auto;
        width = monitor_frame.css('width');
      } else {
        var newSize = scaleToFit(this.width, this.height, $j(img), $j(this.bottomElement));
        width = newSize.width+'px';
        height = newSize.height+'px';
        newscale = parseInt(newSize.autoScale);
      }
    } else if (parseInt(width) || parseInt(height)) {
      if (width) {
        newscale = parseInt(100*parseInt(width)/this.width);
      } else if (height) {
        newscale = parseInt(100*parseInt(height)/this.height);
        width = parseInt(this.width * newscale / 100)+'px';
      }
    } else {
      // a numeric scale, must take actual monitor dimensions and calculate
      width = Math.round(parseInt(this.width) * newscale / 100)+'px';
      height = Math.round(parseInt(this.height) * newscale / 100)+'px';
    }
    if (width && (width != '0px') &&
      ((monitor_frame[0].style.width===undefined) || (-1 == monitor_frame[0].style.width.search('%')))
      ) {
      monitor_frame.css('width', width);
    }

    //img.style.width = width;
    if (height && height != '0px') img.style.height = height;
    img.setAttribute('width', '100%');
    img.setAttribute('height', 'auto');
    this.setStreamScale(newscale);
  }; // setscale

  this.setStreamScale = function(newscale) {
    const img = this.getElement();
    if (!img) {
      console.log("No img in setScale");
      return;
    }
    const stream_frame = $j('#monitor'+this.id);
    if (!newscale) {
      newscale = parseInt(100*parseInt(stream_frame.width())/this.width);
    }
    if (img.nodeName == 'IMG') {
      if (newscale > 100) newscale = 100; // we never request a larger image, as it just wastes bandwidth
      if (newscale <= 0) newscale = 100;
      const oldSrc = img.src;
      if (!oldSrc) {
        console.log('No src on img?!');
        console.log(img);
        return;
      }
      const newSrc = oldSrc.replace(/scale=\d+/i, 'scale='+newscale);
      if (newSrc != oldSrc) {
        this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
        // We know that only the first zms will get the command because the
        // second can't open the commandQueue until the first exits
        // This is necessary because safari will never close the first image
        if ((-1 != img.src.search('connkey')) && (-1 != img.src.search('mode=single'))) {
          console.log("Sending quit");
          this.streamCommand(CMD_QUIT);
        }
        console.log("Changing src to " + newSrc);
        img.src = '';
        img.src = newSrc;
      }
    }
  }; // setscale

  this.start = function(delay) {
    // zms stream
    const stream = this.getElement();
    if (!stream) return;
    if (!stream.src) {
      // Website Monitors won't have an img tag, neither will video
      console.log('No src for #liveStream'+this.id);
      console.log(stream);
      return;
    }
    this.statusCmdTimer = clearTimeout(this.statusCmdTimer);
    // Step 1 make sure we are streaming instead of a static image
    if (stream.getAttribute('loading') == 'lazy') {
      stream.setAttribute('loading', 'eager');
    }
    src = stream.src.replace(/mode=single/i, 'mode=jpeg');
    if (-1 == src.search('connkey')) {
      src += '&connkey='+this.connKey;
    }
    if (stream.src != src) {
      console.log("Setting to streaming: " + src);
      stream.src = '';
      stream.src = src;
    }
    stream.onerror = this.img_onerror.bind(this);
    stream.onload = this.img_onload.bind(this);
  }; // this.start

  this.stop = function() {
    if ( 0 ) {
      const stream = this.getElement();
      if (!stream) return;
      src = stream.src.replace(/mode=jpeg/i, 'mode=single');
      if (stream.src != src) {
        console.log("Setting to stopped");
        stream.src = '';
        stream.src = src;
      }
    }
    this.streamCommand(CMD_STOP);
    this.statusCmdTimer = clearTimeout(this.statusCmdTimer);
    this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
  };
  this.kill = function() {
    const stream = this.getElement();
    if (!stream) return;
    stream.onerror = null;
    stream.onload = null;
    this.statusCmdTimer = clearTimeout(this.statusCmdTimer);
    this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
  };
  this.pause = function() {
    this.streamCommand(CMD_PAUSE);
  };
  this.play = function() {
    this.streamCommand(CMD_PLAY);
  };

  this.eventHandler = function(event) {
    console.log(event);
  };

  this.onclick = function(evt) {
    console.log('onclick');
  };

  this.setup_onclick = function(func) {
    if (func) {
      this.onclick = func;
    }
    if (this.onclick) {
      const el = this.getFrame();
      if (!el) return;
      el.addEventListener('click', this.onclick, false);
    }
  };

  this.disable_onclick = function() {
    const el = this.getElement();
    if (!el) return;
    el.removeEventListener('click', this.onclick);
  };

  this.onpause = function() {
    console.log('onpause');
  };
  this.setup_onpause = function(func) {
    this.onpause = func;
  };
  this.onplay = null;
  this.setup_onplay = function(func) {
    this.onplay = func;
  };

  this.setStateClass = function(jobj, stateClass) {
    if (!jobj) {
      console.log("No obj in setStateClass");
      return;
    }
    if (!jobj.hasClass(stateClass)) {
      if (stateClass != 'alarm') jobj.removeClass('alarm');
      if (stateClass != 'alert') jobj.removeClass('alert');
      if (stateClass != 'idle') jobj.removeClass('idle');

      jobj.addClass(stateClass);
    }
  };

  this.setAlarmState = function(alarmState) {
    var stateClass = '';
    if (alarmState == STATE_ALARM) {
      stateClass = 'alarm';
    } else if (alarmState == STATE_ALERT) {
      stateClass = 'alert';
    }

    const stateValue = $j('#stateValue'+this.id);
    if (stateValue.length) {
      stateValue.text(stateStrings[alarmState]);
      if (stateClass) {
        stateValue.addClass(stateClass);
      } else {
        stateValue.removeClass();
      }
    }
    const monitorFrame = $j('#monitor'+this.id);
    if (monitorFrame.length) this.setStateClass(monitorFrame, stateClass);

    const isAlarmed = ( alarmState == STATE_ALARM || alarmState == STATE_ALERT );
    const wasAlarmed = ( this.lastAlarmState == STATE_ALARM || this.lastAlarmState == STATE_ALERT );

    const newAlarm = ( isAlarmed && !wasAlarmed );
    const oldAlarm = ( !isAlarmed && wasAlarmed );

    if (newAlarm) {
      if (ZM_WEB_SOUND_ON_ALARM) {
        // Enable the alarm sound
        const isIE = window.document.documentMode ? true : false;
        if (!isIE) {
          $j('#alarmSound').removeClass('hidden');
        } else {
          $j('#MediaPlayer').trigger('play');
        }
      }
      if (ZM_WEB_POPUP_ON_ALARM) {
        window.focus();
      }
      if (this.onalarm) {
        this.onalarm();
      }
    }
    if (oldAlarm) { // done with an event do a refresh
      if (ZM_WEB_SOUND_ON_ALARM) {
        // Disable alarm sound
        const isIE = window.document.documentMode ? true : false;
        if (!isIE) {
          $j('#alarmSound').addClass('hidden');
        } else {
          $j('#MediaPlayer').trigger('pause');
        }
      }
      if (this.onalarm) {
        this.onalarm();
      }
    }
    this.lastAlarmState = alarmState;
  }; // end function setAlarmState( currentAlarmState )

  this.onalarm = null;
  this.setup_onalarm = function(func) {
    this.onalarm = func;
  };

  this.onFailure = function(jqxhr, textStatus, error) {
    // Assuming temporary problem, retry in a bit.

    clearTimeout(this.streamCmdTimer);
    this.streamCmdTimer = setTimeout(this.streamCmdQuery.bind(this), 10*statusRefreshTimeout);
    logAjaxFail(jqxhr, textStatus, error);
    if (error == 'Unauthorized') {
      window.location.reload();
    }
  };

  this.getStreamCmdResponse = function(respObj, respText) {
    var stream = this.getElement();
    if (!stream) {
      return;
    }

    //watchdogOk('stream');
    //this.streamCmdTimer = clearTimeout(this.streamCmdTimer);

    if (respObj.result == 'Ok') {
      if (respObj.status) {
        const streamStatus = this.status = respObj.status;

        if (this.type != 'WebSite') {
          let viewingFPSValue = $j('#viewingFPSValue'+this.id);
          if (!viewingFPSValue.length) viewingFPSValue = $j('#fpsValue'+this.id);
          const captureFPSValue = $j('#captureFPSValue'+this.id);
          const analysisFPSValue = $j('#analysisFPSValue'+this.id);

          this.status.fps = this.status.fps.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
          if (viewingFPSValue.length && (viewingFPSValue.text != this.status.fps)) {
            viewingFPSValue.text(this.status.fps);
          }
          this.status.analysisfps = this.status.analysisfps.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
          if (analysisFPSValue.length && (analysisFPSValue.text != this.status.analysisfps)) {
            analysisFPSValue.text(this.status.analysisfps);
          }
          this.status.capturefps = this.status.capturefps.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
          if (captureFPSValue.length && (captureFPSValue.text != this.status.capturefps)) {
            captureFPSValue.text(this.status.capturefps);
          }

          const levelValue = $j('#levelValue');
          if (levelValue.length) {
            levelValue.text(this.status.level);
            var newClass = 'ok';
            if (this.status.level > 95) {
              newClass = 'alarm';
            } else if (this.status.level > 80) {
              newClass = 'alert';
            }
            levelValue.removeClass();
            levelValue.addClass(newClass);
          }

          const delayString = secsToTime(this.status.delay);

          if (this.status.paused == true) {
            $j('#modeValue'+this.id).text('Paused');
            $j('#rate'+this.id).addClass('hidden');
            $j('#delayValue'+this.id).text(delayString);
            $j('#delay'+this.id).removeClass('hidden');
            $j('#level'+this.id).removeClass('hidden');
            this.onpause();
          } else if (this.status.delayed == true) {
            $j('#modeValue'+this.id).text('Replay');
            $j('#rateValue'+this.id).text(this.status.rate);
            $j('#rate'+this.id).removeClass('hidden');
            $j('#delayValue'+this.id).text(delayString);
            $j('#delay'+this.id).removeClass('hidden');
            $j('#level'+this.id).removeClass('hidden');
            if (this.status.rate == 1) {
              if (this.onplay) this.onplay();
            } else if (this.status.rate > 0) {
              if (this.status.rate < 1) {
                streamCmdSlowFwd(false);
              } else {
                streamCmdFastFwd(false);
              }
            } else {
              if (this.status.rate > -1) {
                streamCmdSlowRev(false);
              } else {
                streamCmdFastRev(false);
              }
            } // rate
          } else {
            $j('#modeValue'+this.id).text('Live');
            $j('#rate'+this.id).addClass('hidden');
            $j('#delay'+this.id).addClass('hidden');
            $j('#level'+this.id).addClass('hidden');
            if (this.onplay) this.onplay();
          } // end if paused or delayed

          $j('#zoomValue'+this.id).text(this.status.zoom);
          if (this.status.zoom == '1.0') {
            $j('#zoom'+this.id).addClass('hidden');
          }
          if ('zoomOutBtn' in this.buttons) {
            if (this.status.zoom == '1.0') {
              setButtonState('zoomOutBtn', 'unavail');
            } else {
              setButtonState('zoomOutBtn', 'inactive');
            }
          }
        } // end if compact montage

        this.setAlarmState(this.status.state);

        if (canEdit.Monitors) {
          if (streamStatus.enabled) {
            if ('enableAlarmButton' in this.buttons) {
              if (!this.buttons.enableAlarmButton.hasClass('disabled')) {
                this.buttons.enableAlarmButton.addClass('disabled');
                this.buttons.enableAlarmButton.prop('title', disableAlarmsStr);
              }
            }
            if ('forceAlarmButton' in this.buttons) {
              if (streamStatus.forced) {
                if (! this.buttons.forceAlarmButton.hasClass('disabled')) {
                  this.buttons.forceAlarmButton.addClass('disabled');
                  this.buttons.forceAlarmButton.prop('title', cancelForcedAlarmStr);
                }
              } else {
                if (this.buttons.forceAlarmButton.hasClass('disabled')) {
                  this.buttons.forceAlarmButton.removeClass('disabled');
                  this.buttons.forceAlarmButton.prop('title', forceAlarmStr);
                }
              }
              this.buttons.forceAlarmButton.prop('disabled', false);
            }
          } else {
            if ('enableAlarmButton' in this.buttons) {
              this.buttons.enableAlarmButton.removeClass('disabled');
              this.buttons.enableAlarmButton.prop('title', enableAlarmsStr);
            }
            if ('forceAlarmButton' in this.buttons) {
              this.buttons.forceAlarmButton.prop('disabled', true);
            }
          }
          if ('enableAlarmButton' in this.buttons) {
            this.buttons.enableAlarmButton.prop('disabled', false);
          }
        } // end if canEdit.Monitors

        if (this.status.auth) {
          if (this.status.auth != this.auth_hash) {
            // Don't reload the stream because it causes annoying flickering. Wait until the stream breaks.
            console.log("Changed auth from " + this.auth_hash + " to " + this.status.auth);
            this.streamCmdParms.auth = auth_hash = this.auth_hash = this.status.auth;
          }
        } // end if have a new auth hash
      } // end if has state
    } else {
      console.error(respObj.message);
      // Try to reload the image stream.
      if (stream.src) {
        console.log('Reloading stream: ' + stream.src);
        src = stream.src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
        src = src.replace(/auth=\w+/i, 'auth='+this.auth_hash);
        // Maybe updated auth
        if (src != stream.src) {
          stream.src = '';
          stream.src = src;
        } else {
          console.log("Failed to update rand on stream src");
        }
      }
    } // end if Ok or not
  };

  this.statusQuery = function() {
    this.streamCommand(CMD_QUERY);
    this.statusCmdTimer = setTimeout(this.statusQuery.bind(this), statusRefreshTimeout);
  };

  this.streamCmdQuery = function(resent) {
    if (this.type != 'WebSite') {
      this.streamCmdParms.command = CMD_QUERY;
      this.streamCmdReq(this.streamCmdParms);
    }
    this.streamCmdTimer = setTimeout(this.streamCmdQuery.bind(this), statusRefreshTimeout);
  };

  this.streamCommand = function(command) {
    if (typeof(command) == 'object') {
      for (const key in command) this.streamCmdParms[key] = command[key];
    } else {
      this.streamCmdParms.command = command;
    }
    this.streamCmdReq(this.streamCmdParms);
  };

  this.alarmCommand = function(command) {
    if (this.ajaxQueue) {
      console.log("Aborting in progress ajax for alarm");
      // Doing this for responsiveness, but we could be aborting something important. Need smarter logic
      this.ajaxQueue.abort();
    }
    const alarmCmdParms = Object.assign({}, this.streamCmdParms);
    alarmCmdParms.request = 'alarm';
    alarmCmdParms.command = command;
    alarmCmdParms.id = this.id;

    this.ajaxQueue = jQuery.ajaxQueue({
      url: this.url,
      xhrFields: { withCredentials: true },
      data: alarmCmdParms,
      dataType: "json"
    })
        .done(this.getStreamCmdResponse.bind(this))
        .fail(this.onFailure.bind(this));
  };

  if (this.type != 'WebSite') {
    $j.ajaxSetup({timeout: AJAX_TIMEOUT});
    if (auth_hash) {
      this.streamCmdParms.auth = auth_hash;
    } else if (auth_relay) {
      this.streamCmdParms.auth_relay = auth_relay;
    }

    this.streamCmdReq = function(streamCmdParms) {
      this.ajaxQueue = jQuery.ajaxQueue({
        url: this.url,
        xhrFields: { withCredentials: true },
        data: streamCmdParms,
        dataType: "json"
      })
          .done(this.getStreamCmdResponse.bind(this))
          .fail(this.onFailure.bind(this));
    };
  }
  this.analyse_frames = true;
  this.show_analyse_frames = function(toggle) {
    this.analyse_frames = toggle;
    this.streamCmdParms.command = this.analyse_frames ? CMD_ANALYZE_ON : CMD_ANALYZE_OFF;
    this.streamCmdReq(this.streamCmdParms);
  };
} // end function MonitorStream
