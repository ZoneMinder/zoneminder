"use strict";

/**
 * EventStream - Manages a persistent zms MJPEG connection for event playback.
 *
 * Mirrors the MonitorStream.js constructor-function pattern. Frames arrive via
 * a hidden <img> receiving a multipart MJPEG stream from zms and are drawn to
 * a caller-supplied <canvas> on each img.onload.
 *
 * Commands (seek, pause, play, rate changes) are sent to zms over its existing
 * command-socket protocol via AJAX, exactly like MonitorStream and event.js.
 *
 * @param {Object} config
 * @param {number} config.monitorId
 * @param {number} config.monitorWidth   - Native monitor width
 * @param {number} config.monitorHeight  - Native monitor height
 * @param {string} config.url            - URL to index.php (for command AJAX)
 * @param {string} config.url_to_zms     - PathToZMS base URL
 * @param {HTMLCanvasElement} config.canvas
 * @param {number} [config.scale=100]    - Scale percentage
 */
function EventStream(config) {
  this.monitorId = config.monitorId;
  this.monitorWidth = config.monitorWidth;
  this.monitorHeight = config.monitorHeight;
  this.url = config.url;
  this.url_to_zms = config.url_to_zms;
  this.canvas = config.canvas;
  this.scale = config.scale ? parseInt(config.scale) : 100;

  this.connKey = null;
  this.img = null;
  this.started = false;
  this.paused = false;
  this.currentEventId = null;
  this.rate = 100;
  this.status = null;
  this.streamCmdTimer = null;
  this.ajaxQueue = null;
  this.rafId = null;

  // Recovery state
  this.consecutiveErrors = 0;
  this.maxRecoveryAttempts = 5;
  this.recoveryDelay = 1000; // ms, doubles on each retry
  this.recoveryTimer = null;
  this.lastOptions = null; // saved for restart after recovery

  // Callbacks — set by the consumer
  this.onStatus = null;
  this.onError = null;

  // How often to poll zms for status (ms). Use the global if available,
  // otherwise fall back to a sensible default.
  this.statusInterval = (typeof statusRefreshTimeout !== 'undefined')
    ? statusRefreshTimeout
    : (typeof streamTimeout !== 'undefined') ? streamTimeout : 2000;

  // Command parameters template — matches MonitorStream / event.js protocol
  this.streamCmdParms = {
    view: 'request',
    request: 'stream',
    connkey: null
  };

  // -------------------------------------------------------------------------
  // connKey generation (identical to MonitorStream)
  // -------------------------------------------------------------------------

  this.genConnKey = function() {
    return (Math.floor((Math.random() * 999999) + 1))
        .toLocaleString('en-US', {minimumIntegerDigits: 6, useGrouping: false});
  };

  // -------------------------------------------------------------------------
  // start(eventId, options) — Begin streaming an event
  // -------------------------------------------------------------------------

  /**
   * @param {number|string} eventId
   * @param {Object}  [options]
   * @param {number}  [options.time]    - Epoch seconds to start at
   * @param {number}  [options.frame=1] - Frame ID to start at
   * @param {number}  [options.rate=100] - Playback rate (100 = 1x)
   * @param {string}  [options.replay='none']
   * @param {number}  [options.maxfps]  - Max FPS for the stream
   */
  this.start = function(eventId, options) {
    options = options || {};
    this.currentEventId = eventId;
    this.rate = (options.rate !== undefined) ? options.rate : 100;
    this.paused = false;
    this.lastOptions = Object.assign({}, options);

    // Fresh connkey for this stream
    this.connKey = this.genConnKey();
    this.streamCmdParms.connkey = this.connKey;

    // Build zms URL
    var src = this.url_to_zms +
      '?source=event' +
      '&mode=jpeg' +
      '&event=' + eventId +
      '&monitor=' + this.monitorId +
      '&scale=' + this.scale +
      '&rate=' + this.rate +
      '&maxfps=' + (options.maxfps || 5) +
      '&replay=' + (options.replay || 'none') +
      '&connkey=' + this.connKey;

    if (options.frame) {
      src += '&frame=' + options.frame;
    }
    if (options.time) {
      src += '&time=' + options.time;
    }

    // Auth
    if (typeof auth_relay !== 'undefined' && auth_relay) {
      src += '&' + auth_relay;
    }

    // Use a DOM <img> element for MJPEG reception. Browsers natively
    // update a DOM <img> with each frame from a multipart/x-mixed-replace
    // response, but a detached Image() object does not reliably trigger
    // onload per frame.  We position it off-screen and draw from it to
    // the canvas on a requestAnimationFrame loop.
    if (!this.img) {
      this.img = document.createElement('img');
      this.img.style.cssText = 'position:absolute;left:-9999px;top:-9999px;' +
        'width:1px;height:1px;visibility:hidden;';
      document.body.appendChild(this.img);
    }

    var self = this;

    this.img.onerror = function() {
      console.warn('EventStream: MJPEG stream error for event ' +
        self.currentEventId + ' (monitor ' + self.monitorId + ')');
      self.streamCmdTimer = clearInterval(self.streamCmdTimer);
      if (self.rafId) {
        cancelAnimationFrame(self.rafId);
        self.rafId = null;
      }
      // Attempt recovery — zms likely died
      self.recover();
    };

    // onload fires once when the first MJPEG frame arrives, confirming
    // the zms process is running and the command socket is ready.
    this.img.onload = function() {
      // Successful frame — reset error counter
      self.consecutiveErrors = 0;
      self.recoveryDelay = 1000;

      if (!self.streamCmdTimer) {
        self.streamCmdQuery();
        self.streamCmdTimer = setInterval(
            self.streamCmdQuery.bind(self), self.statusInterval
        );
      }
    };

    // Start the rAF draw loop — draws whenever the browser has
    // decoded a new MJPEG frame into the img element.
    this.startDrawLoop();

    // Setting src starts the MJPEG connection
    this.img.src = src;
    this.started = true;
  };

  // -------------------------------------------------------------------------
  // stop() — Stop the current stream
  // -------------------------------------------------------------------------

  this.stop = function() {
    if (this.recoveryTimer) {
      clearTimeout(this.recoveryTimer);
      this.recoveryTimer = null;
    }

    if (!this.started) return;

    this.streamCommand(CMD_QUIT);
    this.streamCmdTimer = clearInterval(this.streamCmdTimer);

    if (this.rafId) {
      cancelAnimationFrame(this.rafId);
      this.rafId = null;
    }

    if (this.img) {
      this.img.onload = null;
      this.img.onerror = null;
      this.img.src = '';
      if (this.img.parentNode) {
        this.img.parentNode.removeChild(this.img);
      }
      this.img = null;
    }

    this.started = false;
    this.paused = false;
    this.connKey = null;
    this.streamCmdParms.connkey = null;
    this.consecutiveErrors = 0;
    this.recoveryDelay = 1000;
  };

  // -------------------------------------------------------------------------
  // recover() — Attempt to restart after zms death
  // -------------------------------------------------------------------------

  this.recover = function() {
    this.consecutiveErrors++;

    if (this.consecutiveErrors > this.maxRecoveryAttempts) {
      console.error('EventStream: max recovery attempts reached for monitor ' +
        this.monitorId + ', giving up');
      if (this.onError) this.onError('Stream recovery failed');
      return;
    }

    console.warn('EventStream: recovery attempt ' + this.consecutiveErrors +
      '/' + this.maxRecoveryAttempts + ' for monitor ' + this.monitorId);

    var self = this;
    var eventId = this.currentEventId;
    var opts = Object.assign({}, this.lastOptions || {});
    opts.rate = this.rate;

    // Clean up old state without sending CMD_QUIT (zms is already dead)
    this.streamCmdTimer = clearInterval(this.streamCmdTimer);
    if (this.rafId) {
      cancelAnimationFrame(this.rafId);
      this.rafId = null;
    }
    if (this.img) {
      this.img.onload = null;
      this.img.onerror = null;
      this.img.src = '';
      if (this.img.parentNode) {
        this.img.parentNode.removeChild(this.img);
      }
      this.img = null;
    }
    this.started = false;
    this.connKey = null;
    this.streamCmdParms.connkey = null;

    // Delay before restarting — exponential backoff
    this.recoveryTimer = setTimeout(function() {
      self.recoveryTimer = null;
      self.start(eventId, opts);
    }, this.recoveryDelay);

    this.recoveryDelay = Math.min(this.recoveryDelay * 2, 10000);
  };

  // -------------------------------------------------------------------------
  // seek(offset) — Seek within the current event (seconds from start)
  // -------------------------------------------------------------------------

  this.seek = function(offset) {
    if (!this.started) return;
    this.streamCommand({command: CMD_SEEK, offset: offset});
  };

  // -------------------------------------------------------------------------
  // seekToTime(epochSecs) — Seek by wall-clock time
  // -------------------------------------------------------------------------

  this.seekToTime = function(epochSecs) {
    if (!this.started || !this.status) return;
    // status.event gives us the current event ID; we need the event's
    // start time to compute an offset.  If the caller hasn't provided
    // event metadata we fall back to duration-based estimation.
    //
    // For montagereview integration the caller will typically have the
    // event start time available in the global `events` object.
    var eventStartSecs = null;
    if (typeof events !== 'undefined' && events[this.currentEventId]) {
      eventStartSecs = events[this.currentEventId].StartTimeSecs;
    }
    if (eventStartSecs) {
      var offset = epochSecs - eventStartSecs;
      if (offset < 0) offset = 0;
      this.seek(offset);
    }
  };

  // -------------------------------------------------------------------------
  // setRate(rate) — Change playback rate (100 = 1x realtime)
  // -------------------------------------------------------------------------

  this.setRate = function(rate) {
    this.rate = rate;
    if (!this.started) return;
    this.streamCommand({command: CMD_VARPLAY, rate: rate});
  };

  // -------------------------------------------------------------------------
  // pause() / play()
  // -------------------------------------------------------------------------

  this.pause = function() {
    if (!this.started) return;
    this.paused = true;
    this.streamCommand(CMD_PAUSE);
  };

  this.play = function() {
    if (!this.started) return;
    this.paused = false;
    this.streamCommand(CMD_PLAY);
  };

  // -------------------------------------------------------------------------
  // setScale(scale) — Change the stream scale
  // -------------------------------------------------------------------------

  this.setScale = function(scale) {
    this.scale = scale;
    if (!this.started) return;
    this.streamCommand({command: CMD_SCALE, scale: scale});
  };

  // -------------------------------------------------------------------------
  // switchEvent(eventId, options) — Switch to a different event
  // -------------------------------------------------------------------------

  this.switchEvent = function(eventId, options) {
    if (this.recoveryTimer) {
      clearTimeout(this.recoveryTimer);
      this.recoveryTimer = null;
    }

    if (this.started) {
      // Tell current zms to exit
      this.streamCommand(CMD_QUIT);
      this.streamCmdTimer = clearInterval(this.streamCmdTimer);
      if (this.rafId) {
        cancelAnimationFrame(this.rafId);
        this.rafId = null;
      }
      if (this.img) {
        this.img.onload = null;
        this.img.onerror = null;
        this.img.src = '';
        if (this.img.parentNode) {
          this.img.parentNode.removeChild(this.img);
        }
        this.img = null;
      }
      this.started = false;
      this.connKey = null;
      this.streamCmdParms.connkey = null;
    }

    // Reset recovery state for fresh event
    this.consecutiveErrors = 0;
    this.recoveryDelay = 1000;

    // Brief delay to let the old zms process clean up, then start fresh
    var self = this;
    setTimeout(function() {
      self.start(eventId, options);
    }, 200);
  };

  // -------------------------------------------------------------------------
  // streamCommand(command) — Send a command to zms via AJAX
  // -------------------------------------------------------------------------

  this.streamCommand = function(command) {
    if (!this.started) {
      return;
    }
    var params = Object.assign({}, this.streamCmdParms);
    if (typeof command === 'object') {
      for (var key in command) {
        if (command.hasOwnProperty(key)) params[key] = command[key];
      }
    } else {
      params.command = command;
    }
    this.streamCmdReq(params);
  };

  // -------------------------------------------------------------------------
  // streamCmdReq(params) — Low-level AJAX to the command socket
  // -------------------------------------------------------------------------

  this.streamCmdReq = function(params) {
    var self = this;
    this.ajaxQueue = jQuery.ajaxQueue({
      url: this.url + (auth_relay ? '?' + auth_relay : ''),
      xhrFields: {withCredentials: true},
      data: params,
      dataType: 'json'
    })
        .done(function(respObj) {
          self.getStreamCmdResponse(respObj);
        })
        .fail(function(jqXHR, textStatus) {
          if (textStatus === 'abort') return;
          console.warn('EventStream: AJAX failed for monitor ' +
            self.monitorId + ': ' + textStatus);
          // AJAX failure likely means zms has died (socket gone)
          self.recover();
        });
  };

  // -------------------------------------------------------------------------
  // streamCmdQuery() — Periodic CMD_QUERY for status updates
  // -------------------------------------------------------------------------

  this.streamCmdQuery = function() {
    if (this.started) {
      var params = Object.assign({}, this.streamCmdParms);
      params.command = CMD_QUERY;
      this.streamCmdReq(params);
    }
  };

  // -------------------------------------------------------------------------
  // getStreamCmdResponse(respObj) — Handle CMD_QUERY / command responses
  // -------------------------------------------------------------------------

  this.getStreamCmdResponse = function(respObj) {
    if (!respObj) return;

    if (respObj.result === 'Error' || respObj.result === 'Err') {
      console.warn('EventStream: command error for monitor ' +
        this.monitorId);
      // Error response means stream.php couldn't talk to zms — recover
      this.recover();
      return;
    }

    // Successful response — reset error counter
    this.consecutiveErrors = 0;
    this.recoveryDelay = 1000;

    if (!respObj.status) return;

    this.status = respObj.status;

    // Update auth hash if the server sent a fresh one
    if (this.status.auth) {
      if (typeof auth_hash !== 'undefined' && this.status.auth !== auth_hash) {
        auth_hash = this.status.auth;
      }
      if (typeof auth_relay !== 'undefined' && this.status.auth_relay) {
        auth_relay = this.status.auth_relay;
      }
    }

    // Track paused state from server
    if (this.status.paused !== undefined) {
      this.paused = !!this.status.paused;
    }

    // Notify consumer
    if (this.onStatus) {
      this.onStatus(this.status);
    }
  };

  // -------------------------------------------------------------------------
  // startDrawLoop() — rAF loop that copies the MJPEG img to the canvas
  // -------------------------------------------------------------------------

  this.startDrawLoop = function() {
    var self = this;
    function loop() {
      if (!self.started) return;
      self.drawFrame();
      self.rafId = requestAnimationFrame(loop);
    }
    this.rafId = requestAnimationFrame(loop);
  };

  // -------------------------------------------------------------------------
  // drawFrame() — Draw the current MJPEG frame to the canvas
  // -------------------------------------------------------------------------

  this.drawFrame = function() {
    if (!this.canvas || !this.img) return;
    // Only draw if the img has decoded at least one frame
    if (!this.img.naturalWidth) return;
    var ctx = this.canvas.getContext('2d');
    ctx.drawImage(this.img, 0, 0, this.canvas.width, this.canvas.height);
    if (this.onFrameDrawn) this.onFrameDrawn(this.canvas);
  };
}
