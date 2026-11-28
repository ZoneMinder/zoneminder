var server;
var janus = null;
var streaming2;
var intervalId;
const pauseBtn = $j('#pauseBtn');
const playBtn = $j('#playBtn');
var monitor;

function cycleIsPaused() {
  try {
    return sessionStorage.getItem('zmCyclePaused') === '1';
  } catch {
    return false;
  }
}

function cycleSetPaused(paused) {
  try {
    if (paused) {
      sessionStorage.setItem('zmCyclePaused', '1');
    } else {
      sessionStorage.removeItem('zmCyclePaused');
    }
  } catch {
    // sessionStorage unavailable; the pause just does not persist.
  }
}

function nextCycleView() {
  window.location.replace(zmAuth.appendTo('?view=cycle&mid='+nextMid+'&mode='+mode), cycleRefreshTimeout);
}

function cyclePause() {
  clearInterval(intervalId);
  cycleSetPaused(true);
  pauseBtn.prop('disabled', true);
  playBtn.prop('disabled', false);
}

function cycleStart() {
  // The play button can be reached without a pause in between, and the old id
  // is unrecoverable once overwritten. refs #5135
  clearInterval(intervalId);
  cycleSetPaused(false);
  // A refresh time of 0 means off.
  if (cycleRefreshTimeout > 0) {
    intervalId = setInterval(nextCycleView, cycleRefreshTimeout);
  }
  pauseBtn.prop('disabled', false);
  playBtn.prop('disabled', true);
}

function cycleNext() {
  if (!monitorData.length) return;
  // Do not move monIdx: a second click before the page unloads would skip a monitor.
  let idx = Number(monIdx) + 1;
  if ( idx >= monitorData.length ) {
    idx = 0;
  }
  if ( !monitorData[idx] ) {
    console.log('No monitorData for ' + idx);
  }

  window.location.replace(zmAuth.appendTo('?view=cycle&mid='+monitorData[idx].id+'&mode='+mode), cycleRefreshTimeout);
}

function cyclePrev() {
  if (!monitorData.length) return;
  let idx = Number(monIdx) - 1;
  if ( idx < 0 ) {
    idx = monitorData.length - 1;
  }
  if ( !monitorData[idx] ) {
    console.log('No monitorData for ' + idx);
  }

  window.location.replace(zmAuth.appendTo('?view=cycle&mid='+monitorData[idx].id+'&mode='+mode), cycleRefreshTimeout);
}

function initCycle() {
  if (!monitorData.length) return;
  if (cycleIsPaused()) {
    pauseBtn.prop('disabled', true);
    playBtn.prop('disabled', false);
  } else if (cycleRefreshTimeout > 0) {
    // A refresh time of 0 means off.
    intervalId = setInterval(nextCycleView, cycleRefreshTimeout);
  }
  monitor = new MonitorStream(monitorData[monIdx]);
  monitor.setScale($j('#scale').val(), $j('#width').val(), $j('#height').val());
  monitor.start();
}

function changeSize() {
  if (!monitor) return;
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
  setCookie('zmCycleScale', '0');
  setCookie('zmCycleWidth', width);
  setCookie('zmCycleHeight', height);
  applyScale();
  monitor.setStreamScale(scale);
} // end function changeSize()

function changeScale() {
  var scale = $j('#scale').val();
  $j('#width').val('auto');
  $j('#height').val('auto');
  setCookie('zmCycleScale', scale);
  setCookie('zmCycleWidth', 'auto');
  setCookie('zmCycleHeight', 'auto');
  applyScale();
} // end function changeScale()

function applyScale() {
  if (!monitor) return;
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
