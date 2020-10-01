function switchDeviceOn( element, key ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'device';
  form.command.value = 'on';
  form.key.value = key;
  form.submit();
}

function switchDeviceOff( element, key ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'device';
  form.command.value = 'off';
  form.key.value = key;
  form.submit();
}

function deleteDevice( element ) {
  var form = element.form;
  form.view.value = currentView;
  form.action.value = 'delete';
  form.submit();
}

function configureButtons( element, name ) {
  var form = element.form;
  var checked = false;
  for (var i = 0; i < form.elements.length; i++) {
    if ( form.elements[i].name.indexOf(name) == 0) {
      if ( form.elements[i].checked ) {
        checked = true;
        break;
      }
    }
  }
  form.deleteBtn.disabled = !checked;
}

// Manage the New button
function New(el) {
  url = el.getAttribute('data-url');
  window.location.assign(url);
}

function initPage() {
  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Disable the back button if there is nothing to go back to
  $j('#backBtn').prop('disabled', !document.referrer.length);

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });
}

$j(document).ready(initPage);
