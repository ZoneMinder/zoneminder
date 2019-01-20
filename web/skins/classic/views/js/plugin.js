function validateForm( form ) {
  return ( true );
}

function submitForm( form ) {
  form.submit();
}


function limitRange( field, minValue, maxValue ) {
  if ( parseInt(field.value) < parseInt(minValue) ) {
    field.value = minValue;
  } else if ( parseInt(field.value) > parseInt(maxValue) ) {
    field.value = maxValue;
  }
}

function initPage() {
  return ( true );
}

window.addEventListener( 'DOMContentLoaded', initPage );
