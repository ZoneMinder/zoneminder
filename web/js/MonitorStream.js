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
  this.alarmState = STATE_IDLE;
  this.lastAlarmState = STATE_IDLE;
  this.streamCmdParms = {
    view: 'request',
    request: 'stream',
    connkey: this.connKey
  };
  this.type = monitorData.type;
  this.refresh = monitorData.refresh;
  this.element = null;
  this.getElement = function() {
    if (this.element) return this.element;
    this.element = document.getElementById('liveStream'+this.id);
    if (!this.element) {
      console.error("No img for #liveStream"+this.id);
    }
    return this.element;
  };

  /* if the img element didn't have a src, this would fill it in, causing it to show. */
  this.show = function() {
    const stream = this.getElement();
    if (!stream.src) {
      stream.src = this.url_to_zms+"&mode=single&scale=100&connkey="+this.connKey;
    }
  };

  this.setScale = function(newscale) {
    const img = this.getElement();
    if (!img) return;

    this.scale = newscale;

    const oldSrc = img.getAttribute('src');
    if (!oldSrc) {
      console.log("No src on img?!");
      console.log(img);
      return;
    }
    let newSrc = '';

    img.setAttribute('src', '');
    console.log("Scaling to: " + newscale);

    if (newscale == '0' || newscale == 'auto') {
      let bottomElement = document.getElementById('replayStatus');
      if (!bottomElement) {
        bottomElement = document.getElementById('monitorState');
      }
      var newSize = scaleToFit(this.width, this.height, $j(img), $j(bottomElement));

      console.log(newSize);
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
    // Step 1 make sure we are streaming instead of a static image
    const stream = this.getElement();
    if (!stream) return;

    if (this.janusEnabled) {
      var id = parseInt(this.id);
      var server;
      if (window.location.protocol=='https:') {
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
    if (!stream.src) {
      // Website Monitors won't have an img tag
      console.log('No src for #liveStream'+this.id);
      console.log(stream);
      return;
    }
    src = stream.src.replace(/mode=single/i, 'mode=jpeg');
    if ( -1 == src.search('connkey') ) {
      src += '&connkey='+this.connKey;
    }
    if ( stream.src != src ) {
      console.log("Setting to streaming: " + src);
      stream.src = '';
      stream.src = src;
    }
    setTimeout(this.streamCmdQuery.bind(this), delay);
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
    this.streamCmdParms.command = CMD_STOP;
    this.streamCmdReq(this.streamCmdParms);
  };
  this.pause = function() {
    this.streamCmdParms.command = CMD_PAUSE;
    this.streamCmdReq(this.streamCmdParms);
  };
  this.play = function() {
    this.streamCmdParms.command = CMD_PLAY;
    this.streamCmdReq(this.streamCmdParms);
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

  this.setStateClass = function(jobj, stateClass) {
    if ( !jobj ) {
      return;
    }
    if ( !jobj.hasClass( stateClass ) ) {
      if ( stateClass != 'alarm' ) jobj.removeClass('alarm');
      if ( stateClass != 'alert' ) jobj.removeClass('alert');
      if ( stateClass != 'idle' ) jobj.removeClass('idle');

      jobj.addClass(stateClass);
    }
  };

  this.onFailure = function(jqxhr, textStatus, error) {
    setTimeout(this.streamCmdQuery.bind(this), 1000*statusRefreshTimeout);
    logAjaxFail(jqxhr, textStatus, error);
  };

  this.getStreamCmdResponse = function(respObj, respText) {
    var stream = $j('#liveStream'+this.id)[0];

    if (!stream) {
      console.log('No live stream');
      return;
    }

    //watchdogOk('stream');
    if (streamCmdTimer) {
      streamCmdTimer = clearTimeout(streamCmdTimer);
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
          const viewingFPSValue = $j('#vewingFPSValue'+this.id);
          const captureFPSValue = $j('#captureFPSValue'+this.id);
          const analysisFPSValue = $j('#analysisFPSValue'+this.id);

          const stateValue = $j('#stateValue'+this.id);
          const monitorState = $j('#monitorState'+this.id);

          if (viewingFPSValue.length && (viewingFPSValue.text != this.status.fps)) {
            viewingFPSValue.text(this.status.fps);
          }
          if (analysisFPSValue.length && (analysisFPSValue.text != this.status.analysisfps)) {
            analysisFPSValue.text(this.status.analysisfps);
          }
          if (captureFPSValue.length && (captureFPSValue.text != this.status.capturefps)) {
            captureFPSValue.text(this.status.capturefps);
          }

          if (stateValue.length) stateValue.text(stateStrings[this.alarmState]);
          if (monitorState.length) this.setStateClass(monitorState, stateClass);
        }

        this.setStateClass($j('#monitor'+this.id), stateClass);

        /*Stream could be an applet so can't use moo tools*/
        //stream.parentNode().className = stateClass;

        var isAlarmed = ( this.alarmState == STATE_ALARM || this.alarmState == STATE_ALERT );
        var wasAlarmed = ( this.lastAlarmState == STATE_ALARM || this.lastAlarmState == STATE_ALERT );

        var newAlarm = ( isAlarmed && !wasAlarmed );
        var oldAlarm = ( !isAlarmed && wasAlarmed );

        if (newAlarm) {
          if (false && SOUND_ON_ALARM) {
            // Enable the alarm sound
            $j('#alarmSound').removeClass('hidden');
          }
          if ((typeof POPUP_ON_ALARM !== 'undefined') && POPUP_ON_ALARM) {
            windowToFront();
          }
        }
        if (false && SOUND_ON_ALARM) {
          if ( oldAlarm ) {
            // Disable alarm sound
            $j('#alarmSound').addClass('hidden');
          }
        }
        if (this.status.auth) {
          if (this.status.auth != this.auth_hash) {
            // Try to reload the image stream.
            if (stream) {
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

    this.lastAlarmState = this.alarmState;
    setTimeout(this.streamCmdQuery.bind(this), statusRefreshTimeout);
  };

  this.streamCmdQuery = function(resent) {
    //console.log("Starting CmdQuery for " + this.connKey );
    if ( this.type != 'WebSite' ) {
      this.streamCmdParms.command = CMD_QUERY;
      this.streamCmdReq(this.streamCmdParms);
    }
  };

  if ( this.type != 'WebSite' ) {
    this.streamCmdReq = function(streamCmdParms) {
      if ( auth_hash ) {
        this.streamCmdParms.auth = auth_hash;
      } else if ( auth_relay ) {
        this.streamCmdParms.auth_relay = '';
      }
      $j.ajaxSetup({timeout: AJAX_TIMEOUT});
      $j.getJSON(this.url, streamCmdParms)
          .done(this.getStreamCmdResponse.bind(this))
          .fail(this.onFailure.bind(this));
    };
  }
  this.analyse_frames = true;
  this.show_analyse_frames = function(toggle) {
    this.analyse_frames = toggle;
    this.streamCmdParms.command = this.analyse_frames?CMD_ANALYZE_ON:CMD_ANALYZE_OFF;
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
