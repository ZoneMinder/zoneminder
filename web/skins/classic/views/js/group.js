function configureButtons() {
  var form = $j('#groupForm')[0];
  if ( !form ) {
    console.log("No groupForm found");
    return;
  }
  if ( !canEditGroups ) {
    console.log("Cannot edit groups");
    form.elements['action'].disabled = disabled;
    return;
  }
  var disabled = false;

  if ( form.elements['newGroup[Name]'].value == '' ) {
    disabled = true;
  }
  form.elements['action'].disabled = disabled;
}

window.focus();
