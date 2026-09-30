// Manage the NEW Group button
function newGroup() {
  $j('#groupModal').remove();
  $j.getJSON(thisUrl + '?request=modal&modal=group')
      .done(function(data) {
        insertModalHtml('groupdModal', data.html);
        $j('#groupModal').modal('show');
        $j('#newGroupMonitorIds').chosen({width: "100%"});
      })
      .fail(logAjaxFail);
}

function setGroup( element ) {
  const form = element.form;
  form.action.value = 'setgroup';
  form.submit();
}

function editGroup( element ) {
  const gid = element.getAttribute('data-group-id');
  if ( !gid ) {
    console.log('No group id found in editGroup');
  } else {
    $j('#groupModal').remove();
    $j.getJSON(thisUrl + '?request=modal&modal=group&gid=' + gid)
        .done(function(data) {
          insertModalHtml('groupModal', data.html);
          $j('#groupModal').modal('show');
          $j('#newGroupMonitorIds').chosen({width: "100%"});
        })
        .fail(logAjaxFail);
  }
}

function deleteGroup(element) {
  getDelConfirmModal('ConfirmDeleteGroups', 'Delete', 'groupsForm');
}

function configureButtons(element) {
  if (canEdit.Groups) {
    configureDeleteButton(element);
  } else {
    const form = element.form;
    if (form) form.deleteBtn.disabled = true;
  }
}

function configModalBtns() {
  const form = document.getElementById('groupForm');
  if (!form) {
    console.error("No groupForm found");
    return;
  }
  if (!canEdit.Groups) {
    console.log("Cannot edit groups");
    form.elements['action'].disabled = true;
    return;
  }
  if (form.elements['newGroup[Name]'].value == '') {
    form.elements['action'].disabled = true;
  }
}
