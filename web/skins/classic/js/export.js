/*
 * This file contains UI functions relating to export / download modals
 *
 */
var fileExistencePollingInterval = null;

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
    const downloadLink = $j('#downloadLink');
    if (exportFile instanceof Array) {
      let nodeCopy;
      fileForAutoDownload = [...exportFile];
      for (let i=0, length = exportFile.length; i < length; i++) {
        const file = exportFile[i];
        const fileName = getFileNameFromURL(file);

        if (i==0) {
          if (exportFile.length == 1) {
            downloadLink.text('Download ' + '"' + fileName + '"');
          } else {
            downloadLink.text((i+1) + '. Download ' + '"' + fileName + '"');
          }
          downloadLink.attr("href", thisUrl + file);
          downloadLink.removeClass('disabled').removeAttr('aria-disabled tabindex');
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
      downloadLink.removeClass('disabled').removeAttr('aria-disabled tabindex');
      downloadLink.text('Download ' + '"' + getFileNameFromURL(exportFile) + '"');
      downloadLink.attr("href", thisUrl + exportFile);
      fileForAutoDownload = [exportFile];
    }

    $j('#exportProgress').addClass( 'text-success' );
    $j('#exportProgress').text(exportSucceededString);
    for (let i=0, length = fileForAutoDownload.length; i < length; i++) {
      setTimeout(startDownload, 1500+(i*500), fileForAutoDownload[i]); // This is an automatic download link.
    }

    const filenamePathArray = [];
    for (let i = 0; i < fileForAutoDownload.length; i++) {
      // Let's create an array of filename path
      const params = new URLSearchParams(fileForAutoDownload[i]);
      const exportRoot = (params.has('export_root')) ? params.get('export_root') : '';
      const filename = (params.has('file')) ? params.get('file') : '';
      const filenamePath = (exportRoot) ? exportRoot + '/' + filename : filename;

      filenamePathArray.push(filenamePath);
    }

    if (fileExistencePollingInterval) clearInterval(fileExistencePollingInterval);
    let existenceCheckInFlight = false;
    fileExistencePollingInterval = setInterval(() => {
      if (existenceCheckInFlight) return;
      existenceCheckInFlight = true;
      $j.ajax({
        type: 'GET',
        url: thisUrl,
        dataType: 'json',
        data: {
          'view': 'request',
          'request': 'event',
          'action': 'file_existence_check',
          'file_name_array': filenamePathArray
        },
        success: checkedResponse,
        timeout: 0,
        complete: function() {
          existenceCheckInFlight = false;
        },
        error: function(jqXHR, status, errorThrown) {
          logAjaxFail(jqXHR, status, errorThrown);
          $j('#exportProgress').html('Failed: ' + errorThrown);
          clearInterval(fileExistencePollingInterval);
          fileExistencePollingInterval = null;
        }
      });
    }, 3000);
  } else {
    $j('#exportProgress').addClass( 'text-danger' );
    $j('#exportProgress').text(exportFailedString);
  }
}

function checkedResponse(data, responseText) {
  const downloadModal = document.getElementById('downloadModal');
  if (!downloadModal) {
    console.log("Modal window for files download is missing.");
    clearInterval(fileExistencePollingInterval);
    return;
  }
  let filesExist = false;
  if (data.result == "Error") {
    console.warn(data, responseText);
  } else if (data.result == "Ok") {
    let nodeList = {};
    if (downloadModal) nodeList = downloadModal.querySelectorAll("[id^=downloadLink]");
    for (let i = 0; i < data.response.length; i++) {
      if (data.response[i][1] === false) {
        nodeList.forEach(function(el) {
          const params = new URL(el.href).searchParams;
          const exportRoot = params.get('export_root') || '';
          const file = params.get('file') || '';
          const relPath = exportRoot ? (exportRoot + '/' + file) : file;
          if (relPath === data.response[i][0]) {
            el.classList.add('disabled');
            el.setAttribute('aria-disabled', 'true');
            el.setAttribute('tabindex', '-1');
          }
        });
      } else {
        filesExist = true;
      }
    }
  }
  if (data.result == "Error" || !filesExist || !(downloadModal.offsetWidth > 0 && downloadModal.offsetHeight > 0)) {
    clearInterval(fileExistencePollingInterval);
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
