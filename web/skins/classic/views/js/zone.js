var pauseBtn = $j('#pauseBtn');
var playBtn = $j('#playBtn');
var saveBtn = $j('#saveBtn');
var cancelBtn = $j('#cancelBtn');
var resetBtn = $j('#resetBtn');
var undoBtn = $j('#undoBtn');
var undoStack = [];
var maxUndoStack = 50;
var undoInput = null; // the point input whose edit already has an undo step
var initialState = null; // normalised snapshot of form values taken on load, for change detection
var initialValues = null; // raw form values as loaded, for restoring on reset
var initialPoints = null; // snapshot of zone['Points'] taken on load
var backBtn = $j('#backBtn');
var refreshBtn = $j('#refreshBtn');
var analyseBtn = $j('#analyseBtn');
var monitors = [];
var analyse_frames = true;
var TimerHideShow;

function segmentsIntersect(a, b, c, d) {
  const cross = (p, q, r) => (q.x-p.x)*(r.y-p.y) - (q.y-p.y)*(r.x-p.x);
  const within = (p, q, r) => Math.min(p.x, q.x) <= r.x && r.x <= Math.max(p.x, q.x) &&
    Math.min(p.y, q.y) <= r.y && r.y <= Math.max(p.y, q.y);
  const d1 = cross(c, d, a);
  const d2 = cross(c, d, b);
  const d3 = cross(a, b, c);
  const d4 = cross(a, b, d);
  if ( ((d1 > 0 && d2 < 0) || (d1 < 0 && d2 > 0)) && ((d3 > 0 && d4 < 0) || (d3 < 0 && d4 > 0)) ) return true;
  return (d1 == 0 && within(c, d, a)) || (d2 == 0 && within(c, d, b)) ||
    (d3 == 0 && within(a, b, c)) || (d4 == 0 && within(a, b, d));
}

/* The zone is edited in the page, so this has to look at the points as they are now */
function pointsSelfIntersect(points) {
  const n = points.length;
  const pts = points.map((p) => ({x: parseFloat(p.x), y: parseFloat(p.y)}));
  for ( let i = 0; i < n; i++ ) {
    for ( let j = i+2; j < n; j++ ) {
      if ( i == 0 && j == n-1 ) continue; // these edges share a vertex
      if ( segmentsIntersect(pts[i], pts[(i+1)%n], pts[j], pts[(j+1)%n]) ) return true;
    }
  }
  return false;
}

function validateForm( form ) {
  var errors = [];
  if ( pointsSelfIntersect(zone['Points']) ) {
    errors[errors.length] = selfIntersectingString;
  }
  if ( form.elements['newZone[Type]'].value != 'Inactive' && form.elements['newZone[Type]'].value != 'Privacy' ) {
    if ( !form.newAlarmRgbR.value || !form.newAlarmRgbG.value || !form.newAlarmRgbB.value ) {
      errors[errors.length] = alarmRGBUnsetString;
    }
    form.elements['newZone[AlarmRGB]'].value = (form.newAlarmRgbR.value<<16)|(form.newAlarmRgbG.value<<8)|form.newAlarmRgbB.value;
    if ( !form.elements['newZone[MinPixelThreshold]'].value || (parseInt(form.elements['newZone[MinPixelThreshold]'].value) <= 0 ) ) {
      errors[errors.length] = minPixelThresUnsetString;
    } else if ( (parseInt(form.elements['newZone[MinPixelThreshold]'].value) >= parseInt(form.elements['newZone[MaxPixelThreshold]'].value)) && (parseInt(form.elements['newZone[MaxPixelThreshold]'].value) > 0) ) {
      errors[errors.length] = minPixelThresLtMaxString;
    }
    if ( form.elements['newZone[CheckMethod]'].value == 'FilteredPixels' || form.elements['newZone[CheckMethod]'].value == 'Blobs' ) {
      if ( !form.elements['newZone[FilterX]'].value || !form.elements['newZone[FilterY]'].value ) {
        errors[errors.length] = filterUnsetString;
      }
    }
    if ( !form.elements['newZone[MinAlarmPixels]'].value || (parseFloat(form.elements['newZone[MinAlarmPixels]'].value) <= 0 ) ) {
      errors[errors.length] = minAlarmAreaUnsetString;
    } else if ( (parseFloat(form.elements['newZone[MinAlarmPixels]'].value) >= parseFloat(form.elements['newZone[MaxAlarmPixels]'].value)) && (parseFloat(form.elements['newZone[MaxAlarmPixels]'].value) > 0) ) {
      errors[errors.length] = minAlarmAreaLtMaxString;
    }
    if ( form.elements['newZone[CheckMethod]'].value == 'FilteredPixels' || form.elements['newZone[CheckMethod]'].value == 'Blobs' ) {
      if ( !form.elements['newZone[MinFilterPixels]'].value || (parseFloat(form.elements['newZone[MinFilterPixels]'].value) <= 0 ) ) {
        errors[errors.length] = minFilterAreaUnsetString;
      } else if ( (parseFloat(form.elements['newZone[MinFilterPixels]'].value) >= parseFloat(form.elements['newZone[MaxFilterPixels]'].value)) && (parseFloat(form.elements['newZone[MaxFilterPixels]'].value) > 0) ) {
        errors[errors.length] = minFilterAreaLtMaxString;
      } else if ( parseFloat(form.elements['newZone[MinAlarmPixels]'].value) < parseFloat(form.elements['newZone[MinFilterPixels]'].value) ) {
        errors[errors.length] = minFilterLtMinAlarmString;
      }
      if ( form.elements['newZone[CheckMethod]'].value == 'Blobs' ) {
        if ( !form.elements['newZone[MinBlobPixels]'].value || (parseFloat(form.elements['newZone[MinBlobPixels]'].value) <= 0 ) ) {
          errors[errors.length] = minBlobAreaUnsetString;
        } else if ( (parseFloat(form.elements['newZone[MinBlobPixels]'].value) >= parseFloat(form.elements['newZone[MaxBlobPixels]'].value)) && (parseFloat(form.elements['newZone[MaxBlobPixels]'].value) > 0) ) {
          errors[errors.length] = minBlobAreaLtMaxString;
        } else if ( parseFloat(form.elements['newZone[MinFilterPixels]'].value) < parseFloat(form.elements['newZone[MinBlobPixels]'].value) ) {
          errors[errors.length] = minBlobLtMinFilterString;
        }
        if ( !form.elements['newZone[MinBlobs]'].value || (parseInt(form.elements['newZone[MinBlobs]'].value) <= 0 ) ) {
          errors[errors.length] = minBlobsUnsetString;
        } else if ( (parseInt(form.elements['newZone[MinBlobs]'].value) >= parseInt(form.elements['newZone[MaxBlobs]'].value)) && (parseInt(form.elements['newZone[MaxBlobs]'].value) > 0) ) {
          errors[errors.length] = minBlobsLtMaxString;
        }
      }
    }
  }
  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}

