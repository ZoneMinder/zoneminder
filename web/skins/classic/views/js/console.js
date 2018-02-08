
function setButtonStates( element ) {
  var form = element.form;
  var checked = 0;
  for ( var i=0; i < form.elements.length; i++ ) {
    if (
      form.elements[i].type=="checkbox"
      &&
      form.elements[i].name=="markMids[]"
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
    if ( checked == 1 ) {
      $j(form.cloneBtn).css('display','inline');
    } else {
      form.cloneBtn.hide();
    }
  } else {
    form.cloneBtn.hide();
    form.editBtn.disabled = true;
    form.deleteBtn.disabled = true;
  }
}

function addMonitor(element) {
  var form = element.form;
  var dupParam;
  var monitorId=-1;
  if (form.addBtn.value == jsTranslatedCloneText) {
    // get the value of the first checkbox
    for ( var i = 0; i < form.elements.length; i++ ) {
      if ( form.elements[i].type == "checkbox" ) {
        if ( form.elements[i].checked ) {
          monitorId = form.elements[i].value;
          break;
        }
      }
    }
  }
  if ( monitorId != -1 ) {
    createPopup( '?view=monitor&dupId='+monitorId, 'zmMonitor0', 'monitor' );
  } else {
    window.location = '?view=add_monitors';
  }
}

function editMonitor( element ) {
  var form = element.form;
  var monitorIds = Array();

  for ( var i = 0; i < form.elements.length; i++ ) {
    if ( form.elements[i].type == "checkbox" ) {
      if ( form.elements[i].checked ) {
        monitorIds.push( form.elements[i].value );
        //form.elements[i].checked = false;
        //setButtonStates( form.elements[i] );
        //$(form.elements[i]).getParent( 'tr' ).removeClass( 'highlight' );
        //break;
      }
    }
  } // end foreach checkboxes
  if ( monitorIds.length == 1 )
        createPopup( '?view=monitor&mid='+monitorIds[0], 'zmMonitor'+monitorIds[0], 'monitor' );
  else if ( monitorIds.length > 1 ) 
        createPopup( '?view=monitors&'+(monitorIds.map(function(mid){return 'mids[]='+mid;}).join('&')), 'zmMonitors', 'monitors' );
}

function deleteMonitor( element ) {
  if ( confirm( 'Warning, deleting a monitor also deletes all events and database entries associated with it.\nAre you sure you wish to delete?' ) ) {
    var form = element.form;
    form.elements['action'].value = 'delete';
    form.submit();
  }
}

function reloadWindow() {
  window.location.replace( thisUrl );
}

function initPage() {
  reloadWindow.periodical( consoleRefreshTimeout );
  if ( showVersionPopup )
    createPopup( '?view=version', 'zmVersion', 'version' );
  if ( showDonatePopup )
    createPopup( '?view=donate', 'zmDonate', 'donate' );

  // Makes table sortable
  $j( function() {
    $j( "#consoleTableBody" ).sortable({
        handle: ".glyphicon-sort",
        update: applySort,
        axis:'Y' } );
    $j( "#consoleTableBody" ).disableSelection();
  } );
}

function applySort(event, ui) {
  var monitor_ids = $j(this).sortable('toArray');
  var ajax = new Request.JSON( {
      url: 'index.php?request=console',
      data: { monitor_ids: monitor_ids, action: 'sort' },
      method: 'post',
      timeout: AJAX_TIMEOUT
      } );
  ajax.send();
} // end function applySort(event,ui)

window.addEvent( 'domready', initPage );
