(function () {

L.Handler.MarkerSnap = L.Handler.extend({
    options: {
        snapDistance: 15, // in pixels
        snapVertices: true
    },

    initialize: function (map, marker, options) {
        L.Handler.prototype.initialize.call(this, map);
        this._markers = [];
        this._guides = [];

        if (arguments.length == 2) {
            if (!(marker instanceof L.Class)) {
                options = marker;
                marker = null;
            }
        }

        L.Util.setOptions(this, options || {});

        if (marker) {
            // new markers should be draggable !
            if (!marker.dragging) marker.dragging = new L.Handler.MarkerDrag(marker);
            marker.dragging.enable();
            this.watchMarker(marker);
        }

        // Convert snap distance in pixels into buffer in degres, for searching around mouse
        // It changes at each zoom change.
        function computeBuffer() {
            this._buffer = map.layerPointToLatLng(new L.Point(0,0)).lat -
                           map.layerPointToLatLng(new L.Point(this.options.snapDistance, 0)).lat;
        }
        map.on('zoomend', computeBuffer, this);
        map.whenReady(computeBuffer, this);
        computeBuffer.call(this);
    },

    enable: function () {
        this.disable();
        for (var i=0; i<this._markers.length; i++) {
            this.watchMarker(this._markers[i]);
        }
    },

    disable: function () {
        for (var i=0; i<this._markers.length; i++) {
            this.unwatchMarker(this._markers[i]);
        }
    },

    watchMarker: function (marker) {
        if (this._markers.indexOf(marker) == -1)
            this._markers.push(marker);
        marker.on('move', this._snapMarker, this);
    },

    unwatchMarker: function (marker) {
        marker.off('move', this._snapMarker, this);
        delete marker['snap'];
    },

    addGuideLayer: function (layer) {
        for (var i=0, n=this._guides.length; i<n; i++)
            if (L.stamp(layer) === L.stamp(this._guides[i]))
                return;
        this._guides.push(layer);
    },

    _snapMarker: function(e) {
        var marker = e.target,
            latlng = marker.getLatLng(),
            snaplist = [];

        function isDifferentLayer(layer) {
            if (layer.getLatLng) {
                return L.stamp(marker) !== L.stamp(layer);
            } else {
                if (layer.editing && layer.editing._enabled) {
                    var points = layer.editing._verticesHandlers[0]._markerGroup.getLayers();
                    for(var i = 0, n = points.length; i < n; i++) {
                        if (L.stamp(points[i]) === L.stamp(marker)) { return false; }
                    }
                }
            }

            return true;
        }

        function processGuide(guide) {
            if ((guide._layers !== undefined) &&
                (typeof guide.searchBuffer !== 'function')) {
                // Guide is a layer group and has no L.LayerIndexMixin (from Leaflet.LayerIndex)
                for (var id in guide._layers) {
                    processGuide(guide._layers[id]);
                }
            }
            else if (typeof guide.searchBuffer === 'function') {
                // Search snaplist around mouse
                var nearlayers = guide.searchBuffer(latlng, this._buffer);
                snaplist = snaplist.concat(nearlayers.filter(function(layer) {
                    return isDifferentLayer(layer);
                }));
            }
            // Make sure the marker doesn't snap to itself or the associated polyline layer
            else if (isDifferentLayer(guide)) {
                snaplist.push(guide);
            }
        }

        for (var i=0, n = this._guides.length; i < n; i++) {
            var guide = this._guides[i];
            processGuide.call(this, guide);
        }

        var closest = this._findClosestLayerSnap(this._map,
                                                 snaplist,
                                                 latlng,
                                                 this.options.snapDistance,
                                                 this.options.snapVertices);

        closest = closest || {layer: null, latlng: null};
        this._updateSnap(marker, closest.layer, closest.latlng);
    },

    _findClosestLayerSnap: function (map, layers, latlng, tolerance, withVertices) {
        return L.GeometryUtil.closestLayerSnap(map, layers, latlng, tolerance, withVertices);
    },

    _updateSnap: function (marker, layer, latlng) {
        if (layer && latlng) {
            marker._latlng = L.latLng(latlng);
            marker.update();
            if (marker.snap != layer) {
                marker.snap = layer;
                if (marker._icon) L.DomUtil.addClass(marker._icon, 'marker-snapped');
                marker.fire('snap', {layer:layer, latlng: latlng});
            }
        }
        else {
            if (marker.snap) {
                if (marker._icon) L.DomUtil.removeClass(marker._icon, 'marker-snapped');
                marker.fire('unsnap', {layer:marker.snap});
            }
            delete marker['snap'];
        }
    }
});


if (!L.Edit) {
    // Leaflet.Draw not available.
    return;
}


L.Handler.PolylineSnap = L.Edit.Poly.extend({

    initialize: function (map, poly, options) {
        var that = this;

        L.Edit.Poly.prototype.initialize.call(this, poly, options);
        this._snapper = new L.Handler.MarkerSnap(map, options);
        poly.on('remove', function() {
            that.disable();
        });
    },

    addGuideLayer: function (layer) {
        this._snapper.addGuideLayer(layer);
    },
    
    _initHandlers: function () {
        this._verticesHandlers = [];
        for (var i = 0; i < this.latlngs.length; i++) {
            this._verticesHandlers.push(new L.Edit.PolyVerticesEditSnap(this._poly, this.latlngs[i], this.options));
        }
    }
});

L.Edit.PolyVerticesEditSnap = L.Edit.PolyVerticesEdit.extend({
    _createMarker: function (latlng, index) {
               var marker = L.Edit.PolyVerticesEdit.prototype._createMarker.call(this, latlng, index);

        // Treat middle markers differently
        var isMiddle = index === undefined;
        if (isMiddle) {
            // Snap middle markers, only once they were touched
            marker.on('dragstart', function () {
                this._poly.snapediting._snapper.watchMarker(marker);
            }, this);
        }
        else {
            this._poly.snapediting._snapper.watchMarker(marker);
        }
        return marker;
    }
});

L.EditToolbar.SnapEdit = L.EditToolbar.Edit.extend({
    snapOptions: {
        snapDistance: 15, // in pixels
        snapVertices: true
    },

    initialize: function(map, options) {
        L.EditToolbar.Edit.prototype.initialize.call(this, map, options);

        if (options.snapOptions) {
            L.Util.extend(this.snapOptions, options.snapOptions);
        }

        if (Array.isArray(this.snapOptions.guideLayers)) {
            this._guideLayers = this.snapOptions.guideLayers;
        } else if (options.guideLayers instanceof L.LayerGroup) {
            this._guideLayers = this.snapOptions.guideLayers.getLayers();
        } else {
            this._guideLayers = [];
        }
    },

    addGuideLayer: function(layer) {
        var index = this._guideLayers.findIndex(function(guideLayer) {
            return L.stamp(layer) === L.stamp(guideLayer);
        });

        if (index === -1) {
            this._guideLayers.push(layer);
            this._featureGroup.eachLayer(function(layer) {
                if (layer.snapediting) { layer.snapediting._guides.push(layer); }
            });
        }
    },

    removeGuideLayer: function(layer) {
      var index = this._guideLayers.findIndex(function(guideLayer) {
          return L.stamp(layer) === L.stamp(guideLayer);
      });

      if (index !== -1) {
          this._guideLayers.splice(index, 1);
          this._featureGroup.eachLayer(function(layer) {
              if (layer.snapediting) { layer.snapediting._guides.splice(index, 1); }
          });
      }
    },

    clearGuideLayers: function() {
        this._guideLayers = [];
        this._featureGroup.eachLayer(function(layer) {
            if (layer.snapediting) { layer.snapediting._guides = []; }
        });
    },

    _enableLayerEdit: function(e) {
        L.EditToolbar.Edit.prototype._enableLayerEdit.call(this, e);

        var layer = e.layer || e.target || e;

        if (!layer.snapediting) {
            if (layer.getLatLng) {
                layer.snapediting = new L.Handler.MarkerSnap(layer._map, layer, this.snapOptions);
            } else {
                if (layer.editing) {
                  layer.editing._verticesHandlers[0]._markerGroup.clearLayers();
                  delete layer.editing;
                }

                layer.editing = layer.snapediting = new L.Handler.PolylineSnap(layer._map, layer, this.snapOptions);
            }

            for (var i = 0, n = this._guideLayers.length; i < n; i++) {
                layer.snapediting.addGuideLayer(this._guideLayers[i]);
            }
        }

        layer.snapediting.enable();
    }
});

L.Draw.Feature.SnapMixin = {
    _snap_initialize: function () {
        this.on('enabled', this._snap_on_enabled, this);
        this.on('disabled', this._snap_on_disabled, this);
    },

    _snap_on_enabled: function () {
        if (!this.options.guideLayers) {
            return;
        }

        if (!this._mouseMarker) {
            this._map.on('layeradd', this._snap_on_enabled, this);
            return;
        }else{
            this._map.off('layeradd', this._snap_on_enabled, this);
        }

        if (!this._snapper) {
            this._snapper = new L.Handler.MarkerSnap(this._map);
            if (this.options.snapDistance) {
                this._snapper.options.snapDistance = this.options.snapDistance;
            }
            if (this.options.snapVertices) {
                this._snapper.options.snapVertices = this.options.snapVertices;
            }
        }

        for (var i=0, n=this.options.guideLayers.length; i<n; i++)
            this._snapper.addGuideLayer(this.options.guideLayers[i]);

        var marker = this._mouseMarker;

        this._snapper.watchMarker(marker);

        // Show marker when (snap for user feedback)
        var icon = marker.options.icon;
        marker.on('snap', function (e) {
                  marker.setIcon(this.options.icon);
                  marker.setOpacity(1);
              }, this)
              .on('unsnap', function (e) {
                  marker.setIcon(icon);
                  marker.setOpacity(0);
              }, this);

        marker.on('click', this._snap_on_click, this);
    },

    _snap_on_click: function (e) {
        if (this._markers) {
            var markerCount = this._markers.length,
                marker = this._markers[markerCount - 1];
            if (this._mouseMarker.snap) {
                if(e){
                  // update the feature being drawn to reflect the snapped location:
                  marker.setLatLng(e.target._latlng);
                  if(this._poly){
                    var polyPointsCount = this._poly._latlngs.length;
                    this._poly._latlngs[polyPointsCount - 1] = e.target._latlng;
                    this._poly.redraw();
                  }
                }

                L.DomUtil.addClass(marker._icon, 'marker-snapped');
            }
        }
    },

    _snap_on_disabled: function () {
        delete this._snapper;
    },
};

L.Draw.Feature.include(L.Draw.Feature.SnapMixin);
L.Draw.Feature.addInitHook('_snap_initialize');

})();
