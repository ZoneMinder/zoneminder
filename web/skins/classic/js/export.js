/*
 * This file contains UI functions relating to export / download modals
 *
 */

function startDownload(exportFile) {
  console.log("Starting download from " + exportFile);
  window.location.replace(exportFile);
}

function exportResponse(data, responseText) {
  const generated = (data.result=='Ok') ? 1 : 0;

  $j('#exportProgress').removeClass( 'text-warning' );
  if (generated) {
    const exportFile = data.exportFile;
    $j('#downloadLink').text('Download');
    $j('#downloadLink').attr("href", thisUrl + exportFile);
    $j('#exportProgress').addClass( 'text-success' );
    $j('#exportProgress').text(exportSucceededString);
    setTimeout(startDownload, 1500, exportFile);
  } else {
    $j('#exportProgress').addClass( 'text-danger' );
    $j('#exportProgress').text(exportFailedString);
  }
}

function exportEvent() {
  $j.ajax({
    url: thisUrl + '?view=request&request=event&action=download',
    dataType: 'json',
    data: $j('#downloadForm').serialize(),
    success: exportResponse,
    timeout: 0,
    error: function(jqXHR, status, errorThrown) {
      logAjaxFail(jqXHR, status, errorThrown);
      $j('#exportProgress').html('Failed: ' + errorThrown);
    }
  });
  $j('#exportProgress').removeClass('invisible');
}
