var form = $j('#monitorePresetForm');

function submitPreset( element ) {
  form.target = opener.name;
  form.view.value = 'monitor';
  form.submit();
  closeWindow.delay( 250 );
}

function configureButtons() {
  form.saveBtn.disabled = (form.preset.selectedIndex==0);
}

function initPage() {
  $j('#preset').change(configureButtons);
}

$j(document).ready(initPage);
