var janus = null;
const streaming = [];

function MonitorStream(monitorData) {
  this.id = monitorData.id;
  this.connKey = monitorData.connKey;
  this.auth_relay = auth_relay;
  this.auth_hash = auth_hash;
  this.url = monitorData.url;
  this.url_to_zms = monitorData.url_to_zms;
  this.width = monitorData.width;
  this.height = monitorData.height;
  this.janusEnabled = monitorData.janusEnabled;
  this.janusPin = monitorData.janus_pin;
  this.server_id = monitorData.server_id;
  this.scale = 100;
  this.status = {capturefps: 0, analysisfps: 0}; // json object with alarmstatus, fps etc
  this.lastAlarmState = STATE_IDLE;
  this.statusCmdTimer = null; // timer for requests using ajax to get monitor status
  this.statusCmdParms = {
    view: 'request',
    request: 'status',
    connkey: this.connKey
  };
  this.streamCmdTimer = null; // timer for requests to zms for status
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
      console.log('Image stream has loaded! starting streamCmd for '+this.connKey+' in '+statusRefreshTimeout + 'ms');
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
      stream.src = this.url_to_zms+"&mode=single&scale="+this.scale+"&connkey="+this.connKey+this.auth_relay;
    }
  };

  /* scale should be '0' for auto, or an integer value
   * width should be auto, 100%, integer +px
   * height should be auto, 100%, integer +px
   * */
  this.setScale = function(newscale, width, height) {
    const img = this.getElement();
    if (!img) {
      console.log('No img in setScale');
      return;
    }

    // Scale the frame
    monitor_frame = $j('#monitor'+this.id);
    if (!monitor_frame) {
      console.log('Error finding frame');
      return;
    }

    if (((newscale == '0') || (newscale == 0) || (newscale=='auto')) && (width=='auto' || !width)) {
      if (!this.bottomElement) {
        newscale = Math.floor(100*monitor_frame.width() / this.width);
        // We don't want to change the existing css, cuz it might be 59% or 123px or auto;
        width = monitor_frame.css('width');
        height = Math.round(parseInt(this.height) * newscale / 100)+'px';
      } else {
        const newSize = scaleToFit(this.width, this.height, $j(img), $j(this.bottomElement));
        width = newSize.width+'px';
        height = newSize.height+'px';
        newscale = parseInt(newSize.autoScale);
        if (newscale < 25) newscale = 25; // Arbitrary.  4k shown on 1080p screen looks terrible
      }
    } else if (parseInt(width) || parseInt(height)) {
      if (width) {
        if (width.search('px') != -1) {
          newscale = parseInt(100*parseInt(width)/this.width);
        } else { // %
          // Set it, then get the calculated width
          monitor_frame.css('width', width);
          newscale = parseInt(100*parseInt(monitor_frame.width())/this.width);
        }
      } else if (height) {
        newscale = parseInt(100*parseInt(height)/this.height);
        width = parseInt(this.width * newscale / 100)+'px';
      }
    } else {
      // a numeric scale, must take actual monitor dimensions and calculate
      width = Math.round(parseInt(this.width) * newscale / 100)+'px';
      height = Math.round(parseInt(this.height) * newscale / 100)+'px';
    }
    if (width && (width != '0px') && (img.style.width.search('%') == -1)) {
      monitor_frame.css('width', parseInt(width));
    }
    if (height && height != '0px') img.style.height = height;

    this.setStreamScale(newscale);
  }; // setScale

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
    if (newscale > 100) newscale = 100; // we never request a larger image, as it just wastes bandwidth
    if (newscale < 25) newscale = 25; // Arbitrary, lower values look bad
    if (newscale <= 0) newscale = 100;
    this.scale = newscale;
    if (this.connKey) {
      /* Can just tell it to scale, in fact will happen automatically on next query */
    } else {
      if (img.nodeName == 'IMG') {
        const oldSrc = img.src;
        if (!oldSrc) {
          console.log('No src on img?!', img);
          return;
        }
        const newSrc = oldSrc.replace(/scale=\d+/i, 'scale='+newscale);
        if (newSrc != oldSrc) {
          this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
          // We know that only the first zms will get the command because the
          // second can't open the commandQueue until the first exits
          // This is necessary because safari will never close the first image
          if (-1 != img.src.search('connkey') && -1 != img.src.search('mode=single')) {
            this.streamCommand(CMD_QUIT);
          }
          console.log("Changing src from " + img.src + " to " + newSrc + 'refresh timeout:' + statusRefreshTimeout);
          img.src = '';
          img.src = newSrc;
          this.streamCmdTimer = setTimeout(this.streamCmdQuery.bind(this), statusRefreshTimeout);
        }
      }
    }
  }; // setStreamScale

  this.start = function(delay) {
    if (this.janusEnabled) {
      let server;
      if (ZM_JANUS_PATH) {
        server = ZM_JANUS_PATH;
      } else if (this.server_id && Servers[this.server_id]) {
        server = Servers[this.server_id].urlToJanus();
      } else if (window.location.protocol=='https:') {
        // Assume reverse proxy setup for now
        server = "https://" + window.location.hostname + "/janus";
      } else {
        server = "http://" + window.location.hostname + "/janus";
      }

      if (janus == null) {
        Janus.init({debug: "all", callback: function() {
          janus = new Janus({server: server}); //new Janus
        }});
      }
      attachVideo(parseInt(this.id), this.janusPin);
      this.statusCmdTimer = setTimeout(this.statusCmdQuery.bind(this), delay);
      return;
    }

    // zms stream
    const stream = this.getElement();
    if (!stream) return;
    if (!stream.src) {
      // Website Monitors won't have an img tag, neither will video
      console.log('No src for #liveStream'+this.id);
      console.log(stream);
      return;
    }
    this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
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
    if (janus) {
      if (streaming[this.id]) {
        streaming[this.id].detach();
      }
    }
    const stream = this.getElement();
    if (!stream) return;
    stream.onerror = null;
    stream.onload = null;
    this.stop();

    if (this.ajaxQueue) {
      console.log("Aborting in progress ajax for kill");
      // Doing this for responsiveness, but we could be aborting something important. Need smarter logic
      this.ajaxQueue.abort();
    }
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
    let stateClass = '';
    if (alarmState == STATE_ALARM) {
      stateClass = 'alarm';
    } else if (alarmState == STATE_ALERT) {
      stateClass = 'alert';
    }

    const stateValue = $j('#stateValue'+this.id);
    if (stateValue.length) {
      if (stateValue.text() != stateStrings[alarmState]) {
        stateValue.text(stateStrings[alarmState]);
        this.setStateClass(stateValue, stateClass);
      }
    }
    const monitorFrame = $j('#monitor'+this.id);
    if (monitorFrame.length) this.setStateClass(monitorFrame, stateClass);

    const isAlarmed = ( alarmState == STATE_ALARM || alarmState == STATE_ALERT );
    const wasAlarmed = ( this.lastAlarmState == STATE_ALARM || this.lastAlarmState == STATE_ALERT );

    const newAlarm = ( isAlarmed && !wasAlarmed );
    const oldAlarm = ( !isAlarmed && wasAlarmed );

    if (newAlarm) {
      if (ZM_WEB_SOUND_ON_ALARM !== '0') {
        console.log('Attempting to play alarm sound');
        if (ZM_DIR_SOUNDS != '' && ZM_WEB_ALARM_SOUND != '') {
          const sound = new Audio(ZM_DIR_SOUNDS+'/'+ZM_WEB_ALARM_SOUND);
          sound.play();
        } else {
          console.log("You must specify ZM_DIR_SOUNDS and ZM_WEB_ALARM_SOUND as well");
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

    if (error == 'abort') {
      console.log('have abort, will trust someone else to start us back up');
    } else if (error == 'Unauthorized') {
      window.location.reload();
    } else {
      console.log("Queuing up a new query after a pause");
      this.streamCmdTimer = setTimeout(this.streamCmdQuery.bind(this), 10*statusRefreshTimeout);
      logAjaxFail(jqxhr, textStatus, error);
    }
  };

  this.getStreamCmdResponse = function(respObj, respText) {
    const stream = this.getElement();
    if (!stream) return;

    //watchdogOk('stream');
    //this.streamCmdTimer = clearTimeout(this.streamCmdTimer);

    if (respObj.result == 'Ok') {
      if (respObj.status) {
        const streamStatus = this.status = respObj.status;

        if (this.type != 'WebSite') {
          const viewingFPSValue = $j('#viewingFPSValue'+this.id);
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
          if ((this.status.scale !== undefined) && (this.status.scale !== undefined) && (this.status.scale != this.scale)) {
            if (this.status.scale != 0) {
              console.log("Stream not scaled, re-applying", this.scale, this.status.scale);
              this.streamCommand({command: CMD_SCALE, scale: this.scale});
            }
          }

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

  /* getStatusCmd is used when not streaming, since there is no persistent zms */
  this.getStatusCmdResponse=function(respObj, respText) {
    //watchdogOk('status');
    this.statusCmdTimer = clearTimeout(this.statusCmdTimer);


    if (respObj.result == 'Ok') {
      const monitorStatus = respObj.monitor.Status;
      const captureFPSValue = $j('#captureFPSValue'+this.id);
      const analysisFPSValue = $j('#analysisFPSValue'+this.id);

      if (respObj.monitor.FrameRate) {
        const fpses = respObj.monitor.FrameRate.split(",");
        fpses.forEach(function(fps) {
          const name_values = fps.split(':');
          const name = name_values[0].trim();
          const value = name_values[1].trim().toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});

          if (name == 'analysis') {
            this.status.analysisfps = value;
            if (analysisFPSValue.length && (analysisFPSValue.text() != value)) {
              analysisFPSValue.text(value);
            }
          } else if (name == 'capture') {
            if (captureFPSValue.length && (captureFPSValue.text() != value)) {
              captureFPSValue.text(value);
            }
          } else {
            console.log("Unknown fps name " + name);
          }
        });
      }

      if (canEdit.Monitors) {
        if (monitorStatus.enabled) {
          if ('enableAlarmButton' in this.buttons) {
            if (!this.buttons.enableAlarmButton.hasClass('disabled')) {
              this.buttons.enableAlarmButton.addClass('disabled');
              this.buttons.enableAlarmButton.prop('title', disableAlarmsStr);
            }
          }
          if ('forceAlarmButton' in this.buttons) {
            if (monitorStatus.forced) {
              if (!this.buttons.forceAlarmButton.hasClass('disabled')) {
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

      this.setAlarmState(monitorStatus);
    } else {
      checkStreamForErrors('getStatusCmdResponse', respObj);
    }

    this.statusCmdTimer = setTimeout(this.statusCmdQuery.bind(this), statusRefreshTimeout);
  };

  this.statusCmdQuery=function() {
    $j.getJSON(this.url + '?view=request&request=status&entity=monitor&element[]=Status&element[]=FrameRate&id='+this.id+'&'+this.auth_relay)
        .done(this.getStatusCmdResponse.bind(this))
        .fail(logAjaxFail);

    this.statusCmdTimer = null;
  };

  this.statusQuery = function() {
    this.streamCommand(CMD_QUERY);
    this.statusCmdTimer = setTimeout(this.statusQuery.bind(this), statusRefreshTimeout);
  };

  this.streamCmdQuery = function(resent) {
    if (this.type != 'WebSite') {
      // Websites don't have streaming
      // Can't use streamCommand because it aborts

      this.streamCmdParms.command = CMD_QUERY;
      this.streamCmdReq(this.streamCmdParms);
    }
    // Queue up another query
    this.streamCmdTimer = setTimeout(this.streamCmdQuery.bind(this), statusRefreshTimeout);
  };

  this.streamCommand = function(command) {
    const params = Object.assign({}, this.streamCmdParms);
    if (typeof(command) == 'object') {
      for (const key in command) params[key] = command[key];
    } else {
      params.command = command;
    }
    /*
    if (this.ajaxQueue) {
      this.ajaxQueue.abort();
    }
    */
    this.streamCmdReq(params);
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
      url: this.url + (auth_relay?'?'+auth_relay:''),
      xhrFields: {withCredentials: true},
      data: alarmCmdParms,
      dataType: "json"
    })
        .done(this.getStreamCmdResponse.bind(this))
        .fail(this.onFailure.bind(this));
  };

  if (this.type != 'WebSite') {
    $j.ajaxSetup({timeout: AJAX_TIMEOUT});

    this.streamCmdReq = function(streamCmdParms) {
      this.ajaxQueue = jQuery.ajaxQueue({
        url: this.url + (auth_relay?'?'+auth_relay:''),
        xhrFields: {withCredentials: true},
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

  this.setMaxFPS = function(maxfps) {
    if (1) {
      this.streamCommand({command: CMD_MAXFPS, maxfps: currentSpeed});
    } else {
      var streamImage = this.getElement();
      const oldsrc = streamImage.attr('src');
      streamImage.attr('src', ''); // stop streaming
      if (maxfps == '0') {
        // Unlimited
        streamImage.attr('src', oldsrc.replace(/maxfps=\d+/i, 'maxfps=0.00100'));
      } else {
        streamImage.attr('src', oldsrc.replace(/maxfps=\d+/i, 'maxfps='+newvalue));
      }
    }
  }; // end setMaxFPS
} // end function MonitorStream

async function attachVideo(id, pin) {
  await waitUntil(() => janus.isConnected() );
  janus.attach({
    plugin: "janus.plugin.streaming",
    opaqueId: "streamingtest-"+Janus.randomString(12),
    success: function(pluginHandle) {
      streaming[id] = pluginHandle;
      const body = {"request": "watch", "id": id, "pin": pin};
      streaming[id].send({"message": body});
    },
    error: function(error) {
      Janus.error("  -- Error attaching plugin... ", error);
    },
    onmessage: function(msg, jsep) {
      Janus.debug(" ::: Got a message :::");
      Janus.debug(msg);
      var result = msg["result"];
      if (result !== null && result !== undefined) {
        if (result["status"] !== undefined && result["status"] !== null) {
          var status = result["status"];
          Janus.debug(status);
        }
      } else if (msg["error"] !== undefined && msg["error"] !== null) {
        return;
      }
      if (jsep !== undefined && jsep !== null) {
        Janus.debug("Handling SDP as well...");
        Janus.debug(jsep);
        if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
          if (jsep["sdp"].includes("420029")) {
            jsep["sdp"] = jsep["sdp"].replace("420029", "42e01f");
          } else if (jsep["sdp"].includes("4d002a")) {
            jsep["sdp"] = jsep["sdp"].replace("4d002a", "4de02a");
          }
        }
        // Offer from the plugin, let's answer
        streaming[id].createAnswer({
          jsep: jsep,
          // We want recvonly audio/video and, if negotiated, datachannels
          media: {audioSend: false, videoSend: false, data: true},
          success: function(jsep) {
            Janus.debug("Got SDP!");
            Janus.debug(jsep);
            var body = {"request": "start"};
            streaming[id].send({"message": body, "jsep": jsep});
          },
          error: function(error) {
            Janus.error("WebRTC error:", error);
          }
        });
      }
    }, //onmessage function
    onremotestream: function(ourstream) {
      Janus.debug(" ::: Got a remote stream :::");
      Janus.debug(ourstream);
      Janus.attachMediaStream(document.getElementById("liveStream" + id), ourstream);
    },
    onremotetrack: function(track, mid, on) {
      Janus.debug(" ::: Got a remote track :::");
      Janus.debug(track);
      if (track.kind ==="audio") {
        stream = new MediaStream();
        stream.addTrack(track.clone());
        if (document.getElementById("liveAudio" + id) == null) {
          audioElement = document.createElement('audio');
          audioElement.setAttribute("id", "liveAudio" + id);
          audioElement.controls = true;
          document.getElementById("imageFeed" + id).append(audioElement);
        }
        Janus.attachMediaStream(document.getElementById("liveAudio" + id), stream);
      } else {
        stream = new MediaStream();
        stream.addTrack(track.clone());
        Janus.attachMediaStream(document.getElementById("liveStream" + id), stream);
      }
    }
  }); // janus.attach
} //function attachVideo

const waitUntil = (condition) => {
  return new Promise((resolve) => {
    const interval = setInterval(() => {
      if (!condition()) {
        return;
      }
      clearInterval(interval);
      resolve();
    }, 100);
  });
};
