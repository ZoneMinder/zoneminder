# How to upgrade from 0.3 to 0.4

There are a number of changes to the plugin in 0.4 that may break peoples implementations of Leaflet.draw 0.3.

### L.Tooltip is now L.Draw.Tooltip

```
    var tooltip = new L.Tooltip(map);
```

Is now

```
    var tooltip = new L.Draw.Tooltip(map);
```

See ./src/Tooltip.js

# How to upgrade from 0.1 to 0.2

There are a number of changes to the plugin in 0.2 that may break peoples implementations of Leaflet.draw 0.1. I will try my best to list any changes here.

## Event consolidation

Leaflet.draw 0.1 had a created event for each different shape that was created. 0.2 now consolidates these into a single created shape.

The vector or marker is accessed by the `layer` property of the event arguments, the type of layer by the `layerType`.

#### New way

```js
map.on(L.Draw.Event.CREATED, function (e) {
	var type = e.layerType,
		layer = e.layer;

	if (type === 'marker') {
		// Do any marker specific logic here
	}

	map.addLayer(layer);
});
```

#### Old way

```js
map.on('draw:poly-created', function (e) {
	map.addLayer(e.poly);
});
map.on('draw:rectangle-created', function (e) {
	map.addLayer(e.rect);
});
map.on('draw:circle-created', function (e) {
	map.addLayer(e.circ);
});
map.on('draw:marker-created', function (e) {
	e.marker.bindPopup('A popup!');
	map.addLayer(e.marker);
});
```

## Draw handler started/stopped event change

Renamed the drawing started and stopped events to be the same as the created standard.

`drawing` -> `draw:drawstart` and `drawing-disabled` -> `draw:drawstop`.

The event argument has also changed from `drawingType` -> `layerType`.

## CSS changes

There has been a whole bunch of CSS changes, if you have customized any of these please see [leaflet.draw.css](https://github.com/Leaflet/Leaflet.draw/blob/master/dist/leaflet.draw.css).
