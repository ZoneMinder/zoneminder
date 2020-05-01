function selectFilter(element) {
  element.form.submit();
}

function validateForm( form ) {
  var rows = $j(form).find('tbody').eq(0).find('tr');
  var obrCount = 0;
  var cbrCount = 0;
  for ( var i = 0; i < rows.length; i++ ) {
    if (rows.length > 2) {
      obrCount += parseInt(form.elements['filter[Query][terms][' + i + '][obr]'].value);
      cbrCount += parseInt(form.elements['filter[Query][terms][' + i + '][cbr]'].value);
    }
    if ( form.elements['filter[Query][terms][' + i + '][val]'].value == '' ) {
      alert(errorValue);
      return false;
    }
  }
  if ( (obrCount - cbrCount) != 0 ) {
    alert(errorBrackets);
    return false;
  }
  var numbers_reg = /\D/;
  if ( numbers_reg.test(form.elements['filter[Query][limit]'].value) ) {
    alert("There appear to be non-numeric characters in your limit. Limit must be a positive integer value or empty.");
    return false;
  }
  return true;
}

function updateButtons(element) {
  var form = element.form;
  if ( element.type == 'checkbox' && element.checked ) {
    form.elements['executeButton'].disabled = false;
  } else {
    var canExecute = false;
    if ( form.elements['filter[AutoArchive]'] && form.elements['filter[AutoArchive]'].checked ) {
      canExecute = true;
    } else if ( form.elements['filter[AutoCopy]'] && form.elements['filter[AutoCopy]'].checked ) {
      canExecute = true;
    } else if ( form.elements['filter[AutoMove]'] && form.elements['filter[AutoMove]'].checked ) {
      canExecute = true;
    } else if ( form.elements['filter[AutoVideo]'] && form.elements['filter[AutoVideo]'].checked ) {
      canExecute = true;
    } else if ( form.elements['filter[AutoUpload]'] && form.elements['filter[AutoUpload]'].checked ) {
      canExecute = true;
    } else if ( form.elements['filter[AutoEmail]'] && form.elements['filter[AutoEmail]'].checked ) {
      canExecute = true;
    } else if ( form.elements['filter[AutoMessage]'] && form.elements['filter[AutoMessage]'].checked ) {
      canExecute = true;
    } else if ( form.elements['filter[AutoExecute]'].checked && form.elements['filter[AutoExecuteCmd]'].value != '' ) {
      canExecute = true;
    } else if ( form.elements['filter[AutoDelete]'].checked ) {
      canExecute = true;
    } else if ( form.elements['filter[UpdateDiskSpace]'].checked ) {
      canExecute = true;
    }
    form.elements['executeButton'].disabled = !canExecute;
  }
  if ( form.elements['filter[Name]'].value ) {
    form.elements['Save'].disabled = false;
    form.elements['SaveAs'].disabled = false;
  } else {
    form.elements['Save'].disabled = true;
    form.elements['SaveAs'].disabled = true;
  }
}

function click_AutoEmail(element) {
  updateButtons(this);
  if ( this.checked ) {
    $j('#EmailOptions').show();
  } else {
    $j('#EmailOptions').hide();
  }
}

function click_automove(element) {
  updateButtons(this);
  if ( this.checked ) {
    $j(this.form.elements['filter[AutoMoveTo]']).css('display', 'inline');
  } else {
    this.form.elements['filter[AutoMoveTo]'].hide();
  }
}

function click_autocopy(element) {
  updateButtons(this);
  if ( this.checked ) {
    $j(this.form.elements['filter[AutoCopyTo]']).css('display', 'inline');
  } else {
    this.form.elements['filter[AutoCopyTo]'].hide();
  }
}

function checkValue( element ) {
  var rows = $j(element).closest('tbody').children();
  parseRows(rows);
  //clearValue(element);
}

function clearValue( element ) {
  $j(element).closest('tr').find('[type=text]').val('');
}

function resetFilter( element ) {
  element.form.reset();
  $j('#contentForm')[0].reset();
}

function submitToEvents( element ) {
  var form = element.form;
  form.action = thisUrl + '?view=events';
  history.replaceState(null, null, '?view=filter&' + $j(form).serialize());
}

function submitToMontageReview(element) {
  var form = element.form;
  form.action = thisUrl + '?view=montagereview';
  window.location.assign('?view=montagereview&'+$j(form).serialize());
  history.replaceState(null, null, '?view=montagereview&live=0&' + $j(form).serialize());
}

function submitToExport(element) {
  var form = element.form;
  window.location.assign('?view=export&'+$j(form).serialize());
  //createPopup('?view=export&filter_id='+form.elements['Id'].value, 'zmExport', 'export' );
}

