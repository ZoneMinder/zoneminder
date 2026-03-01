/**
 * AnnotationEditor - Canvas-based bounding box annotation editor for
 * ZoneMinder custom model training. Allows users to view, create, edit,
 * and save object annotations on event frames in YOLO format.
 *
 * Usage:
 *   var editor = new AnnotationEditor({
 *     canvasId: 'annotationCanvas',
 *     eventId: 123,
 *     translations: { ... }
 *   });
 *   editor.init();
 *   editor.open();
 */

var ANNOTATION_COLORS = [
  '#e6194b', '#3cb44b', '#4363d8', '#f58231', '#911eb4',
  '#42d4f4', '#f032e6', '#bfef45', '#fabed4', '#469990',
  '#dcbeff', '#9A6324', '#800000', '#aaffc3', '#808000',
  '#000075', '#a9a9a9'
];

var ANNOTATION_HANDLE_SIZE = 8;
var ANNOTATION_HIT_THRESHOLD = 8;
var ANNOTATION_MIN_BOX_SIZE = 10;
var ANNOTATION_MAX_UNDO = 50;

/**
 * @param {Object} options
 * @param {string} options.canvasId  ID of the canvas DOM element
 * @param {number} options.eventId   Current event ID
 * @param {Object} options.translations  Translated UI strings
 * @constructor
 */
function AnnotationEditor(options) {
  this.canvasId = options.canvasId;
  this.eventId = options.eventId;
  this.translations = options.translations || {};

  this.canvas = null;
  this.ctx = null;

  // Annotation data
  this.annotations = [];
  this.selectedIndex = -1;
  this.classLabels = [];
  this.dirty = false;
  this.image = null;
  this.imageNaturalW = 0;
  this.imageNaturalH = 0;
  this.currentFrameId = null;
  this.availableFrames = [];
  this.totalFrames = 0;
  this.eventPath = '';

  // Drawing state
  this.isDrawing = false;
  this.drawStart = null;
  this.drawCurrent = null;

  // Drag/resize state
  this.isDragging = false;
  this.isResizing = false;
  this.resizeHandle = null;
  this.dragOffset = {x: 0, y: 0};

  // Undo
  this.undoStack = [];

  // Stats
  this.trainingStats = null;

  // Label usage tracking for sorting most-recently-used first
  this.recentLabels = [];
}

/**
 * Initialize the editor: bind canvas and keyboard events.
 */
AnnotationEditor.prototype.init = function() {
  this.canvas = document.getElementById(this.canvasId);
  if (!this.canvas) {
    console.error('AnnotationEditor: canvas not found: ' + this.canvasId);
    return;
  }
  this.ctx = this.canvas.getContext('2d');

  var self = this;

  this.canvas.addEventListener('mousedown', function(e) {
    self._onMouseDown(e);
  });
  this.canvas.addEventListener('mousemove', function(e) {
    self._onMouseMove(e);
  });
  this.canvas.addEventListener('mouseup', function(e) {
    self._onMouseUp(e);
  });

  // Prevent context menu on canvas
  this.canvas.addEventListener('contextmenu', function(e) {
    e.preventDefault();
  });

  $j(document).on('keydown.annotationEditor', function(e) {
    // Only handle keys when panel is open
    if (!$j('#annotationPanel').hasClass('open')) return;

    // Don't capture keys when typing in an input
    if ($j(e.target).is('input, textarea, select')) return;

    if (e.ctrlKey && e.key === 'z') {
      e.preventDefault();
      self.undo();
    } else if (e.key === 'Delete' || e.key === 'Backspace') {
      if (self.selectedIndex >= 0) {
        e.preventDefault();
        self._pushUndo();
        self.deleteAnnotation(self.selectedIndex);
      }
    } else if (e.key === 'Escape') {
      if (self.isDrawing) {
        self.isDrawing = false;
        self.drawStart = null;
        self.drawCurrent = null;
        self._render();
      }
      self._hideLabelPicker();
    }
  });
};

/**
 * Open the annotation panel: load detection data, show panel.
 */
AnnotationEditor.prototype.open = function() {
  var self = this;

  $j('#annotationPanel').addClass('open');
  $j('#eventVideo').hide();

  // Load class labels and detection data in parallel
  this._loadLabels();
  this._loadStats();

  $j.getJSON(thisUrl + '?request=training&action=load&eid=' + this.eventId)
      .done(function(data) {
        if (data.result === 'Error') {
          self._setStatus(data.message, 'error');
          return;
        }
        var resp = data.response || data;
        self.availableFrames = resp.availableFrames || [];
        self.totalFrames = resp.totalFrames || 0;
        self.eventPath = resp.eventPath || '';
        self.imageNaturalW = resp.width || 0;
        self.imageNaturalH = resp.height || 0;
        self.monitorId = resp.monitorId || '';
        self.hasDetectScript = resp.hasDetectScript || false;

        // Show/hide detect button based on script availability
        if (self.hasDetectScript) {
          $j('#annotationDetectBtn').show();
        } else {
          $j('#annotationDetectBtn').hide();
        }

        self._updateFrameSelector(self.availableFrames);

        if (resp.detectionData) {
          self._loadDetectionData(resp.detectionData);
        }

        var defaultFrame = resp.defaultFrameId || 'alarm';
        self._loadFrameImage(defaultFrame);
      })
      .fail(function(jqxhr) {
        self._setStatus(self.translations.FailedToLoadEvent || 'Failed to load event data', 'error');
        logAjaxFail(jqxhr);
      });
};

