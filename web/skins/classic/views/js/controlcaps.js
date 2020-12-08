var table = $j('#controlTable');
var addNewBtn = $j('#addNewBtn');
var editBtn = $j('#editBtn');
var deleteBtn = $j('#deleteBtn');

// Manage the Add New Control button
function AddNewControl(el) {
  url = el.getAttribute('data-url');
  window.location.assign(url);
}

// Manage the Add New Control button
function addNewControl(el) {
  url = el.getAttribute('data-url');
  window.location.assign(url);
}

// Manage the Edit Control button
function editControl(el) {
  var selection = getIdSelections();

  url = el.getAttribute('data-url');
  window.location.assign(url+selection);
}

// Returns the event id's of the selected rows
function getIdSelections() {
  return $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Id.replace(/(<([^>]+)>)/gi, ''); // strip the html from the element before sending
  });
}

// Load the Delete Confirmation Modal HTML via Ajax call
function getDelConfirmModal(key) {
  $j.getJSON(thisUrl + '?request=modal&modal=delconfirm&key=' + key)
      .done(function(data) {
        insertModalHtml('deleteConfirm', data.html);
        manageDelConfirmModalBtns();
      })
      .fail(logAjaxFail);
}

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById("delConfirmBtn").addEventListener("click", function onDelConfirmClick(evt) {
    if ( ! canEdit.Control ) {
      enoperm();
      return;
    }

    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=controlcaps&action=delete&cids[]='+selections.join('&cids[]='))
        .done( function(data) {
          $j('#eventTable').bootstrapTable('refresh');
          window.location.reload(true);
        })
        .fail(logAjaxFail);
  });
}

function initPage() {
  // enable or disable buttons based on current selection and user rights
  table.on('check.bs.table uncheck.bs.table ' +
  'check-all.bs.table uncheck-all.bs.table',
  function() {
    selections = table.bootstrapTable('getSelections');

    addNewBtn.prop('disabled', (selections.length || !canEdit.Control));
    editBtn.prop('disabled', !((selections.length == 1) && canEdit.Control));
    deleteBtn.prop('disabled', !(selections.length && canEdit.Control));
  });

  // Init the bootstrap-table
  table.bootstrapTable({icons: icons});

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Disable the back button if there is nothing to go back to
  $j('#backBtn').prop('disabled', !document.referrer.length);

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Manage the DELETE button
  document.getElementById("deleteBtn").addEventListener("click", function onDeleteClick(evt) {
    if ( ! canEdit.Control ) {
      enoperm();
      return;
    }

    evt.preventDefault();
    $j('#deleteConfirm').modal('show');
  });

  // Load the delete confirmation modal into the DOM
  getDelConfirmModal('ConfirmDeleteControl');

  // Hide these columns on first run when no cookie is saved
  if ( !getCookie("zmControlTable.bs.table.columns") ) {
    table.bootstrapTable('hideColumn', 'Id');
  }
}

$j(document).ready(function() {
  initPage();
});
