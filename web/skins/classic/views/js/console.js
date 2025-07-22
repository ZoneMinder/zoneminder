function setButtonStates(element) {
  const form = element.form;
  var checked = 0;
  for ( var i=0, len = form.elements.length; i < len; i++ ) {
    if (
      form.elements[i].type == "checkbox" &&
      form.elements[i].name == "markMids[]"
    ) {
      var tr = $j(form.elements[i]).closest("tr");
      if ( form.elements[i].checked ) {
        checked ++;
        tr.addClass("danger");
      } else {
        tr.removeClass("danger");
      }
    }
  }
  if ( checked ) {
    form.editBtn.disabled = false;
    form.deleteBtn.disabled = false;
    form.selectBtn.disabled = false;
    form.cloneBtn.disabled = false;
  } else {
    form.editBtn.disabled = true;
    form.deleteBtn.disabled = true;
    form.selectBtn.disabled = true;
    form.cloneBtn.disabled = true;
  }
}

function scanNetwork(element) {
  window.location.assign('?view=add_monitors');
}
function addMonitor(element) {
  if (user.Monitors == 'Create') {
    window.location.assign('?view=monitor');
  } else {
    alert('Need create monitors privilege');
  }
}

function cloneMonitor(element) {
  if (user.Monitors != 'Create') {
    alert('Need create monitors privilege');
    return;
  }
  var form = element.form;
  var monitorId = -1;
  // get the value of the first checkbox
  for ( var i=0, len=form.elements.length; i < len; i++ ) {
    if (
      form.elements[i].type == "checkbox" &&
      form.elements[i].name == "markMids[]" &&
      form.elements[i].checked
    ) {
      monitorId = form.elements[i].value;
      break;
    }
  } // end foreach element
  if ( monitorId != -1 ) {
    window.location.assign('?view=monitor&dupId='+monitorId);
  } else {
    alert('Please select a monitor to clone');
  }
}

function editMonitor( element ) {
  var form = element.form;
  var monitorIds = Array();

  for ( var i = 0; i < form.elements.length; i++ ) {
    if (
      form.elements[i].type == "checkbox" &&
      form.elements[i].name == "markMids[]" &&
      form.elements[i].checked
    ) {
      monitorIds.push( form.elements[i].value );
    }
  } // end foreach checkboxes
  if ( monitorIds.length == 1 ) {
    window.location.assign('?view=monitor&mid='+monitorIds[0]);
  } else if ( monitorIds.length > 1 ) {
    window.location.assign( '?view=monitors&'+(monitorIds.map(function(mid) {
      return 'mids[]='+mid;
    }).join('&')));
  }
}

function deleteMonitor( element ) {
  if (confirm('Deleting a monitor only marks it as deleted.  Events will age out. If you want them to be immediately removed, please delete them first.\nAre you sure you wish to delete?')) {
    const form = element.form;
    form.elements['action'].value = 'delete';
    form.submit();
  }
}

function selectMonitor(element) {
  var form = element.form;
  var url = thisUrl+'?view=console';
  for ( var i = 0; i < form.elements.length; i++ ) {
    if (
      form.elements[i].type == 'checkbox' &&
      form.elements[i].name == 'markMids[]' &&
      form.elements[i].checked
    ) {
      url += '&MonitorId[]='+form.elements[i].value;
    }
  }
  window.location.replace(url);
}

function reloadWindow() {
  window.location.replace( thisUrl );
}

// Manage the the Function modal and its buttons
function manageFunctionModal(evt) {
  evt.preventDefault();

  if ( !canEdit.Events ) {
    enoperm();
    return;
  }

  if ( ! $j('#modalFunction').length ) {
    // Load the Function modal on page load
    $j.getJSON(thisUrl + '?request=modal&modal=function')
        .done(function(data) {
          insertModalHtml('modalFunction', data.html);
          // Manage the CANCEL modal buttons
          $j('.funcCancelBtn').click(function(evt) {
            evt.preventDefault();
            $j('#modalFunction').modal('hide');
          });
          // Manage the SAVE modal buttons
          $j('.funcSaveBtn').click(function(evt) {
            evt.preventDefault();
            $j('#function_form').submit();
          });

          manageFunctionModal(evt);
        })
        .fail(logAjaxFail);
    return;
  }

  var mid = evt.currentTarget.getAttribute('data-mid');
  monitor = monitors[mid];
  if ( !monitor ) {
    console.error("No monitor found for mid " + mid);
    return;
  }

  var function_form = document.getElementById('function_form');
  if ( !function_form ) {
    console.error("Unable to find form with id function_form");
    return;
  }
  function_form.elements['newFunction'].onchange=function() {
    $j('#function_help div').hide();
    $j('#'+this.value+'Help').show();
    if ( this.value == 'Monitor' || this.value == 'None' ) {
      $j('#FunctionAnalysisEnabled').hide();
    } else {
      $j('#FunctionAnalysisEnabled').show();
    }
    if ( this.value == 'Record' || this.value == 'Nodect' ) {
      $j('#FunctionDecodingEnabled').show();
    } else {
      $j('#FunctionDecodingEnabled').hide();
    }
  };
  function_form.elements['newFunction'].value = monitor.Function;
  function_form.elements['newFunction'].onchange();

  function_form.elements['newEnabled'].checked = monitor.Enabled == '1';
  function_form.elements['newDecodingEnabled'].checked = monitor.DecodingEnabled == '1';
  function_form.elements['mid'].value = mid;
  document.getElementById('function_monitor_name').innerHTML = monitor.Name;

  $j('#modalFunction').modal('show');
} // end function manageFunctionModal

function initPage() {
  if (consoleRefreshTimeout > 0) {
    setInterval(reloadWindow, consoleRefreshTimeout);
  }
  if ( showDonatePopup ) {
    $j.getJSON(thisUrl + '?request=modal&modal=donate')
        .done(function(data) {
          insertModalHtml('donate', data.html);
          $j('#donate').modal('show');
          // Manage the Apply button
          $j('#donateApplyBtn').click(function(evt) {
            evt.preventDefault();
            $j('#donateForm').submit();
          });
        })
        .fail(logAjaxFail);
  }


  // Setup the thumbnail video animation
  if (!isMobile()) initThumbAnimation();

  $j('.functionLnk').click(manageFunctionModal);

  // Makes table sortable
  $j('#consoleTableBody').sortable({
    disabled: true,
    update: applySort,
    axis: 'Y'} );
} // end function initPage

function sortMonitors(button) {
  if (button.classList.contains('btn-success')) {
    $j( "#consoleTableBody" ).sortable('disable');
  } else {
    $j( "#consoleTableBody" ).sortable('enable');
  }
  button.classList.toggle('btn-success');
}

function applySort(event, ui) {
  var monitor_ids = $j(this).sortable('toArray');
  var data = {monitor_ids: monitor_ids, action: 'sort'};

  $j.post(thisUrl + '?request=console', data)
      .fail(logAjaxFail);
} // end function applySort(event,ui)

$j(document).ready(initPage);
