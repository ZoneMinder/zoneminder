"use strict";
function selectFilter(element) {
  element.form.submit();
}

function validateForm(form) {
  const rows = $j(form).find('tbody').eq(0).find('tr');
  let obrCount = 0;
  let cbrCount = 0;
  for ( let i = 0; i < rows.length; i++ ) {
    if ( rows.length > 2 ) {
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
  const numbers_reg = /\D/;
  if ( numbers_reg.test(form.elements['filter[Query][limit]'].value) ) {
    alert('There appear to be non-numeric characters in your limit. Limit must be a positive integer value or empty.');
    return false;
  }
  if ( form.elements['filter[AutoDelete]'].checked ) {
    // if Delete action is Enabled should also have an unarchived term
    let have_archivestatus_term = false;
    for ( let i = 0; i < rows.length; i++ ) {
      if ( form.elements['filter[Query][terms][' + i + '][attr]'].value == 'Archived' ) {
        have_archivestatus_term = true;
      }
    }
    if ( ! have_archivestatus_term ) {
      return confirm('You have enabled deleting events but do not have a term referencing the archived status of the event.  This filter may delete events that you want to save! Are you sure?');
    }
  } else if ( form.elements['filter[UpdateDiskSpace]'].checked ) {
    let have_endtime_term = false;
    for ( let i = 0; i < rows.length; i++ ) {
      if (
        ( form.elements['filter[Query][terms][' + i + '][attr]'].value == 'EndDateTime' ) ||
        ( form.elements['filter[Query][terms][' + i + '][attr]'].value == 'EndTime' ) ||
        ( form.elements['filter[Query][terms][' + i + '][attr]'].value == 'EndDate' )
      ) {
        have_endtime_term = true;
        break;
      }
    }
    if ( ! have_endtime_term ) {
      return confirm('You don\'t have an End Date/Time term in your filter.  This might match recordings that are still in progress and so the UpdateDiskSpace action will be a waste of time and resources.  Ideally you should have an End Date/Time IS NOT NULL term.  Do you want to continue?');
    }
  } else if ( form.elements['filter[Background]'].checked ) {
    if ( ! (
      form.elements['filter[AutoArchive]'].checked ||
      form.elements['filter[AutoUnarchive]'].checked ||
      form.elements['filter[UpdateDiskSpace]'].checked ||
      form.elements['filter[AutoVideo]'].checked ||
      (form.elements['filter[AutoEmail]'] && form.elements['filter[AutoEmail]'].checked) ||
      (form.elements['filter[AutoMessage]'] && form.elements['filter[AutoMessage]'].checked) ||
      form.elements['filter[AutoExecute]'].checked ||
      form.elements['filter[AutoDelete]'].checked ||
      form.elements['filter[AutoCopy]'].checked ||
      form.elements['filter[AutoMove]'].checked
    ) ) {
      alert('You have chosen to run this filter in the background but not selected any actions.');
    }
  }
  return true;
}

function updateButtons(element) {
  const form = element.form;

  let canExecute = false;
  // If you can't edit events, then you cannot perform actions on them via filter
  if (canEdit['Events']) {
    if (
      (form.elements['filter[AutoArchive]'] && form.elements['filter[AutoArchive]'].checked) ||
      (form.elements['filter[AutoUnarchive]'] && form.elements['filter[AutoUnarchive]'].checked) ||
      (form.elements['filter[AutoCopy]'] && form.elements['filter[AutoCopy]'].checked) ||
      (form.elements['filter[AutoMove]'] && form.elements['filter[AutoMove]'].checked) ||
      (form.elements['filter[AutoVideo]'] && form.elements['filter[AutoVideo]'].checked) ||
      (form.elements['filter[AutoExecute]'].checked && form.elements['filter[AutoExecuteCmd]'].value != '') ||
      (form.elements['filter[AutoDelete]'].checked) ||
      (form.elements['filter[UpdateDiskSpace]'].checked) ||
      (form.elements['filter[AutoUpload]'] && form.elements['filter[AutoUpload]'].checked) ||
      (form.elements['filter[AutoEmail]'] && form.elements['filter[AutoEmail]'].checked) ||
      (form.elements['filter[AutoMessage]'] && form.elements['filter[AutoMessage]'].checked)
    ) {
      canExecute = true;
    }
  } else if (canView['Events']) {
    if (
      (form.elements['filter[AutoUpload]'] && form.elements['filter[AutoUpload]'].checked) ||
      (form.elements['filter[AutoEmail]'] && form.elements['filter[AutoEmail]'].checked) ||
      (form.elements['filter[AutoMessage]'] && form.elements['filter[AutoMessage]'].checked)
    ) {
      canExecute = true;
    }
  }

  document.getElementById('executeButton').disabled = !canExecute;
  // Anyone can create a filter, only canEdit:System or the owner of the filter can edit/delete it.
  const canWeEdit = (canEdit['System'] || (user.Id == filter.UserId) || !filter.Id);
  const canSave = (form.elements['filter[Name]'].value && canWeEdit);

  document.getElementById('Save').disabled = !canSave;
  document.getElementById('SaveAs').disabled = !canSave;
  document.getElementById('Delete').disabled = !(filter.Id && canWeEdit);
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
    $j(this.form.elements['filter[AutoMoveTo]']).hide();
  }
}

function click_autocopy(element) {
  updateButtons(this);
  if ( this.checked ) {
    $j(this.form.elements['filter[AutoCopyTo]']).css('display', 'inline');
  } else {
    $j(this.form.elements['filter[AutoCopyTo]']).hide();
  }
}

function checkValue( element ) {
  const rows = $j(element).closest('tbody').children();
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

function submitToEvents(element) {
  const form = element.form;
  form.elements['action'].value='';
  window.location.assign('?view=events&'+$j(form).serialize());
}

function submitToMontageReview(element) {
  const form = element.form;
  form.action = thisUrl + '?view=montagereview';
  window.location.assign('?view=montagereview&live=0&'+$j(form).serialize());
  history.replaceState(null, null, '?view=montagereview&live=0&' + $j(form).serialize());
}

function submitToExport(element) {
  const form = element.form;
  window.location.assign('?view=export&'+$j(form).serialize());
}

function submitAction(button) {
  const form = button.form;
  form.elements['action'].value = button.value;
  form.submit();
}

function deleteFilter(element) {
  const form = element.form;
  if (confirm(deleteSavedFilterString+" '"+form.elements['filter[Name]'].value+"'?")) {
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
  for ( let rowNum = 0; rowNum < rows.length; rowNum++ ) { //Each row is a term
    const queryPrefix = 'filter[Query][terms][';
    const inputTds = rows.eq(rowNum).children();

    if ( rowNum == 0 ) inputTds.eq(0).html('&nbsp'); //Remove and from first term
    if ( rowNum > 0 ) { //add and/or to 1+
      const cnjVal = inputTds.eq(0).children().val();
      const conjSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][cnj]').attr('id', queryPrefix + rowNum + '][cnj]');
      $j.each(conjTypes, function(i) {
        conjSelect.addClass('chosen chosen-full-width').append('<option value="' + i + '" >' + i + '</option>');
      });
      inputTds.eq(0).html(conjSelect).children().val(cnjVal === undefined ? 'and' : cnjVal);
    }

    const brackets = rows.length - 2;
    if ( brackets > 0 ) { // add bracket select to all rows
      const obrSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][obr]').attr('id', queryPrefix + rowNum + '][obr]');
      const cbrSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][cbr]').attr('id', queryPrefix + rowNum + '][cbr]');
      obrSelect.addClass('chosen').attr('data-placeholder', ' ').append('<option value="0"</option>');
      cbrSelect.addClass('chosen').attr('data-placeholder', ' ').append('<option value="0"</option>');
      for ( let i = 1; i <= brackets; i++ ) { // build bracket options
        obrSelect.append('<option value="' + i + '">' + '('.repeat(i) + '</option>');
        cbrSelect.append('<option value="' + i + '">' + ')'.repeat(i) + '</option>');
      }
      const obrVal = inputTds.eq(1).children().val() != undefined ? inputTds.eq(1).children().val() : 0; // Save currently selected bracket option
      const cbrVal = inputTds.eq(5).children().val() != undefined ? inputTds.eq(5).children().val() : 0;
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

    const attr = inputTds.eq(2).children().val();

    if ( attr == 'Archived' ) { //Archived types
      inputTds.eq(3).html('equal to<input type="hidden" name="filter[Query][terms][' + rowNum + '][op]" value="=">');
      const archiveSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (let i = 0; i < archiveTypes.length; i++) {
        archiveSelect.append('<option value="' + i + '">' + archiveTypes[i] + '</option>');
      }
      const archiveVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(archiveSelect).children().val(archiveVal).addClass('chosen chosen-full-width');
    } else if ( attr == 'AlarmedZoneId' ) {
      const zoneSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for ( monitor_id in monitors ) {
        for ( zone_id in zones ) {
          const zone = zones[zone_id];
          if ( monitor_id == zone.MonitorId ) {
            zoneSelect.append('<option value="' + zone_id + '">' + zone.Name + '</option>');
          }
        } // end foreach zone
      } // end foreach monitor
      const zoneVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(zoneSelect).children().val(zoneVal).addClass('chosen chosen-full-width');
    } else if ( attr.indexOf('Weekday') >= 0 ) { //Weekday selection
      const weekdaySelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (let i = 0; i < weekdays.length; i++) {
        weekdaySelect.append('<option value="' + i + '">' + weekdays[i] + '</option>');
      }
      const weekdayVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(weekdaySelect).children().val(weekdayVal).addClass('chosen chosen-full-width');
    } else if ( attr == 'StateId' ) { //Run state
      const stateSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (const key in states) {
        stateSelect.append('<option value="' + key + '">' + states[key] + '</option>');
      }
      const stateVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(stateSelect).children().val(stateVal).addClass('chosen chosen-full-width');
    } else if ( attr == 'ServerId' || attr == 'MonitorServerId' || attr == 'StorageServerId' || attr == 'FilterServerId' ) { //Select Server
      const serverSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (const key in servers) {
        serverSelect.append('<option value="' + key + '">' + servers[key] + '</option>');
      }
      const serverVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(serverSelect).children().val(serverVal).addClass('chosen chosen-full-width');
    } else if ( (attr == 'StorageId') || (attr == 'SecondaryStorageId') ) { //Choose by storagearea
      const storageSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (const key in storageareas ) {
        storageSelect.append('<option value="' + key + '">' + storageareas[key].Name + '</option>');
      }
      const storageVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(storageSelect).children().val(storageVal).addClass('chosen chosen-full-width');
    } else if ( attr == 'Monitor' ) {
      const monitorSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      sorted_monitor_ids.forEach(function(monitor_id) {
        monitorSelect.append('<option value="' + monitor_id + '">' + escapeHTML(monitors[monitor_id].Name) + '</option>');
      });
      const monitorVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(monitorSelect).children().val(monitorVal).addClass('chosen chosen-full-width');
    } else if ( attr == 'MonitorName' ) { //Monitor names
      const monitorSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      sorted_monitor_ids.forEach(function(monitor_id) {
        monitorSelect.append('<option value="' + monitors[monitor_id].Name + '">' + escapeHTML(monitors[monitor_id].Name) + '</option>');
      });
      const monitorVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(monitorSelect).children().val(monitorVal).addClass('chosen chosen-full-width');
    } else if ( attr == 'Tags' ) { // Tags
      const tagSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      for (const key in availableTags) {
        tagSelect.append('<option value="' + key + '">' + escapeHTML(availableTags[key]) + '</option>');
      };
      const tagVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(tagSelect).children().val(tagVal).addClass('chosen chosen-full-width');
    } else if ( attr == 'ExistsInFileSystem' ) {
      const select = $j('<select></select>').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]').addClass('chosen');
      for (const booleanVal in booleanValues ) {
        select.append('<option value="' + booleanVal + '">' + escapeHTML(booleanValues[booleanVal]) + '</option>');
      }
      let val = inputTds.eq(4).children().val();
      if ( ! val ) val = 'false'; // default to the first option false
      inputTds.eq(4).html(select).children().val(val);
    } else if ( attr == 'DiskPercent' ) {
      const textInput = $j('<input></input>').attr('type', 'number').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]').attr('min', '0').attr('step', '1');
      const textVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(textInput).children().val(textVal);
    } else { // Reset to regular text field and operator for everything that isn't special
      const textInput = $j('<input></input>').attr('type', 'text').attr('name', queryPrefix + rowNum + '][val]').attr('id', queryPrefix + rowNum + '][val]');
      const textVal = inputTds.eq(4).children().val();
      inputTds.eq(4).html(textInput).children().val(textVal);
    }

    // Validate the operator
    const opSelect = $j('<select></select>').attr('name', queryPrefix + rowNum + '][op]').attr('id', queryPrefix + rowNum + '][op]');
    let opVal = inputTds.eq(3).children().val();
    if ( attr == 'ExistsInFileSystem' ) {
      if ( ! opVal ) {
        // Default to equals so that something gets selected
        opVal = 'IS';
      }
      for (const key of ['IS', 'IS NOT']) {
        opSelect.append('<option value="' + key + '"'+(key == opVal ? ' selected="selected"' : '')+'>' + opTypes[key] + '</option>');
      }
    } else if (attr == 'Tags') {
      if ( ! opVal ) {
        // Default to LIKE so that something gets selected
        opVal = '=';
      }
      for ( const key in tags_opTypes ) {
        opSelect.append('<option value="' + key + '"'+(key == opVal ? ' selected="selected"' : '')+'>' + tags_opTypes[key] + '</option>');
      }
    } else {
      if ( ! opVal ) {
        // Default to equals so that something gets selected
        console.log("No value for operator. Defaulting to =");
        opVal = '=';
      }
      for ( const key in opTypes ) {
        opSelect.append('<option value="' + key + '"'+(key == opVal ? ' selected="selected"' : '')+'>' + opTypes[key] + '</option>');
      }
    }
    inputTds.eq(3).html(opSelect).children().val(opVal).addClass('chosen chosen-full-width');
    if ( attr.endsWith('DateTime') ) { //Start/End DateTime
      inputTds.eq(4).children().datetimepicker({timeFormat: "HH:mm:ss", dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false});
    } else if ( attr.endsWith('Date') ) { //Start/End Date
      inputTds.eq(4).children().datepicker({dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false});
    } else if ( attr.endsWith('Time')) { //Start/End Time
      inputTds.eq(4).children().timepicker({timeFormat: "HH:mm:ss", constrainInput: false});
    }

    const attr_element = inputTds.find("[name$='attr\\]']"); // Set attr list id and name
    const term = attr_element.attr('name').split(/[[\]]{1,2}/);
    console.log(term);
    term.length--;
    term.shift();
    term[2] = rowNum;
    inputTds.eq(2).children().eq(0).attr('name', 'filter'+stringFilter(term));
    inputTds.eq(2).children().eq(0).attr('id', 'filter'+stringFilter(term));
  } //End for each term/row
  // ICON This populates the url bar with contents of the form.  I'm not sure why
  // history.replaceState(null, null, '?view=filter&' + $j('#contentForm').serialize());
  applyChosen();
} // parseRows

