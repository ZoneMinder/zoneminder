
var requestQueue = new Request.Queue({
  concurrent: monitorData.length,
  stopOnFailure: false
});
function streamCmdQuit( ) {
  fo
  if ( action ) {
    streamCmdReq.send( streamCmdParms+"&command="+CMD_QUIT );
  }
}

var monitors = new Array();

function initPage() {
 for ( var i = 0, length = monitorData.length; i < length; i++ ) {
    monitors[i] = new MonitorStream(monitorData[i]);

    // Start the fps and status updates. give a random delay so that we don't assault the server
    var delay = Math.round( (Math.random()+0.5)*statusRefreshTimeout );
    monitors[i].start(delay);
  }
}

window.addEventListener('DOMContentLoaded', initPage);

