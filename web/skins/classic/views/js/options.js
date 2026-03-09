// Load the Server Modal HTML via Ajax call
function getServerModal(sid) {
  $j.getJSON(thisUrl + '?request=modal&modal=server&id=' + sid)
      .done(function(data) {
        insertModalHtml('ServerModal', data.html);
        $j('#ServerModal').modal('show');
      })
      .fail(logAjaxFail);
}

function enableServerModal() {
  $j(".serverCol").click(function(evt) {
    evt.preventDefault();
    const sid = $j(this).data('sid');
    getServerModal(sid);
  });
  $j('#NewServerBtn').click(function(evt) {
    evt.preventDefault();
    getServerModal(0);
  });
}

// Load the Storage Modal HTML via Ajax call
function getStorageModal(sid) {
  $j.getJSON(thisUrl + '?request=modal&modal=storage&id=' + sid)
      .done(function(data) {
        insertModalHtml('storageModal', data.html);
        $j('#storageModal').modal('show');
      })
      .fail(logAjaxFail);
}

function enableStorageModal() {
  $j(".storageCol").click(function(evt) {
    evt.preventDefault();
    var sid = $j(this).data('sid');
    getStorageModal(sid);
  });
  $j('#NewStorageBtn').click(function(evt) {
    evt.preventDefault();
    getStorageModal(0);
  });
}

// Manage the Add New User button
function AddNewUser(el) {
  url = el.getAttribute('data-url');
  window.location.assign(url);
}

// Manage the Add New Role button
function AddNewRole(el) {
  url = el.getAttribute('data-url');
  window.location.assign(url);
}

function sortMenuItems(button) {
  if (button.classList.contains('btn-success')) {
    $j('#menuItemsBody').sortable('disable');
    // Update hidden sort order fields based on new row positions
    $j('#menuItemsBody tr').each(function(index) {
      $j(this).find('.sortOrderInput').val((index + 1) * 10);
    });
  } else {
    $j('#menuItemsBody').sortable('enable');
  }
  button.classList.toggle('btn-success');
}

function initPage() {
  const NewStorageBtn = $j('#NewStorageBtn');
  const NewServerBtn = $j('#NewServerBtn');

  if ( canEdit.System ) enableStorageModal();
  if ( canEdit.System ) enableServerModal();

  NewStorageBtn.prop('disabled', !canEdit.System);
  NewServerBtn.prop('disabled', !canEdit.System);

  $j('.bootstraptable').bootstrapTable({icons: icons}).show();

  // Menu items tab: sortable drag-and-drop
  if ($j('#menuItemsBody').length) {
    $j('#menuItemsBody').sortable({
      disabled: true,
      axis: 'y',
      cursor: 'move',
      update: function() {
        $j('#menuItemsBody tr').each(function(index) {
          $j(this).find('.sortOrderInput').val((index + 1) * 10);
        });
      }
    });
  }
}

$j(document).ready(function() {
  initPage();
});
