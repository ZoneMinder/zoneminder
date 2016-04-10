var streamCmdParms = "view=request&request=stream&connkey="+connKey;
var streamCmdReq = new Request.JSON( { url: monitorUrl+thisUrl, method: 'post', timeout: AJAX_TIMEOUT, link: 'cancel' } );
var streamCmdTimer = null;

function streamCmdQuit( action ) {
    if ( action )
        streamCmdReq.send( streamCmdParms+"&command="+CMD_QUIT );
}

