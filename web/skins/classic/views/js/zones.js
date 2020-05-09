var streamCmdParms = "view=request&request=stream&connkey="+connKey;
var streamCmdReq = new Request.JSON( {url: monitorUrl, method: 'post', timeout: AJAX_TIMEOUT, link: 'cancel'} );

function streamCmdQuit( action ) {
  if ( action ) {
    streamCmdReq.send( streamCmdParms+"&command="+CMD_QUIT );
  }
}

