function validateForm ( form ) {
  let rows = $j(form).find('tbody').eq(0).find('tr');
  let obrCount = 0;
  let cbrCount = 0;
  for ( let i = 0; i < rows.length; i++ ) {
    if (rows.length > 2) {
      obrCount += parseInt(form.elements['filter[Query][terms][' + i + '][obr]'].value);
      cbrCount += parseInt(form.elements['filter[Query][terms][' + i + '][cbr]'].value);
    }
    if (form.elements['filter[Query][terms][' + i + '][val]'].value == '') {
      alert( errorValue );
      return false;
    }
  }
  if (obrCount - cbrCount != 0) {
    alert( errorBrackets );
    return false;
  }
  return true;
}

function updateButtons( element ) {
  var form = element.form;

  if ( element.type == 'checkbox' && element.checked ) {
    form.elements['executeButton'].disabled = false;
  } else {
    var canExecute = false;
    if ( form.elements['filter[AutoArchive]'] && form.elements['filter[AutoArchive]'].checked )
      canExecute = true;
    else if ( form.elements['filter[AutoVideo]'] && form.elements['filter[AutoVideo]'].checked )
      canExecute = true;
    else if ( form.elements['filter[AutoUpload]'] && form.elements['filter[AutoUpload]'].checked )
      canExecute = true;
    else if ( form.elements['filter[AutoEmail]'] && form.elements['filter[AutoEmail]'].checked )
      canExecute = true;
    else if ( form.elements['filter[AutoMessage]'] && form.elements['filter[AutoMessage]'].checked )
      canExecute = true;
    else if ( form.elements['filter[AutoExecute]'].checked && form.elements['filter[AutoExecuteCmd]'].value != '' )
      canExecute = true;
    else if ( form.elements['filter[AutoDelete]'].checked )
      canExecute = true;
    else if ( form.elements['filter[UpdateDiskSpace]'].checked )
      canExecute = true;
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

function checkValue ( element ) {
  let rows = $j(element).closest('tbody').children();
  parseRows(rows);
  clearValue(element);
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

function executeFilter( element ) {
  var form = element.form;
  form.action = thisUrl + '?view=events';
  form.elements['action'].value = 'execute';
  history.replaceState(null, null, '?view=filter&' + $j(form).serialize());
}

function saveFilter( element ) {
  var form = element.form;
  form.target = window.name;
  form.elements['action'].value = element.value;
  form.action = thisUrl + '?view=filter';
  form.submit();
}

function deleteFilter( element, name ) {
  if ( confirm( deleteSavedFilterString+" '"+name+"'?" ) ) {
    var form = element.form;
    form.elements['action'].value = 'delete';
    form.submit();
  }
}

function parseRows (rows) {
  for (let i = 0; i < rows.length; i++) { //Traverse terms(rows)
    let inputs = rows[i].children;  //Array of tds
    if (i == 0) $j(inputs[0]).html('&nbsp'); //Remove and from first term
    if (rows.length -1 > 0 && i > 0 && inputs[0].children.length == 0) { //add and/or to 1+ if doesn't exist
      $j(inputs[0]).html('<select name="filter[Query][terms][' + i + '][cnj]" id="filter[Query][terms][' + i + '][cnj]"><option value="and">and</option><option value="or">or</option></select>');
    }
    let brackets = rows.length - 2;
    if (brackets > 0) { //add bracket td to all rows
      let obr = '<select name="filter[Query][terms][' + i + '][obr]" id="filter[Query][terms][' + i + '][obr]"><option value="0"></option>';
      let cbr = '<select name="filter[Query][terms][' + i + '][cbr]" id="filter[Query][terms][' + i + '][cbr]"><option value="0"></option>';
      for (let k = 1; k <= brackets; k++) {//build bracket options
        obr += '<option value="' + k + '">' + '('.repeat(k) + '</option>';
        cbr += '<option value="' + k + '">' + ')'.repeat(k) + '</option>';
      }
      obr += '</select>';
      cbr += '</select>';
      let obrVal = $j(inputs[1]).children().val();  //Save currently selected bracket option
      let cbrVal = $j(inputs[5]).children().val();
      $j(inputs[1]).html(obr).children().val(obrVal); //Set bracket contents and assign saved value
      $j(inputs[5]).html(cbr).children().val(cbrVal);
    } else {
      $j(inputs[1]).html('&nbsp');
      $j(inputs[5]).html('&nbsp');
    }
    if ($j(inputs[2]).children().val() == "Archived") {  //Archived filter is very different.  Handles html changes.
      $j(inputs[3]).html('equal to<input type="hidden" name="filter[Query][terms][' + i + '][op]" value="=">');
      $j(inputs[4]).html('<select name="filter[Query][terms][' + i + '][val]" id="filter[Query][terms][' + i + '][val]"><option value="0">Unarchived Only</option><option value="1">Archived Only</option></select>');
    } else if ($j(inputs[3]).children().attr('type') == 'hidden' ) {
      $j(inputs[3]).html('<select name="filter[Query][terms][' + i + '][op]" id="filter[Query][terms][' + i + '][op]"><option value="=">equal to</option><option value="!=">not equal to</option><option value=">=">greater than or equal to</option><option value=">">greater than</option><option value="<">less than</option><option value="<=">less than or equal to</option><option value="=~">matches</option><option value="!~">does not match</option><option value="=[]">in set</option><option value="![]">not in set</option><option value="IS">is</option><option value="IS NOT">is not</option></select>');
      $j(inputs[4]).html('<input type="text" name="filter[Query][terms][' + i + '][val]" value="" id="filter[Query][terms][' + i + '][val]">');
    }
    for (let j = 0; j < inputs.length; j++) { //Set all query array values in case any were missed
      let input = inputs[j].children;
      if (input.length) { //Ignore placeholders.
        if (input[0].type == 'button' && rows.length == 1) { //if add/delete button disable when only term
          $j(input[1]).prop('disabled', true);
        } else if (input[0].type == 'button') {  //Enable if more than one term
          $j(input[1]).prop('disabled', false);
        } else {  // Set all non-button array values
          let term = input[0].name.split(/[[\]]{1,2}/);
          term.length--;
          term.shift();
          term[2] = i;
          input[0].name = 'filter'+stringFilter(term);
          input[0].id = 'filter'+stringFilter(term);
        }
      }
    }
  }
  history.replaceState(null, null, '?view=filter&' + $j('#contentForm').serialize());
}

function stringFilter (term) {
  let termString = '';
  term.forEach(function(item) {
   termString += '[' + item + ']';
  });
  return termString;
}

function addTerm( element ) {
  let row = $j(element).closest('tr');
  let newRow = row.clone().insertAfter(row);
  newRow.find('select').each( function () { //reset new row to default
    this[0].selected = 'selected';
  });
  newRow.find('input[type="text"]').val('');
  let rows = $j(row).parent().children();
  parseRows(rows);
}

function delTerm( element ) {
  let row = $j(element).closest('tr');
  let rowParent = $j(row).parent();
  row.remove();
  let rows = rowParent.children();
  parseRows(rows);
}

function init() {
  updateButtons( $('executeButton') );
}

window.addEvent( 'domready', init );
