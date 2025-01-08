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
  update_estimated_ram_use();
  return false;
} // function updateMonitorDimensions(element)

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
  var backBtn = $j('#backBtn');
  var onvifBtn = $j('#onvifBtn');

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
        $j('#newMonitor\\[MaxFPS\\]').show();
      } else {
        $j('#newMonitor\\[MaxFPS\\]').hide();
      }
    };
  });
  document.querySelectorAll('input[name="newMonitor[AlarmMaxFPS]"]').forEach(function(el) {
    el.oninput = el.onclick = function(e) {
      if ( e.target.value ) {
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
  document.querySelectorAll('select[name="newMonitor[Type]"]').forEach(function(el) {
    el.onchange = function() {
      const form = document.getElementById('contentForm');
      form.tab.value = 'general';
      form.submit();
    };
  });
  document.querySelectorAll('input[name="newMonitor[ImageBufferCount]"],input[name="newMonitor[MaxImageBufferCount]"],input[name="newMonitor[Width]"],input[name="newMonitor[Height]"],input[name="newMonitor[PreEventCount]"]').forEach(function(el) {
    el.oninput = window['buffer_setting_oninput'].bind(el);
  });
  update_estimated_ram_use();

  document.querySelectorAll('select[name="newMonitor[Function]"]').forEach(function(el) {
    el.onchange = function() {
      $j('#function_help div').hide();
      $j('#'+this.value+'Help').show();
      if ( this.value == 'Monitor' || this.value == 'None' ) {
        $j('#FunctionEnabled').hide();
      } else {
        $j('#FunctionEnabled').show();
      }
      if ( this.value == 'Record' || this.value == 'Nodect' ) {
        $j('#FunctionDecodingEnabled').show();
      } else {
        $j('#FunctionDecodingEnabled').hide();
      }
    };
    el.onchange();
  });

  document.querySelectorAll('select[name="newMonitor[VideoWriter]"]').forEach(function(el) {
    el.onchange = function() {
      if ( this.value == 1 /* Encode */ ) {
        $j('.OutputCodec').show();
        $j('.Encoder').show();
        $j('.OutputContainer').show();
        $j('.OptionalEncoderParam').show();
        $j('.RecordAudio').show();

      } else {
        $j('.OutputCodec').hide();
        $j('.Encoder').hide();
        $j('.OutputContainer').hide();
        $j('.OptionalEncoderParamPreset').hide();
        $j('.OptionalEncoderParam').hide();
        $j('.RecordAudio').hide();

      }
    };
    el.onchange();
  });
  document.querySelectorAll('select[name="newMonitor[OutputCodec]"]').forEach(function(el) {
    el.onchange = function() {
      var encoder_dropdown = $j('select[name="newMonitor[Encoder]"]');
      if (encoder_dropdown) {
        for (i=0; i<encoder_dropdown[0].options.length; i++) {
          option = encoder_dropdown[0].options[i];
          if ( this.value == 27 ) {
            option.disabled = !option.value.includes('264');
            if ( option.disabled && option.selected ) {
              encoder_dropdown[0].options[0].selected = 1;
              option.selected = false;
            }
          } else if ( this.value == 173 /* hevc */ ) {
            option.disabled = !(option.value.includes('hevc') || option.value.includes('265') );
            if ( option.disabled && option.selected ) {
              encoder_dropdown[0].options[0].selected = 1;
              option.selected = false;
            }
          } else if ( this.value == 226 /* av1 */ ) {
            option.disabled = !(option.value.includes('av1'));
            if ( option.disabled && option.selected ) {
              encoder_dropdown[0].options[0].selected = 1;
              option.selected = false;
            }
          } else {
            option.disabled = false;
          }
        }
      } else {
        console.log('No encoder');
      }
    };
    el.onchange();
  });

  $j('.chosen').chosen();

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Manage the PROBE button
  $j('#probeBtn').click(function(evt) {
    var mid = evt.currentTarget.getAttribute("data-mid");
    evt.preventDefault();

    //FIX-ME: MAKE THIS A MODAL
    //$j('#modalFunction-'+mid).modal('show');
    window.location.assign('?view=monitorprobe&mid='+mid);
  });

  // Manage the ONVIF button
  $j('#onvifBtn').click(function(evt) {
    var mid = evt.currentTarget.getAttribute("data-mid");
    evt.preventDefault();

    //FIX-ME: MAKE THIS A MODAL
    //$j('#modalFunction-'+mid).modal('show');
    window.location.assign('?view=onvifprobe&mid='+mid);
  });

  // Don't enable the onvif button if there is no previous zm page to go back to
  onvifBtn.prop('disabled', !hasOnvif);

  // Manage the PRESET button
  $j('#presetBtn').click(function(evt) {
    var mid = evt.currentTarget.getAttribute("data-mid");
    evt.preventDefault();

    //FIX-ME: MAKE THIS A MODAL
    //$j('#modalFunction-'+mid).modal('show');
    window.location.assign('?view=monitorpreset&mid='+mid);
  });

  // Manage the CANCEL Button
  document.getElementById("cancelBtn").addEventListener("click", function onCancelClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=console');
  });

  if ( parseInt(ZM_OPT_USE_GEOLOCATION) ) {
    if ( window.L ) {
      var form = document.getElementById('contentForm');
      var latitude = form.elements['newMonitor[Latitude]'].value;
      var longitude = form.elements['newMonitor[Longitude]'].value;
      map = L.map('LocationMap', {
        center: L.latLng(latitude, longitude),
        zoom: 13,
        onclick: function() {
          alert('click');
        }
      });
      L.tileLayer(ZM_OPT_GEOLOCATION_TILE_PROVIDER, {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
        maxZoom: 18,
        id: 'mapbox/streets-v11',
        tileSize: 512,
        zoomOffset: -1,
        accessToken: ZM_OPT_GEOLOCATION_ACCESS_TOKEN,
      }).addTo(map);
      L.marker([latitude, longitude]).addTo(map);
    } else {
      console.log('Location turned on but leaflet not installed.');
    }
  } // end if ZM_OPT_USE_GEOLOCATION
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

function buffer_setting_oninput(e) {
  const max_image_buffer_count = document.getElementById('newMonitor[MaxImageBufferCount]');
  const pre_event_count = document.getElementById('newMonitor[PreEventCount]');
  console.log(pre_event_count.value ,'>', max_image_buffer_count.value, this);
  if (parseInt(pre_event_count.value) > parseInt(max_image_buffer_count.value)) {
    if (this.id=='newMonitor[PreEventCount]') {
      max_image_buffer_count.value=pre_event_count.value;
    } else {
      pre_event_count.value = max_image_buffer_count.value;
    }
  }
      
  update_estimated_ram_use();
}
function update_estimated_ram_use() {
  const width = document.querySelectorAll('input[name="newMonitor[Width]"]')[0].value;
  const height = document.querySelectorAll('input[name="newMonitor[Height]"]')[0].value;
  const colours = document.querySelectorAll('select[name="newMonitor[Colours]"]')[0].value;

  let min_buffer_count = parseInt(document.querySelectorAll('input[name="newMonitor[ImageBufferCount]"]')[0].value);
  min_buffer_count += parseInt(document.getElementById('newMonitor[PreEventCount]').value);
  const min_buffer_size = min_buffer_count * width * height * colours;
  document.getElementById('estimated_ram_use').innerHTML = 'Min: ' + human_filesize(min_buffer_size);

  const max_buffer_count = parseInt(document.getElementById('newMonitor[MaxImageBufferCount]').value);
  if (max_buffer_count) {
    var max_buffer_size = (min_buffer_count + max_buffer_count) * width * height * colours;
    document.getElementById('estimated_ram_use').innerHTML += ' Max: ' + human_filesize(max_buffer_size);
  } else {
    document.getElementById('estimated_ram_use').innerHTML += ' Max: Unlimited';
  }
}

function updateLatitudeAndLongitude(latitude, longitude) {
  var form = document.getElementById('contentForm');
  form.elements['newMonitor[Latitude]'].value = latitude;
  form.elements['newMonitor[Longitude]'].value = longitude;
}
function getLocation() {
  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition((position) => {
      updateLatitudeAndLongitude(position.coords.latitude, position.coords.longitude);
    });
  } else {
    console.log("Geolocation not available");
  }
}

window.addEventListener('DOMContentLoaded', initPage);
