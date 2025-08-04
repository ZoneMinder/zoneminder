import {VideoRTC} from './video-rtc.js';

/**
 * This is example, how you can extend VideoRTC player for your app.
 * Also you can check this example: https://github.com/AlexxIT/WebRTC
 */
class VideoStream extends VideoRTC {
    set divMode(value) {
        const modeEl = this.closest('[id ^= "monitor"]').querySelector('.stream-info-mode');
        const statusEl = this.closest('[id ^= "monitor"]').querySelector('.stream-info-status');
        if (modeEl) modeEl.innerText = 'Go2RTC ' + value;
        if (statusEl) statusEl.innerText = '';
        this.setAttribute('current-mode', value.toUpperCase());
        this.currentMode = value.toUpperCase();
    }

    set divError(value) {
        const modeEl = this.closest('[id ^= "monitor"]').querySelector('.stream-info-mode');
        const statusEl = this.closest('[id ^= "monitor"]').querySelector('.stream-info-status');
        if (modeEl) {
          const state = modeEl.innerText;
          if (state !== 'Go2RTC loading') return;
          modeEl.innerText = 'Go2RTC ' + 'error';
        }
        if (statusEl) statusEl.innerText = 'Go2RTC ' + value;
        this.setAttribute('current-mode', value.toUpperCase());
        this.currentMode = value.toUpperCase();
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
    }

  pause() {
    this.video.pause();
  }
  close() {
    this.video.pause();
  }
}

customElements.define('video-stream', VideoStream);
