/*
* Support https://audiomotion.dev/#/
* IgorA100 2026
*/

var AudioMotionAnalyzer = null;

function checkAudioMotionEnabled() {
  return (typeof AUDIO_MOTION_ENABLED !== 'undefined' && AUDIO_MOTION_ENABLED === 'true');
}

if (checkAudioMotionEnabled()) {
  import('../assets/audioMotion-analyzer/src/audioMotion-analyzer.js').then(module => {
    if (module.AudioMotionAnalyzer) {
      AudioMotionAnalyzer = module.AudioMotionAnalyzer;
    } else {
      AudioMotionAnalyzer = window.AudioMotionAnalyzer;
    }
  });
}
//import {AudioMotionAnalyzer} from '../assets/audioMotion-analyzer/src/audioMotion-analyzer.js';

export class _AudioMotionAnalyzer extends HTMLElement {
  constructor() {
    super();
    this.audioMotion = null; // AudioMotionAnalyzer object
    this.initCompleted = false;
    this.getTracksFromStreamTimeout = 20000;
    if (currentView == 'watch' || currentView == 'event') {
      this.maxFPS = 30;
      this.loRes = false;
    } else {
      this.maxFPS = 5; // We'll limit the processing speed, as this affects the client's CPU load.
      this.loRes = true;
    }
    this.mid = stringToNumber(this.id);
    this.gainNode = null; // This is required for controlling the signal level, as we're using a separate stream. This is because when using WebRTC, we can't get the audio stream from <video>.
    [this.infoIsAudio, this.infoIsVideo] = [1,2].map(() => document.createElement('i'));
    this.infoIsAudio.setAttribute('id',"ama_is-audio" + this.mid);
    this.infoIsAudio.setAttribute('class','material-icons md-18');
    this.infoIsAudio.innerText = 'music_off';
    this.infoIsVideo.setAttribute('id',"ama_is-video" + this.mid);
    this.infoIsVideo.setAttribute('class','material-icons md-18');
    this.infoIsVideo.innerText = 'videocam_off';
    this.handlerEventListener = {};
    this.currentPlayer = null; // The current player during initialization
    this.currentMediaStream = null; // The current MediaStream during initialization

    this.hide();
  }

  connectedCallback() {  
    //console.log('connectedCallback');  
  }  

  disconnectedCallback() {  
    //console.log('disconnectedCallback');  
  }

  hide = function() {
    this.classList.add('hidden');
  };

  show = function() {
    this.classList.remove('hidden');
  };

  changeIconIsAudio = function(mode) {
    const info = this.getInfoBlock();
    if (info) {
      let isAudio = info.querySelector("#ama_is-audio" + this.mid);
      if (!isAudio) {
        info.appendChild(this.infoIsAudio);
        isAudio = info.querySelector("#ama_is-audio" + this.mid);
      }
      if (isAudio) isAudio.innerHTML = (mode == 'off') ? 'music_off' : 'music_note';
    }
  };

  changeIconIsVideo = function(mode) {
    const info = this.getInfoBlock();
    if (info) {
      let isVideo = info.querySelector("#ama_is-video" + this.mid);
      if (!isVideo) {
        info.appendChild(this.infoIsVideo);
        isVideo = info.querySelector("#ama_is-video" + this.mid);
      }
      if (isVideo) isVideo.innerHTML = (mode == 'off') ? 'videocam_off' : 'videocam';
    }
  };

  init = async function() {
    //console.trace("<<<<<RUN_INIT>>>", this.mid, "--", this.id, this.audioMotion);
    const streamPlayer = this.getActivePlayer();
    const monitorStream = getMonitorStream(this.mid);
    const mediaStream = monitorStream.mediaStream;
    const audioTrack = monitorStream.audioTrack;

    if (this.currentPlayer !== null && streamPlayer === this.currentPlayer && this.currentMediaStream !== null && mediaStream.id === this.currentMediaStream.id) {
      if (this.audioMotion && this.gainNode && mediaStream && mediaStream.active && audioTrack && !this.audioMotion.isOn) {
        this.audioMotion.start();
        return;
      } else {
        console.log(`AudioMotion reinitialization for the same player "${streamPlayer}" is not allowed for monitor ID=${this.mid}`);
        return;
      }
    }

    this.waitingGetTracksFromStream = true;
    this.initCompleted = true;

    if (this.audioMotion) {
      this.destroy();
    }
    this.currentPlayer = streamPlayer;
    this.currentMediaStream = monitorStream.mediaStream;
    this.changeIconIsVideo('off');
    this.changeIconIsAudio('off');

    if (!monitorStream.mediaStream) {
      await this.getTracksFromStream(monitorStream);
    }
    this.createMotionAnalyzer();
  }; // END init = function()

