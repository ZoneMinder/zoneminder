function updateMonitorDimensions( element ) {
  var form = element.form;
  var widthFactor = parseInt( defaultAspectRatio.replace( /:.*$/, '' ) );
  var heightFactor = parseInt( defaultAspectRatio.replace( /^.*:/, '' ) );

  if ( form.elements['preserveAspectRatio'].checked ) {
    var monitorWidth = parseInt(form.elements['newMonitor[Width]'].value);
    var monitorHeight = parseInt(form.elements['newMonitor[Height]'].value);
    switch ( element.name ) {
      case 'newMonitor[Width]':
        if ( monitorWidth >= 0 ) {
          form.elements['newMonitor[Height]'].value = Math.round((monitorWidth * heightFactor) / widthFactor);
        } else {
          form.elements['newMonitor[Height]'].value = '';
        }
        break;
      case 'newMonitor[Height]':
        if ( monitorHeight >= 0 ) {
          form.elements['newMonitor[Width]'].value = Math.round((monitorHeight * widthFactor) / heightFactor);
        } else {
          form.elements['newMonitor[Width]'].value = '';
        }
        break;
    }
  }
  return ( false );
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
  document.querySelectorAll('input[name="newMonitor[SignalCheckColour]"]').forEach(function(el) {
    el.oninput = function(event) {
      $j('#SignalCheckSwatch').css('background-color', event.target.value);
    };
  });
  document.querySelectorAll('input[name="newMonitor[WebColour]"]').forEach(function(el) {
    el.oninput = function(event) {
      $j('#WebSwatch').css('background-color', event.target.value);
    };
  });
  $j('#contentForm').submit(function(event) {
    if ( validateForm(this) ) {
      $j('#contentButtons').hide();
      return true;
    } else {
      return false;
    };
  });

  // Disable form submit on enter
  $j('#contentForm input').on('keyup keypress', function(e) {
    var keyCode = e.keyCode || e.which;
    if ( keyCode == 13 ) {
      e.preventDefault();
      return false;
    }
  });

  document.querySelectorAll('input[name="newMonitor[MaxFPS]"]').forEach(function(el) {
    el.oninput = el.onclick = function(e) {
      if ( e.target.value ) {
        console.log('showing');
        $j('#newMonitor\\[MaxFPS\\]').show();
      } else {
        $j('#newMonitor\\[MaxFPS\\]').hide();
      }
    };
  });
  document.querySelectorAll('input[name="newMonitor[AlarmMaxFPS]"]').forEach(function(el) {
    el.oninput = el.onclick = function(e) {
      if ( e.target.value ) {
        console.log('showing');
        $j('#newMonitor\\[AlarmMaxFPS\\]').show();
      } else {
        $j('#newMonitor\\[AlarmMaxFPS\\]').hide();
      }
    };
  });

  $j('.chosen').chosen();
} // end function initPage()

window.addEventListener('DOMContentLoaded', initPage);
