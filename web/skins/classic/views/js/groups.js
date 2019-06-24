function newGroup() {
  createPopup( '?view=group', 'zmGroup', 'group' );
}

function setGroup( element ) {
  var form = element.form;
  form.action.value = 'setgroup';
  form.submit();
}

function editGroup( gid ) {
  createPopup( '?view=group&gid='+gid, 'zmGroup', 'group' );
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
