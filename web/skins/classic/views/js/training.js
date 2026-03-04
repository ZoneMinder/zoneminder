"use strict";
/**
 * AnnotationEditor - Canvas-based bounding box annotation editor for
 * ZoneMinder custom model training. Allows users to view, create, edit,
 * and save object annotations on event frames in YOLO format.
 *
 * Usage:
 *   let editor = new AnnotationEditor({
 *     canvasId: 'annotationCanvas',
 *     eventId: 123,
 *     translations: { ... }
 *   });
 *   editor.init();
 *   editor.open();
 */

const ANNOTATION_COLORS = [
  '#e6194b', '#3cb44b', '#4363d8', '#f58231', '#911eb4',
  '#42d4f4', '#f032e6', '#bfef45', '#fabed4', '#469990',
  '#dcbeff', '#9A6324', '#800000', '#aaffc3', '#808000',
  '#000075', '#a9a9a9'
];

const ANNOTATION_HANDLE_SIZE = 8;
const ANNOTATION_HIT_THRESHOLD = 8;
const ANNOTATION_MIN_BOX_SIZE = 10;
const ANNOTATION_MAX_UNDO = 50;
const ANNOTATION_SCALE_REF_WIDTH = 900;

// --- Pure browse-panel utility functions (no closured state) ---

function browseFileIcon(name) {
  let ext = name.split('.').pop().toLowerCase();
  if (ext === 'jpg' || ext === 'jpeg' || ext === 'png') return 'fa-file-image-o';
  if (ext === 'txt') return 'fa-file-text-o';
  if (ext === 'yaml' || ext === 'yml') return 'fa-file-code-o';
  return 'fa-file-o';
}

function browseIsImage(name) {
  let ext = name.split('.').pop().toLowerCase();
  return ext === 'jpg' || ext === 'jpeg' || ext === 'png';
}

function browseIsText(name) {
  let ext = name.split('.').pop().toLowerCase();
  return ext === 'txt' || ext === 'yaml' || ext === 'yml';
}

function browsePathStartsWith(full, prefix) {
  return full === prefix || full.indexOf(prefix + '/') === 0;
}

function browseFindNode(nodes, path) {
  for (let i = 0; i < nodes.length; i++) {
    if (nodes[i].path === path) return nodes[i];
    if (nodes[i].type === 'dir' && nodes[i].children) {
      let found = browseFindNode(nodes[i].children, path);
      if (found) return found;
    }
  }
  return null;
}

function browseRemoveFromTree(nodes, path) {
  for (let i = 0; i < nodes.length; i++) {
    if (nodes[i].path === path) {
      nodes.splice(i, 1);
      return true;
    }
    if (nodes[i].type === 'dir' && nodes[i].children) {
      if (browseRemoveFromTree(nodes[i].children, path)) return true;
    }
  }
  return false;
}

function browseGetRootFiles(nodes) {
  let files = [];
  for (let i = 0; i < nodes.length; i++) {
    if (nodes[i].type === 'file') files.push(nodes[i]);
  }
  return files;
}

function browseGetFilesForPath(nodes, path) {
  if (path === null) return [];
  for (let i = 0; i < nodes.length; i++) {
    let node = nodes[i];
    if (node.type === 'dir' && node.path === path) {
      let files = [];
      if (node.children) {
        for (let j = 0; j < node.children.length; j++) {
          if (node.children[j].type === 'file') {
            files.push(node.children[j]);
          }
        }
      }
      return files;
    }
    if (node.type === 'dir' && node.children) {
      let found = browseGetFilesForPath(node.children, path);
      if (found.length > 0 || browsePathStartsWith(path, node.path)) return found;
    }
  }
  return [];
}

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

  const self = this;

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
AnnotationEditor.prototype.open = function(initialFrame) {
  const self = this;

  $j('#annotationPanel').addClass('open');
  $j('#eventVideo').hide();

  // Guard against navigating away with unsaved annotations.
  // We intercept link clicks directly instead of using beforeunload,
  // because ZM's global beforeunload handler (skin.js) hides
  // #content as a page-leave animation before the browser dialog
  // appears, causing a blank screen if the user cancels.
  this._navGuardHandler = function(e) {
    if (!self.dirty) return;
    let link = e.target.closest('a[href]');
    if (!link) return;
    let msg = self.translations.TrainingUnsaved ||
        'You have unsaved annotations. Discard changes?';
    if (!confirm(msg)) {
      e.preventDefault();
      e.stopPropagation();
    } else {
      self.dirty = false;
    }
  };
  document.addEventListener('click', this._navGuardHandler, true);

  // Load class labels, stats, and detection data in parallel
  this._loadLabels();
  this._loadStats();
  this._loadEventData(this.eventId, initialFrame);
};

/**
 * Close the annotation panel. Prompts if dirty.
 */
