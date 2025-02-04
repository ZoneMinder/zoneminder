// Manage the Add New Zone button
function AddNewZone(el) {
  url = el.getAttribute('data-url');
  window.location.assign(url);
}

var monitors = new Array();
var TimerHideShow;

function initPage() {
  for ( var i = 0, length = monitorData.length; i < length; i++ ) {
    monitors[i] = new MonitorStream(monitorData[i]);

    // Start the fps and status updates. give a random delay so that we don't assault the server
    var delay = Math.round( (Math.random()+0.5)*statusRefreshTimeout );
    monitors[i].setStreamScale();
    monitors[i].start(delay);
  }
  $j('svg polygon').on('click', function(e) {
    window.location='?view=zone&mid='+this.getAttribute('data-mid')+'&zid='+this.getAttribute('data-zid');
  });

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Disable the back button if there is nothing to go back to
  $j('#backBtn').prop('disabled', !document.referrer.length);

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });
}

function panZoomIn(el) {
  zmPanZoom.zoomIn(el);
}

function panZoomOut(el) {
  zmPanZoom.zoomOut(el);
}

function streamCmdQuit() {
  for ( var i = 0, length = monitorData.length; i < length; i++ ) {
    monitors[i] = new MonitorStream(monitorData[i]);
    monitors[i].stop();
  }
}

window.addEventListener('DOMContentLoaded', initPage);

document.onvisibilitychange = () => {
  if (document.visibilityState === "hidden") {
    TimerHideShow = clearTimeout(TimerHideShow);
    TimerHideShow = setTimeout(function() {
      //Stop monitors when closing or hiding page
      for (let i = 0, length = monitorData.length; i < length; i++) {
        monitors[i].kill();
      }
    }, 15*1000);
  } else {
    //Start monitors when show page
    for (let i = 0, length = monitorData.length; i < length; i++) {
      if (!monitors[i].started) {
        monitors[i].start();
      }
    }
  }
};
