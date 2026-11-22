// Leaflet is an optional install (js/leaflet); without it there is no L to draw with.
if (window.L) {
  const map = new ZMMap();
  map.addMonitors();
} else {
  const template = document.getElementById('map-template');
  template.classList.add('alert', 'alert-warning');
  template.textContent = MAP_UNAVAILABLE;
  console.log('Map view: leaflet not installed.');
}
