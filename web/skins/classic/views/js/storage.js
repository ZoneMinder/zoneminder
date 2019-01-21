
function validateForm(form) {
  var errors = [];
  if ( !form.elements['newStorage[Name]'].value ) {
    errors[errors.length] = 'You must supply a name';
  }
  if ( !form.elements['newStorage[Path]'].value ) {
    errors[errors.length] = 'You must supply a path';
  }
  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}
