/**
 * Matrix transform path for SVG/VML
 * TODO: adapt to Leaflet 0.8 upon release
 */

"use strict";

if (L.Browser.svg) { // SVG transformation

  L.Path.include({

    /**
     * Reset transform matrix
     */
    _resetTransform: function() {
      this._container.setAttributeNS(null, 'transform', '');
    },

    /**
     * Applies matrix transformation to SVG
     * @param {Array.<Number>} matrix
     */
    _applyTransform: function(matrix) {
      this._container.setAttributeNS(null, "transform",
        'matrix(' + matrix.join(' ') + ')');
    }

  });

} else { // VML transform routines

  L.Path.include({

    /**
     * Reset transform matrix
     */
    _resetTransform: function() {
      if (this._skew) {
        // super important! workaround for a 'jumping' glitch:
        // disable transform before removing it
        this._skew.on = false;
        this._container.removeChild(this._skew);
        this._skew = null;
      }
    },

    /**
     * Applies matrix transformation to VML
     * @param {Array.<Number>} matrix
     */
    _applyTransform: function(matrix) {
      var skew = this._skew;

      if (!skew) {
        skew = this._createElement('skew');
        this._container.appendChild(skew);
        skew.style.behavior = 'url(#default#VML)';
        this._skew = skew;
      }

      // handle skew/translate separately, cause it's broken
      var mt = matrix[0].toFixed(8) + " " + matrix[1].toFixed(8) + " " +
        matrix[2].toFixed(8) + " " + matrix[3].toFixed(8) + " 0 0";
      var offset = Math.floor(matrix[4]).toFixed() + ", " +
        Math.floor(matrix[5]).toFixed() + "";

      var s = this._container.style;
      var l = parseFloat(s.left);
      var t = parseFloat(s.top);
      var w = parseFloat(s.width);
      var h = parseFloat(s.height);

      if (isNaN(l)) l = 0;
      if (isNaN(t)) t = 0;
      if (isNaN(w) || !w) w = 1;
      if (isNaN(h) || !h) h = 1;

      var origin = (-l / w - 0.5).toFixed(8) + " " + (-t / h - 0.5).toFixed(8);

      skew.on = "f";
      skew.matrix = mt;
      skew.origin = origin;
      skew.offset = offset;
      skew.on = true;
    }

  });
}

// Renderer-independent
L.Path.include({

  /**
   * Check if the feature was dragged, that'll supress the click event
   * on mouseup. That fixes popups for example
   *
   * @param  {MouseEvent} e
   */
  _onMouseClick: function(e) {
    if ((this.dragging && this.dragging.moved()) ||
      (this._map.dragging && this._map.dragging.moved())) {
      return;
    }

    this._fireMouseEvent(e);
  }
});
/**
 * Leaflet vector features drag functionality
 * @preserve
 */

"use strict";

/**
 * Drag handler
 * @class L.Path.Drag
 * @extends {L.Handler}
 */
