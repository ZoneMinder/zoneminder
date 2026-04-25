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
  form.saveBtn.disabled = (form.probe.selectedIndex == 0);
  var manualUrlEl = form.elements.namedItem('manual_url');
  var hasManualUrl = manualUrlEl && manualUrlEl.value.trim().length > 0;
  if (form.elements.namedItem('onvifBtn')) {
    form.onvifBtn.disabled = !hasManualUrl;
  }
}

function connectOnvif( element ) {
  var form = element.form;
  var manualUrl = form.manual_url ? form.manual_url.value.trim() : '';
  if ( !manualUrl ) return;
  var mid = (form.mid && form.mid.value) ? form.mid.value : '';
  window.location.assign(
      '?view=onvifprobe&mid=' + encodeURIComponent(mid) +
      '&manual_url=' + encodeURIComponent(manualUrl)
  );
}

function changeInterface(element) {
  element.form.submit();
}
