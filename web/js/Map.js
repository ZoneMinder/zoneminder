'use strict';

var _createClass = function() {
  function defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  } return function(Constructor, protoProps, staticProps) {
    if (protoProps) defineProperties(Constructor.prototype, protoProps);
    if (staticProps) defineProperties(Constructor, staticProps);
    return Constructor;
  };
}();

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

var ZMMap = function() {
  function ZMMap(json) {
    _classCallCheck(this, ZMMap);

    for (var k in json) {
      this[k] = json[k];
    }

    this.map = L.map('map-template', {
      center: L.latLng(44.30597010, -80.39925430),
      zoom: 4
    });

    L.tileLayer(ZM_OPT_GEOLOCATION_TILE_PROVIDER, {
      attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
      maxZoom: 18,
      id: 'mapbox/streets-v11',
      tileSize: 512,
      zoomOffset: -1,
      accessToken: ZM_OPT_GEOLOCATION_ACCESS_TOKEN,
      detectRetina: true
    }).addTo(this.map);
    this.map.invalidateSize();

    this.iconoDesconectada = L.icon({
      iconUrl: 'skins/classic/graphics/icon-disconnected.png',
      iconSize: [25, 41], // size of the icon
      iconAnchor: [13, 41], // point of the icon which will correspond to marker's location
    });

    this.iconoConectada = L.icon({
      iconUrl: 'skins/classic/graphics/icon-connected.png',
      iconSize: [25, 41], // size of the icon
      iconAnchor: [13, 41], // point of the icon which will correspond to marker's location
    });

    this.iconoError = L.icon({
      iconUrl: 'skins/classic/graphics/icon-error.png',
      iconSize: [25, 41], // size of the icon
      iconAnchor: [13, 41], // point of the icon which will correspond to marker's location
    });
  }

  _createClass(ZMMap, [
    {
      key: 'addMonitors',
      value: function addMonitors() {
        const server = new Server(Servers[serverId]);
        const get_monitors_promise = server.getFromApi('/monitors/index/Deleted:0.json');
        get_monitors_promise
            .then((response) => {
              return response.json();
            })
            .then((monitors) => {
              console.log(monitors);
              if (!monitors.monitors) return;

              let cant_connected = 0;
              let cant_disconnected = 0;
              let cant_error = 0;
              let total_cameras = 0;
              const div_connected = document.getElementById("progress-connected");
              const div_disconnected = document.getElementById("progress-disconnected");
              const div_error = document.getElementById("progress-error");

              const pins = [];

              for (let i=0, len = monitors.monitors.length; i<len; i++) {
                const monitor = monitors.monitors[i].Monitor;
                if (!monitor.Latitude || !monitor.Longitude) continue;
                if (!pins.length) {
                  pins[pins.length] = {
                    latitude: monitor.Latitude,
                    longitude: monitor.Longitude,
                    monitors: [monitor]
                  };
                  continue;
                }
                for (let ii=0; ii < pins.len; ii++) {
                  if (haversineDistance(
                      pins[ii].latitude, monitor.Latitude,
                      pins[ii].longitude, monitor.Longitude,
                  ) > 10 ) {
                    pins[ii].monitors[pins[ii].monitors.length] = monitor;
                  }
                }
              }
              for (let i=0, len = monitors.monitors.length; i<len; i++) {
                const monitor = monitors.monitors[i].Monitor;
                console.log(monitor);
                if (!(monitor.Latitude && monitor.Longitude)) {
                  console.log("Monitor", monitor.Name, "Has no latitude or longitude");
                  continue;
                }
                const monitor_status = monitors["monitors"][i]["Monitor_Status"];
                const event_summary = monitors["monitors"][i]["Event_Summary"];
                const server = new Server(Servers[monitor.ServerId]);

                const id = monitor["Id"];
                const fps = monitor_status["CaptureFPS"];
                const res = monitor["Width"]+"x"+monitor["Height"];
                const events = event_summary["TotalEvents"];

                const url_to_zms = server.urlToZMS()+'?scale=60&mode=single&monitor='+id+(auth_relay?'&'+auth_relay:'');
                const popup = '<a href="'+thisUrl+'?view=watch&mid='+monitor.Id+'">'+monitor.Name+'</a><br>Resolution: '+res+'<br>FPS: '+fps+'<br><a href="?view=watch&mid='+id+'"><img width="400" style="width: 200px;" src="'+url_to_zms+'"/></a><br/>Events: '+events;
                if (event_summary.TotalEvents > 0) {
                  if (monitor_status.Status == 'Connected') {
                    L.marker([monitor.Latitude, monitor.Longitude], {icon: this.iconoConectada})
                        .addTo(this.map).bindPopup(popup);
                    //.addTo(map).bindTooltip(popup);
                    cant_connected += 1;
                  } else {
                    L.marker([monitor.Latitude, monitor.Longitude], {icon: this.iconoDesconectada})
                        .addTo(this.map).bindPopup(popup);
                    //.addTo(map).bindTooltip(popup);
                    cant_disconnected += 1;
                  }
                } else {
                  L.marker([monitor.Latitude, monitor.Longitude], {icon: this.iconoError})
                      .addTo(this.map).bindPopup(popup);
                  cant_error += 1;
                }
                total_cameras += 1;
              } // end foreach monitor
              const percent_connected = Number.parseFloat(((cant_connected*100)/total_cameras)).toFixed(2);
              const percent_disconnected = Number.parseFloat(((cant_disconnected*100)/total_cameras)).toFixed(2);
              const percent_error = Number.parseFloat(((cant_error*100)/total_cameras)).toFixed(2);

              div_connected.style.width = percent_connected+"%";
              div_connected.innerHTML = percent_connected+"%";
              document.getElementById("progress-connected-title").style.width = percent_connected+"%";
              document.getElementById("progress-connected-title").innerHTML = 'Connected';

              div_disconnected.style.width = percent_disconnected+"%";
              div_disconnected.innerHTML = percent_disconnected+"%";
              document.getElementById("progress-disconnected-title").style.width = percent_disconnected+"%";
              document.getElementById("progress-disconnected-title").innerHTML = 'Disconnected';

              div_error.style.width = percent_error+"%";
              div_error.innerHTML = percent_error+"%";
              document.getElementById("progress-error-title").style.width = percent_error+"%";
              document.getElementById("progress-error-title").innerHTML = "ERROR";
            }); // end promise
      }
    } // end addMonitors
  ]);

  return ZMMap;
}();

function haversineDistance($lat1, $lon1, $lat2, $lon2) {
  // Convert latitude and longitude from degrees to radians
  const $lat1Rad = deg2rad($lat1);
  const $lon1Rad = deg2rad($lon1);
  const $lat2Rad = deg2rad($lat2);
  const $lon2Rad = deg2rad($lon2);

  // Calculate differences
  const $latDiff = $lat2Rad - $lat1Rad;
  const $lonDiff = $lon2Rad - $lon1Rad;

  // Haversine formula
  const $a = sin($latDiff / 2) * sin($latDiff / 2) +
    cos($lat1Rad) * cos($lat2Rad) *
    sin($lonDiff / 2) * sin($lonDiff / 2);
  const $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

  // Earth's radius in kilometers (you can use 3959 for miles)
  const $radius = 6371;

  // Calculate the distance
  const $distance = $radius * $c;

  return $distance;
}
