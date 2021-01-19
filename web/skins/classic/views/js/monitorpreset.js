var form = $j('#monitorPresetForm');

function submitPreset( element ) {
  form.target = opener.name;
  form.view.value = 'monitor';
  form.submit();
}

function configureButtons() {
  form.saveBtn.disabled = (form.preset.selectedIndex==0);
}

function initPage() {
  $j('#preset').change(configureButtons);
}

$j(document).ready(initPage);