L.Handler.PathDrag = L.Handler.extend( /** @lends  L.Path.Drag.prototype */ {

  /**
   * @param  {L.Path} path
   * @constructor
   */
  initialize: function(path) {

    /**
     * @type {L.Path}
     */
    this._path = path;

    /**
     * @type {Array.<Number>}
     */
    this._matrix = null;

    /**
     * @type {L.Point}
     */
    this._startPoint = null;

    /**
     * @type {L.Point}
     */
    this._dragStartPoint = null;

  },

  /**
   * Enable dragging
   */
  addHooks: function() {
    this._path.on('mousedown', this._onDragStart, this);
    L.DomUtil.addClass(this._path._container, 'leaflet-path-draggable');
  },

  /**
   * Disable dragging
   */
  removeHooks: function() {
    this._path.off('mousedown', this._onDragStart, this);
    L.DomUtil.removeClass(this._path._container, 'leaflet-path-draggable');
  },

  /**
   * @return {Boolean}
   */
  moved: function() {
    return this._path._dragMoved;
  },

  /**
   * Start drag
   * @param  {L.MouseEvent} evt
   */
  _onDragStart: function(evt) {
    this._startPoint = evt.containerPoint.clone();
    this._dragStartPoint = evt.containerPoint.clone();
    this._matrix = [1, 0, 0, 1, 0, 0];

    this._path._map
      .on('mousemove', this._onDrag, this)
      .on('mouseup', this._onDragEnd, this)
    this._path._dragMoved = false;
  },

  /**
   * Dragging
   * @param  {L.MouseEvent} evt
   */
  _onDrag: function(evt) {
    var x = evt.containerPoint.x;
    var y = evt.containerPoint.y;

    var dx = x - this._startPoint.x;
    var dy = y - this._startPoint.y;

    if (!this._path._dragMoved && (dx || dy)) {
      this._path._dragMoved = true;
      this._path.fire('dragstart');
    }

    this._matrix[4] += dx;
    this._matrix[5] += dy;

    this._startPoint.x = x;
    this._startPoint.y = y;

    this._path._applyTransform(this._matrix);
    this._path.fire('drag');
    L.DomEvent.stop(evt.originalEvent);
  },

  /**
   * Dragging stopped, apply
   * @param  {L.MouseEvent} evt
   */
  _onDragEnd: function(evt) {
    L.DomEvent.stop(evt);
    // undo container transform
    this._path._resetTransform();
    // apply matrix
    this._transformPoints(this._matrix);

    this._path._map
      .off('mousemove', this._onDrag, this)
      .off('mouseup', this._onDragEnd, this);

    // consistency
    this._path.fire('dragend', {
      distance: Math.sqrt(
        L.LineUtil._sqDist(this._dragStartPoint, evt.containerPoint)
      )
    });

    this._matrix = null;
    this._startPoint = null;
    this._dragStartPoint = null;
  },

  /**
   * Applies transformation, does it in one sweep for performance,
   * so don't be surprised about the code repetition.
   *
   * [ x ]   [ a  b  tx ] [ x ]   [ a * x + b * y + tx ]
   * [ y ] = [ c  d  ty ] [ y ] = [ c * x + d * y + ty ]
   *
   * @param {Array.<Number>} matrix
   */
  _transformPoints: function(matrix) {
    var path = this._path;
    var i, len, latlng;

    var px = L.point(matrix[4], matrix[5]);

    var crs = path._map.options.crs;
    var transformation = crs.transformation;
    var scale = crs.scale(path._map.getZoom());
    var projection = crs.projection;

    var diff = transformation.untransform(px, scale)
      .subtract(transformation.untransform(L.point(0, 0), scale));

    // console.time('transform');

    // all shifts are in-place
    if (path._point) { // L.Circle
      path._latlng = projection.unproject(
        projection.project(path._latlng)._add(diff));
      path._point._add(px);
    } else if (path._originalPoints) { // everything else
      for (i = 0, len = path._originalPoints.length; i < len; i++) {
        latlng = path._latlngs[i];
        path._latlngs[i] = projection
          .unproject(projection.project(latlng)._add(diff));
        path._originalPoints[i]._add(px);
      }
    }

    // holes operations
    if (path._holes) {
      for (i = 0, len = path._holes.length; i < len; i++) {
        for (var j = 0, len2 = path._holes[i].length; j < len2; j++) {
          latlng = path._holes[i][j];
          path._holes[i][j] = projection
            .unproject(projection.project(latlng)._add(diff));
          path._holePoints[i][j]._add(px);
        }
      }
    }

    // console.timeEnd('transform');

    path._updatePath();
  }

});

L.Path.prototype.__initEvents = L.Path.prototype._initEvents;
L.Path.prototype._initEvents = function() {
  this.__initEvents();

  if (this.options.draggable) {
    if (this.dragging) {
      this.dragging.enable();
    } else {
      this.dragging = new L.Handler.PathDrag(this);
      this.dragging.enable();
    }
  } else if (this.dragging) {
    this.dragging.disable();
  }
};
(function() {

  // listen and propagate dragstart on sub-layers
  L.FeatureGroup.EVENTS += ' dragstart';

  function wrapMethod(klasses, methodName, method) {
    for (var i = 0, len = klasses.length; i < len; i++) {
      var klass = klasses[i];
      klass.prototype['_' + methodName] = klass.prototype[methodName];
      klass.prototype[methodName] = method;
    }
  }

  /**
   * @param {L.Polygon|L.Polyline} layer
   * @return {L.MultiPolygon|L.MultiPolyline}
   */
  function addLayer(layer) {
    if (this.hasLayer(layer)) {
      return this;
    }
    layer
      .on('drag', this._onDrag, this)
      .on('dragend', this._onDragEnd, this);
    return this._addLayer.call(this, layer);
  }

  /**
   * @param  {L.Polygon|L.Polyline} layer
   * @return {L.MultiPolygon|L.MultiPolyline}
   */
  function removeLayer(layer) {
    if (!this.hasLayer(layer)) {
      return this;
    }
    layer
      .off('drag', this._onDrag, this)
      .off('dragend', this._onDragEnd, this);
    return this._removeLayer.call(this, layer);
  }

  // duck-type methods to listen to the drag events
  wrapMethod([L.MultiPolygon, L.MultiPolyline], 'addLayer', addLayer);
  wrapMethod([L.MultiPolygon, L.MultiPolyline], 'removeLayer', removeLayer);

  var dragMethods = {
    _onDrag: function(evt) {
      var layer = evt.target;
      this.eachLayer(function(otherLayer) {
        if (otherLayer !== layer) {
          otherLayer._applyTransform(layer.dragging._matrix);
        }
      });

      this._propagateEvent(evt);
    },

    _onDragEnd: function(evt) {
      var layer = evt.target;

      this.eachLayer(function(otherLayer) {
        if (otherLayer !== layer) {
          otherLayer._resetTransform();
          otherLayer.dragging._transformPoints(layer.dragging._matrix);
        }
      });

      this._propagateEvent(evt);
    }
  };

  L.MultiPolygon.include(dragMethods);
  L.MultiPolyline.include(dragMethods);

})();
// TODO: dismiss that on Leaflet 0.8.x release

