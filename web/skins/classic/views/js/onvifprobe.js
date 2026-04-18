function submitCamera( element ) {
  var form = element.form;
  form.target = self.name;
  form.view.value = 'monitor';
  form.submit();
}

function gotoStep1( element ) {
  var form = element.form;
  form.target = self.name;
  form.view.value = 'onvifprobe';
  form.step.value = '1';
  form.submit();
}

function normalizeOnvifManualUrl( manualUrl ) {
  var normalizedUrl = manualUrl.trim();

  if ( normalizedUrl.length === 0 ) {
    return '';
  }

  if ( !/^[a-z][a-z0-9+.-]*:\/\//i.test(normalizedUrl) ) {
    normalizedUrl = 'http://' + normalizedUrl;
  }

  try {
    var parsedUrl = new URL(normalizedUrl);

    if ( parsedUrl.protocol !== 'http:' && parsedUrl.protocol !== 'https:' ) {
      return '';
    }

    if ( !parsedUrl.pathname || parsedUrl.pathname === '/' ) {
      parsedUrl.pathname = '/onvif/device_service';
    }

    return parsedUrl.toString();
  } catch ( e ) {
    return '';
  }
}

function gotoStep2( element ) {
  var form = element.form;
  var manualUrlEl = form.elements.namedItem('manual_url');
  var manualUrl = manualUrlEl ? normalizeOnvifManualUrl(manualUrlEl.value) : '';

  if ( manualUrl.length > 0 ) {
    var cameraData = {
      Function: 'Monitor',
      Type: 'Ffmpeg',
      Host: manualUrl,
      SOAP: '1.1',
      ConfigURL: manualUrl,
      ConfigOptions: 'SOAP1.1',
      Notes: ''
    };

    var encoded = btoa(JSON.stringify(cameraData));
    var option = null;
    var i;

    for ( i = 0; i < form.probe.options.length; i++ ) {
      if ( form.probe.options[i].value === encoded ) {
        option = form.probe.options[i];
        break;
      }
    }

    if ( !option ) {
      option = document.createElement('option');
      option.value = encoded;
      form.probe.appendChild(option);
    }

    option.selected = true;
  }

  form.target = self.name;
  form.view.value = 'onvifprobe';
  form.step.value = '2';
  form.submit();
}

function configureButtons(element) {
  var form = element.form;
  var manualUrlEl = form.elements.namedItem('manual_url');
  var hasManualUrl = manualUrlEl && manualUrlEl.value.trim().length > 0;
  var hasProbe = form.probe && (form.probe.selectedIndex != 0);

  if (form.elements.namedItem('nextBtn')) {
    form.nextBtn.disabled = !(hasManualUrl || hasProbe);
  }
  if (form.elements.namedItem('saveBtn')) {
    form.saveBtn.disabled = (form.probe.selectedIndex == 0);
  }
}

function changeInterface(element) {
  gotoStep1(element);
}
