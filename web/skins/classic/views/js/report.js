var backBtn = $j('#backBtn');
var deleteBtn = $j('#deleteBtn');

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  if ( ! canEdit.Events ) {
    enoperm();
    return;
  }

  deleteReports([document.getElementById("reportForm").getAttribute("data-report_id")]);
}

function deleteReports(ids) {
  const ticker = document.getElementById('deleteProgressTicker');
  const chunk = ids.splice(0, 10);
  console.log("Deleting " + chunk.length + " selections.  " + ids.length);

  $j.getJSON(thisUrl + '?request=reports&task=delete&ids[]='+chunk.join('&ids[]='))
      .done( function(data) {
        if (ids.length) {
          if (ticker.innerHTML.length < 1 || ticker.innerHTML.length > 10) {
            ticker.innerHTML = '.';
          } else {
            ticker.innerHTML = ticker.innerHTML + '.';
          }
        }
        window.location.assign("?view=reports");
      })
      .fail( function(jqxhr) {
        logAjaxFail(jqxhr);
        $j('#reportsTable').bootstrapTable('refresh');
      });
}

function initPage() {
  deleteBtn.prop('disabled', !canEdit.Events);

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener('click', function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Manage the DELETE button
  document.getElementById("deleteBtn").addEventListener('click', function onDeleteClick(evt) {
    if (!canEdit.Events) {
      enoperm();
      return;
    }

    getDelConfirmModal('ConfirmDeleteReport');
  });
}

$j(document).ready(function() {
  initPage();
});
