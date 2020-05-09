function submitCamera( element ) {
  var form = element.form;
  form.target = opener.name;
  form.view.value = 'monitor';
  form.submit();
  closeWindow.delay( 250 );
}

function configureButtons( element ) {
  var form = element.form;
  form.saveBtn.disabled = (form.probe.selectedIndex==0);
}
