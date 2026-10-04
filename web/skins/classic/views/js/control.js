var form = $j('#controlForm');

function controlReq(data) {
  $j.getJSON(zmAuth.appendTo(monitorUrl + '?view=request&request=control'), data)
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

// Bound by ptzControls() as data-on-mousedown/mouseup (or data-on-click):
// the button's value is the command, releasing a continuous move stops it.
function controlCmd(event) {
  const button = event.currentTarget || event.target;
  const control = (event.type == 'mouseup') ? 'moveStop' : button.getAttribute('value');
  const xtell = button.getAttribute('data-xtell');
  const ytell = button.getAttribute('data-ytell');
  const data = {};

  if (xtell || ytell) {
    const target = $j(button);
    const offset = target.offset();
    const width = target.width();
    const height = target.height();

    const x = event.pageX - offset.left;
    const y = event.pageY - offset.top;

    if (xtell) {
      let xge = parseInt((x*100)/width);
      if (xtell == -1) {
        xge = 100 - xge;
      } else if (xtell == 2) {
        xge = 2*(50 - xge);
      }
      data.xge = xge;
    }
    if (ytell) {
      let yge = parseInt((y*100)/height);
      if (ytell == -1) {
        yge = 100 - yge;
      } else if (ytell == 2) {
        yge = 2*(50 - yge);
      }
      data.yge = yge;
    }
  }
  data.id = $j('#mid').val();
  data.control = control;
  controlReq(data);
}

function initPage() {
  $j('#mid').change(function() {
    form.submit();
  });
}

$j(document).ready(initPage);
