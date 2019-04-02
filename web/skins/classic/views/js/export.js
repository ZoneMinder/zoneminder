var exportTimer = null;

function configureExportButton( element ) {
  var form = element.form;

  var checkCount = 0;
  var radioCount = 0;
  for ( var i = 0; i < form.elements.length; i++ ) {
    if ( form.elements[i].type == "checkbox" && form.elements[i].checked ) {
      checkCount++;
    } else if ( form.elements[i].type == "radio" && form.elements[i].checked ) {
      radioCount++;
    }
  }
  form.elements['exportButton'].disabled = (checkCount == 0 || radioCount == 0);
}

function startDownload(file) {
  console.log("Starting download of " + file);
  window.location.replace(file);
}

function exportProgress() {
  if ( exportTimer ) {
    var tickerText = $('exportProgressTicker').get('text');
    if ( tickerText.length < 1 || tickerText.length > 4 ) {
      $('exportProgressTicker').set('text', '.');
    } else {
      $('exportProgressTicker').appendText('.');
    }
  }
}

function exportResponse(respObj, respText) {
  //console.log(respText);

  $clear(exportTimer);
  if ( respObj.result != 'Ok' ) {
    $('exportProgressTicker').set('text', respObj.message);
  } else {
    $('exportProgressTicker').set('text', exportSucceededString);
    console.log(respObj.exportFile);
    console.log(encodeURIComponent(respObj.exportFile));

    startDownload.pass(decodeURIComponent(respObj.exportFile)).delay( 1500 );
  }
  return;

  if ( 0 ) {
    var eids = new Array();
    for (var i = 0, len=form.elements.length; i < len; i++) {
      if ( form.elements[i].name == 'eids[]' ) {
        eids[eids.length] = 'eids[]='+form.elements[i].value;
      }
    }
  }
  form.submit();

  //window.location.replace( thisUrl+'?view='+currentView+'&'+eids.join('&')+'&exportFile='+respObj.exportFile+'&generated='+((respObj.result=='Ok')?1:0) );
}

function exportEvent( ) {
  var parms = 'view=event&request=event&action=export';
  parms += '&'+$('contentForm').toQueryString();
  var query = new Request.JSON( {
    url: thisUrl,
    method: 'post',
    data: parms,
    onSuccess: exportResponse
  } );
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
    startDownload.pass(exportFile).delay( 1500 );
  }
  document.getElementById('exportButton').addEventListener('click', exportEvent);
}

window.addEventListener('DOMContentLoaded', initPage);