/**
 * Close the annotation panel. Prompts if dirty.
 */
AnnotationEditor.prototype.close = function() {
  if (this.dirty) {
    var msg = this.translations.UnsavedAnnotations ||
        'You have unsaved annotations. Discard changes?';
    if (!confirm(msg)) return;
  }

  $j('#annotationPanel').removeClass('open');
  $j('#eventVideo').show();
  this._hideLabelPicker();

  // Reset state
  this.annotations = [];
  this.selectedIndex = -1;
  this.dirty = false;
  this.isDrawing = false;
  this.isDragging = false;
  this.isResizing = false;
  this.undoStack = [];
  this.image = null;
  this.currentFrameId = null;
};

/**
 * Convert objects.json detection data into the annotations array.
 * @param {Object} data  Parsed objects.json content
 */
AnnotationEditor.prototype._loadDetectionData = function(data) {
  this.annotations = [];

  if (!data) return;

  // objects.json may have different structures depending on the detector.
  // Common format: array of {label, confidence, bbox: [x1,y1,x2,y2]}
  // or: {objects: [{label, confidence, bbox: ...}]}
  var objects = [];
  if (Array.isArray(data)) {
    objects = data;
  } else if (data.objects && Array.isArray(data.objects)) {
    objects = data.objects;
  } else if (data.detections && Array.isArray(data.detections)) {
    objects = data.detections;
  }

  for (var i = 0; i < objects.length; i++) {
    var obj = objects[i];
    var ann = {
      label: obj.label || obj.name || obj.class || 'unknown',
      confidence: obj.confidence || obj.score || 0,
      x1: 0,
      y1: 0,
      x2: 0,
      y2: 0
    };

    if (obj.bbox && Array.isArray(obj.bbox)) {
      ann.x1 = obj.bbox[0];
      ann.y1 = obj.bbox[1];
      ann.x2 = obj.bbox[2];
      ann.y2 = obj.bbox[3];
    } else if (obj.x1 !== undefined) {
      ann.x1 = obj.x1;
      ann.y1 = obj.y1;
      ann.x2 = obj.x2;
      ann.y2 = obj.y2;
    }

    this.annotations.push(ann);
  }

  this._updateSidebar();
};

/**
 * Load a frame image into the canvas.
 * @param {string|number} frameId  Frame ID or special name
 */
AnnotationEditor.prototype._loadFrameImage = function(frameId) {
  var self = this;
  this.currentFrameId = frameId;

  var img = new Image();
  img.crossOrigin = 'anonymous';
  img.onload = function() {
    self.image = img;
    self.imageNaturalW = img.naturalWidth;
    self.imageNaturalH = img.naturalHeight;

    // Set canvas resolution to match image
    self.canvas.width = img.naturalWidth;
    self.canvas.height = img.naturalHeight;

    self._render();
  };
  img.onerror = function() {
    self._setStatus(self.translations.FailedToLoadFrame || 'Failed to load frame image', 'error');
  };

  img.src = thisUrl + '?view=image&eid=' + this.eventId + '&fid=' + frameId;
};

/**
 * Switch to a different frame. Prompts if dirty.
 * @param {string|number} frameId
 */
AnnotationEditor.prototype.switchFrame = function(frameId) {
  if (this.dirty) {
    var msg = this.translations.UnsavedAnnotations ||
        'You have unsaved annotations. Discard changes?';
    if (!confirm(msg)) return;
  }

  this.annotations = [];
  this.selectedIndex = -1;
  this.dirty = false;
  this.undoStack = [];
  this._hideLabelPicker();

  this._loadFrameImage(frameId);
  this._updateSidebar();
};

/**
 * Render the entire canvas: image, all boxes, in-progress drawing.
 */
AnnotationEditor.prototype._render = function() {
  var ctx = this.ctx;
  var w = this.canvas.width;
  var h = this.canvas.height;

  ctx.clearRect(0, 0, w, h);

  // Draw the frame image
  if (this.image) {
    ctx.drawImage(this.image, 0, 0, w, h);
  }

  // Draw all annotation boxes
  for (var i = 0; i < this.annotations.length; i++) {
    this._drawBox(this.annotations[i], i === this.selectedIndex, i);
  }

  // Draw in-progress drawing rectangle
  if (this.isDrawing && this.drawStart && this.drawCurrent) {
    var x = Math.min(this.drawStart.x, this.drawCurrent.x);
    var y = Math.min(this.drawStart.y, this.drawCurrent.y);
    var bw = Math.abs(this.drawCurrent.x - this.drawStart.x);
    var bh = Math.abs(this.drawCurrent.y - this.drawStart.y);

    var drawScale = this.canvas.width / 900;
    ctx.save();
    ctx.strokeStyle = '#ff8c00';
    ctx.lineWidth = Math.max(2, Math.round(3 * drawScale));
    ctx.setLineDash([Math.round(8 * drawScale), Math.round(4 * drawScale)]);
    ctx.strokeRect(x, y, bw, bh);
    ctx.restore();
  }
};

/**
 * Draw a single annotation box.
 * @param {Object} ann  Annotation object
 * @param {boolean} isSelected  Whether this box is selected
 * @param {number} index  Index in annotations array (for color)
 */
