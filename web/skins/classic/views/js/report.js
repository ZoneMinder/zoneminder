var backBtn = $j('#backBtn');
var deleteBtn = $j('#deleteBtn');

// Load the Delete Confirmation Modal HTML via Ajax call
function getDelConfirmModal() {
  $j.getJSON(thisUrl + '?request=modal&modal=delconfirm')
      .done(function(data) {
        insertModalHtml('deleteConfirm', data.html);
        manageDelConfirmModalBtns();
      })
      .fail(logAjaxFail);
}

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById("delConfirmBtn").addEventListener('click', function onDelConfirmClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }
    evt.preventDefault();

    const selections = getIdSelections();
    if (!selections.length) {
      alert('Please select reports to delete.');
    } else {
      deleteReports(selections);
    }
  });

  // Manage the CANCEL modal button
  document.getElementById("delCancelBtn").addEventListener('click', function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

function deleteReports(ids) {
  const ticker = document.getElementById('deleteProgressTicker');
  const chunk = ids.splice(0, 10);
  console.log("Deleting " + chunk.length + " selections.  " + ids.length);

  $j.getJSON(thisUrl + '?request=reports&task=delete&ids[]='+chunk.join('&ids[]='))
      .done( function(data) {
        if (!ids.length) {
          $j('#reportsTable').bootstrapTable('refresh');
          $j('#deleteConfirm').modal('hide');
        } else {
          if (ticker.innerHTML.length < 1 || ticker.innerHTML.length > 10) {
            ticker.innerHTML = '.';
          } else {
            ticker.innerHTML = ticker.innerHTML + '.';
          }
          deleteReports(ids);
        }
      })
      .fail( function(jqxhr) {
        logAjaxFail(jqxhr);
        $j('#reportsTable').bootstrapTable('refresh');
        $j('#deleteConfirm').modal('hide');
      });
}

function initPage() {
  // Load the delete confirmation modal into the DOM
  getDelConfirmModal();

  deleteBtn.prop('disabled', canEdit.Events);

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

    evt.preventDefault();
    $j('#deleteConfirm').modal('show');
  });
}

$j(document).ready(function() {
  initPage();
});