function submitForm(form) {
  form.elements['newZone[AlarmRGB]'].value = (form.newAlarmRgbR.value<<16)|(form.newAlarmRgbG.value<<8)|form.newAlarmRgbB.value;
  form.elements['newZone[NumCoords]'].value = zone['Points'].length;
  form.elements['newZone[Coords]'].value = getCoordString();
  form.elements['newZone[Area]'].value = zone.Area;

  form.submit();
}

function syncPixelInputStates() {
  var fields = ['MinAlarmPixels', 'MaxAlarmPixels', 'MinFilterPixels', 'MaxFilterPixels', 'MinBlobPixels', 'MaxBlobPixels'];
  var form = document.zoneForm;
  for (var i = 0; i < fields.length; i++) {
    var pctInput = form.elements['newZone[' + fields[i] + ']'];
    var pxInput = document.getElementById(fields[i] + '_px');
    if (pctInput && pxInput) {
      pxInput.disabled = pctInput.disabled;
    }
  }
}

/* The colour picker and the R/G/B inputs are two views of the same value */
function alarmColourFromInputs() {
  const form = document.zoneForm;
  const picker = document.getElementById('alarmColour');
  if (!picker) return;
  const hex = (value) => ('0'+(parseInt(value) || 0).toString(16)).slice(-2);
  picker.value = '#'+hex(form.newAlarmRgbR.value)+hex(form.newAlarmRgbG.value)+hex(form.newAlarmRgbB.value);
  picker.disabled = form.newAlarmRgbR.disabled;
}

function alarmColourToInputs(picker) {
  const form = document.zoneForm;
  form.newAlarmRgbR.value = parseInt(picker.value.substr(1, 2), 16);
  form.newAlarmRgbG.value = parseInt(picker.value.substr(3, 2), 16);
  form.newAlarmRgbB.value = parseInt(picker.value.substr(5, 2), 16);
  checkDirty();
}

function applyZoneType() {
  var form = document.zoneForm;
  var typeSelect = form.elements['newZone[Type]'];

  // Sync the closed <select> color class with the currently selected option
  if (typeSelect && typeSelect.selectedIndex >= 0) {
    // Remove any existing zone-type-* class while preserving other classes (e.g. form-control)
    typeSelect.className = typeSelect.className.replace(/\bzone-type-\S+/g, '').trim();
    var selectedClass = typeSelect.options[typeSelect.selectedIndex].className;
    if (selectedClass) {
      typeSelect.classList.add(selectedClass);
    }
  }

  if ( form.elements['newZone[Type]'].value == 'Inactive' || form.elements['newZone[Type]'].value == 'Privacy' ) {
    form.presetSelector.disabled = true;
    form.newAlarmRgbR.disabled = true;
    form.newAlarmRgbG.disabled = true;
    form.newAlarmRgbB.disabled = true;
    form.elements['newZone[CheckMethod]'].disabled = true;
    form.elements['newZone[MinPixelThreshold]'].disabled = true;
    form.elements['newZone[MaxPixelThreshold]'].disabled = true;
    form.elements['newZone[MinAlarmPixels]'].disabled = true;
    form.elements['newZone[MaxAlarmPixels]'].disabled = true;
    form.elements['newZone[FilterX]'].disabled = true;
    form.elements['newZone[FilterY]'].disabled = true;
    form.elements['newZone[MinFilterPixels]'].disabled = true;
    form.elements['newZone[MaxFilterPixels]'].disabled = true;
    form.elements['newZone[MinBlobPixels]'].disabled = true;
    form.elements['newZone[MaxBlobPixels]'].disabled = true;
    form.elements['newZone[MinBlobs]'].disabled = true;
    form.elements['newZone[MaxBlobs]'].disabled = true;
    form.elements['newZone[OverloadFrames]'].disabled = true;
    form.elements['newZone[ExtendAlarmFrames]'].disabled = true;
  } else if ( form.elements['newZone[Type]'].value == 'Preclusive' ) {
    form.presetSelector.disabled = false;
    form.newAlarmRgbR.disabled = true;
    form.newAlarmRgbG.disabled = true;
    form.newAlarmRgbB.disabled = true;
    form.elements['newZone[CheckMethod]'].disabled = false;
    form.elements['newZone[MinPixelThreshold]'].disabled = false;
    form.elements['newZone[MaxPixelThreshold]'].disabled = false;
    form.elements['newZone[MinAlarmPixels]'].disabled = false;
    form.elements['newZone[MaxAlarmPixels]'].disabled = false;
    form.elements['newZone[OverloadFrames]'].disabled = false;
    form.elements['newZone[ExtendAlarmFrames]'].disabled = false;
    applyCheckMethod();
  } else {
    form.presetSelector.disabled = false;
    form.newAlarmRgbR.disabled = false;
    form.newAlarmRgbG.disabled = false;
    form.newAlarmRgbB.disabled = false;
    form.elements['newZone[CheckMethod]'].disabled = false;
    form.elements['newZone[MinPixelThreshold]'].disabled = false;
    form.elements['newZone[MaxPixelThreshold]'].disabled = false;
    form.elements['newZone[MinAlarmPixels]'].disabled = false;
    form.elements['newZone[MaxAlarmPixels]'].disabled = false;
    form.elements['newZone[OverloadFrames]'].disabled = false;
    form.elements['newZone[ExtendAlarmFrames]'].disabled = true;
    applyCheckMethod();
  }

  var presetId = $j('#presetSelector').val();
  var loadPresetBtn = document.getElementById('loadPresetBtn');
  if (loadPresetBtn) {
    loadPresetBtn.disabled = form.presetSelector.disabled || !presets[presetId];
  }
  var savePresetBtn = document.getElementById('savePresetBtn');
  if (savePresetBtn) {
    savePresetBtn.disabled = form.presetSelector.disabled;
  }

  syncPixelInputStates();
  alarmColourFromInputs();
}

