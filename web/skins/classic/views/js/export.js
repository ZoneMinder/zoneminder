var exportTimer = null;

function configureExportButton(element) {
  var form = element.form;

  var eventCount = 0;
  document.querySelectorAll('input[name="eids[]"]').forEach(function(el) {
    if ( el.checked ) {
      eventCount ++;
    }
  });

  form.elements['exportButton'].disabled = (
    eventCount &&
    (
      form.elements['exportDetail'].checked ||
      form.elements['exportFrames'].checked ||
      form.elements['exportImages'].checked ||
      form.elements['exportVideo'].checked ||
      form.elements['exportMisc'].checked
    ) &&
    ( form.elements['exportFormat'][0].checked || form.elements['exportFormat'][1].checked ) &&
    ( form.elements['exportCompress'][0].checked || form.elements['exportCompress'][1].checked )
  );
}

function startDownload(file) {
  console.log('Starting download of ' + file);
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
  clearInterval(exportTimer);
  if ( respObj.result != 'Ok' ) {
    $('exportProgressTicker').set('text', respObj.message);
  } else {
    $('exportProgressTicker').set('text', exportSucceededString);
    startDownload.pass(decodeURIComponent(respObj.exportFile)).delay(1500);
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

function exportEvents( ) {
  var parms = 'view=event&request=event&action=export';
  parms += '&'+$('contentForm').toQueryString();
  var query = new Request.JSON( {
    url: thisUrl,
    method: 'post',
    data: parms,
    onSuccess: exportResponse
  } );
  query.send();
  $('exportProgress').removeClass('hidden');
  $('exportProgress').setProperty('class', 'warnText');
  $('exportProgressText').set('text', exportProgressString);
  //exportProgress();
  exportTimer = exportProgress.periodical( 500 );
}

function getEventDetailModal(eid) {
  $j.getJSON(thisUrl + '?request=modal&modal=eventdetail&eids[]=' + eid)
  .done(function(data) {
    if ( $j('#eventDetailModal').length ) {
      $j('#eventDetailModal').replaceWith(data.html);
    } else {
      $j("body").append(data.html);
    }
    $j('#eventDetailModal').modal('show');
    // Manage the Save button
    $j('#eventDetailSaveBtn').click(function(evt) {
      evt.preventDefault();
      $j('#eventDetailForm').submit();
    });
  })
  .fail(function(jqxhr, textStatus, error) {
    console.log("Request Failed: " + textStatus + ", " + error);
    console.log("Response Text: " + jqxhr.responseText);
  });
}

function initPage() {
  configureExportButton( $('exportButton') );
  if ( exportReady ) {
    startDownload.pass(exportFile).delay(1500);
  }
  document.getElementById('exportButton').addEventListener('click', exportEvents);
  // Manage the eventdetail link in the export list
  $j(".eDetailLink").click(function(evt) {
    evt.preventDefault();
    var eid = $j(this).data('eid');
    getEventDetailModal(eid);
  });
}

window.addEventListener('DOMContentLoaded', initPage);
