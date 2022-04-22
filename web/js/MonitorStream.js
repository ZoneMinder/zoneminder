var janus = null;
var streaming = [];

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
    } else {
      console.log("Setting bottomElement to ");
      console.log(e);
    }
    this.bottomElement = e;
  };

  this.img_onerror = function() {
    console.log('Failed loading image stream');
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
    stream_frame = $j('#monitor'+this.id);

    if ((newscale == '0') || (width=='auto' && height=='auto' && newscale=='')) {
      if (!this.bottomElement) {
        console.log("No bottom element set. Setting to monitorStatus");
        this.bottomElement = document.getElementById('monitorStatus'+this.id);
        if (!this.bottomElement) {
          console.log('bottomElement not found');
        }
      }
      var newSize = scaleToFit(this.width, this.height, $j(img), $j(this.bottomElement));
      width = newSize.width;
      height = newSize.height;
      newscale = parseInt(newSize.autoScale);
      console.log("auto scale " + newscale);
    } else if (parseInt(width) || parseInt(height)) {
      if (width) {
        newscale = parseInt(100*parseInt(width)/this.width);
        if (!parseInt(height)) height = parseInt(this.height * newscale / 100);
      } else if (height) {
        newscale = parseInt(100*parseInt(height)/this.height);
        width = parseInt(this.width * newscale / 100);
      }
      console.log("New scale from size: " + newscale);
    } else {
      // a numeric scale, must take actual monitor dimensions and calculate
      width = Math.round(parseInt(this.width) * newscale / 100);
      height = Math.round(parseInt(this.height) * newscale / 100);
      console.log("Setting to " + width + "x" + height + " from " + newscale);
    }

    if (img.nodeName == 'IMG') {
      if (newscale > 100) newscale = 100; // we never request a larger image, as it just wastes bandwidth
      if (newscale < 0) newscale = 100;
      const oldSrc = img.src;
      if (!oldSrc) {
        console.log('No src on img?!');
        console.log(img);
        return;
      }
      const newSrc = oldSrc.replace(/scale=\d+/i, 'scale='+newscale);
      if (newSrc != oldSrc) {
        this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
        this.statusCmdTimer = clearTimeout(this.statusCmdTimer);
        // We know that only the first zms will get the command because the
        // second can't open the commandQueue until the first exits
        // This is necessary because safari will never close the first image
        this.streamCommand(CMD_QUIT);
        this.statusCmdTimer = setTimeout(this.statusQuery.bind(this), statusRefreshTimeout);
        img.src = newSrc;
      }
    }

    monitor_frame.css('width', parseInt(width) ? parseInt(width)+'px' : 'auto');
    // monitor_frame never has fixed height
    //stream_frame.css('width', parseInt(width) ? width : 'auto');
    //stream_frame.css('height', parseInt(height) ? height : 'auto');
  }; // setscale

  this.start = function(delay) {
    if (this.janusEnabled) {
      var server;
      if (ZM_JANUS_PATH) {
        server = ZM_JANUS_PATH;
      } else if (window.location.protocol=='https:') {
        // Assume reverse proxy setup for now
        server = "https://" + window.location.hostname + "/janus";
      } else {
        server = "http://" + window.location.hostname + ":8088/janus";
      }

      if (janus == null) {
        Janus.init({debug: "all", callback: function() {
          janus = new Janus({server: server}); //new Janus
        }});
      }
      attachVideo(parseInt(this.id));
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
    clearTimeout(this.statusCmdTimer);
    // Step 1 make sure we are streaming instead of a static image
    src = stream.src.replace(/mode=single/i, 'mode=jpeg');
    if (-1 == src.search('connkey')) {
      src += '&connkey='+this.connKey;
    }
    if (stream.src != src) {
      console.log("Setting to streaming: " + src);
      stream.src = '';
      stream.src = src;
    }
    this.statusCmdTimer = setTimeout(this.statusQuery.bind(this), delay);
    stream.onerror = this.img_onerror.bind(this);
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
    clearTimeout(this.statusCmdTimer);
    clearTimeout(this.streamCmdTimer);
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
    this.onclick = func;
    const el = this.getFrame();
    if (!el) return;
    el.addEventListener('click', this.onclick, false);
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
    //const monitorState = $j('#monitorState'+this.id);
    //if (monitorState.length) this.setStateClass(monitorState, stateClass);

    const isAlarmed = ( alarmState == STATE_ALARM || alarmState == STATE_ALERT );
    const wasAlarmed = ( this.lastAlarmState == STATE_ALARM || this.lastAlarmState == STATE_ALERT );

    const newAlarm = ( isAlarmed && !wasAlarmed );
    const oldAlarm = ( !isAlarmed && wasAlarmed );

    if (newAlarm) {
      if (SOUND_ON_ALARM) {
        // Enable the alarm sound
        if (!msieVer) {
          $j('#alarmSound').removeClass('hidden');
        } else {
          $j('#MediaPlayer').trigger('play');
        }
      }
      if (POPUP_ON_ALARM) {
        window.focus();
      }
      if (this.onalarm) {
        this.onalarm();
      }
    }
    if (oldAlarm) { // done with an event do a refresh
      if (SOUND_ON_ALARM) {
        // Disable alarm sound
        if (!msieVer) {
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
    this.streamCmdTimer = clearTimeout(this.streamCmdTimer);

    if (respObj.result == 'Ok') {
      if (respObj.status) {
        const streamStatus = this.status = respObj.status;

        if ( (
          (typeof COMPACT_MONTAGE === 'undefined') ||
          !COMPACT_MONTAGE) &&
          (this.type != 'WebSite')
        ) {
          const viewingFPSValue = $j('#viewingFPSValue'+this.id);
          const captureFPSValue = $j('#captureFPSValue'+this.id);
          const analysisFPSValue = $j('#analysisFPSValue'+this.id);


          if (viewingFPSValue.length && (viewingFPSValue.text != this.status.fps)) {
            viewingFPSValue.text(this.status.fps);
          }
          if (analysisFPSValue.length && (analysisFPSValue.text != this.status.analysisfps)) {
            analysisFPSValue.text(this.status.analysisfps);
          }
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
    this.streamCmdQuery(CMD_QUERY);
    this.statusCmdTimer = setTimeout(this.statusQuery.bind(this), statusRefreshTimeout);
  };

  this.streamCmdQuery = function(resent) {
    if (this.type != 'WebSite') {
      this.streamCmdParms.command = CMD_QUERY;
      this.streamCmdReq(this.streamCmdParms);
    }
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
      data: alarmCmdParms, dataType: "json"})
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
      this.ajaxQueue = jQuery.ajaxQueue({url: this.url, data: streamCmdParms, dataType: "json"})
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

async function attachVideo(id) {
  await waitUntil(() => janus.isConnected() );
  janus.attach({
    plugin: "janus.plugin.streaming",
    opaqueId: "streamingtest-"+Janus.randomString(12),
    success: function(pluginHandle) {
      streaming[id] = pluginHandle;
      var body = {"request": "watch", "id": id};
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