function executeFilter( element ) {
  var form = element.form;
  form.action = thisUrl + '?view=filter';
  form.elements['action'].value = 'execute';
  form.submit();
  //history.replaceState(null, null, '?view=filter&' + $j(form).serialize());
}

function saveFilter( element ) {
  var form = element.form;
  form.target = window.name;
  form.elements['action'].value = element.value;
  form.action = thisUrl + '?view=filter';
  //form.submit();
  // Submit is done by the button type="submit"
}

function deleteFilter( element ) {
  var form = element.form;
  if ( confirm( deleteSavedFilterString+" '"+form.elements['filter[Name]'].value+"'?" ) ) {
    form.elements['action'].value = 'delete';
    form.submit();
  }
}
var escape = document.createElement('textarea');
function escapeHTML(html) {
  escape.textContent = html;
  return escape.innerHTML;
}

function parseRows(rows) {
  for ( var rowNum = 0; rowNum < rows.length; rowNum++ ) { //Each row is a term
    var queryPrefix = 'filter[Query][terms][';
    var inputTds = rows.eq(rowNum).children();

    if ( rowNum == 0 ) inputTds.eq(0).html('&nbsp'); //Remove and from first term
    if ( rowNum > 0 ) { //add and/or to 1+
      var cnjVal = inputTds.eq(0).children().val();
      var conjSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][cnj]').attr('id', queryPrefix + rowNum + '][cnj]');
      $j.each(conjTypes, function(i) {
        conjSelect.append('<option value="' + i + '" >' + i + '</option>');
      });
      inputTds.eq(0).html(conjSelect).children().val(cnjVal === undefined ? 'and' : cnjVal);
    }

    var brackets = rows.length - 2;
    if ( brackets > 0 ) { // add bracket select to all rows
      var obrSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][obr]').attr('id', queryPrefix + rowNum + '][obr]');
      var cbrSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][cbr]').attr('id', queryPrefix + rowNum + '][cbr]');
      obrSelect.append('<option value="0"</option>');
      cbrSelect.append('<option value="0"</option>');
      for ( var i = 1; i <= brackets; i++ ) { // build bracket options
        obrSelect.append('<option value="' + i + '">' + '('.repeat(i) + '</option>');
        cbrSelect.append('<option value="' + i + '">' + ')'.repeat(i) + '</option>');
      }
      var obrVal = inputTds.eq(1).children().val() != undefined ? inputTds.eq(1).children().val() : 0; // Save currently selected bracket option
      var cbrVal = inputTds.eq(5).children().val() != undefined ? inputTds.eq(5).children().val() : 0;
      inputTds.eq(1).html(obrSelect).children().val(obrVal); // Set bracket contents and assign saved value
      inputTds.eq(5).html(cbrSelect).children().val(cbrVal);
    } else {
      inputTds.eq(1).html('&nbsp'); // Blank if there aren't enough terms for brackets
      inputTds.eq(5).html('&nbsp');
    }

    if ( rows.length == 1 ) {
      inputTds.eq(6).find('button[data-on-click-this="delTerm"]').prop('disabled', true); // enable/disable remove row button
    } else {
      inputTds.eq(6).find('button[data-on-click-this="delTerm"]').prop('disabled', false);
    }

    var attr = inputTds.eq(2).children().val();

    if ( attr == 'Archived' ) { //Archived types
      inputTds.eq(3).html('equal to<input type="hidden" name="filter[Query][terms][' + rowNum + '][op]" value="=">');
      var archiveSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (var i = 0; i < archiveTypes.length; i++) {
        archiveSelect.append('<option value="' + i + '">' + archiveTypes[i] + '</option>');
      }
      var archiveVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(archiveSelect).children().val(archiveVal).chosen({width: "101%"});
    } else if ( attr == 'AlarmedZoneId' ) {
      var zoneSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for ( monitor_id in monitors ) {
        for ( zone_id in zones ) {
          var zone = zones[zone_id];
          if ( monitor_id == zone.MonitorId ) {
            zoneSelect.append('<option value="' + zone_id + '">' + zone.Name + '</option>');
          }
        } // end foreach zone
      } // end foreach monitor
      var zoneVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(zoneSelect).children().val(zoneVal).chosen({width: "101%"});
    } else if ( attr.indexOf('Weekday') >= 0 ) { //Weekday selection
      var weekdaySelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (var i = 0; i < weekdays.length; i++) {
        weekdaySelect.append('<option value="' + i + '">' + weekdays[i] + '</option>');
      }
      var weekdayVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(weekdaySelect).children().val(weekdayVal).chosen({width: "101%"});
    } else if ( attr == 'StateId' ) { //Run state
      var stateSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (var key in states) {
        stateSelect.append('<option value="' + key + '">' + states[key] + '</option>');
      }
      var stateVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(stateSelect).children().val(stateVal).chosen({width: "101%"});
    } else if ( attr == 'ServerId' || attr == 'MonitorServerId' || attr == 'StorageServerId' || attr == 'FilterServerId' ) { //Select Server
      var serverSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (var key in servers) {
        serverSelect.append('<option value="' + key + '">' + servers[key] + '</option>');
      }
      var serverVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(serverSelect).children().val(serverVal).chosen({width: "101%"});
    } else if ( (attr == 'StorageId') || (attr == 'SecondaryStorageId') ) { //Choose by storagearea
      var storageSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for ( key in storageareas ) {
        storageSelect.append('<option value="' + key + '">' + storageareas[key].Name + '</option>');
      }
      var storageVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(storageSelect).children().val(storageVal).chosen({width: "101%"});
    } else if ( attr == 'MonitorName' ) { //Monitor names
      var monitorSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for ( var monitor_id in monitors ) {
        monitorSelect.append('<option value="' + monitors[monitor_id].Name + '">' + escapeHTML(monitors[monitor_id].Name) + '</option>');
      }
      var monitorVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(monitorSelect).children().val(monitorVal);
    } else { // Reset to regular text field and operator for everything that isn't special
      var textInput = $j('<input></input>').attr('type', 'text').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      var textVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(textInput).children().val(textVal);
    }

    // Validate the operator
    var opSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][op]').attr('id', queryPrefix + rowNum + '][op]');
    var opVal = inputTds.eq(3).children().val();
    if ( ! opVal ) {
      // Default to equals so that something gets selected
      console.log("No value for operator. Defaulting to =");
      opVal = '=';
    }
    for ( var key in opTypes ) {
      opSelect.append('<option value="' + key + '"'+(key == opVal ? ' selected="selected"' : '')+'>' + opTypes[key] + '</option>');
    }
    inputTds.eq(3).html(opSelect).children().val(opVal).chosen({width: "101%"});
    if ( attr.endsWith('DateTime') ) { //Start/End DateTime
      inputTds.eq(4).children().datetimepicker({timeFormat: "HH:mm:ss", dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false});
    } else if ( attr.endsWith('Date') ) { //Start/End Date
      inputTds.eq(4).children().datepicker({dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false});
    } else if ( attr.endsWith('Time')) { //Start/End Time
      inputTds.eq(4).children().timepicker({timeFormat: "HH:mm:ss", constrainInput: false});
    }

    attr = inputTds.find("[name$='attr\\]']"); // Set attr list id and name
    var term = attr.attr('name').split(/[[\]]{1,2}/);
    term.length--;
    term.shift();
    term[2] = rowNum;
    inputTds.eq(2).children().eq(0).attr('name', 'filter'+stringFilter(term));
    inputTds.eq(2).children().eq(0).attr('id', 'filter'+stringFilter(term));
  } //End for each term/row
  history.replaceState(null, null, '?view=filter&' + $j('#contentForm').serialize());
}

