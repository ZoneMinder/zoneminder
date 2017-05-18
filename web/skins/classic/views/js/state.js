function checkState( element ) {
  var form = element.form;

  var minIndex = running?2:1;
  if ( form.runState.selectedIndex < minIndex ) {
    form.saveBtn.disabled = true;
    form.deleteBtn.disabled = true;
  } else {
    form.saveBtn.disabled = false;
    form.deleteBtn.disabled = false;
  }

  if ( form.newState.value != '' )
    form.saveBtn.disabled = false;

  // PP if we are in 'default' state, disable delete
  // you can still save
  if ( element.value.toLowerCase() == 'default' ) {
    form.saveBtn.disabled = false;
    form.deleteBtn.disabled = true;
  }
}

function saveState( element ) {
  var form = element.form;

  form.view.value = currentView;
  form.action.value = 'save';
  form.submit();
}

function deleteState( element ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'delete';
  form.submit();
}

if ( applying ) {
  function submitForm() {
    $('contentForm').submit();
  }
  window.addEvent( 'domready', function() { submitForm.delay( 1000 ); } );
}
