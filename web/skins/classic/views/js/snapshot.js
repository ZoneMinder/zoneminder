
// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById('delConfirmBtn').addEventListener('click', function onDelConfirmClick(evt) {
    if ( !canEdit.Events ) {
      enoperm();
      return;
    }

    evt.preventDefault();
    /*
    $j.getJSON(thisUrl + '?request=events&task=delete&eids[]='+eventData.Id)
        .done(function(data) {
          streamNext(true);
        })
        .fail(logAjaxFail);
        */
  });

  // Manage the CANCEL modal button
  document.getElementById("delCancelBtn").addEventListener("click", function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

function downloadResponse(respObj, respText) {
  clearInterval(downloadTimer);
  if (respObj.result != 'Ok' ) {
    $j('#downloadProgressTicker').text(respObj.message);
  } else {
    $j('#downloadProgressTicker').text(downloadSucceededString);
    setTimeout(startDownload, 1500, decodeURIComponent(respObj.exportFile));
  }
  return;
}
function startDownload(file) {
  window.location.replace(file);
}

function downloadProgress() {
  if (downloadTimer) {
    var tickerText = $j('#downloadProgressTicker').text();
    if (tickerText.length < 1 || tickerText.length > 4) {
      $j('#downloadProgressTicker').text('.');
    } else {
      $j('#downloadProgressTicker').append('.');
    }
  }
}

function initPage() {
  // enable or disable buttons based on current selection and user rights
  /*
  renameBtn.prop('disabled', !canEdit.Events);
  archiveBtn.prop('disabled', !(!eventData.Archived && canEdit.Events));
  unarchiveBtn.prop('disabled', !(eventData.Archived && canEdit.Events));
  */

  // Don't enable the back button if there is no previous zm page to go back to
  $j('#backBtn').prop('disabled', !document.referrer.length);

  // Manage the BACK button
  bindButton('#backBtn', 'click', null, function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Manage the REFRESH Button
  bindButton('#refreshBtn', 'click', null, function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Manage the EDIT button
  $j('#saveBtn').prop('disabled', !(canEdit.Events || (snapshot.CreatedBy == user.Id) ));
  bindButton('#saveBtn', 'click', null, function onSaveClick(evt) {
    /*
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }
    */
    evt.target.form.submit();
  });

  // Manage the EXPORT button
  $j('#downloadBtn').prop('disabled', !canView.Snapshots);
  bindButton('#downloadBtn', 'click', null, function onDownloadClick(evt) {
    evt.preventDefault();
    formData = {
      eids: snapshot.EventIds,
      exportImages: 0,
      exportVideo: 0,
      exportFrames: 0,
      exportDetail: 0,
      exportMisc: 1,
      exportFormat: 'zip',
      exportCompress: 0,
      exportStructure: 'flat',
      exportFile: 'Snapshot'+snapshot.Id
    };
    $j.getJSON(thisUrl + '?view=event&request=event&action=export', formData)
        .done(downloadResponse)
        .fail(logAjaxFail);

    $j('#downloadProgress').removeClass('hidden');
    $j('#downloadProgress').addClass('warnText');
    $j('#downloadProgress').text(downloadProgressString);

    downloadTimer = setInterval(downloadProgress, 500);
  });

  $j('#exportBtn').prop('disabled', !canView.Snapshots);
  bindButton('#exportBtn', 'click', null, function onExportClick(evt) {
    console.log('export clicked');
    evt.preventDefault();
    window.location.assign('?view=export&eids[]='+snapshot.EventIds.join('&eids[]='));
  });

  /*
  // Manage the DOWNLOAD VIDEO button
  bindButton('#downloadBtn', 'click', null, function onDownloadClick(evt) {
    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=modal&modal=download&eids[]='+eventData.Id)
        .done(function(data) {
          insertModalHtml('downloadModal', data.html);
          $j('#downloadModal').modal('show');
          // Manage the GENERATE DOWNLOAD button
          $j('#exportButton').click(exportEvent);
        })
        .fail(logAjaxFail);
  });
*/
  // Manage the DELETE button
  $j('#deleteBtn').prop('disabled', !canEdit.Events);
  bindButton('#deleteBtn', 'click', null, function onDeleteClick(evt) {
    if ( !canEdit.Events ) {
      enoperm();
      return;
    }

    evt.preventDefault();
    if ( ! $j('#deleteConfirm').length ) {
      // Load the delete confirmation modal into the DOM
      $j.getJSON(thisUrl + '?request=modal&modal=delconfirm')
          .done(function(data) {
            insertModalHtml('deleteConfirm', data.html);
            manageDelConfirmModalBtns();
            $j('#deleteConfirm').modal('show');
          })
          .fail(logAjaxFail);
      return;
    }
    $j('#deleteConfirm').modal('show');
  });
} // end initPage

// Kick everything off
$j(document).ready(initPage);
