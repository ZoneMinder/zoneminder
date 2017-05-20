function submitCamera( element ) {
  var form = element.form;
  form.target = opener.name;
  form.view.value = 'monitor';
  form.submit();
  closeWindow.delay( 250 );
}

function gotoStep1( element ) {
  var form = element.form;
  form.target = self.name;
  form.view.value = 'onvifprobe';
  form.step.value = '1';
  form.submit();
}

function gotoStep2( element ) {
  var form = element.form;
  form.target = self.name;
  form.view.value = 'onvifprobe';
  form.step.value = '2';
  form.submit();
}

function configureButtons( element ) {
  var form = element.form;
  if (form.elements.namedItem("nextBtn")) {
    form.nextBtn.disabled = (form.probe.selectedIndex==0) ||
      (form.username == "") || (form.username == null) ||
      (form.password == "") || (form.password == null);
  }
  if(form.elements.namedItem("saveBtn")) {
    form.saveBtn.disabled = (form.probe.selectedIndex==0);
  }
}
