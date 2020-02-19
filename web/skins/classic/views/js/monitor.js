function updateMonitorDimensions(element) {
  var form = element.form;
  if ( element.type == 'number' ) {
    // either width or height

    var widthFactor = parseInt(defaultAspectRatio.replace(/:.*$/, ''));
    var heightFactor = parseInt(defaultAspectRatio.replace(/^.*:/, ''));

    var monitorWidth = parseInt(form.elements['newMonitor[Width]'].value);
    var monitorHeight = parseInt(form.elements['newMonitor[Height]'].value);

    if ( form.elements['preserveAspectRatio'].checked ) {
      switch ( element.name ) {
        case 'newMonitor[Width]':
          if ( monitorWidth >= 0 ) {
            form.elements['newMonitor[Height]'].value = Math.round((monitorWidth * heightFactor) / widthFactor);
          } else {
            form.elements['newMonitor[Height]'].value = '';
          }
          monitorHeight = parseInt(form.elements['newMonitor[Height]'].value);
          break;
        case 'newMonitor[Height]':
          if ( monitorHeight >= 0 ) {
            form.elements['newMonitor[Width]'].value = Math.round((monitorHeight * widthFactor) / heightFactor);
          } else {
            form.elements['newMonitor[Width]'].value = '';
          }
          monitorWidth = parseInt(form.elements['newMonitor[Width]'].value);
          break;
      }
    }
    // If we find a matching option in the dropdown, select it or select custom

    var option = $j('select[name="dimensions_select"] option[value="'+monitorWidth+'x'+monitorHeight+'"]');
    if ( !option.size() ) {
      $j('select[name="dimensions_select"]').val('');
    } else {
      $j('select[name="dimensions_select"]').val(monitorWidth+'x'+monitorHeight);
    }
  } else {
    // For some reason we get passed the first option instead of the select
    element = form.elements['dimensions_select'];

    var value = element.options[element.selectedIndex].value;
    if ( value != '' ) { // custom dimensions
      var dimensions = value.split('x');
      form.elements['newMonitor[Width]'].value = dimensions[0];
      form.elements['newMonitor[Height]'].value = dimensions[1];
    }
  }
  return false;
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
  document.querySelectorAll('input[name="newMonitor[Width]"]').forEach(function(el) {
    el.oninput = window['updateMonitorDimensions'].bind(el, el);
  });
  document.querySelectorAll('input[name="newMonitor[Height]"]').forEach(function(el) {
    el.oninput = window['updateMonitorDimensions'].bind(el, el);
  });
  document.querySelectorAll('select[name="dimensions_select"]').forEach(function(el) {
    el.onchange = window['updateMonitorDimensions'].bind(el, el);
  });
  document.querySelectorAll('select[name="newMonitor[ControlId]"]').forEach(function(el) {
    el.onchange = window['loadLocations'].bind(el, el);
  });
  document.querySelectorAll('input[name="newMonitor[WebColour]"]').forEach(function(el) {
    el.onchange = window['change_WebColour'].bind(el);
  });


  $j('.chosen').chosen();
} // end function initPage()

function change_WebColour() {
  $j('#WebSwatch').css(
      'backgroundColor',
      $j('input[name="newMonitor[WebColour]"]').val()
  );
}

function getRandomColour() {
  var letters = '0123456789ABCDEF';
  var colour = '#';
  for (var i = 0; i < 6; i++) {
    colour += letters[Math.floor(Math.random() * 16)];
  }
  return colour;
}

function random_WebColour() {
  var new_colour = getRandomColour();
  $j('input[name="newMonitor[WebColour]"]').val(new_colour);
  $j('#WebSwatch').css(
      'backgroundColor', new_colour
  );
}

window.addEventListener('DOMContentLoaded', initPage);
