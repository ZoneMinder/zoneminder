"use strict";
var janus = null;
const streaming = [];

function MonitorStream(monitorData) {
  this.id = monitorData.id;
  this.name = monitorData.name;
  this.started = false;
  this.connKey = monitorData.connKey;
  this.url = monitorData.url;
  this.url_to_zms = monitorData.url_to_zms;
  this.width = monitorData.width;
  this.height = monitorData.height;
  this.RTSP2WebEnabled = monitorData.RTSP2WebEnabled;
  this.RTSP2WebType = monitorData.RTSP2WebType;
  this.RTSP2WebStream = monitorData.RTSP2WebStream;
  this.Go2RTCEnabled = monitorData.Go2RTCEnabled;
  this.Go2RTCMSEBufferCleared = true;
  this.currentChannelStream = null;
  this.MSEBufferCleared = true;
  this.webrtc = null;
  this.hls = null;
  this.mse = null;
  this.wsMSE = null;
  this.streamStartTime = 0; // Initial point of flow start time. Used for flow lag time analysis.
  this.waitingStart;
  this.mseListenerSourceopenBind = null;
  this.streamListenerBind = null;
  this.mseSourceBufferListenerUpdateendBind = null;
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
  this.capturing = monitorData.capturing;
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
      console.log('Image stream has loaded! starting streamCmd for monitor ID='+this.id+' connKey='+this.connKey+' in '+statusRefreshTimeout + 'ms');
      this.streamCmdQuery.bind(this);
      this.streamCmdTimer = setInterval(this.streamCmdQuery.bind(this), statusRefreshTimeout);
    }
  };

  this.player = '';
  this.activePlayer = ''; // Variants: go2rtc, janus, rtsp2web_hls, rtsp2web_mse, rtsp2web_webrtc, zms. Relevant for this.player = ''/Auto
  this.setPlayer = function(p) {
    if (-1 != p.indexOf('go2rtc')) {

    } else if (-1 != p.indexOf('rtsp2web')) {
      if (-1 != p.indexOf('_hls')) {
        this.RTSP2WebType = 'HLS';
      } else if (-1 != p.indexOf('_mse')) {
        this.RTSP2WebType = 'MSE';
      } else if (-1 != p.indexOf('_webrtc')) {
        this.RTSP2WebType = 'WebRTC';
      }
    } else if (-1 != p.indexOf('janus')) {

    }
    return this.player = p;
  };

  this.manageAvailablePlayersOptions = function(action, opt) {
    if (action == 'disable') {
      opt.setAttribute('disabled', '');
      opt.setAttribute('title', playerDisabledInMonitorSettings);
    } else if (action == 'enable') {
      opt.removeAttribute('disabled');
      opt.removeAttribute('title');
    }
  };

  this.manageAvailablePlayers = function() {
    const selectPlayers = document.querySelector('[id="player"][name="codec"]');
    const opts = selectPlayers.options;

    for (var opt, j = 0; opt = opts[j]; j++) {
      if (-1 !== opt.value.indexOf('go2rtc')) {
        if (this.Go2RTCEnabled) {
          this.manageAvailablePlayersOptions('enable', opt);
        } else {
          this.manageAvailablePlayersOptions('disable', opt);
        }
      } else if (-1 !== opt.value.indexOf('rtsp2web')) {
        if (this.RTSP2WebEnabled) {
          this.manageAvailablePlayersOptions('enable', opt);
        } else {
          this.manageAvailablePlayersOptions('disable', opt);
        }
      }
    }
    let selectedPlayerOption = selectPlayers.options[selectPlayers.selectedIndex];
    if (selectedPlayerOption) {
      if (selectedPlayerOption.value == '') {
        // Perhaps "Auto" is left from the previous monitor, we will change it according to the cookies.
        const zmWatchPlayer = getCookie('zmWatchPlayer');
        if (zmWatchPlayer) {
          selectPlayers.value = zmWatchPlayer;
          selectedPlayerOption = selectPlayers.options[selectPlayers.selectedIndex];
        }
      }
      if (selectedPlayerOption && selectedPlayerOption.disabled) {
        // Selected player is not available for the current monitor
        selectPlayers.value = ''; // Auto
      }
    }
    this.player = selectPlayers.value;
  };

  this.element = null;
  this.getElement = function() {
    if (this.element) return this.element;
    this.element = document.getElementById('liveStream'+this.id);
    if (!this.element) {
      console.error("No element for #liveStream"+this.id);
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
    const newscaleSelect = newscale;

    const stream = this.getElement();
    if (!stream) {
      console.log('No stream in setScale');
      return;
    }
    console.log("setScale", stream);

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
        const newSize = scaleToFit(this.width, this.height, $j(stream), $j(this.bottomElement), $j('#wrapperMonitor'));
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
    if (width && (width != '0px') && (stream.style.width.search('%') == -1)) {
      if (param.resizeImg) {
        monitor_frame.css('width', parseInt(width));
      }
    }
    if (param.resizeImg) {
      if (stream.style.width) stream.style.width = '100%';
      if (height && height != '0px') stream.style.height = height;
    } else { //This code will not be needed when using GridStack & PanZoom on Montage page. Only required when trying to use "scaleControl"
      if (newscaleSelect != 0) {
        stream.style.width = 'auto';
        $j(stream).closest('.monitorStream')[0].style.overflow = 'auto';
      } else {
        //const monitor_stream = $j(stream).closest('.monitorStream');
        //const realWidth = monitor_stream.attr('data-width');
        //const realHeight = monitor_stream.attr('data-height');
        //const ratio = realWidth / realHeight;
        //const imgWidth = $j(stream)[0].offsetWidth + 4; // including border
        stream.style.width = '100%';
        $j(stream).closest('.monitorStream')[0].style.overflow = 'hidden';
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
    const stream = this.getElement();
    if (!stream) {
      console.log("No stream in setStreamScale");
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
      if (stream.nodeName == 'IMG') {
        const oldSrc = stream.src;
        if (!oldSrc) {
          console.log('No src on img?!', stream);
          return;
        }
        let newSrc = oldSrc.replace(/scale=\d+/i, 'scale='+newscale);
        newSrc = newSrc.replace(/auth=\w+/i, 'auth='+auth_hash);
        if (newSrc != oldSrc) {
          this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
          // We know that only the first zms will get the command because the
          // second can't open the commandQueue until the first exits
          // This is necessary because safari will never close the first image
          if (-1 != stream.src.search('connkey') && -1 != stream.src.search('mode=single')) {
            this.streamCommand(CMD_QUIT);
          }
          console.log("Changing src from " + stream.src + " to " + newSrc + 'refresh timeout:' + statusRefreshTimeout);
          stream.src = '';
          stream.src = newSrc;
          this.streamCmdTimer = setInterval(this.streamCmdQuery.bind(this), statusRefreshTimeout);
        }
      }
    }
  }; // setStreamScale

  /*
  * streamChannel = 0 || Primary; 1 || Secondary.
  */
  this.start = function(streamChannel = 'default') {
    if (streamChannel === null || streamChannel === '' || currentView == 'montage') streamChannel = 'default';
    if (!['default', 0, 1].includes(streamChannel)) {
      streamChannel = (streamChannel.toLowerCase() == 'primary') ? 0 : 1;
    }
    this.streamListenerBind = streamListener.bind(null, this);

    console.log('start', this.Go2RTCEnabled, (!this.player), (-1 != this.player.indexOf('go2rtc')), ((!this.player) || (-1 != this.player.indexOf('go2rtc'))));

    $j('#volumeControls').hide();

    if (this.Go2RTCEnabled && ((!this.player) || (-1 !== this.player.indexOf('go2rtc')))) {
      if (ZM_GO2RTC_PATH) {
        const url = new URL(ZM_GO2RTC_PATH);

        const old_stream = this.getElement();
        const stream = this.element = document.createElement('video-stream');
        stream.id = old_stream.id; // should be liveStream+id
        stream.style = old_stream.style; // Copy any applied styles
        stream.background = true; // We do not use the document hiding/showing analysis from "video-rtc.js", because we have our own analysis
        const Go2RTCModUrl = url;
        const webrtcUrl = Go2RTCModUrl;
        this.currentChannelStream = (streamChannel == 'default') ? ((this.RTSP2WebStream == 'Secondary') ? 1 : 0) : streamChannel;
        webrtcUrl.protocol = (url.protocol=='https:') ? 'wss:' : 'ws';
        webrtcUrl.pathname += "/ws";
        //webrtcUrl.search = 'src='+this.id;
        webrtcUrl.search = 'src='+this.id+'_'+this.currentChannelStream;
        stream.src = webrtcUrl.href;
        const stream_container = old_stream.parentNode;

        old_stream.remove();
        stream_container.appendChild(stream);
        this.webrtc = stream; // track separately do to api differences between video tag and video-stream
        this.set_stream_volume(this.muted ? 0.0 : this.volume/100);
        if (-1 != this.player.indexOf('_')) {
          stream.mode = this.player.substring(this.player.indexOf('_')+1);
        }

        clearInterval(this.statusCmdTimer); // Fix for issues in Chromium when quickly hiding/showing a page. Doesn't clear statusCmdTimer when minimizing a page https://stackoverflow.com/questions/9501813/clearinterval-not-working
        this.statusCmdTimer = setInterval(this.statusCmdQuery.bind(this), statusRefreshTimeout);
        this.started = true;
        this.streamListenerBind();

        $j('#volumeControls').show();
        if (typeof observerMontage !== 'undefined') observerMontage.observe(stream);
        this.activePlayer = 'go2rtc';
        return;
      } else {
        alert("ZM_GO2RTC_PATH is empty. Go to Options->System and set ZM_GO2RTC_PATH accordingly.");
      }
    }

    if (this.janusEnabled && ((!this.player) || (-1 !== this.player.indexOf('janus')))) {
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
      this.streamListenerBind();
      this.activePlayer = 'janus';
      return;
    }

    if (this.RTSP2WebEnabled && ((!this.player) || (-1 !== this.player.indexOf('rtsp2web')))) {
      if (ZM_RTSP2WEB_PATH) {
        let stream = this.getElement();
        if (stream.nodeName != 'VIDEO') {
          // replace with new video tag.
          const stream_container = stream.parentNode;
          const new_stream = this.element = document.createElement('video');
          new_stream.id = stream.id; // should be liveStream+id
          new_stream.setAttribute("autoplay", "");
          new_stream.setAttribute("muted", "");
          new_stream.setAttribute("playsinline", "");
          new_stream.style = stream.style; // Copy any applied styles
          stream.remove();
          stream_container.appendChild(new_stream);
          stream = new_stream;
        }
        const url = new URL(ZM_RTSP2WEB_PATH);
        const useSSL = (url.protocol == 'https');

        const rtsp2webModUrl = url;
        rtsp2webModUrl.username = '';
        rtsp2webModUrl.password = '';
        //.urlParts.length > 1 ? urlParts[1] : urlParts[0]; // drop the username and password for viewing
        this.currentChannelStream = (streamChannel == 'default') ? ((this.RTSP2WebStream == 'Secondary') ? 1 : 0) : streamChannel;
        if (this.RTSP2WebType == 'HLS') {
          const hlsUrl = rtsp2webModUrl;
          hlsUrl.pathname = "/stream/" + this.id + "/channel/" + this.currentChannelStream + "/hls/live/index.m3u8";
          /*
          if (useSSL) {
            hlsUrl = "https://" + rtsp2webModUrl + "/stream/" + this.id + "/channel/0/hls/live/index.m3u8";
          } else {
            hlsUrl = "http://" + rtsp2webModUrl + "/stream/" + this.id + "/channel/0/hls/live/index.m3u8";
          }
          */
          if (Hls.isSupported()) {
            this.hls = new Hls();
            this.hls.loadSource(hlsUrl.href);
            this.hls.attachMedia(stream);
          } else if (stream.canPlayType('application/vnd.apple.mpegurl')) {
            stream.src = hlsUrl.href;
          }
          this.activePlayer = 'rtsp2web_hls';
        } else if (this.RTSP2WebType == 'MSE') {
          const mseUrl = rtsp2webModUrl;
          mseUrl.protocol = useSSL ? 'wss' : 'ws';
          mseUrl.pathname = "/stream/" + this.id + "/channel/" + this.currentChannelStream + "/mse";
          mseUrl.search = "uuid=" + this.id + "&channel=" + this.currentChannelStream + "";
          startMsePlay(this, stream, mseUrl.href);
          this.activePlayer = 'rtsp2web_mse';
        } else if (this.RTSP2WebType == 'WebRTC') {
          const webrtcUrl = rtsp2webModUrl;
          webrtcUrl.pathname = "/stream/" + this.id + "/channel/" + this.currentChannelStream + "/webrtc";
          startRTSP2WebPlay(stream, webrtcUrl.href, this);
          this.activePlayer = 'rtsp2web_webrtc';
        }
        clearInterval(this.statusCmdTimer); // Fix for issues in Chromium when quickly hiding/showing a page. Doesn't clear statusCmdTimer when minimizing a page https://stackoverflow.com/questions/9501813/clearinterval-not-working
        this.statusCmdTimer = setInterval(this.statusCmdQuery.bind(this), statusRefreshTimeout);
        this.started = true;
        this.streamListenerBind();
        $j('#volumeControls').show();
        return;
      } else {
        console.log("ZM_RTSP2WEB_PATH is empty. Go to Options->System and set ZM_RTSP2WEB_PATH accordingly.");
      }
    }

    // zms stream
    let stream = this.getElement();
    if (!stream) return;

    if (stream.nodeName != 'IMG') {
      // replace with new img tag.
      const stream_container = stream.parentNode;
      const new_stream = this.element = document.createElement('img');
      new_stream.id = stream.id; // should be liveStream+id
      new_stream.style = stream.style; // Copy any applied styles
      stream.remove();
      stream_container.appendChild(new_stream);
      stream = new_stream;
    }
    this.streamCmdTimer = clearTimeout(this.streamCmdTimer);
    // Step 1 make sure we are streaming instead of a static image
    if (stream.getAttribute('loading') == 'lazy') {
      stream.setAttribute('loading', 'eager');
    }
    let src = this.url_to_zms.replace(/mode=single/i, 'mode=jpeg');
    if (-1 == src.search('auth')) {
      src += '&'+auth_relay;
    } else {
      src = src.replace(/auth=\w+/i, 'auth='+auth_hash);
    }
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
    this.streamListenerBind();
    this.activePlayer = 'zms';
  }; // this.start

  this.stop = function() {
    const stream = this.getElement();
    if (!stream) {
      console.warn(`! ${dateTimeToISOLocal(new Date())} Stream for ID=${this.id} it is impossible to stop because it is not found.`);
      return;
    }
    console.debug(`! ${dateTimeToISOLocal(new Date())} Stream for ID=${this.id} STOPPED`);
    //if ( 1 ) {
    if (-1 === this.player.indexOf('rtsp2web')) {
      if (stream.src) {
        let src = stream.src;
        if (-1 === src.indexOf('mode=')) {
          src += '&mode=single';
        } else {
          src = src.replace(/mode=jpeg/i, 'mode=single');
        }

        if (stream.src != src) {
          stream.src = '';
          stream.src = src;
        }
      }
    }
    this.streamCommand(CMD_STOP);
    this.statusCmdTimer = clearInterval(this.statusCmdTimer);
    this.streamCmdTimer = clearInterval(this.streamCmdTimer);
    this.started = false;
    if (-1 !== this.player.indexOf('go2rtc')) {
      if (!(stream.wsState === WebSocket.CLOSED && stream.pcState === WebSocket.CLOSED)) {
        try {
          stream.ondisconnect();
        } catch (e) {
          console.warn(e);
        }
      }
    } else if (-1 !== this.player.indexOf('rtsp2web')) {
      if (this.webrtc) {
        if (this.webrtc.close) this.webrtc.close();
        stream.src = '';
        stream.srcObject = null;
        this.webrtc = null;
      }
      if (this.hls) {
        this.hls.destroy();
        this.hls = null;
      }
      if (this.RTSP2WebType == 'MSE') {
        this.stopMse();
      }
    }
  };

  this.stopMse = function() {
    this.MSEBufferCleared = false;
    this.streamStartTime = 0;
    return new Promise((resolve, reject) => {
      if (this.mseSourceBuffer && this.mseSourceBuffer.updating) {
        this.mseSourceBuffer.abort();
      }

      if (this.mseSourceBuffer) {
        this.mseSourceBuffer.removeEventListener('updateend', this.mseSourceBufferListenerUpdateendBind); // affects memory release
        this.mseSourceBuffer.addEventListener('updateend', onBufferRemoved, this);
        try {
          /*
          Very, very rarely, on the MONTAGE PAGE THERE MAY BE AN ERROR OF THE TYPE: TypeError: Failed to execute 'remove' on 'SourceBuffer': The start provided (0) is outside the range (0, 0).
          Possibly due to high CPU load, the browser does not have time to process or the "src" attribute was removed from the object.
          */
          this.mseSourceBuffer.remove(0, Infinity);
        } catch (e) {
          console.warn(`${dateTimeToISOLocal(new Date())} An error occurred while cleaning Source Buffer for ID=${this.id}`, e);
          reject(e);
        }
      }

      if (this.mse) {
        this.mse.removeEventListener('sourceopen', this.mseListenerSourceopenBind); // This really makes a big difference in freeing up memory.
      }

      if (!this.mseSourceBuffer) {
        resolve();
      }

      function onBufferRemoved(this_) {
        this.removeEventListener('updateend', onBufferRemoved);
        resolve();
      }
    })
        .then(() => {
          if (this.mseSourceBuffer) {
            this.mse.removeSourceBuffer(this.mseSourceBuffer);
            this.mse.endOfStream();
          }
          this.closeWebSocket();
          this.mse = null;
          this.mseStreamingStarted = false;
          this.mseSourceBuffer = null;
          this.MSEBufferCleared = true;
        })
        .catch((error) => {
          console.warn(`${dateTimeToISOLocal(new Date())} An error occurred while stopMse() for ID=${this.id}`, error);
          this.closeWebSocket();
          this.mse = null;
          this.mseStreamingStarted = false;
          this.mseSourceBuffer = null;
          this.MSEBufferCleared = true;
        });
  };

  this.kill = function() {
    if (janus && streaming[this.id]) {
      streaming[this.id].detach();
    }
    const stream = this.getElement();
    if (!stream) {
      console.log("No element found for monitor "+this.id);
      return;
    }
    stream.onerror = null;
    stream.onload = null;

    // this.stop tells zms to stop streaming, but the process remains. We need to turn the stream into an image.
    if (stream.src && -1 === this.player.indexOf('rtsp2web')) {
      stream.src = '';
    }
    this.stop();
  };

  this.restart = function(channelStream = "default", delay = 200) {
    this.stop();
    const this_ = this;
    setTimeout(function() {// During the downtime, the monitor may have already started to work.
      if (!this_.started) this_.start(channelStream);
    }, delay);
  };

  this.pause = function() {
    if (this.RTSP2WebEnabled || this.Go2RTCEnabled) {
      /* HLS does not have "src", WebRTC and MSE have "src" */
      this.element.pause();
      this.statusCmdTimer = clearInterval(this.statusCmdTimer);
    } else {
      if (this.element.src) {
        this.streamCommand(CMD_PAUSE);
      } else {
        this.element.pause();
        this.statusCmdTimer = clearInterval(this.statusCmdTimer);
      }
    }
  };

  this.play = function() {
    console.log('play');
    if (this.Go2RTCEnabled) {
      this.element.play(); // go2rtc player will handle mute
      this.statusCmdTimer = setInterval(this.statusCmdQuery.bind(this), statusRefreshTimeout);
    } else if (this.RTSP2WebEnabled) {
      /* HLS does not have "src", WebRTC and MSE have "src" */
      this.element.play().catch(() => {
        if (!this.element.muted) {
          console.log('played muted');
          this.element.muted = true;
          this.element.play().catch((er) => {
            console.warn(er);
          });
        } else {
          console.log('not muted');
        }
      });
      this.statusCmdTimer = setInterval(this.statusCmdQuery.bind(this), statusRefreshTimeout);
    } else {
      if (this.element.src) {
        this.streamCommand(CMD_PLAY);
      } else {
        this.element.play();
        this.statusCmdTimer = setInterval(this.statusCmdQuery.bind(this), statusRefreshTimeout);
      }
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

  this.volume_slider = null;
  this.volume = 0.0; // Half

  this.setup_volume = function(slider) {
    this.volume_slider = slider;
    this.volume_slider.addEventListener('click', (e) => {
      const x = e.pageX - this.volume_slider.getBoundingClientRect().left; // or e.offsetX (less support, though)
      const clickedValue = parseInt(x * this.volume_slider.max / this.volume_slider.offsetWidth);
      this.volume_slider.value = clickedValue;
      this.set_volume(clickedValue);
      this.muted = clickedValue ? false : true;
      setCookie('zmWatchMuted', this.muted);
      this.mute_btn.firstElementChild.innerHTML = (this.muted ? 'volume_off' : 'volume_up');
    });
    this.volume = this.volume_slider.value;
  };

  /* Takes volume as 0->100 */
  this.set_volume = function(volume) {
    this.volume = volume;
    this.set_stream_volume(volume/100);
    setCookie('zmWatchVolume', this.volume);
  };

  /* Takes volume as percentage */
  this.set_stream_volume = function(volume) {
    if (this.webrtc && this.webrtc.volume ) {
      this.webrtc.volume(volume);
    } else {
      const stream = this.getElement();
      stream.volume = volume;
    }
  };

  this.mute_btn = null;
  this.muted = false;

  this.setup_mute = function(mute_btn) {
    this.mute_btn = mute_btn;
    this.mute_btn.onclick = () => {
      this.muted = !this.muted;
      setCookie('zmWatchMuted', this.muted);
      this.mute_btn.firstElementChild.innerHTML = (this.muted ? 'volume_off' : 'volume_up');

      if (this.muted === false) {
        this.set_stream_volume(this.volume/100); // lastvolume
        if (this.volume_slider) this.volume_slider.value = this.volume;
      } else {
        this.set_stream_volume(0.0);
        if (this.volume_slider) this.volume_slider.value = 0;
      }
    };
    this.muted = (this.mute_btn.firstElementChild.innerHTML == 'volume_off');
    if (this.muted) {
      // muted, adjust volume bar
      this.set_stream_volume(0.0);
      if (this.volume_slider) this.volume_slider.value = 0;
    }
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
      if (!this.started) return;
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

    if (this.Go2RTCEnabled && ((!this.player) || (-1 !== this.player.indexOf('go2rtc')))) {
    } else if (this.RTSP2WebEnabled && ((!this.player) || (-1 !== this.player.indexOf('rtsp2web')))) {
      // We correct the lag from real time. Relevant for long viewing and network problems.
      if (this.RTSP2WebType == 'MSE') {
        const videoEl = document.getElementById("liveStream" + this.id);
        if (this.wsMSE && videoEl.buffered != undefined && videoEl.buffered.length > 0) {
          const videoElCurrentTime = videoEl.currentTime; // Current time of playback
          const currentTime = (Date.now() / 1000);
          const deltaRealTime = (currentTime - this.streamStartTime).toFixed(2); // How much real time has passed since playback started
          const bufferEndTime = videoEl.buffered.end(videoEl.buffered.length - 1);
          let delayCurrent = (deltaRealTime - videoElCurrentTime).toFixed(2); // Delay of playback moment from real time
          if (delayCurrent < 0) {
            //Possibly with high client CPU load. Cannot be negative.
            this.streamStartTime = currentTime - bufferEndTime;
            delayCurrent = 0;
          }

          $j('#delayValue'+this.id).text(delayCurrent);

          // The first 10 seconds are allocated for the start, at this point the delay can be more than 2-3 seconds. It is necessary to avoid STOP/START looping
          if (!videoEl.paused && deltaRealTime > 10) {
            // Ability to scroll through the last buffered frames when paused.
            if (bufferEndTime - videoElCurrentTime > 2.0) {
              // Correcting a flow lag of more than X seconds from the end of the buffer
              // When the client's CPU load is 99-100%, there may be problems with constant time adjustment, but this is better than a constantly increasing lag of tens of seconds.
              //console.debug(`${dateTimeToISOLocal(new Date())} Adjusting currentTime for a video object ID=${this.id}:${(bufferEndTime - videoElCurrentTime).toFixed(2)}sec.`);
              videoEl.currentTime = bufferEndTime - 0.1;
            }
            if (deltaRealTime - bufferEndTime > 1.5) {
              // Correcting the buffer end lag by more than X seconds from real time
              console.log(`${dateTimeToISOLocal(new Date())} Adjusting currentTime for a video object ID=${this.id} Buffer end lag from real time='${(deltaRealTime - bufferEndTime).toFixed(2)}sec. RESTART is started.`);

              this.restart(this.currentChannelStream);
            }
          }
        } else if (!this.wsMSE && this.started) {
          console.warn(`UNSCHEDULED CLOSE SOCKET for camera ID=${this.id}`);
          this.restart(this.currentChannelStream);
        }
      } else if (this.RTSP2WebType == 'WebRTC') {
        if ((!this.webrtc || (this.webrtc && this.webrtc.connectionState != "connected")) && this.started) {
          console.warn(`UNSCHEDULED CLOSE WebRTC for camera ID=${this.id}`);
          this.restart(this.currentChannelStream);
        }
      }
    } // end if Go2RTC or RTSP2Web
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
      if (!(streamCmdParms.command == CMD_STOP && (this.RTSP2WebEnabled || this.Go2RTCEnabled))) {
        //Otherwise, there will be errors in the console "Socket ... does not exist" when quickly switching stop->start and we also do not need to replace SRC in getStreamCmdResponse
        this.ajaxQueue = jQuery.ajaxQueue({
          url: this.url + (auth_relay?'?'+auth_relay:''),
          xhrFields: {withCredentials: true},
          data: streamCmdParms,
          dataType: 'json'
        })
            .done(this.getStreamCmdResponse.bind(this))
            .fail(this.onFailure.bind(this));
      };
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

  this.closeWebSocket = function() {
    console.log(`${dateTimeToISOLocal(new Date())} WebSocket for a video object ID=${this.id} is being closed.`);
    if (this.wsMSE && this.wsMSE.readyState !== WebSocket.CLOSING && this.wsMSE.readyState !== WebSocket.CLOSED) {
      //Socket may still be in the "CONNECTING" state. It would be better to wait for the connection and only then close it, but we will not complicate the code, since this happens rarely and does not globally affect the overall work.
      this.wsMSE.close(1000, "We close the connection");
    }
    this.mseQueue = []; // ABSOLUTELY NEEDED
  }; // end closeWebSocket

  this.clearWebSocket = function() {
    if (this.wsMSE) {
      this.wsMSE.onopen = () => {};
      this.wsMSE.onmessage = () => {};
      this.wsMSE.onclose = () => {};
      this.wsMSE.onerror = () => {};
      this.wsMSE = null;
      delete this.wsMSE;
    }
  };
  this.mseCodecs = '';

  this.onpcvideo = function(video2) {
    if (this.pc) {
      // Video+Audio > Video, H265 > H264, Video > Audio, WebRTC > MSE
      let rtcPriority = 0;
      let msePriority = 0;

      /** @type {MediaStream} */
      const stream = video2.srcObject;
      if (stream.getVideoTracks().length > 0) rtcPriority += 0x220;
      if (stream.getAudioTracks().length > 0) rtcPriority += 0x102;

      if (this.mseCodecs.indexOf('hvc1.') >= 0) msePriority += 0x230;
      if (this.mseCodecs.indexOf('avc1.') >= 0) msePriority += 0x210;
      if (this.mseCodecs.indexOf('mp4a.') >= 0) msePriority += 0x101;

      if (rtcPriority >= msePriority) {
        this.element.srcObject = stream;
        this.play();

        this.pcState = WebSocket.OPEN;

        this.wsState = WebSocket.CLOSED;
        if (this.ws) {
          this.ws.close();
          this.ws = null;
        }
      } else {
        this.pcState = WebSocket.CLOSED;
        if (this.pc) {
          this.pc.close();
          this.pc = null;
        }
      }
    }

    video2.srcObject = null;
  };
} // end class MonitorStream

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

async function get_PeerConnection(media, videoEl) {
  const pc = new RTCPeerConnection({
    bundlePolicy: 'max-bundle',
    iceServers: [{urls: 'stun:stun.l.google.com:19302'}],
    sdpSemantics: 'unified-plan', // important for Chromecast 1
  });

  const localTracks = [];
  /*
  if (/camera|microphone/.test(media)) {
    const tracks = await getMediaTracks('user', {
      video: media.indexOf('camera') >= 0,
      audio: media.indexOf('microphone') >= 0,
    });
    tracks.forEach(track => {
      pc.addTransceiver(track, {direction: 'sendonly'});
      if (track.kind === 'video') localTracks.push(track);
    });
  }
*/

  if (media.indexOf('display') >= 0) {
    const tracks = await getMediaTracks('display', {
      video: true,
      audio: media.indexOf('speaker') >= 0,
    });
    tracks.forEach((track) => {
      pc.addTransceiver(track, {direction: 'sendonly'});
      if (track.kind === 'video') localTracks.push(track);
    });
  }

  if (/video|audio/.test(media)) {
    const tracks = ['video', 'audio']
        .filter((kind) => media.indexOf(kind) >= 0)
        .map((kind) => pc.addTransceiver(kind, {direction: 'recvonly'}).receiver.track);
    console.log('localtracks', tracks);
    localTracks.push(...tracks);
  }

  videoEl.srcObject = new MediaStream(localTracks);

  return pc;
}

async function getMediaTracks(media, constraints) {
  try {
    const stream = media === 'user' ?
      await navigator.mediaDevices.getUserMedia(constraints) :
      await navigator.mediaDevices.getDisplayMedia(constraints);
    return stream.getTracks();
  } catch (e) {
    console.warn(e);
    return [];
  }
}

function startRTSP2WebPlay(videoEl, url, stream) {
  if (typeof RTCPeerConnection !== 'function') {
    const msg = `Your browser does not support 'RTCPeerConnection'. Monitor '${stream.name}' ID=${stream.id} not started.`;
    console.log(msg);
    stream.getElement().before(document.createTextNode(msg));
    stream.RTSP2WebType = null; // Avoid repeated restarts.
    return;
  }

  if (stream.webrtc) {
    stream.webrtc.close();
    stream.webrtc = null;
  }

  const mediaStream = new MediaStream();
  videoEl.srcObject = mediaStream;
  stream.webrtc = new RTCPeerConnection({
    iceServers: [{urls: ['stun:stun.l.google.com:19302']}],
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
    if (stream.webrtc.sctp && stream.webrtc.sctp.state != 'open') return;
    await stream.webrtc.setLocalDescription(offer);
    //console.log(stream.webrtc.localDescription.sdp);

    $j.post(url, {
      data: btoa(stream.webrtc.localDescription.sdp)
    }, function(data) {
      if ((stream.webrtc && 'sctp' in stream.webrtc && stream.webrtc.sctp) && stream.webrtc.sctp.state != 'stable') {
        //console.log(data);
        try {
          stream.webrtc.setRemoteDescription(new RTCSessionDescription({
            type: 'answer',
            sdp: atob(data)
          }));
        } catch (e) {
          console.warn(e);
        }
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

function streamListener(stream) {
  window.addEventListener('beforeunload', function(event) {
    console.log('streamListener');
    stream.kill();
  });
}

function mseListenerSourceopen(context, videoEl, url) {
  context.wsMSE = new WebSocket(url);
  context.wsMSE.binaryType = 'arraybuffer';

  context.wsMSE.onopen = function(event) {
    console.log(`Connect to ws for a video object ID=${context.id}`);
  };
  context.wsMSE.onclose = (event) => {
    context.clearWebSocket();
    console.log(`${dateTimeToISOLocal(new Date())} WebSocket CLOSED for a video object ID=${context.id}.`);
  };
  context.wsMSE.onerror = function(event) {
    console.warn(`${dateTimeToISOLocal(new Date())} WebSocket ERROR for a video object ID=${context.id}:`, event);
    if (this.started) this.restart();
  };
  context.wsMSE.onmessage = function(event) {
    if (!context.mse || (context.mse && context.mse.readyState !== "open")) return;
    const data = new Uint8Array(event.data);
    if (data[0] === 9) {
      let mimeCodec;
      const decodedArr = data.slice(1);
      if (window.TextDecoder) {
        mimeCodec = new TextDecoder('utf-8').decode(decodedArr);
      } else {
        console.log("Browser too old. Doesn't support TextDecoder");
      }

      if (MediaSource.isTypeSupported('video/mp4; codecs="' + mimeCodec + '"')) {
        console.log(`For a video object ID=${context.id} codec used: ${mimeCodec}`);
      } else {
        const msg = `For a video object ID=${context.id} codec '${mimeCodec}' not supported. Monitor '${context.name}' ID=${context.id} not starting.`;
        console.log(msg);
        context.getElement().before(document.createTextNode(msg));
        context.stop();
        context.RTSP2WebType = null; // Avoid repeated restarts
        return;
      }

      context.mseSourceBuffer = context.mse.addSourceBuffer('video/mp4; codecs="' + mimeCodec + '"');
      context.mseSourceBuffer.mode = 'segments';
      context.mseSourceBufferListenerUpdateendBind = pushMsePacket.bind(null, videoEl, context);
      context.mseSourceBuffer.addEventListener('updateend', context.mseSourceBufferListenerUpdateendBind);
    } else {
      readMsePacket(event.data, videoEl, context);
    }
  };
}

function startMsePlay(context, videoEl, url) {
  console.log('startMsePlay');
  var startPermitted = true;
  if (!context.MSEBufferCleared) {
    startPermitted = false;
  }
  if (context.wsMSE && context.wsMSE.readyState === WebSocket.OPEN) {
    startPermitted = false;
    context.closeWebSocket();
  } else if (context.wsMSE && context.wsMSE.readyState === WebSocket.CONNECTING) {
    startPermitted = false;
  }

  if (startPermitted) {
    clearTimeout(context.waitingStart);
  } else {
    context.waitingStart = setTimeout(function() {
      startMsePlay(context, videoEl, url);
    }, 100);
    return;
  }

  context.mse = new MediaSource();
  videoEl.onplay = (event) => {
    context.streamStartTime = (Date.now() / 1000).toFixed(2);
    if (videoEl.buffered.length > 0 && videoEl.currentTime < videoEl.buffered.end(videoEl.buffered.length - 1) - 0.1) {
      //For example, after a pause you press Play, you need to adjust the time.
      console.debug(`${dateTimeToISOLocal(new Date())} Adjusting currentTime for a video object ID=${context.id} Lag='${(videoEl.buffered.end(videoEl.buffered.length - 1) - videoEl.currentTime).toFixed(2)}sec.`);
      videoEl.currentTime = videoEl.buffered.end(videoEl.buffered.length - 1) - 0.1;
    }
  };
  videoEl.addEventListener('listener_pause', () => {
    /* Temporarily not in use */
  });
  context.mseListenerSourceopenBind = mseListenerSourceopen.bind(null, context, videoEl, url);
  context.mse.addEventListener('sourceopen', context.mseListenerSourceopenBind);

  // Older browsers may not have srcObject
  if ('srcObject' in videoEl) {
    try {
      //fileInfo (type) required by safari, but not by chrome..
      videoEl.srcObject = context.mse;
    } catch (err) {
      if (err.name != "TypeError") {
        throw err;
      }
      // Even if they do, they may only support MediaStream
      videoEl.src = window.URL.createObjectURL(context.mse);
    }
  } else {
    videoEl.src = window.URL.createObjectURL(context.mse);
  }
  $j('#delay'+context.id).removeClass('hidden');
}

function pushMsePacket(videoEl, context) {
  if (context != undefined && !context.mseSourceBuffer.updating) {
    if (context.mseQueue.length > 0) {
      const packet = context.mseQueue.shift();
      appendMseBuffer(packet, context);
    } else {
      context.mseStreamingStarted = false;
    }
  }
  /* This is not required yet, because we have our own algorithm for stopping the stream.
  if (videoEl.buffered != undefined && videoEl.buffered.length > 0) {
    if (typeof document.hidden !== 'undefined' && document.hidden) {
      // no sound, browser paused video without sound in background
      videoEl.currentTime = videoEl.buffered.end((videoEl.buffered.length - 1)) - 0.5;
    }
  }*/
}

function readMsePacket(packet, videoEl, context) {
  if (!context.started) {
    //Avoid race errors...
    return;
  }
  if (context.mseSourceBuffer) {
    if (!context.mseStreamingStarted) {
      appendMseBuffer(packet, context);
      context.mseStreamingStarted = true;
      return;
    }
  } else {
    // An extremely rare situation, but quite possible. Mistakes should be avoided.
    console.log("Source buffer for MSE missing. Probably the stream was stopped while reading the next packet.");
    return;
  }

  context.mseQueue.push(packet);
  if (!context.mseSourceBuffer.updating) {
    pushMsePacket(videoEl, context);
  }
}

function appendMseBuffer(packet, context) {
  try {
    /*
    You may receive the error "The SourceBuffer is full, and cannot free space to append additional buffers"
    Browsers do not report the maximum allowed buffer length and do not always clear it correctly in time, especially when there are network problems and key frames are lost during a UDP connection. An error may also appear when the client's CPU load is more than 99%
    https://developer.chrome.com/blog/quotaexceedederror
    https://stackoverflow.com/questions/53309874/sourcebuffer-removestart-end-removes-whole-buffered-timerange-how-to-handle
    https://stackoverflow.com/questions/50333767/html5-video-streaming-video-with-blob-urls/50354182#50354182
    */
    context.mseSourceBuffer.appendBuffer(packet);
  } catch (e) {
    // We could get the current length of the buffer and trim it, but that's not entirely straightforward, so let's not overcomplicate the code.
    if (e.name === 'QuotaExceededError') {
      const videoEl = document.getElementById("liveStream" + context.id);
      let secondsInBuffer = 0;
      if (videoEl.buffered != undefined && videoEl.buffered.length > 0) {
        secondsInBuffer = (videoEl.buffered.end(videoEl.buffered.length - 1) - videoEl.buffered.start(videoEl.buffered.length - 1)).toFixed(2);
      }
      console.warn(`${dateTimeToISOLocal(new Date())} Restarting stream due to an error adding data to the buffer '${secondsInBuffer}'sec., and length = ${videoEl.buffered.length} for ID=${context.id}`, e);

      // The client's browser needs to rest 1000ms.
      context.restart(context.currentChannelStream, 1000);
    } else {
      console.warn(`${dateTimeToISOLocal(new Date())} Error adding buffer to ID=${context.id}.`, e);
      throw e;
    }
  }
}
