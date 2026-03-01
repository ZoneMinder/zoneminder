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
    console.error('Training: canvas not found: ' + this.canvasId);
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

  // Warn on page navigation if unsaved annotations exist
  this._beforeUnloadHandler = function(e) {
    if (self.dirty) {
      e.preventDefault();
      e.returnValue = '';
    }
  };
  window.addEventListener('beforeunload', this._beforeUnloadHandler);

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
        self._setStatus(self.translations.TrainingFailedToLoadEvent || 'Failed to load event data', 'error');
        logAjaxFail(jqxhr);
      });
};

/**
 * Close the annotation panel. Prompts if dirty.
 */
AnnotationEditor.prototype.close = function() {
  if (this.dirty) {
    var msg = this.translations.TrainingUnsaved ||
        'You have unsaved annotations. Discard changes?';
    if (!confirm(msg)) return;
  }

  $j('#annotationPanel').removeClass('open');
  $j('#eventVideo').show();
  this._hideLabelPicker();

  // Remove page-leave guard
  if (this._beforeUnloadHandler) {
    window.removeEventListener('beforeunload', this._beforeUnloadHandler);
    this._beforeUnloadHandler = null;
  }

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
  this._updateFrameInfo();

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
    // If numeric frame exceeds total, clamp to max and retry
    var num = parseInt(frameId, 10);
    if (!isNaN(num) && self.totalFrames > 0 && num > self.totalFrames) {
      self._setStatus(self.translations.TrainingFailedToLoadFrame || 'Failed to load frame image', 'error');
      self.switchFrame(String(self.totalFrames));
      return;
    }
    self._setStatus(self.translations.TrainingFailedToLoadFrame || 'Failed to load frame image', 'error');
  };

  img.src = thisUrl + '?view=image&eid=' + this.eventId + '&fid=' + frameId;
};

/**
 * Update the "Frame: X of Y" indicator below the canvas.
 */
AnnotationEditor.prototype._updateFrameInfo = function() {
  var el = $j('#annotationFrameInfo');
  if (!el.length) return;

  var frameLabel = this.currentFrameId;
  var num = parseInt(frameLabel, 10);
  if (!isNaN(num)) {
    frameLabel = String(num);
  }
  if (this.totalFrames > 0) {
    el.text((this.translations.Frame || 'Frame') + ': ' +
        frameLabel + ' / ' + this.totalFrames);
  } else {
    el.text((this.translations.Frame || 'Frame') + ': ' + frameLabel);
  }
};

/**
 * Switch to a different frame. Prompts if dirty.
 * @param {string|number} frameId
 */