AnnotationEditor.prototype._drawBox = function(ann, isSelected, index) {
  var ctx = this.ctx;
  var color;
  if (ann.pending) {
    color = '#ff8c00'; // orange for unconfirmed detections
  } else {
    color = '#28a745'; // green for accepted/manual annotations
  }

  var x = Math.min(ann.x1, ann.x2);
  var y = Math.min(ann.y1, ann.y2);
  var w = Math.abs(ann.x2 - ann.x1);
  var h = Math.abs(ann.y2 - ann.y1);

  // Scale sizes relative to canvas width so they're visible at any resolution
  var scale = this.canvas.width / 900;
  var borderWidth = Math.max(2, Math.round((isSelected ? 4 : 3) * scale));
  var fontSize = Math.max(14, Math.round(18 * scale));
  var handleSize = Math.max(8, Math.round(10 * scale));
  var textPad = Math.round(6 * scale);

  // Semi-transparent fill
  ctx.save();
  ctx.fillStyle = color;
  ctx.globalAlpha = 0.15;
  ctx.fillRect(x, y, w, h);
  ctx.restore();

  // Border
  ctx.save();
  ctx.strokeStyle = color;
  ctx.lineWidth = borderWidth;
  ctx.strokeRect(x, y, w, h);
  ctx.restore();

  // Label text above box
  var labelText = ann.label;
  if (ann.confidence > 0) {
    labelText += ' ' + Math.round(ann.confidence * 100) + '%';
  }

  ctx.save();
  ctx.font = 'bold ' + fontSize + 'px sans-serif';
  var textMetrics = ctx.measureText(labelText);
  var textW = textMetrics.width + textPad * 2;
  var textH = Math.round(fontSize * 1.5);
  var textX = x;
  var textY = y - textH;
  if (textY < 0) textY = y; // flip below if too close to top

  ctx.fillStyle = color;
  ctx.fillRect(textX, textY, textW, textH);

  ctx.fillStyle = '#ffffff';
  ctx.textBaseline = 'middle';
  ctx.fillText(labelText, textX + textPad, textY + textH / 2);
  ctx.restore();

  // Draw resize handles on selected box
  if (isSelected) {
    var handles = this._getHandlePositions(ann);
    var handleNames = Object.keys(handles);
    for (var i = 0; i < handleNames.length; i++) {
      var hp = handles[handleNames[i]];
      ctx.save();
      ctx.fillStyle = '#ffffff';
      ctx.strokeStyle = color;
      ctx.lineWidth = Math.max(1, Math.round(scale));
      ctx.fillRect(hp.x - handleSize / 2, hp.y - handleSize / 2, handleSize, handleSize);
      ctx.strokeRect(hp.x - handleSize / 2, hp.y - handleSize / 2, handleSize, handleSize);
      ctx.restore();
    }
  }
};

/**
 * Get the 8 resize handle positions for an annotation box.
 * @param {Object} ann
 * @return {Object} {nw, n, ne, e, se, s, sw, w} with {x, y}
 */
AnnotationEditor.prototype._getHandlePositions = function(ann) {
  var x1 = Math.min(ann.x1, ann.x2);
  var y1 = Math.min(ann.y1, ann.y2);
  var x2 = Math.max(ann.x1, ann.x2);
  var y2 = Math.max(ann.y1, ann.y2);
  var mx = (x1 + x2) / 2;
  var my = (y1 + y2) / 2;

  return {
    nw: {x: x1, y: y1},
    n: {x: mx, y: y1},
    ne: {x: x2, y: y1},
    e: {x: x2, y: my},
    se: {x: x2, y: y2},
    s: {x: mx, y: y2},
    sw: {x: x1, y: y2},
    w: {x: x1, y: my}
  };
};

/**
 * Convert mouse event coordinates to image space.
 * @param {MouseEvent} e
 * @return {{x: number, y: number}}
 */
AnnotationEditor.prototype._mouseToImage = function(e) {
  var rect = this.canvas.getBoundingClientRect();
  var scaleX = this.canvas.width / rect.width;
  var scaleY = this.canvas.height / rect.height;
  return {
    x: (e.clientX - rect.left) * scaleX,
    y: (e.clientY - rect.top) * scaleY
  };
};

/**
 * Handle mousedown on canvas: check handles, check box hit, or start drawing.
 * @param {MouseEvent} e
 */
AnnotationEditor.prototype._onMouseDown = function(e) {
  if (e.button !== 0) return; // left button only

  this._hideLabelPicker();

  var pos = this._mouseToImage(e);

  // Check resize handles on selected box first
  if (this.selectedIndex >= 0) {
    var handle = this._hitTestHandles(pos, this.annotations[this.selectedIndex]);
    if (handle) {
      this._pushUndo();
      this.isResizing = true;
      this.resizeHandle = handle;
      return;
    }
  }

  // Check if clicking on any box
  var hitIndex = this._hitTestBoxes(pos);
  if (hitIndex >= 0) {
    this._pushUndo();
    this.selectAnnotation(hitIndex);
    this.isDragging = true;
    var ann = this.annotations[hitIndex];
    this.dragOffset = {
      x: pos.x - Math.min(ann.x1, ann.x2),
      y: pos.y - Math.min(ann.y1, ann.y2)
    };
    return;
  }

  // Deselect and start drawing
  this.selectedIndex = -1;
  this._updateSidebar();
  this.isDrawing = true;
  this.drawStart = pos;
  this.drawCurrent = pos;
  this._render();
};

/**
 * Handle mousemove on canvas: update drawing, dragging, or resizing.
 * @param {MouseEvent} e
 */
