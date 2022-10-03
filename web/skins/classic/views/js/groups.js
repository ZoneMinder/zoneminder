// Manage the NEW Group button
function newGroup() {
  $j.getJSON(thisUrl + '?request=modal&modal=group')
      .done(function(data) {
        insertModalHtml('groupdModal', data.html);
        $j('#groupModal').modal('show');
        $j('.chosen').chosen("destroy");
        $j('.chosen').chosen();
      })
      .fail(logAjaxFail);
}

function setGroup( element ) {
  const form = element.form;
  form.action.value = 'setgroup';
  form.submit();
}

function editGroup( element ) {
  var gid = element.getAttribute('data-group-id');
  if ( !gid ) {
    console.log('No group id found in editGroup');
  } else {
    $j.getJSON(thisUrl + '?request=modal&modal=group&gid=' + gid)
        .done(function(data) {
          insertModalHtml('groupModal', data.html);
          $j('#groupModal').modal('show');
          $j('.chosen').chosen("destroy");
          $j('.chosen').chosen();
        })
        .fail(logAjaxFail);
  }
}

function deleteGroup(element) {
  const form = element.form;
  form.elements['action'].value = 'delete';
  form.submit();
}

function configureButtons(element) {
  if (canEdit.Groups) {
    const form = element.form;
    if (element.checked) {
      form.deleteBtn.disabled = (element.value == 0);
    }
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
