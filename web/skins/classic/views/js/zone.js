function validateForm( form ) {
  var errors = new Array();
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
  var presetId = $('presetSelector').get('value');

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
  }
}

function toPercent(field, maxValue) {
  if ( field.value != '' ) {
    field.value = Math.round((100*100*field.value)/maxValue)/100;
  }
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
  $('row'+index).addClass('highlight');
  $('point'+index).addClass('highlight');
}

function highlightOff(index) {
  $('row'+index).removeClass('highlight');
  $('point'+index).removeClass('highlight');
}

function setActivePoint(index) {
  highlightOff(index);
  $('row'+index).addClass('active');
  $('point'+index).addClass('active');
}

function unsetActivePoint(index) {
  $('row'+index).removeClass('active');
  $('point'+index).removeClass('active');
}

function getCoordString() {
  var coords = new Array();
  for ( var i = 0; i < zone['Points'].length; i++ ) {
    coords[coords.length] = zone['Points'][i].x+','+zone['Points'][i].y;
  }
  return coords.join(' ');
}

function updateZoneImage() {
  var SVG = $('zoneSVG');
  var Poly = $('zonePoly');
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
  var point = $('point'+index);
  var x = constrainValue(point.getStyle('left').toInt(), 0, maxX);
  var y = constrainValue(point.getStyle('top').toInt(), 0, maxY);

  $('newZone[Points]['+index+'][x]').value = x;
  $('newZone[Points]['+index+'][y]').value = y;
  zone['Points'][index].x = x;
  zone['Points'][index].y = y;
  var Point = $('zonePoly').points.getItem(index);
  Point.x =x;
  Point.y =y;
  updateArea();
}

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
    zone['Points'].splice( nextIndex, 0, {'x': newX, 'y': newY} );
  }
  drawZonePoints();
  // drawZonePoints calls updateZoneImage
  //updateZoneImage();
  //setActivePoint( nextIndex );
}

function delPoint(index) {
  zone['Points'].splice(index, 1);
  drawZonePoints();
}

function limitPointValue(point, loVal, hiVal) {
  point.value = constrainValue(point.value, loVal, hiVal);
}

function updateArea( ) {
  area = Polygon_calcArea(zone['Points']);
  zone.Area = area;
  var form = $('zoneForm');
  form.elements['newZone[Area]'].value = area;
  if ( form.elements['newZone[Units]'].value == 'Percent' ) {
    form.elements['newZone[TempArea]'].value = Math.round( area/monitorArea*100 );
  } else if ( form.elements['newZone[Units]'].value == 'Pixels' ) {
    form.elements['newZone[TempArea]'].value = area;
  } else {
    alert("Unknown units: " + form.elements['newZone[Units]'].value);
  }
}

function updateX(index) {
  limitPointValue($('newZone[Points]['+index+'][x]'), 0, maxX);

  var point = $('point'+index);
  var x = $('newZone[Points]['+index+'][x]').get('value');

  point.setStyle('left', x+'px');
  zone['Points'][index].x = x;
  var Point = $('zonePoly').points.getItem(index);
  Point.x = x;
  updateArea();
}

function updateY(index) {
  limitPointValue($('newZone[Points]['+index+'][y]'), 0, maxY);

  var point = $('point'+index);
  var y = $('newZone[Points]['+index+'][y]').get('value');

  point.setStyle('top', y+'px');
  zone['Points'][index].y = y;
  var Point = $('zonePoly').points.getItem(index);
  Point.y = y;
  updateArea();
}

function saveChanges(element) {
  var form = element.form;
  if ( validateForm(form) ) {
    submitForm(form);
    if ( form.elements['newZone[Type]'].value == 'Privacy' ) {
      alert( 'Capture process for this monitor will be restarted for the Privacy zone changes to take effect.' );
    }
    return true;
  }
  return false;
}

