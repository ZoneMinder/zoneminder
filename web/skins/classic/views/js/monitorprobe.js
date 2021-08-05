function submitCamera( element ) {
  var form = element.form;
  if (opener) {
    form.target = opener.name;
  }
  form.view.value = 'monitor';
  form.submit();
}

function configureButtons( element ) {
  var form = element.form;
  form.saveBtn.disabled = (form.probe.selectedIndex==0);
}
