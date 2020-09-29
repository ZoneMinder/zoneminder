function configureExportButton( element ) {
  var form = element.form;

  var radioCount = 0;
  for ( var i = 0, len=form.elements.length; i < len; i++ ) {
    if ( form.elements[i].type == "radio" && form.elements[i].checked ) {
      radioCount++;
    }
  }
  form.elements['exportButton'].disabled = (radioCount == 0);
}

function startDownload( exportFile ) {
  console.log("Starting download from " + exportFile);
  window.location.replace( exportFile );
}

var exportTimer = null;

function exportProgress() {
  var tickerText = $j('#exportProgressTicker').text();
  if ( tickerText.length < 1 || tickerText.length > 4 ) {
    $j('#exportProgressTicker').text( '.' );
  } else {
    $j('#exportProgressTicker').append( '.' );
  }
}

function exportResponse(respObj, respText) {
  console.log(respObj);
  
  var fullUrl = thisUrl+'?view='+currentView+'&'+eidParm+
      '&exportFormat='+respObj.exportFormat+
      '&exportFile='+respObj.exportFile+
      '&generated='+((respObj.result=='Ok')?1:0)+
      '&connkey='+connkey;

  console.log('The full url is: ' + fullUrl);
  window.location.replace(fullUrl);
}

function exportEvent() {
  var form = $j('#contentForm').serialize();
  $j.getJSON(thisUrl + '?view=request&request=event&action=download', form)
      .done(exportResponse)
      .fail(logAjaxFail); 
  $j('#exportProgress').removeClass( 'hidden' );
  $j('#exportProgress').addClass( 'warnText' );
  $j('#exportProgressText').text( exportProgressString );
  exportProgress();
  exportTimer = exportProgress.periodical( 500 );
}

function initPage() {
  if ( exportReady ) {
    startDownload.pass( exportFile ).delay( 1500 );
  }
  document.getElementById('exportButton').addEventListener("click", exportEvent );
}

$j(document).ready(function() {
  initPage();
});