AnnotationEditor.prototype._onMouseMove = function(e) {
  var pos = this._mouseToImage(e);

  if (this.isDrawing) {
    this.drawCurrent = pos;
    this._render();
    return;
  }

  if (this.isDragging && this.selectedIndex >= 0) {
    var ann = this.annotations[this.selectedIndex];
    var w = Math.abs(ann.x2 - ann.x1);
    var h = Math.abs(ann.y2 - ann.y1);
    var newX = pos.x - this.dragOffset.x;
    var newY = pos.y - this.dragOffset.y;

    // Clamp to canvas bounds
    newX = Math.max(0, Math.min(newX, this.canvas.width - w));
    newY = Math.max(0, Math.min(newY, this.canvas.height - h));

    ann.x1 = newX;
    ann.y1 = newY;
    ann.x2 = newX + w;
    ann.y2 = newY + h;
    this.dirty = true;
    this._render();
    return;
  }

  if (this.isResizing && this.selectedIndex >= 0) {
    this._doResize(pos);
    this.dirty = true;
    this._render();
    return;
  }

  // Update cursor based on what's under the mouse
  this._updateCursor(pos);
};

/**
 * Handle mouseup on canvas: finish drawing, dragging, or resizing.
 * @param {MouseEvent} e
 */
AnnotationEditor.prototype._onMouseUp = function(e) {
  if (e.button !== 0) return;

  if (this.isDrawing) {
    this.isDrawing = false;
    var pos = this._mouseToImage(e);
    var x1 = Math.min(this.drawStart.x, pos.x);
    var y1 = Math.min(this.drawStart.y, pos.y);
    var x2 = Math.max(this.drawStart.x, pos.x);
    var y2 = Math.max(this.drawStart.y, pos.y);

    // Discard if too small
    if ((x2 - x1) < ANNOTATION_MIN_BOX_SIZE ||
        (y2 - y1) < ANNOTATION_MIN_BOX_SIZE) {
      this.drawStart = null;
      this.drawCurrent = null;
      this._render();
      return;
    }

    this.drawStart = null;
    this.drawCurrent = null;

    // Show label picker for the new box
    this._showLabelPicker(x1, y1, x2, y2, e);
    return;
  }

  if (this.isDragging) {
    this.isDragging = false;
    this._updateSidebar();
  }

  if (this.isResizing) {
    this.isResizing = false;
    this.resizeHandle = null;
    // Normalize coordinates so x1 < x2, y1 < y2
    if (this.selectedIndex >= 0) {
      var ann = this.annotations[this.selectedIndex];
      var nx1 = Math.min(ann.x1, ann.x2);
      var ny1 = Math.min(ann.y1, ann.y2);
      var nx2 = Math.max(ann.x1, ann.x2);
      var ny2 = Math.max(ann.y1, ann.y2);
      ann.x1 = nx1;
      ann.y1 = ny1;
      ann.x2 = nx2;
      ann.y2 = ny2;
    }
    this._updateSidebar();
  }
};

/**
 * Hit-test boxes in reverse order (top-most first).
 * @param {{x: number, y: number}} pos
 * @return {number} Index of hit box, or -1
 */
AnnotationEditor.prototype._hitTestBoxes = function(pos) {
  for (var i = this.annotations.length - 1; i >= 0; i--) {
    var ann = this.annotations[i];
    var x1 = Math.min(ann.x1, ann.x2);
    var y1 = Math.min(ann.y1, ann.y2);
    var x2 = Math.max(ann.x1, ann.x2);
    var y2 = Math.max(ann.y1, ann.y2);
    if (pos.x >= x1 && pos.x <= x2 && pos.y >= y1 && pos.y <= y2) {
      return i;
    }
  }
  return -1;
};

/**
 * Hit-test resize handles on a box.
 * @param {{x: number, y: number}} pos
 * @param {Object} ann
 * @return {string|null} Handle name or null
 */
AnnotationEditor.prototype._hitTestHandles = function(pos, ann) {
  var handles = this._getHandlePositions(ann);
  var names = Object.keys(handles);
  for (var i = 0; i < names.length; i++) {
    var hp = handles[names[i]];
    if (Math.abs(pos.x - hp.x) <= ANNOTATION_HIT_THRESHOLD &&
        Math.abs(pos.y - hp.y) <= ANNOTATION_HIT_THRESHOLD) {
      return names[i];
    }
  }
  return null;
};

/**
 * Resize the selected box based on handle being dragged.
 * @param {{x: number, y: number}} pos
 */
AnnotationEditor.prototype._doResize = function(pos) {
  var ann = this.annotations[this.selectedIndex];

  // Clamp position to canvas bounds
  var px = Math.max(0, Math.min(pos.x, this.canvas.width));
  var py = Math.max(0, Math.min(pos.y, this.canvas.height));

  switch (this.resizeHandle) {
    case 'nw':
      ann.x1 = px;
      ann.y1 = py;
      break;
    case 'n':
      ann.y1 = py;
      break;
    case 'ne':
      ann.x2 = px;
      ann.y1 = py;
      break;
    case 'e':
      ann.x2 = px;
      break;
    case 'se':
      ann.x2 = px;
      ann.y2 = py;
      break;
    case 's':
      ann.y2 = py;
      break;
    case 'sw':
      ann.x1 = px;
      ann.y2 = py;
      break;
    case 'w':
      ann.x1 = px;
      break;
    default:
      break;
  }
};

/**
 * Update the cursor style based on position over handles or boxes.
 * @param {{x: number, y: number}} pos
 */
AnnotationEditor.prototype._updateCursor = function(pos) {
  var cursorClass = '';

  // Check handles on selected box
  if (this.selectedIndex >= 0) {
    var handle = this._hitTestHandles(pos, this.annotations[this.selectedIndex]);
    if (handle) {
      cursorClass = 'mode-resize-' + handle;
      this._setCursorClass(cursorClass);
      return;
    }
  }

  // Check if over any box
  var hitIndex = this._hitTestBoxes(pos);
  if (hitIndex >= 0) {
    cursorClass = 'mode-move';
  }

  this._setCursorClass(cursorClass);
};