  stop = function(force = true) {
    if (this.audioMotion) {
      this.audioMotion.stop();
      this.disconnectMediaStreamSource();
      this.initCompleted = false;
      this.currentPlayer = this.currentMediaStream = null;
      this.changeIconIsAudio('off');
      this.changeIconIsVideo('off');
      manageEventListener.removeEventListener(this.handlerEventListener['volumechange']);
    }
 }; // END stop = function() {

  pause = function() {
    if (this.audioMotion && this.audioMotion.isOn) {
      this.audioMotion.stop();
    }
  };

  start = function() {
    if (this.audioMotion && this.audioMotion.isOn) return;
    const monitorStream = getMonitorStream(this.mid);
    const mediaStream = monitorStream.mediaStream;
    const audioTrack = monitorStream.audioTrack;
    if (this.audioMotion && this.gainNode && mediaStream && mediaStream.active && audioTrack) {
      this.audioMotion.start();
    } else {
      this.init();
    }
  };

  createMotionAnalyzer = function() {
    const mid = this.mid;
    const audioEl = this.getMediaStreamSource();
    const volumeControls = document.getElementById(`volumeControls${mid}`);
    const monitorStream = getMonitorStream(mid)
    if (!monitorStream) {
      console.warn(`Audio visualization. Stream for monitor ID=${mid} not found.`);
      return;
    }

    this.audioMotion = new AudioMotionAnalyzer(
      document.getElementById(`audioVisualization${mid}`),
      {
        //source: audioEl, // main audio source is the HTML <audio> element .webrtc - не работает пока.
        //width: 100%,
        canvas: document.querySelector(`#audioVisualization${mid} canvas`),
        height: 80,
        mode: 2, // This has little impact on performance. The lower the number, the larger the number of bars.
        maxFPS: this.maxFPS,
        loRes: this.loRes, //https://github.com/hvianna/audioMotion-analyzer?tab=readme-ov-file#lores-boolean
        fftSize: 4096, // It has almost no impact on performance. The lower this number, the worse the frequency analysis; at 32, almost all the bars are identical... Optimally, 1024 or more
        alphaBars: true,
        noteLabels: false, // It's not really necessary.
        showScaleX: false, // Removes frequency signatures.
        showScaleY: false,
        overlay: true, // Makes the background transparent.
        bgAlpha: .5, // Background transparency only works with overlay: true.
        ansiBands: true,
        barSpace: .5,
        //channelLayout: 'single',
        channelLayout: 'dual-combined',
        colorMode: 'gradient',
        frequencyScale: 'log',
        gradient: 'classic',
        //ledBars: true,
        //connectSpeakers: false, // Defaults to TRUE
        lumiBars: false,
        maxFreq: 5000,
        minFreq: 125,
        //maxDecibels: -15, // Def = -25
        //minDecibels: -75, // Def = -85
        mirror: 0,
        radial: false,
        //reflexFit: true,
        //reflexRatio: .1,
        //reflexAlpha: .25,
        //reflexBright: 1,
        //linearAmplitude: true,
        linearAmplitude: false,
        //linearBoost: 4, // 4 is the optimal mid-range value, approximately the same as with "linearAmplitude" disabled. Only works when linearAmplitude: true
        showBgColor: true,
        showPeaks: true,
        trueLeds: true
      }
    );
    monitorStream.audioMotion = this;

    this.audioMotion.registerGradient( 'myGradient', {
      bgColor: '#34495e', // background color (optional) - defaults to '#111'
      dir: 'w',           // add this property to create a horizontal gradient (optional)
      colorStops: [       // list your gradient colors in this array (at least one color is required)
        'hsl( 0, 100%, 50% )',        // colors can be defined in any valid CSS format
        { color: 'yellow', pos: .6 }, // in an object, use `pos` to adjust the offset (0 to 1) of a colorStop
        { color: '#0f0', level: .5 }  // use `level` to set the max bar amplitude (0 to 1) to use this color
      ]
    });
    this.audioMotion.setOptions({gradient:"myGradient"});

    if (monitorStream.audioTrack) {
      this.connectToMediaStreamSource();
      // Waiting for canvas to be drawn or stream error
      waitUntil(() => this.waitReadiness()); // TODO We have an observer on the Montage page and that should be enough, I guess... But there is no observer on the "canvas" on the Watch & Event page.
    }

    this.controlMediaStream();
  }; // END createMotionAnalyzer = function()

  destroy = function() {
    if (this.audioMotion) {
      this.stop();
      this.audioMotion.destroy();
    }
  }; // END destroy = function()

  getInfoBlock =  function() {
    let info = document.querySelector('[id ^= "monitorStatus'+this.mid+'"] .stream-info-status-track'); // Watch&Montage page
    if (!info) info = document.querySelector('[id ^= "wrapperEventVideo"] .stream-info-status-track'); // Event page
    return info;
  };

