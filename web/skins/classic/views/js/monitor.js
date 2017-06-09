function updateMonitorDimensions( element ) {
  var form = element.form;
  var widthFactor = parseInt( defaultAspectRatio.replace( /:.*$/, '' ) );
  var heightFactor = parseInt( defaultAspectRatio.replace( /^.*:/, '' ) );

  if ( form.elements['preserveAspectRatio'].checked ) {
    var monitorWidth = parseInt(form.elements['newMonitor[Width]'].value);
    var monitorHeight = parseInt(form.elements['newMonitor[Height]'].value);
    switch( element.name ) {
      case 'newMonitor[Width]':
        if ( monitorWidth >= 0 )
          form.elements['newMonitor[Height]'].value = Math.round((monitorWidth * heightFactor) / widthFactor);
        else
          form.elements['newMonitor[Height]'].value = '';
        break;
      case 'newMonitor[Height]':
        if ( monitorHeight >= 0 )
          form.elements['newMonitor[Width]'].value = Math.round((monitorHeight * widthFactor) / heightFactor);
        else
          form.elements['newMonitor[Width]'].value = '';
        break;
    }
  }
  return( false );
}

function loadLocations( element ) {
  var form = element.form;
  var controlIdSelect = form.elements['newMonitor[ControlId]'];
  var returnLocationSelect = form.elements['newMonitor[ReturnLocation]'];

  returnLocationSelect.options.length = 1;
  //returnLocationSelect.options[0] = new Option( noneString, -1 );

  var returnLocationOptions = controlOptions[controlIdSelect.selectedIndex];
  if ( returnLocationOptions ) {
    for ( var i = 0; i < returnLocationOptions.length; i++ ) {
      returnLocationSelect.options[returnLocationSelect.options.length] = new Option( returnLocationOptions[i], i );
    }
  }
}

function initPage() {
  //var protocolSelector = $('contentForm').elements['newMonitor[Protocol]'];
  //if ( $(protocolSelector).getTag() == 'select' )
  //updateMethods( $(protocolSelector) );
}

window.addEvent( 'domready', initPage );
