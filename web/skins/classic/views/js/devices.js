function switchDeviceOn( element, key ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'device';
  form.command.value = 'on';
  form.key.value = key;
  form.submit();
}

function switchDeviceOff( element, key ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'device';
  form.command.value = 'off';
  form.key.value = key;
  form.submit();
}

function deleteDevice( element ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'delete';
  form.submit();
}

function configureButtons( element, name ) {
  var form = element.form;
  var checked = false;
  for (var i = 0; i < form.elements.length; i++) {
    if ( form.elements[i].name.indexOf(name) == 0) {
      if ( form.elements[i].checked ) {
        checked = true;
        break;
      }
    }
  }
  form.deleteBtn.disabled = !checked;
}

window.focus();
