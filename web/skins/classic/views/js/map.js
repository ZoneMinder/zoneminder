/*
* En la variable map esta inicialiando el mapa para ellos usamos la constante L que instancia leaflet para el manejo de mapas.
* el setView se usa para decirle a leaflet donde se centrar nuestro mapa y le pasamos lo valores de latitud y longitud, el valor de 10 indica el nivel de zoom por defecto.
*/
const map = L.map('map-template', {
  center: L.latLng(44.30597010, -80.39925430),
  zoom : 8 });

L.tileLayer(ZM_OPT_GEOLOCATION_TILE_PROVIDER, {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
        maxZoom: 18,
        id: 'mapbox/streets-v11',
        tileSize: 512,
        zoomOffset: -1,
        accessToken: ZM_OPT_GEOLOCATION_ACCESS_TOKEN,
        detectRetina: true
      }).addTo(map);
map.invalidateSize();

//L.Control.geocoder().addTo(map);

var iconoDesconectada = L.icon({
    iconUrl: 'skins/classic/graphics/icon-disconnected.png',
    iconSize:     [25, 41], // size of the icon
    iconAnchor:   [13, 41], // point of the icon which will correspond to marker's location
});    

var iconoConectada = L.icon({
    iconUrl: 'skins/classic/graphics/icon-connected.png',
    iconSize:     [25, 41], // size of the icon
    iconAnchor:   [13, 41], // point of the icon which will correspond to marker's location
}); 

var iconoError = L.icon({
    iconUrl: 'skins/classic/graphics/icon-error.png',
    iconSize:     [25, 41], // size of the icon
    iconAnchor:   [13, 41], // point of the icon which will correspond to marker's location
}); 

//-------------Agregado por Matias Figueroa ------------
//Funcion para obtener direccion del servidor
var wwwUrlPath = window.document.location.href;
// Obtengo el directorio después de la dirección del host,
var pathName = window.document.location.pathname;
var pos = wwwUrlPath.indexOf(pathName);
// Dirección del servidor
var localhostPath = wwwUrlPath.substring(0, pos);
//-------------------Fin agregado------------------

const server = new Server(Servers[serverId]);

//const requestURL = server.UrlToApi()+'/monitors.json'+(auth_hash ? '?auth=' + auth_hash : '');
const requestURL = 'https://zm.connortechnology.com/api/monitors.json'+(auth_hash ? '?auth=' + auth_hash : '');
const request = new XMLHttpRequest();
var monitors = {};
request.open('GET', requestURL, open);
request.responseType = 'json';
request.send();

request.onload = function() {
  if (!request.response) {
    alert("Error gettings monitor data");
    return;
  }

  monitors = request.response;
  
  console.log(monitors);

    var cantidadMonitors =  monitors.monitors.length;
    var lat;
    var long;
    var nombre;
    var estado;
    var fps;
    var res;
    var id;
    var events;
    var cant_conectadas = 0;
    var cant_desconectadas = 0;
    var cant_error = 0;
    let total_cameras = 0;
    var div_conectadas =  document.getElementById("progress-conectadas");
    var div_desconectadas = document.getElementById("progress-desconectadas");
    var div_error = document.getElementById("progress-error");
    
    for (let i=0, len = monitors.monitors.length; i < len; i++) {
      const monitor = monitors["monitors"][i]["Monitor"];
      console.log(monitor);
      const monitor_status = monitors["monitors"][i]["Monitor_Status"];
      const event_summary = monitors["monitors"][i]["Event_Summary"];
      const server = new Server(Servers[monitor.ServerId]);

      id      = monitor["Id"];
      nombre  = monitors["monitors"][i]["Monitor"]["Name"];
      estado  = monitors["monitors"][i]["Monitor_Status"]["Status"];
      fps     = monitors["monitors"][i]["Monitor_Status"]["CaptureFPS"];
      res     = monitors["monitors"][i]["Monitor"]["Width"]+"x"+monitors["monitors"][i]["Monitor"]["Height"];
      events = monitors["monitors"][i]["Event_Summary"]["TotalEvents"];
        
      if (!(monitor.Latitude && monitor.Longitude)) {
        console.log("Monitor", monitor.Name, "Has no latitude or longitude");
        continue;
      }

      const url_to_zms = server.UrlToZMS()+'?scale=60&mode=single&monitor='+id+(auth_relay?'&'+auth_relay:'');
      const popup = monitor.Name+'<br>Resolution: '+res+'<br>FPS: '+fps+'<br><a href="?view=watch&mid='+id+'"><img style="width: 100%;" src="'+url_to_zms+'"/></a><br/>Events: '+events;
      if (event_summary.TotalEvents > 0) {
        if (monitor_status.Status == 'Connected') {
          L.marker([monitor.Latitude, monitor.Longitude], {icon: iconoConectada})
            .addTo(map).bindPopup(popup);
          cant_conectadas += 1;
        } else {
          L.marker([monitor.Latitude, monitor.Longitude], {icon: iconoDesconectada})
            .addTo(map).bindPopup(popup);
          cant_desconectadas += 1;
        }
      } else {
        L.marker([monitor.Latitude, monitor.Longitude], {icon: iconoError})
          .addTo(map).bindPopup(popup);
        cant_error += 1;
      }
      total_cameras += 1;
    }  // end foreach monitor
    var percent_conectadas = Number.parseFloat(((cant_conectadas*100)/total_cameras)).toFixed(2);
    var percent_desconectadas = Number.parseFloat(((cant_desconectadas*100)/total_cameras)).toFixed(2);
    var percent_error = Number.parseFloat(((cant_error*100)/total_cameras)).toFixed(2);
    
    div_conectadas.style.width = percent_conectadas+"%";
    div_conectadas.innerHTML = percent_conectadas+"%";
    document.getElementById("progress-conectadas-titulo").style.width = percent_conectadas+"%";
    document.getElementById("progress-conectadas-titulo").innerHTML = "CONECTADAS";
    
    div_desconectadas.style.width = percent_desconectadas+"%";
    div_desconectadas.innerHTML = percent_desconectadas+"%";
    document.getElementById("progress-desconectadas-titulo").style.width = percent_desconectadas+"%";
    document.getElementById("progress-desconectadas-titulo").innerHTML = "DESCONECTADAS";
    
    div_error.style.width = percent_error+"%";
    div_error.innerHTML = percent_error+"%";
    document.getElementById("progress-error-titulo").style.width = percent_error+"%";
    document.getElementById("progress-error-titulo").innerHTML = "ERROR";
  }
