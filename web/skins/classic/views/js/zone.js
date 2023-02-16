var pauseBtn = $j('#pauseBtn');
var playBtn = $j('#playBtn');
var saveBtn = $j('#saveBtn');
var cancelBtn = $j('#cancelBtn');
var backBtn = $j('#backBtn');
var refreshBtn = $j('#refreshBtn');
var monitors = [];

function validateForm( form ) {
  var errors = [];
  if ( selfIntersecting ) {
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

function applyZoneType() {
  var form = document.zoneForm;
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
}

function applyCheckMethod() {
  var form = document.zoneForm;
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
}

function applyPreset() {
  var form = document.zoneForm;
  var presetId = $j('#presetSelector').val();

  if ( presets[presetId] ) {
    var preset = presets[presetId];

    form.elements['newZone[Units]'].selectedIndex = preset['UnitsIndex'];
    form.elements['newZone[CheckMethod]'].selectedIndex = preset['CheckMethodIndex'];
    form.elements['newZone[MinPixelThreshold]'].value = preset['MinPixelThreshold'];
    form.elements['newZone[MaxPixelThreshold]'].value = preset['MaxPixelThreshold'];
    form.elements['newZone[FilterX]'].value = preset['FilterX'];
    form.elements['newZone[FilterY]'].value = preset['FilterY'];
    form.elements['newZone[MinAlarmPixels]'].value = preset['MinAlarmPixels'];
    form.elements['newZone[MaxAlarmPixels]'].value = preset['MaxAlarmPixels'];
    form.elements['newZone[MinFilterPixels]'].value = preset['MinFilterPixels'];
    form.elements['newZone[MaxFilterPixels]'].value = preset['MaxFilterPixels'];
    form.elements['newZone[MinBlobPixels]'].value = preset['MinBlobPixels'];
    form.elements['newZone[MaxBlobPixels]'].value = preset['MaxBlobPixels'];
    form.elements['newZone[MinBlobs]'].value = preset['MinBlobs'];
    form.elements['newZone[MaxBlobs]'].value = preset['MaxBlobs'];
    form.elements['newZone[OverloadFrames]'].value = preset['OverloadFrames'];
    form.elements['newZone[ExtendAlarmFrames]'].value = preset['ExtendAlarmFrames'];

    applyCheckMethod();
    form.elements['newZone[TempArea]'].value = 100;
  }
}

function toPixels(field, maxValue) {
  if ( field.value != '' ) {
    field.value = Math.round((field.value*maxValue)/100);
    if ( field.value > maxValue ) {
      field.value = maxValue;
    }
  }
  field.setAttribute('step', 1);
  field.setAttribute('max', maxValue);
}

// maxValue is the max Pixels value which is normally the max area
function toPercent(field, maxValue) {
  if ( field.value != '' ) {
    field.value = Math.round((100*100*field.value)/maxValue)/100;
    if ( field.value > 100 ) {
      field.value = 100;
    }
  }
  field.setAttribute('step', 'any');
  field.setAttribute('max', 100);
}

function applyZoneUnits() {
  var area = zone.Area;

  var form = document.zoneForm;
  if ( form.elements['newZone[Units]'].value == 'Pixels' ) {
    form.elements['newZone[TempArea]'].value = area;
    toPixels(form.elements['newZone[MinAlarmPixels]'], area);
    toPixels(form.elements['newZone[MaxAlarmPixels]'], area);
    toPixels(form.elements['newZone[MinFilterPixels]'], area);
    toPixels(form.elements['newZone[MaxFilterPixels]'], area);
    toPixels(form.elements['newZone[MinBlobPixels]'], area);
    toPixels(form.elements['newZone[MaxBlobPixels]'], area);
  } else {
    form.elements['newZone[TempArea]'].value = Math.round(area/monitorArea * 100);
    toPercent(form.elements['newZone[MinAlarmPixels]'], area);
    toPercent(form.elements['newZone[MaxAlarmPixels]'], area);
    toPercent(form.elements['newZone[MinFilterPixels]'], area);
    toPercent(form.elements['newZone[MaxFilterPixels]'], area);
    toPercent(form.elements['newZone[MinBlobPixels]'], area);
    toPercent(form.elements['newZone[MaxBlobPixels]'], area);
  }
}

function limitRange(field, minValue, maxValue) {
  if ( field.value != '' ) {
    field.value = constrainValue(
        parseInt(field.value),
        parseInt(minValue),
        parseInt(maxValue)
    );
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
  var minValue = 0;
  var maxValue = zone.Area;
  if ( document.zoneForm.elements['newZone[Units]'].value == 'Percent' ) {
    maxValue = 100;
  }
  limitRange(field, minValue, maxValue);
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

function setActivePoint(index) {
  highlightOff(index);
  $j('#row'+index).addClass('active');
  $j('#point'+index).addClass('active');
}

function unsetActivePoint(index) {
  $j('#row'+index).removeClass('active');
  $j('#point'+index).removeClass('active');
}

function getCoordString() {
  var coords = [];
  for ( var i = 0; i < zone['Points'].length; i++ ) {
    coords[coords.length] = zone['Points'][i].x+','+zone['Points'][i].y;
  }
  return coords.join(' ');
}

function updateZoneImage() {
  var SVG = document.getElementById('zoneSVG');
  var Poly = document.getElementById('zonePoly');
  Poly.points.clear();
  for ( var i = 0; i < zone['Points'].length; i++ ) {
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

function updateActivePoint(index) {
  var point = $j('#point'+index);
  var imageFrame = document.getElementById('imageFrame'+monitorId);
  var style = imageFrame.currentStyle || window.getComputedStyle(imageFrame);
  var padding_left = parseInt(style.paddingLeft);
  var padding_top = parseInt(style.paddingTop);
  var padding_right = parseInt(style.paddingRight);
  var scale = (imageFrame.clientWidth - ( padding_top + padding_right )) / maxX;
  var left = parseInt(point.css('left'), 10);

  if ( left < padding_left ) {
    point.css('left', style.paddingLeft);
    left = parseInt(padding_left);
  }
  var top = parseInt(point.css('top'));
  if ( top < padding_top ) {
    point.css('top', style.paddingTop);
    top = parseInt(padding_top);
  }

  var x = constrainValue(Math.ceil(left / scale)-Math.ceil(padding_left/scale), 0, maxX);
  var y = constrainValue(Math.ceil(top / scale)-Math.ceil(padding_top/scale), 0, maxY);

  zone['Points'][index].x = document.getElementById('newZone[Points]['+index+'][x]').value = x;
  zone['Points'][index].y = document.getElementById('newZone[Points]['+index+'][y]').value = y;
  var Point = document.getElementById('zonePoly').points.getItem(index);
  Point.x = x;
  Point.y = y;
  updateArea();
} // end function updateActivePoint(index)

function addPoint(index) {
  var nextIndex = index+1;
  if ( index >= (zone['Points'].length-1) ) {
    nextIndex = 0;
  }

  var newX = parseInt(Math.round((zone['Points'][index]['x']+zone['Points'][nextIndex]['x'])/2));
  var newY = parseInt(Math.round((zone['Points'][index]['y']+zone['Points'][nextIndex]['y'])/2));
  if ( nextIndex == 0 ) {
    zone['Points'][zone['Points'].length] = {'x': newX, 'y': newY};
  } else {
    zone['Points'].splice(nextIndex, 0, {'x': newX, 'y': newY});
  }
  drawZonePoints();
}

function delPoint(index) {
  zone['Points'].splice(index, 1);
  drawZonePoints();
}

function limitPointValue(point, loVal, hiVal) {
  point.value = constrainValue(point.value, loVal, hiVal);
}

function updateArea( ) {
  const area = Polygon_calcArea(zone['Points']);
  zone.Area = area;
  const form = document.getElementById('zoneForm');
  form.elements['newZone[Area]'].value = area;
  if ( form.elements['newZone[Units]'].value == 'Percent' ) {
    form.elements['newZone[TempArea]'].value = Math.round( area/monitorArea*100 );
  } else if ( form.elements['newZone[Units]'].value == 'Pixels' ) {
    form.elements['newZone[TempArea]'].value = area;
  } else {
    alert('Unknown units: ' + form.elements['newZone[Units]'].value);
  }
}

function updateX(input) {
  const index = input.getAttribute('data-point-index');

  limitPointValue(input, 0, maxX);

  const point = $j('#point'+index);
  const x = parseInt(input.value);
  const imageFrame = document.getElementById('imageFrame'+monitorId);
  const style = imageFrame.currentStyle || window.getComputedStyle(imageFrame);
  const padding_left = parseInt(style.paddingLeft);
  const padding_right = parseInt(style.paddingRight);
  const scale = (imageFrame.clientWidth - ( padding_left + padding_right )) / maxX;

  point.css('left', parseInt(x*scale)+'px');
  zone['Points'][index].x = x;
  const Point = document.getElementById('zonePoly').points.getItem(index);
  Point.x = x;
  updateArea();
}

function updateY(input) {
  const index = input.getAttribute('data-point-index');
  limitPointValue(input, 0, maxY);

  const point = $j('#point'+index);
  const y = input.value;
  const imageFrame = document.getElementById('imageFrame'+monitorId);
  const style = imageFrame.currentStyle || window.getComputedStyle(imageFrame);
  const padding_left = parseInt(style.paddingLeft);
  const padding_right = parseInt(style.paddingRight);
  const scale = (imageFrame.clientWidth - ( padding_left + padding_right )) / maxX;

  point.css('top', parseInt(y*scale)+'px');
  zone['Points'][index].y = y;
  const Point = document.getElementById('zonePoly').points.getItem(index);
  Point.y = y;
  updateArea();
}

function saveChanges(element) {
  var form = element.form;
  if ( validateForm(form) ) {
    submitForm(form);
    if ( form.elements['newZone[Type]'].value == 'Privacy' ) {
      alert('Capture process for this monitor will be restarted for the Privacy zone changes to take effect.');
    }
    for (var i = 0, length = monitors.length; i < length; i++) {
      monitors[i].stop();
    }
    return true;
  }
  return false;
}

function drawZonePoints() {
  var imageFrame = document.getElementById('imageFrame'+monitorId);
  if (!imageFrame) {
    console.log("No imageFrame for " + monitorId);
    return;
  }
  $j('.zonePoint').remove();
  var style = imageFrame.currentStyle || window.getComputedStyle(imageFrame);
  var padding_left = parseInt(style.paddingLeft);
  var padding_right = parseInt(style.paddingRight);
  var padding_top = parseInt(style.paddingTop);
  var scale = (imageFrame.clientWidth - ( padding_left + padding_right )) / maxX;

  $j.each( zone['Points'], function(i, coord) {
    var div = $j('<div>');
    div.attr({
      'id': 'point'+i,
      'data-point-index': i,
      'class': 'zonePoint',
      'title': 'Point '+(i+1)
    });
    div.css({
      left: (Math.round(coord.x * scale) + padding_left)+"px",
      top: ((parseInt(coord.y * scale)) + padding_top) +"px"
    });

    div.mouseover(highlightOn.bind(i, i));
    div.mouseout(highlightOff.bind(i, i));

    $j(imageFrame).append(div);

    div.draggable({
      'containment': document.getElementById('imageFeed'+zone.MonitorId),
      'start': setActivePoint.bind(i, i),
      'stop': fixActivePoint.bind(i, i),
      'drag': updateActivePoint.bind(i, i)
    });
  }); // end $j.each point

  var tables = $j('#zonePoints table table');
  tables.find('tbody').empty();

  for ( var i = 0; i < zone['Points'].length; i++ ) {
    var row = document.createElement('tr');
    row.id = 'row'+i;
    $j(row).mouseover(highlightOn.bind(i, i));
    $j(row).mouseout(highlightOff.bind(i, i));

    var cell = document.createElement('td');
    $j(cell).text(i+1).appendTo(row);

    cell = document.createElement('td');
    var input = document.createElement('input');
    $j(input).attr({
      'id': 'newZone[Points]['+i+'][x]',
      'name': 'newZone[Points]['+i+'][x]',
      'value': zone['Points'][i].x,
      'type': 'number',
      'class': 'ZonePoint',
      'min': '0',
      'max': maxX,
      'data-point-index': i
    });
    input.oninput = window['updateX'].bind(input, input);
    $j(input).appendTo(cell);
    $j(cell).appendTo(row);

    cell = document.createElement('td');
    input = document.createElement('input');
    $j(input).attr({
      'id': 'newZone[Points]['+i+'][y]',
      'name': 'newZone[Points]['+i+'][y]',
      'value': zone['Points'][i].y,
      'type': 'number',
      'class': 'ZonePoint',
      'min': '0',
      'max': maxY,
      'data-point-index': i
    });
    input.oninput = window['updateY'].bind(input, input);
    $j(input).appendTo(cell);
    $j(cell).appendTo(row);

    cell = document.createElement('td');
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
}

function streamCmdPause() {
  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    monitors[i].pause();
  }
  pauseBtn.hide();
  playBtn.show();
}

function streamCmdPlay() {
  for ( var i = 0, length = monitors.length; i < length; i++ ) {
    monitors[i].play();
  }
  pauseBtn.show();
  playBtn.hide();
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
  this.selectedIndex = 0;
}

function initPage() {
  var form = document.zoneForm;

  //form.elements['newZone[Name]'].disabled = true;
  //form.elements['newZone[Type]'].disabled = true;
  form.presetSelector.disabled = true;
  form.presetSelector.onblur = window['presetSelectorBlur'].bind(form.presetSelector, form.presetSelector);
  //form.elements['newZone[Units]'].disabled = true;
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
        var el = form.elements[element_name];
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
        var el = form.elements[element_name];
        if ( el ) {
          el.oninput = window['limitFilter'].bind(el, el);
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
    var el = form.elements[element_name];
    if ( el ) {
      el.oninput = window['limitArea'].bind(el, el);
      el.disabled = true;
    } else {
      console.error("Element " + element_name + " not found in zone edit form");
    }
  }
  );

  form.elements['newZone[MinBlobPixels]'].disabled = true;
  form.elements['newZone[MaxBlobPixels]'].disabled = true;
  form.elements['newZone[MinBlobs]'].disabled = true;
  form.elements['newZone[MaxBlobs]'].disabled = true;
  form.elements['newZone[OverloadFrames]'].disabled = true;

  applyZoneType();

  if ( form.elements['newZone[Units]'].value == 'Percent' ) {
    applyZoneUnits();
  }

  applyCheckMethod();

  pauseBtn.click(streamCmdPause);
  playBtn.click(streamCmdPlay);
  playBtn.hide(); // hide pause initially

  if ( el = saveBtn[0] ) {
    el.onclick = window['saveChanges'].bind(el, el);
  }
  if ( el = cancelBtn[0] ) {
    el.onclick = function() {
      for (var i = 0, length = monitors.length; i < length; i++) {
        monitors[i].stop();
      }
      window.history.back();
    };
  }

  for ( var i = 0, length = monitorData.length; i < length; i++ ) {
    monitors[i] = new MonitorStream(monitorData[i]);

    // Start the fps and status updates. give a random delay so that we don't assault the server
    var delay = Math.round( (Math.random()+0.5)*statusRefreshTimeout );
    monitors[i].setScale('0');
    monitors[i].start(delay);
  }

  document.querySelectorAll('.imageFrame img').forEach(function(el) {
    el.addEventListener("load", imageLoadEvent, {passive: true});
  });
  window.addEventListener("resize", drawZonePoints, {passive: true});
  // if the image link is broken for some reason we won't draw the points, so do it manually
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
} // initPage

function imageLoadEvent() {
  // We only need this event on the first image load to set dimensions.
  // Turn it off after it has been called.
  document.querySelectorAll('.imageFrame img').forEach(function(el) {
    el.removeEventListener("load", imageLoadEvent, {passive: true});
  });
  drawZonePoints();
}

function Polygon_calcArea(coords) {
  var n_coords = coords.length;
  var float_area = 0.0;

  for ( i = 0; i < n_coords-1; i++ ) {
    var trap_area = (coords[i].x*coords[i+1].y - coords[i+1].x*coords[i].y) / 2;
    float_area += trap_area;
    //printf( "%.2f (%.2f)\n", float_area, trap_area );
  }
  float_area += (coords[n_coords-1].x*coords[0].y - coords[0].x*coords[n_coords-1].y) / 2;

  return Math.round(Math.abs(float_area));
}

window.addEventListener('DOMContentLoaded', initPage);