function applyCheckMethod() {
  var form = document.zoneForm;
  // These types have no check method, applyZoneType() has disabled all of it
  if ( form.elements['newZone[Type]'].value == 'Inactive' || form.elements['newZone[Type]'].value == 'Privacy' ) {
    syncPixelInputStates();
    return;
  }
  if ( form.elements['newZone[CheckMethod]'].value == 'AlarmedPixels' ) {
    form.elements['newZone[FilterX]'].disabled = true;
    form.elements['newZone[FilterY]'].disabled = true;
    form.elements['newZone[MinFilterPixels]'].disabled = true;
    form.elements['newZone[MaxFilterPixels]'].disabled = true;
    form.elements['newZone[MinBlobPixels]'].disabled = true;
    form.elements['newZone[MaxBlobPixels]'].disabled = true;
    form.elements['newZone[MinBlobs]'].disabled = true;
    form.elements['newZone[MaxBlobs]'].disabled = true;
  } else if ( form.elements['newZone[CheckMethod]'].value == 'FilteredPixels' ) {
    form.elements['newZone[FilterX]'].disabled = false;
    form.elements['newZone[FilterY]'].disabled = false;
    form.elements['newZone[MinFilterPixels]'].disabled = false;
    form.elements['newZone[MaxFilterPixels]'].disabled = false;
    form.elements['newZone[MinBlobPixels]'].disabled = true;
    form.elements['newZone[MaxBlobPixels]'].disabled = true;
    form.elements['newZone[MinBlobs]'].disabled = true;
    form.elements['newZone[MaxBlobs]'].disabled = true;
  } else {
    form.elements['newZone[FilterX]'].disabled = false;
    form.elements['newZone[FilterY]'].disabled = false;
    form.elements['newZone[MinFilterPixels]'].disabled = false;
    form.elements['newZone[MaxFilterPixels]'].disabled = false;
    form.elements['newZone[MinBlobPixels]'].disabled = false;
    form.elements['newZone[MaxBlobPixels]'].disabled = false;
    form.elements['newZone[MinBlobs]'].disabled = false;
    form.elements['newZone[MaxBlobs]'].disabled = false;
  }
  syncPixelInputStates();
}

function applyPreset() {
  var form = document.zoneForm;
  var presetId = $j('#presetSelector').val();
  var isBuiltIn = isBuiltInPreset(presetId);

  // Update load and delete preset button states
  var loadPresetBtn = document.getElementById('loadPresetBtn');
  if (loadPresetBtn) {
    loadPresetBtn.disabled = form.presetSelector.disabled || !presets[presetId];
  }
  var deleteBtn = document.getElementById('deletePresetBtn');
  if (deleteBtn) {
    var isUserPreset = parseInt(presetId, 10) > 0 && !isBuiltIn;
    deleteBtn.style.display = isUserPreset ? 'inline-block' : 'none';
  }

  if ( presets[presetId] ) {
    var preset = presets[presetId];

    for (var i = 0; i < zonePresetFields.length; i++) {
      var field = zonePresetFields[i];
      var el = form.elements['newZone[' + field + ']'];
      if (el) {
        // Preserve legacy CheckMethodIndex selection for the ENUM dropdown; other fields assign directly
        if (field === 'CheckMethod' && typeof preset['CheckMethodIndex'] !== 'undefined') {
          el.selectedIndex = preset['CheckMethodIndex'];
        } else {
          el.value = preset[field];
        }
      }
    }

    applyCheckMethod();
    form.elements['newZone[Area]'].value = 100;
    updateAllPixelDisplays();
    // Loading with the button fires no input event, so the form is not told it changed
    checkDirty();
  }
}

// Update the pixel input to reflect the current percentage value
function updatePixelFromPercent(pctInput) {
  var pixelArea = Math.round(zone.Area / monitorArea * monitorPixelArea);
  var match = pctInput.name.match(/\[(\w+)\]$/);
  if (!match) return;
  var pxInput = document.getElementById(match[1] + '_px');
  if (!pxInput) return;
  if (pctInput.value !== '' && pixelArea > 0) {
    pxInput.value = Math.round(parseFloat(pctInput.value) * pixelArea / 100);
    pxInput.setAttribute('max', pixelArea);
  } else {
    pxInput.value = '';
  }
}

// Update the percentage input to reflect a pixel input change
function updatePercentFromPixel(pxInput) {
  var pixelArea = Math.round(zone.Area / monitorArea * monitorPixelArea);
  // Derive the form field name from the px input id: MinAlarmPixels_px -> newZone[MinAlarmPixels]
  var fieldName = pxInput.id.replace('_px', '');
  var pctInput = document.zoneForm.elements['newZone[' + fieldName + ']'];
  if (!pctInput) return;
  if (pxInput.value !== '' && pixelArea > 0) {
    var px = Math.max(0, Math.min(parseInt(pxInput.value), pixelArea));
    pxInput.value = px;
    pctInput.value = Math.round(px / pixelArea * 10000) / 100;
  } else {
    pctInput.value = '';
  }
}

function updateAllPixelDisplays() {
  var pixelArea = Math.round(zone.Area / monitorArea * monitorPixelArea);
  var form = document.zoneForm;

  // Update area display
  var areaPct = Math.round(zone.Area / monitorArea * 100);
  form.elements['newZone[Area]'].value = areaPct;
  var areaPctSpan = document.getElementById('areaPercent');
  if (areaPctSpan) areaPctSpan.textContent = areaPct;
  var areaPxSpan = document.getElementById('areaPixels');
  if (areaPxSpan) areaPxSpan.textContent = pixelArea;

  // Update threshold pixel inputs
  var fields = ['MinAlarmPixels', 'MaxAlarmPixels', 'MinFilterPixels', 'MaxFilterPixels', 'MinBlobPixels', 'MaxBlobPixels'];
  for (var i = 0; i < fields.length; i++) {
    var input = form.elements['newZone[' + fields[i] + ']'];
    if (input) updatePixelFromPercent(input);
  }
}

function limitRange(field, minValue, maxValue) {
  if ( field.value != '' ) {
    var currentValue = parseFloat(field.value);
    var constrainedValue = constrainValue(currentValue, parseInt(minValue), parseInt(maxValue));
    if ( constrainedValue !== currentValue ) {
      field.value = constrainedValue;
    }
  }
}

