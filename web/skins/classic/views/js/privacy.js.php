function validateForm ( form ) {
  var errors = new Array();

  if ( !form.elements['notify-on'].checked && !form.elements['notify-off'].checked ) {
    errors[errors.length] = "<?php echo translate('PrivacyBadSelection') ?>";
  }

  if ( errors.length ) {
    alert( errors.join( "\n" ) );
    return( false );
  }
  return( true );
}

