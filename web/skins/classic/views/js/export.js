var exportTimer = null;

function configureExportButton() {
  const form = document.getElementById('contentForm');
  if (!form) {
    console.error('Form contentForm not found.');
    return;
  }

  let haveEventChecked = 0;
  for (let i=0, len=form.elements['eids[]'].length; i<len; i++) {
    if (form.elements['eids[]'][i].checked) {
      haveEventChecked ++;
      break;
    }
  }

  form.elements['exportButton'].disabled = (
    haveEventChecked &&
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
  if (exportTimer) {
    const tickerText = $j('#exportProgressTicker').text();
    if ( tickerText.length < 1 || tickerText.length > 4 ) {
      $j('#exportProgressTicker').text('.');
    } else {
      $j('#exportProgressTicker').append('.');
    }
  } else {
    console.log('No timer');
  }
}

function exportResponse(respObj, respText) {
  clearInterval(exportTimer);
  if (respObj.result != 'Ok') {
    $j('#exportProgressTicker').text(respObj.message);
  } else {
    $j('#exportProgressTicker').text(exportSucceededString);
    setTimeout(startDownload, 1500, decodeURIComponent(respObj.exportFile));
  }
  return;
}

function exportEvents( ) {
  $j('#exportProgress').removeClass('hidden');
  $j('#exportProgress').addClass('warnText');
  $j('#exportProgressText').text(exportProgressString);

  $j.ajax({
    url: thisUrl + '?view=event&request=event&action=export',
    data: $j('#contentForm').serialize(),
    dataType: 'json',
    timeout: 0,
    success: exportResponse,
    error: exportFail
  });

  exportTimer = setInterval(exportProgress, 500);
}

function exportFail() {
  clearInterval(exportTimer);
  $j('#exportProgress').addClass('errorText');
  $j('#exportProgressTicker').text('Failed export');
  logAjaxFail();
}

function getEventDetailModal(eid) {
  $j.getJSON(thisUrl + '?request=modal&modal=eventdetail&eids[]=' + eid)
      .done(function(data) {
        insertModalHtml('eventDetailModal', data.html);
        $j('#eventDetailModal').modal('show');
        // Manage the Save button
        $j('#eventDetailSaveBtn').click(function(evt) {
          evt.preventDefault();
          $j('#eventDetailForm').submit();
        });
      })
      .fail(logAjaxFail);
}

function initPage() {
  configureExportButton();
  if (exportReady) {
    setTimeout(startDownload, 1500, exportFile);
  }
  document.getElementById('exportButton').addEventListener('click', exportEvents);

  // Manage the eventdetail link in the export list
  $j('.eDetailLink').click(function(evt) {
    evt.preventDefault();
    getEventDetailModal($j(this).data('eid'));
  });

  // Manage the BACK button
  document.getElementById('backBtn').addEventListener('click', function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Don't enable the back button if there is no previous zm page to go back to
  $j('#backBtn').prop('disabled', !document.referrer.length);

  // Manage the REFRESH Button
  document.getElementById('refreshBtn').addEventListener('click', function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });
}

window.addEventListener('DOMContentLoaded', initPage);