/**
 * Set the cursor CSS class on the canvas.
 * @param {string} className
 */
AnnotationEditor.prototype._setCursorClass = function(className) {
  var canvas = $j(this.canvas);
  // Remove all mode-* classes
  var classes = (canvas.attr('class') || '').split(/\s+/);
  for (var i = 0; i < classes.length; i++) {
    if (classes[i].indexOf('mode-') === 0) {
      canvas.removeClass(classes[i]);
    }
  }
  if (className) {
    canvas.addClass(className);
  }
};

/**
 * Show the label picker dropdown near the drawn box.
 * @param {number} x1
 * @param {number} y1
 * @param {number} x2
 * @param {number} y2
 * @param {MouseEvent} mouseEvent
 */
AnnotationEditor.prototype._showLabelPicker = function(x1, y1, x2, y2, mouseEvent) {
  var self = this;

  this._hideLabelPicker();

  var picker = document.createElement('div');
  picker.className = 'annotation-label-picker';

  // Position near the box's top-right corner in screen coords
  var rect = this.canvas.getBoundingClientRect();
  var scaleX = rect.width / this.canvas.width;
  var scaleY = rect.height / this.canvas.height;
  var screenX = rect.left + x2 * scaleX + 5;
  var screenY = rect.top + y1 * scaleY;

  // Keep within viewport
  var viewW = window.innerWidth;
  var viewH = window.innerHeight;
  if (screenX + 180 > viewW) {
    screenX = rect.left + x1 * scaleX - 180;
  }
  if (screenY + 200 > viewH) {
    screenY = viewH - 210;
  }

  picker.style.left = screenX + 'px';
  picker.style.top = screenY + 'px';
  picker.style.position = 'fixed';

  // Build sorted label list (most recently used first)
  var sortedLabels = this._getSortedLabels();

  for (var i = 0; i < sortedLabels.length; i++) {
    var btn = document.createElement('button');
    btn.className = 'label-option';
    btn.textContent = sortedLabels[i];
    btn.setAttribute('data-label', sortedLabels[i]);
    btn.addEventListener('click', (function(label) {
      return function(e) {
        e.preventDefault();
        e.stopPropagation();
        self._pushUndo();
        self._addAnnotation(label, x1, y1, x2, y2);
        self._hideLabelPicker();
        self._trackLabelUsage(label);
      };
    })(sortedLabels[i]));
    picker.appendChild(btn);
  }

  // New label input at bottom
  var inputDiv = document.createElement('div');
  inputDiv.className = 'new-label-input';
  var input = document.createElement('input');
  input.type = 'text';
  input.placeholder = this.translations.NewLabel || 'New Label';
  input.addEventListener('keydown', function(e) {
    e.stopPropagation();
    if (e.key === 'Enter') {
      var label = input.value.trim().toLowerCase();
      if (label && /^[a-zA-Z0-9_-]+$/.test(label)) {
        self._pushUndo();
        self._addAnnotation(label, x1, y1, x2, y2);
        self._hideLabelPicker();
        self._trackLabelUsage(label);
        // Add to classLabels if new
        if (self.classLabels.indexOf(label) === -1) {
          self.classLabels.push(label);
        }
      }
    } else if (e.key === 'Escape') {
      self._hideLabelPicker();
      self._render();
    }
  });
  inputDiv.appendChild(input);
  picker.appendChild(inputDiv);

  document.body.appendChild(picker);

  // Focus the input for immediate typing
  setTimeout(function() {
    input.focus();
  }, 50);

  this._labelPicker = picker;
};

/**
 * Remove the label picker from the DOM.
 */
AnnotationEditor.prototype._hideLabelPicker = function() {
  if (this._labelPicker && this._labelPicker.parentNode) {
    this._labelPicker.parentNode.removeChild(this._labelPicker);
  }
  this._labelPicker = null;
};

/**
 * Get labels sorted by most recently used first.
 * @return {Array<string>}
 */
AnnotationEditor.prototype._getSortedLabels = function() {
  var labels = this.classLabels.slice();
  var recent = this.recentLabels;

  labels.sort(function(a, b) {
    var ia = recent.indexOf(a);
    var ib = recent.indexOf(b);
    // Recently used items get lower indices (higher priority)
    if (ia === -1 && ib === -1) return 0;
    if (ia === -1) return 1;
    if (ib === -1) return -1;
    return ia - ib;
  });

  return labels;
};

/**
 * Track label usage for MRU sorting.
 * @param {string} label
 */
AnnotationEditor.prototype._trackLabelUsage = function(label) {
  var idx = this.recentLabels.indexOf(label);
  if (idx >= 0) {
    this.recentLabels.splice(idx, 1);
  }
  this.recentLabels.unshift(label);
};

/**
 * Add a new annotation.
 * @param {string} label
 * @param {number} x1
 * @param {number} y1
 * @param {number} x2
 * @param {number} y2
 */
AnnotationEditor.prototype._addAnnotation = function(label, x1, y1, x2, y2) {
  this.annotations.push({
    label: label,
    x1: x1,
    y1: y1,
    x2: x2,
    y2: y2,
    confidence: 0
  });
  this.selectedIndex = this.annotations.length - 1;
  this.dirty = true;
  this._updateSidebar();
  this._render();
};