L.Polygon.include( /** @lends L.Polygon.prototype */ {

  /**
   * @return {L.LatLng}
   */
  getCenter: function() {
    var i, j, len, p1, p2, f, area, x, y,
      points = this._parts[0];

    // polygon centroid algorithm; only uses the first ring if there are multiple

    area = x = y = 0;

    for (i = 0, len = points.length, j = len - 1; i < len; j = i++) {
      p1 = points[i];
      p2 = points[j];

      f = p1.y * p2.x - p2.y * p1.x;
      x += (p1.x + p2.x) * f;
      y += (p1.y + p2.y) * f;
      area += f * 3;
    }

    return this._map.layerPointToLatLng([x / area, y / area]);
  }

});
"use strict";

/**
 * Static flag for move markers
 * @type {Boolean}
 */
L.EditToolbar.Edit.MOVE_MARKERS = false;

L.EditToolbar.Edit.include( /** @lends L.EditToolbar.Edit.prototype */ {

  /**
   * @override
   */
  initialize: function(map, options) {
    L.EditToolbar.Edit.MOVE_MARKERS = !!options.selectedPathOptions.moveMarkers;
    this._initialize(map, options);
  },

  /**
   * @param  {L.Map}  map
   * @param  {Object} options
   */
  _initialize: L.EditToolbar.Edit.prototype.initialize

});
/**
 * Mainly central marker routines
 */

L.Edit.SimpleShape.include( /** @lends L.Edit.SimpleShape.prototype */ {

  /**
   * Put move marker into center
   */
  _updateMoveMarker: function() {
    if (this._moveMarker) {
      this._moveMarker.setLatLng(this._getShapeCenter());
    }
  },

  /**
   * Shape centroid
   * @return {L.LatLng}
   */
  _getShapeCenter: function() {
    return this._shape.getBounds().getCenter();
  },

  /**
   * @override
   */
  _createMoveMarker: function() {
    if (L.EditToolbar.Edit.MOVE_MARKERS) {
      this._moveMarker = this._createMarker(this._getShapeCenter(),
        this.options.moveIcon);
    }
  }

});

/**
 * Override this if you don't want the central marker
 * @type {Boolean}
 */
L.Edit.SimpleShape.mergeOptions({
  moveMarker: false
});
/**
 * Dragging routines for circle
 */

L.Edit.Circle.include( /** @lends L.Edit.Circle.prototype */ {

  /**
   * @override
   */
  addHooks: function() {
    if (this._shape._map) {
      this._map = this._shape._map;
      if (!this._markerGroup) {
        this._enableDragging();
        this._initMarkers();
      }
      this._shape._map.addLayer(this._markerGroup);
    }
  },

  /**
   * @override
   */
  removeHooks: function() {
    if (this._shape._map) {
      for (var i = 0, l = this._resizeMarkers.length; i < l; i++) {
        this._unbindMarker(this._resizeMarkers[i]);
      }

      this._disableDragging();
      this._resizeMarkers = null;
      this._map.removeLayer(this._markerGroup);
      delete this._markerGroup;
    }

    this._map = null;
  },

  /**
   * @override
   */
  _createMoveMarker: L.Edit.SimpleShape.prototype._createMoveMarker,

  /**
   * Change
   * @param  {L.LatLng} latlng
   */
  _resize: function(latlng) {
    var center = this._shape.getLatLng();
    var radius = center.distanceTo(latlng);

    this._shape.setRadius(radius);

    this._updateMoveMarker();
  },

  /**
   * Adds drag start listeners
   */
  _enableDragging: function() {
    if (!this._shape.dragging) {
      this._shape.dragging = new L.Handler.PathDrag(this._shape);
    }
    this._shape.dragging.enable();
    this._shape
      .on('dragstart', this._onStartDragFeature, this)
      .on('dragend', this._onStopDragFeature, this);
  },

  /**
   * Removes drag start listeners
   */
  _disableDragging: function() {
    this._shape.dragging.disable();
    this._shape
      .off('dragstart', this._onStartDragFeature, this)
      .off('dragend', this._onStopDragFeature, this);
  },

  /**
   * Start drag
   * @param  {L.MouseEvent} evt
   */
  _onStartDragFeature: function() {
    this._shape._map.removeLayer(this._markerGroup);
    this._shape.fire('editstart');
  },

  /**
   * Dragging stopped, apply
   * @param  {L.MouseEvent} evt
   */
  _onStopDragFeature: function() {
    var center = this._shape.getLatLng();

    //this._moveMarker.setLatLng(center);
    this._resizeMarkers[0].setLatLng(this._getResizeMarkerPoint(center));

    // show resize marker
    this._shape._map.addLayer(this._markerGroup);
    this._updateMoveMarker();
    this._fireEdit();
  }
});
/**
 * Dragging routines for poly handler
 */