function drawZonePoints() {
  $('imageFrame').getElements('div.zonePoint').each(
      function(element) {
        element.destroy();
      });
  for ( var i = 0; i < zone['Points'].length; i++ ) {
    var div = new Element('div', {
      'id': 'point'+i,
      'class': 'zonePoint',
      'title': 'Point '+(i+1),
      'styles': {
        'left': zone['Points'][i].x,
        'top': zone['Points'][i].y
      }
    });
    div.addEvent('mouseover', highlightOn.pass(i));
    div.addEvent('mouseout', highlightOff.pass(i));
    div.inject($('imageFrame'));
    div.makeDraggable( {
      'container': $('imageFrame'),
      'onStart': setActivePoint.pass(i),
      'onComplete': fixActivePoint.pass(i),
      'onDrag': updateActivePoint.pass(i)
    } );
  }

  var tables = $('zonePoints').getElement('table').getElements('table');
  tables.each( function(table) {
    table.getElement('tbody').empty();
  } );

  for ( var i = 0; i < zone['Points'].length; i++ ) {
    var row;
    row = new Element('tr', {'id': 'row'+i});
    row.addEvents({'mouseover': highlightOn.pass(i), 'mouseout': highlightOff.pass(i)});
    var cell = new Element('td');
    cell.set('text', i+1);
    cell.inject(row);

    cell = new Element('td');
    var input = new Element('input', {
      'id': 'newZone[Points]['+i+'][x]',
      'name': 'newZone[Points]['+i+'][x]',
      'value': zone['Points'][i].x,
      'size': 5
    });
    input.addEvent('input', updateX.pass(i));
    input.inject(cell);
    cell.inject(row);

    cell = new Element('td');
    input = new Element('input', {
      'id': 'newZone[Points]['+i+'][y]',
      'name': 'newZone[Points]['+i+'][y]',
      'value': zone['Points'][i].y,
      'size': 5
    } );
    input.addEvent('input', updateY.pass(i));
    input.inject(cell);
    cell.inject(row);

    cell = new Element('td');
    new Element('button', {
      'type': 'button',
      'events': {'click': addPoint.pass(i)}
    }).set('text', '+').inject(cell);
    if ( zone['Points'].length > 3 ) {
      cell.appendText(' ');
      new Element('button', {
        'id': 'delete'+i,
        'type': 'button',
        'events': {'click': delPoint.pass(i)}
      }).set('text', '-').inject(cell);
    }
    cell.inject(row);

    row.inject(tables[i%tables.length].getElement('tbody'));
  }
  // Sets up the SVG polygon
  updateZoneImage();
}

//
// Imported from watch.js and modified for new zone edit view
//

var alarmState = STATE_IDLE;
var lastAlarmState = STATE_IDLE;

function setAlarmState( currentAlarmState ) {
  alarmState = currentAlarmState;

  var stateClass = '';
  if ( alarmState == STATE_ALARM ) {
    stateClass = 'alarm';
  } else if ( alarmState == STATE_ALERT ) {
    stateClass = 'alert';
  }
  $('stateValue').set('text', stateStrings[alarmState]);
  if ( stateClass ) {
    $('stateValue').setProperty('class', stateClass);
  } else {
    $('stateValue').removeProperty('class');
  }

  var isAlarmed = ( alarmState == STATE_ALARM || alarmState == STATE_ALERT );
  var wasAlarmed = ( lastAlarmState == STATE_ALARM || lastAlarmState == STATE_ALERT );

  var newAlarm = ( isAlarmed && !wasAlarmed );
  var oldAlarm = ( !isAlarmed && wasAlarmed );

  if ( newAlarm ) {
    if ( SOUND_ON_ALARM ) {
      // Enable the alarm sound
      if ( !canPlayPauseAudio ) {
        $('alarmSound').removeClass('hidden');
      } else {
        $('MediaPlayer').Play();
      }
    }
  }
  if ( SOUND_ON_ALARM ) {
    if ( oldAlarm ) {
      // Disable alarm sound
      if ( !canPlayPauseAudio ) {
        $('alarmSound').addClass('hidden');
      } else {
        $('MediaPlayer').Stop();
      }
    }
  }
  lastAlarmState = alarmState;
}

var streamCmdParms = "view=request&request=stream&connkey="+connKey;
if ( auth_hash ) {
  streamCmdParms += '&auth='+auth_hash;
}
var streamCmdReq = new Request.JSON( {
  url: monitorUrl,
  method: 'get',
  timeout: AJAX_TIMEOUT,
  link: 'cancel',
  onSuccess: getStreamCmdResponse
} );
var streamCmdTimer = null;

var streamStatus;

function getStreamCmdResponse(respObj, respText) {
  watchdogOk("stream");
  if ( streamCmdTimer ) {
    streamCmdTimer = clearTimeout(streamCmdTimer);
  }

  if ( respObj.result == 'Ok' ) {
    streamStatus = respObj.status;
    $('fpsValue').set('text', streamStatus.fps);

    setAlarmState(streamStatus.state);

    if ( streamStatus.paused == true ) {
      streamCmdPause(false);
    } else if ( streamStatus.delayed == true && streamStatus.rate == 1 ) {
      streamCmdPlay(false);
    }
  } else {
    checkStreamForErrors('getStreamCmdResponse', respObj);//log them
    if ( ! streamPause ) {
      // Try to reload the image stream.
      var streamImg = $('liveStream'+monitorId);
      if ( streamImg ) {
        streamImg.src = streamImg.src.replace(/rand=\d+/i, 'rand='+Math.floor(Math.random() * 1000000));
      }
    }
  }

  if ( !streamPause ) {
    var streamCmdTimeout = statusRefreshTimeout;
    if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT ) {
      streamCmdTimeout = streamCmdTimeout/5;
    }
    streamCmdTimer = streamCmdQuery.delay(streamCmdTimeout);
  }
}

var streamPause = false;

