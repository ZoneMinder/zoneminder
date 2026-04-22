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

// Load the Delete Confirmation Modal HTML via Ajax call
function getDelConfirmModal(key, title) {
  $j.getJSON(thisUrl + '?request=modal&modal=delconfirm&key=' + key + '&title=' + title)
      .done(function(data) {
        insertModalHtml('deleteConfirm', data.html);
        document.getElementById("delConfirmBtn").addEventListener("click", function onDelConfirmClick(evt) {
        $j('#deleteConfirm').modal('hide');
        submitThisForm(document.querySelector('form[name="userForm"]'));
        });
      })
      .fail(logAjaxFail);
}

function DeleteUser() {
  $j('#deleteConfirm').modal('show');
}

function initPage() {
  const NewStorageBtn = $j('#NewStorageBtn');
  const NewServerBtn = $j('#NewServerBtn');

  if ( canEdit.System ) enableStorageModal();
  if ( canEdit.System ) enableServerModal();

  NewStorageBtn.prop('disabled', !canEdit.System);
  NewServerBtn.prop('disabled', !canEdit.System);

  $j('.bootstraptable').bootstrapTable({icons: icons}).show();

  // Menu items tab: sortable drag-and-drop and icon type toggle
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

    // Toggle icon name input visibility and placeholder based on icon type
    $j('.iconTypeSelect').on('change', function() {
      const id = $j(this).data('item-id');
      const type = $j(this).val();
      const nameInput = $j('#iconName-' + id);
      if (type === 'none') {
        nameInput.hide();
      } else {
        nameInput.show();
        if (type === 'image') {
          nameInput.attr('placeholder', 'graphics/menu/icon.png').css('width', '200px');
        } else {
          nameInput.attr('placeholder', '').css('width', '140px');
        }
      }
    });
  }
  getDelConfirmModal('ConfirmDeleteUser', 'ConfirmDeleteUserTitle');
}

$j(document).ready(function() {
  initPage();
});