AnnotationEditor.prototype.close = function() {
  if (!this._confirmDiscardIfDirty()) return;

  $j('#annotationPanel').removeClass('open');
  $j('#eventVideo').show();
  this._hideLabelPicker();

  // Close inline browse panel if open
  if (this._browseInline) {
    this._browseInline.remove();
    this._browseInline = null;
  }

  // Remove page-leave guard
  if (this._navGuardHandler) {
    document.removeEventListener('click', this._navGuardHandler, true);
    this._navGuardHandler = null;
  }

  // Reset state
  this._resetAnnotationState();
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
  let objects = [];
  if (Array.isArray(data)) {
    objects = data;
  } else if (data.objects && Array.isArray(data.objects)) {
    objects = data.objects;
  } else if (data.detections && Array.isArray(data.detections)) {
    objects = data.detections;
  }

  for (let i = 0; i < objects.length; i++) {
    let obj = objects[i];
    let ann = {
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
  const self = this;
  this.currentFrameId = frameId;
  this._updateFrameInfo();

  // Blur the current canvas and show spinner while the new frame loads
  let container = this.canvas ? this.canvas.parentNode : null;
  if (this.canvas) {
    this.canvas.style.filter = 'blur(10px)';
    this.canvas.style.opacity = '0.6';
  }
  if (container && !container.querySelector('.canvas-loading-overlay')) {
    let overlay = document.createElement('div');
    overlay.className = 'canvas-loading-overlay';
    overlay.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    container.appendChild(overlay);
  }

  let img = new Image();
  img.crossOrigin = 'anonymous';
  img.onload = function() {
    self.image = img;
    self.imageNaturalW = img.naturalWidth;
    self.imageNaturalH = img.naturalHeight;

    // Set canvas resolution to match image
    self.canvas.width = img.naturalWidth;
    self.canvas.height = img.naturalHeight;

    self.canvas.style.filter = '';
    self.canvas.style.opacity = '';
    let spinner = container ? container.querySelector('.canvas-loading-overlay') : null;
    if (spinner) spinner.remove();
    self._render();
  };
  img.onerror = function() {
    if (self.canvas) {
      self.canvas.style.filter = '';
      self.canvas.style.opacity = '';
    }
    let spinner = container ? container.querySelector('.canvas-loading-overlay') : null;
    if (spinner) spinner.remove();
    // If numeric frame exceeds total, clamp to max and retry
    let num = parseInt(frameId, 10);
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
 * Load previously saved annotations from training data for the given
 * event+frame. Populates the annotations array and re-renders.
 */
AnnotationEditor.prototype._loadSavedAnnotations = function(eid, fid) {
  const self = this;
  $j.getJSON(thisUrl + '?request=training&action=load_saved&eid=' + eid +
      '&fid=' + encodeURIComponent(fid))
      .done(function(data) {
        let resp = data.response || data;
        let saved = resp.annotations || [];
        if (saved.length === 0) return;
        self.annotations = [];
        for (let i = 0; i < saved.length; i++) {
          self.annotations.push({
            x1: saved[i].x1,
            y1: saved[i].y1,
            x2: saved[i].x2,
            y2: saved[i].y2,
            label: saved[i].label,
            pending: false
          });
        }
        self.selectedIndex = -1;
        self.dirty = false;
        self._render();
        self._updateSidebar();
        self._setStatus(saved.length + ' ' +
            (self.translations.TrainingSavedLoaded || 'saved annotation(s) loaded'));
      })
      .fail(logAjaxFail);
};

/**
 * Update the "Frame: X of Y" indicator below the canvas.
 */
AnnotationEditor.prototype._updateFrameInfo = function() {
  let el = $j('#annotationFrameInfo');
  if (!el.length) return;

  let frameLabel = this.currentFrameId;
  let num = parseInt(frameLabel, 10);
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
  if (!this._confirmDiscardIfDirty()) return;

  this._resetAnnotationState();

  this._loadFrameImage(frameId);
  this._updateSidebar();
};

/**
 * Switch to a different event without a full page reload.
 * Reloads event metadata, frame list, and loads the given frame.
 */
AnnotationEditor.prototype.switchEvent = function(newEid, frameId) {
  if (!this._confirmDiscardIfDirty()) return;

  this.eventId = String(newEid);
  this._resetAnnotationState();

  // Update page title and URL without reload
  let titleEl = document.querySelector('.training-view-title');
  if (titleEl) titleEl.innerHTML = (this.translations.ObjectTraining || 'Object Training') +
      ' &mdash; Event ' + newEid;
  document.title = (this.translations.ObjectTraining || 'Object Training') +
      ' - ' + newEid;
  let backBtn = document.getElementById('backToEventBtn');
  if (backBtn) backBtn.href = '?view=event&eid=' + newEid;
  history.replaceState(null, '', '?view=training&eid=' + newEid +
      (frameId ? '&frame=' + encodeURIComponent(frameId) : ''));

  // Reload event data
  this._loadEventData(newEid, frameId);
};

/**
 * Render the entire canvas: image, all boxes, in-progress drawing.
 */
AnnotationEditor.prototype._render = function() {
  const ctx = this.ctx;
  const w = this.canvas.width;
  const h = this.canvas.height;

  ctx.clearRect(0, 0, w, h);

  // Draw the frame image
  if (this.image) {
    ctx.drawImage(this.image, 0, 0, w, h);
  }

  // Draw all annotation boxes
  for (let i = 0; i < this.annotations.length; i++) {
    this._drawBox(this.annotations[i], i === this.selectedIndex, i);
  }

  // Draw in-progress drawing rectangle
  if (this.isDrawing && this.drawStart && this.drawCurrent) {
    let x = Math.min(this.drawStart.x, this.drawCurrent.x);
    let y = Math.min(this.drawStart.y, this.drawCurrent.y);
    let bw = Math.abs(this.drawCurrent.x - this.drawStart.x);
    let bh = Math.abs(this.drawCurrent.y - this.drawStart.y);

    let drawScale = this.canvas.width / ANNOTATION_SCALE_REF_WIDTH;
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
  const ctx = this.ctx;
  let color;
  if (ann.pending) {
    color = '#ff8c00'; // orange for unconfirmed detections
  } else {
    color = '#28a745'; // green for accepted/manual annotations
  }

  const x = Math.min(ann.x1, ann.x2);
  const y = Math.min(ann.y1, ann.y2);
  const w = Math.abs(ann.x2 - ann.x1);
  const h = Math.abs(ann.y2 - ann.y1);

  // Scale sizes relative to canvas width so they're visible at any resolution
  const scale = this.canvas.width / ANNOTATION_SCALE_REF_WIDTH;
  const borderWidth = Math.max(2, Math.round((isSelected ? 4 : 3) * scale));
  const fontSize = Math.max(14, Math.round(18 * scale));
  const handleSize = Math.max(8, Math.round(10 * scale));
  const textPad = Math.round(6 * scale);

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
  let labelText = ann.label;
  if (ann.confidence > 0) {
    labelText += ' ' + Math.round(ann.confidence * 100) + '%';
  }

  ctx.save();
  ctx.font = 'bold ' + fontSize + 'px sans-serif';
  let textMetrics = ctx.measureText(labelText);
  let textW = textMetrics.width + textPad * 2;
  let textH = Math.round(fontSize * 1.5);
  let textX = x;
  let textY = y - textH;
  if (textY < 0) textY = y; // flip below if too close to top

  ctx.fillStyle = color;
  ctx.fillRect(textX, textY, textW, textH);

  ctx.fillStyle = '#ffffff';
  ctx.textBaseline = 'middle';
  ctx.fillText(labelText, textX + textPad, textY + textH / 2);
  ctx.restore();

  // Draw resize handles on selected box
  if (isSelected) {
    let handles = this._getHandlePositions(ann);
    let handleNames = Object.keys(handles);
    for (let i = 0; i < handleNames.length; i++) {
      let hp = handles[handleNames[i]];
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
  const x1 = Math.min(ann.x1, ann.x2);
  const y1 = Math.min(ann.y1, ann.y2);
  const x2 = Math.max(ann.x1, ann.x2);
  const y2 = Math.max(ann.y1, ann.y2);
  const mx = (x1 + x2) / 2;
  const my = (y1 + y2) / 2;

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
  const rect = this.canvas.getBoundingClientRect();
  const scaleX = this.canvas.width / rect.width;
  const scaleY = this.canvas.height / rect.height;
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

  const pos = this._mouseToImage(e);

  // Hold Shift to force draw mode (draw inside existing boxes)
  if (!e.shiftKey) {
    // Check resize handles on selected box first
    if (this.selectedIndex >= 0) {
      let handle = this._hitTestHandles(pos, this.annotations[this.selectedIndex]);
      if (handle) {
        this._pushUndo();
        this.isResizing = true;
        this.resizeHandle = handle;
        return;
      }
    }

    // Check if clicking on any box
    let hitIndex = this._hitTestBoxes(pos);
    if (hitIndex >= 0) {
      this._pushUndo();
      this.selectAnnotation(hitIndex);
      this.isDragging = true;
      let ann = this.annotations[hitIndex];
      this.dragOffset = {
        x: pos.x - Math.min(ann.x1, ann.x2),
        y: pos.y - Math.min(ann.y1, ann.y2)
      };
      return;
    }
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
  const pos = this._mouseToImage(e);

  if (this.isDrawing) {
    this.drawCurrent = pos;
    this._scheduleRender();
    return;
  }

  if (this.isDragging && this.selectedIndex >= 0) {
    let ann = this.annotations[this.selectedIndex];
    let w = Math.abs(ann.x2 - ann.x1);
    let h = Math.abs(ann.y2 - ann.y1);
    let newX = pos.x - this.dragOffset.x;
    let newY = pos.y - this.dragOffset.y;

    // Clamp to canvas bounds
    newX = Math.max(0, Math.min(newX, this.canvas.width - w));
    newY = Math.max(0, Math.min(newY, this.canvas.height - h));

    ann.x1 = newX;
    ann.y1 = newY;
    ann.x2 = newX + w;
    ann.y2 = newY + h;
    this.dirty = true;
    this._scheduleRender();
    return;
  }

  if (this.isResizing && this.selectedIndex >= 0) {
    this._doResize(pos);
    this.dirty = true;
    this._scheduleRender();
    return;
  }

  // Update cursor based on what's under the mouse
  this._updateCursor(pos, e);
};

/**
 * Throttle render calls via requestAnimationFrame to avoid
 * redundant repaints on every mousemove pixel.
 */
AnnotationEditor.prototype._scheduleRender = function() {
  if (this._rafPending) return;
  const self = this;
  this._rafPending = true;
  requestAnimationFrame(function() {
    self._render();
    self._rafPending = false;
  });
};

/**
 * Handle mouseup on canvas: finish drawing, dragging, or resizing.
 * @param {MouseEvent} e
 */
AnnotationEditor.prototype._onMouseUp = function(e) {
  if (e.button !== 0) return;

  if (this.isDrawing) {
    this.isDrawing = false;
    const pos = this._mouseToImage(e);
    const rect = this._getNormalizedRect(this.drawStart.x, this.drawStart.y, pos.x, pos.y);
    let x1 = rect.x1;
    let y1 = rect.y1;
    let x2 = rect.x2;
    let y2 = rect.y2;

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
      let ann = this.annotations[this.selectedIndex];
      let nr = this._getNormalizedRect(ann.x1, ann.y1, ann.x2, ann.y2);
      ann.x1 = nr.x1;
      ann.y1 = nr.y1;
      ann.x2 = nr.x2;
      ann.y2 = nr.y2;
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
  for (let i = this.annotations.length - 1; i >= 0; i--) {
    let ann = this.annotations[i];
    let x1 = Math.min(ann.x1, ann.x2);
    let y1 = Math.min(ann.y1, ann.y2);
    let x2 = Math.max(ann.x1, ann.x2);
    let y2 = Math.max(ann.y1, ann.y2);
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
  let handles = this._getHandlePositions(ann);
  let names = Object.keys(handles);
  for (let i = 0; i < names.length; i++) {
    let hp = handles[names[i]];
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
  let ann = this.annotations[this.selectedIndex];

  // Clamp position to canvas bounds
  let px = Math.max(0, Math.min(pos.x, this.canvas.width));
  let py = Math.max(0, Math.min(pos.y, this.canvas.height));

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
AnnotationEditor.prototype._updateCursor = function(pos, e) {
  let cursorClass = '';

  // Shift held = force draw mode cursor
  if (e && e.shiftKey) {
    this._setCursorClass('');
    return;
  }

  // Check handles on selected box
  if (this.selectedIndex >= 0) {
    let handle = this._hitTestHandles(pos, this.annotations[this.selectedIndex]);
    if (handle) {
      cursorClass = 'mode-resize-' + handle;
      this._setCursorClass(cursorClass);
      return;
    }
  }

  // Check if over any box
  let hitIndex = this._hitTestBoxes(pos);
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
  let canvas = $j(this.canvas);
  // Remove all mode-* classes
  let classes = (canvas.attr('class') || '').split(/\s+/);
  for (let i = 0; i < classes.length; i++) {
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
  const self = this;

  this._hideLabelPicker();

  let picker = document.createElement('div');
  picker.className = 'annotation-label-picker';

  // Position near the box's top-right corner in screen coords
  let rect = this.canvas.getBoundingClientRect();
  let scaleX = rect.width / this.canvas.width;
  let scaleY = rect.height / this.canvas.height;
  let screenX = rect.left + x2 * scaleX + 5;
  let screenY = rect.top + y1 * scaleY;

  // Keep within viewport
  let viewW = window.innerWidth;
  let viewH = window.innerHeight;
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
  let sortedLabels = this._getSortedLabels();

  for (let i = 0; i < sortedLabels.length; i++) {
    let btn = document.createElement('button');
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
  let inputDiv = document.createElement('div');
  inputDiv.className = 'new-label-input';
  let input = document.createElement('input');
  input.type = 'text';
  input.placeholder = this.translations.NewLabel || 'New Label';
  input.addEventListener('keydown', function(e) {
    e.stopPropagation();
    if (e.key === 'Enter') {
      let label = input.value.trim().toLowerCase();
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
  let labels = this.classLabels.slice();
  let recent = this.recentLabels;

  labels.sort(function(a, b) {
    let ia = recent.indexOf(a);
    let ib = recent.indexOf(b);
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
  let idx = this.recentLabels.indexOf(label);
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
  this._updateSidebar();
  this._render();

  // If no annotations left, remove frame from training set
  if (this.annotations.length === 0 && this.currentFrameId) {
    this.dirty = false;
    const self = this;
    $j.ajax({
      url: thisUrl + '?request=training&action=delete',
      method: 'POST',
      data: {eid: this.eventId, fid: this.currentFrameId},
      dataType: 'json'
    }).done(function(data) {
      let resp = data.response || data;
      self._setStatus(
          self.translations.TrainingRemoved || 'Training annotation removed',
          'success'
      );
      if (resp.stats) {
        self.trainingStats = resp.stats;
        self._renderStats();
      }
      if (self._browseState) {
        self._browseState.invalidateObjects();
        self._browseState.refreshTree();
      }
    }).fail(logAjaxFail);
  } else {
    this.dirty = true;
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
  let changed = false;
  for (let i = 0; i < this.annotations.length; i++) {
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
  const self = this;

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
      'info'
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
    let resp = data.response || data;
    let detections = resp.detections || [];

    if (detections.length === 0) {
      self._setStatus(
          self.translations.TrainingDetectNoResults || 'No objects detected'
      );
      return;
    }

    // Remove existing pending annotations (from a previous detect)
    self._pushUndo();
    let kept = [];
    for (let i = 0; i < self.annotations.length; i++) {
      if (!self.annotations[i].pending) {
        kept.push(self.annotations[i]);
      }
    }
    self.annotations = kept;

    // Add new detections as pending (orange)
    for (let d = 0; d < detections.length; d++) {
      let det = detections[d];
      let ann = {
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
  let copy = JSON.parse(JSON.stringify(this.annotations));
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
  let state = this.undoStack.pop();
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
  const self = this;
  const list = $j('#annotationObjectList');
  if (!list.length) return;

  list.empty();

  for (let i = 0; i < this.annotations.length; i++) {
    let ann = this.annotations[i];
    let isSelected = (i === this.selectedIndex);

    let li = $j('<li>')
        .addClass('annotation-object-item')
        .toggleClass('selected', isSelected)
        .attr('data-index', i);

    let swatchColor = ann.pending ? '#ff8c00' : '#28a745';
    let swatch = $j('<span>')
        .addClass('color-swatch')
        .css('background-color', swatchColor);

    let labelSpan = $j('<span>')
        .addClass('object-label')
        .text(ann.label);

    li.append(swatch).append(labelSpan);

    if (ann.confidence > 0) {
      let confSpan = $j('<span>')
          .addClass('object-confidence')
          .text(Math.round(ann.confidence * 100) + '%');
      li.append(confSpan);
    }

    if (ann.pending) {
      let acceptBtn = $j('<button>')
          .addClass('btn-action btn-accept')
          .attr('title', self.translations.AcceptDetection || 'Accept')
          .html('&#10003;')
          .attr('data-index', i);
      li.append(acceptBtn);
    }

    let removeBtn = $j('<button>')
        .addClass('btn-action btn-remove')
        .attr('title', self.translations.TrainingDeleteBox || 'Delete')
        .html('&times;')
        .attr('data-index', i);

    li.append(removeBtn);
    list.append(li);
  }

  // Bind click handlers
  list.find('.annotation-object-item').on('click', function(e) {
    if ($j(e.target).hasClass('btn-remove') || $j(e.target).hasClass('btn-accept')) return;
    let idx = parseInt($j(this).attr('data-index'), 10);
    self.selectAnnotation(idx);
  });

  list.find('.btn-accept').on('click', function(e) {
    e.stopPropagation();
    let idx = parseInt($j(this).attr('data-index'), 10);
    self.acceptAnnotation(idx);
  });

  list.find('.btn-remove').on('click', function(e) {
    e.stopPropagation();
    let idx = parseInt($j(this).attr('data-index'), 10);
    self.deleteAnnotation(idx);
  });
};

/**
 * Update the label dropdown select element with current labels.
 */
AnnotationEditor.prototype._updateLabelDropdown = function() {
  let select = $j('#annotationLabelSelect');
  if (!select.length) return;

  select.empty();
  for (let i = 0; i < this.classLabels.length; i++) {
    select.append(
        $j('<option>').val(this.classLabels[i]).text(this.classLabels[i])
    );
  }
};

/**
 * Load class labels from the server.
 */
AnnotationEditor.prototype._loadLabels = function() {
  const self = this;
  $j.getJSON(thisUrl + '?request=training&action=labels')
      .done(function(data) {
        let resp = data.response || data;
        self.classLabels = resp.labels || [];
        self._updateLabelDropdown();
      })
      .fail(logAjaxFail);
};

/**
 * Load training stats from the server.
 */
AnnotationEditor.prototype._loadStats = function() {
  const self = this;
  $j.getJSON(thisUrl + '?request=training&action=status')
      .done(function(data) {
        let resp = data.response || data;
        self.trainingStats = resp.stats || null;
        self._renderStats();
      })
      .fail(logAjaxFail);
};

/**
 * Build and render training stats HTML in the sidebar.
 */
AnnotationEditor.prototype._renderStats = function() {
  let container = $j('#annotationStats');
  if (!container.length) return;
  const self = this;

  container.empty();

  const t = this.translations;
  let stats = this.trainingStats || {total_images: 0, total_classes: 0, images_per_class: {}};
  let hasData = stats.total_images > 0 || stats.background_images > 0;

  // Header
  let header = $j('<div>').addClass('annotation-stats-header');
  header.append($j('<span>').text(t.TrainingDataStats || 'Training Data Statistics'));
  container.append(header);

  // Always show delete-all button so user can clean up from any state
  let sidebarTrashBtn = $j('#annotationDeleteAllBtn');
  sidebarTrashBtn.show().off('click.deleteAll').on('click.deleteAll', function() {
    self._deleteAllTrainingData();
  });

  // Compact stat rows
  let row = function(label, value) {
    return $j('<div>').addClass('stat-row')
        .append($j('<span>').addClass('stat-label').text(label))
        .append($j('<span>').addClass('stat-value').text(value));
  };

  container.append(row(t.TrainingTotalImages || 'Annotated images', stats.total_images));
  if (stats.background_images > 0) {
    container.append(row(t.TrainingBackgroundImages || 'Background images', stats.background_images));
  }

  // Per-class image counts
  let classData = stats.images_per_class || {};
  let classNames = Object.keys(classData);

  if (classNames.length > 0) {
    container.append($j('<div>').css({'font-weight': '600', 'margin': '4px 0 2px'}).text(t.ImagesPerClass || 'Images per class'));

    let hasLowClass = false;
    for (let i = 0; i < classNames.length; i++) {
      container.append(row(classNames[i], classData[classNames[i]]));
      if (classData[classNames[i]] < 50) hasLowClass = true;
    }

    let guidance = $j('<div>').addClass('training-guidance');
    if (!hasLowClass) guidance.addClass('training-ready');
    guidance.text(t.TrainingGuidance || 'Aim for 50-100+ images per class.');
    container.append(guidance);
  } else {
    container.append($j('<div>').css({'color': '#6c757d', 'padding': '4px 0'}).text(t.TrainingNoData || 'No training data yet.'));
  }
};

AnnotationEditor.prototype._deleteAllTrainingData = function() {
  const self = this;
  const t = this.translations;
  let answer = prompt(t.ConfirmDeleteTrainingData || 'This will permanently delete ALL training data. Type "agree" to confirm:');
  if (answer !== 'agree') return;

  $j.ajax({url: thisUrl + '?request=training&action=delete_all', method: 'POST', dataType: 'json'})
      .done(function(resp) {
        if (resp.result === 'Ok') {
          self.annotations = [];
          self.selectedIndex = -1;
          self.dirty = false;
          self.undoStack = [];
          self._updateSidebar();
          self._render();
          self.trainingStats = resp.stats || {};
          self.classLabels = (resp.stats && resp.stats.class_labels) ? resp.stats.class_labels : [];
          self._renderStats();
          self._setStatus(t.TrainingDataDeleted || 'All training data deleted.');
          // Sync: refresh browse panel if open
          if (self._browseState) {
            self._browseState.invalidateObjects();
            self._browseState.refreshTree();
          }
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
  const self = this;
  let total = this.totalFrames;
  if (!total || total <= 0) {
    this._setStatus(this.translations.TrainingLoadFrameFirst || 'Load a frame first', 'error');
    return;
  }

  // Remove any existing overlay
  $j('#frameBrowseOverlay').remove();

  let thumbWidth = 160;
  let perPage = 50;
  let totalPages = Math.ceil(total / perPage);
  // Start on the page containing the current frame
  let curNum = parseInt(this.currentFrameId, 10);
  let currentPage = (!isNaN(curNum) && curNum > 0)
    ? Math.ceil(curNum / perPage) : 1;

  let overlay = $j('<div id="frameBrowseOverlay" class="frame-browse-overlay">');
  let panel = $j('<div class="frame-browse-panel">');

  // Header
  let header = $j('<div class="frame-browse-header">');
  header.append($j('<span>').text(
    (this.translations.TrainingBrowseFrames || 'Browse Frames') +
    ' (' + total + ')'));
  let closeBtn = $j('<button class="frame-browse-close">&times;</button>');
  closeBtn.on('click', function() { cleanup(); });
  header.append(closeBtn);
  panel.append(header);

  // Grid container
  let grid = $j('<div class="frame-browse-grid">');

  // Pagination container
  let paginationWrap = $j('<nav class="frame-browse-pagination">');
  let paginationUl = $j('<ul class="pagination pagination-sm justify-content-center mb-0">');
  paginationWrap.append(paginationUl);

  function renderPage(page) {
    currentPage = page;
    grid.empty();

    let start = (page - 1) * perPage + 1;
    let end = Math.min(page * perPage, total);

    for (let i = start; i <= end; i++) {
      let fid = i;
      let cell = $j('<div class="frame-browse-cell">');
      let img = $j('<img>')
          .attr('loading', 'lazy')
          .attr('src', thisUrl + '?view=image&eid=' + self.eventId +
            '&fid=' + fid + '&width=' + thumbWidth)
          .attr('alt', 'Frame ' + fid);
      let label = $j('<div class="frame-browse-label">').text(fid);

      if (String(fid) === String(self.currentFrameId)) {
        cell.addClass('active');
      }

      cell.on('click', function() {
        cleanup();
        self.switchFrame(String(fid));
      });

      cell.append(img).append(label);
      grid.append(cell);
    }

    // Scroll grid to top
    grid.scrollTop(0);

    // Rebuild pagination
    renderPagination(page);
  }

  function renderPagination(page) {
    paginationUl.empty();

    // Prev
    let prevLi = $j('<li class="page-item">').toggleClass('disabled', page <= 1);
    prevLi.append($j('<a class="page-link" href="#">&laquo;</a>')
        .on('click', function(e) {
          e.preventDefault();
          if (page > 1) renderPage(page - 1);
        }));
    paginationUl.append(prevLi);

    // Determine which page numbers to show
    let pages = buildPageNumbers(page, totalPages);
    for (let p = 0; p < pages.length; p++) {
      let val = pages[p];
      if (val === '...') {
        paginationUl.append(
          $j('<li class="page-item disabled">')
              .append($j('<span class="page-link">').text('...'))
        );
      } else {
        let num = val;
        let li = $j('<li class="page-item">').toggleClass('active', num === page);
        li.append($j('<a class="page-link" href="#">').text(num)
            .on('click', function(e) {
              e.preventDefault();
              renderPage(num);
            }));
        paginationUl.append(li);
      }
    }

    // Next
    let nextLi = $j('<li class="page-item">')
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
      let all = [];
      for (let i = 1; i <= last; i++) all.push(i);
      return all;
    }
    let pages = [];
    pages.push(1);
    let rangeStart = Math.max(2, current - 1);
    let rangeEnd = Math.min(last - 1, current + 1);
    if (rangeStart > 2) pages.push('...');
    for (let i = rangeStart; i <= rangeEnd; i++) pages.push(i);
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
  const self = this;
  const t = this.translations;

  // Toggle: if inline panel exists, toggle visibility
  if (this._browseInline) {
    if (this._browseInline.is(':visible')) {
      this._browseInline.hide();
    } else {
      this._browseInline.show();
    }
    return;
  }

  // Build inline browse panel (first time)
  let panel = $j('<div>').addClass('training-browse-inline');

  // Header
  let header = $j('<div>').addClass('browse-header');
  header.append($j('<span>').text(t.TrainingBrowse || 'Browse Training Data'));
  let browseChanged = false;

  let closeBrowse = function() {
    panel.hide();
    if (browseChanged) {
      objectsData = null;
      backgroundsData = null;
      $j.getJSON(thisUrl + '?request=training&action=status').done(function(data) {
        let resp = data.response || data;
        if (resp.stats) {
          self.trainingStats = resp.stats;
          if (resp.stats.class_labels) self.classLabels = resp.stats.class_labels;
          self._renderStats();
          self._updateSidebar();
        }
      }).fail(logAjaxFail);
      browseChanged = false;
    }
  };

  let closeBtn = $j('<button>').addClass('browse-close').html('&times;');
  closeBtn.on('click', closeBrowse);
  header.append(closeBtn);
  panel.append(header);

  // Body: tree + files stacked vertically in inline panel
  let body = $j('<div>').addClass('browse-body');
  let treePanel = $j('<div>').addClass('browse-tree');
  let rightPanel = $j('<div>').addClass('browse-right');
  let filesArea = $j('<div>').addClass('browse-files');
  let previewArea = $j('<div>').addClass('browse-preview').hide();

  rightPanel.append(filesArea);
  rightPanel.append(previewArea);
  body.append(treePanel);
  body.append(rightPanel);
  panel.append(body);

  // Loading state
  treePanel.html('<div class="browse-empty-msg">' + (t.TrainingLoading || 'Loading...') + '</div>');

  // Insert as first child of annotation workspace
  $j('.annotation-workspace').prepend(panel);
  this._browseInline = panel;

  // State
  let selectedDirPath = null;
  let selectedFileName = null;
  let treeData = null;

  // Expose browse state on editor so save/delete can trigger cross-panel sync
  let browseState = {
    getSelectedDirPath: function() { return selectedDirPath; },
    refreshFiles: function() { if (selectedDirPath !== null) showFiles(selectedDirPath); },
    invalidateObjects: function() { objectsData = null; backgroundsData = null; browseChanged = true; },
    refreshTree: function() {
      $j.getJSON(thisUrl + '?request=training&action=browse')
          .done(function(data) {
            let resp = data.response || data;
            treeData = resp.tree || [];
            treePanel.empty();
            buildTreeNodes(treePanel, treeData, 0);
            injectObjectsFolder(treePanel);
            if (selectedDirPath !== null) showFiles(selectedDirPath);
          })
          .fail(logAjaxFail);
    }
  };
  self._browseState = browseState;

  // Show files in the right panel for a given directory path
  function showFiles(dirPath) {
    selectedDirPath = dirPath;
    selectedFileName = null;
    previewArea.hide();
    filesArea.empty();

    let files;
    if (dirPath === '') {
      // Root: show root-level files
      files = browseGetRootFiles(treeData);
    } else {
      files = browseGetFilesForPath(treeData, dirPath);
    }

    // Files header
    let fHeader = $j('<div>').addClass('browse-files-header');
    fHeader.append($j('<span>').text(dirPath || '/'));
    fHeader.append($j('<span>').addClass('file-count').text(files.length + ' files'));
    filesArea.append(fHeader);

    if (files.length === 0) {
      filesArea.append($j('<div>').addClass('browse-empty-msg').text(t.TrainingNoFiles || 'No files'));
      return;
    }

    for (let i = 0; i < files.length; i++) {
      let file = files[i];
      let row = $j('<div>').addClass('browse-file-row');
      row.append($j('<span>').addClass('file-name').text(file.name));
      if (file.size !== undefined) {
        row.append($j('<span>').addClass('file-size').text(human_filesize(file.size)));
      }

      // Delete button for image/label files
      let dirBase = (file.path || '').split('/')[0];
      if (dirBase === 'images' || dirBase === 'labels') {
        let delBtn = $j('<button>').addClass('browse-file-delete')
            .attr('title', 'Delete image + label pair')
            .html('<i class="fa fa-trash"></i>');
        delBtn.on('click', function(e) {
          e.stopPropagation();
          if (!confirm(t.TrainingConfirmDeleteFile || 'Delete this file and its paired image/label?')) return;
          delBtn.prop('disabled', true);
          $j.ajax({url: thisUrl + '?request=training&action=browse_delete&path=' +
              encodeURIComponent(file.path), method: 'POST', dataType: 'json'})
              .done(function(data) {
                let resp = data.response || data;
                browseChanged = true;
                // Remove deleted files from treeData
                if (resp.deleted) {
                  for (let d = 0; d < resp.deleted.length; d++) {
                    browseRemoveFromTree(treeData, resp.deleted[d]);
                  }
                }
                // Close preview if showing this file
                if (selectedFileName === file.name) {
                  previewArea.hide();
                  selectedFileName = null;
                }
                // Sync: if deleted file matches current editor frame, clear annotations
                let delStem = file.name.replace(/\.[^.]+$/, '');
                let curStem = 'event_' + self.eventId + '_frame_' +
                    (self.currentFrameId || '');
                if (delStem === curStem) {
                  self.annotations = [];
                  self.selectedIndex = -1;
                  self.dirty = false;
                  self._updateSidebar();
                  self._render();
                }
                // Always refresh stats after delete
                self._loadStats();
                // Invalidate objects cache
                objectsData = null;
                backgroundsData = null;
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
    }
  }

  // Show file preview
  function showPreview(file) {
    selectedFileName = file.name;
    previewArea.empty().show();

    let pvHeader = $j('<div>').addClass('browse-preview-header');
    pvHeader.append($j('<span>').text(file.name));

    let pvActions = $j('<span>').addClass('browse-preview-actions');

    // Edit button: parse event_{eid}_frame_{fid} from filename to navigate
    let stem = file.name.replace(/\.[^.]+$/, '');
    let match = stem.match(/^event_(\d+)_frame_(.+)$/);
    if (match) {
      let editBtn = $j('<button>')
          .addClass('browse-edit-btn')
          .attr('title', t.TrainingEditAnnotation || 'Edit annotation')
          .html('<i class="fa fa-pencil"></i>');
      editBtn.on('click', function() {
        let eid = match[1];
        let fid = match[2];
        self.switchEvent(eid, fid);
      });
      pvActions.append(editBtn);
    }

    let pvClose = $j('<button>').addClass('preview-close').html('&times;');
    pvClose.on('click', function() {
      previewArea.hide();
      filesArea.find('.browse-file-row').removeClass('selected');
      selectedFileName = null;
    });
    pvActions.append(pvClose);
    pvHeader.append(pvActions);
    previewArea.append(pvHeader);

    let pvContent = $j('<div>').addClass('browse-preview-content');
    previewArea.append(pvContent);

    let fileUrl = thisUrl + '?request=training&action=browse_file&path=' +
        encodeURIComponent(file.path);

    if (browseIsImage(file.name)) {
      let container = $j('<div>').addClass('browse-preview-img-wrap');
      let img = $j('<img>').attr('src', fileUrl)
          .attr('alt', file.name)
          .on('error', function() {
            pvContent.html('<em>' + (t.TrainingFailedToLoadFrame || 'Failed to load image') + '</em>');
          });
      let canvas = $j('<canvas>').addClass('browse-preview-canvas')[0];
      container.append(img);
      container.append(canvas);
      pvContent.append(container);

      // Once image loads, fetch label file and draw boxes
      img.on('load', function() {
        let natW = img[0].naturalWidth;
        let natH = img[0].naturalHeight;
        let dispW = img[0].clientWidth;
        let dispH = img[0].clientHeight;
        canvas.width = dispW;
        canvas.height = dispH;
        $j(canvas).css({width: dispW + 'px', height: dispH + 'px'});

        // Derive label file path from image path
        let stem = file.name.replace(/\.[^.]+$/, '');
        let lblPath = file.path.replace(/^images\//, 'labels/')
            .replace(/\.[^.]+$/, '.txt');
        let lblUrl = thisUrl + '?request=training&action=browse_file&path=' +
            encodeURIComponent(lblPath);

        // Fetch class labels and label file in parallel
        $j.when(
            $j.getJSON(thisUrl + '?request=training&action=labels'),
            $j.getJSON(lblUrl)
        ).done(function(labelsResp, lblResp) {
          let classLabels = (labelsResp[0].response || labelsResp[0]).labels || [];
          let content = (lblResp[0].response || lblResp[0]).content || '';
          let lines = content.split('\n').filter(function(l) {
            return l.trim().length > 0;
          });
          if (lines.length === 0) return;

          let ctx = canvas.getContext('2d');
          for (let li = 0; li < lines.length; li++) {
            let parts = lines[li].trim().split(/\s+/);
            if (parts.length < 5) continue;
            let classId = parseInt(parts[0], 10);
            let cx = parseFloat(parts[1]);
            let cy = parseFloat(parts[2]);
            let bw = parseFloat(parts[3]);
            let bh = parseFloat(parts[4]);

            // Convert YOLO normalized to display pixels
            let x = (cx - bw / 2) * dispW;
            let y = (cy - bh / 2) * dispH;
            let w = bw * dispW;
            let h = bh * dispH;

            let color = ANNOTATION_COLORS[classId % ANNOTATION_COLORS.length];
            let label = classLabels[classId] || ('class ' + classId);

            // Draw box
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.strokeRect(x, y, w, h);

            // Draw label background
            ctx.font = '11px sans-serif';
            let textW = ctx.measureText(label).width + 6;
            let textH = 16;
            let labelY = y - textH;
            if (labelY < 0) labelY = y;
            ctx.fillStyle = color;
            ctx.fillRect(x, labelY, textW, textH);

            // Draw label text
            ctx.fillStyle = '#fff';
            ctx.fillText(label, x + 3, labelY + 12);
          }
        });
      });
    } else if (browseIsText(file.name)) {
      pvContent.html('<em>' + (t.TrainingLoading || 'Loading...') + '</em>');
      $j.getJSON(fileUrl).done(function(data) {
        let resp = data.response || data;
        pvContent.empty();
        pvContent.append($j('<pre>').text(resp.content || '(empty)'));
      }).fail(function() {
        pvContent.html('<em>' + (t.TrainingSaveFailed || 'Failed to load file') + '</em>');
      });
    } else {
      pvContent.html('<em>' + (t.TrainingPreviewUnavailable || 'Preview not available for this file type') + '</em>');
    }
  }

  // --- Virtual "Objects" folder ---
  let objectsData = null; // cached browse_objects response
  let backgroundsData = null; // cached background images

  function injectObjectsFolder(container) {
    let objectsNode = $j('<div>').addClass('browse-tree-node')
        .css('padding-left', '8px')
        .attr('data-path', '__objects__');
    let icon = $j('<i>').addClass('fa fa-tags');
    objectsNode.append(icon);
    objectsNode.append($j('<span>').addClass('tree-label')
        .text(t.TrainingObjects || 'Objects'));

    let childContainer = $j('<div>').addClass('browse-tree-children').hide();

    // Insert at the top of the tree panel
    container.prepend(childContainer);
    container.prepend(objectsNode);

    objectsNode.on('click', function(e) {
      e.stopPropagation();
      treePanel.find('.browse-tree-node').removeClass('selected');
      objectsNode.addClass('selected');

      if (icon.hasClass('fa-tags-open')) {
        // Collapse
        icon.removeClass('fa-tags-open');
        childContainer.hide();
        return;
      }

      // Expand
      icon.addClass('fa-tags-open');

      // Fetch if not cached or data changed
      if (objectsData && !browseChanged) {
        renderObjectsChildren(childContainer, objectsData, backgroundsData);
        childContainer.show();
        showObjectsSummary(objectsData, backgroundsData);
        return;
      }

      childContainer.html('<div style="padding:4px 8px;color:#6c757d;font-size:0.75rem">' +
          (t.TrainingLoading || 'Loading...') + '</div>').show();

      $j.getJSON(thisUrl + '?request=training&action=browse_objects')
          .done(function(data) {
            let resp = data.response || data;
            objectsData = resp.objects || {};
            backgroundsData = resp.backgrounds || [];
            browseChanged = false;
            renderObjectsChildren(childContainer, objectsData, backgroundsData);
            showObjectsSummary(objectsData, backgroundsData);
          })
          .fail(function() {
            childContainer.html('<div style="padding:4px 8px;color:#dc3545;font-size:0.75rem">' +
                (t.TrainingSaveFailed || 'Failed to load') + '</div>');
          });
    });
  }

  function renderObjectsChildren(container, objects, backgrounds) {
    container.empty();
    let classNames = Object.keys(objects);
    let hasBackgrounds = backgrounds && backgrounds.length > 0;
    if (classNames.length === 0 && !hasBackgrounds) {
      container.html('<div style="padding:4px 20px;color:#6c757d;font-size:0.75rem">' +
          (t.TrainingNoObjects || 'No annotated objects yet') + '</div>');
      return;
    }
    for (let ci = 0; ci < classNames.length; ci++) {
      let className = classNames[ci];
      let items = objects[className];
      let classNode = $j('<div>').addClass('browse-tree-node')
          .css('padding-left', '20px');
      classNode.append($j('<i>').addClass('fa fa-tag'));
      classNode.append($j('<span>').addClass('tree-label').text(className));
      classNode.append($j('<span>').addClass('class-count-badge')
          .text('(' + items.length + ')'));
      container.append(classNode);

      classNode.on('click', function(e) {
        e.stopPropagation();
        treePanel.find('.browse-tree-node').removeClass('selected');
        classNode.addClass('selected');
        showObjectThumbnails(className, items);
      });
    }

    // Background images node
    if (hasBackgrounds) {
      let bgNode = $j('<div>').addClass('browse-tree-node')
          .css('padding-left', '20px');
      bgNode.append($j('<i>').addClass('fa fa-ban'));
      bgNode.append($j('<span>').addClass('tree-label')
          .text(t.TrainingBackgroundImages || 'Background images'));
      bgNode.append($j('<span>').addClass('class-count-badge')
          .text('(' + backgrounds.length + ')'));
      container.append(bgNode);

      bgNode.on('click', function(e) {
        e.stopPropagation();
        treePanel.find('.browse-tree-node').removeClass('selected');
        bgNode.addClass('selected');
        showObjectThumbnails(
            t.TrainingBackgroundImages || 'Background images', backgrounds);
      });
    }
  }

  function showObjectsSummary(objects, backgrounds) {
    // Show a summary in the right panel when the top-level Objects node is clicked
    filesArea.empty();
    previewArea.hide();
    let classNames = Object.keys(objects);
    let totalImages = 0;
    for (let i = 0; i < classNames.length; i++) {
      totalImages += objects[classNames[i]].length;
    }
    let bgCount = backgrounds ? backgrounds.length : 0;
    let summary = classNames.length + ' classes, ' + totalImages + ' images';
    if (bgCount > 0) {
      summary += ', ' + bgCount + ' background';
    }
    let fHeader = $j('<div>').addClass('browse-files-header');
    fHeader.append($j('<span>').text(t.TrainingObjects || 'Objects'));
    fHeader.append($j('<span>').addClass('file-count').text(summary));
    filesArea.append(fHeader);

    if (classNames.length === 0 && bgCount === 0) {
      filesArea.append($j('<div>').addClass('browse-empty-msg')
          .text(t.TrainingNoObjects || 'No annotated objects yet'));
    }
  }

  function showObjectThumbnails(className, items) {
    selectedDirPath = null;
    selectedFileName = null;
    previewArea.hide();
    filesArea.empty();

    let fHeader = $j('<div>').addClass('browse-files-header');
    fHeader.append($j('<span>').text(className));
    fHeader.append($j('<span>').addClass('file-count')
        .text(items.length + ' images'));
    filesArea.append(fHeader);

    if (items.length === 0) {
      filesArea.append($j('<div>').addClass('browse-empty-msg')
          .text(t.TrainingNoObjects || 'No annotated objects yet'));
      return;
    }

    let grid = $j('<div>').addClass('browse-thumb-grid');

    for (let i = 0; i < items.length; i++) {
      let item = items[i];
      let cell = $j('<div>').addClass('browse-thumb-cell');
      let imgUrl = thisUrl + '?request=training&action=browse_file&path=' +
          encodeURIComponent(item.imgPath);
      let img = $j('<img>').attr('src', imgUrl).attr('alt', item.stem);
      cell.append(img);

      // Label: extract event/frame from stem
      let label = item.stem;
      let match = item.stem.match(/^event_(\d+)_frame_(.+)$/);
      if (match) {
        label = 'E' + match[1] + ' / ' + match[2];
      }
      cell.append($j('<div>').addClass('browse-thumb-label').text(label));

      cell.on('click', function() {
        if (match) {
          self.switchEvent(match[1], match[2]);
        }
      });

      grid.append(cell);
    }

    filesArea.append(grid);
  }

  // Build tree nodes recursively in the left panel
  function buildTreeNodes(container, nodes, depth) {
    for (let i = 0; i < nodes.length; i++) {
      let node = nodes[i];
      if (node.type === 'dir') {
        let treeNode = $j('<div>').addClass('browse-tree-node')
            .css('padding-left', (8 + depth * 12) + 'px')
            .attr('data-path', node.path);
        let icon = $j('<i>').addClass('fa fa-folder');
        treeNode.append(icon);
        treeNode.append($j('<span>').addClass('tree-label').text(node.name));
        container.append(treeNode);

        let childContainer = $j('<div>').addClass('browse-tree-children');
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
        let fileNode = $j('<div>').addClass('browse-tree-node')
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
          let fHeader = $j('<div>').addClass('browse-files-header');
          fHeader.append($j('<span>').text('/'));
          fHeader.append($j('<span>').addClass('file-count').text('1 file'));
          filesArea.append(fHeader);
          let row = $j('<div>').addClass('browse-file-row selected');
          row.append($j('<i>').addClass('fa ' + browseFileIcon(node.name)));
          row.append($j('<span>').addClass('file-name').text(node.name));
          if (node.size !== undefined) {
            row.append($j('<span>').addClass('file-size').text(human_filesize(node.size)));
          }
          filesArea.append(row);
          showPreview(node);
        });
      }
    }
  }

  // Fetch tree data
  $j.getJSON(thisUrl + '?request=training&action=browse')
      .done(function(data) {
        let resp = data.response || data;
        treeData = resp.tree || [];

        treePanel.empty();

        if (treeData.length === 0) {
          treePanel.html('<div class="browse-empty-msg">' +
              (t.TrainingNoData || 'No training data yet.') + '</div>');
          filesArea.html('<div class="browse-empty-msg">' +
              (t.TrainingNoData || 'No training data yet.') + '</div>');
          return;
        }

        buildTreeNodes(treePanel, treeData, 0);

        // Inject virtual "Objects" folder at the top of the tree
        injectObjectsFolder(treePanel);

        // Auto-select images/all if it exists, otherwise first dir
        let autoSelect = 'images/all';
        let autoNode = browseFindNode(treeData, autoSelect);
        if (!autoNode) {
          // Find first dir
          for (let i = 0; i < treeData.length; i++) {
            if (treeData[i].type === 'dir') {
              autoSelect = treeData[i].path;
              break;
            }
          }
        }

        // Expand parents and select
        let parts = autoSelect.split('/');
        let pathSoFar = '';
        for (let p = 0; p < parts.length; p++) {
          pathSoFar = pathSoFar ? pathSoFar + '/' + parts[p] : parts[p];
          let treeNodeEl = treePanel.find(
              '.browse-tree-node[data-path="' + pathSoFar + '"]');
          if (treeNodeEl.length) {
            treeNodeEl.find('.fa-folder').removeClass('fa-folder')
                .addClass('fa-folder-open');
            treeNodeEl.next('.browse-tree-children').show();
          }
        }
        let targetNode = treePanel.find(
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
  const self = this;
  let container = $j('#annotationFrameSelector');
  if (!container.length) return;

  // Show/hide frame buttons based on availability
  container.find('.frame-btn').each(function() {
    let frameId = $j(this).attr('data-frame');
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
    let frameId = $j(this).attr('data-frame');
    self.switchFrame(frameId);

    // Update highlight
    container.find('.frame-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
    $j(this).removeClass('btn-outline-secondary').addClass('btn-primary');
  });

  // Frame number input for going to specific frame
  let frameInput = container.find('.frame-input');
  frameInput.off('keydown.annEditor').on('keydown.annEditor', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      let val = parseInt($j(this).val(), 10);
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
  const self = this;

  if (!this.currentFrameId) {
    this._setStatus(this.translations.TrainingNoFrameLoaded || 'No frame loaded', 'error');
    return;
  }

  // Only save accepted (non-pending) annotations
  let accepted = [];
  let pendingCount = 0;
  for (let i = 0; i < this.annotations.length; i++) {
    if (this.annotations[i].pending) {
      pendingCount++;
    } else {
      accepted.push(this.annotations[i]);
    }
  }

  if (pendingCount > 0 && accepted.length === 0) {
    // All boxes are unconfirmed — user probably forgot to accept them
    let msg = (this.translations.TrainingPendingOnly ||
        'You have %1 unaccepted detection(s) (orange boxes). Accept them first, or save as a background image with no objects?')
        .replace('%1', pendingCount);
    if (!confirm(msg)) return;
  } else if (pendingCount > 0) {
    // Mix of accepted and unconfirmed boxes
    let msg = (this.translations.TrainingPendingDiscard ||
        '%1 unaccepted detection(s) (orange boxes) will not be saved. Continue?')
        .replace('%1', pendingCount);
    if (!confirm(msg)) return;
  } else if (accepted.length === 0) {
    let msg = this.translations.TrainingBackgroundConfirm ||
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
        let resp = data.response || data;
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

        // Sync: refresh browse panel if open
        if (self._browseState) {
          self._browseState.invalidateObjects();
          self._browseState.refreshTree();
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
  const el = $j('#annotationStatus');
  if (!el.length) return;

  el.text(msg)
      .removeClass('error saving info success')
      .addClass(type || '');

  // Clear any previous auto-clear timer to avoid race conditions
  if (this._statusTimer) clearTimeout(this._statusTimer);
  this._statusTimer = setTimeout(function() {
    el.text('').removeClass('error saving info success');
  }, 8000);
};

/**
 * Prompt the user to discard unsaved changes if dirty.
 * @return {boolean} true if clean or user confirmed discard, false if cancelled
 */
AnnotationEditor.prototype._confirmDiscardIfDirty = function() {
  if (!this.dirty) return true;
  let msg = this.translations.TrainingUnsaved ||
      'You have unsaved annotations. Discard changes?';
  if (!confirm(msg)) return false;
  this.dirty = false;
  return true;
};

/**
 * Reset annotation editing state (annotations, selection, undo, drawing flags).
 */
AnnotationEditor.prototype._resetAnnotationState = function() {
  this.annotations = [];
  this.selectedIndex = -1;
  this.dirty = false;
  this.isDrawing = false;
  this.isDragging = false;
  this.isResizing = false;
  this.undoStack = [];
  this._hideLabelPicker();
};

/**
 * Apply event metadata from a load response to the editor instance.
 * Shared by open() and switchEvent().
 * @param {Object} resp  The response payload from the load action
 */
AnnotationEditor.prototype._applyEventData = function(resp) {
  this.availableFrames = resp.availableFrames || [];
  this.totalFrames = resp.totalFrames || 0;
  this.eventPath = resp.eventPath || '';
  this.imageNaturalW = resp.width || 0;
  this.imageNaturalH = resp.height || 0;
  this.monitorId = resp.monitorId || '';
  this.hasDetectScript = resp.hasDetectScript || false;

  if (this.hasDetectScript) {
    $j('#annotationDetectBtn').show();
  } else {
    $j('#annotationDetectBtn').hide();
  }

  this._updateFrameSelector(this.availableFrames);

  if (resp.detectionData) {
    this._loadDetectionData(resp.detectionData);
  }
};

/**
 * Load event metadata and optionally a preferred frame.
 * Shared by open() and switchEvent() to avoid duplicating the AJAX call.
 * @param {string|number} eid  Event ID to load
 * @param {string} [preferredFrame]  Frame to load; falls back to defaultFrameId
 */
AnnotationEditor.prototype._loadEventData = function(eid, preferredFrame) {
  const self = this;
  $j.getJSON(thisUrl + '?request=training&action=load&eid=' + eid)
      .done(function(data) {
        if (data.result === 'Error') {
          self._setStatus(data.message, 'error');
          return;
        }
        let resp = data.response || data;
        self._applyEventData(resp);

        let startFrame = preferredFrame || resp.defaultFrameId || 'alarm';
        self._loadFrameImage(startFrame);

        if (preferredFrame) {
          self._loadSavedAnnotations(self.eventId, preferredFrame);
        }
      })
      .fail(function(jqxhr) {
        self._setStatus(self.translations.TrainingFailedToLoadEvent || 'Failed to load event data', 'error');
        logAjaxFail(jqxhr);
      });
};

/**
 * Normalize a rectangle so x1 <= x2, y1 <= y2.
 * @param {number} x1
 * @param {number} y1
 * @param {number} x2
 * @param {number} y2
 * @return {{x1: number, y1: number, x2: number, y2: number}}
 */
AnnotationEditor.prototype._getNormalizedRect = function(x1, y1, x2, y2) {
  return {
    x1: Math.min(x1, x2),
    y1: Math.min(y1, y2),
    x2: Math.max(x1, x2),
    y2: Math.max(y1, y2)
  };
};

