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
  window.location.replace( exportFile );
}

var exportTimer = null;

function exportProgress() {
  var tickerText = $('exportProgressTicker').get('text');
  if ( tickerText.length < 1 || tickerText.length > 4 ) {
    $('exportProgressTicker').set( 'text', '.' );
  } else {
    $('exportProgressTicker').appendText( '.' );
  }
}

function exportResponse( respObj, respText ) {
  window.location.replace( thisUrl+'?view='+currentView+'&'+eidParm+'&exportFormat='+respObj.exportFormat+'&generated='+((respObj.result=='Ok')?1:0) );
}

function exportEvent( form ) {
  var parms = 'view=request&request=event&action=download';
  parms += '&'+$(form).toQueryString();
  var query = new Request.JSON( {url: thisUrl, method: 'post', data: parms, onSuccess: exportResponse} );
  query.send();
  $('exportProgress').removeClass( 'hidden' );
  $('exportProgress').setProperty( 'class', 'warnText' );
  $('exportProgressText').set( 'text', exportProgressString );
  exportProgress();
  exportTimer = exportProgress.periodical( 500 );
}

function initPage() {
  if ( exportReady ) {
    startDownload.pass( exportFile ).delay( 1500 );
  }
  document.getElementById('exportButton').addEventListener("click", function onClick(evt) {
    exportEvent(this.form);
  });
}

window.addEventListener( 'DOMContentLoaded', initPage );