function stringFilter(term) {
  var termString = '';
  term.forEach(function(item) {
    termString += '[' + item + ']';
  });
  return termString;
}

function addTerm( element ) {
  const row = $j(element).closest('tr');
  row.find('select').chosen('destroy');
  const newRow = row.clone().insertAfter(row);
  //newRow.find('select').each( function() { //reset new row to default
  //  if ($j(this).find('option').length > 0 )
  //    this[0].selected = 'selected';
  //});
  newRow.find('input[type="text"]').val('');
  newRow[0].querySelectorAll("button[data-on-click-this]").forEach(function(el) {
    const fnName = el.getAttribute("data-on-click-this");
    el.onclick = window[fnName].bind(el, el);
  });

  newRow[0].querySelectorAll('select[data-on-change-this]').forEach(function(el) {
    const fnName = el.getAttribute('data-on-change-this');
    el.onchange = window[fnName].bind(el, el);
  });

  const rows = $j(row).parent().children();
  parseRows(rows);
}

function delTerm( element ) {
  const row = $j(element).closest('tr');
  const rowParent = $j(row).parent();
  row.remove();
  const rows = rowParent.children();
  parseRows(rows);
}

function debugFilter() {
  getModal('filterdebug', $j(form).serialize());
}

function manageModalBtns(id) {
  // Manage the CANCEL modal button
  const cancelBtn = document.getElementById(id+"CancelBtn");
  if ( cancelBtn ) {
    document.getElementById(id+"CancelBtn").addEventListener("click", function onCancelClick(evt) {
      $j('#'+id).modal('hide');
    });
  }
}

// function getAvailableTags() {
//   $j.getJSON(thisUrl + '?request=tags&action=getavailabletags')
//       .done(function(data) {
//         return data.response;
//       })
//       .fail(logAjaxFail);
// }

function initPage() {
  updateButtons($j('#executeButton')[0]);
  parseRows($j('#fieldsTable tbody').children());
}

$j(document).ready(initPage);
