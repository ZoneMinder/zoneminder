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

let newMenuItemIndex = 0;

function addMenuItem() {
  const idx = newMenuItemIndex++;
  const rowId = 'new' + idx;
  const sortOrder = ($j('#menuItemsBody tr').length + 1) * 10;
  const row =
    '<tr id="menuItem-' + rowId + '">' +
      '<td>' +
        '<input type="hidden" name="newItems[' + idx + '][SortOrder]" class="sortOrderInput" value="' + sortOrder + '"/>' +
        '<input type="checkbox" name="newItems[' + idx + '][Enabled]" value="1" checked/>' +
      '</td>' +
      '<td class="text-left">' +
        '<input type="text" name="newItems[' + idx + '][MenuKey]" class="menuKeyInput" placeholder="' + escapeHTML(menuItemStrings.menuKey) + '" required/>' +
      '</td>' +
      '<td>' +
        '<input type="text" name="newItems[' + idx + '][Label]" placeholder="' + escapeHTML(menuItemStrings.label) + '"/>' +
      '</td>' +
      '<td class="text-left">' +
        '<input type="text" name="newItems[' + idx + '][Link]" class="linkInput" placeholder="?view=... or https://..." style="width:220px;"/>' +
      '</td>' +
      '<td class="text-left">' +
        '<div class="d-flex align-items-center" style="gap:6px;">' +
          '<span class="menuIconPreview" id="iconPreview-' + rowId + '"></span>' +
          '<select name="newItems[' + idx + '][IconType]" class="form-control form-control-sm iconTypeSelect" data-item-id="' + rowId + '" style="width:auto;display:inline-block;">' +
            '<option value="material" selected>Material</option>' +
            '<option value="fontawesome">Font Awesome</option>' +
            '<option value="image">Image</option>' +
            '<option value="none">None</option>' +
          '</select>' +
          '<input type="text" name="newItems[' + idx + '][Icon]" class="form-control form-control-sm iconNameInput" id="iconName-' + rowId + '" placeholder="" style="width:140px;"/>' +
        '</div>' +
      '</td>' +
      '<td class="text-right">' +
        '<button type="button" class="btn btn-sm btn-danger removeMenuItemBtn" title="' + escapeHTML(menuItemStrings.remove) + '"><i class="material-icons">delete</i></button>' +
      '</td>' +
    '</tr>';
  $j('#menuItemsBody').append(row);
}

// Re-render the icon preview cell of a menu row from its current type/name.
function updateMenuIconPreview($row) {
  const $preview = $row.find('.menuIconPreview');
  if (!$preview.length) return;
  const type = $row.find('.iconTypeSelect').val();
  const $input = $row.find('.iconNameInput');
  let val = $input.val().trim();
  if (type === 'fontawesome') {
    $preview.html('<i class="fa ' + escapeHTML(val) + '"></i>');
  } else if (type === 'image') {
    $preview.html(val ? '<img src="' + escapeHTML(val) + '" style="width:24px;height:24px;object-fit:contain;" alt=""/>' : '');
  } else if (type === 'none') {
    $preview.html('');
  } else { // material
    if (val === '') val = $input.attr('placeholder') || 'menu';
    $preview.html('<i class="material-icons">' + escapeHTML(val) + '</i>');
  }
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

function DeleteUser() {
  getDelConfirmModal('ConfirmDeleteUser', 'ConfirmDeleteUserTitle', 'userForm');
}

function DeleteServer() {
  getDelConfirmModal('ConfirmDeleteServer', 'ConfirmDeleteServerTitle', 'serversForm');
}

function DeleteStorage() {
  getDelConfirmModal('ConfirmDeleteStorage', 'ConfirmDeleteStorageTitle', 'storageForm');
}

function DeleteRole() {
  getDelConfirmModal('ConfirmDeleteRole', 'ConfirmDeleteRoleTitle', 'roleForm');
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

    // Toggle icon name input visibility and placeholder based on icon type.
    // Delegated so dynamically added rows (Add new entry) are handled too.
    $j('#menuItemsBody').on('change', '.iconTypeSelect', function() {
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
      updateMenuIconPreview($j(this).closest('tr'));
    });

    // Live-update the icon preview as the icon name is typed.
    $j('#menuItemsBody').on('input', '.iconNameInput', function() {
      updateMenuIconPreview($j(this).closest('tr'));
    });

    // Show the derived ?view= default in the Link placeholder of a new row
    // as its MenuKey is typed (matches the built-in ?view= rule).
    $j('#menuItemsBody').on('input', '.menuKeyInput', function() {
      const key = $j(this).val().trim();
      $j(this).closest('tr').find('.linkInput')
          .attr('placeholder', key === '' ? '?view=... or https://...' : '?view=' + key);
    });

    // Remove an unsaved new menu entry row.
    $j('#menuItemsBody').on('click', '.removeMenuItemBtn', function() {
      $j(this).closest('tr').remove();
    });

    // Delete a saved menu entry: confirm, then submit the form as a delete.
    $j('#menuItemsBody').on('click', '.deleteMenuItemBtn', function() {
      if (!confirm(menuItemStrings.confirmDelete)) return;
      const id = $j(this).data('id');
      const $form = $j(this).closest('form');
      $form.find('input[name="action"]').val('deletemenuitems');
      $j('<input>', {type: 'hidden', name: 'deleteIds[]', value: id}).appendTo($form);
      $form.submit();
    });
  }
}

$j(document).ready(function() {
  initPage();
});
