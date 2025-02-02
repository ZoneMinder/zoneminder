"use strict";
var janus = null;
const streaming = [];

function MonitorStream(monitorData) {
  this.id = monitorData.id;
  this.connKey = monitorData.connKey;
  this.url = monitorData.url;
  this.url_to_zms = monitorData.url_to_zms;
  this.width = monitorData.width;
  this.height = monitorData.height;
  this.RTSP2WebEnabled = monitorData.RTSP2WebEnabled;
  this.RTSP2WebType = monitorData.RTSP2WebType;
  this.webrtc = null;
  this.mseStreamingStarted = false;
  this.mseQueue = [];
  this.mseSourceBuffer = null;
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
  this.gridstack = null;
  this.setGridStack = function(gs) {
    this.gridstack = gs;
  };

  this.bottomElement = null;
  this.setBottomElement = function(e) {
    if (!e) {
      console.error("Empty bottomElement");
    }
    this.bottomElement = e;
  };

  this.img_onerror = function() {
    console.log('Image stream has been stopped! stopping streamCmd');
    this.streamCmdTimer = clearInterval(this.streamCmdTimer);
  };
  this.img_onload = function() {
    if (!this.streamCmdTimer) {
      console.log('Image stream has loaded! starting streamCmd for '+this.connKey+' in '+statusRefreshTimeout + 'ms');
      this.streamCmdQuery.bind(this);
      this.streamCmdTimer = setInterval(this.streamCmdQuery.bind(this), statusRefreshTimeout);
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
      stream.src = this.url_to_zms+"&mode=single&scale="+this.scale+"&connkey="+this.connKey+'&'+auth_relay;
    }
  };

  /* scale should be '0' for auto, or an integer value
   * width should be auto, 100%, integer +px
   * height should be auto, 100%, integer +px
   * param.resizeImg be boolean (added only for using GridStack & PanZoom on Montage page)
   * param.scaleImg scaling 1=100% (added only for using PanZoom on Montage & Watch page)
   * param.streamQuality in %, numeric value from -50 to +50)
   * */
  this.setScale = function(newscale, width, height, param = {}) {
    const img = this.getElement();
    const newscaleSelect = newscale;
    if (!img) {
      console.log('No img in setScale');
      return;
    }

    // Scale the frame
    const monitor_frame = $j('#monitor'+this.id);
    if (!monitor_frame) {
      console.log('Error finding frame');
      return;
    }

    if (((newscale == '0') || (newscale == 0) || (newscale=='auto')) && (width=='auto' || !width)) {
      if (!this.bottomElement) {
        if (param.scaleImg) {
          newscale = Math.floor(100*monitor_frame.width() / this.width * param.scaleImg);
        } else {
          newscale = Math.floor(100*monitor_frame.width() / this.width);
        }
        // We don't want to change the existing css, cuz it might be 59% or 123px or auto;
        width = monitor_frame.css('width');
        height = Math.round(parseInt(this.height) * newscale / 100)+'px';
      } else {
        const newSize = scaleToFit(this.width, this.height, $j(img), $j(this.bottomElement), $j('#wrapperMonitor'));
        width = newSize.width+'px';
        height = newSize.height+'px';
        if (param.scaleImg) {
          newscale = parseInt(newSize.autoScale * param.scaleImg);
        } else {
          newscale = parseInt(newSize.autoScale);
        }
        if (newscale < 25) newscale = 25; // Arbitrary.  4k shown on 1080p screen looks terrible
      }
    } else if (parseInt(width) || parseInt(height)) {
      if (width) {
        if (width.search('px') != -1) {
          newscale = parseInt(100*parseInt(width)/this.width);
        } else { // %
          // Set it, then get the calculated width
          if (param.resizeImg) {
            monitor_frame.css('width', width);
          }
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
      if (param.resizeImg) {
        monitor_frame.css('width', parseInt(width));
      }
    }
    if (param.resizeImg) {
      if (img.style.width) img.style.width = '100%';
      if (height && height != '0px') img.style.height = height;
    } else { //This code will not be needed when using GridStack & PanZoom on Montage page. Only required when trying to use "scaleControl"
      if (newscaleSelect != 0) {
        img.style.width = 'auto';
        $j(img).closest('.monitorStream')[0].style.overflow = 'auto';
      } else {
        //const monitor_stream = $j(img).closest('.monitorStream');
        //const realWidth = monitor_stream.attr('data-width');
        //const realHeight = monitor_stream.attr('data-height');
        //const ratio = realWidth / realHeight;
        //const imgWidth = $j(img)[0].offsetWidth + 4; // including border
        img.style.width = '100%';
        $j(img).closest('.monitorStream')[0].style.overflow = 'hidden';
      }
    }
    let streamQuality = 0;
    if (param.streamQuality) {
      streamQuality = param.streamQuality;
      newscale += parseInt(newscale/100*streamQuality);
    }
    this.setStreamScale(newscale, streamQuality);
  }; // setScale

  this.setStreamScale = function(newscale, streamQuality=0) {
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
    if (newscale < 25 && streamQuality > -1) newscale = 25; // Arbitrary, lower values look bad
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
        let newSrc = oldSrc.replace(/scale=\d+/i, 'scale='+newscale);
        newSrc = newSrc.replace(/auth=\w+/i, 'auth='+auth_hash);
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
          this.streamCmdTimer = setInterval(this.streamCmdQuery.bind(this), statusRefreshTimeout);
        }
      }
    }
  }; // setStreamScale

  this.start = function() {
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
      this.statusCmdTimer = setInterval(this.statusCmdQuery.bind(this), statusRefreshTimeout);
      this.started = true;
      return;
    }
    if (this.RTSP2WebEnabled) {
      if (ZM_RTSP2WEB_PATH) {
        const videoEl = document.getElementById("liveStream" + this.id);
        const url = new URL(ZM_RTSP2WEB_PATH);
        const useSSL = (url.protocol == 'https');

        const rtsp2webModUrl = url;
        rtsp2webModUrl.username = '';
        rtsp2webModUrl.password = '';
        //.urlParts.length > 1 ? urlParts[1] : urlParts[0]; // drop the username and password for viewing
        if (this.RTSP2WebType == 'HLS') {
          const hlsUrl = rtsp2webModUrl;
          hlsUrl.pathname = "/stream/" + this.id + "/channel/0/hls/live/index.m3u8";
          /*
          if (useSSL) {
            hlsUrl = "https://" + rtsp2webModUrl + "/stream/" + this.id + "/channel/0/hls/live/index.m3u8";
          } else {
            hlsUrl = "http://" + rtsp2webModUrl + "/stream/" + this.id + "/channel/0/hls/live/index.m3u8";
          }
          */
          if (Hls.isSupported()) {
            const hls = new Hls();
            hls.loadSource(hlsUrl.href);
            hls.attachMedia(videoEl);
          } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            videoEl.src = hlsUrl.href;
          }
        } else if (this.RTSP2WebType == 'MSE') {
          videoEl.addEventListener('pause', () => {
            if (videoEl.currentTime > videoEl.buffered.end(videoEl.buffered.length - 1)) {
              videoEl.currentTime = videoEl.buffered.end(videoEl.buffered.length - 1) - 0.1;
              videoEl.play();
            }
          });
          const mseUrl = rtsp2webModUrl;
          mseUrl.protocol = useSSL ? 'wss' : 'ws';
          mseUrl.pathname = "/stream/" + this.id + "/channel/0/mse?uuid=" + this.id + "&channel=0";
          startMsePlay(this, videoEl, mseUrl.href);
        } else if (this.RTSP2WebType == 'WebRTC') {
          const webrtcUrl = rtsp2webModUrl;
          webrtcUrl.pathname = "/stream/" + this.id + "/channel/0/webrtc";
          startRTSP2WebPlay(videoEl, webrtcUrl.href, this);
        }
        this.statusCmdTimer = setInterval(this.statusCmdQuery.bind(this), statusRefreshTimeout);
        this.started = true;
        return;
      } else {
        console.log("ZM_RTSP2WEB_PATH is empty. Go to Options->System and set ZM_RTSP2WEB_PATH accordingly.");
      }
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
    let src = stream.src.replace(/mode=single/i, 'mode=jpeg');
    src = src.replace(/auth=\w+/i, 'auth='+auth_hash);
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
    this.started = true;
  }; // this.start

  this.stop = function() {
    if ( 0 ) {
      const stream = this.getElement();
      if (!stream) return;
      const src = stream.src.replace(/mode=jpeg/i, 'mode=single');
      if (stream.src != src) {
        stream.src = '';
        stream.src = src;
      }
    }
    this.streamCommand(CMD_STOP);
    this.statusCmdTimer = clearInterval(this.statusCmdTimer);
    this.streamCmdTimer = clearInterval(this.streamCmdTimer);
    this.started = false;
    if (this.webrtc) {
      this.webrtc.close();
      this.webrtc = null;
    }
  };

  this.kill = function() {
    if (janus) {
      if (streaming[this.id]) {
        streaming[this.id].detach();
      }
    }
    const stream = this.getElement();
    if (!stream) {
      console.log("No element found for monitor "+this.id);
      return;
    }
    stream.onerror = null;
    stream.onload = null;
    this.stop();

    // this.stop tells zms to stop streaming, but the process remains. We need to turn the stream into an image.
    if (stream.src) {
      const src = stream.src.replace(/mode=jpeg/i, 'mode=single');
      if (stream.src != src) {
        stream.src = '';
        stream.src = src;
      }
    }

    // Because we stopped the zms process above, any remaining ajaxes will fail.  But aborting them will also cause them to fail, so why bother?
    if (0 && this.ajaxQueue) {
      console.log("Aborting in progress ajax for kill");
      // Doing this for responsiveness, but we could be aborting something important. Need smarter logic
      this.ajaxQueue.abort();
    }
  };

  this.pause = function() {
    if (this.element.src) {
      this.streamCommand(CMD_PAUSE);
    } else {
      this.element.pause();
      this.statusCmdTimer = clearInterval(this.statusCmdTimer);
    }
  };

  this.play = function() {
    if (this.element.src) {
      this.streamCommand(CMD_PLAY);
    } else {
      this.element.play();
      this.statusCmdTimer = setInterval(this.statusCmdQuery.bind(this), statusRefreshTimeout);
    }
  };

  this.eventHandler = function(event) {
    console.log(event);
  };

  this.onclick = function(evt) {
    console.log('onclick');
  };

  this.onmove = function(evt) {
    console.log('onmove');
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

  this.setup_onmove = function(func) {
    if (func) {
      this.onmove = func;
    }
    if (this.onmove) {
      const el = this.getFrame();
      if (!el) return;
      el.addEventListener('mousemove', this.onmove, false);
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

          this.status.fps = this.status.fps.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 2});
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
            let newClass = 'ok';
            if (this.status.level > 95) {
              newClass = 'alarm';
            } else if (this.status.level > 80) {
              newClass = 'alert';
            }
            levelValue.removeClass();
            levelValue.addClass(newClass);
          }

          if (this.status.score) {
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
          if ('enableAlarmButton' in this.buttons) {
            if (streamStatus.analysing == ANALYSING_NONE) {
              // Not doing analysis, so enable/disable button should be grey

              if (!this.buttons.enableAlarmButton.hasClass('disabled')) {
                this.buttons.enableAlarmButton.addClass('disabled');
                this.buttons.enableAlarmButton.prop('title', disableAlarmsStr);
              }
            } else {
              this.buttons.enableAlarmButton.removeClass('disabled');
              this.buttons.enableAlarmButton.prop('title', enableAlarmsStr);
            } // end if doing analysis
            this.buttons.enableAlarmButton.prop('disabled', false);
          } // end if have enableAlarmButton

          if ('forceAlarmButton' in this.buttons) {
            if (streamStatus.state == STATE_ALARM || streamStatus.state == STATE_ALERT) {
              // Ic0n: My thought here is that the non-disabled state should be for killing an alarm
              // and the disabled state should be to force an alarm
              if (this.buttons.forceAlarmButton.hasClass('disabled')) {
                this.buttons.forceAlarmButton.removeClass('disabled');
                this.buttons.forceAlarmButton.prop('title', cancelForcedAlarmStr);
              }
            } else {
              if (!this.buttons.forceAlarmButton.hasClass('disabled')) {
                // Looks disabled
                this.buttons.forceAlarmButton.addClass('disabled');
                this.buttons.forceAlarmButton.prop('title', forceAlarmStr);
              }
            }
            this.buttons.forceAlarmButton.prop('disabled', false);
          }
        } // end if canEdit.Monitors

        if (this.status.auth) {
          if (this.status.auth != auth_hash) {
            // Don't reload the stream because it causes annoying flickering. Wait until the stream breaks.
            console.log("Changed auth from " + auth_hash + " to " + this.status.auth);
            auth_hash = this.status.auth;
            auth_relay = this.status.auth_relay;
          }
        } // end if have a new auth hash
      } // end if has state
    } else {
      console.error(respObj.message);
      // Try to reload the image stream.
      if (stream.src) {
        console.log('Reloading stream: ' + stream.src);
        let src = stream.src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
        src = src.replace(/auth=\w+/i, 'auth='+auth_hash);
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
    if (respObj.result == 'Ok') {
      const captureFPSValue = $j('#captureFPSValue'+this.id);
      const analysisFPSValue = $j('#analysisFPSValue'+this.id);
      const viewingFPSValue = $j('#viewingFPSValue'+this.id);
      const monitor = respObj.monitor;

      if (monitor.FrameRate) {
        const fpses = monitor.FrameRate.split(',');
        fpses.forEach(function(fps) {
          const name_values = fps.split(':');
          const name = name_values[0].trim();
          const value = name_values[1].trim().toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 2});

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
      } else {
        if (analysisFPSValue.length && (analysisFPSValue.text() != monitor.AnalysisFPS)) {
          analysisFPSValue.text(monitor.AnalysisFPS);
        }

        if (captureFPSValue.length && (captureFPSValue.text() != monitor.CaptureFPS)) {
          captureFPSValue.text(monitor.CaptureFPS);
        }
        if (viewingFPSValue.length && viewingFPSValue.text() == '') {
          $j('#viewingFPS'+this.id).hide();
        }
      }

      if (canEdit.Monitors) {
        if ('enableAlarmButton' in this.buttons) {
          if (monitor.Analysing == 'None') {
            // Not doing analysis, so enable/disable button should be grey

            if (!this.buttons.enableAlarmButton.hasClass('disabled')) {
              this.buttons.enableAlarmButton.addClass('disabled');
              this.buttons.enableAlarmButton.prop('title', disableAlarmsStr);
            }
          } else {
            this.buttons.enableAlarmButton.removeClass('disabled');
            this.buttons.enableAlarmButton.prop('title', enableAlarmsStr);
          } // end if doing analysis
          this.buttons.enableAlarmButton.prop('disabled', false);
        } // end if have enableAlarmButton

        if ('forceAlarmButton' in this.buttons) {
          if (monitor.Status == STATE_ALARM || monitor.Status == STATE_ALERT) {
            // Ic0n: My thought here is that the non-disabled state should be for killing an alarm
            // and the disabled state should be to force an alarm
            if (this.buttons.forceAlarmButton.hasClass('disabled')) {
              this.buttons.forceAlarmButton.removeClass('disabled');
              this.buttons.forceAlarmButton.prop('title', cancelForcedAlarmStr);
            }
          } else {
            if (!this.buttons.forceAlarmButton.hasClass('disabled')) {
              // Looks disabled
              this.buttons.forceAlarmButton.addClass('disabled');
              this.buttons.forceAlarmButton.prop('title', forceAlarmStr);
            }
          }
          this.buttons.forceAlarmButton.prop('disabled', false);
        }
      } // end if canEdit.Monitors

      this.setAlarmState(monitor.Status);

      if (respObj.auth_hash) {
        if (auth_hash != respObj.auth_hash) {
          // Don't reload the stream because it causes annoying flickering. Wait until the stream breaks.
          console.log("Changed auth from " + auth_hash + " to " + respObj.auth_hash);
          auth_hash = respObj.auth_hash;
          auth_relay = respObj.auth_relay;
        }
      } // end if have a new auth hash
    } else {
      checkStreamForErrors('getStatusCmdResponse', respObj);
    }
  }; // this.getStatusCmdResponse

  this.statusCmdQuery = function() {
    $j.getJSON(this.url + '?view=request&request=status&entity=monitor&element[]=Status&element[]=CaptureFPS&element[]=AnalysisFPS&element[]=Analysing&element[]=Recording&id='+this.id+'&'+auth_relay)
        .done(this.getStatusCmdResponse.bind(this))
        .fail(logAjaxFail);
  };

  this.statusQuery = function() {
    this.streamCommand(CMD_QUERY);
  };

  this.streamCmdQuery = function(resent) {
    if (this.type != 'WebSite') {
      // Websites don't have streaming
      // Can't use streamCommand because it aborts

      this.streamCmdParms.command = CMD_QUERY;
      this.streamCmdReq(this.streamCmdParms);
    }
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
      console.log('Aborting in progress ajax for alarm', this.ajaxQueue);
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
      dataType: 'json'
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
        dataType: 'json'
      })
          .done(this.getStreamCmdResponse.bind(this))
          .fail(this.onFailure.bind(this));
    };
  }
  this.analyse_frames = true;
  this.show_analyse_frames = function(toggle) {
    const streamImage = this.getElement();
    if (streamImage.nodeName == 'IMG') {
      this.analyse_frames = toggle;
      this.streamCmdParms.command = this.analyse_frames ? CMD_ANALYZE_ON : CMD_ANALYZE_OFF;
      this.streamCmdReq(this.streamCmdParms);
    } else {
      console.log("Not streaming from zms, can't show analysis frames");
    }
  };

  this.setMaxFPS = function(maxfps) {
    if (1) {
      this.streamCommand({command: CMD_MAXFPS, maxfps: maxfps});
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

function startRTSP2WebPlay(videoEl, url, stream) {
  const mediaStream = new MediaStream();
  videoEl.srcObject = mediaStream;
  stream.webrtc = new RTCPeerConnection({
    iceServers: [{
      urls: ['stun:stun.l.google.com:19302']
    }],
    sdpSemantics: 'unified-plan'
  });

  /* It doesn't work yet
  stream.webrtc.ondatachannel = function(event) {
    console.log('onDataChannel trigger:', event.channel);
    event.channel.onopen = () => console.log(`Data channel is open`);
    event.channel.onmessage = (event) => console.log('Event data:', event.data);
  };
  */

  stream.webrtc.oniceconnectionstatechange = function(event) {
    console.log('iceServer changed state to: ', '"', event.currentTarget.connectionState, '"');
  };
  stream.webrtc.onnegotiationneeded = async function handleNegotiationNeeded() {
    const offer = await stream.webrtc.createOffer({
      //iceRestart:true,
      offerToReceiveAudio: true,
      offerToReceiveVideo: true
    });
    await stream.webrtc.setLocalDescription(offer);
    $j.post(url, {
      data: btoa(stream.webrtc.localDescription.sdp)
    }, function(data) {
      try {
        stream.webrtc.setRemoteDescription(new RTCSessionDescription({
          type: 'answer',
          sdp: atob(data)
        }));
      } catch (e) {
        console.warn(e);
      }
    });
  };
  stream.webrtc.onsignalingstatechange = async function signalingstatechange() {
    switch (stream.webrtc.signalingState) {
      case 'have-local-offer':
        break;
      case 'stable':
        /*
        * There is no ongoing exchange of offer and answer underway.
        * This may mean that the RTCPeerConnection object is new, in which case both the localDescription and remoteDescription are null;
        * it may also mean that negotiation is complete and a connection has been established.
        */
        break;
      case 'closed':
        /*
         * The RTCPeerConnection has been closed.
         */
        break;
      default:
        console.log(`unhandled signalingState is ${stream.webrtc.signalingState}`);
        break;
    }
  };

  stream.webrtc.ontrack = function ontrack(event) {
    console.log(event.track.kind + ' track is delivered');
    mediaStream.addTrack(event.track);
  };

  const webrtcSendChannel = stream.webrtc.createDataChannel('rtsptowebSendChannel');
  webrtcSendChannel.onopen = (event) => {
    console.log(`${webrtcSendChannel.label} has opened`);
    webrtcSendChannel.send('ping');
  };
  webrtcSendChannel.onclose = (_event) => {
    console.log(`${webrtcSendChannel.label} has closed`);
    if (stream.started) {
      startRTSP2WebPlay(videoEl, url, stream);
    }
  };
  webrtcSendChannel.onmessage = (event) => console.log(event.data);
}

function startMsePlay(context, videoEl, url) {
  const mse = new MediaSource();
  mse.addEventListener('sourceopen', function() {
    const ws = new WebSocket(url);
    ws.binaryType = 'arraybuffer';
    ws.onopen = function(event) {
      console.log('Connect to ws');
    };
    ws.onmessage = function(event) {
      const data = new Uint8Array(event.data);
      if (data[0] === 9) {
        let mimeCodec;
        const decodedArr = data.slice(1);
        if (window.TextDecoder) {
          mimeCodec = new TextDecoder('utf-8').decode(decodedArr);
        } else {
          console.log("Browser too old. Doesn't support TextDecoder");
        }
        context.mseSourceBuffer = mse.addSourceBuffer('video/mp4; codecs="' + mimeCodec + '"');
        context.mseSourceBuffer.mode = 'segments';
        context.mseSourceBuffer.addEventListener('updateend', pushMsePacket, videoEl, context);
      } else {
        readMsePacket(event.data, videoEl, context);
      }
    };
  }, false);
  videoEl.src = window.URL.createObjectURL(mse);
}

function pushMsePacket(videoEl, context) {
  if (context != undefined && !context.mseSourceBuffer.updating) {
    if (context.mseQueue.length > 0) {
      const packet = context.mseQueue.shift();
      context.mseSourceBuffer.appendBuffer(packet);
    } else {
      context.mseStreamingStarted = false;
    }
  }
  if (videoEl.buffered != undefined && videoEl.buffered.length > 0) {
    if (typeof document.hidden !== 'undefined' && document.hidden) {
    // no sound, browser paused video without sound in background
      videoEl.currentTime = videoEl.buffered.end((videoEl.buffered.length - 1)) - 0.5;
    }
  }
}

function readMsePacket(packet, videoEl, context) {
  if (!context.mseStreamingStarted) {
    context.mseSourceBuffer.appendBuffer(packet);
    context.mseStreamingStarted = true;
    return;
  }
  context.mseQueue.push(packet);
  if (!context.mseSourceBuffer.updating) {
    pushMsePacket(videoEl, context);
  }
}
