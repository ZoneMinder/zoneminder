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
    this.buttons.name = element;
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

  this.setScale = function(newscale) {
    const img = this.getElement();
    if (!img) return;

    this.scale = newscale;

    const oldSrc = img.getAttribute('src');
    if (!oldSrc) {
      console.log('No src on img?!');
      console.log(img);
      return;
    }
    let newSrc = '';

    img.setAttribute('src', '');
    console.log("Scaling to: " + newscale);

    if (newscale == '0' || newscale == 'auto') {
      const bottomElement = document.getElementById('monitorState'+this.id);
      var newSize = scaleToFit(this.width, this.height, $j(img), $j(bottomElement));

      //console.log(newSize);
      newWidth = newSize.width;
      newHeight = newSize.height;
      autoScale = parseInt(newSize.autoScale);
      // This is so that we don't waste bandwidth and let the browser do all the scaling.
      if (autoScale > 100) autoScale = 100;
      if (autoScale) {
        newSrc = oldSrc.replace(/scale=\d+/i, 'scale='+autoScale);
      }
    } else {
      newWidth = this.width * newscale / SCALE_BASE;
      newHeight = this.height * newscale / SCALE_BASE;
      img.width(newWidth);
      img.height(newHeight);
      if (newscale > 100) newscale = 100;
      newSrc = oldSrc.replace(/scale=\d+/i, 'scale='+newscale);
    }
    img.setAttribute('src', newSrc);
  };

  this.start = function(delay) {
    if (this.janusEnabled) {
      var id = parseInt(this.id);
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
      attachVideo(id);
      return;
    }

    const stream = this.getElement();
    if (!stream) return;
    if (!stream.src) {
      // Website Monitors won't have an img tag, neither will video
      console.log('No src for #liveStream'+this.id);
      console.log(stream);
      return;
    }
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
    setTimeout(this.statusQuery.bind(this), delay);
  };

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
    } else {
      console.log("No statevalue");
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
    setTimeout(this.streamCmdQuery.bind(this), 1000*statusRefreshTimeout);
    logAjaxFail(jqxhr, textStatus, error);
  };

  this.getStreamCmdResponse = function(respObj, respText) {
    var stream = this.getElement();
    if (!stream) {
      return;
    }

    //watchdogOk('stream');
    if (this.streamCmdTimer) {
      this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
    }

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
              this.buttons.enableAlarmButton.addClass('disabled');
              this.buttons.enableAlarmButton.prop('title', disableAlarmsStr);
            }
            if ('forceAlarmButton' in this.buttons) {
              if (streamStatus.forced) {
                this.buttons.forceAlarmButton.addClass('disabled');
                this.buttons.forceAlarmButton.prop('title', cancelForcedAlarmStr);
              } else {
                this.buttons.forceAlarmButton.removeClass('disabled');
                this.buttons.forceAlarmButton.prop('title', forceAlarmStr);
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
            // Try to reload the image stream.
            if (stream && stream.src) {
              const oldsrc = stream.src;
              stream.src = '';
              stream.src = oldsrc.replace(/auth=\w+/i, 'auth='+this.status.auth);
            }
            console.log("Changed auth from " + this.auth_hash + " to " + this.status.auth);
            this.auth_hash = this.status.auth;
          }
        } // end if have a new auth hash
      } // end if has state
    } else {
      console.error(respObj.message);
      // Try to reload the image stream.
      if (stream) {
        if (stream.src) {
          console.log('Reloading stream: ' + stream.src);
          src = stream.src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
          // Maybe navbar updated auth FIXME
          if (src != stream.src) {
            stream.src = src;
          } else {
            console.log("Failed to update rand on stream src");
          }
        }
      } else {
        console.log('No stream to reload?');
      }
    } // end if Ok or not
  };

  this.statusQuery = function() {
    this.streamCmdQuery(CMD_QUERY);
    setTimeout(this.statusQuery.bind(this), statusRefreshTimeout);
  };

  this.streamCmdQuery = function(resent) {
    //console.log("Starting CmdQuery for " + this.connKey );
    if ( this.type != 'WebSite' ) {
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
    } else if ( auth_relay ) {
      this.streamCmdParms.auth_relay = '';
    }

    this.streamCmdReq = function(streamCmdParms) {
      if (this.ajaxQueue) {
        this.ajaxQueue.abort();
      }
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
        if ((navigator.userAgent.toLowerCase().indexOf('firefox') > -1) && (jsep["sdp"].includes("420029"))) { //because firefox devs are stubborn
          jsep["sdp"] = jsep["sdp"].replace("420029", "42e01f");
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
      Janus.debug(" ::: Got a remote track :::");
      Janus.debug(ourstream);
      Janus.attachMediaStream(document.getElementById("liveStream" + id), ourstream);
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
