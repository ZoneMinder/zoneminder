function changeDateTime(e) {
  var minTime_element = $j('#minTime');
  var maxTime_element = $j('#maxTime');

  var minTime = moment(minTime_element.val());
  var maxTime = moment(maxTime_element.val());
  if ( minTime.isAfter(maxTime) ) {
    maxTime_element.parent().addClass('has-error');
    return; // Don't reload because we have invalid datetime filter.
  } else {
    maxTime_element.parent().removeClass('has-error');
  }

  minTime_element[0].form.submit();
return;
  var minStr = "&minTime="+($j('#minTime')[0].value);
  var maxStr = "&maxTime="+($j('#maxTime')[0].value);

  var liveStr="&live="+(liveMode?"1":"0");
  var fitStr ="&fit="+(fitMode?"1":"0");

  var zoomStr="";
  for ( var i=0; i < numMonitors; i++ )
    if ( monitorZoomScale[monitorPtr[i]] < 0.99 || monitorZoomScale[monitorPtr[i]] > 1.01 )  // allow for some up/down changes and just treat as 1 of almost 1
    zoomStr += "&z" + monitorPtr[i].toString() + "=" + monitorZoomScale[monitorPtr[i]].toFixed(2);

  var uri = "?view=" + currentView + fitStr + minStr + maxStr + liveStr + zoomStr + "&scale=" + $j("#scaleslider")[0].value + "&speed=" + speeds[$j("#speedslider")[0].value];
  window.location = uri;
}

function initPage() {
  $j('#minTime').datetimepicker({
      timeFormat: "HH:mm:ss",
      dateFormat: "yy-mm-dd",
      maxDate: +0,
      constrainInput: false,
      onClose: function (newDate, oldData) {
        if (newDate !== oldData.lastVal) {
          changeDateTime();
        }
      }
  });
  $j('#maxTime').datetimepicker({
      timeFormat: "HH:mm:ss",
      dateFormat: "yy-mm-dd",
      minDate: $j('#minTime').val(),
      maxDate: +0,
      constrainInput: false,
      onClose: function (newDate, oldData) {
        if (newDate !== oldData.lastVal) {
          changeDateTime();
        }
      }
  });
}
// Kick everything off
window.addEvent( 'domready', initPage );
