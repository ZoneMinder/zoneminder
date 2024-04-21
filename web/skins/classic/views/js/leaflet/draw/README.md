[![GitHub version](https://badge.fury.io/gh/Leaflet%2Fleaflet.draw.svg)](https://badge.fury.io/gh/Leaflet%2Fleaflet.draw)
[![npm version](https://badge.fury.io/js/leaflet-draw.svg)](https://badge.fury.io/js/leaflet-draw)
[![NPM Downloads](https://img.shields.io/npm/dt/leaflet-draw.svg)](https://www.npmjs.com/package/leaflet-draw)
[![Bower version](https://badge.fury.io/bo/leaflet.draw.svg)](https://badge.fury.io/bo/leaflet.draw)
[![Build Status](https://travis-ci.org/Leaflet/Leaflet.draw.svg?branch=master)](https://travis-ci.org/Leaflet/Leaflet.draw)
[![Leaflet.draw Chat](https://badges.gitter.im/Leaflet/Leaflet.draw.svg)](https://gitter.im/Leaflet/Leaflet.draw?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![GitHub issues](https://img.shields.io/github/issues/Leaflet/Leaflet.draw.svg)](https://github.com/Leaflet/Leaflet.draw/issues)
[![GitHub forks](https://img.shields.io/github/forks/Leaflet/Leaflet.draw.svg)](https://github.com/Leaflet/Leaflet.draw/network)
[![GitHub stars](https://img.shields.io/github/stars/Leaflet/Leaflet.draw.svg)](https://github.com/Leaflet/Leaflet.draw/stargazers)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/Leaflet/Leaflet.draw/master/MIT-LICENSE.md)

# Leaflet.draw
Adds support for drawing and editing vectors and markers on [Leaflet maps](https://github.com/Leaflet/Leaflet).

Supports [Leaflet](https://github.com/Leaflet/Leaflet/releases) 0.7.x and 1.0.0+ branches.

Please check out our [Api Documentation](https://leaflet.github.io/Leaflet.draw/docs/leaflet-draw-latest.html)

#### Upgrading from Leaflet.draw 0.1

Leaflet.draw 0.2.0 changes a LOT of things from 0.1. Please see [BREAKING CHANGES](https://github.com/Leaflet/Leaflet.draw/blob/master/BREAKINGCHANGES.md) for how to upgrade.

## In this readme

- [Customizing Language](#customizing-language-and-text-in-leafletdraw)
- [Common tasks](#common-tasks)
- [Contributing](#contributing)
- [Thanks](#thanks)

## Customizing language and text in Leaflet.draw

Leaflet.draw uses the `L.drawLocal` configuration object to set any text used in the plugin. Customizing this will allow support for changing the text or supporting another language.

See [Leaflet.draw.js](https://github.com/Leaflet/Leaflet.draw/blob/master/src/Leaflet.draw.js) for the default strings.

E.g.

```js
    // Set the button title text for the polygon button
    L.drawLocal.draw.toolbar.buttons.polygon = 'Draw a sexy polygon!';
    
    // Set the tooltip start text for the rectangle
    L.drawLocal.draw.handlers.rectangle.tooltip.start = 'Not telling...';
```

## Common tasks

The following examples outline some common tasks.

### Example Leaflet.draw config

The following example will show you how to:

1. Change the position of the control's toolbar.
2. Customize the styles of a vector layer.
3. Use a custom marker.
4. Disable the delete functionality.

```js
    var cloudmadeUrl = 'http://{s}.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/997/256/{z}/{x}/{y}.png',
        cloudmade = new L.TileLayer(cloudmadeUrl, {maxZoom: 18}),
        map = new L.Map('map', {layers: [cloudmade], center: new L.LatLng(-37.7772, 175.2756), zoom: 15 });
    
    var editableLayers = new L.FeatureGroup();
    map.addLayer(editableLayers);
    
    var MyCustomMarker = L.Icon.extend({
        options: {
            shadowUrl: null,
            iconAnchor: new L.Point(12, 12),
            iconSize: new L.Point(24, 24),
            iconUrl: 'link/to/image.png'
        }
    });
    
    var options = {
        position: 'topright',
        draw: {
            polyline: {
                shapeOptions: {
                    color: '#f357a1',
                    weight: 10
                }
            },
            polygon: {
                allowIntersection: false, // Restricts shapes to simple polygons
                drawError: {
                    color: '#e1e100', // Color the shape will turn when intersects
                    message: '<strong>Oh snap!<strong> you can\'t draw that!' // Message that will show when intersect
                },
                shapeOptions: {
                    color: '#bada55'
                }
            },
            circle: false, // Turns off this drawing tool
            rectangle: {
                shapeOptions: {
                    clickable: false
                }
            },
            marker: {
                icon: new MyCustomMarker()
            }
        },
        edit: {
            featureGroup: editableLayers, //REQUIRED!!
            remove: false
        }
    };
    
    var drawControl = new L.Control.Draw(options);
    map.addControl(drawControl);
    
    map.on(L.Draw.Event.CREATED, function (e) {
        var type = e.layerType,
            layer = e.layer;
    
        if (type === 'marker') {
            layer.bindPopup('A popup!');
        }
    
        editableLayers.addLayer(layer);
    });
```

### Changing a drawing handlers options

You can change a draw handlers options after initialisation by using the `setDrawingOptions` method on the Leaflet.draw control.

E.g. to change the colour of the rectangle:

```js
drawControl.setDrawingOptions({
    rectangle: {
    	shapeOptions: {
        	color: '#0000FF'
        }
    }
});
```

# Contributing
 
## Testing

To test you can install the npm dependencies:

    npm install

and then use:

    jake test

## Documentation

Documentation is build with Leafdoc, to generate the documentation use

    jake docs

and the generated html documentation is saved to `./docs/leaflet-draw-latest.html`

## Thanks

Touch friendly version of Leaflet.draw was created by Michael Guild (https://github.com/michaelguild13).

The touch support was initiated due to a demand for it at National Geographic for their Map Maker Projected (http://mapmaker.education.nationalgeographic.com/) that was created by Michael Guild and Daniel Schep (https://github.com/dschep)

Thanks so much to [@brunob](https://github.com/brunob), [@tnightingale](https://github.com/tnightingale), and [@shramov](https://github.com/shramov). I got a lot of ideas from their Leaflet plugins.

All the [contributors](https://github.com/Leaflet/Leaflet.draw/graphs/contributors) and issue reporters of this plugin rock. Thanks for tidying up my mess and keeping the plugin on track.

The icons used for some of the toolbar buttons are either from http://glyphicons.com/ or inspired by them. <3 Glyphicons!

Finally, [@mourner](https://github.com/mourner) is the man! Thanks for dedicating so much of your time to create the gosh darn best JavaScript mapping library around.