L.Edit.Rectangle.include( /** @lends L.Edit.Rectangle.prototype */ {

  /**
   * @override
   */
  addHooks: function() {
    if (this._shape._map) {
      if (!this._markerGroup) {
        this._enableDragging();
        this._initMarkers();
      }
      this._shape._map.addLayer(this._markerGroup);
    }
  },

  /**
   * @override
   */
  removeHooks: function() {
    if (this._shape._map) {
      this._shape._map.removeLayer(this._markerGroup);
      this._disableDragging();
      delete this._markerGroup;
      delete this._markers;
    }
  },

  /**
   * @override
   */
  _resize: function(latlng) {
    // Update the shape based on the current position of
    // this corner and the opposite point
    this._shape.setBounds(L.latLngBounds(latlng, this._oppositeCorner));
    this._updateMoveMarker();
  },

  /**
   * @override
   */
  _onMarkerDragEnd: function(e) {
    this._toggleCornerMarkers(1);
    this._repositionCornerMarkers();

    L.Edit.SimpleShape.prototype._onMarkerDragEnd.call(this, e);
  },

  /**
   * Adds drag start listeners
   */
  _enableDragging: function() {
    if (!this._shape.dragging) {
      this._shape.dragging = new L.Handler.PathDrag(this._shape);
    }
    this._shape.dragging.enable();
    this._shape
      .on('dragstart', this._onStartDragFeature, this)
      .on('dragend', this._onStopDragFeature, this);
  },

  /**
   * Removes drag start listeners
   */
  _disableDragging: function() {
    this._shape.dragging.disable();
    this._shape
      .off('dragstart', this._onStartDragFeature, this)
      .off('dragend', this._onStopDragFeature, this);
  },

  /**
   * Start drag
   * @param  {L.MouseEvent} evt
   */
  _onStartDragFeature: function() {
    this._shape._map.removeLayer(this._markerGroup);
    this._shape.fire('editstart');
  },

  /**
   * Dragging stopped, apply
   * @param  {L.MouseEvent} evt
   */
  _onStopDragFeature: function() {
    var polygon = this._shape;
    for (var i = 0, len = polygon._latlngs.length; i < len; i++) {
      // update marker
      var marker = this._resizeMarkers[i];
      marker.setLatLng(polygon._latlngs[i]);

      // this one's needed to update the path
      marker._origLatLng = polygon._latlngs[i];
      if (marker._middleLeft) {
        marker._middleLeft.setLatLng(this._getMiddleLatLng(marker._prev, marker));
      }
      if (marker._middleRight) {
        marker._middleRight.setLatLng(this._getMiddleLatLng(marker, marker._next));
      }
    }
    // this._moveMarker.setLatLng(polygon.getBounds().getCenter());

    // show vertices
    this._shape._map.addLayer(this._markerGroup);
    this._updateMoveMarker();

    this._repositionCornerMarkers();
    this._fireEdit();
  }
});
/**
 * Dragging routines for poly handler
 */

