var periodical_id;

function nextCycleView() {
  window.location.replace('?view=cycle&mid='+nextMid+'&mode='+mode, cycleRefreshTimeout);
}

function cyclePause() {
  $clear(periodical_id);
  $('pauseBtn').disabled = true;
  $('playBtn').disabled = false;
}
function cycleStart() {
  periodical_id = nextCycleView.periodical(cycleRefreshTimeout);
  $('pauseBtn').disabled = false;
  $('playBtn').disabled = true;
}
function cycleNext() {
  monIdx ++;
  if ( monIdx >= monitorData.length ) {
    monIdx = 0;
  }
  if ( !monitorData[monIdx] ) {
    console.log("No monitorData for " + monIdx);
  }

  window.location.replace('?view=cycle&mid='+monitorData[monIdx].id+'&mode='+mode, cycleRefreshTimeout);
}
function cyclePrev() {
  if (monIdx) {
    monIdx -= 1;
  } else {
    monIdx = monitorData.length - 1;
  }

  window.location.replace('?view=cycle&mid='+monitorData[monIdx].id+'&mode='+mode, cycleRefreshTimeout);
}

function initCycle() {
  periodical_id = nextCycleView.periodical(cycleRefreshTimeout);
}

function changeSize() {
  var width = $('width').get('value');
  var height = $('height').get('value');

  // Scale the frame
  monitor_frame = $j('#imageFeed');
  if ( !monitor_frame ) {
    console.log('Error finding frame');
    return;
  }
  if ( width ) {
    monitor_frame.css('width', width);
  }
  if ( height ) {
    monitor_frame.css('height', height);
  }

  /* Stream could be an applet so can't use moo tools */
  var streamImg = $('liveStream'+monitorData[monIdx].id);
  if ( streamImg ) {
    if ( streamImg.nodeName == 'IMG' ) {
      var src = streamImg.src;
      streamImg.src = '';
      console.log(parseInt(width));
      src = src.replace(/width=[\.\d]+/i, 'width='+parseInt(width));
      src = src.replace(/height=[\.\d]+/i, 'height='+parseInt(height));
      src = src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
      streamImg.src = src;
    }
    streamImg.style.width = width ? width : null;
    streamImg.style.height = height ? height : null;
  } else {
    console.log("Did not find liveStream"+monitorData[monIdx].id);
  }
  $('scale').set('value', '');
  Cookie.write('zmCycleScale', '', {duration: 10*365});
  Cookie.write('zmCycleWidth', width, {duration: 10*365});
  Cookie.write('zmCycleHeight', height, {duration: 10*365});
} // end function changeSize()

function changeScale() {
  var scale = $('scale').get('value');
  $('width').set('value', 'auto');
  $('height').set('value', 'auto');
  Cookie.write('zmCycleScale', scale, {duration: 10*365});
  Cookie.write('zmCycleWidth', 'auto', {duration: 10*365});
  Cookie.write('zmCycleHeight', 'auto', {duration: 10*365});
  var newWidth = ( monitorData[monIdx].width * scale ) / SCALE_BASE;
  var newHeight = ( monitorData[monIdx].height * scale ) / SCALE_BASE;

  // Scale the frame
  monitor_frame = $j('#imageFeed');
  if ( !monitor_frame ) {
    console.log('Error finding frame');
    return;
  }

  if ( scale != '0' ) {
    if ( newWidth ) {
      monitor_frame.css('width', newWidth+'px');
    }
    if ( newHeight ) {
      monitor_frame.css('height', newHeight+'px');
    }
  } else {
    monitor_frame.css('width', '100%');
    monitor_frame.css('height', 'auto');
  }
  /*Stream could be an applet so can't use moo tools*/
  var streamImg = $j('#liveStream'+monitorData[monIdx].id)[0];
  if ( streamImg ) {
    if ( streamImg.nodeName == 'IMG' ) {
      var src = streamImg.src;
      streamImg.src = '';

      //src = src.replace(/rand=\d+/i,'rand='+Math.floor((Math.random() * 1000000) ));
      src = src.replace(/scale=[\.\d]+/i, 'scale='+scale);
      if ( scale != '0' ) {
        src = src.replace(/width=[\.\d]+/i, 'width='+newWidth);
        src = src.replace(/height=[\.\d]+/i, 'height='+newHeight);
      } else {
        src = src.replace(/width=[\.\d]+/i, 'width='+monitorData[monIdx].width);
        src = src.replace(/height=[\.\d]+/i, 'height='+monitorData[monIdx].height);
      }
      streamImg.src = src;
    }
    if ( scale != '0' ) {
      streamImg.style.width = newWidth+'px';
      streamImg.style.height = newHeight+'px';
    } else {
      streamImg.style.width = '100%';
      streamImg.style.height = 'auto';
    }
  } else {
    console.log("Did not find liveStream"+monitorData[monIdx].id);
  }
} // end function changeScale()

window.addEventListener('DOMContentLoaded', initCycle);
