function validateForm( form, newUser ) {
  var errors = new Array();
  if ( !form.elements['newUser[Username]'].value ) {
    errors[errors.length] = "You must supply a username";
  }
  if ( form.elements['newUser[Password]'].value ) {
    if ( !form.conf_password.value ) {
      errors[errors.length] = "You must confirm the password";
    } else if ( form.elements['newUser[Password]'].value != form.conf_password.value ) {
      errors[errors.length] = "The new and confirm passwords are different";
    }
  } else if ( newUser ) {
    errors[errors.length] = "You must supply a password";
  }
  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}

function initPage() {
  $j('#contentForm').submit(function(event) {
    if ( validateForm(this) ) {
      $j('#contentButtons').hide();
      return true;
    } else {
      return false;
    };
  });
} // end function initPage

window.addEventListener('DOMContentLoaded', initPage);
