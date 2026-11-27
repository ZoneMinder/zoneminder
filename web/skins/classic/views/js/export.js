var exportTimer = null;

function configureExportButton() {
  const form = document.getElementById('contentForm');
  if (!form) {
    console.error('Form contentForm not found.');
    return;
  }

  const haveEvent = form.querySelectorAll('input[name="eids[]"]').length > 0;

  form.elements['exportButton'].disabled = !(
    haveEvent &&
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
  exportTimer = null;
  if (respObj.result != 'Ok') {
    $j('#exportProgressTicker').text(respObj.message);
  } else {
    $j('#exportProgressTicker').text(exportSucceededString);
    setTimeout(startDownload, 1500, decodeURIComponent(respObj.exportFile));
  }
  return;
}

function exportEvents( ) {
  // A second click while an export runs would start a second server export
  // into the same directory and leak the first progress timer.
  if (exportTimer) return;
  $j('#exportProgress').removeClass('hidden errorText');
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

  // A second click while the first export runs would otherwise orphan its
  // ticker, which then overwrites the result with dots for good.
  clearInterval(exportTimer);
  exportTimer = setInterval(exportProgress, 500);
}

function exportFail(jqxhr, textStatus, error) {
  clearInterval(exportTimer);
  exportTimer = null;
  $j('#exportProgress').addClass('errorText');
  $j('#exportProgressTicker').text('Failed export');
  logAjaxFail(jqxhr, textStatus, error);
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
