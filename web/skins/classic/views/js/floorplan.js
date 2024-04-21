/*
leaflet draw documentation:
https://leaflet.github.io/Leaflet.draw/docs/leaflet-draw-latest.html

pending:
customize icon: https://stackoverflow.com/questions/41031817/place-custom-markers-for-leaflet-draw-marker-property
*/

	
// Create the map object.
const fpmap = L.map( 'leaflet-map', {
//    center: [ 51.1642, 10.4541 ],
    minZoom: -3,
    maxZoom: 5,
//    zoomDelta: 1,
    zoomSnap: 0.01,
    scrollWheelZoom: false,
    trackResize: true,
    attributionControl: false,
    crs: L.CRS.Simple
} );
 
// Define the image overlay and its boundaries.L.marker([52.5162746,13.3777041]).addTo(map);
//const imageUrl = './floorplan.png'; // we need to define if we will have a hardcoded name for the floorplan
const fp_imageUrl = './graphics/floorplan.jpg'; // we need to define if we will have a hardcoded name for the floorplan
const fp_imageBounds = [
    [ -100, -100 ],
    [ 500, 1000],
];
 
// Add the overlay to the map.
L.imageOverlay( fp_imageUrl, fp_imageBounds ).addTo( fpmap );

 
// Automatically zoom the map to the boundaries.
fpmap.fitBounds( fp_imageBounds );


// FeatureGroup is to store editable layers
var drawnItems = new L.FeatureGroup();
fpmap.addLayer(drawnItems);
var drawControl = new L.Control.Draw({
// control toolbar
    position : 'topleft',
    draw : {
//        position : 'topleft',
        polygon : true,
        polyline : false,
        rectangle : false,
        circle : false,
        circlemarker: false

    }, 

//
   edit: {
        featureGroup: drawnItems
   }
});


fpmap.addControl(drawControl);

// load saved info in database.
document.onreadystatechange = function () {
  if (document.readyState == "complete") {
//       alert ("it should be loading shapes now...");
       document.getElementById("loadBtn").addEventListener("click",getInfo);
//       fpmap.fitBounds( fp_imageBounds );
//       alert ("shapes loaded...");
  }
}

fpmap.on('draw:created', function (e) {
    var type = e.layerType,
        layer = e.layer;
        // create properties for layer to store custom data
        feature = layer.feature = layer.feature || {}; // Initialize feature
        feature.type = feature.type || "Feature"; // Initialize feature.type
        var props = feature.properties = feature.properties || {}; // Initialize feature.properties
        props.mId = L.stamp(layer);
        props.isMon = 0;

    if (type === 'marker') {
        // Do marker specific actions
        // 1. get monitor ID
        var monid = getmonitorID(layer);
        // 2. create props in layer to store custom data
        props.mId = monid;
        props.isMon = 1;
        layer.mid = monid;
        // set monitorId label as tooltip to marker
//        var wlink = 'http://10.0.3.220/zm/index.php?view=watch&mid='+layer.mid;
//        layer.bindPopup(layer.mid+' '+'<a href="'+wlink+'" target="_blank">open</a>');
//        layer.bindPopup(layer.mId+' '+'<a href="'+monurl(layer.mId)+'" target="_blank">open</a>', {permanent:true, direction:'top'});
        layer.bindPopup(props.mId+' '+'<a href="'+monurl(props.mId)+'" target="_blank">open</a>', {permanent:true, direction:'top'});


/*        layer.bindTooltip(layer.mid, {permanent:true, direction:'top'})
        alert("Double click on marker to watch monitor...");
        layer.on('dblclick', function(){
                 window.open('http://10.0.3.220/zm/index.php?view=watch&mid='+layer.mid,'_blank');
               })*/
    }

    // Do whatever else you need to. (save to db, add to map etc)
    // create JSON to save to database
    var geojson = e.layer.toGeoJSON();
    var geostr = JSON.stringify(geojson);
    // save data to database
    fetch('./fp_insert.php?mid='+props.mId+'&ismon='+props.isMon+'&json='+geostr)
    // finally add layer to the map
    drawnItems.addLayer(layer);

});


fpmap.on('draw:edited', function (e) {
    // Update db to save latest changes.
    var layers = e.layers;
    // this is due the fact that it returns a list of all modified layers...
    layers.eachLayer(function (layer) {
    //    console.log(layer);
    var mId = layer.feature.properties.mId;
    var geojson = layer.toGeoJSON();
    var geostr = JSON.stringify(geojson);
    fetch('./fp_modify.php?mid='+mId+'&json='+geostr)
   });
});

fpmap.on('draw:deleted', function (e) {
    // Update db to save latest changes.
    var layers = e.layers;
    // this is due the fact that it returns a list of all modified layers...
    layers.eachLayer(function (layer) {
    //    console.log(layer);
    var mId = layer.feature.properties.mId;
    fetch('./fp_delete.php?mid='+mId)
   });


});


function addMarker(e){
    // Add marker to map at click location; add popup window
//    var newMarker = new L.marker(e.latlng).addTo(map).on('click', e => e.target.remove()); //click to delete
    var newMarker = new L.marker(e.latlng).addTo(fpmap).on('contextmenu', e => e.target.remove()); //right click to delete
}

var getmonitorID = function(layer) {
        while (true){
        var mid = prompt('please, enter the monitor ID', 'Monitor ID#');
            if (!isNaN(mid)) {
                 break;
               }
            else {
                 alert("Please enter a valid monitor ID");
                 }
            }
        return mid;
};


var getShapeType = function(layer) {

    if (layer instanceof L.Circle) {
        return 'circle';
    }

    if (layer instanceof L.Marker) {
        return 'marker';
    }

    if ((layer instanceof L.Polyline) && ! (layer instanceof L.Polygon)) {
        return 'polyline';
    }

    if ((layer instanceof L.Polygon) && ! (layer instanceof L.Rectangle)) {
        return 'polygon';
    }

    if (layer instanceof L.Rectangle) {
        return 'rectangle';
    }

};


function getInfo() {
//   $.getJSON("./fp_getinfo.php", function (data) {
   jQuery.getJSON("fp_getinfo.php", function (data) {
//       console.log(data);
//       console.log(data.length);
       for (var i = 0; i < data.length; i++) {
            var myGeoJson = L.geoJSON(JSON.parse(data[i])); // just parse data
            var newlayer = myGeoJson.getLayers()[0]; // create new layer
            newlayer = myGeoJson.getLayers()[0];
            newlayer.mId = newlayer.feature.properties.mId;
            L.stamp(newlayer);
            if (getShapeType(newlayer) === 'marker') 
                {               
//                   var wlink = 'http://10.0.3.220/zm/index.php?view=watch&mid='+newlayer.mId;
                   // set tooltip
//                   newlayer.bindTooltip(newlayer.mId+' '+'<a href="'+wlink+'" target="_blank">open</a>', {permanent:true, direction:'top'});
//                   newlayer.bindPopup(newlayer.mId+' '+'<a href="'+wlink+'" target="_blank">open</a>', {permanent:true, direction:'top'});
                   newlayer.bindPopup(newlayer.mId+' '+'<a href="'+monurl(newlayer.mId)+'" target="_blank">open</a>', {permanent:true, direction:'top'});
//                   console.log(wlink);
                }
            // add object in array to drawnItems
            drawnItems.addLayer(newlayer);
            }; //end for
        }); // end Ajax

  }; //end function get info





function monurl(mId){
   var path = 'http://10.0.3.220/zm/index.php?view=watch&mid=';
   return path+mId;
}
