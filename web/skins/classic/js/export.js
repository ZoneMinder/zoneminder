/*
 * This file contains UI functions relating to export / download modals
 *
 */

function startDownload(exportFile) {
  console.log("Starting download from " + exportFile);
  window.location.replace(exportFile);
}

function getFileNameFromURL(url) {
  const matches = url.match(/file=(.*?)&/) || url.match(/file=(.*)/);
  return (matches) ? matches[1] : '';
}

function exportResponse(data, responseText) {
  const generated = (data.result=='Ok') ? 1 : 0;

  $j('#exportProgress').removeClass( 'text-warning' );
  if (generated) {
    let fileForAutoDownload = [];
    const exportFile = data.exportFile; // NOW THIS IS a real array of links
    if (exportFile instanceof Array) {
      var nodeCopy;
      fileForAutoDownload = [...exportFile];
      for (let i=0, length = exportFile.length; i < length; i++) {
        const file = exportFile[i];
        const fileName = getFileNameFromURL(file);

        if (i==0) {
          if (exportFile.length == 1) {
            $j('#downloadLink').text('Download ' + '"' + fileName + '"');
          } else {
            $j('#downloadLink').text((i+1) + '. Download ' + '"' + fileName + '"');
          }
          $j('#downloadLink').attr("href", thisUrl + file);
        } else {
          const downloadLink = document.getElementById('downloadLink'+(i-1)) || document.getElementById('downloadLink'); // Links must be in sequential order.
          nodeCopy = downloadLink.cloneNode(true);
          nodeCopy.id = 'downloadLink'+i;
          downloadLink.parentNode.insertBefore(nodeCopy, downloadLink.nextSibling);
          $j(nodeCopy).html('<br>' + (i+1) + '. Download ' + '"' + fileName + '"');
          $j(nodeCopy).attr("href", thisUrl + file);
        }
      }
    } else {
      $j('#downloadLink').text('Download ' + '"' + getFileNameFromURL(exportFile) + '"');
      $j('#downloadLink').attr("href", thisUrl + exportFile);
      fileForAutoDownload = [exportFile];
    }

    $j('#exportProgress').addClass( 'text-success' );
    $j('#exportProgress').text(exportSucceededString);
    for (let i=0, length = fileForAutoDownload.length; i < length; i++) {
      setTimeout(startDownload, 1500+(i*500), fileForAutoDownload[i]); // This is an automatic download link.
    }
  } else {
    $j('#exportProgress').addClass( 'text-danger' );
    $j('#exportProgress').text(exportFailedString);
  }
}

function exportEvent() {
  $j('#exportProgress').html('<span class="spinner-grow" role="status" aria-hidden="true"></span>Exporting...');
  $j('#exportProgress').removeClass( 'text-success text-danger text-warning' );
  $j('#exportProgress').addClass( 'text-warning invisible' );
  $j('#downloadLink').html( '' );
  let i = 1;
  let downloadLink = null;
  while (i < 1000) { // We believe that more than 1000 links cannot be generated.
    downloadLink = document.getElementById('downloadLink'+i);
    if (downloadLink) {
      downloadLink.remove();
      i++;
    } else {
      i = 1000;
    }
  }

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
