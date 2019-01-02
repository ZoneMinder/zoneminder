function selectMonitors() {
  createPopup( '?view=monitorselect&callForm=groupForm&callField=newGroup[MonitorIds]', 'zmMonitors', 'monitorselect' );
}

if ( refreshParent ) {
  opener.location.reload(true);
}

function configureButtons( element ) {
  if ( canEditGroups ) {
    var form = element.form;
    var disabled = false;

    if ( form.elements['newGroup[Name]'].value == '' ) {
      disabled = true;
    } 
    form.saveBtn.disabled = disabled;
  }
}

window.focus();