function limitRangeToUnsignedByte(field) {
  if ( field.value != '' ) {
    field.value = constrainValue(parseInt(field.value), 0, 255);
  }
}

function limitFilter(field) {
  field.value = (Math.floor((field.value-1)/2)*2) + 1;
  field.value = constrainValue(parseInt(field.value), 3, 15);
}

function limitArea(field) {
  limitRange(field, 0, 100);
  updatePixelFromPercent(field);
}

function highlightOn(index) {
  $j('#row'+index).addClass('highlight');
  $j('#point'+index).addClass('highlight');
}

function highlightOff(index) {
  row = $j('#row'+index);
  if ( row.length ) {
    row.removeClass('highlight');
  } else {
    console.log("No row for index " + index);
  }
  $j('#point'+index).removeClass('highlight');
}

var activePointIndex = -1;

function setActivePoint(index) {
  if ( activePointIndex >= 0 && activePointIndex != index ) {
    unsetActivePoint(activePointIndex);
  }
  activePointIndex = index;
  highlightOff(index);
  $j('#row'+index).addClass('active');
  $j('#point'+index).addClass('active');
}

function unsetActivePoint(index) {
  $j('#row'+index).removeClass('active');
  $j('#point'+index).removeClass('active');
  if ( activePointIndex == index ) {
    activePointIndex = -1;
  }
}

/* Keyboard nudging: a selected point can be moved a coordinate unit at a time,
 * through the same update path as typing in the point table. */
function zoneModalOpen() {
  if ( document.querySelector && document.querySelector('.modal.show') ) return true;
  const modal = document.getElementById('deleteConfirm');
  return !!(modal && modal.classList && modal.classList.contains('show'));
}

function isTextEntryTarget(target) {
  if ( !target ) return false;
  const tag = (target.tagName || '').toUpperCase();
  if ( tag == 'INPUT' || tag == 'TEXTAREA' || tag == 'SELECT' ) return true;
  return !!target.isContentEditable;
}

function nudgeSelectedPoint(dx, dy) {
  const index = activePointIndex;
  if ( index < 0 || index >= zone['Points'].length ) return;
  const point = zone['Points'][index];

  if ( dx ) {
    const input = document.getElementById('newZone[Points]['+index+'][x]');
    const target = input || {value: 0, getAttribute: function() {
      return String(index);
    }};
    target.value = parseFloat(point.x) + dx;
    updateX(target);
    if ( input ) input.value = parseFloat(zone['Points'][index].x).toFixed(2);
  }

  if ( dy ) {
    const input = document.getElementById('newZone[Points]['+index+'][y]');
    const target = input || {value: 0, getAttribute: function() {
      return String(index);
    }};
    target.value = parseFloat(point.y) + dy;
    updateY(target);
    if ( input ) input.value = parseFloat(zone['Points'][index].y).toFixed(2);
  }
}

function onZoneKeydown(event) {
  if ( !event ) return;
  if ( event.ctrlKey || event.altKey || event.metaKey ) return;
  if ( isTextEntryTarget(event.target) ) return;
  if ( zoneModalOpen() ) return;

  const step = event.shiftKey ? 10 : 1;
  let dx = 0;
  let dy = 0;
  switch ( event.key ) {
    case 'ArrowLeft': dx = -step; break;
    case 'ArrowRight': dx = step; break;
    case 'ArrowUp': dy = -step; break;
    case 'ArrowDown': dy = step; break;
    default: return;
  }

  // Only act while a point is selected. This leaves arrow keys alone for other
  // page behaviour when none is.
  if ( activePointIndex < 0 ) return;
  event.preventDefault();
  nudgeSelectedPoint(dx, dy);
}

function zonePointIndexFromNode(node) {
  while ( node && node !== document ) {
    if ( node.classList && node.classList.contains('zonePoint') ) {
      const index = parseInt(node.getAttribute('data-point-index'), 10);
      return isNaN(index) ? -1 : index;
    }
    node = node.parentNode;
  }
  return -1;
}

function onZonePointSelect(event) {
  const index = zonePointIndexFromNode(event.target);
  if ( index >= 0 ) setActivePoint(index);
}

function getCoordString() {
  var coords = [];
  for ( let i = 0; i < zone['Points'].length; i++ ) {
    coords[coords.length] = parseFloat(zone['Points'][i].x).toFixed(2)+','+parseFloat(zone['Points'][i].y).toFixed(2);
  }
  return coords.join(' ');
}

function updateZoneImage() {
  var SVG = document.getElementById('zoneSVG');
  var Poly = document.getElementById('zonePoly');
  Poly.points.clear();
  for ( let i = 0; i < zone['Points'].length; i++ ) {
    var Point = SVG.createSVGPoint();
    Point.x = zone['Points'][i].x;
    Point.y = zone['Points'][i].y;
    Poly.points.appendItem(Point);
  }
}

function fixActivePoint(index) {
  updateActivePoint(index);
  unsetActivePoint(index);
  updateZoneImage();
}

function constrainValue(value, loVal, hiVal) {
  if ( value < loVal ) {
    return loVal;
  }
  if ( value > hiVal ) {
    return hiVal;
  }
  return value;
}

function clampDragPosition(position, frameW, frameH) {
  position.left = constrainValue(position.left, 0, frameW);
  position.top = constrainValue(position.top, 0, frameH);
}

function updateActivePoint(index, position) {
  const point = $j('#point'+index);
  if (!point.length) return;
  const imageFeed = document.getElementById('imageFeed'+zone.MonitorId);
  const frameW = imageFeed.clientWidth;
  const frameH = imageFeed.clientHeight;

  let point_left = (position && typeof position === 'object') ? position.left : parseInt(point.css('left'), 10);
  if ( point_left < 0 ) {
    point.css('left', '0px');
    point_left = 0;
  }
  let point_top = (position && typeof position === 'object') ? position.top : parseInt(point.css('top'));
  if ( point_top < 0 ) {
    point.css('top', '0px');
    point_top = 0;
  }

  var x = constrainValue(Math.round((point_left / frameW) * maxX * 100) / 100, 0, maxX);
  var y = constrainValue(Math.round((point_top / frameH) * maxY * 100) / 100, 0, maxY);

  zone['Points'][index].x = x;
  zone['Points'][index].y = y;
  document.getElementById('newZone[Points]['+index+'][x]').value = x.toFixed(2);
  document.getElementById('newZone[Points]['+index+'][y]').value = y.toFixed(2);
  var Poly = document.getElementById('zonePoly');
  if (index < Poly.points.numberOfItems) {
    var Point = Poly.points.getItem(index);
    Point.x = x;
    Point.y = y;
  }
  updateArea();
} // end function updateActivePoint(index)

