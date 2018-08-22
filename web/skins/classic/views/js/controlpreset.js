function updateLabel() {
  var form = $('contentForm');
  var preset_ddm = form.elements['preset'];

  var presetIndex = preset_ddm[preset_ddm.selectedIndex].value;
  if ( labels[presetIndex] ) {
    form.newLabel.value = labels[presetIndex];
  } else {
    form.newLabel.value = '';
  }
}
window.addEvent('domready', updateLabel);
