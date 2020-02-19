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
  var monitorIds = new Array();
  for ( var i = 0; i < form.elements['monitorIds'].options.length; i++ ) {
    if ( form.elements['monitorIds'].options[i].selected ) {
      monitorIds[monitorIds.length] = form.elements['monitorIds'].options[i].value;
    }
  }
  form.elements['newUser[MonitorIds]'].value = monitorIds.join( ',' );
  if ( errors.length ) {
    alert( errors.join( "\n" ) );
    return ( false );
  }
  return ( true );
}