function addPoint(index) {
  pushUndo();
  var nextIndex = index+1;
  if ( index >= (zone['Points'].length-1) ) {
    nextIndex = 0;
  }

  var newX = Math.round(((parseFloat(zone['Points'][index]['x'])+parseFloat(zone['Points'][nextIndex]['x']))/2) * 100) / 100;
  var newY = Math.round(((parseFloat(zone['Points'][index]['y'])+parseFloat(zone['Points'][nextIndex]['y']))/2) * 100) / 100;
  if ( nextIndex == 0 ) {
    zone['Points'][zone['Points'].length] = {'x': newX, 'y': newY};
  } else {
    zone['Points'].splice(nextIndex, 0, {'x': newX, 'y': newY});
  }
  drawZonePoints();
}

function delPoint(index) {
  pushUndo();
  zone['Points'].splice(index, 1);
  drawZonePoints();
  updateArea();
}

/* Keeps deep copies of the points so an edit can be rolled back */
function pushUndo() {
  undoInput = null;
  undoStack.push(JSON.parse(JSON.stringify(zone['Points'])));
  if ( undoStack.length > maxUndoStack ) {
    undoStack.shift();
  }
  updateUndoButton();
}

// The point inputs update on every keystroke; typing one value is one step.
function pushUndoForInput(input) {
  if ( undoInput === input ) return;
  pushUndo();
  undoInput = input;
}

function updateUndoButton() {
  undoBtn.prop('disabled', undoStack.length === 0);
}

function undo() {
  if ( undoStack.length === 0 ) return;
  zone['Points'] = undoStack.pop();
  undoInput = null;
  drawZonePoints();
  updateArea();
  updateUndoButton();
}

