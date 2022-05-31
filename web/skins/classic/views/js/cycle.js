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
  monitor = new MonitorStream(monitorData[monIdx]);
  monitor.setBottomElement(document.getElementById('buttons'));
  monitor.setScale($j('#scale').val(), $j('#width').val(), $j('#height').val());
  monitor.start();
}

function changeSize() {
  var width = $j('#width').val();
  var height = $j('#height').val();
  $j('#scale').val('0');
  setCookie('zmCycleScale', '0', 3600);
  setCookie('zmCycleWidth', width, 3600);
  setCookie('zmCycleHeight', height, 3600);
  monitor.setScale($j('#scale').val(), $j('#width').val(), $j('#height').val());
} // end function changeSize()

function changeScale() {
  var scale = $j('#scale').val();
  $j('#width').val('auto');
  $j('#height').val('auto');
  setCookie('zmCycleScale', scale, 3600);
  setCookie('zmCycleWidth', 'auto', 3600);
  setCookie('zmCycleHeight', 'auto', 3600);
  monitor.setScale($j('#scale').val(), $j('#width').val(), $j('#height').val());
} // end function changeScale()

$j(document).ready(initCycle);