AnnotationEditor.prototype.switchFrame = function(frameId) {
  if (this.dirty) {
    var msg = this.translations.TrainingUnsaved ||
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
          self.translations.TrainingRemoved || 'Training annotation removed',
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
        this.translations.TrainingDetectNoScript || 'No detection script configured',
        'error'
    );
    return;
  }

  if (!this.currentFrameId) {
    this._setStatus(this.translations.TrainingLoadFrameFirst || 'Load a frame first', 'error');
    return;
  }

  this._setStatus(
      this.translations.TrainingDetectRunning || 'Running detection...',
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
          self.translations.TrainingDetectNoResults || 'No objects detected'
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
        detections.length + ' ' + (self.translations.TrainingDetectedObjects || 'object(s) detected — accept or reject each'),
        'success'
    );
  }).fail(function(jqxhr) {
    $j('#annotationDetectBtn').prop('disabled', false);
    self._setStatus(self.translations.TrainingDetectFailed || 'Detection failed', 'error');
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
        .attr('title', self.translations.TrainingDeleteBox || 'Delete')
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
  var self = this;

  container.empty();

  var t = this.translations;
  var stats = this.trainingStats || {total_images: 0, total_classes: 0, images_per_class: {}};
  var hasData = stats.total_images > 0 || stats.background_images > 0;

  // Header
  var header = $j('<div>').addClass('annotation-stats-header');
  header.append($j('<span>').text(t.TrainingDataStats || 'Training Data Statistics'));
  container.append(header);

  // Show/hide delete-all button in sidebar header based on data
  var sidebarTrashBtn = $j('#annotationDeleteAllBtn');
  if (hasData) {
    sidebarTrashBtn.show().off('click.deleteAll').on('click.deleteAll', function() {
      self._deleteAllTrainingData();
    });
  } else {
    sidebarTrashBtn.hide();
  }

  // Compact stat rows
  var row = function(label, value) {
    return $j('<div>').addClass('stat-row')
        .append($j('<span>').addClass('stat-label').text(label))
        .append($j('<span>').addClass('stat-value').text(value));
  };

  container.append(row(t.TrainingTotalImages || 'Annotated images', stats.total_images));
  if (stats.background_images > 0) {
    container.append(row(t.TrainingBackgroundImages || 'Background images', stats.background_images));
  }

  // Per-class image counts
  var classData = stats.images_per_class || {};
  var classNames = Object.keys(classData);

  if (classNames.length > 0) {
    container.append($j('<div>').css({'font-weight': '600', 'margin': '4px 0 2px'}).text(t.ImagesPerClass || 'Images per class'));

    var hasLowClass = false;
    for (var i = 0; i < classNames.length; i++) {
      container.append(row(classNames[i], classData[classNames[i]]));
      if (classData[classNames[i]] < 50) hasLowClass = true;
    }

    var guidance = $j('<div>').addClass('training-guidance');
    if (!hasLowClass) guidance.addClass('training-ready');
    guidance.text(t.TrainingGuidance || 'Aim for 50-100+ images per class.');
    container.append(guidance);
  } else {
    container.append($j('<div>').css({'color': '#6c757d', 'padding': '4px 0'}).text(t.TrainingNoData || 'No training data yet.'));
  }
};

AnnotationEditor.prototype._deleteAllTrainingData = function() {
  var self = this;
  var t = this.translations;
  var answer = prompt(t.ConfirmDeleteTrainingData || 'This will permanently delete ALL training data. Type "agree" to confirm:');
  if (answer !== 'agree') return;

  $j.ajax({url: thisUrl + '?request=training&action=delete_all', method: 'POST', dataType: 'json'})
      .done(function(resp) {
        if (resp.result === 'Ok') {
          self.trainingStats = resp.stats || {};
          self.classLabels = (resp.stats && resp.stats.class_labels) ? resp.stats.class_labels : [];
          self._renderStats();
          self._setStatus(t.TrainingDataDeleted || 'All training data deleted.');
        } else {
          self._setStatus(resp.message || 'Delete failed', 'error');
        }
      })
      .fail(function() {
        self._setStatus(t.TrainingSaveFailed || 'Request failed', 'error');
      });
};

/**
 * Open an overlay showing thumbnail grid of all event frames with pagination.
 * Click a thumbnail to switch to that frame.
 */
AnnotationEditor.prototype.browseFrames = function() {
  var self = this;
  var total = this.totalFrames;
  if (!total || total <= 0) {
    this._setStatus(this.translations.TrainingLoadFrameFirst || 'Load a frame first', 'error');
    return;
  }

  // Remove any existing overlay
  $j('#frameBrowseOverlay').remove();

  var thumbWidth = 160;
  var perPage = 50;
  var totalPages = Math.ceil(total / perPage);
  // Start on the page containing the current frame
  var curNum = parseInt(this.currentFrameId, 10);
  var currentPage = (!isNaN(curNum) && curNum > 0)
    ? Math.ceil(curNum / perPage) : 1;

  var overlay = $j('<div id="frameBrowseOverlay" class="frame-browse-overlay">');
  var panel = $j('<div class="frame-browse-panel">');

  // Header
  var header = $j('<div class="frame-browse-header">');
  header.append($j('<span>').text(
    (this.translations.TrainingBrowseFrames || 'Browse Frames') +
    ' (' + total + ')'));
  var closeBtn = $j('<button class="frame-browse-close">&times;</button>');
  closeBtn.on('click', function() { cleanup(); });
  header.append(closeBtn);
  panel.append(header);

  // Grid container
  var grid = $j('<div class="frame-browse-grid">');

  // Pagination container
  var paginationWrap = $j('<nav class="frame-browse-pagination">');
  var paginationUl = $j('<ul class="pagination pagination-sm justify-content-center mb-0">');
  paginationWrap.append(paginationUl);

  function renderPage(page) {
    currentPage = page;
    grid.empty();

    var start = (page - 1) * perPage + 1;
    var end = Math.min(page * perPage, total);

    for (var i = start; i <= end; i++) {
      (function(fid) {
        var cell = $j('<div class="frame-browse-cell">');
        var img = $j('<img>')
            .attr('loading', 'lazy')
            .attr('src', thisUrl + '?view=image&eid=' + self.eventId +
              '&fid=' + fid + '&width=' + thumbWidth)
            .attr('alt', 'Frame ' + fid);
        var label = $j('<div class="frame-browse-label">').text(fid);

        if (String(fid) === String(self.currentFrameId)) {
          cell.addClass('active');
        }

        cell.on('click', function() {
          cleanup();
          self.switchFrame(String(fid));
        });

        cell.append(img).append(label);
        grid.append(cell);
      })(i);
    }

    // Scroll grid to top
    grid.scrollTop(0);

    // Rebuild pagination
    renderPagination(page);
  }

  function renderPagination(page) {
    paginationUl.empty();

    // Prev
    var prevLi = $j('<li class="page-item">').toggleClass('disabled', page <= 1);
    prevLi.append($j('<a class="page-link" href="#">&laquo;</a>')
        .on('click', function(e) {
          e.preventDefault();
          if (page > 1) renderPage(page - 1);
        }));
    paginationUl.append(prevLi);

    // Determine which page numbers to show
    var pages = buildPageNumbers(page, totalPages);
    for (var p = 0; p < pages.length; p++) {
      var val = pages[p];
      if (val === '...') {
        paginationUl.append(
          $j('<li class="page-item disabled">')
              .append($j('<span class="page-link">').text('...'))
        );
      } else {
        (function(num) {
          var li = $j('<li class="page-item">').toggleClass('active', num === page);
          li.append($j('<a class="page-link" href="#">').text(num)
              .on('click', function(e) {
                e.preventDefault();
                renderPage(num);
              }));
          paginationUl.append(li);
        })(val);
      }
    }

    // Next
    var nextLi = $j('<li class="page-item">')
        .toggleClass('disabled', page >= totalPages);
    nextLi.append($j('<a class="page-link" href="#">&raquo;</a>')
        .on('click', function(e) {
          e.preventDefault();
          if (page < totalPages) renderPage(page + 1);
        }));
    paginationUl.append(nextLi);
  }

  function buildPageNumbers(current, last) {
    // Always show first, last, and a window around current
    if (last <= 7) {
      var all = [];
      for (var i = 1; i <= last; i++) all.push(i);
      return all;
    }
    var pages = [];
    pages.push(1);
    var rangeStart = Math.max(2, current - 1);
    var rangeEnd = Math.min(last - 1, current + 1);
    if (rangeStart > 2) pages.push('...');
    for (var i = rangeStart; i <= rangeEnd; i++) pages.push(i);
    if (rangeEnd < last - 1) pages.push('...');
    pages.push(last);
    return pages;
  }

  function cleanup() {
    overlay.remove();
    $j(document).off('keydown.frameBrowse');
  }

  renderPage(currentPage);
  panel.append(grid);
  panel.append(paginationWrap);
  overlay.append(panel);
  $j('body').append(overlay);

  // Close on overlay background click
  overlay.on('click', function(e) {
    if (e.target === overlay[0]) cleanup();
  });

  // Close on Escape
  $j(document).on('keydown.frameBrowse', function(e) {
    if (e.key === 'Escape') cleanup();
  });
};

/**
 * Open a read-only browse overlay showing training folder contents.
 */
AnnotationEditor.prototype.browseTrainingData = function() {
  var self = this;
  var t = this.translations;

  // Helper: file icon class from extension
  function fileIcon(name) {
    var ext = name.split('.').pop().toLowerCase();
    if (ext === 'jpg' || ext === 'jpeg' || ext === 'png') return 'fa-file-image-o';
    if (ext === 'txt') return 'fa-file-text-o';
    if (ext === 'yaml' || ext === 'yml') return 'fa-file-code-o';
    return 'fa-file-o';
  }

  // Helper: is image extension
  function isImage(name) {
    var ext = name.split('.').pop().toLowerCase();
    return ext === 'jpg' || ext === 'jpeg' || ext === 'png';
  }

  // Helper: is text extension
  function isText(name) {
    var ext = name.split('.').pop().toLowerCase();
    return ext === 'txt' || ext === 'yaml' || ext === 'yml';
  }

  // Build overlay
  var overlay = $j('<div>').addClass('training-browse-overlay');
  var panel = $j('<div>').addClass('training-browse-panel');

  // Header
  var header = $j('<div>').addClass('browse-header');
  header.append($j('<span>').text(t.TrainingBrowse || 'Browse Training Data'));
  var pathSpan = $j('<span>').addClass('browse-path');
  header.append(pathSpan);
  var browseChanged = false;

  var closeBrowse = function() {
    overlay.remove();
    if (browseChanged) {
      $j.getJSON(thisUrl + '?request=training&action=status').done(function(data) {
        var resp = data.response || data;
        if (resp.stats) {
          self.trainingStats = resp.stats;
          if (resp.stats.class_labels) self.classLabels = resp.stats.class_labels;
          self._renderStats();
          self._updateSidebar();
        }
      });
    }
  };

  var closeBtn = $j('<button>').addClass('browse-close').html('&times;');
  closeBtn.on('click', closeBrowse);
  header.append(closeBtn);
  panel.append(header);

  // Body: two-panel layout
  var body = $j('<div>').addClass('browse-body');
  var treePanel = $j('<div>').addClass('browse-tree');
  var rightPanel = $j('<div>').addClass('browse-right');
  var filesArea = $j('<div>').addClass('browse-files');
  var previewArea = $j('<div>').addClass('browse-preview').hide();

  rightPanel.append(filesArea);
  rightPanel.append(previewArea);
  body.append(treePanel);
  body.append(rightPanel);
  panel.append(body);

  // Loading state
  treePanel.html('<div class="browse-empty-msg">' + (t.TrainingLoading || 'Loading...') + '</div>');

  overlay.append(panel);
  overlay.on('click', function(e) {
    if (e.target === overlay[0]) closeBrowse();
  });
  $j('body').append(overlay);

  // State
  var selectedDirPath = null;
  var selectedFileName = null;
  var treeData = null;

  // Collect all files in a node (for dir nodes, list direct file children)
  function getFilesForPath(nodes, path) {
    if (path === null) return [];
    for (var i = 0; i < nodes.length; i++) {
      var node = nodes[i];
      if (node.type === 'dir' && node.path === path) {
        // Return file children of this dir
        var files = [];
        if (node.children) {
          for (var j = 0; j < node.children.length; j++) {
            if (node.children[j].type === 'file') {
              files.push(node.children[j]);
            }
          }
        }
        return files;
      }
      if (node.type === 'dir' && node.children) {
        var found = getFilesForPath(node.children, path);
        if (found.length > 0 || pathStartsWith(path, node.path)) return found;
      }
    }
    return [];
  }

  function pathStartsWith(full, prefix) {
    return full === prefix || full.indexOf(prefix + '/') === 0;
  }

  // Find a specific node by path
  function findNode(nodes, path) {
    for (var i = 0; i < nodes.length; i++) {
      if (nodes[i].path === path) return nodes[i];
      if (nodes[i].type === 'dir' && nodes[i].children) {
        var found = findNode(nodes[i].children, path);
        if (found) return found;
      }
    }
    return null;
  }

  // Remove a node from the tree by path
  function removeFromTree(nodes, path) {
    for (var i = 0; i < nodes.length; i++) {
      if (nodes[i].path === path) {
        nodes.splice(i, 1);
        return true;
      }
      if (nodes[i].type === 'dir' && nodes[i].children) {
        if (removeFromTree(nodes[i].children, path)) return true;
      }
    }
    return false;
  }

  // Collect root-level files (files not inside any dir)
  function getRootFiles(nodes) {
    var files = [];
    for (var i = 0; i < nodes.length; i++) {
      if (nodes[i].type === 'file') files.push(nodes[i]);
    }
    return files;
  }

  // Show files in the right panel for a given directory path
  function showFiles(dirPath) {
    selectedDirPath = dirPath;
    selectedFileName = null;
    previewArea.hide();
    filesArea.empty();

    var files;
    if (dirPath === '') {
      // Root: show root-level files
      files = getRootFiles(treeData);
    } else {
      files = getFilesForPath(treeData, dirPath);
    }

    // Files header
    var fHeader = $j('<div>').addClass('browse-files-header');
    fHeader.append($j('<span>').text(dirPath || '/'));
    fHeader.append($j('<span>').addClass('file-count').text(files.length + ' files'));
    filesArea.append(fHeader);

    if (files.length === 0) {
      filesArea.append($j('<div>').addClass('browse-empty-msg').text(t.TrainingNoFiles || 'No files'));
      return;
    }

    for (var i = 0; i < files.length; i++) {
      (function(file) {
        var row = $j('<div>').addClass('browse-file-row');
        row.append($j('<span>').addClass('file-name').text(file.name));
        if (file.size !== undefined) {
          row.append($j('<span>').addClass('file-size').text(human_filesize(file.size)));
        }

        // Delete button for image/label files
        var dirBase = (file.path || '').split('/')[0];
        if (dirBase === 'images' || dirBase === 'labels') {
          var delBtn = $j('<button>').addClass('browse-file-delete')
              .attr('title', 'Delete image + label pair')
              .html('<i class="fa fa-trash"></i>');
          delBtn.on('click', function(e) {
            e.stopPropagation();
            if (!confirm(t.TrainingConfirmDeleteFile || 'Delete this file and its paired image/label?')) return;
            delBtn.prop('disabled', true);
            $j.ajax({url: thisUrl + '?request=training&action=browse_delete&path=' +
                encodeURIComponent(file.path), method: 'POST', dataType: 'json'})
                .done(function(data) {
                  var resp = data.response || data;
                  browseChanged = true;
                  // Remove deleted files from treeData
                  if (resp.deleted) {
                    for (var d = 0; d < resp.deleted.length; d++) {
                      removeFromTree(treeData, resp.deleted[d]);
                    }
                  }
                  // Close preview if showing this file
                  if (selectedFileName === file.name) {
                    previewArea.hide();
                    selectedFileName = null;
                  }
                  // Re-render current directory
                  showFiles(selectedDirPath);
                })
                .fail(function() {
                  alert(t.TrainingDeleteFailed || 'Failed to delete');
                  delBtn.prop('disabled', false);
                });
          });
          row.append(delBtn);
        }

        row.on('click', function() {
          filesArea.find('.browse-file-row').removeClass('selected');
          row.addClass('selected');
          showPreview(file);
        });
        filesArea.append(row);
      })(files[i]);
    }
  }

  // Show file preview
  function showPreview(file) {
    selectedFileName = file.name;
    previewArea.empty().show();

    var pvHeader = $j('<div>').addClass('browse-preview-header');
    pvHeader.append($j('<span>').text(file.name));
    var pvClose = $j('<button>').addClass('preview-close').html('&times;');
    pvClose.on('click', function() {
      previewArea.hide();
      filesArea.find('.browse-file-row').removeClass('selected');
      selectedFileName = null;
    });
    pvHeader.append(pvClose);
    previewArea.append(pvHeader);

    var pvContent = $j('<div>').addClass('browse-preview-content');
    previewArea.append(pvContent);

    var fileUrl = thisUrl + '?request=training&action=browse_file&path=' +
        encodeURIComponent(file.path);

    if (isImage(file.name)) {
      var container = $j('<div>').addClass('browse-preview-img-wrap');
      var img = $j('<img>').attr('src', fileUrl)
          .attr('alt', file.name)
          .on('error', function() {
            pvContent.html('<em>' + (t.TrainingFailedToLoadFrame || 'Failed to load image') + '</em>');
          });
      var canvas = $j('<canvas>').addClass('browse-preview-canvas')[0];
      container.append(img);
      container.append(canvas);
      pvContent.append(container);

      // Once image loads, fetch label file and draw boxes
      img.on('load', function() {
        var natW = img[0].naturalWidth;
        var natH = img[0].naturalHeight;
        var dispW = img[0].clientWidth;
        var dispH = img[0].clientHeight;
        canvas.width = dispW;
        canvas.height = dispH;
        $j(canvas).css({width: dispW + 'px', height: dispH + 'px'});

        // Derive label file path from image path
        var stem = file.name.replace(/\.[^.]+$/, '');
        var lblPath = file.path.replace(/^images\//, 'labels/')
            .replace(/\.[^.]+$/, '.txt');
        var lblUrl = thisUrl + '?request=training&action=browse_file&path=' +
            encodeURIComponent(lblPath);

        // Fetch class labels and label file in parallel
        $j.when(
            $j.getJSON(thisUrl + '?request=training&action=labels'),
            $j.getJSON(lblUrl)
        ).done(function(labelsResp, lblResp) {
          var classLabels = (labelsResp[0].response || labelsResp[0]).labels || [];
          var content = (lblResp[0].response || lblResp[0]).content || '';
          var lines = content.split('\n').filter(function(l) {
            return l.trim().length > 0;
          });
          if (lines.length === 0) return;

          var ctx = canvas.getContext('2d');
          for (var li = 0; li < lines.length; li++) {
            var parts = lines[li].trim().split(/\s+/);
            if (parts.length < 5) continue;
            var classId = parseInt(parts[0], 10);
            var cx = parseFloat(parts[1]);
            var cy = parseFloat(parts[2]);
            var bw = parseFloat(parts[3]);
            var bh = parseFloat(parts[4]);

            // Convert YOLO normalized to display pixels
            var x = (cx - bw / 2) * dispW;
            var y = (cy - bh / 2) * dispH;
            var w = bw * dispW;
            var h = bh * dispH;

            var color = ANNOTATION_COLORS[classId % ANNOTATION_COLORS.length];
            var label = classLabels[classId] || ('class ' + classId);

            // Draw box
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.strokeRect(x, y, w, h);

            // Draw label background
            ctx.font = '11px sans-serif';
            var textW = ctx.measureText(label).width + 6;
            var textH = 16;
            var labelY = y - textH;
            if (labelY < 0) labelY = y;
            ctx.fillStyle = color;
            ctx.fillRect(x, labelY, textW, textH);

            // Draw label text
            ctx.fillStyle = '#fff';
            ctx.fillText(label, x + 3, labelY + 12);
          }
        });
      });
    } else if (isText(file.name)) {
      pvContent.html('<em>' + (t.TrainingLoading || 'Loading...') + '</em>');
      $j.getJSON(fileUrl).done(function(data) {
        var resp = data.response || data;
        pvContent.empty();
        pvContent.append($j('<pre>').text(resp.content || '(empty)'));
      }).fail(function() {
        pvContent.html('<em>' + (t.TrainingSaveFailed || 'Failed to load file') + '</em>');
      });
    } else {
      pvContent.html('<em>' + (t.TrainingPreviewUnavailable || 'Preview not available for this file type') + '</em>');
    }
  }

  // Build tree nodes recursively in the left panel
  function buildTreeNodes(container, nodes, depth) {
    for (var i = 0; i < nodes.length; i++) {
      (function(node) {
        if (node.type === 'dir') {
          var treeNode = $j('<div>').addClass('browse-tree-node')
              .css('padding-left', (8 + depth * 12) + 'px')
              .attr('data-path', node.path);
          var icon = $j('<i>').addClass('fa fa-folder');
          treeNode.append(icon);
          treeNode.append($j('<span>').addClass('tree-label').text(node.name));
          container.append(treeNode);

          var childContainer = $j('<div>').addClass('browse-tree-children');
          container.append(childContainer);

          // Click to select directory
          treeNode.on('click', function(e) {
            e.stopPropagation();
            // Update selected state
            treePanel.find('.browse-tree-node').removeClass('selected');
            treeNode.addClass('selected');
            // Toggle open/close icon
            if (icon.hasClass('fa-folder-open')) {
              icon.removeClass('fa-folder-open').addClass('fa-folder');
              childContainer.hide();
            } else {
              icon.removeClass('fa-folder').addClass('fa-folder-open');
              childContainer.show();
            }
            showFiles(node.path);
          });

          // Build children (initially hidden except first level)
          if (node.children && node.children.length > 0) {
            buildTreeNodes(childContainer, node.children, depth + 1);
          }
          if (depth > 0) childContainer.hide();
        } else if (node.type === 'file' && depth === 0) {
          // Root-level files (e.g., data.yaml) shown in tree
          var fileNode = $j('<div>').addClass('browse-tree-node')
              .css('padding-left', (8 + depth * 12) + 'px')
              .attr('data-path', node.path);
          fileNode.append($j('<i>').addClass('fa fa-file-text-o'));
          fileNode.append($j('<span>').addClass('tree-label').text(node.name));
          container.append(fileNode);

          fileNode.on('click', function(e) {
            e.stopPropagation();
            treePanel.find('.browse-tree-node').removeClass('selected');
            fileNode.addClass('selected');
            // Show this single file directly in preview
            filesArea.empty();
            var fHeader = $j('<div>').addClass('browse-files-header');
            fHeader.append($j('<span>').text('/'));
            fHeader.append($j('<span>').addClass('file-count').text('1 file'));
            filesArea.append(fHeader);
            var row = $j('<div>').addClass('browse-file-row selected');
            row.append($j('<i>').addClass('fa ' + fileIcon(node.name)));
            row.append($j('<span>').addClass('file-name').text(node.name));
            if (node.size !== undefined) {
              row.append($j('<span>').addClass('file-size').text(human_filesize(node.size)));
            }
            filesArea.append(row);
            showPreview(node);
          });
        }
      })(nodes[i]);
    }
  }

  // Fetch tree data
  $j.getJSON(thisUrl + '?request=training&action=browse')
      .done(function(data) {
        var resp = data.response || data;
        treeData = resp.tree || [];

        if (resp.base) pathSpan.text(resp.base);
        treePanel.empty();

        if (treeData.length === 0) {
          treePanel.html('<div class="browse-empty-msg">' +
              (t.TrainingNoData || 'No training data yet.') + '</div>');
          filesArea.html('<div class="browse-empty-msg">' +
              (t.TrainingNoData || 'No training data yet.') + '</div>');
          return;
        }

        buildTreeNodes(treePanel, treeData, 0);

        // Auto-select images/all if it exists, otherwise first dir
        var autoSelect = 'images/all';
        var autoNode = findNode(treeData, autoSelect);
        if (!autoNode) {
          // Find first dir
          for (var i = 0; i < treeData.length; i++) {
            if (treeData[i].type === 'dir') {
              autoSelect = treeData[i].path;
              break;
            }
          }
        }

        // Expand parents and select
        var parts = autoSelect.split('/');
        var pathSoFar = '';
        for (var p = 0; p < parts.length; p++) {
          pathSoFar = pathSoFar ? pathSoFar + '/' + parts[p] : parts[p];
          var treeNodeEl = treePanel.find(
              '.browse-tree-node[data-path="' + pathSoFar + '"]');
          if (treeNodeEl.length) {
            treeNodeEl.find('.fa-folder').removeClass('fa-folder')
                .addClass('fa-folder-open');
            treeNodeEl.next('.browse-tree-children').show();
          }
        }
        var targetNode = treePanel.find(
            '.browse-tree-node[data-path="' + autoSelect + '"]');
        if (targetNode.length) {
          targetNode.addClass('selected');
        }
        showFiles(autoSelect);
      })
      .fail(function() {
        treePanel.empty();
        treePanel.html('<div class="browse-empty-msg" style="color:#dc3545">' +
            (t.TrainingSaveFailed || 'Failed to load') + '</div>');
      });
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
    this._setStatus(this.translations.TrainingNoFrameLoaded || 'No frame loaded', 'error');
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
    var msg = this.translations.TrainingBackgroundConfirm ||
        'No objects marked. Save as a background image (no objects)?\n\nBackground images help the model learn to reduce false positives.';
    if (!confirm(msg)) return;
  }

  this._setStatus(this.translations.TrainingSaving || 'Saving...', 'saving');

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
          self._setStatus(data.message || self.translations.TrainingSaveFailed || 'Save failed', 'error');
          return;
        }
        var resp = data.response || data;
        self.dirty = false;
        self._setStatus(
            self.translations.TrainingSaved || 'Training annotation saved',
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
        self._setStatus(self.translations.TrainingSaveFailed || 'Save failed', 'error');
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

  // Auto-clear all messages after a delay
  setTimeout(function() {
    el.text('').removeClass('error saving');
  }, 8000);
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