  controlMediaStream = function() {
    const monitorStream = getMonitorStream(this.mid);
    if (!monitorStream.mediaStream) return;
    const volumeControls = document.querySelector('[id ^= "volumeControls'+this.mid+'"]');

    if (monitorStream.videoTrack) {
      this.changeIconIsVideo('on');
    } else {
      this.changeIconIsVideo('off');
    }
    if (monitorStream.audioTrack) {
      this.changeIconIsAudio('on');
      this.show();
      if (volumeControls) volumeControls.classList.remove('disabled');
    } else {
      this.changeIconIsAudio('off');
      this.hide();
      if (volumeControls) volumeControls.classList.add('disabled');
    }
  };

  connectToMediaStreamSource = async function () {
    if (!this.audioMotion) return;
    const audioEl = this.getMediaStreamSource();
    if (!audioEl) {
      console.log(`MediaStreamSource for monitor with ID=${this.mid} not found`);
      return;
    }

    const audioCtx = this.audioMotion.audioCtx;
    const monitorStream = getMonitorStream(this.mid);
    const mediaStream = monitorStream.mediaStream;

    this.disconnectMediaStreamSource();
    this.gainNode = audioCtx.createGain();

    this.handlerEventListener['volumechange'] = manageEventListener.addEventListener(audioEl, 'volumechange', this.listenerVolumechange.bind(null, this));

    if (audioEl.muted) {
      this.gainNode.gain.value = 0;
    } else {
      this.gainNode.gain.value = audioEl.volume;
    }

    if (!mediaStream.active) { // This is especially useful for the Event page during repeat playback.
      await this.getTracksFromStream(monitorStream);
      return;
    }
    const source   = audioCtx.createMediaStreamSource(mediaStream);
    source.connect(this.gainNode);
    this.audioMotion.connectInput(this.gainNode);
    //this.audioMotion.connectOutput(); // This will result in duplicate sound output.
  };

  disconnectMediaStreamSource = function() {
    if (this.audioMotion) {
      this.audioMotion.disconnectOutput();
      this.audioMotion.disconnectInput();
    }
  };

  getTracksFromStream = async function(monitorStream) {
    await waitUntil(() => this.waitingGetTracksFromStream, this.getTracksFromStreamTimeout);
    // Until the previous request completes within "this.getTracksFromStreamTimeout," don't send a new one.
    this.waitingGetTracksFromStream = false;
    await getTracksFromStream(monitorStream);
    this.waitingGetTracksFromStream = true;
  };

  monitorGridRedrawTrigger = function() {
    if (currentView == 'montage' && !changedMonitors.includes(this.mid)) {
      changedMonitors.push(this.mid);
    } else if (currentView == 'watch') {
      updateScale = true;
    } else if (currentView == 'event') {
      changeScale();
    }
  };

  waitReadiness = function() {
    let ready = false;
    const streamNode = this.getMediaStreamSource();
    if (streamNode) {
      const currentMode = streamNode.getAttribute('current-mode'); // This go2RTC indicates either "error" or MSE, RTC, etc.
      const h = (this.querySelector('canvas')) ? this.querySelector('canvas').getBoundingClientRect().height : 0;
      if ((currentMode && -1 !== currentMode.toLowerCase().indexOf('error')) || (this.audioMotion && h > 0)) {
        this.monitorGridRedrawTrigger();
        ready = true;
      }
    }
    return ready;
  }

  listenerVolumechange = function(_this, event){ // Adjust the visualization level according to the stream's volume level
    if (event.target.muted === true) {
      _this.gainNode.gain.value = 0;
    } else {
      _this.gainNode.gain.value = event.target.volume;
    }
  };

  getMediaStreamSource = function() {
    // Go2RTC || RTSP2Web on Watch page || Event page
    return (document.querySelector(`#liveStream${this.mid} video`) || document.getElementById('liveStream'+this.mid)) || document.querySelector(`#videoFeedStream${this.mid} video`);
  };

  getStreamSource = function() {
    return (document.getElementById('liveStream'+this.mid)) || document.querySelector(`#videoFeedStream${this.mid}`);
  };

  getActivePlayer = function() {
    const monitorStream = getMonitorStream(this.mid);
    let currentPlayer = null;
    if (monitorStream) {
      const activePlayer = monitorStream.activePlayer || null;
      currentPlayer = activePlayer;
      if (activePlayer && activePlayer.toLowerCase().indexOf('go2rtc') > -1) { // There will be no active player on the Event page.
        const currentMode = this.getStreamSource().getAttribute('current-mode');
        if (currentMode) currentPlayer = activePlayer + "_" + currentMode;
      }
    }
    return currentPlayer;
  };
} // END CLASS

if (checkAudioMotionEnabled() && (currentView == 'watch' || currentView == 'montage' || currentView == 'event')) {
  customElements.define('audio-motion', _AudioMotionAnalyzer);
}
