function validateForm(form) {
  var errors = [];
  if ( !form.elements['newServer[Name]'].value ) {
    errors[errors.length] = 'You must supply a name';
  }
  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}