L.Edit.PolyVerticesEdit.include( /** @lends L.Edit.PolyVerticesEdit.prototype */ {

  // store methods to call them in overrides
  __createMarker: L.Edit.PolyVerticesEdit.prototype._createMarker,
  __removeMarker: L.Edit.PolyVerticesEdit.prototype._removeMarker,

  /**
   * @override
   */
  addHooks: function() {
    if (this._poly._map) {
      if (!this._markerGroup) {
        this._enableDragging();
        this._initMarkers();
        // Create center marker
        this._createMoveMarker();
      }
      this._poly._map.addLayer(this._markerGroup);
    }
  },

  /**
   * @override
   */
  _createMoveMarker: function() {
    if (L.EditToolbar.Edit.MOVE_MARKERS && (this._poly instanceof L.Polygon)) {
      this._moveMarker = new L.Marker(this._getShapeCenter(), {
        icon: this.options.moveIcon
      });
      this._moveMarker.on('mousedown', this._delegateToShape, this);
      this._markerGroup.addLayer(this._moveMarker);
    }
  },

  /**
   * Start dragging through the marker
   * @param  {L.MouseEvent} evt
   */
  _delegateToShape: function(evt) {
    var poly = this._shape || this._poly;
    var marker = evt.target;
    poly.fire('mousedown', L.Util.extend(evt, {
      containerPoint: L.DomUtil.getPosition(marker._icon)
        .add(poly._map._getMapPanePos())
    }));
  },

  /**
   * Polygon centroid
   * @return {L.LatLng}
   */
  _getShapeCenter: function() {
    return this._poly.getCenter();
  },

  /**
   * @override
   */
  removeHooks: function() {
    if (this._poly._map) {
      this._poly._map.removeLayer(this._markerGroup);
      this._disableDragging();
      delete this._markerGroup;
      delete this._markers;
    }
  },

  /**
   * Adds drag start listeners
   */
  _enableDragging: function() {
    if (!this._poly.dragging) {
      this._poly.dragging = new L.Handler.PathDrag(this._poly);
    }
    this._poly.dragging.enable();
    this._poly
      .on('dragstart', this._onStartDragFeature, this)
      .on('dragend', this._onStopDragFeature, this);
  },

  /**
   * Removes drag start listeners
   */
  _disableDragging: function() {
    this._poly.dragging.disable();
    this._poly
      .off('dragstart', this._onStartDragFeature, this)
      .off('dragend', this._onStopDragFeature, this);
  },

  /**
   * Start drag
   * @param  {L.MouseEvent} evt
   */
  _onStartDragFeature: function(evt) {
    this._poly._map.removeLayer(this._markerGroup);
    this._poly.fire('editstart');
  },

  /**
   * Dragging stopped, apply
   * @param  {L.MouseEvent} evt
   */
  _onStopDragFeature: function(evt) {
    // var polygon = this._poly;
    for (var i = 0, len = this._latlngs.length; i < len; i++) {
      // update marker
      var marker = this._markers[i];
      marker.setLatLng(this._latlngs[i]);

      // this one's needed to update the path
      marker._origLatLng = this._latlngs[i];
      if (marker._middleLeft) {
        marker._middleLeft.setLatLng(this._getMiddleLatLng(marker._prev, marker));
      }
      if (marker._middleRight) {
        marker._middleRight.setLatLng(this._getMiddleLatLng(marker, marker._next));
      }
    }

    // show vertices
    this._poly._map.addLayer(this._markerGroup);
    L.Edit.SimpleShape.prototype._updateMoveMarker.call(this);
    this._fireEdit();
  },

  /**
   * Copy from simple shape
   */
  _updateMoveMarker: L.Edit.SimpleShape.prototype._updateMoveMarker,

  /**
   * @override
   */
  _createMarker: function(latlng, index) {
    var marker = this.__createMarker(latlng, index);
    marker
      .on('dragstart', this._hideMoveMarker, this)
      .on('dragend', this._showUpdateMoveMarker, this);
    return marker;
  },

  /**
   * @override
   */
  _removeMarker: function(marker) {
    this.__removeMarker(marker);
    marker
      .off('dragstart', this._hideMoveMarker, this)
      .off('dragend', this._showUpdateMoveMarker, this);
  },

  /**
   * Hide move marker while dragging a vertex
   */
  _hideMoveMarker: function() {
    if (this._moveMarker) {
      this._markerGroup.removeLayer(this._moveMarker);
    }
  },

  /**
   * Show and update move marker
   */
  _showUpdateMoveMarker: function() {
    if (this._moveMarker) {
      this._markerGroup.addLayer(this._moveMarker);
      this._updateMoveMarker();
    }
  }

});

/**
 * @type {L.DivIcon}
 */
L.Edit.PolyVerticesEdit.prototype.options.moveIcon = new L.DivIcon({
  iconSize: new L.Point(8, 8),
  className: 'leaflet-div-icon leaflet-editing-icon leaflet-edit-move'
});

/**
 * Override this if you don't want the central marker
 * @type {Boolean}
 */
L.Edit.PolyVerticesEdit.mergeOptions({
  moveMarker: false
});
