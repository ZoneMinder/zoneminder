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
        super.oninit();
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

    onopen() {
        console.debug('stream.onopen');
        const result = super.onopen();

        this.onmessage['stream'] = msg => {
            console.debug('stream.onmessge', msg);
            switch (msg.type) {
                case 'error':
                    this.divError = msg.value;
                    break;
                case 'mse':
                case 'hls':
                case 'mp4':
                case 'mjpeg':
                    this.divMode = msg.type.toUpperCase();
                    this.getTracksFromStream();
                    break;
            }
        };

        return result;
    }

    onclose() {
        console.debug('stream.onclose');
        return super.onclose();
    }

    onpcvideo(ev) {
        console.debug('stream.onpcvideo');
        super.onpcvideo(ev);

        if (this.pcState !== WebSocket.CLOSED) {
            this.divMode = 'RTC';
        }
        this.getTracksFromStream();
    }

  pause() {
    this.video.pause();
  }
  close() {
    this.video.pause();
  }

    getTracksFromStream() {
        const liveStream = this.closest('[id ^= "liveStream"]');
        if (liveStream) {
            const monitorStream = getMonitorStream(stringToNumber(liveStream.id));
            if (monitorStream) {
                setTimeout(function() { //It takes time for full playback to complete, otherwise you may not receive the tracks. This is especially true for MSE.
                    getTracksFromStream(monitorStream);
                }, 500);
            }
        }
    }
}

customElements.define('video-stream', VideoStream);
