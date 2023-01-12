function configureButtons() {
  const form = document.getElementById('monitorPresetForm');
  form.saveBtn.disabled = (form.preset.selectedIndex==0);
}

function initPage() {
  $j('#preset').change(configureButtons);
}

$j(document).ready(initPage);
