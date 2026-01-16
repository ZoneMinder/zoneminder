
var map = null;
var marker = null;
var sourceFormMonitor = null;

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

function Janus_Use_RTSP_Restream_onclick(e) {
  Janus_Use_RTSP_Restream = $j('[name="newMonitor[Janus_Use_RTSP_Restream]"]');
  if (Janus_Use_RTSP_Restream.length) {
    const Janus_RTSP_User = $j('#Janus_RTSP_User');
    if (Janus_Use_RTSP_Restream[0].checked) {
      Janus_RTSP_User.show();
    } else {
      Janus_RTSP_User.hide();
    }
  } else {
    console.log("Didn't find newMonitor[Janus_Use_RTSP_Restream]");
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
    if (validateForm(this)) {
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
      if (e.target.value) {
        $j('#newMonitor\\[AlarmMaxFPS\\]').show();
      } else {
        $j('#newMonitor\\[AlarmMaxFPS\\]').hide();
      }
    };
  });
  document.querySelectorAll('select[name="newMonitor[Devices]"]').forEach(function(el) {
    el.onchange = window['devices_onchange'].bind(el, el);
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

  document.querySelectorAll('select[name="newMonitor[VideoWriter]"]').forEach(function(el) {
    el.onchange = function() {
      if (this.value == 1 /* Encode */) {
        $j('.OutputCodec').show();
        $j('.WallClockTimeStamps').hide();
        $j('.Encoder').show();
      } else {
        $j('.WallClockTimeStamps').show();
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
          } else if ( this.value == 167 /* vp9 */ ) {
            option.disabled = !(option.value.includes('vp9'));
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

  var sourceFormMonitor = $j('#contentForm').serialize();
  // Manage the ZONES Button
  document.getElementById("zones-tab").addEventListener("click", function onZonesClick(evt) {
    if ($j('#contentForm').serialize() !== sourceFormMonitor) {
      evt.preventDefault();
      const data = {
        request: "modal",
        modal: "saveconfirm",
        key: messageSavingDataWhenLeavingPage
      };

      if (!document.getElementById('saveConfirm')) {
        // Load the save confirmation modal into the DOM
        $j.getJSON(thisUrl, data)
            .done(function(data) {
              insertModalHtml('saveConfirm', data.html);
              manageSaveConfirmModalBtns();
              $j('#saveConfirm').modal('show');
            })
            .fail(function(jqXHR) {
              console.log('error getting saveconfirm', jqXHR);
              logAjaxFail(jqXHR);
            });
        return;
      } else {
        document.getElementById('saveConfirmBtn').disabled = false; // re-enable the button
        $j('#saveConfirm').modal('show');
      }
    } else {
      const href = '?view=zones&mid='+mid;
      window.location.assign(href);
    }
  });

  // Manage the SAVE CONFIRMATION modal button
  function manageSaveConfirmModalBtns() {
    const href = '?view=zones&mid='+mid;
    document.getElementById('saveConfirmBtn').addEventListener('click', function onSaveConfirmClick(evt) {
      document.getElementById('saveConfirmBtn').disabled = true; // prevent double click
      evt.preventDefault();
      saveMonitorData(href);
    });

    // Manage the Don't SAVE modal button
    document.getElementById('dontSaveBtn').addEventListener('click', function onSaveConfirmClick(evt) {
      evt.preventDefault();
      window.location.assign(href);
    });

    // Manage the CANCEL modal button
    document.getElementById('saveCancelBtn').addEventListener('click', function onSaveCancelClick(evt) {
      $j('#saveConfirm').modal('hide');
    });
  }

  // Manage the SAVE Button
  document.getElementById("saveBtn").addEventListener("click", function onZonesClick(evt) {
    saveMonitorData();
  });

  const form = document.getElementById('contentForm');

  //manage the Janus settings div

  const janusEnabled = form.elements['newMonitor[JanusEnabled]'];
  if (janusEnabled) {
    janusEnabled.onclick = update_players;
    if (janusEnabled.checked) {
      document.getElementById("FunctionJanusAudioEnabled").hidden = false;
      document.getElementById("FunctionJanusProfileOverride").hidden = false;
      document.getElementById("FunctionJanusUseRTSPRestream").hidden = false;
      document.getElementById("FunctionJanusRTSPSessionTimeout").hidden = false;
    } else {
      document.getElementById("FunctionJanusAudioEnabled").hidden = true;
      document.getElementById("FunctionJanusProfileOverride").hidden = true;
      document.getElementById("FunctionJanusUseRTSPRestream").hidden = true;
      document.getElementById("FunctionJanusRTSPSessionTimeout").hidden = true;
    }

    janusEnabled.addEventListener('change', function() {
      if (this.checked) {
        document.getElementById("FunctionJanusAudioEnabled").hidden = false;
        document.getElementById("FunctionJanusProfileOverride").hidden = false;
        document.getElementById("FunctionJanusUseRTSPRestream").hidden = false;
        document.getElementById("FunctionJanusRTSPSessionTimeout").hidden = false;
      } else {
        document.getElementById("FunctionJanusAudioEnabled").hidden = true;
        document.getElementById("FunctionJanusProfileOverride").hidden = true;
        document.getElementById("FunctionJanusUseRTSPRestream").hidden = true;
        document.getElementById("FunctionJanusRTSPSessionTimeout").hidden = true;
      }
    });

    const Janus_Use_RTSP_Restream = form.elements['newMonitor[Janus_Use_RTSP_Restream]'];
    if (Janus_Use_RTSP_Restream) {
      Janus_Use_RTSP_Restream.onclick = Janus_Use_RTSP_Restream_onclick;
    }
  }

  //Manage the RTSP2Web settings div
  const RTSP2WebEnabled = form.elements['newMonitor[RTSP2WebEnabled]'];
  const Go2RTCEnabled = form.elements['newMonitor[Go2RTCEnabled]'];
  if (Go2RTCEnabled) Go2RTCEnabled.onclick = update_players;
  if (RTSP2WebEnabled) RTSP2WebEnabled.onclick = update_players;

  if (RTSP2WebEnabled || Go2RTCEnabled) {
    if (Go2RTCEnabled.checked || RTSP2WebEnabled.checked) {
      document.getElementById("RTSP2WebStream").hidden = false;
    } else {
      document.getElementById("RTSP2WebStream").hidden = true;
    }

    Go2RTCEnabled.addEventListener('change', function() {
      if (this.checked || RTSP2WebEnabled.checked) {
        document.getElementById("RTSP2WebStream").hidden = false;
      } else {
        document.getElementById("RTSP2WebStream").hidden = true;
      }
    });

    RTSP2WebEnabled.addEventListener('change', function() {
      if (this.checked || Go2RTCEnabled.checked) {
        document.getElementById("RTSP2WebStream").hidden = false;
      } else {
        document.getElementById("RTSP2WebStream").hidden = true;
      }
    });
  }
  update_players();

  const monitorPath = document.getElementsByName("newMonitor[Path]")[0];
  if (monitorPath) {
    monitorPath.addEventListener('keyup', change_Path); // on edit sync path -> user & pass
    monitorPath.addEventListener('blur', change_Path); // remove fields from path if user & pass equal on end of edit

    const monitorUser = document.getElementsByName("newMonitor[User]");
    if ( monitorUser.length > 0 ) {
      monitorUser[0].addEventListener('blur', change_Path); // remove fields from path if user & pass equal
    }

    const monitorPass = document.getElementsByName("newMonitor[Pass]");
    if ( monitorPass.length > 0 ) {
      monitorPass[0].addEventListener('blur', change_Path); // remove fields from path if user & pass equal
    }
  }

  if (form.elements['newMonitor[Type]'].value == 'WebSite') return;

  if (parseInt(ZM_OPT_USE_GEOLOCATION)) {
    if (window.L) {
      const latitude = form.elements['newMonitor[Latitude]'].value;
      const longitude = form.elements['newMonitor[Longitude]'].value;
      map = L.map('LocationMap', {
        center: L.latLng(latitude, longitude),
        zoom: 8,
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
      marker = L.marker([latitude, longitude], {draggable: 'true'});
      marker.addTo(map);
      marker.on('dragend', function(event) {
        const marker = event.target;
        const position = marker.getLatLng();
        const form = document.getElementById('contentForm');
        form.elements['newMonitor[Latitude]'].value = position.lat;
        ll2dms(form.elements['newMonitor[Latitude]']);
        form.elements['newMonitor[Longitude]'].value = position.lng;
        ll2dms(form.elements['newMonitor[Longitude]']);
      });
      map.invalidateSize();
      $j("a[href='#pills-location']").on('shown.bs.tab', function(e) {
        map.invalidateSize();
      });
    } else {
      console.log('Location turned on but leaflet not installed.');
    }
    ll2dms(form.elements['newMonitor[Latitude]']);
    ll2dms(form.elements['newMonitor[Longitude]']);
  } // end if ZM_OPT_USE_GEOLOCATION

  updateLinkedMonitorsUI();

  // Setup the thumbnail video animation
  if (!isMobile()) initThumbAnimation();

  manageChannelStream();
} // end function initPage()

function saveMonitorData(href = '') {
  const alertBlock = $j("#alertSaveMonitorData");
  const form_data = $j("#contentForm").serializeArray();
  alertBlock.fadeIn({duration: 'fast'});
  form_data.push({name: "action", value: "save"});
  $j.ajax({
    type: "POST",
    url: "?view=monitor",
    data: form_data,
    success: function() {
      alertBlock.fadeOut({duration: 'fast'});
      if (href) window.location.assign(href);
      //document.getElementById('zones-tab').classList.remove("disabled");
    },
    error: function() {
      alertBlock.fadeOut({duration: 'fast'});
      alert('An error occurred while saving data.'); /* Proper formatting required! */
    }
  });
  $j('#saveConfirm').modal('hide');
}

function ll2dms(input) {
  const latitude = document.getElementById('newMonitor[Latitude]');
  if (latitude.value === '') return;
  if (latitude.value < -90) latitude.value=-90;
  if (latitude.value > 90) latitude.value=90;

  const longitude = document.getElementById('newMonitor[Longitude]');
  if (longitude.value === '') return;
  if (longitude.value < -180) longitude.value=-180;
  if (longitude.value > 180) longitude.value=180;
  const dmsCoords = new DmsCoordinates(parseFloat(latitude.value), parseFloat(longitude.value));

  if (input.id == 'newMonitor[Latitude]') {
    const dms = document.getElementById('LatitudeDMS');
    dms.value = dmsCoords.latitude.toString(2);
  } else if (input.id == 'newMonitor[Longitude]') {
    const dms = document.getElementById('LongitudeDMS');
    dms.value = dmsCoords.longitude.toString(2);
  } else {
    console.log("Unknown input in ll2dms");
  }
  updateMarker();
}

function dms2ll(input) {
  const latitude = document.getElementById('newMonitor[Latitude]');
  const longitude = document.getElementById('newMonitor[Longitude]');
  const dms = parseDms(input.value);

  if (input.id == 'LatitudeDMS') {
    latitude.value = dms.toFixed(8);
  } else if (input.id == 'LongitudeDMS') {
    longitude.value = dms.toFixed(8);
  } else {
    console.log('Unknown input in dms2ll');
  }
  updateMarker();
}

function change_Path(event) {
  const pathInput = document.getElementsByName("newMonitor[Path]")[0];

  const protoPrefixPos = pathInput.value.indexOf('://');
  if ( protoPrefixPos == -1 ) {
    return;
  }

  // check the formatting of the url
  const authSeparatorPos = pathInput.value.indexOf( '@', protoPrefixPos+3 );
  if ( authSeparatorPos == -1 ) {
    console.log('ignoring URL without "@"');
    return;
  }

  const fieldsSeparatorPos = pathInput.value.indexOf( ':', protoPrefixPos+3 );
  if ( authSeparatorPos == -1 || fieldsSeparatorPos >= authSeparatorPos ) {
    console.warn('ignoring URL incorrectly formatted, missing ":"');
    return;
  }

  const usernameValue = pathInput.value.substring( protoPrefixPos+3, fieldsSeparatorPos );
  const passwordValue = pathInput.value.substring( fieldsSeparatorPos+1, authSeparatorPos );
  if ( usernameValue.length == 0 || passwordValue.length == 0 ) {
    console.warn('ignoring URL incorrectly formatted, empty username or password');
    return;
  }

  // get the username / password inputs
  const userInput = document.getElementsByName("newMonitor[User]");
  const passInput = document.getElementsByName("newMonitor[Pass]");

  if (userInput.length != 1 || passInput.length != 1) {
    // If we didn't find the inputs
    return;
  }

  // on editing update the fields only if they are empty or a prefix of the new value
  if (event.type != 'blur') {
    userInput[0].value = usernameValue;
    passInput[0].value = passwordValue;
    return;
  }

  // on leaving the input sync the values and remove it from the url
  // only if they already match (to not overwrite already present values)
  if ( userInput[0].value == usernameValue && passInput[0].value == decodeURI(passwordValue) ) {
    pathInput.value = pathInput.value.substring(0, protoPrefixPos+3) + pathInput.value.substring(authSeparatorPos+1, pathInput.value.length);
  }
}

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
  if (parseInt(max_image_buffer_count.value)
    &&
    (parseInt(pre_event_count.value) > parseInt(max_image_buffer_count.value))
  ) {
    if (this.id == 'newMonitor[PreEventCount]') {
      max_image_buffer_count.value = pre_event_count.value;
    } else {
      pre_event_count.value = max_image_buffer_count.value;
    }
  }
  update_estimated_ram_use();
}
function update_estimated_ram_use() {
  const form = document.getElementById('contentForm');
  if (form.elements['newMonitor[Type]'].value == 'WebSite') return;

  const width = document.querySelectorAll('input[name="newMonitor[Width]"]')[0].value;
  const height = document.querySelectorAll('input[name="newMonitor[Height]"]')[0].value;
  const colours = document.querySelectorAll('select[name="newMonitor[Colours]"]')[0].value;

  let min_buffer_count = parseInt(document.querySelectorAll('input[name="newMonitor[ImageBufferCount]"]')[0].value);
  min_buffer_count += parseInt(document.getElementById('newMonitor[PreEventCount]').value);
  const min_buffer_size = min_buffer_count * width * height * colours;
  document.getElementById('estimated_ram_use').innerHTML = 'Min: ' + human_filesize(min_buffer_size);

  const max_buffer_count = parseInt(document.getElementById('newMonitor[MaxImageBufferCount]').value);
  if (max_buffer_count) {
    const max_buffer_size = (min_buffer_count + max_buffer_count) * width * height * colours;
    document.getElementById('estimated_ram_use').innerHTML += ' Max: ' + human_filesize(max_buffer_size);
  } else {
    document.getElementById('estimated_ram_use').innerHTML += ' Max: Unlimited';
  }
}

function updateMarker() {
  const latitude = document.getElementById('newMonitor[Latitude]').value;
  const longitude = document.getElementById('newMonitor[Longitude]').value;
  if (typeof L !== 'undefined') {
    const latlng = new L.LatLng(latitude, longitude);
    marker.setLatLng(latlng);
    map.setView(latlng, 8, {animation: true});
    setTimeout(function() {
      map.invalidateSize(true);
    }, 100);
  } else {
    console.log('You must install leaflet');
  }
}

function updateLatitudeAndLongitude(latitude, longitude) {
  var form = document.getElementById('contentForm');
  form.elements['newMonitor[Latitude]'].value = latitude;
  form.elements['newMonitor[Longitude]'].value = longitude;
  updateMarker(latitude, longitude);
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

function Capturing_onChange(e) {
}

function Analysing_onChange(e) {
}

function Recording_onChange(e) {
}

function SecondPath_onChange(e) {
  if (e.value) {
    $j('#AnalysingSource').show();
    $j('#RecordingSource').show();
  } else {
    $j('#AnalysingSource').hide();
    $j('#RecordingSource').hide();
  }
}

function update_players() {
  const dropdown = $j('[name="newMonitor[DefaultPlayer]"]');
  if (!dropdown.length) {
    console.log("No element found for DefaultPlayer");
    return;
  }
  const form = dropdown[0].form;
  const selected_value = dropdown.val() || '';
  const go2rtc_enabled = form.elements['newMonitor[Go2RTCEnabled]'] && form.elements['newMonitor[Go2RTCEnabled]'].checked;
  const rtsp2web_enabled = form.elements['newMonitor[RTSP2WebEnabled]'] && form.elements['newMonitor[RTSP2WebEnabled]'].checked;
  const janus_enabled = form.elements['newMonitor[JanusEnabled]'] && form.elements['newMonitor[JanusEnabled]'].checked;

  dropdown.empty();
  $j.each(players, function(key, entry) {
    if (
      ((-1 != key.indexOf('go2rtc')) && !go2rtc_enabled)
      ||
      ((-1 != key.indexOf('rtsp2web')) && !rtsp2web_enabled)
      ||
      ((-1 != key.indexOf('janus')) && !janus_enabled)
    ) {
      console.log("not adding ", key, go2rtc_enabled, rtsp2web_enabled, janus_enabled);
    } else {
      dropdown.append($j('<option></option>').attr('value', key).text(entry));
    }
  });
  //dropdown.chosen("destroy");
  //dropdown.chosen();
  dropdown.val(selected_value);
  if (dropdown[0].selectedIndex==-1) dropdown[0].selectedIndex = 0;
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
    ManufacturerId_select.form.elements['newMonitor[Manufacturer]'].disabled = true;
    populate_models(ManufacturerId_select.value);
  } else {
    ManufacturerId_select.form.elements['newMonitor[Manufacturer]'].style['display'] = '';
    ManufacturerId_select.form.elements['newMonitor[Manufacturer]'].disabled = false;
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
    $j('[name="newMonitor[Model]"]').hide().prop('disabled', true);
  } else {
    $j('[name="newMonitor[Model]"]').show().prop('disabled', false);
  }
}

function Model_onchange(input) {
  select_by_value_case_insensitive(input.form.elements['newMonitor[ModelId]'], input.value);
}

function updateLinkedMonitorsUI() {
  expr_to_ui($j('[name="newMonitor[LinkedMonitors]"]').val(), $j('#LinkedMonitorsUI'));
}

function devices_onchange(devices) {
  const selected = $j(devices).val();
  const device = devices.form.elements['newMonitor[Device]'];
  if (selected !== '') {
    device.value = selected;
    device.style['display'] = 'none';
  } else {
    device.style['display'] = 'inline';
  }
}
function ControlId_onChange(ddm) {
  const ControlEditButton = document.getElementById('ControlEditButton');
  if (ControlEditButton) ControlEditButton.disabled = ddm.value ? false : true;
}

function ControlEdit_onClick() {
  const ControlId = document.getElementById('ControlId');
  if (ControlId) {
    window.location = '?view=controlcap&cid='+ControlId.value;
  }
}

function ControlList_onClick() {
  window.location = '?view=options&tab=control';
}

window.addEventListener('DOMContentLoaded', initPage);
