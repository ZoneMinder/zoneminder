var server;
var janus = null;
var streaming2;
var intervalId;
const pauseBtn = $j('#pauseBtn');
const playBtn = $j('#playBtn');
var monitor;

function nextCycleView() {
  window.location.replace('?view=cycle&mid='+nextMid+'&mode='+mode, cycleRefreshTimeout);
}

function cyclePause() {
  clearInterval(intervalId);
  pauseBtn.prop('disabled', true);
  playBtn.prop('disabled', false);
}

function cycleStart() {
  intervalId = setInterval(nextCycleView, cycleRefreshTimeout);
  pauseBtn.prop('disabled', false);
  playBtn.prop('disabled', true);
}

function cycleNext() {
  monIdx ++;
  if ( monIdx >= monitorData.length ) {
    monIdx = 0;
  }
  if ( !monitorData[monIdx] ) {
    console.log('No monitorData for ' + monIdx);
  }

  window.location.replace('?view=cycle&mid='+monitorData[monIdx].id+'&mode='+mode, cycleRefreshTimeout);
}

function cyclePrev() {
  monIdx --;
  if ( monIdx < 0 ) {
    monIdx = monitorData.length - 1;
  }
  if ( !monitorData[monIdx] ) {
    console.log('No monitorData for ' + monIdx);
  }

  window.location.replace('?view=cycle&mid='+monitorData[monIdx].id+'&mode='+mode, cycleRefreshTimeout);
}

function initCycle() {
  intervalId = setInterval(nextCycleView, cycleRefreshTimeout);

  if (monitorData[monIdx].janusEnabled) {
    if (ZM_JANUS_PATH) {
      server = ZM_JANUS_PATH;
    } else if (window.location.protocol=='https:') {
      // Assume reverse proxy setup for now
      server = "https://" + window.location.hostname + "/janus";
    } else {
      server = "http://" + window.location.hostname + ":8088/janus";
    }
    opaqueId = "streamingtest-"+Janus.randomString(12);
    Janus.init({debug: "all", callback: function() {
      janus = new Janus({
        server: server,
        success: function() {
          janus.attach({
            plugin: "janus.plugin.streaming",
            opaqueId: opaqueId,
            success: function(pluginHandle) {
              streaming2 = pluginHandle;
              var body = {"request": "watch", "id": monitorData[monIdx].id};
              streaming2.send({"message": body});
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
                  const status = result["status"];
                  console.log(status);
                }
              } else if (msg["error"] !== undefined && msg["error"] !== null) {
                Janus.debug(msg["error"]);
                return;
              }
              if (jsep !== undefined && jsep !== null) {
                Janus.debug("Handling SDP as well...");
                Janus.debug(jsep);
                if ((navigator.userAgent.toLowerCase().indexOf('firefox') > -1) && (jsep["sdp"].includes("420029"))) { //because firefox devs are stubborn
                  jsep["sdp"] = jsep["sdp"].replace("420029", "42e01f");
                }
                // Offer from the plugin, let's answer
                streaming2.createAnswer({
                  jsep: jsep,
                  // We want recvonly audio/video and, if negotiated, datachannels
                  media: {audioSend: false, videoSend: false, data: true},
                  success: function(jsep) {
                    Janus.debug("Got SDP!");
                    Janus.debug(jsep);
                    var body = {"request": "start"};
                    streaming2.send({"message": body, "jsep": jsep});
                  },
                  error: function(error) {
                    Janus.error("WebRTC error:", error);
                  }
                });
              }
            }, //onmessage function
            onremotestream: function(stream) {
              Janus.debug(" ::: Got a remote track :::");
              Janus.debug(stream);
              Janus.attachMediaStream(document.getElementById("liveStream" + monitorData[monIdx].id), stream);
              document.getElementById("liveStream" + monitorData[monIdx].id).play();
            }
          });// attach
        } //Success functio
      }); //new Janus
    }}); //janus.init callback
  } //if janus

  monitor = new MonitorStream(monitorData[monIdx]);
  applyScale();
}

function changeSize() {
  var width = $j('#width').val();
  var height = $j('#height').val();

  // Scale the frame
  monitor_frame = $j('#imageFeed'+monitor.id);
  if ( !monitor_frame ) {
    console.log('Error finding frame');
    return;
  }
  let scale = 100;
  if ( width != 'auto' && width != '100%') {
    scale = parseInt(100*parseInt(width) / monitorData[monIdx].width);
  }
  monitor_frame.css('width', width);
  monitor_frame.css('height', height);
  if (scale > 100) scale = 100;
  if (scale <= 0) scale = 100;

  $j('#scale').val('0');
  setCookie('zmCycleScale', '0', 3600);
  setCookie('zmCycleWidth', width, 3600);
  setCookie('zmCycleHeight', height, 3600);
  applyScale();
  monitor.setStreamScale(scale);
} // end function changeSize()

function changeScale() {
  var scale = $j('#scale').val();
  $j('#width').val('auto');
  $j('#height').val('auto');
  setCookie('zmCycleScale', scale, 3600);
  setCookie('zmCycleWidth', 'auto', 3600);
  setCookie('zmCycleHeight', 'auto', 3600);
  applyScale();
} // end function changeScale()

function applyScale() {
  var scale = $j('#scale').val();
  var width = $j('#width').val();
  var height = $j('#height').val();

  // Scale the frame
  monitor_frame = $j('#imageFeed'+monitor.id);
  if ( !monitor_frame ) {
    console.log('Error finding frame');
    return;
  }

  let newWidth;
  let newHeight;
  if ( scale != '0' && scale != '' && scale != 'auto' ) {
    newWidth = (( monitorData[monIdx].width * scale ) / SCALE_BASE)+'px';
    newHeight = (( monitorData[monIdx].height * scale ) / SCALE_BASE)+'px';
  } else {
    var newSize = scaleToFit(monitorData[monIdx].width, monitorData[monIdx].height, monitor_frame, $j('#buttons'));
    if (width != 'auto' || height != 'auto') {
      newWidth = width;
      newHeight = height;
    } else {
      newWidth = newSize.width+'px';
      newHeight = newSize.height+'px';
    }
    scale = newSize.autoScale;
  }
  monitor_frame.width(newWidth);
  monitor_frame.height(newHeight);
  const monitor_image = $j('#imageFeed'+monitor.id + ' img');
  monitor_image.width('100%');
  monitor_image.height(height);

  monitor.setStreamScale(scale);
} // end function changeScale()

$j(document).ready(initCycle);