function handleUndoKeydown(event) {
  const key = (event.key || '').toLowerCase();
  if ( !((event.ctrlKey || event.metaKey) && key === 'z') ) return;
  const target = event.target || {};
  const tag = (target.tagName || '').toUpperCase();
  if ( tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' ) return;
  event.preventDefault();
  undo();
}

function limitPointValue(point, loVal, hiVal) {
  point.value = constrainValue(parseFloat(point.value), loVal, hiVal);
}

function updateArea( ) {
  // Area is calculated in percentage coordinate space (0-100 x 0-100)
  zone.Area = Polygon_calcArea(zone['Points']);
  updateAllPixelDisplays();
  checkDirty();
}

/* Updates the drawn point based on input from the coordinates text inputs */
function updateX(input) {
  const index = input.getAttribute('data-point-index');

  // Empty while the value is being retyped; keep the point where it is
  if ( input.value === '' ) return;
  pushUndoForInput(input);
  limitPointValue(input, 0, maxX);

  const point = $j('#point'+index);
  const x = parseFloat(input.value);
  const imageFeed = document.getElementById('imageFeed'+zone.MonitorId);
  const frameW = imageFeed.clientWidth;

  point.css('left', Math.round(x / maxX * frameW) + 'px');
  zone['Points'][index].x = x;
  const Point = document.getElementById('zonePoly').points.getItem(index);
  Point.x = x;
  updateArea();
}

/* Updates the drawn point based on input from the coordinates text inputs */
function updateY(input) {
  const index = input.getAttribute('data-point-index');
  if ( input.value === '' ) return;
  pushUndoForInput(input);
  limitPointValue(input, 0, maxY);

  const point = $j('#point'+index);
  const y = parseFloat(input.value);
  const imageFeed = document.getElementById('imageFeed'+zone.MonitorId);
  const frameH = imageFeed.clientHeight;

  point.css('top', Math.round(y / maxY * frameH) + 'px');
  zone['Points'][index].y = y;
  const Point = document.getElementById('zonePoly').points.getItem(index);
  Point.y = y;
  updateArea();
}

/* The preset and player selectors are in the form but are not part of the zone */
function isZoneField(el) {
  return el.name && el.name != 'presetSelector' && el.name != 'codec';
}

/* Serialise every named form field so we can tell whether anything changed */
function formState() {
  const form = document.zoneForm;
  const values = [];
  for ( let i = 0; i < form.elements.length; i++ ) {
    const el = form.elements[i];
    if ( isZoneField(el) ) {
      // Compare numbers by value so 100 and 100.00 don't look like a change
      const value = (el.type == 'number' && el.value !== '') ? parseFloat(el.value) : el.value;
      values.push(el.name+'='+value);
    }
  }
  return values.join('&');
}

/* Highlight the coordinate inputs of any point that differs from the loaded zone */
function markChangedPoints() {
  if ( initialPoints === null ) return;
  for ( let i = 0; i < zone['Points'].length; i++ ) {
    const original = initialPoints[i];
    const changed = !original ||
      parseFloat(zone['Points'][i].x).toFixed(2) != parseFloat(original.x).toFixed(2) ||
      parseFloat(zone['Points'][i].y).toFixed(2) != parseFloat(original.y).toFixed(2);
    $j('#newZone\\[Points\\]\\['+i+'\\]\\[x\\], #newZone\\[Points\\]\\['+i+'\\]\\[y\\]').toggleClass('changed', changed);
  }
}

function checkDirty() {
  if ( initialState === null ) return;
  resetBtn.prop('disabled', formState() == initialState);
  markChangedPoints();
}

function resetChanges() {
  const form = document.zoneForm;
  // drawZonePoints re-creates the point inputs, so they are not restored here
  pushUndo();
  zone['Points'] = JSON.parse(JSON.stringify(initialPoints));
  drawZonePoints();

  for ( const name in initialValues ) {
    const el = form.elements[name];
    if ( el ) el.value = initialValues[name];
  }
  form.presetSelector.selectedIndex = 0;

  var deleteBtn = document.getElementById('deletePresetBtn');
  if (deleteBtn) {
    deleteBtn.style.display = 'none';
  }

  applyZoneType();
  alarmColourFromInputs();
  updateArea();
  checkDirty();
}

function saveChanges(element) {
  var form = document.zoneForm;
  if ( validateForm(form) ) {
    submitForm(form);
    if ( form.elements['newZone[Type]'].value == 'Privacy' ) {
      alert('Capture process for this monitor will be restarted for the Privacy zone changes to take effect.');
    }
    for (let i = 0, length = monitors.length; i < length; i++) {
      monitors[i].stop();
    }
    return true;
  }
  return false;
}

function drawZonePoints() {
  var imageFeed = document.getElementById('imageFeed'+zone.MonitorId);
  $j('.zonePoint').remove();
  activePointIndex = -1;
  var frameW = imageFeed.clientWidth;
  var frameH = imageFeed.clientHeight;

  $j.each( zone['Points'], function(i, coord) {
    var div = $j('<div>');
    div.attr({
      'id': 'point'+i,
      'data-point-index': i,
      'class': 'zonePoint',
      'tabindex': '0',
      'title': 'Point '+(i+1)
    });
    div.css({
      left: Math.round(parseFloat(coord.x) / maxX * frameW)+"px",
      top: Math.round(parseFloat(coord.y) / maxY * frameH)+"px"
    });

    div.mouseover(highlightOn.bind(i, i));
    div.mouseout(highlightOff.bind(i, i));

    $j(imageFeed).append(div);

    div.draggable({
      'start': function() {
        pushUndo();
        setActivePoint(i);
      },
      'stop': fixActivePoint.bind(i, i),
      'drag': function(event, ui) {
        clampDragPosition(ui.position, imageFeed.clientWidth, imageFeed.clientHeight);
        updateActivePoint(i, ui.position);
      }
    });
  }); // end $j.each point

  var tables = $j('#zonePoints table table');
  tables.find('tbody').empty();

  for ( let i = 0; i < zone['Points'].length; i++ ) {
    var row = document.createElement('tr');
    row.id = 'row'+i;
    $j(row).mouseover(highlightOn.bind(i, i));
    $j(row).mouseout(highlightOff.bind(i, i));

    var cell = document.createElement('td');
    $j(cell).addClass('pointIndex').text(i+1).appendTo(row);

    cell = document.createElement('td');
    $j(cell).addClass('pointX');
    var input = document.createElement('input');
    $j(input).attr({
      'id': 'newZone[Points]['+i+'][x]',
      'name': 'newZone[Points]['+i+'][x]',
      'value': parseFloat(zone['Points'][i].x).toFixed(2),
      'type': 'number',
      'class': 'ZonePoint',
      'min': '0',
      'max': maxX,
      'step': 'any',
      'data-point-index': i
    });
    input.oninput = window['updateX'].bind(input, input);
    $j(input).appendTo(cell);
    $j(cell).appendTo(row);

    cell = document.createElement('td');
    $j(cell).addClass('pointY');
    input = document.createElement('input');
    $j(input).attr({
      'id': 'newZone[Points]['+i+'][y]',
      'name': 'newZone[Points]['+i+'][y]',
      'value': parseFloat(zone['Points'][i].y).toFixed(2),
      'type': 'number',
      'class': 'ZonePoint',
      'min': '0',
      'max': maxY,
      'step': 'any',
      'data-point-index': i
    });
    input.oninput = window['updateY'].bind(input, input);
    $j(input).appendTo(cell);
    $j(cell).appendTo(row);

    cell = document.createElement('td');
    $j(cell).addClass('pointAction');
    var pbtn = document.createElement('button');
    $j(pbtn)
        .attr('type', 'button')
        .text('+')
        .click(addPoint.bind(i, i))
        .appendTo(cell);

    if ( zone['Points'].length > 3 ) {
      var mbtn = document.createElement('button');
      $j(mbtn)
          .attr('id', 'delete'+i)
          .attr('type', 'button')
          .addClass('ml-1')
          .text('-')
          .click(delPoint.bind(i, i))
          .appendTo(cell);
    }
    $j(cell).appendTo(row);

    $j(row).appendTo(tables.eq((i%tables.length)).find('tbody'));
  } // end foreach point
  // Sets up the SVG polygon
  updateZoneImage();
  checkDirty();
} // end drawZonePoints()

function streamCmdPause() {
  for ( let i = 0, length = monitors.length; i < length; i++ ) {
    monitors[i].pause();
  }
  pauseBtn.hide();
  playBtn.show();
}

function streamCmdPlay() {
  for ( let i = 0, length = monitors.length; i < length; i++ ) {
    monitors[i].play();
  }
  pauseBtn.show();
  playBtn.hide();
}

function changePlayer() {
  const player = $j('#player').val();
  setCookie('zmZonePlayer', player);
  for (let i = 0, length = monitors.length; i < length; i++) {
    monitors[i].stop();
    monitors[i].setPlayer(player);
    setTimeout(function() {
      monitors[i].start();
    }, 200);
  }
}

//Make sure the various refreshes are still taking effect
function watchdogCheck(type) {
  if ( watchdogInactive[type] ) {
    watchdogFunctions[type]();
    watchdogInactive[type] = false;
  } else {
    watchdogInactive[type] = true;
  }
}

function watchdogOk(type) {
  watchdogInactive[type] = false;
}

function presetSelectorBlur() {
  //this.selectedIndex = 0;
}

function initPage() {
  const form = document.zoneForm;

  //form.elements['newZone[Name]'].disabled = true;
  //form.elements['newZone[Type]'].disabled = true;
  form.presetSelector.disabled = true;
  form.presetSelector.onblur = window['presetSelectorBlur'].bind(form.presetSelector, form.presetSelector);
  //form.elements['newZone[Units]'].disabled = true;
  let CheckMethod;
  if ( CheckMethod = form.elements['newZone[CheckMethod]'] ) {
    CheckMethod.disabled = true;
    CheckMethod.onchange = window['applyCheckMethod'].bind(CheckMethod, CheckMethod);
  }

  [
    'newZone[MinPixelThreshold]',
    'newZone[MaxPixelThreshold]',
    'newAlarmRgbR',
    'newAlarmRgbG',
    'newAlarmRgbB',
  ].forEach(
      function(element_name, index) {
        const el = form.elements[element_name];
        if ( el ) {
          el.oninput = window['limitRangeToUnsignedByte'].bind(el, el);
          el.disabled = true;
        } else {
          console.error("Element " + element_name + " not found in zone edit form");
        }
      });
  [
    'newZone[FilterX]',
    'newZone[FilterY]'
  ].forEach(
      function(element_name, index) {
        const el = form.elements[element_name];
        if ( el ) {
          el.onchange = window['limitFilter'].bind(el, el);
          el.disabled = true;
        } else {
          console.error("Element " + element_name + " not found in zone edit form");
        }
      }
  );
  [
    'newZone[MinAlarmPixels]',
    'newZone[MaxAlarmPixels]',
    'newZone[MinFilterPixels]',
    'newZone[MaxFilterPixels]'
  ].forEach(function(element_name, index) {
    const el = form.elements[element_name];
    if ( el ) {
      el.oninput = window['limitArea'].bind(el, el);
      el.disabled = true;
    } else {
      console.error("Element " + element_name + " not found in zone edit form");
    }
  }
  );

  form.elements['newZone[MinBlobPixels]'].oninput = window['limitArea'].bind(form.elements['newZone[MinBlobPixels]'], form.elements['newZone[MinBlobPixels]']);
  form.elements['newZone[MinBlobPixels]'].disabled = true;
  form.elements['newZone[MaxBlobPixels]'].oninput = window['limitArea'].bind(form.elements['newZone[MaxBlobPixels]'], form.elements['newZone[MaxBlobPixels]']);
  form.elements['newZone[MaxBlobPixels]'].disabled = true;

  // Bind pixel inputs for bidirectional sync (editing pixels updates percentages)
  var pxInputs = document.querySelectorAll('.pxInput');
  for (var i = 0; i < pxInputs.length; i++) {
    pxInputs[i].oninput = window['updatePercentFromPixel'].bind(pxInputs[i], pxInputs[i]);
  }

  form.elements['newZone[MinBlobs]'].disabled = true;
  form.elements['newZone[MaxBlobs]'].disabled = true;
  form.elements['newZone[OverloadFrames]'].disabled = true;

  applyZoneType();
  applyCheckMethod();
  updateAllPixelDisplays();

  const picker = document.getElementById('alarmColour');
  if (picker) {
    picker.oninput = window['alarmColourToInputs'].bind(picker, picker);
    $j('input[name^="newAlarmRgb"]').on('input change', alarmColourFromInputs);
    alarmColourFromInputs();
  }

  pauseBtn.click(streamCmdPause);
  playBtn.click(streamCmdPlay);
  playBtn.hide(); // hide pause initially

  let el;
  if ( el = saveBtn[0] ) {
    el.onclick = window['saveChanges'].bind(el, el);
  }
  if ( el = cancelBtn[0] ) {
    el.onclick = function() {
      for (let i = 0, length = monitors.length; i < length; i++) {
        monitors[i].stop();
      }
      window.history.back();
    };
  }

  if ( el = resetBtn[0] ) {
    el.onclick = resetChanges;
  }

  if ( el = undoBtn[0] ) {
    el.onclick = undo;
  }
  updateUndoButton();

  document.addEventListener('keydown', handleUndoKeydown);

  if ( el = analyseBtn[0] ) {
    el.onclick = function() {
      // Read current state from MonitorStream (server may have changed it)
      if (monitors.length) {
        analyse_frames = monitors[0].analyse_frames;
      }
      analyse_frames = !analyse_frames;
      if (analyse_frames) {
        analyseBtn.addClass('btn-primary');
        analyseBtn.removeClass('btn-secondary');
        analyseBtn.attr('title', translate['Showing Analysis']);
      } else {
        analyseBtn.removeClass('btn-primary');
        analyseBtn.addClass('btn-secondary');
        analyseBtn.attr('title', translate['Not Showing Analysis']);
      }
      for ( let i = 0, length = monitors.length; i < length; i++ ) {
        monitors[i].show_analyse_frames(analyse_frames);
      }
    };
  } else {
    console.log('Analyse button not found');
  }

  const cookiePlayer = getCookie('zmZonePlayer');

  for ( let i = 0, length = monitorData.length; i < length; i++ ) {
    monitors[i] = new MonitorStream(monitorData[i]);
    monitors[i].manageAvailablePlayers(cookiePlayer);
    monitors[i].setButton('analyseBtn', analyseBtn);
    monitors[i].setStreamScale();
    monitors[i].show_analyse_frames(analyse_frames);
    monitors[i].start();
  }

  // Move the zone SVG into the imageFeed container so it overlays only the image,
  // not the status bar below it.
  var imageFeed = document.getElementById('imageFeed'+zone.MonitorId);
  var zoneSVG = document.getElementById('zoneSVG');
  if (imageFeed && zoneSVG) {
    imageFeed.appendChild(zoneSVG);
  }

  if (imageFeed) {
    (new ResizeObserver(drawZonePoints)).observe(imageFeed);
  } else {
    window.addEventListener("resize", drawZonePoints, {passive: true});
  }
  changeScale();
  drawZonePoints();

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Disable the back button if there is nothing to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Snapshot the loaded state so the Reset button can restore it
  initialPoints = JSON.parse(JSON.stringify(zone['Points']));
  initialValues = {};
  for ( let i = 0; i < form.elements.length; i++ ) {
    const el = form.elements[i];
    if ( isZoneField(el) && el.name.indexOf('newZone[Points]') !== 0 ) {
      initialValues[el.name] = el.value;
    }
  }
  initialState = formState();
  $j('#zoneForm').on('input change', checkDirty);
  checkDirty();

  // Managed PresetBtn
  $j('#loadPresetBtn').on('click', function() {
    applyPreset();
  });

  $j('#savePresetBtn').on('click', function() {
    var form = document.zoneForm;
    var presetSelect = document.getElementById('presetSelector');
    var currentId = presetSelect ? presetSelect.value : 0;

    // Pre-fill name only for user-created presets; leave empty for built-in presets
    var defaultName = (parseInt(currentId, 10) > 0 && !isBuiltInPreset(currentId))
      ? presetSelect.options[presetSelect.selectedIndex].text
      : '';

    var promptText = (typeof enterZonePresetName !== 'undefined') ? enterZonePresetName : 'Enter zone preset name:';
    var invalidNameText = (typeof invalidZonePresetName !== 'undefined') ? invalidZonePresetName : 'Please enter a valid preset name.';

    var name = prompt(promptText, defaultName);
    if (name === null) {
      return;
    }

    var trimmedName = name.trim();
    if (trimmedName === '') {
      alert(invalidNameText);
      return;
    }

    // Prevent saving under the name of any built-in preset shown in the dropdown
    if (presetSelect) {
      for (var i = 0; i < presetSelect.options.length; i++) {
        var opt = presetSelect.options[i];
        if (isBuiltInPreset(opt.value) && opt.text.trim().toLowerCase() === trimmedName.toLowerCase()) {
          alert(invalidNameText);
          return;
        }
      }
    }

    var postData = {
      view: 'zone',
      action: 'save_preset',
      mid: form.elements['mid'].value,
      zid: form.elements['zid'].value,
      presetName: trimmedName
    };

    if (form.elements['__csrf_magic']) {
      postData['__csrf_magic'] = form.elements['__csrf_magic'].value;
    }

    for (var i = 0; i < zonePresetFields.length; i++) {
      var field = zonePresetFields[i];
      var el = form.elements['newZone[' + field + ']'];
      if (el) {
        postData['newZone[' + field + ']'] = el.value;
      }
    }

    $j.post('?view=zone', postData, function(data) {
      if (data.result === 'Ok' && data.preset) {
        var p = data.preset;
        presets[p.Id] = p;
        var opt = $j('#presetSelector option[value="' + p.Id + '"]');
        if (opt.length) {
          opt.text(p.Name);
        } else {
          $j('#presetSelector').append($j('<option>', {value: p.Id, text: p.Name}));
        }
        $j('#presetSelector').val(p.Id);
        $j('#loadPresetBtn').prop('disabled', false);
        $j('#deletePresetBtn').css('display', 'inline-block');
      } else if (data.message) {
        alert(data.message);
      }
    }, 'json').fail(logAjaxFail);
  });

  $j('#deletePresetBtn').on('click', function() {
    var presetSelect = document.getElementById('presetSelector');
    var presetId = presetSelect ? presetSelect.value : 0;
    if (parseInt(presetId, 10) <= 0 || isBuiltInPreset(presetId)) {
      return;
    }
    getDelConfirmModal('ZoneConfirmDeletePreset', 'ConfirmDeleteTitle');
  });
} // initPage

/* Sizes the feed to the space left above the Save/Reset/Cancel buttons, the same
 * way the watch view fits its stream.  Called on load and on window resize. */
function changeScale() {
  const feed = $j('#imageFeed'+zone.MonitorId);
  if (!feed.length) return;
  const controls = $j('#StreamControlButtons');
  const newSize = scaleToFit(monitorData[0].width, monitorData[0].height, feed, controls, $j('#imageAndPoints'));
  if (!newSize) return;
  feed.css('width', newSize.width+'px');
  // Line the stream controls and the zone test panel up with the video, which
  // is centred in the column
  controls.add('#zoneTestPanel').css({
    'width': newSize.width+'px',
    'margin-left': (feed.offset().left - $j('#imageAndPoints').offset().left)+'px'
  });
  // Ask for an image close to the size we display it at.  The scale set when the
  // stream started was calculated before the feed had been sized, so it was far
  // too small and the browser had to enlarge the image.  Round down by 5: our
  // scaler is not precise enough for the last few percent to be worth the bytes.
  if (monitors.length) {
    monitors[0].setStreamScale(5 * Math.floor(newSize.width / monitorData[0].width * 20));
  }
}

function panZoomIn(el) {
  zmPanZoom.zoomIn(el);
}

function panZoomOut(el) {
  zmPanZoom.zoomOut(el);
}

function Polygon_calcArea(coords) {
  var n_coords = coords.length;
  var float_area = 0.0;

  for ( i = 0; i < n_coords-1; i++ ) {
    var trap_area = (parseFloat(coords[i].x)*parseFloat(coords[i+1].y) - parseFloat(coords[i+1].x)*parseFloat(coords[i].y)) / 2;
    float_area += trap_area;
  }
  float_area += (parseFloat(coords[n_coords-1].x)*parseFloat(coords[0].y) - parseFloat(coords[0].x)*parseFloat(coords[n_coords-1].y)) / 2;

  return Math.round(Math.abs(float_area));
}

// Callback invoked by getDelConfirmModal() when user confirms deletion
function manageDelConfirmModalBtns() {
  var form = document.zoneForm;
  var presetSelect = document.getElementById('presetSelector');
  var presetId = presetSelect ? presetSelect.value : 0;

  if (isBuiltInPreset(presetId)) {
    return;
  }

  var postData = {
    view: 'zone',
    action: 'delete_preset',
    mid: form.elements['mid'].value,
    zid: form.elements['zid'].value,
    presetId: presetId
  };

  if (form.elements['__csrf_magic']) {
    postData['__csrf_magic'] = form.elements['__csrf_magic'].value;
  }

  $j.post('?view=zone', postData, function(data) {
    if (data.result === 'Ok') {
      delete presets[presetId];
      $j('#presetSelector option[value="' + presetId + '"]').remove();
      $j('#presetSelector').val(0);
      $j('#loadPresetBtn').prop('disabled', true);
      $j('#deletePresetBtn').hide();
    } else if (data.message) {
      alert(data.message);
    }
  }, 'json').fail(logAjaxFail);
}

function isBuiltInPreset(presetId) {
  var id = parseInt(presetId, 10);
  if (!id || id <= 0) return true;
  var presetSelect = document.getElementById('presetSelector');
  if (presetSelect && presetSelect.dataset.builtinIds) {
    var builtInIds = presetSelect.dataset.builtinIds.split(',').map(Number);
    return builtInIds.indexOf(id) !== -1;
  }
  return true;
}

document.onvisibilitychange = () => {
  if (document.visibilityState === "hidden") {
    TimerHideShow = clearTimeout(TimerHideShow);
    TimerHideShow = setTimeout(function() {
      //Stop monitors when closing or hiding page
      for (let i = 0, length = monitorData.length; i < length; i++) {
        monitors[i].stop();
      }
    }, 15*1000);
  } else {
    // Back before the 15 s ran out: the pending stop would kill a visible stream
    TimerHideShow = clearTimeout(TimerHideShow);
    //Start monitors when show page - unless a replay or still the zone
    //tools show over the feed stopped them on purpose
    const replaying = document.querySelector('.replaying');
    for (let i = 0, length = monitorData.length; i < length; i++) {
      if (!monitors[i].started && !replaying) {
        monitors[i].start();
      }
    }
  }
};

window.addEventListener('DOMContentLoaded', initPage);

if ( typeof document !== 'undefined' && document.addEventListener ) {
  document.addEventListener('click', onZonePointSelect);
  document.addEventListener('focusin', onZonePointSelect);
  document.addEventListener('keydown', onZoneKeydown);
}