/**
 * Delete an annotation by index.
 * @param {number} index
 */
AnnotationEditor.prototype.deleteAnnotation = function(index) {
  if (index < 0 || index >= this.annotations.length) return;
  this._pushUndo();
  this.annotations.splice(index, 1);
  if (this.selectedIndex >= this.annotations.length) {
    this.selectedIndex = this.annotations.length - 1;
  }
  if (this.selectedIndex === index) {
    this.selectedIndex = -1;
  } else if (this.selectedIndex > index) {
    this.selectedIndex--;
  }
  this.dirty = true;
  this._updateSidebar();
  this._render();

  // If no annotations left, remove from training set
  if (this.annotations.length === 0 && this.currentFrameId) {
    var self = this;
    $j.ajax({
      url: thisUrl + '?request=training&action=delete',
      method: 'POST',
      data: {eid: this.eventId, fid: this.currentFrameId},
      dataType: 'json'
    }).done(function(data) {
      var resp = data.response || data;
      self.dirty = false;
      self._setStatus(
          self.translations.AnnotationsRemoved || 'Annotation removed from training set',
          'success'
      );
      if (resp.stats) {
        self.trainingStats = resp.stats;
        self._renderStats();
      }
    }).fail(function(jqxhr) {
      logAjaxFail(jqxhr);
    });
  }
};

/**
 * Accept a pending detection (remove pending flag, turn green).
 * @param {number} index
 */
AnnotationEditor.prototype.acceptAnnotation = function(index) {
  if (index < 0 || index >= this.annotations.length) return;
  if (!this.annotations[index].pending) return;
  this._pushUndo();
  delete this.annotations[index].pending;
  this.dirty = true;
  this._updateSidebar();
  this._render();
};

/**
 * Accept all pending detections at once.
 */
AnnotationEditor.prototype.acceptAllAnnotations = function() {
  var changed = false;
  for (var i = 0; i < this.annotations.length; i++) {
    if (this.annotations[i].pending) {
      if (!changed) {
        this._pushUndo();
        changed = true;
      }
      delete this.annotations[i].pending;
    }
  }
  if (changed) {
    this.dirty = true;
    this._updateSidebar();
    this._render();
  }
};

/**
 * Run object detection on the current frame via the server-side script.
 */
AnnotationEditor.prototype.detect = function() {
  var self = this;

  if (!this.hasDetectScript) {
    this._setStatus(
        this.translations.DetectNoScript || 'No detection script configured',
        'error'
    );
    return;
  }

  if (!this.currentFrameId) {
    this._setStatus(this.translations.LoadFrameFirst || 'Load a frame first', 'error');
    return;
  }

  this._setStatus(
      this.translations.DetectRunning || 'Running detection...',
      'saving'
  );

  $j('#annotationDetectBtn').prop('disabled', true);

  $j.ajax({
    url: thisUrl + '?request=training&action=detect',
    method: 'POST',
    data: {
      eid: this.eventId,
      fid: this.currentFrameId,
      mid: this.monitorId
    },
    dataType: 'json'
  }).done(function(data) {
    $j('#annotationDetectBtn').prop('disabled', false);

    if (data.result === 'Error') {
      self._setStatus(data.message, 'error');
      return;
    }
    var resp = data.response || data;
    var detections = resp.detections || [];

    if (detections.length === 0) {
      self._setStatus(
          self.translations.DetectNoResults || 'No objects detected',
          'error'
      );
      return;
    }

    // Remove existing pending annotations (from a previous detect)
    self._pushUndo();
    var kept = [];
    for (var i = 0; i < self.annotations.length; i++) {
      if (!self.annotations[i].pending) {
        kept.push(self.annotations[i]);
      }
    }
    self.annotations = kept;

    // Add new detections as pending (orange)
    for (var d = 0; d < detections.length; d++) {
      var det = detections[d];
      var ann = {
        label: det.label || 'unknown',
        confidence: det.confidence || 0,
        x1: 0,
        y1: 0,
        x2: 0,
        y2: 0,
        pending: true
      };
      if (det.bbox && Array.isArray(det.bbox)) {
        ann.x1 = det.bbox[0];
        ann.y1 = det.bbox[1];
        ann.x2 = det.bbox[2];
        ann.y2 = det.bbox[3];
      }
      self.annotations.push(ann);
    }

    self.dirty = true;
    self._updateSidebar();
    self._render();
    self._setStatus(
        detections.length + ' ' + (self.translations.DetectedObjects || 'object(s) detected — accept or reject each'),
        'success'
    );
  }).fail(function(jqxhr) {
    $j('#annotationDetectBtn').prop('disabled', false);
    self._setStatus(self.translations.DetectFailed || 'Detection failed', 'error');
    logAjaxFail(jqxhr);
  });
};

/**
 * Change the label of an annotation.
 * @param {number} index
 * @param {string} newLabel
 */
AnnotationEditor.prototype.relabelAnnotation = function(index, newLabel) {
  if (index < 0 || index >= this.annotations.length) return;
  this._pushUndo();
  this.annotations[index].label = newLabel;
  this.dirty = true;
  this._updateSidebar();
  this._render();
};

/**
 * Select an annotation by index.
 * @param {number} index
 */
AnnotationEditor.prototype.selectAnnotation = function(index) {
  this.selectedIndex = index;
  this._updateSidebar();
  this._render();
};

/**
 * Push current annotations state to the undo stack.
 */