function stringFilter(term) {
  var termString = '';
  term.forEach(function(item) {
    termString += '[' + item + ']';
  });
  return termString;
}

function addTerm( element ) {
  var row = $j(element).closest('tr');
  row.find('select').chosen('destroy');
  var newRow = row.clone().insertAfter(row);
  row.find('select').chosen({width: '101%'});
  newRow.find('select').each( function() { //reset new row to default
    this[0].selected = 'selected';
  }).chosen({width: '101%'});
  newRow.find('input[type="text"]').val('');
  newRow[0].querySelectorAll("button[data-on-click-this]").forEach(function(el) {
    var fnName = el.getAttribute("data-on-click-this");
    el.onclick = window[fnName].bind(el, el);
  });

  newRow[0].querySelectorAll('select[data-on-change-this]').forEach(function(el) {
    var fnName = el.getAttribute('data-on-change-this');
    el.onchange = window[fnName].bind(el, el);
  });

  var rows = $j(row).parent().children();
  parseRows(rows);
}

function delTerm( element ) {
  var row = $j(element).closest('tr');
  var rowParent = $j(row).parent();
  row.remove();
  var rows = rowParent.children();
  parseRows(rows);
}

function init() {
  updateButtons( $('executeButton') );
  $j('#Id').chosen();
  $j('#fieldsTable select').not("[name$='br\\]'], [name$='cnj\\]']").chosen({width: '101%'}); //Every select except brackets/and
  $j("#sortTable [name$='sort_field\\]']").chosen();
}

window.addEventListener( 'DOMContentLoaded', init );
