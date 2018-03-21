var jsTranslatedAddText;
var jsTranslatedCloneText;

function setButtonStates( element ) {
  var form = element.form;
  var checked = 0;
  for ( var i = 0; i < form.elements.length; i++ ) {
    if ( form.elements[i].type == "checkbox" ) {
      if ( form.elements[i].checked ) {
        if ( checked++ > 1 )
          break;
      }
    }
  }
  $(element).getParent( 'tr' ).toggleClass( 'highlight' );
  form.editBtn.disabled = (checked!=1);
  form.addBtn.value = (checked==1) ? jsTranslatedCloneText:jsTranslatedAddText;

  form.deleteBtn.disabled = (checked==0);
}

function addMonitor( element) {
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
  dupParam = (monitorId == -1 ) ? '': '&dupId='+monitorId;
  createPopup( '?view=monitor'+dupParam, 'zmMonitor0', 'monitor' );
}

function editMonitor( element ) {
  var form = element.form;
  for ( var i = 0; i < form.elements.length; i++ ) {
    if ( form.elements[i].type == "checkbox" ) {
      if ( form.elements[i].checked ) {
        var monitorId = form.elements[i].value;
        createPopup( '?view=monitor&mid='+monitorId, 'zmMonitor'+monitorId, 'monitor' );
        form.elements[i].checked = false;
        setButtonStates( form.elements[i] );
        //$(form.elements[i]).getParent( 'tr' ).removeClass( 'highlight' );
        break;
      }
    }
  }
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
  jsTranslatedAddText = translatedAddText;
  jsTranslatedCloneText = translatedCloneText;
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