AnnotationEditor.prototype._pushUndo = function() {
  // Deep copy annotations
  var copy = JSON.parse(JSON.stringify(this.annotations));
  this.undoStack.push({
    annotations: copy,
    selectedIndex: this.selectedIndex
  });
  if (this.undoStack.length > ANNOTATION_MAX_UNDO) {
    this.undoStack.shift();
  }
};

/**
 * Pop and restore the last state from the undo stack.
 */
AnnotationEditor.prototype.undo = function() {
  if (this.undoStack.length === 0) return;
  var state = this.undoStack.pop();
  this.annotations = state.annotations;
  this.selectedIndex = state.selectedIndex;
  this.dirty = true;
  this._updateSidebar();
  this._render();
};

/**
 * Rebuild the sidebar object list HTML.
 */
AnnotationEditor.prototype._updateSidebar = function() {
  var self = this;
  var list = $j('#annotationObjectList');
  if (!list.length) return;

  list.empty();

  for (var i = 0; i < this.annotations.length; i++) {
    var ann = this.annotations[i];
    var colorIndex = this._getColorIndex(ann.label);
    var color = ANNOTATION_COLORS[colorIndex % ANNOTATION_COLORS.length];
    var isSelected = (i === this.selectedIndex);

    var li = $j('<li>')
        .addClass('annotation-object-item')
        .toggleClass('selected', isSelected)
        .attr('data-index', i);

    var swatchColor = ann.pending ? '#ff8c00' : '#28a745';
    var swatch = $j('<span>')
        .addClass('color-swatch')
        .css('background-color', swatchColor);

    var labelSpan = $j('<span>')
        .addClass('object-label')
        .text(ann.label);

    li.append(swatch).append(labelSpan);

    if (ann.confidence > 0) {
      var confSpan = $j('<span>')
          .addClass('object-confidence')
          .text(Math.round(ann.confidence * 100) + '%');
      li.append(confSpan);
    }

    if (ann.pending) {
      var acceptBtn = $j('<button>')
          .addClass('btn-accept')
          .attr('title', self.translations.AcceptDetection || 'Accept')
          .html('&#10003;')
          .attr('data-index', i);
      li.append(acceptBtn);
    }

    var removeBtn = $j('<button>')
        .addClass('btn-remove')
        .attr('title', self.translations.DeleteBox || 'Delete')
        .html('&times;')
        .attr('data-index', i);

    li.append(removeBtn);
    list.append(li);
  }

  // Bind click handlers
  list.find('.annotation-object-item').on('click', function(e) {
    if ($j(e.target).hasClass('btn-remove') || $j(e.target).hasClass('btn-accept')) return;
    var idx = parseInt($j(this).attr('data-index'), 10);
    self.selectAnnotation(idx);
  });

  list.find('.btn-accept').on('click', function(e) {
    e.stopPropagation();
    var idx = parseInt($j(this).attr('data-index'), 10);
    self.acceptAnnotation(idx);
  });

  list.find('.btn-remove').on('click', function(e) {
    e.stopPropagation();
    var idx = parseInt($j(this).attr('data-index'), 10);
    self._pushUndo();
    self.deleteAnnotation(idx);
  });
};

/**
 * Update the label dropdown select element with current labels.
 */
AnnotationEditor.prototype._updateLabelDropdown = function() {
  var select = $j('#annotationLabelSelect');
  if (!select.length) return;

  select.empty();
  for (var i = 0; i < this.classLabels.length; i++) {
    select.append(
        $j('<option>').val(this.classLabels[i]).text(this.classLabels[i])
    );
  }
};

/**
 * Load class labels from the server.
 */
AnnotationEditor.prototype._loadLabels = function() {
  var self = this;
  $j.getJSON(thisUrl + '?request=training&action=labels')
      .done(function(data) {
        var resp = data.response || data;
        self.classLabels = resp.labels || [];
        self._updateLabelDropdown();
      })
      .fail(logAjaxFail);
};

/**
 * Load training stats from the server.
 */
AnnotationEditor.prototype._loadStats = function() {
  var self = this;
  $j.getJSON(thisUrl + '?request=training&action=status')
      .done(function(data) {
        var resp = data.response || data;
        self.trainingStats = resp.stats || null;
        self._renderStats();
      })
      .fail(logAjaxFail);
};

/**
 * Build and render training stats HTML in the sidebar.
 */
AnnotationEditor.prototype._renderStats = function() {
  var container = $j('#annotationStats');
  if (!container.length) return;

  container.empty();

  var t = this.translations;
  var stats = this.trainingStats || {total_images: 0, total_classes: 0, images_per_class: {}};

  var header = $j('<div>')
      .addClass('annotation-stats-header')
      .text(t.TrainingDataStats || 'Training Data Statistics');
  container.append(header);

  var dl = $j('<dl>');
  dl.append(
      $j('<dt>').text(t.TotalAnnotatedImages || 'Total annotated images'),
      $j('<dd>').text(stats.total_images)
  );
  if (stats.background_images > 0) {
    dl.append(
        $j('<dt>').text(t.BackgroundImages || 'Background images (no objects)'),
        $j('<dd>').text(stats.background_images)
    );
  }
  container.append(dl);

  // Per-class image counts — the main info
  var classData = stats.images_per_class || {};
  var classNames = Object.keys(classData);

  if (classNames.length > 0) {
    container.append($j('<div>').css({'font-weight': '600', 'margin': '6px 0 4px'}).text(t.ImagesPerClass || 'Images per class'));

    var hasLowClass = false;
    for (var i = 0; i < classNames.length; i++) {
      var className = classNames[i];
      var count = classData[className];
      var row = $j('<div>').addClass('class-count');
      row.append($j('<span>').text(className));
      row.append($j('<span>').addClass('count').text(count));
      container.append(row);
      if (count < 50) hasLowClass = true;
    }

    // Training guidance
    var guidance = $j('<div>').addClass('training-guidance');
    if (!hasLowClass) guidance.addClass('training-ready');
    guidance.text(
        t.TrainingGuidance ||
        'Training is generally possible with at least 50-100 images per class. For best results, aim for 200+ images per class.'
    );
    container.append(guidance);
  } else {
    container.append($j('<div>').css({'color': '#6c757d', 'padding': '4px 0'}).text(t.NoTrainingData || 'No training data yet. Save annotations to build your dataset.'));
  }
};

