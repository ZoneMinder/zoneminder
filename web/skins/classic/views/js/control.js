var controlParms = "view=request&request=control";
var controlReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, onSuccess: getControlResponse } );

function getControlResponse( respObj, respText ) {
  if ( !respObj )
    return;
  //console.log( respText );
  if ( respObj.result != 'Ok' ) {
    alert( "Control response was status = "+respObj.status+"\nmessage = "+respObj.message );
  }
}

function controlCmd( control, event, xtell, ytell ) {
  var locParms = "&id="+$('mid').get('value');
  if ( event && (xtell || ytell) ) {
    var target = event.target;
    var coords = $(target).getCoordinates();

    var x = event.pageX - coords.left;
    var y = event.pageY - coords.top;

    if ( xtell ) {
      var xge = parseInt( (x*100)/coords.width );
      if ( xtell == -1 )
        xge = 100 - xge;
      else if ( xtell == 2 )
        xge = 2*(50 - xge);
      locParms += "&xge="+xge;
    }
    if ( ytell ) {
      var yge = parseInt( (y*100)/coords.height );
      if ( ytell == -1 )
        yge = 100 - yge;
      else if ( ytell == 2 )
        yge = 2*(50 - yge);
      locParms += "&yge="+yge;
    }
  }
  controlReq.send( controlParms+"&control="+control+locParms );
}
