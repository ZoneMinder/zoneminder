// Manage the NEW Group button 
function newGroup() {
  $j.getJSON(thisUrl + '?request=modal&modal=group')
      .done(function(data) {
        if ( $j('#groupModal').length ) {
          $j('#groupModal').replaceWith(data.html);
        } else {
          $j("body").append(data.html);
        }
        $j('#groupModal').modal('show');
        $j('.chosen').chosen("destroy");
        $j('.chosen').chosen();
        // Manage the Save button
        $j('#grpModalSaveBtn').click(function(evt) {
          evt.preventDefault();
          $j('#groupForm').submit();
        });
      })
      .fail(logAjaxFail);
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
    $j.getJSON(thisUrl + '?request=modal&modal=group&gid=' + gid)
        .done(function(data) {
          if ( $j('#groupModal').length ) {
            $j('#groupModal').replaceWith(data.html);
          } else {
            $j("body").append(data.html);
          }
          $j('#groupModal').modal('show');
          $j('.chosen').chosen("destroy");
          $j('.chosen').chosen();
          // Manage the Save button
          $j('#grpModalSaveBtn').click(function(evt) {
            evt.preventDefault();
            $j('#groupForm').submit();
          });
        })
        .fail(logAjaxFail);
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

function configModalBtns() {
  var form = $j('#groupForm')[0];
  if ( !form ) {
    console.log("No groupForm found");
    return;
  }
  if ( !canEditGroups ) {
    console.log("Cannot edit groups");
    form.elements['action'].disabled = disabled;
    return;
  }
  var disabled = false;

  if ( form.elements['newGroup[Name]'].value == '' ) {
    disabled = true;
  }
  form.elements['action'].disabled = disabled;
}