/**
 * Update the frame selector bar with available frames.
 * @param {Array<string>} availableFrames
 */
AnnotationEditor.prototype._updateFrameSelector = function(availableFrames) {
  var self = this;
  var container = $j('#annotationFrameSelector');
  if (!container.length) return;

  // Show/hide frame buttons based on availability
  container.find('.frame-btn').each(function() {
    var frameId = $j(this).attr('data-frame');
    if (availableFrames.indexOf(frameId) >= 0) {
      $j(this).show();
    } else {
      $j(this).hide();
    }
  });

  // Highlight current frame
  container.find('.frame-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
  container.find('.frame-btn[data-frame="' + self.currentFrameId + '"]')
      .removeClass('btn-outline-secondary')
      .addClass('btn-primary');

  // Bind click handlers
  container.find('.frame-btn').off('click.annEditor').on('click.annEditor', function(e) {
    e.preventDefault();
    var frameId = $j(this).attr('data-frame');
    self.switchFrame(frameId);

    // Update highlight
    container.find('.frame-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
    $j(this).removeClass('btn-outline-secondary').addClass('btn-primary');
  });

  // Frame number input for going to specific frame
  var frameInput = container.find('.frame-input');
  frameInput.off('keydown.annEditor').on('keydown.annEditor', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      var val = parseInt($j(this).val(), 10);
      if (val > 0 && val <= self.totalFrames) {
        self.switchFrame(String(val));
      }
    }
  });

  // Show total frames count
  container.find('.frame-total').text('/ ' + this.totalFrames);
};

/**
 * Save annotations via AJAX POST.
 */
AnnotationEditor.prototype.save = function() {
  var self = this;

  if (!this.currentFrameId) {
    this._setStatus(this.translations.NoFrameLoaded || 'No frame loaded', 'error');
    return;
  }

  // Only save accepted (non-pending) annotations
  var accepted = [];
  for (var i = 0; i < this.annotations.length; i++) {
    if (!this.annotations[i].pending) {
      accepted.push(this.annotations[i]);
    }
  }

  if (accepted.length === 0) {
    var msg = this.translations.BackgroundImageConfirm ||
        'No objects marked. Save as a background image (no objects)?\n\nBackground images help the model learn to reduce false positives.';
    if (!confirm(msg)) return;
  }

  this._setStatus(this.translations.Saving || 'Saving...', 'saving');

  $j.ajax({
    url: thisUrl + '?request=training&action=save',
    method: 'POST',
    data: {
      eid: this.eventId,
      fid: this.currentFrameId,
      annotations: JSON.stringify(accepted),
      width: this.imageNaturalW,
      height: this.imageNaturalH
    },
    dataType: 'json'
  })
      .done(function(data) {
        if (data.result === 'Error') {
          self._setStatus(data.message || self.translations.SaveFailed || 'Save failed', 'error');
          return;
        }
        var resp = data.response || data;
        self.dirty = false;
        self._setStatus(
            self.translations.AnnotationSaved || 'Annotation saved to training set',
            'success'
        );
        $j('#annotationSaveBtn').removeClass('btn-success').addClass('btn-saved');
        setTimeout(function() {
          $j('#annotationSaveBtn').removeClass('btn-saved').addClass('btn-success');
        }, 3000);

        // Update stats from save response
        if (resp.stats) {
          self.trainingStats = resp.stats;
          self._renderStats();
        }

        // Refresh labels in case new ones were added
        if (resp.stats && resp.stats.class_labels) {
          self.classLabels = resp.stats.class_labels;
          self._updateLabelDropdown();
        }
      })
      .fail(function(jqxhr) {
        self._setStatus(self.translations.SaveFailed || 'Save failed', 'error');
        logAjaxFail(jqxhr);
      });
};

/**
 * Set the status message in the action bar.
 * @param {string} msg
 * @param {string} type  'success', 'error', or 'saving'
 */
AnnotationEditor.prototype._setStatus = function(msg, type) {
  var el = $j('#annotationStatus');
  if (!el.length) return;

  el.text(msg)
      .removeClass('error saving')
      .addClass(type === 'error' ? 'error' : (type === 'saving' ? 'saving' : ''));

  // Clear success/saving messages after a delay
  if (type !== 'error') {
    setTimeout(function() {
      el.text('');
    }, 4000);
  }
};

/**
 * Get consistent color index for a label.
 * @param {string} label
 * @return {number}
 */
AnnotationEditor.prototype._getColorIndex = function(label) {
  // Use classLabels index if available, else hash the label
  var idx = this.classLabels.indexOf(label);
  if (idx >= 0) return idx;

  // Simple hash for labels not in classLabels
  var hash = 0;
  for (var i = 0; i < label.length; i++) {
    hash = ((hash << 5) - hash) + label.charCodeAt(i);
    hash = hash & hash; // Convert to 32-bit integer
  }
  return Math.abs(hash);
};
