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
      var form = document.getElementById('contentForm');
      form.tab.value = 'general';
      form.submit();
    };
  });
  document.querySelectorAll('input[name="newMonitor[ImageBufferCount]"],input[name="newMonitor[MaxImageBufferCount]"],input[name="newMonitor[Width]"],input[name="newMonitor[Height]"],input[name="newMonitor[PreEventCount]"]').forEach(function(el) {
    el.oninput = window['update_estimated_ram_use'].bind(el);
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
      } else {
        $j('.OutputCodec').hide();
        $j('.Encoder').hide();
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

  //manage the Janus audio check
  if (document.getElementsByName("newMonitor[JanusEnabled]")[0].checked) {
    document.getElementById("FunctionJanusAudioEnabled").hidden = false;
  } else {
    document.getElementById("FunctionJanusAudioEnabled").hidden = true;
  }

  document.getElementsByName("newMonitor[JanusEnabled]")[0].addEventListener('change', function() {
    if (this.checked) {
      document.getElementById("FunctionJanusAudioEnabled").hidden = false;
    } else {
      document.getElementById("FunctionJanusAudioEnabled").hidden = true;
    }
  });

  // Amcrest API controller
  if (document.getElementsByName("newMonitor[ONVIF_Event_Listener]")[0].checked) {
    document.getElementById("function_use_Amcrest_API").hidden = false;
  } else {
    document.getElementById("function_use_Amcrest_API").hidden = true;
  }
  document.getElementsByName("newMonitor[ONVIF_Event_Listener]")[0].addEventListener('change', function() {
    if (this.checked) {
      document.getElementById("function_use_Amcrest_API").hidden = false;
    }
  });
  document.getElementsByName("newMonitor[ONVIF_Event_Listener]")[1].addEventListener('change', function() {
    if (this.checked) {
      document.getElementById("function_use_Amcrest_API").hidden = true;
    }
  });

  if ( ZM_OPT_USE_GEOLOCATION ) {
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

function update_estimated_ram_use() {
  var width = document.querySelectorAll('input[name="newMonitor[Width]"]')[0].value;
  var height = document.querySelectorAll('input[name="newMonitor[Height]"]')[0].value;
  var colours = document.querySelectorAll('select[name="newMonitor[Colours]"]')[0].value;

  var min_buffer_count = parseInt(document.querySelectorAll('input[name="newMonitor[ImageBufferCount]"]')[0].value);
  min_buffer_count += parseInt(document.querySelectorAll('input[name="newMonitor[PreEventCount]"]')[0].value);
  var min_buffer_size = min_buffer_count * width * height * colours;
  document.getElementById('estimated_ram_use').innerHTML = 'Min: ' + human_filesize(min_buffer_size);

  var max_buffer_count = parseInt(document.querySelectorAll('input[name="newMonitor[MaxImageBufferCount]"]')[0].value);
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

function populate_models(ManufacturerId) {
  const dropdown = $j('[name="newMonitor[ModelId]"]');
  if (!dropdown.length) {
    console.log("No element found for ModelId");
    return;
  }

  dropdown.empty();
  dropdown.append('<option value="" selected="true">Unknown</option>');
  dropdown.prop('selectedIndex', 0);

  if (ManufacturerId) {
    // Populate dropdown with list of provinces
    $j.getJSON(thisUrl+'?request=models&ManufacturerId='+ManufacturerId, function(data) {
      if (data.result == 'Ok') {
        $j.each(data.models, function(key, entry) {
          dropdown.append($j('<option></option>').attr('value', entry.Id).text(entry.Name));
        });
        dropdown.chosen("destroy");
        dropdown.chosen();
      } else {
        alert(data.result);
      }
    });
  }
  dropdown.chosen("destroy");
  dropdown.chosen();
}

function ManufacturerId_onchange(ManufacturerId_select) {
  if (ManufacturerId_select.value) {
    ManufacturerId_select.form.elements['newMonitor[Manufacturer]'].style['display'] = 'none';
    populate_models(ManufacturerId_select.value);
  } else {
    ManufacturerId_select.form.elements['newMonitor[Manufacturer]'].style['display'] = 'inline';
    // Set models dropdown to Unknown, text area visible
    const ModelId_dropdown = $j('[name="newMonitor[ModelId]"]');
    ModelId_dropdown.empty();
    ModelId_dropdown.append('<option selected="true">Unknown</option>');
    ModelId_dropdown.prop('selectedIndex', 0);
    $j('[name="newMonitor[Model]"]').show();
  }
}

function select_by_value_case_insensitive(dropdown, value) {
  const test_value = value.toLowerCase();
  for (i=1; i < dropdown.options.length; i++) {
    if (dropdown.options[i].text.toLowerCase() == test_value) {
      dropdown.selectedIndex = i;
      dropdown.options[i].selected = true;
      $j(dropdown).chosen("destroy").chosen();
      return;
    }
  }
  if (dropdown.selectedIndex != 0) {
    dropdown.selectedIndex = 0;
    $j(dropdown).chosen("destroy").chosen();
  }
}

function Manufacturer_onchange(input) {
  if (!input.value) {
    return;
  }
  ManufacturerId_select = input.form.elements['newMonitor[ManufacturerId]'];
  select_by_value_case_insensitive(ManufacturerId_select, input.value);
  populate_models(ManufacturerId_select.value);
}

function ModelId_onchange(ModelId_select) {
  if (parseInt(ModelId_select.value)) {
    $j('[name="newMonitor[Model]"]').hide();
  } else {
    $j('[name="newMonitor[Model]"]').show();
  }
}

function Model_onchange(input) {
  select_by_value_case_insensitive(input.form.elements['newMonitor[ModelId]'], input.value);
}

window.addEventListener('DOMContentLoaded', initPage);
