var intervalId;
var pauseBtn = $j('#pauseBtn');
var playBtn = $j('#playBtn');

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
  var scale = $j('#scale').val();
  if ( scale == '0' || scale == 'auto' ) applyScale();
}

function changeSize() {
  var width = $j('#width').val();
  var height = $j('#height').val();

  // Scale the frame
  monitor_frame = $j('#imageFeed');
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

  /* Stream could be an applet so can't use moo tools */
  var streamImg = document.getElementById('liveStream'+monitorData[monIdx].id);
  if ( streamImg ) {
    if ( streamImg.nodeName == 'IMG' ) {

      var src = streamImg.src;
      streamImg.src = '';
      if (scale) src = src.replace(/scale=\d+/i, 'scale='+scale);
      src = src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
      streamImg.src = src;
    }
    streamImg.style.width = width ? width : null;
    streamImg.style.height = height ? height : null;
  } else {
    console.log('Did not find liveStream'+monitorData[monIdx].id);
  }
  $j('#scale').val('0');
  setCookie('zmCycleScale', '0', 3600);
  setCookie('zmCycleWidth', width, 3600);
  setCookie('zmCycleHeight', height, 3600);
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
  monitor_frame = $j('#imageFeed');
  if ( !monitor_frame ) {
    console.log('Error finding frame');
    return;
  }

  if ( scale != '0' && scale != '' && scale != 'auto' ) {
    var newWidth = ( monitorData[monIdx].width * scale ) / SCALE_BASE;
    var newHeight = ( monitorData[monIdx].height * scale ) / SCALE_BASE;
    if ( newWidth ) {
      monitor_frame.css('width', newWidth+'px');
    }
    if ( newHeight ) {
      monitor_frame.css('height', newHeight+'px');
    }
  } else {

    //var bottomEl = streamMode == 'stills' ? $j('#eventImageNav') : $j('#replayStatus');
    var newSize = scaleToFit(monitorData[monIdx].width, monitorData[monIdx].height, monitor_frame, $j('#buttons'));
    if (width != 'auto' || height != 'auto') {
      newWidth = width;
      newHeight = height;
    } else {
      newWidth = newSize.width+'px';
      newHeight = newSize.height+'px';
    }
    autoScale = newSize.autoScale;
    monitor_frame.width(newWidth);
    monitor_frame.height(newHeight);
  }

  /*Stream could be an applet so can't use moo tools*/
  var streamImg = $j('#liveStream'+monitorData[monIdx].id)[0];
  if ( !streamImg ) {
    console.log("Did not find liveStream"+monitorData[monIdx].id);
    return;
  }

  if ( streamImg.nodeName == 'IMG' ) {
    var src = streamImg.src;
    streamImg.src = '';

    //src = src.replace(/rand=\d+/i,'rand='+Math.floor((Math.random() * 1000000) ));
    src = src.replace(/scale=[\.\d]+/i, 'scale='+scale);
    // zms doesn't actually use width&height
    streamImg.src = src;
  }

  //if ( scale != '0' && scale != '' && scale != 'auto' ) {
    streamImg.style.width = newWidth;
    streamImg.style.height = newHeight;
  //} else {
    //streamImg.style.width = '100%';
    //streamImg.style.height = 'auto';
  //}
} // end function changeScale()

$j(document).ready(initCycle);
