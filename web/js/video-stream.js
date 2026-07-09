import {VideoRTC} from './video-rtc.js';

/**
 * This is example, how you can extend VideoRTC player for your app.
 * Also you can check this example: https://github.com/AlexxIT/WebRTC
 */
class VideoStream extends VideoRTC {
    set divMode(value) {
      const monitor = this.closest('[id ^= "monitor"]');
      if (monitor) {
        const modeEl = monitor.querySelector('.stream-info-mode');
        const statusEl = monitor.querySelector('.stream-info-status');
        if (modeEl) modeEl.innerText = 'Go2RTC ' + value;
        if (statusEl) statusEl.innerText = '';
      } else {
        console.log("NO monitor div");
      }
      this.currentMode = value.toUpperCase();
      this.setAttribute('current-mode', this.currentMode);
    }

    set divError(value) {
        const monitor = this.closest('[id ^= "monitor"]');
        if (monitor) {
          const modeEl = monitor.querySelector('.stream-info-mode');
          const statusEl = monitor.querySelector('.stream-info-status');
          if (modeEl) {
            const state = modeEl.innerText;
            if (state !== 'Go2RTC loading') return;
            modeEl.innerText = 'Go2RTC error';
          }
          if (statusEl) statusEl.innerText = 'Go2RTC ' + value;
        } else {
          console.log("NO monitor div");
        }
        this.setAttribute('current-mode', 'ERROR');
        this.currentMode = 'ERROR';
    }

    /**
     * Custom GUI
     */
    oninit() {
        console.debug('stream.oninit');
        this.visibilityCheck = false;
        super.oninit();
    }

    onplay() {
        const container = document.getElementById('monitor-thumb-overlay');
        const monitorStream = getMonitorStream(stringToNumber(this.id));
        if (monitorStream) {
            monitorStream.streamStartTime = (Date.now() / 1000).toFixed(2);
            monitorStream.resetCountStreamErrors(this.activePlayer);
        }

        if (container) {
            const dimensions = calculateOverlayDimensions(this.video);
            if (dimensions) {
                container.style.width = dimensions.width+'px';
                container.style.height = dimensions.height+'px';
            }
        }
        super.onplay();
    }

    onconnect() {
        console.debug('stream.onconnect');
        const result = super.onconnect();
        if (result) this.divMode = 'loading';
        return result;
    }

    ondisconnect() {
        console.debug('stream.ondisconnect');
        super.ondisconnect();
    }

    connectedCallback() {
        console.debug('stream.connectedCallback');
        super.connectedCallback(); 
    }

    onopen() {
        console.debug('stream.onopen');
        const result = super.onopen();
        if (result !== undefined) {
            // An available player mode should return.An available player mode should return.
            if ((!("length" in result)) || (("length" in result) && !result.length)) {
                this.errorHandling(this.mode, `Playback using Go2RTC in mode "${this.mode}" is not possible.`);
            }
        } else {
            // TODO: Something needs to be done, but what exactly?
            console.warn(`stream.onopen returned RESULT=UNDEFINED for monitor with ID=${stringToNumber(this.id)} and mode="${this.mode}"`);
        }

        this.onmessage['stream'] = msg => {
            console.debug('stream.onmessge', msg);
            switch (msg.type) {
                case 'error':
                    this.divError = msg.value;
                    if (msg.value.indexOf("webrtc") > -1) {
                        this.errorHandling("webrtc");
                    } else if (msg.value.indexOf("mse") > -1) {
                        this.errorHandling("mse");
                    } else if (msg.value.indexOf("hls") > -1) {
                        this.errorHandling("hls");
                    }
                    break;
                case 'mse':
                case 'hls':
                case 'mp4':
                case 'mjpeg':
                    this.divMode = msg.type.toUpperCase();
                    const monitorStream = getMonitorStream(stringToNumber(this.id));
                    if (monitorStream) {
                        monitorStream.player = "go2rtc_" + msg.type;
                    }
                    break;
                case 'webrtc/candidate':
                    if (!this.waitingWebrtcPlayback) {
                        // webrtc/candidate can be multiple times, we only need one
                        this.waitingWebrtcPlayback = setTimeout(function(self) {
                            self.errorHandling("webrtc", 'No video - trying next player');
                        }, 3000, this);
                    }
                    break;
            }
        };
        return result;
    }

    onclose() {
        console.debug('stream.onclose');
        return super.onclose();
    }

    onerror(ev) {
        clearTimeout(this.waitingWebrtcPlayback);
        this.waitingWebrtcPlayback = null;
        console.debug('stream.onerror', ev);
        const monitorStream = getMonitorStream(stringToNumber(this.id));
        if (monitorStream && monitorStream.started) {
            this.errorHandling(this.mode);
        }
        super.onerror(ev);
    }

    onpcvideo(ev) {
        clearTimeout(this.waitingWebrtcPlayback);
        this.waitingWebrtcPlayback = null;
        console.debug('stream.onpcvideo');
        super.onpcvideo(ev);

        if (this.pcState !== WebSocket.CLOSED) {
            this.divMode = 'RTC';
        }
    }

    pause() {
        this.video.pause();
    }
    close() {
        this.video.pause();
    }

    errorHandling(currentMode, message = null) {
        const monitorStream = getMonitorStream(stringToNumber(this.id));
        if (monitorStream) {
            monitorStream.player = "go2rtc_" + currentMode;
            monitorStream.streamErrorRegistration();
            monitorStream.restart(monitorStream.currentChannelStream);
            monitorStream.writeTextInfoBlock("Error");
            if (message) monitorStream.showText(message);
        }

        this.dispatchEvent(
            new CustomEvent('go2rtc.events.error', {
                detail: {
                    mode: currentMode,
                    message: message
                },
                bubbles: true,
                cancelable: false
            })
        );
    }
}

customElements.define('video-stream', VideoStream);
