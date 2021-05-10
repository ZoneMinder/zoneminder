function newGroup() {
  createPopup( '?view=group', 'zmGroup', 'group' );
}

function setGroup( element ) {
  var form = element.form;
  form.action.value = 'setgroup';
  form.submit();
}

function editGroup( element ) {
  var gid = element.getAttribute('data-group-id');
  if ( !gid ) {
    console.log('No group id found in editGroup');
  } else {
    createPopup('?view=group&gid='+gid, 'zmGroup'+gid, 'group');
  }
}

function deleteGroup( element ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'delete';
  form.submit();
}

function configureButtons( element ) {
  if ( canEditGroups ) {
    var form = element.form;
    if ( element.checked ) {
      form.deleteBtn.disabled = (element.value == 0);
    }
  }
}

window.focus();
