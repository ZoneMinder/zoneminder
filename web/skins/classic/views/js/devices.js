var newDeviceBtn = $j('#newDeviceBtn');
var table = $j('#devicesTable');
var deleteBtn = $j('#deleteBtn');

// Load the Device Modal HTML via Ajax call
function getDeviceModal(did) {
  $j.getJSON(thisUrl + '?request=modal&modal=device&did=' + did)
      .done(function(data) {
        insertModalHtml('deviceModal', data.html);
        $j('#deviceModal').modal('show');
        // Manage the Save button
        $j('#deviceSaveBtn').click(function(evt) {
          evt.preventDefault();
          $j('#deviceModalForm').submit();
        });
      })
      .fail(logAjaxFail);
}

function enableDeviceModal() {
  $j(".deviceCol").click(function(evt) {
    evt.preventDefault();
    var did = $j(this).data('did');
    getDeviceModal(did);
  });
  newDeviceBtn.click(function(evt) {
    evt.preventDefault();
    getDeviceModal(0);
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
    if ( ! canEdit.Device ) {
      enoperm();
      return;
    }

    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=devices&action=delete&markDids[]='+selections.join('&markDids[]='))
        .done( function(data) {
          $j('#devicesTable').bootstrapTable('refresh');
          window.location.reload(true);
        })
        .fail(logAjaxFail);
  });

  // Manage the CANCEL modal button
  document.getElementById("delCancelBtn").addEventListener("click", function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

// Returns the event id's of the selected rows
function getIdSelections() {
  return $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Id.replace(/(<([^>]+)>)/gi, ''); // strip the html from the element before sending
  });
}

// Take the appropriate action when the user clicks on cells in the table
function processClicks(event, field, value, row, $element) {
  if ( field == 'On' || field == 'Off' ) {
    var key = row.KeyString.replace(/(<([^>]+)>)/gi, '');
    var url = '?request=device&action=device&command=' + field + '&key=' + key;
    console.log('Url sent to device: ' + url);
    $j.getJSON(thisUrl + url)
        .done(function(data) {
          // TODO - verify if either of these are needed
          $j('#devicesTable').bootstrapTable('refresh');
          window.location.reload(true);
        })
        .fail(logAjaxFail);
  }
}

function initPage() {
  // Init the bootstrap-table
  table.bootstrapTable({icons: icons});

  if ( canEdit.Device ) enableDeviceModal();

  newDeviceBtn.prop('disabled', !canEdit.Device);

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
    if ( ! canEdit.Device ) {
      enoperm();
      return;
    }

    evt.preventDefault();
    $j('#deleteConfirm').modal('show');
  });

  // Load the delete confirmation modal into the DOM
  getDelConfirmModal('ConfirmDeleteDevices');

  // enable or disable buttons based on current selection and user rights
  table.on('check.bs.table uncheck.bs.table ' +
  'check-all.bs.table uncheck-all.bs.table',
  function() {
    selections = table.bootstrapTable('getSelections');

    deleteBtn.prop('disabled', !(selections.length && canEdit.Device));
  });

  // Process mouse clicks on the table cells
  table.on('click-cell.bs.table', processClicks);
}

$j(document).ready(initPage);
