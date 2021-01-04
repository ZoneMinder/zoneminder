function initPage() {
  var form = document.getElementById('contentForm');
  var preset_ddm = form.elements['preset'];

  var presetIndex = preset_ddm[preset_ddm.selectedIndex].value;
  if ( labels[presetIndex] ) {
    form.newLabel.value = labels[presetIndex];
  } else {
    form.newLabel.value = '';
  }
}

$j(document).ready(initPage );
