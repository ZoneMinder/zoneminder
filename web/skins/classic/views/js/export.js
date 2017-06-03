function configureExportButton( element ) {
  var form = element.form;

  var checkCount = 0;
  var radioCount = 0;
  for ( var i = 0; i < form.elements.length; i++ ) {
    if ( form.elements[i].type == "checkbox" && form.elements[i].checked )
      checkCount++;
    else if ( form.elements[i].type == "radio" && form.elements[i].checked )
      radioCount++;
  }
  form.elements['exportButton'].disabled = (checkCount == 0 || radioCount == 0);
}

function startDownload( exportFile ) {
  window.location.replace( exportFile );
}

var exportTimer = null;

function exportProgress() {
  var tickerText = $('exportProgressTicker').get('text');
  if ( tickerText.length < 1 || tickerText.length > 4 )
    $('exportProgressTicker').set( 'text', '.' );
  else
    $('exportProgressTicker').appendText( '.' );
}

function exportResponse( respObj, respText ) {
  window.location.replace( thisUrl+'?view='+currentView+'&'+eidParm+'&exportFile='+respObj.exportFile+'&generated='+((respObj.result=='Ok')?1:0) );
}

function exportEvent( form ) {
  var parms = 'view=request&request=event&action=export';
  parms += '&'+$(form).toQueryString();
  var query = new Request.JSON( { url: thisUrl, method: 'post', data: parms, onSuccess: exportResponse } );
  query.send();
  $('exportProgress').removeClass( 'hidden' );
  $('exportProgress').setProperty( 'class', 'warnText' );
  $('exportProgressText').set( 'text', exportProgressString );
  exportProgress();
  exportTimer = exportProgress.periodical( 500 );
}

function initPage() {
  configureExportButton( $('exportButton') );
  if ( exportReady ) {
    startDownload.pass( exportFile ).delay( 1500 );
  }
}

window.addEvent( 'domready', initPage );