function streamCmdPauseToggle() {
  if ( streamPause == true ) {
    streamCmdPlay(true);
    streamPause = false;
    document.getElementById('pauseBtn').innerHTML = pauseString;
  } else {
    streamCmdPause(true);
    streamPause = true;
    document.getElementById('pauseBtn').innerHTML = playString;
  }
}

function streamCmdPause(action) {
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_PAUSE);
  }
}

function streamCmdPlay(action) {
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_PLAY);
  }
}

function streamCmdStop(action) {
  if ( action ) {
    streamCmdReq.send(streamCmdParms+"&command="+CMD_STOP);
  }
}

function streamCmdQuery() {
  streamCmdReq.send(streamCmdParms+"&command="+CMD_QUERY);
}

var statusCmdParms = "view=request&request=status&entity=monitor&id="+monitorId+"&element[]=Status&element[]=FrameRate";
if ( auth_hash ) {
  statusCmdParms += '&auth='+auth_hash;
}
var statusCmdReq = new Request.JSON( {
  url: monitorUrl,
  method: 'get',
  data: statusCmdParms,
  timeout: AJAX_TIMEOUT,
  link: 'cancel',
  onSuccess: getStatusCmdResponse
} );
var statusCmdTimer = null;

function getStatusCmdResponse(respObj, respText) {
  watchdogOk("status");
  if ( statusCmdTimer ) {
    statusCmdTimer = clearTimeout(statusCmdTimer);
  }

  if ( respObj.result == 'Ok' ) {
    $('fpsValue').set('text', respObj.monitor.FrameRate);
    setAlarmState(respObj.monitor.Status);
  } else {
    checkStreamForErrors("getStatusCmdResponse", respObj);
  }

  if ( ! streamPause ) {
    var statusCmdTimeout = statusRefreshTimeout;
    if ( alarmState == STATE_ALARM || alarmState == STATE_ALERT ) {
      statusCmdTimeout = statusCmdTimeout/5;
    }
    statusCmdTimer = statusCmdQuery.delay(statusCmdTimeout);
  }
}

function statusCmdQuery() {
  statusCmdReq.send();
}

function fetchImage( streamImage ) {
  streamImage.src = streamImage.src.replace(/rand=\d+/i, 'rand='+Math.floor((Math.random() * 1000000) ));
}

function appletRefresh() {
  if ( streamStatus && (!streamStatus.paused && !streamStatus.delayed) ) {
    var streamImg = $('liveStream');
    var parent = streamImg.getParent();
    streamImg.dispose();
    streamImg.inject( parent );
    if ( appletRefreshTime ) {
      appletRefresh.delay( appletRefreshTime*1000 );
    }
  } else {
    appletRefresh.delay( 15*1000 ); //if we are paused or delayed check every 15 seconds if we are live yet...
  }
}

var watchdogInactive = {
  'stream': false,
  'status': false
};

var watchdogFunctions = {
  'stream': streamCmdQuery,
  'status': statusCmdQuery
};

//Make sure the various refreshes are still taking effect
function watchdogCheck(type) {
  if ( watchdogInactive[type] ) {
    console.log("Detected streamWatch of type: " + type + " stopped, restarting");
    watchdogFunctions[type]();
    watchdogInactive[type] = false;
  } else {
    watchdogInactive[type] = true;
  }
}

function watchdogOk(type) {
  watchdogInactive[type] = false;
}

function initPage() {
  var form = document.zoneForm;

  //form.elements['newZone[Name]'].disabled = true;
  //form.elements['newZone[Type]'].disabled = true;
  form.presetSelector.disabled = true;
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
  drawZonePoints();

  $('pauseBtn').onclick = function() {
    streamCmdPauseToggle();
  };
  if ( el = $('saveBtn') ) {
    el.onclick = window['saveChanges'].bind(el, el);
  }
  if ( el = $('cancelBtn') ) {
    el.onclick = function() {
      refreshParentWindow();
      closeWindow();
    };
  }

  //
  // Imported from watch.js and modified for new zone edit view
  //

  var delay = (Math.random()+0.1) * statusRefreshTimeout;
  //console.log("Delay for status updates is: " + delay );
  if ( streamMode == 'single' ) {
    statusCmdTimer = statusCmdQuery.delay(delay);
    watchdogCheck.pass('status').periodical(statusRefreshTimeout*2);
  } else {
    streamCmdTimer = streamCmdQuery.delay(delay);
    watchdogCheck.pass('stream').periodical(statusRefreshTimeout*2);
  }

  if ( canStreamNative || (streamMode == 'single') ) {
    var streamImg = $('imageFrame').getElement('img');
    if ( !streamImg ) {
      streamImg = $('imageFrame').getElement('object');
    }
    if ( streamMode == 'single' ) {
      streamImg.addEvent('click', fetchImage.pass(streamImg));
      fetchImage.pass(streamImg).periodical(imageRefreshTimeout);
    }
  }

  if ( refreshApplet && appletRefreshTime ) {
    appletRefresh.delay(appletRefreshTime*1000);
  }
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
