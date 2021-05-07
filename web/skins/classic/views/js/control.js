var form = $j('#controlForm');

function controlReq(data) {
  $j.getJSON(thisUrl + '?view=request&request=control', data)
      .done(getControlResponse)
      .fail(logAjaxFail);
}

function getControlResponse(respObj, respText) {
  if ( !respObj ) {
    return;
  }
  //console.log( respText );
  if ( respObj.result != 'Ok' ) {
    alert("Control response was status = "+respObj.status+"\nmessage = "+respObj.message);
  }
}

function controlCmd( control, event, xtell, ytell ) {
  var mid = $j('#mid').getAttribute('value');

  if ( event && (xtell || ytell) ) {
    var data = {};
    var target = $j(event.target);
    var offset = target.offset();
    var width = target.width();
    var height = target.height();

    var x = event.pageX - offset.left;
    var y = event.pageY - offset.top;

    if ( xtell ) {
      var xge = parseInt( (x*100)/width );
      if ( xtell == -1 ) {
        xge = 100 - xge;
      } else if ( xtell == 2 ) {
        xge = 2*(50 - xge);
      }
      data.xge = xge;
    }
    if ( ytell ) {
      var yge = parseInt( (y*100)/height );
      if ( ytell == -1 ) {
        yge = 100 - yge;
      } else if ( ytell == 2 ) {
        yge = 2*(50 - yge);
      }
      data.yge = yge;
    }
  }
  data.id = mid;
  data.control = control;
  controlReq(data);
}

function initPage() {
  $j('#mid').change(function() {
    form.submit();
  });
}

$j(document).ready(initPage);
